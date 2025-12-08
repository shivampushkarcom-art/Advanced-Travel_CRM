<?php
/**
 * ATC Astra Theme Integration
 * Compatibility and enhancements for Astra theme
 */

if (!defined('ABSPATH')) exit;

class ATC_Astra_Integration {
    
    public static function init() {
        // Check if Astra theme is active
        if (!self::is_astra_active()) {
            return;
        }
        
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_astra_compatibility'], 20);
        add_filter('astra_get_option', [__CLASS__, 'override_astra_colors'], 10, 2);
        add_action('wp_head', [__CLASS__, 'add_astra_custom_css'], 99);
        add_filter('body_class', [__CLASS__, 'add_atc_body_class']);
    }
    
    /**
     * Check if Astra is active
     */
    private static function is_astra_active() {
        return (defined('ASTRA_THEME_VERSION') || function_exists('astra_setup')) && !is_admin();
    }
    
    /**
     * Enqueue Astra Compatibility Styles
     */
    public static function enqueue_astra_compatibility() {
        wp_enqueue_style(
            'atc-astra-compatibility',
            ATC_ASSETS_URL . 'css/atc-astra-compatibility.css',
            ['astra-theme-css'],
            ATC_VERSION,
            'all'
        );
        // Add inline style with highest priority to ensure golden gradient is applied
        // EXACT SAME gradient and animation as widgets (Search, Contact Us, Service Grid)
        $golden_gradient = 'linear-gradient(135deg, #F4D03F 0%, #D4AF37 25%, #F4E4C1 50%, #D4AF37 75%, #F4D03F 100%)';
        $custom_css = "
            @keyframes goldenShimmer {
                0%, 100% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
            }
            .ast-header-wrapper,
            .ast-primary-header,
            .ast-header-bar,
            .ast-primary-header-bar,
            .ast-header-break-point .ast-header-wrapper,
            .ast-header-break-point .ast-primary-header,
            header.site-header,
            .ast-header {
                background: {$golden_gradient} !important;
                background-size: 400% 400% !important;
                animation: goldenShimmer 8s ease infinite !important;
            }
        ";
        wp_add_inline_style('atc-astra-compatibility', $custom_css);
    }
    
    /**
     * Override Astra Colors
     */
    public static function override_astra_colors($value, $option) {
        // Override primary color with our blue
        if ($option === 'theme-color') {
            return '#1E3A5F';
        }
        
        // Override link color
        if ($option === 'link-color') {
            return '#1E3A5F';
        }
        
        return $value;
    }
    
    /**
     * Add Custom CSS for Astra
     */
    public static function add_astra_custom_css() {
        ?>
        <style id="atc-astra-custom-css">
            /* ATC Astra Compatibility */
            :root {
                --ast-global-color-0: #1E3A5F;
                --ast-global-color-1: #D4AF37;
                --ast-global-color-2: #F4D03F;
            }
            
            /* Golden shimmer animation - EXACT SAME as Search Widget, Service Grid, Contact Us */
            @keyframes goldenShimmer {
                0%, 100% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
            }
            
            /* Astra header compatibility - EXACT SAME golden gradient and animation as widgets */
            /* Target all possible Astra header elements */
            .ast-header-wrapper,
            .ast-primary-header,
            .ast-header-bar,
            .ast-primary-header-bar,
            .ast-header-break-point .ast-header-wrapper,
            .ast-header-break-point .ast-primary-header,
            header.site-header,
            .ast-header {
                /* Premium Golden Animated Background - EXACT SAME as Search Widget, Service Grid, Contact Us */
                background: linear-gradient(135deg, 
                    #F4D03F 0%,      /* Light Gold */
                    #D4AF37 25%,     /* Gold */
                    #F4E4C1 50%,     /* Cream Gold */
                    #D4AF37 75%,     /* Gold */
                    #F4D03F 100%     /* Light Gold */
                ) !important;
                background-size: 400% 400% !important;
                animation: goldenShimmer 8s ease infinite !important;
                /* Removed all borders, shadows, overlays - just the gradient background */
                border: none !important;
                box-shadow: none !important;
            }
            
            /* Remove any extra overlays or borders */
            .ast-header-wrapper::before,
            .ast-header-wrapper::after {
                display: none !important;
            }
            
            /* Center header content */
            .ast-header-wrapper .ast-container {
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                width: 100% !important;
            }
            
            /* Header navigation links - Blue text on gold */
            .ast-header-wrapper .main-navigation a,
            .ast-header-wrapper .main-navigation .menu-item a {
                color: #1E3A5F !important;
                font-weight: 600 !important;
            }
            
            .ast-header-wrapper .main-navigation a:hover,
            .ast-header-wrapper .main-navigation .menu-item a:hover {
                color: #0A1F44 !important;
            }
            
            /* Astra footer compatibility - Footer visible (removed hiding per user request) */
            /* Footer is now visible and styled by theme */
            
            /* Button compatibility */
            .ast-button,
            .button,
            button {
                background: #1E3A5F !important;
                border-color: #1E3A5F !important;
            }
            
            .ast-button:hover,
            .button:hover,
            button:hover {
                background: #2E4A7F !important;
            }
        </style>
        <?php
    }
    
    /**
     * Add ATC Body Class
     */
    public static function add_atc_body_class($classes) {
        $classes[] = 'atc-astra-theme';
        return $classes;
    }
}

// Initialize if Astra is active
if (defined('ASTRA_THEME_VERSION') || function_exists('astra_setup')) {
    ATC_Astra_Integration::init();
} else {
    add_action('after_setup_theme', [ATC_Astra_Integration::class, 'init']);
}

