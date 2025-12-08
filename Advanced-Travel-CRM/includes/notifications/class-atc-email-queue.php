<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * class-atc-email-queue.php
 * Location: includes/notifications/class-atc-email-queue.php
 * ═══════════════════════════════════════════════════════════════════
 */

if (!defined('ABSPATH')) exit;

class ATC_Email_Queue {
    
    const MAX_ATTEMPTS = 3;
    
    public static function init() {
        // Process queue every 5 minutes
        add_action('atc_process_email_queue', [__CLASS__, 'process_queue']);
        
        if (!wp_next_scheduled('atc_process_email_queue')) {
            wp_schedule_event(time(), 'every_5_minutes', 'atc_process_email_queue');
        }
        
        // Add custom cron schedule
        add_filter('cron_schedules', [__CLASS__, 'add_cron_schedule']);
    }
    
    public static function add_cron_schedule($schedules) {
        $schedules['every_5_minutes'] = [
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'advanced-travel-crm')
        ];
        return $schedules;
    }
    
    /**
     * ✅ Queue email for sending
     */
    public static function queue_email($to, $subject, $body, $headers = [], $attachments = [], $priority = 5) {
        global $wpdb;
        
        $data = [
            'recipient' => sanitize_email($to),
            'subject' => sanitize_text_field($subject),
            'body' => $body,
            'headers' => json_encode($headers),
            'attachments' => json_encode($attachments),
            'priority' => intval($priority),
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => current_time('mysql'),
        ];
        
        // Store in notifications table with type 'queued_email'
        $wpdb->insert(ATC_TABLE_NOTIFICATIONS, [
            'type' => 'queued_email',
            'channel' => 'email',
            'recipient' => $data['recipient'],
            'payload' => json_encode($data),
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => $data['created_at'],
        ]);
        
        return $wpdb->insert_id;
    }
    
    /**
     * ✅ Process email queue
     */
    public static function process_queue() {
        global $wpdb;
        
        // Get pending emails (max 10 per batch)
        $emails = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_NOTIFICATIONS . " 
            WHERE type = 'queued_email' 
            AND status IN ('pending', 'failed')
            AND attempts < " . self::MAX_ATTEMPTS . "
            ORDER BY created_at ASC 
            LIMIT 10",
            ARRAY_A
        );
        
        foreach ($emails as $email) {
            self::send_queued_email($email);
        }
    }
    
    /**
     * ✅ Send queued email
     */
    private static function send_queued_email($email) {
        global $wpdb;
        
        $payload = json_decode($email['payload'], true);
        
        // Increment attempts
        $attempts = intval($email['attempts']) + 1;
        
        // Configure headers
        $headers = json_decode($payload['headers'], true) ?: [];
        if (empty($headers)) {
            $headers = ['Content-Type: text/html; charset=UTF-8'];
        }
        
        // Send email
        $result = wp_mail(
            $payload['recipient'],
            $payload['subject'],
            $payload['body'],
            $headers,
            json_decode($payload['attachments'], true) ?: []
        );
        
        if ($result) {
            // Mark as sent
            $wpdb->update(
                ATC_TABLE_NOTIFICATIONS,
                [
                    'status' => 'sent',
                    'sent_at' => current_time('mysql'),
                    'attempts' => $attempts,
                ],
                ['id' => $email['id']]
            );
            
            ATC_Logger::log('info', 'Email sent from queue: ' . $payload['recipient']);
        } else {
            // Mark as failed or retry
            $status = ($attempts >= self::MAX_ATTEMPTS) ? 'failed' : 'pending';
            
            $wpdb->update(
                ATC_TABLE_NOTIFICATIONS,
                [
                    'status' => $status,
                    'attempts' => $attempts,
                    'response' => json_encode(['error' => 'wp_mail failed']),
                ],
                ['id' => $email['id']]
            );
            
            ATC_Logger::log('error', 'Email failed (attempt ' . $attempts . '): ' . $payload['recipient']);
        }
    }
    
    /**
     * ✅ Retry failed emails
     */
    public static function retry_failed($email_id) {
        global $wpdb;
        
        $wpdb->update(
            ATC_TABLE_NOTIFICATIONS,
            ['status' => 'pending', 'attempts' => 0],
            ['id' => $email_id, 'type' => 'queued_email']
        );
    }
    
    /**
     * ✅ Get queue stats
     */
    public static function get_stats() {
        global $wpdb;
        
        return [
            'pending' => $wpdb->get_var(
                "SELECT COUNT(*) FROM " . ATC_TABLE_NOTIFICATIONS . " 
                WHERE type = 'queued_email' AND status = 'pending'"
            ),
            'sent' => $wpdb->get_var(
                "SELECT COUNT(*) FROM " . ATC_TABLE_NOTIFICATIONS . " 
                WHERE type = 'queued_email' AND status = 'sent'"
            ),
            'failed' => $wpdb->get_var(
                "SELECT COUNT(*) FROM " . ATC_TABLE_NOTIFICATIONS . " 
                WHERE type = 'queued_email' AND status = 'failed'"
            ),
        ];
    }
}
