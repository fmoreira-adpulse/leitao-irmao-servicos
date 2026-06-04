<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * My Account deposits tab content.
 *
 * @var array  $orders
 * @var string $pay_btn_label
 */
?>
<div class="apd-myaccount-deposits">
    <h3><?php esc_html_e( 'My Deposits', 'advanced-partial-payment' ); ?></h3>

    <?php if ( ! empty( $orders ) ) : ?>
    <table class="woocommerce-orders-table apd-deposits-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Order', 'advanced-partial-payment' ); ?></th>
                <th><?php esc_html_e( 'Date', 'advanced-partial-payment' ); ?></th>
                <th><?php esc_html_e( 'Total', 'advanced-partial-payment' ); ?></th>
                <th><?php esc_html_e( 'Paid', 'advanced-partial-payment' ); ?></th>
                <th><?php esc_html_e( 'Balance', 'advanced-partial-payment' ); ?></th>
                <th><?php esc_html_e( 'Status', 'advanced-partial-payment' ); ?></th>
                <th><?php esc_html_e( 'Action', 'advanced-partial-payment' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $orders as $order ) :
                $details = APD_Order::get_deposit_details( $order );
                if ( ! $details ) continue;

                $plan_name = (string) $order->get_meta( '_apd_payment_plan_name' );
                $schedule  = $order->get_meta( '_apd_installment_schedule' );
                $schedule  = is_array( $schedule ) ? $schedule : array();
                $paid      = floatval( $details['amount_paid'] );

                // Work out which installment is "next due": the first one whose
                // cumulative total is not yet covered by the amount already paid.
                $cumulative   = 0;
                $next_index   = -1;
                foreach ( $schedule as $i => $inst ) {
                    $cumulative += floatval( $inst['amount'] ?? 0 );
                    if ( $next_index === -1 && $cumulative > $paid + 0.001 ) {
                        $next_index = $i;
                    }
                }
            ?>
            <tr>
                <td data-title="<?php esc_attr_e( 'Order', 'advanced-partial-payment' ); ?>">
                    <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a>
                    <?php if ( $plan_name ) : ?>
                        <small style="display:block;color:#6366f1;font-weight:600;"><?php echo esc_html( $plan_name ); ?></small>
                    <?php endif; ?>
                </td>
                <td data-title="<?php esc_attr_e( 'Date', 'advanced-partial-payment' ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
                <td data-title="<?php esc_attr_e( 'Total', 'advanced-partial-payment' ); ?>"><?php echo wp_kses_post( wc_price( $details['total_amount'] ) ); ?></td>
                <td data-title="<?php esc_attr_e( 'Paid', 'advanced-partial-payment' ); ?>" class="apd-text-success"><?php echo wp_kses_post( wc_price( $details['amount_paid'] ) ); ?></td>
                <td data-title="<?php esc_attr_e( 'Balance', 'advanced-partial-payment' ); ?>" class="<?php echo $details['balance_due'] > 0 ? 'apd-text-danger' : 'apd-text-success'; ?>">
                    <strong><?php echo wp_kses_post( wc_price( $details['balance_due'] ) ); ?></strong>
                </td>
                <td data-title="<?php esc_attr_e( 'Status', 'advanced-partial-payment' ); ?>">
                    <?php if ( $details['balance_due'] > 0 ) : ?>
                        <span class="apd-status-badge apd-status-pending"><?php esc_html_e( 'Partially Paid', 'advanced-partial-payment' ); ?></span>
                    <?php else : ?>
                        <span class="apd-status-badge apd-status-complete"><?php esc_html_e( 'Fully Paid', 'advanced-partial-payment' ); ?></span>
                    <?php endif; ?>
                </td>
                <td data-title="<?php esc_attr_e( 'Action', 'advanced-partial-payment' ); ?>">
                    <?php if ( $details['balance_due'] > 0 ) : ?>
                        <a href="<?php echo esc_url( APD_Pay_Balance::get_pay_balance_url( $order->get_id() ) ); ?>"
                           class="woocommerce-button button apd-pay-balance-btn">
                            <?php echo esc_html( $pay_btn_label ); ?>
                        </a>
                    <?php else : ?>
                        <span class="apd-paid-check">✓</span>
                    <?php endif; ?>
                </td>
            </tr>

            <?php if ( ! empty( $schedule ) ) : ?>
            <tr class="apd-schedule-row">
                <td colspan="7" style="padding:0;border-top:0;">
                    <details class="apd-schedule-details" style="margin:0 0 4px;">
                        <summary style="cursor:pointer;padding:10px 12px;background:#f8fafc;border-radius:6px;font-weight:600;color:#334155;list-style:none;">
                            <?php esc_html_e( 'Payment plan & upcoming payments', 'advanced-partial-payment' ); ?>
                            <?php if ( $next_index > -1 && isset( $schedule[ $next_index ] ) ) : ?>
                                <span style="color:#6366f1;font-weight:600;margin-left:6px;">
                                    <?php
                                    printf(
                                        /* translators: 1: amount, 2: due date */
                                        esc_html__( 'Next: %1$s due %2$s', 'advanced-partial-payment' ),
                                        wp_kses_post( wc_price( floatval( $schedule[ $next_index ]['amount'] ?? 0 ) ) ),
                                        esc_html( $schedule[ $next_index ]['due_date'] ?? ( $schedule[ $next_index ]['due_label'] ?? '' ) )
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </summary>
                        <table class="apd-schedule-table" style="width:100%;border-collapse:collapse;margin-top:8px;font-size:13px;">
                            <thead>
                                <tr>
                                    <th style="text-align:left;padding:6px 10px;color:#64748b;">#</th>
                                    <th style="text-align:left;padding:6px 10px;color:#64748b;"><?php esc_html_e( 'Due', 'advanced-partial-payment' ); ?></th>
                                    <th style="text-align:left;padding:6px 10px;color:#64748b;"><?php esc_html_e( 'Date', 'advanced-partial-payment' ); ?></th>
                                    <th style="text-align:right;padding:6px 10px;color:#64748b;"><?php esc_html_e( 'Amount', 'advanced-partial-payment' ); ?></th>
                                    <th style="text-align:center;padding:6px 10px;color:#64748b;"><?php esc_html_e( 'Status', 'advanced-partial-payment' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $schedule as $i => $inst ) :
                                    if ( $i < $next_index || $next_index === -1 ) {
                                        $row_status      = __( 'Paid', 'advanced-partial-payment' );
                                        $row_status_color = '#16a34a';
                                    } elseif ( $i === $next_index ) {
                                        $row_status      = __( 'Next due', 'advanced-partial-payment' );
                                        $row_status_color = '#6366f1';
                                    } else {
                                        $row_status      = __( 'Upcoming', 'advanced-partial-payment' );
                                        $row_status_color = '#94a3b8';
                                    }
                                ?>
                                <tr style="border-top:1px solid #eef2f7;">
                                    <td style="padding:6px 10px;"><?php echo esc_html( $inst['number'] ?? ( $i + 1 ) ); ?></td>
                                    <td style="padding:6px 10px;"><?php echo esc_html( $inst['due_label'] ?? '' ); ?></td>
                                    <td style="padding:6px 10px;"><?php echo esc_html( $inst['due_date'] ?? '' ); ?></td>
                                    <td style="padding:6px 10px;text-align:right;font-weight:600;"><?php echo wp_kses_post( wc_price( floatval( $inst['amount'] ?? 0 ) ) ); ?></td>
                                    <td style="padding:6px 10px;text-align:center;color:<?php echo esc_attr( $row_status_color ); ?>;font-weight:600;">
                                        <?php if ( $i === $next_index ) : ?>
                                            <a href="<?php echo esc_url( APD_Pay_Balance::get_pay_balance_url( $order->get_id(), floatval( $inst['amount'] ?? 0 ) ) ); ?>"
                                               class="woocommerce-button button apd-pay-installment-btn"
                                               style="padding:4px 12px;font-size:12px;">
                                                <?php
                                                printf(
                                                    /* translators: %s: installment amount */
                                                    esc_html__( 'Pay %s', 'advanced-partial-payment' ),
                                                    wp_strip_all_tags( wc_price( floatval( $inst['amount'] ?? 0 ) ) )
                                                );
                                                ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo esc_html( $row_status ); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </details>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
    <div class="woocommerce-message woocommerce-message--info">
        <p><?php esc_html_e( 'No deposit orders found.', 'advanced-partial-payment' ); ?></p>
    </div>
    <?php endif; ?>
</div>
