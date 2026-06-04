<?php
/**
 * Plugin Name: Ad-pulse Order Management Plugin
 * Plugin URI: mailto:daniel.diogo@ad-pulse.com
 * Description: Order Management Plugin made to easily access and track orders
 * Version: 0.1
 * Author: Adpulse
 * Author URI: https://www.ad-pulse.com/
 **/

/**
 * Código a correr imediatamente após ativação do plugin
 * @return void
 */
function activation_function(): void
{
    create_stores_and_category();
}

/**
 * Verificação das dependências de outros plugins necessárias para o correto
 * @return void
 */
function check_dependencies(): void
{
    $plugin_dependencies = [
        'woocommerce/woocommerce.php',
        'bp-custom-order-status-for-woocommerce/main.php'
    ];

    $sitewide_plugins = get_site_option('active_sitewide_plugins');
    $active_plugins = array_merge(is_array($sitewide_plugins)? array_flip($sitewide_plugins) : [], get_option('active_plugins'));
    $plugins_diff = array_diff($plugin_dependencies, array_intersect($active_plugins, $plugin_dependencies));

    if(!empty($plugins_diff)) {
        $this_plugin = plugin_basename( __FILE__ );
        if(in_array($this_plugin, $active_plugins)) {
            deactivate_plugins($this_plugin);
        }
        $plugins_str = implode(',', $plugins_diff);
        add_action('admin_notices', 'error_msg');
    }
}

function error_msg ($plugins = ''): void
{
    $class = 'notice notice-error';
    $message = __( 'Error: the required plugins are not installed: ') . $plugins;
    printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
}

/**
 * Criação de lojas e da categoria das lojas
 * @return void
 */
function create_stores_and_category(): void
{
    $stores = [
        'ch' => 'Chiado',
        'ae' => 'Estoril',
        'pm' => 'Atelier (Bairro Alto)',
        'onl' => 'Pedido Online'
    ];

    $stores_category = ['slug' => 'stores', 'name' => 'Stores'];

    $category = get_categories($stores_category);
    if(empty($category)) {
        $store_category = wp_insert_category(['cat_name' => $stores_category['slug'], 'category_nicename' => $stores_category['name']]);
    } else {
        $store_category = $category[0]->term_id;
    }

    foreach($stores as $slug => $store_name) {
        $post = get_posts(
            [
                'name' => $slug,
                'cat' => $store_category
            ]
        );
        if (empty($post)) {
            wp_insert_post(
                [
                    'post_name' => $slug,
                    'post_title' => $store_name,
                    'post_category' => [$store_category]
                ]
            );
        }
    }
}

/**
 * Informação do primeiro produto com SKU definido na encomenda
 * @return array
 */
function get_product_data_from_order(WC_Order $order, bool $get_sku = false) {
    $products = $order->get_items();
    $sku = "";

    foreach($products as $product) {
        $this_data = $product->get_data();
        error_log(json_encode($this_data));
        if (is_array($this_data) && !empty($this_data) && isset($this_data['product_id']) && $this_data['product_id'] > 0) {
            $sku = wc_get_product($this_data['product_id'])->get_sku();
        }
        
        if(!empty($sku)) {
            break;
        }
    }

    return $get_sku? $sku : $this_data;
}

/**
 * Código a correr imediatamente após desativação do plugin
 * @return void
 */
function deactivation_function() {
    $aux = true;
}

// region Included scripts

$scripts_to_include = [
    'fileuploader' => [
        'src' => [
            'class.fileuploader.php'
        ]
    ],
    'includes' => [
        'orders' => [
            'general.php',
            'list.php',
            'number.php',
            'status.php',
            'custom_fields.php',
            'attachments.php',
            'report.php',
            'payments.php',
            'alerts.php',
            'mepp_tweaks.php',
            'product_search.php',
            'permissions.php',
        ],
        'tickets' => [
            'permissions.php',
            'general.php'
        ],
        'users' => [
            'add_user_form.php',
            'search.php'
        ],
        'settings.php'
    ]
];

function include_php_scripts($scripts, $base_path) {
    foreach($scripts as $key => $script) {
        if(is_array($script)) {
            include_php_scripts($script, $base_path . '/' . $key);
        } else {
            include($base_path . '/' . $script);
        }
    }
}

$base_path = plugin_dir_path(__FILE__);
$base_path = substr($base_path, 0, -1);
include_php_scripts($scripts_to_include, $base_path);

// endregion

function ad_pulse_assets() {
    $plugin_main_dir = plugin_dir_url(__FILE__);
    wp_register_style('ad-pulse-style', $plugin_main_dir . 'css/styles.css', array(), '1.1', 'all');
    wp_enqueue_style('ad-pulse-style');

    wp_register_style('font-file-uploader', $plugin_main_dir . 'fileuploader/dist/font/font-fileuploader.css', array(), '1.0','all');
    wp_enqueue_style('font-file-uploader');

    wp_register_style('style-file-uploader', $plugin_main_dir . 'fileuploader/dist/jquery.fileuploader.min.css', array(), '1.0', 'all');
    wp_enqueue_style('style-file-uploader');

    wp_register_script('jquery-file-uploader', $plugin_main_dir . 'fileuploader/dist/jquery.fileuploader.min.js', array('jquery'), '1.0', true);
    wp_enqueue_script('jquery-file-uploader');
    
    wp_register_script('ad-pulse-script', $plugin_main_dir . 'js/scripts.js', array('jquery'), '1.4', true);
    wp_enqueue_script('ad-pulse-script');
}

// region Esconder notificações de plugins exceto para admins
function my_hide_notices(){
    if (!is_super_admin()) {
        remove_all_actions( 'user_admin_notices' );
        remove_all_actions( 'admin_notices' );
    }
}
// endregion

// region Remover avisos de avaliação/promoção do WooCommerce
add_action( 'admin_footer', function() {
    echo '<style>
        .wc-rate-link-notice,
        p.wc-rating-notice,
        .wc-rate-us,
        .woocommerce-store-alerts,
        .woocommerce-admin-promo-notices,
        #woocommerce-marketing-modal,
        .woocommerce-message.wc-connect-notice,
        .js-wc-notice-dismiss[data-notice="marketing"] {
            display: none !important;
        }
    </style>';
} );
// endregion

// region ✅ FIX: Limpar hash antes de qualquer script carregar — evita reabertura do painel ao fazer refresh
add_action( 'admin_head', function() {
    echo '<script>
        if (window.location.hash && window.location.hash !== "#-") {
            window.history.replaceState(null, "", window.location.pathname + window.location.search);
        }
    </script>';
}, 1 );
// endregion

// region ✅ FIX: Detetar message=1 no iframe e avisar a página pai para fazer refresh ao fechar o painel
add_action( 'admin_head', function() {
    echo '<script>
        if ( self !== top && window.location.search.indexOf("message=1") > -1 ) {
            if ( window.parent.refreshOnClose !== undefined ) {
                window.parent.refreshOnClose = 1;
            }
        }
    </script>';
}, 2 );
// endregion

// region ✅ FIX: Mostrar loader do painel ao clicar "Mover para o lixo" no iframe
add_action( 'admin_footer', function() {
    echo '<script>
        jQuery(document).ready(function() {
            jQuery("#inbrowser").on("load", function() {
                try {
                    var iframeDoc = document.getElementById("inbrowser").contentDocument;
                    jQuery(iframeDoc).on("click", "a.submitdelete", function() {
                        jQuery("#inbrowser--loading").addClass("d-flex").removeClass("d-none");
                        jQuery("#inbrowser").hide();
                    });
                } catch(e) {}
            });
        });
    </script>';
} );
// endregion

add_action('in_admin_header', 'my_hide_notices', 99);

add_filter('acf/settings/remove_wp_meta_box', '__return_false');

add_action('admin_enqueue_scripts', 'ad_pulse_assets');
add_action('plugins_loaded', 'check_dependencies');

register_activation_hook(__FILE__, 'activation_function');
register_deactivation_hook(__FILE__, 'deactivation_function');

function my_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        return home_url('/wp-admin/admin.php?page=energyplus');
    } else {
        return $redirect_to;
    }
}
add_filter('login_redirect', 'my_login_redirect', 19, 3);

function redirect_non_admin_users() {
    if (!wp_doing_ajax() ) {
        wp_redirect( home_url('/wp-admin/admin.php?page=energyplus') );
        exit;
    }
}
//add_action( 'template_redirect', 'redirect_non_admin_users' );