<?php

// A lógica foi alterada e o funcionamento disto já não faz sentido
function check_payment_for_statuses($order_id, $old_status, $new_status, $order)
{
    // if ($old_status != $new_status) {
    //     check_if_old_status_has_been_paid($order, $old_status);
    //     check_if_new_status_has_payment($order, $new_status);
    // }
}

function check_if_old_status_has_been_paid($order, $old_status)
{
    // get the old status metadata
    $status_meta = get_status_meta($old_status);

    // check if payment is set in the old status
    if (is_array($status_meta)) {
        $payment_value = get_payment_value($status_meta, $order);
        if ($payment_value != false) {
            $child_orders = get_child_orders_by_parent_order($order->ID);
            foreach ($child_orders as $child_order) {
                if ($child_order->created_via == $old_status && $child_order->date_paid == null) {
                    // it is not possible to advance from this status while it is pending
                    $order->update_status('wc-' . $old_status);
                    $order->save();
                }
            }
        }
    }
}

function check_if_new_status_has_payment($order, $new_status)
{
    // get the new status metadata
    $status_meta = get_status_meta($new_status);

    // check if payment is set in the new status
    if (is_array($status_meta) && $order instanceof WC_Order) {
        $payment_value = get_payment_value($status_meta, $order);
        if ($payment_value != false) {
            $child_orders = get_child_orders_by_parent_order($order->get_id());
            foreach ($child_orders as $child_order) {
                if ($child_order->get_created_via() == $new_status)
                    return;
            }
            
            // create sub-order if no child order was found with this order already
            create_partial_payment_order($order->get_id(), $new_status, $payment_value);
        }
    }

    return;
}

// checks if status has payment set and if so returns the minimum payment percentage
function get_payment_value(array $status_meta, WC_Order $order)
{
    // check if payment meta data is set
    $payment_vars = ['status_has_payment', 'status_payment_is_percentage', 'minimum_payment_percentage', 'minimum_absolute_payment'];
    $payment_value = 0;

    $payment_vars_are_set = true;
    foreach($payment_vars as $payment_var_name) {
        if (!array_key_exists($payment_var_name, $status_meta))
            $payment_vars_are_set = false;
    }

    $status_has_payment = false;
    if ($payment_vars_are_set) {
        $payment_is_set = $status_meta['status_has_payment'][0] == '1';

        if ($payment_is_set) {
            $payment_is_percentage = $status_meta['status_payment_is_percentage'][0] == '1';

            if($payment_is_percentage) {
                $percentage = floatval($status_meta['minimum_payment_percentage'][0]);
                $payment_value = round($order->get_total() * ($percentage / 100), 2);
            }
            else
                $payment_value = floatval($status_meta['minimum_absolute_payment'][0]);
        }

        $status_has_payment = $payment_is_set && $payment_value > 0;
    }

    return $status_has_payment ? $payment_value : false;
}

function create_order_fee(WC_Order &$order, float $imported_total_fee)
{
    // Get the customer country code
    $country_code = $order->get_shipping_country();

    // Set the array for tax calculations
    $calculate_tax_for = array(
        'country' => $country_code, 
        'state' => '', 
        'postcode' => '', 
        'city' => ''
    );

    // Get a new instance of the WC_Order_Item_Fee Object
    $item_fee = new WC_Order_Item_Fee();

    $item_fee->set_name( "Fee" ); // Generic fee name
    $item_fee->set_amount( $imported_total_fee ); // Fee amount
    $item_fee->set_tax_class( '' ); // default for ''
    $item_fee->set_tax_status( 'taxable' ); // or 'none'
    $item_fee->set_total( $imported_total_fee ); // Fee amount

    // Calculating Fee taxes
    $item_fee->calculate_taxes( $calculate_tax_for );

    // Add Fee item to the order
    $order->add_item( $item_fee );
}

function set_sub_order_paid() {
    $sub_order = wc_get_order($_POST['order_id']);
    if ($sub_order instanceof WC_Order) {
        $sub_order->payment_complete();
        $sub_order->save();
    }
}

function set_child_order_as_completed ($status, $order_id, $order) {
    if ($order->parent_id > 0) {
        return 'completed';
    }
    return $status;
}

function set_status_after_payment($order_id) {
    $order_statuses = wc_get_order_statuses();
    $child_order = wc_get_order($order_id);

    if ($child_order instanceof WC_Order) {
        $parent_order = wc_get_order($child_order->parent_id); 
        if ($parent_order instanceof WC_Order) {
            $current_status = $parent_order->get_status();

            $next_status_suffix = '-pa';
            $wc_status_prefix = 'wc-';
            $current_status = $wc_status_prefix . $current_status;
            $next_status = $current_status . $next_status_suffix;

            if (array_key_exists($current_status, $order_statuses) && array_key_exists($next_status, $order_statuses)) {
                // status without 'wc-' prefix
                $parent_order->update_status(substr($next_status, 3));
                $parent_order->save();
            }
        }
    }
}


/**
 * Create child order
 */
function create_partial_payment_order($parent_order_id, $status, $payment_value)
{
    if ($payment_value <= 0)
        return;

    $parent_order = wc_get_order($parent_order_id);
    $order_configs = [
        'customer_id' => $parent_order->get_customer_id(),
        'parent' => $parent_order_id,
        'status' => 'pending',
        'created_via' =>  $status
    ];

    $new_order = wc_create_order($order_configs);
    create_order_fee($new_order, $payment_value);

    $new_order->calculate_totals();
    $new_order->update_status('wc-pending');
    $new_order->save();

    wp_update_post([
        'ID' => $new_order->get_id(), 
        'post_author' => get_current_user_id()
    ]);

    // return the newly created order
    return $new_order;
}

function get_child_orders_by_parent_order($parent_order_id)
{
    return wc_get_orders(['parent' => $parent_order_id]);
}

#region Generate payments functions

function generate_payments() {
    $order_id = $_POST["order_id"];
    $number_of_payments = intval($_POST["payment_number"]);

    if ($order_id > 0 && $number_of_payments > 0) {
        $order = wc_get_order($order_id);

        $price_to_pay = floatval($order->get_total());
        $child_orders = get_child_orders_by_parent_order($order->get_id());
        foreach ($child_orders as $child_order) {
            $price_to_pay -= floatval($child_order->get_total());
        }

        $partial_pay = $price_to_pay / $number_of_payments;
        if ($partial_pay > 0) {
            for ($i = 0; $i < $number_of_payments; $i++) {
                create_partial_payment_order($order->get_id(), 'installment', $partial_pay);
            }
        }
    }
}

#endregion

#region Payments box viewer functions

function payments_box($screenID, $order)
{
    $real_order = get_order_from_post($order);

    if ($real_order instanceof WC_Order)
        add_meta_box('payment_custom_box', __('Payment'), 'payment_box_content', $screenID, 'advanced', 'high');
}

function payment_box_content($order)
{
    $real_order = get_order_from_post($order);
    if ($real_order instanceof WC_Order) {
        // nonce para segurança
        wp_nonce_field(basename(__FILE__), 'payment_box_nonce');

        // get child orders
        $child_orders = get_child_orders_by_parent_order($real_order->ID);

        $options = get_option('APF');

        $payments_created_blocksing_order_status = isset($options["order_status"]) && isset($options["order_status"]["payments_created_blocking_order_status"])? 
                                                        get_status_slug_from_post_name($options["order_status"]["payments_created_blocking_order_status"]) 
                                                        : 
                                                        "";
        
        $first_payment_blocking_order_status = isset($options["order_status"]) && isset($options["order_status"]["first_payment_blocking_order_status"])? 
                                                        get_status_slug_from_post_name($options["order_status"]["first_payment_blocking_order_status"]) 
                                                        : 
                                                        "";

        $check_payments_are_created = isset($options["order_status"]) && $payments_created_blocksing_order_status == $real_order->status;
        $check_first_payment_is_paid = isset($options["order_status"]) && $first_payment_blocking_order_status == $real_order->status;

        $content = child_orders_table_builder($real_order->ID, $child_orders, $check_payments_are_created, $check_first_payment_is_paid);
        echo $content;
    }
}

function child_orders_table_builder($parent_order_id, $child_orders, $check_payments_are_created, $check_first_payment_is_paid)
{
    $order_id_header = __('Sub') . '-' . __('Encomenda');
    $created_by_user_header = __('Criado por');
    $created_date_header = __('Criado em');
    $paid_date_header = __('Data de pagamento');
    $amount_header = __('Sub-total');
    $status_header = __('Estado');
    $actions_header = __('Ação');

    $no_installments_error_message = __("Não é possível atualizar a encomenda pois ainda não foram criados pagamentos");
    $no_paid_installments_error_message = __("Não é possível atualizar a encomenda pois ainda nenhuma das prestações foi paga");

    $table_content = "";
    $price_options = ['decimal_separator' => ','];
    $installments_already_created = false;

    foreach ($child_orders as $child_order) {
        $order_id = $child_order->ID;
        $order_link = $child_order->get_checkout_payment_url();

        $is_installment = $child_order->created_via == "installment";
        if ($is_installment)
            $installments_already_created = true;

        $user_who_created = get_user(get_post($child_order->id)->post_author)->display_name;
        
        $order_total = wc_price($child_order->get_total(), array_merge($price_options, ['currency' => $child_order->get_currency()]));
        $order_status = $child_order->date_paid == null? $child_order->get_status() : __('paid');
        $order_status_label = __(ucfirst($order_status));

        $created_date = date_format_check_null($child_order->get_date_created());
        $paid_date = date_format_check_null($child_order->get_date_paid());
        $is_paid = !(empty($paid_date) || $paid_date == "-");

        $order_label = "{$order_id_header} #{$order_id}";
        $is_pending = $order_status == 'pending' && $child_order->date_paid == null;
        $order_link_in_button = $is_pending? $order_link : '';

        // botão aparece se o estado for "on-hold"
        $button_label = $is_pending? __("Pagamento") : __("Definir como pago");
        $button_html = "<button class=\"button button-primary\" data-action=\"confirm-payment\" data-href=\"{$order_link_in_button}\" data-id=\"{$order_id}\"><span>{$button_label}</span><div class=\"loader\"></div></button>";
        $button_if_exists = $is_pending || $order_status == 'on-hold'? $button_html : "-";

        $table_content .=   "<tr data-is-installment=\"{$is_installment}\" data-is-paid=\"{$is_paid}\">
                                <td>{$order_label}</a></td>
                                <td>{$created_date}</td>
                                <td>{$paid_date}</td>
                                <td>{$user_who_created}</td>
                                <td>{$order_total}</td>
                                <td>{$order_status_label}</td>
                                <td>{$button_if_exists}</td>
                            </tr>";
    }

    $table =    "<table class=\"wp-list-table striped widefat payments-table\">" . 
                    "<thead>
                        <tr>
                            <th>{$order_id_header}</th>
                            <th>{$created_date_header}</th>
                            <th>{$paid_date_header}</th>
                            <th>{$created_by_user_header}</th>
                            <th>{$amount_header}</th>
                            <th>{$status_header}</th>
                            <th>{$actions_header}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$table_content}
                    </tbody>
                </table>"; 

    $generate_payments_form_url = get_site_url() . "ad-pulse/generate_payments";
    $generate_payments_button = $installments_already_created? "" :
        '<div class="generate-payments-container"' . 
                "data-check-payments-are-created=\"{$check_payments_are_created}\" " .
                "data-check-first-payment=\"{$check_first_payment_is_paid}\" " . 
                "data-no-installments-error=\"{$no_installments_error_message}\" " .
                "data-no-paid-installments-error=\"{$no_paid_installments_error_message}\" " . 
        '>' .
            '<label for="payment-number">' . __("Número de prestações") . '</label>' .
            '<input type="number" id="payment-number" name="payment-number" min="1" step="1" max="100">' .
            '<button class="button button-primary disabled" data-action="generate-payments" data-id="' . $parent_order_id . '">' . __("Gerar prestações") . '</button>' .
        '</div>';

    return !empty($table_content)? $table . $generate_payments_button : $generate_payments_button;
}

function date_format_check_null($date)
{
    return $date != null? $date->date('d-m-Y') : '-';
}

#endregion

#region Ligação das funções com os filtros/ações

// Sistema antigo de sub-encomendas (substituído pelo plugin Pagamentos Faseados)
// add_action('add_meta_boxes', 'payments_box', 10, 2);
// add_action('woocommerce_order_status_changed', 'check_payment_for_statuses', 10, 4);
// add_action('woocommerce_payment_complete', 'set_status_after_payment', 10, 2);
// add_action( 'wp_ajax_confirm_sub_order_payment', 'set_sub_order_paid');
// add_action('wp_ajax_generate_payments', 'generate_payments');

add_filter('woocommerce_payment_complete_order_status', 'set_child_order_as_completed', 10, 3);
#endregion