<?php 
class WCSTS_Shortcode
{
	public function __construct()
	{
		add_shortcode( 'wcsts_ticket_area', array(&$this, 'wcsts_ticket_area' ));
		add_shortcode( 'wcsts_pay_per_ticket_area', array(&$this, 'wcsts_pay_per_ticket_area' ));
	}
	function wcsts_ticket_area($atts = [], $content = null, $tag = '')
	{
		global $wcsts_html_helper, $wp, $wcsts_option_helper;
		$can_render = true;
		$atts = shortcode_atts(
				array(
					'open_new_ticket' => true,
				), $atts);
		
		if(isset( $wp->query_vars) && is_array( $wp->query_vars))
			foreach ( $wp->query_vars as $key => $value ) 
					if($key == 'view-order') //To avoid shortcode is used on order details page
							$can_render = false;
				
		$extra_parameters = array('not_logged_message'=>$content, 'shortcode_atts' => $atts);
		if($can_render)
		{
			ob_start();
			$wcsts_html_helper->frontend_ticket_area(null,false,false, $extra_parameters);
			return ob_get_clean();
		}
	}
	
	function wcsts_pay_per_ticket_area($atts = [], $content = null, $tag = '')
	{
		global $wcsts_html_helper, $wp, $wcsts_option_helper;
		$can_render = true;
		$atts = shortcode_atts(
				array(
					'open_new_ticket' => true,
				), $atts);
				
		if(isset( $wp->query_vars) && is_array( $wp->query_vars))
			foreach ( $wp->query_vars as $key => $value ) 
					if($key == 'view-order') //To avoid shortcode is used on order details page
							$can_render = false;
				
		$extra_parameters = array('not_logged_message'=>$content, 'list_all_ppt_tickets_of_the_current_user' => true, 'shortcode_atts' => $atts);
		if($can_render)
		{
			ob_start();
			$wcsts_html_helper->frontend_ticket_area(null,false,false, $extra_parameters);
			return ob_get_clean();
		}
	}
}
?>