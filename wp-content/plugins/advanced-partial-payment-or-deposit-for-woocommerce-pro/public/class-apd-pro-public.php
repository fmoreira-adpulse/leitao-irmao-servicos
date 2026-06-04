<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pro frontend class.
 */
class APD_Pro_Public {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) return;

        wp_enqueue_style(
            'apd-pro-public',
            APD_PRO_PLUGIN_URL . 'public/css/apd-pro-public.css',
            array( 'apd-public' ),
            APD_PRO_VERSION
        );
    }
}
