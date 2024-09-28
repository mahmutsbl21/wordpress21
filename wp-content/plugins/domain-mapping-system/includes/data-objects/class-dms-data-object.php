<?php

namespace DMS\Includes\Data_Objects;

use DMS\Includes\Exceptions\DMS_Exception;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class Data_Object implements JsonSerializable {

	/**
	 * Data of the object
	 *
	 * @var null|array
	 */
	protected ?array $data;

	/**
	 * Constructor
	 *
	 * @param null|array $instance
	 */
	function __construct( ?array $instance = null ) {
		$this->set_data( $instance );
		$this->hydrate();
	}

	/**
	 * Hydrate
	 *
	 * @return void
	 */
	public function hydrate(): void {
		foreach ( $this->data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$setter = 'set' . '_' . strtolower( $key );
				if ( method_exists( $this, $setter ) ) {
					call_user_func( [ $this, $setter ], $value );
				} else {
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Creates new data object
	 *
	 * @param array $data
	 *
	 * @return object
	 */
	abstract public static function create( array $data ): object;

	/**
	 * Finds data object
	 *
	 * @param null|int $id
	 *
	 * @return object|null
	 */
	abstract public static function find( ?int $id ): ?object;

	/**
	 * Creates new data object in db
	 *
	 * @param $data
	 *
	 * @return object
	 * @throws DMS_Exception
	 */
	public static function wpdb_create( $data ): object {
		global $wpdb;

		$result = $wpdb->insert( $wpdb->prefix . static::TABLE, $data );
		if ( empty( $result ) ) {
			throw new DMS_Exception( 'error_on_save', __( 'Error on save.', 'domain-mapping-system' ) );
		}
		$inserted_id = $wpdb->insert_id;

		$data['id'] = $inserted_id;

		return self::make( $data );
	}

	/**
	 * Makes new object of Data object
	 *
	 * @param array $data the data
	 *
	 * @return object
	 */
	public static function make( array $data ): object {
		return new static( $data );
	}

	/**
	 * Gets data objects from db corresponding to conditions
	 *
	 * @param array $conditions
	 * @param null|int $paged
	 * @param null|int $limit
	 * @param string|null $orderby
	 * @param null|string $order
	 *
	 * @return array
	 */
	public static function wpdb_where( array $conditions, ?int $paged = null, ?int $limit = 1, ?string $orderby = 'id', ?string $order = 'ASC' ): array {
		global $wpdb;
		$where_clause = '1';
		$values       = array();

		foreach ( $conditions as $key => $value ) {
			if ( is_array( $value ) ) {
				$placeholders = array();
				foreach ( $value as $val ) {
					$placeholders[] = is_int( $val ) ? '%d' : '%s';
					$values[]       = $val;
				}
				$where_clause .= " AND `$key` IN (" . implode( ', ', $placeholders ) . ")";
			} else {
				$where_clause .= " AND `$key` = " . ( is_int( $value ) ? '%d' : '%s' );
				$values[]     = $value;
			}
		}

		$orderby = esc_sql( $orderby );
		$order   = strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC';
		$query   = "SELECT * FROM `" . $wpdb->prefix . static::TABLE . "` WHERE $where_clause ORDER BY `$orderby` $order ";
		$query   = ! empty( $values ) ? $wpdb->prepare( $query, $values ) : $query;
		if ( ! empty( $paged ) || ! empty( $limit ) ) {
			$paged = is_null( $paged ) ? 1 : $paged;
			$limit = (int) $limit;

			if ( $limit > 0 ) {
				if ($orderby == 'primary'){
					$offset = ! empty( $paged ) ? $paged : 0;
				} else {
					$offset = ! empty( $paged ) ? ( $paged - 1 ) * $limit : 0;
				}
				$limit  = $wpdb->prepare( "LIMIT %d, %d", $offset, $limit );
				$query  .= $limit;
			}
		}

		$result = $wpdb->get_results( $query, ARRAY_A );
		$data   = [];

		if ( ! empty( $result ) ) {
			foreach ( $result as $res ) {
				$mapping = self::make( $res );
				$data[]  = $mapping;
			}
		}

		return $data;
	}

	/**
	 * Finds from database
	 *
	 * @param null|int $id
	 *
	 * @return object|null
	 */
	public static function wpdb_find( ?int $id ): ?object {
		global $wpdb;
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . static::TABLE . " WHERE id=%d", $id ), ARRAY_A );

		return !empty( $result ) ? self::make( $result ) : null;
	}

	/**
	 * Deletes data object
	 *
	 * @param null|int $id
	 *
	 * @return true
	 * @throws DMS_Exception
	 */
	public static function wpdb_delete( ?int $id ): bool {
		global $wpdb;
		$result = $wpdb->delete( $wpdb->prefix . static::TABLE, [ 'id' => $id ] );
		if ( $result === false ) {
			throw new DMS_Exception( 'not_found', __( 'Object not found', 'domain-mapping-system' ) );
		}

		return true;
	}

	/**
	 * Update data objects
	 *
	 * @param $id
	 * @param $data
	 *
	 * @return object
	 */
	public static function wpdb_update( $id, $data ): object {
		global $wpdb;
		$where = [ 'id' => (int) $id ];
		$wpdb->update( $wpdb->prefix . static::TABLE, $data, $where );

		$data = array_merge( $where, $data );

		return self::make( $data );
	}

	/**
	 * Finds setting
	 *
	 * @param string $key
	 *
	 * @return object
	 */
	public static function setting_find( string $key ): object {
		$option = get_option( $key, null );

		$data = [ 'key' => $key, 'value' => $option ];

		return self::make( $data );
	}

	/**
	 * Creates setting
	 *
	 * @param array $data
	 *
	 * @return object
	 */
	public static function setting_create( array $data ): object {
		update_option( $data['key'], $data['value'] );

		return self::make( $data );
	}

	/**
	 * Deletes setting
	 *
	 * @param mixed $key
	 *
	 * @return true
	 * @throws DMS_Exception
	 */
	public static function setting_delete( ?string $key ): bool {
		if ( ! delete_option( $key ) ) {
			throw new DMS_Exception( 'setting_not_found', __( 'Setting was not found', 'domain-mapping-system' ) );
		}

		return true;
	}

	/**
	 * Gets count of data object
	 *
	 * @param array $conditions
	 *
	 * @return string|null
	 */
	public static function count( array $conditions = [] ): ?string {
		global $wpdb;
		$where_clause = 'WHERE 1 ';
		$values       = [];
		foreach ( $conditions as $key => $value ) {
			if ( ! empty( $where_clause ) ) {
				$where_clause .= ' AND ';
			}
			if ( is_int( $value ) ) {
				$where_clause .= "$key = %d";
			} else {
				$where_clause .= "$key = %s";
			}
			$values[] = $value;
		}
		$where_clause = ! empty( $where_clause ) ? $wpdb->prepare( $where_clause, implode( ',', $values ) ) : $where_clause;

		return $wpdb->get_var( 'SELECT COUNT(id) FROM ' . $wpdb->prefix . static::TABLE . ' ' . $where_clause );
	}

	/**
	 * Data getter
	 *
	 * @return array|null
	 */
	public function get_data(): ?array {
		return $this->data;
	}

	/**
	 * Data setter
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function set_data( $data ): void {
		$this->data = $data;
	}

	/**
	 * Json serialize
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return $this->toArray();
	}

	/**
	 * To array
	 *
	 * @return array
	 */
	public function toArray(): array {
		$reflection = new ReflectionClass( $this );
		$array      = [];

		foreach ( $reflection->getProperties( ReflectionProperty::IS_PUBLIC ) as $property ) {
			$name = $property->getName();

			// Check if the property is initialized
			if ( $property->isInitialized( $this ) ) {
				$value = $property->getValue( $this );

				if ( is_object( $value ) && is_callable( [ $value, 'toArray' ] ) ) {
					$value = $value->toArray();
				} elseif ( is_array( $value ) ) {
					$sub_array = [];
					foreach ( $value as $sub_property => $sub_value ) {
						if ( is_object( $sub_value ) && is_callable( [ $sub_value, 'toArray' ] ) ) {
							$sub_value = $sub_value->toArray();
						}
						$sub_array[ $sub_property ] = $sub_value;
					}
					$value = $sub_array;
				}
				$array[ $name ] = $value;
			} else {
				// Optionally, handle uninitialized properties here
				$array[ $name ] = null;
			}
		}

		return $array;
	}
}