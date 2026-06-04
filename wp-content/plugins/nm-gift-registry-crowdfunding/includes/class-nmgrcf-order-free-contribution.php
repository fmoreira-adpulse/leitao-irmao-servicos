<?php

defined( 'ABSPATH' ) || exit;

/**
 * Order actions related to free contributions
 */
class NMGRCF_Order_Free_Contribution {

	public static function run() {
		if ( !is_nmgrcf_free_contributions_enabled() ) {
			return;
		}

		add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'set_order_item_props' ], 10, 3 );
		add_action( 'woocommerce_before_order_itemmeta', [ __CLASS__, 'set_order_item_thumbnail' ], 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'add_order_item_data' ], 10, 3 );
		add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'add_order_data' ] );
		add_action( 'woocommerce_order_item_meta_start', [ __CLASS__, 'display_itemmeta_table_data' ], 10, 2 );
		add_action( 'woocommerce_before_order_itemmeta', [ __CLASS__, 'display_itemmeta_table_data' ], 10, 2 );
		add_filter( 'nmgr_shop_order_column_data', [ __CLASS__, 'recognise_wishlists_with_contributions' ], 10, 2 );
		add_filter( 'nmgr_do_order_payment_actions', [ __CLASS__, 'do_order_payment_actions' ], 10, 2 );
		add_action( 'nmgr_order_payment_complete', [ __CLASS__, 'update_free_contribution_reference' ], 10, 3 );
		add_action( 'nmgr_order_payment_complete', [ __CLASS__, 'email_customer_new_free_contribution' ], 99, 3 );
		add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'update_reference_ordered_amt' ], 10, 3 );
	}

	/**
	 * Set the properties of the free contribution order item to reflect the properties of the
	 * wishlist being contributed to rather than the crowdfund placeholder product.
	 *
	 * (Note that some properties cannot be set here such as the product thumbnail as they
	 * cannot be set in the class. They have to be set separately if there is a filter for them)
	 */
	public static function set_order_item_props( $item, $cart_item_key, $cart_item ) {
		$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
		if ( $nmgr_fc_data ) {
			$item->set_props( array(
				'name' => nmgrcf_get_free_contribution_cart_item_name( $cart_item ),
				'product_id' => 0 // Set product id to 0 to avoid associating the crowdfund contribution with a product in admin
			) );
		}
	}

	/**
	 * Set the thumbnail of each order item representing a free contribution
	 * in the order item table on the shop order page.
	 *
	 * This is necessary because the order item not attached to any product so it has no
	 * thumbnail by default. We want to attach the thumbnail of the wishlist that is
	 * receiving the free contribution.
	 *
	 * In this case we are adding the thumbnail directly to the order item table row and hiding it
	 * to display it in the appropriate cell for the thumbnail using js. This is a hack as there is
	 * no proper filter to do this.
	 */
	public static function set_order_item_thumbnail( $item_id, $item ) {
		$cf = $item->get_meta( 'nmgrcf_free_contribution' );

		if ( $cf ) {
			$wishlist = nmgr_get_wishlist( $cf[ 'wishlist_id' ] );

			if ( $wishlist ) {
				$wishlist_thumbnail = $wishlist->get_thumbnail();

				if ( $wishlist_thumbnail ) {
					echo '<div class="nmgrcf-contribution-order-item-thumbnail" style="display:none;">' .
					wp_kses( $wishlist_thumbnail, nmgr_allowed_post_tags() ) .
					'</div>';
				}
			}
		}
	}

	public static function add_order_item_data( $item, $cart_item_key, $values ) {
		$nmgr_fc_data = nmgr_get_cart_item_data( $values, 'free_contribution' );
		if ( $nmgr_fc_data ) {
			$item->add_meta_data( 'nmgrcf_free_contribution', $nmgr_fc_data );
		}
	}

	public static function add_order_data( $order_id ) {
		$wishlist_data = array();
		$order = wc_get_order( $order_id );

		if ( !$order ) {
			return;
		}

		foreach ( $order->get_items() as $order_item ) {
			$meta = $order_item->get_meta( 'nmgrcf_free_contribution' );
			if ( $meta ) {
				$wishlist_data[] = array_merge( $meta, array(
					'order_item_id' => $order_item->get_id(), // order item id
					) );
			}
		}

		if ( !empty( $wishlist_data ) ) {
			$order_meta = array();
			foreach ( $wishlist_data as $data ) {
				$order_meta[ $data[ 'wishlist_id' ] ][ 'wishlist_id' ] = $data[ 'wishlist_id' ];
				$order_meta[ $data[ 'wishlist_id' ] ][ 'order_item_id' ] = $data[ 'order_item_id' ];
				$order_meta[ $data[ 'wishlist_id' ] ][ 'sent_customer_new_free_contribution_email' ] = 'no';
			}

			$order->add_meta_data( 'nmgrcf_free_contribution', $order_meta );
			$order->save();
		}
	}

	public static function display_itemmeta_table_data( $item_id, $item ) {
		$meta = $item->get_meta( 'nmgrcf_free_contribution' );
		if ( $meta ) {
			$wishlist = nmgr_get_wishlist( $meta[ 'wishlist_id' ], true );

			if ( !$wishlist ) {
				return;
			}

			/* translators: %s: wishlist type title */
			$title = sprintf( __( 'This contribution is made to this %s', 'nm-gift-registry-crowdfunding' ), nmgr_get_type_title() );
			$link = nmgr_get_wishlist_link( $wishlist, array( 'title' => $title ) );

			echo '<div class="nmgr-order-item-wishlist">'
			/* translators: %s: wishlist type title */
			. sprintf( esc_html__( 'For %s: ', 'nm-gift-registry-crowdfunding' ), esc_html( nmgr_get_type_title() ) )
			. wp_kses( $link, nmgr_allowed_post_tags() )
			. '</div>';
		}
	}

	/**
	 * Make order list table recognise wishlists with free contributions.
	 */
	public static function recognise_wishlists_with_contributions( $column_data, $order ) {
		$fc = $order->get_meta( 'nmgrcf_free_contribution' );

		if ( !empty( $fc ) ) {
			foreach ( $fc as $wishlist_data ) {
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

	public static function do_order_payment_actions( $bool, $order ) {
		return $order->get_meta( 'nmgrcf_free_contribution' ) ? true : $bool;
	}

	public static function update_free_contribution_reference( $order_id, $order_wishlist_data, $order ) {
		$contribution_data = $order->get_meta( 'nmgrcf_free_contribution' );

		if ( !$contribution_data ) {
			return;
		}

		$refunded_wishlists = array();

		foreach ( $contribution_data as $wishlist_id => $data ) {
			$wishlist = nmgr_get_wishlist( $wishlist_id );
			$order_item_id = $data[ 'order_item_id' ];

			if ( !$wishlist ) {
				continue;
			}

			// Get the wishlist's free contribution reference from the database
			$fcr_meta = $wishlist->get_free_contributions_reference();
			$contribution_reference = $fcr_meta ? $fcr_meta : array();

			/**
			 * If the free contribution reference doesn't exist for this order,
			 * flag this as a new free contribution order.
			 */
			$fc_is_new = isset( $contribution_reference[ $order_id ] ) ? false : true;

			// Get the order item
			$order_item = $order->get_item( $order_item_id );

			/**
			 * If we don't have the order item object, we assume the item has been removed from the order
			 * (This might be due to a refund or something).
			 * So we simply delete the free contribution reference of this item for the order if it exists,
			 * and return
			 */
			if ( !$order_item ) {
				if ( isset( $contribution_reference[ $order_id ] ) ) {
					$original_purchased_amount = nmgrcf_round( array_sum( wp_list_pluck( $contribution_reference, 'purchased_amount' ) ) );

					unset( $contribution_reference[ $order_id ] );

					$new_purchased_amount = nmgrcf_round( array_sum( wp_list_pluck( $contribution_reference, 'purchased_amount' ) ) );

					$wishlist->update_free_contributions_reference( $contribution_reference );

					/**
					 * The wishlist was previously in the order because it has a contribution reference
					 * for the order id, but since it no longer has it, let's assume that the refunded
					 * amount of the wishlist has changed. (That is, all of the contribution is refunded).
					 * So we add the wishlist to the refunded_wishlists array.
					 */
					if ( $new_purchased_amount < $original_purchased_amount ) {
						$refunded_wishlists[ $wishlist->get_id() ] = $original_purchased_amount - $new_purchased_amount;
					}
				}
				continue;
			}

			// Set a default fre contribution reference for new wishlists
			$default_fcr = array(
				'ordered_amount' => 0, // contribution made in order but not yet processed
				'refunded_amount' => 0, // contribution made in order but refunded
				'purchased_amount' => 0, // contribution made in order and processed or completed
			);

			/**
			 * If this wishlist's free contribution reference has not be set for this order, set the new one
			 * else get the one set for this order
			 */
			$fcr = $fc_is_new ? $default_fcr : $contribution_reference[ $order_id ];

			// Set a new contribution reference for the wishlist for this order based on current contribution price
			$ordered_amount = $order_item->get_total();

			$_refunded_amount = 0; // this is always negative
			foreach ( $order->get_refunds() as $refund ) {
				foreach ( $refund->get_items( 'line_item' ) as $refunded_item ) {
					if ( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === $order_item_id ) {
						$_refunded_amount += $refunded_item->get_total();
					}
				}
			}
			$refunded_amount = $_refunded_amount * -1;

			$new_fcr = array(
				'ordered_amount' => $ordered_amount,
				'refunded_amount' => $refunded_amount,
				'purchased_amount' => $ordered_amount - $refunded_amount,
			);

			/**
			 * If the order payment is cancelled, update the contribution reference
			 * and purchased amount for the wishlist
			 */
			if ( $order->has_status( nmgr_get_payment_cancelled_order_statuses() ) ) {
				$new_fcr[ 'purchased_amount' ] = 0;
			}

			/**
			 * If the contribution is not new and the new purchased amount is less than the old purchased amount,
			 * we assume the contribution is refunded  (or the order payment is cancelled)
			 * so we add it to the refunded_items array
			 */
			if ( !$fc_is_new && ( nmgrcf_round( $new_fcr[ 'purchased_amount' ] ) < nmgrcf_round( $fcr[ 'purchased_amount' ] ) ) ) {
				$refunded_wishlists[ $wishlist->get_id() ] = $fcr[ 'purchased_amount' ] - $new_fcr[ 'purchased_amount' ];
			}

			/**
			 * if the stored free contribution reference is not equal to the new free contribution reference
			 * the order contribution reference purchased amount might have changed, so update it
			 */
			if ( $fcr !== $new_fcr ) {
				$contribution_reference[ $order_id ] = $new_fcr;
				$wishlist->update_free_contributions_reference( $contribution_reference );
			}
		}

		// If we have items in the refunded_items array, set up the refund action.
		if ( !empty( $refunded_wishlists ) ) {
			self::email_customer_refunded_free_contribution( $refunded_wishlists, $order );
		}
	}

	/**
	 * Update the crowdfunded amount for a wishlist item when an order is created
	 */
	public static function update_reference_ordered_amt( $order_id, $posted_data, $order ) {
		$order_wishlist_data = $order->get_meta( 'nmgrcf_free_contribution' );
		if ( $order_wishlist_data ) {
			self::update_free_contribution_reference( $order_id, $order_wishlist_data, $order );
		}
	}

	public static function email_customer_new_free_contribution( $order_id, $order_wishlist_data, $order ) {
		WC()->mailer();

		if ( !$order->is_paid() || !class_exists( \NMGR\Sub\Email::class ) ) {
			return;
		}

		$order_fc_data = $order->get_meta( 'nmgrcf_free_contribution' );

		if ( empty( $order_fc_data ) ) {
			return;
		}

		// Loop through the wishlists in the order and send email only if it hasn't been sent
		foreach ( $order_fc_data as $wishlist_id => $wishlist_data ) {
			if ( isset( $wishlist_data[ 'sent_customer_new_free_contribution_email' ] ) &&
				'no' === $wishlist_data[ 'sent_customer_new_free_contribution_email' ] ) {
				$order_item = $order->get_item( $wishlist_data[ 'order_item_id' ] );

				if ( !$order_item ) {
					continue;
				}
				$emailer = nmgr()->email( 'email_customer_new_free_contribution', $wishlist_id );
				$emailer->template_args[ 'order' ] = $order;
				$emailer->template_args[ 'order_item' ] = $order_item;
				$emailer->template_args[ 'order_customer_name' ] = nmgr()->mailer()->get_order_customer_name( $order );
				$emailer->template_args[ 'wishlist' ] = nmgr_get_wishlist( $wishlist_id );
				$emailer->template_base = nmgrcf()->path . 'templates/';
				$emailer->trigger();

				$order_fc_data[ $wishlist_id ][ 'sent_customer_new_free_contribution_email' ] = 'yes';
				$order->update_meta_data( 'nmgrcf_free_contribution', $order_fc_data );
				$order->save();
			}
		}
	}

	public static function email_customer_refunded_free_contribution( $refunded_wishlists, $order ) {
		WC()->mailer();

		if ( !class_exists( \NMGR\Sub\Email::class ) ) {
			return;
		}

		foreach ( $refunded_wishlists as $wishlist_id => $refunded_amount ) {
			$emailer = nmgr()->email( 'email_customer_refunded_free_contribution', $wishlist_id );
			$emailer->template_args[ 'order' ] = $order;
			$emailer->template_args[ 'refunded_amount' ] = $refunded_amount;
			$emailer->template_args[ 'order_customer_name' ] = nmgr()->mailer()->get_order_customer_name( $order );
			$emailer->template_args[ 'wishlist' ] = nmgr_get_wishlist( $wishlist_id );
			$emailer->template_base = nmgrcf()->path . 'templates/';
			$emailer->trigger();
		}
	}

}
