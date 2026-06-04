<?php
/**
 * Product filter table row.
 *
 * @var array $field Field data.
 * @var int   $row_index Row index.
 * @var array $filter Current condition filter.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<tr class="row-input -row-<?php echo esc_attr( $row_index ); ?>" data-row-id="<?php echo esc_attr( $row_index ); ?>">
	<td class="col-input -<?php echo esc_attr( $field['id'] ); ?> -type">
		<?php
		self::output_input(
			WC_BOGOF_Conditions::get_metabox_type_field( $row_index, $field, $filter )
		);
		?>
	</td>
	<td class="col-input -<?php echo esc_attr( $field['id'] ); ?> -modifier">
	<?php
		self::output_input(
			array(
				'type'   => 'group',
				'fields' => WC_BOGOF_Conditions::get_metabox_modifier_fields( $row_index, $field, $filter ),
			)
		);
		?>
	</td>
	<td class="col-input -<?php echo esc_attr( $field['id'] ); ?> -value">
	<?php
		self::output_input(
			array(
				'type'   => 'group',
				'fields' => WC_BOGOF_Conditions::get_metabox_value_fields( $row_index, $field, $filter ),
			)
		);
		?>
	</td>
	<td class="remove">
		<a class="wc-bogo-icon -minus remove-row" href="#"></a>
	</td>
</tr>
