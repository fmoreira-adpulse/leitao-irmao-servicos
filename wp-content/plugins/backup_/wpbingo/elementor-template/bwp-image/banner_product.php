<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $product, $woocommerce_loop;
?>
<div class="bwp-widget-banner <?php echo esc_html( $layout ); ?>">
	<div class="bg-banner">
		<?php  if($product_id && $product = wc_get_product( $product_id )):
			$symboy = get_woocommerce_currency_symbol( get_woocommerce_currency() );
			$attributes = $product->get_attributes();			
		?>
		<div class="container">
			<div class="row">
				<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 content-image">
					<div class="image">
						<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr__("Banner Product","wpbingo"); ?>">
					</div>
				</div>
				<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 content-products">
					<div class="products-content">
						<div class="content">
							<?php if( $title1) : ?>
							<h2 class="title-banner"><?php echo esc_html( $title1 ); ?></h2>
							<?php endif; ?>
							<h3 class="product-title"><a href="<?php echo get_permalink( $product_id );  ?>"><?php echo $product->get_title(); ?></a></h3>
							<div class="product-price"><?php echo $product->get_price_html(); ?></div>
							<div class="product-description"><?php echo wp_trim_words( $product->get_short_description() ); ?></div>
							<div class="product-button">
								<?php
									if(function_exists("funio_woocommerce_template_loop_add_to_cart")){
										funio_woocommerce_template_loop_add_to_cart();
									}
								?>
								<div class="btn-detail"><a href="<?php echo get_permalink( $product_id );  ?>"><?php echo esc_html__("detail","wpbingo") ?></a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif ?>
	</div>
</div>
