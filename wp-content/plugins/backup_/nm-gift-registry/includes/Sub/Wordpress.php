<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Wordpress extends \NMGR_Wordpress {

	public static function run() {
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'set_image_attributes' ), 10, 3 );
		add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
		add_filter( 'wp_loaded', array( __CLASS__, 'add_single_multiple_products_to_cart' ) );
		add_filter( 'wp_loaded', array( __CLASS__, 'user_delete_wishlist' ) );
		add_filter( 'nmgr_show_add_to_wishlist_button', array( __CLASS__, 'hide_add_to_wishlist_button' ), 10, 3 );
		add_filter( 'nmgr_register_post_type', array( __CLASS__, 'modify_register_post_type' ), 0 );

		parent::run();
	}

	/**
	 * Set standard attributes for wishlist post thumbnail
	 */
	public static function set_image_attributes( $attr, $attachment, $size ) {
		if ( 'nmgr_medium' == $size ) {
			$wishlist = nmgr_get_wishlist( $attachment->post_parent );
			if ( $wishlist && !$attr[ 'alt' ] ) {
				$attr[ 'alt' ] = $wishlist->get_title();
			}
			$attr[ 'class' ] = $attr[ 'class' ] . ' nmgr-post-thumbnail';
		}
		return $attr;
	}

	public static function pre_get_posts( $query ) {
		if ( !is_admin() && 'nm_gift_registry' === $query->get( 'post_type' ) && is_nmgr_archive() ) {
			$excluded = array_unique( array_merge(
					get_option( 'nmgr_exclude_from_search', [] ),
					self::get_archived_wishlist_ids()
				) );

			if ( !empty( $excluded ) ) {
				$query->set( 'post__not_in', $excluded );
			}
		}
	}

	private static function get_archived_wishlist_ids() {
		global $wpdb;

		$ids = wp_cache_get( 'nmgr_archived_ids', 'nmgr' );

		if ( false === $ids ) {
			$ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_nmgr_archived' AND meta_value = '1'" );

			wp_cache_set( 'nmgr_archived_ids', $ids, 'nmgr' );
		}

		return is_array( $ids ) ? $ids : [];
	}

	/**
	 * Add single or multiple wishlist item products to the cart at once via http
	 */
	public static function add_single_multiple_products_to_cart() {
		$data = array();
		if ( isset( $_POST[ 'nmgr_add_to_cart_item_data_string' ] ) ) {
			$data = json_decode( stripslashes( $_POST[ 'nmgr_add_to_cart_item_data_string' ] ) );
		} elseif ( isset( $_POST[ 'nmgr-add-to-cart-product-id' ] ) ) {
			$data = array( $_POST );
		}

		$items_data = apply_filters( 'nmgr_add_to_cart_items_data', $data );

		if ( empty( $items_data ) ) {
			return;
		}

		nmgr()->order()->add_to_cart( $items_data );

		// If there was no error after adding to cart, optionally do a redirect.
		if ( 0 === wc_notice_count( 'error' ) ) {
			$url = apply_filters( 'woocommerce_add_to_cart_redirect', false, false );

			if ( $url ) {
				wp_safe_redirect( $url );
				exit;
			} elseif ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}
	}

	public static function user_delete_wishlist() {
		if ( is_admin() || !\NMGR_Form::verify_nonce() ||
			!isset( $_POST[ 'nmgr_separated_settings_form' ] ) ) {
			return;
		}

		$wishlist_id = $_POST[ 'nmgr_wishlist_id' ] ?? false;
		$wishlist = nmgr_get_wishlist( $wishlist_id );

		if ( isset( $_POST[ 'nmgr_delete_wishlist' ] ) && $wishlist && nmgr_user_can_manage_wishlist( $wishlist ) ) {
			$type = $wishlist->get_type();

			if ( $wishlist->delete() ) {
				wc_add_notice(
					sprintf(
						/* translators: %s: wishlist type title */
						__( 'Your %s has been deleted', 'nm-gift-registry' ),
						nmgr_get_type_title( '', '', $type )
					),
					'notice'
				);

				wp_redirect( nmgr_get_url( $type, 'home' ) );
				exit();
			}
		}
	}

	public static function hide_add_to_wishlist_button( $show, $product, $type ) {
		if ( 'gift-registry' === $type && $product && !is_nmgr_product_add_to_wishlist_allowed( $product ) ) {
			return false;
		}

		return $show;
	}

	public static function modify_register_post_type( $args ) {
		if ( 'no' !== nmgr_get_option( 'display_image_thumbnail' ) ) {
			$args[ 'supports' ][] = 'thumbnail';
		}

		if ( nmgr_get_option( 'enable_messages', 1 ) ) {
			$args[ 'supports' ][] = 'comments';
		}

		return $args;
	}

}
