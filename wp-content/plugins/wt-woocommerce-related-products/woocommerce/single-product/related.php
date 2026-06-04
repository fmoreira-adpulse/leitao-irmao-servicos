<?php
/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     4.9.0
 */
if (!defined('ABSPATH')) {
	exit;
}


if ( ! function_exists( 'crp_get_all_product_ids_from_cat_ids' ) ) {

	/**
	* Get all product ids from the given category ids
	* @since 1.3.7
	* @return array  
	*/
	function crp_get_all_product_ids_from_cat_ids( array $cat_ids ) {
            
            $all_ids = $total= array();
            
            if($cat_ids){
                $cat_ids = array_reverse($cat_ids);
                foreach ($cat_ids as $ckey => $cat_value) {
		$all_ids = get_posts(
			array(
				'post_type'		 => 'product',
				'numberposts'	 => -1,
				'post_status'	 => 'publish',
				'fields'		 => 'ids',
				'tax_query'		 => array(
					array(
						'taxonomy'	 => 'product_cat',
						'field'		 => 'term_id',
                        'terms'		 => array($cat_value),
						'operator'	 => 'IN',
					)
				),
			)
		);
                    $total = array_merge($total,$all_ids);
                    unset($all_ids);
                }
            }
            $all_ids = array_unique($total);

//		$all_ids = get_posts(
//			array(
//				'post_type'		 => 'product',
//				'numberposts'	 => -1,
//				'post_status'	 => 'publish',
//				'fields'		 => 'ids',
//				'tax_query'		 => array(
//					array(
//						'taxonomy'	 => 'product_cat',
//						'field'		 => 'term_id',
//						'terms'		 => $cat_ids,
//						'operator'	 => 'IN',
//					)
//				),
//			)
//		);

		return $all_ids;
	}
}

if ( ! function_exists( 'crp_get_all_product_ids_from_tag_ids' ) ) {

	/**
	* Get all product ids from the given tag ids
	* @since 1.3.7
	* @return array  
	*/
	function crp_get_all_product_ids_from_tag_ids( array $tag_ids ) {
		$all_ids = get_posts(
			array(
				'post_type'		 => 'product',
				'numberposts'	 => -1,
				'post_status'	 => 'publish',
				'fields'		 => 'ids',
				'tax_query'		 => array(
					array(
						'taxonomy'	 => 'product_tag',
						'field'		 => 'term_id',
						'terms'		 => $tag_ids,
						'operator'	 => 'IN',
					)
				),
			)
		);

		return $all_ids;
	}
}

if ( ! function_exists( 'crp_get_all_product_ids_from_attr_ids' ) ) {

	/**
	* Get all product ids from the given attributes
	* @since 1.4.0
	* @return array  
	*/
	function crp_get_all_product_ids_from_attr_ids( array $attr_data ) {
	
		$tax_query = array( 'relation'=> 'OR' );
		foreach ($attr_data as $attr_name => $attr_term_ids) {
			$tax_query[] = array(
				'taxonomy'        => "pa_$attr_name",
				'terms'           =>  $attr_term_ids,
				'operator'        => 'IN',
			);
		}
		$all_ids = new WP_Query(
			array(
				'post_type'		 => array('product', 'product_variation'),
				'posts_per_page'	 => -1,
				'post_status'	 => 'publish',
				'fields'		 => 'ids',
				'tax_query' => $tax_query
			)
		);

		if( $all_ids->have_posts() ) {
			return $all_ids->posts;
		}    

		return array();
	}
}

$global_related_by = (array) apply_filters( 'wt_crp_global_related_by', get_option('custom_related_products_crp_related_by', array('category')) );
$number_of_products = get_option('custom_related_products_crp_number', 3);
$slider_state = get_option('custom_related_products_slider','enable');
if ('enable' !== $slider_state) {
	$number_of_products = class_exists('Custom_Related_Products') ? Custom_Related_Products::wt_get_device_type() : '3';
}
$number_of_products = apply_filters('wt_related_products_number', $number_of_products);

if ( $related_products || !empty($global_related_by) ) :

?>

	<section class="related products wt-related-products" style="opacity: 0; transition: opacity 0.2s ease;">

        <?php
		global $post;

		// when rendering through shortcode
		if (isset($shortcode_post)) {

			$post = $shortcode_post;
		}
		
		$working_mode = class_exists('Custom_Related_Products') ? Custom_Related_Products::get_current_working_mode() : '';

		if ( 'custom' === $working_mode ) 
		{

			$current_post_id = $post->ID;
			global $sitepress;
			$use_primary_id_wpml = apply_filters( 'wt_crp_use_primary_id_wpml', get_option('custom_related_products_use_primary_id_wpml') );
			if( 'enable' === $use_primary_id_wpml && isset( $sitepress ) && defined('ICL_LANGUAGE_CODE') ) {
				$default_lang = $sitepress->get_default_language();
				if( $default_lang != ICL_LANGUAGE_CODE && function_exists('icl_object_id') ) {
					$default_id = icl_object_id ($post->ID, "product", false, $default_lang);
					$default_post = get_post( $default_id );
					$post = $default_post;
				}
			}

			$reselected = get_post_meta($post->ID, 'selected_ids', true);

			if (!empty($reselected)) {
				add_post_meta($post->ID, '_crp_related_ids', $reselected);
			}

			$related = apply_filters( 'wt_crp_related_product_ids', array_filter(array_map('absint', (array) get_post_meta($post->ID, '_crp_related_ids', true))));

			//gets selected related categories
			$related_categories_ids = apply_filters( 'wt_crp_related_category_ids',array_filter(array_map('absint', (array) get_post_meta($post->ID, '_crp_related_product_cats', true))));
				
			//gets selected related tags
			$related_tags_ids = apply_filters( 'wt_crp_related_tag_ids', get_post_meta($post->ID, '_crp_related_product_tags', true) );
			
			//gets selected related attributes
			$related_attr_ids = apply_filters( 'wt_crp_related_attribute_ids', get_post_meta($post->ID, '_crp_related_product_attr', true) );

			if(!empty($related) || !empty($related_categories_ids) || !empty($related_tags_ids) || !empty($related_attr_ids)) {

				if (!empty($related_categories_ids)) {
					$all_ids = crp_get_all_product_ids_from_cat_ids( $related_categories_ids );

					if (!empty($related)) {
						$related = array_merge($all_ids, $related);
					} else {
						$related = $all_ids;
					}
				}
	
				if (!empty($related_tags_ids) && is_array($related_tags_ids)) {
					$all_ids = crp_get_all_product_ids_from_tag_ids( $related_tags_ids );

					if (!empty($related)) {
						$related = array_merge($all_ids, $related);
					} else {
						$related = $all_ids;
					}
				}

				if (!empty($related_attr_ids)) {

					$all_ids = crp_get_all_product_ids_from_attr_ids( $related_attr_ids );

					if (!empty($related)) {
						$related = array_merge($all_ids, $related);
					} else {
						$related = $all_ids;
					}
				}
			} elseif (!empty($global_related_by)) {

				$related = array();
				$all_related_products = array();
				
				// Ensure both tags and categories are selected
				if (in_array('tag', $global_related_by) && in_array('category', $global_related_by)) {
					$product_tag_ids = array();
					$deepest_child_cat_id = null;
					$parent_category_ids = array();

					// Get current product's tags
					$tag_terms = get_the_terms($post->ID, 'product_tag');
					if (!empty($tag_terms) && !is_wp_error($tag_terms)) {
						foreach ($tag_terms as $term) {
							$product_tag_ids[] = $term->term_id;
						}
					}

					// Get current product's categories and parent category
					$cat_terms = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'all'));
					if (!empty($cat_terms) && !is_wp_error($cat_terms)) {
						$deepest_child_cat_id = array_reduce($cat_terms, function ($carry, $term) {
							if (!$carry || $term->parent > $carry->parent) {
								return $term;
							}
							return $carry;
						});
						$deepest_child_cat_id = $deepest_child_cat_id->term_id;

						// Collect parent categories
						foreach ($cat_terms as $term) {
							if ($term->parent > 0) {
								$parent_category_ids[] = $term->parent;
							}
						}
					}

					// First Priority: Products matching both deepest child category and tags
					if (!empty($product_tag_ids) && $deepest_child_cat_id) {
						$args = array(
							'post_type' => 'product',
							'posts_per_page' => -1,
							'post__not_in' => array($post->ID),
							'tax_query' => array(
								'relation' => 'AND',
								array(
									'taxonomy' => 'product_tag',
									'field' => 'id',
									'terms' => $product_tag_ids,
								),
								array(
									'taxonomy' => 'product_cat',
									'field' => 'id',
									'terms' => $deepest_child_cat_id,
								)
							)
						);
						$both_matches = get_posts($args);
						$related = array_merge($related, wp_list_pluck($both_matches, 'ID'));
					}

					// Second Priority: Products from the deepest child category only
					if ($deepest_child_cat_id && count($related) < $number_of_products) {
						$args = array(
							'post_type' => 'product',
							'posts_per_page' => -1,
							'post__not_in' => array_merge(array($post->ID), $related),
							'tax_query' => array(
								array(
									'taxonomy' => 'product_cat',
									'field' => 'id',
									'terms' => $deepest_child_cat_id,
								)
							)
						);
						$cat_matches = get_posts($args);
						$related = array_merge($related, wp_list_pluck($cat_matches, 'ID'));
					}

					// Third Priority: Products with matching tags, sorted by the number of common tags
					if (!empty($product_tag_ids) && count($related) < $number_of_products) {
						$args = array(
							'post_type' => 'product',
							'posts_per_page' => -1,
							'post__not_in' => array_merge(array($post->ID), $related),
							'tax_query' => array(
								array(
									'taxonomy' => 'product_tag',
									'field' => 'id',
									'terms' => $product_tag_ids,
								)
							)
						);
						$tag_matches = get_posts($args);

						// Score products by number of matching tags
						$scored_products = array();
						foreach ($tag_matches as $product) {
							$product_tags = wp_get_post_terms($product->ID, 'product_tag', array('fields' => 'ids'));
							$matching_tags = array_intersect($product_tag_ids, $product_tags);
							$scored_products[] = array(
								'id' => $product->ID,
								'score' => count($matching_tags),
							);
						}

						// Sort by number of matching tags
						usort($scored_products, function ($a, $b) {
							return $b['score'] - $a['score'];
						});

						$tag_sorted_ids = wp_list_pluck($scored_products, 'id');
						$related = array_merge($related, $tag_sorted_ids);
					}

					// Fourth Priority: Fill remaining slots with products from parent category
					if (!empty($parent_category_ids) && count($related) < $number_of_products) {
						$args = array(
							'post_type' => 'product',
							'posts_per_page' => $number_of_products - count($related),
							'post__not_in' => array_merge(array($post->ID), $related),
							'tax_query' => array(
								array(
									'taxonomy' => 'product_cat',
									'field' => 'id',
									'terms' => $parent_category_ids,
								)
							)
						);
						$parent_matches = get_posts($args);
						$related = array_merge($related, wp_list_pluck($parent_matches, 'ID'));
					}

					// Ensure unique and limit to required number of products
					$related = array_slice(array_unique($related), 0, $number_of_products);
				}
			
				// When only category is selected
				elseif (count($global_related_by) === 1 && in_array('category', $global_related_by)) {
					$product_cat_ids = [];
					$sub_category_ids = [];
					$parent_category_ids = [];
					
					$prod_terms = wp_get_post_terms($post->ID, 'product_cat', array("orderby" => "parent"));
				
					if (!empty($prod_terms) && !is_wp_error($prod_terms)) {
						$category_count = count($prod_terms);
						$term_ids = array_column($prod_terms, 'term_id');
				
						foreach ($prod_terms as $prod_term) {
							$has_term_id = false;
							
							// Get child categories (subcategories)
							$children = get_terms([
								'taxonomy'   => 'product_cat',
								'parent'     => $prod_term->term_id,
								'hide_empty' => false
							]);
				
							foreach ($children as $child) {
								if (in_array($child->term_id, $term_ids)) {
									$has_term_id = true;
									break;
								}
							}
				
							if ( 0 === count($children) || !$has_term_id) {
								// If no children, it is the deepest subcategory
								$sub_category_ids[] = $prod_term->term_id;
							} else {
								// Otherwise, add it to parent categories
								$parent_category_ids[] = $prod_term->term_id;
							}
						}
				
						// Ensure unique category IDs
						$sub_category_ids = array_unique($sub_category_ids);
						$parent_category_ids = array_unique($parent_category_ids);
				
						$related_products = [];
				
						// Fetch products from subcategories first
						if (!empty($sub_category_ids)) {
							$sub_cat_products = crp_get_all_product_ids_from_cat_ids($sub_category_ids);
							$related_products = array_merge($related_products, $sub_cat_products);
						}
				
						// If not enough products, fetch from parent categories
						if (count($related_products) < $number_of_products) {
							$parent_cat_products = crp_get_all_product_ids_from_cat_ids($parent_category_ids);
							$related_products = array_merge($related_products, $parent_cat_products);
						}
				
						// Ensure unique products & remove the current product
						$related_products = array_unique($related_products);
						$related_products = array_diff($related_products, [$post->ID]);
				
						// Limit the number of products
						$related = array_slice($related_products, 0, $number_of_products);
					}					
				}

				// When only tag is selected
				elseif (count($global_related_by) === 1 && in_array('tag', $global_related_by)) {
					$product_tag_ids = array();
					$prod_terms = get_the_terms($post->ID, 'product_tag');
					
					if (!empty($prod_terms) && !is_wp_error($prod_terms)) {
						foreach ($prod_terms as $term) {
							$product_tag_ids[] = $term->term_id;
						}
						
						// Get all products with matching tags
						$tag_products = crp_get_all_product_ids_from_tag_ids($product_tag_ids);
						
						// Score products by number of matching tags
						$scored_products = array();
						foreach ($tag_products as $product_id) {
							$product_tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'ids'));
							$matching_tags = array_intersect($product_tag_ids, $product_tags);
							$scored_products[] = array(
								'id' => $product_id,
								'score' => count($matching_tags)
							);
						}
						
						// Sort by number of matching tags
						usort($scored_products, function($a, $b) {
							return $b['score'] - $a['score'];
						});
						
						$related = array_slice(wp_list_pluck($scored_products, 'id'), 0, $number_of_products);
					}
				}
			}

			//gets excluded categories and tags
			$excluded_categories_ids = apply_filters( 'wt_crp_excluded_category_ids',get_post_meta($post->ID, '_crp_excluded_cats', true) );
			$excluded_tag_ids = apply_filters( 'wt_crp_excluded_tag_ids',array());

			if (!empty($excluded_categories_ids) && !empty($related)) {
				$all_ids = crp_get_all_product_ids_from_cat_ids( $excluded_categories_ids );

				if (!empty($all_ids)) {
					$related = array_diff($related, $all_ids);
				}
			}

			//Product tags of current viewing product page
			$product_tags = wp_get_post_terms($post->ID, 'product_tag', array('fields' => 'ids'));
			
			if (!empty($excluded_tag_ids) && !empty($related)) {
				// Prefetch all tags for related products 
				$related_product_tags_map = array();
				foreach ($related as $product_id) {
					$related_product_tags_map[$product_id] = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'ids']) ?: array();
				}

				$related = array_filter($related, function ($product_id) use ($excluded_tag_ids, $product_tags, $related_product_tags_map) {
					$related_product_tags = $related_product_tags_map[$product_id] ?? array();

					if (empty($related_product_tags)) {
						return false;
					}

					// Tags that are shared with the current product
					$shared_tags = array_intersect($related_product_tags, $product_tags);

					// Non-excluded tags that are shared with the current product
					$shared_non_excluded_tags = array_intersect($shared_tags, array_diff($product_tags, $excluded_tag_ids));

					// Include the product if it has at least one shared, non-excluded tag
					return !empty($shared_non_excluded_tags);
				});
			}
			
			delete_post_meta($post->ID, 'selected_ids');
			$related	= is_array($related) ? array_diff($related, array($post->ID, $current_post_id)) : array();
			/* Exclude Widget to categories and products  */
			$categories_to_exclude_widgets = (array) apply_filters( 'wt_crp_exclude_rp_widget_by_category', get_option('custom_related_products_crp_exclude_widget_category', array()) );
			$products_to_exclude_widgets = (array) apply_filters( 'wt_crp_exclude_rp_widget_by_product', get_option('custom_related_products_crp_exclude_widget_product', array()) );

			if( !empty($categories_to_exclude_widgets) || !empty($products_to_exclude_widgets) ) {

				if(!empty($categories_to_exclude_widgets)) {
					$get_product_ids = crp_get_all_product_ids_from_cat_ids($categories_to_exclude_widgets);

					if(!empty($products_to_exclude_widgets)) {
						$products_to_exclude_widgets = array_merge($get_product_ids,$products_to_exclude_widgets); 
					} else {
						$products_to_exclude_widgets = $categories_to_exclude_widgets;
					}
					
				}
			}

			if (!empty($related) && (is_array($products_to_exclude_widgets) && !in_array($post->ID,$products_to_exclude_widgets)) ) {

                // To exclude out of stock products
				$exclude_os	 = get_option('custom_related_products_exclude_os');
				if (!empty($exclude_os)) {

					foreach ($related as $key => $product_id) {
					    $stock_status = get_post_meta($product_id, '_stock_status', true);

					    if ($stock_status === 'outofstock') {
					        unset($related[$key]);
					    }
					}
				}

				// To exclude backorder products
				$exclude_backorder	 = get_option('custom_related_products_rp_exclude_backorder');

				if (!empty($exclude_backorder)) {

					foreach ($related as $key => $product_id) {
					    $stock_status = get_post_meta($product_id, '_stock_status', true);

					    if ($stock_status === 'onbackorder') {
					        unset($related[$key]);
					    }
					}
				}
				

                $related = array_slice($related, 0, $number_of_products);

				$related_products	 = array();
				$copy				 = array();
				
				$orderby 			 = get_option('custom_related_products_crp_order_by', 'popularity');
				$orderby			 = apply_filters('wt_related_products_orderby', $orderby);
				
				if ($orderby === 'relevance') {
					// Directly copy related to copy without shuffling
					$copy = $related;
				} else {
					// Shuffle only if orderby is not relevance
					$related_products = $related;
					while (count($related_products)) {
						// Take a random array element by its key
						$element = array_rand($related_products);
						// Assign the array and its value to another array
						$copy[$element] = $related_products[$element];
						// Delete the element from the source array
						unset($related_products[$element]);
					}
				}
				$order 				 = get_option('custom_related_products_crp_order', 'DESC');	
				$order				 = apply_filters('wt_related_products_order', $order);

				$i = 1;

				// Setup your custom query
				$args = array(
					'post_type'      => array('product', 'product_variation'), 
					'posts_per_page' => $number_of_products, 
					'orderby'        => $orderby, 
					'order'          => $order, 
					'post__in'       => $copy,
					'has_password'   => false
				);
				$args = apply_filters('wbte_rp_args_for_related_products_fetching',$args);

				// Remove 'order' if 'orderby' is 'relevance' or 'post__in'
				if ($orderby === 'relevance') {
					unset($args['order']); // Remove 'order' key
					$args['orderby'] = 'post__in'; // Force order by the 'post__in' array
				}
				
				$custom_orderby = class_exists('Custom_Related_Products') ? Custom_Related_Products::get_custom_order_by_values() : array();
				if( array_key_exists( $orderby, $custom_orderby ) ) {
					$args['orderby'] =  $custom_orderby[$orderby]['orderby'];
					$args['meta_key'] = $custom_orderby[$orderby]['meta_key'];
				}

				$copy = apply_filters("woocommerce_crp_set_product_visibility", $copy); 
				$min_slides = class_exists('Custom_Related_Products') ? Custom_Related_Products::wt_get_device_type() : '3';
				$slider_status = 'enable';

				if(count($copy) <= $min_slides){
					update_option('custom_related_products_slider_temp','disable');
					$slider_status = 'disable';
				}else{
					update_option('custom_related_products_slider_temp','enabled');
				}
				$bxslider		 = 'slider';
				$slider_state	 = get_option('custom_related_products_slider','enable');
				$crp_title		 = get_option('custom_related_products_crp_title', esc_html__('Related Products', 'wt-woocommerce-related-products'));
				$crp_heading 	 = apply_filters('wt_related_products_heading', "<h2 class='wt-crp-heading'>" . esc_html( $crp_title ) . " </h2>", $crp_title);

				$few_slider		 = '';
				$slider_type = get_option('custom_related_products_slider_type') ? get_option('custom_related_products_slider_type'):'swiper';
					if(in_array('elementor/elementor.php', apply_filters('active_plugins', get_option('active_plugins')))){ 
					$slider_type = 'bx';
				}
				
				if(strstr(wp_get_theme()->get('Name'),'Woodmart') || strstr(wp_get_theme()->get('Name'),'Flatsome')){
					$slider_type = 'bx';
				}
				
				if(strstr(wp_get_theme()->get('Name'),'Divi') || strstr(wp_get_theme()->get('Name'),'Avada') || strstr(wp_get_theme()->get('Name'),'BeOnePage')){
					$slider_type = 'swiper';
				}
				if( 'disable' === $slider_status ){
					$slider_state = '';
					$bxslider = '';
				}
					if ('enable' === $slider_state && $slider_type== 'bx') {
						$bxslider = 'bxslider';
					}
					if (('enable' !== $slider_state && 'swiper' === $slider_type ) || ('enable' !== $slider_state && 'bx' === $slider_type )) {
						$bxslider = '';
					}
				if(strstr(wp_get_theme()->get('Name'),'Twenty Twenty-One')){
					$slider_type = 'swiper';
				}
                                
                // Added empty check to stop showing random related products when post_ids are empty.
                if(empty($args['post__in']))
                {
                	$loop = array();
                } else {

                	$loop	 = new WP_Query($args);

                    if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
						@ini_set( 'display_errors', 0 ); 
					}
                }
				
		       	if(!empty($loop) && $loop->have_posts())
		       	{

					echo $crp_heading;

	                if($bxslider && apply_filters( 'wt_crp_custom_related_product_template', false ))
	                {
	                    ?>
	                            <div class="carousel-wrap">
	                                <div class="owl-carousel owl-theme products">
					                    <?php 

					                        $rel_products = $loop->posts;
						                    foreach ($rel_products as $products) {
				                                $bs_id = absint($products->ID);
				                                $bs_qty = 3;
				                                $args = array(
				                                    'id' => $bs_id,
				                                    'qty' => $bs_qty,
				                                    'loop' => '',
				                                );
				                              
				                                wc_get_template('/wt-custom-related.php', $args, CRP_PLUGIN_TEMPLATE_PATH, CRP_PLUGIN_TEMPLATE_PATH);
				                            }
					                     ?>
				                    </div>
				                </div>
	                        <?php 
	                    
	                }else
	                {
	                    if ($bxslider) 
	                    {
	                        ?>
	                        <div class="carousel-wrap">
	                        	
	                            <?php $wt_rp_ul_tag = apply_filters('wt_rp_alter_slider_carousal_ul_tag','<ul class="owl-carousel owl-theme products">');

								echo $wt_rp_ul_tag;
	                            
	                    } else 
	                    {
	                         woocommerce_product_loop_start();
	                    }

	                    while ($loop->have_posts()) : $loop->the_post();
						wc_get_template_part('content', 'product'); 

	                    endwhile; // end of the loop. 
	                    woocommerce_product_loop_end();
	                    if ($bxslider) 
	                    {
                            ?></ul>
                            </div>
	                    <?php 
	                    } 
	                }
		        }                               
			} else {
				?>
				<section class="related_products" style="display: none;"></section>
				<?php
			}
		} elseif('default' === $working_mode && !empty( $related_products ))
		{
	        $crp_title         = get_option('custom_related_products_crp_title', esc_html__('Related Products', 'wt-woocommerce-related-products'));
	        $crp_heading 	 = apply_filters('wt_related_products_heading', "<h2 class='wt-crp-heading'>" . esc_html( $crp_title ) . " </h2>", $crp_title);
	        $bxslider = false;
			?>
			<?php echo $crp_heading; ?>
			<?php
			$crelated = get_post_meta($post->ID, '_crp_related_ids', true);

			if (!empty($crelated))
				update_post_meta($post->ID, 'selected_ids', $crelated);
			?>
			<?php if ($bxslider) { ?>
				<ul class="<?php echo esc_attr( $bxslider ); ?> crp-slider products columns-<?php echo esc_attr(wc_get_loop_prop('columns')); ?>">
			<?php } else {
				woocommerce_product_loop_start();
			} 
			?>
			<?php
			foreach ($related_products as $related_product) :
				if (!is_object($related_product)) {
					$related_product = wc_get_product($related_product);
				}

				$post_object		 = get_post($related_product->get_id());
				setup_postdata($GLOBALS['post']	 = &$post_object);
				wc_get_template_part('content', 'product');
			?>
			<?php
			endforeach;
			woocommerce_product_loop_end();
		}
		?>

	</section>

<?php
endif;
wp_reset_postdata();
