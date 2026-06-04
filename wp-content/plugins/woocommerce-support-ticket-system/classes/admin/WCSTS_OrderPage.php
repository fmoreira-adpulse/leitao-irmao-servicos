<?php 
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class WCSTS_OrderPage
{
	public function __construct()
	{
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ) ); 
	}
	function add_meta_boxes() 
	{
		$screen = 'shop_order';
		
		try
		{
			$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';
		}
		catch (Exception $e) {$screen = 'shop_order';}
		add_meta_box( 'wcsts-related-order-tickets', esc_html__('Support tickets', 'woocommerce-support-ticket-system'), array( &$this, 'render_related_order_meta_box' ), $screen , 'side','high');
	}
	function render_related_order_meta_box($post_or_order_object)
	{
		global $wcsts_time_model, $wcsts_order_model;
		
		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		$order_id = ( $post_or_order_object instanceof WP_Post ) ? $post_or_order_object->ID : $post_or_order_object->get_id();
		
		$orders_ticket = $wcsts_order_model->get_ticket_ids_by_order_id($order_id);
		
		if(is_array($orders_ticket) && !empty($orders_ticket)):
		?>
			<ol class="wcsts-order-ticket-list">
			<?php foreach($orders_ticket as $order_ticket): ?>
			<li class="wcsts-order-ticket-list-element"><a href="<?php echo get_edit_post_link($order_ticket->ticket_id); ?>" target="_blank">#<?php echo $order_ticket->ticket_id; ?></a> - <?= date('d/m/Y H:i:s', strtotime($order_ticket->date)) ?> </li>
			<?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<a class="button-primary" target="_blank" href="<?php esc_attr_e(admin_url('post-new.php?post_type=wcsts_ticket&order_id='.$order_id)); ?>"> <?php esc_html_e('Add new ticket', 'woocommerce-support-ticket-system') ?> </a>
		<?php
		
	}
}
?>