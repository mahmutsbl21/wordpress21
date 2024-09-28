<?php

namespace DMS\Includes\Frontend\Handlers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Frontend\Mapper_Factory;
use DMS\Includes\Frontend\Services\Request_Params;
use DMS\Includes\Utils\Helper;
use Exception;
class Mapping_Handler {
    /**
     * Matching mapping value
     *
     * @var Mapping_Value|null
     */
    public ?Mapping_Value $matching_mapping_value = null;

    /**
     * Request params instance
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * Frontend instance
     *
     * @var Frontend
     */
    public Frontend $frontend;

    /**
     * Global domain mapping handler instance
     *
     * @var Global_Domain_Mapping_Handler|null
     */
    public ?Global_Domain_Mapping_Handler $global_domain_mapping;

    /**
     * Flag for checking if there was mapping or not
     *
     * @var bool
     */
    public bool $mapped = false;

    /**
     * Matched mapping
     *
     * @var mixed
     */
    public ?Mapping $mapping;

    /**
     * Mapping values of the matching mapping
     *
     * @var array|null
     */
    public ?array $mapping_values;

    /**
     * Flag for checking if the domain and path matches with some mapping
     *
     * @var null|bool
     */
    public ?bool $domain_path_match = null;

    /**
     * URL to redirect to if a redirection is needed.
     *
     * @var string
     */
    public string $redirect_to = '';

    /**
     * Constructor
     *
     * @param Request_Params $request_params Request params instance
     * @param null|Global_Domain_Mapping_Handler $global_domain_mapping Global domain mapping handler instance
     * @param Frontend $frontend Frontend handler instance
     */
    public function __construct( Request_Params $request_params, ?Global_Domain_Mapping_Handler $global_domain_mapping, Frontend $frontend ) {
        $this->frontend = $frontend;
        $this->request_params = $request_params;
        $this->global_domain_mapping = $global_domain_mapping;
        $this->define_hooks();
    }

    /**
     * Define hooks
     *
     * @return void
     */
    public function define_hooks() : void {
        add_action( 'pre_get_posts', array($this, 'run'), 9998 );
        add_action( 'redirect_canonical', array($this, 'prevent_canonical_redirection'), 9999 );
        add_filter(
            'wp_redirect',
            array($this, 'prevent_redirection'),
            9999,
            2
        );
    }

    /**
     * The main function which gets matching mapping and mapping value
     * During pre_get_posts hook, modifies the main query, and handles mapping
     *
     * @param $query
     *
     * @return object
     */
    public function run( $query ) : object {
        try {
            if ( $query->is_main_query() && !is_admin() ) {
                $this->mapping = $this->matching_mapping_from_db();
                $this->domain_path_match = $this->is_the_path_correct();
                if ( $this->domain_path_match ) {
                    $this->mapping_values = ( $this->mapping ? Mapping_Value::where( [
                        'mapping_id' => $this->mapping->id,
                    ] ) : [] );
                    if ( $this->mapping_values ) {
                        $mapping_value = $this->frontend->mapping_scenarios->run_object_mapped_scenario( $this, $this->request_params );
                        if ( $mapping_value ) {
                            $this->matching_mapping_value = $mapping_value;
                            $mapper = ( new Mapper_Factory() )->make( $this->matching_mapping_value, $query );
                            $query = $mapper->get_query();
                            $this->mapped = 1;
                            if ( method_exists( $this, 'add_favicon_and_custom_html__premium_only' ) ) {
                                $this->add_favicon_and_custom_html__premium_only();
                            }
                        }
                    }
                    if ( !empty( $this->frontend->global_domain_mapping ) && empty( $this->mapped ) ) {
                        // Check global domain mapping
                        if ( method_exists( $this, 'check_global_domain_existence__premium_only' ) ) {
                            $this->check_global_domain_existence__premium_only();
                        }
                    }
                }
            }
            return $query;
        } catch ( Exception $exception ) {
            Helper::log( $exception, __METHOD__ );
            return $query;
        }
    }

    /**
     * Check is the path incorrect, if so redirect to the right url
     *
     * @return bool
     */
    public function is_the_path_correct() {
        // Check if both mapping path and request path are not empty
        if ( !empty( $this->mapping->path ) && !empty( $this->request_params->path ) ) {
            $mapping_host = $this->mapping->host;
            $mapping_path = $this->mapping->path;
            $request_path = $this->request_params->path;
            // Check if the request path starts with the mapping path, case-insensitively
            if ( str_starts_with( strtolower( $request_path ), strtolower( $mapping_path ) ) ) {
                // Correct the case of the request path if necessary
                if ( !str_starts_with( $request_path, $mapping_path ) ) {
                    $corrected_path = str_replace( strtolower( $mapping_path ), $mapping_path, strtolower( $request_path ) );
                    $url = Helper::generate_url( $mapping_host, $corrected_path );
                }
                // Redirect if the URL is set
                if ( !empty( $url ) ) {
                    $this->redirect_to = $url;
                    add_action( 'template_redirect', array($this, 'redirect_to_correct_url'), 1 );
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Redirect to the correct url
     *
     * @return void
     */
    public function redirect_to_correct_url() {
        if ( !empty( $this->redirect_to ) ) {
            Helper::redirect_to( $this->redirect_to );
        }
    }

    /**
     * Checks matching mapping from db
     *
     * @return false|Mapping
     */
    public function matching_mapping_from_db() : ?Mapping {
        try {
            $empty_path = $this->request_params->get_path() == '';
            $args = [
                'host' => $this->request_params->get_domain(),
            ];
            $all_mappings = Mapping::where( $args );
            if ( !empty( $all_mappings ) ) {
                if ( !$empty_path ) {
                    // Check maybe there is mapping with the requested url path
                    $mappings = array_values( array_filter( $all_mappings, function ( $item ) {
                        return strtolower( $this->request_params->get_path() ) === strtolower( $item->path ) && !empty( $item->path );
                    } ) );
                    // Check the mapping the path of which is contained in the requested url path
                    if ( empty( $mappings ) ) {
                        $mappings = array_values( array_filter( $all_mappings, function ( $item ) {
                            return str_starts_with( strtolower( $this->request_params->get_path() ), strtolower( $item->path ) );
                        } ) );
                    }
                } else {
                    $mappings = array_values( array_filter( $all_mappings, function ( $item ) {
                        return empty( $item->path );
                    } ) );
                }
                if ( empty( $mappings ) ) {
                    return null;
                }
                return $mappings[0];
            }
            return null;
        } catch ( Exception $e ) {
            Helper::log( $e, __METHOD__ );
            return null;
        }
    }

    /**
     * Prepares value instance
     *
     * @param $id
     * @param $type
     *
     * @return Mapping_Value
     */
    public function prepare_value_instance( $id, $type ) : Mapping_Value {
        $data = array(
            'object_id'   => $id,
            'object_type' => $type,
        );
        return Mapping_Value::make( $data );
    }

    /**
     * Prevents redirection of the mapped page to canonical url
     *
     * @param $canonical
     *
     * @return false|mixed
     */
    public function prevent_canonical_redirection( $canonical ) : ?string {
        if ( $this->mapped ) {
            return null;
        }
        return $canonical;
    }

    /**
     * Prevent redirection added by other plugins
     *
     * @param $location
     * @param $status
     *
     * @return false|mixed
     */
    public function prevent_redirection( $location, $status ) {
        if ( $this->mapped ) {
            return false;
        }
        return $location;
    }

}
