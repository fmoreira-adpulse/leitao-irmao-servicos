<?php

defined( 'ABSPATH' ) || exit;

/* translators: %s: recipient's name */
echo sprintf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) ) . "\n\n";

/* translators: 1: wishlist type title, 2: site title */
echo sprintf( esc_html__( 'Thanks for creating a %1$s on %2$s.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() ),
	esc_html( $email->get_blogname() ) ) . "\n\n";

if ( 'publish' === $email->get_wishlist()->get_status() ) {
	/* translators: 1: wishlist type title, 2: wishlist permalink */
	echo sprintf( esc_html__( 'You can view your %1$s on the site the same way your guests would see it at: %2$s.', 'nm-gift-registry' ),
		esc_html( nmgr_get_type_title() ),
		esc_url( $email->get_wishlist()->get_permalink() ) ) . "\n\n";
}

/* translators: %s: wishlist type title */
echo sprintf( esc_html__( 'We have fantastic products in store that would make good additions to your %s. Visit the link below to start shopping for items now.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() ) ) . "\n\n";

echo esc_url( wc_get_page_permalink( 'shop' ) ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
