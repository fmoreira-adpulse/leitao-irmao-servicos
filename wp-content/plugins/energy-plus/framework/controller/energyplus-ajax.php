<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class EnergyPlus_Ajax extends EnergyPlus {

  public static function run() {

    $segment = EnergyPlus_Helpers::post('segment', false);

    if (!$segment) {
      $segment = EnergyPlus_Helpers::get('segment', false);
    }

    // ✅ Segurança corrigida
    if ( ! isset($_REQUEST['_asnonce']) || ! wp_verify_nonce( $_REQUEST['_asnonce'], 'energyplus-segment--' . $segment ) ) {
        wp_send_json_error('Security check failed');
    }

    if (!$segment) {
        error_log('EnergyPlus AJAX: segment vazio');
        wp_send_json_error('Missing segment');
    }

    switch ($segment) {

      case 'search':
        EnergyPlus_Events::search();
      break;

      case 'lists':
        EnergyPlus_Events::lists();
      break;

      case 'orders':
        EnergyPlus_Orders::ajax();
      break;

      case 'customers':
        EnergyPlus_Customers::ajax();
      break;

      case 'coupons':
        EnergyPlus_Coupons::ajax();
      break;

      case 'products':
        EnergyPlus_Products::ajax();
      break;

      case 'comments':
        EnergyPlus_Comments::ajax();
      break;

      case 'settings':
        EnergyPlus_Settings::ajax();
      break;

      case 'reports':
        EnergyPlus_Reports::ajax();
      break;

      case 'dashboard':
        EnergyPlus_Dashboard::ajax();
      break;

      case 'notifications':
        EnergyPlus_Events::notifications();
      break;

      default:
        wp_send_json_error('Invalid segment: ' . $segment);
      break;
    }
  }

  public static function error($error) {
    wp_send_json(array(
      'status' => 0,
      'error'  => esc_html($error)
    ));
  }

  public static function success($message = '', $details = array(), $raw = false, $new_statuses = []) {
    wp_send_json(array_merge(array(
      'status' => 1,
      'message' => $message,
      'new_statuses' => $new_statuses
    ), $details));
  }
}