
<?php
if ( ! defined( 'WPINC' ) ) {die;
}


$wt_rp_admin_img_path = CRP_PLUGIN_URL . 'admin/img/other_solutions';
?>
<style>

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
.wt_crp_brand_label {
    width: 100%;
    padding-bottom: 10px;
    font-size: 11px;
    font-weight: 600;
}
.wt_crp_brand_logo img {
    max-width: 100px;
}

.wt-crp-main-container {
    padding: 0 10px 10px 20px;
    display: inline-block;
}

/* Style for You may also need tab starts here */

.wt_exclusive{ position: relative; color: #433434;}

.wt_rp_row_1{ display: flex; position: relative; padding-top: 40px;}
.wt_rp_row_2 { padding: 1% 3%;font-weight: 400;}
.wt_rp_row_2 h2{ font-weight: 700; font-size: 24px;}
.wt_rp_discount_logo{ position: absolute; top: 21px; left: 10px;}

.wt_rp_promo_code { margin-top: 30px; display: flex; border-left: 2px solid #007FFF; padding-left: 30px; padding-right: 90px}
.wt_rp_copied {position: absolute; top: 109px; left: 866px; display: none; font-size: 10px;}
.wt_rp_promo_code p{ font-size: 15px; padding-right: 20px;}
.wt_rp_promo_code_text{ height: 110px; display: flex; border: 2px solid #007FFF; padding-left: 60px; margin-left: 130px; border-radius: 6px;}

.wt_rp_exclusive_for_you { padding-right: 100px}
.wt_rp_exclusive_for_you h1{ font-weight:700; font-size:24px; }
.wt_rp_exclusive_for_you p{ font-weight: 400; font-size: 14px; }

/*.wt_rp_also_need_plugin_row{width: 100%; background: #12265F; color: #FFFFFF; display: flex;}*/
.wt_rp_also_need_plugin_row{width: 102%; background: #FFFFFF; color: #434343; display: flex; margin-left: -19px; border: 1px solid #007FFF; border-radius: 10px;}


.wt_rp_also_need_plugin_title_wrapper img { width: 45px; height: 45px; align-self: center;}
.wt_rp_also_need_plugin_title_wrapper h3{ margin-left: 12px; font-size: 18px; font-weight: 700; color: #212121;}
.wt_rp_also_need_plugin_title_wrapper{ display: flex; }
.wt_rp_also_need_plugin_content_wrapper{ display: flex; }
.wt_rp_also_need_pf_plugin_content_wrapper{ display: block; }

.wt_rp_also_need_plugin_content_left, 
.wt_rp_also_need_plugin_content_right{ flex: 1; }
/*.wt_rp_also_need_plugin_img img{ padding: 65px 100px 60px 100px; }*/
.wt_rp_also_need_plugin_img img{ height: 100%; }
.wt_rp_pf_plugin_img img{ padding: 5% 74px; }
.wt_rp_pf_plugin_img { background-color: #F0F6FF; border-radius: 50px 10px 10px 0;}
.wt_rp_also_need_plugin_img { width: 46%; overflow: hidden; background-color: #F0F6FF; }
.wt_rp_also_need_plugin_content{ width:100%; box-sizing:border-box;  height: auto; padding: 2% 2% 2% 3%;}
.wt_rp_also_need_plugin_content ul{ list-style:none; margin-left:20px; margin-top: 10px; }
.wt_rp_also_need_plugin_content li{ float:left; width:calc(100% - 23px); box-sizing:border-box; padding-left:23px; padding:4px 0px; font-size: 15px; font-weight: 400;}
.wt_rp_also_need_plugin_content li .dashicons{ margin-left:-20px; float:left; color:#6abe45; }
.wt_rp_also_need_plugin_content li .dashicons-yes-alt{ color:#18c01d; margin-right: 0px; font-size: 17px;}

.wt_rp_fbt_content{ box-sizing: border-box; padding: 2% 2% 2% 5%; height: auto; width: 100%;}
.wt_rp_fbt_content ul{ list-style:none; margin-left:20px;color: #434343; }
.wt_rp_fbt_content li{ float:left; width:calc(100% - 23px); box-sizing:border-box; padding-left:23px; padding:4px 0px; font-size: 15px; font-weight: 400; }
.wt_rp_fbt_content li .dashicons{ margin-left:-20px; float:left; color:#434343; }
.wt_rp_fbt_content li .dashicons-yes-alt{ color:#18c01d; margin-right: 0px; font-size: 17px;}

.wt_rp_visit_plugin_btn{ width: 140px; display:inline-block; padding:16px 35px; color:#fff; background:#007FFF; border-radius:5px; text-decoration:none; font-size:14px; margin-top:14px; font-weight: 600; }
.wt_rp_visit_plugin_btn:hover{ color:#fff; text-decoration:none; background:#1da5f8; color:#fff; }

.wt_rp_also_need_list_item{
    float: left;
    margin-left: 20px;
}
.dashicons-info-outline{ color: #007FFF; font-size: 14px; }
.tooltip{position: absolute; left: 529px; bottom: -1px; margin: 10px;}
/* Tooltip text */
.tooltip .tooltiptext {
    visibility: hidden;
    width: 120px;
    background-color: #D1E5FE;
    color: #434343;
    text-align: center;
    padding: 5px 0;

    /* Position the tooltip text */
    position: absolute;
    z-index: 1;
    top: 100%;
    width: 300px;
    margin-left: -47px;

    /* Fade in tooltip */
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 13px;
    font-weight: 400;
    font-style: italic;
}

/* Tooltip arrow */
.tooltip .tooltiptext::after {
    content: "";
    position: absolute;
    bottom: 100%;
    left: 50%;
    margin-left: -117px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent transparent #D1E5FE transparent;
}

.tooltip:hover .tooltiptext {
  visibility: visible;
  opacity: 1;
}
@media (min-width: 520px) and (max-width: 1200px) {
    .wt_rp_pf_plugin_img{
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .wt_rp_pf_plugin_img img{
        width: 100%;
        object-fit: contain;
    }
}
@media (min-width: 768px) and (max-width: 1200px) {
    .wt_rp_also_need_pf_plugin_content_wrapper{ display: flex; }
    .wt_rp_also_need_pf_plugin_content_left,
    .wt_rp_also_need_pf_plugin_content_right{ flex: 1; }
}

@media (max-width: 520px) {
    .wt-crp-main-container{
        width: 90vw;
        padding: 0 10px;
    }
    .wt_rp_also_need_plugin_row{
        margin-left: 0px;
    }
    .wt_rp_also_need_plugin_img,
    .wt_rp_pf_plugin_img{
        display: none;
    }
    .wt_rp_also_need_plugin_content_wrapper{
        flex-direction: column;
    }
}

/* Style for You may also need tab ends here */

</style>

<div class="wt-crp-main-container">
<!-- You may also like starts here -->
    <div class="wt_exclusive">
        <div class="wt_rp_row_2">
            <h2><?php esc_html_e('Premium extensions', 'wt-woocommerce-related-products'); ?></h2>
            <p style="font-size: 15px;"><?php esc_html_e('Level up your product suggestions and improve conversion rates!', 'wt-woocommerce-related-products'); ?></p>
        </div>
    </div>


    <div class="wt_rp_also_need_plugin_row" > 
        <div class="wt_rp_also_need_plugin_content" >
            <div class="wt_rp_also_need_plugin_title_wrapper">
                <img src="<?php echo esc_url($wt_rp_admin_img_path . '/' . 'product-recommendation-plugin.svg');?>">
                <h3><?php esc_html_e('WooCommerce Product Recommendations', 'wt-woocommerce-related-products'); ?></h3>
            </div>
            <p style="font-size: 14px;"><?php esc_html_e( 'Automate tailored product suggestions for better conversions.', 'wt-woocommerce-related-products' ); ?></p>
            <div class="wt_rp_also_need_plugin_content_wrapper">
                <div class="wt_rp_also_need_plugin_content_left">
                    <ul>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Automatic product recommendations','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Generate suggestions using filters','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Place recommendations on relevant pages','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Sort products by price, popularity, rating, etc.','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Display recommendations in a grid or a slider','wt-woocommerce-related-products'); ?></span></li>
                    </ul>
                </div>
                <div class="wt_rp_also_need_plugin_content_right">
                    <ul>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Hide out-of-stock products from suggestions','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Multiple product type support','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Built-in recommendations template','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Create custom recommendations','wt-woocommerce-related-products'); ?></span></li>
                    </ul>
                </div>
            </div>
            
            <a href="https://www.webtoffee.com/product/woocommerce-product-recommendations/?utm_source=free_plugin_addon&utm_medium=related_products&utm_campaign=Product_Recommendations" target="_blank" class="wt_rp_visit_plugin_btn" style="margin-left: 0px;"><?php esc_html_e('Visit plugin page', 'wt-woocommerce-related-products'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
        </div>
        <div class="wt_rp_also_need_plugin_img" style="border-radius: 50px 10px 10px 0; position: relative;">
            <img src="<?php echo esc_url($wt_rp_admin_img_path . '/' .'product-recommendations.png')?>" width="501px"; height="429px"; style="position: absolute; left: 20px">
        </div>
    </div>

    <div class="wt_rp_also_need_plugin_row" style="margin-top: 30px;"> 
        <div class="wt_rp_also_need_plugin_img" style="border-radius: 10px 50px 0 10px; position: relative">
            <img src="<?php echo esc_url($wt_rp_admin_img_path . '/' .'frequently-bought-together.png')?>" width="501px"; height="429px"; style="position: absolute; right: 20px">
        </div>
        <div class="wt_rp_fbt_content">
            <div class="wt_rp_also_need_plugin_title_wrapper">
                <img src="<?php echo esc_url($wt_rp_admin_img_path . '/' . 'frequently-bought-together-plugin.svg');?>">
                <h3><?php esc_html_e('Frequently Bought Together For WooCommerce', 'wt-woocommerce-related-products'); ?></h3>
            </div>
            <p style="font-size: 14px;"><?php esc_html_e( 'Increase average order value with customized product bundles.', 'wt-woocommerce-related-products' ); ?></p>           
            <div class="wt_rp_also_need_plugin_content_wrapper">
                <div class="wt_rp_also_need_plugin_content_left">
                    <ul>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Suggest products based on store order history','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Display suggestions on product pages','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Multiple FBT recommendation layouts','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Offers discounts on product bundles','wt-woocommerce-related-products'); ?></span></li>
                    </ul>
                </div>
                <div class="wt_rp_also_need_plugin_content_right">
                    <ul>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Use upsells, cross-sells, & related products as frequently bought products','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Customize the widget title, button, and label texts','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'A quick edit page to enable, edit or remove suggestions','wt-woocommerce-related-products'); ?></span></li> 
                    </ul>
                </div>
            </div>
            <a href="https://www.webtoffee.com/product/woocommerce-frequently-bought-together/?utm_source=free_plugin_addon&utm_medium=related_products&utm_campaign=Frequently_Bought_Together" target="_blank" class="wt_rp_visit_plugin_btn"><?php esc_html_e('Visit plugin page', 'wt-woocommerce-related-products'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
        </div>
    </div>

    <div class="wt_rp_also_need_plugin_row" style="margin-top: 30px;"> 
        <div class="wt_rp_also_need_plugin_content" style="padding: 2% 3%;">
            <div class="wt_rp_also_need_plugin_title_wrapper">
                <img src="<?php echo esc_url($wt_rp_admin_img_path . '/' . 'product-feed-plugin.svg');?>">
                <h3><?php esc_html_e('Product Feed for WooCommerce Plugin', 'wt-woocommerce-related-products'); ?></h3>
            </div>
            <p style="font-size: 14px;"><?php esc_html_e( 'Generate product feeds to expand sales channels.', 'wt-woocommerce-related-products' ); ?></p>            
            <div class="wt_rp_also_need_pf_plugin_content_wrapper">
                <div class="wt_rp_also_need_pf_plugin_content_left">
                    <ul>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Generate optimized product feeds for 20+ sales channels','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Map WooCommerce product details and categories','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Create feeds for all Google shopping platforms','wt-woocommerce-related-products'); ?></span></li>
                    </ul>
                </div>
                <div class="wt_rp_also_need_pf_plugin_content_right">
                    <ul>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Sync WooCommerce products with Facebook Catalog','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Tailor your product feed with filters','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Track and manage feed updates ','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Keep your product feeds up-to-date','wt-woocommerce-related-products'); ?></span></li>
                    </ul>  
                </div>
            </div>
            <a href="https://www.webtoffee.com/product/woocommerce-product-feed/?utm_source=free_plugin_addon&utm_medium=related_products&utm_campaign=WooCommerce_Product_Feed" target="_blank" class="wt_rp_visit_plugin_btn"><?php esc_html_e('Visit plugin page', 'wt-woocommerce-related-products'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
        </div>
        <div class="wt_rp_pf_plugin_img">
            <img src="<?php echo esc_url($wt_rp_admin_img_path . '/' .'product-feed.png')?>" width="430px"; height="380px";>
        </div>
    </div>

    <div class="wt_rp_also_need_plugin_row" style="margin-top: 30px;"> 
        <div class="wt_rp_also_need_plugin_content" >
            <div class="wt_rp_also_need_plugin_title_wrapper">
                <img src="<?php echo esc_url($wt_rp_admin_img_path . '/' . 'gift-card-plugin.svg');?>">
                <h3><?php esc_html_e('Gift Card Plugin', 'wt-woocommerce-related-products'); ?></h3>
            </div>
            <p style="font-size: 14px;"><?php esc_html_e( 'Create and sell customizable gift cards for any occasion.', 'wt-woocommerce-related-products' ); ?></p>
            <div class="wt_rp_also_need_plugin_content_wrapper">
                <div class="wt_rp_also_need_plugin_content_left">
                    <ul>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Create and sell unlimited gift cards','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Allow customers to buy, redeem, and share','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Schedule gift card delivery','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Sell physical gift cards ','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Provide instant refunds to store credits','wt-woocommerce-related-products'); ?></span></li>
                    </ul>
                </div>
                <div class="wt_rp_also_need_plugin_content_right">
                    <ul>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Set predefined or custom gift card amounts','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Manage user credit balances from a single dashboard','wt-woocommerce-related-products'); ?></span></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><span class="wt_rp_also_need_list_item"><?php esc_html_e( 'Set usage restrictions for gift card coupons','wt-woocommerce-related-products'); ?></span></li>
                    </ul>
                </div>
            </div>
            
            <a href="https://www.webtoffee.com/product/woocommerce-gift-cards/?utm_source=free_plugin_addon&utm_medium=related_products&utm_campaign=WooCommerce_Gift_Cards" target="_blank" class="wt_rp_visit_plugin_btn" style="margin-left: 0px;"><?php _e('Visit plugin page', 'wt-woocommerce-related-products'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
        </div>
        <div class="wt_rp_also_need_plugin_img" style="border-radius: 50px 10px 10px 0; position: relative;">
            <img src="<?php echo esc_url($wt_rp_admin_img_path . '/' .'product-recommendations.png')?>" width="501px"; height="429px"; style="position: absolute; left: 20px">
        </div>
    </div>

<!-- You may also like ends here -->
</div>


