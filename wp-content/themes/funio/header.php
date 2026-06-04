<?php
/**
 * Version:            1.0.0
 * Theme Name:         funio
 * Theme URI:          http://wpbingosite.com/themes/funio/
 * Author:             Wpbingo
 * Author URI:         http://wpbingosite.com/
 * License:            GNU General Public License v2 or later
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<?php 
	global $funio_page_id;
	$funio_settings = funio_global_settings();
	$direction = funio_get_direction(); 
	$funio_page_id = get_the_ID();	
	$header_style = funio_get_config('header_style', ''); 
	$header_style  = (get_post_meta( $funio_page_id, 'page_header_style', true )) ? get_post_meta($funio_page_id, 'page_header_style', true ) : $header_style ;
	$enable_sticky_header = ( isset($funio_settings['enable-sticky-header']) && $funio_settings['enable-sticky-header'] ) ? ($funio_settings['enable-sticky-header']) : false;
	$show_minicart = (isset($funio_settings['show-minicart']) && $funio_settings['show-minicart']) ? ($funio_settings['show-minicart']) : false;
	$show_searchform = (isset($funio_settings['show-searchform']) && $funio_settings['show-searchform']) ? ($funio_settings['show-searchform']) : false;
	$background_page = get_post_meta( get_the_ID(), 'page_background', true );
	$checkout_page_style="";
	if( function_exists('is_checkout') && is_checkout() ){
		$checkout_page_style = funio_get_config('checkout_page_style','checkout-page-style-1');
	}
?>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<link rel="profile" href="//gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php funio_loading_overlay(); ?>
<div id='page' class="hfeed page-wrapper  <?php echo esc_attr($checkout_page_style) ; ?>" <?php if($background_page) { ?>style="background-image: url('<?php echo esc_url($background_page); ?>');background-repeat:no-repeat;background-position:top center;"<?php } ?>>
	<?php if(isset($header_style) && $header_style) { ?>
		<?php get_template_part('templates/headers/header',$header_style); ?>
	<?php }else{ ?>
		<div id='bwp-header' class="bwp-header bwp-header-default">
			<?php funio_menu_mobile(); ?>
			<div class="header-desktop">
				<div class="container">
					<div class='header-content' data-sticky_header="<?php echo esc_attr($enable_sticky_header); ?>">
						<div class="row">
							<?php if($show_minicart || $show_searchform){ ?>
								<div class="col-xl-2 col-lg-2 col-md-6 col-sm-6 col-6 header-logo">
									<?php funio_header_logo(); ?>
								</div>
								<div class="col-xl-8 col-lg-8 col-md-12 col-12  wpbingo-menu-mobile">
									<?php funio_top_menu(); ?>
								</div>
								<div class="col-xl-2 col-lg-2 col-md-6 col-sm-6 col-6 margin-top-20">
									 <!-- Begin menu -->
									<?php if($show_minicart && class_exists( 'WooCommerce' )){ ?>
									<div class="wpbingo-cart-top pull-right">
										<?php get_template_part( 'woocommerce/minicart-ajax' ); ?>
									</div>
									<?php } ?>
									<!-- Begin Search -->
									<?php if($show_searchform && class_exists( 'WooCommerce' )){ ?>
									<div class="search-box pull-right">
										<div class="search-toggle"><i class="fa fa-search"></i></div>
										<div class="dropdown-search"><?php funio_search_form_product(); ?></div>
									</div>
									<?php } ?>
									<!-- End Search -->
								</div>
							<?php }else{ ?>
								<div class="col-xl-2 col-lg-2 col-md-6 col-sm-6 col-8 header-logo">
									<?php funio_header_logo(); ?>
								</div>
								<div class="col-xl-10 col-lg-10 col-md-6 col-sm-6 col-4 wpbingo-menu-mobile text-right">
									<?php funio_top_menu(); ?>
								</div>						
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div><!-- End header-wrapper -->		
	<?php } ?>
<div id="bwp-main" class="bwp-main">
<?php funio_get_page_title(); ?>