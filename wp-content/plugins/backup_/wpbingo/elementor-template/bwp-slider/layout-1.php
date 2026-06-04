<?php if($settings['list_tab']){ ?>
	<?php $j = 0; ?>
	<div class="bwp-slider <?php echo esc_attr($layout); ?>">
		<div class="slick-carousel slick-carousel-center"  data-centerMode="true" data-nav="<?php echo esc_attr($show_nav);?>" data-dots="<?php echo esc_attr($show_pag);?>" data-columns4="<?php echo esc_attr($columns4); ?>" data-columns3="<?php echo esc_attr($columns3); ?>" data-columns2="<?php echo esc_attr($columns2); ?>" data-columns1="<?php echo esc_attr($columns1); ?>" data-columns="<?php echo esc_attr($columns); ?>" >
			<?php foreach ($settings['list_tab'] as  $item){ ?>
				<div class="item">
					<div class="content-image">
						<?php if( $item['image'] && $item['image']['url'] ){ ?>
							<a href="<?php echo wp_kses_post($item['link_slider']); ?>">
								<img src="<?php echo esc_url($item['image']['url']); ?>" alt="<?php echo esc_attr__('Image Slider','wpbingo'); ?>">
							</a>
						<?php } ?>
					</div>
					<div class="slider-content">
						<div class="content-info">
							<?php if( $item['subtitle'] ){ ?>
								<div class="subtitle">
									<?php echo wp_kses_post($item['subtitle']); ?>
								</div>
							<?php } ?>
							<?php if( $item['title_slider'] ){ ?>
								<h2 class="title"><?php echo esc_html($item['title_slider']); ?></h2>
							<?php } ?>
							<?php if( $item['description_slider'] ){ ?>
								<div class="description">
									<?php echo wp_kses_post($item['description_slider']); ?>
								</div>
							<?php } ?>
							<?php if( $item['title_button_slider'] ){ ?>
								<div class="button">
									<a href="<?php echo wp_kses_post($item['link_slider']); ?>"><?php echo wp_kses_post($item['title_button_slider']); ?></a>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
<?php }?>