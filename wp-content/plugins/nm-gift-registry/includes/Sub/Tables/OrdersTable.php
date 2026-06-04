<?php

namespace NMGR\Sub\Tables;

use NMGR\Tables\Table;
use NMGR\Fields\Fields;

defined( 'ABSPATH' ) || exit;

class OrdersTable extends Table {

	protected $id = 'orders';
	protected $wishlist;
	protected $items_per_page = 12;

	public function __construct( \NMGR_Wishlist $wishlist ) {
		$this->wishlist = $wishlist;

		$fields = new Fields();
		$fields->set_id( $this->id );
		$fields->set_data( $this->data() );
		$fields->filter_showing();
		$fields->set_values( [ $this, 'get_cell_value' ] );

		$this->set_data( $fields->get_data() );
	}

	protected function get_items_count() {
		return $this->wishlist->get_order_ids( [
				'select' => 'COUNT(DISTINCT posts.ID)',
				'get' => 'var'
			] );
	}

	protected function rows_object() {
		$orders = [];
		$order_ids = $this->wishlist->get_order_ids( $this->pagination_args );
		foreach ( $order_ids as $order_id ) {
			$orders[] = wc_get_order( $order_id );
		}
		return $orders;
	}

	private function data() {
		$data = [
			'customer' => [
				'label' => __( 'Customer', 'nm-gift-registry' ),
			],
			'order' => [
				'label' => __( 'Order', 'nm-gift-registry' ),
				'show' => is_nmgr_admin(),
			],
			'order-date' => [
				'label' => __( 'Order date', 'nm-gift-registry' ),
			],
			'items-purchased' => [
				'label' => __( 'Items purchased', 'nm-gift-registry' ),
			],
			'total-spent' => [
				'label' => __( 'Total spent', 'nm-gift-registry' ),
				'desc' => __( 'Total spent including tax', 'nm-gift-registry' ),
			],
		];

		return $data;
	}

	public function get_totals_template() {
		ob_start();
		?>
		<div class="nmgr-after-table-row orders-total">
			<table class="total">
				<tr class="nmgr-row">
					<td class="label"><?php esc_html_e( 'Total', 'nm-gift-registry' ); ?>:</td>
					<td width="1%"></td>
					<td class="total">
						<?php echo wc_price( $this->wishlist->get_amount_received_in_orders() ); ?>
					</td>
				</tr>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	protected function get_cell_value() {
		$key = $this->get_cell_key();
		$order = $this->get_row_object();
		$wishlist = $this->wishlist;

		ob_start();

		switch ( $key ) {
			case 'customer':
				$user = $order->get_user();
				$using_billing_details = false;
				$billing_full_name = trim( $order->get_formatted_billing_full_name() );

				if ( $user ) {
					$username = "$user->first_name $user->last_name";
					$customer = $username ? $username : $user->display_name;
					$email = $user->user_email;
				} else {
					$customer = $billing_full_name ? $billing_full_name : __( 'Guest', 'nm-gift-registry' );
					$email = $order->get_billing_email() ? $order->get_billing_email() : '';
					$using_billing_details = true;
				}

				if ( !$billing_full_name && !$user ) {
					echo '<span class="nmgr-guest-text">' . esc_html( $customer ) . '</span>';
				} else {
					echo esc_html( $customer );
				}

				if ( $order->is_created_via( 'nmgr_wishlist' ) ) {
					?>
					<span class="nmgr-tip nmgr-badge"
								title="<?php esc_attr_e( nmgr_get_custom_order_notice() ); ?>">&mcy;</span>
					<?php
				}

				if ( apply_filters( "nmgr_orders_table_{$key}_column_show_email", true ) && $email ) {
					echo '<div class="meta-item email">' .
					'<strong>' . __( 'Email:', 'nm-gift-registry' ) . ' </strong>' . sanitize_email( $email ) .
					'</div>';
				}

				if ( apply_filters( "nmgr_orders_table_{$key}_column_show_phone", true ) &&
					$using_billing_details && $order->get_billing_phone() ) {
					echo '<div class="meta-item phone"><strong>' . __( 'Tel:', 'nm-gift-registry' ) . ' </strong>' . esc_html( $order->get_billing_phone() ) . '</div>';
				}

				do_action_deprecated( "nmgr_orders_table_{$key}_column", [], '4.6.0', 'nmgr_fields_orders' );

				break;
			case 'order':
				$order_no = _x( '#', 'hash', 'nm-gift-registry' ) . $order->get_order_number();

				if ( $order->get_status() === 'trash' ) {
					echo $order_no;
				} else {
					$link = get_edit_post_link( $order->get_id() );
					echo '<a href="' . esc_url( $link ) . '">' . $order_no . '</a>';
				}

				do_action_deprecated( "nmgr_orders_table_{$key}_column", [], '4.6.0', 'nmgr_fields_orders' );
				break;
			case 'order-date':
				echo nmgr_format_date( $order->get_date_created() );
				do_action_deprecated( "nmgr_orders_table_{$key}_column", [], '4.6.0', 'nmgr_fields_orders' );
				break;
			case 'items-purchased':
				echo '<ul class="items-purchased-list">';
				foreach ( $order->get_items() as $item_id => $item ) {
					if ( nmgr_get_wishlist_id_for_order_item( $item ) === $wishlist->get_id() ) {
						echo '<li>';
						$permalink = is_admin() ?
							get_edit_post_link( $item->get_product_id() ) :
							$item->get_product()->get_permalink( $item );
						echo $permalink ? '<a href="' . esc_url( $permalink ) . '">' . wp_kses_post( $item->get_name() ) . '</a>' : wp_kses_post( $item->get_name() );
						echo ' &times; ' . ( int ) ($item->get_quantity() + $order->get_qty_refunded_for_item( $item_id ));
						echo '</li>';
					}
				}
				echo '</ul>';
				do_action_deprecated( "nmgr_orders_table_{$key}_column", [], '4.6.0', 'nmgr_fields_orders' );
				break;
			case 'total-spent':
				$total = $wishlist->get_amount_received_in_order( $order );
				echo wc_price( $total );
				do_action_deprecated( "nmgr_orders_table_{$key}_column", [], '4.6.0', 'nmgr_fields_orders' );
				break;
		}

		return ob_get_clean();
	}

	public function get_template() {
		return parent::get_template() . $this->get_totals_template();
	}

}
