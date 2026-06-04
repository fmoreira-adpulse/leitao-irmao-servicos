<?php 
class WCSTS_Text
{
	var $text_cache;
	var $topic_cache;
	var $topic_recipents_cache;
	public function __construct()
	{
	}
	public function get_topic_type($topic_type)
	{
		$all_data['order_ticket_subject_type'] = get_field('wcsts_order_ticket_subject_type', 'option'); 
		$all_data['order_ticket_subject_type'] = $all_data['order_ticket_subject_type'] != null ? $all_data['order_ticket_subject_type'] : 'text_input';
		
		$all_data['user_ticket_subject_type'] = get_field('wcsts_user_ticket_subject_type', 'option'); 
		$all_data['user_ticket_subject_type'] = $all_data['user_ticket_subject_type'] != null ? $all_data['user_ticket_subject_type'] : 'text_input'; 	
		
		return isset($all_data[$topic_type]) ? $all_data[$topic_type] : 'text_input';
	}
	public function get_topics_data($subject_type = null)
	{
		if(isset($this->topic_cache))
			return isset($subject_type) ? $this->topic_cache[$subject_type] : $this->topic_cache;
		$all_data = array();
		
		//Subject types
		$all_data['order_ticket_subject_type'] = get_field('wcsts_order_ticket_subject_type', 'option'); 
		$all_data['order_ticket_subject_type'] = $all_data['order_ticket_subject_type'] != null ? $all_data['order_ticket_subject_type'] : 'text_input';
		
		$all_data['user_ticket_subject_type'] = get_field('wcsts_user_ticket_subject_type', 'option'); 
		$all_data['user_ticket_subject_type'] = $all_data['user_ticket_subject_type'] != null ? $all_data['user_ticket_subject_type'] : 'text_input'; 	
		
		//Topics 
		$all_data['order_ticket_subject_topics'] = array();
		$counter = 0;
		if( have_rows('wcsts_order_ticket_subject_topics', 'option') )
			while ( have_rows('wcsts_order_ticket_subject_topics', 'option') ) 
			{
				the_row();
				$all_data['order_ticket_subject_topics'][$counter++] = get_sub_field('wcsts_topic', 'option');
			}
				
		$all_data['user_ticket_subject_topics'] = array();
		$counter = 0;
		if( have_rows('wcsts_user_ticket_subject_topics', 'option') )
			while ( have_rows('wcsts_user_ticket_subject_topics', 'option') ) 
			{
				the_row();
				$all_data['user_ticket_subject_topics'][$counter++] = get_sub_field('wcsts_topic', 'option');
			}
		
		$all_data['automatic_ticket_subject_topic'] = get_field('wcsts_automatic_ticket_subject_topic', 'option'); 
		$all_data['automatic_ticket_subject_topic'] = $all_data['automatic_ticket_subject_topic'] != null ? $all_data['automatic_ticket_subject_topic'] : '';
		
		$this->topic_cache = $all_data;		
		/* wcmca_var_dump($subject_type);
		wcmca_var_dump($all_data);
		wcmca_var_dump($all_data[$subject_type]); */
		return isset($subject_type) && isset($all_data[$subject_type]) ? $all_data[$subject_type] : $all_data;
	}
	public function get_topic_recipents_data()
	{
		if(isset($this->topic_recipents_cache))
			return $this->topic_recipents_cache;
		$data = array();
		
		$data['order_ticket_recipients'] = array();
		$counter = 0;
		if( have_rows('wcsts_order_ticket_subject_topics', 'option') )
			while ( have_rows('wcsts_order_ticket_subject_topics', 'option') ) 
			{
				the_row();
				$data['order_ticket_recipients'][$counter++] = get_sub_field('wcsts_topic_email_recipient', 'option');
			}
				
		$data['user_ticket_recipients'] = array();
		$counter = 0;
		if( have_rows('wcsts_user_ticket_subject_topics', 'option') )
			while ( have_rows('wcsts_user_ticket_subject_topics', 'option') ) 
			{
				the_row();
				$data['user_ticket_recipients'][$counter++] = get_sub_field('wcsts_topic_email_recipient', 'option');
			}
		
		$this->topic_recipents_cache = $data;
		return $data;
	}
	public function get_texts()
	{
		if(isset($this->text_cache))
			return $this->text_cache;
		
		$all_data['new_ticket_description_text'] = get_field('wcsts_new_ticket_description_text', 'option'); 		
		$all_data['new_ticket_description_text'] = $all_data['new_ticket_description_text'] != null ? $all_data['new_ticket_description_text'] : ""; 		
		
		$all_data['new_ticket_succesfully_submitted_message'] = get_field('wcsts_new_ticket_succesfully_submitted_message', 'option'); 
		$all_data['new_ticket_succesfully_submitted_message'] = $all_data['new_ticket_succesfully_submitted_message'] != null ? $all_data['new_ticket_succesfully_submitted_message'] : esc_html__("The ticket has been succesfully submitted!","woocommerce-support-ticket-system"); 
		
		//My account
		$all_data['my_account_page_user_ticket_area_tab_label'] = get_field('wcsts_my_account_page_user_ticket_area_tab_label', 'option'); 
		$all_data['my_account_page_user_ticket_area_tab_label'] = $all_data['my_account_page_user_ticket_area_tab_label'] != null ? $all_data['my_account_page_user_ticket_area_tab_label'] : esc_html__("Personal Ticket Area","woocommerce-support-ticket-system"); 
		
		$all_data['my_account_tab_page_description'] = get_field('wcsts_my_account_tab_page_description', 'option'); 
		$all_data['my_account_tab_page_description'] = $all_data['my_account_tab_page_description'] != null ? $all_data['my_account_tab_page_description'] : ""; 
		
		$all_data['personal_ticket_area_tab_new_messages_counter_label'] = get_field('wcsts_personal_ticket_area_tab_new_messages_counter_label', 'option'); 
		$all_data['personal_ticket_area_tab_new_messages_counter_label'] = $all_data['personal_ticket_area_tab_new_messages_counter_label'] == "" || $all_data['personal_ticket_area_tab_new_messages_counter_label'] != null  ? $all_data['personal_ticket_area_tab_new_messages_counter_label'] : esc_html__('(new messages: %s)','woocommerce-support-ticket-system'); 
	
		//Emails 
		//Users
		$all_data['user_new_ticket_subject_text'] = get_field('wcsts_user_new_ticket_subject_text', 'option'); 		
		$all_data['user_new_ticket_subject_text'] = $all_data['user_new_ticket_subject_text'] != null ? $all_data['user_new_ticket_subject_text'] : esc_html__("New ticket #{ticket_id} submitted","woocommerce-support-ticket-system"); 
		
		$all_data['user_new_ticket_email_body_message'] = get_field('wcsts_user_new_ticket_email_body_message', 'option'); 		
		$all_data['user_new_ticket_email_body_message'] = $all_data['user_new_ticket_email_body_message'] != null ? $all_data['user_new_ticket_email_body_message'] : esc_html__("Thank you for contacting us. You ticket has been received, we will contact you back as soon as possible.", "woocommerce-support-ticket-system"); 
		
		$all_data['user_new_reply_by_admin_email_subject_text'] = get_field('wcsts_user_new_reply_by_admin_email_subject_text', 'option'); 		
		$all_data['user_new_reply_by_admin_email_subject_text'] = $all_data['user_new_reply_by_admin_email_subject_text'] != null ? $all_data['user_new_reply_by_admin_email_subject_text'] : esc_html__("New reply to ticket #{ticket_id}","woocommerce-support-ticket-system");  
		
		$all_data['user_new_reply_by_admin_email_body'] = get_field('wcsts_user_new_reply_by_admin_email_body', 'option'); 		
		$all_data['user_new_reply_by_admin_email_body'] = $all_data['user_new_reply_by_admin_email_body'] != null ? $all_data['user_new_reply_by_admin_email_body'] : esc_html__("The admin has replied to your ticket:<br/><br/><i>{message}</i><br/><br/>Reply by clicking on the following link: {order_page_url}", "woocommerce-support-ticket-system") ;
		
		$all_data['user_new_ticket_subject_text_user_type'] = get_field('wcsts_user_new_ticket_subject_text_user_type', 'option'); 		
		$all_data['user_new_ticket_subject_text_user_type'] = $all_data['user_new_ticket_subject_text_user_type'] != null ? $all_data['user_new_ticket_subject_text_user_type'] : esc_html__("New ticket #{ticket_id} submitted","woocommerce-support-ticket-system");
		
		$all_data['user_new_ticket_email_body_message_user_type'] = get_field('wcsts_user_new_ticket_email_body_message_user_type', 'option'); 		
		$all_data['user_new_ticket_email_body_message_user_type'] = $all_data['user_new_ticket_email_body_message_user_type'] != null ? $all_data['user_new_ticket_email_body_message_user_type'] :  esc_html__("Thank you for contacting us. You ticket has been received, we will contact you back as soon as possible.", "woocommerce-support-ticket-system") ; 
		
		$all_data['user_new_reply_by_admin_email_subject_text_user_type'] = get_field('wcsts_user_new_reply_by_admin_email_subject_text_user_type', 'option'); 		
		$all_data['user_new_reply_by_admin_email_subject_text_user_type'] = $all_data['user_new_reply_by_admin_email_subject_text_user_type'] != null ? $all_data['user_new_reply_by_admin_email_subject_text_user_type'] : esc_html__("New reply to ticket #{ticket_id}","woocommerce-support-ticket-system"); 
		
		$all_data['user_new_reply_by_admin_email_body_user_type'] = get_field('wcsts_user_new_reply_by_admin_email_body_user_type', 'option'); 		
		$all_data['user_new_reply_by_admin_email_body_user_type'] = $all_data['user_new_reply_by_admin_email_body_user_type'] != null ? $all_data['user_new_reply_by_admin_email_body_user_type'] : esc_html__("The admin has replied to your ticket:<br/><br/><i>{message}</i>", "woocommerce-support-ticket-system") ;
		
		//PPT
		$all_data['ppt_no_more_questions_left_message'] = get_field('wcsts_ppt_no_more_questions_left_message', 'option'); 		
		$all_data['ppt_no_more_questions_left_message'] = $all_data['ppt_no_more_questions_left_message'] != null ? $all_data['ppt_no_more_questions_left_message'] : esc_html__("You cannot ask any more question.", "woocommerce-support-ticket-system") ;
		
		//Order list page
		$all_data['get_help_button_text'] = get_field('wcsts_get_help_button_text', 'option'); 		
		$all_data['get_help_button_text'] = $all_data['get_help_button_text'] != null ? $all_data['get_help_button_text'] : esc_html__("Get Help", "woocommerce-support-ticket-system") ;
		
		$all_data['staff_label_text'] = get_field('wcsts_staff_label_text', 'option'); 		
		$all_data['staff_label_text'] = $all_data['staff_label_text'] != null ? $all_data['staff_label_text'] : esc_html__("Staff", "woocommerce-support-ticket-system") ;
		
		
		//Admin
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$all_data['admin_new_ticket_subject_text'] = get_field('wcsts_admin_new_ticket_subject_text', 'option'); 		
		$all_data['admin_new_ticket_subject_text'] = $all_data['admin_new_ticket_subject_text'] != null ? $all_data['admin_new_ticket_subject_text'] : esc_html__("New ticket #{ticket_id} submitted", "woocommerce-support-ticket-system"); 
		
		$all_data['admin_new_ticket_email_body_message'] = get_field('wcsts_admin_new_ticket_email_body_message', 'option'); 		
		$all_data['admin_new_ticket_email_body_message'] = $all_data['admin_new_ticket_email_body_message'] != null ? $all_data['admin_new_ticket_email_body_message'] : esc_html__("The user has submitted a new ticket:<br/><h3>Ticket subject</h3><br/><i>{subject}</i><br/><br/><h3>Message</h3><br/><i>{message}</i><br/><br/>Reply by clicking on the following link: {admin_ticket_page}", "woocommerce-support-ticket-system"); 
		
		$all_data['admin_new_reply_by_user_email_subject_text'] = get_field('wcsts_admin_new_reply_by_user_email_subject_text', 'option'); 		
		$all_data['admin_new_reply_by_user_email_subject_text'] = $all_data['admin_new_reply_by_user_email_subject_text'] != null ? $all_data['admin_new_reply_by_user_email_subject_text'] : esc_html__("New reply to ticket #{ticket_id}", "woocommerce-support-ticket-system"); 
		
		$all_data['admin_new_reply_by_user_email_body'] = get_field('wcsts_admin_new_reply_by_user_email_body', 'option'); 		
		$all_data['admin_new_reply_by_user_email_body'] = $all_data['admin_new_reply_by_user_email_body'] != null ? $all_data['admin_new_reply_by_user_email_body'] : esc_html__("The user replied to your ticket:<br/><h3>Ticket subject</h3><br/><i>{subject}</i><br/><br/><h3>Message</h3><br/><i>{message}</i><br/><br/>Reply by clicking on the following link: {admin_ticket_page}", "woocommerce-support-ticket-system");;

		$all_data['admin_new_ticket_assigned_email_subject_text'] = get_field('wcsts_admin_new_ticket_assigned_email_subject_text', 'option'); 		
		$all_data['admin_new_ticket_assigned_email_subject_text'] = $all_data['admin_new_ticket_assigned_email_subject_text'] != null ? $all_data['admin_new_ticket_assigned_email_subject_text'] : esc_html__("Following tickets have been assigned: {ticket_id}","woocommerce-support-ticket-system"); ; 
		
		$all_data['admin_new_ticket_assigned_email_body'] = get_field('wcsts_admin_new_ticket_assigned_email_body', 'option'); 		
		$all_data['admin_new_ticket_assigned_email_body'] = $all_data['admin_new_ticket_assigned_email_body'] != null ? $all_data['admin_new_ticket_assigned_email_body'] : esc_html__("Following tickets have been assigned: {ticket_id}. <br/>Reply by clicking on the following link(s): {admin_ticket_page}","woocommerce-support-ticket-system"); 
		
		$all_data['email_sender_name'] = get_field('wcsts_email_sender_name', 'option'); 		
		$all_data['email_sender_name'] = $all_data['email_sender_name'] != null && $all_data['email_sender_name'] != "" ? $all_data['email_sender_name'] : get_bloginfo('name'); 
		
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		$this->text_cache = $all_data;
		return $all_data;
	}
	function get_email_sender_name()
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$email_sender_name = get_field('wcsts_email_sender_name', 'option'); 		
		$email_sender_name = $email_sender_name != null && $email_sender_name != "" ? $email_sender_name : get_bloginfo('name'); 
		
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		return $email_sender_name;
	}
	function get_automatic_ticket_first_message()
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$automatic_ticket_first_message = get_field('wcsts_automatic_ticket_first_message', 'option'); 		
		$automatic_ticket_first_message = $automatic_ticket_first_message != null && $automatic_ticket_first_message != "" ? $automatic_ticket_first_message : ""; 
		
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		return $automatic_ticket_first_message;
	}
	function cl_acf_set_language() 
	{
	  return acf_get_setting('default_language');
	}
	function replace_shortcodes($message, $user_id, $order = null)
	{
		$is_order = isset($order);
		$account_shortcodes = array('[account_first_name]', '[account_last_name]', '[account_email]');
		$order_shortcodes = array('[order_id]', '[order_total]', '[order_date]');
		$billing_shortcodes = array('[billing_first_name]', '[billing_last_name]', '[billing_email]', '[billing_company]', '[billing_company]', '[billing_phone]', '[billing_country]', '[billing_state]', '[billing_city]', '[billing_post_code]', '[billing_address_1]', '[billing_address_2]', '[formatted_billing_address]');
		$shipping_shortcodes = array('[shipping_first_name]', '[shipping_last_name]', '[shipping_company]', '[shipping_phone]', '[shipping_country]', '[shipping_state]', '[shipping_city]', '[shipping_post_code]', '[shipping_address_1]', '[shipping_address_2]', '[formatted_shipping_address]');
		
		//list: 
		// - account: 			[account_first_name], [account_last_name], [account_email]
		// - order:   			[order_id], [order_total], [order_date]
		// - billing/shipping:  [billing_first_name], [billing_last_name], [billing_email], [billing_company], [billing_company], [billing_phone], [billing_country], [billing_state], [billing_city], [billing_post_code], [billing_address_1], [billing_address_2], [formatted_billing_address]
		
		$customer = isset($user_id) ? new WC_Customer( $user_id ) : null;
		foreach($account_shortcodes as $current_shortcode)
			if (strpos($message, $current_shortcode) !== false)
			{
				$method_name = "get_".str_replace(array('[',']', 'account_'), "", $current_shortcode);
				$value = is_callable ( array($customer , $method_name) ) ? $customer->$method_name() : "";
				$message = str_replace($current_shortcode, $value, $message);
			}
		foreach($order_shortcodes as $current_shortcode)
			if (strpos($message, $current_shortcode) !== false)
			{
				$original_method_name = $method_name = str_replace(array('[',']'), "", $current_shortcode);
				switch($method_name)
				{
					case 'order_id': $method_name = 'get_id'; break;
					case 'order_total': $method_name = 'get_formatted_order_total'; break;
					case 'order_date': $method_name = 'get_date_created'; break;
				}
				$value = $order != null && is_callable ( array($order , $method_name) ) ? $order->$method_name() : "";
				if(is_object($value) && get_class($value) == 'WC_DateTime')
				{
					$value = $value->date_i18n(get_option('date_format')." ".get_option('time_format'));
					//wcsts_var_dump($value);
				}
				$message = str_replace($current_shortcode, $value, $message);
			}
		foreach($billing_shortcodes as $current_shortcode)
			if (strpos($message, $current_shortcode) !== false)
			{
				$method_name = "get_".str_replace(array('[',']'), "", $current_shortcode);
				$value = $order != null && is_callable ( array($order , $method_name) ) ? $order->$method_name() : "";
				$message = str_replace($current_shortcode, $value, $message);
			}
		foreach($shipping_shortcodes as $current_shortcode)
			if (strpos($message, $current_shortcode) !== false)
			{
				$method_name = "get_".str_replace(array('[',']'), "", $current_shortcode);
				$value = $order != null && is_callable ( array($order , $method_name) ) ? $order->$method_name() : "";
				$message = str_replace($current_shortcode, $value, $message);
			}
		//wp_die();
		return $message;
	}
}
?>