<?php if ( ! defined( 'ABSPATH' ) ) exit;
$summary = APD_Reports::get_summary();
?>
<div class="apd-tab-header">
    <h2><?php esc_html_e( 'Deposit Reports', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h2>
    <p><?php esc_html_e( 'Overview of deposits, balances, and collection performance.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
</div>

<div class="apd-report-stats">
    <div class="apd-stat-card">
        <div class="apd-stat-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);"><span class="dashicons dashicons-chart-bar"></span></div>
        <div class="apd-stat-content">
            <span class="apd-stat-value"><?php echo esc_html( $summary['total_orders'] ); ?></span>
            <span class="apd-stat-label"><?php esc_html_e( 'Total Deposit Orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
        </div>
    </div>
    <div class="apd-stat-card">
        <div class="apd-stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><span class="dashicons dashicons-money-alt"></span></div>
        <div class="apd-stat-content">
            <span class="apd-stat-value"><?php echo wc_price( $summary['original_deposit'] ); ?></span>
            <span class="apd-stat-label"><?php esc_html_e( 'Original Deposit Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
        </div>
    </div>
    <div class="apd-stat-card">
        <div class="apd-stat-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a);"><span class="dashicons dashicons-chart-line"></span></div>
        <div class="apd-stat-content">
            <span class="apd-stat-value"><?php echo wc_price( $summary['total_collected'] ); ?></span>
            <span class="apd-stat-label"><?php esc_html_e( 'Collected So Far', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
        </div>
    </div>
    <div class="apd-stat-card">
        <div class="apd-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);"><span class="dashicons dashicons-clock"></span></div>
        <div class="apd-stat-content">
            <span class="apd-stat-value"><?php echo wc_price( $summary['total_pending'] ); ?></span>
            <span class="apd-stat-label"><?php esc_html_e( 'Outstanding Balance', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
        </div>
    </div>
</div>

<div class="apd-card">
    <div class="apd-card-header"><h3><?php esc_html_e( 'Collection Rate', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
    <div class="apd-card-body">
        <div class="apd-progress-bar-container">
            <div class="apd-progress-label">
                <span><?php esc_html_e( 'Collected vs Total Order Value', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                <span class="apd-progress-percent"><?php echo esc_html( $summary['collection_rate'] ); ?>%</span>
            </div>
            <div class="apd-progress-bar">
                <div class="apd-progress-fill" style="width:<?php echo esc_attr( $summary['collection_rate'] ); ?>%"></div>
            </div>
            <small style="display:block;margin-top:8px;color:#64748b;">
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: collected amount, 2: total order value */
                        __( '%1$s collected out of %2$s total deposit-order value.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                        wp_strip_all_tags( wc_price( $summary['total_collected'] ) ),
                        wp_strip_all_tags( wc_price( $summary['total_order_value'] ) )
                    )
                );
                ?>
            </small>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:16px;">
            <div style="padding:14px 16px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                <strong style="display:block;font-size:13px;color:#0f172a;"><?php esc_html_e( 'Balance Payments Collected', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
                <span style="display:block;margin-top:6px;font-size:20px;font-weight:700;color:#111827;"><?php echo wc_price( $summary['balance_paid'] ); ?></span>
                <small style="display:block;margin-top:6px;color:#64748b;"><?php esc_html_e( 'Additional payments received after the initial deposit.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></small>
            </div>
            <div style="padding:14px 16px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                <strong style="display:block;font-size:13px;color:#0f172a;"><?php esc_html_e( 'Settlement Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
                <span style="display:block;margin-top:6px;font-size:20px;font-weight:700;color:#111827;">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: 1: partial count, 2: settled count */
                            __( '%1$d partial / %2$d settled', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                            intval( $summary['partially_paid_count'] ),
                            intval( $summary['fully_settled_count'] )
                        )
                    );
                    ?>
                </span>
                <small style="display:block;margin-top:6px;color:#64748b;"><?php esc_html_e( 'Matches the same order status split shown in the dashboard widget.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></small>
            </div>
        </div>
    </div>
</div>
