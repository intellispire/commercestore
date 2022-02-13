<?php
/**
 * Endpoint for API v3
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, CommerceStore
 * @license   GPL2+
 * @since     2.11.4
 */

namespace CS\API\v3;

abstract class Endpoint {

	public static $namespace = 'cs/v3';

	/**
	 * Registers the endpoint(s).
	 *
	 * @since 2.11.4
	 *
	 * @return void
	 */
	abstract public function register();

}
