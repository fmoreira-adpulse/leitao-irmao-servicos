<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin order metabox and deposit column in orders list.
 */
class APD_Admin_Order {

    public function __construct() {
        // Metabox on order edit page
        add_action( 'add_meta_boxes', array( $this, 'add_deposit_metabox' ) );
        // Custom columns in orders list
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_columns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), 10, 2 );
        // HPOS columns
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_columns' ) );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_order_columns_hpos' ), 10, 2 );
        // AJAX: Record manual payment
        add_action( 'wp_ajax_apd_record_payment', array( $this, 'ajax_record_payment' ) );
        // AJAX: Apply deposit / payment plan to a manual order (legacy/fallback)
        add_action( 'wp_ajax_apd_apply_deposit_to_order', array( $this, 'ajax_apply_deposit_to_order' ) );
        // Primary path: during the native order save we ONLY record a lightweight
        // transient flag (no order writes — those mid-save are unsafe on HPOS and slow
        // on loaded servers). The deposit is then applied on the next admin page load.
        add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_deposit_on_order_save' ), 60, 1 );
        add_action( 'admin_init', array( $this, 'maybe_apply_pending_deposit' ) );
        // Workaround: this site's server (nginx/WAF) 404s admin URLs containing
        // "message=1". WooCommerce appends that to its post-save order-edit redirect,
        // so every order save would land on a blocked URL. Strip it from order-edit
        // redirects. (Proper fix is server-side; this keeps order saving usable.)
        add_filter( 'wp_redirect', array( $this, 'strip_blocked_message_param' ), 99, 1 );
        // Order status styling
        add_action( 'admin_head', array( $this, 'order_status_styles' ) );
    }

    /**
     * Strip the "message" query arg from WooCommerce order-edit redirects.
     *
     * This site's server returns a hard nginx 404 for admin URLs containing
     * "message=1" (the param WooCommerce adds after saving an order). Removing it
     * lets the post-save redirect land on a URL the server will serve. Only touches
     * HPOS order-edit redirects that actually carry the param — nothing else.
     *
     * @param string $location Redirect URL.
     * @return string
     */
    public function strip_blocked_message_param( $location ) {
        if ( ! is_string( $location ) || '' === $location ) {
            return $location;
        }
        if ( false === strpos( $location, 'page=wc-orders' ) || false === strpos( $location, 'message=' ) ) {
            return $location;
        }
        return remove_query_arg( 'message', $location );
    }

    /**
     * Add metabox to order edit page.
     */
    public function add_deposit_metabox() {
        $screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'apd-deposit-details',
            __( '💰 Deposit Details', 'advanced-partial-payment' ),
            array( $this, 'render_deposit_metabox' ),
            $screen,
            'side',
            'high'
        );

        add_meta_box(
            'apd-deposit-payment-record',
            __( 'Deposit Payment Record', 'advanced-partial-payment' ),
            array( $this, 'render_payment_record_metabox' ),
            $screen,
            'normal',
            'default'
        );
    }

    /**
     * Render deposit metabox.
     */
    public function render_deposit_metabox( $post_or_order ) {
        $order = $this->get_order_object( $post_or_order );

        if ( ! $order ) {
            echo '<p style="color:#999;">' . esc_html__( 'Order not found.', 'advanced-partial-payment' ) . '</p>';
            return;
        }

        if ( ! APD_Order::is_deposit_order( $order ) ) {
            if ( apd_get_option( 'admin_only_deposit', 'no' ) === 'yes' ) {
                $this->render_apply_deposit_form( $order );
            } else {
                echo '<p style="color:#999;font-size:12px;margin:0;">'
                    . esc_html__( 'No deposit applied.', 'advanced-partial-payment' )
                    . '</p>'
                    . '<p style="font-size:11px;color:#aaa;margin:6px 0 0;">'
                    . sprintf(
                        /* translators: link to settings */
                        wp_kses( __( 'To apply deposits to manual back-office orders, enable <a href="%s">Admin Manual Orders Only</a> in Deposits → General.', 'advanced-partial-payment' ), array( 'a' => array( 'href' => array() ) ) ),
                        esc_url( admin_url( 'admin.php?page=apd-deposits&tab=general' ) )
                    )
                    . '</p>';
            }
            return;
        }

        $details      = APD_Order::get_deposit_details( $order );
        if ( ! $details ) {
            return;
        }

        $plan_name = $order->get_meta( '_apd_payment_plan_name' );
        $schedule  = $order->get_meta( '_apd_installment_schedule' );

        // For the first payment suggestion: use the deposit amount if nothing has been paid yet.
        $is_first_payment    = floatval( $details['amount_paid'] ) <= 0;
        $suggested_payment   = $is_first_payment
            ? floatval( $details['deposit_amount'] )
            : floatval( $details['balance_due'] );
        ?>
        <div class="apd-metabox-content">

            <?php if ( $plan_name ) : ?>
            <div style="margin:0 0 10px;padding:6px 10px;background:#eef2ff;border-left:3px solid #4338ca;border-radius:0 4px 4px 0;font-size:12px;color:#4338ca;font-weight:600;">
                <?php echo esc_html( $plan_name ); ?>
            </div>
            <?php endif; ?>

            <table class="apd-metabox-table" style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="padding:8px 0;color:#666;"><?php esc_html_e( 'Full Order Total', 'advanced-partial-payment' ); ?></td>
                    <td style="padding:8px 0;text-align:right;font-weight:600;"><?php echo wc_price( $details['total_amount'] ); ?></td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#666;"><?php esc_html_e( '1st Installment (Deposit)', 'advanced-partial-payment' ); ?></td>
                    <td style="padding:8px 0;text-align:right;color:#2271b1;font-weight:600;"><?php echo wc_price( $details['deposit_amount'] ); ?></td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#666;"><?php esc_html_e( 'Total Paid', 'advanced-partial-payment' ); ?></td>
                    <td style="padding:8px 0;text-align:right;color:#00a32a;font-weight:600;"><?php echo wc_price( $details['amount_paid'] ); ?></td>
                </tr>
                <tr style="border-top:2px solid #e2e4e7;">
                    <td style="padding:10px 0;font-weight:700;color:#333;"><?php esc_html_e( 'Balance Due', 'advanced-partial-payment' ); ?></td>
                    <td style="padding:10px 0;text-align:right;font-weight:700;color:<?php echo $details['balance_due'] > 0 ? '#d63638' : '#00a32a'; ?>;font-size:16px;">
                        <?php echo wc_price( $details['balance_due'] ); ?>
                    </td>
                </tr>
            </table>

            <?php if ( ! empty( $schedule ) && is_array( $schedule ) ) : ?>
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid #e2e4e7;">
                <p style="margin:0 0 6px;font-weight:600;font-size:11px;text-transform:uppercase;color:#666;">
                    <?php esc_html_e( 'Payment Schedule', 'advanced-partial-payment' ); ?>
                </p>
                <?php foreach ( $schedule as $inst ) : ?>
                <div style="display:flex;justify-content:space-between;gap:8px;font-size:11px;padding:4px 0;border-bottom:1px solid #f0f0f1;">
                    <span style="color:#555;">
                        #<?php echo esc_html( $inst['number'] ?? '' ); ?>
                        <?php if ( ! empty( $inst['due_label'] ) ) : ?>
                            &mdash; <?php echo esc_html( $inst['due_label'] ); ?>
                        <?php endif; ?>
                    </span>
                    <span style="font-weight:600;color:#1d2327;"><?php echo wc_price( floatval( $inst['amount'] ?? 0 ) ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( $details['balance_due'] > 0 ) : ?>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e4e7;">
                <p style="margin:0 0 4px;font-weight:600;font-size:12px;text-transform:uppercase;color:#666;">
                    <?php esc_html_e( 'Record Manual Payment', 'advanced-partial-payment' ); ?>
                </p>
                <?php if ( $is_first_payment ) : ?>
                <p style="margin:0 0 6px;font-size:11px;color:#2271b1;">
                    <?php
                    printf(
                        /* translators: %s deposit amount */
                        esc_html__( 'First installment suggested: %s', 'advanced-partial-payment' ),
                        wp_kses_post( wc_price( $details['deposit_amount'] ) )
                    );
                    ?>
                </p>
                <?php endif; ?>
                <div style="display:flex;gap:6px;">
                    <input type="number" id="apd-manual-amount" step="0.01" min="0.01"
                           max="<?php echo esc_attr( $details['balance_due'] ); ?>"
                           value="<?php echo esc_attr( $suggested_payment ); ?>"
                           style="flex:1;min-width:0;" placeholder="<?php esc_attr_e( 'Amount', 'advanced-partial-payment' ); ?>" />
                    <button type="button" class="button button-primary" id="apd-record-payment"
                            data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
                        <?php esc_html_e( 'Record', 'advanced-partial-payment' ); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $details['history'] ) ) : ?>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e4e7;">
                <p style="margin:0 0 8px;font-weight:600;font-size:12px;text-transform:uppercase;color:#666;">
                    <?php esc_html_e( 'Payment History', 'advanced-partial-payment' ); ?>
                </p>
                <?php foreach ( $details['history'] as $index => $entry ) : ?>
                <div style="padding:8px 0;border-bottom:1px solid #f0f0f1;">
                    <div style="display:flex;justify-content:space-between;gap:8px;font-size:12px;color:#555;">
                        <span style="font-weight:600;color:#1d2327;">
                            <?php echo esc_html( sprintf( __( 'Payment %d', 'advanced-partial-payment' ), $index + 1 ) ); ?>
                        </span>
                        <span style="font-weight:600;"><?php echo wc_price( floatval( $entry['amount'] ?? 0 ) ); ?></span>
                    </div>
                    <div style="margin-top:4px;font-size:11px;color:#50575e;">
                        <?php echo esc_html( $this->get_payment_type_label( $entry ) ); ?>
                        <?php if ( ! empty( $entry['note'] ) ) : ?>
                            · <?php echo esc_html( $entry['note'] ); ?>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px;color:#999;margin-top:3px;"><?php echo esc_html( $entry['date'] ?? '' ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render a full-width payment record panel on the order edit screen.
     */
    public function render_payment_record_metabox( $post_or_order ) {
        $order = $this->get_order_object( $post_or_order );

        if ( ! $order || ! APD_Order::is_deposit_order( $order ) ) {
            echo '<p style="color:#999;">' . esc_html__( 'No deposit payment record available for this order.', 'advanced-partial-payment' ) . '</p>';
            return;
        }

        $details = APD_Order::get_deposit_details( $order );
        if ( ! $details ) {
            echo '<p style="color:#999;">' . esc_html__( 'No payment record found.', 'advanced-partial-payment' ) . '</p>';
            return;
        }

        $history       = is_array( $details['history'] ) ? $details['history'] : array();
        $running_total = 0;
        $plan_name     = $order->get_meta( '_apd_payment_plan_name' );
        $schedule      = $order->get_meta( '_apd_installment_schedule' );
        ?>
        <div class="apd-admin-payment-record">

            <?php if ( $plan_name ) : ?>
            <div style="margin-bottom:14px;padding:10px 14px;background:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:16px;">📋</span>
                <div>
                    <div style="font-size:12px;font-weight:700;color:#4338ca;text-transform:uppercase;letter-spacing:.04em;"><?php esc_html_e( 'Payment Plan', 'advanced-partial-payment' ); ?></div>
                    <div style="font-size:15px;font-weight:600;color:#1d2327;"><?php echo esc_html( $plan_name ); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px;">
                <div style="padding:12px 14px;border:1px solid #e2e4e7;border-radius:8px;background:#fff;">
                    <div style="font-size:11px;font-weight:600;color:#646970;text-transform:uppercase;"><?php esc_html_e( 'Full Order Total', 'advanced-partial-payment' ); ?></div>
                    <div style="margin-top:6px;font-size:18px;font-weight:700;color:#1d2327;"><?php echo wc_price( $details['total_amount'] ); ?></div>
                </div>
                <div style="padding:12px 14px;border:1px solid #e2e4e7;border-radius:8px;background:#fff;">
                    <div style="font-size:11px;font-weight:600;color:#646970;text-transform:uppercase;"><?php esc_html_e( '1st Installment', 'advanced-partial-payment' ); ?></div>
                    <div style="margin-top:6px;font-size:18px;font-weight:700;color:#2271b1;"><?php echo wc_price( $details['deposit_amount'] ); ?></div>
                </div>
                <div style="padding:12px 14px;border:1px solid #e2e4e7;border-radius:8px;background:#fff;">
                    <div style="font-size:11px;font-weight:600;color:#646970;text-transform:uppercase;"><?php esc_html_e( 'Total Paid', 'advanced-partial-payment' ); ?></div>
                    <div style="margin-top:6px;font-size:18px;font-weight:700;color:#00a32a;"><?php echo wc_price( $details['amount_paid'] ); ?></div>
                </div>
                <div style="padding:12px 14px;border:1px solid #e2e4e7;border-radius:8px;background:#fff;">
                    <div style="font-size:11px;font-weight:600;color:#646970;text-transform:uppercase;"><?php esc_html_e( 'Balance Due', 'advanced-partial-payment' ); ?></div>
                    <div style="margin-top:6px;font-size:18px;font-weight:700;color:<?php echo $details['balance_due'] > 0 ? '#d63638' : '#00a32a'; ?>;"><?php echo wc_price( $details['balance_due'] ); ?></div>
                </div>
            </div>

            <table class="widefat striped" style="border:1px solid #e2e4e7;">
                <thead>
                    <tr>
                        <th style="width:80px;"><?php esc_html_e( '#', 'advanced-partial-payment' ); ?></th>
                        <th style="width:160px;"><?php esc_html_e( 'Payment', 'advanced-partial-payment' ); ?></th>
                        <th style="width:180px;"><?php esc_html_e( 'Date', 'advanced-partial-payment' ); ?></th>
                        <th><?php esc_html_e( 'Note', 'advanced-partial-payment' ); ?></th>
                        <th style="width:140px;text-align:right;"><?php esc_html_e( 'Amount', 'advanced-partial-payment' ); ?></th>
                        <th style="width:160px;text-align:right;"><?php esc_html_e( 'Running Paid', 'advanced-partial-payment' ); ?></th>
                        <th style="width:160px;text-align:right;"><?php esc_html_e( 'Balance After', 'advanced-partial-payment' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $history ) ) : ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e( 'No payment entries recorded yet.', 'advanced-partial-payment' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $history as $index => $entry ) : ?>
                            <?php
                            $amount        = floatval( $entry['amount'] ?? 0 );
                            $running_total += $amount;
                            $balance_after = max( 0, floatval( $details['total_amount'] ) - $running_total );
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( sprintf( __( 'Payment %d', 'advanced-partial-payment' ), $index + 1 ) ); ?></strong></td>
                                <td><?php echo esc_html( $this->get_payment_type_label( $entry ) ); ?></td>
                                <td><?php echo esc_html( $entry['date'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $entry['note'] ?? '' ); ?></td>
                                <td style="text-align:right;font-weight:600;"><?php echo wc_price( $amount ); ?></td>
                                <td style="text-align:right;"><?php echo wc_price( $running_total ); ?></td>
                                <td style="text-align:right;color:<?php echo $balance_after > 0 ? '#d63638' : '#00a32a'; ?>;font-weight:600;"><?php echo wc_price( $balance_after ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( ! empty( $schedule ) && is_array( $schedule ) ) : ?>
            <h4 style="margin:20px 0 8px;font-size:13px;"><?php esc_html_e( 'Expected Payment Schedule', 'advanced-partial-payment' ); ?></h4>
            <table class="widefat striped" style="border:1px solid #e2e4e7;">
                <thead>
                    <tr>
                        <th style="width:60px;"><?php esc_html_e( '#', 'advanced-partial-payment' ); ?></th>
                        <th><?php esc_html_e( 'Due', 'advanced-partial-payment' ); ?></th>
                        <th style="width:180px;"><?php esc_html_e( 'Due Date', 'advanced-partial-payment' ); ?></th>
                        <th style="width:160px;text-align:right;"><?php esc_html_e( 'Amount', 'advanced-partial-payment' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $schedule as $inst ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $inst['number'] ?? '' ); ?></strong></td>
                        <td><?php echo esc_html( $inst['due_label'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $inst['due_date'] ?? '' ); ?></td>
                        <td style="text-align:right;font-weight:600;"><?php echo wc_price( floatval( $inst['amount'] ?? 0 ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

        </div>
        <?php
    }

    /**
     * Add deposit status column to orders list.
     */
    public function add_order_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'order_total' === $key ) {
                $new_columns['apd_deposit_status'] = __( 'Deposit', 'advanced-partial-payment' );
            }
        }
        return $new_columns;
    }

    /**
     * Render deposit column (legacy).
     */
    public function render_order_columns( $column, $post_id ) {
        if ( 'apd_deposit_status' === $column ) {
            $order = wc_get_order( $post_id );
            $this->render_deposit_column_content( $order );
        }
    }

    /**
     * Render deposit column (HPOS).
     */
    public function render_order_columns_hpos( $column, $order ) {
        if ( 'apd_deposit_status' === $column ) {
            $this->render_deposit_column_content( $order );
        }
    }

    /**
     * Column content.
     */
    private function render_deposit_column_content( $order ) {
        if ( ! $order || ! APD_Order::is_deposit_order( $order ) ) {
            echo '<span style="color:#999;">—</span>';
            return;
        }

        $details = APD_Order::get_deposit_details( $order );
        if ( ! $details ) {
            return;
        }

        if ( $details['balance_due'] > 0 ) {
            printf(
                '<span style="color:#d63638;font-weight:600;" title="%s">%s %s</span>',
                esc_attr__( 'Balance Due', 'advanced-partial-payment' ),
                esc_html__( 'Due:', 'advanced-partial-payment' ),
                wp_kses_post( wc_price( $details['balance_due'] ) )
            );
        } else {
            echo '<span style="color:#00a32a;font-weight:600;">✓ ' . esc_html__( 'Paid', 'advanced-partial-payment' ) . '</span>';
        }
    }

    /**
     * AJAX: Record manual payment.
     */
    public function ajax_record_payment() {
        check_ajax_referer( 'apd_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'advanced-partial-payment' ) );
            return;
        }

        $order_id = intval( $_POST['order_id'] ?? 0 );
        $amount   = floatval( $_POST['amount'] ?? 0 );

        if ( ! $order_id || $amount <= 0 ) {
            wp_send_json_error( __( 'Invalid data.', 'advanced-partial-payment' ) );
            return;
        }

        $result = APD_Order::record_payment( $order_id, $amount, __( 'Manual payment recorded by admin', 'advanced-partial-payment' ) );

        if ( $result ) {
            wp_send_json_success( __( 'Payment recorded!', 'advanced-partial-payment' ) );
        } else {
            wp_send_json_error( __( 'Failed to record payment.', 'advanced-partial-payment' ) );
        }
    }

    /**
     * Custom styling for partially-paid status in orders list.
     */
    public function order_status_styles() {
        $screen = get_current_screen();
        if ( ! $screen ) return;
        ?>
        <style>
            .order-status.status-partially-paid {
                background: #fff3e0;
                color: #e65100;
            }
            mark.partially-paid {
                background: #fff3e0;
                color: #e65100;
            }
            @media (max-width: 1280px) {
                .apd-admin-payment-record > div:first-child {
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }
            }
            @media (max-width: 782px) {
                .apd-admin-payment-record > div:first-child {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
        <?php
    }

    /**
     * Get a WC order object from either a post or an order instance.
     *
     * @param mixed $post_or_order Post or order.
     * @return WC_Order|false
     */
    private function get_order_object( $post_or_order ) {
        return ( $post_or_order instanceof WP_Post ) ? wc_get_order( $post_or_order->ID ) : $post_or_order;
    }

    /**
     * Render the "Apply Deposit / Payment Plan" form for orders not yet marked as deposit orders.
     * This is the entry point for manually-created back-office orders.
     *
     * @param WC_Order $order Order object.
     */
    private function render_apply_deposit_form( $order ) {
        $plans = array();
        if ( class_exists( 'APD_Payment_Plans' ) ) {
            $plans = APD_Payment_Plans::get_active_plans();
        }

        // Order Total is 0 on new manual orders until "Recalculate" is clicked; use the items subtotal as fallback.
        $order_total     = floatval( $order->get_total() );
        if ( $order_total <= 0 ) {
            $order_total = floatval( $order->get_subtotal() );
        }
        $currency_symbol = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
        ?>
        <div class="apd-apply-deposit-wrap">
            <p style="margin:0 0 10px;font-size:12px;color:#999;">
                <?php esc_html_e( 'No deposit has been applied to this order yet.', 'advanced-partial-payment' ); ?>
            </p>

            <p style="margin:0 0 6px;font-weight:600;font-size:12px;text-transform:uppercase;color:#666;">
                <?php esc_html_e( 'Apply Deposit / Payment Plan', 'advanced-partial-payment' ); ?>
            </p>

            <?php wp_nonce_field( 'apd_apply_deposit', 'apd_apply_nonce' ); ?>

            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="padding:4px 0 2px;color:#555;font-size:12px;">
                        <?php esc_html_e( 'Full Order Total', 'advanced-partial-payment' ); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 8px;">
                        <input type="number" id="apd-apply-total" name="apd_apply_total" step="0.01" min="0.01"
                               value="<?php echo esc_attr( $order_total > 0 ? $order_total : '' ); ?>"
                               style="width:100%;"
                               placeholder="<?php esc_attr_e( 'Enter total amount', 'advanced-partial-payment' ); ?>" />
                    </td>
                </tr>

                <?php if ( ! empty( $plans ) ) : ?>
                <tr>
                    <td style="padding:4px 0 2px;color:#555;font-size:12px;">
                        <?php esc_html_e( 'Payment Plan', 'advanced-partial-payment' ); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 8px;">
                        <select id="apd-apply-plan" name="apd_apply_plan" style="width:100%;">
                            <option value=""><?php esc_html_e( '— Custom Deposit —', 'advanced-partial-payment' ); ?></option>
                            <?php foreach ( $plans as $plan_id => $plan ) : ?>
                                <option value="<?php echo esc_attr( $plan_id ); ?>">
                                    <?php echo esc_html( $plan['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endif; ?>

                <tr id="apd-custom-deposit-row"<?php echo ! empty( $plans ) ? ' style="display:none;"' : ''; ?>>
                    <td style="padding:4px 0 2px;color:#555;font-size:12px;">
                        <?php esc_html_e( 'Deposit Amount', 'advanced-partial-payment' ); ?>
                    </td>
                </tr>
                <tr id="apd-custom-deposit-inputs"<?php echo ! empty( $plans ) ? ' style="display:none;"' : ''; ?>>
                    <td style="padding:0 0 8px;">
                        <div style="display:flex;gap:6px;align-items:center;">
                            <select id="apd-apply-deposit-type" name="apd_apply_deposit_type" style="width:50%;">
                                <option value="percentage"><?php esc_html_e( 'Percentage', 'advanced-partial-payment' ); ?></option>
                                <option value="fixed"><?php esc_html_e( 'Fixed', 'advanced-partial-payment' ); ?></option>
                            </select>
                            <input type="number" id="apd-apply-deposit-value" name="apd_apply_deposit_value" step="0.01" min="0"
                                   value="50" style="flex:1;min-width:0;" />
                            <span id="apd-apply-deposit-suffix" style="color:#666;font-weight:600;">%</span>
                        </div>
                    </td>
                </tr>
            </table>

            <div style="margin:10px 0 0;padding:10px 12px;background:#e6f3ff;border-left:3px solid #2271b1;font-size:12px;color:#1d3a52;line-height:1.6;">
                <strong><?php esc_html_e( 'To apply:', 'advanced-partial-payment' ); ?></strong>
                <?php esc_html_e( 'After choosing the payment plan or deposit above, click the WooCommerce', 'advanced-partial-payment' ); ?>
                <strong><?php echo $order->get_id() && 'auto-draft' !== $order->get_status() ? esc_html__( '"Update"', 'advanced-partial-payment' ) : esc_html__( '"Create"', 'advanced-partial-payment' ); ?></strong>
                <?php esc_html_e( 'button (top-right of this page). The order is saved and the deposit is applied together.', 'advanced-partial-payment' ); ?>
            </div>

            <?php if ( ! $order->get_customer_id() ) : ?>
            <div style="margin:8px 0 0;padding:8px 10px;background:#fce8e8;border-left:3px solid #d63638;font-size:11px;color:#7a1c1c;line-height:1.5;">
                <strong><?php esc_html_e( 'Tip:', 'advanced-partial-payment' ); ?></strong>
                <?php esc_html_e( 'Assign a Customer (in the General panel above) before saving. Guest orders without a customer or billing email will not appear in the customer\'s My Account → Deposits.', 'advanced-partial-payment' ); ?>
            </div>
            <?php endif; ?>
        </div>

        <script>
        (function($){
            var currSymbol = <?php echo wp_json_encode( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ); ?>;

            // Toggle custom deposit fields when plan selector changes.
            $(document).on('change', '#apd-apply-plan', function(){
                var hasPlan = $(this).val() !== '';
                $('#apd-custom-deposit-row, #apd-custom-deposit-inputs').toggle(!hasPlan);
            });

            // Update suffix when deposit type changes.
            $(document).on('change', '#apd-apply-deposit-type', function(){
                $('#apd-apply-deposit-suffix').text($(this).val() === 'percentage' ? '%' : currSymbol);
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * During the native order save, record a lightweight transient describing the
     * requested deposit. We deliberately do NOT touch the order object here:
     *   - Mid-save order writes desync HPOS records (the new order then 404s).
     *   - Heavy work (extra saves, stock reduction) during the create request can
     *     time the origin out (Cloudflare 524) on busy servers.
     * The actual deposit is applied on the next admin page load via
     * maybe_apply_pending_deposit(), a clean standalone request.
     *
     * @param int $order_id Order being saved.
     */
    public function save_deposit_on_order_save( $order_id ) {
        if ( ! isset( $_POST['apd_apply_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apd_apply_nonce'] ) ), 'apd_apply_deposit' ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        if ( apd_get_option( 'admin_only_deposit', 'no' ) !== 'yes' ) {
            return;
        }

        $total = isset( $_POST['apd_apply_total'] ) ? floatval( wp_unslash( $_POST['apd_apply_total'] ) ) : 0;
        if ( $total <= 0 ) {
            return;
        }

        $plan_id   = sanitize_text_field( wp_unslash( $_POST['apd_apply_plan'] ?? '' ) );
        $dep_type  = in_array( sanitize_text_field( wp_unslash( $_POST['apd_apply_deposit_type'] ?? '' ) ), array( 'fixed', 'percentage' ), true )
            ? sanitize_text_field( wp_unslash( $_POST['apd_apply_deposit_type'] ) )
            : 'percentage';
        $dep_value = floatval( wp_unslash( $_POST['apd_apply_deposit_value'] ?? 50 ) );

        // Store only in wp_options (transient) — completely decoupled from the order,
        // so there is zero risk of corrupting it or slowing the save request.
        set_transient(
            'apd_apply_' . (int) $order_id,
            array(
                'total'     => (float) $total,
                'plan_id'   => (string) $plan_id,
                'dep_type'  => (string) $dep_type,
                'dep_value' => (float) $dep_value,
            ),
            10 * MINUTE_IN_SECONDS
        );
    }

    /**
     * Apply a pending deposit (recorded by save_deposit_on_order_save) when the admin
     * lands on the order edit screen. Runs in a normal admin GET request — the same
     * safe context in which editing any existing order works.
     */
    public function maybe_apply_pending_deposit() {
        // Resolve the order id + storage type from the current admin request.
        $order_id = 0;
        $is_hpos  = false;
        if ( isset( $_GET['page'], $_GET['id'] ) && 'wc-orders' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
            $order_id = absint( $_GET['id'] );
            $is_hpos  = true;
        } elseif ( isset( $_GET['post'], $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
            $order_id = absint( $_GET['post'] );
        }
        if ( ! $order_id ) {
            return;
        }

        $params = get_transient( 'apd_apply_' . $order_id );
        if ( ! is_array( $params ) ) {
            return;
        }

        // Clear immediately so it never re-applies, even if something below fails.
        // (This also guarantees the post-redirect request below won't loop.)
        delete_transient( 'apd_apply_' . $order_id );

        $order = wc_get_order( $order_id );
        if ( ! $order || APD_Order::is_deposit_order( $order ) ) {
            return;
        }

        try {
            $result = $this->apply_deposit_to_order(
                $order,
                $params['total'] ?? 0,
                $params['plan_id'] ?? '',
                $params['dep_type'] ?? 'percentage',
                $params['dep_value'] ?? 50
            );
            if ( is_wp_error( $result ) ) {
                $order->add_order_note(
                    sprintf(
                        /* translators: %s: error message */
                        __( 'Deposit could not be applied: %s', 'advanced-partial-payment' ),
                        $result->get_error_message()
                    )
                );
                $order->save();
            }
        } catch ( \Throwable $e ) {
            if ( function_exists( 'wc_get_logger' ) ) {
                wc_get_logger()->error( 'APD deposit apply failed: ' . $e->getMessage(), array( 'source' => 'apd-deposit' ) );
            }
        }

        // Redirect back to the same order edit page BEFORE the heavy edit screen renders.
        // This splits the work across two light requests: this one does only the DB save,
        // the next one only renders. On busy servers that prevents the combined
        // save + full-page-render from exceeding the origin/Cloudflare timeout (524).
        // The transient is already deleted, so the next request won't re-apply or loop.
        $redirect = $is_hpos
            ? admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id )
            : admin_url( 'post.php?post=' . $order_id . '&action=edit' );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Shared deposit application logic. Writes deposit meta, the installment schedule,
     * sets the order total to the first installment, adds a note, saves, and reduces stock.
     *
     * @param WC_Order $order     Order object.
     * @param float    $total     Full order total entered by the admin.
     * @param string   $plan_id   Payment plan ID (empty for a custom deposit).
     * @param string   $dep_type  'percentage' or 'fixed' (only used for custom deposit).
     * @param float    $dep_value Deposit value (only used for custom deposit).
     * @return float|WP_Error Deposit amount on success, WP_Error on failure.
     */
    private function apply_deposit_to_order( $order, $total, $plan_id, $dep_type, $dep_value ) {
        if ( $total <= 0 ) {
            return new WP_Error( 'apd_invalid_total', __( 'Please enter a valid order total.', 'advanced-partial-payment' ) );
        }

        // Calculate the deposit (first-installment) amount.
        $deposit_amount = 0;
        $plan           = false;

        if ( ! empty( $plan_id ) && class_exists( 'APD_Payment_Plans' ) ) {
            $plan = APD_Payment_Plans::get_plan( $plan_id );
            if ( $plan && ! empty( $plan['installments'] ) && is_array( $plan['installments'] ) ) {
                $first       = reset( $plan['installments'] );
                $inst_amount = floatval( $first['amount'] ?? 0 );
                if ( 'fixed' === ( $plan['price_type'] ?? 'percentage' ) ) {
                    $deposit_amount = round( min( $inst_amount, $total ), wc_get_price_decimals() );
                } else {
                    $deposit_amount = round( ( min( $inst_amount, 100.0 ) / 100.0 ) * $total, wc_get_price_decimals() );
                }
            }
        } else {
            if ( 'fixed' === $dep_type ) {
                $deposit_amount = round( min( $dep_value, $total ), wc_get_price_decimals() );
            } else {
                $deposit_amount = round( ( min( $dep_value, 100.0 ) / 100.0 ) * $total, wc_get_price_decimals() );
            }
        }

        if ( $deposit_amount <= 0 ) {
            return new WP_Error( 'apd_zero_deposit', __( 'Calculated deposit is zero. Check the plan installment or deposit value.', 'advanced-partial-payment' ) );
        }

        // Clear any stale values then write fresh meta.
        $meta_keys = array( '_apd_is_deposit', '_apd_deposit_amount', '_apd_total_amount', '_apd_amount_paid', '_apd_balance_due', '_apd_payment_history' );
        foreach ( $meta_keys as $key ) {
            $order->delete_meta_data( $key );
        }

        $order->add_meta_data( '_apd_is_deposit',      'yes',           true );
        $order->add_meta_data( '_apd_source',          'admin_manual',  true );
        $order->add_meta_data( '_apd_deposit_amount',  $deposit_amount, true );
        $order->add_meta_data( '_apd_total_amount',    $total,          true );
        $order->add_meta_data( '_apd_amount_paid',     0,               true );
        $order->add_meta_data( '_apd_balance_due',     $total,          true );
        $order->add_meta_data( '_apd_payment_history', array(),         true );

        // If a payment plan was selected, persist the schedule as well.
        if ( $plan && ! empty( $plan_id ) ) {
            $schedule = APD_Payment_Plans::build_schedule( $plan, $total );
            $order->delete_meta_data( '_apd_payment_plan_id' );
            $order->delete_meta_data( '_apd_payment_plan_name' );
            $order->delete_meta_data( '_apd_installment_schedule' );
            $order->add_meta_data( '_apd_payment_plan_id',      $plan_id,      true );
            $order->add_meta_data( '_apd_payment_plan_name',    $plan['name'], true );
            $order->add_meta_data( '_apd_installment_schedule', $schedule,     true );
        }

        // Set the WC order total to the deposit amount so the customer's "Pay" button
        // at checkout charges only the first installment, not the full order total.
        $order->set_total( $deposit_amount );

        $order->add_order_note(
            sprintf(
                /* translators: 1: full total, 2: first installment */
                __( 'Deposit applied manually by admin. Full total: %1$s — First installment: %2$s. Customer can pay the first installment via the order payment link.', 'advanced-partial-payment' ),
                wc_price( $total ),
                wc_price( $deposit_amount )
            )
        );
        $order->save();

        // NOTE: stock reduction is intentionally NOT performed here. WooCommerce reduces
        // stock automatically when the order moves to processing/completed, and calling
        // wc_reduce_stock_levels() manually fires other plugins' stock hooks which can be
        // slow on a busy server. Admins control stock via the order status.

        return $deposit_amount;
    }

    /**
     * AJAX: Apply a deposit or payment plan to a manually-created order.
     * Kept as a fallback for environments where it works; the native order-save path
     * (save_deposit_on_order_save) is the primary mechanism.
     */
    public function ajax_apply_deposit_to_order() {
        check_ajax_referer( 'apd_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'advanced-partial-payment' ) );
            return;
        }

        if ( apd_get_option( 'admin_only_deposit', 'no' ) !== 'yes' ) {
            wp_send_json_error( __( 'Admin Manual Orders Only is not enabled. Go to Deposits → General to enable it.', 'advanced-partial-payment' ) );
            return;
        }

        $order_id  = intval( $_POST['order_id'] ?? 0 );
        $total     = floatval( $_POST['total_amount'] ?? 0 );
        $plan_id   = sanitize_text_field( wp_unslash( $_POST['plan_id'] ?? '' ) );
        $dep_type  = in_array( sanitize_text_field( wp_unslash( $_POST['deposit_type'] ?? '' ) ), array( 'fixed', 'percentage' ), true )
            ? sanitize_text_field( wp_unslash( $_POST['deposit_type'] ) )
            : 'percentage';
        $dep_value = floatval( $_POST['deposit_value'] ?? 50 );

        $order = $order_id ? wc_get_order( $order_id ) : false;
        if ( ! $order ) {
            wp_send_json_error( __( 'Order not found.', 'advanced-partial-payment' ) );
            return;
        }

        if ( APD_Order::is_deposit_order( $order ) ) {
            wp_send_json_error( __( 'This order already has a deposit applied.', 'advanced-partial-payment' ) );
            return;
        }

        $result = $this->apply_deposit_to_order( $order, $total, $plan_id, $dep_type, $dep_value );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
            return;
        }

        $use_hpos = false;
        try {
            $use_hpos = wc_get_container()->get(
                \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class
            )->custom_orders_table_usage_is_enabled();
        } catch ( \Throwable $e ) {
            $use_hpos = false;
        }
        $edit_url = $use_hpos
            ? admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id )
            : admin_url( 'post.php?post=' . $order_id . '&action=edit' );

        wp_send_json_success( array(
            'message'  => __( 'Deposit applied successfully!', 'advanced-partial-payment' ),
            'deposit'  => wc_price( $result ),
            'total'    => wc_price( $total ),
            'order_id' => $order_id,
            'edit_url' => $edit_url,
        ) );
    }

    /**
     * Format a readable payment type label.
     *
     * @param array $entry Payment history entry.
     * @return string
     */
    private function get_payment_type_label( $entry ) {
        $type = $entry['type'] ?? '';

        switch ( $type ) {
            case 'deposit':
                return __( 'Initial Deposit', 'advanced-partial-payment' );
            case 'balance_payment':
                return __( 'Remaining Balance', 'advanced-partial-payment' );
            default:
                return __( 'Payment', 'advanced-partial-payment' );
        }
    }
}
