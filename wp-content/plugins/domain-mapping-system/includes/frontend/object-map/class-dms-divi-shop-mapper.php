<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use WP_Query;

class Divi_Shop_Mapper extends Mapper implements Mapper_Interface {

	/**
	 * @var int|null
	 */
	public ?int $shop_page_id = null;
	
	/**
	 * Constructor
	 *
	 * @param Mapping_Value $value
	 * @param WP_Query $query
	 */
	public function __construct( Mapping_Value $value, WP_Query $query ) {
		parent::__construct($value, $query);
		// If we reached here, so for sure Shop page exists, cause previously checked in Shop_Mapper
		$this->shop_page_id = wc_get_page_id( 'shop' );
		$this->object       = get_post( $this->mapping_value->object_id );
		$this->define_query();
	}
	
	/**
	 * Modify the wp_query according to current object parameters
	 *
	 * @return void
	 */
	public function define_query(): void {
		global $et_builder_used_in_wc_shop;
		// Set et_builder_used_in_wc_shop() global to true
		$et_builder_used_in_wc_shop = true;
		
		// Overwrite page query. This overwrite enables is_page() and other standard
		// page-related function to work normally after pre_get_posts hook
		$this->query->set( 'page_id', $this->shop_page_id );
		$this->query->set( 'post_type', 'page' );
		$this->query->set( 'posts_per_page', 1 );
		$this->query->set( 'wc_query', null );
		$this->query->set( 'meta_query', array() );

		$this->query->is_singular          = true;
		$this->query->is_page              = true;
		$this->query->is_post_type_archive = false;
		$this->query->is_archive           = false;

		// Avoid unwanted <p> at the beginning of the rendered builder
		remove_filter( 'the_content', 'wpautop' );
	}
}