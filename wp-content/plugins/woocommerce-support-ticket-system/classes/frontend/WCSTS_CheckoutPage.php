<?php 
class WCSTS_CheckoutPage
{
	public function __construct()
	{
		add_action('woocommerce_checkout_order_processed', array( &$this, 'after_order_creation_on_checkout' )); //After checkout
	}
	public function after_order_creation_on_checkout($order_id)
	{
		global $wcsts_order_model;
		
		$wcsts_order_model->proces_order_items_saving_ppt_meta_on_order($order_id);
	}
}
?>