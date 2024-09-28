<?php

namespace DMS\Includes\Frontend\Scenarios;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Handlers\Force_Redirection_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Services\Request_Params;
class Mapping_Scenario {
    /**
     * The array of Scenario classes
     *
     * @var string[]
     */
    public array $list;

    /**
     * Define mapping scenarios
     */
    public function __construct() {
        $this->list = array(
            'DMS\\Includes\\Frontend\\Scenarios\\Simple_Object_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Global_Term_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Global_Parent_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Short_Child_Page_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Shop_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Global_Product_Mapping'
        );
    }

    /**
     * Loop through mapping scenarios
     *
     * @param Mapping_Handler $handler Mapping handler instance
     * @param Request_Params $request_params Request params instance
     *
     * @return null|Mapping_Value
     */
    public function run_object_mapped_scenario( Mapping_Handler $handler, Request_Params $request_params ) : ?Mapping_Value {
        $list = apply_filters( 'dms_mapping_scenarios_list', $this->list );
        foreach ( $list as $scenario ) {
            if ( class_exists( $scenario ) ) {
                $scenario_instance = new $scenario();
                if ( $mapping_value = $scenario_instance->object_mapped( $handler, $request_params ) ) {
                    break;
                }
            }
        }
        return $mapping_value ?? null;
    }

}
