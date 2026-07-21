<?php
/**
 * Cron jobs: retry automático de falhas e reconciliação diária completa.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Servicos_Sync_Cron {

	const HOOK_RETRY                  = 'servicos_sync_retry_event';
	const HOOK_RECONCILIACAO          = 'servicos_sync_reconciliacao_event';
	const HOOK_RECONCILIACAO_TICKETS  = 'servicos_sync_reconciliacao_tickets_event';
	const MAX_TENTATIVAS              = 5;

	public function __construct() {
		add_action( self::HOOK_RETRY, array( $this, 'processar_retries' ) );
		add_action( self::HOOK_RECONCILIACAO, array( $this, 'processar_reconciliacao' ) );
		add_action( self::HOOK_RECONCILIACAO_TICKETS, array( $this, 'processar_reconciliacao_tickets' ) );
		add_filter( 'cron_schedules', array( $this, 'adicionar_intervalo_10min' ) );
	}

	public function adicionar_intervalo_10min( $schedules ) {
		$schedules['servicos_sync_10min'] = array(
			'interval' => 10 * MINUTE_IN_SECONDS,
			'display'  => __( 'A cada 10 minutos (Serviços Sync)', 'servicos-sync' ),
		);
		return $schedules;
	}

	public static function agendar_eventos() {
		if ( ! wp_next_scheduled( self::HOOK_RETRY ) ) {
			wp_schedule_event( time() + 600, 'servicos_sync_10min', self::HOOK_RETRY );
		}
		if ( ! wp_next_scheduled( self::HOOK_RECONCILIACAO ) ) {
			wp_schedule_event( strtotime( 'tomorrow 03:00' ), 'daily', self::HOOK_RECONCILIACAO );
		}
		// Horário diferente do das encomendas, para não sobrecarregar o E-commerce
		// com dois pedidos de reconciliação ao mesmo tempo.
		if ( ! wp_next_scheduled( self::HOOK_RECONCILIACAO_TICKETS ) ) {
			wp_schedule_event( strtotime( 'tomorrow 03:15' ), 'daily', self::HOOK_RECONCILIACAO_TICKETS );
		}
	}

	public static function limpar_eventos() {
		wp_clear_scheduled_hook( self::HOOK_RETRY );
		wp_clear_scheduled_hook( self::HOOK_RECONCILIACAO );
		wp_clear_scheduled_hook( self::HOOK_RECONCILIACAO_TICKETS );
	}

	/**
	 * Reenvia registos falhados, respeitando o backoff progressivo.
	 * Marca como "necessita atenção" (e alerta o admin) quando esgota as tentativas.
	 */
	public function processar_retries() {
		$registos = Servicos_Sync_Log::obter_para_retry( self::MAX_TENTATIVAS );

		foreach ( $registos as $registo ) {
			if ( ! Servicos_Sync_Log::pode_tentar_agora( $registo ) ) {
				continue;
			}

			$payload = json_decode( $registo->payload, true );
			if ( ! $payload ) {
				continue;
			}

			$sucesso = Servicos_Sync_Sender::enviar( $registo->id, $payload );

			if ( ! $sucesso && ( (int) $registo->tentativas + 1 ) >= self::MAX_TENTATIVAS ) {
				Servicos_Sync_Log::marcar_necessita_atencao( $registo->id );
				$this->alertar_admin( $registo->order_ref, 'Excedeu o número máximo de tentativas de sincronização.' );
			}
		}
	}

	/**
	 * Compara a lista de encomendas locais com a lista confirmada no E-commerce
	 * e reenvia tudo o que estiver em falta ou desatualizado.
	 * Esta é a rede de segurança que apanha falhas que nem o retry resolveu.
	 */
	public function processar_reconciliacao() {
		$opcoes = get_option( 'servicos_sync_opcoes', array() );

		$url      = isset( $opcoes['ecommerce_url'] ) ? trailingslashit( $opcoes['ecommerce_url'] ) . 'wp-json/servicos-sync/v1/list' : '';
		$user     = isset( $opcoes['ecommerce_user'] ) ? $opcoes['ecommerce_user'] : '';
		$app_pass = isset( $opcoes['ecommerce_app_password'] ) ? $opcoes['ecommerce_app_password'] : '';

		if ( empty( $url ) || empty( $user ) || empty( $app_pass ) ) {
			return;
		}

		// Últimos 90 dias — suficiente para apanhar qualquer atraso sem sobrecarregar.
		$desde = gmdate( 'c', strtotime( '-90 days' ) );

		$resposta = wp_remote_get(
			add_query_arg( 'since', $desde, $url ),
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $app_pass ),
				),
			)
		);

		if ( is_wp_error( $resposta ) || 200 !== wp_remote_retrieve_response_code( $resposta ) ) {
			$this->alertar_admin( '-', 'Falha ao contactar o E-commerce durante a reconciliação diária. Verifique a ligação.' );
			return;
		}

		$remoto = json_decode( wp_remote_retrieve_body( $resposta ), true );
		if ( ! is_array( $remoto ) ) {
			return;
		}

		// Mapa order_ref => hash confirmado no E-commerce.
		$remoto_hashes = array();
		foreach ( $remoto as $item ) {
			if ( isset( $item['order_ref'], $item['hash'] ) ) {
				$remoto_hashes[ $item['order_ref'] ] = $item['hash'];
			}
		}

		// Percorre as encomendas WooCommerce locais dos últimos 90 dias e compara.
		$encomendas = wc_get_orders(
			array(
				'limit'        => -1,
				'date_created' => '>' . strtotime( '-90 days' ),
			)
		);

		foreach ( $encomendas as $order ) {
			// Mini-encomendas dos Pagamentos Faseados são internas — nunca entram na reconciliação.
			if ( $order->get_meta( '_lpf_mini_order' ) ) {
				continue;
			}

			$payload = Servicos_Sync_Sender::montar_payload( $order );

			$hash_remoto = isset( $remoto_hashes[ $payload['order_ref'] ] ) ? $remoto_hashes[ $payload['order_ref'] ] : null;

			if ( $hash_remoto !== $payload['hash'] ) {
				// Divergente ou em falta no E-commerce: reenvia.
				$log_id = Servicos_Sync_Log::registar_tentativa(
					$payload['order_ref'],
					$payload['customer_email'],
					$payload['hash'],
					wp_json_encode( $payload )
				);
				Servicos_Sync_Sender::enviar( $log_id, $payload );
			}
		}
	}

	/**
	 * Compara os tickets de encomenda locais com os confirmados no E-commerce
	 * e reenvia tudo o que estiver em falta ou desatualizado — mesmo princípio
	 * da reconciliação de encomendas, mas contra o endpoint /ticket-list.
	 */
	public function processar_reconciliacao_tickets() {
		$opcoes = get_option( 'servicos_sync_opcoes', array() );

		$url      = isset( $opcoes['ecommerce_url'] ) ? trailingslashit( $opcoes['ecommerce_url'] ) . 'wp-json/servicos-sync/v1/ticket-list' : '';
		$user     = isset( $opcoes['ecommerce_user'] ) ? $opcoes['ecommerce_user'] : '';
		$app_pass = isset( $opcoes['ecommerce_app_password'] ) ? $opcoes['ecommerce_app_password'] : '';

		if ( empty( $url ) || empty( $user ) || empty( $app_pass ) ) {
			return;
		}

		$resposta = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $app_pass ),
				),
			)
		);

		if ( is_wp_error( $resposta ) || 200 !== wp_remote_retrieve_response_code( $resposta ) ) {
			$this->alertar_admin( '-', 'Falha ao contactar o E-commerce durante a reconciliação diária de tickets. Verifique a ligação.' );
			return;
		}

		$remoto = json_decode( wp_remote_retrieve_body( $resposta ), true );
		if ( ! is_array( $remoto ) ) {
			return;
		}

		// Mapa ticket_id => hash confirmado no E-commerce.
		$remoto_hashes = array();
		foreach ( $remoto as $item ) {
			if ( isset( $item['ticket_id'], $item['hash'] ) ) {
				$remoto_hashes[ (string) $item['ticket_id'] ] = $item['hash'];
			}
		}

		foreach ( Servicos_Sync_Tickets::listar_todos_com_hash() as $ticket_id => $payload ) {
			$hash_remoto = isset( $remoto_hashes[ $payload['ticket_id'] ] ) ? $remoto_hashes[ $payload['ticket_id'] ] : null;

			if ( $hash_remoto !== $payload['hash'] ) {
				$log_id = Servicos_Sync_Log::registar_tentativa(
					'ticket:' . $ticket_id,
					$payload['customer_email'],
					$payload['hash'],
					wp_json_encode( $payload )
				);
				Servicos_Sync_Tickets::enviar( $log_id, $payload );
			}
		}
	}

	/**
	 * Envia um email ao administrador quando algo precisa de atenção manual.
	 */
	private function alertar_admin( $order_ref, $mensagem ) {
		$destinatario = get_option( 'admin_email' );
		$assunto      = '[Serviços Sync] Necessita atenção: ' . $order_ref;
		$corpo        = "Encomenda: {$order_ref}\n\n{$mensagem}\n\nVerifique o ecrã 'Serviços Sync' no wp-admin para mais detalhes.";

		wp_mail( $destinatario, $assunto, $corpo );
	}
}
