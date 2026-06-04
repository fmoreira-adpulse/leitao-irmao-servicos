<?php
/**
 * ThemeHigh Free Plugins Page
 *
 * @package    woo-extra-product-options
 * @subpackage woo-extra-product-options/includes/themehigh-dashboard
 * @link       https://themehigh.com
 * @since      3.3.4
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('ThemeHigh_Admin_Free_Plugins_Page')) {
    class ThemeHigh_Admin_Free_Plugins_Page {
        
        const TEXT_DOMAIN = 'woo-extra-product-options';
        const CACHE_EXPIRATION = 12 * HOUR_IN_SECONDS;
        
        /**
         * Render the plugins page
         */
        public static function render() {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Discover Free ThemeHigh Plugins', 'woo-extra-product-options') . '</h1>';
            
            $plugins = self::get_themehigh_plugins_from_wp_org();
            
            if (empty($plugins)) {
                echo '<div class="notice notice-info"><p>' . 
                    esc_html__('No free ThemeHigh plugins found or there was an error fetching them.', 'woo-extra-product-options') . 
                    '</p></div>';
                echo '</div>';
                return;
            }

            // Load necessary WordPress core scripts
            wp_enqueue_script('plugin-install');
            wp_enqueue_script('updates');
            add_thickbox();
            
            self::render_modal();
            self::render_plugins_grid($plugins);
            
            echo '</div>';
            self::add_plugin_management_js();
        }
        
        /**
         * Render the modal for plugin details
         */
        private static function render_modal() {
            ?>
            <div id="themehigh-plugin-modal" style="display:none;">
                <div class="themehigh-plugin-modal-content">
                    <div class="themehigh-plugin-modal-header">
                        <h2 class="plugin-name"></h2>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="themehigh-plugin-modal-body">
                        <div class="plugin-screenshot"></div>
                        <div class="plugin-info">
                            <div class="plugin-description"></div>
                            <div class="plugin-meta">
                                <div class="plugin-version"></div>
                                <div class="plugin-active-installs"></div>
                                <div class="plugin-rating"></div>
                                <div class="plugin-author"></div>
                            </div>
                        </div>
                    </div>
                    <div class="themehigh-plugin-modal-footer">
                        <a href="#" class="button wordpress-link" target="_blank">
                            <?php echo esc_html__('View on WordPress.org', 'woo-extra-product-options'); ?>
                        </a>
                        <div class="plugin-action-buttons">
                            <!-- Dynamic buttons will be inserted here -->
                        </div>
                        <button class="button close-modal">
                            <?php echo esc_html__('Close', 'woo-extra-product-options'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
        
        /**
         * Render the plugins grid
         */
        private static function render_plugins_grid($plugins) {
            echo '<div class="th-plugins-wrapper featured">';
            
            foreach ($plugins as $plugin) {
                $plugin = (object) $plugin;
                self::render_single_plugin($plugin);
            }
            
            echo '</div>';
        }
        
        /**
         * Render a single plugin card
         */
        private static function render_single_plugin($plugin) {
            $plugin_file = self::is_plugin_installed($plugin->slug);
            $plugin_installed = !empty($plugin_file);
            $plugin_active = $plugin_installed && is_plugin_active($plugin_file);
            
            // Generate WordPress standard activation URL
            $activation_url = '';
            if ($plugin_installed && !$plugin_active) {
                $activation_url = wp_nonce_url(
                    self_admin_url('plugins.php?action=activate&plugin=' . urlencode($plugin_file)),
                    'activate-plugin_' . $plugin_file
                );
            }
            ?>
            <div class="th-plugins-child">
                <div class="th-plugins-top">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <?php if (!empty($plugin->icons['1x'])) : ?>
                            <img src="<?php echo esc_url($plugin->icons['1x']); ?>" 
                                 alt="<?php echo esc_attr($plugin->name); ?>" 
                                 style="width: 64px; height: 64px; margin-right: 15px; border-radius: 4px;">
                        <?php endif; ?>
                        <h3 style="margin: 0; font-size: 1.2em;">
                            <?php echo esc_html($plugin->name); ?>
                        </h3>
                    </div>
                        
                    <p style="margin-top: 0; color: #666;">
                        <?php echo esc_html(wp_trim_words($plugin->short_description, 20)); ?>
                    </p>
                        
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <div>
                            <span style="display: inline-block; background: #f0f0f0; padding: 2px 8px; border-radius: 3px; font-size: 0.9em;">
                                <?php 
                                
                                printf(
                                    /* translators: %s: Plugin version number */
                                    esc_html__('Version %s', 'woo-extra-product-options'), 
                                    esc_html($plugin->version)
                                );
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                 
                <div class="th-plugins-bottom">
                    <div class="plugin-actions" style="margin-top: 15px; display: flex; gap: 10px;">
                        <button class="button view-details" 
                                data-plugin='<?php echo esc_attr(wp_json_encode($plugin)); ?>'
                                data-activation-url='<?php echo esc_url($activation_url); ?>'>
                            <?php esc_html_e('View Details', 'woo-extra-product-options'); ?>
                        </button>

                        <?php if ($plugin_active) : ?>
                            <span class="button button-disabled">
                                <?php esc_html_e('Active', 'woo-extra-product-options'); ?>
                            </span>
                        <?php elseif ($plugin_installed) : ?>
                            <a href="<?php echo esc_url($activation_url); ?>" class="button button-primary">
                                <?php esc_html_e('Activate', 'woo-extra-product-options'); ?>
                            </a>
                        <?php else : ?>
                            <button class="button button-primary install-plugin"
                                    data-slug="<?php echo esc_attr($plugin->slug); ?>"
                                    data-name="<?php echo esc_attr($plugin->name); ?>">
                                <?php esc_html_e('Install Now', 'woo-extra-product-options'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
        
        /**
         * Get ThemeHigh plugins from WordPress.org API
         */
        public static function get_themehigh_plugins_from_wp_org() {
            $transient_key = 'themehigh_free_plugins_wp_org';
            $plugins = get_transient($transient_key);
            
            if ($plugins !== false) {
                return $plugins;
            }
            
            $api_url = add_query_arg(array(
                'action' => 'query_plugins',
                'request' => array(
                    'author' => 'themehigh',
                    'per_page' => 100,
                    'fields' => array(
                        'icons' => true,
                        'banners' => true,
                        'short_description' => true,
                        'active_installs' => true,
                        'last_updated' => true,
                        'rating' => true,
                        'num_ratings' => true,
                        'homepage' => true,
                        'author' => true,
                        'sections' => true
                    )
                )
            ), 'https://api.wordpress.org/plugins/info/1.2/');
            
            $response = wp_remote_get($api_url, array(
                'timeout' => 15,
                'user-agent' => 'ThemeHigh Admin Menu; ' . home_url()
            ));
            
            if (is_wp_error($response)) {
                return array();
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (empty($data['plugins'])) {
                return array();
            }
            
            // Sort plugins by active installs and rating
            uasort($data['plugins'], array(__CLASS__, 'sort_plugins_callback'));
            
            set_transient($transient_key, $data['plugins'], self::CACHE_EXPIRATION);
            return $data['plugins'];
        }
        
        /**
         * Callback function for sorting plugins
         */
        public static function sort_plugins_callback($a, $b) {
            $a_installs = isset($a['active_installs']) ? $a['active_installs'] : 0;
            $b_installs = isset($b['active_installs']) ? $b['active_installs'] : 0;
            
            if ($a_installs == $b_installs) {
                $a_rating = isset($a['rating']) ? $a['rating'] : 0;
                $b_rating = isset($b['rating']) ? $b['rating'] : 0;
                
                if ($b_rating > $a_rating) return 1;
                if ($b_rating < $a_rating) return -1;
                return 0;
            }
            
            if ($b_installs > $a_installs) return 1;
            if ($b_installs < $a_installs) return -1;
            return 0;
        }
        
        /**
         * Check if plugin is installed
         */
        public static function is_plugin_installed($slug) {
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            $all_plugins = get_plugins();
            
            foreach ($all_plugins as $plugin_file => $plugin_data) {
                $plugin_dir = dirname($plugin_file);
                if ($plugin_dir === $slug || $plugin_file === $slug . '/' . $slug . '.php') {
                    return $plugin_file;
                }
            }
            
            return false;
        }
        
        /**
         * Add JavaScript for plugin management
         */
        public static function add_plugin_management_js() {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                
                // Handle view details click
                $(document).on('click', '.view-details', function(e) {
                    e.preventDefault();
                    var pluginData = $(this).data('plugin');
                    var activationUrl = $(this).data('activation-url');
                    var $modal = $('#themehigh-plugin-modal');
                    var $button = $(this).closest('.th-plugins-child').find('.install-plugin, .button-primary, .button-disabled');
                    var pluginState = 'install'; // Default

                    // Determine plugin state from the clicked button
                    if ($button.hasClass('button-primary') && !$button.hasClass('install-plugin')) {
                        pluginState = 'activate';
                    } else if ($button.hasClass('button-disabled')) {
                        pluginState = 'active';
                    }

                    // Populate modal with plugin data
                    $modal.find('.plugin-name').text(pluginData.name);

                    // Set WordPress.org link
                    var wordpressUrl = 'https://wordpress.org/plugins/' + pluginData.slug + '/';
                    $modal.find('.wordpress-link').attr('href', wordpressUrl);

                    // Clear previous buttons
                    $modal.find('.plugin-action-buttons').empty();

                    // Add appropriate button based on plugin state
                    if (pluginState === 'install') {
                        $modal.find('.plugin-action-buttons').html(
                            '<button class="button button-primary install-from-modal" ' +
                            'data-slug="' + pluginData.slug + '" ' +
                            'data-name="' + pluginData.name + '">' +
                            '<?php esc_html_e("Install Now", 'woo-extra-product-options'); ?>' +
                            '</button>'
                        );
                    } else if (pluginState === 'activate') {
                        $modal.find('.plugin-action-buttons').html(
                            '<a href="' + activationUrl + '" class="button button-primary">' +
                            '<?php esc_html_e("Activate", 'woo-extra-product-options'); ?>' +
                            '</a>'
                        );
                    } else {
                        $modal.find('.plugin-action-buttons').html(
                            '<span class="button button-disabled">' +
                            '<?php esc_html_e("Active", 'woo-extra-product-options'); ?>' +
                            '</span>'
                        );
                    }

                    // Use banner if available, otherwise use icon
                    var screenshotUrl = pluginData.banners && pluginData.banners.low ? 
                        pluginData.banners.low : 
                        (pluginData.icons['1x'] || pluginData.icons['2x'] || pluginData.icons.default);

                    $modal.find('.plugin-screenshot').html('<img src="' + screenshotUrl + '" alt="' + pluginData.name + '">');
                    $modal.find('.plugin-description').html(pluginData.short_description);

                    // Add meta information
                    $modal.find('.plugin-version').html('<strong><?php esc_html_e("Version:", 'woo-extra-product-options'); ?></strong> ' + pluginData.version);
                    $modal.find('.plugin-active-installs').html('<strong><?php esc_html_e("Active Installations:", 'woo-extra-product-options'); ?></strong> ' + pluginData.active_installs.toLocaleString() + '+');

                    // Rating
                    var ratingText = '<strong><?php esc_html_e("Rating:", 'woo-extra-product-options'); ?></strong> ';
                    var starCount = Math.round(pluginData.rating / 20);
                    var fullStars = Math.floor(starCount);
                    var halfStar = (starCount - fullStars) >= 0.5;
                    var emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

                    for (var i = 0; i < fullStars; i++) {
                        ratingText += '<span class="dashicons dashicons-star-filled" style="color: #ffb900;"></span>';
                    }
                    if (halfStar) {
                        ratingText += '<span class="dashicons dashicons-star-half" style="color: #ffb900;"></span>';
                    }
                    for (var i = 0; i < emptyStars; i++) {
                        ratingText += '<span class="dashicons dashicons-star-empty" style="color: #ffb900;"></span>';
                    }

                    ratingText += ' (' + pluginData.num_ratings.toLocaleString() + ' <?php esc_html_e("ratings", 'woo-extra-product-options'); ?>)';
                    $modal.find('.plugin-rating').html(ratingText);

                    // Author
                    $modal.find('.plugin-author').html('<strong><?php esc_html_e("Author:", 'woo-extra-product-options'); ?></strong> ' + pluginData.author);
                    
                    // Show modal
                    $modal.fadeIn();
                });

                // Install from modal
                $(document).on('click', '.install-from-modal', function(e) {
                    e.preventDefault();
                    $('#themehigh-plugin-modal').fadeOut();
                    // Trigger click on the corresponding install button in the grid
                    $('.install-plugin[data-slug="' + $(this).data('slug') + '"]').trigger('click');
                });

                // Close modal
                $(document).on('click', '.close-modal', function(e) {
                    e.preventDefault();
                    $('#themehigh-plugin-modal').fadeOut();
                });

                // Handle plugin installation
                $(document).on('click', '.install-plugin', function(e) {
                    e.preventDefault();
                    var $button = $(this);
                    var slug = $button.data('slug');
                    var pluginName = $button.data('name');

                    $button.addClass('updating-message').text('<?php esc_html_e('Installing...', 'woo-extra-product-options'); ?>');

                    wp.updates.installPlugin({
                        slug: slug,
                        success: function(response) {
                            var activationUrl = response.activateUrl;
                           
                            // Replace the install button with an activation link
                            $button.replaceWith(
                                '<a href="' + activationUrl + '" class="button button-primary">' +
                                '<?php esc_html_e("Activate", 'woo-extra-product-options'); ?>' +
                                '</a>'
                            );

                            // Update the view details button to include the activation URL
                            $button.closest('.th-plugins-child').find('.view-details').data('activation-url', activationUrl);
                        },
                        error: function(response) {
                            $button.removeClass('updating-message').text('<?php esc_html_e('Install Failed', 'woo-extra-product-options'); ?>');
                        }
                    });
                });
            });
            </script>
            <?php
        }
    }
}