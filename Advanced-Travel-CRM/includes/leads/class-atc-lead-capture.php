<?php
/**
 * ATC Lead Capture System
 * Captures every search as a potential lead with automatic scoring
 */

if (!defined('ABSPATH')) exit;

class ATC_Lead_Capture {
    
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }
    
    /**
     * Register REST routes
     */
    public static function register_routes() {
        register_rest_route('atc/v1', '/capture-lead', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'capture_lead'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Capture lead from search
     */
    public static function capture_lead($request) {
        $params = $request->get_json_params();
        
        // Extract lead data
        $lead_data = [
            'name' => sanitize_text_field($params['name'] ?? ''),
            'email' => sanitize_email($params['email'] ?? ''),
            'phone' => sanitize_text_field($params['phone'] ?? ''),
            'service' => sanitize_text_field($params['service'] ?? ''),
            'destination' => sanitize_text_field($params['destination'] ?? ''),
            'trip_type' => sanitize_text_field($params['trip_type'] ?? ''),
            'budget_min' => floatval($params['budget_min'] ?? 0),
            'budget_max' => floatval($params['budget_max'] ?? 0),
            'date_from' => sanitize_text_field($params['date_from'] ?? ''),
            'date_to' => sanitize_text_field($params['date_to'] ?? ''),
            'adults' => intval($params['adults'] ?? 1),
            'children' => intval($params['children'] ?? 0),
            'user_id' => get_current_user_id() ?: null,
            'source' => sanitize_text_field($params['source'] ?? 'search_form'),
            'ip' => self::get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'device_type' => self::get_device_type(),
            'browser' => self::get_browser(),
            'utm_source' => sanitize_text_field($params['utm_source'] ?? $_GET['utm_source'] ?? ''),
            'utm_medium' => sanitize_text_field($params['utm_medium'] ?? $_GET['utm_medium'] ?? ''),
            'utm_campaign' => sanitize_text_field($params['utm_campaign'] ?? $_GET['utm_campaign'] ?? ''),
            'referrer' => sanitize_text_field($_SERVER['HTTP_REFERER'] ?? ''),
        ];
        
        // Check for duplicate lead (same email within 24 hours)
        $existing_lead = self::find_duplicate_lead($lead_data);
        
        if ($existing_lead) {
            // Update existing lead
            $lead_id = self::update_existing_lead($existing_lead['id'], $lead_data);
        } else {
            // Create new lead
            $lead_id = self::create_new_lead($lead_data);
        }
        
        if (!$lead_id) {
            return new WP_Error('lead_capture_failed', __('Failed to capture lead', 'advanced-travel-crm'), ['status' => 500]);
        }
        
        // Calculate score
        $scoring = ATC_Lead_Scoring::update_lead_score($lead_id);
        
        // Trigger notification if hot lead
        if ($scoring['temperature'] === 'hot') {
            do_action('atc_lead_created', $lead_id, array_merge($lead_data, $scoring));
        }
        
        return rest_ensure_response([
            'success' => true,
            'lead_id' => $lead_id,
            'score' => $scoring['score'],
            'temperature' => $scoring['temperature'],
        ]);
    }
    
    /**
     * Find duplicate lead
     */
    private static function find_duplicate_lead($lead_data) {
        global $wpdb;
        
        if (empty($lead_data['email'])) {
            return null;
        }
        
        // Find lead with same email created in last 24 hours
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_LEADS . " 
            WHERE email = %s 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC LIMIT 1",
            $lead_data['email']
        ), ARRAY_A);
    }
    
    /**
     * Create new lead
     */
    private static function create_new_lead($lead_data) {
        global $wpdb;
        
        $lead_data['search_count'] = 1;
        $lead_data['status'] = 'new';
        $lead_data['created_at'] = current_time('mysql');
        $lead_data['last_activity_at'] = current_time('mysql');
        
        $wpdb->insert(ATC_TABLE_LEADS, $lead_data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update existing lead
     */
    private static function update_existing_lead($lead_id, $new_data) {
        global $wpdb;
        
        // Get current lead
        $current_lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_LEADS . " WHERE id = %d",
            $lead_id
        ), ARRAY_A);
        
        if (!$current_lead) {
            return null;
        }
        
        // Increment search count
        $search_count = intval($current_lead['search_count']) + 1;
        
        // Update with new data
        $update_data = [
            'search_count' => $search_count,
            'last_activity_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        
        // Update destination if more specific
        if (!empty($new_data['destination'])) {
            $update_data['destination'] = $new_data['destination'];
        }
        
        // Update dates if provided
        if (!empty($new_data['date_from'])) {
            $update_data['date_from'] = $new_data['date_from'];
        }
        if (!empty($new_data['date_to'])) {
            $update_data['date_to'] = $new_data['date_to'];
        }
        
        // Update budget if provided
        if (!empty($new_data['budget_min'])) {
            $update_data['budget_min'] = $new_data['budget_min'];
        }
        if (!empty($new_data['budget_max'])) {
            $update_data['budget_max'] = $new_data['budget_max'];
        }
        
        $wpdb->update(
            ATC_TABLE_LEADS,
            $update_data,
            ['id' => $lead_id]
        );
        
        // Log activity
        $wpdb->insert(ATC_TABLE_LEAD_ACTIVITIES, [
            'lead_id' => $lead_id,
            'activity_type' => 'searched_again',
            'description' => sprintf(__('Searched for %s (search #%d)', 'advanced-travel-crm'), $new_data['destination'], $search_count),
            'created_at' => current_time('mysql'),
        ]);
        
        return $lead_id;
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Get device type
     */
    private static function get_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
            return 'tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }
    
    /**
     * Get browser name
     */
    private static function get_browser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($user_agent, 'Safari') !== false) return 'Safari';
        if (strpos($user_agent, 'Edge') !== false) return 'Edge';
        if (strpos($user_agent, 'Opera') !== false) return 'Opera';
        
        return 'Other';
    }
    
    /**
     * Convert lead to booking
     */
    public static function convert_lead_to_booking($email, $booking_id) {
        global $wpdb;
        
        if (empty($email)) return;
        
        $wpdb->update(
            ATC_TABLE_LEADS,
            [
                'status' => 'converted',
                'converted_booking_id' => $booking_id,
                'updated_at' => current_time('mysql'),
            ],
            [
                'email' => $email,
                'status' => 'new'
            ]
        );
    }
}