
<?php
$widget_id = isset( $widget_id ) ? $widget_id : 'bwp_woo_slider_'.rand().time();
$class_col_lg = ($columns == 5) ? '2-4'  : (12/$columns);
$class_col_md = ($columns1 == 5) ? '2-4'  : (12/$columns1);
$class_col_sm = ($columns2 == 5) ? '2-4'  : (12/$columns2);
$class_col_xs = ($columns3 == 5) ? '2-4'  : (12/$columns3);
$attributes = 'col-xl-'.$class_col_lg .' col-lg-'.$class_col_md .' col-md-'.$class_col_sm .' col-'.$class_col_xs; 
do_action( 'before' ); 
if ( $list -> have_posts() ){ ?>
	<div id="<?php echo $widget_id; ?>" class="bwp_product_list <?php echo esc_attr($layout); ?> <?php echo esc_attr($class); ?> <?php if(empty($title1)) echo 'no-title'; ?>">
		<?php if($title1 || $description || $label) { ?>
		<div class="product-list-top">
			<?php if($title1) { ?>
			<div class="title-block"> 
				<h2><?php echo esc_html($title1); ?></h2>
			</div> 
			<?php } ?>
		</div>
		<?php } ?>
		<div class="content products-list grid row">	
		<?php while($list->have_posts()): $list->the_post();
		global $product, $post, $wpdb, $average; ?>
			<div class="item <?php echo esc_attr($attributes); ?>">
				<div class="item-product">
					<div class="item-thumb">
						<a href="<?php echo get_permalink( $product->get_id() ); ?>"><img src="<?php echo wp_get_attachment_url( $product->get_image_id() ); ?>" alt="<?php echo esc_attr__("Image Product","wpbingo"); ?>" /></a>
					</div>
					<div class="content-bottom">
						<?php if ( $rating_html = wc_get_rating_html( $product->get_average_rating() ) ) { ?>
							<div class="rating">
								<?php echo wc_get_rating_html( $product->get_average_rating() ); ?>
								<div class="review-count">
									( <?php echo esc_attr($product->get_review_count());?><?php echo esc_html__(' review','wpbingo');?> )
								</div>
							</div>
						<?php }else{ ?>
							<div class="rating none">
								<div class="star-rating none"></div>
								<div class="review-count">
									( 0 <?php echo esc_html__(' reviews','wpbingo');?> )
								</div>
							</div>
						<?php } ?>
						<div class="item-title">
							<a href="<?php echo esc_url(get_permalink( $product->get_id() )); ?>"><?php echo $product->get_title(); ?></a>
						</div>
						<div class="price">
							<?php echo $product->get_price_html(); ?>
						</div>
					</div>
				</div>
			</div>
		<?php endwhile; wp_reset_postdata();?>
		</div>			
	</div>
	<?php
	}
?>