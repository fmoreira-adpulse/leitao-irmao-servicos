<?php

/**
 * Cart actions related to crowdfunding a wishlist item
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Cart_Crowdfund {

	public static function run() {
		add_filter( 'woocommerce_is_purchasable', [ __CLASS__, 'make_product_purchasable_by_all' ], 10, 2 );
		add_filter( 'nmgr_cart_has_wishlist_notice', [ __CLASS__, 'hide_cart_notice_for_contributions' ] );

		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return;
		}

		add_filter( 'nmgr_add_to_cart_items_data', array( __CLASS__, 'set_add_to_cart_crowdfund_items_data' ) );
		add_action( 'nmgr_add_to_cart_action', array( __CLASS__, 'add_crowdfund_contribution_to_cart' ) );
		add_filter( 'woocommerce_cart_item_thumbnail', array( __CLASS__, 'set_cart_item_thumbnail' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'set_cart_item_name' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_removed_title', array( __CLASS__, 'set_cart_item_name' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'set_cart_item_price' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'set_cart_item_subtotal' ) );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'show_cart_item_data' ), 50, 2 );
		add_filter( 'nmgr_get_wishlist_in_cart', array( __CLASS__, 'get_crowdfund_wishlist_in_cart' ), 10, 2 );
		add_filter( 'nmgr_get_wishlists_in_cart', array( __CLASS__, 'get_crowdfund_wishlists_in_cart' ), 10, 2 );
		add_filter( 'wc_add_to_cart_message_html', array( __CLASS__, 'set_added_to_cart_notice' ), 10, 2 );
	}

	/**
	 * Setup crowdfunded item product to be added to the cart (via http) when
	 * the button is clicked.
	 * (Applies only when adding single product to cart, not multiple products)
	 */
	public static function set_add_to_cart_crowdfund_items_data( $items_data ) {
		if ( filter_input( INPUT_POST, 'nmgr-cf-price' ) ) {
			$items_data[] = [
				'nmgr-cf-wishlist-item-id' => filter_input( INPUT_POST, 'nmgr-cf-wishlist-item-id', FILTER_VALIDATE_INT ),
				'nmgr-cf-wishlist-id' => filter_input( INPUT_POST, 'nmgr-cf-wishlist-id', FILTER_VALIDATE_INT ),
				'nmgr-cf-price' => ( float ) filter_input( INPUT_POST, 'nmgr-cf-price' ),
			];
		}
		return $items_data;
	}

	/**
	 * Add a crowdfunded item product to the cart
	 *
	 * This function is used when adding the product as a single item
	 * or when adding it as part of multiple items in bulk.
	 */
	public static function add_crowdfund_contribution_to_cart( &$items_data ) {
		$crowdfund_products = array();

		foreach ( $items_data as $key => $item_mixed ) {
			$item = ( array ) $item_mixed;
			if ( isset( $item[ 'nmgr-cf-price' ] ) ) {
				$crowdfund_products[] = $item;
				unset( $items_data[ $key ] );
			}
		}

		if ( empty( $crowdfund_products ) ) {
			return;
		}

		$placeholder_product_id = nmgrcf_get_placeholder_product_id();

		if ( !wc_get_product( $placeholder_product_id ) ) {
			wc_add_notice( __( 'The product to be crowdfunded could not be created.', 'nm-gift-registry-crowdfunding' ), 'error' );
			return;
		}

		foreach ( $crowdfund_products as $cf_product ) {

			$item_id = ( int ) $cf_product[ 'nmgr-cf-wishlist-item-id' ];
			$wishlist_id = ( int ) $cf_product[ 'nmgr-cf-wishlist-id' ];
			$contribute_price = ( float ) $cf_product[ 'nmgr-cf-price' ];

			if ( !$item_id || !is_numeric( $contribute_price ) ) {
				continue;
			}

			try {

				$item = nmgr_get_wishlist_item( $item_id );

				if ( !$item ) {
					throw new Exception( sprintf(
								/* translators: %s: wishlist type title */
								__( 'The product to be crowdfunded does not exist in this %s.', 'nm-gift-registry-crowdfunding' ),
								nmgr_get_type_title()
							) );
				}

				$crowdfund_data = $item->get_crowdfund_data();
				$amount_left = $item->get_crowdfund_amount_left();
				$min_amount = isset( $crowdfund_data[ 'min_amount' ] ) ? ( float ) $crowdfund_data[ 'min_amount' ] : 0;
				$cart_item_key = ''; // Cart item key for the item if it is in the cart
				$item_in_cart = false;

				if ( 0 >= nmgrcf_round( $contribute_price ) ) {
					throw new Exception(
							__( 'Please enter a valid contribution amount for the item.', 'nm-gift-registry-crowdfunding' )
					);
				}

				// Check if the crowdfund product is already in the cart for this wishlist item
				foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
					$ci_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
					if ( $ci_data && $item_id === $ci_data[ 'wishlist_item_id' ] ) {
						$item_in_cart = $cart_item;
						$cart_item_key = $key;
						break;
					}
				}

				/**
				 * Check that the amount added to cart is not less than the minimum set by the wishlist owner (this only applies
				 * if the amount needed is not less than the minimum set by the wishlist owner.
				 */
				if ( !$item_in_cart && $min_amount &&
					nmgrcf_round( $contribute_price ) < nmgrcf_round( $min_amount ) &&
					nmgrcf_round( $contribute_price ) < nmgrcf_round( $amount_left ) ) {
					throw new Exception( sprintf(
								/* translators: 1: minimum contribution amount, 2: product name */
								__( 'Please contribute a minimum of %1$s for &ldquo;%2$s&rdquo;.', 'nm-gift-registry-crowdfunding' ),
								wc_price( $min_amount ),
								$item->get_product_name()
							) );
				}

				/**
				 * Check that the amount added to cart is not greater than the amount needed
				 */
				if ( !$item_in_cart && (nmgrcf_round( $contribute_price ) > nmgrcf_round( $amount_left )) ) {
					throw new Exception( sprintf(
								/* translators: 1: product name, 2: amount contributed, 3: amount needed */
								__( 'The amount contributed for &ldquo;%1$s&rdquo; (%2$s) is greater than the amount needed (%3$s). Please adjust the amount.', 'nm-gift-registry-crowdfunding' ),
								$item->get_product_name(),
								wc_price( $contribute_price ),
								wc_price( $amount_left )
							) );
				}

				/**
				 * Check that the amount added to cart + the amount already in cart is not greater than the amount needed
				 */
				if ( $item_in_cart && ( nmgrcf_round( $contribute_price + $item_in_cart[ 'line_subtotal' ] ) > nmgrcf_round( $amount_left ) ) ) {
					$cart_amount_needed = $amount_left - $item_in_cart[ 'line_subtotal' ];

					$notice = sprintf(
						/* translators: 1: amount contributed, 2: product name, 3: amount in cart  */
						__( 'You cannot contribute another %1$s for &ldquo;%2$s&rdquo; as you already have %3$s in the cart for the item.', 'nm-gift-registry-crowdfunding' ),
						wc_price( $contribute_price ),
						$item->get_product_name(),
						wc_price( $item_in_cart[ 'line_subtotal' ] )
					);

					if ( nmgrcf_round( $cart_amount_needed ) > nmgrcf_round( $min_amount ) ) {
						$notice .= ' ' . sprintf(
								/* translators: %s: amount needed */
								__( 'Please contribute a maximum of %s.', 'nm-gift-registry-crowdfunding' ),
								wc_price( $cart_amount_needed )
						);
					} else {
						$notice .= ' ' .
							__( 'Please remove the contribution in the cart and make a new contribution if necessary.', 'nm-gift-registry-crowdfunding' );
					}

					throw new Exception( $notice );
				}

				$price = !$item_in_cart ? $contribute_price : $contribute_price + $item_in_cart[ 'line_subtotal' ];

				if ( !$item_in_cart ) {
					$cart_item_data = array(
						'nm_gift_registry' => array(
							'wishlist_id' => $wishlist_id,
							'wishlist_item_id' => $item_id,
							'product_id' => $item->get_product_id(),
							'product_name' => $item->get_product_name(),
							'contributed_price' => $price,
							'type' => 'crowdfund'
						),
					);

					$cart_item_key = wc()->cart->add_to_cart( $placeholder_product_id, 1, 0, array(), $cart_item_data );
				}

				if ( $cart_item_key ) {
					// Store the current contributed price subtotal
					wc()->cart->cart_contents[ $cart_item_key ][ 'nm_gift_registry' ][ 'contributed_price' ] = $price;

					wc()->cart->cart_contents[ $cart_item_key ][ 'data' ]->set_price( $price );
					wc()->cart->calculate_totals();

					/**
					 * The product has been updated in the cart rather than added
					 * (This flag is used to tweak the notice shown after the product has been added to the cart)
					 */
					if ( $item_in_cart ) {
						wc()->session->set( 'nmgr_cf_product_updated_in_cart', true );
					}

					if ( doing_action( 'wp_ajax_nopriv_nmgr_cf_add_to_cart' ) || doing_action( 'wp_ajax_nmgr_cf_add_to_cart' ) ) {
						// wc filter (check this filter on updates)
						do_action( 'woocommerce_ajax_added_to_cart', $placeholder_product_id );
					}

					wc_add_to_cart_message( $item->get_product_id() );

					unset( wc()->session->nmgr_cf_product_updated_in_cart );
				}
			} catch ( Exception $ex ) {
				wc_add_notice( $ex->getMessage(), 'error' );
			}

			if ( wp_doing_ajax() ) {
				$data = array(
					'product_id' => isset( $item ) && $item ? $item->get_product_id() : 0,
					'quantity' => 1,
					'wishlist_id' => $wishlist_id,
					'wishlist_item_id' => $item_id,
				);

				nmgr()->order()->add_add_to_cart_item_ref_data( $data );
			}
		}
	}

	/**
	 * Change the thumbnail of the crowdfund placeholder product in the cart to reflect the
	 * thumbnail of the product that is being crowdfunded.
	 * (This is necessary as the crowdfund placeholder product has a generic thumbnail and is being used to fund
	 * multiple items)
	 */
	public static function set_cart_item_thumbnail( $image, $cart_item ) {
		$crowdfund_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
		if ( $crowdfund_data ) {
			$product = wc_get_product( $crowdfund_data[ 'product_id' ] ?? 0 );

			if ( $product ) {
				$image = $product->get_image();
			}
		}
		return $image;
	}

	/**
	 * Change the name of the crowdfund placeholder product in the cart to reflect the name of the product
	 * that is being crowdfunded.
	 * (This is necessary as the crowdfund placeholder product has a generic name and is being used to fund
	 * multiple items)
	 */
	public static function set_cart_item_name( $product_name, $cart_item ) {
		$stored_name = nmgrcf_get_crowdfund_cart_item_name( $cart_item );
		return $stored_name ? $stored_name : $product_name;
	}

	/**
	 * Change the price of the crowdfund placeholder product in the cart to reflect the contributed price of the product
	 * that is being crowdfunded.
	 * (This is necessary as the crowdfund placeholder product has a zero price as it is used to fund
	 * multiple items)
	 */
	public static function set_cart_item_price( $price, $cart_item ) {
		$crowdfund_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
		if ( $crowdfund_data ) {
			return wc_price( $crowdfund_data[ 'contributed_price' ] );
		}
		return $price;
	}

	/**
	 * Set the subtotal of the contributed price for the crowdfunded product
	 * in the cart whenever the cart totals are calculated
	 */
	public static function set_cart_item_subtotal( $cart_object ) {
		foreach ( $cart_object->get_cart() as $cart_item ) {
			$crowdfund_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
			if ( $crowdfund_data ) {
				$cart_item[ 'data' ]->set_price( $crowdfund_data[ 'contributed_price' ] );
			}
		}
	}

	public static function show_cart_item_data( $item_data, $cart_item_data ) {
		$crowdfund_cart_data = nmgr_get_cart_item_data( $cart_item_data, 'crowdfund' );
		if ( !$crowdfund_cart_data || empty( array_filter( $crowdfund_cart_data ) ) ) {
			return $item_data;
		}

		$wishlist = nmgr_get_wishlist( $crowdfund_cart_data[ 'wishlist_id' ], true );

		if ( !$wishlist ) {
			return $item_data;
		}

		$title = sprintf(
			/* translators: %s: wishlist type title */
			__( 'You are partly buying this item for this %s', 'nm-gift-registry-crowdfunding' ),
			nmgr_get_type_title()
		);
		$item_data[] = array(
			'key' => sprintf(
				/* translators: %s: wishlist type title */
				__( 'For %s', 'nm-gift-registry-crowdfunding' ),
				nmgr_get_type_title()
			),
			'value' => nmgr_get_wishlist_link( $wishlist, array( 'title' => $title ) ),
			'display' => '',
		);

		return $item_data;
	}

	public static function get_crowdfund_wishlist_in_cart( $id, $cart ) {
		if ( !$id ) {
			foreach ( $cart as $cart_item ) {
				$nmgr_cf_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
				if ( $nmgr_cf_data ) {
					return $nmgr_cf_data[ 'wishlist_id' ];
				}
			}
		}
		return $id;
	}

	public static function get_crowdfund_wishlists_in_cart( $wishlists, $cart ) {
		foreach ( $cart as $cart_item ) {
			$nmgr_cf_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
			if ( $nmgr_cf_data ) {
				$wishlists[] = $nmgr_cf_data[ 'wishlist_id' ];
			}
		}
		return $wishlists;
	}

	/**
	 * Added to cart notice for crowdfund product
	 */
	public static function set_added_to_cart_notice( $message, $products ) {
		$crowdfund_data = null;
		foreach ( wc()->cart->get_cart_contents() as $content ) {
			$nmgr_cf_data = nmgr_get_cart_item_data( $content, 'crowdfund' );
			if ( $nmgr_cf_data && in_array( $nmgr_cf_data[ 'product_id' ], array_keys( $products ) ) ) {
				$crowdfund_data = $nmgr_cf_data;
			}
		}

		if ( !$crowdfund_data ) {
			return $message;
		}

		$str = '</a>';
		$str_pos = strpos( $message, $str );

		if ( !$str_pos ) {
			return $message;
		}

		$product_updated = wc()->session->get( 'nmgr_cf_product_updated_in_cart', false );
		$contributed_price = $crowdfund_data[ 'contributed_price' ];
		$product_name = $crowdfund_data[ 'product_name' ];

		if ( $product_updated ) {
			$text = sprintf(
				/* translators: 1: contribution amount, 2: product name */
				__( 'A total contribution of %1$s for &ldquo;%2$s&rdquo; has been added to your cart.', 'nm-gift-registry-crowdfunding' ),
				wc_price( $contributed_price ),
				$product_name
			);
		} else {
			$text = sprintf(
				/* translators: 1: contribution amount, 2: product name */
				__( 'A contribution of %1$s for &ldquo;%2$s&rdquo; has been added to your cart.', 'nm-gift-registry-crowdfunding' ),
				wc_price( $contributed_price ),
				$product_name
			);
		}

		$filtered_message = apply_filters( 'nmgrcf_add_to_cart_message', $text );
		$msg_last_part = substr( $message, $str_pos + strlen( $str ) );
		$message = str_replace( $msg_last_part, $filtered_message, $message );

		return $message;
	}

	/**
	 * Make sure everyone can purchase a crowdfund product
	 * @return boolean
	 */
	public static function make_product_purchasable_by_all( $bool, $product ) {
		if ( 'nmgr-crowdfunded' === $product->get_status() ) {
			return true;
		}
		return $bool;
	}

	public static function hide_cart_notice_for_contributions( $message ) {
		if ( $message && (nmgrcf_cart_has_crowdfund_contribution() || nmgrcf_cart_has_free_contribution()) ) {
			$notify = false;
			foreach ( wc()->cart->get_cart() as $cart_item ) {
				if ( isset( $cart_item[ 'nm_gift_registry' ] ) ) {
					$notify = true;
					break;
				}
			}

			if ( !$notify ) {
				return false;
			}
		}
		return $message;
	}

}
