<?php
/**
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 4.4.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;
?>
<style>
	#nmgrcf-cf-settings-form .nmgr-title {
		font-size: 1.275em;
		margin-bottom: 5px;
	}

	#nmgrcf-cf-settings-form td {
		padding-bottom: 10px;
	}

</style>

<?php
$label = 'nmgrcf_crowdfunded_' . $item->get_id();
?>

<form id="nmgrcf-cf-settings-form" class="nmgrcf-form" data-nmgr_post_action="save_crowdfund_settings">
	<?php
	if ( !empty( $args[ 'data' ] ) ) {
		foreach ( $args[ 'data' ] as $key => $value ) {
			echo "<input type='hidden' name='data[{$key}]' value='" . json_encode( $value ) . "'>";
		}
	}
	?>
	<div class="nmgr-title"><?php echo esc_html( $item->get_product_name() ); ?></div>
	<table>
		<tr>
			<td>
				<label for="<?php echo esc_attr( $label ); ?>">
					<?php esc_html_e( 'Crowdfund', 'nm-gift-registry' ); ?>
				</label>
			</td>
			<td>
				<?php
				$cb_args = [
					'input_id' => $label,
					'input_name' => 'crowdfunded',
					'checked' => $item->is_crowdfunded(),
					'show_hidden_input' => true,
				];

				if ( $item->maintain_crowdfund_status() ) {
					$maintain_status_notice = nmgrcf_get_item_maintain_crowdfund_status_notice( $item );
					$cb_args[ 'input_attributes' ][ 'readonly' ] = 'readonly';
					$cb_args[ 'label_attributes' ][ 'title' ] = $maintain_status_notice; // for html notice
					$cb_args[ 'label_attributes' ][ 'data-nmgrcf_disabled_notice' ] = $maintain_status_notice; // for js alert
				}
				echo nmgr_get_checkbox_switch( $cb_args );
				?>
			</td>
		</tr>
		<tr>
			<td>
				<?php esc_html_e( 'Minimum contribution (optional)', 'nm-gift-registry-crowdfunding' ); ?>
			</td>
			<td>
				<?php
				$crowdfund_data = $item->get_crowdfund_data();
				nmgrcf_price_box( array(
					'name' => 'minimum_amount',
					'id' => 'nmgrcf_fc_min_amt',
					'currency-symbol-border' => true,
					'value' => $crowdfund_data[ 'min_amount' ] ?? '',
				) );
				?>
			</td>
		</tr>
	</table>
</form>
