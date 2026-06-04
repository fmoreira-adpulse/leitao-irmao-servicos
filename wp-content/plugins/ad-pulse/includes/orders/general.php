<?php
function get_order_from_post($post) {
    if ($post instanceof WC_Order)
        return $post;
    else if ($post instanceof WP_Post) {
        $order = wc_get_order($post->ID);
        if ($order instanceof WC_Order)
            return $order;
    }

    return false;
}

function remove_shop_order_meta_boxes() {
    remove_meta_box( 'postcustom', 'shop_order', 'normal' );
    remove_meta_box( 'woocommerce-order-downloads', 'shop_order', 'normal' );
}

add_action( 'add_meta_boxes', 'remove_shop_order_meta_boxes', 90);