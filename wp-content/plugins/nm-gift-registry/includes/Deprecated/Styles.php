<?php

namespace NMGR\Deprecated;

defined( 'ABSPATH' ) || exit;

class Styles {

	public static function messages() {
		ob_start();
		?>
		<style>
			.nmgr-message {
				margin-bottom: 1.875em;
			}

			.nmgr-message a:hover svg {
				fill: #666;
			}

			.nmgr-message .footer .title {
				margin-right: 1em;
			}

			.nmgr-message .footer .content {
				color: #a9a9a9;
			}

			.nmgr-message .nmgr-wrap,
			.nmgr-message .item-ordered-content {
				flex-grow: 1;
			}

			.nmgr-message,
			.nmgr-message .header,
			.nmgr-message .footer {
				display: flex;
			}

			.nmgr-message .header {
				justify-content: space-between;
				margin-bottom: 2px;
				font-size: smaller;
			}

			.nmgr-message .nmgr-wrap {
				padding: 1.125em;
				background: #efefef;
				position: relative;
			}

			.nmgr-message .nmgr-wrap:after {
				content: "";
				display: block;
				position: absolute;
				bottom: -0.625em;
				left: 1.25em;
				width: 0;
				height: 0;
				border-width: 10px 10px 0 0;
				border-style: solid;
				border-color: #efefef transparent;
			}

			.nmgr-message .underline {
				border-bottom: 1px dotted;
			}
		</style>
		<?php

		return ob_get_clean();
	}

}
