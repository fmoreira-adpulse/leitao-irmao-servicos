<?php

defined( 'ABSPATH' ) || exit;

/* translators: %s: recipient's name */
echo sprintf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) ) . "\n\n";

/* translators: 1: customer billing full name, 2: wishlist type title */
echo sprintf( esc_html__( 'You have just received a message from %1$s who has ordered some items for your %2$s.', 'nm-gift-registry' ),
	'<strong>' . esc_html( $order_customer_name ) . '</strong>',
	esc_html( nmgr_get_type_title() ) ) . "\n\n";

\NMGR\Sub\Mailer::show_message( $email );

if ( !empty( $message_object->items_ordered ) ) {
	echo esc_html( wc_strtoupper( __( 'Items ordered', 'nm-gift-registry' ) ) ) . "\n\n";

	foreach ( $message_object->items_ordered as $item ) {
		echo wp_kses_post( "{$item[ 'name' ]} &times; {$item[ 'quantity' ]}" ) . "\n";
	}
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
