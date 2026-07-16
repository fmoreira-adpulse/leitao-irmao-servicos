<?php
/**
 * Deteta mudanças nas encomendas e envia os dados para o E-commerce.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Servicos_Sync_Sender {

	/**
	 * IDs de encomendas marcadas para sincronizar neste pedido.
	 * O envio real acontece uma única vez no shutdown, já com o estado final —
	 * vários hooks podem disparar para a mesma encomenda no mesmo pedido
	 * (ex: gravar fases + mudança de status no mesmo save do admin).
	 */
	private static $pendentes = array();

	public function __construct() {
		// Dispara em qualquer mudança de status (inclui criação da encomenda).
		add_action( 'woocommerce_order_status_changed', array( $this, 'ao_mudar_status' ), 10, 3 );

		// Dispara também quando o total da encomenda é alterado manualmente no admin.
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'ao_gravar_encomenda' ), 50, 1 );

		// Disparada pelo plugin Pagamentos Faseados sempre que as fases de uma encomenda
		// mudam (guardar, enviar link, marcar como pago, pagamento online, faturas).
		add_action( 'lpf_phases_updated', array( $this, 'sincronizar_encomenda' ), 10, 1 );
	}

	public function ao_mudar_status( $order_id, $status_antigo, $status_novo ) {
		$this->sincronizar_encomenda( $order_id );
	}

	public function ao_gravar_encomenda( $order_id ) {
		$this->sincronizar_encomenda( $order_id );
	}

	/**
	 * Marca a encomenda para sincronização no fim do pedido.
	 */
	public function sincronizar_encomenda( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Mini-encomendas criadas pelos Pagamentos Faseados são um detalhe interno
		// da plataforma (transportam o pagamento de uma fase) — nunca sincronizar.
		if ( $order->get_meta( '_lpf_mini_order' ) ) {
			return;
		}

		if ( empty( self::$pendentes ) ) {
			add_action( 'shutdown', array( __CLASS__, 'enviar_pendentes' ) );
		}

		self::$pendentes[ $order->get_id() ] = true;
	}

	/**
	 * Envia todas as encomendas marcadas neste pedido (uma vez cada, estado final).
	 */
	public static function enviar_pendentes() {
		$ids             = array_keys( self::$pendentes );
		self::$pendentes = array();

		foreach ( $ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			$payload = self::montar_payload( $order );

			$log_id = Servicos_Sync_Log::registar_tentativa(
				$payload['order_ref'],
				$payload['customer_email'],
				$payload['hash'],
				wp_json_encode( $payload )
			);

			self::enviar( $log_id, $payload );
		}
	}

	/**
	 * Monta o payload completo da encomenda (resumo + fases de pagamento), com hash.
	 */
	public static function montar_payload( $order ) {
		$payload = array(
			'order_ref'      => $order->get_order_number(),
			'customer_email' => $order->get_billing_email(),
			'value'          => (string) $order->get_total(),
			'currency'       => $order->get_currency(),
			'status'         => $order->get_status(),
			'created_date'   => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
		);

		$fases = self::montar_fases( $order );

		$payload['fases']      = $fases['linhas'];
		$payload['total_pago'] = $fases['total_pago'];
		$payload['em_falta']   = $fases['em_falta'];
		$payload['itens']      = self::montar_itens( $order );

		// Nome legível do estado (inclui estados personalizados da Plataforma,
		// que o E-commerce não conhece pelo slug).
		$payload['status_label'] = wc_get_order_status_name( $order->get_status() );

		$payload['hash'] = self::calcular_hash( $payload );

		return $payload;
	}

	/**
	 * Linhas da encomenda (artigos e taxas), para o cliente ver o detalhe
	 * do serviço na loja e não apenas o plano de pagamentos.
	 */
	private static function montar_itens( $order ) {
		$itens = array();

		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			$quantidade = ( $item instanceof WC_Order_Item_Product ) ? $item->get_quantity() : 1;

			// Meta-dados visíveis ao cliente (personalizações, variações, etc.) —
			// a mesma lista que o WooCommerce mostra numa encomenda normal.
			$meta = array();
			foreach ( $item->get_formatted_meta_data( '_', true ) as $meta_item ) {
				$meta[] = array(
					'chave' => wp_strip_all_tags( (string) $meta_item->display_key ),
					'valor' => trim( wp_strip_all_tags( (string) $meta_item->display_value ) ),
				);
			}

			$itens[] = array(
				'nome'       => (string) $item->get_name(),
				'quantidade' => (string) $quantidade,
				'total'      => number_format( (float) $item->get_total() + (float) $item->get_total_tax(), 2, '.', '' ),
				'meta'       => $meta,
			);
		}

		return $itens;
	}

	/**
	 * Lê as fases de pagamento (meta _lpf_payment_phases do plugin Pagamentos Faseados)
	 * e devolve as linhas para o payload + totais.
	 *
	 * Todos os valores numéricos vão como string com 2 casas decimais, para o hash
	 * sobreviver intacto ao round-trip JSON no recetor.
	 */
	private static function montar_fases( $order ) {
		$meta            = $order->get_meta( '_lpf_payment_phases', true );
		$total_encomenda = (float) $order->get_total();
		$linhas          = array();
		$total_pago      = 0.0;

		if ( is_array( $meta ) ) {
			$posicao = 0;

			foreach ( $meta as $fase ) {
				$valor    = (float) ( $fase['value'] ?? 0 );
				$tipo     = ( $fase['type'] ?? 'nominal' );
				$montante = ( 'percentage' === $tipo ) ? ( $valor / 100 ) * $total_encomenda : $valor;
				$pago     = ( ( $fase['status'] ?? 'pending' ) === 'paid' );
				$metodo   = $fase['method'] ?? 'manual';

				if ( $pago ) {
					$total_pago += $montante;
				}

				// Link de pagamento: mesma regra da área de cliente da plataforma —
				// fase pendente, método online, link já enviado e mini-encomenda ainda por pagar.
				$payment_url = '';
				if ( ! $pago && 'online' === $metodo && ! empty( $fase['link_sent_at'] ) && ! empty( $fase['mini_order_id'] ) ) {
					$mini = wc_get_order( (int) $fase['mini_order_id'] );
					if ( $mini && ! $mini->is_paid() ) {
						$payment_url = $mini->get_checkout_payment_url();
					}
				}

				$invoice_url = '';
				if ( $pago && ! empty( $fase['invoice_file_id'] ) ) {
					$invoice_url = (string) wp_get_attachment_url( (int) $fase['invoice_file_id'] );
				}

				$linhas[] = array(
					'phase_id'    => (string) ( $fase['phase_id'] ?? '' ),
					'description' => (string) ( $fase['description'] ?? '' ),
					'type'        => $tipo,
					'value'       => number_format( $valor, 2, '.', '' ),
					'amount'      => number_format( $montante, 2, '.', '' ),
					'method'      => $metodo,
					'status'      => $pago ? 'paid' : 'pending',
					'paid_at'     => (string) ( $fase['paid_at'] ?? '' ),
					'payment_url' => $payment_url,
					'invoice_url' => $invoice_url,
					'position'    => (string) $posicao,
				);

				$posicao++;
			}
		}

		return array(
			'linhas'     => $linhas,
			'total_pago' => number_format( $total_pago, 2, '.', '' ),
			'em_falta'   => number_format( max( 0.0, $total_encomenda - $total_pago ), 2, '.', '' ),
		);
	}

	/**
	 * Calcula um hash estável do payload, para deteção de duplicados e idempotência.
	 * Quando o payload inclui fases, estas entram no hash (o recetor replica esta fórmula).
	 */
	public static function calcular_hash( $payload ) {
		$base = $payload['order_ref'] . '|' . $payload['value'] . '|' . $payload['status'] . '|' . $payload['created_date'];

		if ( isset( $payload['fases'] ) ) {
			$base .= '|' . wp_json_encode( $payload['fases'] ) . '|' . $payload['total_pago'] . '|' . $payload['em_falta'];
		}

		if ( isset( $payload['itens'] ) ) {
			$base .= '|' . wp_json_encode( $payload['itens'] );
		}

		if ( isset( $payload['status_label'] ) ) {
			$base .= '|' . $payload['status_label'];
		}

		return hash( 'sha256', $base );
	}

	/**
	 * Envia efetivamente o payload para o endpoint REST do E-commerce.
	 * Usado tanto no envio inicial como nos retries.
	 */
	public static function enviar( $log_id, $payload ) {
		$opcoes = get_option( 'servicos_sync_opcoes', array() );

		$url      = isset( $opcoes['ecommerce_url'] ) ? trailingslashit( $opcoes['ecommerce_url'] ) . 'wp-json/servicos-sync/v1/receive' : '';
		$user     = isset( $opcoes['ecommerce_user'] ) ? $opcoes['ecommerce_user'] : '';
		$app_pass = isset( $opcoes['ecommerce_app_password'] ) ? $opcoes['ecommerce_app_password'] : '';

		if ( empty( $url ) || empty( $user ) || empty( $app_pass ) ) {
			Servicos_Sync_Log::atualizar_resultado( $log_id, Servicos_Sync_Log::STATUS_FALHOU, 'Configuração incompleta (URL/utilizador/password não definidos).' );
			return false;
		}

		$resposta = wp_remote_post(
			$url,
			array(
				'timeout'   => 15,
				'headers'   => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $app_pass ),
					'Content-Type'  => 'application/json',
				),
				'body'      => wp_json_encode( $payload ),
				'blocking'  => true,
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
}
