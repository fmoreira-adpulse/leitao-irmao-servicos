<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class APD_Event_Form_Builder_Integration {

    public function __construct() {
        add_action( 'admin_head', array( $this, 'render_admin_styles' ) );
        add_action( 'admin_footer', array( $this, 'render_admin_scripts' ) );
        add_action( 'mpwem_attendee_list_action', array( $this, 'render_attendee_row_payload' ), 20, 3 );
        add_filter( 'mep_csv_fixed_cols', array( $this, 'extend_attendee_csv_headers' ) );
        add_filter( 'mep_csv_fixed_cols_data', array( $this, 'extend_attendee_csv_row' ), 10, 2 );
        add_action( 'mep_rpv_export_pdf_btn', array( $this, 'render_report_overview_tools' ), 20, 3 );
        add_action( 'mep_attendee_list_heading', array( $this, 'render_order_report_detail_heading' ), 20 );
        add_action( 'mep_attendee_list_item', array( $this, 'render_order_report_detail_cell' ), 20, 1 );
        add_action( 'mep_pdf_ticket_after_attendee_info', array( $this, 'render_existing_pdf_deposit_info' ), 20, 1 );
        add_filter( 'mep_event_pdf_email_text', array( $this, 'append_existing_pdf_email_deposit_info' ), 20, 2 );
    }

    public static function is_form_builder_active() {
        return class_exists( 'MPWEM_Query' ) && class_exists( 'MPWEM_Global_Function' );
    }

    public function render_admin_styles() {
        if ( ! self::is_form_builder_active() || ! $this->is_supported_admin_page() ) {
            return;
        }
        ?>
        <style>
            .apd-event-attendee-payload { display:none !important; }
            .apd-event-summary { min-width: 220px; line-height: 1.4; }
            .apd-event-summary strong { display:block; margin-bottom:4px; }
            .apd-event-summary small { display:block; color:#6b7280; }
            .apd-event-summary-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:4px 14px; margin-top:6px; font-size:12px; }
            .apd-event-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; margin-bottom:4px; }
            .apd-event-badge-deposit { background:#ede9fe; color:#5b21b6; }
            .apd-event-badge-full { background:#e5f7eb; color:#166534; }
            .apd-event-report-wrap { margin:18px 0 24px; padding:18px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; }
            .apd-event-report-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px; }
            .apd-event-report-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:18px; }
            .apd-event-report-card { padding:14px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; }
            .apd-event-report-card span { display:block; color:#6b7280; font-size:12px; margin-bottom:6px; }
            .apd-event-report-card strong { font-size:20px; line-height:1.2; }
            .apd-event-report-table { width:100%; border-collapse:collapse; }
            .apd-event-report-table th, .apd-event-report-table td { padding:10px 12px; border-top:1px solid #e5e7eb; text-align:left; vertical-align:top; }
            .apd-event-report-table th { font-weight:600; background:#f9fafb; }
            .apd-event-report-empty { color:#6b7280; font-style:italic; }
            .apd-event-order-cell { min-width:200px; }
        </style>
        <?php
    }

    public function render_admin_scripts() {
        if ( ! self::is_form_builder_active() || ! $this->is_attendee_page() ) {
            return;
        }
        ?>
        <script>
            (function() {
                function injectApdColumn() {
                    var tables = document.querySelectorAll('#mpwem_filter_result ._ov_auto table');
                    tables.forEach(function(table) {
                        var headRow = table.querySelector('thead tr');
                        if (!headRow) {
                            return;
                        }
                        if (!headRow.querySelector('.apd-event-summary-head')) {
                            var th = document.createElement('th');
                            th.className = 'apd-event-summary-head';
                            th.textContent = 'Deposit Details';
                            headRow.appendChild(th);
                        }
                        table.querySelectorAll('tbody tr').forEach(function(row) {
                            if (row.querySelector('.apd-event-summary-cell')) {
                                return;
                            }
                            var payload = row.querySelector('.apd-event-attendee-payload');
                            if (!payload) {
                                return;
                            }
                            try {
                                var data = JSON.parse(payload.textContent);
                                var td = document.createElement('td');
                                td.className = 'apd-event-summary-cell';
                                td.innerHTML = data.summary_html || '';
                                row.appendChild(td);
                            } catch (error) {}
                        });
                    });
                }

                injectApdColumn();
                var target = document.getElementById('mpwem_filter_result') || document.body;
                if (!target) {
                    return;
                }
                new MutationObserver(function() {
                    injectApdColumn();
                }).observe(target, { childList: true, subtree: true });
            })();
        </script>
        <?php
    }

    public function render_attendee_row_payload( $order_id, $attendee_id, $event_id ) {
        if ( ! self::is_form_builder_active() ) {
            return;
        }

        $payload = array(
            'summary_html' => $this->render_attendee_summary_markup( $this->get_order_context( $order_id, $attendee_id, $event_id ) ),
        );

        echo '<script type="application/json" class="apd-event-attendee-payload">' . wp_json_encode( $payload ) . '</script>';
    }

    public function extend_attendee_csv_headers( $columns ) {
        $columns   = is_array( $columns ) ? $columns : array();
        $columns[] = __( 'Payment Mode', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $columns[] = __( 'Order Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $columns[] = __( 'Deposit Amount', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $columns[] = __( 'Amount Paid', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $columns[] = __( 'Balance Due', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $columns[] = __( 'Order Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $columns[] = __( 'Payment Method', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $columns[] = __( 'Payment Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );

        return $columns;
    }

    public function extend_attendee_csv_row( $row, $attendee_id ) {
        $row     = is_array( $row ) ? $row : array();
        $context = $this->get_order_context( 0, $attendee_id, 0 );

        $row[] = $context['payment_mode'];
        $row[] = $this->format_money( $context['total_amount'] );
        $row[] = $this->format_money( $context['deposit_amount'] );
        $row[] = $this->format_money( $context['amount_paid'] );
        $row[] = $this->format_money( $context['balance_due'] );
        $row[] = $context['order_status'];
        $row[] = $context['payment_method'];
        $row[] = $context['payment_plan'];

        return $row;
    }

    public function render_order_report_detail_heading() {
        if ( self::is_form_builder_active() ) {
            echo '<th>' . esc_html__( 'Deposit Info', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</th>';
        }
    }

    public function render_order_report_detail_cell( $attendee_id ) {
        if ( ! self::is_form_builder_active() ) {
            return;
        }

        echo '<td class="apd-event-order-cell">' . wp_kses_post( $this->render_attendee_summary_markup( $this->get_order_context( 0, $attendee_id, 0 ) ) ) . '</td>';
    }

    public function render_report_overview_tools( $start_date, $end_date, $payment_gateway ) {
        if ( ! self::is_form_builder_active() ) {
            return;
        }

        $data    = $this->get_report_overview_data( $start_date, $end_date, $payment_gateway );
        ?>
        <div class="apd-event-report-wrap">
            <div class="apd-event-report-cards">
                <div class="apd-event-report-card"><span><?php esc_html_e( 'Orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['summary']['orders'] ) ); ?></strong></div>
                <div class="apd-event-report-card"><span><?php esc_html_e( 'Deposit Orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['summary']['deposit_orders'] ) ); ?></strong></div>
                <div class="apd-event-report-card"><span><?php esc_html_e( 'Collected', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span><strong><?php echo esc_html( $this->format_money( $data['summary']['collected'] ) ); ?></strong></div>
                <div class="apd-event-report-card"><span><?php esc_html_e( 'Balance Due', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></span><strong><?php echo esc_html( $this->format_money( $data['summary']['balance_due'] ) ); ?></strong></div>
            </div>
            <?php if ( ! empty( $data['events'] ) ) : ?>
                <table class="apd-event-report-table">
                    <thead><tr><th><?php esc_html_e( 'Event', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th><th><?php esc_html_e( 'Orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th><th><?php esc_html_e( 'Deposit Orders', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th><th><?php esc_html_e( 'Order Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th><th><?php esc_html_e( 'Collected', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th><th><?php esc_html_e( 'Balance Due', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ( $data['events'] as $event_row ) : ?>
                            <tr>
                                <td><?php echo esc_html( $event_row['event_name'] ); ?></td>
                                <td><?php echo esc_html( number_format_i18n( $event_row['orders'] ) ); ?></td>
                                <td><?php echo esc_html( number_format_i18n( $event_row['deposit_orders'] ) ); ?></td>
                                <td><?php echo esc_html( $this->format_money( $event_row['order_total'] ) ); ?></td>
                                <td><?php echo esc_html( $this->format_money( $event_row['collected'] ) ); ?></td>
                                <td><?php echo esc_html( $this->format_money( $event_row['balance_due'] ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="apd-event-report-empty"><?php esc_html_e( 'No deposit report data found for the selected range.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_report_overview_data( $start_date, $end_date, $payment_gateway ) {
        $data = array(
            'summary' => array( 'orders' => 0, 'deposit_orders' => 0, 'collected' => 0, 'balance_due' => 0 ),
            'events'  => array(),
            'rows'    => array(),
        );

        if ( ! function_exists( 'mep_fb_rpv_attendee_query' ) ) {
            return $data;
        }

        $loop   = mep_fb_rpv_attendee_query( $start_date, $end_date, '', '', $this->sanitize_gateway_filter( $payment_gateway ) );
        $orders = array();

        if ( $loop && $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                $loop->the_post();
                $context   = $this->get_order_context( 0, get_the_ID(), 0 );
                $order_key = $context['order_id'] ? (string) $context['order_id'] : 'attendee-' . get_the_ID();

                if ( ! isset( $orders[ $order_key ] ) ) {
                    $orders[ $order_key ] = array(
                        'order_id'       => $context['order_id'],
                        'customer'       => $context['customer_name'],
                        'tickets'        => 0,
                        'payment_mode'   => $context['payment_mode'],
                        'order_total'    => (float) $context['total_amount'],
                        'deposit_amount' => (float) $context['deposit_amount'],
                        'amount_paid'    => (float) $context['amount_paid'],
                        'balance_due'    => (float) $context['balance_due'],
                        'order_status'   => $context['order_status'],
                        'payment_method' => $context['payment_method'],
                        'payment_plan'   => $context['payment_plan'],
                        'event_date'     => $context['event_date'],
                        'event_names'    => array(),
                        'categories'     => array(),
                    );
                }

                $orders[ $order_key ]['tickets']++;
                if ( ! empty( $context['event_name'] ) ) {
                    $orders[ $order_key ]['event_names'][ $context['event_name'] ] = $context['event_name'];
                }
                if ( ! empty( $context['category'] ) ) {
                    $orders[ $order_key ]['categories'][ $context['category'] ] = $context['category'];
                }
            }
            wp_reset_postdata();
        }

        foreach ( $orders as $order_row ) {
            $row = array(
                'event_name'     => ! empty( $order_row['event_names'] ) ? implode( ', ', $order_row['event_names'] ) : __( 'Unknown Event', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                'category'       => ! empty( $order_row['categories'] ) ? implode( ', ', $order_row['categories'] ) : '',
                'order_id'       => $order_row['order_id'],
                'customer'       => $order_row['customer'],
                'tickets'        => $order_row['tickets'],
                'payment_mode'   => $order_row['payment_mode'],
                'order_total'    => (float) $order_row['order_total'],
                'deposit_amount' => (float) $order_row['deposit_amount'],
                'amount_paid'    => (float) $order_row['amount_paid'],
                'balance_due'    => (float) $order_row['balance_due'],
                'order_status'   => $order_row['order_status'],
                'payment_method' => $order_row['payment_method'],
                'payment_plan'   => $order_row['payment_plan'],
                'event_date'     => $order_row['event_date'],
            );

            $data['rows'][] = $row;
            $data['summary']['orders']++;
            if ( 'Deposit' === $row['payment_mode'] ) {
                $data['summary']['deposit_orders']++;
            }
            $data['summary']['collected']   += $row['amount_paid'];
            $data['summary']['balance_due'] += $row['balance_due'];

            foreach ( $order_row['event_names'] as $event_name ) {
                if ( ! isset( $data['events'][ $event_name ] ) ) {
                    $data['events'][ $event_name ] = array( 'event_name' => $event_name, 'orders' => 0, 'deposit_orders' => 0, 'order_total' => 0, 'collected' => 0, 'balance_due' => 0 );
                }
                $data['events'][ $event_name ]['orders']++;
                if ( 'Deposit' === $row['payment_mode'] ) {
                    $data['events'][ $event_name ]['deposit_orders']++;
                }
                $data['events'][ $event_name ]['order_total'] += $row['order_total'];
                $data['events'][ $event_name ]['collected']   += $row['amount_paid'];
                $data['events'][ $event_name ]['balance_due'] += $row['balance_due'];
            }
        }

        return $data;
    }

    private function get_order_context( $order_id, $attendee_id = 0, $event_id = 0 ) {
        $context = $this->get_empty_context();
        $attendee_id = absint( $attendee_id );
        $event_id    = absint( $event_id );
        $order_id    = absint( $order_id );

        if ( $attendee_id > 0 ) {
            $context['ticket_no']      = sanitize_text_field( (string) get_post_meta( $attendee_id, 'ea_ticket_no', true ) );
            $context['attendee_name']  = sanitize_text_field( (string) get_post_meta( $attendee_id, 'ea_name', true ) );
            $context['attendee_email'] = sanitize_email( (string) get_post_meta( $attendee_id, 'ea_email', true ) );
            $context['attendee_phone'] = sanitize_text_field( (string) get_post_meta( $attendee_id, 'ea_phone', true ) );
            $context['ticket_label']   = sanitize_text_field( (string) get_post_meta( $attendee_id, 'ea_ticket_type', true ) );
            $context['event_date']     = sanitize_text_field( (string) get_post_meta( $attendee_id, 'ea_event_date', true ) );
            $context['customer_name']  = $context['attendee_name'];
            $order_id                  = $order_id ? $order_id : absint( get_post_meta( $attendee_id, 'ea_order_id', true ) );
            $event_id                  = $event_id ? $event_id : absint( get_post_meta( $attendee_id, 'ea_event_id', true ) );
        }

        if ( $event_id > 0 ) {
            $context['event_name'] = get_the_title( $event_id );
            if ( class_exists( 'MPWEM_Global_Function' ) && method_exists( 'MPWEM_Global_Function', 'all_taxonomy_data' ) ) {
                $categories = MPWEM_Global_Function::all_taxonomy_data( $event_id, 'mep_cat' );
                if ( is_array( $categories ) ) {
                    $context['category'] = implode( ', ', array_filter( array_map( 'sanitize_text_field', $categories ) ) );
                }
            }
        }

        $order = $order_id > 0 ? wc_get_order( $order_id ) : false;
        if ( ! $order ) {
            $context['order_id'] = $order_id;
            return $context;
        }

        $context['order_id']       = $order->get_id();
        $context['customer_name']  = $order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : $context['customer_name'];
        $context['order_status']   = wc_get_order_status_name( $order->get_status() );
        $context['payment_method'] = $order->get_payment_method_title() ? $order->get_payment_method_title() : $order->get_payment_method();
        $context['payment_plan']   = sanitize_text_field( (string) $order->get_meta( '_apd_payment_plan_name' ) );

        if ( class_exists( 'APD_Order' ) && APD_Order::is_deposit_order( $order ) ) {
            $details                   = APD_Order::get_deposit_details( $order );
            $context['payment_mode']   = 'Deposit';
            $context['is_deposit']     = true;
            $context['total_amount']   = isset( $details['total_amount'] ) ? (float) $details['total_amount'] : (float) $order->get_total();
            $context['deposit_amount'] = isset( $details['deposit_amount'] ) ? (float) $details['deposit_amount'] : 0;
            $context['amount_paid']    = isset( $details['amount_paid'] ) ? (float) $details['amount_paid'] : $context['deposit_amount'];
            $context['balance_due']    = isset( $details['balance_due'] ) ? (float) $details['balance_due'] : 0;
        } else {
            $context['payment_mode']   = 'Full Payment';
            $context['is_deposit']     = false;
            $context['total_amount']   = (float) $order->get_total();
            $context['deposit_amount'] = 0;
            $context['amount_paid']    = (float) $order->get_total();
            $context['balance_due']    = 0;
        }

        return $context;
    }

    private function get_empty_context() {
        return array(
            'order_id'       => 0,
            'event_name'     => '',
            'category'       => '',
            'ticket_no'      => '',
            'ticket_label'   => '',
            'attendee_name'  => '',
            'attendee_email' => '',
            'attendee_phone' => '',
            'customer_name'  => '',
            'event_date'     => '',
            'payment_mode'   => 'Full Payment',
            'is_deposit'     => false,
            'total_amount'   => 0,
            'deposit_amount' => 0,
            'amount_paid'    => 0,
            'balance_due'    => 0,
            'order_status'   => '',
            'payment_method' => '',
            'payment_plan'   => '',
        );
    }

    public function render_existing_pdf_deposit_info( $ticket_id ) {
        if ( ! self::is_form_builder_active() ) {
            return;
        }

        $context = $this->get_order_context( 0, absint( $ticket_id ), 0 );
        if ( empty( $context['order_id'] ) ) {
            return;
        }

        echo '<li><strong>' . esc_html__( 'Payment Mode:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</strong> ' . esc_html( $context['payment_mode'] ) . '</li>';
        echo '<li><strong>' . esc_html__( 'Order Total:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</strong> ' . esc_html( $this->format_money( $context['total_amount'] ) ) . '</li>';

        if ( $context['is_deposit'] ) {
            echo '<li><strong>' . esc_html__( 'Deposit Paid:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</strong> ' . esc_html( $this->format_money( $context['deposit_amount'] ) ) . '</li>';
        }

        echo '<li><strong>' . esc_html__( 'Amount Paid:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</strong> ' . esc_html( $this->format_money( $context['amount_paid'] ) ) . '</li>';
        echo '<li><strong>' . esc_html__( 'Balance Due:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</strong> ' . esc_html( $this->format_money( $context['balance_due'] ) ) . '</li>';

        if ( ! empty( $context['payment_plan'] ) ) {
            echo '<li><strong>' . esc_html__( 'Payment Plan:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</strong> ' . esc_html( $context['payment_plan'] ) . '</li>';
        }
    }

    public function append_existing_pdf_email_deposit_info( $content, $order_id ) {
        $context = $this->get_order_context( absint( $order_id ), 0, 0 );
        if ( empty( $context['order_id'] ) ) {
            return $content;
        }

        $lines   = array();
        $lines[] = '';
        $lines[] = __( 'Payment Summary:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $lines[] = sprintf( '%s %s', __( 'Payment Mode:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $context['payment_mode'] );
        $lines[] = sprintf( '%s %s', __( 'Order Total:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $this->format_money( $context['total_amount'] ) );

        if ( $context['is_deposit'] ) {
            $lines[] = sprintf( '%s %s', __( 'Deposit Paid:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $this->format_money( $context['deposit_amount'] ) );
        }

        $lines[] = sprintf( '%s %s', __( 'Amount Paid:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $this->format_money( $context['amount_paid'] ) );
        $lines[] = sprintf( '%s %s', __( 'Balance Due:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $this->format_money( $context['balance_due'] ) );

        if ( ! empty( $context['payment_plan'] ) ) {
            $lines[] = sprintf( '%s %s', __( 'Payment Plan:', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ), $context['payment_plan'] );
        }

        return rtrim( (string) $content ) . "\n" . implode( "\n", $lines );
    }

    private function render_attendee_summary_markup( $context ) {
        $badge_class = $context['is_deposit'] ? 'apd-event-badge apd-event-badge-deposit' : 'apd-event-badge apd-event-badge-full';
        $badge_label = $context['is_deposit'] ? __( 'Deposit Order', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) : __( 'Full Payment', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );
        $summary     = '<div class="apd-event-summary">';
        $summary    .= '<span class="' . esc_attr( $badge_class ) . '">' . esc_html( $badge_label ) . '</span>';
        $summary    .= '<strong>' . esc_html( $this->format_money( $context['total_amount'] ) ) . '</strong>';
        $summary    .= '<small>' . esc_html__( 'Order Total', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . '</small>';
        $summary    .= '<div class="apd-event-summary-grid">';
        if ( $context['is_deposit'] ) {
            $summary .= '<span>' . esc_html__( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $this->format_money( $context['deposit_amount'] ) ) . '</span>';
        }
        $summary .= '<span>' . esc_html__( 'Paid', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $this->format_money( $context['amount_paid'] ) ) . '</span>';
        $summary .= '<span>' . esc_html__( 'Balance', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $this->format_money( $context['balance_due'] ) ) . '</span>';
        if ( ! empty( $context['payment_plan'] ) ) {
            $summary .= '<span>' . esc_html__( 'Plan', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $context['payment_plan'] ) . '</span>';
        }
        if ( ! empty( $context['order_status'] ) ) {
            $summary .= '<span>' . esc_html__( 'Status', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) . ': ' . esc_html( $context['order_status'] ) . '</span>';
        }
        $summary .= '</div></div>';

        return $summary;
    }

    private function sanitize_gateway_filter( $payment_gateway ) {
        if ( is_array( $payment_gateway ) ) {
            return array_values( array_filter( array_map( 'sanitize_text_field', $payment_gateway ) ) );
        }
        if ( empty( $payment_gateway ) ) {
            return array();
        }

        return array_values( array_filter( array_map( 'sanitize_text_field', explode( ',', (string) $payment_gateway ) ) ) );
    }

    private function format_money( $amount ) {
        return wp_strip_all_tags( html_entity_decode( wc_price( (float) $amount ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
    }

    private function is_supported_admin_page() {
        return $this->is_attendee_page() || $this->is_report_overview_page() || $this->is_order_report_page();
    }

    private function is_attendee_page() {
        return is_admin() && isset( $_GET['page'] ) && 'attendee_list' === sanitize_key( wp_unslash( $_GET['page'] ) );
    }

    private function is_report_overview_page() {
        return is_admin() && isset( $_GET['page'] ) && 'mep_report_overview' === sanitize_key( wp_unslash( $_GET['page'] ) );
    }

    private function is_order_report_page() {
        return is_admin() && isset( $_GET['page'] ) && 'mep-reports' === sanitize_key( wp_unslash( $_GET['page'] ) );
    }
}
