<?php
/**
 * Currency Functions
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.0
 */

use CS\Currency\Currency;
use CS\Currency\Money_Formatter;

/**
 * Get Currencies
 *
 * @since 1.0
 * @return array $currencies A list of the available currencies
 */
function cs_get_currencies() {
	$currencies = array(
		'USD'  => __( 'US Dollars (&#36;)', 'commercestore' ),
		'EUR'  => __( 'Euros (&euro;)', 'commercestore' ),
		'GBP'  => __( 'Pound Sterling (&pound;)', 'commercestore' ),
		'AUD'  => __( 'Australian Dollars (&#36;)', 'commercestore' ),
		'BRL'  => __( 'Brazilian Real (R&#36;)', 'commercestore' ),
		'CAD'  => __( 'Canadian Dollars (&#36;)', 'commercestore' ),
		'CZK'  => __( 'Czech Koruna', 'commercestore' ),
		'DKK'  => __( 'Danish Krone', 'commercestore' ),
		'HKD'  => __( 'Hong Kong Dollar (&#36;)', 'commercestore' ),
		'HUF'  => __( 'Hungarian Forint', 'commercestore' ),
		'ILS'  => __( 'Israeli Shekel (&#8362;)', 'commercestore' ),
		'JPY'  => __( 'Japanese Yen (&yen;)', 'commercestore' ),
		'MYR'  => __( 'Malaysian Ringgits', 'commercestore' ),
		'MXN'  => __( 'Mexican Peso (&#36;)', 'commercestore' ),
		'NZD'  => __( 'New Zealand Dollar (&#36;)', 'commercestore' ),
		'NOK'  => __( 'Norwegian Krone', 'commercestore' ),
		'PHP'  => __( 'Philippine Pesos', 'commercestore' ),
		'PLN'  => __( 'Polish Zloty', 'commercestore' ),
		'SGD'  => __( 'Singapore Dollar (&#36;)', 'commercestore' ),
		'SEK'  => __( 'Swedish Krona', 'commercestore' ),
		'CHF'  => __( 'Swiss Franc', 'commercestore' ),
		'TWD'  => __( 'Taiwan New Dollars', 'commercestore' ),
		'THB'  => __( 'Thai Baht (&#3647;)', 'commercestore' ),
		'INR'  => __( 'Indian Rupee (&#8377;)', 'commercestore' ),
		'TRY'  => __( 'Turkish Lira (&#8378;)', 'commercestore' ),
		'RIAL' => __( 'Iranian Rial (&#65020;)', 'commercestore' ),
		'RUB'  => __( 'Russian Rubles', 'commercestore' ),
		'AOA'  => __( 'Angolan Kwanza', 'commercestore' ),
	);

	return apply_filters( 'cs_currencies', $currencies );
}

/**
 * Accepts an amount (ideally from the database, unmodified) and formats it
 * for display. The amount itself is formatted and the currency prefix/suffix
 * is applied and positioned.
 *
 * @since 3.0
 *
 * @param int|float|string $amount
 * @param string           $currency
 *
 * @return string
 */
function cs_display_amount( $amount, $currency ) {
	$formatter = new Money_Formatter( $amount, new Currency( $currency ) );

	return $formatter->format_for_display()
		->apply_symbol();
}

/**
 * Get the store's set currency
 *
 * @since 1.5.2
 * @return string The currency code
 */
function cs_get_currency() {
	$currency = cs_get_option( 'currency', 'USD' );
	return apply_filters( 'cs_currency', $currency );
}

/**
 * Given a currency determine the symbol to use. If no currency given, site default is used.
 * If no symbol is determined, the currency string is returned.
 *
 * @since  2.2
 *
 * @param string $currency The currency string
 *
 * @return string           The symbol to use for the currency
 */
function cs_currency_symbol( $currency = '' ) {
	if ( empty( $currency ) ) {
		$currency = cs_get_currency();
	}

	$currency = new Currency( $currency );

	return $currency->symbol;
}

/**
 * Get the name of a currency
 *
 * @since 2.2
 *
 * @param string $code The currency code
 *
 * @return string The currency's name
 */
function cs_get_currency_name( $code = 'USD' ) {
	$currencies = cs_get_currencies();
	$name       = isset( $currencies[ $code ] ) ? $currencies[ $code ] : $code;
	return apply_filters( 'cs_currency_name', $name );
}

