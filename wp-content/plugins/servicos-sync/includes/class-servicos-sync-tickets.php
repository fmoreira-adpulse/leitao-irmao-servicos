<?php
/**
 * Deteta mudanças em tickets do WCSTS (WooCommerce Support Ticket System) e
 * envia-os para o E-commerce, para aparecerem na secção "Serviços" da My Account.
 *
 * Só sincroniza tickets do tipo "encomenda" (wcsts_ticket_type = order) —
 * tickets internos sem encomenda associada nunca saem da Plataforma.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Servicos_Sync_Tickets {

	/**
	 * IDs de tickets marcados para sincronizar neste pedido — mesmo princípio do
	 * Servicos_Sync_Sender: o envio real acontece uma única vez no shutdown,
	 * já com o estado final (evita duplicar envios quando o ticket e a mensagem
	 * mudam no mesmo pedido).
	 */
	private static $pendentes = array();

	public function __construct() {
		add_action( 'save_post_wcsts_ticket', array( $this, 'ao_gravar_ticket' ), 20, 2 );
		add_action( 'save_post_wcsts_ticket_message', array( $this, 'ao_gravar_mensagem' ), 20, 2 );
		add_action( 'before_delete_post', array( $this, 'ao_apagar_ticket' ), 10 );
	}

	public function ao_gravar_ticket( $ticket_id, $post ) {
		if ( wp_is_post_revision( $ticket_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		self::marcar_pendente( $ticket_id );
	}

	public function ao_gravar_mensagem( $message_id, $post ) {
		if ( wp_is_post_revision( $message_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status || ! $post->post_parent ) {
			return;
		}

		self::marcar_pendente( (int) $post->post_parent );
	}

	/**
	 * Ordem de eliminação: o ticket deixou de existir na Plataforma. Envia-se de
	 * imediato (não fica para o shutdown) porque a esse ponto o post já não existe.
	 */
	public function ao_apagar_ticket( $post_id ) {
		if ( 'wcsts_ticket' !== get_post_type( $post_id ) ) {
			return;
		}

		global $wcsts_ticket_model;
		if ( 'order' !== $wcsts_ticket_model->get_attributes( $post_id, 'ticket_type' ) ) {
			return;
		}

		$hash    = hash( 'sha256', 'delete|' . $post_id );
		$payload = array(
			'ticket_id' => (string) $post_id,
			'deleted'   => true,
			'hash'      => $hash,
		);

		$log_id = Servicos_Sync_Log::registar_tentativa( 'ticket:' . $post_id, '', $hash, wp_json_encode( $payload ) );
		self::enviar( $log_id, $payload );
	}

	private static function marcar_pendente( $ticket_id ) {
		global $wcsts_ticket_model;

		if ( 'wcsts_ticket' !== get_post_type( $ticket_id ) ) {
			return;
		}

		// Só tickets de encomenda interessam ao E-commerce — tickets internos
		// (tipo "user") não têm encomenda a que se ligar do outro lado.
		if ( 'order' !== $wcsts_ticket_model->get_attributes( $ticket_id, 'ticket_type' ) ) {
			return;
		}

		if ( empty( self::$pendentes ) ) {
			add_action( 'shutdown', array( __CLASS__, 'enviar_pendentes' ) );
		}

		self::$pendentes[ $ticket_id ] = true;
	}

	/**
	 * Envia todos os tickets marcados neste pedido (uma vez cada, estado final).
	 */
	public static function enviar_pendentes() {
		$ids             = array_keys( self::$pendentes );
		self::$pendentes = array();

		foreach ( $ids as $ticket_id ) {
			$payload = self::construir_payload( $ticket_id );

			if ( ! $payload ) {
				continue;
			}

			$log_id = Servicos_Sync_Log::registar_tentativa(
				'ticket:' . $ticket_id,
				$payload['customer_email'],
				$payload['hash'],
				wp_json_encode( $payload )
			);

			self::enviar( $log_id, $payload );
		}
	}

	/**
	 * Monta o payload completo do ticket (dados + mensagens), com hash — no
	 * mesmo formato que Servicos_Sync_Conta_Tickets::validar_e_guardar() espera
	 * do lado do E-commerce.
	 *
	 * Devolve null se o ticket não existir ou não tiver encomenda associada
	 * (nesse caso não há nada de útil a sincronizar).
	 */
	public static function construir_payload( $ticket_id ) {
		global $wcsts_ticket_model, $wcsts_ticket_message_model;

		$ticket_post = get_post( $ticket_id );
		if ( ! $ticket_post || 'wcsts_ticket' !== $ticket_post->post_type ) {
			return null;
		}

		$order_id = (int) $wcsts_ticket_model->get_attributes( $ticket_id, 'associated_order' );
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order ) {
			return null;
		}

		$status_data = $wcsts_ticket_model->get_status_data( $ticket_id );

		$payload = array(
			'ticket_id'      => (string) $ticket_id,
			'order_id'       => (string) $order->get_id(),
			'order_ref'      => $order->get_order_number(),
			'customer_email' => $order->get_billing_email(),
			'subject'        => (string) $wcsts_ticket_model->get_subject( $ticket_id ),
			'status'         => (string) $wcsts_ticket_model->get_status( $ticket_id ),
			'status_label'   => self::extrair_status_label( $status_data ),
			'status_bg'      => (string) ( $status_data['background_color'] ?? '' ),
			'status_text'    => (string) ( $status_data['text_color'] ?? '' ),
			'created_date'   => get_post_time( 'c', true, $ticket_post ),
			'messages'       => self::montar_mensagens( $ticket_id ),
		);

		$payload['hash'] = self::calcular_hash( $payload );

		return $payload;
	}

	/**
	 * Extrai o texto do estado a partir do array devolvido por get_status_data().
	 * O WCSTS guarda a etiqueta como um array por idioma (suporte a WPML) —
	 * `$status_data['label'][$status_data['current_lang']]` — nunca como string
	 * simples, por isso não pode ser usado diretamente num contexto de string.
	 */
	private static function extrair_status_label( $status_data ) {
		$label = $status_data['label'] ?? '';

		if ( ! is_array( $label ) ) {
			return (string) $label;
		}

		$lang_atual = $status_data['current_lang'] ?? '';
		if ( $lang_atual && isset( $label[ $lang_atual ] ) && '' !== $label[ $lang_atual ] ) {
			return (string) $label[ $lang_atual ];
		}

		// Sem tradução para o idioma atual: usa a primeira etiqueta não vazia disponível.
		foreach ( $label as $texto ) {
			if ( is_string( $texto ) && '' !== $texto ) {
				return $texto;
			}
		}

		return '';
	}

	/**
	 * Lista as mensagens do ticket, com anexos — anexados só pelo staff, nunca
	 * pelo cliente (o formulário do E-commerce não tem campo de upload).
	 */
	private static function montar_mensagens( $ticket_id ) {
		global $wcsts_ticket_message_model;

		$mensagens = array();

		foreach ( $wcsts_ticket_message_model->get_messages_by_ticket_id( $ticket_id ) as $mensagem ) {
			$anexos = array();
			foreach ( $wcsts_ticket_message_model->get_attachments( $mensagem->ID ) as $url ) {
				$anexos[] = array(
					'nome' => basename( (string) $url ),
					'url'  => $url,
				);
			}

			$mensagens[] = array(
				'msg_id'      => (string) $mensagem->ID,
				'author'      => $mensagem->is_customer_message ? 'customer' : 'staff',
				'author_name' => $mensagem->is_customer_message ? '' : get_the_author_meta( 'display_name', $mensagem->post_author ),
				'content'     => (string) $mensagem->post_content,
				'date'        => get_post_time( 'c', true, $mensagem ),
				'attachments' => $anexos,
			);
		}

		return $mensagens;
	}

	/**
	 * Réplica exata da fórmula usada em Servicos_Sync_Conta_Tickets::validar_e_guardar()
	 * do lado do E-commerce — tem de produzir sempre o mesmo hash dos dois lados.
	 */
	public static function calcular_hash( $payload ) {
		$base = (string) $payload['ticket_id']
			. '|' . (string) $payload['order_id']
			. '|' . (string) $payload['order_ref']
			. '|' . (string) $payload['customer_email']
			. '|' . (string) $payload['subject']
			. '|' . (string) $payload['status']
			. '|' . (string) $payload['status_label']
			. '|' . (string) $payload['status_bg']
			. '|' . (string) $payload['status_text']
			. '|' . (string) $payload['created_date']
			. '|' . wp_json_encode( $payload['messages'] );

		return hash( 'sha256', $base );
	}

	/**
	 * Envia efetivamente o payload para o endpoint REST do E-commerce.
	 */
	public static function enviar( $log_id, $payload ) {
		$opcoes = get_option( 'servicos_sync_opcoes', array() );

		$url      = isset( $opcoes['ecommerce_url'] ) ? trailingslashit( $opcoes['ecommerce_url'] ) . 'wp-json/servicos-sync/v1/ticket-receive' : '';
		$user     = isset( $opcoes['ecommerce_user'] ) ? $opcoes['ecommerce_user'] : '';
		$app_pass = isset( $opcoes['ecommerce_app_password'] ) ? $opcoes['ecommerce_app_password'] : '';

		if ( empty( $url ) || empty( $user ) || empty( $app_pass ) ) {
			Servicos_Sync_Log::atualizar_resultado( $log_id, Servicos_Sync_Log::STATUS_FALHOU, 'Configuração incompleta (URL/utilizador/password não definidos).' );
			return false;
		}

		$resposta = wp_remote_post(
			$url,
			array(
				'timeout'  => 15,
				'headers'  => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $app_pass ),
					'Content-Type'  => 'application/json',
				),
				'body'     => wp_json_encode( $payload ),
				'blocking' => true,
			)
		);

		if ( is_wp_error( $resposta ) ) {
			Servicos_Sync_Log::atualizar_resultado( $log_id, Servicos_Sync_Log::STATUS_FALHOU, $resposta->get_error_message() );
			return false;
		}

		$codigo = wp_remote_retrieve_response_code( $resposta );
		$corpo  = json_decode( wp_remote_retrieve_body( $resposta ), true );

		$confirmado = ( 200 === $codigo && isset( $corpo['hash'] ) && $corpo['hash'] === $payload['hash'] );

		if ( $confirmado ) {
			Servicos_Sync_Log::atualizar_resultado( $log_id, Servicos_Sync_Log::STATUS_SUCESSO, wp_remote_retrieve_body( $resposta ) );
			return true;
		}

		Servicos_Sync_Log::atualizar_resultado(
			$log_id,
			Servicos_Sync_Log::STATUS_FALHOU,
			'HTTP ' . $codigo . ' — ' . wp_remote_retrieve_body( $resposta )
		);
		return false;
	}

	/**
	 * IDs (post ID) + hash confirmado de todos os tickets de encomenda existentes
	 * localmente — usado pela reconciliação diária para comparar com o E-commerce.
	 */
	public static function listar_todos_com_hash() {
		$ids = get_posts(
			array(
				'post_type'      => 'wcsts_ticket',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => 'wcsts_ticket_type',
						'value' => 'order',
					),
				),
			)
		);

		$resultado = array();
		foreach ( $ids as $ticket_id ) {
			$payload = self::construir_payload( $ticket_id );
			if ( $payload ) {
				$resultado[ $ticket_id ] = $payload;
			}
		}

		return $resultado;
	}
}
