<?php
defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';
?>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Item', 'nm-gift-registry' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Quantity', 'nm-gift-registry' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Price', 'nm-gift-registry' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			// Total price figure of all the items - without currency symbol
			$total = 0;

			foreach ( $order_item_ids as $item_id ) {
				$item = $order->get_item( $item_id );

				if ( !$item ) {
					continue;
				}

				$product = $item->get_product();

				if ( !$product ) {
					continue;
				}

				$image = $product->get_image( array( 32, 32 ) );
				$total = $total + $order->get_line_subtotal( $item );
				?>
				<tr>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
						<?php
						echo wp_kses_post( apply_filters( 'nmgr_order_items_details_thumbnail', $image, $item ) );

						echo wp_kses_post( apply_filters( 'nmgr_order_items_details_name', $item->get_name(), $item, false ) );

						if ( $product->get_sku() ) {
							echo wp_kses_post( ' (#' . $product->get_sku() . ')' );
						}
						?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( apply_filters( 'nmgr_order_items_details_quantity', $item->get_quantity() ) ); ?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>;border-top-width: 4px;">
					<?php esc_html_e( 'Total', 'nm-gift-registry' ); ?>
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