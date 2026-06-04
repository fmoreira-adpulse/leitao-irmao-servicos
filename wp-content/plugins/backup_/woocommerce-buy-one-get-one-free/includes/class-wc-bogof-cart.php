<?php
/**
 * WooCommerce Buy One Get One Free cart actions.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart Class
 */
class WC_BOGOF_Cart {


	/**
	 * Cart rules
	 *
	 * @var WC_BOGOF_Cart_Rules
	 */
	public static $cart_rules = null;

	/**
	 * Calculate cart subtotal.
	 *
	 * @var WC_BOGOF_Cart_Totals
	 */
	private static $cart_totals = null;

	/**
	 * Init hooks
	 */
	public static function init() {
		add_filter( 'woocommerce_order_again_cart_item_data', array( __CLASS__, 'order_again_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_pre_remove_cart_item_from_session', array( __CLASS__, 'remove_order_again_cart_item' ), 9999, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 9999, 3 );
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'cart_loaded_from_session' ), 20 );
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'init_discounts' ), 25 );
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'validate_free_items' ), 30 );
	}

	/**
	 * Populate the order again item data.
	 *
	 * @param array         $cart_item_data Cart item data array.
	 * @param WC_Order_Item $item Order item instance.
	 */
	public static function order_again_cart_item_data( $cart_item_data, $item ) {
		$metas = $item->get_meta( '_wc_bogof_rule_id', false, 'edit' );
		if ( empty( $metas ) || ! is_array( $metas ) ) {
			return $cart_item_data;
		}

		$is_free_item = $item->get_meta( '_wc_bogof_free_item' );

		if ( ! in_array( $is_free_item, array( 'yes', 'no' ), true ) ) {
			// We check if it is a free item using the rule data.
			$meta = current( $metas );
			$rule = wc_bogof_get_rule( $meta->value );

			$is_free_item = $rule && in_array( $rule->get_type(), array( 'buy_a_get_a', 'buy_a_get_b' ), true );
		}

		$is_free_item = wc_string_to_bool( $is_free_item );

		if ( $is_free_item ) {
			// Add order again flags.
			$cart_item_data['_bogof_remove_order_again'] = array(
				'is_free_item' => true,
				'rule_ids'     => wc_list_pluck( $metas, 'value' ),
			);
		}

		return $cart_item_data;
	}

	/**
	 * Remove the free items after order again.
	 * This prevent the message "item from your previous order could not be added to your cart"
	 *
	 * @param bool   $remove If true, the item will not be added to the cart. Default: false.
	 * @param string $key Cart item key.
	 * @param array  $values Cart item values e.g. quantity and product_id.
	 */
	public static function remove_order_again_cart_item( $remove, $key, $values ) {
		return ! empty( $values['_bogof_remove_order_again']['is_free_item'] );
	}

	/**
	 * Update product with the flags.
	 *
	 * @param array  $session_data Session data.
	 * @param array  $values Values.
	 * @param string $key Item key.
	 * @return array
	 */
	public static function get_cart_item_from_session( $session_data, $values, $key ) {
		if ( isset( $values['_bogof_free_item'] ) ) {
			$session_data['_bogof_free_item'] = $values['_bogof_free_item'];
		}

		if ( isset( $values['_bogof_discount'] ) && is_array( $values['_bogof_discount'] ) ) {
			$session_data['_bogof_discount'] = $values['_bogof_discount'];
		}

		return $session_data;
	}

	/**
	 * Cart loaded from session.
	 */
	public static function cart_loaded_from_session() {
		if ( did_action( 'wc_bogof_cart_rules_loaded' ) ) {
			// Only do it once.
			return;
		}
		self::init_cart_rules();
		self::init_order_again();
		self::init_hooks();

		do_action( 'wc_bogof_cart_rules_loaded' );
	}

	/**
	 * Load available rules.
	 */
	private static function init_cart_rules() {
		self::$cart_rules = new WC_BOGOF_Cart_Rules();
		$cart_contents    = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( self::is_free_item( $cart_item ) ) {
				continue;
			}
			self::$cart_rules->add( $cart_item );
		}

		self::$cart_rules->sort();
	}

	/**
	 * Init the order again var.
	 */
	private static function init_order_again() {
		if ( did_action( 'woocommerce_ordered_again' ) ) {
			WC()->session->set( 'wc_bogof_is_order_again', 1 );
		}
	}

	/**
	 * Init hooks.
	 */
	private static function init_hooks() {
		// Free items and discounts.
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'cart_update' ), 9999 );

		// Handles gift removed by the user.
		add_action( 'woocommerce_cart_item_removed', array( __CLASS__, 'cart_item_removed' ), 5 );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( __CLASS__, 'cart_item_set_quantity' ), 5 );
		add_action( 'woocommerce_cart_item_restored', array( __CLASS__, 'cart_item_restored' ) );

		// Process orders.
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'checkout_validation' ) );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'checkout_create_order' ) );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'checkout_create_order_line_item' ), 10, 3 );

		// Handles product price.
		add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'get_product_price' ), 9999, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( __CLASS__, 'get_product_price' ), 9999, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'get_product_price' ), 9999, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( __CLASS__, 'get_product_price' ), 9999, 2 );

		// Coupons.
		add_filter( 'woocommerce_coupon_is_valid', array( __CLASS__, 'validate_bogo_coupon' ), 20, 2 );
		add_filter( 'woocommerce_coupon_is_valid', array( __CLASS__, 'coupon_is_valid' ), 9999, 2 );
		add_filter( 'woocommerce_coupon_get_items_to_validate', array( __CLASS__, 'coupon_get_items_to_validate' ), 9999 );

		// Cart totals.
		add_action( 'wc_bogof_after_set_cart_item_discount', array( __CLASS__, 'calculate_items_subtotal' ) );
	}

	/**
	 * Add the discounts. Hooked on woocommerce_cart_loaded_from_session because "Smart coupons" call this hook more than one time.
	 */
	public static function init_discounts() {
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $key => $cart_item ) {

			unset( WC()->cart->cart_contents[ $key ]['data']->_bogof_discount );

			if ( isset( $cart_item['_bogof_discount'] ) && is_array( $cart_item['_bogof_discount'] ) ) {

				WC()->cart->cart_contents[ $key ]['data']->_bogof_discount = new WC_BOGOF_Cart_Item_Discount( $cart_item, $cart_item['_bogof_discount'] );

				/**
				 * Action: wc_bogof_init_cart_item_discount.
				 *
				 * @since 3.5.0
				 * @param array                       $cart_item Cart item data.
				 * @param WC_BOGOF_Cart_Item_Discount $discount Discount instance.
				 */
				do_action( 'wc_bogof_init_cart_item_discount', $cart_item, WC()->cart->cart_contents[ $key ]['data']->_bogof_discount );
			}
		}
	}

	/**
	 * Validate the free items.
	 */
	public static function validate_free_items() {
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $key => $cart_item ) {

			if ( self::is_free_item( $cart_item ) ) {

				$cart_rule_id = self::is_valid_free_item( $cart_item );

				if ( ! $cart_rule_id ) {
					unset( WC()->cart->cart_contents[ $key ]['data']->_bogof_discount );
					unset( WC()->cart->cart_contents[ $key ]['_bogof_discount'] );
					unset( WC()->cart->cart_contents[ $key ]['_bogof_free_item'] );
				}
			} elseif ( self::is_valid_discount( $cart_item ) ) {

				// Check the rules.
				foreach ( $cart_item['data']->_bogof_discount->get_rules() as $cart_rule_id => $quantity ) {
					if ( ! self::$cart_rules->exists( $cart_rule_id ) ) {
						WC()->cart->cart_contents[ $key ]['data']->_bogof_discount->remove_free_quantity( $cart_rule_id );
					}
				}
				if ( ! WC()->cart->cart_contents[ $key ]['data']->_bogof_discount->has_discount() ) {
					unset( WC()->cart->cart_contents[ $key ]['data']->_bogof_discount );
					unset( WC()->cart->cart_contents[ $key ]['_bogof_discount'] );
				}
			}
		}
	}

	/**
	 * Update free items qty on item removed.
	 *
	 * @param string $cart_item_key Cart item key.
	 */
	public static function cart_item_removed( $cart_item_key ) {
		$cart_contents = WC()->cart->get_removed_cart_contents();
		$cart_item     = $cart_contents[ $cart_item_key ];
		$cart_rule_id  = self::is_valid_free_item( $cart_item );

		if ( $cart_rule_id ) {
			// Add the rule to the removed rules array.
			self::$cart_rules->remove_by_user( $cart_rule_id );
		} else {
			// Restore the cart rule.
			self::$cart_rules->restore_by_user( $cart_item_key );
		}
	}

	/**
	 * Update free items qty on item qty updated.
	 *
	 * @param string $cart_item_key Cart item key.
	 */
	public static function cart_item_set_quantity( $cart_item_key ) {
		$cart_item = isset( WC()->cart->cart_contents[ $cart_item_key ] ) ? WC()->cart->cart_contents[ $cart_item_key ] : false;
		if ( ! self::is_valid_free_item( $cart_item ) ) {
			// Restore the cart rule.
			self::$cart_rules->restore_by_user( $cart_item_key );
		}
	}

	/**
	 * Unset free item flag after item restored
	 *
	 * @param string $cart_item_key Cart item key.
	 */
	public static function cart_item_restored( $cart_item_key ) {
		$cart_contents = WC()->cart->get_cart_contents();
		$cart_item     = isset( $cart_contents[ $cart_item_key ] ) ? $cart_contents[ $cart_item_key ] : false;

		if ( $cart_item && self::is_free_item( $cart_item ) ) {
			unset( WC()->cart->cart_contents[ $cart_item_key ]['_bogof_free_item'] );
			unset( WC()->cart->cart_contents[ $cart_item_key ]['_bogof_discount'] );
		}
	}

	/**
	 * Refresh cart rules quantities and discounts.
	 */
	public static function cart_update() {
		static $avoid_recursion = false;
		if ( $avoid_recursion ) {
			return;
		}
		$avoid_recursion = true;

		// Add the new cart rules.
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( self::is_free_item( $cart_item ) ) {
				continue;
			}
			self::$cart_rules->add( $cart_item );
		}

		// Remove all discounts.
		self::remove_all_discounts();

		// Refresh cart totals.
		self::$cart_totals = null;

		// Update cart rules.
		self::$cart_rules->update_cart();

		// Cart rules updated.
		do_action( 'wc_bogof_after_cart_rules_update' );

		$avoid_recursion = false;
	}

	/**
	 * Check the usage limit of each rule in the cart.
	 *
	 * @param array $posted Post data.
	 */
	public static function checkout_validation( $posted ) {
		$customer_ids = wc_bogof_user_ids();
		if ( ! empty( $posted['billing_email'] ) ) {
			$customer_ids[] = strtolower( sanitize_email( $posted['billing_email'] ) );
		}

		if ( ! count( $customer_ids ) ) {
			return;
		}

		foreach ( self::$cart_rules as $cart_rule_id => $cart_rule ) {

			$rule = $cart_rule->get_rule();

			if ( self::cart_rule_in_use( $cart_rule_id ) && ! $rule->is_usage_per_user_under_limit( $customer_ids ) ) {

				WC()->session->set( 'refresh_totals', true );

				wc_add_notice( __( 'You reached the usage limit of the offer. The order totals have been updated. Please confirm your order by pressing the "Place order" button at the bottom of the page.', 'wc-buy-one-get-one-free' ), 'error' );
				break;
			}
		}
	}

	/**
	 * Add the rule Id as order metadata.
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function checkout_create_order( $order ) {
		foreach ( self::$cart_rules as $cart_rule_id => $cart_rule ) {
			if ( self::cart_rule_in_use( $cart_rule_id ) ) {
				$cart_rule->get_rule()->increase_usage_count( $order );
			}
		}
	}

	/**
	 * Add the rule ID as item meta.
	 *
	 * @param  WC_Order_Item_Product $item          Order item data.
	 * @param  string                $cart_item_key Cart item key.
	 * @param  array                 $values        Order item values.
	 */
	public static function checkout_create_order_line_item( $item, $cart_item_key, $values ) {
		$rules         = array();
		$cart_rule_ids = array();
		$free_item     = false;
		$updated       = false;
		$cart_item     = WC()->cart->get_cart_item( $cart_item_key );

		if ( self::is_valid_free_item( $cart_item ) ) {
			$cart_rule_ids[] = $cart_item['_bogof_free_item'];
			$free_item       = true;

		} elseif ( self::is_valid_discount( $cart_item ) ) {
			$cart_rule_ids = array_keys( $cart_item['data']->_bogof_discount->get_rules() );
		}

		foreach ( $cart_rule_ids as $cart_rule_id ) {
			$cart_rule = self::$cart_rules->get( $cart_rule_id );
			if ( $cart_rule ) {
				$rules[ $cart_rule->get_rule_id() ] = $cart_rule->get_rule()->get_title();
			}
		}

		foreach ( array_unique( $rules ) as $rule_id => $rule_title ) {
			$item->add_meta_data( '_wc_bogof_rule_id', $rule_id );
			$updated = true;
		}

		if ( $updated ) {
			$item->add_meta_data( '_wc_bogof_rule_name', $rules );
			$item->add_meta_data( '_wc_bogof_free_item', wc_bool_to_string( $free_item ) );
		}
	}

	/**
	 * Return the zero price for free product in the cart.
	 *
	 * @param mixed      $price Product price.
	 * @param WC_Product $product Product instance.
	 */
	public static function get_product_price( $price, $product ) {
		if ( self::is_valid_discount( $product ) && $product->_bogof_discount->has_discount() ) {
			$price = $product->_bogof_discount->get_sale_price();
		}
		return $price;
	}

	/**
	 * Validate the a "BOGO coupon".
	 * The coupon is a "BOGO coupon" if the amount is zero, the type is one of the default coupon types and are in a BOGO rule.
	 *
	 * @throws Exception Error message.
	 * @param bool      $is_valid Is valid?.
	 * @param WC_Coupon $coupon Coupon object.
	 */
	public static function validate_bogo_coupon( $is_valid, $coupon ) {
		if ( ! $is_valid || $coupon->get_amount() > 0 || ! $coupon->is_type( array( 'percent', 'fixed_cart', 'fixed_product' ) ) || $coupon->get_free_shipping() ) {
			// It's no a BOGO coupon.
			return $is_valid;
		}

		$coupon_rules = wc_bogof_get_coupon_rule_ids( $coupon );

		if ( count( $coupon_rules ) ) {

			// It's a BOGO coupon: validate.

			$valid = false;

			foreach ( self::$cart_rules as $cart_rule ) {

				if ( in_array( $cart_rule->get_rule()->get_id(), $coupon_rules, true ) ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce' ), 109 );
			}
		}

		return $is_valid;
	}

	/**
	 * Disable the usage of coupons is there is a free item in the cart.
	 *
	 * @param bool      $is_valid Is valid?.
	 * @param WC_Coupon $coupon Coupon object.
	 * @return bool
	 */
	public static function coupon_is_valid( $is_valid, $coupon ) {
		if ( 'yes' !== get_option( 'wc_bogof_disable_coupons', 'no' ) || ! $is_valid ) {
			return $is_valid;
		}

		// Checks the coupon is in a cart rule.
		foreach ( self::$cart_rules as $cart_rule ) {
			if ( in_array( $coupon->get_id(), $cart_rule->get_rule()->get_coupon_ids() ) ) { // phpcs:ignore WordPress.PHP.StrictInArray
				return true;
			}
		}

		// If there is a free item coupon is invalid.
		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			if ( self::is_valid_free_item( $cart_item ) || self::is_valid_discount( $cart_item ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Remove the free items from the coupon validations.
	 *
	 * @param array $items Items to validate.
	 * @return array
	 */
	public static function coupon_get_items_to_validate( $items ) {
		foreach ( $items as $key => $item ) {
			$cart_rule_id = self::is_valid_free_item( $item->object );
			$cart_rule    = $cart_rule_id ? self::$cart_rules->get( $cart_rule_id ) : false;
			if ( $cart_rule && $cart_rule->get_rule()->get_exclude_coupon_validation() ) {
				unset( $items[ $key ] );
			}
		}
		return $items;
	}

	/**
	 * Calculate items subtotal after adding a discount.
	 */
	public static function calculate_items_subtotal() {
		if ( ! empty( self::$cart_totals ) ) {
			self::$cart_totals->calculate_items_subtotal();
		}
	}

	/**
	 * ---------------------------------------------
	 * Helper functions.
	 * ---------------------------------------------
	 */

	/**
	 * Return the cart subtotal.
	 *
	 * @since 2.1.0
	 * @return float
	 */
	public static function cart_subtotal() {
		if ( empty( self::$cart_totals ) ) {
			self::$cart_totals = new WC_BOGOF_Cart_Totals();
		}
		return self::$cart_totals->get_subtotal();
	}

	/**
	 * Set an discount price.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param mixed  $cart_rule_id Rule ID.
	 * @param int    $free_qty Free quantity.
	 * @param bool   $remove_discounts Remove previus discounts?.
	 * @return array
	 */
	public static function set_cart_item_discount( $cart_item_key, $cart_rule_id, $free_qty, $remove_discounts = true ) {
		$cart_item_data = WC()->cart->cart_contents[ $cart_item_key ];

		if ( ! isset( $cart_item_data['data'] ) || ! is_object( $cart_item_data['data'] ) ) {
			return;
		}

		$_product = &$cart_item_data['data'];

		if ( ! isset( $_product->_bogof_discount ) || $remove_discounts ) {
			unset( $_product->_bogof_discount );
			$_product->_bogof_discount = new WC_BOGOF_Cart_Item_Discount( $cart_item_data );
		}

		$_product->_bogof_discount->set_free_quantity( $cart_rule_id, $free_qty );

		WC()->cart->cart_contents[ $cart_item_key ]['_bogof_discount'] = $_product->_bogof_discount->get_rules(); // Do not store objects in the session.

		do_action( 'wc_bogof_after_set_cart_item_discount', $cart_item_data, $_product->_bogof_discount );
	}

	/**
	 * Is a free cart item?.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @return bool
	 */
	public static function is_free_item( $cart_item_data ) {
		return isset( $cart_item_data['_bogof_free_item'] );
	}

	/**
	 * Is a valid free cart item?.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @return string Cart rule ID or FALSE if it does not valid.
	 */
	public static function is_valid_free_item( $cart_item_data ) {
		return isset( $cart_item_data['_bogof_free_item'] ) && self::$cart_rules->exists( $cart_item_data['_bogof_free_item'] ) && self::$cart_rules->get( $cart_item_data['_bogof_free_item'] )->support_gifts() ? $cart_item_data['_bogof_free_item'] : false;
	}

	/**
	 * Is a valid offer?.
	 *
	 * @param array|object $data Data to check.
	 * @return bool
	 */
	public static function is_valid_discount( $data ) {
		$discount = false;
		if ( is_array( $data ) && ! self::is_valid_free_item( $data ) && isset( $data['data'] ) && is_object( $data['data'] ) && isset( $data['data']->_bogof_discount ) ) {
			$discount = $data['data']->_bogof_discount;
		} elseif ( is_object( $data ) && isset( $data->_bogof_discount ) ) {
			$discount = $data->_bogof_discount;
		}
		return $discount && is_a( $discount, 'WC_BOGOF_Cart_Item_Discount' ) && $discount->has_cart_rule( self::$cart_rules->ids() );
	}

	/**
	 * Returns the free items in the cart of a rule.
	 *
	 * @param mixed $cart_rule_id Rule ID.
	 * @return array
	 */
	public static function get_free_items( $cart_rule_id ) {
		$items         = array();
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $key => $cart_item ) {
			if ( self::is_valid_free_item( $cart_item ) === $cart_rule_id ) {
				$items[ $key ] = $cart_item;
			}
		}
		return $items;
	}

	/**
	 * Returns the number of free items in the cart of a rule.
	 *
	 * @param mixed $cart_rule_id Cart rule ID.
	 * @return int
	 */
	public static function get_free_quantity( $cart_rule_id ) {
		$qty           = 0;
		$cart_contents = self::get_free_items( $cart_rule_id );
		foreach ( $cart_contents as $cart_item ) {
			$qty += $cart_item['quantity'];
		}
		return $qty;
	}

	/**
	 * Removes the free items of a cart rule.
	 *
	 * @param mixed $cart_rule_id Cart rule ID.
	 */
	public static function remove_free_items( $cart_rule_id ) {
		$free_items = self::get_free_items( $cart_rule_id );
		foreach ( array_keys( $free_items ) as $cart_item_key ) {
			unset( WC()->cart->cart_contents[ $cart_item_key ] );
		}
	}

	/**
	 * Removes all discounts.
	 */
	public static function remove_all_discounts() {
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $item_key => $cart_item_data ) {
			if ( ! self::is_free_item( $cart_item_data ) && self::is_valid_discount( $cart_item_data ) ) {
				unset( WC()->cart->cart_contents[ $item_key ]['data']->_bogof_discount );
				unset( WC()->cart->cart_contents[ $item_key ]['_bogof_discount'] );
			}
		}
	}

	/**
	 * Removes the discount of a cart rule.
	 *
	 * @param string|array $cart_rule_id Cart rule ID or array of IDs.
	 */
	public static function remove_discount( $cart_rule_id ) {
		$cart_contents = WC()->cart->get_cart_contents();
		$cart_rule_ids = is_array( $cart_rule_id ) ? $cart_rule_id : array( $cart_rule_id );

		foreach ( $cart_rule_ids as $id ) {
			foreach ( self::$cart_rules->get_cart_item_keys( $id ) as $item_key ) {
				if ( isset( $cart_contents[ $item_key ] ) && self::is_valid_discount( $cart_contents[ $item_key ] ) ) {
					WC()->cart->cart_contents[ $item_key ]['data']->_bogof_discount->remove_free_quantity( $id );
					WC()->cart->cart_contents[ $item_key ]['_bogof_discount'] = WC()->cart->cart_contents[ $item_key ]['data']->_bogof_discount->get_rules();
				}
			}
		}
	}

	/**
	 * Returns the number of items available for free in the shop.
	 *
	 * @return int
	 */
	public static function get_shop_free_quantity() {
		if ( is_null( self::$cart_rules ) ) {
			return 0;
		}

		$qty = 0;
		foreach ( self::$cart_rules as $rule ) {
			$qty += $rule->get_shop_free_quantity();
		}
		return $qty;
	}

	/**
	 * Returns the free available qty for a product.
	 *
	 * @param WC_Product $product Product instance.
	 * @return int
	 */
	public static function get_product_shop_free_quantity( $product ) {
		$qty = 0;
		foreach ( self::$cart_rules as $cart_rule ) {
			if ( $cart_rule->is_shop_avilable_free_product( $product ) ) {
				$qty += $cart_rule->get_shop_free_quantity();
			}
		}
		return $qty;
	}

	/**
	 * Returns the hash based on cart contents and bogo rules.
	 *
	 * @return string hash for cart content
	 */
	public static function get_hash() {
		if ( ! isset( WC()->cart ) ) {
			return false;
		}
		$pieces = array(
			WC_Cache_Helper::get_transient_version( 'bogof_rules' ),
		);
		foreach ( self::$cart_rules as $rule_id => $rule ) {
			$pieces[ $rule_id ] = $rule->get_free_products_in();
		}
		return md5( wp_json_encode( $pieces ) );
	}

	/**
	 * Is there a free item in the cart that that belongs to the "cart rule"?
	 *
	 * @param string $cart_rule_id Cart rule ID.
	 * @return boolean
	 */
	public static function cart_rule_has_free_item( $cart_rule_id ) {
		return count( self::get_free_items( $cart_rule_id ) ) > 0;
	}

	/**
	 * Is there a discount in the cart that that belongs to the "cart rule"?
	 *
	 * @param string $cart_rule_id Cart rule ID.
	 * @return boolean
	 */
	public static function cart_rule_has_discount( $cart_rule_id ) {
		$has_discount  = false;
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $cart_item ) {
			if ( self::is_valid_discount( $cart_item ) && $cart_item['data']->_bogof_discount->has_cart_rule( $cart_rule_id ) ) {
				$has_discount = true;
				break;
			}
		}
		return $has_discount;
	}

	/**
	 * Returns is the cart rule is in use.
	 *
	 * @param string $cart_rule_id Cart rule ID.
	 * @return boolean
	 */
	public static function cart_rule_in_use( $cart_rule_id ) {
		return self::cart_rule_has_free_item( $cart_rule_id ) || self::cart_rule_has_discount( $cart_rule_id );
	}
}
