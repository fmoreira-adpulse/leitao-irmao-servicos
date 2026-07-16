<?php
defined( 'ABSPATH' ) || exit;

class LON_Handler {

    public function __construct() {
        add_filter( 'cosmbp_order_status_settings_fields', array( $this, 'add_status_fields' ) );
        // Prioridade 5 para disparar antes do sistema existente (prioridade 10)
        add_action( 'woocommerce_order_status_changed', array( $this, 'on_status_changed' ), 5, 3 );
    }

    /**
     * Adiciona os novos campos de configuração ao metabox de cada estado.
     */
    public function add_status_fields( array $fields ): array {
        $new_fields = array(
            array(
                'type'    => 'subheading',
                'content' => __( 'Notificações por Role', 'lon' ),
            ),
            array(
                'id'         => '_lon_notify_customer',
                'type'       => 'switcher',
                'title'      => __( 'Notificar cliente', 'lon' ),
                'desc'       => __( 'Envia o e-mail de cliente quando o estado muda para este.', 'lon' ),
                'default'    => '0',
                'dependency' => array( '_enable_email', '==', 'true' ),
            ),
            array(
                'id'         => '_lon_customer_subject',
                'type'       => 'text',
                'title'      => __( 'Assunto (cliente)', 'lon' ),
                'desc'       => __( 'Se vazio, usa o assunto definido em WooCommerce → Definições → E-mails. Placeholders: {order_date}, {order_number}, {order_status}, {site_title}', 'lon' ),
                'dependency' => array( '_lon_notify_customer', '==', 'true' ),
            ),
            array(
                'id'         => '_lon_customer_heading',
                'type'       => 'text',
                'title'      => __( 'Cabeçalho (cliente)', 'lon' ),
                'desc'       => __( 'Título no topo do e-mail do cliente. Se vazio, usa o das definições do WooCommerce.', 'lon' ),
                'dependency' => array( '_lon_notify_customer', '==', 'true' ),
            ),
            array(
                'id'         => '_lon_customer_additional_content',
                'type'       => 'textarea',
                'title'      => __( 'Texto adicional (cliente)', 'lon' ),
                'desc'       => __( 'Texto livre incluído no corpo do e-mail do cliente. Se vazio, usa o das definições do WooCommerce.', 'lon' ),
                'dependency' => array( '_lon_notify_customer', '==', 'true' ),
            ),
            array(
                'id'         => '_lon_notify_internal',
                'type'       => 'switcher',
                'title'      => __( 'Notificar internamente', 'lon' ),
                'desc'       => __( 'Envia notificação aos intervenientes da encomenda (utilizadores que já atuaram nela) que tenham uma das roles configuradas abaixo.', 'lon' ),
                'default'    => '0',
                'dependency' => array( '_enable_email', '==', 'true' ),
            ),
            array(
                'id'         => '_lon_internal_roles',
                'type'       => 'checkbox',
                'title'      => __( 'Roles a notificar', 'lon' ),
                'desc'       => __( 'Em cada role, recebem os intervenientes da encomenda; se a role não tiver nenhum interveniente nesta encomenda, recebem todos os utilizadores dessa role.', 'lon' ),
                'options'    => $this->get_roles_options(),
                'dependency' => array( '_lon_notify_internal', '==', 'true' ),
            ),
            array(
                'id'         => '_lon_internal_subject',
                'type'       => 'text',
                'title'      => __( 'Assunto (interno)', 'lon' ),
                'desc'       => __( 'Se vazio, usa o assunto definido em WooCommerce → Definições → E-mails. Placeholders: {order_date}, {order_number}, {order_status}, {site_title}', 'lon' ),
                'dependency' => array( '_lon_notify_internal', '==', 'true' ),
            ),
            array(
                'id'         => '_lon_internal_heading',
                'type'       => 'text',
                'title'      => __( 'Cabeçalho (interno)', 'lon' ),
                'desc'       => __( 'Título no topo do e-mail interno. Se vazio, usa o das definições do WooCommerce.', 'lon' ),
                'dependency' => array( '_lon_notify_internal', '==', 'true' ),
            ),
            array(
                'id'         => '_lon_internal_additional_content',
                'type'       => 'textarea',
                'title'      => __( 'Texto adicional (interno)', 'lon' ),
                'desc'       => __( 'Texto livre incluído no corpo do e-mail interno. Se vazio, usa o das definições do WooCommerce.', 'lon' ),
                'dependency' => array( '_lon_notify_internal', '==', 'true' ),
            ),
        );

        // Insere os novos campos após _recipient_cc
        foreach ( $fields as $i => $field ) {
            if ( isset( $field['id'] ) && '_recipient_cc' === $field['id'] ) {
                array_splice( $fields, $i + 1, 0, $new_fields );
                return $fields;
            }
        }

        return array_merge( $fields, $new_fields );
    }

    /**
     * Chamado quando o estado de uma encomenda muda.
     * Se o novo estado tiver configuração nova (lon), assume o controlo e suprime o sistema antigo.
     */
    public function on_status_changed( int $order_id, string $old_status, string $new_status ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // O hook woocommerce_order_status_changed pode disparar mais do que uma vez
        // para a mesma transição (ex.: HPOS com modo de compatibilidade ativo).
        // Regista todas as transições — não apenas as notificadas — para que uma
        // re-entrada legítima no mesmo estado (X → Y → X) volte a notificar.
        if ( $order->get_meta( '_lon_last_status_seen' ) === $new_status ) {
            return;
        }
        $order->update_meta_data( '_lon_last_status_seen', $new_status );
        $order->save_meta_data();

        $status_post = $this->get_status_post( $new_status );
        if ( ! $status_post ) {
            return;
        }

        $notify_customer = (bool) get_post_meta( $status_post->ID, '_lon_notify_customer', true );
        $notify_internal = (bool) get_post_meta( $status_post->ID, '_lon_notify_internal', true );

        if ( ! $notify_customer && ! $notify_internal ) {
            return;
        }

        // Suprime o e-mail do sistema antigo (bp-custom-order-status) para este estado
        add_filter( 'woocommerce_email_enabled_bvos_custom_' . $new_status, '__return_false' );

        if ( $notify_customer ) {
            $this->dispatch( $order, 'customer', $order->get_billing_email(), $status_post->ID );
        }

        if ( $notify_internal ) {
            foreach ( $this->get_internal_recipients( $status_post, $order ) as $email ) {
                $this->dispatch( $order, 'internal', $email, $status_post->ID );
            }
        }
    }

    /**
     * Obtém o post do CPT order_status correspondente ao slug do estado.
     */
    private function get_status_post( string $status ): ?WP_Post {
        $posts = get_posts( array(
            'numberposts' => 1,
            'post_type'   => 'order_status',
            'meta_query'  => array( array(
                'key'   => 'status_slug',
                'value' => $status,
            ) ),
        ) );

        return $posts[0] ?? null;
    }

    /**
     * Determina os destinatários internos: em cada role configurada, notifica os
     * intervenientes da encomenda (autores de notas + quem está a fazer a alteração);
     * se a role não tiver nenhum interveniente nesta encomenda, notifica todos os
     * utilizadores dessa role.
     */
    private function get_internal_recipients( WP_Post $status_post, WC_Order $order ): array {
        $configured_roles = get_post_meta( $status_post->ID, '_lon_internal_roles', true );
        $configured_roles = is_array( $configured_roles ) ? array_filter( $configured_roles ) : array();

        // Se não há roles configuradas, usa o fallback de shop managers
        if ( empty( $configured_roles ) ) {
            return $this->get_shop_manager_emails();
        }

        $participant_names = $this->get_order_participant_names( $order );
        $current_user      = wp_get_current_user();

        $emails = array();
        foreach ( $configured_roles as $role ) {
            $role_users = get_users( array( 'role' => $role ) );

            $participants = array_filter( $role_users, function ( WP_User $u ) use ( $participant_names, $current_user ) {
                return ( $current_user->ID > 0 && $u->ID === $current_user->ID )
                    || in_array( $u->display_name, $participant_names, true );
            } );

            foreach ( ( $participants ?: $role_users ) as $u ) {
                $emails[ $u->user_email ] = $u->user_email;
            }
        }

        return array_values( $emails );
    }

    /**
     * Nomes públicos dos utilizadores que já atuaram na encomenda (autores das
     * notas de encomenda, onde o WooCommerce regista o display_name).
     */
    private function get_order_participant_names( WC_Order $order ): array {
        $names = array();
        foreach ( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) as $note ) {
            if ( ! empty( $note->added_by ) && 'system' !== $note->added_by ) {
                $names[ $note->added_by ] = $note->added_by;
            }
        }
        return array_values( $names );
    }

    private function get_shop_manager_emails(): array {
        $users = get_users( array( 'role' => 'shop_manager' ) );
        return array_map( function ( WP_User $u ) { return $u->user_email; }, $users );
    }

    /**
     * Instancia um LON_Email e envia para o destinatário indicado.
     */
    private function dispatch( WC_Order $order, string $type, string $recipient, int $status_post_id ): void {
        if ( empty( $recipient ) ) {
            return;
        }

        if ( ! class_exists( 'LON_Email' ) ) {
            WC()->mailer(); // garante que a classe base WC_Email está carregada
            require_once LON_PLUGIN_DIR . 'includes/class-lon-email.php';
        }

        $email = new LON_Email();
        $email->setup( $order, $type, $recipient, $status_post_id );
        $email->send(
            $recipient,
            $email->get_subject(),
            $email->get_content(),
            $email->get_headers(),
            $email->get_attachments()
        );
    }

    /**
     * Devolve os roles do WordPress formatados para os campos de checkbox do CSF.
     */
    private function get_roles_options(): array {
        $options = array();
        foreach ( wp_roles()->get_names() as $key => $name ) {
            $options[ $key ] = translate_user_role( $name );
        }
        return $options;
    }
}
