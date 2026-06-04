<?php
defined( 'ABSPATH' ) || exit;
?>

<p>
	<?php
	/* translators: %s: recipient's name */
	printf( esc_html__( 'Hi %s,', 'nm-gift-registry-crowdfunding' ), esc_html( $email->get_recipient_name() ) );
	?>
</p>

<p>
	<?php
	/* translators: 1: refunded amount, 2: wishlist type title, 3: wishlist title, 4: customer full name */
	printf( esc_html__( '%1$s contributed to your %2$s %3$s by %4$s has been refunded. You no longer have this amount as part of your free contributions.', 'nm-gift-registry-crowdfunding' ),
		'<strong>' . wp_kses_post( wc_price( $refunded_amount ) ) . '</strong>',
		esc_html( nmgr_get_type_title() ),
		'<strong>' . esc_html( $wishlist->get_title() ) . '</strong>',
		'<strong>' . esc_html( $order_customer_name ) . '</strong>'
	);
	?>
</p>