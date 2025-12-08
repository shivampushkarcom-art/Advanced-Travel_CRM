<?php
/**
 * ATC Enhanced Dashboard & Analytics
 * Modern, synced, real-time dashboard with charts and insights
 */

if (!defined('ABSPATH')) exit;

class ATC_Dashboard_Enhanced {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 11);
        add_action('wp_ajax_atc_get_dashboard_data', [__CLASS__, 'ajax_get_dashboard_data']);
        add_action('wp_ajax_atc_get_analytics_data', [__CLASS__, 'ajax_get_analytics_data']);
    }
    
    public static function admin_menu() {
        // Remove old analytics menu if it exists (to prevent conflicts)
        remove_submenu_page('atc-dashboard', 'atc-analytics');
        
        // Replace default dashboard
        remove_submenu_page('atc-dashboard', 'atc-dashboard');
        add_submenu_page(
            'atc-dashboard',
            __('Dashboard', 'advanced-travel-crm'),
            __('Dashboard', 'advanced-travel-crm'),
            'manage_options',
            'atc-dashboard',
            [__CLASS__, 'dashboard_page']
        );
        
        // Enhanced analytics (replaces old analytics)
        add_submenu_page(
            'atc-dashboard',
            __('Analytics', 'advanced-travel-crm'),
            __('Analytics', 'advanced-travel-crm'),
            'manage_options',
            'atc-analytics',
            [__CLASS__, 'analytics_page']
        );
    }
    
    /**
     * Get comprehensive dashboard stats
     */
    public static function get_dashboard_stats() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this_week_start = date('Y-m-d', strtotime('monday this week'));
        $this_month_start = date('Y-m-01');
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));
        
        // Today's stats
        $today_bookings = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . ATC_TABLE_BOOKINGS . ' WHERE DATE(created_at) = %s',
            $today
        ));
        
        // Revenue: Include all bookings (paid, completed, or any status with price > 0)
        $today_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(price_total), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) = %s 
            AND price_total > 0 
            AND (payment_status = 'paid' OR status IN ('completed', 'confirmed') OR payment_method IN ('cash', 'manual', 'offline'))",
            $today
        ));
        
        // Yesterday for comparison
        $yesterday_bookings = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . ATC_TABLE_BOOKINGS . ' WHERE DATE(created_at) = %s',
            $yesterday
        ));
        
        $yesterday_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(price_total), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) = %s 
            AND price_total > 0 
            AND (payment_status = 'paid' OR status IN ('completed', 'confirmed') OR payment_method IN ('cash', 'manual', 'offline'))",
            $yesterday
        ));
        
        // This week
        $week_bookings = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . ATC_TABLE_BOOKINGS . ' WHERE DATE(created_at) >= %s',
            $this_week_start
        ));
        
        $week_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(price_total), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) >= %s 
            AND price_total > 0 
            AND (payment_status = 'paid' OR status IN ('completed', 'confirmed') OR payment_method IN ('cash', 'manual', 'offline'))",
            $this_week_start
        ));
        
        // This month
        $month_bookings = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . ATC_TABLE_BOOKINGS . ' WHERE DATE(created_at) >= %s',
            $this_month_start
        ));
        
        $month_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(price_total), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) >= %s 
            AND price_total > 0 
            AND (payment_status = 'paid' OR status IN ('completed', 'confirmed') OR payment_method IN ('cash', 'manual', 'offline'))",
            $this_month_start
        ));
        
        // Last month for comparison
        $last_month_bookings = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . ATC_TABLE_BOOKINGS . ' 
            WHERE DATE(created_at) BETWEEN %s AND %s',
            $last_month_start, $last_month_end
        ));
        
        $last_month_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(price_total), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) BETWEEN %s AND %s 
            AND price_total > 0 
            AND (payment_status = 'paid' OR status IN ('completed', 'confirmed') OR payment_method IN ('cash', 'manual', 'offline'))",
            $last_month_start, $last_month_end
        ));
        
        // Pending bookings
        $pending_bookings = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " WHERE status = 'pending'"
        );
        
        // Hot leads
        $hot_leads = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " WHERE temperature = 'hot' AND status = 'new'"
        );
        
        // Total leads (today)
        $total_leads = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " WHERE DATE(created_at) = %s",
            $today
        ));
        
        // Conversion rate (bookings / leads ratio)
        $total_leads_all = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS
        );
        
        $total_bookings_all = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " WHERE price_total > 0 AND status != 'cancelled'"
        );
        
        // Calculate conversion rate: bookings / (leads + bookings) or just bookings/leads if leads exist
        if ($total_leads_all > 0) {
            $conversion_rate = round(($total_bookings_all / $total_leads_all) * 100, 1);
        } elseif ($total_bookings_all > 0) {
            // If no leads but have bookings, show 100%
            $conversion_rate = 100.0;
        } else {
            $conversion_rate = 0.0;
        }
        
        // Also get converted leads count for reference
        $converted_leads = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " WHERE status = 'converted'"
        );
        
        // Top services
        $top_services = $wpdb->get_results($wpdb->prepare(
            "SELECT service, COUNT(*) as count, COALESCE(SUM(price_total), 0) as revenue 
            FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) >= %s
            GROUP BY service ORDER BY count DESC LIMIT 5",
            $this_month_start
        ), ARRAY_A);
        
        // Booking status breakdown
        $status_breakdown = $wpdb->get_results(
            "SELECT status, COUNT(*) as count 
            FROM " . ATC_TABLE_BOOKINGS . " 
            GROUP BY status",
            ARRAY_A
        );
        
        // Revenue by service (include all completed bookings)
        $revenue_by_service = $wpdb->get_results($wpdb->prepare(
            "SELECT service, COALESCE(SUM(price_total), 0) as revenue 
            FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE price_total > 0 
            AND (payment_status = 'paid' OR status IN ('completed', 'confirmed') OR payment_method IN ('cash', 'manual', 'offline'))
            AND DATE(created_at) >= %s
            GROUP BY service ORDER BY revenue DESC",
            $this_month_start
        ), ARRAY_A);
        
        // Lifetime revenue (all bookings with price > 0, exclude only cancelled)
        // This includes all bookings regardless of payment status or method
        $lifetime_revenue = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(price_total), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE price_total > 0 
            AND status != 'cancelled'"
        );
        
        // Recent bookings (last 7 days)
        $recent_bookings = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY created_at DESC LIMIT 10",
            ARRAY_A
        );
        
        // Hot leads
        $hot_leads_list = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_LEADS . " 
            WHERE temperature = 'hot' AND status = 'new' 
            ORDER BY score DESC, created_at DESC LIMIT 10",
            ARRAY_A
        );
        
        // Calculate growth percentages
        $booking_growth = $yesterday_bookings > 0 
            ? round((($today_bookings - $yesterday_bookings) / $yesterday_bookings) * 100, 1)
            : ($today_bookings > 0 ? 100 : 0);
        
        $revenue_growth = $yesterday_revenue > 0
            ? round((($today_revenue - $yesterday_revenue) / $yesterday_revenue) * 100, 1)
            : ($today_revenue > 0 ? 100 : 0);
        
        $month_booking_growth = $last_month_bookings > 0
            ? round((($month_bookings - $last_month_bookings) / $last_month_bookings) * 100, 1)
            : ($month_bookings > 0 ? 100 : 0);
        
        $month_revenue_growth = $last_month_revenue > 0
            ? round((($month_revenue - $last_month_revenue) / $last_month_revenue) * 100, 1)
            : ($month_revenue > 0 ? 100 : 0);
        
        return [
            // Today
            'today_bookings' => $today_bookings,
            'today_revenue' => $today_revenue,
            'booking_growth' => $booking_growth,
            'revenue_growth' => $revenue_growth,
            
            // Week
            'week_bookings' => $week_bookings,
            'week_revenue' => $week_revenue,
            
            // Month
            'month_bookings' => $month_bookings,
            'month_revenue' => $month_revenue,
            'month_booking_growth' => $month_booking_growth,
            'month_revenue_growth' => $month_revenue_growth,
            
            // Status
            'pending_bookings' => $pending_bookings,
            'hot_leads' => $hot_leads,
            'total_leads' => $total_leads,
            'conversion_rate' => $conversion_rate,
            
            // Breakdowns
            'top_services' => $top_services,
            'status_breakdown' => $status_breakdown,
            'revenue_by_service' => $revenue_by_service,
            
            // Lists
            'recent_bookings' => $recent_bookings,
            'hot_leads_list' => $hot_leads_list,
            
            // Lifetime
            'lifetime_revenue' => $lifetime_revenue,
        ];
    }
    
    /**
     * Dashboard page
     */
    public static function dashboard_page() {
        $stats = self::get_dashboard_stats();
        $currency_symbol = get_option('atc_currency_symbol', '₹');
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);
        wp_enqueue_script('atc-dashboard-enhanced', ATC_ASSETS_URL . 'js/atc-dashboard-enhanced.js', ['jquery', 'chart-js'], ATC_VERSION, true);
        wp_enqueue_style('atc-dashboard-enhanced', ATC_ASSETS_URL . 'css/atc-dashboard-enhanced.css', [], ATC_VERSION);
        
        wp_localize_script('atc-dashboard-enhanced', 'atcDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('atc_dashboard_nonce'),
            'stats' => $stats,
            'currency' => $currency_symbol,
        ]);
        
        ?>
        <div class="wrap atc-dashboard-enhanced">
            <div class="atc-dashboard-header">
                <div>
                    <h1>📊 Travel CRM Dashboard</h1>
                    <p class="description">Real-time insights and analytics for your travel business</p>
                </div>
                <div class="atc-dashboard-actions">
                    <button class="button button-secondary" id="atc-refresh-dashboard">
                        🔄 Refresh
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=atc-analytics'); ?>" class="button button-primary">
                        📈 View Analytics
                    </a>
                </div>
            </div>
            
            <!-- Key Metrics -->
            <div class="atc-metrics-grid">
                <div class="atc-metric-card atc-metric-primary">
                    <div class="atc-metric-icon">📊</div>
                    <div class="atc-metric-content">
                        <div class="atc-metric-label">Today's Bookings</div>
                        <div class="atc-metric-value"><?php echo number_format($stats['today_bookings']); ?></div>
                        <div class="atc-metric-change <?php echo $stats['booking_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['booking_growth'] >= 0 ? '↑' : '↓'; ?> 
                            <?php echo abs($stats['booking_growth']); ?>% vs yesterday
                        </div>
                    </div>
                </div>
                
                <div class="atc-metric-card atc-metric-success">
                    <div class="atc-metric-icon">💰</div>
                    <div class="atc-metric-content">
                        <div class="atc-metric-label">Today's Revenue</div>
                        <div class="atc-metric-value"><?php echo $currency_symbol . number_format($stats['today_revenue'], 2); ?></div>
                        <div class="atc-metric-change <?php echo $stats['revenue_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['revenue_growth'] >= 0 ? '↑' : '↓'; ?> 
                            <?php echo abs($stats['revenue_growth']); ?>% vs yesterday
                        </div>
                    </div>
                </div>
                
                <div class="atc-metric-card atc-metric-warning">
                    <div class="atc-metric-icon">⏳</div>
                    <div class="atc-metric-content">
                        <div class="atc-metric-label">Pending Bookings</div>
                        <div class="atc-metric-value"><?php echo number_format($stats['pending_bookings']); ?></div>
                        <div class="atc-metric-change">
                            Needs attention
                        </div>
                    </div>
                </div>
                
                <div class="atc-metric-card atc-metric-danger">
                    <div class="atc-metric-icon">🔥</div>
                    <div class="atc-metric-content">
                        <div class="atc-metric-label">Hot Leads</div>
                        <div class="atc-metric-value"><?php echo number_format($stats['hot_leads']); ?></div>
                        <div class="atc-metric-change">
                            High priority
                        </div>
                    </div>
                </div>
                
                <div class="atc-metric-card atc-metric-info">
                    <div class="atc-metric-icon">📈</div>
                    <div class="atc-metric-content">
                        <div class="atc-metric-label">This Month Revenue</div>
                        <div class="atc-metric-value"><?php echo $currency_symbol . number_format($stats['month_revenue'], 2); ?></div>
                        <div class="atc-metric-change <?php echo $stats['month_revenue_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['month_revenue_growth'] >= 0 ? '↑' : '↓'; ?> 
                            <?php echo abs($stats['month_revenue_growth']); ?>% vs last month
                        </div>
                    </div>
                </div>
                
                <div class="atc-metric-card atc-metric-purple">
                    <div class="atc-metric-icon">🎯</div>
                    <div class="atc-metric-content">
                        <div class="atc-metric-label">Conversion Rate</div>
                        <div class="atc-metric-value"><?php echo $stats['conversion_rate']; ?>%</div>
                        <div class="atc-metric-change">
                            Leads to bookings
                        </div>
                    </div>
                </div>
                
                <div class="atc-metric-card atc-metric-gold">
                    <div class="atc-metric-icon">💎</div>
                    <div class="atc-metric-content">
                        <div class="atc-metric-label">Lifetime Revenue</div>
                        <div class="atc-metric-value"><?php echo $currency_symbol . number_format($stats['lifetime_revenue'], 2); ?></div>
                        <div class="atc-metric-change">
                            All completed bookings
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="atc-charts-grid">
                <div class="atc-chart-card">
                    <div class="atc-chart-header">
                        <h3>📊 Bookings Trend (Last 7 Days)</h3>
                    </div>
                    <div class="atc-chart-body">
                        <canvas id="atc-bookings-chart"></canvas>
                    </div>
                </div>
                
                <div class="atc-chart-card">
                    <div class="atc-chart-header">
                        <h3>💰 Revenue by Service</h3>
                    </div>
                    <div class="atc-chart-body">
                        <canvas id="atc-revenue-chart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Data Tables Row -->
            <div class="atc-tables-grid">
                <div class="atc-table-card">
                    <div class="atc-table-header">
                        <h3>📋 Recent Bookings</h3>
                        <a href="<?php echo admin_url('admin.php?page=atc-bookings'); ?>" class="button button-small">
                            View All →
                        </a>
                    </div>
                    <div class="atc-table-body">
                        <?php if (empty($stats['recent_bookings'])): ?>
                            <p class="atc-empty-state">No recent bookings</p>
                        <?php else: ?>
                            <table class="atc-data-table">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($stats['recent_bookings'], 0, 5) as $booking): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($booking['booking_id']); ?></strong></td>
                                            <td>
                                                <?php echo esc_html($booking['customer_name']); ?><br>
                                                <small><?php echo esc_html($booking['customer_email']); ?></small>
                                            </td>
                                            <td><?php echo esc_html(ucfirst($booking['service'])); ?></td>
                                            <td><strong><?php echo esc_html($currency_symbol . number_format((float) $booking['price_total'], 2)); ?></strong></td>
                                            <td>
                                                <span class="atc-status-badge atc-status-<?php echo esc_attr($booking['status']); ?>">
                                                    <?php echo esc_html(ucfirst($booking['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="atc-table-card">
                    <div class="atc-table-header">
                        <h3>🔥 Hot Leads</h3>
                        <a href="<?php echo admin_url('admin.php?page=atc-leads'); ?>" class="button button-small">
                            View All →
                        </a>
                    </div>
                    <div class="atc-table-body">
                        <?php if (empty($stats['hot_leads_list'])): ?>
                            <p class="atc-empty-state">No hot leads at the moment</p>
                        <?php else: ?>
                            <table class="atc-data-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Destination</th>
                                        <th>Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($stats['hot_leads_list'], 0, 5) as $lead): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($lead['name'] ?: 'Anonymous'); ?></strong></td>
                                            <td>
                                                <?php if (!empty($lead['phone'])): ?>
                                                    📱 <?php echo esc_html($lead['phone']); ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($lead['email'])): ?>
                                                    📧 <?php echo esc_html($lead['email']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($lead['destination'] ?: 'N/A'); ?></td>
                                            <td>
                                                <span class="atc-score-badge atc-score-hot">
                                                    <?php echo esc_html((int) $lead['score']); ?>/100
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                if (class_exists('ATC_Notification_Manager')) {
                                                    echo ATC_Notification_Manager::get_whatsapp_button($lead['id'], 'lead', 'customer');
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Analytics page
     */
    public static function analytics_page() {
        $stats = self::get_dashboard_stats();
        $currency_symbol = get_option('atc_currency_symbol', '₹');
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);
        wp_enqueue_script('atc-dashboard-enhanced', ATC_ASSETS_URL . 'js/atc-dashboard-enhanced.js', ['jquery', 'chart-js'], ATC_VERSION, true);
        wp_enqueue_style('atc-dashboard-enhanced', ATC_ASSETS_URL . 'css/atc-dashboard-enhanced.css', [], ATC_VERSION);
        
        ?>
        <div class="wrap atc-analytics-enhanced">
            <div class="atc-analytics-header">
                <div>
                    <h1>📈 Advanced Analytics</h1>
                    <p class="description">Comprehensive insights and performance metrics</p>
                </div>
                <div class="atc-analytics-filters">
                    <select id="atc-analytics-period" class="atc-filter-select">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="365">Last Year</option>
                    </select>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="atc-metrics-grid">
                <div class="atc-metric-card">
                    <div class="atc-metric-label">Total Bookings</div>
                    <div class="atc-metric-value"><?php echo number_format($stats['month_bookings']); ?></div>
                </div>
                <div class="atc-metric-card">
                    <div class="atc-metric-label">Total Revenue</div>
                    <div class="atc-metric-value"><?php echo $currency_symbol . number_format($stats['month_revenue'], 2); ?></div>
                </div>
                <div class="atc-metric-card">
                    <div class="atc-metric-label">Total Leads</div>
                    <div class="atc-metric-value"><?php echo number_format($stats['total_leads']); ?></div>
                </div>
                <div class="atc-metric-card">
                    <div class="atc-metric-label">Conversion Rate</div>
                    <div class="atc-metric-value"><?php echo $stats['conversion_rate']; ?>%</div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="atc-charts-grid">
                <div class="atc-chart-card">
                    <div class="atc-chart-header">
                        <h3>📊 Revenue Trend</h3>
                    </div>
                    <div class="atc-chart-body">
                        <canvas id="atc-revenue-trend-chart"></canvas>
                    </div>
                </div>
                
                <div class="atc-chart-card">
                    <div class="atc-chart-header">
                        <h3>🎯 Booking Status Distribution</h3>
                    </div>
                    <div class="atc-chart-body">
                        <canvas id="atc-status-chart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top Services -->
            <div class="atc-table-card">
                <div class="atc-table-header">
                    <h3>🏆 Top Performing Services</h3>
                </div>
                <div class="atc-table-body">
                    <table class="atc-data-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                                <th>Avg. Booking Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['top_services'] as $service): ?>
                                <tr>
                                    <td><strong><?php echo esc_html(ucfirst($service['service'])); ?></strong></td>
                                    <td><?php echo esc_html($service['count']); ?></td>
                                    <td><strong><?php echo esc_html($currency_symbol . number_format((float) $service['revenue'], 2)); ?></strong></td>
                                    <td><?php echo esc_html($currency_symbol . number_format((float) $service['revenue'] / max($service['count'], 1), 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Get dashboard data
     */
    public static function ajax_get_dashboard_data() {
        check_ajax_referer('atc_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $stats = self::get_dashboard_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get analytics data
     */
    public static function ajax_get_analytics_data() {
        check_ajax_referer('atc_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $period = intval($_POST['period'] ?? 30);
        $date_from = date('Y-m-d', strtotime("-{$period} days"));
        $date_to = current_time('Y-m-d');
        
        global $wpdb;
        
        // Get daily data for chart
        $daily_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, 
                    COUNT(*) as bookings, 
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN price_total ELSE 0 END), 0) as revenue
             FROM " . ATC_TABLE_BOOKINGS . " 
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $date_from, $date_to
        ), ARRAY_A);
        
        wp_send_json_success(['daily_data' => $daily_data]);
    }
}

