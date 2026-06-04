<?php
defined( 'ABSPATH' ) || exit;
?>

<p>
	<?php
	/* translators: 1,2: wishlist type title */
	printf( esc_html__( 'A %1$s has just been fulfilled on your site. All the items in the %2$s have been fully purchased.', 'nm-gift-registry' ),
		esc_html( nmgr_get_type_title() ),
		esc_html( nmgr_get_type_title() )
	);
	?>
</p>

<?php
\NMGR\Sub\Mailer::show_wishlist_details( $email );
