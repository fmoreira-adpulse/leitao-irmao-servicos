<?php $j = 1; ?>
<?php while($list->have_posts()): $list->the_post();
	global $product, $post, $wpdb, $average;
	if( ( $j % $item_row ) == 1 && $item_row !=1) { ?>
	<div class="item">
	<?php } ?>
		<div class="item-product <?php echo $class_col; ?>">
			<?php
			if($content_product)
				include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product'.esc_attr($content_product).'.php'); 
			else
				include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'content-product.php'); 
			?>
		</div>
	<?php if( ($j % $item_row == 0 || $j == $list->post_count) && $item_row !=1  ){?> 
	</div>
	<?php  } $j++;?>			
<?php endwhile; wp_reset_postdata(); ?>