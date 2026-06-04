<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = get_option( 'apd_settings', array() );
$logs     = class_exists( 'APD_Reminders' ) ? APD_Reminders::get_logs( 20 ) : array();
?>
<div class="apd-tab-header">
    <h2><?php esc_html_e( 'Auto Reminders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h2>
    <p><?php esc_html_e( 'Send automatic email reminders for outstanding balances.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
</div>
<form class="apd-settings-form" data-tab="reminders">
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Reminder Settings', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Enable Auto Reminders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Automatically send balance due reminders via email.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <label class="apd-toggle">
                        <input type="checkbox" name="enable_reminders" value="yes" <?php checked( $settings['enable_reminders'] ?? 'no', 'yes' ); ?> />
                        <span class="apd-toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Days Before Due Date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Send reminder X days before the installment due date.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="number" name="reminder_days_before" class="apd-input" min="1" max="30" value="<?php echo esc_attr( $settings['reminder_days_before'] ?? 3 ); ?>" />
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Days After Due Date (Overdue)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Send overdue reminder X days after the due date.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="number" name="reminder_days_after" class="apd-input" min="1" max="30" value="<?php echo esc_attr( $settings['reminder_days_after'] ?? 1 ); ?>" />
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Reminder Interval (General)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Days between general reminders for non-plan orders.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="number" name="reminder_interval" class="apd-input" min="1" max="90" value="<?php echo esc_attr( $settings['reminder_interval'] ?? 7 ); ?>" />
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Auto-Cancel Overdue Orders After', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Automatically cancel deposit orders that still have unpaid balance after this many overdue days. Set 0 to disable.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="number" name="auto_cancel_overdue_days" class="apd-input" min="0" max="365" value="<?php echo esc_attr( $settings['auto_cancel_overdue_days'] ?? 0 ); ?>" />
                </div>
            </div>
        </div>
    </div>
    <div class="apd-form-actions">
        <button type="submit" class="apd-btn apd-btn-primary"><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Settings', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></button>
    </div>
</form>

<div class="apd-card" style="margin-top:24px;">
    <div class="apd-card-header"><h3><?php esc_html_e( 'Reminder & Auto-Cancel History', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
    <div class="apd-card-body">
        <?php if ( ! empty( $logs ) ) : ?>
            <table class="apd-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                        <th><?php esc_html_e( 'Order', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                        <th><?php esc_html_e( 'Customer', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                        <th><?php esc_html_e( 'Due Date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $logs as $log ) : ?>
                        <?php
                        $order_id   = intval( $log['order_id'] ?? 0 );
                        $order_link = $order_id ? admin_url( 'post.php?post=' . $order_id . '&action=edit' ) : '';
                        ?>
                        <tr>
                            <td><?php echo esc_html( $log['sent_at'] ?? '' ); ?></td>
                            <td><?php echo esc_html( ucwords( str_replace( '_', ' ', $log['type'] ?? '' ) ) ); ?></td>
                            <td>
                                <?php if ( $order_link ) : ?>
                                    <a href="<?php echo esc_url( $order_link ); ?>">#<?php echo esc_html( $log['order_number'] ?? $order_id ); ?></a>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                echo esc_html( $log['customer'] ?? '' );
                                if ( ! empty( $log['email'] ) ) {
                                    echo '<br><small>' . esc_html( $log['email'] ) . '</small>';
                                }
                                ?>
                            </td>
                            <td><?php echo ! empty( $log['due_date'] ) ? esc_html( $log['due_date'] ) : '&mdash;'; ?></td>
                            <td><?php echo isset( $log['amount'] ) ? wp_kses_post( wc_price( floatval( $log['amount'] ) ) ) : '&mdash;'; ?></td>
                            <td><?php echo esc_html( ucfirst( $log['status'] ?? '' ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No reminder activity has been logged yet.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
        <?php endif; ?>
    </div>
</div>
