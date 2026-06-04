<?php 
global $post, $woocommerce, $product;
$post_thumbnail_id = get_post_thumbnail_id( $post->ID );
$full_size_image   = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );
$image_title       = get_post_field( 'post_excerpt', $post_thumbnail_id );
$placeholder       = has_post_thumbnail() ? 'with-images' : 'without-images';
$wrapper_classes   = apply_filters( 'woocommerce_single_product_image_gallery_classes', array(
	'woocommerce-product-gallery',
	'woocommerce-product-gallery--' . $placeholder,
	'images',
) );
$class= "";
if(funio_image_single_product()->show_thumb && (funio_image_single_product()->position == "left" || funio_image_single_product()->position == "right"))
	$class = "vertical";
?>
<div class="images <?php echo esc_attr($class); ?>">
	<figure class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>">
		<div class="row">
			<?php if(funio_image_single_product()->show_thumb && funio_image_single_product()->position == "left") : ?>
				<div class="<?php echo esc_attr(funio_image_single_product()->class_thumb); ?>">
				<?php do_action( 'woocommerce_product_thumbnails' ); ?>
				</div>
			<?php endif; ?>
			<div class="<?php echo esc_attr(funio_image_single_product()->class_image); ?>">
				<div class="image-additional text-center">
				<?php
				$attributes = array(
					'id'						=> "image", 	
					'title'                   => $image_title,
					'data-src'                => $full_size_image[0],
					'data-large_image'        => $full_size_image[0],
					'data-large_image_width'  => $full_size_image[1],
					'data-large_image_height' => $full_size_image[2],
				);
				if ( has_post_thumbnail() ) {
					$html  = '<div data-thumb="' . get_the_post_thumbnail_url( $post->ID, 'full' ) . '" class="woocommerce-product-gallery__image"><a data-elementor-open-lightbox="default" data-elementor-lightbox-slideshow="image-additional" data-image="' . esc_url( $full_size_image[0] ) . '" href="' . esc_url( $full_size_image[0] ) . '">';
					$html .= get_the_post_thumbnail( $post->ID, 'shop_single', $attributes );
					$html .= '</a></div>';
				} else {
					$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
					$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src() ), esc_html__( 'Awaiting product image', 'funio' ) );
					$html .= '</div>';
				} 
				echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, get_post_thumbnail_id( $post->ID ) ); ?>
				<?php funio_get_video_product(); ?>
				<?php funio_view_product(); ?>
				</div>
			</div>
			<?php if(funio_image_single_product()->show_thumb && (funio_image_single_product()->position == "right" || funio_image_single_product()->position == "bottom")) : ?>
				<div class="<?php echo esc_attr(funio_image_single_product()->class_thumb); ?>">
				<?php do_action( 'woocommerce_product_thumbnails' ); ?>
				</div>
			<?php endif; ?>	
		</div>
	</figure>
</div>