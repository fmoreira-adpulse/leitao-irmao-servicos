<?php

/**
 * Sync
 */
defined( 'ABSPATH' ) || exit;

class NMGR_Wishlist_Item extends NMGR_Data {

	/**
	 * Wishlist item data stored in nmgr_wishlist_items table
	 *
	 * @var array
	 */
	protected $core_data = array(
		'wishlist_id' => 0,
		'product_or_variation_id' => 0,
		'product_id' => 0,
		'variation_id' => 0,
		'variation' => array(),
		'quantity' => 0,
		'purchased_quantity' => 0,
		'unique_id' => '',
		'quantity_reference' => array(),
		'purchase_log' => array(),
		'date_created' => '',
	);
	protected $extra_data = [
		'product_name' => '',
		'product_status' => '',
		'product_stock_quantity' => '',
		'product_stock_status' => '',
		'product_price' => 0,
		'product_sku' => '',
		'product_or_variation_image_id' => 0,
		'product_image_id' => 0,
	];

	/**
	 * Meta type.
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_metadata/.
	 * @var string
	 */
	public $meta_type = 'wishlist_item';

	/**
	 * Name of the object type
	 *
	 * @var string
	 */
	protected $object_type = 'wishlist_item';

	/**
	 * Constructor.
	 *
	 * @param int|object $item ID to load from the DB, or NMGR_Wishlist_Item object.
	 */
	public function __construct( $item = 0 ) {
		// $item would be an object with 'wishlist_item_id' if read from database
		if ( is_object( $item ) && !empty( $item->wishlist_item_id ) ) {
			$this->set_id( absint( $item->wishlist_item_id ) );
		}

		parent::__construct( $item );

		if ( $this->get_id() > 0 ) {
			$this->read();
		}
	}

	/*
	  |--------------------------------------------------------------------------
	  | Getters
	  |--------------------------------------------------------------------------
	 */

	public function get_product_name() {
		return $this->get_prop( 'product_name' );
	}

	public function get_product_status() {
		return $this->get_prop( 'product_status' );
	}

	public function get_product_sku() {
		return $this->get_prop( 'product_sku' );
	}

	public function get_product_stock_status() {
		return $this->get_prop( 'product_stock_status' );
	}

	/**
	 * @return int|null
	 */
	public function get_product_stock_quantity() {
		return $this->get_prop( 'product_stock_quantity' );
	}

	public function get_product_image_id() {
		$pv_image_id = $this->get_prop( 'product_or_variation_image_id' );
		return ( int ) ($pv_image_id ? $pv_image_id : $this->get_prop( 'product_image_id' ));
	}

	public function get_product_image( $size = 'nmgr_thumbnail', $args = [], $placeholder = true ) {
		$image = '';
		$image_id = $this->get_product_image_id();

		$attr = array_merge( [
			'title' => $this->get_product_name(),
			'class' => 'nmgr-tip',
			'alt' => $this->get_product_name()
			],
			$args
		);

		if ( $image_id ) {
			$image = wp_get_attachment_image( $image_id, $size, false, $attr );
		}

		if ( !$image && $placeholder ) {
			$image = wc_placeholder_img( $size, $attr );
		}

		return $image;
	}

	public function is_in_stock() {
		return 'outofstock' !== $this->get_product_stock_status();
	}

	public function is_purchasable() {
		return $this->get_cost();
	}

	public function get_product_permalink() {
		$url = get_permalink( $this->get_product_id() );

		$variation = $this->get_variation();
		if ( $url && !empty( $variation ) ) {
			// Filter and encode keys and values so this is not broken by add_query_arg.
			$variation = array_map( 'urlencode', $variation );
			$keys = array_map( 'urlencode', array_keys( $variation ) );
			$url = add_query_arg( array_combine( $keys, $variation ), $url );
		}

		return $url;
	}

	/**
	 * Get the id of the wishlist the item belongs to
	 *
	 * @return int
	 */
	public function get_wishlist_id() {
		return absint( $this->get_prop( 'wishlist_id' ) );
	}

	/**
	 * Get the date the item was added to the wishlist
	 *
	 * @return Timestamp
	 */
	public function get_date_created() {
		return $this->get_prop( 'date_created' );
	}

	/**
	 * Get the date the item was last updated in the wishlist
	 *
	 * @return Timestamp
	 */
	public function get_date_modified() {
		_deprecated_function( __METHOD__, '4.12' );
		return $this->get_prop( 'date_modified' );
	}

	public function get_product_or_variation_id() {
		return absint( $this->get_prop( 'product_or_variation_id' ) );
	}

	public function get_product_id() {
		return absint( $this->get_prop( 'product_id' ) );
	}

	/**
	 * Get the id of the product variation this item represents
	 *
	 * @return int
	 */
	public function get_variation_id() {
		return absint( $this->get_prop( 'variation_id' ) );
	}

	/**
	 * Get the variation of the product this item represents
	 *
	 * @return array
	 */
	public function get_variation() {
		return array_filter( ( array ) $this->get_prop( 'variation' ) );
	}

	/**
	 * Get the quantity of this item in the wishlist
	 *
	 * @return int
	 */
	public function get_quantity() {
		return ( int ) $this->get_prop( 'quantity' );
	}

	/**
	 * Get the purchased quantity of this item in the wishlist
	 *
	 * @return int
	 */
	public function get_purchased_quantity() {
		return ( int ) apply_filters( 'nmgr_item_purchased_quantity', $this->get_prop( 'purchased_quantity' ), $this );
	}

	/**
	 * Get the unpurchased quantity of the item
	 * This only works if the quantity and purchased quantity columns are visible on the items table
	 *
	 * @return int
	 */
	public function get_unpurchased_quantity() {
		return max( $this->get_quantity() - $this->get_purchased_quantity(), 0 );
	}

	/**
	 * Get the unique id of this item
	 *
	 * @return string
	 */
	public function get_unique_id() {
		return $this->get_prop( 'unique_id' );
	}

	/**
	 * Get the quantity reference of this item
	 *
	 * @return array
	 */
	public function get_quantity_reference() {
		return array_filter( ( array ) $this->get_prop( 'quantity_reference' ) );
	}

	/**
	 * Get the product this item represents
	 *
	 * @return WC_Product
	 */
	public function get_product() {
		return wc_get_product( $this->get_product_or_variation_id() );
	}

	/**
	 * Get the wishlist this item belongs to
	 *
	 * @return NMGR_Wishlist
	 */
	public function get_wishlist() {
		return nmgr_get_wishlist( $this->get_wishlist_id() );
	}

	/**
	 * Get the total cost of the wishlist item (cost of product x qty)
	 *
	 * @param bool $currency_symbol Whether to return the value formatted with the currency symbol
	 * @return string
	 */
	public function get_total( $currency_symbol = false ) {
		$total = ( float ) ($this->get_quantity() * $this->get_cost());
		return $currency_symbol ? wc_price( $total, array( 'currency' => get_woocommerce_currency() ) ) : $total;
	}

	public function get_cost( $currency_symbol = false ) {
		$price = ( float ) $this->get_prop( 'product_price' );
		return $currency_symbol ? wc_price( $price, array( 'currency' => get_woocommerce_currency() ) ) : $price;
	}

	/**
	 * Get variations that are not shown in the item title
	 * This is because variation titles display the attributes
	 *
	 * @return array Array of variation name, value pairs
	 */
	public function get_variations_for_display() {
		_deprecated_function( __METHOD__, '4.4.0', 'nmgr_get_variations_for_display' );
		return nmgr_get_variations_for_display( $this->get_variation(), $this->get_product_name() );
	}

	/**
	 * Get the method used to purchase quantities of the item
	 * @return array
	 */
	public function get_purchase_log() {
		return array_filter( ( array ) $this->get_prop( 'purchase_log' ) );
	}

	public function get_purchase_log_props() {
		$pro = nmgr()->is_pro;

		return [
			'id' => [
				'label' => $pro ? __( 'ID', 'nm-gift-registry' ) : __( 'ID', 'nm-gift-registry-lite' ),
			],
			'type' => [
				'label' => $pro ? __( 'Type', 'nm-gift-registry' ) : __( 'Type', 'nm-gift-registry-lite' ),
			],
			'quantity' => [
				'label' => $pro ? __( 'Quantity', 'nm-gift-registry' ) : __( 'Quantity', 'nm-gift-registry-lite' ),
			],
			'user_id' => [
				'label' => $pro ? __( 'User ID', 'nm-gift-registry' ) : __( 'User ID', 'nm-gift-registry-lite' ),
			],
			'route' => [
				'label' => $pro ? __( 'Route', 'nm-gift-registry' ) : __( 'Route', 'nm-gift-registry-lite' ),
			],
			'date' => [
				'label' => $pro ? __( 'Date', 'nm-gift-registry' ) : __( 'Date', 'nm-gift-registry-lite' ),
			],
		];
	}

	/**
	 * Add a purchase method for an item when it's purchased quantity has been updated
	 * to log details about the particular update
	 *
	 * @param int $quantity The quantity of the item reflecting the current purchase.
	 * This number should be negative if the purchase is a refund and positive if it
	 * is indeed a purchase.
	 * @param type $method_type The type of purchase. Default values are 'order' and 'manual'
	 * to determine whether the purchased quantity was updated via an order or manually via
	 * the wishlist items table
	 * @param array $args Extra data to add to the purchase method log. For example 'order_id'.
	 */
	public function add_purchase_log( $quantity, $method_type = 'order', $args = [] ) {
		$original = $this->get_purchase_log();
		$id = $this->get_wishlist_id() . '_' . $this->get_id() . '_' . time();
		$default = [
			'id' => $id,
			'type' => $method_type,
			'quantity' => ( int ) $quantity,
			'user_id' => get_current_user_id(),
			'route' => is_nmgr_admin() ? 'admin' : 'frontend',
			'date' => current_time( 'mysql' ),
		];

		$fargs = array_merge( $default, $args );
		$original[ $id ] = $fargs;
		$this->set_purchase_log( $original );
	}

	public function set_purchase_log( $value ) {
		$this->set_prop( 'purchase_log', $value );
	}

	/**
	 * Get the amount purchased for the item from orders
	 *
	 * This is taken from the actual price of the item in each order
	 * typical made from the checkout page.
	 * @return int|float
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
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim2 ON oim.order_item_id = oim2.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim3 ON oim2.order_item_id = oim3.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON oim.order_item_id = oi.order_item_id
			LEFT JOIN $orders_table AS po ON oi.order_id = po.ID
			WHERE oim.meta_key = '_line_total'
			AND oim2.meta_key = 'nmgr_item_id' AND oim2.meta_value = {$this->get_id()}
			AND oim3.meta_key = 'nmgr_wishlist_id' AND oim3.meta_value = (
			SELECT wishlist_id FROM {$wpdb->prefix}nmgr_wishlist_items
				WHERE wishlist_item_id = {$this->get_id()}
			)
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

		return nmgr_round( apply_filters_deprecated( 'nmgr_item_purchased_amount', [ $amt, $this ], '4.4.0' ) );
	}

	/**
	 * Get the total amount purchased for the item.
	 * This may include amount purchased from orders and other methods such as crowdfunds.
	 * @return float
	 */
	public function get_total_purchased_amount() {
		return nmgr_round( apply_filters( 'nmgr_item_total_purchased_amount', $this->get_purchased_amount(), $this ) );
	}

	/**
	 * Get the amount left to be purchased for the item
	 * @return int|float
	 */
	public function get_unpurchased_amount() {
		return nmgr_round( $this->get_total() ) - nmgr_round( $this->get_purchased_amount() );
	}

	/**
	 * Get the total amount left to be purchased for the item
	 * This may include amount unpurchased from orders and other methods such as crowdfunds.
	 * @return int|float
	 */
	public function get_total_unpurchased_amount() {
		return nmgr_round( $this->get_total() ) - nmgr_round( $this->get_total_purchased_amount() );
	}

	/**
	 * Get the data that would be used to identify the item in the cart (as cart item data)
	 * or in the order (as order item metadata)
	 *
	 * Wishlist information gotten:
	 * - wishlist_id
	 * - wishlist_item_id
	 * - product_id
	 * - variation_id
	 * - type
	 *
	 * @return array
	 */
	public function get_cart_order_data() {
		return array(
			'wishlist_id' => ( int ) $this->get_wishlist_id(),
			'wishlist_item_id' => ( int ) $this->get_id(),
			'product_id' => $this->get_product_id(),
			'variation_id' => $this->get_variation_id(),
			'type' => 'wishlist_item',
		);
	}

	/**
	 * Get the ids of all the order items that have this item's product
	 * @return int[]
	 */
	public function get_order_item_ids() {
		_deprecated_function( __METHOD__, '4.5.0', __CLASS__ . '->get_paid_order_item_ids()' );
		global $wpdb;

		$order_item_ids = $this->cache_get( 'order_item_ids' );

		if ( false === $order_item_ids ) {
			$order_item_ids = $wpdb->get_col( $wpdb->prepare( "
		SELECT DISTINCT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta
		WHERE meta_key = 'nmgr_item_id' AND meta_value = %d",
					$this->get_id()
				) );

			$this->cache_set( 'order_item_ids', $order_item_ids );
		}

		return is_array( $order_item_ids ) ? array_map( 'absint', $order_item_ids ) : [];
	}

	public function get_paid_order_item_ids() {
		global $wpdb;

		$orders_table = nmgr_orders_table();
		$status_key = false !== strpos( $orders_table, 'posts' ) ? 'post_status' : 'status';
		$order_item_ids = $this->cache_get( 'paid_order_item_ids' );

		if ( false === $order_item_ids ) {
			$order_item_ids = $wpdb->get_col( $wpdb->prepare( "
		SELECT DISTINCT oim.order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta AS oim
		LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON oim.order_item_id = oi.order_item_id
		LEFT JOIN $orders_table AS po ON oi.order_id = po.ID
		WHERE oim.meta_key = 'nmgr_item_id' AND oim.meta_value = %d
		AND po.$status_key IN ('wc-" . implode( "','wc-", array_map( 'esc_sql', nmgr_is_paid_statuses() ) ) . "')"
					,
					$this->get_id()
				) );

			$this->cache_set( 'paid_order_item_ids', $order_item_ids );
		}

		return is_array( $order_item_ids ) ? array_map( 'absint', $order_item_ids ) : [];
	}

	/*
	  |--------------------------------------------------------------------------
	  | Setters
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Set wishlist ID.
	 *
	 * @param int $value Wishlist ID.
	 */
	public function set_wishlist_id( $value ) {
		$this->set_prop( 'wishlist_id', absint( $value ) );
	}

	/**
	 * Set item desired quantity
	 *
	 * @param int $value Desired quantity
	 */
	public function set_quantity( $value ) {
		$this->set_prop( 'quantity', wc_stock_amount( $value ) );
	}

	/**
	 * Set item purchased quantity
	 *
	 * @param int $value purchased quantity
	 */
	public function set_purchased_quantity( $value ) {
		$this->set_prop( 'purchased_quantity', absint( $value ) );
	}

	/**
	 * Set item product id
	 *
	 * @param int $value Product id.
	 */
	public function set_product_id( $value ) {
		$this->set_prop( 'product_id', absint( $value ) );
	}

	/**
	 * Set item variation id
	 * @param int $value Product Id/Variation id
	 */
	public function set_variation_id( $value ) {
		$this->set_prop( 'variation_id', absint( $value ) );
	}

	/**
	 * Set the item variation
	 * @param array Product variation
	 */
	public function set_variation( $value ) {
		$this->set_prop( 'variation', $value );
	}

	/**
	 * Set all product details for item at once based on the product the item represents
	 *
	 * This sets the product id, variation id and variation
	 *
	 * @param WC_Product $product Product the item represents
	 */
	public function set_product( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$this->set_product_id( $product->get_parent_id() );
			$this->set_variation_id( $product->get_id() );
			$this->set_variation( is_callable( array( $product, 'get_variation_attributes' ) ) ? $product->get_variation_attributes() : array() );
		} else {
			$this->set_product_id( $product->get_id() );
		}
	}

	/**
	 * Set the unique id for the item
	 * @param string $value unique id
	 */
	public function set_unique_id( $value ) {
		$this->set_prop( 'unique_id', $value );
	}

	/**
	 * Set the quantity reference for the item
	 * @param array $value Quantity reference
	 */
	public function set_quantity_reference( $value ) {
		$this->set_prop( 'quantity_reference', $value );
	}

	/**
	 * Add the data for this wishlist item as order item meta
	 * Make sure to save the order item ($order_item) afterwards as this
	 * function doesn't save it.
	 * @param WC_Order_Item $order_item
	 */
	public function add_order_item_meta( $order_item ) {
		$order_item->add_meta_data( 'nmgr_item_id', $this->get_id() );
		$order_item->add_meta_data( 'nmgr_wishlist_id', $this->get_wishlist_id() );
	}

	/*
	  |--------------------------------------------------------------------------
	  | Conditionals
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Get whether any quantity of this item has been purchased
	 *
	 * This is only possible if the 'purchased quantity' column is visible on the items table
	 * as it is the column used to determine that item purchased would be accounted for
	 *
	 * @return boolean True or false
	 */
	public function is_purchased() {
		$purchased = ( bool ) $this->get_purchased_quantity();
		return ( bool ) apply_filters( 'nmgr_item_is_purchased', $purchased, $this );
	}

	/**
	 * Get whether the desired quantity of this item has been completely purchased
	 *
	 * This is typically only possible if the 'quantity' and 'purchased_quantity' columns are visible on the items table
	 *
	 * @return boolean
	 */
	public function is_fulfilled() {
		$fulfilled = ( bool ) 0 >= $this->get_unpurchased_quantity();
		return ( bool ) apply_filters( 'nmgr_item_is_fulfilled', $fulfilled, $this );
	}

	public function read_meta_data() {
		_deprecated_function( __METHOD__, '4.10' );
		$props_to_meta_keys = $this->get_meta_keys( $this->get_meta_data() );
		foreach ( $props_to_meta_keys as $prop => $meta_key ) {
			if ( metadata_exists( $this->meta_type, $this->get_id(), $meta_key ) ) {
				$val = get_metadata( $this->meta_type, $this->get_id(), $meta_key, true );
			} else {
				$val = $this->get_default_data()[ $prop ] ?? '';
			}
			$this->set_prop( $prop, $val );
		}
	}

	public function get_wishlist_type() {
		return nmgr()->wishlist()->get_type_from_db( $this->get_wishlist_id() );
	}

	/**
	 * Wrapper function to update the purchased quantity of an item
	 *
	 * @param array $args Arguments used to perform the update:
	 * - quantity {int} The new purchased quantity.
	 * - paid {boolean} Whether the order should be marked as paid. Default false.
	 * - create_order {boolean} Whether to create an order to reflect the update. Default true.
	 * - apply_price - {boolean) Whether to include the price of the item in the created order. Default true.
	 * - order_note - {string}. Order note that should be added to the order. Default none.
	 * - order_item_meta = {array} Metadata that should be added to the order item if created. Default none.
	 * @return null|WC_Order order object if an order is created, else null
	 */
	public function update_purchased_quantity( $args = [] ) {
		if ( isset( $args[ 'quantity' ] ) ) {
			$update_qty = ( int ) $args[ 'quantity' ];
			$purchased_qty = $this->get_purchased_quantity();

			if ( $update_qty < $purchased_qty ) {
				$this->set_purchased_quantity( $update_qty );
				$this->save();
			} elseif ( $update_qty > $purchased_qty ) {
				if ( false === ( bool ) ( $args[ 'create_order' ] ?? true ) ) {
					$this->set_purchased_quantity( $update_qty );
					$this->save();
				} else {
					$args[ 'quantity' ] = $update_qty - $purchased_qty;
					return $this->create_order( $args );
				}
			}
		}
	}

	/**
	 * Create an order for the wishlist item
	 *
	 * @param array $args Arguments used:
	 * - quantity {int} The order item quantity.
	 * - paid {boolean} Whether the order should be marked as paid. Default false.
	 * - apply_price - {boolean) Whether to include the price of the item in the created order. Default true.
	 * - order_note - {string}. Order note that should be added to the order. Default none.
	 * - order_item_meta = {array} Metadata that should be added to the order item if created. Default none.
	 * @return null|WC_Order order object if an order is created, else null
	 */
	public function create_order( $args = [] ) {
		$order = new \WC_Order();
		$order->set_created_via( 'nmgr_wishlist' );
		$status = ( $args[ 'paid' ] ?? false ) ? 'processing' : 'on-hold';
		$order->set_status( apply_filters( 'nmgr_item_purchased_order_status', $status, $args ) );

		if ( $args[ 'paid' ] ?? false ) {
			$order->set_date_paid( time() );
		}

		if ( function_exists( 'is_nmgr_shipping_to_wishlist_address' ) &&
			is_nmgr_shipping_to_wishlist_address() ) {
			$wishlist = $this->get_wishlist();
			if ( $wishlist->has_shipping_address() ) {
				foreach ( $wishlist->get_shipping() as $key => $value ) {
					if ( is_callable( array( $order, "set_shipping_{$key}" ) ) ) {
						$order->{"set_shipping_{$key}"}( $value );
					}
				}
			}
		}

		$product = $this->get_product();

		if ( $product ) {
			$order_item = new \WC_Order_Item_Product();
			$quantity = $args[ 'quantity' ];
			$subtotal = wc_get_price_excluding_tax( $product, [ 'qty' => $quantity ] );

			$order_item->set_props( [
				'name' => $product->get_name(),
				'tax_class' => $product->get_tax_class(),
				'product_id' => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
				'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
				'variation' => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
				'quantity' => $quantity,
				'subtotal' => $subtotal,
				'total' => false === ( bool ) ( $args[ 'apply_price' ] ?? true ) ? 0 : $subtotal,
			] );

			if ( !empty( $args[ 'order_item_meta' ] ) ) {
				foreach ( $args[ 'order_item_meta' ] as $meta_key => $meta_value ) {
					$order_item->add_meta_data( $meta_key, $meta_value );
				}
			}

			$this->add_order_item_meta( $order_item );

			$order->add_item( $order_item );
			$order->calculate_totals();
		}

		nmgr()->order()->add_meta_data( $order ); // Order is saved here

		$order->add_order_note( nmgr_get_custom_order_notice() );

		if ( !empty( $args[ 'order_note' ] ) ) {
			$order->add_order_note( $args[ 'order_note' ] );
		}

		return $order;
	}

	public function get_order_created_toast_notice( $order ) {
		if ( is_a( $order, \WC_Order::class ) ) {
			$order_notice = nmgr()->is_pro ?
				__( 'Order created', 'nm-gift-registry' ) :
				__( 'Order created', 'nm-gift-registry-lite' );

			if ( is_nmgr_admin() ) {
				$order_notice .= sprintf( ' <a style="color:#fff;text-decoration:underline;" href="%s" tabindex="1" class="nmgr-view-btn">%s</a>',
					esc_url( get_edit_post_link( $order->get_id() ) ),
					nmgr()->is_pro ? __( 'View', 'nm-gift-registry' ) : __( 'View', 'nm-gift-registry-lite' )
				);
			}

			return nmgr_get_toast_notice( $order_notice );
		}
	}

	/*
	  |--------------------------------------------------------------------------
	  | CRUD
	  |--------------------------------------------------------------------------
	 */

	public function create() {
		global $wpdb;

		$current_time = current_time( 'mysql', 1 );
		$this->set_prop( 'date_created', $current_time );

		$core_data = $this->get_core_data();

		$wpdb->insert( $wpdb->prefix . 'nmgr_wishlist_items', array_map( 'maybe_serialize', $core_data ) );
		$this->set_id( $wpdb->insert_id );
		$this->apply_changes();

		$this->clear_wishlist_cache();

		if ( has_action( 'nmgr_wishlist_item_created' ) ) {
			do_action_deprecated( 'nmgr_wishlist_item_created',
				[ $this, $this->get_wishlist() ], '4.11.4', 'nmgr_item_created'
			);
		}
		do_action( 'nmgr_item_created', $this );
	}

	public function read() {
		$items_data = $this->cache_get( 'read' );

		if ( false === $items_data ) {
			$this->set_defaults();
			$args = [
				'limit' => 1,
				'where' => 'AND items.wishlist_item_id = ' . $this->get_id(),
			];
			$items_data = $this->get_from_db( $args );
			$this->cache_set( 'read', $items_data );
		}

		if ( !$items_data ) {
			/* translators: %s: wishlist type title */
			throw new Exception( sprintf(
						/* translators: %s: wishlist type title */
						nmgr()->is_pro ? __( 'Invalid %s item.', 'nm-gift-registry' ) : __( 'Invalid %s item.', 'nm-gift-registry-lite' ),
						nmgr_get_type_title()
					) );
		}

		$data = reset( $items_data );
		foreach ( array_keys( $this->get_data() ) as $key ) {
			if ( property_exists( $data, $key ) ) {
				$this->set_prop( $key, maybe_unserialize( $data->$key ) );
			}
		}
		$this->set_object_read();
	}

	public function update() {
		global $wpdb;

		$this->data = array_replace( $this->get_data(), $this->changes );
		$core_data = $this->get_core_data();

		$wpdb->update(
			$wpdb->prefix . 'nmgr_wishlist_items',
			array_map( 'maybe_serialize', $core_data ),
			array( 'wishlist_item_id' => $this->get_id() )
		);

		$this->clear_cache();
		$this->clear_wishlist_cache();
		$this->apply_changes();

		if ( has_action( 'nmgr_wishlist_item_updated' ) ) {
			do_action_deprecated( 'nmgr_wishlist_item_updated',
				[ $this, $this->get_wishlist() ], '4.11.4', 'nmgr_item_updated'
			);
		}
		do_action( 'nmgr_item_updated', $this );
	}

	public function delete() {
		global $wpdb;

		$wishlist_id = $this->get_wishlist_id();

		do_action( 'nmgr_before_delete_wishlist_item', $this->get_id() );

		$this->clear_cache();
		$this->clear_wishlist_cache();

		$wpdb->delete( $wpdb->prefix . 'nmgr_wishlist_items', array( 'wishlist_item_id' => $this->get_id() ) );
		$wpdb->delete( $wpdb->prefix . 'nmgr_wishlist_itemmeta', array( 'wishlist_item_id' => $this->get_id() ) );

		do_action( 'nmgr_wishlist_item_deleted', $this->get_id(), $wishlist_id );

		$this->set_id( 0 );
		return true;
	}

	/**
	 * @return NMGR_Wishlist_Item[]|\NMGR\Sub\Wishlist_Item[]
	 */
	public function get_from_db( $args = [] ) {
		global $wpdb;

		$defaults = [
			'limit' => null,
			'offset' => 0,
			'order' => 'DESC',
			'orderby' => 'items.date_created',
			'where' => null,
			'page' => null, // 'page' and 'offset' should not be used together.
			'select' => 'items.*,
			product.max_price AS product_price,
			product.sku AS product_sku,
			product.stock_status AS product_stock_status,
			product.stock_quantity AS product_stock_quantity,
			posts.post_title AS product_name,
			posts.post_status AS product_status,
			postmeta.meta_value AS product_or_variation_image_id,
			postmeta2.meta_value AS product_image_id
			',
			'return' => 'items', // other values are query, raw_results
		];

		$p_args = wp_parse_args( $args, $defaults );
		$limit = max( 0, ( int ) $p_args[ 'limit' ] );

		if ( $p_args[ 'page' ] ) {
			$p_args[ 'offset' ] = max( 0, (( int ) $p_args[ 'page' ] - 1) * $limit );
		}

		$select_sql = $p_args[ 'select' ];
		$where_sql = $p_args[ 'where' ] ?? '';
		$limit_sql = $limit ? $wpdb->prepare( "LIMIT %d", $limit ) : '';
		$offset_sql = $limit ? 'OFFSET ' . $p_args[ 'offset' ] : '';
		$order_sql = $p_args[ 'orderby' ] ? esc_sql( "ORDER BY {$p_args[ 'orderby' ]} {$p_args[ 'order' ]}" ) : '';
		$sql = "SELECT $select_sql "
			. "FROM {$wpdb->prefix}nmgr_wishlist_items AS items "
			. "LEFT JOIN $wpdb->posts AS posts "
			. "ON items.product_or_variation_id = posts.ID "
			. "LEFT JOIN $wpdb->postmeta AS postmeta "
			. "ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_thumbnail_id' "
			. "LEFT JOIN $wpdb->postmeta AS postmeta2 "
			. "ON IF(posts.post_parent != '', posts.post_parent, posts.ID) = postmeta2.post_id AND postmeta2.meta_key = '_thumbnail_id' "
			. "LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup AS product "
			. "ON posts.ID = product.product_id "
			. "WHERE 1=1 $where_sql "
			. "$order_sql "
			. "$limit_sql "
			. "$offset_sql";

		// Items from database are returned as objects
		$items_data = $wpdb->get_results( $sql );

		return $items_data;
	}

	public function cache_get( $key ) {
		$data = wp_cache_get( $this->get_id(), 'nmgr_item' );
		return (false !== $data && isset( $data[ $key ] )) ? $data[ $key ] : false;
	}

	public function cache_set( $key, $value ) {
		$data = wp_cache_get( $this->get_id(), 'nmgr_item' );
		if ( false === $data ) {
			$data = [];
		}

		$data[ $key ] = $value;
		wp_cache_set( $this->get_id(), $data, 'nmgr_item' );
	}

	public function clear_cache() {
		wp_cache_delete( $this->get_id(), 'nmgr_item' );
	}

	public function clear_wishlist_cache() {
		wp_cache_delete( $this->get_wishlist_id(), 'nmgr_wishlist' );
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
