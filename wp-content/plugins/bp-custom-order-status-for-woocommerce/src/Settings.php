<?php
namespace Brightplugins_COS;

class Settings {

	const CLUB_MEMBERSHIP_LINK = 'https://brightplugins.com/product/club-membership/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_club_membership';
	const PRO_VERSION_LINK = 'https://brightplugins.com/product/custom-order-status-manager-for-woocommerce/?utm_source=freemium&utm_medium=plugin_page&utm_content=banner&utm_campaign=upgrade_pro';
	const DOCS = 'https://brightplugins.com/docs/automation-rules-automatic-status-changes/';

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'bp_admin_menu' ) );
		add_filter( "plugin_row_meta", [$this, 'pluginMetaLinks'], 20, 2 );
		//add_action( 'widgets_init', [$this, 'pluginOptions'], 9999999 );
		add_action( 'init', function(){
			if( !is_admin() ) {
				return;
			}

			$this->pluginOptions();
        }, 9 );
		add_action( 'admin_notices', [$this, 'generate_pro_banner'] );
		add_filter( "plugin_action_links_" . BVOS_PLUGIN_BASE, [$this, 'add_settings_link'] );
	}
	/**
	 * @param  $settings_tabs
	 * @return mixed
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['settings_tab_demo'] = __( 'Custom Status Settings', 'bp-custom-order-status' );
		return $settings_tabs;
	}

	/**
	 * 
	 */
	public function generate_pro_banner() {

		$is_cosm_page = is_admin() && isset($_GET['page']) && $_GET['page'] === 'wcbv-order-status-setting';

		if (Bootstrap::is_pro_version_activated() || !$is_cosm_page) {
			return;
		} 

		?>
		
			<div style="display: flex;justify-content: center;flex-wrap: wrap;align-items: center;flex-direction: row;gap: 20px;">

				<a target="_blank" href="<?php echo esc_attr( self::PRO_VERSION_LINK ); ?> ">
					<img style="max-height: 210px;" alt="order status custom manager banner" src="<?php echo esc_attr( COSMBP_ASSETS . '/img/order-status-control-pro-version-banner.png' ); ?>">
				</a>

				<div id="my-countdown">
					<div style="max-height: 180px;" class="special-offer-banner">
						<div class="offer-title">
							Exclusive Offer: <span class="offer-highlight">35% PERMANENT DISCOUNT!</span>
						</div>
						<div class="offer-description">
							Lock in this discount for as long as your PRO subscription stays active! It’s our way of saying thanks for sticking with us. Grab it now to keep these savings for life!
						</div>
						
						<div class="countdown-grid" id="timer-display">
							<div class="time-block">
								<span class="time-number" id="days">00</span>
								<span class="time-label">Days</span>
							</div>
							<div class="time-block">
								<span class="time-number" id="hours">00</span>
								<span class="time-label">Hours</span>
							</div>
							<div class="time-block">
								<span class="time-number" id="minutes">00</span>
								<span class="time-label">Min</span>
							</div>
							<div class="time-block">
								<span class="time-number" id="seconds">00</span>
								<span class="time-label">Sec</span>
							</div>
						</div>

						<a target="_blank" href="https://brightplugins.com/cart/?add-to-cart=9004111222029455&coupon-code=cosm35off" class="offer-button">
							Claim My Discount Now
						</a>
					</div>
				</div>
			</div>

			<style>
				/* --- CSS Styles --- */
				#my-countdown {
					--purple: #9037a1;
					--teal: #44a2ab;
					--gold: #ffab00;
					--white: #ffffff;
					font-family: 'Segoe UI', Roboto, Arial, sans-serif;
					margin: 20px 0;
				}

				.special-offer-banner {
					background: linear-gradient(135deg, var(--purple), var(--teal));
					color: var(--white);
					padding: 25px;
					border-radius: 12px;
					text-align: center;
					max-width: 850px;
					margin: 0 auto;
					box-shadow: 0 10px 30px rgba(0,0,0,0.15);
				}

				.offer-title {
					font-size: 24px;
					font-weight: 800;
					margin-bottom: 12px;
					text-transform: uppercase;
					letter-spacing: 0.5px;
				}

				.offer-highlight {
					color: var(--gold);
				}

				.offer-description {
					font-size: 15px;
					margin-bottom: 15px;
					opacity: 0.95;
					max-width: 600px;
					margin-left: auto;
					margin-right: auto;
					line-height: 1.4;
				}

				/* Updated Grid for 4 columns */
				.countdown-grid {
					display: grid;
					grid-template-columns: repeat(4, 1fr);
					gap: 10px;
					max-width: 450px;
					margin: 0 auto 20px auto;
				}

				.time-block {
					background: rgba(0, 0, 0, 0.25);
					padding: 8px 3px;
					border-radius: 8px;
					border: 1px solid rgba(255, 255, 255, 0.1);
				}

				.time-number {
					font-size: 24px;
					font-weight: bold;
					color: var(--gold);
					display: block;
					line-height: 1;
					margin-bottom: 5px;
				}

				.time-label {
					font-size: 11px;
					text-transform: uppercase;
					letter-spacing: 1px;
					opacity: 0.8;
					display: block;
				}

				.offer-button {
					background-color: var(--gold);
					color: var(--purple);
					font-weight: bold;
					padding: 16px 40px;
					border-radius: 50px;
					text-decoration: none;
					font-size: 18px;
					display: inline-block;
					transition: transform 0.2s, background-color 0.2s;
					box-shadow: 0 4px 15px rgba(0,0,0,0.2);
				}

				.offer-button:hover {
					background-color: #ffc400;
					transform: scale(1.05);
				}

				/* Responsiveness for small screens */
				@media (max-width: 500px) {
					.offer-title { font-size: 18px; }
					.countdown-grid { gap: 5px; }
					.time-number { font-size: 20px; }
					.time-label { font-size: 9px; }
					.special-offer-banner { padding: 20px 15px; }
				}
			</style>

			<script>
				(function() {
					// Target Date: March 31, 2026
					const targetDate = new Date("March 31, 2026 23:59:59").getTime();

					const updateTimer = setInterval(function() {
						const now = new Date().getTime();
						const timeLeft = targetDate - now;

						// Time calculations
						const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
						const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
						const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
						const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

						// Display logic
						document.getElementById("days").innerHTML = days.toString().padStart(2, '0');
						document.getElementById("hours").innerHTML = hours.toString().padStart(2, '0');
						document.getElementById("minutes").innerHTML = minutes.toString().padStart(2, '0');
						document.getElementById("seconds").innerHTML = seconds.toString().padStart(2, '0');

						// Expiration logic
						if (timeLeft < 0) {
							clearInterval(updateTimer);
							document.getElementById("timer-display").innerHTML = 
								"<div style='grid-column: span 4; color: var(--gold); font-weight: bold; font-size: 20px;'>OFFER EXPIRED</div>";
						}
					}, 1000);
				})();
			</script>

		<?php
	}

	/**
	 * Settings link
	 *
	 * @since 0.8.0
	 *
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$row_meta = array(
			'settings' => '<a href="' . get_admin_url( null, 'admin.php?page=wcbv-order-status-setting' ) . '">' . __( 'Settings', 'bp-custom-order-status' ) . '</a>',
		);

		return array_merge( $links, $row_meta );
	}

	public function pluginOptions() {

		// Set a unique slug-like ID
		$prefix = 'wcbv_status_default';

		$version = BVOS_PLUGIN_VER;
		if( Bootstrap::is_pro_version_activated() ) {
			$version = COSMBP_PRO_PLUGIN_VER;
		}

		// Create options
		\CSF::createOptions( $prefix, array(
			'menu_title'      => 'Order Status Settings',
			'menu_slug'       => 'wcbv-order-status-setting',
			'framework_title' => 'Custom Order Status Manager for WooCommerce <small> version: ' . esc_html( $version ) . '</small>',
			'menu_type'       => 'submenu',
			'menu_parent'     => 'brightplugins',
			'nav'             => 'inline',
			'theme'           => 'dark',
			'footer_after'    => '',
			'footer_credit'   => 'Please rate <strong>Custom Order Status Manager for WooCommerce</strong> on  <a href="https://wordpress.org/support/plugin/bp-custom-order-status-for-woocommerce/reviews/?filter=5" target="_blank">WordPress.org</a> to help us spread the word. Thank you from the Bright Plugins team!',
			'show_footer'     => false,
			'show_bar_menu'   => false,
		) );

		// Create a section
		\CSF::createSection( $prefix, array(
			'title'  => 'General Settings',
			'fields' => array(
				array(
					'id'      => 'orderstatus_default_status',
					'type'    => 'select',
					'title'   => __( 'Default Order Status', 'bp-custom-order-status' ),
					'default' => 'bpos_disabled',
					'options' => 'bpcosOrderStatusList',
				),
				array(
					'id'      => 'preorder_status',
					'type'    => 'select',
					'class'   => ( !defined( 'WCPO_PLUGIN_VER' ) ) ? 'hidden' : '',
					'title'   => __( 'Preorder Transition Status', 'bp-custom-order-status' ),
					'default' => 'pre-ordered',
					'options' => 'bpcosOrderStatusList',
				),
				array(
					'title'      => __('Link to Custom Status Page', 'bp-custom-order-status'),
					'type'       => 'text',
					'before'     => sprintf(__('Go to my <a href="%s">Custom Status Page</a>.', 'bp-custom-order-status'), admin_url('edit.php?post_type=order_status')),
					'id'         => 'go_to_custom_status_page',
					'attributes' => array(
						'style' => 'display:none;',
					),
				),
				array(
					'id'      => 'enable_wpml',
					'type'    => 'switcher',
					'title'   => __( 'Enable WPML compatibility', 'bp-custom-order-status' ),
					'class'   => ( !class_exists( 'sitepress' ) ) ? 'hidden' : '',
					'default' => ( !class_exists( 'sitepress' ) ) ? false : true,
					'desc'    => __( 'It shows the status name on the current language', 'bp-custom-order-status' ),
					'label'   => __( 'Keep disabled if find any issue', 'bp-custom-order-status' ),
				),
				array(
					'type'    => 'notice',
					'style'   => 'info',
					'content' => apply_filters( 'cosm_upsale_notice', '' ),
				),
			),
		) );

		// Create a section
        \CSF::createSection( $prefix, array(
            'title'  => 'Payment Methods',
            'fields' => array_merge(
                array(
                    // A Notice
					array(
						'type'    => 'notice',
						'style'   => 'info',
						'content' => 'Is one of your payment methods not appearing on this page or is it not working properly? It is likely not compatible with the free version <br>Please contact us through our support portal: ' . '<a href="https://brightplugins.com/support/">' . 'Support' . '</a>',
					),
                ),
				$this->getPaymentOptions(),
            ) ,
        ) );

		/**
		 * Upgrade to Club Membership section
		 */

		 add_filter( 'cosmbp_advertising_place', function(){

			$fire_icon = '<img draggable="false" role="img" class="emoji" alt="🔥" src="' . COSMBP_ASSETS . '/img/fire-icon.svg' . '">';

			$upsale_notice = '<h3>' . $fire_icon . ' All Access Membership ' . $fire_icon . '</h3>';
			$upsale_notice .= '<p>Unlock all 19 premium WooCommerce plugins with one club membership. <a href="' . self::CLUB_MEMBERSHIP_LINK . '">Join the Club</a></p>';

			return wp_kses_post( $upsale_notice );
		}  );

		\CSF::createSection( $prefix, array(
			'title'  => 'Upgrade to Club Membership',
			'icon'   => 'fas fa-lock',
			'fields' => array(
				array(
					'type'    => 'notice',
					'style'   => 'info',
					'content' => apply_filters( 'cosmbp_advertising_place', '' ),
				),
				array(
					'type'    => 'callback',
					'function' => function(){
						echo '<p><a href="' . self::CLUB_MEMBERSHIP_LINK . '"> <img style="max-width: 100%" src="' . COSMBP_ASSETS . '/img/pro-bp-plugins.png' . '"> </a></p>';
					},
				)
			)
		));

				do_action( 'cosmbp_free_setting_section', $prefix );

		/**
		 * If the pro version is activated so it cuts the flow bellow
		 */
		if( Bootstrap::is_pro_version_activated() ) {
			return;
		}

		/**
		 * Automation rules section - disabled
		 */
		\CSF::createSection($prefix, array(
			'id'     => 'automation_rules_section_disabled',
			'title'  => '<span class="pro-badge" style="position: absolute;z-index: 1;left: 0;top: -13px;background-color: white;padding: .2em .5em;border-radius: 6px;color: black;transform: rotate(-15deg);">PRO</span> ' . __('Automation Rules', 'bp-custom-order-status'),
			'fields' => [
				array(
					'type'    => 'notice',
					'style'   => 'info',
					'content' => 'This feature is Available in <a target="_blank" href="' . self::PRO_VERSION_LINK . '">Pro Version!</a> <i class="fas fa-lock"></i>',
				),
				[
					'type'     => 'callback',
					'function' => [ $this, 'generate_blocked_rules_pro_feature' ],
				],
			],
		));
	}

	/**
	 * Option list for all payment methods
	 *
	 * @return array
	 */
	public function getPaymentOptions() {
		$payment_gateways = [];

		// Validate WooCommerce Payment Gateways class availability
		if ( ! class_exists( 'WC_Payment_Gateways' ) ) {
			return array();
		}

		try {
			// Use the official WooCommerce method to get payment gateways instance
			$payment_gateways_instance = \WC_Payment_Gateways::instance();
			
			// Verify the instance is valid and has the payment_gateways method
			if ( ! method_exists( $payment_gateways_instance, 'payment_gateways' ) ) {
				return array();
			}

			$available_payment_gateways = $payment_gateways_instance->payment_gateways();
			
			// Validate that payment_gateways() returned an array
			if ( ! is_array( $available_payment_gateways ) ) {
				return array();
			}

			$payment_gateways = array();

			foreach ( $available_payment_gateways as $key => $gateway ) {
				// Validate that gateway is an object before accessing properties
				if ( ! is_object( $gateway ) ) {
					continue;
				}

				// Validate that gateway has a title property and it's not empty
				if( !isset( $gateway->title ) || empty( $gateway->title ) ) {
					continue;
				}

				$payment_gateways[] = array(
					'title'   => "Default Status for: " . $gateway->title,
					'id'      => 'orderstatus_default_statusgateway_' . $key,
					'default' => 'bpos_disabled',
					'type'    => 'select',
					'desc'    => __('Order on this payment method will change to this status ', 'bp-custom-order-status'),
					'options' => 'bpcosOrderStatusList',
				);
			}
		} catch (\Throwable $th) {
			error_log( 'Bright Plugins - Custom Order Status Manager - ERROR: ' . $th->getMessage());
		}
		
		return $payment_gateways;
	}

	/**
	 * Get all woocommerce order status
	 *
	 * @return array
	 */
	public function wcbv_get_all_status()
	{
		$result = array();
		if ($_REQUEST["page"] ?? '' == 'wcbv-order-status-setting') {
			$statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array();
			foreach ($statuses as $status => $status_name) {
				$result[substr($status, 3)] = $status_name;
			}
		}
		return $result;
	}

	/**
	 * Add links to plugin's description in plugins table
	 *
	 * @param  array   $links Initial list of links.
	 * @param  string  $file  Basename of current plugin.
	 * @return array
	 */
	public function pluginMetaLinks($links, $file)
	{
		if (BVOS_PLUGIN_BASE !== $file) {
			return $links;
		}
		$rate_cos     = '<a target="_blank" href="https://wordpress.org/support/plugin/bp-custom-order-status-for-woocommerce/reviews/?filter=5"> Rate this plugin » </a>';
		$support_link = '<a style="color:red;" target="_blank" href="https://brightplugins.com/support/">' . __('Support', 'bp-custom-order-status') . '</a>';

		$links[] = $rate_cos;
		$links[] = $support_link;

		return $links;
	}

	public function bp_admin_menu()
	{

		add_menu_page('Bright Plugins', 'Bright Plugins', '#manage_options', 'brightplugins', null, plugin_dir_url(__DIR__) . 'assets/img/bp-logo-icon.png', 60);

		do_action('bp_sub_menu');
	}


	/**
	 * It generates blocked rules pro feaures
	 * 
	 * CSS - HTML code
	 */
	public function generate_blocked_rules_pro_feature() {
		?>
			<style>
				.cosm-free-option-blocked {
					opacity: 0.6;
					pointer-events: none;
				}

				@keyframes atencionPro {
					0% {
						transform: rotate(-5deg);
						filter: brightness(100%);
					}
					50% {
						transform: rotate(-20deg);
						filter: brightness(110%);
						text-shadow: 0 0 5px rgba(255, 255, 255, 0.8);
					}
					100% {
						transform: rotate(-5deg);
						filter: brightness(100%);
					}
				}
				.pro-badge {
					animation: atencionPro 1.5s ease-in-out 12; 
					cursor: pointer;
					display: inline-block;
					transition: all 0.3s ease;
				}

				.pro-badge:hover {
					animation-duration: 0.8s;
				}
			</style>
			
			<div style="margin: 20px 3px;">
				
			</div>

			<div style="margin: 20px 3px;">
				<ul>
					<li>
						- &#128221; See how the feature <a target="_blank" href="<?php echo self::DOCS ?>">Automation rules</a> works (in less than 3 minute)
					</li>
				</ul>
			</div>
			
			<hr>
			
			<div class="cosm-free-option-blocked">
				<div style="display: flex;align-items: anchor-center;column-gap: 1em;">
					<h1 style="display: inline;">Order Status Automation Rules</h1>
					<a href="#" class="action  button">Add Automation Rule</a>
				</div>
				<div method="post">
					<input type="hidden" name="page" value="wcbv-order-status-setting">
					<input type="hidden" name="csf-tab" value="automation_rules_section">
					<ul class="subsubsub">
						<li class="all"><a href="#" class="current  ">All <span class="count">(1)</span></a> |</li>
						<li class="publish"><a href="#" class="">Published <span class="count">(1)</span></a> |</li>
						<li class="trash"><a href="#" class="">Trash <span class="count">(0)</span></a></li>
					</ul>
					<p class="search-box">
						<label class="screen-reader-text" for="rule-search-search-input">Search Automation Rules:</label>
						<input type="search" id="rule-search-search-input" name="s" value="">
						<input type="submit" id="search-submit" class="button  " value="Search Automation Rules">
					</p>
					<input type="hidden" id="_wpnonce" name="_wpnonce" value="7f59459a1a"><input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=wcbv-order-status-setting&amp;_bcos_tab=automation-rules">
					<div class="tablenav top">

						<div class="alignleft actions bulkactions">
							<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label><select name="action" class="" id="bulk-action-selector-top">
								<option value="-1">Bulk actions</option>
								<option value="trash">Move to Trash</option>
							</select>
							<input type="submit" name="bulk_action" id="doaction" class="button action  " value="Apply">
						</div>
						<div class="alignleft actions">
							<label for="filter-by-date" class="screen-reader-text">Filter by date</label>
							<select name="m" id="filter-by-date" class="">
								<option selected="selected" value="0">All dates</option>
								<option value="202507">July 2025</option>
							</select>
							<input type="submit" name="filter_action" id="post-query-submit" class="button  " value="Filter">
						</div>
						<div class="tablenav-pages one-page"><span class="displaying-num">1 item</span>
							<span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
								<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">1</span></span></span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span></span>
						</div>
						<br class="clear">
					</div>
					<table class="wp-list-table widefat fixed striped table-view-list rules">
						<thead>
							<tr>
								<td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox">
									<label for="cb-select-all-1"><span class="screen-reader-text">Select All</span></label>
								</td>
								<th scope="col" id="rule_id" class="manage-column column-rule_id column-primary sortable desc  "><a href="#"><span>ID</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">Sort ascending.</span></a></th>
								<th scope="col" id="title" class="manage-column column-title sortable asc  "><a href="#"><span>Title</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">Sort descending.</span></a></th>
								<th scope="col" id="date" class="manage-column column-date sortable asc  "><a href="#"><span>Date</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">Sort descending.</span></a></th>
								<th scope="col" id="rule_status" class="manage-column column-rule_status  ">Rule Status</th>
								<th scope="col" id="rule_priority" class="manage-column column-rule_priority sortable desc  "><a href="#"><span>Rule Priority</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">Sort ascending.</span></a></th>
							</tr>
						</thead>

						<tbody id="the-list" data-wp-lists="list:rule" class="">
							<tr>
								<th scope="row" class="check-column"><input type="checkbox" name="rule[]" value="1613"></th>
								<td class="rule_id column-rule_id has-row-actions column-primary" data-colname="ID">1613<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
								<td class="title column-title" data-colname="Title"><strong><a href="#">Automatic change from Awaiting Pickup to Picked Up after 7 days</a></strong>
									<div class="row-actions"><span class="edit"><a href="#">Edit</a> | </span><span class="trash"><a href="#" class="submitdelete">Trash</a></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
								</td>
								<td class="date column-date" data-colname="Date">February 16, 2026</td>
								<td class="rule_status column-rule_status" data-colname="Rule Status">Enabled</td>
								<td class="rule_priority column-rule_priority" data-colname="Rule Priority">1</td>
							</tr>
						</tbody>

						<tfoot>
							<tr class="">
								<td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox">
									<label for="cb-select-all-2"><span class="screen-reader-text">Select All</span></label>
								</td>
								<th scope="col" class="manage-column column-rule_id column-primary sortable desc"><a href="#"><span>ID</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">Sort ascending.</span></a></th>
								<th scope="col" class="manage-column column-title sortable asc"><a href="#"><span>Title</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">Sort descending.</span></a></th>
								<th scope="col" class="manage-column column-date sortable asc"><a href="#"><span>Date</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">Sort descending.</span></a></th>
								<th scope="col" class="manage-column column-rule_status">Rule Status</th>
								<th scope="col" class="manage-column column-rule_priority sortable desc"><a href="#"><span>Rule Priority</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">Sort ascending.</span></a></th>
							</tr>
						</tfoot>

					</table>
					<div class="tablenav bottom  ">

						<div class="alignleft actions bulkactions">
							<label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label><select name="action2" id="bulk-action-selector-bottom">
								<option value="-1">Bulk actions</option>
								<option value="trash">Move to Trash</option>
							</select>
							<input type="submit" name="bulk_action" id="doaction2" class="button action" value="Apply">
						</div>
						<div class="tablenav-pages one-page"><span class="displaying-num">1 item</span>
							<span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
								<span class="screen-reader-text">Current Page</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">1 of <span class="total-pages">1</span></span></span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span></span>
						</div>
						<br class="clear">
					</div>
				</div>

			</div>

		<?php
	}
}
