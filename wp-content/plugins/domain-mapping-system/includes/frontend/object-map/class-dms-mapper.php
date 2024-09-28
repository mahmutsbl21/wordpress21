<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use WP_Post;
use WP_Query;
use WP_Term;

class Mapper {

	/**
	 * WP query instance which must be modified
	 *
	 * @var WP_Query
	 */
	public WP_Query $query;

	/**
	 * Matching Mapping value
	 *
	 * @var Mapping_Value
	 */
	public Mapping_Value $mapping_value;

	/**
	 * The object corresponding mapping value
	 *
	 * @var WP_Post|WP_Term
	 */
	public $object;

	/**
	 * Constructor
	 *
	 * @param Mapping_Value $value
	 * @param WP_Query $query
	 */
	public function __construct(  Mapping_Value $value, WP_Query $query ) {
		$this->query         = $query;
		$this->mapping_value = $value;
	}

	/**
	 * Get the current query
	 *
	 * @return WP_Query
	 */
	public function get_query():WP_Query {
		return $this->query;
	}
}