<?php

/**
 * EnergyPlus Orders
 *
 * Order management
 *
 * @since      1.0.0
 * @package    EnergyPlus
 * @subpackage EnergyPlus/framework
 * @author     EN.ER.GY <support@en.er.gy>
 * */


if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}


class EnergyPlus_Orders extends EnergyPlus
{

	public static function run()
	{
		EnergyPlus::wc_engine();
		wp_enqueue_script("energyplus-orders",  EnergyPlus_Public . "js/energyplus-orders.js", array(), EnergyPlus_Version);
		self::route();
	}

	private static function route()
	{
		switch (EnergyPlus_Helpers::get('action')) {
			default:
				self::index();
				break;
		}
	}

	public static function filter($filter)
	{
		if (!$filter) {
			$filter['page']        = 1;
			$filter['limit']       = absint(EnergyPlus::option('reactors-tweaks-pg-orders', 10));
			$filter['post_status'] = array_keys(wc_get_order_statuses());
		}

		$filter['product']  = EnergyPlus_Helpers::get('product', null);
		$filter['priority'] = EnergyPlus_Helpers::get('priority', null);

		if ($status = EnergyPlus_Helpers::get('status', null)) {
			if ('trash' === $status) {
				$filter['post_status'] = 'trash';
			} else {
				if (in_array('wc-' . $status, array_keys(wc_get_order_statuses()))) {
					$filter['post_status'] = "wc-" . $status;
				}
				if (in_array($status, array_keys(wc_get_order_statuses()))) {
					$filter['post_status'] = $status;
				}
			}
		}

		if (EnergyPlus_Helpers::get('s', null)) {
			$filter['search'] = sanitize_text_field(EnergyPlus_Helpers::get('s', ''));
		}

		if (EnergyPlus_Helpers::get('go', null)) {
			$filter['mode'] = 95;
		}

		if (EnergyPlus_Helpers::get('pg', null)) {
			$filter['page'] = intval(EnergyPlus_Helpers::get('pg', 0));
		}

		if ($customer = EnergyPlus_Helpers::get('customer')) {
			$filter['meta_query'] = array(
				array(
					'key'     => '_customer_user',
					'value'   => absint($customer),
					'compare' => '=',
				)
			);
		}

		if (EnergyPlus_Helpers::get('orderby')) {
			if (false !== strpos(EnergyPlus_Helpers::get('orderby', ''), 'meta_')) {
				$filter['orderby']  = "meta_value_num";
				$filter['meta_key'] = sanitize_sql_orderby(str_replace('meta_', '', EnergyPlus_Helpers::get('orderby', '')));
			} else {
				$filter['orderby'] = sanitize_sql_orderby(EnergyPlus_Helpers::get('orderby', ''));
			}
			$filter['order'] = 'ASC' === EnergyPlus_Helpers::get('order', 'ASC') ? 'ASC' : 'DESC';
		}

		return $filter;
	}

	public static function index($filter = array())
	{
		$filter = self::filter($filter);

		$list = array();
		$list['statuses'] = wc_get_order_statuses();

		$total = 0;
		foreach ($list['statuses'] as $list_status_k => $list_status_k) {
			$list['statuses_count'][$list_status_k] = wc_orders_count(str_replace('wc-', '', $list_status_k));
			$total += $list['statuses_count'][$list_status_k];
		}

		$list['statuses_count']["any"]   = $total;
		$list['statuses_count']['trash'] = wp_count_posts('shop_order')->trash;

		$prods = array_map(function ($elem) {
			$data = $elem->get_data();
			if (!empty($data['sku']))
				return ['sku' => $data['sku'], 'name' => $data['name']];
		}, wc_get_products([]));

		$list['products']  = array_column($prods, 'name', 'sku');
		$orders['orders']  = self::get_orders($filter['post_status'], $filter, $filter['page'])['result'];

		switch ($mode = (!empty($filter['mode']) ? absint($filter['mode']) : EnergyPlus::option('mode-energyplus-orders', 1))) {
			case 1:
			case 98:
				$orders['orders'] = self::_group_by($orders['orders'], 'created_at');
				echo EnergyPlus_View::run('orders/list-' . $mode, array('orders' => $orders['orders'], 'list' => $list, 'ajax' => EnergyPlus_Helpers::is_ajax()));
				break;
			case 2:
				echo EnergyPlus_View::run('orders/list-2', array('orders' => array('all' => array('orders' => $orders['orders'])), 'list' => $list, 'ajax' => EnergyPlus_Helpers::is_ajax()));
				break;
			case 97:
				return EnergyPlus_View::run('orders/list-2', array('orders' => array('all' => array('orders' => $orders['orders'])), 'list' => $list, 'ajax' => 1));
				break;
			case 99:
				if (!EnergyPlus_Admin::is_full()) {
					EnergyPlus_Helpers::frame(admin_url('edit.php?post_type=shop_order'));
				} else {
					wp_redirect(admin_url('edit.php?post_type=shop_order'));
				}
				break;
			case 95:
				echo EnergyPlus_View::run('orders/list-95', array('list' => $list, 'iframe_url' => EnergyPlus_Helpers::get_submenu_url(EnergyPlus_Helpers::get('go'))));
				break;
		}
	}

	private static function _group_by($array, $key)
	{
		$return = array();
		foreach ($array as $val) {
			$time = EnergyPlus_Helpers::grouped_time($val['date_created']);
			$return[$time['key']]['title']    = $time['title'];
			$return[$time['key']]['orders'][] = $val;
		}
		return $return;
	}

	public static function ajax()
	{
		global $woocommerce;
		EnergyPlus::wc_engine();

		$do = EnergyPlus_Helpers::post('do', 'default');

		if ('search' != $do) {
			EnergyPlus_Helpers::ajax_nonce(TRUE);
		}

		switch ($do) {

			case "filter":
				$fields = $_POST['fields'];
				$filter = array();

				foreach ($fields as $key => $field) {
					$field['value'] = sanitize_text_field($field['value']);

					if ('order_id' === $field['name'] && trim($field['value']) !== '') {
						$filter['post__in'] = array(EnergyPlus_Helpers::clean($field['value']));
					}
					if ('status' === $field['name'] && trim($field['value']) !== '') {
						if (in_array($field['value'], array('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed'))) {
							$filter['post_status'] = "wc-" . EnergyPlus_Helpers::clean($field['value']);
						}
					}
					if ('customer' === $field['name'] && trim($field['value']) !== '') {
						$filter['meta_query'] = array(
							array(
								'key'     => '_customer_user',
								'value'   => absint(EnergyPlus_Helpers::clean($field['value'])),
								'compare' => '=',
							),
						);
					}
				}

				echo self::index($filter);
				wp_die();
				break;

			case 'search':
				$filter = EnergyPlus_Helpers::post('extra', []);
				if (!is_array($filter)) {
					$filter = [];
				}
				$filter['search'] = EnergyPlus_Helpers::post('q', '');
				$filter['mode']   = (EnergyPlus_Helpers::post('mode') ? absint(EnergyPlus_Helpers::post('mode')) : null);
				$filter['page']   = 1;

				echo self::index($filter);
				wp_die();
				break;

			case 'deleteforever':
			case 'restore':
				$id    = absint(EnergyPlus_Helpers::post('id', 0));
				$order = new WC_Order($id);

				if (!$order) {
					EnergyPlus_Ajax::error(esc_html__('Error', 'energyplus'));
					wp_die();
				}

				if ('deleteforever' === $do) {
					$change = wp_delete_post($id, true);
				} else {
					$change = wp_untrash_post($id);
				}

				if (!$change) {
					EnergyPlus_Ajax::error(esc_html__('Error', 'energyplus'));
				} else {
					EnergyPlus_Ajax::success('OK', array('id' => $id, 'message' => esc_html__('Done', 'energyplus')));
				}
				break;

			case 'changestatus':
				$result = array();
				$status = EnergyPlus_Helpers::post('status');
				$ids    = wp_parse_id_list(EnergyPlus_Helpers::post('id', array()));

				if (!is_array($ids)) {
					wp_die(-1);
				}

				if (!in_array("wc-" . $status, array_keys(wc_get_order_statuses())) && !in_array($status, array_keys(wc_get_order_statuses())) && !in_array($status, array('all', 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed', 'trash', 'restore', 'deleteforever'))) {
					wp_die(-2);
				}

				$ids = array_map('absint', $ids);

				foreach ($ids as $id) {
					$change = false;
					$order  = new WC_Order(absint($id));

					if ($order) {
						if ('trash' === $status) {
							$change = wp_trash_post($id);
						} else if ('deleteforever' === $status) {
							$change = wp_delete_post($id, true);
						} else if ('restore' === $status) {
							$change = wp_untrash_post($id);
						} else {
							$change = $order->update_status($status);
							do_action('woocommerce_update_order', $order->get_id(), $order);
						}

						wc_delete_shop_order_transients(absint($id));
						EnergyPlus_Events::save_post_shop_order($id);
					}

					if ($change) {
						$result['success'][] = $id;
					} else {
						$result['errors'][] = $id;
					}
				}

				$order_statuses = show_order_status(wc_get_order_statuses(), $id, true);
				$has_input      = false;
				return EnergyPlus_Ajax::success('Order status has been changed', $result, false, $order_statuses, $has_input);
				break;
		}
	}

	private static function get_orders($type, $filter = array(), $page = 0)
	{
		$count  = 0;
		$orders = array();

		// =====================================================================
		// Permissões: roles com acesso a todos os estados rápidos
		// =====================================================================
		$user        = wp_get_current_user();
		$is_admin    = in_array('administrator', (array) $user->roles);

		// =====================================================================
		// PESQUISA — usa wc_order_search + filtro energyplus_order_search
		// que pesquisa por _order_number, _billing_nif e _billing_sage
		// =====================================================================
		if (!empty($filter['search']) && (2 < strlen($filter['search']) || 0 === strlen($filter['search']))) {

			$search = sanitize_text_field($filter['search']);

			$results = apply_filters(
				'energyplus_order_search',
				wc_order_search($search),
				$search
			);

			if (0 < count($results)) {
				$results = array_reverse($results);
				$results = array_slice($results, 0, 100);

				foreach ($results as $order_id) {
					$order = wc_get_order($order_id);
					if (!$order || !method_exists($order, 'get_formatted_billing_address')) continue;
					if (!$order instanceof WC_Order) continue;
					if ($order->get_parent_id() > 0) continue;
					if ($order->get_meta('_lpf_mini_order')) continue;

					// Status filter
					if (!empty($filter['post_status']) && ('wc-' . $order->get_status() !== $filter['post_status'])) continue;

					// Product filter
					if (!is_null($filter['product']) && function_exists('get_product_data_from_order')) {
						if (get_product_data_from_order($order, true) != $filter['product']) continue;
					}

					// Priority filter
					if ($filter['priority'] != null) {
						$priority        = $order->get_meta('_order_custom_priority', true) == "1";
						$priority_filter = $filter['priority'] == "1";
						if ($priority != $priority_filter) continue;
					}

					$data                       = $order->get_data();
					$data['std']                = $order;
					$data['billing_formatted']  = $order->get_formatted_billing_address();
					$data['shipping_formatted'] = $order->get_formatted_shipping_address();
					$data['total_refunded']     = $order->get_total_refunded();

					$meta                   = array_column($order->get_meta_data(), 'value', 'key');
					$data['has_priority']   = isset($meta['_order_custom_priority']) && $meta['_order_custom_priority'] == "1";
					$data['next_statuses']  = self::get_next_statuses($data['status'], $is_admin);

					$orders[strtotime($data['date_created'])] = $data;
				}
				krsort($orders);
			}

			$count = count($results);

		} else {

			// =====================================================================
			// LISTAGEM NORMAL — sem pesquisa
			// =====================================================================
			$allowed_order_statuses = array_keys(wc_get_order_statuses());

			$query_args = array(
				'post_status'    => $allowed_order_statuses,
				'posts_per_page' => absint(EnergyPlus::option('reactors-tweaks-pg-orders', 20)),
				'paged'          => $page,
				'orderby'        => 'ID',
				'order'          => 'DESC',
			);

			if (isset($filter['post_status'])) {
				$query_args['post_status'] = $type;
			}

			if (isset($filter['post__in'])) {
				$query_args['post__in'] = $filter['post__in'];
			}

			$query_args                   = array_merge($query_args, $filter);
			$query_args['posts_per_page'] = absint(EnergyPlus::option('reactors-tweaks-pg-orders', 20));

			// Excluir sub-encomendas (child orders do sistema antigo) e mini-orders do LPF
			$query_args['parent'] = 0;
			$query_args['meta_query'][] = [
				'key'     => '_lpf_mini_order',
				'compare' => 'NOT EXISTS',
			];

			$query     = new WC_Order_Query($query_args);
			$allorders = $query->get_orders();

			$count_args                   = $query_args;
			$count_args['posts_per_page'] = -1;
			unset($count_args['fields']);
			$count_query = new WC_Order_Query($count_args);
			$total_count = count($count_query->get_orders());

			foreach ($allorders as $ord) {
				$order = wc_get_order($ord->id);

				if (is_wp_error($order) || !$order instanceof WC_Order) continue;

				// Product filter
				if (!is_null($filter['product']) && function_exists('get_product_data_from_order')) {
					if (get_product_data_from_order($order, true) != $filter['product']) continue;
				}

				// Priority filter
				if ($filter['priority'] != null) {
					$priority        = $order->get_meta('_order_custom_priority', true) == "1";
					$priority_filter = $filter['priority'] == "1";
					if ($priority != $priority_filter) continue;
				}

				$data                       = $order->get_data();
				$data['std']                = $order;
				$data['billing_formatted']  = $order->get_formatted_billing_address();
				$data['shipping_formatted'] = $order->get_formatted_shipping_address();
				$data['total_refunded']     = $order->get_total_refunded();

				$meta                 = array_column($order->get_meta_data(), 'value', 'key');
				$data['has_priority'] = isset($meta['_order_custom_priority']) && $meta['_order_custom_priority'] == "1";
				$data['next_statuses'] = self::get_next_statuses($data['status'], $is_admin);

				$orders[] = $data;
			}

			$count = $total_count;
		}

		return array(
			'count'  => $count,
			'result' => $orders,
		);
	}

	/**
	 * Devolve os estados rápidos disponíveis conforme o role do utilizador.
	 * Admins: todos os estados configurados.
	 * Outros: apenas STOP (wc-stop-loja).
	 */
	private static function get_next_statuses($order_status, $is_admin)
	{
		if ($is_admin) {
			$cond = EnergyPlus::option('reactors-tweaks-order-cond', array_keys(wc_get_order_statuses()));
			$next = isset($cond['wc-' . $order_status])
				? $cond['wc-' . $order_status]
				: array_keys(wc_get_order_statuses());
		} else {
			$next = array_key_exists('wc-stop-loja', wc_get_order_statuses())
				? ['wc-stop-loja']
				: [];
		}

		return apply_filters('energyplus_order_statuses', $next, $order_status);
	}
}