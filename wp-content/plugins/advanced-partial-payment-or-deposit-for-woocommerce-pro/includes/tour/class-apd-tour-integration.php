<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Tour Booking Manager integration.
 */
class APD_Tour_Integration {

    public function __construct() {
        add_action( 'apd_dashboard_header_badges', array( $this, 'render_dashboard_badge' ) );
        add_action( 'apd_general_tab_after_intro', array( $this, 'render_general_status_card' ) );
        add_filter( 'apd_save_settings', array( $this, 'save_settings' ), 10, 3 );
        add_filter( 'apd_deposit_enabled', array( $this, 'maybe_disable_tour_deposits' ), 20, 2 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ), 5 );
        add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_checkout_selector' ), 6 );
        add_action( 'wp_ajax_apd_tour_checkout_payment_type', array( $this, 'ajax_update_checkout_payment_type' ) );
        add_action( 'wp_ajax_nopriv_apd_tour_checkout_payment_type', array( $this, 'ajax_update_checkout_payment_type' ) );

        add_action( 'admin_head', array( $this, 'render_admin_styles' ) );
        add_action( 'admin_footer', array( $this, 'render_order_list_assets' ) );
        add_action( 'ttbm_guest_list_after_table_head', array( $this, 'render_guest_list_head' ) );
        add_action( 'ttbm_guest_list_after_list_body', array( $this, 'render_guest_list_cell' ), 20, 1 );
        add_action( 'ttbm_pdf_after_tour_info_list', array( $this, 'render_ticket_pdf_deposit_info' ), 20, 1 );
        add_action( 'wp_ajax_apd_tour_order_summaries', array( $this, 'ajax_tour_order_summaries' ) );
        add_action( 'wp_ajax_ttbm_download_csv', array( $this, 'maybe_handle_existing_tour_csv' ), 1 );
        add_action( 'wp_ajax_nopriv_ttbm_download_csv', array( $this, 'maybe_handle_existing_tour_csv' ), 1 );
        add_action( 'wp_ajax_ttbm_generate_attendee_pdf', array( $this, 'maybe_handle_existing_tour_pdf' ), 1 );
        add_action( 'wp_ajax_nopriv_ttbm_generate_attendee_pdf', array( $this, 'maybe_handle_existing_tour_pdf' ), 1 );
    }

    public static function is_tour_active() {
        return class_exists( 'TTBM_Global_Function' ) && ( class_exists( 'TTBM_Woocommerce' ) || class_exists( 'TTBM_Function' ) );
    }

    private function is_reporting_available() {
        return self::is_tour_active() && class_exists( 'TTBM_Function_PRO' );
    }

    public function is_integration_enabled() {
        $settings = get_option( 'apd_settings', array() );
        return ( $settings['tour_integration_enabled'] ?? 'yes' ) === 'yes';
    }

    public function save_settings( $settings, $tab, $posted ) {
        if ( 'general' !== $tab ) {
            return $settings;
        }

        $settings['tour_integration_enabled'] = isset( $posted['tour_integration_enabled'] ) ? 'yes' : 'no';

        return $settings;
    }

    public function maybe_disable_tour_deposits( $enabled, $product_id ) {
        if ( ! $enabled || $this->is_integration_enabled() || ! self::is_tour_active() ) {
            return $enabled;
        }

        return $this->is_tour_product( $product_id ) ? false : $enabled;
    }

    public function render_dashboard_badge() {
        if ( ! self::is_tour_active() ) {
            return;
        }
        ?>
        <span class="apd-pro-badge" style="background:rgba(14,165,233,.16);color:#e0f2fe;">
            <span class="dashicons dashicons-location-alt"></span>
            <?php esc_html_e( 'Tour Active', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
        </span>
        <?php
    }

    public function render_general_status_card() {
        if ( ! self::is_tour_active() ) {
            return;
        }

        $enabled = $this->is_integration_enabled();
        ?>
        <div class="apd-card" id="apd-tour-settings-card">
            <div class="apd-card-header">
                <h3><?php esc_html_e( 'Tour Booking Integration', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3>
            </div>
            <div class="apd-card-body">
                <div class="apd-field-row">
                    <div class="apd-field-label">
                        <label><?php esc_html_e( 'Integration Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                        <p class="apd-field-desc"><?php esc_html_e( 'Tour Booking Manager is active. Tour bookings can use APD deposit calculations on the WooCommerce checkout page.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                    </div>
                    <div class="apd-field-input">
                        <span class="apd-pro-badge" style="background:rgba(16,185,129,.14);color:#065f46;">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e( 'Plugin Active', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                        </span>
                    </div>
                </div>
                <div class="apd-field-row">
                    <div class="apd-field-label">
                        <label for="apd-tour-integration-enabled"><?php esc_html_e( 'Enable Tour Integration', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                        <p class="apd-field-desc"><?php esc_html_e( 'Turn the checkout deposit integration for Tour Booking Manager bookings on or off from this setting.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                    </div>
                    <div class="apd-field-input">
                        <label class="apd-toggle">
                            <input
                                type="checkbox"
                                name="tour_integration_enabled"
                                id="apd-tour-integration-enabled"
                                value="yes"
                                <?php checked( $enabled, true ); ?>
                            />
                            <span class="apd-toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <p class="apd-field-desc" style="margin-top:12px;">
                    <?php echo esc_html( $enabled ? __( 'The integration is enabled. Tour bookings will show APD payment options on checkout and use deposit calculations there.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) : __( 'The integration is disabled. Tour-linked products will behave like normal full-payment bookings until you turn it back on.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) ); ?>
                </p>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var card = document.getElementById('apd-tour-settings-card');
                var form = document.querySelector('.apd-settings-form[data-tab="general"]');

                if (card && form && card.parentNode !== form) {
                    form.insertBefore(card, form.firstChild);
                }
            });
        </script>
        <?php
    }

    public function enqueue_checkout_assets() {
        if ( ! $this->is_integration_enabled() || ! is_checkout() || is_order_received_page() || ! $this->cart_has_tour_deposit_items() ) {
            return;
        }

        $css = '
.apd-tour-checkout-selector{margin:0 0 18px}
.apd-tour-checkout-selector .apd-product-deposit-form{margin:0}
';

        if ( wp_style_is( 'apd-pro-public', 'enqueued' ) ) {
            wp_add_inline_style( 'apd-pro-public', $css );
        } elseif ( wp_style_is( 'apd-public', 'enqueued' ) ) {
            wp_add_inline_style( 'apd-public', $css );
        }

        if ( wp_script_is( 'apd-public', 'enqueued' ) ) {
            wp_add_inline_script(
                'apd-public',
                '(function($){"use strict";var isUpdating=false;$(document).on("change","input[name=\"apd_tour_checkout_payment_type\"]",function(){if(isUpdating||typeof apd_public==="undefined"){return;}isUpdating=true;$(".apd-tour-checkout-selector input").prop("disabled",true);$.post(apd_public.ajax_url,{action:"apd_tour_checkout_payment_type",nonce:apd_public.nonce,payment_type:$(this).val()}).always(function(){$(document.body).trigger("update_checkout");});});$(document.body).on("updated_checkout",function(){isUpdating=false;$(".apd-tour-checkout-selector input").prop("disabled",false);});})(jQuery);',
                'after'
            );
        }

        $this->enqueue_block_checkout_integration();
    }

    public function render_checkout_selector() {
        if ( ! self::is_tour_active() || ! $this->is_integration_enabled() ) {
            return;
        }

        $state = $this->get_checkout_state();
        if ( empty( $state['has_tour_deposit_items'] ) ) {
            return;
        }

        $settings      = get_option( 'apd_settings', array() );
        $deposit_label = $settings['deposit_label'] ?? __( 'Deposit', 'advanced-partial-payment' );
        $balance_label = $settings['due_balance_label'] ?? __( 'Due Balance', 'advanced-partial-payment' );
        $deposit_text  = $settings['deposit_text'] ?? __( 'Pay a deposit of {deposit_amount}', 'advanced-partial-payment' );
        $full_text     = $settings['full_payment_text'] ?? __( 'Pay full amount of {full_amount}', 'advanced-partial-payment' );

        $deposit_text = str_replace( '{deposit_amount}', wc_price( $state['deposit_total'] ), $deposit_text );
        $full_text    = str_replace( '{full_amount}', wc_price( $state['full_total'] ), $full_text );
        ?>
        <div class="apd-tour-checkout-selector">
            <div class="apd-product-deposit-form">
                <div class="apd-deposit-header">
                    <span class="apd-deposit-icon">&#128176;</span>
                    <span class="apd-deposit-title"><?php esc_html_e( 'Payment Options', 'advanced-partial-payment' ); ?></span>
                </div>
                <div class="apd-deposit-options">
                    <label class="apd-deposit-option">
                        <input
                            type="radio"
                            name="apd_tour_checkout_payment_type"
                            value="deposit"
                            <?php checked( 'deposit' === $state['selected_payment_type'] ); ?>
                        />
                        <div class="apd-option-content">
                            <span class="apd-option-radio"></span>
                            <div class="apd-option-text">
                                <span class="apd-option-label"><?php echo wp_kses_post( $deposit_text ); ?></span>
                                <span class="apd-option-detail"><?php echo esc_html( $balance_label ); ?>: <?php echo wp_kses_post( wc_price( $state['balance_due'] ) ); ?></span>
                            </div>
                        </div>
                    </label>
                    <?php if ( $state['allow_full_payment'] ) : ?>
                    <label class="apd-deposit-option">
                        <input
                            type="radio"
                            name="apd_tour_checkout_payment_type"
                            value="full"
                            <?php checked( 'full' === $state['selected_payment_type'] ); ?>
                        />
                        <div class="apd-option-content">
                            <span class="apd-option-radio"></span>
                            <div class="apd-option-text">
                                <span class="apd-option-label"><?php echo wp_kses_post( $full_text ); ?></span>
                                <span class="apd-option-detail"><?php echo esc_html( $deposit_label ); ?>: <?php echo wp_kses_post( wc_price( $state['deposit_total'] ) ); ?></span>
                            </div>
                        </div>
                    </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_update_checkout_payment_type() {
        check_ajax_referer( 'apd_public_nonce', 'nonce' );

        if ( ! $this->is_integration_enabled() ) {
            wp_send_json_error( __( 'Tour integration is disabled.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
        }

        if ( ! WC()->cart ) {
            wp_send_json_error( __( 'Cart not available.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
        }

        $payment_type = sanitize_text_field( wp_unslash( $_POST['payment_type'] ?? 'deposit' ) );
        $updated      = false;
        $deposit      = APD_Deposit::instance();

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( ! $this->is_tour_cart_item( $cart_item ) || ! $deposit->is_deposit_enabled( $cart_item['product_id'] ) ) {
                continue;
            }

            $forced = $deposit->is_force_deposit_enabled( $cart_item['product_id'] );
            WC()->cart->cart_contents[ $cart_item_key ]['apd_pay_deposit'] = ( $forced || 'deposit' === $payment_type ) ? 'yes' : 'no';
            $updated = true;
        }

        if ( $updated ) {
            WC()->cart->set_session();
            WC()->cart->calculate_totals();
            wp_send_json_success();
        }

        wp_send_json_error( __( 'No tour checkout items found.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
    }

    private function get_checkout_state() {
        $deposit       = APD_Deposit::instance();
        $tour_items    = array();
        $allow_full    = true;
        $selected_type = 'deposit';

        if ( ! WC()->cart ) {
            return array(
                'has_tour_deposit_items' => false,
            );
        }

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( ! $this->is_tour_cart_item( $cart_item ) || ! $deposit->is_deposit_enabled( $cart_item['product_id'] ) ) {
                continue;
            }

            $tour_items[] = $cart_item;

            if ( ! $deposit->is_full_payment_allowed( $cart_item['product_id'] ) ) {
                $allow_full = false;
            }

            $selected_type = ( ( $cart_item['apd_pay_deposit'] ?? 'yes' ) === 'yes' ) ? 'deposit' : 'full';
        }

        if ( empty( $tour_items ) ) {
            return array(
                'has_tour_deposit_items' => false,
            );
        }

        if ( ! $allow_full ) {
            $selected_type = 'deposit';
        }

        $deposit_summary = $this->calculate_tour_payment_summary( 'deposit' );
        $full_summary    = $this->calculate_tour_payment_summary( 'full' );

        return array(
            'has_tour_deposit_items' => true,
            'allow_full_payment'     => $allow_full,
            'selected_payment_type'  => $selected_type,
            'deposit_total'          => floatval( $deposit_summary['deposit_amount'] ?? 0 ),
            'balance_due'            => floatval( $deposit_summary['balance_due'] ?? 0 ),
            'full_total'             => floatval( $full_summary['full_total'] ?? 0 ),
        );
    }

    private function cart_has_tour_deposit_items() {
        $state = $this->get_checkout_state();
        return ! empty( $state['has_tour_deposit_items'] );
    }

    private function enqueue_block_checkout_integration() {
        if ( ! $this->is_block_checkout_page() ) {
            return;
        }

        $state = $this->get_checkout_state();
        if ( empty( $state['has_tour_deposit_items'] ) ) {
            return;
        }

        $settings      = get_option( 'apd_settings', array() );
        $balance_label = $settings['due_balance_label'] ?? __( 'Due Balance', 'advanced-partial-payment' );
        $deposit_text  = $settings['deposit_text'] ?? __( 'Pay a deposit of {deposit_amount}', 'advanced-partial-payment' );
        $full_text     = $settings['full_payment_text'] ?? __( 'Pay full amount of {full_amount}', 'advanced-partial-payment' );

        $payload = array(
            'selectedType' => $state['selected_payment_type'],
            'allowFull'    => ! empty( $state['allow_full_payment'] ),
            'depositText'  => str_replace( '{deposit_amount}', wc_price( $state['deposit_total'] ), $deposit_text ),
            'fullText'     => str_replace( '{full_amount}', wc_price( $state['full_total'] ), $full_text ),
            'balanceLabel' => $balance_label,
            'balanceHtml'  => wc_price( $state['balance_due'] ),
            'fullDetail'   => __( 'Pay the complete amount now.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'apd_public_nonce' ),
            'refreshText'  => __( 'Updating payment option...', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
        );

        $script = 'window.apdTourBlocks=' . wp_json_encode( $payload ) . ';';
        $script .= <<<'JS'
(function(){
    "use strict";

    var config = window.apdTourBlocks || null;

    if (!config) {
        return;
    }

    var isUpdating = false;

    var createSelector = function () {
        var wrapper = document.createElement("div");
        wrapper.className = "apd-tour-checkout-selector apd-tour-checkout-selector--block";
        wrapper.innerHTML =
            '<div class="apd-product-deposit-form">' +
                '<div class="apd-deposit-header">' +
                    '<span class="apd-deposit-icon">&#128176;</span>' +
                    '<span class="apd-deposit-title">Payment Options</span>' +
                '</div>' +
                '<div class="apd-deposit-options">' +
                    '<label class="apd-deposit-option">' +
                        '<input type="radio" name="apd_tour_checkout_payment_type" value="deposit"' + (config.selectedType === "deposit" ? ' checked="checked"' : "") + ' />' +
                        '<div class="apd-option-content">' +
                            '<span class="apd-option-radio"></span>' +
                            '<div class="apd-option-text">' +
                                '<span class="apd-option-label">' + config.depositText + '</span>' +
                                '<span class="apd-option-detail">' + config.balanceLabel + ': ' + config.balanceHtml + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</label>' +
                    (config.allowFull ? (
                        '<label class="apd-deposit-option">' +
                            '<input type="radio" name="apd_tour_checkout_payment_type" value="full"' + (config.selectedType === "full" ? ' checked="checked"' : "") + ' />' +
                            '<div class="apd-option-content">' +
                                '<span class="apd-option-radio"></span>' +
                                '<div class="apd-option-text">' +
                                    '<span class="apd-option-label">' + config.fullText + '</span>' +
                                    '<span class="apd-option-detail">' + (isUpdating && config.selectedType === "full" ? config.refreshText : config.fullDetail) + '</span>' +
                                '</div>' +
                            '</div>' +
                        '</label>'
                    ) : '') +
                '</div>' +
            '</div>';

        return wrapper;
    };

    var findTarget = function () {
        var selectors = [
            ".wc-block-checkout__main .wc-block-checkout__actions_row",
            ".wc-block-checkout__main .wc-block-components-checkout-place-order-button",
            ".wc-block-checkout__main .wc-block-checkout__payment-method",
            ".wc-block-checkout__main .wp-block-woocommerce-checkout-payment-block",
            ".wc-block-checkout__sidebar .wc-block-components-totals-wrapper"
        ];

        for (var i = 0; i < selectors.length; i++) {
            var node = document.querySelector(selectors[i]);
            if (node) {
                return node;
            }
        }

        return null;
    };

    var mountSelector = function () {
        var target = findTarget();

        if (!target) {
            return;
        }

        var existing = document.querySelector(".apd-tour-checkout-selector--block");
        if (existing && existing.parentNode) {
            if (target.previousElementSibling === existing || target === existing.parentNode) {
                return;
            }
            existing.parentNode.removeChild(existing);
        }

        var selector = createSelector();
        target.parentNode.insertBefore(selector, target);
    };

    var refreshBlocksCheckout = function () {
        try {
            if (window.wp && window.wp.data && typeof window.wp.data.dispatch === "function") {
                var cartStore = window.wp.data.dispatch("wc/store/cart");

                if (cartStore) {
                    if (typeof cartStore.invalidateResolutionForStore === "function") {
                        cartStore.invalidateResolutionForStore();
                        return true;
                    }

                    if (typeof cartStore.invalidateResolutionForStoreSelector === "function") {
                        cartStore.invalidateResolutionForStoreSelector("getCartData", []);
                        return true;
                    }
                }
            }
        } catch (error) {}

        try {
            if (window.jQuery) {
                window.jQuery(document.body).trigger("update_checkout");
                return true;
            }
        } catch (error) {}

        return false;
    };

    var finishUpdate = function () {
        isUpdating = false;
        var formInputs = document.querySelectorAll('.apd-tour-checkout-selector input[name="apd_tour_checkout_payment_type"]');
        Array.prototype.forEach.call(formInputs, function (node) {
            node.disabled = false;
        });
        mountSelector();
    };

    document.addEventListener("change", function (event) {
        var input = event.target;

        if (!input || input.name !== "apd_tour_checkout_payment_type") {
            return;
        }

        isUpdating = true;
        config.selectedType = input.value;
        mountSelector();

        var formInputs = document.querySelectorAll('.apd-tour-checkout-selector input[name="apd_tour_checkout_payment_type"]');
        Array.prototype.forEach.call(formInputs, function (node) {
            node.disabled = true;
        });

        var body = new window.FormData();
        body.append("action", "apd_tour_checkout_payment_type");
        body.append("nonce", config.nonce);
        body.append("payment_type", input.value);

        window.fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: body
        }).then(function () {
            if (!refreshBlocksCheckout()) {
                finishUpdate();
            }
        }).catch(function () {
            finishUpdate();
        });
    });

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", mountSelector);
    } else {
        mountSelector();
    }

    if (typeof window.MutationObserver === "function") {
        var debounceTimer = null;
        var observer = new window.MutationObserver(function () {
            if (debounceTimer) { window.clearTimeout(debounceTimer); }
            debounceTimer = window.setTimeout(mountSelector, 150);
        });
        // Scope to checkout/cart containers instead of document.body for performance.
        var observeRoot = document.querySelector('.wp-block-woocommerce-checkout, .wp-block-woocommerce-cart, [data-block-name="woocommerce/checkout"], [data-block-name="woocommerce/cart"]');
        if (!observeRoot) { observeRoot = document.body; }
        observer.observe(observeRoot, { childList: true, subtree: true });
    }

    if (window.wp && window.wp.data && typeof window.wp.data.subscribe === "function") {
        window.wp.data.subscribe(function () {
            if (isUpdating) {
                finishUpdate();
            }
        });
    }
})();
JS;

        if ( wp_script_is( 'apd-blocks', 'enqueued' ) ) {
            wp_add_inline_script( 'apd-blocks', $script, 'after' );
        } elseif ( wp_script_is( 'apd-public', 'enqueued' ) ) {
            wp_add_inline_script( 'apd-public', $script, 'after' );
        }
    }

    private function is_tour_cart_item( $cart_item ) {
        $tour_id = 0;

        // Check multiple possible keys Tour Booking might use in cart item
        if ( ! empty( $cart_item['ttbm_id'] ) ) {
            $tour_id = intval( $cart_item['ttbm_id'] );
        } elseif ( ! empty( $cart_item['ttbm_hotel_booking'] ) ) {
            $tour_id = intval( $cart_item['ttbm_hotel_booking'] );
        } elseif ( ! empty( $cart_item['tour_id'] ) ) {
            $tour_id = intval( $cart_item['tour_id'] );
        } elseif ( ! empty( $cart_item['ttbm_tour_id'] ) ) {
            $tour_id = intval( $cart_item['ttbm_tour_id'] );
        }

        if ( $tour_id > 0 ) {
            return true;
        }

        $product_id = intval( $cart_item['product_id'] ?? 0 );
        return $product_id > 0 && $this->is_tour_product( $product_id );
    }

    private function is_tour_product( $product_id ) {
        $product_id = intval( $product_id );

        if ( $product_id <= 0 ) {
            return false;
        }

        $linked_tour_id = get_post_meta( $product_id, 'link_ttbm_id', true );

        return ! empty( $linked_tour_id );
    }

    private function calculate_tour_payment_summary( $payment_type ) {
        $deposit_engine = APD_Deposit::instance();

        if ( ! WC()->cart ) {
            return array(
                'has_deposit'    => false,
                'full_total'     => 0,
                'deposit_amount' => 0,
                'balance_due'    => 0,
            );
        }

        $cart_subtotal          = 0;
        $deposit_subtotal       = 0;
        $has_deposit            = false;
        $deposit_only_fee_total = 0;
        $fee_total              = 0;

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product_id = intval( $cart_item['product_id'] ?? 0 );
            $line_total = floatval( $cart_item['line_total'] ?? 0 );
            $quantity   = max( 1, intval( $cart_item['quantity'] ?? 1 ) );

            $cart_subtotal += $line_total;

            $is_tour_deposit_item = $this->is_tour_cart_item( $cart_item ) && $deposit_engine->is_deposit_enabled( $product_id );
            $pay_deposit          = isset( $cart_item['apd_pay_deposit'] ) ? $cart_item['apd_pay_deposit'] : 'yes';

            if ( $is_tour_deposit_item ) {
                $pay_deposit = ( 'deposit' === $payment_type ) ? 'yes' : 'no';
            }

            if ( $deposit_engine->is_deposit_enabled( $product_id ) && 'yes' === $pay_deposit ) {
                $unit_price       = $line_total / $quantity;
                $unit_deposit     = $deposit_engine->get_deposit_amount( $product_id, $unit_price );
                $deposit_subtotal += $unit_deposit * $quantity;
                $has_deposit      = true;
                continue;
            }

            $deposit_subtotal += $line_total;
        }

        $shipping_total = floatval( WC()->cart->get_shipping_total() );
        $tax_total      = floatval( WC()->cart->get_total_tax() );

        foreach ( WC()->cart->get_fees() as $fee ) {
            $current_fee_total = isset( $fee->total ) ? floatval( $fee->total ) : ( isset( $fee->amount ) ? floatval( $fee->amount ) : 0 );

            if ( $current_fee_total > 0 ) {
                if ( class_exists( 'APD_Deposit_Fees' ) && APD_Deposit_Fees::is_partial_payment_fee( $fee ) ) {
                    if ( 'deposit' === $payment_type ) {
                        $deposit_only_fee_total += $current_fee_total;
                    }
                } else {
                    $fee_total += $current_fee_total;
                }
            }
        }

        if ( 'deposit' === $payment_type ) {
            $fee_total += $deposit_only_fee_total;
        }

        $full_total    = $cart_subtotal + $shipping_total + $tax_total + $fee_total;
        $deposit_ratio = $cart_subtotal > 0 ? min( 1, $deposit_subtotal / $cart_subtotal ) : 0;
        $deposit_total = $deposit_subtotal;

        if ( $cart_subtotal > 0 ) {
            $deposit_total += ( $shipping_total * $deposit_ratio );
            $deposit_total += ( $tax_total * $deposit_ratio );
            $deposit_total += ( ( $fee_total - $deposit_only_fee_total ) * $deposit_ratio );
        }

        if ( 'deposit' === $payment_type ) {
            $deposit_total += $deposit_only_fee_total;
        }

        $deposit_total = round( min( $deposit_total, $full_total ), wc_get_price_decimals() );
        $balance_due   = round( max( 0, $full_total - $deposit_total ), wc_get_price_decimals() );

        return array(
            'has_deposit'    => $has_deposit,
            'full_total'     => round( $full_total, wc_get_price_decimals() ),
            'deposit_amount' => $deposit_total,
            'balance_due'    => $balance_due,
        );
    }

    private function is_block_checkout_page() {
        if ( ! is_checkout() ) {
            return false;
        }
        if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout' ) ) {
            return true;
        }
        // FSE themes: has_block() misses blocks in templates, so check the template.
        if ( function_exists( 'wc_current_theme_is_fse_theme' ) && wc_current_theme_is_fse_theme() ) {
            $template = get_page_template_slug();
            if ( $template && false !== strpos( $template, 'checkout' ) ) {
                return true;
            }
            $page_id = wc_get_page_id( 'checkout' );
            if ( $page_id > 0 ) {
                $template = get_page_template_slug( $page_id );
                if ( $template && false !== strpos( $template, 'checkout' ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public function render_admin_styles() {
        if ( ! $this->is_reporting_available() || ( ! $this->is_guest_list_page() && ! $this->is_order_list_page() ) ) {
            return;
        }
        ?>
        <style>
            .apd-tour-summary { min-width: 220px; line-height: 1.45; }
            .apd-tour-summary strong { display:block; margin-bottom:4px; }
            .apd-tour-summary small { display:block; color:#6b7280; }
            .apd-tour-summary-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:4px 12px; margin-top:6px; font-size:12px; }
            .apd-tour-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; margin-bottom:4px; }
            .apd-tour-badge-deposit { background:#ede9fe; color:#5b21b6; }
            .apd-tour-badge-full { background:#e5f7eb; color:#166534; }
            .apd-tour-order-paid { display:flex; flex-direction:column; gap:2px; line-height:1.35; }
            .apd-tour-order-paid strong { font-size:14px; }
            .apd-tour-order-paid small { display:block; color:#6b7280; }
        </style>
        <?php
    }

    public function render_guest_list_head() {
        if ( $this->is_reporting_available() ) {
            echo '<th class="textLeft">' . esc_html__( 'Deposit Info', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</th>';
        }
    }

    public function render_guest_list_cell( $attendee_id ) {
        if ( ! $this->is_reporting_available() ) {
            return;
        }

        $context = $this->get_tour_context( absint( $attendee_id ) );
        echo '<td>' . wp_kses_post( $this->render_summary_markup( $context ) ) . '</td>';
    }

    public function maybe_handle_existing_tour_csv() {
        if ( ! $this->is_reporting_available() || ! class_exists( 'TTBM_Pro_CSV' ) ) {
            return;
        }

        if ( ! isset( $_REQUEST['action'], $_REQUEST['document_type'] ) || 'ttbm_download_csv' !== $_REQUEST['action'] || 'csv' !== $_REQUEST['document_type'] ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
            return;
        }

        check_admin_referer( 'ttbm_download_csv' );

        $tour_id       = isset( $_REQUEST['tour_id'] ) ? TTBM_Global_Function::data_sanitize( wp_unslash( $_REQUEST['tour_id'] ) ) : '';
        $hotel_id      = isset( $_REQUEST['hotel_id'] ) ? TTBM_Global_Function::data_sanitize( wp_unslash( $_REQUEST['hotel_id'] ) ) : '';
        $tour_date     = isset( $_REQUEST['tour_date'] ) ? TTBM_Global_Function::data_sanitize( wp_unslash( $_REQUEST['tour_date'] ) ) : '';
        $filter_key    = isset( $_REQUEST['filter_key'] ) ? TTBM_Global_Function::data_sanitize( wp_unslash( $_REQUEST['filter_key'] ) ) : '';
        $filter_value  = isset( $_REQUEST['filter_value'] ) ? TTBM_Global_Function::data_sanitize( wp_unslash( $_REQUEST['filter_value'] ) ) : '';
        $post_per_page = isset( $_REQUEST['post_per_page'] ) ? TTBM_Global_Function::data_sanitize( wp_unslash( $_REQUEST['post_per_page'] ) ) : 50;
        $post_per_page = $post_per_page > 0 ? $post_per_page : 50;

        $csv          = new TTBM_Pro_CSV();
        $header_row   = $csv->csv_header( $tour_id );
        $header_row[] = __( 'Payment Mode', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $header_row[] = __( 'Order Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $header_row[] = __( 'Deposit Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $header_row[] = __( 'Amount Paid', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $header_row[] = __( 'Balance Due', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $header_row[] = __( 'Payment Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );

        $data_rows    = $csv->csv_data( $tour_id, $tour_date, $hotel_id, $filter_key, $filter_value, $post_per_page );
        $attendee_ids = $this->get_attendee_ids( $tour_id, $tour_date, $hotel_id, $filter_key, $filter_value, $post_per_page );
        $filename     = 'Tour_guest_Export_' . sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ?? 'site' ) ) . '_' . time() . '.csv';
        $handle       = fopen( 'php://output', 'w' );

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        fprintf( $handle, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-type: text/csv' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Expires: 0' );
        header( 'Pragma: public' );

        if ( ! empty( $data_rows ) ) {
            fputcsv( $handle, $header_row );
            foreach ( $data_rows as $index => $data_row ) {
                $attendee_id = isset( $attendee_ids[ $index ] ) ? absint( $attendee_ids[ $index ] ) : 0;
                $context     = $this->get_tour_context( $attendee_id );
                $data_row[]  = $context['payment_mode'];
                $data_row[]  = $this->format_money( $context['total_amount'] );
                $data_row[]  = $this->format_money( $context['deposit_amount'] );
                $data_row[]  = $this->format_money( $context['amount_paid'] );
                $data_row[]  = $this->format_money( $context['balance_due'] );
                $data_row[]  = $context['payment_plan'];
                fputcsv( $handle, $data_row );
            }
        } else {
            fputcsv( $handle, array( esc_html__( 'No Data Found !', 'ttbm-pro' ) ) );
        }

        fclose( $handle );
        exit;
    }

    public function maybe_handle_existing_tour_pdf() {
        if ( ! $this->is_reporting_available() || ! class_exists( 'TTBM_Attendee_Pdf' ) ) {
            return;
        }

        if ( empty( $_GET['action'] ) || 'ttbm_generate_attendee_pdf' !== $_GET['action'] ) {
            return;
        }

        check_admin_referer( 'ttbm_generate_attendee_pdf' );

        $post_id      = isset( $_GET['post_id'] ) ? sanitize_text_field( wp_unslash( $_GET['post_id'] ) ) : '';
        $attendees    = isset( $_GET['attendees'] ) ? sanitize_text_field( wp_unslash( $_GET['attendees'] ) ) : '';
        $attendee_ids = $attendees ? array_filter( array_map( 'absint', explode( ',', $attendees ) ) ) : array();
        $date         = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : '';

        $pdf  = new TTBM_Attendee_Pdf();
        $html = $pdf->create_attendee_pdf_file( $post_id, $date, $attendee_ids );
        $html = $this->inject_pdf_deposit_info( $html, $post_id, $date, $attendee_ids );

        header( 'Content-Type: application/pdf; charset=UTF-8' );

        $mpdf                           = new \Mpdf\Mpdf();
        $mpdf->allow_charset_conversion = true;
        $mpdf->autoScriptToLang         = true;
        $mpdf->baseScript               = 1;
        $mpdf->autoVietnamese           = true;
        $mpdf->autoArabic               = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->WriteHTML( $html );
        $mpdf->Output( 'attendee_pdf.pdf', 'D' );
        exit;
    }

    public function render_ticket_pdf_deposit_info( $ticket_id ) {
        $ticket_id = absint( $ticket_id );
        if ( $ticket_id <= 0 ) {
            return;
        }

        $order_id = absint( TTBM_Global_Function::get_post_info( $ticket_id, 'ttbm_order_id' ) );
        $summary  = $this->get_order_payment_context( $order_id );

        if ( empty( $summary['is_deposit'] ) ) {
            return;
        }
        ?>
        <li>
            <strong><?php esc_html_e( 'Payment Mode : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php esc_html_e( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Paid Amount : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo wp_kses_post( $summary['amount_paid_html'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Deposit Amount : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo wp_kses_post( $summary['deposit_amount_html'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Total Amount : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo wp_kses_post( $summary['total_amount_html'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Due Balance : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo wp_kses_post( $summary['balance_due_html'] ); ?>
        </li>
        <?php if ( ! empty( $summary['payment_plan'] ) ) : ?>
        <li>
            <strong><?php esc_html_e( 'Payment Plan : ', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
            <?php echo esc_html( $summary['payment_plan'] ); ?>
        </li>
        <?php endif;
    }

    public function ajax_tour_order_summaries() {
        check_ajax_referer( 'apd_tour_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to access this data.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), 403 );
        }

        $order_ids = isset( $_POST['order_ids'] ) ? (array) wp_unslash( $_POST['order_ids'] ) : array();
        $order_ids = array_filter( array_map( 'absint', $order_ids ) );
        $payload   = array();

        foreach ( $order_ids as $order_id ) {
            $payload[ $order_id ] = $this->get_order_payment_context( $order_id );
        }

        wp_send_json_success( $payload );
    }

    private function get_attendee_ids( $tour_id = '', $tour_date = '', $hotel_id = '', $filter_key = '', $filter_value = '', $post_per_page = -1 ) {
        $ids         = array();
        $guest_query = TTBM_Function_PRO::attendee_query( $tour_id, $tour_date, $hotel_id, $filter_key, $filter_value, $post_per_page );

        if ( $guest_query && ! empty( $guest_query->posts ) ) {
            foreach ( $guest_query->posts as $attendee ) {
                $ids[] = absint( $attendee->ID );
            }
        }

        return $ids;
    }

    private function get_tour_context( $attendee_id ) {
        $context = array(
            'payment_mode'   => __( 'Full Payment', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'is_deposit'     => false,
            'total_amount'   => 0,
            'deposit_amount' => 0,
            'amount_paid'    => 0,
            'balance_due'    => 0,
            'payment_plan'   => '',
        );

        if ( $attendee_id <= 0 ) {
            return $context;
        }

        $order_id = absint( TTBM_Global_Function::get_post_info( $attendee_id, 'ttbm_order_id' ) );
        $order    = $order_id > 0 ? wc_get_order( $order_id ) : false;

        if ( ! $order ) {
            return $context;
        }

        $context['payment_plan'] = sanitize_text_field( (string) $order->get_meta( '_apd_payment_plan_name' ) );

        if ( class_exists( 'APD_Order' ) && APD_Order::is_deposit_order( $order ) ) {
            $details                   = APD_Order::get_deposit_details( $order );
            $context['payment_mode']   = __( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
            $context['is_deposit']     = true;
            $context['total_amount']   = isset( $details['total_amount'] ) ? (float) $details['total_amount'] : (float) $order->get_total();
            $context['deposit_amount'] = isset( $details['deposit_amount'] ) ? (float) $details['deposit_amount'] : 0;
            $context['amount_paid']    = isset( $details['amount_paid'] ) ? (float) $details['amount_paid'] : $context['deposit_amount'];
            $context['balance_due']    = isset( $details['balance_due'] ) ? (float) $details['balance_due'] : 0;
        } else {
            $context['total_amount'] = (float) $order->get_total();
            $context['amount_paid']  = (float) $order->get_total();
        }

        return $context;
    }

    private function get_order_payment_context( $order_id ) {
        $order_id = absint( $order_id );
        $order    = $order_id > 0 ? wc_get_order( $order_id ) : false;

        if ( ! $order ) {
            return array(
                'exists' => false,
            );
        }

        $summary = array(
            'exists'              => true,
            'is_deposit'          => false,
            'payment_mode'        => __( 'Full Payment', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            'amount_paid'         => (float) $order->get_total(),
            'deposit_amount'      => 0,
            'total_amount'        => (float) $order->get_total(),
            'balance_due'         => 0,
            'payment_plan'        => sanitize_text_field( (string) $order->get_meta( '_apd_payment_plan_name' ) ),
            'amount_paid_html'    => wc_price( $order->get_total() ),
            'deposit_amount_html' => wc_price( 0 ),
            'total_amount_html'   => wc_price( $order->get_total() ),
            'balance_due_html'    => wc_price( 0 ),
        );

        if ( class_exists( 'APD_Order' ) && APD_Order::is_deposit_order( $order ) ) {
            $details = APD_Order::get_deposit_details( $order );

            if ( is_array( $details ) ) {
                $summary['is_deposit']          = true;
                $summary['payment_mode']        = __( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
                $summary['amount_paid']         = (float) ( $details['amount_paid'] ?? 0 );
                $summary['deposit_amount']      = (float) ( $details['deposit_amount'] ?? 0 );
                $summary['total_amount']        = (float) ( $details['total_amount'] ?? 0 );
                $summary['balance_due']         = (float) ( $details['balance_due'] ?? 0 );
                $summary['amount_paid_html']    = wc_price( $summary['amount_paid'] );
                $summary['deposit_amount_html'] = wc_price( $summary['deposit_amount'] );
                $summary['total_amount_html']   = wc_price( $summary['total_amount'] );
                $summary['balance_due_html']    = wc_price( $summary['balance_due'] );
            }
        }

        return $summary;
    }

    private function render_summary_markup( $context ) {
        $badge_class = $context['is_deposit'] ? 'apd-tour-badge apd-tour-badge-deposit' : 'apd-tour-badge apd-tour-badge-full';
        $badge_label = $context['is_deposit'] ? __( 'Deposit Order', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) : __( 'Full Payment', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $html        = '<div class="apd-tour-summary">';
        $html       .= '<span class="' . esc_attr( $badge_class ) . '">' . esc_html( $badge_label ) . '</span>';
        $html       .= '<strong>' . esc_html( $this->format_money( $context['total_amount'] ) ) . '</strong>';
        $html       .= '<small>' . esc_html__( 'Order Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</small>';
        $html       .= '<div class="apd-tour-summary-grid">';

        if ( $context['is_deposit'] ) {
            $html .= '<span>' . esc_html__( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $this->format_money( $context['deposit_amount'] ) ) . '</span>';
        }

        $html .= '<span>' . esc_html__( 'Paid', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $this->format_money( $context['amount_paid'] ) ) . '</span>';
        $html .= '<span>' . esc_html__( 'Balance', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $this->format_money( $context['balance_due'] ) ) . '</span>';

        if ( ! empty( $context['payment_plan'] ) ) {
            $html .= '<span>' . esc_html__( 'Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $context['payment_plan'] ) . '</span>';
        }

        $html .= '</div></div>';

        return $html;
    }

    private function inject_pdf_deposit_info( $html, $post_id, $date, $attendee_ids ) {
        $target_ids = ! empty( $attendee_ids ) ? $attendee_ids : $this->get_attendee_ids( $post_id, $date, '', '', '', -1 );
        if ( empty( $target_ids ) || empty( $html ) ) {
            return $html;
        }

        libxml_use_internal_errors( true );
        $document = new DOMDocument( '1.0', 'UTF-8' );
        $loaded   = $document->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();

        if ( ! $loaded ) {
            return $html;
        }

        $rows      = $document->getElementsByTagName( 'tr' );
        $row_index = 0;

        foreach ( $rows as $row ) {
            if ( 0 === $row_index ) {
                $row_index++;
                continue;
            }

            $cells = $row->getElementsByTagName( 'td' );
            if ( $cells->length < 5 ) {
                continue;
            }

            $attendee_id = isset( $target_ids[ $row_index - 1 ] ) ? absint( $target_ids[ $row_index - 1 ] ) : 0;
            if ( ! $attendee_id ) {
                $row_index++;
                continue;
            }

            $context = $this->get_tour_context( $attendee_id );
            $cell    = $cells->item( 4 );
            $append  = $document->createElement( 'div' );
            $append->setAttribute( 'style', 'margin-top:10px;padding-top:8px;border-top:1px solid #d1d5db;font-size:11px;line-height:1.5;' );
            $append->appendChild( $document->createElement( 'strong', esc_html__( 'Payment Summary', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) ) );
            $append->appendChild( $document->createElement( 'br' ) );
            $append->appendChild( $document->createTextNode( sprintf( '%s %s', __( 'Mode:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $context['payment_mode'] ) ) );
            $append->appendChild( $document->createElement( 'br' ) );
            $append->appendChild( $document->createTextNode( sprintf( '%s %s', __( 'Total:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $this->format_money( $context['total_amount'] ) ) ) );
            $append->appendChild( $document->createElement( 'br' ) );
            if ( $context['is_deposit'] ) {
                $append->appendChild( $document->createTextNode( sprintf( '%s %s', __( 'Deposit:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $this->format_money( $context['deposit_amount'] ) ) ) );
                $append->appendChild( $document->createElement( 'br' ) );
            }
            $append->appendChild( $document->createTextNode( sprintf( '%s %s', __( 'Paid:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $this->format_money( $context['amount_paid'] ) ) ) );
            $append->appendChild( $document->createElement( 'br' ) );
            $append->appendChild( $document->createTextNode( sprintf( '%s %s', __( 'Balance:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $this->format_money( $context['balance_due'] ) ) ) );
            $cell->appendChild( $append );

            $row_index++;
        }

        return $document->saveHTML();
    }

    private function format_money( $amount ) {
        return wp_strip_all_tags( html_entity_decode( wc_price( (float) $amount ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
    }

    public function render_order_list_assets() {
        if ( ! $this->is_order_list_page() ) {
            return;
        }

        $config = array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'apd_tour_admin_nonce' ),
            'labels'  => array(
                'paid'    => __( 'Paid', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'deposit' => __( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'total'   => __( 'Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'balance' => __( 'Balance', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'plan'    => __( 'Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'full'    => __( 'Full payment', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            ),
        );
        ?>
        <script>
            window.apdTourOrderList = <?php echo wp_json_encode( $config ); ?>;
            (function(){
                "use strict";

                var config = window.apdTourOrderList || null;
                if (!config) {
                    return;
                }

                var root = document.getElementById("ttbm_order_list_result");
                if (!root) {
                    return;
                }

                var requestToken = 0;

                var getColumnIndex = function (table, label) {
                    var headers = table ? table.querySelectorAll("thead th") : [];
                    for (var i = 0; i < headers.length; i++) {
                        if ((headers[i].textContent || "").trim().toLowerCase() === label.toLowerCase()) {
                            return i;
                        }
                    }
                    return -1;
                };

                var renderSummary = function (cell, summary) {
                    if (!cell || !summary || !summary.exists) {
                        return;
                    }

                    if (!summary.is_deposit) {
                        cell.innerHTML = '<div class="apd-tour-order-paid"><strong>' + summary.amount_paid_html + '</strong><small>' + config.labels.full + '</small></div>';
                        return;
                    }

                    var html = '<div class="apd-tour-order-paid">' +
                        '<strong>' + summary.amount_paid_html + '</strong>' +
                        '<small>' + config.labels.deposit + ': ' + summary.deposit_amount_html + '</small>' +
                        '<small>' + config.labels.total + ': ' + summary.total_amount_html + '</small>' +
                        '<small>' + config.labels.balance + ': ' + summary.balance_due_html + '</small>';

                    if (summary.payment_plan) {
                        html += '<small>' + config.labels.plan + ': ' + summary.payment_plan + '</small>';
                    }

                    html += '</div>';
                    cell.innerHTML = html;
                };

                var enhanceTable = function () {
                    var table = root.querySelector("table.custom-table");
                    if (!table) {
                        return;
                    }

                    var orderIndex = getColumnIndex(table, "Order ID");
                    var paidIndex = getColumnIndex(table, "Paid Amount");
                    if (orderIndex < 0 || paidIndex < 0) {
                        return;
                    }

                    var rows = table.querySelectorAll("tbody tr");
                    var orderIds = [];
                    var cellsByOrder = {};

                    Array.prototype.forEach.call(rows, function (row) {
                        var cells = row.children;
                        if (!cells || !cells[orderIndex] || !cells[paidIndex]) {
                            return;
                        }

                        var orderId = parseInt((cells[orderIndex].textContent || "").replace(/[^\d]/g, ""), 10);
                        if (!orderId) {
                            return;
                        }

                        orderIds.push(orderId);
                        cellsByOrder[orderId] = cells[paidIndex];
                    });

                    if (!orderIds.length) {
                        return;
                    }

                    requestToken++;
                    var activeToken = requestToken;
                    var body = new window.FormData();
                    body.append("action", "apd_tour_order_summaries");
                    body.append("nonce", config.nonce);
                    orderIds.forEach(function (orderId) {
                        body.append("order_ids[]", orderId);
                    });

                    window.fetch(config.ajaxUrl, {
                        method: "POST",
                        credentials: "same-origin",
                        body: body
                    }).then(function (response) {
                        return response.json();
                    }).then(function (response) {
                        if (activeToken !== requestToken || !response || !response.success || !response.data) {
                            return;
                        }

                        Object.keys(response.data).forEach(function (orderId) {
                            renderSummary(cellsByOrder[orderId], response.data[orderId]);
                        });
                    }).catch(function () {});
                };

                var debounceTimer = null;
                var queueEnhance = function () {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = window.setTimeout(enhanceTable, 120);
                };

                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", queueEnhance);
                } else {
                    queueEnhance();
                }

                if (typeof window.MutationObserver === "function") {
                    var observer = new window.MutationObserver(queueEnhance);
                    observer.observe(root, { childList: true, subtree: true });
                }
            })();
        </script>
        <?php
    }

    private function is_guest_list_page() {
        return is_admin() && isset( $_GET['page'] ) && 'ttbm_guest_list' === sanitize_key( wp_unslash( $_GET['page'] ) );
    }

    private function is_order_list_page() {
        return is_admin() && isset( $_GET['page'] ) && 'ttbm_order_list' === sanitize_key( wp_unslash( $_GET['page'] ) );
    }
}
