<?php

namespace DMS\Includes\Utils;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Integrations\Seo_Yoast;
use Exception;
use WP_Error;

class Helper {

	/**
	 * Check is an AI_Exception instance or not
	 *
	 * @param Exception|DMS_Exception|mixed $e
	 *
	 * @return bool
	 */
	public static function is_dms_error( $e ): bool {
		return ( $e instanceof DMS_Exception );
	}

	/**
	 * If debug mode is turned on log the data
	 *
	 * @param Exception|DMS_Exception $data
	 * @param string $key
	 *
	 * @return void
	 */
	public static function log( $data, string $key = 'log' ) {
		$debug = DMS()::get_debug();
		if ( $debug ) {
			if ( $data instanceof DMS_Exception ) {
				$data_to_log = $data->get_error_data();
			} else {
				$data_to_log = $data;
			}
			if ( $data instanceof Exception ) {
				$message = $data->getMessage() . ':  ';
			}
			error_log( DMS()->get_plugin_name() . '-debug: [' . $key . ']: ' . ( $message ?? '' ) . print_r( $data_to_log, true ) );
		}
	}

	/**
	 * Get the WP error instance based on the message
	 *
	 * @param Exception|DMS_Exception $e
	 *
	 * @return WP_Error
	 */
	public static function get_wp_error( $e ): WP_Error {
		if ( $e instanceof DMS_Exception ) {
			return new WP_Error( $e->get_error_code(), $e->getMessage(), $e->get_error_data() );
		}

		return new WP_Error( 'technical_error', __( 'Technical error', 'domain-mapping-system' ), [ 'http_status' => 400 ] );
	}

	/**
	 * Get custom taxonomies
	 *
	 * @param $object_type
	 *
	 * @return array
	 */
	public static function get_custom_taxonomies( $object_type ): array {
		$taxonomies = get_taxonomies( array(
			'object_type' => array( $object_type ),
			'_builtin'    => false,
		) );

		return array_map( function ( $taxonomy ) {
			return ucfirst( $taxonomy );
		}, $taxonomies );
	}

	/**
	 * Get scheme
	 *
	 * @return string
	 */
	public static function get_scheme(): string {
		return trim( wp_parse_url( get_site_url(), PHP_URL_SCHEME ) );
	}

	/**
	 * Check is the host valid
	 *
	 * @param string $host
	 *
	 * @return bool
	 */
	public static function is_valid_host( string $host ): bool {
		if ( strpos( $host, '.' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get base host
	 *
	 * @return string
	 */
	public static function get_base_host(): string {
		return trim( wp_parse_url( get_site_url(), PHP_URL_HOST ) );
	}

	/**
	 * Check is subdirectory install
	 *
	 * @return bool
	 */
	public static function is_sub_directory_install(): bool {
		return ! empty( self::get_base_path() );
	}

	/**
	 * Get base path
	 *
	 * @return array|string|string[]|null
	 */
	public static function get_base_path() {
		$path = wp_parse_url( get_site_url(), PHP_URL_PATH );

		return ! empty( $path ) ? preg_replace( '/\//', '', trim( $path ), 1 ) : '';
	}

	/**
	 * Check is bedrock structure
	 *
	 * @return bool
	 */
	public static function check_if_bedrock(): bool {
		$separators = explode( '/', WP_CONTENT_DIR );
		if ( $separators[ count( $separators ) - 1 ] == 'app' && $separators[ count( $separators ) - 2 ] == 'web' ) {
			return true;
		}

		return false;
	}


	/**
	 * Replace substring
	 *
	 * @param string $str_pattern
	 * @param string $str_replacement
	 * @param string $string
	 *
	 * @return array|string|string[]
	 */
	public static function str_replace_once( string $str_pattern, string $str_replacement, string $string ) {
		$str_pos = ! empty( $str_pattern ) ? strpos( $string, $str_pattern ) : 0;
		if ( str_contains( $string, $str_pattern ) ) {
			return substr_replace( $string, $str_replacement, $str_pos, strlen( $str_pattern ) );
		}

		return $string;
	}

	/**
	 * Get host plus path
	 *
	 * @param Mapping $mapping
	 *
	 * @return string
	 */
	public static function get_host_plus_path( Mapping $mapping ): string {
		$mapping = [ $mapping->host, $mapping->path ];

		return trim( implode( '/', $mapping ), '/' );
	}

	/**
	 * Check is string ends with substring
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function ends_with( string $haystack, string $needle ): bool {
		$length = strlen( $needle );
		if ( $length == 0 ) {
			return true;
		}

		return ( substr( $haystack, - $length ) === $needle );
	}

	/**
	 * Get the shop page id
	 *
	 * @return int|null
	 */
	public static function get_shop_page_association(): ?int {
		return function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : null;
	}


	/**
	 * Check is mapped cpt or not
	 *
	 * @param $key
	 *
	 * @return null|Mapping
	 */
	public static function is_mapped_cpt( $key ): ?Mapping {
		$taxonomies = get_object_taxonomies( get_post_type( $key ), 'objects' );

		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$terms = get_the_terms( $key, $taxonomy->name );
				if ( ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$mappings = Mapping::get_by_mapping_value( 'term', $term->term_id );
						if ( ! empty( $mappings ) ) {

							return $mappings[0];
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Redirect to specified url
	 *
	 * @param string|null $url
	 *
	 * @return false
	 */
	public static function redirect_to( ?string $url ): bool {
		if ( empty( $url ) || ( class_exists( 'DMS\\Includes\\Integrations\\Seo_Yoast' ) && Seo_Yoast::get_instance()->is_sitemap_requested() ) ) {
			return false;
		}
		wp_redirect( $url );
		exit();
	}

	/**
	 * Generates url from host and path
	 *
	 * @param string|null $host
	 * @param string|null $path
	 *
	 * @return string
	 */
	public static function generate_url( ?string $host, ?string $path ): string {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$path   = ! empty( $path ) ? $path . '/' : '';

		return $scheme . $host . '/' . $path;
	}

	/**
	 * Prepares class name
	 *
	 * @param $separator
	 * @param $name
	 *
	 * @return string
	 */
	public static function prepare_class_name( $separator, $name ) {
		$name = explode( $separator, $name );
		$name = array_map( 'ucfirst', $name );

		return implode( '_', $name );
	}

	/**
	 * Checks if active theme is Divi
	 * 
	 * @return bool
	 */
	public static function active_theme_is_divi() {
		return function_exists('wp_get_theme') && ( wp_get_theme()->get( 'Name' ) === 'Divi' || ( wp_get_theme()->parent() && wp_get_theme()->parent()->get( 'Name' ) === 'Divi' ) );
	}
}