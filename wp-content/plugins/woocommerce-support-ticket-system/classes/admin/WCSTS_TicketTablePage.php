<?php 
class WCSTS_TicketTablePage
{
	var $admin_notice = "";
	public function __construct()
	{
		add_action( 'admin_notices', array($this,'print_admin_result_notice') );
		
		//--------- Columns -------------
		//head
		add_filter( 'manage_edit-wcsts_ticket_columns', array( &$this, 'add_custom_columns_heads'),15 );
		//content
		add_action( 'manage_wcsts_ticket_posts_custom_column', array( &$this, 'add_custom_content_columns'), 10, 2 );
		//sortable function
		add_filter( 'manage_edit-wcsts_ticket_sortable_columns', array( &$this,'sort_columns') );
		//--------- End columns ---------
		
		
		//Extra dropdown menu and actions
		add_action('restrict_manage_posts', array( &$this,'add_extra_filters'));
		
		//Pre filter
		add_filter('parse_query',array( &$this,'filter_query'));
		add_action( 'pre_get_posts', array( &$this,'set_default_sort'),9 );
		
	}
	function print_admin_result_notice() 
	{
		if($this->admin_notice == "")
			return;
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo $this->admin_notice; ?></p>
		</div>
		<?php
	} 
	public function add_extra_filters()
	{
		global $typenow, $wp_query,$wcsts_option_helper,$wcsts_user_model, $wcsts_ticket_model; 
		if ($typenow=='wcsts_ticket') 
		{
			$ticket_visibility = $wcsts_option_helper->get_all_options('ticket_visibility', 'all_tickets'); // all_tickets || only_assigned
			
			wp_enqueue_style( 'wcsts-select2-style',  WCSTS_PLUGIN_PATH.'/css/vendor/select2/select2.css' );
			wp_enqueue_style( 'wcsts-backend-tickets-table',  WCSTS_PLUGIN_PATH.'/css/backend-tickets-table-page.css' );
			
			wp_enqueue_script('wcsts-select2', WCSTS_PLUGIN_PATH.'/js/vendor/select2/select2.min.js', array('jquery'));
			wp_register_script('wcsts-load-customers-list', WCSTS_PLUGIN_PATH.'/js/backend-load-customer-list.js', array('jquery'));
			wp_register_script('wcsts-custom-ui', WCSTS_PLUGIN_PATH.'/js/backend-ticket-table-custom-ui.js', array('jquery'));
			$custom_html_ui_vars = array(
				'filter_button_text' => esc_html__( 'Apply', 'woocommerce-support-ticket-system' ),
			);  
			wp_localize_script( 'wcsts-custom-ui', 'wcsts_custom_ui', $custom_html_ui_vars );

			wp_localize_script( 'wcsts-load-customers-list', 'wcsts', array(
																			 'select2_placeholder' => esc_html__( 'Filter by customer', 'woocommerce-support-ticket-system' ),
																			 'selected_user_info_label' => esc_html__( 'Showing ticket for user ID: ', 'woocommerce-support-ticket-system' ),
																			 'select2_selected_value' => isset($_GET['customer_id']) ? $_GET['customer_id'] : ""
																			) 
								);
			wp_enqueue_script( 'wcsts-load-customers-list' );
			wp_enqueue_script( 'wcsts-custom-ui' );
			
			$selected = isset($_GET['wcsts_ticket_type']) && $_GET['wcsts_ticket_type'] ? $_GET['wcsts_ticket_type']:"none";
			$show_tickets_assigned_to = isset($_GET['wcsts_show_tickets_assigned_to']) && $_GET['wcsts_show_tickets_assigned_to'] ? $_GET['wcsts_show_tickets_assigned_to']:"none";
			$select2_selected = isset($_GET['customer_id']) && $_GET['customer_id'] ? $_GET['customer_id']:"none";
			$selected_status = "none";
			$selected_filter_status = isset($_GET['wcsts_ticket_filter_by_status']) && $_GET['wcsts_ticket_filter_by_status'] ? $_GET['wcsts_ticket_filter_by_status']:"none";
			
			if($wcsts_user_model->is_current_user_administrator() || $ticket_visibility == 'all_tickets'):
			?>
			<select name="wcsts_show_tickets_assigned_to" >
				<option value="none" <?php if($show_tickets_assigned_to == "none") echo 'selected="selected"';?> ><?php esc_html_e('Show ticket assignet to', 'woocommerce-support-ticket-system') ?></option>
				<option value="all" <?php if($show_tickets_assigned_to == "all") echo 'selected="selected"';?>><?php esc_html_e('All', 'woocommerce-support-ticket-system') ?></option>
				<option value="to_me" <?php if($show_tickets_assigned_to == "to_me") echo 'selected="selected"';?>><?php esc_html_e('To me', 'woocommerce-support-ticket-system') ?></option>
			</select>
			<?php endif; 
			$ticket_types = $wcsts_ticket_model->get_ticket_types();
			?>
			<select name="wcsts_ticket_type" >
				<option value="none" <?php if($selected == "none") echo 'selected="selected"';?>><?php esc_html_e('Select a ticket type', 'woocommerce-support-ticket-system') ?></option>
				<option value="all" <?php if($selected == "all") echo 'selected="selected"';?>><?php esc_html_e('All', 'woocommerce-support-ticket-system') ?></option>
				<?php foreach($ticket_types as $ticket_type => $ticket_name): ?>
				<option value="<?php echo $ticket_type; ?>" <?php if($selected == $ticket_type) echo 'selected="selected"';?>><?php echo $ticket_name; ?></option>
				<?php endforeach;?>
			</select>
			<?php 
			$available_statuses = $wcsts_ticket_model->get_available_statuses();
			?>
			<select name="wcsts_ticket_filter_by_status" >
				<option value="none" <?php if($selected_filter_status == "none") echo 'selected="selected"';?>><?php esc_html_e('Filter by status', 'woocommerce-support-ticket-system') ?></option>
				<?php foreach($available_statuses as $status_id => $status_data): ?>
				<option value="<?php echo $status_id; ?>" <?php if($selected_filter_status == $status_id) echo 'selected="selected"';?>><?php echo $status_data["label"][$status_data["current_lang"]]; ?></option>
				<?php endforeach;?>
			</select>
			
			<select name="wcsts_ticket_bulk_status_assign" >
				<option value="none" <?php if($selected_status == "none") echo 'selected="selected"';?>><?php esc_html_e('Bulk status assign', 'woocommerce-support-ticket-system') ?></option>
				<?php foreach($available_statuses as $status_id => $status_data): ?>
				<option value="<?php echo $status_id; ?>" <?php if($selected_status == $status_id) echo 'selected="selected"';?>><?php echo $status_data["label"][$status_data["current_lang"]]; ?></option>
				<?php endforeach;?>
			</select>
						
			<select class="js-data-customers-ajax" id="wcst_select2_customer_id" name="customer_id" >
			</select>
			<?php
		}
	}
	function filter_query($query) 
	{
		global $pagenow, $wcsts_option_helper, $wcsts_user_model,$wpdb;
		$qv = &$query->query_vars;
		
		if( $pagenow !='edit.php' || !isset($qv['post_type']) || $qv['post_type'] !='wcsts_ticket')
			return $query;
		
		$ticket_visibility =  isset($qv['post_type']) && $qv['post_type'] =='wcsts_ticket' && isset($wcsts_option_helper) && is_admin() ? $wcsts_option_helper->get_all_options('ticket_visibility', 'all_tickets') : 'all_tickets';
		
		$wpdb->query('SET MAX_JOIN_SIZE=9999');
		$wpdb->query('SET SQL_BIG_SELECTS=1');
			
		if ($pagenow=='edit.php' && 
		    isset($qv['post_type']) && $qv['post_type']=='wcsts_ticket' && isset($_GET['wcsts_ticket_type']) && ($_GET['wcsts_ticket_type'] != 'none' && $_GET['wcsts_ticket_type'] != 'all')) 
		{
			 $qv['meta_query'][] = 
				array(
				 array(
					'key' => 'wcsts_ticket_type',
					'compare' => '=',
					'value' => $_GET['wcsts_ticket_type']
				  ),
				 
			  );
			
		}
		if ($pagenow=='edit.php' && 
		    isset($qv['post_type']) && $qv['post_type']=='wcsts_ticket' && isset($_GET['wcsts_ticket_bulk_status_assign']) && $_GET['wcsts_ticket_bulk_status_assign'] != 'none' && isset($_GET['post'])) 
		{
			foreach((array)$_GET['post'] as $ticket_id)
			{
				$wcsts_ticket_model = new WCSTS_Ticket();
				$wcsts_ticket_model->set_status($ticket_id, $_GET['wcsts_ticket_bulk_status_assign']);
				$this->admin_notice = esc_html__('Statuses successfully applied to selected tickets!','woocommerce-support-ticket-system');
			}
		}
		if (  $pagenow=='edit.php' && 
		     isset($qv['post_type']) && $qv['post_type']=='wcsts_ticket' && 
			 ( (!$wcsts_user_model->is_current_user_administrator() && $ticket_visibility != 'all_tickets') || (isset($_GET['wcsts_show_tickets_assigned_to']) && ($_GET['wcsts_show_tickets_assigned_to'] != 'none' && $_GET['wcsts_show_tickets_assigned_to'] != 'all')) ) 
			) 
		{
			 $qv['meta_query'][] = 
				
				 array
				   (
				     'relation' => 'OR',
					  array(
						'key' => 'wcsts_manager_user_id',
						'compare' => '=',
						 'value' => get_current_user_id() //assigned 
					  ),
				  );
			
			  
		}
		if ($pagenow=='edit.php' && 
		    isset($qv['post_type']) && $qv['post_type']=='wcsts_ticket' && isset($_GET['customer_id']) && is_numeric($_GET['customer_id'])) 
			{
				
			 $qv['meta_query'][] = 
				array(
				 //'relation' => 'OR',
				  array(
					'key' => 'wcsts_associated_user',
					'compare' => '=',
					 'value' => $_GET['customer_id']
				  )
			  );
			}
		if ($pagenow=='edit.php' && 
		    isset($qv['post_type']) && $qv['post_type']=='wcsts_ticket' && isset($_GET['wcsts_ticket_filter_by_status']) && $_GET['wcsts_ticket_filter_by_status'] != 'none') 
			{
				
			 $qv['meta_query'][] = 
				array(
				 //'relation' => 'OR',
				  array(
					'key' => 'wcsts_status',
					'compare' => '=',
					 'value' => $_GET['wcsts_ticket_filter_by_status']
				  )
			  );
			}
			
		return $query;	
		
	}
	
	function sort_tickets_by_priority($clauses, $wp_query) 
	{
	   global $wpdb;
 
		if ( isset( $wp_query->query['orderby'] ) && 'wcsts_ticket_priority' == $wp_query->query['orderby'] ) 
		{
			$clauses['join'] .= "LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
							LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
							LEFT OUTER JOIN {$wpdb->terms} USING (term_id)";
			 
			$clauses['where'] .= " AND (taxonomy = 'wcsts_ticket_priority' OR taxonomy IS NULL)";
			$clauses['groupby'] = "object_id";
			$clauses['orderby']  = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
			$clauses['orderby'] .= ( 'ASC' == strtoupper( $wp_query->get('order') ) ) ? 'ASC' : 'DESC';
		}
	 
		return $clauses;
	}
	function set_default_sort($query)
	{
		if( ! $query->is_main_query() || 'wcsts_ticket' != $query->get( 'post_type' )  )
        return;
		$orderby = $query->get( 'orderby');      
	
		 switch ( $orderby ) 
		{
			  
			case '':  // <-- The default empty case
				$query->set( 'order', 'desc' );  
				$query->set( 'orderby',  'modified-date' );
				break;
			case 'open-date':
				$query->set( 'meta_key', 'wcsts_open_status_date_standard_format' );
				$query->set( 'orderby',  'meta_value' );
				break; 	
			case 'modified-date':
				$query->set( 'orderby',  'post_modified' );
				break; 	
			case 'new-messages-counter':
				$query->set( 'meta_key', 'wcst_new_messages_counter' );
				$query->set( 'orderby',  'meta_value' );
				break; 
			default:
				break;
		} 
	}
	function sort_columns( $columns)
	{
		$columns['open-date'] = 'open-date';
		$columns['new-messages-counter'] = 'new-messages-counter';
		$columns['modified-date'] = 'modified-date';
		$columns['ticket-id'] = 'id';
		$columns['status'] = 'status';
		return $columns;
	}
	function add_custom_columns_heads($columns)
	{  
		global $wcsts_user_model;
		//new columns
		$resorted_columns = array();
		$resorted_columns['cb'] = $columns['cb'];
		$resorted_columns['ticket-id'] = esc_html__('Id', 'woocommerce-support-ticket-system'); 
		$resorted_columns['ticket-type'] = esc_html__('Type', 'woocommerce-support-ticket-system'); 
		$resorted_columns['new-messages-counter'] = esc_html__('New messages', 'woocommerce-support-ticket-system'); 
		$resorted_columns['total-messages-counter'] = esc_html__('Total messages', 'woocommerce-support-ticket-system'); 
		$resorted_columns['subject'] = esc_html__('Subject', 'woocommerce-support-ticket-system'); 
		$resorted_columns['order-user-id'] = esc_html__('Order/User', 'woocommerce-support-ticket-system'); 
		$resorted_columns['status'] = esc_html__('Status', 'woocommerce-support-ticket-system'); 
		if($wcsts_user_model->is_current_user_administrator())
			$resorted_columns['assigned-users'] = esc_html__('Assigned to', 'woocommerce-support-ticket-system'); 
		
		//not needed elements removal
		unset($columns['title']);
		unset($columns['cb']);
		unset($columns['date']);
		
		foreach($columns as $column_key => $column_content)
			$resorted_columns[$column_key] = $column_content;
		
		//as last elements
		$resorted_columns['who-replied-last'] = esc_html__('Who replied last', 'woocommerce-support-ticket-system'); 
		$resorted_columns['modified-date'] = esc_html__('Last reply on', 'woocommerce-support-ticket-system'); 
		$resorted_columns['open-date'] = esc_html__('Opened on', 'woocommerce-support-ticket-system'); 
		
	   return $resorted_columns;
	}
	function manage_defaults_columns($column)
	{
		//wcsts_var_dump($column);
	}
	
	function add_custom_content_columns( $column, $ticket_id ) 
	{
		global $wcsts_ticket_model, $wcsts_user_model, $wcsts_option_helper;
		
		//Pre operations
		if($column == 'ticket-id' || $column == 'new-messages-counter')
		{
			$new_messages_counter = $wcsts_ticket_model->count_new_messages($ticket_id);
			$new_messages_counter_label  = $new_messages_counter > 0 ? '<span style="background:#d54e21; padding:5px; color:white;">'.$new_messages_counter.'</strong>' : $new_messages_counter;
		}
		//Columns
		if ( $column == 'ticket-id' ) 
		{
			//priority color lately managed via js (WordPress api doesn't have any method to manipulate taxonomy columns
			$term_id = $wcsts_ticket_model->get_priority_id($ticket_id);
			$attributes = $wcsts_option_helper->get_priority_term_attributes($term_id);
			$background_color = isset($attributes['background_color']) ? $attributes['background_color']: "none";
			$text_color = isset($attributes['text_color']) ? $attributes['text_color']: "#000000";
			//end
			
			$ticket = get_post($ticket_id);	
			$id_label = $new_messages_counter > 0 ? "<strong>".$ticket_id."</strong>" : $ticket_id;
			$output = '<a href="'.get_edit_post_link($ticket_id).'" data-is-priority-data-defined="true" data-priority-background-color="'.$background_color.'" data-priority-text-color="'.$text_color.'">'.$id_label.'</a><br/>';
			$output .= '<div class="row-actions">';
			$output .= '<span class="edit"><a href="'.get_edit_post_link($ticket_id).'">'.esc_html__('Edit', 'woocommerce-support-ticket-system').'</a> | </span>';
			if($ticket->post_status == 'trash')
				$output .= '<span class="trash"><a href="'.get_delete_post_link($ticket_id, null, true).'">'.esc_html__('Delete permanently', 'woocommerce-support-ticket-system').'</a></span>';
			else
				$output .= '<span class="trash"><a href="'.get_delete_post_link($ticket_id).'">'.esc_html__('Delete', 'woocommerce-support-ticket-system').'</a></span>';
			$output .= '</div>';
			echo $output;
		}
		if ( $column == 'new-messages-counter' )
		{			
			echo $new_messages_counter_label;
		}
		if ( $column == 'ticket-type' )
		{			
			$type = $wcsts_ticket_model->get_attributes($ticket_id,'ticket_type');
			$ticket_types = $wcsts_ticket_model->get_ticket_types();
			echo $ticket_types[$type];
			
		}
		if($column == 'total-messages-counter')
		{
			$total_messages_counter = $wcsts_ticket_model->count_total_messages($ticket_id);
			echo $total_messages_counter;
		}
		if ( $column == 'subject' ) 
		{
			if($wcsts_ticket_model->get_attributes($ticket_id,'ticket_type') != 'ppt')
			{
				echo "<div class='wcsts_subject_simple_text'>"; 
				echo $wcsts_ticket_model->get_subject($ticket_id);
				echo "</div>";
			}
			else 
			{
				echo "<div class='wcsts_subject_text_container'>"; 
				echo "<span class='wcsts_highlighted_text'>".esc_html__('Product name: ','woocommerce-support-ticket-system')."</span>";
				echo $wcsts_ticket_model->get_attributes($ticket_id, 'ppt_product_name', "")."<br/><br/>";
				echo "<span class='wcsts_highlighted_text'>".esc_html__('Question left: ','woocommerce-support-ticket-system')."</span>";
				echo $wcsts_ticket_model->get_attributes($ticket_id, 'number_of_questions_left', "");
				echo "</div>";
			}
		}
		if ( $column == 'order-user-id' ) 
		{
			$type = $wcsts_ticket_model->get_attributes($ticket_id,'ticket_type');
			if($type == 'order')
			{
				$associated_order = $wcsts_ticket_model->get_attributes($ticket_id,'associated_order');
				$associated_order = apply_filters('wcsts_get_order_id', $associated_order);
				echo '<a href="'.get_edit_post_link($associated_order).'" target="_blank">'.$associated_order.'</a>';
			}
			else
			{
				$associated_user_id = $wcsts_ticket_model->get_attributes($ticket_id,'associated_user');
				$associated_user = get_user_meta($associated_user_id);
				$first_name = isset($associated_user['billing_first_name']) && isset($associated_user['billing_first_name'][0]) ? $associated_user['billing_first_name'][0] : "";
				$last_name = isset($associated_user['billing_last_name']) && isset($associated_user['billing_last_name'][0]) ? $associated_user['billing_last_name'][0] : "";
				$email = isset($associated_user['billing_email']) && isset( $associated_user['billing_email'][0]) ? $associated_user['billing_email'][0] : "";
				echo '<a href="'.get_edit_user_link($associated_user_id).'" target="_blank">#'.$associated_user_id." - ".$first_name.' '.$last_name.'<br>('.$email.')</a>';
			}
		}
		if($column =='who-replied-last')
		{
			switch($wcsts_ticket_model->get_who_replied_latest($ticket_id))
			{
				case 'customer': esc_html_e('Customer', 'woocommerce-support-ticket-system'); break;
				case 'staff': esc_html_e('Staff', 'woocommerce-support-ticket-system'); break;
			}
		}
		if ( $column == 'modified-date' ) 
			the_modified_date(get_option('date_format')." ".get_option('time_format'));
		if ( $column == 'open-date' ) 
			echo $wcsts_ticket_model->get_attributes($ticket_id, 'open_status_date', "");
		if ( $column == 'status' ) 
		{
			$ticket_status_data = $wcsts_ticket_model->get_status_data($ticket_id);
			$status_text = $ticket_status_data['label'][$ticket_status_data['current_lang']];
			$ticket_status = $ticket_status_data['id'];
			 $status_label = '<span style="background:'.$ticket_status_data['background_color'].'; color:'.$ticket_status_data['text_color'].'; padding:5px;">'. $status_text.'</span>';
			
			echo $status_label;
		}
		if($column == 'assigned-users')
		{
			$user_ids = $wcsts_ticket_model->get_manager_user_ids($ticket_id);
			if(empty($user_ids))
				esc_html_e('Any','woocommerce-support-ticket-system');
			else
				foreach($user_ids as $user_id)
				{
					$user_data = $wcsts_user_model->get_user_data($user_id);
					if($user_data)
					{
						$user_name = $user_data->first_name.$user_data->last_name != "" ? $user_data->first_name." ".$user_data->last_name : "N/A";
						echo "<div class='wcsts_manager_user_container'><strong>#".$user_data->ID."</strong> - <a href='".get_edit_user_link($user_data->ID)."' target='_blank' >".$user_name."</a> </div>"; //(".$user_data->user_email.")
					}
				}
		}
		
	}
}
?>