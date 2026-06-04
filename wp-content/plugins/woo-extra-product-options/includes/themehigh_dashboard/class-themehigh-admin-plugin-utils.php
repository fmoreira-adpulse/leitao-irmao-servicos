<?php 
/**
 * ThemeHigh Plugin Utilities
 *
 * @package    woo-extra-product-options
 * @subpackage woo-extra-product-options/includes/themehigh-dashboard
 * @link       https://themehigh.com
 * @since      3.3.4
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('ThemeHigh_Admin_Plugin_Utils')) {
    class ThemeHigh_Admin_Plugin_Utils {
        
        /**
         * Check if a plugin is installed by slug
         *
         * @param string $slug The plugin slug to check
         * @return string|false Plugin file path if installed, false otherwise
         */
        public static function is_plugin_installed($slug) {
            // Validate input
            if (empty($slug) || !is_string($slug)) {
                return false;
            }
            
            // Ensure get_plugins function is available
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            // Get all installed plugins
            $all_plugins = get_plugins();
            
            if (empty($all_plugins) || !is_array($all_plugins)) {
                return false;
            }
            
            // Search for the plugin by slug
            foreach ($all_plugins as $plugin_file => $plugin_data) {
                $plugin_dir = dirname($plugin_file);
                
                // Check if the plugin directory matches the slug
                if ($plugin_dir === $slug) {
                    return $plugin_file;
                }
                
                // Check if the plugin file starts with the slug
                if (strpos($plugin_file, $slug . '/') === 0) {
                    return $plugin_file;
                }
                
                // Check for single-file plugins (plugin-name.php)
                if ($plugin_file === $slug . '.php') {
                    return $plugin_file;
                }
            }
            
            return false;
        }
        
        /**
         * Check if a plugin is active
         *
         * @param string $plugin_file The plugin file path
         * @return bool True if active, false otherwise
         */
        public static function is_plugin_active($plugin_file) {
            if (empty($plugin_file)) {
                return false;
            }
            
            return is_plugin_active($plugin_file);
        }
        
        /**
         * Get plugin data by slug
         *
         * @param string $slug The plugin slug
         * @return array|false Plugin data array or false if not found
         */
        public static function get_plugin_data($slug) {
            $plugin_file = self::is_plugin_installed($slug);
            
            if (!$plugin_file) {
                return false;
            }
            
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            $all_plugins = get_plugins();
            
            return isset($all_plugins[$plugin_file]) ? $all_plugins[$plugin_file] : false;
        }
        
        /**
         * Get plugin status (not installed, installed, active)
         *
         * @param string $slug The plugin slug
         * @return string Status: 'not_installed', 'installed', 'active'
         */
        public static function get_plugin_status($slug) {
            $plugin_file = self::is_plugin_installed($slug);
            
            if (!$plugin_file) {
                return 'not_installed';
            }
            
            if (self::is_plugin_active($plugin_file)) {
                return 'active';
            }
            
            return 'installed';
        }
        
        /**
         * Get activation URL for a plugin
         *
         * @param string $plugin_file The plugin file path
         * @return string|false Activation URL or false on failure
         */
        public static function get_activation_url($plugin_file) {
            if (empty($plugin_file)) {
                return false;
            }
            
            // Check if plugin is already active
            if (self::is_plugin_active($plugin_file)) {
                return false;
            }
            
            return wp_nonce_url(
                self_admin_url('plugins.php?action=activate&plugin=' . urlencode($plugin_file)),
                'activate-plugin_' . $plugin_file
            );
        }
        
        /**
         * Get deactivation URL for a plugin
         *
         * @param string $plugin_file The plugin file path
         * @return string|false Deactivation URL or false on failure
         */
        public static function get_deactivation_url($plugin_file) {
            if (empty($plugin_file)) {
                return false;
            }
            
            // Check if plugin is active
            if (!self::is_plugin_active($plugin_file)) {
                return false;
            }
            
            return wp_nonce_url(
                self_admin_url('plugins.php?action=deactivate&plugin=' . urlencode($plugin_file)),
                'deactivate-plugin_' . $plugin_file
            );
        }
        
        /**
         * Check if a plugin slug is valid format
         *
         * @param string $slug The plugin slug to validate
         * @return bool True if valid format, false otherwise
         */
        public static function is_valid_slug($slug) {
            if (empty($slug) || !is_string($slug)) {
                return false;
            }
            
            // Basic slug validation (alphanumeric, hyphens, underscores)
            return preg_match('/^[a-zA-Z0-9_-]+$/', $slug) === 1;
        }
        
        /**
         * Get all installed ThemeHigh plugins
         *
         * @return array Array of ThemeHigh plugin files
         */
        public static function get_themehigh_plugins() {
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            $all_plugins = get_plugins();
            $themehigh_plugins = array();
            
            foreach ($all_plugins as $plugin_file => $plugin_data) {
                // Check if it's a ThemeHigh plugin by author or plugin URI
                $author = isset($plugin_data['Author']) ? strtolower($plugin_data['Author']) : '';
                $plugin_uri = isset($plugin_data['PluginURI']) ? strtolower($plugin_data['PluginURI']) : '';
                
                if (strpos($author, 'themehigh') !== false || 
                    strpos($plugin_uri, 'themehigh') !== false) {
                    $themehigh_plugins[$plugin_file] = $plugin_data;
                }
            }
            
            return $themehigh_plugins;
        }
    }
}