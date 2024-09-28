<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use WP_Query;

class Product_Mapper extends Mapper implements Mapper_Interface {

	/**
	 * Constructor
	 *
	 * @param Mapping_Value $value
	 * @param WP_Query $query
	 */
	public function __construct( Mapping_Value $value, WP_Query $query ) {
		parent::__construct($value, $query);
		$this->object        = get_post( $this->mapping_value->object_id );
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
		unset( $this->query->is_home);
		unset( $this->query->query['error']);
		unset( $this->query->query_vars['error']);
		$this->query->is_404 = false;
		$this->query->query['product'] = $this->object->post_name;
		$this->query->set( 'product', $this->object->post_name );
		$this->query->query['post_type'] = $this->object->post_type;
		$this->query->query['name'] = $this->object->post_name;
		$this->query->query['page'] = '';
		$this->query->is_singular = true;
		$this->query->is_single = true;
		$this->query->set( 'post_type', $this->object->post_type );
		$this->query->set( 'name', $this->object->post_name );
		$this->query->set( 'page', '' );
	}
}