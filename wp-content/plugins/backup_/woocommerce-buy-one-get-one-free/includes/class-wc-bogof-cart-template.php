<?php
/**
 * WooCommerce Buy One Get One Free cart template actions.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart_Template Class
 */
class WC_BOGOF_Cart_Template {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'woocommerce_before_cart', array( __CLASS__, 'before_cart' ) );
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'cart_item_quantity' ), 9999, 3 );
		add_filter( 'woocommerce_coupon_discount_amount_html', array( __CLASS__, 'coupon_discount_amount_html' ), 9999, 2 );
	}

	/**
	 * Cart styles.
	 */
	public static function enqueue_scripts() {
		if ( is_cart() ) {
			wp_enqueue_style( 'cart', plugins_url( 'assets/css/cart.css', WC_BOGOF_PLUGIN_FILE ), array(), WC_Buy_One_Get_One_Free::$version );
		}
	}

	/**
	 * Add the filter for the cart items.
	 */
	public static function before_cart() {
		// Add the cart filters.
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'before_cart_item_price' ), -1, 2 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'after_cart_item_price' ), 9999, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'cart_item_subtotal' ), 9999, 2 );
	}

	/**
	 * Quantity of free items have not be able updated
	 *
	 * @param int    $product_quantity Product quantity.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $cart_item Cart item.
	 * @return string
	 */
	public static function cart_item_quantity( $product_quantity, $cart_item_key, $cart_item = false ) {
		if ( ! $cart_item ) {
			$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		}

		if ( $cart_item && WC_BOGOF_Cart::is_valid_free_item( $cart_item ) ) {
			$product_quantity = sprintf( '%s <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item['quantity'], $cart_item_key, $cart_item['quantity'] );
		}
		return $product_quantity;
	}

	/**
	 * Cart item price. For BOGO offers returns the original price.
	 *
	 * @param string $cart_price Price to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function before_cart_item_price( $cart_price, $cart_item ) {
		if ( WC_BOGOF_Cart::is_valid_discount( $cart_item ) ) {
			// Rename the BOGOF discount property to display the default price in the cart.
			$cart_item['data']->_bogof_discount_removed = $cart_item['data']->_bogof_discount;
			unset( $cart_item['data']->_bogof_discount );
		}

		return $cart_price;
	}

	/**
	 * After cart item price. Restore the BOGO discount.
	 *
	 * @param string $cart_price Price to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function after_cart_item_price( $cart_price, $cart_item ) {
		if ( isset( $cart_item['data']->_bogof_discount_removed ) ) {

			$cart_price = wc_bogof_get_cart_product_price( $cart_item['data'] );

			// Restore the BOGOF discount.
			$cart_item['data']->_bogof_discount = $cart_item['data']->_bogof_discount_removed;

			unset( $cart_item['data']->_bogof_discount_removed );

			if ( 'yes' === get_option( 'wc_bogof_base_regular_price', 'no' ) ) {
				$cart_price = wc_bogof_get_cart_product_price(
					$cart_item['data'],
					array(
						'price' => $cart_item['data']->_bogof_discount->get_base_price(),
						'qty'   => 1,
					)
				);
			}

			$cart_price = wc_price( $cart_price );

		} elseif ( WC_BOGOF_Cart::is_valid_free_item( $cart_item ) && WC_BOGOF_Cart::is_valid_discount( $cart_item['data'] ) ) {

			if ( $cart_item['data']->get_price() > 0 ) {
				$cart_price = wc_format_sale_price(
					wc_bogof_get_cart_product_price(
						$cart_item['data'],
						array(
							'price' => $cart_item['data']->_bogof_discount->get_base_price(),
							'qty'   => 1,
						)
					),
					wc_bogof_get_cart_product_price(
						$cart_item['data'],
						array(
							'price' => $cart_item['data']->get_price(),
							'qty'   => 1,
						)
					)
				);
			} else {
				// Free!.
				$cart_price = apply_filters( 'wc_bogof_free_item_cart_price', __( 'Free!', 'woocommerce' ) );
			}
		}
		return $cart_price;
	}

	/**
	 * Cart item subtotal. For BOGO offers display the discount.
	 *
	 * @param string $cart_subtotal Subtotal to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function cart_item_subtotal( $cart_subtotal, $cart_item ) {
		if ( WC_BOGOF_Cart::is_valid_discount( $cart_item ) ) {

			$free_quantity = $cart_item['data']->_bogof_discount->get_free_quantity();
			$base_price    = wc_bogof_get_cart_product_price( $cart_item['data'], array( 'price' => $cart_item['data']->_bogof_discount->get_base_price() ) );
			$raw_discount  = wc_bogof_get_cart_product_price( $cart_item['data'], array( 'price' => $cart_item['data']->_bogof_discount->get_discount() ) ) / $cart_item['data']->_bogof_discount->get_free_quantity();
			$raw_subtotal  = $base_price * $cart_item['quantity'];

			$line_subtotal = sprintf( '<span class="bogof_discount_line">%s</span>', wc_price( $raw_subtotal ) );
			$line_discount = sprintf( '<span class="bogof_discount_line discount">&ndash; %s  &times; %s</span>', $free_quantity, wc_price( $raw_discount ) );
			$line_total    = sprintf( '<span class="bogof_discount_line subtotal">%s%s</span>', apply_filters( 'wc_bogof_discount_line_subtotal_prefix', esc_html__( 'Subtotal', 'woocommerce' ) . ':&nbsp;', $cart_subtotal, $cart_item ), $cart_subtotal );
			$cart_subtotal = '<span class="bogof_discount_item_subtotal">' . $line_subtotal . $line_discount . $line_total . '</span>';
		}
		return $cart_subtotal;
	}

	/**
	 * Coupon amount HTML.
	 *
	 * @param string    $discount_amount_html Coupon amount HTML.
	 * @param WC_Coupon $coupon Coupon object.
	 * @return string
	 */
	public static function coupon_discount_amount_html( $discount_amount_html, $coupon ) {

		if ( ( ! is_callable( array( $coupon, 'is_type' ) ) && $coupon->is_type( array( 'percent', 'fixed_cart' ) ) ) ) {
			return $discount_amount_html;
		}

		$in_cart_rule = false;

		// Checks the coupon is in a cart rule.
		foreach ( wc_bogof_cart_rules() as $cart_rule ) {
			if ( in_array( $coupon->get_id(), $cart_rule->get_rule()->get_coupon_ids() ) ) { // phpcs:ignore WordPress.PHP.StrictInArray
				$in_cart_rule = true;
				break;
			}
		}

		if ( $in_cart_rule && floatval( 0 ) === floatval( $coupon->get_amount() ) ) {
			// Do not display 0.00.
			$discount_amount_html = '';
		}

		return $discount_amount_html;
	}
}
