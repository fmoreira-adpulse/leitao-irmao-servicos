<?php
/**
 * The template for displaying a wishlist's content in the single-nm_gift_registry.php template
 *
 * We hide post_class() on is_page() which checks for the gift registry or wishlist page to
 * improve performance, and only display it on is_singular() which is used when the wishlist is displayed
 * using the default post type archive singular page (this is a legacy view not currently used by the plugin)
 * @deprecated since version 4.7.0
 * @version 4.7.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;

nmgr_overridden_notice( __FILE__, '4.7.0' );

if ( !isset( $wishlist ) ) {
	$wishlist = nmgr_get_wishlist( get_the_ID(), true );
}

do_action( 'nmgr_before_single', $wishlist );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="nmgr-<?php the_ID(); ?>" <?php !is_page() ? post_class() : ''; ?>>

	<?php
	do_action( 'nmgr_wishlist', $wishlist );
	?>
</div>

<?php do_action( 'nmgr_after_single', $wishlist ); ?>

