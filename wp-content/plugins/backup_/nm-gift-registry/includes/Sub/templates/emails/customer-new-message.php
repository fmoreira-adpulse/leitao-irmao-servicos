<?php
defined( 'ABSPATH' ) || exit;
?>
<div style="margin-bottom: 40px;">
	<p>
		<?php
		/* translators: %s: recipient's name */
		printf( esc_html__( 'Hi %s,', 'nm-gift-registry' ), esc_html( $email->get_recipient_name() ) );
		?>
	</p>

	<p>
		<?php
		/* translators: 1: customer billing full name, 2: wishlist type title */
		printf( esc_html__( 'You have just received a message from %1$s who has ordered some items for your %2$s.', 'nm-gift-registry' ),
			'<strong>' . esc_html( $order_customer_name ) . '</strong>',
			esc_html( nmgr_get_type_title() )
		);
		?>
	</p>

	<?php \NMGR\Sub\Mailer::show_message( $email ); ?>

	<?php if ( !empty( $message_object->items_ordered ) ) : ?>
		<h3><?php esc_html_e( 'Items ordered', 'nm-gift-registry' ); ?></h3>
		<ul>
			<?php foreach ( $message_object->items_ordered as $item ) : ?>
				<li><?php echo "{$item[ 'name' ]} &times; {$item[ 'quantity' ]}"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped           ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>

<?php
