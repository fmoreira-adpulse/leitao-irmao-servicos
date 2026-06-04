<?php

/**
 * The template for displaying wishlist archive results
 * @deprecated since version 4.7.0
 * @version 4.7.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;

nmgr_overridden_notice( __FILE__, '4.7.0' );

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

if ( !is_nmgr_wishlist_page( 'archive' ) ) {
	echo nmgr_get_wishlist_template();
} else {
	nmgr_archive_loop( null, nmgr_get_archive_template_args() );
}

do_action( 'woocommerce_after_main_content' );

do_action( 'nmgr_sidebar' );

get_footer( 'shop' );


