<?php

namespace DMS\Includes;

use DMS\Includes\Frontend\Services\Request_Params;
use DMS\Includes\Utils\Helper;
use Exception;
class Freemius {
    /**
     * The single instance of the class.
     *
     * @var Freemius
     */
    public static Freemius $instance;

    /**
     * Freemius
     *
     * @var \Freemius|null
     */
    public ?\Freemius $fs;

    /**
     * Plugin path
     *
     * @var string
     */
    public string $plugin_path;

    /**
     * Request params
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * Constructor
     */
    public function __construct() {
        $this->request_params = new Request_Params();
        $this->fs = $this->fs_init();
        if ( !empty( $fs ) ) {
            $this->fs->add_filter( 'plugin_icon', [$this, 'dms_fs_custom_icon'] );
        }
    }

    /**
     * Define and load freemius sdk from not frontend side
     *
     * @return \Freemius|null
     */
    private function fs_init() : ?\Freemius {
        try {
            // Include Freemius SDK.
            if ( is_admin() || !empty( $this->request_params->path ) && str_contains( $this->request_params->path, 'wp-json' ) ) {
                require_once plugin_dir_path( __DIR__ ) . '/vendor/freemius/start.php';
                return fs_dynamic_init( [
                    'id'             => '6959',
                    'slug'           => 'domain-mapping-system',
                    'premium_slug'   => 'domain-mapping-system-pro',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_e348807215df985c848c86b883ee3',
                    'is_premium'     => false,
                    'premium_suffix' => 'PRO',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => [
                        'slug'    => 'domain-mapping-system',
                        'support' => false,
                        'parent'  => [
                            'slug' => 'domain-mapping-system',
                        ],
                    ],
                    'is_live'        => true,
                ] );
            }
            return null;
        } catch ( Exception $e ) {
            Helper::log( $e, __METHOD__ );
            return null;
        }
    }

    /**
     * Main Freemius instance
     *
     * @return Freemius
     */
    public static function getInstance() : Freemius {
        if ( !isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Change the plugin icon
     *
     * @return string
     */
    function dms_fs_custom_icon() {
        return dirname( __FILE__ ) . '/assets/img/dms-logo.jpg';
    }

    /**
     * Return freemius instance
     *
     * @return \Freemius|null
     */
    public function get_freemius() : ?\Freemius {
        return $this->fs;
    }

}
