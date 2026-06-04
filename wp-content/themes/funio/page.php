<?php 
	get_header();	
	$sidebar_blog = "";
	if(is_active_sidebar('sidebar-blog')){
		$sidebar_blog = funio_blog_sidebar();
	}
	$layout_blog = funio_blog_view();
	$class_content_blog = 'blog-content-'.esc_attr($layout_blog);
	$j=1;
?>
<div class="container">
	<div class="row">
		<?php if($sidebar_blog == 'left' && is_active_sidebar('sidebar-blog')):?>		
		<div class="bwp-sidebar sidebar-blog <?php echo esc_attr(funio_get_class()->class_sidebar_left); ?>">
			<?php dynamic_sidebar( 'sidebar-blog' );?>	
		</div>
		<?php endif; ?>
		<div class="cate-post-content <?php echo esc_attr($sidebar_blog); ?> <?php if(is_active_sidebar('sidebar-blog')){ echo esc_attr(funio_get_class()->class_blog_content); }else{ echo "col-lg-12 col-md-12 col-sm-12 col-12"; } ?>">
			<div id="main-content" class="main-content">
				<div id="primary" class="content-area">
					<div id="content" class="site-content" role="main">
						<?php
							// Start the Loop.
							while ( have_posts() ) : the_post();
								// Include the page content template.
								get_template_part( 'templates/content/content', 'page');
								// If comments are open or we have at least one comment, load up the comment template.
								if ( comments_open() || get_comments_number() ) {
									comments_template();
								}
							endwhile;
						?>
					</div><!-- #content -->
				</div><!-- #primary -->
			</div><!-- #main-content -->
		</div> 
		<?php if($sidebar_blog == 'right' && is_active_sidebar('sidebar-blog')): ?>			
			<div class="bwp-sidebar sidebar-blog <?php echo esc_attr(funio_get_class()->class_sidebar_right); ?>">
				<?php dynamic_sidebar('sidebar-blog');?>	
			</div>				
		<?php endif; ?>
    </div>
</div>
<?php
get_footer();