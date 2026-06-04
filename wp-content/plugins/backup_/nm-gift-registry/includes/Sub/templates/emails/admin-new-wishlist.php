<?php
defined( 'ABSPATH' ) || exit;
?>

<p>
	<?php
	/* translators: %s: wishlist type title */
	printf( esc_html__( 'A new %s has been created on your site.', 'nm-gift-registry' ),
		esc_html( nmgr_get_type_title() ) );
	?>
</p>

<?php
NMGR\Sub\Mailer::show_wishlist_details( $email );
