<?php

namespace DMS\Includes\Frontend\Scenarios;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Handlers\Force_Redirection_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Services\Request_Params;
use DMS\Includes\Utils\Helper;
class Simple_Object_Mapping implements Mapping_Scenario_Interface {
    /**
     * Check the scenario and return the corresponding mapping value
     * if not the following scenario return null
     *
     * @param Mapping_Handler $mapping_handler Mapping handler instance
     * @param Request_Params $request_params Request params instance
     *
     * @return null|Mapping_Value
     */
    function object_mapped( Mapping_Handler $mapping_handler, Request_Params $request_params ) : ?Mapping_Value {
        $mapping = $mapping_handler->mapping;
        $matched_mapping_value = null;
        foreach ( $mapping_handler->mapping_values as $value ) {
            $value->object_id = (int) $value->object_id;
            $object_type = $value->object_type;
            $value_link = ( $object_type == 'post' ? get_permalink( $value->object_id ) : get_term_link( $value->object_id ) );
            $value_path = trim( wp_parse_url( $value_link, PHP_URL_PATH ), '/' );
            if ( is_wp_error( $value_link ) ) {
                continue;
            }
            $primary = $value->primary || count( $mapping_handler->mapping_values ) == 1;
            if ( $primary && $request_params->domain == $mapping->host && $request_params->path == $mapping->path ) {
                $matched_mapping_value = $value;
                break;
            } else {
                $possible_mapping_path = implode( '/', [$mapping->path, $value_path] );
                if ( !$primary && str_starts_with( trim( $request_params->path, '/' ), trim( $possible_mapping_path, '/' ) ) ) {
                    $matched_mapping_value = $value;
                    break;
                }
            }
        }
        return $matched_mapping_value;
    }

}
