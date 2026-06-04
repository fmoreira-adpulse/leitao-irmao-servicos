<?php

namespace NMGR\Sub;

use NMGR\Sub\License;

defined( 'ABSPATH' ) || exit;

class Update extends License {

	/**
	 * @var \NMGR\Sub\Props
	 */
	private $plugin_props;
	protected $option_name = 'nmgr_licenses';
	protected $page_slug = 'nmgr-licenses';

	public function __construct( $plugin_props ) {
		$this->plugin_props = $plugin_props;

		add_filter( 'nmerimedia_packages', [ $this, 'set_packages' ] );
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 99 );
	}

	public function get_page_heading() {
		return __( 'License', 'nm-gift-registry' );
	}

	public function get_package_ids() {
		return apply_filters( 'nmerimedia_packages', parent::get_package_ids() );
	}

	public function set_packages( $packages ) {
		$packages[] = $this->plugin_props->basename;
		return apply_filters( 'nmgr_packages', $packages );
	}

	public function add_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=nm_gift_registry',
			__( 'License', 'nm-gift-registry' ) . ' - ' . $this->plugin_props->name,
			__( 'License', 'nm-gift-registry' ),
			'manage_nm_gift_registry_settings',
			$this->page_slug,
			array( $this, 'page_content' )
		);
	}

	public function get_page_url() {
		return menu_page_url( $this->page_slug, false );
	}

}
