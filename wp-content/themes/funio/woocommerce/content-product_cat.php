<?php
/**
 * The template for displaying product category thumbnails within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product_cat.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 4.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$category_style = funio_get_config('category_style','sidebar');
$subcategories_style = funio_get_config('style-subcategories','shop_mini_categories');
$icon_category = $thumbnail_id = "";
$id_category =  is_tax() ? get_queried_object()->term_id : 0;
?>
<li data-id_category="<?php echo esc_attr($category->term_id); ?>" class="product-category product <?php if( $category->term_id == $id_category ){ ?>active<?php } ?>">
	<?php
	/**
	 * woocommerce_before_subcategory hook.
	 *
	 * @hooked woocommerce_template_loop_category_link_open - 10
	 */
	if($subcategories_style != 'shop_mini_categories'){
		if($subcategories_style == "icon_categories"){
			$icon_category = get_term_meta( $category->term_id, 'category_icon', true );
		}else{
			$thumbnail_id         = get_term_meta( $category->term_id, 'thumbnail_id', true );
		}
		if($icon_category || $thumbnail_id){
			do_action( 'woocommerce_before_subcategory', $category );	
			/**
			 * woocommerce_before_subcategory_title hook.
			 *
			 * @hooked woocommerce_subcategory_thumbnail - 10
			 */
			do_action( 'woocommerce_before_subcategory_title', $category );			
		}
	}
	/**
	 * woocommerce_shop_loop_subcategory_title hook.
	 *
	 * @hooked woocommerce_template_loop_category_title - 10
	 */
	do_action( 'woocommerce_shop_loop_subcategory_title', $category );
	/**
	 * woocommerce_after_subcategory_title hook.
	 */
	do_action( 'woocommerce_after_subcategory_title', $category );
	/**
	 * woocommerce_after_subcategory hook.
	 *
	 * @hooked woocommerce_template_loop_category_link_close - 10
	 */
	do_action( 'woocommerce_after_subcategory', $category );
	?>
</li>