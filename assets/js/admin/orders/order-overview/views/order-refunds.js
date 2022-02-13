/**
 * Internal dependencies
 */
import { OrderRefund } from './order-refund.js';

/**
 * Order refunds
 *
 * @since 3.0
 *
 * @class OrderRefunds
 * @augments wp.Backbone.View
 */
export const OrderRefunds = wp.Backbone.View.extend( {
	/**
	 * @since 3.0
	 */
	tagName: 'tbody',

	/**
	 * @since 3.0
	 */
	className: 'cs-order-overview-summary__refunds',

	/**
	 * @since 3.0
	 */
	template: wp.template( 'cs-admin-order-refunds' ),

	/**
	 * Renders initial view.
	 *
	 * @since 3.0
	 */
	render() {
		const { state } = this.options;
		const { models: refunds } = state.get( 'refunds' );

		_.each( refunds, ( model ) => (
			this.views.add(
				new OrderRefund( {
					...this.options,
					model,
				} )
			)
		) );
	},
} );
