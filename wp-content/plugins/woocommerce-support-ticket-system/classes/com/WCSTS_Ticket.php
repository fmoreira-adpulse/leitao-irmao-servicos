<?php

use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class WCSTS_Ticket
{
	var $all_options = array();
	public function __construct()
	{
		add_action('init', array(&$this, 'register_custom_post_type'), 0);

		//On delete:
		// -- delete all the associated wcsts_ticket_message 
		add_action('before_delete_post', array(&$this, 'before_delete_ticket'), 10);

		add_action('wp_ajax_wcsts_submit_new_message', array(&$this, 'ajax_add_new_message'));
		add_action('wp_ajax_nopriv_wcsts_submit_new_message', array(&$this, 'ajax_add_new_message'));
		add_action('wp_ajax_wcsts_delete_message', array(&$this, 'ajax_delete_message'));
		add_action('wp_ajax_wcsts_submit_new_ticket', array(&$this, 'ajax_open_new_ticket'));
		add_action('wp_ajax_nopriv_wcsts_submit_new_ticket', array(&$this, 'ajax_open_new_ticket'));
		add_action('wp_ajax_wcsts_delete_attachment', array(&$this, 'ajax_wcsts_delete_attachment'));
		add_action('wp_ajax_wcsts_get_tickets_list', array(&$this, 'ajax_wcsts_get_tickets_list'));
		add_action('wp_ajax_wcsts_reset_admin_unread_messages_counter', array(&$this, 'ajax_reset_admin_unread_messages_counter'));

		add_action('admin_init',  array(&$this, 'add_custom_capabilities'));

		//
		//old add_action( 'save_post', array( &$this, 'update_ticket_associated_user_on_order_update' ), 999, 2 );
		add_action('woocommerce_process_shop_order_meta', array(&$this, 'update_ticket_associated_user_on_order_update'), 999, 2);
		//add_action( 'publish_wcsts_ticket', array( &$this, 'on_admin_ticket_creation' ), 10, 2 );
		//add_action( 'draft_to_publish', array( &$this, 'on_admin_ticket_creation' ));
	}
	function add_custom_capabilities()
	{
		global $wcsts_user_model;
		//$roles = array('administrator','shop_manager');
		$capabilities = array(
			'edit_wcsts_ticket',
			'edit_wcsts_tickets',
			'publish_wcsts_tickets',
			'read_wcsts_ticket',
			'read_private_wcsts_tickets',
			'delete_wcsts_tickets',
			'delete_wcsts_ticket',
			'create_wcsts_tickets',
			'edit_others_wcsts_tickets'
		);
		$roles = $wcsts_user_model->get_roles_that_can();
		$roles_that_can_not = $wcsts_user_model->get_roles_that_cannot();

		foreach ($roles as $role) {
			$admin = get_role($role);

			if (isset($admin)) {
				//$admin->add_cap( 'manage_wcsts_ticket' );
				foreach ($capabilities as $cap)
					$admin->add_cap($cap);
			}
		}
		foreach ($roles_that_can_not as $role) {
			$not_admin = get_role($role);
			if (isset($not_admin))
				foreach ($capabilities as $cap)
					$not_admin->remove_cap($cap);
		}
	}
	function register_custom_post_type()
	{
		global $wcsts_option_helper, $wcsts_user_model;
		$role_who_can_manage = isset($wcsts_option_helper) ? $wcsts_option_helper->get_all_options('roles_can_manage_ticket_system', array()) : array();

		$is_autorized_user = $wcsts_user_model->current_users_belongs_to_roles($role_who_can_manage);

		$labels = array(
			'name'                => _x('Support Ticket', 'Ticket', 'woocommerce-support-ticket-system'),
			'singular_name'       => _x('Support Ticket', 'Ticket', 'woocommerce-support-ticket-system'),
			'menu_name'           => esc_html__('WooCommerce Support Tickets', 'woocommerce-support-ticket-system'),
			'name_admin_bar'      => esc_html__('WooCommerce Support Tickets', 'woocommerce-support-ticket-system'),
			'parent_item_colon'   => esc_html__('Parent Item:', 'woocommerce-support-ticket-system'),
			'all_items'           => esc_html__('All Tickets', 'woocommerce-support-ticket-system'),
			'add_new_item'        => esc_html__('Add Ticket', 'woocommerce-support-ticket-system'),
			'add_new'             => esc_html__('Add Ticket', 'woocommerce-support-ticket-system'),
			'new_item'            => esc_html__('New Ticket', 'woocommerce-support-ticket-system'),
			'edit_item'           => esc_html__('Edit Ticket', 'woocommerce-support-ticket-system'),
			'update_item'         => esc_html__('Update Ticket', 'woocommerce-support-ticket-system'),
			'view_item'           => esc_html__('View Ticket', 'woocommerce-support-ticket-system'),
			'search_items'        => esc_html__('Search Tickets', 'woocommerce-support-ticket-system'),
			'not_found'           => esc_html__('Not found', 'woocommerce-support-ticket-system'),
			'not_found_in_trash'  => esc_html__('Not found in Trash', 'woocommerce-support-ticket-system'),
		);
		$args = array(
			'label'               => esc_html__('WooCommerce Support Tickets', 'woocommerce-support-ticket-system'),
			'description'         => esc_html__('WooCommerce Support Ticket System', 'woocommerce-support-ticket-system'),
			'labels'              => $labels,
			//'menu_icon'           => 'dashicons-media-document',
			'menu_icon'           => WCSTS_PLUGIN_PATH . '/images/icon.png',
			/*  'supports'            =>  array('author'),  */
			'taxonomies'          => array( /*'category' , 'post_tag' */),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,                                      //'woocommerce': rendered in WooCommerce menu
			'show_in_menu'        => $is_autorized_user /* current_user_can( 'manage_woocommerce' ) ? true : false */,
			/* 'menu_position'       => wcsts_get_free_menu_position(5), */
			'show_in_admin_bar'   => $is_autorized_user,
			'show_in_nav_menus'   => $is_autorized_user,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			//'map_meta_cap'		  => true,
			//'capability_type'     => array('wcsts_manage_ticket', 'wcsts_manage_tickets'),/* 'shop_order' */  ////review-> shop_order raises error
			'capabilities' => array(
				'edit_post' => 'edit_wcsts_ticket',
				'edit_posts' => 'edit_wcsts_tickets',
				//'edit_others_posts' => 'edit_wcsts_tickets',
				'publish_posts' => 'publish_wcsts_tickets',
				'read_post' => 'read_wcsts_ticket',
				'read_private_posts' => 'read_private_wcsts_tickets',
				'delete_posts' => 'delete_wcsts_tickets',
				'delete_post' => 'delete_wcsts_ticket',
				'create_posts'       => 'create_wcsts_tickets',
				'edit_others_posts'  => 'edit_others_wcsts_tickets',
			),
			/* 'rewrite'             => array( 'slug' => 'wppas_photo' ) */
		);
		register_post_type('wcsts_ticket', $args);
		remove_post_type_support('wcsts_ticket', 'title');
		remove_post_type_support('wcsts_ticket', 'editor');
		flush_rewrite_rules();

		//Custom taxonomy
		$labels = array(
			'name'              => _x('Status', 'woocommerce-support-ticket-system'),
			'singular_name'     => _x('Status', 'woocommerce-support-ticket-system'),
			'search_items'      => esc_html__('Search Status', 'woocommerce-support-ticket-system'),
			'all_items'         => esc_html__('All Statuses', 'woocommerce-support-ticket-system'),
			'parent_item'       => esc_html__('Parent Status', 'woocommerce-support-ticket-system'),
			'parent_item_colon' => esc_html__('Parent Status:'),
			'edit_item'         => esc_html__('Edit Status', 'woocommerce-support-ticket-system'),
			'update_item'       => esc_html__('Update Status', 'woocommerce-support-ticket-system'),
			'add_new_item'      => esc_html__('Add New Status', 'woocommerce-support-ticket-system'),
			'new_item_name'     => esc_html__('New Status Name', 'woocommerce-support-ticket-system'),
			'menu_name'         => esc_html__('Statuses', 'woocommerce-support-ticket-system'),
		);
		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => $is_autorized_user,
			'show_admin_column' => $is_autorized_user,
			'query_var'         => true,
			'rewrite'           => array('slug' => 'wcsts_ticket_status'),
		);
		//register_taxonomy( 'wcsts_ticket_status', array( 'wcsts_ticket' ), $args );

		//Custom taxonomy
		$labels = array(
			'name'              => _x('Priority', 'woocommerce-support-ticket-system'),
			'singular_name'     => _x('Priority', 'woocommerce-support-ticket-system'),
			'search_items'      => esc_html__('Search Priority', 'woocommerce-support-ticket-system'),
			'all_items'         => esc_html__('All Priorities', 'woocommerce-support-ticket-system'),
			'parent_item'       => esc_html__('Parent Priority', 'woocommerce-support-ticket-system'),
			'parent_item_colon' => esc_html__('Parent Priority:'),
			'edit_item'         => esc_html__('Edit Priority', 'woocommerce-support-ticket-system'),
			'update_item'       => esc_html__('Update Priority', 'woocommerce-support-ticket-system'),
			'add_new_item'      => esc_html__('Add New Priority', 'woocommerce-support-ticket-system'),
			'new_item_name'     => esc_html__('New Priority Name', 'woocommerce-support-ticket-system'),
			'menu_name'         => esc_html__('Priorities', 'woocommerce-support-ticket-system'),
		);
		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array('slug' => 'wcsts_ticket_priority'),
		);
		register_taxonomy('wcsts_ticket_priority', array('wcsts_ticket'), $args);
	}
	//the ones with open status;
	public function count_new_tickets()
	{
		global $wcsts_option_helper, $wcsts_user_model;

		$ticket_visibility = $wcsts_option_helper->get_all_options('ticket_visibility', 'all_tickets');
		/* $count_posts = wp_count_posts('wcsts_ticket');
		 
		if(isset($count_posts->publish))
			foreach( */
		$result = 0;
		$args = array(
			'post_status'   => 'publish',
			'post_type'     => 'wcsts_ticket',
			'fields' => 'ids',
			'meta_query'	=> array(
				//'relation'		=> 'AND',
				array(
					'key'	 	=> 'wcst_new_messages_counter',
					'value'	  	=> 0,
					'compare' 	=> '>',
				)/*,
						 array(
							'key'	  	=> 'featured',
							'value'	  	=> '1',
							'compare' 	=> '=',
						), */
			),
		);


		if (!$wcsts_user_model->is_current_user_administrator() && $ticket_visibility != 'all_tickets') {
			//wcsts_var_dump($args);
			$args['meta_query']['relation'] = 'AND';
			$args['meta_query'][] =
				array(
					'relation' => 'OR',
					array(
						'key' => 'wcsts_manager_user_id',
						'compare' => '=',
						'value' => get_current_user_id() //assigned 
					),
					array(
						'key' => 'wcsts_manager_user_id',
						'compare' => 'NOT EXISTS',
						'value' => ''
					),
					array(
						'key' => 'wcsts_manager_user_id',
						'compare' => '=',
						'value' => null
					)
				);
			//wcsts_var_dump($args);  
		}
		$ticket_ids = get_posts($args); //num of post with new messages
		foreach ((array)$ticket_ids as $ticket_id) {
			//wcsts_var_dump($ticket_id);
			//wcsts_var_dump($this->count_new_messages($ticket_id));
			$result += $this->count_new_messages($ticket_id);
		}
		//return is_array($ticket_ids) ? count($ticket_ids) : 0; 
		return $result;
	}
	public function get_order_ticket_number($order_id)
	{
		global $wcsts_order_model;
		$result = $wcsts_order_model->get_ticket_ids_by_order_id($order_id);
		$result = $result && is_array($result) ? count($result) : 0;

		if ($result == 0) {
			$result = $wcsts_order_model->get_ppt_ticket_ids_by_order_id($order_id);
			$result = $result && is_array($result) ? count($result) : 0;
		}
		return $result;
	}
	public function count_new_messages($ticket_id)
	{
		$result =  get_post_meta($ticket_id, 'wcst_new_messages_counter', true);
		return $result ? $result : 0;
	}
	public function count_total_messages($ticket_id)
	{
		$args = array(
			'posts_per_page'   => -1,
			'category'         => '',
			'category_name'    => '',
			'post_type'        => 'wcsts_ticket_message',
			'post_parent'      =>  $ticket_id,
			'post_status'      => 'publish',
			'suppress_filters' => true,
			'fields'
		);
		$messages = get_posts($args);
		return isset($messages) && is_array($messages) ? count($messages) : 0;
	}
	public function delete_ticket($ticket_id, $force_delete = true)
	{
		wp_delete_post($ticket_id, $force_delete);
	}
	public function reset_new_messages_counter($ticket_id)
	{
		delete_post_meta($ticket_id, 'wcst_new_messages_counter');
		update_post_meta($ticket_id, 'wcst_new_messages_counter', 0, true);
	}
	public function update_new_messages_counter($ticket_id, $new_messages_counter = 1)
	{
		$old_value = get_post_meta($ticket_id, 'wcst_new_messages_counter', true);
		$new_value = isset($old_value) && is_numeric($old_value) ? $new_messages_counter + $old_value : $new_messages_counter;
		update_post_meta($ticket_id, 'wcst_new_messages_counter', $new_value, $old_value);
	}
	// Updated on the add_reply() method defined on TicketMessage model
	public function update_new_admin_messages($ticket_id, $new_messages_counter = 1)
	{
		$old_value = get_post_meta($ticket_id, 'wcst_new_admin_messages_counter', true);
		$new_value = isset($old_value) && is_numeric($old_value) ? $new_messages_counter + $old_value : $new_messages_counter;
		update_post_meta($ticket_id, 'wcst_new_admin_messages_counter', $new_value, $old_value);
	}
	public function reset_new_admin_messages_counter($ticket_id)
	{
		delete_post_meta($ticket_id, 'wcst_new_admin_messages_counter');
		update_post_meta($ticket_id, 'wcst_new_admin_messages_counter', 0, true);
	}
	public function count_new_admin_messages($ticket_id)
	{
		$result =  get_post_meta($ticket_id, 'wcst_new_admin_messages_counter', true);
		return $result ? $result : 0;
	}
	public function count_total_new_admin_messages_per_type($id = null, $ticket_type = "user") //order, user, ppt
	{
		$id = !$id ? get_current_user_id() : $id;
		if (!$id)
			return 0;

		if ($ticket_type == 'user' || $ticket_type == 'order') {
			$messages_by_ticket = $ticket_type == 'user' ? $this->get_ticket_messages_by_user_id_and_type($id, 'asc', $ticket_type) : $this->get_ticket_messages_by_order_id($id, 'asc');
		} else //ppt
		{
			$messages_by_ticket = $this->get_ppt_ticket_messages_by_order_id($id, 'asc');
		}
		$admin_total_replies =  0;

		foreach ((array)$messages_by_ticket as $ticket_id => $messages)
			$admin_total_replies += $this->count_new_admin_messages($ticket_id);

		return $admin_total_replies;
	}
	public function get_who_replied_latest($ticket_id)
	{
		global $wcsts_ticket_message_model;
		$result = $wcsts_ticket_message_model->get_messages_by_ticket_id($ticket_id);
		$who = 'N/A';
		if (isset($result) && !empty($result)) {
			$last = end($result);
			$who = $last->is_customer_message ? 'customer' : 'staff';
		}
		//wcsts_var_dump($result);
		return $who;
	}
	public function ajax_wcsts_get_tickets_list()
	{
		$resultCount = 50;
		$search_string = isset($_GET['search_string']) ? $_GET['search_string'] : null;
		$page = isset($_GET['page']) ? $_GET['page'] : null;
		$offset = isset($page) ? ($page - 1) * $resultCount : null;
		$tickets = $this->get_tickets_list($search_string, $offset, $resultCount);
		echo json_encode($tickets);
		wp_die();
	}
	public function ajax_reset_admin_unread_messages_counter()
	{
		$ticket_id = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : null;
		if ($ticket_id != null) {
			$this->reset_new_admin_messages_counter($ticket_id);
		}
		wp_die();
	}
	public function ajax_open_new_ticket()
	{
		if (!wp_verify_nonce(wcsts_get_value_if_set($_POST, 'security', ""), 'wcsts_security'))
			wp_die();

		$this->open_new_ticket(true);
	}
	public function get_ticket_types()
	{
		return array('order' => esc_html__('Order', 'woocommerce-support-ticket-system'), 'user' => esc_html__('User', 'woocommerce-support-ticket-system'), 'ppt' => esc_html__('Pay per ticket', 'woocommerce-support-ticket-system'));
	}
	public function get_latest_message($ticket_id)
	{
		global $wcsts_ticket_message_model;

		$messages = $wcsts_ticket_message_model->get_messages_by_ticket_id($ticket_id);
		if (empty($messages))
			return array();

		$last_message = array();
		foreach ($messages as $message) {
			$message->is_customer_message == 'customer';
			$last_message = $message;
		}

		return end($messages);
	}
	public function get_latest_ticket($ticket_type, $user_id, $order)
	{
		//The plugin checks if alreay exists a order/ppt ticket (for curernt order) that has already opened withing the time interveal
		switch ($ticket_type) {
			case 'order':
				$ticket_type_metakey = 'wcsts_associated_order';
				$ticket_type_metavalue = WCSTS_Order::get_id($order);
				break;
			case '':
				$ticket_type_metakey = 'wcsts_associated_user';
				$ticket_type_metavalue = $user_id;
				break;
			case '':
				$ticket_type_metakey = 'wcsts_ppt_order_id';
				$ticket_type_metavalue =  $additional_params['order_id'];
				break;
		}
		$args = array(
			'post_status'   => 'publish',
			'post_author'   => $user_id,
			'post_type'     => 'wcsts_ticket',
			'numberposts' => 1,
			'meta_query'	=> array(
				'relation' => 'AND',
				array(
					'key'	 	=> 'wcsts_ticket_type',
					'value'	  	=> $ticket_type,
					'compare' 	=> '=',
				),
				array(
					'key'	 	=> $ticket_type_metakey,
					'value'	  	=> $ticket_type_metavalue,
					'compare' 	=> '=',
				),
			)
		);
		$result = get_posts($args);
		return $result ? $result[0] : array();
	}
	public function open_new_ticket($is_ajax = true, $subject = '', $message = '', $type = 'order', $order = null, $current_user_id = null, $additional_params = array())
	{
		global $wcsts_ticket_message_model, $wcsts_frontend_order_details_page_addon,
			$wcsts_order_model, $wcsts_file_model, $wcsts_email_model, $wcsts_html_helper,
			$wcsts_user_model, $wcsts_option_helper, $wcsts_time_model;
		$ticket_id = 0;
		$automatic_ticket_options = $wcsts_option_helper->get_automatic_ticket_options();


		if ($is_ajax) {
			$subject = isset($_POST['subject']) ? wp_strip_all_tags($_POST['subject']) : null;
			$message = isset($_POST['message']) ? wcsts_remove_script_tag($_POST['message']) : null;
			$type = isset($_POST['type']) ? $_POST['type'] : null;
			$priority = isset($_POST['priority']) ? $_POST['priority'] : null;
			$order = isset($_POST['type_id']) && isset($type) && $type == 'order' ? wc_get_order($_POST['type_id']) : null; //it is the user/order id

		}

		$user_id = isset($current_user_id) ? $current_user_id : get_current_user_id();

		//Post by time check
		$seconds = $wcsts_option_helper->get_post_time_restriction_options();
		$seconds = $seconds['new_ticket_post_time_interval'];
		if ($is_ajax && $seconds > 0) {
			$result = $this->get_latest_ticket($type, $user_id, $order);
			if ($result && !$wcsts_time_model->can_be_posted($result->post_date, $seconds)) {
				$extra_params = array('ticket_post_time_error' => true, 'ticket_post_time_interval' => $seconds);
				if ($type == 'order') {
					$extra_params['allow_guest'] = true;
					$wcsts_html_helper->frontend_ticket_area($order, true, true, $extra_params);
				} else
					$wcsts_html_helper->frontend_ticket_area(null, true, true, $extra_params);

				wp_die();
			}
		}
		if (isset($subject) && isset($message) && isset($type) && (($type == 'order' && isset($order)) || ($type != 'order'))) {
			$current_user = $type == 'order' ? $order->get_user() : new WP_User($user_id);
			$args = array(
				'post_status'   => 'publish',
				'post_author'   => wcsts_get_value_if_set($additional_params, 'post_author', $user_id),
				'post_type'     => 'wcsts_ticket',
			);
			$ticket_id = wp_insert_post($args);

			//Agents management 
			$managers_ids = $wcsts_user_model->get_associated_ticket_managers($user_id);

			// Adiciona o criador da ordem (post_author) como manager do ticket
			if ($type == 'order' && isset($order)) {
				$order_post = get_post(WCSTS_Order::get_id($order));
				if ($order_post && !empty($order_post->post_author)) {
					$order_creator_id = (int)$order_post->post_author;
					if (!in_array($order_creator_id, (array)$managers_ids)) {
						$managers_ids[] = $order_creator_id;
					}
				}
			}

			$this->assign_manager_users_to_tickets($ticket_id, $managers_ids);

			//attrtibutes
			$this->set_attribute($ticket_id, 'open_status_date', current_time('F j, Y g:i a'));
			$this->set_status($ticket_id, 'open');
			if ($type == 'order') {
				$this->set_attribute($ticket_id, 'associated_order', WCSTS_Order::get_id($order));
				$this->set_attribute($ticket_id, 'associated_user', $order->get_user_id()); //new: this is used to filter ticket by user
			} else
				$this->set_attribute($ticket_id, 'associated_user', $user_id);

			$this->set_attribute($ticket_id, 'subject', $subject);

			//Type 
			$this->set_attribute($ticket_id, 'ticket_type', $type);

			//Is an automatic ticket?
			$this->set_attribute($ticket_id, 'was_automatically_opened', !$is_ajax);

			if ($type == 'ppt') {
				$this->set_attribute($ticket_id, 'number_of_questions_left', $additional_params['questions_number']);
				$this->set_attribute($ticket_id, 'ppt_order_id', $additional_params['order_id']);
				$this->set_attribute($ticket_id, 'ppt_product_name', $additional_params['product_name']);
				$this->set_attribute($ticket_id, 'ppt_product_id', $additional_params['product_id'] . "_" . $additional_params['variation_id']);
			}

			//associating order to ticket 
			if ($type == 'order') {
				$wcsts_order_model->assign_ticket_id_to_order($ticket_id, WCSTS_Order::get_id($order));
			} else
				$wcsts_user_model->assign_ticket_id_to_user($ticket_id, $user_id);

			//Priority 
			if (isset($priority))
				$this->set_priority($ticket_id, $priority);

			//Message 
			$message_id = $message != '' ? $wcsts_ticket_message_model->add_reply($ticket_id, $message, wcsts_get_value_if_set($additional_params, 'is_automatically_opened_ticket', false) ? false : true, wcsts_get_value_if_set($additional_params, 'post_author', null)) : null;
			$this->update_modified_date($ticket_id);

			//Notification 
			if ($is_ajax) //Any message is opened via ajax. If not, it means that is "manually" opened on order status change (so it is an automatic ticket)
			{

				//Admin notification
				$overriden_recipients = $type == 'order' || $type == 'user' ? $this->get_topic_recipients($ticket_id, $type) : "";
				$wcsts_email_model->send_new_ticket_notification_to_admin($ticket_id, $message, $overriden_recipients);
				//User notification
				if ($current_user)
					$wcsts_email_model->send_new_ticket_notification_to_user($ticket_id, $message, $current_user, $type, $order);
			}
			//Automatic ticket by default is disable for automatically opened ticket
			elseif ($current_user && wcsts_get_value_if_set($additional_params, 'is_automatically_opened_ticket', false) && $automatic_ticket_options['automatic_ticket_enable_first_message_notification']) {
				//User notification
				//This is now disabled, the tiicket is now opened as Admin. Enable to send notification to user: $wcsts_email_model->send_new_ticket_notification_to_user($ticket_id, $message, $current_user,$type, $order);
				$wcsts_email_model->send_reply_notification_to_user($ticket_id, $message, $type);
			}

			//Attachments
			if (isset($_POST['wcsts_files']) && is_numeric($message_id))
				$wcsts_file_model->save_uploaded_files($_POST['wcsts_files'], $ticket_id, $message_id);

			//update new message counter
			$this->update_new_messages_counter($ticket_id, 1);

			//Html
			if ($is_ajax) {
				if ($type == 'order')
					$wcsts_html_helper->frontend_ticket_area($order, true, true, array("allow_guest" => true));
				else
					$wcsts_html_helper->frontend_ticket_area(null, true);
			}
		} else
			esc_html_e('Error', 'woocommerce-support-ticket-system');

		if ($is_ajax)
			wp_die();

		return $ticket_id;
	}
	public function ajax_add_new_message()
	{
		if (!wp_verify_nonce(wcsts_get_value_if_set($_POST, 'security', ""), 'wcsts_security'))
			wp_die();

		global $wcsts_ticket_message_model, $wcsts_frontend_order_details_page_addon, $wcsts_file_model, $wcsts_email_model, $wcsts_html_helper, $wcsts_option_helper, $wcsts_time_model;
		$ticket_id = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : null;
		$message = isset($_POST['message']) ? wcsts_remove_script_tag($_POST['message']) : null;
		$type = isset($_POST['type']) ? $_POST['type'] : null;
		$list_all_ppt_tickets_of_the_current_user = isset($_POST['list_all_ppt_tickets_of_the_current_user']) && $_POST['list_all_ppt_tickets_of_the_current_user']  == 'yes' ? true : false;
		$order = isset($_POST['type_id']) && isset($type) && ($type == 'order' || ($type == 'ppt' && !$list_all_ppt_tickets_of_the_current_user)) ? wc_get_order($_POST['type_id']) : null;
		$user_id = isset($_POST['type_id']) && isset($type) && $type == 'user' ? $_POST['type_id'] : null;
		$options = $wcsts_option_helper->get_all_options();

		//Post by time check
		$seconds = $wcsts_option_helper->get_post_time_restriction_options();
		$seconds = $seconds['new_ticket_message_post_time_interval'];
		if ($seconds > 0) {
			$result = $this->get_latest_message($ticket_id);
			if ($result && !$wcsts_time_model->can_be_posted($result->post_date, $seconds)) {
				$extra_params = array('ticket_message_post_time_error' => true, 'ticket_message_post_time_interval' => $seconds, 'allow_guest' => true);
				if ($type == 'order')
					$wcsts_html_helper->frontend_ticket_area($order, true, true, $extra_params);
				else if ($type == 'user') {
					$extra_params['allow_guest'] = false;
					$wcsts_html_helper->frontend_ticket_area(null, true, true, $extra_params);
				} else
					$wcsts_html_helper->frontend_ticket_area($order, true, true, array(
						'list_all_ppt_tickets_of_the_current_user' => $list_all_ppt_tickets_of_the_current_user,
						'ticket_message_post_time_error' => true,
						'ticket_message_post_time_interval' => $seconds,
						'allow_guest' => true
					));

				wp_die();
			}
		}

		if (isset($ticket_id) && isset($message) && isset($type) && (($type == 'order' && isset($order)) || ($type == 'user' && isset($user_id)) || $type == 'ppt')) {
			//Message 
			$message_id = $wcsts_ticket_message_model->add_reply($ticket_id, $message, true);

			//Notification
			$overriden_recipients = $type == 'order' || $type == 'user' ? $this->get_topic_recipients($ticket_id, $type) : "";
			$wcsts_email_model->send_reply_notification_to_admin($ticket_id, $message, $overriden_recipients);

			//Date
			$this->update_modified_date($ticket_id);

			//Counter
			$this->update_new_messages_counter($ticket_id, 1);

			if (isset($_POST['wcsts_files']) && is_numeric($message_id))
				$wcsts_file_model->save_uploaded_files($_POST['wcsts_files'], $ticket_id, $message_id);
			//PPT
			if ($type == 'ppt') {
				$number_of_questions_left = $this->ppt_decrease_question_left_counter($ticket_id);
				if ($number_of_questions_left == 0)
					$this->set_status($ticket_id, 'closed');
			}
			//Status switch
			if (($status_to_switch = $this->get_status_to_which_automatically_swith_in_case_of_reply($ticket_id)) != false) {
				$this->set_status($ticket_id, $status_to_switch);
			}

			//HTML
			if ($type == 'order')
				$wcsts_html_helper->frontend_ticket_area($order, true, true,  array('allow_guest' => true));
			else if ($type == 'user')
				$wcsts_html_helper->frontend_ticket_area(null, true);
			else
				$wcsts_html_helper->frontend_ticket_area($order, true, true, array('list_all_ppt_tickets_of_the_current_user' => $list_all_ppt_tickets_of_the_current_user, 'allow_guest' => true));
		} else
			esc_html_e('Error', 'woocommerce-support-ticket-system');
		wp_die();
	}
	public function ajax_delete_message()
	{
		global $wcsts_ticket_message_model;
		$ticket_message_id = isset($_POST['ticket_message_id']) ? $_POST['ticket_message_id'] : null;
		if (isset($ticket_message_id)) {
			$wcsts_ticket_message_model->delete($ticket_message_id);
		}
		wp_die();
	}
	public function ajax_wcsts_delete_attachment()
	{
		global $wcsts_file_model, $wcsts_ticket_message_model;
		$attachment_unique_value = isset($_POST['attachment_unique_value']) ? $_POST['attachment_unique_value'] : null;
		$message_id = isset($_POST['message_id']) ? $_POST['message_id'] : null;
		if (isset($message_id) && isset($attachment_unique_value)) {
			$wcsts_ticket_message_model->delete_attachment($message_id, $attachment_unique_value);
		}
		wp_die();
	}

	public function get_tickets_list($search_string, $offset, $resultCount)
	{
		global $wpdb;
		$limit_query = isset($offset) && isset($resultCount) ? " LIMIT {$resultCount} OFFSET {$offset}" : "";
		$query_string = "SELECT tickets.ID, ticket_status.meta_value as ticket_status, ticket_open_date.meta_value as ticket_open_date, ticket_type.meta_value as ticket_type
							 FROM {$wpdb->posts} AS tickets 
							 INNER JOIN {$wpdb->postmeta} AS ticket_status ON ticket_status.post_id = tickets.ID 
							 INNER JOIN {$wpdb->postmeta} AS ticket_open_date ON ticket_open_date.post_id = tickets.ID 
							 INNER JOIN {$wpdb->postmeta} AS ticket_type ON ticket_type.post_id = tickets.ID 
							 WHERE tickets.post_type = 'wcsts_ticket' AND 
							 ticket_status.meta_key = 'wcsts_status'  AND 
							 ticket_open_date.meta_key = 'wcsts_open_status_date'  AND 
							 ticket_type.meta_key = 'wcsts_ticket_type' 
							 ";

		if ($search_string) {
			$offset = null;
			$limit_query = "";
			$query_string .=  " AND ( tickets.ID LIKE '%{$search_string}%'   
									  )";
		}
		$query_string .=  " GROUP BY tickets.ID ORDER BY tickets.ID ASC " . $limit_query;
		$wpdb->query('SET MAX_JOIN_SIZE=99999999999999999');
		$wpdb->query('SET SQL_BIG_SELECTS=1');
		$results = $wpdb->get_results($query_string);

		if (isset($offset) && isset($resultCount)) {
			$query_string = "SELECT COUNT(*) as tot
							 FROM {$wpdb->posts} AS tickets
							 WHERE tickets.post_type = 'wcsts_ticket' ";
			$num_order = $wpdb->get_col($query_string);
			$num_order = isset($num_order[0]) ? $num_order[0] : 0;
			$endCount = $offset + $resultCount;
			$morePages = $num_order > $endCount;
			$results = array(
				"results" => $results,
				"pagination" => array(
					"more" => $morePages
				)
			);
		} else
			$results = array(
				"results" => $results,
				"pagination" => array(
					"more" => $false
				)
			);

		return $results;
	}
	public function update_ticket_associated_user_on_order_update($order_id, $post)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;
		// AJAX? Not used here
		if (defined('DOING_AJAX') && DOING_AJAX)
			return;

		global $wcsts_order_model;
		$order = wc_get_order($order_id);
		if (!$order)
			return;

		//If the admin assings the order to another users, in this way also the associated user to the ticked is updated
		$orders_ticket = $wcsts_order_model->get_ticket_ids_by_order_id($order_id);
		foreach ((array)$orders_ticket as $ticket_obj)
			$this->set_attribute($ticket_obj->ticket_id, 'associated_user', $order->get_user_id());
	}
	public function on_admin_ticket_creation($ticket_id, $post)
	{
		global $wcsts_user_model;
		/* if($post->post_type != "wcsts_ticket")
			return; */

		wcsts_var_dump($post->post_type);
		wcsts_var_dump($ticket_id);
		wcsts_var_dump(get_current_user_id());
		wp_die();
		//$wcsts_user_model->assign_ticket_id_to_user($ticket_id, get_current_user_id());
	}
	private function update_modified_date($ticket_id)
	{
		/* global $wpdb;
		$query = "UPDATE {$wpdb->posts}.post_modified 
				  WHERE {$wpdb->posts}.ID = {$ticket_id} ";
				  
		$wpdb->get_results($query); */
		$my_post = array(
			'ID'           => $ticket_id,
			'post_modified'   => current_time('mysql')
		);

		// Update the post into the database
		wp_update_post($my_post);
	}
	public function get_creation_date($ticket_id)
	{
		return get_the_date(get_option('date_format'), $ticket_id);
	}
	public function before_delete_ticket($ticket_id)
	{
		global $wcsts_ticket_message_model, $wcsts_file_model, $wcsts_order_model, $wcsts_user_model;
		$ticket = get_post($ticket_id);
		if ($ticket->post_type != 'wcsts_ticket')
			return;
		$wcsts_ticket_message_model->delete_all_ticket_messages($ticket_id);

		$wcsts_file_model->delete_directory($ticket_id);

		$wcsts_order_model->delete_tickets_assigned_to_order($ticket_id);
		$wcsts_user_model->delete_tickets_assigned_to_user($ticket_id);
	}
	public function get_ticket_messages_by_order_id($order_id, $order = 'asc')
	{
		global $wcsts_order_model, $wcsts_ticket_message_model;
		$ticket_ids = $wcsts_order_model->get_ticket_ids_by_order_id($order_id);

		$result = array();
		foreach ((array)$ticket_ids as $ticket_obj)
			$result[$ticket_obj->ticket_id] =  $wcsts_ticket_message_model->get_messages_by_ticket_id($ticket_obj->ticket_id);

		if (is_array($result) && !empty($result)) {
			if ($order == 'asc')
				ksort($result);
			else
				krsort($result);
		}

		return $result;
	}
	public function get_ppt_ticket_messages_by_order_id($order_id, $order = 'asc')
	{
		global $wcsts_order_model, $wcsts_ticket_message_model;
		$ticket_ids = $wcsts_order_model->get_ppt_ticket_ids_by_order_id($order_id);

		$result = array();
		foreach ((array)$ticket_ids as $ticket_obj)
			$result[$ticket_obj->ticket_id] =  $wcsts_ticket_message_model->get_messages_by_ticket_id($ticket_obj->ticket_id);

		if (is_array($result) && !empty($result)) {
			if ($order == 'asc')
				ksort($result);
			else
				krsort($result);
		}

		return $result;
	}
	public function get_ticket_messages_by_user_id_and_type($user_id, $order = 'asc', $type = 'user')
	{
		global $wcsts_user_model, $wcsts_ticket_message_model;
		$ticket_ids = $wcsts_user_model->get_ticket_ids_by_user_id_and_type($user_id, $type);

		$result = array();
		foreach ((array)$ticket_ids as $ticket_obj)
			$result[$ticket_obj->ticket_id] =  $wcsts_ticket_message_model->get_messages_by_ticket_id($ticket_obj->ticket_id);

		if (is_array($result) && !empty($result)) {
			if ($order == 'asc')
				ksort($result);
			else
				krsort($result);
		}

		return $result;
	}
	//Note: This method returns alwaus the topic content. Use the get_attributes('subject') return the selected topic index ( in case of select topic)
	public function get_subject($ticket_id)
	{
		global $wcsts_option_helper, $wcsts_text_helper;
		$ticket_type = $this->get_attributes($ticket_id, 'ticket_type');
		$was_automatically_opened = $this->get_attributes($ticket_id, 'was_automatically_opened');
		$subject_type = $wcsts_text_helper->get_topic_type($ticket_type . '_ticket_subject_type');

		$result = "";
		if ($was_automatically_opened) {
			return $this->get_attributes($ticket_id, 'subject', "");
		}
		switch ($subject_type) {
			case 'text_input':
				$result = $this->get_attributes($ticket_id, 'subject', "");
				break;
			case 'admin_defined_topics':
				$topic_id = $this->get_attributes($ticket_id, 'subject', "");
				$topics = $wcsts_text_helper->get_topics_data($ticket_type . '_ticket_subject_topics');;
				$result = isset($topics[$topic_id]) ? $topics[$topic_id] : "";
				break;
		}
		return $result /* $this->get_attributes( $ticket_id, 'subject', "") */;
	}
	public function set_subject($ticket_id, $subject)
	{
		if (! add_post_meta($ticket_id, 'wcsts_subject',  $subject, true))
			update_post_meta($ticket_id, 'wcsts_subject',  $subject);
	}
	public function get_subject_topics($ticket_id)
	{
		global $wcsts_option_helper, $wcsts_text_helper;
		$ticket_type = $this->get_attributes($ticket_id, 'ticket_type');
		//$topics = $wcsts_option_helper->get_all_options($ticket_type.'_ticket_subject_topics');
		$topics = $wcsts_text_helper->get_topics_data($ticket_type . '_ticket_subject_topics');
		return $topics;
	}
	public function get_subject_topics_by_type($ticket_type)
	{
		global $wcsts_option_helper, $wcsts_text_helper;
		//$topics = $wcsts_option_helper->get_all_options($ticket_type.'_ticket_subject_topics');
		$topics = $wcsts_text_helper->get_topics_data($ticket_type . '_ticket_subject_topics');
		return $topics;
	}
	public function get_topic_recipients($ticket_id, $topic_type = 'user') //$topic_type = 'user' || 'order'
	{
		if (!isset($ticket_id))
			return "";

		global $wcsts_text_helper;
		$subject_id = $this->get_attributes($ticket_id, 'subject', "");
		/* $ticket_type = $this->get_attributes($ticket_id,'ticket_type');
		$subject_type = $wcsts_text_helper->get_topic_type($ticket_type.'_ticket_subject_type'); */
		$topic_data = $wcsts_text_helper->get_topic_recipents_data();
		return wcsts_get_value_if_set($topic_data, array("{$topic_type}_ticket_recipients", $subject_id), '');
	}
	public function get_priority($ticket_id)
	{
		$priority = esc_html__("N/A", "woocommerce-support-ticket-system");
		$result = wp_get_post_terms($ticket_id, 'wcsts_ticket_priority');
		if ($result && isset($result[0])) {
			$priority = $result[0]->name;
		}
		return $priority;
	}
	public function get_priority_id($ticket_id)
	{
		$priority_id = false;
		$result = wp_get_post_terms($ticket_id, 'wcsts_ticket_priority');
		if ($result && isset($result[0])) {
			$priority_id = $result[0]->term_id;
		}

		return $priority_id;
	}
	public function set_priority($ticket_id, $priority_id)
	{
		//$id = is_numeric ($priority_id) ? $priority_id : intval($priority_id);
		//wp_set_post_terms( $ticket_id, array($id), 'wcsts_ticket_priority' );
		wp_set_object_terms($ticket_id, $priority_id, 'wcsts_ticket_priority'); //slug
	}
	public function get_priority_list()
	{
		$priorities = array();
		$terms = get_terms(array(
			'taxonomy' => 'wcsts_ticket_priority',
			'hide_empty' => false,
		));

		//wcsts_var_dump($terms);
		if ($terms)
			foreach ($terms as $term) {
				$priorities[$term->slug] = $term->name;
				//$priorities[$term->term_id] = $term->name;
			}
		return $priorities;
	}

	public function ppt_reset_question_left_counter($ticket_id, $order)
	{
		global $wcsts_order_model;
		$id = $this->get_attributes($ticket_id, 'ppt_product_id', 0);
		if ($id == 0)
			return;
		/* wcsts_write_log("ppt_reset_question_left_counter");
		wcsts_write_log($ticket_id);
		wcsts_write_log($order->get_id()); */
		$key = WCSTS_Order::$ORDER_PPT_ORDER_KEY_PREFIX . $id;
		$number = $wcsts_order_model->get_meta($order, $key);
		//wcsts_write_log($number);
		if (!isset($number) || !is_numeric($number))
			return;

		$this->set_attribute($ticket_id, 'number_of_questions_left', $number);

		//cache
		$this->all_options[$ticket_id]['number_of_questions_left'] = $number;
	}
	public function ppt_decrease_question_left_counter($ticket_id)
	{
		$number_of_questions_left = $this->get_attributes($ticket_id, 'number_of_questions_left', 0);
		$number_of_questions_left = $number_of_questions_left > 0 ? $number_of_questions_left - 1 : $number_of_questions_left;
		//cache
		$this->all_options[$ticket_id]['number_of_questions_left'] = $number_of_questions_left;
		$this->set_attribute($ticket_id, 'number_of_questions_left', $number_of_questions_left);

		return $number_of_questions_left;
	}
	/* public function get_available_statuses()
	{
		return array('open' => esc_html__('Open', 'woocommerce-support-ticket-system'), 'in_progress' => esc_html__('In Progress', 'woocommerce-support-ticket-system'), 'closed' => esc_html__('Closed', 'woocommerce-support-ticket-system'));
	} */

	//Status managment
	public function get_status_data($ticket_id)
	{
		$status_id =  $this->get_attributes($ticket_id, 'status', "open");
		$field = get_field_object('wcsts_status');
		//$statuses = $this->get_available_statuses();
		$status_data = $this->get_status_by_id($status_id, $ticket_id);

		return $status_data;
	}
	public function get_status($ticket_id)
	{
		return $this->get_attributes($ticket_id, 'status', "open");
	}
	public function set_status($ticket_id, $default_value = 'open')
	{
		$this->set_attribute($ticket_id, 'status', $default_value);
	}
	public function get_status_to_which_automatically_swith_in_case_of_reply($ticket_id)
	{
		$status_data = $this->get_status_data($ticket_id);
		return $status_data['automatic_switch_to_selected_status'];
	}
	public function get_status_by_id($status_id, $ticket_id = null)
	{
		global $wcsts_wpml_helper;
		$lang_code = $wcsts_wpml_helper->get_current_locale();
		$statuses = $this->get_available_statuses();


		if (!isset($statuses[$status_id]) && isset($ticket_id))
			$this->set_status($ticket_id, 'status', 'open');

		return isset($statuses[$status_id]) ? $statuses[$status_id] : $statuses['open'];
	}
	public function get_available_statuses()
	{
		global $wcsts_wpml_helper;
		$statuses_option = get_option('wcsts_statuses');
		$statuses = array();
		$default_satuses = array(
			'open' => array('background_color' => '#16a085', 'text_color' => '#ffffff', 'automatic_switch_to_selected_status' => false, 'label' => esc_html__('Open', 'woocommerce-support-ticket-system')),
			'in_progress' => array('background_color' => '#e67e22', 'text_color' => '#ffffff', 'automatic_switch_to_selected_status' => false, 'label' => esc_html__('In Progress', 'woocommerce-support-ticket-system')),
			'closed' => array('background_color' => '#c0392b', 'text_color' => '#ffffff', 'automatic_switch_to_selected_status' => false, 'label' => esc_html__('Closed', 'woocommerce-support-ticket-system'))
		);
		if ($statuses_option)
			foreach ((array)$statuses_option as $status_id => $status_option) {
				$statuses[$status_id] = $status_option;
			}

		$lang_list = $wcsts_wpml_helper->get_langauges_list();
		$curr_lang_code = $wcsts_wpml_helper->get_current_locale();
		$curr_def_code = $wcsts_wpml_helper->get_default_locale();
		//Default statuses managment. Format:
		/* 
			 ["open"]=>
			  array(3) {
				["background_color"]=>
				string(7) "#e67e22"
				["text_color"]=>
				string(7) "#000000"
				["label"]=>
				array(2) {
				  ["it_IT"]=>
				  string(4) "Open"
				  ["en_US"]=>
				  string(4) "Open"
				}
			  }
		  */
		foreach ($default_satuses as $default_code => $default_status) {
			//Set defaults
			if (!isset($statuses[$default_code])) {
				$statuses[$default_code] = array(
					'background_color' => $default_status['background_color'],
					'text_color' => $default_status['text_color'],
					'automatic_switch_to_selected_status' => $default_status['automatic_switch_to_selected_status'],
					'label' => array()
				);
			}
			if ($lang_list !== false) {
				foreach ($lang_list as $lang) {
					if (!isset($statuses[$default_code]['label'][$lang['default_locale']]))
						$statuses[$default_code]['label'][$lang['default_locale']] = $default_status['label'];
				}
			} else if (!isset($statuses[$default_code]['label'][$wcsts_wpml_helper->get_default_locale()]))
				$statuses[$default_code]['label'][$wcsts_wpml_helper->get_default_locale()] = $default_status['label'];

			$statuses[$default_code]['current_lang'] = $curr_lang_code;
			$statuses[$default_code]['def_lang'] = $curr_def_code;
			$statuses[$default_code]['is_custom'] = false; //redundant
			$statuses[$default_code]['id'] = $default_code;
			$statuses[$default_code]['automatic_switch_to_selected_status'] = wcsts_get_value_if_set($statuses, array($default_code, 'automatic_switch_to_selected_status'), null) ? $statuses[$default_code]['automatic_switch_to_selected_status'] : $default_status['automatic_switch_to_selected_status'];
			$statuses[$default_code]['background_color'] =  strpos($statuses[$default_code]['background_color'], '#') === false ? "#" . $statuses[$default_code]['background_color'] :  $statuses[$default_code]['background_color'];
			$statuses[$default_code]['text_color'] =  strpos($statuses[$default_code]['text_color'], '#') === false ? "#" . $statuses[$default_code]['text_color'] :  $statuses[$default_code]['text_color'];
		}

		//Custom statuses languages managment (if multilanguage): in case a translation does not exists an empty string is setted
		if ($statuses)
			foreach ((array)$statuses as $status_id => $status_option) {
				if (array_key_exists($status_id, $default_satuses))
					continue;

				$statuses[$status_id]['current_lang'] = $curr_lang_code;
				$statuses[$status_id]['def_lang'] = $curr_def_code;
				$statuses[$status_id]['is_custom'] = true; //redundant
				$statuses[$status_id]['background_color'] =  strpos($statuses[$status_id]['background_color'], '#') === false ? "#" . $statuses[$status_id]['background_color'] :  $statuses[$status_id]['background_color'];
				$statuses[$status_id]['text_color'] =  strpos($statuses[$status_id]['text_color'], '#') === false ? "#" . $statuses[$status_id]['text_color'] :  $statuses[$status_id]['text_color'];
				$statuses[$status_id]['automatic_switch_to_selected_status'] =  wcsts_get_value_if_set($statuses, array($status_id, 'automatic_switch_to_selected_status'), false) ? $statuses[$status_id]['automatic_switch_to_selected_status']  : false;

				if ($lang_list !== false) {
					foreach ($lang_list as $lang)
						if (!isset($statuses[$status_id]['label'][$lang['default_locale']]))
							$statuses[$status_id]['label'][$lang['default_locale']] = "";
				} else
						if (!isset($statuses[$status_id]['label'][$wcsts_wpml_helper->get_default_locale()]))
					$statuses[$status_id]['label'][$wcsts_wpml_helper->get_default_locale()] = "";
			}

		return $statuses;
	}
	function save_statuses($statuses)
	{
		update_option('wcsts_statuses', $statuses);
	}
	//End status managment


	//Note: get_attributes('subject'), in case of select topic, return the selected topic index
	public function get_attributes($ticket_id, $option_name = null, $default = null)
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);

		if (!isset($this->all_options[$ticket_id])) {
			$this->all_options[$ticket_id] = array();
			//Type
			$this->all_options[$ticket_id]['ticket_type'] = get_field('wcsts_ticket_type', $ticket_id);
			$this->all_options[$ticket_id]['ticket_type'] = $this->all_options[$ticket_id]['ticket_type'] != null ? $this->all_options[$ticket_id]['ticket_type'] : 'order';

			$this->all_options[$ticket_id]['was_automatically_opened'] = get_field('wcsts_was_automatically_opened', $ticket_id);
			$this->all_options[$ticket_id]['was_automatically_opened'] = $this->all_options[$ticket_id]['was_automatically_opened'] != null ? $this->all_options[$ticket_id]['was_automatically_opened'] : false;

			$this->all_options[$ticket_id]['associated_order'] = get_field('wcsts_associated_order', $ticket_id);
			$this->all_options[$ticket_id]['associated_order'] = $this->all_options[$ticket_id]['associated_order'] != null ? $this->all_options[$ticket_id]['associated_order'] : null;

			$this->all_options[$ticket_id]['associated_user'] = @get_field('wcsts_associated_user', $ticket_id);
			$this->all_options[$ticket_id]['associated_user'] = $this->all_options[$ticket_id]['associated_user'] != null ? $this->all_options[$ticket_id]['associated_user'] : null;
			$this->all_options[$ticket_id]['associated_user'] = is_array($this->all_options[$ticket_id]['associated_user']) ? $this->all_options[$ticket_id]['associated_user']['ID'] : $this->all_options[$ticket_id]['associated_user'];


			//Status
			$this->all_options[$ticket_id]['status'] = get_field('wcsts_status', $ticket_id);
			$this->all_options[$ticket_id]['status'] = $this->all_options[$ticket_id]['status'] != null ? $this->all_options[$ticket_id]['status'] : "open";

			/* $this->all_options[$ticket_id]['subject'] = get_field('wcsts_subject', $ticket_id); */
			$this->all_options[$ticket_id]['subject'] = get_post_meta($ticket_id, 'wcsts_subject', true);
			$this->all_options[$ticket_id]['subject'] = isset($this->all_options[$ticket_id]['subject']) ? $this->all_options[$ticket_id]['subject'] : "";

			$this->all_options[$ticket_id]['open_status_date'] = get_field('wcsts_open_status_date', $ticket_id);  //F j, Y g:i a
			$this->all_options[$ticket_id]['open_status_date'] = $this->all_options[$ticket_id]['open_status_date'] != null ? $this->all_options[$ticket_id]['open_status_date'] : "";

			$this->all_options[$ticket_id]['closed_status_date'] = get_field('wcsts_closed_status_date', $ticket_id);  //F j, Y g:i a
			$this->all_options[$ticket_id]['closed_status_date'] = $this->all_options[$ticket_id]['closed_status_date'] != null ? $this->all_options[$ticket_id]['closed_status_date'] : "";

			$this->all_options[$ticket_id]['notification_recipients_override'] = get_field('wcst_notification_recipients_override', $ticket_id);
			$this->all_options[$ticket_id]['notification_recipients_override'] = $this->all_options[$ticket_id]['notification_recipients_override'] != null ? $this->all_options[$ticket_id]['notification_recipients_override'] : "";

			$this->all_options[$ticket_id]['number_of_questions_left'] = get_field('wcsts_number_of_questions_left', $ticket_id);
			$this->all_options[$ticket_id]['number_of_questions_left'] = $this->all_options[$ticket_id]['number_of_questions_left'] != null ? $this->all_options[$ticket_id]['number_of_questions_left'] : "";

			$this->all_options[$ticket_id]['ppt_product_name'] = get_field('wcsts_ppt_product_name', $ticket_id);
			$this->all_options[$ticket_id]['ppt_product_name'] = $this->all_options[$ticket_id]['ppt_product_name'] != null ? $this->all_options[$ticket_id]['ppt_product_name'] : "";

			$this->all_options[$ticket_id]['ppt_product_id'] = get_field('wcsts_ppt_product_id', $ticket_id);
			$this->all_options[$ticket_id]['ppt_product_id'] = $this->all_options[$ticket_id]['ppt_product_id'] != null ? $this->all_options[$ticket_id]['ppt_product_id'] : "";

			$this->all_options[$ticket_id]['ppt_order_id'] = get_field('wcsts_ppt_order_id', $ticket_id);
			$this->all_options[$ticket_id]['ppt_order_id'] = $this->all_options[$ticket_id]['ppt_order_id'] != null ? $this->all_options[$ticket_id]['ppt_order_id'] : 0;
		}

		remove_filter('acf/settings/current_language', array(&$this, 'cl_acf_set_language'), 100);

		if (isset($option_name))
			return isset($this->all_options[$ticket_id][$option_name]) ? $this->all_options[$ticket_id][$option_name] : $default;

		return $this->all_options[$ticket_id];
	}
	function set_attribute($ticket_id, $option_name, $value)
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);

		//Update
		update_field('wcsts_' . $option_name, $value, $ticket_id);

		//special open/close data fields
		if ($option_name == 'open_status_date') {
			$this->set_special_standard_date_field_format('open_status_date', $ticket_id, $value);
		} elseif ($option_name == 'closed_status_date') {
			$this->set_special_standard_date_field_format('closed_status_date', $ticket_id, $value);
		}
		//Cache
		if (isset($this->all_options[$ticket_id]))
			$this->all_options[$ticket_id][$option_name] = $value;
		remove_filter('acf/settings/current_language', array(&$this, 'cl_acf_set_language'), 100);
	}
	//open_status_date || closed_status_date
	function set_special_standard_date_field_format($option_name, $ticket_id, $value)
	{
		$myDateTime = DateTime::createFromFormat('F j, Y g:i a', $value);
		if (!is_object($myDateTime))
			return;
		$newDateString = $myDateTime->format('Y-m-d H:i:s');
		update_post_meta($ticket_id, 'wcsts_' . $option_name . '_standard_format', $newDateString);
	}

	function cl_acf_set_language()
	{
		return acf_get_setting('default_language');
	}
	public function remove_manager_user_assigned_to_any_ticket($user_ids)
	{
		$user_ids = !is_array($user_ids) ? array($user_ids) : $user_ids;

		if (empty($user_ids))
			return;

		global $wpdb;
		$query_string = "DELETE  
						  FROM {$wpdb->postmeta} 
						  WHERE meta_key = 'wcsts_manager_user_id' AND meta_value IN ('" . implode("','", $user_ids) . "')";

		$wpdb->query('SET MAX_JOIN_SIZE=99999999999999999');
		$wpdb->query('SET SQL_BIG_SELECTS=1');
		$wpdb->get_results($query_string);
	}
	public function remove_all_manager_users_assigned_to_tickets($ticket_ids)
	{
		$ticket_ids = !is_array($ticket_ids) ? array($ticket_ids) : $ticket_ids;

		if (empty($ticket_ids))
			return;

		foreach ($ticket_ids as $ticket_id)
			delete_post_meta($ticket_id, 'wcsts_manager_user_id');
	}
	public function assign_manager_users_to_tickets($ticket_ids, $user_ids)
	{
		$ticket_ids = !is_array($ticket_ids) ? array($ticket_ids) : $ticket_ids;
		$user_ids = !is_array($user_ids) ? array($user_ids) : $user_ids;

		if (empty($ticket_ids) || empty($user_ids))
			return;

		foreach ($ticket_ids as $ticket_id)
			foreach ($user_ids as $user_id)
				add_post_meta($ticket_id, 'wcsts_manager_user_id', $user_id, false);
	}
	public function get_manager_user_ids($ticket_id)
	{
		if (!isset($ticket_id))
			return array();

		$result = get_post_meta($ticket_id, 'wcsts_manager_user_id');

		/* Format
		array(2) {
		  [0]=>
		  string(4) "7969"
		  [1]=>
		  string(1) "1"
		}
		*/
		return $result ? $result : array();
	}
	public function get_tickets_managed_by_user($user_id)
	{
		if (!isset($user_id))
			return array();


		global $wpdb;
		$query_string = " SELECT  post_id AS ticket_id
						  FROM {$wpdb->postmeta} 
						  WHERE meta_key = 'wcsts_manager_user_id' AND meta_value = {$user_id} ";

		$wpdb->query('SET MAX_JOIN_SIZE=99999999999999999');
		$wpdb->query('SET SQL_BIG_SELECTS=1');
		$result = $wpdb->get_results($query_string, ARRAY_A);

		/* 
		array(3) {
		  [0]=>
		  array(1) {
			["ticket_id"]=>
			string(4) "24"
		  }
		  [1]=>
		  array(1) {
			["ticket_id"]=>
			string(4) "22"
		  }
		  [2]=>
		  array(1) {
			["ticket_id"]=>
			string(4) "1"
		  }
		}
		*/

		return $result ? $result : array();
	}
}
