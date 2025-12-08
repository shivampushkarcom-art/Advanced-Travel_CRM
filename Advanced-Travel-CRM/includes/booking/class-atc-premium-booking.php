<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * ATC Premium Booking Wizard
 * Step-by-step booking flow with Make My Trip-like UI
 * ═══════════════════════════════════════════════════════════════════
 */

if (!defined('ABSPATH')) exit;

class ATC_Premium_Booking {
    
    public static function init() {
        add_action('wp_footer', [__CLASS__, 'render_booking_modal']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }
    
    public static function enqueue_assets() {
        wp_enqueue_style('atc-premium-booking', ATC_ASSETS_URL . 'css/atc-premium-booking.css', [], ATC_VERSION);
        wp_enqueue_style('atc-premium-search', ATC_ASSETS_URL . 'css/atc-premium-search.css', [], ATC_VERSION);
        wp_enqueue_script('atc-premium-booking', ATC_ASSETS_URL . 'js/atc-premium-booking.js', ['jquery'], ATC_VERSION, true);
        
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
        wp_localize_script('atc-premium-booking', 'atcPremiumBookingConfig', [
            'restUrl' => esc_url_raw($rest_url),
            'nonce' => wp_create_nonce('wp_rest'),
            'currency' => get_option('atc_currency_symbol', '₹'),
            'currencyCode' => get_option('atc_currency', 'INR'),
            'paymentEnabled' => get_option('atc_payment_enabled', 0),
        ]);
        
        // Also set it as atcPremiumBooking for backward compatibility
        wp_add_inline_script('atc-premium-booking', '
            if (typeof atcPremiumBooking === "undefined" || !atcPremiumBooking.restUrl) {
                var atcPremiumBooking = atcPremiumBookingConfig;
                window._atcBookingConfigOriginal = atcPremiumBookingConfig;
            }
        ', 'after');
    }
    
    public static function register_routes() {
        register_rest_route('atc/v1', '/booking/premium', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_booking'],
            'permission_callback' => '__return_true',
        ]);
        
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
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
            $package_id
        ), ARRAY_A);
        
        // If not found, try services
        if (!$package) {
            $package = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_SERVICES . " WHERE id = %d",
                $package_id
            ), ARRAY_A);
        }
        
        if (!$package) {
            return new WP_Error('not_found', 'Package not found', ['status' => 404]);
        }
        
        return rest_ensure_response($package);
    }
    
    public static function create_booking($request) {
        // Check if login is required for bookings
        if (class_exists('ATC_Feature_Manager')) {
            $require_login = ATC_Feature_Manager::is_enabled('require_login_booking');
            if ($require_login && !is_user_logged_in()) {
                return new WP_Error(
                    'login_required',
                    __('Please login to create a booking.', 'advanced-travel-crm'),
                    ['status' => 401, 'login_url' => home_url('/login/')]
                );
            }
        }
        
        $params = $request->get_json_params();
        
        // Validate required fields
        if (empty($params['customer_name']) || empty($params['customer_email'])) {
            return new WP_Error('missing_fields', 'Name and email are required', ['status' => 400]);
        }
        
        // Generate booking ID
        if (class_exists('ATC_Booking_Validator')) {
            $booking_id = ATC_Booking_Validator::generate_booking_id();
        } else {
            $booking_id = 'ATC-' . date('Ymd-His') . '-' . wp_rand(1000, 9999);
        }
        
        // Prepare booking data with ALL form fields
        $booking_data = [
            'booking_id' => $booking_id,
            'service' => sanitize_text_field($params['service'] ?? ''),
            'package_id' => intval($params['package_id'] ?? 0),
            'user_id' => get_current_user_id() ?: null,
            'customer_name' => sanitize_text_field($params['customer_name']),
            'customer_email' => sanitize_email($params['customer_email']),
            'customer_phone' => sanitize_text_field($params['customer_phone'] ?? ''),
            'destination' => sanitize_text_field($params['destination'] ?? $params['package'] ?? ''),
            'travel_date' => !empty($params['travel_date']) ? sanitize_text_field($params['travel_date']) : null,
            'return_date' => !empty($params['return_date']) ? sanitize_text_field($params['return_date']) : null,
            'adults' => intval($params['adults'] ?? 1),
            'children' => intval($params['children'] ?? 0),
            'child_ages' => sanitize_text_field($params['child_ages'] ?? ''),
            'hotel_type' => sanitize_text_field($params['hotel_type'] ?? ''),
            'trip_type' => sanitize_text_field($params['trip_type'] ?? ''),
            'special_requirements' => sanitize_textarea_field($params['special_requirements'] ?? $params['requirements'] ?? $params['message'] ?? ''),
            'price_total' => floatval($params['price_total'] ?? 0),
            'currency' => get_option('atc_currency', 'INR'),
            'form_data' => $params,
            'status' => 'pending',
        ];

        // Fetch Package Details for Snapshot
        if (!empty($booking_data['package_id'])) {
            global $wpdb;
            
            // Try custom packages first
            $package_row = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
                $booking_data['package_id']
            ), ARRAY_A);
            
            // If not found, try services
            if (!$package_row) {
                $package_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM " . ATC_TABLE_SERVICES . " WHERE id = %d",
                    $booking_data['package_id']
                ), ARRAY_A);
            }
            
            if ($package_row) {
                $booking_data['package_name'] = $package_row['name'] ?? $package_row['package_name'] ?? '';
                $booking_data['package_snapshot'] = json_encode($package_row);
                
                // Backfill destination if missing in params
                if (empty($booking_data['destination']) && !empty($package_row['destination'])) {
                    $booking_data['destination'] = $package_row['destination'];
                }
            }
        }
        
        // Insert booking
        try {
            $id = ATC_Bookings::insert_booking($booking_data);
            
            if ($id) {
                // Trigger booking created action
                do_action('atc_booking_created', $id, $booking_data);
                
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Booking created successfully!',
                    'booking_id' => $booking_id,
                    'record_id' => $id,
                    'price_total' => $booking_data['price_total'],
                ]);
            }
            
            // Get the actual error from the database
            global $wpdb;
            $db_error = $wpdb->last_error;
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('error', 'Premium booking failed: ' . ($db_error ?: 'Unknown error'));
            }
            
            return new WP_Error('booking_failed', 'Failed to create booking: ' . ($db_error ?: 'Unknown database error'), ['status' => 500]);
        } catch (Exception $e) {
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('error', 'Premium booking exception: ' . $e->getMessage());
            }
            return new WP_Error('booking_exception', 'Booking error: ' . $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Render booking modal in footer
     */
    public static function render_booking_modal() {
        ?>
        <div id="atc-premium-booking-modal" class="atc-premium-booking-modal">
            <div class="atc-premium-booking-overlay"></div>
            <div class="atc-premium-booking-content">
                
                <!-- Header -->
                <div class="atc-premium-booking-header">
                    <h2 id="atc-booking-title">Complete Your Booking</h2>
                    <button class="atc-premium-booking-close">&times;</button>
                </div>
                
                <!-- Booking Steps -->
                <div class="atc-premium-booking-steps">
                    <div class="atc-booking-step active" data-step="1">
                        <div class="atc-step-number">1</div>
                        <div class="atc-step-info">
                            <div class="atc-step-title">Package Details</div>
                            <div class="atc-step-desc">Review your selection</div>
                        </div>
                    </div>
                    <div class="atc-booking-step" data-step="2">
                        <div class="atc-step-number">2</div>
                        <div class="atc-step-info">
                            <div class="atc-step-title">Traveler Info</div>
                            <div class="atc-step-desc">Enter your details</div>
                        </div>
                    </div>
                    <div class="atc-booking-step" data-step="3">
                        <div class="atc-step-number">3</div>
                        <div class="atc-step-info">
                            <div class="atc-step-title">Payment</div>
                            <div class="atc-step-desc">Complete payment</div>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Body -->
                <div class="atc-premium-booking-body">
                    
                    <!-- Step 1: Package Details -->
                    <div class="atc-booking-form-section active" data-step="1">
                        <div id="atc-booking-package-details">
                            <div class="atc-premium-loading">
                                <div class="atc-premium-spinner"></div>
                                <p>Loading package details...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Traveler Information -->
                    <div class="atc-booking-form-section" data-step="2">
                        <form id="atc-booking-traveler-form" class="atc-premium-booking-form">
                            
                            <div class="atc-premium-form-group">
                                <label>Full Name *</label>
                                <input type="text" name="customer_name" required placeholder="Enter your full name">
                            </div>
                            
                            <div class="atc-premium-form-group">
                                <label>Email Address *</label>
                                <input type="email" name="customer_email" required placeholder="your.email@example.com">
                            </div>
                            
                            <div class="atc-premium-form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="customer_phone" required placeholder="+91 9876543210">
                            </div>
                            
                            <div class="atc-premium-form-group">
                                <label>Number of Adults</label>
                                <input type="number" name="adults" min="1" value="2" required>
                            </div>
                            
                            <div class="atc-premium-form-group">
                                <label>Number of Children</label>
                                <input type="number" name="children" min="0" value="0">
                            </div>
                            
                            <div class="atc-premium-form-group">
                                <label>Special Requests</label>
                                <textarea name="special_requests" rows="4" placeholder="Any special requests or requirements..."></textarea>
                            </div>
                            
                        </form>
                    </div>
                    
                    <!-- Step 3: Payment -->
                    <div class="atc-booking-form-section" data-step="3">
                        <div id="atc-booking-payment-section">
                            <div class="atc-premium-loading">
                                <div class="atc-premium-spinner"></div>
                                <p>Preparing payment...</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Footer -->
                <div class="atc-premium-booking-footer">
                    <div class="atc-booking-total">
                        <span class="atc-booking-total-label">Total Amount</span>
                        <span id="atc-booking-total-amount">₹0</span>
                    </div>
                    
                    <div class="atc-booking-nav">
                        <button class="atc-btn-nav atc-btn-nav-back" id="atc-booking-back-btn" style="display: none;">
                            ← Back
                        </button>
                        <button class="atc-btn-nav atc-btn-nav-next" id="atc-booking-next-btn">
                            Next →
                        </button>
                        <button class="atc-btn-nav atc-btn-nav-next" id="atc-booking-submit-btn" style="display: none;">
                            Complete Booking
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
}

