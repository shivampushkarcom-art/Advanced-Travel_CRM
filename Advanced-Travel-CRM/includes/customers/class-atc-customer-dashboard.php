<?php
/**
 * ATC Customer Dashboard
 * Advanced customer portal with booking management
 * 
 * Features:
 * - Dashboard overview with stats
 * - Booking history with filters
 * - Booking details modal
 * - Download invoices
 * - Cancellation requests
 * - Support tickets (optional)
 */

if (!defined('ABSPATH')) exit;

class ATC_Customer_Dashboard {
    
    public static function init() {
        add_shortcode('atc_dashboard_advanced', [__CLASS__, 'dashboard_shortcode']);
        add_shortcode('atc_booking_history', [__CLASS__, 'booking_history_shortcode']);
        add_shortcode('atc_booking_details', [__CLASS__, 'booking_details_shortcode']);
        
        // AJAX handlers
        add_action('wp_ajax_atc_get_booking_details', [__CLASS__, 'ajax_get_booking_details']);
        add_action('wp_ajax_atc_download_invoice', [__CLASS__, 'ajax_download_invoice']);
        add_action('wp_ajax_atc_request_cancellation', [__CLASS__, 'ajax_request_cancellation']);
    }
    
    /**
     * Advanced Dashboard Shortcode
     */
    public static function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="atc-notice atc-notice-warning">
                <p>' . __('Please login to view your dashboard.', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        // Check if functions exist before calling
        if (!function_exists('get_current_user_id') || !function_exists('wp_get_current_user')) {
            return '<div class="atc-notice atc-notice-error">
                <p>' . __('User functions not available.', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Get customer stats
        $stats = self::get_customer_stats($user_id);
        
        ob_start();
        ?>
        <div class="atc-advanced-dashboard">
            <!-- Dashboard Header -->
            <div class="atc-dashboard-header-advanced">
                <div class="atc-user-welcome">
                    <div class="atc-user-avatar">
                        <?php echo get_avatar($user_id, 80); ?>
                    </div>
                    <div class="atc-user-info">
                        <h1><?php printf(__('Welcome, %s', 'advanced-travel-crm'), esc_html($user->display_name)); ?></h1>
                        <p class="atc-user-email"><?php echo esc_html($user->user_email); ?></p>
                        <p class="atc-user-tier">
                            <span class="atc-tier-badge atc-tier-<?php echo esc_attr($stats['tier']); ?>">
                                <?php echo esc_html(ucfirst($stats['tier'])); ?> Member
                            </span>
                        </p>
                    </div>
                </div>
                
                <div class="atc-dashboard-quick-actions">
                    <a href="<?php echo home_url(); ?>" class="atc-btn atc-btn-primary">
                        🎯 <?php _e('Book New Trip', 'advanced-travel-crm'); ?>
                    </a>
                    <a href="?tab=profile" class="atc-btn atc-btn-outline">
                        ⚙️ <?php _e('Settings', 'advanced-travel-crm'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="atc-dashboard-stats-advanced">
                <div class="atc-stat-card-advanced">
                    <div class="atc-stat-icon-advanced">📋</div>
                    <div class="atc-stat-details">
                        <span class="atc-stat-label"><?php _e('Total Bookings', 'advanced-travel-crm'); ?></span>
                        <span class="atc-stat-value"><?php echo number_format($stats['total_bookings']); ?></span>
                    </div>
                </div>
                
                <div class="atc-stat-card-advanced">
                    <div class="atc-stat-icon-advanced">✅</div>
                    <div class="atc-stat-details">
                        <span class="atc-stat-label"><?php _e('Completed', 'advanced-travel-crm'); ?></span>
                        <span class="atc-stat-value"><?php echo number_format($stats['completed']); ?></span>
                    </div>
                </div>
                
                <div class="atc-stat-card-advanced">
                    <div class="atc-stat-icon-advanced">⏳</div>
                    <div class="atc-stat-details">
                        <span class="atc-stat-label"><?php _e('Upcoming', 'advanced-travel-crm'); ?></span>
                        <span class="atc-stat-value"><?php echo number_format($stats['upcoming']); ?></span>
                    </div>
                </div>
                
                <div class="atc-stat-card-advanced">
                    <div class="atc-stat-icon-advanced">💰</div>
                    <div class="atc-stat-details">
                        <span class="atc-stat-label"><?php _e('Total Spent', 'advanced-travel-crm'); ?></span>
                        <span class="atc-stat-value"><?php echo get_option('atc_currency_symbol', '₹') . number_format($stats['total_spent'], 0); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Recent Bookings -->
            <div class="atc-dashboard-section">
                <div class="atc-section-header-advanced">
                    <h2>📖 <?php _e('Recent Bookings', 'advanced-travel-crm'); ?></h2>
                    <a href="?tab=all-bookings" class="atc-btn atc-btn-text">
                        <?php _e('View All', 'advanced-travel-crm'); ?> →
                    </a>
                </div>
                
                <?php echo self::render_recent_bookings($user_id, 5); ?>
            </div>
            
            <!-- Upcoming Trips -->
            <?php if ($stats['upcoming'] > 0): ?>
            <div class="atc-dashboard-section">
                <div class="atc-section-header-advanced">
                    <h2>🗓️ <?php _e('Upcoming Trips', 'advanced-travel-crm'); ?></h2>
                </div>
                
                <?php echo self::render_upcoming_trips($user_id); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Booking History Shortcode
     */
    public static function booking_history_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="atc-notice atc-notice-warning">
                <p>' . __('Please login to view your bookings.', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        $atts = shortcode_atts([
            'status' => '',
            'limit' => 20,
        ], $atts);
        
        $user_id = get_current_user_id();
        
        // Get filters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : $atts['status'];
        $service_filter = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
        
        // Build query
        global $wpdb;
        $where = ["user_id = %d"];
        $params = [$user_id];
        
        if ($status_filter) {
            $where[] = "status = %s";
            $params[] = $status_filter;
        }
        
        if ($service_filter) {
            $where[] = "service = %s";
            $params[] = $service_filter;
        }
        
        $sql = "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY created_at DESC LIMIT %d";
        $params[] = intval($atts['limit']);
        
        $bookings = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        ob_start();
        ?>
        <div class="atc-booking-history-wrapper">
            <div class="atc-booking-filters">
                <form method="get" action="">
                    <select name="status" onchange="this.form.submit()">
                        <option value=""><?php _e('All Statuses', 'advanced-travel-crm'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'advanced-travel-crm'); ?></option>
                        <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Confirmed', 'advanced-travel-crm'); ?></option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'advanced-travel-crm'); ?></option>
                        <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('Cancelled', 'advanced-travel-crm'); ?></option>
                    </select>
                    
                    <select name="service" onchange="this.form.submit()">
                        <option value=""><?php _e('All Services', 'advanced-travel-crm'); ?></option>
                        <option value="tours" <?php selected($service_filter, 'tours'); ?>><?php _e('Tours', 'advanced-travel-crm'); ?></option>
                        <option value="hotels" <?php selected($service_filter, 'hotels'); ?>><?php _e('Hotels', 'advanced-travel-crm'); ?></option>
                        <option value="flights" <?php selected($service_filter, 'flights'); ?>><?php _e('Flights', 'advanced-travel-crm'); ?></option>
                        <option value="trains" <?php selected($service_filter, 'trains'); ?>><?php _e('Trains', 'advanced-travel-crm'); ?></option>
                        <option value="safari" <?php selected($service_filter, 'safari'); ?>><?php _e('Safari', 'advanced-travel-crm'); ?></option>
                        <option value="cars" <?php selected($service_filter, 'cars'); ?>><?php _e('Cars', 'advanced-travel-crm'); ?></option>
                    </select>
                    
                    <?php if ($status_filter || $service_filter): ?>
                    <a href="?" class="atc-btn atc-btn-text"><?php _e('Reset', 'advanced-travel-crm'); ?></a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (empty($bookings)): ?>
                <div class="atc-empty-state">
                    <div class="atc-empty-icon">📭</div>
                    <h3><?php _e('No bookings found', 'advanced-travel-crm'); ?></h3>
                    <p><?php _e('Try adjusting your filters or make a new booking!', 'advanced-travel-crm'); ?></p>
                </div>
            <?php else: ?>
                <div class="atc-bookings-grid">
                    <?php foreach ($bookings as $booking): ?>
                        <?php echo self::render_booking_card($booking); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render booking card
     */
    private static function render_booking_card($booking) {
        $service_icons = [
            'tours' => '🏖️',
            'hotels' => '🏨',
            'flights' => '✈️',
            'trains' => '🚂',
            'safari' => '🦁',
            'cars' => '🚗',
            'forex' => '💱',
        ];
        
        $icon = $service_icons[$booking['service']] ?? '📦';
        
        ob_start();
        ?>
        <div class="atc-booking-card" data-booking-id="<?php echo $booking['id']; ?>">
            <div class="atc-booking-card-header">
                <div class="atc-booking-service">
                    <span class="atc-service-icon"><?php echo $icon; ?></span>
                    <span class="atc-service-name"><?php echo esc_html(ucfirst($booking['service'])); ?></span>
                </div>
                <span class="atc-status-badge atc-status-<?php echo esc_attr($booking['status']); ?>">
                    <?php echo esc_html(ucfirst($booking['status'])); ?>
                </span>
            </div>
            
            <div class="atc-booking-card-body">
                <h3 class="atc-booking-id" style="margin: 0 0 15px 0; color: #0A1F44; font-size: 18px; font-weight: 700;">
                    <?php echo esc_html($booking['booking_id'] ?: '#' . $booking['id']); ?>
                </h3>
                
                <div class="atc-booking-meta">
                    <div class="atc-meta-item">
                        <span class="atc-meta-icon">📅</span>
                        <span><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></span>
                    </div>
                    
                    <?php if (!empty($booking['destination'])): ?>
                    <div class="atc-meta-item">
                        <span class="atc-meta-icon">📍</span>
                        <span><?php echo esc_html($booking['destination']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="atc-meta-item">
                        <span class="atc-meta-icon">💰</span>
                        <strong><?php echo esc_html($booking['currency'] . ' ' . number_format($booking['price_total'], 2)); ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="atc-booking-card-footer">
                <button class="atc-btn atc-btn-small atc-btn-outline atc-view-booking-details" 
                        data-booking-id="<?php echo $booking['id']; ?>">
                    <?php _e('View Details', 'advanced-travel-crm'); ?>
                </button>
                
                <?php if ($booking['payment_status'] === 'paid'): ?>
                <button class="atc-btn atc-btn-small atc-btn-text atc-download-invoice" 
                        data-booking-id="<?php echo $booking['id']; ?>">
                    📄 <?php _e('Invoice', 'advanced-travel-crm'); ?>
                </button>
                <?php endif; ?>
                
                <?php if ($booking['status'] === 'pending'): ?>
                <button class="atc-btn atc-btn-small atc-btn-danger atc-request-cancellation" 
                        data-booking-id="<?php echo $booking['id']; ?>">
                    <?php _e('Cancel', 'advanced-travel-crm'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get customer stats
     */
    private static function get_customer_stats($user_id) {
        global $wpdb;
        
        $stats = [
            'total_bookings' => 0,
            'completed' => 0,
            'upcoming' => 0,
            'total_spent' => 0,
            'tier' => 'new',
        ];
        
        // Total bookings
        $stats['total_bookings'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " WHERE user_id = %d",
            $user_id
        ));
        
        // Completed
        $stats['completed'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE user_id = %d AND status = 'completed'",
            $user_id
        ));
        
        // Upcoming (confirmed bookings with future dates)
        $stats['upcoming'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE user_id = %d AND status IN ('confirmed', 'pending') 
            AND (travel_date IS NULL OR travel_date >= CURDATE())",
            $user_id
        ));
        
        // Total spent - handle NULL values and ensure proper calculation
        $stats['total_spent'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(CAST(price_total AS DECIMAL(12,2))), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE user_id = %d AND (payment_status = 'paid' OR payment_status = 'completed')",
            $user_id
        ));
        
        // Fallback: if still 0, try without payment_status filter
        if (empty($stats['total_spent']) || $stats['total_spent'] == 0) {
            $stats['total_spent'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(CAST(price_total AS DECIMAL(12,2))), 0) FROM " . ATC_TABLE_BOOKINGS . " 
                WHERE user_id = %d",
                $user_id
            ));
        }
        
        $stats['total_spent'] = floatval($stats['total_spent']) ?: 0;
        
        // Determine tier
        if ($stats['total_bookings'] >= 10) {
            $stats['tier'] = 'vip';
        } elseif ($stats['total_bookings'] >= 5) {
            $stats['tier'] = 'loyal';
        } elseif ($stats['total_bookings'] >= 1) {
            $stats['tier'] = 'regular';
        }
        
        return $stats;
    }
    
    /**
     * Render recent bookings
     */
    private static function render_recent_bookings($user_id, $limit = 5) {
        global $wpdb;
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE user_id = %d 
            ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
        
        if (empty($bookings)) {
            return '<div class="atc-empty-state-small">
                <p>' . __('No bookings yet', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        ob_start();
        ?>
        <div class="atc-bookings-list-compact">
            <?php foreach ($bookings as $booking): ?>
            <div class="atc-booking-item-compact">
                <div class="atc-booking-compact-left">
                    <span class="atc-booking-compact-icon"><?php echo self::get_service_icon($booking['service']); ?></span>
                    <div class="atc-booking-compact-info">
                        <strong style="color: #0A1F44; font-size: 15px;"><?php echo esc_html($booking['booking_id'] ?: '#' . $booking['id']); ?></strong>
                        <span class="atc-booking-compact-service"><?php echo esc_html(ucfirst($booking['service'])); ?></span>
                    </div>
                </div>
                
                <div class="atc-booking-compact-right">
                    <span class="atc-status-badge atc-status-<?php echo esc_attr($booking['status']); ?>">
                        <?php echo esc_html(ucfirst($booking['status'])); ?>
                    </span>
                    <button class="atc-btn atc-btn-icon atc-view-booking-details" 
                            data-booking-id="<?php echo $booking['id']; ?>" 
                            title="<?php _e('View Details', 'advanced-travel-crm'); ?>">
                        👁️
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render upcoming trips
     */
    private static function render_upcoming_trips($user_id) {
        global $wpdb;
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE user_id = %d 
            AND status IN ('confirmed', 'pending')
            AND (travel_date IS NULL OR travel_date >= CURDATE())
            ORDER BY travel_date ASC LIMIT 3",
            $user_id
        ), ARRAY_A);
        
        if (empty($bookings)) {
            return '<p>' . __('No upcoming trips', 'advanced-travel-crm') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="atc-upcoming-trips-grid">
            <?php foreach ($bookings as $booking): ?>
            <div class="atc-upcoming-trip-card">
                <div class="atc-trip-date-badge">
                    <?php if ($booking['travel_date']): ?>
                        <span class="atc-trip-month"><?php echo date('M', strtotime($booking['travel_date'])); ?></span>
                        <span class="atc-trip-day"><?php echo date('d', strtotime($booking['travel_date'])); ?></span>
                    <?php else: ?>
                        <span class="atc-trip-month">TBD</span>
                    <?php endif; ?>
                </div>
                
                <div class="atc-trip-details">
                    <h4 style="margin: 0 0 5px 0; color: #0A1F44; font-size: 16px; font-weight: 700;"><?php echo esc_html($booking['booking_id'] ?: '#' . $booking['id']); ?></h4>
                    <p><?php echo esc_html(ucfirst($booking['service'])); ?></p>
                    <?php if ($booking['destination']): ?>
                    <p class="atc-trip-destination">📍 <?php echo esc_html($booking['destination']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get service icon
     */
    private static function get_service_icon($service) {
        $icons = [
            'tours' => '🏖️',
            'hotels' => '🏨',
            'flights' => '✈️',
            'trains' => '🚂',
            'safari' => '🦁',
            'cars' => '🚗',
            'forex' => '💱',
        ];
        
        return $icons[$service] ?? '📦';
    }
    
    /**
     * ========================================
     * AJAX HANDLERS
     * ========================================
     */
    
    /**
     * AJAX: Get booking details
     */
    public static function ajax_get_booking_details() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please login', 'advanced-travel-crm')]);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => __('Invalid booking', 'advanced-travel-crm')]);
        }
        
        // Verify ownership
        if (!ATC_Auth::can_access_booking($booking_id)) {
            wp_send_json_error(['message' => __('Access denied', 'advanced-travel-crm')]);
        }
        
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            wp_send_json_error(['message' => __('Booking not found', 'advanced-travel-crm')]);
        }
        
        // Parse form data
        $booking['form_data_parsed'] = maybe_unserialize($booking['form_data']);
        
        wp_send_json_success($booking);
    }
    
    /**
     * AJAX: Download invoice
     */
    public static function ajax_download_invoice() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please login', 'advanced-travel-crm')]);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!ATC_Auth::can_access_booking($booking_id)) {
            wp_send_json_error(['message' => __('Access denied', 'advanced-travel-crm')]);
        }
        
        // Generate invoice URL
        $invoice_url = add_query_arg([
            'atc_action' => 'download_invoice',
            'booking_id' => $booking_id,
            'nonce' => wp_create_nonce('atc_invoice_' . $booking_id),
        ], home_url());
        
        wp_send_json_success(['invoice_url' => $invoice_url]);
    }
    
    /**
     * AJAX: Request cancellation
     */
    public static function ajax_request_cancellation() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please login', 'advanced-travel-crm')]);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!ATC_Auth::can_access_booking($booking_id)) {
            wp_send_json_error(['message' => __('Access denied', 'advanced-travel-crm')]);
        }
        
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if ($booking['status'] !== 'pending') {
            wp_send_json_error(['message' => __('Only pending bookings can be cancelled', 'advanced-travel-crm')]);
        }
        
        // Update booking
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            [
                'status' => 'cancelled',
                'notes' => $booking['notes'] . "\n\nCancellation Reason: " . $reason,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $booking_id]
        );
        
        // Trigger notification
        do_action('atc_booking_cancelled', $booking_id, $booking);
        
        wp_send_json_success(['message' => __('Booking cancelled successfully', 'advanced-travel-crm')]);
    }
}