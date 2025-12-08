<?php
/**
 * ATC REST API
 * Handles all REST API endpoints for bookings, searches, and services
 */

class ATC_REST {

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        $auth_cb = function() {
            $nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
            return wp_verify_nonce($nonce, 'wp_rest');
        };

        // Booking endpoint
        register_rest_route('atc/v1', '/book', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'book_service'],
            'permission_callback' => $auth_cb,
        ]);

        // Search lead logging
        register_rest_route('atc/v1', '/search-lead', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'log_search'],
            'permission_callback' => $auth_cb,
        ]);

        // Get all service configs
        register_rest_route('atc/v1', '/services', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_services'],
            'permission_callback' => '__return_true',
        ]);

        // Get single service config
        register_rest_route('atc/v1', '/service/(?P<service>[a-zA-Z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_service'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * ✅ FIXED: Book service with proper validation
     */
    public static function book_service($request) {
        $params = $request->get_json_params();

        // Normalize fields
        $name   = sanitize_text_field($params['name'] ?? $params['customer_name'] ?? '');
        $email  = sanitize_email($params['email'] ?? $params['customer_email'] ?? '');
        $phone  = sanitize_text_field($params['phone'] ?? $params['customer_phone'] ?? '');
        $service= sanitize_text_field($params['service'] ?? '');

        // Basic validation
        if (empty($name) || empty($email)) {
            return new WP_Error('missing_fields', 'Name and email are required', ['status' => 400]);
        }

        // Validate service
        $services = ATC_Services::get_services();
        $statuses = class_exists('ATC_Service_Manager') ? ATC_Service_Manager::get_services_status() : [];
        if (empty($service) || empty($services[$service]) || (isset($statuses[$service]) && !$statuses[$service])) {
            return new WP_Error('invalid_service', 'Invalid or inactive service', ['status' => 400]);
        }

        // ✅ FIX: Generate unique booking ID using validator
        if (class_exists('ATC_Booking_Validator')) {
            $booking_id = ATC_Booking_Validator::generate_booking_id();
        } else {
            $booking_id = 'ATC-' . date('Ymd-His') . '-' . wp_rand(1000, 9999);
        }

        // Prepare booking data
        $booking_data = [
            'booking_id'     => $booking_id,
            'service'        => $service,
            'user_id'        => get_current_user_id() ?: null,
            'customer_name'  => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'price_total'    => floatval($params['price_total'] ?? 0),
            'currency'       => get_option('atc_currency', 'INR'),
            'form_data'      => $params,
            'package_id'     => intval($params['package_id'] ?? 0),
            'destination'    => sanitize_text_field($params['destination'] ?? ''),
            'travel_date'    => !empty($params['travel_date']) ? sanitize_text_field($params['travel_date']) : null,
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

        // ✅ FIX: Validate booking data
        if (class_exists('ATC_Booking_Validator')) {
            $validation = ATC_Booking_Validator::validate_booking_data($booking_data);
            if (is_wp_error($validation)) {
                return $validation;
            }

            // Check for duplicates
            $duplicate_check = ATC_Booking_Validator::check_duplicate_booking($booking_data);
            if (is_wp_error($duplicate_check)) {
                return $duplicate_check;
            }
        }

        // Insert booking
        $id = ATC_Bookings::insert_booking($booking_data);

        if ($id) {
            // ✅ FIX: Use proper notification system (NOT ATC_Notify)
            do_action('atc_booking_created', $id, $booking_data);

            return rest_ensure_response([
                'success'    => true,
                'message'    => 'Booking created successfully!',
                'booking_id' => $booking_id,
                'record_id'  => $id,
                'price_total'=> $booking_data['price_total'],
                'service'    => $booking_data['service'],
            ]);
        }

        return new WP_Error('booking_failed', 'Failed to create booking', ['status' => 500]);
    }

    public static function log_search($request) {
        $params = $request->get_json_params();
        $params['user_id'] = get_current_user_id() ?: null;

        $id = ATC_Bookings::insert_search_log($params);

        return rest_ensure_response([
            'success' => true,
            'log_id'  => $id,
        ]);
    }

    public static function get_services($request) {
        $configs = ATC_Config_Loader::get_all_configs();
        return rest_ensure_response($configs);
    }

    public static function get_service($request) {
        $service = sanitize_text_field($request['service']);
        $config  = ATC_Config_Loader::load_service_config($service);

        if (empty($config)) {
            return new WP_Error('not_found', 'Service not found', ['status' => 404]);
        }

        return rest_ensure_response($config);
    }
}
