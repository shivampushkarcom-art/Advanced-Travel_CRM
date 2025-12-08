<?php
if(!defined('ABSPATH')) exit;

class ATC_Utils {

    /**
     * Sanitize phone number
     * Keeps only digits and leading +
     */
    public static function sanitize_phone($p) {
        return preg_replace('/[^0-9+]/','',(string)$p);
    }

    /**
     * Validate a comma-separated list of emails
     * Returns array of valid emails
     */
    public static function validate_email_list($csv) {
        $parts = array_filter(array_map('trim', explode(',', (string)$csv)));
        $out = [];
        foreach($parts as $p){
            if(is_email($p)) $out[] = $p;
        }
        return $out;
    }

    /**
     * Format currency consistently
     */
    public static function format_currency($amount, $currency = 'INR') {
        return sprintf('%s %s', $currency, number_format((float)$amount, 2));
    }

    /**
     * Replace placeholders in templates
     * Example: {booking_id}, {customer_name}, etc.
     */
    public static function replace_placeholders($template, $data) {
        foreach ($data as $key => $val) {
            $template = str_replace('{'.$key.'}', (string)$val, $template);
        }
        return $template;
    }

    /**
     * Safe JSON loader
     */
    public static function load_json_file($path) {
        if (!file_exists($path)) return [];
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }
}