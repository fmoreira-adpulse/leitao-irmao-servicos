<?php 
class WCSTS_TicketMessage
{
	public function __construct()
	{
		add_action( 'init', array(&$this, 'register_custom_post_type'), 0 );
		add_action('before_delete_post', array( &$this,'delete_all_attachments'), 10);
	}
	function register_custom_post_type() 
	{

		$labels = array(
			'name'                => _x( 'Support Ticket Message', 'Ticket', 'woocommerce-support-ticket-system' ),
			'singular_name'       => _x( 'Support Ticket Message', 'Ticket', 'woocommerce-support-ticket-system' ),
			'parent_item_colon'   => __( 'Parent Item:', 'woocommerce-support-ticket-system' ),
			'all_items'           => __( 'All Messages', 'woocommerce-support-ticket-system' ),
			'add_new_item'        => __( 'Add Message', 'woocommerce-support-ticket-system' ),
			'add_new'             => __( 'Add Message', 'woocommerce-support-ticket-system' ),
			'new_item'            => __( 'New Message', 'woocommerce-support-ticket-system' ),
			'edit_item'           => __( 'Edit Message', 'woocommerce-support-ticket-system' ),
			'update_item'         => __( 'Update Message', 'woocommerce-support-ticket-system' ),
			'view_item'           => __( 'View Message', 'woocommerce-support-ticket-system' ),
			'search_items'        => __( 'Search Message', 'woocommerce-support-ticket-system' ),
			'not_found'           => __( 'Not found', 'woocommerce-support-ticket-system' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'woocommerce-support-ticket-system' ),
		);
		$args = array(
			'label'               => __( 'WooCommerce Support Message', 'woocommerce-support-ticket-system' ),
			'description'         => __( 'WooCommerce Support Ticket System', 'woocommerce-support-ticket-system' ),
			'labels'              => $labels,
			'supports'            => array('editor' /* , 'author' */),
			'taxonomies'          => array( /*'category' , 'post_tag' */ ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,                                     
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,		
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'shop_order'/* 'post' */
		);
		register_post_type( 'wcsts_ticket_message', $args );
		flush_rewrite_rules();
		
	}
	public function assing_customer_id_to_messages_by_order($ticket_id)
	{
		global $wcsts_ticket_model;
		$order_id = $wcsts_ticket_model->get_attributes($ticket_id, 'associated_order');
		$order = wc_get_order($order_id);
		
		if(WCSTS_Order::get_customer_id($order) == null || WCSTS_Order::get_customer_id($order) == 0)
			return;
		$current_user_id = get_current_user_id();
		$args = array(
				'posts_per_page'   => -1,
				'category'         => '',
				'category_name'    => '',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'meta_key'         => 'is_customer_message',
				'meta_value'       => 1,
				'post_type'        => 'wcsts_ticket_message',
				'post_parent'      =>  $ticket_id,
				/* 'author'	   => $order->get_customer_id(), */
				'post_status'      => 'publish',
				'suppress_filters' => true,
				'fields'        => 'ids',				
			 /* 'meta_query' => array(
					 array(
							 'key' => 'featured',
							 'value' => 'yes',
						   )
						) */
			);
		$messages = get_posts( $args );
		/* wcsts_var_dump($messages );
		wp_die(); */
		$customer_id = WCSTS_Order::get_customer_id($order);
		foreach((array)$messages as $ticket_message_id)
			wp_update_post( array('ID' => $ticket_message_id, 'post_author'=>$customer_id) ); 
	
	}
	public function get_messages_by_ticket_id($ticket_id)
	{
		$args = array(
				'posts_per_page'   => -1,
				'category'         => '',
				'category_name'    => '',
				'orderby'          => 'date',
				'order'            => 'ASC',
				/* 'meta_key'         => 'is_customer_message',
				'meta_value'       => 1, */
				'post_type'        => 'wcsts_ticket_message',
				'post_parent'      =>  $ticket_id,
				/* 'author'	   => $order->get_customer_id(), */
				'post_status'      => 'publish',
				'suppress_filters' => true,
				/* 'fields'        => 'ids' */
			);
		$messages = get_posts( $args );
		foreach((array)$messages as $key => $message)
					$messages[$key]->is_customer_message = get_post_meta($message->ID, 'is_customer_message',true) ? true : false;
		
		return $messages;
	}
	public function add_reply($ticket_id, $message, $is_customer = false, $author_id = null)
	{
		global $wcsts_ticket_model;
		$new_reply = array(
			'post_title'    => "",
			'post_content'  => $message,
			'post_status'   => 'publish',
			'post_parent'   => $ticket_id,
			'post_author'   => $author_id ? $author_id : get_current_user_id(),
			'post_type'     => 'wcsts_ticket_message',
		);
		 
		$reply_id = wp_insert_post( $new_reply );
		if(is_numeric($reply_id))
		{
			update_post_meta($reply_id, 'is_customer_message', $is_customer);
			if(!$is_customer)
			{
				$wcsts_ticket_model->update_new_admin_messages($ticket_id);
			}
			return $reply_id;
		}
		return false;
	}
	public function delete_all_ticket_messages($ticket_id)
	{
		$args = array( 
			'post_parent' => $ticket_id,
			'post_type' => 'wcsts_ticket_message',
			'fields'        => 'ids'
		);

		$messages = get_posts( $args );
		
		foreach((array)$messages as $message_id)
			$this->delete($message_id);
	}
	public function get_attachments($message_id)
	{
		$upoad_dir = wp_upload_dir();
		$attachments = get_post_meta($message_id, 'wcsts_attachment');
		$result = array();
		
		foreach((array)$attachments as $attachment)
			$result[$attachment] = $upoad_dir['baseurl'].'/'.$attachment;
		
		return $result;
	}
	public function delete_attachment($message_id, $attachment_unique_value)
	{
		global $wcsts_file_model;
		
		//Update post meta 
		//wcsts_var_dump($message_id);
		//wcsts_var_dump($attachment_unique_value);
		$result = delete_post_meta($message_id, 'wcsts_attachment', $attachment_unique_value);
		//wcsts_var_dump($result);
		
		//file delete 
		$upoad_dir = wp_upload_dir();
		$wcsts_file_model->delete_file($attachment_unique_value);
	}
	public function delete($message_id)
	{
		wp_delete_post($message_id, true);
	}
	public function add_attachment_path($message_id, $file_path)
	{
		add_post_meta($message_id, 'wcsts_attachment', $file_path, false);
	}
	public function delete_all_attachments($message_id)
	{
		global $wcsts_file_model;
		$message = get_post($message_id);
		if (!isset($message) || $message->post_type != 'wcsts_ticket_message')
			return;
		
		$attachments = get_post_meta($message_id, 'wcsts_attachment');
		
		foreach((array)$attachments as $attachment)
			$wcsts_file_model->delete_file($attachment); 
	}
}
?>