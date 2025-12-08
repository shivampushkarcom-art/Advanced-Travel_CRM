<?php
/**
 * ATC Hotel API Integration Framework
 * Provides framework for integrating with hotel booking APIs
 * Supports manual booking mode and API integration mode
 */

if (!defined('ABSPATH')) exit;

class ATC_Hotel_API {
    
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
        
        // Register REST API endpoints for hotel search/booking
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
        
        self::$initialized = true;
    }
    
    /**
     * Register available hotel API providers
     */
    public static function register_api_providers() {
        // Booking.com API
        self::$api_providers['booking'] = [
            'name' => 'Booking.com API',
            'description' => 'World\'s leading hotel booking platform',
            'api_url' => 'https://distribution-xml.booking.com',
            'docs_url' => 'https://developers.booking.com',
            'features' => ['search', 'booking', 'pricing', 'availability'],
        ];
        
        // Expedia API
        self::$api_providers['expedia'] = [
            'name' => 'Expedia API',
            'description' => 'Global travel technology platform',
            'api_url' => 'https://api.expedia.com',
            'docs_url' => 'https://developer.expedia.com',
            'features' => ['search', 'booking', 'pricing', 'reviews'],
        ];
        
        // Hotels.com API
        self::$api_providers['hotels'] = [
            'name' => 'Hotels.com API',
            'description' => 'Hotel booking and travel services',
            'api_url' => 'https://api.hotels.com',
            'docs_url' => 'https://developer.hotels.com',
            'features' => ['search', 'booking', 'pricing'],
        ];
        
        // Agoda API
        self::$api_providers['agoda'] = [
            'name' => 'Agoda API',
            'description' => 'Asia-Pacific hotel booking platform',
            'api_url' => 'https://api.agoda.com',
            'docs_url' => 'https://developer.agoda.com',
            'features' => ['search', 'booking', 'pricing', 'availability'],
        ];
        
        // Custom API (for future integrations)
        self::$api_providers['custom'] = [
            'name' => 'Custom API',
            'description' => 'Integrate your own hotel booking API',
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
        $provider = get_option('atc_hotel_api_provider', 'manual');
        
        if ($provider === 'manual') {
            return null;
        }
        
        return isset(self::$api_providers[$provider]) ? $provider : null;
    }
    
    /**
     * Check if API integration is enabled
     */
    public static function is_api_enabled() {
        $provider = get_option('atc_hotel_api_provider', 'manual');
        return $provider !== 'manual' && !empty($provider);
    }
    
    /**
     * Add settings page for hotel API configuration
     */
    public static function add_settings_page() {
        add_submenu_page(
            'atc-dashboard',
            __('Hotel API Integration', 'advanced-travel-crm'),
            __('Hotel API', 'advanced-travel-crm'),
            'manage_options',
            'atc-hotel-api',
            [__CLASS__, 'render_settings_page']
        );
    }
    
    /**
     * Render API settings page
     */
    public static function render_settings_page() {
        // Handle form submission
        if (isset($_POST['atc_save_hotel_api_settings']) && check_admin_referer('atc_hotel_api_settings')) {
            $provider = sanitize_text_field($_POST['atc_hotel_api_provider'] ?? 'manual');
            $api_key = sanitize_text_field($_POST['atc_hotel_api_key'] ?? '');
            $api_secret = sanitize_text_field($_POST['atc_hotel_api_secret'] ?? '');
            $api_url = esc_url_raw($_POST['atc_hotel_api_url'] ?? '');
            $test_mode = isset($_POST['atc_hotel_api_test_mode']) ? 1 : 0;
            
            update_option('atc_hotel_api_provider', $provider);
            update_option('atc_hotel_api_key', $api_key);
            update_option('atc_hotel_api_secret', $api_secret);
            update_option('atc_hotel_api_url', $api_url);
            update_option('atc_hotel_api_test_mode', $test_mode);
            
            echo '<div class="notice notice-success"><p>' . __('Hotel API settings saved successfully!', 'advanced-travel-crm') . '</p></div>';
        }
        
        $current_provider = get_option('atc_hotel_api_provider', 'manual');
        $api_key = get_option('atc_hotel_api_key', '');
        $api_secret = get_option('atc_hotel_api_secret', '');
        $api_url = get_option('atc_hotel_api_url', '');
        $test_mode = get_option('atc_hotel_api_test_mode', 0);
        
        $providers = self::get_api_providers();
        ?>
        <div class="wrap">
            <h1><?php _e('Hotel API Integration', 'advanced-travel-crm'); ?></h1>
            
            <div class="atc-api-settings-container" style="max-width: 900px; margin-top: 20px;">
                <div class="atc-api-info-box" style="background: #f0f7ff; border-left: 4px solid #0066cc; padding: 20px; margin-bottom: 30px;">
                    <h2 style="margin-top: 0;">📡 Hotel API Integration Framework</h2>
                    <p>This framework allows you to integrate with hotel booking APIs for live search and booking. Currently, the system works in <strong>Manual Booking Mode</strong> where you receive booking requests and process them manually.</p>
                    <p><strong>To enable API integration:</strong> Select an API provider below and configure your API credentials. The system will automatically switch to API mode once configured.</p>
                </div>
                
                <form method="post" action="">
                    <?php wp_nonce_field('atc_hotel_api_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Booking Mode', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <select name="atc_hotel_api_provider" id="atc_hotel_api_provider" style="width: 300px;">
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
                        
                        <tbody id="atc_hotel_api_credentials" style="<?php echo $current_provider === 'manual' ? 'display: none;' : ''; ?>">
                            <tr>
                                <th><label><?php _e('API Key', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <input type="text" name="atc_hotel_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Enter your API key">
                                    <p class="description">Your API key from the selected provider</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label><?php _e('API Secret', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <input type="password" name="atc_hotel_api_secret" value="<?php echo esc_attr($api_secret); ?>" class="regular-text" placeholder="Enter your API secret">
                                    <p class="description">Your API secret (stored securely)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label><?php _e('API URL', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <input type="url" name="atc_hotel_api_url" value="<?php echo esc_url($api_url); ?>" class="regular-text" placeholder="https://api.example.com">
                                    <p class="description">Custom API endpoint URL (if using custom provider)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label><?php _e('Test Mode', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="atc_hotel_api_test_mode" value="1" <?php checked($test_mode, 1); ?>>
                                        <?php _e('Enable test/sandbox mode', 'advanced-travel-crm'); ?>
                                    </label>
                                    <p class="description">Use test environment for API calls (recommended for testing)</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="atc_save_hotel_api_settings" class="button button-primary">
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
            $('#atc_hotel_api_provider').on('change', function() {
                const provider = $(this).val();
                if (provider === 'manual') {
                    $('#atc_hotel_api_credentials').slideUp();
                } else {
                    $('#atc_hotel_api_credentials').slideDown();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Register REST API routes for hotel operations
     */
    public static function register_rest_routes() {
        // Hotel search endpoint (for future API integration)
        register_rest_route('atc/v1', '/hotels/search', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'search_hotels'],
            'permission_callback' => '__return_true',
        ]);
        
        // Hotel booking endpoint (for future API integration)
        register_rest_route('atc/v1', '/hotels/book', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'book_hotel'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
        
        // Hotel availability check
        register_rest_route('atc/v1', '/hotels/availability', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'check_availability'],
            'permission_callback' => '__return_true',
        ]);
        
        // Hotel room rates
        register_rest_route('atc/v1', '/hotels/rates', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'get_room_rates'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Search hotels (API integration endpoint)
     */
    public static function search_hotels($request) {
        $params = $request->get_json_params();
        
        // Check if API is enabled
        if (!self::is_api_enabled()) {
            return new WP_Error('api_disabled', __('Hotel API integration is not enabled. Please configure it in Hotel API settings.', 'advanced-travel-crm'), ['status' => 400]);
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
     * Book hotel (API integration endpoint)
     */
    public static function book_hotel($request) {
        $params = $request->get_json_params();
        
        // Check if API is enabled
        if (!self::is_api_enabled()) {
            // For manual mode, create a booking request
            // This will be processed manually by admin
            $booking_data = [
                'service' => 'hotels',
                'package_id' => intval($params['package_id'] ?? 0),
                'customer_name' => sanitize_text_field($params['name'] ?? ''),
                'customer_email' => sanitize_email($params['email'] ?? ''),
                'customer_phone' => sanitize_text_field($params['phone'] ?? ''),
                'adults' => intval($params['adults'] ?? 1),
                'children' => intval($params['children'] ?? 0),
                'check_in' => sanitize_text_field($params['check_in'] ?? ''),
                'check_out' => sanitize_text_field($params['check_out'] ?? ''),
                'rooms' => intval($params['rooms'] ?? 1),
                'room_type' => sanitize_text_field($params['room_type'] ?? 'Standard'),
                'status' => 'pending',
            ];
            
            // Save booking request (manual processing)
            global $wpdb;
            $booking_id = 'HTL-' . date('Ymd') . '-' . wp_rand(1000, 9999);
            
            $wpdb->insert(
                ATC_TABLE_BOOKINGS,
                [
                    'booking_id' => $booking_id,
                    'service' => 'hotels',
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
                'message' => __('Hotel booking request received. You will be contacted shortly.', 'advanced-travel-crm'),
                'booking_id' => $booking_id,
                'mode' => 'manual',
            ]);
        }
        
        // API mode - will be implemented based on provider
        return rest_ensure_response([
            'success' => false,
            'message' => __('API integration is configured but not yet implemented. Please use manual booking mode.', 'advanced-travel-crm'),
        ]);
    }
    
    /**
     * Check hotel availability
     */
    public static function check_availability($request) {
        $params = $request->get_json_params();
        $package_id = intval($params['package_id'] ?? 0);
        $check_in = sanitize_text_field($params['check_in'] ?? '');
        $check_out = sanitize_text_field($params['check_out'] ?? '');
        
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
                'message' => __('Hotel package not available.', 'advanced-travel-crm'),
            ]);
        }
        
        // In manual mode, assume available if package is active
        // In API mode, this would check actual availability via API
        return rest_ensure_response([
            'available' => true,
            'package' => [
                'id' => $package['id'],
                'name' => $package['name'],
                'price' => floatval($package['price']),
            ],
            'check_in' => $check_in,
            'check_out' => $check_out,
        ]);
    }
    
    /**
     * Get room rates (for future API integration)
     */
    public static function get_room_rates($request) {
        $params = $request->get_json_params();
        $package_id = intval($params['package_id'] ?? 0);
        $check_in = sanitize_text_field($params['check_in'] ?? '');
        $check_out = sanitize_text_field($params['check_out'] ?? '');
        $adults = intval($params['adults'] ?? 1);
        $children = intval($params['children'] ?? 0);
        $rooms = intval($params['rooms'] ?? 1);
        
        if (!$package_id) {
            return new WP_Error('invalid_package', __('Package ID is required.', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d AND status = 'active'",
            $package_id
        ), ARRAY_A);
        
        if (!$package) {
            return new WP_Error('package_not_found', __('Hotel package not found.', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        // Calculate nights
        $nights = 1;
        if (!empty($check_in) && !empty($check_out)) {
            $check_in_date = new DateTime($check_in);
            $check_out_date = new DateTime($check_out);
            $nights = $check_in_date->diff($check_out_date)->days;
            $nights = max(1, $nights);
        }
        
        // Base price per night
        $price_per_night = floatval($package['price']);
        $total_price = $price_per_night * $nights * $rooms;
        
        return rest_ensure_response([
            'success' => true,
            'package_id' => $package_id,
            'price_per_night' => $price_per_night,
            'nights' => $nights,
            'rooms' => $rooms,
            'total_price' => $total_price,
            'currency' => get_option('atc_currency_symbol', '₹'),
            'check_in' => $check_in,
            'check_out' => $check_out,
            'adults' => $adults,
            'children' => $children,
        ]);
    }
}

