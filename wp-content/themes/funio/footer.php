	</div><!-- #main -->
		<?php 
			global $funio_page_id;
			$funio_settings = funio_global_settings();
			$footer_style = funio_get_config('footer_style','');
			$footer_style = (get_post_meta( $funio_page_id,'page_footer_style', true )) ? get_post_meta( $funio_page_id, 'page_footer_style', true ) : $footer_style ;
			$header_style = funio_get_config('header_style', ''); 
			$header_style  = (get_post_meta( $funio_page_id, 'page_header_style', true )) ? get_post_meta($funio_page_id, 'page_header_style', true ) : $header_style ;
		?>
		<?php if($footer_style && (get_post($footer_style)) && in_array( 'elementor/elementor.php', apply_filters('active_plugins', get_option( 'active_plugins' )))){ ?>
			<?php $elementor_instance = Elementor\Plugin::instance(); ?>
			<footer id="bwp-footer" class="bwp-footer <?php echo esc_attr( get_post($footer_style)->post_name ); ?>">
				<?php echo funio_render_footer($footer_style);	?>
			</footer>
		<?php }else{
			funio_copyright();
		}?>
	</div><!-- #page -->
	<div class="search-overlay">	
		<div class="container wrapper-search">
			<div class="search-top">
				<h2><?php echo esc_html__("what are you looking for?","funio"); ?></h2>
				<div class="close-search"><?php echo esc_html__("close","funio"); ?><i class="icon_close"></i></div>
			</div>
			<?php funio_search_form_product(); ?>		
		</div>	
	</div>
	<div class="bwp-quick-view">
	</div>	
	<?php 
		$back_active = funio_get_config('back_active');
		if($back_active && $back_active == 1):
	?>
	<div class="back-top">
		<i class="arrow_carrot-up"></i>
	</div>
	<?php endif;?>
	<?php if((isset($funio_settings['show-newletter']) && $funio_settings['show-newletter']) && is_active_sidebar('newletter-popup-form') && function_exists('funio_popup_newsletter')) : ?>		
		<?php funio_popup_newsletter(); ?>
	<?php endif;  ?>
	<?php funio_login_form(); ?>
	<?php wp_footer(); ?>
</body>
</html>