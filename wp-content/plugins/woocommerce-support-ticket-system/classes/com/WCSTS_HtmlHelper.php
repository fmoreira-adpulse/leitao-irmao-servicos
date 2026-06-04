<?php
class WCSTS_HtmlHelper
{
	public function __construct()
	{
		add_action('wp_ajax_wcsts_load_new_custom_status_configuration_box', array(&$this, 'load_new_custom_status_configuration_box'));
		add_action('admin_init', array($this, 'init'));
	}
	public function init()
	{
		if (isset($_REQUEST['wcsts_shortcode_page'])) {
			$this->render_shortcode_info();
			wp_die();
		}
	}
	public function render_shortcode_info()
	{
?>
		<p><?php esc_html_e('You can use the following shortcodes that will be replaced with user account and order datails (only if the current ticket is an order ticket type) data.', 'woocommerce-support-ticket-system'); ?></p>
		<h3><?php esc_html_e('Account', 'woocommerce-support-ticket-system'); ?></h3>
		[account_first_name], [account_last_name], [account_email]


		<h3><?php wcsts_html_escape_allowing_special_tags(__('Order (<i>only for order type tickets</i>)', 'woocommerce-support-ticket-system')); ?></h3>
		[order_id], [order_total], [order_date]
		<h3><?php wcsts_html_escape_allowing_special_tags(__('Billing (<i>only for order type tickets</i>)', 'woocommerce-support-ticket-system')); ?></h3>
		[billing_first_name], [billing_last_name], [billing_email], [billing_company], [billing_company], [billing_phone], [billing_country], [billing_state], [billing_city], [billing_post_code], [billing_address_1], [billing_address_2], [formatted_billing_address]
		<h3><?php wcsts_html_escape_allowing_special_tags(__('Billing (<i>only for order type tickets</i>)', 'woocommerce-support-ticket-system')); ?></h3>
		[shipping_first_name], [shipping_last_name], [shipping_company], [shipping_phone], [shipping_country], [shipping_state], [shipping_city], [shipping_post_code], [shipping_address_1], [shipping_address_2], [formatted_shipping_address]
	<?php
	}
	public function render_multiple_user_selector($selected = array(), $options = array())
	{
		global $wcsts_user_model, $wcsts_option_helper;

		wp_enqueue_script('wcsts-assign-ticket-loader-users', WCSTS_PLUGIN_PATH . '/js/backend-ticket-assigner-loader-users.js', array('jquery'));
		$ticket_visibility =  $wcsts_option_helper->get_all_options('ticket_visibility', 'all_tickets');
	?>
		<?php if ($wcsts_user_model->is_current_user_administrator() || $ticket_visibility == 'all_tickets'): ?>
			<select class="js-data-users-ajax" id="wcsts_select2_user_id" name="manager_user_id[]" multiple="multiple">
				<?php if (!empty($selected))
					foreach ($selected as $user_id) {
						$user_data = $wcsts_user_model->get_user_data($user_id);
						$first_last_name = $user_data->first_name . $user_data->last_name != "" ? $user_data->first_name . " " . $user_data->last_name : "N/A";
						$user_option_label = "#" . $user_data->ID . " - Login: " . $user_data->user_login . " - First and last name: " . $first_last_name . " - Email:" . $user_data->user_email;
						echo '<option value="' . $user_id . '" selected="selected" >' . $user_option_label . '</option>';
					}
				?>
			</select>
			<?php if (!wcsts_get_value_if_set($options, 'disable_notification_area', false)): ?>
				<p>
				<h4><?php esc_html_e('Notify users about the ticket assignment?', 'woocommerce-support-ticket-system'); ?></h4>
				<select name="notification_action">
					<option value="yes" selected><?php esc_html_e('Yes', 'woocommerce-support-ticket-system'); ?></option>
					<option value="no"><?php esc_html_e('No', 'woocommerce-support-ticket-system'); ?></option>
				</select>
				</p>
			<?php endif; ?>
		<?php else: ?>
			<p><?php esc_html_e('Only the administrator can assign the ticket managment.', 'woocommerce-support-ticket-system'); ?></p>
			<input type="hidden" name="manager_user_id[]" value="<?php echo get_current_user_id(); ?>" />
		<?php
		endif;
	}
	public function frontend_ticket_area($order = null, $is_ajax = false, $display_header = true, $extra_parameters = array())
	{
		global $wcsts_order_model;

		$list_all_ppt_tickets_of_the_current_user = false;
		$allow_guest = isset($extra_parameters['allow_guest']) ?  $extra_parameters['allow_guest'] : false; //Only invoked by the "order received page" with guest users
		if (!is_user_logged_in() && !$allow_guest) {
			$not_logged_message = isset($extra_parameters['not_logged_message']) ?  $extra_parameters['not_logged_message'] : "";
			if ($not_logged_message == "")
				wcsts_html_escape_allowing_special_tags(sprintf(__('Please <a href="%s">login</a>.', 'woocommerce-support-ticket-system'), get_permalink(get_option('woocommerce_myaccount_page_id'))));
			else
				echo $not_logged_message;
			return;
		}

		global $wcsts_ticket_model, $wcsts_ticket_message_model, $wcsts_text_helper, $wcsts_option_helper, $sitepress;
		$user_id = get_current_user_id();

		$ticket_type = isset($order) ? 'order' : 'user';

		if (isset($order)) {
			$ticket_type = $wcsts_order_model->has_ppt_ticket_associated($order->get_id()) ? 'ppt' : 'order';
		}
		if (!$allow_guest && !$user_id)
			return;

		if ($allow_guest && $ticket_type == 'user')
			return;

		$sorting_order = isset($_GET['wcsts_sort_by_date']) ? $_GET['wcsts_sort_by_date'] : 'desc';
		$current_visible_status = isset($_GET['wcsts_filter_by_status']) ? $_GET['wcsts_filter_by_status'] : 'all';
		$default_sorting = 'desc';

		//Used by the [wcsts_ticket_area] shortcode
		if (wcsts_get_value_if_set($extra_parameters, 'list_all_ppt_tickets_of_the_current_user', false)) {
			$ticket_type = "ppt";
			$messages_by_ticket = $wcsts_ticket_model->get_ticket_messages_by_user_id_and_type($user_id, $sorting_order, $ticket_type);
			$list_all_ppt_tickets_of_the_current_user = true;
		} else {
			switch ($ticket_type) {
				case 'ppt':
					$messages_by_ticket = $wcsts_ticket_model->get_ppt_ticket_messages_by_order_id(WCSTS_Order::get_id($order), $sorting_order);
					break;
				case 'order':
					$messages_by_ticket = $wcsts_ticket_model->get_ticket_messages_by_order_id(WCSTS_Order::get_id($order), $sorting_order);
					break;
				case 'user':
					$messages_by_ticket = $wcsts_ticket_model->get_ticket_messages_by_user_id_and_type($user_id, $sorting_order);
					break;
			}
		}

		$date_format = get_option('date_format');
		$time_format = get_option('time_format');
		$customized_texts = $wcsts_text_helper->get_texts();
		$options = $wcsts_option_helper->get_all_options();
		$attachment_box_id = 0;
		$staff_label = $customized_texts['staff_label_text'];
		$order_ticket_max_number = $options['order_ticket_limit'];
		$current_number_of_opened_order_tickets = count($messages_by_ticket);
		$new_tickets_can_be_opened = $ticket_type != 'order' || $order_ticket_max_number < 0 ||  $current_number_of_opened_order_tickets < $order_ticket_max_number;
		$new_tickets_can_be_opened = $ticket_type == 'user' && $options['disable_user_ticket_opening'] ? false : $new_tickets_can_be_opened;
		$new_tickets_can_be_opened = wcsts_get_value_if_set($extra_parameters, array('shortcode_atts', 'open_new_ticket'), "1") == "0" ? false : $new_tickets_can_be_opened;

		$disable_user_reply_until_admin_message = array('order' => $options['order_ticket_disable_user_reply_until_admin_message'], 'user' => $options['user_ticket_disable_user_reply_until_admin_message'], 'ppt' => false);

		$subject_type = $wcsts_text_helper->get_topic_type($ticket_type . '_ticket_subject_type');

		$display_ticket_status = $options['display_ticket_status_on_frontend'] == 'yes' ? true : false;
		$available_ticket_statueses = $wcsts_ticket_model->get_available_statuses();

		$show_order_status =  $options['display_order_status_on_order_tickets'];


		$max_file_size_text = ($options['max_file_size'] / 1024) < 1 ? floor($options['max_file_size']) . "kb" : floor($options['max_file_size'] / 1024) . "MB";

		if (!$is_ajax) {
			//CSS
			wp_enqueue_style('wcsts-ticket-area', WCSTS_PLUGIN_PATH . '/css/frontend-ticket-area.css');
			wp_enqueue_style('wcsts-ticket-area-pagination', WCSTS_PLUGIN_PATH . '/css/frontend-pagination.css');
			wp_enqueue_style('dashicons');

			//JS
			wp_enqueue_script('tinymce_js', includes_url('js/tinymce/') . 'wp-tinymce.php', array('jquery'), false, true);
			wp_register_script('wcsts-ticket-area', WCSTS_PLUGIN_PATH . '/js/frontend-ticket-area.js', array('jquery'));
			wp_register_script('wcsts-ticket-area-new-message', WCSTS_PLUGIN_PATH . '/js/frontend-ticket-area-new-message.js', array('jquery'));
			wp_register_script('wcsts-ticket-area-new-ticket', WCSTS_PLUGIN_PATH . '/js/frontend-ticket-area-new-ticket.js', array('jquery'));
			wp_register_script('wcsts-paging', WCSTS_PLUGIN_PATH . '/js/vendor/paging/paging.js', array('jquery'));
			wp_register_script('wcsts-frontend-upload-manager', WCSTS_PLUGIN_PATH . '/js/frontend-upload-manager.js', array('jquery'));
			wp_register_script('wcsts-frontend-uploader', WCSTS_PLUGIN_PATH . '/js/frontend-uploader.js', array('jquery'));

			$js_variables = array(
				'wcsts_ajax_url' => admin_url('admin-ajax.php'),
				'wcsts_empty_message_error' => esc_html__('Message cannot be empty', 'woocommerce-support-ticket-system'),
				'wcsts_empty_subject_error' => esc_html__('Subject cannot be empty', 'woocommerce-support-ticket-system'),
				'wcsts_file_size_error' => esc_html__('The file size excedes the size limit of: ', 'woocommerce-support-ticket-system'),
				'wcsts_browser_compliant_error' => esc_html__('Your browser is not HTML5 compliant ', 'woocommerce-support-ticket-system'),
				'wcsts_extension_error' => esc_html__('The your are trying to upload files with a not allowed extension', 'woocommerce-support-ticket-system'),
				'wcsts_upload_still_in_progress' => esc_html__('Please wait, upload still in progress.', 'woocommerce-support-ticket-system'),
				'wcsts_minimum_chars_warning' => esc_html__('Please wait, the message cannot be shorter than %s characters.', 'woocommerce-support-ticket-system'),
				'wcsts_new_ticket_result_message' => $customized_texts['new_ticket_succesfully_submitted_message'],
				'subject_max_chars' => $options['subject_lenght'],
				'message_max_chars' => $options['message_lenght'],
				'message_min_chars' => $options['message_min_lenght'],
				'wpml_language' => isset($sitepress) ? $sitepress->get_current_language() : "en",
				'expand_button_text' => esc_html__('Expand', 'woocommerce-support-ticket-system'),
				'collapse_button_text' => esc_html__('Collapse', 'woocommerce-support-ticket-system'),
				'current_sort' => $sorting_order,
				'tiny_mce' => $options['frontend_use_tiny_mce'],
				'item_per_page' => $options['ticket_area_pagination'],
				'order_ticket_max_number' => $order_ticket_max_number,
				'current_number_of_opened_order_tickets'  => $current_number_of_opened_order_tickets,
				'security' =>  wp_create_nonce('wcsts_security')

			);
			wp_localize_script('wcsts-ticket-area', 'wcsts', $js_variables);
			wp_localize_script('wcsts-ticket-area-new-message', 'wcsts', $js_variables);
			wp_localize_script('wcsts-ticket-area-new-ticket', 'wcsts', $js_variables);
			wp_localize_script('wcsts-frontend-uploader', 'wcsts', $js_variables);

			wp_enqueue_script('wcsts-ticket-area');
			wp_enqueue_script('wcsts-ticket-area-new-message');
			wp_enqueue_script('wcsts-ticket-area-new-ticket');
			wp_enqueue_script('wcsts-paging');
			wp_enqueue_script('wcsts-frontend-upload-manager');
			wp_enqueue_script('wcsts-frontend-uploader');
		}
		if (!$is_ajax): ?>

			<div id="wcsts_ticket_area" <?php if (!$display_header) echo 'style="margin-top:0px;"'; ?>>
				<?php if ($display_header): ?>
					<header class="wcsts_contact_us_header">
						<h2 class="wcsts_contact_us_title"><?php esc_html_e('Contact us', 'woocommerce-support-ticket-system'); ?></h2>
					</header>
				<?php endif; ?>

				<!-- /Options block -->
				<form id="wcsts_ticket_area_options_form" action="">
					<input type="hidden" name="wcsts_get_help" value="true"></input>
					<div class="wcsts_option_block">
						<label for="wcsts_sort_by_date_menu" class="wcsts_option_label"><?php esc_html_e('Sort tickets from:', 'woocommerce-support-ticket-system'); ?></label>
						<select name="wcsts_sort_by_date" id="wcsts_sort_by_date_menu">
							<!-- <option value="default" <?php if ($sorting_order == '') echo 'selected="selected"'; ?>><?php esc_html_e('Default sorting', 'woocommerce-support-ticket-system'); ?></option> -->
							<option value="desc" <?php if ($sorting_order == 'desc') echo 'selected="selected"'; ?>><?php esc_html_e('Newer to older', 'woocommerce-support-ticket-system'); ?></option>
							<option value="asc" <?php if ($sorting_order == 'asc') echo 'selected="selected"'; ?>><?php esc_html_e('Older to newer', 'woocommerce-support-ticket-system'); ?></option>
						</select>
					</div>
					<?php if ($display_ticket_status): ?>
						<div class="wcsts_option_block">
							<label for="wcsts_filter_by_status_menu" class="wcsts_option_label"><?php esc_html_e('Filter by status:', 'woocommerce-support-ticket-system'); ?></label>
							<select name="wcsts_filter_by_status" id="wcsts_filter_by_status_menu">
								<option value="all" <?php if ($current_visible_status == 'all') echo 'selected="selected"'; ?>><?php esc_html_e('Show all', 'woocommerce-support-ticket-system'); ?>
									<?php foreach ($available_ticket_statueses as $available_status_code => $available_status_text) {
										$is_selected = $current_visible_status == $available_status_code ? ' selected="selected" ' : "";
										echo '<option value="' . $available_status_code . '" ' . $is_selected . ' >' . $available_status_text["label"][$available_status_text['current_lang']] . '</option>';
									}
									?>
							</select>
						</div>
					<?php endif; ?>
				</form>
				<!-- /Options block -->

				<?php if ($ticket_type != 'ppt' && $new_tickets_can_be_opened): ?>
					<button id="wcsts_new_ticket_button_redirect" class="button wcsts_button"><?php esc_html_e('New ticket', 'woocommerce-support-ticket-system'); ?></button>
				<?php endif; ?>
				<div id="wcsts_tickets_container">
				<?php endif; //!is_ajax 
				?>
				<input id="wcsts_type_id" type="hidden" value="<?php echo ($ticket_type == 'order' || ($ticket_type == 'ppt' && !$list_all_ppt_tickets_of_the_current_user)) ? WCSTS_Order::get_id($order) : $user_id; ?>"></input>
				<input id="wcsts_type" type="hidden" value="<?php echo $ticket_type; ?>"></input>
				<input id="list_all_ppt_tickets_of_the_current_user" type="hidden" value="<?php echo $list_all_ppt_tickets_of_the_current_user ? 'yes' : 'no'; ?>"></input>
				<div id="wcsts_ticket_pagination_container">
					<?php $message_box_id = 0;
					foreach ($messages_by_ticket as $ticket_id => $messages):

						$ticket_status_data = $wcsts_ticket_model->get_status_data($ticket_id);
						$status_text = $ticket_status_data['label'][$ticket_status_data['current_lang']];
						$ticket_status = $ticket_status_data['id'];
						$creation_date = $wcsts_ticket_model->get_creation_date($ticket_id);
						$ppt_questions_left = $wcsts_ticket_model->get_attributes($ticket_id, 'number_of_questions_left', 0);
						$ppt_product_name = $wcsts_ticket_model->get_attributes($ticket_id, 'ppt_product_name', "");
						$unread_messages = $wcsts_ticket_model->count_new_admin_messages($ticket_id);
						$can_reply = $options['deny_closed_ticket_reply'] && $ticket_status == 'closed' ? false : true;

						//Unread messages managment
						$admin_total_replies = $admin_replies_counter = 0;
						foreach ($messages as $message)
							if (!$message->is_customer_message)
								$admin_total_replies++;

						$admin_unread_message_base_index = $admin_total_replies - $unread_messages + 1;
						$admin_replies_counter = 0;

						$order_status_text = $ticket_type == 'order' && $show_order_status ? sprintf(", <strong>%s</strong>", esc_html__('Order status: ', 'woocommerce-support-ticket-system')) . wc_get_order_status_name($order->get_status()) . " " : "";

						//Filter by status (if active)
						if ($current_visible_status != 'all' && $current_visible_status != $ticket_status)
							continue;
					?>
						<!-- single ticket container -->
						<div class="wcsts_single_ticket_container">
							<span class="wcsts_ticket_subject"><?php if ($ticket_type == 'ppt') echo "#" . $ticket_id . " - " . $ppt_product_name;
																else echo $wcsts_ticket_model->get_subject($ticket_id); ?> <?php if ($unread_messages > 0) echo '<span class="wcsts_unread_admin_messages">' . $unread_messages . '</span> '; ?> <button class="wcts_expand_button button wcsts_button" data-ticket-id="<?php echo $ticket_id; ?>" data-id="<?php echo $message_box_id; ?>"><?php if ($options['ticket_conversation_is_expansed']) esc_html_e('Collapse', 'woocommerce-support-ticket-system');
																																																																																																																else esc_html_e('Expand', 'woocommerce-support-ticket-system'); ?></button></span>
							<span class="wcsts_ticket_status">
								<?php if ($display_ticket_status) echo "<strong>" . esc_html__('Status:', 'woocommerce-support-ticket-system') . '</strong> <span style="background:' . $ticket_status_data['background_color'] . '; color:' . $ticket_status_data['text_color'] . ';" class="wcsts_status_box wcsts_status_' . $ticket_status . '">' . $status_text . '</span>, ';
								echo "<strong>" . esc_html__('ID:', 'woocommerce-support-ticket-system') . "</strong>." . $ticket_id . ", <strong>" . esc_html__('Number of messages:', 'woocommerce-support-ticket-system') . "</strong> " . count($messages) . ", <strong>" . esc_html__('Created on:', 'woocommerce-support-ticket-system') . "</strong> " . $creation_date . $order_status_text;  ?>
								<?php if ($options['display_ticket_priority_selector_on_frontend'] == 'yes'): ?>
									<?php echo ", <strong>" . esc_html__('Priority:', 'woocommerce-support-ticket-system') . "</strong> " . $wcsts_ticket_model->get_priority($ticket_id); ?>
								<?php endif; ?>
								<?php if ($ticket_type == 'ppt')
									echo ", <strong>" . esc_html__('Questions left:', 'woocommerce-support-ticket-system') . "</strong> " . $ppt_questions_left ?>

							</span>
							<div id="wcsts_messages_box_<?php echo $message_box_id; ?>" class="wcsts_messages_box" <?php if ($options['ticket_conversation_is_expansed']): ?>style="display:block;" <?php endif; ?>>
								<?php
								$last_reply_from_admin = false;
								foreach ($messages as $message):
									if ($message->is_customer_message):
										$last_reply_from_admin = false;
										$attachments = $wcsts_ticket_message_model->get_attachments($message->ID); ?>
										<div class="wcsts_ticket_message_content wcsts_customer_ticket_message_content">
											<span class="wcsts_customer_message_details wcsts_message_details">
												<strong><?php esc_html_e('You on', 'woocommerce-support-ticket-system'); ?></strong><br />
												<?php echo date_i18n($date_format . " " . $time_format, strtotime($message->post_date)); ?>
											</span>
											<div class="wcsts_customer_message wcsts_message">
												<p><?php echo wcsts_restore_paragraph_breaks($message->post_content); ?></p>
												<?php if (!empty($attachments)): ?>
													<div class="wcsts_attachments_container">
														<?php $attachment_counter = 0;
														foreach ($attachments as $attachment_unique_value => $attachment_url):
															$attachment_box_id++; ?>
															<div class="wcts_single_attachment" id="wcsts_single_attachment_<?php echo $attachment_box_id; ?>">
																<span class="wcts_attachment_title"><?php echo sprintf(esc_html__('Attachment %d', 'woocommerce-support-ticket-system'), ++$attachment_counter); ?>: </span>
																<a class="dashicons dashicons-paperclip" href="<?php echo $attachment_url; ?>" target="_blank" download></a>
															</div>
														<?php endforeach; //attachments 
														?>
													</div>
												<?php endif; //!empty($attachments) 
												?>
											</div>
										</div>
									<?php //Admin replies
									else:
										$admin_replies_counter++;
										$last_reply_from_admin = true; ?>
										<div class="wcsts_ticket_message_content wcsts_admin_ticket_message_content <?php if ($unread_messages != 0 && $admin_replies_counter >= $admin_unread_message_base_index) echo 'wcsts_ticket_unread_message_content'; ?>">
											<span class="wcsts_admin_message_details wcsts_message_details">
												<strong><?php echo $staff_label . esc_html__(' on', 'woocommerce-support-ticket-system'); ?></strong><br />
												<?php echo date_i18n($date_format . " " . $time_format, strtotime($message->post_date)); ?>
											</span>
											<div class="wcsts_admin_message wcsts_message ">
												<p><?php echo wcsts_restore_paragraph_breaks($message->post_content); ?></p>
												<?php
												$attachments = $wcsts_ticket_message_model->get_attachments($message->ID);
												if (!empty($attachments)): ?>
													<div class="wcsts_attachments_container">
														<?php $attachment_counter = 0;
														foreach ($attachments as $attachment_unique_value => $attachment_url):
															$attachment_box_id++; ?>
															<div class="wcts_single_attachment" id="wcsts_single_attachment_<?php echo $attachment_box_id; ?>">
																<span class="wcts_attachment_title"><?php echo sprintf(esc_html__('Attachment %d', 'woocommerce-support-ticket-system'), ++$attachment_counter); ?>: </span>
																<a class="dashicons dashicons-paperclip" href="<?php echo $attachment_url; ?>" target="_blank" download></a>
															</div>
														<?php endforeach; //attachments 
														?>
													</div>
												<?php endif; //!empty($attachments) 
												?>
											</div>
										</div>
								<?php endif;
								endforeach; //messages 
								?>
								<!-- Reply area -->
								<?php
								$can_reply = $disable_user_reply_until_admin_message[$ticket_type] && !$last_reply_from_admin ? false : $can_reply;
								if (($ticket_type != 'ppt' || $ppt_questions_left > 0) && $can_reply): ?>
									<div class="wcsts_new_message_container" id="wcsts_new_message_container_<?php echo $ticket_id; ?>">
										<span class="wcsts_new_message_label"><?php esc_html_e('New message', 'woocommerce-support-ticket-system'); ?> (<?php esc_html_e('Characters left:', 'woocommerce-support-ticket-system'); ?> <span id="wcsts_message_max_char_left_<?php echo $ticket_id; ?>" class="wcsts_message_max_char_left"><?php echo $options['message_lenght']; ?></span> <?php if ($options['message_min_lenght']) echo sprintf(esc_html__(', Min characters:  %s', 'woocommerce-support-ticket-system'), $options['message_min_lenght']); ?>)</span>
										<textarea id="wcsts_new_message_textarea_<?php echo $ticket_id; ?>" class="wcsts_new_message_textarea tinymce-enabled" data-id="<?php echo $ticket_id; ?>" maxlength="<?php echo $options['message_lenght']; ?>" minlength="<?php echo $options['message_min_lenght']; ?>"></textarea>
										<!-- attachment area -->
										<?php if ($options['allow_files_attachment']): ?>
											<span class="wcsts_attachments_label"><?php esc_html_e('Attachment(s)', 'woocommerce-support-ticket-system'); ?> <?php if ($options['max_file_size'] > 0) echo sprintf(esc_html__('(Max size: %s)', 'woocommerce-support-ticket-system'), $max_file_size_text); ?></span>
											<?php for ($i = 0; $i < $options['num_of_uploadable_files']; $i++):
												$current_file_id = $ticket_id . "_" . $i; ?>
												<div class="wcsts_input_attachment_container">
													<input type="file" <?php if ($options['allowed_file_types'] != '') echo 'accept="' . $options['allowed_file_types'] . '"'; ?>
														data-max-size="<?php echo $options['max_file_size']; //is already expressed in kb (otherwise it should be multiplied for *1024) 
																		?>"
														class="wcsts_attachment_input wcsts_new_message_attachment wcsts_new_message_attachment_group_<?php echo $ticket_id; ?>"
														data-clear-button="<?php echo  $current_file_id; ?>"
														data-id="<?php echo  $current_file_id; ?>"
														data-upload-button-id="#wcsts_file_upload_button_<?php echo  $current_file_id; ?>"
														data-delete-button-id="#wcsts_file_tmp_delete_button_<?php echo  $current_file_id; ?>"
														id="wcsts_input_file_<?php echo  $current_file_id; ?>"
														data-hide-index="<?php echo $i; ?>"
														data-main-container=".wcsts_new_message_container">
													</input>

													<!-- New managment -->
													<input type="hidden" class="wcsts_file_metadata_<?php echo  $ticket_id; ?>" id="wcsts-filename-<?php echo $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_name]" value=""></input>
													<input type="hidden" class="wcsts_file_metadata_<?php echo  $ticket_id; ?>" id="wcsts-filenameprefix-<?php echo  $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_name_tmp_prefix]" value=""></input>
													<input type="hidden" class="wcsts_file_metadata_<?php echo  $ticket_id; ?>" id="wcsts-complete-name-<?php echo  $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_complete_name]" value=""></input>
													<!-- File name display after upload -->
													<div class="wcsts_file_name_display_after_upload" id="wcsts-filename-display-<?php echo $current_file_id; ?>"></div>
													<!-- Upload button -->
													<button class="button wcsts_file_upload_button"
														id="wcsts_file_upload_button_<?php echo  $current_file_id; ?>"
														data-id="<?php echo  $current_file_id; ?>"
														data-upload-field-id="#wcsts_input_file_<?php echo  $current_file_id; ?>"><?php esc_html_e('Upload', 'woocommerce-support-ticket-system') ?></button>
													<!-- File name -->
													<span class="wcsts_file_tmp_name" id="wcsts_file_tmp_name_<?php echo  $current_file_id; ?>"></span>
													<!-- Delete button -->
													<button class="button wcsts_file_tmp_delete_button"
														id="wcsts_file_tmp_delete_button_<?php echo  $current_file_id; ?>"
														data-id="<?php echo  $current_file_id; ?>"
														data-file-to-delete=""><?php esc_html_e('Delete', 'woocommerce-support-ticket-system') ?> </button>
													<!-- Upload progress managment -->
													<div id="wcsts_upload_progress_status_container_<?php echo  $current_file_id; ?>" class="wcsts_upload_progress_status_container">
														<div class="wcsts_upload_progressbar" id="wcsts_upload_progressbar_<?php echo  $current_file_id; ?>"></div>
														<div class="wcsts_upload_progressbar_percent" id="wcsts_upload_progressbar_percent_<?php echo  $current_file_id; ?>">0%</div>
													</div>

												</div>
											<?php endfor; ?>
										<?php endif; ?>

										<button class="button wcsts_submit_new_message_button wcsts_button" data-id="<?php echo $ticket_id; ?>" data-message-box-id="<?php echo $message_box_id; ?>"><?php esc_html_e('Submit new message', 'woocommerce-support-ticket-system'); ?></button>
									</div>

									<div class="wcsts_sending_message_status" id="wcsts_sending_message_status_<?php echo $ticket_id; ?>"><img class="wcsts_preloader_image" src="<?php echo WCSTS_PLUGIN_PATH . '/images/loader.gif' ?>"></img></div>
									<?php if (isset($extra_parameters['ticket_message_post_time_error'])): ?>
										<div class="wcsts_error_box wcsts_new_message_area_error_box">
											<?php echo sprintf(esc_html__('Ticket can be opened every %d seconds', 'woocommerce-support-ticket-system'), $extra_parameters['ticket_message_post_time_interval']); ?>
										</div>
									<?php endif; ?>


									<button class="button wcsts_show_new_message_area_button wcsts_button" id="wcsts_show_new_message_area_button_<?php echo $ticket_id; ?>" data-id="<?php echo $ticket_id; ?>"><?php esc_html_e('Add new message', 'woocommerce-support-ticket-system'); ?></button>
								<?php else: ?>
									<div class="wcst_no_more_questions_message"><?php if ($ticket_type == 'ppt') echo $customized_texts['ppt_no_more_questions_left_message']; ?></div>
								<?php endif; //end $ticket_type 
								?>


							</div>
						</div> <!-- end single ticket container -->
					<?php $message_box_id++;
					endforeach; //messages_by_ticket 
					?>
				</div> <!-- wcsts_ticket_pagination_container -->
				<div id="wcsts_pagination_navigation"></div>

				<!-- New Ticket Box -->
				<?php if ($ticket_type != 'ppt' && $new_tickets_can_be_opened): ?>
					<div id="wcsts_new_ticket_box">
						<span id="wcsts_new_ticket_box_title"><?php esc_html_e('Submit a new ticket', 'woocommerce-support-ticket-system'); ?></span>
						<div id="wcsts_new_ticket_content">
							<div id="wcsts_new_ticket_description_box">
								<?php echo $customized_texts['new_ticket_description_text']; ?>
							</div>
							<!-- Subject -->
							<label class="wcsts_new_ticket_label"><?php esc_html_e('Subject', 'woocommerce-support-ticket-system'); ?> <?php if ($subject_type == 'text_input'): ?> (<?php esc_html_e('Characters left:', 'woocommerce-support-ticket-system'); ?> <span id="wcsts_subject_max_char_left"><?php echo $options['subject_lenght']; ?></span>) <?php endif; ?></label>
							<?php if ($subject_type == 'text_input'): ?>
								<input type="text" id="wcsts_new_ticket_subject" value="" maxlength="<?php echo $options['subject_lenght']; ?>"></input>
							<?php else:
								$topics = $wcsts_ticket_model->get_subject_topics_by_type($ticket_type); ?>
								<select id="wcsts_new_ticket_subject" name="wcsts_subject" class="wcsts_select" required="required">
									<?php foreach ($topics as $topic_id => $topic):  ?>
										<option value="<?php echo $topic_id; ?>"><?php echo $topic; ?></value>
										<?php endforeach; ?>
								</select>
							<?php endif; ?>

							<!-- Priority -->
							<?php if ($options['display_ticket_priority_selector_on_frontend'] == 'yes'): ?>
								<?php
								$priority_list = $wcsts_ticket_model->get_priority_list();
								if (!empty($priority_list)): ?>
									<label class="wcsts_new_ticket_label"><?php esc_html_e('Priority', 'woocommerce-support-ticket-system'); ?> </label>
									<select id="wcsts_new_ticket_priority" name="wcsts_priority" class="wcsts_select" required="required">
										<?php foreach ($priority_list as $priority_code => $priority_name): ?>
											<option value="<?php echo $priority_code; ?>"><?php echo $priority_name; ?></option>
										<?php endforeach; ?>
									</select>
								<?php endif; ?>
							<?php endif; ?>

							<!-- Message -->
							<label class="wcsts_new_ticket_label"><?php esc_html_e('Message', 'woocommerce-support-ticket-system'); ?> (<?php esc_html_e('Characters left:', 'woocommerce-support-ticket-system'); ?> <span id="wcsts_new_ticket_message_max_char_left"><?php echo $options['message_lenght']; ?></span> <?php if ($options['message_min_lenght']) echo sprintf(esc_html__(', Min characters:  %s', 'woocommerce-support-ticket-system'), $options['message_min_lenght']); ?>)</label>
							<textarea id="wcsts_new_ticket_message" class="wcsts_new_ticket_message tinymce-enabled" maxlength="<?php echo $options['message_lenght']; ?>" minlength="<?php echo $options['message_min_lenght']; ?>"></textarea>
							<!-- Attachment area -->
							<?php if ($options['allow_files_attachment']): ?>
								<span class="wcsts_attachments_label"><?php esc_html_e('Attachment(s)', 'woocommerce-support-ticket-system'); ?> <?php if ($options['max_file_size'] > 0) echo sprintf(esc_html__('(Max size: %s)', 'woocommerce-support-ticket-system'), $max_file_size_text); ?></span>
								<?php for ($i = 0; $i < $options['num_of_uploadable_files']; $i++):
									$current_file_id = "new_ticket_file_" . $i ?>
									<div class="wcsts_input_attachment_container">
										<input type="file" <?php if ($options['allowed_file_types'] != '') echo 'accept="' . $options['allowed_file_types'] . '"'; ?>
											data-max-size="<?php echo $options['max_file_size']; ?>"
											class="wcsts_attachment_input wcsts_new_ticket_attachment"
											data-clear-button="wcsts_clear_file_button_new_ticket_<?php echo $current_file_id; ?>"
											data-upload-button-id="#wcsts_file_upload_button_<?php echo  $current_file_id; ?>"
											data-delete-button-id="#wcsts_file_tmp_delete_button_<?php echo  $current_file_id; ?>"
											id="wcsts_input_file_<?php echo $current_file_id; ?>"
											data-id="<?php echo  $current_file_id; ?>"
											data-hide-index="<?php echo $i; ?>"
											data-main-container="#wcsts_new_ticket_content">
										</input>

										<!-- New managment -->
										<input type="hidden" class="wcsts_file_metadata" id="wcsts-filename-<?php echo  $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_name]" value=""></input>
										<input type="hidden" class="wcsts_file_metadata" id="wcsts-filenameprefix-<?php echo  $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_name_tmp_prefix]" value=""></input>
										<input type="hidden" class="wcsts_file_metadata" id="wcsts-complete-name-<?php echo  $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_complete_name]" value=""></input>
										<!-- Upload button -->
										<button class="button wcsts_file_upload_button"
											id="wcsts_file_upload_button_<?php echo  $current_file_id; ?>"
											data-id="<?php echo  $current_file_id; ?>"
											data-upload-field-id="#wcsts_input_file_<?php echo  $current_file_id; ?>"><?php esc_html_e('Upload', 'woocommerce-support-ticket-system') ?></button>
										<!-- File name -->
										<span class="wcsts_file_tmp_name" id="wcsts_file_tmp_name_<?php echo  $current_file_id; ?>"></span>
										<!-- Delete button -->
										<button class="button wcsts_file_tmp_delete_button"
											id="wcsts_file_tmp_delete_button_<?php echo  $current_file_id; ?>"
											data-id="<?php echo  $current_file_id; ?>"
											data-file-to-delete=""><?php esc_html_e('Delete', 'woocommerce-support-ticket-system') ?> </button>
										<!-- Upload progress managment -->
										<div id="wcsts_upload_progress_status_container_<?php echo  $current_file_id; ?>" class="wcsts_upload_progress_status_container">
											<div class="wcsts_upload_progressbar" id="wcsts_upload_progressbar_<?php echo  $current_file_id; ?>"></div>
											<div class="wcsts_upload_progressbar_percent" id="wcsts_upload_progressbar_percent_<?php echo  $current_file_id; ?>">0%</div>
										</div>
									</div>
								<?php endfor; ?>
							<?php endif; ?>

							<div id="wcsts_new_ticket_loader"><img class="wcsts_preloader_image" src="<?php echo WCSTS_PLUGIN_PATH . '/images/loader.gif' ?>"></img></div>
							<?php if (isset($extra_parameters['ticket_post_time_error'])): ?>
								<div class="wcsts_error_box wcsts_new_ticket_area_error_box">
									<?php echo sprintf(esc_html__('Ticket can be opened every %d seconds', 'woocommerce-support-ticket-system'), $extra_parameters['ticket_post_time_interval']); ?>
								</div>
							<?php else: ?>
								<div id="wcsts_new_ticket_status"></div>
							<?php endif; ?>
							<button class="button wcsts_button" id="wcsts_open_new_ticket_button" class="wcsts_clear_file_button"><?php esc_html_e('Open new ticket', 'woocommerce-support-ticket-system'); ?></button>
						</div>
					</div>
				<?php endif; //ticket type check 
				?>
				<?php if (!$is_ajax): ?>
				</div>
			<?php endif; //!is_ajax 
			?>
			</div>
		<?php
	}

	function load_new_custom_status_configuration_box()
	{
		$this->render_status_configuration();
		wp_die();
	}
	function render_status_configuration($status = null)
	{
		global $wcsts_wpml_helper, $wcsts_ticket_model;
		$id = isset($status) ? $status['id'] : wcsts_random_string();
		$is_custom = isset($status) ? $status['is_custom'] : true;
		$default_name = isset($status) ? $status["label"][$status['def_lang']] : "";
		$def_lang = $wcsts_wpml_helper->get_default_locale();
		$background_color = isset($status) ? $status['background_color'] : "#ffffff";
		$text_color = isset($status) ? $status['text_color'] : "#000000";
		$automatic_switch_to_selected_status = isset($status) ? $status['automatic_switch_to_selected_status'] : false;
		$statuses = $wcsts_ticket_model->get_available_statuses();
		wp_enqueue_style('jquery-color');
		wp_enqueue_script('wcsts-color-picker',  WCSTS_PLUGIN_PATH . '/js/vendor/color-picker/jscolor.js', array('jquery'));

		?>
			<div class="wcsts_status_configuration_container" id="wcsts_status_configuration_container_<?php echo  $id; ?>">
				<input type="hidden" name="wcts_statuses[<?php echo $id; ?>][id]" value="<?php echo $id; ?>"></input>
				<input type="hidden" name="wcts_statuses[<?php echo $id; ?>][is_custom]" value="<?php if ($is_custom) echo 'true';
																								else echo 'false'; ?>"></input>

				<div class="wcsts_full_container">
					<label><?php esc_html_e('Name', 'woocommerce-support-ticket-system'); ?></label>
					<input type="text" placeholder="<?php if (!$is_custom) echo $default_name;
													else esc_html_e('Name', 'woocommerce-support-ticket-system'); ?>" name="wcts_statuses[<?php echo $id; ?>][label][<?php echo $def_lang; ?>]" value="<?php echo $default_name; ?>" <?php /* if(!$is_custom) echo 'disabled="disabled"'; else  */ echo 'required="required"'; ?>></input>
				</div>
				<div class="wcsts_inline_container">
					<label><?php esc_html_e('Background color', 'woocommerce-support-ticket-system'); ?></label>
					<input name="wcts_statuses[<?php echo $id; ?>][background_color]" id="color-picker-<?php echo $id; ?>" class="jscolor" value="<?php echo $background_color; ?>"></input>
				</div>

				<div class="wcsts_inline_container">
					<label><?php esc_html_e('Text color', 'woocommerce-support-ticket-system'); ?></label>
					<input type="text" name="wcts_statuses[<?php echo $id; ?>][text_color]" id="color-picker-<?php echo $id; ?>" class="jscolor" value="<?php echo $text_color; ?>"></input>
				</div>
				<?php if (true /* $is_custom */): ?>
					<div class="wcsts_block_container">
						<label><?php esc_html_e('Automatic switch to selected status in case of reply', 'woocommerce-support-ticket-system'); ?></label>
						<p class="wcsts_option_description"><?php esc_html_e('If enabled, when the user posts a reply, the current ticket status will be switched to the selected one.', 'woocommerce-support-ticket-system'); ?>
							<?php if ($status['id'] == 'closed'): ?>
								<br><strong><?php esc_html_e('Note: ', 'woocommerce-support-ticket-system'); ?></strong><?php esc_html_e('If a ticket is marked as closed, the user will not be able to reply. To allow that, enable the special option in the <strong>Ticket System Options</strong> menu.', 'woocommerce-support-ticket-system'); ?>
							<?php endif; ?>
						</p>

						<select name="wcts_statuses[<?php echo $id; ?>][automatic_switch_to_selected_status]">
							<option value="false" <?php selected($automatic_switch_to_selected_status, false); ?>><?php esc_html_e('Disabled', 'woocommerce-support-ticket-system'); ?></option>
							<?php
							foreach ($statuses as $staus_code => $status_name)
								echo '<option value="' . $staus_code . '" ' . selected($automatic_switch_to_selected_status, $staus_code) . ' >' . $status_name["label"][$status_name["def_lang"]] . '</option>';
							?>
						</select>
					</div>
				<?php endif; ?>

				<!-- WPML -->
				<?php if ($wcsts_wpml_helper->wpml_is_active()) {
				?>
					<div class="wcsts_language_container">
						<span class="wcsts_title"><?php esc_html_e('WPML', 'woocommerce-support-ticket-system'); ?></span>
						<?php
						$langs = $wcsts_wpml_helper->get_langauges_list($status);
						if ($langs)
							foreach ($langs as $lang_data):
								if ($lang_data['default_locale'] == $def_lang)
									continue;
								$current_lang_name = isset($status) ? $status["label"][$lang_data['default_locale']] : "";
						?>
							<div class="wcsts_inline_container">
								<label><img src="<?php echo $lang_data['country_flag_url']; ?>" /> <?php echo $lang_data['translated_name']; ?></label>
								<input type="text" placeholder="<?php echo printf(esc_html__('Name for %s language', 'woocommerce-support-ticket-system'), $lang_data['translated_name']); ?>" name="wcts_statuses[<?php echo $id; ?>][label][<?php echo $lang_data['default_locale']; ?>]" value="<?php echo $current_lang_name; ?>" required="required"></input>
							</div>
						<?php endforeach; ?>
					</div>
				<?php }

				if ($is_custom): ?>
					<div class="wcsts_delete_button_container">
						<button class="wcsts_delete_custom_status button-delete" data-id-to-delete="wcsts_status_configuration_container_<?php echo  $id; ?>"><?php esc_html_e('Delete', 'woocommerce-support-ticket-system'); ?></button>
					</div>
				<?php endif; ?>

			</div>
	<?php
	}
}
	?>