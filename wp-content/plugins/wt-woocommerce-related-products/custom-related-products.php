<?php

/**
 * Plugin Name:       Related Products for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/wt-woocommerce-related-products/
 * Description:       Displays custom related products based on category, tag, attribute or product for your WooCommerce store.
 * Version:           1.7.2
 * Author:            WebToffee
 * Author URI:        https://www.webtoffee.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wt-woocommerce-related-products
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'CRP_PLUGIN_URL' ) ) {
	define( 'CRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'CRP_PLUGIN_DIR' ) ) {
	define( 'CRP_PLUGIN_DIR', __DIR__ );
}
if ( ! defined( 'CRP_PLUGIN_DIR_PATH' ) ) {
	define( 'CRP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'CRP_PLUGIN_TEMPLATE_PATH' ) ) {
	define( 'CRP_PLUGIN_TEMPLATE_PATH', CRP_PLUGIN_DIR_PATH . 'public/partials' );
}


if ( ! defined( 'WT_CRP_BASE_NAME' ) ) {
	define( 'WT_CRP_BASE_NAME', plugin_basename( __FILE__ ) );
}
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WT_RELATED_PRODUCTS_VERSION', '1.7.2' );

/**
 *  @since 1.6.0
 *  Changelog in plugins page
 */
add_action( 'in_plugin_update_message-wt-woocommerce-related-products/custom-related-products.php', 'wt_crp_update_message', 10, 2 );

if ( ! function_exists( 'wt_crp_update_message' ) ) {
	function wt_crp_update_message( $data, $response ) {
		if ( isset( $data['upgrade_notice'] ) ) {
			add_action( 'admin_print_footer_scripts', 'wt_crp_plugin_screen_update_notice_js' );
			$msg = str_replace( array( '<p>', '</p>' ), array( '<div>', '</div>' ), $data['upgrade_notice'] );
			echo '<style type="text/css">
			#wt-woocommerce-related-products-update .update-message p:last-child{ display:none;}     
			#wt-woocommerce-related-products-update ul{ list-style:disc; margin-left:30px;}
			.wt_crp_update_message{ padding-left:30px;}
			</style>
			<div class="update-message wt_crp_update_message">' . wp_kses_post( wpautop( $msg ) ) . '</div>';

		}
	}
}

/**
 *  @since 1.6.0
 *  Javascript code for changelog in plugins page
 */
if ( ! function_exists( 'wt_crp_plugin_screen_update_notice_js' ) ) {
	function wt_crp_plugin_screen_update_notice_js() {
		global $pagenow;
		if ( 'plugins.php' !== $pagenow ) {
			return;
		}
		?>
		<script>
			( function( $ ){
				var update_dv=$('#wt-woocommerce-related-products-update');
				update_dv.find('.wt_crp_update_message').next('p').remove();
				update_dv.find('a.update-link:eq(0)').on('click', function(){
					$('.wt_crp_update_message').remove();
				});
			})( jQuery );
		</script>
		<?php
	}
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-custom-related-products-activator.php
 */
if ( ! function_exists( 'wt_crp_activate_custom_related_products' ) ) {
	function wt_crp_activate_custom_related_products() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-related-products-activator.php';
		Custom_Related_Products_Activator::activate();
	}
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-custom-related-products-deactivator.php
 */

if ( ! function_exists( 'wt_crp_deactivate_custom_related_products' ) ) {
	function wt_crp_deactivate_custom_related_products() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-related-products-deactivator.php';
		Custom_Related_Products_Deactivator::deactivate();
	}
}

register_activation_hook( __FILE__, 'wt_crp_activate_custom_related_products' );
register_deactivation_hook( __FILE__, 'wt_crp_deactivate_custom_related_products' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-custom-related-products.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-wt-relatedproducts-uninstall-feedback.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-wt-security-helper.php';

/**
 * @since    1.4.8
 *
 * Check if WooCommerce is active
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && ! array_key_exists( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins', array() ) ) ) ) { // deactive if woocommerce in not active
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		add_action( 'admin_notices', 'wt_rp_disabled_notice' );
		return;
}
if( !function_exists('wt_rp_disabled_notice') ) {
	function wt_rp_disabled_notice() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>Related Products</strong> requires WooCommerce to be active. You can download WooCommerce %s.', 'wt-woocommerce-related-products' ), '<a href="https://wordpress.org/plugins/woocommerce">' . __( 'here', 'wt-woocommerce-related-products' ) . '</a>' ) . '</p></div>';
	}
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if( !function_exists('run_custom_related_products')){
	 function run_custom_related_products() {

		$plugin = new Custom_Related_Products();
		$plugin->run();
	}

}

run_custom_related_products();