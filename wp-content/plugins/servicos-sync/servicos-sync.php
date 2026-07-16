<?php
/**
 * Plugin Name: Serviços Sync (Emissor)
 * Description: Sincroniza encomendas da Plataforma de Serviços com a área "Serviços" do E-commerce. Envia referência, valor, status e data de criação sempre que uma encomenda é criada ou atualizada.
 * Version: 1.3.0
 * Author: Consultoria WordPress
 * Text Domain: servicos-sync
 * Requires Plugins: woocommerce
 *
 * Este plugin corre no site da PLATAFORMA DE SERVIÇOS (emissor).
 * Não faz nada no e-commerce — esse é o plugin "Serviços Sync (Conta)".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acesso direto não permitido.
}

define( 'SERVICOS_SYNC_VERSION', '1.3.0' );
define( 'SERVICOS_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SERVICOS_SYNC_URL', plugin_dir_url( __FILE__ ) );
define( 'SERVICOS_SYNC_LOG_TABLE', 'servicos_sync_log' );

require_once SERVICOS_SYNC_PATH . 'includes/class-servicos-sync-log.php';
require_once SERVICOS_SYNC_PATH . 'includes/class-servicos-sync-sender.php';
require_once SERVICOS_SYNC_PATH . 'includes/class-servicos-sync-cron.php';
require_once SERVICOS_SYNC_PATH . 'includes/class-servicos-sync-admin.php';

/**
 * Ativação: cria a tabela de log e agenda os crons.
 */
function servicos_sync_activate() {
	Servicos_Sync_Log::criar_tabela();
	Servicos_Sync_Cron::agendar_eventos();
}
register_activation_hook( __FILE__, 'servicos_sync_activate' );

/**
 * Desativação: remove os crons agendados (não apaga dados).
 */
function servicos_sync_deactivate() {
	Servicos_Sync_Cron::limpar_eventos();
}
register_deactivation_hook( __FILE__, 'servicos_sync_deactivate' );

/**
 * Arranque do plugin.
 */
function servicos_sync_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>Serviços Sync:</strong> este plugin requer o WooCommerce ativo.</p></div>';
			}
		);
		return;
	}

	new Servicos_Sync_Sender();
	new Servicos_Sync_Cron();
	new Servicos_Sync_Admin();
}
add_action( 'plugins_loaded', 'servicos_sync_init' );
