<div class="bwp-widget-video <?php echo esc_attr($class); ?> <?php echo esc_attr($layout); ?>">	
	<div class="bg-video">		
		<div class="video-wrapper videos">
			<?php  if($image): ?>
			<div class="bwp-image">
				<div class="videoThumb">
					<img class="img-responsive" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr__("Image Video","wpbingo"); ?>" />
				</div>
			</div>
			<?php endif;?>
		</div>
	</div>
	<div class="content">
		<?php if( $title1) : ?>
		<h2 class="title-video"><?php echo esc_html( $title1 ); ?></h2>
		<?php endif; ?>
		<?php if( $description) : ?>
		<div class="description-video"><?php echo esc_html( $description ); ?></a></div>
		<?php endif; ?>
		<?php  if($link): ?>
			<a class="bwp-video" href="<?php echo esc_url($link); ?>"><i class="fa fa-play" aria-hidden="true"></i></a>
		<?php endif;?>
	</div>
</div>
