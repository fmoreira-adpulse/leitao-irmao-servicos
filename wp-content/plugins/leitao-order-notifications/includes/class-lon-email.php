<?php
defined( 'ABSPATH' ) || exit;

class LON_Email extends WC_Email {

    public function __construct() {
        $this->id            = 'lon_notification';
        $this->template_base = LON_TEMPLATE_BASE;
        $this->placeholders  = array(
            '{order_date}'   => '',
            '{order_number}' => '',
            '{order_status}' => '',
        );

        parent::__construct();
    }

    /**
     * Prepara o e-mail com os dados da encomenda, tipo e destinatário.
     *
     * @param WC_Order $order
     * @param string   $type           'customer' ou 'internal'
     * @param string   $recipient      endereço de e-mail do destinatário
     * @param int      $status_post_id ID do post do CPT order_status (para textos por tipo)
     */
    public function setup( WC_Order $order, string $type, string $recipient, int $status_post_id = 0 ): void {
        $status = $order->get_status();

        $this->object         = $order;
        $this->recipient      = $recipient;
        $this->customer_email = ( 'customer' === $type );

        // Carrega as definições guardadas pelo WooCommerce para o e-mail do estado (assunto, cabeçalho, etc.)
        $bvos_id           = 'bvos_custom_' . $status;
        $this->option_name = 'woocommerce_' . $bvos_id . '_settings';
        $this->settings    = get_option( $this->option_name, array() );

        // Textos específicos por tipo de destinatário, definidos no metabox do estado;
        // quando preenchidos, têm precedência sobre as definições partilhadas do WooCommerce
        if ( $status_post_id > 0 ) {
            $prefix = 'customer' === $type ? '_lon_customer_' : '_lon_internal_';
            foreach ( array( 'subject', 'heading', 'additional_content' ) as $key ) {
                $custom = get_post_meta( $status_post_id, $prefix . $key, true );
                if ( is_string( $custom ) && '' !== trim( $custom ) ) {
                    $this->settings[ $key ] = $custom;
                }
            }
        }

        $this->subject    = $this->get_option( 'subject', __( 'Informação sobre a sua encomenda {site_title} de {order_date}', 'lon' ) );
        $this->heading    = $this->get_option( 'heading', __( 'Estado da encomenda alterado para {order_status}', 'lon' ) );
        $this->email_type = $this->get_option( 'email_type', 'html' );

        if ( 'customer' === $type ) {
            $this->template_html  = 'emails/customer-order-status-email.php';
            $this->template_plain = 'emails/plain/customer-order-status-email.php';
        } else {
            $this->template_html  = 'emails/admin-order-status-email.php';
            $this->template_plain = 'emails/plain/admin-order-status-email.php';
        }

        $this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
        $this->placeholders['{order_number}'] = $order->get_order_number();
        $this->placeholders['{order_status}'] = wc_get_order_status_name( $status );
    }

    public function get_content_html(): string {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => ! $this->customer_email,
                'plain_text'         => false,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_content_plain(): string {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => ! $this->customer_email,
                'plain_text'         => true,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }
}
