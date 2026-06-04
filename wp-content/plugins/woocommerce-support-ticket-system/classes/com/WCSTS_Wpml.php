<?php
class WCSTS_Wpml
{
	var $current_lang;
	var $before_admin_lang;
	public function __construct()
	{
		add_action('plugins_loaded', array(&$this,'init_ajax_language'));
	}
	public function init_ajax_language()
	{
		if(!isset($_POST['wcsts_wpml_language']) || !$this->wpml_is_active())
			return;
		
		global $sitepress;
		load_plugin_textdomain('woocommerce-support-ticket-system', false, WCSTS_PLUGIN_LANG_PATH );
		$sitepress->switch_lang($_POST['wcsts_wpml_language'], true);
	}
	public function wpml_is_active()
	{
		return class_exists('SitePress');
	}
	public function switch_to_admin_default_lang()
	{
		if(!$this->wpml_is_active())
			return; 
		
		global $sitepress_settings,$sitepress,$locale;			
		$this->before_admin_lang = ICL_LANGUAGE_CODE;
		$sitepress->switch_lang($sitepress_settings['admin_default_language']);		
		$locale = $sitepress_settings['admin_default_language']."_".strtoupper($sitepress_settings['admin_default_language']); 
		load_plugin_textdomain('woocommerce-support-ticket-system', false, WCSTS_PLUGIN_LANG_PATH);
	}
	public function restore_from_admin_default_lang()
	{
		if(!$this->wpml_is_active())
			return;
		
		global $sitepress,$locale;
		$sitepress->switch_lang($this->before_admin_lang);
		$locale = $this->before_admin_lang."_".strtoupper($this->before_admin_lang);
		load_plugin_textdomain('woocommerce-support-ticket-system', false, WCSTS_PLUGIN_LANG_PATH);
	}
	public function remove_translated_id($items_array, $post_type = "product", $default_language = false)
	{
		if(!class_exists('SitePress'))
			return false;
		global $sitepress;
		$current_language = ICL_LANGUAGE_CODE;
		if($default_language)
			$current_language = $sitepress->get_default_language();
		$filtered_items_list = array();
		foreach($items_array as $item)	
		{
			/* $result = wpml_get_language_information($item->id);
			if(!is_bool (strpos($result['locale'], ICL_LANGUAGE_CODE)))
			{
				array_push($filtered_items_list, $item);
			}*/
			
			//If in the selected language the $id is the same of the language, is not a transaltion so can be kept
			$item_id = is_object($item) && method_exists($item,'get_id') ? $item->get_id() : $item->id;
			//$item_type = is_object($item) && method_exists($item,'get_type') ? $item->get_type() : $item->type;

			if(function_exists('icl_object_id'))
				$item_translated_id = icl_object_id($item_id, $post_type, false,$current_language);
			else
				$item_translated_id = apply_filters( 'wpml_object_id', $item_id, $post_type, false, $current_language );
			
			if($item_id == $item_translated_id)
				array_push($filtered_items_list, $item);
		}
			
		return $filtered_items_list ;
	}
	
	public function get_main_language_ids($items_array, $post_type = "product")
	{
		if(!class_exists('SitePress'))
			return $items_array;
		
		global $sitepress;
		$filtered_items_list = array();
		foreach($items_array as $item)	
		{
			$item_id = is_object($item) && method_exists($item,'get_id') ? $item->get_id() : $item->id;
			//$item_type = is_object($item) && method_exists($item,'get_type') ? $item->get_type() : $item->type;

			if(function_exists('icl_object_id'))
				$item_translated_id = icl_object_id($item_id, $post_type, false, $sitepress->get_default_language());
			else
				$item_translated_id = apply_filters( 'wpml_object_id', $item_id, $post_type, false, $sitepress->get_default_language() );
			
			if(!$item_translated_id) //means is already main language id
				array_push($filtered_items_list, $item);
		}
			
		return $filtered_items_list ;
	}
	public function get_main_language_id($id_to_get_original, $post_type = "product")
	{
		if(!class_exists('SitePress') || $id_to_get_original == 0)
			return $id_to_get_original;
		
		global $sitepress;
		
		if(function_exists('icl_object_id'))
				$id_to_get_original = icl_object_id($id_to_get_original, $post_type, true, $sitepress->get_default_language());
			else
				$id_to_get_original = apply_filters( 'wpml_object_id', $id_to_get_original, $post_type, true, $sitepress->get_default_language() );
			
		return $id_to_get_original;
	}
	
	public function switch_to_default_language()
	{
		if(!$this->wpml_is_active())
			return;
		global $sitepress;
		$this->curr_lang = ICL_LANGUAGE_CODE ;
		$sitepress->switch_lang($sitepress->get_default_language());
	
	}
	public function switch_to_current_language()
	{
		if(!$this->wpml_is_active())
			return;
		
		global $sitepress;
		$sitepress->switch_lang($this->curr_lang);
	}
	public function get_default_locale()
	{
		global $sitepress;
													//en_US
		return !$this->wpml_is_active() ? get_locale() /* substr(get_locale(), 0,2) */ /* get_bloginfo("language")  */: $sitepress->get_locale($sitepress->get_default_language());
	}
	public function get_current_locale()
	{
		global $sitepress;
													//en_US
		return !$this->wpml_is_active() ? get_locale() /* substr(get_locale(), 0,2) */ /* get_bloginfo("language")  */: $sitepress->get_locale(ICL_LANGUAGE_CODE);
	}
	public function get_langauges_list()
	{
		/* 
		Array
		(
		 [0] => Array
		  (
		   ["code"]=>
			string(2) "en"
			["id"]=>
			string(1) "1"
			["native_name"]=>
			string(7) "English"
			["major"]=>
			string(1) "1"
			["active"]=>
			string(1) "1"
			["default_locale"]=>
			string(5) "en_US"
			["encode_url"]=>
			string(1) "0"
			["tag"]=>
			string(2) "en"
			["missing"]=>
			int(0)
			["translated_name"]=>
			string(7) "English"
			["url"]=>
			string(44) "https://site.com/demo/my-account/"
			["country_flag_url"]=>
			string(95) "https://site.com/demo/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png"
			["language_code"]=>
			string(2) "en"
		  )
		 */
		 $langs = apply_filters( 'wpml_active_languages', NULL, 'skip_missing=0orderby=id&order=desc' );
		return !$this->wpml_is_active() || empty($langs) ? false : $langs;
	}
}
?>