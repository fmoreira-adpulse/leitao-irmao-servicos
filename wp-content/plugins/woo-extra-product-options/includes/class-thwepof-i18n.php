<?php
/**
 * Internationalization and Translation Handler
 * Simple, backward-compatible approach
 *
 * @package THWEPOF
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('THWEPOF_i18n')):

class THWEPOF_i18n {
    
    /**
     * Register a string for translation
     * BACKWARD COMPATIBLE: Works exactly like the old wpml_register_string()
     *
     * @param string $name Unique identifier (can be empty, will be auto-generated)
     * @param string $value The string to register
     * @return bool Success status
     */
    public static function wpml_register_string($name, $value) {
        if (empty($value)) {
            return false;
        }
        
        // Old behavior: If name is empty, generate one with "WEPOF - " prefix
        if (empty($name)) {
            $name = "WEPOF - " . $value;
        }
        
        // Register with WPML (original functionality)
        if (function_exists('icl_register_string')) {
            icl_register_string(WEPOF_Extra_Product_Options::TEXT_DOMAIN, $name, $value);
        }
        
        // NEW: Also register with modern WPML action
        if (has_action('wpml_register_single_string')) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML hook
            do_action('wpml_register_single_string', WEPOF_Extra_Product_Options::TEXT_DOMAIN, $name, $value);
        }
        
        // NEW: Register with Polylang if active
        if (function_exists('pll_register_string')) {
            pll_register_string($name, $value, WEPOF_Extra_Product_Options::TEXT_DOMAIN);
        }
        
        // TranslatePress auto-detects strings, no registration needed
        
        return true;
    }
    
    /**
     * Translate a string
     * Tries WPML, Polylang, TranslatePress, then WordPress .po/.mo files
     *
     * @param string $original_string Original string to translate
     * @param string $name String identifier (same as used in wpml_register_string)
     * @param string $context Optional context (defaults to plugin text domain)
     * @return string Translated string
     */
    public static function translate($original_string, $name, $context = '') {
        if (empty($original_string) || !is_string($original_string)) {
            return $original_string;
        }
        
        $context = !empty($context) ? $context : WEPOF_Extra_Product_Options::TEXT_DOMAIN;
        
        // If name is empty, generate it (same logic as registration)
        if (empty($name)) {
            $name = "WEPOF - " . $original_string;
        }
        
        // Try WPML translation first
        if (self::is_wpml_active()) {
            // Modern WPML filter
            if (has_filter('wpml_translate_single_string')) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML hook
                $translated = apply_filters('wpml_translate_single_string', $original_string, $context, $name);
                if ($translated !== $original_string) {
                    return $translated;
                }
            }
            // Legacy WPML function
            if (function_exists('icl_t')) {
                $translated = icl_t($context, $name, $original_string);
                if ($translated !== $original_string) {
                    return $translated;
                }
            }
        }
        
        // Try Polylang translation
        if (function_exists('pll__')) {
            $translated = pll__($original_string);
            if ($translated !== $original_string) {
                return $translated;
            }
        }
        
        // Try TranslatePress
        if (function_exists('trp_translate')) {
            $translated = trp_translate($original_string, WEPOF_Extra_Product_Options::TEXT_DOMAIN);

            // added wp_strip_all_tags to prevent HTML tags from being translated, which can cause issues in some contexts
            $translated =  wp_strip_all_tags($translated);
            
            if ($translated !== $original_string) {
                return $translated;
            }
        }
        
        // Fallback to WordPress .po/.mo files
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translation required
        $po_mo_translated = __($original_string, 'woo-extra-product-options');
        if ($po_mo_translated !== $original_string) {
            return $po_mo_translated;
        }
        
        // WooCommerce core fallback
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.TextDomainMismatch -- Intentional fallback to WooCommerce translations
        $woocommerce_translated = __($original_string, 'woocommerce');
        if ($woocommerce_translated !== $original_string) {
            return $woocommerce_translated;
        }
        
        // Return original if no translation found
        return $original_string;
    }
    
    /**
     * Unregister a string from translation
     *
     * @param string $name String identifier
     * @return bool Success status
     */
    public static function unregister_string($name) {
        if (empty($name)) {
            return false;
        }
        
        // Unregister from WPML
        if (function_exists('icl_unregister_string')) {
            icl_unregister_string(WEPOF_Extra_Product_Options::TEXT_DOMAIN, $name);
        }
        
        // Unregister from Polylang
        if (function_exists('pll_unregister_string')) {
            pll_unregister_string($name);
        }
        
        return true;
    }
    
    /**
     * Check if WPML is active
     *
     * @return bool
     */
    private static function is_wpml_active() {
        return has_filter('wpml_translate_single_string') || 
               function_exists('icl_t') || 
               defined('ICL_SITEPRESS_VERSION');
    }
}

endif;