<?php
defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';
?>


<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
	<thead>
		<tr>
			<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Message', 'nm-gift-registry' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php echo wp_kses_post( wptexturize( $message ) ); ?>
			</td>
		</tr>
	</tbody>
</table>
