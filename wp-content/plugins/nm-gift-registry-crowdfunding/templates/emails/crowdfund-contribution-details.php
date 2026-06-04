<?php
/**
 * Crowdfund contribution details snippet attached to emails
 */
defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';
?>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col"  style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Item', 'nm-gift-registry-crowdfunding' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Amount Contributed', 'nm-gift-registry-crowdfunding' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			// Total price figure of all the items - without currency symbol
			$total = 0;

			foreach ( $order_item_ids as $wishlist_item_id => $order_item_id ) {
				$order_item = $order->get_item( $order_item_id );
				$wishlist_item = nmgr_get_wishlist_item( $wishlist_item_id );

				if ( !$order_item || !$wishlist_item ) {
					continue;
				}

				$image = $wishlist_item->get_product_image( array( 32, 32 ) );
				$total = $total + $order->get_line_subtotal( $order_item );
				?>
				<tr>
					<td class="td"  style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
						<?php
						echo wp_kses_post( $image );
						echo wp_kses_post( $wishlist_item->get_product_name() );

						if ( $wishlist_item->get_product_sku() ) {
							echo wp_kses_post( ' (#' . $wishlist_item->get_product_sku() . ')' );
						}
						?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $order_item ) ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<th class="td" scope="row"  style="text-align:<?php echo esc_attr( $text_align ); ?>;border-top-width: 4px;">
					<?php esc_html_e( 'Total', 'nm-gift-registry-crowdfunding' ); ?>
				</th>
				<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;border-top-width: 4px;">
					<?php
					$total_price = wc_price( $total, array( 'currency' => $order->get_currency() ) );
					echo wp_kses_post( $total_price );
					?>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
