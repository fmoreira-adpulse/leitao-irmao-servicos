<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Order extends \NMGR_Order {

	public static function run() {
		add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'maybe_restrict_items_from_cart' ], 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'prev_multi_wishlists_in_cart' ], 10, 2 );
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'message_to_wishlist_owner' ), 10 );
		add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'save_message_to_wishlist_owner' ], 10 );
		add_filter( 'woocommerce_cart_shipping_packages', [ __CLASS__, 'set_wishlist_address_for_package' ], 99 );
		add_filter( 'woocommerce_cart_shipping_packages', array( __CLASS__, 'create_wishlist_packages' ), 99 );
		add_filter( 'woocommerce_shipping_package_name', array( __CLASS__, 'shipping_package_name' ), 99, 3 );
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'package_rates' ), 99, 2 );
		add_filter( 'woocommerce_shipping_show_shipping_calculator', [ __CLASS__, 'show_shipping_calc' ], 10, 3 );
		add_action( 'woocommerce_before_shipping_calculator', array( __CLASS__, 'hide_shipping_calculator' ) );
		add_filter( 'woocommerce_ship_to_different_address_checked', array( __CLASS__, 'check_shipping_address' ) );
		add_action( 'woocommerce_before_checkout_shipping_form', [ __CLASS__, 'disable_ship_checkbox' ] );
		add_action( 'woocommerce_checkout_update_customer', array( __CLASS__, 'reset_customer_shipping_address' ) );
		add_action( 'woocommerce_cart_item_removed', array( __CLASS__, 'reset_shipping_address' ), 10, 2 );
		add_action( 'woocommerce_cart_item_restored', array( __CLASS__, 'reset_shipping_address' ), 10, 2 );
		add_filter( 'woocommerce_formatted_address_replacements', [ __CLASS__, 'format_address' ], 10, 2 );
		add_action( 'woocommerce_email_customer_details', [ __CLASS__, 'format_address_email' ], 10, 2 );
		add_action( 'woocommerce_check_cart_items', [ __CLASS__, 'check_cart_items_for_different_wishlists' ] );
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'check_cart_for_mixed_items' ) );
		add_action( 'woocommerce_before_checkout_shipping_form', array( __CLASS__, 'hide_shipping_form' ), 999 );
		add_action( 'woocommerce_after_checkout_shipping_form', [ __CLASS__, 'reset_hide_shipping_form' ], -1 );
		add_action( 'woocommerce_before_checkout_process', array( __CLASS__, 'add_shipping_post_data' ) );
		add_action( 'woocommerce_checkout_create_order_shipping_item', [ __CLASS__, 'add_shipping_data' ], 10, 3 );
		add_filter( 'woocommerce_order_get_formatted_shipping_address', [ __CLASS__, 'shipping_addy' ], 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', [ __CLASS__, 'notify_wishlist_order' ] );
		add_filter( 'woocommerce_cart_totals_get_item_tax_rates', [ __CLASS__, 'set_tax_rates' ], 10, 2 );
		add_action( 'woocommerce_before_cart_totals', [ __CLASS__, 'hide_estimated_for_text' ] );
		parent::run();
	}

	/**
	 * Restrict items from being added to the cart based on whether they are wishlist or normal items
	 *
	 * @since 1.1.0
	 * @param type $passed
	 * @return boolean
	 */
	public static function maybe_restrict_items_from_cart( $passed, $product_id ) {
		$item_data = self::get_add_to_cart_item_data( $product_id );

		if ( !isset( $item_data[ 'nmgr-add-to-cart-wishlist' ] ) && nmgr_restrict_normal_items_from_cart() ) {
			$message = sprintf(
				/* translators: %1$s, %2$s: wishlist type title */
				esc_attr__( 'You cannot add both normal and %1$s items to the cart. Please remove the %1$s items from the cart before adding the normal item.', 'nm-gift-registry' ),
				esc_html( nmgr_get_type_title() ),
				esc_html( nmgr_get_type_title() ) );

			if ( !wc_has_notice( $message, 'error' ) ) {
				wc_add_notice( $message, 'error' );
			}
			return false;
		} elseif ( isset( $item_data[ 'nmgr-add-to-cart-wishlist' ] ) &&
			has_term( 'gift-registry', 'nm_gift_registry_type', $item_data[ 'nmgr-add-to-cart-wishlist' ] ) &&
			nmgr_restrict_wishlist_items_from_cart() ) {
			$message = sprintf(
				/* translators: %1$s, %2$s: wishlist type title */
				esc_attr__( 'You cannot add both %1$s and normal items to the cart. Please remove the normal items from the cart before adding the %2$s item.', 'nm-gift-registry' ),
				esc_html( nmgr_get_type_title() ),
				esc_html( nmgr_get_type_title() ) );

			if ( !wc_has_notice( $message, 'error' ) ) {
				wc_add_notice( $message, 'error' );
			}
			return false;
		}
		return $passed;
	}

	public static function prev_multi_wishlists_in_cart( $passed, $product_id ) {
		$item_data = self::get_add_to_cart_item_data( $product_id );

		if ( empty( $item_data ) ) {
			return $passed;
		}

		$wishlist_id = ( int ) $item_data[ 'nmgr-add-to-cart-wishlist' ];

		if ( nmgr()->is_pro && nmgr_get_option( 'cart_prevent_multiple_wishlists' ) ) {
			$wishlist_in_cart = nmgr_get_wishlist_in_cart();

			if ( $wishlist_in_cart && $wishlist_id !== ( int ) $wishlist_in_cart ) {
				wc_add_notice( sprintf(
						/* translators: %1$s: pluralized wishlist type title, %2$s, %3$s: wishlist type title */
						esc_attr__( 'You cannot add items from different %1$s to the cart. Please remove the items from the %2$s in the cart in order to add items from another %3$s.', 'nm-gift-registry' ),
						esc_html( nmgr_get_type_title( '', true ) ),
						esc_html( nmgr_get_type_title() ),
						esc_html( nmgr_get_type_title() ) ), 'error' );
				return false;
			}
		}
		return $passed;
	}

	/**
	 * Send an optional message to the wishlist owner on the checkout page
	 */
	public static function message_to_wishlist_owner( $checkout ) {
		if ( !nmgr_get_option( 'enable_messages' ) ) {
			return;
		}

		$wishlist_ids = nmgr_get_wishlists_in_cart();
		if ( $wishlist_ids ) {

			$heading = sprintf( '<h3>%s</h3>', nmgr_get_type_title( 'c' ) );
			$dep_heading = apply_filters_deprecated( 'nmgr_checkout_wishlist_message_heading_html', array( $heading, $checkout ), '2.1.2', 'nmgr_checkout_message_heading_html' );
			echo apply_filters( 'nmgr_checkout_message_heading_html', $dep_heading, $checkout );

			foreach ( $wishlist_ids as $id ) {
				$wishlist = nmgr_get_wishlist( $id, true );

				if ( !$wishlist ) {
					continue;
				}

				$field = woocommerce_form_field( "nmgr_comments[$id]", array(
					'type' => 'textarea',
					'id' => 'nmgr-comments-' . $id,
					'class' => array( 'nmgr-comment' ),
					'label' => apply_filters( 'nmgr_checkout_message_textarea_label', sprintf(
							/* translators: %s: wishlist type title */
							__( 'Send an optional message to the owner of the %s', 'nm-gift-registry' ), nmgr_get_type_title() ), $wishlist )
					. sprintf(
						/* translators: 1: wishlist type title, 2: link to wishlist */
						'<br>%1$s: %2$s', nmgr_get_type_title( 'c' ), nmgr_get_wishlist_link( $wishlist ) ),
					'placeholder' => apply_filters( 'nmgr_checkout_message_textarea_placeholder', sprintf(
							/* translators: %s: wishlist type title */
							__( 'Message to the owner of the %s.', 'nm-gift-registry' ), nmgr_get_type_title() ), $wishlist ),
					'return' => true,
					), $checkout->get_value( "nmgr_comments[$id]" ) );

				echo $field;
			}
		}
	}

	/**
	 * Saves the wishlist message to the wishlist after the order has been processed on the checkout page
	 */
	public static function save_message_to_wishlist_owner( $order_id ) {
		if ( isset( $_REQUEST[ 'nmgr_comments' ] ) && !empty( array_filter( $_REQUEST[ 'nmgr_comments' ] ) ) ) {
			foreach ( $_REQUEST[ 'nmgr_comments' ] as $wishlist_id => $message ) {
				$wishlist = nmgr_get_wishlist( $wishlist_id, true );

				if ( !$wishlist ) {
					continue;
				}

				$wishlist->add_message( array(
					'message' => wc_sanitize_textarea( $message ),
					'order_id' => $order_id,
				) );
			}
		}
	}

	/**
	 * Set the shipping address on cart page to the wishlist address
	 * if we have only one package and one wishlist in the cart
	 * and we are shipping to the wishlist address
	 *
	 * The purpose of this function is simply to make it easy to set the
	 * cart to be shipping to the wishlist address even if the plugin option
	 * to calculate shipping is not set.
	 *
	 * @since 1.1.3
	 */
	public static function set_wishlist_address_for_package( $packages ) {
		$wishlist_shipping = is_nmgr_cart_shipping_to_wishlist_address( false );
		if ( 1 === count( $packages ) && $wishlist_shipping && 1 === count( $wishlist_shipping ) ) {
			foreach ( $packages as $index => $package ) {
				if ( isset( $package[ 'contents' ] ) ) {
					foreach ( $package[ 'contents' ] as $content ) {
						$nmgr_data = nmgr_get_cart_item_data( $content, 'wishlist_item' );
						if ( $nmgr_data ) {
							$wishlist = nmgr_get_wishlist( $nmgr_data[ 'wishlist_id' ] );

							if ( $wishlist ) {
								$packages[ $index ][ 'nmgr_wishlist_id' ] = $wishlist->get_id(); // Add this for easy identification
								$packages[ $index ][ 'destination' ] = array(
									'country' => $wishlist->get_shipping_country(),
									'state' => $wishlist->get_shipping_state(),
									'postcode' => $wishlist->get_shipping_postcode(),
									'city' => $wishlist->get_shipping_city(),
									'address' => $wishlist->get_shipping_address(),
									'address_1' => $wishlist->get_shipping_address(),
									'address_2' => $wishlist->get_shipping_address_2(),
								);
							}
						}
					}
				}
			}
		}
		return $packages;
	}

	/**
	 * Create shipping packages for wishlist items if calculating wishlist shipping separately
	 * or if cart has multiple packages and it is shipping to the wishlist owner's address
	 *
	 * @since 1.1.0
	 */
	public static function create_wishlist_packages( $packages ) {
		$wishlists_in_cart = nmgr_get_wishlists_in_cart();

		if ( !nmgr_get_option( 'enable_shipping' ) || empty( $wishlists_in_cart ) ) {
			return $packages;
		}

		$calculate_shipping = filter_var( nmgr_get_option( 'shipping_calculate' ), FILTER_VALIDATE_BOOLEAN );
		$shipping_to_wishlist_address = is_nmgr_cart_shipping_to_wishlist_address();

		if (
			(!$calculate_shipping && !$shipping_to_wishlist_address ) ||
			($shipping_to_wishlist_address && 1 === count( $wishlists_in_cart ) && 1 === count( $packages ) )
		) {
			return $packages;
		}

		$wishlists_in_packages = array();
		$wishlist_packages = array();
		$first_package = reset( $packages );

		foreach ( $packages as $index => $package ) {
			if ( isset( $package[ 'contents' ] ) ) {
				foreach ( $package[ 'contents' ] as $key => $content ) {
					$nmgr_data = nmgr_get_cart_item_data( $content, 'wishlist_item' );
					if ( $nmgr_data ) {
						$wishlists_in_packages[ $key ] = $content;
						unset( $packages[ $index ][ 'contents' ][ $key ] );

						if ( isset( $package[ 'content_cost' ], $content[ 'line_total' ] ) ) {
							$packages[ $index ][ 'content_cost' ] = $packages[ $index ][ 'content_cost' ] - $content[ 'line_total' ];
						}
					}
				}
			}
		}

		if ( !empty( $wishlists_in_packages ) ) {
			foreach ( $wishlists_in_packages as $key => $content ) {
				$wishlist_id = $content[ 'nm_gift_registry' ][ 'wishlist_id' ];
				$wishlist_packages[ $wishlist_id ][ $key ] = $content;
			}
		}

		if ( !empty( $wishlist_packages ) ) {
			foreach ( $wishlist_packages as $wishlist_id => $content ) {
				$wishlist = nmgr_get_wishlist( $wishlist_id, true );

				if ( $wishlist ) {
					/**
					 * Merge current package with first package to capture package key, value pairs
					 * not supplied here
					 */
					$packages[] = array_merge( $first_package, array(
						'nmgr_wishlist_id' => $wishlist_id, // Add this for easy identification
						'contents' => $content,
						'contents_cost' => array_sum( wp_list_pluck( $content, 'line_total' ) ),
						'applied_coupons' => wc()->cart->get_applied_coupons(),
						'destination' => array(
							'country' => $wishlist->get_shipping_country(),
							'state' => $wishlist->get_shipping_state(),
							'postcode' => $wishlist->get_shipping_postcode(),
							'city' => $wishlist->get_shipping_city(),
							'address' => $wishlist->get_shipping_address(),
							'address_1' => $wishlist->get_shipping_address(),
							'address_2' => $wishlist->get_shipping_address_2(),
						),
						) );
				}
			}

			// Ensure we don't have any empty packages
			foreach ( $packages as $index => $package ) {
				if ( empty( $package[ 'contents' ] ) ) {
					unset( $packages[ $index ] );
				}
			}
		}

		return $packages;
	}

	/**
	 * Change shipping package name for wishlist items to show wishlist title
	 *
	 * @since 1.1.0
	 */
	public static function shipping_package_name( $current_name, $index, $package ) {
		$cart_wishlist_id = is_nmgr_cart_shipping_to_wishlist_address( false );

		if ( nmgr_get_option( 'enable_shipping' ) ) {
			if ( nmgr_get_option( 'shipping_calculate' ) && isset( $package[ 'nmgr_wishlist_id' ] ) ) {
				$wishlist_id = $package[ 'nmgr_wishlist_id' ];
			} elseif ( !empty( $cart_wishlist_id ) &&
				1 === count( nmgr_get_wishlists_in_cart() ) &&
				1 === count( wc()->cart->get_shipping_packages() ) ) {
				$wishlist_id = reset( $cart_wishlist_id );
			}

			if ( isset( $wishlist_id ) ) {
				$wishlist = nmgr_get_wishlist( $wishlist_id, true );

				if ( $wishlist ) {
					$new_name = sprintf(
						/* translators: %1$s: wishlist type title, %2$s: wishlist title */
						esc_html__( 'Shipping for %1$s - %2$s', 'nm-gift-registry' ),
						nmgr_get_type_title(),
						$wishlist->get_title() );

					return apply_filters( 'nmgr_shipping_package_name', $new_name, $wishlist, $package );
				}
			}
		}

		return $current_name;
	}

	/**
	 * Allow only specific shipping methods for wishlist items
	 *
	 * @since 1.1.3
	 */
	public static function package_rates( $rates, $package ) {
		$shipping_methods_ids = nmgr_get_option( 'shipping_methods' );

		if ( nmgr_get_option( 'enable_shipping' ) &&
			nmgr_get_option( 'shipping_calculate' ) &&
			!empty( $shipping_methods_ids ) &&
			isset( $package[ 'nmgr_wishlist_id' ] ) ) {
			foreach ( $rates as $key => $rate_object ) {
				if ( !in_array( $rate_object->get_method_id(), $shipping_methods_ids ) ) {
					unset( $rates[ $key ] );
				}
			}
		}
		return $rates;
	}

	/**
	 * Whether the shipping calculator should be shown when wishlist items are present
	 *
	 * @since 1.1.0
	 */
	public static function show_shipping_calc( $boolean, $index, $package ) {
		return is_nmgr_cart_shipping_to_wishlist_address() ? false : $boolean;
	}

	/**
	 * Hack to hide shipping calculator if not necessary
	 * Used only on woocommerce versions before 4.1.1 which don't have the filter 'woocommerce_shipping_show_shipping_calculator'
	 *
	 * @since 1.1.0
	 */
	public static function hide_shipping_calculator() {
		if ( version_compare( wc()->version, '4.1.1', '<' ) && is_nmgr_cart_shipping_to_wishlist_address() ) {
			echo '<style>form.woocommerce-shipping-calculator { display: none; }</style>';
		}
	}

	/**
	 * Check the shipping address if necessary
	 *
	 * @since 1.1.0
	 */
	public static function check_shipping_address( $bool ) {
		if ( is_nmgr_cart_shipping_to_wishlist_address() ) {
			return true;
		}
		return $bool;
	}

	public static function disable_ship_checkbox() {
		if ( is_nmgr_cart_shipping_to_wishlist_address() ) {
			echo '<style id="nmgr-disable_ship_to_different_address_checkbox-css">'
			. '#ship-to-different-address > label {pointer-events:none;} '
			. 'input#ship-to-different-address-checkbox {pointer-events:none;opacity: 0.5;cursor:not-allowed}'
			. '</style>';
		}
	}

	/**
	 * Hack to prevent customer's shipping address from being updated to the wishlist's owner's shipping address
	 * when the customer buys items that ship to the wishlist's owner's address
	 *
	 * @since 1.1.0
	 */
	public static function reset_customer_shipping_address( $customer ) {
		if ( is_nmgr_cart_shipping_to_wishlist_address() ) {
			// Before new customer's shipping address gets saved, simply reset it to the customer's original shipping address
			foreach ( $customer->get_data()[ 'shipping' ] as $key => $value ) {
				if ( is_callable( array( $customer, "set_shipping_{$key}" ) ) ) {
					$customer->{"set_shipping_{$key}"}( $value );
				} else {
					$customer->update_meta_data( "shipping_{$key}", $value );
				}
			}
		}
	}

	/**
	 * Maybe reset the shipping addresses on the cart page when an item is removed or restored
	 *
	 * @since 1.1.3
	 */
	public static function reset_shipping_address( $cart_item_key, $cart ) {
		$packages = is_a( wc()->cart, 'WC_Cart' ) ? wc()->cart->get_shipping_packages() : array();
		self::set_wishlist_address_for_package( $packages );
		self::create_wishlist_packages( $packages );
	}

	/**
	 * Modify or hide all or parts of the wishlist's owner's shipping address
	 * if the cart items are shipping to it
	 *
	 * By default this action is enabled for all wishlist addresses in the frontend
	 * and disabled in the backend. It can be explicitly enabled or disabled using
	 * the filter 'nmgr_disable_formatted_address_replacements'.
	 *
	 * @since 1.1.3
	 */
	public static function format_address( $args, $address ) {
		if ( apply_filters( 'nmgr_disable_formatted_address_replacements', is_admin() ) ) {
			return $args;
		}

		$wishlist_id = isset( $address[ 'nmgr_wishlist_id' ] ) ? $address[ 'nmgr_wishlist_id' ] : 0;

		if ( !$wishlist_id ) {
			$id = self::is_wishlist_package_address( $address );
			$ids = $id ? $id : is_nmgr_cart_shipping_to_wishlist_address( false );

			if ( is_array( $ids ) ) {
				foreach ( $ids as $id ) {
					$wishlist = nmgr_get_wishlist( $id );
					if ( $wishlist ) {
						$original = array_intersect_assoc( $address, $wishlist->get_shipping() );
						if ( $original === $address ) {
							$wishlist_id = $id;
							break;
						}
					}
				}
			} else {
				$wishlist_id = $ids;
			}
		}

		$wishlist = nmgr_get_wishlist( $wishlist_id );

		if ( !$wishlist ) {
			return $args;
		}

		$fields_to_hide = ( array ) nmgr_get_option( 'shipping_address_hidden' );

		if ( nmgr_get_option( 'shipping_address_replacement_text' ) || in_array( 'all', $fields_to_hide ) ) {
			foreach ( $args as $key => $value ) {
				$args[ $key ] = '';
			}
		}

		if ( nmgr_get_option( 'shipping_address_replacement_text' ) ) {
			if ( is_cart() ) {
				$fill_fields = array(
					'first_name' => $wishlist->get_shipping_first_name(),
					'last_name' => $wishlist->get_shipping_last_name(),
					'company' => $wishlist->get_shipping_company(),
					'address_2' => $wishlist->get_shipping_address_2(),
				);
				foreach ( $fill_fields as $key => $value ) {
					if ( isset( $address[ $key ] ) && empty( $address[ $key ] ) ) {
						$address[ $key ] = $value;
					}
				}
			}

			$replacement_text = self::get_shipping_address_replacement_text( $address, $wishlist );
			$args[ '{address_1}' ] = is_cart() ? ' - ' . rtrim( str_replace( "\n", ' ', $replacement_text ), '.' ) : $replacement_text;
		} elseif ( !in_array( 'all', $fields_to_hide ) ) {
			foreach ( $fields_to_hide as $value ) {
				if ( isset( $args[ '{' . $value . '}' ] ) ) {
					$args[ '{' . $value . '}' ] = '';
				}

				if ( isset( $args[ '{' . $value . '_upper}' ] ) ) {
					$args[ '{' . $value . '_upper}' ] = '';
				}

				if ( 'state' === $value && isset( $args[ '{state_code}' ] ) ) {
					$args[ '{state_code}' ] = '';
				}

				if ( 'first_name' === $value && !in_array( 'last_name', $fields_to_hide ) && isset( $args[ '{name}' ], $address[ 'last_name' ] ) ) {
					$args[ '{name}' ] = $address[ 'last_name' ];
				}

				if ( 'last_name' === $value && !in_array( 'first_name', $fields_to_hide ) && isset( $args[ '{name}' ], $address[ 'first_name' ] ) ) {
					$args[ '{name}' ] = $address[ 'first_name' ];
				}
			}

			if ( empty( array_diff( array( 'first_name', 'last_name' ), $fields_to_hide ) ) ) {
				$args[ '{name}' ] = '';
				$args[ '{name_upper}' ] = '';
			}
		}

		return $args;
	}

	/**
	 * Format order shipping address in woocommerce emails if the email is sent to customer
	 */
	public static function format_address_email( $order, $sent_to_admin ) {
		if ( !$sent_to_admin ) {
			add_filter( 'nmgr_disable_formatted_address_replacements', '__return_false' );
		}
	}

	/**
	 * Remove extra wishlist items if cart is not allowed to have items from different
	 * wishlists.
	 */
	public static function check_cart_items_for_different_wishlists() {
		$wishlist_ids = nmgr_get_wishlists_in_cart();
		if ( nmgr_get_option( 'cart_prevent_multiple_wishlists' ) && 1 < count( $wishlist_ids ) ) {
			$wishlist_id = ( int ) reset( $wishlist_ids );
			foreach ( wc()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$nmgr_data = nmgr_get_cart_item_data( $cart_item );
				if ( $nmgr_data &&
					isset( $nmgr_data[ 'wishlist_id' ] ) &&
					$wishlist_id !== $nmgr_data[ 'wishlist_id' ] ) {
					wc()->cart->set_quantity( $cart_item_key, 0 );
					$message = sprintf(
						/* translators: 1: cart item name, 2: wishlist type title */
						__( 'The item %1$s has been removed from your cart as you are not allowed to have items from different %2$s in the cart.', 'nm-gift-registry' ),
						'<strong>' . wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $cart_item[ 'data' ]->get_name(), $cart_item, $cart_item_key ) ) . '</strong>',
						'<strong>' . esc_html( nmgr_get_type_title( '', true ) ) . '</strong>'
					);
					wc_add_notice( $message, 'error' );
				}
			}
		}
	}

	/**
	 * Remove mixed items if cart has both wishlist and non-wishlist items
	 */
	public static function check_cart_for_mixed_items() {
		if ( nmgr_restrict_normal_items_from_cart() || nmgr_restrict_wishlist_items_from_cart() ) {
			$cart_contents = wc()->cart->get_cart_contents();
			$first_cart_item = reset( $cart_contents );
			$item_type = !empty( nmgr_get_cart_item_data( $first_cart_item ) ) ? 'wishlist' : 'normal';

			foreach ( wc()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$nmgr_data = nmgr_get_cart_item_data( $cart_item );
				if ( ('wishlist' === $item_type && !$nmgr_data ) || ('normal' === $item_type && $nmgr_data) ) {
					wc()->cart->set_quantity( $cart_item_key, 0 );
					$message = sprintf(
						/* translators: 1: cart item name, 2: wishlist type title */
						__( 'The item %1$s has been removed from your cart as you are not allowed to have both normal and %2$s items in the cart.', 'nm-gift-registry' ),
						'<strong>' . wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $cart_item[ 'data' ]->get_name(), $cart_item, $cart_item_key ) ) . '</strong>',
						esc_html( nmgr_get_type_title() )
					);
					wc_add_notice( $message, 'error' );
				}
			}
		}
	}

	public static function hide_shipping_form() {
		if ( is_nmgr_cart_shipping_to_wishlist_address() ) {
			add_filter( 'woocommerce_form_field', array( __CLASS__, 'return_null' ) );
		}
	}

	public static function reset_hide_shipping_form() {
		if ( has_filter( 'woocommerce_form_field', array( __CLASS__, 'return_null' ) ) ) {
			remove_filter( 'woocommerce_form_field', array( __CLASS__, 'return_null' ) );
		}
	}

	public static function return_null() {
		return null;
	}

	public static function add_shipping_post_data() {
		$wishlist_ids = is_nmgr_cart_shipping_to_wishlist_address( false );
		if ( is_array( $wishlist_ids ) && !empty( $wishlist_ids ) ) {
			$wishlist = nmgr_get_wishlist( reset( $wishlist_ids ) );

			if ( $wishlist ) {
				foreach ( $wishlist->get_shipping() as $key => $value ) {
					$_POST[ "shipping_$key" ] = $value;
				}
			}
		}
	}

	/**
	 * Add wishlist data as order shipping item meta
	 *
	 * @since 1.1.0
	 */
	public static function add_shipping_data( $item, $package_key, $package ) {
		if ( nmgr_get_option( 'enable_shipping' ) && nmgr_get_option( 'shipping_calculate' ) && isset( $package[ 'nmgr_wishlist_id' ] ) ) {
			$wishlist = nmgr_get_wishlist( $package[ 'nmgr_wishlist_id' ], true );
			if ( !$wishlist ) {
				return;
			}
			$item->add_meta_data( 'nmgr_wishlist_id', $package[ 'nmgr_wishlist_id' ], true );

			if ( is_a( wc()->countries, 'WC_Countries' ) && !empty( $package[ 'destination' ] ) ) {
				$address = wc()->countries->get_formatted_address( $package[ 'destination' ], ', ' );
				$item->add_meta_data( 'nmgr_shipping_address', $address );
			}
		}
	}

	/**
	 * If order is shipping to wishlist addresses, display all the wishlist addresses.
	 * (Shown on the checkout order received page and in order emails)
	 * This function is a hack to show all the addresses in place of one as woocommerce
	 * only has provision for one shipping address with one order.
	 */
	public static function shipping_addy( $address, $raw_address, $order ) {
		$wishlist_ids = is_nmgr_order_shipping_to_wishlist_address( $order, false );
		$in_order_template = in_nmgr_template( [ 'order/order-details-customer.php', 'emails/email-addresses.php' ] );

		if ( is_array( $wishlist_ids ) && is_a( wc()->countries, 'WC_Countries' ) && ($in_order_template || is_checkout()) ) {
			$addresses = array();

			foreach ( $wishlist_ids as $id ) {
				$wishlist = nmgr_get_wishlist( $id, true );
				if ( $wishlist ) {
					/**
					 * Add the 'nmgr_wishlist_id' tag to help the 'get_formatted_address' function
					 * format the shipping address specfically for wishlists
					 */
					$address_args = array_merge( $wishlist->get_shipping(), array( 'nmgr_wishlist_id' => $id ) );
					$addresses[] = wc()->countries->get_formatted_address( $address_args );
				}
			}

			if ( !empty( $addresses ) ) {
				if ( 1 < count( $wishlist_ids ) ) {
					if ( $in_order_template ) {
						// In these template the 'address' tag is used so we have to apply it to the multiple addresses
						$addresses = array_map( function( $add ) {
							return "<address>$add</address>";
						}, $addresses );
						$final = implode( '<br>', $addresses );
					} elseif ( is_checkout() ) {
						/**
						 * This targets other places where the formatted order shipping address is displayed.
						 * We want to space out the shipping addresses.
						 */
						$final = implode( '<br><br>', $addresses );
					}
				} else {
					$final = reset( $addresses );
				}

				$filter_args = array(
					'formatted_address' => $address,
					'raw_address' => $raw_address,
					'order' => $order
				);

				return apply_filters( 'nmgr_order_get_formatted_shipping_address', $final, $filter_args );
			}
		}

		return $address;
	}

	/**
	 * Show notice in the order screen if the order shipping address belongs to the wishlist's owner
	 *
	 * @since 1.1.0
	 */
	public static function notify_wishlist_order( $order ) {
		$show_notice = apply_filters( 'nmgr_order_show_shipping_destination_notice', true );
		$wishlist_ids = is_nmgr_order_shipping_to_wishlist_address( $order, false );
		$notice = nmgr_get_order_shipping_addresses_notice( $wishlist_ids );

		if ( $show_notice && !empty( $notice ) ) {
			?>
			<style>
				#order_data .order_data_column:last-child .address {
					display: none;
				}
			</style>
			<div class="nmgr-order-shipping-notice-clear" style="clear:both;padding:10px;"></div>
			<?php

			echo $notice;
		}
	}

	/**
	 * Make sure each wishlist item in the cart has its tax rate set according to the
	 * wishlist owner's address.
	 *
	 * This is done only if we are calulating wishlist items shipping separately or if
	 * the cart is shipping to the wishlist owner's address
	 */
	public static function set_tax_rates( $rates, $item ) {
		$nmgr_data = nmgr_get_cart_item_data( $item->object, 'wishlist_item' );

		if ( !empty( $nmgr_data ) &&
			(is_nmgr_cart_shipping_to_wishlist_address() || nmgr_get_option( 'shipping_calculate' )) ) {
			$wishlist_id = $nmgr_data[ 'wishlist_id' ];

			$location = function( $loc ) use( $wishlist_id ) {
				$addy = self::get_wishlist_tax_address( $wishlist_id );
				return $addy ? $addy : $loc;
			};

			add_filter( 'woocommerce_get_tax_location', $location );

			$rates = \WC_Tax::get_rates( $item->product->get_tax_class() );

			remove_filter( 'woocommerce_get_tax_location', $location );
		}
		return $rates;
	}

	/**
	 * Hide the "estimated for %s" text (where %s is the country) which woocommerce adds next to
	 * the tax rate label in the cart totals.
	 *
	 * We need to hide this when altering the tax rate for the specific cart item to be based on the
	 * wishlist owner's address as the country displayed is based on the currently logged in customer's
	 * country rather than the wishlist address country.
	 */
	public static function hide_estimated_for_text() {
		if ( nmgr_get_wishlist_in_cart() &&
			(is_nmgr_cart_shipping_to_wishlist_address() || nmgr_get_option( 'shipping_calculate' )) ) {
			?>
			<style>
				.cart_totals tr.tax-rate > th > small { display: none; }
			</style>
			<?php

		}
	}

	public static function get_wishlist_tax_address( $wishlist_id ) {
		$wishlist = nmgr_get_wishlist( $wishlist_id );
		if ( $wishlist ) {
			return [
				$wishlist->get_shipping_country(),
				$wishlist->get_shipping_state(),
				$wishlist->get_shipping_postcode(),
				$wishlist->get_shipping_city(),
			];
		}
	}

	/**
	 * Check if the address supplied is actually the destination address for a
	 * wishlist package on the cart page
	 *
	 * This function is just a 'hackish' way of telling if a shipping address on
	 * the cart page actually belongs to a wishlist in the cart so that we can
	 * manipulate the address if we want. It is used when there is no other way to
	 * tell the address other than by using the address itself.
	 *
	 * Here we are simplying comparing the address supplied to the destination address
	 * of the wishlist package to see if they match.
	 *
	 * @since 1.1.4
	 * @return mixed The wishlist id the the address is tagged to belong to or false if
	 * it is not a wishlist package address
	 */
	private static function is_wishlist_package_address( $address ) {
		if ( nmgr_get_option( 'enable_shipping' ) && is_a( wc()->cart, 'WC_Cart' ) ) {
			$packages = wc()->cart->get_shipping_packages();
			foreach ( $packages as $package ) {
				if ( isset( $package[ 'destination' ], $package[ 'nmgr_wishlist_id' ] ) ) {
					$compare_address = array_intersect_key( $address, $package[ 'destination' ] );
					if ( $compare_address == $package[ 'destination' ] ) {
						return $package[ 'nmgr_wishlist_id' ];
					}
				}
			}
		}
		return false;
	}

	/**
	 * Compose the replacement text for the wishlist's owner's shipping address
	 *
	 * @since 1.1.3
	 * @param array $address The shipping address
	 * @param NMGR_Wishlist $wishlist The wishlist object
	 */
	private static function get_shipping_address_replacement_text( $address, $wishlist_id ) {
		$country = $address[ 'country' ];
		$state = $address[ 'state' ];

		$wishlist = is_a( $wishlist_id, 'NMGR_Wishlist' ) ? $wishlist_id : nmgr_get_wishlist( $wishlist_id );
		if ( !$wishlist ) {
			$wishlist = nmgr()->wishlist();
		}

		if ( is_a( wc()->countries, 'WC_Countries' ) && isset( wc()->countries->get_countries()[ $address[ 'country' ] ] ) ) {
			$country = wc()->countries->get_countries()[ $address[ 'country' ] ];
		}

		if ( $address[ 'country' ] && $address[ 'state' ] &&
			is_a( wc()->countries, 'WC_Countries' ) && isset( wc()->countries->get_states( $address[ 'country' ] )[ $address[ 'state' ] ] ) ) {
			$state = wc()->countries->get_states( $address[ 'country' ] )[ $address[ 'state' ] ];
		}

		return str_replace(
			array(
				'{wishlist_type_title}', '{shipping_first_name}', '{shipping_last_name}',
				'{shipping_company}', '{shipping_country}', '{shipping_address_1}',
				'{shipping_address_2}', '{shipping_city}', '{shipping_state}',
				'{shipping_postcode}', '{first_name}', '{last_name}',
				'{full_name}', '{partner_first_name}', '{partner_last_name}',
				'{partner_full_name}', '{display_name}', '{email}',
			),
			array(
				nmgr_get_type_title(), $address[ 'first_name' ], $address[ 'last_name' ],
				$address[ 'company' ], $country, $address[ 'address_1' ],
				$address[ 'address_2' ], $address[ 'city' ], $state,
				$address[ 'postcode' ], $wishlist->get_first_name(), $wishlist->get_last_name(),
				$wishlist->get_full_name(), $wishlist->get_partner_first_name(), $wishlist->get_partner_last_name(),
				$wishlist->get_partner_full_name(), $wishlist->get_display_name(), $wishlist->get_email(),
			),
			nmgr_get_option( 'shipping_address_replacement_text' )
		);
	}

}
