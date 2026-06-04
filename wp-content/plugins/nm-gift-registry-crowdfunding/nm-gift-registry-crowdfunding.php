<?php

/**
 * Plugin Name: NM Gift Registry and Wishlist - Crowdfunding Pro
 * Plugin URI: https://nmerimedia.com
 * Description: Allows items in a wishlist or gift registry to be crowdfunded and funded via free contributions. Adds coupon features to the wishlist. <a href="https://nmerimedia.com/product-category/plugins/" target="_blank">See more plugins&hellip;</a>
 * Author: Nmeri Media
 * Author URI: https://nmerimedia.com
 * License: Nmeri Media
 * License URI: https://nmerimedia.com/license
 * Version: 4.9
 * Domain Path: /languages
 * Docs URI: https://docs.nmerimedia.com/doc/crowdfunding-nm-gift-registry-and-wishlist/
 * Support URI: https://nmerimedia.com/contact/
 * Requires at least: 4.7
 * Requires PHP: 7.4
 */
defined( 'ABSPATH' ) || exit;

spl_autoload_register( function ( $class ) {
	$namespace = 'NMGRCF\\';

	if ( !class_exists( $class ) && false !== stripos( $class, $namespace ) ) {
		// Replace the namespace with the directory
		$path1 = str_replace( $namespace, trailingslashit( __DIR__ ) . 'includes/', $class );
		// Change the namespace separators to directory separators
		$path2 = str_replace( '\\', '/', $path1 );
		// Add the file extension
		$path = $path2 . '.php';

		if ( file_exists( $path ) ) {
			include_once $path;
		}
	} elseif ( !class_exists( $class ) && false !== stripos( $class, 'nmgrcf_' ) ) {
		$file = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		$filepath = trailingslashit( __DIR__ ) . 'includes/' . $file;

		if ( file_exists( $filepath ) ) {
			include_once $filepath;
		}
	}
} );

if ( !class_exists( NMGRCF::class ) ) {
	include_once 'includes/class-nmgrcf.php';
}

function nmgrcf() {
	return NMGRCF::get_instance( __FILE__ );
}

nmgrcf()->init();
