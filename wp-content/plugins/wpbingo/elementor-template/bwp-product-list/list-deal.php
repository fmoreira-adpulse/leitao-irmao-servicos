<?php
$widget_id = isset( $widget_id ) ? $widget_id : 'bwp_woo_slider_'.rand().time();

$count = $list->post_count;
$j = 1;
do_action( 'before' ); 
if ( $list -> have_posts() ){ ?>
	<div id="<?php echo $widget_id; ?>" class="bwp_product_list <?php echo esc_attr($layout); ?> <?php if(empty($title1)) echo 'no-title'; ?>">
		<div class="title-deal">
			<?php if($title1) { ?>
			<div class="title-block">   
				<h2 class="font-extrabold"><?php echo esc_html($title1); ?></h2>
			</div> 
			<?php } ?>
			<?php if( $time_deal) : ?>
				<div class="countdown-deal">
					<?php
						$start_time = time();
						$countdown_time = strtotime($time_deal);
						$date = bwp_timezone_offset( $countdown_time );
					?>
					<label><?php echo esc_html__("end in","wpbingo") ?></label>
					<div class="product-countdown"  
						data-day="<?php echo esc_html__("d","wpbingo"); ?>"
						data-hour="<?php echo esc_html__("h","wpbingo"); ?>"
						data-min="<?php echo esc_html__("m","wpbingo"); ?>"
						data-sec="<?php echo esc_html__("s","wpbingo"); ?>"	
						data-date="<?php echo esc_attr( $date ); ?>"  
						data-sttime="<?php echo esc_attr( $start_time ); ?>" 
						data-cdtime="<?php echo esc_attr( $countdown_time ); ?>" 
						data-id="<?php echo $widget_id; ?>">
					</div>
				</div>
			<?php endif;?>
		</div>
		<div class="list-product">
			<div class="product-content">
				<div class="content-product-list">	
					<div class="slider products-list grid slick-carousel" data-slidesToScroll="true" data-dots="<?php echo esc_attr($show_pag);?>"  data-nav="<?php echo esc_attr($show_nav);?>" data-columns4="<?php echo $columns4; ?>" data-columns3="<?php echo $columns3; ?>" data-columns2="<?php echo $columns2; ?>" data-columns1="<?php echo $columns1; ?>" data-columns1440="<?php echo $columns1440; ?>" data-columns="<?php echo $columns; ?>">	
					<?php while($list->have_posts()): $list->the_post();global $product, $post, $wpdb, $average; ?>
						<?php	if( ($j == 1) ||  ( $j % $item_row  == 1 ) || ( $item_row == 1 )) { ?>
							<div class="item-product">
						<?php } ?>
							<div class="items">
								<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product2.php'); ?>
							</div>
						<?php if( ($j == $count) || ($j % $item_row == 0) || ($item_row == 1)){?> 
							</div><!-- #post-## -->
						<?php  } $j++;?>
					<?php endwhile; wp_reset_postdata();?>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
?>