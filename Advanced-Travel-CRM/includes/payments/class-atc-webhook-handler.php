<?php
// ═══════════════════════════════════════════════════════════════════
// FILE 2: class-atc-webhook-handler.php (NEW)
// Location: includes/payments/class-atc-webhook-handler.php
// ═══════════════════════════════════════════════════════════════════

class ATC_Webhook_Handler {
    
    public static function init() {
        // Register webhook endpoints
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }
    
    public static function register_routes() {
        // Razorpay webhook
        register_rest_route('atc/v1', '/webhook/razorpay', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_razorpay_webhook'],
            'permission_callback' => '__return_true',
        ]);
        
        // Stripe webhook
        register_rest_route('atc/v1', '/webhook/stripe', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_stripe_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * ✅ Handle Razorpay webhook
     */
    public static function handle_razorpay_webhook($request) {
        $body = $request->get_body();
        $headers = $request->get_headers();
        
        // Verify signature
        $webhook_secret = get_option('atc_razorpay_webhook_secret', '');
        
        if (!empty($webhook_secret)) {
            $signature = $headers['x_razorpay_signature'][0] ?? '';
            $expected_signature = hash_hmac('sha256', $body, $webhook_secret);
            
            if ($signature !== $expected_signature) {
                ATC_Logger::log('error', 'Razorpay webhook signature mismatch');
                return new WP_Error('invalid_signature', 'Invalid signature', ['status' => 401]);
            }
        }
        
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['event'])) {
            return new WP_Error('invalid_data', 'Invalid webhook data', ['status' => 400]);
        }
        
        // Handle different events
        switch ($data['event']) {
            case 'payment.captured':
                self::handle_razorpay_payment_captured($data['payload']);
                break;
                
            case 'payment.failed':
                self::handle_razorpay_payment_failed($data['payload']);
                break;
                
            case 'order.paid':
                self::handle_razorpay_order_paid($data['payload']);
                break;
        }
        
        return rest_ensure_response(['status' => 'success']);
    }
    
    /**
     * ✅ Handle Razorpay payment captured
     */
    private static function handle_razorpay_payment_captured($payload) {
        global $wpdb;
        
        $payment_entity = $payload['payment']['entity'] ?? [];
        $order_id = $payment_entity['order_id'] ?? '';
        
        if (empty($order_id)) return;
        
        // Find payment record
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_PAYMENTS . " WHERE transaction_id = %s",
            $order_id
        ), ARRAY_A);
        
        if (!$payment) {
            ATC_Logger::log('error', 'Payment not found for Razorpay order: ' . $order_id);
            return;
        }
        
        // Update payment status
        $wpdb->update(
            ATC_TABLE_PAYMENTS,
            [
                'status' => 'paid',
                'gateway_response' => json_encode($payment_entity),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment['id']]
        );
        
        // Update booking
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            [
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment['booking_id']]
        );
        
        // Get booking details
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $payment['booking_id']
        ), ARRAY_A);
        
        // Trigger payment received event
        do_action('atc_payment_received', $payment['booking_id'], array_merge($payment, $booking));
        
        ATC_Logger::log('info', 'Razorpay payment captured: ' . $order_id);
    }
    
    /**
     * ✅ Handle Razorpay payment failed
     */
    private static function handle_razorpay_payment_failed($payload) {
        global $wpdb;
        
        $payment_entity = $payload['payment']['entity'] ?? [];
        $order_id = $payment_entity['order_id'] ?? '';
        
        if (empty($order_id)) return;
        
        $wpdb->update(
            ATC_TABLE_PAYMENTS,
            ['status' => 'failed', 'gateway_response' => json_encode($payment_entity)],
            ['transaction_id' => $order_id]
        );
        
        ATC_Logger::log('error', 'Razorpay payment failed: ' . $order_id);
    }
    
    /**
     * ✅ Handle Stripe webhook
     */
    public static function handle_stripe_webhook($request) {
        $body = $request->get_body();
        $signature = $request->get_header('stripe_signature');
        
        $webhook_secret = get_option('atc_stripe_webhook_secret', '');
        
        if (!empty($webhook_secret)) {
            // Verify signature (simplified - use Stripe SDK in production)
            $expected_signature = hash_hmac('sha256', $body, $webhook_secret);
            
            if (!hash_equals($expected_signature, $signature)) {
                ATC_Logger::log('error', 'Stripe webhook signature mismatch');
                return new WP_Error('invalid_signature', 'Invalid signature', ['status' => 401]);
            }
        }
        
        $event = json_decode($body, true);
        
        if (!$event || !isset($event['type'])) {
            return new WP_Error('invalid_data', 'Invalid webhook data', ['status' => 400]);
        }
        
        // Handle different events
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                self::handle_stripe_payment_succeeded($event['data']['object']);
                break;
                
            case 'payment_intent.payment_failed':
                self::handle_stripe_payment_failed($event['data']['object']);
                break;
        }
        
        return rest_ensure_response(['status' => 'success']);
    }
    
    /**
     * ✅ Handle Stripe payment succeeded
     */
    private static function handle_stripe_payment_succeeded($payment_intent) {
        global $wpdb;
        
        $payment_intent_id = $payment_intent['id'];
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_PAYMENTS . " WHERE transaction_id = %s",
            $payment_intent_id
        ), ARRAY_A);
        
        if (!$payment) {
            ATC_Logger::log('error', 'Payment not found for Stripe payment_intent: ' . $payment_intent_id);
            return;
        }
        
        $wpdb->update(
            ATC_TABLE_PAYMENTS,
            [
                'status' => 'paid',
                'gateway_response' => json_encode($payment_intent),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment['id']]
        );
        
        $wpdb->update(
            ATC_TABLE_BOOKINGS,
            [
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment['booking_id']]
        );
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_BOOKINGS . " WHERE id = %d",
            $payment['booking_id']
        ), ARRAY_A);
        
        do_action('atc_payment_received', $payment['booking_id'], array_merge($payment, $booking));
        
        ATC_Logger::log('info', 'Stripe payment succeeded: ' . $payment_intent_id);
    }
    
    /**
     * ✅ Handle Stripe payment failed
     */
    private static function handle_stripe_payment_failed($payment_intent) {
        global $wpdb;
        
        $wpdb->update(
            ATC_TABLE_PAYMENTS,
            ['status' => 'failed', 'gateway_response' => json_encode($payment_intent)],
            ['transaction_id' => $payment_intent['id']]
        );
        
        ATC_Logger::log('error', 'Stripe payment failed: ' . $payment_intent['id']);
    }
}