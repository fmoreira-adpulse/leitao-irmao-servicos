<?php

defined( 'ABSPATH' ) || exit;

/* translators: %s: wishlist type title */
echo sprintf( esc_html__( 'A new %s has been created on your site.', 'nm-gift-registry' ), esc_html( nmgr_get_type_title() ) ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

NMGR\Sub\Mailer::show_wishlist_details( $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
