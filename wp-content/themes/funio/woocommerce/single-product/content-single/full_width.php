<?php
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 0 );
	remove_action( 'woocommerce_single_product_summary', 'funio_size_guide', 20 );
	remove_action( 'woocommerce_single_product_summary', 'funio_get_countdown', 20 );	
	remove_action( 'woocommerce_single_product_summary', 'funio_add_social', 45 );	
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'funio_add_loop_wishlist_link', 35 );
	add_action( 'woocommerce_after_add_to_cart_button', 'funio_add_loop_wishlist_link', 35 );
	?>
<div class="bwp-single-image col-lg-12 col-md-12 col-12">
	<?php
		/**
		 * woocommerce_before_single_product_summary hooked
		 *
		 * @hooked woocommerce_show_product_sale_flash - 10
		 * @hooked woocommerce_show_product_images - 20
		 */
		do_action( 'woocommerce_before_single_product_summary' );
		wc_get_template( 'single-product/thumbnails-image/full_width.php' );
	?>
</div>
<div class="bwp-single-content-info col-lg-12 col-md-12 col-12 ">
	<div class="bwp-single-info">
		<div class="summary entry-summary entry-heading">
			<?php woocommerce_template_single_rating(); ?>
			<?php woocommerce_template_single_title(); ?>
			<?php woocommerce_template_single_price(); ?>
			<?php funio_get_video_product(); ?>
			<?php funio_view_product(); ?>
			<?php funio_get_countdown(); ?>
		</div>
		<div class="summary entry-summary entry-cart">
			<?php funio_size_guide(); ?>
			<?php do_action( 'woocommerce_single_product_summary' ); ?>
		</div>
	</div>
</div>
<?php funio_add_social(); ?>