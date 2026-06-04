<?php if ( ! defined( 'ABSPATH' ) ) exit;
$settings = get_option( 'apd_settings', array() );
$roles = wp_roles()->get_names();
$selected_roles = $settings['conditional_user_roles'] ?? array();
?>
<div class="apd-tab-header">
    <h2><?php esc_html_e( 'Conditional Rules', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h2>
    <p><?php esc_html_e( 'Define conditions under which deposits are available.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
</div>
<form class="apd-settings-form" data-tab="conditional-rules">
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Cart Total Threshold', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Minimum Cart Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Deposits only available when cart total exceeds this amount. Set to 0 to disable.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <div class="apd-input-group">
                        <input type="number" name="conditional_min_cart" class="apd-input" step="0.01" min="0" value="<?php echo esc_attr( $settings['conditional_min_cart'] ?? 0 ); ?>" />
                        <span class="apd-input-suffix"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
                    </div>
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Maximum Cart Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Deposits only available when cart total stays below this amount. Set to 0 to disable.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <div class="apd-input-group">
                        <input type="number" name="conditional_max_cart" class="apd-input" step="0.01" min="0" value="<?php echo esc_attr( $settings['conditional_max_cart'] ?? 0 ); ?>" />
                        <span class="apd-input-suffix"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Cart Quantity Rules', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Minimum Quantity', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Deposits only available when the cart contains at least this many items. Set to 0 to disable.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="number" name="conditional_min_quantity" class="apd-input" min="0" value="<?php echo esc_attr( $settings['conditional_min_quantity'] ?? 0 ); ?>" />
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Maximum Quantity', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Deposits only available while the cart quantity stays at or below this value. Set to 0 to disable.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="number" name="conditional_max_quantity" class="apd-input" min="0" value="<?php echo esc_attr( $settings['conditional_max_quantity'] ?? 0 ); ?>" />
                </div>
            </div>
        </div>
    </div>
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Date Range', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Available From', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Deposits only available from this date. Leave empty to disable.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="date" name="conditional_date_from" class="apd-input" value="<?php echo esc_attr( $settings['conditional_date_from'] ?? '' ); ?>" />
                </div>
            </div>
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Available Until', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'Deposits only available until this date.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <input type="date" name="conditional_date_to" class="apd-input" value="<?php echo esc_attr( $settings['conditional_date_to'] ?? '' ); ?>" />
                </div>
            </div>
        </div>
    </div>
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'User Role Restrictions', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div class="apd-field-row">
                <div class="apd-field-label">
                    <label><?php esc_html_e( 'Allow Guests', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <p class="apd-field-desc"><?php esc_html_e( 'If role restrictions are enabled, allow non-logged-in customers to still use deposits.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                </div>
                <div class="apd-field-input">
                    <label class="apd-toggle">
                        <input type="checkbox" name="conditional_allow_guests" value="yes" <?php checked( $settings['conditional_allow_guests'] ?? 'yes', 'yes' ); ?> />
                        <span class="apd-toggle-slider"></span>
                    </label>
                </div>
            </div>
            <p class="apd-field-desc" style="margin-bottom:12px;"><?php esc_html_e( 'Select which user roles can use deposits. Leave empty to allow all.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
            <div class="apd-gateway-grid">
                <?php foreach ( $roles as $role_key => $role_name ) : ?>
                <label class="apd-gateway-item">
                    <input type="checkbox" name="conditional_user_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $selected_roles ) ); ?> />
                    <span class="apd-gateway-label"><?php echo esc_html( $role_name ); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="apd-form-actions">
        <button type="submit" class="apd-btn apd-btn-primary"><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Rules', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></button>
    </div>
</form>
