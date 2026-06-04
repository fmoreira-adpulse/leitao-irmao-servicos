<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @see 	    http://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
get_header(); ?>
<?php
		/**
		 * woocommerce_before_main_content hook
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked funio_woocommerce_breadcrumb - 20
		 */
		$sidebar_detail_product = funio_get_config('sidebar_detail_product');
		$product_layout_thumb = funio_get_config("layout-thumbs","zoom");
		do_action( 'woocommerce_before_main_content' );
	?>
<div class="clearfix">
	<div class="contents-detail">
		<div class="main-single-product<?php if( is_active_sidebar('sidebar-product') && $sidebar_detail_product != 'full' ) { ?> sidebar<?php } ?>">
			<?php if($sidebar_detail_product == 'left' && is_active_sidebar('sidebar-product')):?>			
				<div class="bwp-sidebar sidebar-product <?php echo esc_attr(funio_get_class()->class_sidebar_left); ?>">
					<?php dynamic_sidebar( 'sidebar-product' );?>		
				</div>				
			<?php endif; ?>			
			<div class="<?php echo esc_attr(funio_get_class()->class_detail_product_content); ?> <?php if(is_active_sidebar('sidebar-product') && $sidebar_detail_product == 'left'){ echo "pull-right"; } ?>">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php wc_get_template_part( 'content', 'single-product' ); ?>
				<?php endwhile; // end of the loop. ?>
			</div>
			<?php if($sidebar_detail_product == 'right' && is_active_sidebar('sidebar-product')):?>			
				<div class="bwp-sidebar sidebar-product <?php echo esc_attr(funio_get_class()->class_sidebar_left); ?>">
					<?php dynamic_sidebar( 'sidebar-product' );?>		
				</div>				
			<?php endif; ?>	
			<?php
				/**
				 * woocommerce_after_main_content hook
				 *
				 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
				 */
				do_action( 'woocommerce_after_main_content' );
			?>
		</div>
	</div>
</div>
<?php get_footer(); ?>