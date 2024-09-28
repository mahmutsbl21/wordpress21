<?php

namespace DMS\Includes\Frontend\Scenarios;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Frontend\Handlers\Force_Redirection_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Services\Request_Params;
interface Mapping_Scenario_Interface {
    /**
     * Check the mapping scenario
     *
     * @param Mapping_Handler $mapping_handler Mapping handler instance
     * @param Request_Params $request_params Request params instance
     *
     * @return null|Mapping_Value
     */
    public function object_mapped( Mapping_Handler $mapping_handler, Request_Params $request_params ) : ?Mapping_Value;

}
