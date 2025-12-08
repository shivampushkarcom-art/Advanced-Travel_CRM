<?php
/**
 * ATC User Account System
 * Complete customer portal with registration, login, profile management
 * 
 * Features:
 * - User registration with WhatsApp OTP verification
 * - Login/Logout
 * - Profile management
 * - Password change
 * - Booking history
 * - Account dashboard
 */

if (!defined('ABSPATH')) exit;

class ATC_User {

    public static function init() {
        // Shortcodes
        add_shortcode('atc_account_dashboard', [__CLASS__, 'dashboard_shortcode']);
        add_shortcode('atc_register_form', [__CLASS__, 'register_form_shortcode']);
        add_shortcode('atc_login_form', [__CLASS__, 'login_form_shortcode']);
        add_shortcode('atc_profile_form', [__CLASS__, 'profile_form_shortcode']);
        add_shortcode('atc_password_form', [__CLASS__, 'password_form_shortcode']);
        add_shortcode('atc_logout_button', [__CLASS__, 'logout_button_shortcode']);
        add_shortcode('atc_account_menu', [__CLASS__, 'account_menu_shortcode']);

        // Form Handlers
        add_action('init', [__CLASS__, 'handle_registration']);
        add_action('init', [__CLASS__, 'handle_profile_update']);
        add_action('init', [__CLASS__, 'handle_password_change']);
        add_action('init', [__CLASS__, 'handle_logout']);
        add_action('init', [__CLASS__, 'handle_otp_verification']);
        add_action('init', [__CLASS__, 'handle_custom_login']);
        add_action('init', [__CLASS__, 'handle_forgot_password']);
        add_action('init', [__CLASS__, 'handle_password_reset_otp']);
        add_action('init', [__CLASS__, 'handle_password_reset']);
        
        // AJAX Handlers
        add_action('wp_ajax_atc_resend_verification_otp', [__CLASS__, 'ajax_resend_verification_otp']);
        add_action('wp_ajax_nopriv_atc_resend_verification_otp', [__CLASS__, 'ajax_resend_verification_otp']);
        add_action('wp_ajax_atc_resend_password_reset_otp', [__CLASS__, 'ajax_resend_password_reset_otp']);
        add_action('wp_ajax_nopriv_atc_resend_password_reset_otp', [__CLASS__, 'ajax_resend_password_reset_otp']);
        
        // Dashboard AJAX handlers
        add_action('wp_ajax_atc_get_booking_details', [__CLASS__, 'ajax_get_booking_details']);
        add_action('wp_ajax_atc_cancel_booking', [__CLASS__, 'ajax_cancel_booking']);
        
        // Shortcodes (keeping old name for backward compatibility, but it handles phone verification)
        add_shortcode('atc_verify_email_form', [__CLASS__, 'verify_email_form_shortcode']);
        add_shortcode('atc_verify_phone_form', [__CLASS__, 'verify_email_form_shortcode']); // Alias for clarity
        add_shortcode('atc_forgot_password_form', [__CLASS__, 'forgot_password_form_shortcode']);
        add_shortcode('atc_reset_password_form', [__CLASS__, 'reset_password_form_shortcode']);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }
    
    /**
     * Enqueue frontend scripts
     */
    public static function enqueue_scripts() {
        global $post;
        
        // Check if account menu shortcode is present on the page
        $has_account_menu = false;
        if (is_a($post, 'WP_Post')) {
            // Check in post content
            if (has_shortcode($post->post_content, 'atc_account_menu')) {
                $has_account_menu = true;
            }
            // Also check if shortcode might be in widgets/blocks/page builders
            if (strpos($post->post_content, 'atc_account_menu') !== false) {
                $has_account_menu = true;
            }
        }
        
        // Check in widgets (for header/footer widgets)
        if (is_active_sidebar('header') || is_active_sidebar('footer')) {
            $widget_text = '';
            ob_start();
            dynamic_sidebar('header');
            dynamic_sidebar('footer');
            $widget_text = ob_get_clean();
            if (strpos($widget_text, 'atc_account_menu') !== false) {
                $has_account_menu = true;
            }
        }
        
        // Always load if user is logged in (menu might be in header/footer) OR if it's an account page OR if shortcode is present
        $is_account_page = is_page('login') || is_page('register') || is_page('my-account') || is_page('verify-email') || is_page('forgot-password');
        $should_load = is_user_logged_in() || $is_account_page || $has_account_menu;
        
        if ($should_load) {
            wp_enqueue_style('atc-account-styles', ATC_ASSETS_URL . 'css/atc-account.css', [], ATC_VERSION);
            wp_enqueue_script('atc-account', ATC_ASSETS_URL . 'js/atc-account.js', ['jquery'], ATC_VERSION, true);
            
            wp_localize_script('atc-account', 'atcAccount', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('atc_nonce'),
                'strings' => [
                    'confirmCancel' => __('Are you sure you want to cancel this booking?', 'advanced-travel-crm'),
                    'emailSent' => __('Email sent successfully!', 'advanced-travel-crm'),
                    'error' => __('An error occurred. Please try again.', 'advanced-travel-crm'),
                ]
            ]);
        }
    }

    /**
     * ========================================
     * REGISTRATION
     * ========================================
     */
    
    /**
     * Registration Form Shortcode (Premium UI)
     */
    public static function register_form_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<div class="atc-notice atc-notice-info">
                <p>' . __('You are already logged in.', 'advanced-travel-crm') . ' 
                <a href="' . esc_url(wp_logout_url(home_url())) . '">' . __('Logout', 'advanced-travel-crm') . '</a></p>
            </div>';
        }
        
        $atts = shortcode_atts([
            'redirect' => home_url('/my-account/'),
        ], $atts);
        
        ob_start();
        ?>
        <div class="atc-premium-auth-form atc-register-form-wrapper">
            <div class="atc-auth-header">
                <h2><?php _e('Create Your Account', 'advanced-travel-crm'); ?></h2>
                <p><?php _e('Join us and start booking amazing trips', 'advanced-travel-crm'); ?></p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="atc-message atc-message-error">
                    <p><?php echo esc_html(self::get_error_message($_GET['error'])); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="atc-premium-form">
                <?php wp_nonce_field('atc_register', 'atc_register_nonce'); ?>
                <input type="hidden" name="atc_register_submit" value="1">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($atts['redirect']); ?>">
                
                <div class="atc-premium-form-group">
                    <label for="reg_name"><?php _e('Full Name', 'advanced-travel-crm'); ?> *</label>
                    <input type="text" name="name" id="reg_name" required 
                           placeholder="<?php _e('Enter your full name', 'advanced-travel-crm'); ?>"
                           value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>">
                </div>
                
                <div class="atc-premium-form-group">
                    <label for="reg_email"><?php _e('Email Address', 'advanced-travel-crm'); ?> *</label>
                    <input type="email" name="email" id="reg_email" required 
                           placeholder="<?php _e('Enter your email address', 'advanced-travel-crm'); ?>"
                           value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                </div>
                
                <div class="atc-premium-form-group">
                    <label for="reg_phone"><?php _e('Phone Number', 'advanced-travel-crm'); ?> *</label>
                    <input type="tel" name="phone" id="reg_phone" required
                           placeholder="+919876543210"
                           value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>">
                    <small><?php _e('Include country code (e.g., +91 for India)', 'advanced-travel-crm'); ?></small>
                </div>
                
                <div class="atc-premium-form-group">
                    <label for="reg_password"><?php _e('Password', 'advanced-travel-crm'); ?> *</label>
                    <input type="password" name="password" id="reg_password" required minlength="6"
                           placeholder="<?php _e('Create a password', 'advanced-travel-crm'); ?>">
                    <small><?php _e('Minimum 6 characters', 'advanced-travel-crm'); ?></small>
                </div>
                
                <div class="atc-premium-form-group">
                    <label for="reg_password_confirm"><?php _e('Confirm Password', 'advanced-travel-crm'); ?> *</label>
                    <input type="password" name="password_confirm" id="reg_password_confirm" required minlength="6"
                           placeholder="<?php _e('Confirm your password', 'advanced-travel-crm'); ?>">
                </div>
                
                <div class="atc-premium-form-group">
                    <label class="atc-checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span><?php _e('I agree to the', 'advanced-travel-crm'); ?> 
                            <a href="<?php echo get_privacy_policy_url(); ?>" target="_blank"><?php _e('Terms & Conditions', 'advanced-travel-crm'); ?></a>
                        </span>
                    </label>
                </div>
                
                <div class="atc-premium-form-actions">
                    <button type="submit" class="atc-btn-premium atc-btn-premium-primary atc-btn-block">
                        <?php _e('Create Account', 'advanced-travel-crm'); ?>
                    </button>
                </div>
                
                <div class="atc-auth-footer">
                    <p><?php _e('Already have an account?', 'advanced-travel-crm'); ?> 
                       <a href="<?php echo home_url('/login/'); ?>"><?php _e('Login', 'advanced-travel-crm'); ?></a>
                    </p>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle Registration Form Submission
     */
    public static function handle_registration() {
        if (!isset($_POST['atc_register_submit'])) {
            return;
        }
        
        if (!isset($_POST['atc_register_nonce']) || !wp_verify_nonce($_POST['atc_register_nonce'], 'atc_register')) {
            return;
        }
        
        // Sanitize inputs
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $redirect_value = isset($_POST['redirect_to']) ? wp_unslash($_POST['redirect_to']) : '';
        $redirect_to = self::resolve_redirect_target($redirect_value);
        $redirect_to = wp_validate_redirect($redirect_to, home_url('/'));
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'name_required';
        }
        
        if (empty($email) || !is_email($email)) {
            $errors[] = 'email_invalid';
        }
        
        if (email_exists($email)) {
            $errors[] = 'email_exists';
        }
        
        // Validate phone number
        if (!empty($phone)) {
            $phone = preg_replace('/[^0-9+]/', '', $phone);
            if (strlen($phone) < 10) {
                $errors[] = 'phone_invalid';
            }
            
            // Check if phone already exists
            global $wpdb;
            $existing_phone = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'atc_phone' AND meta_value = %s",
                $phone
            ));
            if ($existing_phone) {
                $errors[] = 'phone_exists';
            }
        }
        
        if (strlen($password) < 6) {
            $errors[] = 'password_short';
        }
        
        if ($password !== $password_confirm) {
            $errors[] = 'password_mismatch';
        }
        
        if (!isset($_POST['terms'])) {
            $errors[] = 'terms_required';
        }
        
        // If errors, redirect back
        if (!empty($errors)) {
            wp_redirect(add_query_arg('error', implode(',', $errors), wp_get_referer()));
            exit;
        }
        
        // Create WordPress user
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('error', 'registration_failed', wp_get_referer()));
            exit;
        }
        
        // Update user meta
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name,
        ]);
        
        update_user_meta($user_id, 'atc_phone', $phone);
        update_user_meta($user_id, 'atc_phone_verified', 0);
        update_user_meta($user_id, 'atc_email_verified', 0); // Keep for backward compatibility
        
        // Create customer record
        if (class_exists('ATC_Customers')) {
            ATC_Customers::get_or_create($email, [
                'name' => $name,
                'phone' => $phone,
                'user_id' => $user_id,
            ]);
        }
        
        // Send OTP for phone verification via WhatsApp
        if (class_exists('ATC_OTP') && !empty($phone)) {
            $otp_result = ATC_OTP::send_otp($phone, 'verification');
            
            if (is_wp_error($otp_result)) {
                // If OTP sending fails, still create account but mark as unverified
                update_user_meta($user_id, 'atc_otp_send_failed', true);
            } else {
                // Store phone for OTP verification
                update_user_meta($user_id, 'atc_pending_verification_phone', $phone);
            }
        }
        
        // Redirect to OTP verification page (using verify-email slug for backward compatibility)
        wp_redirect(add_query_arg([
            'atc_action' => 'verify_phone',
            'phone' => urlencode($phone),
            'registered' => 'success'
        ], home_url('/verify-email/'))); // Using verify-email page slug for backward compatibility
        exit;
    }
    
    /**
     * Handle OTP Phone Verification
     */
    public static function handle_otp_verification() {
        if (!isset($_POST['atc_verify_otp_submit'])) {
            return;
        }
        
        if (!isset($_POST['atc_otp_nonce']) || !wp_verify_nonce($_POST['atc_otp_nonce'], 'atc_verify_phone_otp')) {
            wp_redirect(add_query_arg('error', 'invalid_nonce', wp_get_referer()));
            exit;
        }
        
        $phone = sanitize_text_field($_POST['phone']);
        $otp_code = sanitize_text_field($_POST['otp_code']);
        
        if (empty($phone) || empty($otp_code)) {
            wp_redirect(add_query_arg('error', 'missing_fields', wp_get_referer()));
            exit;
        }
        
        // Verify OTP
        if (!class_exists('ATC_OTP')) {
            wp_redirect(add_query_arg('error', 'otp_system_unavailable', wp_get_referer()));
            exit;
        }
        
        $result = ATC_OTP::verify_otp($phone, $otp_code, 'verification');
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg('error', $result->get_error_code(), wp_get_referer()));
            exit;
        }
        
        // OTP verified - activate user account
        // Find user by phone number
        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'atc_phone' AND meta_value = %s",
            $phone
        ));
        
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                update_user_meta($user->ID, 'atc_phone_verified', 1);
                update_user_meta($user->ID, 'atc_email_verified', 1); // Also mark email as verified
                delete_user_meta($user->ID, 'atc_pending_verification_phone');
                
                // Auto-login user
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                
                // Redirect to account dashboard
                wp_redirect(add_query_arg('verified', 'success', home_url('/my-account/')));
                exit;
            }
        }
        
        wp_redirect(add_query_arg('error', 'user_not_found', wp_get_referer()));
        exit;
    }

    /**
     * ========================================
     * LOGIN (Uses WordPress default with custom redirect)
     * ========================================
     */
    
    /**
     * Login Form Shortcode (Custom - supports email/phone)
     */
    public static function login_form_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<div class="atc-notice atc-notice-info">
                <p>' . __('You are already logged in.', 'advanced-travel-crm') . ' 
                <a href="' . esc_url(home_url('/my-account/')) . '">' . __('Go to Dashboard', 'advanced-travel-crm') . '</a></p>
            </div>';
        }
        
        $atts = shortcode_atts([
            'redirect' => '',
        ], $atts);

        $requested_redirect = '';
        if (isset($_GET['redirect_to'])) {
            $requested_redirect = wp_unslash($_GET['redirect_to']);
        } elseif (!empty($atts['redirect'])) {
            $requested_redirect = $atts['redirect'];
        }

        $redirect_to = self::resolve_redirect_target($requested_redirect);
        
        ob_start();
        ?>
        <div class="atc-premium-auth-form atc-login-form-wrapper">
            <div class="atc-auth-header">
                <h2><?php _e('Welcome Back', 'advanced-travel-crm'); ?></h2>
                <p><?php _e('Login to your account', 'advanced-travel-crm'); ?></p>
            </div>
            
            <?php if (isset($_GET['login_failed'])): ?>
                <div class="atc-message atc-message-error">
                    <p><?php echo esc_html(self::get_error_message($_GET['login_failed'])); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logged_out'])): ?>
                <div class="atc-message atc-message-success">
                    <p><?php _e('You have been logged out successfully.', 'advanced-travel-crm'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['password_reset']) && $_GET['password_reset'] == 'success'): ?>
                <div class="atc-message atc-message-success">
                    <p><?php _e('✅ Password reset successfully! Please login with your new password.', 'advanced-travel-crm'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['verified']) && $_GET['verified'] == 'success'): ?>
                <div class="atc-message atc-message-success">
                    <p><?php _e('✅ Phone verified successfully! Please login.', 'advanced-travel-crm'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="atc-premium-form">
                <?php wp_nonce_field('atc_custom_login', 'atc_login_nonce'); ?>
                <input type="hidden" name="atc_login_submit" value="1">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                
                <div class="atc-premium-form-group">
                    <label for="login_username"><?php _e('Email or Phone Number', 'advanced-travel-crm'); ?> *</label>
                    <input type="text" name="username" id="login_username" required 
                           placeholder="<?php _e('Enter your email or phone number', 'advanced-travel-crm'); ?>"
                           value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>">
                    <small><?php _e('You can login with either your email address or phone number', 'advanced-travel-crm'); ?></small>
                </div>
                
                <div class="atc-premium-form-group">
                    <label for="login_password"><?php _e('Password', 'advanced-travel-crm'); ?> *</label>
                    <input type="password" name="password" id="login_password" required 
                           placeholder="<?php _e('Enter your password', 'advanced-travel-crm'); ?>">
                    <small><a href="<?php echo home_url('/forgot-password/'); ?>"><?php _e('Forgot password?', 'advanced-travel-crm'); ?></a></small>
                </div>
                
                <div class="atc-premium-form-group">
                    <label class="atc-checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span><?php _e('Remember me', 'advanced-travel-crm'); ?></span>
                    </label>
                </div>
                
                <div class="atc-premium-form-actions">
                    <button type="submit" class="atc-btn-premium atc-btn-premium-primary atc-btn-block">
                        <?php _e('Login', 'advanced-travel-crm'); ?>
                    </button>
                </div>
                
                <div class="atc-auth-footer">
                    <p><?php _e('Don\'t have an account?', 'advanced-travel-crm'); ?> 
                       <a href="<?php echo home_url('/register/'); ?>"><?php _e('Create Account', 'advanced-travel-crm'); ?></a>
                    </p>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle Custom Login (with email/phone support)
     */
    public static function handle_custom_login() {
        if (!isset($_POST['atc_login_submit'])) {
            return;
        }
        
        if (!isset($_POST['atc_login_nonce']) || !wp_verify_nonce($_POST['atc_login_nonce'], 'atc_custom_login')) {
            return;
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url('/my-account/');
        
        if (empty($username) || empty($password)) {
            wp_redirect(add_query_arg('login_failed', 'empty_fields', wp_get_referer()));
            exit;
        }
        
        // Detect if input is email or phone
        $user = null;
        if (is_email($username)) {
            // Login with email
            $user = get_user_by('email', $username);
        } else {
            // Login with phone number
            global $wpdb;
            $phone = preg_replace('/[^0-9+]/', '', $username);
            $user_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'atc_phone' AND meta_value = %s",
                $phone
            ));
            
            if ($user_id) {
                $user = get_user_by('id', $user_id);
            }
        }
        
        if (!$user) {
            wp_redirect(add_query_arg('login_failed', 'invalid_credentials', wp_get_referer()));
            exit;
        }
        
        // Verify password
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_redirect(add_query_arg('login_failed', 'invalid_credentials', wp_get_referer()));
            exit;
        }
        
        // Login successful
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        // Update last login
        update_user_meta($user->ID, 'atc_last_login', current_time('mysql'));
        
        // Redirect
        wp_safe_redirect($redirect_to);
        exit;
    }
    
    /**
     * Verify Phone Form Shortcode (WhatsApp OTP Verification)
     * Note: Shortcode name is [atc_verify_email_form] for backward compatibility,
     * but it actually handles WhatsApp OTP phone verification
     */
    public static function verify_email_form_shortcode($atts) {
        // This shortcode handles WhatsApp OTP phone verification (not email)
        // Keeping old name for backward compatibility with existing pages
        if (is_user_logged_in() && get_user_meta(get_current_user_id(), 'atc_phone_verified', true)) {
            return '<div class="atc-notice atc-notice-info">
                <p>' . __('Your phone is already verified.', 'advanced-travel-crm') . ' 
                <a href="' . esc_url(home_url('/my-account/')) . '">' . __('Go to Dashboard', 'advanced-travel-crm') . '</a></p>
            </div>';
        }
        
        $phone = isset($_GET['phone']) ? sanitize_text_field(urldecode($_GET['phone'])) : '';
        
        if (empty($phone)) {
            return '<div class="atc-message atc-message-error">
                <p>' . __('Phone number is required for verification.', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        ob_start();
        ?>
        <div class="atc-premium-auth-form atc-otp-verification-wrapper">
            <div class="atc-auth-header">
                <h2><?php _e('Verify Your Phone', 'advanced-travel-crm'); ?></h2>
                <p><?php printf(__('We sent a 6-digit code to %s via WhatsApp', 'advanced-travel-crm'), '<strong>' . esc_html($phone) . '</strong>'); ?></p>
            </div>
            
            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                <div class="atc-message atc-message-success">
                    <p><?php _e('✅ Registration successful! Please check your WhatsApp for the OTP code.', 'advanced-travel-crm'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="atc-message atc-message-error">
                    <p><?php echo esc_html(self::get_error_message($_GET['error'])); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['otp_resent'])): ?>
                <div class="atc-message atc-message-success">
                    <p><?php _e('✅ New OTP code sent! Please check your WhatsApp.', 'advanced-travel-crm'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="atc-premium-form" id="atc-otp-verification-form">
                <?php wp_nonce_field('atc_verify_phone_otp', 'atc_otp_nonce'); ?>
                <input type="hidden" name="atc_verify_otp_submit" value="1">
                <input type="hidden" name="phone" value="<?php echo esc_attr($phone); ?>">
                
                <div class="atc-premium-form-group atc-otp-input-group">
                    <label for="otp_code"><?php _e('Enter 6-Digit OTP Code', 'advanced-travel-crm'); ?> *</label>
                    <input type="text" name="otp_code" id="otp_code" 
                           maxlength="6" pattern="[0-9]{6}" 
                           placeholder="000000" required autocomplete="off"
                           class="atc-otp-input">
                    <small><?php _e('Enter the 6-digit code sent to your WhatsApp', 'advanced-travel-crm'); ?></small>
                </div>
                
                <div class="atc-premium-form-actions">
                    <button type="submit" class="atc-btn-premium atc-btn-premium-primary atc-btn-block">
                        <?php _e('Verify Phone', 'advanced-travel-crm'); ?>
                    </button>
                </div>
                
                <div class="atc-auth-footer">
                    <p><?php _e('Didn\'t receive the code?', 'advanced-travel-crm'); ?></p>
                    <button type="button" class="atc-btn-premium atc-btn-premium-text atc-resend-otp-btn" 
                            data-phone="<?php echo esc_attr($phone); ?>">
                        <?php _e('Resend OTP', 'advanced-travel-crm'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Resend Verification OTP
     */
    public static function ajax_resend_verification_otp() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        if (empty($phone)) {
            wp_send_json_error(['message' => __('Phone number required', 'advanced-travel-crm')]);
        }
        
        if (!class_exists('ATC_OTP')) {
            wp_send_json_error(['message' => __('OTP system unavailable', 'advanced-travel-crm')]);
        }
        
        $result = ATC_OTP::resend_otp($phone, 'verification');
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('New OTP code sent! Please check your WhatsApp.', 'advanced-travel-crm')]);
    }

    /**
     * ========================================
     * ACCOUNT DASHBOARD
     * ========================================
     */
    
    /**
     * Account Dashboard Shortcode
     */
    public static function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="atc-notice atc-notice-warning">
                <p>' . __('Please', 'advanced-travel-crm') . ' 
                <a href="' . esc_url(home_url('/login/')) . '">' . __('login', 'advanced-travel-crm') . '</a> 
                ' . __('to view your account.', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        // Check if functions exist before calling
        if (!function_exists('wp_get_current_user') || !function_exists('get_current_user_id')) {
            return '<div class="atc-notice atc-notice-error">
                <p>' . __('User functions not available.', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        $user = wp_get_current_user();
        $user_id = get_current_user_id();
        
        // Get user stats
        global $wpdb;
        $total_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ATC_TABLE_BOOKINGS . " WHERE user_id = %d",
            $user_id
        ));
        
        // Calculate total spent - handle NULL values and ensure proper calculation
        $total_spent = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(CAST(price_total AS DECIMAL(12,2))), 0) FROM " . ATC_TABLE_BOOKINGS . " 
            WHERE user_id = %d AND (payment_status = 'paid' OR payment_status = 'completed')",
            $user_id
        ));
        
        // Fallback: if still 0, try without payment_status filter (for testing)
        if (empty($total_spent) || $total_spent == 0) {
            $total_spent = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(CAST(price_total AS DECIMAL(12,2))), 0) FROM " . ATC_TABLE_BOOKINGS . " 
                WHERE user_id = %d",
                $user_id
            ));
        }
        
        $total_spent = floatval($total_spent) ?: 0;
        
        // Get recent bookings
        $bookings = ATC_Bookings::get_bookings_by_user($user_id);
        
        ob_start();
        ?>
        <div class="atc-account-dashboard">
            <!-- Dashboard Header -->
            <div class="atc-dashboard-header">
                <div class="atc-dashboard-welcome">
                    <h1><?php printf(__('Welcome back, %s!', 'advanced-travel-crm'), esc_html($user->display_name)); ?></h1>
                    <p><?php _e('Manage your bookings and account settings', 'advanced-travel-crm'); ?></p>
                </div>
                <div class="atc-dashboard-actions">
                    <a href="<?php echo add_query_arg('tab', 'profile'); ?>" class="atc-btn atc-btn-outline">
                        <?php _e('Edit Profile', 'advanced-travel-crm'); ?>
                    </a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="atc-btn atc-btn-text">
                        <?php _e('Logout', 'advanced-travel-crm'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="atc-dashboard-stats">
                <div class="atc-stat-card">
                    <div class="atc-stat-icon">📋</div>
                    <div class="atc-stat-content">
                        <h3><?php echo number_format($total_bookings); ?></h3>
                        <p><?php _e('Total Bookings', 'advanced-travel-crm'); ?></p>
                    </div>
                </div>
                
                <div class="atc-stat-card">
                    <div class="atc-stat-icon">💰</div>
                    <div class="atc-stat-content">
                        <h3><?php echo get_option('atc_currency_symbol', '₹') . number_format($total_spent ?: 0, 2); ?></h3>
                        <p><?php _e('Total Spent', 'advanced-travel-crm'); ?></p>
                    </div>
                </div>
                
                <div class="atc-stat-card">
                    <div class="atc-stat-icon">⭐</div>
                    <div class="atc-stat-content">
                        <h3><?php echo esc_html(ucfirst(self::get_customer_tier($total_bookings))); ?></h3>
                        <p><?php _e('Member Tier', 'advanced-travel-crm'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Navigation -->
            <div class="atc-dashboard-tabs">
                <a href="?tab=bookings" class="atc-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'bookings') ? 'active' : ''; ?>">
                    <?php _e('My Bookings', 'advanced-travel-crm'); ?>
                </a>
                <a href="?tab=profile" class="atc-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'profile') ? 'active' : ''; ?>">
                    <?php _e('Profile', 'advanced-travel-crm'); ?>
                </a>
                <a href="?tab=password" class="atc-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'password') ? 'active' : ''; ?>">
                    <?php _e('Password', 'advanced-travel-crm'); ?>
                </a>
            </div>
            
            <!-- Tab Content -->
            <div class="atc-dashboard-content">
                <?php
                $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'bookings';
                
                switch ($tab) {
                    case 'profile':
                        echo self::profile_form_shortcode([]);
                        break;
                    
                    case 'password':
                        echo self::password_form_shortcode([]);
                        break;
                    
                    case 'bookings':
                    default:
                        self::render_bookings_tab($bookings);
                        break;
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render bookings tab
     */
    private static function render_bookings_tab($bookings) {
        ?>
        <div class="atc-bookings-section">
            <h2><?php _e('Your Bookings', 'advanced-travel-crm'); ?></h2>
            
            <?php if (empty($bookings)): ?>
                <div class="atc-empty-state">
                    <div class="atc-empty-icon">📭</div>
                    <h3><?php _e('No bookings yet', 'advanced-travel-crm'); ?></h3>
                    <p><?php _e('Start planning your next adventure!', 'advanced-travel-crm'); ?></p>
                    <a href="<?php echo home_url(); ?>" class="atc-btn atc-btn-primary">
                        <?php _e('Browse Services', 'advanced-travel-crm'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="atc-bookings-table-wrapper">
                    <table class="atc-bookings-table">
                        <thead>
                            <tr>
                                <th><?php _e('Booking ID', 'advanced-travel-crm'); ?></th>
                                <th><?php _e('Service', 'advanced-travel-crm'); ?></th>
                                <th><?php _e('Date', 'advanced-travel-crm'); ?></th>
                                <th><?php _e('Amount', 'advanced-travel-crm'); ?></th>
                                <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                                <th><?php _e('Actions', 'advanced-travel-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): 
                                // Handle both array and object formats
                                $booking_id = is_array($booking) ? ($booking['booking_id'] ?? '') : ($booking->booking_id ?? '');
                                $id = is_array($booking) ? ($booking['id'] ?? 0) : ($booking->id ?? 0);
                                $service = is_array($booking) ? ($booking['service'] ?? '') : ($booking->service ?? '');
                                $created_at = is_array($booking) ? ($booking['created_at'] ?? '') : ($booking->created_at ?? '');
                                $currency = is_array($booking) ? ($booking['currency'] ?? 'INR') : ($booking->currency ?? 'INR');
                                $price_total = is_array($booking) ? (floatval($booking['price_total'] ?? 0)) : (floatval($booking->price_total ?? 0));
                                $status = is_array($booking) ? ($booking['status'] ?? 'pending') : ($booking->status ?? 'pending');
                                
                                // Generate booking ID if empty
                                if (empty($booking_id) && $id) {
                                    $booking_id = 'ATC-' . str_pad($id, 6, '0', STR_PAD_LEFT);
                                } elseif (empty($booking_id)) {
                                    $booking_id = 'N/A';
                                }
                                
                                // Format date properly
                                $formatted_date = __('N/A', 'advanced-travel-crm');
                                if (!empty($created_at)) {
                                    $timestamp = strtotime($created_at);
                                    if ($timestamp !== false && $timestamp > 0) {
                                        $formatted_date = date('M d, Y', $timestamp);
                                    }
                                }
                            ?>
                            <tr>
                                <td><strong style="color: #0A1F44; font-size: 15px;"><?php echo esc_html($booking_id); ?></strong></td>
                                <td><?php echo esc_html(!empty($service) ? ucfirst($service) : __('N/A', 'advanced-travel-crm')); ?></td>
                                <td><?php echo esc_html($formatted_date); ?></td>
                                <td><strong><?php echo esc_html($currency . ' ' . number_format($price_total, 2)); ?></strong></td>
                                <td>
                                    <span class="atc-status-badge atc-status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html(!empty($status) ? ucfirst($status) : __('Pending', 'advanced-travel-crm')); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="atc-booking-actions">
                                        <button class="atc-btn atc-btn-small atc-btn-outline atc-view-booking" 
                                                data-booking-id="<?php echo esc_attr($id); ?>">
                                            <?php _e('View', 'advanced-travel-crm'); ?>
                                        </button>
                                        
                                        <?php if ($status === 'pending'): ?>
                                        <button class="atc-btn atc-btn-small atc-btn-danger atc-cancel-booking" 
                                                data-booking-id="<?php echo esc_attr($id); ?>">
                                            <?php _e('Cancel', 'advanced-travel-crm'); ?>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * ========================================
     * PROFILE MANAGEMENT
     * ========================================
     */
    
    /**
     * Profile Form Shortcode
     */
    public static function profile_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return self::login_required_message();
        }
        
        // Check if function exists before calling
        if (!function_exists('wp_get_current_user')) {
            return '<div class="atc-notice atc-notice-error">
                <p>' . __('User functions not available.', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        $user = wp_get_current_user();
        $phone = get_user_meta($user->ID, 'atc_phone', true);
        
        ob_start();
        ?>
        <div class="atc-profile-form-wrapper">
            <h2><?php _e('Profile Information', 'advanced-travel-crm'); ?></h2>
            
            <?php if (isset($_GET['profile_updated']) && $_GET['profile_updated'] == 'success'): ?>
                <div class="atc-message atc-message-success">
                    <p><?php _e('✅ Profile updated successfully!', 'advanced-travel-crm'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="atc-message atc-message-error">
                    <p><?php echo esc_html(self::get_error_message($_GET['error'])); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="atc-form">
                <?php wp_nonce_field('atc_profile_update', 'atc_profile_nonce'); ?>
                <input type="hidden" name="atc_profile_submit" value="1">
                
                <div class="atc-form-row">
                    <div class="atc-form-field">
                        <label for="profile_name"><?php _e('Full Name', 'advanced-travel-crm'); ?> *</label>
                        <input type="text" name="name" id="profile_name" required 
                               value="<?php echo esc_attr($user->display_name); ?>">
                    </div>
                </div>
                
                <div class="atc-form-row">
                    <div class="atc-form-field">
                        <label for="profile_email"><?php _e('Email Address', 'advanced-travel-crm'); ?></label>
                        <input type="email" id="profile_email" value="<?php echo esc_attr($user->user_email); ?>" disabled>
                        <small><?php _e('Email cannot be changed', 'advanced-travel-crm'); ?></small>
                    </div>
                </div>
                
                <div class="atc-form-row">
                    <div class="atc-form-field">
                        <label for="profile_phone"><?php _e('Phone Number', 'advanced-travel-crm'); ?></label>
                        <input type="tel" name="phone" id="profile_phone" placeholder="+919876543210"
                               value="<?php echo esc_attr($phone); ?>">
                    </div>
                </div>
                
                <div class="atc-form-actions">
                    <button type="submit" class="atc-btn atc-btn-primary">
                        <?php _e('Update Profile', 'advanced-travel-crm'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle Profile Update
     */
    public static function handle_profile_update() {
        if (!isset($_POST['atc_profile_submit'])) {
            return;
        }
        
        if (!isset($_POST['atc_profile_nonce']) || !wp_verify_nonce($_POST['atc_profile_nonce'], 'atc_profile_update')) {
            return;
        }
        
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $name = sanitize_text_field($_POST['name']);
        $phone = sanitize_text_field($_POST['phone']);
        
        // Update user
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name,
        ]);
        
        update_user_meta($user_id, 'atc_phone', $phone);
        
        // Update customer record - Check if function exists
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            if (class_exists('ATC_Customers')) {
                global $wpdb;
                $wpdb->update(
                    ATC_TABLE_CUSTOMERS,
                    ['name' => $name, 'phone' => $phone],
                    ['email' => $user->user_email]
                );
            }
        }
        
        wp_redirect(add_query_arg('profile_updated', 'success', wp_get_referer()));
        exit;
    }

    /**
     * ========================================
     * PASSWORD CHANGE
     * ========================================
     */
    
    /**
     * Password Change Form Shortcode
     */
    public static function password_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return self::login_required_message();
        }
        
        ob_start();
        ?>
        <div class="atc-password-form-wrapper">
            <h2><?php _e('Change Password', 'advanced-travel-crm'); ?></h2>
            
            <?php if (isset($_GET['password_changed']) && $_GET['password_changed'] == 'success'): ?>
                <div class="atc-message atc-message-success">
                    <p><?php _e('✅ Password changed successfully!', 'advanced-travel-crm'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="atc-message atc-message-error">
                    <p><?php echo esc_html(self::get_error_message($_GET['error'])); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="atc-form">
                <?php wp_nonce_field('atc_password_change', 'atc_password_nonce'); ?>
                <input type="hidden" name="atc_password_submit" value="1">
                
                <div class="atc-form-row">
                    <div class="atc-form-field">
                        <label for="current_password"><?php _e('Current Password', 'advanced-travel-crm'); ?> *</label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>
                </div>
                
                <div class="atc-form-row">
                    <div class="atc-form-field">
                        <label for="new_password"><?php _e('New Password', 'advanced-travel-crm'); ?> *</label>
                        <input type="password" name="new_password" id="new_password" required minlength="6">
                        <small><?php _e('Minimum 6 characters', 'advanced-travel-crm'); ?></small>
                    </div>
                </div>
                
                <div class="atc-form-row">
                    <div class="atc-form-field">
                        <label for="confirm_password"><?php _e('Confirm New Password', 'advanced-travel-crm'); ?> *</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                    </div>
                </div>
                
                <div class="atc-form-actions">
                    <button type="submit" class="atc-btn atc-btn-primary">
                        <?php _e('Change Password', 'advanced-travel-crm'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle Password Change
     */
    public static function handle_password_change() {
        if (!isset($_POST['atc_password_submit'])) {
            return;
        }
        
        if (!isset($_POST['atc_password_nonce']) || !wp_verify_nonce($_POST['atc_password_nonce'], 'atc_password_change')) {
            return;
        }
        
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        $user = get_user_by('id', $user_id);
        
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            wp_redirect(add_query_arg('error', 'current_password_wrong', wp_get_referer()));
            exit;
        }
        
        if (strlen($new_password) < 6) {
            wp_redirect(add_query_arg('error', 'password_short', wp_get_referer()));
            exit;
        }
        
        if ($new_password !== $confirm_password) {
            wp_redirect(add_query_arg('error', 'password_mismatch', wp_get_referer()));
            exit;
        }
        
        // Update password
        wp_set_password($new_password, $user_id);
        
        // Re-authenticate user
        wp_set_auth_cookie($user_id);
        
        wp_redirect(add_query_arg('password_changed', 'success', wp_get_referer()));
        exit;
    }

    /**
     * ========================================
     * LOGOUT
     * ========================================
     */
    
    /**
     * Logout Button Shortcode
     */
    public static function logout_button_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $atts = shortcode_atts([
            'text' => __('Logout', 'advanced-travel-crm'),
            'class' => 'atc-btn atc-btn-text',
            'redirect' => home_url(),
        ], $atts);
        
        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            esc_url(wp_logout_url($atts['redirect'])),
            esc_attr($atts['class']),
            esc_html($atts['text'])
        );
    }
    
    /**
     * Handle Logout
     */
    public static function handle_logout() {
        // WordPress handles logout, we just customize redirect if needed
        if (isset($_GET['atc_logout'])) {
            wp_logout();
            wp_redirect(home_url());
            exit;
        }
    }

    /**
     * ========================================
     * ACCOUNT MENU
     * ========================================
     */
    
    /**
     * Account Menu Shortcode
     * Renders a compact premium login button when the visitor is logged out.
     * Account navigation/dropdowns have been removed per the latest UX brief.
     */
    public static function account_menu_shortcode($atts) {
        if (is_user_logged_in()) {
            /**
             * Account navigation has been intentionally removed per design request.
             * Logged-in users should not see any account controls in this slot.
             */
            return '';
        }

        $atts = shortcode_atts([
            'redirect' => '',
            'label' => __('Login', 'advanced-travel-crm'),
        ], $atts);

        $login_url = home_url('/login/');
        $current_page = self::get_current_url();
        $preferred_redirect = !empty($atts['redirect']) ? esc_url_raw($atts['redirect']) : $current_page;

        if (!empty($preferred_redirect)) {
            $login_url = add_query_arg('redirect_to', $preferred_redirect, $login_url);
        }
        
        ob_start();
        ?>
        <div class="atc-account-menu-wrapper atc-account-menu-login-only" id="atc-account-menu-wrapper">
                <a href="<?php echo esc_url($login_url); ?>" 
                   class="atc-profile-menu-trigger atc-profile-menu-login-btn" 
                   id="atc-profile-menu-trigger"
               aria-label="<?php echo esc_attr($atts['label']); ?>">
                <span class="atc-menu-icon" aria-hidden="true">👤</span>
                <span class="atc-profile-name"><?php echo esc_html($atts['label']); ?></span>
                </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * ========================================
     * AJAX HANDLERS
     * ========================================
     */
    
    /**
     * AJAX: Get Booking Details
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
        
        // Verify user owns this booking
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d AND user_id = %d",
            $booking_id,
            get_current_user_id()
        ), ARRAY_A);
        
        if (!$booking) {
            wp_send_json_error(['message' => __('Booking not found', 'advanced-travel-crm')]);
        }
        
        // Parse form data if exists
        if (!empty($booking['form_data'])) {
            $booking['form_data_parsed'] = maybe_unserialize($booking['form_data']);
        }
        
        // Format booking ID if empty
        if (empty($booking['booking_id'])) {
            $booking['booking_id'] = 'ATC-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
        }
        
        // Format date
        if (!empty($booking['created_at'])) {
            $timestamp = strtotime($booking['created_at']);
            if ($timestamp !== false && $timestamp > 0) {
                $booking['created_at_formatted'] = date('M d, Y', $timestamp);
            } else {
                $booking['created_at_formatted'] = __('N/A', 'advanced-travel-crm');
            }
        } else {
            $booking['created_at_formatted'] = __('N/A', 'advanced-travel-crm');
        }
        
        wp_send_json_success($booking);
    }
    
    /**
     * AJAX: Resend Email
     */
    public static function ajax_resend_email() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please login', 'advanced-travel-crm')]);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => __('Invalid booking', 'advanced-travel-crm')]);
        }
        
        // Verify user owns this booking
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d AND user_id = %d",
            $booking_id,
            get_current_user_id()
        ), ARRAY_A);
        
        if (!$booking) {
            wp_send_json_error(['message' => __('Booking not found', 'advanced-travel-crm')]);
        }
        
        // Resend email
        if (class_exists('ATC_Notification_Manager')) {
            ATC_Notification_Manager::on_booking_created($booking_id, $booking);
            wp_send_json_success(['message' => __('Email sent successfully!', 'advanced-travel-crm')]);
        }
        
        wp_send_json_error(['message' => __('Failed to send email', 'advanced-travel-crm')]);
    }
    
    /**
     * AJAX: Cancel Booking
     */
    public static function ajax_cancel_booking() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please login', 'advanced-travel-crm')]);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => __('Invalid booking', 'advanced-travel-crm')]);
        }
        
        // Verify user owns this booking
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d AND user_id = %d",
            $booking_id,
            get_current_user_id()
        ), ARRAY_A);
        
        if (!$booking) {
            wp_send_json_error(['message' => __('Booking not found', 'advanced-travel-crm')]);
        }
        
        if ($booking['status'] !== 'pending') {
            wp_send_json_error(['message' => __('Only pending bookings can be cancelled', 'advanced-travel-crm')]);
        }
        
        // Update booking status
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            ['status' => 'cancelled', 'updated_at' => current_time('mysql')],
            ['id' => $booking_id]
        );
        
        // Trigger cancellation notification
        do_action('atc_booking_cancelled', $booking_id, $booking);
        
        wp_send_json_success(['message' => __('Booking cancelled successfully', 'advanced-travel-crm')]);
    }
    
    /**
     * ========================================
     * PASSWORD RESET (FORGOT PASSWORD)
     * ========================================
     */
    
    /**
     * Forgot Password Form Shortcode
     */
    public static function forgot_password_form_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<div class="atc-notice atc-notice-info">
                <p>' . __('You are already logged in.', 'advanced-travel-crm') . ' 
                <a href="' . esc_url(wp_logout_url(home_url())) . '">' . __('Logout', 'advanced-travel-crm') . '</a></p>
            </div>';
        }
        
        ob_start();
        ?>
        <div class="atc-premium-auth-form">
            <div class="atc-auth-header">
                <h2><?php _e('🔐 Forgot Password?', 'advanced-travel-crm'); ?></h2>
                <p><?php _e('Enter your phone number and we\'ll send you an OTP via WhatsApp to reset your password', 'advanced-travel-crm'); ?></p>
            </div>
            
            <?php if (isset($_GET['otp_sent']) && $_GET['otp_sent'] == 'success'): ?>
                <div class="atc-message atc-message-success">
                    <p><?php _e('✅ OTP sent successfully! Please check your WhatsApp.', 'advanced-travel-crm'); ?></p>
                </div>
                <?php
                // Show OTP verification form
                $phone = isset($_GET['phone']) ? sanitize_text_field($_GET['phone']) : '';
                if ($phone) {
                    echo self::password_reset_otp_form_shortcode(['phone' => $phone]);
                }
                ?>
            <?php else: ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="atc-message atc-message-error">
                        <p><?php echo esc_html(self::get_error_message($_GET['error'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" class="atc-premium-form">
                    <?php wp_nonce_field('atc_forgot_password', 'atc_forgot_password_nonce'); ?>
                    <input type="hidden" name="atc_forgot_password_submit" value="1">
                    
                    <div class="atc-premium-form-group">
                        <label for="forgot_phone"><?php _e('Phone Number', 'advanced-travel-crm'); ?> *</label>
                        <input type="tel" name="phone" id="forgot_phone" required 
                               placeholder="<?php _e('Enter your registered phone number', 'advanced-travel-crm'); ?>"
                               value="<?php echo isset($_GET['phone']) ? esc_attr($_GET['phone']) : ''; ?>">
                        <small><?php _e('We\'ll send a 6-digit OTP code to this phone via WhatsApp', 'advanced-travel-crm'); ?></small>
                    </div>
                    
                    <div class="atc-premium-form-actions">
                        <button type="submit" class="atc-btn-premium atc-btn-premium-primary atc-btn-block">
                            <?php _e('Send OTP', 'advanced-travel-crm'); ?>
                        </button>
                    </div>
                    
                    <div class="atc-auth-footer">
                        <p><?php _e('Remember your password?', 'advanced-travel-crm'); ?> 
                           <a href="<?php echo home_url('/login/'); ?>"><?php _e('Login', 'advanced-travel-crm'); ?></a>
                        </p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Password Reset OTP Verification Form
     */
    public static function password_reset_otp_form_shortcode($atts) {
        $phone = isset($atts['phone']) ? sanitize_text_field($atts['phone']) : (isset($_GET['phone']) ? sanitize_text_field($_GET['phone']) : '');
        
        if (empty($phone)) {
            return '<div class="atc-message atc-message-error">
                <p>' . __('Phone number is required', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        ob_start();
        ?>
        <div class="atc-premium-auth-form" style="margin-top: 30px;">
            <div class="atc-auth-header">
                <h3><?php _e('🔑 Verify OTP', 'advanced-travel-crm'); ?></h3>
                <p><?php printf(__('Enter the 6-digit code sent to %s via WhatsApp', 'advanced-travel-crm'), '<strong>' . esc_html($phone) . '</strong>'); ?></p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="atc-message atc-message-error">
                    <p><?php echo esc_html(self::get_error_message($_GET['error'])); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['otp_verified']) && $_GET['otp_verified'] == 'success'): ?>
                <?php
                // Show password reset form
                echo self::reset_password_form_shortcode(['phone' => $phone]);
                ?>
            <?php else: ?>
                <form method="post" action="" class="atc-premium-form">
                    <?php wp_nonce_field('atc_password_reset_otp', 'atc_password_reset_otp_nonce'); ?>
                    <input type="hidden" name="atc_password_reset_otp_submit" value="1">
                    <input type="hidden" name="phone" value="<?php echo esc_attr($phone); ?>">
                    
                    <div class="atc-premium-form-group">
                        <label for="reset_otp"><?php _e('Enter OTP Code', 'advanced-travel-crm'); ?> *</label>
                        <div class="atc-otp-input-group">
                            <input type="text" name="otp" id="reset_otp" 
                                   class="atc-otp-input" 
                                   maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="000000" required autocomplete="off">
                        </div>
                        <small><?php _e('Enter the 6-digit code from your WhatsApp', 'advanced-travel-crm'); ?></small>
                    </div>
                    
                    <div class="atc-premium-form-actions">
                        <button type="submit" class="atc-btn-premium atc-btn-premium-primary atc-btn-block">
                            <?php _e('Verify OTP', 'advanced-travel-crm'); ?>
                        </button>
                    </div>
                    
                    <div class="atc-auth-footer">
                        <p><?php _e('Didn\'t receive the code?', 'advanced-travel-crm'); ?></p>
                        <button type="button" class="atc-btn-premium atc-btn-premium-text atc-resend-password-reset-otp" 
                                data-phone="<?php echo esc_attr($phone); ?>">
                            <?php _e('Resend OTP', 'advanced-travel-crm'); ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto-focus OTP input
            $('#reset_otp').focus();
            
            // Auto-submit when 6 digits entered
            $('#reset_otp').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {
                    $(this).closest('form').submit();
                }
            });
            
            // Handle paste
            $('#reset_otp').on('paste', function(e) {
                var pastedData = (e.originalEvent || e).clipboardData.getData('text/plain');
                var numbers = pastedData.replace(/[^0-9]/g, '');
                if (numbers.length >= 6) {
                    this.value = numbers.substring(0, 6);
                    $(this).closest('form').submit();
                }
            });
            
            // Resend OTP
            $('.atc-resend-password-reset-otp').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var phone = btn.data('phone');
                
                btn.prop('disabled', true).text('<?php _e('Sending...', 'advanced-travel-crm'); ?>');
                
                $.ajax({
                    url: atcAccount.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'atc_resend_password_reset_otp',
                        nonce: atcAccount.nonce,
                        phone: phone
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('New OTP sent! Please check your WhatsApp.', 'advanced-travel-crm'); ?>');
                            btn.text('<?php _e('Resend OTP', 'advanced-travel-crm'); ?>');
                            // Enable after 60 seconds
                            setTimeout(function() {
                                btn.prop('disabled', false);
                            }, 60000);
                        } else {
                            alert(response.data.message || '<?php _e('Failed to send OTP. Please try again.', 'advanced-travel-crm'); ?>');
                            btn.prop('disabled', false).text('<?php _e('Resend OTP', 'advanced-travel-crm'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred. Please try again.', 'advanced-travel-crm'); ?>');
                        btn.prop('disabled', false).text('<?php _e('Resend OTP', 'advanced-travel-crm'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Reset Password Form Shortcode
     */
    public static function reset_password_form_shortcode($atts) {
        $phone = isset($atts['phone']) ? sanitize_text_field($atts['phone']) : (isset($_GET['phone']) ? sanitize_text_field($_GET['phone']) : '');
        
        if (empty($phone)) {
            return '<div class="atc-message atc-message-error">
                <p>' . __('Phone number is required', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        // Check if OTP is verified
        $otp_verified = get_transient('atc_password_reset_verified_' . md5($phone));
        if (!$otp_verified) {
            return '<div class="atc-message atc-message-error">
                <p>' . __('Please verify OTP first', 'advanced-travel-crm') . '</p>
            </div>';
        }
        
        ob_start();
        ?>
        <div class="atc-premium-auth-form" style="margin-top: 30px;">
            <div class="atc-auth-header">
                <h3><?php _e('🔄 Reset Password', 'advanced-travel-crm'); ?></h3>
                <p><?php _e('Enter your new password', 'advanced-travel-crm'); ?></p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="atc-message atc-message-error">
                    <p><?php echo esc_html(self::get_error_message($_GET['error'])); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="atc-premium-form">
                <?php wp_nonce_field('atc_password_reset', 'atc_password_reset_nonce'); ?>
                <input type="hidden" name="atc_password_reset_submit" value="1">
                <input type="hidden" name="phone" value="<?php echo esc_attr($phone); ?>">
                
                <div class="atc-premium-form-group">
                    <label for="new_password"><?php _e('New Password', 'advanced-travel-crm'); ?> *</label>
                    <input type="password" name="new_password" id="new_password" required minlength="6"
                           placeholder="<?php _e('Enter new password', 'advanced-travel-crm'); ?>">
                    <small><?php _e('Minimum 6 characters', 'advanced-travel-crm'); ?></small>
                </div>
                
                <div class="atc-premium-form-group">
                    <label for="confirm_password"><?php _e('Confirm New Password', 'advanced-travel-crm'); ?> *</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                           placeholder="<?php _e('Confirm new password', 'advanced-travel-crm'); ?>">
                </div>
                
                <div class="atc-premium-form-actions">
                    <button type="submit" class="atc-btn-premium atc-btn-premium-primary atc-btn-block">
                        <?php _e('Reset Password', 'advanced-travel-crm'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle Forgot Password Request (WhatsApp OTP via Phone)
     */
    public static function handle_forgot_password() {
        if (!isset($_POST['atc_forgot_password_submit'])) {
            return;
        }
        
        if (!isset($_POST['atc_forgot_password_nonce']) || !wp_verify_nonce($_POST['atc_forgot_password_nonce'], 'atc_forgot_password')) {
            wp_redirect(add_query_arg('error', 'invalid_nonce', wp_get_referer()));
            exit;
        }
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        // Clean phone number
        if (!empty($phone)) {
            $phone = preg_replace('/[^0-9+]/', '', $phone);
        }
        
        if (empty($phone) || strlen($phone) < 10) {
            wp_redirect(add_query_arg('error', 'phone_invalid', wp_get_referer()));
            exit;
        }
        
        // Check if user exists with this phone number
        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'atc_phone' AND meta_value = %s",
            $phone
        ));
        
        // Don't reveal if phone exists for security - always show success message
        // But only send OTP if user exists
        if ($user_id) {
            // Send OTP for password reset via WhatsApp
            if (!class_exists('ATC_OTP')) {
                wp_redirect(add_query_arg('error', 'otp_system_unavailable', wp_get_referer()));
                exit;
            }
            
            $otp_result = ATC_OTP::send_otp($phone, 'password_reset');
            if (is_wp_error($otp_result)) {
                $error_code = $otp_result->get_error_code();
                wp_redirect(add_query_arg([
                    'error' => $error_code,
                    'phone' => urlencode($phone)
                ], wp_get_referer()));
                exit;
            }
        }
        
        // Always redirect with success (security: don't reveal if phone exists)
        // Redirect to OTP verification
        wp_redirect(add_query_arg([
            'otp_sent' => 'success',
            'phone' => urlencode($phone)
        ], home_url('/forgot-password/')));
        exit;
    }
    
    /**
     * Handle Password Reset OTP Verification
     */
    public static function handle_password_reset_otp() {
        if (!isset($_POST['atc_password_reset_otp_submit'])) {
            return;
        }
        
        if (!isset($_POST['atc_password_reset_otp_nonce']) || !wp_verify_nonce($_POST['atc_password_reset_otp_nonce'], 'atc_password_reset_otp')) {
            wp_redirect(add_query_arg('error', 'invalid_nonce', wp_get_referer()));
            exit;
        }
        
        $phone = sanitize_text_field($_POST['phone']);
        $otp = sanitize_text_field($_POST['otp']);
        
        if (empty($phone)) {
            wp_redirect(add_query_arg('error', 'phone_invalid', wp_get_referer()));
            exit;
        }
        
        // Verify OTP
        if (!class_exists('ATC_OTP')) {
            wp_redirect(add_query_arg('error', 'otp_system_unavailable', wp_get_referer()));
            exit;
        }
        
        $verify_result = ATC_OTP::verify_otp($phone, $otp, 'password_reset');
        if (is_wp_error($verify_result)) {
            $error_code = $verify_result->get_error_code();
            wp_redirect(add_query_arg([
                'error' => $error_code,
                'phone' => urlencode($phone)
            ], wp_get_referer()));
            exit;
        }
        
        // Mark OTP as verified (valid for 10 minutes)
        set_transient('atc_password_reset_verified_' . md5($phone), true, 600);
        
        // Redirect to password reset form
        wp_redirect(add_query_arg([
            'otp_verified' => 'success',
            'phone' => urlencode($phone)
        ], home_url('/forgot-password/')));
        exit;
    }
    
    /**
     * Handle Password Reset
     */
    public static function handle_password_reset() {
        if (!isset($_POST['atc_password_reset_submit'])) {
            return;
        }
        
        if (!isset($_POST['atc_password_reset_nonce']) || !wp_verify_nonce($_POST['atc_password_reset_nonce'], 'atc_password_reset')) {
            wp_redirect(add_query_arg('error', 'invalid_nonce', wp_get_referer()));
            exit;
        }
        
        $phone = sanitize_text_field($_POST['phone']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($phone)) {
            wp_redirect(add_query_arg('error', 'phone_invalid', wp_get_referer()));
            exit;
        }
        
        // Check if OTP is verified
        $otp_verified = get_transient('atc_password_reset_verified_' . md5($phone));
        if (!$otp_verified) {
            wp_redirect(add_query_arg([
                'error' => 'otp_not_verified',
                'phone' => urlencode($phone)
            ], home_url('/forgot-password/')));
            exit;
        }
        
        // Validate password
        if (strlen($new_password) < 6) {
            wp_redirect(add_query_arg([
                'error' => 'password_short',
                'phone' => urlencode($phone)
            ], wp_get_referer()));
            exit;
        }
        
        if ($new_password !== $confirm_password) {
            wp_redirect(add_query_arg([
                'error' => 'password_mismatch',
                'phone' => urlencode($phone)
            ], wp_get_referer()));
            exit;
        }
        
        // Get user by phone
        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'atc_phone' AND meta_value = %s",
            $phone
        ));
        
        if (!$user_id) {
            wp_redirect(add_query_arg('error', 'user_not_found', wp_get_referer()));
            exit;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_redirect(add_query_arg('error', 'user_not_found', wp_get_referer()));
            exit;
        }
        
        // Reset password
        wp_set_password($new_password, $user->ID);
        
        // Delete verification transient
        delete_transient('atc_password_reset_verified_' . md5($phone));
        delete_transient('atc_password_reset_phone_' . md5($phone));
        
        // Redirect to login with success message
        wp_redirect(add_query_arg('password_reset', 'success', home_url('/login/')));
        exit;
    }
    
    /**
     * AJAX: Resend Password Reset OTP
     */
    public static function ajax_resend_password_reset_otp() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        if (empty($phone)) {
            wp_send_json_error(['message' => __('Phone number required', 'advanced-travel-crm')]);
        }
        
        if (!class_exists('ATC_OTP')) {
            wp_send_json_error(['message' => __('OTP system unavailable', 'advanced-travel-crm')]);
        }
        
        $result = ATC_OTP::resend_otp($phone, 'password_reset');
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('New OTP sent! Please check your WhatsApp.', 'advanced-travel-crm')]);
    }
    
    /**
     * ========================================
     * HELPER FUNCTIONS
     * ========================================
     */
    
    /**
     * Get the current page URL (with host and query parameters)
     */
    private static function get_current_url() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        if (!empty($host)) {
            $scheme = is_ssl() ? 'https://' : 'http://';
            return esc_url_raw($scheme . $host . $request_uri);
        }

        return esc_url_raw(home_url('/'));
    }

    /**
     * Resolve a safe redirect target with fallbacks
     */
    private static function resolve_redirect_target($preferred = '') {
        $preferred = $preferred ? esc_url_raw($preferred) : '';
        if (!empty($preferred)) {
            return $preferred;
        }

        $referer = wp_get_referer();
        if ($referer) {
            return esc_url_raw($referer);
        }

        return self::get_current_url();
    }
    
    /**
     * Get customer tier based on bookings
     */
    private static function get_customer_tier($total_bookings) {
        if ($total_bookings >= 10) return 'vip';
        if ($total_bookings >= 5) return 'loyal';
        if ($total_bookings >= 1) return 'regular';
        return 'new';
    }
    
    /**
     * Get error message
     */
    private static function get_error_message($error_code) {
        $messages = [
            'name_required' => __('Name is required', 'advanced-travel-crm'),
            'email_invalid' => __('Please enter a valid email', 'advanced-travel-crm'),
            'email_exists' => __('This email is already registered', 'advanced-travel-crm'),
            'phone_invalid' => __('Please enter a valid phone number (minimum 10 digits)', 'advanced-travel-crm'),
            'phone_exists' => __('This phone number is already registered', 'advanced-travel-crm'),
            'password_short' => __('Password must be at least 6 characters', 'advanced-travel-crm'),
            'password_mismatch' => __('Passwords do not match', 'advanced-travel-crm'),
            'terms_required' => __('You must agree to the terms', 'advanced-travel-crm'),
            'registration_failed' => __('Registration failed. Please try again.', 'advanced-travel-crm'),
            'current_password_wrong' => __('Current password is incorrect', 'advanced-travel-crm'),
            'invalid_credentials' => __('Invalid email/phone or password', 'advanced-travel-crm'),
            'empty_fields' => __('Please fill in all required fields', 'advanced-travel-crm'),
            'invalid_nonce' => __('Security verification failed. Please try again.', 'advanced-travel-crm'),
            'missing_fields' => __('Please fill in all required fields', 'advanced-travel-crm'),
            'otp_system_unavailable' => __('OTP verification system is currently unavailable', 'advanced-travel-crm'),
            'expired' => __('OTP has expired. Please request a new one.', 'advanced-travel-crm'),
            'incorrect' => __('Incorrect OTP code. Please try again.', 'advanced-travel-crm'),
            'max_attempts' => __('Too many incorrect attempts. Please request a new OTP.', 'advanced-travel-crm'),
            'user_not_found' => __('User account not found', 'advanced-travel-crm'),
            'rate_limit' => __('Too many OTP requests. Please try again later.', 'advanced-travel-crm'),
            'otp_not_verified' => __('Please verify OTP first', 'advanced-travel-crm'),
        ];
        
        $codes = explode(',', $error_code);
        $errors = array_map(function($code) use ($messages) {
            return $messages[trim($code)] ?? __('An error occurred', 'advanced-travel-crm');
        }, $codes);
        
        return implode(' ', $errors);
    }
    
    /**
     * Login required message
     */
    private static function login_required_message() {
        return '<div class="atc-notice atc-notice-warning">
            <p>' . __('Please', 'advanced-travel-crm') . ' 
            <a href="' . esc_url(home_url('/login/')) . '">' . __('login', 'advanced-travel-crm') . '</a> 
            ' . __('to access this page.', 'advanced-travel-crm') . '</p>
        </div>';
    }
}