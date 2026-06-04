<?php 
class WCSTS_Order
{
	static $ORDER_PPT_ASSIGNED_TO_USER_PROFILE_KEY_NAME = 'wcsts_ppt_meta_assigned_to_user'; //not used
	static $ORDER_PPT_TICKETS_HAVE_BEEN_CREATED_KEY_NAME = 'wcsts_ppt_tickets_have_beeen_created';
	static $ORDER_PPT_TICKET_ID_KEY_NAME = 'wcsts_ppt_ticket_id';
	static $ORDER_PPT_ORDER_KEY_PREFIX = 'wcsts_ppt_';
	static $ORDER_AUTOMATIC_TICKET_ALREADY_OPENED = 'wcsts_automatic_ticket_already_opened';
	
	public function __construct()
	{
		add_action('init', array($this, 'init'));
		
		//Order status change: Info are stored when the order is payed and rechecked when marked as completed
		//add_filter('woocommerce_payment_complete_order_status', array( &$this, 'on_order_payment_complete' ), 10 ,2); Moved into the init()
		 //WooCommerce Subscriptions: on renewal
		add_action('woocommerce_subscription_renewal_payment_complete', array( &$this, 'on_wcs_subscription_renewal' ), 10 ,3);
		//---
		add_action('woocommerce_order_status_changed', array( &$this, 'on_order_status_change' ));
		//add_action( 'woocommerce_order_status_on-hold', array( &$this, 'on_status_on_hold'), 10, 1);
		add_action( 'woocommerce_order_status_completed', array( &$this, 'on_order_status_completed'), 10, 1);
		//---
		add_action( 'woocommerce_order_status_failed', array( &$this, 'reset_ppt_data_on_bad_order_status'), 10, 1);
		add_action( 'woocommerce_order_status_refunded', array( &$this, 'reset_ppt_data_on_bad_order_status'), 10, 1);
		add_action( 'woocommerce_order_status_cancelled', array( &$this, 'reset_ppt_data_on_bad_order_status'), 10, 1);
		
		//On order delete 
		add_action( 'before_delete_post', array( &$this, 'reset_ppt_data_on_bad_order_status' ), 10 );
	}
	
	public function init()
	{
		global $wcsts_option_helper;
		$options = $wcsts_option_helper->get_automatic_ticket_options();
		
		if(!$wcsts_option_helper->ppt_disable_payment_detection())
			add_filter('woocommerce_payment_complete_order_status', array( &$this, 'on_order_payment_complete' ), 10 ,2);
		
		//wcsts_var_dump($options);
		if($options['open_ticket_automatically'] && !empty($options['automatic_ticket_order_status']))
		{
			foreach($options['automatic_ticket_order_status'] as $status)
			{
				$status_name = str_replace('wc-', '',$status);
				add_action( 'woocommerce_order_status_'.$status_name, array($this, 'on_order_statuts_transition'), 10, 2 );
			}
		}
	}
	public function get_all_order_numbers()
	{
		$orders = wc_get_orders( array(
			'limit' => -1,
			 'orderby' => 'ID',
			 'return' => 'ids',
		) );
		return $orders;
	}
	public static function get_customer_id($order)
	{
		if(is_bool($order))
			return 0;
		
		if(version_compare( WC_VERSION, '2.7', '<' ))
			return $order->customer_user;
		
		return $order->get_customer_id();
	}
	public static function get_id($order)
	{
		if(is_bool($order))
			return 0;
		
		if(version_compare( WC_VERSION, '2.7', '<' ))
			return $order->id;
		
		return $order->get_id();
	}
	
	
	public function on_wcs_subscription_renewal($wc_subscription_obj, $new_order)
	{
		
		global $wcsts_ticket_model;
		
		$original_order = $wc_subscription_obj->get_parent(); //returns order obj;
		$tickets_to_update = $this->get_meta($original_order, WCSTS_Order::$ORDER_PPT_TICKET_ID_KEY_NAME, false);
		foreach((array)$tickets_to_update  as $current_ticket)
		{
			$wcsts_ticket_model->ppt_reset_question_left_counter($current_ticket->value, $original_order);
		}
		
		//just created tickets for the new oreder are deleted
		$this->delete_ppt_meta_and_tickets($new_order);
	}
	public function on_order_status_change($order_id, $old_status = null, $new_status = null)
	{
		global $wcsts_option_helper, $wcsts_ticket_model;
		$order = wc_get_order($order_id);
		
		if(!isset($order) || $order == false)
			return;
		
		if($order->get_status() == 'completed' && $wcsts_option_helper->get_all_options('mark_ticket_as_closed_on_completed', false))
		{
			$orders_ticket = $this->get_ticket_ids_by_order_id($order_id);
			foreach((array)$orders_ticket as $ticket_obj)
			{
				$wcsts_ticket_model->set_status($ticket_obj->ticket_id, 'closed');
			}
		}	
		
	}
	public function on_order_status_completed($order_id, $old_status = null, $new_status = null)
	{
		global $wcsts_option_helper, $wcsts_ticket_model;
		$order = wc_get_order($order_id);
		if(!isset($order) || $order == false)
			return;
		
		$this->on_order_payment_complete($order->get_status(), $order_id);
	}
	public function has_ppt_ticket_associated($order)
	{
		return $this->get_meta($order, WCSTS_Order::$ORDER_PPT_TICKETS_HAVE_BEEN_CREATED_KEY_NAME, true);
	}
	public function reset_ppt_data_on_bad_order_status($order_id)
	{
		global $wcsts_user_model, $wcsts_ticket_model;
		$order = wc_get_order($order_id);
		if($order == false)
			return;
		
		$tickets_have_been_created = $this->has_ppt_ticket_associated($order);
		
		if(!$tickets_have_been_created)
			return;
		
		$this->update_meta($order, WCSTS_Order::$ORDER_PPT_TICKETS_HAVE_BEEN_CREATED_KEY_NAME, 'no');
		$meta_that_could_be_deleted = array();
		$items = $order->get_items();
		
		$this->delete_ppt_meta_and_tickets($order);
	}
	public function delete_ppt_meta_and_tickets($order)
	{
		global $wcsts_ticket_model;
		$tickets_to_delete = $this->get_meta($order, WCSTS_Order::$ORDER_PPT_TICKET_ID_KEY_NAME, false);
			foreach((array)$tickets_to_delete  as $ticket_to_delete)
				
				$wcsts_ticket_model->delete_ticket($ticket_to_delete->value);
		$this->delete_meta($order, WCSTS_Order::$ORDER_PPT_TICKET_ID_KEY_NAME);
	}
	public function on_order_payment_complete($order_status, $order_id )
	{
		global $wcsts_user_model, $wcsts_ticket_model, $wcsts_text_helper;	
		
		$order = wc_get_order($order_id);
		
		if(!isset($order) || $order == false)
			return $order_status;
		
		if(function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal( $order ))
			return $order_status;
		 
		if($order->get_customer_id() == 0)
			return $order_status;
	
		$tickets_have_been_created = $this->has_ppt_ticket_associated($order);
		if(!$tickets_have_been_created || $tickets_have_been_created === 'yes' )
			return $order_status; 
			
		$already_processed = array();
		$items = $order->get_items();
		foreach($items as $item)
		{
			$product_id = $item->get_product_id();
			$variation_id = $item->get_variation_id();
			if(isset($already_processed[$product_id."_".$variation_id]))
				continue;
			
			$key = WCSTS_Order::$ORDER_PPT_ORDER_KEY_PREFIX.$product_id.'_'.$variation_id;
			$results = $this->get_meta($order, $key, false);
			$already_processed[$product_id."_".$variation_id] = true;
			$product_name = wc_get_product($variation_id != 0 ? $variation_id : $product_id);
			$product_name = $product_name->get_name();
			$date_created = $order->get_date_created();
			$date_created = $date_created->date('Y-m-d H:i:s');
			
			//Old method by which data was saved on user profile
			/*if(isset($results))
				foreach((array)$results as $questions_number)
				{
					if($questions_number->value != 0)
						$wcsts_user_model->add_ppt_questions_number_meta($order->get_customer_id(), array( 'product_id' => $product_id,
																										   'variation_id' => $variation_id,
																										   'order_date' => $date_created, //WC_DateTime
																										   'questions_number' => $questions_number->value,
																										   'product_name'=> $product_name
																										   ));
				}*/
				
			//New tickets are created and references are saved into order metadata
			if(isset($results))
				foreach((array)$results as $questions_number)
					if($questions_number->value != 0)
					{
						$ticke_id = $wcsts_ticket_model->open_new_ticket(false, '', '', 'ppt', null, $order->get_customer_id(), array( 'product_id' => $product_id,
																																   'variation_id' => $variation_id,
																																   'order_date' => $date_created, //WC_DateTime
																																   'questions_number' => $questions_number->value,
																																   'product_name'=> $product_name,
																																   'order_id'=> $order_id,
																																   ));
																													   
						if($ticke_id > 0)
							$this->add_meta($order, WCSTS_Order::$ORDER_PPT_TICKET_ID_KEY_NAME, $ticke_id);
					}
		}	
		$this->update_meta($order, WCSTS_Order::$ORDER_PPT_TICKETS_HAVE_BEEN_CREATED_KEY_NAME, 'yes');
			
		return $order_status;
	}
	public function on_order_statuts_transition($order_id, $order)
	{
		try{
			global $wcsts_option_helper, $wcsts_text_helper, $wcsts_ticket_model, $wcsts_user_model;
			$automatic_ticket_first_message = $wcsts_text_helper->get_automatic_ticket_first_message();
			
			$already_opened = $this->get_meta( $order, WCSTS_Order::$ORDER_AUTOMATIC_TICKET_ALREADY_OPENED);
			if(!$already_opened) //In case was not already opened, the plugin creates a new "automatic ticket" (if the special option is set to yes)
			{
				$manager_id = $order->get_customer_id() ? $wcsts_user_model->get_associated_ticket_managers($order->get_customer_id(), true) : $wcsts_user_model->get_first_available_admin_id();
				$wcsts_ticket_model->open_new_ticket(false, $wcsts_text_helper->get_topics_data('automatic_ticket_subject_topic'), $automatic_ticket_first_message, 'order', $order, null, array('is_automatically_opened_ticket' => true, 'post_author' => $manager_id));
				$this->update_meta($order, WCSTS_Order::$ORDER_AUTOMATIC_TICKET_ALREADY_OPENED, true);
			}
		}catch(Exception $e){}
	}
	public function proces_order_items_saving_ppt_meta_on_order($order_id)
	{
		global $wcsts_product_model, $wcsts_wpml_helper;
		$order = wc_get_order($order_id);
		$items = $order->get_items();
		$exists_at_least_one_ppt = false;
		foreach($items as $item)
		{
			$product_id = $wcsts_wpml_helper->get_main_language_id($item->get_product_id());
			$variation_id = $wcsts_wpml_helper->get_main_language_id($item->get_variation_id());
			
			$questions_number = $wcsts_product_model->get_product_questions_number($variation_id != 0 ? $variation_id : $product_id);
			
			if($questions_number > 0)
			{
				$questions_number *= $item->get_quantity();
				$exists_at_least_one_ppt = true;
				$key = WCSTS_Order::$ORDER_PPT_ORDER_KEY_PREFIX.$product_id.'_'.$variation_id;
				//add_meta: In case cart has differnt item with same ids, in order are saved distinctly
				$this->add_meta($order, $key, $questions_number);
			}
		}
		
		if($exists_at_least_one_ppt)
		{
			//$this->update_meta($order, WCSTS_Order::$ORDER_PPT_ASSIGNED_TO_USER_PROFILE_KEY_NAME, 'no');
			$this->update_meta($order, WCSTS_Order::$ORDER_PPT_TICKETS_HAVE_BEEN_CREATED_KEY_NAME, 'no');
		}
	}
	public function assign_ticket_id_to_order($ticket_id, $order_id)
	{
		//Ticket id can be assigned only to one order. So all old order is resetted and then reassigned to the new one.
		/* $args = array(
				'posts_per_page'   => -1,
				'category'         => '',
				'category_name'    => '',
				'orderby'          => 'date',
				'order'            => 'DESC',
			    'meta_key'         => 'wcsts_ticket_id',
				'meta_value'       => $ticket_id, 
				'post_type'        => 'shop_order',
				// 'post_status'      => 'publish', 
				'suppress_filters' => true,
				'fields'        => 'ids'
			);
		$orders = get_posts( $args ); */
		//retrieve orlder order id by ticket id
		global $wpdb;
		$query = " SELECT * 
				   FROM {$wpdb->postmeta} AS order_meta
				   WHERE order_meta.meta_key = 'wcsts_ticket_id'
				   AND order_meta.meta_value = '{$ticket_id}' ";
				   
		$orders = $wpdb->get_results($query);
		//reset
		foreach((array)$orders as $order)
			delete_post_meta($order->post_id, 'wcsts_ticket_id', $ticket_id);
		
		//assign ticket id to the new one	
		add_post_meta($order_id, 'wcsts_ticket_id', $ticket_id, false);
	}
	public function get_order_details_page_url($order)
	{
		/*
		  An alternative can be: '<a href="'.add_query_arg('view-order', WCSTS_Order::get_id($order), get_permalink( get_option( 'woocommerce_myaccount_page_id' ) )  ).'">'.__('Order details page', 'woocommerce-support-ticket-system').'</a>';
		*/
		$order_url = $order->get_customer_id() ? $order->get_view_order_url(): 
												 $order->get_checkout_order_received_url( ); 
												 
		return $order_url;
	}
	public function get_meta($order_id_or_object, $key, $single = true)
	{
		$order = is_numeric($order_id_or_object) ? wc_get_order($order_id_or_object) : $order_id_or_object;
		
		return !isset($order) || is_bool($order) ? array() : $order->get_meta($key, $single);
	}
	public function add_meta($order_id_or_object, $key, $value, $unique = false)
	{
		$order = is_numeric($order_id_or_object) ? wc_get_order($order_id_or_object) : $order_id_or_object;
		$order->add_meta_data($key, $value, $unique);
		$order->save();
	}
	public function delete_meta($order_id_or_object, $key)
	{
		$order = is_numeric($order_id_or_object) ? wc_get_order($order_id_or_object) : $order_id_or_object;
		$order->delete_meta_data($key);
		$order->save();
	}
	public function update_meta($order_id_or_object, $key, $value)
	{
		$order = is_numeric($order_id_or_object) ? wc_get_order($order_id_or_object) : $order_id_or_object;
		$order->update_meta_data($key, $value);
		$order->save();
	}
	public function get_ticket_ids_by_order_id($order_id)
	{
		global $wcsts_ticket_message_model ;
		global $wpdb;
		//On meta table are created special key where the post_id is the order id and the wcsts_ticket_id value is the ticket id. 
		//Exist multiple post meta
		$query = " SELECT order_meta.meta_value AS ticket_id, tickets.post_status, tickets.post_date as date
				   FROM {$wpdb->postmeta} AS order_meta
				   INNER JOIN {$wpdb->posts} AS tickets ON tickets.ID = order_meta.meta_value
				   WHERE order_meta.meta_key = 'wcsts_ticket_id'
				   AND tickets.post_status = 'publish'
				   AND order_meta.post_id = '{$order_id}' ";
	
		return $wpdb->get_results($query); //return object: {ticket_id, post_status} -> obj->ticket_id
	}
	public function get_ppt_ticket_ids_by_order_id($order_id)
	{
		global $wcsts_ticket_message_model ;
		global $wpdb;
		$query = " SELECT ticket_meta.post_id AS ticket_id, tickets.post_status
				   FROM {$wpdb->postmeta} AS ticket_meta
				   INNER JOIN {$wpdb->posts} AS tickets ON tickets.ID = ticket_meta.post_id
				   WHERE ticket_meta.meta_key = 'wcsts_ppt_order_id'
				   AND tickets.post_status = 'publish'
				   AND ticket_meta.meta_value = '{$order_id}' ";
				   
		return $wpdb->get_results($query); //return object: {ticket_id, post_status} -> obj->ticket_id
	}
	public function delete_tickets_assigned_to_order($ticket_id)
	{
		global $wpdb;
		$query = " DELETE FROM {$wpdb->postmeta} 
				   WHERE meta_key = 'wcsts_ticket_id' 
				   AND meta_value = '{$ticket_id}' ";
	
		return $wpdb->get_results($query);
	}
}
?>