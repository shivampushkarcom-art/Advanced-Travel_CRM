<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * ATC Installer - COMPLETE DATABASE SETUP (ENHANCED v2.3.1)
 * Location: includes/core/class-atc-installer.php
 * 
 * CRITICAL FIXES:
 * ✅ Added search_logs table
 * ✅ Fixed unique constraints to prevent duplicates
 * ✅ Added proper indexes for performance
 * ✅ Added migration system
 * ✅ Fixed all table creation errors
 * ═══════════════════════════════════════════════════════════════════
 */

if (!defined('ABSPATH')) exit;

class ATC_Installer {
    
    /**
     * Run installation
     */
    public static function activate() {
        try {
            self::create_tables();
            self::create_default_options();
            self::create_roles_capabilities();
            self::insert_default_data();
            self::schedule_cron_jobs();
            
            update_option('atc_version', ATC_VERSION);
            update_option('atc_db_version', ATC_DB_VERSION);
            update_option('atc_install_date', current_time('mysql'));
            
            // Clear cache
            wp_cache_flush();
            
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('info', 'Installation completed successfully');
            }
            
        } catch (Exception $e) {
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('error', 'Installation failed: ' . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * ✅ FIXED: Create all database tables with proper constraints
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $tables_created = 0;
        
        // ==================== TABLE 1: BOOKINGS ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_BOOKINGS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id VARCHAR(50) NOT NULL,
            service VARCHAR(50) NOT NULL,
            package_id BIGINT(20) UNSIGNED NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            customer_id BIGINT(20) UNSIGNED NULL,
            status VARCHAR(20) DEFAULT 'pending',
            customer_name VARCHAR(191) NOT NULL,
            customer_email VARCHAR(191) NOT NULL,
            package_name VARCHAR(191),
            package_snapshot LONGTEXT,
            customer_phone VARCHAR(50),
            destination VARCHAR(255),
            travel_date DATE NULL,
            return_date DATE NULL,
            adults INT DEFAULT 1,
            children INT DEFAULT 0,
            price_total DECIMAL(12,2) DEFAULT 0,
            currency VARCHAR(10) DEFAULT 'INR',
            payment_status VARCHAR(20) DEFAULT 'unpaid',
            payment_method VARCHAR(50),
            form_data LONGTEXT,
            notes TEXT,
            admin_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_id (booking_id),
            KEY service (service),
            KEY package_id (package_id),
            KEY user_id (user_id),
            KEY customer_email (customer_email),
            KEY status (status),
            KEY payment_status (payment_status),
            KEY travel_date (travel_date),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 2: CUSTOMERS ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_CUSTOMERS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NULL,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) NOT NULL,
            phone VARCHAR(50),
            address TEXT,
            city VARCHAR(100),
            state VARCHAR(100),
            country VARCHAR(100) DEFAULT 'India',
            total_bookings INT DEFAULT 0,
            total_spent DECIMAL(12,2) DEFAULT 0,
            lifetime_value DECIMAL(12,2) DEFAULT 0,
            last_booking_date DATETIME,
            customer_type VARCHAR(20) DEFAULT 'regular',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY user_id (user_id),
            KEY customer_type (customer_type),
            KEY total_spent (total_spent)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 3: LEADS ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_LEADS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) DEFAULT '',
            email VARCHAR(191) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            service VARCHAR(50),
            destination VARCHAR(255),
            budget_min DECIMAL(10,2) DEFAULT 0,
            budget_max DECIMAL(10,2) DEFAULT 0,
            date_from DATE NULL,
            date_to DATE NULL,
            adults INT DEFAULT 1,
            children INT DEFAULT 0,
            user_id BIGINT(20) UNSIGNED NULL,
            assigned_to BIGINT(20) UNSIGNED NULL,
            metadata LONGTEXT,
            status VARCHAR(20) DEFAULT 'new',
            score INT DEFAULT 0,
            temperature VARCHAR(10) DEFAULT 'cold',
            source VARCHAR(100) DEFAULT 'search_form',
            search_count INT DEFAULT 1,
            utm_source VARCHAR(100),
            utm_medium VARCHAR(100),
            utm_campaign VARCHAR(100),
            ip VARCHAR(45),
            currency VARCHAR(10) DEFAULT 'INR',
            device_type VARCHAR(20),
            browser VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY phone (phone),
            KEY status (status),
            KEY score (score),
            KEY temperature (temperature),
            KEY service (service),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 4: LEAD ACTIVITIES ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_LEAD_ACTIVITIES . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT(20) UNSIGNED NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            description TEXT,
            user_id BIGINT(20) UNSIGNED,
            metadata LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lead_id (lead_id),
            KEY activity_type (activity_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 5: SERVICES ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_SERVICES . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            service_key VARCHAR(50) NOT NULL,
            name VARCHAR(191) NOT NULL,
            description TEXT,
            destination VARCHAR(255),
            price DECIMAL(10,2) DEFAULT 0,
            original_price DECIMAL(10,2) DEFAULT 0,
            duration_days INT DEFAULT 1,
            featured TINYINT(1) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            image_url VARCHAR(500),
            images LONGTEXT,
            features TEXT,
            inclusions TEXT,
            exclusions TEXT,
            itinerary LONGTEXT,
            rating DECIMAL(3,2) DEFAULT 0,
            reviews_count INT DEFAULT 0,
            discount INT DEFAULT 0,
            metadata LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY service_key (service_key),
            KEY destination (destination),
            KEY status (status),
            KEY featured (featured),
            KEY price (price)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 6: NOTIFICATIONS ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_NOTIFICATIONS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NULL,
            lead_id BIGINT(20) UNSIGNED NULL,
            query_id BIGINT(20) UNSIGNED NULL,
            type VARCHAR(50),
            channel VARCHAR(20),
            recipient VARCHAR(191),
            payload LONGTEXT,
            status VARCHAR(20) DEFAULT 'pending',
            attempts INT DEFAULT 0,
            response LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY lead_id (lead_id),
            KEY query_id (query_id),
            KEY type (type),
            KEY channel (channel),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // Add query_id column if it doesn't exist (for existing installations)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM " . ATC_TABLE_NOTIFICATIONS . " LIKE 'query_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE " . ATC_TABLE_NOTIFICATIONS . " ADD COLUMN query_id BIGINT(20) UNSIGNED NULL AFTER lead_id");
            $wpdb->query("ALTER TABLE " . ATC_TABLE_NOTIFICATIONS . " ADD KEY query_id (query_id)");
        }
        
        // ==================== TABLE 7: PAYMENTS ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_PAYMENTS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NOT NULL,
            transaction_id VARCHAR(100) NOT NULL,
            payment_method VARCHAR(50),
            gateway VARCHAR(50),
            amount DECIMAL(12,2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'INR',
            status VARCHAR(20) DEFAULT 'pending',
            payment_type VARCHAR(20) DEFAULT 'online',
            payment_proof VARCHAR(500),
            payment_date DATETIME NULL,
            admin_notes TEXT,
            gateway_response LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_id (transaction_id),
            KEY booking_id (booking_id),
            KEY status (status),
            KEY gateway (gateway),
            KEY payment_type (payment_type)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 8: EMAIL TEMPLATES ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_EMAIL_TEMPLATES . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            subject VARCHAR(255),
            body LONGTEXT,
            whatsapp_body TEXT,
            sms_body TEXT,
            variables TEXT,
            type VARCHAR(50) DEFAULT 'booking',
            recipient_type VARCHAR(20) DEFAULT 'customer',
            active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY type (type),
            KEY recipient_type (recipient_type)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 9: WHATSAPP TEMPLATES ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_WHATSAPP_TEMPLATES . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            message_body LONGTEXT NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            booking_status VARCHAR(50) NULL,
            recipient_type VARCHAR(20) DEFAULT 'customer',
            variables TEXT,
            active TINYINT(1) DEFAULT 1,
            priority INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY event_type (event_type),
            KEY booking_status (booking_status),
            KEY recipient_type (recipient_type),
            KEY active (active)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 10: AUTOMATION RULES ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_AUTOMATION_RULES . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            trigger_event VARCHAR(50) NOT NULL,
            conditions LONGTEXT,
            actions LONGTEXT,
            active TINYINT(1) DEFAULT 1,
            priority INT DEFAULT 10,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trigger_event (trigger_event),
            KEY active (active)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 10: ADMIN RECIPIENTS ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_ADMIN_RECIPIENTS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191),
            phone VARCHAR(50),
            email_enabled TINYINT(1) DEFAULT 1,
            whatsapp_enabled TINYINT(1) DEFAULT 0,
            sms_enabled TINYINT(1) DEFAULT 0,
            whatsapp_mode VARCHAR(20) DEFAULT 'semi-auto',
            notify_events TEXT,
            role VARCHAR(50) DEFAULT 'admin',
            active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY active (active)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== ✅ NEW: TABLE 11: SEARCH LOGS ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . ATC_TABLE_SEARCH_LOGS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NULL,
            session_id VARCHAR(100),
            service VARCHAR(50),
            query_data LONGTEXT,
            results_count INT DEFAULT 0,
            ip VARCHAR(45),
            device_type VARCHAR(20),
            browser VARCHAR(50),
            referrer TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY service (service),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== ✅ ENHANCED: TABLE 12: CUSTOM PACKAGES (Enterprise Level) ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "atc_custom_packages (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            package_id VARCHAR(50) NOT NULL DEFAULT '',
            service_key VARCHAR(50) NOT NULL,
            name VARCHAR(191) NOT NULL,
            short_description TEXT,
            description LONGTEXT,
            destination VARCHAR(255),
            price DECIMAL(10,2) DEFAULT 0,
            original_price DECIMAL(10,2) DEFAULT 0,
            duration_days INT DEFAULT 1,
            duration_nights INT DEFAULT 0,
            adults INT DEFAULT 1,
            children INT DEFAULT 0,
            featured TINYINT(1) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            image_url VARCHAR(500),
            images LONGTEXT,
            gallery_images LONGTEXT,
            features TEXT,
            highlights TEXT,
            inclusions TEXT,
            exclusions TEXT,
            itinerary LONGTEXT,
            day_wise_itinerary LONGTEXT,
            terms_conditions TEXT,
            cancellation_policy TEXT,
            refund_policy TEXT,
            category VARCHAR(100),
            package_type VARCHAR(100),
            tags TEXT,
            activities TEXT,
            rating DECIMAL(3,2) DEFAULT 0,
            reviews_count INT DEFAULT 0,
            discount INT DEFAULT 0,
            group_id BIGINT(20) UNSIGNED NULL,
            sort_order INT DEFAULT 0,
            view_count INT DEFAULT 0,
            booking_count INT DEFAULT 0,
            metadata LONGTEXT,
            created_by BIGINT(20) UNSIGNED NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY package_id (package_id),
            KEY service_key (service_key),
            KEY destination (destination),
            KEY status (status),
            KEY featured (featured),
            KEY price (price),
            KEY category (category),
            KEY package_type (package_type),
            KEY group_id (group_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // Migrate/update existing table structure if needed
        self::migrate_custom_packages_table();
        
        // ==================== ✅ NEW: TABLE 13: PACKAGE GROUPS (Featured Collections) ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "atc_package_groups (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id VARCHAR(50) NOT NULL,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            description TEXT,
            service_key VARCHAR(50),
            image_url VARCHAR(500),
            display_type VARCHAR(50) DEFAULT 'grid',
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_id (group_id),
            UNIQUE KEY slug (slug),
            KEY service_key (service_key),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== ✅ NEW: TABLE 13.5: PACKAGE-GROUP RELATIONSHIP (Many-to-Many) ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "atc_package_group_relations (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            package_id BIGINT(20) UNSIGNED NOT NULL,
            group_id BIGINT(20) UNSIGNED NOT NULL,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY package_group (package_id, group_id),
            KEY package_id (package_id),
            KEY group_id (group_id),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== ✅ NEW: TABLE 14: PACKAGE QUERIES/REQUESTS ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "atc_package_queries (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            query_id VARCHAR(50) NOT NULL,
            customer_name VARCHAR(191) NOT NULL,
            customer_email VARCHAR(191) NOT NULL,
            customer_phone VARCHAR(50),
            service_key VARCHAR(50),
            package_id BIGINT(20) UNSIGNED NULL,
            query_type VARCHAR(50) DEFAULT 'custom_package',
            destination VARCHAR(255),
            travel_date DATE NULL,
            return_date DATE NULL,
            adults INT DEFAULT 1,
            children INT DEFAULT 0,
            budget_min DECIMAL(10,2) DEFAULT 0,
            budget_max DECIMAL(10,2) DEFAULT 0,
            special_requirements TEXT,
            message TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            assigned_to BIGINT(20) UNSIGNED NULL,
            admin_notes TEXT,
            response TEXT,
            user_id BIGINT(20) UNSIGNED NULL,
            metadata LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY query_id (query_id),
            KEY customer_email (customer_email),
            KEY service_key (service_key),
            KEY package_id (package_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        // ==================== TABLE 15: VISITOR TRACKING ====================
        $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "atc_visitor_tracking (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(100) NOT NULL,
            visitor_id VARCHAR(100),
            ip_address VARCHAR(45),
            user_agent TEXT,
            device_type VARCHAR(50),
            browser VARCHAR(100),
            os VARCHAR(100),
            screen_resolution VARCHAR(50),
            country VARCHAR(100),
            city VARCHAR(100),
            state VARCHAR(100),
            timezone VARCHAR(50),
            referrer_url TEXT,
            landing_page VARCHAR(500),
            current_page VARCHAR(500),
            user_id BIGINT(20) UNSIGNED NULL,
            is_logged_in TINYINT(1) DEFAULT 0,
            activity_type VARCHAR(50),
            activity_data LONGTEXT,
            service VARCHAR(50),
            package_id BIGINT(20) UNSIGNED NULL,
            search_query TEXT,
            booking_id VARCHAR(50),
            query_id VARCHAR(50),
            page_views INT DEFAULT 1,
            session_duration INT DEFAULT 0,
            first_visit DATETIME,
            last_visit DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY visitor_id (visitor_id),
            KEY ip_address (ip_address),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY service (service),
            KEY created_at (created_at),
            KEY last_visit (last_visit)
        ) $charset_collate;";
        
        dbDelta($sql);
        $tables_created++;
        
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('info', "Database tables created: $tables_created");
        }
    }
    
    /**
     * ✅ NEW: Create missing tables only
     */
    public static function create_missing_tables() {
        global $wpdb;
        
        $required_tables = [
            ATC_TABLE_BOOKINGS,
            ATC_TABLE_CUSTOMERS,
            ATC_TABLE_LEADS,
            ATC_TABLE_LEAD_ACTIVITIES,
            ATC_TABLE_SERVICES,
            ATC_TABLE_NOTIFICATIONS,
            ATC_TABLE_PAYMENTS,
            ATC_TABLE_EMAIL_TEMPLATES,
            ATC_TABLE_AUTOMATION_RULES,
            ATC_TABLE_ADMIN_RECIPIENTS,
            ATC_TABLE_SEARCH_LOGS,
            ATC_TABLE_CUSTOM_PACKAGES,
            ATC_TABLE_PACKAGE_GROUPS,
            ATC_TABLE_PACKAGE_GROUP_RELATIONS,
            ATC_TABLE_PACKAGE_QUERIES,
        ];
        
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if ($exists != $table) {
                self::create_tables();
                break;
            }
        }
    }
    
    /**
     * ✅ NEW: Add unique constraints if missing
     */
    public static function add_unique_constraints() {
        global $wpdb;
        
        // Check and add unique constraint on booking_id if missing
        $index_exists = $wpdb->get_var("
            SELECT COUNT(1) 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE table_schema = DATABASE() 
            AND table_name = '" . ATC_TABLE_BOOKINGS . "' 
            AND index_name = 'booking_id'
        ");
        
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE " . ATC_TABLE_BOOKINGS . " ADD UNIQUE KEY booking_id (booking_id)");
        }
        
        // Check and add unique constraint on email in customers
        $index_exists = $wpdb->get_var("
            SELECT COUNT(1) 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE table_schema = DATABASE() 
            AND table_name = '" . ATC_TABLE_CUSTOMERS . "' 
            AND index_name = 'email'
        ");
        
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE " . ATC_TABLE_CUSTOMERS . " ADD UNIQUE KEY email (email)");
        }
    }
    
    /**
     * Create default options
     */
    private static function create_default_options() {
        $defaults = [
            // General
            'atc_currency' => 'INR',
            'atc_currency_symbol' => '₹',
            
            // Features
            'atc_feature_booking_system' => 1,
            'atc_feature_lead_capture' => 1,
            'atc_feature_customer_portal' => 1,
            'atc_feature_admin_notifications' => 1,
            'atc_feature_payment_gateway' => 0,
            'atc_feature_email_notifications' => 1,
            'atc_feature_whatsapp_notifications' => 1,
            
            // Services Status (all enabled by default)
            'atc_services_status' => [
                'tours' => 1,
                'hotels' => 1,
                'flights' => 1,
                'trains' => 1,
                'safari' => 1,
                'cars' => 1,
                'forex' => 1,
                'visa' => 1,
                'celebrity' => 1,
            ],
            
            // Email
            'atc_admin_email' => get_option('admin_email'),
            'atc_email_from_name' => get_bloginfo('name'),
            
            // Payment
            'atc_payment_enabled' => 0,
            'atc_payment_required' => 0,
            'atc_payment_manual_allowed' => 1,
            
            // OTP
            'atc_whatsapp_otp_enabled' => 1,
            
            // Cancellation
            'atc_cancellation_enabled' => 1,
            'atc_cancellation_window' => 24,
            'atc_cancellation_requires_approval' => 0,
            'atc_refund_policy' => 'full',
            
            // Lead Scoring
            'atc_lead_score_threshold_hot' => 75,
            'atc_lead_score_threshold_warm' => 50,
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Create roles and capabilities
     */
    private static function create_roles_capabilities() {
        $admin = get_role('administrator');
        if ($admin) {
            $caps = [
                'manage_atc',
                'manage_atc_bookings',
                'manage_atc_leads',
                'view_atc_analytics',
                'manage_atc_payments',
                'manage_atc_settings',
            ];
            foreach ($caps as $cap) {
                $admin->add_cap($cap);
            }
        }
    }
    
    /**
     * Insert default data
     */
    private static function insert_default_data() {
        global $wpdb;
        
        // Check if templates already exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM " . ATC_TABLE_EMAIL_TEMPLATES);
        if ($count > 0) return;
        
        $templates = [
            [
                'name' => 'Admin: New Booking Alert',
                'slug' => 'admin_new_booking',
                'subject' => '🎫 New Booking #{booking_id}',
                'body' => "Hi there,\n\nGreat news! A new booking has been received.\n\n📋 Booking Details:\nBooking ID: {booking_id}\nService: {service_name}\nCustomer: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\nAmount: {currency} {price_total}\nStatus: Pending\n\nBest regards,\n{company_name} Team",
                'whatsapp_body' => "🎫 *NEW BOOKING RECEIVED*\n\n📋 Booking ID: {booking_id}\n👤 Customer: {customer_name}\n📱 Phone: {customer_phone}\n💰 Amount: {currency} {price_total}\n\n_{company_name}_",
                'variables' => 'booking_id,service_name,customer_name,customer_email,customer_phone,price_total,currency,company_name',
                'type' => 'booking',
                'recipient_type' => 'admin',
                'active' => 1,
            ],
            [
                'name' => 'Customer: Booking Confirmation',
                'slug' => 'customer_booking_confirmation',
                'subject' => '✅ Booking Confirmed - {booking_id}',
                'body' => "Dear {customer_name},\n\nThank you for choosing {company_name}! 🎉\n\nYour booking has been confirmed successfully.\n\n📋 Your Booking Details:\n━━━━━━━━━━━━━━━\nBooking ID: {booking_id}\nService: {service_name}\nTravel Date: {travel_date}\nTotal Amount: {currency} {price_total}\nPayment Status: {payment_status}\n\nWe'll send you more details soon.\n\nBest regards,\n{company_name} Team\n{support_email}",
                'whatsapp_body' => "✅ *BOOKING CONFIRMED!*\n\nHi {customer_name}! 👋\n\nYour booking is confirmed! 🎉\n\n📋 Details:\n🆔 {booking_id}\n📅 {travel_date}\n💰 {currency} {price_total}\n\n_{company_name}_",
                'variables' => 'booking_id,customer_name,service_name,travel_date,price_total,currency,payment_status,company_name,support_email',
                'type' => 'booking',
                'recipient_type' => 'customer',
                'active' => 1,
            ],
            [
                'name' => 'Admin: Hot Lead Alert',
                'slug' => 'admin_hot_lead',
                'subject' => '🔥 Hot Lead - {destination}',
                'body' => "🔥 HIGH PRIORITY LEAD!\n\n👤 Lead Details:\n━━━━━━━━━━━━━━━\nName: {lead_name}\nEmail: {lead_email}\nPhone: {lead_phone}\nDestination: {destination}\nBudget: {currency} {budget_min} - {budget_max}\nScore: {score}/100\nTemperature: {temperature}\n\n👉 View Lead:\n{lead_url}\n\n⚡ FOLLOW UP IMMEDIATELY!",
                'whatsapp_body' => "🔥 *HOT LEAD ALERT*\n\n👤 {lead_name}\n📱 {lead_phone}\n📍 {destination}\n💰 Budget: {currency} {budget_min}-{budget_max}\n⭐ Score: {score}/100\n\n🔗 {lead_url}\n\n_Contact immediately!_",
                'variables' => 'lead_name,lead_email,lead_phone,destination,budget_min,budget_max,currency,score,temperature,lead_url',
                'type' => 'lead',
                'recipient_type' => 'admin',
                'active' => 1,
            ],
        ];
        
        foreach ($templates as $template) {
            $wpdb->insert(ATC_TABLE_EMAIL_TEMPLATES, $template);
        }
        
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('info', 'Default email templates inserted');
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private static function schedule_cron_jobs() {
        if (class_exists('ATC_Email_Queue')) {
            add_filter('cron_schedules', ['ATC_Email_Queue', 'add_cron_schedule']);
        }

        if (!wp_next_scheduled('atc_daily_automation')) {
            wp_schedule_event(time(), 'daily', 'atc_daily_automation');
        }
        
        if (!wp_next_scheduled('atc_process_email_queue')) {
            wp_schedule_event(time(), 'every_5_minutes', 'atc_process_email_queue');
        }
    }
    
    /**
     * Deactivation
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('atc_daily_automation');
        wp_clear_scheduled_hook('atc_process_email_queue');
    }
    
    /**
     * Migrate custom packages table to add missing columns
     * This ensures existing tables get updated with new columns
     * Can be called manually or automatically during table creation
     */
    public static function migrate_custom_packages_table() {
        global $wpdb;
        
        $table_name = ATC_TABLE_CUSTOM_PACKAGES;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return; // Table doesn't exist, dbDelta will create it
        }
        
        // Get existing columns
        $existing_columns = [];
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        foreach ($columns as $column) {
            $existing_columns[strtolower($column->Field)] = true;
        }
        
        // List of columns that should exist (lowercase for comparison)
        $columns_to_add = [];
        
        if (!isset($existing_columns['short_description'])) {
            $columns_to_add[] = "ADD COLUMN short_description TEXT";
        }
        if (!isset($existing_columns['refund_policy'])) {
            $columns_to_add[] = "ADD COLUMN refund_policy TEXT";
        }
        if (!isset($existing_columns['metadata'])) {
            $columns_to_add[] = "ADD COLUMN metadata LONGTEXT";
        }
        if (!isset($existing_columns['created_by'])) {
            $columns_to_add[] = "ADD COLUMN created_by BIGINT(20) UNSIGNED NULL";
        }
        if (!isset($existing_columns['group_id'])) {
            $columns_to_add[] = "ADD COLUMN group_id BIGINT(20) UNSIGNED NULL";
        }
        if (!isset($existing_columns['sort_order'])) {
            $columns_to_add[] = "ADD COLUMN sort_order INT DEFAULT 0";
        }
        if (!isset($existing_columns['view_count'])) {
            $columns_to_add[] = "ADD COLUMN view_count INT DEFAULT 0";
        }
        if (!isset($existing_columns['booking_count'])) {
            $columns_to_add[] = "ADD COLUMN booking_count INT DEFAULT 0";
        }
        if (!isset($existing_columns['rating'])) {
            $columns_to_add[] = "ADD COLUMN rating DECIMAL(3,2) DEFAULT 0";
        }
        if (!isset($existing_columns['reviews_count'])) {
            $columns_to_add[] = "ADD COLUMN reviews_count INT DEFAULT 0";
        }
        if (!isset($existing_columns['discount'])) {
            $columns_to_add[] = "ADD COLUMN discount INT DEFAULT 0";
        }
        if (!isset($existing_columns['duration_nights'])) {
            $columns_to_add[] = "ADD COLUMN duration_nights INT DEFAULT 0";
        }
        if (!isset($existing_columns['original_price'])) {
            $columns_to_add[] = "ADD COLUMN original_price DECIMAL(10,2) DEFAULT 0";
        }
        if (!isset($existing_columns['gallery_images'])) {
            $columns_to_add[] = "ADD COLUMN gallery_images LONGTEXT";
        }
        if (!isset($existing_columns['day_wise_itinerary'])) {
            $columns_to_add[] = "ADD COLUMN day_wise_itinerary LONGTEXT";
        }
        if (!isset($existing_columns['category'])) {
            $columns_to_add[] = "ADD COLUMN category VARCHAR(100)";
        }
        if (!isset($existing_columns['package_type'])) {
            $columns_to_add[] = "ADD COLUMN package_type VARCHAR(100)";
        }
        if (!isset($existing_columns['tags'])) {
            $columns_to_add[] = "ADD COLUMN tags TEXT";
        }
        if (!isset($existing_columns['activities'])) {
            $columns_to_add[] = "ADD COLUMN activities TEXT";
        }
        
        // Add missing columns
        if (!empty($columns_to_add)) {
            $added_count = 0;
            foreach ($columns_to_add as $alter_sql) {
                $result = $wpdb->query("ALTER TABLE $table_name $alter_sql");
                if ($result !== false) {
                    $added_count++;
                } else {
                    // Log error but continue
                    if (class_exists('ATC_Logger')) {
                        ATC_Logger::log('error', 'Failed to add column: ' . $alter_sql . ' - ' . $wpdb->last_error);
                    }
                }
            }
            
            if ($added_count > 0 && class_exists('ATC_Logger')) {
                ATC_Logger::log('info', 'Migrated custom_packages table: Added ' . $added_count . ' columns');
            }
        }
        
        // Add indexes if they don't exist (check and add safely)
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        $existing_indexes = [];
        foreach ($indexes as $index) {
            $existing_indexes[strtolower($index->Key_name)] = true;
        }
        
        $indexes_to_add = [];
        if (!isset($existing_indexes['destination'])) {
            $indexes_to_add[] = "ADD INDEX destination (destination)";
        }
        if (!isset($existing_indexes['category'])) {
            $indexes_to_add[] = "ADD INDEX category (category)";
        }
        if (!isset($existing_indexes['package_type'])) {
            $indexes_to_add[] = "ADD INDEX package_type (package_type)";
        }
        if (!isset($existing_indexes['group_id'])) {
            $indexes_to_add[] = "ADD INDEX group_id (group_id)";
        }
        if (!isset($existing_indexes['price'])) {
            $indexes_to_add[] = "ADD INDEX price (price)";
        }
        
        // Add missing indexes
        if (!empty($indexes_to_add)) {
            foreach ($indexes_to_add as $alter_sql) {
                try {
                    $wpdb->query("ALTER TABLE $table_name $alter_sql");
                } catch (Exception $e) {
                    // Index might already exist, continue
                    if (class_exists('ATC_Logger')) {
                        ATC_Logger::log('warning', 'Index creation skipped: ' . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Migrate package_id to numeric format (match auto-increment id)
     * This ensures all packages have numeric package_id (1, 2, 3, etc.)
     */
    public static function migrate_package_ids() {
        global $wpdb;
        
        $table_name = ATC_TABLE_CUSTOM_PACKAGES;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0; // Table doesn't exist
        }
        
        // Update all packages to use numeric package_id (set package_id = id)
        // This will work for both old packages with non-numeric package_id and packages without proper package_id
        $result = $wpdb->query(
            "UPDATE $table_name SET package_id = CAST(id AS CHAR) WHERE package_id != CAST(id AS CHAR) OR package_id IS NULL OR package_id = ''"
        );
        
        $updated_count = $result !== false ? $result : 0;
        
        if ($updated_count > 0 && class_exists('ATC_Logger')) {
            ATC_Logger::log('info', "Migrated package_ids: Updated $updated_count packages to use numeric package_id format");
        }
        
        return $updated_count;
    }
}