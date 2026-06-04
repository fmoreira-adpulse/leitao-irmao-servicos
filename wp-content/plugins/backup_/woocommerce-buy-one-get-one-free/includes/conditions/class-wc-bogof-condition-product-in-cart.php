<?php
/**
 * Condition product is in cart class.
 *
 * @since 3.3.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Product_In_Cart Class
 */
class WC_BOGOF_Condition_Product_In_Cart extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'product_in_cart';
		$this->title    = __( 'Product is in cart', 'wc-buy-one-get-one-free' );
		$this->supports = array( '_gift_products' );
	}

	/**
	 * Returns the ID of the products in the cart.
	 *
	 * @return array
	 */
	protected function get_cart_product_ids() {
		$ids = [];

		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			if ( WC_BOGOF_Cart::is_free_item( $cart_item ) || ! ( isset( $cart_item['data'] ) && is_callable( [ $cart_item['data'], 'get_id' ] ) ) ) {
				continue;
			}
			$ids[] = $cart_item['data']->get_id();
		}

		return $ids;
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return array(
			'yes' => __( 'Yes', 'wc-buy-one-get-one-free' ),
			'no'  => __( 'No', 'wc-buy-one-get-one-free' ),
		);
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return array();
	}

	/**
	 * Is the condition data empty?
	 *
	 * @param array $data Array that contains the condition data.
	 * @return bool
	 */
	public function is_empty( $data ) {
		return empty( $data['type'] );
	}

	/**
	 * Evaluate condition field.
	 *
	 * @param array $data Condition field data.
	 * @param mixed $value Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$product_id = false;
		$check      = false;

		if ( is_numeric( $value ) ) {
			$product_id = absint( $value );
		} elseif ( isset( $value['data'] ) && is_callable( [ $value['data'], 'get_id' ] ) ) {
			$product_id = $value['data']->get_id();
		}

		if ( $product_id ) {
			$check = in_array( $product_id, $this->get_cart_product_ids(), true );
			if ( $this->modifier_is( 'no' ) ) {
				$check = ! $check;
			}
		}

		return $check;
	}

	/**
	 * Return the WHERE clause that returns the products that meet the condition.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function get_where_clause( $data ) {
		global $wpdb;

		$product_ids = $this->get_cart_product_ids();

		if ( empty( $product_ids ) ) {
			return '1>1';
		}

		$operator = $this->modifier_is( $data, 'no' ) ? 'NOT IN' : 'IN';

		return $wpdb->posts . '.ID ' . $operator . ' (' . implode( ',', $product_ids ) . ')';
	}

	/**
	 * Return the condition as string.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function to_string( $data ) {
		return $this->title . ' ' . $data['modifier'];
	}
}
