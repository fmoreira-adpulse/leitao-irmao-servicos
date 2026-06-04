<?php

namespace NMGR\Tables;

use NMGR\Tables\Table;
use NMGR\Fields\ItemFields;
use NMGR\Fields\ItemsTotalsFields;
use NMGR\Fields\ItemActionsFields;
use NMGR\Fields\ItemStatusesFields;
use NMGR\Tables\ItemsTotalsTable;

class ItemsTable extends Table {

	protected $id = 'items';
	protected $display;
	protected $is_grid = false;
	protected $item_statuses = [];
	public $wishlist;
	public $is_gift_registry = true;
	public $is_admin;
	public $is_public;

	/**
	 * @var \NMGR_Items_View|\NMGR\Sub\Items_View
	 */
	public $view;

	public function __construct( \NMGR_Wishlist $wishlist ) {
		$this->wishlist = $wishlist;
		$this->is_admin = is_nmgr_admin();
		$this->is_public = false === ($this->is_admin || nmgr_user_has_wishlist( $this->wishlist ));
		$this->is_gift_registry = false === $this->wishlist->is_type( 'wishlist' );

		$this->view = nmgr()->items_view( $wishlist );

		$item_fields = new ItemFields( $this );
		$item_fields->filter_showing();

		$this->set_data( $item_fields->get_data() );
	}

	protected function get_items_count() {
		return $this->wishlist->get_items_count();
	}

	public function get_orderby_key( $part ) {
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
	 * Utility function to get all the current items in the view.
	 * This function is necessary because there is public method to get the $rows_object property.
	 * Used in place of \NMGR_Items_View::get_items() for legacy reasons.
	 * @todo Remove in later version, maybe 5.0.0
	 */
	public function get_items() {
		return $this->rows_object;
	}

	protected function rows_object() {
		$args = $this->pagination_args;

		if ( $this->order ) {
			$args[ 'order' ] = $this->order;
		}

		if ( $this->orderby ) {
			$args[ 'orderby' ] = $this->get_orderby_key( $this->orderby );
		}

		return $this->wishlist->read_items( $args );
	}

	public function get_items_count_progressbar() {
		ob_start();
		$item_count = $this->wishlist ? $this->wishlist->get_items_quantity_count() : 0;

		if ( $item_count && !$this->is_public && $this->is_gift_registry && is_nmgr_wishlist() ) :
			?>
			<div class="nmgr-items-count-progress">
				<div class="nmgr-progressbar-wrapper">
					<?php
					$item_purchased_count = $this->wishlist->get_items_purchased_quantity_count();
					$title_attribute = sprintf(
						/* translators: 1: item quantity purchased, 2: item quantity in wishlist */
						nmgr()->is_pro ? __( '%1$s of %2$s items purchased.', 'nm-gift-registry' ) : __( '%1$s of %2$s items purchased.', 'nm-gift-registry-lite' ),
						$item_purchased_count,
						$item_count
					);
					echo wp_kses( nmgr_progressbar( $item_count, $item_purchased_count, $title_attribute ),
						array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() ) );
					?>
				</div>
				<div class="nmgr-item-count">
					<?php
					printf(
						/* translators: %d: item count */
						nmgr()->is_pro ? _nx( '%d item', '%d items', $item_count, 'wishlist item count', 'nm-gift-registry' ) : _nx( '%d item', '%d items', $item_count, 'wishlist item count', 'nm-gift-registry-lite' ),
						$item_count
					);
					?>
				</div>
			</div>
			<?php
		endif;
		return ob_get_clean();
	}

	public function get_items_count_progressbar_data() {
		$template = $this->get_items_count_progressbar();
		return $template ? [ '.nmgr-items-count-progress' => $template ] : [];
	}

	public function set_row_object( $object ) {
		parent::set_row_object( $object );

		/**
		 * Hack to set item in view class when we set row object here.
		 * @todo Remove in later version, maybe 5.0.0
		 */
		$this->view->item = $object;

		$this->item_statuses = (new ItemStatusesFields( $this ) )->get_data();
	}

	public function get_row() {
		if ( !$this->item_is_disabled() ) {
			return parent::get_row();
		}
	}

	public function get_cell( $key ) {
		return apply_filters_deprecated( 'nmgr_item_part_content', [ parent::get_cell( $key ), $key, $this->view ], '4.6.0' );
	}

	/**
	 *
	 * @param this $table
	 * @return type
	 */
	protected function get_cell_value() {
		$fnc = "get_item_{$this->cell_key}";
		$part_data = $this->get_data()[ $this->cell_key ];
		$content = $part_data[ 'content' ] ?? false;

		if ( false !== $content ) {
			if ( is_callable( $content ) ) {
				/**
				 * This try catch block is only present to catch the fatal error resulting
				 * from the use of $this->view, which is deprecated, as the argument for the user function.
				 * @todo Remove in version 5.0.0
				 */
				try {
					$content = call_user_func( $content, $this );
				} catch ( \Error $exc ) {
					_deprecated_argument( '', '4.11', 'Argument \NMGR_Items_View class has been replaced with \NMGR\Tables\ItemsTable. Error message: ' . $exc->getMessage() . ' ' . $exc->getTraceAsString() );
					$content = call_user_func( $content, $this->view );
				}
			}
		} elseif ( is_callable( [ $this, $fnc ] ) ) {
			$content = $this->$fnc();
		}

		return $content;
	}

	protected function get_table_attributes() {
		$attributes = parent::get_table_attributes();

		$attributes[ 'class' ][] = 'nmgr-items-view';
		$attributes[ 'class' ][] = $this->display;

		if ( !$this->is_grid ) {
			$attributes[ 'class' ][] = 'nmgr-items-table';
		}

		$filtered = apply_filters_deprecated( 'nmgr_items_view_attributes', [ $attributes, $this->view ], '4.11', 'nmgr_items_table_attributes' );
		return apply_filters( 'nmgr_items_table_attributes', $filtered, $this );
	}

	protected function get_header_attributes( $key ) {
		$attributes = parent::get_header_attributes( $key );
		$part_data = $this->get_data()[ $key ];

		/**
		 * Keep 'item_' . $key legacy class
		 * This class has been replaced with 'nmgr_' . $key for consistency with classes on table cells.
		 * It is still used in nm-gift-registry-sortable plugin, So keep till major update
		 * @todo Remove in later version
		 * @since 4.6.0
		 */
		$attributes[ 'class' ][] = 'item_' . $key;

		if ( $part_data[ 'orderby' ] ?? false ) {
			$orderby_key = $this->get_orderby_key( $key );
			$attributes[ 'data-orderby' ] = $orderby_key;
			$attributes[ 'class' ][] = 'orderby';

			if ( $orderby_key === ($this->get_orderby_key( $this->orderby ?? ''  )) ) {
				// 'desc' is default items order
				$attributes[ 'class' ][] = strtolower( $this->order ?? 'desc' );
			}
		}

		/**
		 * @todo Maybe deprecate 'table_header_attributes' as not used in main plugin
		 * @since version 4.6.0
		 * only used in nm-gift-registry-sortable
		 */
		if ( !empty( $part_data[ 'table_header_attributes' ] ) ) {
			$attributes = nmgr_merge_args( $attributes, $part_data[ 'table_header_attributes' ] );
		}

		return $attributes;
	}

	protected function get_row_attributes() {
		$attributes = parent::get_row_attributes();

		$item = $this->get_row_object();
		$classes = array_merge( [ 'item' ], array_keys( $this->item_statuses ) );

		$attributes[ 'id' ] = $this->get_item_id();
		$attributes[ 'class' ] = nmgr_merge_args( ($attributes[ 'class' ] ?? [] ), $classes );
		$attributes[ 'data-product_title' ] = $item->get_product_name();
		$attributes[ 'data-wishlist_item_id' ] = $item->get_id();
		$attributes[ 'data-wishlist_id' ] = $item->get_wishlist_id();

		$filtered = apply_filters_deprecated( 'nmgr_item_view_attributes', [ $attributes, $this->view ], '4.11', 'nmgr_item_row_attributes' );
		return apply_filters( 'nmgr_item_row_attributes', $filtered, $this );
	}

	protected function get_cell_attributes( $key ) {
		$attributes = parent::get_cell_attributes( $key );
		$attributes[ 'class' ][] = 'nmgr-item-part';

		if ( 'action_buttons' !== $key ) {
			foreach ( $this->item_statuses as $status ) {
				if ( true === ($status[ 'blur' ] ?? null) ) {
					$attributes[ 'class' ][] = 'nmgr-blur';
					break;
				}
			}
		}

		$part_data = $this->get_data()[ $key ];
		/**
		 * @todo Maybe deprecate 'content_container_attributes' as not used in main plugin
		 * only used in nm-gift-registry-sortable
		 * @since version 4.6.0
		 */
		if ( !empty( $part_data[ 'content_container_attributes' ] ) ) {
			$attributes = nmgr_merge_args( $attributes, $part_data[ 'content_container_attributes' ] );
		}

		$filtered = apply_filters_deprecated( 'nmgr_item_part_attributes', [ $attributes, $key, $this->view ], '4.11', 'nmgr_item_cell_attributes' );
		return apply_filters( 'nmgr_item_cell_attributes', $filtered, $key, $this );
	}

	public function get_item_thumbnail() {
		$item = $this->get_row_object();
		$size = $this->is_grid ? 'nmgr_medium' : 'nmgr_thumbnail';
		$deprecated = apply_filters_deprecated( 'nmgr_item_view_image_size', [ $size, $this->view ], '4.11', 'nmgr_item_image_size' );
		$image_size = apply_filters( 'nmgr_item_image_size', $deprecated, $this );
		$thumbnail = $item->get_product_image( $image_size );

		$file = 'account/items/item-thumbnail.php';
		if ( nmgr_overridden( $file ) ) {
			nmgr_overridden_notice( $file, '4.6.0' );
			return nmgr_get_template( $file, array(
				'thumbnail' => $thumbnail,
				'item' => $item,
				'view' => $this->view,
				) );
		}

		return '<div class="thumbnail">' . $thumbnail . '</div>';
	}

	public function get_item_title() {
		$item = $this->get_row_object();
		$product_link = $this->is_admin ?
			get_edit_post_link( $item->get_product_id() ) :
			$item->get_product_permalink();

		if ( $product_link && !$this->is_admin && $this->is_gift_registry &&
			empty( $this->get_item_statuses_for_disabling_add_to_cart() ) ) {
			$product_link = add_query_arg( 'nmgr_item_id', $item->get_id(), $product_link );
		}

		$args = array(
			'item' => $item,
			'product_link' => $product_link,
			'view' => $this->view,
		);

		ob_start();
		?>

		<div>
			<?php
			echo $product_link ?
				'<a href="' . esc_url( $product_link ) . '">' . wp_kses_post( $item->get_product_name() ) . '</a>' :
				wp_kses_post( $item->get_product_name() );

			do_action_deprecated( 'nmgr_item_view_after_title', [ $args ], '4.11', 'nmgr_item_after_title' );
			do_action( 'nmgr_item_after_title', $this );

			if ( $item->get_product_sku() ) {
				echo '<div class="sku meta-item"><strong>' .
				esc_html( nmgr()->is_pro ?
						__( 'SKU:', 'nm-gift-registry' ) :
						__( 'SKU:', 'nm-gift-registry-lite' )  ) .
				'</strong> ' . esc_html( $item->get_product_sku() ) . '</div>';
			}

			if ( !$this->is_public && $item->get_variation_id() ) {
				?>
				<div class="variation-id meta-item">
					<strong>
						<?php
						echo esc_html( nmgr()->is_pro ?
								__( 'Variation ID:', 'nm-gift-registry' ) :
								__( 'Variation ID:', 'nm-gift-registry-lite' )  );
						?>
					</strong>
					<?php echo esc_html( $item->get_variation_id() ); ?>
				</div>
				<?php
			}

			$variations = $item->get_variation();
			if ( !empty( $variations ) ) :
				$variations_for_display = nmgr_get_variations_for_display( $variations, $item->get_product_name() );
				if ( !empty( $variations_for_display ) ) :
					?>
					<ul class="variations meta-item">
						<?php foreach ( $variations_for_display as $variation ) :
							?>
							<li class="nmgr-item-variation <?php echo esc_attr( $variation[ 'key' ] ); ?>">
								<strong><?php echo wp_kses_post( $variation[ 'key' ] ); ?>:</strong>
								<?php echo wp_kses_post( force_balance_tags( $variation[ 'value' ] ) ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php
				endif;
			endif;

			do_action_deprecated( 'nmgr_item_view_title_end', [ $args ], '4.11', 'nmgr_item_title_end' );
			do_action( 'nmgr_item_title_end', $this );
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_item_cost() {
		ob_start();
		$item = $this->get_row_object();

		$cost_per_item_text = nmgr()->is_pro ?
			__( 'Cost per item', 'nm-gift-registry' ) :
			__( 'Cost per item', 'nm-gift-registry-lite' );
		?>

		<div class="nmgr-tip" title="<?php echo esc_attr( $cost_per_item_text ); ?>">
			<?php echo wp_kses_post( $item->get_cost( true ) ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_item_quantity() {
		$item = $this->get_row_object();
		$desired_quantity = $item->get_quantity();
		$purchased_quantity = $item->get_purchased_quantity();
		$has_quantity_mismatch = $desired_quantity < $purchased_quantity;
		$can_archive_item = method_exists( $item, 'is_archived' );
		$show_input = !$this->is_public &&
			(!$can_archive_item || ($can_archive_item && !$item->is_archived()) || $has_quantity_mismatch);

		$quantity = $this->is_gift_registry && $this->is_public ?
			$item->get_unpurchased_quantity() :
			$desired_quantity;


		$old_min = apply_filters_deprecated( 'nmgr_item_quantity_input_min', [ 1, $this->view ], '4.11', 'nmgr_item_input_min_quantity' );
		$min = apply_filters( 'nmgr_item_input_min_quantity', $old_min, $this );
		if ( has_filter( 'nmgr_quantity_input_min' ) ) {
			$product = $item->get_product();
			$min = apply_filters_deprecated( 'nmgr_quantity_input_min',
				[ $product->get_min_purchase_quantity(), $product ],
				'4.4.0',
				'nmgr_item_input_min_quantity'
			);
		}

		$old_max = apply_filters_deprecated( 'nmgr_item_quantity_input_max', [ '', $this->view ], '4.11', 'nmgr_item_input_max_quantity' );
		$max = apply_filters( 'nmgr_item_input_max_quantity', $old_max, $this );
		if ( has_filter( 'nmgr_quantity_input_max' ) ) {
			$product = $item->get_product();
			$val = ($product->backorders_allowed() ? '' : $product->get_stock_quantity() );
			$max = apply_filters_deprecated( 'nmgr_quantity_input_max',
				[ $val, $product ],
				'4.4.0',
				'nmgr_item_input_max_quantity'
			);
		}

		ob_start();
		?>
		<div>
			<div class="nmgr-tip" title="<?php
			echo esc_attr( nmgr()->is_pro ?
					__( 'Desired quantity', 'nm-gift-registry' ) :
					__( 'Desired quantity', 'nm-gift-registry-lite' )  );
			?>">
						 <?php
						 if ( nmgr()->is_pro && $this->is_grid ) {
							 echo nmgr_get_svg( array(
								 'icon' => 'cart-empty',
								 'size' => 1,
								 'fill' => '#bbb',
								 'class' => 'align-with-text',
							 ) ) . ' ';
						 }

						 if ( $show_input ) :
							 ?>
					<input type="number"
								 step="1"
								 placeholder="0"
								 autocomplete="off"
								 size="4"
								 class="quantity <?php echo $has_quantity_mismatch ? 'nmgr-settings-error' : ''; ?>"
								 value="<?php echo esc_attr( $quantity ); ?>"
								 data-item_id="<?php echo ( int ) $item->get_id(); ?>"
								 data-qty="<?php echo esc_attr( $quantity ); ?>"
								 name="wishlist_item_qty[<?php echo absint( $item->get_id() ); ?>]"
								 min="<?php echo esc_attr( $min ); ?>"
								 max="<?php echo esc_attr( $max ); ?>"
								 />
								 <?php
							 else :
								 echo ( int ) $quantity;
							 endif;
							 ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_item_purchased_quantity() {

		$item = $this->get_row_object();

		$file = 'account/items/item-purchased-quantity.php';
		if ( nmgr_overridden( $file ) ) {
			nmgr_overridden_notice( $file, '4.6.0' );
			return nmgr_get_template( $file, array(
				'item' => $item,
				'view' => $this->view,
				) );
		}

		ob_start();

		$pq_text = nmgr()->is_pro ?
			__( 'Purchased quantity', 'nm-gift-registry' ) :
			__( 'Purchased quantity', 'nm-gift-registry-lite' );
		?>

		<div>
			<div class="nmgr-tip" title="<?php echo esc_attr( $pq_text ); ?>">
				<?php
				if ( nmgr()->is_pro && $this->is_grid ) {
					echo nmgr_get_svg( array(
						'icon' => 'cart-full',
						'size' => 1,
						'fill' => '#bbb',
						'class' => 'align-with-text',
					) ) . ' ';
				}

				echo wp_kses_post( apply_filters( 'nmgr_item_purchased_quantity_display',
						$item->get_purchased_quantity(),
						$item
				) );
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_item_total_cost() {
		$item = $this->get_row_object();

		$file = 'account/items/item-total_cost.php';
		if ( nmgr_overridden( $file ) ) {
			nmgr_overridden_notice( $file, '4.6.0' );
			return nmgr_get_template( 'account/items/item-total_cost.php', array(
				'item' => $item,
				'view' => $this->view,
				) );
		}

		ob_start();
		?>
		<div>
			<div class="nmgr-tip" title="<?php
			echo esc_attr( nmgr()->is_pro ?
					__( 'Total cost', 'nm-gift-registry' ) :
					__( 'Total cost', 'nm-gift-registry-lite' )  );
			?>">
						 <?php echo $item->get_total( true ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_item_add_to_cart_button() {
		$type = $this->wishlist->get_type();
		$item = $this->get_row_object();

		// Get the maximum quantity of the item that can be added to the cart
		$max_purchase_qty = $item->get_product_stock_quantity();

		if ( 'gift-registry' === $type ) {
			$desired_qty = $item->get_unpurchased_quantity();

			if ( $max_purchase_qty > 0 ) {
				if ( $max_purchase_qty > $desired_qty ) {
					$max_qty = $desired_qty;
				} else {
					$max_qty = $max_purchase_qty;
				}
			} else {
				$max_qty = $desired_qty;
			}
		} else {
			$max_qty = $max_purchase_qty;
		}

		$button_classes = [
			'nmgr_add_to_cart_button',
			'button',
			'alt'
		];

		if ( nmgr()->is_pro && method_exists( $this, 'get_pro_add_to_cart_button_classes' ) ) {
			$button_classes = array_merge( $button_classes, $this->get_pro_add_to_cart_button_classes( $type ) );
		} else {
			$button_classes[] = 'nmgr_ajax_add_to_cart';
		}

		ob_start();
		?>
		<div>
			<?php
			do_action( 'nmgr_item_before_add_to_cart_form', $item );

			$item_statuses = $this->get_item_statuses_for_disabling_add_to_cart();

			if ( !empty( $item_statuses ) ) :
				foreach ( $item_statuses as $status_key => $status_args ) {
					$attributes = nmgr_utils_format_attributes( $status_args[ 'add_to_cart_column_attributes' ] ?? [] );
					?>
					<div class="<?php echo esc_attr( $status_key ); ?>">
						<span <?php echo $attributes; ?>>
							<?php echo wp_kses_post( $status_args[ 'label' ], nmgr_allowed_post_tags() ); ?>
						</span>
					</div>
					<?php
				}

			else:

				$args = array(
					'item' => $item,
					'max_qty' => $max_qty,
					'button_classes' => $button_classes,
					'table' => $this,
					'view' => $this->view,
				);

				$old_form = apply_filters_deprecated( 'nmgr_add_to_cart_form', [ null, $args ], '4.11', 'nmgr_item_add_to_cart_form_html' );
				$add_to_cart_form = apply_filters( 'nmgr_item_add_to_cart_form_html', $old_form, $this );

				if ( $add_to_cart_form ) :

					echo $add_to_cart_form;

				else :
					?>

					<form class="cart nmgr-add-to-cart-form"
								action="<?php the_permalink(); ?>"
								method="post"
								enctype='multipart/form-data'>
						<input type="hidden" name="nmgr-add-to-cart-product-id"
									 value="<?php echo ( int ) $item->get_product_id(); ?>" />
						<input type="hidden" name="nmgr-add-to-cart-wishlist-item"
									 value="<?php echo absint( $item->get_id() ); ?>" />
						<input type="hidden" name="nmgr-add-to-cart-wishlist"
									 value="<?php echo absint( $item->get_wishlist_id() ); ?>" />

						<?php if ( $item->get_variation_id() ) : ?>
							<input type="hidden" name="variation_id"
										 value="<?php echo absint( $item->get_variation_id() ); ?>" />
										 <?php
										 if ( !empty( $item->get_variation() ) ) :
											 foreach ( $item->get_variation() as $attribute_key => $value ) :
												 ?>
									<input type="hidden" name="<?php echo esc_attr( $attribute_key ); ?>"
												 value="<?php echo esc_attr( $value ); ?>" />
												 <?php
											 endforeach;
										 endif;
									 endif;
									 ?>

						<?php do_action( 'nmgr_item_add_to_cart_form', $item ); ?>

						<div class="quantity">
							<input type="<?php echo ( int ) $max_qty === 1 ? 'hidden' : 'number'; ?>"
										 step="1"
										 autocomplete="off"
										 size="4"
										 class="input-text qty text"
										 value="1"
										 name="quantity"
										 min="1"
										 max="<?php echo esc_attr( $max_qty ); ?>"
										 autocomplete="off"
										 />
						</div>

						<button type="submit"
										class="<?php echo implode( ' ', array_filter( array_unique( $button_classes ) ) ); ?>"
										data-product_id="<?php echo absint( $item->get_product_or_variation_id() ); ?>"
										data-wishlist_item_id="<?php echo absint( $item->get_id() ); ?>"
										data-wishlist_id="<?php echo absint( $item->get_wishlist_id() ); ?>">
											<?php echo esc_html( $this->add_to_cart_text() ); ?>
						</button>
					</form>

				<?php
				endif;
			endif;
			do_action( 'nmgr_item_after_add_to_cart_form', $item );
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_item_action_buttons() {
		$item = $this->get_row_object();

		$args = array(
			'dropdown' => $this->get_item_dropdown(),
			'item' => $item,
			'items_args' => $this->get_args(),
			'view' => $this->view,
		);

		$file = 'account/items/item-actions-edit-delete.php';
		if ( nmgr_overridden( $file ) ) {
			nmgr_overridden_notice( $file, '4.6.0' );
			return nmgr_get_template( $file, $args );
		}

		ob_start();
		?>
		<div>
			<div class="edit-delete-wrapper">
				<?php
				echo $args[ 'dropdown' ];

				do_action_deprecated( 'nmgr_item_view_actions_edit_delete', [ $item, $this->get_args() ], '3.0.0' );
				?>

			</div>
			<?php do_action_deprecated( 'nmgr_item_view_actions', [ $item, $this->get_args(), $args ], '4.11' ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * This function is used only in NMGR_Items_View class
	 * @access private
	 * @todo Maybe remove in version 5.0.0
	 */
	public function get_item_statuses() {
		return $this->item_statuses;
	}

	public function get_item_statuses_for_disabling_add_to_cart() {
		return array_filter( $this->item_statuses, function ( $status ) {
			return true === ($status[ 'show_in_add_to_cart_column' ] ?? null);
		} );
	}

	/**
	 * Get all the actions that should be performed on the specific item in the view
	 * @param boolean $all Whether to get all the actions without any conditions of display. Default false.
	 * @return array
	 */
	public function get_item_actions( $all = false ) {
		if ( $this->is_gift_registry &&
			is_callable( [ $this->wishlist, 'is_archived' ] ) &&
			$this->wishlist->is_archived() ) {
			return [];
		}

		$fields = new ItemActionsFields( $this );

		if ( !$all ) {
			$fields->filter_showing();
		}

		return $fields->get_data();
	}

	public function get_item_dropdown() {
		$dropdown = nmgr_get_dropdown();

		foreach ( $this->get_item_actions() as $action_args ) {
			if ( is_array( $action_args ) ) {
				$dropdown->set_menu_item(
					($action_args[ 'text' ] ?? '' ),
					($action_args[ 'attributes' ] ?? [] ),
					($action_args[ 'tag' ] ?? 'a' )
				);
			}
		}

		$show_notices_statuses = array_filter( $this->item_statuses, function ( $status ) {
			return true === ($status[ 'show_notice' ] ?? null);
		} );

		if ( !empty( $show_notices_statuses ) ) {
			if ( $dropdown->has_items() ) {
				$dropdown->set_menu_divider();
			}

			$statuses_text = nmgr()->is_pro ?
				__( 'Item statuses', 'nm-gift-registry' ) :
				__( 'Item statuses', 'nm-gift-registry-lite' );

			$dropdown->set_menu_header( $statuses_text );

			foreach ( $show_notices_statuses as $status ) {
				$dropdown->set_menu_text( $status[ 'label' ] ?? null  );
			}
		}

		return $dropdown->has_items() ? $dropdown->get() : '';
	}

	public function get_delete_item_notice() {
		$item = $this->get_row_object();

		return apply_filters( 'nmgr_delete_item_notice', sprintf(
				/* translators: %s: wishlist type title */
				nmgr()->is_pro ? __( 'Are you sure you want to remove the %s item?', 'nm-gift-registry' ) : __( 'Are you sure you want to remove the %s item?', 'nm-gift-registry-lite' ),
				nmgr_get_type_title( '', false, $this->wishlist->get_type() )
			), $item );
	}

	public function get_item_id() {
		return $this->get_row_object() ? 'nmgr_item_' . ( int ) $this->get_row_object()->get_id() : 0;
	}

	/**
	 * Whether the item should be hidden from view
	 * (All items in the wishlist are shown by default)
	 * @return boolean
	 */
	public function item_is_disabled() {
		/**
		 * Don't show the item if we are on the frontend single wishlist page
		 * and it is fulfilled and should be hidden
		 */
		$item = $this->get_row_object();
		$disabled = false;

		if ( $this->is_gift_registry && $this->is_public ) {
			$disabled = ($item->is_fulfilled() &&
				nmgr_get_type_option( $this->wishlist->get_type(), 'hide_fulfilled_items' )) ||
				(method_exists( $item, 'is_archived' ) &&
				$item->is_archived() &&
				!$item->is_fulfilled());
		}
		return apply_filters_deprecated( 'nmgr_item_view_is_disabled', [ $disabled, $this->view ], '4.11' );
	}

	protected function get_template_before_table() {
		return $this->get_items_count_progressbar();
	}

	protected function get_template_after_table() {
		ob_start();
		$action_args = [ $this->rows_object, $this->wishlist, $this->get_args(), $this->view ];
		$this->wishlist ? do_action_deprecated( 'nmgr_after_items_table_body', $action_args, '4.6.0' ) : '';
		$template = ob_get_clean();

		$template .= $this->get_totals_template();
		$template .= $this->get_table_actions();

		return $template;
	}

	public function get_template() {
		return $this->get_template_before_table() . $this->get_table() . $this->get_template_after_table();
	}

	protected function get_table_actions() {
		$file = 'account/items/items-actions.php';
		if ( nmgr_overridden( $file ) ) {
			nmgr_overridden_notice( $file, '4.6.0' );

			return nmgr_get_template( 'account/items/items-actions.php',
				array(
					'items' => $this->rows_object,
					'wishlist' => $this->wishlist,
					'items_args' => $this->args,
					'view' => $this->view,
				) );
		}
		ob_start();
		?>
		<div class="nmgr-after-table-row items-actions">
			<p>
				<?php
				if ( $this->is_admin ) {
					echo '<button type="button" '
					. 'data-nmgr_post_action="show_add_items_dialog" '
					. 'class="button nmgr-post-action">'
					. esc_html( nmgr()->is_pro ?
							__( 'Add item(s)', 'nm-gift-registry' ) :
							__( 'Add item(s)', 'nm-gift-registry-lite' )
					)
					. '</button>';
				}

				do_action_deprecated( 'nmgr_after_items_actions',
					[ $this->rows_object, $this->wishlist, $this->args, $this->view ],
					'4.11'
				);
				?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	public function add_to_cart_text() {
		$text = nmgr()->is_pro ?
			__( 'Add to cart', 'nm-gift-registry' ) :
			__( 'Add to cart', 'nm-gift-registry-lite' );
		return apply_filters( 'nmgr_add_to_cart_text', $text, $this );
	}

	public function get_totals_template() {
		if ( $this->wishlist->is_type( 'gift-registry' ) &&
			in_array( 'total_cost', array_keys( $this->get_data() ) ) &&
			!$this->is_public ) {

			$fields = new ItemsTotalsFields( $this );

			$file = 'account/items/items-total-cost.php';
			if ( nmgr_overridden( $file ) ) {
				nmgr_overridden_notice( $file, '4.6.0' );
				return nmgr_get_template( $file,
					array(
						'wishlist' => $this->wishlist,
						'rows' => $fields->get_data()
					)
				);
			}

			ob_start();
			?>
			<div class="nmgr-after-table-row items-total-cost">
				<?php (new ItemsTotalsTable( $fields ) )->show(); ?>
			</div>
			<?php
			return ob_get_clean();
		}
	}

	public function get_totals_template_data() {
		$template = $this->get_totals_template();
		return $template ? [ '.items-total-cost' => $template ] : [];
	}

	public function get_item_template_data( $item ) {
		$this->set_row_object( $item );
		return [ '#' . $this->get_item_id() => $this->get_row() ];
	}

}
