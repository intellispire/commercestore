/**
 * Internal dependencies
 */
import { updatePaymentMethodForm } from './update-payment-method';

export function setup() {
	if ( ! document.getElementById( 'csx-update-payment-method' ) ) {
		return;
	}

	updatePaymentMethodForm();
}
