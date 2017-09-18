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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoResultServer\models\classes;

interface ResultServerService {
    
    const SERVICE_ID = 'taoResultServer/resultservice';

    const PROPERTY_SERVER_CLASS	= 'http://www.tao.lu/Ontologies/TAOResultServer.rdf#ResultServer';

    const PROPERTY_MODEL_CLASS = 'http://www.tao.lu/Ontologies/TAOResultServer.rdf#ResultServerModel';

    const PROPERTY_MODEL_PROP ='http://www.tao.lu/Ontologies/TAOResultServer.rdf#hasResultServerModel';

    const PROPERTY_MODEL_IMPL_PROP = 'http://www.tao.lu/Ontologies/TAOResultServer.rdf#implementation';

    const PROPERTY_RESULT_SERVER = 'http://www.tao.lu/Ontologies/TAOResultServer.rdf#void';

    /**
     * For legacy non stateless storage
     * 
     * @param \core_kernel_classes_Resource $compiledDelivery
     * @param string $executionIdentifier
     */
    public function initResultServer($compiledDelivery, $executionIdentifier);

    /**
     * Returns the storage engine of the result server
     *
     * @param string $deliveryId
     * @throws \common_exception_Error
     * @return \taoResultServer_models_classes_ReadableResultStorage|\taoResultServer_models_classes_WritableResultStorage|oat\taoResultServer\models\classes\ResultManagement
     */
    public function getResultStorage($deliveryId);
}
