<?php
/**
 * ThemeHigh Notifications Page
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

// ThemeHigh Notifications Page - This is for future use
if (!class_exists('ThemeHigh_Notifications_Page')) {
    class ThemeHigh_Notifications_Page {
        public static function render() {
            echo '<div class="wrap"><h1>' . esc_html__('Notifications', 'woo-extra-product-options') . '</h1>';
            echo '</div>';
        }
    }
}