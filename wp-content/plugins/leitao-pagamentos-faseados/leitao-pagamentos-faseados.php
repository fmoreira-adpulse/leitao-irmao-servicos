<?php
/**
 * Plugin Name: Pagamentos Faseados
 * Description: Gestão de pagamentos faseados para encomendas no backoffice
 * Version:     1.0.0
 * Author:      Leitão & Irmão
 * Text Domain: lpf
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   9.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LPF_VERSION', '1.0.0' );
define( 'LPF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LPF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) return;

    require_once LPF_PLUGIN_DIR . 'includes/class-lpf-settings.php';
    require_once LPF_PLUGIN_DIR . 'includes/class-lpf-order-meta-box.php';
    require_once LPF_PLUGIN_DIR . 'includes/class-lpf-payment-link.php';
    require_once LPF_PLUGIN_DIR . 'includes/class-lpf-my-account.php';
    require_once LPF_PLUGIN_DIR . 'includes/class-lpf-order-price.php';
    require_once LPF_PLUGIN_DIR . 'includes/class-lpf-status-guard.php';

    LPF_Settings::init();
    LPF_Order_Meta_Box::init();
    LPF_Payment_Link::init();
    LPF_My_Account::init();
    LPF_Order_Price::init();
    LPF_Status_Guard::init();
} );
