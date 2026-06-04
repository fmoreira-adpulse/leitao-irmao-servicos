<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

if ( !class_exists( '\WC_Email' ) ) {
	include_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
}

class Email extends \WC_Email {

	/**
	 * The id of the wishlist we are currently dealing with
	 *
	 * @var int
	 */
	public $wishlist_id = 0;

	/**
	 * The name of the recipient of the email
	 *
	 * @var string
	 */
	public $recipient_name;

	/**
	 * Variables to send to both html and plain templates
	 *
	 * @var array
	 */
	public $template_args = array();

	/**
	 * Prepare an email to be sent using the Woocommerce email system
	 *
	 * @param string $section_id Unique id representing the type of email to send
	 * - This is also the section id of an email settings section which holds the options for
	 * - configuring the email in the settings screen (@see NMGR_Admin_Settings->emails_tab_sections)
	 *
	 * @param int $wishlist_id The id of the wishlist we are sending the email regarding
	 */
	public function __construct( $section_id = '', $wishlist_id = 0 ) {

		if ( is_numeric( $wishlist_id ) && $wishlist_id > 0 ) {
			$this->wishlist_id = $wishlist_id;
		}

		$wishlist = $this->get_wishlist();

		if ( $section_id ) {
			$this->id = $section_id;

			$email_sections = nmgr()->gift_registry_settings()->get_tab_sections( 'emails' );
			$this_section = isset( $email_sections[ $section_id ] ) ? $email_sections[ $section_id ] : array();

			$heading_args = nmgr()->gift_registry_settings()->get_email_section_field( 'heading', $section_id );
			$subject_args = nmgr()->gift_registry_settings()->get_email_section_field( 'subject', $section_id );

			/**
			 * Set default properties for the email class
			 * These properties can be overridden by re-declaring them after instantiating the class
			 * Properties tagged 'for wc' are needed if we are showing the emails in wc emails settings
			 * We're not calling the parent constructor so we also replicate some of its actions here
			 */
			$this->title = $this_section[ 'title' ] ?? $this->title; // For wc
			$this->description = $this_section[ 'description' ] ?? $this->description; // For wc
			$this->template_base = nmgr()->path . '/includes/Sub/templates/'; // For wc
			$this->email_type = $this->get_option( 'email_type' );
			$this->enabled = $this->get_option( 'enabled' );
			$this->customer_email = $this_section[ 'is_customer_email' ] ?? false;
			$this->heading = isset( $heading_args[ 'placeholder' ] ) ? $heading_args[ 'placeholder' ] : '';
			$this->subject = isset( $subject_args[ 'placeholder' ] ) ? $subject_args[ 'placeholder' ] : '';

			$basename = str_replace( array( 'email_', '_' ), array( '', '-' ), $section_id ) . '.php';

			$this->template_html = $this->get_template_type_path( $basename, 'html' );
			$this->template_plain = $this->get_template_type_path( $basename, 'plain' );
			$this->placeholders[ '{site_title}' ] = $this->get_blogname();
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
			$this->template_args[ 'email' ] = $this;

			if ( $wishlist ) {
				$this->placeholders[ '{wishlist_title}' ] = $wishlist->get_title();
				$this->template_args[ 'wishlist' ] = $wishlist;
			}

			if ( $wishlist && $this->is_customer_email() ) {
				$user = $wishlist->get_user();
				$this->recipient = $wishlist->get_email();
				$this->recipient_name = $wishlist->get_full_name() ? $wishlist->get_full_name() : (isset( $user->user_login ) ? $user->user_login : '');
			}

			add_action( 'phpmailer_init', array( $this, 'handle_multipart' ) );
		}
	}

	/**
	 * Sets class properties
	 *
	 * This function is mainly used outside of the class to set protected properties
	 * that cannot be set outside the class such as 'placeholder' and 'customer_email
	 *
	 * @param type $props
	 */
	public function set_props( $props ) {
		foreach ( $props as $key => $value ) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Send the email
	 */
	public function trigger() {
		$this->setup_locale();

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	// Only for development. Do not use.
	public function preview() {
		if ( $this->is_enabled() && $this->get_recipient() ) {
			WC()->mailer();
			$message = $this->style_inline( $this->get_content() );
			echo $message;
			exit;
		}
	}

	/**
	 * Get the wishlist we are currently dealing with
	 *
	 * @return \NMGR_Wishlist
	 */
	public function get_wishlist() {
		return nmgr_get_wishlist( $this->wishlist_id );
	}

	/**
	 * Get an option value for sending an email
	 *
	 * We are overriding the parent class method so that we can get the option from our
	 * own settings and avoid the woocommerce filter applied in the parent class method
	 *
	 * @overrides parent::get_option()
	 * @param string $key option key
	 * @return string
	 */
	public function get_option( $key, $default_value = null ) {
		$wishlist = $this->get_wishlist();
		$type = $wishlist ? $wishlist->get_type() : 'gift-registry';
		$db_value = nmgr_get_type_option( $type, $this->id . "_$key" );
		return $db_value ? $db_value : $default_value;
	}

	/**
	 * Returns validated email address recipients for the email
	 *
	 * We are overriding the parent class method so that we can avoid the woocommerce filter
	 * applied there and apply our own
	 *
	 * @overrides parent::get_recipient()
	 * @return string
	 */
	public function get_recipient() {
		$recipient = apply_filters( "nmgr_{$this->id}_recipient", $this->recipient );
		$recipients = array_map( 'trim', explode( ',', $recipient ) );
		$recipients = array_filter( $recipients, 'is_email' );
		return implode( ', ', $recipients );
	}

	/**
	 * Get the name of the recipent of the email
	 *
	 * (Generally used for customer emails only)
	 *
	 * @return string
	 */
	public function get_recipient_name() {
		return $this->recipient_name;
	}

	/**
	 * Get the subject of the email
	 *
	 * We are overriding the parent class method so that we can avoid the woocommerce filter
	 * applied there and apply our own
	 *
	 * @overrides parent::get_subject()
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( "nmgr_{$this->id}_subject", $this->format_string( $this->get_option( 'subject', $this->get_default_subject() ) ) );
	}

	/**
	 * Get the heading of the email
	 *
	 * We are overriding the parent class method so that we can avoid the woocommerce filter
	 * applied there and apply our own
	 *
	 * @overrides parent::get_heading()
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( "nmgr_{$this->id}_heading", $this->format_string( $this->get_option( 'heading', $this->get_default_heading() ) ) );
	}

	/**
	 * Get the template for sending emails in html
	 *
	 * @return string
	 */
	public function get_content_html() {
		return $this->get_content_template( $this->template_html );
	}

	/**
	 * Get the template for sending plain emails
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return $this->get_content_template( $this->template_plain );
	}

	public function get_content_template( $name, $with_header_and_footer = true ) {
		$overridden_file = nmgr_overridden( $name );
		if ( $overridden_file ) {
			nmgr_overridden_notice( $overridden_file, '4.8.0' );
			$template = nmgr_get_template( $name, $this->template_args );
		} else {
			ob_start();
			$email_type = $this->get_email_type();

			if ( $with_header_and_footer ) {
				'html' === $email_type ? do_action( 'woocommerce_email_header', $this->get_heading(), $this ) : '';
				echo 'plain' === $email_type ? '= ' . esc_html( $this->get_heading() ) . " =\n\n" : '';
			}

			echo apply_filters( 'nmgr_email_template', $this->get_template_part( $name ), $this );

			if ( $with_header_and_footer ) {
				'html' === $email_type ? do_action( 'woocommerce_email_footer', $this ) : '';
				echo 'plain' === $email_type ?
					esc_html( apply_filters( 'woocommerce_email_footer_text',
							get_option( 'woocommerce_email_footer_text' ) ) ) : '';
			}

			$template = ob_get_clean();
		}

		return $template;
	}

	public function get_template_part( $name ) {
		ob_start();
		extract( $this->template_args );
		include $this->template_base . $name;
		return ob_get_clean();
	}

	/**
	 * Determine whether the email is enabled and can be sent
	 *
	 * We are overriding the parent class method so that we can avoid the woocommerce filter
	 * applied there and apply our own, as well as to correctly detect the enabled status based on
	 * the saved checkbox value.
	 *
	 * @overrides parent::is_enabled()
	 * @return boolean
	 */
	public function is_enabled() {
		return apply_filters( "nmgr_{$this->id}_enabled", ( bool ) $this->enabled );
	}

	public function get_template_type_path( $basename, $template_type = null ) {
		$type = $template_type ? $template_type : $this->get_email_type();
		return 'plain' === $type ? ('emails/plain/' . $basename) : ('emails/' . $basename);
	}

}
