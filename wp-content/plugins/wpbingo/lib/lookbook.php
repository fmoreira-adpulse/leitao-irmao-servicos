<?php
require_once( ABSPATH . 'wp-includes/pluggable.php' );
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
define ( 'UPLOAD_FOLDER_NAME', 'bwp_lookbook');
define ( 'UPLOAD_FOLDER_NAME_THUMB', 'bwp_lookbook_thumb');
define ( 'UPLOAD_FOLDER_NAME_ORIG', 'bwp_lookbook_orig');
define ( 'WPBINGO_LOOKBOOK_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
define ( 'WPBINGO_LOOKBOOK_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
define ( 'LOOKBOOK_UPLOAD_URL_IMAGE', WPBINGO_LOOKBOOK_PLUGIN_URL . '/lookbook/images');
define ( 'LOOKBOOK_UPLOAD_URL_THUMB', WPBINGO_LOOKBOOK_PLUGIN_URL . '/lookbook/images/'.UPLOAD_FOLDER_NAME_THUMB);
define ( 'LOOKBOOK_UPLOAD_PATH_IMAGE', WPBINGO_LOOKBOOK_PLUGIN_DIR . '/lookbook/images');
define ( 'LOOKBOOK_UPLOAD_PATH', WPBINGO_LOOKBOOK_PLUGIN_DIR . '/lookbook/images/' . UPLOAD_FOLDER_NAME);
define ( 'LOOKBOOK_UPLOAD_PATH_ORIG', WPBINGO_LOOKBOOK_PLUGIN_DIR . '/lookbook/images/' . UPLOAD_FOLDER_NAME_ORIG);
define ( 'LOOKBOOK_UPLOAD_PATH_THUMB', WPBINGO_LOOKBOOK_PLUGIN_DIR . '/lookbook/images/' . UPLOAD_FOLDER_NAME_THUMB);

global  $admin_get_handlers,$admin_post_handlers,$config;
$admin_get_handlers = array('list_lookbook','add_lookbook');
$admin_post_handlers = array('store_lookbook','del_lookbooks','del_lookbook','check_isset_product','ajax_upload','search_product');
$config = array('width'=> 1024,
				'height' => 500,
				'thumb_width'=> 100,
				'thumb_height' => 100,
			);

function admin_js(){
    wp_register_style( 'bwp-lookbook-css', plugins_url( 'lookbook/admin/css/lookbook.css', __FILE__));
    wp_enqueue_style( 'bwp-lookbook-css' );
    wp_register_script( 'jquery-annotate-js', plugins_url( 'lookbook/admin/js/jquery.annotate.js', __FILE__) );
	wp_enqueue_script('jquery-annotate-js');	
}

function lookbook_style(){
	if (!wp_style_is('bwp_lookbook_css')) {
		wp_register_style( 'bwp_lookbook_css', plugins_url('/wpbingo/assets/css/bwp_lookbook.css') );
		wp_enqueue_style('bwp_lookbook_css');
	}	
}

function lookbook_add_menu() {
	add_submenu_page( 'wpbingo', esc_html__( 'LookBook', 'wpbingo' ), esc_html__( 'LookBook', 'wpbingo' ), 'manage_options', 'lookbook', 'bwp_dashboard');
}

if ( is_admin() ) {
    if( current_user_can('manage_options') ){
        require_once WPBINGO_LOOKBOOK_PLUGIN_DIR . '/lookbook/admin/admin.php';
    }
}

function lookbook_init() {
    do_action( 'lookbook_init' );
}

add_action('wp_print_scripts', 'load_slider_scripts');

function load_slider_scripts() {
    wp_enqueue_style ('lookbook');
}


/**
 * Init
 */
add_action( 'init', 'lookbook_init' );
add_action( 'admin_menu', 'lookbook_add_menu' );
add_action( 'admin_enqueue_scripts', 'admin_js' );
add_action( 'wp_enqueue_scripts', 'lookbook_style' );
