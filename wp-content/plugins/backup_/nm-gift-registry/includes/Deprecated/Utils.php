<?php

namespace NMGR\Deprecated;

defined( 'ABSPATH' ) || exit;

/**
 * Main utils class
 * @class Utils
 */
class Utils {

	/**
	 * Get the allowed post tags
	 * This returns wordpress allowed html tags in post and the plugin's allowed svg tags
	 *
	 * @return array
	 */
	public static function allowed_post_tags() {
		_deprecated_function( __METHOD__, '4.10', 'nmgr_allowed_post_tags' );
		return nmgr_allowed_post_tags();
	}

	/**
	 * Return an array of html attributes as a string to be inserted directly into the html element
	 * @param array $attributes Attributes to flatten
	 * @return string
	 */
	public static function format_attributes( $attributes ) {
		_deprecated_function( __METHOD__, '4.10', 'nmgr_format_attributes' );
		return nmgr_utils_format_attributes( $attributes );
	}

	/**
	 * Return an array of html attributes as a string to be inserted directly into the html element
	 * (Alias of format_attributes())
	 * @param array $attributes Attributes to flatten
	 * @return string
	 */
	public static function flatten_attributes( $attributes ) {
		_deprecated_function( __METHOD__, '4.10', 'nmgr_format_attributes' );
		return nmgr_utils_format_attributes( $attributes );
	}

}
