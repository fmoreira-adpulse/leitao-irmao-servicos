<?php
class WCSTS_ProductPage
{
	public function __construct()
	{
		add_action('woocommerce_variation_options',array(&$this,'add_questions_input_text_to_variation'),1,3);
		add_action('woocommerce_product_options_pricing',array(&$this,'add_add_questions_input_text_to_simple_product'));
		add_action( 'save_post', array( &$this, 'on_save' ), 999, 2 );
		add_action( 'wp_ajax_woocommerce_save_variations', array( &$this, 'on_variation_ajax_save' ) );
		add_action( 'woocommerce_variable_product_bulk_edit_actions', array( &$this, 'add_bulk_options_to_select_menu' ) );
		add_action( 'admin_head', array( &$this, 'add_scripts' ), 1 );
	}
	public function add_scripts()
	{
		$page_obj = get_current_screen();
		if(!isset($page_obj ) || $page_obj->id != 'product')
			return;
		
		wp_register_script('wcsts-product-page', WCSTS_PLUGIN_PATH.'/js/backend-product-page.js', array('jquery') );
		$translation_array = array(
			'bulk_questions_number_label' => __( 'Enter a number of questions to assing', 'woocommerce-support-ticket-system' )
		);
		wp_localize_script( 'wcsts-product-page', 'wcsts', $translation_array );
		wp_enqueue_script( 'wcsts-product-page' );
		
		
	}
	public function add_bulk_options_to_select_menu()
	{
		?>
		<optgroup label="<?php esc_attr_e( 'Pay Per Ticket', 'woocommerce-support-ticket-system' ); ?>">
			<option value="ppt_bulk_assign_questions_number"><?php _e( 'Assign questions number to all variations', 'woocommerce-support-ticket-system' ); ?></option>
		</optgroup>
		<?php 
	}
	public function add_questions_input_text_to_variation($loop, $variation_data, $variation )
	{
		
		global $wcsts_product_model, $wcsts_wpml_helper;
		//wcsts_var_dump($variation);
		$variation_id = $wcsts_wpml_helper->get_main_language_id($variation->ID);
		$value = $wcsts_product_model->get_product_questions_number($variation_id);
		
		
		woocommerce_wp_text_input( array(
				'id'          => "wcsts_ppt_questions_number_{$variation->ID}",
				'name'          => "wcsts_ppt_questions_number[{$variation->ID}]",
				'value'       =>  $value,
				'type'       =>  'number',
				'custom_attributes'       =>  array('min' => 0),
				'class' 			=> 'wcsts_questions_number_input',
				'wrapper_class'   =>  'form-row form-row-full',
				'label'       => __( 'Questions number', 'woocommerce-support-ticket-system' ),
				'placeholder' => __( 'Questions number', 'woocommerce-support-ticket-system' )
			) );
		//echo '</p>';
	}
	public function add_add_questions_input_text_to_simple_product( )
	{  
		global $post, $wcsts_product_model, $wcsts_wpml_helper;
		$product_id = $wcsts_wpml_helper->get_main_language_id($post->ID);
		$value = $wcsts_product_model->get_product_questions_number($product_id);
		woocommerce_wp_text_input( array(
				'id'          => 'wcsts_ppt_questions_number',
				'value'       =>  $value,
				'type'       =>  'number',
				'custom_attributes'       =>  array('min' => 0),
				'label'       => __( 'Questions number', 'woocommerce-support-ticket-system' ),
				'placeholder' => __( 'Questions number', 'woocommerce-support-ticket-system' ),
				//'description' => __( 'Enter the number of questions the user can make.', 'woocommerce-support-ticket-system' ),
			) );
	}
	public function on_save($product_id, $product_obj)
	{
		global $wcsts_wpml_helper, $wcsts_product_model;
		$product_id = $wcsts_wpml_helper->get_main_language_id($product_id);
		
		/*if ( !isset( $_POST['wcsts_admin_action'] ) || !wp_verify_nonce( $_POST['wcsts_admin_action'], 'wcsts_admin_ticket_edit' ) )
			return;*/
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        return;
		// AJAX? Not used here
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) 
				return;
		if($product_obj->post_type != 'product')
			return;
		
		if(!isset($_POST['wcsts_ppt_questions_number']))
			return;
		
		
		if(!is_array($_POST['wcsts_ppt_questions_number']))
				$wcsts_product_model->update_product_questions_number($product_id, $_POST['wcsts_ppt_questions_number']);
		else 
			foreach($_POST['wcsts_ppt_questions_number'] as $variation_id => $questions_number)
			{
				$variation_id = $wcsts_wpml_helper->get_main_language_id($variation_id);
				$wcsts_product_model->update_product_questions_number($variation_id, $questions_number);
			}
			
	}
	public function on_variation_ajax_save()
	{
		global $wcsts_wpml_helper, $wcsts_product_model;
		if(isset($_POST['wcsts_ppt_questions_number']) && is_array($_POST['wcsts_ppt_questions_number']))
			foreach($_POST['wcsts_ppt_questions_number'] as $variation_id => $questions_number)
			{
				$variation_id = $wcsts_wpml_helper->get_main_language_id($variation_id);
				$wcsts_product_model->update_product_questions_number($variation_id, $questions_number);
			}
	}
}
?>