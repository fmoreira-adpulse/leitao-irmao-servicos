<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Ajax extends \NMGR_Ajax {

	protected function paginate() {
		$section_dataset = $_POST[ 'section_dataset' ];
		$table_dataset = $_POST[ 'table_dataset' ];
		$current_page = ( int ) $table_dataset[ 'page' ];
		$page = 'next' === $_POST[ 'nav_dataset' ][ 'action' ] ? $current_page + 1 : $current_page - 1;
		$class = !empty( $table_dataset[ 'class' ] ) ? wp_unslash( $table_dataset[ 'class' ] ) : '';

		if ( class_exists( $class ) && !empty( $section_dataset[ 'wishlist_id' ] ) ) {
			/**
			 * This is used to get tables that are part of account sections.
			 * We expect them to take a wishlist object as their constructor argument
			 */
			$table = new $class( nmgr_get_wishlist( $section_dataset[ 'wishlist_id' ] ) );
			$table->set_page( $page );
			$table->set_order( $table_dataset[ 'order' ] );
			$table->set_orderby( $table_dataset[ 'orderby' ] );
			$table->setup();

			/**
			 * We're replacing the table and navigation elements separately in case they are placed at
			 * different positions in the document, as in the case of the items table
			 */
			wp_send_json( array(
				'success' => true,
				'replace_templates' => [
					"#{$table->get_html_id()}" => $table->get_table(),
					".nmgr-navs.{$table->get_id()}" => $table->get_nav()
				],
			) );
		}
	}

	protected function toggle_item_favourite() {
		$wid = $this->get_posted_wishlist_and_item_ids();
		$wishlist_id = $wid[ 'wishlist_id' ];
		$wishlist_item_ids = $wid[ 'wishlist_item_ids' ];

		$this->check_wishlist_permission( $wishlist_id );

		if ( $wishlist_id && !empty( $wishlist_item_ids ) ) {
			$table = nmgr()->items_table( nmgr_get_wishlist( $wishlist_id ) );
			$sections = [];

			foreach ( ($wishlist_item_ids ?? [] ) as $item_id ) {
				$item = nmgr_get_wishlist_item( $item_id );
				if ( $item && !$item->is_archived() && $wishlist_id === ( int ) $item->get_wishlist_id() ) {
					$value = $item->is_favourite() ? 0 : 1;
					$item->set_favourite( $value );
					$item->save();

					$sections = array_merge( $sections, $table->get_item_template_data( $item ) );
				}
			}

			wp_send_json( [ 'replace_templates' => $sections ] );
		}
	}

	protected function toggle_item_archive() {
		$wid = $this->get_posted_wishlist_and_item_ids();
		$wishlist_id = $wid[ 'wishlist_id' ];
		$wishlist_item_ids = $wid[ 'wishlist_item_ids' ];

		$this->check_wishlist_permission( $wishlist_id );

		if ( $wishlist_id && !empty( $wishlist_item_ids ) ) {
			$table = nmgr()->items_table( nmgr_get_wishlist( $wishlist_id ) );
			$sections = [];

			foreach ( ($wishlist_item_ids ?? [] ) as $item_id ) {
				$item = nmgr_get_wishlist_item( $item_id );
				if ( $item && $wishlist_id === ( int ) $item->get_wishlist_id() ) {
					if ( !$item->is_archived() ) {
						$item->archive();
					} else {
						$item->unarchive();
					}

					$sections = array_merge( $sections, $table->get_item_template_data( $item ) );
				}
			}

			wp_send_json( [ 'replace_templates' => $sections ] );
		}
	}

	/**
	 * Delete wishlist message
	 */
	protected function delete_message() {
		$wid = $this->get_posted_wishlist_and_item_ids();
		$wishlist_id = $wid[ 'wishlist_id' ];
		$this->check_wishlist_permission( $wishlist_id );

		$wishlist = nmgr_get_wishlist( $wishlist_id );
		$message_id = ( int ) $_POST[ 'message_id' ];
		if ( in_array( $message_id, wp_list_pluck( ( array ) $wishlist->get_messages(), 'id' ) ) ) {
			if ( wp_delete_comment( $message_id, true ) ) {
				/**
				 * @since 4.6.0
				 * @todo Remove in later version
				 */
				if ( nmgr_overridden( 'account/messages.php' ) ) {
					wp_send_json(
						[
							'toast_notice' => nmgr_get_success_toast_notice(),
							'replace_templates' => [
								'#nmgr-messages' => nmgr_get_account_section( 'messages', $wishlist_id )
							]
						]
					);
				} else {
					wp_send_json( [
						'replace_templates' => [ '#nmgr_message_' . $message_id => '' ]
					] );
				}
			}
		}
	}

	protected function save_image() {
		$wishlist_id = filter_input( INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT );
		$this->check_wishlist_permission( $wishlist_id );
		$context = isset( $_POST[ 'context' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'context' ] ) ) : '';

		/**
		 * make sure we only accept image mime types
		 */
		$overrides = array(
			'test_form' => false,
			'mimes' => $this->mimes(),
		);

		$attachment_id = media_handle_upload( 'nmgr-file', $wishlist_id, array(), $overrides );

		if ( !is_wp_error( $attachment_id ) ) {
			$wishlist = nmgr_get_wishlist( $wishlist_id );

			if ( 'featured' == $context ) {
				$original_id = get_post_thumbnail_id( $wishlist_id );

				if ( $original_id ) {
					wp_delete_attachment( $original_id, true );
				}

				$wishlist->set_thumbnail_id( $attachment_id );
				$wishlist->save();
			} elseif ( 'background' == $context ) {
				$original_bg_id = $wishlist->get_background_image_id();

				if ( $original_bg_id ) {
					wp_delete_attachment( $original_bg_id, true );
				}

				$wishlist->set_background_image_id( $attachment_id );
				$wishlist->save();
			}

			$acc = nmgr()->account( $wishlist );
			$response_data = array(
				'success' => true,
				'replace_templates' => $acc->set_section( 'images' )->get_sections_to_replace(),
				'wishlist' => $wishlist->get_data()
			);
			wp_send_json( $response_data );
		}
	}

	protected function delete_image() {
		$wishlist_id = filter_input( INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT );
		$image_id = filter_input( INPUT_POST, 'image_id', FILTER_VALIDATE_INT );
		$this->check_wishlist_permission( $wishlist_id );

		$wishlist = nmgr_get_wishlist( $wishlist_id );

		if ( $wishlist && $image_id ) {
			$wishlist->delete_images( $image_id );
			$acc = nmgr()->account( $wishlist );

			$response_data = array(
				'success' => true,
				'replace_templates' => $acc->set_section( 'images' )->get_sections_to_replace(),
				'wishlist' => $wishlist->get_data()
			);
			wp_send_json( $response_data );
		}
	}

	protected function save_display_mode_to_session() {
		$mode = sanitize_text_field( $_POST[ 'mode' ] );
		$section_dataset = $_POST[ 'section_dataset' ];

		if ( is_a( wc()->session, 'WC_Session' ) ) {
			wc()->session->set( 'nmgr_items_display_mode', $mode );

			$table_dataset = $_POST[ 'table_dataset' ];
			$page = ( int ) $table_dataset[ 'page' ]; // current page
			$class = !empty( $table_dataset[ 'class' ] ) ? wp_unslash( $table_dataset[ 'class' ] ) : '';

			if ( class_exists( $class ) && !empty( $section_dataset[ 'wishlist_id' ] ) ) {
				$table = new $class( nmgr_get_wishlist( $section_dataset[ 'wishlist_id' ] ) );
				$table->set_page( $page );
				$table->set_order( $table_dataset[ 'order' ] );
				$table->set_orderby( $table_dataset[ 'orderby' ] );
				$table->setup();

				wp_send_json( array(
					'success' => true,
					'replace_templates' => [
						"#{$table->get_html_id()}" => $table->get_table(),
						".nmgr-navs.{$table->get_id()}" => $table->get_nav(),
						".nmgr-display-modes.{$table->get_id()}" => $table->get_display_modes_toggle(),
					],
				) );
			}
		}
	}

}
