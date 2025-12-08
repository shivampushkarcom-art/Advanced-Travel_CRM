<?php
/**
 * ATC Inline Text Editor
 * Click-to-edit text functionality
 */

if (!defined('ABSPATH')) exit;

class ATC_Text_Editor {
    
    public static function init() {
        // Delay initialization until WordPress is fully loaded
        // This prevents calling current_user_can() before wp_get_current_user() is available
        add_action('init', [__CLASS__, 'setup'], 20);
    }
    
    /**
     * Setup - Called after WordPress is fully loaded
     */
    public static function setup() {
        // Check if user functions are available
        if (!function_exists('current_user_can') || !function_exists('wp_get_current_user')) {
            return;
        }
        
        // Only for admins
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [__CLASS__, 'add_editor_modal']);
        add_action('wp_ajax_atc_save_text', [__CLASS__, 'save_text']);
    }
    
    /**
     * Enqueue Assets
     */
    public static function enqueue_assets() {
        // Check if constants exist
        $assets_url = defined('ATC_ASSETS_URL') ? ATC_ASSETS_URL : plugin_dir_url(__FILE__) . '../../assets/';
        $version = defined('ATC_VERSION') ? ATC_VERSION : '2.4.0';
        
        if (!is_admin()) {
            wp_enqueue_style('atc-text-editor', $assets_url . 'css/atc-text-editor.css', [], $version);
            wp_enqueue_script('atc-text-editor', $assets_url . 'js/atc-text-editor.js', ['jquery'], $version, true);
            
            // Check if function exists before calling
            $is_admin = function_exists('current_user_can') ? current_user_can('edit_posts') : false;
            
            wp_localize_script('atc-text-editor', 'atcTextEditor', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('atc_text_editor_nonce'),
                'isAdmin' => $is_admin,
            ]);
        }
    }
    
    /**
     * Add Editor Modal
     */
    public static function add_editor_modal() {
        // Check if function exists before calling
        if (!function_exists('current_user_can') || !current_user_can('edit_posts')) {
            return;
        }
        ?>
        <div id="atc-text-editor-modal" class="atc-modal" style="display:none;">
            <div class="atc-modal-content">
                <span class="atc-modal-close">&times;</span>
                <h2>Edit Text</h2>
                <div class="atc-editor-form">
                    <textarea id="atc-editor-textarea" rows="5"></textarea>
                    <div class="atc-editor-actions">
                        <button class="button button-primary" id="atc-save-text-btn">Save</button>
                        <button class="button" id="atc-cancel-edit-btn">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save Text (AJAX)
     */
    public static function save_text() {
        check_ajax_referer('atc_text_editor_nonce', 'nonce');
        
        // Check if function exists before calling
        if (!function_exists('current_user_can') || !current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $element_id = sanitize_text_field($_POST['element_id']);
        $text = wp_kses_post($_POST['text']);
        $post_id = intval($_POST['post_id']);
        
        // Save to post meta
        update_post_meta($post_id, '_atc_custom_text_' . $element_id, $text);
        
        wp_send_json_success(['message' => 'Text saved successfully']);
    }
}

ATC_Text_Editor::init();

