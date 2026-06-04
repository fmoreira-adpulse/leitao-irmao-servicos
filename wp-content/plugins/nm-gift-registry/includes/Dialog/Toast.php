<?php

namespace NMGR\Dialog;

use NMGR\Dialog\Modal;

class Toast extends Modal {

	/**
	 * The type should be 'toast' but to prevent conflict with bootstrap .toast styles,
	 * we call it notice as it is simply used for notifications.
	 */
	protected $type = 'notice';
	protected $notice_type = 'success';

	public function __construct() {
		$this->options[ 'type' ] = 'toast';
		$this->options[ 'width' ] = 350;
		$this->options[ 'show' ] = 100;
		$this->options[ 'hide' ] = 100;
		$this->options[ 'appendTo' ] = '.nmgr-toaster';
		$this->options[ 'modal' ] = false;
	}

	protected function styles() {
		parent::styles();

		$notice_colors = [
			'notice' => '#1e85be',
			'error' => '#b81c23',
			'success' => '#0f834d',
		];
		?>
		<style>
			.nmgr-toaster {
				position: fixed;
				left: 0;
				bottom: 0;
				margin: 1rem;
				width: max-content;
				max-width: 95vw;
				z-index: 999999;
				display: table;
			}

			.nmgr-toaster > :not(:last-child) {
				margin-bottom: .75rem;
			}

			.nmgr-toaster > * {
				left: 0 !important;
				top: 0 !important;
				position: relative !important;
			}

			.nmgr-toaster .button.wc-forward {
				display: none;
			}

			@media (max-width: <?php echo $this->options[ 'width' ]; ?>px) {
				.nmgr-toaster {
					margin: 0;
					left: 50%;
					transform: translateX(-50%);
				}

				<?php echo $this->selector(); ?> {
					max-width: 95vw;
				}
			}

			<?php echo $this->selector(); ?> {
				border: none; /* for admin */
				background: <?php echo $notice_colors[ $this->notice_type ]; ?>
			}

			<?php echo $this->selector( "#$this->id" ); ?> {
				padding: 11px 25px 11px 11px;
				color: white;
				position: static; /* for admin */
			}

			<?php echo $this->selector( '.ui-dialog-titlebar' ); ?> {
				border: none;
				padding: 0; /* for admin */
			}

			<?php echo $this->selector( '.ui-dialog-title' ); ?> {
				width: 0; /* for admin */
			}

			<?php echo $this->selector( '.ui-button.ui-dialog-titlebar-close' ); ?> {
				color: black;
			}
		</style>
		<?php
	}

	/**
	 * Set a toast notice type to get.
	 * @param string $type The type of notice to get. Values are notice, error and success.
	 * Default is success.
	 * @param boolean $style Whether to style the toast according to the notice type. Default true.
	 */
	public function set_notice_type( $type ) {
		$this->notice_type = $type;
	}

}
