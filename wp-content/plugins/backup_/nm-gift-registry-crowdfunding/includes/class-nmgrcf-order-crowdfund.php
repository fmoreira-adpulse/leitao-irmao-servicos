<?php

/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

/**
 * Order actions related to crowdfunding
 * @sync
 */
class NMGRCF_Order_Crowdfund {

	public static function run() {
		add_action( 'admin_footer', array( __CLASS__, 'show_contribution_order_item_thumbnail' ) );

		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return;
		}

		add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'set_order_item_props' ], 10, 3 );
		add_action( 'woocommerce_before_order_itemmeta', [ __CLASS__, 'set_order_item_thumbnail' ], 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'order_item_meta_data' ], 10, 3 );
		add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'order_meta_data' ] );
		add_filter( 'nmgr_wishlist_get_items_in_order', [ __CLASS__, 'set_wishlist_item_in_order' ], 10, 3 );
		add_filter( 'nmgr_item_is_fulfilled', [ __CLASS__, 'set_crowdfund_item_fulfilled_condition' ], 10, 2 );
		add_filter( 'nmgr_do_order_payment_actions', [ __CLASS__, 'do_order_payment_actions' ], 10, 2 );
		add_action( 'nmgr_order_payment_complete', [ __CLASS__, 'update_wishlist_item_crowdfund_received_amt' ] );
		add_action( 'nmgr_order_payment_complete', [ __CLASS__, 'email_customer_new_contribution' ], 99, 3 );
		add_filter( 'nmgr_shop_order_column_data', [ __CLASS__, 'recognise_wishlists_with_ccontributions' ], 10, 2 );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', [ __CLASS__, 'format_item_data' ], 10, 2 );
	}

	/**
	 * Show thumbnails in the appropriate thumbnail table cell
	 * for crowdfund and free contributions on the edit order page if these have been set.
	 *
	 * This is the final part of the hack that is used to show order item thumbnails for
	 * crowdfunds and free contributions which are usually associated with a placeholder
	 * product which has no default thumbnail.
	 */
	public static function show_contribution_order_item_thumbnail() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && in_array( $screen->id, [ 'shop_order', 'woocommerce_page_wc-orders' ] ) &&
			'edit' === ( $_GET[ 'action' ] ?? '' ) ) {
			?>
			<script type="text/javascript">
				var containers = document.querySelectorAll('.nmgrcf-contribution-order-item-thumbnail');
				if (containers.length) {
					for (var container of containers) {
						var wrapper = container.closest('tr.item').querySelector('.wc-order-item-thumbnail');
						wrapper.innerHTML = container.innerHTML;
					}
				}
			</script>
			<?php

		}
	}

	/**
	 * Set the properties of the crowdfund order item to reflect the properties of the
	 * wishlist product being crowdfunded rather than the crowdfund placeholder product.
	 *
	 * (Note that some properties cannot be set here such as the product thumbnail as they
	 * cannot be set in the class. They have to be set separately if there is a filter for them)
	 */
	public static function set_order_item_props( $item, $cart_item_key, $cart_item ) {
		$nmgr_cf_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
		if ( $nmgr_cf_data ) {
			$item->set_props( array(
				'name' => nmgrcf_get_crowdfund_cart_item_name( $cart_item ),
				'product_id' => 0 // Set product id to 0 to avoid associating the crowdfund contribution with a product in admin
			) );
		}
	}

	/**
	 * Set the thumbnail of each order item representing a crowdfund contribution
	 * in the order item table on the shop order page.
	 *
	 * This is necessary because the order item not attached to any product so it has no
	 * thumbnail by default. We want to attach the thumbnail of the product that is being
	 * crowdfunded.
	 *
	 * In this case we are adding the thumbnail directly to the order item table row and hiding it
	 * to display it in the appropriate cell for the thumbnail using js. This is a hack as there is
	 * no proper filter to do this.
	 */
	public static function set_order_item_thumbnail( $item_id, $item ) {
		$cf = $item->get_meta( 'nmgrcf_item_id' );

		if ( $cf ) {
			$item = nmgr_get_wishlist_item( $cf );
			if ( $item ) {
				$product = $item->get_product_image( 'thumbnail', [ 'title' => '' ] );

				if ( $product ) {
					echo '<div class="nmgrcf-contribution-order-item-thumbnail" style="display:none;">' .
					wp_kses_post( $product ) .
					'</div>';
				}
			}
		}
	}

	public static function order_item_meta_data( $item, $cart_item_key, $values ) {
		$nmgr_cf_data = nmgr_get_cart_item_data( $values, 'crowdfund' );
		if ( $nmgr_cf_data ) {
			$item->add_meta_data( 'nmgrcf_item_id', $nmgr_cf_data[ 'wishlist_item_id' ] );
			$item->add_meta_data( 'nmgrcf_wishlist_id', $nmgr_cf_data[ 'wishlist_id' ] );
		}
	}

	public static function order_meta_data( $order_id ) {
		$wishlist_items_data = array();
		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $order_item ) {
			$cf_item_id = $order_item->get_meta( 'nmgrcf_item_id' );

			if ( $cf_item_id ) {
				$wishlist_items_data[] = array(
					'order_item_id' => $order_item->get_id(), // order item id
					'wishlist_item_id' => $cf_item_id,
					'wishlist_id' => $order_item->get_meta( 'nmgrcf_wishlist_id' ),
				);
			}
		}

		if ( !empty( $wishlist_items_data ) ) {
			$order_meta = array();
			foreach ( $wishlist_items_data as $data ) {
				$order_meta[ $data[ 'wishlist_id' ] ][ 'wishlist_id' ] = $data[ 'wishlist_id' ];
				$order_meta[ $data[ 'wishlist_id' ] ][ 'wishlist_item_ids' ][] = $data[ 'wishlist_item_id' ];
				$order_meta[ $data[ 'wishlist_id' ] ][ 'order_item_ids' ][ $data[ 'wishlist_item_id' ] ] = $data[ 'order_item_id' ];
				$order_meta[ $data[ 'wishlist_id' ] ][ 'sent_customer_new_contribution_email' ] = 'no';
			}

			$order->add_meta_data( 'nmgr_cf', $order_meta );
			$order->save();
		}
	}

	/**
	 * This function allows us to specify that there are wishlist items in the order (from a crowdfunded item)
	 * because normally these items are not detected as normal wishlist items since they are paid for
	 * differently.
	 *
	 * The function is mainly used in the NMGR_Wishlist class for wishlist messages.
	 */
	public static function set_wishlist_item_in_order( $items_in_order, $order, $wishlist ) {
		foreach ( $order->get_items() as $item_id => $item ) {
			$cf_wishlist_id = $item->get_meta( 'nmgrcf_wishlist_id' );
			if ( $cf_wishlist_id && $cf_wishlist_id == $wishlist->get_id() ) {
				$items_in_order[ $item_id ] = array(
					'name' => $item->get_name(),
					'quantity' => $item->get_quantity(),
					'variation_id' => $item->get_variation_id(),
					'total' => $item->get_total() - $order->get_total_refunded_for_item( $item_id ),
				);
			}
		}

		return $items_in_order;
	}

	public static function set_crowdfund_item_fulfilled_condition( $bool, $item ) {
		if ( $item->is_crowdfunded() ) {
			return 0 >= $item->get_unpurchased_quantity();
		}
		return $bool;
	}

	public static function do_order_payment_actions( $bool, $order ) {
		return !empty( nmgrcf_get_order_wishlist_item_ids( $order->get_id() ) ) ? true : $bool;
	}

	/**
	 * Update the crowdfund amount of a wishlist item
	 */
	public static function update_wishlist_item_crowdfund_received_amt( $order_id ) {
		$refunded_items = array();
		$wishlist_item_ids = nmgrcf_get_order_wishlist_item_ids( $order_id );

		foreach ( $wishlist_item_ids as $wishlist_item_id ) {
			$wishlist_item = nmgr_get_wishlist_item( $wishlist_item_id );
			if ( $wishlist_item && $wishlist_item->is_crowdfunded() ) {
				$order_item_ids = $wishlist_item->get_crowdfund_order_item_ids();
				$total_ordered_amt = 0;

				if ( empty( $order_item_ids ) ) {
					continue;
				}

				foreach ( $order_item_ids as $oid ) {
					$torder = wc_get_order( wc_get_order_id_by_order_item_id( $oid ) );
					$order_item = $torder->get_item( $oid );
					/**
					 * We have to use get_total() instead of get_subtotal() in order to account for
					 * discounts applied to the item such as wallet discount.
					 */
					$total_ordered_amt += $order_item->get_total() - $torder->get_total_refunded_for_item( $oid );
				}

				$current_ordered_amt = nmgrcf_round( $wishlist_item->get_crowdfund_amount_received() );
				$new_amt = nmgrcf_round( $total_ordered_amt );

				if ( $current_ordered_amt !== $new_amt ) {
					$wishlist_item->set_crowdfund_amount_received( $new_amt );

					// Refund
					if ( $new_amt < $current_ordered_amt ) {
						$refunded_items[ $wishlist_item->get_id() ] = $current_ordered_amt - $new_amt;
					}
				}

				// Item purchased quantity may need to be updated
				$qty = $wishlist_item->has_fulfill_amount() ? $wishlist_item->get_quantity() : 0;
				$wishlist_item->set_purchased_quantity( $qty );

				$wishlist_item->save();
			}
		}

		// If we have items in the refunded_items array, set up the refund action.
		if ( !empty( $refunded_items ) ) {
			$order = wc_get_order( $order_id );
			$wishlist_crowdfund_data = array_filter( ( array ) $order->get_meta( 'nmgr_cf' ) );
			self::email_customer_refunded_crowdfund_contribution( $refunded_items, $wishlist_crowdfund_data, $order );
		}
	}

	/**
	 * Email the customer when a new crowdfund contribution has been made for an item in his wishlist
	 *
	 * @param int $order_id The order id
	 * @param array $order_wishlist_data The order meta value which holds all the information for the wishlists in the order
	 * @param WC_Order $order
	 */
	public static function email_customer_new_contribution( $order_id, $order_wishlist_data, $order ) {
		WC()->mailer();
		// As a precaution, let's just make sure we're doing this only if the order is paid
		if ( !$order->is_paid() || !class_exists( \NMGR\Sub\Email::class ) ) {
			return;
		}

		/**
		 * $order_wishlist_data maybe be empty but we are not using it anyway because we want to get
		 * information specifically for crowdfund contributions in the order so we use the 'nmgr_cf meta value in the order.
		 */
		$order_crowdfund_data = $order->get_meta( 'nmgr_cf' );

		if ( empty( $order_crowdfund_data ) ) {
			return;
		}

		// Loop through the wishlists in the order and send email only if it hasn't been sent
		foreach ( $order_crowdfund_data as $wishlist_id => $wishlist_data ) {
			if ( isset( $wishlist_data[ 'sent_customer_new_contribution_email' ] ) &&
				'no' === $wishlist_data[ 'sent_customer_new_contribution_email' ] ) {
				$emailer = nmgr()->email( 'email_customer_new_crowdfund_contribution', $wishlist_id );
				$emailer->template_args[ 'order' ] = $order;
				$emailer->template_args[ 'order_item_ids' ] = $wishlist_data[ 'order_item_ids' ];
				$emailer->template_args[ 'order_customer_name' ] = nmgr()->mailer()->get_order_customer_name( $order );
				$emailer->template_args[ 'wishlist' ] = nmgr_get_wishlist( $wishlist_id );
				$emailer->template_base = nmgrcf()->path . 'templates/';
				$emailer->trigger();

				$order_crowdfund_data[ $wishlist_id ][ 'sent_customer_new_contribution_email' ] = 'yes';
				$order->update_meta_data( 'nmgr_cf', $order_crowdfund_data );
				$order->save();
			}
		}
	}

	public static function email_customer_refunded_crowdfund_contribution( $refunded_items, $order_crowdfund_data, $order ) {
		WC()->mailer();
		$refunded_wishlists = array();
		$wishlists_to_wishlist_item_ids = wp_list_pluck( $order_crowdfund_data, 'wishlist_item_ids' );

		foreach ( $wishlists_to_wishlist_item_ids as $wishlist_id => $wishlist_item_ids ) {
			$refunded_items_in_wishlist = array_intersect_key( $refunded_items, array_flip( $wishlist_item_ids ) );
			if ( !empty( $refunded_items_in_wishlist ) ) {
				$refunded_wishlists[ $wishlist_id ] = $refunded_items_in_wishlist;
			}
		}

		if ( class_exists( \NMGR\Sub\Email::class ) && !empty( $refunded_wishlists ) ) {
			foreach ( $refunded_wishlists as $wishlist_id => $wishlist_item_ids_to_amts ) {
				$emailer = nmgr()->email( 'email_customer_refunded_crowdfund_contribution', $wishlist_id );
				$emailer->template_args[ 'order' ] = $order;
				$emailer->template_args[ 'wishlist_item_ids_to_amts' ] = $wishlist_item_ids_to_amts;
				$emailer->template_args[ 'order_customer_name' ] = nmgr()->mailer()->get_order_customer_name( $order );
				$emailer->template_args[ 'wishlist' ] = nmgr_get_wishlist( $wishlist_id );
				$emailer->template_base = nmgrcf()->path . 'templates/';
				$emailer->trigger();
			}
		}
	}

	/**
	 * Make order list table recognise wishlists with crowdfunded items.
	 */
	public static function recognise_wishlists_with_ccontributions( $column_data, $order ) {
		$cf = $order->get_meta( 'nmgr_cf' );

		if ( !empty( $cf ) ) {
			foreach ( $cf as $wishlist_data ) {
				if ( isset( $wishlist_data[ 'wishlist_id' ] ) ) {
					$wishlist_id = $wishlist_data[ 'wishlist_id' ];
					$item_count = isset( $wishlist_data[ 'order_item_ids' ] ) ?
						count( $wishlist_data[ 'order_item_ids' ] ) :
						0;

					if ( isset( $column_data[ $wishlist_id ] ) ) {
						$column_data[ $wishlist_id ][ 'item_count' ] = $column_data[ $wishlist_id ][ 'item_count' ] + $item_count;
					} else {
						$column_data[ $wishlist_id ] = array(
							'wishlist_id' => $wishlist_id,
							'item_count' => $item_count
						);
					}
				}
			}
		}

		return $column_data;
	}

	/**
	 * Show the wishlist title as item meta data whenever the item meta data is displayed
	 */
	public static function format_item_data( $meta_array, $item ) {
		foreach ( $meta_array as $id => $meta ) {
			if ( 'nmgrcf_wishlist_id' === $meta->key ) {
				$wishlist = nmgr_get_wishlist( $meta->value, true );

				if ( $wishlist ) {
					$title = sprintf(
						/* translators: %s: wishlist type title */
						__( 'This contribution is made for an item in this %s', 'nm-gift-registry-crowdfunding' ),
						nmgr_get_type_title()
					);

					$show_link = true;
					if ( is_admin() ) {
						$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
						if ( !$screen ||
							($screen && !in_array( $screen->id,
								[ 'shop_order', 'edit-shop_order', 'woocommerce_page_wc-orders' ] )
							) ) {
							$show_link = false;
						}
					}

					$meta_array[ $id ]->display_key = nmgr_get_type_title( 'c' );
					$meta_array[ $id ]->display_value = $show_link ?
						nmgr_get_wishlist_link( $wishlist, [ 'title' => $title ] ) :
						$wishlist->get_title();
				}
			}

			if ( in_array( $meta->key, [ 'nmgrcf_item_id' ] ) ) {
				unset( $meta_array[ $id ] );
			}
		}

		return $meta_array;
	}

}
