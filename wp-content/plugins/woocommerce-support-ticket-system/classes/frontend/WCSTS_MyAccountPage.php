<?php 
class WCSTS_MyAccountPage
{
	public function __construct()
	{
		
		try{
			$wc_version = wcsts_get_woo_version_number();
		}catch(Exception $e){}
		
		if(!isset($wc_version) || version_compare($wc_version , 2.6, '<') )
			add_action( 'woocommerce_after_my_account', array( &$this, 'add_order_table_extra_buttons'));
		if(isset($wc_version) && version_compare($wc_version , 2.6, '>=') )
			add_action( 'woocommerce_account_content', array( &$this,'add_order_table_extra_buttons'),99 );
		
		add_filter('woocommerce_account_menu_items', array(&$this,'add_user_ticket_area_to_menu'));
		add_action( 'init', array(&$this,'add_custom_endpoints') );
		add_filter( 'query_vars', array(&$this,'add_end_point_to_query_vars'), 0 );
		
		//add_action( 'woocommerce_account_wcsts-user-tickets-area_endpoint', array(&$this,'render_user_ticket_area') ); //Old endpoint handler
		add_filter( 'the_title',  array(&$this,'render_user_ticket_area_title') );
		
		//Order table
		add_filter( 'woocommerce_account_orders_columns',  array(&$this,'add_ticket_number_column') );
		add_action('woocommerce_my_account_my_orders_column_wcsts-ticket-number', array(&$this,'add_ticket_number_column_content') );
		add_action('woocommerce_my_account_my_orders_column_wcsts-new-admin-messages-number', array(&$this,'add_new_admin_messages_number_column_content') );
	}
	function add_ticket_number_column($columns)
	{
		global $wcsts_option_helper;
		$new_columns = array();
		foreach($columns as $key => $column)
		{
			$new_columns[$key] = $column;
			if($key == 'order-total')
			{
				if($wcsts_option_helper->get_all_options('display_ticket_number_column_on_my_accont_order_table') && $wcsts_option_helper->get_all_options('is_order_ticket_enabled'))
				{
					$new_columns['wcsts-ticket-number'] = esc_html__('Tickets', 'woocommerce-support-ticket-system');
					$new_columns['wcsts-new-admin-messages-number'] = esc_html__('New messages', 'woocommerce-support-ticket-system');
				}
			}
		}
		return $new_columns;
	}
	function add_ticket_number_column_content($order)
	{
		global $wcsts_ticket_model;
		echo $wcsts_ticket_model->get_order_ticket_number(WCSTS_Order::get_id($order));
	}
	function add_new_admin_messages_number_column_content($order)
	{
		global $wcsts_ticket_model, $wcsts_order_model;
		$ticket_type = $wcsts_order_model->has_ppt_ticket_associated($order->get_id()) ? 'ppt' : 'order';
		$new_messages_counter = $wcsts_ticket_model->count_total_new_admin_messages_per_type(WCSTS_Order::get_id($order), $ticket_type);
		echo $new_messages_counter;
	}
	function add_order_table_extra_buttons()
	{
		global $wp, $wcsts_option_helper, $wcsts_text_helper;
		$can_render = false;
		if ( did_action( 'woocommerce_account_content' ) ) 
		{
			foreach ( $wp->query_vars as $key => $value ) 
			{
				if($key == get_option('woocommerce_myaccount_orders_endpoint'))
					$can_render = true;
			}
		}
		else
			$can_render = true;
		
		$can_render = $wcsts_option_helper->get_all_options('is_order_ticket_enabled', true) ? $can_render : false;
		$can_render = !$wcsts_option_helper->get_all_options('disable_get_help_button', false) ? $can_render : false;
		
		if(!$can_render)
			return false;
		
		if(!get_current_user_id()) //???
			return;
		
		$texts = $wcsts_text_helper->get_texts();
		wp_enqueue_style('wcsts-my-account', WCSTS_PLUGIN_PATH.'/css/frontend-my-account.css');
		wp_register_script('wcsts-my-account-orders-table', WCSTS_PLUGIN_PATH.'/js/frontend-my-account-orders-table.js', array('jquery'));
		//include WCSTS_PLUGIN_ABS_PATH.'/templates/my_account_orders_table.php';
		
		$translation_array = array(
			'get_help_text' => $texts['get_help_button_text'],
			'view_order_url' => wc_get_endpoint_url( 'view-order', "wcsts_order_id_place_holder", wc_get_page_permalink( 'myaccount' ) ),
			'wc_ver' => WC_VERSION
		);
		wp_localize_script( 'wcsts-my-account-orders-table', 'wcsts', $translation_array );
		wp_enqueue_script( 'wcsts-my-account-orders-table' );
	}
		
	public function add_custom_endpoints() 
	{
		global $wcsts_option_helper;
		$user_ticket_area_endpoint =  $wcsts_option_helper->get_user_ticket_area_endpoint();
		add_rewrite_endpoint( $user_ticket_area_endpoint, EP_ROOT | EP_PAGES );
		//flush_rewrite_rules();
		
		//handler 
		add_action( 'woocommerce_account_'.$user_ticket_area_endpoint."_endpoint", array(&$this,'render_user_ticket_area') );
	}
	public function add_end_point_to_query_vars( $vars ) 
	{
		global $wcsts_option_helper;
		$user_ticket_area_endpoint =  $wcsts_option_helper->get_user_ticket_area_endpoint();
		$vars[] = $user_ticket_area_endpoint;

		return $vars;
	}
	function render_user_ticket_area_title( $title ) 
	{
		global $wp_query,$wcsts_text_helper, $wcsts_option_helper;
		
		$user_ticket_area_endpoint =  $wcsts_option_helper->get_user_ticket_area_endpoint();
		$is_endpoint = isset( $wp_query->query_vars[$user_ticket_area_endpoint ] );
		
		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) 
		{
			// New page title.
			$texts = $wcsts_text_helper->get_texts();
			$title = $texts['my_account_page_user_ticket_area_tab_label'];
			remove_filter( 'the_title',  array(&$this,'render_user_ticket_area_title') );
		}

		return $title;
	}
	public function add_user_ticket_area_to_menu($items)
	{
		global $wcsts_option_helper, $wcsts_text_helper, $wcsts_ticket_model;
			if($wcsts_option_helper->get_all_options('display_user_ticket_area_in_my_account_page') != 'yes')
				return $items;
		
		wp_enqueue_style('wcsts-my-account', WCSTS_PLUGIN_PATH.'/css/frontend-my-account.css');		
		//$new_items = array();
		$user_ticket_area_endpoint =  $wcsts_option_helper->get_user_ticket_area_endpoint();
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );
		$texts = $wcsts_text_helper->get_texts();
		$items[$user_ticket_area_endpoint] = $texts['my_account_page_user_ticket_area_tab_label'];
		$new_messages_counter = $wcsts_ticket_model->count_total_new_admin_messages_per_type();
		
		if($new_messages_counter > 0 && $texts['personal_ticket_area_tab_new_messages_counter_label'] != "")
			$items[$user_ticket_area_endpoint] .=  " ".sprintf($texts['personal_ticket_area_tab_new_messages_counter_label'], $new_messages_counter);
		$items['customer-logout'] = $logout;
		return $items;
	}
	public function render_user_ticket_area()
	{
		global $wcsts_html_helper, $wcsts_option_helper, $wcsts_text_helper, $wcsts_ticket_model;
		$texts = $wcsts_text_helper->get_texts();
		
		//wcsts_var_dump($wcsts_ticket_model->get_available_statuses());
			
		if( $wcsts_option_helper->get_all_options('display_user_ticket_area_in_my_account_page')== 'yes')
		{
			if($texts['my_account_tab_page_description'] != "")
				echo '<div class="wcsts_ticket_area_description">'.$texts['my_account_tab_page_description'].'</div>';
			$wcsts_html_helper->frontend_ticket_area(null,false,false);
		}
	}
}
?>