<?php
/**
 * Permissões de acesso aos campos das encomendas por role de utilizador
 *
 * Define quais os roles que têm acesso a editar determinadas secções
 * das encomendas. Roles não incluídos ficam com os campos visíveis
 * mas não editáveis (disabled).
 */

/**
 * Bloquear a secção de itens da encomenda para roles sem permissão.
 * No estado default, todos os utilizadores podem editar.
 * Nos restantes estados, apenas os roles privilegiados podem editar.
 */
add_action( 'admin_head', function() {

    $user = wp_get_current_user();
    $allowed_roles = [ 'administrator', 'shop_manager', 'lojasli', 'admin-plataforma', 'superadminli' ];
    $has_access = ! empty( array_intersect( $allowed_roles, (array) $user->roles ) );

    if ( ! $has_access ) {
        $order_id = $_GET['id'] ?? null;
        $is_default_state = true;

        if ( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $current_status = $order->get_status();
                $default_status = get_option( 'default_order_status' );
                $is_default_state = in_array( $current_status, [ 'auto-draft', 'pending' ] )
                    || ( $default_status && $current_status === $default_status );
            }
        }

        if ( ! $is_default_state ) {
            echo '<style>
                /* Bloquear edição da secção de itens da encomenda */
                #woocommerce-order-items input,
                #woocommerce-order-items select,
                #woocommerce-order-items textarea,
                #woocommerce-order-items button:not(.handlediv) {
                    pointer-events: none !important;
                    opacity: 0.6 !important;
                }
                #woocommerce-order-items .wc-order-edit-line-item-actions,
                #woocommerce-order-items .wc-order-bulk-actions,
                #woocommerce-order-items .wc-order-add-item,
                #woocommerce-order-items .wc-order-refund-items {
                    display: none !important;
                }
            </style>';
        }
    }

}, 5 );