<?php

namespace NMGRCF\Tables;

use NMGR\Tables\Table;
use NMGR\Fields\Fields;

defined( 'ABSPATH' ) || exit;

class CrowdfundsTable extends Table {

	protected $id = 'crowdfunds';
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
		return $this->wishlist->get_crowdfund_order_ids( [ 'count' => true ] );
	}

	protected function rows_object() {
		$contributions = [];

		$order_ids = $this->wishlist->get_crowdfund_order_ids( $this->pagination_args );
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			foreach ( $order->get_items() as $order_item ) {
				$wishlist_item_id = $order_item->get_meta( 'nmgrcf_item_id' );
				if ( $wishlist_item_id ) {
					$wishlist_item = nmgr_get_wishlist_item( $wishlist_item_id );
					if ( $wishlist_item && ( int ) $wishlist_item->get_wishlist_id() === ( int ) $this->wishlist->get_id() ) {
						$contributions[] = array(
							'order' => $order,
							'item' => $wishlist_item,
							'amount' => $order_item->get_total() - $order->get_total_refunded_for_item( ( int ) $order_item->get_id() ),
						);
					}
				}
			}
		}

		return $contributions;
	}

	private function data() {
		$data = [
			'contributor' => [
				'label' => __( 'Contributor', 'nm-gift-registry-crowdfunding' ),
			]
		];

		if ( is_nmgr_admin() ) {
			$data[ 'order' ] = [
				'label' => __( 'Order', 'nm-gift-registry-crowdfunding' ),
			];
		}

		$data[ 'date-contributed' ] = [
			'label' => __( 'Date contributed', 'nm-gift-registry-crowdfunding' ),
		];

		$data[ 'item-crowdfunded' ] = [
			'label' => __( 'Item crowdfunded', 'nm-gift-registry-crowdfunding' ),
		];

		$data[ 'amount' ] = [
			'label' => __( 'Amount', 'nm-gift-registry-crowdfunding' ),
		];

		return $data;
	}

	protected function get_cell_value() {
		$key = $this->get_cell_key();
		$contribution = $this->get_row_object();

		ob_start();

		switch ( $key ) {
			case 'contributor':
				$user = $contribution[ 'order' ]->get_user();
				$using_billing_details = false;
				$billing_full_name = trim( $contribution[ 'order' ]->get_formatted_billing_full_name() );

				if ( $user ) {
					$username = "$user->first_name $user->last_name";
					$customer = $username ? $username : $user->display_name;
					$email = $user->user_email;
				} else {
					$customer = $billing_full_name ? $billing_full_name : __( 'Guest', 'nm-gift-registry-crowdfunding' );
					$email = $contribution[ 'order' ]->get_billing_email() ? $contribution[ 'order' ]->get_billing_email() : '';
					$using_billing_details = true;
				}

				if ( !$billing_full_name && !$user ) {
					echo '<span class="nmgr-guest-text">' . esc_html( $customer ) . '</span>';
				} else {
					echo esc_html( $customer );
				}

				if ( apply_filters( "nmgrcf_crowdfunds_table_{$key}_column_show_email", true ) && $email ) {
					echo '<div class="meta-item email">';
					echo '<strong>' . esc_html__( 'Email:', 'nm-gift-registry-crowdfunding' ) . ' </strong>' . esc_html( sanitize_email( $email ) );
					echo '</div>';
				}

				if ( apply_filters( "nmgrcf_crowdfunds_table_{$key}_column_show_phone", true ) &&
					$using_billing_details && $contribution[ 'order' ]->get_billing_phone() ) {
					echo '<div class="meta-item phone"><strong>' . esc_html__( 'Tel:', 'nm-gift-registry-crowdfunding' ) . ' </strong>' . esc_html( $contribution[ 'order' ]->get_billing_phone() ) . '</div>';
				}
				break;
			case 'order':
				$label = sprintf(
					/* translators: %s: order number */
					__( 'Order #%s', 'nm-gift-registry-crowdfunding' ),
					$contribution[ 'order' ]->get_order_number()
				);
				if ( $contribution[ 'order' ]->get_status() === 'trash' ) {
					echo esc_html( $label );
				} else {
					echo '<a href="' . esc_url( get_edit_post_link( $contribution[ 'order' ]->get_id() ) ) . '">' . esc_html( $label ) . '</a>';
				}
				break;
			case 'date-contributed':
				echo esc_html( nmgr_format_date( $contribution[ 'order' ]->get_date_created() ) );
				break;
			case 'item-crowdfunded':
				$wishlist_item = $contribution[ 'item' ];
				$permalink = is_nmgr_admin() ? get_edit_post_link( $wishlist_item->get_product_id() ) : $wishlist_item->get_product_permalink();
				echo $permalink ? '<a href="' . esc_url( $permalink ) . '">' . wp_kses_post( $wishlist_item->get_product_name() ) . '</a>' : wp_kses_post( $wishlist_item->get_product_name() );
				break;
			case 'amount':
				echo wp_kses_post( wc_price( $contribution[ 'amount' ] ) );
				break;
		}

		return ob_get_clean();
	}

	public function get_totals_template() {
		$wallet_balance = $this->wishlist->get_wallet_balance();
		$crowdfund_amt_left = $this->wishlist->get_crowdfund_amount_left();
		$crowdfund_amt_received = $this->wishlist->get_crowdfund_amount_received();
		$crowdfund_amt_available = $this->wishlist->get_crowdfund_amount_available();
		$crowdfund_amt_needed = $this->wishlist->get_crowdfund_amount_needed();

		ob_start();
		?>
		<div class="nmgr-after-table-row crowdfunds-totals">
			<table class="total crowdfund-amount">
				<tr class="crowdfund-amount-received">
					<td class="label">
		<?php
		esc_html_e( 'Amount received', 'nm-gift-registry-crowdfunding' );
		echo nmgr_get_help_tip(
			esc_html( sprintf(
					/* translators: %s: wishlist type title */
					__( 'This is the amount received from actual contributions to the crowdfunded items in your %s. It does not include the amount used to fund those items from the wallet, if applicable.', 'nm-gift-registry-crowdfunding' ),
					nmgr_get_type_title()
				) )
		);
		?> :</td>
					<td width="1%"></td>
					<td class="crowdfund-amount-received">
		<?php echo wp_kses_post( wc_price( $crowdfund_amt_received ) ); ?>
					</td>
				</tr>
				<tr class="crowdfund-amount-left">
					<td class="label">
		<?php
		esc_html_e( 'Amount still needed', 'nm-gift-registry-crowdfunding' );
		echo nmgr_get_help_tip(
			esc_html__( 'This is the amount still needed to completely fulfill your crowdfunding campaign. It takes into account the amount already received for the crowdfunded items and the amount available in the wallet.', 'nm-gift-registry-crowdfunding' )
		);
		?> :</td>
					<td width="1%"></td>
					<td class="crowdfund-amount-left">
		<?php echo wp_kses_post( wc_price( $crowdfund_amt_left ) ); ?>
					</td>
				</tr>
			</table>
			<table class="total total-crowdfund-amount nmgr-border-top">
				<tr class="total-amount-needed nmgrcf-grey">
					<td class="label">
		<?php
		esc_html_e( 'Amount expected', 'nm-gift-registry-crowdfunding' );
		echo nmgr_get_help_tip(
			esc_html__( 'This is the amount expected to be received from your crowdfunding campaign that should make it fulfilled. It is based on the original unpurchased amount of the crowdfunded items from the start of the campaign.', 'nm-gift-registry-crowdfunding' )
		);
		?> :</td>
					<td width="1%"></td>
					<td class="total-amount-needed">
		<?php echo wp_kses_post( wc_price( $crowdfund_amt_needed ) ); ?>
					</td>
				</tr>


				<tr class="wallet-amount nmgrcf-grey">
					<td class="label">
		<?php
		esc_html_e( 'Amount in wallet', 'nm-gift-registry-crowdfunding' );
		echo nmgr_get_help_tip(
			esc_html__( 'This is the amount you currently have in your wallet that can be used to fund crowdfunded items. It is not currently part of the contributions received for any item.', 'nm-gift-registry-crowdfunding' )
		);
		?> :</td>
					<td width="1%"></td>
					<td class="wallet-amount"><?php echo wp_kses_post( wc_price( $wallet_balance ) ); ?></td>
				</tr>


				<tr class="total-amount-available">
					<td class="label">
		<?php
		if ( $crowdfund_amt_needed && !$crowdfund_amt_left ) {
			echo nmgr_get_svg( array(
				'icon' => 'heart',
				'class' => 'align-with-text',
				'style' => 'margin-right:5px;',
				'fill' => '#999',
				'title' => esc_html__( 'You have enough contributions to fulfill your crowdfunding campaign.', 'nm-gift-registry-crowdfunding' ),
			) );
		}

		esc_html_e( 'Total amount available', 'nm-gift-registry-crowdfunding' );

		echo nmgr_get_help_tip(
			esc_html__( 'This is the real time amount available to your crowdfunding campaign that shows the progress of your campaign and helps to determine its fulfillment. It takes into account the amount available for each item from actual contributions received or from funding through the wallet, and the remaining amount available in the wallet.', 'nm-gift-registry-crowdfunding' )
		);
		?> :
					</td>
					<td width="1%"></td>
					<td class="total-amount-available">
		<?php echo wp_kses_post( wc_price( $crowdfund_amt_available ) ); ?>
					</td>
				</tr>

			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_template() {
		return parent::get_template() . $this->get_totals_template();
	}

}
