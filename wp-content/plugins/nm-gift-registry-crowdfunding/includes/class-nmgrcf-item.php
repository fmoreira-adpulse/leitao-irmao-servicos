<?php

/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Item extends \NMGR\Sub\Wishlist_Item {

	protected $cf_data = [
		'crowdfunded' => 0,
		'crowdfund_data' => null,
		'crowdfund_received' => null,
		'wallet_amount' => null,
	];

	public function __construct( $item = 0 ) {
		$this->core_data = array_merge( $this->core_data, $this->cf_data );
		parent::__construct( $item );
	}

	/*
	  |--------------------------------------------------------------------------
	  | Getters
	  |--------------------------------------------------------------------------
	 */

	/*
	 * This function overrides the parent class function in nm-gift-registry 4.4.0
	 * So we should remove in a later version.
	 * @since version 4.4.0
	 * @todo Remove in later version
	 */

	public function get_product_or_variation_id() {
		return $this->get_variation_id() ? $this->get_variation_id() : $this->get_product_id();
	}

	public function get_crowdfund_data() {
		$data = $this->get_prop( 'crowdfund_data' );
		return $data ? $data : array();
	}

	public function get_crowdfund_reference() {
		_deprecated_function( __METHOD__, '4.5.0' );
		$data = $this->get_prop( 'crowdfund_reference' );
		return $data ? $data : array();
	}

	/**
	 * @return null|float
	 */
	public function get_wallet_amount() {
		$amt = $this->get_prop( 'wallet_amount' );
		return is_null( $amt ) ? $amt : nmgrcf_round( $amt );
	}

	/**
	 * Get the amount needed to fulfill a crowdfunded item.
	 *
	 * This amount needed to fulfill the item from the start of the campaign to the end.
	 * It shows the total amount of contributions to be received before the item can be
	 * marked as fulfilled. For this reason, it is based on the original price of the item
	 * and the quantity of the item.
	 */
	public function get_crowdfund_amount_needed() {
		return ( float ) ($this->is_crowdfunded() ? $this->get_total() : 0);
	}

	/**
	 * Get the amount received for a crowdfunded item
	 *
	 * This takes into account both purchases and discounts from orders.
	 * It does not take into account money used to fund the item from the wallet.
	 */
	public function get_crowdfund_amount_received() {
		return nmgrcf_round( $this->is_crowdfunded() ? $this->get_prop( 'crowdfund_received' ) : 0 );
	}

	/**
	 * Get the crowdfunded amount available for an item.
	 *
	 * (This is the real available balance for the item determined by the crowdfund
	 * amount received for the item and the amount received from the wallet
	 *
	 * This amount helps to determine whether the crowdfunded item has been fulfilled.
	 */
	public function get_crowdfund_amount_available() {
		if ( !$this->is_crowdfunded() ) {
			return ( float ) 0;
		}

		$amt_available = (nmgrcf_round( $this->get_crowdfund_amount_received() ) + nmgrcf_round( $this->get_wallet_amount() ));

		return ( float ) $amt_available;
	}

	/**
	 * Get the amount left to be received for a crowdfunded item.
	 * If this amount is positive, the crowdfunded item would remain unfulfilled.
	 * This amount determines how much more the wishlist owner needs to get in order
	 * to mark the item as completely crowdfunded.
	 */
	public function get_crowdfund_amount_left() {
		return max( nmgrcf_round( $this->get_crowdfund_amount_needed() ) - nmgrcf_round( $this->get_crowdfund_amount_available() ), ( float ) 0 );
	}

	public function get_total_purchased_amount() {
		return nmgrcf_round( parent::get_total_purchased_amount() ) + nmgrcf_round( $this->get_crowdfund_amount_available() );
	}

	/**
	 * Get the purchased amount available for an item.
	 *
	 * (This is the real available balance for the item determined by the actual
	 * amount purchased for the item, the amount credited to the wallet,
	 * and the amount debited from the wallet to the item).
	 *
	 * This function is meant to be used for normal (non-crowdfunded) items.
	 * It is the equivalent of 'nmgrcf_item_get_crowdfund_amount_available' for
	 * crowdfunded items.
	 */
	public function get_purchased_amount() {
		$amt = 0;
		if ( !$this->is_crowdfunded() ) {
			$amt = (nmgrcf_round( parent::get_purchased_amount() ) + nmgrcf_round( $this->get_wallet_amount() ));
		}
		return ( float ) $amt;
	}

	public function get_unpurchased_amount() {
		$amt = 0;
		if ( !$this->is_crowdfunded() ) {
			$amt = nmgrcf_round( $this->get_total() ) - nmgrcf_round( $this->get_purchased_amount() );
		}
		return ( float ) $amt;
	}

	public function get_purchased_quantity() {
		return $this->is_credited_to_wallet() ? 0 : parent::get_purchased_quantity();
	}

	/**
	 * Get the amounts credited to the wallet for the item
	 * @return array
	 */
	public function get_credits_to_wallet() {
		_deprecated_function( __METHOD__, '4.5.0' );
		$ref = $this->get_prop( 'credits_to_wallet' );
		return empty( $ref ) ? array() : $ref;
	}

	/**
	 * Get the amounts debited from the wallet for the item
	 * @return array
	 */
	public function get_debits_from_wallet() {
		_deprecated_function( __METHOD__, '4.5.0' );
		$ref = $this->get_prop( 'debits_from_wallet' );
		return empty( $ref ) ? array() : $ref;
	}

	/**
	 * Get the total amount credited to the wallet for the item
	 * @return int|float
	 */
	public function get_total_credits_to_wallet() {
		_deprecated_function( __METHOD__, '4.5.0' );
		return array_sum( $this->get_credits_to_wallet() );
	}

	/**
	 * Get the total amount debited from the wallet for the item
	 * @return int}float
	 */
	public function get_total_debits_from_wallet() {
		_deprecated_function( __METHOD__, '4.5.0' );
		return array_sum( $this->get_debits_from_wallet() );
	}

	public function get_crowdfund_order_item_ids() {
		global $wpdb;

		$ids = $this->cache_get( 'crowdfund_order_item_ids' );

		if ( false === $ids ) {
			$ids = $wpdb->get_col( "
		SELECT DISTINCT oim.order_item_id
		FROM {$wpdb->prefix}woocommerce_order_itemmeta AS oim
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim2 ON oim.order_item_id = oim2.order_item_id
		WHERE oim.meta_key = 'nmgrcf_item_id' AND oim.meta_value = {$this->get_id()}
		AND oim2.meta_key = 'nmgrcf_wishlist_id' AND oim2.meta_value = {$this->get_wishlist_id()}
		"
			);

			$this->cache_set( 'crowdfund_order_item_ids', $ids );
		}

		return is_array( $ids ) ? array_map( 'absint', $ids ) : [];
	}

	/*
	  |--------------------------------------------------------------------------
	  | Conditionals
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Whether the item can only be funded from the wallet
	 * @return boolean
	 */
	public function is_funded_from_wallet() {
		return !is_null( $this->get_wallet_amount() );
	}

	/**
	 * Whether any amount received for an item has been credited to the wallet
	 * @return mixed False if not.
	 */
	public function is_credited_to_wallet() {
		// If item is credited to wallet, the total purchased amount is expected to be zero
		return $this->is_funded_from_wallet() && nmgrcf_round( 0 ) === $this->get_total_purchased_amount();
	}

	/**
	 * Whether any amount received for an item has been debited from the wallet
	 * @return mixed False if not.
	 */
	public function is_debited_from_wallet() {
		return $this->is_funded_from_wallet() && $this->has_fulfill_amount();
	}

	/**
	 * Whether item purchase is disabled on the frontend.
	 * For normal items, this means the product cannot be added to the cart.
	 * For crowdfunded items, this means a contribution cannot be added to the cart.
	 *
	 * @return boolean
	 */
	public function is_purchase_disabled() {
		_deprecated_function( __METHOD__, '4.4.0' );
		$wishlist = $this->get_wishlist();
		$cond1 = $wishlist->has_fulfill_amount() && $this->is_wallet_transfer_enabled();
		$cond2 = $this->is_funded_from_wallet();

		return apply_filters( 'is_nmgrcf_item_purchase_disabled', ($cond1 || $cond2 ), $this );
	}

	/**
	 * Whether the crowdfunding for an item has been completely fulfilled
	 */
	public function is_crowdfunding_fulfilled() {
		return $this->is_crowdfunded() &&
			nmgrcf_round( $this->get_crowdfund_amount_available() ) >= nmgrcf_round( $this->get_crowdfund_amount_needed() );
	}

	/**
	 * Whether the current crowdfund status of the item should be maintained
	 * (This flag should prevent the crowdfund status from being changed
	 * when the item is saved.
	 *
	 * @return boolean
	 */
	public function maintain_crowdfund_status() {
		return $this->is_fulfilled() || $this->is_purchased() || $this->has_crowdfund_contributions() ||
			$this->is_archived() || $this->is_funded_from_wallet();
	}

	/**
	 * Check if the wishlist item has crowdfund contributions
	 * (This function is simply an alias for 'get_crowdfund_amount_available')
	 * @return boolean
	 */
	public function has_crowdfund_contributions() {
		return ( bool ) $this->get_crowdfund_amount_available();
	}

	public function is_crowdfunded() {
		return ( bool ) $this->get_prop( 'crowdfunded' );
	}

	/**
	 * Whether the item has the amount for it to be fulfilled.
	 */
	public function has_fulfill_amount() {
		return nmgrcf_round( 0 ) >= nmgrcf_round( $this->get_total_unpurchased_amount() );
	}

	/**
	 * Whether wallet transfers are enabled for this wishlist item
	 *
	 * @return boolean True if the item is crowdfunded or if it is a normal item
	 * and transfers are enabled for normal items.
	 */
	public function is_wallet_transfer_enabled() {
		return !$this->is_archived() &&
			($this->is_crowdfunded() || nmgr_get_option( 'enable_wallet_transfer_all' ));
	}

	public function is_crowdfunding_enabled() {
		return is_nmgrcf_crowdfunding_enabled() && !$this->is_archived();
	}

	public function set_crowdfunded( $value ) {
		$this->set_prop( 'crowdfunded', $value );
	}

	public function set_crowdfund_data( $value ) {
		$this->set_prop( 'crowdfund_data', $value );
	}

	public function set_crowdfund_amount_received( $value ) {
		$this->set_prop( 'crowdfund_received', $value );
	}

	public function set_wallet_amount( $value ) {
		$this->set_prop( 'wallet_amount', $value );
	}

	/*
	  |--------------------------------------------------------------------------
	  | Actions
	  |--------------------------------------------------------------------------
	 */

	public function make_crowdfunded( $crowdfund_data = array() ) {
		_deprecated_function( __METHOD__, '4.5.0' );
		$this->set_prop( 'crowdfunded', 1 );
		if ( !empty( $crowdfund_data ) ) {
			$this->set_prop( 'crowdfund_data', $crowdfund_data );
		}
		$this->save();
	}

	public function unmake_crowdfunded() {
		_deprecated_function( __METHOD__, '4.5.0' );
		$this->set_props( [
			'crowdfunded' => 0,
			'crowdfund_data' => null,
		] );
		$this->save();
	}

	/**
	 * Transfer the amount received for the wishlist item to the wallet
	 * @return boolean True if the wallet was credited with the amount, WP_Error if not
	 */
	public function credit_wallet() {
		$wallet = new NMGRCF_Wallet( $this->get_wishlist_id() );
		return $wallet->credit_item_amount( $this );
	}

	/**
	 * Fund the wishlist item from the amount in the wallet
	 * @return boolean True if wallet was debited, WP_Error if not.
	 */
	public function debit_wallet() {
		$wallet = new NMGRCF_Wallet( $this->get_wishlist_id() );
		return $wallet->debit_item_amount( $this );
	}

	public function update_crowdfund_reference( $reference ) {
		_deprecated_function( __METHOD__, '4.5.0' );
		$this->set_prop( 'crowdfund_reference', $reference );
		$this->save();
		do_action( 'nmgrcf_item_crowdfund_reference_updated', $this );
	}

	public function credit_wallet_amount( $amount ) {
		$this->set_wallet_amount( nmgrcf_round( $this->get_wallet_amount() ) - nmgrcf_round( $amount ) );
	}

	public function debit_wallet_amount( $amount ) {
		$this->set_wallet_amount( nmgrcf_round( $this->get_wallet_amount() ) + nmgrcf_round( $amount ) );
	}

	/**
	 * Refund the quantities of this item purchased in all orders
	 */
	public function refund_quantity_in_orders() {
		$order_item_ids = $this->get_order_item_ids();

		foreach ( $order_item_ids as $order_item_id ) {
			$order = wc_get_order( wc_get_order_id_by_order_item_id( $order_item_id ) );
			$order_item = $order->get_item( $order_item_id );
			$refund_qty = $order_item->get_quantity() - absint( $order->get_qty_refunded_for_item( $order_item_id ) );
			if ( $refund_qty > 0 ) {
				$args = [
					'reason' => sprintf(
						/* translators: %s: wishlist type title */
						__( 'Transfer amount received for item to %s wallet.', 'nm-gift-registry-crowdfunding' ),
						nmgr_get_type_title()
					),
					'order_id' => $order_item->get_order_id(),
					'line_items' => [
						$order_item->get_id() => [
							'qty' => $refund_qty,
							'refund_total' => 0,
							'refund_tax' => [],
						],
					],
				];

				wc_create_refund( $args );
			}
		}
	}

}
