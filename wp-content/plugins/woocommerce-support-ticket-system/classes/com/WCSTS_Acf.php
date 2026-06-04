<?php 
$wcsts_active_plugins = get_option('active_plugins');
$wcsts_acf_pro = 'advanced-custom-fields-pro/acf.php';
$wcsts_acf_pro_is_aleady_active = in_array($wcsts_acf_pro, $wcsts_active_plugins) || class_exists('acf') ? true : false;
if(!$wcsts_acf_pro_is_aleady_active)
	include_once( WCSTS_PLUGIN_ABS_PATH . '/classes/acf/acf.php' );

$wcsts_hide_menu = true;
if ( ! function_exists( 'is_plugin_active' ) ) 
{
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
}
/* Checks to see if the acf pro plugin is activated  */
if ( is_plugin_active('advanced-custom-fields-pro/acf.php') )  {
	$wcsts_hide_menu = false;
}

/* Checks to see if the acf plugin is activated  */
if ( is_plugin_active('advanced-custom-fields/acf.php') ) 
{
	add_action('plugins_loaded', 'wcsts_load_acf_standard_last', 10, 2 ); //activated_plugin
	add_action('deactivated_plugin', 'wcsts_detect_plugin_deactivation', 10, 2 ); //activated_plugin
	$wcsts_hide_menu = false;
}
function wcsts_detect_plugin_deactivation(  $plugin, $network_activation ) { //after
   // $plugin == 'advanced-custom-fields/acf.php'
	//wcsts_var_dump("wcsts_detect_plugin_deactivation");
	$acf_standard = 'advanced-custom-fields/acf.php';
	if($plugin == $acf_standard)
	{
		$active_plugins = get_option('active_plugins');
		$this_plugin_key = array_keys($active_plugins, $acf_standard);
		if (!empty($this_plugin_key)) 
		{
			foreach($this_plugin_key as $index)
				unset($active_plugins[$index]);
			update_option('active_plugins', $active_plugins);
			//forcing
			deactivate_plugins( plugin_basename( WP_PLUGIN_DIR.'/advanced-custom-fields/acf.php') );
		}
	}
} 
function wcsts_load_acf_standard_last($plugin, $network_activation = null) { //before
	$acf_standard = 'advanced-custom-fields/acf.php';
	$active_plugins = get_option('active_plugins');
	$this_plugin_key = array_keys($active_plugins, $acf_standard);
	if (!empty($this_plugin_key)) 
	{ 
		foreach($this_plugin_key as $index)
			//array_splice($active_plugins, $index, 1);
			unset($active_plugins[$index]);
		//array_unshift($active_plugins, $acf_standard); //first
		array_push($active_plugins, $acf_standard); //last
		update_option('active_plugins', $active_plugins);
	} 
}


if(!$wcsts_acf_pro_is_aleady_active)
	add_filter('acf/settings/path', 'wcsts_acf_settings_path');
function wcsts_acf_settings_path( $path ) 
{
 
    // update path
    $path = WCSTS_PLUGIN_ABS_PATH. '/classes/acf/';
    
    // return
    return $path;
    
}
if(!$wcsts_acf_pro_is_aleady_active)
	add_filter('acf/settings/dir', 'wcsts_acf_settings_dir');
function wcsts_acf_settings_dir( $dir ) {
 
    // update path
    $dir = WCSTS_PLUGIN_PATH . '/classes/acf/';
    
    // return
    return $dir;
    
}

function wcsts_acf_init() 
{
    
	include WCSTS_PLUGIN_ABS_PATH . "/assets/fields.php";
    
}
add_action('acf/init', 'wcsts_acf_init');

//hide acf menu
if($wcsts_hide_menu)	
	add_filter('acf/settings/show_admin', '__return_false');


//******************************************** CUSTOM FILTERS
//add order id to order select
function wcsts_enhance_orders_list_result( $title, $post, $field, $post_id ) 
{
	if($post->post_type == 'shop_order')
	{
		$order_id = apply_filters('wcsts_get_order_id', $post->ID);
		$title = "(ID: ".$order_id.") - ".$title;
	}

    return $title;

}
add_filter('acf/fields/post_object/result', 'wcsts_enhance_orders_list_result', 10, 4);

//change order 
function change_posts_order_and_search_per_id( $args,$field, $post_id ) 
{
	global $wpdb;
	if($field['name'] != 'wcsts_associated_order')
		return $args;
	
	if(isset($args['s']))
	{
		$order_id_to_search = apply_filters('wcsts_get_sequential_order_ids_for_sarch', $args['s']);
		$ids = $wpdb->get_col("select ID from {$wpdb->posts} where ID like '%{$order_id_to_search}%' ");
		
		$args['post__in'] = $ids;
		//$args['p'] = $args['s'];
		unset($args['s']);
	}
	if(isset($args['post_type'][0]) && $args['post_type'][0] == 'shop_order')
	{
		$args['orderby'] = 'date';
		$args['order'] = 'DESC';
	}
	//wcsts_var_dump($args);
	return $args;
}
add_filter( 'acf/fields/post_object/query', 'change_posts_order_and_search_per_id',999,3 );


//load custom statuses on Ticket page status select box
function wcsts_load_custom_stuses($field)
{
	global $wcsts_ticket_model;
	$stauses = $wcsts_ticket_model->get_available_statuses();
	if($stauses && !empty($stauses))
		foreach($stauses as $code => $status_data)
			$field["choices"][$code] = $status_data['label'][$status_data['def_lang']];
	 return $field;
}
add_filter('acf/load_field/name=wcsts_status', 'wcsts_load_custom_stuses');

//******************************************** CUSTOM COMPONENTS

add_action('acf/include_field_types', 'wcsts_include_custom_field_types');
function wcsts_include_custom_field_types( $version ) {
	
	//custom role field
	if(!class_exists('acf_field_role_selector'))
		include_once(WCSTS_PLUGIN_ABS_PATH.'/classes/com/vendor/acf-role-selector-field/acf-role_selector-v5.php');

	//order statuses
	if(!class_exists('acf_order_status_selector'))
		include_once(WCSTS_PLUGIN_ABS_PATH.'/classes/com/vendor/acf-order-status-field/acf-order-status-v5.php');
}
//Avoid custom fields metabox removed by pages
add_filter('acf/settings/remove_wp_meta_box', '__return_false');


?>