<?php
namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Mapping_Objects\Mapper;
use DMS\Includes\Frontend\Mapping_Objects\Mapper_Interface;
use WP_Query;

class Tribe_Events_Mapper extends Mapper implements Mapper_Interface {

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
		unset($this->query->tax_query);
		unset($this->query->query['error']);
		unset($this->query->is_404);
		unset($this->query->query_vars['error']);
		unset($this->query->query['attachment']);
		unset($this->query->query_vars['attachment']);
		unset($this->query->is_attachment);
		$this->query->query[$this->object->post_type] = $this->object->post_name;
		$this->query->query['post_type'] = $this->object->post_type;
		$this->query->query['name'] = $this->object->post_name;
		$this->query->query_vars[$this->object->post_type] = $this->object->post_name;
		$this->query->query_vars['post_type'] = $this->object->post_type;
		$this->query->query_vars['name'] = $this->object->post_name;
		$this->query->is_single= true;
		$this->query->is_singular= true;
		$this->query->is_page= false;
		$this->query->query_vars['p'] = $this->object->ID;
		$this->query->tribe_is_event = true;
		$this->query->tribe_is_multi_posttype = false;
		$this->query->tribe_is_event_category = false;
		$this->query->tribe_is_event_venue = false;
		$this->query->tribe_is_event_organizer = false;
		$this->query->tribe_is_event_query = true;
	}
}