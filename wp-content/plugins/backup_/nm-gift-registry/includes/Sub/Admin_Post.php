<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Admin_Post extends \NMGR_Admin_Post {

	public static function run() {
		parent::run();

		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'set_post_submitbox_actions' ) );
		add_action( 'nmgr_post_submitbox_actions', array( __CLASS__, 'show_exclude_from_search_field' ), 10 );
		add_action( 'nmgr_post_submitbox_actions', array( __CLASS__, 'show_archive_field' ), 10 );
		add_filter( 'nmgr_add_items_table_columns', array( __CLASS__, 'extra_add_items_table_columns' ), 0 );
		add_action( 'admin_head', array( __CLASS__, 'add_wishlist_archive_css' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_pro_meta_boxes' ), 20 );
	}

	public static function add_pro_meta_boxes() {
		if ( self::is_gift_registry() ) {
			add_meta_box(
				'nm_gift_registry-orders',
				__( 'Orders', 'nm-gift-registry' ),
				array( __CLASS__, 'orders_metabox' ),
				'nm_gift_registry',
				'normal'
			);
		}


		if ( self::is_gift_registry() && nmgr_get_option( 'display_image_background' ) !== 'no' ) {
			add_meta_box(
				'nm_gift_registry-bg-img',
				__( 'Background Image', 'nm-gift-registry' ),
				array( __CLASS__, 'background_image_metabox' ),
				'nm_gift_registry',
				'side',
				'default'
			);
		}

		if ( self::is_gift_registry() && nmgr_get_option( 'enable_messages' ) ) {
			add_meta_box(
				'nm_gift_registry-messages',
				__( 'Messages', 'nm-gift-registry' ),
				array( __CLASS__, 'messages_metabox' ),
				'nm_gift_registry',
				'normal',
				'low'
			);
		}
	}

	public static function set_post_submitbox_actions() {
		global $post;

		if ( 'nm_gift_registry' === get_post_type() && self::is_gift_registry() ) {
			$wishlist = nmgr_get_wishlist( $post );

			echo '<hr>';
			do_action( 'nmgr_post_submitbox_actions', $wishlist );
			echo '<div style="padding-top:.5em;"></div>';
		}
	}

	public static function show_exclude_from_search_field( $wishlist ) {
		$form = new \NMGR_Form( $wishlist );
		$field = $form->get_fields_html( [ 'exclude_from_search' ] );

		echo '<div class="misc-pub-section misc-pub-nmgr-exclude-from-search">' . $field . '</div>';
	}

	public static function show_archive_field( $wishlist ) {
		$form = new \NMGR_Form( $wishlist );
		$field = $form->get_fields_html( [ 'archived' ] );
		?>
		<style>
			.misc-pub-section p#nmgr_archived_field {
				margin: -10px 0;
			}
		</style>
		<?php

		echo '<div class="misc-pub-section misc-pub-nmgr-archived">' . $field . '</div>';
	}

	public static function orders_metabox( $post ) {
		echo nmgr_get_account_section( 'orders', $post->ID );
	}

	public static function background_image_metabox( $post ) {
		// Get the background image id value
		$bg_id = nmgr_get_wishlist( $post )->get_background_image_id();

		// Get WordPress' media upload URL
		$upload_link = esc_url( get_upload_iframe_src( 'image', $post->ID ) );

		$src = $bg_id ? wp_get_attachment_image_src( $bg_id, 'medium' ) : '';
		$image = $src ? esc_html( $src[ 0 ] ) : '';
		echo "<a href='$upload_link' class='custom-img-container ", $image ? "" : "hidden", "'>";
		echo $image ? "<img src='$image' alt='' />" : "";
		echo "</a>"
		. "<p class='hide-if-no-js nmgr-set-bg-img-desc ", $image ? "" : "hidden", "'>"
		. esc_html__( 'Click the image to update', 'nm-gift-registry' )
		. "</p>"
		. "<p class='hide-if-no-js'>"
		. "<a class='upload-custom-img ", $image ? "hidden" : "", "' href='$upload_link'>"
		. esc_html__( 'Set background image', 'nm-gift-registry' )
		. "</a>"
		. "<a class='delete-custom-img ", !$image ? "hidden" : "", "' href='#'>"
		. esc_html__( 'Remove background image', 'nm-gift-registry' )
		. "</a>"
		. "</p>"
		. "<input class='custom-img-id' name='background_image_id' type='hidden' value='$bg_id' >";
	}

	public static function messages_metabox( $post ) {
		echo nmgr_get_account_section( 'messages', $post->ID );
	}

	public static function extra_add_items_table_columns( $columns ) {
		if ( !self::is_gift_registry() ) {
			return $columns;
		}

		$fav_text = esc_html__( 'Favourite', 'nm-gift-registry' );

		$columns[ $fav_text ] = '<td data-title="' . $fav_text . '"><select name="product_fav"><option value="0">' . esc_html__( 'No', 'nm-gift-registry' ) . '</option><option value="1">' . esc_html__( 'Yes', 'nm-gift-registry' ) . '</option></td>';

		if ( !nmgr_get_option( 'display_item_favourite' ) ) {
			unset( $columns[ $fav_text ] );
		}

		return $columns;
	}

	public static function add_wishlist_archive_css() {
		global $pagenow, $post;

		if ( 'post.php' === $pagenow && 'nm_gift_registry' === get_post_type( $post ) ) {
			$wishlist = nmgr_get_wishlist( $post );
			if ( $wishlist && $wishlist->is_archived() ) {
				?>
				<style id="nmgr-wishlist-archive-css">
					#nm_gift_registry-bg-img .inside,
					#postimagediv .inside,
					#nm_gift_registry-profile .select2-container,
					.misc-pub-nmgr-exclude-from-search .nmgr-checkbox-switch,
					textarea#nmgr_description,
					#titlewrap input#title {
						pointer-events: none;
						opacity: .8;
					}

					#nm_gift_registry-profile .inside,
					#nm_gift_registry-items .inside,
					#nm_gift_registry-orders .inside,
					#nm_gift_registry-messages .inside,
					#nm_gift_registry-event-date .inside {
						opacity: .8;
					}
				</style>
				<?php

			}
		}
	}

}
