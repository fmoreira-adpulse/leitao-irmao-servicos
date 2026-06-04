<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Props extends \NMGR_Props {

	/**
	 * @deprecated since version 4.6.0
	 */
	public function sub() {
		_deprecated_function( __METHOD__, '4.6.0' );
		return $this->account();
	}

	public function admin_post() {
		return new \NMGR\Sub\Admin_Post();
	}

	public function ajax() {
		return new \NMGR\Sub\Ajax();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function items_view( $wishlist = null ) {
		return new \NMGR\Sub\Items_View( $wishlist );
	}

	public function items_table( $wishlist ) {
		return new \NMGR\Sub\Tables\ItemsTable( $wishlist );
	}

	public function templates() {
		return new \NMGR\Sub\Templates();
	}

	public function wordpress() {
		return new \NMGR\Sub\Wordpress();
	}

	public function order() {
		return new \NMGR\Sub\Order();
	}

	public function wishlist_item( $item = 0 ) {
		$class = apply_filters( 'nmgr_wishlist_item_class', \NMGR\Sub\Wishlist_Item::class );
		return new $class( $item );
	}

	public function wishlist( $wishlist = 0 ) {
		$class = apply_filters( 'nmgr_wishlist_class', \NMGR\Sub\Wishlist::class );
		return new $class( $wishlist );
	}

	public function email( $section_id = '', $wishlist_id = 0 ) {
		return new \NMGR\Sub\Email( $section_id, $wishlist_id );
	}

	public function mailer() {
		return new \NMGR\Sub\Mailer();
	}

	public function account( $wishlist = false ) {
		return new \NMGR\Sub\Account( $wishlist );
	}

	public function add_to_wishlist() {
		return new \NMGR\Sub\AddToWishlist();
	}

}
