<?php

defined( 'ABSPATH' ) || exit;

/* translators: %s: recipient's name */
echo sprintf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) ) . "\n\n";

echo esc_html__( 'Congratulations.', 'nm-gift-registry' ) . "\n\n";

/* translators: %s: wishlist type title */
echo sprintf( esc_html__( 'All items in your %s have now been fully purchased.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() ) ) . "\n\n";

echo esc_html__( 'If applicable, we will be sending them to you using the shipping details you have provided.', 'nm-gift-registry' ) . "\n\n";

echo esc_html__( 'Please contact us if you have any questions or need further help.', 'nm-gift-registry' ) . "\n\n";

echo esc_html__( 'We are always at your service.', 'nm-gift-registry' ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

