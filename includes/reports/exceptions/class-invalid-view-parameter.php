<?php
/**
 * Invalid_View_Parameter exception class
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
 * Implements an Invalid_View_Parameter exception thrown when a given
 * view parameter is invalid.
 *
 * @since 3.0
 *
 * @see Invalid_Parameter
 * @see \CS_Exception
 */
class Invalid_View_Parameter extends Invalid_Parameter implements \CS_Exception {

	/**
	 * Builds the Invalid_View_Parameter exception message.
	 *
	 * @since 3.0
	 *
	 * @param string     $argument_name Argument or parameter resulting in the exception.
	 * @param string     $method        Function or method name the argument or parameter was passed to.
	 * @return string Informed Invalid_Argument message.
	 */
	public static function build_message( $argument_name, $method, $context = null ) {
		self::$error_message = sprintf( 'The \'%1$s\' %2$s for the \'%3$s\' view is missing or invalid in \'%4$s\'.',
			$argument_name,
			static::$type,
			$context,
			$method
		);
	}
}
