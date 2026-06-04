<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pay Balance endpoint for customers.
 */
class APD_Pay_Balance {

    public function __construct() {
        // Endpoint and query var are registered centrally in apd_register_rewrite_endpoints().
        add_action( 'template_redirect', array( $this, 'handle_pay_balance' ) );
        // AJAX pay balance
        add_action( 'wp_ajax_apd_create_balance_order', array( $this, 'create_balance_order' ) );
    }

    /**
     * Handle the pay balance request.
     */
    public function handle_pay_balance() {
        if ( ! isset( $_GET['apd_pay_balance'] ) ) {
            return;
        }

        $order_id = intval( $_GET['apd_pay_balance'] );
        $nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'apd_pay_balance_' . $order_id ) ) {
            wc_add_notice( __( 'Invalid request.', 'advanced-partial-payment' ), 'error' );
            wp_redirect( wc_get_account_endpoint_url( 'deposits' ) );
            exit;
        }

        if ( ! is_user_logged_in() ) {
            wp_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order || ! APD_Order::is_deposit_order( $order ) ) {
            wc_add_notice( __( 'Invalid order.', 'advanced-partial-payment' ), 'error' );
            wp_redirect( wc_get_account_endpoint_url( 'deposits' ) );
            exit;
        }

        // Check ownership by user ID or matching billing email.
        $current_user    = wp_get_current_user();
        $order_owner_id  = (int) $order->get_customer_id();
        $order_email     = $order->get_billing_email();
        $is_owner        = ( $order_owner_id && $order_owner_id === get_current_user_id() )
            || ( $order_email && strtolower( $order_email ) === strtolower( $current_user->user_email ) );
        if ( ! $is_owner ) {
            wc_add_notice( __( 'You do not have permission to pay this balance.', 'advanced-partial-payment' ), 'error' );
            wp_redirect( wc_get_account_endpoint_url( 'deposits' ) );
            exit;
        }

        $details = APD_Order::get_deposit_details( $order );
        if ( ! $details || $details['balance_due'] <= 0 ) {
            wc_add_notice( __( 'This order has no outstanding balance.', 'advanced-partial-payment' ), 'notice' );
            wp_redirect( wc_get_account_endpoint_url( 'deposits' ) );
            exit;
        }

        // Determine how much to charge: a specific installment amount (apd_amount) if
        // provided and valid, otherwise the full outstanding balance. Never more than
        // the balance due.
        $balance_due = floatval( $details['balance_due'] );
        $amount      = isset( $_GET['apd_amount'] ) ? floatval( wp_unslash( $_GET['apd_amount'] ) ) : $balance_due;
        if ( $amount <= 0 || $amount > $balance_due ) {
            $amount = $balance_due;
        }
        $amount = round( $amount, wc_get_price_decimals() );

        // Update the order total to the chosen amount and redirect to payment.
        $order->update_meta_data( '_apd_balance_payment_pending', $amount );
        $order->set_total( $amount );
        if ( $order->get_status() !== 'partially-paid' ) {
            $order->set_status( 'partially-paid', __( 'Customer started a remaining balance payment.', 'advanced-partial-payment' ) );
        }
        $order->save();

        // Redirect to pay page
        wp_redirect( $order->get_checkout_payment_url() );
        exit;
    }

    /**
     * Generate pay balance URL.
     *
     * @param int   $order_id Order ID.
     * @param float $amount   Optional specific amount (e.g. a single installment). 0 = full balance.
     * @return string
     */
    public static function get_pay_balance_url( $order_id, $amount = 0 ) {
        $args = array( 'apd_pay_balance' => $order_id );
        if ( $amount > 0 ) {
            $args['apd_amount'] = $amount;
        }
        return wp_nonce_url(
            add_query_arg( $args, wc_get_page_permalink( 'myaccount' ) ),
            'apd_pay_balance_' . $order_id
        );
    }

    /**
     * AJAX: Create a balance payment order.
     */
    public function create_balance_order() {
        check_ajax_referer( 'apd_public_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Please log in to pay the balance.', 'advanced-partial-payment' ) );
        }

        $order_id = intval( $_POST['order_id'] ?? 0 );
        if ( ! $order_id ) {
            wp_send_json_error( __( 'Invalid order.', 'advanced-partial-payment' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( __( 'Permission denied.', 'advanced-partial-payment' ) );
            return;
        }

        // Check ownership by user ID or matching billing email (admin-created orders
        // may have customer_id = 0 but a billing email linked to the logged-in user).
        $current_user   = wp_get_current_user();
        $order_owner_id = (int) $order->get_customer_id();
        $order_email    = $order->get_billing_email();
        $is_owner       = ( $order_owner_id && $order_owner_id === get_current_user_id() )
            || ( $order_email && strtolower( $order_email ) === strtolower( $current_user->user_email ) );
        if ( ! $is_owner ) {
            wp_send_json_error( __( 'Permission denied.', 'advanced-partial-payment' ) );
            return;
        }

        if ( ! APD_Order::is_deposit_order( $order ) ) {
            wp_send_json_error( __( 'This order is not a deposit order.', 'advanced-partial-payment' ) );
            return;
        }

        $details = APD_Order::get_deposit_details( $order );
        if ( ! $details || $details['balance_due'] <= 0 ) {
            wp_send_json_error( __( 'This order has no outstanding balance.', 'advanced-partial-payment' ) );
            return;
        }

        $pay_url = self::get_pay_balance_url( $order_id );
        wp_send_json_success( array( 'pay_url' => $pay_url ) );
    }
}
