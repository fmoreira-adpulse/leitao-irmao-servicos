<?php 
class WCSTS_User
{
	var $current_user;
	var $current_user_id;
	public function __construct()
	{
		add_action('wp_ajax_wcsts_get_customers_list', array(&$this, 'ajax_get_customers_partial_list'));
		add_action('wp_ajax_wcsts_get_admins_list', array(&$this, 'ajax_get_admins_list'));
	}
	public function get_pp_meta($user_id)
	{
		$result = array();
		$customer = new WC_Customer($user_id);
		$all_meta = $customer->get_meta_data();
		
		if($all_meta)
			foreach((array)$all_meta as $single_meta)
				if(strpos($single_meta->key, 'wcsts_ppt_') !==false)
					$result[$single_meta->id] = $single_meta;
				
		return $result;
	}
	public function add_ppt_questions_number_meta($user_id, $params )
	{
		if($user_id == 0)
			return;
		
		$key = 'wcsts_ppt_'.$params['product_id'].'_'.$params['variation_id'];
		$this->add_meta($user_id, $key, $params);
	}
	public function get_user_name($user_id)
	{
		if(!isset($user_id))
			return "";
		
		$data = get_userdata($user_id);
		return !$data ? "" : $data->display_name;
	}
	public function get_first_available_admin_id()
	{
		$args = array(
				'role'    => 'administrator',
				'order'   => 'ASC'
			);
		$users = get_users( $args );
		
		if($users)
			return $users[0]->ID;
		
		return null;
	}
	public function get_associated_ticket_managers($user_id, $return_only_first = false)
	{
		$results = $this->get_meta($user_id, 'wcsts_associated_ticket_managers');
		$return = array();
		
		if($results && !empty($results))
			foreach($results as $result)
			{
				$data =  $result->get_data();
				if($data['value'])
				{
					if(is_array($data['value']))
					{
						foreach($data['value'] as $value)
							$return[] = $value;
					}
					else
						$return[] = $data['value'];
				}
			}
			
		if($return_only_first)
		{
			if(!empty($return))
				return $return[0];
			else 
				return $this->get_first_available_admin_id();
		}
		return $return;
	}
	public function set_associated_ticket_managers($user_id, $ids)
	{
		$this->add_meta($user_id, 'wcsts_associated_ticket_managers', $ids, true);
	}
	public function add_meta($user_id, $key, $value, $unique = false)
	{
		$customer = new WC_Customer($user_id);
		$customer->add_meta_data($key, $value, $unique);
		$customer->save_meta_data();
	}
	public function get_meta($user_id, $key, $single = false)
	{
		if(!isset($this->current_user_id) || $this->current_user_id != $user_id)
		{
			$this->current_user_id = $user_id;
			$this->current_user = new WC_Customer($user_id);
		}
		
		$result = $this->current_user->get_meta($key, $single);
		return $result;
	}
	public function update_ppt_question_numbers_metas_by_ids($user_id, $values)
	{
		if(!is_array($values) || empty($values))
			return;
		
		$customer = new WC_Customer($user_id);
		$all_ppt_meta = $this->get_pp_meta($user_id);
	
		foreach((array)$values as $value)
		{
			if(isset($all_ppt_meta[$value['id']]))
			{
				$current_value = $all_ppt_meta[$value['id']]->value;
				$current_value['questions_number'] = $value['questions_number'];
				
				$customer->update_meta_data( $all_ppt_meta[$value['id']]->key, $current_value, $value['id'] );
			}
			
		}
		$customer->save();
		
	}
	public function delete_ppt_meta_by_ids($user_id, $ids)
	{
		if(!is_array($ids) || empty($ids))
			return;
		
		$customer = new WC_Customer($user_id);
		foreach((array)$ids as $id)
			$customer->delete_meta_data_by_mid(  $id );
		$customer->save();
	}
	public function delete_ppt_meta_data_by_product_ids_and_date($user_id, $meta_to_delete)
	{
		if($user_id == 0)
			return;
		//Format: date -> product_id."_".variation_id
		
		$meta_ids_to_delete = array();
		foreach((array)$meta_to_delete as $product_unique_id  => $unique_date_id)
		{
			$results = $this->get_meta($user_id, WCSTS_Order::$ORDER_PPT_ORDER_KEY_PREFIX.$product_unique_id, false);
			
			foreach((array)$results as $meta_to_check)
			{
				if($meta_to_check->value['order_date'] == $unique_date_id)
					$meta_ids_to_delete[] = $meta_to_check->id;
			}
		}
		$this->delete_metas($user_id, $meta_ids_to_delete);
	}
	public function ajax_get_admins_list()
	{
		$this->ajax_get_customers_partial_list(true);
	}
	public function ajax_get_customers_partial_list($return_only_who_can_manage_tickets = false)
	{
		$resultCount = 15;
		$search_string = isset($_GET['search_string']) ? $_GET['search_string'] : null;
		$page = isset($_GET['page']) ? $_GET['page'] : null;
		$offset = isset($page) ? ($page - 1) * $resultCount : null;
		$customers = $this->get_customers_list($search_string ,$offset, $resultCount, $return_only_who_can_manage_tickets);
		echo json_encode( $customers); 
		wp_die();
	}
	public function get_customers_list($search_string ,$offset, $resultCount, $return_only_who_can_manage_tickets)
	{
		global $wpdb; 
		$join_manager_roles_additional_string = $where_manager_roles_additional_string = "";
		if($return_only_who_can_manage_tickets)
		{
			$manager_roles = $this->get_roles_that_can();
			$join_manager_roles_additional_string = $where_manager_roles_additional_string = "";
			
			if(count($manager_roles) > 0 )
			{
				$counter = 0;
				foreach((array)$manager_roles as $manager_role)
				{
					$where_manager_roles_additional_string .= $counter++ == 0 ? " ( " : " OR ";
					$where_manager_roles_additional_string .= " user_capabilities.meta_value LIKE '%".serialize($manager_role).serialize(true)."%' ";
				}
				$where_manager_roles_additional_string .= " ) ";
			}
			
		}
		$join_manager_roles_additional_string = " LEFT JOIN {$wpdb->usermeta} AS user_capabilities ON users.ID = user_capabilities.user_id AND user_capabilities.meta_key = '{$wpdb->prefix}capabilities'";
				
		$limit_query = isset($offset) && isset($resultCount) ? " LIMIT {$resultCount} OFFSET {$offset}": "";
		$additional_select = $additional_join = $additional_where = "";
		{
			
			
			$additional_join = " LEFT JOIN {$wpdb->usermeta} AS first_name_meta  ON first_name_meta.user_id = users.ID AND first_name_meta.meta_key = 'first_name'
								 LEFT JOIN {$wpdb->usermeta} AS last_name_meta  ON last_name_meta.user_id = users.ID AND last_name_meta.meta_key = 'last_name' 
								 LEFT JOIN {$wpdb->usermeta} AS billing_name_meta  ON billing_name_meta.user_id = users.ID  AND billing_name_meta.meta_key = 'billing_first_name' 
								 LEFT JOIN {$wpdb->usermeta} AS billing_last_name_meta  ON billing_last_name_meta.user_id = users.ID  AND billing_last_name_meta.meta_key = 'billing_last_name'
								 LEFT JOIN {$wpdb->usermeta} AS billing_email_meta  ON billing_email_meta.user_id = users.ID AND billing_email_meta.meta_key = 'billing_email'
								 ";
								 
		}
		 $query_string = "SELECT users.ID as ID, users.user_email as email, users.user_login as user_login, first_name_meta.meta_value as first_name, last_name_meta.meta_value as last_name, 
								 billing_name_meta.meta_value as billing_name, billing_last_name_meta.meta_value as billing_last_name, billing_email_meta.meta_value as billing_email, user_capabilities.meta_value as capabilies
							 FROM {$wpdb->users} AS users {$additional_join} {$join_manager_roles_additional_string} ";
							
		if($where_manager_roles_additional_string != "" || $additional_where != "")					
							 $query_string .=" WHERE {$where_manager_roles_additional_string} {$additional_where} ";
		
		if($search_string)
		{
			
			$offset = null;
			$limit_query = "";
			if($where_manager_roles_additional_string != "" || $additional_where != "")
				$query_string .= " AND ";
			else 
				$query_string .= " WHERE ";
			
			$query_string .=  " ( users.ID LIKE '%{$search_string}%' OR  
										  users.user_email LIKE '%{$search_string}%' OR 
										  users.user_login LIKE '%{$search_string}%' OR 
										  first_name_meta.meta_value LIKE '%{$search_string}%' OR
										  last_name_meta.meta_value LIKE '%{$search_string}%' OR
										  billing_name_meta.meta_value LIKE '%{$search_string}%' OR 
										  billing_last_name_meta.meta_value LIKE '%{$search_string}%' OR 
										  billing_email_meta.meta_value LIKE '%{$search_string}%'  
									  )";
		}
		$order_by =  " GROUP BY users.ID ORDER BY  users.ID ASC ".$limit_query ;
		$wpdb->query('SET SQL_BIG_SELECTS=1');
		$wpdb->query('SET MAX_JOIN_SIZE=99999999999999999');
		$results = $wpdb->get_results($query_string.$order_by );
		$bad_char = array('"', "'");
		
		
		if(isset($offset) && isset($resultCount))
		{
			$num_order = $wpdb->get_results($query_string );
			$num_order = isset($num_order) ? count($num_order) : 0;
			$endCount = $offset + $resultCount;
			$morePages = $num_order > $endCount;
			$results = array(
				  "results" => $results,
				  "pagination" => array(
					  "more" => $morePages
				  )
			  );
		}
		else
			$results = array(
				  "results" => $results,
				  "pagination" => array(
					  "more" => false
				  )
			  );
		
		return $results;
	}
	public function get_user_email_by_ids($users_ids, $return_string = true)
	{
		$recipient_managers = "";
		foreach((array)$users_ids as $manager_id)
		{
			$user_data = $this->get_user_data($manager_id);
			$recipient_managers .= $recipient_managers != "" ? ", ".$user_data->user_email : $user_data->user_email;
		}
		return $return_string ? $recipient_managers : explode(",",$recipient_managers);
	}
	public function get_user_data($user_id)
	{
		if($user_id)
		{
			$user = get_userdata( $user_id );
			return is_object($user) ? $user : false;
		}
				
		return false;
	}
	public function is_current_user_administrator()
	{
		global $current_user;
		if(in_array('administrator', $current_user->roles))
				return true;
			
		return false;
	}
	public function get_roles_that_can($capability = "edit_posts")
	{
		global $wcsts_option_helper;
		$result = isset($wcsts_option_helper) ? $wcsts_option_helper->get_all_options('roles_can_manage_ticket_system', array()) : array();
		return $result;
	}
	public function get_roles_that_cannot()
	{
		$can = $this->get_roles_that_can();
		$available = $this->get_available_roles(true);
		
		$result = array();
		
		return isset($available) && isset($can) ? array_diff($available, $can) : array();
	}
	public function get_available_roles($get_only_code = false) 
	{
		global $wp_roles;

		$all_roles = $wp_roles->roles;
		$editable_roles = apply_filters('editable_roles', $all_roles);
		if($get_only_code)
		{
			$result = array();
			foreach($editable_roles as $role_code => $role_stuff)
				$result[$role_code] = $role_code;
				
			$editable_roles = $result;
		}
		return $editable_roles;
	}
	public function current_users_belongs_to_roles($roles)
	{
		$roles = !isset($roles) || !is_array($roles) ? array() : $roles;
		
		if(!is_user_logged_in())
			return false;
		global $current_user;
		
		if(empty($roles) && in_array('shop_manager', $current_user->roles))
			return true;
		
		if(in_array('administrator', $current_user->roles))
				return true;
			
		foreach($roles as $role)
			if(in_array($role, $current_user->roles))
				return true;
			
		return false;	
	}
	public function assign_ticket_id_to_user($ticket_id, $user_id)
	{
		global $wpdb;
		$query = " SELECT * 
				   FROM {$wpdb->usermeta} AS user_meta
				   WHERE user_meta.meta_key = 'wcsts_ticket_id'
				   AND user_meta.meta_value = '{$ticket_id}' ";
				   
		$users = $wpdb->get_results($query);
		//reset
		foreach((array)$users as $user)
			delete_user_meta($user->user_id, 'wcsts_ticket_id', $ticket_id);
		
		//assign ticket id to the new one	
		$result = add_user_meta($user_id, 'wcsts_ticket_id', $ticket_id, false);
	}
	public function get_ticket_ids_by_user_id_and_type($user_id, $type = 'user')
	{
		global $wcsts_ticket_message_model,$wpdb;
		$query = " SELECT user_meta.meta_value AS ticket_id, tickets.post_status
				   FROM {$wpdb->usermeta} AS user_meta
				   INNER JOIN {$wpdb->posts} AS tickets ON tickets.ID = user_meta.meta_value
				   INNER JOIN {$wpdb->postmeta} AS ticketmeta ON ticketmeta.post_id = tickets.ID
				   WHERE user_meta.meta_key = 'wcsts_ticket_id'
				   AND tickets.post_status = 'publish'
				   AND user_meta.user_id = '{$user_id}' 
				   AND ticketmeta.meta_key = 'wcsts_ticket_type' 
				   AND ticketmeta.meta_value = '{$type}' 
				   ";
		$wpdb->query('SET SQL_BIG_SELECTS=1');
		$wpdb->query('SET MAX_JOIN_SIZE=99999999999999999');
		return $wpdb->get_results($query);
	}
	public function delete_tickets_assigned_to_user($ticket_id)
	{
		global $wpdb;
		$query = " DELETE FROM {$wpdb->usermeta}  
				   WHERE meta_key = 'wcsts_ticket_id' 
				   AND meta_value = '{$ticket_id}' ";
	
		return $wpdb->get_results($query);
	}
}
?>