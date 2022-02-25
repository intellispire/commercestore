<?php

/**
 * CommerceStore class tests.
 *
 * @coversDefaultClass CS
 */
class Tests_CS extends CS_UnitTestCase {
	protected $object;

	public function setUp() {
		parent::setUp();
		$this->object = CS();
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function test_cs_instance() {
		$this->assertClassHasStaticAttribute( 'instance', 'CommerceStore' );
	}

	/**
	 * @covers CommerceStore::setup_constants
	 */
	public function test_constants() {
		// Plugin Folder URL
		$path = str_replace( 'tests/', '', plugin_dir_url( __FILE__ ) );
		$this->assertSame( CS_PLUGIN_URL, $path );

		// Plugin Folder Path
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$path = substr( $path, 0, -1 );
		$cs  = substr( CS_PLUGIN_DIR, 0, -1 );
		$this->assertSame( $cs, $path );

		// Plugin Root File
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$this->assertSame( CS_PLUGIN_FILE, $path.'commercestore.php' );
	}

	/**
	 * @dataProvider _test_includes_dp
	 * @covers ::includes()
	 *
	 * @group cs_includes
	 */
	public function test_includes( $path_to_file ) {
		$this->assertFileExists( $path_to_file );
	}

	/**
	 * Data provider for test_includes().
	 */
	public function _test_includes_dp() {
		return array(
			array( CS_PLUGIN_DIR . 'includes/admin/settings/register-settings.php' ),
			array( CS_PLUGIN_DIR . 'includes/install.php' ),
			array( CS_PLUGIN_DIR . 'includes/actions.php' ),
			array( CS_PLUGIN_DIR . 'includes/deprecated-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/deprecated-hooks.php' ),
			array( CS_PLUGIN_DIR . 'includes/ajax-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/template-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/checkout/template.php' ),
			array( CS_PLUGIN_DIR . 'includes/checkout/functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/cart/template.php' ),
			array( CS_PLUGIN_DIR . 'includes/cart/functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/cart/actions.php' ),
			array( CS_PLUGIN_DIR . 'includes/api/class-cs-api.php' ),
			array( CS_PLUGIN_DIR . 'includes/api/class-cs-api-v1.php' ),
			array( CS_PLUGIN_DIR . 'includes/class-cs-cache-helper.php' ),
			array( CS_PLUGIN_DIR . 'includes/class-cs-fees.php' ),
			array( CS_PLUGIN_DIR . 'includes/class-cs-html-elements.php' ),
			array( CS_PLUGIN_DIR . 'includes/class-cs-logging.php' ),
			array( CS_PLUGIN_DIR . 'includes/class-cs-session.php' ),
			array( CS_PLUGIN_DIR . 'includes/class-cs-roles.php' ),
			array( CS_PLUGIN_DIR . 'includes/class-cs-stats.php' ),
			array( CS_PLUGIN_DIR . 'includes/class-utilities.php' ),
			array( CS_PLUGIN_DIR . 'includes/formatting.php' ),
			array( CS_PLUGIN_DIR . 'includes/widgets.php' ),
			array( CS_PLUGIN_DIR . 'includes/mime-types.php' ),
			array( CS_PLUGIN_DIR . 'includes/gateways/functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/gateways/paypal-standard.php' ),
			array( CS_PLUGIN_DIR . 'includes/gateways/manual.php' ),
			array( CS_PLUGIN_DIR . 'includes/interface-cs-exception.php' ),
			array( CS_PLUGIN_DIR . 'includes/discount-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/payments/functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/payments/actions.php' ),
			array( CS_PLUGIN_DIR . 'includes/payments/class-payment-stats.php' ),
			array( CS_PLUGIN_DIR . 'includes/payments/class-payments-query.php' ),
			array( CS_PLUGIN_DIR . 'includes/misc-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/download-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/scripts.php' ),
			array( CS_PLUGIN_DIR . 'includes/post-types.php' ),
			array( CS_PLUGIN_DIR . 'includes/plugin-compatibility.php' ),
			array( CS_PLUGIN_DIR . 'includes/reports/exceptions/class-invalid-parameter.php' ),
			array( CS_PLUGIN_DIR . 'includes/emails/functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/emails/template.php' ),
			array( CS_PLUGIN_DIR . 'includes/emails/actions.php' ),
			array( CS_PLUGIN_DIR . 'includes/error-tracking.php' ),
			array( CS_PLUGIN_DIR . 'includes/user-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/query-filters.php' ),
			array( CS_PLUGIN_DIR . 'includes/tax-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/process-purchase.php' ),
			array( CS_PLUGIN_DIR . 'includes/login-register.php' ),
			array( CS_PLUGIN_DIR . 'includes/reports/class-init.php' ),
			array( CS_PLUGIN_DIR . 'includes/utils/class-cs-exception.php' ),
			array( CS_PLUGIN_DIR . 'includes/utils/class-registry.php' ),
			array( CS_PLUGIN_DIR . 'includes/utils/interface-static-registry.php' ),
			array( CS_PLUGIN_DIR . 'includes/utils/exceptions/class-attribute-not-found.php' ),
			array( CS_PLUGIN_DIR . 'includes/utils/exceptions/class-invalid-argument.php' ),
			array( CS_PLUGIN_DIR . 'includes/utils/exceptions/class-invalid-parameter.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/add-ons.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/admin-actions.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/class-cs-notices.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/admin-pages.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/dashboard-widgets.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/thickbox.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/upload-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/customers/class-customer-table.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/customers/customer-actions.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/customers/customer-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/customers/customers.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/downloads/dashboard-columns.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/downloads/metabox.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/downloads/contextual-help.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/discounts/contextual-help.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/discounts/discount-actions.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/discounts/discount-codes.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/payments/payments-history.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/payments/contextual-help.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/reporting/contextual-help.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/reporting/export/export-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/reporting/reports.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/reporting/graphing.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/settings/display-settings.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/settings/contextual-help.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/upgrades/upgrades.php' ),
			array( CS_PLUGIN_DIR . 'includes/admin/class-cs-heartbeat.php' ),
			array( CS_PLUGIN_DIR . 'includes/process-download.php' ),
			array( CS_PLUGIN_DIR . 'includes/shortcodes.php' ),
			array( CS_PLUGIN_DIR . 'includes/theme-compatibility.php' ),
		);
	}

	/**
	 * @dataProvider _test_includes_assets_dp
	 * @covers ::includes()
	 *
	 * @group cs_includes
	 */
	public function test_includes_assets( $path_to_file ) {
		$this->assertFileExists( $path_to_file );
	}

	/**
	 * Data provider for test_includes_assets().
	 */
	public function _test_includes_assets_dp() {
		return array(
			array( CS_PLUGIN_DIR . 'assets/css/chosen.min.css' ),
			array( CS_PLUGIN_DIR . 'assets/css/cs-admin-chosen.min.css' ),
			array( CS_PLUGIN_DIR . 'assets/css/cs-admin.min.css' ),
			array( CS_PLUGIN_DIR . 'assets/images/cs-cpt-2x.png' ),
			array( CS_PLUGIN_DIR . 'assets/images/cs-cpt.png' ),
			array( CS_PLUGIN_DIR . 'assets/images/cs-icon-2x.png' ),
			array( CS_PLUGIN_DIR . 'assets/images/cs-icon.png' ),
			array( CS_PLUGIN_DIR . 'assets/images/cs-logo.png' ),
			array( CS_PLUGIN_DIR . 'assets/images/cs-media.png' ),
			array( CS_PLUGIN_DIR . 'assets/images/loading.gif' ),
			array( CS_PLUGIN_DIR . 'templates/images/loading.gif' ),
			array( CS_PLUGIN_DIR . 'assets/images/media-button.png' ),
			array( CS_PLUGIN_DIR . 'templates/images/tick.png' ),
			array( CS_PLUGIN_DIR . 'assets/images/xit.gif' ),
			array( CS_PLUGIN_DIR . 'templates/images/xit.gif' ),
			array( CS_PLUGIN_DIR . 'assets/js/cs-admin.js' ),
			array( CS_PLUGIN_DIR . 'assets/js/cs-ajax.js' ),
			array( CS_PLUGIN_DIR . 'assets/js/cs-checkout-global.js' ),
			array( CS_PLUGIN_DIR . 'assets/js/vendor/chosen.jquery.min.js' ),
			array( CS_PLUGIN_DIR . 'assets/js/vendor/jquery.creditcardvalidator.min.js' ),
			array( CS_PLUGIN_DIR . 'assets/js/vendor/jquery.flot.min.js' ),

			// Cannot be in /vendor/ for back-compat :(
			array( CS_PLUGIN_DIR . 'assets/js/jquery.validate.min.js' ),
		);
	}
}
