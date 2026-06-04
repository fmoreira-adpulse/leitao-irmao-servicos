
<?php
$widget_id = isset( $widget_id ) ? $widget_id : 'bwp_woo_slider_'.rand().time();
$class_col_lg = ($columns == 5) ? '2-4'  : (12/$columns);
$class_col_md = ($columns1 == 5) ? '2-4'  : (12/$columns1);
$class_col_sm = ($columns2 == 5) ? '2-4'  : (12/$columns2);
$class_col_xs = ($columns3 == 5) ? '2-4'  : (12/$columns3);
$attributes = 'col-xl-'.$class_col_lg .' col-lg-'.$class_col_md .' col-md-'.$class_col_sm .' col-'.$class_col_xs; 
do_action( 'before' );
$count = $list->post_count;
$j = 1;
if ( $list -> have_posts() ){ ?>
	<div id="<?php echo $widget_id; ?>" class="bwp_product_list <?php echo esc_attr($layout); ?> <?php echo esc_attr($class); ?> <?php if(empty($title1)) echo 'no-title'; ?>">
		<?php if($title1 || $description || $label) { ?>
		<div class="product-list-top">
			<?php if($title1) { ?>
			<div class="title-block">
				<?php if($description) { ?>
				<div class="page-description"><?php echo esc_html($description); ?></div>
				<?php } ?>  
				<h2><?php echo esc_html($title1); ?></h2>
			</div> 
			<?php } ?>
			<?php if($label) { ?>
			<div class="btn-product-list">
				<a href="<?php echo esc_html($link); ?>"><?php echo esc_html($label);?></a>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
		<div class="content products-list grid row">	
		<?php while($list->have_posts()): $list->the_post();
		global $product, $post, $wpdb, $average; ?>
			<?php	if( ($j == 1) ||  ( $j % 5  == 1 ) ||  ( $j % 5  == 2 ) ) { ?>
				<div class="item-product <?php echo $attributes ?> <?php if($j % 5  == 2) { ?>item-for<?php } ?>">
			<?php } ?>
				<div class="item">
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
					<?php }elseif ($style_product == 6){ ?>
						<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product6.php'); ?>
					<?php }elseif ($style_product == 7){ ?>
						<?php include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product7.php'); ?>
					<?php } ?>
				</div>
			<?php if( ($j == $count) || ($j % 5 == 0) ||  ( $j % 5  == 1 ) ){?> 
				</div><!-- #post-## -->
			<?php  } $j++;?>
		<?php endwhile; wp_reset_postdata();?>
		</div>	
	</div>
	<?php
	}
?>