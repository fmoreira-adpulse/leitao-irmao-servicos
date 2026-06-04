<?php if ( ! defined( 'ABSPATH' ) ) exit;
$settings = get_option( 'apd_settings', array() );
$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
$deposit_gws = $settings['deposit_gateways'] ?? array();
$balance_gws = $settings['balance_gateways'] ?? array();
$gateway_modes = $settings['gateway_checkout_modes'] ?? array();
$plans = class_exists( 'APD_Payment_Plans' ) ? APD_Payment_Plans::get_active_plans() : array();
?>
<div class="apd-tab-header">
    <h2><?php esc_html_e( 'Gateway Rules', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h2>
    <p><?php esc_html_e( 'Control which payment gateways are available for deposit and balance payments.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
</div>
<form class="apd-settings-form" data-tab="gateway-rules">
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Deposit Payment Gateways', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <p class="apd-field-desc" style="margin-bottom:12px;"><?php esc_html_e( 'Select gateways allowed for initial deposit payments. Leave empty to allow all.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
            <div class="apd-gateway-grid">
                <?php foreach ( $gateways as $gw ) :
                    if ( $gw->enabled !== 'yes' ) continue; ?>
                <label class="apd-gateway-item">
                    <input type="checkbox" name="deposit_gateways[]" value="<?php echo esc_attr( $gw->id ); ?>" <?php checked( in_array( $gw->id, $deposit_gws ) ); ?> />
                    <span class="apd-gateway-label"><?php echo esc_html( $gw->get_title() ); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Balance Payment Gateways', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <p class="apd-field-desc" style="margin-bottom:12px;"><?php esc_html_e( 'Select gateways allowed for balance payments. Leave empty to allow all.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
            <div class="apd-gateway-grid">
                <?php foreach ( $gateways as $gw ) :
                    if ( $gw->enabled !== 'yes' ) continue; ?>
                <label class="apd-gateway-item">
                    <input type="checkbox" name="balance_gateways[]" value="<?php echo esc_attr( $gw->id ); ?>" <?php checked( in_array( $gw->id, $balance_gws ) ); ?> />
                    <span class="apd-gateway-label"><?php echo esc_html( $gw->get_title() ); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="apd-card">
        <div class="apd-card-header"><h3><?php esc_html_e( 'Gateway Checkout Mode Mapping', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
        <div class="apd-card-body">
            <div style="margin-bottom:16px;padding:14px 16px;border:1px solid #dbeafe;border-radius:12px;background:#eff6ff;">
                <strong style="display:block;font-size:13px;color:#1d4ed8;margin-bottom:8px;"><?php esc_html_e( 'How this works', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
                <ul style="margin:0 0 0 18px;color:#475569;font-size:13px;line-height:1.7;">
                    <li><?php esc_html_e( 'Inherit: keep the normal APD product/category/global settings.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></li>
                    <li><?php esc_html_e( 'Full payment only: customer must pay the full amount when this gateway is selected.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></li>
                    <li><?php esc_html_e( 'Fixed deposit or Percentage deposit: enter the amount in Mode Value.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></li>
                    <li><?php esc_html_e( 'Payment plan: choose one plan from the Payment Plan dropdown. That plan will be applied when this gateway is selected.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></li>
                    <li><?php esc_html_e( 'Min / Max deposit: uses the product min/max range rules. If the customer already chose a custom min/max deposit amount, it will be kept. Otherwise the minimum allowed deposit is used automatically.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></li>
                </ul>
            </div>
            <p class="apd-field-desc" style="margin-bottom:12px;"><?php esc_html_e( 'Automatically switch the APD behavior when the customer chooses a payment gateway on checkout. Use Inherit to keep the normal product/category/global deposit settings.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
            <div style="display:grid;gap:14px;">
                <?php foreach ( $gateways as $gw ) :
                    if ( $gw->enabled !== 'yes' ) {
                        continue;
                    }

                    $rule          = isset( $gateway_modes[ $gw->id ] ) && is_array( $gateway_modes[ $gw->id ] ) ? $gateway_modes[ $gw->id ] : array();
                    $selected_mode = $rule['mode'] ?? 'inherit';
                    $selected_value = $rule['value'] ?? '';
                    $selected_plan = $rule['plan_id'] ?? '';
                    ?>
                <div class="apd-gateway-mode-card" style="padding:14px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;">
                    <div style="display:grid;grid-template-columns:minmax(180px,1.2fr) minmax(180px,1fr) minmax(140px,.8fr) minmax(180px,1fr);gap:14px;align-items:end;">
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#0f172a;margin-bottom:6px;"><?php echo esc_html( $gw->get_title() ); ?></label>
                            <p style="margin:0;color:#64748b;font-size:12px;"><?php echo esc_html( $gw->id ); ?></p>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:6px;"><?php esc_html_e( 'APD Mode', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                            <select name="gateway_checkout_mode[<?php echo esc_attr( $gw->id ); ?>]" class="apd-select apd-gateway-mode-select" style="width:100%;">
                                <option value="inherit" <?php selected( $selected_mode, 'inherit' ); ?>><?php esc_html_e( 'Inherit normal APD rules', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                                <option value="full" <?php selected( $selected_mode, 'full' ); ?>><?php esc_html_e( 'Full payment only', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                                <option value="fixed" <?php selected( $selected_mode, 'fixed' ); ?>><?php esc_html_e( 'Fixed deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                                <option value="percentage" <?php selected( $selected_mode, 'percentage' ); ?>><?php esc_html_e( 'Percentage deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                                <option value="payment_plan" <?php selected( $selected_mode, 'payment_plan' ); ?>><?php esc_html_e( 'Payment plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                                <option value="min_max" <?php selected( $selected_mode, 'min_max' ); ?>><?php esc_html_e( 'Min / Max deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                            </select>
                        </div>
                        <div class="apd-gateway-value-wrap">
                            <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:6px;"><?php esc_html_e( 'Mode Value', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                            <input type="number" name="gateway_checkout_value[<?php echo esc_attr( $gw->id ); ?>]" class="apd-input apd-gateway-value-input" step="0.01" min="0" value="<?php echo esc_attr( $selected_value ); ?>" placeholder="<?php esc_attr_e( 'For fixed or %', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>" />
                        </div>
                        <div class="apd-gateway-plan-wrap">
                            <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:6px;"><?php esc_html_e( 'Payment Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                            <select name="gateway_checkout_plan[<?php echo esc_attr( $gw->id ); ?>]" class="apd-select apd-gateway-plan-select" style="width:100%;">
                                <option value=""><?php esc_html_e( 'Auto / First available', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                                <?php foreach ( $plans as $plan_id => $plan ) : ?>
                                    <option value="<?php echo esc_attr( $plan_id ); ?>" <?php selected( $selected_plan, $plan_id ); ?>><?php echo esc_html( $plan['name'] ?? $plan_id ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <p class="apd-gateway-mode-help" style="margin:10px 0 0;color:#64748b;font-size:12px;">
                        <?php esc_html_e( 'Choose an APD mode first. Extra fields will appear only when that mode needs them.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="apd-form-actions">
        <button type="submit" class="apd-btn apd-btn-primary"><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Rules', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></button>
    </div>
</form>
<script>
(function(){
    var cards = document.querySelectorAll('.apd-gateway-mode-card');

    if (!cards.length) {
        return;
    }

    var setHelpText = function (helpNode, mode) {
        var messages = {
            inherit: 'Normal APD settings will be used for this gateway.',
            full: 'This gateway will force full payment only.',
            fixed: 'Enter the fixed deposit amount in Mode Value.',
            percentage: 'Enter the deposit percentage in Mode Value. Example: 30 means 30%.',
            payment_plan: 'Choose which payment plan should be applied when this gateway is selected.',
            min_max: 'This gateway will use the normal min/max deposit rules. A previously chosen custom deposit amount will be kept; otherwise the minimum allowed deposit is applied automatically.'
        };

        helpNode.textContent = messages[mode] || messages.inherit;
    };

    var updateCard = function (card) {
        var select = card.querySelector('.apd-gateway-mode-select');
        var valueWrap = card.querySelector('.apd-gateway-value-wrap');
        var valueInput = card.querySelector('.apd-gateway-value-input');
        var planWrap = card.querySelector('.apd-gateway-plan-wrap');
        var planSelect = card.querySelector('.apd-gateway-plan-select');
        var helpNode = card.querySelector('.apd-gateway-mode-help');

        if (!select || !valueWrap || !valueInput || !planWrap || !planSelect || !helpNode) {
            return;
        }

        var mode = select.value;
        var needsValue = mode === 'fixed' || mode === 'percentage';
        var needsPlan = mode === 'payment_plan';

        valueWrap.style.display = needsValue ? '' : 'none';
        valueInput.disabled = !needsValue;

        planWrap.style.display = needsPlan ? '' : 'none';
        planSelect.disabled = !needsPlan;

        setHelpText(helpNode, mode);
    };

    Array.prototype.forEach.call(cards, function(card){
        var select = card.querySelector('.apd-gateway-mode-select');
        if (!select) {
            return;
        }
        select.addEventListener('change', function(){
            updateCard(card);
        });
        updateCard(card);
    });
})();
</script>
