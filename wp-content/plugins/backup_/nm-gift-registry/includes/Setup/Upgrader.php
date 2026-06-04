<?php

namespace NMGR\Setup;

use NMGR\Events\SetWishlistTerms,
		NMGR\Events\UpdateOrderItemMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Upgrader class
 * works for both lite and pro versions
 */
class Upgrader {

	public static $old_version;

	public static function run() {
		self::$old_version = get_option( nmgr()->prefix . '_version' );
		/**
		 * Run upgrades on 'init' instead of 'admin_init' so that they can also run on frontend
		 * Hook to priority 70 to run after events have been registed in \NMGR_Events::run()
		 * And priority 70 is standard plugin init priority.
		 */
		add_action( 'init', [ __CLASS__, 'init' ], 70 );
	}

	public static function init() {
		$methods = get_class_methods( __CLASS__ );
		foreach ( $methods as $method ) {
			if ( 0 === strpos( $method, '_' ) ) {
				$version = ltrim( str_replace( '_', '.', $method ), '.' );
				if ( version_compare( self::$old_version, $version, '<' ) ) {
					self::$method();
				}
			}
		}
	}

	public static function _4_0_0() {
		(new SetWishlistTerms )->run();
		delete_metadata( 'user', 0, 'nmgr_enable_wishlist', '', true );

		$existing_settings = get_option( 'nmgr_settings' );
		if ( $existing_settings ) {
			/**
			 * Option key 'add_to_wishlist_single' is deprecated.
			 * If it's value is already set, set it for the option key 'add_to_wishlist_button_position_single'
			 * remove the deprecated option key in a later version
			 * @since 4.0.0
			 */
			if ( array_key_exists( 'add_to_wishlist_single', $existing_settings ) &&
				!$existing_settings[ 'add_to_wishlist_single' ] ) {
				$existing_settings[ 'add_to_wishlist_button_position_single' ] = '';
				$update = true;
			}

			/**
			 * Option key 'add_to_wishlist_archive' is deprecated.
			 * If it's value is already set, set it for the option key 'add_to_wishlist_button_position_archive'
			 * remove the deprecated option key in a later version
			 * @since 4.0.0
			 */
			if ( array_key_exists( 'add_to_wishlist_archive', $existing_settings ) &&
				!$existing_settings[ 'add_to_wishlist_archive' ] ) {
				$existing_settings[ 'add_to_wishlist_button_position_archive' ] = '';
				$update = true;
			}

			if ( isset( $update ) && $update ) {
				update_option( 'nmgr_settings', $existing_settings );
			}
		}
	}

	public static function _4_2_0() {
		wp_clear_scheduled_hook( 'nmgr_delete_guest_wishlists' );
		wp_clear_scheduled_hook( 'nmgr_set_expired_wishlists' );
		wp_clear_scheduled_hook( 'nmgr_gift-registry_SetExpiredWishlists' );
	}

	public static function _4_3_0() {
		(new UpdateOrderItemMeta )->run();
	}

	/**
	 * Version 4.4.0
	 */
	public static function _4_4_0() {
		global $wpdb;

		/**
		 * Add _nmgr_fulfilled wishlist meta_key to replace _date_fulfilled
		 * @todo Remove _date_fulfilled meta_key in later version.
		 */
		$wpdb->query( "
		INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
			SELECT pm.post_id, '_nmgr_fulfilled', '1' FROM $wpdb->postmeta AS pm
			INNER JOIN $wpdb->posts AS pp	ON pm.post_id = pp.ID
			WHERE pp.post_type = 'nm_gift_registry'
			AND pm.meta_key = '_date_fulfilled'
			AND pm.meta_value != ''
			AND NOT EXISTS (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_nmgr_fulfilled' AND post_id = pm.post_id)
		" );

		/**
		 * Change '_nmgr_wishlist_id' order meta key to 'nmgr_wishlist_id'
		 */
		$wpdb->query( "
		UPDATE {$wpdb->prefix}woocommerce_order_itemmeta
			SET meta_key = 'nmgr_wishlist_id'
			WHERE meta_key = '_nmgr_wishlist_id'
		" );

		/**
		 * Change '_nmgr_item_id' order meta key to 'nmgr_item_id'
		 */
		$wpdb->query( "
		UPDATE {$wpdb->prefix}woocommerce_order_itemmeta
			SET meta_key = 'nmgr_item_id'
			WHERE meta_key = '_nmgr_item_id'
		" );
	}

	public static function _4_5_0() {
		wp_clear_scheduled_hook( 'nmgr_gift-registry_DeleteGuestWishlists' );
		wp_clear_scheduled_hook( 'nmgr_wishlist_DeleteGuestWishlists' );
	}

}
