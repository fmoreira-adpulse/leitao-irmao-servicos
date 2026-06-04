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
		<?php  if($link): ?>
			<a class="bwp-video" href="<?php echo esc_url($link); ?>"><i class="fa fa-play" aria-hidden="true"></i></a>
		<?php endif;?>
		<?php if( $title1) : ?>
		<h6 class="title-video"><?php echo esc_html( $title1 ); ?></h6>
		<?php endif; ?>
		<?php if( $description) : ?>
		<div class="description-video"><?php echo esc_html( $description ); ?></a></div>
		<?php endif; ?>
		<?php if( $labelbutton) : ?>
		<div class="button-video"><a href="<?php echo esc_url($linkbutton); ?>"><?php echo esc_html( $labelbutton ); ?></a></div>
		<?php endif; ?>
	</div>
</div>
