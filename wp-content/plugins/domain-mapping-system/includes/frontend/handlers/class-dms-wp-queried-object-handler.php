<?php

namespace DMS\Includes\Handlers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Freemius;
use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Frontend\Handlers\Favicon_Handler;
use DMS\Includes\Frontend\Handlers\Global_Domain_Mapping_Handler;
use DMS\Includes\Frontend\Handlers\Map_Html_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Services\Request_Params;
use DMS\Includes\Utils\Helper;
use Exception;
class WP_Queried_Object_Handler {
    /**
     * Frontend instance
     *
     * @var Frontend
     */
    public Frontend $frontend;

    /**
     * Global domain mapping handler instance
     *
     * @var mixed
     */
    public ?object $global_domain_mapping_handler;

    /**
     * Mapping handler instance
     *
     * @var Mapping_Handler
     */
    public Mapping_Handler $mapping_handler;

    /**
     * WP query instance
     *
     * @var mixed
     */
    public ?object $wp_query;

    /**
     * Matched mapping
     *
     * @var Mapping|mixed
     */
    public ?Mapping $matched_mapping;

    /**
     * The current object id
     *
     * @var null|int
     */
    public ?int $object_id = null;

    /**
     * The current object type
     *
     * @var null|string
     */
    public ?string $object_type = null;

    /**
     * Request params instance
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * Freemius instance
     *
     * @var \Freemius|null
     */
    public ?\Freemius $fs;

    /**
     * Scenarios
     *
     * @var string[]
     */
    public array $scenarios = [
        'empty_object_scenario__premium_only',
        'wrong_path_scenario__premium_only',
        'most_matching_mapping_scenario',
        'mapped_cpt_scenario__premium_only'
    ];

    /**
     * Constructor
     *
     * @param Frontend $frontend Frontend instance
     * @param null|Global_Domain_Mapping_Handler $global_domain_mapping_handler Global domain mapping instance on premium version
     * @param Mapping_Handler $mapping_handler Mapping Handler
     * @param Request_Params $request_params Request params instance
     */
    public function __construct(
        Frontend $frontend,
        ?Global_Domain_Mapping_Handler $global_domain_mapping_handler,
        Mapping_Handler $mapping_handler,
        Request_Params $request_params
    ) {
        $this->frontend = $frontend;
        $this->global_domain_mapping_handler = $global_domain_mapping_handler;
        $this->mapping_handler = $mapping_handler;
        $this->request_params = $request_params;
        $this->fs = Freemius::getInstance()->get_freemius();
        add_action(
            'wp',
            array($this, 'catch_queried_object'),
            15,
            1
        );
    }

    /**
     * Catch queried object
     *
     *
     * @return void
     */
    public function catch_queried_object() : void {
        try {
            global $wp_query;
            $result = null;
            $this->wp_query = $wp_query;
            if ( !is_admin() && (!empty( $this->global_domain_mapping_handler->mapped ) || $this->mapping_handler->domain_path_match) && !empty( $this->request_params->path ) ) {
                // Get current object id and type
                $this->maybe_category();
                $this->maybe_post();
                $this->maybe_shop();
                // Loop through scenarios
                foreach ( $this->scenarios as $method ) {
                    if ( method_exists( $this, $method ) ) {
                        $result = $this->{$method}();
                        if ( !empty( $result ) ) {
                            break;
                        }
                    }
                }
                // If Mapping was returned add favicon and custom html
                // If url was returned redirect to the given url
                if ( $result instanceof Mapping ) {
                    $this->matched_mapping = $result;
                    add_filter( 'get_site_icon_url', [new Favicon_Handler($this->matched_mapping->attachment_id), 'override'] );
                    add_action( 'wp_head', array(new Map_Html_Handler($this->matched_mapping->custom_html), 'override') );
                    return;
                } elseif ( is_string( $result ) ) {
                    Helper::redirect_to( $result );
                    exit;
                }
            }
        } catch ( Exception $exception ) {
            // If error was thrown show 404 not found
            Helper::log( $exception, __METHOD__ );
            $wp_query->set_404();
            status_header( 404 );
        }
    }

    /**
     * Check if the current page is term page
     *
     * @return void
     */
    public function maybe_category() : void {
        if ( is_category() ) {
            $this->object_id = $this->wp_query->get_queried_object()->term_id;
            $this->object_type = 'term';
        }
    }

    /**
     * Check if the current page is post page
     *
     * @return void
     */
    public function maybe_post() : void {
        if ( is_single() || is_page() || is_home() ) {
            $this->object_id = $this->wp_query->get_queried_object_id();
            $this->object_type = 'post';
        }
    }

    /**
     * Check is the current page is shop page
     *
     * @return void
     */
    public function maybe_shop() : void {
        if ( function_exists( 'is_shop' ) && is_shop() ) {
            $this->object_id = Helper::get_shop_page_association();
            $this->object_type = 'post';
        }
    }

    /**
     * Check the most matching Mapping and return
     *
     * @return Mapping|null
     */
    public function most_matching_mapping_scenario() : ?Mapping {
        $mapping_value = Mapping_Value::where( [
            'object_id'   => $this->object_id,
            'object_type' => $this->object_type,
        ] );
        if ( !empty( $mapping_value ) ) {
            $mapping_value = $mapping_value[0];
            $mapping = Mapping::where( [
                'path' => $this->request_params->path,
                'host' => $this->request_params->domain,
                'id'   => $mapping_value->mapping_id,
            ] );
        }
        return ( !empty( $mapping[0]->host ) ? $mapping[0] : null );
    }

}
