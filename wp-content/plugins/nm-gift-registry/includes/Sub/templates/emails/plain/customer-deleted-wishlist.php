<?php

defined( 'ABSPATH' ) || exit;

/* translators: %s: recipient's name */
echo sprintf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) ) . "\n\n";

/* translators: 1: wishlist type title, 2: site title */
echo sprintf( esc_html__( 'You have just deleted your %1$s on %2$s.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() ),
	esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) )
 ) . "\n\n";

/* translators: %s: wishlist type title */
echo sprintf( esc_html__( 'If you did this accidentally, please contact the site administrator to restore your %s, otherwise just ignore this email.', 'nm-gift-registry' ), esc_html( nmgr_get_type_title() ) ) . "\n\n";

/* translators: %s: wishlist type title */
echo sprintf( esc_html__( 'We have fantastic products in store with which you can always create a %s for your special occasion as well as that of your friends and family. Feel free to take advantage of this and contact us if we can help.', 'nm-gift-registry' ), esc_html( nmgr_get_type_title() ) ) . "\n\n";


echo esc_html__( 'We are always at your service.', 'nm-gift-registry' ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

