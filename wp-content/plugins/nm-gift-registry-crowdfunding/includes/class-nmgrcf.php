<?php

defined( 'ABSPATH' ) || exit;

use NMGRCF\Lib\Upgrader,
		NMGRCF\Lib\PluginProps,
		NMGRCF\Events\UpdateOrderItemMeta,
		NMGRCF\Events\UpdateCrowdfundReceivedWalletAmountColumns;

class NMGRCF extends PluginProps {

	private static $instance;
	public $requires_nmgr_pro = '4.13';

	public function __construct( $filepath = null ) {
		if ( method_exists( $this, 'set_plugin_props' ) ) {
			$this->set_plugin_props( $filepath );
		}
	}

	public static function get_instance( $filepath = null ) {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static( $filepath );
		}
		return static::$instance;
	}

	public function init() {
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
		add_action( 'init', array( $this, 'maybe_install_and_run' ), 0 );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'plugin_action_links', array( $this, 'show_disabled_notices' ), 10, 2 );
		add_filter( 'nmgr_packages', array( $this, 'add_crowdfund_plugin_to_nmgr_packages' ) );

		if ( is_admin() ) {
			register_uninstall_hook( $this->file, array( __CLASS__, 'uninstall' ) );
		}
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->slug, false, plugin_basename( dirname( $this->file ) ) . '/languages' );
	}

	public function plugin_row_meta( $links, $file ) {
		if ( $file == $this->basename ) {
			$defaults = [
				'docs_url' => __( 'Docs', 'nm-gift-registry-crowdfunding' ),
				'support_url' => __( 'Support', 'nm-gift-registry-crowdfunding' ),
			];

			foreach ( $defaults as $url => $text ) {
				if ( !empty( $this->{$url} ) ) {
					$links[] = '<a target="_blank" href="' . $this->{$url} . '">' . $text . '</a>';
				}
			}
		}
		return $links;
	}

	public function install_actions() {
		Upgrader::run();

		/**
		 * Crowdfund settings have been added to NM Gift Registry default settings
		 * so we merge these into the already stored plugin setting in the database
		 */
		$existing_settings = get_option( 'nmgr_settings' );
		if ( !array_key_exists( 'enable_crowdfunding', $existing_settings ) ) {
			$defaults = nmgr()->gift_registry_settings()->get_default_field_values();
			update_option( 'nmgr_settings', array_merge( $defaults, $existing_settings ) );
		}

		$this->create_table_columns();

		update_option( 'nmgrcf_version', $this->version );
		do_action( 'nmgrcf_installed' ); // Occurs on init hook (after activation or during version update)
	}

	public function create_table_columns() {
		global $wpdb;

		$items_table = "{$wpdb->prefix}nmgr_wishlist_items";
		$columns = $wpdb->get_col( "SHOW columns from $items_table" );

		$key = 'crowdfunded';
		if ( !in_array( $key, $columns ) ) {
			$wpdb->query( "ALTER TABLE $items_table ADD `$key` TINYINT(1) NULL DEFAULT 0 AFTER `purchase_log`" );
		}

		$key = 'crowdfund_data';
		if ( !in_array( $key, $columns ) ) {
			$wpdb->query( "ALTER TABLE $items_table ADD `$key` LONGTEXT NULL AFTER `crowdfunded`" );
		}

		$key = 'crowdfund_received';
		if ( !in_array( $key, $columns ) ) {
			$wpdb->query( "ALTER TABLE $items_table ADD `$key` DOUBLE NULL AFTER `crowdfund_data`" );
		}

		$key = 'wallet_amount';
		if ( !in_array( $key, $columns ) ) {
			$wpdb->query( "ALTER TABLE $items_table ADD `$key` DOUBLE NULL AFTER `crowdfund_received`" );
		}
	}

	public function maybe_install_and_run() {
		$this->maybe_disable();

		if ( $this->is_disabled() ) {
			return;
		}

		require_once $this->path . 'includes/functions.php';

		// Install plugin
		add_action( 'nmgr_installed', array( $this, 'install_actions' ) );
		if ( version_compare( get_option( 'nmgrcf_version' ), $this->version, '<' ) ) {
			// priority 80 ensures that nmgr install events have already occured
			add_action( 'init', array( $this, 'install_actions' ), 80 );
		}

		/**
		 * These actions have to be present when the plugin is activated even if crowdfunding is disabled as they show
		 * the settings for enabling it, and other features. So we put them before the check to see if crowdfunding is enabled
		 */
		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'plugin_action_links' ) );

		NMGRCF_Settings::run();
		(new UpdateOrderItemMeta )->init();
		(new UpdateCrowdfundReceivedWalletAmountColumns )->init();

		if ( !is_nmgrcf_enabled() ) {
			return;
		}

		add_action( 'init', array( $this, 'register_post_status' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'include_sprite_file' ) );
		add_action( 'admin_footer', array( $this, 'include_sprite_file' ) );
		add_action( 'wp', array( $this, 'add_shortcodes' ) );
		add_filter( 'nmgr_wishlist_class', array( $this, 'set_wishlist_class' ) );
		add_filter( 'nmgr_wishlist_item_class', array( $this, 'set_wishlist_item_class' ) );

		$classes = array(
			NMGRCF_Admin::class,
			NMGRCF_Coupon::class,
			NMGRCF_Item_Table::class,
			NMGRCF_Wallet::class,
			NMGRCF_Templates::class,
		);

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) && method_exists( $class, 'run' ) ) {
				$class::run();
			}
		}

		$module_classes = array(
			NMGRCF_Cart::class,
			NMGRCF_Order::class,
		);

		$modules = array( 'Crowdfund', 'Free_Contribution' );

		foreach ( $module_classes as $class ) {
			foreach ( $modules as $module ) {
				$the_class = $class . '_' . $module;
				if ( class_exists( $the_class ) && method_exists( $the_class, 'run' ) ) {
					$the_class::run();
				}
			}
		}

		do_action( 'nmgrcf_plugin_loaded' );
	}

	public function enqueue_scripts() {
		if ( (!is_admin() && (is_woocommerce() || is_nmgr_wishlist_page())) || is_nmgr_admin() ) {
			$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? date( 'H:i:s' ) : $this->version;
			$style_deps = is_nmgr_admin() ? array( 'nmgr-admin' ) : array( 'nmgr-frontend' );
			$script_deps = is_nmgr_admin() ? array( 'nmgr-admin' ) : array( 'nmgr-frontend' );

			wp_enqueue_style( 'nmgrcf',
				$this->url . 'assets/css/style.min.css',
				$style_deps,
				$version
			);

			wp_enqueue_script( 'nmgrcf',
				$this->url . 'assets/js/script.min.js',
				$script_deps,
				$version,
				true
			);
		}
	}

	public function include_sprite_file() {
		if ( is_nmgr_admin() || is_nmgr_wishlist_page() ) {
			$sprite_file = $this->path . 'assets/svg/sprite.svg';
			if ( file_exists( $sprite_file ) ) {
				include_once $sprite_file;
			}
		}
	}

	public function register_post_status() {
		register_post_status( 'nmgr-crowdfunded', array(
			'label' => _x( 'NM Gift Registry crowdfunded', 'Product post status', 'nm-gift-registry-crowdfunding' ),
			'public' => false,
			'internal' => true,
			/* translators: %s: number of items */
			'label_count' => _n_noop( 'NM Gift Registry crowdfunded <span class="count">(%s)</span>', 'NM Gift Registry crowdfunded <span class="count">(%s)</span>', 'nm-gift-registry-crowdfunding' ),
		) );
	}

	public static function uninstall() {
		global $wpdb;

		if ( !apply_filters( 'nmgrcf_delete_data_on_uninstall', false ) ) {
			return;
		}

		$nmgr_settings = get_option( 'nmgr_settings' );

		if ( !empty( $nmgr_settings ) ) {
			$options = array(
				'enable_crowdfunding'
			);
			foreach ( $options as $option ) {
				if ( isset( $nmgr_settings[ $option ] ) ) {
					unset( $nmgr_settings[ $option ] );
				}
			}
			update_option( 'nmgr_settings', $nmgr_settings );

			/**
			 * Before we delete from nm gift registry tables we have to make sure they exists.
			 * A quick way to do this is to check if the plugin settings exists in the database.
			 * If it does, then the plugin tables also exist so we can delete from them.
			 * This prevents showing an error if the tables don't exist like when the full version
			 * of the plugin is deleted at the same time with the crowdfunding extension.
			 */
			$columns = [
				'crowdfunded',
				'crowdfund_data',
				'crowdfund_received',
				'credits_to_wallet',
				'debits_from_wallet',
			];

			foreach ( $columns as $col ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}nmgr_wishlist_items DROP COLUMN $col" );
			}
		}

		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'nmgr-crowdfunded';" );
		$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'nmgrcf\_%';" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'nmgrcf\_%';" );
	}

	public function plugin_action_links( $links ) {
		$url = add_query_arg( array(
			'post_type' => 'nm_gift_registry',
			'page' => 'nmgr-settings',
			'tab' => 'modules',
			'section' => 'crowdfunding',
			), admin_url( 'edit.php' ) );

		return array_merge( $links, array(
			'<a href="' . $url . '">' . __( 'Settings', 'nm-gift-registry-crowdfunding' ) . '</a>'
			) );
	}

	public function add_crowdfund_plugin_to_nmgr_packages( $packages ) {
		$packages[] = $this->basename;
		return $packages;
	}

	public function add_shortcodes() {
		$shortcodes = array(
			'nmgrcf_get_crowdfunds_template' => 'nmgrcf_crowdfunds',
			'nmgrcf_get_free_contributions_template' => 'nmgrcf_free_contributions',
		);

		foreach ( $shortcodes as $function => $shortcode ) {
			if ( function_exists( $function ) ) {
				add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
			}
		}
	}

	public function maybe_disable() {
		if ( !function_exists( 'nmgr_get_option' ) ) {
			$this->notices[ 'disable' ][] = __( 'This plugin requires the NM Gift Registry and Wishlist plugin to be activated for it to run.', 'nm-gift-registry-crowdfunding' );
		}

		if ( function_exists( 'nmgr' ) && !nmgr()->is_pro ) {
			$this->notices[ 'disable' ][] = sprintf(
				/* translators : %s wishlist type title */
				__( 'This version of the plugin only works with the PRO version of %s', 'nm-gift-registry-crowdfunding' ),
				nmgr()->name
			);
		}

		if ( function_exists( 'nmgr' ) && version_compare( nmgr()->version, $this->requires_nmgr_pro, '<' ) ) {
			$this->notices[ 'disable' ][] = sprintf(
				/* translators:
				 * 1: plugin name,
				 * 2: NM Gift Registry plugin name,
				 * 3: required NM Gift Registry version,
				 * 4: current NM Gift Registry version
				 */
				__( '%1$s needs %2$s %3$s or higher to work. You have version %4$s. Please update it.', 'nm-gift-registry-crowdfunding' ),
				$this->name,
				nmgr()->name,
				$this->requires_nmgr_pro,
				nmgr()->version
			);
		}

		$disable = !empty( $this->notices[ 'disable' ] );

		if ( $disable ) {
			$this->is_disabled = true;
		}

		return $disable;
	}

	public function show_disabled_notices( $actions, $plugin_file ) {
		if ( $this->basename === $plugin_file && !empty( $this->notices[ 'disable' ] ) ) {
			$notices = htmlspecialchars( json_encode( implode( "\n\n", $this->notices[ 'disable' ] ) ) );

			$actions[ 'disabled' ] = '<strong style="color:red;display:inline;cursor:pointer;" onclick="alert(' . $notices . ');">' .
				__( 'Disabled', 'nm-gift-registry-crowdfunding' ) .
				' &#9432;</strong>';
		}
		return $actions;
	}

	public function set_wishlist_class( $class ) {
		return is_nmgrcf_enabled() ? NMGRCF_Wishlist::class : $class;
	}

	public function set_wishlist_item_class( $class ) {
		return is_nmgrcf_enabled() ? NMGRCF_Item::class : $class;
	}

}
