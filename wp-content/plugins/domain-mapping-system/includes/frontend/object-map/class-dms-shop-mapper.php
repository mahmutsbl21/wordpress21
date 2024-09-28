<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Services\Request_Params;
use DMS\Includes\Utils\Helper;
use WP_Query;

class Shop_Mapper extends Mapper implements Mapper_Interface {

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
		$this->shop_page_id = wc_get_page_id( 'shop' );
		// Maybe Divi runs shop page
		if($this->divi_runs_shop_page()) {
			$divi_shop_mapper = new Divi_Shop_Mapper($this->mapping_value, $this->query);
			$this->query = $divi_shop_mapper->query;
		} else {
			$this->object  = get_post( $this->mapping_value->object_id );
			$this->define_query();	
		}
	}

	/**
	 * Check whether Divi supports the shop page
	 * 
	 * @return bool
	 */
	public function divi_runs_shop_page() {
		// Check if Divi theme or parent theme is active
		return Helper::active_theme_is_divi() && function_exists( 'et_pb_is_pagebuilder_used' ) && et_pb_is_pagebuilder_used( $this->shop_page_id );
	}

	/**
	 * Modify the wp_query according to current object parameters
	 *
	 * @return void
	 */
	public function define_query(): void {
		$pagination_path = (new Request_Params())->pagination_path;
		if ($pagination_path){
			$paged = str_replace('page/','', $pagination_path);
		} else {
			$paged = 1;
		}
		unset($this->query->query['attachment']);
		unset($this->query->query['page']);
		unset($this->query->query['name']);
		unset($this->query->query_vars['attachment']);
		unset($this->query->is_attachment);
		unset($this->query->query_vars['attachment_id']);
		unset($this->query->query_vars['page']);
		unset($this->query->query_vars['name']);
		$this->query->set( 'post_type', 'product' );
		$this->query->set( 'page_id', '' );
		$this->query->is_attachment        = false;
		$this->query->is_singular          = false;
		$this->query->is_home              = false;
		$this->query->is_single            = false;
		$this->query->is_page              = false;
		$this->query->is_post_type_archive = true;
		$this->query->is_archive           = true;
		$this->query->is_404               = false;
		$this->query->set( 'wc_query', 'product_query' );
		$this->query->set( 'error', '' );
		$this->query->set( 'paged', $paged );


		if ( ! empty( $instance->path ) ) {
			$this->query->set( 'attachment', '' );
			$this->query->set( 'name', '' );
			$this->query->set( 'page', '' );
			$this->query->set( 'pagename', '' );
			$this->query->set( 'category_name', '' );
			$this->query->query = [];
		}
	}
}