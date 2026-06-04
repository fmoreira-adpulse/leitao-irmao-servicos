<?php

namespace NMGR\Sub\Fields;

use NMGR\Fields\AccountFields as Lite;

defined( 'ABSPATH' ) || exit;

class AccountFields extends Lite {

	/**
	 * Hack to make current version compatibile with nmgrcf <= 4.6
	 * @todo Remove in version 5.0.0
	 */
	public function is_gift_registry() {
		return $this->account->is_gift_registry();
	}

	/**
	 * Hack to make current version compatibile with nmgrcf <= 4.6
	 * @todo Remove in version 5.0.0
	 */
	public function get_wishlist() {
		return $this->account->get_wishlist();
	}

	protected function data() {
		$sections = parent::data();
		$is_gift_registry = $this->account->is_gift_registry();

		$pro_sections = [
			'images' => [
				'title' => nmgr()->is_pro ?
				__( 'Images', 'nm-gift-registry' ) :
				__( 'Images', 'nm-gift-registry-lite' ),
				'priority' => 40,
				'content' => [ $this->account, 'get_images_section' ],
				'show' => $is_gift_registry,
			],
			'orders' => [
				'title' => nmgr()->is_pro ?
				__( 'Orders', 'nm-gift-registry' ) :
				__( 'Orders', 'nm-gift-registry-lite' ),
				'priority' => 55,
				'content' => [ $this->account, 'get_orders_section' ],
				'show_for_user_only' => true,
				'show' => $is_gift_registry,
			],
			'messages' => [
				'title' => nmgr()->is_pro ?
				__( 'Messages', 'nm-gift-registry' ) :
				__( 'Messages', 'nm-gift-registry-lite' ),
				'priority' => 60,
				'content' => [ $this->account, 'get_messages_section' ],
				'show_for_user_only' => true,
				'show' => $is_gift_registry && nmgr_get_option( 'enable_messages' ),
			],
			'settings' => [
				'title' => nmgr()->is_pro ?
				__( 'Settings', 'nm-gift-registry' ) :
				__( 'Settings', 'nm-gift-registry-lite' ),
				'priority' => 70,
				'content' => [ $this->account, 'get_settings_section' ],
				'show_for_user_only' => true,
			],
		];

		return array_merge( $sections, $pro_sections );
	}

}
