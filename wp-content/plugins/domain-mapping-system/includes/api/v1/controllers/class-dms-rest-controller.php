<?php

namespace DMS\Includes\Api\V1\Controllers;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;

abstract class Rest_Controller extends WP_REST_Controller {
	/**
	 * Namespace of DMS Rest controller
	 *
	 * @var string
	 */
	protected $namespace = 'dms/v1';

	/**
	 * Check is the nonce verified
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function nonce_is_verified( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}
}