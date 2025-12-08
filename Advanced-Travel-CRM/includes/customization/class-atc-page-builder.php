<?php
/**
 * ATC Drag-and-Drop Page Builder
 * Visual page builder with section reordering
 */

if (!defined('ABSPATH')) exit;

class ATC_Page_Builder {
    
    private static $instance = null;
    
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 20); // Priority 20 to ensure parent menu exists
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_atc_save_page_layout', [$this, 'save_page_layout']);
        add_action('wp_ajax_atc_get_page_layout', [$this, 'get_page_layout']);
        add_action('wp_ajax_atc_reorder_sections', [$this, 'reorder_sections']);
        add_action('add_meta_boxes', [$this, 'add_page_builder_meta_box']);
    }
    
    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
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
                'Page Builder',
                'Page Builder',
                'manage_options',
                'atc-page-builder',
                [$this, 'render_page_builder']
            );
        }
    }
    
    /**
     * Enqueue Admin Assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'atc-page-builder') !== false || $hook === 'post.php' || $hook === 'post-new.php') {
            // Check if constants exist
            $assets_url = defined('ATC_ASSETS_URL') ? ATC_ASSETS_URL : plugin_dir_url(__FILE__) . '../../assets/';
            $version = defined('ATC_VERSION') ? ATC_VERSION : '2.4.0';
            
            wp_enqueue_style('atc-page-builder', $assets_url . 'css/atc-page-builder.css', [], $version);
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('atc-page-builder', $assets_url . 'js/atc-page-builder.js', ['jquery', 'jquery-ui-sortable'], $version, true);
            
            wp_localize_script('atc-page-builder', 'atcPageBuilder', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('atc_page_builder_nonce'),
                'sections' => $this->get_available_sections(),
            ]);
        }
    }
    
    /**
     * Add Meta Box to Pages
     */
    public function add_page_builder_meta_box() {
        $post_types = ['page'];
        if (class_exists('Elementor\Plugin')) {
            $post_types[] = 'elementor_library';
        }
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'atc_page_builder',
                'ATC Page Builder',
                [$this, 'render_meta_box'],
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render Meta Box
     */
    public function render_meta_box($post) {
        $layout = get_post_meta($post->ID, '_atc_page_layout', true);
        $sections = $layout ? json_decode($layout, true) : [];
        
        wp_nonce_field('atc_page_builder_meta', 'atc_page_builder_nonce');
        ?>
        <div class="atc-page-builder-meta">
            <p>
                <label>
                    <input type="checkbox" name="atc_enable_page_builder" value="1" <?php checked(get_post_meta($post->ID, '_atc_enable_page_builder', true), '1'); ?>>
                    Enable ATC Page Builder
                </label>
            </p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=atc-page-builder&post_id=' . $post->ID); ?>" class="button button-primary">
                    Open Page Builder
                </a>
            </p>
            <input type="hidden" name="atc_page_layout" value="<?php echo esc_attr($layout); ?>">
        </div>
        <?php
    }
    
    /**
     * Render Page Builder Interface
     */
    public function render_page_builder() {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        $post = $post_id ? get_post($post_id) : null;
        
        // Get all pages for selector
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        
        if (!$post && $post_id) {
            echo '<div class="wrap"><h1>Page Builder</h1><div class="notice notice-error"><p>Page not found. Please select a valid page.</p></div></div>';
            $post_id = 0;
        }
        
        // Get sections for the selected page
        $sections = [];
        if ($post) {
            $layout = get_post_meta($post_id, '_atc_page_layout', true);
            if ($layout) {
                $decoded = json_decode($layout, true);
                if (is_array($decoded)) {
                    $sections = $decoded;
                }
            }
        }
        
        ?>
        <div class="wrap atc-page-builder-wrap">
            <h1>🎨 Page Builder</h1>
            
            <?php if (!$post): ?>
                <!-- Page Selector -->
                <div class="atc-page-selector">
                    <div class="atc-page-selector-card">
                        <h2>Select a Page to Build</h2>
                        <p class="description">Choose a page from the list below to start building with drag-and-drop sections.</p>
                        
                        <form method="get" action="" style="margin: 20px 0;">
                            <input type="hidden" name="page" value="atc-page-builder">
                            <label for="atc-select-page" style="display: block; margin-bottom: 10px; font-weight: 600;">Select Page:</label>
                            <select name="post_id" id="atc-select-page" class="regular-text" style="width: 100%; max-width: 500px; padding: 10px; font-size: 14px;" onchange="this.form.submit();">
                                <option value="">-- Select a Page --</option>
                                <?php foreach ($pages as $page_item): ?>
                                    <option value="<?php echo esc_attr($page_item->ID); ?>" <?php selected($post_id, $page_item->ID); ?>>
                                        <?php echo esc_html($page_item->post_title); ?> 
                                        (<?php echo esc_html(ucfirst($page_item->post_status)); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        
                        <div class="atc-quick-actions">
                            <h3>Quick Actions</h3>
                            <p>
                                <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="button button-primary" target="_blank">
                                    ➕ Create New Page
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button" target="_blank">
                                    📄 Manage Pages
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Page Builder Interface -->
                <div class="atc-page-builder-header">
                    <div class="atc-page-info">
                        <h2 style="margin: 0;"><?php echo esc_html($post->post_title); ?></h2>
                        <p style="margin: 5px 0 0 0; color: #666;">
                            <a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank">Edit Page</a> | 
                            <a href="<?php echo get_permalink($post_id); ?>" target="_blank">View Page</a> |
                            <a href="<?php echo admin_url('admin.php?page=atc-page-builder'); ?>">Change Page</a>
                        </p>
                    </div>
                </div>
                
                <div class="atc-page-builder-container">
                    <!-- Sidebar -->
                    <div class="atc-page-builder-sidebar">
                        <div class="atc-sidebar-section">
                            <h3>📦 Available Sections</h3>
                            <p class="description">Click "Add" to add a section to your page</p>
                            <div class="atc-sections-list" id="atc-sections-list">
                                <?php foreach ($this->get_available_sections() as $section_key => $section): ?>
                                <div class="atc-section-item" data-section="<?php echo esc_attr($section_key); ?>">
                                    <div class="atc-section-icon"><?php echo esc_html($section['icon']); ?></div>
                                    <div class="atc-section-info">
                                        <strong><?php echo esc_html($section['title']); ?></strong>
                                        <span><?php echo esc_html($section['description']); ?></span>
                                    </div>
                                    <button class="button button-small atc-add-section" data-section="<?php echo esc_attr($section_key); ?>">Add</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="atc-sidebar-section atc-actions-section">
                            <h3>💾 Actions</h3>
                            <div class="atc-page-builder-actions">
                                <button class="button button-primary button-large atc-save-layout" data-post-id="<?php echo esc_attr($post_id); ?>" style="width: 100%; margin-bottom: 10px;">
                                    💾 Save Layout
                                </button>
                                <button class="button button-large atc-preview-layout" data-post-id="<?php echo esc_attr($post_id); ?>" style="width: 100%;">
                                    👁️ Preview Page
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Canvas -->
                    <div class="atc-page-builder-canvas">
                        <div class="atc-canvas-header">
                            <h3>📄 Page Sections</h3>
                            <p class="description">Drag sections to reorder. Click Edit to customize, Remove to delete.</p>
                        </div>
                        
                        <input type="hidden" name="atc_page_layout" id="atc-page-layout" value="<?php echo esc_attr($layout ?? ''); ?>">
                        
                        <div class="atc-sections-container" id="atc-sections-container">
                            <?php if (!empty($sections) && is_array($sections)): ?>
                                <?php foreach ($sections as $index => $section): 
                                    if (!isset($section['type'])) continue;
                                    $section_type = $section['type'];
                                    $available_sections = $this->get_available_sections();
                                    $section_info = $available_sections[$section_type] ?? null;
                                ?>
                                <div class="atc-section-wrapper" data-section="<?php echo esc_attr($section_type); ?>" data-index="<?php echo esc_attr($index); ?>">
                                    <div class="atc-section-header">
                                        <span class="atc-section-handle" title="Drag to reorder">☰</span>
                                        <span class="atc-section-icon-small"><?php echo esc_html($section_info['icon'] ?? '📦'); ?></span>
                                        <span class="atc-section-title"><?php echo esc_html($section_info['title'] ?? ucfirst($section_type)); ?></span>
                                        <div class="atc-section-actions">
                                            <button class="button button-small atc-edit-section" data-section="<?php echo esc_attr($index); ?>" title="Edit Section">⚙️ Edit</button>
                                            <button class="button button-small atc-remove-section" data-section="<?php echo esc_attr($index); ?>" title="Remove Section">🗑️ Remove</button>
                                        </div>
                                    </div>
                                    <div class="atc-section-preview">
                                        <?php echo $this->render_section_preview($section); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="atc-empty-state" id="atc-empty-state" style="<?php echo !empty($sections) ? 'display:none;' : ''; ?>">
                            <div class="atc-empty-icon">📄</div>
                            <h3>No Sections Yet</h3>
                            <p>Start building your page by adding sections from the sidebar.</p>
                            <p class="description">Click "Add" next to any section in the sidebar to add it to your page.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get Available Sections
     */
    private function get_available_sections() {
        return [
            'hero' => [
                'title' => 'Hero Section',
                'description' => 'Main hero with title and search',
                'icon' => '🏠',
                'shortcode' => 'atc_homepage_hero',
            ],
            'services' => [
                'title' => 'Services Grid',
                'description' => 'Display all services',
                'icon' => '📦',
                'shortcode' => 'atc_homepage_services',
            ],
            'featured_tours' => [
                'title' => 'Featured Tours',
                'description' => 'Featured tour packages',
                'icon' => '✈️',
                'shortcode' => 'atc_homepage_featured_packages',
            ],
            'featured_hotels' => [
                'title' => 'Featured Hotels',
                'description' => 'Featured hotel packages',
                'icon' => '🏨',
                'shortcode' => 'atc_homepage_featured_packages',
            ],
            'why_choose_us' => [
                'title' => 'Why Choose Us',
                'description' => 'Features and benefits',
                'icon' => '⭐',
                'shortcode' => 'atc_homepage_why_choose_us',
            ],
            'testimonials' => [
                'title' => 'Testimonials',
                'description' => 'Customer reviews',
                'icon' => '💬',
                'shortcode' => 'atc_homepage_testimonials',
            ],
            'statistics' => [
                'title' => 'Statistics',
                'description' => 'Numbers and stats',
                'icon' => '📊',
                'shortcode' => 'atc_homepage_statistics',
            ],
            'cta' => [
                'title' => 'Call to Action',
                'description' => 'Contact form section',
                'icon' => '📞',
                'shortcode' => 'atc_homepage_cta',
            ],
        ];
    }
    
    /**
     * Get Default Sections
     */
    private function get_default_sections() {
        return [
            ['type' => 'hero', 'settings' => []],
            ['type' => 'services', 'settings' => []],
            ['type' => 'featured_tours', 'settings' => ['service' => 'tours']],
            ['type' => 'why_choose_us', 'settings' => []],
            ['type' => 'testimonials', 'settings' => []],
            ['type' => 'cta', 'settings' => []],
        ];
    }
    
    /**
     * Get Section Title
     */
    private function get_section_title($type) {
        $sections = $this->get_available_sections();
        return $sections[$type]['title'] ?? ucfirst($type);
    }
    
    /**
     * Render Section Preview
     */
    private function render_section_preview($section) {
        $sections = $this->get_available_sections();
        $section_info = $sections[$section['type']] ?? null;
        
        if (!$section_info) {
            return '<p>Unknown section</p>';
        }
        
        $shortcode = $section_info['shortcode'];
        $settings = $section['settings'] ?? [];
        
        $shortcode_string = '[' . $shortcode;
        foreach ($settings as $key => $value) {
            $shortcode_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        $shortcode_string .= ']';
        
        return '<div class="atc-shortcode-preview">' . esc_html($shortcode_string) . '</div>';
    }
    
    /**
     * Save Page Layout (AJAX)
     */
    public function save_page_layout() {
        check_ajax_referer('atc_page_builder_nonce', 'nonce');
        
        // Check if function exists before calling
        if (!function_exists('current_user_can') || !current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $layout = isset($_POST['layout']) ? wp_unslash($_POST['layout']) : '';
        
        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }
        
        // Validate JSON
        $decoded = json_decode($layout, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid layout data']);
        }
        
        update_post_meta($post_id, '_atc_page_layout', $layout);
        update_post_meta($post_id, '_atc_enable_page_builder', '1');
        
        wp_send_json_success(['message' => 'Layout saved successfully']);
    }
    
    /**
     * Get Page Layout (AJAX)
     */
    public function get_page_layout() {
        check_ajax_referer('atc_page_builder_nonce', 'nonce');
        
        $post_id = intval($_GET['post_id']);
        $layout = get_post_meta($post_id, '_atc_page_layout', true);
        
        wp_send_json_success(['layout' => $layout]);
    }
    
    /**
     * Reorder Sections (AJAX)
     */
    public function reorder_sections() {
        check_ajax_referer('atc_page_builder_nonce', 'nonce');
        
        // Check if function exists before calling
        if (!function_exists('current_user_can') || !current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $post_id = intval($_POST['post_id']);
        $order = json_decode(stripslashes($_POST['order']), true);
        
        $layout = get_post_meta($post_id, '_atc_page_layout', true);
        $sections = $layout ? json_decode($layout, true) : [];
        
        $reordered = [];
        foreach ($order as $index) {
            if (isset($sections[$index])) {
                $reordered[] = $sections[$index];
            }
        }
        
        update_post_meta($post_id, '_atc_page_layout', json_encode($reordered));
        
        wp_send_json_success(['message' => 'Sections reordered']);
    }
}

ATC_Page_Builder::init();

