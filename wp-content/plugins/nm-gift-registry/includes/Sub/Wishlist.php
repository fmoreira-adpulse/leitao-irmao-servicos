<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Wishlist extends \NMGR_Wishlist {

	protected $pro_core_data = [
		'post_password' => '',
	];
	protected $pro_meta_data = [
		'background_image_id' => 0,
		'thumbnail_id' => 0,
		'nmgr_archived' => 0,
	];

	/**
	 * Get the featured image thumbnail id for the wishlist
	 *
	 * @return int Thumbnail id
	 */
	public function get_thumbnail_id() {
		return $this->get_prop( 'thumbnail_id' );
	}

	/**
	 * Get the background image id for the wishlist
	 *
	 * @return int Background image id
	 */
	public function get_background_image_id() {
		return $this->get_prop( 'background_image_id' );
	}

	/**
	 * Set the thumbnail id for the wishlist
	 */
	public function set_thumbnail_id( $value ) {
		$this->set_prop( 'thumbnail_id', $value );
	}

	/**
	 * Set the background image id for the wishlist
	 */
	public function set_background_image_id( $value ) {
		$this->set_prop( 'background_image_id', $value );
	}

	public function set_visibility( $value ) {
		_deprecated_function( __METHOD__, '4.0.0', 'set_status()' );
		if ( in_array( $value, [ 'private', 'publish' ] ) ) {
			$this->set_status( $value );
		}
	}

	public function set_password( $value ) {
		$this->set_prop( 'post_password', $value );
	}

	/**
	 * Get the archived value
	 * @return int
	 */
	public function get_archived() {
		return absint( $this->get_prop( 'nmgr_archived' ) );
	}

	/**
	 * Set the archived value
	 * @param int $value
	 */
	public function set_archived( $value ) {
		$this->set_prop( 'nmgr_archived', absint( $value ) );
		wp_cache_delete( 'nmgr_archived_ids', 'nmgr' );
	}

	public function is_archived() {
		return ( bool ) apply_filters( 'nmgr_wishlist_is_archived', $this->get_archived(), $this );
	}

	/**
	 * Archive the wishlist
	 */
	public function archive() {
		$this->set_archived( 1 );
		$this->save();
	}

	/**
	 * Unarchive the wishlist
	 */
	public function unarchive() {
		$this->set_archived( 0 );
		$this->save();
	}

	/*
	  |--------------------------------------------------------------------------
	  | Wishlist images
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Delete all images from the wishlist
	 *
	 * Saves updated wishlist data without images data to database after deleting.
	 *
	 * @param int|array $image_id Image id(s) to delete. If none is supplied, deletes all wishlist images
	 */
	public function delete_images( $image_id = [] ) {
		$default_data = $this->get_default_data();
		$default_image_ids = [
			( int ) $default_data[ 'thumbnail_id' ],
			( int ) $default_data[ 'background_image_id' ],
		];

		$saved_image_ids = [
			'thumbnail' => ( int ) $this->get_thumbnail_id(),
			'background' => ( int ) $this->get_background_image_id()
		];

		if ( empty( $image_id ) ) {
			$image_id = $saved_image_ids;
		}

		foreach ( array_filter( ( array ) $image_id ) as $img_id ) {
			if ( !in_array( $img_id, $default_image_ids, true ) &&
				in_array( $img_id, $saved_image_ids ) &&
				apply_filters( 'nmgr_delete_attachment', true, $img_id, $this ) ) {
				wp_delete_attachment( $img_id );
			}

			if ( ( int ) $img_id === $saved_image_ids[ 'thumbnail' ] ) {
				$this->set_thumbnail_id( 0 );
			} elseif ( ( int ) $img_id === $saved_image_ids[ 'background' ] ) {
				$this->set_background_image_id( 0 );
			}
		}

		$this->save();
	}

	/*
	  |--------------------------------------------------------------------------
	  | Wishlist messages
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Adds a customer message to the wishlist.
	 *
	 * @param  array $data Comment data to add.
	 *  - $data keys required:
	 * -- message The comment message
	 * -- order_id The order id
	 *
	 * @return int Comment ID.
	 */
	public function add_message( $data ) {
		if ( !$this->get_id() ) {
			return 0;
		}

		$order = wc_get_order( $data[ 'order_id' ] );
		$user = $order->get_user();

		if ( $user ) {
			$username = "$user->first_name $user->last_name";
			$comment_author = $username ? $username : $user->display_name;
			$comment_author_email = $user->user_email;
		} elseif ( $order->get_formatted_billing_full_name() ) {
			$comment_author = $order->get_formatted_billing_full_name();
			$comment_author_email = sanitize_email( $order->get_billing_email() );
		} else {
			$comment_author = __( 'Guest', 'nm-gift-registry' );
			$comment_author_email = '';
		}

		$commentdata = array(
			'comment_post_ID' => $this->get_id(),
			'comment_author' => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url' => '',
			'comment_content' => $data[ 'message' ],
			'comment_agent' => 'NM Gift Registry',
			'comment_type' => 'nmgiftregistry_msg',
			'comment_parent' => 0,
			'comment_approved' => 1,
		);

		$comment_id = wp_insert_comment( $commentdata );
		add_comment_meta( $comment_id, 'order_id', $data[ 'order_id' ] );
		$this->add_unread_message();

		return $comment_id;
	}

	public function get_messages_args() {
		return [
			'post_id' => $this->get_id(),
			'status' => 'approve',
			'type' => 'nmgiftregistry_msg',
		];
	}

	public function get_messages_count() {
		return get_comments( array_merge( $this->get_messages_args(), [ 'count' => true ], ) );
	}

	/**
	 * Get all messages added to the wishlist by customers
	 */
	public function get_messages( $args = [] ) {
		/**
		 * get_comments returns all comments if post id is 0
		 * So only run it if we have a valid post_id as we don't want to return all comments for all wishlists
		 */
		if ( !$this->get_id() ) {
			return array();
		}

		$comment_args = [
			'number' => $args[ 'limit' ] ?? null,
			'paged' => $args[ 'page' ] ?? null,
		];

		$comments = get_comments( array_merge( $this->get_messages_args(), $comment_args ) );
		return $this->setup_messages( $comments );
	}

	/**
	 * Get the wishlist's message attached to an order
	 *
	 * Each order can only have one wishlist message attached to it so this function is
	 * expected to return a single comment object (if the order has the wishlist's message)
	 *
	 * @param int $order_id Order id
	 * @return mixed WP_Comment_Object | false
	 */
	public function get_message_in_order( $order_id ) {
		$comments = get_comments( array_merge( $this->get_messages_args(), array(
			'meta_key' => 'order_id',
			'meta_value' => ( int ) $order_id
			) ) );

		$prepared_comments = $this->setup_messages( $comments );
		return !empty( $prepared_comments ) ? $prepared_comments[ 0 ] : false;
	}

	private function setup_messages( $comments ) {
		return array_filter( array_map( function ( $comment ) {
				$order_id = get_comment_meta( $comment->comment_ID, 'order_id', true );

				$msg = ( object ) array(
						'id' => $comment->comment_ID,
						'name' => $comment->comment_author,
						'content' => $comment->comment_content,
						'email' => $comment->comment_author_email,
						'date_created' => get_comment_date( get_option( 'date_format' ), $comment->comment_ID ),
						'order_id' => $order_id,
						'items_ordered' => $this->get_items_in_order( $order_id ),
				);

				// Provide the opportunity to filter the contents returned for each message object
				return apply_filters( 'nmgr_wishlist_message', $msg, $this );
			}, $comments ) );
	}

	/**
	 * Whether the wishlist is excluded from appearing in search results
	 *
	 * @return boolean
	 */
	public function is_excluded_from_search() {
		$excluded_from_search = get_option( 'nmgr_exclude_from_search', array() );
		return in_array( $this->get_id(), $excluded_from_search );
	}

	/**
	 * @deprecated since 4.1.0
	 */
	public function exclude_from_search( $value = null ) {
		_deprecated_function( __METHOD__, '4.1.0', 'set_exclude_from_search' );
		$this->set_exclude_from_search( $value );
	}

	/**
	 * Exclude this specific wishlist from appearing in wishlist search results
	 * @param int|bool $value Value that determines whether the wishlist is excluded.
	 */
	public function set_exclude_from_search( $value = null ) {
		$val = $value ? filter_var( $value, FILTER_VALIDATE_BOOLEAN ) : null;
		$wishlists_excluded_from_search = get_option( 'nmgr_exclude_from_search', array() );
		$maybe_exclude = [ absint( $this->get_id() ) ];

		if ( !$val ) {
			$exclude = array_diff( $wishlists_excluded_from_search, $maybe_exclude );
		} else {
			$exclude = array_unique( array_merge( $wishlists_excluded_from_search, $maybe_exclude ) );
		}
		update_option( 'nmgr_exclude_from_search', $exclude );
	}

	/**
	 * Get the post visibility of the wishlist (e.g. public, password protected)
	 *
	 * @return string
	 */
	public function get_visibility() {
		$val = __( 'Public', 'nm-gift-registry' );
		if ( 'private' === $this->get_status() ) {
			$val = __( 'Private', 'nm-gift-registry' );
		} elseif ( !empty( $this->get_password() ) ) {
			$val = __( 'Password', 'nm-gift-registry' );
		}
		return $val;
	}

	/**
	 * Get the post password of the wishlist
	 *
	 * @return type
	 */
	public function get_password() {
		return $this->get_prop( 'post_password' );
	}

	/**
	 * Get the wishlist featured image thumbnail
	 * Optionally returns a placeholder if there is no thumbnail
	 *
	 * @param bool $placeholder Whether to return the placeholder image. Default true
	 * @return string Thumbnail img tag or default placeholder if no thumbnail image exists
	 */
	public function get_thumbnail( $placeholder = true ) {
		$display = nmgr_get_option( 'display_image_thumbnail' );

		if ( 'no' === $display ) {
			return;
		}

		$class = 'circle' === $display ? 'nmgr-circle' : '';

		$thumb = wp_get_attachment_image(
			$this->get_thumbnail_id(),
			'nmgr_medium',
			false,
			array( 'class' => $class )
		);

		if ( !$thumb && $placeholder ) {
			$thumb = wp_get_attachment_image(
				apply_filters( 'nmgr_featured_placeholder_image_id', '', $this ),
				'nmgr_medium',
				false,
				array( 'class' => $class )
			);

			if ( !$thumb ) {
				$placeholder_svg = nmgr_get_svg( array(
					'icon' => 'user',
					'size' => nmgr()->post_thumbnail_size() / 16, // convert px to em.
					'fill' => '#ccc',
					'class' => "nmgr-post-thumbnail {$class}",
					'style' => 'max-width:100%;max-height:100%;background-color:#f8f8f8;',
					) );

				$thumb = apply_filters( 'nmgr_featured_placeholder_svg', $placeholder_svg, $this );
			}
		}

		return apply_filters( 'nmgr_get_thumbnail', $thumb, $placeholder, $this );
	}

	/**
	 * Get the wishlist background image url
	 * Optionally return the url to a placeholder image if no background image is present
	 *
	 * @return string
	 */
	public function get_background_image_url() {
		$bg_img_id = $this->get_background_image_id();
		$id = $bg_img_id ? $bg_img_id : apply_filters( 'nmgr_background_placeholder_image_id', '', $this );
		return wp_get_attachment_url( $id );
	}

	/**
	 * Get the wishlist background image in the wishlist thumbnail size
	 * Optionally returns a placeholder image if there is no thumbnail
	 *
	 * @param bool $placeholder Whether to return the placeholder image. Default true
	 * @return string Thumbnail img tag or default placeholder if no thumbnail image exists
	 */
	public function get_background_thumbnail( $placeholder = true ) {
		if ( 'no' === nmgr_get_option( 'display_image_background' ) ) {
			return;
		}

		$thumb = wp_get_attachment_image( $this->get_background_image_id(), 'nmgr_medium' );

		if ( !$thumb && $placeholder ) {
			$thumb = wp_get_attachment_image(
				apply_filters( 'nmgr_background_placeholder_image_id', '', $this ),
				'nmgr_medium'
			);
		}

		return apply_filters( 'nmgr_get_background_thumbnail', $thumb, $placeholder, $this );
	}

	/**
	 * Get the ids of all orders that contain a particular wishlist
	 * Orders gotten are those which have is paid statuses.
	 * @return mixed Order ids
	 */
	public function get_order_ids( $args = [] ) {
		global $wpdb;

		if ( empty( $args ) ) {
			$ids = $this->cache_get( 'order_ids' );
			if ( false !== $ids ) {
				return $ids;
			}
		}

		$defaults = [
			'limit' => null,
			'offset' => 0,
			'order' => 'DESC',
			'orderby' => 'posts.ID',
			'select' => 'DISTINCT posts.ID',
			'get' => 'col',
			'statuses' => nmgr_is_paid_statuses(),
			'where' => null,
			'page' => null, // 'page' and 'offset' should not be used together.
		];

		$p_args = wp_parse_args( $args, $defaults );

		$limit = max( 0, ( int ) $p_args[ 'limit' ] );
		$orders_table = nmgr_orders_table();

		if ( $p_args[ 'page' ] ) {
			$p_args[ 'offset' ] = max( 0, (( int ) $p_args[ 'page' ] - 1) * $limit );
		}

		if ( !empty( $p_args[ 'statuses' ] ) ) {
			$status_key = false !== strpos( $orders_table, 'posts' ) ? 'post_status' : 'status';
			$p_args[ 'where' ] = $p_args[ 'where' ] . "AND posts.$status_key IN  ('wc-" . implode( "','wc-", array_map( 'esc_sql', $p_args[ 'statuses' ] ) ) . "')";
		}

		$select_sql = $p_args[ 'select' ];
		$where_sql = ($p_args[ 'where' ] ?? '');
		$limit_sql = $limit ? $wpdb->prepare( "LIMIT %d", $limit ) : '';
		$offset_sql = $limit ? 'OFFSET ' . $p_args[ 'offset' ] : '';
		$order_sql = esc_sql( "ORDER BY {$p_args[ 'orderby' ]} {$p_args[ 'order' ]}" );
		$sql = "SELECT $select_sql "
			. "FROM $orders_table AS posts "
			. "LEFT JOIN {$wpdb->prefix}wc_order_product_lookup AS opl "
			. "ON posts.ID = opl.order_id "
			. "LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as oim "
			. "ON opl.order_item_id = oim.order_item_id "
			. "WHERE 1=1 "
			. "AND oim.meta_key = 'nmgr_wishlist_id' "
			. "AND oim.meta_value = {$this->get_id()} "
			. "$where_sql "
			. "$order_sql "
			. "$limit_sql "
			. "$offset_sql";

		$result = $wpdb->{'get_' . $p_args[ 'get' ]}( $sql );

		if ( empty( $args ) ) {
			$this->cache_set( 'order_ids', $result );
		}

		return $result;
	}

	public function get_paid_order_ids() {
		_deprecated_function( __FUNCTION__, '4.11', 'NMGR_Wishlist->get_order_ids' );
		return $this->get_order_ids();
	}

	/**
	 * Get the total amount received for all items in a wishlist in all orders
	 * @return float
	 */
	public function get_amount_received_in_orders() {
		$total = 0;
		$order_ids = $this->get_order_ids();
		foreach ( $order_ids as $id ) {
			$total += $this->get_amount_received_in_order( $id );
		}
		return nmgr_round( $total );
	}

	/**
	 * Get the amount received for all items in a wishlist from an order
	 * @param int|WC_Order $order_id Order id or object
	 * @return float
	 */
	public function get_amount_received_in_order( $order_id ) {
		$total = 0;
		$order = is_a( $order_id, \WC_Order::class ) ? $order_id : wc_get_order( $order_id );

		if ( $order ) {
			foreach ( $order->get_items() as $item ) {
				if ( nmgr_get_wishlist_id_for_order_item( $item ) === $this->get_id() ) {
					$total += $item->get_total() - $order->get_total_refunded_for_item( ( int ) $item->get_id() );
				}
			}
		}
		return nmgr_round( $total );
	}

	public function get_unread_messages() {
		return ( int ) get_post_meta( $this->get_id(), 'unread_messages', true );
	}

	public function set_messages_as_read() {
		update_post_meta( $this->get_id(), 'unread_messages', 0 );
	}

	private function add_unread_message() {
		update_post_meta( $this->get_id(), 'unread_messages', 1 + $this->get_unread_messages() );
	}

}
