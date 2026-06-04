<?php
defined( 'ABSPATH' ) || exit;
?>

<p>
	<?php
	/* translators: %s: recipient's name */
	printf( esc_html__( 'Hi %s,', 'nm-gift-registry-crowdfunding' ), esc_html( $email->get_recipient_name() ) );
	?>
</p>

<p>
	<?php
	/* translators: 1: wishlist type title, 2: customer full name */
	printf( esc_html__( 'Some amounts contributed to crowdfunded items in your %1$s by %2$s have been refunded. You no longer have these amounts in your crowdfunding account.', 'nm-gift-registry-crowdfunding' ),
		esc_html( nmgr_get_type_title() ),
		'<strong>' . esc_html( $order_customer_name ) . '</strong>'
	);
	?>
</p>
<p> <?php esc_html_e( 'Here are the details of the refund including the amount remaining for the item in your crowdfunding account after the refund (Amount Left), and the amount you still need to completely fulfill the item (Amount Needed):', 'nm-gift-registry-crowdfunding' ); ?> </p>

<?php
// Table showing the details of the refund
require_once 'crowdfund-contribution-refunds.php';
