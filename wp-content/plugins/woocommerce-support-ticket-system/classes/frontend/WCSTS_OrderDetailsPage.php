<?php 
class WCSTS_OrderDetailsPage
{
	public function __construct()
	{
		//Priority 100: in this way the ticket box is injected after 'woocommerce_order_again_button' action and after WCAM attachments
		add_action( 'init', array( &$this, 'init' ), 100 ); 
		
		add_action('woocommerce_my_account_my_orders_column_order-number', array( &$this, 'alter_order_number_column' ), 999 );
	}
	public function init()
	{
		global $wcsts_option_helper;
		
		$position = $wcsts_option_helper->get_all_options('order_ticket_area_position');
		if($position === 'woocommerce_order_details_after_billing_and_shipping_details')
			add_action( 'woocommerce_order_details_after_order_table', array( &$this, 'render_ticket_area' ), 100 );
		else
			add_action( $position, array( &$this, 'render_ticket_area' ), 100 ); // 'woocommerce_order_details_after_order_table' || 'woocommerce_order_details_after_customer_details' || 'woocommerce_order_details_after_billing_and_shipping_details'
	}
	public function render_ticket_area($order)
	{
		global $wcsts_html_helper, $wp, $wcsts_option_helper;
		$can_render = true;
		foreach ( $wp->query_vars as $key => $value ) 
				if($order->get_customer_id() && $key == 'order-received') //For registered user ONLY: this avoids the ticket box is also received on the landing page after an order has been placed
						$can_render = false;
						
		$can_render = $wcsts_option_helper->get_all_options('is_order_ticket_enabled', true) ? $can_render : false;
		$position = $wcsts_option_helper->get_all_options('order_ticket_area_position');
		$order_ticket_system_disabled_order_statuses = $wcsts_option_helper->get_all_options('order_ticket_system_disabled_order_statuses');
		$disable_smooth_scroll = $wcsts_option_helper->get_all_options('order_details_page_smooth_scroll');
		
		$status = "wc-".$order->get_status();
		$can_render =  !in_array($status, $order_ticket_system_disabled_order_statuses) ? $can_render : false;
		if($can_render)
		{
			$render_after_billign_and_shipping_details = $position == 'woocommerce_order_details_after_billing_and_shipping_details' ? 'true' : 'false';
			wp_register_script('wcsts-my-account-orders-table', WCSTS_PLUGIN_PATH.'/js/frontend-my-account-view-order.js', array('jquery'));
			wp_localize_script('wcsts-my-account-orders-table', 'wcsts_order_details_options', array('render_after_billign_and_shipping_details' => $render_after_billign_and_shipping_details,
																										'disable_smooth_scroll' =>$disable_smooth_scroll ? 'true' : 'false'));
			wp_enqueue_script('wcsts-my-account-orders-table');
			$wcsts_html_helper->frontend_ticket_area($order, false, true, array("allow_guest" => !$order->get_customer_id()));
		}
	}
	
	public function alter_order_number_column($order)
	{
		if(is_plugin_active( 'woocommerce-shipping-tracking/shipping-tracking.php' ))
			return
		
		?>
		<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" data-wcst-id="<?php echo WCSTS_Order::get_id($order); ?>">
			<?php echo _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number(); ?>
		</a>
		<?php 
	}
	
}
?>