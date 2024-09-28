<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use WP_Query;

class Post_Mapper extends Mapper implements Mapper_Interface {

	/**
	 * Constructor
	 *
	 * @param Mapping_Value $value
	 * @param WP_Query $query
	 */
	public function __construct( Mapping_Value $value, WP_Query $query ) {
		parent::__construct( $value, $query );
		$this->object = get_post( $this->mapping_value->object_id );
		$this->define_query();
	}

	/**
	 * Modify the wp_query according to current object parameters
	 *
	 * @return void
	 */
	public function define_query(): void {
		unset( $this->query->query['error'] );
		unset( $this->query->query_vars['error'] );
		unset( $this->query->is_404 );
		unset( $this->query->query['category_name'] );
		unset( $this->query->query_vars['category_name'] );
		unset( $this->query->query_vars['name'] );
		unset( $this->query->query_vars['page'] );
		unset( $this->query->query_vars['post_type'] );
		unset( $this->query->query['page'] );
		unset( $this->query->query['attachment'] );
		unset( $this->query->query_vars['attachment'] );
		unset( $this->query->is_attachment );
		unset( $this->query->query['name'] );
		unset( $this->query->query_vars['product'] );
		unset( $this->query->query['product'] );
		unset( $this->query->query['post_type'] );
		unset( $this->query->query['posts_per_page'] );
		unset( $this->query->query['no_found_rows'] );
		unset( $this->query->query['cache_results'] );
		unset( $this->query->query_vars['posts_per_page'] );
		unset( $this->query->query_vars['no_found_rows'] );
		unset( $this->query->query_vars['cache_results'] );
		unset( $this->query->query_vars['is_home'] );
		unset( $this->query->tax_query );
		unset( $this->query->is_archive );
		unset( $this->query->query_vars['meta_key'] );
		unset( $this->query->query_vars['orderby'] );
		unset( $this->query->query_vars['order'] );
		unset( $this->query->is_home );
		unset( $this->query->is_category );
		unset( $this->query->query['pagename']);
		unset( $this->query->query_vars['pagename']);
		unset( $this->query->queried_object_id);
		unset( $this->query->queried_object);

		if ( $this->object->post_type !== 'page' ) {
			$this->query->is_page            = false;
			$this->query->is_single          = true;
			$this->query->is_singular        = true;
			$this->query->query['post_type'] = $this->object->post_type;
			$this->query->query['name']      = $this->object->post_name;
			$this->query->set( 'post_type', $this->object->post_type );
			$this->query->set( 'name', $this->object->post_name );
		} else {
			unset( $this->query->tax_query );
			unset( $this->query->relation );
			unset( $this->query->is_archive );
			unset( $this->query->is_category );
			$this->query->is_singular = true;
			$this->query->is_page     = true;
			$this->query->is_single   = false;
		}
		$this->query->set( 'page_id', $this->object->ID );
		$this->query->is_post_type_archive = false;
	}
}