<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Utils\Helper;
use DMS\Includes\Data_Objects\Mapping;

class Mapping_Repository {

	/**
	 * Allowed request methods
	 */
	const ALLOWED_METHODS = [
		'create',
		'update',
		'delete',
	];

	/**
	 * Dynamically create, update, delete mappings
	 *
	 * @param array $params
	 *
	 * @return array
	 * @throws DMS_Exception
	 */
	public function batch( array $params ): array {
		$results = [];

		foreach ( $params as $param ) {
			if ( empty( $param['method'] ) || ! in_array( $param['method'], self::ALLOWED_METHODS ) ) {
				throw new DMS_Exception( 'unknown_method', __('Unknown method', 'domain-mapping-system') );
			}

			$method = $param['method'];
			$data   = $param['data'];

			$errors  = [];
			$success = true;

			foreach ( $data as $item ) {
				$result = null;
				switch ( $method ) {
					case 'create':
						$result = Mapping::create( $item );
						break;
					case 'update':
						$result = Mapping::update( $item['id'], $item );
						break;
					case 'delete':
						$result = Mapping::delete( $item['id'] );
						break;
				}

				if ( Helper::is_dms_error( $result ) ) {
					$errors[] = $result;
					$success  = false;
				}
			}

			$results[] = [
				'method'  => $method,
				'success' => $success,
				'errors'  => $errors
			];
		}

		return $results;
	}


	/**
	 * Create or update mapping by given params
	 *
	 * @param array $params
	 *
	 * @return Mapping
	 * @throws DMS_Exception
	 */
	public function create( array $params ): Mapping {
		$data = [
			'host'          => ! empty( $params['host'] ) ? sanitize_text_field( $params['host'] ) : '',
			'path'          => ! empty( $params['path'] ) ? sanitize_text_field( $params['path'] ) : '',
			'attachment_id' => ! empty( $params['attachment_id'] ) ? sanitize_text_field( $params['attachment_id'] ) : 0,
			'custom_html'   => ! empty( $params['custom_html'] ) ? sanitize_text_field( $params['custom_html'] ) : ''
		];
		if ( ! empty( $params['id'] ) ) {
			return Mapping::update( $params['id'], $data );
		} else {
			$mapping = Mapping::where( [ 'host' => $data['host'], 'path' => $data['path'] ] );
			if ( ! empty( $mapping ) ) {
				$path_string = '';
				if ( ! empty( $data['path'] ) ) {
					$path_string = ' and path: %s';
				}
				throw new DMS_Exception( 'duplicate_mapping', sprintf(
					'Mapping with host: %s' . $path_string . ' already exists',
					$data['host'],
					$data['path']
				) );
			}

			return Mapping::create( $data );
		}
	}
}