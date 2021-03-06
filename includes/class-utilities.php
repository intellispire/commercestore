<?php
/**
 * CommerceStore Utilities Bootstrap
 *
 * @package     CS
 * @subpackage  Utilities
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace CS;

use CS\Utils as Utils;
use CS\Reports as Reports;

/**
 * Class that bootstraps various utilities leveraged in CommerceStore core.
 *
 * @since 3.0
 */
class Utilities {

	/**
	 * Represents the WordPress GMT offset in seconds.
	 *
	 * @since 3.0
	 * @var   int
	 */
	private $gmt_offset = null;

	/**
	 * Represents the value of the WordPress 'date_format' option at run-time.
	 *
	 * @since 3.0
	 * @var   string
	 */
	private $date_format = null;

	/**
	 * Represents the value of the WordPress 'time_format' option at run-time.
	 *
	 * @since 3.0
	 * @var   string
	 */
	private $time_format = null;

	/**
	 * Represents the value of the WordPress time zone at run-time.
	 *
	 * @since 3.0
	 * @var   string
	 */
	private $time_zone = null;

	/**
	 * Sets up instantiating core utilities.
	 *
	 * @since 3.0
	 */
	public function __construct() {
		$this->set_gmt_offset();
		$this->set_date_format();
		$this->set_time_format();
		$this->set_time_zone();
		$this->includes();
	}

	/**
	 * Loads needed files for core utilities.
	 *
	 * @since 3.0
	 */
	private function includes() {
		$utils_dir = CS_PLUGIN_DIR . 'includes/utils/';

		// Interfaces.
		require_once $utils_dir . 'interface-static-registry.php';
		require_once $utils_dir . 'interface-error-logger.php';

		// Exceptions.
		require_once $utils_dir . 'class-cs-exception.php';
		require_once $utils_dir . 'exceptions/class-attribute-not-found.php';
		require_once $utils_dir . 'exceptions/class-invalid-argument.php';
		require_once $utils_dir . 'exceptions/class-invalid-parameter.php';

		// Date management.
		require_once $utils_dir . 'class-date.php';

		// Registry.
		require_once $utils_dir . 'class-registry.php';
	}

	/**
	 * Retrieves a given registry instance by name.
	 *
	 * @since 3.0
	 *
	 * @param string $name Registry name.
	 * @return \CS\Utils\Registry|\WP_Error The registry instance if it exists, otherwise a WP_Error..
	 */
	public function get_registry( $name ) {
		switch ( $name ) {
			case 'reports':
				if ( ! did_action( 'cs_reports_init' ) ) {
					_doing_it_wrong( __FUNCTION__, 'The Report registry cannot be retrieved prior to the cs_reports_init hook.', 'CS 3.0' );
				} elseif ( class_exists( '\CS\Reports\Data\Report_Registry' ) ) {
					$registry = Reports\Data\Report_Registry::instance();
				}
				break;

			case 'reports:endpoints':
				if ( ! did_action( 'cs_reports_init' ) ) {
					_doing_it_wrong( __FUNCTION__, 'The Endpoints registry cannot be retrieved prior to the cs_reports_init hook.', 'CS 3.0' );
				} elseif ( class_exists( '\CS\Reports\Data\Endpoint_Registry' ) ) {
					$registry = Reports\Data\Endpoint_Registry::instance();
				}
				break;

			case 'reports:endpoints:views':
				if ( ! did_action( 'cs_reports_init' ) ) {
					_doing_it_wrong( __FUNCTION__, 'The Endpoint Views registry cannot be retrieved prior to the cs_reports_init hook.', 'CS 3.0' );
				} elseif ( class_exists( '\CS\Reports\Data\Endpoint_View_Registry' ) ) {
					$registry = Reports\Data\Endpoint_View_Registry::instance();
				}
				break;

			default:
				$registry = new \WP_Error( 'invalid_registry', "The '{$name}' registry does not exist." );
				break;
		}

		return $registry;
	}

	/**
	 * Retrieves a date format string based on a given short-hand format.
	 *
	 * @see cs_get_date_format()
	 * @see cs_get_date_picker_format()
	 *
	 * @since 3.0
	 *
	 * @param string $format Shorthand date format string. Accepts 'date', 'time', 'mysql', 'datetime',
	 *                       'picker-field' or 'picker-js'. If none of the accepted values, the
	 *                       original value will simply be returned. Default is the value of the
	 *                       `$date_format` property, derived from the core 'date_format' option.
	 * @return string date_format()-compatible date format string.
	 */
	public function get_date_format_string( $format = 'date' ) {

		// Default to 'date' if empty
		if ( empty( $format ) ) {
			$format = 'date';
		}

		// Bail if format is not known
		if ( ! in_array( $format, array( 'date', 'time', 'datetime', 'mysql', 'date-attribute', 'date-js', 'date-mysql', 'time-mysql' ), true ) ) {
			return $format;
		}

		// What known format are we getting?
		switch ( $format ) {

			// jQuery UI Datepicker fields, placeholders, etc...
			case 'date-attribute':
				$retval = 'yyyy-mm-dd';
				break;

			// jQuery UI Datepicker JS variable
			case 'date-js':
				$retval = 'yy-mm-dd';
				break;

			// Date in MySQL format
			case 'date-mysql':
				$retval = 'Y-m-d';
				break;

			// Time in MySQL format
			case 'time-mysql':
				$retval = 'H:i:s';
				break;

			// MySQL datetime columns
			case 'mysql':
				$retval = 'Y-m-d H:i:s';
				break;

			// WordPress date_format + time_format
			case 'datetime':
				$retval = $this->get_date_format() . ' ' . $this->get_time_format();
				break;

			// WordPress time_format only
			case 'time':
				$retval = $this->get_time_format();
				break;

			// WordPress date_format only
			case 'date':
			default:
				$retval = $this->get_date_format();
				break;
		}

		return $retval;
	}

	/**
	 * Retrieves a date instance for the WP timezone (and offset) based on the given date string.
	 *
	 * @since 3.0
	 *
	 * @param string $date_string  Optional. Date string. Default 'now'.
	 * @param string $timezone     Optional. Timezone to generate the Carbon instance for.
	 *                             Default is the timezone set in WordPress settings.
	 * @param bool   $localize     Optional. Whether to apply the offset in seconds to the generated
	 *                             date. Default false.
	 *
	 * @return \CS\Utils\Date Date instance. Time is returned as UTC.
	 * @throws \Exception
	 */
	public function date( $date_string = 'now', $timezone = null, $localize = false ) {

		// Fallback to this time zone
		if ( null === $timezone && true === $localize ) {
			$timezone = $this->get_time_zone();
		} elseif ( null === $timezone && false === $localize ) {
			$timezone = 'UTC';
		}

		/*
		 * Create the DateTime object with the "local" WordPress timezone.
		 *
		 * Note that supplying the timezone during DateTime instantiation doesn't actually
		 * convert the UNIX timestamp, it just lays the groundwork for deriving the offset.
		 */
		$date = new Utils\Date( $date_string, new \DateTimezone( $timezone ) );

		if ( false === $localize ) {
			/*
			 * The offset is automatically applied when the Date object is instantiated.
			 *
			 * If $apply_offset is false, the interval needs to be removed again after the fact.
			 */
			$offset   = $date->getOffset();
			$interval = \DateInterval::createFromDateString( "-{$offset} seconds" );
			$date->add( $interval );
		}

		return $date;
	}

	/**
	 * Retrieves the WordPress GMT offset property, as cached at run-time.
	 *
	 * @since 3.0
	 *
	 * @param bool $refresh Optional. Whether to refresh the `$gmt_offset` value before retrieval.
	 *                      Default false.
	 * @return int Value of the gmt_offset property.
	 */
	public function get_gmt_offset( $refresh = false ) {
		if ( is_null( $this->gmt_offset ) || ( true === $refresh ) ) {
			$this->set_gmt_offset();
		}

		return $this->gmt_offset;
	}

	/**
	 * Retrieves the WordPress date format, as cached at run-time.
	 *
	 * @since 3.0
	 *
	 * @param bool $refresh Optional. Whether to refresh the `$gmt_offset` value before retrieval.
	 *                      Default false.
	 * @return string Value of the `$date_format` property.
	 */
	public function get_date_format( $refresh = false ) {
		if ( is_null( $this->date_format ) || ( true === $refresh ) ) {
			$this->set_date_format();
		}

		return $this->date_format;
	}

	/**
	 * Retrieves the WordPress time format, as cached at run-time.
	 *
	 * @since 3.0
	 *
	 * @param bool $refresh Optional. Whether to refresh the `$gmt_offset` value before retrieval.
	 *                      Default false.
	 * @return string Value of the `$time_format` property.
	 */
	public function get_time_format( $refresh = false ) {
		if ( is_null( $this->time_format ) || ( true === $refresh ) ) {
			$this->set_time_format();
		}

		return $this->time_format;
	}

	/**
	 * Retrieves the WordPress time zone, as cached at run-time.
	 *
	 * @since 3.0
	 *
	 * @param bool $refresh Optional. Whether to refresh the `$time_zone` value before retrieval.
	 *                      Default false.
	 * @return string Value of the `$time_zone` property.
	 */
	public function get_time_zone( $refresh = false ) {
		if ( is_null( $this->time_zone ) || ( true === $refresh ) ) {
			$this->set_time_zone();
		}

		return $this->time_zone;
	}

	/** Private Setters *******************************************************/

	/**
	 * Private setter for GMT offset
	 *
	 * @since 3.0
	 */
	private function set_gmt_offset() {
		$this->gmt_offset = get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;
	}

	/**
	 * Private setter for date format
	 *
	 * @since 3.0
	 */
	private function set_date_format() {
		$this->date_format = get_option( 'date_format', 'M j, Y' );
	}

	/**
	 * Private setter for time format
	 *
	 * @since 3.0
	 */
	private function set_time_format() {
		$this->time_format = get_option( 'time_format', 'g:i a' );
	}

	/**
	 * Private setter for time zone
	 *
	 * @since 3.0
	 */
	private function set_time_zone() {

		// Default return value
		$retval = 'UTC';

		// Get some useful values
		$timezone   = get_option( 'timezone_string' );
		$gmt_offset = $this->get_gmt_offset();

		// Use timezone string if it's available
		if ( ! empty( $timezone ) ) {
			$retval = $timezone;

		// Use GMT offset to calculate
		} elseif ( is_numeric( $gmt_offset ) ) {

			if ( version_compare( phpversion(), '5.5', '<' ) ) {

				/**
				 * In the event the user has PHP 5.3 or 5.4 and is using a GMT offset like "GMT-5"
				 * instead of a Country/City based timezone setting in the WordPress settings, we have to attempt a lookup
				 * of the string timezone since DateTimeZone doesn't support instantiation from a GMT offset in these versions of PHP.
				 *
				 * timezone_name_from_abbr allows us to look up a TimeZone string like "America/Chicago" from the offset, which
				 * will stop DateTimeZone from causing a fatal error in these circumstances.
				 */
				$is_dst = date( 'I' );
				$retval = timezone_name_from_abbr('', $gmt_offset, $is_dst );

			} else {

				$hours   = abs( floor( $gmt_offset / HOUR_IN_SECONDS ) );
				$minutes = abs( floor( ( $gmt_offset / MINUTE_IN_SECONDS ) % MINUTE_IN_SECONDS ) );
				$math    = ( $gmt_offset >= 0 ) ? '+' : '-';
				$value   = ! empty( $minutes )  ? "{$hours}:{$minutes}" : $hours;
				$retval  = "GMT{$math}{$value}";

			}

		}

		// Set
		$this->time_zone = $retval;
	}
}
