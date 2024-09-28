<?php

namespace DMS\Includes\Admin;

use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Freemius;
use DMS\Includes\Utils\Helper;
/**
 * Admin class which organizes all the admin related functionality
 */
class Admin {
    /**
     * Plugin name
     *
     * @var string
     */
    public string $plugin_name;

    /**
     * Plugin path
     *
     * @var string
     */
    public string $plugin_path;

    /**
     * Plugin url
     *
     * @var string
     */
    public string $plugin_url;

    /**
     * Plugin version
     *
     * @var string
     */
    public string $version;

    /**
     * Freemius instance
     */
    public ?\Freemius $fs;

    /**
     * Constructor
     *
     * @param string $plugin_name Plugin name
     * @param string $plugin_path Plugin path
     * @param string $plugin_url Plugin Url
     * @param string $version Plugin version
     */
    public function __construct(
        string $plugin_name,
        string $plugin_path,
        string $plugin_url,
        string $version
    ) {
        $this->plugin_name = $plugin_name;
        $this->plugin_path = $plugin_path;
        $this->plugin_url = $plugin_url;
        $this->version = $version;
        $this->fs = Freemius::getInstance()->fs;
        $this->define_hooks();
    }

    /**
     * Define admin hooks
     *
     * @return void
     */
    public function define_hooks() : void {
        add_action( 'admin_menu', array($this, 'admin_menu') );
        add_action( 'admin_post_save_dms_screen_options', array($this, 'save_screen_options') );
    }

    /**
     * Add new menu and pages for DMS plugin
     *
     * @return void
     */
    public function admin_menu() : void {
        $page = add_menu_page(
            __( 'Domain Mapping', $this->plugin_name, 'domain-mapping-system' ),
            __( 'Domain Mapping', $this->plugin_name, 'domain-mapping-system' ),
            'manage_options',
            'domain-mapping-system',
            array($this, 'include_options'),
            'dashicons-admin-site-alt3'
        );
        add_action( 'admin_print_styles-' . $page, array($this, 'register_styles') );
        add_action( 'admin_print_scripts-' . $page, array($this, 'register_scripts') );
    }

    /**
     * Save screen options
     *
     * @return void
     */
    public function save_screen_options() : void {
        $check_nonce = check_admin_referer( 'save_dms_screen_options', 'save_dms_screen_options_nonce' );
        $referer = wp_get_referer();
        $redirect_to_first_page = false;
        if ( $check_nonce ) {
            if ( !empty( $_POST['dms_mappings_per_page'] ) ) {
                $per_page = sanitize_text_field( $_POST['dms_mappings_per_page'] );
                $saved_value = Setting::find( 'dms_mappings_per_page' )->get_value();
                if ( $per_page != $saved_value ) {
                    $redirect_to_first_page = true;
                }
            }
            $values_per_mapping = ( !empty( $_POST['dms_values_per_mapping'] ) ? sanitize_text_field( $_POST['dms_values_per_mapping'] ) : 5 );
            $mappings_per_page = ( !empty( $_POST['dms_mappings_per_page'] ) ? sanitize_text_field( $_POST['dms_mappings_per_page'] ) : 10 );
            Setting::update( [
                'key'   => 'dms_mappings_per_page',
                'value' => $mappings_per_page,
            ] );
            Setting::update( [
                'key'   => 'dms_values_per_mapping',
                'value' => $values_per_mapping,
            ] );
        }
        $url = site_url() . $referer;
        if ( $redirect_to_first_page ) {
            $url = remove_query_arg( 'paged', $url );
        }
        wp_redirect( $url );
    }

    /**
     * Include plugin options page view
     *
     * @return void
     */
    public function include_options() : void {
        $dms_fs = $this->fs;
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have the permissions to access this page.', 'domain-mapping-system' ) );
        }
        require_once $this->plugin_path . 'templates/option-page.php';
    }

    /**
     * Register plugin styles
     *
     * @return void
     */
    public function register_styles() : void {
        wp_register_style(
            'dms-min-css',
            $this->plugin_url . 'assets/css/dms.min.css',
            array(),
            $this->version
        );
        wp_enqueue_style( 'dms-min-css' );
    }

    /**
     * Get custom post types of the site
     *
     * @return array
     */
    public function get_content_types() : array {
        $types = get_post_types( array(
            'public'   => true,
            '_builtin' => false,
        ), 'objects' );
        $clean_types = array();
        foreach ( $types as $singular => $item ) {
            $clean_type = array(
                'name'        => $singular,
                'label'       => $item->labels->name,
                'has_archive' => $item->has_archive,
            );
            $clean_types[] = $clean_type;
        }
        return apply_filters( 'dms_available_content_types', $clean_types );
    }

    /**
     * Get the corresponding rest structure according to permalink structure
     *
     * @return string
     */
    public function get_rest_url() {
        if ( get_option( 'permalink_structure' ) ) {
            return rest_url( '/dms/v1/' );
        } else {
            return add_query_arg( 'rest_route', '/dms/v1/', site_url( '/' ) );
        }
    }

    /**
     * Register & enqueue JS
     *
     * @return void
     */
    public function register_scripts() : void {
        /**
         * Collect data to localize
         * translations for JS
         * premium flag
         */
        $translations = (include_once $this->plugin_path . 'assets/js/localizations/js-translations.php');
        /**
         * Collect data to localize
         * translations for JS
         * premium flag
         */
        $dms_fs_data = array(
            'nonce'              => wp_create_nonce( 'dms_nonce' ),
            'rest_nonce'         => wp_create_nonce( 'wp_rest' ),
            'scheme'             => Helper::get_scheme(),
            'ajax_url'           => admin_url( 'admin-ajax.php' ),
            'site_url'           => site_url(),
            'rest_url'           => $this->get_rest_url(),
            'translations'       => $translations,
            'is_premium'         => (int) $this->fs->can_use_premium_code__premium_only(),
            'upgrade_url'        => $this->fs->get_upgrade_url(),
            'values_per_mapping' => Setting::find( 'dms_values_per_mapping' )->get_value() ?? 5,
            'mappings_per_page'  => Setting::find( 'dms_mappings_per_page' )->get_value() ?? 10,
            'paged'              => ( !empty( $_GET['paged'] ) ? $_GET['paged'] : 1 ),
            'plugin_url'         => $this->plugin_url,
        );
        // Register main js dependencies
        // Woo is using same js, that is why deregister first to avoid conflicts
        wp_deregister_script( 'select2' );
        // Deregister another script used by woo, which causes issues
        wp_deregister_script( 'wc-enhanced-select' );
        wp_register_script(
            'select2',
            $this->plugin_url . 'assets/js/select2.full.min.js',
            array('jquery'),
            $this->version
        );
        wp_register_script( 'dms-stack', $this->plugin_url . 'assets/js/dms-stack.js', $this->version );
        wp_enqueue_script( 'dms-stack' );
        wp_register_script(
            'dms-js',
            $this->plugin_url . 'assets/js/dms.js',
            array('jquery', 'dms-stack'),
            $this->version
        );
        wp_enqueue_script( 'select2' );
        wp_enqueue_script( 'dms-js' );
        // Include js data into dms-js
        wp_localize_script( 'dms-js', 'dms_fs', $dms_fs_data );
        // Dequeue Wc Vendors Pro js file to avoid from conflicts
        wp_dequeue_script( 'wcv-admin-js' );
    }

}
