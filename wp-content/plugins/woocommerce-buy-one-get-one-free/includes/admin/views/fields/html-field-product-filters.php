<?php
/**
 * Product filter field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

$default = empty( $field['default'] ) ? [] : $field['default'];
$filters = ! empty( $field['value'][0] ) ? $field['value'][0] : $field['default'];
?>

<div class="wc-bogo-repeater" id="<?php echo esc_attr( $field['id'] ); ?>">
	<table class="wc-bogo-table">
		<tbody>
			<?php
			foreach ( $filters as $row_index => $filter ) {
				if ( empty( $filter['type'] ) || ! WC_BOGOF_Conditions::get_condition( $filter['type'] ) ) {
					continue;
				}
				include dirname( __FILE__ ) . '/html-product-filters-table-row.php'; // phpcs:ignore
			}
			?>
		</tbody>
	</table>
	<a class="button add-row" href="wc-bogo-product-filter-<?php echo esc_attr( $field['id'] ); ?>">&plus;&nbsp;<?php esc_html_e( 'Add condition', 'wc-buy-one-get-one-free' ); ?></a>
</div>
<script type="text/html" id="tmpl-wc-bogo-product-filter-<?php echo esc_attr( $field['id'] ); ?>">
<?php
$row_index = '{{{data.rowId}}}';
$filter    = array(
	'type'     => false,
	'modifier' => false,
	'value'    => false,
);
include dirname( __FILE__ ) . '/html-product-filters-table-row.php'; // phpcs:ignore
?>
</script>
