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
 * Apenas administrator e shop_manager podem editar itens.
 */
add_action( 'admin_head', function() {

    $user = wp_get_current_user();
    $allowed_roles = [ 'administrator', 'shop_manager' ];
    $has_access = ! empty( array_intersect( $allowed_roles, (array) $user->roles ) );

    if ( ! $has_access ) {
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

}, 5 );