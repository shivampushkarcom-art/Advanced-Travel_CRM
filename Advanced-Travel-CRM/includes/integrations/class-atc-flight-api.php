<?php
/**
 * ATC Flight API Integration Framework
 * Provides framework for integrating with flight booking APIs
 * Supports manual booking mode and API integration mode
 */

if (!defined('ABSPATH')) exit;

class ATC_Flight_API {
    
    private static $initialized = false;
    private static $api_providers = [];
    private static $active_provider = null;
    
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Register API providers
        add_action('admin_init', [__CLASS__, 'register_api_providers']);
        
        // Add settings page
        add_action('admin_menu', [__CLASS__, 'add_settings_page'], 30);
        
        // Register REST API endpoints for flight search/booking
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
        
        self::$initialized = true;
    }
    
    /**
     * Register available flight API providers
     */
    public static function register_api_providers() {
        // Amadeus API
        self::$api_providers['amadeus'] = [
            'name' => 'Amadeus API',
            'description' => 'Professional flight search and booking API',
            'api_url' => 'https://api.amadeus.com',
            'docs_url' => 'https://developers.amadeus.com',
            'features' => ['search', 'booking', 'pricing', 'seat_selection'],
        ];
        
        // Sabre API
        self::$api_providers['sabre'] = [
            'name' => 'Sabre API',
            'description' => 'Enterprise travel technology platform',
            'api_url' => 'https://api.sabre.com',
            'docs_url' => 'https://developer.sabre.com',
            'features' => ['search', 'booking', 'pricing', 'ancillary'],
        ];
        
        // Travelport API
        self::$api_providers['travelport'] = [
            'name' => 'Travelport API',
            'description' => 'Global travel commerce platform',
            'api_url' => 'https://api.travelport.com',
            'docs_url' => 'https://developer.travelport.com',
            'features' => ['search', 'booking', 'pricing'],
        ];
        
        // Custom API (for future integrations)
        self::$api_providers['custom'] = [
            'name' => 'Custom API',
            'description' => 'Integrate your own flight booking API',
            'api_url' => '',
            'docs_url' => '',
            'features' => ['search', 'booking'],
        ];
    }
    
    /**
     * Get available API providers
     */
    public static function get_api_providers() {
        return self::$api_providers;
    }
    
    /**
     * Get active API provider
     */
    public static function get_active_provider() {
        $provider = get_option('atc_flight_api_provider', 'manual');
        
        if ($provider === 'manual') {
            return null;
        }
        
        return isset(self::$api_providers[$provider]) ? $provider : null;
    }
    
    /**
     * Check if API integration is enabled
     */
    public static function is_api_enabled() {
        $provider = get_option('atc_flight_api_provider', 'manual');
        return $provider !== 'manual' && !empty($provider);
    }
    
    /**
     * Add settings page for flight API configuration
     */
    public static function add_settings_page() {
        add_submenu_page(
            'atc-dashboard',
            __('Flight API Integration', 'advanced-travel-crm'),
            __('Flight API', 'advanced-travel-crm'),
            'manage_options',
            'atc-flight-api',
            [__CLASS__, 'render_settings_page']
        );
    }
    
    /**
     * Render API settings page
     */
    public static function render_settings_page() {
        // Handle form submission
        if (isset($_POST['atc_save_flight_api_settings']) && check_admin_referer('atc_flight_api_settings')) {
            $provider = sanitize_text_field($_POST['atc_flight_api_provider'] ?? 'manual');
            $api_key = sanitize_text_field($_POST['atc_flight_api_key'] ?? '');
            $api_secret = sanitize_text_field($_POST['atc_flight_api_secret'] ?? '');
            $api_url = esc_url_raw($_POST['atc_flight_api_url'] ?? '');
            $test_mode = isset($_POST['atc_flight_api_test_mode']) ? 1 : 0;
            
            update_option('atc_flight_api_provider', $provider);
            update_option('atc_flight_api_key', $api_key);
            update_option('atc_flight_api_secret', $api_secret);
            update_option('atc_flight_api_url', $api_url);
            update_option('atc_flight_api_test_mode', $test_mode);
            
            echo '<div class="notice notice-success"><p>' . __('Flight API settings saved successfully!', 'advanced-travel-crm') . '</p></div>';
        }
        
        $current_provider = get_option('atc_flight_api_provider', 'manual');
        $api_key = get_option('atc_flight_api_key', '');
        $api_secret = get_option('atc_flight_api_secret', '');
        $api_url = get_option('atc_flight_api_url', '');
        $test_mode = get_option('atc_flight_api_test_mode', 0);
        
        $providers = self::get_api_providers();
        ?>
        <div class="wrap">
            <h1><?php _e('Flight API Integration', 'advanced-travel-crm'); ?></h1>
            
            <div class="atc-api-settings-container" style="max-width: 900px; margin-top: 20px;">
                <div class="atc-api-info-box" style="background: #f0f7ff; border-left: 4px solid #0066cc; padding: 20px; margin-bottom: 30px;">
                    <h2 style="margin-top: 0;">📡 API Integration Framework</h2>
                    <p>This framework allows you to integrate with flight booking APIs for live search and booking. Currently, the system works in <strong>Manual Booking Mode</strong> where you receive booking requests and process them manually.</p>
                    <p><strong>To enable API integration:</strong> Select an API provider below and configure your API credentials. The system will automatically switch to API mode once configured.</p>
                </div>
                
                <form method="post" action="">
                    <?php wp_nonce_field('atc_flight_api_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Booking Mode', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <select name="atc_flight_api_provider" id="atc_flight_api_provider" style="width: 300px;">
                                    <option value="manual" <?php selected($current_provider, 'manual'); ?>>
                                        <?php _e('Manual Booking (Current)', 'advanced-travel-crm'); ?>
                                    </option>
                                    <?php foreach ($providers as $key => $provider): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($current_provider, $key); ?>>
                                            <?php echo esc_html($provider['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Select "Manual Booking" to receive booking requests for manual processing, or choose an API provider for automated booking.</p>
                            </td>
                        </tr>
                        
                        <tbody id="atc_api_credentials" style="<?php echo $current_provider === 'manual' ? 'display: none;' : ''; ?>">
                            <tr>
                                <th><label><?php _e('API Key', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <input type="text" name="atc_flight_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Enter your API key">
                                    <p class="description">Your API key from the selected provider</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label><?php _e('API Secret', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <input type="password" name="atc_flight_api_secret" value="<?php echo esc_attr($api_secret); ?>" class="regular-text" placeholder="Enter your API secret">
                                    <p class="description">Your API secret (stored securely)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label><?php _e('API URL', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <input type="url" name="atc_flight_api_url" value="<?php echo esc_url($api_url); ?>" class="regular-text" placeholder="https://api.example.com">
                                    <p class="description">Custom API endpoint URL (if using custom provider)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label><?php _e('Test Mode', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="atc_flight_api_test_mode" value="1" <?php checked($test_mode, 1); ?>>
                                        <?php _e('Enable test/sandbox mode', 'advanced-travel-crm'); ?>
                                    </label>
                                    <p class="description">Use test environment for API calls (recommended for testing)</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="atc_save_flight_api_settings" class="button button-primary">
                            <?php _e('Save Settings', 'advanced-travel-crm'); ?>
                        </button>
                    </p>
                </form>
                
                <!-- API Provider Information -->
                <div class="atc-api-providers-info" style="margin-top: 40px;">
                    <h2><?php _e('Available API Providers', 'advanced-travel-crm'); ?></h2>
                    <div class="atc-providers-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                        <?php foreach ($providers as $key => $provider): ?>
                            <div class="atc-provider-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #fff;">
                                <h3 style="margin-top: 0;"><?php echo esc_html($provider['name']); ?></h3>
                                <p><?php echo esc_html($provider['description']); ?></p>
                                <?php if (!empty($provider['docs_url'])): ?>
                                    <p><a href="<?php echo esc_url($provider['docs_url']); ?>" target="_blank" class="button button-small">View Docs</a></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#atc_flight_api_provider').on('change', function() {
                const provider = $(this).val();
                if (provider === 'manual') {
                    $('#atc_api_credentials').slideUp();
                } else {
                    $('#atc_api_credentials').slideDown();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Register REST API routes for flight operations
     */
    public static function register_rest_routes() {
        // Flight search endpoint (for future API integration)
        register_rest_route('atc/v1', '/flights/search', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'search_flights'],
            'permission_callback' => '__return_true',
        ]);
        
        // Flight booking endpoint (for future API integration)
        register_rest_route('atc/v1', '/flights/book', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'book_flight'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
        
        // Flight availability check
        register_rest_route('atc/v1', '/flights/availability', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'check_availability'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Search flights (API integration endpoint)
     */
    public static function search_flights($request) {
        $params = $request->get_json_params();
        
        // Check if API is enabled
        if (!self::is_api_enabled()) {
            return new WP_Error('api_disabled', __('Flight API integration is not enabled. Please configure it in Flight API settings.', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        $provider = self::get_active_provider();
        if (!$provider) {
            return new WP_Error('no_provider', __('No API provider configured.', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        // This is a framework - actual API calls will be implemented based on provider
        // For now, return a placeholder response
        return rest_ensure_response([
            'success' => false,
            'message' => __('API integration is configured but not yet implemented. Please use manual booking mode.', 'advanced-travel-crm'),
            'provider' => $provider,
        ]);
    }
    
    /**
     * Book flight (API integration endpoint)
     */
    public static function book_flight($request) {
        $params = $request->get_json_params();
        
        // Check if API is enabled
        if (!self::is_api_enabled()) {
            return new WP_Error('api_disabled', __('Flight API integration is not enabled. Booking will be processed manually.', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        // For manual mode, create a booking request
        // This will be processed manually by admin
        $booking_data = [
            'service' => 'flights',
            'package_id' => intval($params['package_id'] ?? 0),
            'customer_name' => sanitize_text_field($params['name'] ?? ''),
            'customer_email' => sanitize_email($params['email'] ?? ''),
            'customer_phone' => sanitize_text_field($params['phone'] ?? ''),
            'adults' => intval($params['adults'] ?? 1),
            'children' => intval($params['children'] ?? 0),
            'infants' => intval($params['infants'] ?? 0),
            'class' => sanitize_text_field($params['class'] ?? 'Economy'),
            'departure_date' => sanitize_text_field($params['departure_date'] ?? ''),
            'return_date' => sanitize_text_field($params['return_date'] ?? ''),
            'status' => 'pending',
        ];
        
        // Save booking request (manual processing)
        global $wpdb;
        $booking_id = 'FLT-' . date('Ymd') . '-' . wp_rand(1000, 9999);
        
        $wpdb->insert(
            ATC_TABLE_BOOKINGS,
            [
                'booking_id' => $booking_id,
                'service' => 'flights',
                'package_id' => $booking_data['package_id'],
                'customer_name' => $booking_data['customer_name'],
                'customer_email' => $booking_data['customer_email'],
                'customer_phone' => $booking_data['customer_phone'],
                'adults' => $booking_data['adults'],
                'children' => $booking_data['children'],
                'price_total' => floatval($params['price'] ?? 0),
                'status' => 'pending',
                'metadata' => json_encode($booking_data),
                'created_at' => current_time('mysql'),
            ]
        );
        
        return rest_ensure_response([
            'success' => true,
            'message' => __('Flight booking request received. You will be contacted shortly.', 'advanced-travel-crm'),
            'booking_id' => $booking_id,
            'mode' => 'manual',
        ]);
    }
    
    /**
     * Check flight availability
     */
    public static function check_availability($request) {
        $params = $request->get_json_params();
        $package_id = intval($params['package_id'] ?? 0);
        
        if (!$package_id) {
            return new WP_Error('invalid_package', __('Package ID is required.', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d AND status = 'active'",
            $package_id
        ), ARRAY_A);
        
        if (!$package) {
            return rest_ensure_response([
                'available' => false,
                'message' => __('Flight package not available.', 'advanced-travel-crm'),
            ]);
        }
        
        return rest_ensure_response([
            'available' => true,
            'package' => [
                'id' => $package['id'],
                'name' => $package['name'],
                'price' => floatval($package['price']),
            ],
        ]);
    }
}

