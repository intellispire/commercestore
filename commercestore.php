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

// @todo replace hardcoding of post_type and taxonomies with the following constants all throughout the codebase
const CS_POST_TYPE = 'csproduct';
const CS_TAG_TYPE = CS_POST_TYPE . '_tag';
const CS_CAT_TYPE = CS_POST_TYPE . '_category';

const CS_BASE_PLUGIN = __FILE__;

require_once( __DIR__ . '/vendor/autoload.php' );

// Invoke the checker
new CS_Requirements_Check();

require_once('includes/subscriptions/subscriptions.php');
add_action('init', function() { CS_Auto_Register::get_instance(); });

// Optional Extensions
@include_once('csae/csae.php');
@include_once('csae-dev/csae-dev.php');
