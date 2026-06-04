<?php

namespace NMGR\Deprecated;

class Mailer {

	public static function run() {
		add_action( 'nmgr_email_wishlist_details', array( __CLASS__, 'email_wishlist_details' ), 10, 2 );
		add_action( 'nmgr_email_checkout_message', array( __CLASS__, 'email_message' ), 10, 2 );
		add_action( 'nmgr_email_order_items_details', array( __CLASS__, 'email_order_items_details' ), 10, 3 );
		add_action( 'nmgr_email_order_items_details', array( __CLASS__, 'show_custom_order_notice' ), 20, 3 );
		add_action( 'nmgr_email_refund_items_details', array( __CLASS__, 'email_refund_items_details' ), 10, 3 );
	}

	public static function email_wishlist_details( $wishlist_details, $email ) {
		if ( !empty( $email->template_args[ 'wishlist_details' ] ) ) {
			self::show_template_part( 'email-wishlist-details.php', $email );
		}
	}

	public static function email_message( $message, $email ) {
		self::show_template_part( 'email-message.php', $email );
	}

	public static function email_order_items_details( $order, $order_item_ids, $email ) {
		self::show_template_part( 'email-order-items-details.php', $email );
	}

	public static function show_custom_order_notice( $order, $order_item_ids, $email ) {
		if ( !empty( $email->template_args[ 'order' ] ) &&
			$email->template_args[ 'order' ]->is_created_via( 'nmgr_wishlist' ) ) {
			if ( 'plain' === $email->get_email_type() ) {
				echo esc_html( nmgr_get_custom_order_notice() ) . "\n\n";
			} else {
				echo '<p><i>' . esc_html( nmgr_get_custom_order_notice() ) . '<i></p>';
			}
		}
	}

	public static function email_refund_items_details( $wishlist_item_ids_to_qtys, $wishlist, $email ) {
		self::show_template_part( 'email-refund-items-details.php', $email );
	}

	public static function show_template_part( $basename, $email ) {
		$filepath = $email->get_template_type_path( $basename );
		echo wp_kses( $email->get_template_part( $filepath ), nmgr_allowed_post_tags() );
	}

}
