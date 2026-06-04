<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Mailer {

	public static function run() {
		/**
		 * Priority 99 allows these emails to be sent after any custom operations on the wishlist has been carried out,
		 * for example updating the purchased quantity of wishlist items.
		 */
		add_action( 'nmgr_created_wishlist', array( __CLASS__, 'admin_new_wishlist' ), 99 );
		add_action( 'nmgr_created_wishlist', array( __CLASS__, 'customer_new_wishlist' ), 99 );
		add_action( 'nmgr_created_order', array( __CLASS__, 'customer_ordered_items' ), 99 );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'customer_ordered_items' ), 99 );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'customer_new_message' ), 99 );
		add_action( 'nmgr_order_payment_complete', array( __CLASS__, 'customer_purchased_items' ), 99 );
		add_action( 'nmgr_order_items_refunded', array( __CLASS__, 'customer_refunded_items' ), 99, 2 );
		add_action( 'nmgr_fulfilled_wishlist', array( __CLASS__, 'customer_fulfilled_wishlist' ), 10 );
		add_action( 'nmgr_fulfilled_wishlist', array( __CLASS__, 'admin_fulfilled_wishlist' ), 10 );
		add_action( 'nmgr_trashed_wishlist', array( __CLASS__, 'customer_deleted_wishlist' ), 99 );
	}

	/**
	 * Email the chosen recipients when a new wishlist has been created
	 *
	 * @param int $wishlist_id Wishlist id
	 */
	public static function admin_new_wishlist( $wishlist_id ) {
		WC()->mailer();
		$wishlist_details = self::get_wishlist_details( $wishlist_id );

		$emailer = nmgr()->email( 'email_admin_new_wishlist', $wishlist_id );
		$emailer->template_args[ 'wishlist_details' ] = $wishlist_details;
		$emailer->trigger();
	}

	/**
	 * Email the customer when he has created a new wishlist
	 *
	 * @param int $wishlist_id Id of Wishlist
	 */
	public static function customer_new_wishlist( $wishlist_id ) {
		WC()->mailer();
		$wishlist_details = self::get_wishlist_details( $wishlist_id );

		$emailer = nmgr()->email( 'email_customer_new_wishlist', $wishlist_id );
		$emailer->template_args[ 'wishlist_details' ] = $wishlist_details;
		$emailer->trigger();
	}

	/**
	 * Email the customer when items in his wishlist have been ordered for him
	 *
	 * @param int $order_id The order id
	 */
	public static function customer_ordered_items( $order_id ) {
		/**
		 * This email is sent on the following hooks:
		 * - nmgr_created_order (Triggers whenever an order is created in admin or frontend checkout)
		 * - woocommerce_checkout_order_processed (Triggers only on frontend checkout for created order)
		 *
		 * To prevent the email from being sent twice on the checkout page we have to make it send only on
		 * woocommerce_checkout_order_processed on the frontend. This leaves it to run on nmgr_created_order
		 * in admin.
		 *
		 * The reason why it is run on both hooks is because of checkout messages. The email can contain
		 * checkout messages if sent. But nmgr_create_order runs immediately after the order is created,
		 * before the checkout messages are saved. This is why we run the function on the
		 * woocommerce_checkout_order_processed hook on frontend because by the time this hook is fired,
		 * the order has been created and the checkout message has been saved to the wishlist with the
		 * order number.
		 *
		 */
		if ( !is_admin() && doing_action( 'nmgr_created_order' ) ) {
			return;
		}

		WC()->mailer();

		$order_data = new \NMGR_Order_Data( $order_id );
		$order = $order_data->get_order();

		if ( !$order ) {
			return;
		}

		foreach ( $order_data->get_wishlist_ids() as $wishlist_id ) {
			$emailer = nmgr()->email( 'email_customer_ordered_items', $wishlist_id );
			$emailer->template_args[ 'order' ] = $order;
			$emailer->template_args[ 'order_customer_name' ] = self::get_order_customer_name( $order );
			$emailer->template_args[ 'order_item_ids' ] = $order_data->get_order_item_ids_for_wishlist( $wishlist_id );

			if ( nmgr_get_option( 'email_customer_ordered_items_checkout_message' ) ) {
				$message_obj = $emailer->get_wishlist()->get_message_in_order( $order->get_id() );
				if ( $message_obj ) {
					$emailer->template_args[ 'message' ] = $message_obj->content;
				}
			}

			if ( $order->is_created_via( 'nmgr_wishlist' ) ) {
				if ( 'plain' === $emailer->get_email_type() ) {
					$custom_notice = nmgr_get_custom_order_notice() . "\n\n";
				} else {
					$custom_notice = '<p><i>' . nmgr_get_custom_order_notice() . '<i></p>';
				}
				$emailer->template_args[ 'custom_order_notice' ] = $custom_notice;
			}

			$emailer->trigger();
		}
	}

	/**
	 * Email the customer when items in his wishlist have been purchased in an order
	 *
	 * @param int $order_id The order id
	 * @param array $order_wishlist_data The order meta value which holds all the information for the wishlists in the order
	 * @param int|WC_Order $order
	 */
	public static function customer_purchased_items( $order_id ) {
		WC()->mailer();

		$order_data = new \NMGR_Order_Data( $order_id );
		$order = $order_data->get_order();

		// As a precaution, let's just make sure we're doing this only if the order is paid
		if ( !$order || !$order->is_paid() ) {
			return;
		}

		$order_wishlist_data = $order_data->get_meta();

		// Loop through the wishlists in the order and send email only if it hasn't been sent
		foreach ( $order_data->get_wishlist_ids() as $wishlist_id ) {
			if ( isset( $order_wishlist_data[ $wishlist_id ][ 'sent_customer_purchased_items_email' ] ) &&
				'no' === $order_wishlist_data[ $wishlist_id ][ 'sent_customer_purchased_items_email' ] ) {
				$emailer = nmgr()->email( 'email_customer_purchased_items', $wishlist_id );
				$emailer->template_args[ 'order' ] = $order;
				$emailer->template_args[ 'order_item_ids' ] = $order_data->get_order_item_ids_for_wishlist( $wishlist_id );
				$emailer->template_args[ 'order_customer_name' ] = self::get_order_customer_name( $order );

				if ( nmgr_get_option( 'email_customer_purchased_items_checkout_message' ) ) {
					$message_obj = $emailer->get_wishlist()->get_message_in_order( $order->get_id() );
					if ( $message_obj ) {
						$emailer->template_args[ 'message' ] = $message_obj->content;
					}
				}

				if ( $order->is_created_via( 'nmgr_wishlist' ) ) {
					if ( 'plain' === $emailer->get_email_type() ) {
						$custom_notice = nmgr_get_custom_order_notice() . "\n\n";
					} else {
						$custom_notice = '<p><i>' . nmgr_get_custom_order_notice() . '<i></p>';
					}
					$emailer->template_args[ 'custom_order_notice' ] = $custom_notice;
				}

				$emailer->trigger();

				$order_wishlist_data[ $wishlist_id ][ 'sent_customer_purchased_items_email' ] = 'yes';
				$order->update_meta_data( 'nm_gift_registry', $order_wishlist_data );
				$order->save();
			}
		}
	}

	/**
	 * Email the customer when items in his wishlist have been refunded.
	 *
	 * This is determined by the stock of the items in the order being reduced
	 *
	 * @param array $refunded_items Array of refunded items wishlist_item_ids to the refunded quantities
	 * @param int|WC_Order $order_id The order id or object
	 */
	public static function customer_refunded_items( $refunded_items, $order_id ) {
		WC()->mailer();

		$refunded_wishlists = array();
		$order_data = new \NMGR_Order_Data( $order_id );
		$order = $order_data->get_order();
		$order_wishlist_data = $order_data->get_meta();
		$wishlists_to_wishlist_item_ids = wp_list_pluck( $order_wishlist_data, 'wishlist_item_ids' );

		foreach ( $wishlists_to_wishlist_item_ids as $wishlist_id => $wishlist_item_ids ) {
			$refunded_items_in_wishlist = array_intersect_key( $refunded_items, array_flip( $wishlist_item_ids ) );
			if ( !empty( $refunded_items_in_wishlist ) ) {
				$refunded_wishlists[ $wishlist_id ] = $refunded_items_in_wishlist;
			}
		}

		if ( !empty( $refunded_wishlists ) ) {
			foreach ( $refunded_wishlists as $wishlist_id => $wishlist_item_ids_to_qtys ) {
				$emailer = nmgr()->email( 'email_customer_refunded_items', $wishlist_id );
				$emailer->template_args[ 'order' ] = $order;
				$emailer->template_args[ 'order_customer_name' ] = self::get_order_customer_name( $order );
				$emailer->template_args[ 'wishlist_item_ids_to_qtys' ] = $wishlist_item_ids_to_qtys;
				$emailer->trigger();
			}
		}
	}

	/**
	 * Email the customer when all the items in his wishlist have been bought
	 *
	 * @param int $wishlist_id The id of the fulfilled wishlist
	 */
	public static function customer_fulfilled_wishlist( $wishlist_id ) {
		WC()->mailer();
		$emailer = nmgr()->email( 'email_customer_fulfilled_wishlist', $wishlist_id );
		$emailer->trigger();
	}

	/**
	 * Email the chosen recipients when all the items in a customer's wishlist have been bought
	 *
	 * @param int $wishlist_id The id of the fulfilled wishlist
	 */
	public static function admin_fulfilled_wishlist( $wishlist_id ) {
		WC()->mailer();
		$wishlist_details = self::get_wishlist_details( $wishlist_id );

		$emailer = nmgr()->email( 'email_admin_fulfilled_wishlist', $wishlist_id );
		$emailer->template_args[ 'wishlist_details' ] = $wishlist_details;
		$emailer->trigger();
	}

	/**
	 * Email the customer when a new message has been sent from the checkout page during an order
	 *
	 * @param int $order_id The id of the order associated with the message
	 */
	public static function customer_new_message( $order_id ) {
		WC()->mailer();

		$order_data = new \NMGR_Order_Data( $order_id );

		foreach ( $order_data->get_wishlist_ids() as $wishlist_id ) {
			$wishlist = nmgr_get_wishlist( $wishlist_id, true );

			if ( !$wishlist ) {
				continue;
			}

			$message_object = $wishlist->get_message_in_order( $order_id );

			if ( !$message_object ) {
				continue;
			}

			$emailer = nmgr()->email( 'email_customer_new_message', $wishlist_id );
			$emailer->template_args[ 'order' ] = $order_data->get_order();
			$emailer->template_args[ 'order_customer_name' ] = self::get_order_customer_name( $order_data->get_order() );
			$emailer->template_args[ 'message_object' ] = $message_object;
			$emailer->template_args[ 'message' ] = $message_object->content;
			$emailer->trigger();
		}
	}

	/**
	 * Email the customer when his wishlist is trashed
	 *
	 * According to the plugin's default configuration, when a customer deletes his wishlist from the
	 * frontend it is sent to the trash rather than forcefully deleted. So a customer deleted wishlist
	 * is really a trashed wishlist. The admin can restore the wishlist from the backend.
	 *
	 * This email is sent only when the customer deletes his wishlist from the frontend and
	 * not when it is deleted from the backend
	 *
	 * If the trash is disabled, this function will not run
	 *
	 * @param int $wishlist_id The id of the trashed wishlist
	 */
	public static function customer_deleted_wishlist( $wishlist_id ) {
		WC()->mailer();
		if ( !is_nmgr_admin() ) {
			$emailer = nmgr()->email( 'email_customer_deleted_wishlist', $wishlist_id );
			$emailer->trigger();
		}
	}

	/**
	 * Get the details of a wishlist for sending in emails
	 *
	 * @param int $wishlist_id The wishlist id
	 * @return array Wishlist details
	 */
	public static function get_wishlist_details( $wishlist_id ) {
		$wishlist = nmgr_get_wishlist( $wishlist_id, true );

		if ( $wishlist ) {
			$details = [];
			$form = new \NMGR_Form( $wishlist_id );
			$fields = $form->get_fields( 'profile', [], true, false );
			$email_fields = array_filter( $fields, function( $el ) {
				return !empty( $el[ 'show_in_email' ] );
			} );

			foreach ( $email_fields as $key => $val ) {
				$value = '';

				if ( is_callable( [ $wishlist, "get_$key" ] ) ) {
					$value = $wishlist->{"get_$key"}();
				} else {
					$value = $wishlist->get_prop( $key );
				}

				if ( ('event_date' === $key) && $value ) {
					$value = nmgr_format_date( $value );
				}

				$details[ $val[ 'label' ] ?? $key ] = $value;
			}

			return apply_filters( 'nmgr_email_wishlist_details_fields', array_filter( $details ), $wishlist );
		}
	}

	/**
	 * Get the name of the customer who placed the order
	 * (Priority is given to the logged in user who made the order, before the billing name used for the order)
	 *
	 * @param WC_Order $order
	 * @return string Customer name
	 */
	public static function get_order_customer_name( $order ) {
		$user = $order->get_user();
		if ( $user ) {
			$username = "$user->first_name $user->last_name";
			$name = $username ? $username : $user->display_name;
		} else {
			$billing_name = trim( $order->get_formatted_billing_full_name() );
			$name = $billing_name ? $billing_name : __( 'a guest customer', 'nm-gift-registry' );
		}
		return $name;
	}

	public static function show_template_part( $file, $email ) {
		$filepath = $email->get_template_type_path( $file );
		echo wp_kses( $email->get_template_part( $filepath ), nmgr_allowed_post_tags() );
	}

	public static function show_wishlist_details( $email ) {
		if ( !empty( $email->template_args[ 'wishlist_details' ] ) ) {
			self::show_template_part( 'email-wishlist-details.php', $email );
		}
	}

	public static function show_order_details( $email ) {
		self::show_template_part( 'email-order-items-details.php', $email );
	}

	public static function show_refund_details( $email ) {
		self::show_template_part( 'email-refund-items-details.php', $email );
	}

	public static function show_message( $email ) {
		self::show_template_part( 'email-message.php', $email );
	}

}
