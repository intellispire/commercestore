<?php
/**
 * 3.0 Data Migration - Base.
 *
 * @subpackage  Admin/Upgrades/v3
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

namespace CS\Admin\Upgrades\v3;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Base Class.
 *
 * @since 3.0
 */
class Base extends \CS_Batch_Export {

	/**
	 * Orders.
	 *
	 * @since 3.0
	 * @var   string
	 */
	const ORDERS = 'orders';

	/**
	 * Discounts.
	 *
	 * @since 3.0
	 * @var   string
	 */
	const DISCOUNTS = 'discounts';

	/**
	 * Our export type. Used for export-type specific filters/actions.
	 *
	 * @since 3.0
	 * @var   string
	 */
	public $export_type = '';

	/**
	 * Allows for a non-download batch processing to be run.
	 *
	 * @since 3.0
	 * @var   bool
	 */
	public $is_void = true;

	/**
	 * Sets the number of items to pull on each step.
	 *
	 * @since 3.0
	 * @var   int
	 */
	public $per_step = 50;

	/**
	 * Is the upgrade done?
	 *
	 * @since 3.0
	 * @var   bool
	 */
	public $done;

	/**
	 * Message.
	 *
	 * @since 3.0
	 * @var   string
	 */
	public $message;

	/**
	 * Completed message.
	 *
	 * @since 3.0
	 * @var   string
	 */
	public $completed_message;

	/**
	 * Upgrade routine.
	 *
	 * @since 3.0
	 * @var   string
	 */
	public $upgrade;

	/**
	 * Retrieve the data pertaining to the current step and migrate as necessary.
	 *
	 * @since 3.0
	 *
	 * @return bool True if data was migrated, false otherwise.
	 */
	public function get_data() {
		return false;
	}

	/**
	 * Process a step.
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function process_step() {
		if ( ! $this->can_export() ) {
			wp_die(
				esc_html__( 'You do not have permission to run this upgrade.', 'commercestore' ),
				esc_html__( 'Error', 'commercestore' ),
				array(
					'response' => 403,
				)
			);
		}

		$had_data = $this->get_data();

		if ( $had_data ) {
			$this->done = false;
			// Save the *next* step to do.
			update_option( sprintf( 'cs_v3_migration_%s_step', sanitize_key( $this->upgrade ) ), $this->step + 1 );
			return true;
		} else {
			$this->done    = true;
			$this->message = $this->completed_message;
			cs_set_upgrade_complete( $this->upgrade );
			delete_option( sprintf( 'cs_v3_migration_%s_step', sanitize_key( $this->upgrade ) ) );
			return false;
		}
	}

	/**
	 * Set the headers.
	 *
	 * @since 3.0
	 */
	public function headers() {
		cs_set_time_limit();
	}

	/**
	 * Perform the migration.
	 *
	 * @since 3.0
	 *
	 * @return void
	 */
	public function export() {

		// Set headers.
		$this->headers();

		cs_die();
	}

	/**
	 * Return the global database interface.
	 *
	 * @since  3.0
	 * @access protected
	 * @static
	 *
	 * @return \wpdb|\stdClass
	 */
	protected static function get_db() {
		return isset( $GLOBALS['wpdb'] )
			? $GLOBALS['wpdb']
			: new \stdClass();
	}

	/**
	 * Set properties specific to the export.
	 *
	 * @since 3.0
	 *
	 * @param array $request Form data passed into the batch processor.
	 */
	public function set_properties( $request ) {
	}

	/**
	 * Allow for pre-fetching of data for the remainder of the batch processor.
	 *
	 * @since 3.0
	 */
	public function pre_fetch() {
	}
}
