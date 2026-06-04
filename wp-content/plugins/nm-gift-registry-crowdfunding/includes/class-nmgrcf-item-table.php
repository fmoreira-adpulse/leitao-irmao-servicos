<?php
/**
 * Actions related to the management of crowdfunded items in the wishlist items table
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Item_Table {

	public static function run() {
		add_filter( 'nmgr_delete_item_notice', array( __CLASS__, 'set_crowdfund_delete_notice' ), 5, 2 );
		add_action( 'nmgr_post_action', [ __CLASS__, 'post_action' ] );
		add_filter( 'nmgr_item_add_to_cart_form_html', array( __CLASS__, 'get_crowdfund_add_to_cart_form' ), 10, 2 );
		add_action( 'nmgr_item_after_title', array( __CLASS__, 'maybe_show_wallet_transfers_icon' ) );
		add_filter( 'nmgr_fields_item_statuses', array( __CLASS__, 'modify_item_statuses' ), 10, 2 );
		add_filter( 'nmgr_fields_item_actions', array( __CLASS__, 'add_item_actions' ), 10, 2 );
		add_filter( 'nmgr_fields_item', [ __CLASS__, 'crowdfunding_items_view_data' ], 10, 2 );
		add_filter( 'nmgr_fields_items_totals', array( __CLASS__, 'add_items_total_crowdfund_rows' ), 10, 2 );
	}

	public static function crowdfunding_items_view_data( $data, $fields ) {
		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return $data;
		}

		$label = __( 'Crowdfunded', 'nm-gift-registry-crowdfunding' );

		$data[ 'crowdfunding' ] = [
			'label' => $label,
			'table_header_content' => nmgr_get_svg( array(
				'icon' => 'users',
				'size' => 1,
				'fill' => '#ccc',
				'title' => $label,
			) ),
			'priority' => 75,
			'show' => $fields->table->is_gift_registry,
			'content' => [ __CLASS__, 'items_view_crowdfunding_content' ],
			'content_container_attributes' => [
				'data-title' => $label,
			],
		];

		return $data;
	}

	public static function post_action( $args ) {
		$pa = $args[ 'post_action' ] ?? null;
		switch ( $pa ) {
			case 'show_crowdfund_settings_dialog':
			case 'save_crowdfund_settings':
				if ( is_callable( __CLASS__, $pa ) ) {
					self::$pa( $args );
				}
				break;
		}
	}

	public static function save_crowdfund_settings() {
		$item = nmgr_get_wishlist_item( ( int ) $_POST[ 'data' ][ 'wishlist_item_id' ] );
		$crowdfund_data = $item->get_crowdfund_data();
		$min_amt = ( float ) $_POST[ 'minimum_amount' ];
		$crowdfund_data[ 'min_amount' ] = 0 < $min_amt ? $min_amt : ($crowdfund_data[ 'minimum_amount' ] ?? '');

		$item->set_crowdfunded( ( int ) $_POST[ 'crowdfunded' ] );
		$item->set_crowdfund_data( $crowdfund_data );
		$item->save();

		$table = nmgr()->items_table( $item->get_wishlist() );

		wp_send_json( array(
			'close_dialog' => true,
			'replace_templates' => array_merge(
				$table->get_item_template_data( $item ),
				$table->get_totals_template_data()
			),
		) );
	}

	public static function show_crowdfund_settings_dialog( $args ) {
		$item_id = $args[ 'wishlist_item_id' ];

		$vars = [
			'data' => [
				'wishlist_item_id' => $item_id,
				'page' => ( int ) ($_POST[ 'dataset' ][ 'page' ] ?? 1),
			],
			'item' => nmgr_get_wishlist_item( $item_id ),
		];

		$modal = nmgr_get_modal();
		$modal->set_id( 'nmgr-crowdfund-settings-dialog-' . $item_id );
		$modal->set_title( self::crowdfund_settings_text() );
		$modal->set_content( nmgrcf_get_template( 'dialogs/crowdfund-settings.php', $vars ) );
		$modal->set_footer( $modal->get_save_button( [
				'attributes' => [
					'type' => 'submit',
					'class' => [
						'button-primary',
					],
					'form' => 'nmgrcf-cf-settings-form'
				]
		] ) );

		wp_send_json( [
			'show_template' => $modal->get(),
		] );
	}

	public static function items_view_crowdfunding_content( $table ) {
		$item = $table->get_row_object();
		$wishlist = $table->wishlist;
		$crowdfunded = ( int ) $item->is_crowdfunded();
		$can_manage_wishlist = nmgr_user_can_manage_wishlist( $wishlist );

		if ( $can_manage_wishlist ) {
			$available = $item->get_crowdfund_amount_available();
			$left = $item->get_crowdfund_amount_left();
			$amt_available = wc_price( $available );
			$amt_left = wc_price( $left );
			$amt_needed = $item->get_crowdfund_amount_needed();
		}

		ob_start();
		?>
		<div>
			<div class="view">
				<?php
				if ( $crowdfunded ) {
					$icon_args = array(
						'icon' => 'users',
						'fill' => 'currentColor',
						'title' => __( 'Crowdfunded', 'nm-gift-registry-crowdfunding' ),
					);

					$icon_args[ 'title' ] = apply_filters( 'nmgrcf_items_table_body_crowdfunding_icon_tooltip', $icon_args[ 'title' ] );

					echo wp_kses( nmgr_get_svg( $icon_args ), nmgr_allowed_post_tags() );

					if ( $can_manage_wishlist ) {
						/* translators: %s: amount received */
						$amt_available_text = sprintf( __( '%s received.', 'nm-gift-registry-crowdfunding' ), $amt_available );

						/* translators: %s: amount still needed */
						$amt_left_text = sprintf( __( '%s still needed.', 'nm-gift-registry-crowdfunding' ), $amt_left );

						$title_attribute = sprintf(
							/* translators: 1: amount received, 2: amount needed */
							__( '%1$s of %2$s received.', 'nm-gift-registry-crowdfunding' ),
							wc_price( $available ),
							wc_price( $amt_needed )
						);

						$progress_total = $available + $left;
						echo wp_kses(
							nmgr_progressbar( $progress_total, $available, $title_attribute, true, false ),
							nmgr_allowed_post_tags()
						);
						?>
						<div class="cf-info">
							<div class="amt-received nmgr-tip"
									 title="<?php echo esc_attr( strip_tags( $amt_available_text ) ); ?>">
										 <?php
										 echo wp_kses( nmgr_get_svg( array(
											 'icon' => 'cart-full',
											 'class' => 'align-with-text',
											 'style' => 'margin-right:2px;',
											 'fill' => 'currentColor',
											 ) ), nmgr_allowed_post_tags() ) . wp_kses_post( $amt_available );
										 ?>
							</div>
							<div class="amt-needed nmgr-tip"
									 title="<?php echo esc_attr( strip_tags( $amt_left_text ) ); ?>">
										 <?php
										 echo wp_kses( nmgr_get_svg( array(
											 'icon' => 'cart-empty',
											 'class' => 'align-with-text',
											 'style' => 'margin-right:2px;',
											 'fill' => 'currentColor',
											 ) ), nmgr_allowed_post_tags() ) . wp_kses_post( $amt_left );
										 ?>
							</div>
						</div>
						<?php
					}
				} else {
					echo wp_kses( nmgr_get_svg( array(
						'icon' => 'users',
						'fill' => '#ccc',
						'title' => __( 'Not crowdfunded', 'nm-gift-registry-crowdfunding' ),
						) ), nmgr_allowed_post_tags() );
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function set_crowdfund_delete_notice( $notice, $item ) {
		if ( $item->is_crowdfunding_enabled() && $item->get_crowdfund_amount_available() ) {
			if ( $item->is_wallet_transfer_enabled() ) {
				$notice .= ' ' . __( 'Please move the item\'s crowdfunded contributions to the wallet before deletion if applicable to prevent them from being lost. Click OK to delete anyway.', 'nm-gift-registry-crowdfunding' );
			} else {
				$notice .= ' ' . __( 'This would also delete the item\'s crowdfunded contributions.', 'nm-gift-registry-crowdfunding' );
			}
		} elseif ( $item->is_wallet_transfer_enabled() && $item->get_purchased_quantity() ) {
			remove_filter( 'nmgr_delete_item_notice', array( 'NMGR_Templates', 'notify_of_item_purchased_status' ), 10 );
			$notice .= ' ' . __( 'Please move the item\'s purchased amount to the wallet before deletion to prevent it from being lost. Click OK to delete anyway.', 'nm-gift-registry-crowdfunding' );
		}
		return $notice;
	}

	public static function add_items_total_crowdfund_rows( $rows, $fields ) {
		$wishlist = $fields->table->wishlist;

		if ( is_nmgrcf_crowdfunding_enabled() ) {
			$wishlist_type_title = nmgr_get_type_title();
			if ( $wishlist->has_normal_item() ) {
				$rows[ 'normal_amount_needed' ] = [
					'priority' => 50,
					'label' => __( 'Normal amount still needed', 'nm-gift-registry-crowdfunding' ) .
					nmgr_get_help_tip( sprintf(
							/* translators: %s: wishlist type title */
							esc_html__( 'This is the amount still needed to completely fulfill all the normal (non-crowdfunded) items in the %s.', 'nm-gift-registry-crowdfunding' ),
							$wishlist_type_title
						) ) . ' :',
					'content' => wc_price( $wishlist->get_unpurchased_amount() ),
					'class' => [ 'nmgr-grey' ],
				];

				$rows[ 'crowdfund_amount_needed' ] = [
					'priority' => 60,
					'label' => __( 'Crowdfund amount still needed', 'nm-gift-registry-crowdfunding' ) .
					nmgr_get_help_tip( sprintf(
							/* translators: %s: wishlist type title */
							esc_html__( 'This is the amount still needed to completely fulfill all the crowdfunded items in the %s.', 'nm-gift-registry-crowdfunding' ),
							$wishlist_type_title
						) ) . ' :',
					'content' => wc_price( $wishlist->get_crowdfund_amount_left() ),
					'class' => [ 'nmgr-grey' ],
				];
			}
		}

		$rows[ 'wallet_amount' ] = [
			'priority' => 70,
			'label' => __( 'Amount in wallet', 'nm-gift-registry-crowdfunding' ) .
			nmgr_get_help_tip( esc_html__( 'This is the amount you currently have in your wallet that can be used to fund items. It is not currently part of the money received for any item.', 'nm-gift-registry-crowdfunding' ) ) . ' :',
			'show' => $wishlist->is_wallet_transfer_enabled(),
			'content' => wc_price( $wishlist->get_wallet_balance() ),
			'class' => [ 'nmgr-grey' ],
		];

		return $rows;
	}

	public static function crowdfund_settings_text() {
		return __( 'Crowdfund settings', 'nm-gift-registry-crowdfunding' );
	}

	public static function add_item_actions( $actions, $fields ) {
		$item = $fields->table->get_row_object();
		$is_gift_registry = $fields->table->is_gift_registry;
		$is_admin = $fields->table->is_admin;

		if ( !$is_gift_registry ) {
			return $actions;
		}

		$item_id = $item ? $item->get_id() : 0;
		$item_block = htmlspecialchars(
			json_encode( [
			'.nmgr-items-view .item[data-wishlist_item_id="' . $item_id . '"]'
			] )
		);
		$is_wallet_transfer_enabled = $item && $item->is_wallet_transfer_enabled();

		if ( $item ) {
			if ( $item->is_crowdfunded() && true === ( $actions[ 'purchase_refund' ][ 'show' ] ?? false) ) {
				$actions[ 'purchase_refund' ][ 'show' ] = !$item->is_fulfilled();
			}

			if ( !$is_admin && true === ( $actions[ 'delete' ][ 'show' ] ?? false) ) {
				$actions[ 'delete' ][ 'show' ] = !$item->is_funded_from_wallet() &&
					!$item->has_crowdfund_contributions();
			}
		}

		$actions[ 'crowdfund' ] = [
			'text' => self::crowdfund_settings_text(),
			'priority' => 50,
			'attributes' => [
				'class' => [
					'crowfund-wishlist-item',
					'nmgr-post-action',
				],
				'href' => '#',
				'data-nmgr_post_action' => 'show_crowdfund_settings_dialog',
				'data-wishlist_item_id' => $item_id,
			],
			'show' => $item && $item->is_crowdfunding_enabled() && !$item->is_fulfilled() &&
			!$item->is_funded_from_wallet(),
		];

		$actions[ 'credit_wallet' ] = [
			'text' => __( 'Send received amount to wallet', 'nm-gift-registry-crowdfunding' ),
			'priority' => 60,
			'show' => $is_wallet_transfer_enabled,
			'attributes' => [
				'class' => [
					'nmgr-credit-wallet',
					'nmgr-cf',
					'nmgrcf-post-action',
				],
				'href' => '#',
				'data-nmgr_post_action' => 'item_debit_credit_wallet_action',
				'data-nmgr_block' => $item_block,
				'data-context' => 'credit',
				'data-wishlist_item_id' => $item_id,
				'data-notice' => __( 'If you send the amount received for this item to the wallet, contributions or purchases would be disabled for the item and it can only be funded from the wallet. Are you sure you want to continue?', 'nm-gift-registry-crowdfunding' ),
			]
		];
		$actions[ 'debit_wallet' ] = [
			'text' => __( 'Fund from wallet', 'nm-gift-registry-crowdfunding' ),
			'priority' => 70,
			'show' => $is_wallet_transfer_enabled,
			'attributes' => [
				'class' => [
					'nmgr-debit-wallet',
					'nmgr-cf',
					'nmgrcf-post-action',
				],
				'href' => '#',
				'data-nmgr_post_action' => 'item_debit_credit_wallet_action',
				'data-nmgr_block' => $item_block,
				'data-context' => 'debit',
				'data-wishlist_item_id' => $item_id,
				'data-notice' => __( 'Are you sure you want to fund this item from the wallet? Please note that contributions or purchases would be disabled for the item and it can only be funded from the wallet in the future.', 'nm-gift-registry-crowdfunding' ),
			]
		];

		return $actions;
	}

	public static function get_crowdfund_add_to_cart_form( $form, $table ) {
		$item = $table->get_row_object();
		if ( is_nmgrcf_crowdfunding_enabled() && $item->is_crowdfunded() ) {
			$form = nmgrcf_get_template( 'add-to-cart.php', [ 'table' => $table ] );
		}
		return $form;
	}

	/**
	 * Show the wallet icon next to the item title as notification
	 * if the item can only be funded from the wallet
	 */
	public static function maybe_show_wallet_transfers_icon( $args ) {
		$item = $args->get_row_object();
		if ( $item->is_funded_from_wallet() ) {
			echo nmgr_get_svg( array(
				'icon' => 'credit-card-full',
				'style' => 'margin-left:5px',
				'class' => 'align-with-text',
				'title' => __( 'This item is funded from the wallet.', 'nm-gift-registry-crowdfunding' ),
				'fill' => '#aaa',
			) );
		}
	}

	public static function modify_item_statuses( $statuses, $fields ) {
		if ( isset( $statuses[ 'not-purchasable' ] ) ) {
			$wishlist = $fields->table->wishlist;
			$item = $fields->table->get_row_object();

			$cond1 = $wishlist->has_fulfill_amount() && $item->is_wallet_transfer_enabled();
			$cond2 = $item->is_funded_from_wallet();
			$is_purchase_disabled = !$item->is_fulfilled() && ($cond1 || $cond2 );

			$current_show = $statuses[ 'not-purchasable' ][ 'show' ] ?? true;
			$statuses[ 'not-purchasable' ][ 'show' ] = $current_show || $is_purchase_disabled;

			if ( $is_purchase_disabled ) {
				$notice = __( 'This item can only be funded from the wallet. Checkout purchases are disabled for it.', 'nm-gift-registry-crowdfunding' );
				$statuses[ 'not-purchasable' ][ 'label' ] = $statuses[ 'not-purchasable' ][ 'label' ] . ' ' .
					nmgr_get_help_tip( $notice );
			}
		}

		return $statuses;
	}

}
