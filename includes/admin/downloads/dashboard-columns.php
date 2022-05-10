<?php
/**
 * Dashboard Columns
 *
 * @package     CS
 * @subpackage  Admin/Downloads
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Download Columns
 *
 * Defines the custom columns and their order
 *
 * @since 1.0
 * @param array $download_columns Array of download columns
 * @return array $download_columns Updated array of download columns for Downloads
 *  Post Type List Table
 */
function cs_download_columns( $download_columns ) {
	$category_labels = cs_get_taxonomy_labels( 'download_category' );
	$tag_labels      = cs_get_taxonomy_labels( 'download_tag'      );

	return apply_filters( 'cs_download_columns', array(
		'cb'                => '<input type="checkbox"/>',
		'title'             => __( 'Name', 'commercestore' ),
		'download_category' => $category_labels['menu_name'],
		'download_tag'      => $tag_labels['menu_name'],
		'price'             => __( 'Price', 'commercestore' ),
		'sales'             => __( 'Net Sales', 'commercestore' ),
		'earnings'          => __( 'Net Revenue', 'commercestore' ),
		'date'              => __( 'Date', 'commercestore' )
	) );
}
add_filter( 'manage_edit-download_columns', 'cs_download_columns' );

/**
 * Render Download Columns
 *
 * @since 1.0
 * @param string $column_name Column name
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_download_columns( $column_name, $post_id ) {

	// Bail if not a download
	if ( get_post_type( $post_id ) !== 'download' ) {
		return;
	}

	switch ( $column_name ) {
		case 'download_category':
			$terms = get_the_term_list( $post_id, 'download_category', '', ', ', '');
			echo ! empty( $terms )
				? $terms
				: '&mdash;';
			break;
		case 'download_tag':
			$terms = get_the_term_list( $post_id, 'download_tag', '', ', ', '');
			echo ! empty( $terms )
				? $terms
				: '&mdash;';
			break;
		case 'price':
			if ( cs_has_variable_prices( $post_id ) ) {
				echo cs_price_range( $post_id );
			} else {
				echo cs_price( $post_id, false );
				echo '<input type="hidden" class="downloadprice-' . $post_id . '" value="' . cs_get_download_price( $post_id ) . '" />';
			}
			break;
		case 'sales':
			if ( current_user_can( 'view_product_stats', $post_id ) ) {
				$sales_url = add_query_arg( array(
					'page'       => 'cs-payment-history',
					'product-id' => urlencode( $post_id )
				), cs_get_admin_base_url() );

				echo '<a href="' . esc_url( $sales_url ) . '">';
					echo cs_get_download_sales_stats( $post_id );
				echo '</a>';
			} else {
				echo '-';
			}
			break;
		case 'earnings':
			if ( current_user_can( 'view_product_stats', $post_id ) ) {
				$report_url = cs_get_admin_url( array(
					'page'     => 'cs-reports',
					'view'     => 'downloads',
					'products' => $post_id,
				) );

				echo '<a href="' . esc_url( $report_url ) . '">';
					echo cs_currency_filter( cs_format_amount( cs_get_download_earnings_stats( $post_id ) ) );
				echo '</a>';
			} else {
				echo '-';
			}
			break;
	}
}
add_action( 'manage_posts_custom_column', 'cs_render_download_columns', 10, 2 );

/**
 * Registers the sortable columns in the list table
 *
 * @since 1.0
 * @param array $columns Array of the columns
 * @return array $columns Array of sortable columns
 */
function cs_sortable_download_columns( $columns ) {
	$columns['price']    = 'price';
	$columns['sales']    = 'sales';
	$columns['earnings'] = 'earnings';

	return $columns;
}
add_filter( 'manage_edit-download_sortable_columns', 'cs_sortable_download_columns' );

/**
 * Sorts Columns in the Downloads List Table
 *
 * @since 1.0
 * @param array $vars Array of all the sort variables
 * @return array $vars Array of all the sort variables
 */
function cs_sort_downloads( $vars ) {
	// Check if we're viewing the "download" post type
	if ( isset( $vars['post_type'] ) && 'download' == $vars['post_type'] ) {
		// Check if 'orderby' is set to "sales"
		if ( isset( $vars['orderby'] ) && 'sales' == $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => '_cs_download_sales',
					'orderby'  => 'meta_value_num'
				)
			);
		}

		// Check if "orderby" is set to "earnings"
		if ( isset( $vars['orderby'] ) && 'earnings' == $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => '_cs_download_earnings',
					'orderby'  => 'meta_value_num'
				)
			);
		}

		// Check if "orderby" is set to "earnings"
		if ( isset( $vars['orderby'] ) && 'price' == $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => 'cs_price',
					'orderby'  => 'meta_value_num'
				)
			);
		}
	}

	return $vars;
}

/**
 * Sets restrictions on author of Downloads List Table
 *
 * @since  2.2
 * @param  array $vars Array of all sort variables
 * @return array       Array of all sort variables
 */
function cs_filter_downloads( $vars ) {
	if ( isset( $vars['post_type'] ) && 'download' == $vars['post_type'] ) {

		// If an author ID was passed, use it
		if ( isset( $_REQUEST['author'] ) && ! current_user_can( 'view_shop_reports' ) ) {

			$author_id = $_REQUEST['author'];
			if ( (int) $author_id !== get_current_user_id() ) {
				// Tried to view the products of another person, sorry
				wp_die( __( 'You do not have permission to view this data.', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
			}
			$vars = array_merge(
				$vars,
				array(
					'author' => get_current_user_id()
				)
			);
		}
	}

	return $vars;
}

/**
 * Download Load
 *
 * Sorts the downloads.
 *
 * @since 1.0
 * @return void
 */
function cs_download_load() {
	add_filter( 'request', 'cs_sort_downloads'   );
	add_filter( 'request', 'cs_filter_downloads' );
}
add_action( 'load-edit.php', 'cs_download_load', 9999 );

/**
 * Add Download Filters
 *
 * Adds taxonomy drop down filters for downloads.
 *
 * @since 1.0
 * @return void
 */
function cs_add_download_filters() {
	global $typenow;

	// Checks if the current post type is 'download'
	if ( $typenow !== 'download') {
		return;
	}

	$terms = get_terms( 'download_category' );
	if ( count( $terms ) > 0 ) {
		echo "<select name='download_category' id='download_category' class='postform'>";
			$category_labels = cs_get_taxonomy_labels( 'download_category' );
			echo "<option value=''>" . sprintf( __( 'All %s', 'commercestore' ), strtolower( $category_labels['name'] ) ) . "</option>";
			foreach ( $terms as $term ) {
				$selected = isset( $_GET['download_category'] ) && $_GET['download_category'] === $term->slug ? ' selected="selected"' : '';
				echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
			}
		echo "</select>";
	}

	$terms = get_terms( 'download_tag' );
	if ( count( $terms ) > 0 ) {
		echo "<select name='download_tag' id='download_tag' class='postform'>";
			$tag_labels = cs_get_taxonomy_labels( 'download_tag' );
			echo "<option value=''>" . sprintf( __( 'All %s', 'commercestore' ), strtolower( $tag_labels['name'] ) ) . "</option>";
			foreach ( $terms as $term ) {
				$selected = isset( $_GET['download_tag']) && $_GET['download_tag'] === $term->slug ? ' selected="selected"' : '';
				echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
			}
		echo "</select>";
	}

	if ( isset( $_REQUEST['all_posts'] ) && '1' === $_REQUEST['all_posts'] ) {
		echo '<input type="hidden" name="all_posts" value="1" />';
	} else if ( ! current_user_can( 'view_shop_reports' ) ) {
		$author_id = get_current_user_id();
		echo '<input type="hidden" name="author" value="' . esc_attr( $author_id ) . '" />';
	}
}
add_action( 'restrict_manage_posts', 'cs_add_download_filters', 100 );

/**
 * Remove Download Month Filter
 *
 * Removes the drop down filter for downloads by date.
 *
 * @author Daniel J Griffiths
 * @since 2.1
 * @param array $dates The preset array of dates
 * @global $typenow The post type we are viewing
 * @return array Empty array disables the dropdown
 */
function cs_remove_month_filter( $dates ) {
	global $typenow;

	if ( 'download' === $typenow ) {
		$dates = array();
	}

	return $dates;
}
add_filter( 'months_dropdown_results', 'cs_remove_month_filter', 99 );

/**
 * Adds price field to Quick Edit options
 *
 * @since 1.1.3.4
 * @param string $column_name Name of the column
 * @param string $post_type Current Post Type (i.e. download)
 * @return void
 */
function cs_price_field_quick_edit( $column_name, $post_type ) {

	// Bail if not price or download
	if ( $column_name !== 'price' || $post_type !== 'download' ) {
		return;
	} ?>

	<fieldset class="inline-edit-col-left">
		<div id="cs-download-data" class="inline-edit-col">
			<h4><?php echo sprintf( __( '%s Configuration', 'commercestore' ), cs_get_label_singular() ); ?></h4>
			<label>
				<span class="title"><?php _e( 'Price', 'commercestore' ); ?></span>
				<span class="input-text-wrap">
					<input type="text" name="_cs_regprice" class="text regprice" />
				</span>
			</label>
			<br class="clear" />
		</div>
	</fieldset>

	<?php
}
add_action( 'quick_edit_custom_box', 'cs_price_field_quick_edit', 10, 2 );
add_action( 'bulk_edit_custom_box',  'cs_price_field_quick_edit', 10, 2 );

/**
 * Updates price when saving post
 *
 * @since 1.1.3.4
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_price_save_quick_edit( $post_id ) {
	if ( ! isset( $_POST['post_type']) || 'download' !== $_POST['post_type'] ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	if ( cs_doing_autosave() ) {
		return $post_id;
	}

	if ( isset( $_REQUEST['_cs_regprice'] ) ) {
		update_post_meta( $post_id, 'cs_price', strip_tags( stripslashes( $_REQUEST['_cs_regprice'] ) ) );
	}
}
add_action( 'save_post', 'cs_price_save_quick_edit' );

/**
 * Process bulk edit actions via AJAX
 *
 * @since 1.4.4
 * @return void
 */
function cs_save_bulk_edit() {

	$post_ids = ! empty( $_POST['post_ids'] )
		? wp_parse_id_list( $_POST['post_ids'] )
		: array();

	if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
		$price = isset( $_POST['price'] )
			? strip_tags( stripslashes( $_POST['price'] ) )
			: 0;

		foreach ( $post_ids as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			if ( ! empty( $price ) ) {
				update_post_meta( $post_id, 'cs_price', cs_sanitize_amount( $price ) );
			}
		}
	}

	die();
}
add_action( 'wp_ajax_cs_save_bulk_edit', 'cs_save_bulk_edit' );
