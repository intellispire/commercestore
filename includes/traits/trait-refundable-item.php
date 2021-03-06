<?php
/**
 * Refundable Item
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.0
 */

namespace CS;

trait Refundable_Item {

	/**
	 * Refunded order items for this item.
	 *
	 * When this item has been refunded, matching item records are created and associated with the
	 * refund record. These are those items.
	 *
	 * @since 3.0
	 * @var null|array|false
	 */
	protected $refunded_items = null;

	/**
	 * Retrieves records that were refunded from this original item.
	 *
	 * @since 3.0
	 *
	 * @return array|false
	 */
	abstract public function get_refunded_items();

	/**
	 * The maximum amounts that can be refunded. This starts with the original item amounts, subtracts
	 * discounts, and subtracts what's already been refunded.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function get_refundable_amounts() {
		$subtotal = $this->subtotal;
		if ( ! empty( $this->discount ) ) {
			$subtotal -= $this->discount;
		}

		$maximums = array(
			'subtotal' => $subtotal,
			'tax'      => $this->tax,
			'total'    => $this->total,
		);

		$refunded_items = $this->get_refunded_items();

		if ( ! empty( $refunded_items ) ) {
			foreach ( $refunded_items as $refunded_item ) {
				// We're adding numbers here, because `$refund_item` has negative amounts already.
				$maximums['subtotal'] += $refunded_item->subtotal;
				$maximums['tax']      += $refunded_item->tax;
				$maximums['total']    += $refunded_item->total;
			}
		}

		$maximums['subtotal'] = number_format( $maximums['subtotal'], cs_currency_decimal_filter(), '.', '' );
		$maximums['tax']      = number_format( $maximums['tax'], cs_currency_decimal_filter(), '.', '' );
		$maximums['total']    = number_format( $maximums['total'], cs_currency_decimal_filter(), '.', '' );

		return $maximums;
	}

}
