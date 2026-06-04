<?php
/**
 * Framework help field.
 *
 * @package    Woo_Gallery_Slider
 * @subpackage Woo_Gallery_Slider/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.
if ( ! class_exists( 'WCGS_Field_sp_help_free' ) ) {
	/**
	 *
	 * Field: help
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class WCGS_Field_sp_help_free extends WCGS_Fields {

		/**
		 * Help field constructor.
		 *
		 * @param array  $field The field type.
		 * @param string $value The values of the field.
		 * @param string $unique The unique ID for the field.
		 * @param string $where To where show the output CSS.
		 * @param string $parent The parent args.
		 */
		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {

			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		/**
		 * Render
		 *
		 * @return void
		 */
		public function render() {
			echo wp_kses_post( $this->field_before() );
			?>
		<div class="about-wrap sp-wcgs-help">
			<h1><?php esc_html_e( 'Welcome to WooCommerce Gallery Slider!', 'gallery-slider-for-woocommerce' ); ?></h1>
			<p class="about-text">
			<?php
			esc_html_e( 'Get introduced to WooCommerce Gallery Slider plugin by watching our "Getting Started" video tutorial series. This video will help you get started with the plugin.', 'gallery-slider-for-woocommerce' );
			?>
				</p>
			<div class="wp-badge"></div>

			<div class="headline-feature-video">
			<div class="headline-feature feature-video">
				<iframe width="560" height="315" src="https://www.youtube.com/embed/GUUcDfFSoP8?list=PLoUb-7uG-5jP0hHs2c_bD2k8r3ALZMYto" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
			</div>
				</div>

			<div class="feature-section three-col">
				<div class="col">
					<div class="sp-wcgs-feature ">
						<h3><i class="sp-font fa fa-headphones"></i> Need Help?</h3>
						<p>Stuck with any issues? No worries! Our Expert Support Team is always ready to help you out promptly.</p>
						<a href="https://shapedplugin.com/support/" target="_blank" class="wcgs-help-button">Get Support</a>
					</div>
				</div>
				<div class="col">
					<div class="sp-wcgs-feature">
						<h3><i class="sp-font fa fa-file-text" ></i>Documentation</h3>
						<p>Check out our documentation page and more information about what you can do with WooCommerce Gallery Slider.</p>
						<a href="https://docs.shapedplugin.com/docs/gallery-slider-for-woocommerce/overview/" target="_blank" class="wcgs-help-button">Documentation</a>
					</div>
				</div>
				<div class="col">
					<div class="sp-wcgs-feature">
						<h3><i class="sp-font fa fa-heart-o" ></i>Show Your Love</h3>
						<p>We are really thankful to choose our plugin. Take your 1 minute to review the plugin and spread the love to inspire us.</p>
						<a href="https://wordpress.org/support/plugin/gallery-slider-for-woocommerce/reviews/?filter=5" target="_blank" class="wcgs-help-button">Review Now</a>
					</div>
				</div>
			</div>

			<div class="about-wrap sp-wcgs-plugin-section">
				<div class="sp-plugin-section-title text-center">
					<h2>Take your shop beyond the typical with more WooCommerce plugins!</h2>
					<h4>Some of our powerful premium plugins are ready to make your online store awesome.</h4>
				</div>
				<div class="feature-section first-cols three-col">
				<div class="col">
					<a href="https://shapedplugin.com/woocommerce-product-slider/" target="_blank" alt="WooCommerce Product Slider Pro" class="wcgs-plugin-link">
						<div class="sp-wcgs-feature ">
						<img src="https://shapedplugin.com/wp-content/uploads/edd/2022/08/woo-product-slider.png" alt="WooCommerce Product Slider Pro" class="wcgs-help-img">
						<h3>Product Slider for WooCommerce</h3>
						<p>Boost sales by interactive product Slider, Grid, and Table in your WooCommerce website or store.</p>
					</div>
					</a>
				</div>
				<div class="col">
					<a href="https://shapedplugin.com/plugin/woocommerce-category-slider-pro/" target="_blank" alt="Category Slider Pro for WooCommerce" class="wcgs-plugin-link">
						<div class="sp-wcgs-feature ">
						<img src="https://shapedplugin.com/wp-content/uploads/edd/2022/08/woo-category-slider.png" alt="Category Slider Pro for WooCommerce" class="wcgs-help-img">
						<h3>Category Slider Pro for WooCommerce</h3>
						<p>Display by filtering the list of categories aesthetically and boosting sales.</p>
					</div>
					</a>
				</div>
				<div class="col">
					<a href="https://shapedplugin.com/plugin/woocommerce-quick-view-pro/" target="_blank" alt="Quick View Pro for WooCommerce" class="wcgs-plugin-link">
						<div class="sp-wcgs-feature ">
						<img src="https://shapedplugin.com/wp-content/uploads/edd/2022/08/woo-quick-view.png" alt="Quick View Pro for WooCommerce" class="wcgs-help-img">
						<h3>Quick View Pro for WooCommerce</h3>
						<p>Quickly view product information with smooth animation via AJAX in a nice Modal without opening the product page.</p>
					</div>
					</a>
				</div>
			
				<div class="col">
					<a href="https://shapedplugin.com/easy-accordion/" alt="Easy Accordion" target="_blank" class="wcgs-plugin-link">
						<div class="sp-wcgs-feature ">
						<img src="https://shapedplugin.com/wp-content/uploads/edd/2022/08/easy-accordion.png" alt="Easy Accordion" class="wcgs-help-img">
						<h3>Easy Accordion</h3>
						<p>Minimize customer support by offering comprehensive FAQs and increasing conversions.</p>
					</div>
					</a>
				</div>
				<div class="col">
					<a href="https://shapedplugin.com/real-testimonials/" alt="Real Testimonials" target="_blank" class="wcgs-plugin-link">
						<div class="sp-wcgs-feature ">
						<img src="https://shapedplugin.com/wp-content/uploads/edd/2022/08/real-testimonials.png" alt="Real Testimonials" class="wcgs-help-img">
						<h3>Real Testimonials</h3>
						<p>Simply collect, manage, and display Testimonials on your website and boost conversions.</p>
					</div>
					</a>
				</div>
				<div class="col">
				<a href="https://smartpostshow.com/" alt="Smart Post Show" target="_blank" class="wcgs-plugin-link" >
						<div class="sp-wcgs-feature ">
						<img src="https://shapedplugin.com/wp-content/uploads/edd/2022/08/smart-post-show.png" alt="Smart Post Show" class="wcgs-help-img">
						<h3>Smart Post Show</h3>
						<p>Filter and display posts (any post types), pages, taxonomy, custom taxonomy, and custom field, in beautiful layouts.</p>
					</div>
					</a>
				</div>
			</div>
			</div>

			</div>
			<?php
			echo wp_kses_post( $this->field_after() );
		}

	}
}
