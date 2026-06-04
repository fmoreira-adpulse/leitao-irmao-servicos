<?php 
class WCSTS_Option
{
	var $all_data = null;
	var $email = null;
	public function __construct()
	{
	}
	public function ppt_disable_payment_detection()
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$ppt_disable_payment_detection = get_field('wcsts_ppt_disable_payment_detection', 'option'); 		
		$ppt_disable_payment_detection = isset($ppt_disable_payment_detection) ? $ppt_disable_payment_detection : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $ppt_disable_payment_detection;
	}
	public function get_automatic_ticket_options()
	{
		$all_data = array();
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$all_data['open_ticket_automatically'] = get_field('wcsts_open_ticket_automatically', 'option'); 		
		$all_data['open_ticket_automatically'] = $all_data['open_ticket_automatically'] != null ? $all_data['open_ticket_automatically'] : false; 		
		
		$all_data['automatic_ticket_enable_first_message_notification'] = get_field('wcsts_automatic_ticket_enable_first_message_notification', 'option'); 		
		$all_data['automatic_ticket_enable_first_message_notification'] = $all_data['automatic_ticket_enable_first_message_notification'] != null ? $all_data['automatic_ticket_enable_first_message_notification'] : false; 		
		
		
		$all_data['automatic_ticket_order_status'] = get_field('wcsts_automatic_ticket_order_status', 'option'); 		
		$all_data['automatic_ticket_order_status'] = $all_data['automatic_ticket_order_status'] != null ? $all_data['automatic_ticket_order_status'] : array(); 	

		
		foreach((array)$all_data['automatic_ticket_order_status'] as $key => $value)
			{
				if($value == "")
					unset($all_data['automatic_ticket_order_status'][$key]);
			}
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $all_data;
	}
	public function get_post_time_restriction_options()
	{
		$all_data = array();
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$all_data['new_ticket_message_post_time_interval'] = get_field('wcsts_new_ticket_message_post_time_interval', 'option'); 		
		$all_data['new_ticket_message_post_time_interval'] = $all_data['new_ticket_message_post_time_interval'] != null ? $all_data['new_ticket_message_post_time_interval'] : 0; 		
		
		$all_data['new_ticket_post_time_interval'] = get_field('wcsts_new_ticket_post_time_interval', 'option'); 		
		$all_data['new_ticket_post_time_interval'] = $all_data['new_ticket_post_time_interval'] != null ? $all_data['new_ticket_post_time_interval'] : 0; 		
		
	
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $all_data;
	}
	public function get_email_options()
	{
		
		$all_data = array();
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		if(!isset($this->email))	
		{
			$all_data['disable_email_header_and_footer'] = get_field('wcsts_disable_email_header_and_footer', 'option'); 		
			$all_data['disable_email_header_and_footer'] = $all_data['disable_email_header_and_footer'] != null ? $all_data['disable_email_header_and_footer'] : false; 		
			
			$all_data['email_from_address'] = get_field('wcst_email_from_address', 'option'); 		
			$all_data['email_from_address'] = $all_data['email_from_address'] != null ? $all_data['email_from_address'] : ""; 		
		}
		else
			$all_data = $this->email;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $all_data;
	}
	public function get_user_ticket_area_endpoint()
	{
		
		$result;
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$result = get_field('wcsts_user_tickect_area_url_endpoint', 'option'); 		
		$result = $result != null ? $result : "wcsts-user-tickets-area"; 		
			
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result;
	}
	public function get_all_options($option_name = null, $option_default_value = null)
	{
		$all_data = array();
		if(!isset($this->all_data))	
		{
			add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
			
			$all_data['subject_lenght'] = get_field('wcsts_subject_lenght', 'option'); 		
			$all_data['subject_lenght'] = $all_data['subject_lenght'] != null ? $all_data['subject_lenght'] : 140; 		
			
			$all_data['message_lenght'] = get_field('wcsts_message_lenght', 'option'); 
			$all_data['message_lenght'] = $all_data['message_lenght'] != null ? $all_data['message_lenght'] : 500; 
			
			$all_data['message_min_lenght'] = get_field('wcsts_message_min_lenght', 'option'); 
			$all_data['message_min_lenght'] = $all_data['message_min_lenght'] != null ? $all_data['message_min_lenght'] : 0; 
			
			$all_data['ticket_visibility'] = get_field('wcsts_ticket_visibility', 'option'); 
			$all_data['ticket_visibility'] = $all_data['ticket_visibility'] ? $all_data['ticket_visibility'] : 'all_tickets'; // all_tickets || only_assigned
			
			$all_data['deny_closed_ticket_reply'] = get_field('wcsts_deny_closed_ticket_reply', 'option'); 
			$all_data['deny_closed_ticket_reply'] = $all_data['deny_closed_ticket_reply'] ? $all_data['deny_closed_ticket_reply'] : false; 
			
			$all_data['display_ticket_priority_selector_on_frontend'] = get_field('wcsts_display_ticket_priority_selector_on_frontend', 'option'); 
			$all_data['display_ticket_priority_selector_on_frontend'] = $all_data['display_ticket_priority_selector_on_frontend'] ? $all_data['display_ticket_priority_selector_on_frontend'] : 'no'; 
			
			$all_data['display_ticket_status_on_frontend'] = get_field('wcsts_display_ticket_status_on_frontend', 'option'); 
			$all_data['display_ticket_status_on_frontend'] = $all_data['display_ticket_status_on_frontend'] ? $all_data['display_ticket_status_on_frontend'] : 'no'; 
			
			$all_data['frontend_use_tiny_mce'] = get_field('wcsts_frontend_use_tiny_mce', 'option'); 
			$all_data['frontend_use_tiny_mce'] = $all_data['frontend_use_tiny_mce'] ? $all_data['frontend_use_tiny_mce'] : false; 
			
			$all_data['ticket_area_pagination'] = get_field('wcsts_ticket_area_pagination', 'option'); 
			$all_data['ticket_area_pagination'] = $all_data['ticket_area_pagination'] ? $all_data['ticket_area_pagination'] : 10; 
			
			$all_data['roles_can_manage_ticket_system'] = get_field('wcsts_roles_can_manage_ticket_system', 'option'); 
			foreach((array)$all_data['roles_can_manage_ticket_system'] as $key => $value)
			{
				if($value == "")
					unset($all_data['roles_can_manage_ticket_system'][$key]);
			}
			$all_data['roles_can_manage_ticket_system'] = $all_data['roles_can_manage_ticket_system'] != null &&  
														  $all_data['roles_can_manage_ticket_system'][0] != "" && 
														  !empty($all_data['roles_can_manage_ticket_system']) ? $all_data['roles_can_manage_ticket_system'] : array('shop_manager', 'administrator');
			
			
			$all_data['display_user_ticket_area_in_my_account_page'] = get_field('wcsts_display_user_ticket_area_in_my_account_page', 'option');
			$all_data['display_user_ticket_area_in_my_account_page'] = $all_data['display_user_ticket_area_in_my_account_page'] ? $all_data['display_user_ticket_area_in_my_account_page'] : 'yes';
			
			$all_data['disable_user_ticket_opening'] = get_field('wcsts_disable_user_ticket_opening', 'option');
			$all_data['disable_user_ticket_opening'] = $all_data['disable_user_ticket_opening'] ? $all_data['disable_user_ticket_opening'] : false;
			
			$all_data['display_order_status_on_order_tickets'] = get_field('wcsts_display_order_status_on_order_tickets', 'option');
			$all_data['display_order_status_on_order_tickets'] = $all_data['display_order_status_on_order_tickets'] ? $all_data['display_order_status_on_order_tickets'] : false;
			
			$all_data['order_ticket_limit'] = get_field('wcsts_order_ticket_limit', 'option');
			$all_data['order_ticket_limit'] = isset($all_data['order_ticket_limit']) && is_numeric($all_data['order_ticket_limit']) ? $all_data['order_ticket_limit'] : -1;
			
			$all_data['order_details_page_smooth_scroll'] = get_field('wcsts_order_details_page_smooth_scroll', 'option');
			$all_data['order_details_page_smooth_scroll'] = isset($all_data['order_details_page_smooth_scroll']) ? $all_data['order_details_page_smooth_scroll'] : false;
			
			$all_data['disable_get_help_button'] = get_field('wcsts_disable_get_help_button', 'option');
			$all_data['disable_get_help_button'] = isset($all_data['disable_get_help_button']) ? $all_data['disable_get_help_button'] : false;
			
			$all_data['ticket_conversation_is_expansed'] = get_field('wcsts_ticket_conversation_is_expansed', 'option');
			$all_data['ticket_conversation_is_expansed'] = isset($all_data['ticket_conversation_is_expansed']) ? $all_data['ticket_conversation_is_expansed'] : false;
			
			$all_data['order_ticket_disable_user_reply_until_admin_message'] = get_field('wcsts_order_ticket_disable_user_reply_until_admin_message', 'option');
			$all_data['order_ticket_disable_user_reply_until_admin_message'] = isset($all_data['order_ticket_disable_user_reply_until_admin_message']) ? $all_data['order_ticket_disable_user_reply_until_admin_message'] : false;
			
			$all_data['user_ticket_disable_user_reply_until_admin_message'] = get_field('wcsts_user_ticket_disable_user_reply_until_admin_message', 'option');
			$all_data['user_ticket_disable_user_reply_until_admin_message'] = isset($all_data['user_ticket_disable_user_reply_until_admin_message']) ? $all_data['user_ticket_disable_user_reply_until_admin_message'] : false;
			
			$all_data['automatic_tickect_status_switch_from_closed_to_open'] = get_field('wcsts_automatic_tickect_status_switch_from_closed_to_open', 'option');
			$all_data['automatic_tickect_status_switch_from_closed_to_open'] = $all_data['automatic_tickect_status_switch_from_closed_to_open'] ? $all_data['automatic_tickect_status_switch_from_closed_to_open'] : false;
			
			$all_data['is_order_ticket_enabled'] = get_field('wcsts_is_order_ticket_enabled', 'option');
			$all_data['is_order_ticket_enabled'] = $all_data['is_order_ticket_enabled']  ? $all_data['is_order_ticket_enabled'] : true;
			$all_data['is_order_ticket_enabled'] = $all_data['is_order_ticket_enabled'] == 'yes' ? true : false;
			
			$all_data['is_pay_per_ticket_enabled'] = get_field('wcsts_is_pay_per_ticket_enabled', 'option');
			$all_data['is_pay_per_ticket_enabled'] = $all_data['is_pay_per_ticket_enabled']  ? $all_data['is_pay_per_ticket_enabled'] : true;
			$all_data['is_pay_per_ticket_enabled'] = $all_data['is_pay_per_ticket_enabled'] == 'yes' ? true : false;
			
			$all_data['display_ticket_number_column_on_my_accont_order_table'] = get_field('wcsts_display_ticket_number_column_on_my_accont_order_table', 'option');
			$all_data['display_ticket_number_column_on_my_accont_order_table'] = $all_data['display_ticket_number_column_on_my_accont_order_table']  ? $all_data['display_ticket_number_column_on_my_accont_order_table'] : false;
			
			$all_data['mark_ticket_as_closed_on_completed'] = get_field('wcsts_mark_ticket_as_closed_on_completed', 'option');
			$all_data['mark_ticket_as_closed_on_completed'] = $all_data['mark_ticket_as_closed_on_completed']  ? $all_data['mark_ticket_as_closed_on_completed'] : false;
			
			$all_data['order_ticket_area_position'] = get_field('wcsts_order_ticket_area_position', 'option');
			$all_data['order_ticket_area_position'] = $all_data['order_ticket_area_position']  ? $all_data['order_ticket_area_position'] : 'woocommerce_order_details_after_order_table';
			
			$all_data['order_ticket_system_disabled_order_statuses'] = get_field('wcsts_order_ticket_system_disabled_order_statuses', 'option');
			$all_data['order_ticket_system_disabled_order_statuses'] = $all_data['order_ticket_system_disabled_order_statuses'] ? $all_data['order_ticket_system_disabled_order_statuses'] : array();
			foreach((array)$all_data['order_ticket_system_disabled_order_statuses'] as $key => $item)
			{
				if($item == "")
					unset($all_data['order_ticket_system_disabled_order_statuses'][$key]);
			}
			
			//Subject types
			/*$all_data['order_ticket_subject_type'] = get_field('wcsts_order_ticket_subject_type', 'option'); 
			$all_data['order_ticket_subject_type'] = $all_data['order_ticket_subject_type'] != null ? $all_data['order_ticket_subject_type'] : 'text_input'; 
			$all_data['order_ticket_subject_topics'] = array();
			$counter = 0;
			if( have_rows('wcsts_order_ticket_subject_topics', 'option') )
				while ( have_rows('wcsts_order_ticket_subject_topics', 'option') ) 
				{
					the_row();
					$all_data['order_ticket_subject_topics'][$counter++] = get_sub_field('wcsts_topic', 'option');
				}
			
			$all_data['user_ticket_subject_type'] = get_field('wcsts_user_ticket_subject_type', 'option'); 
			$all_data['user_ticket_subject_type'] = $all_data['user_ticket_subject_type'] != null ? $all_data['user_ticket_subject_type'] : 'text_input'; 			
			$all_data['user_ticket_subject_topics'] = array();
			$counter = 0;
			if( have_rows('wcsts_user_ticket_subject_topics', 'option') )
				while ( have_rows('wcsts_user_ticket_subject_topics', 'option') ) 
				{
					the_row();
					$all_data['user_ticket_subject_topics'][$counter++] = get_sub_field('wcsts_topic', 'option');
				}
			*/
		
			//Attachments
			$all_data['allow_files_attachment'] = get_field('wcsts_allow_files_attachment', 'option'); 
			$all_data['allow_files_attachment'] = $all_data['allow_files_attachment'] != null ? $all_data['allow_files_attachment'] : false; 
			
			$all_data['num_of_uploadable_files'] = get_field('wcsts_num_of_uploadable_files', 'option'); 
			$all_data['num_of_uploadable_files'] = $all_data['num_of_uploadable_files'] != null ? $all_data['num_of_uploadable_files'] : 1; 
			
			$all_data['allowed_file_types'] = get_field('wcsts_allowed_file_types', 'option'); 
			$all_data['allowed_file_types'] = $all_data['allowed_file_types'] != null ? $all_data['allowed_file_types'] : ""; 
			
			$all_data['max_file_size'] = get_field('wcsts_max_file_size', 'option'); 
			$all_data['max_file_size'] = $all_data['max_file_size'] != null ? $all_data['max_file_size'] : 4096; 
			
			//Email notifications
			$all_data['user_email_notifications'] = get_field('wcsts_user_email_notifications', 'option'); 
			$all_data['user_email_notifications'] = $all_data['user_email_notifications'] != null ? $all_data['user_email_notifications'] : false; 
			
			$all_data['user_new_ticket_submission_notification'] = get_field('wcsts_user_new_ticket_submission_notification', 'option'); 
			$all_data['user_new_ticket_submission_notification'] = $all_data['user_new_ticket_submission_notification'] != null ? $all_data['user_new_ticket_submission_notification'] : false; 
			
			$all_data['user_reply_by_admin_notification'] = get_field('wcsts_user_reply_by_admin_notification', 'option'); 
			$all_data['user_reply_by_admin_notification'] = $all_data['user_reply_by_admin_notification'] != null ? $all_data['user_reply_by_admin_notification'] : false; 
			
			$all_data['admin_email_notifications'] = get_field('wcsts_admin_email_notifications', 'option'); 
			$all_data['admin_email_notifications'] = $all_data['admin_email_notifications'] != null ? $all_data['admin_email_notifications'] : false; 
			
			$all_data['admin_new_ticket_submission_notification'] = get_field('wcsts_admin_new_ticket_submission_notification', 'option'); 
			$all_data['admin_new_ticket_submission_notification'] = $all_data['admin_new_ticket_submission_notification'] != null ? $all_data['admin_new_ticket_submission_notification'] : false; 
			
			$all_data['admin_reply_by_user_notification'] = get_field('wcsts_admin_reply_by_user_notification', 'option'); 
			$all_data['admin_reply_by_user_notification'] = $all_data['admin_reply_by_user_notification'] != null ? $all_data['admin_reply_by_user_notification'] : false; 
			
			$all_data['admin_custom_email_recipiens'] = get_field('wcsts_admin_custom_email_recipiens', 'option'); 
			$all_data['admin_custom_email_recipiens'] = $all_data['admin_custom_email_recipiens'] != null ? $all_data['admin_custom_email_recipiens'] : ""; 
			
			remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
			$this->all_data = $all_data;
		}
		else
			$all_data = $this->all_data;
		if(isset($option_name))
			return isset($all_data[$option_name]) ? $all_data[$option_name] : $option_default_value;
		
		return $all_data;
	}
	function cl_acf_set_language() 
	{
	  return acf_get_setting('default_language');
	}
	function set_priorities_attributes($term_id, $term_meta)
	{
		update_option( "taxonomy_$term_id", $term_meta );
	}
	function get_priority_term_attributes($term_id)
	{
		return get_option( "taxonomy_$term_id");
	}
	function delete_priority_term_attributes($term_id)
	{
		return delete_option( "taxonomy_$term_id");
	}
}
?>