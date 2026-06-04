<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pro admin â€” registers tab views and enqueues pro assets.
 */
class APD_Pro_Admin {

    public function __construct() {
        // Override tab file paths so pro tabs show real content
        add_filter( 'apd_tab_file_payment-plans', array( $this, 'tab_payment_plans' ) );
        add_filter( 'apd_tab_file_min-max', array( $this, 'tab_min_max' ) );
        add_filter( 'apd_tab_file_gateway-rules', array( $this, 'tab_gateway_rules' ) );
        add_filter( 'apd_tab_file_reminders', array( $this, 'tab_reminders' ) );
        add_filter( 'apd_tab_file_reports', array( $this, 'tab_reports' ) );
        add_filter( 'apd_tab_file_conditional-rules', array( $this, 'tab_conditional_rules' ) );
        add_filter( 'apd_tab_file_license', array( $this, 'tab_license' ) );

        // Mark pro tabs as not pro (unlock them)
        add_filter( 'apd_admin_tabs', array( $this, 'unlock_tabs' ) );

        // Enqueue pro admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
        add_action( 'admin_footer-index.php', array( $this, 'render_dashboard_wide_layout' ) );
    }

    /**
     * Unlock pro tabs.
     */
    public function unlock_tabs( $tabs ) {
        $pro_keys = array( 'payment-plans', 'min-max', 'gateway-rules', 'reminders', 'reports', 'conditional-rules', 'license' );
        foreach ( $pro_keys as $key ) {
            if ( isset( $tabs[ $key ] ) ) {
                $tabs[ $key ]['pro'] = false;
            }
        }
        return $tabs;
    }

    public function tab_payment_plans() { return APD_PRO_PLUGIN_DIR . 'admin/views/tabs/payment-plans.php'; }
    public function tab_min_max() { return APD_PRO_PLUGIN_DIR . 'admin/views/tabs/min-max.php'; }
    public function tab_gateway_rules() { return APD_PRO_PLUGIN_DIR . 'admin/views/tabs/gateway-rules.php'; }
    public function tab_reminders() { return APD_PRO_PLUGIN_DIR . 'admin/views/tabs/reminders.php'; }
    public function tab_reports() { return APD_PRO_PLUGIN_DIR . 'admin/views/tabs/reports.php'; }
    public function tab_conditional_rules() { return APD_PRO_PLUGIN_DIR . 'admin/views/tabs/conditional-rules.php'; }
    public function tab_license() { return APD_PRO_PLUGIN_DIR . 'admin/views/tabs/license.php'; }

    public function register_dashboard_widget() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'apd_deposit_overview_widget',
            __( 'Deposit Orders Overview', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            array( $this, 'render_dashboard_widget' )
        );
    }

    public function render_dashboard_widget() {
        $orders = wc_get_orders( array(
            'limit'      => 200,
            'orderby'    => 'date',
            'order'      => 'DESC',
            'meta_key'   => '_apd_is_deposit',
            'meta_value' => 'yes',
            'return'     => 'objects',
        ) );

        $stats = array(
            'orders'          => 0,
            'deposit_total'   => 0,
            'paid_total'      => 0,
            'balance_total'   => 0,
            'balance_paid'    => 0,
            'partially_paid'  => 0,
            'fully_settled'   => 0,
            'source_counts'   => array(),
            'recent_orders'   => array(),
        );

        foreach ( $orders as $order ) {
            if ( ! class_exists( 'APD_Order' ) || ! APD_Order::is_deposit_order( $order ) ) {
                continue;
            }

            $details = APD_Order::get_deposit_details( $order );
            if ( ! is_array( $details ) ) {
                continue;
            }

            $source = $this->detect_order_source( $order );

            $stats['orders']++;
            $stats['deposit_total'] += floatval( $details['deposit_amount'] ?? 0 );
            $stats['paid_total']    += floatval( $details['amount_paid'] ?? 0 );
            $stats['balance_total'] += floatval( $details['balance_due'] ?? 0 );

            if ( floatval( $details['balance_due'] ?? 0 ) > 0 ) {
                $stats['partially_paid']++;
            } else {
                $stats['fully_settled']++;
            }

            if ( ! isset( $stats['source_counts'][ $source ] ) ) {
                $stats['source_counts'][ $source ] = 0;
            }
            $stats['source_counts'][ $source ]++;

            $stats['recent_orders'][] = array(
                'id'          => $order->get_id(),
                'customer'    => $order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : __( 'Guest', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'source'      => $source,
                'status'      => wc_get_order_status_name( $order->get_status() ),
                'paid'        => floatval( $details['amount_paid'] ?? 0 ),
                'deposit'     => floatval( $details['deposit_amount'] ?? 0 ),
                'balance'     => floatval( $details['balance_due'] ?? 0 ),
                'total'       => floatval( $details['total_amount'] ?? 0 ),
                'plan'        => sanitize_text_field( (string) $order->get_meta( '_apd_payment_plan_name' ) ),
                'edit_url'    => function_exists( 'wc_get_container' ) && method_exists( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class, 'custom_orders_table_usage_is_enabled' )
                    ? ( wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
                        ? admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order->get_id() )
                        : admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) )
                    : admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
                'date'        => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
            );
        }

        $stats['balance_paid'] = max( 0, $stats['paid_total'] - $stats['deposit_total'] );

        arsort( $stats['source_counts'] );
        ?>
        <style>
            .apd-dashboard-widget { display:grid; gap:16px; max-width:100%; overflow:hidden; }
            .apd-dashboard-widget .apd-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; }
            .apd-dashboard-widget .apd-kpi { background:linear-gradient(135deg,#ffffff,#f6f8fc); border:1px solid #e2e8f0; border-radius:14px; padding:14px 16px; }
            .apd-dashboard-widget .apd-kpi-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
            .apd-dashboard-widget .apd-kpi-value { margin-top:6px; font-size:22px; font-weight:700; color:#0f172a; }
            .apd-dashboard-widget .apd-kpi-note { margin-top:4px; font-size:12px; color:#64748b; }
            .apd-dashboard-widget .apd-panels { display:grid; grid-template-columns:minmax(0,1fr); gap:14px; }
            .apd-dashboard-widget .apd-panel { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 16px; min-width:0; overflow:hidden; }
            .apd-dashboard-widget .apd-panel h4 { margin:0 0 12px; font-size:14px; }
            .apd-dashboard-widget .apd-source-list { display:grid; gap:8px; }
            .apd-dashboard-widget .apd-source-row { display:flex; justify-content:space-between; align-items:center; gap:10px; font-size:13px; }
            .apd-dashboard-widget .apd-source-filter { display:flex; justify-content:space-between; align-items:center; gap:10px; width:100%; padding:0; border:0; background:transparent; cursor:pointer; text-align:left; }
            .apd-dashboard-widget .apd-source-filter:focus { outline:none; box-shadow:none; }
            .apd-dashboard-widget .apd-source-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:12px; font-weight:600; transition:all .2s ease; }
            .apd-dashboard-widget .apd-source-filter.is-active .apd-source-badge { background:#4338ca; color:#fff; }
            .apd-dashboard-widget .apd-source-count { font-weight:700; color:#334155; }
            .apd-dashboard-widget .apd-table-wrap { width:100%; max-width:100%; overflow-x:auto; overflow-y:hidden; }
            .apd-dashboard-widget table { width:100%; min-width:620px; border-collapse:collapse; }
            .apd-dashboard-widget th, .apd-dashboard-widget td { padding:10px 8px; border-bottom:1px solid #edf2f7; text-align:left; vertical-align:top; }
            .apd-dashboard-widget th { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; }
            .apd-dashboard-widget td strong { display:block; color:#0f172a; }
            .apd-dashboard-widget td small { display:block; color:#64748b; margin-top:2px; }
            .apd-dashboard-widget .apd-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#fef3c7; color:#92400e; font-size:11px; font-weight:700; }
            .apd-dashboard-widget .apd-empty { padding:18px; border:1px dashed #cbd5e1; border-radius:14px; text-align:center; color:#64748b; background:#f8fafc; }
            .apd-dashboard-widget .apd-filter-meta { display:flex; align-items:center; justify-content:space-between; gap:10px; margin:-4px 0 12px; font-size:12px; color:#64748b; }
            .apd-dashboard-widget .apd-filter-meta strong { color:#0f172a; }
            .apd-dashboard-widget tr.apd-is-hidden { display:none; }
            .apd-dashboard-widget .apd-filter-count { font-weight:700; color:#4338ca; }
            .apd-dashboard-widget .apd-no-results { display:none; text-align:center; color:#64748b; padding:18px 10px; }
            .apd-dashboard-widget .apd-no-results.is-visible { display:block; }
            @media (min-width: 1200px) {
                .apd-dashboard-widget .apd-panels { grid-template-columns:minmax(0,260px) minmax(0,1fr); }
            }
            @media (max-width: 782px) {
                .apd-dashboard-widget .apd-kpis { grid-template-columns:minmax(0,1fr); }
                .apd-dashboard-widget table { min-width:520px; }
            }
        </style>
        <div class="apd-dashboard-widget">
            <div class="apd-kpis">
                <div class="apd-kpi">
                    <div class="apd-kpi-label"><?php esc_html_e( 'Deposit Orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                    <div class="apd-kpi-value apd-kpi-orders"><?php echo esc_html( $stats['orders'] ); ?></div>
                    <div class="apd-kpi-note apd-kpi-orders-note"><?php esc_html_e( 'All deposit-related WooCommerce orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                </div>
                <div class="apd-kpi">
                    <div class="apd-kpi-label"><?php esc_html_e( 'Original Deposit Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                    <div class="apd-kpi-value apd-kpi-deposit"><?php echo wp_kses_post( wc_price( $stats['deposit_total'] ) ); ?></div>
                    <div class="apd-kpi-note apd-kpi-deposit-note"><?php esc_html_e( 'Initial deposit amount defined across all deposit orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                </div>
                <div class="apd-kpi">
                    <div class="apd-kpi-label"><?php esc_html_e( 'Collected So Far', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                    <div class="apd-kpi-value apd-kpi-paid"><?php echo wp_kses_post( wc_price( $stats['paid_total'] ) ); ?></div>
                    <div class="apd-kpi-note apd-kpi-paid-note">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s: amount */
                                __( 'Includes %s collected through balance payments', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                                wp_strip_all_tags( wc_price( $stats['balance_paid'] ) )
                            )
                        );
                        ?>
                    </div>
                </div>
                <div class="apd-kpi">
                    <div class="apd-kpi-label"><?php esc_html_e( 'Outstanding Balance', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                    <div class="apd-kpi-value apd-kpi-balance"><?php echo wp_kses_post( wc_price( $stats['balance_total'] ) ); ?></div>
                    <div class="apd-kpi-note apd-kpi-balance-note">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: 1: partially-paid count, 2: fully-settled count */
                                __( '%1$d partially paid, %2$d fully settled deposit orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                                $stats['partially_paid'],
                                $stats['fully_settled']
                            )
                        );
                        ?>
                    </div>
                </div>
            </div>

            <div class="apd-panels">
                <div class="apd-panel">
                    <h4><?php esc_html_e( 'Order Sources', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h4>
                    <?php if ( empty( $stats['source_counts'] ) ) : ?>
                        <div class="apd-empty"><?php esc_html_e( 'No deposit orders found yet.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                    <?php else : ?>
                        <div class="apd-source-list">
                            <div class="apd-source-row">
                                <button type="button" class="apd-source-filter is-active" data-source="all" aria-pressed="true">
                                    <span class="apd-source-badge"><?php esc_html_e( 'All', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                                    <span class="apd-source-count"><?php echo esc_html( $stats['orders'] ); ?></span>
                                </button>
                            </div>
                            <?php foreach ( $stats['source_counts'] as $source => $count ) : ?>
                                <div class="apd-source-row">
                                    <button type="button" class="apd-source-filter" data-source="<?php echo esc_attr( $source ); ?>" aria-pressed="false">
                                        <span class="apd-source-badge"><?php echo esc_html( $this->get_source_label( $source ) ); ?></span>
                                        <span class="apd-source-count"><?php echo esc_html( $count ); ?></span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="apd-panel">
                    <h4><?php esc_html_e( 'Recent Deposit Orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h4>
                    <?php if ( empty( $stats['recent_orders'] ) ) : ?>
                        <div class="apd-empty"><?php esc_html_e( 'No recent deposit orders to show.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                    <?php else : ?>
                        <div class="apd-filter-meta">
                            <span>
                                <?php esc_html_e( 'Showing:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                                <strong class="apd-current-filter"><?php esc_html_e( 'All', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
                            </span>
                            <span class="apd-filter-count"><?php echo esc_html( count( $stats['recent_orders'] ) ); ?></span>
                        </div>
                        <div class="apd-table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Order', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                                        <th><?php esc_html_e( 'Source', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                                        <th><?php esc_html_e( 'Paid / Balance', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                                        <th><?php esc_html_e( 'Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="apd-recent-orders-body">
                                    <?php foreach ( $stats['recent_orders'] as $order_row ) : ?>
                                        <tr
                                            data-source="<?php echo esc_attr( $order_row['source'] ); ?>"
                                            data-paid="<?php echo esc_attr( wc_format_decimal( $order_row['paid'] ) ); ?>"
                                            data-deposit="<?php echo esc_attr( wc_format_decimal( $order_row['deposit'] ) ); ?>"
                                            data-balance="<?php echo esc_attr( wc_format_decimal( $order_row['balance'] ) ); ?>"
                                        >
                                            <td>
                                                <strong><a href="<?php echo esc_url( $order_row['edit_url'] ); ?>">#<?php echo esc_html( $order_row['id'] ); ?></a></strong>
                                                <small><?php echo esc_html( $order_row['customer'] ); ?></small>
                                                <small><?php echo esc_html( $order_row['date'] ); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo esc_html( $this->get_source_label( $order_row['source'] ) ); ?></strong>
                                                <?php if ( ! empty( $order_row['plan'] ) ) : ?>
                                                    <small><?php echo esc_html( $order_row['plan'] ); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo wp_kses_post( wc_price( $order_row['paid'] ) ); ?></strong>
                                                <small><?php echo esc_html__( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>: <?php echo wp_kses_post( wc_price( $order_row['deposit'] ) ); ?></small>
                                                <small><?php echo esc_html__( 'Balance', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>: <?php echo wp_kses_post( wc_price( $order_row['balance'] ) ); ?></small>
                                                <small><?php echo esc_html__( 'Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>: <?php echo wp_kses_post( wc_price( $order_row['total'] ) ); ?></small>
                                            </td>
                                            <td><span class="apd-status"><?php echo esc_html( $order_row['status'] ); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="apd-no-results"><?php esc_html_e( 'No deposit orders found for this filter.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
            (function(){
                "use strict";

                var widget = document.getElementById("apd_deposit_overview_widget");
                if (!widget) {
                    return;
                }

                var filters = widget.querySelectorAll(".apd-source-filter");
                var rows = widget.querySelectorAll(".apd-recent-orders-body tr[data-source]");
                var currentFilter = widget.querySelector(".apd-current-filter");
                var countNode = widget.querySelector(".apd-filter-count");
                var noResults = widget.querySelector(".apd-no-results");
                var ordersNode = widget.querySelector(".apd-kpi-orders");
                var depositNode = widget.querySelector(".apd-kpi-deposit");
                var paidNode = widget.querySelector(".apd-kpi-paid");
                var balanceNode = widget.querySelector(".apd-kpi-balance");
                var ordersNote = widget.querySelector(".apd-kpi-orders-note");
                var depositNote = widget.querySelector(".apd-kpi-deposit-note");
                var paidNote = widget.querySelector(".apd-kpi-paid-note");
                var balanceNote = widget.querySelector(".apd-kpi-balance-note");
                var moneyConfig = <?php echo wp_json_encode( array(
                    'symbol'       => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, get_bloginfo( 'charset' ) ),
                    'position'     => get_option( 'woocommerce_currency_pos', 'left' ),
                    'decimals'     => wc_get_price_decimals(),
                    'decimalSep'   => wc_get_price_decimal_separator(),
                    'thousandSep'  => wc_get_price_thousand_separator(),
                ) ); ?>;

                if (!filters.length || !rows.length || !currentFilter || !countNode || !noResults || !ordersNode || !depositNode || !paidNode || !balanceNode) {
                    return;
                }

                var formatMoney = function (amount) {
                    amount = Number(amount || 0);
                    var fixed = amount.toFixed(Number(moneyConfig.decimals || 2));
                    var parts = fixed.split(".");
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, moneyConfig.thousandSep || ",");
                    var formatted = parts[0];
                    if (parts.length > 1 && Number(moneyConfig.decimals || 2) > 0) {
                        formatted += (moneyConfig.decimalSep || ".") + parts[1];
                    }

                    switch (moneyConfig.position) {
                        case "right":
                            return formatted + moneyConfig.symbol;
                        case "left_space":
                            return moneyConfig.symbol + " " + formatted;
                        case "right_space":
                            return formatted + " " + moneyConfig.symbol;
                        case "left":
                        default:
                            return moneyConfig.symbol + formatted;
                    }
                };

                var setFilter = function (source, label) {
                    Array.prototype.forEach.call(filters, function (button) {
                        var isActive = button.getAttribute("data-source") === source;
                        button.classList.toggle("is-active", isActive);
                        button.setAttribute("aria-pressed", isActive ? "true" : "false");
                    });

                    var visibleCount = 0;
                    var depositTotal = 0;
                    var paidTotal = 0;
                    var balanceTotal = 0;
                    var partialCount = 0;
                    Array.prototype.forEach.call(rows, function (row) {
                        var matches = source === "all" || row.getAttribute("data-source") === source;
                        row.classList.toggle("apd-is-hidden", !matches);
                        if (matches) {
                            visibleCount++;
                            depositTotal += parseFloat(row.getAttribute("data-deposit") || 0);
                            paidTotal += parseFloat(row.getAttribute("data-paid") || 0);
                            balanceTotal += parseFloat(row.getAttribute("data-balance") || 0);
                            if (parseFloat(row.getAttribute("data-balance") || 0) > 0) {
                                partialCount++;
                            }
                        }
                    });

                    currentFilter.textContent = label;
                    countNode.textContent = String(visibleCount);
                    noResults.classList.toggle("is-visible", visibleCount === 0);
                    ordersNode.textContent = String(visibleCount);
                    depositNode.textContent = formatMoney(depositTotal);
                    paidNode.textContent = formatMoney(paidTotal);
                    balanceNode.textContent = formatMoney(balanceTotal);

                    if (ordersNote) {
                        ordersNote.textContent = label === "All" ? "All deposit-related WooCommerce orders" : "Deposit orders for " + label;
                    }
                    if (depositNote) {
                        depositNote.textContent = label === "All" ? "Initial deposit amount defined across all deposit orders" : "Initial deposit amount defined for " + label;
                    }
                    if (paidNote) {
                        paidNote.textContent = "Includes " + formatMoney(Math.max(0, paidTotal - depositTotal)) + " collected through balance payments";
                    }
                    if (balanceNote) {
                        balanceNote.textContent = partialCount + " partially paid, " + Math.max(0, visibleCount - partialCount) + " fully settled deposit orders";
                    }
                };

                Array.prototype.forEach.call(filters, function (button) {
                    button.addEventListener("click", function () {
                        var badge = button.querySelector(".apd-source-badge");
                        setFilter(button.getAttribute("data-source"), badge ? badge.textContent.trim() : "All");
                    });
                });
            })();
        </script>
        <?php
    }

    private function detect_order_source( $order ) {
        $types = array();

        foreach ( $order->get_items() as $item_id => $item ) {
            $mptbm_id = (int) wc_get_order_item_meta( $item_id, '_mptbm_id', true );
            $event_id = (int) wc_get_order_item_meta( $item_id, 'event_id', true );
            $ttbm_id  = (int) wc_get_order_item_meta( $item_id, '_ttbm_id', true );

            if ( $mptbm_id > 0 ) {
                $types['ecab'] = true;
            } elseif ( $event_id > 0 && get_post_type( $event_id ) === 'mep_events' ) {
                $types['event'] = true;
            } elseif ( $ttbm_id > 0 ) {
                $types['tour'] = true;
            } else {
                $types['product'] = true;
            }
        }

        $types = array_keys( $types );

        if ( empty( $types ) ) {
            return 'product';
        }

        return count( $types ) > 1 ? 'mixed' : $types[0];
    }

    private function get_source_label( $source ) {
        $labels = array(
            'product' => __( 'Woo Product', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'event'   => __( 'Event', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'tour'    => __( 'Tour', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'ecab'    => __( 'eCab', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'mixed'   => __( 'Mixed', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
        );

        return $labels[ $source ] ?? ucfirst( (string) $source );
    }

    public function render_dashboard_wide_layout() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <style>
            body.index-php #postbox-container-2,
            body.index-php #postbox-container-2 .meta-box-sortables {
                overflow: visible;
            }
            body.index-php #apd_deposit_overview_widget.apd-dashboard-wide {
                position: relative;
                z-index: 3;
                width: calc(200% + 20px);
                max-width: none;
            }
            @media (max-width: 1599px) {
                body.index-php #apd_deposit_overview_widget.apd-dashboard-wide {
                    width: 100%;
                }
            }
        </style>
        <script>
            (function(){
                "use strict";

                var applyWideWidget = function () {
                    var widget = document.getElementById("apd_deposit_overview_widget");
                    var containerTwo = document.querySelector("#postbox-container-2 .meta-box-sortables");
                    var containerThree = document.querySelector("#postbox-container-3");

                    if (!widget || !containerTwo) {
                        return;
                    }

                    if (widget.parentNode !== containerTwo) {
                        containerTwo.insertBefore(widget, containerTwo.firstChild);
                    }

                    var canStretch = window.innerWidth >= 1600 && containerThree && window.getComputedStyle(containerThree).display !== "none";
                    widget.classList.toggle("apd-dashboard-wide", !!canStretch);
                };

                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", applyWideWidget);
                } else {
                    applyWideWidget();
                }

                window.addEventListener("resize", applyWideWidget);
                window.setTimeout(applyWideWidget, 120);
            })();
        </script>
        <?php
    }

    /**
     * Enqueue pro admin assets.
     */
    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_apd-deposits' !== $hook ) return;

        wp_enqueue_style(
            'apd-pro-admin',
            APD_PRO_PLUGIN_URL . 'admin/css/apd-pro-admin.css',
            array( 'apd-admin' ),
            APD_PRO_VERSION
        );
        wp_enqueue_script(
            'apd-pro-admin',
            APD_PRO_PLUGIN_URL . 'admin/js/apd-pro-admin.js',
            array( 'apd-admin', 'jquery' ),
            APD_PRO_VERSION,
            true
        );
    }
}
