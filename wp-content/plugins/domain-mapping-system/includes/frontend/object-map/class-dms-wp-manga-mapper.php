<?php
namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Mapping_Objects\Mapper;
use DMS\Includes\Frontend\Mapping_Objects\Mapper_Interface;
use WP_Query;

class Wp_Manga_Mapper extends Mapper implements Mapper_Interface {

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
	 * Define the query
	 *
	 * @return void
	 */
	function define_query(): void {
		unset($this->query->query_vars['page_id']);
		unset($this->query->query['error']);
		unset($this->query->query_vars['error']);
		unset($this->query->query['attachment']);
		unset($this->query->query_vars['attachment']);
		unset($this->query->query_vars['post__not_in']);
		$this->query->query[$this->object->post_type] = $this->object->post_name;
		$this->query->query['post_type'] = $this->object->post_type;
		$this->query->query['name'] = $this->object->post_name;
		$this->query->query_vars[$this->object->post_type] = $this->object->post_name;
		$this->query->query_vars['post_type'] = $this->object->post_type;
		$this->query->query_vars['name'] = $this->object->post_name;
		$this->query->is_single= true;
		$this->query->is_singular= true;
		$this->query->is_page= false;
		$this->query->query['page'] = '';
		$this->query->query_vars['page'] = '';
		$this->query->is_posts_page = false;
		$this->query->is_home = false;
		$this->query->tax_query = null;
		$this->query->attachment = false;
		$this->query->is_404 = false;
	}
}