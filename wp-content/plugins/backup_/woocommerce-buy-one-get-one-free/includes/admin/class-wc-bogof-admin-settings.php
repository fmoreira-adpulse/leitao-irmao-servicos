<?php
/**
 * WooCommerce Buy One Get One Free admin settings
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Admin_Settings Class
 */
class WC_BOGOF_Admin_Settings {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'create_page' ) );
	}

	/**
	 * Add settings page to the dashboard menu.
	 */
	public static function create_page() {
		if ( ! is_admin() || empty( $_GET['page'] ) || 'shop_bogof_rule_settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Create page only for valid ID.
			return;
		}

		$page = add_submenu_page(
			'woocommerce',
			__( 'Settings', 'wc-buy-one-get-one-free' ),
			__( 'Settings', 'wc-buy-one-get-one-free' ),
			'manage_woocommerce',
			'shop_bogof_rule_settings',
			array( __CLASS__, 'output' )
		);

		// Save settings.
		add_action( 'admin_init', array( __CLASS__, 'save' ) );

		// Remove the page after create it.
		add_action( 'admin_head', array( __CLASS__, 'remove_page' ) );

		// WC_Admin_Settings is required.
		if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
			include WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		}
	}

	/**
	 * Handle saving of settings.
	 *
	 * @return void
	 */
	public static function save() {
		// We should only save on the settings page.
		if ( empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		check_admin_referer( 'wc-bogo-settings' );

		WC_Admin_Settings::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );

		$settings = self::get_settings();
		$data     = wc_clean( $_POST );

		woocommerce_update_options( $settings, $data );
	}

	/**
	 * Remove the page from menu.
	 */
	public static function remove_page() {
		remove_submenu_page( 'woocommerce', 'shop_bogof_rule_settings' );
	}

	/**
	 * Output the HTML for the settings.
	 */
	public static function output() {
		$settings = self::get_settings();
		include dirname( __FILE__ ) . '/views/html-settings-page.php';
	}

	/**
	 * Return settings array.
	 *
	 * @return array
	 */
	private static function get_settings() {
		return array(
			array(
				'title' => __( 'Choose your gift layout', 'wc-buy-one-get-one-free' ),
				'type'  => 'title',
			),
			array(
				'title'    => __( 'Display eligible free gift(s) on', 'wc-buy-one-get-one-free' ),
				'desc_tip' => __( 'Where do you want to show the free eligible products?', 'wc-buy-one-get-one-free' ),
				'id'       => 'wc_bogof_cyg_display_on',
				'type'     => 'radio',
				'options'  => array(
					'after_cart'  => __( 'After the cart', 'wc-buy-one-get-one-free' ),
					/* Translators: %s Page contents. */
					'custom_page' => sprintf( __( 'A page that contains the %s shortcode', 'wc-buy-one-get-one-free' ), '[wc_choose_your_gift]' ),
				),
				'default'  => 'after_cart',
			),
			array(
				'title'       => __( 'Title', 'wc-buy-one-get-one-free' ),
				'desc'        => __( 'The title of the "choose your gift" area.', 'wc-buy-one-get-one-free' ),
				'id'          => 'wc_bogof_cyg_title',
				'type'        => 'text',
				'css'         => 'min-width:300px;',
				'placeholder' => __( 'Choose your gift', 'wc-buy-one-get-one-free' ),
				'desc_tip'    => true,
			),
			array(
				'title'    => __( 'Choose your gift page', 'wc-buy-one-get-one-free' ),
				/* Translators: %s Page contents. */
				'desc_tip' => sprintf( __( 'Page contents: %s', 'wc-buy-one-get-one-free' ), '[wc_choose_your_gift]' ),
				'id'       => 'wc_bogof_cyg_page_id',
				'type'     => 'single_select_page',
				'default'  => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
				'desc'     => __( 'This page needs to be set so that WooCommerce knows where to send users to choose the free products.', 'wc-buy-one-get-one-free' ),
			),
			array(
				'type' => 'sectionend',
			),
			array(
				'title' => __( 'Choose your gift notice', 'wc-buy-one-get-one-free' ),
				'type'  => 'title',
			),
			array(
				'title'       => __( 'Message', 'wc-buy-one-get-one-free' ),
				'desc'        => __( 'Message of the notice to show customer when there are eligible free products. Use [qty] for the number of items.', 'wc-buy-one-get-one-free' ),
				'id'          => 'wc_bogof_cyg_notice',
				'type'        => 'text',
				'css'         => 'min-width:300px;',
				/* Translators: %s [qty] placeholder. */
				'placeholder' => sprintf( __( 'You can now add %s product(s) for free to the cart.', 'wc-buy-one-get-one-free' ), '[qty]' ),
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Button text', 'wc-buy-one-get-one-free' ),
				'id'          => 'wc_bogof_cyg_notice_button_text',
				'type'        => 'text',
				'css'         => 'min-width:300px;',
				'placeholder' => __( 'Choose your gift', 'wc-buy-one-get-one-free' ),
			),
			array(
				'type' => 'sectionend',
			),
			array(
				'title' => __( 'Advanced', 'wc-buy-one-get-one-free' ),
				'type'  => 'title',
			),
			array(
				'title'   => __( 'On sale products', 'wc-buy-one-get-one-free' ),
				'desc'    => __( 'Calculate the discount using the "regular price".', 'wc-buy-one-get-one-free' ),
				'id'      => 'wc_bogof_base_regular_price',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Disable coupons', 'wc-buy-one-get-one-free' ),
				'desc'    => __( 'Disable coupons usage if there is a free BOGO item in the cart.', 'wc-buy-one-get-one-free' ),
				'id'      => 'wc_bogof_disable_coupons',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Custom attributes', 'wc-buy-one-get-one-free' ),
				'desc'    => __( 'Include the custom product attributes in the "Variation attribute" condition.', 'wc-buy-one-get-one-free' ),
				'id'      => 'wc_bogof_include_custom_attributes',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'type' => 'sectionend',
			),
		);
	}
}
