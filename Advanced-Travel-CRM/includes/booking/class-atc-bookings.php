<?php
// ═══════════════════════════════════════════════════════════════════
// FILE 3: class-atc-bookings.php (ENHANCED)
// Location: includes/booking/class-atc-bookings.php
// ═══════════════════════════════════════════════════════════════════

class ATC_Bookings {

    /**
     * ✅ Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = ATC_TABLE_BOOKINGS;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id VARCHAR(50) NOT NULL,
            service VARCHAR(50) NOT NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            customer_name VARCHAR(200) NOT NULL,
            customer_email VARCHAR(200) NOT NULL,
            customer_phone VARCHAR(50) NULL,
            price_total DECIMAL(12,2) DEFAULT 0,
            currency VARCHAR(10) DEFAULT 'INR',
            status VARCHAR(50) DEFAULT 'pending',
            payment_status VARCHAR(20) DEFAULT 'unpaid',
            form_data LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_id_unique (booking_id),
            KEY service (service),
            KEY status (status),
            KEY customer_email (customer_email)
        ) $charset_collate;";
        dbDelta($sql);
    }
    
    /**
     * ✅ Self-healing: Ensure all required columns exist
     */
    private static function ensure_booking_columns() {
        global $wpdb;
        
        // Check if we've already run this check recently (avoid running on every insert)
        $last_check = get_transient('atc_booking_columns_check');
        if ($last_check) {
            return;
        }
        
        $table = ATC_TABLE_BOOKINGS;
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM $table");
        
        // Required columns that might be missing
        $required_columns = [
            'package_id' => 'BIGINT(20) UNSIGNED NULL AFTER service',
            'customer_id' => 'BIGINT(20) UNSIGNED NULL AFTER customer_phone',
            'destination' => 'VARCHAR(255) NULL AFTER customer_phone',
            'travel_date' => 'DATE NULL AFTER destination',
            'return_date' => 'DATE NULL AFTER travel_date',
            'adults' => 'INT DEFAULT 1 AFTER return_date',
            'children' => 'INT DEFAULT 0 AFTER adults',
            'package_name' => 'VARCHAR(255) NULL AFTER children',
            'package_snapshot' => 'LONGTEXT NULL AFTER package_name',
        ];
        
        $columns_added = false;
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN $column $definition");
                $columns_added = true;
            }
        }
        
        if ($columns_added && class_exists('ATC_Logger')) {
            ATC_Logger::log('info', 'Self-healed: Added missing columns to bookings table');
        }
        
        // Cache this check for 1 hour
        set_transient('atc_booking_columns_check', time(), HOUR_IN_SECONDS);
    }

    /**
     * ✅ ENHANCED: Insert booking with validation
     */
    public static function insert_booking($data) {
        global $wpdb;
        
        // Self-healing: Ensure all required columns exist
        self::ensure_booking_columns();
        
        // Use validator if available
        if (class_exists('ATC_Booking_Validator')) {
            $data = ATC_Booking_Validator::sanitize_booking_data($data);
            
            $validation = ATC_Booking_Validator::validate_booking_data($data);
            if (is_wp_error($validation)) {
                return false;
            }
        }
        
        // Ensure booking_id is set
        if (empty($data['booking_id'])) {
            if (class_exists('ATC_Booking_Validator')) {
                $data['booking_id'] = ATC_Booking_Validator::generate_booking_id();
            } else {
                $data['booking_id'] = 'ATC-' . date('Ymd-His') . '-' . wp_rand(1000, 9999);
            }
        }
        
        $insert_data = [
            'booking_id'     => $data['booking_id'],
            'service'        => $data['service'],
            'package_id'     => $data['package_id'] ?? null,
            'user_id'        => $data['user_id'] ?: null,
            'customer_name'  => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? '',
            'destination'    => $data['destination'] ?? '',
            'travel_date'    => !empty($data['travel_date']) ? $data['travel_date'] : null,
            'price_total'    => $data['price_total'],
            'currency'       => $data['currency'] ?? 'INR',
            'status'         => $data['status'] ?? 'pending',
            'payment_status' => $data['payment_status'] ?? 'unpaid',
            'package_name'   => $data['package_name'] ?? '',
            'package_snapshot' => $data['package_snapshot'] ?? null,
            'form_data'      => maybe_serialize($data['form_data'] ?? $data),
        ];
        
        $result = $wpdb->insert(ATC_TABLE_BOOKINGS, $insert_data);
        
        if ($result) {
            $booking_id = $wpdb->insert_id;
            
            // Create/update customer record
            if (class_exists('ATC_Customers')) {
                ATC_Customers::get_or_create($data['customer_email'], [
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'] ?? '',
                    'user_id' => $data['user_id'] ?? null,
                ]);
            }
            
            return $booking_id;
        }
        
        // Log error for debugging
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('error', 'Booking insert failed: ' . $wpdb->last_error);
        } else {
            error_log('ATC Booking Insert Error: ' . $wpdb->last_error);
        }
        
        return false;
    }

    /**
     * ✅ Insert search log
     */
    public static function insert_search_log($data) {
        global $wpdb;

        $wpdb->insert(ATC_TABLE_SEARCH_LOGS, [
            'user_id'    => intval($data['user_id'] ?? 0),
            'service'    => sanitize_text_field($data['service'] ?? ''),
            'query_data' => json_encode($data),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => current_time('mysql'),
        ]);

        return $wpdb->insert_id;
    }

    /**
     * ✅ Log notification
     */
    public static function log_notification($booking_id, $channel, $status = 'sent', $response = '') {
        global $wpdb;

        $wpdb->insert(ATC_TABLE_NOTIFICATIONS, [
            'booking_id' => intval($booking_id),
            'channel'    => sanitize_text_field($channel),
            'status'     => sanitize_text_field($status),
            'response'   => maybe_serialize($response),
            'created_at' => current_time('mysql'),
        ]);

        return $wpdb->insert_id;
    }

    /**
     * ✅ Get booking by ID
     */
    public static function get_booking($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id=%d", 
            $id
        ), ARRAY_A);
    }

    /**
     * ✅ Get bookings by user
     */
    public static function get_bookings_by_user($user_id, $limit = 50) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE user_id=%d 
            ORDER BY created_at DESC 
            LIMIT %d", 
            $user_id,
            $limit
        ), ARRAY_A);
    }
    
    /**
     * ✅ NEW: Update booking status
     */
    public static function update_status($booking_id, $status) {
        global $wpdb;
        
        // 1. Get Old Status & Data (ADDED)
        $booking_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking_data) return false;
        $old_status = $booking_data['status'];
        
        // 2. Update Database
        $result = $wpdb->update(
            ATC_TABLE_BOOKINGS,
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $booking_id]
        );
        
        // 3. Fire Hook (ADDED)
        if ($result !== false && $old_status !== $status) {
            $booking_data['status'] = $status;
            do_action('atc_booking_status_changed', $booking_id, $old_status, $status, $booking_data);
        }
        
        return $result;
    }
    
    /**
     * ✅ NEW: Update payment status
     */
    public static function update_payment_status($booking_id, $payment_status) {
        global $wpdb;
        
        return $wpdb->update(
            ATC_TABLE_BOOKINGS,
            ['payment_status' => $payment_status, 'updated_at' => current_time('mysql')],
            ['id' => $booking_id]
        );
    }
}