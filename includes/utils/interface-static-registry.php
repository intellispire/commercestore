<?php
/**
 * Static_Registry interface
 *
 * @package     CS
 * @subpackage  Interfaces/Utilities
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace CS\Utils;

/**
 * Defines the contract for a static (singleton) registry object.
 *
 * @since 3.0
 */
interface Static_Registry {

	/**
	 * Retrieves the one true registry instance.
	 *
	 * @since 3.0
	 *
	 * @return \CS\Utils\Static_Registry Registry instance.
	 */
	public static function instance();

}