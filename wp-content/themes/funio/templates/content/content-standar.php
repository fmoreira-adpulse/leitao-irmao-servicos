<?php 
	global $instance;
	$format = get_post_format();
?>
<div class="list-post">
    <article id="post-<?php esc_attr(the_ID()); ?>" <?php post_class(); ?>>
		<?php if( empty($format) || $format == 'image' || $format == 'quote') : ?>	
			<?php if ( get_the_post_thumbnail() ){?>
			<div class="entry-thumb single-thumb">
				<a class="post-thumbnail" href="<?php echo esc_url(the_permalink()); ?>" title="<?php the_title_attribute(); ?>">
					<?php the_post_thumbnail( 'funio-full-width' )?>				
				</a>
			</div>		
			<?php } ?>
		<?php elseif( $format == 'video' || $format == 'audio' ) : ?>
			<?php if ( get_the_post_thumbnail() ){?>
			<div class="entry-thumb single-thumb">
				<a class="post-thumbnail" href="<?php esc_url(the_permalink()); ?>" title="<?php echo the_title_attribute(); ?>">
					<?php the_post_thumbnail( 'funio-full-width', array( 'alt' => get_the_title() ) );	?>      
				</a> 
			</div>
			<?php } ?>
		<?php elseif( $format == 'gallery' ) : 
			$ids = "";	
			if(preg_match_all('/\[gallery(.*?)?\]/', get_post($instance['post_id'])->post_content, $matches)){
				$attrs = array();
					if (count($matches[1])>0){
						foreach ($matches[1] as $m){
							$attrs[] = shortcode_parse_atts($m);
						}
					}
					if (count($attrs)> 0){
						foreach ($attrs as $attr){
							if (is_array($attr) && array_key_exists('ids', $attr)){
								$ids = $attr['ids'];
								break;
							}
						}
					}
				?>
				<?php if($ids){ ?>
				<div class="entry-thumb">
					<div id="gallery_slider_<?php echo esc_attr($post->ID); ?>" class="gallery-slider">	
						<div class="slick-carousel" data-columns4="1" data-columns3="1" data-columns2="1" data-columns1="1" data-columns="1" data-nav="true">
								<?php
									if($ids){
										$ids = explode(',', $ids);						
										foreach ( $ids as $i => $id ){ ?>
											<div class="item">
													<?php echo wp_get_attachment_image($id, 'funio-full-width'); ?>
											</div>
										<?php }	
									}
								?>
						</div>
					</div>
				</div>
				<?php }
			} ?>			
		<?php endif; ?>			
		<div class="post-content">
			<?php if ( is_sticky() && is_home() && ! is_paged() ) { ?>
				<span class="sticky-post"><?php echo esc_html__( 'Featured', 'funio' ) ?></span>
			<?php } ?>
			<?php if ( in_array( 'category', get_object_taxonomies( get_post_type() ) ) && funio_categorized_blog() ) : ?>
				<div class="cat-links"><?php echo get_the_category_list(', '); ?></div>
			<?php endif; ?>	
        	<h3 class="entry-title"><a href="<?php echo esc_url(the_permalink()) ?>"><?php echo the_title(); ?></a></h3>
			<div class="entry-meta-head">
				<?php if (funio_get_config('archives-author')) { ?>
					<div class="entry-author">
						<span class="entry-meta-link"><i class="wpb-icon-user"></i><?php echo the_author_posts_link(); ?></span>
					</div>
				<?php } ?>
				<span class="comments-link">
					<i class="wpb-icon-chat"></i>
					<?php 
					$comment_count =  wp_count_comments(get_the_ID())->total_comments;
					if($comment_count > 0) {
					?>
						<?php if($comment_count == 1){?>
							<?php echo esc_attr($comment_count) .'<span>'. esc_html__(' Comment', 'funio').'</span>'; ?>
						<?php }else{ ?>
							<?php echo esc_attr($comment_count) .'<span>'. esc_html__(' Comments', 'funio').'</span>'; ?>
						<?php } ?>
					<?php }else{ ?>
						<?php echo esc_attr($comment_count) .'<span>'. esc_html__(' Comments', 'funio').'</span>'; ?>
					<?php } ?>
				</span>
			</div>
			<?php
        		if (funio_get_config('blog-excerpt')) {
                    echo funio_get_excerpt( funio_get_config('list-blog-excerpt-length',70), true);
                }
			?>
    	</div>	
    </article><!-- #post-## -->
</div>