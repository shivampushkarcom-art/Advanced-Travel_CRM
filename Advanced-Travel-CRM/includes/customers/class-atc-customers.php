<?php
/**
 * ATC Customers Management
 * Handles customer deduplication, profiles, and lifetime value tracking
 */

if (!defined('ABSPATH')) exit;

class ATC_Customers {
    
    public static function init() {
        add_action('atc_booking_created', [__CLASS__, 'create_or_update_customer'], 5, 2);
        add_action('atc_payment_received', [__CLASS__, 'update_customer_spending'], 10, 2);
    }
    
    /**
     * Get or create customer by email
     */
    public static function get_or_create($email, $data = []) {
        global $wpdb;
        
        if (empty($email)) return null;
        
        // Check if customer exists
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOMERS . " WHERE email = %s",
            $email
        ), ARRAY_A);
        
        if ($customer) {
            return $customer;
        }
        
        // Create new customer
        $customer_data = [
            'email' => sanitize_email($email),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'user_id' => $data['user_id'] ?? null,
            'created_at' => current_time('mysql'),
        ];
        
        $wpdb->insert(ATC_TABLE_CUSTOMERS, $customer_data);
        
        return self::get_by_email($email);
    }
    
    /**
     * Get customer by email
     */
    public static function get_by_email($email) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOMERS . " WHERE email = %s",
            $email
        ), ARRAY_A);
    }
    
    /**
     * Get customer by ID
     */
    public static function get_by_id($customer_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOMERS . " WHERE id = %d",
            $customer_id
        ), ARRAY_A);
    }
    
    /**
     * Update customer when booking is created
     */
    public static function create_or_update_customer($booking_id, $booking_data) {
        $email = $booking_data['customer_email'] ?? '';
        if (empty($email)) return;
        
        $customer = self::get_or_create($email, [
            'name' => $booking_data['customer_name'] ?? '',
            'phone' => $booking_data['customer_phone'] ?? '',
            'user_id' => $booking_data['user_id'] ?? null,
        ]);
        
        if ($customer) {
            self::recalculate_stats($customer['id']);
        }
    }
    
    /**
     * Update customer spending when payment received
     */
    public static function update_customer_spending($booking_id, $payment_data) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking || empty($booking['customer_email'])) return;
        
        $customer = self::get_by_email($booking['customer_email']);
        if ($customer) {
            self::recalculate_stats($customer['id']);
        }
    }
    
    /**
     * Recalculate customer statistics
     */
    public static function recalculate_stats($customer_id) {
        global $wpdb;
        
        // Get customer
        $customer = self::get_by_id($customer_id);
        if (!$customer) return;
        
        // Calculate total bookings
        $total_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE customer_email = %s",
            $customer['email']
        ));
        
        // Calculate total spent (paid bookings only)
        $total_spent = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(price_total) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE customer_email = %s AND payment_status = 'paid'",
            $customer['email']
        ));
        
        // Get last booking date
        $last_booking_date = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(created_at) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE customer_email = %s",
            $customer['email']
        ));
        
        // Determine customer type
        $customer_type = 'regular';
        if ($total_bookings >= 10) {
            $customer_type = 'vip';
        } elseif ($total_bookings >= 5) {
            $customer_type = 'loyal';
        } elseif ($total_bookings == 1) {
            $customer_type = 'new';
        }
        
        // Update customer
        $wpdb->update(
            ATC_TABLE_CUSTOMERS,
            [
                'total_bookings' => $total_bookings,
                'total_spent' => $total_spent ?: 0,
                'lifetime_value' => $total_spent ?: 0,
                'last_booking_date' => $last_booking_date,
                'customer_type' => $customer_type,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $customer_id]
        );
    }
    
    /**
     * Get customer bookings
     */
    public static function get_customer_bookings($customer_id, $limit = 10) {
        global $wpdb;
        
        $customer = self::get_by_id($customer_id);
        if (!$customer) return [];
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE customer_email = %s 
            ORDER BY created_at DESC LIMIT %d",
            $customer['email'],
            $limit
        ), ARRAY_A);
    }
    
    /**
     * Search customers
     */
    public static function search($query, $limit = 20) {
        global $wpdb;
        
        $search = '%' . $wpdb->esc_like($query) . '%';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOMERS . " 
            WHERE name LIKE %s OR email LIKE %s OR phone LIKE %s
            ORDER BY total_spent DESC LIMIT %d",
            $search, $search, $search, $limit
        ), ARRAY_A);
    }
}
