<?php
defined( 'ABSPATH' ) || exit;
?>

<p>
	<?php
	/* translators: %s: recipient's name */
	printf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) );
	?>
</p>

<p>
	<?php
	/* translators: 1: wishlist type title, 2:wishlist title, 3: customer full name */
	printf( esc_html__( 'Some items have been ordered for your %1$s %2$s by %3$s.', 'nm-gift-registry' ),
		esc_html( nmgr_get_type_title() ),
		'<strong>' . esc_html( $email->get_wishlist()->get_title() ) . '</strong>',
		'<strong>' . esc_html( $order_customer_name ) . '</strong>'
	);
	?>
</p>

<p>
	<?php
	printf( esc_html__( 'Here are the details of the order placed on %s.', 'nm-gift-registry' ),
		wc_format_datetime( $order->get_date_created() )
	);
	?>
</p>

<?php
\NMGR\Sub\Mailer::show_order_details( $email );

if ( !empty( $custom_order_notice ) ) {
	echo wp_kses_post( $custom_order_notice );
}

// Maybe show wishlist messages
if ( !empty( $message ) ) {
	?>
	<div style="margin-bottom: 40px;">
		<p>
			<?php
			printf( esc_html__( 'You have also been sent a message by %s.', 'nm-gift-registry' ),
				'<strong>' . esc_html( $order_customer_name ) . '</strong>'
			);
			?>
		</p>

		<?php \NMGR\Sub\Mailer::show_message( $email ); ?>
	</div>
	<?php
}
?>

<p style="margin-bottom: 40px;">
	<?php esc_html_e( 'We look forward to fulfilling your order soon.', 'nm-gift-registry' ); ?>
</p>

