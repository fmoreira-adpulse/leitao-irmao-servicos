<?php
/**
 * Buy One Get One Free Deprecated Functions
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alert for deprecated hooks on after_metabox_settings_fields.
 *
 * @param string $old_hook Old hook name.
 * @param string $deprecated_version Deprecated since version.
 * @param string $alternative Alternative.
 * @param bool   $do_action Do run the old hook?.
 */
function wc_bogof_deprecated_hook( $old_hook, $deprecated_version, $alternative = '', $do_action = false ) {
	if ( 'wc_bogof_' === substr( $old_hook, 0, 9 ) && has_action( $old_hook ) ) {
		wc_deprecated_hook( esc_html( $old_hook ), esc_html( $deprecated_version ), esc_html( $alternative ) );
		if ( $do_action ) {
			do_action( $old_hook ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		}
	}
}

/**
 * Alert for deprecated hooks on after_metabox_settings_fields.
 */
function wc_bogof_deprecated_after_metabox_settings_fields() {
	wc_bogof_deprecated_hook( 'wc_bogof_rule_data_tabs', '3.0.0' );
	wc_bogof_deprecated_hook( 'wc_bogof_rule_data_panels', '3.0.0', 'wc_bogof_after_metabox_settings_fields', true );
}
add_action( 'wc_bogof_after_metabox_settings_fields', 'wc_bogof_deprecated_after_metabox_settings_fields', -100 );

/**
 * Output an enhanced select.
 *
 * @deprecated 3.0.0
 * @param array $field Data about the field to render.
 */
function wc_bogof_enhanced_select( $field ) {
	wc_deprecated_function( 'wc_bogof_enhanced_select', '3.0.0', 'WC_BOGOF_Admin_Meta_Boxes::output_metabox_field' );
	WC_BOGOF_Admin_Meta_Boxes::output_metabox_field(
		array_merge(
			$field,
			array(
				'type' => 'enhanced-select',
			)
		)
	);
}

/**
 * Output an search product select.
 *
 * @deprecated 3.0.0
 * @param array $field Data about the field to render.
 */
function wc_bogof_search_product_select( $field ) {
	wc_deprecated_function( 'wc_bogof_search_product_select', '3.0.0', 'WC_BOGOF_Admin_Meta_Boxes::output_metabox_field' );
	$type = isset( $field['object'] ) && 'product' === $field['object'] ? 'search-product' : 'search-coupon';
	WC_BOGOF_Admin_Meta_Boxes::output_metabox_field(
		array_merge(
			$field,
			array(
				'type' => $type,
			)
		)
	);
}

/**
 * Output enhanced select options.
 *
 * @deprecated 3.0.0
 * @param array $options Options in array.
 * @param array $values Values selected.
 */
function wc_bogof_enhanced_select_options( $options, $values ) {
	wc_deprecated_function( 'wc_bogof_enhanced_select_options', '3.0.0' );
}
