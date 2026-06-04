<?php
/**
 * Global Condition Taxonomy class.
 *
 * @since 4.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Taxonomy Class
 */
class WC_BOGOF_Condition_Taxonomy extends WC_BOGOF_Abstract_Condition_Product_Base {

	/**
	 * Constructor.
	 *
	 * @param string $taxonomy_name Taxonomy name.
	 * @param string $taxonomy_label Taxonomy label.
	 */
	public function __construct( $taxonomy_name = '', $taxonomy_label = '' ) {
		$this->id    = $taxonomy_name;
		$this->title = $taxonomy_label;
	}

	/**
	 * Returns the taxonomy name.
	 *
	 * @return string|array
	 */
	protected function get_taxonomy_name() {
		return $this->id;
	}

	/**
	 * Return the product taxonomy terms IDs.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	protected function get_product_terms( $product_id ) {
		$cache_key  = $this->get_cache_key( $this->get_id() . "_{$product_id}", "product_{$product_id}" );
		$post_terms = $this->cache_get( $cache_key );
		if ( ! is_array( $post_terms ) ) {
			$post_terms = wp_get_post_terms( $product_id, $this->get_taxonomy_name(), array( 'fields' => 'ids' ) );
			$this->cache_set( $cache_key, $post_terms );
		}
		return $post_terms;
	}

	/**
	 * Checks if a value exists in an array.
	 *
	 * @param int   $product_id Product ID to check.
	 * @param array $haystack The array.
	 * @return bool
	 */
	protected function in_array( $product_id, $haystack ) {
		$post_terms = $this->get_product_terms( $product_id );
		return wc_bogof_in_array_intersect( $haystack, $post_terms );
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return array(
			'type'        => 'enhanced-select',
			'options'     => $this->get_metabox_field_options(),
			'placeholder' => __( 'Choose', 'wc-buy-one-get-one-free' ) . '&hellip;',
		);
	}

	/**
	 * Returns the "value" metabox field options.
	 *
	 * @return array
	 */
	protected function get_metabox_field_options() {
		$raw_terms = get_terms(
			array(
				'taxonomy'   => $this->get_taxonomy_name(),
				'hide_empty' => 0,
			)
		);
		$terms     = array();
		$options   = array();

		foreach ( $raw_terms as $term ) {
			$terms[ $term->term_id ] = array(
				'name'   => $term->name,
				'parent' => isset( $term->parent ) ? $term->parent : 0,
			);
		}

		foreach ( $terms as $term_id => $term ) {
			$options[ $term_id ] = $this->get_metabox_field_option_name( $term_id, $terms );
		}
		asort( $options, SORT_NATURAL | SORT_FLAG_CASE );

		return $options;
	}

	/**
	 * Retruns the term name for the metabox field.
	 *
	 * @param int   $term_id Term ID.
	 * @param array $terms Array of terms.
	 */
	protected function get_metabox_field_option_name( $term_id, $terms ) {

		if ( empty( $terms[ $term_id ]['parent'] ) ) {
			return $terms[ $term_id ]['name'];
		} else {
			return $this->get_metabox_field_option_name( $terms[ $term_id ]['parent'], $terms ) . '&nbsp;>&nbsp;' . $terms[ $term_id ]['name'];
		}
	}

	/**
	 * Return the WHERE clause that returns the products that meet the condition.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function get_where_clause( $data ) {
		global $wpdb;
		// Empty conditions always return ''.
		if ( empty( $data['value'] ) || ! is_array( $data['value'] ) ) {
			return false;
		}
		$operator          = $this->modifier_is( $data, 'not-in' ) ? 'NOT IN' : 'IN';
		$term_taxonomy_ids = implode( ',', array_map( 'absint', $data['value'] ) );

		return "{$wpdb->posts}.ID {$operator} ( SELECT tr.object_id FROM {$wpdb->term_taxonomy} AS tt INNER JOIN {$wpdb->term_relationships} AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy = '{$this->get_taxonomy_name()}' AND tt.term_id IN ({$term_taxonomy_ids}) )";
	}

	/**
	 * Get formatted values.
	 *
	 * @see WC_BOGOF_Abstract_Condition_Product_Base::to_string
	 * @param array $values Values to formatted.
	 * @return array
	 */
	protected function get_formatted_values( $values ) {
		$terms            = $this->get_metabox_field_options();
		$formatted_values = array();
		foreach ( $values as $term_id ) {
			if ( ! empty( $terms[ $term_id ] ) ) {
				$formatted_values[] = $terms[ $term_id ];
			}
		}
		return $formatted_values;
	}
}
