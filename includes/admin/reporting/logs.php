<?php
/**
 * Logs UI (moved)
 *
 * @package     CS
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 * @deprecated  3.0
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

_cs_deprecated_file(
	__FILE__,
	'3.0',
	'includes/admin/tools/logs.php',
	__( 'The logs tab has been moved to the Tools screen.', 'commercestore' )
);

require_once CS_PLUGIN_DIR . 'includes/admin/tools/logs.php';
