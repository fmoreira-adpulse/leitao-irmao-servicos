<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reports and analytics for deposits.
 */
class APD_Reports {

    /**
     * Build a normalized summary from deposit orders.
     *
     * @param WC_Order[] $orders Deposit orders.
     * @return array<string,int|float>
     */
    private static function build_summary_from_orders( $orders ) {
        $summary = array(
            'total_orders'          => 0,
            'original_deposit'      => 0,
            'total_collected'       => 0,
            'total_pending'         => 0,
            'total_order_value'     => 0,
            'balance_paid'          => 0,
            'partially_paid_count'  => 0,
            'fully_settled_count'   => 0,
            'collection_rate'       => 0,
        );

        foreach ( $orders as $order ) {
            if ( ! $order || ! class_exists( 'APD_Order' ) || ! APD_Order::is_deposit_order( $order ) ) {
                continue;
            }

            $details = APD_Order::get_deposit_details( $order );
            if ( ! is_array( $details ) ) {
                continue;
            }

            $deposit_amount = floatval( $details['deposit_amount'] ?? 0 );
            $amount_paid    = floatval( $details['amount_paid'] ?? 0 );
            $balance_due    = floatval( $details['balance_due'] ?? 0 );
            $total_amount   = floatval( $details['total_amount'] ?? 0 );

            $summary['total_orders']++;
            $summary['original_deposit'] += $deposit_amount;
            $summary['total_collected']  += $amount_paid;
            $summary['total_pending']    += $balance_due;
            $summary['total_order_value'] += $total_amount;

            if ( $balance_due > 0 ) {
                $summary['partially_paid_count']++;
            } else {
                $summary['fully_settled_count']++;
            }
        }

        $summary['balance_paid'] = max( 0, $summary['total_collected'] - $summary['original_deposit'] );

        $summary['collection_rate'] = $summary['total_order_value'] > 0
            ? round( ( $summary['total_collected'] / $summary['total_order_value'] ) * 100, 1 )
            : 0;

        return $summary;
    }

    public function __construct() {
        add_action( 'wp_ajax_apd_get_report_data', array( $this, 'get_report_data' ) );
    }

    /**
     * AJAX: Get report data.
     */
    public function get_report_data() {
        check_ajax_referer( 'apd_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        $period = sanitize_text_field( wp_unslash( $_POST['period'] ?? '30' ) );
        if ( ! is_numeric( $period ) || intval( $period ) <= 0 ) {
            $period = 30;
        }
        $date_from = wp_date( 'Y-m-d', strtotime( "-{$period} days" ) );

        // Get deposit orders
        $orders = wc_get_orders( array(
            'date_created' => '>=' . $date_from,
            'limit'        => -1,
            'meta_query'   => array(
                array(
                    'key'   => '_apd_is_deposit',
                    'value' => 'yes',
                ),
            ),
        ) );

        $summary = self::build_summary_from_orders( $orders );

        wp_send_json_success( array(
            'total_deposits'       => $summary['original_deposit'],
            'total_balance_due'    => $summary['total_pending'],
            'total_collected'      => $summary['total_collected'],
            'deposit_count'        => $summary['total_orders'],
            'fully_paid_count'     => $summary['fully_settled_count'],
            'pending_count'        => $summary['partially_paid_count'],
            'collection_rate'      => $summary['collection_rate'],
            'balance_paid'         => $summary['balance_paid'],
            'total_order_value'    => $summary['total_order_value'],
            'original_deposit'     => $summary['original_deposit'],
            'partially_paid_count' => $summary['partially_paid_count'],
            'fully_settled_count'  => $summary['fully_settled_count'],
        ) );
    }

    /**
     * Get summary data (non-AJAX).
     */
    public static function get_summary() {
        $orders = wc_get_orders( array(
            'limit'      => -1,
            'meta_query' => array(
                array(
                    'key'   => '_apd_is_deposit',
                    'value' => 'yes',
                ),
            ),
        ) );

        return self::build_summary_from_orders( $orders );
    }
}
