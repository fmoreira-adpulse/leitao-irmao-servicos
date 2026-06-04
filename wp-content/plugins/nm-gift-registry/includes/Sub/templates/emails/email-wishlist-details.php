<?php
defined( 'ABSPATH' ) || exit;
?>


<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
	<h2><?php
/* translators: %s: wishlist type title */
printf( esc_html__( '%s details', 'nm-gift-registry' ), esc_html( nmgr_get_type_title( 'cf' ) ) );
?></h2>
	<ul>
		<?php foreach ( $wishlist_details as $label => $value ) : ?>
			<li><strong><?php echo wp_kses_post( $label ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $value ); ?></span></li>
			<?php endforeach; ?>
	</ul>
</div>
