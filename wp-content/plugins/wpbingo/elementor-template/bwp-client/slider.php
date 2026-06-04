<div class="bwp-client <?php echo esc_attr($layout); ?>">
 <div class="block">
 	<?php if(isset($title1) && $title1) {?>
	<div class="block-title">
		<div class="client-title">
			<?php if($subtitle) { ?>
			<div class="subtitle"><?php echo $subtitle; ?></div>
			<?php } ?> 
			<?php
				echo '<h2>'. esc_html($title1) .'</h2>';
			?>
		</div>
	</div>
	<?php } ?>
	<div class="block_content">
		<div class="client">
			<div class="slick-carousel" data-dots="<?php echo esc_attr($show_pag);?>" data-item_row="<?php echo esc_attr($item_row); ?>" data-nav="<?php echo esc_attr($show_nav);?>" data-columns4="<?php echo esc_attr($columns4); ?>" data-columns3="<?php echo esc_attr($columns3); ?>" data-columns2="<?php echo esc_attr($columns2); ?>" data-columns1="<?php echo esc_attr($columns1); ?>" data-columns="<?php echo esc_attr($columns); ?>" data-columns1440="<?php echo esc_attr($columns1440); ?>">
				<?php foreach ($settings['list_tab'] as  $item){ ?>
					<?php if( $item['image_slider'] && $item['image_slider']['url'] ){ ?>
						<div class="item">
							<div  class="item-image">
								<a class="image" href="<?php echo esc_url($item['link_client']); ?>">
									<img src="<?php echo esc_url($item['image_slider']['url']); ?>" alt="<?php echo esc_attr__('Image Slider','wpbingo'); ?>">
								</a>
							</div>
						</div>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
	</div>
 </div>
</div>