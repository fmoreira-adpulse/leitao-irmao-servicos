<?php
/**
 * Mostrar Campos Adicionados à Encomenda
 * @param $order
 * @return void
 */
function add_custom_fields(WC_Order $order) {

    $all_fields = get_order_custom_fields();

    $custom_fields = '';
    $order_meta_data = array_column($order->get_meta_data(), 'value', 'key');

    $printed_headers = [];

    foreach($all_fields as $field) {

        // valor atual do campo
        $custom_meta_key = '_order_custom_' . $field['name'];
        $input_value = $field['type'] == 'checkbox'? array_values($field['choices'])[0] : (isset($order_meta_data[$custom_meta_key])? $order_meta_data[$custom_meta_key] : "");
        $editable_string = "";

        // verificação se o campo é condicionado por SKU
        $main_sku = get_product_data_from_order($order, true);
        preg_match('#\#sku=(.*?)\##', $field['name'], $match);
        error_log("Condição para o SKU:");
        error_log(json_encode($match));
        if (!empty($match) && $match[1] != $main_sku)
            continue;

        // verificação se o campo é condicionado por estado de encomenda
        preg_match('#\#status=(.*?)\##', $field['name'], $match);
        if (!empty($match) && $match[1] != $order->get_data()['status']) {
           // se não pertencer a este estado e estiver vazio então não aparece
           // se estiver preenchido aparece como não editável
            if (!empty($input_value))
                $editable_string = ' disabled';
            else
                continue;
        }

        check_and_print_header($custom_fields, $printed_headers, $field['name'], 'engraving', __('Gravação'));
        check_and_print_header($custom_fields, $printed_headers, $field['name'], 'denial', __('Recusa'));

        $custom_field_id = 'order-custom-' . $field['name'];

        $label_tag = '<label for="' . $custom_field_id . '">' . __($field['label']) . '</label>';
        
        $checked = $field['type'] == 'checkbox' && $order_meta_data[$custom_meta_key] == (array_values($field['choices'])[0]);

        $required_label = ($field['required'])? ' required' : '';
        $maxlength_label = isset($field['maxlength']) && $field['maxlength'] > 0? ' maxlength="' . $field['maxlength'] . '"' : '';

        $add_to_array = true;
        switch($field['type']) {
            case 'file':
                $add_to_array = false;
                break;
            case 'textarea':
                $input_tag = '<textarea id="' . $custom_field_id . '" name="' . $custom_meta_key . '" ' . $required_label . $editable_string . '>' . $input_value . '</textarea>';
                break;
            case 'select':
                $select_options = '<option value="" disabled selected>Select an option</option>';
                foreach($field['choices'] as $choice_value => $choice_text) {
                    $selected_attr = $choice_value == $input_value? 'selected' : '';
                    $select_options .= '<option value="' . $choice_value . '" ' . $selected_attr . '>' . $choice_text . '</option>';
                }
                $input_tag = '<select id="' . $custom_field_id . '" name="' . $custom_meta_key . '"' . $required_label . $editable_string . '>' . $select_options . '</select>';
                break;
            default:
                $input_tag = '<input type="' . $field['type'] . '" id="' . $custom_field_id . '" name="' . $custom_meta_key . '" value="' . $input_value . '"' . $maxlength_label . $required_label . $editable_string .  ($checked? ' checked' : '') . '>';
                break;
        }

        if($add_to_array) {
            $custom_fields .= '<p class="form-field form-field-wide">' . $label_tag . $input_tag . '</p>';
        }
    }

    error_log("Fields to be echoed: ");
    error_log($custom_fields);
    echo $custom_fields;
}

function remove_default_acf_fields_meta_box() {
    global $wp_meta_boxes;

    $screen = 'shop_order';
    $context = 'normal';
    $priority = 'high';
    $id_to_delete = iterate_through_meta_boxes($wp_meta_boxes[$screen][$context]);

    remove_meta_box($id_to_delete, $screen, $context);
}

function iterate_through_meta_boxes($meta_box_array) {
    if (is_array($meta_box_array)) {
        if (isset($meta_box_array['id']) && is_string($meta_box_array['id']) && str_starts_with($meta_box_array['id'], 'acf-group')) {
            return $meta_box_array['id'];
        } else {
            foreach ($meta_box_array as $sub_array) {
                $has_id = iterate_through_meta_boxes($sub_array);
                if ($has_id != false)
                    return $has_id;
            }
        }
    }

    return false;
}

function check_and_print_header(&$fields, &$printed_headers, $input_name, $section_slug, $section_title) {
    if (str_contains($input_name, $section_slug) && !in_array($section_slug, $printed_headers)) {
        $fields .= '&nbsp<h3 class="custom-input-header">' . $section_title . '</h3>';
        $printed_headers[] = $section_slug;
    }
}


/**
 * Guardar Campos Adicionados na Encomenda
 * @param $order_id
 * @param WC_Order $order
 * @return void
 */
function save_custom_fields($order_id, $order): void
{
    // Skip if not a real form submission
    if (empty($_POST)) return;

    // Load a fresh order from HPOS so we pick up the status already committed
    // by WC_Meta_Box_Order_Data::save (priority 40) before this runs (priority 45).
    $real_order = wc_get_order($order_id);
    if (!$real_order) return;

    $fields = get_order_custom_fields();

    foreach($fields as $field) {
        $meta_key = '_order_custom_' . $field['name'];
        if (!empty($_POST[$meta_key])) {
            $real_order->update_meta_data($meta_key, sanitize_text_field($_POST[$meta_key]));
        }
    }

    remove_action('woocommerce_process_shop_order_meta', 'save_custom_fields', 45);
    $real_order->save();
    add_action('woocommerce_process_shop_order_meta', 'save_custom_fields', 45, 2);
}

/**
 * Ir buscar campos associados à encomenda
 * @return array
 */
function get_order_custom_fields(): array
{
    $field_groups = get_posts(['post_type' => 'acf-field-group', 'posts_per_page' => -1]);
    $fields = [];

    error_log("Result within get_order_custom_fields:");
    error_log(json_encode($field_groups));

    foreach($field_groups as $group) {
        $in_order = false;
        $content = maybe_unserialize($group->post_content);
        foreach($content['location'] as $location) {
            if(in_array(['param' => 'post_type', 'operator' => '==', 'value' => 'shop_order'], $location)) {
                $in_order = true;
                break;
            }
        }

        if($in_order) {
            error_log("Fields to merge:");
            error_log(json_encode(acf_get_fields($group)));
            $fields = array_merge($fields, acf_get_fields($group));
        }
    }

    error_log("Fields' array within get_order_custom_fields:");
    error_log(json_encode($fields));

    return $fields;
}

/**
 * Adição dos campos adicionais na secção de faturação
 */
function add_custom_billing_fields($fields) 
{
    $field['sage'] = 
    [
        'type'        => 'text',
        'label'       => __('Número de cliente do SAGE 2', 'woocommerce'),
        'placeholder' => _x('Nº SAGE', 'placeholder', 'woocommerce'),
        'required'    => false,
        'class'       => array('form-row-first'),
        'clear'       => true,
    ];

    return $fields;
}


/**
 * Add custom billing fields to order admin panel
 *
 * @param array $billing_fields The billing fields.
 */
function woocommerce_custom_admin_billing_fields( $billing_fields ) {
    $billing_fields['sage'] = 
    [
        'label' =>  __('Número de cliente do SAGE', 'woocommerce')
    ];

    return $billing_fields;
}

/**
 * Add field to the admin user edit screen
 *
 * @param array $show_fields The fields.
 */

 function woocommerce_custom_billing_customer_meta_fields( $show_fields ) {
    if ( isset( $show_fields['billing'] ) && is_array( $show_fields['billing']['fields'] ) ) {
        $show_fields['billing']['fields']['sage'] = array(
            'label'       => _x('Nº SAGE', 'placeholder', 'woocommerce'),
            'description' => __('Número de cliente do SAGE', 'woocommerce')
        );
    }
    return $show_fields;
}

function customize_autofill_customer_details( $response, $customer, $customer_id ) {
    // Modify the $customer_data array as needed
    // $customer_data['billing_phone'] = 'Custom Phone';
    $prefixes = ['billing', 'shipping'];
    foreach ($customer->meta_data as $meta) {
        $sub_keys = explode('_', $meta->key);
        if (in_array($sub_keys[0], $prefixes)) {
            $response[$sub_keys[0]][$sub_keys[1]] = $meta->value;
        }
    }

    return $response;
}

// region Ligação das funções aos filtros/ações
add_action( 'woocommerce_customer_meta_fields', 'woocommerce_custom_billing_customer_meta_fields', 10, 1);
add_filter( 'woocommerce_admin_billing_fields', 'woocommerce_custom_admin_billing_fields', 10, 1);
add_filter( 'woocommerce_billing_fields', 'add_custom_billing_fields', 10, 2 );

add_action('woocommerce_admin_order_data_after_order_details', 'add_custom_fields', 10, 1);
add_action('woocommerce_process_shop_order_meta', 'save_custom_fields', 45, 2);
add_filter( 'woocommerce_ajax_get_customer_details', 'customize_autofill_customer_details', 11, 3 );
add_action('add_meta_boxes', 'remove_default_acf_fields_meta_box', 99);
// endregion