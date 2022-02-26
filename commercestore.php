<?php
/**
 * Plugin Name: CommerceStore
 * Plugin URI: https://commercestore.com
 * Description: The easiest way to sell digital products with WordPress.
 * Author: CommerceStore
 * Author URI: https://commercestore.com
 * Version: 4.0.0
 * Text Domain: commercestore
 * Domain Path: languages
 * Requires PHP: 7.0
 *
 * CommerceStore is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * CommerceStore is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CommerceStore. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package CS
 * @category Core
 * @author CommerceStore
 * @version 4.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Feature flags
 */

const CS_FEATURE_VARIABLE_PRICE = true;
const CS_FEATURE_MARKETING = false;
const CS_DEFAULT_SLUG = 'store'; // Override with the CS_SLUG constant

const CS_ICON = 'download'; // Change the reports icon here

const CS_POST_TYPE = 'infoproduct'; // 'download'
const CS_TAG_TYPE = CS_POST_TYPE . '_tag';
const CS_CAT_TYPE = CS_POST_TYPE . '_category';
const CS_LOG_TYPE = 'cs_log_type';

// Custom Post Types - Not fully implemented across the board
const CS_PRODUCT_CPT = CS_POST_TYPE;
const CS_PAYMENT_CPT = 'cs_payment';
const CS_DISCOUNT_CPT = 'cs_discount';
const CS_LOG_CPT = 'cs_log';


const CS_QUERY_VAR = CS_POST_TYPE; // 'download';

// Tease out the word 'download' that is used in different contexts.
// search and replace back during build?
const CS_EX_ADMIN_PAGE = 'download';
const CS_EX_DOWNLOAD_ADMIN_PAGE = 'download';

const CS_EX_DOWNLOAD_CSS_CLASS = 'download';
const CS_EX_DOWNLOAD_SHORTCODE = 'download';
const CS_EX_DOWNLOAD_ITEM = 'download';
const CS_EX_DOWNLOAD_ARGS = 'download';
const CS_EX_DOWNLOAD_MESSAGES = 'download';
const CS_EX_DOWNLOAD_MESSAGES_TYPE = 'download';

class CSFilter {
	const CATEGORY_ARGS = 'cs_' . CS_CAT_TYPE . '_args';
	const CATEGORY_LABELS = 'cs_' . CS_CAT_TYPE . '_labels';

	const TAG_ARGS = 'cs_' . CS_TAG_TYPE . '_args';
	const TAG_LABELS = 'cs_' . CS_TAG_TYPE . '_labels';
}


const CS_BASE_PLUGIN = __FILE__;

require_once( __DIR__ . '/vendor/autoload.php' );

// Invoke the checker
new CS_Requirements_Check();

require_once('includes/subscriptions/subscriptions.php');
add_action('init', function() { CS_Auto_Register::get_instance(); });

// Optional Extensions
@include_once('csae/csae.php');
@include_once('csae-dev/csae-dev.php');
