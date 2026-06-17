<?php

/**
 * Cálculo do novo ID da encomenda (independente de qualquer outro plugin)
 * @param $order_id
 * @param $new_order
 * @return void
 */
function custom_order_number($order_id, $new_order) {
    static $saving = [];

    // Skip if the order already has a number or if we're already saving it (re-entrance guard)
    if (!empty($new_order->get_meta('_order_number'))) return;
    if (!empty($saving[$order_id])) return;

    $apf = get_option('APF');
    $allowed_categories = $apf['order_number']['order_number_product_categories'] ?? [];
    $number_template    = $apf['order_number']['order_number_definition'] ?? '';

    if (empty($number_template)) return;

    // check if it is allowed to change this order number (by its products' categories)
    $order_number_allowed = empty($allowed_categories); // allow if no category restriction configured
    foreach ($new_order->get_items() as $item) {
        $product = wc_get_product($item->get_product_id());
        if (!$product) continue;

        $intersection = array_intersect($allowed_categories, $product->get_category_ids());
        if (!empty($intersection)) {
            $order_number_allowed = true;
            break;
        }
    }

    if (!$order_number_allowed) return;

    // Build the number from the template
    $orderNumber = '';
    $isShortCode = false;
    $thisShortCode = '';

    foreach (str_split($number_template) as $templateChar) {
        if ($templateChar == '[') {
            $isShortCode = true;
        } else if ($templateChar == ']') {
            $this_short_code = check_short_code($thisShortCode, $new_order);

            // if a required shortcode is empty, abort (incomplete number is not useful)
            if (empty($this_short_code) && $this_short_code !== 0)
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

    if (empty($orderNumber)) return;

    $saving[$order_id] = true;
    $new_order->update_meta_data('_order_number', $orderNumber);
    $new_order->save();
    unset($saving[$order_id]);
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
        $product = wc_get_product($singleItem->get_product_id());
        if (!$product) continue;
        $sku = $product->get_sku();
        if ($sku != '') break;
    }

    return $sku;
}


// region Ligação das funções com os filtros/ações

// woocommerce_new_order: cobre encomendas de checkout (artigos já presentes)
add_action('woocommerce_new_order', 'custom_order_number', 10, 2);

// woocommerce_update_order: cobre encomendas criadas no admin onde artigos são adicionados depois
add_action('woocommerce_update_order', 'custom_order_number', 10, 2);

// endregion