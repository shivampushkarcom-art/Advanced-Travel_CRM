<?php
/**
 * ATC Elementor Integration
 * Elementor widgets for ATC components
 */

if (!defined('ABSPATH')) exit;

class ATC_Elementor_Integration {
    
    public static function init() {
        // Check if Elementor is installed and activated
        if (!self::is_elementor_active()) {
            return;
        }
        
        add_action('elementor/widgets/register', [__CLASS__, 'register_widgets'], 20);
        add_action('elementor/elements/categories_registered', [__CLASS__, 'add_elementor_category'], 20);
    }
    
    /**
     * Check if Elementor is active
     */
    private static function is_elementor_active() {
        return did_action('elementor/loaded') || class_exists('\Elementor\Plugin');
    }
    
    /**
     * Register Elementor Category
     */
    public static function add_elementor_category($elements_manager) {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        
        $elements_manager->add_category(
            'atc-travel',
            [
                'title' => esc_html__('ATC Travel', 'advanced-travel-crm'),
                'icon' => 'fa fa-plane',
            ]
        );
    }
    
    /**
     * Register Elementor Widgets
     */
    public static function register_widgets($widgets_manager) {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }
        
        // Load base widget first
        $base_widget_file = ATC_INCLUDES_DIR . 'customization/elementor/class-atc-base-widget.php';
        if (file_exists($base_widget_file)) {
            require_once $base_widget_file;
        }
        
        // Load widget files
        $widget_files = [
            'class-atc-hero-widget.php',
            'class-atc-services-widget.php',
            'class-atc-featured-packages-widget.php',
            'class-atc-testimonials-widget.php',
            'class-atc-statistics-widget.php',
            'class-atc-cta-widget.php',
            'class-atc-service-landing-widget.php',
        ];
        
        foreach ($widget_files as $widget_file) {
            $file_path = ATC_INCLUDES_DIR . 'customization/elementor/' . $widget_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Register widgets only if classes exist
        if (class_exists('ATC_Elementor_Hero_Widget')) {
            $widgets_manager->register(new ATC_Elementor_Hero_Widget());
        }
        if (class_exists('ATC_Elementor_Services_Widget')) {
            $widgets_manager->register(new ATC_Elementor_Services_Widget());
        }
        if (class_exists('ATC_Elementor_Featured_Packages_Widget')) {
            $widgets_manager->register(new ATC_Elementor_Featured_Packages_Widget());
        }
        if (class_exists('ATC_Elementor_Testimonials_Widget')) {
            $widgets_manager->register(new ATC_Elementor_Testimonials_Widget());
        }
        if (class_exists('ATC_Elementor_Statistics_Widget')) {
            $widgets_manager->register(new ATC_Elementor_Statistics_Widget());
        }
        if (class_exists('ATC_Elementor_CTA_Widget')) {
            $widgets_manager->register(new ATC_Elementor_CTA_Widget());
        }
        if (class_exists('ATC_Elementor_Service_Landing_Widget')) {
            $widgets_manager->register(new ATC_Elementor_Service_Landing_Widget());
        }
    }
}

// Initialize safely
add_action('plugins_loaded', function() {
    if (did_action('elementor/loaded') || class_exists('\Elementor\Plugin')) {
        ATC_Elementor_Integration::init();
    }
}, 20);

