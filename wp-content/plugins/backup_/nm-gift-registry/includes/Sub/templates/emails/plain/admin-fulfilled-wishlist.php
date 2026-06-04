<?php

defined( 'ABSPATH' ) || exit;


/* translators: 1, 2: wishlist type title */
echo sprintf( esc_html__( 'A %1$s has just been fulfilled on your site. All the items in the %1$s have been fully purchased.', 'nm-gift-registry' ), esc_html( nmgr_get_type_title() ), esc_html( nmgr_get_type_title() ) ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

\NMGR\Sub\Mailer::show_wishlist_details( $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

