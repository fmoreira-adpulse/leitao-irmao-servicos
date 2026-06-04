<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('apd_pro_license_error_code')) {
    function apd_pro_license_error_code($license_data, $item_name = 'this Plugin') {
        switch ($license_data->error) {
            case 'expired':
                $message = sprintf(
                    __('Your license key expired on %s.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'),
                    wp_date(get_option('date_format'), strtotime($license_data->expires))
                );
                break;
            case 'revoked':
                $message = __('Your license key has been disabled.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro');
                break;
            case 'missing':
                $message = __('Invalid license.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro');
                break;
            case 'invalid':
            case 'site_inactive':
                $message = __('Your license is not active for this URL.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro');
                break;
            case 'item_name_mismatch':
                $message = sprintf(__('This appears to be an invalid license key for %s.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'), $item_name);
                break;
            case 'no_activations_left':
                $message = __('Your license key has reached its activation limit.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro');
                break;
            default:
                $message = __('An error occurred, please try again.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro');
                break;
        }
        return $message;
    }
}

add_action('wp_ajax_apd_pro_ajax_license_activate', 'apd_pro_ajax_license_activate');
if (!function_exists('apd_pro_ajax_license_activate')) {
    function apd_pro_ajax_license_activate() {
        check_ajax_referer('apd_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'));
        }

        $license              = sanitize_text_field($_REQUEST['key']);
        $key_option_name      = 'apd_pro_license_key';
        $status_option_name   = 'apd_pro_license_status';
        $expire_option_name   = 'apd_pro_license_expire';
        $order_id_option_name = 'apd_pro_license_order_id';
        $item_name            = APD_PRO_NAME;
        $item_id              = APD_PRO_ID;

        // data to send in our API request
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_id'    => $item_id,
            'url'        => home_url()
        );

        // Call the custom API.
        $response     = wp_remote_post(MEP_STORE_URL, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));
        
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            $message = (is_wp_error($response) && !empty($response->get_error_message())) ? $response->get_error_message() : __('An error occurred, please try again.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro');
            wp_send_json_error($message);
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));
            
            if (false === $license_data->success) {
                $message = apd_pro_license_error_code($license_data, $item_name);
                update_option($key_option_name, '');
                update_option($expire_option_name, '');
                update_option($order_id_option_name, '');  
                update_option($status_option_name, 'invalid');
                wp_send_json_error($message);
            } else {
                $payment_id = $license_data->payment_id;
                $expire = $license_data->expires;
                $message = sprintf(__("Success! License Key is valid. Your Order ID is %s. Validity of this license is %s.", "advanced-partial-payment-or-deposit-for-woocommerce-pro"), $payment_id, $expire);
                
                update_option($key_option_name, $license);
                update_option($expire_option_name, $license_data->expires);
                update_option($order_id_option_name, $license_data->payment_id);            
                update_option($status_option_name, $license_data->license);
                wp_send_json_success($message);
            }
        }
    }
}

add_action('wp_ajax_apd_pro_ajax_license_deactivate', 'apd_pro_ajax_license_deactivate');
if (!function_exists('apd_pro_ajax_license_deactivate')) {
    function apd_pro_ajax_license_deactivate() {
        check_ajax_referer('apd_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'));
        }

        $license              = get_option('apd_pro_license_key');
        $key_option_name      = 'apd_pro_license_key';
        $status_option_name   = 'apd_pro_license_status';
        $expire_option_name   = 'apd_pro_license_expire';
        $order_id_option_name = 'apd_pro_license_order_id';
        $item_id              = APD_PRO_ID;

        // data to send in our API request
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $license,
            'item_id'    => $item_id,
            'url'        => home_url()
        );

        // Call the custom API.
        $response = wp_remote_post(MEP_STORE_URL, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            $message = (is_wp_error($response) && !empty($response->get_error_message())) ? $response->get_error_message() : __('An error occurred, please try again.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro');
            wp_send_json_error($message);
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));
            
            update_option($key_option_name, '');
            update_option($expire_option_name, '');
            update_option($order_id_option_name, ''); 
            update_option($status_option_name, 'invalid');
            
            wp_send_json_success(__('License deactivated successfully.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro'));
        }
    }
}