<?php
/**
 * WooCommerce Buy One Get One Free calculate cart subtotals (items - discounts).
 *
 * It's not possible to use the WooCommerce class. Built a custom class to calculate the cart subtotal
 *
 * @since 2.1
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart_Totals Class
 */
class WC_BOGOF_Cart_Totals {

	/**
	 * Items subtotal.
	 *
	 * @var float
	 */
	protected $items_subtotal = 0;


	/**
	 * Discounts total.
	 *
	 * @var float
	 */
	protected $discounts_total = 0;


	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( empty( WC()->cart ) || ! is_a( WC()->cart, 'WC_Cart' ) ) {
			return;
		}

		$this->calculate();
	}

	/**
	 * Run all calculations methods.
	 */
	public function calculate() {
		do_action( 'wc_bogof_before_calculate_totals' );

		$this->calculate_items_subtotal();
		$this->calculate_discounts();

		do_action( 'wc_bogof_after_calculate_totals' );
	}

	/**
	 * Calculate items total.
	 */
	public function calculate_items_subtotal() {
		$this->items_subtotal = 0;

		$cart_contents = array_filter( WC()->cart->get_cart_contents() );
		foreach ( $cart_contents as $key => $cart_item ) {
			if ( ! WC_BOGOF_Cart::is_free_item( $cart_item ) ) {
				$this->items_subtotal += wc_bogof_get_cart_product_price( $cart_item['data'], array( 'qty' => $cart_item['quantity'] ) );
			}
		}
	}

	/**
	 * Calculate COUPON based discounts which change item prices.
	 *
	 * @uses  WC_Discounts class.
	 */
	public function calculate_discounts() {
		$cart              = WC()->cart;
		$price_include_tax = wc_prices_include_tax();
		$discounts         = new WC_Discounts( $cart );

		$discounts->set_items_from_cart( $cart );

		$coupons = $this->get_coupons_from_cart();

		foreach ( $coupons as $coupon ) {
			$discounts->apply_coupon( $coupon );
		}
		$coupon_discounts = $discounts->get_discounts_by_item();

		$this->discounts_total = array_sum( $coupon_discounts );

		if ( $cart->display_prices_including_tax() !== $price_include_tax ) {
			// Remove/Add taxes from the coupon discounts.
			foreach ( $coupon_discounts as $item_key => $coupon_discount ) {
				$item = $cart->get_cart_item( $item_key );

				if ( ! empty( $item ) && isset( $item['data'] ) && $item['data']->is_taxable() ) {

					$tax_rates = $this->get_product_tax_rates( $item['data'] );
					$item_tax  = wc_round_tax_total( array_sum( WC_Tax::calc_tax( $coupon_discount, $tax_rates, $price_include_tax ) ) );

					if ( $price_include_tax ) {
						$this->discounts_total -= $item_tax;
					} else {
						$this->discounts_total += $item_tax;
					}
				}
			}
		}

	}

	/**
	 * Return array of coupon objects from the cart.
	 */
	protected function get_coupons_from_cart() {
		$coupons = WC()->cart->get_coupons();

		foreach ( $coupons as $coupon ) {
			switch ( $coupon->get_discount_type() ) {
				case 'fixed_product':
					$coupon->sort = 1;
					break;
				case 'percent':
					$coupon->sort = 2;
					break;
				case 'fixed_cart':
					$coupon->sort = 3;
					break;
				default:
					$coupon->sort = 0;
					break;
			}

			// Allow plugins to override the default order.
			$coupon->sort = apply_filters( 'woocommerce_coupon_sort', $coupon->sort, $coupon );
		}

		uasort( $coupons, array( $this, 'sort_coupons_callback' ) );
		return $coupons;
	}

	/**
	 * Sort coupons so discounts apply consistently across installs.
	 *
	 * In order of priority;
	 *  - sort param
	 *  - usage restriction
	 *  - coupon value
	 *  - ID
	 *
	 * @param WC_Coupon $a Coupon object.
	 * @param WC_Coupon $b Coupon object.
	 * @return int
	 */
	protected function sort_coupons_callback( $a, $b ) {
		if ( $a->sort === $b->sort ) {
			if ( $a->get_limit_usage_to_x_items() === $b->get_limit_usage_to_x_items() ) {
				if ( $a->get_amount() === $b->get_amount() ) {
					return $b->get_id() - $a->get_id();
				}
				return ( $a->get_amount() < $b->get_amount() ) ? -1 : 1;
			}
			return ( $a->get_limit_usage_to_x_items() < $b->get_limit_usage_to_x_items() ) ? -1 : 1;
		}
		return ( $a->sort < $b->sort ) ? -1 : 1;
	}

	/**
	 * Get tax rates for an product.
	 *
	 * @param  WC_Product $product Product to get tax rates for.
	 * @return array of taxes
	 */
	protected function get_product_tax_rates( $product ) {
		if ( ! wc_tax_enabled() ) {
			return array();
		}
		return WC_Tax::get_rates( $product->get_tax_class(), WC()->cart->get_customer() );
	}

	/**
	 * Return the cart subtotal.
	 *
	 * @return float
	 */
	public function get_subtotal() {
		return $this->items_subtotal - $this->discounts_total;
	}

}
