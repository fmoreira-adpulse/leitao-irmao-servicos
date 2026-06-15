<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LPF_Order_Price {

    /** Guarda preços anteriores entre os dois hooks de save */
    private static array $old_prices = [];

    public static function init(): void {
        // Capturar preços antes de guardar
        add_action( 'woocommerce_before_save_order_items', [ __CLASS__, 'capture_old_prices' ], 10, 1 );

        // Comparar e registar alterações após guardar
        add_action( 'woocommerce_saved_order_items', [ __CLASS__, 'log_price_changes' ], 10, 1 );

        // Impedir que pagamento directo avance o estado automaticamente
        add_filter( 'woocommerce_payment_complete_order_status', [ __CLASS__, 'prevent_auto_status_advance' ], 10, 3 );
    }

    // -------------------------------------------------------------------------
    // Registo de alteração de preço
    // -------------------------------------------------------------------------

    public static function capture_old_prices( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $excluded = array_filter( [
            LPF_Settings::get_vap_product_id(),
            LPF_Settings::get_peqrep_product_id(),
        ] );

        self::$old_prices = [];

        foreach ( $order->get_items() as $item_id => $item ) {
            if ( in_array( $item->get_product_id(), $excluded, true ) ) continue;

            self::$old_prices[ $item_id ] = [
                'name'  => $item->get_name(),
                'total' => (float) $item->get_total(),
            ];
        }
    }

    public static function log_price_changes( int $order_id ): void {
        if ( empty( self::$old_prices ) ) return;

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $excluded = array_filter( [
            LPF_Settings::get_vap_product_id(),
            LPF_Settings::get_peqrep_product_id(),
        ] );

        foreach ( $order->get_items() as $item_id => $item ) {
            if ( in_array( $item->get_product_id(), $excluded, true ) ) continue;
            if ( ! isset( self::$old_prices[ $item_id ] ) ) continue;

            $old = self::$old_prices[ $item_id ]['total'];
            $new = (float) $item->get_total();

            if ( abs( $old - $new ) < 0.001 ) continue;

            $order->add_order_note(
                sprintf(
                    __( 'Preço do produto "%s" actualizado: %s → %s', 'lpf' ),
                    $item->get_name(),
                    wc_price( $old ),
                    wc_price( $new )
                ),
                false,
                true
            );
        }

        self::$old_prices = [];
    }

    // -------------------------------------------------------------------------
    // Impedir avanço automático de estado quando VAP é pago directamente
    // -------------------------------------------------------------------------

    public static function prevent_auto_status_advance( string $new_status, int $order_id, WC_Order $order ): string {
        $aguarda_vap = LPF_Settings::get_status_aguarda_vap();
        if ( ! $aguarda_vap ) return $new_status;

        // WC devolve o status sem prefixo "wc-" em get_status()
        $current = $order->get_status();
        $target  = str_replace( 'wc-', '', $aguarda_vap );

        if ( $current === $target ) {
            // Manter no estado actual — o admin avança manualmente
            return $current;
        }

        return $new_status;
    }
}
