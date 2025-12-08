<?php
/**
 * ========================================
 * FILE: class-atc-automation.php
 * ========================================
 */
class ATC_Automation {
    
    public static function init() {
        // Schedule automated tasks
        if (!wp_next_scheduled('atc_daily_automation')) {
            wp_schedule_event(time(), 'daily', 'atc_daily_automation');
        }
        
        add_action('atc_daily_automation', [__CLASS__, 'run_daily_tasks']);
    }
    
    public static function run_daily_tasks() {
        // Send payment reminders
        self::send_payment_reminders();
        
        // Send booking reminders
        self::send_booking_reminders();
        
        // Send review requests
        self::send_review_requests();
    }
    
    private static function send_payment_reminders() {
        global $wpdb;
        
        $pending_bookings = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE payment_status = 'unpaid' 
            AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            LIMIT 10"
        );
        
        foreach ($pending_bookings as $booking) {
            // Send reminder email
            do_action('atc_send_payment_reminder', $booking->id);
        }
    }
    
    private static function send_booking_reminders() {
        global $wpdb;
        
        $upcoming_bookings = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE travel_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND status = 'confirmed'
            LIMIT 10"
        );
        
        foreach ($upcoming_bookings as $booking) {
            // Send reminder
            do_action('atc_send_booking_reminder', $booking->id);
        }
    }
    
    private static function send_review_requests() {
        global $wpdb;
        
        $completed_bookings = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE return_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            AND status = 'completed'
            LIMIT 10"
        );
        
        foreach ($completed_bookings as $booking) {
            // Send review request
            do_action('atc_send_review_request', $booking->id);
        }
    }
}
