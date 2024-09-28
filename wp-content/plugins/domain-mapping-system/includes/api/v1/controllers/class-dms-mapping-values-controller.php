<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Repositories\Mapping_Value_Repository;
use DMS\Includes\Utils\Helper;
use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;
use WP_REST_Server;

class Mapping_Values_Controller extends Rest_Controller {

	/**
	 * Rest endpoint
	 */
	const REST_ENDPOINT = 'values';

	/**
	 * Mapping rest base
	 *
	 * @var string
	 */
	protected string $mapping_rest_base = 'mappings/(?P<mapping_id>[\d]+)/values';

	/**
	 * Mapping generic rest base
	 *
	 * @var string
	 */
	protected string $generic_rest_base = 'mapping_values';

	/**
	 * Register rest routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route( $this->namespace, '/' . $this->mapping_rest_base . '/batch/', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch' ),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->mapping_rest_base . '/', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_items' ),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'create_item' ),
				'args'                => $this->get_collection_params(),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->generic_rest_base . '/(?P<id>[\d]+)', array(
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
				'args'                => $this->get_collection_params(),
				'permission_callback' => array( $this, 'nonce_is_verified' ),
			),
		) );
	}

	/**
	 * Collection params
	 *
	 * @return array[]
	 */
	public function get_collection_params(): array {
		return array(
			'object_id'   => array(
				'description'       => 'ID of the mapped object (e.g., term or post).',
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_object_id' ),
			),
			'mapping_id'  => array(
				'description'       => 'ID of the mapped object (e.g., term or post).',
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_mapping_id' ),
			),
			'object_type' => array(
				'description'       => 'Type of the mapped object (e.g., term or post).',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_object_type' ),
			),
			'primary'     => array(
				'description'       => 'The type of mapping (primary or secondary)',
				'type'              => array( 'integer', 'null' ),
				'required'          => false,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Batch callback
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function batch( $request ) {
		try {
			$id       = $request->get_param( 'mapping_id' );
			$data     = $request->get_param( 'data' );
			$mappings = ( new Mapping_Value_Repository() )->batch( $id, $data );

			return rest_ensure_response( $mappings );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Get Mapping Values
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_items( $request ) {
		try {
			$limit          = (int) $request->get_param( 'values_per_row' );
			$start          = (int) $request->get_param( 'start' );
			$mapping_id     = $request->get_param( 'mapping_id' );
			$mapping_values = Mapping_Value::where( [ 'mapping_id' => $mapping_id ], $start, $limit );
			$mapping_values = $this->prepare_item_object( $mapping_values );
			$mapping_values = $this->prepare_total_count( $mapping_values, $mapping_id );

			return rest_ensure_response( $mapping_values );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Prepare item object
	 *
	 * @param $mapping_values
	 *
	 * @return array
	 */
	public static function prepare_item_object( $mapping_values ): array {
		$prepared_values = [];
		if ( $mapping_values instanceof Mapping_Value ) {
			$mapping_values = array( $mapping_values );
		}
		foreach ( $mapping_values as $value ) {
			$name = '';
			if ( $value->object_type == 'post' ) {
				$name = get_post( $value->object_id )->post_title;
			} elseif ( $value->object_type == 'term' ) {
				$name = get_term( $value->object_id )->name;
			}
			$name              = apply_filters( 'dms_mapping_value_name', $name, $value );
			$prepared_values[] = array(
				'value'   => $value,
				'_object' => [ 'object_name' => $name ],
			);
		}

		return $prepared_values;
	}

	/**
	 * Prepare total count of mapping values
	 *
	 * @param array $mapping_values
	 * @param int $mapping_id
	 *
	 * @return array
	 */
	public static function prepare_total_count( array $mapping_values, int $mapping_id ): array {
		if ( Helper::is_dms_error( $mapping_values ) ) {
			return $mapping_values;
		}

		return [
			'items'  => $mapping_values,
			'_total' => Mapping_Value::count( [ 'mapping_id' => $mapping_id ] )
		];
	}

	/**
	 * Delete multiple mapping values by mapping id
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function delete_items( $request ) {
		try {
			$id    = $request->get_param( 'mapping_id' );
			$count = $request->get_param( 'count' );
			$res   = ( new Mapping_Value_Repository() )->delete_items( $id, $count );

			return rest_ensure_response( $res );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Create new mapping value
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function create_item( $request ) {
		try {
			$mapping_id    = $request->get_param( 'mapping_id' );
			$mapping_value = ( new Mapping_Value_Repository() )->create( $mapping_id, $request->get_json_params() );

			return rest_ensure_response( $mapping_value );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Get mapping value
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_item( $request ) {
		try {
			$id            = $request->get_param( 'id' );
			$mapping_value = Mapping_Value::find( $id );

			return rest_ensure_response( $mapping_value );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Delete mapping value
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function delete_item( $request ) {
		try {
			$id            = $request->get_param( 'id' );
			$mapping_value = Mapping_Value::delete( $id );

			return rest_ensure_response( $mapping_value );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Update mapping value
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function update_item( $request ) {
		try {
			$id            = $request->get_param( 'id' );
			$mapping_value = Mapping_Value::update( $id, $request->get_json_params() );

			return rest_ensure_response( $mapping_value );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Validate mapping id
	 *
	 * @param int $value
	 *
	 * @return WP_Error|int
	 * @throws DMS_Exception
	 */
	public function validate_mapping_id( int $value ) {
		if ( ! Mapping::find( $value ) instanceof Mapping ) {
			return new WP_Error( 'rest_object_id_error', 'Mapping does not exist', [ 'status' => 400 ] );
		}

		return $value;
	}

	/**
	 * Validate object id
	 *
	 * @param int $value
	 *
	 * @return float|int|string|WP_Error
	 */
	public function validate_object_id( int $value ) {
		if ( ! is_numeric( $value ) ) {
			return new WP_Error( 'rest_invalid_object_id', 'Invalid object ID', [ 'status' => 400 ] );
		}
		$is_validated = term_exists( $value, get_taxonomies() ) || ! empty( get_post( $value ) );
		if ( ! apply_filters( 'dms_validate_object_id', $is_validated, $value ) ) {
			return new WP_Error( 'rest_object_not_found', 'Object not found', [ 'status' => 400 ] );
		}

		return $value;
	}

	/**
	 * Validate object type
	 *
	 * @param string $value
	 *
	 * @return WP_Error|string
	 */
	public function validate_object_type( string $value ) {
		$allowed_values = [ 'term', 'post' ];
		if ( ! in_array( $value, apply_filters( 'dms_allowed_object_types', $allowed_values ) ) ) {
			return new WP_Error( 'rest_invalid_object_type', 'Invalid object type', [ 'status' => 400 ] );
		}

		return $value;
	}

	/**
	 * Item schema
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the mapping value.', 'domain-mapping-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'mapping_id'  => array(
					'description' => __( 'ID of the associated mapping.', 'domain-mapping-system' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
					'required'    => true,
				),
				'object_id'   => array(
					'description'       => __( 'ID of the mapped object (e.g., term or post).', 'domain-mapping-system' ),
					'type'              => 'integer',
					'context'           => array( 'edit' ),
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'object_type' => array(
					'description'       => __( 'Type of the mapped object (e.g., term or post).', 'domain-mapping-system' ),
					'type'              => 'string',
					'context'           => array( 'edit' ),
					'required'          => true,
					'enum'              => array( 'term', 'post' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'primary'     => array(
					'description'       => __( 'Type of mapping (primary or secondary).', 'domain-mapping-system' ),
					'type'              => array( 'integer', 'null' ),
					'context'           => array( 'edit' ),
					'sanitize_callback' => 'absint',
				),
			),
		);
	}
}