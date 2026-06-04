<?php

namespace NMGR\Sub;

use NMGR\Sub\Tables\ItemsTable;
use NMGR\Sub\Tables\MessagesTable;
use NMGR\Sub\Tables\OrdersTable;
use NMGR\Sub\Fields\AccountFields;
use NMGR\Deprecated\Styles;

defined( 'ABSPATH' ) || exit;

class Account extends \NMGR_Account {

	protected function get_sections() {
		return (new AccountFields( $this ) )->get_data();
	}

	protected function items_table() {
		return ItemsTable::class;
	}

	protected function get_images_section() {
		$type = $this->get_type();
		$show_background = 'no' !== nmgr_get_type_option( $type, 'display_image_background' );
		$show_featured = 'no' !== nmgr_get_type_option( $type, 'display_image_thumbnail' );
		$wishlist = $this->wishlist;

		if ( !$wishlist || !is_nmgr_enabled( $type ) || (!$show_background && !$show_featured) ) {
			return;
		}

		$class = [];
		$class[] = $show_background ? 'show-bg' : '';
		$class[] = 'circle' === nmgr_get_type_option( $type, 'display_image_thumbnail' ) ? 'feat-circle' : '';
		$class[] = 'center' === nmgr_get_type_option( $type, 'display_image_thumbnail_position' ) ? 'feat-center' : '';
		$class[] = 'right' === nmgr_get_type_option( $type, 'display_image_thumbnail_position' ) ? 'feat-right' : '';
		$class[] = 'left' === nmgr_get_type_option( $type, 'display_image_thumbnail_position' ) ? 'feat-left' : '';

		$overridden_file = $this->is_overridden();
		if ( $overridden_file ) {
			nmgr_overridden_notice( $overridden_file, '4.6.0' );

			$vars = $this->get_default_section_vars();

			$vars[ 'attributes' ] = nmgr_merge_args(
				$vars[ 'attributes' ] ?? [],
				[ 'class' => array_filter( $class ) ]
			);
			$vars2 = nmgr_merge_args( $vars, array(
				'images_args' => array( 'editable' => true ),
				'show_background' => $show_background,
				'show_featured' => $show_featured,
				) );

			return $this->get_template( $vars2 );
		} else {
			ob_start();

			$this->section_attributes = nmgr_merge_args( $this->section_attributes,
				[ 'class' => array_filter( $class ) ]
			);

			if ( $show_background ) :
				?>
				<div class="background-image-wrapper">
					<div class="nmgr-bg nmgr-wrapper"
							 style="<?php
							 echo $wishlist->get_background_image_url() ?
								 'background-image:url(' . esc_url( $wishlist->get_background_image_url() ) . ');' : '';
							 ?>"
							 data-image_id="<?php echo absint( $wishlist->get_background_image_id() ); ?>"
							 data-context="background">
								 <?php do_action( 'nmgr_background_image_content', $wishlist ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_featured ) : ?>
				<div class="featured-image-wrapper">
					<div class="nmgr-thumbnail nmgr-wrapper"
							 data-image_id="<?php echo absint( $wishlist->get_thumbnail_id() ); ?>"
							 data-context="featured">
								 <?php do_action( 'nmgr_featured_image_content', $wishlist ); ?>
						<div class="preview">
							<?php echo $wishlist->get_thumbnail(); ?>
						</div>
					</div>
				</div>
				<?php
			endif;

			$content = ob_get_clean();

			return $this->get_new_template( $content );
		}
	}

	protected function get_messages_section() {
		if ( nmgr_get_type_option( $this->get_type(), 'enable_messages' ) ) {
			$overridden_file = $this->is_overridden();
			if ( $overridden_file ) {
				nmgr_overridden_notice( $overridden_file, '4.6.0' );
				$vars = $this->get_default_section_vars();
				$vars2 = nmgr_merge_args( $vars, array(
					'messages_args' => array( 'editable' => true ),
					'attributes' => [
						'class' => array(
							'editable',
						),
						'data-editable' => true,
					],
					'messages' => $this->get_messages(),
					) );

				return '<div>' . Styles::messages() . $this->get_template( $vars2 ) . '</div>';
			} else {
				$table = new MessagesTable( $this->wishlist );
				$content = $table->setup()->get_template();
				return $this->get_new_template( $content );
			}
		}
	}

	protected function get_orders_section() {
		$overridden_file = $this->is_overridden();
		if ( $overridden_file ) {
			nmgr_overridden_notice( $overridden_file, '4.6.0' );

			$wishlist = $this->wishlist;
			$orders = [];

			if ( $wishlist ) {
				$order_ids = $wishlist->get_order_ids();
				foreach ( $order_ids as $order_id ) {
					$orders[] = wc_get_order( $order_id );
				}
			}

			$vars = $this->get_default_section_vars();
			$vars[ 'orders' ] = $orders;
			$vars[ 'order_columns' ] = apply_filters( 'nmgr_orders_table_columns', array(
				'customer' => __( 'Customer', 'nm-gift-registry' ),
				'order' => __( 'Order', 'nm-gift-registry' ),
				'order-date' => __( 'Order date', 'nm-gift-registry' ),
				'items-purchased' => __( 'Items purchased', 'nm-gift-registry' ),
				'total-spent' => [
					'label' => __( 'Total spent', 'nm-gift-registry' ),
					'title' => __( 'Total spent including tax', 'nm-gift-registry' ),
				]
				) );

			if ( isset( $vars[ 'order_columns' ][ 'order' ] ) && !is_nmgr_admin() ) {
				unset( $vars[ 'order_columns' ][ 'order' ] );
			}

			return $this->get_template( $vars );
		} else {
			$table = new OrdersTable( $this->wishlist );
			$content = $table->setup()->get_template();
			return $this->get_new_template( $content );
		}
	}

	protected function get_settings_section() {
		$form = $this->wishlist ? new \NMGR_Form( $this->wishlist->get_id() ) : '';

		$overridden_file = $this->is_overridden();
		if ( $overridden_file ) {
			nmgr_overridden_notice( $overridden_file, '4.6.0' );
			$vars = $this->get_default_section_vars();
			$vars2 = array_merge( $vars, [ 'form' => $form ] );
			return $this->get_template( $vars2 );
		} else {
			ob_start();

			$seperated_fields = [ 'delete_wishlist' ];
			?>
			<form method="post" id="nmgr-settings-form" class="nmgr-form">
				<?php
				echo $form->get_fields_html( 'settings', $seperated_fields );

				if ( $form->has_fields() ) {
					echo $form->get_hidden_fields();
					echo $form->get_submit_button( 'settings' );
				}
				?>
			</form>

			<?php
			$sep_html = $form->get_fields_html( $seperated_fields );
			if ( $form->has_fields() ) :
				?>
				<div class="nmgr-separated-settings-section">
					<form method="post" id="nmgr-separated-settings-form">
						<?php
						echo $sep_html;
						echo $form->get_hidden_fields();
						?>
						<input type="hidden" name="nmgr_separated_settings_form" value="1">
					</form>
				</div>

				<script>
					document.addEventListener('DOMContentLoaded', function () {
						jQuery('input#nmgr_delete_wishlist').on('change', function () {
							if (this.dataset.notice && !window.confirm(this.dataset.notice)) {
								this.checked = !this.checked;
								return false;
							}
							this.closest('form').submit();
						});
					}, false);
				</script>
				<?php
			endif;

			$content = ob_get_clean();
			return $this->get_new_template( $content );
		}
	}

	public function get_messages() {
		_deprecated_function( __METHOD__, '4.6.0', 'NMGR\Sub\Account->get_wishlist()->get_messages()' );
		return $this->wishlist ? $this->wishlist->get_messages() : array();
	}

	public static function get_all_wishlists( $type ) {
		if ( nmgr_get_type_option( $type, 'allow_multiple_wishlists' ) ) {
			return nmgr_get_user_wishlists( '', $type );
		} else {
			return parent::get_all_wishlists( $type );
		}
	}

	protected static function single_wishlist_only( $type ) {
		return nmgr_get_user_wishlists_count( '', $type ) &&
			(!nmgr()->is_pro || !nmgr_get_type_option( $type, 'allow_multiple_wishlists' ));
	}

}
