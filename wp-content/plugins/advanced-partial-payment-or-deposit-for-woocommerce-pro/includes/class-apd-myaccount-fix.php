<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * APD My Account – integration-aware Deposits tab.
 *
 * Responsibilities
 * ────────────────
 * 1. Guarantee the "Deposits" menu item is always present even when a third-party
 *    plugin (e.g. eCab Pro Driver_Frontend_Panel) unconditionally removes "orders"
 *    from the My Account nav — which would also prevent APD free's filter from ever
 *    inserting the tab (it only inserts after "orders").
 *
 * 2. When one or more booking-type integrations are active AND enabled, filter the
 *    orders shown in the Deposits tab so only orders that belong to those booking
 *    types are listed.
 *
 *    Rules
 *    ─────
 *    • eCab active + enabled  → show only eCab orders
 *    • Event active + enabled → show only Event orders
 *    • Tour active + enabled  → show only Tour orders
 *    • Multiple active        → show orders that match ANY of the active types
 *    • None active / all off  → show all deposit orders (original behaviour)
 */
class APD_MyAccount_Fix {

    public function __construct() {
        // Run very late so we execute AFTER any third-party removals (priority 999).
        add_filter( 'woocommerce_account_menu_items', array( $this, 'ensure_deposits_tab' ), 999 );

        // Filter the deposits list rendered by APD free's render_content().
        // Hook into woocommerce_account_deposits_endpoint before APD renders it,
        // by replacing the orders variable via an output-buffer trick — simpler:
        // override the query by filtering wc_get_orders args.
        add_filter( 'apd_myaccount_orders_query_args', array( $this, 'filter_orders_query_args' ), 10, 1 );

        // Fallback: if the free plugin does NOT have the filter above, intercept
        // the woocommerce_account_deposits_endpoint action at a higher priority and
        // replace the render entirely when filtering is needed.
        add_action( 'woocommerce_account_deposits_endpoint', array( $this, 'maybe_render_filtered_deposits' ), 1 );
    }

    // =========================================================================
    // 1. Ensure the Deposits tab is always visible
    // =========================================================================

    /**
     * Make sure the "deposits" item exists in the My Account menu.
     * If it was already inserted by the free plugin nothing changes.
     * If it was lost because "orders" was removed by eCab Pro, we re-insert it.
     *
     * @param array $items
     * @return array
     */
    public function ensure_deposits_tab( $items ) {
        if ( isset( $items['deposits'] ) ) {
            return $items;
        }

        $label     = __( 'Deposits', 'advanced-partial-payment' );
        $new_items = array();
        $inserted  = false;

        foreach ( $items as $key => $value ) {
            if ( 'customer-logout' === $key && ! $inserted ) {
                $new_items['deposits'] = $label;
                $inserted = true;
            }
            $new_items[ $key ] = $value;
        }

        if ( ! $inserted ) {
            $new_items['deposits'] = $label;
        }

        return $new_items;
    }

    // =========================================================================
    // 2. Filter deposits list by active integration type
    // =========================================================================

    /**
     * Return which booking types are currently active AND enabled.
     *
     * @return array  Keys: 'ecab', 'event', 'tour' — present only when active+enabled.
     */
    private function get_active_integrations() {
        $active   = array();
        $settings = get_option( 'apd_settings', array() );

        // eCab
        if (
            class_exists( 'APD_Ecab_Integration' ) &&
            APD_Ecab_Integration::is_ecab_active() &&
            ( $settings['ecab_integration_enabled'] ?? 'yes' ) === 'yes'
        ) {
            $active[] = 'ecab';
        }

        // Event (EventPress / MEP)
        if (
            class_exists( 'APD_Event_Integration' ) &&
            APD_Event_Integration::is_eventpress_active() &&
            ( $settings['eventpress_integration_enabled'] ?? 'yes' ) === 'yes'
        ) {
            $active[] = 'event';
        }

        // Tour (TTBM)
        if (
            class_exists( 'APD_Tour_Integration' ) &&
            APD_Tour_Integration::is_tour_active() &&
            ( $settings['tour_integration_enabled'] ?? 'yes' ) === 'yes'
        ) {
            $active[] = 'tour';
        }

        return $active;
    }

    /**
     * Check whether a WC order belongs to any of the given booking types,
     * OR is a plain WooCommerce product order (no booking meta at all).
     *
     * Detection is done by inspecting order line-item meta — same keys the
     * booking plugins write during checkout:
     *   eCab  → line item meta `_mptbm_id`
     *   Event → line item meta `event_id`   (no leading underscore)
     *   Tour  → line item meta `_ttbm_id`
     *
     * Plain WooCommerce product orders (none of the above meta keys present)
     * are ALWAYS shown regardless of which integrations are active.
     *
     * @param \WC_Order $order
     * @param array     $types  e.g. ['ecab','event']
     * @return bool
     */
    private function order_matches_types( $order, $types ) {
        if ( empty( $types ) ) {
            return true;
        }

        $is_ecab  = false;
        $is_event = false;
        $is_tour  = false;

        foreach ( $order->get_items() as $item_id => $item ) {
            if ( ! $is_ecab ) {
                $mptbm_id = (int) wc_get_order_item_meta( $item_id, '_mptbm_id', true );
                if ( $mptbm_id > 0 ) {
                    $is_ecab = true;
                }
            }

            if ( ! $is_event ) {
                $event_id = (int) wc_get_order_item_meta( $item_id, 'event_id', true );
                if ( $event_id > 0 ) {
                    // Avoid repeated DB calls by checking cached post type.
                    $post_type = wp_cache_get( 'apd_post_type_' . $event_id, 'apd' );
                    if ( false === $post_type ) {
                        $post_type = get_post_type( $event_id );
                        wp_cache_set( 'apd_post_type_' . $event_id, $post_type, 'apd', 60 );
                    }
                    if ( 'mep_events' === $post_type ) {
                        $is_event = true;
                    }
                }
            }

            if ( ! $is_tour ) {
                $ttbm_id = (int) wc_get_order_item_meta( $item_id, '_ttbm_id', true );
                if ( $ttbm_id > 0 ) {
                    $is_tour = true;
                }
            }
        }

        // If the order belongs to one of the active booking types → show it.
        if ( in_array( 'ecab', $types, true ) && $is_ecab ) {
            return true;
        }
        if ( in_array( 'event', $types, true ) && $is_event ) {
            return true;
        }
        if ( in_array( 'tour', $types, true ) && $is_tour ) {
            return true;
        }

        // Plain WooCommerce product order (no booking meta at all) → always show.
        if ( ! $is_ecab && ! $is_event && ! $is_tour ) {
            return true;
        }

        return false;
    }

    /**
     * Filter hook for `apd_myaccount_orders_query_args` (if APD free ever adds it).
     * Currently unused but future-proof.
     *
     * @param array $args
     * @return array
     */
    public function filter_orders_query_args( $args ) {
        return $args;
    }

    /**
     * Remove the APD_MyAccount::render_content() action registered by the free plugin.
     *
     * Because the free plugin stores no global reference to its APD_MyAccount instance
     * we must walk $wp_filter to find and remove the exact callback object.
     */
    private function remove_apd_myaccount_render() {
        global $wp_filter;

        $hook = 'woocommerce_account_deposits_endpoint';
        if ( empty( $wp_filter[ $hook ] ) ) {
            return;
        }

        foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $id => $callback ) {
                $fn = $callback['function'] ?? null;
                if (
                    is_array( $fn ) &&
                    isset( $fn[0], $fn[1] ) &&
                    is_object( $fn[0] ) &&
                    $fn[0] instanceof APD_MyAccount &&
                    'render_content' === $fn[1]
                ) {
                    unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $id ] );
                }
            }
        }
    }

    /**
     * Intercept the deposits endpoint render at priority 1.
     *
     * If one or more integrations are active we take over rendering so we can
     * filter orders by booking type.  We remove APD free's own render callback
     * (priority 10) to avoid duplicate output.
     *
     * When no integrations are active we do nothing, letting APD free render
     * everything as normal.
     */
    public function maybe_render_filtered_deposits() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $active_types = $this->get_active_integrations();

        // No integrations active → fall through to APD free's original render.
        if ( empty( $active_types ) ) {
            return;
        }

        // Remove APD free's own render_content callback so only ours outputs.
        // Because APD free stores no global reference, we walk $wp_filter directly.
        $this->remove_apd_myaccount_render();

        $this->render_filtered_deposits( $active_types );
    }

    /**
     * Render the Deposits tab showing only orders that match the active integrations.
     *
     * @param array $active_types
     */
    private function render_filtered_deposits( $active_types ) {
        $customer_id = get_current_user_id();
        if ( ! $customer_id ) {
            return;
        }

        // Fetch a larger pool (limit 50) so after type-filtering we still have
        // enough rows to show.  Orders must already be tagged as APD deposit orders.
        $candidate_orders = wc_get_orders( array(
            'customer_id' => $customer_id,
            'limit'       => 50,
            'status'      => array( 'partially-paid', 'completed', 'processing', 'on-hold', 'pending' ),
            'meta_query'  => array(
                array(
                    'key'     => '_apd_is_deposit',
                    'value'   => 'yes',
                    'compare' => '=',
                ),
            ),
        ) );

        // Filter to only include orders that belong to the active booking types.
        $orders = array_filter( $candidate_orders, function ( $order ) use ( $active_types ) {
            return $this->order_matches_types( $order, $active_types );
        } );

        $settings      = get_option( 'apd_settings', array() );
        $pay_btn_label = $settings['pay_button_label'] ?? __( 'Pay Remaining Balance', 'advanced-partial-payment' );

        // Re-use APD free's template if available; otherwise inline a minimal one.
        $template = APD_PLUGIN_DIR . 'public/views/myaccount-deposits.php';
        if ( file_exists( $template ) ) {
            include $template;
        } else {
            $this->render_fallback_deposits( $orders, $pay_btn_label );
        }
    }

    /**
     * Minimal fallback renderer in case APD free's template is missing.
     *
     * @param \WC_Order[] $orders
     * @param string      $pay_btn_label
     */
    private function render_fallback_deposits( $orders, $pay_btn_label ) {
        if ( ! class_exists( 'APD_Order' ) ) {
            return;
        }
        ?>
        <div class="apd-myaccount-deposits">
            <h3><?php esc_html_e( 'My Deposits', 'advanced-partial-payment' ); ?></h3>
            <?php if ( ! empty( $orders ) ) : ?>
            <table class="woocommerce-orders-table apd-deposits-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Order', 'advanced-partial-payment' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'advanced-partial-payment' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'advanced-partial-payment' ); ?></th>
                        <th><?php esc_html_e( 'Paid', 'advanced-partial-payment' ); ?></th>
                        <th><?php esc_html_e( 'Balance', 'advanced-partial-payment' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'advanced-partial-payment' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'advanced-partial-payment' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $orders as $order ) :
                        $details = APD_Order::get_deposit_details( $order );
                        if ( ! $details ) continue;
                    ?>
                    <tr>
                        <td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a></td>
                        <td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
                        <td><?php echo wc_price( $details['total_amount'] ); ?></td>
                        <td><?php echo wc_price( $details['amount_paid'] ); ?></td>
                        <td><strong><?php echo wc_price( $details['balance_due'] ); ?></strong></td>
                        <td>
                            <?php if ( $details['balance_due'] > 0 ) : ?>
                                <span class="apd-status-badge apd-status-pending"><?php esc_html_e( 'Partially Paid', 'advanced-partial-payment' ); ?></span>
                            <?php else : ?>
                                <span class="apd-status-badge apd-status-complete"><?php esc_html_e( 'Fully Paid', 'advanced-partial-payment' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $details['balance_due'] > 0 && class_exists( 'APD_Pay_Balance' ) ) : ?>
                                <a href="<?php echo esc_url( APD_Pay_Balance::get_pay_balance_url( $order->get_id() ) ); ?>"
                                   class="woocommerce-button button apd-pay-balance-btn">
                                    <?php echo esc_html( $pay_btn_label ); ?>
                                </a>
                            <?php elseif ( $details['balance_due'] <= 0 ) : ?>
                                <span class="apd-paid-check">&#10003;</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <div class="woocommerce-message woocommerce-message--info">
                <p><?php esc_html_e( 'No deposit orders found.', 'advanced-partial-payment' ); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
