<?php

defined( 'ABSPATH' ) || exit;

/* translators: %s: wishlist type title */
echo sprintf( esc_html( ucwords( __( '%s details', 'nm-gift-registry' ) ) ), esc_html( nmgr_get_type_title() ) )
 . "\n\n";

foreach ( $wishlist_details as $label => $value ) {
	echo wp_kses_post( $label ) . ': ' . wp_kses_post( $value ) . "\n";
}
