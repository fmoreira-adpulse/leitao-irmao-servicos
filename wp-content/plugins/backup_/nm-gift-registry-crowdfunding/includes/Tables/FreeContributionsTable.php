<?php

namespace NMGRCF\Tables;

use NMGR\Tables\Table;
use NMGR\Fields\Fields;

defined( 'ABSPATH' ) || exit;

class FreeContributionsTable extends Table {

	protected $id = 'free_contributions';
	protected $wishlist;
	protected $items_per_page = 12;
	protected $reference = [];

	public function __construct( \NMGR_Wishlist $wishlist ) {
		$this->wishlist = $wishlist;

		$fields = new Fields();
		$fields->set_id( $this->id );
		$fields->set_data( $this->data() );
		$fields->set_values( [ $this, 'get_cell_value' ] );

		$this->set_data( $fields->get_data() );

		if ( $this->wishlist ) {
			$reference = $this->wishlist->get_free_contributions_reference();
			$filtered = array_filter( $reference, function ( $ref ) {
				return is_array( $ref ) && isset( $ref[ 'purchased_amount' ] ) && 0 < ( int ) $ref[ 'purchased_amount' ];
			} );
			$this->reference = array_reverse( $filtered, true );
		}
	}

	protected function get_items_count() {
		return count( $this->reference );
	}

	protected function rows_object() {
		$contributions = [];

		if ( !empty( $this->reference ) ) {
			$limit = $this->pagination_args[ 'limit' ] ?? null;
			$page = $this->pagination_args[ 'page' ] ?? null;
			$offset = $page ? max( 0, (( int ) $page - 1) * $limit ) : 0;

			$reference = array_slice( $this->reference, $offset, $limit, true );

			foreach ( $reference as $order_id => $ref ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$contributions[] = array(
						'order' => $order,
						'amount' => $ref[ 'purchased_amount' ],
					);
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

				if ( apply_filters( "nmgrcf_free_contributions_table_{$key}_column_show_email", true ) && $email ) {
					echo '<div class="meta-item email">';
					echo '<strong>' . esc_html__( 'Email:', 'nm-gift-registry-crowdfunding' ) . ' </strong>' . esc_html( sanitize_email( $email ) );
					echo '</div>';
				}

				if ( apply_filters( "nmgrcf_free_contributions_table_{$key}_column_show_phone", true ) &&
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
			case 'amount':
				echo wp_kses_post( wc_price( $contribution[ 'amount' ] ) );
				break;
		}

		return ob_get_clean();
	}

	public function get_totals_template() {
		$wishlist = $this->wishlist;
		$amt_needed = $wishlist->get_free_contributions_amount_needed();

		ob_start();
		?>
		<div class="nmgr-after-table-row free-contributions-totals">
			<table class="total free-contributions-summary-1">
				<tr class="free-contributions-received">
					<td class="label">
						<?php
						esc_html_e( 'Total amount received', 'nm-gift-registry-crowdfunding' );
						echo nmgr_get_help_tip(
							sprintf(
								/* translators: %s: wishlist type title */
								esc_html__( 'This is the amount received from real time contributions to your %s by customers.', 'nm-gift-registry-crowdfunding' ),
								esc_html( nmgr_get_type_title() )
							)
						);
						?> :</td>
					<td width="1%"></td>
					<td class="total free-contributions-received">
						<?php echo wp_kses_post( wc_price( $wishlist->get_free_contributions_amount_received() ) ); ?>
					</td>
				</tr>

				<?php if ( $amt_needed ) : ?>
					<tr class="free-contributions-needed nmgrcf-grey">
						<td class="label">
							<?php
							esc_html_e( 'Amount still needed', 'nm-gift-registry-crowdfunding' );
							echo nmgr_get_help_tip(
								esc_html( sprintf(
										/* translators: %s: wishlist type title */
										__( 'This is the amount still needed by you. It takes into account the amount already received and the total amount expected to be received.', 'nm-gift-registry-crowdfunding' ),
										nmgr_get_type_title()
									) )
							);
							?> :</td>
						<td width="1%"></td>
						<td class="total free-contributions-needed">
							<?php echo wp_kses_post( wc_price( $wishlist->get_free_contributions_amount_left() ) ); ?>
						</td>
					</tr>
				<?php endif; ?>

			</table>
			<?php if ( $amt_needed ) : ?>
				<table class="total free-contributions-summary-2 nmgr-border-top">
					<tr class="free-contributions-expected">
						<td class="label">
							<?php
							esc_html_e( 'Amount expected', 'nm-gift-registry-crowdfunding' );
							echo nmgr_get_help_tip(
								esc_html__( 'This is the total amount you are expecting to receive.', 'nm-gift-registry-crowdfunding' )
							);
							?> :</td>
						<td width="1%"></td>
						<td class="total free-contributions-expected">
							<?php echo wp_kses_post( wc_price( $amt_needed ) ); ?>
						</td>
					</tr>
				</table>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_template() {
		return parent::get_template() . $this->get_totals_template();
	}

}
