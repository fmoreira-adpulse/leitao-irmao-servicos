<?php
/**
 * System status report.
 *
 * @var array $rules Array of the active BOGO rules data in json format.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>

<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Buy One Get One Free"><h2>Buy One Get One Free <?php echo wc_help_tip( esc_html__( 'This section shows details of the Buy One Get One Free plugin.', 'wc-buy-one-get-one-free' ) ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-lable="<?php esc_html_e( 'Display eligible free gift(s) on', 'wc-buy-one-get-one-free' ); ?>">
			<?php esc_html_e( 'Display eligible free gift(s) on', 'wc-buy-one-get-one-free' ); ?>
			</td>
			<td>&nbsp;</td>
			<td>
				<?php echo esc_html( get_option( 'wc_bogof_cyg_display_on' ) ); ?>
			</td>
		</tr>
		<?php if ( $wc_gift_page ) : ?>
		<tr>
			<td data-export-lable="<?php esc_html_e( 'Choose your gift page', 'wc-buy-one-get-one-free' ); ?>">
			<?php esc_html_e( 'Choose your gift page', 'wc-buy-one-get-one-free' ); ?>
			</td>
			<td>&nbsp;</td>
			<td>
				<?php
				$found_error = false;
				// Page ID check.
				if ( ! $wc_gift_page['page_set'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Page not set', 'woocommerce' ) . '</mark>';
					$found_error = true;
				} elseif ( ! $wc_gift_page['page_exists'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Page ID is set, but the page does not exist', 'woocommerce' ) . '</mark>';
					$found_error = true;
				} elseif ( ! $wc_gift_page['page_visible'] ) {
					/* Translators: %s: docs link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . wp_kses_post( sprintf( __( 'Page visibility should be <a href="%s" target="_blank">public</a>', 'woocommerce' ), 'https://wordpress.org/support/article/content-visibility/' ) ) . '</mark>';
					$found_error = true;
				} elseif ( ! $wc_gift_page['shortcode_present'] ) {
					// Shortcode check.
					// translators: HTML tags.
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . wp_kses_post( sprintf( __( 'The "choose your gift" page has not set! Customers will not be able to add to the cart the free product. Go to the %1$ssettings page%2$s and set a %3$spublic page%4$s that contains the [wc_choose_your_gift] shortcode. ', 'wc-buy-one-get-one-free' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=buy-one-get-one-free' ) . '">', '</a>', '<strong>', '</strong>' ) );
				}

				if ( ! $found_error ) {
					echo '<mark class="yes">#' . absint( $wc_gift_page['page_id'] ) . ' - ' . esc_html( str_replace( home_url(), '', get_permalink( $wc_gift_page['page_id'] ) ) ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<td data-export-lable="<?php esc_html_e( 'Disable coupons', 'wc-buy-one-get-one-free' ); ?>">
			<?php esc_html_e( 'Disable coupons', 'wc-buy-one-get-one-free' ); ?>
			</td>
			<td>&nbsp;</td>
			<td>
				<?php echo esc_html( get_option( 'wc_bogof_disable_coupons', 'no' ) ); ?>
			</td>
		</tr>
		<tr>
			<td data-export-lable="<?php esc_html_e( 'Custom attributes', 'wc-buy-one-get-one-free' ); ?>">
			<?php esc_html_e( 'Custom attributes', 'wc-buy-one-get-one-free' ); ?>
			</td>
			<td>&nbsp;</td>
			<td>
				<?php echo esc_html( get_option( 'wc_bogof_include_custom_attributes', 'no' ) ); ?>
			</td>
		</tr>
		<?php
		foreach ( $rules as $wc_bogo_id => $wc_bogo_data ) {
			printf(
				'<tr><td>%1$s</td><td>&nbsp;</td><td>%2$s</td></tr>',
				esc_html( 'Rule #' . $wc_bogo_id ),
				esc_html( $wc_bogo_data )
			);
		}
		?>
	</tbody>
</table>
