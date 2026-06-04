<?php

/**
 * Pesquisa por NIF e por número de SAGE
 */
function search_customers($query, $term, $limit, $query_type) {
    if ($query_type == 'meta_query') {
        $query['meta_query'] = array_merge(
            $query['meta_query'], 
            [
                [
                    'key'     => 'billing_nif',
                    'value'   => $term,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => 'sage',
                    'value'   => $term,
                    'compare' => 'LIKE'
                ]
            ]
        );
    }

    return $query;
}

function searchbox_customers_in_order_detail_page($customers) {
    foreach ($customers as $id => &$label) {
        $user = get_user_by('id', $id);
        if ($user) {
            $sage = get_user_meta($id, 'sage', true);
            $label .= $sage ? " - Nº SAGE: $sage" : '';
        }
    }
    return $customers;
}

function handle_get_user_info() {
    // Check for required parameter
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if (!$user_id || !current_user_can('edit_users')) {
        wp_send_json_error(['message' => 'Invalid permissions or user ID']);
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        wp_send_json_error(['message' => 'User not found']);
    }

    // Example user data
    $data = array(
        'ID'           => $user->ID,
        'username'     => $user->user_login,
        'email'        => $user->user_email,
        'display_name' => $user->display_name,
        'role'         => $user->roles[0],
        'meta'         => get_user_meta($user->ID), // Optional: full meta array
    );

    wp_send_json_success($data);
}


add_action('wp_ajax_get_user_info', 'handle_get_user_info');
add_filter('woocommerce_json_search_found_customers', 'searchbox_customers_in_order_detail_page', 10, 1);
add_filter('woocommerce_customer_search_customers', 'search_customers', 10, 4);