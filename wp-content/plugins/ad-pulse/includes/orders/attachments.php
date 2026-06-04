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
                $file['size']              = filesize( $file['file'] );
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
    error_log( '=== attachments START === order: ' . $order_id );

    if ( empty( $_POST ) || ! isset( $_POST['attachments_order_files_nonce'] ) ) {
        error_log( 'attachments: return - no POST or no nonce' );
        return;
    }

    if ( ! wp_verify_nonce( $_POST['attachments_order_files_nonce'], basename( __FILE__ ) ) ) {
        error_log( 'attachments: return - nonce failed' );
        return;
    }

    error_log( 'attachments: nonce OK' );

    if ( ! current_user_can( 'edit_post', $order_id ) ) {
        error_log( 'attachments: return - no permission' );
        return;
    }

    error_log( 'attachments: permission OK' );

    if ( empty( $_FILES['attachments_order_files']['name'][0] ) && empty( $_POST['fileuploader-list-attachments_order_files'] ) ) {
        error_log( 'attachments: return - no files' );
        return;
    }

    error_log( 'attachments: has files, proceeding' );

    require_once ABSPATH . 'wp-admin/includes/file.php';

    $existingFiles = $this_order->get_meta( '_attachments_order_files' );
    if ( ! is_array( $existingFiles ) ) {
        $existingFiles = [];
    }

    $finalFiles = [];

    // Lista do UI — ficheiros que o utilizador quer manter
    $fileList = [];
    if ( ! empty( $_POST['fileuploader-list-attachments_order_files'] ) ) {
        $jsonDecoded = json_decode( stripslashes( $_POST['fileuploader-list-attachments_order_files'] ), true );
        if ( is_array( $jsonDecoded ) ) {
            $fileList = array_column( $jsonDecoded, 'file' );
        }
    }

    error_log( 'attachments: fileList = ' . json_encode( $fileList ) );

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

    // Upload de novos ficheiros
    if ( ! empty( $_FILES['attachments_order_files']['name'][0] ) ) {
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
                    error_log( 'attachments: uploaded ' . $fileName . ' → ' . $uploadedFile['url'] );
                    $finalFiles[] = [
                        'file' => $uploadedFile['file'],
                        'url'  => $uploadedFile['url'],
                        'type' => $uploadedFile['type'],
                        'name' => $fileName,
                    ];
                } else {
                    error_log( 'attachments: upload error for ' . $fileName . ': ' . $uploadedFile['error'] );
                }
            }
        }
    }

    error_log( 'attachments: saving ' . count( $finalFiles ) . ' files' );

    update_post_meta( $order_id, '_attachments_order_files', $finalFiles );

    error_log( '=== attachments END ===' );
}

// =============================================================================
// Hooks
// =============================================================================
add_action( 'post_edit_form_tag', 'multipart_encoding' );
add_action( 'add_meta_boxes',     'attachments_box', 10, 2 );

// Usar save_post em vez de woocommerce_update_order
// O post.php faz 302 e os $_FILES só existem durante esse pedido
add_action( 'save_post', function( $post_id ) {
    $order = wc_get_order( $post_id );
    if ( $order instanceof WC_Order ) {
        attachments_save_order_files( $post_id, $order );
    }
}, 10, 1 );