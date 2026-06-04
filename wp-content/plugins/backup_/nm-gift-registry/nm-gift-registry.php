<?php

/**
 * Plugin Name: NM Gift Registry and Wishlist
 * Plugin URI: https://nmerimedia.com
 * Description: Advanced and highly customizable gift registry and wishlist plugin for your woocommerce store. <a href="https://nmerimedia.com/product-category/plugins/" target="_blank">See more plugins&hellip;</a>
 * Author: Nmeri Media
 * Author URI: https://nmerimedia.com
 * License: Nmeri Media
 * License URI: https://nmerimedia.com/license
 * Version: 4.13
 * Text Domain: nm-gift-registry
 * Domain Path: /languages/
 * Review URI: https://wordpress.org/support/plugin/nm-gift-registry-and-wishlist-lite/reviews?rate=5#new-post
 * Docs URI: https://docs.nmerimedia.com/doc/nm-gift-registry-and-wishlist/
 * Product URI: https://nmerimedia.com/product/nm-gift-registry
 * Support URI: https://nmerimedia.com/contact/
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 4.4.0
 * WC tested up to: 8.6.1
 */
defined( 'ABSPATH' ) || exit;

define( 'NMGR_FILE', __FILE__ );

function nm_gift_registry() {
	return NMGR_Install::get_plugin_props();
}

class NMGR_Install {

	/**
	 * @var NMGR_Setup
	 */
	private static $installer;

	/**
	 * Whether we have old crowdfunding plugin activated.
	 * @todo Remove in a later version
	 * @since version 4.3.0
	 * @var boolean
	 */
	public static $has_old_crowdfunding;

	public static function run() {
		if ( !class_exists( NMGR_Setup::class ) ) {
			include_once 'includes/class-nmgr-setup.php';
		}

		self::$installer = new NMGR_Setup( __FILE__ );
		self::$installer->load();

		/**
		 * @todo Remove in later version
		 * @since version 4.3.0
		 */
		self::$has_old_crowdfunding = function_exists( 'nmgrcf' ) && version_compare( nmgrcf()->version, '4.3.0', '<' );
	}

	public static function get_plugin_props() {
		return self::$installer->get_plugin_props();
	}

}

NMGR_Install::run();

/**
 * Legacy function to deactivate new pro version when old lite version is activated
 * @todo Remove in a later version
 * @since version 4.3.0
 */
add_action( 'activate_plugin', 'nmgr_activate_lite' );

function nmgr_activate_lite( $plugin ) {
	if ( function_exists( 'nm_gift_registry_lite' ) &&
		nm_gift_registry_lite()->basename === $plugin &&
		version_compare( nm_gift_registry_lite()->version, '4.3.0', '<' ) ) {
		deactivate_plugins( nm_gift_registry()->basename );
	}
}
