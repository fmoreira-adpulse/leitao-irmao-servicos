<?php 
class WCSTS_CustomersAgentsAssociationPage
{
	public function __construct(){}
	
	private function save_data()
	{
		global $wcsts_user_model, $wcsts_ticket_model;
		$manager_ids = isset($_POST['manager_user_id']) ? $_POST['manager_user_id'] : array();
		$customer_ids = isset($_POST['customer_id']) ? $_POST['customer_id'] : array();
		
		if(empty($manager_ids) && !empty($customer_ids))
			 foreach($customer_ids as $customer_id)
				$wcsts_user_model->set_associated_ticket_managers($customer_id, array());
		
		if(!empty($manager_ids) && !empty($customer_ids))
			foreach($customer_ids as $customer_id)
				$wcsts_user_model->set_associated_ticket_managers($customer_id, $manager_ids);
		
		
	}
	public static function force_dequeue_scripts($enqueue_styles)
	{
		if ( class_exists( 'woocommerce' ) && isset($_GET['page']) && $_GET['page'] == 'wcsts-customer-agents-association-ticket') 
		{
			global $wp_scripts;
			$wp_scripts->queue = array();
			WCSTS_AssignTicketPage::enqueue_scripts();

		} 
	}
	public static function enqueue_scripts()
	{
		if ( class_exists( 'woocommerce' ) && isset($_GET['page']) && $_GET['page'] == 'wcsts-customer-agents-association-ticket') 
		{
			wp_enqueue_script('jquery') ;
			wp_enqueue_script('jquery-ui-core') ;
			wp_enqueue_script('jquery-ui-slider') ;
			wp_enqueue_script('jquery-ui-progressbar');
			
		}
	}
	public function render_page()
	{
		global $wcsts_html_helper;
		if(isset($_POST) && !empty($_POST))
		{
			if(isset($_POST['customer_id']) || isset($_POST['manager_user_id']))
			{
				$this->save_data();
				echo '<div id="message" class="updated"><p>'.esc_html__('Operation successfully performed.', 'woocommerce-support-ticket-system').'</p></div>';
			}
			elseif(!isset($_POST['customer_id']) && !isset($_POST['manager_user_id']))
			{
				echo '<div id="message" class="error"><p>'.esc_html__('Choose at least 1 agent and/or 1 customer.', 'woocommerce-support-ticket-system').'</p></div>';
			}
		}
		
		
		wp_enqueue_style( 'wcsts-common', WCSTS_PLUGIN_PATH.'/css/backend-common.css');
		wp_enqueue_style( 'wcsts-customer-agents-association-ticket', WCSTS_PLUGIN_PATH.'/css/backend-ticket-assign-page.css');
		wp_enqueue_style( 'wcsts-select2-style',  WCSTS_PLUGIN_PATH.'/css/vendor/select2/select2.css' ); 
	
		wp_register_script('wcsts-customer-agents-association-ticket', WCSTS_PLUGIN_PATH.'/js/backend-customers-agents-association.js', array('jquery'));	
				$translation_array = array(
						'user_ids_missing_message' => esc_html__( 'No Agents has been select, in this way the previous agent(s) assignements will be removed. Do you want to procede?', 'woocommerce-support-ticket-system' ),
						'customer_ids_missing_message' => esc_html__( 'No Customer has been select, in this way all tickets assigned to the selected user(s) will be removed. Do you want to procede?', 'woocommerce-support-ticket-system' )
		);
		wp_localize_script( 'wcsts-customer-agents-association-ticket', 'wcsts', $translation_array );

		wp_enqueue_script('wcsts-customer-agents-association-ticket');			
		wp_enqueue_script('wcsts-customer-agents-association-ticket-loader-tickets', WCSTS_PLUGIN_PATH.'/js/backend-ticket-assigner-loader-tickets.js', array('jquery'));			
		wp_enqueue_script('wcsts-loader-customers-list', WCSTS_PLUGIN_PATH.'/js/backend-load-customer-list.js', array('jquery'));			
		wp_enqueue_script('wcsts-select2', WCSTS_PLUGIN_PATH.'/js/vendor/select2/select2.min.js', array('jquery'));			
		?>
		
		<div class="wrap white-box">
		<form action="" method="post" id="wcsts_assign_tickets_form">
			<h1 ><?php esc_html_e('How it works?', 'woocommerce-support-ticket-system');?></h1>
			<p><?php wcsts_html_escape_allowing_special_tags(__('This menu allows you to associate customers with agents. In this way, tickets opened by a user will be automatically assigned to the associated agent.', 'woocommerce-support-ticket-system'));?></p>
			<h2 class="wcsts_title_with_border"><?php esc_html_e('1. Select customers', 'woocommerce-support-ticket-system');?></h2>
			<p><?php wcsts_html_escape_allowing_special_tags(__('You can search via login, first and last name', 'woocommerce-support-ticket-system'));?></p>
			<select class="js-data-customers-ajax" id="wcsts_select2_customer_id" name="customer_id[]" multiple="multiple"> </select>
		
			
			<h2 class="wcsts_title_with_border"><?php esc_html_e('2. Select administrator', 'woocommerce-support-ticket-system');?></h2>
			<p><?php wcsts_html_escape_allowing_special_tags(__('You can search by administrator id, name, surname or email. <strong>NOTE:</strong> to reset agents assignements, leave empty this field and just select the customer in the previous step for which you want to reset the assignment.', 'woocommerce-support-ticket-system'));?></p>
			<?php $wcsts_html_helper->render_multiple_user_selector(array(), array('disable_notification_area' => true)); ?>
			
			
									
			<p class="submit">
						<input type="submit" value="Save Changes" class="button-primary" name="Submit">
					</p>
			</form>
		</div>
		<?php
	}
}
?>