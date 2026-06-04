<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get an email template file from the templates path
 *
 * Automatically determines whether to get a plain or html template file
 *
 * @param string $name The base name of the template file to get (without any prefix)
 * @param array $args Variables to send to the template file
 * @param NMGR\Sub\Email $email The email object containing properties of the email
 * @return string Template html
 */
function nmgr_get_email_template( $name, $args, $email ) {
	_deprecated_function( __FUNCTION__, '4.8.0' );
	$real_name = ('plain' === $email->get_email_type()) ? "emails/plain/$name" : "emails/$name";
	return $email->get_content_template( $real_name, false );
}

if ( !function_exists( 'nmgr_get_images_template' ) ) {

	function nmgr_get_images_template( $id, $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.7.0', 'nmgr_get_account_section' );
		$template = nmgr_get_account_section( 'images', $id );
		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

if ( !function_exists( 'nmgr_get_messages_template' ) ) {

	function nmgr_get_messages_template( $id, $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.7.0', 'nmgr_get_account_section' );
		$template = nmgr_get_account_section( 'messages', $id );
		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

if ( !function_exists( 'nmgr_get_orders_template' ) ) {

	function nmgr_get_orders_template( $id, $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.7.0', 'nmgr_get_account_section' );
		$template = nmgr_get_account_section( 'orders', $id );
		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

if ( !function_exists( 'nmgr_get_settings_template' ) ) {

	function nmgr_get_settings_template( $id, $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.7.0', 'nmgr_get_account_section' );
		$template = nmgr_get_account_section( 'settings', $id );
		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

/**
 * Whether a product is allowed to be added to the wishlist
 *
 * This is based on whether the product or its category is included or exclude in the plugin settings
 *
 * @param WC_Product $product
 * @return boolean
 */
function is_nmgr_product_add_to_wishlist_allowed( $product ) {
	if ( !is_object( $product ) ) {
		return false;
	}

	$add_to_wishlist = true;
	$product_cats = wc_get_product_cat_ids( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() );
	$product_ids = array( $product->get_id(), $product->get_parent_id() );
	$include_product_ids = ( array ) nmgr_get_option( 'add_to_wishlist_include_products' );
	$exclude_product_ids = ( array ) nmgr_get_option( 'add_to_wishlist_exclude_products' );
	$include_product_cats = ( array ) nmgr_get_option( 'add_to_wishlist_include_categories' );
	$exclude_product_cats = ( array ) nmgr_get_option( 'add_to_wishlist_exclude_categories' );

	if ( count( $include_product_ids ) && !count( array_intersect( $product_ids, $include_product_ids ) ) ) {
		$add_to_wishlist = false;
	}

	if ( count( $include_product_cats ) && !count( array_intersect( $product_cats, $include_product_cats ) ) ) {
		$add_to_wishlist = false;
	}

	if ( count( $exclude_product_ids ) && count( array_intersect( $product_ids, $exclude_product_ids ) ) ) {
		$add_to_wishlist = false;
	}

	if ( count( $exclude_product_cats ) && count( array_intersect( $product_cats, $exclude_product_cats ) ) ) {
		$add_to_wishlist = false;
	}

	return apply_filters( 'is_nmgr_product_add_to_wishlist_allowed', $add_to_wishlist, $product );
}

/**
 * Check if items have been restricted from being added to the cart based on whether
 * they are wishlist or normal items
 *
 * @return boolean True if the restrict option is set in the admin and the cart is not empty.
 */
function is_nmgr_cart_restricted() {
	$val = ( bool ) is_a( wc()->cart, 'WC_Cart' ) && !wc()->cart->is_empty() && nmgr_get_option( 'cart_prevent_mixed_items' );
	return apply_filters( 'is_nmgr_cart_restricted', $val );
}

/**
 * Check if wishlist items have been restricted from being added to the cart
 *
 * @return boolean True if the restrict option is set and there are already non-wishlist items in the cart.
 */
function nmgr_restrict_wishlist_items_from_cart() {
	return is_nmgr_cart_restricted() && ( bool ) !nmgr_get_wishlist_in_cart();
}

/**
 * Check if non-wishlist items have been restricted from being added to the cart
 *
 * @return boolean True if the restrict option is set and there are already wishlist items in the cart
 */
function nmgr_restrict_normal_items_from_cart() {
	return is_nmgr_cart_restricted() && ( bool ) nmgr_get_wishlist_in_cart();
}

/**
 * Check if items in cart are shipping to a wishlist's owner's address
 *
 * This function is only useful on the frontend and would only return the correct value if the cart has wishlist items.
 * Otherwise it would always return false.
 *
 * @param boolean $return_bool Whether to return a boolean value (true), or the
 * array of wishlist ids in the cart.
 * @return boolean|array Boolean or the array of wishlist ids in the cart if cart items
 * are shipping to the wishlist address and $return_bool is false, else false.
 */
function is_nmgr_cart_shipping_to_wishlist_address( $return_bool = true ) {
	$wishlist_ids = nmgr_get_wishlists_in_cart();

	$bool = ( bool ) (is_nmgr_shipping_to_wishlist_address() && !empty( $wishlist_ids ));

	$val = ($bool && !$return_bool) ? $wishlist_ids : $bool;
	return apply_filters( 'is_nmgr_cart_shipping_to_wishlist_address', $val, $return_bool );
}

/**
 * Check if purchased wishlist items should be shipping to the wishlist's owner's address
 * @return bool
 */
function is_nmgr_shipping_to_wishlist_address() {
	return nmgr_get_type_option( 'gift-registry', 'enable' ) &&
		nmgr_get_type_option( 'gift-registry', 'enable_shipping' ) &&
		nmgr_get_type_option( 'gift-registry', 'shipping_to_wishlist_address' );
}

/**
 * Check if an order is shipping to the wishlists' addresses
 *
 * This function is only useful after an order has been created when the order exists.
 *
 * @param WC_Order $order Order object or id
 * @param boolean $return_bool Whether to return a boolean value (default), or the wishlist ids if true
 * @return bool|array Boolean or array of wishlist ids in the order if the order
 * is shipping to the wishlists' addresses.
 */
function is_nmgr_order_shipping_to_wishlist_address( $order, $return_bool = true ) {
	$object = wc_get_order( $order );
	$val = false;

	if ( is_a( $object, 'WC_Order' ) ) {
		$meta = ( array ) $object->get_meta( 'nm_gift_registry' );

		if ( !empty( $meta ) ) {
			$wishlist_ids = array();

			foreach ( $meta as $value ) {
				if ( isset( $value[ 'shipping_to_wishlist_address' ], $value[ 'wishlist_id' ] ) &&
					filter_var( $value[ 'shipping_to_wishlist_address' ], FILTER_VALIDATE_BOOLEAN ) ) {
					$wishlist_ids[] = $value[ 'wishlist_id' ];
				}
			}

			if ( !empty( $wishlist_ids ) ) {
				$val = !$return_bool ? $wishlist_ids : true;
			}
		}
	}
	return apply_filters( 'is_nmgr_order_shipping_to_wishlist_address', $val, $order, $return_bool );
}

/**
 * Get the total amount received for all items in a wishlist in all orders
 * @param int $wishlist_id Wishlist id
 * @deprecated since version 4.5.0
 * @return int|float
 */
function nmgr_wishlist_get_amount_received_in_orders( $wishlist_id ) {
	_deprecated_function( __FUNCTION__, '4.5.0', 'NMGR_Wishlist->get_amount_received_in_orders()' );
	$wishlist = is_a( $wishlist_id, \NMGR_Wishlist::class ) ? $wishlist_id : nmgr_get_wishlist( $wishlist_id );
	return $wishlist->get_amount_received_in_orders();
}

/**
 * Get the amount received for all items in a wishlist from an order
 * @param int|NMGR_Wishlist $wishlist_id Wishlist id or Object
 * @param int|WC_Order $order_id Order id or object
 * @deprecated since version 4.5.0
 * @return int
 */
function nmgr_wishlist_get_amount_received_in_order( $wishlist_id, $order_id ) {
	_deprecated_function( __FUNCTION__, '4.5.0', 'NMGR_Wishlist->get_amount_received_in_order()' );
	$wishlist = is_a( $wishlist_id, \NMGR_Wishlist::class ) ? $wishlist_id : nmgr_get_wishlist( $wishlist_id );
	return $wishlist->get_amount_received_in_order( $order_id );
}

/**
 * Show the shipping addresses of the wishlists the order is shipping to
 *
 * @param string|array $wishlist_ids Wishlist ids or objects
 * @param array $args Arguments used to show the notice.
 *  map_url {boolean} Whether to map the addresses to url (google maps). Default true.
 * 	separator {string} How to separate address lines. Default is '<br/>' woocommerce separator.
 *  show_notice {boolean} Whether to show the notice that the order is shipping to
 *                        the wishlist address. Default true.
 *
 * @return string
 */
function nmgr_get_order_shipping_addresses_notice( $wishlist_ids, $args = array() ) {
	if ( !empty( $wishlist_ids ) ) {
		$defaults = array(
			'map_url' => false,
			'separator' => '',
			'show_notice' => true,
		);

		$fargs = apply_filters( 'nmgr_get_order_shipping_addresses_notice_args', wp_parse_args( $args, $defaults ) );

		$addresses = $notice = '';

		foreach ( ( array ) $wishlist_ids as $wishlist_id ) {
			$wishlist = nmgr_get_wishlist( $wishlist_id, true );

			if ( $wishlist ) {
				$link = '<span class="nmgr-wishlist-title">' . $wishlist->get_title() . ' ' . nmgr_get_wishlist_link( $wishlist, array( 'content' => '&raquo;' ) ) . '</span>';

				if ( is_a( wc()->countries, 'WC_Countries' ) ) {
					if ( $fargs[ 'separator' ] ) {
						$address = wc()->countries->get_formatted_address( $wishlist->get_shipping(), $fargs[ 'separator' ] );
					} else {
						$address = wc()->countries->get_formatted_address( $wishlist->get_shipping() );
					}

					if ( $fargs[ 'map_url' ] ) {
						$url = nmgr_get_shipping_address_map_url( $wishlist->get_shipping() );
						$address = '<a target="_blank" href="' . esc_url( $url ) . '">' . $address . '</a>';
					}
				}
				$addresses .= '<p>' . $link . '<br>' . $address . '</p>';
			}
		}

		if ( !empty( $addresses ) ) {

			if ( $fargs[ 'show_notice' ] ) {
				ob_start();
				?>
				<div class="nmgr-order-shipping-notice" style="background-color:#efefef;padding:10px;">
					<p style="margin:0!important;">
						<?php
						printf(
							/* translators: %s: wishlist type title */
							esc_html__( 'This order has been set by default to be shipped to the following %s addresses:', 'nm-gift-registry' ),
							esc_html( nmgr_get_type_title() )
						);
						?>
					</p></div>

				<?php
				$notice = ob_get_clean();
			}

			return $notice . $addresses;
		}
	}
}

/**
 * Get the url for a shipping address. Default is google map url.
 *
 * @param array $raw_address the address in raw non-formatted way
 * @return type
 */
function nmgr_get_shipping_address_map_url( $raw_address ) {
	$order = new WC_Order();
	$order_address = $order->get_address( 'shipping' );

	add_filter( 'woocommerce_get_order_address', function() use( $raw_address ) {
		return $raw_address;
	} );

	$mapped_address = $order->get_shipping_address_map_url();

	add_filter( 'woocommerce_get_order_address', function() use( $order_address ) {
		return $order_address;
	} );

	return $mapped_address;
}

/**
 * Get all orders in which wishlist items are purchased.
 * (These are orders which have paid statuses. Orders which are on-hold or pending or which
 * have other unpaid statuses do not count).
 *
 * @param int $wishlist_id The wishlist id
 * @return array Order ids
 */
function nmgr_wishlist_get_orders_with_purchases( $wishlist_id ) {
	_deprecated_function( __FUNCTION__, '4.3.0', 'NMGR_Wishlist->get_order_ids' );
	return nmgr_get_wishlist( $wishlist_id )->get_order_ids();
}

function nmgr_get_custom_add_to_wishlist_button( $type = 'gift-registry' ) {
	return nmgr_get_type_option( $type, 'add_to_wishlist_button_custom_html' );
}
