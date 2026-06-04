<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Integrations {

	public static function run() {
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'check_cart_for_multiple_addresses' ) );
	}

	public static function check_cart_for_multiple_addresses() {
		if ( is_nmgr_cart_shipping_to_wishlist_address() && function_exists( 'nm_multi_addresses' ) ) {
			add_filter( 'nm_multi_addresses_is_multiple_shipping_enabled', '__return_false' );
			remove_action( 'woocommerce_before_checkout_shipping_form', [ 'NM_Multi_Addresses\Checkout', 'show_shipping_select_btn' ] );
		}
	}

}
