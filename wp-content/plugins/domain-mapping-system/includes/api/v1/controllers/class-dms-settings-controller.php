<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Repositories\Setting_Repository;
use DMS\Includes\Utils\Helper;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;

class Settings_Controller extends Rest_Controller {

	/**
	 * Settings rest base
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register settings api routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch/', array(
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch' ),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/', array(
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'create_item' ),
				'args'                => $this->get_collection_params(),
				'permission_callback' => array( $this, 'nonce_is_verified' ),

			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<key>[a-zA-Z0-9_.-]+)', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'nonce_is_verified' ),

			),
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'create_item' ),
				'args'                => $this->get_collection_params(),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
			),
		) );
	}

	public function get_collection_params() {
		return array(
			'key'   => array(
				'description'       => 'Setting key',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'value' => array(
				'description'       => 'Setting value',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get Setting
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_item( $request ) {
		try {
			$params  = $request->get_param( 'key' );
			$setting = Setting::find( $params );

			return rest_ensure_response( $setting );
		} catch ( \Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	public function create_item( $request ) {
		try {
			$params = $request->get_params();

			return Setting::create( $params );
		} catch ( \Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	public function delete_item( $request ) {
		try {
			$key = $request->get_param( 'key' );

			return Setting::delete( $key );
		} catch ( \Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	public function batch( $request ) {
		try {
			$params = $request->get_params();

			return ( new Setting_Repository )->batch( $params );
		} catch ( \Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	public function get_item_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'key'   => array(
					'description' => __( 'Unique key for the setting.', 'domain-mapping-system' ),
					'type'        => 'string',
					'context'     => array( 'edit', 'view' ),
					'required'    => true,
					'pattern'     => '^[a-zA-Z0-9_.-]+$',
				),
				'value' => array(
					'description' => __( 'Value of the setting.', 'domain-mapping-system' ),
					'type'        => 'string',
					'context'     => array( 'edit', 'view' ),
					'required'    => true,
				),
			),
		);
	}
}