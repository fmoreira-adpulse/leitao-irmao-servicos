<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Custom_Related_Products
 * @subpackage Custom_Related_Products/admin/partials
 */

?>

<?php $wt_rp_admin_gopro_img_path = CRP_PLUGIN_URL . 'admin/img/gopro'; ?>

<div class="wrap wt-crp-container">
	<?php settings_errors(); ?>
	<div class="crp-main-container">
		<div class="wt-crp-general-settings">
			<p style="font-size: 16px;">
				<b> <?php esc_html_e( 'Settings', 'wt-woocommerce-related-products' ); ?></b>
			</p>
			<p style="border-top: 1px dashed rgb(204, 204, 204); padding-top: 5px;"></p>
		</div>
		<div class="wt-crp-form-container">
			<form action="options.php" method="post">
				<?php
				settings_fields( $this->plugin_name );
				do_settings_sections( $this->plugin_name );
				submit_button();
				?>
			</form>
		</div>
	</div>
	<div class="crp-right-container" style="float: <?php echo is_rtl() ? 'left;' : 'right;'; ?>">
		<div style="background: #fff; height:auto; padding: 15px; box-shadow: 0px 0px 2px #ccc;">
			<h2 style="text-align: center;margin-top: 10px;"><?php esc_html_e( 'Watch setup video', 'wt-woocommerce-related-products' ); ?></h2>
			<iframe src="//www.youtube.com/embed/KOMx3g-ZMQs" allowfullscreen="allowfullscreen" frameborder="0" align="middle" style="width:100%;margin-bottom: 1em;margin-top: 4px;"></iframe>
		</div>
		
		<?php
		/* Exclusive Go Pro section - temporarily disabled
		<div class="wt_rp_exclusive_gopro">
			<div class="wt_rp_exclusive_gopro_content">
				<h3><?php _e( 'Exclusive for <span class="exclusive_txt">you!<span>', 'wt-woocommerce-related-products' ); ?></h3>
				<img class="wt_rp_30_discount_img"src="<?php echo esc_url( $wt_rp_admin_gopro_img_path . '/30-discount.svg' ); ?>" height="78px" width="87px">
				<p class="wt_rp_exclusive_gopro_txt">
					<?php 
					echo wp_kses(
						__( 'Our free plugins are getting all of the premium promotion plugins at <b>30% off</b>.', 'wt-woocommerce-related-products' ),
						array( 'b' => array() )
					); 
					?>
				</p>
				<div class="wt_rp_copy_content">
					<a href="#" style="background: #007FFF; border-radius: 5px;"><img src="<?php echo esc_url( $wt_rp_admin_gopro_img_path . '/promo-code.svg' ); ?>" style="height: 35px; margin-top: 8px;"><a>            
					<p><?php esc_html_e( 'Use code at checkout', 'wt-woocommerce-related-products' ); ?></p>
					<p class="wt_rp_copied"><?php esc_html_e( 'Copied!', 'wt-woocommerce-related-products' ); ?></p>
				</div>
			</div>
		</div>
		*/
		?>

		<div class="wt_rp_plugin_promo">
			<div class="wt_rp_plugin_promo_content">
				<div class="wt_rp_plugin_promo_title_wrapper">
					<img src="<?php echo esc_url( $wt_rp_admin_gopro_img_path . '/product-recommendation-plugin.svg' ); ?>">
					<h3><?php esc_html_e( 'WooCommerce Product Recommendations', 'wt-woocommerce-related-products' ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Get the most from premium version Get the most from premium version', 'wt-woocommerce-related-products' ); ?></p>
				
			</div>
			<div class="wt_rp_gopro_buttons">
				<a href="#" class="wt_rp_pro_features_btn wt_rp_product_recomm_show_btn "><?php esc_html_e( 'Premium features', 'wt-woocommerce-related-products' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
				<a href="#" class="wt_rp_pro_hide_features_btn wt_rp_product_recomm_hide_btn"><?php esc_html_e( 'Hide features', 'wt-woocommerce-related-products' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
				<a href="https://www.webtoffee.com/product/woocommerce-product-recommendations/?utm_source=free_plugin_sidebar&utm_medium=related_free_plugin&utm_campaign=Product_Recommendations" target="_blank" class="wt_rp_view_plugin_btn"><?php esc_html_e( 'View plugin', 'wt-woocommerce-related-products' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
			</div>
			<div class="wt_rp_pro_feature_content">
				<div class="wt_rp_pro_feature_content_div wt_rp_product_recomm_points">
					<ul class="ticked-list">
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Automatic product recommendations', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Generate suggestions using filters', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Place recommendations on relevant pages', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Sort products by price, popularity, rating, etc. ', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Display recommendations in a grid or a slider', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Hide out-of-stock products from suggestions ', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Multiple product type support ', 'wt-woocommerce-related-products' ); ?></li>
						</ul>
				</div>
			</div>
		</div>

		<div class="wt_rp_marketing_automation">
			<div class="wt_rp_marketing_automation_content">
				<span style="background-color:#FFD159 ; color: #515151; padding: 4px 8px; border-radius: 70px; font-size: 12px; font-weight: 700; display: inline-block; margin-bottom: 10px;">Special introductory offer</span>
				<h3><?php _e( 'WebToffee <span class="exclusive_txt">Woocommerce Marketing Automation</span> App', 'wt-woocommerce-related-products' ); ?></h3>
				<p class="wt_rp_marketing_automation_txt">
					<?php 
					echo wp_kses(
						__( 'Automate your marketing emails, and create dynamic popups, and powerful promotion campaigns on your WooCommerce store.', 'wt-woocommerce-related-products' ),
						array( 'b' => array() )
					); 
					?>
				</p>
				<div class="wt_rp_marketing_automation_cta">
					<a href="https://www.webtoffee.com/ecommerce-marketing-automation/?utm_source=free_plugin_sidebar&utm_medium=related_free_plugin&utm_campaign=EMA" target="_blank" class="wt_rp_signup_btn"><?php esc_html_e('Sign up for free', 'wt-woocommerce-related-products'); ?></a>            
					<p style="margin: 0px; padding-top: 10px;"><?php esc_html_e( '*Offer valid only for a limited time.', 'wt-woocommerce-related-products' ); ?></p>
				</div>
			</div>
		</div>

		<div class="wt_rp_plugin_promo wt_rp_margin_click_style_fbt" >
			<div class="wt_rp_plugin_promo_content">
				<div class="wt_rp_plugin_promo_title_wrapper">
					<img src="<?php echo esc_url( $wt_rp_admin_gopro_img_path . '/frequently-bought-together-plugin.svg' ); ?>">
					<h3><?php esc_html_e( 'Frequently Bought Together For WooCommerce', 'wt-woocommerce-related-products' ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Get the most from premium version Get the most from premium version ', 'wt-woocommerce-related-products' ); ?></p>
			</div>
			<div class="wt_rp_gopro_buttons">
				<a href="#" class="wt_rp_pro_features_btn wt_rp_fbt_show_btn"><?php esc_html_e( 'Premium features', 'wt-woocommerce-related-products' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
				<a href="#" class="wt_rp_pro_hide_features_btn wt_rp_fbt_hide_btn"><?php esc_html_e( 'Hide features', 'wt-woocommerce-related-products' ); ?> <span class="dashicons dashicons-arrow-right-alt wt_hide_dashboard_icon"></span></a>
				<a href="https://www.webtoffee.com/product/woocommerce-frequently-bought-together/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Frequently_Bought_Together" target="_blank" class="wt_rp_view_plugin_btn"><?php esc_html_e( 'View plugin', 'wt-woocommerce-related-products' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
			</div>

			<div class="wt_rp_pro_feature_content">
				<div class="wt_rp_pro_feature_content_div wt_rp_fbt_points">
					<ul class="ticked-list">
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Suggest products based on store order history', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Display suggestions on product pages', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Multiple recommendation layouts', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Offers discounts on product bundles', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Use upsells, cross-sells, & related products as frequently bought products', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Customize the title, button, and label texts', 'wt-woocommerce-related-products' ); ?></li>
						<li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'A quick edit page to enable, edit or remove suggestions ', 'wt-woocommerce-related-products' ); ?></li>
						</ul>
				</div>
			</div>
		</div>

		<div class="wt_rp_plugin_promo wt_rp_margin_click_style_bs">

		<div class="wt_rp_extras">
			<div class="wt_rp_extras_content">
				<img src="<?php echo esc_url( $wt_rp_admin_gopro_img_path . '/30day-money-back.svg' ); ?>">
				<h3  style="color: #606060;"><?php esc_html_e( '100% No Risk Money Back Guarantee', 'wt-woocommerce-related-products' ); ?></h3>
			</div>
			<div class="wt_rp_extras_content" style="margin-top: 1px;">
				<img src="<?php echo esc_url( $wt_rp_admin_gopro_img_path . '/satisfaction-rating.svg' ); ?>">
				<h3  style="color: #606060;"><?php esc_html_e( 'Supported by a team with 99% customer satisfaction score', 'wt-woocommerce-related-products' ); ?></h3>
			</div>
		</div>

		

	</div>


</div>

<style>
	.wt_go-review {
		background: #fff;
		float: left;
		/* border-radius: 4px; */
		height: auto;
		padding: 15px;
		box-shadow: 0 0 2px #ccc;
		margin: 15px 0;
	}

	.wt_go-link {
		background: #fff;
		float: left;
		/* border-radius: 4px; */
		height: auto;
		padding: 15px;
		box-shadow: 0 0 2px #ccc;
	}

	.wt_go-review h3 {
		text-align: center;
	}

	.wt-blue-info {
		color: #646970;
		background-color: #d9edf7;
		border-color: #bce8f1;
		padding: 2px 18px 18px;
		border: 1px solid transparent;
		border-radius: 4px;
		margin-top: 32px;
	}

	.wt-crp-container .form-table th {
		width: 290px;
	}

	.crp-main-container {
		width: 68%;
		display: inline-block;
		background-color: white;
		padding: 10px 20px;
	}

	.crp-right-container {
		width: 27%;
		margin-right: 10px;
	}

	.crp-paragraph {
		margin-top: 12px !important;
	}

	.working-mode-field .description {
		margin: 0 0 15px 25px;
	}

	.crp-disallow {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.wt-crp-container .crp-disallow select,
	.wt-crp-container .crp-disallow label,
	.wt-crp-container .crp-disallow input,
	.wt-crp-container .crp-disallow span,
	.wt-crp-container .crp-disallow li {
		cursor: not-allowed !important;
	}

	.wt-crp-container fieldset span.select2 {
		width: 320px !important;
	}

	.wt-crp-select,
	.wt-crp-input {
		width: 320px;
	}

	.wt-crp-info {
		font-size: 14px;
		line-height: 1.8;
	}

	.wt-crp-info-box {
		color: #646970;
		background-color: #d9edf7;
		border-color: #bce8f1;
		padding: 14px 14px 5px;
		border: 1px solid transparent;
		border-radius: 4px;
		margin-bottom: 14px;
		width: auto !important;
	}

	.crp-alert {
		position: relative;
		padding: 0.75rem 1.25rem;
		margin-bottom: 1rem;
		border: 1px solid transparent;
		border-radius: 0.25rem;
	}

	.crp-seconday-alert {
		color: #646970;
		background-color: #d9edf7;
		border-color: #bce8f1;
	}

	.crp-info-alert {
		color: #0c5460;
		background-color: #d1ecf1;
		border-color: #bee5eb;
	}

	.crp-warning-alert {
		color: #856404;
		background-color: #fff3cd;
		border-color: #ffeeba;
	}

	.wt-crp-note {
		font-size: 13px !important;
	}

	.crp-overide-theme .crp-alert {
		margin-top: 8px;
		width: 70%;
		display: none;
	}

	.wt_crp_branding {
		text-align: end;
		width: 100%;
		margin-bottom: 10px;
	}

	.wt_crp_brand_label {
		width: 100%;
		padding-bottom: 10px;
		font-size: 11px;
		font-weight: 600;
	}

	.wt_crp_brand_logo img {
		max-width: 100px;
	}

	.inner-addon {
		position: relative;
		margin: 10px;
	}

	/* style icon */
	.inner-addon .glyphicon {
		position: absolute;
		pointer-events: none;
	}

	/* align icon */
	.left-addon .glyphicon {
		left: 0;
	}

	.right-addon .glyphicon {
		right: 0;
	}

	/* add padding */
	.left-addon input {
		padding-left: 40px !important;
	}

	.right-addon input {
		padding-right: 30px;
	}

	.wt-preview-desktop:before {
		content: "\f472";
		display: inline-block;
		-webkit-font-smoothing: antialiased;
		font: normal 20px/21px dashicons;
		vertical-align: top;
		margin: 1px 2px;
		padding: 4px;
		border-right: 1px solid #8c8f94;
	}

	.wt-preview-tablet:before {
		content: "\f471";
		display: inline-block;
		-webkit-font-smoothing: antialiased;
		font: normal 20px/21px dashicons;
		vertical-align: top;
		margin: 1px 2px;
		padding: 4px;
		border-right: 1px solid #8c8f94;
	}

	.wt-preview-mobile:before {
		content: "\f470";
		display: inline-block;
		-webkit-font-smoothing: antialiased;
		font: normal 20px/21px dashicons;
		vertical-align: top;
		margin: 1px 2px;
		padding: 4px;
		border-right: 1px solid #8c8f94;
	}

	#custom_related_products_crp_banner_product_width_mobile::-webkit-inner-spin-button,
	#custom_related_products_crp_banner_product_width_mobile::-webkit-outer-spin-button,
	#custom_related_products_crp_banner_product_width_tab::-webkit-inner-spin-button,
	#custom_related_products_crp_banner_product_width_tab::-webkit-outer-spin-button,
	#custom_related_products_crp_banner_product_width_desk::-webkit-inner-spin-button,
	#custom_related_products_crp_banner_product_width_desk::-webkit-outer-spin-button {
		opacity: 1;
	}

	.wt_rp_exclusive_gopro {
		position: relative;
		width: 92%;
		background: #fff;
		color: #595959;
		height: 200px;
		padding: 4%;
		margin: 9% 0;
		display: flex;
	}

	.wt_rp_marketing_automation {
		position: relative;
		width: 92%;
		background: #fff;
		color: #595959;
		height: 100%;
		min-height: 330px;
		padding: 5% 4%;
		margin: 9% 0;
		display: flex;
	}

	.wt_rp_exclusive_gopro_txt,
	.wt_rp_marketing_automation_txt {
		font-size: 15px;
		color: #000;
	}

	.wt_rp_exclusive_gopro h3 {
		font-size: 22px;
		padding-left: 10px;
		padding-bottom: 20px;
		line-height: 25px;
	}

	.wt_rp_marketing_automation h3 {
		font-size: 18px;
		line-height: 25px;
		text-align: left;
		margin: 0;
		padding: 10px 15px 20px 0;
	}

	.wt_rp_exclusive_gopro .wt_rp_30_discount_img {
		position: absolute;
		left: 239px;
		bottom: 170px;
	}

	.wt_rp_exclusive_gopro_content h3,
	.wt_rp_marketing_automation_content h3 {
		border-bottom: 1px dashed #595959;
	}

	.wt_rp_marketing_automation_content {
		padding: 10px;
	}

	.exclusive_txt {
		color: #007FFF;
	}

	.wt_rp_marketing_automation .exclusive_txt {
		font-weight: 600;
	}

	.wt_rp_copy_content {
		display: flex;
		font-size: 15px;
		padding-left: 10px;
	}

	.wt_rp_copy_content a {
		outline: none;
	}

	.wt_rp_copy_content p {
		padding-left: 10px;
		font-size: 14px;
		color: black;
		font-weight: 400;
	}

	.wt_rp_copied {
		position: absolute;
		top: 192px;
		right: 243px;
		display: none;
		font-size: 9px;
	}

	.wt_rp_promo_code a {
		background: #007FFF;
		border-radius: 5px;
	}

	.wt_rp_plugin_promo {
		height: auto;
		width: 92%;
		background: #fff;
		color: #000000;
		padding: 4%;
		margin-top: 20px;
	}

	.wt_rp_plugin_promo_content {
		padding-left: 15px;
		padding-right: 20px;
	}

	.wt_rp_plugin_promo_title_wrapper img {
		width: 45px;
		height: 45px;
	}

	.wt_rp_plugin_promo_title_wrapper h3 {
		font-size: 15px;
		font-weight: 700;
		margin-top: -1px;
		line-height: 25px;
		padding-left: 10px;
	}

	.wt_rp_plugin_promo_title_wrapper {
		padding-top: 18px;
		display: flex;
		color: #000000;
	}

	.wt_rp_plugin_promo_content p {
		font-weight: 500;
		font-size: 14px;
		color: #959595;
	}

	.wt_rp_gopro_buttons {
		height: auto;
		margin: 17px -15px;
	}

	.wt_rp_pro_features_btn {
		text-decoration: none;
		font-size: 14px;
		color: #36AF00;
		padding-right: 40px;
		padding-left: 29px;
	}

	.wt_rp_pro_hide_features_btn {
		text-decoration: none;
		font-size: 14px;
		color: #3176FD;
		padding-right: 70px;
		display: none;
		margin-left: 34px;
	}

	.wt_rp_view_plugin_btn {
		text-decoration: none;
		font-size: 14px;
		color: #3176FD;
	}

	.wt_rp_gopro_buttons .dashicons-arrow-right-alt {
		font-size: 14px;
		display: inline;
		vertical-align: middle;
	}

	.wt_rp_plugin_promo_content_points {
		background: #fff;
		width: 92%;
		margin-top: 1px;
		margin-left: -15px;
		margin-right: -15px;
		padding: 2px 7px 2px 10px;
		line-height: 25px;
	}

	.wt_rp_plugin_promo_content_points ul {
		margin-left: 20px;
	}

	.wt_rp_plugin_promo_content_points .text_content {
		color: green;
	}

	.wt_rp_plugin_promo_content_points li {
		line-height: 16px;
		font-weight: 400;
		font-size: 15px;
		margin-right: 21px;
		padding: 9px 0;
	}

	.wt_rp_plugin_promo_content_points li .dashicons {
		margin-right: 7px;
		font-size: 16px;
	}

	.wt_rp_plugin_promo_content_points li .dashicons-yes-alt {
		color: #18c01d;
		font-size: 20px;
		line-height: 18px;
		padding-bottom: 10px;
	}

	.wt_rp_plugin_promo_content_points .ticked-list li .dashicons {
		background: #fff;
		color: #6ABE45;
		border-radius: 20px;
		margin-right: 5px;
		margin-left: -25px;
	}

	.wt_rp_plugin_promo_content_points .ticked-list li {
		margin-bottom: 15px;
		padding-left: 25px;
		color: #000;
	}

	.ticked-list {
		font-size: 13px;
		line-height: 1.4em;
		padding-left: 0;
		padding-top: 10px;
	}

	.wt_rp_extras_content {
		background: #fff;
		color: #606060;
		float: left;
		padding: 6%;
		display: flex;
		width: 97%;
		margin-left: -4%;
	}

	.wt_rp_extras_content h3 {
		line-height: 22px;
		margin-left: 10px;
		font-size: 14px;
	}

	.wt_rp_pro_feature_content_div {
		padding: 4%;
		padding-top: 10px;
		background: white;
		font-weight: 400;
		display: none;
	}

	.wt_rp_pro_feature_content ul {
		padding-left: 11px;
		padding-right: 40px;
		font-size: 14px;
	}

	.wt_rp_pro_feature_content .ticked-list li {
		margin-bottom: 15px;
		padding-left: 35px;
		color: #000;
	}

	.wt_rp_pro_feature_content .ticked-list li div {
		margin-top: -22px;
		margin-left: 7px;
		line-height: 24px;
	}

	.wt_rp_pro_feature_content .ticked-list li .dashicons {
		background: #fff;
		color: #6ABE45;
		border-radius: 20px;
		margin-right: 5px;
		margin-left: -25px;
	}

	.crp-number .description,
	.crp-banner-width .description {
		margin-left: 110px;
		margin-top: -31px;
	}

	.wt_rp_signup_btn {
		display: inline-block;
		background: #007FFF;
		border-radius: 5px;
		color: #fff !important;
		text-decoration: none;
		font-size: 15px;
		font-weight: 700;
		padding: 12px 5px;
		line-height: 1.4;
		text-align: center;
		min-width: 130px;
		margin: 10px 0;
	}

	.wt_rp_signup_btn:hover {
		background: #0066cc;
		color: #fff !important;
	}

	@media (min-width: 1200px) and (max-width: 1440px) {
		.woocommerce table.form-table input[type=text] {
			width: 330px !important;
		}
		
		.woocommerce table.form-table .select2-container {
			min-width: 330px !important;
		}
		
		.woocommerce table.form-table select {
			width: 330px;
		}
		
		.wt_rp_exclusive_gopro,
		.wt_rp_marketing_automation {
			height: fit-content;
		}
		
		.wt_rp_exclusive_gopro .wt_rp_copy_content,
		.wt_rp_marketing_automation .wt_rp_marketing_automation_cta {
			align-items: center;
		}
		
		.wt_rp_pro_features_btn {
			padding-left: 15px;
			padding-right: 12px;
		}
	}

	@media (max-width: 1200px) {
		.crp-main-container,
		.crp-right-container {
			width: 100%;
			padding: 10px;
		}
		
		.crp-right-container {
			margin-top: 10px;
			float: none !important;
		}
		
		.wrap.wt-crp-container {
			margin-right: 15px;
		}
		
		.wt-crp-form-container {
			padding: 0 10px;
		}
	}
</style>

<script>
	jQuery(document).ready(function(){

		jQuery('.wt_rp_product_recomm_show_btn').click(function(e){
			e.preventDefault();

			jQuery('.wt_rp_product_recomm_points').toggle();
			jQuery('.wt_rp_product_recomm_hide_btn').toggle();
			jQuery('.wt_rp_product_recomm_show_btn').css("display","none");
			jQuery('.wt_rp_margin_click_style_fbt').css("margin-top","30px");

		});

		jQuery('.wt_rp_product_recomm_hide_btn').click(function(e){
			e.preventDefault();

			jQuery('.wt_rp_product_recomm_show_btn').toggle();
			jQuery('.wt_rp_product_recomm_hide_btn').css("display","none");
			jQuery('.wt_rp_product_recomm_points').toggle();
			jQuery('.wt_rp_margin_click_style_fbt').css("margin-top","30px");

		});

		jQuery('.wt_rp_fbt_show_btn').click(function(e){
			e.preventDefault();

			jQuery('.wt_rp_fbt_points').toggle();
			jQuery('.wt_rp_fbt_hide_btn').toggle();
			jQuery('.wt_rp_fbt_show_btn').css("display","none");
			jQuery('.wt_rp_margin_click_style_bs').css("margin-top","30px");
		});

		jQuery('.wt_rp_fbt_hide_btn').click(function(e){
			e.preventDefault();

			jQuery('.wt_rp_fbt_show_btn').toggle();
			jQuery('.wt_rp_fbt_hide_btn').css("display","none");
			jQuery('.wt_rp_fbt_points').toggle();
			jQuery('.wt_rp_margin_click_style_bs').css("margin-top","30px");

		});

		jQuery('.wt_rp_best_sellers_show_btn').click(function(e){
			e.preventDefault();

			jQuery('.wt_rp_best_sellers_points').toggle();
			jQuery('.wt_rp_best_sellers_hide_btn').toggle();
			jQuery('.wt_rp_best_sellers_show_btn').css("display","none");
			jQuery('.wt_rp_extras').css("margin-top","30px");
		});

		jQuery('.wt_rp_best_sellers_hide_btn').click(function(e){
			e.preventDefault();

			jQuery('.wt_rp_best_sellers_show_btn').toggle();
			jQuery('.wt_rp_best_sellers_hide_btn').css("display","none");
			jQuery('.wt_rp_best_sellers_points').toggle();
			jQuery('.wt_rp_extras').css("margin-top","10px");

		});

	});

</script>

