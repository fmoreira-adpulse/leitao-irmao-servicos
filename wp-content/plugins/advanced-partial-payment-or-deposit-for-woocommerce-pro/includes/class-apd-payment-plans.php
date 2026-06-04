<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Payment Plans – multi-plan builder with dynamic installment rows.
 * Plans are stored as a serialised option: apd_payment_plans => [ plan_id => { ... } ]
 */
class APD_Payment_Plans {

    public function __construct() {
        // AJAX – plan CRUD
        add_action( 'wp_ajax_apd_save_plan', array( $this, 'ajax_save_plan' ) );
        add_action( 'wp_ajax_apd_delete_plan', array( $this, 'ajax_delete_plan' ) );
        add_action( 'wp_ajax_apd_get_plans', array( $this, 'ajax_get_plans' ) );

        // Suppress the free plugin's deposit form when plans exist or type is payment_plan
        add_filter( 'apd_suppress_deposit_form', array( $this, 'should_suppress_deposit_form' ), 10, 2 );

        // Display plan selector on product page — priority 26 so the free plugin's
        // deposit form (priority 25) always runs first and checks suppression before
        // the plan selector renders.
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_plan_selector' ), 26 );

        // Save selected plan to cart
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_plan_to_cart' ), 15, 2 );
        add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'restore_plan_from_session' ), 10, 2 );

        // Create schedule on order completion (classic + block checkout)
        add_action( 'woocommerce_checkout_order_created', array( $this, 'save_plan_to_order' ), 20, 1 );
        add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'save_plan_to_order' ), 20, 1 );

        // Provide first-installment deposit amount for the cart summary (fixes $0 payment_plan cart total).
        add_filter( 'apd_payment_plan_cart_deposit', array( $this, 'get_plan_cart_deposit' ), 10, 4 );

        // Product-level plan assignment UI is in the free plugin's "Deposit" product tab (APD_Product_Meta).
        // We only hook save here so that unchecking ALL plans correctly clears the meta when nothing
        // is submitted in $_POST (checkboxes don't post when unchecked).
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ) );
    }

    /* ------------------------------------------------------------------
       Helpers
       ------------------------------------------------------------------ */

    /**
     * Get all plans.
     */
    public static function get_plans() {
        $plans = get_option( 'apd_payment_plans', array() );
        return is_array( $plans ) ? $plans : array();
    }

    /**
     * Get only active plans.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function get_active_plans() {
        return array_filter(
            self::get_plans(),
            function ( $plan ) {
                return ! empty( $plan['status'] ) && 'active' === $plan['status'];
            }
        );
    }

    /**
     * Get a single plan by ID.
     *
     * @param string $plan_id Plan identifier.
     * @return array|false Plan data or false if not found.
     */
    public static function get_plan( $plan_id ) {
        $plans = self::get_plans();
        return isset( $plans[ $plan_id ] ) ? $plans[ $plan_id ] : false;
    }

    /**
     * Get plans available for a product.
     *
     * Priority: product-level assigned plans → category-level assigned plans → all active plans.
     *
     * BUG FIX: get_post_meta() with $single=true returns '' when the key does not exist.
     * Casting that '' to array produces [''], and !empty(['']) is TRUE – which incorrectly
     * treats "no override saved" as "override with an empty plan ID". array_filter() removes
     * those empty-string entries before the empty-check so only real plan IDs are considered.
     *
     * @param int $product_id Product ID.
     * @return array<string,array<string,mixed>> Keyed by plan ID.
     */
    public static function get_plans_for_product( $product_id ) {
        $raw      = get_post_meta( $product_id, '_apd_assigned_plans', true );
        $assigned = array_filter( is_array( $raw ) ? $raw : array() ); // strip '' / false entries
        $active   = self::get_active_plans();

        // Product-level explicit assignment.
        if ( ! empty( $assigned ) ) {
            return array_intersect_key( $active, array_flip( array_values( $assigned ) ) );
        }

        // Category-level fallback.
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $category_plan_ids = array();

            foreach ( $product->get_category_ids() as $category_id ) {
                $cat_raw   = get_term_meta( $category_id, '_apd_assigned_plans', true );
                $cat_plans = array_filter( is_array( $cat_raw ) ? $cat_raw : array() );
                if ( ! empty( $cat_plans ) ) {
                    $category_plan_ids = array_merge( $category_plan_ids, $cat_plans );
                }
            }

            $category_plan_ids = array_values( array_unique( array_filter( $category_plan_ids ) ) );

            if ( ! empty( $category_plan_ids ) ) {
                return array_intersect_key( $active, array_flip( $category_plan_ids ) );
            }
        }

        // Global fallback: all active plans.
        return $active;
    }

    /**
     * Build human-readable schedule for a plan + price.
     *
     * @param array $plan  Plan data.
     * @param float $price Product price (× quantity).
     * @return array<int,array<string,mixed>>
     */
    public static function build_schedule( $plan, $price ) {
        if ( empty( $plan['installments'] ) || ! is_array( $plan['installments'] ) ) {
            return array();
        }

        $schedule = array();
        foreach ( $plan['installments'] as $idx => $inst ) {
            $amount_pct = floatval( $inst['amount'] );
            if ( $plan['price_type'] === 'fixed' ) {
                $amount = $amount_pct;
            } else {
                $amount = round( ( $amount_pct / 100 ) * $price, wc_get_price_decimals() );
            }

            $due_label = '';
            $due_date  = '';

            switch ( $inst['due_type'] ?? 'immediately' ) {
                case 'immediately':
                    $due_label = __( 'Immediately', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
                    $due_date  = current_time( 'Y-m-d' );
                    break;

                case 'after_purchase':
                    $days      = intval( $inst['due_after_value'] ?? 0 );
                    $unit      = $inst['due_after_unit'] ?? 'day';
                    $due_label = sprintf(
                        __( '%d %s(s) after purchase', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                        $days,
                        $unit
                    );
                    $due_date = wp_date( 'Y-m-d', strtotime( "+{$days} {$unit}" ) );
                    break;

                case 'fixed_date':
                    $due_date  = $inst['due_fixed_date'] ?? '';
                    $due_label = $due_date ? wp_date( get_option( 'date_format' ), strtotime( $due_date ) ) : '';
                    break;
            }

            $schedule[] = array(
                'number'     => $idx + 1,
                'percentage' => $amount_pct,
                'amount'     => $amount,
                'due_type'   => $inst['due_type'] ?? 'immediately',
                'due_label'  => $due_label,
                'due_date'   => $due_date,
                'status'     => $idx === 0 ? 'pending' : 'upcoming',
            );
        }

        return $schedule;
    }

    /* ------------------------------------------------------------------
       AJAX – Save Plan
       ------------------------------------------------------------------ */

    public function ajax_save_plan() {
        check_ajax_referer( 'apd_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
            return;
        }

        $plan_id = sanitize_text_field( wp_unslash( $_POST['plan_id'] ?? '' ) );
        $is_new  = empty( $plan_id );

        if ( $is_new ) {
            $plan_id = 'plan_' . uniqid();
        }

        $installments = array();
        if ( isset( $_POST['installments'] ) && is_array( $_POST['installments'] ) ) {
            foreach ( $_POST['installments'] as $inst ) {
                $installments[] = array(
                    'amount'          => floatval( $inst['amount'] ?? 0 ),
                    'due_type'        => sanitize_text_field( $inst['due_type'] ?? 'immediately' ),
                    'due_after_value' => intval( $inst['due_after_value'] ?? 0 ),
                    'due_after_unit'  => sanitize_text_field( $inst['due_after_unit'] ?? 'day' ),
                    'due_fixed_date'  => sanitize_text_field( $inst['due_fixed_date'] ?? '' ),
                );
            }
        }

        $plan = array(
            'id'           => $plan_id,
            'name'         => sanitize_text_field( wp_unslash( $_POST['plan_name'] ?? '' ) ),
            'description'  => sanitize_textarea_field( wp_unslash( $_POST['plan_description'] ?? '' ) ),
            'price_type'   => sanitize_text_field( wp_unslash( $_POST['price_type'] ?? 'percentage' ) ),
            'status'       => sanitize_text_field( wp_unslash( $_POST['plan_status'] ?? 'active' ) ),
            'installments' => $installments,
            'created'      => $is_new ? current_time( 'mysql' ) : sanitize_text_field( wp_unslash( $_POST['plan_created'] ?? current_time( 'mysql' ) ) ),
            'updated'      => current_time( 'mysql' ),
        );

        $plans             = self::get_plans();
        $plans[ $plan_id ] = $plan;
        update_option( 'apd_payment_plans', $plans );

        wp_send_json_success( array(
            'message' => $is_new ? __( 'Plan created!', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) : __( 'Plan updated!', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'plan_id' => $plan_id,
            'plan'    => $plan,
        ) );
    }

    /* ------------------------------------------------------------------
       AJAX – Delete Plan
       ------------------------------------------------------------------ */

    public function ajax_delete_plan() {
        check_ajax_referer( 'apd_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
            return;
        }

        $plan_id = sanitize_text_field( wp_unslash( $_POST['plan_id'] ?? '' ) );
        $plans   = self::get_plans();

        if ( isset( $plans[ $plan_id ] ) ) {
            unset( $plans[ $plan_id ] );
            update_option( 'apd_payment_plans', $plans );
            wp_send_json_success( __( 'Plan deleted.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
        }

        wp_send_json_error( __( 'Plan not found.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
    }

    /* ------------------------------------------------------------------
       AJAX – Get Plans
       ------------------------------------------------------------------ */

    public function ajax_get_plans() {
        check_ajax_referer( 'apd_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
        }
        wp_send_json_success( self::get_plans() );
    }

    /* ------------------------------------------------------------------
       Installment Row Renderer (used by admin views)
       ------------------------------------------------------------------ */

    /**
     * Render a single installment row HTML for the plan builder.
     *
     * @param int    $idx        Row index.
     * @param array  $inst       Installment data.
     * @param string $price_type 'percentage' or 'fixed'.
     * @return void
     */
    public static function render_installment_row( $idx, $inst, $price_type = 'percentage' ) {
        $suffix   = $price_type === 'fixed' ? get_woocommerce_currency_symbol() : '%';
        $due_type = $inst['due_type'] ?? 'immediately';
        ?>
        <div class="apd-installment-row" data-index="<?php echo esc_attr( $idx ); ?>">
            <div class="apd-inst-amount">
                <div class="apd-input-group" style="max-width:130px;">
                    <input type="number" name="installments[<?php echo esc_attr( $idx ); ?>][amount]" class="apd-input apd-inst-amount-input" value="<?php echo esc_attr( $inst['amount'] ?? '' ); ?>" step="0.01" min="0" placeholder="0" />
                    <span class="apd-input-suffix apd-inst-suffix"><?php echo esc_html( $suffix ); ?></span>
                </div>
            </div>
            <div class="apd-inst-due">
                <div class="apd-inst-due-fields">
                    <select name="installments[<?php echo esc_attr( $idx ); ?>][due_type]" class="apd-select apd-due-type-select" style="min-width:220px;">
                        <option value="immediately" <?php selected( $due_type, 'immediately' ); ?>><?php esc_html_e( 'Immediately', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                        <option value="after_purchase" <?php selected( $due_type, 'after_purchase' ); ?>><?php esc_html_e( 'Specific duration after purchase', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                        <option value="fixed_date" <?php selected( $due_type, 'fixed_date' ); ?>><?php esc_html_e( 'On a fixed date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                    </select>
                    <div class="apd-due-extra apd-due-after" style="<?php echo $due_type === 'after_purchase' ? '' : 'display:none;'; ?>">
                        <span style="color:#64748b;font-size:13px;font-weight:500;"><?php esc_html_e( 'After', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                        <input type="number" name="installments[<?php echo esc_attr( $idx ); ?>][due_after_value]" class="apd-input" style="width:70px;" value="<?php echo esc_attr( $inst['due_after_value'] ?? 0 ); ?>" min="0" />
                        <select name="installments[<?php echo esc_attr( $idx ); ?>][due_after_unit]" class="apd-select" style="min-width:90px;">
                            <option value="day" <?php selected( $inst['due_after_unit'] ?? 'day', 'day' ); ?>><?php esc_html_e( 'day(s)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                            <option value="week" <?php selected( $inst['due_after_unit'] ?? 'day', 'week' ); ?>><?php esc_html_e( 'week(s)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                            <option value="month" <?php selected( $inst['due_after_unit'] ?? 'day', 'month' ); ?>><?php esc_html_e( 'month(s)', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></option>
                        </select>
                    </div>
                    <div class="apd-due-extra apd-due-fixed" style="<?php echo $due_type === 'fixed_date' ? '' : 'display:none;'; ?>">
                        <span style="color:#64748b;font-size:13px;font-weight:500;"><?php esc_html_e( 'Date', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span>
                        <input type="date" name="installments[<?php echo esc_attr( $idx ); ?>][due_fixed_date]" class="apd-input" style="width:auto;" value="<?php echo esc_attr( $inst['due_fixed_date'] ?? '' ); ?>" placeholder="YYYY-MM-DD" />
                    </div>
                </div>
            </div>
            <div class="apd-inst-actions">
                <button type="button" class="apd-btn apd-btn-small apd-btn-danger apd-remove-installment" title="<?php esc_attr_e( 'Remove', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>">
                    &#x2715;
                </button>
            </div>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------
       Frontend – Product Page
       ------------------------------------------------------------------ */

    /**
     * Filter callback: tell the free plugin to suppress its deposit form
     * only when this product is using the payment plan deposit type.
     *
     * @param bool $suppress   Current suppression state.
     * @param int  $product_id Product ID.
     * @return bool
     */
    public function should_suppress_deposit_form( $suppress, $product_id ) {
        if ( $suppress ) {
            return $suppress; // already suppressed by another filter
        }

        $deposit_engine = APD_Deposit::instance();
        $deposit_type   = $deposit_engine->get_deposit_type( $product_id );
        $plans          = self::get_plans_for_product( $product_id );

        return $deposit_type === 'payment_plan' && ! empty( $plans );
    }

    /**
     * Render the unified plan selector on the product page.
     * Replaces the free plugin's deposit radio form for payment_plan-type products.
     */
    public function display_plan_selector() {
        global $product;
        if ( ! $product ) {
            return;
        }

        $deposit_engine = APD_Deposit::instance();
        if ( ! $deposit_engine->is_deposit_enabled( $product->get_id() ) ) {
            return;
        }

        $deposit_type = $deposit_engine->get_deposit_type( $product->get_id() );
        if ( $deposit_type !== 'payment_plan' ) {
            return;
        }

        $plans = self::get_plans_for_product( $product->get_id() );
        if ( empty( $plans ) ) {
            return;
        }

        $price         = floatval( $product->get_price() );
        $allow_full    = $deposit_engine->is_full_payment_allowed( $product->get_id() );
        $settings      = get_option( 'apd_settings', array() );
        $full_text     = $settings['full_payment_text'] ?? 'Pay full amount of {full_amount}';
        $balance_label = $settings['due_balance_label'] ?? __( 'Due Balance', 'advanced-partial-payment' );
        $full_text     = str_replace( '{full_amount}', wc_price( $price ), $full_text );

        include APD_PRO_PLUGIN_DIR . 'public/views/payment-plan-selector.php';
    }

    /* ------------------------------------------------------------------
       Cart Integration
       ------------------------------------------------------------------ */

    /**
     * Store the selected plan ID on the cart item.
     *
     * @param array $cart_item_data Existing cart item data.
     * @param int   $product_id     Product being added.
     * @return array
     */
    public function add_plan_to_cart( $cart_item_data, $product_id ) {
        if ( isset( $_POST['apd_selected_plan'] ) && ! empty( $_POST['apd_selected_plan'] ) ) {
            $cart_item_data['apd_selected_plan'] = sanitize_text_field( wp_unslash( $_POST['apd_selected_plan'] ) );
        }
        return $cart_item_data;
    }

    /**
     * Restore the selected plan ID from session.
     *
     * @param array $cart_item Cart item.
     * @param array $values    Session values.
     * @return array
     */
    public function restore_plan_from_session( $cart_item, $values ) {
        if ( isset( $values['apd_selected_plan'] ) ) {
            $cart_item['apd_selected_plan'] = $values['apd_selected_plan'];
        }
        return $cart_item;
    }

    /* ------------------------------------------------------------------
       Cart – Payment Plan Deposit Amount
       ------------------------------------------------------------------ */

    /**
     * Return the first-installment amount for a payment_plan product in the cart.
     *
     * This is the implementation of the `apd_payment_plan_cart_deposit` filter fired by
     * APD_Deposit::get_cart_payment_summary(). Without this the payment_plan deposit type
     * had no handler in get_deposit_amount() and contributed $0 to the cart deposit total.
     *
     * @param float  $deposit    Current deposit value (default 0.0).
     * @param string $plan_id    Plan ID stored on the cart item (may be empty string).
     * @param float  $unit_price Unit product price (line_total / quantity).
     * @param array  $cart_item  Full WooCommerce cart item array.
     * @return float First installment amount for the resolved plan.
     */
    public function get_plan_cart_deposit( $deposit, $plan_id, $unit_price, $cart_item ) {
        $plan = false;

        // Try the explicitly-selected plan first.
        if ( ! empty( $plan_id ) ) {
            $plan = self::get_plan( $plan_id );
        }

        // Fall back to the first active plan available for this product.
        if ( ! $plan ) {
            $product_id = isset( $cart_item['product_id'] ) ? intval( $cart_item['product_id'] ) : 0;
            if ( $product_id ) {
                $plans = self::get_plans_for_product( $product_id );
                if ( ! empty( $plans ) ) {
                    $plan = reset( $plans );
                }
            }
        }

        if ( empty( $plan ) || empty( $plan['installments'] ) || ! is_array( $plan['installments'] ) ) {
            return $deposit;
        }

        // First installment is the amount due immediately.
        $first_inst = reset( $plan['installments'] );
        $amount     = floatval( $first_inst['amount'] ?? 0 );

        if ( 'fixed' === ( $plan['price_type'] ?? 'percentage' ) ) {
            return round( min( $amount, $unit_price ), wc_get_price_decimals() );
        }

        // Percentage-based plan.
        return round( ( min( $amount, 100.0 ) / 100.0 ) * $unit_price, wc_get_price_decimals() );
    }

    /* ------------------------------------------------------------------
       Order – Save Plan Schedule
       ------------------------------------------------------------------ */

    /**
     * Persist the installment schedule on the order after checkout.
     *
     * @param WC_Order $order The order just created.
     */
    public function save_plan_to_order( $order ) {
        $cart = WC()->cart;
        if ( ! $cart ) {
            return;
        }

        foreach ( $cart->get_cart() as $item ) {
            if ( empty( $item['apd_selected_plan'] ) ) {
                continue;
            }

            $plan = self::get_plan( $item['apd_selected_plan'] );
            if ( ! $plan ) {
                continue;
            }

            $product = wc_get_product( $item['product_id'] );
            if ( ! $product ) {
                continue;
            }

            // Use the actual line total (after discounts/coupons), not the catalog price.
            $price    = floatval( $item['line_total'] );
            $schedule = self::build_schedule( $plan, $price );

            $order->update_meta_data( '_apd_payment_plan_id', $item['apd_selected_plan'] );
            $order->update_meta_data( '_apd_payment_plan_name', $plan['name'] );
            $order->update_meta_data( '_apd_installment_schedule', $schedule );
            break; // one plan per order
        }

        $order->save();
    }

    /* ------------------------------------------------------------------
       Product Edit – Save Plan Assignment
       Plan assignment UI lives in the free plugin's "Deposit" product tab (APD_Product_Meta).
       This save hook ensures _apd_assigned_plans is ALWAYS written – even when all checkboxes
       are unchecked (in which case $_POST['_apd_assigned_plans'] is absent).
       ------------------------------------------------------------------ */

    /**
     * Save product-level plan assignments.
     *
     * @param int $product_id Post ID being saved.
     */
    public function save_product_meta( $product_id ) {
        $assigned = isset( $_POST['_apd_assigned_plans'] )
            ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['_apd_assigned_plans'] ) )
            : array();

        update_post_meta(
            $product_id,
            '_apd_assigned_plans',
            array_values( array_unique( array_filter( $assigned ) ) )
        );
    }
}
