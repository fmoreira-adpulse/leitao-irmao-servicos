<?php

defined( 'ABSPATH' ) || exit;

/**
 * Cart actions related to free contributions for a wishlist
 */
class NMGRCF_Cart_Free_Contribution {

	public static function run() {
		if ( !is_nmgrcf_free_contributions_enabled() ) {
			return;
		}

		add_action( 'wp_loaded', array( __CLASS__, 'add_free_contribution_to_cart' ) );
		add_action( 'wp_ajax_nmgrcf_fc_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_nmgrcf_fc_add_to_cart', [ __CLASS__, 'ajax_add_to_cart' ] );

		if ( wp_doing_ajax() && ('nmgrcf_fc_add_to_cart' === ($_POST[ 'action' ] ?? null) ) ) {
			remove_action( 'wp_loaded', array( __CLASS__, 'add_free_contribution_to_cart' ) );
		}

		add_filter( 'woocommerce_cart_item_thumbnail', [ __CLASS__, 'set_cart_item_thumbnail' ], 10, 2 );
		add_filter( 'woocommerce_cart_item_name', [ __CLASS__, 'set_cart_item_name' ], 10, 2 );
		add_filter( 'woocommerce_cart_item_removed_title', [ __CLASS__, 'set_cart_item_name' ], 10, 2 );
		add_filter( 'woocommerce_cart_item_price', [ __CLASS__, 'set_cart_item_price' ], 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', [ __CLASS__, 'set_cart_item_subtotal' ] );
		add_filter( 'woocommerce_get_item_data', [ __CLASS__, 'show_cart_item_data' ], 50, 2 );
		add_filter( 'nmgr_get_wishlist_in_cart', array( __CLASS__, 'get_wishlist_in_cart' ), 10, 2 );
		add_filter( 'nmgr_get_wishlists_in_cart', [ __CLASS__, 'get_wishlists_in_cart' ], 10, 2 );
		add_filter( 'wc_add_to_cart_message_html', [ __CLASS__, 'set_added_to_cart_notice' ] );
		add_filter( 'nmgr_featured_placeholder_svg', [ __CLASS__, 'set_cart_item_placeholder_thumbnail' ] );
	}

	public static function ajax_add_to_cart() {
		self::add_free_contribution_to_cart();

		$success = wc_notice_count( 'success' ) ? true : false;
		$redirect_url = $success ? apply_filters( 'woocommerce_add_to_cart_redirect', false, false ) : false;
		if ( $success && !$redirect_url && 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			$redirect_url = wc_get_cart_url();
		}

		// We are expecting notices from the add to cart action, so get them
		$custom_data = array(
			'success' => $success,
			'notices' => $success && $redirect_url ? '' : nmgr_get_wc_toast_notices(),
			'redirect_url' => esc_url( apply_filters( 'nmgrcf_free_contribution_ajax_add_to_cart_redirect_url', $redirect_url, $success ) ),
		);

		if ( class_exists( 'wc_ajax' ) && method_exists( 'wc_ajax', 'get_refreshed_fragments' ) ) {
			add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) use ( $custom_data ) {
				return array_merge( $fragments, $custom_data );
			} );

			WC_AJAX::get_refreshed_fragments();
		}
	}

	public static function add_free_contribution_to_cart() {
		if ( empty( $_POST[ 'nmgrcf_fc_add_to_cart_amt' ] ) || empty( $_POST[ 'nmgr_wid' ] ) ) {
			return;
		}

		$contribute_price = ( float ) $_POST[ 'nmgrcf_fc_add_to_cart_amt' ];
		$wishlist_id = ( int ) $_POST[ 'nmgr_wid' ];
		$cart_item_key = '';
		$item_in_cart = false;
		$wishlist = nmgr_get_wishlist( $wishlist_id );
		$amount_left = $wishlist->get_real_free_contributions_amount_left();
		$settings = $wishlist->get_free_contributions_settings();
		$minimum_amount = $settings[ 'minimum_amount' ];
		if ( $amount_left < nmgrcf_round( $minimum_amount ) ) {
			$minimum_amount = $amount_left;
		}

		$placeholder_product_id = nmgrcf_get_placeholder_product_id();

		if ( !wc_get_product( $placeholder_product_id ) ) {
			wc_add_notice( __( 'The contribution could not be added to the cart.', 'nm-gift-registry-crowdfunding' ) . 'error' );
			return;
		}

		try {
			if ( 0 >= nmgrcf_round( $contribute_price ) ) {
				throw new Exception( __( 'Please enter a valid contribution amount.', 'nm-gift-registry-crowdfunding' ) );
			}

			// Check if the contribution product is already in the cart for this wishlist item
			foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
				$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
				if ( $nmgr_fc_data && $wishlist_id === $nmgr_fc_data[ 'wishlist_id' ] ) {
					$item_in_cart = $cart_item;
					$cart_item_key = $key;
					break;
				}
			}

			/**
			 * Check that the amount added to cart is not less than the minimum set by the wishlist owner
			 * if the amount needed is not less than the minimum set by the wishlist owner.
			 */
			if ( !$item_in_cart && $minimum_amount &&
				nmgrcf_round( $contribute_price ) < nmgrcf_round( $minimum_amount ) &&
				($amount_left && nmgrcf_round( $contribute_price ) < nmgrcf_round( $amount_left ))
			) {
				throw new Exception( sprintf(
							/* translators: %s: minimum contribution amount */
							__( 'Please contribute a minimum of %s.', 'nm-gift-registry-crowdfunding' ),
							wc_price( $minimum_amount )
						) );
			}

			/**
			 * Check that the amount added to cart is not greater than the amount needed
			 */
			if ( !$item_in_cart && $amount_left &&
				(nmgrcf_round( $contribute_price ) > nmgrcf_round( $amount_left )) ) {
				throw new Exception( sprintf(
							/* translators: 1: amount contributed, 2: amount needed */
							__( 'The amount contributed (%1$s) is greater than the amount needed (%2$s). Please adjust the amount.', 'nm-gift-registry-crowdfunding' ),
							wc_price( $contribute_price ),
							wc_price( $amount_left )
						) );
			}

			/**
			 * Check that the amount added to cart + the amount already in cart is not greater than the amount needed
			 */
			if ( $item_in_cart && $amount_left &&
				( nmgrcf_round( $contribute_price + $item_in_cart[ 'line_subtotal' ] ) > nmgrcf_round( $amount_left ) )
			) {
				$cart_amount_needed = $amount_left - nmgrcf_round( $item_in_cart[ 'line_subtotal' ] );

				$notice = sprintf(
					/* translators: 1: amount contributed, 2: wishlist type title, 3: amount in cart  */
					__( 'You cannot contribute another %1$s to the %2$s as you already have %3$s in the cart for it.', 'nm-gift-registry-crowdfunding' ),
					wc_price( $contribute_price ),
					nmgr_get_type_title(),
					wc_price( $item_in_cart[ 'line_subtotal' ] )
				);

				if ( nmgrcf_round( $cart_amount_needed ) > nmgrcf_round( $minimum_amount ) ) {
					$notice .= ' ' . sprintf(
							/* translators: %s: amount needed */
							__( 'Please contribute a maximum of %s.', 'nm-gift-registry-crowdfunding' ),
							wc_price( $cart_amount_needed )
					);
				} else {
					$notice .= ' ' . __( 'Please remove the contribution in the cart and make a new contribution if necessary.', 'nm-gift-registry-crowdfunding' );
				}

				throw new Exception( $notice );
			}

			$price = !$item_in_cart ? $contribute_price : $contribute_price + $item_in_cart[ 'line_subtotal' ];

			if ( !$item_in_cart ) {
				$cart_item_data = array(
					'nmgr_wishlist' => $wishlist,
					'nm_gift_registry' => array(
						'wishlist_id' => $wishlist_id,
						'contributed_price' => $price,
						'type' => 'free_contribution',
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
					wc()->session->set( 'nmgrcf_free_contribution_updated_in_cart', true );
				}

				if ( doing_action( 'wp_ajax_nopriv_nmgrcf_free_contribution_add_to_cart' ) ||
					doing_action( 'wp_ajax_nmgrcf_free_contribution_add_to_cart' ) ) {
					// wc filter (check this filter on updates)
					do_action( 'woocommerce_ajax_added_to_cart', $placeholder_product_id );
				}

				wc_add_to_cart_message( $placeholder_product_id );

				unset( wc()->session->nmgrcf_free_contribution_updated_in_cart );
			}
		} catch ( Exception $exc ) {
			wc_add_notice( $exc->getMessage(), 'error' );
		}
	}

	/**
	 * Change the thumbnail of the free contribution placeholder product in the cart to reflect
	 * the thumbnail of the wishlist.
	 * (This is necessary as the free contribution placeholder product has a generic thumbnail
	 * and is being used to fund multiple items)
	 */
	public static function set_cart_item_thumbnail( $thumbnail, $cart_item ) {
		$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
		if ( $nmgr_fc_data && !empty( $cart_item[ 'nmgr_wishlist' ] ) ) {
			$wishlist_thumbnail = $cart_item[ 'nmgr_wishlist' ]->get_thumbnail();

			if ( $wishlist_thumbnail ) {
				$thumbnail = $wishlist_thumbnail;
			}
		}
		return $thumbnail;
	}

	/**
	 * Change the name of the crowdfund placeholder product in the cart to reflect the name of the
	 * free contribution
	 * (This is necessary as the crowdfund placeholder product has a generic name and is being used to fund
	 * multiple items)
	 */
	public static function set_cart_item_name( $product_name, $cart_item ) {
		$stored_name = nmgrcf_get_free_contribution_cart_item_name( $cart_item );
		return $stored_name ? $stored_name : $product_name;
	}

	/**
	 * Change the price of the crowdfund placeholder product in the cart to reflect the
	 * freely contributed price
	 * (This is necessary as the crowdfund placeholder product has a zero price as it is used to fund
	 * multiple items)
	 */
	public static function set_cart_item_price( $price, $cart_item ) {
		$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
		if ( $nmgr_fc_data ) {
			return wc_price( $nmgr_fc_data[ 'contributed_price' ] );
		}
		return $price;
	}

	/**
	 * Set the subtotal of the freely contributed price to the wishlist
	 * in the cart whenever the cart totals are calculated
	 */
	public static function set_cart_item_subtotal( $cart_object ) {
		foreach ( $cart_object->get_cart() as $cart_item ) {
			$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
			if ( $nmgr_fc_data ) {
				$cart_item[ 'data' ]->set_price( $nmgr_fc_data[ 'contributed_price' ] );
			}
		}
	}

	public static function show_cart_item_data( $item_data, $cart_item_data ) {
		$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item_data, 'free_contribution' );
		if ( !$nmgr_fc_data || empty( array_filter( $nmgr_fc_data ) ) ) {
			return $item_data;
		}

		$wishlist = $cart_item_data[ 'nmgr_wishlist' ] ?? null;

		/* translators: %s: wishlist type title */
		$title = sprintf( __( 'You have made a free contribution to this %s', 'nm-gift-registry-crowdfunding' ), esc_html( nmgr_get_type_title() ) );
		$item_data[] = array(
			/* translators: %s: wishlist type title */
			'key' => sprintf( __( 'For %s', 'nm-gift-registry-crowdfunding' ), esc_html( nmgr_get_type_title() ) ),
			'value' => nmgr_get_wishlist_link( $wishlist, array( 'title' => $title ) ),
			'display' => '',
		);

		return $item_data;
	}

	public static function get_wishlist_in_cart( $id, $cart ) {
		if ( !$id ) {
			foreach ( $cart as $cart_item ) {
				$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
				if ( $nmgr_fc_data ) {
					return $nmgr_fc_data[ 'wishlist_id' ];
				}
			}
		}
		return $id;
	}

	public static function get_wishlists_in_cart( $wishlists, $cart ) {
		foreach ( $cart as $cart_item ) {
			$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
			if ( $nmgr_fc_data ) {
				$wishlists[] = $nmgr_fc_data[ 'wishlist_id' ];
			}
		}
		return $wishlists;
	}

	/**
	 * Added to cart notice for free contribution
	 */
	public static function set_added_to_cart_notice( $message ) {
		$cart_item_fc_data = null;
		$cart_item = null;
		$added_amt = filter_input( INPUT_POST, 'nmgrcf_fc_add_to_cart_amt' );
		$wishlist_id = filter_input( INPUT_POST, 'nmgr_wid' );

		foreach ( wc()->cart->get_cart_contents() as $content ) {
			$nmgr_fc_data = nmgr_get_cart_item_data( $content, 'free_contribution' );
			if ( $added_amt && $nmgr_fc_data && $nmgr_fc_data[ 'wishlist_id' ] == $wishlist_id ) {
				$cart_item_fc_data = $nmgr_fc_data;
				$cart_item = $content;
			}
		}

		if ( !$cart_item_fc_data ) {
			return $message;
		}

		$str = '</a>';
		$str_pos = strpos( $message, $str );

		if ( !$str_pos ) {
			return $message;
		}

		$product_updated = wc()->session->get( 'nmgrcf_free_contribution_updated_in_cart', false );
		$contributed_price = $cart_item_fc_data[ 'contributed_price' ];
		$wishlist = $cart_item[ 'nmgr_wishlist' ] ?? null;

		if ( $product_updated ) {
			$text = sprintf(
				/* translators: 1: contribution amount, 2: wishlist title */
				__( 'A total contribution of %1$s to &ldquo;%2$s&rdquo; has been added to your cart.', 'nm-gift-registry-crowdfunding' ),
				wc_price( $contributed_price ),
				$wishlist ? $wishlist->get_title() : ''
			);
		} else {
			$text = sprintf(
				/* translators: 1: contribution amount, 2: wishlist title */
				__( 'A contribution of %1$s to &ldquo;%2$s&rdquo; has been added to your cart.', 'nm-gift-registry-crowdfunding' ),
				wc_price( $contributed_price ),
				$wishlist ? $wishlist->get_title() : ''
			);
		}

		$filtered_message = apply_filters( 'nmgrcf_free_contribution_add_to_cart_message', $text );
		$msg_last_part = substr( $message, $str_pos + strlen( $str ) );
		$final_message = str_replace( $msg_last_part, $filtered_message, $message );

		return $final_message;
	}

	/**
	 * Set the cart placeholder thumbnail that should appear for a free contribution
	 * if the wishlist has no actual thumbnail image.
	 *
	 * What we want to simply do here is to display the default placeholder svg in an img
	 * tag so that it can be styled properly with other product thumbnails in the cart table.
	 * For this we have to retrive the actual svg file without the sprite as using the sprite
	 * makes it difficult to base64_encode it.
	 */
	public static function set_cart_item_placeholder_thumbnail( $psvg ) {
		if ( !doing_filter( 'woocommerce_cart_item_thumbnail' ) ) {
			return $psvg;
		}

		$svg = nmgr_get_svg( array(
			'icon' => 'user',
			'size' => nmgr()->post_thumbnail_size() / 16, // convert px to em.
			'fill' => '#ccc',
			'class' => 'nmgr-post-thumbnail',
			'sprite' => false,
			'style' => 'max-width:100%;max-height:100%;background-color:#f8f8f8;',
			) );

		return '<img src="data:image/svg+xml;base64,' . base64_encode( $svg ) . '">';
	}

}
