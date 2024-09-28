<?php
/*
* Plugin Name: Domain Mapping System Helper
* Plugin URI: https://gauchoplugins.com/
* Description: Allow main DMS plugin to be loaded from the mu-plugins directory 
* Version: 1.0.0
* Author: Gaucho Plugins
* Author URI: https://gauchoplugins.com/
* License: GPL3
*/
if(!defined('ABSPATH')) {
	die();
}
// Check what version is active
global $wpdb;
$pro_active  = $wpdb->get_var( "SELECT `option_id` FROM " . $wpdb->prefix . "options WHERE option_name = 'active_plugins' AND option_value like '%domain-mapping-system-pro\/dms.php%'" );
$free_active = $wpdb->get_var( "SELECT `option_id` FROM " . $wpdb->prefix . "options WHERE option_name = 'active_plugins' AND option_value like '%domain-mapping-system\/dms.php%'" );

$free = ABSPATH . 'wp-content/plugins/domain-mapping-system/dms.php';
$pro = ABSPATH . 'wp-content/plugins/domain-mapping-system-pro/dms.php';
if ( file_exists( $pro ) && ! empty( $pro_active ) ) {
	$plugin_file = $pro;
} elseif ( file_exists( $free ) && ! empty( $free_active ) ) {
	$plugin_file = $free;
}
if ( !empty($plugin_file)) {
	require_once( $plugin_file );
}