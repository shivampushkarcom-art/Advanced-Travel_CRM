<?php
/**
 * ATC Floating Query Form Button
 * Simple blue pill button that appears on all pages
 * Similar to WhatsApp button but for query form
 */

if (!defined('ABSPATH')) exit;

class ATC_Floating_Query_Button {
    
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [__CLASS__, 'render_button']);
    }
    
    public static function enqueue_assets() {
        if (is_admin()) {
            return;
        }
        
        if (!defined('ATC_ASSETS_URL') || !defined('ATC_VERSION')) {
            return;
        }
        
        // Enqueue query form assets
        wp_enqueue_style('atc-premium-query-form', ATC_ASSETS_URL . 'css/atc-premium-query-form.css', [], ATC_VERSION);
        wp_enqueue_script('atc-query-form-enhanced', ATC_ASSETS_URL . 'js/atc-query-form-enhanced.js', ['jquery'], ATC_VERSION, true);
        
        // Enqueue floating button specific assets
        wp_enqueue_style('atc-floating-query-button', ATC_ASSETS_URL . 'css/atc-floating-query-button.css', [], ATC_VERSION);
        wp_enqueue_script('atc-floating-query-button', ATC_ASSETS_URL . 'js/atc-floating-query-button.js', ['jquery'], ATC_VERSION, true);
        
        // Localize script
        wp_localize_script('atc-query-form-enhanced', 'atcQueryForm', [
            'restUrl' => esc_url_raw(rest_url('atc/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    
    public static function render_button() {
        if (is_admin()) {
            return;
        }
        
        // Get service context
        $service_key = '';
        if (class_exists('ATC_Services')) {
            $service_key = ATC_Services::detect_service_context();
        }
        
        // Generate unique modal ID
        $modal_id = 'atc-floating-query-modal-' . wp_rand(1000, 9999);
        
        // Account system colors
        $account_primary = '#667eea';
        $account_secondary = '#764ba2';
        
        // Default title and subtitle
        $title = __('Looking for something special? Share your travel needs!', 'advanced-travel-crm');
        $subtitle = __('We\'ll create a custom package just for you', 'advanced-travel-crm');
        
        ?>
        <!-- Floating Query Form Button -->
        <div id="atc-floating-query-button" class="atc-floating-query-button" style="display: none;">
            <button type="button" 
                    class="atc-floating-query-btn" 
                    data-atc-query-form-trigger 
                    data-service="<?php echo esc_attr($service_key); ?>" 
                    data-modal-id="<?php echo esc_attr($modal_id); ?>"
                    style="--atc-primary: <?php echo esc_attr($account_primary); ?>; --atc-secondary: <?php echo esc_attr($account_secondary); ?>;"
                    aria-label="<?php esc_attr_e('Open Query Form', 'advanced-travel-crm'); ?>">
                <span class="atc-floating-query-icon">💬</span>
                <span class="atc-floating-query-text"><?php echo esc_html(__('Request Custom Package', 'advanced-travel-crm')); ?></span>
            </button>
            <button type="button" class="atc-floating-query-close" aria-label="<?php esc_attr_e('Close', 'advanced-travel-crm'); ?>">
                <span>×</span>
            </button>
        </div>
        
        <!-- Query Form Modal -->
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
                <?php 
                // Render query form
                if (class_exists('ATC_Package_Query_Enhanced')) {
                    echo ATC_Package_Query_Enhanced::render_simple_query_form($service_key, '', '', $account_primary, $account_secondary, $modal_id);
                }
                ?>
            </div>
        </div>
        <?php
    }
}

