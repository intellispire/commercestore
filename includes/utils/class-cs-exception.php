<?php
/**
 * Namespaced exception object for CS
 *
 * @package     CS
 * @subpackage  Classes/Utilities/Exceptions
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace CS\Utils;

/**
 * Implements a namespaced CS-specific exception object.
 *
 * Implements the CS_Exception marker interface to make it easier to catch
 * CS-specific exceptions under one umbrella.
 *
 * @since 3.0
 *
 * @see \Exception
 * @see \CS_Exception
 */
class Exception extends \Exception implements \CS_Exception {}
