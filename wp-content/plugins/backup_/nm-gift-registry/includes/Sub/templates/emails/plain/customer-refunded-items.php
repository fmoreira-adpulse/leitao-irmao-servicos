<?php

defined( 'ABSPATH' ) || exit;

/* translators: %s: recipient's name */
echo sprintf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) ) . "\n\n";

/* translators: 1,3: wishlist type title, 2: customer full name */
echo sprintf( esc_html__( 'Some items purchased for your %1$s by %2$s have been refunded. You no longer have these items in your %3$s.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() ),
	'<strong>' . esc_html( $order_customer_name ) . '</strong>',
	esc_html( nmgr_get_type_title() )
 ) . "\n\n";

echo esc_html__( 'Here are the details of the refund:', 'nm-gift-registry' ) . "\n\n";

\NMGR\Sub\Mailer::show_refund_details( $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
