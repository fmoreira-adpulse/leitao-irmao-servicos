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
	/* translators: 1: wishlist type title, 2: site title */
	printf( esc_html__( 'Thanks for creating a %1$s on %2$s.', 'nm-gift-registry' ),
		esc_html( nmgr_get_type_title() ),
		esc_html( $email->get_blogname() )
	);
	?>
</p>

<?php if ( 'publish' === $email->get_wishlist()->get_status() ) : ?>
	<p>
		<?php
		/* translators: 1: wishlist type title, 2: wishlist permalink */
		printf( esc_html__( 'You can view your %1$s on the site the same way your guests would see it at: %2$s.', 'nm-gift-registry' ),
			esc_html( nmgr_get_type_title() ),
			make_clickable( esc_url( $email->get_wishlist()->get_permalink() ) )
		);
		?>
	</p>
<?php endif; ?>

<p>
	<?php
	/* translators: %s: wishlist type title */
	printf( esc_html__( 'We have fantastic products in store that would make good additions to your %s. Visit the link below to start shopping for items now.', 'nm-gift-registry' ),
		esc_html( nmgr_get_type_title() )
	);
	?>
</p>

<p style="text-align: center;padding-top:20px;padding-bottom: 20px;"><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button alt"><?php esc_html_e( 'Shop for items', 'nm-gift-registry' ); ?></a></p>

<?php
NMGR\Sub\Mailer::show_wishlist_details( $email );
