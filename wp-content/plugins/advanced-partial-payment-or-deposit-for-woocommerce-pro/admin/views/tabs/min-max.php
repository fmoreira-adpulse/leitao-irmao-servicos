<?php if ( ! defined( 'ABSPATH' ) ) exit;
$settings = get_option( 'apd_settings', array() ); ?>
<div class="apd-tab-header">
    <h2><?php esc_html_e( 'Min / Max Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h2>
    <p><?php esc_html_e( 'Set minimum and maximum boundaries for deposit amounts.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
</div>
<form class="apd-settings-form" data-tab="min-max">
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Minimum Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Minimum Deposit Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'The smallest deposit amount allowed. Set to 0 to disable.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <div class="apd-input-group">
                        <input type="number" name="min_deposit_amount" class="apd-input" step="0.01" min="0" value="<?php echo esc_attr( $settings['min_deposit_amount'] ?? 0 ); ?>" />
                        <span class="apd-input-suffix"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Maximum Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Maximum Deposit Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'The largest deposit amount allowed. Set to 0 to disable.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <div class="apd-input-group">
                        <input type="number" name="max_deposit_amount" class="apd-input" step="0.01" min="0" value="<?php echo esc_attr( $settings['max_deposit_amount'] ?? 0 ); ?>" />
                        <span class="apd-input-suffix"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Partial Payment Fee / Extra Charge', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Enable Extra Charge', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Apply an extra fee only when the customer chooses deposit / partial payment.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <label class="apd-toggle">
                        <input type="checkbox" name="enable_partial_payment_fee" value="yes" <?php checked( $settings['enable_partial_payment_fee'] ?? 'no', 'yes' ); ?> />
                        <span class="apd-toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Fee Label', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Shown in cart, checkout, and the order totals.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="text" name="partial_payment_fee_label" class="apd-input" value="<?php echo esc_attr( $settings['partial_payment_fee_label'] ?? __( 'Partial Payment Fee', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) ); ?>" />
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Fee Type', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Choose a fixed charge or a percentage of the deposit being paid now.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <select name="partial_payment_fee_type" class="apd-select">
                        <option value="fixed" <?php selected( $settings['partial_payment_fee_type'] ?? 'fixed', 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                        <option value="percentage" <?php selected( $settings['partial_payment_fee_type'] ?? 'fixed', 'percentage' ); ?>><?php esc_html_e( 'Percentage of Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                    </select>
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Fee Value', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'For percentage, enter 5 for 5%. For fixed, enter the exact charge amount.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <div class="apd-input-group">
                        <input type="number" name="partial_payment_fee_value" class="apd-input" step="0.01" min="0" value="<?php echo esc_attr( $settings['partial_payment_fee_value'] ?? 0 ); ?>" />
                        <span class="apd-input-suffix"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?> / %</span>
                    </div>
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Taxable Fee', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Enable only if your store should tax the extra charge.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <label class="apd-toggle">
                        <input type="checkbox" name="partial_payment_fee_taxable" value="yes" <?php checked( $settings['partial_payment_fee_taxable'] ?? 'no', 'yes' ); ?> />
                        <span class="apd-toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="apd-form-actions">
        <button type="submit" class="apd-btn apd-btn-primary"><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Settings', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></button>
    </div>
</form>
