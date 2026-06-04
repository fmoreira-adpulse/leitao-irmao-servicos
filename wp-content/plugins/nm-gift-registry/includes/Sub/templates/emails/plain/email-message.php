<?php

defined( 'ABSPATH' ) || exit;

echo esc_html__( 'Message:', 'nm-gift-registry' ) . "\n" . wp_kses_post( wptexturize( $message ) );
?>



