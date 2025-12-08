<?php
/**
 * ========================================
 * FILE: class-atc-security.php
 * ========================================
 */
class ATC_Security {

    private const RATE_LIMIT_KEY_PREFIX = 'atc_rate_limit_';
    private const RATE_LIMIT_MAX_REQUESTS = 100;
    private const RATE_LIMIT_WINDOW = HOUR_IN_SECONDS;

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'check_rate_limit'], 0);
    }
    
    public static function check_rate_limit() {
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return;
        }

        if (current_user_can('manage_atc')) {
            return; // Skip for privileged users
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        if (empty($ip)) {
            return; // Unable to determine IP, skip limiting
        }

        $key = self::RATE_LIMIT_KEY_PREFIX . md5($ip);
        $requests = (int) get_transient($key);

        if ($requests >= self::RATE_LIMIT_MAX_REQUESTS) {
            status_header(429);
            header('Retry-After: ' . self::RATE_LIMIT_WINDOW);

            wp_send_json_error([
                'code' => 'atc_rate_limited',
                'message' => __('Rate limit exceeded. Please try again later.', 'advanced-travel-crm'),
            ], 429);
        }

        set_transient($key, $requests + 1, self::RATE_LIMIT_WINDOW);
    }
    
    public static function sanitize_phone($phone) {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
    
    public static function validate_booking_data($data) {
        $errors = [];
        
        if (empty($data['customer_name'])) {
            $errors[] = __('Name is required', 'advanced-travel-crm');
        }
        
        if (empty($data['customer_email']) || !is_email($data['customer_email'])) {
            $errors[] = __('Valid email is required', 'advanced-travel-crm');
        }
        
        if (empty($data['service'])) {
            $errors[] = __('Service is required', 'advanced-travel-crm');
        }
        
        return $errors;
    }
}
