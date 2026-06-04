<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Payment plan selector on product page.
 *
 * Variables available from APD_Payment_Plans::display_plan_selector():
 * @var array  $plans
 * @var float  $price
 * @var float  $deposit_amount
 * @var float  $due_balance
 * @var bool   $allow_full
 * @var string $deposit_text
 * @var string $balance_label
 * @var string $full_text
 */

$plans = is_array( $plans ) ? $plans : array();

if ( empty( $plans ) ) {
    return;
}

$default_plan_id = '';

foreach ( $plans as $plan_id => $plan ) {
    if ( ! empty( APD_Payment_Plans::build_schedule( $plan, $price ) ) ) {
        $default_plan_id = (string) $plan_id;
        break;
    }
}
$selector_title = __( 'Choose How You Want to Pay', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
$show_standard_deposit = $deposit_amount > 0;
?>
<div class="apd-payment-plan-selector">
    <div class="apd-plan-selector-header">
        <span class="dashicons dashicons-calendar-alt" style="color:#6366f1;font-size:18px;width:18px;height:18px;"></span>
        <strong><?php echo esc_html( $selector_title ); ?></strong>
    </div>

    <input type="hidden" name="apd_payment_type" id="apd_payment_type" value="deposit" />
    <input type="hidden" name="apd_selected_plan" id="apd_selected_plan" value="<?php echo esc_attr( $show_standard_deposit ? '' : $default_plan_id ); ?>" />

    <div class="apd-plan-options">
        <?php if ( $show_standard_deposit ) : ?>
        <label class="apd-plan-radio-option">
            <input
                type="radio"
                name="apd_plan_choice"
                value="deposit"
                data-payment-type="deposit"
                data-plan-id=""
                checked="checked"
            />
            <div class="apd-plan-radio-content">
                <span class="apd-plan-radio-circle"></span>
                <div class="apd-plan-radio-text">
                    <span class="apd-plan-radio-title"><?php echo wp_kses_post( $deposit_text ); ?></span>
                    <span class="apd-plan-radio-desc">
                        <?php echo esc_html( $balance_label ); ?>: <?php echo wp_kses_post( wc_price( $due_balance ) ); ?>
                    </span>
                </div>
            </div>
        </label>
        <?php endif; ?>

        <?php foreach ( $plans as $plan_id => $plan ) : ?>
            <?php
            $schedule = APD_Payment_Plans::build_schedule( $plan, $price );

            if ( empty( $schedule ) ) {
                continue;
            }
            ?>
        <label class="apd-plan-radio-option">
            <input
                type="radio"
                name="apd_plan_choice"
                value="<?php echo esc_attr( $plan_id ); ?>"
                data-payment-type="deposit"
                data-plan-id="<?php echo esc_attr( $plan_id ); ?>"
                <?php checked( ! $show_standard_deposit && (string) $plan_id === $default_plan_id ); ?>
            />
            <div class="apd-plan-radio-content">
                <span class="apd-plan-radio-circle"></span>
                <div class="apd-plan-radio-text">
                    <span class="apd-plan-radio-heading">
                        <span class="apd-plan-radio-title"><?php echo esc_html( $plan['name'] ); ?></span>
                        <span class="apd-plan-radio-toggle" aria-hidden="true"></span>
                    </span>
                    <?php if ( ! empty( $plan['description'] ) ) : ?>
                    <span class="apd-plan-radio-desc"><?php echo esc_html( $plan['description'] ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="apd-plan-schedule-preview">
                <?php foreach ( $schedule as $schedule_item ) : ?>
                <div class="apd-plan-schedule-item">
                    <span class="apd-plan-item-amount">
                        <?php echo wc_price( $schedule_item['amount'] ); ?>
                        <small>(<?php echo esc_html( $schedule_item['percentage'] ); ?>%)</small>
                    </span>
                    <span class="apd-plan-item-due"><?php echo esc_html( $schedule_item['due_label'] ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </label>
        <?php endforeach; ?>

        <?php if ( $allow_full ) : ?>
        <label class="apd-plan-radio-option">
            <input
                type="radio"
                name="apd_plan_choice"
                value="full"
                data-payment-type="full"
                data-plan-id=""
            />
            <div class="apd-plan-radio-content">
                <span class="apd-plan-radio-circle"></span>
                <div class="apd-plan-radio-text">
                    <span class="apd-plan-radio-title"><?php echo wp_kses_post( $full_text ); ?></span>
                    <span class="apd-plan-radio-desc"><?php esc_html_e( 'Pay the complete order amount now.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                </div>
            </div>
        </label>
        <?php endif; ?>
    </div>
</div>

<script>
(function($){
    function syncDepositSelection() {
        var selected = $('input[name="apd_plan_choice"]:checked');
        var paymentType = selected.data('payment-type') || 'deposit';
        var planId = selected.data('plan-id') || '';

        $('#apd_payment_type').val(paymentType);
        $('#apd_selected_plan').val(planId);
    }

    $(document).on('change', 'input[name="apd_plan_choice"]', syncDepositSelection);
    syncDepositSelection();
})(jQuery);
</script>
