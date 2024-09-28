<?php

namespace DMS\Includes\Api;

use DMS\Includes\Api\V1\Controllers\Rest_Controller;

class Server {

	/**
	 * The single instance of the class.
	 *
	 * @var null|Server
	 * @since 1.0.0
	 */
	private static ?Server $_instance = null;

	/**
	 * Engines list
	 *
	 * @var Rest_Controller[]
	 */
	protected array $controllers = [];

	/**
	 * Singleton
	 *
	 * @return Server|null
	 */
	public static function get_instance(): ?Server {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Register rest routes
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		foreach ( $this->get_controllers() as $key => $controller ) {
			$this->controllers[ $key ] = new $controller;
			$this->controllers[ $key ]->register_routes();
		}
	}

	/**
	 * Get all controllers
	 *
	 * @return string[]
	 */
	public function get_controllers(): array {
		// Filtering could be applied here
		return [
			'mappings'       => 'DMS\\Includes\\Api\\V1\\Controllers\\Mappings_Controller',
			'mapping_values' => 'DMS\\Includes\\Api\\V1\\Controllers\\Mapping_Values_Controller',
			'settings'       => 'DMS\\Includes\\Api\\V1\\Controllers\\Settings_Controller',
		];
	}
}