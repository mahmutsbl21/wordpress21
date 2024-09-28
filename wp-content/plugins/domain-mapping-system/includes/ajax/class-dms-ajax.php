<?php

namespace DMS\Includes\Ajax;

use DMS\Includes\Data_Objects\Mapping_Value;

/**
 * Ajax class for organizing callbacks for ajax requests
 */
class Ajax {

	/**
	 * Ajax actions
	 *
	 * @var string[]
	 */
	protected static array $actions
		= [
			'mapping_values_search' => 'load_more_mapping_options',
		];

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public static function init(): void {
		self::define_hooks();
	}

	/**
	 * Define hooks
	 *
	 * @return void
	 */
	private static function define_hooks(): void {
		add_action( 'wp_ajax_mapping_values_search', array( 'DMS\Includes\Ajax\Ajax', 'load_more_mapping_options' ) );
	}

	/**
	 * Load more options
	 *
	 * Fires when 'Load More' button is clicked from admin side
	 *
	 * @return void
	 */
	public static function load_more_mapping_options(): void {
		$search_term = ! empty( $_GET['search_term'] ) ? sanitize_text_field( $_GET['search_term'] ) : '';
		$options     = self::search_select_values( $search_term );

		$results = [];
		foreach ( $options as $key_inner => $optgroup ) {
			$group = [ 'text' => $key_inner, 'children' => [] ];
			foreach ( $optgroup as $option ) {
				$group['children'][] = [ 'id' => $option['id'], 'text' => $option['title'] ];
			}
			$results[] = $group;
		}

		wp_send_json( $results );
	}

	/**
	 * Search select values
	 *
	 * Fires when User does search on the mapping select box
	 *
	 * @param string $search
	 *
	 * @return array
	 */
	public static function search_select_values( string $search = '' ): array {
		$posts = [];

		// Get custom post types
		$custom_post_types = get_post_types( [ '_builtin' => false ], 'objects' );

		// Include native post types (Posts and Pages)
		$native_post_types = [
			'post' => 'Posts',
			'page' => 'Pages'
		];

		// Merge custom and native post types
		$post_types = array_merge( $custom_post_types, $native_post_types );

		// Retrieve blog categories if enabled
		$useCats = get_option( 'dms_use_categories' );
		if ( $useCats === 'on' ) {
			$catArgs = [
				'hide_empty' => false,
				'number'     => ( $search ? - 1 : 5 )
			];
			if ( ! empty( $search ) ) {
				$catArgs['name__like'] = $search;
			}
			$cats = get_categories( $catArgs );
			if ( ! empty( $cats ) ) {
				$posts['Blog Categories'] = [];
				foreach ( $cats as $cat ) {
					$posts['Blog Categories'][] = [
						'title' => $cat->name,
						'id'    => 'term_' . $cat->term_id
					];
				}
			}
		}

		// Loop through each post type to retrieve posts and taxonomies connected with the current post type
		foreach ( $post_types as $post_type => $label ) {
			$label    = is_string( $label ) ? $label : $label->label;
			$usePosts = get_option( 'dms_use_' . $post_type );
			if ( $usePosts === 'on' ) {
				$postArgs = [
					'numberposts' => ( $search ? - 1 : 5 ),
					'post_type'   => $post_type
				];
				if ( ! empty( $search ) ) {
					$postArgs['s'] = $search;
				}
				$blogPosts = get_posts( $postArgs );
				if ( ! empty( $blogPosts ) ) {
					$posts[ ucfirst( $label ) ] = [];
					foreach ( $blogPosts as $post ) {
						$posts[ ucfirst( $label ) ][] = [
							'id'    => $post->ID,
							'title' => $post->post_title,
							'link'  => get_permalink( $post->ID )
						];
					}
				}
			}
			// Retrieve custom taxonomies connected with this post
			$postTaxonomies = get_taxonomies( [ 'object_type' => [ $post_type ], '_builtin' => false ], 'objects' );
			foreach ( $postTaxonomies as $taxonomy ) {
				$useTax = get_option( 'dms_use_cat_' . $post_type . '_' . $taxonomy->name );
				if ( $useTax === 'on' ) {
					$taxonomyArgs = [
						'taxonomy'   => $taxonomy->name,
						'hide_empty' => false,
						'number'     => ( $search ? - 1 : 5 )
					];
					if ( ! empty( $search ) ) {
						$taxonomyArgs['name__like'] = $search;
					}
					$terms = get_terms( $taxonomyArgs );
					if ( ! empty( $terms ) ) {
						$posts[ ucfirst( $taxonomy->label ) ] = [];
						foreach ( $terms as $term ) {
							$posts[ ucfirst( $taxonomy->label ) ][] = [
								'title'     => $term->name,
								'id'        => 'term_' . $term->term_id,
								'permalink' => get_term_link( $term->term_id, $taxonomy->name )
							];
						}
					}
				}
			}
		}

		if ( ! empty( $_GET['mapping'] ) ) {
			$mapping        = $_GET['mapping'];
			$values_ids     = [];
			$mapping_values = Mapping_Value::where( [ 'mapping_id' => $mapping ] );
			foreach ( $mapping_values as $value ) {
				$object_id = $value->object_id;
				if ( $value->object_type == 'term' ) {
					$object_id = $value->object_type . '_' . $value->object_id;
				}
				$values_ids[] = $object_id;
			}

			foreach ( $posts as $type => $post_group ) {
				foreach ( $post_group as $key => $post ) {
					if ( in_array( $post['id'], $values_ids ) ) {
						unset( $posts[ $type ][ $key ] );
					}
				}
			}
		}

		return apply_filters( 'dms_search_select_values', $posts );
	}

}