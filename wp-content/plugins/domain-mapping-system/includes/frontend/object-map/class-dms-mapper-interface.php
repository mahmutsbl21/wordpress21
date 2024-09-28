<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

interface Mapper_Interface {

	/**
	 * Define wp_query
	 *
	 * @return void
	 */
	function define_query(): void;
}