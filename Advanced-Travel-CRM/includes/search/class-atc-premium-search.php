<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * ATC Premium Search Page
 * Make My Trip-like premium search interface
 * ═══════════════════════════════════════════════════════════════════
 */

if (!defined('ABSPATH')) exit;

class ATC_Premium_Search {
    
    public static function init() {
        add_shortcode('atc_premium_search', [__CLASS__, 'search_shortcode']);
        add_shortcode('atc_global_search', [__CLASS__, 'global_search_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets'], 20);
    }
    
    public static function enqueue_assets() {
        // Check if shortcode is on the page
        global $post;
        $has_shortcode = false;
        
        if (is_a($post, 'WP_Post')) {
            // Check in post content for both premium search and global search
            if (has_shortcode($post->post_content, 'atc_premium_search') || 
                has_shortcode($post->post_content, 'atc_global_search')) {
                $has_shortcode = true;
            }
            
            // Also check if shortcode might be in widgets/blocks
            // This is a fallback for page builders
            if (strpos($post->post_content, 'atc_premium_search') !== false || 
                strpos($post->post_content, 'atc_global_search') !== false) {
                $has_shortcode = true;
            }
        }
        
        // If no shortcode found, don't enqueue
        if (!$has_shortcode) {
            return;
        }
        
        wp_enqueue_style('atc-premium-search', ATC_ASSETS_URL . 'css/atc-premium-search.css', [], ATC_VERSION);
        wp_enqueue_script('atc-premium-search', ATC_ASSETS_URL . 'js/atc-premium-search.js', ['jquery'], ATC_VERSION, true);
        
        // Ensure REST URL is properly formatted
        $rest_url = rest_url('atc/v1/');
        if (!preg_match('/^https?:\/\//', $rest_url)) {
            // If relative URL, make it absolute
            $rest_url = home_url($rest_url);
        }
        
        // Ensure rest_url is not empty
        if (empty($rest_url)) {
            $rest_url = home_url('/wp-json/atc/v1/');
        }
        
        // Ensure rest_url ends with /
        if (!preg_match('/\/$/', $rest_url)) {
            $rest_url .= '/';
        }
        
        // Use a different variable name to avoid conflicts
        // Store it as atcPremiumSearchConfig to avoid being overwritten
        wp_localize_script('atc-premium-search', 'atcPremiumSearchConfig', [
            'restUrl' => esc_url_raw($rest_url),
            'nonce' => wp_create_nonce('wp_rest'),
            'currency' => get_option('atc_currency_symbol', '₹'),
            'currencyCode' => get_option('atc_currency', 'INR'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
        
        // Also set it as atcPremiumSearch for backward compatibility
        // But only if it doesn't exist yet
        wp_add_inline_script('atc-premium-search', '
            if (typeof atcPremiumSearch === "undefined" || !atcPremiumSearch.restUrl) {
                var atcPremiumSearch = atcPremiumSearchConfig;
                window._atcConfigOriginal = atcPremiumSearchConfig;
            }
        ', 'after');
    }
    
    /**
     * Premium Search Page Shortcode
     * Usage: [atc_premium_search service="tours"] or [atc_premium_search service="visa"]
     * Supports service-specific theming via Service Ecosystem
     */
    public static function search_shortcode($atts) {
        $atts = shortcode_atts([
            'service' => 'tours',
        ], $atts);
        
        $service_key = sanitize_text_field($atts['service']);
        
        ob_start();
        ?>
        <div class="atc-premium-search-page" data-service="<?php echo esc_attr($service_key); ?>">
            
            <!-- Premium Search Form with Strong Glassmorphism -->
            <form class="atc-premium-search-form-compact" id="atc-premium-search-form">
                <div class="atc-search-form-row">
                    <div class="atc-form-group atc-form-group-flexible">
                        <?php if (!empty($service_key)): ?>
                            <input type="text" name="search_query" placeholder="Search <?php echo esc_attr(ucfirst($service_key)); ?> destinations, activities, or packages..." autocomplete="off" class="atc-search-input-main">
                        <?php else: ?>
                            <input type="text" name="search_query" placeholder="Search all destinations, activities, or packages..." autocomplete="off" class="atc-search-input-main">
                        <?php endif; ?>
                        <input type="hidden" name="destination" value="">
                        <input type="hidden" name="activities" value="">
                        <input type="hidden" name="package_type" value="">
                    </div>
                    
                    <button type="submit" class="atc-btn-search-compact">
                        <span class="atc-search-icon">🔍</span>
                        <span class="atc-search-text">Search</span>
                    </button>
                </div>
                
                <!-- Advanced Filters (Collapsible) -->
                <div class="atc-search-advanced-toggle">
                    <button type="button" class="atc-advanced-toggle-btn" aria-expanded="false">
                        <span>⚙️ More Filters</span>
                        <span class="atc-toggle-icon">▼</span>
                    </button>
                </div>
                
                <div class="atc-search-advanced-filters" style="display: none;">
                    <div class="atc-advanced-filters-row">
                        <div class="atc-form-group">
                            <label>💰 Budget Range</label>
                            <select name="budget_range">
                                <option value="">Any Budget</option>
                                <option value="0-10000">Under ₹10,000</option>
                                <option value="10000-25000">₹10,000 - ₹25,000</option>
                                <option value="25000-50000">₹25,000 - ₹50,000</option>
                                <option value="50000-100000">₹50,000 - ₹1,00,000</option>
                                <option value="100000+">Above ₹1,00,000</option>
                            </select>
                        </div>
                        
                        <div class="atc-form-group">
                            <label>🎯 Package Type</label>
                            <select name="package_type_filter">
                                <option value="">All Types</option>
                                <option value="luxury">Luxury</option>
                                <option value="standard">Standard</option>
                                <option value="budget">Budget</option>
                                <option value="honeymoon">Honeymoon</option>
                                <option value="pilgrimage">Pilgrimage</option>
                                <option value="adventure">Adventure</option>
                            </select>
                        </div>
                        
                        <div class="atc-form-group">
                            <label>⭐ Rating</label>
                            <select name="rating">
                                <option value="">Any Rating</option>
                                <option value="5">5 Stars</option>
                                <option value="4">4+ Stars</option>
                                <option value="3">3+ Stars</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="service" value="<?php echo esc_attr($service_key); ?>">
            </form>
            
            <!-- Results Section (Initially Hidden) -->
            <div class="atc-premium-results-wrapper" id="atc-premium-results" style="display: none;">
                <?php echo do_shortcode('[atc_premium_results service="' . esc_attr($service_key) . '"]'); ?>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Global Search Shortcode - Searches across all services
     * Usage: [atc_global_search] - No service parameter needed
     */
    public static function global_search_shortcode($atts) {
        $atts = shortcode_atts([
            'title' => 'Search All Travel Services',
            'subtitle' => 'Find packages across tours, hotels, flights, and more',
        ], $atts);
        
        ob_start();
        ?>
        <div class="atc-premium-search-page atc-global-search-page" data-service="">
            
            <!-- Premium Search Form with Strong Glassmorphism -->
            <form class="atc-premium-search-form-compact" id="atc-premium-search-form">
                <div class="atc-search-form-row">
                    <div class="atc-form-group atc-form-group-flexible">
                        <input type="text" name="search_query" placeholder="Search all destinations, activities, or packages across all services..." autocomplete="off" class="atc-search-input-main">
                        <input type="hidden" name="destination" value="">
                        <input type="hidden" name="activities" value="">
                        <input type="hidden" name="package_type" value="">
                    </div>
                    
                    <button type="submit" class="atc-btn-search-compact">
                        <span class="atc-search-icon">🔍</span>
                        <span class="atc-search-text">Search</span>
                    </button>
                </div>
                
                <!-- Service Filter for Global Search -->
                <div class="atc-global-search-services">
                    <label>Filter by Service:</label>
                    <div class="atc-service-filters">
                        <?php
                        if (class_exists('ATC_Services')) {
                            $services = ATC_Services::get_services();
                            foreach ($services as $service_key => $service_data) {
                                $service_label = is_array($service_data) ? $service_data['label'] : $service_key;
                                ?>
                                <label class="atc-service-filter-checkbox">
                                    <input type="checkbox" name="service_filter[]" value="<?php echo esc_attr($service_key); ?>" checked>
                                    <span><?php echo esc_html($service_label); ?></span>
                                </label>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Advanced Filters (Collapsible) -->
                <div class="atc-search-advanced-toggle">
                    <button type="button" class="atc-advanced-toggle-btn" aria-expanded="false">
                        <span>⚙️ More Filters</span>
                        <span class="atc-toggle-icon">▼</span>
                    </button>
                </div>
                
                <div class="atc-search-advanced-filters" style="display: none;">
                    <div class="atc-advanced-filters-row">
                        <div class="atc-form-group">
                            <label>💰 Budget Range</label>
                            <select name="budget_range">
                                <option value="">Any Budget</option>
                                <option value="0-10000">Under ₹10,000</option>
                                <option value="10000-25000">₹10,000 - ₹25,000</option>
                                <option value="25000-50000">₹25,000 - ₹50,000</option>
                                <option value="50000-100000">₹50,000 - ₹1,00,000</option>
                                <option value="100000+">Above ₹1,00,000</option>
                            </select>
                        </div>
                        
                        <div class="atc-form-group">
                            <label>🎯 Package Type</label>
                            <select name="package_type_filter">
                                <option value="">All Types</option>
                                <option value="luxury">Luxury</option>
                                <option value="standard">Standard</option>
                                <option value="budget">Budget</option>
                                <option value="honeymoon">Honeymoon</option>
                                <option value="pilgrimage">Pilgrimage</option>
                                <option value="adventure">Adventure</option>
                            </select>
                        </div>
                        
                        <div class="atc-form-group">
                            <label>⭐ Rating</label>
                            <select name="rating">
                                <option value="">Any Rating</option>
                                <option value="5">5 Stars</option>
                                <option value="4">4+ Stars</option>
                                <option value="3">3+ Stars</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- No service parameter - empty means global search -->
                <input type="hidden" name="service" value="">
            </form>
            
            <!-- Results Section (Initially Hidden) -->
            <div class="atc-premium-results-wrapper" id="atc-premium-results" style="display: none;">
                <div class="atc-global-search-results"></div>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Convert hex color to rgba
     */
    private static function hex_to_rgba($hex, $alpha = 1) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r, $g, $b, $alpha)";
    }
}

