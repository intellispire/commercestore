<?php
/**
 * Batch Downloads Export Class
 *
 * This class handles download products export
 *
 * @package     CS
 * @subpackage  Admin/Reporting/Export
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.5
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Batch_Downloads_Export Class
 *
 * @since 2.5
 */
class CS_Batch_Downloads_Export extends CS_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var string
	 * @since 2.5
	 */
	public $export_type = 'downloads';

	/**
	 * Set the CSV columns.
	 *
	 * @since 2.5
	 *
	 * @return array $cols All the columns.
	 */
	public function csv_cols() {
		$cols = array(
			'ID'                     => __( 'ID', 'commercestore' ),
			'post_name'              => __( 'Slug', 'commercestore' ),
			'post_title'             => __( 'Name', 'commercestore' ),
			'post_date'              => __( 'Date Created', 'commercestore' ),
			'post_author'            => __( 'Author', 'commercestore' ),
			'post_content'           => __( 'Description', 'commercestore' ),
			'post_excerpt'           => __( 'Excerpt', 'commercestore' ),
			'post_status'            => __( 'Status', 'commercestore' ),
			'categories'             => __( 'Categories', 'commercestore' ),
			'tags'                   => __( 'Tags', 'commercestore' ),
			'cs_price'              => __( 'Price', 'commercestore' ),
			'_cs_files'             => __( 'Files', 'commercestore' ),
			'_cs_download_limit'    => __( 'File Download Limit', 'commercestore' ),
			'_thumbnail_id'          => __( 'Featured Image', 'commercestore' ),
			'cs_sku'                => __( 'SKU', 'commercestore' ),
			'cs_product_notes'      => __( 'Notes', 'commercestore' ),
			'_cs_download_sales'    => __( 'Sales', 'commercestore' ),
			'_cs_download_earnings' => __( 'Earnings', 'commercestore' ),
		);

		return $cols;
	}

	/**
	 * Get the export data.
	 *
	 * @since 2.5
	 *
	 * @return array $data The data for the CSV file.
	 */
	public function get_data() {
		$data = array();

		$meta = array(
			'cs_price',
			'_cs_files',
			'_cs_download_limit',
			'_thumbnail_id',
			'cs_sku',
			'cs_product_notes',
			'_cs_download_sales',
			'_cs_download_earnings',
		);

		$args = array(
			'post_type'      => CS_POST_TYPE,
			'posts_per_page' => 30,
			'paged'          => $this->step,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		if ( 0 !== $this->download ) {
			$args['post__in'] = array( $this->download );
		}

		$downloads = new WP_Query( $args );

		if ( $downloads->posts ) {
			foreach ( $downloads->posts as $download ) {
				$row = array();

				foreach ( $this->csv_cols() as $key => $value ) {

					// Setup default value
					$row[ $key ] = '';

					if ( in_array( $key, $meta ) ) {
						switch ( $key ) {
							case '_thumbnail_id' :
								$image_id    = get_post_thumbnail_id( $download->ID );
								$row[ $key ] = wp_get_attachment_url( $image_id );
								break;

							case 'cs_price' :
								if ( cs_has_variable_prices( $download->ID ) ) {
									$prices = array();
									foreach ( cs_get_variable_prices( $download->ID ) as $price ) {
										$prices[] = $price['name'] . ': ' . $price['amount'];
									}

									$row[ $key ] = implode( ' | ', $prices );
								} else {
									$row[ $key ] = cs_get_download_price( $download->ID );
								}
								break;

							case '_cs_files' :
								$files = array();

								foreach ( cs_get_download_files( $download->ID ) as $file ) {
									$f = $file['file'];

									if ( cs_has_variable_prices( $download->ID ) ) {
										$condition = isset( $file['condition'] ) ? $file['condition'] : 'all';
										$f         .= ';' . $condition;
									}

									$files[] = $f;

									unset( $file );
								}

								$row[ $key ] = implode( ' | ', $files );
								break;

							default :
								$row[ $key ] = get_post_meta( $download->ID, $key, true );
								break;
						}
					} elseif ( isset( $download->$key ) ) {
						switch ( $key ) {
							case 'post_author':
								$row[ $key ] = get_the_author_meta( 'user_login', $download->post_author );
								break;

							default:
								$row[ $key ] = $download->$key;
								break;
						}
					} elseif ( 'tags' == $key ) {
						$terms = get_the_terms( $download->ID, 'download_tag' );

						if ( $terms ) {
							$terms       = wp_list_pluck( $terms, 'name' );
							$row[ $key ] = implode( ' | ', $terms );
						}
					} elseif ( 'categories' == $key ) {
						$terms = get_the_terms( $download->ID, CS_CAT_TYPE );

						if ( $terms ) {
							$terms       = wp_list_pluck( $terms, 'name' );
							$row[ $key ] = implode( ' | ', $terms );
						}
					}
				}

				$data[] = $row;
			}

			$data = apply_filters( 'cs_export_get_data', $data );
			$data = apply_filters( 'cs_export_get_data_' . $this->export_type, $data );

			return $data;
		}

		return false;
	}

	/**
	 * Return the calculated completion percentage.
	 *
	 * @since 2.5
	 *
	 * @return int Percentage complete.
	 */
	public function get_percentage_complete() {
		$args = array(
			'post_type'      => CS_POST_TYPE,
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		if ( 0 !== $this->download ) {
			$args['post__in'] = array( $this->download );
		}

		$downloads  = new WP_Query( $args );
		$total      = (int) $downloads->post_count;
		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( 30 * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Set the properties specific to the downloads export.
	 *
	 * @since 3.0
	 *
	 * @param array $request Form data passed into the batch processor.
	 */
	public function set_properties( $request ) {
		$this->download = isset( $request['download_id'] ) ? absint( $request['download_id'] ) : null;
	}
}
