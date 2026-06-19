<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LPF_Payment_Link {

    public static function init() {
        add_action( 'wp_ajax_lpf_send_payment_link',    [ __CLASS__, 'ajax_send_link' ] );
        add_action( 'wp_ajax_lpf_upload_phase_invoice', [ __CLASS__, 'ajax_upload_invoice' ] );
        add_action( 'wp_ajax_lpf_send_phase_invoice',   [ __CLASS__, 'ajax_send_invoice' ] );
        add_action( 'woocommerce_payment_complete',      [ __CLASS__, 'on_payment_complete' ] );
        add_action( 'woocommerce_order_status_changed',  [ __CLASS__, 'on_status_changed' ], 10, 4 );
    }

    // -------------------------------------------------------------------------
    // AJAX — admin clica "Enviar link"
    // -------------------------------------------------------------------------

    public static function ajax_send_link() {
        check_ajax_referer( 'lpf_send_link', 'nonce' );

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
        $updated = false;

        foreach ( $phases as &$phase ) {
            if ( $phase['phase_id'] !== $phase_id ) continue;

            $mini_order = self::get_or_create_mini_order( $order, $phase );
            if ( is_wp_error( $mini_order ) ) {
                wp_send_json_error( [ 'message' => $mini_order->get_error_message() ] );
            }

            $payment_url = $mini_order->get_checkout_payment_url();
            $sent_at     = current_time( 'd/m/Y H:i' );

            $phase['mini_order_id'] = $mini_order->get_id();
            $phase['link_sent_at']  = $sent_at;

            self::send_email( $order, $phase, $payment_url );

            $order->add_order_note(
                sprintf( __( 'Link de pagamento da fase "%s" enviado para %s (%s).', 'lpf' ),
                    $phase['description'] ?: $phase_id,
                    $order->get_billing_email(),
                    $sent_at
                ),
                false,
                true
            );

            $updated = true;
            break;
        }
        unset( $phase );

        if ( ! $updated ) {
            wp_send_json_error( [ 'message' => __( 'Fase não encontrada.', 'lpf' ) ] );
        }

        $order->update_meta_data( '_lpf_payment_phases', $phases );
        $order->save();

        wp_send_json_success( [ 'sent_at' => current_time( 'd/m/Y H:i' ) ] );
    }

    // -------------------------------------------------------------------------
    // Mini-encomenda
    // -------------------------------------------------------------------------

    private static function get_or_create_mini_order( WC_Order $parent, array &$phase ) {
        // Reutiliza mini-encomenda existente se ainda estiver por pagar
        if ( ! empty( $phase['mini_order_id'] ) ) {
            $existing = wc_get_order( (int) $phase['mini_order_id'] );
            if ( $existing && ! $existing->is_paid() && $existing->get_status() === 'pending' ) {
                return $existing;
            }
        }

        $amount = self::calculate_amount( $phase, $parent );
        if ( $amount <= 0 ) {
            return new WP_Error( 'invalid_amount', __( 'O valor da fase tem de ser superior a zero.', 'lpf' ) );
        }

        $mini = wc_create_order();
        if ( is_wp_error( $mini ) ) return $mini;

        // Copiar morada de facturação
        $mini->set_address( $parent->get_address( 'billing' ), 'billing' );

        // Item de taxa (sem IVA) com o valor da fase
        $fee = new WC_Order_Item_Fee();
        $fee->set_name( $phase['description'] ?: __( 'Pagamento de fase', 'lpf' ) );
        $fee->set_amount( $amount );
        $fee->set_total( $amount );
        $fee->set_tax_status( 'none' );
        $mini->add_item( $fee );
        $mini->calculate_totals();
        $mini->set_status( 'pending' );

        // Meta de ligação à encomenda principal
        $mini->update_meta_data( '_lpf_parent_order_id', $parent->get_id() );
        $mini->update_meta_data( '_lpf_phase_id',        $phase['phase_id'] );
        $mini->update_meta_data( '_lpf_mini_order',      true );
        $mini->save();

        return $mini;
    }

    private static function calculate_amount( array $phase, WC_Order $parent ) {
        $value = floatval( $phase['value'] ?? 0 );
        if ( ( $phase['type'] ?? 'nominal' ) === 'percentage' ) {
            return ( $value / 100 ) * floatval( $parent->get_total() );
        }
        return $value;
    }

    // -------------------------------------------------------------------------
    // Email
    // -------------------------------------------------------------------------

    private static function send_email( WC_Order $order, array $phase, string $url ) {
        $to      = $order->get_billing_email();
        $name    = $order->get_billing_first_name();
        $subject = sprintf( __( 'Pagamento da encomenda #%s', 'lpf' ), $order->get_order_number() );
        $amount  = wc_price( self::calculate_amount( $phase, $order ) );
        $label   = $phase['description'] ?: __( 'Fase de pagamento', 'lpf' );
        $shop    = get_bloginfo( 'name' );

        $body = self::email_template( compact( 'name', 'label', 'amount', 'url', 'shop', 'order' ) );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $shop . ' <' . get_option( 'admin_email' ) . '>',
        ];

        wp_mail( $to, $subject, $body, $headers );
    }

    private static function email_template( array $d ) {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;">
  <table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;border-radius:4px;overflow:hidden;">
    <tr>
      <td style="background:#2c3e50;padding:24px 32px;">
        <p style="color:#fff;margin:0;font-size:20px;font-weight:bold;"><?php echo esc_html( $d['shop'] ); ?></p>
      </td>
    </tr>
    <tr>
      <td style="padding:32px;">
        <p style="margin:0 0 16px;"><?php printf( esc_html__( 'Olá %s,', 'lpf' ), esc_html( $d['name'] ) ); ?></p>
        <p style="margin:0 0 8px;"><?php printf( esc_html__( 'Tem um pagamento pendente referente à encomenda #%s.', 'lpf' ), esc_html( $d['order']->get_order_number() ) ); ?></p>
        <table width="100%" style="margin:16px 0;border:1px solid #eee;border-radius:4px;">
          <tr>
            <td style="padding:12px 16px;color:#666;"><?php esc_html_e( 'Descrição', 'lpf' ); ?></td>
            <td style="padding:12px 16px;font-weight:bold;"><?php echo esc_html( $d['label'] ); ?></td>
          </tr>
          <tr style="background:#f9f9f9;">
            <td style="padding:12px 16px;color:#666;"><?php esc_html_e( 'Valor', 'lpf' ); ?></td>
            <td style="padding:12px 16px;font-weight:bold;"><?php echo wp_kses_post( $d['amount'] ); ?></td>
          </tr>
        </table>
        <p style="text-align:center;margin:24px 0;">
          <a href="<?php echo esc_url( $d['url'] ); ?>"
             style="background:#2c3e50;color:#fff;padding:14px 32px;border-radius:4px;text-decoration:none;font-weight:bold;display:inline-block;">
            <?php esc_html_e( 'Pagar agora', 'lpf' ); ?>
          </a>
        </p>
        <p style="color:#999;font-size:12px;margin:0;"><?php esc_html_e( 'Se não solicitou este pagamento, ignore este email.', 'lpf' ); ?></p>
      </td>
    </tr>
  </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // AJAX — upload de fatura por fase
    // -------------------------------------------------------------------------

    public static function ajax_upload_invoice(): void {
        check_ajax_referer( 'lpf_upload_invoice', 'nonce' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permissão insuficiente.', 'lpf' ) ] );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        $phase_id = sanitize_text_field( $_POST['phase_id'] ?? '' );

        if ( ! $order_id || ! $phase_id ) {
            wp_send_json_error( [ 'message' => __( 'Dados inválidos.', 'lpf' ) ] );
        }

        if ( empty( $_FILES['invoice_file'] ) || $_FILES['invoice_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( [ 'message' => __( 'Erro no upload do ficheiro.', 'lpf' ) ] );
        }

        $allowed_types = [ 'application/pdf', 'image/jpeg', 'image/png' ];
        $filetype      = wp_check_filetype( $_FILES['invoice_file']['name'] );
        if ( ! in_array( $filetype['type'], $allowed_types, true ) ) {
            wp_send_json_error( [ 'message' => __( 'Tipo de ficheiro não permitido. Use PDF, JPG ou PNG.', 'lpf' ) ] );
        }

        if ( $_FILES['invoice_file']['size'] > 1 * 1024 * 1024 ) {
            wp_send_json_error( [ 'message' => __( 'O ficheiro não pode exceder 1 MB.', 'lpf' ) ] );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => __( 'Encomenda não encontrada.', 'lpf' ) ] );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload( 'invoice_file', 0 );

        if ( is_wp_error( $attachment_id ) ) {
            wp_send_json_error( [ 'message' => $attachment_id->get_error_message() ] );
        }

        $phases = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        $found  = false;

        foreach ( $phases as &$phase ) {
            if ( $phase['phase_id'] !== $phase_id ) continue;

            // Apagar ficheiro anterior se existir
            if ( ! empty( $phase['invoice_file_id'] ) ) {
                wp_delete_attachment( (int) $phase['invoice_file_id'], true );
            }

            $phase['invoice_file_id'] = $attachment_id;
            $phase['invoice_sent_at'] = '';
            $found = true;
            break;
        }
        unset( $phase );

        if ( ! $found ) {
            wp_delete_attachment( $attachment_id, true );
            wp_send_json_error( [ 'message' => __( 'Fase não encontrada.', 'lpf' ) ] );
        }

        $order->update_meta_data( '_lpf_payment_phases', $phases );
        $order->save();

        wp_send_json_success( [
            'attachment_id' => $attachment_id,
            'file_url'      => wp_get_attachment_url( $attachment_id ),
            'filename'      => basename( (string) get_attached_file( $attachment_id ) ),
        ] );
    }

    // -------------------------------------------------------------------------
    // AJAX — enviar fatura por email
    // -------------------------------------------------------------------------

    public static function ajax_send_invoice(): void {
        check_ajax_referer( 'lpf_send_invoice', 'nonce' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permissão insuficiente.', 'lpf' ) ] );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        $phase_id = sanitize_text_field( $_POST['phase_id'] ?? '' );

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => __( 'Encomenda não encontrada.', 'lpf' ) ] );
        }

        $phases   = $order->get_meta( '_lpf_payment_phases', true ) ?: [];
        $phase_ref = null;

        foreach ( $phases as &$phase ) {
            if ( $phase['phase_id'] !== $phase_id ) continue;
            $phase_ref = &$phase;
            break;
        }

        if ( ! $phase_ref ) {
            wp_send_json_error( [ 'message' => __( 'Fase não encontrada.', 'lpf' ) ] );
        }

        if ( empty( $phase_ref['invoice_file_id'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Nenhuma fatura carregada para esta fase.', 'lpf' ) ] );
        }

        $file_path = get_attached_file( (int) $phase_ref['invoice_file_id'] );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            wp_send_json_error( [ 'message' => __( 'Ficheiro da fatura não encontrado no servidor.', 'lpf' ) ] );
        }

        $sent_at = current_time( 'd/m/Y H:i' );

        self::send_invoice_email( $order, $phase_ref, $file_path );

        $phase_ref['invoice_sent_at'] = $sent_at;
        $order->update_meta_data( '_lpf_payment_phases', $phases );
        $order->save();

        $order->add_order_note(
            sprintf(
                __( 'Fatura da fase "%s" enviada para %s (%s).', 'lpf' ),
                $phase_ref['description'] ?: $phase_id,
                $order->get_billing_email(),
                $sent_at
            ),
            false,
            true
        );

        unset( $phase_ref );

        wp_send_json_success( [ 'sent_at' => $sent_at ] );
    }

    private static function send_invoice_email( WC_Order $order, array $phase, string $file_path ): void {
        $to      = $order->get_billing_email();
        $name    = $order->get_billing_first_name();
        $subject = sprintf( __( 'Fatura — Encomenda #%s', 'lpf' ), $order->get_order_number() );
        $label   = $phase['description'] ?: __( 'Fase de pagamento', 'lpf' );
        $shop    = get_bloginfo( 'name' );

        $body = self::invoice_email_template( compact( 'name', 'label', 'shop', 'order' ) );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $shop . ' <' . get_option( 'admin_email' ) . '>',
        ];

        wp_mail( $to, $subject, $body, $headers, [ $file_path ] );
    }

    private static function invoice_email_template( array $d ): string {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;">
  <table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;border-radius:4px;overflow:hidden;">
    <tr>
      <td style="background:#2c3e50;padding:24px 32px;">
        <p style="color:#fff;margin:0;font-size:20px;font-weight:bold;"><?php echo esc_html( $d['shop'] ); ?></p>
      </td>
    </tr>
    <tr>
      <td style="padding:32px;">
        <p style="margin:0 0 16px;"><?php printf( esc_html__( 'Olá %s,', 'lpf' ), esc_html( $d['name'] ) ); ?></p>
        <p style="margin:0 0 16px;">
          <?php printf(
            esc_html__( 'Segue em anexo a fatura referente à fase "%s" da encomenda #%s.', 'lpf' ),
            esc_html( $d['label'] ),
            esc_html( $d['order']->get_order_number() )
          ); ?>
        </p>
        <p style="color:#999;font-size:12px;margin:0;"><?php esc_html_e( 'Se tiver alguma questão, não hesite em contactar-nos.', 'lpf' ); ?></p>
      </td>
    </tr>
  </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Hooks de pagamento — marcar fase como paga
    // -------------------------------------------------------------------------

    public static function on_payment_complete( $mini_order_id ) {
        self::handle_paid_mini_order( $mini_order_id );
    }

    public static function on_status_changed( $mini_order_id, $old_status, $new_status, $order ) {
        if ( in_array( $new_status, [ 'processing', 'completed' ], true ) ) {
            self::handle_paid_mini_order( $mini_order_id );
        }
    }

    private static function handle_paid_mini_order( $mini_order_id ) {
        $mini = wc_get_order( $mini_order_id );
        if ( ! $mini || ! $mini->get_meta( '_lpf_mini_order' ) ) return;

        $parent_id = (int) $mini->get_meta( '_lpf_parent_order_id' );
        $phase_id  = $mini->get_meta( '_lpf_phase_id' );

        if ( ! $parent_id || ! $phase_id ) return;

        $parent = wc_get_order( $parent_id );
        if ( ! $parent ) return;

        $phases  = $parent->get_meta( '_lpf_payment_phases', true ) ?: [];
        $changed = false;

        foreach ( $phases as &$phase ) {
            if ( $phase['phase_id'] !== $phase_id ) continue;
            if ( ( $phase['status'] ?? '' ) === 'paid' ) break; // já pago

            $phase['status']  = 'paid';
            $phase['paid_at'] = current_time( 'd/m/Y H:i' );
            $changed          = true;
            break;
        }
        unset( $phase );

        if ( ! $changed ) return;

        $parent->update_meta_data( '_lpf_payment_phases', $phases );
        $parent->save();

        $label = '';
        foreach ( $phases as $p ) {
            if ( $p['phase_id'] === $phase_id ) { $label = $p['description']; break; }
        }

        $parent->add_order_note(
            sprintf( __( 'Fase "%s" paga online (mini-encomenda #%d).', 'lpf' ),
                $label ?: $phase_id,
                $mini_order_id
            ),
            true
        );

        $mini->add_order_note(
            sprintf( __( 'Pagamento aplicado à fase "%s" da encomenda #%d.', 'lpf' ),
                $label ?: $phase_id,
                $parent_id
            )
        );
    }
}
