<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use WP_Query;

class Term_Mapper extends Mapper implements Mapper_Interface {

	/**
	 * Constructor
	 *
	 * @param Mapping_Value $value
	 * @param WP_Query $query
	 */
	public function __construct( Mapping_Value $value, WP_Query $query ) {
		parent::__construct($value, $query);
		$this->object        = get_term( $this->mapping_value->object_id );
		$this->define_query();
	}

	/**
	 * Modify the wp_query according to current object parameters
	 *
	 * @return void
	 */
	public function define_query(): void {
		unset( $this->query->query['category_name'] );
		unset( $this->query->query_vars['category_name'] );
		$this->query->set( 'taxonomy', $this->object->taxonomy );
		$this->query->set( 'term', $this->object->slug );
		$this->query->query['taxonomy'] = $this->object->taxonomy;
		$this->query->query['term']     = $this->object->slug;
		$this->query->set( 'name', '' );
		$this->query->set( 'is_single', false );
		$this->query->set( 'is_archive', true );
		$this->query->set( 'is_tax', true );
		$this->query->set( 'is_singular', false );
		$this->query->set( 'queried_object', $this->object );
		$this->query->set( 'queried_object_id', $this->object->term_id );
		$this->query->name                    = '';
		$this->query->is_single               = false;
		$this->query->is_archive              = true;
		$this->query->is_tax                  = true;
		$this->query->is_singular             = false;
		$this->query->queried_object          = $this->object;
		$this->query->queried_object_id       = $this->object->term_id;

		$this->query->tax_query               = (object) [
			'queries' => [
				[
					'taxonomy' => $this->object->taxonomy,
					'field'    => 'slug',
					'terms'    => $this->object->slug,
					'operator' => 'IN'
				]
			]
		];
		$this->query->query_vars['tax_query'] = $this->query->tax_query->queries;
		unset( $this->query->query_vars['name'] );
		unset( $this->query->query_vars['page'] );
		unset( $this->query->query_vars['post_type'] );
		unset( $this->query->query['page'] );
		unset( $this->query->query['name'] );
		unset( $this->query->query_vars['page_id'] );
		unset( $this->query->is_page );
		unset( $this->query->is_home);
		unset( $this->query->is_attachment);
		unset( $this->query->query['attachment']);
		unset( $this->query->query_vars['attachment']);
		unset( $this->query->is_post_type_archive );
		if (isset($this->query->query_vars['error'])){
			unset($this->query->query_vars['error']);
		}
		if ( isset($this->query->query['error'] ) ) {
			unset( $this->query->query['error'] );
			unset( $this->query->query_vars['error'] );
			unset( $this->query->is_404 );
		}
	}
}