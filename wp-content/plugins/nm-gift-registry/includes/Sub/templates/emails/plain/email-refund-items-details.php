<?php

defined( 'ABSPATH' ) || exit;

foreach ( $wishlist_item_ids_to_qtys as $item_id => $qty_refunded ) {
	$item = $wishlist->get_item( $item_id );

	if ( !$item ) {
		continue;
	}

	$product = $item->get_product();

	if ( !$product ) {
		continue;
	}

	echo wp_kses_post( apply_filters( 'nmgr_refund_items_details_name', $product->get_name(), $item, false ) );

	if ( $product->get_sku() ) {
		echo wp_kses_post( ' (#' . $product->get_sku() . ')' );
	}

	echo "\n";

	echo esc_html__( 'Purchased Quantity', 'nm-gift-registry' ) . ":\t " . wp_kses_post( apply_filters( 'nmgr_refund_items_details_purchased_quantity', $item->get_purchased_quantity() ) );

	echo "\n";

	echo esc_html__( 'Refunded Quantity', 'nm-gift-registry' ) . ":\t " . wp_kses_post( apply_filters( 'nmgr_refund_items_details_refunded_quantity', $qty_refunded ) );

	echo "\n\n";
}

echo "==========\n\n";

echo esc_html( wc_strtoupper( __( 'Summary', 'nm-gift-registry' ) ) ) . "\n\n";

/* translators: %s: wishlist type title */
echo sprintf( esc_html__( 'Total items in %s', 'nm-gift-registry' ), esc_html( nmgr_get_type_title() ) )
 . ":\t "
 . absint( $wishlist->get_items_quantity_count() )
 . "\n\n";

echo esc_html__( 'Total items purchased', 'nm-gift-registry' ) . ":\t " . absint( $wishlist->get_items_purchased_quantity_count() ) . "\n\n";

