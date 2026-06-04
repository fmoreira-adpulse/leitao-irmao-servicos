<?php

namespace NMGR\Sub\Tables;

use NMGR\Tables\Table;
use NMGR\Fields\Fields;

defined( 'ABSPATH' ) || exit;

class MessagesTable extends Table {

	protected $id = 'messages';
	protected $wishlist;
	protected $items_per_page = 12;

	public function __construct( \NMGR_Wishlist $wishlist ) {
		$this->wishlist = $wishlist;

		$fields = new Fields();
		$fields->set_id( $this->id );
		$fields->set_data( $this->data() );
		$fields->set_values( [ $this, 'get_cell_value' ] );

		$this->set_data( $fields->get_data() );
	}

	protected function get_items_count() {
		return $this->wishlist->get_messages_count();
	}

	protected function rows_object() {
		return $this->wishlist->get_messages( $this->pagination_args );
	}

	private function data() {
		$data = [
			'customer' => [
				'label' => __( 'Customer', 'nm-gift-registry' ),
			],
			'message' => [
				'label' => __( 'Message', 'nm-gift-registry' ),
			],
			'order-date' => [
				'label' => __( 'Date', 'nm-gift-registry' ),
			],
			'order-number' => [
				'label' => __( 'Order', 'nm-gift-registry' ),
			],
			'items-ordered' => [
				'label' => __( 'Items ordered', 'nm-gift-registry' ),
			],
			'actions' => [
				'label' => '',
			],
		];

		return $data;
	}

	/**
	 *
	 * @param this $table
	 * @return type
	 */
	protected function get_cell_value() {
		$key = $this->get_cell_key();
		$message = $this->get_row_object();
		$wishlist = $this->wishlist;

		ob_start();

		switch ( $key ) {
			case 'customer':
				echo esc_html( $message->name );
				echo (!empty( $message->email )) ? '<br><small>' . esc_html( $message->email ) . '</small>' : '';
				break;
			case 'message':
				echo esc_html( $message->content );
				break;
			case 'order-date':
				echo esc_html( $message->date_created );
				break;
			case 'order-number':
				$order_no = _x( '#', 'hash', 'nm-gift-registry' ) . $message->order_id;
				$link = '';

				if ( is_nmgr_admin() ) {
					$order = wc_get_order( $message->order_id );
					if ( $order && 'trash' !== $order->get_status() ) {
						$link = get_edit_post_link( $order->get_id() );
					}
				}

				echo $link ? wp_kses_post( "<a href='$link'>$order_no</a>" ) : esc_html( $order_no );
				break;
			case 'items-ordered':
				if ( isset( $message->items_ordered ) && !empty( $message->items_ordered ) ) :
					?>
					<div class="items-ordered-content content">
						<ul style="margin:0;">
							<?php
							foreach ( $message->items_ordered as $item ) {
								echo '<li>' . esc_html( "{$item[ 'name' ]} &times; {$item[ 'quantity' ]}" ) . '</li>';
							}
							?>
						</ul>
					</div>
					<?php
				endif;
				break;
			case 'actions':
				if ( !$wishlist->is_archived() ) :
					?>
					<a href="#" class="nmgr-tip delete-wishlist-message"
						 title="<?php esc_attr_e( 'Delete', 'nm-gift-registry' ); ?>"
						 data-nmgr_post_action="delete_message"
						 data-notice="<?php esc_attr_e( 'Are you sure you want to delete this message?', 'nm-gift-registry' ); ?>"
						 data-message_id="<?php echo absint( $message->id ); ?>"
						 data-wishlist_id="<?php echo absint( $wishlist->get_id() ); ?>">
							 <?php
							 echo nmgr_get_svg( array(
								 'icon' => 'trash-can',
								 'fill' => '#ccc'
							 ) );
							 ?>
					</a>
					<?php
				endif;

				break;
		}

		return ob_get_clean();
	}

	protected function get_row_attributes() {
		$message = $this->get_row_object();

		$attributes = [
			'id' => 'nmgr_message_' . $message->id,
			'class' => [
				'nmgr-message'
			],
		];

		return $attributes;
	}

}
