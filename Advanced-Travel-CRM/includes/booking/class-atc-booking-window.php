<?php
/**
 * ATC Booking Window
 * Dynamic booking modal that adapts to each service
 */

if (!defined('ABSPATH')) exit;

class ATC_Booking_Window {
    
    public static function init() {
        add_action('wp_footer', [__CLASS__, 'render_modal']);
        add_shortcode('atc_booking_button', [__CLASS__, 'booking_button_shortcode']);
    }
    
    /**
     * Render booking modal in footer
     */
    public static function render_modal() {
        ?>
        <div id="atc-booking-modal" class="atc-modal" style="display:none;">
            <div class="atc-modal-overlay"></div>
            <div class="atc-modal-content">
                <div class="atc-modal-header">
                    <h2 id="atc-modal-title">Book Now</h2>
                    <button class="atc-modal-close">&times;</button>
                </div>
                <div class="atc-modal-body">
                    <div id="atc-booking-form-container"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Booking button shortcode
     */
    public static function booking_button_shortcode($atts) {
        $atts = shortcode_atts([
            'service' => 'tours',
            'package_id' => '',
            'text' => __('Book Now', 'advanced-travel-crm'),
            'class' => 'atc-btn atc-btn-gold',
        ], $atts);
        
        return sprintf(
            '<button class="%s" data-atc-booking-trigger data-service="%s" data-package-id="%s">%s</button>',
            esc_attr($atts['class']),
            esc_attr($atts['service']),
            esc_attr($atts['package_id']),
            esc_html($atts['text'])
        );
    }
}