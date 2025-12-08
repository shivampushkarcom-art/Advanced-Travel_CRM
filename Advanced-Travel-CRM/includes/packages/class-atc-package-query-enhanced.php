<?php
/**
 * ATC Package Query System - Enhanced
 * Service-Specific & Package-Specific Query Forms
 * Customizable query fields per package
 */

if (!defined('ABSPATH')) exit;

class ATC_Package_Query_Enhanced {
    
    public static function init() {
        add_shortcode('atc_package_query', [__CLASS__, 'package_query_shortcode']);
        add_shortcode('atc_query_form', [__CLASS__, 'query_form_shortcode']);
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 20); // Admin menu for query management
        add_action('admin_post_atc_update_query', [__CLASS__, 'update_query_handler']);
    }
    
    /**
     * Admin menu for package queries
     */
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Package Queries', 'advanced-travel-crm'),
            __('Package Queries', 'advanced-travel-crm'),
            'manage_options',
            'atc-package-queries',
            [__CLASS__, 'queries_page']
        );
    }
    
    public static function enqueue_assets() {
        wp_enqueue_style('atc-premium-query-form', ATC_ASSETS_URL . 'css/atc-premium-query-form.css', [], ATC_VERSION);
        wp_enqueue_script('atc-query-form-enhanced', ATC_ASSETS_URL . 'js/atc-query-form-enhanced.js', ['jquery'], ATC_VERSION, true);
        
        wp_localize_script('atc-query-form-enhanced', 'atcQueryForm', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('atc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    
    public static function register_routes() {
        register_rest_route('atc/v1', '/package/(?P<id>\d+)/query-fields', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_package_query_fields'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('atc/v1', '/service/(?P<service>[a-z]+)/query-fields', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_service_query_fields_rest'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('atc/v1', '/query/submit', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'submit_query'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Get service-specific query fields (REST API)
     */
    public static function get_service_query_fields_rest($request) {
        $service_key = sanitize_text_field($request['service']);
        
        // Get service config
        $config = ATC_Config_Loader::load_service_config($service_key);
        $query_fields = $config['query_fields'] ?? [];
        
        // Get service theme for colors
        $theme = ATC_Service_Ecosystem::get_service_theme($service_key);
        $colors = $theme['color_scheme'] ?? [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'accent' => '#ff6b35',
        ];
        
        return rest_ensure_response([
            'success' => true,
            'service_key' => $service_key,
            'query_fields' => $query_fields,
            'color_scheme' => $colors,
        ]);
    }
    
    /**
     * Get package-specific query fields
     */
    public static function get_package_query_fields($request) {
        $package_id = intval($request['id']);
        
        // Get package
        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
            $package_id
        ), ARRAY_A);
        
        if (!$package) {
            return new WP_Error('package_not_found', __('Package not found', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        // Get service config
        $service_key = $package['service_key'] ?? 'tours';
        $config = ATC_Config_Loader::load_service_config($service_key);
        
        // Get base query fields from service config
        $query_fields = $config['query_fields'] ?? [];
        
        // Get package-specific customizations from metadata
        $metadata = [];
        $query_form_title = '';
        $query_form_subtitle = '';
        $query_custom_message = '';
        
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
            
            if (is_array($metadata)) {
                if (!empty($metadata['query_fields']) && is_array($metadata['query_fields'])) {
                    // Merge package-specific fields
                    $query_fields = array_merge($query_fields, $metadata['query_fields']);
                }
                
                if (!empty($metadata['query_prefilled']) && is_array($metadata['query_prefilled'])) {
                    // Add pre-filled values to fields
                    foreach ($query_fields as &$field) {
                        $field_id = $field['id'] ?? '';
                        if (!empty($field_id) && isset($metadata['query_prefilled'][$field_id])) {
                            $field['default'] = $metadata['query_prefilled'][$field_id];
                        }
                    }
                }
                
                // Get custom title, subtitle, and message
                $query_form_title = $metadata['query_form_title'] ?? '';
                $query_form_subtitle = $metadata['query_form_subtitle'] ?? '';
                $query_custom_message = $metadata['query_custom_message'] ?? '';
            }
        }
        
        // Get service theme for colors
        $theme = ATC_Service_Ecosystem::get_service_theme($service_key);
        $colors = $theme['color_scheme'] ?? [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'accent' => '#ff6b35',
        ];
        
        return rest_ensure_response([
            'success' => true,
            'package_id' => $package_id,
            'service_key' => $service_key,
            'query_fields' => $query_fields,
            'query_form_title' => $query_form_title,
            'query_form_subtitle' => $query_form_subtitle,
            'query_custom_message' => $query_custom_message,
            'color_scheme' => $colors,
        ]);
    }
    
    /**
     * Package-specific query form shortcode
     */
    public static function package_query_shortcode($atts) {
        $atts = shortcode_atts([
            'package_id' => '',
            'title' => '', // Will be loaded from package metadata if empty
            'subtitle' => '', // Will be loaded from package metadata if empty
            'button_text' => __('Ask for More Details', 'advanced-travel-crm'),
            'button_style' => 'premium',
            'show_button' => 'true',
            'modal_only' => 'false', // If true, only render modal, no wrapper or inline form
        ], $atts);
        
        $package_id = !empty($atts['package_id']) ? intval($atts['package_id']) : (isset($_GET['package_id']) ? intval($_GET['package_id']) : 0);
        
        if (!$package_id) {
            return '<p class="atc-error">Package ID is required.</p>';
        }
        
        // Get package to determine service and load metadata
        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT service_key, metadata FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
            $package_id
        ), ARRAY_A);
        
        if (!$package) {
            return '<p class="atc-error">Package not found.</p>';
        }
        
        $service_key = $package['service_key'];
        
        // Get service theme for colors
        $theme = ATC_Service_Ecosystem::get_service_theme($service_key);
        $colors = $theme['color_scheme'] ?? [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'accent' => '#ff6b35',
        ];
        
        // Load custom title and subtitle from package metadata
        $metadata = [];
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
        }
        
        // Use package-specific title/subtitle if available, otherwise use defaults
        $title = !empty($atts['title']) ? $atts['title'] : (!empty($metadata['query_form_title']) ? $metadata['query_form_title'] : __('Ask for More Details', 'advanced-travel-crm'));
        $subtitle = !empty($atts['subtitle']) ? $atts['subtitle'] : (!empty($metadata['query_form_subtitle']) ? $metadata['query_form_subtitle'] : __('Have questions about this package? Fill in the details below.', 'advanced-travel-crm'));
        $custom_message = !empty($metadata['query_custom_message']) ? $metadata['query_custom_message'] : '';
        
        ob_start();
        
        // If modal_only is true, only render the modal (no wrapper, no button, no inline form)
        if ($atts['modal_only'] === 'true') {
            // Only render the modal HTML
            ?>
            <!-- Premium Query Modal -->
        <div id="atc-package-query-modal-<?php echo esc_attr($package_id); ?>" class="atc-package-query-modal atc-query-modal" style="display: none;" data-service="<?php echo esc_attr($service_key); ?>">
            <div class="atc-query-modal-overlay"></div>
            <div class="atc-query-modal-content" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>;">
                <div class="atc-query-modal-header" style="background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%);">
                    <h2><?php echo esc_html($title); ?></h2>
                    <button class="atc-query-modal-close" aria-label="Close">&times;</button>
                </div>
                <div class="atc-query-modal-body">
                    <?php if (!empty($custom_message)): ?>
                        <div class="atc-query-custom-message">
                            <?php echo wp_kses_post($custom_message); ?>
                        </div>
                    <?php elseif (!empty($subtitle)): ?>
                        <p class="atc-query-modal-subtitle"><?php echo esc_html($subtitle); ?></p>
                    <?php endif; ?>
                    <div class="atc-package-query-form-container" data-package-id="<?php echo esc_attr($package_id); ?>">
                        <div class="atc-query-loading">
                            <div class="atc-spinner"></div>
                            <p><?php _e('Loading query form...', 'advanced-travel-crm'); ?></p>
                        </div>
                        <div class="atc-query-form-content" style="display: none;">
                            <!-- Form will be loaded here by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inject service-specific colors for package query -->
        <style>
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-query-modal-header {
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
        }
        .atc-package-query-wrapper[data-package-id="<?php echo esc_attr($package_id); ?>"] .atc-package-query-btn,
        .atc-package-query-wrapper[data-package-id="<?php echo esc_attr($package_id); ?>"] .atc-query-trigger-premium {
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
            box-shadow: 0 8px 25px <?php echo esc_attr($colors['primary']); ?>40 !important;
        }
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-query-form input:focus,
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-package-query-form input:focus,
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-query-form textarea:focus,
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-package-query-form textarea:focus {
            border-color: <?php echo esc_attr($colors['primary']); ?> !important;
            box-shadow: 0 0 0 4px <?php echo esc_attr($colors['primary']); ?>10 !important;
        }
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-query-form button[type="submit"],
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-package-query-form button[type="submit"] {
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
            box-shadow: 0 8px 25px <?php echo esc_attr($colors['primary']); ?>40 !important;
        }
        </style>
            <?php
            return ob_get_clean();
        }
        
        // Normal rendering with wrapper
        ?>
        <div class="atc-package-query-wrapper" data-package-id="<?php echo esc_attr($package_id); ?>" data-service="<?php echo esc_attr($service_key); ?>" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>;">
            <?php if ($atts['show_button'] === 'true'): ?>
                <div class="atc-query-trigger-wrapper">
                    <button type="button" class="atc-package-query-btn atc-query-trigger-premium" data-atc-package-query-trigger>
                        <span class="atc-query-icon">💬</span>
                        <span class="atc-query-text"><?php echo esc_html($atts['button_text']); ?></span>
                        <span class="atc-query-trigger-arrow">→</span>
                    </button>
                </div>
            <?php else: ?>
                <?php echo self::render_query_form($package_id, $service_key, array_merge($atts, ['title' => $title, 'subtitle' => $subtitle, 'custom_message' => $custom_message])); ?>
            <?php endif; ?>
        </div>
        
        <!-- Premium Query Modal -->
        <div id="atc-package-query-modal-<?php echo esc_attr($package_id); ?>" class="atc-package-query-modal atc-query-modal" style="display: none;" data-service="<?php echo esc_attr($service_key); ?>">
            <div class="atc-query-modal-overlay"></div>
            <div class="atc-query-modal-content" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>;">
                <div class="atc-query-modal-header" style="background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%);">
                    <h2><?php echo esc_html($title); ?></h2>
                    <button class="atc-query-modal-close" aria-label="Close">&times;</button>
                </div>
                <div class="atc-query-modal-body">
                    <?php if (!empty($custom_message)): ?>
                        <div class="atc-query-custom-message">
                            <?php echo wp_kses_post($custom_message); ?>
                        </div>
                    <?php elseif (!empty($subtitle)): ?>
                        <p class="atc-query-modal-subtitle"><?php echo esc_html($subtitle); ?></p>
                    <?php endif; ?>
                    <div class="atc-package-query-form-container" data-package-id="<?php echo esc_attr($package_id); ?>">
                        <div class="atc-query-loading">
                            <div class="atc-spinner"></div>
                            <p><?php _e('Loading query form...', 'advanced-travel-crm'); ?></p>
                        </div>
                        <div class="atc-query-form-content" style="display: none;">
                            <!-- Form will be loaded here by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inject service-specific colors for package query -->
        <style>
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-query-modal-header {
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
        }
        .atc-package-query-wrapper[data-package-id="<?php echo esc_attr($package_id); ?>"] .atc-package-query-btn,
        .atc-package-query-wrapper[data-package-id="<?php echo esc_attr($package_id); ?>"] .atc-query-trigger-premium {
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
            box-shadow: 0 8px 25px <?php echo esc_attr($colors['primary']); ?>40 !important;
        }
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-query-form input:focus,
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-package-query-form input:focus,
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-query-form textarea:focus,
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-package-query-form textarea:focus {
            border-color: <?php echo esc_attr($colors['primary']); ?> !important;
            box-shadow: 0 0 0 4px <?php echo esc_attr($colors['primary']); ?>10 !important;
        }
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-query-form button[type="submit"],
        #atc-package-query-modal-<?php echo esc_attr($package_id); ?> .atc-package-query-form button[type="submit"] {
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
            box-shadow: 0 8px 25px <?php echo esc_attr($colors['primary']); ?>40 !important;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * General query form shortcode (service-specific) - PREMIUM MODAL DESIGN
     */
    public static function query_form_shortcode($atts) {
        $atts = shortcode_atts([
            'service' => '',
            'package_id' => '',
            'title' => '', // Will use service-specific default if empty
            'subtitle' => '', // Will use service-specific default if empty
            'button_text' => __('Request Custom Package', 'advanced-travel-crm'),
            'button_style' => 'premium',
            'show_button' => 'true',
            'modal_id' => 'atc-query-form-modal',
        ], $atts);
        
        // If package_id is provided, use package-specific query
        if (!empty($atts['package_id'])) {
            return self::package_query_shortcode($atts);
        }
        
        // Get service configuration
        $service_key = !empty($atts['service']) ? $atts['service'] : ATC_Services::detect_service_context();
        
        // Get service theme for colors (use account system colors instead)
        $theme = ATC_Service_Ecosystem::get_service_theme($service_key);
        $colors = $theme['color_scheme'] ?? [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'accent' => '#ff6b35',
        ];
        
        // Use account system colors (matching login form)
        $account_primary = '#667eea';
        $account_secondary = '#764ba2';
        
        // Use service-specific defaults if not provided
        $title = !empty($atts['title']) ? $atts['title'] : __('Send Us a Query', 'advanced-travel-crm');
        $subtitle = !empty($atts['subtitle']) ? $atts['subtitle'] : __('We\'ll get back to you as soon as possible', 'advanced-travel-crm');
        
        // Generate unique modal ID
        $modal_id = $atts['modal_id'] . '-' . $service_key . '-' . wp_rand(1000, 9999);
        
        ob_start();
        ?>
        <div class="atc-query-form-wrapper" data-service="<?php echo esc_attr($service_key); ?>" data-modal-id="<?php echo esc_attr($modal_id); ?>">
            <?php if ($atts['show_button'] === 'true'): ?>
                <div class="atc-query-trigger-wrapper">
                    <button type="button" class="atc-query-trigger-btn atc-query-trigger-<?php echo esc_attr($atts['button_style']); ?>" data-atc-query-form-trigger data-service="<?php echo esc_attr($service_key); ?>" data-modal-id="<?php echo esc_attr($modal_id); ?>" style="--atc-primary: <?php echo esc_attr($account_primary); ?>; --atc-secondary: <?php echo esc_attr($account_secondary); ?>;">
                        <span class="atc-query-trigger-icon">💬</span>
                        <span class="atc-query-trigger-text"><?php echo esc_html($atts['button_text']); ?></span>
                        <span class="atc-query-trigger-arrow">→</span>
                    </button>
                </div>
            <?php else: ?>
                <?php echo self::render_simple_query_form($service_key, $title, $subtitle, $account_primary, $account_secondary); ?>
            <?php endif; ?>
        </div>
        
        <!-- Premium Query Modal - Matching Account System Style -->
        <div id="<?php echo esc_attr($modal_id); ?>" class="atc-query-modal atc-simple-query-modal" style="display: none;" data-service="<?php echo esc_attr($service_key); ?>">
            <div class="atc-query-modal-overlay"></div>
            <div class="atc-query-modal-content atc-premium-auth-form">
                <button class="atc-query-modal-close" aria-label="Close">&times;</button>
                <div class="atc-auth-header">
                    <h2><?php echo esc_html($title); ?></h2>
                    <?php if (!empty($subtitle)): ?>
                        <p><?php echo esc_html($subtitle); ?></p>
                    <?php endif; ?>
                </div>
                <?php echo self::render_simple_query_form($service_key, '', '', $account_primary, $account_secondary, $modal_id); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render simple query form (matching account system style)
     * Shows: Service (dropdown), Package (dropdown), Name (required), Phone (required), Email, Message
     * Made public for use by floating query button
     */
    public static function render_simple_query_form($service_key, $title = '', $subtitle = '', $primary_color = '#667eea', $secondary_color = '#764ba2', $form_id = '') {
        $form_class = !empty($form_id) ? 'atc-simple-query-form-modal' : 'atc-simple-query-form-inline';
        $form_attr = !empty($form_id) ? 'data-form-id="' . esc_attr($form_id) . '"' : '';
        
        // Get all services for dropdown (exclude safari - it's a category, not a service)
        $services = [];
        if (class_exists('ATC_Services')) {
            $service_configs = ATC_Services::get_services();
            foreach ($service_configs as $key => $config) {
                // Exclude safari service - it's only a tour category
                if ($key !== 'safari') {
                    $services[$key] = $config['label'] ?? ucfirst($key);
                }
            }
        }
        
        // Get packages for the selected service (or all packages if no service selected)
        global $wpdb;
        $packages = [];
        if (!empty($service_key)) {
            $packages_query = $wpdb->prepare(
                "SELECT id, package_name FROM " . ATC_TABLE_CUSTOM_PACKAGES . " 
                WHERE service = %s AND status = 'active' 
                ORDER BY package_name ASC",
                $service_key
            );
        } else {
            $packages_query = "SELECT id, package_name, service FROM " . ATC_TABLE_CUSTOM_PACKAGES . " 
                WHERE status = 'active' 
                ORDER BY service ASC, package_name ASC";
        }
        $packages = $wpdb->get_results($packages_query, ARRAY_A);
        
        ob_start();
        ?>
        <form class="atc-premium-form atc-simple-query-form <?php echo esc_attr($form_class); ?>" data-service="<?php echo esc_attr($service_key); ?>" <?php echo $form_attr; ?>>
            <div class="atc-message-container"></div>
            
            <?php if (count($services) > 1): ?>
            <div class="atc-premium-form-group">
                <label for="query_service_<?php echo esc_attr($form_id); ?>"><?php _e('Service', 'advanced-travel-crm'); ?></label>
                <select name="service" id="query_service_<?php echo esc_attr($form_id); ?>" 
                        class="atc-query-field atc-service-select" 
                        data-form-id="<?php echo esc_attr($form_id); ?>">
                    <option value=""><?php _e('Select Service', 'advanced-travel-crm'); ?></option>
                    <?php foreach ($services as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($service_key, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
                <input type="hidden" name="service" value="<?php echo esc_attr($service_key); ?>">
            <?php endif; ?>
            
            <div class="atc-premium-form-group">
                <label for="query_package_name_<?php echo esc_attr($form_id); ?>"><?php _e('Package Name', 'advanced-travel-crm'); ?></label>
                <input type="text" name="package_name" id="query_package_name_<?php echo esc_attr($form_id); ?>" 
                       placeholder="<?php _e('Enter the package name you are interested in (Optional)', 'advanced-travel-crm'); ?>"
                       class="atc-query-field">
                <small><?php _e('Tell us which package you are interested in', 'advanced-travel-crm'); ?></small>
            </div>
            
            <div class="atc-premium-form-group">
                <label for="query_name_<?php echo esc_attr($form_id); ?>"><?php _e('Full Name', 'advanced-travel-crm'); ?> *</label>
                <input type="text" name="name" id="query_name_<?php echo esc_attr($form_id); ?>" 
                       placeholder="<?php _e('Enter your full name', 'advanced-travel-crm'); ?>"
                       required
                       class="atc-query-field">
            </div>
            
            <div class="atc-premium-form-group">
                <label for="query_phone_<?php echo esc_attr($form_id); ?>"><?php _e('Phone Number', 'advanced-travel-crm'); ?> *</label>
                <input type="tel" name="phone" id="query_phone_<?php echo esc_attr($form_id); ?>" 
                       placeholder="<?php _e('Enter your phone number', 'advanced-travel-crm'); ?>"
                       required
                       class="atc-query-field">
                <small><?php _e('Phone number is required for us to contact you', 'advanced-travel-crm'); ?></small>
            </div>
            
            <div class="atc-premium-form-group">
                <label for="query_email_<?php echo esc_attr($form_id); ?>"><?php _e('Email Address', 'advanced-travel-crm'); ?></label>
                <input type="email" name="email" id="query_email_<?php echo esc_attr($form_id); ?>" 
                       placeholder="<?php _e('Enter your email address', 'advanced-travel-crm'); ?>"
                       class="atc-query-field">
            </div>
            
            <!-- Travel Date -->
            <div class="atc-premium-form-group">
                <label for="query_travel_date_<?php echo esc_attr($form_id); ?>"><?php _e('Travel Date', 'advanced-travel-crm'); ?></label>
                <input type="date" name="travel_date" id="query_travel_date_<?php echo esc_attr($form_id); ?>" 
                       min="<?php echo date('Y-m-d'); ?>"
                       class="atc-query-field">
                <small><?php _e('When do you plan to travel?', 'advanced-travel-crm'); ?></small>
            </div>
            
            <!-- Trip Type -->
            <div class="atc-premium-form-group">
                <label for="query_trip_type_<?php echo esc_attr($form_id); ?>"><?php _e('Trip Type', 'advanced-travel-crm'); ?></label>
                <select name="trip_type" id="query_trip_type_<?php echo esc_attr($form_id); ?>" class="atc-query-field">
                    <option value=""><?php _e('Select Trip Type', 'advanced-travel-crm'); ?></option>
                    <option value="solo"><?php _e('Solo', 'advanced-travel-crm'); ?></option>
                    <option value="couple"><?php _e('Couple', 'advanced-travel-crm'); ?></option>
                    <option value="family"><?php _e('Family', 'advanced-travel-crm'); ?></option>
                    <option value="friends"><?php _e('Friends', 'advanced-travel-crm'); ?></option>
                    <option value="business"><?php _e('Business', 'advanced-travel-crm'); ?></option>
                    <option value="group"><?php _e('Group', 'advanced-travel-crm'); ?></option>
                </select>
                <small><?php _e('What type of trip are you planning?', 'advanced-travel-crm'); ?></small>
            </div>
            
            <!-- Number of Travelers -->
            <div class="atc-premium-form-group">
                <label><?php _e('Number of Travelers', 'advanced-travel-crm'); ?></label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label for="query_adults_<?php echo esc_attr($form_id); ?>" style="font-size: 13px; color: #666;"><?php _e('Adults', 'advanced-travel-crm'); ?></label>
                        <input type="number" name="adults" id="query_adults_<?php echo esc_attr($form_id); ?>" 
                               min="1" 
                               value="2"
                               class="atc-query-field atc-travelers-count"
                               data-form-id="<?php echo esc_attr($form_id); ?>">
                    </div>
                    <div>
                        <label for="query_children_<?php echo esc_attr($form_id); ?>" style="font-size: 13px; color: #666;"><?php _e('Children', 'advanced-travel-crm'); ?></label>
                        <input type="number" name="children" id="query_children_<?php echo esc_attr($form_id); ?>" 
                               min="0" 
                               value="0"
                               class="atc-query-field atc-children-count"
                               data-form-id="<?php echo esc_attr($form_id); ?>">
                    </div>
                </div>
                <small><?php _e('Number of adults and children traveling', 'advanced-travel-crm'); ?></small>
            </div>
            
            <!-- Child Ages (shown only if children > 0) -->
            <div class="atc-premium-form-group atc-child-ages-group" id="child_ages_<?php echo esc_attr($form_id); ?>" style="display: none;">
                <label><?php _e('Children Ages', 'advanced-travel-crm'); ?></label>
                <div id="child_ages_container_<?php echo esc_attr($form_id); ?>" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Child age inputs will be dynamically added here -->
                </div>
                <small><?php _e('Enter the age of each child (required for accurate pricing)', 'advanced-travel-crm'); ?></small>
            </div>
            
            <!-- Budget -->
            <div class="atc-premium-form-group">
                <label for="query_budget_<?php echo esc_attr($form_id); ?>"><?php _e('Budget Range', 'advanced-travel-crm'); ?></label>
                <select name="budget" id="query_budget_<?php echo esc_attr($form_id); ?>" class="atc-query-field">
                    <option value=""><?php _e('Select Budget Range', 'advanced-travel-crm'); ?></option>
                    <option value="under-50000"><?php _e('Under ₹50,000', 'advanced-travel-crm'); ?></option>
                    <option value="50000-100000"><?php _e('₹50,000 - ₹1,00,000', 'advanced-travel-crm'); ?></option>
                    <option value="100000-200000"><?php _e('₹1,00,000 - ₹2,00,000', 'advanced-travel-crm'); ?></option>
                    <option value="200000-500000"><?php _e('₹2,00,000 - ₹5,00,000', 'advanced-travel-crm'); ?></option>
                    <option value="500000-1000000"><?php _e('₹5,00,000 - ₹10,00,000', 'advanced-travel-crm'); ?></option>
                    <option value="over-1000000"><?php _e('Over ₹10,00,000', 'advanced-travel-crm'); ?></option>
                    <option value="custom"><?php _e('Custom Budget', 'advanced-travel-crm'); ?></option>
                </select>
                <small><?php _e('What is your approximate budget for this trip?', 'advanced-travel-crm'); ?></small>
            </div>
            
            <!-- Custom Budget (shown only if "Custom Budget" selected) -->
            <div class="atc-premium-form-group atc-custom-budget-group" id="custom_budget_<?php echo esc_attr($form_id); ?>" style="display: none;">
                <label for="query_custom_budget_<?php echo esc_attr($form_id); ?>"><?php _e('Custom Budget Amount', 'advanced-travel-crm'); ?></label>
                <input type="text" name="custom_budget" id="query_custom_budget_<?php echo esc_attr($form_id); ?>" 
                       placeholder="<?php _e('e.g., ₹75,000', 'advanced-travel-crm'); ?>"
                       class="atc-query-field">
            </div>
            
            <!-- Hotel Type (only for services that need hotels) -->
            <div class="atc-premium-form-group atc-hotel-type-group" 
                 data-services="<?php echo esc_attr(json_encode(['tours', 'hotels'])); ?>"
                 style="display: none;">
                <label for="query_hotel_type_<?php echo esc_attr($form_id); ?>"><?php _e('Preferred Hotel Type', 'advanced-travel-crm'); ?></label>
                <select name="hotel_type" id="query_hotel_type_<?php echo esc_attr($form_id); ?>" class="atc-query-field">
                    <option value=""><?php _e('Select Hotel Type', 'advanced-travel-crm'); ?></option>
                    <option value="3-star"><?php _e('3 Star', 'advanced-travel-crm'); ?></option>
                    <option value="4-star"><?php _e('4 Star', 'advanced-travel-crm'); ?></option>
                    <option value="5-star"><?php _e('5 Star', 'advanced-travel-crm'); ?></option>
                    <option value="luxury"><?php _e('Luxury', 'advanced-travel-crm'); ?></option>
                    <option value="budget"><?php _e('Budget', 'advanced-travel-crm'); ?></option>
                    <option value="resort"><?php _e('Resort', 'advanced-travel-crm'); ?></option>
                    <option value="no-preference"><?php _e('No Preference', 'advanced-travel-crm'); ?></option>
                </select>
                <small><?php _e('What type of hotel accommodation do you prefer?', 'advanced-travel-crm'); ?></small>
            </div>
            
            <div class="atc-premium-form-group">
                <label for="query_message_<?php echo esc_attr($form_id); ?>"><?php _e('Query / Message', 'advanced-travel-crm'); ?></label>
                <textarea name="message" id="query_message_<?php echo esc_attr($form_id); ?>" 
                          rows="5"
                          placeholder="<?php _e('Tell us about your query or requirements...', 'advanced-travel-crm'); ?>"
                          class="atc-query-field"></textarea>
                <small><?php _e('Describe your query or any special requirements', 'advanced-travel-crm'); ?></small>
            </div>
            
            <div class="atc-premium-form-actions">
                <button type="submit" class="atc-btn atc-btn-primary atc-submit-query-btn">
                    <span><?php _e('Submit Query', 'advanced-travel-crm'); ?></span>
                </button>
            </div>
            
            <style>
            .atc-simple-query-form .atc-btn-primary {
                background: linear-gradient(135deg, <?php echo esc_attr($primary_color); ?> 0%, <?php echo esc_attr($secondary_color); ?> 100%) !important;
                width: 100%;
            }
            .atc-simple-query-form .atc-premium-form-group input:focus,
            .atc-simple-query-form .atc-premium-form-group textarea:focus {
                border-color: <?php echo esc_attr($primary_color); ?> !important;
                box-shadow: 0 0 0 4px <?php echo esc_attr($primary_color); ?>10 !important;
            }
            </style>
        </form>
        
        <!-- Styles are handled by atc-premium-query-form.css -->
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render service-specific query form (inline, not modal) - DEPRECATED
     * Keeping for backward compatibility
     */
    private static function render_service_query_form($service_key, $query_fields, $title, $subtitle, $colors) {
        return self::render_simple_query_form($service_key, $title, $subtitle, $colors['primary'], $colors['secondary']);
    }
    
    /**
     * Render query form (service-specific)
     */
    public static function render_query_form($package_id, $service_key, $atts = []) {
        // Get service config
        $config = ATC_Config_Loader::load_service_config($service_key);
        $query_fields = $config['query_fields'] ?? [];
        
        // Get package-specific customizations
        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT metadata FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
            $package_id
        ), ARRAY_A);
        
        if ($package && !empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
            
            if (!empty($metadata['query_fields']) && is_array($metadata['query_fields'])) {
                $query_fields = array_merge($query_fields, $metadata['query_fields']);
            }
        }
        
        ob_start();
        ?>
        <form class="atc-package-query-form" data-package-id="<?php echo esc_attr($package_id); ?>" data-service="<?php echo esc_attr($service_key); ?>">
            <div class="atc-message-container"></div>
            
            <!-- Service-Specific Query Fields -->
            <div class="atc-query-form-fields">
                <?php echo self::render_query_fields($query_fields, $service_key, $package_id); ?>
            </div>
            
            <button type="submit" class="atc-btn-premium atc-btn-premium-primary">
                <span><?php _e('Submit Query', 'advanced-travel-crm'); ?></span>
            </button>
        </form>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render query fields based on service config
     */
    private static function render_query_fields($fields, $service_key, $package_id) {
        if (empty($fields) || !is_array($fields)) {
            // Fallback to default fields
            return self::render_default_query_fields($package_id);
        }
        
        $html = '';
        
        // Group fields by section
        $sections = [
            'personal' => [],
            'service_specific' => [],
            'additional' => [],
        ];
        
        foreach ($fields as $field) {
            $section = $field['section'] ?? 'service_specific';
            if (!isset($sections[$section])) {
                $sections[$section] = [];
            }
            $sections[$section][] = $field;
        }
        
        // Render Personal Information Section
        if (!empty($sections['personal'])) {
            $html .= '<div class="atc-query-form-section">';
            $html .= '<div class="atc-query-form-section-title"><span>👤</span>' . __('Personal Information', 'advanced-travel-crm') . '</div>';
            $html .= '<div class="atc-form-row">';
            foreach ($sections['personal'] as $field) {
                $html .= self::render_query_field($field);
            }
            $html .= '</div></div>';
        }
        
        // Render Service-Specific Section
        if (!empty($sections['service_specific'])) {
            $html .= '<div class="atc-query-form-section">';
            $html .= '<div class="atc-query-form-section-title"><span>📋</span>' . __('Package Details', 'advanced-travel-crm') . '</div>';
            $html .= '<div class="atc-form-row">';
            foreach ($sections['service_specific'] as $field) {
                $html .= self::render_query_field($field);
            }
            $html .= '</div></div>';
        }
        
        // Render Additional Information Section
        if (!empty($sections['additional'])) {
            $html .= '<div class="atc-query-form-section">';
            $html .= '<div class="atc-query-form-section-title"><span>⭐</span>' . __('Additional Information', 'advanced-travel-crm') . '</div>';
            foreach ($sections['additional'] as $field) {
                $html .= self::render_query_field($field, true);
            }
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Render single query field
     */
    private static function render_query_field($field, $full_width = false) {
        $field_id = $field['id'] ?? '';
        $field_label = $field['label'] ?? '';
        $field_type = $field['type'] ?? 'text';
        $field_required = !empty($field['required']);
        $field_default = $field['default'] ?? '';
        $field_placeholder = $field['placeholder'] ?? '';
        $field_options = $field['options'] ?? [];
        
        $html = '<div class="atc-form-group' . ($full_width ? ' full-width' : '') . '">';
        $html .= '<label for="query_' . esc_attr($field_id) . '">' . esc_html($field_label);
        if ($field_required) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';
        
        switch ($field_type) {
            case 'select':
                $html .= '<select id="query_' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '"' . ($field_required ? ' required' : '') . '>';
                $html .= '<option value="">' . __('Select', 'advanced-travel-crm') . '</option>';
                foreach ($field_options as $option) {
                    $option_value = is_array($option) ? $option['value'] : $option;
                    $option_label = is_array($option) ? $option['label'] : $option;
                    $selected = ($field_default === $option_value) ? ' selected' : '';
                    $html .= '<option value="' . esc_attr($option_value) . '"' . $selected . '>' . esc_html($option_label) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'textarea':
                $html .= '<textarea id="query_' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" rows="4"' . ($field_required ? ' required' : '') . ' placeholder="' . esc_attr($field_placeholder) . '">' . esc_textarea($field_default) . '</textarea>';
                break;
                
            case 'date':
                $html .= '<input type="date" id="query_' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($field_default) . '"' . ($field_required ? ' required' : '') . '>';
                break;
                
            case 'number':
                $html .= '<input type="number" id="query_' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($field_default) . '"' . ($field_required ? ' required' : '') . ' placeholder="' . esc_attr($field_placeholder) . '">';
                break;
                
            default:
                $html .= '<input type="' . esc_attr($field_type) . '" id="query_' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($field_default) . '"' . ($field_required ? ' required' : '') . ' placeholder="' . esc_attr($field_placeholder) . '">';
                break;
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render default query fields (fallback)
     */
    private static function render_default_query_fields($package_id) {
        ob_start();
        ?>
        <div class="atc-query-form-section">
            <div class="atc-query-form-section-title">
                <span>👤</span>
                <?php _e('Personal Information', 'advanced-travel-crm'); ?>
            </div>
            
            <div class="atc-form-row">
                <div class="atc-form-group">
                    <label for="customer_name"><?php _e('Your Name', 'advanced-travel-crm'); ?> *</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>
                
                <div class="atc-form-group">
                    <label for="customer_email"><?php _e('Email Address', 'advanced-travel-crm'); ?> *</label>
                    <input type="email" id="customer_email" name="customer_email" required>
                </div>
            </div>
            
            <div class="atc-form-row">
                <div class="atc-form-group">
                    <label for="customer_phone"><?php _e('Phone Number', 'advanced-travel-crm'); ?> *</label>
                    <input type="tel" id="customer_phone" name="customer_phone" required>
                </div>
            </div>
        </div>
        
        <div class="atc-query-form-section">
            <div class="atc-query-form-section-title">
                <span>💬</span>
                <?php _e('Your Question', 'advanced-travel-crm'); ?>
            </div>
            
            <div class="atc-form-group full-width">
                <label for="message"><?php _e('Message', 'advanced-travel-crm'); ?> *</label>
                <textarea id="message" name="message" rows="5" required placeholder="<?php _e('Ask any questions about this package...', 'advanced-travel-crm'); ?>"></textarea>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Submit query
     */
    public static function submit_query($request) {
        $params = $request->get_json_params();
        
        // Generate query ID
        $query_id = 'QRY-' . date('Ymd') . '-' . wp_rand(1000, 9999);
        
        // Get package to determine service
        $package_id = !empty($params['package_id']) ? intval($params['package_id']) : 0;
        $service_key = '';
        
        if ($package_id) {
            global $wpdb;
            $package = $wpdb->get_row($wpdb->prepare(
                "SELECT service_key FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
                $package_id
            ), ARRAY_A);
            
            if ($package) {
                $service_key = $package['service_key'];
            }
        }
        
        // Handle simple form field names (support both formats)
        $customer_name = '';
        if (!empty($params['customer_name'])) {
            $customer_name = sanitize_text_field($params['customer_name']);
        } elseif (!empty($params['name'])) {
            $customer_name = sanitize_text_field($params['name']);
        }
        
        $customer_phone = '';
        if (!empty($params['customer_phone'])) {
            $customer_phone = sanitize_text_field($params['customer_phone']);
        } elseif (!empty($params['phone'])) {
            $customer_phone = sanitize_text_field($params['phone']);
        }
        
        $customer_email = '';
        if (!empty($params['customer_email'])) {
            $customer_email = sanitize_email($params['customer_email']);
        } elseif (!empty($params['email'])) {
            $customer_email = sanitize_email($params['email']);
        }
        
        $message = !empty($params['message']) ? sanitize_textarea_field($params['message']) : '';
        
        // Validate required fields
        if (empty($customer_phone)) {
            return new WP_Error('missing_phone', __('Phone number is required', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        // If no service key, detect from context
        if (empty($service_key)) {
            $service_key = ATC_Services::detect_service_context();
        }
        
        // Map 'package' field to 'destination' if destination is empty (user fills package as destination)
        $destination = sanitize_text_field($params['destination'] ?? '');
        if (empty($destination) && !empty($params['package'])) {
            $destination = sanitize_text_field($params['package']);
        }
        
        // Build query data (no duplicate keys)
        $query_data = [
            'query_id' => $query_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'service_key' => $service_key,
            'package_id' => $package_id ?: null,
            'query_type' => 'package_query',
            'destination' => $destination,
            'package_name' => $destination, // Use same value for package_name
            'travel_date' => !empty($params['travel_date']) ? sanitize_text_field($params['travel_date']) : null,
            'return_date' => !empty($params['return_date']) ? sanitize_text_field($params['return_date']) : null,
            'adults' => intval($params['adults'] ?? 1),
            'children' => intval($params['children'] ?? 0),
            'budget_min' => 0,
            'budget_max' => 0,
            'special_requirements' => sanitize_textarea_field($params['special_requirements'] ?? $params['requirements'] ?? ''),
            'message' => $message,
            'status' => 'pending',
            'user_id' => get_current_user_id() ?: null,
            'created_at' => current_time('mysql'),
        ];

        // Parse Budget (e.g. "50000-100000" -> min: 50000, max: 100000)
        $budget_str = !empty($params['custom_budget']) ? $params['custom_budget'] : ($params['budget'] ?? '');
        
        if (!empty($budget_str)) {
            // Remove currency symbols and non-numeric chars (keep hyphen)
            $clean_budget = preg_replace('/[^0-9\-]/', '', $budget_str);
            
            if (strpos($clean_budget, '-') !== false) {
                $parts = explode('-', $clean_budget);
                $query_data['budget_min'] = intval($parts[0]);
                $query_data['budget_max'] = intval($parts[1]);
            } else {
                $val = intval($clean_budget);
                if (strpos(strtolower($budget_str), 'under') !== false) {
                    $query_data['budget_max'] = $val;
                } elseif (strpos(strtolower($budget_str), 'over') !== false || strpos(strtolower($budget_str), 'above') !== false) {
                    $query_data['budget_min'] = $val;
                } else {
                    $query_data['budget_min'] = $val; // Default to min for custom single values
                }
            }
        }
        
        // Add Package Name if package exists
        if ($package_id) {
            $package_row = $wpdb->get_row($wpdb->prepare("SELECT package_name FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d", $package_id));
            if ($package_row) {
                $query_data['package_name'] = $package_row->package_name;
            }
        }
        
        // Add service-specific fields
        $meta_fields = [];
        if ($service_key) {
            $config = ATC_Config_Loader::load_service_config($service_key);
            if (!empty($config['query_fields'])) {
                foreach ($config['query_fields'] as $field) {
                    $field_id = $field['id'] ?? '';
                    if (!empty($field_id) && isset($params[$field_id])) {
                        // Store in metadata array
                        $meta_fields[$field_id] = sanitize_text_field($params[$field_id]);
                    }
                }
            }
        }
        
        if (!empty($meta_fields)) {
            $query_data['metadata'] = json_encode(['service_fields' => $meta_fields]);
        }
        
        global $wpdb;
        
        // Self-healing: Ensure metadata column exists
        // (Fixes 500 error if column is missing from previous installs)
        $column_check = $wpdb->get_results("SHOW COLUMNS FROM " . ATC_TABLE_PACKAGE_QUERIES . " LIKE 'metadata'");
        if (empty($column_check)) {
            $wpdb->query("ALTER TABLE " . ATC_TABLE_PACKAGE_QUERIES . " ADD COLUMN metadata LONGTEXT AFTER user_id");
        }
        
        $result = $wpdb->insert(ATC_TABLE_PACKAGE_QUERIES, $query_data);
        
        if ($result) {
            // Trigger notification
            do_action('atc_query_submitted', $wpdb->insert_id, $query_data);
            
            return rest_ensure_response([
                'success' => true,
                'query_id' => $query_id,
                'message' => __('Your query has been submitted successfully! We will get back to you soon.', 'advanced-travel-crm'),
            ]);
        }
        
        // Log error for debugging
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('error', 'Query submission failed: ' . $wpdb->last_error);
        } else {
            error_log('ATC Query Error: ' . $wpdb->last_error);
        }
        
        return new WP_Error('query_submission_failed', __('Failed to submit query', 'advanced-travel-crm'), ['status' => 500]);
    }
    
    /**
     * Admin page for managing package queries
     */
    public static function queries_page() {
        global $wpdb;
        
        // Handle view/edit action
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            self::view_query_page(intval($_GET['id']));
            return;
        }
        
        $status = sanitize_text_field($_GET['status'] ?? 'pending');
        $service_filter = sanitize_text_field($_GET['service'] ?? '');
        
        // Build query
        $where = ["status = %s"];
        $params = [$status];
        
        if (!empty($service_filter)) {
            $where[] = "service_key = %s";
            $params[] = $service_filter;
        }
        
        $sql = "SELECT * FROM " . ATC_TABLE_PACKAGE_QUERIES . " 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY created_at DESC 
                LIMIT 100";
        
        $queries = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        // Get services for filter
        $services = ATC_Services::get_services();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Package Queries', 'advanced-travel-crm'); ?></h1>
            
            <!-- Filters -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="filter-by-service" class="screen-reader-text"><?php _e('Filter by service', 'advanced-travel-crm'); ?></label>
                    <select name="service" id="filter-by-service" onchange="window.location.href='<?php echo admin_url('admin.php?page=atc-package-queries'); ?>&status=<?php echo esc_attr($status); ?>&service=' + this.value;">
                        <option value=""><?php _e('All Services', 'advanced-travel-crm'); ?></option>
                        <?php foreach ($services as $key => $service): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($service_filter, $key); ?>>
                                <?php echo esc_html(is_array($service) ? $service['label'] : $service); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <ul class="subsubsub">
                <li><a href="<?php echo admin_url('admin.php?page=atc-package-queries&status=pending' . (!empty($service_filter) ? '&service=' . $service_filter : '')); ?>" <?php echo $status === 'pending' ? 'class="current"' : ''; ?>>
                    <?php _e('Pending', 'advanced-travel-crm'); ?> <span class="count">(<?php echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . ATC_TABLE_PACKAGE_QUERIES . " WHERE status = %s", 'pending')); ?>)</span>
                </a> |</li>
                <li><a href="<?php echo admin_url('admin.php?page=atc-package-queries&status=responded' . (!empty($service_filter) ? '&service=' . $service_filter : '')); ?>" <?php echo $status === 'responded' ? 'class="current"' : ''; ?>>
                    <?php _e('Responded', 'advanced-travel-crm'); ?> <span class="count">(<?php echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . ATC_TABLE_PACKAGE_QUERIES . " WHERE status = %s", 'responded')); ?>)</span>
                </a> |</li>
                <li><a href="<?php echo admin_url('admin.php?page=atc-package-queries&status=closed' . (!empty($service_filter) ? '&service=' . $service_filter : '')); ?>" <?php echo $status === 'closed' ? 'class="current"' : ''; ?>>
                    <?php _e('Closed', 'advanced-travel-crm'); ?> <span class="count">(<?php echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . ATC_TABLE_PACKAGE_QUERIES . " WHERE status = %s", 'closed')); ?>)</span>
                </a></li>
            </ul>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Query ID', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Date', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Customer', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Service', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Package', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Destination', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Travel Date', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Budget', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Actions', 'advanced-travel-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queries)): ?>
                        <tr>
                            <td colspan="10"><?php _e('No queries found.', 'advanced-travel-crm'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($queries as $query): ?>
                            <tr>
                                <td><strong><?php echo esc_html($query['query_id']); ?></strong></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($query['created_at']))); ?></td>
                                <td>
                                    <strong><?php echo esc_html($query['customer_name']); ?></strong><br>
                                    <small>📧 <?php echo esc_html($query['customer_email']); ?></small><br>
                                    <small>📞 <?php echo esc_html($query['customer_phone']); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($query['service_key'])): ?>
                                        <span class="atc-service-badge"><?php echo esc_html(ucfirst($query['service_key'])); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($query['package_id'])): ?>
                                        <a href="<?php echo admin_url('admin.php?page=atc-custom-packages&action=edit&id=' . $query['package_id']); ?>">
                                            Package #<?php echo esc_html($query['package_id']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="description">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($query['destination'] ?? '-'); ?></td>
                                <td><?php echo $query['travel_date'] ? esc_html(date_i18n(get_option('date_format'), strtotime($query['travel_date']))) : '-'; ?></td>
                                <td>
                                    <?php if ($query['budget_min'] > 0 || $query['budget_max'] > 0): ?>
                                        <strong><?php echo get_option('atc_currency_symbol', '₹'); ?>
                                        <?php echo number_format($query['budget_min']); ?>
                                        <?php if ($query['budget_max'] > $query['budget_min']): ?>
                                            - <?php echo number_format($query['budget_max']); ?>
                                        <?php endif; ?>
                                        </strong>
                                    <?php else: ?>
                                        <span class="description">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="atc-status-badge atc-status-<?php echo esc_attr($query['status']); ?>">
                                        <?php echo esc_html(ucfirst($query['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=atc-package-queries&action=view&id=' . $query['id']); ?>" class="button button-small">
                                        <?php _e('View Details', 'advanced-travel-crm'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <style>
            .atc-status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .atc-status-badge.atc-status-pending {
                background: #fff3cd;
                color: #856404;
            }
            .atc-status-badge.atc-status-responded {
                background: #d1ecf1;
                color: #0c5460;
            }
            .atc-status-badge.atc-status-closed {
                background: #d4edda;
                color: #155724;
            }
            .atc-service-badge {
                display: inline-block;
                padding: 3px 6px;
                background: #f0f0f0;
                border-radius: 3px;
                font-size: 11px;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * View query details page
     */
    public static function view_query_page($query_id) {
        global $wpdb;
        
        $query = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_PACKAGE_QUERIES . " WHERE id = %d",
            $query_id
        ), ARRAY_A);
        
        if (!$query) {
            wp_die(__('Query not found', 'advanced-travel-crm'));
        }
        
        // Parse metadata if exists
        $metadata = [];
        if (!empty($query['metadata'])) {
            $metadata = is_string($query['metadata']) ? json_decode($query['metadata'], true) : $query['metadata'];
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Query Details', 'advanced-travel-crm'); ?>
                <a href="<?php echo admin_url('admin.php?page=atc-package-queries'); ?>" class="page-title-action"><?php _e('Back to Queries', 'advanced-travel-crm'); ?></a>
            </h1>
            
            <div class="atc-query-details" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="atc-query-main">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Query Information', 'advanced-travel-crm'); ?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><?php _e('Query ID', 'advanced-travel-crm'); ?></th>
                                    <td><strong><?php echo esc_html($query['query_id']); ?></strong></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Date', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($query['created_at']))); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Service', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo esc_html(ucfirst($query['service_key'] ?? '-')); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Package ID', 'advanced-travel-crm'); ?></th>
                                    <td>
                                        <?php if (!empty($query['package_id'])): ?>
                                            <a href="<?php echo admin_url('admin.php?page=atc-custom-packages&action=edit&id=' . $query['package_id']); ?>">
                                                Package #<?php echo esc_html($query['package_id']); ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e('Destination', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo esc_html($query['destination'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Travel Date', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo $query['travel_date'] ? esc_html(date_i18n(get_option('date_format'), strtotime($query['travel_date']))) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Return Date', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo $query['return_date'] ? esc_html(date_i18n(get_option('date_format'), strtotime($query['return_date']))) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Adults', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo esc_html($query['adults'] ?? 0); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Children', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo esc_html($query['children'] ?? 0); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Budget', 'advanced-travel-crm'); ?></th>
                                    <td>
                                        <?php if ($query['budget_min'] > 0 || $query['budget_max'] > 0): ?>
                                            <strong><?php echo get_option('atc_currency_symbol', '₹'); ?>
                                            <?php echo number_format($query['budget_min']); ?>
                                            <?php if ($query['budget_max'] > $query['budget_min']): ?>
                                                - <?php echo number_format($query['budget_max']); ?>
                                            <?php endif; ?>
                                            </strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (!empty($query['message'])): ?>
                                <tr>
                                    <th><?php _e('Message', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo nl2br(esc_html($query['message'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($query['special_requirements'])): ?>
                                <tr>
                                    <th><?php _e('Special Requirements', 'advanced-travel-crm'); ?></th>
                                    <td><?php echo nl2br(esc_html($query['special_requirements'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                            
                            <?php if (!empty($metadata) && is_array($metadata)): ?>
                                <h3><?php _e('Additional Information', 'advanced-travel-crm'); ?></h3>
                                <table class="form-table">
                                    <?php foreach ($metadata as $key => $value): ?>
                                        <?php if (is_array($value)) continue; ?>
                                        <tr>
                                            <th><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></th>
                                            <td><?php echo esc_html($value); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="atc-query-sidebar">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Customer Information', 'advanced-travel-crm'); ?></h2>
                        <div class="inside">
                            <p><strong><?php echo esc_html($query['customer_name']); ?></strong></p>
                            <p>📧 <a href="mailto:<?php echo esc_attr($query['customer_email']); ?>"><?php echo esc_html($query['customer_email']); ?></a></p>
                            <p>📞 <a href="tel:<?php echo esc_attr($query['customer_phone']); ?>"><?php echo esc_html($query['customer_phone']); ?></a></p>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Update Status', 'advanced-travel-crm'); ?></h2>
                        <div class="inside">
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                <?php wp_nonce_field('atc_update_query'); ?>
                                <input type="hidden" name="action" value="atc_update_query">
                                <input type="hidden" name="query_id" value="<?php echo esc_attr($query['id']); ?>">
                                
                                <p>
                                    <label for="status"><?php _e('Status', 'advanced-travel-crm'); ?></label>
                                    <select name="status" id="status" class="regular-text">
                                        <option value="pending" <?php selected($query['status'], 'pending'); ?>><?php _e('Pending', 'advanced-travel-crm'); ?></option>
                                        <option value="responded" <?php selected($query['status'], 'responded'); ?>><?php _e('Responded', 'advanced-travel-crm'); ?></option>
                                        <option value="closed" <?php selected($query['status'], 'closed'); ?>><?php _e('Closed', 'advanced-travel-crm'); ?></option>
                                    </select>
                                </p>
                                
                                <p>
                                    <label for="response"><?php _e('Response', 'advanced-travel-crm'); ?></label>
                                    <textarea name="response" id="response" rows="5" class="large-text"><?php echo esc_textarea($query['response'] ?? ''); ?></textarea>
                                </p>
                                
                                <p>
                                    <label for="admin_notes"><?php _e('Admin Notes', 'advanced-travel-crm'); ?></label>
                                    <textarea name="admin_notes" id="admin_notes" rows="3" class="large-text"><?php echo esc_textarea($query['admin_notes'] ?? ''); ?></textarea>
                                </p>
                                
                                <p class="submit">
                                    <input type="submit" class="button button-primary" value="<?php _e('Update Query', 'advanced-travel-crm'); ?>">
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Update query handler
     */
    public static function update_query_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        check_admin_referer('atc_update_query');
        
        $id = intval($_POST['query_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $response = sanitize_textarea_field($_POST['response'] ?? '');
        $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
        
        global $wpdb;
        $wpdb->update(
            ATC_TABLE_PACKAGE_QUERIES,
            [
                'status' => $status,
                'response' => $response,
                'admin_notes' => $admin_notes,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id]
        );
        
        wp_redirect(add_query_arg('atc_notice', 'updated', admin_url('admin.php?page=atc-package-queries')));
        exit;
    }
}

