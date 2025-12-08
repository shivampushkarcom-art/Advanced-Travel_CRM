<?php
/**
 * ATC Cancellation System
 * Complete booking cancellation with time windows, approval, refunds
 */

if (!defined('ABSPATH')) exit;

class ATC_Cancellation {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_post_atc_save_cancellation_settings', [__CLASS__, 'save_settings']);
        add_action('admin_post_atc_approve_cancellation', [__CLASS__, 'approve_cancellation']);
        add_action('admin_post_atc_reject_cancellation', [__CLASS__, 'reject_cancellation']);
    }
    
    /**
     * Check if cancellation is allowed for booking
     */
    public static function can_cancel($booking_id) {
        if (!ATC_Feature_Manager::is_enabled('booking_cancellation')) {
            return new WP_Error('disabled', __('Cancellation is currently disabled', 'advanced-travel-crm'));
        }
        
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found', 'advanced-travel-crm'));
        }
        
        // Check status
        if ($booking['status'] !== 'pending' && $booking['status'] !== 'confirmed') {
            return new WP_Error('invalid_status', __('Only pending/confirmed bookings can be cancelled', 'advanced-travel-crm'));
        }
        
        // Check time window
        if (ATC_Feature_Manager::is_enabled('cancellation_window')) {
            $window_hours = intval(get_option('atc_cancellation_window', 24));
            
            if (!empty($booking['travel_date'])) {
                $travel_timestamp = strtotime($booking['travel_date']);
                $now = current_time('timestamp');
                $hours_until_travel = ($travel_timestamp - $now) / 3600;
                
                if ($hours_until_travel < $window_hours) {
                    return new WP_Error('too_late', sprintf(
                        __('Cancellation must be requested at least %d hours before travel date', 'advanced-travel-crm'),
                        $window_hours
                    ));
                }
            }
        }
        
        return true;
    }
    
    /**
     * Request cancellation
     */
    public static function request_cancellation($booking_id, $reason = '') {
        $can_cancel = self::can_cancel($booking_id);
        
        if (is_wp_error($can_cancel)) {
            return $can_cancel;
        }
        
        global $wpdb;
        
        // Check if approval required
        $requires_approval = ATC_Feature_Manager::is_enabled('cancellation_approval');
        
        if ($requires_approval) {
            // Update to pending cancellation
            $wpdb->update(
                ATC_TABLE_BOOKINGS,
                [
                    'status' => 'cancellation_pending',
                    'notes' => $wpdb->get_var($wpdb->prepare("SELECT notes FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d", $booking_id)) 
                               . "\n\n" . current_time('mysql') . " - Cancellation requested by customer\nReason: " . $reason,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $booking_id]
            );
            
            // Notify admins
            do_action('atc_cancellation_requested', $booking_id, $reason);
            
            return true;
        } else {
            // Auto-approve
            return self::process_cancellation($booking_id, $reason);
        }
    }
    
    /**
     * Process cancellation (admin approved or auto)
     */
    public static function process_cancellation($booking_id, $reason = '') {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found', 'advanced-travel-crm'));
        }
        
        // Update booking
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            [
                'status' => 'cancelled',
                'notes' => ($booking['notes'] ?? '') . "\n\n" . current_time('mysql') . " - Booking cancelled\nReason: " . $reason,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $booking_id]
        );
        
        // Process refund if enabled
        if (ATC_Feature_Manager::is_enabled('process_refunds') && $booking['payment_status'] === 'paid') {
            self::process_refund($booking_id, $booking);
        }
        
        // Trigger notification
        do_action('atc_booking_cancelled', $booking_id, $booking);
        
        ATC_Logger::log('booking', 'Booking cancelled: ' . $booking['booking_id']);
        
        return true;
    }
    
    /**
     * Process refund
     */
    private static function process_refund($booking_id, $booking) {
        $refund_type = get_option('atc_refund_policy', 'full');
        $refund_amount = $booking['price_total'];
        
        if ($refund_type === 'partial') {
            $refund_percent = floatval(get_option('atc_refund_percent', 80));
            $refund_amount = ($booking['price_total'] * $refund_percent) / 100;
        } elseif ($refund_type === 'none') {
            return; // No refund
        }
        
        // Deduct cancellation fee if any
        $cancellation_fee = floatval(get_option('atc_cancellation_fee', 0));
        if ($cancellation_fee > 0) {
            $refund_amount -= $cancellation_fee;
        }
        
        // Log refund (actual processing would be done via payment gateway)
        global $wpdb;
        $wpdb->insert(ATC_TABLE_PAYMENTS, [
            'booking_id' => $booking_id,
            'transaction_id' => 'REFUND-' . time(),
            'payment_method' => 'refund',
            'gateway' => $booking['payment_method'],
            'amount' => -abs($refund_amount),
            'currency' => $booking['currency'],
            'status' => 'refunded',
            'created_at' => current_time('mysql')
        ]);
        
        ATC_Logger::log('payment', 'Refund processed: ' . $booking['booking_id'] . ' - ' . $refund_amount);
    }
    
    /**
     * Admin menu
     */
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Cancellation Requests', 'advanced-travel-crm'),
            __('Cancellations', 'advanced-travel-crm'),
            'manage_atc',
            'atc-cancellations',
            [__CLASS__, 'cancellations_page']
        );
        
        add_submenu_page(
            'atc-settings',
            __('Cancellation Settings', 'advanced-travel-crm'),
            __('Cancellation', 'advanced-travel-crm'),
            'manage_options',
            'atc-cancellation-settings',
            [__CLASS__, 'settings_page']
        );
    }
    
    /**
     * Cancellations page
     */
    public static function cancellations_page() {
        global $wpdb;
        
        $pending = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE status = 'cancellation_pending' 
            ORDER BY updated_at DESC",
            ARRAY_A
        );
        
        ?>
        <div class="wrap">
            <h1>❌ Cancellation Requests</h1>
            
            <?php if (empty($pending)): ?>
                <div class="notice notice-info">
                    <p>No pending cancellation requests.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Travel Date</th>
                            <th>Amount</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $booking): ?>
                        <tr>
                            <td><strong><?php echo esc_html($booking['booking_id']); ?></strong></td>
                            <td><?php echo esc_html($booking['customer_name']); ?></td>
                            <td><?php echo esc_html(ucfirst($booking['service'])); ?></td>
                            <td><?php echo esc_html($booking['travel_date'] ?? '-'); ?></td>
                            <td><?php echo esc_html($booking['currency'] . ' ' . number_format($booking['price_total'], 2)); ?></td>
                            <td><?php echo human_time_diff(strtotime($booking['updated_at']), current_time('timestamp')) . ' ago'; ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=atc_approve_cancellation&booking_id=' . $booking['id']), 'atc_approve_cancel'); ?>" 
                                   class="button button-primary button-small">✓ Approve</a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=atc_reject_cancellation&booking_id=' . $booking['id']), 'atc_reject_cancel'); ?>" 
                                   class="button button-small">✗ Reject</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1>❌ Cancellation Settings</h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('atc_save_cancellation_settings'); ?>
                <input type="hidden" name="action" value="atc_save_cancellation_settings">
                
                <table class="form-table">
                    <tr>
                        <th>Cancellation Window</th>
                        <td>
                            <input type="number" name="cancellation_window" value="<?php echo esc_attr(get_option('atc_cancellation_window', 24)); ?>" min="0" max="168"> hours
                            <p class="description">Customers can cancel up to X hours before travel date</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Refund Policy</th>
                        <td>
                            <select name="refund_policy">
                                <option value="full" <?php selected(get_option('atc_refund_policy', 'full'), 'full'); ?>>Full Refund</option>
                                <option value="partial" <?php selected(get_option('atc_refund_policy'), 'partial'); ?>>Partial Refund</option>
                                <option value="none" <?php selected(get_option('atc_refund_policy'), 'none'); ?>>No Refund</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Partial Refund Percentage</th>
                        <td>
                            <input type="number" name="refund_percent" value="<?php echo esc_attr(get_option('atc_refund_percent', 80)); ?>" min="0" max="100"> %
                        </td>
                    </tr>
                    <tr>
                        <th>Cancellation Fee</th>
                        <td>
                            <input type="number" name="cancellation_fee" value="<?php echo esc_attr(get_option('atc_cancellation_fee', 0)); ?>" min="0" step="0.01">
                            <p class="description">Fixed fee deducted from refund amount</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Settings</button>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    public static function save_settings() {
        check_admin_referer('atc_save_cancellation_settings');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        update_option('atc_cancellation_window', intval($_POST['cancellation_window']));
        update_option('atc_refund_policy', sanitize_text_field($_POST['refund_policy']));
        update_option('atc_refund_percent', floatval($_POST['refund_percent']));
        update_option('atc_cancellation_fee', floatval($_POST['cancellation_fee']));
        
        wp_redirect(admin_url('admin.php?page=atc-cancellation-settings&saved=1'));
        exit;
    }
    
    /**
     * Approve cancellation
     */
    public static function approve_cancellation() {
        check_admin_referer('atc_approve_cancel');
        
        $booking_id = intval($_GET['booking_id']);
        self::process_cancellation($booking_id, 'Approved by admin');
        
        wp_redirect(admin_url('admin.php?page=atc-cancellations'));
        exit;
    }
    
    /**
     * Reject cancellation
     */
    public static function reject_cancellation() {
        check_admin_referer('atc_reject_cancel');
        
        $booking_id = intval($_GET['booking_id']);
        
        global $wpdb;
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            ['status' => 'confirmed', 'updated_at' => current_time('mysql')],
            ['id' => $booking_id]
        );
        
        wp_redirect(admin_url('admin.php?page=atc-cancellations'));
        exit;
    }
}
