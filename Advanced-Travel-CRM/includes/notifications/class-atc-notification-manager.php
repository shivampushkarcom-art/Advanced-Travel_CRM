<?php
/**
 * ATC Notification Manager
 * Enterprise Multi-Admin Notification System
 * 
 * Features:
 * - Multi-admin support (unlimited admins)
 * - WhatsApp: Semi-auto (dashboard buttons with pre-filled messages)
 * - Email: Full-auto (SMTP with queue)
 * - Per-admin configuration
 * - Multi-step customer notifications
 * - Notification queue & history
 * - Event-based triggers
 */

if (!defined('ABSPATH')) exit;

class ATC_Notification_Manager {
    
    public static function init() {
        // Hook into booking/lead/query events
        add_action('atc_booking_created', [__CLASS__, 'on_booking_created'], 10, 2);
        add_action('atc_lead_created', [__CLASS__, 'on_lead_created'], 10, 2);
        add_action('atc_query_submitted', [__CLASS__, 'on_query_submitted'], 10, 2);
        add_action('atc_booking_cancelled', [__CLASS__, 'on_booking_cancelled'], 10, 2);
        add_action('atc_booking_reminder', [__CLASS__, 'send_booking_reminder'], 10, 1);
        add_action('atc_booking_status_changed', [__CLASS__, 'on_booking_status_changed'], 10, 4);
        
        // Admin menu
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        
        // AJAX handlers
        add_action('wp_ajax_atc_test_notification', [__CLASS__, 'ajax_test_notification']);
        add_action('wp_ajax_atc_resend_notification', [__CLASS__, 'ajax_resend_notification']);
        add_action('wp_ajax_atc_save_admin_recipient', [__CLASS__, 'ajax_save_admin_recipient']);
        add_action('wp_ajax_atc_delete_admin_recipient', [__CLASS__, 'ajax_delete_admin_recipient']);
        add_action('wp_ajax_atc_get_recipient', [__CLASS__, 'ajax_get_recipient']);
        add_action('wp_ajax_atc_send_booking_notification', [__CLASS__, 'ajax_send_booking_notification']);
        
        // Scheduled events
        add_action('atc_daily_notifications', [__CLASS__, 'send_daily_summary']);
    }
    
    /**
     * Add admin menu
     */
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Notification History', 'advanced-travel-crm'),
            __('Notifications', 'advanced-travel-crm'),
            'manage_atc',
            'atc-notifications',
            [__CLASS__, 'notifications_page']
        );
        
        add_submenu_page(
            'atc-dashboard',
            __('Admin Recipients', 'advanced-travel-crm'),
            __('Admin Recipients', 'advanced-travel-crm'),
            'manage_options',
            'atc-admin-recipients',
            [__CLASS__, 'admin_recipients_page']
        );
    }
    
    /**
     * Event: New Booking Created
     */
    public static function on_booking_created($booking_id, $booking_data) {
        // 1. Admin Notifications (WhatsApp + Email)
        self::send_admin_notifications('new_booking', $booking_id, $booking_data, 'whatsapp');
        self::send_admin_notifications('new_booking', $booking_id, $booking_data, 'email');
        
        // 2. Customer Notifications (WhatsApp + Email)
        // Send "Initiated" instead of "Confirmed"
        self::send_customer_notification('booking_initiated', $booking_id, $booking_data, 'whatsapp');
        self::send_customer_notification('booking_initiated', $booking_id, $booking_data, 'email');
    }
    
    /**
     * Event: New Lead Created
     */
    public static function on_lead_created($lead_id, $lead_data) {
        $score = intval($lead_data['score'] ?? 0);
        $temperature = $lead_data['temperature'] ?? 'cold';
        
        // Only notify for hot/warm leads
        if ($score < 50) return;
        
        $event_type = ($temperature === 'hot') ? 'hot_lead' : 'warm_lead';
        
        // AUTO: Send email alerts to admins
        self::send_admin_notifications($event_type, $lead_id, $lead_data, 'email', 'lead');
        
        // SEMI-AUTO: WhatsApp button in leads dashboard
        self::log_notification([
            'lead_id' => $lead_id,
            'type' => $event_type,
            'channel' => 'whatsapp',
            'recipient' => 'admin',
            'status' => 'pending_manual',
            'payload' => json_encode($lead_data)
        ]);
    }
    
    /**
     * Event: New Query Submitted
     */
    public static function on_query_submitted($query_id, $query_data) {
        // Log that we received the event
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('info', 'Query notification triggered for ID: ' . $query_id);
        }
        
        // Ensure we have required fields for notifications
        $query_data['query_id'] = $query_id;
        $query_data['id'] = $query_id;
        
        // Map fields for compatibility
        if (!isset($query_data['customer_name']) && isset($query_data['name'])) {
            $query_data['customer_name'] = $query_data['name'];
        }
        if (!isset($query_data['customer_email']) && isset($query_data['email'])) {
            $query_data['customer_email'] = $query_data['email'];
        }
        if (!isset($query_data['customer_phone']) && isset($query_data['phone'])) {
            $query_data['customer_phone'] = $query_data['phone'];
        }
        
        // Send emails to all configured admins
        $email_result = self::send_admin_notifications('new_query', $query_id, $query_data, 'email', 'query');
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('info', 'Query email notification result: ' . ($email_result ? 'sent' : 'failed'));
        }
        
        // Send WhatsApp notifications to admin
        $whatsapp_result = self::send_admin_notifications('new_query', $query_id, $query_data, 'whatsapp', 'query');
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('info', 'Query WhatsApp notification result: ' . ($whatsapp_result ? 'sent' : 'failed'));
        }
    }
    
    /**
     * Event: Booking Cancelled
     */
    public static function on_booking_cancelled($booking_id, $booking_data) {
        // Send both WhatsApp and Email
        self::send_admin_notifications('booking_cancelled', $booking_id, $booking_data, 'whatsapp');
        self::send_customer_notification('booking_cancelled', $booking_id, $booking_data, 'whatsapp');
        
        self::send_admin_notifications('booking_cancelled', $booking_id, $booking_data, 'email');
        self::send_customer_notification('booking_cancelled', $booking_id, $booking_data, 'email');
    }
    
    /**
     * Event: Booking Status Changed
     */
    public static function on_booking_status_changed($booking_id, $old_status, $new_status, $booking_data) {
        // Map status to event type
        $status_events = [
            'confirmed' => 'booking_confirmed',
            'pending' => 'booking_pending',
            'cancelled' => 'booking_cancelled',
            'completed' => 'booking_completed',
            'refunded' => 'booking_refunded',
        ];
        
        $event_type = $status_events[$new_status] ?? 'booking_status_changed';
        
        // 1. WhatsApp to Customer
        self::send_customer_notification_via_template($event_type, $booking_id, array_merge($booking_data, [
            'old_status' => $old_status,
            'new_status' => $new_status,
            'status' => $new_status,
        ]), $new_status);
        
        // 2. Email to Customer (ALWAYS send, no fallback check)
        $template_type = $status_events[$new_status] ?? 'booking_status_changed';
        self::send_customer_notification($template_type, $booking_id, array_merge($booking_data, [
            'old_status' => $old_status,
            'new_status' => $new_status,
            'status' => $new_status,
        ]), 'email');
        
        // 3. Admin Notifications (Status Change - using specific status event)
        self::send_admin_notifications($event_type, $booking_id, array_merge($booking_data, [
            'old_status' => $old_status,
            'new_status' => $new_status,
        ]), 'whatsapp');
        
        self::send_admin_notifications($event_type, $booking_id, array_merge($booking_data, [
            'old_status' => $old_status,
            'new_status' => $new_status,
        ]), 'email');
    }
    
    /**
     * Send customer notification via new WhatsApp template system
     */
    private static function send_customer_notification_via_template($event_type, $booking_id, $data, $booking_status = null) {
        if (!class_exists('ATC_WhatsApp_Templates')) {
            if (defined('ATC_INCLUDES_DIR')) {
                require_once ATC_INCLUDES_DIR . 'notifications/class-atc-whatsapp-templates.php';
            } elseif (defined('ATC_PLUGIN_DIR')) {
                require_once ATC_PLUGIN_DIR . 'includes/notifications/class-atc-whatsapp-templates.php';
            }
        }

        if (!class_exists('ATC_WhatsApp_Templates')) {
            return false;
        }
        
        // Get template from new system
        $template = ATC_WhatsApp_Templates::get_template($event_type, $booking_status, 'customer');
        
        if (!$template || empty($template['message_body'])) {
            return false;
        }
        
        $customer_phone = $data['customer_phone'] ?? '';
        if (empty($customer_phone)) {
            // Try to get phone from user meta if we have user_id
            if (!empty($data['user_id'])) {
                $customer_phone = get_user_meta($data['user_id'], 'atc_phone', true);
            }
        }
        
        if (empty($customer_phone)) {
            return false; // No phone number
        }
        
        // Replace variables
        $message = self::replace_variables($template['message_body'], $data);
        
        // Send via WhatsApp API
        if (class_exists('ATC_WhatsApp_Sender')) {
            $result = ATC_WhatsApp_Sender::send_message($message, $customer_phone);
            
            self::log_notification([
                'booking_id' => $booking_id,
                'type' => $event_type,
                'channel' => 'whatsapp',
                'recipient' => $customer_phone,
                'status' => $result ? 'sent' : 'failed',
                'payload' => json_encode($data),
                'sent_at' => $result ? current_time('mysql') : null
            ]);
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Event: Booking Reminder (24 hours before trip)
     */
    public static function send_booking_reminder($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) return;
        
        // AUTO: Send reminder email to customer
        self::send_customer_notification('booking_reminder', $booking_id, $booking, 'email');
    }
    
    /**
     * Send notifications to all admin recipients
     * Returns true if at least one notification was sent successfully
     */
    private static function send_admin_notifications($event_type, $record_id, $data, $channel = 'whatsapp', $record_type = 'booking') {
        global $wpdb;
        
        $success_count = 0;
        
        // Get all active admin recipients
        $admins = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_ADMIN_RECIPIENTS . " 
            WHERE active = 1",
            ARRAY_A
        );
        
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('info', "Admin recipients found: " . count($admins) . " for channel: $channel");
        }
        
        // Fallback: If no admin recipients configured, send to default admin
        if (empty($admins)) {
            $admin_email = get_option('atc_admin_email', get_option('admin_email'));
            $admin_phone = get_option('atc_whatsapp_admin_phone', '');
            
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('info', "Fallback admin - Email: $admin_email, Phone: $admin_phone");
            }
            
            if ($channel === 'email' && !empty($admin_email) && is_email($admin_email)) {
                $admins = [[
                    'name' => 'Admin',
                    'email' => $admin_email,
                    'email_enabled' => 1,
                    'whatsapp_enabled' => 0,
                    'notify_events' => json_encode(['new_booking', 'new_query', 'hot_lead', 'warm_lead']),
                ]];
            } elseif ($channel === 'whatsapp' && !empty($admin_phone)) {
                $admins = [[
                    'name' => 'Admin',
                    'phone' => $admin_phone,
                    'email_enabled' => 0,
                    'whatsapp_enabled' => 1,
                    'notify_events' => json_encode(['new_booking', 'new_query', 'hot_lead', 'warm_lead']),
                ]];
            } else {
                if (class_exists('ATC_Logger')) {
                    ATC_Logger::log('warning', "No admin recipient available for $channel - Email: '$admin_email', Phone: '$admin_phone'");
                }
                return false; // No valid recipient
            }
        }
        
        foreach ($admins as $admin) {
            // Check if this channel is enabled for this admin
            if ($channel === 'email' && !$admin['email_enabled']) {
                if (class_exists('ATC_Logger')) {
                    ATC_Logger::log('info', "Email disabled for admin: " . $admin['name']);
                }
                continue;
            }
            if ($channel === 'whatsapp' && !$admin['whatsapp_enabled']) {
                if (class_exists('ATC_Logger')) {
                    ATC_Logger::log('info', "WhatsApp disabled for admin: " . $admin['name']);
                }
                continue;
            }
            
            // Check if this admin wants this event type
            $notify_events = json_decode($admin['notify_events'], true) ?: [];
            if (!empty($notify_events) && !in_array($event_type, $notify_events)) {
                if (class_exists('ATC_Logger')) {
                    ATC_Logger::log('info', "Event $event_type not in notify_events for admin: " . $admin['name']);
                }
                continue;
            }
            
            // Send notification
            if ($channel === 'email') {
                $result = self::send_admin_email($admin, $event_type, $record_id, $data);
                if (class_exists('ATC_Logger')) {
                    ATC_Logger::log('info', "Email send to " . $admin['email'] . " result: " . ($result ? 'success' : 'failed'));
                }
                if ($result) $success_count++;
            } elseif ($channel === 'whatsapp') {
                $result = self::send_admin_whatsapp($admin, $event_type, $record_id, $data, $record_type);
                if (class_exists('ATC_Logger')) {
                    ATC_Logger::log('info', "WhatsApp send to " . ($admin['phone'] ?? 'no-phone') . " result: " . ($result ? 'success' : 'failed'));
                }
                if ($result) $success_count++;
            }
        }
        
        return $success_count > 0;
    }
    
    /**
     * Send WhatsApp to admin
     */
    private static function send_admin_whatsapp($admin, $event_type, $record_id, $data, $record_type = 'booking') {
        if (!class_exists('ATC_WhatsApp_Sender')) {
            require_once ATC_INCLUDES_DIR . 'notifications/class-atc-whatsapp-sender.php';
        }
        
        // Format WhatsApp message
        $message = self::format_whatsapp_message($event_type, $record_id, $data, $record_type);
        
        // Get admin phone
        $admin_phone = !empty($admin['phone']) ? $admin['phone'] : get_option('atc_whatsapp_admin_phone', '');
        
        if (empty($admin_phone)) {
            return false;
        }
        
        // Send via WhatsApp
        if (class_exists('ATC_WhatsApp_Sender')) {
            $result = ATC_WhatsApp_Sender::send_message($message, $admin_phone);
            
            // Log result
            $log_data = [
                'type' => $event_type,
                'channel' => 'whatsapp',
                'recipient' => $admin_phone,
                'status' => $result ? 'sent' : 'failed',
                'payload' => json_encode($data),
                'sent_at' => current_time('mysql')
            ];
            
            if ($record_type === 'booking') {
                $log_data['booking_id'] = $record_id;
            } elseif ($record_type === 'query') {
                $log_data['query_id'] = $record_id;
            } elseif ($record_type === 'lead') {
                $log_data['lead_id'] = $record_id;
            }
            
            self::log_notification($log_data);
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Format WhatsApp message
     */
    private static function format_whatsapp_message($event_type, $record_id, $data, $record_type = 'booking') {
        // Header
        $message = "🔔 *" . strtoupper(str_replace('_', ' ', $event_type)) . "*\n";
        $message .= "📅 " . date('d M Y, h:i A') . "\n";
        $message .= "──────────────────\n\n";
        
        if ($event_type === 'new_booking') {
            $message .= "*📋 Booking Details*\n";
            $message .= "• ID: *" . ($data['booking_id'] ?? $record_id) . "*\n";
            $message .= "• Name: " . ($data['customer_name'] ?? 'N/A') . "\n";
            $message .= "• Phone: " . ($data['customer_phone'] ?? 'N/A') . "\n";
            $message .= "• Email: " . ($data['customer_email'] ?? 'N/A') . "\n";
            $message .= "• Package: " . ($data['package_name'] ?? $data['service'] ?? 'Custom') . "\n";
            $message .= "• Service: " . ucfirst($data['service'] ?? 'N/A') . "\n";
            
            if (!empty($data['destination'])) {
                $message .= "• Dest: " . $data['destination'] . "\n";
            }
            if (!empty($data['travel_date'])) {
                $message .= "• Travel: " . date('d M Y', strtotime($data['travel_date'])) . "\n";
            }
            if (!empty($data['adults']) || !empty($data['children'])) {
                $message .= "• Pax: " . ($data['adults']??0) . " Ad, " . ($data['children']??0) . " Ch\n";
            }
            if (!empty($data['price_total'])) {
                $message .= "• Amount: *₹" . number_format(floatval($data['price_total']), 2) . "*\n";
            }
            
            // Special Requests / Form Data
            $special_req = $data['special_requirements'] ?? ($data['form_data']['special_requests'] ?? '');
            if (!empty($special_req)) {
                $message .= "\n*📝 Requests:*\n" . trim(strip_tags($special_req)) . "\n";
            }
            
        } elseif ($event_type === 'new_query') {
            $message .= "*💬 Query Details*\n";
            $message .= "• ID: *" . ($data['query_id'] ?? $record_id) . "*\n";
            $message .= "• Name: " . ($data['customer_name'] ?? 'N/A') . "\n";
            $message .= "• Phone: " . ($data['customer_phone'] ?? $data['phone'] ?? 'N/A') . "\n";
            $message .= "• Email: " . ($data['customer_email'] ?? $data['email'] ?? 'N/A') . "\n";
            $message .= "• Package: " . ($data['package_name'] ?? $data['package'] ?? 'Custom') . "\n";
            
            // Intelligence: Full Form Data
            if (!empty($data['destination'])) {
                $message .= "• Dest: " . $data['destination'] . "\n";
            }
            if (!empty($data['travel_date'])) {
                $message .= "• Travel: " . $data['travel_date'] . "\n";
            }
            if (!empty($data['adults']) || !empty($data['children'])) {
                $message .= "• Pax: " . ($data['adults']??0) . " Ad, " . ($data['children']??0) . " Ch\n";
            }
            if (!empty($data['trip_type'])) {
                $message .= "• Type: " . ucfirst($data['trip_type']) . "\n";
            }
            if (!empty($data['budget_min']) || !empty($data['budget'])) {
                $message .= "• Budget: " . ($data['budget'] ?? ($data['budget_min'] . '-' . $data['budget_max'])) . "\n";
            }
            
            // Requirements & Message
            if (!empty($data['special_requirements'])) {
               $message .= "\n*📝 Requirements:*\n" . trim(strip_tags($data['special_requirements'])) . "\n";
            }
            
            if (!empty($data['message'])) {
                 $message .= "\n*💭 Message:*\n" . trim(strip_tags($data['message'])) . "\n";
            }
        }
        
        // NO Footer Link
        
        return $message;
    }
    
    /**
     * Send email to admin
     */
    private static function send_admin_email($admin, $event_type, $record_id, $data) {
        // Get template
        $template = self::get_email_template("admin_{$event_type}");
        if (!$template) return false;
        
        // Prepare data with admin info
        $email_data = array_merge($data, [
            'admin_name' => $admin['name'],
            'admin_email' => $admin['email'],
        ]);
        
        // Send via Email Sender class
        if (!class_exists('ATC_Email_Sender')) {
            if (defined('ATC_INCLUDES_DIR')) {
                require_once ATC_INCLUDES_DIR . 'notifications/class-atc-email-sender.php';
            } elseif (defined('ATC_PLUGIN_DIR')) {
                require_once ATC_PLUGIN_DIR . 'includes/notifications/class-atc-email-sender.php';
            }
        }

        if (class_exists('ATC_Email_Sender')) {
            $result = ATC_Email_Sender::send($admin['email'], $template, $email_data);
            
            // Log result
            self::log_notification([
                'booking_id' => $record_id,
                'type' => $event_type,
                'channel' => 'email',
                'recipient' => $admin['email'],
                'status' => $result ? 'sent' : 'failed',
                'payload' => json_encode($email_data),
                'sent_at' => current_time('mysql')
            ]);
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Send notification to customer
     */
    private static function send_customer_notification($template_type, $booking_id, $data, $channel = 'whatsapp') {
        // PRIMARY: WhatsApp (default)
        if ($channel === 'whatsapp') {
            // Try new template system first
            if (class_exists('ATC_WhatsApp_Templates')) {
                $event_type = str_replace('customer_', '', $template_type);
                $booking_status = $data['status'] ?? $data['new_status'] ?? null;
                $result = self::send_customer_notification_via_template($event_type, $booking_id, $data, $booking_status);
                if ($result !== false) {
                    return $result;
                }
            }
            
            // Fallback to old system
            $customer_phone = $data['customer_phone'] ?? '';
            if (empty($customer_phone)) {
                // Try to get phone from user meta if we have user_id
                if (!empty($data['user_id'])) {
                    $customer_phone = get_user_meta($data['user_id'], 'atc_phone', true);
                }
            }
            
            if (empty($customer_phone)) {
                return false; // No phone number, can't send WhatsApp
            }
            
            // Get WhatsApp template
            $template = self::get_whatsapp_template("customer_{$template_type}");
            if (empty($template)) {
                // Fallback to default template
                $template = self::get_default_whatsapp_template($template_type);
            }
            
            if (empty($template)) {
                return false; // No template available
            }
            
            // Replace variables
            $message = self::replace_variables($template, $data);
            
            // Send via WhatsApp API
            if (!class_exists('ATC_WhatsApp_Sender')) {
                if (defined('ATC_INCLUDES_DIR')) {
                    require_once ATC_INCLUDES_DIR . 'notifications/class-atc-whatsapp-sender.php';
                } elseif (defined('ATC_PLUGIN_DIR')) {
                    require_once ATC_PLUGIN_DIR . 'includes/notifications/class-atc-whatsapp-sender.php';
                }
            }

            if (class_exists('ATC_WhatsApp_Sender')) {
                $result = ATC_WhatsApp_Sender::send_message($message, $customer_phone);
                
                self::log_notification([
                    'booking_id' => $booking_id,
                    'type' => $template_type,
                    'channel' => 'whatsapp',
                    'recipient' => $customer_phone,
                    'status' => $result ? 'sent' : 'failed',
                    'payload' => json_encode($data),
                    'sent_at' => $result ? current_time('mysql') : null
                ]);
                
                return $result;
            }
            
            return false;
        }
        
        // SECONDARY: Email (fallback)
        if ($channel === 'email') {
            $customer_email = $data['customer_email'] ?? '';
            if (empty($customer_email)) return false;
            
            $template = self::get_email_template("customer_{$template_type}");
            if (!$template) return false;
            
            if (!class_exists('ATC_Email_Sender')) {
                if (defined('ATC_INCLUDES_DIR')) {
                    require_once ATC_INCLUDES_DIR . 'notifications/class-atc-email-sender.php';
                } elseif (defined('ATC_PLUGIN_DIR')) {
                    require_once ATC_PLUGIN_DIR . 'includes/notifications/class-atc-email-sender.php';
                }
            }

            if (class_exists('ATC_Email_Sender')) {
                $result = ATC_Email_Sender::send($customer_email, $template, $data);
                
                self::log_notification([
                    'booking_id' => $booking_id,
                    'type' => $template_type,
                    'channel' => 'email',
                    'recipient' => $customer_email,
                    'status' => $result ? 'sent' : 'failed',
                    'payload' => json_encode($data),
                    'sent_at' => current_time('mysql')
                ]);
                
                return $result;
            }
        }
        
        return false;
    }
    
    /**
     * Get WhatsApp button HTML (SEMI-AUTO)
     */
    public static function get_whatsapp_button($record_id, $record_type = 'booking', $recipient_type = 'admin') {
        global $wpdb;
        
        // Get record data
        if ($record_type === 'booking') {
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
                $record_id
            ), ARRAY_A);
        } else {
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_LEADS . " WHERE id = %d",
                $record_id
            ), ARRAY_A);
        }
        
        if (!$record) return '';
        
        // Get WhatsApp template
        if ($recipient_type === 'admin') {
            $template_slug = "admin_new_{$record_type}";
            $phone = get_option('atc_whatsapp_admin_phone', '');
        } else {
            $template_slug = "customer_{$record_type}_confirmation";
            $phone = $record['customer_phone'] ?? $record['phone'] ?? '';
        }
        
        $template = self::get_whatsapp_template($template_slug);
        if (!$template || empty($phone)) return '';
        
        // Replace variables
        $message = self::replace_variables($template, $record);
        
        // Clean phone
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Generate WhatsApp URL
        $wa_url = 'https://wa.me/' . $phone . '?text=' . urlencode($message);
        
        // Button HTML
        $label = $recipient_type === 'admin' 
            ? __('📱 WhatsApp Admin', 'advanced-travel-crm') 
            : __('📱 WhatsApp Customer', 'advanced-travel-crm');
        
        return sprintf(
            '<a href="%s" target="_blank" class="button button-primary atc-whatsapp-btn" onclick="atcMarkWhatsAppSent(%d, \'%s\', \'%s\')">%s</a>',
            esc_url($wa_url),
            $record_id,
            $record_type,
            $recipient_type,
            $label
        );
    }
    
    /**
     * Get multiple WhatsApp buttons (for both admin and customer)
     */
    public static function get_whatsapp_buttons($record_id, $record_type = 'booking') {
        $buttons = [];
        
        $admin_btn = self::get_whatsapp_button($record_id, $record_type, 'admin');
        if ($admin_btn) $buttons[] = $admin_btn;
        
        $customer_btn = self::get_whatsapp_button($record_id, $record_type, 'customer');
        if ($customer_btn) $buttons[] = $customer_btn;
        
        return implode(' ', $buttons);
    }
    
    /**
     * Get email template
     */
    private static function get_email_template($slug) {
        global $wpdb;
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_EMAIL_TEMPLATES . " 
            WHERE slug = %s AND active = 1",
            $slug
        ), ARRAY_A);
        
        // If template not found in database, use fallback default templates
        if (!$template) {
            $template = self::get_default_template($slug);
        }
        
        return $template;
    }
    
    /**
     * Get default email template (fallback)
     */
    private static function get_default_template($slug) {
        $defaults = [
            'admin_new_booking' => [
                'subject' => '🎫 New Booking #{booking_id} - {customer_name}',
                'body' => "Hi there,\n\nGreat news! A new booking has been received.\n\n📋 Booking Details:\nBooking ID: {booking_id}\nPackage: {package_name}\nService: {service_name}\n\n--- Customer Info ---\nName: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\n\n--- Trip Details ---\nDestination: {destination}\nTravel Date: {travel_date}\nTravelers: {adults} Adults, {children} Children\n\n💰 Amount: {currency} {price_total}\nStatus: Pending\n\n--- Special Requests ---\n{message}\n\nBest regards,\n{company_name} Team",
            ],
            'admin_new_query' => [
                'subject' => 'New Package Query Received - {query_id}',
                'body' => "Hello {admin_name},\n\nA new package query has been received:\n\nQuery ID: {query_id}\nCustomer: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\nPackage: {package_name}\n\n--- Trip Details ---\nDestination: {destination}\nTravel Date: {travel_date}\nTravelers: {adults} Adults, {children} Children\nTrip Type: {trip_type}\nBudget: {budget}\n\n--- Requirements ---\n{query_message}\n\nBest regards,\n{company_name}",
            ],
            'admin_hot_lead' => [
                'subject' => '🔥 Hot Lead: {customer_name} - {destination}',
                'body' => "🔥 HIGH PRIORITY LEAD\n\nName: {customer_name}\nPhone: {customer_phone}\nEmail: {customer_email}\nScore: {score}/100\n\n--- Trip Details ---\nService: {service_name}\nDestination: {destination}\nTravel Date: {travel_date}\nTravelers: {adults} Adults, {children} Children\nTrip Type: {trip_type}\nBudget: {budget}\n\n--- Requirements ---\n{message}\n\n👉 Follow up immediately!",
            ],
            'admin_warm_lead' => [
                'subject' => 'New Lead: {customer_name} - {destination}',
                'body' => "New Lead Received\n\nName: {customer_name}\nPhone: {customer_phone}\nEmail: {customer_email}\nScore: {score}/100\n\n--- Trip Details ---\nService: {service_name}\nDestination: {destination}\nTravel Date: {travel_date}\nTravelers: {adults} Adults, {children} Children\nTrip Type: {trip_type}\nBudget: {budget}\n\n--- Requirements ---\n{message}\n\nPlease review soon.",
            ],
            'customer_query_confirmation' => [
                'subject' => 'Query Received - {company_name}',
                'body' => "Hello {customer_name},\n\nThank you for your query. We have received your request and will get back to you soon.\n\nQuery ID: {query_id}\nPackage: {package_name}\n\nIf you have any questions, please contact us at {support_email}.\n\nBest regards,\n{company_name}",
            ],
            'customer_booking_initiated' => [
                'subject' => 'Booking Received - {company_name}',
                'body' => "Hello {customer_name},\n\nWe have received your booking request (#{booking_id}).\n\nWe are currently reviewing the details and will confirm your booking shortly.\n\nBooking ID: {booking_id}\nPackage: {package_name}\nService: {service_name}\nAmount: {currency} {price_total}\n\nThank you for choosing {company_name}.",
            ],
        ];
        
        if (isset($defaults[$slug])) {
            return [
                'slug' => $slug,
                'subject' => $defaults[$slug]['subject'],
                'body' => $defaults[$slug]['body'],
                'active' => 1,
            ];
        }
        
        return null;
    }
    
    /**
     * Get WhatsApp template body
     */
    private static function get_whatsapp_template($slug) {
        // Use new WhatsApp templates system
        if (!class_exists('ATC_WhatsApp_Templates')) {
            if (defined('ATC_INCLUDES_DIR')) {
                require_once ATC_INCLUDES_DIR . 'notifications/class-atc-whatsapp-templates.php';
            } elseif (defined('ATC_PLUGIN_DIR')) {
                require_once ATC_PLUGIN_DIR . 'includes/notifications/class-atc-whatsapp-templates.php';
            }
        }

        if (class_exists('ATC_WhatsApp_Templates')) {
            // Map old slug format to new event type
            $event_type = str_replace('customer_', '', $slug);
            $event_type = str_replace('admin_', '', $event_type);
            
            // Try to get template from new system
            $template = ATC_WhatsApp_Templates::get_template($event_type, null, 'customer');
            if ($template && !empty($template['message_body'])) {
                return $template['message_body'];
            }
        }
        
        // Fallback to default template
        return self::get_default_whatsapp_template($slug);
    }
    
    /**
     * Get default WhatsApp template
     */
    private static function get_default_whatsapp_template($template_type) {
        // Remove 'customer_' prefix if present for lookup
        $key = str_replace('customer_', '', $template_type);
        
        $defaults = [
            'customer_booking_initiated' => "📝 *Booking Received*\n\nHello {customer_name},\n\nWe have received your booking (#{booking_id}) and are reviewing it.\n\n*📋 Details*\n• Package: {package_name}\n• Service: {service_name}\n• Travel: {travel_date}\n• Amount: {currency} {price_total}\n\nWe will notify you once confirmed.\n\n{company_name}",

            'customer_booking_confirmation' => "🎉 *Booking Confirmed!*\n\nHello {customer_name},\n\nYour booking has been successfully confirmed.\n\n*📋 Booking Details*\n• Ref: {booking_id}\n• Package: {package_name}\n• Service: {service_name}\n• Destination: {destination}\n• Travel: {travel_date}\n\n*💰 Amount:* {currency} {price_total}\n\nThank you for choosing {company_name}.\n\nNeed help? Contact us:\n📱 {support_phone}",
            
            'customer_booking_confirmed' => "✅ *Booking Confirmed!*\n\nHello {customer_name},\n\nBooking #{booking_id} is now confirmed.\n\n*📋 Details*\n• Package: {package_name}\n• Service: {service_name}\n• Destination: {destination}\n• Travel: {travel_date}\n• Amount: {currency} {price_total}\n\nWe look forward to serving you!\n\n{company_name}",
            
            'customer_booking_pending' => "⏳ *Booking Pending*\n\nHello {customer_name},\n\nYour booking #{booking_id} is currently pending confirmation. We will update you shortly.\n\nThank you for your patience.\n\n{company_name}",
            
            'customer_booking_cancelled' => "❌ *Booking Cancelled*\n\nHello {customer_name},\n\nYour booking #{booking_id} has been cancelled.\n\nIf you have questions, please contact our support team:\n📱 {support_phone}\n\n{company_name}",
            
            'customer_booking_completed' => "✅ *Trip Completed*\n\nHello {customer_name},\n\nYour booking #{booking_id} is marked as completed. We hope you had a wonderful trip!\n\nThank you for traveling with {company_name}.",
            
            'customer_booking_refunded' => "💰 *Refund Processed*\n\nHello {customer_name},\n\nRefund for booking #{booking_id} has been initiated.\n\n*Amount:* {currency} {price_total}\n\nPlease allow 5-7 business days for credit.\n\n{company_name}",
            
            'customer_booking_status_changed' => "📢 *Status Update*\n\nHello {customer_name},\n\nYour booking #{booking_id} status has been updated to: *{status}*.\n\n{company_name}",
            
            'customer_payment_receipt' => "💳 *Payment Received*\n\nHello {customer_name},\n\nPayment received for booking #{booking_id}.\n\n*Amount:* {currency} {price_total}\n\nThank you!\n\n{company_name}",
            
            'customer_query_confirmation' => "📝 *Query Received*\n\nHello {customer_name},\n\nWe have received your query (#{query_id}) and will assist you shortly.\n\nThank you for contacting {company_name}!",
        ];
        
        return $defaults[$template_type] ?? $defaults["customer_{$key}"] ?? '';
    }
    
    /**
     * Replace variables in template
     */
    private static function replace_variables($template, $data) {
        // Intelligence: Fetch real Package Name
        $package_name = $data['package_name'] ?? '';
        if (empty($package_name) && !empty($data['package_id'])) {
            $package_post = get_post($data['package_id']);
            if ($package_post) {
                $package_name = $package_post->post_title;
            }
        }
        if (empty($package_name) && !empty($data['service'])) {
            $package_name = ucfirst($data['service']) . ' Package';
        }

        $replacements = [
            '{booking_id}' => $data['booking_id'] ?? '',
            '{customer_name}' => $data['customer_name'] ?? $data['name'] ?? '',
            '{customer_email}' => $data['customer_email'] ?? $data['email'] ?? '',
            '{customer_phone}' => $data['customer_phone'] ?? $data['phone'] ?? '',
            '{service_name}' => ucfirst($data['service'] ?? ''),
            '{destination}' => $data['destination'] ?? '',
            '{price_total}' => number_format($data['price_total'] ?? 0, 2),
            '{currency}' => $data['currency'] ?? get_option('atc_currency', 'INR'),
            '{travel_date}' => $data['travel_date'] ?? '',
            '{return_date}' => $data['return_date'] ?? '',
            '{adults}' => $data['adults'] ?? '1',
            '{children}' => $data['children'] ?? '0',
            '{score}' => $data['score'] ?? '',
            '{budget_min}' => $data['budget_min'] ?? '',
            '{budget_max}' => $data['budget_max'] ?? '',
            '{date_from}' => $data['date_from'] ?? '',
            '{date_to}' => $data['date_to'] ?? '',
            '{company_name}' => get_bloginfo('name'),
            '{support_email}' => get_option('atc_admin_email', get_option('admin_email')),
            '{support_phone}' => get_option('atc_whatsapp_admin_phone', ''),
            '{booking_url}' => '', // Forced Empty
            '{lead_url}' => admin_url('admin.php?page=atc-leads&lead_id=' . ($data['id'] ?? '')),
            '{query_url}' => admin_url('admin.php?page=atc-package-queries&query_id=' . ($data['id'] ?? '')),
            '{query_id}' => $data['query_id'] ?? $data['id'] ?? '',
            '{query_message}' => $data['message'] ?? $data['query'] ?? '',
            '{message}' => $data['message'] ?? $data['query'] ?? '',
            '{budget}' => $data['budget'] ?? (($data['budget_min'] ?? '') . ' - ' . ($data['budget_max'] ?? '')),
            '{trip_type}' => ucfirst($data['trip_type'] ?? ''),
            '{package_name}' => $package_name,
            '{hotel_type}' => ucfirst($data['hotel_type'] ?? ''),
            '{child_ages}' => $data['child_ages'] ?? '',
            '{special_requests}' => $data['special_requirements'] ?? $data['special_requests'] ?? '',
        ];

        // Generic Fallback: Map any other scalar keys from data
        foreach ($data as $key => $value) {
            if (is_scalar($value) && !isset($replacements["{{$key}}"])) {
                $replacements["{{$key}}"] = $value;
            }
        }

        $output = strtr($template, $replacements);
        
        // Cleanup unwanted legacy labels if they exist in the structure (Optional cleanup)
        $labels_to_remove = ["Booking URL: ", "View Full Details:"];
        $output = str_replace($labels_to_remove, "", $output);
        
        return $output;
    }
    
    /**
     * Log notification
     */
    private static function log_notification($data) {
        global $wpdb;
        
        $defaults = [
            'booking_id' => null,
            'lead_id' => null,
            'query_id' => null,
            'type' => '',
            'channel' => '',
            'recipient' => '',
            'status' => 'pending',
            'payload' => '',
            'response' => '',
            'attempts' => 0,
            'created_at' => current_time('mysql'),
            'sent_at' => null
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        $wpdb->insert(ATC_TABLE_NOTIFICATIONS, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Send daily summary to admins
     */
    public static function send_daily_summary() {
        global $wpdb;
        
        // Get today's stats
        $today = date('Y-m-d');
        
        $stats = [
            'bookings' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " WHERE DATE(created_at) = %s",
                $today
            )),
            'revenue' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(price_total) FROM " . ATC_TABLE_BOOKINGS . " WHERE DATE(created_at) = %s AND payment_status = 'paid'",
                $today
            )),
            'leads' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " WHERE DATE(created_at) = %s",
                $today
            )),
            'hot_leads' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . ATC_TABLE_LEADS . " WHERE DATE(created_at) = %s AND temperature = 'hot'",
                $today
            ))
        ];
        
        // Send to admins who subscribed to daily summary
        self::send_admin_notifications('daily_summary', 0, $stats, 'email');
    }
    
    /**
     * Notifications History Page
     */
    public static function notifications_page() {
        global $wpdb;
        
        // Handle filters
        $channel_filter = isset($_GET['channel']) ? sanitize_text_field($_GET['channel']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $where = ['1=1'];
        if ($channel_filter) {
            $where[] = $wpdb->prepare("channel = %s", $channel_filter);
        }
        if ($status_filter) {
            $where[] = $wpdb->prepare("status = %s", $status_filter);
        }
        
        $notifications = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_NOTIFICATIONS . " 
            WHERE " . implode(' AND ', $where) . "
            ORDER BY created_at DESC LIMIT 100",
            ARRAY_A
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('📊 Notification History', 'advanced-travel-crm'); ?></h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="channel" onchange="location.href='<?php echo admin_url('admin.php?page=atc-notifications'); ?>&channel=' + this.value">
                        <option value=""><?php _e('All Channels', 'advanced-travel-crm'); ?></option>
                        <option value="email" <?php selected($channel_filter, 'email'); ?>><?php _e('Email', 'advanced-travel-crm'); ?></option>
                        <option value="whatsapp" <?php selected($channel_filter, 'whatsapp'); ?>><?php _e('WhatsApp', 'advanced-travel-crm'); ?></option>
                        <option value="sms" <?php selected($channel_filter, 'sms'); ?>><?php _e('SMS', 'advanced-travel-crm'); ?></option>
                    </select>
                    
                    <select name="status" onchange="location.href='<?php echo admin_url('admin.php?page=atc-notifications'); ?>&status=' + this.value">
                        <option value=""><?php _e('All Statuses', 'advanced-travel-crm'); ?></option>
                        <option value="sent" <?php selected($status_filter, 'sent'); ?>><?php _e('Sent', 'advanced-travel-crm'); ?></option>
                        <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'advanced-travel-crm'); ?></option>
                        <option value="pending_manual" <?php selected($status_filter, 'pending_manual'); ?>><?php _e('Pending Manual', 'advanced-travel-crm'); ?></option>
                    </select>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date/Time', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Type', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Channel', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Recipient', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Actions', 'advanced-travel-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="6"><?php _e('No notifications found.', 'advanced-travel-crm'); ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                    <tr>
                        <td><?php echo esc_html(date('M d, Y H:i', strtotime($n['created_at']))); ?></td>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $n['type']))); ?></td>
                        <td>
                            <?php
                            $icons = ['email' => '📧', 'whatsapp' => '📱', 'sms' => '💬'];
                            echo ($icons[$n['channel']] ?? '') . ' ' . esc_html(ucfirst($n['channel']));
                            ?>
                        </td>
                        <td><?php echo esc_html($n['recipient']); ?></td>
                        <td>
                            <?php
                            $status_labels = [
                                'sent' => '<span style="color: #10B981;">✅ Sent</span>',
                                'failed' => '<span style="color: #EF4444;">❌ Failed</span>',
                                'pending' => '<span style="color: #F59E0B;">⏳ Pending</span>',
                                'pending_manual' => '<span style="color: #3B82F6;">📱 Manual</span>',
                            ];
                            echo $status_labels[$n['status']] ?? esc_html($n['status']);
                            ?>
                        </td>
                        <td>
                            <?php if ($n['status'] === 'failed'): ?>
                            <button class="button button-small" onclick="atcResendNotification(<?php echo $n['id']; ?>)">
                                <?php _e('Retry', 'advanced-travel-crm'); ?>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <script>
        function atcResendNotification(id) {
            if (!confirm('<?php _e('Resend this notification?', 'advanced-travel-crm'); ?>')) return;
            
            jQuery.post(ajaxurl, {
                action: 'atc_resend_notification',
                notification_id: id,
                _wpnonce: '<?php echo wp_create_nonce('atc_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php _e('Notification resent!', 'advanced-travel-crm'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Failed to resend.', 'advanced-travel-crm'); ?>');
                }
            });
        }
        
        function atcMarkWhatsAppSent(recordId, recordType, recipientType) {
            // Mark WhatsApp as sent when button is clicked
            setTimeout(function() {
                jQuery.post(ajaxurl, {
                    action: 'atc_mark_whatsapp_sent',
                    record_id: recordId,
                    record_type: recordType,
                    recipient_type: recipientType,
                    _wpnonce: '<?php echo wp_create_nonce('atc_nonce'); ?>'
                });
            }, 1000);
        }
        </script>
        <?php
    }
    
    /**
     * Admin Recipients Management Page
     */
    public static function admin_recipients_page() {
        global $wpdb;
        
        // Handle delete
        if (isset($_GET['delete_recipient']) && check_admin_referer('delete_recipient_' . $_GET['delete_recipient'])) {
            $id = intval($_GET['delete_recipient']);
            $wpdb->delete(ATC_TABLE_ADMIN_RECIPIENTS, ['id' => $id], ['%d']);
            echo '<div class="notice notice-success"><p>' . __('Recipient deleted successfully!', 'advanced-travel-crm') . '</p></div>';
        }
        
        // Handle save
        if (isset($_POST['save_recipient'])) {
            check_admin_referer('atc_save_recipient');
            
            $id = isset($_POST['recipient_id']) ? intval($_POST['recipient_id']) : 0;
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'email' => sanitize_email($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
                'whatsapp_enabled' => isset($_POST['whatsapp_enabled']) ? 1 : 0,
                'whatsapp_mode' => sanitize_text_field($_POST['whatsapp_mode']),
                'notify_events' => json_encode($_POST['notify_events'] ?? []),
                'role' => sanitize_text_field($_POST['role']),
                'active' => isset($_POST['active']) ? 1 : 0,
            ];
            
            if ($id) {
                $wpdb->update(ATC_TABLE_ADMIN_RECIPIENTS, $data, ['id' => $id]);
                echo '<div class="notice notice-success"><p>' . __('Recipient updated!', 'advanced-travel-crm') . '</p></div>';
            } else {
                $wpdb->insert(ATC_TABLE_ADMIN_RECIPIENTS, $data);
                echo '<div class="notice notice-success"><p>' . __('Recipient added!', 'advanced-travel-crm') . '</p></div>';
            }
        }
        
        // Get all recipients
        $recipients = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_ADMIN_RECIPIENTS . " ORDER BY id DESC",
            ARRAY_A
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('👥 Admin Recipients', 'advanced-travel-crm'); ?></h1>
            <p><?php _e('Configure who receives notifications and how they receive them.', 'advanced-travel-crm'); ?></p>
            
            <button type="button" class="button button-primary" onclick="showAddRecipientForm()">
                <?php _e('+ Add New Recipient', 'advanced-travel-crm'); ?>
            </button>
            
            <br><br>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Contact', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Email', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('WhatsApp', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Role', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Actions', 'advanced-travel-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipients as $r): ?>
                    <tr>
                        <td><strong><?php echo esc_html($r['name']); ?></strong></td>
                        <td>
                            <?php echo esc_html($r['email']); ?><br>
                            <small><?php echo esc_html($r['phone']); ?></small>
                        </td>
                        <td>
                            <?php echo $r['email_enabled'] ? '<span style="color: #10B981;">✓ Enabled</span>' : '<span style="color: #9CA3AF;">✗ Disabled</span>'; ?>
                        </td>
                        <td>
                            <?php 
                            if ($r['whatsapp_enabled']) {
                                echo '<span style="color: #10B981;">✓ ' . ucfirst($r['whatsapp_mode']) . '</span>';
                            } else {
                                echo '<span style="color: #9CA3AF;">✗ Disabled</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(ucfirst($r['role'])); ?></td>
                        <td>
                            <?php echo $r['active'] ? '<span style="color: #10B981;">● Active</span>' : '<span style="color: #9CA3AF;">● Inactive</span>'; ?>
                        </td>
                        <td>
                            <a href="#" onclick="editRecipient(<?php echo $r['id']; ?>); return false;" class="button button-small">
                                <?php _e('Edit', 'advanced-travel-crm'); ?>
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=atc-admin-recipients&delete_recipient=' . $r['id']), 'delete_recipient_' . $r['id']); ?>" 
                               class="button button-small button-link-delete" 
                               onclick="return confirm('<?php _e('Are you sure you want to delete this recipient?', 'advanced-travel-crm'); ?>');">
                                <?php _e('Delete', 'advanced-travel-crm'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Add/Edit Form Modal -->
            <div id="recipientFormModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; padding: 20px;">
                <div style="max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; max-height: 90vh; overflow-y: auto;">
                    <h2><?php _e('Add/Edit Admin Recipient', 'advanced-travel-crm'); ?></h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('atc_save_recipient'); ?>
                        <input type="hidden" name="recipient_id" id="recipient_id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="name"><?php _e('Name', 'advanced-travel-crm'); ?> *</label></th>
                                <td><input type="text" name="name" id="name" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="email"><?php _e('Email', 'advanced-travel-crm'); ?> *</label></th>
                                <td><input type="email" name="email" id="email" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="phone"><?php _e('WhatsApp Number', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <input type="text" name="phone" id="phone" class="regular-text" placeholder="+919876543210">
                                    <p class="description"><?php _e('Include country code (e.g., +91)', 'advanced-travel-crm'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="role"><?php _e('Role', 'advanced-travel-crm'); ?></label></th>
                                <td>
                                    <select name="role" id="role" class="regular-text">
                                        <option value="super_admin"><?php _e('Super Admin', 'advanced-travel-crm'); ?></option>
                                        <option value="admin"><?php _e('Admin', 'advanced-travel-crm'); ?></option>
                                        <option value="manager"><?php _e('Manager', 'advanced-travel-crm'); ?></option>
                                        <option value="accountant"><?php _e('Accountant', 'advanced-travel-crm'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Email Notifications', 'advanced-travel-crm'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="email_enabled" id="email_enabled" value="1" checked>
                                        <?php _e('Enable Email Notifications', 'advanced-travel-crm'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('WhatsApp Notifications', 'advanced-travel-crm'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="whatsapp_enabled" id="whatsapp_enabled" value="1">
                                        <?php _e('Enable WhatsApp Notifications', 'advanced-travel-crm'); ?>
                                    </label>
                                    <br><br>
                                    <label>
                                        <input type="radio" name="whatsapp_mode" value="semi-auto" checked>
                                        <?php _e('Semi-Auto (Dashboard Button)', 'advanced-travel-crm'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="radio" name="whatsapp_mode" value="full-auto">
                                        <?php _e('Full-Auto (Automatic Sending)', 'advanced-travel-crm'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Notify On Events', 'advanced-travel-crm'); ?></th>
                                <td>
                                    <label><input type="checkbox" name="notify_events[]" value="new_booking" checked> <?php _e('New Booking', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="new_query" checked> <?php _e('New Query', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="payment_received" checked> <?php _e('Payment Received', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="hot_lead" checked> <?php _e('Hot Lead (Score > 75)', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="warm_lead"> <?php _e('Warm Lead (Score 50-74)', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="booking_cancelled"> <?php _e('Booking Cancelled', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="booking_confirmed"> <?php _e('Booking Confirmed', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="booking_pending"> <?php _e('Booking Pending', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="booking_completed"> <?php _e('Booking Completed', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="booking_refunded"> <?php _e('Booking Refunded', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="booking_status_changed"> <?php _e('Booking Status Changed', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="payment_failed"> <?php _e('Payment Failed', 'advanced-travel-crm'); ?></label><br>
                                    <label><input type="checkbox" name="notify_events[]" value="daily_summary"> <?php _e('Daily Summary Report', 'advanced-travel-crm'); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="active" id="active" value="1" checked>
                                        <?php _e('Active', 'advanced-travel-crm'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" name="save_recipient" class="button button-primary"><?php _e('Save Recipient', 'advanced-travel-crm'); ?></button>
                            <button type="button" class="button" onclick="closeRecipientForm()"><?php _e('Cancel', 'advanced-travel-crm'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
            
            <script>
            function showAddRecipientForm() {
                document.getElementById('recipientFormModal').style.display = 'block';
                document.getElementById('recipient_id').value = '';
                document.getElementById('name').value = '';
                document.getElementById('email').value = '';
                document.getElementById('phone').value = '';
            }
            
            function closeRecipientForm() {
                document.getElementById('recipientFormModal').style.display = 'none';
            }
            
            function editRecipient(id) {
                // Load recipient data via AJAX and populate form
                jQuery.post(ajaxurl, {
                    action: 'atc_get_recipient',
                    recipient_id: id,
                    _wpnonce: '<?php echo wp_create_nonce('atc_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        var r = response.data;
                        document.getElementById('recipient_id').value = r.id;
                        document.getElementById('name').value = r.name;
                        document.getElementById('email').value = r.email;
                        document.getElementById('phone').value = r.phone;
                        document.getElementById('role').value = r.role;
                        document.getElementById('email_enabled').checked = r.email_enabled == 1;
                        document.getElementById('whatsapp_enabled').checked = r.whatsapp_enabled == 1;
                        document.getElementById('active').checked = r.active == 1;
                        
                        // Set notify events
                        var events = JSON.parse(r.notify_events || '[]');
                        document.querySelectorAll('input[name="notify_events[]"]').forEach(function(el) {
                            el.checked = events.indexOf(el.value) !== -1;
                        });
                        
                        // Set WhatsApp mode
                        document.querySelectorAll('input[name="whatsapp_mode"]').forEach(function(el) {
                            el.checked = el.value === r.whatsapp_mode;
                        });
                        
                        showAddRecipientForm();
                    }
                });
            }
            </script>
        </div>
        <?php
    }
    
    /**
     * AJAX: Get recipient data
     */
    public static function ajax_get_recipient() {
        check_ajax_referer('atc_nonce', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        global $wpdb;
        $id = intval($_POST['recipient_id']);
        
        $recipient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_ADMIN_RECIPIENTS . " WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if ($recipient) {
            wp_send_json_success($recipient);
        } else {
            wp_send_json_error(['message' => 'Recipient not found']);
        }
    }
    
    /**
     * AJAX: Test notification
     */
    public static function ajax_test_notification() {
        check_ajax_referer('atc_nonce', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $channel = sanitize_text_field($_POST['channel']);
        $recipient = sanitize_email($_POST['recipient']);
        
        $test_data = [
            'booking_id' => 'TEST-' . time(),
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+919876543210',
            'service' => 'tours',
            'destination' => 'Goa',
            'price_total' => '25000',
            'currency' => 'INR',
            'travel_date' => date('Y-m-d', strtotime('+7 days')),
        ];
        
        if ($channel === 'email') {
            $template = self::get_email_template('admin_new_booking');
            if ($template && class_exists('ATC_Email_Sender')) {
                $result = ATC_Email_Sender::send($recipient, $template, $test_data);
                if ($result) {
                    wp_send_json_success(['message' => 'Test email sent!']);
                } else {
                    wp_send_json_error(['message' => 'Failed to send test email']);
                }
            }
        }
        
        wp_send_json_error(['message' => 'Invalid channel']);
    }
    
    /**
     * AJAX: Resend notification
     */
    public static function ajax_resend_notification() {
        check_ajax_referer('atc_nonce', '_wpnonce');
        
        if (!current_user_can('manage_atc')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        global $wpdb;
        $notification_id = intval($_POST['notification_id']);
        
        $notification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_NOTIFICATIONS . " WHERE id = %d",
            $notification_id
        ), ARRAY_A);
        
        if (!$notification) {
            wp_send_json_error(['message' => 'Notification not found']);
        }
        
        // Attempt to resend
        $payload = json_decode($notification['payload'], true);
        
        if ($notification['channel'] === 'email') {
            $template = self::get_email_template($notification['type']);
            if ($template && class_exists('ATC_Email_Sender')) {
                $result = ATC_Email_Sender::send($notification['recipient'], $template, $payload);
                
                if ($result) {
                    $wpdb->update(
                        ATC_TABLE_NOTIFICATIONS,
                        ['status' => 'sent', 'sent_at' => current_time('mysql'), 'attempts' => $notification['attempts'] + 1],
                        ['id' => $notification_id]
                    );
                    wp_send_json_success(['message' => 'Notification resent successfully']);
                } else {
                    $wpdb->update(
                        ATC_TABLE_NOTIFICATIONS,
                        ['attempts' => $notification['attempts'] + 1],
                        ['id' => $notification_id]
                    );
                    wp_send_json_error(['message' => 'Failed to resend notification']);
                }
            }
        }
        
        wp_send_json_error(['message' => 'Cannot resend this notification type']);
    }
    
    /**
     * AJAX: Mark WhatsApp as sent
     */
    public static function ajax_mark_whatsapp_sent() {
        check_ajax_referer('atc_nonce', '_wpnonce');
        
        $record_id = intval($_POST['record_id']);
        $record_type = sanitize_text_field($_POST['record_type']);
        $recipient_type = sanitize_text_field($_POST['recipient_type']);
        
        global $wpdb;
        
        // Update notification status from pending_manual to sent
        $wpdb->update(
            ATC_TABLE_NOTIFICATIONS,
            [
                'status' => 'sent',
                'sent_at' => current_time('mysql')
            ],
            [
                $record_type . '_id' => $record_id,
                'channel' => 'whatsapp',
                'recipient' => $recipient_type,
                'status' => 'pending_manual'
            ]
        );
        
        wp_send_json_success(['message' => 'WhatsApp marked as sent']);
    }
    
    /**
     * AJAX: Send booking notification to admins
     */
    public static function ajax_send_booking_notification() {
        check_ajax_referer('atc_nonce', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        global $wpdb;
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => 'Booking ID required']);
        }
        
        // Get booking data
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            wp_send_json_error(['message' => 'Booking not found']);
        }
        
        // Manually send notifications (don't trigger booking_created to avoid duplicate)
        // Send admin notifications
        self::send_admin_notifications('new_booking', $booking_id, $booking, 'email');
        
        // Send customer confirmation
        self::send_customer_notification('booking_confirmation', $booking_id, $booking, 'email');
        
        wp_send_json_success(['message' => 'Notification sent to all admin recipients']);
    }
}

// Register AJAX action for marking WhatsApp as sent
add_action('wp_ajax_atc_mark_whatsapp_sent', ['ATC_Notification_Manager', 'ajax_mark_whatsapp_sent']);
add_action('wp_ajax_atc_get_recipient', ['ATC_Notification_Manager', 'ajax_get_recipient']);