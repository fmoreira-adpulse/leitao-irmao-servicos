<?php 
	get_header();
	$post_single_layout = funio_post_sidebar();
	$funio_settings = funio_global_settings();
?>
	<?php
		// Start the Loop.
		while ( have_posts() ) : the_post();
			get_template_part( 'templates/content-single/content', $post_single_layout);
		endwhile;
	?>
<?php
get_footer();