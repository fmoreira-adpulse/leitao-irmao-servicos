<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LPF_Status_Guard {

    private static bool $reverting = false;

    public static function init(): void {
        add_action( 'woocommerce_order_status_changed', [ __CLASS__, 'check_transition' ], 10, 4 );
        add_action( 'admin_notices',                    [ __CLASS__, 'show_notice' ] );
    }

    public static function check_transition( int $order_id, string $old_status, string $new_status, WC_Order $order ): void {
        if ( self::$reverting ) return;

        $statuses_requiring_payment = LPF_Settings::get_status_requer_pagamento();
        if ( empty( $statuses_requiring_payment ) ) return;

        // As settings guardam com prefixo "wc-"; o hook devolve sem prefixo
        if ( ! in_array( 'wc-' . $new_status, $statuses_requiring_payment, true ) ) return;

        $phases          = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        $blocking_phases = [];

        foreach ( $phases as $phase ) {
            $is_required = ! empty( $phase['is_required'] );
            $is_last     = ! empty( $phase['is_last'] );
            $is_pending  = ( $phase['status'] ?? 'pending' ) === 'pending';

            // Bloqueia apenas se: obrigatória + não é último pagamento + pendente
            if ( $is_required && ! $is_last && $is_pending ) {
                $blocking_phases[] = $phase['description'] ?: __( '(sem descrição)', 'lpf' );
            }
        }

        if ( empty( $blocking_phases ) ) return;

        // Reverter estado
        self::$reverting = true;
        $order->update_status(
            $old_status,
            sprintf(
                __( 'Transição para "%s" bloqueada — fases obrigatórias por pagar: %s.', 'lpf' ),
                wc_get_order_status_name( $new_status ),
                implode( ', ', $blocking_phases )
            )
        );
        self::$reverting = false;

        // Guardar aviso para mostrar ao admin na próxima carga
        set_transient( 'lpf_blocked_' . get_current_user_id(), [
            'status' => wc_get_order_status_name( $new_status ),
            'phases' => $blocking_phases,
        ], 60 );
    }

    public static function show_notice(): void {
        $key  = 'lpf_blocked_' . get_current_user_id();
        $data = get_transient( $key );
        if ( ! $data ) return;

        delete_transient( $key );
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Pagamentos Faseados —', 'lpf' ); ?></strong>
                <?php
                printf(
                    esc_html__( 'Não é possível avançar para "%s". Fases obrigatórias por pagar: %s.', 'lpf' ),
                    esc_html( $data['status'] ),
                    '<strong>' . esc_html( implode( ', ', $data['phases'] ) ) . '</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }
}
