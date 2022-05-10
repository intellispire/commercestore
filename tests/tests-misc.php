<?php


/**
 * @group cs_misc
 * @group cs_functions
 */
class Test_Misc extends CS_UnitTestCase {

	/**
	 * Download fixture.
	 */
	protected static $download;

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$download = CS_Helper_Download::create_simple_download();
	}

	public function set_up() {
		parent::set_up();
	}

	public function test_test_mode() {
		$this->assertFalse( cs_is_test_mode() );
	}

	public function test_guest_checkout() {
		$this->assertFalse( cs_no_guest_checkout() );
	}

	public function test_logged_in_only() {
		$this->assertFalse( cs_logged_in_only() );
	}

	public function test_straight_to_checkout() {
		$this->assertFalse( cs_straight_to_checkout() );
	}

	public function test_no_redownload() {
		$this->assertFalse( cs_no_redownload() );
	}

	public function test_is_cc_verify_enabled() {
		$this->assertTrue( cs_is_cc_verify_enabled() );
	}

	public function test_is_odd() {
		$this->assertTrue( cs_is_odd( 3 ) );
		$this->assertFalse( cs_is_odd( 4 ) );
	}

	public function test_get_file_extension() {
		$this->assertEquals( 'php', cs_get_file_extension( 'file.php' ) );
	}

	public function test_get_file_extension_with_query_string() {
		$this->assertEquals( 'pdf', cs_get_file_extension( 'file.pdf?test=1' ) );
	}

	public function test_string_is_image_url() {
		$this->assertTrue( cs_string_is_image_url( 'jpg' ) );
		$this->assertTrue( cs_string_is_image_url( 'webp' ) );
		$this->assertFalse( cs_string_is_image_url( 'php' ) );
	}

	public function test_get_ip() {
		$this->assertEquals( '127.0.0.1', cs_get_ip() );

		$_SERVER['REMOTE_ADDR'] = '172.217.6.46';
		$this->assertEquals( '172.217.6.46', cs_get_ip() );

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	}

	public function test_get_ip_reverse_proxies() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '123.123.123.123, 10.0.0.2';
		$this->assertEquals( '123.123.123.123', cs_get_ip() );
		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
	}

	public function test_get_ip_reverse_proxy() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '123.123.123.123';
		$this->assertEquals( '123.123.123.123', cs_get_ip() );
		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
	}


	public function test_get_currencies() {
		$expected = array(
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

		$this->assertEquals( $expected, cs_get_currencies() );

	}

	public function test_get_countries() {
		$expected = array(
			''   => '',
			'US' => 'United States',
			'CA' => 'Canada',
			'GB' => 'United Kingdom',
			'AF' => 'Afghanistan',
			'AX' => '&#197;land Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BQ' => 'Bonaire, Saint Eustatius and Saba',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darrussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CD' => 'Congo, Democratic People\'s Republic',
			'CG' => 'Congo, Republic of',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => 'Cote d\'Ivoire',
			'HR' => 'Croatia/Hrvatska',
			'CU' => 'Cuba',
			'CW' => 'Cura&Ccedil;ao',
			'CY' => 'Cyprus',
			'CZ' => 'Czechia',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'TP' => 'East Timor',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'GQ' => 'Equatorial Guinea',
			'SV' => 'El Salvador',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GR' => 'Greece',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard and McDonald Islands',
			'VA' => 'Holy See (City Vatican State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libyan Arab Jamahiriya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macau',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia',
			'MD' => 'Moldova, Republic of',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'KP' => 'North Korea',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian Territories',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn Island',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'XK' => 'Republic of Kosovo',
			'RE' => 'Reunion Island',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barth&eacute;lemy',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin (French)',
			'SX' => 'Saint Martin (Dutch)',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'SM' => 'San Marino',
			'ST' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovak Republic',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia',
			'KR' => 'South Korea',
			'SS' => 'South Sudan',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen Islands',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'UY' => 'Uruguay',
			'UM' => 'US Minor Outlying Islands',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'VG' => 'Virgin Islands (British)',
			'VI' => 'Virgin Islands (USA)',
			'WF' => 'Wallis and Futuna Islands',
			'EH' => 'Western Sahara',
			'WS' => 'Western Samoa',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe'
		);

		$this->assertEquals( $expected, cs_get_country_list() );
	}

	public function test_states_list() {
		$expected = array(
			''   => '',
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'District of Columbia',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
			'AS' => 'American Samoa',
			'CZ' => 'Canal Zone',
			'CM' => 'Commonwealth of the Northern Mariana Islands',
			'FM' => 'Federated States of Micronesia',
			'GU' => 'Guam',
			'MH' => 'Marshall Islands',
			'MP' => 'Northern Mariana Islands',
			'PW' => 'Palau',
			'PI' => 'Philippine Islands',
			'PR' => 'Puerto Rico',
			'TT' => 'Trust Territory of the Pacific Islands',
			'VI' => 'Virgin Islands',
			'AA' => 'Armed Forces - Americas',
			'AE' => 'Armed Forces - Europe, Canada, Middle East, Africa',
			'AP' => 'Armed Forces - Pacific',
		);

		$this->assertEquals( $expected, cs_get_states_list() );
	}

	public function test_provinces_list() {
		$expected = array(
			''   => '',
			'AB' => 'Alberta',
			'BC' => 'British Columbia',
			'MB' => 'Manitoba',
			'NB' => 'New Brunswick',
			'NL' => 'Newfoundland and Labrador',
			'NS' => 'Nova Scotia',
			'NT' => 'Northwest Territories',
			'NU' => 'Nunavut',
			'ON' => 'Ontario',
			'PE' => 'Prince Edward Island',
			'QC' => 'Quebec',
			'SK' => 'Saskatchewan',
			'YT' => 'Yukon',
		);

		$this->assertEquals( $expected, cs_get_provinces_list() );
	}

	public function test_angola_provinces_list() {
		$expected = array(
			''    => '',
			'BGO' => 'Bengo',
			'BGU' => 'Benguela',
			'BIE' => 'Bié',
			'CAB' => 'Cabinda',
			'CNN' => 'Cunene',
			'HUA' => 'Huambo',
			'HUI' => 'Huíla',
			'CCU' => 'Kuando Kubango', // Cuando Cubango
			'CNO' => 'Kwanza-Norte', // Cuanza Norte
			'CUS' => 'Kwanza-Sul', // Cuanza Sul
			'LUA' => 'Luanda',
			'LNO' => 'Lunda-Norte', // Lunda Norte
			'LSU' => 'Lunda-Sul', // Lunda Sul
			'MAL' => 'Malanje', // Malanje
			'MOX' => 'Moxico',
			'NAM' => 'Namibe',
			'UIG' => 'Uíge',
			'ZAI' => 'Zaire'
		);

		$this->assertSame( $expected, cs_get_angola_provinces_list() );
	}

	public function test_netherlands_provinces_list() {
		$expected = array(
			''   => '',
			'DR' => __( 'Drenthe', 'commercestore' ),
			'FL' => __( 'Flevoland', 'commercestore' ),
			'FR' => __( 'Friesland', 'commercestore' ),
			'GE' => __( 'Gelderland', 'commercestore' ),
			'GR' => __( 'Groningen', 'commercestore' ),
			'LI' => __( 'Limburg', 'commercestore' ),
			'NB' => __( 'North Brabant', 'commercestore' ),
			'NH' => __( 'North Holland', 'commercestore' ),
			'OV' => __( 'Overijssel', 'commercestore' ),
			'ZH' => __( 'South Holland', 'commercestore' ),
			'UT' => __( 'Utrecht', 'commercestore' ),
			'ZE' => __( 'Zeeland', 'commercestore' ),
			'BO' => __( 'Bonaire', 'commercestore' ),
			'SA' => __( 'Saba', 'commercestore' ),
			'SE' => __( 'Sint Eustatius', 'commercestore' ),
		);

		$this->assertSame( $expected, cs_get_netherlands_provinces_list() );
	}

	public function test_month_num_to_name() {
		$this->assertEquals( 'Jan', cs_month_num_to_name( 1 ) );
	}

	/**
	 * @covers ::cs_month_num_to_name()
	 */
	public function test_month_num_to_long_name() {
		$this->assertEquals( 'January', cs_month_num_to_name( 1, true ) );
	}

	public function test_get_php_arg_separator_output() {
		$this->assertEquals( '&', cs_get_php_arg_separator_output() );
	}

	public function test_let_to_num() {
		$this->assertEquals( 0, cs_let_to_num( WP_MEMORY_LIMIT ) / ( 1024*1024 ) );
	}

	/**
	 * @covers ::cs_get_symlink_dir
	 */
	public function test_get_symlink_url() {
		$this->assertEquals( 'http://example.org/wp-content/uploads/cs/symlinks', cs_get_symlink_url() );
	}

	public function test_use_skus() {
		$this->assertFalse( cs_use_skus() );
	}

	public function test_cs_is_host() {
		$this->assertFalse( cs_is_host( 'wpengine' ) );
		$this->assertFalse( cs_is_host( 'wp engine' ) );
		$this->assertFalse( cs_is_host( 'WP Engine' ) );
		$this->assertFalse( cs_is_host( 'WPEngine' ) );

		define( 'WPE_APIKEY', 'testkey' );

		$this->assertTrue( cs_is_host( 'wpengine' ) );
		$this->assertTrue( cs_is_host( 'wp engine' ) );
		$this->assertTrue( cs_is_host( 'WP Engine' ) );
		$this->assertTrue( cs_is_host( 'WPEngine' ) );
	}

	public function test_cs_update_option(){
		$key   = 'some-setting';
		$value = 'some-value';
		$isset = cs_get_option( $key, false );

		// The option shouldn't exist
		$this->assertFalse( $isset );

		$updated = cs_update_option( $key, $value );

		// The option should have successfully updated
		$this->assertTrue( $updated );

		// The option retrieve should be equal to the one we set
		$this->assertEquals( $value, cs_get_option( $key, false ) );

		$key   = 'some-setting2';
		$value = null;
		$isset = cs_get_option( $key, false );

		// The option shouldn't exist
		$this->assertFalse( $isset );

		$updated = cs_update_option( $key, $value );

		// The option should return false due to the null value
		$this->assertFalse( $updated );

		// The option retrieve should be false since it doesn't exist
		$this->assertFalse( cs_get_option( $key, false ) );

	}

	public function test_add_cache_busting() {
		add_filter( 'cs_is_caching_plugin_active', '__return_true' );
		$this->assertEquals( 'http://example.org/?nocache=true', cs_add_cache_busting( home_url( '/') ) );
		remove_filter( 'cs_is_caching_plugin_active', '__return_true' );
		$this->assertEquals( 'http://example.org/', cs_add_cache_busting( home_url( '/' ) ) );
	}

	/**
	 * @covers ::cs_get_current_page_url()
	 */
	public function test_get_current_page_url_if_home_should_return_home_url() {
		$this->go_to( home_url( '/' ) );
		$this->assertEquals( 'http://example.org/', cs_get_current_page_url() );
	}

	/**
	 * @covers ::cs_get_current_page_url()
	 */
	public function test_get_current_page_url_if_a_download_page_should_return_that_url() {
		$this->go_to( get_permalink( self::$download->ID ) );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/?download=test-download-product', cs_get_current_page_url() );
	}

	/**
	 * @covers ::cs_get_current_page_url()
	 */
	public function test_get_current_page_url_if_no_caching_should_return_url_with_nocache_true() {
		add_filter( 'cs_is_caching_plugin_active', '__return_true' );

			$this->go_to( get_permalink( self::$download->ID ) );

			$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/?download=test-download-product&nocache=true', cs_get_current_page_url( true ) );

		remove_filter( 'cs_is_caching_plugin_active', '__return_true' );
	}

	/**
	 * @covers ::cs_get_current_page_url()
	 */
	public function test_get_current_page_url_if_no_cache_checkout_then_current_url_should_match() {
		global $cs_options;

		add_filter( 'cs_is_caching_plugin_active', '__return_true' );

			$cs_options['no_cache_checkout'] = true;

			$this->go_to( get_permalink( $cs_options['purchase_page'] ) );

			$this->assertEquals( cs_get_checkout_uri(), cs_get_current_page_url( true ) );

		remove_filter( 'cs_is_caching_plugin_active', '__return_true' );
	}

	public function test_cart_url_formats() {
		global $cs_options;
		$post = CS_Helper_Download::create_simple_download();

		cs_add_to_cart( $post->ID );

		$this->assertTrue( cs_item_in_cart( $post->ID ) );

		$item_position = cs_get_item_position_in_cart( $post->ID );

		// Go to checkout
		$this->go_to( cs_get_checkout_uri() );

		add_filter( 'cs_is_caching_plugin_active', '__return_true' );

		$remove_url = cs_remove_item_url( $item_position );

		$this->assertStringContainsString( 'page_id=' . $cs_options['purchase_page'], $remove_url );
		$this->assertStringContainsString( 'cs_action=remove', $remove_url );
		$this->assertStringContainsString( 'nocache=true', $remove_url );
		$this->assertStringContainsString( 'cart_item=' . $item_position, $remove_url );

		remove_filter( 'cs_is_caching_plugin_active', '__return_true' );
		unset( $cs_options['no_cache_checkout'] );
		$remove_url = cs_remove_item_url( $item_position );

		$this->assertStringContainsString( 'page_id=' . $cs_options['purchase_page'], $remove_url );
		$this->assertStringContainsString( 'cs_action=remove', $remove_url );
		$this->assertStringContainsString( 'cart_item=' . $item_position, $remove_url );
		$this->assertStringNotContainsString( 'nocache=true', $remove_url );

		// Go home and test again
		$this->go_to( home_url( '/' ) );

		add_filter( 'cs_is_caching_plugin_active', '__return_true' );

		$expected_url = 'http://example.org/?cart_item=' . $item_position . '&cs_action=remove&nocache=true';
		$remove_url   = cs_remove_item_url( $item_position );

		$this->assertStringNotContainsString( 'page_id=', $remove_url );
		$this->assertStringContainsString( 'cs_action=remove', $remove_url );
		$this->assertStringContainsString( 'cart_item=' . $item_position, $remove_url );
		$this->assertStringContainsString( 'nocache=true', $remove_url );

		remove_filter( 'cs_is_caching_plugin_active', '__return_true' );

		$remove_url = cs_remove_item_url( $item_position );

		$this->assertStringNotContainsString( 'page_id=', $remove_url );
		$this->assertStringContainsString( 'cs_action=remove', $remove_url );
		$this->assertStringContainsString( 'cart_item=' . $item_position, $remove_url );
		$this->assertStringNotContainsString( 'nocache=true', $remove_url );

		// Go home and test again
		$this->go_to( home_url( '/' ) );

		add_filter( 'cs_is_caching_plugin_active', '__return_true' );

		$expected_url  = 'http://example.org/?cart_item=' . $item_position . '&cs_action=remove&nocache=true';
		$remove_url    = cs_remove_item_url( $item_position );

		$this->assertEquals( $expected_url, $remove_url );
		remove_filter( 'cs_is_caching_plugin_active', '__return_true' );

		$remove_url    = cs_remove_item_url( $item_position );
		$expected_url  = 'http://example.org/?cart_item=' . $item_position . '&cs_action=remove';

		CS_Helper_Download::delete_download( $post->ID );
	}

	public function test_array_convert() {
		$customer1_id = cs_add_customer( array( 'email' => 'test10@example.com' ) );

		// Test sending a single object in
		$customer_object = new CS_Customer( $customer1_id );
		$customer_array  = cs_object_to_array( $customer_object );
		$this->assertInternalType( 'array', $customer_array );
		$this->assertEquals( $customer_object->id, $customer_array['id'] );
		$this->assertEquals( $customer_object->email, $customer_array['email'] );
		$this->assertEquals( $customer_object->purchase_count, $customer_array['purchase_count'] );

		// Negative tests (no alterations should occur)
		$this->assertEquals( 'string', cs_object_to_array( 'string' ) );
		$this->assertEquals( array( 'foo', 'bar', 'baz' ), cs_object_to_array( array( 'foo', 'bar', 'baz' ) ) );

		// Test sending in an array of objects
		$customers = cs_get_customers();
		$converted = cs_object_to_array( $customers );
		$this->assertInternalType( 'array', $converted[0] );

		// Test payments
		$payment_1 = CS_Helper_Payment::create_simple_payment();
		$payment_2 = CS_Helper_Payment::create_simple_payment();

		$payment_1_obj = new CS_Payment( $payment_1 );
		$payment_2_obj = new CS_Payment( $payment_2 );

		// Test a single convert
		$payment_1_array = cs_object_to_array( $payment_1_obj );
		$this->assertInternalType( 'array',  $payment_1_array );
		$this->assertEquals( $payment_1_obj->ID, $payment_1_array['ID'] );

		$payments = array(
			$payment_1_obj,
			$payment_2_obj,
		);

		$payments_array = cs_object_to_array( $payments );
		$this->assertInternalType( 'array', $payments_array[0] );
		$this->assertEquals( 2, count( $payments_array ) );
	}

	// Test getting currency symols:
	function test_gbp_symbol() {
		$this->assertEquals( '&pound;', cs_currency_symbol( 'GBP' ) );
	}

	function test_brl_symbol() {
		$this->assertEquals( 'R&#36;', cs_currency_symbol( 'BRL' ) );
	}

	function test_us_dollar_symbol() {
		$this->assertEquals( '&#36;', cs_currency_symbol( 'USD' ) );
	}

	function test_au_dollar_symbol() {
		$this->assertEquals( '&#36;', cs_currency_symbol( 'AUD' ) );
	}

	function test_nz_dollar_symbol() {
		$this->assertEquals( '&#36;', cs_currency_symbol( 'NZD' ) );
	}

	function test_ca_dollar_symbol() {
		$this->assertEquals( '&#36;', cs_currency_symbol( 'CAD' ) );
	}

	function test_hk_dollar_symbol() {
		$this->assertEquals( '&#36;', cs_currency_symbol( 'HKD' ) );
	}

	function test_mx_dollar_symbol() {
		$this->assertEquals( '&#36;', cs_currency_symbol( 'MXN' ) );
	}

	function test_sg_dollar_symbol() {
		$this->assertEquals( '&#36;', cs_currency_symbol( 'SGD' ) );
	}

	function test_yen_symbol() {
		$this->assertEquals( '&yen;', cs_currency_symbol( 'JPY' ) );
	}

	function test_aoa_symbol() {
		$this->assertEquals( 'Kz', cs_currency_symbol( 'AOA' ) );
	}

	function test_default_symbol() {
		$this->assertEquals( 'CZK', cs_currency_symbol( 'CZK' ) );
	}

	function test_country_name_blank() {
		$this->assertSame( '', cs_get_country_name( '' ) );
	}

	function test_country_name_us() {
		$this->assertSame( 'United States', cs_get_country_name( 'US' ) );
	}

	function test_cs_delete_option() {
		cs_update_option( 'test_setting', 'testing' );
		cs_delete_option( 'test_setting' );

		$this->assertFalse( cs_get_option( 'test_setting' ) );
	}

	function test_should_allow_file_download_cs_uploaded_file_url() {
		$file_details   = array ( 'scheme' => 'https', 'host' => site_url(), 'path' => '/wp-content/uploads/cs/2019/04/test-file.jpg' );
		$schemas        = array ( 0 => 'http', 1 => 'https' );
		$requested_file = trailingslashit( site_url() ) . 'wp-content/uploads/cs/2019/04/test-file.jpg';

		$this->assertTrue( cs_local_file_location_is_allowed( $file_details, $schemas, $requested_file ) );
	}

	function test_should_allow_file_download_uploaded_file_in_content_url() {
		$file_details   = array ( 'scheme' => 'https', 'host' => site_url(), 'path' => '/wp-content/my-files/test-file.jpg' );
		$schemas        = array ( 0 => 'http', 1 => 'https' );
		$requested_file = trailingslashit( site_url() ) . '/wp-content/my-files/test-file.jpg';

		$this->assertTrue( cs_local_file_location_is_allowed( $file_details, $schemas, $requested_file ) );
	}

	function test_should_allow_file_download_uploaded_file_in_content_absolute_in_content() {
		$this->write_test_file( trailingslashit( WP_CONTENT_DIR ) . 'test-file.jpg' );
		$file_details   = array ( 'path' => trailingslashit( WP_CONTENT_DIR ) . 'test-file.jpg' );
		$schemas        = array ( 0 => 'http', 1 => 'https' );
		$requested_file =  trailingslashit( WP_CONTENT_DIR ) . 'test-file.jpg';

		$this->assertTrue( cs_local_file_location_is_allowed( $file_details, $schemas, $requested_file ) );
		$this->delete_test_file( trailingslashit( WP_CONTENT_DIR ) . 'test-file.jpg' );
	}

	function test_should_allow_file_download_uploaded_file_in_content_absolute_outside_of_content() {
		$this->write_test_file( trailingslashit( ABSPATH ) . 'test-file.jpg' );
		$file_details   = array ( 'path' => trailingslashit( ABSPATH ) . 'test-file.jpg' );
		$schemas        = array ( 0 => 'http', 1 => 'https' );
		$requested_file =  trailingslashit( ABSPATH ) . 'test-file.jpg';

		$this->assertFalse( cs_local_file_location_is_allowed( $file_details, $schemas, $requested_file ) );
		$this->delete_test_file( trailingslashit( ABSPATH ) . 'test-file.jpg' );
	}

	function test_should_allow_file_download_uploaded_file_in_content_url_on_windows_WAMP() {
		$file_details   = array ( 'scheme' => 'https', 'host' => site_url(), 'path' => 'E:\wamp\www\site\wp/wp-content/my-files/test-file.jpg' );
		$schemas        = array ( 0 => 'http', 1 => 'https' );
		$requested_file = trailingslashit( site_url() ) . '/wp-content/my-files/test-file.jpg';

		$this->assertTrue( cs_local_file_location_is_allowed( $file_details, $schemas, $requested_file ) );
	}

	function test_should_allow_file_download_uploaded_file_in_content_absolute_outside_of_content_on_windows_WAMP() {
		$file_details   = array ( 'path' => 'E:\wamp\www\site\wp/test-file.jpg' );
		$schemas        = array ( 0 => 'http', 1 => 'https' );
		$requested_file = 'E:\wamp\www\site\wp/test-file.jpg';

		$this->assertFalse( cs_local_file_location_is_allowed( $file_details, $schemas, $requested_file ) );
	}

	function test_should_allow_file_download_uploaded_file_in_content_url_on_windows_IIS() {
		$file_details   = array ( 'scheme' => 'https', 'host' => site_url(), 'path' => 'C:\inetpub\wwwroot\mysite/wp-content/my-files/test-file.jpg' );
		$schemas        = array ( 0 => 'http', 1 => 'https' );
		$requested_file = trailingslashit( site_url() ) . '/wp-content/my-files/test-file.jpg';

		$this->assertTrue( cs_local_file_location_is_allowed( $file_details, $schemas, $requested_file ) );
	}

	function test_should_allow_file_download_uploaded_file_in_content_absolute_outside_of_content_on_windows_IIS() {
		$file_details   = array ( 'path' => 'C:\inetpub\wwwroot\mysite/test-file.jpg' );
		$schemas        = array ( 0 => 'http', 1 => 'https' );
		$requested_file = 'C:\inetpub\wwwroot\mysite/test-file.jpg';

		$this->assertFalse( cs_local_file_location_is_allowed( $file_details, $schemas, $requested_file ) );
	}

	function test_is_countable_defined() {
		$this->assertTrue( function_exists( 'is_countable' ) );
	}

	function test_is_iterable_defined() {
		$this->assertTrue( function_exists( 'is_iterable' ) );
	}

	private function write_test_file( $full_file_path ) {
		$file = fopen( $full_file_path,"w" );
		fwrite( $file,"" );
		fclose( $file );
	}

	private function delete_test_file( $full_file_path ) {
		unlink( $full_file_path );
	}
}
