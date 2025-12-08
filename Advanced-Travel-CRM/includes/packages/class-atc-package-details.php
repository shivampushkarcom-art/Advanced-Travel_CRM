<?php
/**
 * ATC Package Details
 * Display full package information and handle booking
 */

if (!defined('ABSPATH')) exit;

class ATC_Package_Details {
    
    public static function init() {
        add_shortcode('atc_package_details', [__CLASS__, 'package_details_shortcode']);
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function enqueue_assets() {
        // Don't enqueue assets if enhanced version exists and is loaded
        // Enhanced version handles all asset loading
        if (class_exists('ATC_Package_Details_Enhanced')) {
            return; // Let enhanced version handle asset loading
        }
        
        // Legacy fallback - only if enhanced version doesn't exist
        // These files don't exist, so we'll skip to prevent 404 errors
        // wp_enqueue_style('atc-package-details', ATC_ASSETS_URL . 'css/atc-package-details.css', [], ATC_VERSION);
        // wp_enqueue_script('atc-package-details', ATC_ASSETS_URL . 'js/atc-package-details.js', ['jquery'], ATC_VERSION, true);
        
        // Use tours CSS as fallback if enhanced version doesn't exist
        $tours_css = ATC_PLUGIN_DIR . 'assets/css/atc-package-details-tours.css';
        if (file_exists($tours_css)) {
            wp_enqueue_style('atc-package-details-tours', ATC_ASSETS_URL . 'css/atc-package-details-tours.css', [], ATC_VERSION);
        }
    }
    
    public static function register_routes() {
        register_rest_route('atc/v1', '/package/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_package'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    public static function get_package($request) {
        $package_id = intval($request['id']);
        
        global $wpdb;
        
        // Try custom packages first
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d AND status = 'active'",
            $package_id
        ), ARRAY_A);
        
        // If not found, try services table
        if (!$package) {
            $package = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_SERVICES . " WHERE id = %d AND status = 'active'",
                $package_id
            ), ARRAY_A);
        }
        
        if (!$package) {
            return new WP_Error('package_not_found', __('Package not found', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        // Format package data
        $package = self::format_package_data($package);
        
        return rest_ensure_response([
            'success' => true,
            'package' => $package,
        ]);
    }
    
    public static function format_package_data($package) {
        // Parse JSON fields
        if (!empty($package['images'])) {
            $package['images'] = is_string($package['images']) ? json_decode($package['images'], true) : $package['images'];
        }
        
        if (!empty($package['features'])) {
            $package['features'] = is_string($package['features']) ? explode(',', $package['features']) : $package['features'];
        }
        
        if (!empty($package['inclusions'])) {
            $package['inclusions'] = is_string($package['inclusions']) ? explode(',', $package['inclusions']) : $package['inclusions'];
        }
        
        if (!empty($package['exclusions'])) {
            $package['exclusions'] = is_string($package['exclusions']) ? explode(',', $package['exclusions']) : $package['exclusions'];
        }
        
        if (!empty($package['itinerary'])) {
            $package['itinerary'] = is_string($package['itinerary']) ? json_decode($package['itinerary'], true) : $package['itinerary'];
        }
        
        return $package;
    }
    
    public static function package_details_shortcode($atts) {
        $atts = shortcode_atts([
            'package_id' => '',
        ], $atts);
        
        $package_id = !empty($atts['package_id']) ? intval($atts['package_id']) : (isset($_GET['package_id']) ? intval($_GET['package_id']) : 0);
        
        if (!$package_id) {
            return '<p class="atc-error">Package ID is required.</p>';
        }
        
        ob_start();
        ?>
        <div class="atc-package-details-wrapper" data-package-id="<?php echo esc_attr($package_id); ?>">
            <div class="atc-loading-state">
                <div class="atc-spinner"></div>
                <p>Loading package details...</p>
            </div>
            
            <div class="atc-package-details-content" style="display: none;">
                <!-- Package details will be loaded here by JS -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function render_package_details($package) {
        ob_start();
        ?>
        <div class="atc-package-details">
            
            <!-- Image Gallery -->
            <div class="atc-package-gallery">
                <?php if (!empty($package['image_url'])): ?>
                    <div class="atc-main-image">
                        <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>">
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($package['images']) && is_array($package['images'])): ?>
                    <div class="atc-image-thumbnails">
                        <?php foreach ($package['images'] as $image): ?>
                            <img src="<?php echo esc_url($image); ?>" alt="Gallery image">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Package Header -->
            <div class="atc-package-header">
                <h1 class="atc-package-title"><?php echo esc_html($package['name']); ?></h1>
                
                <?php if (!empty($package['destination'])): ?>
                    <p class="atc-package-location">📍 <?php echo esc_html($package['destination']); ?></p>
                <?php endif; ?>
                
                <div class="atc-package-meta">
                    <?php if (!empty($package['duration_days'])): ?>
                        <span class="atc-meta-item">📅 <?php echo esc_html($package['duration_days']); ?> Days</span>
                    <?php endif; ?>
                    
                    <?php if (!empty($package['rating'])): ?>
                        <span class="atc-meta-item">⭐ <?php echo esc_html($package['rating']); ?>/5</span>
                    <?php endif; ?>
                    
                    <?php if (!empty($package['reviews_count'])): ?>
                        <span class="atc-meta-item">(<?php echo esc_html($package['reviews_count']); ?> reviews)</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Price Section -->
            <div class="atc-package-pricing">
                <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                    <span class="atc-price-original"><?php echo get_option('atc_currency_symbol', '₹'); ?><?php echo number_format($package['original_price']); ?></span>
                <?php endif; ?>
                <span class="atc-price-current"><?php echo get_option('atc_currency_symbol', '₹'); ?><?php echo number_format($package['price']); ?></span>
                <span class="atc-price-label">per person</span>
                
                <?php if (!empty($package['discount'])): ?>
                    <span class="atc-discount-badge"><?php echo esc_html($package['discount']); ?>% OFF</span>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <?php if (!empty($package['description'])): ?>
                <div class="atc-package-section">
                    <h2>About This Package</h2>
                    <div class="atc-package-description">
                        <?php echo wp_kses_post(nl2br($package['description'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Features -->
            <?php if (!empty($package['features'])): ?>
                <div class="atc-package-section">
                    <h2>Key Features</h2>
                    <ul class="atc-package-features">
                        <?php foreach ($package['features'] as $feature): ?>
                            <li>✓ <?php echo esc_html(trim($feature)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Inclusions & Exclusions -->
            <div class="atc-package-section atc-inclusions-exclusions">
                <div class="atc-inclusions">
                    <h3>✅ Inclusions</h3>
                    <?php if (!empty($package['inclusions'])): ?>
                        <ul>
                            <?php foreach ($package['inclusions'] as $inclusion): ?>
                                <li><?php echo esc_html(trim($inclusion)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Contact us for details</p>
                    <?php endif; ?>
                </div>
                
                <div class="atc-exclusions">
                    <h3>❌ Exclusions</h3>
                    <?php if (!empty($package['exclusions'])): ?>
                        <ul>
                            <?php foreach ($package['exclusions'] as $exclusion): ?>
                                <li><?php echo esc_html(trim($exclusion)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Contact us for details</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Itinerary -->
            <?php if (!empty($package['itinerary'])): ?>
                <div class="atc-package-section">
                    <h2>Itinerary</h2>
                    <div class="atc-itinerary">
                        <?php if (is_array($package['itinerary'])): ?>
                            <?php foreach ($package['itinerary'] as $day => $details): ?>
                                <div class="atc-itinerary-day">
                                    <h3>Day <?php echo esc_html($day); ?></h3>
                                    <p><?php echo esc_html($details); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php echo esc_html($package['itinerary']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Terms & Conditions -->
            <?php if (!empty($package['terms_conditions'])): ?>
                <div class="atc-package-section">
                    <h2>Terms & Conditions</h2>
                    <div class="atc-terms">
                        <?php echo wp_kses_post(nl2br($package['terms_conditions'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Booking Actions -->
            <div class="atc-package-actions">
                <button class="atc-btn atc-btn-primary atc-btn-large atc-book-now-btn" data-package-id="<?php echo esc_attr($package['id']); ?>">
                    Book Now
                </button>
                <button class="atc-btn atc-btn-outline atc-btn-large atc-query-btn" data-package-id="<?php echo esc_attr($package['id']); ?>">
                    Ask for Custom Package
                </button>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
}

