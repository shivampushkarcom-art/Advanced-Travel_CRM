<?php
/**
 * ATC OTP System - WhatsApp Business API Integration
 * WhatsApp OTP verification for registration and password reset
 * 
 * Features:
 * - Generate secure OTP
 * - Send via WhatsApp Business API
 * - Verify OTP
 * - Rate limiting
 * - Expiration handling
 * - Resend functionality
 */

if (!defined('ABSPATH')) exit;

class ATC_OTP {
    
    const OTP_LENGTH = 6;
    const OTP_EXPIRY = 600; // 10 minutes
    const MAX_ATTEMPTS = 5;
    
    /**
     * Generate OTP
     */
    public static function generate_otp($length = self::OTP_LENGTH) {
        return wp_rand(pow(10, $length - 1), pow(10, $length) - 1);
    }
    
    /**
     * Clean phone number
     */
    private static function clean_phone_number($phone) {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If doesn't start with +, assume it's Indian number and add +91
        if (!str_starts_with($phone, '+')) {
            if (strlen($phone) === 10) {
                $phone = '+91' . $phone;
            } elseif (strlen($phone) === 12 && str_starts_with($phone, '91')) {
                $phone = '+' . $phone;
            }
        }
        
        return $phone;
    }
    
    /**
     * Send OTP via WhatsApp
     */
    public static function send_otp($phone, $purpose = 'verification') {
        // Check if WhatsApp OTP is enabled
        if (!get_option('atc_whatsapp_otp_enabled', 1)) {
            return false;
        }
        
        // Clean phone number
        $phone = self::clean_phone_number($phone);
        
        // Validate phone number
        if (empty($phone) || strlen($phone) < 10) {
            return new WP_Error('invalid_phone', __('Invalid phone number', 'advanced-travel-crm'));
        }
        
        // Rate limiting - max 3 OTPs per hour per phone
        $rate_key = 'atc_otp_rate_' . md5($phone);
        $sent_count = get_transient($rate_key) ?: 0;
        
        if ($sent_count >= 3) {
            return new WP_Error('rate_limit', __('Too many OTP requests. Please try again later.', 'advanced-travel-crm'));
        }
        
        // Generate OTP
        $otp = self::generate_otp();
        
        // Store OTP (use phone instead of email)
        $otp_key = 'atc_otp_' . md5($phone . $purpose);
        set_transient($otp_key, [
            'code' => $otp,
            'attempts' => 0,
            'created_at' => time(),
            'phone' => $phone,
        ], self::OTP_EXPIRY);
        
        // Update rate limit
        set_transient($rate_key, $sent_count + 1, HOUR_IN_SECONDS);
        
        // Format WhatsApp message
        $message = self::format_whatsapp_otp_message($otp, $purpose);
        
        // Send via WhatsApp
        if (!class_exists('ATC_WhatsApp_Sender')) {
            require_once ATC_INCLUDES_DIR . 'notifications/class-atc-whatsapp-sender.php';
        }
        
        if (class_exists('ATC_WhatsApp_Sender')) {
            $result = ATC_WhatsApp_Sender::send_message($message, $phone);
            
            if ($result) {
                // Log success
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('ATC WhatsApp OTP: To=%s, Purpose=%s, Result=Success', $phone, $purpose));
                }
                return true;
            } else {
                // Log failure
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('ATC WhatsApp OTP: To=%s, Purpose=%s, Result=Failed', $phone, $purpose));
                }
                return new WP_Error('send_failed', __('Failed to send OTP via WhatsApp. Please check your WhatsApp API configuration.', 'advanced-travel-crm'));
            }
        }
        
        return new WP_Error('whatsapp_unavailable', __('WhatsApp API not configured. Please contact administrator.', 'advanced-travel-crm'));
    }
    
    /**
     * Format WhatsApp OTP message
     */
    private static function format_whatsapp_otp_message($otp, $purpose) {
        $site_name = get_bloginfo('name');
        
        $purposes = [
            'verification' => 'registration verification',
            'password_reset' => 'password reset',
            'login' => 'login',
        ];
        
        $purpose_text = $purposes[$purpose] ?? $purpose;
        $expiry_minutes = self::OTP_EXPIRY / 60;
        
        $message = "🔐 *" . ucfirst($site_name) . " - OTP Verification*\n\n";
        $message .= "Your OTP code for " . $purpose_text . " is:\n\n";
        $message .= "*" . $otp . "*\n\n";
        $message .= "This code will expire in " . $expiry_minutes . " minutes.\n\n";
        $message .= "⚠️ *Security Notice:*\n";
        $message .= "If you didn't request this code, please ignore this message.\n";
        $message .= "Do not share this code with anyone.\n\n";
        $message .= "Thank you,\n";
        $message .= $site_name;
        
        return $message;
    }
    
    /**
     * Verify OTP
     */
    public static function verify_otp($phone, $otp_code, $purpose = 'verification') {
        // Clean phone number
        $phone = self::clean_phone_number($phone);
        
        $otp_key = 'atc_otp_' . md5($phone . $purpose);
        $otp_data = get_transient($otp_key);
        
        // Check if OTP exists
        if (!$otp_data) {
            return new WP_Error('expired', __('OTP has expired. Please request a new one.', 'advanced-travel-crm'));
        }
        
        // Check attempts
        if ($otp_data['attempts'] >= self::MAX_ATTEMPTS) {
            delete_transient($otp_key);
            return new WP_Error('max_attempts', __('Too many incorrect attempts. Please request a new OTP.', 'advanced-travel-crm'));
        }
        
        // Verify code
        if ($otp_data['code'] != $otp_code) {
            // Increment attempts
            $otp_data['attempts']++;
            set_transient($otp_key, $otp_data, self::OTP_EXPIRY);
            
            $remaining = self::MAX_ATTEMPTS - $otp_data['attempts'];
            return new WP_Error(
                'incorrect',
                sprintf(__('Incorrect OTP. %d attempts remaining.', 'advanced-travel-crm'), $remaining)
            );
        }
        
        // Success - delete OTP
        delete_transient($otp_key);
        
        return true;
    }
    
    /**
     * Check if OTP is valid (without verifying)
     */
    public static function otp_exists($phone, $purpose = 'verification') {
        $phone = self::clean_phone_number($phone);
        $otp_key = 'atc_otp_' . md5($phone . $purpose);
        return get_transient($otp_key) !== false;
    }
    
    /**
     * Get OTP expiry time (in seconds remaining)
     */
    public static function get_otp_expiry($phone, $purpose = 'verification') {
        $phone = self::clean_phone_number($phone);
        $otp_key = 'atc_otp_' . md5($phone . $purpose);
        $otp_data = get_transient($otp_key);
        
        if (!$otp_data) {
            return 0;
        }
        
        $elapsed = time() - $otp_data['created_at'];
        $remaining = self::OTP_EXPIRY - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Resend OTP
     */
    public static function resend_otp($phone, $purpose = 'verification') {
        // Delete existing OTP
        $phone = self::clean_phone_number($phone);
        $otp_key = 'atc_otp_' . md5($phone . $purpose);
        delete_transient($otp_key);
        
        // Send new OTP
        return self::send_otp($phone, $purpose);
    }
    
    /**
     * Send OTP for login (2FA)
     */
    public static function send_login_otp($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $phone = get_user_meta($user_id, 'atc_phone', true);
        if (empty($phone)) {
            return new WP_Error('no_phone', __('Phone number not found for this user.', 'advanced-travel-crm'));
        }
        
        return self::send_otp($phone, 'login');
    }
    
    /**
     * Verify login OTP (2FA)
     */
    public static function verify_login_otp($user_id, $otp_code) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'advanced-travel-crm'));
        }
        
        $phone = get_user_meta($user_id, 'atc_phone', true);
        if (empty($phone)) {
            return new WP_Error('no_phone', __('Phone number not found for this user.', 'advanced-travel-crm'));
        }
        
        return self::verify_otp($phone, $otp_code, 'login');
    }
    
    /**
     * ========================================
     * SHORTCODES & UI
     * ========================================
     */
    
    /**
     * OTP Verification Form Shortcode
     */
    public static function otp_form_shortcode($atts) {
        $atts = shortcode_atts([
            'phone' => '',
            'purpose' => 'verification',
            'redirect' => home_url(),
        ], $atts);
        
        if (empty($atts['phone'])) {
            return '<p>' . __('Phone number required', 'advanced-travel-crm') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="atc-otp-form-wrapper">
            <h3><?php _e('Enter Verification Code', 'advanced-travel-crm'); ?></h3>
            <p><?php printf(__('We sent a 6-digit code to %s via WhatsApp', 'advanced-travel-crm'), '<strong>' . esc_html($atts['phone']) . '</strong>'); ?></p>
            
            <form method="post" action="" class="atc-otp-form">
                <?php wp_nonce_field('atc_verify_otp', 'atc_otp_nonce'); ?>
                <input type="hidden" name="atc_verify_otp" value="1">
                <input type="hidden" name="phone" value="<?php echo esc_attr($atts['phone']); ?>">
                <input type="hidden" name="purpose" value="<?php echo esc_attr($atts['purpose']); ?>">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($atts['redirect']); ?>">
                
                <div class="atc-otp-input-group">
                    <input type="text" name="otp" id="atc_otp_input" 
                           maxlength="6" pattern="[0-9]{6}" 
                           placeholder="000000" required autocomplete="off">
                </div>
                
                <button type="submit" class="atc-btn atc-btn-primary atc-btn-block">
                    <?php _e('Verify Code', 'advanced-travel-crm'); ?>
                </button>
                
                <div class="atc-otp-resend">
                    <p><?php _e('Didn\'t receive the code?', 'advanced-travel-crm'); ?></p>
                    <button type="button" class="atc-btn atc-btn-text atc-resend-otp" 
                            data-phone="<?php echo esc_attr($atts['phone']); ?>"
                            data-purpose="<?php echo esc_attr($atts['purpose']); ?>">
                        <?php _e('Resend Code', 'advanced-travel-crm'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <style>
        .atc-otp-input-group {
            margin: 20px 0;
        }
        .atc-otp-input-group input {
            font-size: 32px;
            letter-spacing: 10px;
            text-align: center;
            font-weight: bold;
            padding: 15px;
            width: 100%;
        }
        .atc-otp-resend {
            text-align: center;
            margin-top: 20px;
        }
        .atc-otp-resend p {
            margin-bottom: 10px;
            color: #64748b;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto-focus OTP input
            $('#atc_otp_input').focus();
            
            // Auto-submit when 6 digits entered
            $('#atc_otp_input').on('input', function() {
                if (this.value.length === 6) {
                    $(this).closest('form').submit();
                }
            });
            
            // Resend OTP
            $('.atc-resend-otp').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var phone = btn.data('phone');
                var purpose = btn.data('purpose');
                
                btn.prop('disabled', true).text('<?php _e('Sending...', 'advanced-travel-crm'); ?>');
                
                $.post(atcAccount.ajaxUrl, {
                    action: 'atc_resend_otp',
                    nonce: atcAccount.nonce,
                    phone: phone,
                    purpose: purpose
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('New code sent!', 'advanced-travel-crm'); ?>');
                        btn.text('<?php _e('Resend Code', 'advanced-travel-crm'); ?>');
                        
                        // Enable after 60 seconds
                        setTimeout(function() {
                            btn.prop('disabled', false);
                        }, 60000);
                    } else {
                        alert(response.data.message);
                        btn.prop('disabled', false).text('<?php _e('Resend Code', 'advanced-travel-crm'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle OTP verification form submission
     */
    public static function handle_otp_verification() {
        if (!isset($_POST['atc_verify_otp'])) {
            return;
        }
        
        if (!isset($_POST['atc_otp_nonce']) || !wp_verify_nonce($_POST['atc_otp_nonce'], 'atc_verify_otp')) {
            return;
        }
        
        $phone = sanitize_text_field($_POST['phone']);
        $otp = sanitize_text_field($_POST['otp']);
        $purpose = sanitize_text_field($_POST['purpose']);
        $redirect_to = esc_url_raw($_POST['redirect_to']);
        
        $result = self::verify_otp($phone, $otp, $purpose);
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg('error', $result->get_error_code(), wp_get_referer()));
            exit;
        }
        
        // Success
        wp_redirect(add_query_arg('verified', 'success', $redirect_to));
        exit;
    }
    
    /**
     * Initialize OTP system
     */
    public static function init() {
        // Register AJAX handlers
        add_action('wp_ajax_atc_resend_otp', [__CLASS__, 'ajax_resend_otp']);
        add_action('wp_ajax_nopriv_atc_resend_otp', [__CLASS__, 'ajax_resend_otp']);
        
        // Register form handler
        add_action('init', [__CLASS__, 'handle_otp_verification']);
    }
    
    /**
     * AJAX: Resend OTP
     */
    public static function ajax_resend_otp() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $purpose = sanitize_text_field($_POST['purpose'] ?? 'verification');
        
        if (empty($phone)) {
            wp_send_json_error(['message' => __('Phone number required', 'advanced-travel-crm')]);
        }
        
        $result = self::resend_otp($phone, $purpose);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('New code sent via WhatsApp!', 'advanced-travel-crm')]);
    }
}
