<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="no-results not-found">
	<div class="page-header">
		<h2 class="page-nothing"><?php _e( 'Nothing Found', 'funio' ); ?></h2>
	</div><!-- .page-header -->

	<div class="page-content">
		<?php
		if ( is_home() && current_user_can( 'publish_posts' ) ) :

			printf(
				'<p>' . wp_kses(
					/* translators: 1: Link to WP admin new post page. */
					__( 'Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'funio' ),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				) . '</p>',
				esc_url( admin_url( 'post-new.php' ) )
			);

		elseif ( is_search() ) :
			?>

			<p><?php echo esc_html__( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'funio' ); ?></p>
			<?php
			get_search_form();

		else :
			?>

			<p><?php echo esc_html__( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'funio' ); ?></p>
			<?php
			get_search_form();

		endif;
		?>
	</div><!-- .page-content -->
</div>