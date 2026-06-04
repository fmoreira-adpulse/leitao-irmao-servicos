<?php

/**
 * Sync
 */
defined( 'ABSPATH' ) || exit;

class NMGR_Wishlist extends NMGR_Data {

	/**
	 * Wishlist data stored in wp posts table
	 *
	 * @var array
	 */
	protected $core_data = array(
		'post_title' => '',
		'post_status' => 'publish',
		'post_type' => 'nm_gift_registry',
		'post_excerpt' => '',
		'post_name' => '',
		'post_date' => '',
		'post_author' => 0,
	);

	/**
	 * Wishlist meta data stored in post meta table
	 *
	 * Internal meta keys for wishlist
	 *
	 * @var array
	 */
	protected $meta_data = array(
		'_first_name' => '',
		'_last_name' => '',
		'_partner_first_name' => '',
		'_partner_last_name' => '',
		'_email' => '',
		'_event_date' => '',
		'shipping_first_name' => '',
		'shipping_last_name' => '',
		'shipping_company' => '',
		'shipping_address_1' => '',
		'shipping_address_2' => '',
		'shipping_city' => '',
		'shipping_postcode' => '',
		'shipping_country' => '',
		'shipping_state' => '',
		'_date_fulfilled' => null,
		'_nmgr_user_id' => 0,
		'_nmgr_guest' => 0,
		'_nmgr_expired' => 0,
	);
	public $items = array();
	protected $object_type = 'wishlist';
	public $meta_type = 'post';
	protected $type = null;

	/**
	 * Get the wishlist if ID is passed, otherwise the wishlist is new and empty.
	 *
	 * @param  int|object|NMGR_Wishlist $wishlist Wishlist to read.
	 */
	public function __construct( $wishlist = 0 ) {
		if ( !empty( $wishlist->ID ) ) {
			$this->set_id( absint( $wishlist->ID ) );
		}

		if ( !empty( $this->pro_core_data ) ) {
			$this->core_data = array_merge( $this->pro_core_data, $this->core_data );
		}

		if ( !empty( $this->pro_meta_data ) ) {
			$this->meta_data = array_merge( $this->pro_meta_data, $this->meta_data );
		}

		parent::__construct( $wishlist );

		if ( $this->get_id() > 0 ) {
			$this->read();
		}
	}

	/*
	  |--------------------------------------------------------------------------
	  | Getters
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Get all data for this wishlist including wishlist items
	 *
	 * @param bool $items Whether to get the wishlist items with the data. Default false
	 * @return array Wishlist Data
	 */
	public function get_data( $items = false ) {
		$data = parent::get_data();

		if ( $items ) {
			_deprecated_argument( __METHOD__, '4.4.0' );
			$items_data = array_map( function ( $obj ) {
				$d = nmgr_get_wishlist_item( $obj );
				return $d ? $d->get_data() : array();
			}, $this->get_items() );

			$data = array_merge( $data, array( 'items' => $items_data ) );
		}

		return apply_filters( 'nmgr_get_wishlist_data', $data, $this );
	}

	/**
	 * Get the title of the wishlist
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->get_prop( 'post_title' );
	}

	/**
	 * Get the post status of the wishlist (e.g. publish, draft)
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->get_prop( 'post_status' );
	}

	/**
	 * Get the first name of the wishlist owner
	 *
	 * @return string
	 */
	public function get_first_name() {
		return $this->get_prop( '_first_name' );
	}

	/**
	 * Get the last name of the wishlist owner
	 *
	 * @return string
	 */
	public function get_last_name() {
		return $this->get_prop( '_last_name' );
	}

	/**
	 * Get the first name and last name of the wishlist owner
	 *
	 * @return string
	 */
	public function get_full_name() {
		return trim( sprintf( '%1$s %2$s', $this->get_first_name(), $this->get_last_name() ) );
	}

	/**
	 * Get the first name of the wishlist owner's partner
	 *
	 * @return string
	 */
	public function get_partner_first_name() {
		return $this->get_prop( '_partner_first_name' );
	}

	/**
	 * Get the last name of the wishlist owner's partner
	 *
	 * @return string
	 */
	public function get_partner_last_name() {
		return $this->get_prop( '_partner_last_name' );
	}

	/**
	 * Get the first name and last name of the wishlist owner's partner
	 *
	 * @return string
	 */
	public function get_partner_full_name() {
		return trim( sprintf( '%1$s %2$s', $this->get_partner_first_name(), $this->get_partner_last_name() ) );
	}

	/**
	 * Get the display name for the wishlist
	 * This is the combination of the names of the wishlist owner and wishlist owner's partner if available
	 *
	 * @return string
	 */
	public function get_display_name() {
		$display_name = '';
		if ( $this->get_full_name() && $this->get_partner_full_name() ) {
			$display_name = "{$this->get_full_name()} &amp; {$this->get_partner_full_name()}";
		} elseif ( $this->get_full_name() ) {
			$display_name = $this->get_full_name();
		}
		return $display_name;
	}

	/**
	 * Get the registered email for the wishlist
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->get_prop( '_email' );
	}

	/**
	 * Get the date for the wishlist event
	 *
	 * @return string
	 */
	public function get_event_date() {
		return $this->get_prop( '_event_date' );
	}

	/**
	 * Get the wishlist description
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->get_prop( 'post_excerpt' );
	}

	/**
	 * Get all shipping fields
	 *
	 * @return array
	 */
	public function get_shipping() {
		$shipping_keys = array();
		$shipping = array();

		foreach ( array_keys( $this->get_meta_data() ) as $key ) {
			if ( false !== strpos( $key, 'shipping_' ) ) {
				$shipping_keys[] = $key;
			}
		}

		foreach ( $shipping_keys as $key ) {
			$u_key = str_replace( 'shipping_', '', $key );
			if ( is_callable( array( $this, "get_$key" ) ) ) {
				$shipping[ $u_key ] = $this->{"get_$key"}();
			} else {
				$shipping[ $u_key ] = $this->get_prop( $key );
			}
		}

		return $shipping;
	}

	/**
	 * Get shipping first name.
	 *
	 * @return string
	 */
	public function get_shipping_first_name() {
		return $this->get_prop( 'shipping_first_name' );
	}

	/**
	 * Get shipping_last_name.
	 *
	 * @return string
	 */
	public function get_shipping_last_name() {
		return $this->get_prop( 'shipping_last_name' );
	}

	/**
	 * Get shipping company.
	 *
	 * @return string
	 */
	public function get_shipping_company() {
		return $this->get_prop( 'shipping_company' );
	}

	/**
	 * Get shipping address line 1
	 *
	 * @return string
	 */
	public function get_shipping_address() {
		return $this->get_prop( 'shipping_address_1' );
	}

	/**
	 * Get shipping address line 1.
	 *
	 * @return string
	 */
	public function get_shipping_address_1() {
		return $this->get_prop( 'shipping_address_1' );
	}

	/**
	 * Get shipping address line 2.
	 *
	 * @return string
	 */
	public function get_shipping_address_2() {
		return $this->get_prop( 'shipping_address_2' );
	}

	/**
	 * Get shipping city.
	 *
	 * @return string
	 */
	public function get_shipping_city() {
		return $this->get_prop( 'shipping_city' );
	}

	/**
	 * Get shipping state.
	 *
	 * @return string
	 */
	public function get_shipping_state() {
		return $this->get_prop( 'shipping_state' );
	}

	/**
	 * Get shipping postcode.
	 *
	 * @return string
	 */
	public function get_shipping_postcode() {
		return $this->get_prop( 'shipping_postcode' );
	}

	/**
	 * Get shipping country.
	 *
	 * @return string
	 */
	public function get_shipping_country() {
		return $this->get_prop( 'shipping_country' );
	}

	/**
	 * Get the date the wishlist was fulfilled
	 *
	 * This is the date all items in the wishlist were marked as purchased
	 *
	 * @return DateTime object
	 */
	public function get_date_fulfilled() {
		return $this->get_prop( '_date_fulfilled' );
	}

	/**
	 * Get the total price of all items in the wishlist
	 *
	 * @param boolean $currency_symbol Whether to prefix the returned amount with the currency symbol
	 * @return string|int|float
	 */
	public function get_total( $currency_symbol = false ) {
		global $wpdb;

		$total = $this->cache_get( 'total' );

		if ( false === $total ) {
			$total = nmgr_round( $wpdb->get_var( "
					SELECT SUM(pl.max_price * it.quantity) AS total_cost FROM {$wpdb->prefix}nmgr_wishlist_items AS it
	INNER JOIN {$wpdb->prefix}wc_product_meta_lookup AS pl ON (pl.product_id = it.product_or_variation_id)
		WHERE 1=1 AND it.wishlist_id = {$this->get_id()}
					" ) );
			$this->cache_set( 'total', $total );
		}

		return $currency_symbol ? wc_price( $total, array( 'currency' => get_woocommerce_currency() ) ) : $total;
	}

	/**
	 * Get the amount purchased for all items from orders
	 * @return float|int
	 */
	public function get_purchased_amount() {
		global $wpdb;

		$amt = $this->cache_get( 'purchased_amount' );

		if ( false === $amt ) {
			$orders_table = nmgr_orders_table();
			$status_key = false !== strpos( $orders_table, 'posts' ) ? 'post_status' : 'status';

			$total = $wpdb->get_var(
				"SELECT SUM(oim.meta_value)
			FROM {$wpdb->prefix}woocommerce_order_itemmeta AS oim
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim2 ON oim.order_item_id = oim2.order_item_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON oim.order_item_id = oi.order_item_id
			INNER JOIN $orders_table AS po ON oi.order_id = po.ID
			WHERE oim.meta_key = '_line_total'
				AND oim2.meta_key = 'nmgr_wishlist_id' AND oim2.meta_value = {$this->get_id()}
				AND po.$status_key IN ('wc-" . implode( "','wc-", array_map( 'esc_sql', wc_get_is_paid_statuses() ) ) . "')
			"
			);

			/**
			 * Round to fix a bug that makes some results return unrounded when
			 * this function is called via select2 ajax
			 */
			$amt = nmgr_round( $total );
			$this->cache_set( 'purchased_amount', $amt );
		}

		return $amt;
	}

	/**
	 * Get the amount left to be purchased for all items from orders
	 * @return int|float
	 */
	public function get_unpurchased_amount() {
		return max( nmgr_round( $this->get_total() ) - nmgr_round( $this->get_purchased_amount() ), nmgr_round( 0 ) );
	}

	/**
	 * Get the total amount purchased from the wishlist
	 * @return int|float
	 */
	public function get_total_purchased_amount() {
		return ( float ) apply_filters( 'nmgr_wishlist_total_purchased_amount', $this->get_purchased_amount(), $this );
	}

	/**
	 * Get the total amount left to be purchased for the wishlist
	 * @return int|float
	 */
	public function get_total_unpurchased_amount() {
		return max( nmgr_round( $this->get_total() ) - nmgr_round( $this->get_total_purchased_amount() ), nmgr_round( 0 ) );
	}

	/**
	 * Get the permalink for the wishlist
	 *
	 * @return string
	 */
	public function get_permalink() {
		return apply_filters( 'nmgr_wishlist_permalink', get_permalink( $this->get_id() ), $this );
	}

	/**
	 * Get the user id of the user associated with the wishlist
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->get_prop( '_nmgr_user_id' );
	}

	/**
	 * Get the user associated with the wishlist
	 *
	 * @return WP_User|false
	 */
	public function get_user() {
		return is_numeric( $this->get_user_id() ) ? get_user_by( 'id', $this->get_user_id() ) : false;
	}

	/**
	 * Get the customer associated with the wishlist
	 *
	 * This should be the same as the user associated with the wishlist
	 * but simply retrieved as a WC_Customer object
	 */
	public function get_customer() {
		return new \WC_Customer( $this->get_user_id() );
	}

	/**
	 * Get the slug of the wishlist
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->get_prop( 'post_name' );
	}

	/**
	 * Get the date the wishlist was created
	 *
	 * @return string
	 */
	public function get_date_created() {
		return $this->get_prop( 'post_date' );
	}

	/*
	  |--------------------------------------------------------------------------
	  | Setters
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Set the title of the wishlist
	 */
	public function set_title( $value ) {
		$this->set_prop( 'post_title', $value );
	}

	/**
	 * Set the post status of the wishlist
	 */
	public function set_status( $value ) {
		$this->set_prop( 'post_status', $value );
	}

	/**
	 * Set the first name of the wishlist owner
	 */
	public function set_first_name( $value ) {
		$this->set_prop( '_first_name', $value );
	}

	/**
	 * Set the last name of the wishlist owner
	 */
	public function set_last_name( $value ) {
		$this->set_prop( '_last_name', $value );
	}

	/**
	 * Set the first name of the wishlist owner's partner
	 */
	public function set_partner_first_name( $value ) {
		$this->set_prop( '_partner_first_name', $value );
	}

	/**
	 * Set the last name of the wishlist owner's partner
	 */
	public function set_partner_last_name( $value ) {
		$this->set_prop( '_partner_last_name', $value );
	}

	/**
	 * Set the registered email for the wishlist
	 */
	public function set_email( $value ) {
		$this->set_prop( '_email', $value );
	}

	/**
	 * Set the date for the wishlist event
	 */
	public function set_event_date( $value ) {
		$this->set_prop( '_event_date', $value );
	}

	/**
	 * Set the wishlist description
	 */
	public function set_description( $value ) {
		$this->set_prop( 'post_excerpt', $value );
	}

	/**
	 * Set shipping first name.
	 *
	 * @param string $value Shipping first name.
	 */
	public function set_shipping_first_name( $value ) {
		$this->set_prop( 'shipping_first_name', $value );
	}

	/**
	 * Set shipping last name.
	 *
	 * @param string $value Shipping last name.
	 */
	public function set_shipping_last_name( $value ) {
		$this->set_prop( 'shipping_last_name', $value );
	}

	/**
	 * Set shipping company.
	 *
	 * @param string $value Shipping company.
	 */
	public function set_shipping_company( $value ) {
		$this->set_prop( 'shipping_company', $value );
	}

	/**
	 * Set shipping address line 1.
	 *
	 * @param string $value Shipping address line 1.
	 */
	public function set_shipping_address_1( $value ) {
		$this->set_prop( 'shipping_address_1', $value );
	}

	/**
	 * Set shipping address line 2.
	 *
	 * @param string $value Shipping address line 2.
	 */
	public function set_shipping_address_2( $value ) {
		$this->set_prop( 'shipping_address_2', $value );
	}

	/**
	 * Set shipping city.
	 *
	 * @param string $value Shipping city.
	 */
	public function set_shipping_city( $value ) {
		$this->set_prop( 'shipping_city', $value );
	}

	/**
	 * Set shipping state.
	 *
	 * @param string $value Shipping state.
	 */
	public function set_shipping_state( $value ) {
		$this->set_prop( 'shipping_state', $value );
	}

	/**
	 * Set shipping postcode.
	 *
	 * @param string $value Shipping postcode.
	 */
	public function set_shipping_postcode( $value ) {
		$this->set_prop( 'shipping_postcode', $value );
	}

	/**
	 * Set shipping country.
	 *
	 * @param string $value Shipping country.
	 */
	public function set_shipping_country( $value ) {
		$this->set_prop( 'shipping_country', $value );
	}

	/**
	 * Set user id.
	 *
	 * @param int $value User ID.
	 */
	public function set_user_id( $value ) {
		$this->set_prop( 'post_author', (is_numeric( $value ) ? ( int ) $value : 0 ) );
		$this->set_prop( '_nmgr_user_id', $value );
	}

	/**
	 * Set the date the wishlist was fulfilled
	 *
	 * This is the date all items in the wishlist were marked as purchased
	 *
	 * @param string|integer|null $date UTC timestamp
	 */
	public function set_date_fulfilled( $date = null ) {
		$this->set_prop( '_date_fulfilled', $date );
	}

	/**
	 * Set the wishlist type
	 * @param string $type Taxonomy term slug
	 */
	public function set_type( $type ) {
		$this->type = $type;
	}

	/*
	  |--------------------------------------------------------------------------
	  | Wishlist Items
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Remove all items  from this wishlist
	 *
	 * @return void
	 */
	public function delete_items() {
		global $wpdb;

		$this->clear_cache();

		$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}nmgr_wishlist_itemmeta itemmeta INNER JOIN {$wpdb->prefix}nmgr_wishlist_items items WHERE itemmeta.wishlist_item_id = items.wishlist_item_id and items.wishlist_id = %d", $this->get_id() ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}nmgr_wishlist_items WHERE wishlist_id = %d", $this->get_id() ) );
	}

	/**
	 * Get all items in this wishlist
	 *
	 * @todo Deprecate this function as it is unused.
	 * @return NMGR_Wishlist_Item[]|\NMGR\Sub\Wishlist_Item[]
	 */
	public function get_items() {
		if ( !$this->items ) {
			$this->items = apply_filters( 'nmgr_items', $this->read_items(), $this );
		}
		return $this->items;
	}

	/**
	 * Get a single wishlist item from the wishlist
	 *
	 * @param  int  $id wishlist_item_id
	 * @return NMGR_Wishlist_Item|\NMGR\Sub\Wishlist_Item|false
	 */
	public function get_item( $id ) {
		if ( isset( $this->items[ $id ] ) ) {
			return $this->items[ $id ];
		}

		$args = [
			'where' => 'AND wishlist_item_id = ' . ( int ) $id,
		];

		$val = $this->read_items( $args );
		return !empty( $val ) ? reset( $val ) : false;
	}

	public function get_item_by_unique_id( $unique_id ) {
		return $this->get_item_by_column( 'unique_id', $unique_id );
	}

	public function get_item_ids() {
		_deprecated_function( __METHOD__, '4.4.0' );

		global $wpdb;

		$col = $wpdb->get_col( $wpdb->prepare(
				"SELECT wishlist_item_id FROM {$wpdb->prefix}nmgr_wishlist_items WHERE wishlist_id = %s",
				$this->get_id()
			) );

		$ids = array_map( 'intval', $col );

		return $ids;
	}

	/**
	 * Bulk updates wishlist items from the wishlist items table
	 * (used for updating item properties such as quantity, purchased_quantity and favourite)
	 *
	 * @deprecated since version 4.1.0
	 * @param array $posted_data Posted data containing wishlist items properties to save.
	 */
	public function update_items( $posted_data ) {
		_deprecated_function( __METHOD__, '4.1.0' );
		if ( isset( $posted_data[ 'wishlist_item_id' ] ) ) {

			foreach ( $posted_data[ 'wishlist_item_id' ] as $item_id ) {
				$item = $this->get_item( $item_id );

				if ( !$item ) {
					continue;
				}

				// This array holds the props we are updating for each wishlist item
				$data_keys = array(
					'wishlist_item_qty' => $item->get_quantity(),
				);

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = isset( $posted_data[ $key ][ $item_id ] ) ? wc_check_invalid_utf8( wp_unslash( $posted_data[ $key ][ $item_id ] ) ) : $default;
				}

				if ( '0' === $item_data[ 'wishlist_item_qty' ] ) {
					$item->delete();
					continue;
				}

				$props = array(
					'quantity' => $item_data[ 'wishlist_item_qty' ],
				);

				$item->set_props( $props );
				$item->save();
			}
		}
	}

	/**
	 * Remove an item from the wishlist.
	 *
	 * @param int $item_id Item ID to delete.
	 * @return false|void
	 */
	public function delete_item( $item_id ) {
		$item = $this->get_item( $item_id );
		$item->delete();
	}

	/**
	 * Gets the count of items in this wishlist
	 *
	 * @return int
	 */
	public function get_item_count() {
		_deprecated_function( __METHOD__, '4.2.0', __CLASS__ . '::get_item_quantity_count' );
		return $this->get_items_quantity_count();
	}

	/**
	 * Get the total quantities of all items in the wishlist
	 * @return int
	 */
	public function get_items_quantity_count() {
		global $wpdb;

		$val = $this->cache_get( 'items_quantity_count' );

		if ( false === $val ) {
			$val = ( int ) $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM(quantity)
			FROM {$wpdb->prefix}nmgr_wishlist_items
			WHERE wishlist_id = %d
			",
						$this->get_id()
					) );

			$this->cache_set( 'items_quantity_count', $val );
		}

		return ( int ) $val;
	}

	public function get_item_purchased_count() {
		_deprecated_function( __METHOD__, '4.2.0', __CLASS__ . '::get_items_purchased_quantity_count' );
		return $this->get_items_purchased_quantity_count();
	}

	/**
	 * Gets the count of purchased wishlist items
	 * @return int
	 */
	public function get_items_purchased_quantity_count() {
		global $wpdb;

		$val = $this->cache_get( 'items_purchased_quantity_count' );

		if ( false === $val ) {
			$val = ( int ) $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM(purchased_quantity)
			FROM {$wpdb->prefix}nmgr_wishlist_items
			WHERE wishlist_id = %d
				",
						$this->get_id()
					) );

			$this->cache_set( 'items_purchased_quantity_count', $val );
		}

		return apply_filters( 'nmgr_wishlist_item_purchased_count', $val, $this );
	}

	/**
	 * Add an item (product) to this wishlist and save the item in the database
	 *
	 * @param  int|WC_Product $product_obj Product id or object.
	 * @param  int $qty Quantity to add.
	 * @param int $favourite Whether the product is marked as favourite in the wishlist. Values are 1 or 0.
	 * @param array $variation Product variations if the product is a variation.
	 * @param array $item_data Extra data associated with the item to be added.
	 *
	 * @return int
	 */
	public function add_item( $product_obj, $qty = 1, $favourite = null, $variation = [], $item_data = [] ) {
		$product = is_a( $product_obj, \WC_Product::class ) ? $product_obj : wc_get_product( $product_obj );

		if ( !$product || !$qty ) {
			return 0;
		}

		$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->is_type( 'variation' ) ? $product->get_id() : 0;

		// Generate a unique id to identify item in wishlist based on product ID, variation ID, and variation data
		$unique_id = $this->generate_unique_id( $product_id, $variation_id, $variation, $item_data );

		$args = array(
			'wishlist_id' => $this->get_id(),
			'product_id' => $product_id,
			'product_or_variation_id' => $variation_id ? $variation_id : $product_id,
			'variation_id' => $variation_id,
			'variation' => $variation,
			'quantity' => $qty,
			'favourite' => $favourite,
			'unique_id' => $unique_id,
		);

		$item = nmgr()->wishlist_item();

		$item_with_unique_id = $this->get_item_by_unique_id( $unique_id );
		if ( $item_with_unique_id ) {
			$item = $item_with_unique_id;

			// if the wishlist already has the item, we can only update the quantity
			$args[ 'quantity' ] = $item->get_quantity() + $qty;
		}

		$item->set_props( $args );
		$item->save();

		return $item->get_id();
	}

	/**
	 * Checks if an item is already in the wishlist
	 *
	 * @param string $unique_id The unique id of the item in the wishlist
	 * @deprecated since version 4.4.0
	 * @return boolean
	 */
	public function has_item( $unique_id ) {
		_deprecated_function( __METHOD__, '4.4.0' );
		return ( bool ) $this->get_item_by_unique_id( $unique_id );
	}

	/**
	 * Checks if the wishlist has items
	 *
	 * @return boolean
	 */
	public function has_items() {
		return ( bool ) $this->get_items_count();
	}

	/**
	 * Generate a unique id for the wishlist item being added
	 *
	 * @param int $product_id ID of the product the key is being generated for
	 * @param int $variation_id Variation id of the product the key is being generated for
	 * @param array $variation Variation data for the wishlist item
	 * @param array $item_data Extra data passed to denote the uniqueness of the item in the
	 * wishlist
	 */
	public function generate_unique_id( $product_id, $variation_id = 0, $variation = array(), $item_data = array() ) {
		$id_parts = array( $product_id );

		if ( $variation_id && 0 !== $variation_id ) {
			$id_parts[] = $variation_id;
		}

		if ( is_array( $variation ) && !empty( $variation ) ) {
			$variation_key = '';
			foreach ( $variation as $key => $value ) {
				$variation_key .= trim( $key ) . trim( $value );
			}
			$id_parts[] = $variation_key;
		}

		if ( is_array( $item_data ) && !empty( $item_data ) ) {
			$item_data_key = '';
			foreach ( $item_data as $key => $value ) {
				if ( is_array( $value ) || is_object( $value ) ) {
					$value = http_build_query( $value );
				}
				$item_data_key .= trim( $key ) . trim( $value );
			}
			$id_parts[] = $item_data_key;
		}

		return md5( implode( '_', $id_parts ) );
	}

	/**
	 * Get all items in an order for this wishlist
	 *
	 * @param type $order_id Order id
	 */
	public function get_items_in_order( $order_id ) {
		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} elseif ( $order_id instanceof WC_Order ) {
			$order = $order_id;
		}

		if ( !$order ) {
			return;
		}

		$items = $order->get_items();
		$items_in_order = array();

		foreach ( $items as $item_id => $item ) {
			$order_item_wishlist_id = nmgr_get_wishlist_id_for_order_item( $item );
			// We want to make sure the item has not been completely refunded
			if ( $order_item_wishlist_id &&
				( int ) $item->get_quantity() > absint( $order->get_qty_refunded_for_item( $item->get_id() ) ) ) {
				if ( $order_item_wishlist_id === $this->get_id() ) {
					$items_in_order[ $item_id ] = array(
						'name' => $item->get_name(),
						'quantity' => $item->get_quantity(),
						'variation_id' => $item->get_variation_id(),
						'total' => $item->get_total(),
					);
				}
			}
		}

		return apply_filters( 'nmgr_wishlist_get_items_in_order', $items_in_order, $order, $this );
	}

	/**
	 * Get the wishlist item representing a product, if the product is in the wishlist
	 *
	 * @param int|WC_Product $product_id The product id or object
	 * @return NMGR_Wishlist_Item|\NMGR\Sub\Wishlist_Item|false
	 */
	public function get_item_by_product( $product_id ) {
		$id = is_a( $product_id, 'WC_Product' ) ? $product_id->get_id() : $product_id;
		if ( $id ) {
			return $this->get_item_by_column( 'product_id', $id );
		}
	}

	public function get_item_by_column( $column_key, $column_value ) {
		global $wpdb;

		$args = [
			'where' => $wpdb->prepare( "AND items.$column_key = %s", $column_value ),
			'limit' => 1,
		];

		$val = $this->read_items( $args );
		return !empty( $val ) ? reset( $val ) : false;
	}

	/*
	  |--------------------------------------------------------------------------
	  | Conditionals
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Whether the wishlist has a shipping address
	 *
	 * The wishlist has a shipping address if all the required fields are filled
	 * or if the country and address 1 fields are filled.
	 *
	 * @return boolean
	 */
	public function has_shipping_address() {
		$address = $this->get_shipping();

		if ( !isset( $address[ 'country' ] ) || !$address[ 'country' ] ) {
			return false;
		}

		$fields = is_a( wc()->countries, 'WC_Countries' ) ? wc()->countries->get_address_fields( $address[ 'country' ], 'shipping_' ) : array();
		$required = array_keys( array_filter( $fields, function ( $field ) {
				return isset( $field[ 'required' ] ) && $field[ 'required' ];
			} ) );

		foreach ( $required as $field ) {
			$unprefixed = str_replace( 'shipping_', '', $field );
			if ( !isset( $address[ $unprefixed ] ) ||
				(isset( $address[ $unprefixed ] ) && !$address[ $unprefixed ] ) ) {
				return false;
			}
		}

		return (isset( $address[ 'address_1' ] ) && $address[ 'address_1' ]) ||
			(isset( $address[ 'address_2' ] ) && $address[ 'address_2' ]);
	}

	/**
	 * Whether the wishlist needs its shipping address to be filled
	 *
	 * The wishlist needs a shipping address if the shipping address is required in the
	 * plugin setting and it's shipping address is not completely filled.
	 *
	 * @return boolean
	 */
	public function needs_shipping_address() {
		$val = nmgr_get_type_option( $this->get_type(), 'shipping_address_required' ) &&
			!$this->has_shipping_address();
		return apply_filters( 'nmgr_wishlist_needs_shipping_address', $val, $this );
	}

	/**
	 * Whether the wishlist ships to the customer's account shipping address
	 *
	 * @return boolean
	 */
	public function is_shipping_to_account_address() {
		_deprecated_function( __METHOD__, '4.0.0' );

		if ( $this->is_guest() ) {
			return false;
		}

		$bool = ( bool ) $this->get_prop( 'ship_to_account_address' );

		if ( $bool ) {
			$customer = $this->get_customer();
			if ( $customer &&
				((method_exists( $customer, 'has_shipping_address' ) && $customer->has_shipping_address()) ||
				$customer->get_shipping_address())
			) {
				foreach ( $customer->get_shipping() as $k => $v ) {
					update_post_meta( $this->get_id(), '_shipping_' . $k, $v );
				}
			}
			delete_post_meta( $this->get_id(), '_ship_to_account_address' );
		}

		return false;
	}

	/**
	 * Checks if the wishlist has a product
	 *
	 * @param int|array $product_id Product id(s)
	 * @return boolean true|false
	 */
	public function has_product( $product_id ) {
		global $wpdb;

		if ( is_a( $product_id, WC_Product::class ) ) {
			_deprecated_argument( __METHOD__, '4.4.0', 'Use product id instead of product object' );

			if ( $product_id->is_type( 'grouped' ) && $product_id->has_child() ) {
				$product_item_ids = $product_id->get_children();
			} else {
				$product_item_ids = ( array ) $product_id->get_id();
			}
		} else {
			$product_item_ids = ( array ) $product_id;
		}

		$val = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$wpdb->prefix}nmgr_wishlist_items AS items
			WHERE wishlist_id = %d
			AND product_id IN ('" . implode( "','", array_map( 'intval', $product_item_ids ) ) . "')
			LIMIT 1
				",
				$this->get_id()
			) );

		return ( bool ) $val;
	}

	/**
	 * Check if all the items in the wishlist have been fully purchased
	 *
	 * @return boolean
	 */
	protected function has_items_fulfilled() {
		global $wpdb;

		$not_fulfilled = $this->cache_get( 'has_items_fulfilled' );

		if ( false === $not_fulfilled ) {
			$not_fulfilled = $wpdb->get_var( "
			SELECT IF (
				(SELECT wishlist_item_id FROM {$wpdb->prefix}nmgr_wishlist_items
				WHERE wishlist_id = {$this->get_id()}
				LIMIT 1),
				(SELECT wishlist_item_id FROM {$wpdb->prefix}nmgr_wishlist_items
				WHERE wishlist_id = {$this->get_id()}
				AND purchased_quantity < quantity
				LIMIT 1),
				'no_items'
			);
		" );

			$this->cache_set( 'has_items_fulfilled', $not_fulfilled );
		}

		return 'no_items' === $not_fulfilled ? false : ( bool ) !$not_fulfilled;
	}

	public function has_quantity_mismatch() {
		global $wpdb;

		$val = $this->cache_get( 'has_quantity_mismatch' );

		if ( false === $val ) {
			$val = $wpdb->get_var( "
			SELECT COUNT(*)
			FROM {$wpdb->prefix}nmgr_wishlist_items AS items
			WHERE wishlist_id = {$this->get_id()}
			AND quantity < purchased_quantity
			LIMIT 1
				" );

			$this->cache_set( 'has_quantity_mismatch', $val );
		}

		return ( bool ) $val;
	}

	public function is_fulfilled() {
		return $this->has_items_fulfilled();
	}

	/**
	 * Check whether the wishlist is active.
	 * An active wishlist is a wishlist that has any of the registered post statuses and is not trashed.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->get_id() && in_array( $this->get_status(), nmgr_get_post_statuses() );
	}

	/**
	 * Check if the wishlist belongs to a guest
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	public function is_guest() {
		return $this->get_user_id() === get_post_meta( $this->get_id(), '_nmgr_guest', true );
	}

	/**
	 * Check if the wishlist has the term
	 * @param string $term_slug Default values are gift-registry, wishlist
	 * @return boolean
	 */
	public function is_type( $term_slug ) {
		return $term_slug === $this->get_type();
	}

	/**
	 * Check if the wishlist is expired.
	 * This happens if the event date is set and it is passed the current date
	 *
	 * We get this value directly from php using the event date rather than from the _nmgr_expired
	 * meta_key which is set in the database during a cron job and may not be available at the
	 * time of calling this function
	 *
	 * @return boolean
	 */
	public function is_expired() {
		return $this->get_event_date() && $this->get_expiry_days() < 0;
	}

	public function get_type() {
		return $this->type;
	}

	public static function get_type_from_db( $wishlist_id ) {
		$terms = wp_get_object_terms( $wishlist_id, [ 'nm_gift_registry_type' ], [
			'update_term_meta_cache' => false,
			'fields' => 'slugs',
			'number' => 1,
			] );

		if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
			return reset( $terms );
		}
	}

	/**
	 * Get the days of expiry related to the event date
	 * @return boolean|int False if wishlist has no event date. Positive number if wishlist expiry
	 * is in the future, negative number if wishlist has expired, Zero if wishlist expiry is today.
	 */
	public function get_expiry_days() {
		if ( $this->get_event_date() ) {
			$event_date = nmgr_get_datetime( $this->get_event_date() );
			if ( $event_date ) {
				$diff = date_diff( new DateTime( current_time( 'Y-m-d' ) ), new DateTime( $event_date->format( 'Y-m-d' ) ) );
				return ( int ) $diff->format( "%R%a" );
			}
		}
		return false;
	}

	public function get_expiry() {
		return absint( $this->get_prop( '_nmgr_expired' ) );
	}

	public function set_expiry( $value ) {
		$this->set_prop( '_nmgr_expired', absint( $value ) );
	}

	public function clear_items_cache() {
		_deprecated_function( __METHOD__, '4.5.1' );
	}

	/**
	 * Get the number of items in the wishlist
	 * @global type $wpdb
	 * @return type
	 */
	public function get_items_count() {
		global $wpdb;

		$count = $this->cache_get( 'items_count' );

		if ( false === $count ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}nmgr_wishlist_items WHERE wishlist_id = %d", $this->get_id() ) );

			$this->cache_set( 'items_count', $count );
		}

		return ( int ) $count;
	}

	/**
	 * @return NMGR_Wishlist_Item[]|\NMGR\Sub\Wishlist_Item[]
	 */
	public function read_items( $args = [] ) {
		if ( !$this->get_id() ) {
			return [];
		}

		$cache_key = md5( 'read_items' . implode( ',', $args ) );
		$items_data = $this->cache_get( $cache_key );
		if ( false === $items_data ) {
			$args[ 'where' ] = "AND items.wishlist_id = {$this->get_id()} " . ($args[ 'where' ] ?? '');
			$items_data = nmgr()->wishlist_item()->get_from_db( $args );
			$this->cache_set( $cache_key, $items_data );
		}

		$item_ids_to_class_objs = [];

		foreach ( $items_data as $item_data ) {
			$item = nmgr()->wishlist_item();
			$item->set_id( $item_data->wishlist_item_id );
			foreach ( array_keys( $item->get_data() ) as $key ) {
				if ( property_exists( $item_data, $key ) ) {
					$item->set_prop( $key, maybe_unserialize( $item_data->$key ) );
				}
			}
			$item->set_object_read();

			$item_ids_to_class_objs[ $item->get_id() ] = $item;
		}

		return $item_ids_to_class_objs;
	}

	public function add_items_cache_key( $cache_key ) {
		_deprecated_function( __METHOD__, '4.5.1' );
	}

	public function delete_cache() {
		_deprecated_function( __METHOD__, '4.4.0' );
		wp_cache_delete( 'nmgr_wishlist_' . $this->get_id(), 'nmgr_wishlist' );
	}

	/*
	  |--------------------------------------------------------------------------
	  | CRUD
	  |--------------------------------------------------------------------------
	 */

	public function create() {
		$id = wp_insert_post( array_intersect_key( $this->get_data(), $this->get_core_data() ) );

		if ( $id && !is_wp_error( $id ) ) {
			$this->set_id( $id );
			$this->save_type();
			$this->update_meta_data( true );
			$this->apply_changes();

			do_action( 'nmgr_created_wishlist', $id );
		}
	}

	public function read() {
		$this->set_defaults();
		$post = get_post( $this->get_id() );

		if ( !$this->get_id() || !$post || 'nm_gift_registry' !== $post->post_type ) {
			throw new Exception( sprintf(
						/* translators: %s: wishlist type title */
						nmgr()->is_pro ? __( 'Invalid %s.', 'nm-gift-registry' ) : __( 'Invalid %s.', 'nm-gift-registry-lite' ),
						nmgr_get_type_title()
					) );
		}

		foreach ( array_keys( $this->get_data() ) as $key ) {
			if ( property_exists( $post, $key ) ) {
				$this->set_prop( $key, $post->$key );
			}
		}

		$type = $this->get_type_from_db( $this->get_id() );
		if ( !empty( $type ) ) {
			$this->set_type( $type );
		}

		$this->read_meta_data();

		do_action_deprecated( 'nmgr_read_wishlist', [ $this ], '4.4.0' );

		$this->set_object_read( true );
	}

	public function update() {
		$core_data = array_intersect_key( $this->get_changes(), $this->get_core_data() );
		$this->clear_cache();

		if ( !empty( $core_data ) ) {
			/**
			 * Use $wpdb->update to update directly if doing the save_post action (such as in admin screen)
			 * to prevent infinite loops
			 */
			if ( doing_action( 'save_post_nm_gift_registry' ) ) {
				$GLOBALS[ 'wpdb' ]->update( $GLOBALS[ 'wpdb' ]->posts, $core_data, array( 'ID' => $this->get_id() ) );
				clean_post_cache( $this->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $this->get_id() ), $core_data ) );
			}
		}

		$this->save_type();
		$this->update_meta_data();
		$this->apply_changes();

		do_action( 'nmgr_updated_wishlist', $this->get_id() );
	}

	public function delete( $force_delete = false ) {
		$id = $this->get_id();

		if ( !$id ) {
			return;
		}

		$this->clear_cache();

		if ( $force_delete ) {
			wp_delete_post( $id );
			$this->set_id( 0 );
		} else {
			wp_trash_post( $id );
			$this->set_status( 'trash' );
		}

		$this->set_id( 0 );
		return true;
	}

	public function save_type() {
		if ( $this->get_type() ) {
			wp_set_post_terms( $this->get_id(),
				nmgr_get_term_id_by_slug( $this->get_type() ), 'nm_gift_registry_type' );
		}
	}

	public function update_meta_data( $force = false ) {
		$props_to_meta_keys = $this->get_props_to_update( $force );

		if ( !empty( $props_to_meta_keys ) ) {
			foreach ( $props_to_meta_keys as $prop => $meta_key ) {
				if ( is_callable( array( $this, "get_$prop" ) ) ) {
					$new_value = $this->{"get_$prop"}();
				} else {
					$new_value = $this->get_prop( $prop );
				}

				$new_value = is_string( $new_value ) ? wp_slash( $new_value ) : $new_value;
				update_metadata( $this->meta_type, $this->get_id(), $meta_key, $new_value );
			}
		}
	}

	public function read_meta_data() {
		$data = array();
		$props_to_meta_keys = $this->get_meta_keys( $this->get_meta_data() );

		foreach ( $props_to_meta_keys as $prop => $meta_key ) {
			if ( metadata_exists( 'post', $this->get_id(), $meta_key ) ) {
				$data[ $prop ] = get_post_meta( $this->get_id(), $meta_key, true );
			} else {
				$data[ $prop ] = $this->get_default_data()[ $prop ] ?? '';
			}
		}

		$this->set_props( $data );
	}

	public function cache_get( $key ) {
		$data = wp_cache_get( $this->get_id(), 'nmgr_wishlist' );
		return (false !== $data && isset( $data[ $key ] )) ? $data[ $key ] : false;
	}

	public function cache_set( $key, $value ) {
		$data = wp_cache_get( $this->get_id(), 'nmgr_wishlist' );
		if ( false === $data ) {
			$data = [];
		}

		$data[ $key ] = $value;
		wp_cache_set( $this->get_id(), $data, 'nmgr_wishlist' );
	}

	public function clear_cache() {
		wp_cache_delete( $this->get_id(), 'nmgr_wishlist' );
	}

	/**
	 * This function is left here for compatibility with crowfunding version that still uses it.
	 * @todo remove in later version
	 *
	 */
	public function cache_keys() {
		return [];
	}

}
