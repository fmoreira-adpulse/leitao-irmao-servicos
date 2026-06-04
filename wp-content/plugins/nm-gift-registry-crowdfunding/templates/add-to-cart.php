<?php
/**
 * Template for adding contribution for a crowdfunded item to the cart
 *
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 4.7
 * @sync
 */
defined( 'ABSPATH' ) || exit;
?>

<?php
$item = $table->get_row_object();
$amt_received = $item->get_crowdfund_amount_available();
$amt_left = $item->get_crowdfund_amount_left();
$received_text = sprintf(
	/* translators: %s: amount received */
	__( '%s received already!', 'nm-gift-registry-crowdfunding' ),
	wc_price( $amt_received )
);
?>
<div class="nmgr-cf-progressbar-wrapper">
	<?php if ( $amt_received ) : ?>
		<div class="amount-received"><?php echo wp_kses_post( $received_text ); ?></div>
	<?php endif; ?>
	<div class="amount-left">
		<?php
		printf(
			/* translators: %s: amount still needed */
			esc_html__( '%s still needed', 'nm-gift-registry-crowdfunding' ),
			wp_kses_post( wc_price( $amt_left ) )
		);
		?>
	</div>
	<?php
	$progress_total = $amt_received + $amt_left;
	$title_attribute = sprintf(
		/* translators: 1: amount received, 2: amount needed */
		__( '%1$s of %2$s received.', 'nm-gift-registry-crowdfunding' ),
		wc_price( $amt_received ),
		wc_price( $progress_total )
	);
	echo wp_kses( nmgr_progressbar( $progress_total, $amt_received, $title_attribute ),
		nmgr_allowed_post_tags() );
	?>
</div>

<?php
do_action( 'nmgrcf_item_before_add_to_cart_form', $args );
?>

<form class="nmgr-cf nmgr-add-to-cart-form" action="<?php the_permalink(); ?>" method="post">
	<input type="hidden" name="nmgr-cf-wishlist-item-id" value="<?php echo absint( $item->get_id() ); ?>" />
	<input type="hidden" name="nmgr-cf-wishlist-id" value="<?php echo absint( $item->get_wishlist_id() ); ?>" />
	<?php
	do_action( 'nmgrcf_item_add_to_cart_form', $args );

	$crowdfund_data = $item->get_crowdfund_data();
	$minimum_amount = 0;

	if ( isset( $crowdfund_data[ 'min_amount' ] ) && $crowdfund_data[ 'min_amount' ] &&
		nmgrcf_round( $amt_left ) > nmgrcf_round( $crowdfund_data[ 'min_amount' ] ) ) {
		$minimum_amount = ( float ) $crowdfund_data[ 'min_amount' ];
	}

	nmgrcf_price_box( array(
		'max' => $amt_left,
		'min' => $minimum_amount,
		'value' => 0 === $minimum_amount ? '' : $minimum_amount,
		'currency-symbol-border' => true,
	) );

	$cls = !nmgr_get_option( 'ajax_add_to_cart' ) ? '' : 'nmgr_ajax_add_to_cart';
	?>
	<button type="submit"
					class="nmgr_add_to_cart_button nmgr_cf button alt <?php echo sanitize_html_class( $cls ); ?>"
					data-product_id="<?php echo absint( $item->get_product_or_variation_id() ); ?>"
					data-wishlist_item_id="<?php echo absint( $item->get_id() ); ?>"
					data-wishlist_id="<?php echo absint( $item->get_wishlist_id() ); ?>"
					title="<?php
					echo esc_html( __( 'Contribute an amount to the cost of this item', 'nm-gift-registry-crowdfunding' ) );
					?>">
						<?php echo esc_html( nmgrcf_get_crowdfund_item_button_text() ); ?>
	</button>
</form>
<?php
do_action( 'nmgrcf_item_after_add_to_cart_form', $args );
?>
