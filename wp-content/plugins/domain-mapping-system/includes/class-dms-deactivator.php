<?php

namespace DMS\Includes;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       https://limb.dev
 * @since      1.0.0
 */
class Deactivator {
	
	public function __construct() {
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public function deactivate() {
	}

	/**
	 * Uninstall plugin
	 * 
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;
		if ( is_multisite() ) {
			$sites = get_sites();
			foreach ( $sites as $blog ) {
				if ( ! empty( get_blog_option( $blog->id, 'dms_delete_upon_uninstall' ) ) ) {
					$prefix = $wpdb->get_blog_prefix( $blog->id );
					// Custom tables
					$wpdb->query( "DROP TABLE IF EXISTS `" . $prefix . "dms_mappings`" );
					$wpdb->query( "DROP TABLE IF EXISTS `" . $prefix . "dms_mapping_values`" );
					// Options
					delete_blog_option($blog->id,'dms_platform_wpcs_domains_retrieved' );
					delete_blog_option($blog->id,'dms_platform_wpcs_domains_possible_substitution' );
					delete_blog_option($blog->id,'dms_delete_upon_uninstall' );
					delete_blog_option($blog->id,'dms_version' );
					delete_blog_option($blog->id,'dms_enable_query_strings' );
					delete_blog_option($blog->id,'dms_force_site_visitors' );
					delete_blog_option($blog->id,'dms_global_mapping' );
					delete_blog_option($blog->id,'dms_map' );
					delete_blog_option($blog->id,'dms_use_post' );
					delete_blog_option($blog->id,'dms_use_page' );
					delete_blog_option($blog->id,'dms_use_categories' );
					delete_blog_option($blog->id,'dms_mdm_import_note' );
					delete_blog_option($blog->id,'dms_api_secret');
					delete_blog_option($blog->id,'dms_use_product');
					delete_blog_option($blog->id,'dms_use_product_archive');
					delete_blog_option($blog->id,'dms_woo_shop_global_mapping');
					delete_blog_option($blog->id,'dms_global_parent_page_mapping');
					delete_blog_option($blog->id,'dms_seo_sitemap_per_domain');
					delete_blog_option($blog->id,'dms_seo_options_per_domain');
					delete_blog_option($blog->id,'dms_rewrite_urls_on_mapped_page');
					delete_blog_option($blog->id,'dms_remove_parent_page_slug_from_child');
					delete_blog_option($blog->id,'dms_rewrite_urls_on_mapped_page_sc');
					delete_blog_option($blog->id,'dms_mappings_per_page');
					delete_blog_option($blog->id,'dms_values_per_mapping');
					delete_blog_option($blog->id,'dms_archive_global_mapping');
					delete_blog_option($blog->id,'dms_migration_200');
					delete_blog_option($blog->id,'dms-old-mappings');
					delete_blog_option($blog->id,'dms-old-mapping_values');
					
					$types = get_post_types( array(
						'public'   => true,
						'_builtin' => false
					), 'objects' );
					foreach ( $types as $singular => $item ) {
						if ( get_blog_option( $blog->id, "dms_use_{$item->query_var}" ) !== false ) {
							delete_blog_option( $blog->id, "dms_use_{$item->query_var}" );
						}
						$taxonomies = get_object_taxonomies( $singular, 'objects' );
						if ( ! empty( $taxonomies ) ) {
							foreach ( $taxonomies as $taxonomy ) {
								if ( $taxonomy->public && $taxonomy->publicly_queryable ) {
									delete_blog_option( $blog->id, 'dms_use_cat_' . $item->query_var . '_' . $taxonomy->name );
								}
							}
						}
					}
				}
			}
		} elseif ( ! empty( get_option( 'dms_delete_upon_uninstall' ) ) ) {
			// Custom tables
			$wpdb->query( "DROP TABLE `" . $wpdb->prefix . "dms_mappings`" );
			$wpdb->query( "DROP TABLE `" . $wpdb->prefix . "dms_mapping_values`" );
			// Options
			delete_option('dms_platform_wpcs_domains_retrieved' );
			delete_option('dms_platform_wpcs_domains_possible_substitution' );
			delete_option('dms_delete_upon_uninstall' );
			delete_option('dms_version' );
			delete_option('dms_enable_query_strings' );
			delete_option('dms_force_site_visitors' );
			delete_option('dms_global_mapping' );
			delete_option('dms_map' );
			delete_option('dms_use_post' );
			delete_option('dms_use_page' );
			delete_option('dms_use_categories' );
			delete_option('dms_mdm_import_note' );
			delete_option('dms_api_secret');
			delete_option('dms_use_product');
			delete_option('dms_use_product_archive');
			delete_option('dms_woo_shop_global_mapping');
			delete_option('dms_global_parent_page_mapping');
			delete_option('dms_seo_sitemap_per_domain');
			delete_option('dms_seo_options_per_domain');
			delete_option('dms_rewrite_urls_on_mapped_page');
			delete_option('dms_remove_parent_page_slug_from_child');
			delete_option('dms_rewrite_urls_on_mapped_page_sc');
			delete_option('dms_mappings_per_page');
			delete_option('dms_values_per_mapping');
			delete_option('dms_archive_global_mapping');
			delete_option('dms_migration_200');
			delete_option('dms-old-mappings');
			delete_option('dms-old-mapping_values');
			
			$types = get_post_types( array(
				'public'   => true,
				'_builtin' => false
			), 'objects' );
			foreach ( $types as $singular => $item ) {
				if ( get_option( "dms_use_{$item->query_var}" ) !== false ) {
					delete_option( "dms_use_{$item->query_var}" );
				}
				$taxonomies = get_object_taxonomies( $singular, 'objects' );
				if ( ! empty( $taxonomies ) ) {
					foreach ( $taxonomies as $taxonomy ) {
						if ( $taxonomy->public && $taxonomy->publicly_queryable ) {
							delete_option( 'dms_use_cat_' . $item->query_var . '_' . $taxonomy->name );
						}
					}
				}
			}
		}
	}

}
