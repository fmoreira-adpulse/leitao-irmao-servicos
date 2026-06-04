<?php 
class WCSTS_TextCustomizerPage
{
	public function __construct()
	{
		//$this->init_options_menu();
		add_action( 'init', array(&$this, 'init_options_menu') );
	}
	function init_options_menu()
	{
		if( function_exists('acf_add_options_page') && (current_user_can('edit_users') || current_user_can('manage_woocommerce'))) 
		{
			/*acf_add_options_page(array(
				'page_title' 	=> 'Menu name',
				'menu_title'	=> 'Menu name',
				'menu_slug' 	=> 'wcuf-option-menu',
				'capability'	=> 'edit_posts',
				'icon_url'      => 'dashicons-upload',
				'redirect'		=> false
			));*/
			
			 acf_add_options_sub_page(array(
				'page_title' 	=> 'Texts Customizer',
				'menu_title'	=> 'Ticket System Texts',
				'parent_slug'	=> 'edit.php?post_type=wcsts_ticket',
			));
			add_action( 'current_screen', array(&$this, 'cl_set_global_options_pages') );
		}
	}
	/**
	 * Force ACF to use only the default language on some options pages
	 */
	function cl_set_global_options_pages($current_screen) 
	{
	  if(!is_admin())
		  return;
	  
	// wcsts_var_dump($current_screen->id);
	  $page_ids = array(
		"wcsts_ticket_page_acf-options-ticket-system-texts"
	  );
	 
	  if (in_array($current_screen->id, $page_ids)) 
	  {
		global $wcsts_wpml_helper;
		//$wcsts_wpml_helper->switch_to_default_language();
		//add_filter('acf/settings/current_language', array(&$this, 'cl_acf_set_language'), 100);
	  }
	}
	

	function cl_acf_set_language() 
	{
	  return acf_get_setting('default_language');
	}
}
?>