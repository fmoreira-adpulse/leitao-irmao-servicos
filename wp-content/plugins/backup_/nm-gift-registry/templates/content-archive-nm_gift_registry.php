<?php
/**
 * The template for displaying a single wishlist article in archive templates such as search results, categories,
 * tags, and archives.
 * @deprecated since version 4.7.0
 * @version 4.7.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;

nmgr_overridden_notice( __FILE__, '4.7.0' );

$wishlist = nmgr_get_wishlist( get_the_ID() );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'nmgr-archive-content nmgr-background-color' ); ?>>
	<?php if ( nmgr()->is_pro && ($wishlist->get_thumbnail() || $wishlist->get_background_image_id()) ) : ?>
		<div class="entry-thumbnail nmgr-col">
			<a href="<?php echo esc_url( $wishlist->get_permalink() ); ?>" rel="bookmark">
				<?php
				if ( $wishlist->get_thumbnail() ) {
					echo $wishlist->get_thumbnail();
				} else {
					echo $wishlist->get_background_thumbnail();
				}
				?>
			</a>
		</div>
	<?php endif; ?>

	<div class="entry-content nmgr-col">
		<h2 class="entry-title nmgr-title">
			<a href="<?php echo esc_url( $wishlist->get_permalink() ); ?>" rel="bookmark">
				<?php echo esc_html( $wishlist->get_title() ); ?>
			</a>
		</h2>
		<?php
		if ( $wishlist->get_event_date() || $wishlist->get_full_name() ) {
			echo "<p class='nmgr-details'>";
			$wishlist->get_full_name() ? printf( '<span class="nmgr-full-name">%s</span>', esc_html( $wishlist->get_full_name() ) ) : '';
			$wishlist->get_event_date() ? printf(
						'<span class="nmgr-event-date">%1$s <span class="nmgr-date">%2$s</span></span>',
						esc_html( nmgr()->is_pro ?
								__( 'Event date:', 'nm-gift-registry' ) :
								__( 'Event date:', 'nm-gift-registry-lite' )  ),
						esc_html( nmgr_format_date( $wishlist->get_event_date() ) )
					) : '';
			echo "</p>";
		}
		?>
	</div>

	<div class="entry-action nmgr-col">
		<a href="<?php echo esc_url( $wishlist->get_permalink() ); ?>" class="button" rel="bookmark">
			<?php
			echo esc_html( nmgr()->is_pro ?
					__( 'View', 'nm-gift-registry' ) :
					__( 'View', 'nm-gift-registry-lite' )
			);
			?>
		</a>
	</div>

</article>
