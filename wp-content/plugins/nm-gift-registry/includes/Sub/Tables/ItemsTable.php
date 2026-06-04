<?php

namespace NMGR\Sub\Tables;

use NMGR\Tables\ItemsTable as BaseTable;

defined( 'ABSPATH' ) || exit;

class ItemsTable extends BaseTable {

	protected $items_per_page = 12;

	public function __construct( $wishlist ) {
		parent::__construct( $wishlist );

		$display = is_a( wc()->session, 'WC_Session' ) ?
			wc()->session->get( 'nmgr_items_display_mode' ) : null;
		$this->display = $display ?? 'list';

		$this->is_grid = 'grid' === $this->display;

		if ( $this->is_grid ) {
			$this->cell_tag = 'div';
			$this->row_tag = 'div';
			$this->body_tag = '';
			$this->header_tag = 'div';
			$this->head_tag = 'div';
			$this->table_tag = 'div';
		}
	}

	protected function get_head() {
		return $this->is_grid ? '' : parent::get_head();
	}

	protected function get_table_attributes() {
		$attributes = parent::get_table_attributes();

		if ( $this->is_grid ) {
			if ( !empty( $attributes[ 'class' ] ) &&
				is_array( $attributes[ 'class' ] ) &&
				in_array( 'nmgr-table', $attributes[ 'class' ] ) ) {
				$key = array_search( 'nmgr-table', $attributes[ 'class' ] );
				unset( $attributes[ 'class' ][ $key ] );
			}
		}

		return $attributes;
	}

	public function get_item_checkbox() {
		ob_start();

		$item = $this->get_row_object();
		$hide = $item->is_fulfilled() || $item->is_archived();

		if ( !$hide ) :
			?>
			<input type="checkbox" name="nmgr_select[]" value="<?php echo ( int ) $item->get_id(); ?>" class="nmgr-select">
			<?php
		endif;
		return ob_get_clean();
	}

	public function get_item_favourite() {
		$item = $this->get_row_object();

		$file = 'account/items/item-favourite.php';
		if ( nmgr_overridden( $file ) ) {
			nmgr_overridden_notice( $file, '4.6.0' );
			return nmgr_get_template( 'account/items/item-favourite.php', array(
				'item' => $item,
				'view' => $this->view,
				) );
		}

		ob_start();
		?>
		<div>
			<?php
			$icon = $item->is_favourite() ? 'star-full' : 'star-empty';
			$icon_title = $item->is_favourite() ?
				__( 'Favourite', 'nm-gift-registry' ) :
				__( 'Not favourite', 'nm-gift-registry' );

			echo nmgr_get_svg( array(
				'icon' => $icon,
				'fill' => 'currentColor',
				'title' => $icon_title,
			) );
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	protected function get_bulk_actions() {
		if ( 'gift-registry' === $this->wishlist->get_type() &&
			!in_array( 'checkbox', array_keys( $this->get_data() ) ) ) {
			return;
		}

		$actions = [];

		if ( !$this->is_public ) {
			$actions[] = 'dropdown_menu';
		}

		if ( !$this->is_admin ) {
			$actions[] = 'add_to_cart';
		}

		if ( !empty( $actions ) ) {
			$actions = array_merge( [ 'select-all' ], $actions );
		}

		$bulk_actions = ( array ) apply_filters( 'nmgr_items_bulk_actions',
				$actions, $this->wishlist, $this->get_args()
		);

		if ( empty( $bulk_actions ) ) {
			return;
		}

		$template = '<div class="nmgr-items-bulk-actions">';

		foreach ( $bulk_actions as $action ) {
			switch ( $action ) {
				case 'select-all':
					$template .= $this->get_select_all_checkbox();
					break;
				case 'dropdown_menu':
					$dropdown = nmgr_get_dropdown();
					$dropdown->add_container_class( [ 'nmgr-action', 'nmgr-toggle' ] );

					foreach ( $this->get_item_actions( true ) as $action_args ) {
						if ( is_array( $action_args ) && ($action_args[ 'show_in_bulk_actions' ] ?? false) ) {
							$attributes = array_merge(
								($action_args[ 'attributes' ] ?? [] ),
								[ 'data-nmgr_post_mode' => 'bulk' ]
							);
							$dropdown->set_menu_item(
								($action_args[ 'text' ] ?? '' ),
								$attributes,
								($action_args[ 'tag' ] ?? 'a' )
							);
						}
					}

					$template .= $dropdown->has_items() ? $dropdown->get() : '';
					break;

				case 'add_to_cart':
					$classes = array(
						'nmgr-action',
						'nmgr-toggle',
						'button',
						'nmgr-tip'
					);
					$classes[] = $this->wishlist->needs_shipping_address() ? 'disabled' : 'nmgr-bulk-add-to-cart';
					$classes[] = nmgr_get_type_option( $this->wishlist->get_type(), 'ajax_add_to_cart' ) ? 'nmgr_ajax_add_to_cart' : '';
					$classes_string = implode( ' ', array_filter( array_unique( $classes ) ) );

					$template .= '<div class="' . $classes_string . '" tabindex="0" title="' . __( 'Add selected items to cart', 'nm-gift-registry' ) . '">' .
						nmgr_get_svg( array(
							'icon' => 'cart-full',
							'class' => 'align-with-text',
							'style' => 'margin-right:5px;',
							'size' => '1.25em',
						) ) . $this->add_to_cart_text() . '</div>';
					break;
			}
		}
		$template .= '</div>';

		return $template;
	}

	public function get_display_modes_toggle() {
		if ( $this->is_admin ) {
			return;
		}

		$string = "<div class='nmgr-display-modes {$this->id}'>";

		foreach ( [ 'list', 'grid' ] as $val ) {
			$class = [
				'mode',
				$val,
				($val === $this->display ? 'active' : '')
			];

			$string .= sprintf( '<a data-mode="%s" class="%s" href="#">%s</a>',
				$val,
				implode( ' ', $class ),
				nmgr_get_svg( array(
				'icon' => $val,
				'sprite' => false,
				) )
			);
		}

		$string .= '</div>';

		return $string;
	}

	protected function get_select_all_checkbox() {
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

	protected function get_pro_add_to_cart_button_classes( $type ) {
		$classes = [];
		if ( 'gift-registry' === $type ) {
			$wishlist_in_cart = nmgr_get_wishlist_in_cart();
			$prevent_multiple_wishlists = ( bool ) (nmgr_get_option( 'cart_prevent_multiple_wishlists' ) &&
				$wishlist_in_cart &&
				$this->wishlist->get_id() !== absint( $wishlist_in_cart )
				);

			$classes[] = nmgr_restrict_wishlist_items_from_cart() ? 'restricted' : '';
			$classes[] = $prevent_multiple_wishlists ? 'prevent_multiple_wishlists' : '';
			$classes[] = nmgr_restrict_wishlist_items_from_cart() || $prevent_multiple_wishlists ? 'disabled' : '';
		}

		$classes[] = nmgr_get_type_option( $type, 'ajax_add_to_cart' ) ? 'nmgr_ajax_add_to_cart' : '';
		return $classes;
	}

	protected function get_template_before_table() {
		$template = '';

		if ( $this->wishlist && $this->wishlist->get_items_quantity_count() ) {
			$template .= '<div class="nmgr-items-menu">';
			$template .= $this->get_bulk_actions();
			$template .= $this->get_nav( 'top' );
			$template .= $this->get_display_modes_toggle();
			$template .= '</div>';
		}

		return $template . parent::get_template_before_table();
	}

	public function get_template_after_table() {
		return $this->get_nav( 'bottom' ) . parent::get_template_after_table();
	}

}
