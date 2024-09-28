<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Setting;

class Setting_Repository {

	/**
	 * Create multiple settings
	 *
	 * @param array $data
	 *
	 * @return array array of created settings
	 */
	public function batch( array $data ):array {
		$settings = [];
		foreach ( $data as $params ) {
			$params['value'] = sanitize_text_field( $params['value'] );
			$settings[]      = Setting::create( $params );
		}

		return $settings;
	}
}