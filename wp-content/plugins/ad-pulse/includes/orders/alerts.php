<?php

function set_alert_on_order($order) {
    static $saving = [];

    if ($order->get_parent_id() > 0)
        return;

    $order_id = $order->get_id();
    if (!empty($saving[$order_id])) return;

    $user_id_who_changed = get_current_user_id();
    $order_alert_prefix = '_order_alert_for_user_';

    foreach ($order->get_meta_data() as $meta) {
        if (str_contains($meta->key, $order_alert_prefix)) {
            $has_alert = $meta->key != $order_alert_prefix . $user_id_who_changed;
            $user_id_to_be_notified = str_replace($order_alert_prefix, '', $meta->key);
            $order->update_meta_data('_order_alert_for_user_' . $user_id_to_be_notified, $has_alert);
        }
    }
    
    $order->update_meta_data($order_alert_prefix . $user_id_who_changed, false);
    $saving[$order_id] = true;
    $order->save();
    unset($saving[$order_id]);
}

function remove_alert_from_order() {
    $order = wc_get_order($_POST["order_id"]);
    $user_id_who_clicked = get_current_user_id();
    $order_alert_prefix = '_order_alert_for_user_';

    foreach ($order->get_meta_data() as $meta) {
        if (str_contains($meta->key, $order_alert_prefix) && str_replace($order_alert_prefix, '', $meta->key) == $user_id_who_clicked) {
            $order->update_meta_data($meta->key, false);
            $order->save();
            break;
        }
    }
}

function is_item_metadata_changed($order, $items) {
    foreach ($items['meta_key'] as $item_id => $meta_keys) {
        foreach ($meta_keys as $meta_id => $meta_key) {
            // check if the metadata was created or deleted
            if (empty($meta_key) || str_contains($meta_key, 'new'))
                return true;
            else {
                // check if the metadata was updated
                $meta_data_array = $order->get_item($item_id)->get_meta_data();
                foreach ($meta_data_array as $meta_data) {
                    if ($meta_data->id == $meta_id) {
                        if ($meta_data->key != $meta_key || $items['meta_value'][$item_id][$meta_id] != $meta_data->value)
                            return true;
                    }
                }
            }
        }
    }

    return false;
}

function did_items_prices_changed($order, $items) {
    $prices = ['line_subtotal', 'line_total'];
    foreach ($prices as $price_slug) {
        foreach ($items[$price_slug] as $item_id => $price) {
            $order_item_price_slug = str_replace('line_', '', $price_slug);
            if ($price != $order->get_item($item_id)->get_data()[$order_item_price_slug])
                return true;
        }
    }

    return false;
}

function set_alert_on_order_when_order_item_meta_has_changed($order_id, $items) {
    $order = wc_get_order($order_id);

    if (is_item_metadata_changed($order, $items) || did_items_prices_changed($order, $items))
        set_alert_on_order($order);
}

function set_alert_on_new_order_item( $item_id, $item, $order_id ) {
    set_alert_on_order(wc_get_order($order_id));
}

function set_alert_on_deleted_order_item($item_id) {
    $order_item = WC_Order_Factory::get_order_item($item_id);
    set_alert_on_order(wc_get_order($order_item->get_order_id()));
}

function get_old_order( $order ) {
    if (($order->get_parent_id() > 0))
        return;
    
    $old_order = wc_get_order( $order->get_id() );
    if ( $old_order ) {
        $GLOBALS['old_order_state_' . $order->get_id()] = $old_order;
    }
}

function compare_old_with_new_order( $order ) {
    $order_id = $order->get_id();

    if ( isset( $GLOBALS['old_order_state_' . $order_id] ) ) {
        $old_order = $GLOBALS['old_order_state_' . $order_id];

        $diff = [];

        // 1️⃣ Comparar status
        if ( $old_order->get_status() !== $order->get_status() ) {
            $diff['status'] = [
                'old' => $old_order->get_status(),
                'new' => $order->get_status(),
            ];
        }

        // 2️⃣ Comparar valores principais
        $fields = [
            'total'        => 'get_total',
            'customer_id'  => 'get_customer_id',
            'billing_email'=> 'get_billing_email',
            'billing_name' => 'get_formatted_billing_full_name',
            'shipping_name'=> 'get_formatted_shipping_full_name',
        ];

        foreach ( $fields as $label => $method ) {
            if ( method_exists( $old_order, $method ) && method_exists( $order, $method ) ) {
                $old_val = $old_order->$method();
                $new_val = $order->$method();
                if ( $old_val != $new_val ) {
                    $diff[$label] = [
                        'old' => $old_val,
                        'new' => $new_val,
                    ];
                }
            }
        }

        // 3️⃣ Comparar metadados
        $all_keys = array_unique( array_merge(
            array_map( fn($m) => $m->key, $old_order->get_meta_data() ),
            array_map( fn($m) => $m->key, $order->get_meta_data() )
        ));

        foreach ( $all_keys as $key ) {
            if (!str_starts_with($key, "_order_alert")) {
                $old_val = $old_order->get_meta( $key );
                $new_val = $order->get_meta( $key );
                if ( $old_val !== $new_val ) {
                    $diff["meta:$key"] = [
                        'old' => $old_val,
                        'new' => $new_val,
                    ];
                }
            }
        }

        // 4️⃣ Resultado final
        if ( ! empty( $diff ) ) {
            set_alert_on_order($order);
        }

        // limpar global
        unset( $GLOBALS['old_order_state_' . $order_id] );
    }
}

#region Hooks

add_action( 'woocommerce_before_order_object_save', 'get_old_order', 10, 1 );
add_action( 'woocommerce_after_order_object_save', 'compare_old_with_new_order', 10, 1 );
add_action( 'woocommerce_before_save_order_items', 'set_alert_on_order_when_order_item_meta_has_changed', 10, 2 );
add_action( 'woocommerce_new_order_item', 'set_alert_on_new_order_item', 10, 3 );
add_action( 'woocommerce_before_delete_order_item', 'set_alert_on_deleted_order_item', 10, 1 );
add_action( 'wp_ajax_remove_alert', 'remove_alert_from_order' );

#endregion