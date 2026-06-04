<?php

function product_search_by_categories( $products ) {
    // Só no admin + chamadas AJAX do WooCommerce.
    if (is_admin() && wp_doing_ajax()) {
        // Garante que só apanha a pesquisa de produtos (inclui variações) usada no admin.
        $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
        if ( in_array( $action, array( 'woocommerce_json_search_products', 'woocommerce_json_search_products_and_variations' ), true ) ) {

            $categories_allowed = get_option('APF')['order_admin_page_settings']['product_categories_in_order_item_search'];
            $restricted_roles = get_option('APF')['order_admin_page_settings']['product_search_restricted_user_roles'];

            $user  = wp_get_current_user();
            $current_user_roles = (array) $user->roles; 

            // Only restrict if there is at least one role in the intersection
            $roles_intersection = array_intersect($restricted_roles, $current_user_roles);
            if (!empty($roles_intersection)) {
                foreach ( $products as $id => $label ) {
                    // Se vier uma variação, verifica a categoria no produto "mãe".
                    $check_id = (int) $id;
                    $wc_product = wc_get_product( $check_id );
                    if ($wc_product && $wc_product->is_type( 'variation' ))
                        $check_id = (int) $wc_product->get_parent_id();

                    // Se não estiver em nenhuma das categorias permitidas, remove.
                    if (!has_term( $categories_allowed, 'product_cat', $check_id))
                        unset( $products[ $id ] );
                }
            }

        }
    }

    return $products;
}

add_filter( 'woocommerce_json_search_found_products', 'product_search_by_categories', 10, 1);