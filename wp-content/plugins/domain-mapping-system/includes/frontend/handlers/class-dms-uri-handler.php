<?php

namespace DMS\Includes\Frontend\Handlers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Frontend\Services\Request_Params;
use DMS\Includes\Utils\Helper;
use Exception;
class URI_Handler {
    /**
     * Global url rewriting constant
     */
    const REWRITING_GLOBAL = 1;

    /**
     * Selective url rewriting constant
     */
    const REWRITING_SELECTIVE = 2;

    /**
     * The url rewriting setting value
     *
     * @var string|null
     */
    public ?string $url_rewrite;

    /**
     * Rewrite scenario (global, selective)
     *
     * @var mixed
     */
    public ?string $rewrite_scenario = null;

    /**
     * Mapping handler instance
     *
     * @var Mapping_Handler
     */
    public Mapping_Handler $mapping_handler;

    /**
     * Request Params instance
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * Global domain mapping handler instance
     *
     * @var mixed
     */
    public ?Global_Domain_Mapping_Handler $global_mapping_handler;

    /**
     * Constructor
     *
     * @param  Request_Params  $request_params  Request params instance
     * @param  Mapping_Handler  $mapping_handler  Mapping Handler instance
     */
    public function __construct( Request_Params $request_params, Mapping_Handler $mapping_handler, ?Global_Domain_Mapping_Handler $global_mapping_handler ) {
        $this->request_params = $request_params;
        $this->mapping_handler = $mapping_handler;
        $this->global_mapping_handler = $global_mapping_handler;
        $this->init();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init() : void {
        $this->define_rewrite_options();
        $this->prepare_assets_uri_filters();
        if ( method_exists( $this, 'prepare_uri_filters__premium_only' ) ) {
            $this->prepare_uri_filters__premium_only();
        }
    }

    /**
     * Define rewrite options
     *
     * @return void
     */
    public function define_rewrite_options() : void {
        $this->url_rewrite = Setting::find( 'dms_rewrite_urls_on_mapped_page' )->get_value();
        if ( !empty( $this->url_rewrite ) ) {
            $rewrite_scenario = Setting::find( 'dms_rewrite_urls_on_mapped_page_sc' )->get_value();
            $this->rewrite_scenario = ( !empty( $rewrite_scenario ) && in_array( $rewrite_scenario, [self::REWRITING_GLOBAL, self::REWRITING_SELECTIVE] ) ? $rewrite_scenario : self::REWRITING_GLOBAL );
        }
    }

    /**
     * Prepare uri filters
     *
     * @return void
     */
    public function prepare_assets_uri_filters() : void {
        add_filter(
            'plugins_url',
            array($this, 'rewrite_plugins_url'),
            99,
            3
        );
        add_filter(
            'rest_url',
            array($this, 'rewrite_rest_url'),
            99,
            2
        );
        add_filter(
            'script_loader_src',
            array($this, 'replace_script_style_src'),
            10,
            2
        );
        add_filter(
            'style_loader_src',
            array($this, 'replace_script_style_src'),
            10,
            2
        );
        add_filter(
            'admin_url',
            array($this, 'rewrite_admin_url'),
            999,
            4
        );
        add_filter(
            'script_module_loader_src',
            array($this, 'rewrite_script_modules_src'),
            10,
            2
        );
        add_filter(
            'wp_get_attachment_image_src',
            array($this, 'rewrite_attachment_src'),
            10,
            4
        );
        add_filter(
            'get_header_image_tag',
            array($this, 'rewrite_header_image_markup'),
            10,
            3
        );
        add_filter(
            'wp_calculate_image_srcset',
            array($this, 'rewrite_image_srcset'),
            10,
            5
        );
        add_filter(
            'template_directory_uri',
            array($this, 'rewrite_template_uri'),
            10,
            3
        );
        add_filter(
            'stylesheet_directory_uri',
            array($this, 'rewrite_stylesheet_uri'),
            10,
            3
        );
        add_filter(
            'the_content',
            array($this, 'rewrite_the_content'),
            10,
            1
        );
        // Divi related
        add_filter( 'et_builder_custom_fonts', array($this, 'rewrite_et_builder_custom_fonts') );
        add_filter(
            'et_core_page_resource_tag',
            array($this, 'rewrite_et_core_page_resource_tag'),
            999,
            5
        );
    }

    /**
     * Flag to allow links rewriting
     * 
     * @return bool
     */
    public function is_allowed_to_rewrite_links() {
        return !empty( $this->url_rewrite );
    }

    /**
     * Rename modules src
     *
     * @param $url
     *
     * @return mixed|string
     */
    public function rewrite_script_modules_src( $url ) {
        return self::replace_host_occurrence( $url );
    }

    /**
     * Replace host occurrence
     *
     * @param $data
     *
     * @return string
     */
    public function replace_host_occurrence( $data ) : string {
        $host = $this->request_params->get_base_host();
        $dot = '';
        if ( $this->request_params->is_subdirectory_install() ) {
            $path = $this->request_params->get_base_path();
            $path = explode( '/', $path );
            $path = join( '\\/', $path );
            return preg_replace_callback(
                '/(https?:\\/\\/)(' . $host . ')((\\/' . $path . '\\/\\w+)*\\/)?([\\w\\-.]+[^#?\\s]+)' . $dot . '(#[\\w\\-]+)?/',
                array($this, 'actual_host_replace'),
                $data,
                -1
            ) ?? $data;
        }
        return preg_replace_callback(
            '/(https?:\\/\\/)(' . $host . ')((\\/\\w+)*\\/)?([\\w\\-.]+[^#?\\s]+)' . $dot . '(#[\\w\\-]+)?/',
            array($this, 'actual_host_replace'),
            $data,
            -1
        ) ?? $data;
    }

    /**
     * Rewrite rest url
     *
     * @param $url
     * @param $path
     *
     * @return array|mixed|string|string[]
     */
    public function rewrite_rest_url( $url, $path ) : string {
        return self::replace_host_occurrence( $url );
    }

    /**
     * Rewrite plugins_url filter
     *
     * @param $url
     * @param $path
     * @param $plugin
     *
     * @return string
     */
    public function rewrite_plugins_url( $url, $path, $plugin ) {
        return self::replace_host_occurrence( $url );
    }

    /**
     * Gets rewritten url
     *
     * @param  null|int|string  $key
     * @param  null|string  $link
     *
     * @return array|string|string[]|null
     */
    public function get_rewritten_url( $key, ?string $link ) : ?string {
        try {
            $host = $this->request_params->get_base_host();
            if ( $this->rewrite_scenario == self::REWRITING_SELECTIVE ) {
                $mapping = null;
                $mapping_values = Mapping_Value::where( [
                    'object_id' => $key,
                ] );
                if ( !empty( $mapping_values ) ) {
                    $mapping_value = $mapping_values[0];
                    $mapping = Mapping::find( $mapping_value->mapping_id );
                }
                if ( empty( $mapping ) && !empty( $this->mapping_handler->frontend->global_domain_mapping ) && !empty( $this->mapping_handler->frontend->main_mapping ) ) {
                    // Set global domain
                    $mapping = $this->mapping_handler->frontend->main_mapping;
                }
                if ( !empty( $mapping ) && !empty( $mapping->host ) ) {
                    $replace_with = $mapping->host . (( !empty( $mapping->path ) ? '/' . $mapping->path : '' ));
                    $link_without_scheme = preg_replace( "~^(https?://)~i", '', $link );
                    if ( !str_starts_with( $link_without_scheme, $replace_with ) ) {
                        $mapped_link = str_ireplace( $host, $replace_with, $link );
                    }
                }
                if ( !empty( $mapped_link ) ) {
                    return $mapped_link;
                }
            } elseif ( $this->rewrite_scenario == self::REWRITING_GLOBAL ) {
                $link_without_scheme = preg_replace( "~^(https?://)~i", '', $link );
                if ( !str_starts_with( $link_without_scheme, $this->request_params->domain ) ) {
                    $rewrite_link = str_ireplace( $host, $this->request_params->domain, $link );
                }
                if ( !empty( $rewrite_link ) ) {
                    return $rewrite_link;
                }
            }
            return null;
        } catch ( Exception $e ) {
            Helper::log( $e, __METHOD__ );
            return null;
        }
    }

    /**
     * Rewrite stylesheet uri
     *
     * @param  string  $stylesheet_dir_uri
     *
     * @return string
     */
    public function rewrite_stylesheet_uri( string $stylesheet_dir_uri ) : string {
        return self::replace_host_occurrence( $stylesheet_dir_uri );
    }

    /**
     * Rewrites content
     *
     * @param $content
     *
     * @return string
     */
    public function rewrite_the_content( $content ) : string {
        return self::replace_host_occurrence( $content );
    }

    /**
     * Rewrites template uri
     *
     * @param $template_dir_uri
     *
     * @return string
     */
    public function rewrite_template_uri( $template_dir_uri ) : string {
        return self::replace_host_occurrence( $template_dir_uri );
    }

    /**
     * Replace script style source
     *
     * @param $src
     *
     * @return string
     */
    public function replace_script_style_src( $src ) : string {
        $src = self::replace_host_occurrence( $src );
        if ( Helper::check_if_bedrock() ) {
            $src = str_replace( $this->request_params->domain, $this->request_params->domain . '/wp', $src );
        }
        return $src;
    }

    /**
     * Replace actual host
     *
     * @param $input
     *
     * @return string
     */
    public function actual_host_replace( $input ) : string {
        if ( is_array( $input ) ) {
            $input = $input[0];
        }
        $host = $this->request_params->get_base_host();
        $path = $this->request_params->get_base_path();
        if ( !empty( $path ) ) {
            return str_ireplace( '://' . $host . '/' . $path, '://' . $this->request_params->domain, $input );
        }
        return str_ireplace( '://' . $host, '://' . $this->request_params->domain, $input );
    }

    /**
     * Rewrites admin url
     *
     * @param $url
     * @param $path
     *
     * @return string
     */
    public function rewrite_admin_url( $url, $path ) : string {
        if ( $path == 'admin-ajax.php' ) {
            $url = self::replace_host_occurrence( $url );
        }
        return $url;
    }

    /**
     * Rewrite attachment sources
     *
     * @param $image
     *
     * @return array|bool
     */
    public function rewrite_attachment_src( $image ) {
        if ( !empty( $image[0] ) ) {
            $image[0] = self::replace_host_occurrence( $image[0] );
        }
        return $image;
    }

    /**
     * Rewrite header image markup
     *
     * @param $html
     *
     * @return string
     */
    public function rewrite_header_image_markup( $html ) : string {
        if ( !empty( $html ) ) {
            $html = self::replace_host_occurrence( $html );
        }
        return $html;
    }

    /**
     * Rewrite image srcset
     *
     * @param $sources
     *
     * @return array
     */
    public function rewrite_image_srcset( $sources ) : array {
        if ( !empty( $sources ) ) {
            foreach ( $sources as $key => $val ) {
                $sources[$key]['url'] = self::replace_host_occurrence( $val['url'] );
            }
        }
        return $sources;
    }

    /**
     * Rewrite Divi custom fonts urls
     *
     * @param  array  $all_custom_fonts
     *
     * @return array
     */
    public function rewrite_et_builder_custom_fonts( $all_custom_fonts ) {
        if ( is_array( $all_custom_fonts ) ) {
            foreach ( $all_custom_fonts as &$font ) {
                if ( !empty( $font['font_url'] ) && is_array( $font['font_url'] ) ) {
                    foreach ( $font['font_url'] as $sub_key => $font_url ) {
                        $font['font_url'][$sub_key] = self::replace_host_occurrence( $font_url );
                    }
                }
            }
        }
        return $all_custom_fonts;
    }

    /**
     * Rewrite link, style tags uris generated by Divi
     *
     * @param $tag
     * @param $slug
     * @param $scheme
     * @param $onload
     *
     * @return string
     */
    public function rewrite_et_core_page_resource_tag(
        $tag,
        $slug,
        $scheme,
        $onload
    ) {
        return self::replace_host_occurrence( $tag );
    }

}
