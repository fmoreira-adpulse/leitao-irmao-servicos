<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Conditional deposit rules (cart total, date range, quantity, user role).
 */
class APD_Conditional_Rules {

    public function __construct() {
        add_filter( 'apd_deposit_enabled', array( $this, 'check_conditional_rules' ), 10, 2 );
        add_filter( 'apd_deposit_amount', array( $this, 'apply_conditional_amount' ), 30, 5 );
        add_filter( 'apd_save_settings', array( $this, 'save_settings' ), 10, 3 );
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_product_notice' ), 24 );
        add_action( 'woocommerce_before_cart', array( $this, 'render_cart_notice' ), 5 );
        add_action( 'woocommerce_before_checkout_form', array( $this, 'render_checkout_notice' ), 5 );
    }

    /**
     * Check if deposit should be enabled based on conditional rules.
     */
    public function check_conditional_rules( $enabled, $product_id = 0 ) {
        if ( ! $enabled ) {
            return false;
        }

        $context = $this->get_condition_context( $product_id );

        // Cart total threshold
        $min_cart = floatval( apd_get_option( 'conditional_min_cart', 0 ) );
        if ( $min_cart > 0 && $context['cart_total'] < $min_cart ) {
            return false;
        }

        $max_cart = floatval( apd_get_option( 'conditional_max_cart', 0 ) );
        if ( $max_cart > 0 && $context['cart_total'] > $max_cart ) {
            return false;
        }

        $min_quantity = intval( apd_get_option( 'conditional_min_quantity', 0 ) );
        $max_quantity = intval( apd_get_option( 'conditional_max_quantity', 0 ) );
        if ( $min_quantity > 0 && $context['quantity'] < $min_quantity ) {
            return false;
        }

        if ( $max_quantity > 0 && $context['quantity'] > $max_quantity ) {
            return false;
        }

        // Date range
        $date_from = apd_get_option( 'conditional_date_from', '' );
        $date_to   = apd_get_option( 'conditional_date_to', '' );
        $now       = current_time( 'Y-m-d' );

        if ( $date_from && $now < $date_from ) {
            return false;
        }

        if ( $date_to && $now > $date_to ) {
            return false;
        }

        // User role
        $allowed_roles = apd_get_option( 'conditional_user_roles', array() );
        $allow_guests  = apd_get_option( 'conditional_allow_guests', 'yes' );
        if ( ! empty( $allowed_roles ) ) {
            if ( ! is_user_logged_in() ) {
                return $allow_guests === 'yes';
            }

            $user   = wp_get_current_user();
            $match  = array_intersect( $user->roles, $allowed_roles );
            if ( empty( $match ) ) {
                return false;
            }
        }

        return $enabled;
    }

    /**
     * Get cart-aware values for conditional checks.
     *
     * On single-product pages we project one unit of the current product so
     * threshold rules reflect what the customer is about to add.
     *
     * @param int $product_id Product being evaluated.
     * @return array<string,float|int>
     */
    private function get_condition_context( $product_id = 0 ) {
        $cart_total = 0.0;
        $quantity   = 0;

        if ( function_exists( 'WC' ) && WC()->cart ) {
            $cart_total = floatval( WC()->cart->get_subtotal() );

            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $quantity += max( 1, intval( $cart_item['quantity'] ?? 1 ) );
            }
        }

        // Block cart / Store API context: cart may not be hydrated via WC()->cart
        if ( $cart_total <= 0 && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            $cart_total = $this->get_cart_subtotal_from_session();
            $quantity   = $this->get_cart_quantity_from_session();
        }

        if ( $product_id && $this->should_project_single_product( $product_id ) ) {
            $product = wc_get_product( $product_id );

            if ( $product ) {
                $cart_total += floatval( $product->get_price() );
                $quantity++;
            }
        }

        return array(
            'cart_total' => $cart_total,
            'quantity'   => $quantity,
        );
    }

    /**
     * Extract cart subtotal from WC session (safer than reading php://input).
     *
     * @return float
     */
    private function get_cart_subtotal_from_session() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return 0.0;
        }

        $cart_hash = WC()->session->get( 'cart' );
        if ( ! empty( $cart_hash ) && is_array( $cart_hash ) ) {
            $subtotal = 0.0;
            foreach ( $cart_hash as $item ) {
                if ( ! empty( $item['line_total'] ) ) {
                    $subtotal += floatval( $item['line_total'] );
                }
            }
            return $subtotal;
        }

        return 0.0;
    }

    /**
     * Extract cart quantity from WC session.
     *
     * @return int
     */
    private function get_cart_quantity_from_session() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return 0;
        }

        $cart_hash = WC()->session->get( 'cart' );
        if ( ! empty( $cart_hash ) && is_array( $cart_hash ) ) {
            $quantity = 0;
            foreach ( $cart_hash as $item ) {
                $quantity += max( 1, intval( $item['quantity'] ?? 1 ) );
            }
            return $quantity;
        }

        return 0;
    }

    /**
     * Check whether we should include the viewed product in the rule context.
     *
     * @param int $product_id Product ID.
     * @return bool
     */
    private function should_project_single_product( $product_id ) {
        if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_product' ) || ! is_product() ) {
            return false;
        }

        $queried_id = get_queried_object_id();

        return $queried_id > 0 && intval( $queried_id ) === intval( $product_id );
    }

    /**
     * Apply conditional deposit amount modifications.
     */
    public function apply_conditional_amount( $deposit, $product_id, $price, $type, $value ) {
        // Quantity-based rules handled at cart level
        return $deposit;
    }

    /**
     * Render conditional notice on single product pages.
     */
    public function render_product_notice() {
        if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
            return;
        }

        global $product;

        if ( ! $product || ! $this->is_base_deposit_enabled( $product->get_id() ) ) {
            return;
        }

        // Don't show "deposit unavailable" notice when the payment plan selector
        // is already visible for this product — it would confuse customers.
        if ( apply_filters( 'apd_suppress_deposit_form', false, $product->get_id() ) ) {
            return;
        }

        $messages = $this->get_unmet_rule_messages( $product->get_id() );

        if ( empty( $messages ) ) {
            return;
        }

        echo wp_kses_post( $this->get_notice_markup( $messages ) );
    }

    /**
     * Render conditional notice on the cart page.
     */
    public function render_cart_notice() {
        if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
            return;
        }

        $messages = $this->get_cart_unavailable_messages();

        if ( empty( $messages ) ) {
            return;
        }

        echo wp_kses_post( $this->get_notice_markup( $messages ) );
    }

    /**
     * Render conditional notice on checkout.
     */
    public function render_checkout_notice() {
        if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) {
            return;
        }

        $messages = $this->get_cart_unavailable_messages();

        if ( empty( $messages ) ) {
            return;
        }

        echo wp_kses_post( $this->get_notice_markup( $messages ) );
    }

    /**
     * Get unmet conditional rule messages.
     *
     * @param int $product_id Product ID.
     * @return string[]
     */
    public function get_unmet_rule_messages( $product_id = 0 ) {
        $messages = array();
        $context  = $this->get_condition_context( $product_id );

        $min_cart = floatval( apd_get_option( 'conditional_min_cart', 0 ) );
        if ( $min_cart > 0 && $context['cart_total'] < $min_cart ) {
            $messages[] = sprintf(
                /* translators: %s: minimum cart total */
                __( 'Deposit is available when your cart reaches %s.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                wp_strip_all_tags( wc_price( $min_cart ) )
            );
        }

        $max_cart = floatval( apd_get_option( 'conditional_max_cart', 0 ) );
        if ( $max_cart > 0 && $context['cart_total'] > $max_cart ) {
            $messages[] = sprintf(
                /* translators: %s: maximum cart total */
                __( 'Deposit is available only while your cart stays at or below %s.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                wp_strip_all_tags( wc_price( $max_cart ) )
            );
        }

        $min_quantity = intval( apd_get_option( 'conditional_min_quantity', 0 ) );
        if ( $min_quantity > 0 && $context['quantity'] < $min_quantity ) {
            $messages[] = sprintf(
                /* translators: %d: minimum quantity */
                __( 'Deposit is available when your cart contains at least %d item(s).', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                $min_quantity
            );
        }

        $max_quantity = intval( apd_get_option( 'conditional_max_quantity', 0 ) );
        if ( $max_quantity > 0 && $context['quantity'] > $max_quantity ) {
            $messages[] = sprintf(
                /* translators: %d: maximum quantity */
                __( 'Deposit is available only while your cart contains %d item(s) or fewer.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                $max_quantity
            );
        }

        $date_format = get_option( 'date_format' );
        $date_from   = apd_get_option( 'conditional_date_from', '' );
        $date_to     = apd_get_option( 'conditional_date_to', '' );
        $now         = current_time( 'Y-m-d' );

        if ( $date_from && $now < $date_from ) {
            $messages[] = sprintf(
                /* translators: %s: start date */
                __( 'Deposit will be available from %s.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                wp_date( $date_format, strtotime( $date_from ) )
            );
        }

        if ( $date_to && $now > $date_to ) {
            $messages[] = sprintf(
                /* translators: %s: end date */
                __( 'Deposit was available until %s.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                wp_date( $date_format, strtotime( $date_to ) )
            );
        }

        $allowed_roles = apd_get_option( 'conditional_user_roles', array() );
        $allow_guests  = apd_get_option( 'conditional_allow_guests', 'yes' );

        if ( ! empty( $allowed_roles ) ) {
            if ( ! is_user_logged_in() && 'yes' !== $allow_guests ) {
                $messages[] = __( 'Deposit is available only for selected customer roles.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
            } elseif ( is_user_logged_in() ) {
                $user  = wp_get_current_user();
                $match = array_intersect( (array) $user->roles, (array) $allowed_roles );

                if ( empty( $match ) ) {
                    $messages[] = __( 'Deposit is not available for your current customer role.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
                }
            }
        }

        return array_values( array_unique( array_filter( $messages ) ) );
    }

    /**
     * Build notice markup for the frontend.
     *
     * @param string[] $messages Notice messages.
     * @return string
     */
    private function get_notice_markup( $messages ) {
        if ( empty( $messages ) ) {
            return '';
        }

        $html  = '<div class="woocommerce-info apd-conditional-rule-notice" style="margin:0 0 16px;">';
        $html .= '<strong>' . esc_html__( 'Deposit is currently unavailable for this purchase.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</strong>';

        if ( 1 === count( $messages ) ) {
            $html .= '<p style="margin:8px 0 0;">' . esc_html( $messages[0] ) . '</p>';
        } else {
            $html .= '<ul style="margin:8px 0 0 18px;">';
            foreach ( $messages as $message ) {
                $html .= '<li>' . esc_html( $message ) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get cart/checkout messages when at least one cart item supports deposits.
     *
     * @return string[]
     */
    private function get_cart_unavailable_messages() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return array();
        }

        $has_base_deposit_product = false;

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product_id = intval( $cart_item['product_id'] ?? 0 );

            if ( $product_id && $this->is_base_deposit_enabled( $product_id ) ) {
                $has_base_deposit_product = true;
                break;
            }
        }

        if ( ! $has_base_deposit_product ) {
            return array();
        }

        return $this->get_unmet_rule_messages( 0 );
    }

    /**
     * Check whether a product is deposit-enabled before conditional rules.
     *
     * @param int $product_id Product ID.
     * @return bool
     */
    private function is_base_deposit_enabled( $product_id ) {
        if ( ! $product_id || apd_get_option( 'enable_deposit', 'yes' ) !== 'yes' ) {
            return false;
        }

        $product_enabled = get_post_meta( $product_id, '_apd_enable_deposit', true );
        if ( 'yes' === $product_enabled ) {
            return true;
        }

        if ( 'no' === $product_enabled ) {
            return false;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return false;
        }

        foreach ( $product->get_category_ids() as $cat_id ) {
            $cat_enabled = get_term_meta( $cat_id, '_apd_enable_deposit', true );

            if ( 'yes' === $cat_enabled ) {
                return true;
            }

            if ( 'no' === $cat_enabled ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save conditional rules settings.
     */
    public function save_settings( $settings, $tab, $data ) {
        if ( $tab === 'conditional-rules' ) {
            $settings['conditional_min_cart']     = floatval( $data['conditional_min_cart'] ?? 0 );
            $settings['conditional_max_cart']     = floatval( $data['conditional_max_cart'] ?? 0 );
            $settings['conditional_min_quantity'] = intval( $data['conditional_min_quantity'] ?? 0 );
            $settings['conditional_max_quantity'] = intval( $data['conditional_max_quantity'] ?? 0 );
            $settings['conditional_date_from']    = sanitize_text_field( $data['conditional_date_from'] ?? '' );
            $settings['conditional_date_to']      = sanitize_text_field( $data['conditional_date_to'] ?? '' );
            $settings['conditional_user_roles']   = isset( $data['conditional_user_roles'] ) ? array_map( 'sanitize_text_field', (array) $data['conditional_user_roles'] ) : array();
            $settings['conditional_allow_guests'] = isset( $data['conditional_allow_guests'] ) ? 'yes' : 'no';
        }
        return $settings;
    }
}
