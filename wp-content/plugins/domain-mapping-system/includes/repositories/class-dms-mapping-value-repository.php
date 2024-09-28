<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Utils\Helper;

class Mapping_Value_Repository {

	/**
	 * Create multiple mapping values
	 *
	 * @param int $id Mapping id
	 * @param array $params Params which must be updated
	 *
	 * @return array
	 * @throws DMS_Exception
	 */
	public function batch( int $id, array $params ): array {
		$errors = [];
		foreach ( $params as $data ) {
			$data['mapping_id'] = $id;

			if ( ! empty( $data['id'] ) ) {
				$res = Mapping_Value::update( $data['id'], $data );
			} else {
				$res = Mapping_Value::create( $data );
			}
			if ( Helper::is_dms_error( $res ) ) {
				$errors[] = $res;
			}
		}
		$success = empty( $errors );

		return [
			'success' => $success,
			'errors'  => $errors
		];
	}

	/**
	 * Create or update mapping value by given params
	 *
	 * @param array $params
	 * @param int $mapping_id
	 *
	 * @return Mapping_Value
	 * @throws DMS_Exception
	 */
	public function create( int $mapping_id, array $params ): Mapping_Value {
		$data    = [
			'object_type' => ! empty( $params['object_type'] ) ? sanitize_text_field( $params['object_type'] ) : '',
			'object_id'   => ! empty( $params['object_id'] ) ? sanitize_text_field( $params['object_id'] ) : '',
			'primary'     => ! empty( $params['primary'] ) ? sanitize_text_field( $params['primary'] ) : 0,
			'mapping_id'  => sanitize_text_field( $mapping_id )
		];
		$mapping = Mapping_Value::where( $data );
		if ( empty( $mapping ) ) {
			if ( ! empty( $params['id'] ) ) {
				return Mapping_Value::update( $params['id'], $data );
			} else {
				return Mapping_Value::create( $data );
			}
		} else {
			return $mapping[0];
		}
	}

	/**
	 * Delete multiple mapping values by mapping id
	 *
	 * @param int $id
	 *
	 * @return true
	 * @throws DMS_Exception
	 */
	public function delete_items( int $id, $count ): bool {
		$mapping_values = Mapping_Value::where( [ 'mapping_id' => $id ], 0, $count );
		if ( ! empty( $mapping_values ) ) {
			foreach ( $mapping_values as $value ) {
				Mapping_Value::delete( $value->id );
			}
		}

		return true;
	}
}