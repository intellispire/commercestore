<?php
/**
 * Refund Details Sections
 *
 * @package    CS
 * @subpackage Admin/Orders
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      3.0
 */

use CS\Orders\Order;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Header ******************************************************************/

/**
 * Outputs Refund header region/notice of Refund vs. Order.
 *
 * @since 3.0
 *
 * @param Order $refund Current Refund.
 */
function cs_refund_details_notice( $refund ) {
	$order_url = cs_get_admin_url(
		array(
			'id'        => $refund->parent,
			'page'      => 'cs-payment-history',
			'view'      => 'view-order-details',
		)
	);
?>

<div class="edit-post-editor-regions__header">
	<div class="edit-post-header notice-warning" style="border-left-width: 4px; border-left-style: solid;">

		<div class="edit-post-header__settings">
			<a href="<?php echo esc_url( $order_url ); ?>" class="button button-secondary" autofocus>
				<?php esc_html_e( 'View Original Order', 'commercestore' ); ?>
			</a>
		</div>

		<div class="edit-post-header__toolbar">
			<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'A refund is a read-only record to help balance your store&#39;s books.', 'commercestore' ); ?>"></span>&nbsp;
			<?php esc_html_e( 'You are viewing a refund record.', 'commercestore' ); ?>
		</div>

	</div>
</div>

<?php
}

/** Main **********************************************************************/

/**
 * Outputs the Refund Items box.
 *
 * @since 3.0
 *
 * @param Order $refund Current Refund.
 */
function cs_refund_details_items( $refund ) {
	$_items       = array();
	$_adjustments = array();

	$items  = cs_get_order_items( array(
		'order_id' => $refund->id,
		'number'   => 999,
	) );

	foreach ( $items as $item ) {
		$item_adjustments = array();

		$adjustments = cs_get_order_adjustments( array(
			'object_id'   => $item->id,
			'number'      => 999,
			'object_type' => 'order_item',
			'type'        => array(
				'discount',
				'credit',
				'fee',
			),
		) );

		foreach ( $adjustments as $adjustment ) {
			// @todo cs_get_order_adjustment_to_json()?
			$adjustment_args = array(
				'id'           => esc_html( $adjustment->id ),
				'objectId'     => esc_html( $adjustment->object_id ),
				'objectType'   => esc_html( $adjustment->object_type ),
				'typeId'       => esc_html( $adjustment->type_id ),
				'type'         => esc_html( $adjustment->type ),
				'description'  => esc_html( $adjustment->description ),
				'subtotal'     => esc_html( $adjustment->subtotal ),
				'tax'          => esc_html( $adjustment->tax ),
				'total'        => esc_html( $adjustment->total ),
				'dateCreated'  => esc_html( $adjustment->date_created ),
				'dateModified' => esc_html( $adjustment->date_modified ),
				'uuid'         => esc_html( $adjustment->uuid ),
			);

			$item_adjustments[] = $adjustment_args;
			$_adjustments[]     = $adjustment_args;
		}

		// @todo cs_get_order_item_to_json()?
		$_items[] = array(
			'id'           => esc_html( $item->id ),
			'orderId'      => esc_html( $item->order_id ),
			'productId'    => esc_html( $item->product_id ),
			'productName'  => esc_html( $item->get_order_item_name() ),
			'priceId'      => esc_html( $item->price_id ),
			'cartIndex'    => esc_html( $item->cart_index ),
			'type'         => esc_html( $item->type ),
			'status'       => esc_html( $item->status ),
			'quantity'     => esc_html( $item->quantity ),
			'amount'       => esc_html( $item->amount ),
			'subtotal'     => esc_html( $item->subtotal ),
			'discount'     => esc_html( $item->discount ),
			'tax'          => esc_html( $item->tax ),
			'total'        => esc_html( $item->total ),
			'dateCreated'  => esc_html( $item->date_created ),
			'dateModified' => esc_html( $item->date_modified ),
			'uuid'         => esc_html( $item->uuid ),
		);
	}

	$adjustments  = cs_get_order_adjustments( array(
		'object_id'   => $refund->id,
		'number'      => 999,
		'object_type' => 'order',
		'type'        => array(
			'discount',
			'credit',
			'fee',
		),
	) );

	foreach ( $adjustments as $adjustment ) {
		// @todo cs_get_order_adjustment_to_json()?
		$_adjustments[] = array(
			'id'           => esc_html( $adjustment->id ),
			'objectId'     => esc_html( $adjustment->object_id ),
			'objectType'   => esc_html( $adjustment->object_type ),
			'typeId'       => esc_html( $adjustment->type_id ),
			'type'         => esc_html( $adjustment->type ),
			'description'  => esc_html( $adjustment->description ),
			'subtotal'     => esc_html( $adjustment->subtotal ),
			'tax'          => esc_html( $adjustment->tax ),
			'total'        => esc_html( $adjustment->total ),
			'dateCreated'  => esc_html( $adjustment->date_created ),
			'dateModified' => esc_html( $adjustment->date_modified ),
			'uuid'         => esc_html( $adjustment->uuid ),
		);
	}

	$has_tax  = 'none';
	$tax_rate = $refund->id ? $refund->get_tax_rate() : false;
	$location = array(
		'rate'    => $tax_rate,
		'country' => '',
		'region'  => '',
	);
	if ( $tax_rate ) {
		$has_tax         = $location;
		$has_tax['rate'] = $tax_rate;
		if ( $refund->tax_rate_id ) {
			$tax_rate_object = $refund->get_tax_rate_object();
			if ( $tax_rate_object ) {
				$has_tax['country'] = $tax_rate_object->name;
				$has_tax['region']  = $tax_rate_object->description;
			}
		}
	}

	wp_localize_script(
		'cs-admin-orders',
		'csAdminOrderOverview',
		array(
			'items'        => $_items,
			'adjustments'  => $_adjustments,
			'refunds'      => array(),
			'isAdding'     => false,
			'isRefund'     => true,
			'hasQuantity'  => true,
			'hasTax'       => $has_tax,
			'order'        => array(
				'currency'       => $refund->currency,
				'currencySymbol' => html_entity_decode( cs_currency_symbol( $refund->currency ) ),
				'subtotal'       => $refund->subtotal,
				'discount'       => $refund->discount,
				'tax'            => $refund->tax,
				'total'          => $refund->total,
			),
		)
	);

	$templates = array(
		'no-items',
		'subtotal',
		'tax',
		'total',
		'item',
		'adjustment',
		'adjustment-discount',
	);

	foreach ( $templates as $tmpl ) {
		echo '<script type="text/html" id="tmpl-cs-admin-order-' . esc_attr( $tmpl ) . '">';
		require_once CS_PLUGIN_DIR . 'includes/admin/views/tmpl-order-' . $tmpl . '.php';
		echo '</script>';
	}
?>

<div id="cs-order-overview" class="postbox cs-edit-purchase-element cs-order-overview">
	<table id="cs-order-overview-summary" class="widefat wp-list-table cs-order-overview-summary cs-order-overview-summary--refund">
		<thead>
			<tr>
				<th class="column-name column-primary"><?php echo esc_html( cs_get_label_singular() ); ?></th>
				<th class="column-amount"><?php esc_html_e( 'Unit Price', 'commercestore' ); ?></th>
				<th class="column-quantity"><?php esc_html_e( 'Quantity', 'commercestore' ); ?></th>
				<th class="column-subtotal column-right"><?php esc_html_e( 'Amount', 'commercestore' ); ?></th>
			</tr>
		</thead>
	</table>
</div>

<?php
}

/**
 * Outputs Refund Notes box.
 *
 * @since 3.0
 *
 * @param Order $refund Current Refund.
 */
function cs_refund_details_notes( $refund ) {
?>

<div id="cs-order-items" class="postbox cs-edit-purchase-element">
	<h2 class="hndle">
		<?php esc_html_e( 'Refund Notes', 'commercestore' ); ?>
	</h2>

	<div class="inside">
		<?php cs_order_details_notes( $refund ); ?>
	</div>
</div>

<?php
}

/**
 * Outputs "Refund Details" box.
 *
 * @since 3.0
 *
 * @param Order $refund Current Refund.
 */
function cs_refund_details_attributes( $refund ) {
	$refund_date = cs_get_cs_timezone_equivalent_date_from_utc( CS()->utils->date( $refund->date_created, 'utc', true ) );

	$trash_url = wp_nonce_url(
		cs_get_admin_url( array(
			'cs-action'  => 'trash_order',
			'purchase_id' => $refund->id,
			'order_type'  => 'refund',
			'page'        => 'cs-payment-history',
		) ),
		'cs_payment_nonce'
	);

	$order_url = cs_get_admin_url(
		array(
			'id'        => $refund->parent,
			'page'      => 'cs-payment-history',
			'view'      => 'view-order-details',
		)
	);

	$order = cs_get_order( $refund->parent );
?>

<div class="postbox">
	<h2 class="hndle">
		<?php esc_html_e( 'Refund Attributes', 'commercestore' ); ?>
	</h2>

	<div class="cs-admin-box-inside">
		<time datetime="<?php echo esc_attr( CS()->utils->date( $refund->date_created, null, true )->toDateTimeString() ); ?>" style="line-height: normal">
			<?php echo cs_date_i18n( $refund->date_created, 'M. d, Y' ) . '<br />' . cs_date_i18n( strtotime( $refund->date_created ), 'H:i' ); ?> <?php echo esc_html( cs_get_timezone_abbr() ); ?>
		</time>

		<br /><br />

		<a href="<?php echo esc_url( $trash_url ); ?>" class="cs-delete-payment cs-delete">
			<?php esc_html_e( 'Move to Trash', 'commercestore' ); ?>
		</a>
	</div>

	<div class="cs-admin-box-inside cs-admin-box-inside--row">
		<div class="cs-form-group">
			<span class="cs-form-group__label">
				<?php esc_html_e( 'Original Order', 'commercestore' ); ?>
			</span>
			<div class="cs-form-group__control">
				<a href="<?php echo esc_url( $order_url ); ?>"><?php echo esc_html( $order->number ); ?></a>
			</div>
		</div>
	</div>
</div>

<?php
}

/**
 * Outputs related Refunds.
 *
 * @since 3.0
 *
 * @param Order $refund
 */
function cs_refund_details_related_refunds( $refund ) {
	$refunds = array_filter(
		cs_get_order_refunds( $refund->parent ),
		function( $related_refund ) use ( $refund ) {
			return $related_refund->id !== $refund->id;
		}
	);

	if ( empty( $refunds ) ) {
		return;
	}
?>

<div class="postbox cs-order-data">
	<h2 class="hndle">
		<?php esc_html_e( 'Related Refunds', 'commercestore' ); ?>
	</h2>

	<?php
	foreach( $refunds as $refund ) :
		$refund_url = cs_get_admin_url( array(
			'page' => 'cs-payment-history',
			'view' => 'view-refund-details',
			'id'   => $refund->id
		) );
	?>
		<div class="cs-admin-box-inside">
			<div class="cs-form-group">
				<a href="<?php echo esc_url( $refund_url ); ?>" class="cs-form-group__label">
					<?php echo esc_html( $refund->number ); ?>
				</a>
				<div class="cs-form-group__control">
					<time datetime="<?php echo esc_attr( CS()->utils->date( $refund->date_created, null, true )->toDateTimeString() ); ?>" style="line-height: normal">
						<?php echo cs_date_i18n( $refund->date_created, 'M. d, Y' ) . '<br />' . cs_date_i18n( strtotime( $refund->date_created ), 'H:i' ); ?> <?php echo esc_html( cs_get_timezone_abbr() ); ?>
					</time>
				</div>
		</div>
	<?php endforeach; ?>
</div>

<?php
}
