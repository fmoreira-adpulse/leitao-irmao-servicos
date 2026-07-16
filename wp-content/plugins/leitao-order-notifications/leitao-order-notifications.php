<?php
/**
 * Plugin Name: Leitão & Irmão — Notificações de Estados
 * Description: Notificações por e-mail para estados de encomenda, com roteamento baseado em roles do utilizador que fez a alteração.
 * Version:     1.0.0
 * Author:      Leitão & Irmão
 * Text Domain: lon
 */

defined( 'ABSPATH' ) || exit;

define( 'LON_VERSION',       '1.0.0' );
define( 'LON_PLUGIN_DIR',    plugin_dir_path( __FILE__ ) );
define( 'LON_TEMPLATE_BASE', WP_PLUGIN_DIR . '/bp-custom-order-status-for-woocommerce/templates/' );

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    if ( ! is_dir( LON_TEMPLATE_BASE ) ) {
        return;
    }

    // class-lon-email.php estende WC_Email, que ainda não existe neste ponto;
    // é carregado em LON_Handler::dispatch(), depois de WC()->mailer().
    require_once LON_PLUGIN_DIR . 'includes/class-lon-handler.php';

    new LON_Handler();
} );
