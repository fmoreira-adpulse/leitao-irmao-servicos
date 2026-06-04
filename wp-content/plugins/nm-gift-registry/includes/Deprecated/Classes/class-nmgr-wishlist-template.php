<?php

use NMGR\Lib\Single;

defined( 'ABSPATH' ) || exit;

class NMGR_Wishlist_Template extends Single {

	public function __construct( $id = false ) {
		_deprecated_function( __METHOD__, '4.7.0', 'NMGR\Lib\Single->construct()' );
		parent::__construct( $id );
	}

}
