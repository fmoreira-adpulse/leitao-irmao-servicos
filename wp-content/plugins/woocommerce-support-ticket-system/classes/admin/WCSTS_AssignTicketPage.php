<?php
class WCSTS_AssignTicketPage
{
	public function __construct() {}

	private function save_data()
	{
		global $wcsts_user_model, $wcsts_ticket_model, $wcsts_email_model;
		$user_ids = isset($_POST['manager_user_id']) ? $_POST['manager_user_id'] : array();
		$ticket_ids = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : array();
		$notify_user = isset($_POST['notification_action']) && $_POST['notification_action'] == 'yes' ?  true : false;

		if (empty($ticket_ids))
			$wcsts_ticket_model->remove_manager_user_assigned_to_any_ticket($user_ids);

		//Always performed in order to remove older assignment
		$wcsts_ticket_model->remove_all_manager_users_assigned_to_tickets($ticket_ids);

		if (!empty($user_ids) && !empty($ticket_ids))
			$wcsts_ticket_model->assign_manager_users_to_tickets($ticket_ids, $user_ids);

		if ($notify_user && !empty($user_ids) && !empty($ticket_ids))
			$wcsts_email_model->send_manager_user_assigned_ticket_notification($ticket_ids, $user_ids);
	}
	public static function force_dequeue_scripts($enqueue_styles)
	{
		if (class_exists('woocommerce') && isset($_GET['page']) && $_GET['page'] == 'wcsts-assign-ticket') {
			global $wp_scripts;
			$wp_scripts->queue = array();
			WCSTS_AssignTicketPage::enqueue_scripts();
		}
	}
	public static function enqueue_scripts()
	{
		if (class_exists('woocommerce') && isset($_GET['page']) && $_GET['page'] == 'wcsts-assign-ticket') {
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-slider');
			wp_enqueue_script('jquery-ui-progressbar');
		}
	}
	public function render_page()
	{
		global $wcsts_html_helper;
		if (isset($_POST) && !empty($_POST)) {
			if (isset($_POST['ticket_id']) || isset($_POST['manager_user_id'])) {
				$this->save_data();
				echo '<div id="message" class="updated"><p>' . esc_html__('Operation successfully performed.', 'woocommerce-support-ticket-system') . '</p></div>';
			} elseif (!isset($_POST['ticket_id']) && !isset($_POST['manager_user_id'])) {
				echo '<div id="message" class="error"><p>' . esc_html__('Choose at least 1 agent and/or 1 ticket.', 'woocommerce-support-ticket-system') . '</p></div>';
			}
		}


		wp_enqueue_style('wcsts-common', WCSTS_PLUGIN_PATH . '/css/backend-common.css');
		wp_enqueue_style('wcsts-assign-ticket', WCSTS_PLUGIN_PATH . '/css/backend-ticket-assign-page.css');
		wp_enqueue_style('wcsts-select2-style',  WCSTS_PLUGIN_PATH . '/css/vendor/select2/select2.css');

		wp_register_script('wcsts-assign-ticket', WCSTS_PLUGIN_PATH . '/js/backend-ticket-assigner.js', array('jquery'));
		$translation_array = array(
			'user_ids_missing_message' => esc_html__('No agent has been select, in this way the previous ticket(s) assignements will be removed. Do you want to procede?', 'woocommerce-support-ticket-system'),
			'ticket_ids_missing_message' => esc_html__('No ticket has been select, in this way all tickets assigned to the selected agent(s) will be removed. Do you want to procede?', 'woocommerce-support-ticket-system')
		);
		wp_localize_script('wcsts-assign-ticket', 'wcsts', $translation_array);

		wp_enqueue_script('wcsts-assign-ticket');
		wp_enqueue_script('wcsts-assign-ticket-loader-tickets', WCSTS_PLUGIN_PATH . '/js/backend-ticket-assigner-loader-tickets.js', array('jquery'));
		wp_enqueue_script('wcsts-select2', WCSTS_PLUGIN_PATH . '/js/vendor/select2/select2.min.js', array('jquery'));
?>

		<div class="wrap white-box">
			<form action="" method="post" id="wcsts_assign_tickets_form">
				<h1><?php esc_html_e('How it works?', 'woocommerce-support-ticket-system'); ?></h1>
				<p><?php wcsts_html_escape_allowing_special_tags(__('In this section in just 3 step you can easily assign one or more tickets to an agent.<br/><strong>NOTE:</strong> older tickets assignments will be overwritten if you assign new users.', 'woocommerce-support-ticket-system')); ?></p>
				<h2 class="wcsts_title_with_border"><?php esc_html_e('1. Select agents', 'woocommerce-support-ticket-system'); ?></h2>
				<p><?php wcsts_html_escape_allowing_special_tags(__('You can search by agents id, name, surname or email. <strong>NOTE:</strong> to reset previous ticket agent assignements, leave empty this field and then select the tickets you need in the step #2.', 'woocommerce-support-ticket-system')); ?></p>
				<?php $wcsts_html_helper->render_multiple_user_selector(); ?>

				<h2 class="wcsts_title_with_border"><?php esc_html_e('2. Select tickets', 'woocommerce-support-ticket-system'); ?></h2>
				<p><?php wcsts_html_escape_allowing_special_tags(__('You can search by ticket id. <strong>NOTE:</strong> to reset all ticket assigned to an agent, in the previous step select the agent(s) you need and the leave empty this field.', 'woocommerce-support-ticket-system')); ?></p>
				<select class="js-data-tickets-ajax" id="wcsts_select2_ticket_id" name="ticket_id[]" multiple="multiple"> </select>

				<h2 class="wcsts_title_with_border"><?php esc_html_e('3. Notify users?', 'woocommerce-support-ticket-system'); ?></h2>
				<p><?php esc_html_e('You can optionally send a notification email to the users to let them know that they have bee assigned tickets.', 'woocommerce-support-ticket-system'); ?></p>

				<p>
					<label><?php esc_html_e('Send notification email', 'woocommerce-support-ticket-system'); ?></label>
					<select name="notification_action">
						<option value="yes" selected><?php esc_html_e('Yes', 'woocommerce-support-ticket-system'); ?></option>
						<option value="no"><?php esc_html_e('No', 'woocommerce-support-ticket-system'); ?></option>
					</select>
				</p>

				<p class="submit">
					<input type="submit" value="Save Changes" class="button-primary" name="Submit">
				</p>
			</form>
		</div>
<?php
	}
}
?>