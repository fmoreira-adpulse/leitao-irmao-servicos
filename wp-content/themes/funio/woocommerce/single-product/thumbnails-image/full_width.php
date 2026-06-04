<?php
global $post, $product, $woocommerce;
$columns = 	funio_image_single_product()->product_count_thumb;
$attachment_ids = $product->get_gallery_image_ids();
$data_product = $product->get_data();
$image_id = $data_product['image_id'] ? $data_product['image_id'] : array();
$product_thumbs = funio_get_config("product-thumbs",true);
if($image_id )
	array_unshift ($attachment_ids,$image_id);
if ( $attachment_ids ) {
	$loop 		= 0;
	?>
	<div class="image-thumbnail slick-carousel" data-asnavfor=".image-additional" data-vertical="true" data-centermode="true" data-focusonselect="true" data-columns4="3" data-columns3="3" data-columns2="3" data-columns1="3" data-columns="3" data-nav="true" <?php echo esc_attr(funio_image_single_product()->class_data_image); ?>>
	<?php
		foreach ( $attachment_ids as $attachment_id ) {		
			$image_link = wp_get_attachment_url( $attachment_id );
			if ( ! $image_link )
				continue;
			$image_title 	= esc_attr( get_the_title( $attachment_id ) );
			$image_caption 	= esc_attr( get_post_field( 'post_excerpt', $attachment_id ) );
            $image       = wp_get_attachment_image( $attachment_id, apply_filters( 'funio-blog-sidebar', 'shop_catalog' ), 0, $attr = array(
                'title' => $image_title,
                'alt'   => $image_title,
                'data-zoom-image'=> $image_link
                ) ); ?>
			<div class="img-thumbnail">
				<span class="img-thumbnail-scroll">
				<?php echo wp_kses($image,'social'); ?>
				</span>
			</div>
			<?php $loop++;
		}
	?>
	</div>
	<?php
}