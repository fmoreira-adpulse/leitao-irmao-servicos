<?php
/**
 * True/False field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<input type="hidden" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['id'] ); ?>" value="<?php echo esc_attr( ( $field['value'] ? 'yes' : 'no' ) ); ?>" />
<a href="#<?php echo esc_attr( $field['id'] ); ?>" class="wc-bogo-true-false"><span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( ( $field['value'] ? 'enabled' : 'disabled' ) ); ?>" aria-label="<?php echo ( empty( $field['label'] ) ? '' : esc_attr( $field['label'] ) ); ?>"></span></a>
<?php if ( ! empty( $field['message'] ) ) : ?>
<span class="message"><?php echo esc_html( $field['message'] ); ?></span>
<?php endif; ?>
