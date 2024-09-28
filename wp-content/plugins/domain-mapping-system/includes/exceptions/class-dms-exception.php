<?php

namespace DMS\Includes\Exceptions;

use Exception;

/**
 * The exception class for mainly leaving user related errors
 *
 * @link       https://limb.dev
 * @since      1.0.0
 * @package    AI_Exception
 */
class DMS_Exception extends Exception {

	/**
	 * Sanitized error code.
	 *
	 * @var string
	 */
	protected string $error_code;

	/**
	 * Error extra data.
	 *
	 * @var array
	 */
	protected array $error_data;

	/**
	 * Setup exception.
	 *
	 * @param string $code Key
	 * @param string $message User friendly message
	 * @param array $data Error data.
	 */
	public function __construct( string $code, string $message, array $data = array() ) {
		$this->error_code = $code;
		$this->error_data = $data;
		parent::__construct( $message );
	}

	/**
	 * Returns the error code.
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Returns error data.
	 *
	 * @return array
	 */
	public function get_error_data(): array {
		return $this->error_data;
	}
}