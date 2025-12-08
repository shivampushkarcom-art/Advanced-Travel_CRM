<?php
/**
 * ATC Email Sender
 * Handles automatic email sending with SMTP support
 * 
 * Features:
 * - SMTP configuration
 * - WordPress wp_mail fallback
 * - HTML email templates
 * - Variable replacement
 * - Attachment support
 * - Error logging
 */

if (!defined('ABSPATH')) exit;

class ATC_Email_Sender {
    
    /**
     * Send email
     */
    public static function send($to, $template, $data = []) {
        if (empty($to) || empty($template)) {
            return false;
        }
        
        // Configure SMTP if enabled
        $smtp_enabled = get_option('atc_smtp_enabled', 0);
        if ($smtp_enabled) {
            add_action('phpmailer_init', [__CLASS__, 'configure_smtp']);
        }
        
        // Prepare email
        $subject = self::replace_variables($template['subject'] ?? 'Notification', $data);
        $body = self::replace_variables($template['body'] ?? '', $data);
        
        // Convert to HTML
        $html_body = self::convert_to_html($body, $data);
        
        // Headers - Enhanced to prevent spam
        $from_email = self::get_from_email();
        $from_name = self::get_from_name();
        $reply_to = get_option('atc_email_reply_to', $from_email);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $reply_to,
        ];
        
        // Optional: ONLY if you really need it for customers, not admins
        $unsubscribe_url = get_option('atc_email_unsubscribe_url', '');
        if (!empty($unsubscribe_url)) {
            $headers[] = 'List-Unsubscribe: <' . esc_url($unsubscribe_url) . '>';
            $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
        }
        
        // Filter to enhance headers before sending
        $headers = apply_filters('atc_email_headers', $headers, $to, $subject);
        
        // Send email
        $result = wp_mail($to, $subject, $html_body, $headers);
        
        // Remove SMTP hook
        if ($smtp_enabled) {
            remove_action('phpmailer_init', [__CLASS__, 'configure_smtp']);
        }
        
        // Log result
        self::log_email($to, $subject, $result);
        
        return $result;
    }
    
    /**
     * Configure SMTP
     */
    public static function configure_smtp($phpmailer) {
        $host = get_option('atc_smtp_host', '');
        $port = intval(get_option('atc_smtp_port', 587));
        $user = get_option('atc_smtp_user', '');
        $pass = get_option('atc_smtp_pass', '');
        $secure = get_option('atc_smtp_secure', 'tls');
        
        if (empty($host)) return;
        
        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = $port;
        $phpmailer->SMTPAuth = !empty($user);
        
        if (!empty($user)) {
            $phpmailer->Username = $user;
            $phpmailer->Password = $pass;
        }
        
        if ($secure === 'ssl') {
            $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'tls') {
            $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        // Enhanced SMTP settings to prevent spam
        $phpmailer->SMTPKeepAlive = false;
        $phpmailer->SMTPDebug = 0; // Set to 2 for debugging
        $phpmailer->CharSet = 'UTF-8';
        $phpmailer->Encoding = 'base64';
        
        // Set from
        $from_email = self::get_from_email();
        $from_name = self::get_from_name();
        
        if ($from_email) {
            $phpmailer->setFrom($from_email, $from_name, false);
        }
        
        // Set Reply-To
        $reply_to = get_option('atc_email_reply_to', $from_email);
        if ($reply_to) {
            $phpmailer->addReplyTo($reply_to, $from_name);
        }
        
        // Add authentication hints for better deliverability
        $phpmailer->DKIM_selector = 'default';
        $phpmailer->DKIM_domain = parse_url(home_url(), PHP_URL_HOST);
    }
    
    /**
     * Replace variables in template
     */
    private static function replace_variables($template, $data) {
        // Get package name intelligently
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
            '{admin_name}' => $data['admin_name'] ?? '',
            '{booking_id}' => $data['booking_id'] ?? '',
            '{query_id}' => $data['query_id'] ?? $data['id'] ?? '',
            '{customer_name}' => $data['customer_name'] ?? $data['name'] ?? '',
            '{customer_email}' => $data['customer_email'] ?? $data['email'] ?? '',
            '{customer_phone}' => $data['customer_phone'] ?? $data['phone'] ?? '',
            '{service_name}' => ucfirst($data['service'] ?? $data['service_key'] ?? ''),
            '{destination}' => $data['destination'] ?? '',
            '{package_name}' => $package_name,
            '{adults}' => $data['adults'] ?? '0',
            '{children}' => $data['children'] ?? '0',
            '{price_total}' => number_format($data['price_total'] ?? 0, 2),
            '{currency}' => $data['currency'] ?? get_option('atc_currency', 'INR'),
            '{travel_date}' => $data['travel_date'] ?? '',
            '{return_date}' => $data['return_date'] ?? '',
            '{payment_status}' => ucfirst($data['payment_status'] ?? 'unpaid'),
            '{transaction_id}' => $data['transaction_id'] ?? '',
            '{gateway}' => ucfirst($data['gateway'] ?? ''),
            '{amount}' => number_format($data['amount'] ?? 0, 2),
            '{score}' => $data['score'] ?? '',
            '{temperature}' => strtoupper($data['temperature'] ?? ''),
            '{budget_min}' => number_format($data['budget_min'] ?? 0, 0),
            '{budget_max}' => number_format($data['budget_max'] ?? 0, 0),
            '{budget}' => ($data['budget_min'] ?? 0) . ' - ' . ($data['budget_max'] ?? 0),
            '{date_from}' => $data['date_from'] ?? '',
            '{date_to}' => $data['date_to'] ?? '',
            '{lead_name}' => $data['name'] ?? '',
            '{lead_email}' => $data['email'] ?? '',
            '{lead_phone}' => $data['phone'] ?? '',
            '{message}' => $data['message'] ?? $data['special_requirements'] ?? '',
            '{query_message}' => $data['message'] ?? $data['special_requirements'] ?? '',
            '{special_requests}' => $data['special_requirements'] ?? $data['special_requests'] ?? '',
            '{trip_type}' => ucfirst($data['trip_type'] ?? ''),
            '{hotel_type}' => ucfirst($data['hotel_type'] ?? ''),
            '{child_ages}' => $data['child_ages'] ?? '',
            '{company_name}' => get_bloginfo('name'),
            '{site_url}' => home_url(),
            '{support_email}' => get_option('atc_admin_email', get_option('admin_email')),
            '{support_phone}' => get_option('atc_support_phone', ''),
        ];
        
        // Generic fallback: Map any other scalar keys from data
        foreach ($data as $key => $value) {
            if (is_scalar($value) && !isset($replacements["{" . $key . "}"])) {
                $replacements["{" . $key . "}"] = $value;
            }
        }
        
        return strtr($template, $replacements);
    }
    
    /**
     * Convert plain text to HTML email
     */
    private static function convert_to_html($body, $data = []) {
        $body = nl2br(esc_html($body));
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f7fafc;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-container {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .email-header {
            background: #2563eb;
            color: #ffffff;
            padding: 25px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        .email-body {
            padding: 30px;
            font-size: 15px;
            color: #4a5568;
        }
        .email-footer {
            background: #f8fafc;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
        strong {
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>' . get_bloginfo('name') . '</h1>
        </div>
        <div class="email-body">
            ' . $body . '
        </div>
        <div class="email-footer">
            <p>&copy; ' . date('Y') . ' ' . get_bloginfo('name') . '. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Get from email
     */
    private static function get_from_email() {
        $email = get_option('atc_email_from_address', '');
        if (empty($email)) {
            // Try admin email
            $email = get_option('atc_admin_email', '');
            if (empty($email)) {
                $email = get_option('admin_email');
            }
        }
        
        // Validate email
        if (!is_email($email)) {
            $email = get_option('admin_email');
        }
        
        return $email;
    }
    
    /**
     * Get from name
     */
    private static function get_from_name() {
        $name = get_option('atc_email_from_name', '');
        if (empty($name)) {
            $name = get_bloginfo('name');
        }
        return $name;
    }
    
    /**
     * Log email sending
     */
    private static function log_email($to, $subject, $result) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'ATC Email: To=%s, Subject=%s, Result=%s',
                $to,
                $subject,
                $result ? 'Success' : 'Failed'
            ));
        }
    }
}