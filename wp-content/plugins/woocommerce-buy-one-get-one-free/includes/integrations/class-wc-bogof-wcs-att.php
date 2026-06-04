<?php
/**
 * Buy One Get One Free - All Products For Subscriptions by SomewhereWarm
 *
 * @see https://woocommerce.com/es-es/products/all-products-for-woocommerce-subscriptions/
 * @since 1.3.7
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_WCS_ATT Class
 */
class WC_BOGOF_WCS_ATT {

	/**
	 * Retrun the minimun version required.
	 */
	public static function min_version_required() {
		return '2.1.5';
	}

	/**
	 * Returns the extension name.
	 */
	public static function extension_name() {
		return 'All Products for Subscriptions';
	}

	/**
	 * Checks the minimum version required.
	 */
	public static function check_min_version() {
		return version_compare( WCS_ATT::VERSION, static::min_version_required(), '>=' );
	}

	/**
	 * Init hooks
	 */
	public static function init() {
		add_filter( 'wcsatt_cart_item_options', array( __CLASS__, 'cart_item_options' ), 100, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 110 );
		add_filter( 'wc_bogof_load_conditions', array( __CLASS__, 'load_conditions' ) );
		add_action( 'wc_bogof_before_choose_your_gift_loop', array( __CLASS__, 'disable_all_product_for_subscriptions' ) );
		add_action( 'wc_bogof_after_choose_your_gift_loop', array( __CLASS__, 'enable_all_product_for_subscriptions' ) );
		add_action( 'wc_bogof_choose_your_gift_init', array( __CLASS__, 'disable_all_product_for_subscriptions' ) );
		add_filter( 'woocommerce_update_cart_action_cart_updated', array( __CLASS__, 'subscription_cart_update' ), 1 );
	}

	/**
	 * Disable the subscriptions options for the free product.
	 *
	 * @param array $options Subscriptions options.
	 * @param array $subscription_schemes Subscription schemes.
	 * @param array $cart_item Cart item data.
	 * @return array
	 */
	public static function cart_item_options( $options, $subscription_schemes, $cart_item ) {
		if ( WC_BOGOF_Cart::is_free_item( $cart_item ) ) {
			$options = array( $options[0] );
		}
		return $options;
	}

	/**
	 * Set free product
	 *
	 * @param array $session_data Session data.
	 * @return array
	 */
	public static function get_cart_item_from_session( $session_data ) {
		if ( WC_BOGOF_Cart::is_free_item( $session_data ) ) {
			unset( $session_data['wcsatt_data'] );
		}
		return $session_data;
	}

	/**
	 * Add the All Product for WooCommerce Subsctiption condition.
	 *
	 * @param array $conditions Conditions array.
	 * @return array
	 */
	public static function load_conditions( $conditions ) {
		$conditions = is_array( $conditions ) ? $conditions : array();

		if ( ! class_exists( 'WC_BOGOF_Condition_WCS_ATT' ) ) {
			include_once dirname( WC_BOGOF_PLUGIN_FILE ) . '/includes/conditions/class-wc-bogof-condition-wcs-att.php';
		}

		$conditions[] = new WC_BOGOF_Condition_WCS_ATT();

		return $conditions;
	}

	/**
	 * Disable the All Product for Subscription support on products.
	 */
	public static function disable_all_product_for_subscriptions() {
		add_filter( 'wcsatt_product_supports_feature', array( __CLASS__, 'product_supports_feature' ), 9999 );
	}

	/**
	 * Enable the All Product for Subscription support on products.
	 */
	public static function enable_all_product_for_subscriptions() {
		remove_filter( 'wcsatt_product_supports_feature', array( __CLASS__, 'product_supports_feature' ), 9999 );
	}

	/**
	 * Return false to disable the All Product for Subscription support on products.
	 *
	 * @param bool $is_feature_supported Is feature supported?.
	 */
	public static function product_supports_feature( $is_feature_supported ) {
		return false;
	}

	/**
	 * Force cart update after subscription options changes.
	 *
	 * @param  bool $updated Has the cart been updated?.
	 * @return bool
	 */
	public static function subscription_cart_update( $updated ) {

		$changed = false;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! isset( $_POST['cart'][ $cart_item_key ]['convert_to_sub'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				continue;
			}

			$posted_key   = is_callable( array( 'WCS_ATT_Cart', 'get_posted_subscription_scheme' ) ) ? WCS_ATT_Cart::get_posted_subscription_scheme( $cart_item_key ) : false;
			$existing_key = isset( $cart_item['wcsatt_data']['active_subscription_scheme'] ) ? $cart_item['wcsatt_data']['active_subscription_scheme'] : false;

			if ( ( empty( $posted_key ) && ! empty( $existing_key ) ) || ! empty( $posted_key ) && empty( $existing_key ) ) {
				// Subscription changed. Update is required.
				$changed = true;
				break;
			}
		}

		if ( $changed ) {
			// Set session to force update.
			add_filter( 'woocommerce_update_cart_action_cart_updated', array( __CLASS__, 'update_cart_rules' ), 100 );
		}

		return $updated;
	}

	/**
	 * Update the cart rules.
	 *
	 * @param  bool $updated Has the cart been updated?.
	 */
	public static function update_cart_rules( $updated ) {
		WC_BOGOF_Cart::cart_update();
		return $updated;
	}
}
