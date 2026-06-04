<?php

/**
 * Sync
 */

namespace NMGRCF\Lib;

use NMGRCF\Events\UpdateOrderItemMeta,
		NMGRCF\Events\UpdateCrowdfundReceivedWalletAmountColumns;

defined( 'ABSPATH' ) || exit;

class Upgrader {

	public static $old_version;

	public static function run() {
		self::$old_version = get_option( 'nmgrcf_version' );

		if ( !self::$old_version ) {
			return;
		}

		/**
		 * Run upgrades on 'init' instead of 'admin_init' so that they can also run on frontend
		 * Hook to priority 90 to run after nmgr actions have been run
		 */
		add_action( 'init', [ __CLASS__, 'init' ], 90 );
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

	public static function update_datatabase_4_4_0() {
		global $wpdb;

		$items_table = "{$wpdb->prefix}nmgr_wishlist_items";
		$itemmeta_table = "{$wpdb->prefix}nmgr_wishlist_itemmeta";

		if ( !$wpdb->get_var( "SHOW TABLES LIKE '$items_table'" ) ) {
			return;
		}

		$keys = [
			'crowdfunded',
			'crowdfund_data',
			'crowdfund_reference',
			'credits_to_wallet',
			'debits_from_wallet',
		];

		foreach ( $keys as $new_key ) {
			$old_key = 'nmgrcf_' . $new_key;

			if ( $wpdb->get_var( "SELECT meta_key FROM $itemmeta_table WHERE meta_key = '$old_key' LIMIT 1" ) ) {

				if ( in_array( $new_key, [ 'crowdfund_reference', 'debits_from_wallet', 'credits_to_wallet' ] ) &&
					!$wpdb->query( "SHOW columns from $items_table LIKE '$new_key'" ) ) {
					$wpdb->query( "ALTER TABLE $items_table ADD `$new_key` LONGTEXT NULL" );
				}

				$wpdb->query( "UPDATE $items_table AS a INNER JOIN $itemmeta_table AS b ON a.wishlist_item_id = b.wishlist_item_id SET a.$new_key = b.meta_value WHERE b.meta_key = '$old_key'" );

				$wpdb->query( "DELETE FROM $itemmeta_table WHERE meta_key = '$old_key'" );
			}
		}
	}

	public static function _4_5_0() {
		self::update_datatabase_4_4_0();
		(new UpdateCrowdfundReceivedWalletAmountColumns )->run();
		(new UpdateOrderItemMeta )->run();
		delete_option( 'nmgrcf_updated_database' );
		delete_option( 'nmgrcf_has_old_version' );
		delete_option( 'nmgrcf_admin_show_send_free_contributions_notice' );
		delete_option( 'nmgrcf_frontend_show_send_free_contributions_notice' );
	}

}
