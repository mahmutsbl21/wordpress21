<?php

namespace DMS\Includes\Frontend\Services;

use DMS\Includes\Utils\Helper;

class Request_Params {

	/**
	 * Domain of the current page
	 *
	 * @var string
	 */
	public string $domain;

	/**
	 * Query string of the current page
	 *
	 * @var string
	 */
	public string $query_string;

	/**
	 * Pagination path of the current page
	 *
	 * @var null|string
	 */
	public ?string $pagination_path = null;

	/**
	 * Base host of the current page
	 *
	 * @var string
	 */
	public string $base_host;

	/**
	 * The path of the current page
	 *
	 * @var string
	 */
	public string $base_path;

	/**
	 * The path of the current page
	 *
	 * @var string
	 */
	public string $path = '';

	/**
	 * Whether website is subdirectory installation
	 *
	 * @var bool
	 */
	public bool $is_subdirectory_install = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->define_current_domain();
		$this->define_base_domain();
	}

	/**
	 * Set current domain and path
	 *
	 * @return void
	 */
	public function define_current_domain(): void {
		$request_uri = $_SERVER['REQUEST_URI'];
		if ( str_contains( $request_uri, '//' ) ) {
			$request_uri = str_replace( '//', '/', $_SERVER['REQUEST_URI'] );
		}
		$url_parsed = ! empty( $request_uri ) ? wp_parse_url( $request_uri ) : null;
		if ( ! empty( $url_parsed['query'] ) ) {
			$this->set_query_string( $url_parsed['query'] );
		}
		if ( ! empty( $url_parsed['path'] ) ) {
			$this->set_path( trim( $url_parsed['path'], '/' ) );
			if ( preg_match( '/page\/([0-9]+)\/?$/', $this->get_path(), $matches ) ) {
				$this->set_pagination_path( trim( $matches[0], '/' ) );
				$this->set_path( trim( preg_replace( '/page\/([0-9]+)\/?$/', '', $this->get_path(), 1 ), '/' ) );
			}
		}
		$this->set_domain( ! empty( $_SERVER['HTTP_HOST'] ) ? trim( $_SERVER['HTTP_HOST'], '/' ) : ( ! empty( $_SERVER['SERVER_NAME'] ) ? trim( $_SERVER['SERVER_NAME'], '/' ) : '' ) );
	}

	/**
	 * Define base domain
	 *
	 * @return void
	 */
	public function define_base_domain() {
		$this->set_base_path( Helper::get_base_path() );
		$this->set_base_host( Helper::get_base_host() );
		$this->set_is_subdirectory_install( ! empty( $this->get_base_path() ) );
	}

	/**
	 * Set base path
	 *
	 * @param  string  $base_path
	 *
	 * @return void
	 */
	public function set_base_path( string $base_path ): void {
		$this->base_path = $base_path;
	}

	/**
	 * Get base path
	 *
	 * @return string
	 */
	public function get_base_path(): string {
		return $this->base_path;
	}

	/**
	 * Get current domain
	 *
	 * @return string
	 */
	public function get_domain(): string {
		return $this->domain;
	}

	/**
	 * Set current domain
	 *
	 * @param  string  $domain  The domain which must be set
	 *
	 * @return void
	 */
	public function set_domain( string $domain ): void {
		$this->domain = $domain;
	}

	/**
	 * Get current path
	 *
	 * @return string
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Set current path
	 *
	 * @param  string  $path  The path which must be set
	 *
	 * @return void
	 */
	public function set_path( string $path ): void {
		$this->path = $path;
	}

	/**
	 * Get the base host
	 *
	 * @return string
	 */
	public function get_base_host(): string {
		return $this->base_host;
	}

	/**
	 * Set the base host
	 *
	 * @param  string  $base_host
	 *
	 * @return void
	 */
	public function set_base_host( $base_host ): void {
		$this->base_host = $base_host;
	}

	/**
	 * Set query string
	 *
	 * @param  string  $query_string
	 *
	 * @return void
	 */
	public function set_query_string( string $query_string ): void {
		$this->query_string = $query_string;
	}

	/**
	 * Set the pagination path
	 *
	 * @param  string  $pagination_path  The pagination path
	 *
	 * @return void
	 */
	public function set_pagination_path( string $pagination_path ): void {
		$this->pagination_path = $pagination_path;
	}

	/**
	 * Set the is_subdirectory install flag
	 *
	 * @return bool
	 */
	public function is_subdirectory_install(): bool {
		return $this->is_subdirectory_install;
	}

	/**
	 * Get is_subdirectory flag
	 *
	 * @param  bool  $is_subdirectory_install
	 *
	 * @return void
	 */
	public function set_is_subdirectory_install( bool $is_subdirectory_install ): void {
		$this->is_subdirectory_install = $is_subdirectory_install;
	}
}