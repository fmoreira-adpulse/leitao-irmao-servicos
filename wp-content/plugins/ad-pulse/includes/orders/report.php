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
function word_line_breaks($text) {
    return str_replace("\n", '</w:t><w:br/><w:t xml:space="preserve">', str_replace("\r\n", "\n", $text));
}

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
    $url = "https://doc2pdf.ad-pulse.com/convert.php";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($file_path, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'input.docx')]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $curl_response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('[REPORT] Erro curl: ' . curl_error($ch));
        $curl_response = false;
    }

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
    set_time_limit(120);
    error_log('[REPORT] Início da geração do relatório. order_id=' . $_POST['order_id']);

    // Load the template file
    $template_path = __DIR__ . '/../../assets/report_template.docx';
    if (!file_exists($template_path)) {
        error_log('[REPORT] ERRO: template não encontrado em ' . $template_path);
        echo json_encode(['status-code' => 500, 'message' => 'Template não encontrado.']);
        wp_die();
    }
    $template_processor = new TemplateProcessor($template_path);
    error_log('[REPORT] Template carregado com sucesso.');

    // Load the order info
    $order = wc_get_order($_POST['order_id']);
    if (!$order) {
        error_log('[REPORT] ERRO: encomenda não encontrada.');
        echo json_encode(['status-code' => 400, 'message' => 'Encomenda não encontrada.']);
        wp_die();
    }
    $user = $order->get_user();

    $order_meta_data = array_column($order->get_meta_data(), 'value', 'key');
    error_log("This is the order's meta data: " . json_encode($order_meta_data));

    $all_custom_fields = get_order_custom_fields();

    #region Insert info in header
    $order_seller = get_order_seller($order->get_id());
    error_log('[REPORT] Vendedor: ' . (is_object($order_seller) ? $order_seller->display_name : 'NÃO ENCONTRADO (valor: ' . json_encode($order_seller) . ')'));
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
    $template_processor->setValue('seller_name', is_object($order_seller) ? $order_seller->display_name : '');
    $template_processor->setValue('store_name', $store_name);
    error_log('[REPORT] Cabeçalho preenchido.');
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
                'order_meta_field_value' => word_line_breaks($meta_value)
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
                    'product_meta_value' => word_line_breaks($meta['value'])
                ];
            }
        }
    }

    // Replace placeholders for products' meta data
    error_log("Meta data in template: " . json_encode($meta_data_in_template));
    $template_processor->cloneBlock('product_meta_block', 0, true, false, $meta_data_in_template);

    #endregion

    #region Insert info in footer

    $seller_meta = is_object($order_seller) ? get_user_meta($order_seller->ID) : [];
    error_log('[REPORT] Metadados do vendedor: ' . json_encode($seller_meta));

    $template_processor->setValue('user_mobile_phone', $seller_meta['billing_phone'][0] ?? '');
    $template_processor->setValue('user_landline', $seller_meta['telefone_fixo'][0] ?? '');
    $template_processor->setValue('user_email', $seller_meta['billing_email'][0] ?? '');
    error_log('[REPORT] Rodapé preenchido.');

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
    error_log('[REPORT] DOCX guardado em: ' . $full_report_path_docx);

    #region Export as pdf
    $upload_dir = wp_upload_dir();
    $full_report_folder_path = $upload_dir['basedir'] . '/ad-pulse/reports/';
    $full_report_folder_url = $upload_dir['baseurl'] . '/ad-pulse/reports/';

    $full_report_pdf_path = $full_report_folder_path . $report_pdf_name;

    if (!file_exists($full_report_folder_path)) {
        $mkdir_result = wp_mkdir_p($full_report_folder_path);
        error_log('[REPORT] Criação da pasta: ' . ($mkdir_result ? 'OK' : 'FALHOU') . ' — ' . $full_report_folder_path);
    }

    error_log('[REPORT] A enviar DOCX para conversão remota...');
    $pdf_content = convert_to_pdf_by_remote_url($full_report_path_docx);
    error_log('[REPORT] Resposta da conversão: ' . ($pdf_content === false ? 'FALSE (curl falhou)' : 'recebida (' . strlen($pdf_content) . ' bytes)'));

    $file_pointer = fopen($full_report_pdf_path, 'wb');
    if ($file_pointer) {
        fwrite($file_pointer, $pdf_content);
        fclose($file_pointer);

        $response['message'] = 'Success';
        $response['status-code'] = 200;
        $response['file'] = $full_report_folder_url . $report_pdf_name;
        error_log('[REPORT] PDF guardado com sucesso em: ' . $full_report_pdf_path);
    } else {
        error_log('[REPORT] ERRO: fopen falhou para o caminho: ' . $full_report_pdf_path . ' — pasta existe: ' . (file_exists($full_report_folder_path) ? 'sim' : 'não') . ' — permissões: ' . decoct(fileperms($full_report_folder_path) & 0777));
    }
    #endregion

    error_log('[REPORT] Resposta final: ' . json_encode($response));
    echo json_encode($response);
    wp_die();
}

// region Ligação das funções com os filtros/ações

add_action('add_meta_boxes', 'pdf_box', 10, 2);
add_action( 'wp_ajax_generate_report', 'generate_report_handler' );

// endregion