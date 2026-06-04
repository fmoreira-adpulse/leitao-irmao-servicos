<?php 
class WCSTS_Email
{
	public function __construct()
	{
	}
	public function send_email($recipients, $subject, $content)
	{
		global $wcsts_option_helper;
		$mail = WC()->mailer();
		$email_heading = get_bloginfo('name');
		$email_options = $wcsts_option_helper->get_email_options();
		$content = wcsts_trim_breaks(nl2br(trim($content)));
		
		ob_start();
		if( !$email_options['disable_email_header_and_footer'])
			$mail->email_header($email_heading, $mail);
		echo stripcslashes($content);
		if( !$email_options['disable_email_header_and_footer'])
			$mail->email_footer($mail);
		$message =  ob_get_contents();
		ob_end_clean(); 
		
		add_filter('wp_mail_from_name',array(&$this, 'wp_mail_from_name'), 99, 1);
		add_filter('woocommerce_email_from_name',array(&$this, 'wp_mail_from_name'));
		add_filter('woocommerce_email_from_address', array(&$this, 'wp_mail_from'));
		$attachments = array();
		if(!$mail->send( $recipients, $subject, $message, "Content-Type: text/html\r\n")) //$mail->send || wp_mail
		{
			wp_mail( $recipients, $subject, $message, "Content-Type: text/html\r\n");
		}
		remove_filter('wp_mail_from_name',array(&$this, 'wp_mail_from_name'));
		remove_filter('woocommerce_email_from_name',array(&$this, 'wp_mail_from_name'));
		remove_filter('wp_mail_from',array(&$this, 'wp_mail_from'));
		remove_filter('woocommerce_email_from_address',array(&$this, 'wp_mail_from'));
	}
	public function wp_mail_from_name($name) 
	{
		global $wcsts_text_helper;
		$text = $wcsts_text_helper->get_email_sender_name();
		return $text;
	}
	public function wp_mail_from($content_type) 
	{
		global $wcsts_option_helper;
		$email_options = $wcsts_option_helper->get_email_options();
		
		if(!isset($email_options['email_from_address']) || $email_options['email_from_address'] == "")
		{
			$server_headers = apache_request_headers();
			$domain = $server_headers['Host'] ;
			$domain = isset($domain) && is_string($domain) ? str_replace("www.", "", $domain) : "none";
			$sender = 'noprely@'.$domain;
		}
		else 
		{
			$sender = $email_options['email_from_address'];
		}
			
		return $sender;
	}
	public function send_manager_user_assigned_ticket_notification($ticket_ids, $user_ids)
	{
		global $wcsts_text_helper, $wcsts_option_helper, $wcsts_ticket_model;
		$texts = $wcsts_text_helper->get_texts();
		$notify_options =  $wcsts_option_helper->get_all_options();
		
		//Text
		$admin_ticket_page = "";
		foreach($ticket_ids as $ticket_id)
		{
			if($admin_ticket_page != "")
				$admin_ticket_page .=", ";
			$admin_ticket_page .= '<a href="'.get_edit_post_link($ticket_id).'">'. sprintf(__('Ticket #%s details page', 'woocommerce-support-ticket-system'), $ticket_id).'</a>';
		}
		$ticket_id = implode(", ",$ticket_ids);
		$texts['admin_new_ticket_assigned_email_subject_text'] = str_replace("{ticket_id}", $ticket_id, $texts['admin_new_ticket_assigned_email_subject_text']);
		$texts['admin_new_ticket_assigned_email_body'] = str_replace("{ticket_id}", $ticket_id,$texts['admin_new_ticket_assigned_email_body']);
		$texts['admin_new_ticket_assigned_email_body'] = str_replace("{admin_ticket_page}", $admin_ticket_page, $texts['admin_new_ticket_assigned_email_body']);
		
		//Recipients
		$recipients = array();
		foreach($user_ids as $user_id)
		{
			$user = get_userdata( $user_id );
			$recipients[] = $user->user_email;
		}
		if(!empty($recipients))
			$this->send_email(implode(",",$recipients), $texts['admin_new_ticket_assigned_email_subject_text'], $texts['admin_new_ticket_assigned_email_body']);
	}
	public function send_new_ticket_notification_to_admin($ticket_id, $ticket_message, $topic_specific_recipients = '')
	{
		global $wcsts_text_helper, $wcsts_option_helper, $wcsts_ticket_model, $wcsts_user_model;
		$texts = $wcsts_text_helper->get_texts();
		$notify_options =  $wcsts_option_helper->get_all_options();
		$ticket_subject = $wcsts_ticket_model->get_subject($ticket_id);
		if(!$notify_options['admin_email_notifications'] || !$notify_options['admin_new_ticket_submission_notification'] )
			return;
		
		$order = null;
		if( $wcsts_ticket_model->get_attributes($ticket_id, 'ticket_type') == 'order')
		{
			$order_id =  $wcsts_ticket_model->get_attributes($ticket_id, 'associated_order');
			$order = wc_get_order($order_id);
			$user_id = $order->get_customer_id();
		}
		else
			$user_id = $wcsts_ticket_model->get_attributes($ticket_id, 'associated_user');
		
		//Text
		$admin_ticket_page = '<a href="'.get_admin_url().'post.php?post='.$ticket_id.'&action=edit">'. __('Ticket details page', 'woocommerce-support-ticket-system').'</a>';
		$texts['admin_new_ticket_subject_text'] = str_replace("{ticket_id}", $ticket_id, $texts['admin_new_ticket_subject_text']);
		$texts['admin_new_ticket_subject_text'] = str_replace("{subject}", $ticket_subject, $texts['admin_new_ticket_subject_text']);
		$texts['admin_new_ticket_email_body_message'] = str_replace("{ticket_id}", $ticket_id,$texts['admin_new_ticket_email_body_message']);
		$texts['admin_new_ticket_email_body_message'] = str_replace("{subject}", $ticket_subject, $texts['admin_new_ticket_email_body_message']);
		$texts['admin_new_ticket_email_body_message'] = str_replace("{message}", $ticket_message, $texts['admin_new_ticket_email_body_message']);
		$texts['admin_new_ticket_email_body_message'] = str_replace("{admin_ticket_page}", $admin_ticket_page, $texts['admin_new_ticket_email_body_message']);
		$texts['admin_new_ticket_email_body_message'] = $wcsts_text_helper->replace_shortcodes($texts['admin_new_ticket_email_body_message'], $user_id, $order);
		
		//Recipients
		//Check specific users have been assigned to ticket managment -> On new ticket customer manager cannot be already assigned
		//$user_ids = $wcsts_ticket_model->get_manager_user_ids($ticket_id);
		//$default_recipient  = !empty($user_ids) ? $wcsts_user_model->get_user_email_by_ids($user_ids) : get_bloginfo('admin_email');
		//
		$recipients =/*  empty($user_ids) && */ $notify_options['admin_custom_email_recipiens'] != " " && $notify_options['admin_custom_email_recipiens'] != "" ? $notify_options['admin_custom_email_recipiens'] : get_bloginfo('admin_email');
		$recipients_override = $wcsts_ticket_model->get_attributes($ticket_id,'notification_recipients_override');
		$recipients = $topic_specific_recipients != "" ? $topic_specific_recipients : $recipients;
		$recipients = $recipients_override != " " && $recipients_override != "" ? $recipients_override : $recipients;
		
		$this->send_email($recipients, $texts['admin_new_ticket_subject_text'], $texts['admin_new_ticket_email_body_message']);
	}
	public function send_new_ticket_notification_to_user($ticket_id, $ticket_message, $user, $type, $order = null)
	{
		global $wcsts_text_helper, $wcsts_option_helper, $wcsts_ticket_model, $wcsts_order_model;
		$texts = $wcsts_text_helper->get_texts();
		$notify_options =  $wcsts_option_helper->get_all_options();
		$user_ticket_area_endpoint =  $wcsts_option_helper->get_user_ticket_area_endpoint();
		$ticket_subject = $wcsts_ticket_model->get_subject($ticket_id);
		$user_email = $user->user_email;
		if(!$notify_options['user_email_notifications'] || !$notify_options['user_new_ticket_submission_notification'] )
			return;
		
		$text_type_identifier = $type == "order" ? "" : "_user_type";
		//Text
		$ticket_page_url = '<a href="'.add_query_arg($user_ticket_area_endpoint, "", get_permalink( get_option( 'woocommerce_myaccount_page_id' ) )  ).'">'.__('Ticket page', 'woocommerce-support-ticket-system').'</a>';
		
		$subject = str_replace("{ticket_id}", $ticket_id, $texts['user_new_ticket_subject_text'.$text_type_identifier]);
		$subject = str_replace("{subject}", $ticket_subject, $subject);
		$message = str_replace("{ticket_id}", $ticket_id, $texts['user_new_ticket_email_body_message'.$text_type_identifier]);
		$message = str_replace("{subject}", $ticket_subject, $message);
		$message = str_replace("{message}", $ticket_message, $message);
		$message = str_replace("{ticket_page_url}", $ticket_page_url, $message);
		$message = $wcsts_text_helper->replace_shortcodes($message, $user->ID, $order);
		
		$order_page_url = "";
		if(isset($order))
		{
			$user_email = $order->get_billing_email();
			$order_page_url = '<a href="'.$wcsts_order_model->get_order_details_page_url($order).'">'.__('Order details page', 'woocommerce-support-ticket-system').'</a>';
			
		}
		$message = str_replace("{order_page_url}", $order_page_url, $message);
		
		
		$this->send_email($user_email, $subject, $message);
	}
	public function send_reply_notification_to_admin($ticket_id, $ticket_message, $topic_specific_recipients = '')
	{
		global $wcsts_text_helper, $wcsts_option_helper, $wcsts_ticket_model, $wcsts_user_model;
		$texts = $wcsts_text_helper->get_texts();
		$notify_options =  $wcsts_option_helper->get_all_options();
		$ticket_subject = $wcsts_ticket_model->get_subject($ticket_id);
		if(!$notify_options['admin_email_notifications'] || !$notify_options['admin_reply_by_user_notification'] )
			return;
		
		$order = null;
		if( $wcsts_ticket_model->get_attributes($ticket_id, 'ticket_type') == 'order')
		{
			$order_id =  $wcsts_ticket_model->get_attributes($ticket_id, 'associated_order');
			$order = wc_get_order($order_id);
			$user_id = $order->get_customer_id();
		}
		else
			$user_id = $wcsts_ticket_model->get_attributes($ticket_id, 'associated_user');
		
		
		//Text
		$admin_ticket_page = '<a href="'.get_admin_url().'post.php?post='.$ticket_id.'&action=edit">'. __('Ticket details page', 'woocommerce-support-ticket-system').'</a>';
		$texts['admin_new_reply_by_user_email_subject_text'] = str_replace("{ticket_id}", $ticket_id, $texts['admin_new_reply_by_user_email_subject_text']);
		$texts['admin_new_reply_by_user_email_subject_text'] = str_replace("{subject}", $ticket_subject, $texts['admin_new_reply_by_user_email_subject_text']);
		$texts['admin_new_reply_by_user_email_body'] = str_replace("{ticket_id}", $ticket_id,$texts['admin_new_reply_by_user_email_body']);
		$texts['admin_new_reply_by_user_email_body'] = str_replace("{subject}", $ticket_subject, $texts['admin_new_reply_by_user_email_body']);
		$texts['admin_new_reply_by_user_email_body'] = str_replace("{message}", $ticket_message, $texts['admin_new_reply_by_user_email_body']);
		$texts['admin_new_reply_by_user_email_body'] = str_replace("{admin_ticket_page}", $admin_ticket_page, $texts['admin_new_reply_by_user_email_body']);
		$texts['admin_new_reply_by_user_email_body'] = $wcsts_text_helper->replace_shortcodes($texts['admin_new_reply_by_user_email_body'], $user_id, $order);
		
		//Recipients
		//Check specific users have been assigned to ticket managment
		$user_ids = $wcsts_ticket_model->get_manager_user_ids($ticket_id);
		$default_recipient  = !empty($user_ids) ? $wcsts_user_model->get_user_email_by_ids($user_ids) : get_bloginfo('admin_email');
		//
		$recipients = empty($user_ids) && $notify_options['admin_custom_email_recipiens'] != " " && $notify_options['admin_custom_email_recipiens'] != "" ? $notify_options['admin_custom_email_recipiens'] : $default_recipient;
		$recipients_override = $wcsts_ticket_model->get_attributes($ticket_id,'notification_recipients_override');
		$recipients = $topic_specific_recipients != "" ? $topic_specific_recipients : $recipients;
		$recipients = $recipients_override != " " && $recipients_override != "" ? $recipients_override : $recipients;
	
		$this->send_email($recipients, $texts['admin_new_reply_by_user_email_subject_text'], $texts['admin_new_reply_by_user_email_body']);
	}
	public function send_reply_notification_to_user($ticket_id, $ticket_message, $type)
	{
		global $wcsts_text_helper, $wcsts_option_helper, $wcsts_ticket_model, $wcsts_order_model;
		$texts = $wcsts_text_helper->get_texts();
		$notify_options =  $wcsts_option_helper->get_all_options();
		$user_ticket_area_endpoint =  $wcsts_option_helper->get_user_ticket_area_endpoint();
		$ticket_subject = $wcsts_ticket_model->get_subject($ticket_id);
		$ticket_page_url = '<a href="'.add_query_arg($user_ticket_area_endpoint, "", get_permalink( get_option( 'woocommerce_myaccount_page_id' ) )  ).'">'.__('Ticket page', 'woocommerce-support-ticket-system').'</a>';
		
		$user_id = $order = null;
		$order_page_url = $email = "";
		if($type == 'order')
		{
			$order_id =  $wcsts_ticket_model->get_attributes($ticket_id, 'associated_order');
			$order = isset($order_id) ? wc_get_order($order_id) : null;
			if(isset($order) && $order != false)
			{
				$user = $order->get_user();
				$user_id = $order->get_user_id();
				$email = $order->get_billing_email();
				$order_page_url = '<a href="'.$wcsts_order_model->get_order_details_page_url($order).'">'.__('Order details page', 'woocommerce-support-ticket-system').'</a>';
			}
			$text_type_identifier = "";
		}
		else
		{
			$user_id =  $wcsts_ticket_model->get_attributes($ticket_id, 'associated_user');
			$user = new WP_User($user_id);
			$email = $user->data->user_email;
			$order_page_url ="";
			$text_type_identifier = "_user_type";
		}
		
		
		if(!$notify_options['user_email_notifications'] || !$notify_options['user_reply_by_admin_notification'] )
			return;
		
		//Text
		$subject = str_replace("{ticket_id}", $ticket_id, $texts['user_new_reply_by_admin_email_subject_text'.$text_type_identifier]);
		$subject = str_replace("{subject}", $ticket_subject, $subject);
		$message = str_replace("{ticket_id}", $ticket_id, $texts['user_new_reply_by_admin_email_body'.$text_type_identifier]);
		$message = str_replace("{subject}", $ticket_subject, $message);
		$message = str_replace("{message}", $ticket_message, $message);
		$message = str_replace("{order_page_url}", $order_page_url, $message);
		$message = str_replace("{ticket_page_url}", $ticket_page_url, $message);
		$message = $wcsts_text_helper->replace_shortcodes($message, $user_id, $order);
		
		if($email != "")
			$this->send_email($email, $subject, $message);
	}
}
?>