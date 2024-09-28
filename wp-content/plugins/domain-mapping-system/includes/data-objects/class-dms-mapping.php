<?php

namespace DMS\Includes\Data_Objects;


use DMS\Includes\Exceptions\DMS_Exception;

class Mapping extends Data_Object {

	/**
	 * DB table name
	 */
	const TABLE = 'dms_mappings';

	/**
	 * Host of the mapping
	 *
	 * @var null|string
	 */
	public ?string $host = null;

	/**
	 * Path of the mapping
	 *
	 * @var string|null
	 */
	public ?string $path = null;

	/**
	 * Favicon image id of the mapping
	 *
	 * @var null|int
	 */
	public ?int $attachment_id;

	/**
	 * Custom html of mapping
	 *
	 * @var null|string
	 */
	public ?string $custom_html;

	/**
	 * ID of the mapping
	 *
	 * @var null|int
	 */
	public ?int $id;

	/**
	 * Create new mapping
	 *
	 * @param array $data
	 *
	 * @return object
	 * @throws DMS_Exception
	 */
	public static function create( array $data ): object {
		return parent::wpdb_create( $data );
	}

	/**
	 * Update mapping
	 *
	 * @param int $id ID of the mapping
	 * @param array $data the data which must be updated
	 *
	 * @return Mapping
	 */
	public static function update( int $id, array $data ): Mapping {
		return parent::wpdb_update( $id, $data );
	}

	/**
	 * Delete mapping
	 *
	 * @param int $id
	 *
	 * @return bool
	 * @throws DMS_Exception
	 */
	public static function delete( int $id ): bool {
		return parent::wpdb_delete( $id );
	}

	/**
	 * Gets count of mappings
	 *
	 * @param array $conditions
	 *
	 * @return string|null
	 */
	public static function count( array $conditions = [] ): ?string {
		return parent::count( $conditions );
	}

	/**
	 * Get Mapping by mapping value
	 *
	 * @param null|string $type
	 * @param null|int $value
	 *
	 * @return array|null
	 */
	public static function get_by_mapping_value( ?string $type, ?int $value ): ?array {
		$mapping_values = Mapping_Value::where( [ 'object_id' => $value, 'object_type' => $type ] );
		if ( ! empty( $mapping_values ) ) {
			$ids = [];
			foreach ( $mapping_values as $value ) {
				$ids [] = (int) $value->mapping_id;
			}

			return self::where( [ 'id' => $ids ] );

		}

		return null;
	}

	/**
	 * Get mapping by conditions
	 *
	 * @param array $data
	 * @param null|int $paged
	 * @param null| int $limit
	 *
	 * @return array
	 */
	public static function where( array $data = [], ?int $paged = null, ?int $limit = null ): array {
		return parent::wpdb_where( $data, $paged, $limit );
	}

	/**
	 * Get primary mapping value
	 *
	 * @param null|int $mapping_id
	 *
	 * @return int|mixed
	 */
	public static function get_primary_mapping_value( ?int $mapping_id ): ?Mapping_Value {
		$mapping_values = Mapping_Value::where( [ 'mapping_id' => $mapping_id ] );
		foreach ( $mapping_values as $value ) {
			if ( $value->primary ) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Get main mapping
	 *
	 * @return Mapping|null
	 */
	public static function get_main(): ?Mapping {
		$main_mapping_id = Setting::find( 'dms_main_mapping' )->get_value();
		if ( !empty($main_mapping_id) ) {
			$mapping = Mapping::find( $main_mapping_id );
		}
		if( empty( $mapping ) ) {
			$mapping = Mapping::where();
			$mapping = !empty($mapping[0]) ? $mapping[0] : null;
		}
		return $mapping ?? null;
	}

	/**
	 * Finds mapping by id
	 *
	 * @param null|int $id
	 *
	 * @return Mapping|null
	 */
	public static function find( ?int $id ): ?Mapping {
		return parent::wpdb_find( $id );
	}

	/**
	 * Host getter
	 *
	 * @return string
	 */
	public function get_host(): ?string {
		return $this->host;
	}

	/**
	 * Host setter
	 *
	 * @param null|string $host
	 *
	 * @return void
	 */
	public function set_host( ?string $host ): void {
		$this->host = $host;
	}

	/**
	 * Path setter
	 *
	 * @return string|null
	 */
	public function get_path(): ?string {
		return $this->path;
	}

	/**
	 * Path setter
	 *
	 * @param string|null $path
	 *
	 * @return void
	 */
	public function set_path( ?string $path ): void {
		$this->path = $path;
	}

	/**
	 * Favicon getter
	 *
	 * @return int|null
	 */
	public function get_attachment_id(): ?int {
		return $this->attachment_id;
	}

	/**
	 * Attachment id setter
	 *
	 * @param null|int $attachment_id
	 *
	 * @return void
	 */
	public function set_attachment_id( ?int $attachment_id ): void {
		$this->attachment_id = $attachment_id;
	}

	/**
	 * Custom html getter
	 *
	 * @return string|null
	 */
	public function get_custom_html(): ?string {
		return $this->custom_html;
	}

	/**
	 * Custom html setter
	 *
	 * @param null|string $custom_html
	 *
	 * @return void
	 */
	public function set_custom_html( ?string $custom_html ): void {
		$this->custom_html = $custom_html;
	}

	/**
	 * ID getter
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * ID Setter
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}
}