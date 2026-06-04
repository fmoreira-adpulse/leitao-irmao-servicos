<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Payment gateway restrictions and gateway-wise APD mode mapping.
 */
class APD_Gateway_Rules {

    /**
     * Cart item keys used to preserve the shopper's original APD selection.
     */
    const ORIGINAL_PAY_DEPOSIT_KEY = 'apd_gateway_original_pay_deposit';
    const ORIGINAL_PLAN_KEY        = 'apd_gateway_original_selected_plan';
    const ORIGINAL_CUSTOM_KEY      = 'apd_gateway_original_custom_deposit';

    public function __construct() {
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_gateways' ) );
        add_filter( 'apd_save_settings', array( $this, 'save_settings' ), 10, 3 );

        add_action( 'woocommerce_checkout_update_order_review', array( $this, 'capture_checkout_gateway' ) );
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_gateway_checkout_mode' ), 5 );
        add_action( 'template_redirect', array( $this, 'restore_cart_outside_checkout' ), 5 );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_gateway_rule_to_order' ), 20, 2 );
        add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_gateway_mode_notice' ), 5 );

        // Block checkout (Store API) support.
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'save_gateway_rule_to_order_from_store_api' ), 20, 2 );
        add_filter( 'woocommerce_rest_checkout_available_payment_gateways', array( $this, 'filter_gateways_rest' ), 10, 2 );

        add_filter( 'apd_deposit_type', array( $this, 'override_deposit_type' ), 20, 2 );
        add_filter( 'apd_deposit_value', array( $this, 'override_deposit_value' ), 20, 3 );
        add_filter( 'apd_deposit_amount', array( $this, 'override_deposit_amount' ), 50, 5 );
        add_action( 'wp_footer', array( $this, 'render_checkout_refresh_script' ), 50 );
    }

    /**
     * Filter available gateways based on deposit/balance context.
     */
    public function filter_gateways( $gateways ) {
        if ( ! is_checkout() ) {
            return $gateways;
        }

        $deposit_gateways = apd_get_option( 'deposit_gateways', array() );
        $balance_gateways = apd_get_option( 'balance_gateways', array() );

        if ( empty( $deposit_gateways ) && empty( $balance_gateways ) ) {
            return $gateways;
        }

        $is_balance_payment = false;
        if ( isset( $_GET['pay_for_order'] ) && isset( $_GET['key'] ) ) {
            // get_query_var('order-pay') works for legacy CPT; under HPOS the order ID
            // comes from the 'id' query var or the URL path segment.
            $order_id = absint( get_query_var( 'order-pay' ) )
                ?: absint( $_GET['id'] ?? 0 );
            if ( $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order && APD_Order::is_deposit_order( $order ) ) {
                    $is_balance_payment = true;
                }
            }
        }

        $deposit_engine     = APD_Deposit::instance();
        $is_deposit_payment = ! $is_balance_payment && $deposit_engine->cart_has_deposit();

        if ( $is_deposit_payment && ! empty( $deposit_gateways ) ) {
            foreach ( $gateways as $id => $gateway ) {
                if ( ! in_array( $id, $deposit_gateways, true ) ) {
                    unset( $gateways[ $id ] );
                }
            }
        }

        if ( $is_balance_payment && ! empty( $balance_gateways ) ) {
            foreach ( $gateways as $id => $gateway ) {
                if ( ! in_array( $id, $balance_gateways, true ) ) {
                    unset( $gateways[ $id ] );
                }
            }
        }

        return $gateways;
    }

    /**
     * Save gateway settings.
     */
    public function save_settings( $settings, $tab, $data ) {
        if ( 'gateway-rules' !== $tab ) {
            return $settings;
        }

        $settings['deposit_gateways'] = isset( $data['deposit_gateways'] ) ? array_map( 'sanitize_text_field', (array) $data['deposit_gateways'] ) : array();
        $settings['balance_gateways'] = isset( $data['balance_gateways'] ) ? array_map( 'sanitize_text_field', (array) $data['balance_gateways'] ) : array();

        $saved_modes = array();
        $mode_input  = isset( $data['gateway_checkout_mode'] ) ? (array) $data['gateway_checkout_mode'] : array();
        $value_input = isset( $data['gateway_checkout_value'] ) ? (array) $data['gateway_checkout_value'] : array();
        $plan_input  = isset( $data['gateway_checkout_plan'] ) ? (array) $data['gateway_checkout_plan'] : array();

        foreach ( $mode_input as $gateway_id => $mode ) {
            $gateway_id = sanitize_text_field( $gateway_id );
            $mode       = sanitize_text_field( $mode );

            if ( ! $gateway_id || ! in_array( $mode, array( 'inherit', 'full', 'fixed', 'percentage', 'payment_plan', 'min_max' ), true ) ) {
                continue;
            }

            $saved_modes[ $gateway_id ] = array(
                'mode'    => $mode,
                'value'   => floatval( $value_input[ $gateway_id ] ?? 0 ),
                'plan_id' => sanitize_text_field( $plan_input[ $gateway_id ] ?? '' ),
            );
        }

        $settings['gateway_checkout_modes'] = $saved_modes;

        return $settings;
    }

    /**
     * Capture the selected checkout gateway during classic checkout refreshes.
     *
     * @param string $posted_data Serialized checkout data.
     */
    public function capture_checkout_gateway( $posted_data ) {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        parse_str( $posted_data, $parsed );

        if ( empty( $parsed['payment_method'] ) ) {
            return;
        }

        WC()->session->set( 'chosen_payment_method', sanitize_text_field( $parsed['payment_method'] ) );
    }

    /**
     * Apply gateway APD mode to the cart while totals are calculated.
     *
     * @param WC_Cart $cart Cart instance.
     */
    public function apply_gateway_checkout_mode( $cart ) {
        if ( ! $cart instanceof WC_Cart ) {
            return;
        }

        if ( is_admin() && ! wp_doing_ajax() ) {
            return;
        }

        if ( ! $this->is_checkout_context() ) {
            return;
        }

        $gateway_id = $this->get_selected_gateway_id();
        $rule       = $this->get_gateway_mode_rule( $gateway_id );

        if ( empty( $rule ) || empty( $rule['mode'] ) || 'inherit' === $rule['mode'] ) {
            $this->restore_original_cart_selection( $cart, true );
            return;
        }

        $deposit_engine = APD_Deposit::instance();
        $cart_changed   = false;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = intval( $cart_item['product_id'] ?? 0 );

            if ( ! $product_id || ! $deposit_engine->is_deposit_enabled( $product_id ) ) {
                continue;
            }

            if ( ! array_key_exists( self::ORIGINAL_PAY_DEPOSIT_KEY, $cart_item ) ) {
                $cart->cart_contents[ $cart_item_key ][ self::ORIGINAL_PAY_DEPOSIT_KEY ] = $cart_item['apd_pay_deposit'] ?? '__missing__';
                $cart_changed = true;
            }

            if ( ! array_key_exists( self::ORIGINAL_PLAN_KEY, $cart_item ) ) {
                $cart->cart_contents[ $cart_item_key ][ self::ORIGINAL_PLAN_KEY ] = $cart_item['apd_selected_plan'] ?? '__missing__';
                $cart_changed = true;
            }

            if ( ! array_key_exists( self::ORIGINAL_CUSTOM_KEY, $cart_item ) ) {
                $cart->cart_contents[ $cart_item_key ][ self::ORIGINAL_CUSTOM_KEY ] = isset( $cart_item['apd_custom_deposit'] ) ? floatval( $cart_item['apd_custom_deposit'] ) : '__missing__';
                $cart_changed = true;
            }

            $cart->cart_contents[ $cart_item_key ]['apd_gateway_mode']  = $rule['mode'];
            $cart->cart_contents[ $cart_item_key ]['apd_gateway_value'] = floatval( $rule['value'] ?? 0 );
            $cart_changed = true;

            if ( 'full' === $rule['mode'] ) {
                $cart->cart_contents[ $cart_item_key ]['apd_pay_deposit'] = 'no';
                unset( $cart->cart_contents[ $cart_item_key ]['apd_selected_plan'] );
                unset( $cart->cart_contents[ $cart_item_key ]['apd_gateway_plan'] );
                unset( $cart->cart_contents[ $cart_item_key ]['apd_custom_deposit'] );
                continue;
            }

            $cart->cart_contents[ $cart_item_key ]['apd_pay_deposit'] = 'yes';

            if ( 'min_max' === $rule['mode'] ) {
                $line_total = floatval( $cart_item['line_total'] ?? 0 );
                $quantity   = max( 1, intval( $cart_item['quantity'] ?? 1 ) );
                $unit_price = $quantity > 0 ? ( $line_total / $quantity ) : 0;

                if ( $unit_price <= 0 ) {
                    $product    = wc_get_product( $product_id );
                    $unit_price = $product ? floatval( $product->get_price() ) : 0;
                }

                $original_custom = $cart_item[ self::ORIGINAL_CUSTOM_KEY ] ?? '__missing__';
                $custom_amount   = isset( $cart_item['apd_custom_deposit'] ) ? floatval( $cart_item['apd_custom_deposit'] ) : null;

                if ( null === $custom_amount && '__missing__' !== $original_custom ) {
                    $custom_amount = floatval( $original_custom );
                }

                if ( null === $custom_amount ) {
                    $bounds        = $deposit_engine->get_min_max_bounds( $product_id, $unit_price );
                    $custom_amount = $bounds['min'];
                }

                $cart->cart_contents[ $cart_item_key ]['apd_custom_deposit'] = $deposit_engine->sanitize_custom_deposit( $product_id, $custom_amount, $unit_price );
            } else {
                unset( $cart->cart_contents[ $cart_item_key ]['apd_custom_deposit'] );
            }

            if ( 'payment_plan' === $rule['mode'] ) {
                $plan_id = $this->resolve_plan_for_product( $product_id, $rule['plan_id'] ?? '' );

                if ( $plan_id ) {
                    $cart->cart_contents[ $cart_item_key ]['apd_selected_plan'] = $plan_id;
                    $cart->cart_contents[ $cart_item_key ]['apd_gateway_plan']  = $plan_id;
                } else {
                    unset( $cart->cart_contents[ $cart_item_key ]['apd_gateway_plan'] );
                }
            } else {
                unset( $cart->cart_contents[ $cart_item_key ]['apd_selected_plan'] );
                unset( $cart->cart_contents[ $cart_item_key ]['apd_gateway_plan'] );
            }
        }

        if ( $cart_changed ) {
            $cart->set_session();
        }
    }

    /**
     * Restore original APD cart choices outside checkout.
     */
    public function restore_cart_outside_checkout() {
        if ( is_admin() || ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        if ( $this->is_checkout_context() ) {
            return;
        }

        $this->restore_original_cart_selection( WC()->cart, true );
    }

    /**
     * Override deposit type from gateway rule.
     *
     * @param string $type       Effective type.
     * @param int    $product_id Product ID.
     * @return string
     */
    public function override_deposit_type( $type, $product_id ) {
        $rule = $this->get_active_rule_for_product( $product_id );

        if ( empty( $rule ) || empty( $rule['mode'] ) ) {
            return $type;
        }

        if ( in_array( $rule['mode'], array( 'fixed', 'percentage', 'min_max' ), true ) ) {
            return $rule['mode'];
        }

        if ( 'payment_plan' === $rule['mode'] && $this->resolve_plan_for_product( $product_id, $rule['plan_id'] ?? '' ) ) {
            return 'payment_plan';
        }

        return $type;
    }

    /**
     * Override deposit value from gateway rule.
     *
     * @param float|int|string $value      Effective value.
     * @param int              $product_id Product ID.
     * @param string           $type       Effective type.
     * @return float|int|string
     */
    public function override_deposit_value( $value, $product_id, $type ) {
        $rule = $this->get_active_rule_for_product( $product_id );

        if ( empty( $rule ) || empty( $rule['mode'] ) ) {
            return $value;
        }

        if ( in_array( $rule['mode'], array( 'fixed', 'percentage' ), true ) && floatval( $rule['value'] ?? 0 ) > 0 ) {
            return floatval( $rule['value'] );
        }

        return $value;
    }

    /**
     * Override the final deposit amount when gateway mode needs custom logic.
     */
    public function override_deposit_amount( $deposit, $product_id, $price, $type, $value ) {
        $rule = $this->get_active_rule_for_product( $product_id );

        if ( empty( $rule ) || empty( $rule['mode'] ) ) {
            return $deposit;
        }

        if ( 'min_max' === $rule['mode'] ) {
            return $this->get_min_max_gateway_deposit( $product_id, $price );
        }

        if ( 'payment_plan' === $rule['mode'] ) {
            $plan_id = $this->resolve_plan_for_product( $product_id, $rule['plan_id'] ?? '' );

            if ( $plan_id && class_exists( 'APD_Payment_Plans' ) ) {
                $plan = APD_Payment_Plans::get_plan( $plan_id );

                if ( $plan ) {
                    $schedule = APD_Payment_Plans::build_schedule( $plan, $price );

                    if ( ! empty( $schedule[0]['amount'] ) ) {
                        return min( floatval( $schedule[0]['amount'] ), floatval( $price ) );
                    }
                }
            }
        }

        return $deposit;
    }

    /**
     * Save applied gateway rule to order meta.
     *
     * @param WC_Order $order Order object.
     */
    public function save_gateway_rule_to_order( $order, $data = array() ) {
        $gateway_id = $this->get_selected_gateway_id();
        $rule       = $this->get_gateway_mode_rule( $gateway_id );

        if ( ! $order || empty( $gateway_id ) || empty( $rule ) || empty( $rule['mode'] ) || 'inherit' === $rule['mode'] ) {
            return;
        }

        $order->update_meta_data( '_apd_gateway_rule_gateway', $gateway_id );
        $order->update_meta_data( '_apd_gateway_rule_mode', $rule['mode'] );

        if ( ! empty( $rule['value'] ) ) {
            $order->update_meta_data( '_apd_gateway_rule_value', floatval( $rule['value'] ) );
        }

        if ( ! empty( $rule['plan_id'] ) ) {
            $order->update_meta_data( '_apd_gateway_rule_plan', sanitize_text_field( $rule['plan_id'] ) );
        }
    }

    /**
     * Save applied gateway rule to order meta during Store API (block) checkout.
     *
     * @param WC_Order        $order   Order object.
     * @param WP_REST_Request $request Store API request.
     */
    public function save_gateway_rule_to_order_from_store_api( $order, $request ) {
        if ( ! $order || ! $request ) {
            return;
        }

        $body = $request->get_json_params();
        $gateway_id = isset( $body['payment_method'] ) ? sanitize_text_field( $body['payment_method'] ) : '';

        if ( ! $gateway_id ) {
            $gateway_id = $order->get_payment_method();
        }

        $rule = $this->get_gateway_mode_rule( $gateway_id );

        if ( ! $rule || empty( $rule['mode'] ) || 'inherit' === $rule['mode'] ) {
            return;
        }

        $order->update_meta_data( '_apd_gateway_rule_gateway', $gateway_id );
        $order->update_meta_data( '_apd_gateway_rule_mode', $rule['mode'] );

        if ( ! empty( $rule['value'] ) ) {
            $order->update_meta_data( '_apd_gateway_rule_value', floatval( $rule['value'] ) );
        }

        if ( ! empty( $rule['plan_id'] ) ) {
            $order->update_meta_data( '_apd_gateway_rule_plan', sanitize_text_field( $rule['plan_id'] ) );
        }
    }

    /**
     * Filter payment gateways for REST/Store API (block checkout).
     *
     * @param array           $gateways Available gateways.
     * @param WC_Order|null   $order    Order object (if applicable).
     * @return array
     */
    public function filter_gateways_rest( $gateways, $order = null ) {
        $deposit_gateways = apd_get_option( 'deposit_gateways', array() );
        $balance_gateways = apd_get_option( 'balance_gateways', array() );

        if ( empty( $deposit_gateways ) && empty( $balance_gateways ) ) {
            return $gateways;
        }

        $is_balance_payment = false;
        if ( $order && APD_Order::is_deposit_order( $order ) ) {
            $is_balance_payment = true;
        }

        $deposit_engine     = APD_Deposit::instance();
        $is_deposit_payment = ! $is_balance_payment && $deposit_engine->cart_has_deposit();

        if ( $is_deposit_payment && ! empty( $deposit_gateways ) ) {
            foreach ( $gateways as $id => $gateway ) {
                if ( ! in_array( $id, $deposit_gateways, true ) ) {
                    unset( $gateways[ $id ] );
                }
            }
        }

        if ( $is_balance_payment && ! empty( $balance_gateways ) ) {
            foreach ( $gateways as $id => $gateway ) {
                if ( ! in_array( $id, $balance_gateways, true ) ) {
                    unset( $gateways[ $id ] );
                }
            }
        }

        return $gateways;
    }

    /**
     * Trigger checkout refresh when the customer changes gateway on classic checkout.
     */
    public function render_checkout_refresh_script() {
        if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
            return;
        }
        ?>
        <script>
        (function($){
            if (!$ || typeof $(document.body).on !== 'function') {
                return;
            }
            $(document.body).on('change', 'input[name="payment_method"]', function(){
                $(document.body).trigger('update_checkout');
            });
        })(window.jQuery);
        </script>
        <?php
    }

    /**
     * Show the applied gateway mode notice on classic checkout.
     */
    public function render_gateway_mode_notice() {
        if ( ! $this->is_checkout_context() ) {
            return;
        }

        $gateway_id = $this->get_selected_gateway_id();
        $rule       = $this->get_gateway_mode_rule( $gateway_id );

        if ( empty( $rule ) || empty( $rule['mode'] ) || 'inherit' === $rule['mode'] ) {
            return;
        }

        $message = '';

        switch ( $rule['mode'] ) {
            case 'full':
                $message = __( 'The selected payment gateway uses full payment only for this order.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
                break;
            case 'fixed':
                $message = sprintf(
                    /* translators: %s: fixed amount */
                    __( 'The selected payment gateway applies a fixed deposit of %s.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                    wp_strip_all_tags( wc_price( floatval( $rule['value'] ?? 0 ) ) )
                );
                break;
            case 'percentage':
                $message = sprintf(
                    /* translators: %s: percentage */
                    __( 'The selected payment gateway applies a %s%% deposit rule.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                    wc_format_localized_decimal( floatval( $rule['value'] ?? 0 ) )
                );
                break;
            case 'payment_plan':
                $message = __( 'The selected payment gateway applies a payment plan for this order.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
                break;
            case 'min_max':
                $message = __( 'The selected payment gateway applies the product min / max deposit rules. If the customer already chose a custom deposit amount, that amount is kept within the allowed range.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
                break;
        }

        if ( ! $message ) {
            return;
        }

        echo '<div class="woocommerce-info apd-gateway-mode-notice" style="margin:0 0 16px;">' . esc_html( $message ) . '</div>';
    }

    /**
     * Resolve the selected gateway ID from session.
     *
     * @return string
     */
    private function get_selected_gateway_id() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return '';
        }

        $gateway_id = WC()->session->get( 'chosen_payment_method', '' );

        return is_string( $gateway_id ) ? sanitize_text_field( $gateway_id ) : '';
    }

    /**
     * Read gateway mode rule settings.
     *
     * @param string $gateway_id Gateway ID.
     * @return array<string,mixed>
     */
    private function get_gateway_mode_rule( $gateway_id ) {
        if ( empty( $gateway_id ) ) {
            return array();
        }

        $rules = apd_get_option( 'gateway_checkout_modes', array() );
        $rule  = isset( $rules[ $gateway_id ] ) && is_array( $rules[ $gateway_id ] ) ? $rules[ $gateway_id ] : array();

        if ( empty( $rule['mode'] ) ) {
            return array();
        }

        return array(
            'mode'    => sanitize_text_field( $rule['mode'] ),
            'value'   => floatval( $rule['value'] ?? 0 ),
            'plan_id' => sanitize_text_field( $rule['plan_id'] ?? '' ),
        );
    }

    /**
     * Get the active gateway rule for a product during checkout.
     *
     * @param int $product_id Product ID.
     * @return array<string,mixed>
     */
    private function get_active_rule_for_product( $product_id ) {
        if ( ! $this->is_checkout_context() || ! $product_id ) {
            return array();
        }

        $rule = $this->get_gateway_mode_rule( $this->get_selected_gateway_id() );

        if ( empty( $rule ) || empty( $rule['mode'] ) || in_array( $rule['mode'], array( 'inherit', 'full' ), true ) ) {
            return array();
        }

        return $rule;
    }

    /**
     * Whether the current request is on checkout/order-pay.
     *
     * @return bool
     */
    private function is_checkout_context() {
        return function_exists( 'is_checkout' ) && ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) );
    }

    /**
     * Restore original cart APD choices.
     *
     * @param WC_Cart $cart        Cart object.
     * @param bool    $save_cart   Whether to persist the restored cart.
     */
    private function restore_original_cart_selection( $cart, $save_cart = false ) {
        if ( ! $cart instanceof WC_Cart ) {
            return;
        }

        $cart_changed = false;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( array_key_exists( self::ORIGINAL_PAY_DEPOSIT_KEY, $cart_item ) ) {
                $original_pay = $cart_item[ self::ORIGINAL_PAY_DEPOSIT_KEY ];

                if ( '__missing__' === $original_pay ) {
                    unset( $cart->cart_contents[ $cart_item_key ]['apd_pay_deposit'] );
                } else {
                    $cart->cart_contents[ $cart_item_key ]['apd_pay_deposit'] = $original_pay;
                }

                unset( $cart->cart_contents[ $cart_item_key ][ self::ORIGINAL_PAY_DEPOSIT_KEY ] );
                $cart_changed = true;
            }

            if ( array_key_exists( self::ORIGINAL_PLAN_KEY, $cart_item ) ) {
                $original_plan = $cart_item[ self::ORIGINAL_PLAN_KEY ];

                if ( '__missing__' === $original_plan ) {
                    unset( $cart->cart_contents[ $cart_item_key ]['apd_selected_plan'] );
                } else {
                    $cart->cart_contents[ $cart_item_key ]['apd_selected_plan'] = $original_plan;
                }

                unset( $cart->cart_contents[ $cart_item_key ][ self::ORIGINAL_PLAN_KEY ] );
                $cart_changed = true;
            }

            if ( array_key_exists( self::ORIGINAL_CUSTOM_KEY, $cart_item ) ) {
                $original_custom = $cart_item[ self::ORIGINAL_CUSTOM_KEY ];

                if ( '__missing__' === $original_custom ) {
                    unset( $cart->cart_contents[ $cart_item_key ]['apd_custom_deposit'] );
                } else {
                    $cart->cart_contents[ $cart_item_key ]['apd_custom_deposit'] = floatval( $original_custom );
                }

                unset( $cart->cart_contents[ $cart_item_key ][ self::ORIGINAL_CUSTOM_KEY ] );
                $cart_changed = true;
            }

            foreach ( array( 'apd_gateway_mode', 'apd_gateway_value', 'apd_gateway_plan' ) as $temp_key ) {
                if ( array_key_exists( $temp_key, $cart_item ) ) {
                    unset( $cart->cart_contents[ $cart_item_key ][ $temp_key ] );
                    $cart_changed = true;
                }
            }
        }

        if ( $cart_changed && $save_cart ) {
            $cart->set_session();
        }
    }

    /**
     * Resolve a plan for a product.
     *
     * @param int    $product_id      Product ID.
     * @param string $preferred_plan  Preferred plan ID.
     * @return string
     */
    private function resolve_plan_for_product( $product_id, $preferred_plan = '' ) {
        if ( ! class_exists( 'APD_Payment_Plans' ) ) {
            return '';
        }

        $plans = APD_Payment_Plans::get_plans_for_product( $product_id );

        if ( empty( $plans ) ) {
            return '';
        }

        if ( $preferred_plan && isset( $plans[ $preferred_plan ] ) ) {
            return (string) $preferred_plan;
        }

        $plan_ids = array_keys( $plans );

        return ! empty( $plan_ids ) ? (string) reset( $plan_ids ) : '';
    }

    /**
     * Calculate the min/max gateway deposit using the configured min/max rules.
     *
     * If a custom deposit is already stored for the cart item, the cart/payment
     * summary layer will use that amount. This fallback keeps direct calculations
     * aligned by using the minimum allowed deposit when no custom amount exists.
     *
     * @param int   $product_id Product ID.
     * @param float $price      Effective line/unit price.
     * @return float
     */
    private function get_min_max_gateway_deposit( $product_id, $price ) {
        $bounds = APD_Deposit::instance()->get_min_max_bounds( $product_id, $price );

        return min( floatval( $price ), floatval( $bounds['min'] ) );
    }
}
