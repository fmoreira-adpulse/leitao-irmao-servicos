<?php

/**
 * Sync
 */

namespace NMGRCF\Events;

defined( 'ABSPATH' ) || exit;

use NMGRCF\Lib\Scheduler;

/**
 * @access private
 */
class UpdateOrderItemMeta extends Scheduler {

	protected $prefix = 'nmgrcf';
	protected $cache_data = false;
	protected $batch_processing = true;

	protected function task( $row ) {
		global $wpdb;

		$n = maybe_unserialize( $row->meta_value );
		$d = [];
		if ( isset( $n[ 'wishlist_item_id' ] ) ) {
			$d[] = '(' . esc_sql( $row->order_item_id ) . ',"nmgrcf_item_id","' . esc_sql( $n[ 'wishlist_item_id' ] ) . '")';
		}

		if ( isset( $n[ 'wishlist_id' ] ) ) {
			$d[] = '(' . esc_sql( $row->order_item_id ) . ',"nmgrcf_wishlist_id","' . esc_sql( $n[ 'wishlist_id' ] ) . '")';
		}

		if ( !empty( $d ) ) {
			$result = $wpdb->query(
				"INSERT INTO {$this->table()} ( order_item_id, meta_key, meta_value )
							VALUES " . implode( ',', $d )
			);

			if ( $result ) {
				$wpdb->delete( $this->table(), [ 'meta_id' => $row->meta_id ], [ '%d' ] );
			}
		}
	}

	private function table() {
		global $wpdb;
		return $wpdb->prefix . 'woocommerce_order_itemmeta';
	}

	protected function get_batch_data() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->table()} WHERE meta_key = 'nmgr_cf' LIMIT 100" );
	}

}
