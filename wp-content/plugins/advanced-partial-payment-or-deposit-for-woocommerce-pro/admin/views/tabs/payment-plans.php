<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$plans   = APD_Payment_Plans::get_plans();
$editing = isset( $_GET['edit_plan'] ) ? sanitize_text_field( wp_unslash( $_GET['edit_plan'] ) ) : '';
$edit_plan = $editing && isset( $plans[ $editing ] ) ? $plans[ $editing ] : null;
?>
<div class="apd-tab-header">
    <h2><?php esc_html_e( 'Payment Plans', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h2>
    <p><?php esc_html_e( 'Create and manage installment payment plans. Customers can choose a plan when paying a deposit.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
</div>

<!-- ============ Plans List ============ -->
<div id="apd-plans-list" class="apd-card" <?php echo $edit_plan ? 'style="display:none;"' : ''; ?>>
    <div class="apd-card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3><?php esc_html_e( 'All Plans', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3>
        <button type="button" class="apd-btn apd-btn-primary" id="apd-add-new-plan">
            <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
        </button>
    </div>
    <div class="apd-card-body">
        <?php if ( ! empty( $plans ) ) : ?>
        <table class="apd-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Plan Name', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                    <th><?php esc_html_e( 'Installments', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $plans as $pid => $plan ) :
                    $total_pct = 0;
                    foreach ( $plan['installments'] as $inst ) {
                        $total_pct += floatval( $inst['amount'] );
                    }
                ?>
                <tr data-plan-id="<?php echo esc_attr( $pid ); ?>">
                    <td>
                        <strong><?php echo esc_html( $plan['name'] ); ?></strong>
                        <?php if ( ! empty( $plan['description'] ) ) : ?>
                        <br><small style="color:#94a3b8;"><?php echo esc_html( $plan['description'] ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="apd-badge"><?php echo esc_html( ucfirst( $plan['price_type'] ) ); ?></span></td>
                    <td>
                        <?php echo count( $plan['installments'] ); ?> <?php esc_html_e( 'installments', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                        <br><small style="color:#94a3b8;"><?php esc_html_e( 'Total:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?> <?php echo esc_html( $total_pct ); ?><?php echo $plan['price_type'] === 'percentage' ? '%' : ' ' . get_woocommerce_currency_symbol(); ?></small>
                    </td>
                    <td>
                        <?php if ( ( $plan['status'] ?? 'active' ) === 'active' ) : ?>
                            <span class="apd-status-dot apd-status-active"></span><?php esc_html_e( 'Active', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                        <?php else : ?>
                            <span class="apd-status-dot apd-status-inactive"></span><?php esc_html_e( 'Draft', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="apd-btn apd-btn-small apd-edit-plan" data-plan-id="<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Edit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></button>
                        <button type="button" class="apd-btn apd-btn-small apd-btn-danger apd-delete-plan" data-plan-id="<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Delete', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="apd-empty-state">
            <span class="dashicons dashicons-calendar-alt"></span>
            <p><?php esc_html_e( 'No payment plans yet. Click "Add New Plan" to create your first plan.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============ Plan Builder (Create / Edit) ============ -->
<div id="apd-plan-builder" class="apd-card" style="<?php echo $edit_plan ? '' : 'display:none;'; ?>">
    <div class="apd-card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 id="apd-builder-title"><?php echo $edit_plan ? esc_html__( 'Edit Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) : esc_html__( 'Add New Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3>
        <button type="button" class="apd-btn apd-btn-small" id="apd-back-to-list">
            <span class="dashicons dashicons-arrow-left-alt2"></span> <?php esc_html_e( 'Back to List', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
        </button>
    </div>
    <div class="apd-card-body">
        <form id="apd-plan-form">
            <input type="hidden" id="apd-plan-id" value="<?php echo esc_attr( $editing ); ?>" />

            <!-- Row 1: Name -->
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;"><?php esc_html_e( 'Plan Name', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                <input type="text" id="apd-plan-name" class="apd-input" style="max-width:100%;width:100%;" placeholder="<?php esc_attr_e( 'e.g. 4-Part Payment Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>" value="<?php echo $edit_plan ? esc_attr( $edit_plan['name'] ) : ''; ?>" />
            </div>

            <!-- Row 2: Price Type + Description -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;"><?php esc_html_e( 'Price Type', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <select id="apd-plan-price-type" class="apd-select" style="width:100%;">
                        <option value="percentage" <?php echo $edit_plan && $edit_plan['price_type'] === 'percentage' ? 'selected' : ''; ?>><?php esc_html_e( 'Percentage', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                        <option value="fixed" <?php echo $edit_plan && $edit_plan['price_type'] === 'fixed' ? 'selected' : ''; ?>><?php esc_html_e( 'Fixed Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;"><?php esc_html_e( 'Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                    <select id="apd-plan-status" class="apd-select" style="width:100%;">
                        <option value="active" <?php echo $edit_plan && ( $edit_plan['status'] ?? 'active' ) === 'active' ? 'selected' : ''; ?>><?php esc_html_e( 'Active', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                        <option value="draft" <?php echo $edit_plan && ( $edit_plan['status'] ?? '' ) === 'draft' ? 'selected' : ''; ?>><?php esc_html_e( 'Draft', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;"><?php esc_html_e( 'Description (optional)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                <textarea id="apd-plan-description" class="apd-input" style="max-width:100%;width:100%;min-height:60px;resize:vertical;"><?php echo $edit_plan ? esc_textarea( $edit_plan['description'] ?? '' ) : ''; ?></textarea>
            </div>

            <!-- ============ Installment Rules ============ -->
            <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;margin-bottom:16px;">
                <div style="display:grid;grid-template-columns:130px 1fr 50px;background:#f8fafc;padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">
                    <span><?php esc_html_e( 'Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                    <span><?php esc_html_e( 'Due Date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                    <span></span>
                </div>

                <div id="apd-installments-container">
                    <?php
                    if ( $edit_plan && ! empty( $edit_plan['installments'] ) ) {
                        foreach ( $edit_plan['installments'] as $idx => $inst ) {
                            self::render_installment_row( $idx, $inst, $edit_plan['price_type'] );
                        }
                    }
                    ?>
                </div>

                <!-- Footer: Total + Add Rule -->
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;">
                    <span style="font-size:14px;font-weight:700;color:#1e293b;">
                        <?php esc_html_e( 'Total:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                        <span id="apd-total-amount">
                            <?php
                            if ( $edit_plan ) {
                                $t = 0;
                                foreach ( $edit_plan['installments'] as $i ) $t += floatval( $i['amount'] );
                                echo esc_html( $t );
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                        <span id="apd-total-suffix"><?php echo $edit_plan && $edit_plan['price_type'] === 'fixed' ? esc_html( get_woocommerce_currency_symbol() ) : '%'; ?></span>
                    </span>
                    <button type="button" class="apd-btn apd-btn-primary apd-btn-small" id="apd-add-installment">
                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Rule', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                    </button>
                </div>
            </div>

            <div class="apd-form-actions">
                <button type="submit" class="apd-btn apd-btn-primary" id="apd-save-plan">
                    <span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============ Installment Row Template (hidden) ============ -->
<script type="text/html" id="tmpl-apd-installment-row">
<div class="apd-installment-row" data-index="{{data.index}}">
    <div class="apd-inst-amount">
        <div class="apd-input-group" style="max-width:130px;">
            <input type="number" name="installments[{{data.index}}][amount]" class="apd-input apd-inst-amount-input" value="{{data.amount}}" step="0.01" min="0" placeholder="0" />
            <span class="apd-input-suffix apd-inst-suffix">{{data.suffix}}</span>
        </div>
    </div>
    <div class="apd-inst-due">
        <div class="apd-inst-due-fields">
            <select name="installments[{{data.index}}][due_type]" class="apd-select apd-due-type-select" style="min-width:220px;">
                <option value="immediately"><?php esc_html_e( 'Immediately', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                <option value="after_purchase"><?php esc_html_e( 'Specific duration after purchase', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                <option value="fixed_date"><?php esc_html_e( 'On a fixed date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
            </select>
            <div class="apd-due-extra apd-due-after" style="display:none;">
                <span style="color:#64748b;font-size:13px;font-weight:500;"><?php esc_html_e( 'After', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                <input type="number" name="installments[{{data.index}}][due_after_value]" class="apd-input" style="width:70px;" value="0" min="0" />
                <select name="installments[{{data.index}}][due_after_unit]" class="apd-select" style="min-width:90px;">
                    <option value="day"><?php esc_html_e( 'day(s)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                    <option value="week"><?php esc_html_e( 'week(s)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                    <option value="month"><?php esc_html_e( 'month(s)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                </select>
            </div>
            <div class="apd-due-extra apd-due-fixed" style="display:none;">
                <span style="color:#64748b;font-size:13px;font-weight:500;"><?php esc_html_e( 'Date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                <input type="date" name="installments[{{data.index}}][due_fixed_date]" class="apd-input" style="width:auto;" placeholder="YYYY-MM-DD" />
            </div>
        </div>
    </div>
    <div class="apd-inst-actions">
        <button type="button" class="apd-btn apd-btn-small apd-btn-danger apd-remove-installment" title="<?php esc_attr_e( 'Remove', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>">
            âœ•
        </button>
    </div>
</div>
</script>

<script>
// Hydrate existing rows data for JS
var apdExistingInstallments = <?php echo wp_json_encode( $edit_plan ? $edit_plan['installments'] : array() ); ?>;
var apdPriceType = <?php echo wp_json_encode( $edit_plan ? $edit_plan['price_type'] : 'percentage' ); ?>;
</script>
