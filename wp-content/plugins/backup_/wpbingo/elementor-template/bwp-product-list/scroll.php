<?php
$widget_id = isset( $widget_id ) ? $widget_id : 'bwp_woo_slider_'.rand().time();
$count = $list->post_count;
$j = 1;
do_action( 'before' ); 
if ( $list -> have_posts() ){ ?>
	<div id="<?php echo $widget_id; ?>" class="bwp_product_list scroll-list <?php echo esc_attr($layout); ?> <?php if(empty($title1)) echo 'no-title'; ?>">
		<?php if($title1) { ?>
		<div class="title-block">   
			<h2><?php echo esc_html($title1); ?></h2>
			<?php if($description) { ?>
			<div class="page-description"><?php echo esc_html($description); ?></div>
			<?php } ?> 
		</div> 
		<?php } ?>
		<div class="content-scroll-list">
			<div class="content-list-product">
				<div class="list-product" data-columns4="<?php echo esc_attr($columns4); ?>" data-columns3="<?php echo esc_attr($columns3); ?>" data-columns2="<?php echo esc_attr($columns2); ?>" data-columns1="<?php echo esc_attr($columns1); ?>" data-columns="<?php echo esc_attr($columns); ?>">
					<div class="product-content products-list grid">
						<?php while($list->have_posts()): $list->the_post();global $product, $post, $wpdb, $average; ?>
						<div class="item-product">
							<?php if ($style_product == 1) { ?>
								<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product.php'); ?>
							<?php }elseif ($style_product == 2){ ?>
								<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product2.php'); ?>
							<?php }elseif ($style_product == 3){ ?>
								<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product3.php'); ?>
							<?php }elseif ($style_product == 4){ ?>
								<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product4.php'); ?>
							<?php }elseif ($style_product == 5){ ?>
								<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product5.php'); ?>
							<?php } ?>
						</div>
						<?php endwhile; wp_reset_postdata();?>
					</div>
				</div>
				<div class="scrollbar">
					<div class="handle">
						<div class="mousearea"><?php echo esc_html__("Scroll Me","wpbingo"); ?><i class="icon-right-arrow"></i></div>
					</div>
				</div>
				<?php if($show_nav) { ?>
					<div class="controls">
						<button class="btn prev"><i class="arrow_carrot-left"></i></button>
						<button class="btn next"><i class="arrow_carrot-right"></i></button>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php
	}
?>