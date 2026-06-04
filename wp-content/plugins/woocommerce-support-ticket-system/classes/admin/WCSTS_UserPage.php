<?php 
class WCSTS_UserPage
{
	public function __construct()
	{
		add_action ( 'show_user_profile', array( $this,'render_expiring_products_dates'), 99);
		add_action ( 'edit_user_profile', array( $this,'render_expiring_products_dates'), 99 );
		
		//add_action ( 'edit_user_profile_update', array( $this,'save_data')); //user/admin is viewing other profile
		//add_action ( 'personal_options_update', array( $this,'save_data')); //user/admin is viewing other profile
		add_action( 'profile_update', array( $this,'save_profile'), 10 , 3); 
	}
	public function render_expiring_products_dates( $user )
	{
		if(!is_admin())
			return;
		
		wp_enqueue_script('wcsts-select2', WCSTS_PLUGIN_PATH.'/js/vendor/select2/select2.min.js', array('jquery'));			
		
		wp_enqueue_style('wcsts-profile-page', WCSTS_PLUGIN_PATH. '/css/backend-user-profile-page.css' );
		wp_enqueue_style( 'admin-select2', WCSTS_PLUGIN_PATH.'/css/vendor/select2/select2.css'); 
		
		global $wcsts_ticket_model, $wcsts_user_model, $wcsts_html_helper;
		$ppt_data= $wcsts_user_model->get_pp_meta($user->ID);
		$tickets = $wcsts_ticket_model->get_tickets_managed_by_user($user->ID);
		echo '<h3>'.__( 'Assigned tickets', 'woocommerce-support-ticket-system' ).'</h3>';
		echo '<table class="form-table">';
		echo '<tbody>';
		echo '<tr>';
			echo '<th><label class="wcsts_ticket_label">'.__( 'Tickets id(s)', 'woocommerce-support-ticket-system' ).'</label></th>';
			echo '<td>';
			$counter_tmp = 0;
			foreach((array)$tickets as $ticket)
			{
				$ticket_id = $ticket['ticket_id'];
				if($counter_tmp++)
					echo ", ";
				echo '<span class="wcra_purchased_date_content"><a href="'.esc_url(get_edit_post_link($ticket_id)).'" target="_blank">'.$ticket_id.'</a></span>';
			
			} 
			echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		
		
		echo '<h3>'.__( 'Associated ticket managers', 'woocommerce-support-ticket-system' ).'</h3>';
		$managers_ids = $wcsts_user_model->get_associated_ticket_managers($user->ID);
		$wcsts_html_helper->render_multiple_user_selector($managers_ids, array('disable_notification_area' => true)); 
		
		
		return;
		
		
		$counter = 1;
		echo '<h3>'.__( 'Pay per ticket', 'woocommerce-support-ticket-system' ).'</h3>';
			echo '<table class="form-table">';
			echo '<tbody>';
		foreach($ppt_data as $tikect_data)
		{
			
			echo '<tr>';
				echo '<th><label class="wcsts_ticket_label">'.$counter++.". ".__( 'Product name', 'woocommerce-support-ticket-system' ).'</label></th>';
				echo '<td>'.$tikect_data->value['product_name'].'</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label class="wcsts_ticket_label">'.__( 'Questions left', 'woocommerce-support-ticket-system' ).'</label></th>';
				echo '<td><input type="number" min="0" required="required" name="wcsts_ppt[questions_number]['.$tikect_data->id.']" value="'.$tikect_data->value['questions_number'].'"></input></td>';
			echo '</tr>';
			//questions left?: No, left value is the questions_number
			echo '<tr>';
				echo '<th><label class="wcsts_ticket_label">'.__( 'Purchased on', 'woocommerce-support-ticket-system' ).'</label></th>';
				$date = date($tikect_data->value['order_date']);            
				$date_timestamp = strtotime($date);
				$formatted_date = date(get_option('date_format')." ".get_option('time_format'), $date_timestamp); 
				echo '<td>'.$formatted_date.'</td>';
			echo '</tr>';
			echo '<tr class="wcsts_border_bottom">';
				echo '<th><label class="wcsts_ticket_label">'.__( 'Delete', 'woocommerce-support-ticket-system' ).'</label></th>';
				echo '<td><input type="checkbox" name="wcsts_ppt[delete_meta_by_id]['.$tikect_data->id.']" value="true">'.__( 'To delete a Pay Per Ticket data, just check the checkbox and then it the "Update Profile" button you find at the bottom of the page', 'woocommerce-support-ticket-system' ).'</input></td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		
		
		
		
	}
	
	public function save_data($user_id = 0) //it is never 0
	{
		global $wcsts_user_model;
		if($user_id == 0 || !isset($_POST['wcsts_ppt']))
			return;
		
		/* Format: 
		array(2) {
		  ["questions_number"]=>
		  array(2) {
			[270512]=>
			string(1) "3"
			[270513]=>
			string(4) "2122"
		  }
		  ["delete_meta_by_id"]=>
		  array(1) {
			[270513]=>
			string(4) "true"
		  }
		}
		*/
		
		$to_delete = array();
		if(isset($_POST['wcsts_ppt']['delete_meta_by_id']))
			foreach((array)$_POST['wcsts_ppt']['delete_meta_by_id'] as $id_to_delete => $value)
				$to_delete[] = $id_to_delete;
				
		$wcsts_user_model->delete_ppt_meta_by_ids($user_id, $to_delete);
		
		$to_update = array();
		if(isset($_POST['wcsts_ppt']['questions_number']))
			foreach((array)$_POST['wcsts_ppt']['questions_number'] as $id_to_update => $value)
			{
				$to_update[] = array('id'=> $id_to_update, 'questions_number' => $value);
			}
				
		$wcsts_user_model->update_ppt_question_numbers_metas_by_ids($user_id, $to_update);
		
	
	}
	
	public function save_profile($user_id, $old_user_data, $userdata)
	{
		if(!is_admin())
			return;
		global $wcsts_user_model;
		$user_ids = isset($_POST['manager_user_id']) ? $_POST['manager_user_id'] : array();
		$wcsts_user_model->set_associated_ticket_managers($user_id, $user_ids);
	}
	
}
?>