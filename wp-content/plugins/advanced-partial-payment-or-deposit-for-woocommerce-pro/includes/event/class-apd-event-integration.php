<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mage EventPress integration for checkout-only deposit selection.
 */
class APD_Event_Integration {

    public function __construct() {
        add_action( 'apd_dashboard_header_badges', array( $this, 'render_dashboard_badge' ) );
        add_action( 'apd_general_tab_after_intro', array( $this, 'render_general_status_card' ) );
        add_filter( 'apd_save_settings', array( $this, 'save_settings' ), 10, 3 );
        add_filter( 'apd_deposit_enabled', array( $this, 'maybe_disable_event_deposits' ), 20, 2 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ), 5 );
        add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_checkout_selector' ), 5 );
        add_action( 'wp_ajax_apd_event_checkout_payment_type', array( $this, 'ajax_update_checkout_payment_type' ) );
        add_action( 'wp_ajax_nopriv_apd_event_checkout_payment_type', array( $this, 'ajax_update_checkout_payment_type' ) );
    }

    /**
     * Check whether EventPress is active.
     *
     * @return bool
     */
    public static function is_eventpress_active() {
        return defined( 'MPWEM_PLUGIN_DIR' ) || class_exists( 'MPWEM_Woocommerce' );
    }

    /**
     * Check whether the EventPress integration is enabled in APD settings.
     *
     * @return bool
     */
    public function is_integration_enabled() {
        $settings = get_option( 'apd_settings', array() );
        return ( $settings['eventpress_integration_enabled'] ?? 'yes' ) === 'yes';
    }

    /**
     * Persist the EventPress integration setting through the shared settings handler.
     *
     * @param array  $settings Current settings.
     * @param string $tab      Active settings tab.
     * @param array  $posted   Posted data.
     * @return array
     */
    public function save_settings( $settings, $tab, $posted ) {
        if ( 'general' !== $tab ) {
            return $settings;
        }

        $settings['eventpress_integration_enabled'] = isset( $posted['eventpress_integration_enabled'] ) ? 'yes' : 'no';

        return $settings;
    }

    /**
     * Disable APD deposits for EventPress-linked products when the integration is turned off.
     *
     * @param bool $enabled    Current enabled state.
     * @param int  $product_id Product ID.
     * @return bool
     */
    public function maybe_disable_event_deposits( $enabled, $product_id ) {
        if ( ! $enabled || $this->is_integration_enabled() || ! self::is_eventpress_active() ) {
            return $enabled;
        }

        return $this->is_event_product( $product_id ) ? false : $enabled;
    }

    /**
     * Show active badge in the Deposits dashboard header.
     */
    public function render_dashboard_badge() {
        if ( ! self::is_eventpress_active() ) {
            return;
        }
        ?>
        <span class="apd-pro-badge" style="background:rgba(59,130,246,.16);color:#dbeafe;">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e( 'Event Active', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
        </span>
        <?php
    }

    /**
     * Show EventPress integration status on the general tab.
     */
    public function render_general_status_card() {
        if ( ! self::is_eventpress_active() ) {
            return;
        }

        $enabled = $this->is_integration_enabled();
        ?>
        <div class="apd-card" id="apd-eventpress-settings-card">
            <div class="apd-card-header">
                <h3><?php esc_html_e( 'EventPress Integration', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></h3>
            </div>
            <div class="apd-card-body">
                <div class="apd-field-row">
                    <div class="apd-field-label">
                        <label><?php esc_html_e( 'Integration Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                        <p class="apd-field-desc"><?php esc_html_e( 'Mage EventPress is active. Event bookings can use APD deposit calculations on the WooCommerce checkout page.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
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
                        <label for="apd-eventpress-integration-enabled"><?php esc_html_e( 'Enable EventPress Integration', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></label>
                        <p class="apd-field-desc"><?php esc_html_e( 'Turn the checkout deposit integration for EventPress bookings on or off from this setting.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
                    </div>
                    <div class="apd-field-input">
                        <label class="apd-toggle">
                            <input
                                type="checkbox"
                                name="eventpress_integration_enabled"
                                id="apd-eventpress-integration-enabled"
                                value="yes"
                                <?php checked( $enabled, true ); ?>
                            />
                            <span class="apd-toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <p class="apd-field-desc" style="margin-top:12px;">
                    <?php echo esc_html( $enabled ? __( 'The integration is enabled. EventPress bookings will show APD payment options on checkout and use deposit calculations there.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) : __( 'The integration is disabled. EventPress-linked products will behave like normal full-payment event orders until you turn it back on.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) ); ?>
                </p>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var card = document.getElementById('apd-eventpress-settings-card');
                var form = document.querySelector('.apd-settings-form[data-tab="general"]');

                if (card && form && card.parentNode !== form) {
                    form.insertBefore(card, form.firstChild);
                }
            });
        </script>
        <?php
    }

    /**
     * Enqueue checkout-only styles and interaction for event deposits.
     */
    public function enqueue_checkout_assets() {
        if ( ! $this->is_integration_enabled() || ! is_checkout() || is_order_received_page() || ! $this->cart_has_event_deposit_items() ) {
            return;
        }

        $css = '
.apd-event-checkout-selector{margin:0 0 18px}
.apd-event-checkout-selector .apd-product-deposit-form{margin:0}
';

        if ( wp_style_is( 'apd-pro-public', 'enqueued' ) ) {
            wp_add_inline_style( 'apd-pro-public', $css );
        } elseif ( wp_style_is( 'apd-public', 'enqueued' ) ) {
            wp_add_inline_style( 'apd-public', $css );
        }

        if ( wp_script_is( 'apd-public', 'enqueued' ) ) {
            wp_add_inline_script(
                'apd-public',
                '(function($){"use strict";var isUpdating=false;$(document).on("change","input[name=\"apd_event_checkout_payment_type\"]",function(){if(isUpdating||typeof apd_public==="undefined"){return;}isUpdating=true;$(".apd-event-checkout-selector input").prop("disabled",true);$.post(apd_public.ajax_url,{action:"apd_event_checkout_payment_type",nonce:apd_public.nonce,payment_type:$(this).val()}).always(function(){$(document.body).trigger("update_checkout");});});$(document.body).on("updated_checkout",function(){isUpdating=false;$(".apd-event-checkout-selector input").prop("disabled",false);});})(jQuery);',
                'after'
            );
        }

        $this->enqueue_block_checkout_integration();
    }

    /**
     * Render checkout payment choice for EventPress items.
     */
    public function render_checkout_selector() {
        if ( ! self::is_eventpress_active() || ! $this->is_integration_enabled() ) {
            return;
        }

        $state = $this->get_checkout_state();
        if ( empty( $state['has_event_deposit_items'] ) ) {
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
        <div class="apd-event-checkout-selector">
            <div class="apd-product-deposit-form">
                <div class="apd-deposit-header">
                    <span class="apd-deposit-icon">&#128176;</span>
                    <span class="apd-deposit-title"><?php esc_html_e( 'Payment Options', 'advanced-partial-payment' ); ?></span>
                </div>
                <div class="apd-deposit-options">
                    <label class="apd-deposit-option">
                        <input
                            type="radio"
                            name="apd_event_checkout_payment_type"
                            value="deposit"
                            <?php checked( 'deposit' === $state['selected_payment_type'] ); ?>
                        />
                        <div class="apd-option-content">
                            <span class="apd-option-radio"></span>
                            <div class="apd-option-text">
                                <span class="apd-option-label"><?php echo wp_kses_post( $deposit_text ); ?></span>
                                <span class="apd-option-detail">
                                    <?php echo esc_html( $balance_label ); ?>: <?php echo wp_kses_post( wc_price( $state['balance_due'] ) ); ?>
                                </span>
                            </div>
                        </div>
                    </label>
                    <?php if ( $state['allow_full_payment'] ) : ?>
                    <label class="apd-deposit-option">
                        <input
                            type="radio"
                            name="apd_event_checkout_payment_type"
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

    /**
     * AJAX: update payment type for all event cart items.
     */
    public function ajax_update_checkout_payment_type() {
        check_ajax_referer( 'apd_public_nonce', 'nonce' );

        if ( ! $this->is_integration_enabled() ) {
            wp_send_json_error( __( 'EventPress integration is disabled.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
        }

        if ( ! WC()->cart ) {
            wp_send_json_error( __( 'Cart not available.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
        }

        $payment_type = sanitize_text_field( wp_unslash( $_POST['payment_type'] ?? 'deposit' ) );
        $updated      = false;
        $deposit      = APD_Deposit::instance();

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( ! $this->is_event_cart_item( $cart_item ) || ! $deposit->is_deposit_enabled( $cart_item['product_id'] ) ) {
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

        wp_send_json_error( __( 'No event checkout items found.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
    }

    /**
     * Get current checkout state for event cart items.
     *
     * @return array<string,mixed>
     */
    private function get_checkout_state() {
        $deposit       = APD_Deposit::instance();
        $event_items   = array();
        $allow_full    = true;
        $selected_type = 'deposit';

        if ( ! WC()->cart ) {
            return array(
                'has_event_deposit_items' => false,
            );
        }

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( ! $this->is_event_cart_item( $cart_item ) || ! $deposit->is_deposit_enabled( $cart_item['product_id'] ) ) {
                continue;
            }

            $event_items[] = $cart_item;

            if ( ! $deposit->is_full_payment_allowed( $cart_item['product_id'] ) ) {
                $allow_full = false;
            }

            $selected_type = ( ( $cart_item['apd_pay_deposit'] ?? 'yes' ) === 'yes' ) ? 'deposit' : 'full';
        }

        if ( empty( $event_items ) ) {
            return array(
                'has_event_deposit_items' => false,
            );
        }

        if ( ! $allow_full ) {
            $selected_type = 'deposit';
        }

        $deposit_summary = $this->calculate_event_payment_summary( 'deposit' );
        $full_summary    = $this->calculate_event_payment_summary( 'full' );

        return array(
            'has_event_deposit_items' => true,
            'allow_full_payment'      => $allow_full,
            'selected_payment_type'   => $selected_type,
            'deposit_total'           => floatval( $deposit_summary['deposit_amount'] ?? 0 ),
            'balance_due'             => floatval( $deposit_summary['balance_due'] ?? 0 ),
            'full_total'              => floatval( $full_summary['full_total'] ?? 0 ),
        );
    }

    /**
     * Check whether the cart contains event items with deposits enabled.
     *
     * @return bool
     */
    private function cart_has_event_deposit_items() {
        $state = $this->get_checkout_state();
        return ! empty( $state['has_event_deposit_items'] );
    }

    /**
     * Add Checkout Block support for the EventPress selector.
     */
    private function enqueue_block_checkout_integration() {
        if ( ! $this->is_block_checkout_page() ) {
            return;
        }

        $state = $this->get_checkout_state();
        if ( empty( $state['has_event_deposit_items'] ) ) {
            return;
        }

        $settings      = get_option( 'apd_settings', array() );
        $deposit_label = $settings['deposit_label'] ?? __( 'Deposit', 'advanced-partial-payment' );
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

        $script = 'window.apdEventpressBlocks=' . wp_json_encode( $payload ) . ';';
        $script .= <<<'JS'
(function(){
    "use strict";

    var config = window.apdEventpressBlocks || null;

    if (!config) {
        return;
    }

    var isUpdating = false;

    var createSelector = function () {
        var wrapper = document.createElement("div");
        wrapper.className = "apd-event-checkout-selector apd-event-checkout-selector--block";
        wrapper.innerHTML =
            '<div class="apd-product-deposit-form">' +
                '<div class="apd-deposit-header">' +
                    '<span class="apd-deposit-icon">&#128176;</span>' +
                    '<span class="apd-deposit-title">Payment Options</span>' +
                '</div>' +
                '<div class="apd-deposit-options">' +
                    '<label class="apd-deposit-option">' +
                        '<input type="radio" name="apd_event_checkout_payment_type" value="deposit"' + (config.selectedType === "deposit" ? ' checked="checked"' : "") + ' />' +
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
                            '<input type="radio" name="apd_event_checkout_payment_type" value="full"' + (config.selectedType === "full" ? ' checked="checked"' : "") + ' />' +
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

        var existing = document.querySelector(".apd-event-checkout-selector--block");
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
        var formInputs = document.querySelectorAll('.apd-event-checkout-selector input[name="apd_event_checkout_payment_type"]');
        Array.prototype.forEach.call(formInputs, function (node) {
            node.disabled = false;
        });
        mountSelector();
    };

    document.addEventListener("change", function (event) {
        var input = event.target;

        if (!input || input.name !== "apd_event_checkout_payment_type") {
            return;
        }

        isUpdating = true;
        config.selectedType = input.value;
        mountSelector();

        var formInputs = document.querySelectorAll('.apd-event-checkout-selector input[name="apd_event_checkout_payment_type"]');
        Array.prototype.forEach.call(formInputs, function (node) {
            node.disabled = true;
        });

        var body = new window.FormData();
        body.append("action", "apd_event_checkout_payment_type");
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

    /**
     * Detect EventPress cart items.
     *
     * @param array $cart_item Cart item data.
     * @return bool
     */
    private function is_event_cart_item( $cart_item ) {
        $event_id = 0;

        // Check multiple possible keys EventPress might use in cart item
        if ( ! empty( $cart_item['event_id'] ) ) {
            $event_id = intval( $cart_item['event_id'] );
        } elseif ( ! empty( $cart_item['mep_event_id'] ) ) {
            $event_id = intval( $cart_item['mep_event_id'] );
        } elseif ( ! empty( $cart_item['link_mep_event'] ) ) {
            $event_id = intval( $cart_item['link_mep_event'] );
        } elseif ( ! empty( $cart_item['mep_events'] ) ) {
            $event_id = intval( $cart_item['mep_events'] );
        }

        if ( $event_id > 0 && 'mep_events' === get_post_type( $event_id ) ) {
            return true;
        }

        $product_id = intval( $cart_item['product_id'] ?? 0 );
        if ( $product_id > 0 ) {
            $linked_event_id = get_post_meta( $product_id, 'link_mep_event', true );
            if ( ! empty( $linked_event_id ) && 'mep_events' === get_post_type( $linked_event_id ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect EventPress-linked WooCommerce products.
     *
     * @param int $product_id Product ID.
     * @return bool
     */
    private function is_event_product( $product_id ) {
        $product_id = intval( $product_id );

        if ( $product_id <= 0 ) {
            return false;
        }

        $linked_event_id = get_post_meta( $product_id, 'link_mep_event', true );

        return ! empty( $linked_event_id ) && 'mep_events' === get_post_type( $linked_event_id );
    }

    /**
     * Build a cart summary for EventPress items as if they were all paid by deposit or in full.
     *
     * @param string $payment_type Either deposit or full.
     * @return array<string,float|bool>
     */
    private function calculate_event_payment_summary( $payment_type ) {
        $deposit_engine = APD_Deposit::instance();

        if ( ! WC()->cart ) {
            return array(
                'has_deposit'    => false,
                'full_total'     => 0,
                'deposit_amount' => 0,
                'balance_due'    => 0,
            );
        }

        $cart_subtotal    = 0;
        $deposit_subtotal = 0;
        $has_deposit      = false;

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product_id = intval( $cart_item['product_id'] ?? 0 );
            $line_total = floatval( $cart_item['line_total'] ?? 0 );
            $quantity   = max( 1, intval( $cart_item['quantity'] ?? 1 ) );

            $cart_subtotal += $line_total;

            $is_event_deposit_item = $this->is_event_cart_item( $cart_item ) && $deposit_engine->is_deposit_enabled( $product_id );
            $pay_deposit           = isset( $cart_item['apd_pay_deposit'] ) ? $cart_item['apd_pay_deposit'] : 'yes';

            if ( $is_event_deposit_item ) {
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
        $fee_total      = 0;
        $deposit_only_fee_total = 0;

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

    /**
     * Check whether the current checkout page uses the Checkout Block.
     *
     * @return bool
     */
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
}
