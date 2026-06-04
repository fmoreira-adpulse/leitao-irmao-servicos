<?php

require_once __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

/**
 * Função para adicionar a caixa que permite gerar o relatório da encomenda
 * @param $screenID
 * @param $order
 * @return void
 * @throws ReflectionException
 */
function pdf_box($screen, $order = null) {
    $real_order = get_order_from_post($order);
    if ($real_order instanceof WC_Order)
        add_meta_box( 'pdf_custom_box', __('Documento de acompanhamento de encomenda'), 'pdf_box_content', $screen->id, 'advanced', 'high' );
}

function pdf_box_content() {
    // nonce para segurança
    wp_nonce_field(basename(__FILE__), 'pdf_box_nonce');
    echo '<button class="button button-primary" data-action="generate-order-report"><span>' . __('Gerar documento') . '</span><div class="loader"></div></button>';
}

function get_order_seller($order_id) {
    $notes = wc_get_order_notes(array('order_id' => $order_id));
    $first_note = end($notes);
    $user_query = ['search' => $first_note->added_by, 'search_columns' => ['display_name', 'user_nicename']];
    $result = get_users($user_query);
    return !empty($result)? $result[0] : [];
}

function get_order_seller_name($order_id) {
    $order_seller = get_order_seller($order_id);
    return !empty($order_seller)? $order_seller->display_name : '';
}

function convert_to_pdf_by_remote_url($file_path) {
    // info to send
    $url = "https://doc2pdf.ad-pulse.com/convert.php";

    //Initialise the cURL var
    $ch = curl_init();

    // return the response instead of sending it to stdout:
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // set the Url
    curl_setopt($ch, CURLOPT_URL, $url);
    // set the request as a post
    curl_setopt($ch, CURLOPT_POST, true);
    // set the file to send
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($file_path, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'input.docx')]);

    // send the request and get the response
    $curl_response = curl_exec($ch);
    // close the connection
    curl_close($ch);

    return $curl_response;
}

function convert_to_pdf($path_to_output, $input_file) {
    // check whether this is running on a windows os machine or linux os
    // here it is supposed that the remote machine is always a linux one 
    // (unless the server is local and the local machine is running on windows)
    $os_is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

    // set remote path where to ouptut the converted pdf file, server and user
    // TODO: change here when changing server
    $remote_path_to_output = '';
    $server = 'localhost';
    $user = 'admin';

    // ping user
    $ping_options = $os_is_windows? '-n' : '-c';
    exec("ping $ping_options 1 $server", $ping_result, $status_code);

    $output = escapeshellarg($path_to_output);
    $input = escapeshellarg($input_file);

    if ($status_code == 0) {
        if ($server != 'localhost') {
            $command = "libreoffice --headless --convert-to pdf --outdir $remote_path_to_output $input_file";
            $remote_command = "ssh $user@$server '$command'";
            
            // execute the conversion
            exec($remote_command, $output, $status_code);

            // download the file from the external server
            exec("scp $user@$server:$remote_path_to_output $path_to_output", $output, $status_code);
        }
        else {
            $local_command = $os_is_windows? '"C:\Program Files\LibreOffice\program\soffice.exe" --headless --convert-to pdf --outdir "' . $output . '" "' . $input . '"' : "libreoffice --headless --convert-to pdf --outdir $path_to_output $input_file";

            // execute the conversion
            exec($local_command, $output, $status_code);
        }
    }

    return $status_code;
}

function generate_report_handler() {
    // Load the template file
    $template_path = __DIR__ . '/../../assets/report_template.docx';
    $template_processor = new TemplateProcessor($template_path);

    // Load the order info
    $order = wc_get_order($_POST['order_id']);
    $user = $order->get_user();
    
    $order_meta_data = array_column($order->get_meta_data(), 'value', 'key');
    error_log("This is the order's meta data: " . json_encode($order_meta_data));

    $all_custom_fields = get_order_custom_fields();

    #region Insert info in header
    $order_seller = get_order_seller($order->id);
    $store_name = '';

    foreach ($all_custom_fields as $custom_field) {
        if ($custom_field['name'] == 'store') {
            foreach ($custom_field['choices'] as $choice_key => $choice_label) {
                if ($choice_key == $order_meta_data['_order_custom_store'])
                    $store_name = $choice_label;
            }
        }
    }

    // Placeholders in header
    $template_processor->setValue('order_number', $order_meta_data['_order_number']);
    $template_processor->setValue('client_name', $user->display_name);
    $template_processor->setValue('seller_name', $order_seller->display_name);
    $template_processor->setValue('store_name', $store_name);
    #endregion

    #region Insert info in body

    // Placeholders in report's body
    $report_fields = [
        'description',
        'font#sku=9996#',
        'text#sku=9996#',
        'font#sku=9997#',
        'text#sku=9997#'

    ];

    $all_custom_fields_names = array_column($all_custom_fields, 'label', 'name');

    
    $report_fields_in_template = [];
    foreach ($report_fields as $report_field) {
        error_log("This is as a report field: " . $report_field);
        
        $meta_value = $order_meta_data['_order_custom_' . $report_field];
        error_log("This is as a report field's value: " . $meta_value);

        if (!is_null($meta_value) && !empty($meta_value)) {
            $report_fields_in_template[] = [
                'order_meta_field_title' => $all_custom_fields_names[$report_field],
                'order_meta_field_value' => $meta_value
            ];
        }
    }

    // Replace placeholders for orders' meta data
    error_log("These will be the report fields in the template: " . json_encode($report_fields_in_template));
    $template_processor->cloneBlock('order_meta_field_block', 0, true, false, $report_fields_in_template);

    #endregion

    #region Get each order item's meta data in template
    $order_items = $order->get_items();
    $meta_data_in_template = [];
    foreach($order_items as $order_item) {
        $meta_array = $order_item->get_meta_data();
        foreach ($meta_array as $meta_data_class) {
            $meta = $meta_data_class->get_data();
            if (!empty($meta['value']) && is_string($meta['value'])) {
                $meta_data_in_template[] = [
                    'product_meta_title' => $meta['key'],
                    'product_meta_value' => $meta['value']
                ];
            }
        }
    }

    // Replace placeholders for products' meta data
    error_log("Meta data in template: " . json_encode($meta_data_in_template));
    $template_processor->cloneBlock('product_meta_block', 0, true, false, $meta_data_in_template);

    #endregion

    #region Insert info in footer
    
    $seller_meta = get_user_meta($order_seller->id);

    $template_processor->setValue('user_mobile_phone', $seller_meta['billing_phone'][0]);
    $template_processor->setValue('user_landline', $seller_meta['telefone_fixo'][0]);
    $template_processor->setValue('user_email', $seller_meta['billing_email'][0]);

    #endregion
    
    // Calculate path files (for docx)
    $file_name = 'result_' . $order->get_id();
    $report_path_prefix = 'reports/' . $file_name;
    $report_path_docx = $report_path_prefix . '.docx';
    
    $report_pdf_name = $file_name . '.pdf';
    $full_report_path_docx = __DIR__ . '/../../' . $report_path_docx;

    // response array
    $response = ['message' => 'Could not generate the file, please try again later', 'status-code' => 500, 'file' => ''];

    // Save docx
    $template_processor->saveAs($full_report_path_docx);

    #region Export as pdf
    $folder_inside_wp_content = 'uploads/ad-pulse/reports/';
    $full_report_folder_path = __DIR__ . '/../../../../' . $folder_inside_wp_content;

    $full_report_pdf_path = $full_report_folder_path . $report_pdf_name;
    $result_code = convert_to_pdf_by_remote_url($full_report_path_docx);
    $file_pointer = fopen($full_report_pdf_path, 'wb');
    if ($file_pointer) {
        // if the file was created

        fwrite($file_pointer, $result_code);
        fclose($file_pointer);

        $response['message'] = 'Success';
        $response['status-code'] = 200;
        $response['file'] = get_site_url() . '/wp-content/' . $folder_inside_wp_content . $report_pdf_name;
    }
    #endregion

    // Return the report's url
    echo json_encode($response);
    wp_die();
}

// region Ligação das funções com os filtros/ações

add_action('add_meta_boxes', 'pdf_box', 10, 2);
add_action( 'wp_ajax_generate_report', 'generate_report_handler' );

// endregion