<?php
/**
 * ATC WhatsApp Sender - WhatsApp Business API Integration
 * 
 * Supports:
 * - WhatsApp Business API (official)
 * - Twilio WhatsApp API
 * - Custom webhook integration
 */

if (!defined('ABSPATH')) exit;

class ATC_WhatsApp_Sender {
    
    /**
     * Send WhatsApp message
     */
    public static function send_message($message, $phone = null) {
        // Get admin phone from settings
        if (!$phone) {
            $phone = get_option('atc_whatsapp_admin_phone', '');
        }
        
        if (empty($phone)) {
            return false;
        }
        
        // Clean phone number
        $phone = self::clean_phone_number($phone);
        
        // Get API credentials
        $api_token = get_option('atc_whatsapp_api_token', '');
        $phone_id = get_option('atc_whatsapp_phone_id', '');
        $api_type = get_option('atc_whatsapp_api_type', 'whatsapp_business'); // whatsapp_business, twilio, webhook
        $webhook_url = get_option('atc_whatsapp_webhook_url', '');
        
        // Send based on API type
        switch ($api_type) {
            case 'whatsapp_business':
                if (!empty($api_token) && !empty($phone_id)) {
                    return self::send_via_whatsapp_business_api($message, $phone, $api_token, $phone_id);
                }
                // Fallback to webhook if API not configured
                if (!empty($webhook_url)) {
                    return self::send_via_webhook($message, $phone);
                }
                return false;
            case 'twilio':
                if (!empty($api_token)) {
                    return self::send_via_twilio($message, $phone, $api_token);
                }
                // Fallback to webhook if API not configured
                if (!empty($webhook_url)) {
                    return self::send_via_webhook($message, $phone);
                }
                return false;
            case 'webhook':
                return self::send_via_webhook($message, $phone);
            default:
                // Default: Try WhatsApp Business API, fallback to webhook
                if (!empty($api_token) && !empty($phone_id)) {
                    return self::send_via_whatsapp_business_api($message, $phone, $api_token, $phone_id);
                }
                if (!empty($webhook_url)) {
                    return self::send_via_webhook($message, $phone);
                }
                return false;
        }
    }
    
    /**
     * Send via WhatsApp Business API (Meta)
     */
    private static function send_via_whatsapp_business_api($message, $phone, $api_token, $phone_id) {
        if (empty($api_token) || empty($phone_id)) {
            return false;
        }
        
        $url = "https://graph.facebook.com/v18.0/{$phone_id}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            error_log('WhatsApp API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['messages'][0]['id'])) {
            return true;
        }
        
        error_log('WhatsApp API Error: ' . print_r($body, true));
        return false;
    }
    
    /**
     * Send via Twilio WhatsApp API
     */
    private static function send_via_twilio($message, $phone, $api_token) {
        $account_sid = get_option('atc_twilio_account_sid', '');
        $from_number = get_option('atc_twilio_whatsapp_from', 'whatsapp:+14155238886');
        
        if (empty($account_sid) || empty($api_token)) {
            return false;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = [
            'From' => $from_number,
            'To' => 'whatsapp:' . $phone,
            'Body' => $message
        ];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $api_token)
            ],
            'body' => $data,
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            error_log('Twilio WhatsApp Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['sid'])) {
            return true;
        }
        
        error_log('Twilio WhatsApp Error: ' . print_r($body, true));
        return false;
    }
    
    /**
     * Send via Webhook (for custom integrations)
     */
    private static function send_via_webhook($message, $phone) {
        $webhook_url = get_option('atc_whatsapp_webhook_url', '');
        
        // Also check the general webhook URL as fallback
        if (empty($webhook_url)) {
            $webhook_url = get_option('atc_webhook_url', '');
        }
        
        if (empty($webhook_url)) {
            return false;
        }
        
        $data = [
            'phone' => $phone,
            'message' => $message,
            'timestamp' => current_time('mysql')
        ];
        
        $response = wp_remote_post($webhook_url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            error_log('WhatsApp Webhook Error: ' . $response->get_error_message());
            return false;
        }
        
        return wp_remote_retrieve_response_code($response) === 200;
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
}

