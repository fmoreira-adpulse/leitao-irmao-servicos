<?php 
class WCSTS_CustomStatusConfiguratorPage
{
	public function __construct(){}
	
	public function render_page()
	{
		global $wcsts_ticket_model, $wcsts_html_helper ;
		$print_result_message = false;
		if(isset($_POST) && !empty($_POST['wcts_statuses']))
		{
			$wcsts_ticket_model->save_statuses($_POST['wcts_statuses']);
			{
				$print_result_message = true;
			}
			
		}
		
		$statuses = $wcsts_ticket_model->get_available_statuses();
		wp_enqueue_style( 'wcsts-common', WCSTS_PLUGIN_PATH.'/css/backend-common.css');
		wp_enqueue_style( 'wcsts-custom-status', WCSTS_PLUGIN_PATH.'/css/backend-custom-status-page.css');
	
		wp_register_script('wcsts-custom-status', WCSTS_PLUGIN_PATH.'/js/backend-custom-status.js', array('jquery'));	
				$translation_array = array(
						'remove_custom_status_text' => esc_html__( 'Are you sure you want to remove it?', 'woocommerce-support-ticket-system' )
		);
		wp_localize_script( 'wcsts-custom-status', 'wcsts', $translation_array );
		wp_enqueue_script('wcsts-custom-status');	
		?>
		<?php if($print_result_message ): ?>
			<div id="message" class="updated"><p><?php esc_html_e('Operation successfully performed.', 'woocommerce-support-ticket-system'); ?></p></div>
		<?php endif; ?>
		<form action="" method="post" id="wcsts_assign_tickets_form">
			<div class="white-box">
								
					<h2 class="wcsts_title_with_border"><?php esc_html_e('Default statuses', 'woocommerce-support-ticket-system');?></h2>
					<p><?php esc_html_e('Default statuses cannot be deleted.', 'woocommerce-support-ticket-system');?></p>
					<?php 
						foreach($statuses as $id => $status)
							if(!$status['is_custom'])
							{
								$wcsts_html_helper->render_status_configuration($status);
							}
					?>
			
				<h2 class="wcsts_title_with_border_with_margin"><?php esc_html_e('Custom statuses', 'woocommerce-support-ticket-system');?></h2>
				<p><?php esc_html_e('You can add or delete custom statuses in addition to existing ones.', 'woocommerce-support-ticket-system');?></p>
				<div id="wcsts_custom_statuses_container">
					<?php 
						foreach($statuses as $id => $status)
							if($status['is_custom'])
							{
								$wcsts_html_helper->render_status_configuration($status);
							}
					?>
				</div>
				<img id="wcsts_preloader_image" src="<?php echo WCSTS_PLUGIN_PATH.'/images/horizontal-15.gif' ?>" ></img>
				<button class="button-primary" id="wcsts_add_new_custom_status_button"><?php esc_html_e('Add new custom status', 'woocommerce-support-ticket-system');?></button>		
			
				<p class="submit">
					<input type="submit" id="wcsts_submit_button" value="<?php esc_html_e('Save', 'woocommerce-support-ticket-system');?>" class="button-primary" />
				</p>
			</div>
		</form>
		
		<?php
	}
}
?>