<?php

defined( 'ABSPATH' ) || exit;

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

	$total = $total + $order->get_line_subtotal( $item );

	echo wp_kses_post( apply_filters( 'nmgr_order_items_details_name', $item->get_name(), $item, false ) );

	if ( $product->get_sku() ) {
		echo wp_kses_post( ' (#' . $product->get_sku() . ')' );
	}

	echo ' X ' . wp_kses_post( apply_filters( 'nmgr_order_items_details_quantity', $item->get_quantity() ) );

	echo ' = ' . wp_kses_post( $order->get_formatted_line_subtotal( $item ) );

	echo "\n\n";
}

echo "==========\n\n";

$total_price = wc_price( $total, array( 'currency' => $order->get_currency() ) );
echo wp_kses_post( __( 'Total', 'nm-gift-registry' ) . "\t " . $total_price ) . "\n\n";

