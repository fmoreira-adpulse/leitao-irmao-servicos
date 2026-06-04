<?php
defined( 'ABSPATH' ) || exit;
?>

<div style="margin-bottom: 40px;">
	<p>
<?php
/* translators: %s: recipient's name */
printf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) );
?>
	</p>

	<p>
<?php
/* translators: 1: wishlist type title, 2: site title */
printf( esc_html__( 'You have just deleted your %1$s on %2$s.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() ),
	esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) )
);
?>
	</p>

	<p>
<?php
/* translators: %s: wishlist type title */
printf( esc_html__( 'If you did this accidentally, please contact the site administrator to restore your %s, otherwise just ignore this email.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() )
);
?>
	</p>

	<p>
<?php
/* translators: %s: wishlist type title */
printf( esc_html__( 'We have fantastic products in store with which you can always create a %s for your special occasion as well as that of your friends and family. Feel free to take advantage of this and contact us if we can help.', 'nm-gift-registry' ),
	esc_html( nmgr_get_type_title() )
);
?>
	</p>

	<p><?php esc_html_e( 'We are always at your service.', 'nm-gift-registry' ); ?></p>
</div>
<?php
