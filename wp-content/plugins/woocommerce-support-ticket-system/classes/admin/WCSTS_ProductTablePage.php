<?php 
class WCSTS_ProductTablePage
{
	public function __construct()
	{
		add_filter( 'manage_edit-product_columns', array($this, 'add_ppt_column_head'),15 ); 
		add_action( 'manage_product_posts_custom_column', array($this, 'manage_ppt_column_content'), 10, 2 );
	}
	public function add_ppt_column_head($columns)
	 {
		
	    //add column
	   $columns['wcsts-is-ppt-product'] = esc_html__('Pay per ticket', 'woocommerce-support-ticket-system'); 
	
	   return $columns;
	}
	public function manage_ppt_column_content( $column, $product_id ) 
	{
		global $wcsts_product_model, $wcsts_wpml_helper;
		
		if( $column == 'wcsts-is-ppt-product')
		{
			$product = wc_get_product($product_id);
			if($product->get_type( ) == 'simple' || $product->get_type( ) == 'subscription' ) //variable || simple
			{				
				$product_id = $wcsts_wpml_helper->get_main_language_id($product->get_id());
				$questions_number = $wcsts_product_model->get_product_questions_number($product_id);
				
				echo $questions_number > 0 ? esc_html__('Yes', 'woocommerce-support-ticket-system') : esc_html__('No', 'woocommerce-support-ticket-system');
			}
			else
			{
				$variations = $product->get_children(); 
				$is_ppt = false;
				foreach($variations as $variation_id)
				{
					$product_id = $wcsts_wpml_helper->get_main_language_id($variation_id);
					$questions_number = $wcsts_product_model->get_product_questions_number($product_id);
					$is_ppt = $questions_number > 0 ? true : $is_ppt;
				}
				
				echo $is_ppt  ? esc_html__('Yes', 'woocommerce-support-ticket-system') : esc_html__('No', 'woocommerce-support-ticket-system');
			}
		}
	}
}
?>