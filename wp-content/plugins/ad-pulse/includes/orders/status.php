<?php

$exceptions = ['wc-refunded'];

/**
 * Show order status in case it is conditional
 * @param $current_statuses
 * @return mixed
 */
function show_order_status($current_statuses, $order_id = null, $from_change_ajax_call = false): mixed {
    global $exceptions;
    
    $order_id ??= $_GET['id'] ?? null;
    $current_page = isset($_GET['page'])? $_GET['page'] : null;
    $segment = isset($_GET['segment'])? $_GET['segment'] : null;
    $is_correct_page = $current_page == 'wc-orders' || ($segment == 'orders' && $current_page == 'energyplus') || is_ajax();

    if($is_correct_page) {
        #region Check if the call comes from energyplus

        $from_energy_plus = ($segment == 'orders' && $current_page == 'energyplus') || $from_change_ajax_call;

        #endregion

        #region Get "global" variables (all status info and next status prefix)

        $all_status = get_posts(['post_type' => 'order_status', 'posts_per_page' => -1]);
        $default_status = get_option('default_order_status');
        $all_status_meta = [];

        $next_status_prefix = 'next_status_';
        $new_statuses = [];
        $slugs_array = [];

        // Get all status' slugs
        foreach($all_status as $status_key => $status) {
            $all_status_meta[$status_key] = get_post_meta($status->ID);
            $slugs_array[$status_key] = $all_status_meta[$status_key]['status_slug'][0] ?? '';
            $all_status_meta[$status_key]['status_slug'] = $slugs_array[$status_key];
        }

        #endregion

        #region Delete all order statuses which are not custom
        
        if(!empty($slugs_array)) {
            foreach(($current_statuses ?? []) as $curr_key => $curr) {
                $no_wc = str_replace('wc-', '', $curr_key);
                if( is_array($slugs_array) && !in_array($no_wc, $slugs_array) && 
                    is_array($exceptions) && !in_array($curr_key, $exceptions)) {
                    unset($current_statuses[$curr_key]);
                }
            }
        }

        #endregion

        #region Set default status as the first option
        
        $default_slug = 'wc-' . $default_status;
        if ($default_status && in_array($default_slug, array_keys($current_statuses))) {
            $default_title = $current_statuses[$default_slug];

            unset($current_statuses[$default_slug]);
            $current_statuses = array_replace([$default_slug => $default_title], $current_statuses);
        }

        #endregion

        #region Checks if there is an actual order ID and performs the needed changes to display only the conditional status
        if($order_id != null && !(isset($_GET['action']) && $_GET['action'] == 'trash')) {

            $this_order = wc_get_order($order_id);
            if(!$this_order) return $current_statuses;

            $this_data = get_product_data_from_order($this_order);

            $search_pos = array_search($this_order->get_status(), $slugs_array);
            $search_pos = $search_pos !== false? $search_pos : array_search($default_status, $slugs_array);

            $product_id = $this_data['product_id'] ?? 0;
            $next_status_index = $next_status_prefix . $product_id;
            $this_next_status = maybe_unserialize($all_status_meta[$search_pos][$next_status_index][0] ?? null);

            if (is_array($this_next_status) && in_array(-1, $this_next_status)) {
                $next_status_index = $next_status_prefix . '0';
                if($search_pos === false) return $current_statuses;
            }

            if (!$from_energy_plus) {
                $new_statuses['wc-' . $all_status_meta[$search_pos]['status_slug']] = $all_status[$search_pos]->post_title;
            }

            $all_status_names = array_column($all_status, 'post_name');
            $show_all_status = false;

            foreach ((is_array($this_next_status) ? $this_next_status : []) as $status_name) {
                if($status_name == '0') {
                    $show_all_status = true;
                    break;
                } else {
                    $name_pos = array_search($status_name, $all_status_names);
                    if ($name_pos !== false) {
                        $new_statuses['wc-' . $all_status_meta[$name_pos]['status_slug']] = $all_status[$name_pos]->post_title;
                    }
                }
            }

            $for_energyplus_interface = $from_energy_plus && !$from_change_ajax_call;
            if (!$from_change_ajax_call && !$from_energy_plus) {
                foreach($exceptions as $exception)
                    if(isset($current_statuses[$exception]))
                    $new_statuses[$exception] = $current_statuses[$exception];
            }
            
            $current_statuses = $show_all_status || is_null($this_next_status)? $current_statuses : ($for_energyplus_interface? array_keys($new_statuses) : $new_statuses);
            
            if ($this_order->get_status() == 'refunded')
                $current_statuses = $for_energyplus_interface ? [] : ['wc-refunded' => ($current_statuses['wc-refunded'] ?? 'Refunded' ?? 'Refunded')];

            // cycle to check the 'available when not editable' statuses
            if (!is_order_editable($this_order, $current_statuses)) {
                $not_editable_order_options = [];

                foreach((is_array($all_status_meta) ? $all_status_meta : []) as $status_meta) {
                    $status_available_slug = 'available_when_not_editable';

if (
    $status_meta['status_slug'] == $this_order->status || (isset($status_meta[$status_available_slug][0]) && $status_meta[$status_available_slug][0] == '1') ) {
                        $wc_slug = 'wc-' . $status_meta['status_slug'];
                        if (array_key_exists($wc_slug, $current_statuses))
                            $not_editable_order_options[$wc_slug] = $current_statuses[$wc_slug];
                    }
                }

                $current_statuses = $not_editable_order_options;
            }
            
        }
        #endregion
    
    }

    return $current_statuses;
}

/**
 * @param $columns
 * @param $post_type
 * @return mixed
 */
function add_custom_columns($columns): mixed
{
    $columns['conditional'] = 'Conditional Status';
    return $columns;
}

/**
 * @param $column_id
 * @param $post_id
 * @return void
 */
function custom_columns_content($column_id, $post_id ): void
{
    $editOrderURL = get_bloginfo('url') . '/wp-admin/admin.php?page=order_status_page&order_status_id=' . $post_id;
    switch( $column_id ) {
        case 'conditional':
            echo '<a href="' . $editOrderURL . '">Edit Status Conditions</a>';
            break;
    }
}

/**
 * Verificação se é possível alterar o estado da encomenda
 * @param $role User role
 * @return string[] Allowed status to be edited by this user role
 */
function get_editable_status_by_role($role = '', $statuses = []) {
    global $exceptions;
    $all_status = get_posts(['post_type' => 'order_status', 'posts_per_page' => -1]);
    $real_statuses = !empty($statuses)? $statuses : wc_get_order_statuses();
    $allowed_statuses = [];
    
    foreach($all_status as $status_key => $status) {
        $this_status_meta = get_post_meta($status->ID);
        $search = array_search($status->post_title, $real_statuses);
        $real_status_slug = $search !== false ? substr($search, 3) : '';
        if (isset($this_status_meta['allowed_roles'])) {
            $permissions_by_role = maybe_unserialize(current($this_status_meta['allowed_roles']));
            if (is_array($permissions_by_role)) {
                if (in_array($role, $permissions_by_role) || in_array("0", $permissions_by_role)) {
                    $allowed_statuses[] = $real_status_slug;
                }
            }
        }
        else
            $allowed_statuses[] = $real_status_slug;
    }

    foreach ($exceptions as $exception)
        $allowed_statuses[] = substr($exception, 3);

    return $allowed_statuses;
}

function escape_for_jquery($id) {
    // First escape the characters jQuery needs escaped
    $escaped = preg_replace_callback('/[#.=:[\]()>+~]/', function($matches) {
        return '\\\\' . $matches[0]; // double backslash
    }, $id);
    
    return $escaped;
}

function is_order_editable($order = null, $statuses = []) {
    $is_editable = true;
    $screen = get_current_screen();
    $order_id = $_GET['id'] ?? null;
    $allowed_statuses = ["auto-draft", "pending"];

    // check if it is running on the 'Edit Order' screen
        if ((isset($screen) && $screen->id === 'woocommerce_page_wc-orders' && $order_id) || $order) {        $order ??= wc_get_order( $order_id );
        $this_order_status = $order ? $order->get_status() : '';
        $user = wp_get_current_user();
        $allowed_statuses = array_merge($allowed_statuses, get_editable_status_by_role(current($user->roles), $statuses));
        $is_editable = in_array($this_order_status, $allowed_statuses);
    }

    return $is_editable;
}

function custom_disable_order_date_based_on_status() {
    if (!is_order_editable()) {
        // getting the current order status
        $custom_fields = get_order_custom_fields();

        $inputs_to_deactivate = [
            '#order_data [name=order_date]',
            '#order_data [name=order_date_hour]',
            '#order_data [name=order_date_minute]',
            '#customer_user'
        ];

        $elems_to_remove = [
            '.order_data_column .edit_address',
            '#order_line_items .wc-order-edit-line-item',
            '.wc-order-bulk-actions'
        ];

        $file_input_slug = '.inside:has(#attachments_order_files)';

        foreach (($custom_fields ?? []) as $custom_field) {
            $inputs_to_deactivate[] = '#order-custom-' . escape_for_jquery($custom_field['name'] ?? '');
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            debugger;
            $('<?= implode(', ', $inputs_to_deactivate) ?>').prop('disabled', true);
            $('<?= implode(', ', $elems_to_remove) ?>').remove();
            // hide file uploader (it is loaded later so it cant be done as the other cases)
            $('<?= $file_input_slug ?>').addClass('hide-fileuploader');
        });
        </script>
        <?php
    }
}

function get_status_slug_from_post_name($status_post_name) {
    $status_post = get_page_by_path( $status_post_name, OBJECT, 'order_status' );
    if(!$status_post) return null;
    if(!$status_post) return null;
    $status_meta = get_post_meta($status_post->ID);
    return $status_meta['status_slug'][0] ?? null;
}

function get_status_meta($status_slug) {
    $all_statuses = get_posts(['post_type' => 'order_status', 'posts_per_page' => -1]);

    foreach ($all_statuses as $status) {
        $status_meta = get_post_meta($status->ID);

        if ($status_meta['status_slug'][0] == $status_slug) {
            // order status found
            return $status_meta;
        }
    }
}

function on_order_updated_admin($order_id) {
    // sets the payment as null when there is none
    // this is due to the 'unavailable for editing' orders which only have the order status for editing
    if (!isset($_POST['_payment_method']))
        $_POST['_payment_method'] = '';
}

add_filter('wc_order_statuses', 'show_order_status', PHP_INT_MAX, 1);
add_filter('energyplus_order_statuses', 'show_order_status', PHP_INT_MAX, );
add_action('woocommerce_update_order', 'on_order_updated_admin');
add_filter('manage_order_status_posts_columns','add_custom_columns', PHP_INT_MAX, 1);
add_action( 'manage_posts_custom_column','custom_columns_content', 10, 2);
add_action( 'admin_footer', 'custom_disable_order_date_based_on_status');
// endregion