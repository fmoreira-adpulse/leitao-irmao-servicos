<?php
namespace Brightplugins_COS;

class Email {

	public function __construct() {
		add_action( 'woocommerce_order_status_changed', [$this, 'status_changed'], 10, 3 );
		add_filter( 'woocommerce_email_classes', [$this, 'order_status_emails'] );
		add_filter( 'woocommerce_order_is_download_permitted', [$this, 'bvadd_status_to_download_permission'], 10, 2 );

		// This filter fixes the email preview for custom order status emails.
		add_filter( 'woocommerce_prepare_email_for_preview', [ $this, 'prepare_email_for_preview' ], 1 );
	}

	/**
	 * @param $data
	 * @param $order
	 * @return mixed
	 */
	public function bvadd_status_to_download_permission( $data, $order ) {
		$statusGrantDownloadArray = $this->wcbvGetStatusGrantDownloadable();
		if ( in_array( $order->get_status(), $statusGrantDownloadArray, true ) ) {
			return true;
		}
		return $data;
	}

	/**
	 * @param $order_id
	 * @param $old_status
	 * @param $new_status
	 */
	public function status_changed( $order_id, $old_status, $new_status ) {
		$statusGrantDownloadArray = $this->wcbvGetStatusGrantDownloadable();
		if ( in_array( $new_status, $statusGrantDownloadArray, true ) ) {
			wc_downloadable_product_permissions( $order_id, true );
		}

		$wc_emails = WC()->mailer()->get_emails();
		if ( isset( $wc_emails['bvos_custom_' . $new_status] ) ) {
			//$wc_emails['bvos_custom_' . $new_status]->trigger( $order_id );
			$defaultOptions        = get_option( 'wcbv_status_default', null );
			$is_wpml_compatible    = false;
			$wpml_default_language = ' ';
			$wpml_current_lang     = '';
			if ( $defaultOptions ) {
				if ( isset( $defaultOptions['enable_wpml'] ) && '1' == $defaultOptions['enable_wpml'] ) {
					if ( class_exists( 'sitepress' ) ) {
						global $sitepress;
						$wpml_default_language = $sitepress->get_default_language();
						$wpml_current_lang     = get_post_meta( $order_id, 'wpml_language', true );
						if ( empty( $wpml_current_lang ) ) {
							$wpml_current_lang = $wpml_default_language;
						}
						$is_wpml_compatible = true;
					}
				}
			}

			if ( $is_wpml_compatible ) {
				$type         = apply_filters( 'wpml_element_type', get_post_type( $order_id ) );
				$trid         = apply_filters( 'wpml_element_trid', false, $order_id, $type );
				$order_lang   = $wpml_current_lang;
				$translations = apply_filters( 'wpml_get_element_translations', array(), $trid, $type );
				foreach ( $translations as $lang => $translation ) {
					$order_lang = $translation->language_code;
					break;
				}

				if ( strlen( $order_lang ) > 2 ) {
					$order_lang     = str_replace( '-', '_', $order_lang );
					$order_lang_aux = explode( '_', $order_lang );
					if ( isset( $order_lang_aux[1] ) ) {
						$order_lang_aux[1] = strtoupper( $order_lang_aux[1] );
					}
					$order_lang = implode( '_', $order_lang_aux );
				}
				$locale = $order_lang;
				switch_to_locale( $locale );
				$wc_emails['bvos_custom_' . $new_status]->trigger( $order_id );
				restore_previous_locale();
			} else {
				$wc_emails['bvos_custom_' . $new_status]->trigger( $order_id );
			}

		}
	}

	/**
	 * @param $emails
	 * @return mixed
	 */
	public function order_status_emails( $emails ) {

		include_once BVOS_PLUGIN_DIR . '/src/emails/class-wcbv-order-status-email.php';

		$arg = array(
			'numberposts' => -1,
			'post_type'   => 'order_status',
			'meta_query'  => [[
				'key'     => '_enable_email',
				'compare' => '=',
				'value'   => '1',
			]],
		);
		$postStatusList = get_posts( $arg );

		foreach ( $postStatusList as $post ) {

			$status_index = $statusSlug = get_post_meta( $post->ID, 'status_slug', true );

			$emails['bvos_custom_' . $status_index] = new WCBV_Order_Status_Email(
				'bvos_custom_' . $status_index, array(
					'post_id'     => $post->ID,
					'title'       => $post->post_title,
					'description' => $post->post_excerpt,
					'type'        => get_post_meta( $post->ID, '_email_type', true ),
				)
			);

		}
		return $emails;

	}

	/**
	 * @return mixed
	 */
	public function wcbvGetStatusGrantDownloadable() {
		$arg = array(
			'numberposts' => -1,
			'post_type'   => 'order_status',
			'meta_query'  => [[
				'key'     => 'downloadable_grant',
				'compare' => '=',
				'value'   => '1',
			]],
		);
		$postStatusList = get_posts( $arg );
		$statuses       = array();
		foreach ( $postStatusList as $post ) {
			$slug       = get_post_meta( $post->ID, 'status_slug', true );
			$statuses[] = $slug;
		}

		return $statuses;
	}



	// Code to fix the email preview for custom order status emails.

	/**
	 * Prepares email object for preview by loading correct settings and properties.
	 *
	 * This method ensures that custom order status emails have their settings properly
	 * loaded from the database when being previewed in the WooCommerce admin.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Email $email The email object to prepare.
	 * @return \WC_Email The prepared email object with correct settings loaded.
	 */
	public function prepare_email_for_preview( $email ) {
		$section = $this->get_email_id_from_referer();

		// Early return if section is empty or invalid
		if ( empty( $section ) ) {
			return $email;
		}

		// Get all emails from the mailer
		$emails = WC()->mailer()->get_emails();
		
		// Verify that the email exists in the mailer
		if ( empty( $emails[ $section ] ) ) {
			return $email;
		}
		
		// Clone the correct email from the mailer
		$fixed = clone $emails[ $section ];
		
		// Load real settings from database
		$fixed->option_name = 'woocommerce_' . $fixed->id . '_settings';
		$fixed->settings    = get_option( $fixed->option_name, [] );
		
		// Rehydrate important properties with saved values
		$fixed->subject    = $fixed->get_option( 'subject', $fixed->subject );
		$fixed->heading    = $fixed->get_option( 'heading', $fixed->heading );
		$fixed->email_type = $fixed->get_option( 'email_type', $fixed->get_email_type() );
		
		// Preserve the object (order) if it exists
		if ( isset( $email->object ) ) {
			$fixed->object = $email->object;
		}
		
		// Refresh placeholders with current order data
		$this->refresh_email_placeholders( $fixed );

		return $fixed;
	}

	/**
	 * Retrieves the email section ID from various sources.
	 *
	 * This method attempts to get the email section ID from multiple sources in order of priority:
	 * 1. Current request $_GET parameter (most reliable)
	 * 2. Referer URL query string
	 * 3. Global $current_section variable (WooCommerce standard)
	 *
	 * @since 1.0.0
	 *
	 * @return string The sanitized email section ID, or empty string if not found.
	 */
	private function get_email_id_from_referer() {
		// Priority 1: Check current request $_GET parameter (most reliable)
		if ( isset( $_GET['section'] ) && is_string( $_GET['section'] ) ) {
			$section = wc_clean( wp_unslash( $_GET['section'] ) );
			if ( ! empty( $section ) ) {
				return $this->validate_email_section_id( $section );
			}
		}

		// Priority 2: Check referer URL
		$referer = wp_get_referer();
		if ( ! empty( $referer ) && is_string( $referer ) ) {
			$section = $this->extract_section_from_url( $referer );
			if ( ! empty( $section ) ) {
				return $this->validate_email_section_id( $section );
			}
		}

		// Priority 3: Check global $current_section (WooCommerce standard)
		global $current_section;
		if ( isset( $current_section ) && is_string( $current_section ) && ! empty( $current_section ) ) {
			return $this->validate_email_section_id( $current_section );
		}

		return '';
	}

	/**
	 * Extracts the section parameter from a given URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The URL to parse.
	 * @return string The section value, or empty string if not found.
	 */
	private function extract_section_from_url( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return '';
		}

		$parsed_url = wp_parse_url( $url );
		if ( false === $parsed_url || empty( $parsed_url['query'] ) ) {
			return '';
		}

		parse_str( $parsed_url['query'], $query_params );
		if ( ! isset( $query_params['section'] ) || ! is_string( $query_params['section'] ) ) {
			return '';
		}

		return wc_clean( wp_unslash( $query_params['section'] ) );
	}

	/**
	 * Validates and sanitizes an email section ID.
	 *
	 * Ensures the section ID is a valid string format and matches expected patterns
	 * for custom order status emails (bvos_custom_*).
	 *
	 * @since 1.0.0
	 *
	 * @param string $section_id The section ID to validate.
	 * @return string The validated and sanitized section ID, or empty string if invalid.
	 */
	private function validate_email_section_id( $section_id ) {
		if ( empty( $section_id ) || ! is_string( $section_id ) ) {
			return '';
		}

		// Sanitize using WordPress standards
		$sanitized = sanitize_text_field( $section_id );

		// Additional validation: ensure it's not empty after sanitization
		if ( empty( $sanitized ) ) {
			return '';
		}

		// Optional: Validate format if needed (e.g., must start with 'bvos_custom_')
		// This can be made configurable or removed if not needed
		// if ( 0 !== strpos( $sanitized, 'bvos_custom_' ) ) {
		//     return '';
		// }

		return $sanitized;
	}

	/**
	 * Refreshes placeholders for custom order status email preview.
	 *
	 * This method updates the existing placeholders with current order data,
	 * ensuring that placeholders like {order_status} reflect the correct status.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Email $email The email object to refresh placeholders for.
	 * @return void
	 */
	private function refresh_email_placeholders( $email ) {
		// Ensure placeholders array exists
		if ( ! is_array( $email->placeholders ) ) {
			$email->placeholders = array();
		}

		// Only refresh if we have an order object
		if ( ! isset( $email->object ) || ! is_a( $email->object, 'WC_Order' ) ) {
			return;
		}

		$order = $email->object;

		// Extract status slug from email ID (e.g., 'bvos_custom_processing' -> 'processing')
		$status_slug = str_replace( 'bvos_custom_', '', $email->id );
		
		// Set order status to match the email being previewed
		if ( ! empty( $status_slug ) ) {
			$order->set_status( $status_slug );
		}

		// Refresh placeholders with current order data
		if ( isset( $email->placeholders['{order_date}'] ) ) {
			$email->placeholders['{order_date}'] = wc_format_datetime( $order->get_date_created() );
		}
		
		if ( isset( $email->placeholders['{order_number}'] ) ) {
			$email->placeholders['{order_number}'] = $order->get_order_number();
		}
		
		if ( isset( $email->placeholders['{order_status}'] ) ) {
			$email->placeholders['{order_status}'] = wc_get_order_status_name( $order->get_status() );
		}
	}

}
