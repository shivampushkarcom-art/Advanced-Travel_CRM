<?php
/**
 * ATC Admin Dashboard
 * Main admin interface with booking management, WhatsApp buttons, and analytics
 * 
 * Features:
 * - Bookings management with status updates
 * - WhatsApp buttons (semi-auto) for quick contact
 * - Email resend functionality
 * - Lead management with scoring
 * - Real-time analytics
 * - Multi-admin notifications
 */

if (!defined('ABSPATH')) exit;

class ATC_Admin {
    
    private static $initialized = false;
    
    public static function init() {
        // Prevent double initialization
        if (self::$initialized) {
            return;
        }
        
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 10); // ✅ FIXED: Register parent menu first
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_atc_update_booking_status', [__CLASS__, 'ajax_update_booking_status']);
        add_action('wp_ajax_atc_mark_lead_contacted', [__CLASS__, 'ajax_mark_lead_contacted']);
        
        self::$initialized = true;
    }
    
    /**
     * Register admin menus
     */
    public static function admin_menu() {
        // Main menu - ✅ FIXED: Use manage_options for consistency
        add_menu_page(
            __('Travel CRM', 'advanced-travel-crm'),
            __('Travel CRM', 'advanced-travel-crm'),
            'manage_options',
            'atc-dashboard',
            [__CLASS__, 'dashboard_page'],
            'dashicons-palmtree',
            3
        );
        
        // Dashboard
        add_submenu_page(
            'atc-dashboard',
            __('Dashboard', 'advanced-travel-crm'),
            __('Dashboard', 'advanced-travel-crm'),
            'manage_options',
            'atc-dashboard',
            [__CLASS__, 'dashboard_page']
        );
        
        // Bookings
        add_submenu_page(
            'atc-dashboard',
            __('All Bookings', 'advanced-travel-crm'),
            __('Bookings', 'advanced-travel-crm'),
            'manage_options',
            'atc-bookings',
            [__CLASS__, 'bookings_page']
        );
        
        // Leads
        add_submenu_page(
            'atc-dashboard',
            __('Leads', 'advanced-travel-crm'),
            __('Leads', 'advanced-travel-crm'),
            'manage_options',
            'atc-leads',
            [__CLASS__, 'leads_page']
        );
        
        // Customers
        add_submenu_page(
            'atc-dashboard',
            __('Customers', 'advanced-travel-crm'),
            __('Customers', 'advanced-travel-crm'),
            'manage_options',
            'atc-customers',
            [__CLASS__, 'customers_page']
        );
        
        // Payments
        add_submenu_page(
            'atc-dashboard',
            __('Payments', 'advanced-travel-crm'),
            __('Payments', 'advanced-travel-crm'),
            'manage_options',
            'atc-payments',
            [__CLASS__, 'payments_page']
        );
        
        // Visitor Tracking
        add_submenu_page(
            'atc-dashboard',
            __('Visitor Tracking', 'advanced-travel-crm'),
            __('Visitors', 'advanced-travel-crm'),
            'manage_options',
            'atc-visitors',
            [__CLASS__, 'visitors_page']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_assets($hook) {
        if (strpos($hook, 'atc') === false) return;
        
        wp_enqueue_style('atc-admin-styles', ATC_ASSETS_URL . 'css/atc-admin.css', [], ATC_VERSION);
        wp_enqueue_script('atc-admin-js', ATC_ASSETS_URL . 'js/atc-admin.js', ['jquery'], ATC_VERSION, true);
        
        wp_localize_script('atc-admin-js', 'atcAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('atc/v1/'),
            'nonce' => wp_create_nonce('atc_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    
    /**
     * Dashboard page
     */
    public static function dashboard_page() {
        $stats = self::get_dashboard_stats();
        $recent_bookings = self::get_recent_bookings();
        $hot_leads = self::get_hot_leads();
        $currency_symbol = get_option('atc_currency_symbol', '₹');

        ?>
        <div class="wrap atc-dashboard">
            <h1><?php _e('🏖️ Travel CRM Dashboard', 'advanced-travel-crm'); ?></h1>

            <div class="atc-stats-grid">
                <?php
                self::render_stat_card('📊', __("Today's Bookings", 'advanced-travel-crm'), number_format((int) $stats['today_bookings']), 'atc-stat-primary');
                self::render_stat_card('💰', __("Today's Revenue", 'advanced-travel-crm'), $currency_symbol . number_format((float) $stats['today_revenue'], 2), 'atc-stat-success');
                self::render_stat_card('⏳', __('Pending Bookings', 'advanced-travel-crm'), number_format((int) $stats['pending_bookings']), 'atc-stat-warning');
                self::render_stat_card('🔥', __('Hot Leads', 'advanced-travel-crm'), number_format((int) $stats['hot_leads']), 'atc-stat-danger');
                ?>
            </div>

            <div class="atc-dashboard-grid">
                <div class="atc-dashboard-section">
                    <div class="atc-section-header">
                        <h2><?php _e('Recent Bookings', 'advanced-travel-crm'); ?></h2>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=atc-bookings')); ?>" class="button">
                            <?php _e('View All', 'advanced-travel-crm'); ?>
                        </a>
                    </div>

                    <div class="atc-table-responsive">
                        <table class="wp-list-table widefat fixed striped atc-bookings-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Booking ID', 'advanced-travel-crm'); ?></th>
                                    <th><?php _e('Customer', 'advanced-travel-crm'); ?></th>
                                    <th><?php _e('Service', 'advanced-travel-crm'); ?></th>
                                    <th><?php _e('Amount', 'advanced-travel-crm'); ?></th>
                                    <th><?php _e('Actions', 'advanced-travel-crm'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_bookings)) : ?>
                                    <tr><td colspan="5"><?php _e('No bookings yet.', 'advanced-travel-crm'); ?></td></tr>
                                <?php else : ?>
                                    <?php foreach ($recent_bookings as $booking) : ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($booking['booking_id']); ?></strong></td>
                                            <td>
                                                <?php echo esc_html($booking['customer_name']); ?><br>
                                                <small><?php echo esc_html($booking['customer_email']); ?></small>
                                            </td>
                                            <td><?php echo esc_html(ucfirst($booking['service'])); ?></td>
                                            <td><strong><?php echo esc_html($booking['currency'] . ' ' . number_format((float) $booking['price_total'], 2)); ?></strong></td>
                                            <td>
                                                <?php
                                                $whatsapp_button = class_exists('ATC_Notification_Manager')
                                                    ? ATC_Notification_Manager::get_whatsapp_button($booking['id'], 'booking', 'customer')
                                                    : '';

                                                echo $whatsapp_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped by helper
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="atc-dashboard-section">
                    <div class="atc-section-header">
                        <h2><?php _e('🔥 Hot Leads', 'advanced-travel-crm'); ?></h2>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=atc-leads')); ?>" class="button">
                            <?php _e('View All', 'advanced-travel-crm'); ?>
                        </a>
                    </div>

                    <div class="atc-leads-grid">
                        <?php if (empty($hot_leads)) : ?>
                            <p><?php _e('No hot leads at the moment.', 'advanced-travel-crm'); ?></p>
                        <?php else : ?>
                            <?php foreach ($hot_leads as $lead) :
                                $temperature = sanitize_key($lead['temperature'] ?: 'hot');
                                $card_class = 'atc-lead-card atc-lead-' . $temperature;
                                $score_class = 'atc-lead-score-badge atc-score-' . $temperature;
                                ?>
                                <div class="<?php echo esc_attr($card_class); ?>">
                                    <div class="atc-lead-header">
                                        <h4><?php echo esc_html($lead['name'] ?: $lead['email']); ?></h4>
                                        <span class="<?php echo esc_attr($score_class); ?>"><?php echo esc_html((int) $lead['score']); ?>/100</span>
                                    </div>
                                    <div class="atc-lead-body">
                                        <p>
                                            <strong><?php _e('Destination:', 'advanced-travel-crm'); ?></strong>
                                            <?php echo esc_html($lead['destination']); ?>
                                        </p>
                                        <?php if (!empty($lead['email'])) : ?>
                                            <p>📧 <?php echo esc_html($lead['email']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['phone'])) : ?>
                                            <p>📱 <?php echo esc_html($lead['phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="atc-lead-footer">
                                        <?php
                                        $lead_button = class_exists('ATC_Notification_Manager')
                                            ? ATC_Notification_Manager::get_whatsapp_button($lead['id'], 'lead', 'customer')
                                            : '';

                                        echo $lead_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped by helper
                                        ?>
                                        <button class="button button-small atc-mark-contacted" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                                            <?php _e('Mark Contacted', 'advanced-travel-crm'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Bookings management page
     */
    public static function bookings_page() {
        global $wpdb;
        
        if (isset($_POST['update_booking_status']) && check_admin_referer('atc_update_status')) {
            $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
            $new_status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

            if ($booking_id && $new_status) {
                // Get old status
                $old_booking = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
                    $booking_id
                ), ARRAY_A);
                
                $old_status = $old_booking['status'] ?? '';
                
                // Update status
                $result = $wpdb->update(
                    ATC_TABLE_BOOKINGS,
                    ['status' => $new_status, 'updated_at' => current_time('mysql')],
                    ['id' => $booking_id]
                );

                if ($result !== false) {
                    // Trigger notification if status changed
                    if ($old_status !== $new_status && class_exists('ATC_Notification_Manager') && $old_booking) {
                        $booking = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
                            $booking_id
                        ), ARRAY_A);
                        
                        if ($booking) {
                            ATC_Notification_Manager::on_booking_status_changed($booking_id, $old_status, $new_status, $booking);
                        }
                    }
                    
                    echo '<div class="notice notice-success"><p>' . esc_html__('Status updated successfully!', 'advanced-travel-crm') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Unable to update booking status.', 'advanced-travel-crm') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Unable to update booking status.', 'advanced-travel-crm') . '</p></div>';
            }
        }

        $status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $service_filter = isset($_GET['service']) ? sanitize_text_field(wp_unslash($_GET['service'])) : '';

        $where = ['1=1'];
        if ($status_filter) {
            $where[] = $wpdb->prepare('status = %s', $status_filter);
        }
        if ($service_filter) {
            $where[] = $wpdb->prepare('service = %s', $service_filter);
        }

        $query = sprintf(
            'SELECT * FROM %s WHERE %s ORDER BY created_at DESC LIMIT 50',
            ATC_TABLE_BOOKINGS,
            implode(' AND ', $where)
        );

        $bookings = $wpdb->get_results($query, ARRAY_A);
        $services = self::get_available_services();
        $status_options = [
            'pending'   => __('Pending', 'advanced-travel-crm'),
            'confirmed' => __('Confirmed', 'advanced-travel-crm'),
            'completed' => __('Completed', 'advanced-travel-crm'),
            'cancelled' => __('Cancelled', 'advanced-travel-crm'),
        ];

        ?>
        <div class="wrap">
            <h1><?php _e('All Bookings', 'advanced-travel-crm'); ?></h1>

            <div class="atc-filters">
                <form method="get">
                    <input type="hidden" name="page" value="atc-bookings">

                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'advanced-travel-crm'); ?></option>
                        <?php foreach ($status_options as $status_key => $label) : ?>
                            <option value="<?php echo esc_attr($status_key); ?>" <?php selected($status_filter, $status_key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="service">
                        <option value=""><?php _e('All Services', 'advanced-travel-crm'); ?></option>
                        <?php foreach ($services as $service_key => $label) : ?>
                            <option value="<?php echo esc_attr($service_key); ?>" <?php selected($service_filter, $service_key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="button"><?php _e('Filter', 'advanced-travel-crm'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=atc-bookings')); ?>" class="button">
                        <?php _e('Reset', 'advanced-travel-crm'); ?>
                    </a>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped atc-bookings-table">
                <thead>
                    <tr>
                        <th><?php _e('Booking ID', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Customer', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Service', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Amount', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Date', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Actions', 'advanced-travel-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)) : ?>
                        <tr><td colspan="7"><?php _e('No bookings found.', 'advanced-travel-crm'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($bookings as $booking) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($booking['booking_id']); ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($booking['customer_name']); ?></strong><br>
                                    <?php echo esc_html($booking['customer_email']); ?><br>
                                    <small><?php echo esc_html($booking['customer_phone']); ?></small>
                                </td>
                                <td><?php echo esc_html(ucfirst($booking['service'])); ?></td>
                                <td><strong><?php echo esc_html($booking['currency'] . ' ' . number_format((float) $booking['price_total'], 2)); ?></strong></td>
                                <td>
                                    <span class="atc-status-badge atc-status-<?php echo esc_attr($booking['status']); ?>">
                                        <?php echo esc_html(ucfirst($booking['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($booking['created_at']))); ?></td>
                                <td>
                                    <div class="atc-actions-group">
                                        <button class="button button-small atc-view-customer" 
                                                data-booking-id="<?php echo esc_attr($booking['id']); ?>"
                                                data-customer-name="<?php echo esc_attr($booking['customer_name']); ?>"
                                                data-customer-email="<?php echo esc_attr($booking['customer_email']); ?>"
                                                data-customer-phone="<?php echo esc_attr($booking['customer_phone']); ?>"
                                                data-service="<?php echo esc_attr($booking['service']); ?>"
                                                data-price="<?php echo esc_attr($booking['price_total']); ?>"
                                                data-currency="<?php echo esc_attr($booking['currency']); ?>"
                                                data-status="<?php echo esc_attr($booking['status']); ?>"
                                                data-payment-status="<?php echo esc_attr($booking['payment_status']); ?>"
                                                data-created="<?php echo esc_attr($booking['created_at']); ?>">
                                            👤 <?php _e('View Details', 'advanced-travel-crm'); ?>
                                        </button>
                                        
                                        <?php
                                        $whatsapp_button = class_exists('ATC_Notification_Manager')
                                            ? ATC_Notification_Manager::get_whatsapp_button($booking['id'], 'booking', 'customer')
                                            : '';

                                        echo $whatsapp_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped by helper
                                        ?>


                                        <form method="post" class="atc-status-form" style="display: inline-block;">
                                            <?php wp_nonce_field('atc_update_status'); ?>
                                            <input type="hidden" name="booking_id" value="<?php echo esc_attr($booking['id']); ?>">
                                            <select name="status" class="atc-status-select" onchange="this.form.submit()">
                                                <option value=""><?php _e('Change Status', 'advanced-travel-crm'); ?></option>
                                                <?php foreach ($status_options as $status_key => $label) : ?>
                                                    <option value="<?php echo esc_attr($status_key); ?>" <?php selected($booking['status'], $status_key); ?>><?php echo esc_html($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="update_booking_status" value="1">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Leads management page - Enhanced for Lead-Based Website
     */
    public static function leads_page() {
        global $wpdb;
        
        // Handle CSV export
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            self::export_leads_csv();
            exit;
        }
        
        // Handle lead actions
        if (isset($_POST['update_lead_status']) && check_admin_referer('atc_update_lead_status')) {
            $lead_id = intval($_POST['lead_id']);
            $new_status = sanitize_text_field($_POST['status']);
            $wpdb->update(
                ATC_TABLE_LEADS,
                ['status' => $new_status, 'updated_at' => current_time('mysql')],
                ['id' => $lead_id]
            );
            echo '<div class="notice notice-success"><p>' . esc_html__('Lead status updated!', 'advanced-travel-crm') . '</p></div>';
        }
        
        if (isset($_POST['assign_lead']) && check_admin_referer('atc_assign_lead')) {
            $lead_id = intval($_POST['lead_id']);
            $assigned_to = intval($_POST['assigned_to']);
            $wpdb->update(
                ATC_TABLE_LEADS,
                ['assigned_to' => $assigned_to, 'updated_at' => current_time('mysql')],
                ['id' => $lead_id]
            );
            echo '<div class="notice notice-success"><p>' . esc_html__('Lead assigned!', 'advanced-travel-crm') . '</p></div>';
        }
        
        // Filters
        $temperature_filter = isset($_GET['temperature']) ? sanitize_key($_GET['temperature']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $service_filter = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $where = ['1=1'];
        if ($temperature_filter) {
            $where[] = $wpdb->prepare('temperature = %s', $temperature_filter);
        }
        if ($status_filter) {
            $where[] = $wpdb->prepare('status = %s', $status_filter);
        }
        if ($service_filter) {
            $where[] = $wpdb->prepare('service = %s', $service_filter);
        }
        if ($search_query) {
            $where[] = $wpdb->prepare('(name LIKE %s OR email LIKE %s OR phone LIKE %s OR destination LIKE %s)', 
                '%' . $wpdb->esc_like($search_query) . '%',
                '%' . $wpdb->esc_like($search_query) . '%',
                '%' . $wpdb->esc_like($search_query) . '%',
                '%' . $wpdb->esc_like($search_query) . '%'
            );
        }

        // Get statistics
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN temperature = 'hot' THEN 1 END) as hot,
                COUNT(CASE WHEN temperature = 'warm' THEN 1 END) as warm,
                COUNT(CASE WHEN temperature = 'cold' THEN 1 END) as cold,
                COUNT(CASE WHEN status = 'new' THEN 1 END) as new,
                COUNT(CASE WHEN status = 'contacted' THEN 1 END) as contacted,
                COUNT(CASE WHEN status = 'qualified' THEN 1 END) as qualified,
                COUNT(CASE WHEN status = 'converted' THEN 1 END) as converted
            FROM " . ATC_TABLE_LEADS
        );

        $query = sprintf(
            'SELECT * FROM %s WHERE %s ORDER BY score DESC, created_at DESC LIMIT 100',
            ATC_TABLE_LEADS,
            implode(' AND ', $where)
        );

        $leads = $wpdb->get_results($query, ARRAY_A);

        $temperature_options = [
            ''      => __('All Leads', 'advanced-travel-crm'),
            'hot'   => __('🔥 Hot Leads', 'advanced-travel-crm'),
            'warm'  => __('🟡 Warm Leads', 'advanced-travel-crm'),
            'cold'  => __('❄️ Cold Leads', 'advanced-travel-crm'),
        ];

        // Get all admins for assignment
        $admins = get_users(['role' => 'administrator']);
        
        $status_options = [
            'new' => __('🆕 New', 'advanced-travel-crm'),
            'contacted' => __('📞 Contacted', 'advanced-travel-crm'),
            'qualified' => __('✅ Qualified', 'advanced-travel-crm'),
            'converted' => __('💰 Converted', 'advanced-travel-crm'),
            'lost' => __('❌ Lost', 'advanced-travel-crm'),
        ];
        
        ?>
        <div class="wrap atc-leads-enhanced">
            <div class="atc-leads-header">
                <div>
                    <h1>👥 Lead Management</h1>
                    <p class="description">Manage and track all your leads from website visitors</p>
                </div>
                <div class="atc-leads-actions">
                    <a href="<?php echo admin_url('admin.php?page=atc-leads&export=csv'); ?>" class="button">
                        📥 Export CSV
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=atc-visitors'); ?>" class="button">
                        👁️ View Visitors
                    </a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="atc-leads-stats">
                <div class="atc-stat-card">
                    <div class="atc-stat-value"><?php echo esc_html($stats->total ?? 0); ?></div>
                    <div class="atc-stat-label">Total Leads</div>
                </div>
                <div class="atc-stat-card atc-stat-hot">
                    <div class="atc-stat-value"><?php echo esc_html($stats->hot ?? 0); ?></div>
                    <div class="atc-stat-label">🔥 Hot Leads</div>
                </div>
                <div class="atc-stat-card atc-stat-warm">
                    <div class="atc-stat-value"><?php echo esc_html($stats->warm ?? 0); ?></div>
                    <div class="atc-stat-label">🟡 Warm Leads</div>
                </div>
                <div class="atc-stat-card atc-stat-new">
                    <div class="atc-stat-value"><?php echo esc_html($stats->new ?? 0); ?></div>
                    <div class="atc-stat-label">🆕 New Leads</div>
                </div>
                <div class="atc-stat-card atc-stat-qualified">
                    <div class="atc-stat-value"><?php echo esc_html($stats->qualified ?? 0); ?></div>
                    <div class="atc-stat-label">✅ Qualified</div>
                </div>
                <div class="atc-stat-card atc-stat-converted">
                    <div class="atc-stat-value"><?php echo esc_html($stats->converted ?? 0); ?></div>
                    <div class="atc-stat-label">💰 Converted</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="atc-leads-filters">
                <form method="get" class="atc-filter-form">
                    <input type="hidden" name="page" value="atc-leads">
                    
                    <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" 
                           placeholder="🔍 Search leads..." class="atc-search-input">
                    
                    <select name="temperature">
                        <option value="">All Temperatures</option>
                        <?php foreach ($temperature_options as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($temperature_filter, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($status_options as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($status_filter, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="service">
                        <option value="">All Services</option>
                        <option value="tours" <?php selected($service_filter, 'tours'); ?>>Tours</option>
                        <option value="forex" <?php selected($service_filter, 'forex'); ?>>Forex</option>
                        <option value="visa" <?php selected($service_filter, 'visa'); ?>>Visa</option>
                        <option value="hotels" <?php selected($service_filter, 'hotels'); ?>>Hotels</option>
                        <option value="flights" <?php selected($service_filter, 'flights'); ?>>Flights</option>
                        <option value="trains" <?php selected($service_filter, 'trains'); ?>>Trains</option>
                        <option value="cars" <?php selected($service_filter, 'cars'); ?>>Cars</option>
                    </select>
                    
                    <button type="submit" class="button button-primary">Filter</button>
                    <a href="<?php echo admin_url('admin.php?page=atc-leads'); ?>" class="button">Reset</a>
                </form>
            </div>

            <!-- Leads Table/Grid -->
            <div class="atc-leads-container">
                <?php if (empty($leads)) : ?>
                    <div class="atc-empty-state">
                        <p>No leads found matching your filters.</p>
                    </div>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped atc-leads-table">
                        <thead>
                            <tr>
                                <th>Lead</th>
                                <th>Contact</th>
                                <th>Details</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $lead) :
                                $temperature = sanitize_key($lead['temperature'] ?: 'cold');
                                $status = sanitize_key($lead['status'] ?: 'new');
                                $relative_time = human_time_diff(strtotime($lead['created_at']), current_time('timestamp'));
                                $assigned_user = $lead['assigned_to'] ? get_userdata($lead['assigned_to']) : null;
                                ?>
                                <tr class="atc-lead-row atc-lead-<?php echo esc_attr($temperature); ?>">
                                    <td>
                                        <strong><?php echo esc_html($lead['name'] ?: ($lead['email'] ?: __('Anonymous', 'advanced-travel-crm'))); ?></strong>
                                        <?php if ($assigned_user): ?>
                                            <br><small>👤 Assigned to: <?php echo esc_html($assigned_user->display_name); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($lead['email'])) : ?>
                                            <div>📧 <a href="mailto:<?php echo esc_attr($lead['email']); ?>"><?php echo esc_html($lead['email']); ?></a></div>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['phone'])) : ?>
                                            <div>📱 <a href="tel:<?php echo esc_attr($lead['phone']); ?>"><?php echo esc_html($lead['phone']); ?></a></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($lead['destination'])) : ?>
                                            <div><strong>📍</strong> <?php echo esc_html($lead['destination']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['service'])) : ?>
                                            <div><strong>🎯</strong> <?php echo esc_html(ucfirst($lead['service'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['date_from']) && !empty($lead['date_to'])) : ?>
                                            <div><strong>📅</strong> <?php echo esc_html($lead['date_from']); ?> - <?php echo esc_html($lead['date_to']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['budget_min']) && !empty($lead['budget_max'])) : ?>
                                            <div><strong>💰</strong> <?php echo esc_html($lead['currency'] ?? 'INR'); ?> <?php echo esc_html(number_format((float) $lead['budget_min'])); ?> - <?php echo esc_html(number_format((float) $lead['budget_max'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="atc-score-badge atc-score-<?php echo esc_attr($temperature); ?>">
                                            <?php echo esc_html((int) $lead['score']); ?>/100
                                        </span>
                                        <div class="atc-temperature-badge atc-temp-<?php echo esc_attr($temperature); ?>">
                                            <?php 
                                            $temp_icons = ['hot' => '🔥', 'warm' => '🟡', 'cold' => '❄️'];
                                            echo $temp_icons[$temperature] ?? '❄️';
                                            echo ' ' . ucfirst($temperature);
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="post" class="atc-status-form-inline">
                                            <?php wp_nonce_field('atc_update_lead_status'); ?>
                                            <input type="hidden" name="lead_id" value="<?php echo esc_attr($lead['id']); ?>">
                                            <select name="status" class="atc-status-select" onchange="this.form.submit()">
                                                <?php foreach ($status_options as $key => $label) : ?>
                                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($status, $key); ?>>
                                                        <?php echo esc_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="update_lead_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <small><?php echo esc_html(ucfirst($lead['source'] ?? 'search')); ?></small>
                                        <?php if (!empty($lead['utm_source'])): ?>
                                            <br><small>📊 <?php echo esc_html($lead['utm_source']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo esc_html($relative_time); ?> ago</small>
                                        <br><small><?php echo esc_html(date('d M Y, h:i A', strtotime($lead['created_at']))); ?></small>
                                    </td>
                                    <td>
                                        <div class="atc-lead-actions">
                                            <?php
                                            $whatsapp_button = class_exists('ATC_Notification_Manager')
                                                ? ATC_Notification_Manager::get_whatsapp_button($lead['id'], 'lead', 'customer')
                                                : '';
                                            echo $whatsapp_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            ?>
                                            <a href="mailto:<?php echo esc_attr($lead['email'] ?? ''); ?>" class="button button-small">
                                                📧 Email
                                            </a>
                                            <?php if (!empty($lead['phone'])): ?>
                                                <a href="tel:<?php echo esc_attr($lead['phone']); ?>" class="button button-small">
                                                    📞 Call
                                                </a>
                                            <?php endif; ?>
                                            <button class="button button-small atc-view-lead-details" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                                                👁️ View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <style>
            .atc-leads-enhanced {
                background: #f8fafc;
                padding: 20px;
            }
            .atc-leads-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #e2e8f0;
            }
            .atc-leads-actions {
                display: flex;
                gap: 10px;
            }
            .atc-leads-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin-bottom: 30px;
            }
            .atc-stat-card {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                text-align: center;
            }
            .atc-stat-value {
                font-size: 32px;
                font-weight: bold;
                color: #0A1F44;
                margin-bottom: 5px;
            }
            .atc-stat-label {
                color: #64748b;
                font-size: 14px;
            }
            .atc-stat-hot .atc-stat-value { color: #ef4444; }
            .atc-stat-warm .atc-stat-value { color: #f59e0b; }
            .atc-stat-new .atc-stat-value { color: #3b82f6; }
            .atc-stat-qualified .atc-stat-value { color: #10b981; }
            .atc-stat-converted .atc-stat-value { color: #8b5cf6; }
            .atc-leads-filters {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .atc-filter-form {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                align-items: center;
            }
            .atc-search-input {
                flex: 1;
                min-width: 200px;
                padding: 8px 12px;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
            }
            .atc-leads-container {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .atc-leads-table {
                margin: 0;
            }
            .atc-lead-row {
                transition: background 0.2s;
            }
            .atc-lead-row:hover {
                background: #f8fafc !important;
            }
            .atc-lead-hot { border-left: 4px solid #ef4444; }
            .atc-lead-warm { border-left: 4px solid #f59e0b; }
            .atc-lead-cold { border-left: 4px solid #94a3b8; }
            .atc-score-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-weight: 600;
                font-size: 14px;
            }
            .atc-score-hot { background: #fee2e2; color: #991b1b; }
            .atc-score-warm { background: #fef3c7; color: #92400e; }
            .atc-score-cold { background: #f1f5f9; color: #475569; }
            .atc-temperature-badge {
                margin-top: 5px;
                font-size: 12px;
                font-weight: 600;
            }
            .atc-temp-hot { color: #ef4444; }
            .atc-temp-warm { color: #f59e0b; }
            .atc-temp-cold { color: #94a3b8; }
            .atc-status-form-inline {
                display: inline-block;
            }
            .atc-status-select {
                padding: 4px 8px;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
            }
            .atc-lead-actions {
                display: flex;
                gap: 5px;
                flex-wrap: wrap;
            }
            .atc-empty-state {
                text-align: center;
                padding: 60px 20px;
                color: #64748b;
            }
            @media (max-width: 1200px) {
                .atc-leads-stats {
                    grid-template-columns: repeat(3, 1fr);
                }
            }
            @media (max-width: 768px) {
                .atc-leads-header {
                    flex-direction: column;
                    gap: 15px;
                }
                .atc-leads-stats {
                    grid-template-columns: repeat(2, 1fr);
                }
                .atc-filter-form {
                    flex-direction: column;
                }
                .atc-search-input {
                    width: 100%;
                }
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Customers page
     */
    public static function customers_page() {
        global $wpdb;
        
        $customers = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_CUSTOMERS . " ORDER BY total_spent DESC LIMIT 50",
            ARRAY_A
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('Customers', 'advanced-travel-crm'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Contact', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Total Bookings', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Total Spent', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Last Booking', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Type', 'advanced-travel-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                    <tr><td colspan="6"><?php _e('No customers yet.', 'advanced-travel-crm'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><strong><?php echo esc_html($customer['name']); ?></strong></td>
                        <td>
                            📧 <?php echo esc_html($customer['email']); ?><br>
                            <?php if ($customer['phone']): ?>
                            📱 <?php echo esc_html($customer['phone']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($customer['total_bookings']); ?></td>
                        <td><strong><?php echo get_option('atc_currency_symbol', '₹') . number_format($customer['total_spent'], 2); ?></strong></td>
                        <td><?php echo $customer['last_booking_date'] ? esc_html(date('Y-m-d', strtotime($customer['last_booking_date']))) : '-'; ?></td>
                        <td>
                            <span class="atc-badge atc-badge-<?php echo $customer['customer_type']; ?>">
                                <?php echo esc_html(ucfirst($customer['customer_type'])); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Payments page
     */
    public static function payments_page() {
        global $wpdb;
        
        $payments = $wpdb->get_results(
            "SELECT p.*, b.booking_id, b.customer_name 
            FROM " . ATC_TABLE_PAYMENTS . " p
            LEFT JOIN " . ATC_TABLE_BOOKINGS . " b ON p.booking_id = b.id
            ORDER BY p.created_at DESC LIMIT 50",
            ARRAY_A
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('Payments', 'advanced-travel-crm'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Transaction ID', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Booking ID', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Customer', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Amount', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Gateway', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Date', 'advanced-travel-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr><td colspan="7"><?php _e('No payments yet.', 'advanced-travel-crm'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><code><?php echo esc_html($payment['transaction_id']); ?></code></td>
                        <td><strong><?php echo esc_html($payment['booking_id'] ?? '-'); ?></strong></td>
                        <td><?php echo esc_html($payment['customer_name'] ?? '-'); ?></td>
                        <td><strong><?php echo esc_html($payment['currency'] . ' ' . number_format($payment['amount'], 2)); ?></strong></td>
                        <td><?php echo esc_html(ucfirst($payment['gateway'])); ?></td>
                        <td>
                            <span class="atc-status-badge atc-status-<?php echo $payment['status']; ?>">
                                <?php echo esc_html(ucfirst($payment['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('Y-m-d H:i', strtotime($payment['created_at']))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render dashboard stat card
     */
    private static function render_stat_card($icon, $label, $value, $modifier_class) {
        ?>
        <div class="atc-stat-card <?php echo esc_attr($modifier_class); ?>">
            <div class="atc-stat-icon"><?php echo esc_html($icon); ?></div>
            <div class="atc-stat-content">
                <h3><?php echo esc_html($value); ?></h3>
                <p><?php echo esc_html($label); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Collect dashboard stats
     */
    private static function get_dashboard_stats() {
        global $wpdb;

        $today = current_time('Y-m-d');

        return [
            'today_bookings'    => (int) $wpdb->get_var($wpdb->prepare(
                'SELECT COUNT(*) FROM ' . ATC_TABLE_BOOKINGS . ' WHERE DATE(created_at) = %s',
                $today
            )),
            'today_revenue'     => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(price_total) FROM " . ATC_TABLE_BOOKINGS . " WHERE DATE(created_at) = %s AND payment_status = 'paid'",
                $today
            )),
            'pending_bookings'  => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " WHERE status = 'pending'"
            ),
            'hot_leads'         => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " WHERE temperature = 'hot' AND status = 'new'"
            ),
        ];
    }

    /**
     * Fetch recent bookings
     */
    private static function get_recent_bookings($limit = 5) {
        global $wpdb;

        $limit = absint($limit);
        if ($limit <= 0) {
            $limit = 5;
        }

        $query = $wpdb->prepare(
            'SELECT * FROM ' . ATC_TABLE_BOOKINGS . ' ORDER BY created_at DESC LIMIT %d',
            $limit
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Fetch hot leads for dashboard
     */
    private static function get_hot_leads($limit = 5) {
        global $wpdb;

        $limit = absint($limit);
        if ($limit <= 0) {
            $limit = 5;
        }

        $query = $wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_LEADS . " WHERE temperature = 'hot' AND status = 'new' ORDER BY score DESC LIMIT %d",
            $limit
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Return enabled service keys for filters
     */
    private static function get_available_services() {
        if (class_exists('ATC_Services')) {
            $service_configs = ATC_Services::get_services();
            $services = [];

            foreach ($service_configs as $key => $config) {
                $services[$key] = $config['label'] ?? ucfirst($key);
            }

            return $services;
        }

        return [
            'tours'  => __('Tours', 'advanced-travel-crm'),
            'hotels' => __('Hotels', 'advanced-travel-crm'),
            'flights' => __('Flights', 'advanced-travel-crm'),
            'trains' => __('Trains', 'advanced-travel-crm'),
            'safari' => __('Safari', 'advanced-travel-crm'),
            'cars'   => __('Cars', 'advanced-travel-crm'),
            'forex'  => __('Forex', 'advanced-travel-crm'),
        ];
    }
    
    /**
     * AJAX: Update booking status
     */
    public static function ajax_update_booking_status() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!current_user_can('manage_atc')) {
            wp_send_json_error(['message' => __('Unauthorized', 'advanced-travel-crm')]);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        if (!$booking_id || !$status) {
            wp_send_json_error(['message' => __('Invalid parameters', 'advanced-travel-crm')]);
        }
        
        global $wpdb;
        
        // Get old status
        $old_booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$old_booking) {
            wp_send_json_error(['message' => __('Booking not found', 'advanced-travel-crm')]);
        }
        
        $old_status = $old_booking['status'];
        
        // Update status
        $result = $wpdb->update(
            ATC_TABLE_BOOKINGS,
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $booking_id]
        );
        
        if ($result !== false) {
            // Trigger notification if status changed
            if ($old_status !== $status && class_exists('ATC_Notification_Manager')) {
                $booking = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
                    $booking_id
                ), ARRAY_A);
                
                if ($booking) {
                    ATC_Notification_Manager::on_booking_status_changed($booking_id, $old_status, $status, $booking);
                }
            }
            
            wp_send_json_success(['message' => __('Status updated successfully!', 'advanced-travel-crm')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update status', 'advanced-travel-crm')]);
        }
    }
    
    
    /**
     * AJAX: Mark lead as contacted
     */
    public static function ajax_mark_lead_contacted() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!current_user_can('manage_atc')) {
            wp_send_json_error(['message' => __('Unauthorized', 'advanced-travel-crm')]);
        }
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        
        if (!$lead_id) {
            wp_send_json_error(['message' => __('Invalid lead ID', 'advanced-travel-crm')]);
        }
        
        global $wpdb;
        $result = $wpdb->update(
            ATC_TABLE_LEADS,
            ['status' => 'contacted', 'updated_at' => current_time('mysql')],
            ['id' => $lead_id]
        );
        
        if ($result !== false) {
            // Log activity
            $wpdb->insert(ATC_TABLE_LEAD_ACTIVITIES, [
                'lead_id' => $lead_id,
                'activity_type' => 'contacted',
                'description' => __('Lead marked as contacted', 'advanced-travel-crm'),
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
            ]);
            
            wp_send_json_success(['message' => __('Lead marked as contacted!', 'advanced-travel-crm')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update lead', 'advanced-travel-crm')]);
        }
    }
    
    /**
     * Visitor Tracking page
     */
    public static function visitors_page() {
        global $wpdb;
        
        // Check if table exists
        $table_name = $wpdb->prefix . 'atc_visitor_tracking';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            echo '<div class="wrap"><h1>👁️ Visitor Tracking & Analytics</h1>';
            echo '<div class="notice notice-error"><p>The visitor tracking table does not exist. Please deactivate and reactivate the plugin to create the table.</p></div>';
            echo '</div>';
            return;
        }
        
        // Get filter parameters
        $activity_type = isset($_GET['activity_type']) ? sanitize_text_field($_GET['activity_type']) : '';
        $service = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-7 days'));
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
        
        // Build query safely
        $where_conditions = ["DATE(created_at) BETWEEN %s AND %s"];
        $query_params = [$date_from, $date_to];
        
        if (!empty($activity_type)) {
            $where_conditions[] = "activity_type = %s";
            $query_params[] = $activity_type;
        }
        
        if (!empty($service)) {
            $where_conditions[] = "service = %s";
            $query_params[] = $service;
        }
        
        $where_sql = implode(' AND ', $where_conditions);
        
        // Get visitors
        $visitors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE {$where_sql}
            ORDER BY created_at DESC 
            LIMIT 100",
            $query_params
        ));
        
        // Get statistics
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(*) as total_activities,
                COUNT(DISTINCT CASE WHEN activity_type = 'booking' THEN visitor_id END) as visitors_with_bookings,
                COUNT(DISTINCT CASE WHEN activity_type = 'query' THEN visitor_id END) as visitors_with_queries
            FROM {$table_name} 
            WHERE {$where_sql}",
            $query_params
        ));
        
        ?>
        <div class="wrap">
            <h1>👁️ Visitor Tracking & Analytics</h1>
            
            <!-- Statistics Cards -->
            <div class="atc-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="atc-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; color: #667eea;">Unique Visitors</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0; color: #333;"><?php echo esc_html($stats->unique_visitors ?? 0); ?></p>
                </div>
                <div class="atc-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; color: #764ba2;">Total Activities</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0; color: #333;"><?php echo esc_html($stats->total_activities ?? 0); ?></p>
                </div>
                <div class="atc-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; color: #ff6b35;">Bookings</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0; color: #333;"><?php echo esc_html($stats->visitors_with_bookings ?? 0); ?></p>
                </div>
                <div class="atc-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; color: #4ecdc4;">Queries</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0; color: #333;"><?php echo esc_html($stats->visitors_with_queries ?? 0); ?></p>
                </div>
            </div>
            
            <!-- Filters -->
            <form method="get" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <input type="hidden" name="page" value="atc-visitors">
                <table class="form-table">
                    <tr>
                        <th>Activity Type</th>
                        <td>
                            <select name="activity_type">
                                <option value="">All Activities</option>
                                <option value="page_view" <?php selected($activity_type, 'page_view'); ?>>Page Views</option>
                                <option value="search" <?php selected($activity_type, 'search'); ?>>Searches</option>
                                <option value="booking" <?php selected($activity_type, 'booking'); ?>>Bookings</option>
                                <option value="query" <?php selected($activity_type, 'query'); ?>>Queries</option>
                                <option value="package_view" <?php selected($activity_type, 'package_view'); ?>>Package Views</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Service</th>
                        <td>
                            <select name="service">
                                <option value="">All Services</option>
                                <option value="tours" <?php selected($service, 'tours'); ?>>Tours</option>
                                <option value="forex" <?php selected($service, 'forex'); ?>>Forex</option>
                                <option value="visa" <?php selected($service, 'visa'); ?>>Visa</option>
                                <option value="hotels" <?php selected($service, 'hotels'); ?>>Hotels</option>
                                <option value="flights" <?php selected($service, 'flights'); ?>>Flights</option>
                                <option value="trains" <?php selected($service, 'trains'); ?>>Trains</option>
                                <option value="cars" <?php selected($service, 'cars'); ?>>Cars</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Date From</th>
                        <td><input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>"></td>
                    </tr>
                    <tr>
                        <th>Date To</th>
                        <td><input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>"></td>
                    </tr>
                </table>
                <?php submit_button('Filter', 'primary', 'submit', false); ?>
            </form>
            
            <!-- Visitors Table -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>Recent Visitor Activities</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Activity</th>
                            <th>Location</th>
                            <th>Device</th>
                            <th>Service</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitors)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">No visitor activities found for the selected filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visitors as $visitor): 
                                $activity_data = !empty($visitor->activity_data) ? json_decode($visitor->activity_data, true) : [];
                            ?>
                                <tr>
                                    <td><?php echo esc_html(date('d M Y, h:i A', strtotime($visitor->created_at))); ?></td>
                                    <td>
                                        <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $visitor->activity_type ?? 'unknown'))); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo esc_html($visitor->city ?? 'Unknown'); ?>, <?php echo esc_html($visitor->state ?? 'Unknown'); ?><br>
                                        <small style="color: #666;"><?php echo esc_html($visitor->country ?? 'Unknown'); ?></small>
                                    </td>
                                    <td>
                                        <?php echo esc_html(ucfirst($visitor->device_type ?? 'Unknown')); ?><br>
                                        <small style="color: #666;"><?php echo esc_html($visitor->browser ?? 'Unknown'); ?> on <?php echo esc_html($visitor->os ?? 'Unknown'); ?></small>
                                    </td>
                                    <td><?php echo esc_html($visitor->service ? ucfirst($visitor->service) : '-'); ?></td>
                                    <td>
                                        <?php if ($visitor->activity_type === 'booking' && !empty($activity_data['customer_name'])): ?>
                                            <strong><?php echo esc_html($activity_data['customer_name']); ?></strong><br>
                                            <small><?php echo esc_html($activity_data['customer_phone'] ?? ''); ?></small><br>
                                            <small>Booking: <?php echo esc_html($activity_data['booking_id'] ?? ''); ?></small>
                                        <?php elseif ($visitor->activity_type === 'query' && !empty($activity_data['customer_name'])): ?>
                                            <strong><?php echo esc_html($activity_data['customer_name']); ?></strong><br>
                                            <small><?php echo esc_html($activity_data['customer_phone'] ?? ''); ?></small>
                                        <?php elseif ($visitor->activity_type === 'search' && !empty($visitor->search_query)): ?>
                                            <strong>Query:</strong> <?php echo esc_html($visitor->search_query); ?>
                                        <?php elseif ($visitor->activity_type === 'package_view' && !empty($activity_data['package_name'])): ?>
                                            <strong>Package:</strong> <?php echo esc_html($activity_data['package_name']); ?>
                                        <?php else: ?>
                                            <small style="color: #666;"><?php echo esc_html($visitor->current_page ?? ''); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}