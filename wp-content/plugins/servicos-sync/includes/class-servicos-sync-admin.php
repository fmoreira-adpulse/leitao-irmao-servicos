<?php
/**
 * Página de administração: configuração da ligação ao E-commerce e log de sincronização.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Servicos_Sync_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'adicionar_menu' ) );
		add_action( 'admin_post_servicos_sync_guardar_opcoes', array( $this, 'guardar_opcoes' ) );
		add_action( 'admin_post_servicos_sync_testar_ligacao', array( $this, 'testar_ligacao' ) );
		add_action( 'admin_post_servicos_sync_reenviar', array( $this, 'reenviar_manual' ) );
	}

	public function adicionar_menu() {
		add_menu_page(
			'Serviços Sync',
			'Serviços Sync',
			'manage_woocommerce',
			'servicos-sync',
			array( $this, 'render_pagina' ),
			'dashicons-update',
			56
		);
	}

	public function guardar_opcoes() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'servicos_sync_guardar_opcoes' ) ) {
			wp_die( 'Não autorizado.' );
		}

		$opcoes = array(
			'ecommerce_url'           => isset( $_POST['ecommerce_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['ecommerce_url'] ) ) ) : '',
			'ecommerce_user'          => isset( $_POST['ecommerce_user'] ) ? sanitize_text_field( wp_unslash( $_POST['ecommerce_user'] ) ) : '',
			'ecommerce_app_password'  => isset( $_POST['ecommerce_app_password'] ) ? sanitize_text_field( wp_unslash( $_POST['ecommerce_app_password'] ) ) : '',
		);

		update_option( 'servicos_sync_opcoes', $opcoes );

		wp_safe_redirect( add_query_arg( array( 'page' => 'servicos-sync', 'guardado' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function testar_ligacao() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'servicos_sync_testar_ligacao' ) ) {
			wp_die( 'Não autorizado.' );
		}

		$opcoes   = get_option( 'servicos_sync_opcoes', array() );
		$url      = isset( $opcoes['ecommerce_url'] ) ? trailingslashit( $opcoes['ecommerce_url'] ) . 'wp-json/servicos-sync/v1/list' : '';
		$user     = isset( $opcoes['ecommerce_user'] ) ? $opcoes['ecommerce_user'] : '';
		$app_pass = isset( $opcoes['ecommerce_app_password'] ) ? $opcoes['ecommerce_app_password'] : '';

		$resultado = 'erro';

		if ( $url && $user && $app_pass ) {
			$resposta = wp_remote_get(
				add_query_arg( 'since', gmdate( 'c', strtotime( '-1 day' ) ), $url ),
				array(
					'timeout' => 15,
					'headers' => array( 'Authorization' => 'Basic ' . base64_encode( $user . ':' . $app_pass ) ),
				)
			);

			if ( ! is_wp_error( $resposta ) && 200 === wp_remote_retrieve_response_code( $resposta ) ) {
				$resultado = 'sucesso';
			}
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'servicos-sync', 'teste' => $resultado ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function reenviar_manual() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'servicos_sync_reenviar' ) ) {
			wp_die( 'Não autorizado.' );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

		if ( $log_id ) {
			global $wpdb;
			$table  = $wpdb->prefix . SERVICOS_SYNC_LOG_TABLE;
			$registo = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $log_id ) );

			if ( $registo ) {
				$payload = json_decode( $registo->payload, true );
				if ( $payload ) {
					Servicos_Sync_Sender::enviar( $registo->id, $payload );
				}
			}
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'servicos-sync', 'reenviado' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render_pagina() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$opcoes   = get_option( 'servicos_sync_opcoes', array() );
		$registos = Servicos_Sync_Log::listar_recentes( 50 );
		?>
		<div class="wrap">
			<h1>Serviços Sync — Configuração</h1>

			<?php if ( isset( $_GET['guardado'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Configuração guardada.</p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['teste'] ) ) : ?>
				<?php if ( 'sucesso' === $_GET['teste'] ) : ?>
					<div class="notice notice-success is-dismissible"><p>Ligação ao E-commerce estabelecida com sucesso.</p></div>
				<?php else : ?>
					<div class="notice notice-error is-dismissible"><p>Não foi possível ligar ao E-commerce. Verifique o URL, utilizador e Application Password.</p></div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( isset( $_GET['reenviado'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Reenvio solicitado.</p></div>
			<?php endif; ?>

			<h2>Ligação ao E-commerce</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'servicos_sync_guardar_opcoes' ); ?>
				<input type="hidden" name="action" value="servicos_sync_guardar_opcoes">
				<table class="form-table">
					<tr>
						<th><label for="ecommerce_url">URL do E-commerce</label></th>
						<td><input type="url" required name="ecommerce_url" id="ecommerce_url" class="regular-text" placeholder="https://www.exemplo.com" value="<?php echo esc_attr( $opcoes['ecommerce_url'] ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th><label for="ecommerce_user">Utilizador de serviço</label></th>
						<td><input type="text" required name="ecommerce_user" id="ecommerce_user" class="regular-text" value="<?php echo esc_attr( $opcoes['ecommerce_user'] ?? '' ); ?>">
						<p class="description">Utilizador dedicado no E-commerce, com role sem acesso ao wp-admin (ver README).</p></td>
					</tr>
					<tr>
						<th><label for="ecommerce_app_password">Application Password</label></th>
						<td><input type="text" required name="ecommerce_app_password" id="ecommerce_app_password" class="regular-text" value="<?php echo esc_attr( $opcoes['ecommerce_app_password'] ?? '' ); ?>">
						<p class="description">Gerada no perfil desse utilizador, no E-commerce, em "Application Passwords".</p></td>
					</tr>
				</table>
				<?php submit_button( 'Guardar configuração' ); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:2em;">
				<?php wp_nonce_field( 'servicos_sync_testar_ligacao' ); ?>
				<input type="hidden" name="action" value="servicos_sync_testar_ligacao">
				<?php submit_button( 'Testar ligação', 'secondary' ); ?>
			</form>

			<h2>Registo de sincronização (últimos 50)</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Referência</th>
						<th>Email cliente</th>
						<th>Status</th>
						<th>Tentativas</th>
						<th>Última tentativa</th>
						<th>Detalhe</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $registos ) ) : ?>
					<tr><td colspan="7">Ainda sem registos.</td></tr>
				<?php else : ?>
					<?php foreach ( $registos as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r->order_ref ); ?></td>
							<td><?php echo esc_html( $r->customer_email ); ?></td>
							<td><?php echo esc_html( self::etiqueta_status( $r->status ) ); ?></td>
							<td><?php echo esc_html( $r->tentativas ); ?></td>
							<td><?php echo esc_html( $r->ultima_tentativa ); ?></td>
							<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr( $r->resposta_http ); ?>"><?php echo esc_html( wp_trim_words( (string) $r->resposta_http, 8 ) ); ?></td>
							<td>
								<?php if ( in_array( $r->status, array( 'failed', 'needs_attention' ), true ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'servicos_sync_reenviar' ); ?>
									<input type="hidden" name="action" value="servicos_sync_reenviar">
									<input type="hidden" name="log_id" value="<?php echo esc_attr( $r->id ); ?>">
									<button type="submit" class="button button-small">Reenviar agora</button>
								</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function etiqueta_status( $status ) {
		$mapa = array(
			'pending'          => 'Pendente',
			'success'          => '✅ Sincronizado',
			'failed'           => '⚠️ Falhou (em retry)',
			'needs_attention'  => '🔴 Necessita atenção',
		);
		return $mapa[ $status ] ?? $status;
	}
}
