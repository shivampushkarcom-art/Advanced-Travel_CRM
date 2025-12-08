<?php
/**
 * ATC AJAX Handlers - COMPLETE & FIXED
 * All missing handlers now registered
 */

if (!defined('ABSPATH')) exit;

class ATC_AJAX {
    
    public static function init() {
        // Customer Account Actions
        add_action('wp_ajax_atc_get_booking_details', [__CLASS__, 'get_booking_details']);
        add_action('wp_ajax_atc_cancel_booking', [__CLASS__, 'cancel_booking']);
        add_action('wp_ajax_atc_download_invoice', [__CLASS__, 'download_invoice']);
        add_action('wp_ajax_atc_resend_email', [__CLASS__, 'resend_email']);
        
        // Admin Actions
        add_action('wp_ajax_atc_update_booking_status', [__CLASS__, 'update_booking_status']);
        add_action('wp_ajax_atc_mark_lead_contacted', [__CLASS__, 'mark_lead_contacted']);
        add_action('wp_ajax_atc_mark_whatsapp_sent', ['ATC_Notification_Manager', 'ajax_mark_whatsapp_sent']);
        
        // Search & Autocomplete
        add_action('wp_ajax_atc_autocomplete', [__CLASS__, 'autocomplete']);
        add_action('wp_ajax_nopriv_atc_autocomplete', [__CLASS__, 'autocomplete']);
        
        // OTP Actions
        add_action('wp_ajax_atc_resend_otp', ['ATC_OTP', 'ajax_resend_otp']);
        add_action('wp_ajax_nopriv_atc_resend_otp', ['ATC_OTP', 'ajax_resend_otp']);
    }
    
    /**
     * Get booking details
     */
    public static function get_booking_details() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => 'Invalid booking ID']);
        }
        
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if ($booking) {
            wp_send_json_success($booking);
        } else {
            wp_send_json_error(['message' => 'Booking not found']);
        }
    }
    
    /**
     * Cancel booking
     */
    public static function cancel_booking() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Please login']);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$booking_id) {
            wp_send_json_error(['message' => 'Invalid booking']);
        }
        
        // Use cancellation system
        if (class_exists('ATC_Cancellation')) {
            $result = ATC_Cancellation::request_cancellation($booking_id, $reason);
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            }
            
            wp_send_json_success(['message' => 'Cancellation request submitted']);
        }
        
        wp_send_json_error(['message' => 'Cancellation system not available']);
    }
    
    /**
     * Download invoice
     */
    public static function download_invoice() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Please login']);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        // Generate invoice URL
        $invoice_url = add_query_arg([
            'atc_action' => 'download_invoice',
            'booking_id' => $booking_id,
            'nonce' => wp_create_nonce('atc_invoice_' . $booking_id),
        ], home_url());
        
        wp_send_json_success(['invoice_url' => $invoice_url]);
    }
    
    /**
     * Resend email
     */
    public static function resend_email() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => 'Invalid booking']);
        }
        
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            wp_send_json_error(['message' => 'Booking not found']);
        }
        
        // Send email
        if (class_exists('ATC_Notification_Manager')) {
            ATC_Notification_Manager::on_booking_created($booking_id, $booking);
            wp_send_json_success(['message' => 'Email sent successfully!']);
        }
        
        wp_send_json_error(['message' => 'Email system not available']);
    }
    
    /**
     * Update booking status (Admin)
     */
    public static function update_booking_status() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!current_user_can('manage_atc')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        if (!$booking_id || !$status) {
            wp_send_json_error(['message' => 'Invalid parameters']);
        }
        
        global $wpdb;
        
        // 1. Get Old Status & Data
        $booking_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking_data) {
            wp_send_json_error(['message' => 'Booking not found']);
        }
        
        $old_status = $booking_data['status'];
        
        // 2. Update Database
        $result = $wpdb->update(
            ATC_TABLE_BOOKINGS,
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $booking_id]
        );
        
        if ($result !== false) {
            // 3. Fire Hook for Notifications (if status changed)
            if ($old_status !== $status) {
                // Update booking data with new status for the hook
                $booking_data['status'] = $status;
                do_action('atc_booking_status_changed', $booking_id, $old_status, $status, $booking_data);
            }
            
            wp_send_json_success(['message' => 'Status updated']);
        } else {
            wp_send_json_error(['message' => 'Update failed']);
        }
    }
    
    /**
     * Mark lead as contacted (Admin)
     */
    public static function mark_lead_contacted() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!current_user_can('manage_atc')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        
        if (!$lead_id) {
            wp_send_json_error(['message' => 'Invalid lead ID']);
        }
        
        global $wpdb;
        $result = $wpdb->update(
            ATC_TABLE_LEADS,
            ['status' => 'contacted', 'updated_at' => current_time('mysql')],
            ['id' => $lead_id]
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Lead marked as contacted']);
        } else {
            wp_send_json_error(['message' => 'Update failed']);
        }
    }
    
    /**
     * Autocomplete for search
     */
    public static function autocomplete() {
        $query = sanitize_text_field($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            wp_send_json([]);
        }
        
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT destination FROM " . ATC_TABLE_SERVICES . " 
            WHERE destination LIKE %s LIMIT 10",
            '%' . $wpdb->esc_like($query) . '%'
        ), ARRAY_A);
        
        wp_send_json($results);
    }
}