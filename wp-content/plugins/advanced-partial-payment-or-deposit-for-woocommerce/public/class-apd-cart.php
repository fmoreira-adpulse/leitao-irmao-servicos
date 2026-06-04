<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cart deposit integration.
 */
class APD_Cart {

    public function __construct() {
        // Display deposit info in cart
        add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'display_cart_deposit_totals' ) );
        add_action( 'woocommerce_after_cart_item_name', array( $this, 'display_cart_item_deposit' ), 10, 2 );
        // Modify cart item display
        add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'cart_item_subtotal' ), 10, 3 );
        // Adjust totals for block-based cart/checkout flows.
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_store_api_balance_adjustment' ), 20 );
        // Persist cart data
        add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
        // AJAX update payment type
        add_action( 'wp_ajax_apd_update_cart_payment_type', array( $this, 'ajax_update_payment_type' ) );
        add_action( 'wp_ajax_nopriv_apd_update_cart_payment_type', array( $this, 'ajax_update_payment_type' ) );
        // AJAX update payment type for ALL cart items (block checkout support)
        add_action( 'wp_ajax_apd_update_cart_payment_type_all', array( $this, 'ajax_update_payment_type_all' ) );
        add_action( 'wp_ajax_nopriv_apd_update_cart_payment_type_all', array( $this, 'ajax_update_payment_type_all' ) );
    }

    /**
     * Display deposit breakdown in cart totals.
     */
    public function display_cart_deposit_totals() {
        $deposit_engine = APD_Deposit::instance();
        $summary = $deposit_engine->get_cart_payment_summary();

        if ( empty( $summary['has_deposit'] ) ) {
            return;
        }

        $settings      = get_option( 'apd_settings', array() );
        $deposit_label = $settings['deposit_label'] ?? __( 'Deposit', 'advanced-partial-payment' );
        $balance_label = $settings['due_balance_label'] ?? __( 'Due Balance', 'advanced-partial-payment' );
        $deposit_total = $summary['deposit_amount'];
        $balance_due   = $summary['balance_due'];

        include APD_PLUGIN_DIR . 'public/views/cart-deposit-summary.php';
    }

    /**
     * Show deposit info under cart item name.
     */
    public function display_cart_item_deposit( $cart_item, $cart_item_key ) {
        $deposit_engine = APD_Deposit::instance();
        if ( ! $deposit_engine->is_deposit_enabled( $cart_item['product_id'] ) ) {
            return;
        }

        $pay_deposit = isset( $cart_item['apd_pay_deposit'] ) ? $cart_item['apd_pay_deposit'] : 'yes';
        if ( $pay_deposit !== 'yes' ) {
            return;
        }

        $product = wc_get_product( $cart_item['product_id'] );
        if ( ! $product ) return;

        $quantity = max( 1, intval( $cart_item['quantity'] ?? 1 ) );
        $price    = floatval( $cart_item['line_total'] ?? 0 ) / $quantity;
        $type     = $deposit_engine->get_deposit_type( $cart_item['product_id'] );
        $deposit  = ( 'min_max' === $type && isset( $cart_item['apd_custom_deposit'] ) )
            ? $deposit_engine->sanitize_custom_deposit( $cart_item['product_id'], floatval( $cart_item['apd_custom_deposit'] ), $price )
            : $deposit_engine->get_deposit_amount( $cart_item['product_id'], $price );

        $settings     = get_option( 'apd_settings', array() );
        $deposit_label = $settings['deposit_label'] ?? __( 'Deposit', 'advanced-partial-payment' );

        echo '<div class="apd-cart-item-deposit">';
        echo '<small class="apd-cart-deposit-tag">' . esc_html( $deposit_label ) . ': ' . wc_price( $deposit ) . '</small>';
        echo '</div>';
    }

    /**
     * Modify cart item subtotal to show deposit amount.
     */
    public function cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
        $deposit_engine = APD_Deposit::instance();
        if ( ! $deposit_engine->is_deposit_enabled( $cart_item['product_id'] ) ) {
            return $subtotal;
        }

        $pay_deposit = isset( $cart_item['apd_pay_deposit'] ) ? $cart_item['apd_pay_deposit'] : 'yes';
        if ( $pay_deposit !== 'yes' ) {
            return $subtotal;
        }

        $product  = wc_get_product( $cart_item['product_id'] );
        if ( ! $product ) return $subtotal;

        $quantity  = max( 1, intval( $cart_item['quantity'] ?? 1 ) );
        $price     = floatval( $cart_item['line_total'] ?? 0 ) / $quantity;
        $type      = $deposit_engine->get_deposit_type( $cart_item['product_id'] );
        $deposit   = ( 'min_max' === $type && isset( $cart_item['apd_custom_deposit'] ) )
            ? $deposit_engine->sanitize_custom_deposit( $cart_item['product_id'], floatval( $cart_item['apd_custom_deposit'] ), $price )
            : $deposit_engine->get_deposit_amount( $cart_item['product_id'], $price );
        $total_dep = $deposit * $quantity;

        $settings      = get_option( 'apd_settings', array() );
        $deposit_label = $settings['deposit_label'] ?? __( 'Deposit', 'advanced-partial-payment' );

        return $subtotal . '<br><small class="apd-subtotal-deposit">' . esc_html( $deposit_label ) . ': ' . wc_price( $total_dep ) . '</small>';
    }

    /**
     * Restore cart item data from session.
     */
    public function get_cart_item_from_session( $cart_item, $values ) {
        if ( isset( $values['apd_pay_deposit'] ) ) {
            $cart_item['apd_pay_deposit'] = $values['apd_pay_deposit'];
        }
        if ( isset( $values['apd_custom_deposit'] ) ) {
            $cart_item['apd_custom_deposit'] = floatval( $values['apd_custom_deposit'] );
        }
        return $cart_item;
    }

    /**
     * Reduce Store API totals so WooCommerce Cart/Checkout blocks charge the deposit amount.
     *
     * @param WC_Cart $cart Cart object.
     */
    public function apply_store_api_balance_adjustment( $cart ) {
        if ( ! $cart instanceof WC_Cart ) {
            return;
        }

        if ( is_admin() && ! wp_doing_ajax() ) {
            return;
        }

        /**
         * Detect Store API / block checkout context.
         */
        $is_store_api = false;

        // Check for WooCommerce Store API specifically — not just any REST request.
        // A generic REST_REQUEST check would fire for any plugin's REST call, causing
        // the balance fee to be injected during unrelated requests.
        if ( ! empty( $_SERVER['HTTP_X_WC_STORE_API_NONCE'] ) ) {
            $is_store_api = true;
        } elseif ( ! empty( $cart->cart_context ) && 'store-api' === $cart->cart_context ) {
            $is_store_api = true;
        } elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
            if ( false !== strpos( $request_uri, '/wc/store/' ) ) {
                $is_store_api = true;
            }
        }

        if ( ! $is_store_api ) {
            return;
        }

        $deposit_engine = APD_Deposit::instance();
        $summary        = $deposit_engine->get_cart_payment_summary();

        if ( empty( $summary['has_deposit'] ) || empty( $summary['balance_due'] ) ) {
            return;
        }

        $settings      = get_option( 'apd_settings', array() );
        $balance_label = $settings['due_balance_label'] ?? __( 'Due Balance', 'advanced-partial-payment' );
        $fee_label     = sprintf(
            '%1$s (%2$s)',
            $balance_label,
            __( 'Pay Later', 'advanced-partial-payment' )
        );

        $cart->add_fee( $fee_label, -1 * $summary['balance_due'], false );
    }

    /**
     * AJAX: Update payment type in cart.
     */
    public function ajax_update_payment_type() {
        check_ajax_referer( 'apd_public_nonce', 'nonce' );

        $cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
        $payment_type  = sanitize_text_field( wp_unslash( $_POST['payment_type'] ?? 'deposit' ) );

        if ( ! $cart_item_key ) {
            wp_send_json_error( 'Invalid item.' );
        }

        $cart = WC()->cart->get_cart();
        if ( isset( $cart[ $cart_item_key ] ) ) {
            $product_id      = intval( $cart[ $cart_item_key ]['product_id'] ?? 0 );
            $deposit_engine  = APD_Deposit::instance();
            $forced_deposit  = $product_id ? $deposit_engine->is_force_deposit_enabled( $product_id ) : false;

            WC()->cart->cart_contents[ $cart_item_key ]['apd_pay_deposit'] = ( $forced_deposit || $payment_type === 'deposit' ) ? 'yes' : 'no';
            WC()->cart->set_session();
            wp_send_json_success();
        }

        wp_send_json_error( 'Item not found.' );
    }

    /**
     * AJAX: Update payment type for ALL deposit-eligible cart items.
     * Used by block cart/checkout toggle.
     */
    public function ajax_update_payment_type_all() {
        check_ajax_referer( 'apd_public_nonce', 'nonce' );

        $payment_type = sanitize_text_field( wp_unslash( $_POST['payment_type'] ?? 'deposit' ) );
        $cart_changed = false;

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = intval( $cart_item['product_id'] ?? 0 );
            if ( ! $product_id ) {
                continue;
            }

            $deposit_engine = APD_Deposit::instance();
            if ( ! $deposit_engine->is_deposit_enabled( $product_id ) ) {
                continue;
            }

            $forced_deposit = $deposit_engine->is_force_deposit_enabled( $product_id );
            if ( $forced_deposit ) {
                continue; // Can't toggle forced deposits.
            }

            WC()->cart->cart_contents[ $cart_item_key ]['apd_pay_deposit'] = ( $payment_type === 'deposit' ) ? 'yes' : 'no';
            $cart_changed = true;
        }

        if ( $cart_changed ) {
            WC()->cart->set_session();
            WC()->cart->calculate_totals();
        }

        wp_send_json_success( array(
            'payment_type' => $payment_type,
            'cart_changed' => $cart_changed,
        ) );
    }
}
