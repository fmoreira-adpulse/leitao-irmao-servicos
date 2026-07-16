<?php
/**
 * Corre apenas quando o plugin é APAGADO (não na simples desativação).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$manter_dados = get_option( 'servicos_sync_manter_dados_ao_desinstalar', '1' );

if ( '1' !== $manter_dados ) {
	$table = $wpdb->prefix . 'servicos_sync_log';
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	delete_option( 'servicos_sync_opcoes' );
}
