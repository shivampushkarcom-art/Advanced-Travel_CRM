<?php
/**
 * ATC Payments System
 * Main payment orchestrator supporting multiple gateways
 * Gateways: Razorpay, Stripe, PayPal
 */

if (!defined('ABSPATH')) exit;

class ATC_Payments {
    
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }
    
    /**
     * Register REST routes
     */
    public static function register_routes() {
        // Create payment
        register_rest_route('atc/v1', '/payment/create', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_payment'],
            'permission_callback' => '__return_true',
        ]);
        
        // Verify payment
        register_rest_route('atc/v1', '/payment/verify', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'verify_payment'],
            'permission_callback' => '__return_true',
        ]);
        
        // Payment status
        register_rest_route('atc/v1', '/payment/status/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_payment_status'],
            'permission_callback' => '__return_true',
        ]);
        
        // Manual payment
        register_rest_route('atc/v1', '/payment/manual', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'record_manual_payment'],
            'permission_callback' => [__CLASS__, 'check_permission'],
        ]);
    }
    
    /**
     * Admin menu
     */
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Payment Settings', 'advanced-travel-crm'),
            __('Payment Settings', 'advanced-travel-crm'),
            'manage_options',
            'atc-payment-settings',
            [__CLASS__, 'settings_page']
        );
    }
    
    /**
     * Enqueue payment scripts
     */
    public static function enqueue_scripts() {
        if (!get_option('atc_payment_enabled', 0)) {
            return;
        }
        
        $gateway = get_option('atc_payment_gateway', 'razorpay');
        
        // Load gateway scripts
        if ($gateway === 'razorpay' && get_option('atc_razorpay_enabled', 0)) {
            wp_enqueue_script('razorpay-checkout', 'https://checkout.razorpay.com/v1/checkout.js', [], null, true);
        } elseif ($gateway === 'stripe' && get_option('atc_stripe_enabled', 0)) {
            wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], null, true);
        }
        
        // Payment handler
        wp_enqueue_script('atc-payments', ATC_ASSETS_URL . 'js/atc-payments.js', ['jquery'], ATC_VERSION, true);
        
        wp_localize_script('atc-payments', 'atcPayments', [
            'gateway' => $gateway,
            'razorpayKey' => get_option('atc_razorpay_key', ''),
            'stripeKey' => get_option('atc_stripe_publishable_key', ''),
            'currency' => get_option('atc_currency', 'INR'),
            'restUrl' => rest_url('atc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    
    /**
     * Create payment order
     */
    public static function create_payment($request) {
        $params = $request->get_json_params();
        
        $booking_id = intval($params['booking_id'] ?? 0);
        $amount = floatval($params['amount'] ?? 0);
        
        if (!$booking_id || !$amount) {
            return new WP_Error('invalid_data', __('Invalid booking or amount', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        // Get booking
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            return new WP_Error('booking_not_found', __('Booking not found', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        // Get active gateway
        $gateway = get_option('atc_payment_gateway', 'razorpay');
        
        // Create payment based on gateway
        if ($gateway === 'razorpay' && get_option('atc_razorpay_enabled', 0)) {
            return self::create_razorpay_order($booking, $amount);
        } elseif ($gateway === 'stripe' && get_option('atc_stripe_enabled', 0)) {
            return self::create_stripe_intent($booking, $amount);
        }
        
        return new WP_Error('gateway_not_configured', __('Payment gateway not configured', 'advanced-travel-crm'), ['status' => 500]);
    }
    
    /**
     * Create Razorpay order
     */
    private static function create_razorpay_order($booking, $amount) {
        $key_id = get_option('atc_razorpay_key', '');
        $key_secret = get_option('atc_razorpay_secret', '');
        
        if (empty($key_id) || empty($key_secret)) {
            return new WP_Error('razorpay_not_configured', __('Razorpay not configured', 'advanced-travel-crm'), ['status' => 500]);
        }
        
        // Razorpay expects amount in paise (multiply by 100)
        $amount_paise = $amount * 100;
        
        $url = 'https://api.razorpay.com/v1/orders';
        $data = [
            'amount' => $amount_paise,
            'currency' => $booking['currency'] ?: 'INR',
            'receipt' => $booking['booking_id'],
            'notes' => [
                'booking_id' => $booking['booking_id'],
                'customer_email' => $booking['customer_email'],
            ]
        ];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($key_id . ':' . $key_secret),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('razorpay_error', $body['error']['description'], ['status' => 500]);
        }
        
        // Save payment record
        global $wpdb;
        $wpdb->insert(ATC_TABLE_PAYMENTS, [
            'booking_id' => $booking['id'],
            'transaction_id' => $body['id'],
            'payment_method' => 'razorpay',
            'gateway' => 'razorpay',
            'amount' => $amount,
            'currency' => $booking['currency'] ?: 'INR',
            'status' => 'pending',
            'gateway_response' => json_encode($body),
            'created_at' => current_time('mysql'),
        ]);
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $body['id'],
            'amount' => $amount,
            'currency' => $booking['currency'] ?: 'INR',
            'booking_id' => $booking['booking_id'],
            'customer_name' => $booking['customer_name'],
            'customer_email' => $booking['customer_email'],
            'customer_phone' => $booking['customer_phone'],
        ]);
    }
    
    /**
     * Create Stripe payment intent
     */
    private static function create_stripe_intent($booking, $amount) {
        $secret_key = get_option('atc_stripe_secret_key', '');
        
        if (empty($secret_key)) {
            return new WP_Error('stripe_not_configured', __('Stripe not configured', 'advanced-travel-crm'), ['status' => 500]);
        }
        
        // Stripe expects amount in smallest currency unit (cents for USD, paise for INR)
        $amount_cents = $amount * 100;
        
        $url = 'https://api.stripe.com/v1/payment_intents';
        $data = [
            'amount' => $amount_cents,
            'currency' => strtolower($booking['currency'] ?: 'inr'),
            'description' => sprintf(__('Payment for booking %s', 'advanced-travel-crm'), $booking['booking_id']),
            'metadata' => [
                'booking_id' => $booking['booking_id'],
                'customer_email' => $booking['customer_email'],
            ]
        ];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query($data),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('stripe_error', $body['error']['message'], ['status' => 500]);
        }
        
        // Save payment record
        global $wpdb;
        $wpdb->insert(ATC_TABLE_PAYMENTS, [
            'booking_id' => $booking['id'],
            'transaction_id' => $body['id'],
            'payment_method' => 'stripe',
            'gateway' => 'stripe',
            'amount' => $amount,
            'currency' => $booking['currency'] ?: 'INR',
            'status' => 'pending',
            'gateway_response' => json_encode($body),
            'created_at' => current_time('mysql'),
        ]);
        
        return rest_ensure_response([
            'success' => true,
            'client_secret' => $body['client_secret'],
            'payment_intent_id' => $body['id'],
            'amount' => $amount,
            'currency' => $booking['currency'] ?: 'INR',
        ]);
    }
    
    /**
     * Verify payment
     */
    public static function verify_payment($request) {
        $params = $request->get_json_params();
        
        $gateway = sanitize_text_field($params['gateway'] ?? '');
        
        if ($gateway === 'razorpay') {
            return self::verify_razorpay_payment($params);
        } elseif ($gateway === 'stripe') {
            return self::verify_stripe_payment($params);
        }
        
        return new WP_Error('invalid_gateway', __('Invalid payment gateway', 'advanced-travel-crm'), ['status' => 400]);
    }
    
    /**
     * Verify Razorpay payment
     */
    private static function verify_razorpay_payment($params) {
        $razorpay_order_id = sanitize_text_field($params['razorpay_order_id'] ?? '');
        $razorpay_payment_id = sanitize_text_field($params['razorpay_payment_id'] ?? '');
        $razorpay_signature = sanitize_text_field($params['razorpay_signature'] ?? '');
        
        if (empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature)) {
            return new WP_Error('invalid_data', __('Invalid payment data', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        $key_secret = get_option('atc_razorpay_secret', '');
        
        // Verify signature
        $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $key_secret);
        
        if ($generated_signature !== $razorpay_signature) {
            return new WP_Error('signature_mismatch', __('Payment verification failed', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        // Update payment record
        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_PAYMENTS . " WHERE transaction_id = %s",
            $razorpay_order_id
        ), ARRAY_A);
        
        if (!$payment) {
            return new WP_Error('payment_not_found', __('Payment record not found', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        // Update payment status
        $wpdb->update(
            ATC_TABLE_PAYMENTS,
            [
                'status' => 'paid',
                'gateway_response' => json_encode([
                    'razorpay_order_id' => $razorpay_order_id,
                    'razorpay_payment_id' => $razorpay_payment_id,
                    'razorpay_signature' => $razorpay_signature,
                ]),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment['id']]
        );
        
        // Update booking payment status
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            [
                'payment_status' => 'paid',
                'payment_method' => 'razorpay',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment['booking_id']]
        );
        
        // Trigger payment received event
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $payment['booking_id']
        ), ARRAY_A);
        
        do_action('atc_payment_received', $payment['booking_id'], array_merge($payment, $booking));
        
        return rest_ensure_response([
            'success' => true,
            'message' => __('Payment verified successfully', 'advanced-travel-crm'),
            'booking_id' => $booking['booking_id'],
        ]);
    }
    
    /**
     * Verify Stripe payment
     */
    private static function verify_stripe_payment($params) {
        $payment_intent_id = sanitize_text_field($params['payment_intent_id'] ?? '');
        
        if (empty($payment_intent_id)) {
            return new WP_Error('invalid_data', __('Invalid payment data', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        $secret_key = get_option('atc_stripe_secret_key', '');
        
        // Retrieve payment intent from Stripe
        $url = 'https://api.stripe.com/v1/payment_intents/' . $payment_intent_id;
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['status'] !== 'succeeded') {
            return new WP_Error('payment_not_succeeded', __('Payment not completed', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        // Update payment record
        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_PAYMENTS . " WHERE transaction_id = %s",
            $payment_intent_id
        ), ARRAY_A);
        
        if (!$payment) {
            return new WP_Error('payment_not_found', __('Payment record not found', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        // Update payment status
        $wpdb->update(
            ATC_TABLE_PAYMENTS,
            [
                'status' => 'paid',
                'gateway_response' => json_encode($body),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment['id']]
        );
        
        // Update booking
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            [
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment['booking_id']]
        );
        
        // Trigger event
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $payment['booking_id']
        ), ARRAY_A);
        
        do_action('atc_payment_received', $payment['booking_id'], array_merge($payment, $booking));
        
        return rest_ensure_response([
            'success' => true,
            'message' => __('Payment verified successfully', 'advanced-travel-crm'),
            'booking_id' => $booking['booking_id'],
        ]);
    }
    
    /**
     * Check permission for admin actions
     */
    public static function check_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Record manual payment
     */
    public static function record_manual_payment($request) {
        $params = $request->get_json_params();
        
        $booking_id = intval($params['booking_id'] ?? 0);
        $amount = floatval($params['amount'] ?? 0);
        $payment_method = sanitize_text_field($params['payment_method'] ?? '');
        $payment_proof = sanitize_text_field($params['payment_proof'] ?? '');
        $payment_date = sanitize_text_field($params['payment_date'] ?? '');
        $admin_notes = sanitize_textarea_field($params['admin_notes'] ?? '');
        
        if (!$booking_id || !$amount || !$payment_method) {
            return new WP_Error('invalid_data', __('Invalid payment data', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        // Get booking
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            return new WP_Error('booking_not_found', __('Booking not found', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        // Generate transaction ID
        $transaction_id = 'MAN-' . date('Ymd') . '-' . wp_rand(1000, 9999);
        
        // Create payment record
        $payment_data = [
            'booking_id' => $booking_id,
            'transaction_id' => $transaction_id,
            'payment_method' => $payment_method,
            'gateway' => 'manual',
            'amount' => $amount,
            'currency' => $booking['currency'] ?: 'INR',
            'status' => 'paid',
            'payment_type' => 'manual',
            'payment_proof' => $payment_proof,
            'payment_date' => $payment_date ?: current_time('mysql'),
            'admin_notes' => $admin_notes,
            'created_at' => current_time('mysql'),
        ];
        
        $wpdb->insert(ATC_TABLE_PAYMENTS, $payment_data);
        $payment_id = $wpdb->insert_id;
        
        // Update booking payment status
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            [
                'payment_status' => 'paid',
                'payment_method' => $payment_method,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $booking_id]
        );
        
        // Trigger payment received event
        do_action('atc_payment_received', $booking_id, array_merge($payment_data, $booking));
        
        return rest_ensure_response([
            'success' => true,
            'payment_id' => $payment_id,
            'transaction_id' => $transaction_id,
            'message' => __('Manual payment recorded successfully', 'advanced-travel-crm'),
        ]);
    }
    
    /**
     * Get payment status
     */
    public static function get_payment_status($request) {
        $payment_id = intval($request['id']);
        
        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, b.booking_id, b.customer_name 
            FROM " . ATC_TABLE_PAYMENTS . " p
            LEFT JOIN " . ATC_TABLE_BOOKINGS . " b ON p.booking_id = b.id
            WHERE p.id = %d",
            $payment_id
        ), ARRAY_A);
        
        if (!$payment) {
            return new WP_Error('payment_not_found', __('Payment not found', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        return rest_ensure_response($payment);
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        if (isset($_POST['atc_save_payment_settings'])) {
            check_admin_referer('atc_payment_settings');
            
            // Save general settings
            update_option('atc_payment_enabled', isset($_POST['payment_enabled']) ? 1 : 0);
            update_option('atc_payment_required', isset($_POST['payment_required']) ? 1 : 0);
            update_option('atc_payment_manual_allowed', isset($_POST['payment_manual_allowed']) ? 1 : 0);
            update_option('atc_payment_gateway', sanitize_text_field($_POST['payment_gateway'] ?? 'razorpay'));
            update_option('atc_manual_payment_methods', isset($_POST['manual_methods']) ? array_map('sanitize_text_field', $_POST['manual_methods']) : []);
            
            // Save Razorpay settings
            update_option('atc_razorpay_enabled', isset($_POST['razorpay_enabled']) ? 1 : 0);
            update_option('atc_razorpay_key', sanitize_text_field($_POST['razorpay_key'] ?? ''));
            update_option('atc_razorpay_secret', sanitize_text_field($_POST['razorpay_secret'] ?? ''));
            
            // Save Stripe settings
            update_option('atc_stripe_enabled', isset($_POST['stripe_enabled']) ? 1 : 0);
            update_option('atc_stripe_publishable_key', sanitize_text_field($_POST['stripe_publishable_key'] ?? ''));
            update_option('atc_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key'] ?? ''));
            
            echo '<div class="notice notice-success"><p>' . __('Payment settings saved!', 'advanced-travel-crm') . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('💳 Payment Gateway Settings', 'advanced-travel-crm'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('atc_payment_settings'); ?>
                
                <h2><?php _e('General Settings', 'advanced-travel-crm'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Payments', 'advanced-travel-crm'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="payment_enabled" value="1" <?php checked(get_option('atc_payment_enabled', 0), 1); ?>>
                                <?php _e('Enable online payment collection', 'advanced-travel-crm'); ?>
                            </label>
                            <p class="description"><?php _e('Disable if you want to collect payments manually', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Require Payment', 'advanced-travel-crm'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="payment_required" value="1" <?php checked(get_option('atc_payment_required', 0), 1); ?>>
                                <?php _e('Require payment before booking confirmation', 'advanced-travel-crm'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Allow Manual Payment', 'advanced-travel-crm'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="payment_manual_allowed" value="1" <?php checked(get_option('atc_payment_manual_allowed', 1), 1); ?>>
                                <?php _e('Allow customers to pay later (collect manually)', 'advanced-travel-crm'); ?>
                            </label>
                            <p class="description"><?php _e('Enable manual payment methods: Bank Transfer, Cash, Cheque, UPI, etc.', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Manual Payment Methods', 'advanced-travel-crm'); ?></th>
                        <td>
                            <label><input type="checkbox" name="manual_methods[]" value="bank_transfer" checked> <?php _e('Bank Transfer', 'advanced-travel-crm'); ?></label><br>
                            <label><input type="checkbox" name="manual_methods[]" value="cash" checked> <?php _e('Cash', 'advanced-travel-crm'); ?></label><br>
                            <label><input type="checkbox" name="manual_methods[]" value="cheque" checked> <?php _e('Cheque', 'advanced-travel-crm'); ?></label><br>
                            <label><input type="checkbox" name="manual_methods[]" value="upi" checked> <?php _e('UPI', 'advanced-travel-crm'); ?></label><br>
                            <label><input type="checkbox" name="manual_methods[]" value="neft" checked> <?php _e('NEFT', 'advanced-travel-crm'); ?></label><br>
                            <label><input type="checkbox" name="manual_methods[]" value="rtgs" checked> <?php _e('RTGS', 'advanced-travel-crm'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Active Gateway', 'advanced-travel-crm'); ?></th>
                        <td>
                            <select name="payment_gateway">
                                <option value="razorpay" <?php selected(get_option('atc_payment_gateway', 'razorpay'), 'razorpay'); ?>>
                                    Razorpay (India)
                                </option>
                                <option value="stripe" <?php selected(get_option('atc_payment_gateway'), 'stripe'); ?>>
                                    Stripe (International)
                                </option>
                                <option value="paypal" <?php selected(get_option('atc_payment_gateway'), 'paypal'); ?>>
                                    PayPal (Coming Soon)
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('🇮🇳 Razorpay Settings', 'advanced-travel-crm'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Razorpay', 'advanced-travel-crm'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="razorpay_enabled" value="1" <?php checked(get_option('atc_razorpay_enabled', 0), 1); ?>>
                                <?php _e('Enable Razorpay payment gateway', 'advanced-travel-crm'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Razorpay Key ID', 'advanced-travel-crm'); ?></th>
                        <td>
                            <input type="text" name="razorpay_key" value="<?php echo esc_attr(get_option('atc_razorpay_key', '')); ?>" class="regular-text" placeholder="rzp_test_xxxxxxxxxxxxx">
                            <p class="description"><?php _e('Get from Razorpay Dashboard → Settings → API Keys', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Razorpay Key Secret', 'advanced-travel-crm'); ?></th>
                        <td>
                            <input type="password" name="razorpay_secret" value="<?php echo esc_attr(get_option('atc_razorpay_secret', '')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('💳 Stripe Settings', 'advanced-travel-crm'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Stripe', 'advanced-travel-crm'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="stripe_enabled" value="1" <?php checked(get_option('atc_stripe_enabled', 0), 1); ?>>
                                <?php _e('Enable Stripe payment gateway', 'advanced-travel-crm'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Stripe Publishable Key', 'advanced-travel-crm'); ?></th>
                        <td>
                            <input type="text" name="stripe_publishable_key" value="<?php echo esc_attr(get_option('atc_stripe_publishable_key', '')); ?>" class="regular-text" placeholder="pk_test_xxxxxxxxxxxxx">
                            <p class="description"><?php _e('Get from Stripe Dashboard → Developers → API keys', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Stripe Secret Key', 'advanced-travel-crm'); ?></th>
                        <td>
                            <input type="password" name="stripe_secret_key" value="<?php echo esc_attr(get_option('atc_stripe_secret_key', '')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="atc_save_payment_settings" class="button button-primary">
                        <?php _e('Save Payment Settings', 'advanced-travel-crm'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
