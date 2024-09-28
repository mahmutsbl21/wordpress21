<?php

namespace DMS\Includes;

use DMS\Includes\Admin\Admin;
use DMS\Includes\Ajax\Ajax;
use DMS\Includes\Api\Server;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Integrations\SEO\Yoast\Seo_Yoast;
use DMS\Includes\Integrations\WCFM\WCFM;
use DMS\Includes\Migrations\Migration;
/**
 * The file that defines the core plugin class
 *
 * @link
 *
 * @package    DMS
 * @subpackage DMS/includes
 */
/**
 * Main DMS class.
 *
 */
final class DMS {
    /**
     * The single instance of the class.
     */
    private static ?DMS $_instance = null;

    /**
     * The unique identifier of this plugin.
     *
     * @var      string $plugin_name
     */
    public string $plugin_name;

    /**
     * The current active plugin version folder/base_file
     *
     * @var      string $plugin_base_name
     */
    public string $plugin_base_name;

    /**
     * The unique identifier of this plugin's directory path.
     *
     * @var      string $plugin_dir_path
     */
    public string $plugin_dir_path;

    /**
     * The unique identifier of this plugin's directory url.
     *
     * @var      string $plugin_dir_url
     */
    public string $plugin_dir_url;

    /**
     * The current version of the plugin.
     *
     * @var      string $version
     */
    public string $version;

    /**
     * Admin class instance
     *
     * @var Admin
     */
    public Admin $admin;

    /**
     * Get debug value
     *
     * @var string|null
     */
    public static ?string $debug;

    /**
     * Frontend instance
     *
     * @var Frontend
     */
    public Frontend $frontend;

    /**
     * Array that keeps all the instances of the active integrations
     *
     * @var array $integrations
     */
    protected array $integrations;

    /**
     * Constructor
     *
     */
    private function __construct() {
        $this->set_params();
        $this->load_dependencies();
        if ( method_exists( $this, 'load_dependencies__premium_only' ) ) {
            $this->load_dependencies__premium_only();
        }
        $this->set_locale();
        $this->define_admin_classes();
        $this->init_ajax();
        if ( method_exists( $this, 'run_integrations__premium_only' ) ) {
            $this->run_integrations__premium_only();
        }
        $this->define_frontend();
        $this->run_migrations();
        $this->api_init();
    }

    /**
     * Set plugin related parameters (path, url, name, version, debug mode)
     *
     * @return void
     */
    public function set_params() : void {
        if ( defined( 'DMS_VERSION' ) ) {
            $this->version = DMS_VERSION;
        } else {
            $this->version = '2.0.6';
        }
        self::$debug = get_option( 'DMS_debug', true );
        $this->plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );
        $this->plugin_dir_url = plugin_dir_url( dirname( __FILE__ ) );
        $plugin_base_folder_name = basename( $this->plugin_dir_path );
        // No matter free or pro. Plugin name should be domain-mapping-system
        $this->plugin_name = rtrim( $plugin_base_folder_name, '-pro' );
        // Based on free or pro
        $this->plugin_base_name = $plugin_base_folder_name . '/dms.php';
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @return void
     */
    private function load_dependencies() : void {
        /**
         * Utils
         */
        require_once $this->plugin_dir_path . 'includes/utils/class-dms-helper.php';
        /**
         * Exceptions
         */
        require_once $this->plugin_dir_path . 'includes/exceptions/class-dms-exception.php';
        /**
         * Rest Api
         */
        require_once $this->plugin_dir_path . 'includes/api/class-dms-server.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-rest-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-mappings-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-mapping-values-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-settings-controller.php';
        /**
         * Repositories
         */
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-mapping-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-mapping-value-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-setting-repository.php';
        /**
         * Data objects
         */
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-data-object.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-mapping.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-mapping-value.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-setting.php';
        /**
         * Admin Classes
         */
        require_once $this->plugin_dir_path . 'includes/admin/class-dms-admin.php';
        /**
         * Freemius
         */
        require_once $this->plugin_dir_path . 'includes/class-dms-fs.php';
        /**
         * Ajax
         */
        require_once $this->plugin_dir_path . 'includes/ajax/class-dms-ajax.php';
        /**
         * Frontend
         */
        require_once $this->plugin_dir_path . 'includes/frontend/class-dms-frontend.php';
        require_once $this->plugin_dir_path . 'includes/frontend/handlers/class-dms-uri-handler.php';
        require_once $this->plugin_dir_path . 'includes/frontend/handlers/class-dms-mapping-handler.php';
        require_once $this->plugin_dir_path . 'includes/frontend/handlers/class-dms-wp-queried-object-handler.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-mapping-scenario.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-mapping-scenario-interface.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-simple-object-mapping.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-mapper-interface.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-post-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-term-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-product-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-shop-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-divi-shop-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-tribe-events-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-wp-manga-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-wcfm-store-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/class-dms-mapper-factory.php';
        require_once $this->plugin_dir_path . 'includes/frontend/services/class-dms-request-params.php';
        /**
         * Migrations
         */
        require_once $this->plugin_dir_path . 'includes/migrations/class-dms-migration.php';
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @return void
     */
    private function set_locale() : void {
        add_action( 'init', function () {
            load_plugin_textdomain( $this->plugin_name, false, basename( $this->plugin_dir_path ) . '/languages' );
        } );
    }

    /**
     * Define admin classes
     *
     * @return void
     */
    public function define_admin_classes() : void {
        $this->admin = new Admin(
            $this->plugin_name,
            $this->plugin_dir_path,
            $this->plugin_dir_url,
            $this->version
        );
    }

    /**
     * Define ajax
     *
     * @return void
     */
    public function init_ajax() : void {
        Ajax::init();
    }

    /**
     * Define Frontend
     *
     * @return void
     */
    public function define_frontend() : void {
        $this->frontend = Frontend::get_instance(
            $this->plugin_dir_url,
            $this->plugin_name,
            $this->version,
            $this->plugin_dir_path
        );
    }

    /**
     * Main DMS Instance.
     *
     * @return DMS - Main instance.
     * @static
     */
    public static function get_instance() : DMS {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Run migrations
     *
     * @return void
     */
    public function run_migrations() : void {
        new Migration($this->version, $this->plugin_dir_path);
    }

    /**
     * Define controllers
     *
     * @return void
     */
    private function api_init() : void {
        Server::get_instance()->init();
    }

    /**
     * Get debug value
     *
     * @return string|null
     */
    public static function get_debug() : ?string {
        return self::$debug;
    }

    /**
     * Get the plugin name
     *
     * @return    string
     */
    public function get_plugin_name() : string {
        return $this->plugin_name;
    }

    /**
     * Get the plugin version
     *
     * @return    string
     */
    public function get_version() : string {
        return $this->version;
    }

    /**
     * Get the plugin path
     *
     * @return    string
     */
    public function get_plugin_dir_path() : string {
        return $this->plugin_dir_path;
    }

    /**
     * Get plugin dir url
     * 
     * @return string
     */
    public function get_plugin_dir_url() : string {
        return $this->plugin_dir_url;
    }

}
