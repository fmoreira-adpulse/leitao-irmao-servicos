<?php 
/*
Plugin Name: WooCommerce Support Ticket System
Description: Ticket system for WooCommerce
Author: Lagudi Domenico
Version: 17.6
*/

/* 
Copyright: WooCommerce Support Ticket System uses the ACF PRO plugin. ACF PRO files are not to be used or distributed outside of the WooCommerce Support Ticket System plugin.
*/


/* Const */
define('WCSTS_PLUGIN_PATH', rtrim(plugin_dir_url(__FILE__), "/") ) ;
define('WCSTS_PLUGIN_ABS_PATH', dirname( __FILE__ ) ); ///ex.: "woocommerce/wp-content/plugins/woocommerce-support-ticket-system"
define('WCSTS_PLUGIN_LANG_PATH', basename( dirname( __FILE__ ) ) . '/languages' ) ;

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );


if ( !defined('WP_CLI') && ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ||
					   (is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins') ))
					 )	
	)
{
	//For some reasins the theme editor in some installtion won't work. This directive will prevent that.
	if(isset($_POST['action']) && $_POST['action'] == 'edit-theme-plugin-file')
		return;
	
	if(isset($_REQUEST ['context']) && $_REQUEST['context'] == 'edit') //rest api
		return;
		
	if(isset($_POST['action']) && strpos($_POST['action'], 'health-check') !== false) //health check
		return;
		
	$wcsts_id = 17930050;
	$wcsts_name = "WooCommerce Support Ticket System";
	$wcsts_activator_slug = "wcsts-activator";
	
	/* Classes Init */
	include_once( "classes/com/WCSTS_Acf.php"); 
	include_once( "classes/com/WCSTS_Globals.php"); 
	require_once('classes/vendor/vanquish/admin/ActivationPage.php');
	

	/* Actions */
	add_action('init', 'wcsts_init');
	add_action('admin_notices', 'wcsts_admin_notices' );
	add_action('admin_menu', 'wcsts_init_act');
	if(defined('DOING_AJAX') && DOING_AJAX)
			wcsts_init_act();
}
/* Functions */
function wcsts_admin_notices()
{
	global $lcsts, $wcsts_name, $wcsts_activator_slug;
	if($lcsts && (!isset($_GET['page']) || $_GET['page'] != $wcsts_activator_slug))
	{
		 ?>
		<div class="notice notice-success">
			<p><?php wcsts_html_escape_allowing_special_tags(sprintf(__('To complete the <span style="color:#96588a; font-weight:bold;">%s</span> plugin activation, you must verify your purchase license. Click <a href="%s">here</a> to verify it.', 'woocommerce-support-ticket-system' ), $wcsts_name, get_admin_url()."admin.php?page=".$wcsts_activator_slug)); ?></p>
		</div>
		<?php
	}
}
function wcsts_init()
{
	/* Languages */
	load_plugin_textdomain('woocommerce-support-ticket-system', false, basename( dirname( __FILE__ ) ) . '/languages' );
	
}
function wcsts_init_act()
{
	global $wcsts_activator_slug, $wcsts_name, $wcsts_id;
	new WCSTS\vendor\vanquish\admin\ActivationPage($wcsts_activator_slug, $wcsts_name, 'woocommerce-support-ticket-system', $wcsts_id, WCSTS_PLUGIN_PATH);
}
function wcsts_eu()
{
	add_action('admin_menu', 'wcsts_init_admin_panel');
	add_action( 'admin_head', 'wcsts_menu_ticket_count' );
	add_action( 'wp_print_scripts', 'wcsts_unregister_css_and_js' );
	add_action('wp_print_scripts', 'wcsts_remove_acf_data_if_needed', 999); //Defined on Globals
	
	global $wcsts_ticket_model, $wcsts_answer_model, $wcsts_product_model, $wcsts_order_model, $wcsts_user_model, $wcsts_ticket_message_model,
		   $wcsts_html_helper, $wcsts_wpml_helper, $wcsts_text_helper, $wcsts_option_helper, $wcsts_file_model, $wcsts_email_model, $wcsts_shortcode_helper,
		   $wcsts_ticket_table_page_addon, $wcsts_user_page_addon, $wcsts_ticket_page_addon, $wcsts_product_page_addon, $wcsts_product_table_page_addon, $wcsts_option_page,
		   $wcsts_text_customizer_page, $wcsts_priority_page, $wcsts_frontend_order_details_page_addon, $wcsts_frontend_my_account_addon, $wcsts_checkout_page_addon, $wcsts_time_model;
	//com
	require_once('classes/vendor/vanquish/com/Updater.php'); 
	new WCSTS\vendor\vanquish\com\Updater(); 
	if(!class_exists('WCSTS_Ticket'))
	{
		require_once('classes/com/WCSTS_Ticket.php');
		$wcsts_ticket_model = new WCSTS_Ticket();
	}
	if(!class_exists('WCSTS_Answer'))
	{
		require_once('classes/com/WCSTS_Answer.php');
		$wcsts_answer_model = new WCSTS_Answer();
	}
	if(!class_exists('WCSTS_Product'))
	{
		require_once('classes/com/WCSTS_Product.php');
		$wcsts_product_model = new WCSTS_Product();
	}
	if(!class_exists('WCSTS_Order'))
	{
		require_once('classes/com/WCSTS_Order.php');
		$wcsts_order_model = new WCSTS_Order();
	}
	if(!class_exists('WCSTS_User'))
	{
		require_once('classes/com/WCSTS_User.php');
		$wcsts_user_model = new WCSTS_User();
	}
	if(!class_exists('WCSTS_TicketMessage'))
	{
		require_once('classes/com/WCSTS_TicketMessage.php');
		$wcsts_ticket_message_model = new WCSTS_TicketMessage();
	}
	if(!class_exists('WCSTS_HtmlHelper'))
	{
		require_once('classes/com/WCSTS_HtmlHelper.php');
		$wcsts_html_helper = new WCSTS_HtmlHelper();
	}
	if(!class_exists('WCSTS_Wpml'))
	{
		require_once('classes/com/WCSTS_Wpml.php');
		$wcsts_wpml_helper = new WCSTS_Wpml();
	}
	if(!class_exists('WCSTS_Text'))
	{
		require_once('classes/com/WCSTS_Text.php');
		$wcsts_text_helper = new WCSTS_Text();
	}
	if(!class_exists('WCSTS_Option'))
	{
		require_once('classes/com/WCSTS_Option.php');
		$wcsts_option_helper = new WCSTS_Option();
	}
	if(!class_exists('WCSTS_File'))
	{
		require_once('classes/com/WCSTS_File.php');
		$wcsts_file_model = new WCSTS_File();
	}
	if(!class_exists('WCSTS_Email'))
	{
		require_once('classes/com/WCSTS_Email.php');
		$wcsts_email_model = new WCSTS_Email();
	}
	if(!class_exists('WCSTS_Shortcode'))
	{
		require_once('classes/com/WCSTS_Shortcode.php');
		$wcsts_shortcode_helper = new WCSTS_Shortcode();
	}
	if(!class_exists('WCSTS_Time'))
	{
		require_once('classes/com/WCSTS_Time.php');
		$wcsts_time_model = new WCSTS_Time();
	}
	//admin
	if(!class_exists('WCSTS_PredefinedAnswerPage'))
	{
		require_once('classes/admin/WCSTS_PredefinedAnswerPage.php');
		new WCSTS_PredefinedAnswerPage();
	}
	if(!class_exists('WCSTS_TicketTablePage'))
	{
		require_once('classes/admin/WCSTS_TicketTablePage.php');
		$wcsts_ticket_table_page_addon = new WCSTS_TicketTablePage();
	}
	if(!class_exists('WCSTS_UserPage'))
	{
		require_once('classes/admin/WCSTS_UserPage.php');
		$wcsts_user_page_addon = new WCSTS_UserPage();
	}
	if(!class_exists('WCSTS_TicketPage'))
	{
		require_once('classes/admin/WCSTS_TicketPage.php');
		$wcsts_ticket_page_addon = new WCSTS_TicketPage();
	}
	if(!class_exists('WCSTS_ProductPage'))
	{
		require_once('classes/admin/WCSTS_ProductPage.php');
		$wcsts_product_page_addon = new WCSTS_ProductPage();
	}
	if(!class_exists('WCSTS_ProductTablePage'))
	{
		require_once('classes/admin/WCSTS_ProductTablePage.php');
		$wcsts_product_table_page_addon = new WCSTS_ProductTablePage();
	}
	if(!class_exists('WCSTS_OptionPage'))
	{
		require_once('classes/admin/WCSTS_OptionPage.php');
		$wcsts_option_page = new WCSTS_OptionPage();
	}
	if(!class_exists('WCSTS_TextCustomizerPage'))
	{
		require_once('classes/admin/WCSTS_TextCustomizerPage.php');
		$wcsts_text_customizer_page = new WCSTS_TextCustomizerPage();
	}
	if(!class_exists('WCSTS_AssignTicketPage'))
	{
		require_once('classes/admin/WCSTS_AssignTicketPage.php');
	}
	if(!class_exists('WCSTS_CustomersAgentsAssociationPage'))
	{
		require_once('classes/admin/WCSTS_CustomersAgentsAssociationPage.php');
	}
	if(!class_exists('WCSTS_CustomStatusConfiguratorPage'))
	{
		require_once('classes/admin/WCSTS_CustomStatusConfiguratorPage.php');
	}
	if(!class_exists('WCSTS_OrderPage'))
	{
		require_once('classes/admin/WCSTS_OrderPage.php');
		new WCSTS_OrderPage(); 
	}
	if(!class_exists('WCSTS_PriorityPage'))
	{
		require_once('classes/admin/WCSTS_PriorityPage.php');
		$wcsts_priority_page = new WCSTS_PriorityPage(); 
	}
	//frontend
	if(!class_exists('WCSTS_OrderDetailsPage'))
	{
		require_once('classes/frontend/WCSTS_OrderDetailsPage.php');
		$wcsts_frontend_order_details_page_addon = new WCSTS_OrderDetailsPage();
	}
	if(!class_exists('WCSTS_MyAccountPage'))
	{
		require_once('classes/frontend/WCSTS_MyAccountPage.php');
		$wcsts_frontend_my_account_addon = new WCSTS_MyAccountPage();
	}
	if(!class_exists('WCSTS_CheckoutPage'))
	{
		require_once('classes/frontend/WCSTS_CheckoutPage.php');
		$wcsts_checkout_page_addon = new WCSTS_CheckoutPage();
	}
	
	//ACF custom fields init: For some reasons, they are not properly initialized via the WCMCA_Acf.php component
	wcsts_acf_init();
}
function wcsts_unregister_css_and_js($enqueue_styles)
{
	WCSTS_AssignTicketPage::force_dequeue_scripts($enqueue_styles);
	$url = $_SERVER['REQUEST_URI'];
	if( strpos($url, '/point-of-sale') !== false)
	{
		wp_dequeue_script('select2');
	}
}
function wcsts_init_admin_panel()
{ 
	global $wcsts_woocommerce_is_active;
	$place = wcsts_get_free_menu_position(69 , .1);
	$cap = 'edit_users';
	
	add_submenu_page('edit.php?post_type=wcsts_ticket', esc_html__('Statuses','woocommerce-support-ticket-system'), esc_html__('Statuses','woocommerce-support-ticket-system'), 'manage_woocommerce', 'wcsts-custom-statuses-configurator', 'wcsts_render_wcsts_custom_statuses_configurator_page');
	add_submenu_page('edit.php?post_type=wcsts_ticket', esc_html__('Tickets assignment to agents','woocommerce-support-ticket-system'), esc_html__('Tickets assignment to agents','woocommerce-support-ticket-system'), 'manage_woocommerce', 'wcsts-assign-ticket', 'wcsts_render_wcsts_assign_ticket_page');
	add_submenu_page('edit.php?post_type=wcsts_ticket', esc_html__('Customers <-> Agents association','woocommerce-support-ticket-system'), esc_html__('Customers <-> Agents association','woocommerce-support-ticket-system'), 'manage_woocommerce', 'wcsts-customer-agents-association-ticket', 'wcsts_render_customers_agents_association');
}
function wcsts_render_wcsts_assign_ticket_page()
{
	$page = new WCSTS_AssignTicketPage();
	$page->render_page();
}
function wcsts_render_customers_agents_association()
{
	$page = new WCSTS_CustomersAgentsAssociationPage();
	$page->render_page();
}
function wcsts_render_wcsts_custom_statuses_configurator_page()
{
	$page = new WCSTS_CustomStatusConfiguratorPage();
	$page->render_page();
}
function wcsts_show_manage_ticket_visibility_page()
{
	$wcsts_manage_ticket_visibility_page = new WCSTS_ManageTicketVisibilityPage();
	$wcsts_manage_ticket_visibility_page->prepare_items();
	$wcsts_manage_ticket_visibility_page->render_page();
	
}
function wcsts_get_free_menu_position($start, $increment = 0.1)
{
	foreach ($GLOBALS['menu'] as $key => $menu) {
		$menus_positions[] = $key;
	}
	
	if (!in_array($start, $menus_positions)) return $start;

	/* the position is already reserved find the closet one */
	while (in_array($start, $menus_positions)) 
	{
		$start += $increment;
	}
	return (string)$start;
}
function wcsts_var_dump($var)
{
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
}

if (!function_exists('apache_request_headers')) { 
        function apache_request_headers() 
		{ 
            foreach($_SERVER as $key=>$value) 
			{ 
                if (substr($key,0,5)=="HTTP_") { 
                    $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5))))); 
                    $out[$key]=$value; 
                }else{
                    $out[$key]=$value; 
				}
            } 
            return $out; 
        } 
		
}
?>