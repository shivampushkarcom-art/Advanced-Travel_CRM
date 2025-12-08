<?php
/**
 * ATC Service Landing Pages
 * Individual landing pages for each service
 */

if (!defined('ABSPATH')) exit;

class ATC_Service_Landings {
    
    public static function init() {
        // Check if base landing class exists
        if (!class_exists('ATC_Base_Landing')) {
            return;
        }
        
        // Tours Landing
        add_shortcode('atc_tours_landing', function($atts) {
            if (class_exists('ATC_Base_Landing')) {
                return ATC_Base_Landing::render('tours', 'Tours', $atts);
            }
            return '<p>Landing page not available.</p>';
        });
        
        // Hotels Landing
        add_shortcode('atc_hotels_landing', function($atts) {
            if (class_exists('ATC_Base_Landing')) {
                return ATC_Base_Landing::render('hotels', 'Hotels', $atts);
            }
            return '<p>Landing page not available.</p>';
        });
        
        // Flights Landing
        add_shortcode('atc_flights_landing', function($atts) {
            if (class_exists('ATC_Base_Landing')) {
                return ATC_Base_Landing::render('flights', 'Flights', $atts);
            }
            return '<p>Landing page not available.</p>';
        });
        
        // Trains Landing
        add_shortcode('atc_trains_landing', function($atts) {
            if (class_exists('ATC_Base_Landing')) {
                return ATC_Base_Landing::render('trains', 'Trains', $atts);
            }
            return '<p>Landing page not available.</p>';
        });
        
        // Cars Landing
        add_shortcode('atc_cars_landing', function($atts) {
            if (class_exists('ATC_Base_Landing')) {
                return ATC_Base_Landing::render('cars', 'Car Rentals', $atts);
            }
            return '<p>Landing page not available.</p>';
        });
        
        // Forex Landing
        add_shortcode('atc_forex_landing', function($atts) {
            if (class_exists('ATC_Base_Landing')) {
                return ATC_Base_Landing::render('forex', 'Forex Services', $atts);
            }
            return '<p>Landing page not available.</p>';
        });
        
        // Visa Landing
        add_shortcode('atc_visa_landing', function($atts) {
            if (class_exists('ATC_Base_Landing')) {
                return ATC_Base_Landing::render('visa', 'Visa Services', $atts);
            }
            return '<p>Landing page not available.</p>';
        });
        
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function enqueue_assets() {
        // Check if constants exist
        $assets_url = defined('ATC_ASSETS_URL') ? ATC_ASSETS_URL : plugin_dir_url(__FILE__) . '../../assets/';
        $version = defined('ATC_VERSION') ? ATC_VERSION : '2.4.0';
        
        wp_enqueue_style('atc-global-premium', $assets_url . 'css/atc-global-premium.css', [], $version);
        wp_enqueue_style('atc-landing-pages', $assets_url . 'css/atc-landing-pages.css', ['atc-global-premium'], $version);
        // Cursor script removed per user request
    }
}

// Disabled - Service landing pages removed per user request (Celebrity landing page kept separate)
// ATC_Service_Landings::init();

