<?php


/**
 * @group cs_filters
 */
class Tests_Filters extends CS_UnitTestCase {

	public function set_up() {
		parent::set_up();
	}

	public function test_the_content() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_before_download_content', $wp_filter['the_content'][10] );
		$this->assertArrayHasKey( 'cs_after_download_content', $wp_filter['the_content'][10] );
		$this->assertArrayHasKey( 'cs_filter_success_page_content', $wp_filter['the_content'][99999] );
	}

	public function test_wp_head() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_version_in_header', $wp_filter['wp_head'][10] );
	}

	public function test_template_redirect() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_disable_jetpack_og_on_checkout', $wp_filter['template_redirect'][10] );
		$this->assertArrayHasKey( 'cs_block_attachments', $wp_filter['template_redirect'][10] );
		$this->assertArrayHasKey( 'cs_process_cart_endpoints', $wp_filter['template_redirect'][100] );
	}

	public function test_init() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_get_actions', $wp_filter['init'][10] );
		$this->assertArrayHasKey( 'cs_post_actions', $wp_filter['init'][10] );
		$this->assertArrayHasKey( 'cs_add_rewrite_endpoints', $wp_filter['init'][10] );
		$this->assertArrayHasKey( 'cs_no_gateway_error', $wp_filter['init'][10] );
		$this->assertArrayHasKey( 'cs_listen_for_paypal_ipn', $wp_filter['init'][10] );
		$this->assertArrayHasKey( 'cs_setup_download_taxonomies', $wp_filter['init'][0] );
		$this->assertArrayHasKey( 'cs_register_post_type_statuses', $wp_filter['init'][2] );
		$this->assertArrayHasKey( 'cs_setup_cs_post_types', $wp_filter['init'][1] );
		$this->assertArrayHasKey( 'cs_process_download', $wp_filter['init'][100] );
	}

	public function test_admin_init() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_register_settings', $wp_filter['admin_init'][10] );
	}

	public function test_delete_post() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_remove_download_logs_on_delete', $wp_filter['delete_post'][10] );
	}

	public function test_admin_enqueue_scripts() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_enqueue_admin_scripts', $wp_filter['admin_enqueue_scripts'][10] );
	}

	public function test_admin_enqueue_styles() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_enqueue_admin_styles', $wp_filter['admin_enqueue_scripts'][10] );
	}

	public function test_upload_mimes() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_allowed_mime_types', $wp_filter['upload_mimes'][10] );
	}

	public function test_widgets_init() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_register_widgets', $wp_filter['widgets_init'][10] );
	}

	public function test_wp_enqueue_scripts() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_load_scripts',   $wp_filter['wp_enqueue_scripts'][10] );
		$this->assertArrayHasKey( 'cs_enqueue_styles', $wp_filter['wp_enqueue_scripts'][10] );
	}

	public function test_ajax() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_ajax_remove_from_cart', $wp_filter['wp_ajax_cs_remove_from_cart'][10] );
		$this->assertArrayHasKey( 'cs_ajax_remove_from_cart', $wp_filter['wp_ajax_nopriv_cs_remove_from_cart'][10] );
		$this->assertArrayHasKey( 'cs_ajax_add_to_cart', $wp_filter['wp_ajax_cs_add_to_cart'][10] );
		$this->assertArrayHasKey( 'cs_ajax_add_to_cart', $wp_filter['wp_ajax_nopriv_cs_add_to_cart'][10] );
		$this->assertArrayHasKey( 'cs_ajax_apply_discount', $wp_filter['wp_ajax_cs_apply_discount'][10] );
		$this->assertArrayHasKey( 'cs_ajax_apply_discount', $wp_filter['wp_ajax_nopriv_cs_apply_discount'][10] );
		$this->assertArrayHasKey( 'cs_load_checkout_login_fields', $wp_filter['wp_ajax_nopriv_checkout_login'][10] );
		$this->assertArrayHasKey( 'cs_load_checkout_register_fields', $wp_filter['wp_ajax_nopriv_checkout_register'][10] );
		$this->assertArrayHasKey( 'cs_ajax_get_download_title', $wp_filter['wp_ajax_cs_get_download_title'][10] );
		$this->assertArrayHasKey( 'cs_ajax_get_download_title', $wp_filter['wp_ajax_nopriv_cs_get_download_title'][10] );
		$this->assertArrayHasKey( 'cs_check_for_download_price_variations', $wp_filter['wp_ajax_cs_check_for_download_price_variations'][10] );
		$this->assertArrayHasKey( 'cs_load_ajax_gateway', $wp_filter['wp_ajax_cs_load_gateway'][10] );
		$this->assertArrayHasKey( 'cs_load_ajax_gateway', $wp_filter['wp_ajax_nopriv_cs_load_gateway'][10] );
		$this->assertArrayHasKey( 'cs_print_errors', $wp_filter['cs_ajax_checkout_errors'][10] );
		$this->assertArrayHasKey( 'cs_process_purchase_form', $wp_filter['wp_ajax_cs_process_checkout'][10] );
		$this->assertArrayHasKey( 'cs_process_purchase_form', $wp_filter['wp_ajax_nopriv_cs_process_checkout'][10] );
	}

	public function test_cs_after_download_content() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_append_purchase_link', $wp_filter['cs_after_download_content'][10] );
		$this->assertArrayHasKey( 'cs_show_added_to_cart_messages', $wp_filter['cs_after_download_content'][10] );
	}

	public function test_cs_purchase_link_top() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_purchase_variable_pricing', $wp_filter['cs_purchase_link_top'][10] );
		$this->assertArrayHasKey( 'cs_download_purchase_form_quantity_field', $wp_filter['cs_purchase_link_top'][10] );
	}

	public function test_cs_after_price_option() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_variable_price_quantity_field', $wp_filter['cs_after_price_option'][10] );
	}

	public function test_cs_downloads_excerpt() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_downloads_default_excerpt', $wp_filter['cs_downloads_excerpt'][10] );
	}

	public function test_cs_downloads_content() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_downloads_default_content', $wp_filter['cs_downloads_content'][10] );
	}

	public function test_cs_purchase_form() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_show_purchase_form', $wp_filter['cs_purchase_form'][10] );
	}

	public function test_cs_purchase_form_after_user_info() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_user_info_fields', $wp_filter['cs_purchase_form_after_user_info'][10] );
	}

	public function test_cs_cc_form() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_get_cc_form', $wp_filter['cs_cc_form'][10] );
	}

	public function test_cs_after_cc_fields() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_default_cc_address_fields', $wp_filter['cs_after_cc_fields'][10] );
	}

	public function test_cs_purchase_form_register_fields() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_get_register_fields', $wp_filter['cs_purchase_form_register_fields'][10] );
	}

	public function test_cs_purchase_form_login_fields() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_get_login_fields', $wp_filter['cs_purchase_form_login_fields'][10] );
	}

	public function test_cs_payment_mode_select() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_payment_mode_select', $wp_filter['cs_payment_mode_select'][10] );
	}

	public function test_cs_purchase_form_before_cc_form() {
		global $wp_filter;
		// No actions connected to cs_purchase_form_before_cc_form by default
		$this->assertTrue( true );
		//$this->assertArrayHasKey( 'cs_discount_field', $wp_filter['cs_purchase_form_before_cc_form'][10] );
	}

	public function test_cs_purchase_form_after_cc_form() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_checkout_tax_fields', $wp_filter['cs_purchase_form_after_cc_form'][999] );
		$this->assertArrayHasKey( 'cs_checkout_submit', $wp_filter['cs_purchase_form_after_cc_form'][9999] );
	}

	public function test_cs_purchase_form_before_submit() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_print_errors', $wp_filter['cs_purchase_form_before_submit'][10] );
		$this->assertArrayHasKey( 'cs_checkout_final_total', $wp_filter['cs_purchase_form_before_submit'][999] );
	}

	public function test_cs_checkout_form_top() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_discount_field', $wp_filter['cs_checkout_form_top'][-1] );
		$this->assertArrayHasKey( 'cs_show_payment_icons', $wp_filter['cs_checkout_form_top'][10] );
	}

	public function test_cs_empty_cart() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_empty_checkout_cart', $wp_filter['cs_cart_empty'][10] );
	}

	public function test_cs_add_to_cart() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_add_to_cart', $wp_filter['cs_add_to_cart'][10] );
	}

	public function test_cs_remove() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_remove_from_cart', $wp_filter['cs_remove'][10] );
	}

	public function test_cs_purchase_collection() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_collection_purchase', $wp_filter['cs_purchase_collection'][10] );
	}

	public function test_cs_format_amount_decimals() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_currency_decimal_filter', $wp_filter['cs_format_amount_decimals'][10] );
	}

	public function test_cs_paypal_cc_form() {
		global $wp_filter;
		$this->assertArrayHasKey( '__return_false', $wp_filter['cs_paypal_cc_form'][10] );
	}

	public function test_cs_gateway_paypal() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_paypal_purchase', $wp_filter['cs_gateway_paypal'][10] );
	}

	public function test_cs_verify_paypal_ipn() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_paypal_ipn', $wp_filter['cs_verify_paypal_ipn'][10] );
	}

	public function test_cs_paypal_web_accept() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_paypal_web_accept_and_cart', $wp_filter['cs_paypal_web_accept'][10] );
	}

	public function test_cs_paypal_link_transaction_id() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_paypal_link_transaction_id', $wp_filter['cs_payment_details_transaction_id-paypal'][10] );
	}

	public function test_cs_manual_cc_form() {
		global $wp_filter;
		$this->assertArrayHasKey( '__return_false', $wp_filter['cs_manual_cc_form'][10] );
	}

	public function test_cs_gateway_manual() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_manual_payment', $wp_filter['cs_gateway_manual'][10] );
	}

	public function test_cs_remove_cart_discount() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_remove_cart_discount', $wp_filter['cs_remove_cart_discount'][10] );
	}

	public function test_comments_clauses() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_hide_payment_notes', $wp_filter['pre_get_comments'][10] );
		$this->assertArrayHasKey( 'cs_hide_payment_notes_pre_41', $wp_filter['comments_clauses'][10] );
	}

	public function test_cs_update_payment_status() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_complete_purchase', $wp_filter['cs_update_payment_status'][100] );
		$this->assertArrayHasKey( 'cs_record_order_status_change', $wp_filter['cs_transition_order_status'][100] );
		$this->assertArrayHasKey( 'cs_clear_user_history_cache', $wp_filter['cs_update_payment_status'][10] );
	}

	public function test_cs_upgrade_payments() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_update_old_payments_with_totals', $wp_filter['cs_upgrade_payments'][10] );
	}

	public function test_cs_cleanup_file_symlinks() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_cleanup_file_symlinks', $wp_filter['cs_cleanup_file_symlinks'][10] );
	}

	public function test_cs_download_price() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_format_amount', $wp_filter['cs_download_price'][10] );
		$this->assertArrayHasKey( 'cs_currency_filter', $wp_filter['cs_download_price'][20] );
	}

	public function test_admin_head() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_admin_downloads_icon', $wp_filter['admin_head'][10] );
	}

	public function test_enter_title_here() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_change_default_title', $wp_filter['enter_title_here'][10] );
	}

	public function test_post_updated_messages() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_updated_messages', $wp_filter['post_updated_messages'][10] );
	}

	public function test_bulk_post_updated_messages() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_bulk_updated_messages', $wp_filter['bulk_post_updated_messages'][10] );
	}

	public function test_load_edit_php() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_remove_post_types_order', $wp_filter['load-edit.php'][10] );
	}

	public function test_cs_settings_misc() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_append_no_cache_param', $wp_filter['cs_settings_misc'][-1] );
	}

	public function test_cs_admin_sale_notice() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_admin_email_notice', $wp_filter['cs_admin_sale_notice'][10] );
	}

	public function test_cs_email_settings() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_email_template_preview', $wp_filter['cs_purchase_receipt_email_settings'][10] );
	}

	public function test_cs_view_receipt() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_render_receipt_in_browser', $wp_filter['cs_view_receipt'][10] );
	}

	public function test_cs_email_links() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_resend_purchase_receipt', $wp_filter['cs_email_links'][10] );
	}

	public function test_cs_send_test_email() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_send_test_email', $wp_filter['cs_send_test_email'][10] );
	}

	public function test_cs_purchase() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_purchase_form', $wp_filter['cs_purchase'][10] );
	}

	public function test_cs_user_login() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_login_form', $wp_filter['cs_user_login'][10] );
	}

	public function test_cs_edit_user_profile() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_process_profile_editor_updates', $wp_filter['cs_edit_user_profile'][10] );
	}

	public function test_post_class() {
		global $wp_filter;
		$this->assertArrayHasKey( 'cs_responsive_download_post_class', $wp_filter['post_class'][999] );
	}

}
