<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * My Account deposits tab.
 */
class APD_MyAccount {

    public function __construct() {
        // Add menu item
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
        // Render content
        add_action( 'woocommerce_account_deposits_endpoint', array( $this, 'render_content' ) );
    }

    /**
     * Add "Deposits" tab to My Account menu.
     */
    public function add_menu_item( $items ) {
        $new_items = array();
        foreach ( $items as $key => $label ) {
            if ( 'orders' === $key ) {
                $new_items[ $key ] = $label;
                $new_items['deposits'] = __( 'Deposits', 'advanced-partial-payment' );
            } else {
                $new_items[ $key ] = $label;
            }
        }
        return $new_items;
    }

    /**
     * Render deposits content.
     */
    public function render_content() {
        $customer_id = get_current_user_id();
        if ( ! $customer_id ) return;

        $statuses = array( 'partially-paid', 'completed', 'processing', 'on-hold', 'pending' );

        // Primary query: by customer ID (covers orders linked to this account).
        $orders = wc_get_orders( array(
            'customer_id' => $customer_id,
            'limit'       => 20,
            'status'      => $statuses,
            'meta_query'  => array(
                array(
                    'key'     => '_apd_is_deposit',
                    'value'   => 'yes',
                    'compare' => '=',
                ),
            ),
        ) );

        // Fallback: admin-created manual orders are linked by billing email, not
        // customer_id (which may be 0 or the admin's ID). Query by email too.
        $current_user = wp_get_current_user();
        if ( $current_user->user_email ) {
            $email_orders = wc_get_orders( array(
                'billing_email' => $current_user->user_email,
                'limit'         => 20,
                'status'        => $statuses,
                'meta_query'    => array(
                    array(
                        'key'     => '_apd_is_deposit',
                        'value'   => 'yes',
                        'compare' => '=',
                    ),
                ),
            ) );
            // Merge, deduplicate by order ID.
            $seen = array();
            foreach ( $orders as $o ) { $seen[ $o->get_id() ] = true; }
            foreach ( $email_orders as $o ) {
                if ( empty( $seen[ $o->get_id() ] ) ) {
                    $orders[] = $o;
                }
            }
        }

        $settings       = get_option( 'apd_settings', array() );
        $pay_btn_label  = $settings['pay_button_label'] ?? __( 'Pay Remaining Balance', 'advanced-partial-payment' );

        include APD_PLUGIN_DIR . 'public/views/myaccount-deposits.php';
    }
}
