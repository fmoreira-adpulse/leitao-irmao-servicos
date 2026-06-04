<?php

use NMGR\Fields\AccountFields;
use NMGR\Tables\WishlistsTable;
use NMGR\Tables\ItemsTable;

defined( 'ABSPATH' ) || exit;

class NMGR_Account {

	/**
	 * The current wishlist being viewed
	 * @var NMGR_Wishlist
	 */
	protected $wishlist;

	/**
	 * The current section being processed
	 * @var string
	 */
	protected $section_key;
	protected $type = 'gift-registry';
	protected $sections_data = [];
	protected $args = [];
	public $section_attributes = [];

	/**
	 * @todo Delete in version 5.0.0
	 * Added here simply because of items template
	 */
	protected $action_args;

	/**
	 * Inititialize the view for the wishlist items
	 *
	 * @param int|NMGR_Wishlist $wishlist Wishlist id or object
	 * @param array $args Arguments used to compose the view
	 */
	public function __construct( $wishlist = null ) {
		$this->set_wishlist( $wishlist );
		if ( $this->wishlist ) {
			$this->set_type( $this->wishlist->get_type() );
		}
	}

	public function set_section( $section_key ) {
		$this->section_key = $section_key;
		return $this;
	}

	/**
	 * @param int|NMGR_Wishlist $wishlist Wishlist id or object
	 */
	public function set_wishlist( $wishlist ) {
		if ( $wishlist ) {
			$this->wishlist = is_a( $wishlist, \NMGR_Wishlist::class ) ? $wishlist : nmgr_get_wishlist( $wishlist );
		}
	}

	public function set_type( $type ) {
		$this->type = $type;
	}

	/**
	 * @return NMGR_Wishlist\NMGR\Sub\Wishlist
	 */
	public function get_wishlist() {
		return $this->wishlist;
	}

	public function get_type() {
		return $this->type;
	}

	public function is_gift_registry() {
		return 'gift-registry' === $this->get_type();
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function set_args( $args ) {
		_deprecated_function( __METHOD__, '4.11' );
		$this->args = $args;
	}

	/**
	 * @deprecated since version 4.11
	 */
	public function get_args() {
		_deprecated_function( __METHOD__, '4.11' );
		$args = apply_filters_deprecated( 'nmgr_section_args', [ [], $this->section_key ], '4.10' );
		return array_merge( $args, $this->args );
	}

	/**
	 * @deprecated since version 4.11
	 */
	protected function get_default_section_vars() {
		_deprecated_function( __METHOD__, '4.11' );
		$vars = [
			'id' => $this->wishlist ? $this->wishlist->get_id() : 0,
			'wishlist' => $this->wishlist,
			'title' => $this->get_section_title(),
			'type' => $this->type,
			'section_name' => $this->section_key,
			'attributes' => $this->get_section_container_attributes(),
			'template_group' => 'account'
		];
		return $vars;
	}

	protected function get_sections() {
		return (new AccountFields( $this ) )->get_data();
	}

	public function get_sections_data() {
		if ( !$this->sections_data ) {
			$this->sections_data = apply_filters_deprecated( 'nmgr_account_sections', [ $this->get_sections(), $this ], '4.10', 'nmgr_fields_account' );
		}
		return $this->sections_data;
	}

	/**
	 * Get the data for a particular section
	 * @param string $section_key
	 * @return array
	 */
	public function get_section_data( $section_key = null ) {
		if ( !is_null( $section_key ) ) {
			_deprecated_argument( __METHOD__, '4.2.0', 'Use NMGR_Account::set section' );
			$this->section_key = $section_key;
		}
		return $this->get_sections_data()[ $this->section_key ] ?? [];
	}

	public function get_section_title( $section_key = null ) {
		if ( !is_null( $section_key ) ) {
			_deprecated_argument( __METHOD__, '4.2.0', 'Use NMGR_Account::set section' );
			$this->section_key = $section_key;
		}
		$data = $this->get_section_data();
		return $data ? apply_filters( "nmgr_{$this->section_key}_template_title", $data[ 'title' ] ?? '' ) : '';
	}

	protected function get_template( $vars ) {
		return apply_filters(
			"nmgr_{$this->section_key}_template",
			nmgr_get_template( "account/{$this->section_key}.php", $vars ),
			$vars
		);
	}

	protected function get_profile_section() {
		$wishlist = !empty( $this->wishlist ) ? $this->wishlist : nmgr()->wishlist();
		$form = new NMGR_Form( $wishlist->get_id() );
		if ( !$wishlist->get_id() && !empty( $this->get_type() ) ) {
			$form->set_type( $this->get_type() );
		}

		$overridden_file = $this->is_overridden();
		if ( $overridden_file ) {
			nmgr_overridden_notice( $overridden_file, '4.6.0' );
			$vars = $this->get_default_section_vars();
			$vars[ 'form' ] = $form;
			return $this->get_template( $vars );
		} else {
			ob_start();
			?>
			<form id="nmgr-profile-form" class="nmgr-form" method="POST">
				<?php
				echo $form->get_fields_html( 'profile' );
				echo $form->get_hidden_fields();
				echo $form->get_submit_button( 'profile' );
				?>
			</form>
			<?php
			$content = ob_get_clean();
			return $this->get_new_template( $content );
		}
	}

	protected function items_table() {
		return ItemsTable::class;
	}

	protected function get_items_section() {
		$overridden_file = $this->is_overridden();
		if ( $overridden_file ) {
			nmgr_overridden_notice( $overridden_file, '4.6.0' );
			$vars = $this->get_default_section_vars();
			$vars[ 'view' ] = nmgr()->items_view( $this->wishlist );
			return $this->get_template( $vars );
		} else {
			$wishlist = $this->wishlist;
			$content = '';

			if ( $wishlist->is_active() ) {
				$table_class = $this->items_table();
				$table = new $table_class( $wishlist );
				$view = $table->view;
				$table->setup();
				$this->action_args = [ $table->get_items(), $wishlist, $table->get_args(), $view ];
				$content = $table->get_template();
			}
			return $this->get_new_template( $content );
		}
	}

	protected function get_shipping_section() {
		if ( !nmgr_get_type_option( $this->get_type(), 'enable_shipping' ) ) {
			return;
		}

		$form = $this->wishlist ? new \NMGR_Form( $this->wishlist->get_id() ) : '';
		$customer = $this->wishlist ? new \WC_Customer( $this->wishlist->get_user_id() ) : '';

		$overridden_file = $this->is_overridden();
		if ( $overridden_file ) {
			nmgr_overridden_notice( $overridden_file, '4.6.0' );
			$vars = $this->get_default_section_vars();
			$vars2 = array_merge( $vars, array(
				'form' => $form,
				'customer' => $customer,
				) );

			return $this->get_template( $vars2 );
		} else {
			ob_start();
			?>
			<form id="nmgr-shipping-form" class="nmgr-form" method="POST">
				<?php
				if ( $customer &&
					((method_exists( $customer, 'has_shipping_address' ) && $customer->has_shipping_address()) ||
					$customer->get_shipping_address())
				) {
					nmgr_show_copy_shipping_address_btn( $customer->get_shipping() );
				}
				?>
				<div class="wishlist-shipping-address">
					<div class="woocommerce-address-fields">
						<div class="woocommerce-address-fields__field-wrapper">
							<?php
							echo $form->get_fields_html( 'shipping' );
							echo $form->get_hidden_fields();
							echo $form->get_submit_button( 'shipping' );
							// phpcs:enable
							?>
						</div>
					</div>
				</div>
			</form>
			<?php
			$content = ob_get_clean();
			return $this->get_new_template( $content );
		}
	}

	protected function get_section_content() {
		$wishlist_id = $this->wishlist ? $this->wishlist->get_id() : false;

		if ( !is_admin() && ('profile' !== $this->section_key) && !$wishlist_id ) {
			return;
		}

		$data = $this->get_section_data();

		if ( empty( $data ) ) {
			return;
		}

		$show_for_user_only = !empty( $data[ 'show_for_user_only' ] );

		if ( $show_for_user_only && (!is_nmgr_user( $this->get_type() ) ||
			($wishlist_id && !is_admin() && !nmgr_user_has_wishlist( $wishlist_id ))) ) {
			return;
		}

		$content = $data[ 'content' ] ?? '';

		if ( is_callable( $content ) ) {
			if ( has_filter( 'nmgr_account_sections' ) ||
				/**
				 * Hack to accommodate crowdfunding plugin sections
				 * @since version 4.11
				 * @todo Remove in later version, maybe 5.0.0 or earlier
				 */
				(function_exists( 'nmgrcf' ) && version_compare( nmgrcf()->version, '4.6', '<=' )) ) {
				$content = call_user_func( $content, $this->get_default_section_vars(), $this );
			} else {
				$this->set_section_attributes();
				$content = call_user_func( $content, $this );
			}
		}

		return $content;
	}

	/**
	 * Get an account section
	 * @deprecated since version 4.2.0
	 * @param string $section_key The section key, e.g. profile
	 * @return string Section template content
	 */
	public function get_section( $section_key ) {
		_deprecated_function( __METHOD__, '4.2.0', 'NMGR_Account::get_section_template' );
		$this->section_key = $section_key;
		return $this->get_section_content();
	}

	public function get_section_template() {
		return $this->get_section_content();
	}

	public function get_sections_by_ids( $section_keys ) {
		$keys = ( array ) $section_keys;
		$sections = [];
		foreach ( $keys as $section_key ) {
			$this->set_section( $section_key );
			$sections[ '#nmgr-' . $section_key ] = $this->get_section_template();
		}
		return $sections;
	}

	public function get_sections_to_replace() {
		$section_keys = $this->get_other_sections_to_replace();
		$section_keys[] = $this->section_key;
		return $this->get_sections_by_ids( $section_keys );
	}

	public function get_other_sections_to_replace() {
		return $this->get_section_data()[ 'replace_on_load' ] ?? [];
	}

	public function get_section_container_attributes() {
		_deprecated_function( __METHOD__, '4.11' );
		$this->set_section_attributes();
		return apply_filters_deprecated( 'nmgr_account_section_attributes',
			[ $this->section_attributes, $this->section_key ], '4.10' );
	}

	protected function set_section_attributes() {
		if ( empty( $GLOBALS[ 'nmgr' ] ) ) {
			$GLOBALS[ 'nmgr' ] = new \stdClass();
			$GLOBALS[ 'nmgr' ]->is_wishlist_page = is_nmgr_wishlist_page();
			$GLOBALS[ 'nmgr' ]->is_wishlist_page_single = is_nmgr_wishlist();
		}

		$nmgr_global = $GLOBALS[ 'nmgr' ];

		$section_data = $this->get_section_data();

		$id = $section_data[ 'id' ] ?? ('nmgr-' . $this->section_key);

		$attributes = array(
			'id' => $id,
			'class' => [
				'woocommerce',
				'nmgr-account-template',
				'nmgr-ajax',
			],
			'data-section' => $this->section_key,
			'data-nonce' => wp_create_nonce( 'nmgr_manage_wishlist' ),
			/**
			 * @deprecated 'data-wishlist-id'
			 * @todo Remove in version 5.0.0
			 */
			'data-wishlist-id' => !empty( $this->wishlist ) ? absint( $this->wishlist->get_id() ) : 0,
			'data-wishlist_id' => !empty( $this->wishlist ) ? absint( $this->wishlist->get_id() ) : 0,
			'data-nmgr_global' => isset( $nmgr_global ) ? htmlspecialchars( json_encode( $nmgr_global ) ) : '',
		);

		$this->section_attributes = $attributes;
	}

	protected function is_overridden() {
		return nmgr_overridden( "account/{$this->section_key}.php" );
	}

	public function get_new_template( $content ) {
		$wishlist = $this->wishlist;
		$section = $this->section_key;

		ob_start();

		if ( !$content ) {
			echo nmgr_default_content( $section );
		} else {
			$is_archived = is_callable( [ $wishlist, 'is_archived' ] ) ? $wishlist->is_archived() : false;
			$action_args = !empty( $this->action_args ) ? $this->action_args : [ $wishlist ];

			echo '<div ' . nmgr_format_attributes( $this->section_attributes ) . '>';
			echo '<fieldset ' . ($is_archived ? 'disabled' : '') . '>';
			$wishlist ? do_action_deprecated( "nmgr_before_$section", $action_args, '4.11', "nmgr_before_account_$section" ) : null;
			$wishlist ? do_action( "nmgr_before_account_$section", $this ) : null;
			echo $content;
			$wishlist ? do_action_deprecated( "nmgr_after_$section", $action_args, '4.11', "nmgr_after_account_$section" ) : null;
			$wishlist ? do_action( "nmgr_after_account_$section", $this ) : null;
			echo '</fieldset>';
			echo '</div>';
		}

		return ob_get_clean();
	}

	public static function get_all_wishlists( $type ) {
		return [ nmgr_get_wishlist( nmgr_get_user_default_wishlist_id( '', $type ) ) ];
	}

	public static function show_all_wishlists_template( $type ) {
		$wishlists = static::get_all_wishlists( $type );

		do_action( 'nmgr_before_wishlists', $wishlists );
		do_action_deprecated( 'nmgr_before_account_wishlists', [ $wishlists ], '4.6.0', 'nmgr_before_wishlists' );
		?>

		<div class="nmgr-account-wishlists-header">
			<div class="count">
				<?php
				$no_of_wishlists = is_object( $wishlists ) ? 1 : count( $wishlists );
				/* translators: 1: wishlists count, 2: wishlists type title */
				printf( nmgr()->is_pro ? _n( '%1$s  %2$s', '%1$s %2$s', $no_of_wishlists, 'nm-gift-registry' ) : _n( '%1$s  %2$s', '%1$s %2$s', $no_of_wishlists, 'nm-gift-registry-lite' ),
					'<strong>' . number_format_i18n( $no_of_wishlists ) . '</strong>',
					1 === $no_of_wishlists ?
						esc_html( nmgr_get_type_title( '', '', $type ) ) :
						esc_html( nmgr_get_type_title( '', true, $type ) )
				);
				?>
			</div>
		</div>
		<?php
		echo (new WishlistsTable( $wishlists ) )->get_table();
		do_action( 'nmgr_after_wishlists', $wishlists );
		do_action_deprecated( 'nmgr_after_account_wishlists', [ $wishlists ], '4.6.0', 'nmgr_after_wishlists' );
	}

	protected static function single_wishlist_only( $type ) {
		return nmgr_get_user_wishlists_count( '', $type );
	}

	public static function show_new_wishlist_template( $type ) {
		if ( static::single_wishlist_only( $type ) ) {
			$text = sprintf(
				/* translators: %s: wishlist type title */
				nmgr()->is_pro ? __( 'You can only have one %s.', 'nm-gift-registry' ) : __( 'You can only have one %s.', 'nm-gift-registry-lite' ),
				nmgr_get_type_title( '', 0, $type )
			);
			if ( function_exists( 'wc_print_notice' ) ) {
				wc_print_notice( $text, 'notice' );
			}
		} else {
			$account = nmgr()->account();
			$account->set_type( $type );
			echo $account->set_section( 'profile' )->get_section_template();
		}
	}

}
