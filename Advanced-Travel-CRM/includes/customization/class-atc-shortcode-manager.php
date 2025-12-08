<?php
/**
 * ATC Shortcode Manager
 * Visual shortcode picker and parameter editor
 */

if (!defined('ABSPATH')) exit;

class ATC_Shortcode_Manager {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu'], 20); // Priority 20 to ensure parent menu exists
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('media_buttons', [__CLASS__, 'add_shortcode_button'], 15);
        add_action('admin_footer', [__CLASS__, 'render_shortcode_modal']);
        add_action('wp_ajax_atc_preview_shortcode', [__CLASS__, 'preview_shortcode']);
    }
    
    /**
     * Add Admin Menu
     */
    public static function add_admin_menu() {
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
                'Shortcode Manager',
                'Shortcode Manager',
                'manage_options',
                'atc-shortcode-manager',
                [__CLASS__, 'render_shortcode_manager']
            );
        }
    }
    
    /**
     * Enqueue Admin Assets
     */
    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'atc-shortcode-manager') !== false || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_style('atc-shortcode-manager', ATC_ASSETS_URL . 'css/atc-shortcode-manager.css', [], ATC_VERSION);
            wp_enqueue_script('atc-shortcode-manager', ATC_ASSETS_URL . 'js/atc-shortcode-manager.js', ['jquery'], ATC_VERSION, true);
            
            wp_localize_script('atc-shortcode-manager', 'atcShortcodeManager', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('atc_shortcode_manager_nonce'),
                'shortcodes' => self::get_shortcodes_list(),
            ]);
        }
    }
    
    /**
     * Add Shortcode Button to Editor
     */
    public static function add_shortcode_button($editor_id = 'content') {
        echo '<button type="button" class="button atc-insert-shortcode" data-editor="' . esc_attr($editor_id) . '">';
        echo '<span class="dashicons dashicons-shortcode" style="margin-top: 3px;"></span> ';
        echo 'ATC Shortcodes';
        echo '</button>';
    }
    
    /**
     * Render Shortcode Modal
     */
    public static function render_shortcode_modal() {
        ?>
        <div id="atc-shortcode-modal" class="atc-modal" style="display:none;">
            <div class="atc-modal-content">
                <span class="atc-modal-close">&times;</span>
                <h2>Insert ATC Shortcode</h2>
                
                <div class="atc-shortcode-search">
                    <input type="text" id="atc-shortcode-search" placeholder="Search shortcodes...">
                </div>
                
                <div class="atc-shortcode-categories">
                    <?php foreach (self::get_shortcode_categories() as $category => $shortcodes): ?>
                    <div class="atc-shortcode-category">
                        <h3><?php echo esc_html($category); ?></h3>
                        <div class="atc-shortcode-list">
                            <?php foreach ($shortcodes as $shortcode_key => $shortcode): ?>
                            <div class="atc-shortcode-item" data-shortcode="<?php echo esc_attr($shortcode_key); ?>">
                                <strong><?php echo esc_html($shortcode['title']); ?></strong>
                                <span><?php echo esc_html($shortcode['description']); ?></span>
                                <code>[<?php echo esc_html($shortcode_key); ?>]</code>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="atc-shortcode-params" id="atc-shortcode-params" style="display:none;">
                    <h3>Parameters</h3>
                    <div id="atc-params-container"></div>
                    <div class="atc-shortcode-actions">
                        <button class="button button-primary" id="atc-insert-shortcode-btn">Insert Shortcode</button>
                        <button class="button" id="atc-preview-shortcode-btn">Preview</button>
                    </div>
                    <div id="atc-shortcode-preview"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Shortcode Manager Page
     */
    public static function render_shortcode_manager() {
        ?>
        <div class="wrap">
            <h1>ATC Shortcode Manager</h1>
            
            <div class="atc-shortcode-manager">
                <div class="atc-shortcode-search-bar">
                    <input type="text" id="atc-manager-search" placeholder="Search shortcodes..." class="regular-text">
                </div>
                
                <div class="atc-shortcode-library">
                    <?php foreach (self::get_shortcode_categories() as $category => $shortcodes): ?>
                    <div class="atc-shortcode-category-card">
                        <h2><?php echo esc_html($category); ?></h2>
                        <div class="atc-shortcode-grid">
                            <?php foreach ($shortcodes as $shortcode_key => $shortcode): ?>
                            <div class="atc-shortcode-card" data-shortcode="<?php echo esc_attr($shortcode_key); ?>">
                                <div class="atc-shortcode-icon"><?php echo esc_html($shortcode['icon'] ?? '📦'); ?></div>
                                <h3><?php echo esc_html($shortcode['title']); ?></h3>
                                <p><?php echo esc_html($shortcode['description']); ?></p>
                                <code>[<?php echo esc_html($shortcode_key); ?>]</code>
                                <div class="atc-shortcode-actions">
                                    <button class="button button-small atc-copy-shortcode" data-shortcode="<?php echo esc_attr($shortcode_key); ?>">
                                        Copy
                                    </button>
                                    <button class="button button-small atc-edit-shortcode" data-shortcode="<?php echo esc_attr($shortcode_key); ?>">
                                        Edit
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get Shortcode Categories
     */
    private static function get_shortcode_categories() {
        return [
            'Homepage Sections' => [
                'atc_homepage_hero' => [
                    'title' => 'Homepage Hero',
                    'description' => 'Hero section with title and search',
                    'icon' => '🏠',
                    'params' => [
                        'title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Your Premium Travel Partner'],
                        'subtitle' => ['type' => 'text', 'label' => 'Subtitle', 'default' => 'Experience Luxury Travel'],
                        'show_search' => ['type' => 'checkbox', 'label' => 'Show Search', 'default' => 'true'],
                    ],
                ],
                'atc_homepage_services' => [
                    'title' => 'Services Grid',
                    'description' => 'Display all services',
                    'icon' => '📦',
                    'params' => [
                        'title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Our Services'],
                        'columns' => ['type' => 'number', 'label' => 'Columns', 'default' => '3'],
                    ],
                ],
                'atc_homepage_featured_packages' => [
                    'title' => 'Featured Packages',
                    'description' => 'Featured packages by service',
                    'icon' => '⭐',
                    'params' => [
                        'service' => ['type' => 'select', 'label' => 'Service', 'options' => ['tours', 'hotels', 'flights'], 'default' => 'tours'],
                        'title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Featured Packages'],
                        'columns' => ['type' => 'number', 'label' => 'Columns', 'default' => '4'],
                        'per_page' => ['type' => 'number', 'label' => 'Per Page', 'default' => '8'],
                    ],
                ],
                'atc_homepage_why_choose_us' => [
                    'title' => 'Why Choose Us',
                    'description' => 'Features and benefits section',
                    'icon' => '⭐',
                ],
                'atc_homepage_testimonials' => [
                    'title' => 'Testimonials',
                    'description' => 'Customer reviews',
                    'icon' => '💬',
                ],
                'atc_homepage_statistics' => [
                    'title' => 'Statistics',
                    'description' => 'Numbers and stats counter',
                    'icon' => '📊',
                ],
                'atc_homepage_cta' => [
                    'title' => 'Call to Action',
                    'description' => 'Contact form section',
                    'icon' => '📞',
                ],
            ],
            'Service Landing Pages' => [
                'atc_tours_landing' => [
                    'title' => 'Tours Landing',
                    'description' => 'Tours service landing page',
                    'icon' => '✈️',
                ],
                'atc_hotels_landing' => [
                    'title' => 'Hotels Landing',
                    'description' => 'Hotels service landing page',
                    'icon' => '🏨',
                ],
                'atc_flights_landing' => [
                    'title' => 'Flights Landing',
                    'description' => 'Flights service landing page',
                    'icon' => '✈️',
                ],
                'atc_trains_landing' => [
                    'title' => 'Trains Landing',
                    'description' => 'Trains service landing page',
                    'icon' => '🚂',
                ],
                'atc_cars_landing' => [
                    'title' => 'Cars Landing',
                    'description' => 'Car rentals landing page',
                    'icon' => '🚗',
                ],
                'atc_forex_landing' => [
                    'title' => 'Forex Landing',
                    'description' => 'Forex services landing page',
                    'icon' => '💱',
                ],
                'atc_visa_landing' => [
                    'title' => 'Visa Landing',
                    'description' => 'Visa services landing page',
                    'icon' => '📋',
                ],
                'atc_celebrity_landing' => [
                    'title' => 'Celebrity Landing',
                    'description' => 'Celebrity management landing page',
                    'icon' => '⭐',
                ],
            ],
            'Search & Booking' => [
                'atc_premium_search' => [
                    'title' => 'Premium Search',
                    'description' => 'Service search form',
                    'icon' => '🔍',
                ],
                'atc_service_grid' => [
                    'title' => 'Service Grid',
                    'description' => 'Display services grid',
                    'icon' => '📦',
                ],
            ],
        ];
    }
    
    /**
     * Get Shortcodes List
     */
    private static function get_shortcodes_list() {
        $list = [];
        foreach (self::get_shortcode_categories() as $category => $shortcodes) {
            foreach ($shortcodes as $key => $shortcode) {
                $list[$key] = $shortcode;
            }
        }
        return $list;
    }
    
    /**
     * Preview Shortcode (AJAX)
     */
    public static function preview_shortcode() {
        check_ajax_referer('atc_shortcode_manager_nonce', 'nonce');
        
        $shortcode = sanitize_text_field($_POST['shortcode']);
        $params = isset($_POST['params']) ? $_POST['params'] : [];
        
        $shortcode_string = '[' . $shortcode;
        foreach ($params as $key => $value) {
            $shortcode_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        $shortcode_string .= ']';
        
        $preview = do_shortcode($shortcode_string);
        
        wp_send_json_success(['preview' => $preview, 'shortcode' => $shortcode_string]);
    }
}

ATC_Shortcode_Manager::init();

