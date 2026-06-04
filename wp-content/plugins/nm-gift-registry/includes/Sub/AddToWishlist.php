<?php

namespace NMGR\Sub;

use NMGR\Lib\AddToWishlist as BaseClass;

defined( 'ABSPATH' ) || exit;

class AddToWishlist extends BaseClass {

	public function get_dialog_args( $section, $args = [] ) {
		$parent_args = parent::get_dialog_args( $section, $args );

		if ( 'select-wishlist' === $section ) {
			$in_wishlist_options = [];

			// hellet the values of the product's options in each of the user's wishlists
			foreach ( array_filter( $parent_args[ 'products' ] ) as $product ) {
				foreach ( $parent_args[ 'wishlists' ] as $wishlist ) {
					$item = $wishlist->get_item_by_product( $product );
					$fav = $item ? $item->get_favourite() : '';
					$qty = $item ? $item->get_quantity() : 0;
					$in_wishlist_options[ 'nmgr_fav' ][ $product->get_id() ][] = 'data-in-wishlist-' . $wishlist->get_id() . '="' . esc_attr( $fav ) . '"';
					$in_wishlist_options[ 'qty' ][ $product->get_id() ][] = 'data-in-wishlist-' . $wishlist->get_id() . '="' . esc_attr( $qty ) . '"';
				}
			}

			foreach ( $in_wishlist_options as $key => $product_options ) {
				foreach ( $product_options as $product_id => $raw_options ) {
					$parent_args[ 'wishlist_item_options' ][ $key ][ $product_id ] = implode( ' ', $raw_options );
				}
			}
		}

		return $parent_args;
	}

	protected function columns_data( $args ) {
		$parent_cols = parent::columns_data( $args );
		$type = $args[ 'data' ][ 'type' ] ?? null;

		$cols = [
			'quantity_icon' => [
				'show' => 'gift-registry' === $type,
				'priority' => 10,
			],
			'favourite' => [
				'show' => nmgr_get_type_option( $type, 'display_item_favourite' ),
				'priority' => 20,
			],
		];

		return array_merge( $parent_cols, $cols );
	}

	/**
	 * @param string $key
	 * @param Table $table
	 * @return string
	 */
	public function column_value( $table ) {
		$value = parent::column_value( $table );

		if ( $value ) {
			return $value;
		}

		ob_start();

		$key = $table->get_cell_key();
		$product = $table->get_row_object();
		$args = $table->get_args();
		$wishlist_item_options = $args[ 'wishlist_item_options' ];

		switch ( $key ) {
			case 'favourite':
				?>
				<div class="favourite">
					<?php
					$field_id = esc_attr( uniqid( "nmgr_fav_{$product->get_id()}_" ) );
					$data_title_off = __( 'Mark as favourite', 'nm-gift-registry' );
					$data_title_on = sprintf(
						/* translators: %s: wishlist type title */
						esc_attr__( 'This item is a favourite in this %s', 'nm-gift-registry' ), nmgr_get_type_title()
					);
					?>
					<div class="nmgr-btn-group">
						<div class="nmgr-btn">
							<input id="<?php echo $field_id; ?>" type="checkbox" value="1"
										 name="<?php echo $this->get_input_name_for_product( 'favourite', $product->get_id() ); ?>"
										 <?php
										 if ( isset( $wishlist_item_options[ 'nmgr_fav' ][ $product->get_id() ] ) ) {
											 echo $wishlist_item_options[ 'nmgr_fav' ][ $product->get_id() ];
										 }
										 ?>>
							<label for="<?php echo $field_id; ?>"
										 class="icon"
										 data-title-on="<?php echo esc_attr( $data_title_on ); ?>"
										 data-title-off="<?php echo esc_attr( $data_title_off ); ?>"
										 title="<?php echo esc_attr( $data_title_off ); ?>">
								<span class='nmgr-icon'>&bigstar;</span>
							</label>
						</div>
					</div>
				</div>
				<?php
				break;

			case 'quantity_icon':
				$data_title_on = sprintf(
					/* translators: %s: wishlist type title */
					__( 'Quantity already in this %s', 'nm-gift-registry' ),
					nmgr_get_type_title()
				);
				?>
				<div class="nmgr-cart-qty-wrapper nmgr-tip"
						 title="<?php echo $data_title_on; ?>"
						 <?php echo $wishlist_item_options[ 'qty' ][ $product->get_id() ]; ?>>
					<span class="nmgr-badge nmgr-qty"></span>
				</div>
				<?php
				break;
		}

		return ob_get_clean();
	}

}
