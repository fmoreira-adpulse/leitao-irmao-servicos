<?php
defined( 'ABSPATH' ) || exit;
?>

<p>
	<?php
	/* translators: %s: recipient's name */
	printf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) );
	?>
</p>

<p><?php esc_html_e( 'Congratulations.', 'nm-gift-registry' ); ?></p>

<p>
	<?php
	/* translators: %s: wishlist type title */
	printf( esc_html__( 'All items in your %s have now been fully purchased.', 'nm-gift-registry' ),
		esc_html( nmgr_get_type_title() )
	);
	?>
</p>

<p><?php esc_html_e( 'If applicable, we will be sending them to you using the shipping details you have provided.', 'nm-gift-registry' ); ?></p>

<p><?php esc_html_e( 'Please contact us if you have any questions or need further help.', 'nm-gift-registry' ); ?></p>

<p><?php esc_html_e( 'We are always at your service.', 'nm-gift-registry' ); ?></p>

<?php
