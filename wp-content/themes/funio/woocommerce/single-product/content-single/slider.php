<?php
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 0 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	remove_action( 'woocommerce_single_product_summary', 'funio_add_loop_wishlist_link', 35 );
	remove_action( 'woocommerce_single_product_summary', 'funio_size_guide', 20 );
	remove_action( 'woocommerce_single_product_summary', 'funio_get_countdown', 20 );				
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
		?>
	</div>
	<div class="bwp-single-info col-lg-12 col-md-12 col-12 ">
		<div class="row">
			<div class="col-lg-6 col-md-12 col-12">
				<div class="breadcrumb-noheading">
					<div class="container">
					<?php if(function_exists('is_woocommerce') && is_woocommerce()){
						if (class_exists("WCV_Vendors") && WCV_Vendors::is_vendor_page()){
							get_template_part( 'breadcrumb');
						}else{
							funio_woocommerce_breadcrumb();
						}
					}else{
						get_template_part( 'breadcrumb');
					} ?>
					</div>
				</div>
				<div class="summary entry-summary entry-heading">
					<?php woocommerce_template_single_rating(); ?>
					<?php woocommerce_template_single_title(); ?>
					<?php woocommerce_template_single_price(); ?>
				</div>
				<div class="summary entry-summary entry-info">
				<?php
					/**
					 * woocommerce_single_product_summary hook
					 *
					 * @hooked woocommerce_template_single_title - 5
					 * @hooked woocommerce_template_single_rating - 10
					 * @hooked woocommerce_template_single_price - 10
					 * @hooked woocommerce_template_single_excerpt - 20
					 * @hooked woocommerce_template_single_add_to_cart - 30
					 * @hooked woocommerce_template_single_meta - 40
					 * @hooked woocommerce_template_single_sharing - 50
					 */
					do_action( 'woocommerce_single_product_summary' );
				?>
				</div><!-- .summary -->
			</div>
			<div class="col-lg-1 col-md-12 col-12 hidden-md hidden-sm hidden-xs">
			</div>
			<div class="col-xl-4 col-lg-6 col-md-12 col-12">
				<?php funio_size_guide(); ?>
				<div class="summary entry-summary entry-cart">
					<?php woocommerce_template_single_add_to_cart(); ?>
					<?php funio_add_loop_wishlist_link(); ?>
					<?php funio_get_countdown(); ?>
				</div>
			</div>
			<div class="col-lg-1 col-md-12 col-12 hidden-md hidden-sm hidden-xs">
			</div>
		</div>
	</div>