<?php 
if ( !class_exists('Woocommerce') ) { 
	return false;
}
global $woocommerce;
$funio_settings = funio_global_settings();
$cart_layout = funio_get_config('cart-layout','dropdown');
?>
<div class="dropdown mini-cart top-cart" data-text_added="<?php echo esc_html__("Product was added to cart successfully!","funio"); ?>">
	<div class="remove-cart-shadow"></div>
  <a class="dropdown-toggle cart-icon" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	<div class="icons-cart"><i class="icon-cart"></i><span class="cart-count"><?php echo esc_attr($woocommerce->cart->cart_contents_count); ?></span></div>
  </a>
  <div class="dropdown-menu cart-popup">
	<div class="remove-cart">
		<a class="dropdown-toggle cart-remove" data-toggle="dropdown" data-hover="dropdown" data-delay="0" href="#" title="<?php esc_attr_e('View your shopping cart', 'funio'); ?>">
			<?php echo esc_html__("Close","funio") ?><i class="icon_close"></i>
		</a>
	</div>
	<div class="top-total-cart"><?php echo esc_html__("Shopping Cart","funio"); ?>(<?php echo esc_attr($woocommerce->cart->cart_contents_count); ?>)</div>
	<?php woocommerce_mini_cart(); ?>
  </div>
</div>