<?php

/**
 * Sync
 */
use NMGR\Tables\ItemsTable;
use NMGR\Fields\ItemFields;

defined( 'ABSPATH' ) || exit;

/**
 * Output the view related to the wishlist item
 */
class NMGR_Items_View {

	/**
	 * Arguments used to compose the view
	 * @deprecated since version 4.11
	 * @var array
	 */
	public $args = [];

	/**
	 * All the wishlist items in the current view
	 * @deprecated since version 4.11
	 * @var \NMGR_Wishlist_Item[]|NMGR\Sub\Wishlist_Item[]
	 */
	public $items = [];

	/**
	 * The current wishlist item in the view
	 * @deprecated since version 4.11
	 * @var \NMGR_Wishlist_Item|NMGR\Sub\Wishlist_Item
	 */
	public $item;

	/**
	 * The current wishlist being viewed
	 * @deprecated since version 4.11
	 * @var NMGR_Wishlist
	 */
	public $wishlist;

	/**
	 * The display properties
	 * E.g. display mode (list or grid), display row/columns
	 * @deprecated since version 4.11
	 * @var array
	 */
	public $display = [];

	/**
	 * @deprecated since version 4.11
	 */
	public $is_gift_registry = true;

	/**
	 * @deprecated since version 4.11
	 */
	public $is_admin;

	/**
	 * @deprecated since version 4.11
	 */
	public $is_public;

	/**
	 * @deprecated since version 4.6.0
	 */
	public $parts_data = [];

	/**
	 * @var ItemFields
	 * @deprecated since version 4.11
	 */
	public $item_fields = [];

	/**
	 * @deprecated since version 4.11
	 */
	public $items_table;

	/**
	 * Inititialize the view for the wishlist items
	 *
	 * @param int|NMGR_Wishlist $wishlist Wishlist id or object
	 */
	public function __construct( $wishlist = null ) {
		if ( $wishlist ) {
			$this->wishlist = is_a( $wishlist, \NMGR_Wishlist::class ) ? $wishlist : nmgr_get_wishlist( $wishlist );
		}

		$this->is_admin = is_nmgr_admin();
		$this->is_public = false === ($this->is_admin || nmgr_user_has_wishlist( $this->wishlist ));

		if ( $this->wishlist ) {
			$this->is_gift_registry = $this->wishlist->is_type( 'gift-registry' );
		}
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_items() {
		_deprecated_function( __METHOD__, '4.11' );
		if ( !$this->items && $this->wishlist ) {
			$this->items = $this->wishlist->read_items();
		}
		return $this->items;
	}

	/**
	 * @deprecated since version 4.11
	 * @param int|NMGR_Wishlist_Item|\NMGR\Sub\Wishlist_Item $item Item id or object
	 */
	public function set_item( $item ) {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->set_row_object()' );
		$item_object = is_a( $item, \NMGR_Wishlist_Item::class ) ? $item : nmgr_get_wishlist_item( $item );
		$this->items_table()->set_row_object( $item_object );
		$this->item = $item_object;
	}

	/**
	 * @deprecated sisnce version 4.11
	 * @return NMGR_Wishlist_Item|\NMGR\Sub\Wishlist_Item
	 */
	public function get_item() {
		_deprecated_function( __METHOD__, '4.11' );
		return $this->item;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function set_wishlist( $wishlist ) {
		_deprecated_function( __METHOD__, '4.11' );
		$this->wishlist = is_a( $wishlist, \NMGR_Wishlist::class ) ? $wishlist : nmgr_get_wishlist( $wishlist );
	}

	/**
	 * @deprecated since version 4.11
	 * @return NMGR_Wishlist
	 */
	public function get_wishlist() {
		_deprecated_function( __METHOD__, '4.11' );
		return $this->wishlist;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_args() {
		_deprecated_function( __METHOD__, '4.11' );
		return apply_filters_deprecated( 'nmgr_items_view_args', [ $this->args, $this ], '4.11' );
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function set_args( $args ) {
		_deprecated_function( __METHOD__, '4.11' );
		$this->args = $args;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function is_gift_registry() {
		_deprecated_function( __METHOD__, '4.11' );
		return $this->is_gift_registry;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function is_admin() {
		_deprecated_function( __METHOD__, '4.11' );
		return $this->is_admin;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function add_to_cart_text() {
		_deprecated_function( __METHOD__, '4.11' );
		$text = nmgr()->is_pro ?
			__( 'Add to cart', 'nm-gift-registry' ) :
			__( 'Add to cart', 'nm-gift-registry-lite' );
		return apply_filters( 'nmgr_add_to_cart_text', $text, $this );
	}

	/**
	 * @deprecated since version 4.11
	 */
	protected function display() {
		_deprecated_function( __METHOD__, '4.11' );
		return [
			'mode' => 'list',
			'columns' => '',
			'row-gap' => '',
			'column-gap' => '',
			'toggle_mode' => false,
		];
	}

	/**
	 * Get the display properties for the view.
	 * @deprecated since version 4.11
	 * @return array
	 */
	public function get_display() {
		_deprecated_function( __METHOD__, '4.11' );
		if ( !$this->display ) {
			$this->display = !$this->is_admin ?
				apply_filters_deprecated( 'nmgr_items_display', [ $this->display(), $this ], '4.11' ) :
				$this->display();
		}
		return $this->display;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_type() {
		_deprecated_function( __METHOD__, '4.11' );
		return $this->is_gift_registry ? 'gift-registry' : 'wishlist';
	}

	/**
	 * @deprecated since version 4.6.0
	 */
	public function get_parts_data() {
		_deprecated_function( __METHOD__, '4.6.0', __CLASS__ . '->get_item_fields_data()' );
		return $this->parts_data = $this->get_item_fields_data();
	}

	/**
	 * Get the attributes for the view container ('list' or 'grid')
	 * @deprecated since version 4.6.0
	 * @param boolean $formatted Whether to format the attributes
	 * @return string
	 */
	public function get_items_container_attributes( $formatted = true ) {
		_deprecated_function( __METHOD__, '4.6.0' );

		$attributes = [
			'class' => [ 'nmgr-items-view', $this->get_display()[ 'mode' ] ]
		];

		if ( 'list' == $this->get_display()[ 'mode' ] ) {
			$attributes[ 'class' ][] = 'nmgr-table';
			$attributes[ 'class' ][] = 'nmgr-items-table';
		}

		$attr = apply_filters( 'nmgr_items_view_attributes', $attributes, $this );

		return $formatted ? nmgr_format_attributes( $attr ) : $attr;
	}

	/**
	 * Whether the item should be hidden from view
	 * (All items in the wishlist are shown by default)
	 * @deprecated since version 4.11
	 * @return boolean
	 */
	public function item_is_disabled() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->item_is_disabled()' );
		return $this->items_table()->item_is_disabled();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_item_statuses() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_statuses()' );
		return $this->items_table()->get_item_statuses();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_item_statuses_for_disabling_add_to_cart() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_statuses_for_disabling_add_to_cart()' );
		return $this->items_table()->get_item_statuses_for_disabling_add_to_cart();
	}

	/**
	 * Get the attributes used for the item container html
	 * @deprecated since version 4.6.0
	 * @param boolean $formatted Whether to format the attributes for html output
	 * @return array
	 */
	public function get_item_container_attributes( $formatted = true ) {
		_deprecated_function( __METHOD__, '4.6.0' );
		$item_statuses = $this->get_item_statuses();
		$classes = array_merge( [ 'item' ], array_keys( $item_statuses ) );

		$attr = array(
			'id' => $this->get_item_id(),
			'class' => array_filter( $classes ),
			'data-product_title' => sanitize_text_field( $this->get_item()->get_product_name() ),
			'data-wishlist_item_id' => absint( $this->get_item()->get_id() ),
			'data-wishlist_id' => absint( $this->wishlist->get_id() ),
		);

		$attributes = apply_filters( 'nmgr_item_view_attributes', $attr, $this );
		return $formatted ? nmgr_format_attributes( $attributes ) : $attributes;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_item_id() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_id()' );
		return $this->items_table()->get_item_id();
	}

	/**
	 * Replace the default html tag used for the template with the current tag
	 * @deprecated since version 3.0.0
	 * @param string $template The html template
	 * @return string Template html
	 */
	protected function replace_tag( $template ) {
		_deprecated_function( __METHOD__, '3.0.0' );
		$tag = 'list' === $this->get_display()[ 'mode' ] ? 'td' : 'div';
		return str_replace( array( '<td', '</td' ), array( "<$tag", "</$tag" ), $template );
	}

	/**
	 * @deprecated since version 4.6.0
	 */
	public function get_part_attributes( $part ) {
		_deprecated_function( __METHOD__, '4.6.0' );
		$attributes = [
			'class' => [
				'nmgr-item-part',
				'nmgr_' . $part
			],
		];

		$data = $this->get_parts_data()[ $part ] ?? null;

		if ( !empty( $data[ 'label' ] ) &&
			(!array_key_exists( 'table_header_content', $data ) || !empty( $data[ 'table_header_content' ] )) ) {
			$attributes[ 'data-title' ] = $data[ 'label' ];
		}

		if ( 'action_buttons' !== $part ) {
			foreach ( $this->get_item_statuses() as $status ) {
				if ( true === ($status[ 'blur' ] ?? null) ) {
					$attributes[ 'class' ][] = 'nmgr-blur';
					break;
				}
			}
		}

		return $attributes;
	}

	/**
	 * Get the product image template
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_thumbnail() {
		_deprecated_function( __METHOD__, '4.6.0', 'NMGR_Items_View->items_table()->get_item_thumbnail()' );
		return $this->items_table()->get_item_thumbnail();
	}

	/**
	 * Get the template for the item's title
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_title() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_title()' );
		return $this->items_table()->get_item_title();
	}

	/**
	 * Get the template for the item's cost
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_cost() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_cost()' );
		return $this->items_table()->get_item_cost();
	}

	/**
	 * Get the template for the item's quantity
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_quantity() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_quantity()' );
		return $this->items_table()->get_item_quantity();
	}

	/**
	 * Get the template for the item's purchased quantity
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_purchased_quantity() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_purchased_quantity()' );
		return $this->items_table()->get_item_purchased_quantity();
	}

	/**
	 * Get the template for the item's total cost
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_total_cost() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_total_cost()' );
		return $this->items_table()->get_item_total_cost();
	}

	/**
	 * Get the template for the item's add to cart button
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_add_to_cart_button() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_add_to_cart_button()' );
		return $this->items_table()->get_item_add_to_cart_button();
	}

	/**
	 * Get all the actions that should be performed on the specific item in the view
	 * @param boolean $all Whether to get all the actions without any conditions of display. Default false.
	 * @deprecated since version 4.11
	 * @return array
	 */
	public function get_item_actions( $all = false ) {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_actions()' );
		return $this->items_table()->get_item_actions();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_item_dropdown() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_dropdown()' );
		return $this->items_table()->get_item_dropdown();
	}

	/**
	 * Get the template for the item's edit and delete buttons
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_action_buttons() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_action_buttons()' );
		return $this->items_table()->get_item_action_buttons();
	}

	/**
	 * @deprecated since version 4.6.0
	 */
	public function get_table_header( $part ) {
		_deprecated_function( __METHOD__, '4.6.0' );
		$part_data = $this->get_parts_data()[ $part ];
		$header = $part_data[ 'table_header_content' ] ?? ($part_data[ 'label' ] ?? '');

		if ( has_action( "nmgr_items_table_header_$part" ) ) {
			ob_start();
			do_action_deprecated(
				"nmgr_items_table_header_$part",
				[ $this ],
				'3.0.0',
				'nmgr_items_view_parts_data'
			);
			$header = ob_get_clean();
		}

		$attributes = [
			'class' => [
				"nmgr_$part",
				"item_$part",
			]
		];

		if ( $part_data[ 'orderby' ] ?? false ) {
			$orderby_key = $this->get_orderby_key( $part );
			$attributes[ 'data-orderby' ] = $orderby_key;
			$attributes[ 'class' ][] = 'orderby';

			if ( $orderby_key === ($this->get_orderby_key( $this->get_args()[ 'orderby' ] ?? ''  )) ) {
				// 'desc' is default items order
				$attributes[ 'class' ][] = strtolower( $this->get_args()[ 'order' ] ?? 'desc' );
			}
		}

		if ( !empty( $part_data[ 'table_header_attributes' ] ?? false  ) ) {
			$attributes = nmgr_merge_args( $attributes, ( array ) $part_data[ 'table_header_attributes' ] );
		}

		$content = apply_filters_deprecated(
			'nmgr_items_table_header_content',
			[ $header, $part, $this ],
			'3.0.0',
			'nmgr_items_view_parts_data'
		);

		return sprintf(
			'<th %1$s>%2$s</th>',
			nmgr_format_attributes( $attributes ),
			$content
		);
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_orderby_key( $part ) {
		_deprecated_function( __METHOD__, '4.11' );
		switch ( $part ) {
			case 'favourite':
			case 'purchased_quantity':
				$part = "items.$part";
				break;
			case 'cost':
				$part = 'product_price';
				break;
			case 'title':
				$part = 'product_name';
				break;
		}

		return $part;
	}

	/**
	 * @deprecated since version 4.6.0
	 */
	public function get_item_part_content( $part ) {
		_deprecated_function( __METHOD__, '4.6.0' );
		$table = $this->items_table();
		$table->set_row_object( $this->get_item() );
		return $table->get_cell( $part );
	}

	/**
	 * @deprecated since version 4.6.0
	 */
	protected function wrap_with_container( $content, $attributes = '' ) {
		_deprecated_function( __METHOD__, '4.6.0' );
		$tag = 'list' === $this->get_display()[ 'mode' ] ? 'td' : 'div';
		return sprintf(
			'<%1$s %2$s>%3$s</%4$s>',
			$tag,
			nmgr_format_attributes( $attributes ),
			$content,
			$tag
		);
	}

	/**
	 * Check if we are on the wishlist page for public view.
	 * This is the view that is not for the wishlist owner who has the wishlist.
	 * @deprecated since version 4.11
	 * @return bool
	 */
	public function is_public() {
		_deprecated_function( __METHOD__, '4.11' );
		return $this->is_public;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_delete_item_notice() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_delete_item_notice()' );
		return $this->items_table()->get_delete_item_notice();
	}

	/**
	 * @deprecated since version 4.6.0
	 */
	public function get_item_template() {
		_deprecated_function( __METHOD__, '4.6.0' );

		if ( $this->item_is_disabled() ) {
			return;
		}

		$display_mode = $this->get_display()[ 'mode' ];

		$temp = ('list' === $display_mode ? '<tr ' : '<div ') . $this->get_item_container_attributes() . '">';
		foreach ( array_keys( $this->get_parts_data() ) as $part ) {
			$temp .= $this->get_item_part_content( $part );
		}
		$temp .= 'list' === $display_mode ? '</tr>' : '</div>';

		return $temp;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_item_template_data( $item ) {
		_deprecated_function( __METHOD__, '4.11', 'NMGR\Tables\ItemsTable->get_item_template_data' );
		return $this->items_table()->get_item_template_data( $item );
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_totals_template() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR\Tables\ItemsTable->get_totals_template' );
		return $this->items_table()->get_totals_template();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_totals_template_data() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR\Tables\ItemsTable->get_totals_template_data' );
		return $this->items_table()->get_totals_template_data();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_items_count_progressbar() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR\Tables\ItemsTable->get_items_count_progressbar' );
		return $this->items_table()->get_items_count_progressbar();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_items_count_progressbar_data() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR\Tables\ItemsTable->get_items_count_progressbar_data' );
		return $this->items_table()->get_items_count_progressbar_data();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_table() {
		_deprecated_function( __METHOD__, '4.11' );
		$table = $this->items_table();
		$table->set_rows_object( $this->get_items() );
		return $table->get_table();
	}

	/**
	 * @deprecated since version 4.11
	 * @return ItemsTable
	 */
	public function items_table() {
		if ( !$this->items_table ) {
			$this->items_table = new ItemsTable( $this->wishlist );
		}
		return $this->items_table;
	}

	/**
	 * @deprecated since version 4.11
	 * @return ItemFields
	 */
	public function get_item_fields() {
		if ( !$this->item_fields ) {
			$this->item_fields = new ItemFields( $this->items_table() );
			$this->item_fields->filter_showing();
		}
		return $this->item_fields;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_item_fields_data() {
		return $this->items_table()->get_data();
	}

}
