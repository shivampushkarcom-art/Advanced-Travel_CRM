<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * ATC Plugin Core - ENHANCED SHORTCODES v2.3.1
 * Location: includes/core/class-atc-plugin.php
 * 
 * ENHANCEMENTS:
 * ✅ MakeMyTrip-style search widget
 * ✅ Multi-service support with dynamic configs
 * ✅ Integrated booking flow
 * ✅ Advanced search with filters
 * ✅ Custom package requests
 * ✅ Elementor & Astra compatible
 * ═══════════════════════════════════════════════════════════════════
 */

if (!defined('ABSPATH')) exit;

class ATC_Plugin {
    
    private static $initialized = false;

    public static function init() {
        // Prevent double initialization
        if (self::$initialized) {
            return;
        }
        
        self::register_shortcodes();
        self::hooks();
        
        self::$initialized = true;
    }

    /**
     * ✅ ENHANCED: Register all shortcodes
     */
    private static function register_shortcodes() {
        // Search & Results
        add_shortcode('atc_search', [__CLASS__, 'search_shortcode']);
        add_shortcode('atc_search_widget', [__CLASS__, 'search_widget_shortcode']);
        add_shortcode('atc_search_results', [__CLASS__, 'search_results_shortcode']);
        add_shortcode('atc_search_results_container', [__CLASS__, 'search_results_container']);
        
        // Booking
        add_shortcode('atc_booking_form', [__CLASS__, 'booking_shortcode']);
        add_shortcode('atc_booking_button', [__CLASS__, 'booking_button_shortcode']);
        
        // Custom Requests
        add_shortcode('atc_custom_request', [__CLASS__, 'custom_request_shortcode']);
        
        // Customer Account (if not already registered by ATC_User)
        if (!shortcode_exists('atc_account_dashboard')) {
            add_shortcode('atc_account_dashboard', [__CLASS__, 'account_dashboard_shortcode']);
        }
        
        // Service Grid (handled by ATC_Service_Grid class)
    }

    private static function hooks() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        
        // Initialize visitor tracker
        add_action('init', [__CLASS__, 'init_visitor_tracker'], 1);
    }
    
    /**
     * Initialize visitor tracker
     */
    public static function init_visitor_tracker() {
        if (class_exists('ATC_Visitor_Tracker')) {
            ATC_Visitor_Tracker::get_instance();
        }
    }

    /**
     * ✅ ENHANCED: Enqueue all frontend assets
     */
    public static function enqueue_assets() {
        // Core Styles
        wp_enqueue_style('atc-styles', ATC_PLUGIN_URL . 'assets/css/atc-styles.css', [], ATC_VERSION);
        
        // Premium Global Styles
        wp_enqueue_style('atc-global-premium', ATC_PLUGIN_URL . 'assets/css/atc-global-premium.css', [], ATC_VERSION);
        
        // Premium Header Styles (always load for branding)
        wp_enqueue_style('atc-premium-header', ATC_PLUGIN_URL . 'assets/css/atc-premium-header.css', [], ATC_VERSION);
        
        // Search Widget Styles
        wp_enqueue_style('atc-search-widget', ATC_PLUGIN_URL . 'assets/css/atc-search-widget.css', [], ATC_VERSION);
        
        // Results Grid Styles
        wp_enqueue_style('atc-results-grid', ATC_PLUGIN_URL . 'assets/css/atc-results-grid.css', [], ATC_VERSION);
        
        // Service Grid Styles
        wp_enqueue_style('atc-service-grid', ATC_PLUGIN_URL . 'assets/css/atc-service-grid.css', [], ATC_VERSION);
        
        // Account Styles (if logged in)
        if (is_user_logged_in()) {
            wp_enqueue_style('atc-account', ATC_PLUGIN_URL . 'assets/css/atc-account.css', [], ATC_VERSION);
        }
        
        // Core Scripts
        wp_enqueue_script('atc-calculation', ATC_PLUGIN_URL . 'assets/js/atc-calculation.js', ['jquery'], ATC_VERSION, true);
        wp_enqueue_script('atc-search', ATC_PLUGIN_URL . 'assets/js/atc-search.js', ['jquery'], ATC_VERSION, true);
        wp_enqueue_script('atc-frontend', ATC_PLUGIN_URL . 'assets/js/atc-frontend.js', ['jquery', 'atc-calculation', 'atc-search'], ATC_VERSION, true);
        
        // Results Handler
        wp_enqueue_script('atc-results', ATC_PLUGIN_URL . 'assets/js/atc-results.js', ['jquery'], ATC_VERSION, true);
        
        // Booking Window
        wp_enqueue_script('atc-booking-window', ATC_PLUGIN_URL . 'assets/js/atc-booking-window.js', ['jquery', 'atc-frontend'], ATC_VERSION, true);
        
        // Localize main script
        wp_localize_script('atc-frontend', 'atcVars', [
            'restUrl' => esc_url_raw(rest_url('atc/v1/')),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('atc_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'currency' => get_option('atc_currency', 'INR'),
            'currencySymbol' => get_option('atc_currency_symbol', '₹'),
            'paymentEnabled' => get_option('atc_payment_enabled', 0),
        ]);
        
        // Visitor tracking script (capture screen resolution)
        wp_add_inline_script('atc-frontend', "
            // Capture screen resolution for visitor tracking
            if (screen.width && screen.height) {
                var resolution = screen.width + 'x' + screen.height;
                document.cookie = 'atc_screen_resolution=' + resolution + '; path=/; max-age=' + (365 * 24 * 60 * 60);
            }
        ");
    }

    /**
     * ✅ NEW: MakeMyTrip-style Search Widget
     * Usage: [atc_search_widget service="tours" title="Find Your Perfect Tour"]
     */
    public static function search_widget_shortcode($atts) {
        $service = isset($atts['service']) 
            ? sanitize_text_field($atts['service']) 
            : ATC_Services::detect_service_context();

        // Check service status
        if (class_exists('ATC_Service_Manager')) {
            $statuses = ATC_Service_Manager::get_services_status();
            if (isset($statuses[$service]) && !$statuses[$service]) {
                return '<p class="atc-notice">This service is currently unavailable.</p>';
            }
        }

        $title = isset($atts['title']) ? sanitize_text_field($atts['title']) : '';
        $show_results = isset($atts['show_results']) ? $atts['show_results'] === 'true' : false;

        ob_start();
        ?>
        <div class="atc-search-widget-wrapper" data-service="<?php echo esc_attr($service); ?>">
            
            <?php if ($title): ?>
                <div class="atc-search-widget-header">
                    <h2 class="atc-search-widget-title"><?php echo esc_html($title); ?></h2>
                </div>
            <?php endif; ?>
            
            <div class="atc-search-widget">
                <form class="atc-search-form atc-search-form-modern" data-service="<?php echo esc_attr($service); ?>">
                    
                    <div class="atc-message-container"></div>
                    
                    <!-- Dynamic fields will be injected by JS -->
                    <div class="atc-form-fields-modern"></div>
                    
                    <!-- Search Button -->
                    <button type="submit" class="atc-search-btn atc-btn-search-modern">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <span>Search Packages</span>
                    </button>
                </form>
            </div>
            
            <?php if ($show_results): ?>
                <!-- Results container (will be populated by JS) -->
                <div class="atc-search-results-inline"></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * ✅ ENHANCED: Basic Search Form (Backward Compatible)
     * Usage: [atc_search service="tours"]
     */
    public static function search_shortcode($atts = []) {
        $service = isset($atts['service'])
            ? sanitize_text_field($atts['service'])
            : ATC_Services::detect_service_context();

        // Respect service manager status
        if (class_exists('ATC_Service_Manager')) {
            $statuses = ATC_Service_Manager::get_services_status();
            if (isset($statuses[$service]) && !$statuses[$service]) {
                return '<p class="atc-notice">This service is currently unavailable.</p>';
            }
        }

        $title = isset($atts['title']) ? sanitize_text_field($atts['title']) : '';

        ob_start();
        ?>
        <div class="atc-form-wrapper atc-search-wrapper">
            <?php if ($title): ?>
                <h3 class="atc-form-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>

            <form class="atc-search-form" data-service="<?php echo esc_attr($service); ?>">
                <div class="atc-message-container"></div>
                
                <!-- Dynamic fields injected by atc-frontend.js from JSON config -->
                <div class="atc-form-fields"></div>

                <!-- Totals area updated by calculator -->
                <div class="atc-total">Total: <?php echo esc_html(get_option('atc_currency', 'INR')); ?> 0.00</div>

                <button type="submit" class="button button-primary atc-search-btn">Search & Send Lead</button>
            </form>

            <!-- Results container for dual search pages (optional) -->
            <div class="atc-search-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ✅ NEW: Search Results with Filters
     * Usage: [atc_search_results service="tours"]
     */
    public static function search_results_shortcode($atts) {
        // Delegate to ATC_Search_Results if available
        if (class_exists('ATC_Search_Results')) {
            return ATC_Search_Results::results_shortcode($atts);
        }
        
        // Fallback
        return '<div class="atc-search-results-container"></div>';
    }
    
    /**
     * Simple results container
     */
    public static function search_results_container() {
        return '<div class="atc-search-results"></div>';
    }

    /**
     * ✅ ENHANCED: Booking Form
     * Usage: [atc_booking_form service="tours"]
     */
    public static function booking_shortcode($atts = []) {
        $service = isset($atts['service'])
            ? sanitize_text_field($atts['service'])
            : ATC_Services::detect_service_context();

        // Respect service manager status
        if (class_exists('ATC_Service_Manager')) {
            $statuses = ATC_Service_Manager::get_services_status();
            if (isset($statuses[$service]) && !$statuses[$service]) {
                return '<p class="atc-notice">This service is currently unavailable.</p>';
            }
        }

        $require_login = !empty($atts['require_login']) && $atts['require_login'] === 'true';
        if ($require_login && !is_user_logged_in()) {
            return '<div class="atc-notice">Please <a href="'. esc_url(home_url('/login/')) .'">login</a> to book.</div>';
        }

        $title = isset($atts['title']) ? sanitize_text_field($atts['title']) : '';
        $package_id = isset($atts['package_id']) ? intval($atts['package_id']) : 0;

        ob_start();
        ?>
        <div class="atc-form-wrapper atc-booking-wrapper">
            <?php if ($title): ?>
                <h3 class="atc-form-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>

            <form class="atc-booking-form" method="post" action="" data-service="<?php echo esc_attr($service); ?>">
                <?php wp_nonce_field('atc_booking','_atc_booking_nonce'); ?>
                <input type="hidden" name="atc_booking_submit" value="1"/>
                <input type="hidden" name="service" value="<?php echo esc_attr($service); ?>"/>
                <input type="hidden" name="price_total" value="0"/>
                <?php if ($package_id): ?>
                    <input type="hidden" name="package_id" value="<?php echo esc_attr($package_id); ?>"/>
                <?php endif; ?>

                <div class="atc-message-container"></div>

                <!-- Dynamic service-specific fields from JSON config -->
                <div class="atc-form-fields"></div>

                <!-- Customer details -->
                <div class="atc-customer-details">
                    <h4>Your Details</h4>
                    
                    <div class="atc-form-row">
                        <div class="atc-form-field">
                            <label>Your Name *</label>
                            <input type="text" name="customer_name" required>
                        </div>
                    </div>
                    
                    <div class="atc-form-row atc-form-row-2col">
                        <div class="atc-form-field">
                            <label>Email Address *</label>
                            <input type="email" name="customer_email" required>
                        </div>
                        
                        <div class="atc-form-field">
                            <label>Phone Number</label>
                            <input type="tel" name="customer_phone" placeholder="+91...">
                        </div>
                    </div>
                </div>

                <!-- Totals area updated by calculator -->
                <div class="atc-total">Total: <?php echo esc_html(get_option('atc_currency', 'INR')); ?> 0.00</div>

                <button type="submit" class="button button-primary atc-btn-block">Book Now</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ✅ NEW: Booking Button
     * Usage: [atc_booking_button service="tours" package_id="123" text="Book This Package"]
     */
    public static function booking_button_shortcode($atts) {
        $atts = shortcode_atts([
            'service' => 'tours',
            'package_id' => '',
            'text' => __('Book Now', 'advanced-travel-crm'),
            'class' => 'atc-btn atc-btn-primary',
        ], $atts);
        
        return sprintf(
            '<button class="%s atc-book-now" data-service="%s" data-package-id="%s">%s</button>',
            esc_attr($atts['class']),
            esc_attr($atts['service']),
            esc_attr($atts['package_id']),
            esc_html($atts['text'])
        );
    }
    
    /**
     * ✅ NEW: Custom Package Request Form (Premium)
     * Usage: [atc_custom_request]
     */
    public static function custom_request_shortcode($atts) {
        ob_start();
        ?>
        <div class="atc-custom-request-wrapper">
            <div class="atc-custom-request-header">
                <h2>✨ Request Custom Package</h2>
                <p>Tell us your dream vacation details and we'll create a personalized package for you</p>
            </div>
            
            <form class="atc-custom-request-form" method="post">
                <?php wp_nonce_field('atc_custom_request', '_atc_custom_nonce'); ?>
                <input type="hidden" name="atc_custom_request" value="1">
                
                <div class="atc-message-container"></div>
                
                <!-- Destination -->
                <div class="atc-form-field">
                    <label>🌍 Where do you want to go? *</label>
                    <input type="text" name="destination" required placeholder="e.g., Maldives, Switzerland, Bali">
                </div>
                
                <!-- Travel Dates -->
                <div class="atc-form-row atc-form-row-2col">
                    <div class="atc-form-field">
                        <label>📅 Travel Start Date</label>
                        <input type="date" name="date_from">
                    </div>
                    <div class="atc-form-field">
                        <label>📅 Travel End Date</label>
                        <input type="date" name="date_to">
                    </div>
                </div>
                
                <!-- Travelers -->
                <div class="atc-form-row atc-form-row-2col">
                    <div class="atc-form-field">
                        <label>👥 Adults</label>
                        <input type="number" name="adults" min="1" value="2">
                    </div>
                    <div class="atc-form-field">
                        <label>👶 Children</label>
                        <input type="number" name="children" min="0" value="0">
                    </div>
                </div>
                
                <!-- Budget -->
                <div class="atc-form-row atc-form-row-2col">
                    <div class="atc-form-field">
                        <label>💰 Budget (Min)</label>
                        <input type="number" name="budget_min" placeholder="50000">
                    </div>
                    <div class="atc-form-field">
                        <label>💰 Budget (Max)</label>
                        <input type="number" name="budget_max" placeholder="150000">
                    </div>
                </div>
                
                <!-- Preferences -->
                <div class="atc-form-field">
                    <label>✨ Special Preferences & Requirements</label>
                    <textarea name="preferences" rows="5" placeholder="Tell us about your travel style, must-have activities, accommodation preferences, dietary requirements, etc."></textarea>
                </div>
                
                <!-- Contact Details -->
                <div class="atc-form-field">
                    <label>👤 Your Name *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="atc-form-row atc-form-row-2col">
                    <div class="atc-form-field">
                        <label>📧 Email Address *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="atc-form-field">
                        <label>📱 Phone Number *</label>
                        <input type="tel" name="phone" required placeholder="+91...">
                    </div>
                </div>
                
                <button type="submit" class="atc-btn atc-btn-primary atc-btn-block atc-btn-large">
                    🚀 Submit Custom Request
                </button>
                
                <p class="atc-custom-request-note">
                    💡 Our travel experts will get back to you within 24 hours with a personalized quote
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ✅ Account Dashboard (fallback if ATC_User not loaded)
     */
    public static function account_dashboard_shortcode($atts) {
        if (class_exists('ATC_User')) {
            return ATC_User::dashboard_shortcode($atts);
        }
        
        if (!is_user_logged_in()) {
            return '<div class="atc-notice atc-notice-warning">
                <p>' . __('Please login to view your account.', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        return '<div class="atc-account-dashboard">
            <h2>My Account</h2>
            <p>Account features loading...</p>
        </div>';
    }
}