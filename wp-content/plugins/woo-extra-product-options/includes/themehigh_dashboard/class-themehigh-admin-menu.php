<?php
/**
 * ThemeHigh Admin Menu System
 *
 * Provides a shared admin menu structure for all ThemeHigh plugins.
 * 
 * @package    woo-extra-product-options
 * @subpackage woo-extra-product-options/includes/themehigh-dashboard
 * @link       https://themehigh.com
 * @since      3.3.4
 */

declare(strict_types=1);

// If this file is called directly, abort.
defined('ABSPATH') || exit;

if ( ! class_exists( 'ThemeHigh_Admin_Menu' ) ) :
final class ThemeHigh_Admin_Menu {

    public const TOP_LEVEL_SLUG = 'themehigh';
    public const DEFAULT_CAPABILITY = 'manage_woocommerce';
    public const NOTIFICATIONS_SLUG = 'themehigh-notifications';
    public const FREE_PLUGINS_SLUG = 'themehigh-free-plugins';
    public const PREMIUM_PLUGINS_SLUG = 'themehigh-premium-plugins';
    public const PREMIUM_PLUGINS_URL = 'https://www.themehigh.com/plugins/';
    public const TEXT_DOMAIN = 'woo-extra-product-options';
    public const CACHE_EXPIRATION = 12 * HOUR_IN_SECONDS;

    /**
     * The single instance of the class.
     */
    private static ?ThemeHigh_Admin_Menu $instance = null;

    /**
     * Array to hold registered plugin submenu details.
     */
    private static array $registered_plugins = [];

    /**
     * Flag to prevent duplicate script inclusion.
     */
    private bool $script_added = false;

    /**
     * Get the singleton instance.
     */
    public static function get_instance(): ThemeHigh_Admin_Menu {
        if (null === self::$instance) {
            self::$instance = new self();
            self::initialize_hooks();
        }
        return self::$instance;
    }

    /**
     * Initialize all WordPress hooks.
     */
    private static function initialize_hooks(): void {
        add_action('admin_menu', [self::$instance, 'add_admin_menus'], 9);
        add_action('admin_menu', [self::$instance, 'modify_submenus'], 100);
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {}

    /**
     * Register a plugin with the shared menu system.
     */
    public static function register_plugin(
        string $menu_name, 
        string $page_title, 
        string $menu_slug, 
        callable $callback, 
        string $capability = self::DEFAULT_CAPABILITY, 
        int $order = 30
    ): void {
        self::get_instance(); // Ensure instance exists

        if (!isset(self::$registered_plugins[$menu_slug])) {
            self::$registered_plugins[$menu_slug] = [
                'menu_name'  => $menu_name,
                'page_title' => $page_title,
                'slug'       => $menu_slug,
                'callback'   => $callback,
                'capability' => $capability ?: self::DEFAULT_CAPABILITY,
                'order'      => $order,
            ];
        }
    }

    /**
     * Add all admin menus.
     */
    public function add_admin_menus(): void {
        if (!current_user_can(self::DEFAULT_CAPABILITY)) {
            return;
        }

        $this->add_top_level_menu();
        $this->add_plugin_submenus();
        $this->add_free_plugins_submenu();
        $this->add_premium_plugins_submenu();

        // Remove the redundant top-level submenu
        remove_submenu_page(self::TOP_LEVEL_SLUG, self::TOP_LEVEL_SLUG);
    }

    /**
     * Add the top-level menu.
     */
    private function add_top_level_menu(): void {
        $svg_icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNyIgaGVpZ2h0PSIxNCIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI0YxRjFGMSIgZD0iTTEwLjQ5NS4zMkguNTRBLjU0NC41NDQgMCAwIDAgMCAuODd2Mi4xNTdjMCAuMzA0LjI0LjU1LjU0LjU1aDkuOTU1Yy4zIDAgLjU0LS4yNDYuNTQtLjU1Vi44NjljMC0uMzA0LS4yNC0uNTUtLjU0LS41NVpNMTYuMTYxIDUuMzczSDQuMTJhLjU0NC41NDQgMCAwIDAtLjU0LjU1VjguMDhjMCAuMzA1LjI0LjU1LjU0LjU1SDE2LjE2Yy4yOTkgMCAuNTQtLjI0NS41NC0uNTVWNS45MjNjMC0uMzA1LS4yNDEtLjU1LS41NC0uNTVaTTE2LjE2MSAxMC40MjRoLTIuNzk4YS41NDQuNTQ0IDAgMCAwLS41NC41NXYyLjE1N2MwIC4zMDQuMjQxLjU1LjU0LjU1aDIuNzk4Yy4zIDAgLjU0LS4yNDYuNTQtLjU1di0yLjE1OGMwLS4zMDQtLjI0LS41NS0uNTQtLjU1Wk0xMC40OTUgMTAuNDI0SDcuNjk3YS41NDQuNTQ0IDAgMCAwLS41NC41NXYyLjE1N2MwIC4zMDQuMjQxLjU1LjU0LjU1aDIuNzk4Yy4zIDAgLjU0LS4yNDYuNTQtLjU1di0yLjE1OGMwLS4zMDQtLjI0LS41NS0uNTQtLjU1Wk0xNi4xNjEuMzJoLTIuNzk4YS41NDQuNTQ0IDAgMCAwLS41NC41NXYyLjE1N2MwIC4zMDQuMjQxLjU1LjU0LjU1aDIuNzk4Yy4zIDAgLjU0LS4yNDYuNTQtLjU1Vi44NjljMC0uMzA0LS4yNC0uNTUtLjU0LS41NVoiLz48L3N2Zz4=';

        $first_plugin = $this->get_first_registered_plugin();
        $callback = $first_plugin['callback'] ?? '__return_empty_string';

        add_menu_page(
            __('Themehigh', 'woo-extra-product-options'),
            __('Themehigh', 'woo-extra-product-options'),
            self::DEFAULT_CAPABILITY,
            self::TOP_LEVEL_SLUG,
            $callback,
            $svg_icon,
            55
        );
    }

    /**
     * Add registered plugin submenus.
     */
    private function add_plugin_submenus(): void {
        if (empty(self::$registered_plugins)) {
            return;
        }

        // Sort plugins by 'order' before rendering
        uasort(self::$registered_plugins, function(array $a, array $b): int {
            return ($a['order'] ?? 10) <=> ($b['order'] ?? 10);
        });

        foreach (self::$registered_plugins as $slug => $details) {
            if (current_user_can($details['capability'])) {
                add_submenu_page(
                    self::TOP_LEVEL_SLUG,
                    $details['page_title'],
                    $details['menu_name'],
                    $details['capability'],
                    $slug,
                    $details['callback']
                );
            }
        }
    }

    /**
     * Get the first registered plugin by order.
     */
    private function get_first_registered_plugin(): ?array {
        if (empty(self::$registered_plugins)) {
            return null;
        }
    
        // Sort by order
        $plugins = self::$registered_plugins;
        uasort($plugins, fn(array $a, array $b): int => ($a['order'] ?? 10) <=> ($b['order'] ?? 10));
    
        return reset($plugins) ?: null;
    }

    /**
     * Add free plugins submenu.
     */
    private function add_free_plugins_submenu(): void {
        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __('Free Plugins', 'woo-extra-product-options'),
            __('Free Plugins', 'woo-extra-product-options'),
            self::DEFAULT_CAPABILITY,
            self::FREE_PLUGINS_SLUG,
            [ThemeHigh_Admin_Free_Plugins_Page::class, 'render']
        );
    }

    /**
     * Add premium plugins submenu.
     */
    private function add_premium_plugins_submenu(): void {
        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __('Premium Plugins', 'woo-extra-product-options'),
            __('Premium Plugins', 'woo-extra-product-options'),
            self::DEFAULT_CAPABILITY,
            self::PREMIUM_PLUGINS_SLUG,
            '__return_empty_string'
        );
    }

    /**
     * Modify submenus for external links.
     */
    public function modify_submenus(): void {
        global $submenu;

        if (!isset($submenu[self::TOP_LEVEL_SLUG])) {
            return;
        }

        foreach ($submenu[self::TOP_LEVEL_SLUG] as $key => $item) {
            if (($item[2] ?? '') === self::PREMIUM_PLUGINS_SLUG) {
                $this->modify_premium_plugins_link($key);
                break;
            }
        }
    }

    /**
     * Modify the premium plugins link.
     */
    private function modify_premium_plugins_link(int $key): void {
        global $submenu;

        $svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="14" fill="#ffb236" xmlns:v="https://vecta.io/nano"><path d="M7.32 10.871H2.404c-.52 0-.788-.207-.932-.727L.056 5.018c-.28-1.025.527-1.886 1.532-1.641l1.986.499c.113.03.173.008.245-.084L6.344.551c.571-.734 1.482-.735 2.051-.003l2.525 3.241c.07.09.127.119.243.088l1.927-.494c.987-.243 1.801.632 1.523 1.638l-1.426 5.123c-.147.526-.407.727-.931.727H7.32h0zm.01 3.128c-1.659 0-3.317.003-4.976-.002-.607-.002-1.002-.575-.801-1.145a.81.81 0 0 1 .761-.555l1.444-.002 8.489-.002c.276 0 .516.068.699.285a.83.83 0 0 1 .136.917c-.145.335-.412.503-.775.503-1.659-.001-3.317 0-4.976 0v-.001z"/></svg> <span></span>';

        $submenu[self::TOP_LEVEL_SLUG][$key][0] = $svg_icon . 
            esc_html__('Premium Plugins', 'woo-extra-product-options') . 
            ' <span class="screen-reader-text">(' . esc_html__('opens in a new tab', 'woo-extra-product-options') . ')</span>';

        $submenu[self::TOP_LEVEL_SLUG][$key][2] = self::PREMIUM_PLUGINS_URL;
        $submenu[self::TOP_LEVEL_SLUG][$key][4] = 'themehigh-premium-link';

        add_action('admin_footer', [$this, 'add_external_link_script']);
    }

    /**
     * Add JavaScript for external link handling.
     */
    public function add_external_link_script(): void {
        if ($this->script_added) {
            return;
        }
        
        $this->script_added = true;
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('a.themehigh-premium-link').attr('target', '_blank').attr('rel', 'noopener noreferrer');
        });
        </script>
        <?php
    }
}
endif;