<?php

use NMGRCF\Tables\CrowdfundsTable;
use NMGRCF\Tables\FreeContributionsTable;

defined( 'ABSPATH' ) || exit;

class NMGRCF_Templates {

	public static function run() {
		add_action( 'nmgr_wishlist', [ __CLASS__, 'show_add_to_cart_form' ], 65 );
		add_filter( 'nmgr_fields_account', array( __CLASS__, 'add_crowdfunds_section' ), 10, 2 );
		add_filter( 'nmgr_fields_account', array( __CLASS__, 'add_free_contributions_section' ), 10, 2 );
		add_action( 'nmgr_post_action', [ __CLASS__, 'post_action' ] );
		add_action( 'nmgr_fields', [ __CLASS__, 'add_enable_free_contributions_field_to_nmgr_fields' ], 10, 2 );
	}

	public static function show_add_to_cart_form( $wishlist ) {
		if ( $wishlist && $wishlist->is_free_contributions_enabled() && $wishlist->is_type( 'gift-registry' ) ) {
			$vars = array(
				'wishlist' => $wishlist,
				'settings' => $wishlist->get_free_contributions_settings()
			);

			echo nmgrcf_get_template( 'free-contributions-add-to-cart-form.php', $vars );
		}
	}

	public static function add_crowdfunds_section( $sections, $account ) {
		if ( is_nmgrcf_crowdfunding_enabled() && $account->is_gift_registry() ) {
			$sections[ 'crowdfunds' ] = array(
				'title' => __( 'Crowdfunds', 'nm-gift-registry-crowdfunding' ),
				'priority' => 56, // after orders tab
				'show_for_user_only' => true,
				'content' => [ __CLASS__, 'crowdfunds_section' ],
			);
		}

		return $sections;
	}

	public static function crowdfunds_section( $account ) {
		$wishlist = $account->get_wishlist();
		$attributes = $account->section_attributes;
		$table = (new CrowdfundsTable( $wishlist ))->setup()->get_template();

		ob_start();
		?>
		<div <?php echo nmgr_format_attributes( $attributes ); ?>>
			<?php
			$wishlist ? do_action( 'nmgrcf_before_crowdfunds', $wishlist ) : '';
			echo $table;
			$wishlist ? do_action( 'nmgrcf_after_crowdfunds', $wishlist ) : '';
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function add_free_contributions_section( $sections, $account ) {
		$wishlist = $account->get_wishlist();

		if ( $account->is_gift_registry() && $wishlist && $wishlist->is_free_contributions_enabled() ) {
			$sections[ 'free_contributions' ] = array(
				'title' => __( 'Free contributions', 'nm-gift-registry-crowdfunding' ),
				'priority' => 57, // after crowdfunds tab
				'content' => [ __CLASS__, 'free_contributions_section' ],
				'show_for_user_only' => true,
			);
		}
		return $sections;
	}

	public static function free_contributions_section( $account ) {
		$wishlist = $account->get_wishlist();
		$attributes = $account->section_attributes;
		$table = (new FreeContributionsTable( $wishlist ))->setup()->get_template();

		ob_start();
		?>
		<div <?php echo nmgr_format_attributes( $attributes ); ?>>
			<?php
			$wishlist ? do_action( 'nmgrcf_before_free_contributions', $wishlist ) : '';
			echo $table;
			$wishlist ? do_action( 'nmgrcf_after_free_contributions', $wishlist ) : '';
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function post_action( $args ) {
		switch ( $args[ 'post_action' ] ?? null ) {
			case 'show_free_contributions_settings_dialog':
				self::show_free_contributions_settings_dialog( $args );
				break;
			case 'save_free_contributions_settings':
				self::save_free_contributions_settings( $args );
				break;
		}
	}

	public static function show_free_contributions_settings_dialog( $args ) {
		$wishlist_id = $args[ 'wishlist_id' ] ?? false;
		nmgr()->ajax()->check_wishlist_permission( $wishlist_id );

		$response = array();
		$template = nmgrcf_get_free_contributions_settings_dialog_template( $wishlist_id );

		if ( $template ) {
			$response[ 'show_template' ] = $template;
		} else {
			$response[ 'toast_notice' ] = nmgr_get_error_toast_notice();
		}

		wp_send_json( $response );
	}

	public static function save_free_contributions_settings( $args ) {
		$wishlist_id = $args[ 'wishlist_id' ] ?? false;
		nmgr()->ajax()->check_wishlist_permission( $wishlist_id );
		$wishlist = nmgr_get_wishlist( $wishlist_id, true );

		if ( $wishlist ) {
			$db_settings = $wishlist->get_free_contributions_settings();

			foreach ( array_keys( $db_settings ) as $key ) {
				if ( isset( $_POST[ $key ] ) ) {
					$db_settings[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				}
			}

			update_post_meta( $wishlist_id, 'free_contributions_settings', $db_settings );

			$acc = nmgr()->account( $wishlist );
			wp_send_json( array(
				'toast_notice' => nmgr_get_success_toast_notice(),
				'replace_templates' => $acc->get_sections_by_ids( 'free_contributions' ),
				'close_dialog' => true
			) );
		}
	}

	public static function add_enable_free_contributions_field_to_nmgr_fields( $fields, $args ) {
		$wishlist = nmgr_get_wishlist( $args[ 'wishlist' ] );

		if ( 'gift-registry' === $args[ 'form' ]->get_type() ) {
			$fields[ 'enable_free_contributions' ] = array(
				'type' => 'nmgr-checkbox',
				'label' => __( 'Enable free contributions', 'nm-gift-registry-crowdfunding' ) . nmgr_get_help_tip( __( 'Allow contributors to send money to your wallet directly without attaching it to a product.', 'nm-gift-registry-crowdfunding' ) ) . nmgrcf_get_free_contributions_settings_button( $wishlist ? $wishlist->get_id() : 0 ),
				'default' => '',
				'priority' => 115,
				'value' => $wishlist ? $wishlist->is_free_contributions_enabled() : false,
				'fieldset' => 'settings',
				'checkbox_args' => [
					'show_hidden_input' => true,
				],
			);
		}

		return $fields;
	}

}
