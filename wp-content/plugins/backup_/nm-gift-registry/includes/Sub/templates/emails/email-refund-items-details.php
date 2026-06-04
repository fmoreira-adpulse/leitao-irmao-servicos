<?php
defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';
?>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 30px; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Item', 'nm-gift-registry' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Price', 'nm-gift-registry' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Purchased Quantity', 'nm-gift-registry' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Refunded Quantity', 'nm-gift-registry' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $wishlist_item_ids_to_qtys as $item_id => $qty_refunded ) {
				$item = $wishlist->get_item( $item_id );

				if ( !$item ) {
					continue;
				}

				$product = $item->get_product();

				if ( !$product ) {
					continue;
				}

				$image = $product->get_image( array( 32, 32 ) );
				?>
				<tr>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
						<?php
						echo wp_kses_post( apply_filters( 'nmgr_refund_items_details_thumbnail', $image, $item ) );

						echo wp_kses_post( apply_filters( 'nmgr_refund_items_details_name', $product->get_name(), $item, false ) );

						if ( $product->get_sku() ) {
							echo wp_kses_post( ' (#' . $product->get_sku() . ')' );
						}
						?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( $product->get_price_html() ); ?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( apply_filters( 'nmgr_refund_items_details_purchased_quantity', $item->get_purchased_quantity() ) ); ?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( apply_filters( 'nmgr_refund_items_details_refunded_quantity', $qty_refunded ) ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Summary', 'nm-gift-registry' ); ?></h2>
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<tr>
			<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php
				/* translators: %s: wishlist type title */
				printf( esc_html__( 'Total items in %s', 'nm-gift-registry' ), esc_html( nmgr_get_type_title() ) );
				?>
			</th>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php
				echo wp_kses_post( $wishlist->get_items_quantity_count() );
				?>
			</td>
		</tr>
		<tr>
			<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php esc_html_e( 'Total items purchased', 'nm-gift-registry' ); ?>
			</th>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php
				echo wp_kses_post( $wishlist->get_items_purchased_quantity_count() );
				?>
			</td>
		</tr>
	</table>
</div>
