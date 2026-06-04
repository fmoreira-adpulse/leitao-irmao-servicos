<?php

namespace NMGR\Deprecated;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

	public static function run() {
		add_action( 'init', array( __CLASS__, 'add_shortcodes' ), 70 );
		add_filter( 'do_shortcode_tag', [ __CLASS__, 'show_deprecated_notice' ], 10, 2 );
	}

	public static function show_deprecated_notice( $output, $shortcode ) {
		if ( in_array( $shortcode, self::shortcodes() ) ) {
			_deprecated_function( "shortcode [$shortcode]", '4.7.0' );
		}
		return $output;
	}

	private static function shortcodes() {
		return array(
			'nmgr_get_account_template' => 'nmgr_account',
			'nmgr_get_account_wishlist_template' => 'nmgr_account_wishlist',
			'nmgr_get_profile_template' => 'nmgr_profile',
			'nmgr_get_items_template' => 'nmgr_items',
			'nmgr_get_images_template' => 'nmgr_images',
			'nmgr_get_shipping_template' => 'nmgr_shipping',
			'nmgr_get_messages_template' => 'nmgr_messages',
			'nmgr_get_orders_template' => 'nmgr_orders',
			'nmgr_get_settings_template' => 'nmgr_settings',
			'nmgr_get_share_template' => 'nmgr_share',
		);
	}

	public static function add_shortcodes() {
		foreach ( self::shortcodes() as $function => $shortcode ) {
			if ( function_exists( $function ) ) {
				add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
			}
		}
	}

}
