<?php

/**
 * Plugin Name: Domain Mapping System
 * Plugin URI: https://gauchoplugins.com/
 * Description: Domain Mapping System is the most powerful way to manage alias domains and map them to any published resource - creating Microsites with ease!
 * Version: 2.0.6
 * Author: Gaucho Plugins
 * Author URI: https://gauchoplugins.com/
 * License: GPL3
 *
 *
 * Copyright 2020 Brand on Fire (email: support@gauchoplugins.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if(! function_exists( 'DMS' )) {
	/**
	 * Plugin version.
	 * Used SemVer - https://semver.org
	 */
	define( 'DMS_VERSION', '2.0.6' );

	/**
	 * Load activate/Deactivate files
	 */
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dms-activator.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dms-deactivator.php';

	/**
	 * The core plugin class
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-dms.php';
	/**
	 * Returns the main instance of DMS.
	 *
	 * @return DMS\Includes\DMS
	 * @since  1.0.0
	 */
	function DMS() {
		return DMS\Includes\DMS::get_instance();
	}

	/**
	 * Begins execution of the plugin.
	 */
	DMS();
}
/**
 * Activate/Deactivate hooks
 */
register_activation_hook( __FILE__, array( (new \DMS\Includes\Activator()), 'activate' ) );
register_uninstall_hook( __FILE__, array( '\DMS\Includes\Deactivator', 'uninstall' ) );
