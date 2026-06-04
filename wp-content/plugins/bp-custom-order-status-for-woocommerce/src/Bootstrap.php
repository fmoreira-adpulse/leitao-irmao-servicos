<?php

namespace Brightplugins_COS;

class Bootstrap {
	/**
	 * @var string
	 */
	public $woddp_title;
	/**
	 * @var string
	 */
	public $woddp_plugin_url;
	/**
	 * @var boolen
	 */
	public $woddp_activate;
	public function __construct() {
		$this->woddpDefination();
		//new Cpt();
		new StatusColums();
		new Status();
		new Email();
		new Checkout();
		//new Settings();

		add_action( 'admin_notices', [$this, 'review'] );
		add_action( 'admin_init', [$this, 'url_param_check'] );
		add_filter( 'cosm_upsale_notice', [$this, 'cosm_upsale_notice_render'] );

		add_action( 'upgrader_process_complete', [$this, 'new_version_updated'], 10, 2 );
		/**
		 * It tries to join the pro version
		 */
		$this->try_to_join_pro_version();
	}


	/**
	 * New version updated
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public function new_version_updated( $upgrader_object, $options ) {

		try {
			if ( version_compare( '2.0', BVOS_PLUGIN_VER, '<=' ) ) {

				if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
					foreach ($options['plugins'] as $plugin) {
						if ($plugin == BVOS_PLUGIN_BASE) {
							set_transient('cosmbp_new_version_installed_show_notice', true, 600);
						}
					}
				}

				add_action('admin_notices', function() {
					
					if (get_transient('cosmbp_new_version_installed_show_notice')) {
						
						$setting_page_url = admin_url('admin.php?page=wcbv-order-status-setting');
						
						?>
						<div class="notice notice-success is-dismissible">
							<p>
								<strong>Custom Order Status Manager for WooCommerce just got better!.</strong> We’ve added new features and improvements to enhance your workflow (PRO version).
								<a href="<?php echo esc_url($setting_page_url); ?>">Go to the settings page</a> to see what’s new.
							</p>
						</div>
						<?php
						
						delete_transient('cosmbp_new_version_installed_show_notice');
					}
				});
			}
		} catch (\Throwable $th) {
			//throw $th;
		}
	}

	/**
	 * Is the pro version activated ?
	 * 
	 * @since 1.2
	 * 
	 * @return bool
	 */
	public static function is_pro_version_activated() {
		return class_exists( '\Brightplugins_COS_PRO\Premium\PRO_Plugin' );
	}

	/**
	 * It tries to join the pro version module into this one
	 * 
	 * @since 1.2
	 * 
	 * @return void
	 */
	public function try_to_join_pro_version() {
		/**
		 * It fires when the free version has been loaded
		 */
		do_action( 'cosmbp_free_version_loaded' );

		/**
		 * If the pro version is not install
		 * Then it initializes the classes that may be to replaced/extended by the PRO version
		 */
		if ( !self::is_pro_version_activated() ) {
			new Cpt();
			new Settings();
		}
	}
	/**
	 * Get data of Custom Order status plugin
	 *
	 * @return void
	 */
	public function woddpDefination() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( is_plugin_active( 'bp-order-date-time-for-woocommerce/main.php' ) ) {

			$this->woddp_title      = __( 'Check Options', 'bv-order-status' );
			$this->woddp_activate   = true;
			$this->woddp_plugin_url = admin_url( 'admin.php?page=wcbp-woodevelivery-setting' );

		} elseif ( file_exists( WP_PLUGIN_DIR . '/bp-order-date-time-for-woocommerce/main.php' ) ) {

			$this->woddp_title      = __( 'Activate Now', 'bv-order-status' );
			$this->woddp_activate   = false;
			$this->woddp_plugin_url = wp_nonce_url( 'plugins.php?action=activate&plugin=bp-order-date-time-for-woocommerce/main.php&plugin_status=all&paged=1', 'activate-plugin_bp-order-date-time-for-woocommerce/main.php' );

		} else {

			$this->woddp_title      = __( 'Install Now', 'bv-order-status' );
			$this->woddp_activate   = false;
			$this->woddp_plugin_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=bp-order-date-time-for-woocommerce' ), 'install-plugin_bp-order-date-time-for-woocommerce' );

		}
	}
	
	/**
	 * @return null
	 */
	public function cosm_upsale_notice_render( $data ) {

		$data = "<b>Would you like to automate your workflow?</b><br><br>
				Imagine having your custom statuses transition to new stages automatically based on specific <b>actions</b>, <b>events</b>, <b>dates</b>, or <b>custom parameters—all</b> while triggering automated emails to the right people.
				We will be building new features over the next few days, and you can decide what’s next. The most voted features will be built!<br><br>
				Vote here or comment below with the feature you’d love to see in the Premium version:<br><br>
				<a href='https://app.loopedin.io/custom-order-status-manager#/ideas-board' target='_blank' class='button button-primary'>Vote for new features</a><br>";
		return $data;

	}
	/**
	 * simple dismissable logic
	 *
	 * @return void
	 */
	public function url_param_check() {
		if ( isset( $_GET['bpcosm-review-dismiss'] ) && 1 == $_GET['bpcosm-review-dismiss'] ) {
			update_option( 'dfwc_plugin_review', 1 );
		}
		if ( isset( $_GET['bpcosm-review-dismiss-temp'] ) && 1 == $_GET['bpcosm-review-dismiss-temp'] ) {
			set_transient( 'bpcosm_review_later', 1, 2 * WEEK_IN_SECONDS );
		}
	}
	
	/**
	 * Displays a review notice for the Custom Order Status Manager for WooCommerce plugin after 1 week of usage.
	 * If the user has dismissed the notice or has chosen to review later, the notice won't be displayed.
	 * If the user hasn't dismissed the notice and it has been more than 7 days since the plugin was installed, the notice will be displayed.
	 * The notice includes a request for a five-star review on WordPress.org and options to dismiss or review later.
	 *
	 * @return void
	 */
	public function review() {
		$dismiss_parm = array( 'bpcosm-review-dismiss' => '1' );
		$temp_dismiss = array( 'bpcosm-review-dismiss-temp' => '1' );

		$datetime1     = new \DateTime( date( 'Y-m-d h:i:s', get_option( 'bp_custom_order_status_installed' ) ) );
		$datetime2     = new \DateTime( date( 'Y-m-d h:i:s' ) );
		$diff_interval = $this->get_days( $datetime1, $datetime2 );

		if ( get_option( 'dfwc_plugin_review' ) || get_transient( 'bpcosm_review_later' ) ) {
			return;
		} elseif ( $diff_interval > 7 ) {

			?>
        <div class="notice notice-info bpcosm-review-notice">
			<h3><img draggable="false" class="emoji" alt="🎉" src="https://s.w.org/images/core/emoji/11/svg/1f389.svg">  Congrats!</h3>
        <p>You're using <strong>Custom Order Status Manager for WooCommerce</strong> plugin more than 1 week - that’s awesome! If you can spare a minute, please help us by leaving a five star review on WordPress.org.</p>
        <p><strong>~ Bright Plugins</strong></p>
        <p class="dfwc-message-actions">
            <a style="margin-right:8px;" href="https://wordpress.org/support/plugin/bp-custom-order-status-for-woocommerce/reviews/?filter=5#new-post" target="_blank" class="button button-primary">Okay, You Deserve It</a>
            <a style="margin-right:8px;" href="<?php echo esc_url( add_query_arg( $temp_dismiss ) ); ?>"  class="button">Nope, Maybe Later</a>
            <a href="<?php echo wp_nonce_url( add_query_arg( $dismiss_parm ) ); ?>" class=" button">Hide Notification</a>
        </p>
        </div>
        <?php }
	}
	/**
	 * @param $from_date
	 * @param $to_date
	 */
	public function get_days( $from_date, $to_date ) {
		return round(  ( $to_date->format( 'U' ) - $from_date->format( 'U' ) ) / ( 60 * 60 * 24 ) );
	}
	/**
	 * Check if WooCommerce is installed
	 *
	 * @since 1.2.7
	 * @access public
	 *
	 * @return bool
	 */
	public static function is_woocommerce_installed() {

		/**
		 * Checks if it is a multisite
		 */
		if ( is_multisite() ) {
			add_filter( 'active_plugins', function ( $active_plugins ) {

				$network = get_network();

				if ( !isset( $network->id ) ) {
					return $active_plugins;
				}
				$active_sitewide_plugins = get_network_option( $network->id, 'active_sitewide_plugins', null );

				if ( !empty( $active_sitewide_plugins ) ) {
					$network_active_plugins = array();

					foreach ( $active_sitewide_plugins as $key => $value ) {
						$network_active_plugins[] = $key;
					}

					$active_plugins = array_merge( $active_plugins, $network_active_plugins );
				}

				return $active_plugins;
			} );
		}

		$filter_active_plugins    = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		$is_woocommerce_installed = in_array( 'woocommerce/woocommerce.php', $filter_active_plugins, true );

		return $is_woocommerce_installed;
	}

}
