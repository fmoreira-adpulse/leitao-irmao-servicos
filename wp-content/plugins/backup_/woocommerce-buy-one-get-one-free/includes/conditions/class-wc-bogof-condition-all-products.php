<?php
/**
 * Condition Product class.
 *
 * @since 3.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Product Class
 */
class WC_BOGOF_Condition_All_Products extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'all_products';
		$this->title = __( 'All Products', 'wc-buy-one-get-one-free' );
	}

	/**
	 * Evaluate condition field.
	 *
	 * @param array $data Condition field data.
	 * @param mixed $value Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		return true;
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return array();
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
	 * Return the WHERE clause that returns the products that meet the condition.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function get_where_clause( $data ) {
		return '1=1';
	}

	/**
	 * Return the condition as string.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function to_string( $data ) {
		return $this->title;
	}
}
