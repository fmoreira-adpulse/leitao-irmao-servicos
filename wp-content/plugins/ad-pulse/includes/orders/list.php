<?php
/**
 * Adição de novas colunas à tabela com a listagem das encomendas
 * @param $columns
 * @return mixed
 */
function add_custom_shop_order_column( $columns ) {
    $columns['store'] = __('Store', 'ad-pulse');
    $columns['service'] = __('Service', 'ad-pulse');
    return $columns;
}

/**
 * População dos dados das novas colunas
 * @param $column_title
 * @param WC_Order $order
 * @return void
 */
function custom_shop_subscription_column($column_title, WC_Order $order) {
    switch($column_title) {
        case 'store':
            echo query_posts(['category_name' => 'stores', 'name' => $order->get_meta('_order_store')])[0]->post_title;
            break;
        case 'service':
            $prodSKU = mb_substr($order->get_meta('_order_number'), 0, 4);
            $serviceName = wc_get_products(['sku' => $prodSKU])[0]->name;
            echo $serviceName;
            break;
    }
}

function listing_order_number($order_number, $order) {
    return !empty($order->get_meta('_order_number'))? $order->get_meta('_order_number') : $order_number;
}

function check_status_by_user_type($order_query) {
    $user = wp_get_current_user();
    $user_roles = $user->roles;
    $all_status = get_posts(['post_type' => 'order_status', 'posts_per_page' => -1]);

    if(!in_array('administrator', $user_roles)) {
        foreach($all_status as $status) {
            $status_meta = get_post_meta($status->ID);
            $allowed_roles = (isset($status_meta['allowed_roles']) && is_array($status_meta['allowed_roles']))? maybe_unserialize($status_meta['allowed_roles'][0]) : null;

            $is_allowed = is_null($allowed_roles) || !empty(array_intersect($user_roles, $allowed_roles)) || in_array(0, $allowed_roles);

            if(!$is_allowed) {
                $status_slug = get_post_meta($status->ID)['status_slug'][0];
                $wc_status_slug = 'wc-' . $status_slug;
                $index = array_search($wc_status_slug, $order_query['status']);
                unset($order_query['status'][$index]);
            }
        }
    }

    return $order_query;
}

function energyplus_allow_search_by_sku() {
    return true;
}

function energyplus_order_search($search_result, $search_text) {
    $search_by_order_number = wc_get_orders([
        'meta_key'     => '_order_number',
        'meta_compare' => 'LIKE',
        'meta_value'   => $search_text
    ]);

    $search_by_nif = wc_get_orders([
        'meta_key'     => '_billing_nif',
        'meta_compare' => 'LIKE',
        'meta_value'   => $search_text
    ]);

    $search_by_sage = wc_get_orders([
        'meta_key'     => '_billing_sage',
        'meta_compare' => 'LIKE',
        'meta_value'   => $search_text
    ]);

    return array_merge($search_result, $search_by_order_number, $search_by_nif, $search_by_sage);
}

/**
 * ✅ FIX: Mostrar número de encomenda (ou #ID como fallback) no dropdown ACF.
 * Sem este fix, ordens sem _order_number têm texto vazio e o Select2
 * mostra "The results could not be loaded."
 */
function customize_ajax_order_search( $title, $post ) {
    $order = wc_get_order( $post->ID );

    if ( ! $order ) {
        return $title;
    }

    $order_number = $order->get_meta( '_order_number' );

    return ! empty( $order_number ) ? $order_number : '#' . $post->ID;
}

function add_alert_to_order($order) {
    $this_user_id = get_current_user_id();
    foreach ($order["meta_data"] as $order_meta) {
        if ($order_meta->key == "_order_alert_for_user_" . $this_user_id && $order_meta->value) {
            echo 'order-alert ';
            break;
        }
    }
}

function wp_admin_order_list_filter($query) {
    global $pagenow, $typenow;
    if (is_admin() && $query->is_main_query() && $typenow === 'shop_order' && $pagenow === 'edit.php') {
        $query->set('post_parent', 0);
    }
}

// =============================================================================
// Hooks
// =============================================================================

add_filter('energyplus_search_orders_by_sku',                          'energyplus_allow_search_by_sku',         10, 0);
add_filter('woocommerce_shop_order_list_table_prepare_items_query_args','check_status_by_user_type',              10, 1);
add_filter('woocommerce_order_number',                                  'listing_order_number',                   10, 2);
add_filter('manage_woocommerce_page_wc-orders_columns',                 'add_custom_shop_order_column');
add_action('woocommerce_shop_order_list_table_custom_column',           'custom_shop_subscription_column',        900, 2);
add_filter('energyplus_order_search',                                   'energyplus_order_search',                PHP_INT_MAX, 2);
add_filter('acf/fields/post_object/result/name=wcsts_associated_order', 'customize_ajax_order_search',           10, 2);
add_action('add_class_to_order_div',                                    'add_alert_to_order',                     10, 1);
add_action('pre_get_posts',                                             'wp_admin_order_list_filter',             10, 1);

// add_action('load-woocommerce_page_wc-orders', 'check_status', 1000, 1);