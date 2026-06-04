<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Templates extends \NMGR_Templates {

	public static function run() {
		add_action( 'nmgr_wishlist', array( __CLASS__, 'single_show_images' ), 10 );
		add_action( 'nmgr_background_image_content', [ __CLASS__, 'show_background_image_link' ], 10 );
		add_action( 'nmgr_background_image_content', array( __CLASS__, 'show_image_editing_tools' ), 20 );
		add_action( 'nmgr_featured_image_content', array( __CLASS__, 'show_featured_image_link' ), 10 );
		add_action( 'nmgr_featured_image_content', array( __CLASS__, 'show_image_editing_tools' ), 20 );

		parent::run();
	}

	/**
	 * Show wishlist images on single wishlist page
	 */
	public static function single_show_images( $wishlist ) {
		echo nmgr_get_account_section( 'images', $wishlist );
	}

	/**
	 * Show anchor for background image
	 */
	public static function show_background_image_link( $wishlist ) {
		$url = $wishlist->get_background_image_url();

		if ( $url ) {
			?>
			<a class='nmgr-image-link' target='_blank' href="<?php echo esc_url( $url ); ?>"></a>
			<?php
		}
	}

	public static function show_featured_image_link( $wishlist ) {
		$url = wp_get_attachment_url( $wishlist->get_thumbnail_id() );

		if ( $url ) {
			?>
			<a class='nmgr-image-link' target='_blank' href="<?php echo esc_url( $url ); ?>"></a>
			<?php
		}
	}

	/**
	 * Show editing tools only on pages on which we can edit images
	 */
	public static function show_image_editing_tools( $wishlist ) {
		$is_archived = is_callable( [ $wishlist, 'is_archived' ] ) ? $wishlist->is_archived() : false;

		if ( nmgr_user_has_wishlist( $wishlist ) && !$is_archived ) {
			if ( doing_action( 'nmgr_background_image_content' ) ) {
				$image_id = $wishlist->get_background_image_id();
				$input_id = 'nmgr-image-input-background';
			} else {
				$image_id = $wishlist->get_thumbnail_id();
				$input_id = 'nmgr-image-input-thumbnail';
			}
			?>
			<div class="nmgr-image-actions">
				<input id="<?php echo $input_id; ?>"
							 type="file" name="nmgr-upload" class="nmgr-image-input" accept="image/*">

				<label class="nmgr-tip nmgr-upload-image nmgr-action"
							 title="<?php esc_html_e( 'Upload image', 'nm-gift-registry' ); ?>"
							 for="<?php echo $input_id; ?>">
								 <?php
								 echo nmgr_get_svg( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									 'icon' => 'camera',
									 'size' => 1,
									 'fill' => '#666'
								 ) );
								 ?>
				</label>
				<div class="nmgr-tip delete-image nmgr-action"
						 title="<?php esc_html_e( 'Delete image', 'nm-gift-registry' ); ?>"
						 data-image_id="<?php echo esc_attr( $image_id ); ?>"
						 data-wishlist_id="<?php echo esc_attr( $wishlist->get_id() ); ?>"
						 data-notice="<?php esc_attr_e( 'Are you sure you want to delete this image', 'nm-gift-registry' ); ?>">
							 <?php
							 echo nmgr_get_svg( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								 'icon' => 'trash-can',
								 'size' => 1,
								 'fill' => '#666'
							 ) );
							 ?>
				</div>
			</div>
			<?php
		}
	}

}
