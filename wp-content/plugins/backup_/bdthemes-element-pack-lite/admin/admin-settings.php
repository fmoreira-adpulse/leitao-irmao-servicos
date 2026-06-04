<?php

use ElementPack\Notices;
use ElementPack\Utils;
use ElementPack\Admin\ModuleService;
use ElementPack\Base\Element_Pack_Base;
use Elementor\Modules\Usage\Module;
use Elementor\Tracker;

/**
 * Element Pack Admin Settings Class
 */

class ElementPack_Admin_Settings {

	public static $modules_list = null;
	public static $modules_names = null;

	public static $modules_list_only_widgets = null;
	public static $modules_names_only_widgets = null;

	public static $modules_list_only_3rdparty = null;
	public static $modules_names_only_3rdparty = null;

	const PAGE_ID = 'element_pack_options';

	private $settings_api;

	public $responseObj;
	public $showMessage = false;
	private $is_activated = false;

	function __construct() {
		$this->settings_api = new ElementPack_Settings_API;

		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 201 );

		/**
		 * black_friday_notice
		 * Will be not show after 2024-12-06 00:00:00
		 */
		$current_date = date( 'Y-m-d H:i:s' );
		$end_date     = '2024-12-06 00:00:00';

		if ( strtotime( $current_date ) < strtotime( $end_date ) ) {
			add_action( 'admin_notices', [ $this, 'black_friday_notice' ], 10, 3 );
		}
	}

	/**
	 *Black Friday Notice
	 *
	 * @access public
	 */
	public function black_friday_notice() {
		Notices::add_notice(
			[ 
				'id'               => 'black-friday',
				'type'             => 'success',
				'dismissible'      => true,
				'dismissible-time' => HOUR_IN_SECONDS * 72,
				'html_message'     => $this->black_friday_offer_notice_message(),
			]
		);
	}

	public function black_friday_offer_notice_message() {
		$plugin_icon  = BDTEP_ASSETS_URL . 'images/logo.svg';
		$plugin_title = __( 'Best Savings On Black Friday Deals - ⚡Up To 85% Off🔥', 'bdthemes-element-pack' );
		ob_start();
		?>
				<div class="bdt-license-notice-global element_pack_pro">
					<div class="bdt-license-notice-content">
						<h3>
							<?php echo wp_kses_post( $plugin_title ); ?>
						</h3>
						<div class="bdt-license-notice-button-wrap">
							<a href="https://bdthemes.com/black-friday/" target="_blank" class="bdt-button bdt-button-allow">
								<?php esc_html_e( 'Get Deals Now', 'bdthemes-element-pack' ); ?>
							</a>
						</div>
					</div>
					<a href="https://bdthemes.com/black-friday/" target="_blank" class="bdt-link-btn"></a>
				</div>
				<?php
				return ob_get_clean();
	}

	/**
	 * Get used widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */
	public static function get_used_widgets() {

		$used_widgets = array();

		if ( ! Tracker::is_allow_track() ) {
			return $used_widgets;
		}

		if ( class_exists( 'Elementor\Modules\Usage\Module' ) ) {

			$module     = Module::instance();
			$elements   = $module->get_formatted_usage( 'raw' );
			$ep_widgets = self::get_ep_widgets_names();

			if ( is_array( $elements ) || is_object( $elements ) ) {

				foreach ( $elements as $post_type => $data ) {
					foreach ( $data['elements'] as $element => $count ) {
						if ( in_array( $element, $ep_widgets, true ) ) {
							if ( isset( $used_widgets[ $element ] ) ) {
								$used_widgets[ $element ] += $count;
							} else {
								$used_widgets[ $element ] = $count;
							}
						}
					}
				}
			}
		}

		return $used_widgets;
	}

	/**
	 * Get used separate widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_used_only_widgets() {

		$used_widgets = array();

		if ( ! Tracker::is_allow_track() ) {
			return $used_widgets;
		}

		if ( class_exists( 'Elementor\Modules\Usage\Module' ) ) {

			$module     = Module::instance();
			$elements   = $module->get_formatted_usage( 'raw' );
			$ep_widgets = self::get_ep_only_widgets();

			if ( is_array( $elements ) || is_object( $elements ) ) {

				foreach ( $elements as $post_type => $data ) {
					foreach ( $data['elements'] as $element => $count ) {
						if ( in_array( $element, $ep_widgets, true ) ) {
							if ( isset( $used_widgets[ $element ] ) ) {
								$used_widgets[ $element ] += $count;
							} else {
								$used_widgets[ $element ] = $count;
							}
						}
					}
				}
			}
		}

		return $used_widgets;
	}

	/**
	 * Get used only separate 3rdParty widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_used_only_3rdparty() {

		$used_widgets = array();

		if ( ! Tracker::is_allow_track() ) {
			return $used_widgets;
		}

		if ( class_exists( 'Elementor\Modules\Usage\Module' ) ) {

			$module     = Module::instance();
			$elements   = $module->get_formatted_usage( 'raw' );
			$ep_widgets = self::get_ep_only_3rdparty_names();

			if ( is_array( $elements ) || is_object( $elements ) ) {

				foreach ( $elements as $post_type => $data ) {
					foreach ( $data['elements'] as $element => $count ) {
						if ( in_array( $element, $ep_widgets, true ) ) {
							if ( isset( $used_widgets[ $element ] ) ) {
								$used_widgets[ $element ] += $count;
							} else {
								$used_widgets[ $element ] = $count;
							}
						}
					}
				}
			}
		}

		return $used_widgets;
	}

	/**
	 * Get unused widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_unused_widgets() {

		if ( ! current_user_can( 'install_plugins' ) ) {
			die();
		}

		$ep_widgets = self::get_ep_widgets_names();

		$used_widgets = self::get_used_widgets();

		$unused_widgets = array_diff( $ep_widgets, array_keys( $used_widgets ) );

		return $unused_widgets;
	}

	/**
	 * Get unused separate widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_unused_only_widgets() {

		if ( ! current_user_can( 'install_plugins' ) ) {
			die();
		}

		$ep_widgets = self::get_ep_only_widgets();

		$used_widgets = self::get_used_only_widgets();

		$unused_widgets = array_diff( $ep_widgets, array_keys( $used_widgets ) );

		return $unused_widgets;
	}

	/**
	 * Get unused separate 3rdparty widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_unused_only_3rdparty() {

		if ( ! current_user_can( 'install_plugins' ) ) {
			die();
		}

		$ep_widgets = self::get_ep_only_3rdparty_names();

		$used_widgets = self::get_used_only_3rdparty();

		$unused_widgets = array_diff( $ep_widgets, array_keys( $used_widgets ) );

		return $unused_widgets;
	}

	/**
	 * Get widgets name
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_ep_widgets_names() {
		$names = self::$modules_names;

		if ( null === $names ) {
			$names = array_map(
				function ($item) {
					return isset( $item['name'] ) ? 'bdt-' . str_replace( '_', '-', $item['name'] ) : 'none';
				},
				self::$modules_list
			);
		}

		return $names;
	}

	/**
	 * Get separate widgets name
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_ep_only_widgets() {
		$names = self::$modules_names_only_widgets;

		if ( null === $names ) {
			$names = array_map(
				function ($item) {
					return isset( $item['name'] ) ? 'bdt-' . str_replace( '_', '-', $item['name'] ) : 'none';
				},
				self::$modules_list_only_widgets
			);
		}

		return $names;
	}

	/**
	 * Get separate 3rdParty widgets name
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_ep_only_3rdparty_names() {
		$names = self::$modules_names_only_3rdparty;

		if ( null === $names ) {
			$names = array_map(
				function ($item) {
					return isset( $item['name'] ) ? 'bdt-' . str_replace( '_', '-', $item['name'] ) : 'none';
				},
				self::$modules_list_only_3rdparty
			);
		}

		return $names;
	}

	/**
	 * Get URL with page id
	 *
	 * @access public
	 *
	 */

	public static function get_url() {
		return admin_url( 'admin.php?page=' . self::PAGE_ID );
	}

	/**
	 * Init settings API
	 *
	 * @access public
	 *
	 */

	public function admin_init() {

		//set the settings
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->element_pack_admin_settings() );

		//initialize settings
		$this->settings_api->admin_init();
		$this->ep_redirect_to_upgrade();
	}

	// Redirect to Element Pack Pro pricing page
	public function ep_redirect_to_upgrade() {
		if (isset($_GET['page']) && $_GET['page'] === self::PAGE_ID . '_upgrade') {
			wp_redirect('https://www.elementpack.pro/pricing/');
			exit;
		}
	}

	/**
	 * Add Plugin Menus
	 *
	 * @access public
	 *
	 */

	public function admin_menu() {
		add_menu_page(
			BDTEP_TITLE . ' ' . esc_html__( 'Dashboard', 'bdthemes-element-pack' ),
			BDTEP_TITLE,
			'manage_options',
			self::PAGE_ID,
			[ $this, 'plugin_page' ],
			$this->element_pack_icon(),
			58
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTEP_TITLE,
			esc_html__( 'Core Widgets', 'bdthemes-element-pack' ),
			'manage_options',
			self::PAGE_ID . '#element_pack_active_modules',
			[ $this, 'display_page' ]
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTEP_TITLE,
			esc_html__( '3rd Party Widgets', 'bdthemes-element-pack' ),
			'manage_options',
			self::PAGE_ID . '#element_pack_third_party_widget',
			[ $this, 'display_page' ]
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTEP_TITLE,
			esc_html__( 'Extensions', 'bdthemes-element-pack' ),
			'manage_options',
			self::PAGE_ID . '#element_pack_elementor_extend',
			[ $this, 'display_page' ]
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTEP_TITLE,
			esc_html__( 'API Settings', 'bdthemes-element-pack' ),
			'manage_options',
			self::PAGE_ID . '#element_pack_api_settings',
			[ $this, 'display_page' ]
		);

		if ( ! defined( 'BDTEP_LO' ) ) {
			add_submenu_page(
				self::PAGE_ID,
				BDTEP_TITLE,
				esc_html__( 'Other Settings', 'bdthemes-element-pack' ),
				'manage_options',
				self::PAGE_ID . '#element_pack_other_settings',
				[ $this, 'display_page' ]
			);
		}

		if ( ! defined( 'BDTEP_LO' ) ) {
			add_submenu_page(
				self::PAGE_ID,                    
				BDTEP_TITLE,                     
				esc_html__( 'Upgrade', 'bdthemes-element-pack' ),  
				'manage_options',                 
				self::PAGE_ID . '_upgrade',
				[ $this, 'display_page' ]
			);
		}
	}

	/**
	 * Get SVG Icons of Element Pack
	 *
	 * @access public
	 * @return string
	 */

	public function element_pack_icon() {
		return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAyMy4wLjIsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiDQoJIHdpZHRoPSIyMzAuN3B4IiBoZWlnaHQ9IjI1NC44MXB4IiB2aWV3Qm94PSIwIDAgMjMwLjcgMjU0LjgxIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAyMzAuNyAyNTQuODE7Ig0KCSB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+DQoJLnN0MHtmaWxsOiNGRkZGRkY7fQ0KPC9zdHlsZT4NCjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik02MS4wOSwyMjkuMThIMjguOTVjLTMuMTcsMC01Ljc1LTIuNTctNS43NS01Ljc1bDAtMTkyLjA3YzAtMy4xNywyLjU3LTUuNzUsNS43NS01Ljc1aDMyLjE0DQoJYzMuMTcsMCw1Ljc1LDIuNTcsNS43NSw1Ljc1djE5Mi4wN0M2Ni44MywyMjYuNjEsNjQuMjYsMjI5LjE4LDYxLjA5LDIyOS4xOHoiLz4NCjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0yMDcuNSwzMS4zN3YzMi4xNGMwLDMuMTctMi41Nyw1Ljc1LTUuNzUsNS43NUg5MC4wNGMtMy4xNywwLTUuNzUtMi41Ny01Ljc1LTUuNzVWMzEuMzcNCgljMC0zLjE3LDIuNTctNS43NSw1Ljc1LTUuNzVoMTExLjcyQzIwNC45MywyNS42MiwyMDcuNSwyOC4yLDIwNy41LDMxLjM3eiIvPg0KPHBhdGggY2xhc3M9InN0MCIgZD0iTTIwNy41LDExMS4zM3YzMi4xNGMwLDMuMTctMi41Nyw1Ljc1LTUuNzUsNS43NUg5MC4wNGMtMy4xNywwLTUuNzUtMi41Ny01Ljc1LTUuNzV2LTMyLjE0DQoJYzAtMy4xNywyLjU3LTUuNzUsNS43NS01Ljc1aDExMS43MkMyMDQuOTMsMTA1LjU5LDIwNy41LDEwOC4xNiwyMDcuNSwxMTEuMzN6Ii8+DQo8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMjA3LjUsMTkxLjN2MzIuMTRjMCwzLjE3LTIuNTcsNS43NS01Ljc1LDUuNzVIOTAuMDRjLTMuMTcsMC01Ljc1LTIuNTctNS43NS01Ljc1VjE5MS4zDQoJYzAtMy4xNywyLjU3LTUuNzUsNS43NS01Ljc1aDExMS43MkMyMDQuOTMsMTg1LjU1LDIwNy41LDE4OC4xMywyMDcuNSwxOTEuM3oiLz4NCjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xNjkuNjIsMjUuNjJoMzIuMTRjMy4xNywwLDUuNzUsMi41Nyw1Ljc1LDUuNzV2MTEyLjFjMCwzLjE3LTIuNTcsNS43NS01Ljc1LDUuNzVoLTMyLjE0DQoJYy0zLjE3LDAtNS43NS0yLjU3LTUuNzUtNS43NVYzMS4zN0MxNjMuODcsMjguMiwxNjYuNDQsMjUuNjIsMTY5LjYyLDI1LjYyeiIvPg0KPC9zdmc+DQo=';
	}

	/**
	 * Get SVG Icons of Element Pack
	 *
	 * @access public
	 * @return array
	 */

	public function get_settings_sections() {
		$sections = [ 
			[ 
				'id'    => 'element_pack_active_modules',
				'title' => esc_html__( 'Core Widgets', 'bdthemes-element-pack' )
			],
			[ 
				'id'    => 'element_pack_third_party_widget',
				'title' => esc_html__( '3rd Party Widgets', 'bdthemes-element-pack' )
			],
			[ 
				'id'    => 'element_pack_elementor_extend',
				'title' => esc_html__( 'Extensions', 'bdthemes-element-pack' )
			],
			[ 
				'id'    => 'element_pack_api_settings',
				'title' => esc_html__( 'API Settings', 'bdthemes-element-pack' ),
			],
			[ 
				'id'    => 'element_pack_other_settings',
				'title' => esc_html__( 'Other Settings', 'bdthemes-element-pack' ),
			],
		];

		return $sections;
	}

	/**
	 * Merge Admin Settings
	 *
	 * @access protected
	 * @return array
	 */

	protected function element_pack_admin_settings() {

		return ModuleService::get_widget_settings( function ($settings) {
			$settings_fields = $settings['settings_fields'];

			self::$modules_list               = array_merge( $settings_fields['element_pack_active_modules'], $settings_fields['element_pack_third_party_widget'] );
			self::$modules_list_only_widgets  = $settings_fields['element_pack_active_modules'];
			self::$modules_list_only_3rdparty = $settings_fields['element_pack_third_party_widget'];

			return $settings_fields;
		} );
	}

	/**
	 * Get Welcome Panel
	 *
	 * @access public
	 * @return void
	 */

	public function element_pack_welcome() {
		$track_nw_msg = '';
		if ( ! Tracker::is_allow_track() ) {
			$track_nw     = esc_html__( 'This feature is not working because the Elementor Usage Data Sharing feature is Not Enabled.', 'bdthemes-element-pack' );
			$track_nw_msg = 'bdt-tooltip="' . $track_nw . '"';
		}
		?>

		<div class="ep-dashboard-panel"
			data-bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">

			<div class="bdt-grid bdt-grid-medium" data-bdt-grid data-bdt-height-match="target: > div > .bdt-card">
				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ep-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post( $track_nw_msg ); ?>>

						<?php
						$used_widgets    = count( self::get_used_widgets() );
						$un_used_widgets = count( self::get_unused_widgets() );
						?>


						<div class="ep-count-canvas-wrap">
							<h1 class="ep-feature-title"><?php echo esc_html__( 'All Widgets', 'bdthemes-element-pack' ); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ep-count-wrap">
									<div class="ep-widget-count">
										<?php echo esc_html__( 'Used: ', 'bdthemes-element-pack' ); ?><b><?php echo $used_widgets; ?></b>
									</div>
									<div class="ep-widget-count">
										<?php echo esc_html__( 'Unused: ', 'bdthemes-element-pack' ); ?><b><?php echo $un_used_widgets; ?></b>
									</div>
									<div class="ep-widget-count"><?php echo esc_html__( 'Total: ', 'bdthemes-element-pack' ); ?>
										<b><?php echo $used_widgets + $un_used_widgets; ?></b>
									</div>
								</div>

								<div class="ep-canvas-wrap">
									<canvas id="bdt-db-total-status" style="height: 100px; width: 100px;"
										data-label="<?php echo esc_html__( 'Total Widgets Status', 'bdthemes-element-pack' ); ?> - (<?php echo $used_widgets + $un_used_widgets; ?>)"
										data-labels="<?php echo esc_attr( 'Used, Unused' ); ?>"
										data-value="<?php echo esc_attr( $used_widgets ) . ',' . esc_attr( $un_used_widgets ); ?>"
										data-bg="#FFD166, #fff4d9" data-bg-hover="#0673e1, #e71522"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ep-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post( $track_nw_msg ); ?>>

						<?php
						$used_only_widgets   = count( self::get_used_only_widgets() );
						$unused_only_widgets = count( self::get_unused_only_widgets() );
						?>


						<div class="ep-count-canvas-wrap">
							<h1 class="ep-feature-title"><?php echo esc_html__( 'Core', 'bdthemes-element-pack' ); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ep-count-wrap">
									<div class="ep-widget-count">
										<?php echo esc_html__( 'Used: ', 'bdthemes-element-pack' ); ?><b><?php echo $used_only_widgets; ?></b>
									</div>
									<div class="ep-widget-count">
										<?php echo esc_html__( 'Unused: ', 'bdthemes-element-pack' ); ?><b><?php echo $unused_only_widgets; ?></b>
									</div>
									<div class="ep-widget-count"><?php echo esc_html__( 'Total: ', 'bdthemes-element-pack' ); ?>
										<b><?php echo $used_only_widgets + $unused_only_widgets; ?></b>
									</div>
								</div>

								<div class="ep-canvas-wrap">
									<canvas id="bdt-db-only-widget-status" style="height: 100px; width: 100px;"
										data-label="<?php echo esc_html__( 'Core Widgets Status', 'bdthemes-element-pack' ); ?> - (<?php echo $used_only_widgets + $unused_only_widgets; ?>)"
										data-labels="<?php echo esc_attr( 'Used, Unused' ); ?>"
										data-value="<?php echo esc_attr( $used_only_widgets ) . ',' . esc_attr( $unused_only_widgets ); ?>"
										data-bg="#EF476F, #ffcdd9" data-bg-hover="#0673e1, #e71522"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ep-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post( $track_nw_msg ); ?>>

						<?php
						$used_only_3rdparty   = count( self::get_used_only_3rdparty() );
						$unused_only_3rdparty = count( self::get_unused_only_3rdparty() );
						?>


						<div class="ep-count-canvas-wrap">
							<h1 class="ep-feature-title"><?php echo esc_html__( '3rd Party', 'bdthemes-element-pack' ); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ep-count-wrap">
									<div class="ep-widget-count">
										<?php echo esc_html__( 'Used: ', 'bdthemes-element-pack' ); ?><b><?php echo $used_only_3rdparty; ?></b>
									</div>
									<div class="ep-widget-count">
										<?php echo esc_html__( 'Unused: ', 'bdthemes-element-pack' ); ?><b><?php echo $unused_only_3rdparty; ?></b>
									</div>
									<div class="ep-widget-count"><?php echo esc_html__( 'Total:', 'bdthemes-element-pack' ); ?>
										<b><?php echo $used_only_3rdparty + $unused_only_3rdparty; ?></b>
									</div>
								</div>

								<div class="ep-canvas-wrap">
									<canvas id="bdt-db-only-3rdparty-status" style="height: 100px; width: 100px;"
										data-label="<?php echo esc_html__( '3rd Party Widgets Status', 'bdthemes-element-pack' ); ?> - (<?php echo $used_only_3rdparty + $unused_only_3rdparty; ?>)"
										data-labels="<?php echo esc_attr( 'Used, Unused' ); ?>"
										data-value="<?php echo esc_attr( $used_only_3rdparty ) . ',' . esc_attr( $unused_only_3rdparty ); ?>"
										data-bg="#06D6A0, #B6FFEC" data-bg-hover="#0673e1, #e71522"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>

				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ep-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post( $track_nw_msg ); ?>>

						<div class="ep-count-canvas-wrap">
							<h1 class="ep-feature-title"><?php echo esc_html__( 'Active', 'bdthemes-element-pack' ); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ep-count-wrap">
									<div class="ep-widget-count"><?php echo esc_html__( 'Core: ', 'bdthemes-element-pack' ); ?><b
											id="bdt-total-widgets-status-core"></b></div>
									<div class="ep-widget-count"><?php echo esc_html__( '3rd Party: ', 'bdthemes-element-pack' ); ?><b
											id="bdt-total-widgets-status-3rd"></b></div>
									<div class="ep-widget-count">
										<?php echo esc_html__( 'Extensions: ', 'bdthemes-element-pack' ); ?><b
											id="bdt-total-widgets-status-extensions"></b></div>
									<div class="ep-widget-count"><?php echo esc_html__( 'Total: ', 'bdthemes-element-pack' ); ?><b
											id="bdt-total-widgets-status-heading"></b></div>
								</div>

								<div class="ep-canvas-wrap">
									<canvas id="bdt-total-widgets-status" style="height: 100px; width: 100px;"
										data-label="<?php echo esc_html__( 'Total Active Widgets Status', 'bdthemes-element-pack' ); ?>"
										data-labels="<?php echo esc_attr( 'Core, 3rd Party, Extensions' ); ?>"
										data-bg="#0680d6, #B0EBFF, #E6F9FF" data-bg-hover="#0673e1, #B0EBFF, #b6f9e8">
									</canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>

			<?php if ( ! Tracker::is_allow_track() ) : ?>
				<div class="bdt-border-rounded bdt-box-shadow-small bdt-alert-warning" bdt-alert>
					<a href class="bdt-alert-close" bdt-close></a>
					<div class="bdt-text-default">
						<?php
						esc_html_e( 'To view widgets analytics, Elementor Usage Data Sharing feature by Elementor needs to be activated. Please activate the feature to get widget analytics instantly ', 'bdthemes-element-pack' );
						echo '<a href="' . esc_url( admin_url( 'admin.php?page=elementor' ) ) . '">from here.</a>';
						?>
					</div>
				</div>
			<?php endif; ?>

			<div class="bdt-grid bdt-grid-medium" bdt-grid bdt-height-match="target: > div > .bdt-card">
				<div class="bdt-width-2-5@m ep-support-section">
					<div class="ep-support-content bdt-card bdt-card-body">
						<?php
						echo '<h1 class="ep-feature-title">' . esc_html__( 'Support And Feedback', 'bdthemes-element-pack' ) . '</h1>';
						echo '<p>' . esc_html__( 'Feeling like to consult with an expert? Take live Chat support immediately from', 'bdthemes-element-pack' ) . ' <a href="https://elementpack.pro" target="_blank" rel="">ElementPack</a>.' . esc_html__( ' We are always ready to help you 24/7.', 'bdthemes-element-pack' ) . '</p>';
						echo '<p><strong>' . esc_html__( 'Or if you\'re facing technical issues with our plugin, then please create a support ticket', 'bdthemes-element-pack' ) . '</strong></p>';
						echo '<a class="bdt-button bdt-btn-blue bdt-margin-small-top bdt-margin-small-right" target="_blank" rel="" href="https://bdthemes.com/all-knowledge-base-of-element-pack/">' . esc_html__( 'Knowledge Base', 'bdthemes-element-pack' ) . '</a>';
						echo '<a class="bdt-button bdt-btn-grey bdt-margin-small-top" target="_blank" href="https://bdthemes.com/support/">' . esc_html__( 'Get Support', 'bdthemes-element-pack' ) . '</a>';
						?>
					</div>
				</div>

				<div class="bdt-width-3-5@m">
					<div class="bdt-card bdt-card-body ep-system-requirement">
						<h1 class="ep-feature-title bdt-margin-small-bottom">
							<?php echo esc_html__( 'System Requirement', 'bdthemes-element-pack' ); ?></h1>
						<?php $this->element_pack_system_requirement(); ?>
					</div>
				</div>
			</div>

			<div class="bdt-grid bdt-grid-medium" bdt-grid bdt-height-match="target: > div > .bdt-card">
				<div class="bdt-width-1-2@m ep-support-section">
					<div class="bdt-card bdt-card-body ep-feedback-bg">
						<?php
						echo '<h1 class="ep-feature-title">' . esc_html__( 'Missing Any Feature?', 'bdthemes-element-pack' ) . '</h1>';
						echo '<p>' . esc_html__( 'Are you in need of a feature that\'s not available in our plugin? Feel free to do a feature request from here.', 'bdthemes-element-pack' ) . '</p>';
						echo '<a class="bdt-button bdt-btn-grey bdt-margin-small-top" target="_blank" rel="" href="https://feedback.bdthemes.com/b/6vr2250l/feature-requests/">' . esc_html__( 'Request Feature', 'bdthemes-element-pack' ) . '</a>';
						?>
					</div>
				</div>

				<div class="bdt-width-1-2@m">
					<div class="bdt-card bdt-card-body ep-tryaddon-bg">
						<?php
						echo '<h1 class="ep-feature-title">' . esc_html__( 'Try Our Others Plugins', 'bdthemes-element-pack' ) . '</h1>';
						echo '<p style="max-width: 520px;">';
						echo '<b>' . esc_html__( 'Prime Slider, Ultimate Store Kit, Ultimate Store Kit & Live Copy Paste', 'bdthemes-element-pack' ) . '</b> ' . esc_html__( 'addons for', 'bdthemes-element-pack' ) . ' <b>' . esc_html__( 'Elementor', 'bdthemes-element-pack' ) . '</b> ' . esc_html__( 'is the best slider, blogs and eCommerce plugin for WordPress.', 'bdthemes-element-pack' );
						echo esc_html__( ' Also, try our new plugin ZoloBlocks for Gutenberg.', 'bdthemes-prime-slider' );
						echo '</p>';
						echo '<div class="bdt-others-plugins-link">';
						echo '<a class="bdt-button bdt-btn-ps bdt-margin-small-right" target="_blank" href="https://wordpress.org/plugins/bdthemes-prime-slider-lite/" bdt-tooltip="' . esc_html__( 'The revolutionary slider builder addon for Elementor with next-gen superb interface. It\'s Free! Download it.', 'bdthemes-element-pack' ) . '">Prime Slider</a>';
						echo '<a class="bdt-button bdt-btn-zb bdt-margin-small-right" target="_blank" href="https://wordpress.org/plugins/zoloblocks/" bdt-tooltip="' . esc_html__( 'ZoloBlocks is a powerful and lightweight page builder for WordPress. It\'s Free! Download it.', 'bdthemes-element-pack' ) . '">ZoloBlocks</a>';
						echo '<a class="bdt-button bdt-btn-upk bdt-margin-small-right" target="_blank" rel="" href="https://wordpress.org/plugins/ultimate-post-kit/" bdt-tooltip="' . esc_html__( 'Best blogging addon for building quality blogging website with fine-tuned features and widgets. It\'s Free! Download it.', 'bdthemes-element-pack' ) . '">Ultimate Post Kit</a>';
						echo '<a class="bdt-button bdt-btn-usk bdt-margin-small-right" target="_blank" rel="" href="https://wordpress.org/plugins/ultimate-store-kit/" bdt-tooltip="' . esc_html__( 'The only eCommmerce addon for answering all your online store design problems in one package. It\'s Free! Download it.', 'bdthemes-element-pack' ) . '">Ultimate Store Kit</a>';
						echo '<a class="bdt-button bdt-btn-live-copy bdt-margin-small-right" target="_blank" rel="" href="https://wordpress.org/plugins/live-copy-paste/" bdt-tooltip="' . esc_html__( 'Superfast cross-domain copy-paste mechanism for WordPress websites with true UI copy experience. It\'s Free! Download it.', 'bdthemes-element-pack' ) . '">Live Copy Paste</a>';
						echo '<a class="bdt-button bdt-btn-pg bdt-margin-small-right" target="_blank" href="https://wordpress.org/plugins/pixel-gallery/" bdt-tooltip="' . esc_html__( 'Pixel Gallery provides more than 30+ essential elements for everyday applications to simplify the whole web building process. It\'s Free! Download it.', 'bdthemes-element-pack' ) . '">Pixel Gallery</a>';
						echo '</div>';
						?>
					</div>
				</div>
			</div>

		</div>


		<?php
	}

	/**
	 * Get Pro
	 *
	 * @access public
	 * @return void
	 */

	function element_pack_get_pro() {
		?>
		<div class="ep-dashboard-panel"
			bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">

			<div class="bdt-grid" bdt-grid bdt-height-match="target: > div > .bdt-card"
				style="max-width: 800px; margin-left: auto; margin-right: auto;">
				<div class="bdt-width-1-1@m ep-comparision bdt-text-center">
					<div class="bdt-flex bdt-flex-between bdt-flex-middle">
						<div class="bdt-text-left">
							<h1 class="bdt-text-bold"><?php echo esc_html__( 'WHY GO WITH PRO?', 'bdthemes-element-pack' ); ?></h1>
							<h2><?php echo esc_html__( 'Just Compare With Element Pack Lite Vs Pro', 'bdthemes-element-pack' ); ?></h2>
						</div>
						<div class="ep-purchase-button">
							<a href="https://elementpack.pro/pricing/" target="_blank"><?php echo esc_html__( 'Purchase Now', 'bdthemes-element-pack' ); ?></a>
						</div>
					</div>

					<div>

						<ul class="bdt-list bdt-list-divider bdt-text-left bdt-text-normal" style="font-size: 15px;">


							<li class="bdt-text-bold">
								<div class="bdt-grid">
									<?php
									echo '<div class="bdt-width-expand@m">' . esc_html__( 'Features', 'bdthemes-element-pack' ) . '</div>';
									echo '<div class="bdt-width-auto@m">' . esc_html__( 'Free', 'bdthemes-element-pack' ) . '</div>';
									echo '<div class="bdt-width-auto@m">' . esc_html__( 'Pro', 'bdthemes-element-pack' ) . '</div>';
									?>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><span
											bdt-tooltip="pos: top-left; title: <?php echo esc_html__( 'Lite have 35+ Widgets but Pro have 100+ core widgets', 'bdthemes-element-pack' ); ?>"><?php echo esc_html__( 'Core Widgets', 'bdthemes-element-pack' ); ?></span>
									</div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<?php echo '<div class="bdt-width-expand@m">' . esc_html__( 'Theme Compatibility', 'bdthemes-element-pack' ) . '</div>'; ?>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<?php echo '<div class="bdt-width-expand@m">' . esc_html__( 'Dynamic Content & Custom Fields Capabilities', 'bdthemes-element-pack' ) . '</div>'; ?>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<?php echo '<div class="bdt-width-expand@m">' . esc_html__( 'Proper Documentation', 'bdthemes-element-pack' ) . '</div>'; ?>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<?php echo '<div class="bdt-width-expand@m">' . esc_html__( 'Updates & Support', 'bdthemes-element-pack' ) . '</div>'; ?>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Ready Made Pages', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Ready Made Blocks', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Elementor Extended Widgets', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Asset Manager', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Live Copy or Paste', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Template Library (in Editor)', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<?php echo '<div class="bdt-width-expand@m">' . esc_html__( 'Header & Footer Builder', 'bdthemes-element-pack' ) . '</div>'; ?>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Rooten Theme Pro Features', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Priority Support', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'WooCommerce Widgets', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Ready Made Header & Footer', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Essential Shortcodes', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">
										<?php echo esc_html__( 'Context Menu', 'bdthemes-element-pack' ); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>

						</ul>

						<div class="ep-more-features bdt-card bdt-card-body bdt-card-default bdt-margin-medium-top bdt-padding-large">
							<ul class="bdt-list bdt-list-divider bdt-text-left bdt-margin-remove" style="font-size: 15px;">
								<li>
									<div class="bdt-grid bdt-grid-small">
										<?php
										echo '<div class="bdt-width-1-3@m"><span class="dashicons dashicons-heart"></span> ' . esc_html__( ' Incredibly Advanced', 'bdthemes-element-pack' ) . '</div>';
										echo '<div class="bdt-width-1-3@m"><span class="dashicons dashicons-heart"></span> ' . esc_html__( ' Refund or Cancel Anytime', 'bdthemes-element-pack' ) . '</div>';
										echo '<div class="bdt-width-1-3@m"><span class="dashicons dashicons-heart"></span> ' . esc_html__( ' Dynamic Content', 'bdthemes-element-pack' ) . '</div>';
										?>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span>
											<?php echo esc_html__( 'Super-Flexible Widgets', 'bdthemes-element-pack' ); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span
												class="dashicons dashicons-heart"></span><?php echo esc_html__( ' 24/7 Premium Support', 'bdthemes-element-pack' ); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span
												class="dashicons dashicons-heart"></span><?php echo esc_html__( ' Third Party Plugins', 'bdthemes-element-pack' ); ?>
										</div>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span
												class="dashicons dashicons-heart"></span><?php echo esc_html__( ' Special Discount!', 'bdthemes-element-pack' ); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span
												class="dashicons dashicons-heart"></span><?php echo esc_html__( ' Custom Field Integration', 'bdthemes-element-pack' ); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span
												class="dashicons dashicons-heart"></span><?php echo esc_html__( ' With Live Chat Support', 'bdthemes-element-pack' ); ?>
										</div>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span
												class="dashicons dashicons-heart"></span><?php echo esc_html__( ' Trusted Payment Methods', 'bdthemes-element-pack' ); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span
												class="dashicons dashicons-heart"></span><?php echo esc_html__( ' Interactive Effects', 'bdthemes-element-pack' ); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span
												class="dashicons dashicons-heart"></span><?php echo esc_html__( ' Video Tutorial', 'bdthemes-element-pack' ); ?>
										</div>
									</div>
								</li>
							</ul>

							<div class="ep-purchase-button bdt-margin-medium-top">
								<a href="https://elementpack.pro/pricing/" target="_blank"><?php echo esc_html__( 'Purchase Now', 'bdthemes-element-pack' ); ?></a>
							</div>

						</div>

					</div>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Display System Requirement
	 *
	 * @access public
	 * @return void
	 */

	function element_pack_system_requirement() {
		$php_version        = phpversion();
		$max_execution_time = ini_get( 'max_execution_time' );
		$memory_limit       = ini_get( 'memory_limit' );
		$post_limit         = ini_get( 'post_max_size' );
		$uploads            = wp_upload_dir();
		$upload_path        = $uploads['basedir'];
		$yes_icon           = '<span class="valid"><i class="dashicons-before dashicons-yes"></i></span>';
		$no_icon            = '<span class="invalid"><i class="dashicons-before dashicons-no-alt"></i></span>';

		$environment = Utils::get_environment_info();


		?>
		<ul class="check-system-status bdt-grid bdt-child-width-1-2@m bdt-grid-small ">
			<li>
				<div>

					<span class="label1"><?php echo esc_html__( 'PHP Version: ', 'bdthemes-element-pack' ); ?></span>

					<?php
					if ( version_compare( $php_version, '7.0.0', '<' ) ) {
						echo $no_icon;
						echo '<span class="label2" title="' . esc_html__( 'Min: 7.0 Recommended', 'bdthemes-element-pack' ) . '" bdt-tooltip>' . esc_html__( 'Currently: ', 'bdthemes-element-pack' ) . $php_version . '</span>';
					} else {
						echo $yes_icon;
						echo '<span class="label2">' . esc_html__( 'Currently: ', 'bdthemes-element-pack' ) . $php_version . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php echo esc_html__( 'Max execution time: ', 'bdthemes-element-pack' ); ?></span>

					<?php
					if ( $max_execution_time < '90' ) {
						echo $no_icon;
						echo '<span class="label2" title="' . esc_html__( 'Min: 90 Recommended', 'bdthemes-element-pack' ) . '" bdt-tooltip>' . esc_html__( 'Currently: ', 'bdthemes-element-pack' ) . $max_execution_time . '</span>';
					} else {
						echo $yes_icon;
						echo '<span class="label2">' . esc_html__( 'Currently: ', 'bdthemes-element-pack' ) . $max_execution_time . '</span>';
					}
					?>
				</div>
			</li>
			<li>
				<div>
					<span class="label1"><?php echo esc_html__( 'Memory Limit: ', 'bdthemes-element-pack' ); ?></span>

					<?php
					if ( intval( $memory_limit ) < '812' ) {
						echo $no_icon;
						echo '<span class="label2" title="' . esc_html__( 'Min: 812M Recommended', 'bdthemes-element-pack' ) . '" bdt-tooltip>' . esc_html__( 'Currently: ', 'bdthemes-element-pack' ) . $memory_limit . '</span>';
					} else {
						echo $yes_icon;
						echo '<span class="label2">' . esc_html__( 'Currently: ', 'bdthemes-element-pack' ) . $memory_limit . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php echo esc_html__( 'Max Post Limit: ', 'bdthemes-element-pack' ); ?></span>

					<?php
					if ( intval( $post_limit ) < '32' ) {
						echo $no_icon;
						echo '<span class="label2" title="' . esc_html__( 'Min: 32M Recommended', 'bdthemes-element-pack' ) . '" bdt-tooltip>' . esc_html__( 'Currently: ', 'bdthemes-element-pack' ) . $post_limit . '</span>';
					} else {
						echo $yes_icon;
						echo '<span class="label2">' . esc_html__( 'Currently: ', 'bdthemes-element-pack' ) . $post_limit . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php echo esc_html__( 'Uploads folder writable: ', 'bdthemes-element-pack' ); ?></span>

					<?php
					if ( ! is_writable( $upload_path ) ) {
						echo $no_icon;
					} else {
						echo $yes_icon;
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php echo esc_html__( 'MultiSite: ', 'bdthemes-element-pack' ); ?></span>

					<?php
					if ( $environment['wp_multisite'] ) {
						echo $yes_icon;
						echo '<span class="label2">' . esc_html__( 'MultiSite', 'bdthemes-element-pack' ) . '</span>';
					} else {
						echo $yes_icon;
						echo '<span class="label2">' . esc_html__( 'No MultiSite', 'bdthemes-element-pack' ) . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php echo esc_html__( 'GZip Enabled: ', 'bdthemes-element-pack' ); ?></span>

					<?php
					if ( $environment['gzip_enabled'] ) {
						echo $yes_icon;
					} else {
						echo $no_icon;
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php echo esc_html__( 'Debug Mode: ', 'bdthemes-element-pack' ); ?></span>
					<?php
					if ( $environment['wp_debug_mode'] ) {
						echo $no_icon;
						echo '<span class="label2">Currently Turned On</span>';
					} else {
						echo $yes_icon;
						echo '<span class="label2">Currently Turned Off</span>';
					}
					?>
				</div>
			</li>

		</ul>

		<div class="bdt-admin-alert">
			<?php
			echo '<strong>' . esc_html__( 'Note:', 'bdthemes-element-pack' ) . '</strong> ' . esc_html__( 'If you have multiple addons like', 'bdthemes-element-pack' ) . ' <b>Element Pack</b> ' . esc_html__( 'so you need some more requirement some cases so make sure you added more memory for others addon too.', 'bdthemes-element-pack' );
			?>
		</div>
		<?php
	}

	/**
	 * Display Plugin Page
	 *
	 * @access public
	 * @return void
	 */

	function plugin_page() {

		echo '<div class="wrap element-pack-dashboard">';
		echo '<h1>' . BDTEP_TITLE . ' ' . esc_html__( 'Settings', 'bdthemes-element-pack' ) . '</h1>';

		$this->settings_api->show_navigation();

		?>


		<div class="bdt-switcher bdt-tab-container bdt-container-xlarge">
			<div id="element_pack_welcome_page" class="ep-option-page group">
				<?php $this->element_pack_welcome(); ?>

				<?php if ( ! defined( 'BDTEP_WL' ) ) {
					$this->footer_info();
				} ?>
			</div>

			<?php
			$this->settings_api->show_forms();
			?>

			<div id="element_pack_get_pro" class="ep-option-page group">
				<?php $this->element_pack_get_pro(); ?>
			</div>


		</div>

		</div>

		<?php

		$this->script();

		?>

		<?php
	}






	/**
	 * Tabbable JavaScript codes & Initiate Color Picker
	 *
	 * This code uses localstorage for displaying active tabs
	 */
	function script() {
		?>
		<script>
			jQuery(document).ready(function () {
				jQuery('.ep-no-result').removeClass('bdt-animation-shake');
			});

			function filterSearch(e) {
				var parentID = '#' + jQuery(e).data('id');
				var search = jQuery(parentID).find('.bdt-search-input').val().toLowerCase();

				jQuery(".ep-options .ep-option-item").filter(function () {
					jQuery(this).toggle(jQuery(this).attr('data-widget-name').toLowerCase().indexOf(search) > -1)
				});

				if (!search) {
					jQuery(parentID).find('.bdt-search-input').attr('bdt-filter-control', "");
					jQuery(parentID).find('.ep-widget-all').trigger('click');
				} else {
					jQuery(parentID).find('.bdt-search-input').attr('bdt-filter-control', "filter: [data-widget-name*='" + search + "']");
					jQuery(parentID).find('.bdt-search-input').removeClass('bdt-active'); // Thanks to Bar-Rabbas
					jQuery(parentID).find('.bdt-search-input').trigger('click');
				}
			}

			jQuery('.ep-options-parent').each(function (e, item) {
				var eachItem = '#' + jQuery(item).attr('id');
				jQuery(eachItem).on("beforeFilter", function () {
					jQuery(eachItem).find('.ep-no-result').removeClass('bdt-animation-shake');
				});

				jQuery(eachItem).on("afterFilter", function () {

					var isElementVisible = false;
					var i = 0;

					while (!isElementVisible && i < jQuery(eachItem).find(".ep-option-item").length) {
						if (jQuery(eachItem).find(".ep-option-item").eq(i).is(":visible")) {
							isElementVisible = true;
						}
						i++;
					}

					if (isElementVisible === false) {
						jQuery(eachItem).find('.ep-no-result').addClass('bdt-animation-shake');
					}
				});


			});


			jQuery('.ep-widget-filter-nav li a').on('click', function (e) {
				jQuery(this).closest('.bdt-widget-filter-wrapper').find('.bdt-search-input').val('');
				jQuery(this).closest('.bdt-widget-filter-wrapper').find('.bdt-search-input').val('').attr('bdt-filter-control', '');
			});


			jQuery(document).ready(function ($) {
				'use strict';

				function hashHandler() {
					var $tab = jQuery('.element-pack-dashboard .bdt-tab');
					if (window.location.hash) {
						var hash = window.location.hash.substring(1);
						bdtUIkit.tab($tab).show(jQuery('#bdt-' + hash).data('tab-index'));
					}
				}

				jQuery(window).on('load', function () {
					hashHandler();
				});

				window.addEventListener("hashchange", hashHandler, true);

				jQuery('.toplevel_page_element_pack_options > ul > li > a ').on('click', function (event) {
					jQuery(this).parent().siblings().removeClass('current');
					jQuery(this).parent().addClass('current');
				});

				jQuery('#element_pack_active_modules_page a.ep-active-all-widget').click(function (e) {
					e.preventDefault();

					jQuery('#element_pack_active_modules_page .ep-widget-free .checkbox:visible').each(function () {
						jQuery(this).attr('checked', 'checked').prop("checked", true);
					});

					jQuery(this).addClass('bdt-active');
					jQuery('a.ep-deactive-all-widget').removeClass('bdt-active');
				});

				jQuery('#element_pack_active_modules_page a.ep-deactive-all-widget').click(function (e) {
					e.preventDefault();

					jQuery('#element_pack_active_modules_page .ep-widget-free .checkbox:visible').each(function () {
						jQuery(this).removeAttr('checked');
					});

					jQuery(this).addClass('bdt-active');
					jQuery('a.ep-active-all-widget').removeClass('bdt-active');
				});

				jQuery('#element_pack_third_party_widget_page a.ep-active-all-widget').click(function (e) {
					e.preventDefault();

					jQuery('#element_pack_third_party_widget_page .ep-widget-free .checkbox:visible').each(function () {
						jQuery(this).attr('checked', 'checked').prop("checked", true);
					});

					jQuery(this).addClass('bdt-active');
					jQuery('a.ep-deactive-all-widget').removeClass('bdt-active');
				});

				jQuery('#element_pack_third_party_widget_page a.ep-deactive-all-widget').click(function (e) {
					e.preventDefault();

					jQuery('#element_pack_third_party_widget_page .ep-widget-free .checkbox:visible').each(function () {
						jQuery(this).removeAttr('checked');
					});

					jQuery(this).addClass('bdt-active');
					jQuery('a.ep-active-all-widget').removeClass('bdt-active');
				});

				jQuery('#element_pack_elementor_extend_page a.ep-active-all-widget').click(function (e) {
					e.preventDefault();

					jQuery('#element_pack_elementor_extend_page .ep-widget-free .checkbox:visible').each(function () {
						jQuery(this).attr('checked', 'checked').prop("checked", true);
					});

					jQuery(this).addClass('bdt-active');
					jQuery('a.ep-deactive-all-widget').removeClass('bdt-active');
				});

				jQuery('#element_pack_elementor_extend_page a.ep-deactive-all-widget').click(function (e) {
					e.preventDefault();

					jQuery('#element_pack_elementor_extend_page .ep-widget-free .checkbox:visible').each(function () {
						jQuery(this).removeAttr('checked');
					});

					jQuery(this).addClass('bdt-active');
					jQuery('a.ep-active-all-widget').removeClass('bdt-active');
				});

				jQuery('#element_pack_active_modules_page, #element_pack_third_party_widget_page, #element_pack_elementor_extend_page, #element_pack_other_settings_page').find('.ep-pro-inactive .checkbox').each(function () {
					jQuery(this).removeAttr('checked');
					jQuery(this).attr("disabled", true);
				});

				jQuery('form.settings-save').submit(function (event) {
					event.preventDefault();

					bdtUIkit.notification({
						message: '<div bdt-spinner></div> <?php esc_html_e( 'Please wait, Saving settings...', 'bdthemes-element-pack' ) ?>',
						timeout: false
					});

					jQuery(this).ajaxSubmit({
						success: function () {
							bdtUIkit.notification.closeAll();
							bdtUIkit.notification({
								message: '<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Settings Saved Successfully.', 'bdthemes-element-pack' ) ?>',
								status: 'primary'
							});
						},
						error: function (data) {
							bdtUIkit.notification.closeAll();
							bdtUIkit.notification({
								message: '<span bdt-icon=\'icon: warning\'></span> <?php esc_html_e( 'Unknown error, make sure access is correct!', 'bdthemes-element-pack' ) ?>',
								status: 'warning'
							});
						}
					});

					return false;
				});

			});
		</script>
		<?php
	}

	/**
	 * Display Footer
	 *
	 * @access public
	 * @return void
	 */

	function footer_info() {
		?>

		<div class="element-pack-footer-info bdt-margin-medium-top">

			<div class="bdt-grid ">

				<div class="bdt-width-auto@s ep-setting-save-btn">



				</div>

				<div class="bdt-width-expand@s bdt-text-right">
					<p class="">
						Element Pack plugin made with love by <a target="_blank" href="https://bdthemes.com">BdThemes</a> Team.
						<br>All rights reserved by <a target="_blank" href="https://bdthemes.com">BdThemes.com</a>.
					</p>
				</div>
			</div>

		</div>

		<?php
	}

	/**
	 * Get all the pages
	 *
	 * @return array page names with key value pairs
	 */
	function get_pages() {
		$pages         = get_pages();
		$pages_options = [];
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$pages_options[ $page->ID ] = $page->post_title;
			}
		}

		return $pages_options;
	}
}

new ElementPack_Admin_Settings();
