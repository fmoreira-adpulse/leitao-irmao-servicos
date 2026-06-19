<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LPF_My_Account {

    public static function init() {
        // Mostrar fases na página da encomenda em My Account
        add_action( 'woocommerce_order_details_after_order_table', [ __CLASS__, 'render_phases' ], 10, 1 );

        // Esconder mini-encomendas da lista de encomendas do cliente
        add_filter( 'woocommerce_my_account_my_orders_query', [ __CLASS__, 'exclude_mini_orders' ] );

        // Esconder mini-encomendas da listagem de encomendas no backoffice (HPOS)
        add_filter( 'woocommerce_shop_order_list_table_prepare_items_query_args', [ __CLASS__, 'exclude_mini_orders_from_admin' ] );
    }

    public static function render_phases( WC_Order $order ) {
        $phases = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        if ( empty( $phases ) ) return;

        $order_total = (float) $order->get_total();
        $total_paid  = 0.0;

        foreach ( $phases as $phase ) {
            if ( ( $phase['status'] ?? 'pending' ) !== 'paid' ) continue;
            $val = floatval( $phase['value'] ?? 0 );
            $total_paid += ( ( $phase['type'] ?? 'nominal' ) === 'percentage' )
                ? ( $val / 100 ) * $order_total
                : $val;
        }

        $outstanding = max( 0.0, $order_total - $total_paid );
        ?>
        <section class="lpf-myaccount-phases">
            <h2><?php esc_html_e( 'Pagamentos Faseados', 'lpf' ); ?></h2>

            <table class="woocommerce-table shop_table lpf-phases-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Descrição', 'lpf' ); ?></th>
                        <th><?php esc_html_e( 'Valor', 'lpf' ); ?></th>
                        <th><?php esc_html_e( 'Estado', 'lpf' ); ?></th>
                        <th><?php esc_html_e( 'Data', 'lpf' ); ?></th>
                        <th><?php esc_html_e( 'Fatura', 'lpf' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $phases as $phase ) :
                        $is_paid         = ( $phase['status'] ?? 'pending' ) === 'paid';
                        $val             = floatval( $phase['value'] ?? 0 );
                        $amount          = ( ( $phase['type'] ?? 'nominal' ) === 'percentage' )
                            ? ( $val / 100 ) * $order_total
                            : $val;
                        $label           = ( $phase['type'] === 'percentage' )
                            ? wc_price( $amount ) . ' (' . $val . '%)'
                            : wc_price( $amount );
                        $invoice_file_id = intval( $phase['invoice_file_id'] ?? 0 );
                        $invoice_url     = ( $is_paid && $invoice_file_id ) ? wp_get_attachment_url( $invoice_file_id ) : '';
                    ?>
                        <tr class="<?php echo $is_paid ? 'lpf-paid' : 'lpf-pending'; ?>">
                            <td><?php echo esc_html( $phase['description'] ?: '—' ); ?></td>
                            <td><?php echo wp_kses_post( $label ); ?></td>
                            <td>
                                <?php if ( $is_paid ) : ?>
                                    <span class="lpf-badge lpf-badge--paid"><?php esc_html_e( 'Pago', 'lpf' ); ?></span>
                                <?php else : ?>
                                    <span class="lpf-badge lpf-badge--pending"><?php esc_html_e( 'Pendente', 'lpf' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $is_paid ? esc_html( $phase['paid_at'] ?? '—' ) : '—'; ?></td>
                            <td>
                                <?php if ( $invoice_url ) : ?>
                                    <a href="<?php echo esc_url( $invoice_url ); ?>"
                                       class="button lpf-btn-invoice-download"
                                       target="_blank"
                                       download>
                                        <?php esc_html_e( 'Download', 'lpf' ); ?>
                                    </a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4"><?php esc_html_e( 'Total pago', 'lpf' ); ?></th>
                        <td><?php echo wp_kses_post( wc_price( $total_paid ) ); ?></td>
                    </tr>
                    <tr class="lpf-outstanding">
                        <th colspan="4"><?php esc_html_e( 'Montante em falta', 'lpf' ); ?></th>
                        <td><?php echo wp_kses_post( wc_price( $outstanding ) ); ?></td>
                    </tr>
                </tfoot>
            </table>
        </section>
        <?php
    }

    public static function exclude_mini_orders( array $args ) {
        // Excluir mini-encomendas geradas pelo plugin da lista do cliente
        $args['meta_query'][] = [
            'key'     => '_lpf_mini_order',
            'compare' => 'NOT EXISTS',
        ];
        return $args;
    }

    public static function exclude_mini_orders_from_admin( array $args ): array {
        // Excluir mini-encomendas geradas pelo plugin da listagem admin (HPOS)
        $args['meta_query'][] = [
            'key'     => '_lpf_mini_order',
            'compare' => 'NOT EXISTS',
        ];
        return $args;
    }
}
