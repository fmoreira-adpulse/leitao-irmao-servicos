<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

/**
 * @class License
 */
class License {

	public $notices = array();
	protected $package_ids = [];

	/**
	 * Key used to store the licenses in wp_options table
	 */
	protected $option_name;

	/**
	 * Url of license page
	 * Provides full access to the link of the license page.
	 * Typically used if license is in custom page not set with add_menu_page()
	 * @var string
	 */
	protected $page_url;

	public function run() {
		if ( !empty( $this->get_package_ids() ) ) {
			add_action( 'init', array( $this, 'in_plugin_update_messages' ), 70 );
			add_filter( 'plugins_api', array( $this, 'get_plugin_information' ), 10, 3 );
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
			add_action( 'wp_ajax_nmerimedia_process_ajax_verify_license', array( $this, 'process_ajax_verify_license' ) );
			add_action( 'admin_notices', array( $this, 'show_notices' ) );
		}
	}

	public function add_submenu_page() {
		add_submenu_page(
			'nmerimedia',
			__( 'License', 'nm-gift-registry' ),
			__( 'License', 'nm-gift-registry' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'page_content' )
		);
	}

	public function set_option_name( $option_name ) {
		$this->option_name = $option_name;
	}

	/**
	 * Set the id of the package to be updated.
	 * The id should be the basename of the plugin
	 * @param string $package_id Package id
	 */
	public function set_package_id( $package_id ) {
		$this->package_ids[] = $package_id;
	}

	public function set_page_url( $url ) {
		$this->page_url = $url;
	}

	public function get_option_name() {
		return $this->option_name;
	}

	/**
	 * Alias of set_package_id()
	 */
	public function add_package_id( $package_id ) {
		$this->set_package_id( $package_id );
	}

	public function get_page_url() {
		return $this->page_url;
	}

	public function get_page_heading() {
		return;
	}

	public function page_content() {
		?>
		<div class="wrap nmerimedia_licenses">
			<?php if ( $this->get_page_heading() ): ?>
				<h1><?php echo wp_kses( $this->get_page_heading(), nmgr_allowed_post_tags() ); ?></h1>
			<?php endif; ?>
			<table class="wp-list-table widefat striped table-view-list nmerimedia-verify-licenses">
				<thead>
					<tr>
						<th><?php echo esc_html( __( 'Name', 'nm-gift-registry' ) ); ?></th>
						<th><?php echo esc_html( __( 'Current version', 'nm-gift-registry' ) ); ?></th>
						<th><?php echo esc_html( __( 'License key', 'nm-gift-registry' ) ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $this->get_package_ids() as $file ) {
						$plugin = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $file );
						?>
						<tr>
							<td><?php echo esc_html( $plugin[ 'Name' ] ); ?></td>
							<td><?php echo esc_html( $plugin[ 'Version' ] ); ?></td>
							<td>
								<input type="password" class="license_key"
											 placeholder="<?php echo esc_html( __( 'Enter license key', 'nm-gift-registry' ) ); ?>"
											 name="nmerimedia_license_key"
											 value="<?php echo esc_attr( $this->get_license_key( $file ) ); ?>">
								<input type="hidden" class="nmerimedia-package-id" name="nmerimedia_package_id" value="<?php echo esc_attr( $file ); ?>">
							</td>
							<td>
								<button class="button-primary button nmerimedia-verify-license"><?php echo esc_html( __( 'Activate', 'nm-gift-registry' ) ); ?></button>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		$this->ajax_verify_license();
	}

	/**
	 * Get the ids of all the packages that should be updated
	 * The id should be the basename of the plugin
	 */
	public function get_package_ids() {
		return apply_filters( $this->option_name . '_' . 'package_ids', $this->package_ids );
	}

	protected function get_license_keys() {
		return get_option( $this->option_name, array() );
	}

	/**
	 *
	 * @param type $package_id
	 * @return type
	 */
	protected function get_license_key( $package_id ) {
		$licenses = $this->get_license_keys();
		return $licenses[ $package_id ] ?? '';
	}

	public function in_plugin_update_messages() {
		foreach ( $this->get_package_ids() as $package_id ) {
			add_action( 'in_plugin_update_message-' . $package_id, array( $this, 'show_no_auto_update_notice' ), 10, 2 );
			add_action( 'in_plugin_update_message-' . $package_id, array( $this, 'show_upgrade_notice' ), 10, 2 );
		}
	}

	public function show_no_auto_update_notice( $args, $response ) {
		global $pagenow;

		if ( 'plugins.php' === $pagenow && (!isset( $response->package ) || empty( $response->package )) ) {
			echo '<br>';
			printf(
				wp_kses_post(
					/* translators: 1: Lcenses page url, 2: Buy license url */
					__( 'You cannot update the plugin as you do not have a verified license. <a href="%1$s">Please enter your license key here</a> or <a href="%2$s" target="__blank">buy one now</a>.', 'nm-gift-registry' )
				),
				esc_url( $this->get_page_url() ),
				'https://nmerimedia.com/product-category/plugins/'
			);
		}
	}

	public function show_upgrade_notice( $args, $response ) {
		global $pagenow;

		if ( 'plugins.php' === $pagenow && isset( $response->upgrade_notice ) && !empty( $response->upgrade_notice ) ) {
			printf( ''
				. "<div class='nm-update-notice' style='padding:10px 0;border-top:1px solid #ffb900;'>%s</div>"
				. "<p style='display:none;'>",
				wp_kses_post( $response->upgrade_notice )
			);
		}
	}

	public function get_api_url( $path = '' ) {
		return trailingslashit( apply_filters( $this->option_name . '_api_url', 'https://nmerimedia.com/' ) ) . ltrim( $path, '/' );
	}

	public function get_api_ajax_url() {
		return $this->get_api_url( 'wp-admin/admin-ajax.php' );
	}

	public function get_api_nonce() {
		return wp_create_nonce( 'nmerimedia-api-nonce' );
	}

	/**
	 * Create a special nonce for api requests to plugin server
	 * The nonce expires in 10 minutes after creation
	 */
	public function create_api_key() {
		$nonce = random_bytes( 32 );

		$expires = new \DateTime( 'now' );
		$expires->add( new \DateInterval( 'PT10M' ) );

		$payload = json_encode( [
			'nonce' => base64_encode( $nonce ),
			'expires' => $expires->format( 'Y-m-d\TH:i:s' )
			] );

		return base64_encode( hash_hmac( 'sha256', $payload, 'nm-license-api', true ) . $payload );
	}

	/**
	 * Default package information sent with each api request to server
	 *
	 * @param string $package_id The plugin basename e.g 'nm-gift-registry/nm-gift-registry.php'
	 * @return array
	 */
	public function get_default_package_api_params( $package_id ) {
		if ( !function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $package_id;

		if ( !file_exists( $plugin_path ) ) {
			return false;
		}

		$plugin_data = get_plugin_data( $plugin_path );

		return array(
			'package_id' => $package_id,
			'package_name' => $plugin_data[ 'Name' ],
			'package_version' => $plugin_data[ 'Version' ],
			'domain' => get_bloginfo( 'url' ),
			'license_key' => $this->get_license_key( $package_id ),
		);
	}

	public function get_plugin_information( $false, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $false;
		}

		$package_ids = $this->get_package_ids();
		$package_id = '';

		foreach ( $package_ids as $id ) {
			if ( pathinfo( $id, PATHINFO_FILENAME ) === $args->slug ) {
				$package_id = $id;
			}
		}

		// Exit if the plugins api is not about a plugin registered with nmerimedia
		if ( !$package_id ) {
			return $false;
		}

		$packages_data = get_transient( $this->option_name );
		$package_data = isset( $packages_data[ $package_id ] ) ? $packages_data[ $package_id ] : array();
		$active_package_data = $this->get_default_package_api_params( $package_id );

		if ( empty( $package_data ) ) {
			// check for update in our plugin server
			$response = $this->request( 'package_information', array( 'packages' => array( $package_id => $active_package_data ) ) );

			if ( isset( $response->package_information, $response->package_information->$package_id ) &&
				!$this->is_error( $response->package_information->$package_id ) ) {
				$package_data = $this->set_package_information_transient( $package_id, $response->package_information->$package_id );
			}
		}

		$package_data_obj = ( object ) $package_data;

		if ( false !== $package_data &&
			!$this->is_error( $package_data ) &&
			isset( $package_data_obj->new_version ) &&
			version_compare( $active_package_data[ 'package_version' ], $package_data_obj->new_version, '<' ) ) {
			foreach ( $package_data_obj as $key => $value ) {
				if ( is_object( $value ) ) {
					$package_data_obj->{$key} = ( array ) $value;
				}
			}

			return $package_data_obj;
		}
		return $false;
	}

	/**
	 * Main entry point to check for updates for all nmerimedia plugins
	 * @param type $transient
	 * @return type
	 */
	public function check_for_update( $transient ) {
		// exit if we don't have the list of plugins and their versions checked for update
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$packages_information = $this->get_packages_information_for_update();

		if ( !empty( $packages_information ) ) {
			foreach ( $packages_information as $package_id => $package_data ) {
				$transient->response[ $package_id ] = $package_data;
			}
		}

		return $transient;
	}

	/**
	 * Make a request to our plugin server
	 *
	 * @param string $endpoint Endpoint to send request to
	 * @param string|array $package_ids Package id or array of package ids to make request for
	 * @param array $args Arguments to send with the request. $args must be an array of arrays in which
	 * the key of each array represents the package_id and the value is an array representing the parameters to send
	 * for that package id to the endpoint.
	 * @return object|false Json result of the request or false if no results were returned
	 */
	private function request( $endpoint = '', $args = array() ) {
		$api_url = add_query_arg(
			array(
				'nm-license-api' => $endpoint,
				'api_key' => ''
			),
			$this->get_api_url()
		);

		$response = wp_remote_get( $api_url, array(
			'body' => $args,
			'sslverify' => false,
			'timeout' => 10,
			) );

		if ( is_wp_error( $response ) ) {
			return ( object ) array(
					'message' => $response->get_error_message(),
					'status' => 'error'
			);
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return ( object ) array(
					'message' => wp_remote_retrieve_response_message( $response ),
					'status' => 'error'
			);
		}

		// Returns object
		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Return a wordpress admin notice to show
	 * @param string $msg The notice message to return
	 * @param string $notice_type The notice type. Default success.
	 * @param string $package_id The package id the notice belongs to. Default none;
	 */
	public function get_notice( $msg, $notice_type = 'success', $package_id = '' ) {
		$params = $package_id ? $this->get_default_package_api_params( $package_id ) : array();

		if ( 'error' === $notice_type ) {
			$notice = '<div class="error notice is-dismissible"><p>';
			$notice .= $params ? '<strong>' . sanitize_text_field( $params[ 'package_name' ] ) . ':</strong> ' . $msg : $msg;
			$notice .= '</p></div>';
		} else {
			$notice = '<div class="notice notice-success is-dismissible"><p>';
			$notice .= $params ? '<strong>' . sanitize_text_field( $params[ 'package_name' ] ) . ':</strong> ' . $msg : $msg;
			$notice .= '</p></div>';
		}
		return $notice;
	}

	/**
	 * Save the package information for the plugin as a transient for easy retrieval
	 *
	 * @param array|object $package_data
	 * @return boolean|object The saved update data or false if no update data was saved.
	 */
	public function set_package_information_transient( $package_id, $package_data ) {
		$package_data_obj = ( object ) $package_data;

		if ( isset( $package_data_obj->request_params ) ) {
			unset( $package_data_obj->request_params );
		}

		$transientee = get_transient( $this->option_name );
		$transient = $transientee ? ( array ) $transientee : array();
		$transient[ $package_id ] = $package_data_obj;
		set_transient( $this->option_name, $transient, 12 * HOUR_IN_SECONDS );
		return $package_data_obj;
	}

	/**
	 * Check if a result returned from an api request is an error result
	 * @param type $result
	 * @return boolean
	 */
	public function is_error( $result ) {
		$res = ( object ) $result;
		return isset( $res->status ) && 'error' === $res->status;
	}

	/**
	 * Get the information for all packages that is used to show that an update is available
	 */
	public function get_packages_information_for_update() {
		$packages = array();
		foreach ( $this->get_package_ids() as $package_id ) {
			$packages[ $package_id ] = $this->get_default_package_api_params( $package_id );
		}

		$get_packages_information = false;
		$packages_information = get_transient( $this->option_name );

		if ( false == $packages_information ) {
			foreach ( array_keys( $packages ) as $package_id ) {
				if ( !isset( $packages_information[ $package_id ] ) ) {
					$get_packages_information = true;
					break;
				}
			}
		}

		if ( false == $packages_information || $get_packages_information ) {
			// check for update in our plugin server
			$response = $this->request( 'package_information', array( 'packages' => $packages ) );

			if ( isset( $response->package_information ) ) {
				$packages_information = array();
				foreach ( $response->package_information as $package_id => $params ) {
					if ( $this->is_error( $params ) ) {
						unset( $response->package_information->$package_id );
					} else {
						$packages_information[ $package_id ] = $this->set_package_information_transient( $package_id, $params );
					}
				}
			}
		}

		if ( false != $packages_information ) {
			$packages_update_info = array();
			foreach ( $packages_information as $package_id => $package_data ) {
				$default_data = $this->get_default_package_api_params( $package_id );
				if ( isset( $package_data->new_version ) &&
					version_compare( $default_data[ 'package_version' ], $package_data->new_version, '<' ) ) {
					$update_data = new \stdClass();

					$response_fields = array(
						'slug',
						'plugin',
						'new_version',
						'url',
						'package',
						'tested',
						'requires_php',
						'upgrade_notice',
					);

					foreach ( $response_fields as $field ) {
						if ( isset( $package_data->{$field} ) ) {
							$update_data->{$field} = $package_data->{$field};
						}
					}

					$packages_update_info[ $package_id ] = $update_data;
				}
			}
			return $packages_update_info;
		}
	}

	public function http_verify_license() {
		$license_key = $_POST[ 'nmerimedia_license_key' ] ?? '';
		$package_id = $_POST[ 'nmerimedia_package_id' ] ?? '';

		if ( !$license_key || !$package_id ) {
			return;
		}

		$params = array_merge( $this->get_default_package_api_params( $package_id ), array(
			'license_key' => $license_key
			) );

		$response = $this->request( 'verify_license', array( 'packages' => array( $package_id => $params ) ) );

		$this->process_verify_license_response( $response );
	}

	public function ajax_verify_license() {
		$packages = array();
		foreach ( $this->get_package_ids() as $package_id ) {
			$packages[ $package_id ] = $this->get_default_package_api_params( $package_id );
		}
		?>
		<script>
			jQuery(function ($) {
				$('.nmerimedia-verify-license').click(function (e) {
					e.preventDefault();

					var row = $(this).closest('tr'),
							license = row.find('input.license_key').val();

					if (!license) {
						return;
					}

					var package_id = row.find('input.nmerimedia-package-id').val();
					var packages = JSON.parse('<?php echo wp_kses( json_encode( $packages ), [] ); ?>');

					if (!packages[package_id]) {
						return;
					}

					for (var key in packages) {
						if (key !== package_id) {
							delete packages[key];
						}
					}

					packages[package_id]['license_key'] = license;

					var table = $('table.nmerimedia-verify-licenses');
					var container = $('.wrap.nmerimedia_licenses');
					var apiUrl = '<?php echo esc_url( $this->get_api_ajax_url() ); ?>';
					var adminAjaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

					container.find('div.notice').remove();
					table.fadeTo('400', '0.3');

					var apiData = {
						action: 'nm_license_api',
						api_action: 'verify_license',
						packages: packages
					};

					$.get(apiUrl, apiData, function (apiResponse) {
						var data = {
							action: 'nmerimedia_process_ajax_verify_license',
							security: '<?php echo esc_attr( $this->get_api_nonce() ); ?>',
							api_response: JSON.stringify(apiResponse) // stringify to preserve response object
						}

						$.post(adminAjaxUrl, data, function (response) {
							table.fadeTo('300', '1');

							if (response.notices && response.notices.length) {
								response.notices.forEach(function (notice) {
									table.before(notice);
								});
							}
						});
					});

				});
			});
		</script>
		<?php
	}

	/**
	 * When the verify license action was carried out via ajax, process the returned response here
	 */
	public function process_ajax_verify_license() {
		check_ajax_referer( 'nmerimedia-api-nonce', 'security' );

		/**
		 * json decode restores original object format of api response
		 */
		$response = json_decode( sanitize_text_field( wp_unslash( $_POST[ 'api_response' ] ?? '' ) ) );

		/**
		 * This function may produce notices
		 */
		$this->process_verify_license_response( $response );

		// Send any notices to js script if available
		$notices = $this->notices;
		$this->notices = array();
		wp_send_json( array( 'notices' => $notices ) );
	}

	/**
	 * Process the api response from the verify_license action
	 * (This is the main code that processes the response. It is isolated in order to allow it to be used
	 * by both http and ajax license verification requests).
	 */
	public function process_verify_license_response( $response ) {
		// If the request returned an error (e.g. 'could not resolve host'), we would catch it here
		if ( isset( $response->status ) && 'error' === $response->status && isset( $response->message ) ) {
			$this->notices[] = $this->get_notice( $response->message, $response->status );
			return;
		}

		if ( !isset( $response->license_verification ) ) {
			$this->notices[] = $this->get_notice( __( 'No license verification results returned', 'nm-gift-registry' ), 'error' );
			return;
		}

		foreach ( $response->license_verification as $package_id => $package_response ) {
			if ( isset( $package_response->status ) && 'success' === $package_response->status ) {
				$license_keys = $this->get_license_keys();
				$license_keys[ $package_id ] = $package_response->license_key;
				update_option( $this->option_name, $license_keys );

				if ( isset( $package_response->package_update_data ) ) {
					$this->set_package_information_transient( $package_id, $package_response->package_update_data );
				}

				$update_plugins = get_site_transient( 'update_plugins' );

				if ( isset( $update_plugins->response ) ) {
					foreach ( $update_plugins->response as $plugin_slug => $plugin_details ) {
						if ( $package_id === $plugin_slug ) {
							if ( (!isset( $plugin_details->package ) || empty( $plugin_details->package )) &&
								isset( $package_response->package_update_data, $package_response->package_update_data->package ) ) {
								$update_plugins->response[ $plugin_slug ]->package = $package_response->package_update_data->package;
								set_site_transient( 'update_plugins', $update_plugins );
							}
							break;
						}
					}
				}
			}

			if ( isset( $package_response->message ) && $package_response->message ) {
				$this->notices[] = $this->get_notice( $package_response->message, $package_response->status, $package_id );
			}
		}
	}

	/**
	 * Show admin notices on page load
	 */
	public function show_notices() {
		if ( !empty( $this->notices ) ) {
			foreach ( $this->notices as $notice ) {
				echo wp_kses_post( $notice );
			}
		}
		$this->notices = array();
	}

}
