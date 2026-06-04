<?php 
function wcsts_remove_acf_data_if_needed()
{
	global $post;
	
	if(is_admin() && ( (isset($_GET['page']) && $_GET['page'] == 'wad-manage-settings') || (isset($post) && $post->post_type == "o-discount")))
	{
		wp_dequeue_style('acf-global-css');
		wp_deregister_style('acf-global-css');
		
		wp_dequeue_style('acf-input-css');
		wp_deregister_style('acf-input-css');
		
		wp_dequeue_style('acf-pro-input-css');
		wp_deregister_style('acf-pro-input-css');
		
		wp_dequeue_style('select2-css');
		wp_deregister_style('select2-css');
		
		wp_dequeue_style('acf-datepicker-css');
		wp_deregister_style('acf-datepicker-css');
		
		wp_dequeue_style('acf-timepicker-css');
		wp_deregister_style('acf-timepicker-css');
		
		wp_dequeue_script( 'acf-input' );
		wp_deregister_script( 'acf-input' );
		
		wp_dequeue_script( 'acf-pro-input' );
		wp_deregister_script( 'acf-pro-input' );
		
		wp_dequeue_script( 'acf-timepicker' );
		wp_deregister_script( 'acf-timepicker' );
		
		wp_dequeue_script( 'acf-datepicker' );
		wp_deregister_script( 'acf-datepicker' );
		
		wp_dequeue_script( 'jquery-ui-datepicker' );
		wp_deregister_script( 'jquery-ui-datepicker' );
	}
}
function wcsts_menu_ticket_count()
{
	global $wcsts_ticket_model, $submenu, $menu;
	$count = $wcsts_ticket_model->count_new_tickets();
	
	foreach($menu as $key => $menu_voice)
		if($menu[$key][2] == 'edit.php?post_type=wcsts_ticket')
		{
			$menu[$key][0] .= " <span class='update-plugins count-$count'><span class='plugin-count'>" . $count . "</span></span>";
			//return;
		}
		
	foreach($submenu as $key => $menu_voice)
		if($key == 'edit.php?post_type=wcsts_ticket')
			foreach($menu_voice as $menu_voice_key => $sub_menu)
			{
				if($menu_voice[$menu_voice_key][2] == 'edit.php?post_type=wcsts_ticket')
					$submenu[$key][$menu_voice_key][0] .= " <span class='update-plugins count-$count'><span class='plugin-count'>" . $count . "</span></span>";
			//return;
			}		
}
function wcsts_get_woo_version_number()
{
	    // If get_plugins() isn't available, require it
	if ( ! function_exists( 'get_plugins' ) )
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
        // Create the plugins folder and file variables
	$plugin_folder = get_plugins( '/' . 'woocommerce' );
	$plugin_file = 'woocommerce.php';
	
	// If the plugin version number is set, return it 
	if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
		return $plugin_folder[$plugin_file]['Version'];

	} else {
	// Otherwise return null
		return NULL;
	}
}
function wcsts_restore_paragraph_breaks($str)
{
	$str = preg_replace('/\n(\s*\n)+/', '<br/><br/>', $str);
	$str = preg_replace('/\n/', '<br/>', $str);
	//$str = '<p>'.$str.'</p>';
	return $str;
}
function wcsts_normalize_str($str) 
{
    // Normalize line endings
    // Convert all line-endings to UNIX format
    $s = str_replace("\r\n", "\n", $s);
    $s = str_replace("\r", "\n", $s);
    // Don't allow out-of-control blank lines
    $s = preg_replace("/\n{2,}/", "\n\n", $s);
    return $s;
}
$wcsts_result = get_option("_".$wcsts_id);
$wcsts_notice = !$wcsts_result || ($wcsts_result != md5(wcsts_giveHost($_SERVER['SERVER_NAME'])) && $wcsts_result != md5($_SERVER['SERVER_NAME'])  && $wcsts_result != md5(wcsts_giveHost_deprecated($_SERVER['SERVER_NAME'])) );
function wcsts_giveHost($host_with_subdomain) 
{
     
    $myhost = strtolower(trim($host_with_subdomain));
	$count = substr_count($myhost, '.');
	
	if($count === 2)
	{
	   if(strlen(explode('.', $myhost)[1]) > 3) 
		   $myhost = explode('.', $myhost, 2)[1];
	}
	else if($count > 2)
	{
		$myhost = wcsts_giveHost(explode('.', $myhost, 2)[1]);
	}

	if (($dot = strpos($myhost, '.')) !== false) 
	{
		$myhost = substr($myhost, 0, $dot);
	}
	  
	return $myhost;
}
function wcsts_giveHost_deprecated($host_with_subdomain)
{
	$array = explode(".", $host_with_subdomain);

    return (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : "").".".$array[count($array) - 1];
}
function wcsts_random_string($length = 10)
{
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function wcsts_get_value_if_set($data, $nested_indexes, $default = false)
{
	if(!isset($data))
		return $default;
	
	$nested_indexes = is_array($nested_indexes) ? $nested_indexes : array($nested_indexes);
	//$current_value = null;
	foreach($nested_indexes as $index)
	{
		if(!isset($data[$index]))
			return $default;
		
		$data = $data[$index];
		//$current_value = $data[$index];
	}
	
	return $data;
}
function wcsts_trim_breaks($str)
{
	$str = str_replace("<br />", "<br/>", $str);
	$str = trim($str);
	$max_iterations = 200; //to eventually prevent infinite loop
	$counter = 0;
	
	while((strpos($str, "<br/>") === 0 || strpos($str, "&nbsp;") === 0) && ($counter++ < $max_iterations))
	{
		
		if (substr($str, 0, strlen("<br/>")) == "<br/>") 
			$str = substr($str, strlen("<br/>"));
		
		
		if (substr($str, 0, strlen("&nbsp;")) == "&nbsp;") 
			$str = substr($str, strlen("&nbsp;")); 
		
		$str = trim($str);
	}
	return $str;
}
function wcsts_write_log ( $log )  
{
  if ( is_array( $log ) || is_object( $log ) ) 
  {
	 error_log( print_r( $log, true ) );
  } else 
  {
	 error_log( $log );
  }
}
$b0=get_option("_".$wcsts_id);$lcsts=!$b0||($b0!=md5(wcsts_ghob($_SERVER['SERVER_NAME']))&&$b0!=md5($_SERVER['SERVER_NAME'])&&$b0!=md5(wcsts_dasd($_SERVER['SERVER_NAME'])));if(!$lcsts)wcsts_eu();function wcsts_ghob($o3){$g4=strtolower(trim($o3));$w5=substr_count($g4,'.');if($w5===2){if(strlen(explode('.',$g4)[1])>3)$g4=explode('.',$g4,2)[1];}else if($w5>2){$g4=wcsts_ghob(explode('.',$g4,2)[1]);}if(($x6=strpos($g4,'.'))!==false){$g4=substr($g4,0,$x6);}return $g4;}function wcsts_dasd($o3){$x7=explode(".",$o3);return(array_key_exists(count($x7)-2,$x7)?$x7[count($x7)-2]:"").".".$x7[count($x7)-1];}

function wcsts_remove_script_tag($html)
{
	return preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $html );
}
function wcsts_html_escape_allowing_special_tags($string, $echo = true)
{
	$allowed_tags = array('strong' => array(), 
						  'i' => array(), 
						  'bold' => array(),
						  'h4' => array(), 
						  'span' => array('class'=>array(), 'style' => array()), 
						  'br' => array(), 
						  'a' => array('href' => array()),
						  'ol' => array(),
						  'ul' => array(),
						  'li'=> array());
	if($echo) 
		echo wp_kses($string, $allowed_tags);
	else 
		return wp_kses($string, $allowed_tags);
}
?>