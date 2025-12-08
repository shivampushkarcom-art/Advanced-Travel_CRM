<?php
/**
 * ATC Visitor Tracker - JustDial-style Visitor Tracking System
 * 
 * Features:
 * - Auto-capture visitor details (IP, location, device, browser)
 * - Track all activities (searches, bookings, queries, page views)
 * - Auto-send notifications to admin via WhatsApp and Email
 * - Comprehensive visitor analytics
 */

if (!defined('ABSPATH')) exit;

class ATC_Visitor_Tracker {
    
    private static $instance = null;
    private $session_id;
    private $visitor_id;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_session();
        $this->init_hooks();
    }
    
    /**
     * Initialize session tracking
     */
    private function init_session() {
        // Only start session if headers haven't been sent yet
        if (!headers_sent() && !session_id() && session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        // Generate or get session ID
        if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['atc_session_id'])) {
            $_SESSION['atc_session_id'] = $this->generate_session_id();
        }
        $this->session_id = isset($_SESSION['atc_session_id']) ? $_SESSION['atc_session_id'] : $this->generate_session_id();
        
        // Generate or get visitor ID (persistent across sessions)
        if (!isset($_COOKIE['atc_visitor_id'])) {
            $this->visitor_id = $this->generate_visitor_id();
            // Only set cookie if headers haven't been sent
            if (!headers_sent()) {
                setcookie('atc_visitor_id', $this->visitor_id, time() + (365 * 24 * 60 * 60), '/');
            }
        } else {
            $this->visitor_id = sanitize_text_field($_COOKIE['atc_visitor_id']);
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Track page views
        add_action('wp', [$this, 'track_page_view'], 1);
        
        // Track searches
        add_action('atc_search_performed', [$this, 'track_search'], 10, 2);
        
        // Track bookings
        add_action('atc_booking_created', [$this, 'track_booking'], 10, 1);
        
        // Track queries
        add_action('atc_query_submitted', [$this, 'track_query'], 10, 1);
        
        // Track package views
        add_action('atc_package_viewed', [$this, 'track_package_view'], 10, 1);
        
        // Track form submissions
        add_action('atc_form_submitted', [$this, 'track_form_submission'], 10, 2);
    }
    
    /**
     * Generate unique session ID
     */
    private function generate_session_id() {
        return 'ATC-' . time() . '-' . wp_generate_password(12, false);
    }
    
    /**
     * Generate unique visitor ID
     */
    private function generate_visitor_id() {
        return 'VIS-' . time() . '-' . wp_generate_password(12, false);
    }
    
    /**
     * Get visitor IP address
     */
    private function get_ip_address() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Get device information
     */
    private function get_device_info() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $device_type = 'desktop';
        $browser = 'unknown';
        $os = 'unknown';
        
        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $user_agent)) {
            $device_type = 'mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $user_agent)) {
            $device_type = 'tablet';
        }
        
        // Detect browser
        if (preg_match('/chrome/i', $user_agent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $user_agent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $user_agent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edge/i', $user_agent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera/i', $user_agent)) {
            $browser = 'Opera';
        }
        
        // Detect OS
        if (preg_match('/windows/i', $user_agent)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $user_agent)) {
            $os = 'Linux';
        } elseif (preg_match('/android/i', $user_agent)) {
            $os = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
            $os = 'iOS';
        }
        
        return [
            'device_type' => $device_type,
            'browser' => $browser,
            'os' => $os,
            'user_agent' => $user_agent
        ];
    }
    
    /**
     * Get location information (using IP)
     */
    private function get_location_info($ip) {
        // Use free IP geolocation API
        $location = [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'state' => 'Unknown',
            'timezone' => 'UTC'
        ];
        
        if ($ip && $ip !== '0.0.0.0' && $ip !== '127.0.0.1') {
            try {
                // Try ip-api.com (free, no API key needed)
                $response = wp_remote_get('http://ip-api.com/json/' . $ip, [
                    'timeout' => 3,
                    'sslverify' => false
                ]);
                
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    if ($data && $data['status'] === 'success') {
                        $location = [
                            'country' => $data['country'] ?? 'Unknown',
                            'city' => $data['city'] ?? 'Unknown',
                            'state' => $data['regionName'] ?? 'Unknown',
                            'timezone' => $data['timezone'] ?? 'UTC'
                        ];
                    }
                }
            } catch (Exception $e) {
                // Fallback to default
            }
        }
        
        return $location;
    }
    
    /**
     * Track page view
     */
    public function track_page_view() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        // Check if tracking is enabled
        if (!get_option('atc_enable_visitor_tracking', 1)) {
            return;
        }

        // Check if restricted to logged-in users
        if (get_option('atc_track_logged_in_only', 0) && !is_user_logged_in()) {
            return;
        }
        
        global $wpdb;
        
        $ip = $this->get_ip_address();
        $device_info = $this->get_device_info();
        $location = $this->get_location_info($ip);
        
        $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $landing_page = isset($_SESSION['atc_landing_page']) ? $_SESSION['atc_landing_page'] : $current_url;
        
        if (!isset($_SESSION['atc_landing_page'])) {
            $_SESSION['atc_landing_page'] = $current_url;
        }
        
        // Get screen resolution from JavaScript (will be updated via AJAX)
        $screen_resolution = isset($_COOKIE['atc_screen_resolution']) ? sanitize_text_field($_COOKIE['atc_screen_resolution']) : '';
        
        $data = [
            'session_id' => $this->session_id,
            'visitor_id' => $this->visitor_id,
            'ip_address' => $ip,
            'user_agent' => $device_info['user_agent'],
            'device_type' => $device_info['device_type'],
            'browser' => $device_info['browser'],
            'os' => $device_info['os'],
            'screen_resolution' => $screen_resolution,
            'country' => $location['country'],
            'city' => $location['city'],
            'state' => $location['state'],
            'timezone' => $location['timezone'],
            'referrer_url' => $referrer,
            'landing_page' => $landing_page,
            'current_page' => $current_url,
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in() ? 1 : 0,
            'activity_type' => 'page_view',
            'activity_data' => json_encode([
                'page_title' => wp_get_document_title(),
                'post_type' => get_post_type(),
                'post_id' => get_the_ID()
            ]),
            'page_views' => 1,
            'first_visit' => current_time('mysql'),
            'last_visit' => current_time('mysql')
        ];
        
        // Check if visitor exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}atc_visitor_tracking WHERE visitor_id = %s ORDER BY id DESC LIMIT 1",
            $this->visitor_id
        ));
        
        if ($existing) {
            // Update existing visitor
            $wpdb->update(
                $wpdb->prefix . 'atc_visitor_tracking',
                [
                    'current_page' => $current_url,
                    'last_visit' => current_time('mysql'),
                    'page_views' => $existing->page_views + 1,
                    'session_duration' => time() - strtotime($existing->last_visit)
                ],
                ['visitor_id' => $this->visitor_id],
                ['%s', '%s', '%d', '%d'],
                ['%s']
            );
        } else {
            // Insert new visitor
            $wpdb->insert(
                $wpdb->prefix . 'atc_visitor_tracking',
                $data,
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s']
            );
            
            // NO notification for new visitors to reduce noise
        }
    }
    
    /**
     * Track search activity
     */
    public function track_search($query_data, $service = '') {
        // Check if tracking is enabled
        if (!get_option('atc_enable_visitor_tracking', 1)) {
            return;
        }

        // Check if restricted to logged-in users
        if (get_option('atc_track_logged_in_only', 0) && !is_user_logged_in()) {
            return;
        }

        global $wpdb;
        
        $ip = $this->get_ip_address();
        $device_info = $this->get_device_info();
        $location = $this->get_location_info($ip);
        
        $data = [
            'session_id' => $this->session_id,
            'visitor_id' => $this->visitor_id,
            'ip_address' => $ip,
            'user_agent' => $device_info['user_agent'],
            'device_type' => $device_info['device_type'],
            'browser' => $device_info['browser'],
            'os' => $device_info['os'],
            'country' => $location['country'],
            'city' => $location['city'],
            'state' => $location['state'],
            'timezone' => $location['timezone'],
            'current_page' => home_url(add_query_arg([], $_SERVER['REQUEST_URI'])),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in() ? 1 : 0,
            'activity_type' => 'search',
            'activity_data' => json_encode($query_data),
            'service' => $service,
            'search_query' => is_array($query_data) ? (isset($query_data['query']) ? $query_data['query'] : '') : $query_data,
            'last_visit' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'atc_visitor_tracking',
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        // Auto-create lead from search (if lead capture enabled)
        if (get_option('atc_auto_lead_capture', 1) && class_exists('ATC_Lead_Capture')) {
            $this->auto_create_lead_from_search($query_data, $service, $data);
        }
        
        // Send notification
        $this->send_visitor_notification($data, 'search');
    }
    
    /**
     * Track booking activity
     */
    public function track_booking($booking_id) {
        // Check if tracking is enabled
        if (!get_option('atc_enable_visitor_tracking', 1)) {
            return;
        }

        // Check if restricted to logged-in users
        if (get_option('atc_track_logged_in_only', 0) && !is_user_logged_in()) {
            return;
        }

        global $wpdb;
        
        // booking_id can be either numeric ID or booking_id string
        $booking = null;
        if (is_numeric($booking_id)) {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
                $booking_id
            ));
        } else {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE booking_id = %s",
                $booking_id
            ));
        }
        
        if (!$booking) return;
        
        $ip = $this->get_ip_address();
        $device_info = $this->get_device_info();
        $location = $this->get_location_info($ip);
        
        $data = [
            'session_id' => $this->session_id,
            'visitor_id' => $this->visitor_id,
            'ip_address' => $ip,
            'user_agent' => $device_info['user_agent'],
            'device_type' => $device_info['device_type'],
            'browser' => $device_info['browser'],
            'os' => $device_info['os'],
            'country' => $location['country'],
            'city' => $location['city'],
            'state' => $location['state'],
            'timezone' => $location['timezone'],
            'current_page' => home_url(add_query_arg([], $_SERVER['REQUEST_URI'])),
            'user_id' => $booking->user_id,
            'is_logged_in' => $booking->user_id ? 1 : 0,
            'activity_type' => 'booking',
            'activity_data' => json_encode([
                'booking_id' => $booking->booking_id,
                'service' => $booking->service,
                'destination' => $booking->destination,
                'price' => $booking->price_total,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone
            ]),
            'service' => $booking->service,
            'package_id' => $booking->package_id,
            'booking_id' => $booking->booking_id,
            'last_visit' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'atc_visitor_tracking',
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        // Send notification
        $this->send_visitor_notification($data, 'booking');
    }
    
    /**
     * Track query activity
     */
    public function track_query($query_id) {
        // Check if tracking is enabled
        if (!get_option('atc_enable_visitor_tracking', 1)) {
            return;
        }

        // Check if restricted to logged-in users
        if (get_option('atc_track_logged_in_only', 0) && !is_user_logged_in()) {
            return;
        }

        global $wpdb;
        
        $query = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}atc_package_queries WHERE query_id = %s",
            $query_id
        ));
        
        if (!$query) return;
        
        $ip = $this->get_ip_address();
        $device_info = $this->get_device_info();
        $location = $this->get_location_info($ip);
        
        $data = [
            'session_id' => $this->session_id,
            'visitor_id' => $this->visitor_id,
            'ip_address' => $ip,
            'user_agent' => $device_info['user_agent'],
            'device_type' => $device_info['device_type'],
            'browser' => $device_info['browser'],
            'os' => $device_info['os'],
            'country' => $location['country'],
            'city' => $location['city'],
            'state' => $location['state'],
            'timezone' => $location['timezone'],
            'current_page' => home_url(add_query_arg([], $_SERVER['REQUEST_URI'])),
            'user_id' => $query->user_id,
            'is_logged_in' => $query->user_id ? 1 : 0,
            'activity_type' => 'query',
            'activity_data' => json_encode([
                'query_id' => $query->query_id,
                'service' => $query->service_key,
                'destination' => $query->destination,
                'customer_name' => $query->customer_name,
                'customer_email' => $query->customer_email,
                'customer_phone' => $query->customer_phone,
                'message' => $query->message
            ]),
            'service' => $query->service_key,
            'package_id' => $query->package_id,
            'query_id' => $query->query_id,
            'last_visit' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'atc_visitor_tracking',
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        // Send notification
        $this->send_visitor_notification($data, 'query');
    }
    
    /**
     * Track package view
     */
    public function track_package_view($package_id) {
        // Check if tracking is enabled
        if (!get_option('atc_enable_visitor_tracking', 1)) {
            return;
        }

        // Check if restricted to logged-in users
        if (get_option('atc_track_logged_in_only', 0) && !is_user_logged_in()) {
            return;
        }

        global $wpdb;
        
        $ip = $this->get_ip_address();
        $device_info = $this->get_device_info();
        $location = $this->get_location_info($ip);
        
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
            $package_id
        ));
        
        $data = [
            'session_id' => $this->session_id,
            'visitor_id' => $this->visitor_id,
            'ip_address' => $ip,
            'user_agent' => $device_info['user_agent'],
            'device_type' => $device_info['device_type'],
            'browser' => $device_info['browser'],
            'os' => $device_info['os'],
            'country' => $location['country'],
            'city' => $location['city'],
            'state' => $location['state'],
            'timezone' => $location['timezone'],
            'current_page' => home_url(add_query_arg([], $_SERVER['REQUEST_URI'])),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in() ? 1 : 0,
            'activity_type' => 'package_view',
            'activity_data' => json_encode([
                'package_id' => $package_id,
                'package_name' => $package ? $package->name : '',
                'service' => $package ? $package->service_key : ''
            ]),
            'service' => $package ? $package->service_key : '',
            'package_id' => $package_id,
            'last_visit' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'atc_visitor_tracking',
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s']
        );
        
        // Send notification for important package views
        if ($package && in_array($package->service_key, ['tours', 'hotels', 'flights'])) {
            $this->send_visitor_notification($data, 'package_view');
        }
    }
    
    /**
     * Track form submission
     */
    public function track_form_submission($form_type, $form_data) {
        // Check if tracking is enabled
        if (!get_option('atc_enable_visitor_tracking', 1)) {
            return;
        }

        // Check if restricted to logged-in users
        if (get_option('atc_track_logged_in_only', 0) && !is_user_logged_in()) {
            return;
        }

        global $wpdb;
        
        $ip = $this->get_ip_address();
        $device_info = $this->get_device_info();
        $location = $this->get_location_info($ip);
        
        $data = [
            'session_id' => $this->session_id,
            'visitor_id' => $this->visitor_id,
            'ip_address' => $ip,
            'user_agent' => $device_info['user_agent'],
            'device_type' => $device_info['device_type'],
            'browser' => $device_info['browser'],
            'os' => $device_info['os'],
            'country' => $location['country'],
            'city' => $location['city'],
            'state' => $location['state'],
            'timezone' => $location['timezone'],
            'current_page' => home_url(add_query_arg([], $_SERVER['REQUEST_URI'])),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in() ? 1 : 0,
            'activity_type' => 'form_submission',
            'activity_data' => json_encode([
                'form_type' => $form_type,
                'form_data' => $form_data
            ]),
            'last_visit' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'atc_visitor_tracking',
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );
        
        // Send notification
        $this->send_visitor_notification($data, 'form_submission');
    }
    
    /**
     * Send visitor notification to admin
     */
    private function send_visitor_notification($data, $activity_type) {
        // Check if notifications are enabled
        $notifications_enabled = get_option('atc_visitor_notifications_enabled', 1);
        if (!$notifications_enabled) {
            return;
        }

        // Noise Reduction: Only send notifications for high-value activities
        // We do NOT send for: page_view, package_view, search, new_visitor
        $allowed_types = ['booking', 'query', 'form_submission'];
        
        if (!in_array($activity_type, $allowed_types)) {
            return;
        }
        
        // Load WhatsApp and Email sender classes
        if (!class_exists('ATC_WhatsApp_Sender')) {
            require_once ATC_INCLUDES_DIR . 'notifications/class-atc-whatsapp-sender.php';
        }
        
        if (!class_exists('ATC_Email_Sender')) {
            require_once ATC_INCLUDES_DIR . 'notifications/class-atc-email-sender.php';
        }
        
        // Format message
        $message = $this->format_notification_message($data, $activity_type);
        $email_subject = $this->get_notification_subject($activity_type);
        
        // Send via WhatsApp
        if (class_exists('ATC_WhatsApp_Sender')) {
            try {
                ATC_WhatsApp_Sender::send_message($message);
            } catch (Exception $e) {
                error_log('WhatsApp notification failed: ' . $e->getMessage());
            }
        }
        
        // Send via Email
        if (class_exists('ATC_Email_Sender')) {
            $admin_email = get_option('atc_admin_email', get_option('admin_email'));
            if ($admin_email) {
                try {
                    // Use wp_mail directly for visitor notifications
                    $email_message = '<!DOCTYPE html><html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f7fafc; padding: 20px;">';
                    
                    // Card Container
                    $email_message .= '<div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">';
                    
                    // Header
                    $email_message .= '<div style="background: #2563eb; color: #ffffff; padding: 20px; text-align: center;">';
                    $email_message .= '<h2 style="margin:0; font-size: 20px;">' . esc_html($email_subject) . '</h2>';
                    $email_message .= '</div>';
                    
                    // Content
                    $email_message .= '<div style="padding: 30px;">';
                    
                    // Convert the simplified text message to HTML table rows
                    // (We reuse the cleaner logic from format_notification_message, but formatted for Email)
                    
                    $activity_data = json_decode($data['activity_data'], true);
                    $email_message .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
                    
                    // Helper for rows
                    $row_style = 'padding: 12px 0; border-bottom: 1px solid #e2e8f0;';
                    $label_style = 'font-weight: 600; color: #64748b; width: 140px; vertical-align: top; padding: 12px 0; border-bottom: 1px solid #e2e8f0;';
                    
                    if ($activity_type === 'booking') {
                        $email_message .= '<tr><td style="'.$label_style.'">Booking ID</td><td style="'.$row_style.'"><strong>' . $activity_data['booking_id'] . '</strong></td></tr>';
                        $email_message .= '<tr><td style="'.$label_style.'">Customer</td><td style="'.$row_style.'">' . $activity_data['customer_name'] . '</td></tr>';
                        $email_message .= '<tr><td style="'.$label_style.'">Phone</td><td style="'.$row_style.'"><a href="tel:'.$activity_data['customer_phone'].'">' . $activity_data['customer_phone'] . '</a></td></tr>';
                        if (!empty($activity_data['customer_email'])) {
                            $email_message .= '<tr><td style="'.$label_style.'">Email</td><td style="'.$row_style.'"><a href="mailto:'.$activity_data['customer_email'].'">' . $activity_data['customer_email'] . '</a></td></tr>';
                        }
                        $email_message .= '<tr><td style="'.$label_style.'">Amount</td><td style="'.$row_style.'">₹' . number_format($activity_data['price'], 2) . '</td></tr>';
                        $email_message .= '<tr><td style="'.$label_style.'">Destination</td><td style="'.$row_style.'">' . $activity_data['destination'] . '</td></tr>';
                    } elseif ($activity_type === 'query') {
                        $email_message .= '<tr><td style="'.$label_style.'">Query ID</td><td style="'.$row_style.'"><strong>' . $activity_data['query_id'] . '</strong></td></tr>';
                        $email_message .= '<tr><td style="'.$label_style.'">Customer</td><td style="'.$row_style.'">' . $activity_data['customer_name'] . '</td></tr>';
                        $email_message .= '<tr><td style="'.$label_style.'">Phone</td><td style="'.$row_style.'"><a href="tel:'.$activity_data['customer_phone'].'">' . $activity_data['customer_phone'] . '</a></td></tr>';
                        if (!empty($activity_data['customer_email'])) {
                            $email_message .= '<tr><td style="'.$label_style.'">Email</td><td style="'.$row_style.'"><a href="mailto:'.$activity_data['customer_email'].'">' . $activity_data['customer_email'] . '</a></td></tr>';
                        }
                        if (!empty($activity_data['message'])) {
                            $email_message .= '<tr><td style="'.$label_style.'">Message</td><td style="'.$row_style.'"><em>' . nl2br(esc_html($activity_data['message'])) . '</em></td></tr>';
                        }
                    } elseif ($activity_type === 'form_submission') {
                        $email_message .= '<tr><td style="'.$label_style.'">Form</td><td style="'.$row_style.'"><strong>' . ucfirst(str_replace('_', ' ', $activity_data['form_type'])) . '</strong></td></tr>';
                        if (!empty($activity_data['form_data']['name'])) $email_message .= '<tr><td style="'.$label_style.'">Name</td><td style="'.$row_style.'">' . $activity_data['form_data']['name'] . '</td></tr>';
                        if (!empty($activity_data['form_data']['phone'])) $email_message .= '<tr><td style="'.$label_style.'">Phone</td><td style="'.$row_style.'"><a href="tel:'.$activity_data['form_data']['phone'].'">' . $activity_data['form_data']['phone'] . '</a></td></tr>';
                        if (!empty($activity_data['form_data']['email'])) $email_message .= '<tr><td style="'.$label_style.'">Email</td><td style="'.$row_style.'"><a href="mailto:'.$activity_data['form_data']['email'].'">' . $activity_data['form_data']['email'] . '</a></td></tr>';
                    }
                    
                    // Visitor Context Section
                    $email_message .= '</table>';
                    
                    $email_message .= '<div style="background: #f8fafc; padding: 15px; border-radius: 6px; font-size: 13px; color: #64748b;">';
                    $email_message .= '<strong>Visitor Context:</strong><br>';
                    $email_message .= '📍 ' . $data['city'] . ', ' . $data['country'] . '<br>';
                    $email_message .= '📱 ' . ucfirst($data['device_type']) . ' (' . $data['os'] . ')<br>';
                    $email_message .= '🌐 IP: ' . $data['ip_address'];
                    $email_message .= '</div>';
                    
                    $email_message .= '</div>'; // End padding
                    
                    // Footer
                    $email_message .= '<div style="background: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; color: #94a3b8;">';
                    $email_message .= 'ATC System Notification • ' . date('Y');
                    $email_message .= '</div>';
                    
                    $email_message .= '</div></body></html>';
                    
                    $headers = ['Content-Type: text/html; charset=UTF-8'];
                    wp_mail($admin_email, $email_subject, $email_message, $headers);
                } catch (Exception $e) {
                    error_log('Email notification failed: ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Format notification message
     */
    private function format_notification_message($data, $activity_type) {
        $activity_data = json_decode($data['activity_data'], true);
        
        // Header
        $message = "🔔 *" . strtoupper(str_replace('_', ' ', $activity_type)) . "*\n";
        $message .= "📅 " . date('d M Y, h:i A') . "\n";
        $message .= "──────────────────\n\n";
        
        // 1. Primary Activity Details (The "Meat")
        if ($activity_type === 'booking') {
            $message .= "*📋 Booking Details*\n";
            $message .= "• ID: *" . $activity_data['booking_id'] . "*\n";
            $message .= "• Name: " . $activity_data['customer_name'] . "\n";
            $message .= "• Phone: " . $activity_data['customer_phone'] . "\n";
            if (!empty($activity_data['customer_email'])) {
                $message .= "• Email: " . $activity_data['customer_email'] . "\n";
            }
            // Service
            $service = $activity_data['service'] ?? '';
            if (is_array($service)) $service = implode(', ', $service);
            if ($service) $message .= "• Service: " . ucfirst($service) . "\n";
            
            $message .= "• Dest: " . $activity_data['destination'] . "\n";
            $message .= "• Total: *₹" . number_format($activity_data['price'], 2) . "*\n";
            
        } elseif ($activity_type === 'query') {
            $message .= "*💬 Query Details*\n";
            $message .= "• ID: *" . $activity_data['query_id'] . "*\n";
            $message .= "• Name: " . $activity_data['customer_name'] . "\n";
            $message .= "• Phone: " . $activity_data['customer_phone'] . "\n";
            if (!empty($activity_data['customer_email'])) {
                $message .= "• Email: " . $activity_data['customer_email'] . "\n";
            }
            
            $service = $activity_data['service'] ?? '';
            if (is_array($service)) $service = implode(', ', $service);
            if ($service) $message .= "• Service: " . ucfirst($service) . "\n";
            
            if (!empty($activity_data['destination'])) {
                $message .= "• Dest: " . $activity_data['destination'] . "\n";
            }
            if (!empty($activity_data['message'])) {
                $message .= "\n_\"" . substr($activity_data['message'], 0, 150) . "...\"_\n";
            }
            
        } elseif ($activity_type === 'form_submission') {
            $message .= "*📝 Form Submission*\n";
            $message .= "• Type: " . ucfirst(str_replace('_', ' ', $activity_data['form_type'])) . "\n";
            // Iterate common fields if available
            if (!empty($activity_data['form_data']['name'])) $message .= "• Name: " . $activity_data['form_data']['name'] . "\n";
            if (!empty($activity_data['form_data']['phone'])) $message .= "• Phone: " . $activity_data['form_data']['phone'] . "\n";
            if (!empty($activity_data['form_data']['email'])) $message .= "• Email: " . $activity_data['form_data']['email'] . "\n";
        }
        
        $message .= "\n──────────────────\n";
        
        // 2. Visitor Context (Brief)
        $message .= "*👤 Visitor Stats*\n";
        $message .= "📍 " . $data['city'] . ", " . $data['country'] . "\n";
        $message .= "📱 " . ucfirst($data['device_type']) . " (" . $data['os'] . ")\n";
        $message .= "🌐 " . $data['ip_address']; // No link
        
        // REMOVED: Page URL link
        
        return $message;
    }
    
    /**
     * Get notification subject
     */
    private function get_notification_subject($activity_type) {
        $subjects = [
            'new_visitor' => 'New Visitor on Website',
            'search' => 'Visitor Search Activity',
            'booking' => 'New Booking Created',
            'query' => 'New Query Submitted',
            'package_view' => 'Package Viewed',
            'form_submission' => 'Form Submitted'
        ];
        
        return $subjects[$activity_type] ?? 'Visitor Activity';
    }
    
    /**
     * Auto-create lead from search activity
     */
    private function auto_create_lead_from_search($query_data, $service, $visitor_data) {
        if (!class_exists('ATC_Lead_Capture')) {
            return;
        }
        
        global $wpdb;
        
        // Extract lead data from search
        $lead_data = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'service' => $service,
            'destination' => is_array($query_data) ? ($query_data['destination'] ?? $query_data['query'] ?? '') : $query_data,
            'trip_type' => is_array($query_data) ? ($query_data['trip_type'] ?? '') : '',
            'budget_min' => is_array($query_data) ? floatval($query_data['budget_min'] ?? 0) : 0,
            'budget_max' => is_array($query_data) ? floatval($query_data['budget_max'] ?? 0) : 0,
            'date_from' => is_array($query_data) ? ($query_data['date_from'] ?? '') : '',
            'date_to' => is_array($query_data) ? ($query_data['date_to'] ?? '') : '',
            'adults' => is_array($query_data) ? intval($query_data['adults'] ?? 1) : 1,
            'children' => is_array($query_data) ? intval($query_data['children'] ?? 0) : 0,
            'user_id' => get_current_user_id() ?: null,
            'source' => 'auto_search',
            'ip' => $visitor_data['ip_address'] ?? '',
            'user_agent' => $visitor_data['user_agent'] ?? '',
            'device_type' => $visitor_data['device_type'] ?? '',
            'browser' => $visitor_data['browser'] ?? '',
            'utm_source' => sanitize_text_field($_GET['utm_source'] ?? ''),
            'utm_medium' => sanitize_text_field($_GET['utm_medium'] ?? ''),
            'utm_campaign' => sanitize_text_field($_GET['utm_campaign'] ?? ''),
            'referrer' => $visitor_data['referrer_url'] ?? '',
        ];
        
        // Check for existing lead (same visitor_id or IP within 24 hours)
        $existing_lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_LEADS . " 
            WHERE (ip = %s) 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY id DESC LIMIT 1",
            $lead_data['ip']
        ));
        
        if ($existing_lead) {
            // Update existing lead with new search data (use object syntax)
            $search_count = intval($existing_lead->search_count ?? 1) + 1;
            $wpdb->update(
                ATC_TABLE_LEADS,
                [
                    'destination' => $lead_data['destination'] ?: ($existing_lead->destination ?? ''),
                    'service' => $lead_data['service'] ?: ($existing_lead->service ?? ''),
                    'date_from' => $lead_data['date_from'] ?: ($existing_lead->date_from ?? ''),
                    'date_to' => $lead_data['date_to'] ?: ($existing_lead->date_to ?? ''),
                    'search_count' => $search_count,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $existing_lead->id]
            );
            
            // Recalculate score
            if (class_exists('ATC_Lead_Scoring')) {
                ATC_Lead_Scoring::update_lead_score($existing_lead['id']);
            }
        } else {
            // Create new lead
            $wpdb->insert(
                ATC_TABLE_LEADS,
                array_merge($lead_data, [
                    'status' => 'new',
                    'search_count' => 1,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ])
            );
            
            $lead_id = $wpdb->insert_id;
            
            if ($lead_id && class_exists('ATC_Lead_Scoring')) {
                // Calculate initial score
                ATC_Lead_Scoring::update_lead_score($lead_id);
            }
        }
    }
}

