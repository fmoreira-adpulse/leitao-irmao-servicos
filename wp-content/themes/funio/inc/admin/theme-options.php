<?php
/**
 * Funio Settings Options
 */
if (!class_exists('Redux_Framework_funio_settings')) {
    class Redux_Framework_funio_settings {
        public $args        = array();
        public $sections    = array();
        public $theme;
        public $ReduxFramework;
        public function __construct() {
            if (!class_exists('ReduxFramework')) {
                return;
            }
            // This is needed. Bah WordPress bugs.  ;)
            if (  true == Redux_Helpers::isTheme(__FILE__) ) {
                $this->initSettings();
            } else {
                add_action('plugins_loaded', array($this, 'initSettings'), 10);
            }
        }
        public function initSettings() {
            $this->theme = wp_get_theme();
            // Set the default arguments
            $this->setArguments();
            // Set a few help tabs so you can see how it's done
            $this->setHelpTabs();
            // Create the sections and fields
            $this->setSections();
            if (!isset($this->args['opt_name'])) { // No errors please
                return;
            }
            $this->ReduxFramework = new ReduxFramework($this->sections, $this->args);
			$custom_font = funio_get_config('custom_font',false);
			if($custom_font != 1){
				remove_action( 'wp_head', array( $this->ReduxFramework, '_output_css' ),150 );
			}
        }
        function compiler_action($options, $css, $changed_values) {
        }
        function dynamic_section($sections) {
            return $sections;
        }
        function change_arguments($args) {
            return $args;
        }
        function change_defaults($defaults) {
            return $defaults;
        }
        function remove_demo() {
        }
        public function setSections() {
            $page_layouts = funio_options_layouts();
            $sidebars = funio_options_sidebars();
            $funio_header_type = funio_options_header_types();
            $funio_banners_effect = funio_options_banners_effect();
            // General Settings  ------------
            $this->sections[] = array(
                'icon' => 'fa fa-home',
                'icon_class' => 'icon',
                'title' => esc_html__('General', 'funio'),
                'fields' => array(                
                )
            );  
            // Layout Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Layout', 'funio'),
                'fields' => array(
                    array(
                        'id' => 'background_img',
                        'type' => 'media',
                        'title' => esc_html__('Background Image', 'funio'),
                        'sub_desc' => '',
                        'default' => ''
                    ),
                    array(
                        'id'=>'show-newletter',
                        'type' => 'switch',
                        'title' => esc_html__('Show Newletter Form', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Show', 'funio'),
                        'off' => esc_html__('Hide', 'funio'),
                    ),
                    array(
                        'id' => 'background_newletter_img',
                        'type' => 'media',
                        'title' => esc_html__('Popup Newletter Image', 'funio'),
                        'url'=> true,
                        'readonly' => false,
                        'sub_desc' => '',
                        'default' => array(
                            'url' => get_template_directory_uri() . '/images/newsletter-image.jpg'
                        )
                    ),
                    array(
                            'id' => 'back_active',
                            'type' => 'switch',
                            'title' => esc_html__('Back to top', 'funio'),
                            'sub_desc' => '',
                            'desc' => '',
                            'default' => '1'// 1 = on | 0 = off
                            ),                          
                    array(
                            'id' => 'direction',
                            'type' => 'select',
                            'title' => esc_html__('Direction', 'funio'),
                            'options' => array( 'ltr' => esc_html__('Left to Right', 'funio'), 'rtl' => esc_html__('Right to Left', 'funio') ),
                            'default' => 'ltr'
                        )        
                )
            );
            // Logo & Icons Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Logo & Icons', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'sitelogo',
                        'type' => 'media',
                        'compiler'  => 'true',
                        'mode'      => false,
                        'title' => esc_html__('Logo', 'funio'),
                        'desc'      => esc_html__('Upload Logo image default here.', 'funio'),
                        'default' => array(
                            'url' => get_template_directory_uri() . '/images/logo/logo.png'
                        )
                    )
                )
            );
            // Header Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Header', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'header_style',
                        'type' => 'image_select',
                        'full_width' => true,
                        'title' => esc_html__('Header Type', 'funio'),
                        'options' => $funio_header_type,
                        'default' => '4'
                    ),
                    array(
                        'id'=>'show-header-top',
                        'type' => 'switch',
                        'title' => esc_html__('Show Header Top', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'show-searchform',
                        'type' => 'switch',
                        'title' => esc_html__('Show Search Form', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'show-ajax-search',
                        'type' => 'switch',
                        'title' => esc_html__('Show Ajax Search', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio')
                    ),
                    array(
                        'id'=>'limit-ajax-search',
                        'type' => 'text',
                        'title' => esc_html__('Limit Of Result Search', 'funio'),
						'default' => 6,
						'required' => array('show-ajax-search','equals',true)
                    ),					
                    array(
                        'id'=>'search-cats',
                        'type' => 'switch',
                        'title' => esc_html__('Show Categories', 'funio'),
                        'required' => array('search-type','equals',array('post', 'product')),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'show-wishlist',
                        'type' => 'switch',
                        'title' => esc_html__('Show Wishlist', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
					array(
                        'id'=>'show-campbar',
                        'type' => 'switch',
                        'title' => esc_html__('Show Campbar', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
					array(
                        'id'=>'content-campbar',
                        'type' => 'text',
                        'title' => esc_html__('Content Campbar', 'funio'),
						'default' => '20% OFF EVERYTHING – USE CODE:FLASH20 – ENDS SUNDAY',
						'required' => array('show-campbar','equals',true),
                    ),
					array(
						'id' => 'img-campbar',
						'type' => 'media',
						'title' => esc_html__('Image Campbar', 'funio'),
						'url'=> true,
						'readonly' => false,
						'required' => array('show-campbar','equals',true),
						'sub_desc' => '',
						'default' => array(
							'url' => ""
						)
					),
					 array(
                      'id' => 'color-campbar',
                      'type' => 'color',
                      'title' => esc_html__('Color Campbar', 'funio'),
                      'subtitle' => esc_html__('Select a color for Campbar.', 'funio'),
                      'default' => '#424cc7',
                      'transparent' => false,
					  'required' => array('show-campbar','equals',true),
                    ),
					array(
                        'id'=>'show-menutop',
                        'type' => 'switch',
                        'title' => esc_html__('Show Menu Top', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
					array(
                        'id'=>'show-currency',
                        'type' => 'switch',
                        'title' => esc_html__('Show Currency', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
					array(
                        'id'=>'show-compare',
                        'type' => 'switch',
                        'title' => esc_html__('Show Compare', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
					array(
                        'id'=>'show-minicart',
                        'type' => 'switch',
                        'title' => esc_html__('Show Mini Cart', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
					array(
                        'id'=>'cart-layout',
						'type' => 'button_set',
                        'title' => esc_html__('Cart Layout', 'funio'),
                        'options' => array('dropdown' => esc_html__('Dropdown', 'funio'),
											'popup' => esc_html__('Popup', 'funio')),
						'default' => 'dropdown',
						'required' => array('show-minicart','equals',true),
                    ),
					array(
                        'id'=>'cart-style',
						'type' => 'button_set',
                        'title' => esc_html__('Cart Style', 'funio'),
                        'options' => array('dark' => esc_html__('Dark', 'funio'),
											'light' => esc_html__('Light', 'funio')),
						'default' => 'light',
						'required' => array('show-minicart','equals',true),
                    ),
                    array(
                        'id'=>'enable-sticky-header',
                        'type' => 'switch',
                        'title' => esc_html__('Enable Sticky Header', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
					array(
                        'id'=>'email',
                        'type' => 'text',
                        'title' => esc_html__('Header Email', 'funio'),
                        'default' => ''
                    ),
					array(
                        'id'=>'address',
                        'type' => 'text',
                        'title' => esc_html__('Address', 'funio'),
                        'default' => 'Find Store'
                    ),
					array(
                        'id'=>'link_address',
                        'type' => 'text',
                        'title' => esc_html__('Link Address', 'funio'),
                        'default' => '#'
                    ),
                )
            );
            // Footer Settings
            $footers = funio_get_footers();
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Footer', 'funio'),
                'fields' => array(
                    array(
                        'id' => 'footer_style',
                        'type' => 'image_select',
                        'title' => esc_html__('Footer Style', 'funio'),
                        'sub_desc' => esc_html__( 'Select Footer Style', 'funio' ),
                        'desc' => '',
                        'options' => $footers,
                        'default' => '32'
                    ),
                )
            );
            // Copyright Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Copyright', 'funio'),
                'fields' => array(
                    array(
                        'id' => "footer-copyright",
                        'type' => 'textarea',
                        'title' => esc_html__('Copyright', 'funio'),
                        'default' => sprintf( wp_kses('&copy; Copyright %s. All Rights Reserved.', 'funio'), date('Y') )
                    ),
                    array(
                        'id'=>'footer-payments',
                        'type' => 'switch',
                        'title' => esc_html__('Show Payments Logos', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'footer-payments-image',
                        'type' => 'media',
                        'url'=> true,
                        'readonly' => false,
                        'title' => esc_html__('Payments Image', 'funio'),
                        'required' => array('footer-payments','equals','1'),
                        'default' => array(
                            'url' => get_template_directory_uri() . '/images/payments.png'
                        )
                    ),
                    array(
                        'id'=>'footer-payments-image-alt',
                        'type' => 'text',
                        'title' => esc_html__('Payments Image Alt', 'funio'),
                        'required' => array('footer-payments','equals','1'),
                        'default' => ''
                    ),
                    array(
                        'id'=>'footer-payments-link',
                        'type' => 'text',
                        'title' => esc_html__('Payments Link URL', 'funio'),
                        'required' => array('footer-payments','equals','1'),
                        'default' => ''
                    )
                )
            );
            // Page Title Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Page Title', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'page_title',
                        'type' => 'switch',
                        'title' => esc_html__('Show Page Title', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
					 array(
                        'id' => 'show_page_title_bg',
                        'type' => 'switch',
                        'title' => esc_html__('Show Background Breadcrumb', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                        'required' => array('page_title','equals', true),
                    ),
                    array(
                        'id'=>'page_title_bg',
                        'type' => 'media',
                        'url'=> true,
                        'readonly' => false,
                        'title' => esc_html__('Background', 'funio'),
                        'required' => array('show_page_title_bg','equals', true),
	                    'default' => array(
                            'url' => "",
                        )							
                    ),
                    array(
                        'id' => 'breadcrumb',
                        'type' => 'switch',
                        'title' => esc_html__('Show Breadcrumb', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                        'required' => array('page_title','equals', true),
                    ),
                )
            );
            // 404 Page Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('404 Error', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'title-error',
                        'type' => 'text',
                        'title' => esc_html__('Content Page 404', 'funio'),
                        'desc' => esc_html__('Input a block slug name', 'funio'),
                        'default' => '404'
                    ),
					array(
                        'id'=>'sub-title',
                        'type' => 'text',
                        'title' => esc_html__('Content Page 404', 'funio'),
                        'desc' => esc_html__('Input a block slug name', 'funio'),
                        'default' => "Oops! That page can't be found."
                    ), 					
                    array(
                        'id'=>'sub-error',
                        'type' => 'text',
                        'title' => esc_html__('Content Page 404', 'funio'),
                        'desc' => esc_html__('Input a block slug name', 'funio'),
                        'default' => "We're really sorry but we can't seem to find the page you were looking for."
                    ),               
                    array(
                        'id'=>'btn-error',
                        'type' => 'text',
                        'title' => esc_html__('Button Page 404', 'funio'),
                        'desc' => esc_html__('Input a block slug name', 'funio'),
                        'default' => 'Back The Homepage'
                    )                      
                )
            );
            // Social Share Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Social Share', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'social-share',
                        'type' => 'switch',
                        'title' => esc_html__('Show Social Links', 'funio'),
                        'desc' => esc_html__('Show social links in post and product, page, portfolio, etc.', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'share-fb',
                        'type' => 'switch',
                        'title' => esc_html__('Enable Facebook Share', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'share-tw',
                        'type' => 'switch',
                        'title' => esc_html__('Enable Twitter Share', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'share-linkedin',
                        'type' => 'switch',
                        'title' => esc_html__('Enable LinkedIn Share', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'share-pinterest',
                        'type' => 'switch',
                        'title' => esc_html__('Enable Pinterest Share', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                )
            );
            $this->sections[] = array(
				'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Socials Link', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'socials_link',
                        'type' => 'switch',
                        'title' => esc_html__('Enable Socials link', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'link-fb',
                        'type' => 'text',
                        'title' => esc_html__('Enter Facebook link', 'funio'),
						'default' => '#'
                    ),
                    array(
                        'id'=>'link-tw',
                        'type' => 'text',
                        'title' => esc_html__('Enter Twitter link', 'funio'),
						'default' => '#'
                    ),
                    array(
                        'id'=>'link-linkedin',
                        'type' => 'text',
                        'title' => esc_html__('Enter LinkedIn link', 'funio'),
						'default' => '#'
                    ),
                    array(
                        'id'=>'link-youtube',
                        'type' => 'text',
                        'title' => esc_html__('Enter Youtube link', 'funio'),
						'default' => '#'
                    ),
                    array(
                        'id'=>'link-pinterest',
                        'type' => 'text',
                        'title' => esc_html__('Enter Pinterest link', 'funio'),
						'default' => '#'
                    ),
                    array(
                        'id'=>'link-instagram',
                        'type' => 'text',
                        'title' => esc_html__('Enter Instagram link', 'funio'),
						'default' => '#'
                    ),
                )
            );			
            //     The end -----------
            // Styling Settings  -------------
            $this->sections[] = array(
                'icon' => 'icofont icofont-brand-appstore',
                'icon_class' => 'icon',
                'title' => esc_html__('Styling', 'funio'),
                'fields' => array(              
                )
            );  
            // Color & Effect Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Color & Effect', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'compile-css',
                        'type' => 'switch',
                        'title' => esc_html__('Compile Css', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),					
                    array(
                      'id' => 'main_theme_color',
                      'type' => 'color',
                      'title' => esc_html__('Main Theme Color', 'funio'),
                      'subtitle' => esc_html__('Select a main color for your site.', 'funio'),
                      'default' => '#222222',
                      'transparent' => false,
					  'required' => array('compile-css','equals',array(true)),
                    ),      
                    array(
                        'id'=>'show-loading-overlay',
                        'type' => 'switch',
                        'title' => esc_html__('Loading Overlay', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Show', 'funio'),
                        'off' => esc_html__('Hide', 'funio'),
                    ),
                    array(
                        'id'=>'banners_effect',
                        'type' => 'image_select',
                        'full_width' => true,
                        'title' => esc_html__('Banner Effect', 'funio'),
                        'options' => $funio_banners_effect,
                        'default' => 'banners-effect-1'
                    )                   
                )
            );
            // Typography Settings
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Typography', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'custom_font',
                        'type' => 'switch',
                        'title' => esc_html__('Custom Font', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),				
                    array(
                        'id'=>'select-google-charset',
                        'type' => 'switch',
                        'title' => esc_html__('Select Google Font Character Sets', 'funio'),
                        'default' => false,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
						'required' => array('custom_font','equals',true),
                    ),
                    array(
                        'id'=>'google-charsets',
                        'type' => 'button_set',
                        'title' => esc_html__('Google Font Character Sets', 'funio'),
                        'multi' => true,
                        'required' => array('select-google-charset','equals',true),
                        'options'=> array(
                            'cyrillic' => 'Cyrrilic',
                            'cyrillic-ext' => 'Cyrrilic Extended',
                            'greek' => 'Greek',
                            'greek-ext' => 'Greek Extended',
                            'khmer' => 'Khmer',
                            'latin' => 'Latin',
                            'latin-ext' => 'Latin Extneded',
                            'vietnamese' => 'Vietnamese'
                        ),
                        'default' => array('latin','greek-ext','cyrillic','latin-ext','greek','cyrillic-ext','vietnamese','khmer')
                    ),
                    array(
                        'id'=>'family_font_body',
                        'type' => 'typography',
                        'title' => esc_html__('Body Font', 'funio'),
                        'google' => true,
                        'subsets' => false,
                        'font-style' => false,
                        'text-align' => false,
						'output'      => array('body'),
                        'color' => false,
                        'default'=> array(
                            'color'=>"#777777",
                            'google'=>true,
                            'font-weight'=>'400',
                            'font-family'=>'Open Sans',
                            'font-size'=>'14px',
                            'line-height' => '22px'
                        ),
						'required' => array('custom_font','equals',true)
                    ),
                    array(
                        'id'=>'h1-font',
                        'type' => 'typography',
                        'title' => esc_html__('H1 Font', 'funio'),
                        'google' => true,
                        'subsets' => false,
                        'font-style' => false,
                        'text-align' => false,
                        'color' 	=> false,
						'output'      => array('body h1'),
                        'default'=> array(
                            'color'=>"#1d2127",
                            'google'=>true,
                            'font-weight'=>'400',
                            'font-family'=>'Open Sans',
                            'font-size'=>'36px',
                            'line-height' => '44px'
                        ),
						'required' => array('custom_font','equals',true)
                    ),
                    array(
                        'id'=>'h2-font',
                        'type' => 'typography',
                        'title' => esc_html__('H2 Font', 'funio'),
                        'google' => true,
                        'subsets' => false,
                        'font-style' => false,
                        'text-align' => false,
                        'color' => false,
						'output'      => array('body h2'),
                        'default'=> array(
                            'color'=>"#1d2127",
                            'google'=>true,
                            'font-weight'=>'300',
                            'font-family'=>'Open Sans',
                            'font-size'=>'30px',
                            'line-height' => '40px'
                        ),
						'required' => array('custom_font','equals',true)
                    ),
                    array(
                        'id'=>'h3-font',
                        'type' => 'typography',
                        'title' => esc_html__('H3 Font', 'funio'),
                        'google' => true,
                        'subsets' => false,
                        'font-style' => false,
                        'text-align' => false,
                        'color' => false,
						'output'      => array('body h3'),
                        'default'=> array(
                            'color'=>"#1d2127",
                            'google'=>true,
                            'font-weight'=>'400',
                            'font-family'=>'Open Sans',
                            'font-size'=>'25px',
                            'line-height' => '32px'
                        ),
						'required' => array('custom_font','equals',true)
                    ),
                    array(
                        'id'=>'h4-font',
                        'type' => 'typography',
                        'title' => esc_html__('H4 Font', 'funio'),
                        'google' => true,
                        'subsets' => false,
                        'font-style' => false,
                        'text-align' => false,
                        'color' => false,
						'output'      => array('body h4'),
                        'default'=> array(
                            'color'=>"#1d2127",
                            'google'=>true,
                            'font-weight'=>'400',
                            'font-family'=>'Open Sans',
                            'font-size'=>'20px',
                            'line-height' => '27px'
                        ),
						'required' => array('custom_font','equals',true)
                    ),
                    array(
                        'id'=>'h5-font',
                        'type' => 'typography',
                        'title' => esc_html__('H5 Font', 'funio'),
                        'google' => true,
                        'subsets' => false,
                        'font-style' => false,
                        'text-align' => false,
                        'color' => false,
						'output'      => array('body h5'),
                        'default'=> array(
                            'color'=>"#1d2127",
                            'google'=>true,
                            'font-weight'=>'600',
                            'font-family'=>'Open Sans',
                            'font-size'=>'14px',
                            'line-height' => '18px'
                        ),
						'required' => array('custom_font','equals',true)
                    ),
                    array(
                        'id'=>'h6-font',
                        'type' => 'typography',
                        'title' => esc_html__('H6 Font', 'funio'),
                        'google' => true,
                        'subsets' => false,
                        'font-style' => false,
                        'text-align' => false,
                        'color' => false,
						'output'      => array('body h6'),
                        'default'=> array(
                            'color'=>"#1d2127",
                            'google'=>true,
                            'font-weight'=>'400',
                            'font-family'=>'Open Sans',
                            'font-size'=>'14px',
                            'line-height' => '18px'
                        ),
						'required' => array('custom_font','equals',true)
                    )
                )
            );
            //     The end -----------          
            if ( class_exists( 'Woocommerce' ) ) :
                $this->sections[] = array(
                    'icon' => 'icofont icofont-cart-alt',
                    'icon_class' => 'icon',
                    'title' => esc_html__('Ecommerce', 'funio'),
                    'fields' => array(              
                    )
                );
                $this->sections[] = array(
                    'icon' => 'icofont icofont-double-right',
                    'icon_class' => 'icon',
                    'subsection' => true,
                    'title' => esc_html__('Product Archives', 'funio'),
                    'fields' => array(
                        array(
                            'id'=>'category_style',
                            'title' => esc_html__('Product Archives Style', 'funio'),
                            'type' => 'select',
							'options' => array(
								'filter_ontop' => esc_html__('Filter On Top', 'funio'),
                                'sidebar' => esc_html__('Sidebar', 'funio'),
								'filter_dropdown' => esc_html__('Filter Dropdown', 'funio'),
								'filter_sideout' => esc_html__('Filter Side Out', 'funio'),
								'filter_drawer' => esc_html__('Filter Drawer', 'funio'),
								'only_categories' => esc_html__('Shop Only Categories', 'funio'),
                             ),
                            'default' => 'sidebar',
                        ),
						array(
                            'id'=>'shop-layout',
                            'type' => 'button_set',
                            'title' => esc_html__('Shop Layout', 'funio'),
							'options' => array(
								'full' => esc_html__('Full', 'funio'),
								'boxed' => esc_html__('Boxed', 'funio'),
								),
                            'default' => 'boxed',
                        ),
						array(
                            'id'=>'shop_paging',
							'title' => esc_html__('Shop Paging', 'funio'),
                            'type' => 'select',
							'options' => array(
								'shop-pagination' => esc_html__('Pagination', 'funio'),
								'shop-infinity' => esc_html__('Infinity', 'funio'),
								'shop-loadmore' => esc_html__('Load More', 'funio'),
                             ),
                            'default' => 'shop-pagination',
                        ),
						array(
                            'id'=>'show-subcategories',
                            'type' => 'button_set',
                            'title' => esc_html__('Show Sub Categories', 'funio'),
							'options' => array(
								'show' => esc_html__('Yes', 'funio'),
								'hide' => esc_html__('No', 'funio'),
								),
                            'default' => 'show',
                        ),
						array(
                            'id'=>'style-subcategories',
							'title' => esc_html__('Style Sub Categories', 'funio'),
                            'type' => 'select',
							'options' => array(
								'shop_mini_categories' => esc_html__('Mini Categories', 'funio'),
								'icon_categories' => esc_html__('Icon Categories', 'funio'),
								'image_categories' => esc_html__('Image Categories', 'funio'),
                             ),
                            'default' => 'mini',
							'required' => array('show-subcategories','equals','show'),
                        ),
						array(
                            'id' => 'sub_col_large',
                            'type' => 'button_set',
                            'title' => esc_html__('Sub Categories column Desktop', 'funio'),
                            'options' => array(
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',  
									'5' => '5',
                                    '6' => '6'                          
                                ),
                            'default' => '4',
							'required' => array('show-subcategories','equals','show'),
                            'sub_desc' => esc_html__( 'Select number of column on Desktop Screen', 'funio' ),
                        ),
                        array(
                            'id' => 'sub_col_medium',
                            'type' => 'button_set',
                            'title' => esc_html__('Sub Categories column Medium Desktop', 'funio'),
                            'options' => array(
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',  
									'5' => '5',
                                    '6' => '6'                          
                                ),
                            'default' => '3',
							'required' => array('show-subcategories','equals','show'),
                            'sub_desc' => esc_html__( 'Select number of column on Medium Desktop Screen', 'funio' ),
                        ),
                        array(
                            'id' => 'sub_col_sm',
                            'type' => 'button_set',
                            'title' => esc_html__('Sub Categories column Ipad Screen', 'funio'),
                            'options' => array(
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',  
									'5' => '5',
                                    '6' => '6'                          
                                ),
                            'default' => '3',
							'required' => array('show-subcategories','equals','show'),
                            'sub_desc' => esc_html__( 'Select number of column on Ipad Screen', 'funio' ),
                        ),
						array(
                            'id'=>'layout_shop',
							'title' => esc_html__('Style Layout Shop', 'funio'),
                            'type' => 'button_set',
							'options' => array(
								'1' => esc_html__('Style 1', 'funio'),
								'2' => esc_html__('Style 2', 'funio'),
								'3' => esc_html__('Style 3', 'funio'),
								'4' => esc_html__('Style 4', 'funio'),
								'5' => esc_html__('Style 5', 'funio'),
                             ),
                            'default' => '1',
                        ),						
                        array(
                            'id'=>'category-view-mode',
                            'type' => 'button_set',
                            'title' => esc_html__('View Mode', 'funio'),
                            'options' => funio_ct_category_view_mode(),
                            'default' => 'grid',
                        ),
                        array(
                            'id' => 'product_col_large',
                            'type' => 'button_set',
                            'title' => esc_html__('Product Listing column Desktop', 'funio'),
                            'options' => array(
									'1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4'                          
                                ),
                            'default' => '4',
							'required' => array('category-view-mode','equals','grid'),
                            'sub_desc' => esc_html__( 'Select number of column on Desktop Screen', 'funio' ),
                        ),
                        array(
                            'id' => 'product_col_medium',
                            'type' => 'button_set',
                            'title' => esc_html__('Product Listing column Medium Desktop', 'funio'),
                            'options' => array(
									'1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4'                       
                                ),
                            'default' => '3',
							'required' => array('category-view-mode','equals','grid'),
                            'sub_desc' => esc_html__( 'Select number of column on Medium Desktop Screen', 'funio' ),
                        ),
                        array(
                            'id' => 'product_col_sm',
                            'type' => 'button_set',
                            'title' => esc_html__('Product Listing column Ipad Screen', 'funio'),
                            'options' => array(
									'1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4'                        
                                ),
                            'default' => '3',
							'required' => array('category-view-mode','equals','grid'),
                            'sub_desc' => esc_html__( 'Select number of column on Ipad Screen', 'funio' ),
                        ),
						array(
                            'id' => 'product_col_xs',
                            'type' => 'button_set',
                            'title' => esc_html__('Product Listing column Mobile Screen', 'funio'),
                            'options' => array(
									'1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',                         
                                ),
                            'default' => '2',
							'required' => array('category-view-mode','equals','grid'),
                            'sub_desc' => esc_html__( 'Select number of column on Mobile Screen', 'funio' ),
                        ),
                        array(
                            'id'=>'woo-show-rating',
                            'type' => 'switch',
                            'title' => esc_html__('Show Rating in Woocommerce Products Widget', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),						
						array(
                            'id'=>'show-category',
                            'type' => 'switch',
                            'title' => esc_html__('Show Category', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
                        array(
                            'id' => 'product_count',
                            'type' => 'text',
                            'title' => esc_html__('Shop pages show at product', 'funio'),
                            'default' => '12',
                            'sub_desc' => esc_html__( 'Type Count Product Per Shop Page', 'funio' ),
                        ),						
                        array(
                            'id'=>'category-image-hover',
                            'type' => 'switch',
                            'title' => esc_html__('Enable Image Hover Effect', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
                        array(
                            'id'=>'category-hover',
                            'type' => 'switch',
                            'title' => esc_html__('Enable Hover Effect', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
                        array(
                            'id'=>'product-wishlist',
                            'type' => 'switch',
                            'title' => esc_html__('Show Wishlist', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
						array(
							'id'=>'product-compare',
							'type' => 'switch',
							'title' => esc_html__('Show Compare', 'funio'),
							'default' => false,
							'on' => esc_html__('Yes', 'funio'),
							'off' => esc_html__('No', 'funio'),
						),						
                        array(
                            'id'=>'product_quickview',
                            'type' => 'switch',
                            'title' => esc_html__('Show Quick View', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio')
                        ),
                        array(
                            'id'=>'product-quickview-label',
                            'type' => 'text',
                            'required' => array('product-quickview','equals',true),
                            'title' => esc_html__('"Quick View" Text', 'funio'),
                            'default' => ''
                        ),
						array(
                            'id'=>'product-countdown',
                            'type' => 'switch',
                            'title' => esc_html__('Show Product Countdown', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio')
                        ),
						array(
                            'id'=>'product-attribute',
                            'type' => 'switch',
                            'title' => esc_html__('Show Product Attribute', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio')
                        ),
						array(
                            'id'=>'checkout_page_style',
                            'title' => esc_html__('Checkout Page Style', 'funio'),
                            'type' => 'button_set',
                            'options' => array(
                                    'checkout-page-style-1' => 'Style 1',
                                    'checkout-page-style-2' => 'Style 2',                        
                                ),
                            'default' => 'style-1',
                        ),
                    )
                );
                $this->sections[] = array(
                    'icon' => 'icofont icofont-double-right',
                    'icon_class' => 'icon',
                    'subsection' => true,
                    'title' => esc_html__('Single Product', 'funio'),
                    'fields' => array(
                        array(
                            'id'=>'sidebar_detail_product',
                            'type' => 'image_select',
                            'title' => esc_html__('Page Layout', 'funio'),
                            'options' => $page_layouts,
                            'default' => 'full'
                        ),
                        array(
                            'id'=>'product-stock',
                            'type' => 'switch',
                            'title' => esc_html__('Show "Out of stock" Status', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
						array(
                            'id'=>'show-sticky-cart',
                            'type' => 'switch',
                            'title' => esc_html__('Show Sticky Cart Product', 'funio'),
                            'default' => false,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),						
						array(
                            'id'=>'show-countdown',
                            'type' => 'switch',
                            'title' => esc_html__('Show CountDown', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
                        array(
                            'id'=>'product-short-desc',
                            'type' => 'switch',
                            'title' => esc_html__('Show Short Description', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
						array(
							'id' => 'length-product-short-desc',
							'type' => 'text',
							'title' => esc_html__('Length Short Description Quickview', 'funio'),
							'required' => array('product-short-desc','equals',true),
							'default' =>'25',
						),					
                        array(
                            'id'=>'product-related',
                            'type' => 'switch',
                            'title' => esc_html__('Show Related Product', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
                        array(
                            'id'=>'product-related-count',
                            'type' => 'text',
                            'required' => array('product-related','equals',true),
                            'title' => esc_html__('Related Product Count', 'funio'),
                            'default' => '10'
                        ),
                        array(
                            'id'=>'product-related-cols',
                            'type' => 'button_set',
                            'required' => array('product-related','equals',true),
                            'title' => esc_html__('Related Product Columns', 'funio'),
                            'options' => funio_ct_related_product_columns(),
                            'default' => '4',
                        ),
                        array(
                            'id'=>'product-upsell',
                            'type' => 'switch',
                            'title' => esc_html__('Show Upsell Products', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),                      
                        array(
                            'id'=>'product-upsell-count',
                            'type' => 'text',
                            'required' => array('product-upsell','equals',true),
                            'title' => esc_html__('Upsell Products Count', 'funio'),
                            'default' => '10'
                        ),
                        array(
                            'id'=>'product-upsell-cols',
                            'type' => 'button_set',
                            'required' => array('product-upsell','equals',true),
                            'title' => esc_html__('Upsell Product Columns', 'funio'),
                            'options' => funio_ct_related_product_columns(),
                            'default' => '3',
                        ),
                        array(
                            'id'=>'product-crosssells',
                            'type' => 'switch',
                            'title' => esc_html__('Show Crooss Sells Products', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),                      
                        array(
                            'id'=>'product-crosssells-count',
                            'type' => 'text',
                            'required' => array('product-crosssells','equals',true),
                            'title' => esc_html__('Crooss Sells Products Count', 'funio'),
                            'default' => '10'
                        ),
                        array(
                            'id'=>'product-crosssells-cols',
                            'type' => 'button_set',
                            'required' => array('product-crosssells','equals',true),
                            'title' => esc_html__('Crooss Sells Product Columns', 'funio'),
                            'options' => funio_ct_related_product_columns(),
                            'default' => '3',
                        ),						
                        array(
                            'id'=>'product-hot',
                            'type' => 'switch',
                            'title' => esc_html__('Show "Hot" Label', 'funio'),
                            'desc' => esc_html__('Will be show in the featured product.', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
                        array(
                            'id'=>'product-hot-label',
                            'type' => 'text',
                            'required' => array('product-hot','equals',true),
                            'title' => esc_html__('"Hot" Text', 'funio'),
                            'default' => ''
                        ),
                        array(
                            'id'=>'product-sale',
                            'type' => 'switch',
                            'title' => esc_html__('Show "Sale" Label', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
                         array(
                            'id'=>'product-sale-percent',
                            'type' => 'switch',
                            'required' => array('product-sale','equals',true),
                            'title' => esc_html__('Show Sale Price Percentage', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),  
                        array(
                            'id'=>'product-share',
                            'type' => 'switch',
                            'title' => esc_html__('Show Social Share Links', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
						array(
                            'id'=>'size-guide',
                            'type' => 'switch',
                            'title' => esc_html__('Show Size Guide', 'funio'),
                            'default' => false,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
						array(
							'id' => 'img-size-guide',
							'type' => 'media',
							'title' => esc_html__('Image Size Guide', 'funio'),
							'url'=> true,
							'readonly' => false,
							'required' => array('size-guide','equals',true),
							'sub_desc' => '',
							'default' => array(
								'url' => ""
							)
						),
						array(
							'id'=>'description-style',
							'type' => 'select',
							'title' => esc_html__('Description Style', 'funio'),
							'options' => array(
										'accordion' => esc_html__('Accordion', 'funio'),
										'full-content' => esc_html__('Full Content', 'funio'),
										'tab' => esc_html__('Tab', 'funio'),
										'vertical' => esc_html__('Vertical', 'funio'),
										),
							'default' => 'tab',
						),
                    )
                );
                $this->sections[] = array(
                    'icon' => 'icofont icofont-double-right',
                    'icon_class' => 'icon',
                    'subsection' => true,
                    'title' => esc_html__('Image Product', 'funio'),
                    'fields' => array(
                        array(
                            'id'=>'product-thumbs',
                            'type' => 'switch',
                            'title' => esc_html__('Show Thumbnails', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
						array(
                            'id'=>'layout-thumbs',
                            'type' => 'select',
                            'title' => esc_html__('Layouts Product', 'funio'),
                            'options' => array('zoom' => esc_html__('Zoom', 'funio'),
												'scroll' => esc_html__('Scroll', 'funio'),
												'one_column' => esc_html__('One Column', 'funio'),
												'slider' => esc_html__('Slider', 'funio'),
												'grid' => esc_html__('Grid', 'funio'),
												'lagre_gallery' => esc_html__('Lagre Gallery', 'funio'),
												'clean' => esc_html__('Clean', 'funio'),
												'moderm' => esc_html__('Moderm', 'funio'),
												'full_width' => esc_html__('Full Width', 'funio'),
											),	
                            'default' => 'zoom',
                        ),
                        array(
                            'id'=>'position-thumbs',
                            'type' => 'button_set',
                            'title' => esc_html__('Position Thumbnails', 'funio'),
                            'options' => array('left' => esc_html__('Left', 'funio'),
												'right' => esc_html__('Right', 'funio'),
												'bottom' => esc_html__('Bottom', 'funio'),
												'outsite' => esc_html__('Outsite', 'funio')),
                            'default' => 'bottom',
							'required' => array('product-thumbs','equals',true),
                        ),						
                        array(
                            'id' => 'product-thumbs-count',
                            'type' => 'button_set',
                            'title' => esc_html__('Thumbnails Count', 'funio'),
                            'options' => array(
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4', 
									'5' => '5', 									
                                    '6' => '6'                          
                                ),
							'default' => '4',
							'required' => array('product-thumbs','equals',true),
                        ),
                        array(
                            'id'=>'product-image-popup',
                            'type' => 'switch',
                            'title' => esc_html__('Enable Image Popup', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),	
						array(
                            'id'=>'single_background',
                            'type' => 'switch',
                            'title' => esc_html__('Show Background Product Image', 'funio'),
                            'default' => false,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
                        ),
						array(
						  'id' => 'single_background_color',
						  'type' => 'button_set',
						  'title' => esc_html__('Background Single Color', 'funio'),
						  'options' => array(
										'dark' => esc_html__('Dark', 'funio'),
										'light' => esc_html__('Light', 'funio')),
						  'default' => 'light',
						  'required' => array('single_background','equals',array(true)),
						),
                        array(
                            'id'=>'zoom-type',
                            'type' => 'button_set',
                            'title' => esc_html__('Zoom Type', 'funio'),
                            'options' => array(
									'inner' => esc_html__('Inner', 'funio'),
									'window' => esc_html__('Window', 'funio'),
									'lens' => esc_html__('Lens', 'funio')
									),
                            'default' => 'inner',
							'required' => array('layout-thumbs','equals',"zoom"),
                        ),
                        array(
                            'id'=>'zoom-scroll',
                            'type' => 'switch',
                            'title' => esc_html__('Scroll Zoom', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
							'required' => array('layout-thumbs','equals',"zoom"),
                        ),
                        array(
                            'id'=>'zoom-border',
                            'type' => 'text',
                            'title' => esc_html__('Border Size', 'funio'),
                            'default' => '2',
							'required' => array('layout-thumbs','equals',"zoom"),
                        ),
                        array(
                            'id'=>'zoom-border-color',
                            'type' => 'color',
                            'title' => esc_html__('Border Color', 'funio'),
                            'default' => '#f9b61e',
							'required' => array('layout-thumbs','equals',"zoom"),
                        ),                      
                        array(
                            'id'=>'zoom-lens-size',
                            'type' => 'text',
                            'required' => array('zoom-type','equals',array('lens')),
                            'title' => esc_html__('Lens Size', 'funio'),
                            'default' => '200',
							'required' => array('layout-thumbs','equals',"zoom"),
                        ),
                        array(
                            'id'=>'zoom-lens-shape',
                            'type' => 'button_set',
                            'required' => array('zoom-type','equals',array('lens')),
                            'title' => esc_html__('Lens Shape', 'funio'),
                            'options' => array('round' => esc_html__('Round', 'funio'), 'square' => esc_html__('Square', 'funio')),
                            'default' => 'square',
							'required' => array('layout-thumbs','equals',"zoom"),
                        ),
                        array(
                            'id'=>'zoom-contain-lens',
                            'type' => 'switch',
                            'required' => array('zoom-type','equals',array('lens')),
                            'title' => esc_html__('Contain Lens Zoom', 'funio'),
                            'default' => true,
                            'on' => esc_html__('Yes', 'funio'),
                            'off' => esc_html__('No', 'funio'),
							'required' => array('layout-thumbs','equals',"zoom"),
                        ),
                        array(
                            'id'=>'zoom-lens-border',
                            'type' => 'text',
                            'required' => array('zoom-type','equals',array('lens')),
                            'title' => esc_html__('Lens Border', 'funio'),
                            'default' => true,
							'required' => array('layout-thumbs','equals',"zoom")
                        ),
                    )
                );
            endif;
            // Blog Settings  -------------
            $this->sections[] = array(
                'icon' => 'icofont icofont-ui-copy',
                'icon_class' => 'icon',
                'title' => esc_html__('Blog', 'funio'),
                'fields' => array(              
                )
            );      
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Blog & Post Archives', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'post-format',
                        'type' => 'switch',
                        'title' => esc_html__('Show Post Format', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'hot-label',
                        'type' => 'text',
                        'title' => esc_html__('"HOT" Text', 'funio'),
                        'desc' => esc_html__('Hot post label', 'funio'),
                        'default' => ''
                    ),
                    array(
                        'id'=>'sidebar_blog',
                        'type' => 'image_select',
                        'title' => esc_html__('Page Layout', 'funio'),
                        'options' => $page_layouts,
                        'default' => 'left'
                    ),
                    array(
                        'id' => 'layout_blog',
                        'type' => 'button_set',
                        'title' => esc_html__('Layout Blog', 'funio'),
                        'options' => array(
                                'list'  =>  esc_html__( 'List', 'funio' ),
                                'grid' =>  esc_html__( 'Grid', 'funio' ),
								'modern' =>  esc_html__( 'Modern', 'funio' ),
								'standar' =>  esc_html__( 'Standar', 'funio' )
                        ),
                        'default' => 'standar',
                        'sub_desc' => esc_html__( 'Select style layout blog', 'funio' ),
                    ),
                    array(
                        'id' => 'blog_col_large',
                        'type' => 'button_set',
                        'title' => esc_html__('Blog Listing column Desktop', 'funio'),
                        'required' => array('layout_blog','equals','grid'),
                        'options' => array(
                                '2' => '2',
                                '3' => '3',
                                '4' => '4',                         
                                '6' => '6'                          
                            ),
                        'default' => '4',
                        'sub_desc' => esc_html__( 'Select number of column on Desktop Screen', 'funio' ),
                    ),
                    array(
                        'id' => 'blog_col_medium',
                        'type' => 'button_set',
                        'title' => esc_html__('Blog Listing column Medium Desktop', 'funio'),
                        'required' => array('layout_blog','equals','grid'),
                        'options' => array(
                                '2' => '2',
                                '3' => '3',
                                '4' => '4',                         
                                '6' => '6'                          
                            ),
                        'default' => '3',
                        'sub_desc' => esc_html__( 'Select number of column on Medium Desktop Screen', 'funio' ),
                    ),   
                    array(
                        'id' => 'blog_col_sm',
                        'type' => 'button_set',
                        'title' => esc_html__('Blog Listing column Ipad Screen', 'funio'),
                        'required' => array('layout_blog','equals','grid'),
                        'options' => array(
                                '2' => '2',
                                '3' => '3',
                                '4' => '4',                         
                                '6' => '6'                          
                            ),
                        'default' => '3',
                        'sub_desc' => esc_html__( 'Select number of column on Ipad Screen', 'funio' ),
                    ),   					
                    array(
                        'id'=>'archives-author',
                        'type' => 'switch',
                        'title' => esc_html__('Show Author', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'archives-comments',
                        'type' => 'switch',
                        'title' => esc_html__('Show Count Comments', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),                  
                    array(
                        'id'=>'blog-excerpt',
                        'type' => 'switch',
                        'title' => esc_html__('Show Excerpt', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'list-blog-excerpt-length',
                        'type' => 'text',
                        'required' => array('blog-excerpt','equals',true),
                        'title' => esc_html__('List Excerpt Length', 'funio'),
                        'desc' => esc_html__('The number of words', 'funio'),
                        'default' => '50',
                    ),
                    array(
                        'id'=>'grid-blog-excerpt-length',
                        'type' => 'text',
                        'required' => array('blog-excerpt','equals',true),
                        'title' => esc_html__('Grid Excerpt Length', 'funio'),
                        'desc' => esc_html__('The number of words', 'funio'),
                        'default' => '12',
                    ),                  
                )
            );
            $this->sections[] = array(
                'icon' => 'icofont icofont-double-right',
                'icon_class' => 'icon',
                'subsection' => true,
                'title' => esc_html__('Single Post', 'funio'),
                'fields' => array(
                    array(
                        'id'=>'post-single-layout',
                        'type' => 'select',
                        'title' => esc_html__('Page Layout', 'funio'),
                        'options' => array(
								'sidebar' =>  esc_html__( 'Sidebar', 'funio' ),
                                'one_column' =>  esc_html__( 'One Column', 'funio' ),
								'prallax_image' =>  esc_html__( 'Prallax Image', 'funio' ),
								'simple_title' =>  esc_html__( 'Simple Title', 'funio' ),
								'sticky_title' =>  esc_html__( 'Sticky Title', 'funio' )
                        ),
                        'default' => 'sidebar'
                    ),
                    array(
                        'id'=>'post-title',
                        'type' => 'switch',
                        'title' => esc_html__('Show Title', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'post-author',
                        'type' => 'switch',
                        'title' => esc_html__('Show Author Info', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
                    ),
                    array(
                        'id'=>'post-comments',
                        'type' => 'switch',
                        'title' => esc_html__('Show Comments', 'funio'),
                        'default' => true,
                        'on' => esc_html__('Yes', 'funio'),
                        'off' => esc_html__('No', 'funio'),
					)
				)
			);	
            $this->sections[] = array(
				'id' => 'wbc_importer_section',
				'title'  => esc_html__( 'Demo Importer', 'funio' ),
				'icon'   => 'fa fa-cloud-download',
				'desc'   => wp_kses( 'Increase your max execution time, try 40000 I know its high but trust me.<br>
				Increase your PHP memory limit, try 512MB.<br>
				1. The import process will work best on a clean install. You can use a plugin such as WordPress Reset to clear your data for you.<br>
				2. Ensure all plugins are installed beforehand, e.g. WooCommerce - any plugins that you add content to.<br>
				3. Be patient and wait for the import process to complete. It can take up to 3-5 minutes.<br>
				4. Enjoy','social' ),				
				'fields' => array(
					array(
						'id'   => 'wbc_demo_importer',
						'type' => 'wbc_importer'
					)
				)
            );			
        }
        public function setHelpTabs() {
        }
        public function setArguments() {
            $theme = wp_get_theme(); // For use with some settings. Not necessary.
            $this->args = array(
                'opt_name'          => 'funio_settings',
                'display_name'      => $theme->get('Name') . ' ' . esc_html__('Theme Options', 'funio'),
                'display_version'   => esc_html__('Theme Version: ', 'funio') . funio_version,
                'menu_type'         => 'submenu',
                'allow_sub_menu'    => true,
                'menu_title'        => esc_html__('Theme Options', 'funio'),
                'page_title'        => esc_html__('Theme Options', 'funio'),
                'footer_credit'     => esc_html__('Theme Options', 'funio'),
                'google_api_key' => 'AIzaSyAX_2L_UzCDPEnAHTG7zhESRVpMPS4ssII',
                'disable_google_fonts_link' => true,
                'async_typography'  => false,
                'admin_bar'         => false,
                'admin_bar_icon'       => 'dashicons-admin-generic',
                'admin_bar_priority'   => 50,
                'global_variable'   => '',
                'dev_mode'          => false,
                'customizer'        => false,
                'compiler'          => false,
                'page_priority'     => null,
                'page_parent'       => 'themes.php',
                'page_permissions'  => 'manage_options',
                'menu_icon'         => '',
                'last_tab'          => '',
                'page_icon'         => 'icon-themes',
                'page_slug'         => 'funio_settings',
                'save_defaults'     => true,
                'default_show'      => false,
                'default_mark'      => '',
                'show_import_export' => true,
                'show_options_object' => false,
                'transient_time'    => 60 * MINUTE_IN_SECONDS,
                'output'            => true,
                'output_tag'        => true,
                'database'              => '',
                'system_info'           => false,
                'hints' => array(
                    'icon'          => 'icon-question-sign',
                    'icon_position' => 'right',
                    'icon_color'    => 'lightgray',
                    'icon_size'     => 'normal',
                    'tip_style'     => array(
                        'color'         => 'light',
                        'shadow'        => true,
                        'rounded'       => false,
                        'style'         => '',
                    ),
                    'tip_position'  => array(
                        'my' => 'top left',
                        'at' => 'bottom right',
                    ),
                    'tip_effect'    => array(
                        'show'          => array(
                            'effect'        => 'slide',
                            'duration'      => '500',
                            'event'         => 'mouseover',
                        ),
                        'hide'      => array(
                            'effect'    => 'slide',
                            'duration'  => '500',
                            'event'     => 'click mouseleave',
                        ),
                    ),
                ),
                'ajax_save'                 => true,
                'use_cdn'                   => true,
            );
            // Panel Intro text -> before the form
            if (!isset($this->args['global_variable']) || $this->args['global_variable'] !== false) {
                if (!empty($this->args['global_variable'])) {
                    $v = $this->args['global_variable'];
                } else {
                    $v = str_replace('-', '_', $this->args['opt_name']);
                }
            }
            $this->args['intro_text'] = sprintf('<p style="color: #0088cc">'.wp_kses('Please regenerate again default css files in <strong>Skin > Compile Default CSS</strong> after <strong>update theme</strong>.', 'funio').'</p>', $v);
        }           
    }
	if ( !function_exists( 'wbc_extended_example' ) ) {
		function wbc_extended_example( $demo_active_import , $demo_directory_path ) {
			reset( $demo_active_import );
			$current_key = key( $demo_active_import );	
			if ( isset( $demo_active_import[$current_key]['directory'] ) && !empty( $demo_active_import[$current_key]['directory'] )) {
				//Import Sliders
				if ( class_exists( 'RevSlider' ) ) {
					$wbc_sliders_array = array(
						'funio' => array('slider-1.zip','slider-2.zip','slider-3.zip','slider-4.zip','slider-5.zip','slider-6.zip','slider-7.zip','slider-8.zip')
					);
					$wbc_slider_import = $wbc_sliders_array[$demo_active_import[$current_key]['directory']];
					if( is_array( $wbc_slider_import ) ){
						foreach ($wbc_slider_import as $slider_zip) {
							if ( !empty($slider_zip) && file_exists( $demo_directory_path.'rev_slider/'.$slider_zip ) ) {
								$slider = new RevSlider();
								$slider->importSliderFromPost( true, true, $demo_directory_path.'rev_slider/'.$slider_zip );
							}
						}
					}else{
						if ( file_exists( $demo_directory_path.'rev_slider/'.$wbc_slider_import ) ) {
							$slider = new RevSlider();
							$slider->importSliderFromPost( true, true, $demo_directory_path.'rev_slider/'.$wbc_slider_import );
						}
					}
				}				
				// Setting Menus
				$primary = get_term_by( 'name', 'Main menu', 'nav_menu' );
				$primary_vertical = get_term_by( 'name', 'Vertical Menu', 'nav_menu' );
				$primary_currency = get_term_by( 'name', 'Currency Menu', 'nav_menu' );
				$primary_language = get_term_by( 'name', 'Language Menu', 'nav_menu' );
				$primary_topbar   = get_term_by( 'name', 'Menu Topbar', 'nav_menu' );
				if ( isset( $primary->term_id ) ) {
					set_theme_mod( 'nav_menu_locations', array(
							'main_navigation' => $primary->term_id,
							'vertical_menu' => $primary_vertical->term_id,
							'currency_menu' => $primary_currency->term_id,
							'language_menu' => $primary_language->term_id,
							'topbar_menu' => $primary_topbar->term_id	
						)
					);
				}
				// Set HomePage
				$home_page = 'Home Modern';
				$page = get_page_by_title( $home_page );
				if ( isset( $page->ID ) ) {
					update_option( 'page_on_front', $page->ID );
					update_option( 'show_on_front', 'page' );
				}					
			}
		}
		// Uncomment the below
		add_action( 'wbc_importer_after_content_import', 'wbc_extended_example', 10, 2 );
	}
    global $reduxFunioSettings;
    $reduxFunioSettings = new Redux_Framework_funio_settings();
}