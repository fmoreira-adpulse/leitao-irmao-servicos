<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LPF_Settings {

    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', [ __CLASS__, 'add_tab' ], 50 );
        add_action( 'woocommerce_settings_lpf',        [ __CLASS__, 'output' ] );
        add_action( 'woocommerce_update_options_lpf',  [ __CLASS__, 'save' ] );
    }

    public static function add_tab( array $tabs ): array {
        $tabs['lpf'] = __( 'Pagamentos Faseados', 'lpf' );
        return $tabs;
    }

    public static function output(): void {
        $statuses = self::get_all_statuses();
        ?>
        <h2><?php esc_html_e( 'Pagamentos Faseados — Configurações', 'lpf' ); ?></h2>

        <!-- Secção: Produtos -->
        <h3><?php esc_html_e( 'Produtos de Abertura de Processo', 'lpf' ); ?></h3>
        <p><?php esc_html_e( 'Seleccione os produtos que representam os depósitos iniciais de abertura de processo.', 'lpf' ); ?></p>
        <table class="form-table">
            <?php
            self::render_product_field(
                'lpf_vap_product_id',
                __( 'Produto VAP', 'lpf' ),
                __( 'Valor de Abertura de Processo (normalmente 30€).', 'lpf' )
            );
            self::render_product_field(
                'lpf_peqrep_product_id',
                __( 'Produto PEQREP', 'lpf' ),
                __( 'Pequena Reparação (normalmente 4€).', 'lpf' )
            );
            ?>
        </table>

        <hr>

        <!-- Secção: Métodos de Pagamento -->
        <h3><?php esc_html_e( 'Métodos de Pagamento Faseado', 'lpf' ); ?></h3>
        <p><?php esc_html_e( 'Seleccione quais os métodos de pagamento disponíveis no checkout das fases. Se nenhum for seleccionado, todos os métodos activos serão apresentados.', 'lpf' ); ?></p>
        <table class="form-table">
            <?php
            self::render_gateway_multiselect(
                'lpf_payment_gateways',
                __( 'Métodos permitidos', 'lpf' ),
                __( 'Apenas os métodos seleccionados aparecerão ao cliente no momento de pagar uma fase online.', 'lpf' )
            );
            ?>
        </table>

        <hr>

        <!-- Secção: Estados -->
        <h3><?php esc_html_e( 'Estados da Encomenda', 'lpf' ); ?></h3>
        <p><?php esc_html_e( 'Configure os estados do fluxo de trabalho. Seleccione a partir dos estados registados no WooCommerce.', 'lpf' ); ?></p>
        <table class="form-table">
            <?php
            self::render_status_select(
                'lpf_status_aguarda_vap',
                __( 'Aguarda VAP/PEQREP', 'lpf' ),
                __( 'Estado onde o cliente aguarda instrução para pagar o VAP ou PEQREP.', 'lpf' ),
                $statuses
            );
            self::render_status_multiselect(
                'lpf_status_orcamentacao',
                __( 'Estados de Orçamentação', 'lpf' ),
                __( 'Estados em que a encomenda aguarda orçamentação interna (ex: Armazém Central, Oficina, etc.). Seleccione um ou mais.', 'lpf' ),
                $statuses
            );
            self::render_status_select(
                'lpf_status_aprovacao_pendente',
                __( 'Aprovação Cliente Pendente', 'lpf' ),
                __( 'Estado onde o cliente tem acesso ao orçamento e comunica a sua decisão.', 'lpf' ),
                $statuses
            );
            self::render_status_select(
                'lpf_status_orcamento_aceite',
                __( 'Orçamento Aceite pelo Cliente', 'lpf' ),
                __( 'Estado onde o orçamento foi aprovado e as fases de pagamento são definidas.', 'lpf' ),
                $statuses
            );
            self::render_status_multiselect(
                'lpf_status_requer_pagamento',
                __( 'Estados que exigem pagamento', 'lpf' ),
                __( 'Enquanto a encomenda estiver nestes estados, não é possível avançar se existirem fases obrigatórias por pagar.', 'lpf' ),
                $statuses
            );
            self::render_status_select(
                'lpf_status_pronto_orcamento',
                __( 'Estado após pagamento VAP/PEQREP', 'lpf' ),
                __( 'Quando o VAP ou PEQREP é marcado como pago, a encomenda transita automaticamente para este estado (ex: Pronto para Orçamento).', 'lpf' ),
                $statuses
            );
            self::render_status_select(
                'lpf_status_mostrar_fases',
                __( 'Mostrar painéis a partir do estado', 'lpf' ),
                __( 'Os painéis "Pagamentos Faseados" e "Histórico" só aparecem quando a encomenda estiver neste estado, ou se já tiver fases definidas. Deixar em branco para mostrar sempre.', 'lpf' ),
                $statuses
            );
            ?>
        </table>
        <?php
    }

    // -------------------------------------------------------------------------
    // Campos: gateways de pagamento (multi-select)
    // -------------------------------------------------------------------------

    private static function render_gateway_multiselect( string $option_key, string $label, string $description ): void {
        $saved    = (array) get_option( $option_key, [] );
        $gateways = WC()->payment_gateways()->payment_gateways();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="forminp">
                <select id="<?php echo esc_attr( $option_key ); ?>"
                        name="<?php echo esc_attr( $option_key ); ?>[]"
                        multiple="multiple"
                        style="min-width: 350px; min-height: 100px;">
                    <?php foreach ( $gateways as $gateway_id => $gateway ) : ?>
                        <option value="<?php echo esc_attr( $gateway_id ); ?>" <?php echo in_array( $gateway_id, $saved, true ) ? 'selected="selected"' : ''; ?>>
                            <?php echo esc_html( $gateway->get_title() ); ?>
                            <?php if ( 'yes' !== $gateway->enabled ) : ?>
                                <?php esc_html_e( '(inactivo)', 'lpf' ); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php echo esc_html( $description ); ?></p>
            </td>
        </tr>
        <?php
    }

    // -------------------------------------------------------------------------
    // Campos: produto
    // -------------------------------------------------------------------------

    private static function render_product_field( string $option_key, string $label, string $description ): void {
        $product_id   = (int) get_option( $option_key, 0 );
        $product_name = '';

        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $product_name = wp_kses_post( $product->get_formatted_name() );
            }
        }
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="forminp">
                <select class="wc-product-search"
                        id="<?php echo esc_attr( $option_key ); ?>"
                        name="<?php echo esc_attr( $option_key ); ?>"
                        data-placeholder="<?php esc_attr_e( 'Pesquisar produto...', 'lpf' ); ?>"
                        data-action="woocommerce_json_search_products"
                        style="min-width: 350px;">
                    <?php if ( $product_id && $product_name ) : ?>
                        <option value="<?php echo esc_attr( $product_id ); ?>" selected="selected">
                            <?php echo $product_name; ?>
                        </option>
                    <?php endif; ?>
                </select>
                <p class="description"><?php echo esc_html( $description ); ?></p>
            </td>
        </tr>
        <?php
    }

    // -------------------------------------------------------------------------
    // Campos: estado (select simples)
    // -------------------------------------------------------------------------

    private static function render_status_select( string $option_key, string $label, string $description, array $statuses ): void {
        $saved = get_option( $option_key, '' );
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="forminp">
                <select id="<?php echo esc_attr( $option_key ); ?>"
                        name="<?php echo esc_attr( $option_key ); ?>"
                        style="min-width: 350px;">
                    <option value=""><?php esc_html_e( '— Seleccionar estado —', 'lpf' ); ?></option>
                    <?php foreach ( $statuses as $slug => $name ) : ?>
                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $saved, $slug ); ?>>
                            <?php echo esc_html( $name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php echo esc_html( $description ); ?></p>
            </td>
        </tr>
        <?php
    }

    // -------------------------------------------------------------------------
    // Campos: estado (multi-select)
    // -------------------------------------------------------------------------

    private static function render_status_multiselect( string $option_key, string $label, string $description, array $statuses ): void {
        $saved = (array) get_option( $option_key, [] );
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="forminp">
                <select id="<?php echo esc_attr( $option_key ); ?>"
                        name="<?php echo esc_attr( $option_key ); ?>[]"
                        multiple="multiple"
                        style="min-width: 350px; min-height: 120px;">
                    <?php foreach ( $statuses as $slug => $name ) : ?>
                        <option value="<?php echo esc_attr( $slug ); ?>" <?php echo in_array( $slug, $saved, true ) ? 'selected="selected"' : ''; ?>>
                            <?php echo esc_html( $name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php echo esc_html( $description ); ?></p>
            </td>
        </tr>
        <?php
    }

    // -------------------------------------------------------------------------
    // Guardar
    // -------------------------------------------------------------------------

    public static function save(): void {
        // Produtos
        foreach ( [ 'lpf_vap_product_id', 'lpf_peqrep_product_id' ] as $field ) {
            if ( ! empty( $_POST[ $field ] ) ) {
                update_option( $field, absint( $_POST[ $field ] ) );
            } else {
                delete_option( $field );
            }
        }

        // Estados — select simples
        $single_statuses = [
            'lpf_status_aguarda_vap',
            'lpf_status_aprovacao_pendente',
            'lpf_status_orcamento_aceite',
            'lpf_status_pronto_orcamento',
            'lpf_status_mostrar_fases',
        ];
        foreach ( $single_statuses as $field ) {
            $value = sanitize_key( $_POST[ $field ] ?? '' );
            if ( $value ) {
                update_option( $field, $value );
            } else {
                delete_option( $field );
            }
        }

        // Estados — multi-select
        $multi_statuses = [
            'lpf_status_orcamentacao',
            'lpf_status_requer_pagamento',
        ];
        foreach ( $multi_statuses as $field ) {
            $values = array_map( 'sanitize_key', (array) ( $_POST[ $field ] ?? [] ) );
            update_option( $field, $values );
        }

        // Métodos de pagamento faseado
        $gw_values = array_map( 'sanitize_key', (array) ( $_POST['lpf_payment_gateways'] ?? [] ) );
        update_option( 'lpf_payment_gateways', $gw_values );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function get_all_statuses(): array {
        $statuses = wc_get_order_statuses();
        // Remover prefixo "wc-" do label mas manter no value para consistência
        return $statuses;
    }

    public static function get_vap_product_id(): int {
        return (int) get_option( 'lpf_vap_product_id', 0 );
    }

    public static function get_peqrep_product_id(): int {
        return (int) get_option( 'lpf_peqrep_product_id', 0 );
    }

    public static function get_status_aguarda_vap(): string {
        return (string) get_option( 'lpf_status_aguarda_vap', '' );
    }

    public static function get_status_orcamentacao(): array {
        return (array) get_option( 'lpf_status_orcamentacao', [] );
    }

    public static function get_status_aprovacao_pendente(): string {
        return (string) get_option( 'lpf_status_aprovacao_pendente', '' );
    }

    public static function get_status_orcamento_aceite(): string {
        return (string) get_option( 'lpf_status_orcamento_aceite', '' );
    }

    public static function get_status_requer_pagamento(): array {
        return (array) get_option( 'lpf_status_requer_pagamento', [] );
    }

    public static function get_status_pronto_orcamento(): string {
        return (string) get_option( 'lpf_status_pronto_orcamento', '' );
    }

    public static function get_status_mostrar_fases(): string {
        return (string) get_option( 'lpf_status_mostrar_fases', '' );
    }

    public static function get_payment_gateways(): array {
        return (array) get_option( 'lpf_payment_gateways', [] );
    }
}
