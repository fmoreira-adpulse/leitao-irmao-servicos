<?php 
	get_header(); 
	$funio_settings = funio_global_settings();
?>
<div class="page-404">
	<div class="content-page-404">
		<div class="title-error">
			<?php if(isset($funio_settings['title-error']) && $funio_settings['title-error']){
				echo esc_html($funio_settings['title-error']);
			}else{
				echo esc_html__('404', 'funio');
			}?>
		</div>
		<div class="sub-title">
			<?php if(isset($funio_settings['sub-title']) && $funio_settings['sub-title']){
				echo esc_html($funio_settings['sub-title']);
			}else{
				echo esc_html__("Oops! That page can't be found.", "funio");
			}?>
		</div>
		<div class="sub-error">
			<?php if(isset($funio_settings['sub-error']) && $funio_settings['sub-error']){
				echo esc_html($funio_settings['sub-error']);
			}else{
				echo esc_html__("We're really sorry but we can't seem to find the page you were looking for.", 'funio');
			}?>
		</div>
		<a class="btn" href="<?php echo esc_url( home_url('/') ); ?>">
			<?php if(isset($funio_settings['btn-error']) && $funio_settings['btn-error']){
				echo esc_html($funio_settings['btn-error']);}
			else{
				echo esc_html__('Back The Homepage', 'funio');
			}?>
		</a>
	</div>
</div>
<?php
get_footer();