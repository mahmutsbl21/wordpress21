<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Repositories\Mapping_Repository;
use DMS\Includes\Utils\Helper;
use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Mappings_Controller extends Rest_Controller {

	/**
	 * Mappings rest base
	 *
	 * @var string
	 */
	protected $rest_base = 'mappings';


	/**
	 * Get namespace
	 *
	 * @return mixed|string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Register rest routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch/', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch' ),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'create_item' ),
				'args'                => $this->get_collection_params(),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'nonce_is_verified' ),

			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
				'args'                => $this->get_collection_params(),
			),
		) );
	}

	/**
	 * Get collection params
	 *
	 * @return array[]
	 */
	public function get_collection_params(): array {
		return array(
			'host'          => array(
				'description'       => 'Host value for the mapping item',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_host' ),
			),
			'path'          => array(
				'description'       => 'Path value for the mapping item',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'attachment_id' => array(
				'description'       => 'Attachment ID for the mapping item',
				'type'              => 'integer',
				'required'          => false,
				'sanitize_callback' => 'absint',
			),
			'custom_html'   => array(
				'description'       => 'Custom HTML content for the mapping item',
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => array( $this, 'validate_custom_html' ),
			),
		);
	}

	/**
	 * Validate custom html
	 *
	 * @param $value
	 *
	 * @return true|WP_Error
	 */
	public function validate_custom_html( $value ) {
		if ( empty( $value ) ) {
			return true;
		}
		$allowed_tags = array(
			'title',
			'base',
			'link',
			'meta',
			'style',
			'script',
			'noscript'
		);

		preg_match_all( '/<([a-zA-Z0-9]+)[^>]*>/', $value, $matches );
		$tags = array_unique( $matches[1] );

		foreach ( $tags as $tag ) {
			if ( ! in_array( strtolower( $tag ), $allowed_tags, true ) ) {
				return new WP_Error( 'rest_invalid_param', sprintf( 'The custom_html parameter contains disallowed tag: %s.', $tag ), array( 'status' => 400 ) );
			}
		}

		if ( empty( $tags ) ) {
			return new WP_Error( 'rest_invalid_param', 'The custom_html parameter must contain HTML tags.', array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Callback for batch request
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function batch( $request ) {
		try {
			$params = $request->get_params();

			$mappings = ( new Mapping_Repository() )->batch( $params );

			return rest_ensure_response( $mappings );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Get Mappings
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_items( $request ) {
		try {
			$paged    = (int) $request->get_param( 'paged' );
			$limit    = (int) $request->get_param( 'limit' );
			$include = $request->get_param('include');
			$mappings = Mapping::where( [], $paged, $limit );
			if ($include == 'mapping_values') {
				$mappings = $this->prepare_values( $mappings );
			} else {
				$mappings = $this->prepare_values_links( $mappings );
			}
			$mappings = $this->prepare_total_count( $mappings );
			$mappings = $this->prepare_attachment_links( $mappings );

			return rest_ensure_response( $mappings );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Prepare values
	 *
	 * @param $mappings
	 *
	 * @return array|mixed
	 */
	public function prepare_values($mappings){
		if (Helper::is_dms_error($mappings)){
			throw $mappings;
		}
		$single                  = false;

		if ($mappings instanceof Mapping){
			$mappings = array($mappings);
			$single = true;
		}
		$values_per_page = Setting::find('dms_values_per_mapping')->get_value() ?? 5;
		$prepared_mappings = array();
		foreach ($mappings as $mapping){
			$values = Mapping_Value::where(['mapping_id' => $mapping->id], 0, $values_per_page);
			$values = Mapping_Values_Controller::prepare_item_object($values);
			$values = Mapping_Values_Controller::prepare_total_count($values, $mapping->id);
			$prepared_mappings[] = array(
				'mapping' => $mapping,
				'_values'  => $values,
			);
		}

		return $single ? $prepared_mappings[0] : $prepared_mappings;
	}

	/**
	 * Prepare Mapping value links
	 *
	 * @param $mappings
	 *
	 * @return array
	 * @throws DMS_Exception
	 */
	public function prepare_values_links( $mappings ): array {
		if ( Helper::is_dms_error( $mappings ) ) {
			throw $mappings;
		}
		$single                  = false;
		$mapping_values_endpoint = Mapping_Values_Controller::REST_ENDPOINT;
		if ( $mappings instanceof Mapping ) {
			$mappings = array( $mappings );
			$single   = true;
		}
		$preparedMappings = array();
		foreach ( $mappings as $mapping ) {
			$links = array(
				'values' => array(
					'href' => rest_url( sprintf( '/%s/%s/%d/%s', $this->namespace, $this->rest_base, $mapping->id, $mapping_values_endpoint ) ),
				),
			);

			$preparedMappings[] = array(
				'mapping' => $mapping,
				'_links'  => $links,
			);
		}

		return $single ? $preparedMappings[0] : $preparedMappings;
	}

	/**
	 * Prepare count of mappings
	 *
	 * @param array|DMS_Exception $mappings
	 *
	 * @return array
	 * @throws DMS_Exception
	 */
	private function prepare_total_count( $mappings ): array {
		if ( Helper::is_dms_error( ( $mappings ) ) ) {
			throw $mappings;
		}

		return [
			'items'  => $mappings,
			'_total' => Mapping::count()
		];
	}

	/**
	 * Add attachment link to mapping
	 *
	 * @param $mappings
	 *
	 * @return array
	 */
	public function prepare_attachment_links( $mappings ): array {
		if ( Helper::is_dms_error( ( $mappings ) ) ) {
			throw $mappings;
		}

		foreach ( $mappings['items'] as $key => $item ) {
			if ( $item['mapping']->attachment_id ) {
				$attachment_url                                        = wp_get_attachment_url( $item['mapping']->attachment_id );
				$mappings['items'][ $key ]['_links']['attachment_url'] = $attachment_url;
			}
		}

		return $mappings;
	}

	/**
	 * Create mapping
	 *
	 * @param $request
	 *
	 * @return DMS_Exception|WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function create_item( $request ) {
		try {
			if ( ! empty( $request['id'] ) ) {
				throw new DMS_Exception( 'rest_mapping_exists', 'Cannot create existing mapping.', array( 'status' => 400 ) );
			}
			if ( empty( $request['host'] ) ) {
				throw new DMS_Exception( 'rest_empty_host', 'Empty host.', array( 'status' => 400 ) );
			}
			$mapping = ( new Mapping_Repository() )->create( $request->get_json_params() );
			$mapping = $this->prepare_values_links( $mapping );

			return rest_ensure_response( $mapping );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Get mapping
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_item( $request ) {
		try {
			$id      = $request->get_param( 'id' );
			$mapping = Mapping::find( $id );
			$mapping = $this->prepare_values_links( $mapping );

			return rest_ensure_response( $mapping );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Delete mapping
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function delete_item( $request ) {
		try {
			$id      = $request->get_param( 'id' );
			$mapping = Mapping::delete( $id );

			return rest_ensure_response( $mapping );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Update mapping
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function update_item( $request ) {
		try {
			$id      = $request->get_param( 'id' );
			if (isset($request['host']) && $request['host'] == ''){
				throw new DMS_Exception( 'rest_empty_host', 'Empty host.', array( 'status' => 400 ) );
			}
			$mapping = Mapping::update( $id, $request->get_json_params() );
			$mapping = $this->prepare_values_links( $mapping );

			return rest_ensure_response( $mapping );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Validate host
	 *
	 * @param string $value
	 * @param WP_REST_Request $request
	 * @param string $key
	 *
	 * @return string|WP_Error
	 */
	public function validate_host( string $value, WP_REST_Request $request, string $key ) {
		if ( isset( $request['host'] ) ) {
			if ( empty( $value ) ) {
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is required.', 'domain-mapping-system' ), $key ) );
			}
		}

		if ( ! Helper::is_valid_host( $value ) ) {
			return new WP_Error( 'rest_mapping_invalid_host', 'Invalid host', array( 'status' => 400 ) );
		}

		return $value;
	}

	/**
	 * Get mappings schema
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'description' => __( 'Unique identifier for the mapping.', 'domain-mapping-system' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'host'          => array(
					'description' => __( 'Host name for the mapping.', 'domain-mapping-system' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'required'    => true,
				),
				'path'          => array(
					'description' => __( 'Path for the mapping.', 'domain-mapping-system' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'attachment_id' => array(
					'description' => __( 'Attachment id of the mapping.', 'domain-mapping-system' ),
					'type'        => array( 'integer', 'null' ),
					'context'     => array( 'edit' ),
				),
				'custom_html'   => array(
					'description' => __( 'Custom html of the mapping.', 'domain-mapping-system' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'edit' ),
				),
			),
		);
	}
}