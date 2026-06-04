<?php
/***** Active Plugin ********/
add_action( 'tgmpa_register', 'funio_register_required_plugins' );
function funio_register_required_plugins() {
    $plugins = array(
		array(
            'name'               => esc_html__('Woocommerce', 'funio'), 
            'slug'               => 'woocommerce', 
            'required'           => false
        ),
		array(
            'name'      		 => esc_html__('Elementor', 'funio'),
            'slug'     			 => 'elementor',
            'required' 			 => false
        ),		
		array(
            'name'               => esc_html__('Revolution Slider', 'funio'), 
			'slug'               => 'revslider',
			'source'             => get_template_directory() . '/plugins/revslider.zip', 
			'required'           => true, 
        ),
		array(
            'name'               => esc_html__('Wpbingo Core', 'funio'), 
            'slug'               => 'wpbingo', 
            'source'             => get_template_directory() . '/plugins/wpbingo.zip',
            'required'           => true, 
        ),			
		array(
            'name'               => esc_html__('Redux Framework', 'funio'), 
            'slug'               => 'redux-framework', 
            'required'           => false
        ),			
		array(
            'name'      		 => esc_html__('Contact Form 7', 'funio'),
            'slug'     			 => 'contact-form-7',
            'required' 			 => false
        ),	
		array(
            'name'     			 => esc_html__('YITH Woocommerce Wishlist', 'funio'),
            'slug'      		 => 'yith-woocommerce-wishlist',
            'required' 			 => false
        ),	
		array(
            'name'     			 => esc_html__('WooCommerce Variation Swatches', 'funio'),
            'slug'      		 => 'variation-swatches-for-woocommerce',
            'required' 			 => false
        ),
    );
    $config = array();
    tgmpa( $plugins, $config );
}