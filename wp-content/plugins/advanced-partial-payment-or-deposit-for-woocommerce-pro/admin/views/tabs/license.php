<?php if ( ! defined( 'ABSPATH' ) ) exit; 
$license_key = get_option('apd_pro_license_key', '');
$status      = get_option('apd_pro_license_status', 'invalid');
$expire      = get_option('apd_pro_license_expire', '');
$order_id    = get_option('apd_pro_license_order_id', '');

$is_active   = ($status === 'valid');
?>
<div class="apd-tab-header">
    <h2><?php esc_html_e( 'License', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h2>
    <p><?php esc_html_e( 'Manage your pro license key for updates and support.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
</div>

<div class="apd-card">
    <div class="apd-card-header"><h3><?php esc_html_e( 'License Details', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3></div>
    <div class="apd-card-body">
        
        <div class="apd-field-row">
            <div class="apd-field-label">
                <label><?php esc_html_e( 'Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
            </div>
            <div class="apd-field-input">
                <?php if ($is_active) : ?>
                    <span class="apd-status-badge apd-status-complete" style="background-color: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;"><?php esc_html_e( 'Active', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                <?php else : ?>
                    <span class="apd-status-badge apd-status-failed" style="background-color: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;"><?php esc_html_e( 'Inactive', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_active && $expire) : ?>
        <div class="apd-field-row">
            <div class="apd-field-label">
                <label><?php esc_html_e( 'Expires On', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
            </div>
            <div class="apd-field-input">
                <strong><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $expire ) ) ); ?></strong>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($is_active && $order_id) : ?>
        <div class="apd-field-row">
            <div class="apd-field-label">
                <label><?php esc_html_e( 'Order ID', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
            </div>
            <div class="apd-field-input">
                <strong><?php echo esc_html( $order_id ); ?></strong>
            </div>
        </div>
        <?php endif; ?>

        <div class="apd-field-row" style="border-bottom:none;">
            <div class="apd-field-label">
                <label><?php esc_html_e( 'Enter License Key', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                <p class="apd-field-desc"><?php esc_html_e( 'Enter the license key you received after purchase.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
            </div>
            <div class="apd-field-input">
                <div style="display:flex; gap: 10px;">
                    <input type="text" id="apd-license-key" class="apd-input" placeholder="XXXX-XXXX-XXXX-XXXX" value="<?php echo esc_attr( $license_key ); ?>" <?php echo $is_active ? 'readonly' : ''; ?> style="max-width: 300px;" />
                    
                    <?php if ($is_active) : ?>
                        <button type="button" id="apd-deactivate-license" class="apd-btn" style="background: #ef4444; color: white; border: none; padding: 0 16px; border-radius: 4px; cursor: pointer;"><?php esc_html_e('Deactivate', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?></button>
                    <?php else : ?>
                        <button type="button" id="apd-activate-license" class="apd-btn apd-btn-primary" style="padding: 0 16px; border-radius: 4px; cursor: pointer;"><?php esc_html_e('Activate', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    if (typeof apd_admin === 'undefined' || !apd_admin.ajax_url || !apd_admin.nonce) {
        return;
    }
    var ajaxurl = apd_admin.ajax_url;
    var nonce = apd_admin.nonce;

    $('#apd-activate-license').on('click', function() {
        var key = $('#apd-license-key').val();
        var $btn = $(this);
        
        if (!key) {
            alert('<?php esc_html_e('Please enter the license key', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');
            return false;
        }

        $btn.prop('disabled', true).text('<?php esc_html_e('Activating...', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                "action": "apd_pro_ajax_license_activate",
                "nonce": nonce,
                "key": key
            },
            success: function(response) {
                if (response.success) {
                    // We can use APD_Admin toast if available, or fallback to alert
                    if (typeof APD_Admin !== 'undefined' && APD_Admin.showToast) {
                        APD_Admin.showToast(response.data, 'success');
                    } else {
                        alert(response.data);
                    }
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    if (typeof APD_Admin !== 'undefined' && APD_Admin.showToast) {
                        APD_Admin.showToast(response.data, 'error');
                    } else {
                        alert(response.data);
                    }
                    $btn.prop('disabled', false).text('<?php esc_html_e('Activate', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred. Please try again.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');
                $btn.prop('disabled', false).text('<?php esc_html_e('Activate', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');
            }
        });
    });

    $('#apd-deactivate-license').on('click', function() {
        var $btn = $(this);
        
        if (!confirm('<?php esc_html_e('Are you sure you want to deactivate this license?', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>')) {
            return false;
        }

        $btn.prop('disabled', true).text('<?php esc_html_e('Deactivating...', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                "action": "apd_pro_ajax_license_deactivate",
                "nonce": nonce
            },
            success: function(response) {
                if (response.success) {
                    if (typeof APD_Admin !== 'undefined' && APD_Admin.showToast) {
                        APD_Admin.showToast(response.data, 'success');
                    } else {
                        alert(response.data);
                    }
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    if (typeof APD_Admin !== 'undefined' && APD_Admin.showToast) {
                        APD_Admin.showToast(response.data, 'error');
                    } else {
                        alert(response.data);
                    }
                    $btn.prop('disabled', false).text('<?php esc_html_e('Deactivate', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred. Please try again.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');
                $btn.prop('disabled', false).text('<?php esc_html_e('Deactivate', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'); ?>');
            }
        });
    });
});
</script>
