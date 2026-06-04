	<?php 
		$funio_settings = funio_global_settings();
		$cart_layout = funio_get_config('cart-layout','dropdown');
		$cart_style = funio_get_config('cart-style','light');
		$show_minicart = (isset($funio_settings['show-minicart']) && $funio_settings['show-minicart']) ? ($funio_settings['show-minicart']) : false;
		$show_compare = (isset($funio_settings['show-compare']) && $funio_settings['show-compare']) ? ($funio_settings['show-compare']) : false;
		$enable_sticky_header = ( isset($funio_settings['enable-sticky-header']) && $funio_settings['enable-sticky-header'] ) ? ($funio_settings['enable-sticky-header']) : false;
		$show_searchform = (isset($funio_settings['show-searchform']) && $funio_settings['show-searchform']) ? ($funio_settings['show-searchform']) : false;
		$show_wishlist = (isset($funio_settings['show-wishlist']) && $funio_settings['show-wishlist']) ? ($funio_settings['show-wishlist']) : false;
		$show_currency = (isset($funio_settings['show-currency']) && $funio_settings['show-currency']) ? ($funio_settings['show-currency']) : false;
		$show_menutop = (isset($funio_settings['show-menutop']) && $funio_settings['show-menutop']) ? ($funio_settings['show-menutop']) : false;
	?>
	<h1 class="bwp-title hide"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
	<header id='bwp-header' class="bwp-header header-v5">
		<?php funio_campbar(); ?>
		<?php if(isset($funio_settings['show-header-top']) && $funio_settings['show-header-top']){ ?>
		<div id="bwp-topbar" class="topbar-v3 hidden-sm hidden-xs">
			<div class="topbar-inner">
				<div class="container">
					<div class="row">
						<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 topbar-left hidden-sm hidden-xs">
							<?php if( isset($funio_settings['address']) && $funio_settings['address'] ) : ?>
							<div class="address hidden-xs">
								<a href="<?php echo esc_html($funio_settings['link_address']); ?>"><i class="icon-pin"></i><?php echo esc_html($funio_settings['address']); ?></a>
							</div>
							<?php endif; ?>
							<?php if( isset($funio_settings['email']) && $funio_settings['email'] ) : ?>
							<div class="email hidden-xs">
								<i class="icon-email"></i><a href="mailto:<?php echo esc_attr($funio_settings['email']); ?>"><?php echo esc_html($funio_settings['email']); ?></a>
							</div>
							<?php endif; ?>
						</div>
						<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 topbar-right">
							<?php if($show_menutop){ ?>
								<?php wp_nav_menu( 
								  array( 
									  'theme_location' => 'topbar_menu', 
									  'container' => 'false', 
									  'menu_id' => 'topbar_menu', 
									  'menu_class' => 'menu'
								   ) 
								); ?>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
		<?php funio_menu_mobile(); ?>
		<div class="header-desktop">
			<?php if(($show_minicart || $show_wishlist || $show_searchform || is_active_sidebar('top-link')) && class_exists( 'WooCommerce' ) ){ ?>
			<div class='header-wrapper' data-sticky_header="<?php echo esc_attr($funio_settings['enable-sticky-header']); ?>">
				<div class="container">
					<div class="row">
						<div class="col-xl-7 col-lg-7 col-md-12 col-sm-12 col-12 header-left content-header">
							<?php funio_header_logo(); ?>
							<div class="wpbingo-menu-mobile header-menu">
								<div class="header-menu-bg">
									<?php funio_top_menu(); ?>
								</div>
							</div>
						</div>
						<div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-12 header-right">
							<div class="header-page-link">
								<!-- Begin Search -->
								<?php if($show_searchform && class_exists( 'WooCommerce' )){ ?>
								<div class="search-box">
									<div class="search-toggle"><i class="icon-magnifiying-glass"></i></div>
								</div>
								<?php } ?>
								<!-- End Search -->
								<div class="login-header">
									<?php if (is_user_logged_in()) { ?>
										<?php if(is_active_sidebar('top-link')){ ?>
											<div class="block-top-link">
												<?php dynamic_sidebar( 'top-link' ); ?>
											</div>
										<?php } ?>
									<?php }else{ ?>
										<a class="active-login" href="#" ><i class="icon-user"></i></a>
									<?php } ?>
								</div>			
								<?php if($show_wishlist && class_exists( 'YITH_WCWL' )){ ?>
								<div class="wishlist-box">
									<a href="<?php echo get_permalink( get_option('yith_wcwl_wishlist_page_id') ); ?>"><i class="icon-star"></i></a>
								</div>
								<?php } ?>
								<?php if($show_minicart && class_exists( 'WooCommerce' )){ ?>
								<div class="funio-topcart <?php echo esc_attr($cart_layout); ?> <?php echo esc_attr($cart_style); ?>">
									<?php get_template_part( 'woocommerce/minicart-ajax' ); ?>
								</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div><!-- End header-wrapper -->
			<?php }else{ ?>
				<div class="header-normal">
					<div class='header-wrapper' data-sticky_header="<?php echo esc_attr($funio_settings['enable-sticky-header']); ?>">
						<div class="container">
							<div class="row">
								<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-6 header-left">
									<?php funio_header_logo(); ?>
								</div>
								<div class="col-xl-9 col-lg-9 col-md-6 col-sm-6 col-6 wpbingo-menu-mobile header-main">
									<div class="header-menu-bg">
										<?php funio_top_menu(); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</header><!-- End #bwp-header -->