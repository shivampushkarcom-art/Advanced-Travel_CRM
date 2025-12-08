<?php
/**
 * ATC Celebrity Landing Page Customizer
 * Admin interface for customizing celebrity management landing page content
 */

if (!defined('ABSPATH')) exit;

class ATC_Celebrity_Customizer {
    
    const OPTION_NAME = 'atc_celebrity_landing_content';
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_atc_save_celebrity_content', [__CLASS__, 'save_content_handler']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Celebrity Landing Customizer', 'advanced-travel-crm'),
            __('Celebrity Customizer', 'advanced-travel-crm'),
            'manage_options',
            'atc-celebrity-customizer',
            [__CLASS__, 'customizer_page']
        );
    }
    
    public static function enqueue_assets($hook) {
        if ($hook !== 'travel-crm_page_atc-celebrity-customizer') {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_style('atc-celebrity-customizer', ATC_ASSETS_URL . 'css/atc-celebrity-customizer.css', [], ATC_VERSION);
    }
    
    public static function get_default_content() {
        return [
            'hero' => [
                'title' => '⭐ Celebrity Management Services',
                'subtitle' => 'Professional celebrity management for events, endorsements, and brand partnerships',
                'primary_button_text' => 'Get Started',
                'secondary_button_text' => 'Our Services',
                'background_image' => '',
            ],
            'services' => [
                'title' => 'What We Offer',
                'items' => [
                    [
                        'icon' => '🎭',
                        'title' => 'Event Management',
                        'description' => 'Complete celebrity management for corporate events, weddings, product launches, and more.',
                        'image' => '',
                    ],
                    [
                        'icon' => '📸',
                        'title' => 'Brand Endorsements',
                        'description' => 'Connect your brand with the right celebrity for authentic endorsements and partnerships.',
                        'image' => '',
                    ],
                    [
                        'icon' => '🎤',
                        'title' => 'Concert & Shows',
                        'description' => 'Professional management for concerts, shows, and entertainment events.',
                        'image' => '',
                    ],
                    [
                        'icon' => '🤝',
                        'title' => 'Partnerships',
                        'description' => 'Strategic celebrity partnerships for long-term brand associations.',
                        'image' => '',
                    ],
                ],
            ],
            'why_choose_us' => [
                'title' => 'Why Choose Us',
                'features' => [
                    [
                        'icon' => '✨',
                        'title' => 'Experienced Team',
                        'description' => 'Years of experience in celebrity management and event coordination.',
                        'image' => '',
                    ],
                    [
                        'icon' => '🌟',
                        'title' => 'Wide Network',
                        'description' => 'Extensive network of celebrities across various industries and genres.',
                        'image' => '',
                    ],
                    [
                        'icon' => '💼',
                        'title' => 'Professional Service',
                        'description' => 'End-to-end management from planning to execution.',
                        'image' => '',
                    ],
                    [
                        'icon' => '🎯',
                        'title' => 'Custom Solutions',
                        'description' => 'Tailored solutions to meet your specific requirements and budget.',
                        'image' => '',
                    ],
                ],
            ],
            'contact' => [
                'title' => 'Get In Touch',
                'subtitle' => 'Ready to work with us? Contact us today to discuss your celebrity management needs.',
            ],
        ];
    }
    
    public static function get_content() {
        $content = get_option(self::OPTION_NAME, []);
        $defaults = self::get_default_content();
        
        // Merge with defaults to ensure all fields exist
        return wp_parse_args($content, $defaults);
    }
    
    public static function customizer_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        $content = self::get_content();
        $notice_code = isset($_GET['atc_notice']) ? sanitize_text_field($_GET['atc_notice']) : '';
        
        ?>
        <div class="wrap atc-celebrity-customizer-wrap">
            <h1>🎨 Celebrity Landing Page Customizer</h1>
            <p class="description">Customize the content, text, and images for your Celebrity Management landing page.</p>
            
            <?php if ($notice_code === 'saved'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Content saved successfully!', 'advanced-travel-crm'); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="atc-celebrity-customizer-form">
                <?php wp_nonce_field('atc_save_celebrity_content'); ?>
                <input type="hidden" name="action" value="atc_save_celebrity_content">
                
                <div class="atc-customizer-tabs">
                    <button type="button" class="atc-tab-btn active" data-tab="hero">Hero Section</button>
                    <button type="button" class="atc-tab-btn" data-tab="services">Services</button>
                    <button type="button" class="atc-tab-btn" data-tab="why_choose_us">Why Choose Us</button>
                    <button type="button" class="atc-tab-btn" data-tab="contact">Contact</button>
                </div>
                
                <!-- Hero Section -->
                <div class="atc-tab-content active" id="atc-tab-hero">
                    <h2>Hero Section</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="hero_title">Hero Title</label></th>
                            <td>
                                <input type="text" id="hero_title" name="content[hero][title]" value="<?php echo esc_attr($content['hero']['title']); ?>" class="regular-text" />
                                <p class="description">Main heading displayed in the hero section</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="hero_subtitle">Hero Subtitle</label></th>
                            <td>
                                <textarea id="hero_subtitle" name="content[hero][subtitle]" rows="3" class="large-text"><?php echo esc_textarea($content['hero']['subtitle']); ?></textarea>
                                <p class="description">Subtitle text below the main heading</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="hero_primary_button">Primary Button Text</label></th>
                            <td>
                                <input type="text" id="hero_primary_button" name="content[hero][primary_button_text]" value="<?php echo esc_attr($content['hero']['primary_button_text']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="hero_secondary_button">Secondary Button Text</label></th>
                            <td>
                                <input type="text" id="hero_secondary_button" name="content[hero][secondary_button_text]" value="<?php echo esc_attr($content['hero']['secondary_button_text']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="hero_background_image">Hero Background Image (Optional)</label></th>
                            <td>
                                <input type="url" id="hero_background_image" name="content[hero][background_image]" value="<?php echo esc_url($content['hero']['background_image']); ?>" class="regular-text" />
                                <button type="button" class="button atc-upload-image-btn" data-target="hero_background_image">Upload Image</button>
                                <p class="description">Optional background image for hero section</p>
                                <?php if (!empty($content['hero']['background_image'])): ?>
                                    <div class="atc-image-preview" style="margin-top: 10px;">
                                        <img src="<?php echo esc_url($content['hero']['background_image']); ?>" alt="Preview" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Services Section -->
                <div class="atc-tab-content" id="atc-tab-services">
                    <h2>Services Section</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="services_title">Section Title</label></th>
                            <td>
                                <input type="text" id="services_title" name="content[services][title]" value="<?php echo esc_attr($content['services']['title']); ?>" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Service Items</h3>
                    <div id="atc-services-items">
                        <?php foreach ($content['services']['items'] as $index => $item): ?>
                            <div class="atc-service-item-editor" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f9f9f9;">
                                <h4>Service Item <?php echo $index + 1; ?></h4>
                                <table class="form-table">
                                    <tr>
                                        <th><label>Icon / Emoji</label></th>
                                        <td>
                                            <input type="text" name="content[services][items][<?php echo $index; ?>][icon]" value="<?php echo esc_attr($item['icon']); ?>" maxlength="4" class="small-text" />
                                            <p class="description">Emoji icon (max 4 characters)</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Title</label></th>
                                        <td>
                                            <input type="text" name="content[services][items][<?php echo $index; ?>][title]" value="<?php echo esc_attr($item['title']); ?>" class="regular-text" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Description</label></th>
                                        <td>
                                            <textarea name="content[services][items][<?php echo $index; ?>][description]" rows="3" class="large-text"><?php echo esc_textarea($item['description']); ?></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Image (Optional)</label></th>
                                        <td>
                                            <input type="url" name="content[services][items][<?php echo $index; ?>][image]" value="<?php echo esc_url($item['image']); ?>" class="regular-text" />
                                            <button type="button" class="button atc-upload-image-btn" data-target="service_item_<?php echo $index; ?>_image">Upload Image</button>
                                            <?php if (!empty($item['image'])): ?>
                                                <div class="atc-image-preview" style="margin-top: 10px;">
                                                    <img src="<?php echo esc_url($item['image']); ?>" alt="Preview" style="max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Why Choose Us Section -->
                <div class="atc-tab-content" id="atc-tab-why_choose_us">
                    <h2>Why Choose Us Section</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="why_title">Section Title</label></th>
                            <td>
                                <input type="text" id="why_title" name="content[why_choose_us][title]" value="<?php echo esc_attr($content['why_choose_us']['title']); ?>" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Features</h3>
                    <div id="atc-features-items">
                        <?php foreach ($content['why_choose_us']['features'] as $index => $feature): ?>
                            <div class="atc-feature-item-editor" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f9f9f9;">
                                <h4>Feature <?php echo $index + 1; ?></h4>
                                <table class="form-table">
                                    <tr>
                                        <th><label>Icon / Emoji</label></th>
                                        <td>
                                            <input type="text" name="content[why_choose_us][features][<?php echo $index; ?>][icon]" value="<?php echo esc_attr($feature['icon']); ?>" maxlength="4" class="small-text" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Title</label></th>
                                        <td>
                                            <input type="text" name="content[why_choose_us][features][<?php echo $index; ?>][title]" value="<?php echo esc_attr($feature['title']); ?>" class="regular-text" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Description</label></th>
                                        <td>
                                            <textarea name="content[why_choose_us][features][<?php echo $index; ?>][description]" rows="3" class="large-text"><?php echo esc_textarea($feature['description']); ?></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Image (Optional)</label></th>
                                        <td>
                                            <input type="url" name="content[why_choose_us][features][<?php echo $index; ?>][image]" value="<?php echo esc_url($feature['image']); ?>" class="regular-text" />
                                            <button type="button" class="button atc-upload-image-btn" data-target="feature_<?php echo $index; ?>_image">Upload Image</button>
                                            <?php if (!empty($feature['image'])): ?>
                                                <div class="atc-image-preview" style="margin-top: 10px;">
                                                    <img src="<?php echo esc_url($feature['image']); ?>" alt="Preview" style="max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Contact Section -->
                <div class="atc-tab-content" id="atc-tab-contact">
                    <h2>Contact Section</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="contact_title">Section Title</label></th>
                            <td>
                                <input type="text" id="contact_title" name="content[contact][title]" value="<?php echo esc_attr($content['contact']['title']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="contact_subtitle">Section Subtitle</label></th>
                            <td>
                                <textarea id="contact_subtitle" name="content[contact][subtitle]" rows="3" class="large-text"><?php echo esc_textarea($content['contact']['subtitle']); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">Save All Changes</button>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.atc-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                $('.atc-tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.atc-tab-content').removeClass('active');
                $('#atc-tab-' + tab).addClass('active');
            });
            
            // Image upload
            $('.atc-upload-image-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var targetId = button.data('target');
                var targetInput = $('input[name*="' + targetId + '"], input[id="' + targetId + '"]');
                
                if (targetInput.length === 0) {
                    // Try to find by name pattern
                    if (targetId.includes('service_item_')) {
                        var index = targetId.match(/\d+/)[0];
                        targetInput = $('input[name="content[services][items][' + index + '][image]"]');
                    } else if (targetId.includes('feature_')) {
                        var index = targetId.match(/\d+/)[0];
                        targetInput = $('input[name="content[why_choose_us][features][' + index + '][image]"]');
                    } else if (targetId === 'hero_background_image') {
                        targetInput = $('#hero_background_image');
                    }
                }
                
                var frame = wp.media({
                    title: 'Select Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    targetInput.val(attachment.url);
                    
                    // Show preview
                    var preview = targetInput.closest('td').find('.atc-image-preview');
                    if (preview.length === 0) {
                        preview = $('<div class="atc-image-preview" style="margin-top: 10px;"></div>');
                        targetInput.after(preview);
                    }
                    preview.html('<img src="' + attachment.url + '" alt="Preview" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />');
                });
                
                frame.open();
            });
        });
        </script>
        <?php
    }
    
    public static function save_content_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        check_admin_referer('atc_save_celebrity_content');
        
        if (isset($_POST['content']) && is_array($_POST['content'])) {
            $content = self::sanitize_content(wp_unslash($_POST['content']));
            update_option(self::OPTION_NAME, $content);
        }
        
        wp_redirect(add_query_arg('atc_notice', 'saved', admin_url('admin.php?page=atc-celebrity-customizer')));
        exit;
    }
    
    private static function sanitize_content($content) {
        $sanitized = [];
        
        // Hero section
        if (isset($content['hero'])) {
            $sanitized['hero'] = [
                'title' => sanitize_text_field($content['hero']['title'] ?? ''),
                'subtitle' => sanitize_textarea_field($content['hero']['subtitle'] ?? ''),
                'primary_button_text' => sanitize_text_field($content['hero']['primary_button_text'] ?? ''),
                'secondary_button_text' => sanitize_text_field($content['hero']['secondary_button_text'] ?? ''),
                'background_image' => esc_url_raw($content['hero']['background_image'] ?? ''),
            ];
        }
        
        // Services section
        if (isset($content['services'])) {
            $sanitized['services'] = [
                'title' => sanitize_text_field($content['services']['title'] ?? ''),
                'items' => [],
            ];
            
            if (isset($content['services']['items']) && is_array($content['services']['items'])) {
                foreach ($content['services']['items'] as $item) {
                    $sanitized['services']['items'][] = [
                        'icon' => sanitize_text_field($item['icon'] ?? ''),
                        'title' => sanitize_text_field($item['title'] ?? ''),
                        'description' => sanitize_textarea_field($item['description'] ?? ''),
                        'image' => esc_url_raw($item['image'] ?? ''),
                    ];
                }
            }
        }
        
        // Why Choose Us section
        if (isset($content['why_choose_us'])) {
            $sanitized['why_choose_us'] = [
                'title' => sanitize_text_field($content['why_choose_us']['title'] ?? ''),
                'features' => [],
            ];
            
            if (isset($content['why_choose_us']['features']) && is_array($content['why_choose_us']['features'])) {
                foreach ($content['why_choose_us']['features'] as $feature) {
                    $sanitized['why_choose_us']['features'][] = [
                        'icon' => sanitize_text_field($feature['icon'] ?? ''),
                        'title' => sanitize_text_field($feature['title'] ?? ''),
                        'description' => sanitize_textarea_field($feature['description'] ?? ''),
                        'image' => esc_url_raw($feature['image'] ?? ''),
                    ];
                }
            }
        }
        
        // Contact section
        if (isset($content['contact'])) {
            $sanitized['contact'] = [
                'title' => sanitize_text_field($content['contact']['title'] ?? ''),
                'subtitle' => sanitize_textarea_field($content['contact']['subtitle'] ?? ''),
            ];
        }
        
        return $sanitized;
    }
}

