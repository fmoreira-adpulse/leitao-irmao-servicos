<?php
/**
 * Gestão da tabela de log de sincronização.
 *
 * Guarda cada tentativa de envio de uma encomenda para o E-commerce,
 * para sabermos sempre o que foi enviado, quando, e se foi confirmado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Servicos_Sync_Log {

	const STATUS_PENDENTE   = 'pending';
	const STATUS_SUCESSO    = 'success';
	const STATUS_FALHOU     = 'failed';
	const STATUS_ATENCAO    = 'needs_attention'; // Excedeu o número máximo de tentativas.

	/**
	 * Cria a tabela de log (chamado na ativação do plugin).
	 */
	public static function criar_tabela() {
		global $wpdb;

		$table_name      = $wpdb->prefix . SERVICOS_SYNC_LOG_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_ref VARCHAR(64) NOT NULL,
			customer_email VARCHAR(190) NOT NULL,
			payload_hash VARCHAR(64) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			tentativas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			ultima_tentativa DATETIME NULL,
			resposta_http TEXT NULL,
			payload LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY order_ref (order_ref),
			KEY status (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Regista uma nova tentativa (ou reutiliza o registo pendente/falhado existente para o mesmo pedido).
	 */
	public static function registar_tentativa( $order_ref, $customer_email, $payload_hash, $payload_json ) {
		global $wpdb;
		$table = $wpdb->prefix . SERVICOS_SYNC_LOG_TABLE;

		// Se já existe um registo não confirmado para esta referência, atualiza-o em vez de duplicar.
		$existente = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE order_ref = %s AND status != %s ORDER BY id DESC LIMIT 1",
				$order_ref,
				self::STATUS_SUCESSO
			)
		);

		if ( $existente ) {
			$wpdb->update(
				$table,
				array(
					'customer_email' => $customer_email,
					'payload_hash'   => $payload_hash,
					'payload'        => $payload_json,
					'status'         => self::STATUS_PENDENTE,
				),
				array( 'id' => $existente->id )
			);
			return (int) $existente->id;
		}

		$wpdb->insert(
			$table,
			array(
				'order_ref'       => $order_ref,
				'customer_email'  => $customer_email,
				'payload_hash'    => $payload_hash,
				'payload'         => $payload_json,
				'status'          => self::STATUS_PENDENTE,
				'tentativas'      => 0,
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Atualiza o resultado de uma tentativa de envio.
	 */
	public static function atualizar_resultado( $log_id, $status, $resposta_http ) {
		global $wpdb;
		$table = $wpdb->prefix . SERVICOS_SYNC_LOG_TABLE;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				 SET status = %s, resposta_http = %s, tentativas = tentativas + 1, ultima_tentativa = %s
				 WHERE id = %d",
				$status,
				is_string( $resposta_http ) ? $resposta_http : wp_json_encode( $resposta_http ),
				current_time( 'mysql' ),
				$log_id
			)
		);
	}

	/**
	 * Marca como "necessita atenção" quando excede o número máximo de tentativas.
	 */
	public static function marcar_necessita_atencao( $log_id ) {
		global $wpdb;
		$table = $wpdb->prefix . SERVICOS_SYNC_LOG_TABLE;

		$wpdb->update(
			$table,
			array( 'status' => self::STATUS_ATENCAO ),
			array( 'id' => $log_id )
		);
	}

	/**
	 * Devolve registos por status, respeitando o intervalo mínimo entre tentativas (backoff).
	 */
	public static function obter_para_retry( $max_tentativas ) {
		global $wpdb;
		$table = $wpdb->prefix . SERVICOS_SYNC_LOG_TABLE;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE status = %s AND tentativas < %d
				 ORDER BY id ASC
				 LIMIT 50",
				self::STATUS_FALHOU,
				$max_tentativas
			)
		);
	}

	/**
	 * Calcula o próximo momento permitido para retry (backoff progressivo: 5min, 15min, 1h, depois 1h fixo).
	 */
	public static function pode_tentar_agora( $log_row ) {
		if ( empty( $log_row->ultima_tentativa ) ) {
			return true;
		}

		$intervalos = array( 5, 15, 60 ); // minutos
		$indice     = min( (int) $log_row->tentativas - 1, count( $intervalos ) - 1 );
		$indice     = max( $indice, 0 );
		$minutos    = $intervalos[ $indice ];

		$proxima_tentativa = strtotime( $log_row->ultima_tentativa ) + ( $minutos * MINUTE_IN_SECONDS );

		return time() >= $proxima_tentativa;
	}

	/**
	 * Lista os últimos registos para o ecrã de administração.
	 */
	public static function listar_recentes( $limite = 50, $apenas_problemas = false ) {
		global $wpdb;
		$table = $wpdb->prefix . SERVICOS_SYNC_LOG_TABLE;

		if ( $apenas_problemas ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE status IN (%s, %s) ORDER BY id DESC LIMIT %d",
					self::STATUS_FALHOU,
					self::STATUS_ATENCAO,
					$limite
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limite )
		);
	}
}
