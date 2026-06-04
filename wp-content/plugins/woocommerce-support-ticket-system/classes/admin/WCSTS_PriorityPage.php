<?php 
class WCSTS_PriorityPage
{
	public function __construct() 
	{
		//Table
		add_action('wcsts_ticket_priority_edit_form_fields', array( &$this,'show_extra_attributes_on_term_edit_page') );
		add_filter('manage_edit-wcsts_ticket_priority_columns', array( &$this,'add_wcsts_ticket_priority_heads_column_content'));
		add_action('manage_wcsts_ticket_priority_custom_column',  array( &$this,'add_wcsts_ticket_priority_column_content'),5, 3);
		add_action('wcsts_ticket_priority_add_form_fields',  array( &$this,'add_wcsts_ticket_priority_color_to_add_new_priority_form'));
		
		//Crud
		add_action('edited_wcsts_ticket_priority', array( &$this,'save_edited_taxonomy_attributes'));
		add_action('delete_wcsts_ticket_priority', array( &$this,'delete_extra_taxonomy_fields'), 10, 4);
		add_action('create_term', array( &$this,'add_extra_taxonomy_attributes'), 10 ,3);
	}
	private function add_common_js()
	{
		wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker');
		wp_enqueue_script( 'wp-color-picker');
		
		wp_enqueue_script('wcsts-priority-page',  WCSTS_PLUGIN_PATH.'/js/backend-priority-page.js', array('jquery'));
		
	}
	function add_wcsts_ticket_priority_heads_column_content($columns)
	 {

	   $columns['background-color'] = esc_html__('Background color', 'woocommerce-support-ticket-system'); 
	   $columns['text-color'] =esc_html__('Text color', 'woocommerce-support-ticket-system'); 

	   return $columns;
	}
	function  add_wcsts_ticket_priority_column_content($content,$column_name,$term_id)
	{
		global $wcsts_option_helper;
		
		switch ($column_name) 
		{
			case 'background-color':
				$attributes = $wcsts_option_helper->get_priority_term_attributes($term_id);
				$background_color = isset($attributes['background_color']) ? $attributes['background_color']: "none";
				$content = $background_color != "none" ? "<div style='width:100px; height:30px; background-color:{$background_color}; display:block;'></div>" : esc_html__('None', 'woocommerce-support-ticket-system');
				break;
			case 'text-color': 
				$attributes = $wcsts_option_helper->get_priority_term_attributes($term_id);
				$text_color = isset($attributes['text_color']) ? $attributes['text_color']: "#000000";
				$content = "<div style='width:100px; height:30px; background-color:{$text_color}; display:block;'></div>" ; 
				break;
			default:
				break;
		}
		
		return $content;
	}
	function show_extra_attributes_on_term_edit_page($tag) 
	{
		//ToDo: WPML get main id ?
		
		global $wcsts_option_helper;
		
		$this->add_common_js();
		
		$attributes = $wcsts_option_helper->get_priority_term_attributes($tag->term_id);
		
		$background_color = isset($attributes['background_color']) ? $attributes['background_color']: "none";
		$text_color = isset($attributes['text_color']) ? $attributes['text_color']: "#000000";
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_Image_url"><?php esc_html_e('Background color', 'woocommerce-support-ticket-system'); ?></label></th>
			<td>
				<input name="wcsts_attributes[background_color]"  class="jscolor color-field"  value="<?php echo $background_color; ?>" data-default-color="<?php echo $background_color; ?>"></input>
				<span class="description" style="display:block; clear:both;" ><?php esc_html_e('Set a background color for background. If none selected, no background color will be applied.', 'woocommerce-support-ticket-system'); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_Image_url"><?php esc_html_e('Text color', 'woocommerce-support-ticket-system'); ?></label></th>
			<td>
				<input name="wcsts_attributes[text_color]"  class="jscolor color-field"  value="<?php echo $text_color; ?>" data-default-color="<?php echo $text_color; ?>"></input>
				<span class="description" style="display:block; clear:both;" ><?php esc_html_e('Set a text color. If none selected, #000000 (black) will be the default used.', 'woocommerce-support-ticket-system'); ?></span>
			</td>
		</tr>
		
		<?php
	}
	function add_wcsts_ticket_priority_color_to_add_new_priority_form($taxonomy)
	{
		$this->add_common_js();
		
		?>
		<div class="form-field">
			<label><?php esc_html_e('Background color','woocommerce-support-ticket-system'); ?></label>
			<input name="wcsts_attributes[background_color]"  class="jscolor color-field"  value=""></input>
			<p ><?php esc_html_e('Set a background color for background. If none selected, no background color will be applied.', 'woocommerce-support-ticket-system'); ?></p>
		</div>
		
		<div class="form-field">
			<label><?php esc_html_e('Text color','woocommerce-support-ticket-system'); ?></label>
			<input type="text" name="wcsts_attributes[text_color]"  class="jscolor color-field" value=""></input>
			<p ><?php esc_html_e('Set a text color. If none selected, #000000 (black) will be the default used.', 'woocommerce-support-ticket-system'); ?></p>
		</div>
		<?php
	}
	function add_extra_taxonomy_attributes($term_id, $tt_id, $taxonomy)
	{
		if($taxonomy != 'wcsts_ticket_priority')
			return;
		
		$this->save_edited_taxonomy_attributes($term_id);
		
	}
	function save_edited_taxonomy_attributes( $term_id ) 
	{
		global $wcsts_option_helper;
		
		
		/* Example:
			int(15)

			array(10) {
			  ["action"]=>
			  string(9) "editedtag"
			  ["tag_ID"]=>
			  string(2) "15"
			  ["taxonomy"]=>
			  string(21) "wcsts_ticket_priority"
			  ["_wp_original_http_referer"]=>
			  string(153) "https://vanquishplugins.com/demo/wp-admin/edit-tags.php?taxonomy=wcsts_ticket_priority&lang=en&message=3&post_type&post_type=wcsts_ticket"
			  ["_wpnonce"]=>
			  string(10) "b9f90f4db3"
			  ["_wp_http_referer"]=>
			  string(218) "/demo/wp-admin/term.php?taxonomy=wcsts_ticket_priority&tag_ID=15&post_type=wcsts_ticket&wp_http_referer=%2Fdemo%2Fwp-admin%2Fedit-tags.php%3Ftaxonomy%3Dwcsts_ticket_priority%26post_type%3Dwcsts_ticket&message=3&lang=en"
			  ["name"]=>
			  string(4) "High"
			  ["slug"]=>
			  string(4) "high"
			  ["description"]=>
			  string(0) ""
			  ["wppas_term_meta"]=>
			  array(1) {
				["readonly"]=>
				string(2) "no"
			  }
			}

		*/
		
		//ToDo: save extra attributes (WPML: Use main id) ??
		
	
		if ( isset( $_POST['wcsts_attributes'] ) ) 
		{
			$data_to_save  = $_POST['wcsts_attributes'];
			if(!isset($data_to_save['text_color']) || $data_to_save['text_color'] == "")
				$data_to_save['text_color'] = "#000000";
			if(!isset($data_to_save['background_color']) || $data_to_save['background_color'] == "")
				$data_to_save['background_color'] = "none";
			
			$wcsts_option_helper->set_priorities_attributes($term_id, $data_to_save);
		}
	}
	function delete_extra_taxonomy_fields($term, $term_id, $deleted_term, $object_ids)
	{
		//Example
		/* 
			int(50)

			string(2) "50"

			object(WP_Term)#3987 (10) {
			  ["term_id"]=>
			  int(50)
			  ["name"]=>
			  string(6) "asd213"
			  ["slug"]=>
			  string(6) "asd213"
			  ["term_group"]=>
			  int(0)
			  ["term_taxonomy_id"]=>
			  int(50)
			  ["taxonomy"]=>
			  string(21) "wcsts_ticket_priority"
			  ["description"]=>
			  string(3) "asd"
			  ["parent"]=>
			  int(0)
			  ["count"]=>
			  int(0)
			  ["filter"]=>
			  string(3) "raw"
			}

			array(0) {
			}

			*/
		global $wcsts_option_helper;
		$wcsts_option_helper->delete_priority_term_attributes($term_id);
	}
	
	function remove_category_taxonomies_parent_selection()
	{
		if($_GET['taxonomy'] == 'wcsts_ticket_priority')
		{
			$parent = 'parent()';
			if ( isset( $_GET['action'] ) )
				$parent = 'parent().parent()';

			//This is the only way to achieve the removal, cannot be performed by including an exeternal script:
			?>
				<script type="text/javascript">
					jQuery(document).ready(function($)
					{     
						$('label[for=parent]').<?php echo $parent; ?>.remove();       
					});
				</script>
			<?php
		}
	}
}
?>