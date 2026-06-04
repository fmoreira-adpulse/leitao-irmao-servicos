<?php
add_action( 'init', 'funio_button_product' );
add_action( 'woocommerce_before_single_product', 'funio_woocommerce_single_product_summary' );
remove_action( 'woocommerce_shop_loop_subcategory_title', 'woocommerce_template_loop_category_title', 10 );
add_action( 'woocommerce_after_subcategory', 'funio_woocommerce_template_loop_category_title', 10 );
add_action( 'woocommerce_after_subcategory_only', 'funio_woocommerce_template_loop_only_category_title', 10 );
remove_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10 );
add_action( 'woocommerce_before_subcategory_title', 'funio_woocommerce_subcategory_thumbnail', 10 );
add_filter( 'funio_custom_category', 'funio_woocommerce_maybe_show_product_subcategories' );
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
add_action( 'woocommerce_after_cart', 'woocommerce_cross_sell_display');
add_filter('woocommerce_placeholder_img_src', 'funio_woocommerce_placeholder_img_src');
function funio_woocommerce_placeholder_img_src( $src ){
	$src = get_template_directory_uri().'/images/placeholder.jpg';
	return $src;
}
function funio_button_product(){
	$funio_settings = funio_global_settings();
	//Button List Product
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
	//Category
	if(isset($funio_settings['show-category']) && $funio_settings['show-category'] ){
		add_action('woocommerce_before_shop_loop_item', 'funio_woocommerce_template_loop_category', 15 );
	}
	//Cart
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');
		add_action('woocommerce_after_shop_loop_item', 'funio_woocommerce_template_loop_add_to_cart', 15 );
	//Whishlist
	if(isset($funio_settings['product-wishlist']) && $funio_settings['product-wishlist'] && class_exists( 'YITH_WCWL' ) ){
		add_action('woocommerce_after_shop_loop_item', 'funio_add_loop_wishlist_link', 15 );
	}
	//Compare
	if(isset($funio_settings['product-compare']) && $funio_settings['product-compare'] && class_exists( 'YITH_WOOCOMPARE' ) ){
		add_action('woocommerce_after_shop_loop_item', 'funio_add_loop_compare_link', 20 );
	}	
	//Quickview
		add_action('woocommerce_after_shop_loop_item', 'funio_quickview', 35 );
	/* Remove sold by in product loops */
	if(class_exists("WCV_Vendors")){
		remove_action( 'woocommerce_after_shop_loop_item', array('WCV_Vendor_Shop', 'template_loop_sold_by'), 9, 2);
		add_action('woocommerce_after_shop_loop_item_title', array('WCV_Vendor_Shop', 'template_loop_sold_by'), 5 );
	}
	if(isset($funio_settings['product-countdown']) && $funio_settings['product-countdown'] ){
		add_action('woocommerce_before_shop_loop_item_title', 'funio_add_countdownt_item', 15 );
	}
	//Attribute
	if( function_exists("bwp_display_woocommerce_attribute") && isset($funio_settings['product-attribute']) && $funio_settings['product-attribute'] ){
		add_action('woocommerce_before_shop_loop_item_title', 'bwp_display_woocommerce_attribute', 20 );
	}
	/* Remove result count in product shop */
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
}
function funio_woocommerce_single_product_summary(){
	global $product;
	$product_short_desc = funio_get_config('product-short-desc',true);
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 0 );
	add_action( 'woocommerce_single_product_summary', 'funio_add_loop_wishlist_link', 35 );
	add_action( 'woocommerce_single_product_summary', 'funio_add_loop_compare_link', 36 );
	add_action( 'woocommerce_single_product_summary', 'funio_add_social', 45 );
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash');
	add_action( 'woocommerce_single_product_summary', 'funio_label_stock', 25 );	
	add_action( 'woocommerce_single_product_summary', 'funio_get_countdown', 20 );
	add_action( 'woocommerce_single_product_summary', 'funio_size_guide', 20 );	
	add_action( 'woocommerce_after_single_product', 'funio_sticky_cart', 50 );	
}
function funio_woocommerce_template_loop_category() {
	global $product;
	$html = '';
	$category =  get_the_terms( $product->get_id(), 'product_cat' );
	if ( $category && ! is_wp_error( $category ) ) {	
		$html = '<div class="cat-products">';
			$html .= '<a href="'.get_term_link( $category[0]->term_id, 'product_cat' ).'">';
				$html .= $category[0]->name;
			$html .= '</a>';
		$html .= '</div>';
	}
	echo wp_kses($html,'social');
}
function funio_update_total_price() {
	global $woocommerce;
	$data = array(
		'total_price' => $woocommerce->cart->get_cart_total(),
	);
	wp_send_json($data);
}	
add_action( 'wp_ajax_funio_update_total_price', 'funio_update_total_price' );
add_action( 'wp_ajax_nopriv_funio_update_total_price', 'funio_update_total_price' );
/* Ajax Search */
add_action( 'wp_ajax_funio_search_products_ajax', 'funio_search_products_ajax' );
add_action( 'wp_ajax_nopriv_funio_search_products_ajax', 'funio_search_products_ajax' );
function funio_search_products_ajax(){
	$character = (isset($_GET['character']) && $_GET['character'] ) ? $_GET['character'] : '';
	$limit = (isset($_GET['limit']) && $_GET['limit'] ) ? $_GET['limit'] : 5;
	$category = (isset($_GET['category']) && $_GET['category'] ) ? $_GET['category'] : "";
	$args = array(
		'post_type' 			=> 'product',
		'post_status'    		=> 'publish',
		'ignore_sticky_posts'   => 1,	  
		's' 					=> $character,
		'posts_per_page'		=> $limit
	);
	
	if($category){
		$args['tax_query'] = array(
			array(
				'taxonomy'  => 'product_cat',
				'field'     => 'slug',
				'terms'     => $category ));
	}
	$list = new WP_Query( $args );
	$json = array();
	if ($list->have_posts()) {
		while($list->have_posts()): $list->the_post();
		global $product, $post;
		$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'shop_catalog' );
		$json[] = array(
			'product_id' => $product->get_id(),
			'name'       => $product->get_title(),		
			'image'		 =>  $image[0],
			'link'		 =>  get_permalink( $product->get_id() ),
			'price'      =>  $product->get_price_html(),
		);			
		endwhile;
	}
	die (json_encode($json));
}
function funio_label_stock(){
	global $product; 
	$stock = ( $product->is_in_stock() )? 'in-stock' : 'out-stock' ;
	$product_stock = funio_get_config('product-stock',true);
	if($product_stock){ ?>
		<?php if($stock == "out-stock"): ?>
			<div class="product-stock">    
				<span class="stock"><?php echo esc_html__( 'Out stock', 'funio' ); ?></span>
			</div>
		<?php endif; ?>
	<?php } ?>
<?php }
function funio_quickview_short_desc(){
	global $post;
	if ( ! $post->post_excerpt ) {
		return;
	}
	$length_product_short_desc = funio_get_config('length-product-short-desc',true);
	?>
	<div itemprop="description" class="description">
		<?php echo apply_filters( 'woocommerce_short_description', wp_trim_words( $post->post_excerpt, $length_product_short_desc ) ) ?>
	</div>
<?php }
function funio_get_countdown(){
	global $product;
	$start_time = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
	$countdown_time = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );
	$orginal_price = get_post_meta( $product->get_id(), '_regular_price', true );	
	$sale_price = get_post_meta( $product->get_id(), '_sale_price', true );	
	$symboy = get_woocommerce_currency_symbol( get_woocommerce_currency() );
	$show_countdown = funio_get_config('show-countdown',true);
	if($show_countdown){
		if ( $countdown_time ):
			$date = bwp_timezone_offset( $countdown_time ); ?>
			<div class="countdown-single">
				<h2 class="title-countdown"><?php echo esc_html__("Hurry up ! Deal ends in :","funio") ?></h2>
				<div class="product-countdown"  data-day="<?php echo esc_attr__("Days","funio"); ?>" data-hour="<?php echo esc_attr__("Hours","funio"); ?>" data-min="<?php echo esc_attr__("Mins","funio"); ?>" data-sec="<?php echo esc_attr__("Secs","funio"); ?>" data-date="<?php echo esc_attr( $date ); ?>" data-price="<?php echo esc_attr( $symboy.$orginal_price ); ?>" data-sttime="<?php echo esc_attr( $start_time ); ?>" data-cdtime="<?php echo esc_attr( $countdown_time ); ?>" data-id="<?php echo esc_attr('product_'.$product->get_id()); ?>"></div>
			</div>
		<?php endif; ?>
	<?php } ?>
<?php }
function funio_size_guide(){
	global $product;
	$funio_settings = funio_global_settings();
	$size_guide = funio_get_config('size-guide',false);
	if($size_guide && $product->is_type( 'variable' )){
		if(isset($funio_settings['img-size-guide']['url']) && !empty($funio_settings['img-size-guide']['url'])):?>
		<div class="size-guide">
			<div class="title-size-guide"><i class="wpb-icon-ruler"></i><?php echo esc_html__("Size Guide","funio") ?></div>
			<div class="img-size-guide">
				<div class="title-size-guide"><i class="icon_close"></i></div>
				<img src="<?php echo esc_url($funio_settings['img-size-guide']['url']); ?>" alt="<?php echo esc_attr__( 'Image Size Guide','funio' ); ?>">
			</div>
		</div>
		<?php endif; ?>
	<?php } ?>
<?php }
function funio_sticky_cart(){
	global $product; 
	$show_sticky_cart = funio_get_config('show-sticky-cart',true); 
	if($show_sticky_cart){ ?>
	<div class="sticky-product">
		<div class="content">
			<div class="content-product">
				<div class="item-thumb">
					<a href="<?php echo get_permalink( $product->get_id() ); ?>"><img src="<?php echo wp_get_attachment_url( $product->get_image_id() ); ?>" /></a>
				</div>
				<div class="content-bottom">
					<div class="item-title">
						<a href="<?php echo esc_url(get_permalink( $product->get_id() )); ?>"><?php echo esc_html($product->get_title()); ?></a>
					</div>
					<div class="price">
						<?php echo wp_kses($product->get_price_html(),'social'); ?>
					</div>
				</div>
			</div>
			<div class="content-cart">
				<?php woocommerce_template_single_add_to_cart(); ?>
			</div>
		</div>
	</div>
	<?php } ?>
<?php }
function funio_add_countdownt_item(){
	global $product;
	$item_id = 'item_countdown_'.rand().time();
	$start_time = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
	$countdown_time = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );	
	$quickview = funio_get_config('product_quickview');
	if( $start_time && $countdown_time ) {
	$date = bwp_timezone_offset( $countdown_time );	
	?>
	<div class="countdown">
		<div class="item-countdown">
			<div class="product-countdown"  
				data-day="<?php echo esc_html__("days","funio"); ?>"
				data-hour="<?php echo esc_html__("hours","funio"); ?>"
				data-min="<?php echo esc_html__("mins","funio"); ?>"
				data-sec="<?php echo esc_html__("secs","funio"); ?>"
				data-date="<?php echo esc_attr( $date ); ?>"  
				data-sttime="<?php echo esc_attr( $start_time ); ?>" 
				data-cdtime="<?php echo esc_attr( $countdown_time ); ?>"
				data-id="<?php echo esc_attr($item_id); ?>">
			</div>
		</div>
	</div>
	<?php }
}
function funio_woocommerce_template_loop_add_to_cart( $args = array() ) {
	global $product;
	if ( $product ) {
		$defaults = array(
			'quantity' => 1,
			'class'    => implode( ' ', array_filter( array(
					'button',
					'product_type_' . $product->get_type(),
					$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : 'read_more',
					$product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '',
			) ) ),
		);
		$args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $args, $defaults ), $product );
		wc_get_template( 'loop/add-to-cart.php', $args );
	}
}	
function funio_add_excerpt_in_product_archives() {
	global $post;
	if ( ! $post->post_excerpt ) return;		
	echo '<div class="item-description item-description2">'.wp_trim_words( $post->post_excerpt, 25 ).'</div>';
}	
/*add second thumbnail loop product*/
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
add_action( 'woocommerce_before_shop_loop_item_title', 'funio_woocommerce_template_loop_product_thumbnail', 10 );
function funio_product_thumbnail( $size = 'woocommerce_thumbnail', $placeholder_width = 0, $placeholder_height = 0  ) {
	global $funio_settings,$product;
	$html = '';
	$id = get_the_ID();
	$gallery = get_post_meta($id, '_product_image_gallery', true);
	$attachment_image = '';
	if(!empty($gallery)) {
		$gallery = explode(',', $gallery);
		$first_image_id = $gallery[0];
		$attachment_image = wp_get_attachment_image($first_image_id , $size, false, array('class' => 'hover-image back'));
	}
		$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), '' );
		if ( has_post_thumbnail() ){
			if( $attachment_image && $funio_settings['category-image-hover']){
				$html .= '<div class="product-thumb-hover">';
				$html .= '<a href="' . get_the_permalink() . '" class="woocommerce-LoopProduct-link">';
				$html .= (get_the_post_thumbnail( $product->get_id(), $size )) ? get_the_post_thumbnail( $product->get_id(), $size ): '<img src="'.get_template_directory_uri().'/images/placeholder.jpg" alt="'. esc_attr__('No thumb', 'funio').'">';
				if($funio_settings['category-image-hover']){
					$html .= $attachment_image;
				}
				$html .= '</a>';
				$html .= '</div>';				
			}else{
				$html .= '<a href="' . get_the_permalink() . '" class="woocommerce-LoopProduct-link">';		
				$html .= (get_the_post_thumbnail( $product->get_id(), $size )) ? get_the_post_thumbnail( $product->get_id(), $size ): '<img src="'.get_template_directory_uri().'/images/placeholder.jpg" alt="'. esc_attr__('No thumb', 'funio').'">';
				$html .= '</a>';
			}		
		}else{
			$html .= '<a href="' . get_the_permalink() . '" class="woocommerce-LoopProduct-link">';		
			$html .= '<img src="'.get_template_directory_uri().'/images/placeholder.jpg" alt="'. esc_attr__('No thumb', 'funio').'">';
			$html .= '</a>';	
		}
	/* quickview */
	return $html;
}
function funio_woocommerce_template_loop_product_thumbnail(){
	echo funio_product_thumbnail();
}
function funio_countdown_woocommerce_template_loop_product_thumbnail(){
	echo funio_product_thumbnail("shop_single");
}
//Button List Product
/*********QUICK VIEW PRODUCT**********/
function funio_product_quick_view_scripts() {	
	wp_enqueue_script('wc-add-to-cart-variation');
}
add_action( 'wp_enqueue_scripts', 'funio_product_quick_view_scripts' );	
function funio_quickview(){
	global $product;
	$quickview = funio_get_config('product_quickview'); 
	if( $quickview ) : 
		echo '<span class="product-quickview"><a href="#" data-product_id="'.esc_attr($product->get_id()).'" class="quickview quickview-button quickview-'.esc_attr($product->get_id()).'" >'.apply_filters( 'out_of_stock_add_to_cart_text', 'Quick View' ).' <i class="icon-magnifiying-glass"></i>'.'</a></span>';
	endif;
}
add_action("wp_ajax_funio_quickviewproduct", "funio_quickviewproduct");
add_action("wp_ajax_nopriv_funio_quickviewproduct", "funio_quickviewproduct");
function funio_quickviewproduct(){
	echo funio_content_product();exit();
}
function funio_content_product(){
	$productid = (isset($_REQUEST["product_id"]) && $_REQUEST["product_id"]>0) ? $_REQUEST["product_id"] : 0;
	$query_args = array(
		'post_type'	=> 'product',
		'p'			=> $productid
	);
	$outputraw = $output = '';
	$r = new WP_Query($query_args);
	if($r->have_posts()){ 
		while ($r->have_posts()){ $r->the_post(); setup_postdata($r->post);
			ob_start();
			wc_get_template_part( 'content', 'quickview-product' );
			$outputraw = ob_get_contents();
			ob_end_clean();
		}
	}
	$output = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $outputraw);
	return $output;	
}
//sale flash
function funio_add_sale_flash(){	
	wc_get_template( 'loop/sale-flash.php' );
}
//Wish list
function funio_add_loop_wishlist_link(){	
	if ( in_array( 'yith-woocommerce-wishlist/init.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
		echo do_shortcode( "[yith_wcwl_add_to_wishlist]" );
	}
}
//Compare
function funio_add_loop_compare_link(){
	global $post;
	$product_id = $post->ID;	
	if( class_exists( 'YITH_WOOCOMPARE' ) ){
		echo '<div class="woocommerce product compare-button"><a href="javascript:void(0)" class="compare button" data-product_id="'. esc_attr($product_id) .'" rel="nofollow">'.esc_html__("Compare","funio").'</a></div>';	
	}	
}
function funio_add_social() {
	$product_share	 = funio_get_config('product-share',true);
	if ( shortcode_exists( 'social_share' ) && $product_share) :
		echo '<div class="social-icon">';
			echo '<label>';
			echo esc_html__("Share : ","funio");
			echo '</label>';
			echo do_action( 'woocommerce_share' );
			echo do_shortcode( "[social_share]" );
		echo '</div>';
	endif;	
}
function funio_add_thumb_single_product() {
	echo '<div class="image-thumbnail-list">';
	do_action( 'woocommerce_product_thumbnails' );
	echo '</div>';
}
function funio_get_class_item_product(){
	$product_col_large = 12 /(funio_get_config('product_col_large',4));	
	$product_col_medium = 12 /(funio_get_config('product_col_medium',3));
	$product_col_sm 	= 12 /(funio_get_config('product_col_sm',1));
	$product_col_xs 	= 12 /(funio_get_config('product_col_xs',1));
	$class_item_product = 'col-lg-'.$product_col_large.' col-md-'.$product_col_medium.' col-sm-'.$product_col_sm.' col-'.$product_col_xs;
	return $class_item_product;
}
function funio_catalog_perpage(){
	$funio_settings = funio_global_settings();
	$query_string = funio_get_query_string();
	parse_str($query_string, $params);
	$query_string 	= '?'.$query_string;
	$per_page 	=   (isset($funio_settings['product_count']) && $funio_settings['product_count'])  ? (int)$funio_settings['product_count'] : 12;
	$product_count = (isset($params['product_count']) && $params['product_count']) ? ($params['product_count']) : $per_page;
	?>
	<div class="funio-woocommerce-sort-count">
		<div class="woocommerce-sort-count">
			<ul class="list-show">
				<li data-value="<?php echo esc_attr($per_page); 	?>"<?php if ($product_count == $per_page){?>class="active"<?php } ?>><a href="<?php echo add_query_arg('product_count', $per_page, $query_string); ?>"><?php echo esc_attr($per_page); ?></a></li>
				<li data-value="<?php echo esc_attr($per_page*2); 	?>"<?php if ($product_count == $per_page*2){?>class="active"<?php } ?>><a href="<?php echo add_query_arg('product_count', $per_page*2, $query_string); ?>"><?php echo esc_attr($per_page*2); ?></a></li>
				<li data-value="<?php echo esc_attr($per_page*3); 	?>"<?php if ($product_count == $per_page*3){?>class="active"<?php } ?>><a href="<?php echo add_query_arg('product_count', $per_page*3,$query_string); ?>"><?php echo esc_attr($per_page*3); ?></a></li>
			</ul>
		</div>
	</div>
<?php }	
add_filter('loop_shop_per_page', 'funio_loop_shop_per_page');
function funio_loop_shop_per_page() {
	$funio_settings = funio_global_settings();
	$query_string = funio_get_query_string();
	parse_str($query_string, $params);
	$per_page 	=   (isset($funio_settings['product_count']) && $funio_settings['product_count'])  ? (int)$funio_settings['product_count'] : 12;
	$product_count = (isset($params['product_count']) && $params['product_count']) ? ($params['product_count']) : $per_page;
	return $product_count;
}	
function funio_found_posts(){
	wc_get_template( 'loop/woocommerce-found-posts.php' );
}	
remove_action('woocommerce_before_main_content', 'funio_woocommerce_breadcrumb', 20);	
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
function funio_search_form_product(){
	$query_string = funio_get_query_string();
	parse_str($query_string, $params);
	$category_slug = isset( $params['product_cat'] ) ? $params['product_cat'] : '';
	$terms =	get_terms( 'product_cat', 
	array(  
		'hide_empty' => true,	
		'parent' => 0	
	));
	$class_ajax_search 	= "";	 
	$ajax_search 		= funio_get_config('show-ajax-search',false);
	$limit_ajax_search 		= funio_get_config('limit-ajax-search',5);
	if($ajax_search){
		$class_ajax_search = "ajax-search";
	}
	?>
	<form role="search" method="get" class="search-from <?php echo esc_attr($class_ajax_search); ?>" action="<?php echo esc_url(home_url( '/' )); ?>" data-admin="<?php echo admin_url( 'admin-ajax.php', 'funio' ); ?>" data-noresult="<?php echo esc_html__("No Result","funio") ; ?>" data-limit="<?php echo esc_attr($limit_ajax_search); ?>">
		<?php if($terms && is_object($terms)){ ?>
		<div class="select_category pwb-dropdown dropdown">
			<span class="pwb-dropdown-toggle dropdown-toggle" data-toggle="dropdown"><?php echo esc_html__("Category","funio"); ?></span>
			<span class="caret"></span>
			<ul class="pwb-dropdown-menu dropdown-menu category-search">
			<li data-value="" class="<?php  echo (empty($category_slug) ?  esc_attr("active") : ""); ?>"><?php echo esc_html__("Browse Category","funio"); ?></li>
				<?php foreach($terms as $term){ ?>
					<?php if( $term && is_object($term) ){ ?>
						<li data-value="<?php echo esc_attr($term->slug); ?>" class="<?php  echo (($term->slug == $category_slug) ?  esc_attr("active") : ""); ?>"><?php echo esc_html($term->name); ?></li>
						<?php
							$terms_vl1 =	get_terms( 'product_cat', 
							array( 
									'parent' => '', 
									'hide_empty' => false,
									'parent' 		=> $term->term_id, 
							));						
						?>	
						<?php foreach ($terms_vl1 as $term_vl1) { ?>
							<?php if( $term_vl1 && is_object($term_vl1) ){ ?>
								<li data-value="<?php echo esc_attr($term_vl1->slug); ?>" class="<?php  echo (($term_vl1->slug == $category_slug) ?  esc_attr("active") : ""); ?>"><?php echo esc_html($term_vl1->name); ?></li>
								<?php
									$terms_vl2 =	get_terms( 'product_cat', 
									array( 
											'parent' => '', 
											'hide_empty' => false,
											'parent' 		=> $term_vl1->term_id, 
								));	?>					
								<?php foreach ($terms_vl2 as $term_vl2) { ?>
									<?php if( $term_vl2 && is_object($term_vl2) ){ ?>
										<li data-value="<?php echo esc_attr($term_vl2->slug); ?>" class="<?php  echo (($term_vl2->slug == $category_slug) ?  esc_attr("active") : ""); ?>"><?php echo esc_html($term_vl2->name); ?></li>
									<?php } ?>
								<?php } ?>
							<?php } ?>
						<?php } ?>
					<?php } ?>
				<?php } ?>
			</ul>	
			<input type="hidden" name="product_cat" class="product-cat" value="<?php echo esc_attr($category_slug); ?>"/>
		</div>	
		<?php } ?>	
		<div class="search-box">
			<button id="searchsubmit" class="btn" type="submit">
				<i class="icon_search"></i>
				<span><?php echo esc_html__('search','funio'); ?></span>
			</button>
			<input type="text" value="<?php echo get_search_query(); ?>" name="s" id="s" class="input-search s" placeholder="<?php echo esc_attr__( 'Search...', 'funio' ); ?>" />
			<div class="result-search-products-content">
				<ul class="result-search-products">
				</ul>
			</div>
		</div>
		<input type="hidden" name="post_type" value="product" />
	</form>
<?php }
function funio_top_cart(){
	global $woocommerce; ?>
	<div id="cart" class="top-cart">
		<a class="cart-icon" href="<?php echo get_permalink( wc_get_page_id( 'cart' ) ); ?>" title="<?php esc_attr_e('View your shopping cart', 'funio'); ?>">
			<i class="flaticon-bag"></i>
		</a>
	</div>
<?php }
function funio_button_filter(){
	$html = '<a class="button-filter-toggle"></a>';
	echo wp_kses_post($html);
}	
function funio_image_single_product(){
	$class = new stdClass;
	$class->show_thumb = funio_get_config('product-thumbs',false);
	$position = funio_get_config('position-thumbs',"bottom");
	$class->position = $position;
	if($class->show_thumb && $position == "outsite"){
		add_action( 'woocommerce_single_product_summary', 'funio_add_thumb_single_product', 40 );
	}	
	if($position == 'left' || $position == "right"){
		$class->class_thumb = "col-md-2";
		$class->class_data_image = 'data-vertical="true" data-verticalswiping="true"';
		$class->class_image = "col-md-10";
	}else{
		$class->class_thumb = $class->class_image = "col-sm-12";
		$class->class_data_image = "";
	}
	$product_count_thumb = funio_get_config("product-thumbs-count","") ? funio_get_config("product-thumbs-count","") : apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
	$class->product_count_thumb =	$product_count_thumb;
	$product_layout_thumb = funio_get_config("layout-thumbs","zoom");
	$class->product_layout_thumb =	$product_layout_thumb;
	return $class;
}
function funio_category_top_bar(){
	add_action('woocommerce_before_shop_loop','woocommerce_result_count',40); 
	add_action('woocommerce_before_shop_loop','funio_display_view', 35);
	remove_action('woocommerce_before_shop_loop','funio_found_posts', 20);
	add_action('woocommerce_before_shop_loop','woocommerce_catalog_ordering', 30);
	$category_style  = funio_get_config('category_style','sidebar');
	add_action('woocommerce_before_shop_loop','funio_button_filter', 25);
	do_action( 'woocommerce_before_shop_loop' );
}
function funio_get_product_discount(){
	global $product;
	$discount = 0;
	if ($product->is_on_sale() && $product->is_type( 'variable' )){
		$available_variations = $product->get_available_variations();
		for ($i = 0; $i < count($available_variations); ++$i) {
			$variation_id=$available_variations[$i]['variation_id'];
			$variable_product1= new WC_Product_Variation( $variation_id );
			$regular_price = $variable_product1->get_regular_price();
			$sales_price = $variable_product1->get_sale_price();
			if(is_numeric($regular_price) && is_numeric($sales_price)){
				$percentage = round( (( $regular_price - $sales_price ) / $regular_price ) * 100 ) ;
				if ($percentage > $discount) {
					$discount = $percentage;
				}
			}
		}
	}elseif($product->is_on_sale() && $product->is_type( 'simple' )){
		$regular_price	= $product->get_regular_price();
		$sales_price	= $product->get_sale_price();
		if(is_numeric($regular_price) && is_numeric($sales_price)){
			$discount = round( ( ( $regular_price - $sales_price ) / $regular_price ) * 100 );
		}
	}
	if( $discount > 0 ){
		$text_discount = "-".$discount.'%';
	}else{
		$text_discount = '';
	}
	return 	$text_discount;
}
add_action( 'woocommerce_before_quantity_input_field', 'funio_display_quantity_plus' );
function funio_display_quantity_plus() {
   $html = '<button type="button" class="plus" >+</button>';
   echo wp_kses($html,'social');
}
add_action( 'woocommerce_after_quantity_input_field', 'funio_display_quantity_minus' );
function funio_display_quantity_minus() {
	$html = '<button type="button" class="minus" >-</button>';
	echo wp_kses($html,'social');
}
function funio_woocommerce_template_loop_category_title( $category ) { ?>
	<div class="woocommerce-loop-category">
		<h2 class="woocommerce-loop-category__title">
			<a href="<?php echo get_term_link( $category->term_id, 'product_cat' ); ?>"><?php echo esc_html( $category->name ); ?></a>
		</h2>
	</div>
	<?php
}
function funio_woocommerce_template_loop_only_category_title( $category ) { ?>
		<div class="woocommerce-loop-category">
			<h2 class="woocommerce-loop-category__title">
				<a href="<?php echo get_term_link( $category->term_id, 'product_cat' ); ?>"><?php echo esc_html( $category->name ); ?></a>
			</h2>
			<div class="count-product">
				<?php if ( $category->count == 1 ) {
					echo apply_filters( 'woocommerce_subcategory_count_html', esc_html( $category->count ) . '' . esc_html__(" product","funio"), $category );
				}else{
					echo apply_filters( 'woocommerce_subcategory_count_html', esc_html( $category->count ) . '' . esc_html__(" products","funio"), $category );
				} ?>
			</div>
			<div class="view-all">
				<a href="<?php echo get_term_link( $category->term_id, 'product_cat' ); ?>"><?php echo esc_html__("View all products","funio"); ?></a>
			</div>
		</div>
	<?php
}
function funio_woocommerce_subcategory_thumbnail( $category ){
	$subcategories_style = funio_get_config('style-subcategories','shop_mini_categories');
	if($subcategories_style == "icon_categories"){
		$icon_category = get_term_meta( $category->term_id, 'category_icon', true );
		if($icon_category){?>
			<i class="<?php echo esc_attr($icon_category); ?>"></i>
			<?php }
	}else{
		$thumbnail_id         = get_term_meta( $category->term_id, 'thumbnail_id', true );
		if ( $thumbnail_id ) {
			$image        = wp_get_attachment_image_src( $thumbnail_id, 'full' );
			$image        = $image[0]; ?>
			<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $category->name ); ?>"/>
			<?php
		}
	}
}
function funio_get_video_product(){
	global $product;
	$video  = (get_post_meta( $product->get_id(), 'video_product', true )) ? get_post_meta($product->get_id(), 'video_product', true ) : "";
	if($video){ ?>
		<div class="funio-product-button "><div class="funio-bt-video"><a class="bwp-video" href="<?php echo esc_url($video); ?>"><?php echo esc_html__( 'Play video', 'funio' ); ?></a></div></div>
	<?php }
}
function funio_view_product(){
	global $product;
	$view  = (get_post_meta( $product->get_id(), 'view_product', true )) ? get_post_meta($product->get_id(), 'view_product', true ) : "";
	if($view == 'true'){ $j=0; ?>
	<?php $attachment_ids = $product->get_gallery_image_ids(); ?>
	<div class="funio-360-button"><i class="wpb-icon-d-design"></i><?php echo esc_html__("360 Degree","funio") ?></div>
	<div class="content-product-360-view">
		<div class="product-360-view" data-count="<?php echo esc_attr(count($attachment_ids)-1); ?>">
			<div class="funio-360-button"></div>
			<div class="images-display">
				<ul class="images-list">
				<?php
					foreach ( $attachment_ids as $attachment_id ) {		
						$image_link = wp_get_attachment_url( $attachment_id );
						if ( ! $image_link )
							continue;
						$image_title 	= esc_attr( get_the_title( $attachment_id ) );
						$image_caption 	= esc_attr( get_post_field( 'post_excerpt', $attachment_id ) );
						$image       = wp_get_attachment_image( $attachment_id, apply_filters( 'single_product_small_thumbnail_size', 'shop_catalog' ), 0, $attr = array(
							'title' => $image_title,
							'alt'   => $image_title
							) ); ?>
						<li class="images-display image-<?php echo esc_attr($j); ?> <?php if($j==0){ ?>active<?php } ?>"><?php echo wp_kses($image,'social'); ?></li>
						<?php $j++;
					}
				?>
				</ul>
			</div>
		</div>
	</div>
	<?php }
}
function funio_woocommerce_maybe_show_product_subcategories( $loop_html = '' ) {
	if(class_exists( 'WooCommerce' )){
		$product_categories = get_terms( 'product_cat', array('hide_empty' => true) );
		ob_start();
		foreach ( $product_categories as $category ) {
			wc_get_template(
				'content-product_cat.php',
				array(
					'category' => $category,
				)
			);
		}
		$loop_html .= ob_get_clean();
		return $loop_html;
	}
}
function funio_woocommerce_output_product_categories( ){
	if(class_exists( 'WooCommerce' )){ 
		$product_categories = get_terms( 'product_cat', array('hide_empty' => true) );
		if ( ! $product_categories ) {
			return false;
		}
		foreach ( $product_categories as $category ) {
			wc_get_template(
				'content-only-product_cat.php',
				array(
					'category' => $category,
				)
			);
		}
		return true;
	}
}
?>