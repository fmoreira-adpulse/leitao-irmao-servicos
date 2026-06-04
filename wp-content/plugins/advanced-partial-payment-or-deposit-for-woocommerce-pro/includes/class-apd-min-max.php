<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Min/Max deposit amount rules.
 */
class APD_Min_Max {

    public function __construct() {
        add_filter( 'apd_deposit_amount', array( $this, 'enforce_min_max' ), 20, 5 );
        add_filter( 'apd_save_settings', array( $this, 'save_settings' ), 10, 3 );
    }

    /**
     * Enforce min/max boundaries on deposit amount.
     */
    public function enforce_min_max( $deposit, $product_id, $price, $type, $value ) {
        // The min/max slider range should only govern deposits where the effective
        // deposit type is explicitly 'min_max'.  For 'percentage' or 'fixed' types,
        // the calculated deposit is used as-is — clamping it silently to the slider
        // range produces confusing label/amount mismatches on the product page.
        if ( $type !== 'min_max' ) {
            return $deposit;
        }

        $min = floatval( apd_get_option( 'min_deposit_amount', 0 ) );
        $max = floatval( apd_get_option( 'max_deposit_amount', 0 ) );

        // Product-level overrides
        $product_min = get_post_meta( $product_id, '_apd_min_deposit', true );
        $product_max = get_post_meta( $product_id, '_apd_max_deposit', true );

        if ( $product_min !== '' && $product_min !== false ) $min = floatval( $product_min );
        if ( $product_max !== '' && $product_max !== false ) $max = floatval( $product_max );

        if ( $min > 0 && $deposit < $min ) {
            $deposit = $min;
        }
        if ( $max > 0 && $deposit > $max ) {
            $deposit = $max;
        }

        // Never exceed product price
        $deposit = min( $deposit, $price );

        return $deposit;
    }

    /**
     * Save min/max settings.
     */
    public function save_settings( $settings, $tab, $data ) {
        if ( $tab === 'min-max' ) {
            $settings['min_deposit_amount'] = floatval( $data['min_deposit_amount'] ?? 0 );
            $settings['max_deposit_amount'] = floatval( $data['max_deposit_amount'] ?? 0 );
            $settings['min_deposit_type']   = sanitize_text_field( $data['min_deposit_type'] ?? 'fixed' );
            $settings['max_deposit_type']   = sanitize_text_field( $data['max_deposit_type'] ?? 'fixed' );
        }
        return $settings;
    }
}
