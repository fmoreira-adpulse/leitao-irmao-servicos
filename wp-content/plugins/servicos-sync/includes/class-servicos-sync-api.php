<?php
/**
 * Endpoints REST usados pelo plugin "Serviços Sync (Conta)", no E-commerce,
 * para consultar tickets, responder e abrir tickets novos em nome do cliente.
 *
 * Ao contrário do sync de encomendas (só emissor), aqui a Plataforma também
 * recebe pedidos — por isso precisa da sua própria capability dedicada, para
 * o utilizador de serviço criado no E-commerce se autenticar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Servicos_Sync_Api {

	const CAP = 'receive_servicos_sync_tickets';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'registar_rotas' ) );
	}

	public function registar_rotas() {
		register_rest_route(
			'servicos-sync/v1',
			'/ping',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'ping' ),
				'permission_callback' => array( $this, 'verificar_permissao' ),
			)
		);

		register_rest_route(
			'servicos-sync/v1',
			'/reply',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'responder' ),
				'permission_callback' => array( $this, 'verificar_permissao' ),
			)
		);

		register_rest_route(
			'servicos-sync/v1',
			'/ticket-open',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'abrir_ticket' ),
				'permission_callback' => array( $this, 'verificar_permissao' ),
			)
		);
	}

	/**
	 * Só o utilizador de serviço (ou administrador) com a capability dedicada pode aceder —
	 * mesmo princípio usado do lado do E-commerce para as chamadas no sentido inverso.
	 */
	public function verificar_permissao() {
		return is_user_logged_in() && current_user_can( self::CAP );
	}

	public function ping() {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Resposta do cliente a um ticket existente, entregue pelo E-commerce.
	 */
	public function responder( WP_REST_Request $request ) {
		$dados = $request->get_json_params();

		$ticket_id = absint( $dados['ticket_id'] ?? 0 );
		$order_ref = sanitize_text_field( (string) ( $dados['order_ref'] ?? '' ) );
		$email     = sanitize_email( (string) ( $dados['customer_email'] ?? '' ) );
		$mensagem  = trim( wp_strip_all_tags( (string) ( $dados['message'] ?? '' ) ) );

		if ( ! $ticket_id || '' === $order_ref || '' === $mensagem ) {
			return new WP_REST_Response( array( 'erro' => 'Campos em falta.' ), 400 );
		}

		$order = self::resolver_e_validar_encomenda( $order_ref, $email );
		if ( is_wp_error( $order ) ) {
			return new WP_REST_Response( array( 'erro' => $order->get_error_message() ), (int) $order->get_error_data() );
		}

		global $wcsts_ticket_model;

		if ( ! self::ticket_pertence_a_encomenda( $ticket_id, $order ) ) {
			return new WP_REST_Response( array( 'erro' => 'Ticket não encontrado para esta encomenda.' ), 404 );
		}

		if ( 'closed' === $wcsts_ticket_model->get_status( $ticket_id ) ) {
			return new WP_REST_Response( array( 'erro' => 'Este ticket está fechado.' ), 409 );
		}

		self::gravar_resposta_cliente( $ticket_id, $mensagem, $order );

		$payload = Servicos_Sync_Tickets::construir_payload( $ticket_id );
		if ( ! $payload ) {
			return new WP_REST_Response( array( 'erro' => 'Não foi possível confirmar o ticket.' ), 500 );
		}

		return new WP_REST_Response( array( 'ticket' => $payload ), 200 );
	}

	/**
	 * Ticket novo aberto pelo cliente sobre uma das suas encomendas, entregue pelo E-commerce.
	 */
	public function abrir_ticket( WP_REST_Request $request ) {
		$dados = $request->get_json_params();

		$order_ref = sanitize_text_field( (string) ( $dados['order_ref'] ?? '' ) );
		$email     = sanitize_email( (string) ( $dados['customer_email'] ?? '' ) );
		$assunto   = trim( sanitize_text_field( (string) ( $dados['subject'] ?? '' ) ) );
		$mensagem  = trim( wp_strip_all_tags( (string) ( $dados['message'] ?? '' ) ) );

		if ( '' === $order_ref || '' === $assunto || '' === $mensagem ) {
			return new WP_REST_Response( array( 'erro' => 'Campos em falta.' ), 400 );
		}

		$order = self::resolver_e_validar_encomenda( $order_ref, $email );
		if ( is_wp_error( $order ) ) {
			return new WP_REST_Response( array( 'erro' => $order->get_error_message() ), (int) $order->get_error_data() );
		}

		global $wcsts_ticket_model, $wcsts_email_model;

		$user_id   = $order->get_user_id() ?: 0;
		$ticket_id = $wcsts_ticket_model->open_new_ticket(
			false,
			$assunto,
			$mensagem,
			'order',
			$order,
			$user_id,
			array( 'post_author' => $user_id )
		);

		if ( ! $ticket_id || is_wp_error( $ticket_id ) ) {
			return new WP_REST_Response( array( 'erro' => 'Não foi possível abrir o ticket.' ), 500 );
		}

		// open_new_ticket() com is_ajax=false não envia notificações (esse caminho é usado
		// para tickets abertos automaticamente pela própria Plataforma) — aqui é o cliente
		// a abrir o ticket, por isso disparamos as mesmas notificações que o ajax normal envia.
		$destinatarios = $wcsts_ticket_model->get_topic_recipients( $ticket_id, 'order' );
		$wcsts_email_model->send_new_ticket_notification_to_admin( $ticket_id, $mensagem, $destinatarios );

		$cliente = $order->get_user();
		if ( $cliente ) {
			$wcsts_email_model->send_new_ticket_notification_to_user( $ticket_id, $mensagem, $cliente, 'order', $order );
		}

		$payload = Servicos_Sync_Tickets::construir_payload( $ticket_id );
		if ( ! $payload ) {
			return new WP_REST_Response( array( 'erro' => 'Ticket criado mas não foi possível confirmá-lo.' ), 500 );
		}

		return new WP_REST_Response( array( 'ticket' => $payload ), 200 );
	}

	/**
	 * Grava a mensagem do cliente num ticket existente, replicando o que
	 * WCSTS_Ticket::ajax_add_new_message() faz para pedidos vindos do browser.
	 */
	private static function gravar_resposta_cliente( $ticket_id, $mensagem, $order ) {
		global $wcsts_ticket_model, $wcsts_ticket_message_model, $wcsts_email_model;

		$autor_id = $order->get_user_id() ?: null;
		$wcsts_ticket_message_model->add_reply( $ticket_id, $mensagem, true, $autor_id );

		$wcsts_ticket_model->update_new_messages_counter( $ticket_id, 1 );

		$status_para_mudar = $wcsts_ticket_model->get_status_to_which_automatically_swith_in_case_of_reply( $ticket_id );
		if ( false !== $status_para_mudar ) {
			$wcsts_ticket_model->set_status( $ticket_id, $status_para_mudar );
		}

		$destinatarios = $wcsts_ticket_model->get_topic_recipients( $ticket_id, 'order' );
		$wcsts_email_model->send_reply_notification_to_admin( $ticket_id, $mensagem, $destinatarios );

		// update_modified_date() do WCSTS é privado; isto tem o mesmo efeito prático
		// (atualiza post_modified, usado para ordenar por atividade recente).
		wp_update_post( array( 'ID' => $ticket_id ) );
	}

	/**
	 * Encontra a encomenda pela referência tal como é mostrada ao cliente
	 * (get_order_number()). Neste site, o ad-pulse substitui o número da
	 * encomenda pelo meta `_order_number` quando este existe (ver
	 * ad-pulse/includes/orders/list.php) — não é o ID numérico do post.
	 * Encomendas sem esse meta continuam a usar o ID numérico como referência.
	 */
	private static function encontrar_encomenda_por_referencia( $order_ref ) {
		$encontradas = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => '_order_number',
				'meta_value' => $order_ref,
			)
		);

		if ( ! empty( $encontradas ) ) {
			return $encontradas[0];
		}

		$order_id = absint( $order_ref );
		return $order_id ? wc_get_order( $order_id ) : false;
	}

	/**
	 * Resolve a encomenda pela referência e confirma que o email corresponde —
	 * defesa extra, já que o E-commerce também valida a titularidade do seu lado.
	 */
	private static function resolver_e_validar_encomenda( $order_ref, $email ) {
		$order = self::encontrar_encomenda_por_referencia( $order_ref );

		if ( ! $order || (string) $order->get_order_number() !== (string) $order_ref ) {
			return new WP_Error( 'ssync', 'Encomenda não encontrada.', 404 );
		}

		if ( $order->get_meta( '_lpf_mini_order' ) ) {
			return new WP_Error( 'ssync', 'Encomenda inválida.', 404 );
		}

		if ( '' !== $email && ! is_email( $email ) ) {
			return new WP_Error( 'ssync', 'Email de cliente inválido.', 400 );
		}

		if ( '' !== $email && 0 !== strcasecmp( $email, $order->get_billing_email() ) ) {
			return new WP_Error( 'ssync', 'Email de cliente não corresponde à encomenda.', 403 );
		}

		return $order;
	}

	/**
	 * Confirma que o ticket é do tipo "encomenda" e está associado à encomenda indicada.
	 */
	private static function ticket_pertence_a_encomenda( $ticket_id, $order ) {
		global $wcsts_ticket_model;

		if ( 'wcsts_ticket' !== get_post_type( $ticket_id ) ) {
			return false;
		}

		if ( 'order' !== $wcsts_ticket_model->get_attributes( $ticket_id, 'ticket_type' ) ) {
			return false;
		}

		return (int) $wcsts_ticket_model->get_attributes( $ticket_id, 'associated_order' ) === $order->get_id();
	}

	/**
	 * Cria a role/capability que o utilizador de serviço do E-commerce usa para se
	 * autenticar aqui. Chamado na ativação do plugin.
	 */
	public static function garantir_capability() {
		if ( ! get_role( 'servicos_sync_receiver' ) ) {
			add_role(
				'servicos_sync_receiver',
				'Serviços Sync (recetor de tickets)',
				array(
					'read'   => true, // Mínimo exigido pelo WordPress para autenticar via REST.
					self::CAP => true,
				)
			);
		}

		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( self::CAP ) ) {
			$admin->add_cap( self::CAP );
		}
	}
}
