<?php
/**
 * Plugin Name:       advanced-partial-payment-or-deposit-for-woocommerce-pro
 * Plugin URI:        http://mage-people.com
 * Description:       Pro addon for Advanced Partial Payment or Deposit for WooCommerce. Adds payment plans, min/max deposits, gateway restrictions, auto reminders, reports, and more.
 * Version:           4.0.0
 * Author:            MagePeople Team
 * Author URI:        http://mage-people.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       advanced-partial-payment-or-deposit-for-woocommerce-pro
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'APD_PRO_VERSION', '4.0.0' );
define( 'APD_PRO_PLUGIN_FILE', __FILE__ );
define( 'APD_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APD_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check for free plugin dependency.
 */
function apd_pro_check_dependency() {
    if ( ! defined( 'APD_VERSION' ) ) {
        add_action( 'admin_notices', function () {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'APD Deposit Pro', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?></strong>
                    <?php esc_html_e( 'requires "Advanced Partial Payment or Deposit for WooCommerce" to be installed and active.', 'advanced-partial-payment-or-deposit-for-woocommerce-pro' ); ?>
                </p>
            </div>
            <?php
        } );
        return false;
    }
    return true;
}

/**
 * HPOS & Cart/Checkout Blocks compatibility.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
});

/**
 * Initialize pro addon.
 */
function apd_pro_init() {
    if ( ! apd_pro_check_dependency() ) {
        return;
    }

    if (!defined('MEP_STORE_URL')) {
        define('MEP_STORE_URL', 'https://mage-people.com/');
    }
    define('APD_PRO_ID', 92884);
    define('APD_PRO_NAME', 'Advanced Partial Payment or Deposit for WooCommerce Pro');
    
    if (!class_exists('EDD_SL_Plugin_Updater')) {
        include(dirname(__FILE__) . '/license/EDD_SL_Plugin_Updater.php');
    }
    include(dirname(__FILE__) . '/license/main.php');

    $license_key = trim(get_option('apd_pro_license_key'));
    $edd_updater = new EDD_SL_Plugin_Updater(MEP_STORE_URL, __FILE__, array(
        'version'   => APD_PRO_VERSION,
        'license'   => $license_key,
        'item_name' => APD_PRO_NAME,
        'item_id'   => APD_PRO_ID,
        'author'    => 'Developer',
        'url'       => home_url(),
        'beta'      => false
    ));

    // Load text domain
    load_plugin_textdomain( 'advanced-partial-payment-or-deposit-for-woocommerce-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Pro modules
    require_once APD_PRO_PLUGIN_DIR . 'includes/class-apd-payment-plans.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/class-apd-min-max.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/class-apd-deposit-fees.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/class-apd-gateway-rules.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/class-apd-reminders.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/class-apd-reports.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/class-apd-conditional-rules.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/event/class-apd-event-integration.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/event/class-apd-event-form-builder-integration.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/tour/class-apd-tour-integration.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/ecab/class-apd-ecab-integration.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/ecab/class-apd-ecab-order-list-integration.php';
    require_once APD_PRO_PLUGIN_DIR . 'includes/class-apd-myaccount-fix.php';

    new APD_Payment_Plans();
    new APD_Min_Max();
    new APD_Deposit_Fees();
    new APD_Gateway_Rules();
    new APD_Reminders();
    new APD_Reports();
    new APD_Conditional_Rules();
    new APD_Event_Integration();
    new APD_Event_Form_Builder_Integration();
    new APD_Tour_Integration();
    new APD_Ecab_Integration();
    new APD_Ecab_Order_List_Integration();
    new APD_MyAccount_Fix();

    // Admin
    if ( is_admin() ) {
        require_once APD_PRO_PLUGIN_DIR . 'admin/class-apd-pro-admin.php';
        new APD_Pro_Admin();
    }

    // Frontend
    if ( ! is_admin() || wp_doing_ajax() ) {
        require_once APD_PRO_PLUGIN_DIR . 'public/class-apd-pro-public.php';
        new APD_Pro_Public();
    }

    do_action( 'apd_pro_loaded' );
}
add_action( 'plugins_loaded', 'apd_pro_init', 25 );

