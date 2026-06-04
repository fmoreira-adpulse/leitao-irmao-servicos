<?php
defined( 'ABSPATH' ) || exit;

class NMGRCF_Wallet {

	private $wishlist;
	private $wishlist_id;

	public function __construct( $wishlist_id ) {
		$this->wishlist = is_a( $wishlist_id, \NMGR_Wishlist::class ) ?
			$wishlist_id : nmgr_get_wishlist( $wishlist_id );

		if ( $this->wishlist ) {
			$this->wishlist_id = $this->wishlist->get_id();
		}
	}

	public static function run() {
		add_action( 'nmgr_post_action', [ __CLASS__, 'post_action' ] );
		add_action( 'admin_notices', [ __CLASS__, 'show_custom_order_notice_on_order_screen' ] );
		add_action( 'nmgr_email_order_items_details', [ __CLASS__, 'show_custom_order_notice_in_email' ], 30, 3 );
		add_action( 'woocommerce_email_customer_details', [ __CLASS__, 'show_custom_order_notice' ], 30, 3 );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', [ __CLASS__, 'format_item_data' ] );
		add_action( 'woocommerce_after_calculate_totals', [ __CLASS__, 'do_discount' ] );
		add_filter( 'woocommerce_cart_get_total', [ __CLASS__, 'cart_total' ] );
		add_filter( 'woocommerce_cart_get_discount_total', [ __CLASS__, 'cart_discount_total' ] );
		add_action( 'woocommerce_cart_totals_before_order_total', [ __CLASS__, 'show_wallet_discount' ] );
		add_action( 'woocommerce_review_order_before_order_total', [ __CLASS__, 'show_wallet_discount' ] );
		add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'set_item_discount' ], 10, 3 );
		add_action( 'nmgr_order_payment_complete', [ __CLASS__, 'apply_wallet_discount' ], 20 );
		add_filter( 'woocommerce_coupon_is_valid', [ __CLASS__, 'invalidate_coupons' ] );
		add_filter( 'woocommerce_coupon_error', [ __CLASS__, 'set_coupon_error_message' ], 10, 2 );
		add_action( 'woocommerce_check_cart_items', [ __CLASS__, 'remove_cart_items' ] );
		add_action( 'nmgr_before_account_items', [ __CLASS__, 'show_wishlist_has_fulfill_amount_notice' ] );
		add_filter( 'nmgr_fields_item_actions', [ __CLASS__, 'remove_wallet_related_item_actions' ], 10, 2 );
	}

	public static function remove_cart_items() {
		$rem = wc()->session->get( 'nmgrcf_remove_cart', [] );
		if ( !empty( $rem ) ) {
			foreach ( $rem as $cart_item_key ) {
				wc()->cart->remove_cart_item( $cart_item_key );
			}
			unset( wc()->session->nmgrcf_wallet_discount );
		}
	}

	public static function invalidate_coupons( $bool ) {
		return !empty( wc()->session->get( 'nmgrcf_wallet_discount' ) ) ? false : $bool;
	}

	public static function set_coupon_error_message( $message, $error_code ) {
		if ( !empty( wc()->session->get( 'nmgrcf_wallet_discount' ) ) && 100 === $error_code ) {
			$message = __( 'This coupon cannot be used as the wallet discount has already been applied to items in the cart.', 'nm-gift-registry-crowdfunding' );
		}
		return $message;
	}

	/**
	 * We hook this on priority 20 just to make sure the main actions on the
	 * 'nmgr_order_payment_complete' hook such as updating the item purchased amount, updating the
	 * crowdfund amount received and sending the free contribution amount to the wallet have been run.
	 * This would ensure that both the wallet and the wishlist item have the right amounts
	 * to be able to apply the discount to the wishlist item.
	 */
	public static function apply_wallet_discount( $order_id ) {
		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $order_item ) {
			$wallet_discount = $order_item->get_meta( '_nmgrcf_discount' );

			if ( $wallet_discount && !$order_item->get_meta( '_nmgrcf_discount_applied' ) ) {
				if ( $order_item->get_meta( 'nmgrcf_free_contribution' ) || $order_item->get_meta( 'nmgrcf_item_id' ) ) {
					$applied = true;
				} else {
					// Deal with normal wishlist items that are wallet transferable
					$wishlist_item = nmgr_get_wishlist_item( $order_item->get_meta( 'nmgr_item_id' ) );
					$applied = $wishlist_item && !is_wp_error( $wishlist_item->debit_wallet() ) ? true : false;
				}

				if ( isset( $applied ) && $applied ) {
					$order_item->add_meta_data( '_nmgrcf_discount_applied', $wallet_discount );
					$order_item->save();
				}
			}
		}
	}

	public static function set_item_discount( $item, $cart_item_key, $cart_item ) {
		if ( isset( $cart_item[ 'nmgrcf_discount' ] ) ) {
			$item->add_meta_data( '_nmgrcf_discount', $cart_item[ 'nmgrcf_discount' ] );
		}
	}

	public static function do_discount( $cart ) {
		$data = [];

		$check = array_filter( [
			'free_contribution',
			'crowdfund',
			nmgr_get_option( 'enable_wallet_transfer_all' ) ? 'wishlist_item' : ''
			] );

		foreach ( $cart->get_cart_contents() as $cart_item ) {
			foreach ( $check as $data_type ) {
				$nmgr_data = nmgr_get_cart_item_data( $cart_item, $data_type );
				if ( $nmgr_data && nmgrcf_round( $cart_item[ 'line_total' ] ?? 0  ) > nmgrcf_round( 0 ) ) {
					$data[ $nmgr_data[ 'wishlist_id' ] ][] = $cart_item;
					continue;
				}
			}
		}

		$discount_amounts = [];
		$wishlists = [];

		foreach ( $data as $wishlist_id => $cart_items ) {
			$wishlist = nmgr_get_wishlist( $wishlist_id, true );
			if ( $wishlist ) {
				/**
				 * Remove free contributions cart item from being discounted if we must collect them
				 */
				foreach ( $cart_items as $key => $ct ) {
					if ( nmgr_get_cart_item_data( $ct, 'free_contribution' ) &&
						$wishlist->get_free_contributions_amount_left() > nmgrcf_round( 0 ) ) {
						unset( $cart_items[ $key ] );
					}
				}

				$cart_amt = nmgrcf_round( array_sum( wp_list_pluck( $cart_items, 'line_total' ) ) );

				if ( $wishlist->has_fulfill_amount() ) {
					$rem = [];
					foreach ( $cart_items as $ct ) {
						$rem[] = $ct[ 'key' ];
					}
					wc()->session->set( 'nmgrcf_remove_cart', $rem );
					continue;
				}

				$discount_amount = self::discount_wallet_price( $wishlist, $cart_amt );

				if ( $discount_amount ) {
					$discount_amounts[ $wishlist->get_id() ] = $discount_amount;
					$wishlists[] = $wishlist;
				}
			}
		}

		$discount_data = [];

		if ( nmgrcf_round( $cart->get_cart_contents_total() ) >= array_sum( $discount_amounts ) ) {
			foreach ( $wishlists as $wishlist ) {
				$discount_balance = $discount_amounts[ $wishlist->get_id() ];
				$wishlist_cart_items = $data[ $wishlist->get_id() ];
				self::apply_discount_to_products( $cart, $wishlist_cart_items, $discount_balance );
				$discount_data[ $wishlist->get_title() ] = $discount_balance;
			}
		}

		if ( !empty( $discount_data ) ) {
			wc()->session->set( 'nmgrcf_wallet_discount', $discount_data );
		} else {
			unset( wc()->session->nmgrcf_wallet_discount );
		}
	}

	private static function apply_discount_to_products( $cart, $cart_items, $amount ) {
		foreach ( $cart_items as $item ) {
			$amount = nmgrcf_round( $amount );
			$line_total = nmgrcf_round( $item[ 'line_total' ] );
			if ( $amount > nmgrcf_round( 0 ) ) {
				if ( $amount <= $line_total ) {
					$cart->cart_contents[ $item[ 'key' ] ][ 'line_total' ] = $line_total - $amount;
					$cart->cart_contents[ $item[ 'key' ] ][ 'nmgrcf_discount' ] = $amount;
					break;
				} else {
					$cart->cart_contents[ $item[ 'key' ] ][ 'line_total' ] = 0;
					$cart->cart_contents[ $item[ 'key' ] ][ 'nmgrcf_discount' ] = $line_total;
					$amount = $amount - $line_total;
					continue;
				}
			}
		}
	}

	/**
	 * Add the wallet discount amount to the cart discount total
	 * The main reason for this is to allow the discount total amount
	 * shown in the order details to reflect the wallet discount
	 */
	public static function cart_discount_total( $value ) {
		$discount_data = wc()->session->get( 'nmgrcf_wallet_discount', [] );
		if ( !empty( $discount_data ) ) {
			$value += array_sum( $discount_data );
		}
		return $value;
	}

	/**
	 * Substract the wallet discount amount from the cart total
	 * The reason for this is to all the cart total reflect the wallet discount
	 */
	public static function cart_total( $value ) {
		$discount_data = wc()->session->get( 'nmgrcf_wallet_discount', [] );
		if ( !empty( $discount_data ) ) {
			$value -= array_sum( $discount_data );
		}
		return $value;
	}

	private static function discount_wallet_price( $wishlist, $cart_amt ) {
		if ( $wishlist->is_wallet_transfer_enabled() ) {
			$amt_needed = $wishlist->get_wallet_fulfill_amount();
			if ( $amt_needed > nmgrcf_round( 0 ) && $cart_amt > $amt_needed ) {
				$discount_amount = $cart_amt - $amt_needed;
			}
			return isset( $discount_amount ) ? $discount_amount : null;
		}
	}

	public static function discount_text() {
		return __( 'Wallet discount', 'nm-gift-registry-crowdfunding' );
	}

	public static function discount_description() {
		return sprintf(
			/* translators: %s: wishlist type title */
			__( 'This discount has been applied to help fulfill all the wallet transferable items in the %s.', 'nm-gift-registry-crowdfunding' ),
			nmgr_get_type_title()
		);
	}

	public static function show_wallet_discount() {
		$discount_data = wc()->session->get( 'nmgrcf_wallet_discount', [] );

		if ( !empty( $discount_data ) ) {
			$title = self::discount_text() . ' ' . nmgr_get_help_tip( self::discount_description() ) . ' : ';
			foreach ( $discount_data as $wishlist_title => $wallet_amt ) {
				$full_title = $title . ' ' . $wishlist_title;
				?>
				<tr class="nmgrcf-wallet-discount">
					<th><?php echo wp_kses_post( $full_title ); ?></th>
					<td data-title="<?php echo esc_attr( $full_title ); ?>">
						<div><?php echo '-' . wc_price( $wallet_amt ); ?></div>
					</td>
				</tr>
				<?php
			}
		}
	}

	public static function post_action( $args ) {
		switch ( $args[ 'post_action' ] ?? null ) {
			case 'show_view_wallet_dialog':
				self::show_dialog( 'wallet' );
				break;
			case 'show_wallet_log_dialog':
				self::show_dialog( 'wallet_log' );
				break;
			case 'item_debit_credit_wallet_action':
				self::item_debit_credit_wallet_action();
				break;
		}
	}

	public static function remove_wallet_related_item_actions( $actions, $fields ) {
		$item = $fields->table->get_row_object();

		if ( $item && $item->is_funded_from_wallet() ) {
			if ( isset( $actions[ 'purchase_refund' ] ) ) {
				unset( $actions[ 'purchase_refund' ] );
			}
		}
		return $actions;
	}

	public static function show_wishlist_has_fulfill_amount_notice( $account ) {
		$wishlist = $account->get_wishlist();
		if ( nmgr_user_can_manage_wishlist( $wishlist ) && $wishlist->has_fulfill_amount() ) {
			$balance = $wishlist->get_wallet_balance();
			$unpurchased_amt = $wishlist->get_real_total_unpurchased_amount();

			$notice = [];
			$notice[] = sprintf(
				/* translators : 1: wallet balance, 2: wishlist type title, 3: wishlist unpurchased amount */
				__( 'There is enough money available in the wallet (%1$s) to fulfill wallet transferable items in this %2$s (%3$s).', 'nm-gift-registry-crowdfunding' ),
				'<strong> ' . wc_price( $balance ) . '</strong>',
				nmgr_get_type_title(),
				'<strong>' . wc_price( $unpurchased_amt ) . '</strong>'
			);
			$notice[] = __( 'Checkout purchases are now disabled for these items and they can only be funded from the wallet.', 'nm-gift-registry-crowdfunding' );

			if ( nmgrcf_round( $unpurchased_amt ) < nmgrcf_round( $balance ) ) {
				$notice[] = __( 'To use up the extra funds in the wallet, consider adding some extra items or increasing the quantities of existing items.', 'nm-gift-registry-crowdfunding' );
			}

			$str = '<li>' . implode( '</li><li>', $notice ) . '</li>';

			echo '<div class="nmgr-fulfill-notice"><ul>' . wp_kses_post( $str ) . '</ul></div>';
		}
	}

	/**
	 * Ajax action to debit or credit the wallet with the amount received for a wishlist item
	 */
	private static function item_debit_credit_wallet_action() {
		$wid = nmgr()->ajax()->get_posted_wishlist_and_item_ids();
		nmgr()->ajax()->check_wishlist_permission( $wid[ 'wishlist_id' ] );

		$item_id = $wid[ 'wishlist_item_id' ];
		$context = sanitize_text_field( wp_unslash( $_POST[ 'context' ] ?? '' ) );
		$item = nmgr_get_wishlist_item( $item_id );

		if ( $item && in_array( $context, array( 'credit', 'debit' ) ) ) {
			$result = ('credit' === $context) ? $item->credit_wallet() : $item->debit_wallet();

			if ( !is_wp_error( $result ) ) {
				$notices = [ nmgr_get_success_toast_notice() ];

				if ( is_a( $result, \WC_Order::class ) && method_exists( $item, 'get_order_created_toast_notice' ) ) {
					$notices[] = $item->get_order_created_toast_notice( $result );
				}

				$table = nmgr()->items_table( $item->get_wishlist() );

				wp_send_json( array(
					'replace_templates' => array_merge(
						$table->get_item_template_data( $item ),
						$table->get_totals_template_data()
					),
					'toast_notice' => $notices,
				) );
			} else {
				wp_send_json( array(
					'toast_notice' => nmgr_get_toast_notice( $result->get_error_message(), 'error' ),
				) );
			}
		}
	}

	private static function show_dialog( $type ) {
		$wishlist_id = ( int ) wp_unslash( $_POST[ 'wishlist_id' ] ?? false );
		nmgr()->ajax()->check_wishlist_permission( $wishlist_id );
		$response = array();
		$template = '';

		switch ( $type ) {
			case 'wallet_log':
				$template = nmgrcf_get_wallet_log_dialog_template( $wishlist_id );
				break;
		}

		if ( $template ) {
			$response[ 'show_template' ] = $template;
		}

		wp_send_json( $response );
	}

	/* ---------------------------------------
	 *
	 * PUBLIC METHODS
	 *
	  ---------------------------------------- */

	public function get_log() {
		$log = get_post_meta( $this->get_wishlist_id(), 'nmgrcf_wallet_log', true );
		return is_array( $log ) ? $log : array();
	}

	public function get_log_params() {
		return apply_filters( 'nmgrcf_wallet_log_params', array(
			'id' => count( $this->get_log() ) + 1,
			'amount' => '',
			'type' => '',
			'event_code' => '',
			'descriptor' => '',
			'date' => current_time( 'mysql' ),
			'note' => ''
			) );
	}

	private function log( $args = array() ) {
		if ( !$this->get_wishlist() ) {
			return;
		}

		$log_value = array_merge( $this->get_log_params(), $args );

		// Make sure there is an event descriptor
		if ( !empty( $log_value[ 'event_code' ] ) && empty( $log_value[ 'descriptor' ] ) ) {
			$event_desc = $this->get_event_description( $log_value[ 'event_code' ] );
			$log_value[ 'descriptor' ] = !empty( $event_desc ) ? $event_desc[ 'descriptor' ] : '';
		}

		$log = $this->get_log();
		$log[] = $log_value;
		update_post_meta( $this->get_wishlist_id(), 'nmgrcf_wallet_log', $log );
	}

	/**
	 * @deprecated since version 4.5.0
	 */
	public function get_balance() {
		_deprecated_function( __METHOD__, '4.5.0', 'NMGRCF_Wallet->get_wishlist()->get_wallet_balance()' );
		return $this->get_wishlist()->get_wallet_balance();
	}

	/**
	 * Credit the wallet with an amount
	 *
	 * @param array $args Arguments needed to make the transfer.
	 * Minimum arguments:
	 * - amount - The amount to credit the wallet with.
	 * - descriptor - The description of the type of credit e.g. 'TRF FRM WISHLIST ITEM'.
	 */
	public function credit( $args ) {
		return $this->credit_debit( 'credit', $args );
	}

	/**
	 * Debit an amount from the wallet
	 *
	 * @param array $args Arguments needed to make the transfer.
	 * Minimum arguments:
	 * - amount - The amount to debit from the wallet.
	 * - descriptor - The description of the type of debit e.g. 'TRF TO WISHLIST ITEM'.
	 */
	public function debit( $args ) {
		return $this->credit_debit( 'debit', $args );
	}

	/**
	 * Credit or debit the wallet with an amount
	 *
	 * @param string $type The event type .e.g. 'Credit' or 'Debit'
	 * @param array $args Arguments to send to the event action and event log
	 * @return boolean. True if successful, false if not.
	 */
	private function credit_debit( $type, $args ) {
		if ( isset( $args[ 'amount' ] ) ) {
			$args[ 'type' ] = $type;
			$balance = $this->get_wishlist()->get_wallet_credit_debit_balance();

			if ( 'debit' === strtolower( $type ) ) {
				$value = $balance - nmgrcf_round( $args[ 'amount' ] );
			} else {
				$value = $balance + nmgrcf_round( $args[ 'amount' ] );
			}

			update_post_meta( $this->get_wishlist_id(), 'nmgrcf_wallet', $value );
			$this->log( $args );
			return true;
		}
		return false;
	}

	/**
	 * Get the description set used for a wallet event
	 *
	 * @param string $code_or_descriptor The code or descriptor
	 * to get the description set for.
	 *
	 * @return array Description array containing the code, descriptor and description
	 * for the event.
	 */
	public function get_event_description( $code_or_descriptor = '' ) {
		$desc = array(
			'wishlist_item_debit' => array(
				'code' => 'wishlist_item_debit',
				'descriptor' => __( 'TRF FRM WISHLIST ITEM', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Transfer from wishlist item', 'nm-gift-registry-crowdfunding' ),
			),
			'wishlist_item_credit' => array(
				'code' => 'wishlist_item_credit',
				'descriptor' => __( 'TRF TO WISHLIST ITEM', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Transfer to wishlist item', 'nm-gift-registry-crowdfunding' ),
			),
			'free_contribution_debit' => array(
				'code' => 'free_contribution_debit',
				'descriptor' => __( 'TRF FRM FREE CONT', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Transfer from free contribution', 'nm-gift-registry-crowdfunding' ),
			),
			'coupon_creation' => array(
				'code' => 'coupon_creation',
				'descriptor' => __( 'COUPON CREATION', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Create coupon', 'nm-gift-registry-crowdfunding' ),
			),
			'coupon_deletion' => array(
				'code' => 'coupon_deletion',
				'descriptor' => __( 'COUPON DELETION', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Delete coupon', 'nm-gift-registry-crowdfunding' ),
			),
			'reset_wallet' => array(
				'code' => 'reset_wallet',
				'descriptor' => __( 'RESET WALLET', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Reset the amount in the wallet to zero', 'nm-gift-registry-crowdfunding' ),
			),
		);

		if ( $code_or_descriptor ) {
			if ( isset( $desc[ $code_or_descriptor ] ) ) {
				return $desc[ $code_or_descriptor ];
			}

			foreach ( $desc as $item ) {
				if ( $code_or_descriptor === $item[ 'descriptor' ] ) {
					return $item;
				}
			}
		} else {
			return $desc;
		}
	}

	public function get_wishlist() {
		return $this->wishlist;
	}

	public function get_wishlist_id() {
		return $this->wishlist_id;
	}

	/**
	 * Check if the wallet is empty
	 *
	 * The wallet is empty if it has zero or a negative balance
	 * @return boolean
	 */
	public function is_empty() {
		return nmgrcf_round( 0 ) >= nmgrcf_round( $this->get_wishlist()->get_wallet_balance() );
	}

	/**
	 * Check if the wallet has zero balance
	 *
	 * @return boolean
	 */
	public function has_zero_balance() {
		return nmgrcf_round( 0 ) === nmgrcf_round( $this->get_wishlist()->get_wallet_balance() );
	}

	/**
	 * Check if the wallet has a positive balance
	 *
	 * @return boolean
	 */
	public function has_positive_balance() {
		return nmgrcf_round( 0 ) < nmgrcf_round( $this->get_wishlist()->get_wallet_balance() );
	}

	/**
	 * Check if the wallet has a negative balance
	 *
	 * @return boolean
	 */
	public function has_negative_balance() {
		return nmgrcf_round( 0 ) > nmgrcf_round( $this->get_wishlist()->get_wallet_balance() );
	}

	/**
	 * Reset the amount in the wallet to zero
	 *
	 * @return \WP_Error|boolean
	 */
	public function reset() {
		_deprecated_function( __METHOD__, '4.5.0' );

		if ( $this->has_zero_balance() ) {
			return new WP_Error( 'reset_wallet_unnecessary', __( 'The balance in the wallet is already zero.', 'nm-gift-registry-crowdfunding' ) );
		}

		$type = $this->has_positive_balance() ? 'debit' : 'credit';
		$balance = $this->get_wishlist()->get_wallet_balance();

		$reset = $this->credit_debit( $type, array(
			'amount' => $balance,
			'event_code' => 'reset_wallet'
			) );

		if ( $reset ) {
			do_action( 'nmgrcf_wallet_reset', array(
				'wallet' => $this,
				'amount' => $balance,
				'event_type' => $type
			) );
			return true;
		} else {
			return new WP_Error( 'reset_wallet_failed', __( 'There was an error trying to reset the wallet.', 'nm-gift-registry-crowdfunding' ) );
		}
	}

	/**
	 * Credit a free contribution amount to the wallet
	 *
	 * @param int|float $amount The amount to credit
	 * @deprecated since version 4.5.0
	 * @return \WP_Error|boolean
	 */
	public function credit_free_contribution_amount( $amount ) {
		_deprecated_function( __METHOD__, '4.5.0' );
		$credited = $this->credit(
			array(
				'amount' => $amount,
				'event_code' => 'free_contribution_debit'
			)
		);

		if ( $credited ) {
			do_action( 'nmgrcf_free_contribution_credited_to_wallet', array(
				'credited_amount' => $amount,
				'wishlist' => $this->wishlist,
				'wallet' => $this
			) );

			return true;
		}
	}

	/**
	 * Credit the amount available for a wishlist item to the wallet
	 *
	 * @param int|NMGR_Wishlist_Item|NMGRCF_Item $item_id The wishlist item id or object
	 * @return \WP_Error|boolean|\wp_Error
	 */
	public function credit_item_amount( $item_id ) {
		$item = is_a( $item_id, \NMGR_Wishlist_Item::class ) ? $item_id : nmgr_get_wishlist_item( $item_id );
		$amt_available = $item->get_total_purchased_amount();

		if ( nmgrcf_round( 0 ) >= nmgrcf_round( $amt_available ) ) {
			return new WP_Error( 'item_credit_wallet_invalid_amount', __( 'This item does not have enough amount to be credited to the wallet.', 'nm-gift-registry-crowdfunding' ) );
		}

		$event_code = 'wishlist_item_debit';
		$event_desc = $this->get_event_description( $event_code );
		$transaction_desc = $event_desc[ 'descriptor' ] . '/' . $item->get_id();

		$credited = $this->credit(
			array(
				'amount' => $amt_available,
				'event_code' => $event_code,
				'descriptor' => $transaction_desc
			)
		);

		if ( $credited ) {
			$item->credit_wallet_amount( $amt_available );
			$item->save();
			return true;
		} else {
			return new wp_Error( 'item_credit_wallet_failed', __( 'The wallet could not be credited with this item\'s available amount.', 'nm-gift-registry-crowdfunding' ) );
		}
	}

	/**
	 * Fund the wishlist item from the amount in the wallet
	 *
	 * @param int|NMGR_Item $item_id Wishlist item id or object
	 * @return \WP_Error|boolean
	 * @throws Exception
	 */
	public function debit_item_amount( $item_id ) {
		$item = is_a( $item_id, \NMGR_Wishlist_Item::class ) ? $item_id : nmgr_get_wishlist_item( $item_id );

		if ( $item->has_fulfill_amount() ) {
			return new WP_Error( 'item_debit_from_wallet_disallowed', __( 'This item has already been completely funded.', 'nm-gift-registry-crowdfunding' ) );
		}

		if ( !$this->has_positive_balance() ) {
			return new WP_Error( 'item_debit_wallet_empty_wallet', __( 'There is currently no money in the wallet to fund this item.', 'nm-gift-registry-crowdfunding' ) );
		}

		$amt_left = $item->get_total_unpurchased_amount();
		$wallet_amt = $this->get_wishlist()->get_wallet_balance();

		if ( nmgrcf_round( $amt_left ) > nmgrcf_round( $wallet_amt ) ) {
			return new WP_Error( 'item_debit_wallet_insufficient_wallet_amount', sprintf(
					/* translators: 1: Amount in wallet, 2: amount needed for item */
					__( 'The amount in the wallet (%1$s) is not enough to completely fund this item (%2$s needed).', 'nm-gift-registry-crowdfunding' ),
					html_entity_decode( strip_tags( wc_price( $wallet_amt ) ) ),
					html_entity_decode( strip_tags( wc_price( $amt_left ) ) )
				) );
		}

		$order = false;

		$event_code = 'wishlist_item_credit';
		$event_desc = $this->get_event_description( $event_code );
		$transaction_desc = $event_desc[ 'descriptor' ] . '/' . $item->get_id();

		$debited = $this->debit(
			array(
				'amount' => $amt_left,
				'event_code' => $event_code,
				'descriptor' => $transaction_desc
			)
		);

		if ( $debited ) {
			$item->debit_wallet_amount( $amt_left );
			$item->save();

			/**
			 * Create order and update the item purchased quantity because debits from wallet
			 * should always result in the item being fulfilled.
			 * (We have to do this after wallet amount has already been debited from item and saved
			 * so that the accurate unfiltered purchased quantity of the item would be reflected in
			 * the order item quantity.
			 */
			$unpurchased_qty = $item->get_unpurchased_quantity();

			if ( $unpurchased_qty && method_exists( $item, 'create_order' ) ) {
				$order = $item->create_order( [
					'quantity' => $unpurchased_qty,
					'apply_price' => false,
					'order_note' => nmgrcf_get_order_item_wallet_purchase_notice(),
					'order_item_meta' => [ '_nmgrcf_wallet_purchase' => 1 ],
					'paid' => true
					] );
			} else {
				$item->set_purchased_quantity( $item->get_quantity() );
			}

			return false !== $order ? $order : true;
		} else {
			return new WP_Error( 'item_debit_wallet_failed', __( 'The wallet could not be debited to fund the item.', 'nm-gift-registry-crowdfunding' ) );
		}
	}

	public static function show_custom_order_notice_on_order_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && in_array( $screen->id, [ 'shop_order', 'woocommerce_page_wc-orders' ] ) &&
			'edit' === ( $_GET[ 'action' ] ?? '' ) ) {
			$id = $_GET[ 'id' ] ?? ($_GET[ 'post' ] ?? 0);
			$order = $id ? wc_get_order( ( int ) $id ) : false;
			if ( $order && $order->is_created_via( 'nmgr_wishlist' ) &&
				nmgrcf_order_contains_wallet_purchased_item( $order ) ) {
				echo '<div class="notice-info notice"><p>' .
				esc_html( nmgrcf_get_order_item_wallet_purchase_notice() ) .
				'</p></div>';
			}
		}
	}

	public static function show_custom_order_notice_in_email( $order, $order_item_ids, $email ) {
		if ( !empty( $email->template_args[ 'order' ] ) &&
			$email->template_args[ 'order' ]->is_created_via( 'nmgr_wishlist' ) &&
			nmgrcf_order_contains_wallet_purchased_item( $email->template_args[ 'order' ] ) ) {
			$notice = nmgrcf_get_order_item_wallet_purchase_notice();
			if ( 'plain' === $email->get_email_type() ) {
				echo esc_html( $notice ) . "\n\n";
			} else {
				echo '<p><i>' . esc_html( $notice ) . '<i></p>';
			}
		}
	}

	public static function show_custom_order_notice( $order, $sent_to_admin, $plain_text ) {
		if ( $order && $order->is_created_via( 'nmgr_wishlist' ) &&
			nmgrcf_order_contains_wallet_purchased_item( $order ) ) {
			$notice = nmgrcf_get_order_item_wallet_purchase_notice();
			echo $plain_text ? (esc_html( $notice ) . "\n\n") : ('<p>' . esc_html( $notice ) . '</p>');
		}
	}

	public static function format_item_data( $meta_array ) {
		foreach ( $meta_array as $id => $meta ) {
			$remove = [
				'_nmgrcf_wallet_purchase',
				'_nmgrcf_discount_applied'
			];

			if ( in_array( $meta->key, $remove ) ) {
				unset( $meta_array[ $id ] );
			}

			if ( '_nmgrcf_discount' === $meta->key ) {
				$meta_array[ $id ]->display_key = self::discount_text() . ' ' . nmgr_get_help_tip( self::discount_description() ) . ' ';
				$meta_array[ $id ]->display_value = wc_price( $meta->value );
			}
		}
		return $meta_array;
	}

}
