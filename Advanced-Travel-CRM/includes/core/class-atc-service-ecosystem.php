<?php
/**
 * ATC Service Ecosystem Framework
 * Manages service-specific themes, styles, and configurations
 * Each service has its own complete ecosystem
 */

if (!defined('ABSPATH')) exit;

class ATC_Service_Ecosystem {
    
    private static $service_themes = [];
    private static $service_configs = [];
    
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_service_assets'], 15);
        add_filter('atc_package_details_template', [__CLASS__, 'get_service_template'], 10, 2);
        add_filter('atc_package_query_fields', [__CLASS__, 'get_service_query_fields'], 10, 2);
    }
    
    /**
     * Register service theme configuration
     */
    public static function register_service_theme($service_key, $theme_config) {
        self::$service_themes[$service_key] = $theme_config;
    }
    
    /**
     * Get service theme configuration
     */
    public static function get_service_theme($service_key) {
        if (isset(self::$service_themes[$service_key])) {
            return self::$service_themes[$service_key];
        }
        
        // Load from service config
        $config = ATC_Config_Loader::load_service_config($service_key);
        if (!empty($config['theme'])) {
            return $config['theme'];
        }
        
        // Default theme
        return [
            'package_details_style' => 'default',
            'color_scheme' => [
                'primary' => '#667eea',
                'secondary' => '#764ba2',
                'accent' => '#ff6b35',
            ],
            'layout' => 'two-column',
        ];
    }
    
    /**
     * Enqueue service-specific assets
     */
    public static function enqueue_service_assets() {
        // Only load on frontend pages (not admin)
        if (is_admin()) {
            return;
        }
        
        // Detect service context
        $service = ATC_Services::detect_service_context();
        
        // Get service config
        $config = ATC_Config_Loader::load_service_config($service);
        $theme = self::get_service_theme($service);
        
        // Enqueue service-specific CSS (only on pages that might show package details)
        if (is_page() || is_singular() || is_front_page()) {
            $css_file = 'atc-package-details-' . $service . '.css';
            $css_file_path = ATC_PLUGIN_DIR . 'assets/css/' . $css_file;
            
            if (file_exists($css_file_path)) {
                wp_enqueue_style(
                    'atc-package-details-' . $service,
                    ATC_ASSETS_URL . 'css/' . $css_file,
                    [],
                    ATC_VERSION
                );
                
                // Apply service-specific color scheme
                if (!empty($theme['color_scheme'])) {
                    self::inject_service_colors($service, $theme['color_scheme']);
                }
            } else {
                // Fallback to tours CSS if service-specific doesn't exist
                $tours_css = ATC_PLUGIN_DIR . 'assets/css/atc-package-details-tours.css';
                if (file_exists($tours_css)) {
                    wp_enqueue_style(
                        'atc-package-details-tours',
                        ATC_ASSETS_URL . 'css/atc-package-details-tours.css',
                        [],
                        ATC_VERSION
                    );
                }
            }
        }
        
        // Enqueue service-specific JS (if exists)
        $js_file = 'atc-package-details-' . $service . '.js';
        $js_file_path = ATC_PLUGIN_DIR . 'assets/js/' . $js_file;
        
        if (file_exists($js_file_path)) {
            wp_enqueue_script(
                'atc-package-details-' . $service,
                ATC_ASSETS_URL . 'js/' . $js_file,
                ['jquery'],
                ATC_VERSION,
                true
            );
        }
    }
    
    /**
     * Inject service-specific colors via CSS variables
     */
    private static function inject_service_colors($service, $colors) {
        $css = ":root {
            --atc-service-primary: {$colors['primary']};
            --atc-service-secondary: {$colors['secondary']};
            --atc-service-accent: {$colors['accent']};
        }";
        
        wp_add_inline_style('atc-package-details-' . $service, $css);
    }
    
    /**
     * Get service-specific package details template
     */
    public static function get_service_template($template, $service_key) {
        $theme = self::get_service_theme($service_key);
        $style = $theme['package_details_style'] ?? 'default';
        
        // Service-specific template files
        $template_file = ATC_TEMPLATES_DIR . 'package-details-' . $service_key . '.php';
        
        if (file_exists($template_file)) {
            return $template_file;
        }
        
        // Fallback to style-based template
        $style_template = ATC_TEMPLATES_DIR . 'package-details-' . $style . '.php';
        if (file_exists($style_template)) {
            return $style_template;
        }
        
        // Default template
        return $template;
    }
    
    /**
     * Get service-specific query fields
     */
    public static function get_service_query_fields($fields, $service_key) {
        // Load service config
        $config = ATC_Config_Loader::load_service_config($service_key);
        
        // Get query fields from config
        if (!empty($config['query_fields']) && is_array($config['query_fields'])) {
            return $config['query_fields'];
        }
        
        // Default query fields
        return $fields;
    }
    
    /**
     * Get package-specific query fields
     */
    public static function get_package_query_fields($package_id) {
        global $wpdb;
        
        // Get package
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
            $package_id
        ), ARRAY_A);
        
        if (!$package) {
            return [];
        }
        
        // Get service config
        $service_key = $package['service_key'] ?? 'tours';
        $config = ATC_Config_Loader::load_service_config($service_key);
        
        // Get base query fields from service config
        $query_fields = $config['query_fields'] ?? [];
        
        // Get package-specific customizations from metadata
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
            
            if (!empty($metadata['query_fields'])) {
                // Merge package-specific fields
                $query_fields = array_merge($query_fields, $metadata['query_fields']);
            }
            
            if (!empty($metadata['query_prefilled'])) {
                // Add pre-filled values
                foreach ($query_fields as &$field) {
                    if (isset($metadata['query_prefilled'][$field['id']])) {
                        $field['default'] = $metadata['query_prefilled'][$field['id']];
                    }
                }
            }
        }
        
        return $query_fields;
    }
    
    /**
     * Get all registered services with themes
     */
    public static function get_services_with_themes() {
        $services = ATC_Services::get_services();
        $services_with_themes = [];
        
        foreach ($services as $key => $service) {
            $theme = self::get_service_theme($key);
            $services_with_themes[$key] = [
                'label' => $service['label'],
                'icon' => $service['icon'] ?? '',
                'theme' => $theme,
            ];
        }
        
        return $services_with_themes;
    }
}

