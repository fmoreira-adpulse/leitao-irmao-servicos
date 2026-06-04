<?php

function get_string_to_replace(string $order_number, array $meta): array {
    $replacing = [
        'old' => 'Deposit',
        'new' => [
            'VAP' => ['9995', '9996', '9997', 'S9998'],
            'PEQREP' => ['9994', '9999']
        ]
    ];

    $changed = false;

    foreach ($meta as &$meta_value) {
        if (key_exists('title', $meta_value) && $meta_value['title'] == $replacing['old']) {
            foreach ($replacing['new'] as $string => $skus) {
                foreach ($skus as $sku) {
                    if (str_contains($order_number, $sku)) {
                        $meta_value['title'] = $string;
                        $changed = true;
                    }
                }
            }        
        }
    }

    return ['new_value' => $meta, 'changed' => $changed];
}

function replace_deposit_string ($meta_id, $post_id, $meta_key, $meta_value) {
    $order_post_type = 'shop_order';
    $target_meta_key = '_mepp_payment_schedule';

    if (get_post_type($post_id) == $order_post_type && $meta_key == $target_meta_key) {
        // Meta adicionada à encomenda
        $order = wc_get_order($post_id);
        $order_number = $order->get_meta('_order_number', true);
        $new_info = get_string_to_replace($order_number, $meta_value);

        if ($new_info['changed']) {
            $order->update_meta_data($meta_key, $new_info['new_value']);
            $order->save();
        }
    }

    return;
}

// add_action('added_post_meta', 'replace_deposit_string', 10, 4);