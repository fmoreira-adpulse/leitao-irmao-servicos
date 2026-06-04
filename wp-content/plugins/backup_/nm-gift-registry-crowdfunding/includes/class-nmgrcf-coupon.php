<?php
defined( 'ABSPATH' ) || exit;

/**
 * Coupon management for wishlists
 */
class NMGRCF_Coupon {

	public static function run() {
		add_action( 'before_delete_post', array( __CLASS__, 'remove_wishlist_coupon_data' ) );
		add_action( 'trashed_post', array( __CLASS__, 'remove_wishlist_coupon_data' ) );
		add_filter( 'woocommerce_coupon_is_valid', [ __CLASS__, 'validate_coupon' ], 10, 2 );
		add_action( 'woocommerce_check_cart_items', [ __CLASS__, 'remove_invalid_cart_items' ] );
		add_action( 'nmgr_after_account_items', array( __CLASS__, 'show_coupons' ), 15 );
	}

	public static function remove_invalid_cart_items() {
		$wishlist_id = null;

		foreach ( wc()->cart->get_applied_coupons() as $code ) {
			$coupon_id = wc_get_coupon_id_by_code( $code );

			if ( self::is_coupon_from_wallet( $coupon_id ) ) {
				$wishlist_id = self::get_wishlist_for_coupon( $coupon_id );
				break;
			}
		}

		if ( $wishlist_id ) {
			foreach ( wc()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$nmgr_data = self::get_cart_item_data( $cart_item );
				if ( !$nmgr_data || (( int ) $nmgr_data[ 'wishlist_id' ] !== $wishlist_id) ) {
					wc()->cart->set_quantity( $cart_item_key, 0 );
					$message = sprintf(
						/* translators: 1: cart item name, 2: wishlist type title */
						__( '%1$s has been removed from your cart as the coupon %2$s is not valid for it.', 'nm-gift-registry-crowdfunding' ),
						'<strong>' . apply_filters( 'woocommerce_cart_item_name', $cart_item[ 'data' ]->get_name(), $cart_item, $cart_item_key ) . '</strong>',
						'<strong>' . $code . '</strong>'
					);
					wc_add_notice( $message, 'error' );
				}
			}
		}
	}

	public static function validate_coupon( $bool, $coupon ) {
		$coupon_wishlist_id = self::get_wishlist_for_coupon( $coupon->get_id() );

		if ( $coupon_wishlist_id ) {
			if ( self::is_coupon_from_wallet( $coupon->get_id() ) && $coupon->get_usage_count() ) {
				return false;
			}

			if ( !nmgr_cart_has_wishlist( $coupon_wishlist_id ) ) {
				return false;
			}
		}

		return $bool;
	}

	public static function show_coupons( $account ) {
		$wishlist = $account->get_wishlist();
		if ( !nmgr_user_can_manage_wishlist( $wishlist ) ) {
			return;
		}

		$coupons = array();
		foreach ( $wishlist->get_coupon_ids() as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );
			if ( 0 !== $coupon->get_id() ) {
				$coupons[] = $coupon;
			}
		}

		if ( empty( $coupons ) ) {
			return;
		}

		$cols = array(
			'code' => __( 'Code', 'nm-gift-registry-crowdfunding' ),
			'type' => __( 'Type', 'nm-gift-registry-crowdfunding' ),
			'amount' => __( 'Amount', 'nm-gift-registry-crowdfunding' ),
			'usage_limit' => __( 'Usage / Limit', 'nm-gift-registry-crowdfunding' ),
			'items' => __( 'Items', 'nm-gift-registry-crowdfunding' ),
		);

		if ( is_nmgr_admin() ) {
			$cols[ 'actions' ] = __( 'Actions', 'nm-gift-registry-crowdfunding' );
		}
		?>

		<div class="nmgr-after-table-row coupons">

			<?php do_action( 'nmgrcf_before_coupons', $coupons, $wishlist ); ?>

			<header>
				<h4 class="row-title"><?php esc_html_e( 'Coupon(s)', 'nm-gift-registry-crowdfunding' ); ?></h4>
				<div class="nmgr-action nmgr-tip" title="<?php esc_attr_e( 'Show/Hide the coupons table', 'nm-gift-registry-crowdfunding' ); ?>">
					<?php
					echo wp_kses( nmgr_get_svg( array(
						'icon' => 'eye',
						'class' => 'align-with-text',
						'fill' => '#ccc',
						) ), nmgr_allowed_post_tags() );
					?>
				</div>
			</header>
			<table class="nmgrcf-coupons-table nmgr-table responsive">
				<thead>
					<tr>
						<?php
						foreach ( $cols as $key => $label ) {
							switch ( $key ) {
								case 'items':
									$title = sprintf(
										/* translators: %s: wishlist type title */
										__( 'The %s items that the coupon can be used on.', 'nm-gift-registry-crowdfunding' ),
										nmgr_get_type_title()
									);
									echo '<th class="nmgr-tip" title="' . esc_attr( $title ) . '">' . esc_html( $label ) . '</th>';
									break;

								case 'actions':
									echo '<th class="nmgr-text-center">';
									echo wp_kses( nmgr_get_svg( array(
										'icon' => 'gear',
										'size' => 1,
										'fill' => 'currentColor'
										) ), nmgr_allowed_post_tags() );
									echo '</th>';
									break;

								case 'code':
								case 'type':
								case 'amount':
								case 'usage_limit':
									echo '<th>' . wp_kses( $label, nmgr_allowed_post_tags() ) . '</th>';
									break;

//								default:
//									do_action( 'nmgrcf_coupon_table_column_header_' . $key, $label, $items, $wishlist, $items_args );
//									break;
							}
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $coupons as $coupon ) :
						$coupon_type = $coupon->get_discount_type();
						$usage_limit = $coupon->get_usage_limit();
						$usage_count = $coupon->get_usage_count();
						?>
						<tr class="toggle-edit-delete">
							<?php
							foreach ( $cols as $key => $label ) :
								switch ( $key ) {
									case 'code':
										?>
										<td data-title="<?php echo esc_attr( $label ); ?>">
											<?php
											echo esc_html( $coupon->get_code() );
											if ( self::is_coupon_from_wallet( $coupon->get_id() ) ) {
												echo wp_kses( nmgr_get_svg( array(
													'icon' => 'credit-card-full',
													'style' => 'margin-left:5px',
													'class' => 'align-with-text',
													'title' => __( 'This coupon was created from the crowdfund amount in the wallet.', 'nm-gift-registry-crowdfunding' ),
													'fill' => '#aaa',
													) ), nmgr_allowed_post_tags() );
											}
											?>
										</td>
										<?php
										break;

									case 'type':
										printf( '<td data-title="%1$s">%2$s</td>',
											esc_attr( $label ),
											esc_html( wc_get_coupon_type( $coupon_type ) )
										);
										break;

									case 'amount':
										$amount = ( float ) $coupon->get_amount();
										if ( 'percent' === $coupon_type ) {
											$amount .= '%';
										} else {
											$amount = wc_price( $amount );
										}

										printf( '<td data-title="%1$s">%2$s</td>',
											esc_attr( $label ),
											wp_kses_post( $amount )
										);
										break;

									case 'usage_limit':
										printf( '<td data-title="%1$s">%2$s</td>',
											esc_attr( $label ),
											sprintf(
												/* translators: 1: count 2: limit */
												__( '%1$s / %2$s', 'nm-gift-registry-crowdfunding' ),
												esc_html( $usage_count ),
												$usage_limit ? esc_html( $usage_limit ) : '&infin;'
											)
										);
										break;

									case 'items':
										$products = array_filter( array_map( 'wc_get_product', ( array ) $coupon->get_product_ids() ) );
										$content = '';

										if ( !empty( $products ) ) {
											$products_count = count( $products );
											foreach ( $products as $key => $product ) {
												$content .= '<a href="' . get_edit_post_link( $product->get_id() ) . '">' . wp_kses_post( $product->get_title() ) . '</a> ' . ($key !== $products_count - 1 ? '&#44;&nbsp;' : ''); //&#44; comma symbol
											}
										} else {
											$content = '&mdash;';
										}

										printf( '<td data-title="%1$s">%2$s</td>',
											esc_attr( $label ),
											wp_kses_post( $content )
										);
										break;

									case 'actions':
										?>
										<td class="actions">
											<div class="edit-delete-wrapper nmgr-text-center">
												<a class="nmgr-action edit-coupon nmgr-tip"
													 href="<?php echo esc_url( get_edit_post_link( $coupon->get_id() ) ); ?>"
													 title="<?php
						esc_attr_e( 'Visit the coupon page to edit this coupon.',
							'nm-gift-registry-crowdfunding' );
										?>">
													 <?php
														 echo wp_kses( nmgr_get_svg( array(
															 'icon' => 'pencil',
															 'fill' => 'currentColor'
															 ) ), nmgr_allowed_post_tags() );
														 ?>
												</a>
											</div>
										</td>
										<?php
										break;

//									default:
//										do_action( 'nmgrcf_coupon_table_column_body_' . $key, $label, $items, $wishlist,
//											$items_args );
//										break;
								}
							endforeach;
							?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php do_action( 'nmgrcf_after_coupons', $coupons, $wishlist ); ?>

		</div>
		<?php
	}

	/**
	 * If the coupon has been trashed or deleted, remove its data from its associated wishlist
	 */
	public static function remove_wishlist_coupon_data( $post_id ) {
		if ( 'shop_coupon' !== get_post_type( $post_id ) ) {
			return;
		}

		// Check if coupon is associated with a wishlist
		$wishlist_id = self::get_wishlist_for_coupon( $post_id );
		if ( $wishlist_id ) {
			delete_post_meta( $post_id, 'nmgrcf_wishlist_id' ); // dissociate coupon from wishlist

			$wishlist = nmgr_get_wishlist( $wishlist_id );
			if ( $wishlist ) {

				$coupon_ids = $wishlist->get_coupon_ids();
				if ( in_array( $post_id, $coupon_ids ) ) {
					unset( $coupon_ids[ array_search( $post_id, $coupon_ids ) ] );
					update_post_meta( $wishlist_id, 'nmgrcf_coupon_ids', $coupon_ids );
				}

				$wallet_coupon_ids = $wishlist->get_wallet_coupon_ids();
				if ( in_array( $post_id, $wallet_coupon_ids ) ) {
					unset( $wallet_coupon_ids[ array_search( $post_id, $wallet_coupon_ids ) ] );
					update_post_meta( $wishlist_id, 'nmgrcf_wallet_coupon_ids', $wallet_coupon_ids );

					/**
					 * If the coupon has not yet been used, restore it's amount to the wallet amount
					 * since this is a coupon from the wallet
					 */
					$coupon = new WC_Coupon( $post_id );
					if ( !$coupon->get_usage_count() ) {
						$wishlist->get_wallet()->credit(
							array(
								'amount' => $coupon->get_amount(),
								'event_code' => 'coupon_deletion',
								'note' => __( 'Coupon id:', 'nm-gift-registry-crowdfunding' ) . ' ' . $coupon->get_id(),
							)
						);
					}
				}
			}
		}
	}

	private static function get_wishlist_for_coupon( $coupon_id ) {
		return ( int ) get_post_meta( $coupon_id, 'nmgrcf_wishlist_id', true );
	}

	private static function is_coupon_from_wallet( $coupon_id ) {
		return ( bool ) get_post_meta( $coupon_id, 'nmgrcf_coupon_from_wallet', true );
	}

	private static function get_cart_item_data( $cart_item ) {
		return nmgr_get_cart_item_data( $cart_item );
	}

}
