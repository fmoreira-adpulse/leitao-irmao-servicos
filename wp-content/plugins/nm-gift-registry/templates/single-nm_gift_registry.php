<?php

/**
 * Template for displaying all single wishlists
 * @deprecated since version 4.7.0
 * @version 4.7.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;

nmgr_overridden_notice( __FILE__, '4.7.0' );

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

while ( have_posts() ) :
	the_post();

	nmgr_get_wishlist_template( get_the_ID(), true );

endwhile;

do_action( 'woocommerce_after_main_content' );


do_action( 'nmgr_sidebar' );

get_footer( 'shop' );


