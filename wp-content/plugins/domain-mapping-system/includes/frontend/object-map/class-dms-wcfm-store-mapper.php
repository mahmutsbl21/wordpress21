<?php
namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use WP_Query;

class Wcfm_Store_Mapper extends Mapper implements Mapper_Interface {

	/**
	 * Constructor
	 *
	 * @param Mapping_Value $value
	 * @param WP_Query $query
	 */
	public function __construct( Mapping_Value $value, WP_Query $query ) {
		parent::__construct( $value, $query );
		$this->object = wcfmmp_get_store( $this->mapping_value->object_id );
		$this->define_query();
	}

	/**
	 * Define the query
	 *
	 * @return void
	 */
	function define_query(): void {
		$path = trim(wp_parse_url($this->object->get_shop_url(), PHP_URL_PATH), '/');
		$path_array = explode('/', $path);
		$slug = $path_array[count($path_array) - 1];
		$store_slug = $path_array[count($path_array) - 2];
		unset($this->query->query['error']);
		unset($this->query->query_vars['error']);
		unset($this->query->is_404);
		unset($this->query->is_singular);
		
		$this->query->query['post_type'] = 'product';
		$this->query->query_vars['post_type'] = 'product';
		$this->query->query[$store_slug] = $slug;
		$this->query->query_vars[$store_slug] = $slug;
		$this->query->query_vars['author'] = 0;
		$this->query->query_vars['author_name'] = $slug;
		$this->query->query_vars['post_status'] = 'publish';
		$this->query->query_vars['posts_per_page'] = 10;
		$this->query->query['term_section'] = array();
		$this->query->query_vars['wc_query'] = 'product_query';
		$this->query->is_archvie = true;
		$this->query->is_post_type_archive = true;
	}
}