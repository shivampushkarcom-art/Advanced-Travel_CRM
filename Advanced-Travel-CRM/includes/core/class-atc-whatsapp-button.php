<?php
/**
 * ATC Floating WhatsApp Contact Button
 * Appears after a few seconds as a popup icon
 * Opens WhatsApp with admin mobile number
 */

if (!defined('ABSPATH')) exit;

class ATC_WhatsApp_Button {
    
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [__CLASS__, 'render_button']);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 20); // Priority 20 to ensure parent menu exists
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    public static function admin_menu() {
        // Verify parent menu exists before adding submenu
        global $submenu;
        $parent_menu_exists = isset($submenu['atc-dashboard']) || menu_page_url('atc-dashboard', false);
        
        if (!$parent_menu_exists) {
            // Parent menu doesn't exist yet, try to initialize it
            if (class_exists('ATC_Admin') && method_exists('ATC_Admin', 'init')) {
                ATC_Admin::init();
            }
            // Re-check after initialization
            $parent_menu_exists = isset($submenu['atc-dashboard']) || menu_page_url('atc-dashboard', false);
        }
        
        // Only add submenu if parent exists
        if ($parent_menu_exists) {
            add_submenu_page(
                'atc-dashboard',
                __('WhatsApp Button', 'advanced-travel-crm'),
                __('WhatsApp Button', 'advanced-travel-crm'),
                'manage_options',
                'atc-whatsapp-button',
                [__CLASS__, 'settings_page']
            );
        }
    }
    
    public static function register_settings() {
        register_setting('atc_whatsapp_settings', 'atc_whatsapp_enabled');
        register_setting('atc_whatsapp_settings', 'atc_whatsapp_number');
        register_setting('atc_whatsapp_settings', 'atc_whatsapp_message');
        register_setting('atc_whatsapp_settings', 'atc_whatsapp_delay');
        register_setting('atc_whatsapp_settings', 'atc_whatsapp_position');
        register_setting('atc_whatsapp_settings', 'atc_whatsapp_mobile_enabled');
    }
    
    public static function enqueue_assets() {
        if (is_admin()) {
            return;
        }
        
        if (!get_option('atc_whatsapp_enabled', 1)) {
            return;
        }
        
        if (!defined('ATC_ASSETS_URL') || !defined('ATC_VERSION')) {
            return;
        }
        
        wp_enqueue_style('atc-whatsapp-button', ATC_ASSETS_URL . 'css/atc-whatsapp-button.css', [], ATC_VERSION);
        wp_enqueue_script('atc-whatsapp-button', ATC_ASSETS_URL . 'js/atc-whatsapp-button.js', ['jquery'], ATC_VERSION, true);
        
        wp_localize_script('atc-whatsapp-button', 'atcWhatsApp', [
            'number' => get_option('atc_whatsapp_number', ''),
            'message' => get_option('atc_whatsapp_message', 'Hello! I would like to know more about your travel packages.'),
            'delay' => absint(get_option('atc_whatsapp_delay', 3000)), // 3 seconds default
            'position' => get_option('atc_whatsapp_position', 'bottom-right'),
            'mobileEnabled' => get_option('atc_whatsapp_mobile_enabled', 1),
        ]);
    }
    
    public static function render_button() {
        if (is_admin() || !get_option('atc_whatsapp_enabled', 1)) {
            return;
        }
        
        $number = get_option('atc_whatsapp_number', '');
        if (empty($number)) {
            return;
        }
        
        $position = get_option('atc_whatsapp_position', 'bottom-right');
        ?>
        <div id="atc-whatsapp-button" class="atc-whatsapp-button atc-whatsapp-<?php echo esc_attr($position); ?>" style="display: none;">
            <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $number)); ?>?text=<?php echo urlencode(get_option('atc_whatsapp_message', 'Hello! I would like to know more about your travel packages.')); ?>" 
               target="_blank" 
               rel="noopener noreferrer"
               class="atc-whatsapp-link"
               aria-label="<?php esc_attr_e('Contact us on WhatsApp', 'advanced-travel-crm'); ?>">
                <div class="atc-whatsapp-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                </div>
                <div class="atc-whatsapp-pulse"></div>
            </a>
        </div>
        <?php
    }
    
    public static function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('atc_whatsapp_settings');
            self::register_settings();
            
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'atc_whatsapp_') === 0) {
                    update_option($key, sanitize_text_field($value));
                }
            }
            
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $enabled = get_option('atc_whatsapp_enabled', 1);
        $number = get_option('atc_whatsapp_number', '');
        $message = get_option('atc_whatsapp_message', 'Hello! I would like to know more about your travel packages.');
        $delay = get_option('atc_whatsapp_delay', 3000);
        $position = get_option('atc_whatsapp_position', 'bottom-right');
        $mobile_enabled = get_option('atc_whatsapp_mobile_enabled', 1);
        
        ?>
        <div class="wrap">
            <h1>💬 WhatsApp Contact Button Settings</h1>
            <p class="description">Configure the floating WhatsApp button that appears on your website. Customers can click it to contact you directly on WhatsApp.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('atc_whatsapp_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="atc_whatsapp_enabled">Enable WhatsApp Button</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="atc_whatsapp_enabled" value="1" <?php checked($enabled, 1); ?>>
                                Show floating WhatsApp button on website
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="atc_whatsapp_number">WhatsApp Number *</label></th>
                        <td>
                            <input type="text" 
                                   name="atc_whatsapp_number" 
                                   id="atc_whatsapp_number" 
                                   value="<?php echo esc_attr($number); ?>" 
                                   class="regular-text" 
                                   placeholder="+1234567890"
                                   required>
                            <p class="description">Enter your WhatsApp number with country code (e.g., +1234567890). Only numbers and + sign allowed.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="atc_whatsapp_message">Default Message</label></th>
                        <td>
                            <textarea name="atc_whatsapp_message" 
                                      id="atc_whatsapp_message" 
                                      rows="3" 
                                      class="large-text"><?php echo esc_textarea($message); ?></textarea>
                            <p class="description">Pre-filled message when customer clicks the button</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="atc_whatsapp_delay">Appearance Delay (ms)</label></th>
                        <td>
                            <input type="number" 
                                   name="atc_whatsapp_delay" 
                                   id="atc_whatsapp_delay" 
                                   value="<?php echo esc_attr($delay); ?>" 
                                   class="small-text" 
                                   min="0" 
                                   max="30000"
                                   step="500">
                            <p class="description">How long to wait before showing the button (in milliseconds). Default: 3000ms (3 seconds).</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="atc_whatsapp_position">Button Position</label></th>
                        <td>
                            <select name="atc_whatsapp_position" id="atc_whatsapp_position" class="regular-text">
                                <option value="bottom-right" <?php selected($position, 'bottom-right'); ?>>Bottom Right</option>
                                <option value="bottom-left" <?php selected($position, 'bottom-left'); ?>>Bottom Left</option>
                                <option value="top-right" <?php selected($position, 'top-right'); ?>>Top Right</option>
                                <option value="top-left" <?php selected($position, 'top-left'); ?>>Top Left</option>
                            </select>
                            <p class="description">Where the button appears on the screen</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="atc_whatsapp_mobile_enabled">Mobile Enabled</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="atc_whatsapp_mobile_enabled" value="1" <?php checked($mobile_enabled, 1); ?>>
                                Show button on mobile devices
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="submit" class="button button-primary button-large">Save Settings</button>
                </p>
            </form>
        </div>
        <?php
    }
}

ATC_WhatsApp_Button::init();

