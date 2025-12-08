<?php
/**
 * Plugin Name: Advanced Travel CRM Pro
 * Plugin URI: https://advancedtravelcrm.com
 * Description: Enterprise-level travel booking & CRM system with multi-service support, smart lead capture, payment integration, multi-admin notifications, and advanced analytics.
 * Version: 2.4.0
 * Author: Shivam Pushkar
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: advanced-travel-crm
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * 
 * @package AdvancedTravelCRM
 * 
 * PHASE 1 FIXES:
 * ✅ Added ATC_TABLE_SEARCH_LOGS constant
 * ✅ Improved error handling
 * ✅ Better file loading with fallbacks
 * ✅ Version migration system
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define Plugin Constants
define('ATC_VERSION', '2.4.0');
define('ATC_DB_VERSION', '2.3.0'); // ✅ NEW: Track database version
define('ATC_PLUGIN_FILE', __FILE__);
define('ATC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ATC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ATC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ATC_INCLUDES_DIR', ATC_PLUGIN_DIR . 'includes/');
define('ATC_ASSETS_URL', ATC_PLUGIN_URL . 'assets/');
define('ATC_SERVICES_DIR', ATC_PLUGIN_DIR . 'services/');
define('ATC_TEMPLATES_DIR', ATC_PLUGIN_DIR . 'templates/');

/**
 * Main Plugin Class - Singleton Pattern
 */
final class Advanced_Travel_CRM {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * ✅ FIXED: All table constants including missing ones
     */
    private function define_constants() {
        global $wpdb;
        
        // Core tables
        define('ATC_TABLE_BOOKINGS', $wpdb->prefix . 'atc_bookings');
        define('ATC_TABLE_CUSTOMERS', $wpdb->prefix . 'atc_customers');
        define('ATC_TABLE_LEADS', $wpdb->prefix . 'atc_leads');
        define('ATC_TABLE_LEAD_ACTIVITIES', $wpdb->prefix . 'atc_lead_activities');
        define('ATC_TABLE_SERVICES', $wpdb->prefix . 'atc_services');
        define('ATC_TABLE_NOTIFICATIONS', $wpdb->prefix . 'atc_notifications');
        define('ATC_TABLE_PAYMENTS', $wpdb->prefix . 'atc_payments');
        define('ATC_TABLE_EMAIL_TEMPLATES', $wpdb->prefix . 'atc_email_templates');
        define('ATC_TABLE_AUTOMATION_RULES', $wpdb->prefix . 'atc_automation_rules');
        define('ATC_TABLE_ADMIN_RECIPIENTS', $wpdb->prefix . 'atc_admin_recipients');
        
        // ✅ NEW: Missing table constant
        define('ATC_TABLE_SEARCH_LOGS', $wpdb->prefix . 'atc_search_logs');
        define('ATC_TABLE_CUSTOM_PACKAGES', $wpdb->prefix . 'atc_custom_packages');
        define('ATC_TABLE_PACKAGE_QUERIES', $wpdb->prefix . 'atc_package_queries');
        define('ATC_TABLE_PACKAGE_GROUPS', $wpdb->prefix . 'atc_package_groups');
        define('ATC_TABLE_PACKAGE_GROUP_RELATIONS', $wpdb->prefix . 'atc_package_group_relations');
        define('ATC_TABLE_WHATSAPP_TEMPLATES', $wpdb->prefix . 'atc_whatsapp_templates');
    }
    
    /**
     * ✅ ENHANCED: Better error handling and file existence checks
     */
    private function load_dependencies() {
        $files = [
            // ==================== CORE SYSTEM ====================
            'core/class-atc-installer.php',
            'core/class-atc-security.php',
            'core/class-atc-logger.php',
            'core/class-atc-plugin.php',
            'core/class-atc-service-ecosystem.php', // ✅ NEW: Service ecosystem framework
            'utilities/class-atc-db-health.php',
            'utilities/class-atc-template-seeder.php', // ✅ NEW: Template seeder
            
            // ==================== DATABASE MODELS ====================
            'booking/class-atc-bookings.php',
            'booking/class-atc-booking-validator.php', // ✅ NEW
            'customers/class-atc-customers.php',
            'class-atc-services.php',
            
            // ==================== CONFIGURATION ====================
            'utilities/class-atc-config-loader.php',
            'admin/class-atc-service-manager.php',
            'admin/class-atc-feature-manager.php',
            
            // ==================== API & REST ====================
            'api/class-atc-rest.php',
            'api/class-atc-ajax.php',
            
            // ==================== ADMIN & SETTINGS ====================
            'admin/class-atc-admin.php',
            'admin/class-atc-dashboard-enhanced.php', // ✅ NEW: Enhanced dashboard & analytics (replaces old analytics)
            // 'admin/class-atc-analytics.php', // ✅ REMOVED: Replaced by enhanced dashboard
            'admin/class-atc-settings.php',
            
            // ==================== UTILITIES ====================
            'utilities/class-atc-contact-us.php', // ✅ NEW: Premium Contact Us shortcode
            'core/class-atc-service-grid.php', // ✅ NEW: Premium service grid shortcode
            'packages/class-atc-celebrity-landing.php', // ✅ NEW: Celebrity Management landing page
            'admin/class-atc-celebrity-customizer.php', // ✅ NEW: Celebrity Landing Page Customizer
            'core/class-atc-hero-slider.php', // ✅ NEW: Hero Slider Widget (Flipkart-style)
            'core/class-atc-floating-query-button.php', // ✅ NEW: Floating Query Form Button
            'core/class-atc-astra-hamburger-menu.php', // ✅ NEW: Red Hamburger Menu for Astra Theme
            
            // ==================== HOMEPAGE & LANDING PAGES ====================
            // REMOVED: Homepage templates disabled per user request
            // 'homepage/class-atc-homepage-hero.php', // DISABLED: Homepage hero section
            // 'homepage/class-atc-homepage-sections.php', // DISABLED: Homepage sections
            // 'landing-pages/class-atc-base-landing.php', // DISABLED: Base landing page template
            // 'landing-pages/class-atc-service-landings.php', // DISABLED: Service landing pages removed (Celebrity kept separate)
            
            // ==================== CUSTOMIZATION SYSTEM ====================
            'customization/class-atc-error-handler.php', // ✅ NEW: Error handler (load first)
            'customization/class-atc-page-builder.php', // ✅ NEW: Drag-and-drop page builder
            'customization/class-atc-shortcode-manager.php', // ✅ NEW: Shortcode manager
            'customization/class-atc-text-editor.php', // ✅ NEW: Inline text editor
            'customization/class-atc-elementor-integration.php', // ✅ NEW: Elementor integration
            'customization/class-atc-astra-integration.php', // ✅ NEW: Astra theme integration
            
            // ==================== USER SYSTEM ====================
            'customers/class-atc-user.php',
            'customers/class-atc-auth.php',
            'customers/class-atc-otp.php',
            'customers/class-atc-customer-dashboard.php',
            
            // ==================== NOTIFICATIONS ====================
            'notifications/class-atc-notification-manager.php',
            'notifications/class-atc-email-sender.php',
            'notifications/class-atc-email-queue.php', // ✅ NEW
            'notifications/class-atc-email-templates.php',
            'notifications/class-atc-whatsapp-templates.php', // ✅ NEW: WhatsApp templates management
            'notifications/class-atc-whatsapp-sender.php', // ✅ NEW: WhatsApp API integration
            
            // ==================== VISITOR TRACKING ====================
            'tracking/class-atc-visitor-tracker.php', // ✅ NEW: JustDial-style visitor tracking
            
            // ==================== PAYMENTS ====================
            'payments/class-atc-payments.php',
            'payments/class-atc-webhook-handler.php', // ✅ NEW
            
            // ==================== LEAD MANAGEMENT ====================
            'leads/class-atc-lead-scoring.php',
            'leads/class-atc-lead-capture.php',
            'leads/class-atc-lead-popup.php', // ✅ NEW: Auto popup lead generation
            
            // ==================== SEARCH & BOOKING ====================
            'search/class-atc-search-engine.php',
            'search/class-atc-search-results.php', // Legacy - kept for backward compatibility
            'search/class-atc-premium-search.php', // ✅ NEW: Premium search page
            'search/class-atc-premium-results.php', // ✅ NEW: Premium results
            'booking/class-atc-premium-booking.php', // ✅ NEW: Premium booking wizard
            'booking/class-atc-booking-window.php',
            
            // ==================== PACKAGES ====================
            'packages/class-atc-package-details.php', // Legacy - kept as fallback (enhanced version overrides)
            'packages/class-atc-package-details-enhanced.php', // ✅ NEW: Enhanced MakeMyTrip-style (primary)
            'packages/class-atc-custom-packages.php', // ✅ NEW
            'packages/class-atc-package-groups.php', // ✅ NEW: Package groups management
            'packages/class-atc-package-query-enhanced.php', // ✅ NEW: Enhanced package query system (includes admin menu)
            
            // ==================== INTEGRATIONS ====================
            'integrations/class-atc-flight-api.php', // ✅ NEW: Flight API integration framework
            'integrations/class-atc-hotel-api.php', // ✅ NEW: Hotel API integration framework
            
            // ==================== CANCELLATION ====================
            'booking/class-atc-cancellation.php',
            
            // ==================== ANALYTICS & AUTOMATION ====================
            // 'admin/class-atc-analytics.php', // ✅ REMOVED: Replaced by class-atc-dashboard-enhanced.php
            'core/class-atc-automation.php',
            
            // ==================== STORIES FEATURE ====================
            'core/class-atc-stories.php', // ✅ NEW: Instagram/Facebook Stories-like feature
            
            // ==================== WHATSAPP BUTTON ====================
            'core/class-atc-contact-popup-button.php', // ✅ NEW: Floating Contact Popup Button (replaces WhatsApp)
        ];
        
        foreach ($files as $file) {
            $this->load_file($file);
        }
    }
    
    /**
     * ✅ ENHANCED: Better error handling with logging
     */
    private function load_file($filename) {
        $filepath = ATC_INCLUDES_DIR . $filename;
        
        if (file_exists($filepath)) {
            require_once $filepath;
        } else {
            // Log missing file
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('error', 'File not found: ' . $filename);
            }
            
            // Only show error in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ATC: Missing file - ' . $filename);
            }
        }
    }
    
    private function init_hooks() {
        register_activation_hook(ATC_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(ATC_PLUGIN_FILE, [$this, 'deactivate']);
        
        // Initialize early to ensure admin menus are registered
        add_action('plugins_loaded', [$this, 'init'], 0);
        add_action('init', [$this, 'init_components']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_notices', [$this, 'admin_notices']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // ✅ NEW: Version migration hook
        add_action('plugins_loaded', [$this, 'check_version_migration'], 5);
        
        // ✅ FIXED: Ensure admin menus are registered early (before other menus)
        // This is a fallback in case init() hasn't been called yet
        add_action('admin_menu', [$this, 'ensure_admin_menus'], 1);
    }
    
    /**
     * ✅ FIXED: Ensure admin menus are registered
     * This ensures classes are initialized so their admin_menu hooks fire
     */
    public function ensure_admin_menus() {
        // ✅ FIXED: First ensure parent menu is registered
        if (class_exists('ATC_Admin') && method_exists('ATC_Admin', 'init')) {
            ATC_Admin::init();
        }
        
        // Then ensure submenu classes are initialized - let their hooks register menus
        // This prevents duplicate registrations
        if (class_exists('ATC_Logger') && method_exists('ATC_Logger', 'init')) {
            ATC_Logger::init();
        }
        
        if (class_exists('ATC_DB_Health') && method_exists('ATC_DB_Health', 'init')) {
            ATC_DB_Health::init();
        }
        
        if (class_exists('ATC_Custom_Packages') && method_exists('ATC_Custom_Packages', 'init')) {
            ATC_Custom_Packages::init();
        }
        
        if (class_exists('ATC_Package_Groups') && method_exists('ATC_Package_Groups', 'init')) {
            ATC_Package_Groups::init();
        }
        
        // Initialize menu classes that need parent menu
        if (class_exists('ATC_Page_Builder') && method_exists('ATC_Page_Builder', 'init')) {
            ATC_Page_Builder::init();
        }
        
        if (class_exists('ATC_Shortcode_Manager') && method_exists('ATC_Shortcode_Manager', 'init')) {
            ATC_Shortcode_Manager::init();
        }
        
        if (class_exists('ATC_Stories') && method_exists('ATC_Stories', 'init')) {
            ATC_Stories::init();
        }
        
        // WhatsApp button replaced by Contact Popup Button
        // if (class_exists('ATC_WhatsApp_Button') && method_exists('ATC_WhatsApp_Button', 'init')) {
        //     ATC_WhatsApp_Button::init();
        // }
        
        // Initialize Contact Popup Button (replaces WhatsApp button)
        if (class_exists('ATC_Contact_Popup_Button') && method_exists('ATC_Contact_Popup_Button', 'init')) {
            ATC_Contact_Popup_Button::init();
        }
        
        // ATC_Package_Query_Enhanced is initialized in components array
    }
    
    public function activate() {
        try {
            if (class_exists('ATC_Installer')) {
                ATC_Installer::activate();
            }
            flush_rewrite_rules();
            update_option('atc_activation_redirect', true);
            
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('info', 'ATC Pro v' . ATC_VERSION . ' activated successfully');
            }
        } catch (Exception $e) {
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('error', 'Activation failed: ' . $e->getMessage());
            }
            wp_die('Plugin activation failed: ' . $e->getMessage());
        }
    }
    
    public function deactivate() {
        if (class_exists('ATC_Installer') && method_exists('ATC_Installer', 'deactivate')) {
            ATC_Installer::deactivate();
        }
        flush_rewrite_rules();
        
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('info', 'ATC Pro v' . ATC_VERSION . ' deactivated');
        }
    }
    
    /**
     * ✅ NEW: Version migration system
     */
    public function check_version_migration() {
        $installed_version = get_option('atc_version', '0.0.0');
        
        if (version_compare($installed_version, ATC_VERSION, '<')) {
            $this->run_migrations($installed_version, ATC_VERSION);
            update_option('atc_version', ATC_VERSION);
            update_option('atc_db_version', ATC_DB_VERSION);
        }
    }
    
    /**
     * ✅ NEW: Run database migrations
     */
    private function run_migrations($from_version, $to_version) {
        // Migration from 2.2.0 to 2.3.0
        if (version_compare($from_version, '2.3.0', '<')) {
            if (class_exists('ATC_Installer')) {
                // Ensure new tables exist
                ATC_Installer::create_missing_tables();
                
                // Add unique constraints if missing
                ATC_Installer::add_unique_constraints();
                
                if (class_exists('ATC_Logger')) {
                    ATC_Logger::log('info', 'Migrated from ' . $from_version . ' to ' . $to_version);
                }
            }
        }
        
        // Migration for custom packages table (add missing columns)
        // This runs for any version to ensure table structure is up to date
        if (class_exists('ATC_Installer') && method_exists('ATC_Installer', 'migrate_custom_packages_table')) {
            ATC_Installer::migrate_custom_packages_table();
        }
        
        // Migration for package_ids (fix old packages)
        // This runs for any version to ensure all packages have proper package_id format
        if (class_exists('ATC_Installer') && method_exists('ATC_Installer', 'migrate_package_ids')) {
            ATC_Installer::migrate_package_ids();
        }
    }
    
    public function init() {
        // ✅ FIXED: Ensure capabilities FIRST (before anything else)
        $this->ensure_capabilities();
        
        // ✅ FIXED: Initialize admin menu classes FIRST before other components
        // This ensures admin menus are registered before admin_menu hook fires
        if (is_admin()) {
            // First, ensure parent menu is initialized
            if (class_exists('ATC_Admin') && method_exists('ATC_Admin', 'init')) {
                ATC_Admin::init();
            }
            
            // Then initialize submenu classes
            if (class_exists('ATC_Logger') && method_exists('ATC_Logger', 'init')) {
                ATC_Logger::init();
            }
            if (class_exists('ATC_DB_Health') && method_exists('ATC_DB_Health', 'init')) {
                ATC_DB_Health::init();
            }
            if (class_exists('ATC_Custom_Packages') && method_exists('ATC_Custom_Packages', 'init')) {
                ATC_Custom_Packages::init();
            }
            
            if (class_exists('ATC_Package_Groups') && method_exists('ATC_Package_Groups', 'init')) {
                ATC_Package_Groups::init();
            }
            
            if (class_exists('ATC_Package_Query_Enhanced') && method_exists('ATC_Package_Query_Enhanced', 'init')) {
                ATC_Package_Query_Enhanced::init();
            }
        }
        
        // Check database health and run migrations
        if (is_admin()) {
            // Run version migrations
            $this->check_version_migration();
            
            // Migrate custom packages table if needed (ensures columns are added)
            if (class_exists('ATC_Installer') && method_exists('ATC_Installer', 'migrate_custom_packages_table')) {
                ATC_Installer::migrate_custom_packages_table();
            }
            
            if (class_exists('ATC_DB_Health')) {
                ATC_DB_Health::check_tables();
            }
        }
        
        // Initialize all components
        $components = [
            'ATC_Security',
            'ATC_Logger', // ✅ Has initialization guard - safe to call multiple times
            'ATC_DB_Health', // ✅ Has initialization guard - safe to call multiple times
            'ATC_Template_Seeder', // ✅ NEW: Seeds email & WhatsApp templates
            'ATC_Plugin', // ✅ NEW: Core plugin class (shortcodes, frontend functionality)
            'ATC_Service_Grid', // ✅ NEW: Premium service grid shortcode
            'ATC_Celebrity_Landing', // ✅ NEW: Celebrity Management landing page
            'ATC_Celebrity_Customizer', // ✅ NEW: Celebrity Landing Page Customizer
            'ATC_Hero_Slider', // ✅ NEW: Hero Slider Widget (Flipkart-style)
            'ATC_Floating_Query_Button', // ✅ NEW: Floating Query Form Button
            'ATC_Astra_Hamburger_Menu', // ✅ NEW: Red Hamburger Menu for Astra Theme
            'ATC_REST',
            'ATC_AJAX',
            'ATC_Admin', // ✅ Has initialization guard - safe to call multiple times
            'ATC_Dashboard_Enhanced', // ✅ NEW: Modern enhanced dashboard & analytics
            'ATC_Settings',
            'ATC_Service_Ecosystem', // ✅ NEW: Service ecosystem framework (must be before package details)
            'ATC_Service_Manager',
            'ATC_Feature_Manager',
            'ATC_User',
            'ATC_Auth',
            'ATC_Customer_Dashboard',
            'ATC_Notification_Manager',
            'ATC_Email_Templates',
            'ATC_WhatsApp_Templates', // ✅ NEW: Separate WhatsApp templates management
            'ATC_Email_Queue', // ✅ NEW
            'ATC_Payments',
            'ATC_Webhook_Handler', // ✅ NEW
            'ATC_Lead_Capture',
            'ATC_Lead_Popup', // ✅ NEW: Auto popup lead generation system
            'ATC_Search_Engine',
            'ATC_Search_Results', // ✅ NEW
            'ATC_Premium_Search', // ✅ NEW: Premium search page
            'ATC_Premium_Results', // ✅ NEW: Premium results display
            'ATC_Premium_Booking', // ✅ NEW: Premium booking wizard
            'ATC_Booking_Window',
            'ATC_Package_Details_Enhanced', // ✅ Primary: Enhanced MakeMyTrip-style package details
            // Note: ATC_Package_Details kept for REST API routes only (enhanced version overrides shortcode)
            'ATC_Custom_Packages', // ✅ Has initialization guard - safe to call multiple times
            'ATC_Package_Groups', // ✅ Has initialization guard - safe to call multiple times
            'ATC_Package_Query_Enhanced', // ✅ NEW: Enhanced package query system (includes admin menu)
            'ATC_Flight_API', // ✅ NEW: Flight API integration framework
            'ATC_Hotel_API', // ✅ NEW: Hotel API integration framework
            'ATC_Cancellation',
            // 'ATC_Analytics', // ✅ REMOVED: Replaced by ATC_Dashboard_Enhanced
            'ATC_Automation',
            'ATC_Contact_Us', // ✅ NEW: Premium Contact Us shortcode
        ];
        
        foreach ($components as $component) {
            if (class_exists($component) && method_exists($component, 'init')) {
                try {
                    $component::init();
                } catch (Exception $e) {
                    if (class_exists('ATC_Logger')) {
                        ATC_Logger::log('error', 'Failed to initialize ' . $component . ': ' . $e->getMessage());
                    }
                }
            }
        }
        
        // Initialize Customers (different pattern)
        if (class_exists('ATC_Customers') && method_exists('ATC_Customers', 'init')) {
            ATC_Customers::init();
        }
        
        // Activation redirect
        if (get_option('atc_activation_redirect', false)) {
            delete_option('atc_activation_redirect');
            if (!isset($_GET['activate-multi']) && !wp_doing_ajax()) {
                wp_safe_redirect(admin_url('admin.php?page=atc-dashboard'));
                exit;
            }
        }
    }
    
    private function ensure_capabilities() {
        // Always ensure capabilities are assigned (not just when user has manage_options)
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            $caps = [
                'manage_atc',
                'manage_atc_bookings',
                'manage_atc_leads',
                'view_atc_analytics',
                'manage_atc_payments',
                'manage_atc_settings',
            ];
            
            foreach ($caps as $cap) {
                if (!$admin_role->has_cap($cap)) {
                    $admin_role->add_cap($cap);
                }
            }
            
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('info', 'Capabilities ensured');
            }
        }
    }
    
    public function init_components() {
        // Reserved for future use
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'advanced-travel-crm',
            false,
            dirname(ATC_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function enqueue_frontend_assets() {
        // Styles
        wp_enqueue_style(
            'atc-frontend-styles',
            ATC_ASSETS_URL . 'css/atc-styles.css',
            [],
            ATC_VERSION
        );
        
        // Customer Account Styles
        $is_account_page = is_user_logged_in() || is_page('login') || is_page('register') || is_page('my-account') || is_page('verify-email') || is_page('forgot-password');
        if ($is_account_page) {
            wp_enqueue_style(
                'atc-account-styles',
                ATC_ASSETS_URL . 'css/atc-account.css',
                [],
                ATC_VERSION
            );
        }
        
        // Scripts (in correct order)
        wp_enqueue_script(
            'atc-calculation',
            ATC_ASSETS_URL . 'js/atc-calculation.js',
            ['jquery'],
            ATC_VERSION,
            true
        );
        
        wp_enqueue_script(
            'atc-search',
            ATC_ASSETS_URL . 'js/atc-search.js',
            ['jquery'],
            ATC_VERSION,
            true
        );
        
        wp_enqueue_script(
            'atc-frontend',
            ATC_ASSETS_URL . 'js/atc-frontend.js',
            ['jquery', 'atc-calculation', 'atc-search'],
            ATC_VERSION,
            true
        );
        
        wp_enqueue_script(
            'atc-booking-window',
            ATC_ASSETS_URL . 'js/atc-booking-window.js',
            ['jquery', 'atc-frontend'],
            ATC_VERSION,
            true
        );
        
        // Package details script - REMOVED: Enhanced version handles this
        // The enhanced package details class (ATC_Package_Details_Enhanced) handles asset enqueuing
        // This prevents 404 errors for non-existent files
        
        // Query form script - REMOVED: Enhanced version handles this
        // The enhanced query form class (ATC_Package_Query_Enhanced) handles asset enqueuing
        // Correct file is: atc-query-form-enhanced.js (not atc-query-form.js)
        // Enhanced version localizes its own scripts, no need to localize here
        
        // Customer Account JS
        $is_account_page = is_user_logged_in() || is_page('login') || is_page('register') || is_page('my-account') || is_page('verify-email') || is_page('forgot-password');
        if ($is_account_page) {
            wp_enqueue_script(
                'atc-account',
                ATC_ASSETS_URL . 'js/atc-account.js',
                ['jquery'],
                ATC_VERSION,
                true
            );
            
            wp_localize_script('atc-account', 'atcAccount', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('atc_nonce'),
                'strings' => [
                    'confirmCancel' => __('Are you sure you want to cancel this booking?', 'advanced-travel-crm'),
                    'emailSent' => __('Email sent successfully!', 'advanced-travel-crm'),
                    'error' => __('An error occurred. Please try again.', 'advanced-travel-crm'),
                ]
            ]);
        }
        
        // Main localization
        wp_localize_script('atc-frontend', 'atcVars', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('atc/v1/'),
            'nonce' => wp_create_nonce('atc_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'currency' => get_option('atc_currency', 'INR'),
            'currencySymbol' => get_option('atc_currency_symbol', '₹'),
            'paymentEnabled' => get_option('atc_payment_enabled', 0),
            'strings' => [
                'loading' => __('Loading...', 'advanced-travel-crm'),
                'bookingSuccess' => __('Booking submitted successfully!', 'advanced-travel-crm'),
                'bookingError' => __('Failed to submit booking. Please try again.', 'advanced-travel-crm'),
                'searchSuccess' => __('Search saved! We will get back to you soon.', 'advanced-travel-crm'),
            ]
        ]);
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'atc') === false && strpos($hook, 'advanced-travel-crm') === false) {
            return;
        }
        
        wp_enqueue_style(
            'atc-admin-styles',
            ATC_ASSETS_URL . 'css/atc-admin.css',
            [],
            ATC_VERSION
        );
        
        wp_enqueue_script(
            'atc-admin',
            ATC_ASSETS_URL . 'js/atc-admin.js',
            ['jquery', 'wp-api'],
            ATC_VERSION,
            true
        );
        
        wp_localize_script('atc-admin', 'atcAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('atc/v1/'),
            'nonce' => wp_create_nonce('atc_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    
    public function admin_notices() {
        // Configuration warning
        if (!get_option('atc_admin_email') && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('Advanced Travel CRM Pro:', 'advanced-travel-crm'); ?></strong>
                    <?php printf(
                        __('Please <a href="%s">configure your settings</a> to start receiving notifications.', 'advanced-travel-crm'),
                        admin_url('admin.php?page=atc-settings')
                    ); ?>
                </p>
            </div>
            <?php
        }
        
        // Database health warning
        if (get_transient('atc_db_health_warning') && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('⚠️ Advanced Travel CRM Pro:', 'advanced-travel-crm'); ?></strong>
                    <?php _e('Database tables are missing or corrupted.', 'advanced-travel-crm'); ?>
                    <a href="<?php echo admin_url('admin.php?page=atc-db-health'); ?>"><?php _e('Fix Now', 'advanced-travel-crm'); ?></a>
                </p>
            </div>
            <?php
        }
    }
}

/**
 * Initialize the plugin
 */
function atc_pro() {
    return Advanced_Travel_CRM::get_instance();
}

// Start the plugin
atc_pro();

/**
 * Helper function
 */
function ATC() {
    return Advanced_Travel_CRM::get_instance();
}

/**
 * Plugin Meta Information
 */
add_filter('plugin_row_meta', function($links, $file) {
    if ($file === ATC_PLUGIN_BASENAME) {
        $row_meta = [
            'docs' => '<a href="https://docs.advancedtravelcrm.com" target="_blank">' . __('Documentation', 'advanced-travel-crm') . '</a>',
            'support' => '<a href="https://support.advancedtravelcrm.com" target="_blank">' . __('Support', 'advanced-travel-crm') . '</a>',
        ];
        return array_merge($links, $row_meta);
    }
    return $links;
}, 10, 2);