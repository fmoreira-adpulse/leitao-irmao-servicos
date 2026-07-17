<?php
function multipart_encoding() {
    echo 'enctype="multipart/form-data"';
}

function attachments_box( $screenID, $order ) {
    $real_order = get_order_from_post( $order );
    if ( $real_order != false )
        generate_attachments_box( $screenID, $real_order );
}

function generate_attachments_box( $screenID, $order ) {
    remove_meta_box( 'woocommerce-order-downloads', $screenID, 'normal' );
    remove_meta_box( 'order_custom', $screenID, 'normal' );
    add_meta_box( 'attachments_box', __( 'Attachments' ), 'attachments_box_content', $screenID, 'advanced', 'high' );
}

function attachments_box_content( $order ) {
    $real_order = get_order_from_post( $order );
    if ( $real_order instanceof WC_Order ) {
        wp_nonce_field( basename( __FILE__ ), 'attachments_order_files_nonce' );
        $attached = $real_order->get_meta( '_attachments_order_files' );
        if ( is_array( $attached ) ) {
            foreach ( $attached as &$file ) {
                $file['size']              = file_exists( $file['file'] ) ? filesize( $file['file'] ) : 0;
                $file['file']              = $file['url'];
                $file['data']['thumbnail'] = $file['url'];
            }
            unset( $file );
        } else {
            $attached = [];
        }
        echo '<script>let attachedFiles = ' . json_encode( $attached ) . ';</script>';
        $file_uploader = new FileUploader( 'attachments_order_files[]', [ 'extensions' => [ 'jpg', 'png', 'pdf', 'doc', 'docx' ] ] );
        echo $file_uploader->generateInput();
    }
}

function attachments_save_order_files( $order_id, $this_order ) {
    if ( empty( $_POST ) || ! isset( $_POST['attachments_order_files_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( $_POST['attachments_order_files_nonce'], basename( __FILE__ ) ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_shop_orders' ) ) {
        return;
    }

    $real_order = get_order_from_post( $this_order );
    if ( $real_order === false ) {
        $real_order = wc_get_order( $order_id );
    }
    if ( ! $real_order instanceof WC_Order ) {
        return;
    }

    // Sem enctype="multipart/form-data" o browser envia o input de ficheiro como
    // texto em $_POST e $_FILES fica vazio — não tocar nos anexos nesse caso.
    if ( ! isset( $_FILES['attachments_order_files'] ) && isset( $_POST['attachments_order_files'] ) ) {
        return;
    }

    $has_new_files = ! empty( $_FILES['attachments_order_files']['name'][0] );
    $has_file_list = isset( $_POST['fileuploader-list-attachments_order_files'] );

    if ( ! $has_new_files && ! $has_file_list ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';

    $existingFiles = $real_order->get_meta( '_attachments_order_files' );
    if ( ! is_array( $existingFiles ) ) {
        $existingFiles = [];
    }

    $finalFiles = [];

    if ( $has_file_list ) {
        // Lista do UI — ficheiros que o utilizador quer manter
        $fileList = [];
        $jsonDecoded = json_decode( stripslashes( $_POST['fileuploader-list-attachments_order_files'] ), true );
        if ( is_array( $jsonDecoded ) ) {
            $fileList = array_column( $jsonDecoded, 'file' );
        }

        // Manter ficheiros existentes que ainda estão na lista; remover os outros
        foreach ( $existingFiles as $file ) {
            if ( in_array( $file['url'], $fileList ) ) {
                $finalFiles[] = $file;
            } else {
                if ( ! empty( $file['file'] ) && file_exists( $file['file'] ) ) {
                    unlink( $file['file'] );
                }
            }
        }
    } else {
        // Lista não veio no POST (UI não inicializado) — manter tudo
        $finalFiles = $existingFiles;
    }

    // Upload de novos ficheiros
    if ( $has_new_files ) {
        foreach ( $_FILES['attachments_order_files']['name'] as $fileKey => $fileName ) {
            if ( $fileName !== '' ) {
                $file = [
                    'name'     => $_FILES['attachments_order_files']['name'][ $fileKey ],
                    'type'     => $_FILES['attachments_order_files']['type'][ $fileKey ],
                    'tmp_name' => $_FILES['attachments_order_files']['tmp_name'][ $fileKey ],
                    'error'    => $_FILES['attachments_order_files']['error'][ $fileKey ],
                    'size'     => $_FILES['attachments_order_files']['size'][ $fileKey ],
                ];

                $uploadedFile = wp_handle_upload( $file, [ 'test_form' => false ] );

                if ( ! isset( $uploadedFile['error'] ) ) {
                    $finalFiles[] = [
                        'file' => $uploadedFile['file'],
                        'url'  => $uploadedFile['url'],
                        'type' => $uploadedFile['type'],
                        'name' => $fileName,
                    ];
                } else {
                    error_log( 'ad-pulse attachments: erro no upload de ' . $fileName . ': ' . $uploadedFile['error'] );
                }
            }
        }
    }

    $real_order->update_meta_data( '_attachments_order_files', $finalFiles );
    $real_order->save();
}

// =============================================================================
// Hooks
// =============================================================================
add_action( 'post_edit_form_tag',  'multipart_encoding' ); // editor legado (post.php)
add_action( 'order_edit_form_tag', 'multipart_encoding' ); // ecrã HPOS (admin.php?page=wc-orders)
add_action( 'add_meta_boxes',      'attachments_box', 10, 2 );

// woocommerce_process_shop_order_meta dispara no mesmo pedido POST em ambos os
// ecrãs (legado e HPOS), com $_FILES ainda disponível. Prioridade 45: depois
// do save do WooCommerce core (40), tal como custom_fields.php.
add_action( 'woocommerce_process_shop_order_meta', 'attachments_save_order_files', 45, 2 );