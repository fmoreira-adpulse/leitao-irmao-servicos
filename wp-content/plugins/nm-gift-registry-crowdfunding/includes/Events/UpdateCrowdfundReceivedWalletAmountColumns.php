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
class UpdateCrowdfundReceivedWalletAmountColumns extends Scheduler {

	protected $prefix = 'nmgrcf';
	protected $cache_data = false;
	protected $batch_processing = true;

	protected function task( $row ) {
		global $wpdb;

		$cf_ref = ( array ) maybe_unserialize( $row->crowdfund_reference );
		$cf_received = !empty( $cf_ref ) ? array_sum( wp_list_pluck( $cf_ref, 'purchased_amount' ) ) : null;

		$credits = array_sum( ( array ) maybe_unserialize( $row->credits_to_wallet ) );
		$debits = array_sum( ( array ) maybe_unserialize( $row->debits_from_wallet ) );
		$wallet_amt = !$credits && !$debits ? null : nmgrcf_round( $debits ) - nmgrcf_round( $credits );

		$wpdb->update(
			$this->table(),
			[
				'crowdfund_received' => $cf_received ? $cf_received : null,
				'wallet_amount' => $wallet_amt,
				'crowdfund_reference' => null,
				'credits_to_wallet' => null,
				'debits_from_wallet' => null,
			],
			[ 'wishlist_item_id' => $row->wishlist_item_id ]
		);
	}

	private function table() {
		global $wpdb;
		return $wpdb->prefix . 'nmgr_wishlist_items';
	}

	protected function get_batch_data() {
		global $wpdb;
		return $wpdb->get_results( "
			SELECT wishlist_item_id, crowdfund_reference, credits_to_wallet, debits_from_wallet
			FROM {$this->table()}
			WHERE crowdfund_reference != '' OR credits_to_wallet != '' OR debits_from_wallet != ''
			LIMIT 100
			" );
	}

	protected function complete() {
		global $wpdb;

		$columns = [
			'crowdfund_reference',
			'credits_to_wallet',
			'debits_from_wallet',
		];

		foreach ( $columns as $col ) {
			$wpdb->query( "ALTER TABLE {$this->table()} DROP COLUMN $col" );
		}
	}

}
