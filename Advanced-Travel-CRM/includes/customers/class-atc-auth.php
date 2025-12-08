<?php
/**
 * ATC Authentication System
 * Custom login/logout handling with security features
 * 
 * Features:
 * - Custom login processing
 * - Role-based redirects
 * - Login rate limiting
 * - Remember me functionality
 * - Session management
 * - Failed login tracking
 */

if (!defined('ABSPATH')) exit;

class ATC_Auth {
    
    public static function init() {
        // Login/Logout hooks
        add_filter('login_redirect', [__CLASS__, 'login_redirect'], 10, 3);
        add_filter('login_url', [__CLASS__, 'custom_login_url'], 10, 2);
        add_action('wp_login', [__CLASS__, 'on_login'], 10, 2);
        add_action('wp_logout', [__CLASS__, 'on_logout']);
        add_action('wp_login_failed', [__CLASS__, 'on_login_failed']);
        
        // Security
        add_action('authenticate', [__CLASS__, 'check_rate_limit'], 30, 3);
        
        // Admin bar
        add_filter('show_admin_bar', [__CLASS__, 'hide_admin_bar']);
    }
    
    /**
     * Override login URL to use custom login page
     */
    public static function custom_login_url($login_url, $redirect) {
        $custom_login_url = home_url('/login/');
        
        if (!empty($redirect)) {
            $custom_login_url = add_query_arg('redirect_to', urlencode($redirect), $custom_login_url);
        }
        
        return $custom_login_url;
    }
    
    /**
     * Custom login redirect
     */
    public static function login_redirect($redirect_to, $request, $user) {
        // Check if user object exists
        if (!isset($user->ID)) {
            return $redirect_to;
        }
        
        // Admin users go to admin
        if (user_can($user, 'manage_options')) {
            return admin_url('admin.php?page=atc-dashboard');
        }
        
        // Regular customers go to account dashboard
        if (user_can($user, 'read')) {
            return home_url('/my-account/');
        }
        
        return $redirect_to;
    }
    
    /**
     * On successful login
     */
    public static function on_login($user_login, $user) {
        // Clear failed login attempts
        delete_transient('atc_login_attempts_' . self::get_client_ip());
        
        // Update last login time
        update_user_meta($user->ID, 'atc_last_login', current_time('mysql'));
        
        // Log login activity
        self::log_activity($user->ID, 'login', 'User logged in');
    }
    
    /**
     * On logout
     */
    public static function on_logout() {
        $user_id = get_current_user_id();
        if ($user_id) {
            self::log_activity($user_id, 'logout', 'User logged out');
        }
    }
    
    /**
     * On login failed
     */
    public static function on_login_failed($username) {
        $ip = self::get_client_ip();
        $attempts_key = 'atc_login_attempts_' . $ip;
        
        $attempts = get_transient($attempts_key) ?: 0;
        $attempts++;
        
        // Block for 15 minutes after 5 failed attempts
        set_transient($attempts_key, $attempts, 15 * MINUTE_IN_SECONDS);
        
        // Log failed attempt
        self::log_activity(0, 'login_failed', "Failed login attempt for username: $username from IP: $ip");
    }
    
    /**
     * Check rate limit before authentication
     */
    public static function check_rate_limit($user, $username, $password) {
        // Skip if already error
        if (is_wp_error($user)) {
            return $user;
        }
        
        $ip = self::get_client_ip();
        $attempts = get_transient('atc_login_attempts_' . $ip) ?: 0;
        
        // Block after 5 failed attempts
        if ($attempts >= 5) {
            return new WP_Error(
                'too_many_attempts',
                __('Too many failed login attempts. Please try again in 15 minutes.', 'advanced-travel-crm')
            );
        }
        
        return $user;
    }
    
    /**
     * Hide admin bar for non-admin users
     */
    public static function hide_admin_bar($show) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        return $show;
    }
    
    /**
     * Check if user is logged in (helper)
     */
    public static function is_customer_logged_in() {
        return is_user_logged_in() && !current_user_can('manage_options');
    }
    
    /**
     * Get current customer
     */
    public static function get_current_customer() {
        if (!is_user_logged_in()) {
            return null;
        }
        
        $user = wp_get_current_user();
        
        return [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => get_user_meta($user->ID, 'atc_phone', true),
            'email_verified' => get_user_meta($user->ID, 'atc_email_verified', true),
            'last_login' => get_user_meta($user->ID, 'atc_last_login', true),
        ];
    }
    
    /**
     * Require login (use in templates)
     */
    public static function require_login($redirect_to = '') {
        if (!is_user_logged_in()) {
            if (empty($redirect_to)) {
                $redirect_to = home_url($_SERVER['REQUEST_URI']);
            }
            $login_url = home_url('/login/');
            if (!empty($redirect_to)) {
                $login_url = add_query_arg('redirect_to', urlencode($redirect_to), $login_url);
            }
            wp_redirect($login_url);
            exit;
        }
    }
    
    /**
     * Check if current user can access
     */
    public static function can_access_booking($booking_id) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ));
        
        return $booking && ($booking->user_id == $user_id || current_user_can('manage_atc'));
    }
    
    /**
     * ========================================
     * HELPER FUNCTIONS
     * ========================================
     */
    
    /**
     * Get client IP
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Log activity
     */
    private static function log_activity($user_id, $activity_type, $description) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        error_log(sprintf(
            'ATC Auth: User %d - %s - %s',
            $user_id,
            $activity_type,
            $description
        ));
    }
}