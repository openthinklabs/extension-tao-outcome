<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoResultServer\models\Import\Service;

use common_exception_Error;
use common_exception_InvalidArgumentType;
use common_exception_NotFound;
use common_exception_NotImplemented;
use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use oat\generis\model\data\Ontology;
use oat\taoDelivery\model\execution\DeliveryExecutionService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\Import\Exception\ImportResultException;
use oat\taoResultServer\models\Import\Factory\QtiResultXmlFactory;
use oat\taoResultServer\models\Import\Input\ImportResultInput;
use oat\taoResultServer\models\Parser\QtiResultParser;
use qtism\data\AssessmentItemRef;
use qtism\data\AssessmentTest;
use qtism\data\QtiComponentCollection;
use qtism\data\storage\xml\XmlStorageException;
use taoQtiTest_models_classes_QtiTestService;
use taoResultServer_models_classes_WritableResultStorage;

class QtiResultXmlImporter
{
    private Ontology $ontology;
    private ResultServerService $resultServerService;
    private QtiResultXmlFactory $qtiResultXmlFactory;
    private QtiResultParser $qtiResultParser;
    private taoQtiTest_models_classes_QtiTestService $qtiTestService;
    private DeliveryExecutionService $deliveryExecutionService;

    public function __construct(
        Ontology $ontology,
        ResultServerService $resultServerService,
        QtiResultXmlFactory $qtiResultXmlFactory,
        QtiResultParser $qtiResultParser,
        taoQtiTest_models_classes_QtiTestService $qtiTestService,
        DeliveryExecutionService $deliveryExecutionService
    ) {
        $this->ontology = $ontology;
        $this->resultServerService = $resultServerService;
        $this->qtiResultXmlFactory = $qtiResultXmlFactory;
        $this->qtiResultParser = $qtiResultParser;
        $this->qtiTestService = $qtiTestService;
        $this->deliveryExecutionService = $deliveryExecutionService;
    }

    /**
     * @throws ImportResultException
     * @throws XmlStorageException
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     * @throws common_exception_NotFound
     * @throws common_exception_NotImplemented
     * @throws core_kernel_persistence_Exception
     */
    public function importByResultInput(ImportResultInput $input): void
    {
        $this->importQtiResultXml(
            $input->getDeliveryExecutionId(),
            $this->qtiResultXmlFactory->createByImportResult($input)
        );
    }

    /**
     * @throws ImportResultException
     * @throws XmlStorageException
     * @throws common_exception_NotFound
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     * @throws common_exception_NotImplemented
     * @throws core_kernel_persistence_Exception
     */
    public function importQtiResultXml(
        string $deliveryExecutionId,
        string $xmlContent
    ): void {
        $resultStorage = $this->resultServerService->getResultStorage();

        if (!$resultStorage instanceof taoResultServer_models_classes_WritableResultStorage) {
            throw new ImportResultException(
                sprintf(
                    'ResultStorage must be an instance of %s. Instance of %s provided',
                    taoResultServer_models_classes_WritableResultStorage::class,
                    get_class($resultStorage)
                )
            );
        }

        $resultMapper = $this->qtiResultParser->parse($xmlContent);
        $deliveryExecution = $this->deliveryExecutionService->getDeliveryExecution($deliveryExecutionId);
        $delivery = $deliveryExecution->getDelivery();
        $test = $this->ontology->getResource($delivery->getUri())
            ->getOnePropertyValue($this->ontology->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN));

        $this->storeTestVariables(
            $resultStorage,
            $test->getUri(),
            $deliveryExecutionId,
            $resultMapper->getTestVariables()
        );
        $this->storeItemVariables(
            $resultStorage,
            $test->getUri(),
            $this->getItems($test),
            $deliveryExecutionId,
            $resultMapper->getItemVariables()
        );
    }

    private function storeItemVariables(
        taoResultServer_models_classes_WritableResultStorage $resultStorage,
        string $testUri,
        QtiComponentCollection $items,
        string $deliveryExecutionId,
        array $itemVariablesByItemResult
    ): void {
        foreach ($itemVariablesByItemResult as $itemResultIdentifier => $itemVariables) {
            $item = $this->getItem($itemResultIdentifier, $items);

            if (null === $item) {
                continue;
            }

            $resultStorage->storeItemVariables(
                $deliveryExecutionId,
                $testUri,
                $item->getHref(),
                $itemVariables,
                sprintf('%s.%s.0', $deliveryExecutionId, $itemResultIdentifier)
            );
        }
    }

    private function storeTestVariables(
        taoResultServer_models_classes_WritableResultStorage $resultStorage,
        string $testId,
        string $deliveryExecutionId,
        array $itemVariablesByTestResult
    ): void {
        foreach ($itemVariablesByTestResult as $test => $testVariables) {
            $resultStorage->storeTestVariables($deliveryExecutionId, $testId, $testVariables, $deliveryExecutionId);
        }
    }

    private function getItem(string $identifier, QtiComponentCollection $items): ?AssessmentItemRef
    {
        /** @var AssessmentItemRef $item */
        foreach ($items as $item) {
            if ($item->getIdentifier() === $identifier) {
                return $item;
            }
        }

        return null;
    }

    private function getItems(core_kernel_classes_Resource $test): QtiComponentCollection
    {
        $testDoc = $this->qtiTestService->getDoc($test);
        /** @var AssessmentTest $test */
        $assessmentTest = $testDoc->getDocumentComponent();

        return $assessmentTest->getComponentsByClassName('assessmentItemRef');
    }
}
