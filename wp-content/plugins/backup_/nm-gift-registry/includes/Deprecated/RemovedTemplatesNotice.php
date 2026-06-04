<?php

namespace NMGR\Deprecated;

defined( 'ABSPATH' ) || exit;

class RemovedTemplatesNotice {

	public static function run() {
		add_action( 'admin_notices', array( __CLASS__, 'output_notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'save_dismiss_notice_option' ) );
		add_filter( 'removable_query_args', [ __CLASS__, 'remove_query_arg' ] );
	}

	public static function remove_query_arg( $args ) {
		$args[] = 'nmgr_drtn';
		return $args;
	}

	public static function save_dismiss_notice_option() {
		if ( !empty( $_GET[ 'nmgr_drtn' ] ) ) {
			update_option( 'nmgr_dismiss_removed_templates_notice', nmgr()->version );
		}
	}

	public static function output_notice() {
		$dismiss_version = get_option( 'nmgr_dismiss_removed_templates_notice' );

		// if dismiss version is the same as current version, return
		if ( $dismiss_version && version_compare( $dismiss_version, nmgr()->version, '=' ) ) {
			return;
		}

		$removed_files = [];
		$theme_path = apply_filters( 'nmgr_theme_path', trailingslashit( nmgr()->slug ) );

		if ( file_exists( get_stylesheet_directory() . '/' . $theme_path ) ) {
			$folder = get_stylesheet_directory() . '/' . $theme_path;
		} elseif ( file_exists( get_template_directory() . '/' . $theme_path ) ) {
			$folder = get_template_directory() . '/' . $theme_path;
		}

		if ( isset( $folder ) ) {
			$scan_files = \WC_Admin_Status::scan_template_files( $folder );
			foreach ( $scan_files as $file ) {
				if ( !file_exists( nmgr()->template_path() . $file ) ) {
					$removed_files[] = $file;
				}
			}
		}

		if ( !empty( $removed_files ) ) {
			$msg1 = 'The following template files in your theme have been removed from the core plugin. You can no longer override them in your theme folder.';
			$msg2 = 'Please search the code for alternative implementation or contact support.';
			$url = add_query_arg( 'nmgr_drtn', 1 );
			$confirm = 'Are you sure?';

			echo '<div class="notice-info notice is-dismissible">';
			echo '<h3>' . nmgr()->name . '</h3>';
			echo '<h4>' . $msg1 . '</h4>';
			echo '<h4>' . $msg2 . '</h4>';
			echo '<ul>';
			foreach ( $removed_files as $removed ) {
				echo '<li>' . $folder . $removed . '</li>';
			}
			echo '</ul>';
			echo '<p><a href="' . $url . '" class="button" onclick="return confirm(\'' . $confirm . '\')">Dismiss Permanently</a></p>';
			echo '</div>';
		}
	}

}
