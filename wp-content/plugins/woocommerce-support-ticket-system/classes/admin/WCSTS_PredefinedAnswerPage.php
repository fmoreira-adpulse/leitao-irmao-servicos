<?php 
class WCSTS_PredefinedAnswerPage
{
	function __construct()
	{
		add_action( 'add_meta_boxes_wcsts_predef_answer', array( &$this, 'add_custom_meta_boxes' ) );
	}
	public function add_custom_meta_boxes()
	{
		add_meta_box( 'wcst-shortcode', esc_html__('Info - Shortcode', 'woocommerce-support-ticket-system'), array( &$this, 'render_shortcode_info_box' ), 'wcsts_predef_answer', 'side');
	}
	public function render_shortcode_info_box()
	{
		global $wcsts_html_helper;
		
		$wcsts_html_helper->render_shortcode_info();
	}
}
?>