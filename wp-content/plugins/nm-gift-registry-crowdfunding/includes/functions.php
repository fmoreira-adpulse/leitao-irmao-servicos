<?php

/**
 * @sync
 */
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

function nmgrcf_get_item( $item_id ) {
	_deprecated_function( __FUNCTION__, '4.2.0', 'nmgr_get_wishlist_item' );
	return nmgr_get_wishlist_item( $item_id );
}

/**
 * @return NMGRCF_Wishlist|false
 */
function nmgrcf_get_wishlist( $wishlist_id = 0, $active = false ) {
	_deprecated_function( __FUNCTION__, '4.2.0', 'nmgr_get_wishlist' );
	return nmgr_get_wishlist( $wishlist_id, $active );
}

function nmgrcf_price_box( $args = array() ) {
	$defaults = array(
		'name' => 'nmgr-cf-price',
		'min' => 0,
		'max' => '',
		'title' => __( 'Amount', 'nm-gift-registry-crowdfunding' ),
		'id' => '',
		'value' => '',
		'currency-symbol-border' => false,
	);

	$params = wp_parse_args( $args, $defaults );
	?>
	<div class="nmgrcf-price-box">
		<span class="currency-symbol <?php echo $params[ 'currency-symbol-border' ] ? 'border' : ''; ?>">
			<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
		</span>
		<input type="number" step="any" class="price" name="<?php echo esc_attr( $params[ 'name' ] ); ?>"
		<?php
		if ( $params[ 'id' ] ) {
			echo 'id="' . esc_attr( $params[ 'id' ] ) . '"';
		}
		?>
					 min="<?php echo esc_attr( $params[ 'min' ] ); ?>"
					 max="<?php echo esc_attr( $params[ 'max' ] ); ?>"
					 max="<?php echo esc_attr( $params[ 'max' ] ); ?>"
					 value="<?php echo esc_attr( $params[ 'value' ] ); ?>"
					 title="<?php echo esc_attr( $params[ 'title' ] ); ?>">
	</div>
	<?php
}

/**
 * Check if the specified coupon is for the wallet amount
 *
 * @param int|WC_Coupon $coupon_id The coupon id or object
 * @deprecated since version 4.5.0
 * @return boolean
 */
function nmgrcf_coupon_is_for_wallet( $coupon_id ) {
	_deprecated_function( __FUNCTION__, '4.5.0' );
	$coupon = new WC_Coupon( $coupon_id );
	return $coupon->get_id() && !empty( $coupon->get_meta( 'nmgrcf_coupon_from_wallet' ) ) ? true : false;
}

function nmgrcf_round( $amt ) {
	return round( ( float ) $amt, wc_get_price_decimals() );
}

/**
 * Check whether the cart has a crowdfund contribution for a wishlist item
 * @return boolean|int false if it doesn't and the id of the wishlist if it does.
 */
function nmgrcf_cart_has_crowdfund_contribution() {
	if ( is_a( wc()->cart, 'WC_Cart' ) && !WC()->cart->is_empty() ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$nmgr_cf_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
			if ( $nmgr_cf_data && nmgr_get_wishlist( $nmgr_cf_data[ 'wishlist_id' ], true ) ) {
				return ( int ) $nmgr_cf_data[ 'wishlist_id' ];
			}
		}
	}
	return false;
}

/**
 * Check whether the cart has a free contribution for a wishlist
 * @return boolean|int false if it doesn't and the id of the wishlist if it does.
 */
function nmgrcf_cart_has_free_contribution() {
	if ( is_a( wc()->cart, 'WC_Cart' ) && !WC()->cart->is_empty() ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
			if ( $nmgr_fc_data && nmgr_get_wishlist( $nmgr_fc_data[ 'wishlist_id' ], true ) ) {
				return ( int ) $nmgr_fc_data[ 'wishlist_id' ];
			}
		}
	}
	return false;
}

function nmgrcf_get_template( $name, $args = array() ) {
	ob_start();
	extract( $args );
	include nmgrcf()->path . 'templates/' . $name;
	return ob_get_clean();
}

/**
 * Get the name of the product used to show the crowdfund contribution in the cart
 * @param WC_Cart Item data $cart_item Array of cart item properties
 */
function nmgrcf_get_crowdfund_cart_item_name( $cart_item ) {
	$nmgr_cf_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
	if ( !empty( $nmgr_cf_data[ 'product_name' ] ) ) {
		return $nmgr_cf_data[ 'product_name' ] . ' - ' . apply_filters( 'nmgrcf_product_name_description',
				__( 'Crowdfund Contribution', 'nm-gift-registry-crowdfunding' ) );
	}
}

function nmgrcf_get_free_contributions_settings_dialog_template( $wishlist_id ) {
	if ( !is_nmgr_admin() && 0 < $wishlist_id && !nmgr_user_has_wishlist( $wishlist_id ) ) {
		return;
	}

	$wishlist = nmgr_get_wishlist( $wishlist_id, true );

	if ( !$wishlist ) {
		return;
	}

	$vars = array(
		'settings' => $wishlist->get_free_contributions_settings(),
		'wishlist' => $wishlist,
	);

	$modal = nmgr_get_modal();
	$modal->set_id( 'nmgr-free-contributions-settings-dialog-' . $wishlist_id );
	$modal->set_title( __( 'Free contributions settings', 'nm-gift-registry-crowdfunding' ) );
	$modal->set_content( nmgrcf_get_template( 'dialogs/free-contributions-settings.php', $vars ) );
	$modal->set_footer( $modal->get_save_button( [
			'attributes' => [
				'type' => 'submit',
				'class' => [
					'button-primary',
					'nmgrcf-free-contributions-settings-submit',
				],
				'form' => 'nmgrcf-fc-settings-form'
			]
	] ) );
	return $modal->get();
}

/**
 * Get the text to display in the submit button for crowdfunding an item
 * @return string
 */
function nmgrcf_get_crowdfund_item_button_text() {
	return apply_filters( 'nmgrcf_crowdfund_item_button_text', __( 'Contribute', 'nm-gift-registry-crowdfunding' ) );
}

/**
 * Get the id of the placeholder product used by the plugin for various add to cart actions
 * (Creates the placeholder product if it doesn't exist)
 *
 * @return int|null
 */
function nmgrcf_get_placeholder_product_id() {
	$placeholder_product_id = get_option( 'nmgrcf_product_id' );

	if ( !wc_get_product( $placeholder_product_id ) ) {
		$p = new WC_Product();
		$p->set_name( __( 'Contribution', 'nm-gift-registry-crowdfunding' ) );
		$p->set_status( 'nmgr-crowdfunded' );
		$p->set_virtual( true );
		$p->set_catalog_visibility( 'hidden' );
		$p->set_sold_individually( true );
		$p->set_regular_price( 0 );
		$p->set_tax_status( 'none' );
		$placeholder_product_id = $p->save();

		if ( $placeholder_product_id ) {
			update_option( 'nmgrcf_product_id', $placeholder_product_id );
		}
	}

	return $placeholder_product_id;
}

/**
 * Get the name of the product used to show the crowdfund contribution in the cart
 * @param WC_Cart Item data $cart_item Array of cart item properties
 */
function nmgrcf_get_free_contribution_cart_item_name( $cart_item ) {
	$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
	if ( $nmgr_fc_data && nmgr_get_wishlist( $nmgr_fc_data[ 'wishlist_id' ] ) ) {
		return apply_filters( 'nmgrcf_free_contribution_cart_item_name',
			sprintf(
				/* translators: %s: wishlist title */
				__( 'Free Contribution - %s', 'nm-gift-registry-crowdfunding' ),
				nmgr_get_wishlist( $nmgr_fc_data[ 'wishlist_id' ] )->get_title()
			),
			$cart_item
		);
	}
}

if ( !function_exists( 'nmgrcf_get_free_contributions_template' ) ) {

	function nmgrcf_get_free_contributions_template( $id ) {
		_deprecated_function( __FUNCTION__, '4.5.1', 'nmgr_get_account_section' );
		return nmgr_get_account_section( 'free_contributions', $id );
	}

}

function nmgrcf_get_wallet_template( $id ) {
	_deprecated_function( __FUNCTION__, '4.5.1', 'nmgr_get_account_section' );
	return nmgr_get_account_section( 'wallet', $id );
}

function nmgrcf_get_wallet_account_section( $vars ) {
	_deprecated_function( __FUNCTION__, '4.6' );

	$wishlist = $vars[ 'wishlist' ];

	if ( !$wishlist ) {
		return;
	}

	ob_start();
	?>
	<div <?php echo nmgr_format_attributes( $vars[ 'attributes' ] ?? [] ); ?>>
		<div class="nmgr-text-center nmgrcf-amount-in-wallet-display">
			<?php echo wp_kses_post( wc_price( $wishlist->get_wallet_balance() ) ); ?>
		</div>
		<div class="nmgrcf-wallet-actions nmgr-text-center">
			<?php if ( is_nmgr_admin() ) : ?>
				<p>
					<?php echo wp_kses_post( nmgrcf_get_view_wallet_log_button( $wishlist->get_id() ) ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_view_wallet_dialog_template( $wishlist_id ) {
	_deprecated_function( __FUNCTION__, '4.8' );
	if ( !is_nmgr_admin() && !nmgr_user_has_wishlist( $wishlist_id ) ) {
		return;
	}

	$wishlist = nmgr_get_wishlist( $wishlist_id, true );

	if ( !$wishlist ) {
		return;
	}

	$content = nmgrcf_get_wallet_template( $wishlist->get_id() );

	$modal = nmgr_get_modal();
	$modal->set_id( 'nmgr-wallet-dialog-' . $wishlist_id );
	$modal->set_title( __( 'Amount in wallet', 'nm-gift-registry-crowdfunding' ) );
	$modal->set_content( $content );
	return $modal->get();
}

function nmgrcf_get_wallet_log_dialog_template( $wishlist_id ) {
	if ( !is_nmgr_admin() && !nmgr_user_has_wishlist( $wishlist_id ) ) {
		return;
	}

	$wallet = new NMGRCF_Wallet( $wishlist_id );

	if ( !$wallet->get_wishlist() ) {
		return;
	}

	$wallet_log = nmgrcf_get_template( 'wallet-log.php', array(
		'wishlist' => nmgr_get_wishlist( $wishlist_id ),
		'wallet' => $wallet,
		'log' => $wallet->get_log(),
		'class' => 'woocommerce',
		'columns' => array(
			'id' => __( 'ID', 'nm-gift-registry-crowdfunding' ),
			'amount' => __( 'Amount', 'nm-gift-registry-crowdfunding' ),
			'type' => __( 'Transaction type', 'nm-gift-registry-crowdfunding' ),
			'descriptor' => __( 'Description', 'nm-gift-registry-crowdfunding' ),
			'date' => __( 'Date', 'nm-gift-registry-crowdfunding' ),
		),
		) );

	$modal = nmgr_get_modal();
	$modal->set_id( 'nmgr-wallet-log-dialog-' . $wishlist_id );
	$modal->set_title( __( 'Wallet log', 'nm-gift-registry-crowdfunding' ) );
	$modal->set_content( $wallet_log );
	$modal->make_large();
	return $modal->get();
}

/**
 * Is the crowdfunding module enabled
 * @return boolean
 */
function is_nmgrcf_crowdfunding_enabled() {
	return ( bool ) apply_filters( 'is_nmgrcf_crowdfunding_enabled', nmgr_get_option( 'enable_crowdfunding', 1 ) );
}

/**
 * Is the free contributions module enabled
 * @deprecated since 4.1.0
 * @return boolean
 */
function is_nmgrcf_free_contributions_module_enabled() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
	return class_exists( 'NMGRCF_Templates_Free_Contribution' );
}

/**
 * Is the free contributions enabled
 * @return boolean
 */
function is_nmgrcf_free_contributions_enabled() {
	return ( bool ) apply_filters( 'is_nmgrcf_free_contributions_enabled', nmgr_get_option( 'enable_free_contributions', 1 ) );
}

/**
 * Is the coupons module enabled
 * @return boolean
 */
function is_nmgrcf_coupons_enabled() {
	_deprecated_function( __FUNCTION__, '4.5.0' );
	if ( wc_coupons_enabled() && class_exists( 'NMGRCF_Coupon' ) ) {
		return ( bool ) apply_filters( 'is_nmgrcf_coupons_enabled', true );
	}
	return false;
}

function is_nmgrcf_enabled() {
	return is_nmgrcf_crowdfunding_enabled() ||
		is_nmgrcf_free_contributions_enabled() ||
		nmgr_get_option( 'enable_wallet_transfer_all' );
}

/**
 * Is the wallet module enabled
 * @deprecated since version 4.1.0
 * @return boolean
 */
function is_nmgrcf_wallet_enabled() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
	return ( bool ) apply_filters( 'is_nmgrcf_wallet_enabled', is_nmgrcf_enabled() );
}

function nmgrcf_get_view_wallet_log_button( $wishlist_id ) {
	ob_start();
	?>
	<button type="button"
					class="button nmgrcf-post-action nmgrcf-view-wallet-log-btn"
					data-wishlist_id="<?php echo esc_attr( $wishlist_id ); ?>"
					data-nmgr_post_action="show_wallet_log_dialog">
						<?php
						echo esc_html( __( 'View log', 'nm-gift-registry-crowdfunding' ) );
						?>
	</button>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_view_wallet_button( $wishlist_id ) {
	_deprecated_function( __FUNCTION__, '4.5.0' );
	ob_start();
	?>
	<button type="button"
					class="button nmgrcf-view-wallet-btn nmgrcf-post-action"
					data-nmgr_post_action="show_view_wallet_dialog"
					data-wishlist_id="<?php echo esc_attr( $wishlist_id ); ?>">
						<?php
						echo esc_html( __( 'View wallet', 'nm-gift-registry-crowdfunding' ) );
						?>
	</button>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_reset_wallet_button( $wishlist_id ) {
	_deprecated_function( __FUNCTION__, '4.5.0' );

	ob_start();

	$wishlist = nmgr_get_wishlist( $wishlist_id );

	if ( !$wishlist || $wishlist->get_wallet()->has_zero_balance() ) {
		$disabled = 'disabled';
	}

	$block = [ '#nmgr-wallet' ];
	$acc = nmgr()->account( $wishlist )->set_section( 'wallet' );
	$section = $acc->get_section_data();
	if ( !empty( $section[ 'replace_on_load' ] ) ) {
		foreach ( $section[ 'replace_on_load' ] as $rep ) {
			$block[] = "#nmgr-$rep";
		}
	}
	?>
	<button type="button"
					class="button nmgrcf-post-action nmgrcf-reset-wallet-btn"
					data-wishlist_id="<?php echo esc_attr( $wishlist_id ); ?>"
					data-nmgr_post_action="reset_wallet_action"
					data-nmgr_block="<?php echo htmlspecialchars( json_encode( $block ) ); ?>"
					data-notice="<?php
					echo esc_attr( __( 'Resetting the wallet would set the amount in the wallet to zero. Any amount previously available would be lost. Are you sure you want to continue?', 'nm-gift-registry-crowdfunding' ) );
					?>"
					<?php echo isset( $disabled ) ? esc_attr( $disabled ) : ''; ?>>
						<?php
						echo esc_html( __( 'Reset wallet', 'nm-gift-registry-crowdfunding' ) );
						?>
	</button>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_create_coupon_button( $wishlist_id, $coupon_from_wallet = false ) {
	_deprecated_function( __FUNCTION__, '4.5.0' );
	if ( !is_nmgrcf_coupons_enabled() ) {
		return;
	}

	ob_start();

	$wishlist = nmgr_get_wishlist( $wishlist_id );

	$title = __( 'Create a coupon to offer discounts on the remaining wishlist items which have not yet been fulfilled.', 'nm-gift-registry-crowdfunding' );

	if ( $coupon_from_wallet ) {
		$title = __( 'Create a coupon from the amount in the wallet that can be used on normal wishlist items.', 'nm-gift-registry-crowdfunding' );
	}

	if ( !$wishlist || !$wishlist->has_items() || $wishlist->is_fulfilled() ||
		($coupon_from_wallet && $wishlist->get_wallet() && !$wishlist->get_wallet()->has_positive_balance()) ) {
		$disabled = 'disabled';
	}
	?>
	<button type="button"
					class="button nmgrcf-create-coupon-btn nmgrcf-post-action nmgr-tip"
					data-wishlist_id="<?php echo esc_attr( $wishlist->get_id() ); ?>"
					data-nmgr_post_action="show_create_coupon_dialog"
					<?php echo $coupon_from_wallet ? 'data-coupon_from_wallet="1"' : ''; ?>
					title="<?php echo esc_attr( $title ); ?>"
					<?php echo isset( $disabled ) ? esc_attr( $disabled ) : ''; ?>>
						<?php
						echo esc_html( __( 'Create coupon', 'nm-gift-registry-crowdfunding' ) );
						?>
	</button>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_item_maintain_crowdfund_status_notice( $item ) {
	$maintain_crowdfund_status_notice = '';

	if ( is_a( $item, 'NMGRCF_Item' ) && $item->maintain_crowdfund_status() ) {
		if ( $item->is_fulfilled() ) {
			$maintain_crowdfund_status_notice = __( 'This item is already fulfilled so there is no need to change its crowdfund status.', 'nm-gift-registry-crowdfunding' );
		} elseif ( $item->is_purchased() ) {
			$maintain_crowdfund_status_notice = __( 'This item already has purchases and so cannot be crowdfunded.', 'nm-gift-registry-crowdfunding' );
		} elseif ( $item->get_crowdfund_amount_available() ) {
			$maintain_crowdfund_status_notice = __( 'This item already has crowdfund contributions and so cannot be uncrowdfunded.', 'nm-gift-registry-crowdfunding' );
		}
	}

	return $maintain_crowdfund_status_notice;
}

function nmgrcf_get_free_contributions_settings_button( $wishlist_id ) {
	ob_start();
	?>
	<a href="#"
		 class="nmgrcf-post-action nmgrcf-fc-settings-button nmgr-tip nmgr-icon gear"
		 data-nmgr_post_action="show_free_contributions_settings_dialog"
		 title="<?php esc_html_e( 'Settings', 'nm-gift-registry-crowdfunding' ); ?>"
		 data-wishlist_id="<?php echo esc_attr( $wishlist_id ); ?>">
		&#9881;&#xFE0E;
	</a>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_order_item_wallet_purchase_notice() {
	return sprintf(
		/* translators: %s: wishlist type title */
		__( 'Items in this order were purchased with the %s wallet.', 'nm-gift-registry-crowdfunding' ),
		nmgr_get_type_title()
	);
}

function nmgrcf_order_contains_wallet_purchased_item( $order ) {
	$val = false;
	foreach ( $order->get_items() as $item ) {
		if ( $item->get_meta( '_nmgrcf_wallet_purchase' ) ) {
			$val = true;
			break;
		}
	}
	return $val;
}

if ( !function_exists( 'nmgrcf_get_crowdfunds_template' ) ) {


	function nmgrcf_get_crowdfunds_template( $id ) {
		_deprecated_function( __FUNCTION__, '4.5.1', 'nmgr_get_account_section' );
		return nmgr_get_account_section( 'crowdfunds', $id );
	}

}

function is_nmgrcf_cot() {
	return OrderUtil::custom_orders_table_usage_is_enabled();
}

function nmgrcf_orders_table() {
	global $wpdb;
	if ( is_nmgrcf_cot() ) {
		$order_table_ds = wc_get_container()->get( OrdersTableDataStore::class );
		return $order_table_ds::get_orders_table_name();
	} else {
		return $wpdb->posts;
	}
}

function nmgrcf_get_order_wishlist_item_ids( $order_id ) {
	global $wpdb;

	$ids = $wpdb->get_col( "
			SELECT DISTINCT meta_value
			FROM {$wpdb->prefix}woocommerce_order_itemmeta as oim
			LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON oim.order_item_id = oi.order_item_id
			WHERE oi.order_id = $order_id
			AND oim.meta_key = 'nmgrcf_item_id'
			" );

	return array_filter( ( array ) $ids );
}

/**
 * Check if the registered version of nm gift registry is greater or equal to a specific version
 * @param string $version_number The number to test with
 * @return boolean
 */
function is_nmgrcf_nmgr_minimum_version( $version_number ) {
	return version_compare( nmgr()->version, $version_number, '>=' );
}
