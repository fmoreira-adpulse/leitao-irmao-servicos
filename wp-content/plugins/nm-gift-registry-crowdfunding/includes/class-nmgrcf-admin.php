<?php
/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

/**
 * WordPress Admin actions related to plugin
 */
class NMGRCF_Admin {

	public static function run() {
		add_action( 'admin_head', array( __CLASS__, 'admin_css' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 30 );
		add_action( 'nmgr_post_submitbox_actions', array( __CLASS__, 'show_enable_free_contributions_field' ), 20 );
	}

	public static function admin_css() {
		if ( !is_nmgr_admin() ) {
			return;
		}
		?>
		<style>
			#nm_gift_registry-crowdfunds.postbox .inside,
			#nm_gift_registry-free-contributions.postbox .inside {
				margin:0;
				padding:0;
			}
		</style>
		<?php
	}

	public static function add_meta_boxes() {
		global $post;

		if ( !has_term( 'gift-registry', 'nm_gift_registry_type', $post ) ) {
			return;
		}

		$wishlist = !empty( $post->ID ) ? nmgr_get_wishlist( $post ) : false;

		if ( is_nmgrcf_crowdfunding_enabled() ) {
			add_meta_box( 'nm_gift_registry-crowdfunds',
				__( 'Crowdfunds', 'nm-gift-registry-crowdfunding' ),
				array( NMGRCF_Admin::class, 'crowdfunds_metabox' ), 'nm_gift_registry', 'normal' );
		}

		if ( $wishlist && $wishlist->is_free_contributions_enabled() ) {
			add_meta_box( 'nm_gift_registry-free-contributions',
				__( 'Free Contributions', 'nm-gift-registry-crowdfunding' ),
				array( NMGRCF_Admin::class, 'free_contributions_metabox' ), 'nm_gift_registry', 'normal' );
		}

		add_meta_box( 'nm_gift_registry-wallet',
			__( 'Wallet', 'nm-gift-registry-crowdfunding' ),
			array( NMGRCF_Admin::class, 'wallet_metabox' ), 'nm_gift_registry', 'side' );
	}

	public static function crowdfunds_metabox( $post ) {
		echo nmgr_get_account_section( 'crowdfunds', $post->ID );
	}

	public static function free_contributions_metabox( $post ) {
		echo nmgr_get_account_section( 'free_contributions', $post->ID );
	}

	public static function wallet_metabox( $post ) {
		$wishlist = nmgr_get_wishlist( $post );
		?>
		<div class="nmgr-text-center nmgrcf-amount-in-wallet-display">
			<?php echo wp_kses_post( wc_price( $wishlist->get_wallet_balance() ) ); ?>
		</div>
		<div class="nmgrcf-wallet-actions nmgr-text-center">
			<p><?php echo wp_kses_post( nmgrcf_get_view_wallet_log_button( $wishlist->get_id() ) ); ?></p>
		</div>
		<?php
	}

	public static function show_enable_free_contributions_field( $wishlist ) {
		if ( !is_nmgrcf_free_contributions_enabled() ) {
			return;
		}

		$checkbox_args = array(
			'input_id' => 'nmgr_enable_free_contributions',
			'input_name' => 'nmgr_enable_free_contributions',
			'label_text' => __( 'Enable free contributions', 'nm-gift-registry-crowdfunding' ) . nmgr_get_help_tip(
				__( 'Allow contributors to send money to your wallet directly without attaching it to a product.', 'nm-gift-registry-crowdfunding' ) ),
			'checked' => $wishlist->is_free_contributions_enabled(),
			'show_hidden_input' => true,
		);
		?>

		<div class="misc-pub-section" style="display:flex;align-items:center;">
			<div><?php echo nmgr_get_checkbox_switch( $checkbox_args ); ?></div>
			<div style="margin:0 8px 0 10px;"><?php echo nmgrcf_get_free_contributions_settings_button( $wishlist->get_id() ); ?></div>
		</div>
		<?php
	}

}
