<?php

use NMGR\Lib\Archive;

/**
 * Checks if the post is for an nm gift registry post type
 *
 * @param int | WP_Post | null $post The post id or object
 * @deprecated since version 4.10
 * @return boolean True | False
 */
function is_nmgr_post( $post = null ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	return 'nm_gift_registry' === get_post_type( $post );
}

/**
 * Check if we are on the woocommerce account page endpoint for NM Gift Registry
 *
 * @return boolean
 */
function is_nmgr_account() {
	_deprecated_function( __FUNCTION__, '4.0.0', 'is_nmgr_account_section' );
	return is_nmgr_account_section();
}

/**
 * Check if we are in an NM Gift Registry account tab
 *
 * @return boolean
 */
function is_nmgr_account_tab() {
	_deprecated_function( __FUNCTION__, '4.0.0' );
	return apply_filters( 'is_nmgr_account_tab', false );
}

/**
 * Check if we are in an NM Gift Registry modal window
 *
 * @return boolean
 */
function is_nmgr_modal() {
	_deprecated_function( __FUNCTION__, '4.4.0' );
	return apply_filters( 'is_nmgr_modal', (nmgr_get_global_var()->is_modal ?? false ) );
}

function nmgr_get_global_var() {
	_deprecated_function( __FUNCTION__, '4.10' );
	$GLOBALS[ 'nmgr' ] = $GLOBALS[ 'nmgr' ] ?? new \stdClass();
	return $GLOBALS[ 'nmgr' ];
}

/**
 * Check if we are on a page which uses nmgr templates
 * @deprecated since verion 4.3.1
 * @return boolean
 */
function is_nmgr() {
	_deprecated_function( __FUNCTION__, '4.3.1' );
	return apply_filters( 'is_nmgr',
		is_nmgr_wishlist() ||
		is_nmgr_admin() ||
		is_nmgr_search() ||
		is_nmgr_account_section()
	);
}

/**
 * Get the permalink for the wishlist account page
 * or for managing a specific wishlist on the account page
 *
 * @param mixed $id_or_slug The post id, post slug or NMGR_Wishlist object for the wishlist. Optional.
 * @return string
 */
function nmgr_get_account_url( $id_or_slug = '' ) {
	_deprecated_function( __FUNCTION__, '4.0.0' );

	if ( !$id_or_slug ) {
		$account_url = nmgr_get_url( 'gift-registry', 'home' );
	} else {
		if ( is_a( $id_or_slug, 'NMGR_Wishlist' ) ) {
			$slug = $id_or_slug->get_slug();
		} elseif ( is_numeric( $id_or_slug ) ) {
			$post = get_post( $id_or_slug );
			$slug = $post ? $post->post_name : '';
		} else {
			$slug = ( string ) $id_or_slug;
		}
		$account_url = nmgr_get_url( 'gift-registry', $slug );
	}

	return apply_filters( 'nmgr_account_url', $account_url );
}

/**
 * Verify the standard form nonce supplied in NMGR_Form
 *
 * @param array $request Array to check in for existing nonce key or $_REQUEST if not supplied
 * @return false|int False if the nonce is invalid, 1 if the nonce is valid and generated between
 *                   0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
 */
function nmgr_verify_form_nonce( $request = '' ) {
	_deprecated_function( __FUNCTION__, '4.0.0' );
	return NMGR_Form::verify_nonce( $request );
}

/**
 * Verify the wishlist id posted during a request and make sure that it is the
 * same wishlist id sent to the page
 *
 * @deprecated 2.0.0
 * @param array $posted_data The posted data containing the 'wishlist id' and 'nonce' keys. Optional. Default is $_POST.
 * @return int|null Verified wishlist id or null if the wishlist id supplied is invalid.
 */
function nmgr_get_verified_wishlist_id( $posted_data = '' ) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'nmgr_verify_request' );

	$wishlist_id = isset( $_REQUEST[ 'wishlist_id' ] ) ? ( int ) $_REQUEST[ 'wishlist_id' ] : 0;
	if ( 0 === $wishlist_id && nmgr_user_has_wishlist( $wishlist_id ) ) {
		return $wishlist_id;
	}
	return null;
}

function nmgr_get_id_for_object( $object ) {
	_deprecated_function( __FUNCTION__, '4.4.0' );
	if ( !empty( $object->ID ) ) {
		$id = absint( $object->ID );
	} elseif ( is_numeric( $object ) && $object > 0 ) {
		$id = ( int ) $object;
	} elseif ( is_callable( [ $object, 'get_id' ] ) ) {
		$id = $object->get_id();
	}
	return isset( $id ) ? $id : false;
}

/**
 * Get specific information concerning the main wishlist account page
 *
 * For example, get the name or slug of the account page, as set in the admin settings screen
 *
 * @todo remove in later version
 * @deprecated since version 4.3.0
 * @param string $param The information to get e.g. 'name', 'slug'
 * @return string Wishlist account page information
 */
function nmgr_get_account_details( $param ) {
	_deprecated_function( __FUNCTION__, '4.3.0' );
	$options = nmgr_get_option();
	switch ( $param ) {
		case 'name':
			return isset( $options[ 'my_account_name' ] ) ? esc_html( $options[ 'my_account_name' ] ) : false;
		case 'slug':
			$slug = isset( $options[ 'my_account_name' ] ) ? $options[ 'my_account_name' ] : null;
			return $slug ? remove_accents( strtolower( str_replace( ' ', '-', esc_html( $slug ) ) ) ) : false;
	}
}

/**
 * Add to wishlist messages.
 *
 * @param NMGR_Wishlist $wishlist Wishlist Object
 * @param int|array $products Array of product id to quantity wishlist or single product ID.
 * @param bool      $show_qty Should qty's be shown?
 * @param bool      $return   Return message rather than add it.
 *
 * @return mixed
 */
function nmgr_add_to_wishlist_notice( $wishlist, $products, $show_qty = false, $return = false ) {
	_deprecated_function( __FUNCTION__, '4.5.0' );

	$titles = array();
	$count = 0;
	$type = $wishlist->get_type();

	if ( !is_array( $products ) ) {
		$products = array( $products => 1 );
		$show_qty = false;
	}

	if ( !$show_qty ) {
		$products = array_fill_keys( array_keys( $products ), 1 );
	}

	foreach ( $products as $product_id => $qty ) {
		$titles[] = apply_filters( 'nmgr_add_to_wishlist_qty_html', ( $qty > 1 ? absint( $qty ) . ' &times; ' : '' ), $product_id ) .
			apply_filters( 'nmgr_add_to_wishlist_item_name_in_quotes',
				sprintf(
					/* translators: %s: item name */
					nmgr()->is_pro ? _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'nm-gift-registry' ) : _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'nm-gift-registry-lite' ),
					strip_tags( get_the_title( $product_id ) )
				),
				$product_id
		);
		$count += $qty;
	}

	$titles = array_filter( $titles );
	$added_text = sprintf(
		/* translators: 1: item names, 2: wishlist type title */
		nmgr()->is_pro ? _n( '%1$s has been added to your %2$s.', '%1$s have been added to your %2$s.', $count, 'nm-gift-registry' ) : _n( '%1$s has been added to your %2$s.', '%1$s have been added to your %2$s.', $count, 'nm-gift-registry-lite' ),
		wc_format_list_of_items( $titles ),
		esc_html( nmgr_get_type_title( '', false, $type ) )
	);

	// Get success messages.
	$message = sprintf( '<a href="%s" tabindex="1" class="button wc-forward">%s</a> %s',
		esc_url( $wishlist->get_permalink() ),
		sprintf(
			/* translators: %s: wishlist type title */
			nmgr()->is_pro ? __( 'View %s', 'nm-gift-registry' ) : __( 'View %s', 'nm-gift-registry-lite' ),
			nmgr_get_type_title( '', false, $type )
		),
		esc_html( $added_text )
	);

	$message = apply_filters( 'nmgr_add_to_wishlist_notice', $message, $wishlist, $products, $show_qty );

	if ( $return ) {
		return $message;
	} else {
		wc_add_notice( $message );
	}
}

/**
 * Get the standard date format used by the plugin to display dates sitewide
 *
 * This function simply allows the date format to be filtered so that a different
 * date format can be used to display dates sitewide.
 *
 * @return string
 */
function nmgr_date_format() {
	_deprecated_function( __FUNCTION__, '2.4.2' );
	return apply_filters_deprecated( 'nmgr_date_format', [ get_option( 'date_format' ) ], '2.4.2' );
}

/**
 * Get the registered php date format for the plugin.
 *
 * This date format is used by default to format all dates displayed by the plugin sitewide
 * and also to validate dates submitted through jquery-datepicker
 *
 * @return string
 */
function nmgr_php_date_format() {
	_deprecated_function( __FUNCTION__, '2.4.2' );
	return get_option( 'date_format' );
}

/**
 * Checks whether the post content contains any of the specified shortcodes
 *
 * This function is a simply modification of woocommerce's wc_post_content_has_shortcode
 * function to allow checking for multiple shortcodes at once
 *
 * @param string|array $tags Shortcode tag(s) to check for
 * @see wc_post_content_has_shortcode()
 * @return boolean
 */
function nmgr_post_content_has_shortcodes( $tags ) {
	_deprecated_function( __FUNCTION__, '4.0.0', 'nmgr_post_content_has_shortcode' );

	foreach ( ( array ) $tags as $tag ) {
		if ( wc_post_content_has_shortcode( $tag ) ) {
			return true;
		}
	}
	return false;
}

if ( !function_exists( 'nmgr_get_account_tabs' ) ) {

	/**
	 * Tabs used on account page
	 *
	 * @return array
	 */
	function nmgr_get_account_tabs( $wishlist = false ) {
		_deprecated_function( __FUNCTION__, '4.0.0' );
		$account = nmgr()->account( $wishlist );
		$sections_data = $account->get_sections_data();

		foreach ( $sections_data as $key => $data ) {
			$sections_data[ $key ][ 'tab_id' ] = "nmgr-tab-{$key}";
			$sections_data[ $key ][ 'tab_content_id' ] = "tab-{$key}";
		}

		return $sections_data;
	}

}

/**
 * Get the default content that should be shown on an account section.
 * This content is typically shown when there is no wishlist or the account section has no content.
 *
 * @param string $section The name of the account section or tab
 * @param mixed $wishlist The wishlist object, if a wishlist already exists.
 * @return html
 */
function nmgr_get_default_account_section_content( $section = '', $wishlist = '' ) {
	_deprecated_function( __FUNCTION__, '4.7.0', 'nmgr_default_content' );

	$svg = '';
	$svg_args = '';

	$icon = apply_filters_deprecated( 'nmgr_default_account_section_svg_icon', [ 'heart', $section, $wishlist ], '4.7.0' );

	if ( $icon ) {
		$default_svg_args = array(
			'icon' => $icon,
			'size' => nmgr()->post_thumbnail_size() / 16, // convert px to em
			'fill' => '#f8f8f8',
		);

		$svg_args = apply_filters_deprecated( 'nmgr_default_account_section_svg_args', [ $default_svg_args, $section, $wishlist ], '4.7.0' );
	}

	if ( $svg_args ) {
		$svg = sprintf( '<div class="nmgr-no-wishlist-placeholder-svg nmgr-text-center">%s</div>',
			nmgr_get_svg( $svg_args )
		);
	}

	$overridden_file = nmgr_overridden( 'account/call-to-action-no-wishlist.php' );
	if ( $overridden_file ) {
		nmgr_overridden_notice( $overridden_file, '4.6.0' );
		$template = nmgr_get_template( 'account/call-to-action-no-wishlist.php', array( 'section' => $section, 'wishlist' => $wishlist ) );
	} else {
		$template = nmgr_default_content( $section );
	}

	$call_to_action = apply_filters_deprecated( 'nmgr_default_account_section_call_to_action', [ $template, $section, $wishlist ], '4.7.0' );

	$content = apply_filters_deprecated( 'nmgr_default_account_section_content', [ ($svg . $call_to_action ), $section, $wishlist ], '4.7.0' );

	return $content;
}

if ( !function_exists( 'nmgr_get_account_template' ) ) {

	/**
	 * Template for displaying a user's wishlist account information
	 *
	 * @param int|string|NMGR_Wishlist|array $atts Attributes needed to compose the template.
	 * Currently accepted $atts attributes if array:
	 * - id [int|string|NMGR_Wishlist] optional. The id, slug or object of the wishlist to display account information for.
	 * Default to current wishlist id in global context.
	 *
	 * @param boolean $echo Whether to echo the template. Default false.
	 * @return string Template html
	 */
	function nmgr_get_account_template( $atts = '', $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.0.0', 'nmgr_get_wishlist_template()' );
		ob_start();
		$notice = sprintf(
				/* translators: %s: wishlist type title */
				nmgr()->is_pro ? __( 'You can now manage your %s directly from the main page for viewing it.', 'nm-gift-registry' ) : __( 'You can now manage your %s directly from the main page for viewing it.', 'nm-gift-registry-lite' ),
				nmgr_get_type_title()
			) . ' ' . nmgr_get_click_here_link( nmgr_get_url( 'gift-registry', 'home' ) );

		function_exists( 'wc_print_notice' ) ? wc_print_notice( $notice, 'notice' ) : '';
		$template = ob_get_clean();

		if ( $echo ) {
			echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $template;
		}
	}

}

if ( !function_exists( 'nmgr_get_account_wishlist_template' ) ) {

	/**
	 * Template for managing account information for a single wishlist
	 *
	 * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
	 * Currently accepted $atts attributes if array:
	 * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
	 *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
	 *
	 * @param boolean $echo Whether to echo the template. Default false.
	 *
	 * @return string Template html
	 */
	function nmgr_get_account_wishlist_template( $atts = '', $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.0.0' );
	}

}

if ( !function_exists( 'nmgr_get_overview_template' ) ) {

	function nmgr_get_overview_template( $atts = '', $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.0.0' );
		return '';
	}

}

if ( !function_exists( 'nmgr_get_items_template' ) ) {


	function nmgr_get_items_template( $id, $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.7.0', 'nmgr_get_account_section' );
		$template = nmgr_get_account_section( 'items', $id );
		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

if ( !function_exists( 'nmgr_get_shipping_template' ) ) {

	function nmgr_get_shipping_template( $id, $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.7.0', 'nmgr_get_account_section' );
		$template = nmgr_get_account_section( 'shipping', $id );
		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

function nmgr_add_to_wishlist( $wishlist, $product, $quantity, $favourite = null, $variations = array(), $item_data = array() ) {
	_deprecated_function( __FUNCTION__, '4.4.0' );
	nmgr()->add_to_wishlist()->create( $wishlist, $product, $favourite, $quantity, $variations, $item_data );
}

/**
 * Check if it is an admin request
 * @deprecated since version 4.3.0
 * @return boolean
 */
function is_nmgr_admin_request() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
	$current_url = home_url( add_query_arg( null, null ) );
	$admin_url = strtolower( admin_url() );
	$referrer = strtolower( wp_get_referer() );

	// Check if this is a admin request. If true, it
	// could also be a AJAX request from the frontend.
	if ( 0 === strpos( $current_url, $admin_url ) ) {
		// Check if the user comes from a admin page.
		if ( 0 === strpos( $referrer, $admin_url ) ) {
			return true;
		} else {
			return !wp_doing_ajax();
		}
	} else {
		return false;
	}
}

/**
 * Set a cookie - wrapper for setcookie using WP constants.
 *
 * @param string $name Cookie name
 * @param string $value Cookie value
 * @param integer $expire Cookie expiry
 * @deprecated since version 4.4.0
 */
function nmgr_setcookie( $name, $value, $expire = 0 ) {
	_deprecated_function( __FUNCTION__, '4.4.0' );
	if ( !headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, false, false );
	}
}

function nmgr_get_delete_item_notice( $item ) {
	_deprecated_function( __FUNCTION__, '4.4.0', 'NMGR_Items_View::get_delete_item_notice' );
	return apply_filters( 'nmgr_delete_item_notice', sprintf(
			/* translators: %s: wishlist type title */
			nmgr()->is_pro ? __( 'Are you sure you want to remove the %s item?', 'nm-gift-registry' ) : __( 'Are you sure you want to remove the %s item?', 'nm-gift-registry-lite' ),
			nmgr_get_type_title( '', false, $item->get_wishlist()->get_type() )
		), $item );
}

/**
 * Display posts found using the standard wishlist template.
 *
 * This function is meant to provide a consistent template for displaying wishlist posts on any nm_gift_registry
 * archive page such as search, categories, tags, post_type_archive e.t.c. It also works with a custom $wp_query object.
 *
 * @param WP_Query $query Custom query object if provided. Uses global $wp_query by default.
 * @param array $action_args The arguments supplied to the action hooks used in the function.
 *
 * @deprecated since version 2.5
 */
function nmgr_archive_content( $query = null, $action_args = array() ) {
	_deprecated_function( __FUNCTION__, '2.5', 'nmgr_archive_loop' );
	Archive::loop( $query, $action_args );
}

function nmgr_archive_loop( $query = null, $action_args = array() ) {
	_deprecated_function( __FUNCTION__, '4.7.0', 'NMGR\Lib\Archive::loop()' );
	Archive::loop( $query, $action_args );
}

/**
 * Get the default plugin options used with jquery datatables
 * @return array
 */
function nmgr_get_default_datatable_options() {
	_deprecated_function( __FUNCTION__, '4.4.0' );
	return apply_filters( 'nmgr_default_datatable_options',
		array(
			'order' => array(),
			'paging' => false,
			'searching' => false,
			'info' => false,
			'columnDefs' => array(
				array( 'orderable' => true, 'targets' => array( 'dt-orderable' ) ),
				array( 'orderable' => false, 'targets' => array( '_all' ) ),
			),
			'language' => array(
				'zeroRecords' => nmgr()->is_pro ?
				_x( 'No items', 'Empty wishlist items table', 'nm-gift-registry' ) :
				_x( 'No items', 'Empty wishlist items table', 'nm-gift-registry-lite' ),
			),
		)
	);
}

function nmgr_get_datatables() {
	_deprecated_function( __FUNCTION__, '4.4.0' );
	$default_options = nmgr_get_default_datatable_options();
	$default_col_defs = isset( $default_options[ 'columnDefs' ] ) ? $default_options[ 'columnDefs' ] : array();

	return apply_filters( 'nmgr_datatables', array(
		'items_table' => array(
			'selector' => '.nmgr-items-table',
			'name' => 'items_table',
			'events' => array( 'nmgr_items_reloaded' ),
			'options' => array_merge( $default_options, array(
				'columnDefs' => array_merge(
					array(
						array(
							'orderable' => true,
							'targets' => array(
								'item_title',
								'item_cost',
								'item_purchased_quantity',
								'item_favourite',
								'item_total_cost',
							)
						),
					),
					$default_col_defs
				),
			) ),
		),
		) );
}

/**
 * Get the ids of all orders that contain a particular wishlist
 *
 * @global wpdb $wpdb
 * @param int $wishlist_id The wishlist id
 * @return array Array of order ids
 */
function nmgr_get_wishlist_order_ids( $wishlist_id ) {
	_deprecated_function( __FUNCTION__, '4.3.0', 'NMGR_Wishlist->get_order_ids' );
	return nmgr_get_wishlist( $wishlist_id )->get_order_ids();
}

/**
 * Get the number of days guest wishlist can exist before they are deleted
 * and their cookies expire in the browser
 *
 * The maximum is set at 365 as it probably doesn't makes sense for guest
 * wishlist to last longer than a year. They can register for an account.
 *
 * @deprecated since version 4.4.0
 * @return int
 */
function nmgr_get_guest_wishlist_expiry_days() {
	_deprecated_function( __FUNCTION__, '4.4.0' );
	$days = ( int ) apply_filters(
			'nmgr_guest_wishlist_expiry_days',
			nmgr_get_option( 'guest_wishlist_expiry_days', 365 )
	);
	$val = 1 > $days ? 0 : (365 < $days ? 365 : $days);
	return $val;
}

function nmgr_get_archive_template_args( $atts = [] ) {
	_deprecated_function( __FUNCTION__, '4.7.0', '\NMGR\Lib\Archive::get_args()' );
	return Archive::get_args( $atts );
}

function nmgr_get_add_to_wishlist_mode( $args = [] ) {
	_deprecated_function( __FUNCTION__, '4.0.0' );
	return apply_filters( 'nmgr_add_to_wishlist_mode', 'simple', $args );
}

/**
 * @deprecated since version 4.3.1
 */
function nmgr_post_content_has_shortcode( $shortcode, $post_id = null ) {
	_deprecated_function( __FUNCTION__, '4.3.1' );

	global $wpdb;

	$id = $post_id ?? get_queried_object_id();
	$page = $id ? get_post( $id ) : null;

	if ( $page ) {
		$db_search = strtok( $shortcode, ' ' );

		$cache_key = 'nmgr_pchs_' . trim( $db_search, '[]' ) . '_' . $page->ID;
		$result = wp_cache_get( $cache_key );

		if ( false === $result ) {
			$result = $wpdb->get_var( $wpdb->prepare(
					"SELECT ID FROM $wpdb->posts
								WHERE post_type='page'
								AND post_status='publish'
								AND ID=%d
								AND post_content LIKE %s
								LIMIT 1;",
					$page->ID,
					"%{$db_search}%"
				) );

			wp_cache_set( $cache_key, $result );
		}

		return $result;
	}
}

/**
 * Get a template file from the templates path
 *
 * This function searches the templates path in the theme folder before defaulting to
 * the templates path in the plugin folder if it doesn't find the file.
 * This way, It allows plugin templates to be overridden by copying them to the theme folder
 * similar to the way woocommerce works.
 *
 * The default expected theme template path where overridden templates reside is: yourtheme/plugin-slug'
 * where 'yourtheme' is the name of your theme and 'plugin-slug' is nm-gift-registry-lite for the lite version or
 * nm-gift-registry for the full version of the plugin.
 *
 * @param string $name Name of template file to get (prefixed with subfolder if it exists in a subfolder of the template path).
 * @param array $args Variables to send to the template file.
 *
 * @return string Template html
 */
function nmgr_get_template( $name, $args = array() ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	global $nmgr_template;
	$nmgr_template = $name;

	$defaults = array(
		'template_name' => $name,
		'args' => $args,
		'template_path' => nmgr()->theme_path(),
		'default_path' => nmgr()->template_path(),
	);

	$fargs = apply_filters( 'nmgr_get_template_args', $defaults );

	$template = wc_get_template_html(
		$fargs[ 'template_name' ],
		$fargs[ 'args' ],
		$fargs[ 'template_path' ],
		$fargs[ 'default_path' ]
	);

	return $template;
}

/**
 * Output a template file
 *
 * @param type $name Name of template file to get (prefixed with subfolder if it exists in a subfolder of the template path).
 * @param type $args Variables to send to the template file.
 */
function nmgr_template( $name, $args = array() ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	echo nmgr_get_template( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get all the users that have active wishlists
 *
 * Active wishlists are wishlists that are not in trash.
 *
 * @global wpdb $wpdb
 * @param string category The category of wishlists to get.
 * @return array User ids
 */
function nmgr_get_users( $category = 'gift-registry' ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	global $wpdb;

	if ( !in_array( $category, [ 'wishlist', 'gift-registry' ] ) ) {
		_deprecated_argument( __FUNCTION__, '4.0.0', 'Use "gift-registry" or "wishlist".' );
		return [];
	}

	$ids = wp_cache_get( 'nmgr_user_ids' );

	if ( false === $ids ) {
		$ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT post_author FROM {$wpdb->posts} posts
		INNER JOIN {$wpdb->term_relationships} term_relationships
		ON posts.ID = term_relationships.object_id
		INNER JOIN {$wpdb->term_taxonomy} term_taxonomy
		ON term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
		INNER JOIN {$wpdb->terms} terms
		ON terms.term_id = term_taxonomy.term_taxonomy_id
					WHERE post_author != 0
					AND post_type = 'nm_gift_registry'
					AND post_status != 'trash'
					AND term_taxonomy.taxonomy = 'nm_gift_registry_type'
		AND terms.slug = %s
				 ",
				$category
			) );

		wp_cache_set( 'nmgr_user_ids', $ids );
	}
	return $ids;
}

/**
 * Get the title attribute to show when a wishlist has a product
 *
 * This depends on the kind of product - simple, variable, grouped
 * as well as the number of wishlists the user has
 */
function nmgr_get_product_in_wishlist_title_attribute( $product, $type = 'gift-registry' ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	if ( !$product ) {
		return;
	}

	$count = nmgr_get_user_wishlists_count( '', $type );
	$product_type = $product->get_type();
	$wishlist_type_title = ($count > 1 ? nmgr_get_type_title( '', 1, $type ) : nmgr_get_type_title( '', false, $type ) );

	switch ( $product_type ) {
		case 'variable':
			return sprintf(
				nmgr()->is_pro ?
				/* translators: %s: wishlist type title */
				_n(
					'This product has variations in your %s',
					'This product has variations in one or more of your %s',
					$count,
					'nm-gift-registry'
				) :
				/* translators: %s: wishlist type title */
				_n(
					'This product has variations in your %s',
					'This product has variations in one or more of your %s',
					$count,
					'nm-gift-registry-lite'
				),
				$wishlist_type_title
			);
		case 'grouped':
			return sprintf(
				nmgr()->is_pro ?
				/* translators: %s: wishlist type title */
				_n(
					'This product has child products in your %s',
					'This product has child products in one or more of your %s',
					$count,
					'nm-gift-registry'
				) :
				/* translators: %s: wishlist type title */
				_n(
					'This product has child products in your %s',
					'This product has child products in one or more of your %s',
					$count,
					'nm-gift-registry-lite'
				),
				$wishlist_type_title
			);
		default:
			return sprintf(
				nmgr()->is_pro ?
				/* translators: %s: wishlist type title */
				_n(
					'This product is in your %s',
					'This product is in one or more of your %s',
					$count,
					'nm-gift-registry'
				) :
				/* translators: %s: wishlist type title */
				_n(
					'This product is in your %s',
					'This product is in one or more of your %s',
					$count,
					'nm-gift-registry-lite'
				),
				$wishlist_type_title
			);
	}
}

/**
 * Check whether the current user has a product in any of his wishlists
 *
 * @param WC_Product $product
 * @return boolean True or false
 */
function nmgr_user_has_product_in_wishlist( $product, $type = 'gift-registry' ) {
	_deprecated_function( __FUNCTION__, '4.10', 'NMGR\Lib\AddToWishlist->user_has_product()' );
	return (new NMGR\Lib\AddToWishlist )->user_has_product( $product, $type );
}

/**
 * Query string keys for adding an item to the wishlist
 *
 * This function is used to prevent hardcoding the query keys in various files.
 * In case they change later, they would only change here
 *
 * @param string $name query key to get for value
 * @return string Query key
 */
function nmgr_query_key( $name = '' ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	$query_keys = array(
		'product_id' => 'nmgr_pid',
		'wishlist_id' => 'nmgr_wid',
		'quantity' => 'nmgr_qty',
		'favourite' => 'nmgr_fav',
		'variation_id' => 'nmgr_vid',
		'wishlist' => 'nmgr_w', // accepts wishlist id or slug.
	);

	return $name ? (isset( $query_keys[ $name ] ) ? $query_keys[ $name ] : null) : $query_keys;
}

/**
 * Add the plugin prefix to the specified fields keys
 * except fields that have $args['prefix'] set to false
 *
 * @param array $fields Form fields to add prefix to
 * @return array Prefixed form fields
 */
function nmgr_add_prefix( $fields ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	$prefixed = array();
	foreach ( $fields as $name => $args ) {
		if ( (isset( $args[ 'prefix' ] ) && !$args[ 'prefix' ]) || false !== strpos( $name, 'nmgr_' ) ) {
			$prefixed[ $name ] = $args;
			continue;
		}
		$prefixed[ 'nmgr_' . $name ] = $args;
	}
	return $prefixed;
}

/**
 * Remove plugin prefix from supplied fields
 *
 * The function removes the prefix from field keys and values if they are strings or arrays of strings
 *
 * @param string | array $data Fields to remove prefix from
 * @return array Fields with prefix removed from keys and values
 */
function nmgr_remove_prefix( $data ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	$data_array = ( array ) $data;
	$new_data = array();

	foreach ( $data_array as $key => $value ) {
		$key = str_replace( 'nmgr_', '', $key );
		$value = is_string( $value ) ? str_replace( 'nmgr_', '', $value ) : $value;

		if ( is_array( $value ) ) {
			$value = array_map( function( $val ) {
				return is_string( $val ) ? str_replace( 'nmgr_', '', $val ) : $val;
			}, $value );
		}
		$new_data[ $key ] = $value;
	}
	return $new_data;
}

/**
 * Include the svg sprite file in a page
 */
function nmgr_include_sprite_file() {
	_deprecated_function( __FUNCTION__, '4.10' );
	$sprite_file = nmgr()->path . 'assets/svg/sprite.svg';
	if ( file_exists( $sprite_file ) ) {
		include_once $sprite_file;
	}
}

if ( !function_exists( 'nmgr_get_profile_template' ) ) {

	function nmgr_get_profile_template( $atts = '', $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.10' );
		$account = nmgr()->account( $atts[ 'id' ] ?? false );
		if ( !empty( $atts[ 'type' ] ) ) {
			$account->set_type( $atts[ 'type' ] );
		}
		$template = $account->set_section( 'profile' )->get_section_template();

		if ( $echo ) {
			echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $template;
		}
	}

}

/**
 * Template for displaying a single wishlist
 *
 * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
 * Currently accepted $atts attributes if array:
 * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
 *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
 *
 * @param boolean $echo Whether to echo the template. Default false.
 *
 * @return string Template html
 */
function nmgr_get_wishlist_template( $atts = '', $echo = false ) {
	_deprecated_function( __FUNCTION__, '4.10', 'NMGR\Lib\Single::get_template' );
	$template_class = \NMGR\Lib\Single::get_template( $atts );

	if ( $echo ) {
		echo $template_class;
	} else {
		return $template_class;
	}
}

if ( !function_exists( 'nmgr_get_share_template' ) ) {

	function nmgr_get_share_template( $id, $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.10', 'NMGR\Lib\Single::get_share_template' );

		$template = \NMGR\Lib\Single::get_share_template( $id );

		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

function nmgr_add_to_wishlist_button_2() {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->add_to_wishlist()->get_button_template()' );
	echo nmgr()->add_to_wishlist()->get_button_template( [ 'type' => 'wishlist' ] );
}

/**
 * Displays the button for adding a product to the wishlist
 *
 * @param int|WC_Product $atts Attributes needed to compose the button
 * Currently accepted $atts attributes if array:
 * - id [int|WC_Product] Product id or instance of WC_Product. Default none - id is taken from the global product variable context if present.
 * @return string button html
 */
function nmgr_add_to_wishlist_button( $atts = false ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->add_to_wishlist()->get_button_template()' );
	echo nmgr()->add_to_wishlist()->get_button_template( $atts );
}

/**
 * Returns the admin url base page for the plugin
 */
function nmgr_get_admin_url() {
	_deprecated_function( __FUNCTION__, '4.10' );
	return admin_url( 'edit.php?post_type=nm_gift_registry' );
}

/**
 * Get the possible titles that can be used for the wishlist type
 *
 * @return array
 */
function nmgr_get_type_titles() {
	_deprecated_function( __FUNCTION__, '4.10' );
	$titles = array(
		'gift_registry' => array(
			'singular' => nmgr()->is_pro ?
			__( 'gift registry', 'nm-gift-registry' ) :
			__( 'gift registry', 'nm-gift-registry-lite' ),
			'plural' => nmgr()->is_pro ?
			__( 'gift registries', 'nm-gift-registry' ) :
			__( 'gift registries', 'nm-gift-registry-lite' )
		),
		'wishlist' => array(
			'singular' => nmgr()->is_pro ?
			__( 'wishlist', 'nm-gift-registry' ) :
			__( 'wishlist', 'nm-gift-registry-lite' ),
			'plural' => nmgr()->is_pro ?
			__( 'wishlists', 'nm-gift-registry' ) :
			__( 'wishlists', 'nm-gift-registry-lite' )
		),
		'gift_list' => array(
			'singular' => nmgr()->is_pro ?
			__( 'gift list', 'nm-gift-registry' ) :
			__( 'gift list', 'nm-gift-registry-lite' ),
			'plural' => nmgr()->is_pro ?
			__( 'gift lists', 'nm-gift-registry' ) :
			__( 'gift lists', 'nm-gift-registry-lite' )
		),
		'list' => array(
			'singular' => nmgr()->is_pro ?
			__( 'list', 'nm-gift-registry' ) :
			__( 'list', 'nm-gift-registry-lite' ),
			'plural' => nmgr()->is_pro ?
			__( 'lists', 'nm-gift-registry' ) :
			__( 'lists', 'nm-gift-registry-lite' )
		)
	);

	return apply_filters( 'nmgr_type_titles', $titles );
}

/**
 * Sanitize content to allow for svg tags used by the plugin
 *
 * @param string $data Content to sanitize
 * @return string Sanitized content
 */
function nmgr_kses_svg( $data ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	return wp_kses( $data, nmgr_allowed_svg_tags() );
}

/**
 * Sanitize content to allow for HTML tags used by WordPress in post content and svg tags used by the plugin
 *
 * This function is simply used to allow the plugin svg tags to be used alongside
 * WordPress allowed HTML tags in post content.
 *
 * @param string $data Content to sanitize
 * @return string Sanitized content
 */
function nmgr_kses_post( $data ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	return wp_kses( $data, array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() ) );
}

/**
 * Check whether the cart has items belonging to a particular wishlist
 *
 * If no wishlist id is supplied, the function checks for the first wishlist that may have an item in the cart.
 *
 * Alias of nmgr_get_wishlist_in_cart()
 *
 * @param int $wishlist_id The wishlist id to check for.
 * @return boolean
 */
function nmgr_cart_has_wishlist( $wishlist_id = '' ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	return ( bool ) nmgr_get_wishlist_in_cart( $wishlist_id );
}

/**
 * Get the wishlist search template
 * This includes the search form and search results, depending on the arguments provided
 *
 * @param type $atts Attributes needed to compose the template
 * @param boolean $echo Whether to echo the template. Default false.
 * @return string Template html
 */
function nmgr_get_search_template( $atts = '', $echo = false ) {
	_deprecated_function( __FUNCTION__, '4.10', '\NMGR\Lib\Archive::get_search_template' );
	$template = \NMGR\Lib\Archive::get_search_template( $atts );

	if ( $echo ) {
		echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $template;
	}
}

function nmgr_get_search_template_args( $atts = [] ) {
	_deprecated_function( __FUNCTION__, '4.10', '\NMGR\Lib\Archive::get_search_template_args' );
	return \NMGR\Lib\Archive::get_search_template_args( $atts );
}

if ( !function_exists( 'nmgr_get_search_results_template' ) ) {

	/**
	 * Get the template for outputting wishlist search results
	 *
	 * Alias of nmgr_get_archive_template()
	 *
	 * This function or the shortcode attached to it should be used after wp_loaded hook
	 * as that is when the wp_query global exists.
	 *
	 * @param array $atts Attributes needed to compose the template.
	 * @param bool $echo Whether to echo or return the template.
	 */
	function nmgr_get_search_results_template( $atts = array(), $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.10', '\NMGR\Lib\Archive::get_search_results_template' );
		$template = \NMGR\Lib\Archive::get_search_results_template( $atts );

		if ( $echo ) {
			echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $template;
		}
	}

}

/**
 * Get the search form for finding a wishlist on the frontend
 */
function nmgr_get_search_form( $args = [] ) {
	_deprecated_function( __FUNCTION__, '4.10', '\NMGR\Lib\Archive::get_search_form' );
	return \NMGR\Lib\Archive::get_search_form( $args );
}

function nmgr_search_wishlist_form( $args ) {
	_deprecated_function( __FUNCTION__, '4.10', '\NMGR\Lib\Archive::search_form' );
	ob_start();
	$form_action = $args[ 'form_action' ];
	$input_name = $args[ 'input_name' ];
	$input_placeholder = $args[ 'input_placeholder' ];
	$input_required = $args[ 'input_required' ];
	$input_value = $args[ 'input_value' ];
	$submit_button_text = $args[ 'submit_button_text' ];
	$hidden_fields = $args[ 'hidden_fields' ];
	?>
	<form role="search" method="get" class="nmgr-search-form" action="<?php echo esc_url( $form_action ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $input_name ); ?>">
			<?php
			echo esc_html( nmgr()->is_pro ?
					__( 'Search for:', 'nm-gift-registry' ) :
					__( 'Search for:', 'nm-gift-registry-lite' )
			);
			?>
		</label>
		<input type="search"
					 class="search-field"
					 placeholder="<?php echo esc_attr( $input_placeholder ); ?>"
					 value="<?php echo esc_attr( stripslashes( $input_value ) ); ?>"
					 <?php echo (!empty( $input_required )) ? 'required' : ''; ?>
					 name="<?php echo esc_attr( $input_name ); ?>" />
					 <?php if ( $submit_button_text ) : ?>
			<button type="submit">
				<?php echo esc_html( $submit_button_text ); ?>
			</button>
		<?php endif; ?>
		<?php
		if ( isset( $hidden_fields ) && !empty( $hidden_fields ) ) :
			foreach ( $hidden_fields as $key => $value ) :
				?>
				<input type="hidden"
							 name="<?php echo esc_html( $key ); ?>"
							 value="<?php echo esc_html( $value ); ?>" />
							 <?php
						 endforeach;
					 endif;
					 ?>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Generate a user id for wishlist users.
 * This is typically done for guests who are not logged in and so have no user id
 *
 * @return string
 */
function nmgr_generate_user_id() {
	_deprecated_function( __FUNCTION__, '4.10' );
	require_once ABSPATH . 'wp-includes/class-phpass.php';
	$hasher = new PasswordHash( 8, false );
	return md5( $hasher->get_random_bytes( 32 ) );
}

if ( !function_exists( 'nmgr_get_cart_template' ) ) {

	/**
	 * Template for displaying wishlists in cart fashion
	 *
	 * By default wishlists are displayed as a dropdown but this can be changed
	 * by supplying relevant arguments
	 *
	 * @param mixed $atts Attributes needed to compose the template.
	 *
	 * @param boolean $echo Whether to echo the template. Default false.
	 *
	 * @return string Template html
	 */
	function nmgr_get_cart_template( $atts = '', $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.10', 'NMGR_Widget_Cart::template' );
		$template = \NMGR_Widget_Cart::template( $atts );

		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

function nmgr_get_dialog_submit_button( $args = array() ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	$class = isset( $args[ 'class' ] ) ? esc_attr( implode( ' ', ( array ) $args[ 'class' ] ) ) : '';
	$text = isset( $args[ 'text' ] ) ?
		$args[ 'text' ] :
		(nmgr()->is_pro ?
		__( 'Done', 'nm-gift-registry' ) :
		__( 'Done', 'nm-gift-registry-lite' ));
	$attributes = array();

	if ( isset( $args[ 'attributes' ] ) && is_array( $args[ 'attributes' ] ) ) {
		foreach ( $args[ 'attributes' ] as $attribute => $attribute_value ) {
			$attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
		}
	}

	return sprintf( '<button class="nmgr-dialog-submit-button %1$s" %2$s>%3$s</button>',
		$class,
		implode( ' ', $attributes ),
		$text
	);
}

/**
 * Check if we are on the custom page used for wishlist archives
 * @deprecated since version 4.0.0
 * @todo Add deprecated function notice.
 * @return boolean
 */
function is_nmgr_archive_page() {
	_deprecated_function( __FUNCTION__, '4.10', 'is_nmgr_wishlist_page(archive)' );
	return is_nmgr_wishlist_page( 'archive' );
}

/**
 * Get the search term used for searching wishlists
 *
 * The plugin gives priority to wordpress' search term 's' in the wp_query global object.
 * If 's' is not available, the plugin checks for 'nmgr_s' in the wp_query object.
 * If 'nmgr_s' is not available, the plugin checks for 'nmgr_s' in the $_GET array.
 *
 * This function provides a convenient place where all these checks can be made.
 * The search term can be filtered with 'nmgr_get_search_query'
 *
 * @return string Search query term
 */
function nmgr_get_search_query() {
	_deprecated_function( __FUNCTION__, '4.10', '\NMGR\Lib\Archive::get_search_query' );
	return \NMGR\Lib\Archive::get_search_query();
}

/**
 * Get the reference data for all items that have been added to the cart.
 *
 * Typically used when adding to car via ajax if dom elements related
 * to the item being added need to be updated afterwards
 *
 * @return array|null
 */
function nmgr_get_add_to_cart_item_ref_data() {
	_deprecated_function( __FUNCTION__, '4.10' );
	return isset( $GLOBALS[ 'nmgr_add_to_cart_ref_data' ] ) ? $GLOBALS[ 'nmgr_add_to_cart_ref_data' ] : '';
}

/**
 * Destroy the reference data stored for all items added to the cart.
 */
function nmgr_destroy_add_to_cart_item_ref_data() {
	_deprecated_function( __FUNCTION__, '4.10' );
	if ( isset( $GLOBALS[ 'nmgr_add_to_cart_ref_data' ] ) ) {
		unset( $GLOBALS[ 'nmgr_add_to_cart_ref_data' ] );
	}
}

/**
 * Get variation attributes posted by a form
 *
 * @param int $variation_id Id of the variation product
 * @param array $post posted data contaIning variations
 * @return array
 */
function nmgr_get_posted_variations( $variation_id, $post = '' ) {
	_deprecated_function( __FUNCTION__, '4.10', 'NMGR_Order::get_posted_variations' );

	$variations = array();
	$posted_data = array();

	if ( !$variation_id ) {
		return $variations;
	}

	if ( empty( $post ) ) {
		$posted_data = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification
	} elseif ( is_string( $post ) ) {
		parse_str( $post, $posted_data );
	} elseif ( is_array( $post ) ) {
		$posted_data = $post;
	}

	$product = wc_get_product( $variation_id );
	$product_parent_id = $product->get_parent_id();
	$product_parent = wc_get_product( $product_parent_id );

	if ( !$product_parent ) {
		return $variations;
	}

	foreach ( $posted_data as $key => $value ) {
		if ( false === strpos( $key, 'attribute_' ) ) {
			unset( $posted_data[ $key ] );
		}
	}

	foreach ( $product_parent->get_attributes() as $attribute ) {
		if ( !$attribute[ 'is_variation' ] ) {
			continue;
		}
		$attribute_key = 'attribute_' . sanitize_title( $attribute[ 'name' ] );

		if ( isset( $posted_data[ $attribute_key ] ) ) {
			if ( $attribute[ 'is_taxonomy' ] ) {
				$value = sanitize_title( wp_unslash( $posted_data[ $attribute_key ] ) );
			} else {
				$value = html_entity_decode( wc_clean( wp_unslash( $posted_data[ $attribute_key ] ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
			}
			$variations[ $attribute_key ] = $value;
		}
	}

	return $variations;
}

/**
 * The core action used to add wishlist products to the cart via http or ajax
 *
 * @param array $items_data Array of data of each item to be added to the cart
 * @return array $data Array of data for each item that was supplied to be added to the cart
 */
function nmgr_add_to_cart( $items_data ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->order()->add_to_cart' );
	return \NMGR_Order::add_to_cart( $items_data );
}

/**
 * Add reference details of an item that has been added to the cart
 * to the global reference array
 *
 * Typically used when adding to car via ajax if dom elements related
 * to the item being added need to be updated afterwards
 *
 * @param array $data
 */
function nmgr_add_add_to_cart_item_ref_data( $data ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->order()->add_add_to_cart_item_ref_data()' );
	return nmgr()->order()->add_add_to_cart_item_ref_data( $data );
}

/**
 * Checks if an order has wishlist items
 * @param int|WC_Order $order_id The order id or object
 * @return array|boolean Returns the array of wishlist data for the order or false
 * 				 if the order doesn't contain wishlist items
 */
function nmgr_order_has_wishlist_items( $order_id ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	$order = wc_get_order( $order_id );
	$meta = $order->get_meta( 'nm_gift_registry' );
	return !empty( $meta ) ? $meta : false;
}

/**
 * Sync the data for all wishlist items in an order with the data
 * stored for the items themselves.
 *
 * @param int|WC_Order $order_id The order id or object
 */
function nmgr_sync_order_data( $order_id ) {
	_deprecated_function( __FUNCTION__, '4.10' );
	nmgr()->order()->update_wishlist_item_purchased_quantity( $order_id );
}

/**
 * Check if the cart item belongs to a wishlist
 *
 * Alias of nmgr_get_cart_item_data()
 *
 * @param array|string $cart_item The cart item array value or key
 * @param string $data_type The type of wishlist data to check for,
 * Default is 'wishlist_item'. Others are 'crowdfund', 'free_contribution'
 * @return false|array Array of wishlist data if true. False if the cart item doesn't have wishlist data.
 */
function is_nmgr_cart_item( $cart_item, $data_type = null ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr_get_cart_item_data' );
	return nmgr_get_cart_item_data( $cart_item, $data_type );
}

if ( !function_exists( 'nmgr_get_archive_template' ) ) {

	function nmgr_get_archive_template( $atts = array(), $echo = false ) {
		_deprecated_function( __FUNCTION__, '4.10', 'NMGR\Lib\Archive::get_template' );
		$template = Archive::get_template( $atts );

		if ( $echo ) {
			echo $template;
		} else {
			return $template;
		}
	}

}

function nmgr_get_add_items_link() {
	_deprecated_function( __FUNCTION__, '4.10' );
	return sprintf( '<a class="button nmgr-add-items-link nmgr-tip" title="%1$s" href="%2$s">%3$s</a>',
		nmgr_get_add_items_text(),
		nmgr_get_add_items_url(),
		( nmgr()->is_pro ?
		__( 'Add item(s)', 'nm-gift-registry' ) :
		__( 'Add item(s)', 'nm-gift-registry-lite' )
		)
	);
}

function is_nmgr_settings_screen( $type = '' ) {
	_deprecated_function( __FUNCTION__, '4.10', '\NMGR\Settings\Admin::is_settings_screen()' );
	global $pagenow;

	/**
	 * Set options.php as settings screen so that setting screen would be recognized
	 * when saving plugin setting
	 */
	if ( 'options.php' === $pagenow ) {
		return true;
	}

	$is_screen = is_admin() && 'nm_gift_registry' === ($_GET[ 'post_type' ] ?? false);
	$page = $_GET[ 'page' ] ?? false;
	$sections = [
		'wishlist' => 'nmgr-wishlist-settings',
		'gift-registry' => 'nmgr-settings'
	];

	return $type ?
		$is_screen && $page === ($sections[ $type ] ?? null) :
		$is_screen && in_array( $page, $sections );
}

function nmgr_get_elements_to_show( $actions ) {
	_deprecated_function( __FUNCTION__, '4.10', 'NMGR\Fields\Fields::get_elements_to_show' );
	return \NMGR\Fields\Fields::get_elements_to_show( $actions );
}

function nmgr_get_elements_to_show_in_settings( $actions ) {
	_deprecated_function( __FUNCTION__, '4.10', 'NMGR\Fields\Fields::get_elements_to_show_in_settings' );
	return \NMGR\Fields\Fields::get_elements_to_show_in_settings( $actions );
}

function nmgr_sort_by_priority( $a, $b ) {
	_deprecated_function( __FUNCTION__, '4.10', '\NMGR\Fields\Fields::priority_sort' );
	$a[ 'priority' ] = $a[ 'priority' ] ?? 0;
	$b[ 'priority' ] = $b[ 'priority' ] ?? 0;
	return ( $a[ 'priority' ] < $b[ 'priority' ] ) ? -1 : 1;
}

/**
 * Get the wishlist id and wishlist item ids in the $_POST array
 * @return array An array with wishlist_id (int|false) and wishlist_item_ids (array) keys representing
 * the wishlist_id and wishlist_item_ids in $_POST
 */
function nmgr_get_posted_wishlist_and_item_ids() {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->ajax()->get_posted_wishlist_and_item_ids()' );
	return nmgr()->ajax()->get_posted_wishlist_and_item_ids();
}

/**
 * Make sure a user is allowed to perform the action on a wishlist.
 *
 * For ajax usage.
 * This function is similar to check_ajax_referrer in that it kills the script
 * if the wishlist doesn't exist or if the user performing the action is not the
 * wishlist owner or an admin user who can manage the wishlist.
 *
 * @param int|NMGR_Wishlist $wishlist_id The wishlist id or object
 */
function nmgr_check_wishlist_permission( $wishlist_id ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->ajax()->check_wishlist_permission()' );
	nmgr()->ajax()->check_wishlist_permission( $wishlist_id );
}

/**
 * Wrapper function to update the purchased quantity of an item
 *
 * @param int|NMGR_Wishlist_Item|\NMGR\Sub\Wishlist_Item $item_id Item id or object
 * @param array $args Arguments used to perform the update:
 * - quantity {int} The new purchased quantity.
 * - create_order {boolean} Whether to create an order to reflect the update. Default true.
 * - apply_price - {boolean) Whether to include the price of the item in the created order. Default true.
 * - order_note - {string}. Order note that should be added to the order. Default none.
 * - order_item_meta = {array} Metadata that should be added to the order item if created. Default none.
 */
function nmgr_update_item_purchased_quantity( $item_id, $args = [] ) {
	_deprecated_function( __FUNCTION__, '4.10', 'NMGR_Wishlist_Item->update_purchased_quantity' );
	$item = is_a( $item_id, \NMGR_Wishlist_Item::class ) ? $item_id : nmgr_get_wishlist_item( $item_id );
	return $item->update_purchased_quantity( $args );
}

/**
 * Add data concerning the wishlists in the order as order meta data
 *
 * This should typically be done once immediately after the order has been created.
 * This information would serve as the main data store from which we can
 * perform gift registry related wishlist actions on the order later
 *
 * @param WC_Order $created_order The order object
 */
function nmgr_add_order_meta_data( $order ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->order()->add_meta_data()' );
	return nmgr()->order()->add_meta_data( $order );
}

function nmgr_get_default_type() {
	_deprecated_function( __FUNCTION__, '4.10' );
	if ( nmgr_get_type_option( 'gift-registry', 'enable' ) && nmgr_get_type_option( 'wishlist', 'enable' ) ) {
		$type = '';
	} else {
		$type = nmgr_get_type_option( 'wishlist', 'enable' ) ? 'wishlist' : 'gift-registry';
	}
	return apply_filters( 'nmgr_default_type', $type );
}

function nmgr_get_relocation_notice() {
	_deprecated_function( __FUNCTION__, '4.10' );
	return sprintf(
			/* translators: %s: wishlist type title */
			nmgr()->is_pro ? __( 'You can now manage your %s directly from the main page for viewing it.', 'nm-gift-registry' ) : __( 'You can now manage your %s directly from the main page for viewing it.', 'nm-gift-registry-lite' ),
			nmgr_get_type_title()
		) . ' ' . nmgr_get_click_here_link( nmgr_get_url( 'gift-registry', 'home' ) );
}

function nmgr_get_nav( $args = [] ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->account()->navigation_links()' );
	$defaults = [
		'page' => 1,
		'total_pages' => 1,
		'wrapper_attributes' => [
			'class' => [
				'nmgr-navs',
				($args[ 'template' ] ?? ''),
				($args[ 'position' ] ?? ''),
			],
		],
	];
	$pargs = nmgr_merge_args( $defaults, $args );
	$page = ( int ) $pargs[ 'page' ];
	$total_pages = ( int ) $pargs[ 'total_pages' ];

	if ( 2 > $total_pages ) {
		return;
	}

	ob_start();
	?>
	<div <?php echo wp_kses( nmgr_utils_format_attributes( $pargs[ 'wrapper_attributes' ] ), [] ); ?>>
		<span class="page-info">
			<?php
			printf(
				/* translators: 1: current page, 2: total pages */
				nmgr()->is_pro ? __( 'Page %1$d of %2$d', 'nm-gift-registry' ) : __( 'Page %1$d of %2$d', 'nm-gift-registry-lite' ),
				$page,
				$total_pages
			);
			?>
		</span>
		<?php if ( 1 !== $page ) : ?>
			<a href="#"
				 class="nmgr-tip previous nmgr-nav"
				 data-nav_action="previous"
				 title="<?php
				 echo esc_html( nmgr()->is_pro ?
						 __( 'Previous', 'nm-gift-registry' ) :
						 __( 'Previous', 'nm-gift-registry-lite' )  );
				 ?>">&#10092;</a>
			 <?php endif; ?>
			 <?php if ( $page !== $total_pages ) : ?>
			<a href="#"
				 class="nmgr-tip next nmgr-nav"
				 data-nav_action="next"
				 title="<?php
				 echo esc_html( nmgr()->is_pro ?
						 __( 'Next', 'nm-gift-registry' ) :
						 __( 'Next', 'nm-gift-registry-lite' )  );
				 ?>">&#10093;</a>
			 <?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function nmgr_get_wishlist_type( $wishlist_id ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->wishlist()->get_type_from_db()' );
	$terms = wp_get_object_terms( $wishlist_id, [ 'nm_gift_registry_type' ], [
		'update_term_meta_cache' => false,
		'fields' => 'slugs',
		'number' => 1,
		] );

	if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
		return reset( $terms );
	}
}

/**
 * @return NMGR_Wishlist_Item[]|\NMGR\Sub\Wishlist_Item[]
 */
function nmgr_read_items( $args = [] ) {
	_deprecated_function( __FUNCTION__, '4.10', 'nmgr()->wishlist_item()->get_from_db()' );
	global $wpdb;

	$defaults = [
		'limit' => null,
		'offset' => 0,
		'order' => null,
		'orderby' => null,
		'where' => null,
		'page' => null, // 'page' and 'offset' should not be used together.
		'select' => 'items.*,
			product.max_price AS product_price,
			product.sku AS product_sku,
			product.stock_status AS product_stock_status,
			product.stock_quantity AS product_stock_quantity,
			posts.post_title AS product_name,
			posts.post_status AS product_status,
			postmeta.meta_value AS product_or_variation_image_id,
			postmeta2.meta_value AS product_image_id
			',
		'return' => 'items', // other values are query, raw_results
		'cache_key' => null,
	];

	$p_args = wp_parse_args( $args, $defaults );
	$cache_key = $p_args[ 'cache_key' ];

	$items_data = !empty( $cache_key ) ? wp_cache_get( $cache_key ) : false;

	if ( false === $items_data ) {

		$limit = max( 0, ( int ) $p_args[ 'limit' ] );

		if ( $p_args[ 'page' ] ) {
			$p_args[ 'offset' ] = max( 0, (( int ) $p_args[ 'page' ] - 1) * $limit );
		}

		$select_sql = $p_args[ 'select' ];
		$where_sql = $p_args[ 'where' ] ?? '';
		$limit_sql = $limit ? $wpdb->prepare( "LIMIT %d", $limit ) : '';
		$offset_sql = $limit ? 'OFFSET ' . $p_args[ 'offset' ] : '';
		$order_sql = $p_args[ 'orderby' ] ? esc_sql( "ORDER BY {$p_args[ 'orderby' ]} {$p_args[ 'order' ]}" ) : '';
		$sql = "SELECT $select_sql "
			. "FROM {$wpdb->prefix}nmgr_wishlist_items AS items "
			. "LEFT JOIN $wpdb->posts AS posts "
			. "ON items.product_or_variation_id = posts.ID "
			. "LEFT JOIN $wpdb->postmeta AS postmeta "
			. "ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_thumbnail_id' "
			. "LEFT JOIN $wpdb->postmeta AS postmeta2 "
			. "ON IF(posts.post_parent != '', posts.post_parent, posts.ID) = postmeta2.post_id AND postmeta2.meta_key = '_thumbnail_id' "
			. "LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup AS product "
			. "ON posts.ID = product.product_id "
			. "WHERE 1=1 $where_sql "
			. "$order_sql "
			. "$limit_sql "
			. "$offset_sql";

		if ( 'query' === $p_args[ 'return' ] ) {
			return $sql;
		}

		// Items from database are returned as objects
		$items_data = $wpdb->get_results( $sql );

		if ( !empty( $cache_key ) ) {
			wp_cache_set( $cache_key, $items_data );
		}
	}

	return $items_data;
}

/**
 * Put relevant wishlist data into the global variable 'nmgr'
 *
 * This function is hooked into 'the_post' to make the global variable available on an individual
 * post basis in loops. This can be helpful, for example, in retrieving the right wishlist id for
 * the current post if the current 'post' query is not the main query
 *
 * The function is also hooked into 'add_meta_boxes' to make the global variable available
 * in the admin edit screen for a wishlist.
 *
 * Finally the function is hooked into 'template_redirect' to make the global variable available
 * generally for the frontend but also for enqueued scripts which use 'wp_enqueue_script' hook,
 * as this hook runs before 'the_post' hook
 *
 * The global variable is only available on registered nmgr pages and
 * it would not be available before these hooks are called by wordpress
 *
 * @return Object|\stdClass
 */
function nmgr_set_global_var() {
	_deprecated_function( __FUNCTION__, '4.11' );
	$GLOBALS[ 'nmgr' ] = new \stdClass();
	$GLOBALS[ 'nmgr' ]->is_wishlist_page = is_nmgr_wishlist_page();
	$GLOBALS[ 'nmgr' ]->is_wishlist_page_single = is_nmgr_wishlist();
	$GLOBALS[ 'nmgr' ]->is_admin = is_nmgr_admin();
}
