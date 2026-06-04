<?php if($settings['list_tab']){ ?>
	<?php $j = 0; ?>
	<div class="bwp-slider <?php echo esc_attr($layout); ?>">
		<?php if($title1) { ?>
		<div class="title"><?php echo esc_html($title1) ?></div>
		<?php } ?>
		<div class="slick-carousel slick-carousel-center"  data-centerMode="true" data-nav="<?php echo esc_attr($show_nav);?>" data-dots="<?php echo esc_attr($show_pag);?>" data-columns4="<?php echo esc_attr($columns4); ?>" data-columns3="<?php echo esc_attr($columns3); ?>" data-columns2="<?php echo esc_attr($columns2); ?>" data-columns1="<?php echo esc_attr($columns1); ?>" data-columns="<?php echo esc_attr($columns); ?>" >
			<?php foreach ($settings['list_tab'] as  $item){ ?>
				<?php if( ( $j % $item_row ) == 0 && $item_row !=1) { ?>
					<div class="item-slider">
				<?php } ?>
				<div class="item">
					<div class="content">
						<?php if( $item['image_slider'] && $item['image_slider']['url'] ){ ?>
							<div  class="image-slider">
								<a data-elementor-open-lightbox="default" data-elementor-lightbox-slideshow="wpbingo-image-gallery"  href="<?php echo esc_url($item['image_slider']['url']); ?>">
									<img src="<?php echo esc_url($item['image_slider']['url']); ?>" alt="<?php echo esc_attr__('Image Slider','wpbingo'); ?>">
								</a>
							</div>
						<?php } ?>
					</div>				
				</div>
				<?php if((($j + 1) % $item_row == 0 || $j== count($settings['list_tab'])) && $item_row !=1  ){?>
					</div>
				<?php  } $j++;?>
			<?php } ?>
		</div>
	</div>
<?php }?>