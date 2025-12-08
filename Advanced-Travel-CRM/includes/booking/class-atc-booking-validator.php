<?php
/**
 * ===========================================================================
 * class-atc-booking-validator.php
 * Location: includes/booking/class-atc-booking-validator.php
 * ===========================================================================
 */

if (!defined('ABSPATH')) exit;

class ATC_Booking_Validator {
    
    /**
     * ✅ Generate collision-resistant booking ID
     */
    public static function generate_booking_id() {
        global $wpdb;
        
        $max_attempts = 10;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            // Format: ATC-YYYYMMDD-HHMMSS-RAND
            $booking_id = sprintf(
                'ATC-%s-%s-%04d',
                date('Ymd'),
                date('His'),
                wp_rand(1000, 9999)
            );
            
            // Check if exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM " . ATC_TABLE_BOOKINGS . " WHERE booking_id = %s",
                $booking_id
            ));
            
            if (!$exists) {
                return $booking_id;
            }
            
            $attempt++;
            usleep(100000); // Wait 100ms before retry
        }
        
        // Fallback with UUID
        return 'ATC-' . wp_generate_uuid4();
    }
    
    /**
     * ✅ Check for duplicate bookings
     */
    public static function check_duplicate_booking($data) {
        global $wpdb;
        
        // Check for duplicate within last 5 minutes with same customer+service
        $duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE customer_email = %s 
            AND service = %s 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            LIMIT 1",
            $data['customer_email'],
            $data['service']
        ));
        
        if ($duplicate) {
            return new WP_Error(
                'duplicate_booking',
                __('You already have a pending booking for this service. Please wait 5 minutes before booking again.', 'advanced-travel-crm')
            );
        }
        
        return true;
    }
    
    /**
     * ✅ Validate booking data
     */
    public static function validate_booking_data($data) {
        $errors = [];
        
        // Required fields
        if (empty($data['customer_name'])) {
            $errors[] = __('Customer name is required', 'advanced-travel-crm');
        }
        
        if (empty($data['customer_email']) || !is_email($data['customer_email'])) {
            $errors[] = __('Valid email is required', 'advanced-travel-crm');
        }
        
        if (empty($data['service'])) {
            $errors[] = __('Service type is required', 'advanced-travel-crm');
        }
        
        // Validate service exists and is active
        $services = ATC_Services::get_services();
        if (!isset($services[$data['service']])) {
            $errors[] = __('Invalid service type', 'advanced-travel-crm');
        }
        
        // Validate price
        if (isset($data['price_total']) && $data['price_total'] < 0) {
            $errors[] = __('Invalid price', 'advanced-travel-crm');
        }
        
        // Validate dates if provided
        if (!empty($data['travel_date']) && strtotime($data['travel_date']) < strtotime('today')) {
            $errors[] = __('Travel date cannot be in the past', 'advanced-travel-crm');
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors));
        }
        
        return true;
    }
    
    /**
     * ✅ Check availability (basic implementation)
     */
    public static function check_availability($service, $date, $capacity_needed = 1) {
        // TODO: Implement actual availability checking
        // For now, always return true
        return true;
    }
    
    /**
     * ✅ Sanitize booking data
     */
    public static function sanitize_booking_data($data) {
        return [
            'booking_id' => isset($data['booking_id']) ? sanitize_text_field($data['booking_id']) : '',
            'service' => sanitize_text_field($data['service'] ?? ''),
            'user_id' => absint($data['user_id'] ?? get_current_user_id()),
            'customer_name' => sanitize_text_field($data['customer_name'] ?? $data['name'] ?? ''),
            'customer_email' => sanitize_email($data['customer_email'] ?? $data['email'] ?? ''),
            'customer_phone' => sanitize_text_field($data['customer_phone'] ?? $data['phone'] ?? ''),
            'price_total' => floatval($data['price_total'] ?? 0),
            'currency' => sanitize_text_field($data['currency'] ?? get_option('atc_currency', 'INR')),
            'payment_status' => sanitize_text_field($data['payment_status'] ?? 'unpaid'),
            'status' => sanitize_text_field($data['status'] ?? 'pending'),
            'travel_date' => !empty($data['travel_date']) ? sanitize_text_field($data['travel_date']) : null,
            'return_date' => !empty($data['return_date']) ? sanitize_text_field($data['return_date']) : null,
            'destination' => sanitize_text_field($data['destination'] ?? ''),
            'adults' => absint($data['adults'] ?? 1),
            'children' => absint($data['children'] ?? 0),
            'form_data' => $data, // Store full data
        ];
    }
}