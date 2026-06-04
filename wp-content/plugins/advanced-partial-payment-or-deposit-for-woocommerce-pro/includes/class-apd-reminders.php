<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto payment reminders, reminder history, and overdue auto-cancel actions.
 */
class APD_Reminders {

    const LOG_OPTION   = 'apd_reminder_log';
    const HISTORY_META = '_apd_reminder_history';

    public function __construct() {
        add_action( 'init', array( $this, 'sync_cron_events' ) );
        add_action( 'apd_send_payment_reminders', array( $this, 'send_reminders' ) );
        add_action( 'apd_process_overdue_deposit_orders', array( $this, 'process_overdue_orders' ) );
        add_filter( 'apd_save_settings', array( $this, 'save_settings' ), 10, 3 );
    }

    /**
     * Schedule or clear cron events based on settings.
     */
    public function sync_cron_events() {
        if ( 'yes' === apd_get_option( 'enable_reminders', 'no' ) ) {
            $this->ensure_daily_event( 'apd_send_payment_reminders' );
        } else {
            $this->clear_event( 'apd_send_payment_reminders' );
        }

        if ( intval( apd_get_option( 'auto_cancel_overdue_days', 0 ) ) > 0 ) {
            $this->ensure_daily_event( 'apd_process_overdue_deposit_orders' );
        } else {
            $this->clear_event( 'apd_process_overdue_deposit_orders' );
        }
    }

    /**
     * Ensure a daily cron event exists.
     *
     * @param string $hook Cron hook.
     */
    private function ensure_daily_event( $hook ) {
        if ( ! wp_next_scheduled( $hook ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', $hook );
        }
    }

    /**
     * Clear all scheduled instances of a cron event.
     *
     * @param string $hook Cron hook.
     */
    private function clear_event( $hook ) {
        $timestamp = wp_next_scheduled( $hook );

        while ( $timestamp ) {
            wp_unschedule_event( $timestamp, $hook );
            $timestamp = wp_next_scheduled( $hook );
        }
    }

    /**
     * Send payment reminders.
     */
    public function send_reminders() {
        if ( 'yes' !== apd_get_option( 'enable_reminders', 'no' ) ) {
            return;
        }

        $days_before = max( 0, intval( apd_get_option( 'reminder_days_before', 3 ) ) );
        $days_after  = max( 0, intval( apd_get_option( 'reminder_days_after', 1 ) ) );
        $now         = time();

        $orders = wc_get_orders(
            array(
                'status'     => 'partially-paid',
                'limit'      => 50,
                'meta_query' => array(
                    array(
                        'key'     => '_apd_is_deposit',
                        'value'   => 'yes',
                        'compare' => '=',
                    ),
                ),
            )
        );

        foreach ( $orders as $order ) {
            $details = APD_Order::get_deposit_details( $order );
            if ( ! $details || $details['balance_due'] <= 0 ) {
                continue;
            }

            $schedule = $order->get_meta( '_apd_installment_schedule' );
            if ( is_array( $schedule ) && ! empty( $schedule ) ) {
                foreach ( $schedule as $index => $installment ) {
                    if ( 'pending' !== ( $installment['status'] ?? '' ) || empty( $installment['due_date'] ) ) {
                        continue;
                    }

                    $due_timestamp = strtotime( $installment['due_date'] . ' 23:59:59' );
                    if ( ! $due_timestamp ) {
                        continue;
                    }

                    $diff_days = ( $due_timestamp - $now ) / DAY_IN_SECONDS;

                    if ( $days_before > 0 && $diff_days <= $days_before && $diff_days > 0 ) {
                        $key = $this->get_installment_reminder_key( 'upcoming', $index, $installment );
                        if ( ! $this->has_sent_reminder( $order, $key ) ) {
                            $this->send_installment_reminder( $order, $installment, $index, 'upcoming' );
                        }
                    } elseif ( $days_after > 0 && $diff_days < 0 && abs( $diff_days ) <= $days_after ) {
                        $key = $this->get_installment_reminder_key( 'overdue', $index, $installment );
                        if ( ! $this->has_sent_reminder( $order, $key ) ) {
                            $this->send_installment_reminder( $order, $installment, $index, 'overdue' );
                        }
                    }
                }

                continue;
            }

            $order_date = $order->get_date_created();
            if ( ! $order_date ) {
                continue;
            }

            $age_days          = ( $now - $order_date->getTimestamp() ) / DAY_IN_SECONDS;
            $reminder_interval = max( 1, intval( apd_get_option( 'reminder_interval', 7 ) ) );
            $last_reminded     = $order->get_meta( '_apd_last_reminded' );
            $last_reminded_ts  = $last_reminded ? strtotime( $last_reminded ) : 0;
            $since_reminder    = $last_reminded_ts ? ( $now - $last_reminded_ts ) / DAY_IN_SECONDS : PHP_INT_MAX;

            if ( $age_days >= $days_after && $since_reminder >= $reminder_interval ) {
                $this->send_general_reminder( $order );
                $order->update_meta_data( '_apd_last_reminded', current_time( 'mysql' ) );
                $order->save();
            }
        }
    }

    /**
     * Send upcoming/overdue installment reminder.
     *
     * @param WC_Order $order       Order object.
     * @param array    $installment Installment data.
     * @param int      $index       Installment index.
     * @param string   $type        Reminder type.
     */
    private function send_installment_reminder( $order, $installment, $index, $type ) {
        if ( ! $this->trigger_balance_due_email( $order ) ) {
            return;
        }

        $key   = $this->get_installment_reminder_key( $type, $index, $installment );
        $label = 'overdue' === $type ? __( 'Overdue reminder sent', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) : __( 'Upcoming reminder sent', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' );

        $this->mark_reminder_sent( $order, $key );
        $order->add_order_note(
            sprintf(
                '%1$s for installment %2$d due on %3$s.',
                $label,
                intval( $index ) + 1,
                $installment['due_date']
            )
        );
        $order->save();

        $this->append_log(
            array(
                'sent_at'      => current_time( 'mysql' ),
                'type'         => 'overdue' === $type ? 'overdue_installment' : 'upcoming_installment',
                'status'       => 'sent',
                'order_id'     => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'customer'     => trim( $order->get_formatted_billing_full_name() ),
                'email'        => $order->get_billing_email(),
                'amount'       => floatval( $installment['amount'] ?? 0 ),
                'due_date'     => $installment['due_date'] ?? '',
                'note'         => $label,
            )
        );
    }

    /**
     * Send general balance reminder.
     *
     * @param WC_Order $order Order object.
     */
    private function send_general_reminder( $order ) {
        if ( ! $this->trigger_balance_due_email( $order ) ) {
            return;
        }

        $order->add_order_note( __( 'General balance reminder email sent.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ) );
        $order->save();

        $details = APD_Order::get_deposit_details( $order );

        $this->append_log(
            array(
                'sent_at'      => current_time( 'mysql' ),
                'type'         => 'general_balance',
                'status'       => 'sent',
                'order_id'     => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'customer'     => trim( $order->get_formatted_billing_full_name() ),
                'email'        => $order->get_billing_email(),
                'amount'       => $details ? floatval( $details['balance_due'] ) : 0,
                'due_date'     => '',
                'note'         => __( 'General balance reminder sent.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
            )
        );
    }

    /**
     * Trigger the balance due email.
     *
     * @param WC_Order $order Order object.
     * @return bool
     */
    private function trigger_balance_due_email( $order ) {
        if ( ! $order || ! $order->get_billing_email() ) {
            return false;
        }

        WC()->mailer();
        do_action( 'apd_email_balance_due_notification', $order->get_id() );

        return true;
    }

    /**
     * Auto-cancel deposit orders that remain unpaid past the configured grace period.
     */
    public function process_overdue_orders() {
        $grace_days = max( 0, intval( apd_get_option( 'auto_cancel_overdue_days', 0 ) ) );
        if ( $grace_days <= 0 ) {
            return;
        }

        $now = time();
        $orders = wc_get_orders(
            array(
                'status'     => 'partially-paid',
                'limit'      => 50,
                'meta_query' => array(
                    array(
                        'key'     => '_apd_is_deposit',
                        'value'   => 'yes',
                        'compare' => '=',
                    ),
                ),
            )
        );

        foreach ( $orders as $order ) {
            if ( ! APD_Order::order_has_outstanding_balance( $order ) ) {
                continue;
            }

            $reference_timestamp = $this->get_overdue_reference_timestamp( $order );
            if ( ! $reference_timestamp ) {
                continue;
            }

            if ( ( $now - $reference_timestamp ) < ( $grace_days * DAY_IN_SECONDS ) ) {
                continue;
            }

            $reference_date = wp_date( get_option( 'date_format' ), $reference_timestamp );
            $note           = sprintf(
                __( 'Order auto-cancelled because the remaining balance stayed unpaid for more than %1$d day(s) after %2$s.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ),
                $grace_days,
                $reference_date
            );

            $order->update_status( 'cancelled', $note );

            $details = APD_Order::get_deposit_details( $order );
            $this->append_log(
                array(
                    'sent_at'      => current_time( 'mysql' ),
                    'type'         => 'auto_cancelled',
                    'status'       => 'cancelled',
                    'order_id'     => $order->get_id(),
                    'order_number' => $order->get_order_number(),
                    'customer'     => trim( $order->get_formatted_billing_full_name() ),
                    'email'        => $order->get_billing_email(),
                    'amount'       => $details ? floatval( $details['balance_due'] ) : 0,
                    'due_date'     => gmdate( 'Y-m-d', $reference_timestamp ),
                    'note'         => $note,
                )
            );
        }
    }

    /**
     * Get the overdue reference timestamp for an order.
     *
     * @param WC_Order $order Order object.
     * @return int
     */
    private function get_overdue_reference_timestamp( $order ) {
        $schedule          = $order->get_meta( '_apd_installment_schedule' );
        $pending_due_dates = array();

        if ( is_array( $schedule ) ) {
            foreach ( $schedule as $installment ) {
                if ( 'pending' !== ( $installment['status'] ?? '' ) || empty( $installment['due_date'] ) ) {
                    continue;
                }

                $due_timestamp = strtotime( $installment['due_date'] . ' 23:59:59' );
                if ( $due_timestamp ) {
                    $pending_due_dates[] = $due_timestamp;
                }
            }
        }

        if ( ! empty( $pending_due_dates ) ) {
            sort( $pending_due_dates );
            return intval( $pending_due_dates[0] );
        }

        $created = $order->get_date_created();
        return $created ? $created->getTimestamp() : 0;
    }

    /**
     * Create a stable reminder key for an installment reminder.
     *
     * @param string $type        Reminder type.
     * @param int    $index       Installment index.
     * @param array  $installment Installment data.
     * @return string
     */
    private function get_installment_reminder_key( $type, $index, $installment ) {
        return implode(
            ':',
            array(
                sanitize_key( $type ),
                intval( $index ),
                sanitize_text_field( $installment['due_date'] ?? '' ),
            )
        );
    }

    /**
     * Check whether a reminder key has already been sent for an order.
     *
     * @param WC_Order $order Order object.
     * @param string   $key   Reminder key.
     * @return bool
     */
    private function has_sent_reminder( $order, $key ) {
        $history = $order->get_meta( self::HISTORY_META );
        return is_array( $history ) && isset( $history[ $key ] );
    }

    /**
     * Mark a reminder as sent for an order.
     *
     * @param WC_Order $order Order object.
     * @param string   $key   Reminder key.
     */
    private function mark_reminder_sent( $order, $key ) {
        $history = $order->get_meta( self::HISTORY_META );
        if ( ! is_array( $history ) ) {
            $history = array();
        }

        $history[ $key ] = current_time( 'mysql' );
        $order->update_meta_data( self::HISTORY_META, $history );
    }

    /**
     * Append an entry to the reminder history log.
     *
     * @param array $entry Log data.
     */
    private function append_log( $entry ) {
        $logs = get_option( self::LOG_OPTION, array() );
        if ( ! is_array( $logs ) ) {
            $logs = array();
        }

        array_unshift( $logs, $entry );
        $logs = array_slice( $logs, 0, 200 );

        update_option( self::LOG_OPTION, $logs, false );
    }

    /**
     * Get recent reminder log entries for admin views.
     *
     * @param int $limit Maximum rows.
     * @return array<int,array<string,mixed>>
     */
    public static function get_logs( $limit = 20 ) {
        $logs = get_option( self::LOG_OPTION, array() );
        if ( ! is_array( $logs ) ) {
            return array();
        }

        return array_slice( $logs, 0, max( 1, intval( $limit ) ) );
    }

    /**
     * Save reminder settings.
     *
     * @param array  $settings Settings array.
     * @param string $tab      Current tab.
     * @param array  $data     Request data.
     * @return array
     */
    public function save_settings( $settings, $tab, $data ) {
        if ( 'reminders' === $tab ) {
            $settings['enable_reminders']        = isset( $data['enable_reminders'] ) ? 'yes' : 'no';
            $settings['reminder_days_before']    = intval( $data['reminder_days_before'] ?? 3 );
            $settings['reminder_days_after']     = intval( $data['reminder_days_after'] ?? 1 );
            $settings['reminder_interval']       = intval( $data['reminder_interval'] ?? 7 );
            $settings['auto_cancel_overdue_days'] = intval( $data['auto_cancel_overdue_days'] ?? 0 );
        }

        return $settings;
    }
}
