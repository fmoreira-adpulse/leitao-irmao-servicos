<?php

/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Wishlist extends NMGR\Sub\Wishlist {

	/*
	  |--------------------------------------------------------------------------
	  | Getters
	  |--------------------------------------------------------------------------
	 */

	public function get_crowdfund_order_ids( $args = [] ) {
		global $wpdb;

		if ( empty( $args ) ) {
			$ids = $this->cache_get( 'crowdfund_order_ids' );
			if ( false !== $ids ) {
				return $ids;
			}
		}

		$defaults = [
			'limit' => null,
			'get' => 'col',
			'page' => 0, // 'page' is 'offset'
			'count' => null, // whether to return the count of items
		];

		$p_args = wp_parse_args( $args, $defaults );
		$limit = max( 0, ( int ) $p_args[ 'limit' ] );
		$offset = $p_args[ 'page' ] ? max( 0, (( int ) $p_args[ 'page' ] - 1) * $limit ) : 0;
		$limit_sql = $limit ? $wpdb->prepare( "LIMIT %d", $limit ) : '';
		$offset_sql = $limit ? 'OFFSET ' . $offset : '';
		$select_sql = !empty( $args[ 'count' ] ) ? 'COUNT(DISTINCT posts.ID)' : 'DISTINCT posts.ID';

		$orders_table = nmgrcf_orders_table();
		$status_key = false !== strpos( $orders_table, 'posts' ) ? 'post_status' : 'status';
		$statuses = array_merge( wc_get_is_paid_statuses(), [ 'refunded' ] );

		$sql = "
			SELECT $select_sql
			FROM $orders_table AS posts
			LEFT JOIN {$wpdb->prefix}wc_order_product_lookup AS opl ON posts.ID = opl.order_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as oim ON opl.order_item_id = oim.order_item_id
			WHERE oim.meta_key = 'nmgrcf_wishlist_id'
			AND oim.meta_value = {$this->get_id()}
			AND posts.$status_key IN ('wc-" . implode( "','wc-", array_map( 'esc_sql', $statuses ) ) . "')
			ORDER BY posts.ID DESC
			$limit_sql
			$offset_sql
			";

		$ids = !empty( $args[ 'count' ] ) ? $wpdb->get_var( $sql ) : $wpdb->get_col( $sql );

		if ( empty( $args ) ) {
			$this->cache_set( 'crowdfund_order_ids', $ids );
		}

		return $ids;
	}

	/**
	 * Get the real total unpurchased amount for wallet transfers
	 *
	 * The total unpurchased amount is taken from normal items and crowdfunded items.
	 * This amount takes into account the unpurchased amount from normal items only if
	 * wallet transfer is enabled for normal items.
	 */
	public function get_real_total_unpurchased_amount() {
		if ( $this->is_wallet_transfer_enabled() && !nmgr_get_option( 'enable_wallet_transfer_all' ) ) {
			$amt = $this->get_crowdfund_amount_left();
		} else {
			$amt = $this->get_total_unpurchased_amount();
		}
		return nmgrcf_round( $amt );
	}

	public function get_total_purchased_amount() {
		return nmgrcf_round( parent::get_total_purchased_amount() ) +
			$this->get_crowdfund_amount_available();
	}

	public function get_purchased_amount() {
		return nmgrcf_round( parent::get_purchased_amount() ) + $this->get_wallet_amount_for_normal_items();
	}

	/**
	 * Get the balance in the wallet from credits and debits to the wallet
	 * @return float
	 */
	public function get_wallet_credit_debit_balance() {
		return nmgrcf_round( get_post_meta( $this->get_id(), 'nmgrcf_wallet', true ) );
	}

	/**
	 * Get the total balance in the wallet
	 */
	public function get_wallet_balance() {
		return $this->get_free_contributions_amount_available() + $this->get_wallet_credit_debit_balance();
	}

	public function get_wallet_amount_for_normal_items() {
		global $wpdb;

		$amt = $this->cache_get( 'wallet_amount_for_normal_items' );

		if ( false === $amt ) {
			$amt = nmgrcf_round( $wpdb->get_var( "
					SELECT SUM(wallet_amount)
					FROM {$wpdb->prefix}nmgr_wishlist_items
					WHERE wishlist_id = {$this->get_id()} AND crowdfunded != 1
					" ) );
			$this->cache_set( 'wallet_amount_for_normal_items', $amt );
		}

		return $amt;
	}

	public function get_unpurchased_amount() {
		$normal_total = nmgrcf_round( $this->get_total() ) - $this->get_crowdfund_amount_needed();
		return max( $normal_total - nmgrcf_round( $this->get_purchased_amount() ), nmgrcf_round( 0 ) );
	}

	public function get_crowdfund_amount_needed() {
		global $wpdb;

		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return ( float ) 0;
		}

		$amt = $this->cache_get( 'crowdfund_amount_needed' );

		if ( false === $amt ) {
			$amt = nmgrcf_round(
				$wpdb->get_var( "
					SELECT SUM(pl.max_price * it.quantity) AS total_cost
					FROM {$wpdb->prefix}nmgr_wishlist_items AS it
					INNER JOIN {$wpdb->prefix}wc_product_meta_lookup AS pl ON (pl.product_id = it.product_or_variation_id)
					WHERE 1=1 AND it.wishlist_id = {$this->get_id()} AND it.crowdfunded = 1
					" )
			);
			$this->cache_set( 'crowdfund_amount_needed', $amt );
		}

		return $amt;
	}

	public function get_crowdfund_amount_received() {
		global $wpdb;

		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return ( float ) 0;
		}

		$amt = $this->cache_get( 'crowdfund_amount_received' );

		if ( false === $amt ) {
			$amt = nmgrcf_round( $wpdb->get_var( "
					SELECT SUM(crowdfund_received)
					FROM {$wpdb->prefix}nmgr_wishlist_items
					WHERE wishlist_id = {$this->get_id()} AND crowdfunded = 1
					" ) );
			$this->cache_set( 'crowdfund_amount_received', $amt );
		}

		return $amt;
	}

	public function get_crowdfund_amount_available() {
		global $wpdb;

		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return ( float ) 0;
		}

		$amt = $this->cache_get( 'crowdfund_amount_available' );

		if ( false === $amt ) {
			$amt = nmgrcf_round( $wpdb->get_var( "
					SELECT COALESCE(SUM(crowdfund_received), 0) + COALESCE(SUM(wallet_amount), 0)
					FROM {$wpdb->prefix}nmgr_wishlist_items
					WHERE wishlist_id = {$this->get_id()} AND crowdfunded = 1
					" ) );
			$this->cache_set( 'crowdfund_amount_available', $amt );
		}

		return $amt;
	}

	public function get_crowdfund_amount_left() {
		return max( $this->get_crowdfund_amount_needed() - $this->get_crowdfund_amount_available(), nmgrcf_round( 0 ) );
	}

	/**
	 * @return \NMGRCF_Wallet
	 */
	public function get_wallet() {
		return new NMGRCF_Wallet( $this );
	}

	/**
	 * Get the ids of coupons attached to this wishlist
	 */
	public function get_coupon_ids() {
		$coupon_ids = get_post_meta( $this->get_id(), 'nmgrcf_coupon_ids', true );
		return !empty( $coupon_ids ) ? $coupon_ids : array();
	}

	/**
	 * Get the ids of coupons created from wallet amount
	 * @param type $wishlist_id
	 * @return type
	 */
	public function get_wallet_coupon_ids() {
		$coupon_ids = get_post_meta( $this->get_id(), 'nmgrcf_wallet_coupon_ids', true );
		return !empty( $coupon_ids ) ? $coupon_ids : array();
	}

	/**
	 * Get the settings for making free contributions to a wishlist
	 *
	 * @return array
	 */
	public function get_free_contributions_settings() {
		$db_settings = get_post_meta( ( int ) $this->get_id(), 'free_contributions_settings', true );
		$zero = nmgrcf_round( 0 );
		$default_settings = array(
			'enabled' => 1,
			'minimum_amount' => $zero,
			'amount_needed' => $zero,
			'credited_to_wallet' => $zero,
		);

		return wp_parse_args( $db_settings, $default_settings );
	}

	/**
	 * Get the reference of all free contributions made to the wishlist
	 * @return array|false
	 */
	public function get_free_contributions_reference() {
		$ref = get_post_meta( $this->get_id(), 'free_contributions_reference', true );
		return is_array( $ref ) ? $ref : [];
	}

	/**
	 * Get the total amount of free contributions expected for a wishlist
	 */
	public function get_free_contributions_amount_needed() {
		return nmgrcf_round( $this->get_free_contributions_settings()[ 'amount_needed' ] );
	}

	/**
	 * Get the total amount of free contributions received for a wishlist
	 * @return float
	 */
	public function get_free_contributions_amount_received() {
		$amt_received = 0;
		$reference = $this->get_free_contributions_reference();
		if ( !empty( $reference ) ) {
			$amt_received = array_sum( wp_list_pluck( $reference, 'purchased_amount' ) );
		}
		return nmgrcf_round( $amt_received );
	}

	/**
	 * Get the amount left to be received of the total free contribution amount
	 * required for a wishlist
	 *
	 * Note that this function returns 0 if no total free contribution amount has been
	 * set by the wishlist owner. So it is best to check if a total free contribution
	 * amount has been set first before using this function to get the amount left of
	 * that total that has been received.
	 *
	 * @return float
	 */
	public function get_free_contributions_amount_left() {
		return max( $this->get_free_contributions_amount_needed() -
			$this->get_free_contributions_amount_received(), nmgrcf_round( 0 ) );
	}

	/**
	 * Get the real free contributions amount left to be received for a wishlist.
	 *
	 * This takes into account the total amount left to be purchased for the wishlist
	 * and ensures that the amount of free contributions left to be received does not
	 * exceed this amount.
	 *
	 * @return float
	 */
	public function get_real_free_contributions_amount_left() {
		$amt_left = $this->get_free_contributions_amount_left();
		$zero = nmgrcf_round( 0 );
		if ( $amt_left <= $zero && !$this->has_quantity_mismatch() ) {
			$amt_left = max( $this->get_wallet_fulfill_amount(), $zero );
		}
		return $amt_left;
	}

	/**
	 * Get the amount of free contributions that have been sent to the wallet.
	 *
	 * @param int $wishlist_id Wishlist id
	 * @return float
	 */
	public function get_free_contributions_credited_to_wallet() {
		$settings = $this->get_free_contributions_settings();
		return nmgrcf_round( isset( $settings[ 'credited_to_wallet' ] ) ? $settings[ 'credited_to_wallet' ] : 0 );
	}

	/**
	 * Get the amount of free contributions that is actually available to the wishlist owner.
	 *
	 * This is simply based on the amount of free contributions received, but it takes into
	 * account the amount of free contributions sent to the wallet and ignores it as it is
	 * no longer considered free contributions once in the wallet.
	 *
	 * @return float
	 */
	public function get_free_contributions_amount_available() {
		$received_amt = $this->get_free_contributions_amount_received();
		$wallet_amt = $this->get_free_contributions_credited_to_wallet();

		if ( !$received_amt || !$wallet_amt ) {
			return $received_amt;
		}

		return $received_amt - $wallet_amt;
	}

	/**
	 * This function is added to the child class in order to make sure that wishlist
	 * items that have their amounts credited to the wallet do not contribute to the
	 * purchased quantity count as their purchased quantities are artificially set to zero
	 * even though in the database they have positive values
	 */
	public function get_items_purchased_quantity_count() {
		global $wpdb;

		$val = $this->cache_get( 'items_purchased_quantity_count' );

		if ( false === $val ) {
			$val = ( int ) $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM(purchased_quantity)
			FROM {$wpdb->prefix}nmgr_wishlist_items
			WHERE wishlist_id = %d
			AND (ISNULL(wallet_amount) OR wallet_amount > 0)
				",
						$this->get_id()
					) );

			$this->cache_set( 'items_purchased_quantity_count', $val );
		}

		return apply_filters( 'nmgr_wishlist_item_purchased_count', $val, $this );
	}

	/*
	  |--------------------------------------------------------------------------
	  | Conditionals
	  |--------------------------------------------------------------------------
	 */

	/**
	 * This function is added to the child class in order to make sure that wishlist
	 * items that have their amounts credited to the wallet are not recorded as fulfilled
	 * since their purchased quantities are artificially set as zero even though in the
	 * database they have positive values
	 */
	public function is_fulfilled() {
		global $wpdb;

		$not_fulfilled = $this->cache_get( 'is_fulfilled' );

		if ( false === $not_fulfilled ) {
			$not_fulfilled = $wpdb->get_var( "
			SELECT IF (
				(SELECT wishlist_item_id FROM {$wpdb->prefix}nmgr_wishlist_items
				WHERE wishlist_id = {$this->get_id()}
				LIMIT 1),
				(SELECT wishlist_item_id FROM {$wpdb->prefix}nmgr_wishlist_items
				WHERE wishlist_id = {$this->get_id()}
				AND (purchased_quantity < quantity OR (purchased_quantity > 0 AND wallet_amount < 0))
				LIMIT 1),
				'no_items'
			);
		" );

			$this->cache_set( 'is_fulfilled', $not_fulfilled );
		}

		return 'no_items' === $not_fulfilled ? false : ( bool ) !$not_fulfilled;
	}

	/**
	 * Check if free contributions are enabled for the wishlist
	 *
	 * @return boolean
	 */
	public function is_free_contributions_enabled() {
		if ( !is_nmgrcf_free_contributions_enabled() ) {
			return false;
		}

		$settings = $this->get_free_contributions_settings();
		return $settings[ 'enabled' ] ? true : false;
	}

	/**
	 * Whether the wishlist still needs to accept free contributions
	 * @return boolean True if there are free contribution amounts left to receive
	 */
	public function needs_free_contributions() {
		$amt_needed = $this->get_free_contributions_amount_needed();
		$amt_left = $this->get_free_contributions_amount_left();
		$zero = nmgrcf_round( 0 );
		$val = true;

		if ( ($amt_needed > $zero && $amt_left <= $zero) ||
			($amt_needed <= $zero && $this->has_fulfill_amount()) ) {
			$val = false;
		}

		return $val;
	}

	/**
	 * Check if the wishlist has a normal (non-crowdfunded) item
	 */
	function has_normal_item() {
		global $wpdb;

		$val = $this->cache_get( 'has_normal_item' );

		if ( false === $val ) {
			$val = $wpdb->get_var( "
			SELECT COUNT(*) FROM {$wpdb->prefix}nmgr_wishlist_items
				WHERE wishlist_id = {$this->get_id()}
					AND crowdfunded != 1 LIMIT 1
			" );
			$this->cache_set( 'has_normal_item', $val );
		}
		return ( bool ) $val;
	}

	/**
	 * Whether wallet transfers are enabled for the wishlist items
	 *
	 * @return boolean True if the wishlist has crowdfunded items or if normal items
	 * are allowed to be transferred to the wallet
	 */
	public function is_wallet_transfer_enabled() {
		return $this->is_type( 'gift-registry' ) &&
			(is_nmgrcf_crowdfunding_enabled() ||
			$this->is_free_contributions_enabled() ||
			nmgr_get_option( 'enable_wallet_transfer_all' ));
	}

	public function has_crowdfunded_item() {
		_deprecated_function( __METHOD__, '4.8' );
		global $wpdb;

		$val = $this->cache_get( 'has_crowdfunded_item' );

		if ( false === $val ) {
			$val = $wpdb->get_var( "
			SELECT COUNT(*) FROM {$wpdb->prefix}nmgr_wishlist_items
				WHERE wishlist_id = {$this->get_id()}
					AND crowdfunded = 1 LIMIT 1
			" );
			$this->cache_set( 'has_crowdfunded_item', $val );
		}
		return ( bool ) $val;
	}

	/**
	 * Whether the wishlist has the amount needed to fulfill all wallet transferable
	 * items in the wishlist
	 */
	public function has_fulfill_amount() {
		if ( $this->is_wallet_transfer_enabled() && !$this->has_quantity_mismatch() ) {
			$zero = nmgrcf_round( 0 );
			$balance = $this->get_wallet_balance();
			$unpurchased_amt = $this->get_real_total_unpurchased_amount();
			return $balance && $unpurchased_amt > $zero && ($unpurchased_amt <= $balance );
		}
	}

	/**
	 * Get the amount needed to fulfill all wallet transferable items in the wishlist
	 * @return float If value is negative
	 */
	public function get_wallet_fulfill_amount() {
		$balance = $this->get_wallet_balance();
		$unpurchased_amt = $this->get_real_total_unpurchased_amount();
		return $unpurchased_amt - $balance;
	}

	/*
	  |--------------------------------------------------------------------------
	  |
	 * Actions
	  |--------------------------------------------------------------------------
	 */

	public function update_free_contributions_reference( $reference ) {
		update_post_meta( $this->get_id(), 'free_contributions_reference', $reference );
		do_action( 'nmgrcf_free_contributions_reference_updated', $this );
	}

	public function set_enable_free_contributions( $value ) {
		$settings = $this->get_free_contributions_settings();
		$settings[ 'enabled' ] = ( int ) $value;
		update_post_meta( $this->get_id(), 'free_contributions_settings', $settings );
	}

}
