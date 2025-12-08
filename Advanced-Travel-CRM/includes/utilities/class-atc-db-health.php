<?php
/**
 * ATC Database Health Checker
 * Checks and repairs database tables
 */

if (!defined('ABSPATH')) exit;

class ATC_DB_Health {
    
    private static $initialized = false;
    
    /**
     * Initialize
     */
    public static function init() {
        // Prevent double initialization
        if (self::$initialized) {
            return;
        }
        
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 20);
        add_action('admin_post_atc_repair_tables', [__CLASS__, 'repair_tables']);
        
        // Auto-fix missing columns on admin_init (once per hour)
        add_action('admin_init', [__CLASS__, 'ensure_all_columns']);
        
        self::$initialized = true;
    }
    
    /**
     * Ensure all required columns exist in all tables
     */
    public static function ensure_all_columns() {
        global $wpdb;
        
        // Check if we've already run this check recently
        $last_check = get_transient('atc_columns_health_check');
        if ($last_check) {
            return;
        }
        
        $columns_added = false;
        
        // ============ LEADS TABLE ============
        $leads_table = defined('ATC_TABLE_LEADS') ? ATC_TABLE_LEADS : $wpdb->prefix . 'atc_leads';
        $existing = $wpdb->get_col("SHOW COLUMNS FROM $leads_table");
        
        $leads_columns = [
            'user_agent' => 'TEXT NULL',
            'device_type' => 'VARCHAR(50) NULL',
            'browser' => 'VARCHAR(100) NULL',
            'utm_source' => 'VARCHAR(255) NULL',
            'utm_medium' => 'VARCHAR(255) NULL',
            'utm_campaign' => 'VARCHAR(255) NULL',
            'referrer' => 'TEXT NULL',
            'search_count' => 'INT DEFAULT 0',
            'last_activity_at' => 'DATETIME NULL',
            'metadata' => 'LONGTEXT NULL',
            'score' => 'INT DEFAULT 0',
            'temperature' => 'VARCHAR(20) DEFAULT "cold"',
        ];
        
        foreach ($leads_columns as $col => $def) {
            if (!in_array($col, $existing)) {
                $wpdb->query("ALTER TABLE $leads_table ADD COLUMN $col $def");
                $columns_added = true;
            }
        }
        
        // ============ CUSTOM PACKAGES TABLE ============
        $packages_table = defined('ATC_TABLE_CUSTOM_PACKAGES') ? ATC_TABLE_CUSTOM_PACKAGES : $wpdb->prefix . 'atc_custom_packages';
        if ($wpdb->get_var("SHOW TABLES LIKE '$packages_table'") == $packages_table) {
            $existing = $wpdb->get_col("SHOW COLUMNS FROM $packages_table");
            
            $packages_columns = [
                'package_name' => 'VARCHAR(255) NULL AFTER id',
                'name' => 'VARCHAR(255) NULL',
                'service' => 'VARCHAR(50) NULL',
                'destination' => 'VARCHAR(255) NULL',
                'duration' => 'VARCHAR(100) NULL',
                'price' => 'DECIMAL(12,2) DEFAULT 0',
                'status' => 'VARCHAR(50) DEFAULT "active"',
            ];
            
            foreach ($packages_columns as $col => $def) {
                if (!in_array($col, $existing)) {
                    $wpdb->query("ALTER TABLE $packages_table ADD COLUMN $col $def");
                    $columns_added = true;
                }
            }
            
            // If 'name' exists but 'package_name' doesn't have data, copy from name
            if (in_array('name', $existing) && in_array('package_name', $wpdb->get_col("SHOW COLUMNS FROM $packages_table"))) {
                $wpdb->query("UPDATE $packages_table SET package_name = name WHERE package_name IS NULL OR package_name = ''");
            }
        }
        
        // ============ PACKAGE QUERIES TABLE ============
        $queries_table = defined('ATC_TABLE_PACKAGE_QUERIES') ? ATC_TABLE_PACKAGE_QUERIES : $wpdb->prefix . 'atc_package_queries';
        if ($wpdb->get_var("SHOW TABLES LIKE '$queries_table'") == $queries_table) {
            $existing = $wpdb->get_col("SHOW COLUMNS FROM $queries_table");
            
            $queries_columns = [
                'metadata' => 'LONGTEXT NULL',
                'package_name' => 'VARCHAR(255) NULL',
            ];
            
            foreach ($queries_columns as $col => $def) {
                if (!in_array($col, $existing)) {
                    $wpdb->query("ALTER TABLE $queries_table ADD COLUMN $col $def");
                    $columns_added = true;
                }
            }
        }
        
        if ($columns_added && class_exists('ATC_Logger')) {
            ATC_Logger::log('info', 'DB Health: Auto-fixed missing columns in tables');
        }
        
        // Cache this check for 1 hour
        set_transient('atc_columns_health_check', time(), HOUR_IN_SECONDS);
    }
    
    /**
     * Check if all tables exist
     */
    public static function check_tables() {
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
            ATC_TABLE_PACKAGE_QUERIES,
        ];
        
        $missing = [];
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if ($exists != $table) {
                $missing[] = str_replace($wpdb->prefix, '', $table);
            }
        }
        
        if (!empty($missing)) {
            set_transient('atc_db_health_warning', true, 3600);
            ATC_Logger::log('error', 'Missing tables: ' . implode(', ', $missing));
            return false;
        }
        
        delete_transient('atc_db_health_warning');
        return true;
    }
    
    /**
     * Get table status
     */
    public static function get_table_status() {
        global $wpdb;
        
        $tables = [
            'Bookings' => ATC_TABLE_BOOKINGS,
            'Customers' => ATC_TABLE_CUSTOMERS,
            'Leads' => ATC_TABLE_LEADS,
            'Lead Activities' => ATC_TABLE_LEAD_ACTIVITIES,
            'Services' => ATC_TABLE_SERVICES,
            'Notifications' => ATC_TABLE_NOTIFICATIONS,
            'Payments' => ATC_TABLE_PAYMENTS,
            'Email Templates' => ATC_TABLE_EMAIL_TEMPLATES,
            'Automation Rules' => ATC_TABLE_AUTOMATION_RULES,
            'Admin Recipients' => ATC_TABLE_ADMIN_RECIPIENTS,
            'Search Logs' => ATC_TABLE_SEARCH_LOGS,
            'Custom Packages' => ATC_TABLE_CUSTOM_PACKAGES,
            'Package Queries' => ATC_TABLE_PACKAGE_QUERIES,
        ];
        
        $status = [];
        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            $count = 0;
            
            if ($exists == $table) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            }
            
            $status[$name] = [
                'table' => $table,
                'exists' => ($exists == $table),
                'count' => $count,
            ];
        }
        
        return $status;
    }
    
    /**
     * Admin menu
     */
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Database Health', 'advanced-travel-crm'),
            __('DB Health', 'advanced-travel-crm'),
            'manage_options',
            'atc-db-health',
            [__CLASS__, 'health_page']
        );
    }
    
    /**
     * Health page
     */
    public static function health_page() {
        $status = self::get_table_status();
        $all_exist = true;
        $missing_count = 0;
        
        foreach ($status as $info) {
            if (!$info['exists']) {
                $all_exist = false;
                $missing_count++;
            }
        }
        
        ?>
        <div class="wrap">
            <h1>🏥 Database Health Check</h1>
            
            <?php if (isset($_GET['repaired'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Tables repaired successfully!</strong> All missing tables have been created.</p>
                </div>
            <?php endif; ?>
            
            <?php if (!$all_exist): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong>⚠️ Warning:</strong> <?php echo esc_html($missing_count); ?> table(s) are missing. Click "Repair Missing Tables" to fix this.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ All database tables are healthy!</strong> All required tables exist and are ready to use.</p>
                </div>
            <?php endif; ?>
            
            <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <strong>Database Summary:</strong>
                <span style="margin-left: 20px;">Total Tables: <strong><?php echo count($status); ?></strong></span>
                <span style="margin-left: 20px;">✅ Existing: <strong style="color: #10b981;"><?php echo count($status) - $missing_count; ?></strong></span>
                <?php if ($missing_count > 0): ?>
                    <span style="margin-left: 20px;">❌ Missing: <strong style="color: #ef4444;"><?php echo $missing_count; ?></strong></span>
                <?php endif; ?>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 20%;">Table Name</th>
                        <th style="width: 30%;">Database Table</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 15%;">Records</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($status as $name => $info): ?>
                    <tr>
                        <td><strong><?php echo esc_html($name); ?></strong></td>
                        <td><code style="font-size: 11px;"><?php echo esc_html($info['table']); ?></code></td>
                        <td>
                            <?php if ($info['exists']): ?>
                                <span style="color: #10b981; font-weight: bold;">✅ Exists</span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-weight: bold;">❌ Missing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($info['exists']): ?>
                                <strong><?php echo number_format($info['count']); ?></strong>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($info['exists']): ?>
                                <span style="color: #10b981;">✓ Healthy</span>
                            <?php else: ?>
                                <span style="color: #ef4444;">⚠️ Needs Repair</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px;">
                <p class="submit">
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=atc_repair_tables'), 'atc_repair_tables'); ?>" 
                       class="button button-primary button-large"
                       onclick="return confirm('This will recreate any missing tables. Existing data will NOT be affected. Continue?')">
                        🔧 Repair Missing Tables
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=atc-db-health'); ?>" class="button button-secondary">
                        🔄 Refresh Status
                    </a>
                </p>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
                <h3>📚 About Database Health</h3>
                <p>This page checks the status of all plugin database tables:</p>
                <ul style="margin-left: 20px;">
                    <li><strong>✅ Exists:</strong> Table is present and ready to use</li>
                    <li><strong>❌ Missing:</strong> Table needs to be created</li>
                    <li><strong>Records:</strong> Number of records currently in the table</li>
                </ul>
                <p><strong>What happens when you click "Repair Missing Tables"?</strong></p>
                <ul style="margin-left: 20px;">
                    <li>Only missing tables will be created</li>
                    <li>Existing tables and their data are NOT affected</li>
                    <li>This is safe to run at any time</li>
                </ul>
                <p><em>If you see missing tables, click "Repair Missing Tables" to fix them automatically.</em></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Repair tables
     */
    public static function repair_tables() {
        check_admin_referer('atc_repair_tables');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        ATC_Installer::create_tables();
        
        wp_redirect(add_query_arg('repaired', '1', admin_url('admin.php?page=atc-db-health')));
        exit;
    }
}