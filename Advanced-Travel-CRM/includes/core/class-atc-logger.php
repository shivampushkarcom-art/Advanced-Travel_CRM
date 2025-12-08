<?php
/**
 * ATC Logger - Advanced Error Logging System
 * Logs errors, bookings, payments, and leads to separate files
 */

if (!defined('ABSPATH')) exit;

class ATC_Logger {
    
    private static $log_dir = null;
    private static $initialized = false;
    
    /**
     * Initialize logger
     */
    public static function init() {
        // Prevent double initialization
        if (self::$initialized) {
            return;
        }
        
        self::$log_dir = WP_CONTENT_DIR . '/atc-logs/';
        
        if (!file_exists(self::$log_dir)) {
            wp_mkdir_p(self::$log_dir);
            // Protect directory
            @file_put_contents(self::$log_dir . '.htaccess', 'Deny from all');
        }
        
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 20); // ✅ FIXED: Register after parent menu
        self::$initialized = true;
    }
    
    /**
     * Log message
     */
    public static function log($type = 'info', $message = '', $context = []) {
        // Always log plugin-specific events (not dependent on WP_DEBUG)
        // Only skip if explicitly disabled via option
        if (get_option('atc_logging_disabled', 0)) {
            return;
        }
        
        // Initialize log directory if needed
        if (self::$log_dir === null) {
            self::$log_dir = WP_CONTENT_DIR . '/atc-logs/';
            if (!file_exists(self::$log_dir)) {
                wp_mkdir_p(self::$log_dir);
                // Protect directory
                file_put_contents(self::$log_dir . '.htaccess', 'Deny from all');
            }
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_id = get_current_user_id();
        
        $log_entry = sprintf(
            "[%s] [%s] [IP:%s] [User:%d] %s %s\n",
            $timestamp,
            strtoupper($type),
            $ip,
            $user_id,
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        // Determine log file
        $filename = self::$log_dir . date('Y-m-d') . '-' . $type . '.log';
        
        // Write to file
        @file_put_contents($filename, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log critical errors to WordPress debug.log if enabled
        if ($type === 'error' && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('ATC: ' . $message);
        }
    }
    
    /**
     * Get recent logs
     */
    public static function get_logs($type = 'info', $limit = 200, $date = null) {
        if (self::$log_dir === null) {
            self::$log_dir = WP_CONTENT_DIR . '/atc-logs/';
        }
        
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $filename = self::$log_dir . $date . '-' . $type . '.log';
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $lines = @file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }
        
        return array_slice(array_reverse($lines), 0, $limit);
    }
    
    /**
     * Get available log files
     */
    public static function get_available_log_files() {
        if (self::$log_dir === null) {
            self::$log_dir = WP_CONTENT_DIR . '/atc-logs/';
        }
        
        if (!file_exists(self::$log_dir)) {
            return [];
        }
        
        $files = glob(self::$log_dir . '*.log');
        $log_files = [];
        
        foreach ($files as $file) {
            $basename = basename($file);
            // Parse: YYYY-MM-DD-type.log
            if (preg_match('/(\d{4}-\d{2}-\d{2})-(.+)\.log$/', $basename, $matches)) {
                $date = $matches[1];
                $type = $matches[2];
                if (!isset($log_files[$date])) {
                    $log_files[$date] = [];
                }
                $log_files[$date][$type] = $file;
            }
        }
        
        // Sort by date descending
        krsort($log_files);
        
        return $log_files;
    }
    
    /**
     * Get log file size
     */
    public static function get_log_file_size($filename) {
        if (!file_exists($filename)) {
            return 0;
        }
        
        $size = filesize($filename);
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / 1048576, 2) . ' MB';
        }
    }
    
    /**
     * Clear old logs (older than 30 days)
     */
    public static function clear_old_logs() {
        if (!file_exists(self::$log_dir)) {
            return;
        }
        
        $files = glob(self::$log_dir . '*.log');
        $threshold = time() - (30 * DAY_IN_SECONDS);
        
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
            }
        }
    }
    
    /**
     * Admin menu
     */
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('System Logs', 'advanced-travel-crm'),
            __('Logs', 'advanced-travel-crm'),
            'manage_options',
            'atc-logs',
            [__CLASS__, 'logs_page']
        );
    }
    
    /**
     * Logs page
     */
    public static function logs_page() {
        $type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : 'info';
        $date = isset($_GET['log_date']) ? sanitize_text_field($_GET['log_date']) : date('Y-m-d');
        $logs = self::get_logs($type, 500, $date);
        $available_files = self::get_available_log_files();
        
        // Get available dates
        $available_dates = array_keys($available_files);
        $available_types = ['info', 'error', 'booking', 'payment', 'lead', 'notification'];
        
        ?>
        <div class="wrap">
            <h1>📋 System Logs</h1>
            
            <div class="atc-log-controls" style="margin: 20px 0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <div>
                    <label><strong>Log Type:</strong></label>
                    <select name="log_type" id="log_type" onchange="window.location.href='?page=atc-logs&log_type='+this.value+'&log_date=<?php echo esc_attr($date); ?>'">
                        <?php foreach ($available_types as $log_type): ?>
                            <option value="<?php echo esc_attr($log_type); ?>" <?php selected($type, $log_type); ?>>
                                <?php echo esc_html(ucfirst($log_type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label><strong>Date:</strong></label>
                    <select name="log_date" id="log_date" onchange="window.location.href='?page=atc-logs&log_type=<?php echo esc_attr($type); ?>&log_date='+this.value">
                        <?php 
                        // Show last 30 days
                        for ($i = 0; $i < 30; $i++):
                            $check_date = date('Y-m-d', strtotime("-{$i} days"));
                            $has_logs = in_array($check_date, $available_dates);
                        ?>
                            <option value="<?php echo esc_attr($check_date); ?>" <?php selected($date, $check_date); ?>>
                                <?php echo esc_html($check_date); ?> <?php echo $has_logs ? '✓' : ''; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>
                    <a href="?page=atc-logs&log_type=<?php echo esc_attr($type); ?>&log_date=<?php echo esc_attr(date('Y-m-d')); ?>" class="button">
                        Today
                    </a>
                </div>
            </div>
            
            <div class="atc-log-stats" style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <strong>Log Statistics:</strong>
                <?php
                $log_file = self::$log_dir . $date . '-' . $type . '.log';
                if (file_exists($log_file)):
                    $file_size = self::get_log_file_size($log_file);
                    $line_count = count($logs);
                ?>
                    <span style="margin-left: 20px;">📄 File Size: <strong><?php echo esc_html($file_size); ?></strong></span>
                    <span style="margin-left: 20px;">📝 Log Entries: <strong><?php echo number_format($line_count); ?></strong></span>
                <?php else: ?>
                    <span style="margin-left: 20px; color: #d63638;">⚠️ No log file found for this date/type</span>
                <?php endif; ?>
            </div>
            
            <?php if (empty($logs)): ?>
                <div class="notice notice-info">
                    <p><strong>No logs found</strong> for <?php echo esc_html($type); ?> logs on <?php echo esc_html($date); ?>.</p>
                    <p>Logs are created automatically when plugin events occur. Try selecting a different date or log type.</p>
                </div>
            <?php else: ?>
                <div class="atc-logs-container">
                    <div style="background: #1d2327; color: #f0f0f1; padding: 15px; border-radius: 4px 4px 0 0; display: flex; justify-content: space-between; align-items: center;">
                        <strong>Log Entries (Most Recent First)</strong>
                        <button onclick="document.getElementById('log-content').select(); document.execCommand('copy'); alert('Logs copied to clipboard!');" class="button button-small">
                            📋 Copy All
                        </button>
                    </div>
                    <pre id="log-content" style="background:#1d2327; color:#f0f0f1; padding:20px; border-radius:0 0 4px 4px; max-height:600px; overflow-y:auto; margin:0; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.6;"><?php
                        echo esc_html(implode("\n", $logs));
                    ?></pre>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
                <h3>📚 About System Logs</h3>
                <p>This page displays plugin activity logs including:</p>
                <ul style="margin-left: 20px;">
                    <li><strong>Info:</strong> General plugin events and operations</li>
                    <li><strong>Error:</strong> Errors and warnings</li>
                    <li><strong>Booking:</strong> Booking-related events</li>
                    <li><strong>Payment:</strong> Payment processing events</li>
                    <li><strong>Lead:</strong> Lead capture and management events</li>
                    <li><strong>Notification:</strong> Email/WhatsApp notification events</li>
                </ul>
                <p><strong>Log Location:</strong> <code><?php echo esc_html(self::$log_dir); ?></code></p>
                <p><em>Logs are automatically cleaned after 30 days.</em></p>
            </div>
        </div>
        <?php
    }
}