<?php

function add_user_form_endpoint() {
    register_rest_route(
        'ad-pulse', 
        '/add-user', 
        [
            'methods' => 'POST', 
            'callback' => 'add_user_via_form'
        ]
    );
}

// TODO: fazer uma forma de não alterar a palavra-passe
function add_user_via_form($form_data) {
    $form_params = $form_data->get_body_params();

    // matching user data fields to form fields by priority
    $user_main_data = ['user_pass', 'user_login', 'user_email', 'first_name', 'last_name', 'role', 'locale'];

    // getting the data from the fields
    foreach($user_main_data as $user_field) {
        $user_data[$user_field] = $form_params['fields'][$user_field]['value'];
    }

    // meta data to add
    $user_meta_data = ['sage'];
    $user_meta_sections = ['billing_', 'shipping_'];
    $user_meta_labels = ['first_name', 'last_name', 'company', 'address_1', 'city', 'postcode', 'country', 'email', 'phone', 'nif'];

    // creating the proper label for each field
    foreach ($user_meta_sections as $section) {
        foreach ($user_meta_labels as $label) {
            $user_meta_data[] = $section . $label;
        }
    }

    // getting the metadata from the fields
    foreach($user_meta_data as $user_meta_field) {
        $user_data['meta_input'][$user_meta_field] = $form_params['fields'][$user_meta_field]['value'];
    }

    // check username and set sage number as username if it does not exist
    if (is_null($user_data['user_login'])) {
        $user_data['user_login'] = $form_params['fields']['sage']['value'];
    }

    // check if the user is to be created or updated
    if ($form_params['fields']['user_id']['value'] > 0) {
        // update the user
        $user_data['ID'] = $form_params['fields']['user_id']['value'];

        // if the password field was left empty then leave the password unchanged
        if (empty(trim($user_data['user_pass']))) {
            unset($user_data['user_pass']);
        }

        $user_result = wp_update_user($user_data);
    }
    else {
        // creating the new user with basic and meta data
        $user_result = wp_insert_user($user_data);
    }

    error_log("Aqui está o resultado do add_user_via_form: ");
    error_log(json_encode($user_result));
    return $user_result;
}

function check_if_is_admin() {
    echo json_encode(current_user_can('administrator'));
    wp_die();
}

function http_request_timeout($http_args, $url) {
    $http_args['timeout'] = 100;
    return $http_args;
}

function handle_form_response($response, $record) {
    if ($response['response']['code'] == 500) {
        wp_send_json_error( ['message' => array_key_exists('body', $response)? json_decode($response['body'], true)['message'] : '']);
    } else if (200 <= $response['response']['code'] && $response['response']['code'] < 300) {
        wp_send_json_success(['message' => 'Utilizador criado com sucesso']);
    }
}

// auto fill 
function autofill_custom_billing_data($data, $customer, $user_id) {
    // Get your custom data - this could be from user meta or another source
    $custom_data = get_user_meta($user_id, 'sage', true);
    
    // Add to billing data
    $data['billing']['sage'] = $custom_data;
    
    return $data;
}

// edit user redirect
function redirect_update_user_form() {
    // Get the user ID being edited
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $edited_user = get_user_by('ID', $user_id);

    // Optional: Restrict to certain roles or conditions
    if (current_user_can('edit_user', $user_id) && $edited_user && in_array('customer', $edited_user->roles)) {
        // Build custom destination URL
        $redirect_url = site_url('formulario-de-user?energyplus_hide&page=my_custom_user_editor&user_id=' . $user_id);

        // Redirect and exit
        wp_redirect($redirect_url);
        exit;
    }
}

add_action('load-user-edit.php', 'redirect_update_user_form');
add_action( 'elementor_pro/forms/webhooks/response', 'handle_form_response', 10, 2);
add_filter( 'http_request_args', 'http_request_timeout', 10, 2);
add_action('rest_api_init', 'add_user_form_endpoint');
add_action('wp_ajax_is_user_admin', 'check_if_is_admin');
add_filter('woocommerce_ajax_get_customer_details', 'autofill_custom_billing_data', 10, 3);