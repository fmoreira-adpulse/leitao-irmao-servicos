<?php

defined( 'ABSPATH' ) || exit;

/* translators: %s: recipient's name */
echo sprintf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) ) . "\n\n";

/* translators: 1: wishlist type title, 2: wishlist title, 3: customer full name */
echo sprintf( esc_html__( 'Some items have been purchased for your %1$s %2$s by %3$s.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() ),
	'<strong>' . esc_html( $email->get_wishlist()->get_title() ) . '</strong>',
	'<strong>' . esc_html( $order_customer_name ) . '</strong>'
 ) . "\n\n";

/* translators: %s: date */
echo sprintf( esc_html__( 'Here are the details of the purchase made on %s.', 'nm-gift-registry' ), wc_format_datetime( $order->get_date_paid() ) ) . "\n\n";

\NMGR\Sub\Mailer::show_order_details( $email );

if ( !empty( $custom_order_notice ) ) {
	echo wp_kses_post( $custom_order_notice );
}
// Maybe show wishlist messages
if ( !empty( $message ) ) {
	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	/* translators: %s: customer full name */
	echo sprintf( esc_html__( 'You have also been sent a message by %s.', 'nm-gift-registry' ),
		'<strong>' . esc_html( $order_customer_name ) . '</strong>'
	) . "\n\n";

	\NMGR\Sub\Mailer::show_message( $email );
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( 'Congratulations, we look forward to processing more purchases for you.', 'nm-gift-registry' ) . "\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

