<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Partial-payment fee support.
 */
class APD_Deposit_Fees {

    public function __construct() {
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'maybe_add_partial_payment_fee' ), 15 );
        add_filter( 'apd_save_settings', array( $this, 'save_settings' ), 10, 3 );
    }

    /**
     * Add an extra charge when the cart is being paid as a deposit.
     *
     * @param WC_Cart $cart Cart object.
     */
    public function maybe_add_partial_payment_fee( $cart ) {
        if ( ! $cart instanceof WC_Cart ) {
            return;
        }

        if ( is_admin() && ! wp_doing_ajax() ) {
            return;
        }

        if ( 'yes' !== apd_get_option( 'enable_partial_payment_fee', 'no' ) ) {
            return;
        }

        $amount = $this->get_fee_amount( $cart );
        if ( $amount <= 0 ) {
            return;
        }

        $cart->add_fee(
            self::get_fee_label(),
            $amount,
            'yes' === apd_get_option( 'partial_payment_fee_taxable', 'no' )
        );
    }

    /**
     * Calculate fee amount from the current deposit subtotal.
     *
     * @param WC_Cart $cart Cart object.
     * @return float
     */
    private function get_fee_amount( $cart ) {
        $deposit_engine = APD_Deposit::instance();
        $deposit_base   = 0;
        $has_deposit    = false;

        foreach ( $cart->get_cart() as $cart_item ) {
            $product_id = intval( $cart_item['product_id'] ?? 0 );
            if ( ! $product_id || ! $deposit_engine->is_deposit_enabled( $product_id ) ) {
                continue;
            }

            $pay_deposit = $cart_item['apd_pay_deposit'] ?? 'yes';
            if ( 'yes' !== $pay_deposit ) {
                continue;
            }

            $line_total = floatval( $cart_item['line_total'] ?? 0 );
            $quantity   = max( 1, intval( $cart_item['quantity'] ?? 1 ) );
            $unit_price = $line_total / $quantity;

            $deposit_base += $deposit_engine->get_deposit_amount( $product_id, $unit_price ) * $quantity;
            $has_deposit = true;
        }

        if ( ! $has_deposit || $deposit_base <= 0 ) {
            return 0;
        }

        $fee_type  = apd_get_option( 'partial_payment_fee_type', 'fixed' );
        $fee_value = floatval( apd_get_option( 'partial_payment_fee_value', 0 ) );

        if ( $fee_value <= 0 ) {
            return 0;
        }

        $amount = 'percentage' === $fee_type ? ( $deposit_base * $fee_value ) / 100 : $fee_value;

        return round( max( 0, $amount ), wc_get_price_decimals() );
    }

    /**
     * Get the frontend/admin label for the fee.
     *
     * @return string
     */
    public static function get_fee_label() {
        return apd_get_option( 'partial_payment_fee_label', __( 'Partial Payment Fee', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
    }

    /**
     * Check whether a fee line is the APD partial-payment fee.
     *
     * @param object $fee Fee object.
     * @return bool
     */
    public static function is_partial_payment_fee( $fee ) {
        return is_object( $fee ) && isset( $fee->name ) && $fee->name === self::get_fee_label();
    }

    /**
     * Save settings.
     *
     * @param array  $settings Settings array.
     * @param string $tab      Current tab.
     * @param array  $data     Request data.
     * @return array
     */
    public function save_settings( $settings, $tab, $data ) {
        if ( 'deposit-fees' !== $tab && 'min-max' !== $tab ) {
            return $settings;
        }

        $settings['enable_partial_payment_fee'] = isset( $data['enable_partial_payment_fee'] ) ? 'yes' : 'no';
        $settings['partial_payment_fee_label']  = sanitize_text_field( $data['partial_payment_fee_label'] ?? self::get_fee_label() );
        $settings['partial_payment_fee_type']   = sanitize_text_field( $data['partial_payment_fee_type'] ?? 'fixed' );
        $settings['partial_payment_fee_value']  = floatval( $data['partial_payment_fee_value'] ?? 0 );
        $settings['partial_payment_fee_taxable'] = isset( $data['partial_payment_fee_taxable'] ) ? 'yes' : 'no';

        return $settings;
    }
}
