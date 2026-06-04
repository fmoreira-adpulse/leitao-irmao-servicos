<?php

/**
 * Cálculo do novo ID da encomenda (independente de qualquer outro plugin)
 * TODO: fazer com que o prefixo/sufixo seja dinâmico
 * @param $order_id
 * @param $new_order
 * @return void
 */
function custom_order_number($order_id, $new_order) {

    // check if it is allowed to change this order number (by its products' categories)
    $allowed_categories = get_option('APF')['order_number']['order_number_product_categories'];
    $order_number_allowed = false;
    foreach ($new_order->items as $item) {
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id);
        $product_category_ids = $product->get_category_ids();

        $intersection = array_intersect($allowed_categories ?? [], $product_category_ids);
        if (!empty($intersection)) {
            $order_number_allowed = true;
            break;
        }
    }

    // set order number if allowed
    if ($order_number_allowed) {
        $numberTemplate = get_option('APF')['order_number']['order_number_definition'];
        $orderNumber = '';
        $isShortCode = false;
        $thisShortCode = '';

        foreach(str_split($numberTemplate) as $templateChar) {
            if ($templateChar == '[') {
                $isShortCode = true;
            } else if ($templateChar == ']') {
                $this_short_code = check_short_code($thisShortCode, $new_order);
                
                // if the short code is empty, do not add anything to the order number
                if (empty($this_short_code))
                    return;

                $orderNumber .= $this_short_code;
                $thisShortCode = '';
                $isShortCode = false;
            } else if ($isShortCode) {
                $thisShortCode .= $templateChar;
            } else {
                $orderNumber .= $templateChar;
            }
        }

        $new_order->add_meta_data('_order_number', $orderNumber);
        $new_order->save();
    }
}

function check_short_code ($short_code, WC_Order $new_order): bool|int|string
{
    return match ($short_code) {
        'sku' => get_sku($new_order),
        'store' => mb_strtoupper($new_order->get_meta('_order_custom_store')),
        'seq' => $new_order->get_id(),
        default => date($short_code),
    };
}

function get_sku($newOrder): bool|string
{
    $sku = false;

    foreach($newOrder->get_items() as $singleItem) {
        $sku = wc_get_product($singleItem->get_product_id())->get_sku();
        if($sku != '') {
            break;
        }
    }

    return $sku;
}


// region Ligação das funções com os filtros/ações

add_action('woocommerce_new_order', 'custom_order_number', 10, 2);

// endregion