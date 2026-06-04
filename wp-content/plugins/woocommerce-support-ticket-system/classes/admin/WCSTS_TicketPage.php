<?php 
class WCSTS_TicketPage
{
	public function __construct()
	{
		add_action( 'admin_menu' , array($this,'remove_priority_defaul_meta_box'));
		add_action( 'load-post.php' , array($this,'on_open_edit_page'));
		
		//On open:
		// -- in case of new messages, reset the new message counter
		add_action( 'add_meta_boxes_wcsts_ticket', array( &$this, 'add_custom_meta_boxes' ) );
		
		//add_action( 'add_meta_boxes', array( &$this,'remove_yoast_metabox'),999 );
		
		 //On save (Admin page):
	    // --- if status is open and date is empty, set open date as now
	    // --- if status is close and date is empty, set close date as now
		// --- reassign to the  wcsts_ticket_message the customer_id
		// --- reassign to the order the ticked id
		// -- send email to user in case of admin reply
		add_action( 'save_post', array( &$this, 'process_meta_boxes_data_on_ticket_save' ), 999, 2 );
		//add_filter( 'tiny_mce_before_init', array( &$this, 'switch_tinymce_p_br' ));
		
		//add_filter( 'redirect_post_location', array( &$this, 'redirect_afeter_save' ));
		
		//See WCSTS_Ticket for more action hooks
	}
	public function switch_tinymce_p_br( $settings ) 
	{
		$settings['force_br_newlines'] = true;
		$settings['force_p_newlines'] = true;
		$settings['convert_newlines_to_brs'] = true;
		$settings['forced_root_block'] = true;
		return $settings;
	}
	public function remove_priority_defaul_meta_box()
	{
		remove_meta_box( 'tagsdiv-wcsts_ticket_priority', 'wcsts_ticket', 'side' );
	}
	public function on_open_edit_page()
	{
		global $wcsts_ticket_model;
		if(isset($_GET['post']))
			$post = get_post($_GET['post']);
		if(!isset($post) || $post->post_type != 'wcsts_ticket')
			return;
		$wcsts_ticket_model->reset_new_messages_counter($post->ID);
	}
	
	function process_meta_boxes_data_on_ticket_save( $ticket_id, $ticket_obj ) 
	{
		global $wcsts_ticket_model, $wcsts_ticket_message_model, $wcsts_order_model,$wcsts_email_model, $wcsts_user_model, $wcsts_file_model, $wcsts_text_helper;
		
		$message_id = false;
		
		//Only is save is made by admin page takes place
		if ( !isset( $_POST['wcsts_admin_action'] ) || !wp_verify_nonce( $_POST['wcsts_admin_action'], 'wcsts_admin_ticket_edit' ) )
			return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        return;
		// AJAX? Not used here
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) 
				return;
		if($ticket_obj->post_type != 'wcsts_ticket')
			return;
			
		$ticket_status_data = $wcsts_ticket_model->get_status_data($ticket_id);
		$ticket_status = $ticket_status_data['id'];
		if($wcsts_ticket_model->get_attributes($ticket_id, 'open_status_date') == "")
			$wcsts_ticket_model->set_attribute($ticket_id, 'open_status_date', current_time('F j, Y g:i a'));
		else 
			$wcsts_ticket_model->set_special_standard_date_field_format('open_status_date', $ticket_id, $wcsts_ticket_model->get_attributes($ticket_id, 'open_status_date'));
		
		if( $ticket_status == 'closed' && 
			$wcsts_ticket_model->get_attributes($ticket_id, 'closed_status_date') == "")
				$wcsts_ticket_model->set_attribute($ticket_id, 'closed_status_date', current_time('F j, Y g:i a'));
		else if( $ticket_status == 'closed')
				$wcsts_ticket_model->set_special_standard_date_field_format('closed_status_date', $ticket_id, $wcsts_ticket_model->get_attributes($ticket_id, 'closed_status_date'));
			
		
		
		//order: reasign to order the ticked id
		$ticket_type = $wcsts_ticket_model->get_attributes($ticket_id, 'ticket_type');
		$order = null;
		if($ticket_type == 'order')
		{
			if(isset($_POST['wcst_associated_order']))
				$wcsts_ticket_model->set_attribute($ticket_id, 'associated_order', $_POST['wcst_associated_order']);
			
			$order_id = wcsts_get_value_if_set($_GET, 'order_id', false) ? wcsts_get_value_if_set($_GET, 'order_id', false) : $wcsts_ticket_model->get_attributes($ticket_id, 'associated_order');
			$wcsts_ticket_message_model->assing_customer_id_to_messages_by_order($ticket_id);
			
			$order = wc_get_order($order_id);
			$wcsts_order_model->assign_ticket_id_to_order($ticket_id, $order_id);
			if($order)
			{
				$user_id =  $order->get_user_id();
				$wcsts_ticket_model->set_attribute($ticket_id, 'associated_user', $order->get_user_id()); //new: this is used to filter ticket by user //new: this is used to filter ticket by user
			}
		}
		else 
		{
			$user = $wcsts_ticket_model->get_attributes($ticket_id, 'associated_user');
			$user_id = $user == null ? $_POST['acf']['field_57d65c825027b'] : $user;
			$wcsts_user_model->assign_ticket_id_to_user($ticket_id, $user_id); 
		}
		
		//Subject
		if(isset($_POST['wcsts_subject']))
			 $wcsts_ticket_model->set_subject($ticket_id, $_POST['wcsts_subject']);
		
		//Reply message save
		if(isset($_POST['wcsts_reply_message']) && $_POST['wcsts_reply_message'] != "")
		{
			$reply_message = $wcsts_text_helper->replace_shortcodes($_POST['wcsts_reply_message'], $user_id, $order);
			$message_id = $wcsts_ticket_message_model->add_reply($ticket_id, $reply_message);
			
			//Notification
			$wcsts_email_model->send_reply_notification_to_user($ticket_id, $reply_message, $ticket_type);
		}
		
		//Number of questions
		if(isset($_POST['wcsts_number_of_questions_left']) && $_POST['wcsts_number_of_questions_left'] != "")
		{
			$wcsts_ticket_model->set_attribute($ticket_id, 'number_of_questions_left', $_POST['wcsts_number_of_questions_left']);
		}
		
		//Ticket assign managment
		$user_ids = isset($_POST['manager_user_id']) ? $_POST['manager_user_id'] : array();
		$notify_user = isset($_POST['notification_action']) && $_POST['notification_action'] == 'yes' ?  true : false;
	
		//Attachments
		if(isset($_POST['wcsts_files']) && is_numeric($message_id))
		{
			$wcsts_file_model->save_uploaded_files($_POST['wcsts_files'],$ticket_id, $message_id);
		}	
		$ticket_ids = array($ticket_id);
		if( !empty($ticket_ids))
		{
			$wcsts_ticket_model->remove_all_manager_users_assigned_to_tickets($ticket_ids);
			if(!empty($user_ids))
				$wcsts_ticket_model->assign_manager_users_to_tickets($ticket_ids,$user_ids);
		}
		
		if($notify_user && !empty($user_ids) && !empty($ticket_ids))
		{
			$wcsts_email_model->send_manager_user_assigned_ticket_notification($ticket_ids,$user_ids);
		}
		
	}
	
	function remove_yoast_metabox()
	{
		global $post;
		$post_type = get_post_type($post);
		if($post_type == 'wcsts_ticket')
			remove_meta_box('wpseo_meta', 'post', 'normal');
	}
	function add_custom_meta_boxes() 
	{
		global $post, $wcsts_html_helper, $wcsts_ticket_model;
		$ticket_type = $wcsts_ticket_model->get_attributes($post->ID,'ticket_type');
		
		if($ticket_type == 'order')
			add_meta_box( 'wcst-order-details', esc_html__('WooCommerce Support Ticket - Order details', 'woocommerce-support-ticket-system'), array( &$this, 'admin_support_ticket_page_meta_box_order_details' ), 'wcsts_ticket', 'normal');
		add_meta_box( 'wcst-assigned-user-manager', esc_html__('WooCommerce Support Ticket - User(s) that can manage the ticket', 'woocommerce-support-ticket-system'), array( &$this, 'admin_support_ticket_page_meta_box_assigned_manager' ), 'wcsts_ticket', 'normal');
		add_meta_box( 'wcst-customer-data', esc_html__('WooCommerce Support Ticket - Customer details', 'woocommerce-support-ticket-system'), array( &$this, 'admin_support_ticket_page_meta_box_customer' ), 'wcsts_ticket', 'side');
		if($ticket_type != 'ppt')
			add_meta_box( 'wcst-subject', esc_html__('WooCommerce Support Ticket - Subject', 'woocommerce-support-ticket-system'), array( &$this, 'admin_support_ticket_page_meta_box_subject' ), 'wcsts_ticket', 'normal');
		else
			add_meta_box( 'wcst-ppt-data', esc_html__('WooCommerce Support Ticket - Pay Per Ticket info', 'woocommerce-support-ticket-system'), array( &$this, 'admin_support_ticket_page_meta_box_ppt_info' ), 'wcsts_ticket', 'normal');
		add_meta_box( 'wcst-messages', esc_html__('WooCommerce Support Ticket - Messages', 'woocommerce-support-ticket-system'), array( &$this, 'admin_support_ticket_page_meta_box_message' ), 'wcsts_ticket', 'normal');
		add_meta_box( 'wcst-reply-box', esc_html__('WooCommerce Support Ticket - Reply', 'woocommerce-support-ticket-system'), array( &$this, 'admin_support_ticket_page_meta_box_reply_box' ), 'wcsts_ticket', 'normal');
	}
	
	//HTML
	public function admin_support_ticket_page_meta_box_reply_box()
	{
		global $post, $wcsts_option_helper, $wcsts_text_helper, $sitepress, $wcsts_html_helper;
		$ticket_id = $post->ID;
		
		$options = $wcsts_option_helper->get_all_options();
		$customized_texts = $wcsts_text_helper->get_texts();
		
		wp_register_script('wcsts-backend-upload-manager', WCSTS_PLUGIN_PATH. '/js/frontend-upload-manager.js', array('jquery') );
		wp_register_script('wcsts-backend-uploader', WCSTS_PLUGIN_PATH. '/js/frontend-uploader.js', array('jquery') );
			
		$js_variables = array(
				'wcsts_ajax_url' => admin_url('admin-ajax.php'),
				'wcsts_empty_message_error' => esc_html__('Message cannot be empty','woocommerce-support-ticket-system'),
				'wcsts_empty_subject_error' => esc_html__('Subject cannot be empty','woocommerce-support-ticket-system'),
				'wcsts_file_size_error' => esc_html__('The file size excedes the size limit of: ','woocommerce-support-ticket-system'),
				'wcsts_upload_still_in_progress' => esc_html__('Please wait, upload still in progress.','woocommerce-support-ticket-system')
				
			);
		
		wp_localize_script( 'wcsts-backend-uploader', 'wcsts', $js_variables );
		
		wp_enqueue_script( 'wcsts-backend-upload-manager' );
		wp_enqueue_script( 'wcsts-backend-uploader' );
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_script( 'wcsts-backend-predefined-answers-loader', WCSTS_PLUGIN_PATH. '/js/backend-predefinew-answers-loader.js', array('jquery') );
			
		wp_enqueue_style( 'admin-select2', WCSTS_PLUGIN_PATH.'/css/vendor/select2/select2.css'); 
		?>
		<p><strong><?php wcsts_html_escape_allowing_special_tags(__('To submit the reply message press the <i>Update</i> button (the one in upper-right box). This will also save other ticket settings.','woocommerce-support-ticket-system')); ?></strong></p>
		
		<div id="wcsts_predefined_answer_loader_container" class="">
			<label class="wcsts_label"><?php esc_html_e('Load a predefined answer:','woocommerce-support-ticket-system'); ?></label>
			<select className="wcsts_products_select2" id="wcsts_predefiled_answer_loader"></select>
			<div id="wcsts_predefiled_answer_loader_status"><?php esc_html_e('Loading, please wait...','woocommerce-support-ticket-system'); ?></div>
		</div>
		<label class="wcsts_label"><?php esc_html_e('Shortcodes:','woocommerce-support-ticket-system'); ?></label>
		<button class="wcsts_accordion"><?php esc_html_e('Click here for more info','woocommerce-support-ticket-system'); ?></button>
		<div id="wcsts_shortcode_info" class="">
			<?php $wcsts_html_helper->render_shortcode_info(); ?>
		</div>
		
		<?php
		wp_editor( "", 'reply_message', array(
			'wpautop'       => true,
			'media_buttons' => true,
			'textarea_name' => 'wcsts_reply_message',
			'textarea_rows' => 10,
			'teeny'         => true
		) );
		?><h4><?php esc_html_e('Attachments','woocommerce-support-ticket-system'); ?></h4> 
		<?php
		for($i = 0; $i<99; $i++):
			$current_file_id = $ticket_id."_".$i; ?>
			<div class="wcsts_input_attachment_container">
				 <input type="file" 
					   data-max-size=""
					   class="wcsts_attachment_input wcsts_new_message_attachment wcsts_new_message_attachment_group_<?php echo $ticket_id;?>" 
					   data-clear-button="<?php echo  $current_file_id;?>"
					   data-id="<?php echo  $current_file_id;?>"
					   data-upload-button-id = "#wcsts_file_upload_button_<?php echo  $current_file_id; ?>"
					   data-delete-button-id = "#wcsts_file_tmp_delete_button_<?php echo  $current_file_id; ?>"
					   id="wcsts_input_file_<?php echo  $current_file_id;?>" 
					   data-hide-index="<?php echo $i;?>"
					   data-main-container=".wcsts_new_message_container" >
				 </input>
				
				
				<!-- New managment -->
				<input type="hidden" class="wcsts_file_metadata_<?php echo  $ticket_id; ?>" id="wcsts-filename-<?php echo $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_name]" value=""></input>
				<input type="hidden" class="wcsts_file_metadata_<?php echo  $ticket_id; ?>" id="wcsts-filenameprefix-<?php echo  $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_name_tmp_prefix]" value=""></input>
				<input type="hidden" class="wcsts_file_metadata_<?php echo  $ticket_id; ?>" id="wcsts-complete-name-<?php echo  $current_file_id; ?>" name="wcsts_files[<?php echo  $current_file_id; ?>][file_complete_name]" value=""></input>
				<!-- File name display after upload -->
				<div class="wcsts_file_name_display_after_upload" id="wcsts-filename-display-<?php echo $current_file_id;?>"></div>
				<!-- Upload button -->
				<button class="button wcsts_file_upload_button"  
						id="wcsts_file_upload_button_<?php echo  $current_file_id; ?>"
					   data-id="<?php echo  $current_file_id; ?>"  
					   data-upload-field-id="#wcsts_input_file_<?php echo  $current_file_id; ?>"><?php esc_html_e('Upload', 'wp-user-extra-fields') ?></button>
				<button class="button wcsts_file_tmp_delete_button"  
						id="wcsts_file_tmp_delete_button_<?php echo  $current_file_id; ?>"
					   data-id="<?php echo  $current_file_id; ?>"  
					   data-file-to-delete=""><?php esc_html_e('Delete', 'wp-user-extra-fields') ?> </button>
				<!-- Upload progress managment -->
				<div id="wcsts_upload_progress_status_container_<?php echo  $current_file_id; ?>" class="wcsts_upload_progress_status_container">
					<div class="wcsts_upload_progressbar" id="wcsts_upload_progressbar_<?php echo  $current_file_id; ?>"></div >
					<div class="wcsts_upload_progressbar_percent" id="wcsts_upload_progressbar_percent_<?php echo  $current_file_id; ?>">0%</div>
				</div>
			 
			 </div>
		 <?php endfor; ?>
			<!-- <p><input  type="submit" value="<?php esc_html_e('Update','woocommerce-support-ticket-system'); ?>" class="button button-primary button-large" name="save"></p> -->
		<?php 
	}
	public function admin_support_ticket_page_meta_box_assigned_manager()
	{
		global $wcsts_ticket_model, $wcsts_user_model, $wcsts_html_helper, $post;
		
		$ticket_id = $post->ID;
		$user_ids = $wcsts_ticket_model->get_manager_user_ids($ticket_id);
		
		
		$wcsts_html_helper->render_multiple_user_selector($user_ids); 
		
	}
	public function admin_support_ticket_page_meta_box_order_details()
	{
		global $post, $wcsts_ticket_model, $wcsts_product_model, $wcsts_order_model;
		$order_id = wcsts_get_value_if_set($_GET, 'order_id', false) ? wcsts_get_value_if_set($_GET, 'order_id', false) : $wcsts_ticket_model->get_attributes($post->ID, 'associated_order');
		$wc_order = wc_get_order($order_id);
		if(!isset($wc_order) || $wc_order === false)
			return;
		$products = $wc_order->get_items();
						
		$order_total = 0;
		$taxes_total = 0;
		if(count($products) > 0):		
		?>
		<table class="wp-list-table widefat striped wcst-customer-details-table" >
		<thead>
			<tr>
				<th><?php  esc_html_e('Product ID', 'woocommerce-customers-manager'); ?></th>
				<th><?php  esc_html_e('Product name', 'woocommerce-customers-manager'); ?></th>
				<th><?php  esc_html_e('Quantity', 'woocommerce-customers-manager'); ?></th>
				<th><?php  esc_html_e('Sub total', 'woocommerce-customers-manager'); ?></th>
				<th><?php  esc_html_e('Taxes', 'woocommerce-customers-manager'); ?></th>
				<th><?php  esc_html_e('Discount', 'woocommerce-customers-manager'); ?></th>
				<th><?php  esc_html_e('Total', 'woocommerce-customers-manager'); ?></th>
				<th><?php  esc_html_e('Actions', 'woocommerce-customers-manager'); ?></th>
			</tr>
		</thead>
		<tbody>					
		<?php			
			foreach($products as $product)
			{
				$order_item_id = $product->get_product_id();
				$order_item_variation_id = $product->get_variation_id() ;
				$order_item_quantity =  $product->get_quantity();
				$discount = ($product['subtotal']+$product['subtotal_tax']) - ($product['line_total']+$product['line_tax']);
				$discount = $discount > 0 ? $discount : 0;
				?>
						<tr>
							<td> <?php echo $order_item_id; if($order_item_variation_id !=0) echo " (".esc_html__('Var: ', 'woocommerce-customers-manager').$order_item_variation_id.")"; ?> </td>
							<td class="wcst_product_name_column"> <a href="<?php echo get_permalink($order_item_id); ?>">
									<?php if($order_item_variation_id != 0):
												echo $wcsts_product_model->get_variation_complete_name($order_item_variation_id);
											else:
												echo $product['name'];													
									endif; ?>
								</a></td>
							<td> <?php echo $order_item_quantity; ?> </td>
							<td> <?php echo get_woocommerce_currency_symbol().round($product['subtotal'],2); ?> </td>
							<td> <?php echo get_woocommerce_currency_symbol().round($product['subtotal_tax'],2); ?> </td>
							<td> <?php echo get_woocommerce_currency_symbol().round($discount,2); ?> </td>
							<td> <?php echo get_woocommerce_currency_symbol().round($product['line_total']+$product['line_tax'],2)//$wc_product->get_price_html(); ?> </td>
							<td> <a class="button-primary" target="_blank" href="<?php echo get_edit_post_link($order_item_id ); ?>">  <?php esc_html_e('Edit', 'woocommerce-customers-manager'); ?> </a> </td>
						</tr>
						
				
				<?php  
				
				if($wc_order->get_status() != "cancelled"  && $wc_order->get_status() != "refunded")
				{
						if(isset($products_to_quantities_purchased[$product['name']]))
							$products_to_quantities_purchased[$product['name']]['total_purchased'] += $order_item_quantity;
						else
							$products_to_quantities_purchased[$product['name']] = array("total_purchased" => $order_item_quantity, "product" => $product);
				}
				
			   $order_total += $product['subtotal']; //$product['line_total']; //No, because of coupon usage, this is the already discouted price
			   $taxes_total += $product['subtotal_tax']; //$product['line_tax']; //No, because of coupon usage, this is the already discouted price
			}			
		 ?>
		 </tbody>
		</table>
		
			<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Order number:', 'woocommerce-customers-manager' ); ?></span> 
									<span class="wcsts_stats_content"><select name="wcst_associated_order" class="wcsts_select2">
																	<?php
																			$selected =  $wc_order->get_id(); 
																			$all_order_ids = $wcsts_order_model->get_all_order_numbers();
																			foreach($all_order_ids as $current_order_id):
																			
																			?>
																			<option value="<?php echo $current_order_id;?>" <?php selected($current_order_id, $selected); ?>><?php echo apply_filters('wcsts_order_number', $current_order_id);?></option>
																			<?php
																			endforeach;
																	  ?>
																	  </select>
								     </span>
			</span>
			<span class="wcsts_stats"><span class="wcsts_stats_title">Status:</span>
			<span class="wcsts_stats_content">
			<?php 
				$order_status = strtoupper($wc_order->get_status());
				if($order_status == 'COMPLETED')
					echo '<span class="order_status order_completed">'.$order_status.'</span>';
				else if($order_status == 'PROCESSING' || $order_status == 'ON-HOLD')
					echo '<span class="order_status order_processing_onhold">'.$order_status.'</span>';
				else
					echo '<span class="order_status order_not_completed">'.$order_status.'</span>';
				  //$order->post_status; 
			$refounded = $wc_order->get_total_refunded();
			$refounded = isset($refounded) ? floatval($refounded):0;
			if(method_exists ($wc_order,'get_total_shipping'))
			{
			 $total_shipping = get_woocommerce_currency_symbol().$wc_order->get_total_shipping();
			 $total_shipping_tax = get_woocommerce_currency_symbol().$wc_order->get_shipping_tax();
			 $total_order = get_woocommerce_currency_symbol().(round($order_total+$taxes_total+$wc_order->get_total_shipping()+$wc_order->get_shipping_tax()-$wc_order->get_total_discount(false) - $refounded,2));
			
			}
			else
			{
				$total_shipping =  "N/A";
				$total_shipping_tax =  "N/A";
				$total_order = get_woocommerce_currency_symbol().(round($order_total+$taxes_total-$wc_order->get_total_discount(false),1));
			}
			
			?></span></span>
		
		<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Sub total:', 'woocommerce-customers-manager' ); ?></span> <span class="wcsts_stats_content"><?php echo get_woocommerce_currency_symbol().round($order_total,2); ?></span></span>
		<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Taxes:', 'woocommerce-customers-manager' ); ?></span> <span class="wcsts_stats_content"><?php echo get_woocommerce_currency_symbol().round($taxes_total,2); ?></span></span>
		<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Shipping:', 'woocommerce-customers-manager' ); ?></span> <span class="wcsts_stats_content"><?php echo $total_shipping; ?></span></span>
		<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Shipping Taxes:', 'woocommerce-customers-manager' ); ?></span> <span class="wcsts_stats_content"><?php echo $total_shipping_tax; ?></span></span>
		<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Discount:', 'woocommerce-customers-manager' ); ?></span> <span class="wcsts_stats_content"><?php echo get_woocommerce_currency_symbol().round($wc_order->get_total_discount(false),2); ?></span></span>
		<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Total refounded:', 'woocommerce-customers-manager' ); ?></span> <span class="wcsts_stats_content"><?php echo get_woocommerce_currency_symbol().($refounded); ?></span></span>
		<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Total:', 'woocommerce-customers-manager' ); ?></span> <span class="wcsts_stats_content"><?php echo $total_order; ?></span></span>
		<span class="wcsts_stats"><span class="wcsts_stats_title"><?php esc_html_e('Payment method:', 'woocommerce-customers-manager' ); ?></span> <span class="wcsts_stats_content"><?php echo $wc_order->get_payment_method_title(); ?></span></span>
		<?php endif; //end if(products>0) ?>
		
		<strong class="wcsts_more_details"><?php esc_html_e('More details', 'woocommerce-support-ticket-system'); ?></strong>	
		<a class="button" href="<?php echo get_edit_post_link(WCSTS_Order::get_id($wc_order)); ?>" target="_blank"><?php esc_html_e('Order page', 'woocommerce-support-ticket-system'); ?></a>
		<?php 
	}
	public function admin_support_ticket_page_meta_box_customer() 
	{
		//***************** SHARED ************************
		wp_nonce_field( 'wcsts_admin_ticket_edit', 'wcsts_admin_action' ); 
		//CSS
		wp_enqueue_style('wcsts-ticket-details-page', WCSTS_PLUGIN_PATH. '/css/backend-ticket-details-page.css' );
		//JS
		wp_register_script('wcsts-ticket-details-page', WCSTS_PLUGIN_PATH. '/js/backend-ticket-details-page.js', array('jquery') );
		$translation_array = array(
				'wcsts_confirm_message_error' => esc_html__('Are you sure you want to delete?','woocommerce-support-ticket-system')
			);
		wp_localize_script( 'wcsts-ticket-details-page', 'wcsts_ticket_page', $translation_array );
		wp_enqueue_script( 'wcsts-ticket-details-page');
		//***************** END SHARED ************************
		
		global $post, $wcsts_ticket_model,$wp_roles;
		$ticket_type = $wcsts_ticket_model->get_attributes($post->ID, 'ticket_type');
		if($ticket_type == 'order')
		{
			$order_id = wcsts_get_value_if_set($_GET, 'order_id', false) ? wcsts_get_value_if_set($_GET, 'order_id', false) : $wcsts_ticket_model->get_attributes($post->ID, 'associated_order');
			$order = wc_get_order($order_id);// new WC_Order($order_id);
			if( $order == false || WCSTS_Order::get_customer_id($order) == null || WCSTS_Order::get_customer_id($order) == 0)
			{
				if($order == false):
				?>
					<strong><?php esc_html_e("Order no loger exists", 'woocommerce-support-ticket-system'); ?></strong>
				<?php 
				else:
				?>
					<strong><?php esc_html_e("The user hasn't a valid profile associated (is he a guest customer?)", 'woocommerce-support-ticket-system'); ?></strong>
				<?php 
				endif;
				return;
			}
			$user_id = WCSTS_Order::get_customer_id($order);
		}
		else
			$user_id = $wcsts_ticket_model->get_attributes($post->ID, 'associated_user');
		
		
		if(!isset($user_id))
			return;
		$customer = new WP_User( $user_id);
		$customer_data = get_userdata( $user_id);
		$customer_extra_data = get_user_meta($user_id);
		
		?>
		
		<label><?php  esc_html_e('First Name', 'woocommerce-support-ticket-system'); ?></label><br /><?php if(isset($customer_extra_data['first_name'])) echo $customer_extra_data['first_name'][0]; ?> <br /><br />
		<label><?php  esc_html_e('Last Name', 'woocommerce-support-ticket-system'); ?></label><br /><?php if(isset($customer_extra_data['last_name'])) echo $customer_extra_data['last_name'][0];?> <br/><br />
		<label><?php  esc_html_e('Email Address', 'woocommerce-support-ticket-system'); ?></label><br /><?php echo $customer_data->user_email; ?> <br/><br />
		
		<label><?php  esc_html_e('Billing First Name', 'woocommerce-support-ticket-system'); ?></label><br /><?php if(isset($customer_extra_data['billing_first_name'])) echo $customer_extra_data['billing_first_name'][0]; ?> <br /><br />
		<label><?php  esc_html_e('Billing Last Name', 'woocommerce-support-ticket-system'); ?></label><br /><?php if(isset($customer_extra_data['billing_last_name'])) echo $customer_extra_data['billing_last_name'][0];?> <br/><br />
		<label><?php  esc_html_e('Biling Email Address', 'woocommerce-support-ticket-system'); ?></label><br /><?php if(isset($customer_extra_data['billing_email'])) echo $customer_extra_data['billing_email'][0]; ?> <br/><br />
		
		<label><?php  esc_html_e('Registration Date', 'woocommerce-support-ticket-system'); ?> </label><br /><?php echo $customer_data->user_registered; ?> <br/><br />
		<label><?php  esc_html_e('Roles', 'woocommerce-support-ticket-system'); ?> </label><br /> 
					<?php $user = new WP_User( $user_id );
					if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
						foreach ( $user->roles as $role_code )
							echo  $wp_roles->roles[$role_code]["name"]." (". esc_html__('Role code:', 'woocommerce-support-ticket-system')." <i>".$role_code."</i>)<br/>";
					} ?> <br/><br/>
		<strong><?php esc_html_e('More details', 'woocommerce-support-ticket-system'); ?></strong><br/>		
		<a class="button" href="<?php echo get_edit_user_link($user_id); ?>" target="_blank" ><?php esc_html_e('User page', 'woocommerce-support-ticket-system'); ?></a>
		<a class="button" href="<?php echo get_admin_url(); ?>edit.php?s&post_status=all&post_type=shop_order&action=-1&_customer_user=<?php echo $user_id ?>&filter_action=Filter" target="_blank"><?php esc_html_e('Orders list', 'woocommerce-support-ticket-system'); ?></a>
		<?php 
	}
	public function admin_support_ticket_page_meta_box_ppt_info()
	{
		global $post, $wcsts_ticket_model;
		
		echo '<h4 class="ppt_attribute_title">'.esc_html__('Product name:','woocommerce-support-ticket-system').'</h4>';
		echo $wcsts_ticket_model->get_attributes($post->ID, 'ppt_product_name', "");
		echo '<h4 class="ppt_attribute_title">'.esc_html__('Order number:','woocommerce-support-ticket-system').'</h4>';
		echo $wcsts_ticket_model->get_attributes($post->ID, 'ppt_order_id', esc_html__('N/A','woocommerce-support-ticket-system'));
		echo '<h4 class="ppt_attribute_title">'.esc_html__('Question left:','woocommerce-support-ticket-system').'</h4>';
		echo '<input type="number" min="0" required="required" name="wcsts_number_of_questions_left" value="'.$wcsts_ticket_model->get_attributes($post->ID, 'number_of_questions_left', 0).'"></input>';
		
	}
	public function admin_support_ticket_page_meta_box_subject()
	{
		global $post, $wcsts_option_helper, $wcsts_ticket_model, $wcsts_text_helper;
		$ticket_type = $wcsts_ticket_model->get_attributes($post->ID,'ticket_type');
		$subject_type = $wcsts_text_helper->get_topic_type($ticket_type.'_ticket_subject_type');
		$subject = $wcsts_ticket_model->get_attributes($post->ID, 'subject', "");

		?>
		<p><?php wcsts_html_escape_allowing_special_tags(__('<strong>NOTE:</strong> If ticket type has been changed, click on the <strong>Update</strong> button and then select the topic.', 'woocommerce-support-ticket-system')); ?></p>
		<input type="hidden" value="<?php echo $subject_type; ?>" name="wcsts_subject_type"></input>
		<?php if($subject_type == 'text_input'): ?>
			<input type="text" value="<?php echo $subject; ?>" name="wcsts_subject" class="wcst_subject" required="required"></input>
		<?php else: 
				$topics = $wcsts_ticket_model->get_subject_topics($post->ID); ?>
				<select name="wcsts_subject" class="wcst_subject" required="required">
			<?php foreach($topics as $topic_id => $topic): 
					$selected = $subject == $topic_id ? 'selected="selected"':""; ?>
					<option value="<?php echo $topic_id; ?>" <?php echo $selected;?>><?php echo $topic; ?></value>
			 <?php endforeach; ?>
				</select>
		<?php endif;
	}
	public function admin_support_ticket_page_meta_box_message()
	{
		global $wcsts_ticket_message_model, $wcsts_user_model, $post;
		$ticket_id = $post->ID;
		$messages = $wcsts_ticket_message_model->get_messages_by_ticket_id($ticket_id);
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$attachment_box_id = 0;
		
		?>
		<div id="wcsts_ticket_messages_container">
				<?php 
					foreach($messages as $message):
						if(!$message->is_customer_message): ?>
							<div class="wcsts_ticket_message_content" id="wcsts_ticket_message_<?php echo $message->ID; ?>">
								<span class="wcsts_admin_message_details wcsts_message_details">
									<strong><?php echo $wcsts_user_model->get_user_name($message->post_author); //esc_html_e('You on','woocommerce-support-ticket-system'); ?></strong><br/>
									<?php echo date($date_format." ".$time_format, strtotime($message->post_date)); ?><br/>	
									<span class="dashicons dashicons-trash wcsts_delete_message_button wcsts_delete_admin_message_button" data-id="<?php echo $message->ID; ?>"></span>
								</span>
								<div class="wcsts_admin_message wcsts_message ">
									<p><?php echo wcsts_restore_paragraph_breaks($message->post_content); ?></p>
									<?php $attachments = $wcsts_ticket_message_model->get_attachments($message->ID);
											if(!empty($attachments)):?>
											<div class="wcsts_attachments_container">
												<?php $attachment_counter = 0;
												foreach($attachments as $attachment_unique_value => $attachment_url): 
												$attachment_box_id++; ?>
												<div class="wcts_single_attachment" id="wcsts_single_attachment_<?php echo $attachment_box_id; ?>">
													<span class="wcts_attachment_title"><?php echo sprintf(esc_html__('Attachment %d','woocommerce-support-ticket-system'), ++$attachment_counter); ?>: </span>
													<a class="dashicons dashicons-paperclip" href="<?php echo $attachment_url; ?>" target="_blank" download></a>
													<span data-message-id="<?php echo $message->ID; ?>" data-unique-value="<?php echo $attachment_unique_value; ?>" data-box-id="<?php echo $attachment_box_id; ?>" class="dashicons dashicons-trash wcsts_delete_attachment_button"></span>
												</div>
												<?php endforeach; //attachments ?>
											</div>
										<?php endif; //!empty($attachments) ?>
								</div>
							</div>
						<?php else: 
								$attachments = $wcsts_ticket_message_model->get_attachments($message->ID); ?>
								<div class="wcsts_ticket_message_content" id="wcsts_ticket_message_<?php echo $message->ID; ?>">
									<span class="wcsts_customer_message_details wcsts_message_details">
										<strong><?php esc_html_e('Customer on','woocommerce-support-ticket-system'); ?></strong><br/>
										<?php echo date($date_format." ".$time_format, strtotime($message->post_date)); ?>
										<span class="dashicons dashicons-trash wcsts_delete_message_button wcsts_delete_customer_message_button" data-id="<?php echo $message->ID; ?>"></span> 
									</span>	
									<div class="wcsts_customer_message wcsts_message">
										<p><?php echo wcsts_restore_paragraph_breaks($message->post_content); ?></p>
										<?php if(!empty($attachments)):?>
											<div class="wcsts_attachments_container">
												<?php $attachment_counter = 0;
												foreach($attachments as $attachment_unique_value => $attachment_url): 
												$attachment_box_id++; ?>
												<div class="wcts_single_attachment" id="wcsts_single_attachment_<?php echo $attachment_box_id; ?>">
													<span class="wcts_attachment_title"><?php echo sprintf(esc_html__('Attachment %d','woocommerce-support-ticket-system'), ++$attachment_counter); ?>: </span>
													<a class="dashicons dashicons-paperclip" href="<?php echo $attachment_url; ?>" target="_blank" download></a>
													<span data-message-id="<?php echo $message->ID; ?>" data-unique-value="<?php echo $attachment_unique_value; ?>" data-box-id="<?php echo $attachment_box_id; ?>" class="dashicons dashicons-trash wcsts_delete_attachment_button"></span>
												</div>
												<?php endforeach; //attachments ?>
											</div>
										<?php endif; //!empty($attachments) ?>
									</div>							
								</div>
					<?php  endif; //customer message
					endforeach; //messages ?>
		</div>
		<?php 
	}
}
?>