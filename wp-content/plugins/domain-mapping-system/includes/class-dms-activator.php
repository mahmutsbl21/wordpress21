<?php

namespace DMS\Includes;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;

/**
 * Plugin activation.
 *
 * @since      1.0.0
 * @package    DMS
 * @subpackage DMS/includes
 */
class Activator {

	public function __construct() {
	}
	
	public function activate() {
		$this->activate_deactivate_plan();
		$this->create_tables();
		$this->set_config_settings();
		$this->create_dms_mu_helper();
	}

	/**
	 * Create custom tables
	 *
	 * @return bool
	 */
	public function create_tables(): bool {
		global $wpdb;

		$mappings_name       = $wpdb->prefix . Mapping::TABLE;
		$mapping_values_name = $wpdb->prefix . Mapping_Value::TABLE;

		$charset_collate = $wpdb->get_charset_collate();
		$dms_mappings    = "CREATE TABLE IF NOT EXISTS $mappings_name (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `host` varchar(256) NOT NULL,
        `path` varchar(512) DEFAULT NULL,
        `attachment_id` bigint(20) DEFAULT NULL,
        `custom_html` text DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

		$dms_mapping_values = "CREATE TABLE IF NOT EXISTS $mapping_values_name (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `mapping_id` bigint(20) NOT NULL,
        `object_id` bigint(20) NOT NULL,
        `object_type` varchar(16) NOT NULL,
        `primary` bigint(20) NOT NULL,
        PRIMARY KEY  (id),
        KEY `mapping_id` (`mapping_id`),
        KEY `object_id` (`object_id`)
    ) $charset_collate;";

		$result1 = $wpdb->query( $dms_mappings );
		$result2 = $wpdb->query( $dms_mapping_values );

		if ( $result1 === false || $result2 === false ) {
			return false;
		}

		return true;
	}

	/**
	 * Set main configs
	 *
	 * @return void
	 */
	public function set_config_settings(): void {
		if ( empty( Setting::find( 'dms_use_page' )->get_value() ) ) {
			Setting::create( [ 'key' => 'dms_use_page', 'value' => 'on' ] );
			Setting::create( [ 'key' => 'dms_use_post', 'value' => 'on' ] );
			Setting::create( [ 'key' => 'dms_use_categories', 'value' => 'on' ] );
		}

		if ( empty( Setting::find( 'dms_mappings_per_page' )->get_value() ) ) {
			Setting::create( [ 'key' => 'dms_mappings_per_page', 'value' => '10' ] );
		}
		if ( empty( Setting::find( 'dms_values_per_mapping' )->get_value() ) ) {
			Setting::create( [ 'key' => 'dms_values_per_mapping', 'value' => '5' ] );
		}
	}

	/**
	 * Create a little helper plugin in mu-plugins
	 *
	 * @return void
	 */
	public function create_dms_mu_helper(): void {
		// Create mkdir
		if ( ! file_exists( WPMU_PLUGIN_DIR ) ) {
			@mkdir( WPMU_PLUGIN_DIR );
		}
		if ( file_exists( WPMU_PLUGIN_DIR ) ) {
			$file = WPMU_PLUGIN_DIR . "/dms-helper.php";
			if ( ! file_exists( $file ) ) {
				WP_Filesystem();
				global $wp_filesystem;
				$wp_filesystem->put_contents( $file, '<?php
/*
* Plugin Name: Domain Mapping System Helper
* Plugin URI: https://gauchoplugins.com/
* Description: Allow main DMS plugin to be loaded from the mu-plugins directory 
* Version: 1.0.0
* Author: Gaucho Plugins
* Author URI: https://gauchoplugins.com/
* License: GPL3
*/
if(!defined(\'ABSPATH\')) {
	die();
}
// Check what version is active
global $wpdb;
$pro_active  = $wpdb->get_var( "SELECT `option_id` FROM " . $wpdb->prefix . "options WHERE option_name = \'active_plugins\' AND option_value like \'%domain-mapping-system-pro\/dms.php%\'" );
$free_active = $wpdb->get_var( "SELECT `option_id` FROM " . $wpdb->prefix . "options WHERE option_name = \'active_plugins\' AND option_value like \'%domain-mapping-system\/dms.php%\'" );

$free = ABSPATH . \'wp-content/plugins/domain-mapping-system/dms.php\';
$pro = ABSPATH . \'wp-content/plugins/domain-mapping-system-pro/dms.php\';
if ( file_exists( $pro ) && ! empty( $pro_active ) ) {
	$plugin_file = $pro;
} elseif ( file_exists( $free ) && ! empty( $free_active ) ) {
	$plugin_file = $free;
}
if ( !empty($plugin_file)) {
	require_once( $plugin_file );
}' );
			}
		}
	}

	/**
	 * Deactivate other active dms plugins during activation of this
	 *
	 * @return void
	 */
	public function activate_deactivate_plan():void {
		// Here we need to create unique
		// Deactivate other active version
		$plugin_base_name              = DMS::get_instance()->plugin_base_name;
		$plugin_dir_path               = DMS::get_instance()->plugin_dir_path;
		$free_plugin_base_name         = strpos( $plugin_base_name,
			'-pro' ) === false ? $plugin_base_name : str_replace( '-pro', '', $plugin_base_name );
		$premium_plugin_base_name      = strpos( $plugin_base_name,
			'-pro' ) !== false ? $plugin_base_name : str_replace( basename( $plugin_dir_path ),
			basename( $plugin_dir_path ) . '-pro', $plugin_base_name );
		$is_premium_version_activation = current_filter() !== 'activate_' . $free_plugin_base_name;
		// This logic is relevant only to plugins since both the free and premium versions of a plugin can be active at the same time.
		// 1. If running in the activation of the FREE module, get the basename of the PREMIUM.
		// 2. If running in the activation of the PREMIUM module, get the basename of the FREE.
		$other_version_basename = ( $is_premium_version_activation ? $free_plugin_base_name : $premium_plugin_base_name );
		/**
		 * If the other module version is active, deactivate it.
		 *
		 * is_plugin_active() checks if the plugin is active on the site or the network level and
		 * deactivate_plugins() deactivates the plugin whether it's activated on the site or network level.
		 *
		 */
		if ( is_plugin_active( $other_version_basename ) ) {
			deactivate_plugins( $other_version_basename );
		}
	}
}
