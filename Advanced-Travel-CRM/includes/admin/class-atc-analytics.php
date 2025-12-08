<?php
/**
 * ATC Analytics
 * Dashboard analytics and reporting
 */

if (!defined('ABSPATH')) exit;

class ATC_Analytics {
    
    public static function init() {
        // Only register menu if enhanced dashboard is not available
        // Enhanced dashboard handles analytics now
        if (!class_exists('ATC_Dashboard_Enhanced')) {
            add_action('admin_menu', [__CLASS__, 'admin_menu']);
        }
    }
    
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Analytics', 'advanced-travel-crm'),
            __('Analytics', 'advanced-travel-crm'),
            'manage_options', // Changed from 'view_atc_analytics' for consistency
            'atc-analytics',
            [__CLASS__, 'analytics_page']
        );
    }
    
    /**
     * Get dashboard stats (backward compatibility wrapper)
     * Now uses enhanced dashboard stats
     */
    public static function get_stats($date_from = null, $date_to = null) {
        // Use enhanced dashboard stats if available
        if (class_exists('ATC_Dashboard_Enhanced')) {
            $enhanced_stats = ATC_Dashboard_Enhanced::get_dashboard_stats();
            
            // Convert to old format for backward compatibility
            return [
                'total_bookings' => $enhanced_stats['month_bookings'] ?? 0,
                'total_revenue' => $enhanced_stats['month_revenue'] ?? 0,
                'total_leads' => $enhanced_stats['total_leads'] ?? 0,
                'hot_leads' => $enhanced_stats['hot_leads'] ?? 0,
                'conversion_rate' => $enhanced_stats['conversion_rate'] ?? 0,
                'top_services' => $enhanced_stats['top_services'] ?? [],
            ];
        }
        
        // Fallback to old method if enhanced not available
        global $wpdb;
        
        if (!$date_from) $date_from = date('Y-m-d', strtotime('-30 days'));
        if (!$date_to) $date_to = date('Y-m-d');
        
        $stats = [];
        
        // Bookings
        $stats['total_bookings'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Revenue
        $stats['total_revenue'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(price_total), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE payment_status = 'paid' AND DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Leads
        $stats['total_leads'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " 
            WHERE DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Hot leads
        $stats['hot_leads'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " 
            WHERE temperature = 'hot' AND DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Conversion rate
        $converted_leads = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " 
            WHERE status = 'converted' AND DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        $stats['conversion_rate'] = $stats['total_leads'] > 0 
            ? round(($converted_leads / $stats['total_leads']) * 100, 2) 
            : 0;
        
        // Top services
        $stats['top_services'] = $wpdb->get_results($wpdb->prepare(
            "SELECT service, COUNT(*) as count, COALESCE(SUM(price_total), 0) as revenue 
            FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE DATE(created_at) BETWEEN %s AND %s 
            GROUP BY service ORDER BY count DESC LIMIT 5",
            $date_from, $date_to
        ), ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Analytics page
     */
    public static function analytics_page() {
        $stats = self::get_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('📊 Analytics Dashboard', 'advanced-travel-crm'); ?></h1>
            
            <div class="atc-stats-grid">
                <div class="atc-stat-card">
                    <h3><?php echo number_format($stats['total_bookings']); ?></h3>
                    <p><?php _e('Total Bookings (30 days)', 'advanced-travel-crm'); ?></p>
                </div>
                
                <div class="atc-stat-card">
                    <h3><?php echo get_option('atc_currency_symbol', '₹') . number_format($stats['total_revenue'], 2); ?></h3>
                    <p><?php _e('Total Revenue (30 days)', 'advanced-travel-crm'); ?></p>
                </div>
                
                <div class="atc-stat-card">
                    <h3><?php echo number_format($stats['total_leads']); ?></h3>
                    <p><?php _e('Total Leads (30 days)', 'advanced-travel-crm'); ?></p>
                </div>
                
                <div class="atc-stat-card">
                    <h3><?php echo $stats['conversion_rate']; ?>%</h3>
                    <p><?php _e('Conversion Rate', 'advanced-travel-crm'); ?></p>
                </div>
            </div>
            
            <div class="atc-analytics-section">
                <h2><?php _e('Top Services', 'advanced-travel-crm'); ?></h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Service', 'advanced-travel-crm'); ?></th>
                            <th><?php _e('Bookings', 'advanced-travel-crm'); ?></th>
                            <th><?php _e('Revenue', 'advanced-travel-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_services'] as $service): ?>
                        <tr>
                            <td><?php echo esc_html(ucfirst($service['service'])); ?></td>
                            <td><?php echo esc_html($service['count']); ?></td>
                            <td><?php echo get_option('atc_currency_symbol', '₹') . number_format($service['revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
