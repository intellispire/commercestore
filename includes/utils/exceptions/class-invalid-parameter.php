<?php
/**
 * Invalid_Parameter exception class
 *
 * @package     CS
 * @subpackage  Classes/Utilities
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace CS\Utils\Exceptions;

/**
 * Implements an Invalid_Argument exception thrown when a given
 * argument or parameter is invalid.
 *
 * @since 3.0
 *
 * @see \InvalidArgumentException
 * @see \CS_Exception
 */
class Invalid_Parameter extends Invalid_Argument implements \CS_Exception {

	/**
	 * Type of value.
	 *
	 * @since 3.0
	 * @var   string
	 */
	public static $type = 'parameter';

}
