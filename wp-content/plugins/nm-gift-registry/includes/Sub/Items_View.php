<?php

namespace NMGR\Sub;

use NMGR\Sub\Tables\ItemsTable;

defined( 'ABSPATH' ) || exit;

class Items_View extends \NMGR_Items_View {

	/**
	 * Get the display properties for the view.
	 *
	 * In the Pro version, priority is given to properties in the $_REQUEST array,
	 * then in the wc()->session array, and finally the defaults.
	 *
	 * Default properties are:
	 *
	 * - mode {string} The display mode. Values are 'list' or 'grid'. Default is 'list'.
	 *   'grid' only works on pro version
	 * - columns {int} The number of columns per row if display mode is grid. Default 4.
	 * - row-gap {string} The gap between rows of items if display mode is grid. Default 15px.
	 * - column-gap {string} The gap between items in a column if display mode is grid. Default 15px.
	 * - toggle_mode {boolean} Whether to allow the display mode to be changed. Default is false.
	 *   On the pro version, default is true on the wishlist page and false elsewhere.
	 *
	 * @deprecated since version 4.11
	 * @return array
	 */
	protected function display() {
		$session_mode = is_a( wc()->session, 'WC_Session' ) ? wc()->session->get( 'nmgr_items_display_mode' ) : null;

		return [
			'mode' => nmgr()->is_pro && is_nmgr_wishlist() ?
			($_REQUEST[ 'nmgr_items_display' ] ?? ($session_mode ?? 'list')) : 'list',
			'columns' => nmgr()->is_pro ? ($_REQUEST[ 'columns' ] ?? 4) : '',
			'row-gap' => nmgr()->is_pro ? ($_REQUEST[ 'row-gap' ] ?? '1.5625em') : '',
			'column-gap' => nmgr()->is_pro ? ( $_REQUEST[ 'column-gap' ] ?? '1.5625em') : '',
			'toggle_mode' => nmgr()->is_pro ? (is_nmgr_wishlist() ? true : false) : false,
		];
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_items_select_all_checkbox() {
		_deprecated_function( __METHOD__, '4.11' );
		ob_start();
		?>
		<div class="nmgr-action item_select">
			<label class="nmgr-tip" title="<?php esc_attr_e( 'Select all', 'nm-gift-registry' ); ?>">
				<input type="checkbox" class="nmgr-select-all" >
			</label>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the checkbox template
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_checkbox() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_checkbox()' );
		return $this->items_table()->get_item_checkbox();
	}

	/**
	 * Get the template for the item's favourite status
	 * @deprecated since version 4.6.0
	 * @return string Template html
	 */
	public function get_item_favourite() {
		_deprecated_function( __METHOD__, '4.11', 'NMGR_Items_View->items_table()->get_item_favourite()' );
		return $this->items_table()->get_item_favourite();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_pro_add_to_cart_button_classes( $type ) {
		_deprecated_function( __METHOD__, '4.11' );
		$classes = [];
		if ( 'gift-registry' === $type ) {
			$wishlist_in_cart = nmgr_get_wishlist_in_cart();
			$prevent_multiple_wishlists = ( bool ) (nmgr_get_option( 'cart_prevent_multiple_wishlists' ) &&
				$wishlist_in_cart &&
				$this->get_item()->get_wishlist_id() !== absint( $wishlist_in_cart )
				);

			$classes[] = nmgr_restrict_wishlist_items_from_cart() ? 'restricted' : '';
			$classes[] = $prevent_multiple_wishlists ? 'prevent_multiple_wishlists' : '';
			$classes[] = nmgr_restrict_wishlist_items_from_cart() || $prevent_multiple_wishlists ? 'disabled' : '';
		}

		$classes[] = nmgr_get_type_option( $type, 'ajax_add_to_cart' ) ? 'nmgr_ajax_add_to_cart' : '';
		return $classes;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function show_items_display_modes_toggle() {
		_deprecated_function( __METHOD__, '4.11' );
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function show_items_bulk_actions() {
		_deprecated_function( __METHOD__, '4.11' );
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

}
