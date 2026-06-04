<?php

namespace NMGR\Deprecated;

use NMGR\Deprecated\RemovedTemplatesNotice;
use NMGR\Deprecated\Shortcodes;
use NMGR\Deprecated\Mailer;

defined( 'ABSPATH' ) || exit;

class Deprecated {

	public static function run() {
		self::include_deprecated_classes();

		$classes = [
			RemovedTemplatesNotice::class,
			Shortcodes::class,
			Mailer::class,
		];

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				$class::run();
			}
		}

		if ( nmgr_overridden( 'account/items.php' ) ) {
			add_action( 'nmgr_after_items', array( __CLASS__, 'after_items_show_items_total_cost' ), 10, 4 );
			add_action( 'nmgr_after_items', array( __CLASS__, 'after_items_show_items_actions' ), 20, 4 );
			add_action( 'nmgr_after_items_actions', array( __CLASS__, 'show_add_items_button' ) );
		}
	}

	private static function include_deprecated_classes() {
		include_once __DIR__ . '/Classes/class-nmgr-wishlist-template.php';
	}

	public static function after_items_show_items_total_cost( $items, $wishlist, $items_args, $view ) {
		echo $view->get_totals_template();
	}

	public static function after_items_show_items_actions( $items, $wishlist, $items_args, $view ) {
		$file = 'account/items/items-actions.php';
		if ( nmgr_overridden( $file ) ) {
			nmgr_overridden_notice( $file, '4.6.0' );

			nmgr_template( 'account/items/items-actions.php',
				array(
					'items' => $items,
					'wishlist' => $wishlist,
					'items_args' => $items_args,
					'view' => $view,
			) );

			return;
		}
		?>
		<div class="nmgr-after-table-row items-actions">
			<p>
				<?php do_action_deprecated( 'nmgr_after_items_actions', [ $items, $wishlist, $items_args, $view ], '4.11' ); ?>
			</p>
		</div>
		<?php
	}

	public static function show_add_items_button() {
		if ( is_nmgr_admin() ) {
			echo '<button type="button" '
			. 'data-nmgr_post_action="show_add_items_dialog" '
			. 'class="button nmgr-post-action">'
			. esc_html( nmgr()->is_pro ?
					__( 'Add item(s)', 'nm-gift-registry' ) :
					__( 'Add item(s)', 'nm-gift-registry-lite' )
			)
			. '</button>';
		}
	}

}
