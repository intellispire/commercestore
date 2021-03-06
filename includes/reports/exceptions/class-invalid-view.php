<?php
/**
 * Invalid_View exception class
 *
 * @package     CS
 * @subpackage  Classes/Utilities
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace CS\Reports\Exceptions;

use CS\Utils\Exceptions;

/**
 * Implements an Invalid_View exception thrown when a given
 * view is invalid.
 *
 * @since 3.0
 *
 * @see \InvalidArgumentException
 * @see \CS_Exception
 */
class Invalid_View extends Invalid_Parameter implements \CS_Exception {

	/**
	 * Type of value.
	 *
	 * @since 3.0
	 * @var   string
	 */
	public static $type = 'view';

}
