<?php

/**
 * Sync
 */
defined( 'ABSPATH' ) || exit;

use \NMGR\Deprecated\Utils;
use \NMGR\Settings\PluginProps;
use \NMGR\Lib\AddToWishlist;

class NMGR_Props extends PluginProps {

	public $requires_wc = '4.3.0';
	public $prefix = 'nmgrlite';
	public $requires_nmgrcf = '4.4.0';

	public function __construct( $filepath ) {
		parent::__construct( $filepath );

		$this->is_pro = false === strpos( $this->slug, 'lite' );
		$this->prefix = $this->is_pro ? 'nmgr' : $this->prefix;
		$this->is_licensed = $this->is_pro;
	}

	/**
	 * @todo Remove in later version
	 */
	public function __call( $name, $arguments ) {
		if ( in_array( $name, get_class_methods( Utils::class ) ) ) {
			_doing_it_wrong( $name, 'Use nmgr()->utils()->' . $name, '4.3.0' );
			return call_user_func_array( [ Utils::class, $name ], $arguments );
		} else {
			throw new \BadMethodCallException();
		}
	}

	public function utils() {
		return new Utils();
	}

	public function post_thumbnail_size() {
		return apply_filters( 'nmgr_medium_size', 190 );
	}

	/**
	 * @deprecated since version 4.7.0
	 * @todo Remove in version 5.0.0
	 */
	public function theme_path() {
		return apply_filters( 'nmgr_theme_path', trailingslashit( $this->slug ) );
	}

	public function template_path() {
		return plugin_dir_path( $this->file ) . 'templates/';
	}

	public function flush_rewrite_rules() {
		update_option( 'nmgr_flush_rewrite_rules', 1 );
	}

	/**
	 * @deprecated since version 4.6.0
	 */
	public function sub() {
		_deprecated_function( __METHOD__, '4.6.0' );
		return $this->account();
	}

	/**
	 * @return \NMGR_Admin_Post
	 */
	public function admin_post() {
		return new NMGR_Admin_Post();
	}

	/**
	 * @return \NMGR_Ajax
	 */
	public function ajax() {
		return new NMGR_Ajax();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function items_view( $wishlist = null ) {
		return new NMGR_Items_View( $wishlist );
	}

	public function items_table( $wishlist ) {
		return new \NMGR\Tables\ItemsTable( $wishlist );
	}

	/**
	 * @return \NMGR_Templates
	 */
	public function templates() {
		return new NMGR_Templates();
	}

	/**
	 * @return \NMGR_Wordpress
	 */
	public function wordpress() {
		return new NMGR_Wordpress();
	}

	/**
	 * @return \NMGR_Order
	 */
	public function order() {
		return new NMGR_Order();
	}

	/**
	 * @param type $item
	 * @return \NMGR_Wishlist_Item
	 */
	public function wishlist_item( $item = 0 ) {
		$class = apply_filters( 'nmgr_wishlist_item_class', NMGR_Wishlist_Item::class );
		return new $class( $item );
	}

	/**
	 * @param type $wishlist
	 * @return \NMGR_Wishlist
	 */
	public function wishlist( $wishlist = 0 ) {
		$class = apply_filters( 'nmgr_wishlist_class', NMGR_Wishlist::class );
		return new $class( $wishlist );
	}

	/**
	 * @param type $wishlist
	 * @return \NMGR_Account
	 */
	public function account( $wishlist = false ) {
		return new NMGR_Account( $wishlist );
	}

	/**
	 * @return \NMGR\Settings\GiftRegistry
	 */
	public function gift_registry_settings() {
		return new \NMGR\Settings\GiftRegistry( $this );
	}

	/**
	 * @return \NMGR\Settings\Wishlist
	 */
	public function wishlist_settings() {
		return new \NMGR\Settings\Wishlist( $this );
	}

	public function add_to_wishlist() {
		return new AddToWishlist();
	}

}
