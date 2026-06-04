<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * eCab Taxi Booking Manager â€“ Pro admin integration for APD Deposit Pro.
 *
 * Adds deposit information to:
 *  1. MPTBM Order List "Amount" column (JS enhancement via AJAX).
 *  2. PDF ticket (injects deposit summary into the default PDF template).
 *  3. CSV export (intercepts mptbm_order_list_result export and appends columns).
 *
 * Requires eCab Pro (MPTBM_Dependencies_Pro) for admin reporting features.
 */
class APD_Ecab_Order_List_Integration {

    public function __construct() {
        // Admin styles (used by order list)
        add_action( 'admin_head',   array( $this, 'render_admin_styles' ) );

        // Order List amount-column + order-details enhancement (JS)
        add_action( 'admin_footer', array( $this, 'render_order_list_assets' ) );

        // AJAX endpoint supplying per-order deposit summaries to JS (amount column)
        add_action( 'wp_ajax_apd_ecab_order_summaries', array( $this, 'ajax_ecab_order_summaries' ) );

        // AJAX endpoint supplying per-attendee deposit summaries to JS (order details panel)
        add_action( 'wp_ajax_apd_ecab_attendee_summaries', array( $this, 'ajax_ecab_attendee_summaries' ) );

        // PDF ticket â€“ append deposit info after the order information list
        add_action( 'mptbm_after_order_info', array( $this, 'render_ticket_pdf_deposit_info' ), 20, 1 );

        // CSV export â€“ intercept the AJAX handler at priority 1 (before eCab)
        add_action( 'wp_ajax_mptbm_export_csv',        array( $this, 'maybe_handle_ecab_csv' ), 1 );
        add_action( 'wp_ajax_nopriv_mptbm_export_csv', array( $this, 'maybe_handle_ecab_csv' ), 1 );
    }

    // =========================================================================
    // Guard helpers
    // =========================================================================

    private function is_ecab_pro_available() {
        return APD_Ecab_Integration::is_ecab_active() && class_exists( 'MPTBM_Dependencies_Pro' );
    }

    private function is_order_list_page() {
        if ( ! is_admin() ) {
            return false;
        }
        $page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        return 'mptbm_order_list' === $page;
    }

    private function format_money( $amount ) {
        return wp_strip_all_tags( html_entity_decode( wc_price( (float) $amount ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
    }

    // =========================================================================
    // Admin styles
    // =========================================================================

    public function render_admin_styles() {
        if ( ! $this->is_order_list_page() ) {
            return;
        }
        ?>
        <style>
            .apd-ecab-summary { min-width: 200px; line-height: 1.45; }
            .apd-ecab-summary strong { display:block; margin-bottom:4px; }
            .apd-ecab-summary small { display:block; color:#6b7280; }
            .apd-ecab-summary-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:4px 12px; margin-top:6px; font-size:12px; }
            .apd-ecab-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; margin-bottom:4px; }
            .apd-ecab-badge-deposit { background:#ede9fe; color:#5b21b6; }
            .apd-ecab-badge-full    { background:#e5f7eb; color:#166534; }
            .apd-ecab-order-paid { display:flex; flex-direction:column; gap:2px; line-height:1.35; }
            .apd-ecab-order-paid strong { font-size:14px; }
            .apd-ecab-order-paid small  { display:block; color:#6b7280; }
            /* Order Details deposit block */
            .apd-ecab-deposit-block { margin-top:12px; padding-top:12px; border-top:1px dashed #d1d5db; }
            .apd-ecab-deposit-block .apd-deposit-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; margin-bottom:8px; }
            .apd-ecab-deposit-block .apd-deposit-badge-deposit { background:#ede9fe; color:#5b21b6; }
            .apd-ecab-deposit-block .apd-deposit-badge-full    { background:#e5f7eb; color:#166534; }
            .apd-ecab-deposit-block li { display:flex; justify-content:space-between; align-items:center; padding:3px 0; font-size:13px; }
            .apd-ecab-deposit-block li strong { color:#374151; }
            .apd-ecab-deposit-block li span   { color:#111827; font-weight:600; }
        </style>
        <?php
    }

    // =========================================================================
    // Order list â€“ Amount column JS enhancement
    // =========================================================================

    public function render_order_list_assets() {
        if ( ! $this->is_order_list_page() ) {
            return;
        }

        $config = array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'apd_ecab_admin_nonce' ),
            'labels'  => array(
                'paid'    => __( 'Paid', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'deposit' => __( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'total'   => __( 'Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'balance' => __( 'Balance', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'plan'    => __( 'Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'full'    => __( 'Full payment', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            ),
        );
        ?>
        <script>
        window.apdEcabOrderList = <?php echo wp_json_encode( $config ); ?>;
        (function(){
            "use strict";

            var config = window.apdEcabOrderList || null;
            if (!config) { return; }

            var root = document.getElementById("mptbm_order_list_result");
            if (!root) { return; }

            var requestToken = 0;
            var detailsRequestToken = 0;

            var getColumnIndex = function (table, label) {
                var headers = table ? table.querySelectorAll("thead th") : [];
                for (var i = 0; i < headers.length; i++) {
                    if ((headers[i].textContent || "").trim().toLowerCase() === label.toLowerCase()) {
                        return i;
                    }
                }
                return -1;
            };

            var renderSummary = function (cell, summary) {
                if (!cell || !summary || !summary.exists) { return; }

                if (!summary.is_deposit) {
                    cell.innerHTML = '<div class="apd-ecab-order-paid"><strong>' + summary.amount_paid_html + '</strong><small>' + config.labels.full + '</small></div>';
                    return;
                }

                var html = '<div class="apd-ecab-order-paid">' +
                    '<strong>' + summary.amount_paid_html + '</strong>' +
                    '<small>' + config.labels.deposit + ': ' + summary.deposit_amount_html + '</small>' +
                    '<small>' + config.labels.total   + ': ' + summary.total_amount_html   + '</small>' +
                    '<small>' + config.labels.balance + ': ' + summary.balance_due_html   + '</small>';

                if (summary.payment_plan) {
                    html += '<small>' + config.labels.plan + ': ' + summary.payment_plan + '</small>';
                }

                html += '</div>';
                cell.innerHTML = html;
            };

            /* ---------------------------------------------------------------
               Enhance Amount column in table rows
            --------------------------------------------------------------- */
            var enhanceTable = function () {
                var table = root.querySelector("table.order-table");
                if (!table) { return; }

                var amountIndex  = getColumnIndex(table, "Amount");
                if (amountIndex < 0) { return; }

                var rows = table.querySelectorAll("tbody tr:not([data-collapse])");
                var orderIds = [];
                var cellsByOrder = {};

                Array.prototype.forEach.call(rows, function (row) {
                    var pdfBtn = row.querySelector('[data-href*="order_id"]');
                    if (!pdfBtn) { return; }

                    var href    = pdfBtn.getAttribute("data-href") || "";
                    var match   = href.match(/[?&]order_id=(\d+)/);
                    var orderId = match ? parseInt(match[1], 10) : 0;
                    if (!orderId) { return; }

                    var cells = row.children;
                    if (!cells || !cells[amountIndex]) { return; }

                    orderIds.push(orderId);
                    cellsByOrder[orderId] = cells[amountIndex];
                });

                if (!orderIds.length) { return; }

                requestToken++;
                var activeToken = requestToken;

                var body = new window.FormData();
                body.append("action", "apd_ecab_order_summaries");
                body.append("nonce", config.nonce);
                orderIds.forEach(function (orderId) {
                    body.append("order_ids[]", orderId);
                });

                window.fetch(config.ajaxUrl, {
                    method: "POST",
                    credentials: "same-origin",
                    body: body
                }).then(function (response) {
                    return response.json();
                }).then(function (response) {
                    if (activeToken !== requestToken || !response || !response.success || !response.data) { return; }
                    Object.keys(response.data).forEach(function (orderId) {
                        renderSummary(cellsByOrder[orderId], response.data[orderId]);
                    });
                }).catch(function () {});
            };

            /* ---------------------------------------------------------------
               Enhance Order Details panels (Transportation Information)
               The panel's container has data-order-summary-attendee-id="N"
               Inside it, MPTBM_Layout_Pro::order_info() renders a <ul class="mp_list">
               We append deposit <li> items to that <ul>.
            --------------------------------------------------------------- */
            var renderDetailDeposit = function (container, summary) {
                if (!container || !summary || !summary.exists || !summary.is_deposit) { return; }
                /* Don't inject twice */
                if (container.querySelector(".apd-ecab-deposit-block")) { return; }

                var ul = container.querySelector("ul.mp_list");
                if (!ul) { return; }

                var block = document.createElement("div");
                block.className = "apd-ecab-deposit-block";

                var html = '<span class="apd-deposit-badge apd-deposit-badge-deposit">' + config.labels.deposit + '</span>' +
                    '<ul class="mp_list" style="list-style:none;padding:0;margin:0;">' +
                    '<li><strong class="min_150">' + config.labels.paid + ' :</strong><span>' + summary.amount_paid_html + '</span></li>' +
                    '<li><strong class="min_150">' + config.labels.deposit + ' :</strong><span>' + summary.deposit_amount_html + '</span></li>' +
                    '<li><strong class="min_150">' + config.labels.total + ' :</strong><span>' + summary.total_amount_html + '</span></li>' +
                    '<li><strong class="min_150">' + config.labels.balance + ' :</strong><span>' + summary.balance_due_html + '</span></li>';

                if (summary.payment_plan) {
                    html += '<li><strong class="min_150">' + config.labels.plan + ' :</strong><span>' + summary.payment_plan + '</span></li>';
                }
                html += '</ul>';

                block.innerHTML = html;
                ul.parentNode.insertBefore(block, ul.nextSibling);
            };

            var enhanceOrderDetails = function () {
                var panels = root.querySelectorAll("[data-order-summary-attendee-id]");
                if (!panels.length) { return; }

                var attendeeIds = [];
                var panelsByAttendee = {};

                Array.prototype.forEach.call(panels, function (panel) {
                    /* Skip already-enhanced panels */
                    if (panel.querySelector(".apd-ecab-deposit-block")) { return; }
                    var attendeeId = parseInt(panel.getAttribute("data-order-summary-attendee-id"), 10);
                    if (!attendeeId) { return; }
                    attendeeIds.push(attendeeId);
                    panelsByAttendee[attendeeId] = panel;
                });

                if (!attendeeIds.length) { return; }

                detailsRequestToken++;
                var activeToken = detailsRequestToken;

                var body = new window.FormData();
                body.append("action", "apd_ecab_attendee_summaries");
                body.append("nonce", config.nonce);
                attendeeIds.forEach(function (id) {
                    body.append("attendee_ids[]", id);
                });

                window.fetch(config.ajaxUrl, {
                    method: "POST",
                    credentials: "same-origin",
                    body: body
                }).then(function (response) {
                    return response.json();
                }).then(function (response) {
                    if (activeToken !== detailsRequestToken || !response || !response.success || !response.data) { return; }
                    Object.keys(response.data).forEach(function (attendeeId) {
                        renderDetailDeposit(panelsByAttendee[attendeeId], response.data[attendeeId]);
                    });
                }).catch(function () {});
            };

            /* ---------------------------------------------------------------
               Init + MutationObserver
            --------------------------------------------------------------- */
            var debounceTimer = null;
            var queueEnhance = function () {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(function () {
                    enhanceTable();
                    enhanceOrderDetails();
                }, 150);
            };

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", queueEnhance);
            } else {
                queueEnhance();
            }

            /* Re-enhance whenever MPTBM reloads the order list via AJAX */
            if (typeof window.MutationObserver === "function") {
                var observer = new window.MutationObserver(queueEnhance);
                observer.observe(root, { childList: true, subtree: true });
            }
        })();
        </script>
        <?php
    }

    // =========================================================================
    // AJAX â€“ supply per-order deposit summaries to JS
    // =========================================================================

    public function ajax_ecab_order_summaries() {
        check_ajax_referer( 'apd_ecab_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to access this data.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), 403 );
        }

        $order_ids = isset( $_POST['order_ids'] ) ? (array) wp_unslash( $_POST['order_ids'] ) : array();
        $order_ids = array_filter( array_map( 'absint', $order_ids ) );
        $payload   = array();

        foreach ( $order_ids as $order_id ) {
            $payload[ $order_id ] = $this->get_order_payment_context( $order_id );
        }

        wp_send_json_success( $payload );
    }

    // =========================================================================
    // AJAX â€“ supply per-attendee deposit summaries to JS (order details panel)
    // =========================================================================

    public function ajax_ecab_attendee_summaries() {
        check_ajax_referer( 'apd_ecab_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to access this data.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), 403 );
        }

        $attendee_ids = isset( $_POST['attendee_ids'] ) ? (array) wp_unslash( $_POST['attendee_ids'] ) : array();
        $attendee_ids = array_filter( array_map( 'absint', $attendee_ids ) );
        $payload      = array();

        foreach ( $attendee_ids as $attendee_id ) {
            $order_id = absint( get_post_meta( $attendee_id, 'mptbm_order_id', true ) );
            $payload[ $attendee_id ] = $this->get_order_payment_context( $order_id );
        }

        wp_send_json_success( $payload );
    }

    // =========================================================================
    // PDF ticket â€“ deposit info injected via mptbm_after_order_info action
    // =========================================================================

    /**
     * Hook: mptbm_after_order_info ( $attendee_id )
     *
     * Called inside MPTBM_Layout_Pro::service_info() which renders the booking
     * info <ul> on PDF tickets and the order-list detail panel.
     */
    public function render_ticket_pdf_deposit_info( $attendee_id ) {
        $attendee_id = absint( $attendee_id );
        if ( $attendee_id <= 0 ) {
            return;
        }

        $order_id = absint( get_post_meta( $attendee_id, 'mptbm_order_id', true ) );
        $summary  = $this->get_order_payment_context( $order_id );

        if ( empty( $summary['is_deposit'] ) ) {
            return;
        }
        ?>
        <li>
            <strong><?php esc_html_e( 'Payment Mode : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php esc_html_e( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Paid Amount : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo wp_kses_post( $summary['amount_paid_html'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Deposit Amount : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo wp_kses_post( $summary['deposit_amount_html'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Total Amount : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo wp_kses_post( $summary['total_amount_html'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Due Balance : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo wp_kses_post( $summary['balance_due_html'] ); ?>
        </li>
        <?php if ( ! empty( $summary['payment_plan'] ) ) : ?>
        <li>
            <strong><?php esc_html_e( 'Payment Plan : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo esc_html( $summary['payment_plan'] ); ?>
        </li>
        <?php endif;
    }

    // =========================================================================
    // CSV export â€“ intercept and append deposit columns
    // =========================================================================

    /**
     * Intercepts the mptbm_export_csv AJAX action (priority 1) to append deposit
     * columns to the standard MPTBM CSV output.
     *
     * MPTBM Pro does not expose a public CSV class; instead it directly outputs
     * headers + rows in this action. We let it run first (remove our hook
     * temporarily), capture its output, parse it, add deposit columns, then send.
     *
     * Because output buffering with PHP's fputcsv is complex when the upstream
     * action uses exit, the safest approach is to replicate MPTBM's own query
     * and output a new CSV ourselves (the same approach used by the Tour CSV
     * integration against TTBM_Pro_CSV).
     */
    public function maybe_handle_ecab_csv() {
        if ( ! $this->is_ecab_pro_available() ) {
            return;
        }

        if ( ! isset( $_REQUEST['action'] ) || 'mptbm_export_csv' !== $_REQUEST['action'] ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
            return;
        }

        // Read the same filter params that MPTBM order list uses.
        $post_id      = isset( $_REQUEST['post_id'] )    ? absint( wp_unslash( $_REQUEST['post_id'] ) )                    : 0;
        $date         = isset( $_REQUEST['date'] )       ? sanitize_text_field( wp_unslash( $_REQUEST['date'] ) )          : '';
        $filter_key   = isset( $_REQUEST['filter_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_key'] ) )    : '';
        $filter_value = isset( $_REQUEST['filter_value'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_value'] ) ) : '';

        // Query bookings using MPTBM_Function_Pro::attendee_query
        if ( ! class_exists( 'MPTBM_Function_Pro' ) ) {
            return;
        }

        $query    = MPTBM_Function_Pro::attendee_query( $post_id, $date, $filter_key, $filter_value, -1, 1 );
        $bookings = ( $query && ! empty( $query->posts ) ) ? $query->posts : array();

        // Build header row matching MPTBM's own visible columns + APD extras.
        $header_row = array(
            __( 'SI', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Reference', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Booking Date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Customer', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Transport', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Journey Date/Time', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Return Date/Time', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Pickup', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Drop-off', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            // APD deposit columns
            __( 'Payment Mode', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Order Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Deposit Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Amount Paid', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Balance Due', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            __( 'Payment Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
        );

        $filename = 'Taxi_Booking_Export_' . sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ?? 'site' ) ) . '_' . time() . '.csv';
        $handle   = fopen( 'php://output', 'w' );

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        fprintf( $handle, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ); // UTF-8 BOM
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-type: text/csv' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Expires: 0' );
        header( 'Pragma: public' );

        if ( ! empty( $bookings ) ) {
            fputcsv( $handle, $header_row );

            $count = 1;
            foreach ( $bookings as $booking_post ) {
                $attendee_id = absint( $booking_post->ID );
                $order_id    = absint( get_post_meta( $attendee_id, 'mptbm_order_id', true ) );
                $wc_order    = $order_id ? wc_get_order( $order_id ) : false;
                $transport_id = absint( get_post_meta( $attendee_id, 'mptbm_id', true ) );

                $pin         = get_post_meta( $attendee_id, 'mptbm_pin', true );
                $date_val    = get_post_meta( $attendee_id, 'mptbm_date', true );
                $start_place = get_post_meta( $attendee_id, 'mptbm_start_place', true );
                $end_place   = get_post_meta( $attendee_id, 'mptbm_end_place', true );
                $status      = get_post_meta( $attendee_id, 'mptbm_order_status', true );
                $customer    = $wc_order ? $wc_order->get_billing_first_name() . ' ' . $wc_order->get_billing_last_name() : '';
                $amount      = $wc_order ? $this->format_money( (float) $wc_order->get_total() ) : $this->format_money( (float) get_post_meta( $attendee_id, 'mptbm_tp', true ) );
                $transport   = $transport_id ? get_the_title( $transport_id ) : '';

                // Return date from WC order item meta
                $return_date_fmt = 'N/A';
                if ( $wc_order ) {
                    foreach ( $wc_order->get_items() as $item_id => $item ) {
                        $ret_date = wc_get_order_item_meta( $item_id, '_mptbm_return_date', true );
                        $ret_time = wc_get_order_item_meta( $item_id, '_mptbm_return_time', true );
                        if ( ! empty( $ret_date ) ) {
                            $return_date_fmt = date_i18n( 'd M, Y', strtotime( $ret_date ) );
                            if ( ! empty( $ret_time ) ) {
                                $return_date_fmt .= ' ' . $ret_time;
                            }
                            break;
                        }
                    }
                }

                // Format journey date
                $journey_fmt = $date_val ? date_i18n( 'd M, Y H:i', strtotime( $date_val ) ) : '';

                // APD deposit context
                $context = $this->get_order_payment_context( $order_id );

                $data_row = array(
                    $count,
                    '#' . $pin,
                    date_i18n( 'd M, Y', strtotime( $booking_post->post_date ) ),
                    trim( $customer ),
                    $transport,
                    $journey_fmt,
                    $return_date_fmt,
                    $start_place,
                    $end_place,
                    $amount,
                    ucfirst( $status ),
                    // APD columns
                    $context['payment_mode'],
                    $this->format_money( $context['total_amount'] ),
                    $this->format_money( $context['deposit_amount'] ),
                    $this->format_money( $context['amount_paid'] ),
                    $this->format_money( $context['balance_due'] ),
                    $context['payment_plan'],
                );

                fputcsv( $handle, $data_row );
                $count++;
            }
        } else {
            fputcsv( $handle, array( esc_html__( 'No Data Found!', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) ) );
        }

        fclose( $handle );
        exit;
    }

    // =========================================================================
    // Context helpers
    // =========================================================================

    /**
     * Returns a rich deposit summary array for a given WC order.
     *
     * @param  int   $order_id
     * @return array
     */
    private function get_order_payment_context( $order_id ) {
        $order_id = absint( $order_id );
        $order    = $order_id > 0 ? wc_get_order( $order_id ) : false;

        if ( ! $order ) {
            return array( 'exists' => false );
        }

        $summary = array(
            'exists'              => true,
            'is_deposit'          => false,
            'payment_mode'        => __( 'Full Payment', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'amount_paid'         => (float) $order->get_total(),
            'deposit_amount'      => 0.0,
            'total_amount'        => (float) $order->get_total(),
            'balance_due'         => 0.0,
            'payment_plan'        => sanitize_text_field( (string) $order->get_meta( '_apd_payment_plan_name' ) ),
            'amount_paid_html'    => wc_price( $order->get_total() ),
            'deposit_amount_html' => wc_price( 0 ),
            'total_amount_html'   => wc_price( $order->get_total() ),
            'balance_due_html'    => wc_price( 0 ),
        );

        if ( class_exists( 'APD_Order' ) && APD_Order::is_deposit_order( $order ) ) {
            $details = APD_Order::get_deposit_details( $order );

            if ( is_array( $details ) ) {
                $summary['is_deposit']          = true;
                $summary['payment_mode']        = __( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
                $summary['amount_paid']         = (float) ( $details['amount_paid']    ?? 0 );
                $summary['deposit_amount']      = (float) ( $details['deposit_amount'] ?? 0 );
                $summary['total_amount']        = (float) ( $details['total_amount']   ?? 0 );
                $summary['balance_due']         = (float) ( $details['balance_due']    ?? 0 );
                $summary['amount_paid_html']    = wc_price( $summary['amount_paid'] );
                $summary['deposit_amount_html'] = wc_price( $summary['deposit_amount'] );
                $summary['total_amount_html']   = wc_price( $summary['total_amount'] );
                $summary['balance_due_html']    = wc_price( $summary['balance_due'] );
            }
        }

        return $summary;
    }
}
