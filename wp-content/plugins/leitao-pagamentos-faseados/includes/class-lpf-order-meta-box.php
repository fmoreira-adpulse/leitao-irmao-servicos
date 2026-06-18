<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LPF_Order_Meta_Box {

    public static function init() {
        add_action( 'add_meta_boxes',                      [ __CLASS__, 'register' ] );
        add_action( 'woocommerce_process_shop_order_meta', [ __CLASS__, 'save' ] );
        add_action( 'woocommerce_process_shop_order_meta', [ __CLASS__, 'ensure_order_status' ], 35 );
        add_action( 'admin_enqueue_scripts',               [ __CLASS__, 'enqueue' ] );
        add_action( 'wp_ajax_lpf_mark_phase_paid',         [ __CLASS__, 'ajax_mark_paid' ] );
        add_action( 'wp_ajax_lpf_save_phases',             [ __CLASS__, 'ajax_save_phases' ] );
        add_action( 'woocommerce_order_status_changed',    [ __CLASS__, 'maybe_inject_on_status_change' ], 10, 4 );
    }

    public static function register() {
        add_meta_box(
            'lpf-payment-phases',
            __( 'Pagamentos Faseados', 'lpf' ),
            [ __CLASS__, 'render' ],
            [ 'shop_order', 'woocommerce_page_wc-orders' ],
            'normal',
            'default'
        );
        add_meta_box(
            'lpf-payment-history',
            __( 'Histórico de Pagamentos', 'lpf' ),
            [ __CLASS__, 'render_history' ],
            [ 'shop_order', 'woocommerce_page_wc-orders' ],
            'normal',
            'low'
        );
    }

    /** @param WP_Post|WC_Order $post_or_order */
    public static function render( $post_or_order ): void {
        $order = ( $post_or_order instanceof WC_Order )
            ? $post_or_order
            : wc_get_order( $post_or_order->ID );

        if ( ! $order ) return;
        if ( ! self::should_show( $order ) ) return;

        $phases      = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        $order_total = (float) $order->get_total();

        // Calcular dedução VAP a partir dos produtos na encomenda (só VAP é devolvido; PEQREP não é deduzido)
        $vap_id        = LPF_Settings::get_vap_product_id();
        $vap_deduction = 0.0;
        foreach ( $order->get_items() as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) continue;
            if ( $vap_id && $item->get_product_id() === $vap_id ) {
                $vap_deduction += (float) $item->get_total();
            }
        }

        wp_nonce_field( 'lpf_save_phases', 'lpf_phases_nonce' );
        ?>
        <input type="hidden" id="lpf-order-total"   value="<?php echo esc_attr( $order_total ); ?>">
        <input type="hidden" id="lpf-vap-deduction" value="<?php echo esc_attr( $vap_deduction ); ?>">
        <div id="lpf-phases-wrapper">
            <div id="lpf-phases-container">
                <?php foreach ( $phases as $phase ) : ?>
                    <?php self::render_phase_row( $phase ); ?>
                <?php endforeach; ?>
            </div>

            <button type="button" id="lpf-add-phase" class="button button-secondary">
                + <?php esc_html_e( 'Adicionar fase', 'lpf' ); ?>
            </button>

            <div id="lpf-phases-summary">
                <?php self::render_summary( $phases, $order_total ); ?>
            </div>

            <div id="lpf-phases-actions">
                <button type="button" id="lpf-save-phases" class="button lpf-btn-save">
                    <?php esc_html_e( 'Guardar', 'lpf' ); ?>
                </button>
                <span id="lpf-save-feedback" class="lpf-save-feedback"></span>
            </div>
        </div>

        <script type="text/html" id="lpf-phase-template">
            <?php self::render_phase_row( [
                'phase_id'    => '__PHASE_ID__',
                'description' => '',
                'type'        => 'nominal',
                'value'       => '',
                'method'      => 'manual',
                'status'      => 'pending',
                'paid_at'     => '',
            ] ); ?>
        </script>
        <?php
    }

    private static function render_phase_row( array $phase ): void {
        $pid           = esc_attr( $phase['phase_id'] );
        $is_paid       = ( $phase['status'] ?? 'pending' ) === 'paid';
        $method        = $phase['method'] ?? 'manual';
        $paid_at       = $phase['paid_at'] ?? '';
        $type          = $phase['type'] ?? 'nominal';
        $is_last       = ! empty( $phase['is_last'] );
        $is_required   = ! empty( $phase['is_required'] );
        $vap_deduction = floatval( $phase['vap_deduction'] ?? 0 );
        ?>
        <div class="lpf-phase-row <?php echo $is_paid ? 'is-paid' : 'is-pending'; ?><?php echo $is_last ? ' is-last' : ''; ?>" data-phase-id="<?php echo $pid; ?>">

            <input type="hidden" name="lpf_phases[<?php echo $pid; ?>][phase_id]"      value="<?php echo $pid; ?>">
            <input type="hidden" name="lpf_phases[<?php echo $pid; ?>][status]"        value="<?php echo esc_attr( $phase['status'] ?? 'pending' ); ?>">
            <input type="hidden" name="lpf_phases[<?php echo $pid; ?>][paid_at]"       value="<?php echo esc_attr( $paid_at ); ?>">
            <input type="hidden" name="lpf_phases[<?php echo $pid; ?>][is_last]"       value="<?php echo $is_last ? '1' : '0'; ?>" class="lpf-is-last-hidden">
            <input type="hidden" name="lpf_phases[<?php echo $pid; ?>][is_required]"   value="<?php echo $is_required ? '1' : '0'; ?>" class="lpf-is-required-hidden">
            <input type="hidden" name="lpf_phases[<?php echo $pid; ?>][vap_deduction]" value="<?php echo esc_attr( $vap_deduction ); ?>" class="lpf-vap-deduction-hidden">

            <div class="lpf-phase-fields">

                <input type="text"
                       name="lpf_phases[<?php echo $pid; ?>][description]"
                       value="<?php echo esc_attr( $phase['description'] ?? '' ); ?>"
                       placeholder="<?php esc_attr_e( 'Descrição', 'lpf' ); ?>"
                       <?php disabled( $is_paid, true ); ?>>

                <select name="lpf_phases[<?php echo $pid; ?>][type]" class="lpf-type-select" <?php disabled( $is_paid, true ); ?>>
                    <option value="nominal"    <?php selected( $type, 'nominal' ); ?>><?php esc_html_e( 'Valor (€)', 'lpf' ); ?></option>
                    <option value="percentage" <?php selected( $type, 'percentage' ); ?>><?php esc_html_e( 'Percentagem (%)', 'lpf' ); ?></option>
                </select>

                <input type="number"
                       name="lpf_phases[<?php echo $pid; ?>][value]"
                       value="<?php echo esc_attr( $phase['value'] ?? '' ); ?>"
                       min="0"
                       step="0.01"
                       placeholder="0"
                       class="lpf-value-input"
                       <?php disabled( $is_paid, true ); ?>>

                <select name="lpf_phases[<?php echo $pid; ?>][method]" class="lpf-method-select" <?php disabled( $is_paid, true ); ?>>
                    <option value="manual" <?php selected( $method, 'manual' ); ?>><?php esc_html_e( 'Manual', 'lpf' ); ?></option>
                    <option value="online" <?php selected( $method, 'online' ); ?>><?php esc_html_e( 'Online', 'lpf' ); ?></option>
                </select>

                <div class="lpf-phase-actions">
                    <?php if ( $is_paid ) : ?>
                        <span class="lpf-status-badge is-paid">
                            <?php esc_html_e( 'Pago', 'lpf' ); ?>
                            <?php if ( $paid_at ) echo ' (' . esc_html( $paid_at ) . ')'; ?>
                        </span>
                        <?php if ( $is_required ) : ?>
                            <span class="lpf-required-badge"><?php esc_html_e( 'Obrigatória', 'lpf' ); ?></span>
                        <?php endif; ?>
                    <?php else : ?>
                        <label class="lpf-required-label">
                            <input type="checkbox"
                                   class="lpf-is-required-checkbox"
                                   value="1"
                                   <?php checked( $is_required, true ); ?>>
                            <?php esc_html_e( 'Obrigatória', 'lpf' ); ?>
                        </label>

                        <label class="lpf-last-label">
                            <input type="checkbox"
                                   class="lpf-is-last-checkbox"
                                   value="1"
                                   <?php checked( $is_last, true ); ?>>
                            <?php esc_html_e( 'Último pagamento', 'lpf' ); ?>
                        </label>

                        <span class="lpf-status-badge is-pending"><?php esc_html_e( 'Pendente', 'lpf' ); ?></span>

                        <button type="button"
                                class="button lpf-mark-paid<?php echo $method === 'online' ? ' lpf-hidden' : ''; ?>"
                                data-phase-id="<?php echo $pid; ?>">
                            <?php esc_html_e( 'Marcar como pago', 'lpf' ); ?>
                        </button>

                        <button type="button"
                                class="button lpf-send-link<?php echo $method === 'manual' ? ' lpf-hidden' : ''; ?>"
                                data-phase-id="<?php echo $pid; ?>">
                            <?php esc_html_e( 'Enviar link', 'lpf' ); ?>
                        </button>

                        <button type="button" class="button-link lpf-remove-phase" title="<?php esc_attr_e( 'Remover fase', 'lpf' ); ?>">✕</button>
                    <?php endif; ?>
                </div>

            </div>

            <?php if ( $is_last && $vap_deduction > 0 ) : ?>
                <div class="lpf-last-info">
                    <?php printf(
                        esc_html__( 'Dedução VAP aplicada: -%s', 'lpf' ),
                        wc_price( $vap_deduction )
                    ); ?>
                </div>
            <?php else : ?>
                <div class="lpf-last-info lpf-hidden"></div>
            <?php endif; ?>

        </div>
        <?php
    }

    /** @param WP_Post|WC_Order $post_or_order */
    public static function render_history( $post_or_order ): void {
        $order = ( $post_or_order instanceof WC_Order )
            ? $post_or_order
            : wc_get_order( $post_or_order->ID );

        if ( ! $order ) return;
        if ( ! self::should_show( $order ) ) return;

        $phases      = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        $order_total = (float) $order->get_total();

        $paid_phases = array_values( array_filter( $phases, fn( $p ) => ( $p['status'] ?? 'pending' ) === 'paid' ) );

        if ( empty( $paid_phases ) ) {
            echo '<p class="description">' . esc_html__( 'Ainda não foram registados pagamentos.', 'lpf' ) . '</p>';
            return;
        }

        // Calcular totais
        $total_paid    = 0.0;
        $vap_deduction = 0.0;

        foreach ( $phases as $phase ) {
            if ( ( $phase['status'] ?? 'pending' ) === 'paid' ) {
                $val = floatval( $phase['value'] ?? 0 );
                $total_paid += ( ( $phase['type'] ?? 'nominal' ) === 'percentage' )
                    ? ( $val / 100 ) * $order_total
                    : $val;
            }
            if ( ! empty( $phase['is_last'] ) ) {
                $vap_deduction = floatval( $phase['vap_deduction'] ?? 0 );
            }
        }

        $outstanding = max( 0.0, $order_total - $total_paid );
        ?>
        <div class="lpf-history-wrapper">

            <div class="lpf-timeline">
                <?php foreach ( $paid_phases as $i => $phase ) :
                    $val    = floatval( $phase['value'] ?? 0 );
                    $amount = ( ( $phase['type'] ?? 'nominal' ) === 'percentage' )
                        ? ( $val / 100 ) * $order_total
                        : $val;
                    $is_last   = ! empty( $phase['is_last'] );
                    $method    = $phase['method'] ?? 'manual';
                    $phase_vap = floatval( $phase['vap_deduction'] ?? 0 );
                ?>
                <div class="lpf-timeline-item<?php echo $i === count( $paid_phases ) - 1 ? ' is-last-item' : ''; ?>">
                    <div class="lpf-timeline-dot<?php echo $is_last ? ' is-final' : ''; ?>"></div>
                    <div class="lpf-timeline-body">
                        <span class="lpf-tl-date"><?php echo esc_html( $phase['paid_at'] ?? '—' ); ?></span>
                        <span class="lpf-tl-desc"><?php echo esc_html( $phase['description'] ?: '—' ); ?></span>
                        <span class="lpf-tl-amount"><?php echo wp_kses_post( wc_price( $amount ) ); ?></span>
                        <span class="lpf-tl-method">
                            <?php echo $method === 'online'
                                ? '<span class="lpf-badge lpf-badge--online">' . esc_html__( 'Online', 'lpf' ) . '</span>'
                                : '<span class="lpf-badge lpf-badge--manual">' . esc_html__( 'Manual', 'lpf' ) . '</span>';
                            ?>
                        </span>
                        <?php if ( $is_last && $phase_vap > 0 ) : ?>
                            <span class="lpf-tl-deduction">
                                <?php printf( esc_html__( 'Inclui dedução VAP: -%s', 'lpf' ), wc_price( $phase_vap ) ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="lpf-history-summary">
                <div class="lpf-hs-row">
                    <span><?php esc_html_e( 'Total da encomenda', 'lpf' ); ?></span>
                    <span><?php echo wp_kses_post( wc_price( $order_total ) ); ?></span>
                </div>
                <div class="lpf-hs-row">
                    <span><?php esc_html_e( 'Total pago', 'lpf' ); ?></span>
                    <span><?php echo wp_kses_post( wc_price( $total_paid ) ); ?></span>
                </div>
                <?php if ( $vap_deduction > 0 ) : ?>
                <div class="lpf-hs-row lpf-hs-deduction">
                    <span><?php esc_html_e( 'Dedução VAP', 'lpf' ); ?></span>
                    <span>-<?php echo wp_kses_post( wc_price( $vap_deduction ) ); ?></span>
                </div>
                <?php endif; ?>
                <div class="lpf-hs-row lpf-hs-outstanding">
                    <span><?php esc_html_e( 'Montante em falta', 'lpf' ); ?></span>
                    <span><?php echo wp_kses_post( wc_price( $outstanding ) ); ?></span>
                </div>
            </div>

        </div>
        <?php
    }

    private static function render_summary( array $phases, float $order_total ): void {
        $total_paid = 0.0;

        foreach ( $phases as $phase ) {
            if ( ( $phase['status'] ?? 'pending' ) !== 'paid' ) continue;

            $val = floatval( $phase['value'] ?? 0 );
            if ( ( $phase['type'] ?? 'nominal' ) === 'percentage' ) {
                $total_paid += ( $val / 100 ) * $order_total;
            } else {
                $total_paid += $val;
            }
        }

        $outstanding = max( 0.0, $order_total - $total_paid );
        ?>
        <div class="lpf-summary">
            <span><?php printf( esc_html__( 'Total pago: %s', 'lpf' ), wc_price( $total_paid ) ); ?></span>
            <span class="lpf-summary-sep">|</span>
            <span><?php printf( esc_html__( 'Em falta: %s', 'lpf' ), wc_price( $outstanding ) ); ?></span>
        </div>
        <?php
    }

    private static function should_show( WC_Order $order ): bool {
        $activation = LPF_Settings::get_status_mostrar_fases();

        // Sem configuração → mostrar sempre
        if ( $activation === '' ) return true;

        $current = 'wc-' . $order->get_status();

        // Mostrar se estiver no estado configurado
        if ( $current === $activation ) return true;

        // Mostrar se já existirem fases definidas (encomenda já passou pelo estado)
        $phases = $order->get_meta( '_lpf_payment_phases', true );
        return ! empty( $phases );
    }

    // Garante que order_status existe no POST antes de WC_Meta_Box_Order_Data::save (p40) correr.
    // Em HPOS, o select2 pode não enviar order_status; nesse caso usa original_order_status.
    public static function ensure_order_status(): void {
        if ( ! isset( $_POST['order_status'] ) && isset( $_POST['original_order_status'] ) ) {
            $_POST['order_status'] = sanitize_key( wp_unslash( $_POST['original_order_status'] ) );
        }
    }

    public static function save( int $order_id ): void {
        if ( ! isset( $_POST['lpf_phases_nonce'] ) || ! wp_verify_nonce( $_POST['lpf_phases_nonce'], 'lpf_save_phases' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $raw      = $_POST['lpf_phases'] ?? [];
        $existing = $order->get_meta( '_lpf_payment_phases', true ) ?: [];

        // Preserve fields managed by AJAX (not in the form) keyed by phase_id
        $existing_map = [];
        foreach ( $existing as $p ) {
            $existing_map[ $p['phase_id'] ] = $p;
        }

        $phases = [];

        foreach ( $raw as $data ) {
            $phase_id = sanitize_text_field( $data['phase_id'] ?? '' );
            $prev     = $existing_map[ $phase_id ] ?? [];
            $is_paid  = ( $data['status'] ?? '' ) === 'paid';

            // Fases pagas têm inputs disabled que não chegam no POST — preservar do meta existente.
            $phases[] = [
                'phase_id'      => $phase_id,
                'description'   => $is_paid ? ( $prev['description'] ?? '' ) : sanitize_text_field( $data['description'] ?? '' ),
                'type'          => $is_paid ? ( $prev['type'] ?? 'nominal' ) : ( in_array( $data['type'] ?? '', [ 'nominal', 'percentage' ], true ) ? $data['type'] : 'nominal' ),
                'value'         => $is_paid ? floatval( $prev['value'] ?? 0 ) : floatval( $data['value'] ?? 0 ),
                'method'        => $is_paid ? ( $prev['method'] ?? 'manual' ) : ( in_array( $data['method'] ?? '', [ 'manual', 'online' ], true ) ? $data['method'] : 'manual' ),
                'status'        => $is_paid ? 'paid' : 'pending',
                'paid_at'       => sanitize_text_field( $data['paid_at'] ?? $prev['paid_at'] ?? '' ),
                'is_last'       => $is_paid ? ( $prev['is_last'] ?? false ) : ( ! empty( $data['is_last'] ) && $data['is_last'] === '1' ),
                'is_required'   => $is_paid ? ( $prev['is_required'] ?? false ) : ( ! empty( $data['is_required'] ) && $data['is_required'] === '1' ),
                'vap_deduction' => floatval( $data['vap_deduction'] ?? $prev['vap_deduction'] ?? 0 ),
                'mini_order_id' => $prev['mini_order_id'] ?? '',
                'link_sent_at'  => $prev['link_sent_at'] ?? '',
            ];
        }

        // Fases pagas podem ter os inputs disabled no browser e não chegam no POST — restaurar da DB.
        $built_ids = array_column( $phases, 'phase_id' );
        foreach ( $existing_map as $eid => $ep ) {
            if ( ! in_array( $eid, $built_ids, true ) && ( $ep['status'] ?? '' ) === 'paid' ) {
                $phases[] = $ep;
            }
        }

        self::inject_vap_peqrep_phases( $order, $phases );

        $order->update_meta_data( '_lpf_payment_phases', $phases );
        $order->save();
    }

    private static function inject_vap_peqrep_phases( WC_Order $order, array &$phases ): void {
        $map = array_filter( [
            'auto-vap'    => LPF_Settings::get_vap_product_id(),
            'auto-peqrep' => LPF_Settings::get_peqrep_product_id(),
        ] );

        if ( empty( $map ) ) return;

        $existing_ids = array_column( $phases, 'phase_id' );

        foreach ( $map as $auto_id => $product_id ) {
            if ( in_array( $auto_id, $existing_ids, true ) ) continue;

            $value = 0.0;
            foreach ( $order->get_items() as $item ) {
                if ( $item instanceof WC_Order_Item_Product && $item->get_product_id() === $product_id ) {
                    $value += (float) $item->get_total();
                }
            }

            if ( $value <= 0 ) continue;

            $product = wc_get_product( $product_id );
            $desc    = $product ? $product->get_name() : strtoupper( str_replace( 'auto-', '', $auto_id ) );

            array_unshift( $phases, [
                'phase_id'      => $auto_id,
                'description'   => $desc,
                'type'          => 'nominal',
                'value'         => $value,
                'method'        => 'manual',
                'status'        => 'pending',
                'paid_at'       => '',
                'is_last'       => false,
                'is_required'   => false,
                'vap_deduction' => 0.0,
                'mini_order_id' => '',
                'link_sent_at'  => '',
            ] );

            $existing_ids[] = $auto_id;
        }
    }

    public static function maybe_inject_on_status_change( int $order_id, string $old_status, string $new_status, WC_Order $order ): void {
        $aguarda_vap = LPF_Settings::get_status_aguarda_vap();
        if ( ! $aguarda_vap ) return;

        $target = str_replace( 'wc-', '', $aguarda_vap );
        if ( $new_status !== $target ) return;

        $phases = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        $count  = count( $phases );

        self::inject_vap_peqrep_phases( $order, $phases );

        if ( count( $phases ) !== $count ) {
            $order->update_meta_data( '_lpf_payment_phases', $phases );
            $order->save();
        }
    }

    public static function ajax_mark_paid() {
        check_ajax_referer( 'lpf_mark_paid', 'nonce' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permissão insuficiente.', 'lpf' ) ] );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        $phase_id = sanitize_text_field( $_POST['phase_id'] ?? '' );

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => __( 'Encomenda não encontrada.', 'lpf' ) ] );
        }

        $phases  = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        $updated = null;

        foreach ( $phases as &$phase ) {
            if ( $phase['phase_id'] === $phase_id ) {
                $phase['status']  = 'paid';
                $phase['paid_at'] = current_time( 'd/m/Y H:i' );
                $updated          = $phase;
                break;
            }
        }
        unset( $phase );

        if ( ! $updated ) {
            wp_send_json_error( [ 'message' => __( 'Fase não encontrada.', 'lpf' ) ] );
        }

        $order->update_meta_data( '_lpf_payment_phases', $phases );
        $order->save();

        $label = $updated['description'] ?: $phase_id;
        $order->add_order_note(
            sprintf( __( 'Pagamento da fase "%s" registado manualmente (%s).', 'lpf' ), $label, $updated['paid_at'] ),
            false,
            true
        );

        $new_status = '';

        // Transição automática de estado quando VAP ou PEQREP são pagos.
        if ( in_array( $phase_id, [ 'auto-vap', 'auto-peqrep' ], true ) ) {
            $target = LPF_Settings::get_status_pronto_orcamento();
            if ( $target ) {
                $target_slug = str_replace( 'wc-', '', $target );
                if ( $order->get_status() !== $target_slug ) {
                    $order->update_status( $target_slug );
                    $new_status = $target_slug;
                }
            }
        }

        wp_send_json_success( [ 'paid_at' => $updated['paid_at'], 'new_status' => $new_status ] );
    }

    public static function ajax_save_phases(): void {
        check_ajax_referer( 'lpf_save_phases', 'nonce' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permissão insuficiente.', 'lpf' ) ] );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        $order    = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => __( 'Encomenda não encontrada.', 'lpf' ) ] );
        }

        $raw = json_decode( wp_unslash( $_POST['phases'] ?? '[]' ), true );
        if ( ! is_array( $raw ) ) {
            wp_send_json_error( [ 'message' => __( 'Dados inválidos.', 'lpf' ) ] );
        }

        $existing     = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        $existing_map = [];
        foreach ( $existing as $p ) {
            $existing_map[ $p['phase_id'] ] = $p;
        }

        $phases = [];
        foreach ( $raw as $data ) {
            $phase_id = sanitize_text_field( $data['phase_id'] ?? '' );
            $prev     = $existing_map[ $phase_id ] ?? [];

            $phases[] = [
                'phase_id'      => $phase_id,
                'description'   => sanitize_text_field( $data['description'] ?? '' ),
                'type'          => in_array( $data['type'] ?? '', [ 'nominal', 'percentage' ], true ) ? $data['type'] : 'nominal',
                'value'         => floatval( $data['value'] ?? 0 ),
                'method'        => in_array( $data['method'] ?? '', [ 'manual', 'online' ], true ) ? $data['method'] : 'manual',
                'status'        => in_array( $data['status'] ?? '', [ 'pending', 'paid' ], true ) ? $data['status'] : 'pending',
                'paid_at'       => sanitize_text_field( $data['paid_at'] ?? '' ),
                'is_last'       => ( $data['is_last'] ?? '0' ) === '1',
                'is_required'   => ( $data['is_required'] ?? '0' ) === '1',
                'vap_deduction' => floatval( $data['vap_deduction'] ?? 0 ),
                'mini_order_id' => $prev['mini_order_id'] ?? '',
                'link_sent_at'  => $prev['link_sent_at'] ?? '',
            ];
        }

        self::inject_vap_peqrep_phases( $order, $phases );

        $order_total   = (float) $order->get_total();
        $paid_total    = 0.0;
        $pending_total = 0.0;
        foreach ( $phases as $phase ) {
            $val    = floatval( $phase['value'] ?? 0 );
            $amount = ( ( $phase['type'] ?? 'nominal' ) === 'percentage' )
                ? ( $val / 100 ) * $order_total
                : $val;
            if ( ( $phase['status'] ?? 'pending' ) === 'paid' ) {
                $paid_total += $amount;
            } else {
                $pending_total += $amount;
            }
        }
        if ( $pending_total > $order_total + 0.001 ) {
            wp_send_json_error( [ 'message' => __( 'O total das fases pendentes não pode ser superior ao valor remanescente da encomenda.', 'lpf' ) ] );
        }

        $order->update_meta_data( '_lpf_payment_phases', $phases );
        $order->save();

        ob_start();
        self::render_summary( $phases, $order_total );
        $summary_html = ob_get_clean();

        wp_send_json_success( [ 'summary_html' => $summary_html ] );
    }

    public static function enqueue( string $hook ): void {
        $is_order = ( $hook === 'post.php' && isset( $GLOBALS['post'] ) && $GLOBALS['post']->post_type === 'shop_order' )
                 || ( $hook === 'woocommerce_page_wc-orders' && ( $_GET['action'] ?? '' ) === 'edit' );

        if ( ! $is_order ) return;

        wp_enqueue_style(  'lpf-order', LPF_PLUGIN_URL . 'assets/css/lpf-order.css', [],            LPF_VERSION );
        wp_enqueue_script( 'lpf-order', LPF_PLUGIN_URL . 'assets/js/lpf-order.js',  [ 'jquery' ],  LPF_VERSION, true );

        wp_localize_script( 'lpf-order', 'lpf_order', [
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'lpf_mark_paid' ),
            'send_link_nonce' => wp_create_nonce( 'lpf_send_link' ),
            'save_nonce'      => wp_create_nonce( 'lpf_save_phases' ),
            'i18n'            => [
                'paid'                     => __( 'Pago', 'lpf' ),
                'confirm_paid'             => __( 'Confirmar o pagamento desta fase?', 'lpf' ),
                'confirm_send_link'        => __( 'Enviar link de pagamento para a fase "%s"?', 'lpf' ),
                'confirm_send_link_generic'=> __( 'Enviar link de pagamento ao cliente?', 'lpf' ),
                'link_sent'               => __( 'Link enviado —', 'lpf' ),
                'send_link'               => __( 'Enviar link', 'lpf' ),
                'error'                   => __( 'Ocorreu um erro. Tenta novamente.', 'lpf' ),
                'vap_deduction'           => __( 'Dedução VAP aplicada', 'lpf' ),
                'suggested_value'         => __( 'Valor sugerido', 'lpf' ),
                'save_recalculate'        => __( 'Guardar', 'lpf' ),
                'saved'                   => __( 'Guardado!', 'lpf' ),
                'total_exceeds'           => __( 'O total das fases pendentes não pode ser superior ao valor remanescente da encomenda.', 'lpf' ),
            ],
        ] );
    }
}
