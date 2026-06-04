<?php

class acf_order_status_selector extends acf_field {


	
	function __construct() {

		$this->name     = 'order_staus_selector';
		$this->label    = esc_html__( 'Order Status Selector', 'acf-order-status-selector' );
		$this->category = esc_html__( 'Choice', 'acf' );
		$this->defaults = array(
			'return_value' => 'name',
			'field_type'   => 'checkbox',
			'allowed_order_statuses'   => '',
		);

		parent::__construct();

	}


	
	function render_field_settings( $field ) {

		acf_render_field_setting( $field, array(
			'label'			=> esc_html__('Return Format','acf-order-status-selector'),
			'instructions'	=> esc_html__('Specify the returned value type','acf-order-status-selector'),
			'type'			=> 'radio',
			'name'			=> 'return_value',
			'layout'  =>  'horizontal',
			'choices' =>  array(
				'name'   => esc_html__( 'Status Name', 'acf-order-status-selector' ),
				
			)
		));

		$statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array();
		acf_render_field_setting( $field, array(
			'label'			=> esc_html__('Allowed Statuses','acf-order-status-selector'),
			'type'			=> 'checkbox',
			'name'			=> 'allowed_order_statuses',
			'multiple'      => true,
			'instructions'   => esc_html__( 'To allow all statuses, select none or all of the options to the right', 'acf-order-status-selector' ),
			'choices' => $statuses
		));

		acf_render_field_setting( $field, array(
			'label'			=> esc_html__('Field Type','acf-order-status-selector'),
			'type'			=> 'select',
			'name'			=> 'field_type',
			'choices' => array(
				esc_html__( 'Multiple Values', 'acf-order-status-selector' ) => array(
					'checkbox' => esc_html__( 'Checkbox', 'acf-order-status-selector' ),
					'multi_select' => esc_html__( 'Multi Select', 'acf-order-status-selector' )
				),
				esc_html__( 'Single Value', 'acf-order-status-selector' ) => array(
					'radio' => esc_html__( 'Radio Buttons', 'acf-order-status-selector' ),
					'select' => esc_html__( 'Select', 'acf-order-status-selector' )
				)
			)
		));



	}




	function render_field( $field ) 
	{
		$statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array();

		foreach( (array)$statuses as $status => $data ) {
			if( is_array( $field['allowed_order_statuses'] ) && !in_array( $status, $field['allowed_order_statuses'] ) ) {
				unset( $roles[$status] );
			}
		}

		$statuses = apply_filters( 'acfrsf/allowed_order_statuses', $statuses, $field );

		// Select and multiselect fields
	    if( $field['field_type'] == 'select' || $field['field_type'] == 'multi_select' ) :
	    	$multiple = ( $field['field_type'] == 'multi_select' ) ? 'multiple="multiple"' : '';
		?>

			<select name='<?php echo $field['name'] ?>[]' <?php echo $multiple ?>>
				<?php
					foreach( (array)$statuses as $status => $data ) :
					$selected = ( !empty( $field['value'] ) && in_array( $status, $field['value'] ) ) ? 'selected="selected"' : '';
				?>
					<option <?php echo $selected ?> value='<?php echo $status ?>'><?php echo $data ?></option>
				<?php endforeach; ?>
			</select>
		<?php
		// checkbox and radio button fields
		else :
			echo '<ul class="acf-'.$field['field_type'].'-list '.$field['field_type'].' vertical">';
			foreach((array) $statuses as $status => $data ) :
				$checked = ( !empty( $field['value'] ) && in_array( $status, $field['value'] ) ) ? 'checked="checked"' : '';
		?>
		<li><label><input <?php echo $checked ?> type="<?php echo $field['field_type'] ?>" name="<?php echo $field['name'] ?>[]" value="<?php echo $status ?>"><?php echo $data ?></label></li>
		<?php
			endforeach;

			echo '<input type="hidden" name="' .  $field['name'] . '[]" value="" />';

			echo '</ul>';
		endif;
	}


	
	function format_value($value, $post_id, $field) {
		/* if( $field['return_value'] == 'object' )
		{
			foreach( $value as $key => $name ) {
				$value[$key] = get_role( $name );
			}
		} */
		return $value;
	}


	
	function load_value($value, $post_id, $field) {

		/* if( $field['return_value'] == 'object' )
		{
			foreach( $value as $key => $name ) {
				$value[$key] = get_role( $name );
			}
		} */

		return $value;
	}

}

new acf_order_status_selector();

?>