<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core Deposit Calculation Engine.
 *
 * Priority: Product-level → Category-level → Global-level
 */
class APD_Deposit {

    /**
     * Singleton instance.
     */
    private static $instance = null;

    /**
     * Get instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_action( 'init', array( $this, 'register_order_status' ) );
        add_filter( 'wc_order_statuses', array( $this, 'add_order_statuses' ) );
        // When "Admin Manual Orders Only" is enabled, disable deposits on every frontend request.
        add_filter( 'apd_deposit_enabled', array( $this, 'maybe_disable_on_frontend' ), 99, 2 );
    }

    /**
     * Register custom order status.
     */
    public function register_order_status() {
        register_post_status( 'wc-partially-paid', array(
            'label'                     => _x( 'Partially Paid', 'Order status', 'advanced-partial-payment' ),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            /* translators: %s: number of orders */
            'label_count'               => _n_noop(
                'Partially Paid <span class="count">(%s)</span>',
                'Partially Paid <span class="count">(%s)</span>',
                'advanced-partial-payment'
            ),
        ) );
    }

    /**
     * Add custom statuses to WooCommerce.
     */
    public function add_order_statuses( $statuses ) {
        $new_statuses = array();
        foreach ( $statuses as $key => $label ) {
            $new_statuses[ $key ] = $label;
            if ( 'wc-on-hold' === $key ) {
                $new_statuses['wc-partially-paid'] = _x( 'Partially Paid', 'Order status', 'advanced-partial-payment' );
            }
        }
        return $new_statuses;
    }

    /**
     * Filter: disable ALL frontend deposit functionality when "Admin Manual Orders Only" is on.
     * Every frontend request (product pages, cart, checkout, WC AJAX) returns false.
     * Admin-panel page loads and admin-ajax.php requests are not affected.
     *
     * @param bool $enabled    Current enabled state.
     * @param int  $product_id Product ID.
     * @return bool
     */
    public function maybe_disable_on_frontend( $enabled, $product_id ) {
        if ( apd_get_option( 'admin_only_deposit', 'no' ) !== 'yes' ) {
            return $enabled;
        }

        // Block frontend requests, including WooCommerce classic-checkout AJAX which
        // uses admin-ajax.php (making is_admin() true even for customer requests).
        // We distinguish customer AJAX from admin AJAX by capability.
        $is_frontend = ! is_admin()
            || ( wp_doing_ajax() && ! current_user_can( 'manage_woocommerce' ) );

        return $is_frontend ? false : $enabled;
    }

    /**
     * Check if deposit is enabled for a product.
     *
     * Priority: Product-level → Category-level → Global-level
     */
    public function is_deposit_enabled( $product_id ) {
        // Start from global setting as the baseline default.
        $enabled = apd_get_option( 'enable_deposit', 'yes' ) === 'yes';

        // Product-level override takes highest priority.
        $product_enabled = get_post_meta( $product_id, '_apd_enable_deposit', true );
        if ( $product_enabled === 'yes' ) {
            $enabled = true;
        } elseif ( $product_enabled === 'no' ) {
            $enabled = false;
        } else {
            // No product override – fall back to category, then global.
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $categories = $product->get_category_ids();
                foreach ( $categories as $cat_id ) {
                    $cat_enabled = get_term_meta( $cat_id, '_apd_enable_deposit', true );
                    if ( $cat_enabled === 'yes' ) {
                        $enabled = true;
                        break;
                    }
                    if ( $cat_enabled === 'no' ) {
                        $enabled = false;
                        break;
                    }
                }
            }
            // If no category sets an override, $enabled already reflects the global setting.
        }

        /**
         * Filter whether deposit is enabled for a product after core rules are applied.
         */
        return (bool) apply_filters( 'apd_deposit_enabled', $enabled, $product_id );
    }

    /**
     * Get deposit type for a product.
     *
     * @return string 'fixed' or 'percentage'
     */
    public function get_deposit_type( $product_id ) {
        $type = '';

        // Product-level
        $product_type = get_post_meta( $product_id, '_apd_deposit_type', true );
        if ( ! empty( $product_type ) && $product_type !== 'global' ) {
            $type = $product_type;
        } else {
            // Category-level
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $categories = $product->get_category_ids();
                foreach ( $categories as $cat_id ) {
                    $cat_type = get_term_meta( $cat_id, '_apd_deposit_type', true );
                    if ( ! empty( $cat_type ) && $cat_type !== 'global' ) {
                        $type = $cat_type;
                        break;
                    }
                }
            }
        }

        // Global
        if ( '' === $type ) {
            $type = apd_get_option( 'deposit_type', 'percentage' );
        }

        return (string) apply_filters( 'apd_deposit_type', $type, $product_id );
    }

    /**
     * Get raw deposit value for a product.
     */
    public function get_deposit_value( $product_id ) {
        $value = null;

        // Product-level
        $product_value = get_post_meta( $product_id, '_apd_deposit_value', true );
        if ( $product_value !== '' && $product_value !== null ) {
            $product_type = get_post_meta( $product_id, '_apd_deposit_type', true );
            if ( ! empty( $product_type ) && $product_type !== 'global' ) {
                $value = floatval( $product_value );
            }
        }

        if ( null === $value ) {
            // Category-level
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $categories = $product->get_category_ids();
                foreach ( $categories as $cat_id ) {
                    $cat_value = get_term_meta( $cat_id, '_apd_deposit_value', true );
                    $cat_type  = get_term_meta( $cat_id, '_apd_deposit_type', true );
                    if ( $cat_value !== '' && ! empty( $cat_type ) && $cat_type !== 'global' ) {
                        $value = floatval( $cat_value );
                        break;
                    }
                }
            }
        }

        if ( null === $value ) {
            // Global
            $value = floatval( apd_get_option( 'deposit_value', 50 ) );
        }

        return floatval( apply_filters( 'apd_deposit_value', $value, $product_id, $this->get_deposit_type( $product_id ) ) );
    }

    /**
     * Calculate deposit amount for a product.
     */
    public function get_deposit_amount( $product_id, $price = null ) {
        if ( ! $this->is_deposit_enabled( $product_id ) ) {
            return 0;
        }

        if ( is_null( $price ) ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                return 0;
            }
            $price = floatval( $product->get_price() );
        }

        $type  = $this->get_deposit_type( $product_id );
        $value = $this->get_deposit_value( $product_id );
        $deposit = 0;

        if ( 'fixed' === $type ) {
            $deposit = min( $value, $price );
        } elseif ( 'percentage' === $type ) {
            $value   = min( $value, 100 );
            $deposit = ( $price * $value ) / 100;
        } elseif ( 'min_max' === $type ) {
            $bounds  = $this->get_min_max_bounds( $product_id, $price );
            $deposit = $bounds['min'];
        }

        /**
         * Filter the calculated deposit amount.
         */
        $deposit = apply_filters( 'apd_deposit_amount', $deposit, $product_id, $price, $type, $value );

        return round( $deposit, wc_get_price_decimals() );
    }

    /**
     * Get min/max deposit bounds for a product.
     *
     * @param int        $product_id Product ID.
     * @param float|null $price      Product price.
     * @return array<string,float>
     */
    public function get_min_max_bounds( $product_id, $price = null ) {
        if ( is_null( $price ) ) {
            $product = wc_get_product( $product_id );
            $price   = $product ? floatval( $product->get_price() ) : 0;
        }

        $min = floatval( apd_get_option( 'min_deposit_amount', 0 ) );
        $max = floatval( apd_get_option( 'max_deposit_amount', 0 ) );

        $product_min = get_post_meta( $product_id, '_apd_min_deposit', true );
        $product_max = get_post_meta( $product_id, '_apd_max_deposit', true );

        if ( '' !== $product_min && false !== $product_min ) {
            $min = floatval( $product_min );
        }

        if ( '' !== $product_max && false !== $product_max ) {
            $max = floatval( $product_max );
        }

        if ( $min <= 0 ) {
            $min = round( floatval( $price ) * 0.10, wc_get_price_decimals() );
        }

        if ( $max <= 0 || $max > $price ) {
            $max = floatval( $price );
        }

        if ( $min > $max ) {
            $min = $max;
        }

        return array(
            'min' => round( max( 0, floatval( $min ) ), wc_get_price_decimals() ),
            'max' => round( max( 0, floatval( $max ) ), wc_get_price_decimals() ),
        );
    }

    /**
     * Sanitize a custom min/max deposit amount against allowed bounds.
     *
     * @param int        $product_id      Product ID.
     * @param float      $custom_deposit  Submitted/custom deposit.
     * @param float|null $price           Product price.
     * @return float
     */
    public function sanitize_custom_deposit( $product_id, $custom_deposit, $price = null ) {
        $bounds = $this->get_min_max_bounds( $product_id, $price );
        $amount = floatval( $custom_deposit );

        if ( $amount < $bounds['min'] ) {
            $amount = $bounds['min'];
        }

        if ( $amount > $bounds['max'] ) {
            $amount = $bounds['max'];
        }

        return round( $amount, wc_get_price_decimals() );
    }

    /**
     * Get due balance amount.
     */
    public function get_due_balance( $product_id, $price = null ) {
        if ( is_null( $price ) ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                return 0;
            }
            $price = floatval( $product->get_price() );
        }

        $deposit = $this->get_deposit_amount( $product_id, $price );
        return round( $price - $deposit, wc_get_price_decimals() );
    }

    /**
     * Calculate total deposit for the entire cart.
     */
    public function calculate_cart_deposit() {
        $summary = $this->get_cart_payment_summary();
        return $summary['deposit_amount'];
    }

    /**
     * Check if cart has any deposit items.
     */
    public function cart_has_deposit() {
        if ( ! WC()->cart ) {
            return false;
        }

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( $this->is_deposit_enabled( $cart_item['product_id'] ) ) {
                $pay_deposit = isset( $cart_item['apd_pay_deposit'] ) ? $cart_item['apd_pay_deposit'] : 'yes';
                if ( $pay_deposit === 'yes' ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get a normalized payment summary for the current cart.
     *
     * @return array<string,mixed>
     */
    public function get_cart_payment_summary() {
        if ( ! WC()->cart ) {
            return array(
                'has_deposit'        => false,
                'cart_subtotal'      => 0,
                'deposit_subtotal'   => 0,
                'shipping_total'     => 0,
                'tax_total'          => 0,
                'fee_total'          => 0,
                'deposit_only_fee_total' => 0,
                'full_total'         => 0,
                'deposit_amount'     => 0,
                'balance_due'        => 0,
                'deposit_ratio'      => 0,
            );
        }

        $cart_subtotal    = 0;
        $deposit_subtotal = 0;
        $has_deposit      = false;

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $line_total = floatval( $cart_item['line_total'] ?? 0 );
            $quantity   = max( 1, intval( $cart_item['quantity'] ?? 1 ) );

            $cart_subtotal += $line_total;

            if ( $this->is_deposit_enabled( $cart_item['product_id'] ) ) {
                $pay_deposit = isset( $cart_item['apd_pay_deposit'] ) ? $cart_item['apd_pay_deposit'] : 'yes';

                if ( 'yes' === $pay_deposit ) {
                    $unit_price     = $line_total / $quantity;
                    $deposit_type   = $this->get_deposit_type( $cart_item['product_id'] );
                    $custom_deposit = isset( $cart_item['apd_custom_deposit'] ) ? floatval( $cart_item['apd_custom_deposit'] ) : null;

                    if ( 'min_max' === $deposit_type && null !== $custom_deposit ) {
                        $unit_deposit = $this->sanitize_custom_deposit( $cart_item['product_id'], $custom_deposit, $unit_price );
                    } elseif ( 'payment_plan' === $deposit_type ) {
                        /**
                         * Allow the Pro payment-plans module to return the correct first-installment
                         * amount for the selected plan. Falls back to 0 if no plan is active.
                         *
                         * @param float  $deposit    Default deposit (0 for unhandled payment_plan).
                         * @param string $plan_id    The plan ID stored in cart item, or empty string.
                         * @param float  $unit_price Unit product price.
                         * @param array  $cart_item  Full cart item array.
                         */
                        $plan_id      = isset( $cart_item['apd_selected_plan'] ) ? $cart_item['apd_selected_plan'] : '';
                        $unit_deposit = (float) apply_filters( 'apd_payment_plan_cart_deposit', 0.0, $plan_id, $unit_price, $cart_item );
                    } else {
                        $unit_deposit = $this->get_deposit_amount( $cart_item['product_id'], $unit_price );
                    }

                    $deposit_subtotal += $unit_deposit * $quantity;
                    $has_deposit      = true;
                    continue;
                }
            }

            $deposit_subtotal += $line_total;
        }

        $shipping_total = floatval( WC()->cart->get_shipping_total() );
        $tax_total      = floatval( WC()->cart->get_total_tax() );
        $fee_total      = 0;
        $deposit_only_fee_total = 0;

        foreach ( WC()->cart->get_fees() as $fee ) {
            $current_fee_total = isset( $fee->total ) ? floatval( $fee->total ) : ( isset( $fee->amount ) ? floatval( $fee->amount ) : 0 );

            // Skip our own balance adjustment fee to avoid double-counting.
            // WC generates the fee id via sanitize_title() on the fee name, so the
            // generated slug is 'due-balance-pay-later', not 'apd-balance-due'.
            // Match both to be safe across label translations.
            $fee_id = isset( $fee->id ) ? (string) $fee->id : '';
            if ( 'apd-balance-due' === $fee_id || 0 === strpos( $fee_id, 'due-balance' ) ) {
                continue;
            }

            if ( class_exists( 'APD_Deposit_Fees' ) && APD_Deposit_Fees::is_partial_payment_fee( $fee ) ) {
                $deposit_only_fee_total += abs( $current_fee_total );
            }

            $fee_total += $current_fee_total;
        }

        $full_total    = $cart_subtotal + $shipping_total + $tax_total + $fee_total;
        $deposit_ratio = $cart_subtotal > 0 ? min( 1, $deposit_subtotal / $cart_subtotal ) : 0;
        $deposit_total = $deposit_subtotal;

        if ( $cart_subtotal > 0 ) {
            $deposit_total += ( $shipping_total * $deposit_ratio );
            $deposit_total += ( $tax_total * $deposit_ratio );
            $deposit_total += ( ( $fee_total - $deposit_only_fee_total ) * $deposit_ratio );
        }

        $deposit_total += $deposit_only_fee_total;
        $deposit_total = round( min( $deposit_total, $full_total ), wc_get_price_decimals() );
        $balance_due   = round( max( 0, $full_total - $deposit_total ), wc_get_price_decimals() );

        $summary = array(
            'has_deposit'      => $has_deposit,
            'cart_subtotal'    => round( $cart_subtotal, wc_get_price_decimals() ),
            'deposit_subtotal' => round( $deposit_subtotal, wc_get_price_decimals() ),
            'shipping_total'   => round( $shipping_total, wc_get_price_decimals() ),
            'tax_total'        => round( $tax_total, wc_get_price_decimals() ),
            'fee_total'        => round( $fee_total, wc_get_price_decimals() ),
            'deposit_only_fee_total' => round( $deposit_only_fee_total, wc_get_price_decimals() ),
            'full_total'       => round( $full_total, wc_get_price_decimals() ),
            'deposit_amount'   => $deposit_total,
            'balance_due'      => $balance_due,
            'deposit_ratio'    => $deposit_ratio,
        );

        return apply_filters( 'apd_cart_payment_summary', $summary, WC()->cart );
    }

    /**
     * Check if deposit-only mode is enabled.
     *
     * @param int $product_id Optional product ID.
     * @return bool
     */
    public function is_force_deposit_enabled( $product_id = 0 ) {
        if ( $product_id && ! $this->is_deposit_enabled( $product_id ) ) {
            return false;
        }

        if ( $product_id ) {
            $product_force = get_post_meta( $product_id, '_apd_force_deposit', true );

            if ( 'yes' === $product_force ) {
                return true;
            }

            if ( 'no' === $product_force ) {
                return false;
            }

            $product = wc_get_product( $product_id );

            if ( $product ) {
                foreach ( $product->get_category_ids() as $cat_id ) {
                    $category_force = get_term_meta( $cat_id, '_apd_force_deposit', true );

                    if ( 'yes' === $category_force ) {
                        return true;
                    }

                    if ( 'no' === $category_force ) {
                        return false;
                    }
                }
            }
        }

        return apd_get_option( 'force_deposit', 'no' ) === 'yes';
    }

    /**
     * Check if full payment is allowed.
     *
     * @param int $product_id Optional product ID.
     * @return bool
     */
    public function is_full_payment_allowed( $product_id = 0 ) {
        if ( $this->is_force_deposit_enabled( $product_id ) ) {
            return false;
        }

        return apd_get_option( 'allow_full_payment', 'yes' ) === 'yes';
    }
}
