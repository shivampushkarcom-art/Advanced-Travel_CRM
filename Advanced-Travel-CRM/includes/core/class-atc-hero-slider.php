<?php
/**
 * ATC Hero Slider Widget
 * Flipkart-style hero banner with multiple slides, dot navigation, and customizable content
 */

if (!defined('ABSPATH')) exit;

class ATC_Hero_Slider {
    
    const OPTION_NAME = 'atc_hero_sliders'; // Changed to plural for multiple sliders
    
    public static function init() {
        add_shortcode('atc_hero_slider', [__CLASS__, 'hero_slider_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_post_atc_save_hero_slider', [__CLASS__, 'save_slider_handler']);
        add_action('admin_post_atc_delete_hero_slider', [__CLASS__, 'delete_slider_handler']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
    }
    
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Hero Slider', 'advanced-travel-crm'),
            __('Hero Slider', 'advanced-travel-crm'),
            'manage_options',
            'atc-hero-slider',
            [__CLASS__, 'admin_page']
        );
    }
    
    public static function enqueue_assets() {
        wp_enqueue_style('atc-hero-slider', ATC_ASSETS_URL . 'css/atc-hero-slider.css', [], ATC_VERSION);
        wp_enqueue_script('atc-hero-slider', ATC_ASSETS_URL . 'js/atc-hero-slider.js', ['jquery'], ATC_VERSION, true);
    }
    
    public static function admin_assets($hook) {
        // More robust detection of our admin screen
        $is_hero_screen = (strpos($hook, 'atc-hero-slider') !== false);
        if (!$is_hero_screen) {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if ($screen && isset($screen->id)) {
                $is_hero_screen = (strpos($screen->id, 'atc-hero-slider') !== false);
            }
        }
        if (!$is_hero_screen) {
            return;
        }
        
        // Enqueue WordPress media uploader (required for gallery/upload buttons)
        wp_enqueue_media();
        
        // Enqueue jQuery (WordPress admin includes it by default, but ensure it's loaded)
        wp_enqueue_script('jquery');
        
        // Enqueue admin styles
        wp_enqueue_style('atc-hero-slider-admin', ATC_ASSETS_URL . 'css/atc-hero-slider-admin.css', [], ATC_VERSION);
    }
    
    public static function get_all_sliders() {
        $sliders = get_option(self::OPTION_NAME, []);
        if (!is_array($sliders)) {
            $sliders = [];
        }
        return $sliders;
    }
    
    public static function get_slider($slider_id) {
        $sliders = self::get_all_sliders();
        return isset($sliders[$slider_id]) ? $sliders[$slider_id] : null;
    }
    
    public static function get_slides($slider_id = 'default') {
        $slider = self::get_slider($slider_id);
        if (!$slider || !isset($slider['slides'])) {
            return [];
        }
        
        $slides = $slider['slides'];
        if (!is_array($slides)) {
            $slides = [];
        }
        
        // Filter out empty slides and ensure they have required fields (image or video)
        $slides = array_filter($slides, function($slide) {
            $has_image = !empty($slide['image']);
            $has_video = !empty($slide['media_type']) && $slide['media_type'] === 'video' && 
                        (!empty($slide['video_url']) || !empty($slide['youtube_id']) || !empty($slide['vimeo_id']));
            return $has_image || $has_video;
        });
        return array_values($slides); // Re-index array
    }
    
    public static function hero_slider_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 'default',
            'autoplay' => 'true',
            'interval' => '5000',
            'height' => '', // Empty by default to use aspect-ratio
            'show_dots' => 'true',
            'show_arrows' => 'true',
        ], $atts);
        
        $slider_id_param = sanitize_key($atts['id']);
        $slides = self::get_slides($slider_id_param);
        
        if (empty($slides)) {
            return '<p class="atc-hero-slider-empty">No slides configured for this slider. Please add slides from <a href="' . admin_url('admin.php?page=atc-hero-slider&slider=' . $slider_id_param) . '">Hero Slider settings</a>.</p>';
        }
        
        $slider_id = 'atc-hero-slider-' . $slider_id_param . '-' . uniqid();
        $autoplay = $atts['autoplay'] === 'true' ? 'true' : 'false';
        $interval = absint($atts['interval']);
        $height = !empty($atts['height']) ? absint($atts['height']) : '';
        $show_dots = $atts['show_dots'] === 'true';
        $show_arrows = $atts['show_arrows'] === 'true';
        
        // Build style attribute - only add height if specified
        $style_attr = '';
        if (!empty($height)) {
            $style_attr = ' style="--slider-height: ' . esc_attr($height) . 'px;"';
        }
        
        ob_start();
        ?>
        <div class="atc-hero-slider-wrapper" id="<?php echo esc_attr($slider_id); ?>" 
             data-autoplay="<?php echo esc_attr($autoplay); ?>" 
             data-interval="<?php echo esc_attr($interval); ?>"<?php echo $style_attr; ?>>
            <div class="atc-hero-slider-container">
                <?php foreach ($slides as $index => $slide): 
                    $media_type = isset($slide['media_type']) ? $slide['media_type'] : 'image';
                    $is_video = $media_type === 'video';
                ?>
                    <div class="atc-hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo esc_attr($index); ?>" data-media-type="<?php echo esc_attr($media_type); ?>">
                        <?php if (!empty($slide['link'])): ?>
                            <a href="<?php echo esc_url($slide['link']); ?>" class="atc-slide-link" <?php echo !empty($slide['link_target']) ? 'target="' . esc_attr($slide['link_target']) . '"' : ''; ?>>
                        <?php endif; ?>
                        
                        <div class="atc-slide-media">
                            <?php if ($is_video): ?>
                                <?php
                                $video_source = isset($slide['video_source']) ? $slide['video_source'] : 'upload';
                                $video_poster = !empty($slide['video_poster']) ? esc_url($slide['video_poster']) : (!empty($slide['image']) ? esc_url($slide['image']) : '');
                                
                                if ($video_source === 'youtube' && !empty($slide['youtube_id'])):
                                    $youtube_id = esc_attr($slide['youtube_id']);
                                    $youtube_url = 'https://www.youtube.com/embed/' . $youtube_id . '?autoplay=1&mute=1&loop=1&playlist=' . $youtube_id . '&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1';
                                ?>
                                    <div class="atc-slide-video atc-video-youtube">
                                        <iframe src="<?php echo esc_url($youtube_url); ?>" 
                                                frameborder="0" 
                                                allow="autoplay; encrypted-media" 
                                                allowfullscreen
                                                loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                                                class="atc-youtube-iframe"></iframe>
                                    </div>
                                <?php elseif ($video_source === 'vimeo' && !empty($slide['vimeo_id'])): 
                                    $vimeo_id = esc_attr($slide['vimeo_id']);
                                    $vimeo_url = 'https://player.vimeo.com/video/' . $vimeo_id . '?autoplay=1&muted=1&loop=1&controls=0&background=1';
                                ?>
                                    <div class="atc-slide-video atc-video-vimeo">
                                        <iframe src="<?php echo esc_url($vimeo_url); ?>" 
                                                frameborder="0" 
                                                allow="autoplay; fullscreen" 
                                                allowfullscreen
                                                loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                                                class="atc-vimeo-iframe"></iframe>
                                    </div>
                                <?php elseif ($video_source === 'upload' && !empty($slide['video_url'])): ?>
                                    <div class="atc-slide-video atc-video-upload">
                                        <video class="atc-html5-video" 
                                               autoplay 
                                               muted 
                                               loop 
                                               playsinline
                                               poster="<?php echo $video_poster; ?>"
                                               preload="<?php echo $index === 0 ? 'auto' : 'metadata'; ?>">
                                            <source src="<?php echo esc_url($slide['video_url']); ?>" type="video/mp4">
                                            <?php if (!empty($video_poster)): ?>
                                                <img src="<?php echo esc_url($video_poster); ?>" alt="<?php echo esc_attr($slide['title'] ?? 'Slide ' . ($index + 1)); ?>" />
                                            <?php endif; ?>
                                        </video>
                                    </div>
                                <?php else: ?>
                                    <!-- Fallback to image if video not configured -->
                                    <div class="atc-slide-image">
                                        <img src="<?php echo esc_url($slide['image'] ?? ''); ?>" 
                                             alt="<?php echo esc_attr($slide['title'] ?? 'Slide ' . ($index + 1)); ?>" 
                                             loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                                             decoding="async" />
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Image slide -->
                                <div class="atc-slide-image">
                                    <img src="<?php echo esc_url($slide['image']); ?>" 
                                         alt="<?php echo esc_attr($slide['title'] ?? 'Slide ' . ($index + 1)); ?>" 
                                         loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                                         decoding="async" />
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($slide['title']) || !empty($slide['description']) || !empty($slide['button_text'])): ?>
                            <div class="atc-slide-content">
                                <?php if (!empty($slide['title'])): ?>
                                    <h2 class="atc-slide-title"><?php echo esc_html($slide['title']); ?></h2>
                                <?php endif; ?>
                                <?php if (!empty($slide['description'])): ?>
                                    <p class="atc-slide-description"><?php echo esc_html($slide['description']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($slide['button_text'])): ?>
                                    <span class="atc-slide-button"><?php echo esc_html($slide['button_text']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($slide['link'])): ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($show_dots && count($slides) > 1): ?>
                <div class="atc-slider-dots" role="tablist" aria-label="Slider navigation">
                    <?php foreach ($slides as $index => $slide): ?>
                        <button class="atc-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                data-slide="<?php echo esc_attr($index); ?>" 
                                aria-label="Go to slide <?php echo esc_attr($index + 1); ?>"
                                role="tab"
                                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                aria-controls="slide-<?php echo esc_attr($index); ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($show_arrows && count($slides) > 1): ?>
                <button class="atc-slider-arrow atc-slider-prev" aria-label="Previous slide" type="button">‹</button>
                <button class="atc-slider-arrow atc-slider-next" aria-label="Next slide" type="button">›</button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        $all_sliders = self::get_all_sliders();
        $current_slider_id = isset($_GET['slider']) ? sanitize_key($_GET['slider']) : 'default';
        
        // Handle new slider creation
        if (isset($_GET['create']) && isset($_GET['name']) && isset($_GET['slider'])) {
            $new_id = sanitize_key($_GET['slider']);
            $new_name = sanitize_text_field($_GET['name']);
            
            if (!isset($all_sliders[$new_id])) {
                $all_sliders[$new_id] = [
                    'name' => $new_name,
                    'id' => $new_id,
                    'slides' => [],
                ];
                update_option(self::OPTION_NAME, $all_sliders);
            }
            $current_slider_id = $new_id;
        }
        
        $current_slider = self::get_slider($current_slider_id);
        
        // If slider doesn't exist, create default structure and save it
        if (!$current_slider) {
            $default_name = $current_slider_id === 'default' ? 'Default Slider' : ucfirst(str_replace(['-', '_'], ' ', $current_slider_id));
            $current_slider = [
                'name' => $default_name,
                'id' => $current_slider_id,
                'slides' => [],
            ];
            // Save the default slider structure
            $all_sliders[$current_slider_id] = $current_slider;
            update_option(self::OPTION_NAME, $all_sliders);
        }
        
        // Ensure name is set (fallback for old sliders)
        if (empty($current_slider['name'])) {
            $current_slider['name'] = $current_slider_id === 'default' ? 'Default Slider' : ucfirst(str_replace(['-', '_'], ' ', $current_slider_id));
            // Update the slider with the name
            $all_sliders[$current_slider_id] = $current_slider;
            update_option(self::OPTION_NAME, $all_sliders);
        }
        
        $slides = isset($current_slider['slides']) ? $current_slider['slides'] : [];
        $notice_code = isset($_GET['atc_notice']) ? sanitize_text_field($_GET['atc_notice']) : '';
        $error_code = isset($_GET['atc_error']) ? sanitize_text_field($_GET['atc_error']) : '';
        
        // Generate slide template HTML for JavaScript
        // Note: render_slide_editor() already uses ob_start/ob_get_clean internally, so we just call it directly
        $slide_template_html = self::render_slide_editor('__INDEX__', []);
        
        // If template is empty, use a hardcoded fallback template
        // This ensures the feature works even if render_slide_editor() fails
        if (empty($slide_template_html) || trim($slide_template_html) === '') {
            // Create fallback template with all required fields
            $slide_template_html = '<div class="atc-slide-editor" style="border: 2px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f9f9f9;">';
            $slide_template_html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">';
            $slide_template_html .= '<h3 style="margin: 0;">Slide New</h3>';
            $slide_template_html .= '<button type="button" class="button button-link-delete atc-remove-slide">Remove Slide</button>';
            $slide_template_html .= '</div>';
            $slide_template_html .= '<table class="form-table">';
            $slide_template_html .= '<tr><th><label>Slide Image *</label></th><td>';
            $slide_template_html .= '<input type="url" name="slides[__INDEX__][image]" value="" class="regular-text" required />';
            $slide_template_html .= '<button type="button" class="button atc-upload-slide-image">Upload Image</button>';
            $slide_template_html .= '<p class="description">Main image for this slide (required). Recommended: 1200x400px or larger.</p>';
            $slide_template_html .= '</td></tr>';
            $slide_template_html .= '<tr><th><label>Title</label></th><td>';
            $slide_template_html .= '<input type="text" name="slides[__INDEX__][title]" value="" class="regular-text" placeholder="e.g., Special Offer - 50% Off" />';
            $slide_template_html .= '<p class="description">Main heading text (optional)</p></td></tr>';
            $slide_template_html .= '<tr><th><label>Description</label></th><td>';
            $slide_template_html .= '<textarea name="slides[__INDEX__][description]" rows="3" class="large-text" placeholder="e.g., Shop now and get amazing discounts on all products"></textarea>';
            $slide_template_html .= '<p class="description">Short description or offer text (optional)</p></td></tr>';
            $slide_template_html .= '<tr><th><label>Button Text</label></th><td>';
            $slide_template_html .= '<input type="text" name="slides[__INDEX__][button_text]" value="" class="regular-text" placeholder="e.g., Shop Now" />';
            $slide_template_html .= '<p class="description">Text for call-to-action button (optional)</p></td></tr>';
            $slide_template_html .= '<tr><th><label>Link URL</label></th><td>';
            $slide_template_html .= '<input type="url" name="slides[__INDEX__][link]" value="" class="regular-text" placeholder="https://example.com/product" />';
            $slide_template_html .= '<p class="description">URL to open when slide is clicked (optional)</p></td></tr>';
            $slide_template_html .= '<tr><th><label>Link Target</label></th><td>';
            $slide_template_html .= '<select name="slides[__INDEX__][link_target]">';
            $slide_template_html .= '<option value="_self" selected>Same Window</option>';
            $slide_template_html .= '<option value="_blank">New Window</option>';
            $slide_template_html .= '</select></td></tr>';
            $slide_template_html .= '</table></div>';
        }
        
        // Escape the template for JavaScript (handle quotes and special characters)
        // Use JSON_UNESCAPED_SLASHES to keep URLs readable, but escape dangerous characters
        $slide_template_js = json_encode($slide_template_html, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        
        // Ensure we have a valid JSON string
        if ($slide_template_js === false || $slide_template_js === 'null') {
            // Last resort: use a simple string with escaped quotes
            $slide_template_js = '"' . addslashes($slide_template_html) . '"';
        }
        
        ?>
        <div class="wrap atc-hero-slider-admin">
            <h1>🎠 Hero Slider Manager</h1>
            <p class="description">Create and manage multiple hero banner sliders for different pages. Similar to Flipkart-style hero sections.</p>
            
            <?php if ($notice_code === 'saved'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Slider saved successfully!', 'advanced-travel-crm'); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <?php if ($notice_code === 'deleted'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Slider deleted successfully!', 'advanced-travel-crm'); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <?php if ($error_code === 'invalid_slider'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php esc_html_e('Invalid slider ID.', 'advanced-travel-crm'); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <!-- Slider Selector -->
            <div class="atc-slider-selector" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="margin-top: 0;">Select or Create Slider</h2>
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <select id="atc-slider-select" style="min-width: 200px;">
                        <?php 
                        // Always show default if no sliders exist
                        if (empty($all_sliders)): ?>
                            <option value="default" <?php selected($current_slider_id, 'default'); ?>>Default Slider (0 slides)</option>
                        <?php else: ?>
                            <?php foreach ($all_sliders as $id => $slider): 
                                $slide_count = isset($slider['slides']) && is_array($slider['slides']) ? count($slider['slides']) : 0;
                                $slider_name = isset($slider['name']) ? $slider['name'] : ucfirst(str_replace(['-', '_'], ' ', $id));
                            ?>
                                <option value="<?php echo esc_attr($id); ?>" <?php selected($current_slider_id, $id); ?>>
                                    <?php echo esc_html($slider_name); ?> (<?php echo $slide_count; ?> slide<?php echo $slide_count !== 1 ? 's' : ''; ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button type="button" class="button" id="atc-goto-slider">Go to Slider</button>
                    <button type="button" class="button button-primary" id="atc-create-slider">+ Create New Slider</button>
                </div>
            </div>
            
            <!-- Create New Slider Form (Hidden by default) -->
            <div id="atc-create-slider-form" style="display: none; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
                <h3>Create New Slider</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="new_slider_name">Slider Name</label></th>
                        <td>
                            <input type="text" id="new_slider_name" placeholder="e.g., Homepage Slider" class="regular-text" />
                            <p class="description">Display name for this slider</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="new_slider_id">Slider ID</label></th>
                        <td>
                            <input type="text" id="new_slider_id" placeholder="e.g., homepage" class="regular-text" pattern="[a-z0-9_-]+" />
                            <p class="description">Unique ID (lowercase, numbers, hyphens, underscores only). Used in shortcode: [atc_hero_slider id="your-id"]</p>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="atc-create-slider-submit">Create Slider</button>
                    <button type="button" class="button" id="atc-cancel-create">Cancel</button>
                </p>
            </div>
            
            <!-- Current Slider Editor -->
            <div class="atc-current-slider-editor">
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <h2 style="margin: 0;"><?php echo esc_html($current_slider['name'] ?? 'Default Slider'); ?></h2>
                            <p style="margin: 5px 0 0 0; color: #666;">
                                <strong>Slider ID:</strong> <code><?php echo esc_html($current_slider_id); ?></code> | 
                                <strong>Shortcode:</strong> <code>[atc_hero_slider id="<?php echo esc_attr($current_slider_id); ?>"]</code>
                            </p>
                        </div>
                        <?php if ($current_slider_id !== 'default'): ?>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=atc_delete_hero_slider&slider=' . $current_slider_id), 'delete_slider_' . $current_slider_id)); ?>" 
                               class="button button-link-delete" 
                               onclick="return confirm('Are you sure you want to delete this slider? This cannot be undone.');">
                                Delete Slider
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="slider_name">Slider Name</label></th>
                            <td>
                                <input type="text" id="slider_name" name="slider_name" value="<?php echo esc_attr($current_slider['name'] ?? 'Default Slider'); ?>" class="regular-text" />
                                <p class="description">Display name for this slider</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="atc-hero-slider-form">
                    <?php wp_nonce_field('atc_save_hero_slider'); ?>
                    <input type="hidden" name="action" value="atc_save_hero_slider">
                    <input type="hidden" name="slider_id" value="<?php echo esc_attr($current_slider_id); ?>">
                
                <div id="atc-slides-container">
                    <?php if (empty($slides) || !is_array($slides)): ?>
                        <p class="atc-no-slides">No slides added yet. Click "Add New Slide" to get started.</p>
                    <?php else: ?>
                        <?php foreach ($slides as $index => $slide): 
                            // Ensure slide is an array
                            if (!is_array($slide)) {
                                continue;
                            }
                            // Render slide editor
                            echo self::render_slide_editor($index, $slide);
                        endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <p>
                    <button type="button" class="button button-secondary" id="atc-add-slide-btn">+ Add New Slide</button>
                </p>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">Save All Slides</button>
                </p>
            </form>
            
            <div class="atc-shortcode-info">
                <h3>📋 Shortcode Usage</h3>
                <p>Use this shortcode to display the hero slider anywhere on your website:</p>
                <code>[atc_hero_slider id="<?php echo esc_attr($current_slider_id); ?>"]</code>
                
                <h4>Available Options:</h4>
                <ul>
                    <li><code>id="default"</code> - Slider ID to display (default: "default")</li>
                    <li><code>autoplay="true"</code> - Enable/disable autoplay (default: true)</li>
                    <li><code>interval="5000"</code> - Autoplay interval in milliseconds (default: 5000)</li>
                    <li><code>height="400"</code> - Slider height in pixels (default: 400)</li>
                    <li><code>show_dots="true"</code> - Show/hide dot navigation (default: true)</li>
                    <li><code>show_arrows="true"</code> - Show/hide arrow navigation (default: true)</li>
                </ul>
                
                <p><strong>Examples:</strong></p>
                <ul>
                    <li>Homepage: <code>[atc_hero_slider id="homepage"]</code></li>
                    <li>Tours page: <code>[atc_hero_slider id="tours"]</code></li>
                    <li>With options: <code>[atc_hero_slider id="homepage" autoplay="true" interval="6000" height="500"]</code></li>
                </ul>
            </div>
        </div>
        
        <script type="text/javascript">
        (function($) {
            'use strict';
            
            // Ensure jQuery is loaded
            if (typeof jQuery === 'undefined') {
                console.error('ATC Hero Slider: jQuery is not loaded!');
                return;
            }
            
            $(document).ready(function() {
                // Get current slide count from DOM (more reliable than PHP count)
                var existingSlides = $('#atc-slides-container .atc-slide-editor').length;
                var slideIndex = existingSlides > 0 ? existingSlides : <?php echo count($slides); ?>;
                
                // Store slide template
                var slideTemplate = <?php echo $slide_template_js; ?>;
                
                // Debug: Log template status
                if (!slideTemplate || slideTemplate.length === 0) {
                    console.error('ATC Hero Slider: Slide template is empty!');
                    console.error('Template length:', slideTemplate ? slideTemplate.length : 'undefined');
                    console.error('Template type:', typeof slideTemplate);
                } else {
                    console.log('ATC Hero Slider: Slide template loaded successfully. Length:', slideTemplate.length);
                }
                
                // Slider selector
                $(document).on('click', '#atc-goto-slider', function(e) {
                    e.preventDefault();
                    var sliderId = $('#atc-slider-select').val();
                    if (sliderId) {
                        window.location.href = '<?php echo esc_js(admin_url('admin.php?page=atc-hero-slider')); ?>&slider=' + encodeURIComponent(sliderId);
                    }
                });
                
                // Create new slider - Show form
                $(document).on('click', '#atc-create-slider', function(e) {
                    e.preventDefault();
                    var $form = $('#atc-create-slider-form');
                    if ($form.length) {
                        $form.slideDown(300);
                        $('#new_slider_name').focus();
                    } else {
                        alert('Error: Form not found. Please refresh the page.');
                    }
                });
                
                // Cancel create
                $(document).on('click', '#atc-cancel-create', function(e) {
                    e.preventDefault();
                    $('#atc-create-slider-form').slideUp(300);
                    $('#new_slider_name').val('');
                    $('#new_slider_id').val('');
                });
                
                // Submit create slider
                $(document).on('click', '#atc-create-slider-submit', function(e) {
                    e.preventDefault();
                    var name = $('#new_slider_name').val().trim();
                    var id = $('#new_slider_id').val().trim().toLowerCase().replace(/[^a-z0-9_-]/g, '');
                    
                    if (!name) {
                        alert('Please enter a slider name.');
                        $('#new_slider_name').focus();
                        return false;
                    }
                    
                    if (!id) {
                        alert('Please enter a slider ID.');
                        $('#new_slider_id').focus();
                        return false;
                    }
                    
                    if (id === 'default') {
                        alert('"default" is reserved. Please use a different ID.');
                        $('#new_slider_id').focus();
                        return false;
                    }
                    
                    // Check if ID already exists
                    var existingIds = [];
                    $('#atc-slider-select option').each(function() {
                        existingIds.push($(this).val());
                    });
                    
                    if (existingIds.indexOf(id) !== -1) {
                        alert('A slider with this ID already exists. Please use a different ID.');
                        $('#new_slider_id').focus();
                        return false;
                    }
                    
                    // Redirect to create slider
                    var url = '<?php echo esc_js(admin_url('admin.php?page=atc-hero-slider')); ?>&slider=' + encodeURIComponent(id) + '&create=1&name=' + encodeURIComponent(name);
                    window.location.href = url;
                });
                
                // Auto-generate ID from name
                $(document).on('input', '#new_slider_name', function() {
                    var $idField = $('#new_slider_id');
                    if ($idField.val() === '') {
                        var id = $(this).val().toLowerCase().replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, '-');
                        $idField.val(id);
                    }
                });
            
                // Add new slide - Enhanced with better error handling
                $(document).on('click', '#atc-add-slide-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('ATC Hero Slider: Add slide button clicked. Current slideIndex:', slideIndex);
                    
                    // Check if template exists
                    if (!slideTemplate || slideTemplate.length === 0) {
                        alert('Error: Slide template not loaded. Please refresh the page.');
                        console.error('ATC Hero Slider: Slide template is empty!');
                        return false;
                    }
                    
                    // Check if container exists
                    var $container = $('#atc-slides-container');
                    if ($container.length === 0) {
                        alert('Error: Slides container not found. Please refresh the page.');
                        console.error('ATC Hero Slider: Container #atc-slides-container not found!');
                        return false;
                    }
                    
                    // Replace index placeholder
                    var slideHtml = slideTemplate.replace(/__INDEX__/g, slideIndex);
                    
                    // Remove "No slides" message if present
                    if ($container.find('.atc-no-slides').length > 0) {
                        $container.html('');
                    }
                    
                    // Append new slide
                    $container.append(slideHtml);
                    
                    // Scroll to new slide with delay to ensure DOM is updated
                    setTimeout(function() {
                        var $newSlide = $container.find('.atc-slide-editor').last();
                        if ($newSlide.length > 0) {
                            $('html, body').animate({
                                scrollTop: $newSlide.offset().top - 100
                            }, 300);
                        }
                    }, 100);
                    
                    slideIndex++;
                    console.log('ATC Hero Slider: Slide added successfully. New slideIndex:', slideIndex);
                });
                
                // Remove slide
                $(document).on('click', '.atc-remove-slide', function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to remove this slide?')) {
                        $(this).closest('.atc-slide-editor').fadeOut(300, function() {
                            $(this).remove();
                            if ($('#atc-slides-container .atc-slide-editor').length === 0) {
                                $('#atc-slides-container').html('<p class="atc-no-slides">No slides added yet. Click "Add New Slide" to get started.</p>');
                            }
                        });
                    }
                });
                
                // Image upload
                $(document).on('click', '.atc-upload-slide-image', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var slideEditor = button.closest('.atc-slide-editor');
                    var imageInput = slideEditor.find('input[name*="[image]"]');
                    var preview = slideEditor.find('.atc-image-preview');
                    
                    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                        alert('WordPress media library is not available. Please refresh the page.');
                        return;
                    }
                    
                    var frame = wp.media({
                        title: 'Select Slide Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        imageInput.val(attachment.url);
                        if (preview.length === 0) {
                            preview = $('<div class="atc-image-preview" style="margin-top: 10px;"></div>');
                            imageInput.after(preview);
                        }
                        preview.html('<img src="' + attachment.url + '" alt="Preview" style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />');
                    });
                    
                    frame.open();
                });
                
                // Preview on URL input
                $(document).on('input', 'input[name*="[image]"]', function() {
                    var url = $(this).val();
                    var $this = $(this);
                    var preview = $this.siblings('.atc-image-preview');
                    
                    if (url) {
                        if (preview.length === 0) {
                            preview = $('<div class="atc-image-preview" style="margin-top: 10px;"></div>');
                            $this.after(preview);
                        }
                        preview.html('<img src="' + url + '" alt="Preview" style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />');
                    } else if (preview.length > 0) {
                        preview.remove();
                    }
                });
                
                // Media type selector - Show/hide fields
                $(document).on('change', '.atc-media-type-selector', function() {
                    var $slideEditor = $(this).closest('.atc-slide-editor');
                    var mediaType = $(this).val();
                    var $imageFields = $slideEditor.find('.atc-image-fields');
                    var $videoFields = $slideEditor.find('.atc-video-fields');
                    var $videoPosterFields = $slideEditor.find('.atc-video-poster-fields');
                    var $imageInput = $slideEditor.find('.atc-slide-image-input');
                    
                    if (mediaType === 'video') {
                        $imageFields.hide();
                        $imageInput.removeAttr('required');
                        $videoFields.show();
                        $videoPosterFields.show();
                    } else {
                        $imageFields.show();
                        $imageInput.attr('required', 'required');
                        $videoFields.hide();
                        $videoPosterFields.hide();
                    }
                });
                
                // Video source selector - Show/hide specific video fields
                $(document).on('change', '.atc-video-source-selector', function() {
                    var $slideEditor = $(this).closest('.atc-slide-editor');
                    var videoSource = $(this).val();
                    
                    // Hide all video source fields
                    $slideEditor.find('.atc-video-upload-fields').hide();
                    $slideEditor.find('.atc-video-youtube-fields').hide();
                    $slideEditor.find('.atc-video-vimeo-fields').hide();
                    
                    // Show selected video source fields
                    if (videoSource === 'upload') {
                        $slideEditor.find('.atc-video-upload-fields').show();
                    } else if (videoSource === 'youtube') {
                        $slideEditor.find('.atc-video-youtube-fields').show();
                    } else if (videoSource === 'vimeo') {
                        $slideEditor.find('.atc-video-vimeo-fields').show();
                    }
                });
                
                // Video upload button
                $(document).on('click', '.atc-upload-slide-video', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var slideEditor = button.closest('.atc-slide-editor');
                    var videoInput = slideEditor.find('input[name*="[video_url]"]');
                    var preview = slideEditor.find('.atc-video-preview');
                    
                    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                        alert('WordPress media library is not available. Please refresh the page.');
                        return;
                    }
                    
                    var frame = wp.media({
                        title: 'Select Video File',
                        button: { text: 'Use this video' },
                        multiple: false,
                        library: { type: 'video' }
                    });
                    
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        videoInput.val(attachment.url);
                        if (preview.length === 0) {
                            preview = $('<div class="atc-video-preview" style="margin-top: 10px;"></div>');
                            videoInput.after(preview);
                        }
                        preview.html('<video controls style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px;"><source src="' + attachment.url + '" type="video/mp4"></video>');
                    });
                    
                    frame.open();
                });
                
                // Video poster upload button
                $(document).on('click', '.atc-upload-slide-poster', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var slideEditor = button.closest('.atc-slide-editor');
                    var posterInput = slideEditor.find('input[name*="[video_poster]"]');
                    var preview = slideEditor.find('.atc-poster-preview');
                    
                    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                        alert('WordPress media library is not available. Please refresh the page.');
                        return;
                    }
                    
                    var frame = wp.media({
                        title: 'Select Poster Image',
                        button: { text: 'Use this image' },
                        multiple: false,
                        library: { type: 'image' }
                    });
                    
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        posterInput.val(attachment.url);
                        if (preview.length === 0) {
                            preview = $('<div class="atc-poster-preview" style="margin-top: 10px;"></div>');
                            posterInput.after(preview);
                        }
                        preview.html('<img src="' + attachment.url + '" alt="Poster Preview" style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />');
                    });
                    
                    frame.open();
                });
                
                // YouTube URL parser
                $(document).on('input', '.atc-youtube-url-input', function() {
                    var url = $(this).val();
                    var $slideEditor = $(this).closest('.atc-slide-editor');
                    var $youtubeIdInput = $slideEditor.find('.atc-youtube-id-input');
                    var $preview = $slideEditor.find('.atc-video-preview');
                    
                    if (url) {
                        // Extract YouTube video ID from various URL formats
                        var videoId = null;
                        var patterns = [
                            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
                            /youtube\.com\/watch\?.*v=([^&\n?#]+)/
                        ];
                        
                        for (var i = 0; i < patterns.length; i++) {
                            var match = url.match(patterns[i]);
                            if (match && match[1]) {
                                videoId = match[1];
                                break;
                            }
                        }
                        
                        if (videoId) {
                            $youtubeIdInput.val(videoId);
                            if ($preview.length === 0) {
                                $preview = $('<div class="atc-video-preview" style="margin-top: 10px;"></div>');
                                $(this).after($preview);
                            }
                            $preview.html('<iframe width="300" height="169" src="https://www.youtube.com/embed/' + videoId + '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="border: 1px solid #ddd; border-radius: 4px;"></iframe>');
                        } else {
                            $youtubeIdInput.val('');
                            if ($preview.length > 0) {
                                $preview.html('<p style="color: #d63638;">Invalid YouTube URL. Please use format: https://www.youtube.com/watch?v=VIDEO_ID</p>');
                            }
                        }
                    } else {
                        $youtubeIdInput.val('');
                        if ($preview.length > 0) {
                            $preview.remove();
                        }
                    }
                });
                
                // Vimeo URL parser
                $(document).on('input', '.atc-vimeo-url-input', function() {
                    var url = $(this).val();
                    var $slideEditor = $(this).closest('.atc-slide-editor');
                    var $vimeoIdInput = $slideEditor.find('.atc-vimeo-id-input');
                    var $preview = $slideEditor.find('.atc-video-preview');
                    
                    if (url) {
                        // Extract Vimeo video ID
                        var videoId = null;
                        var patterns = [
                            /vimeo\.com\/(\d+)/,
                            /vimeo\.com\/video\/(\d+)/
                        ];
                        
                        for (var i = 0; i < patterns.length; i++) {
                            var match = url.match(patterns[i]);
                            if (match && match[1]) {
                                videoId = match[1];
                                break;
                            }
                        }
                        
                        if (videoId) {
                            $vimeoIdInput.val(videoId);
                            if ($preview.length === 0) {
                                $preview = $('<div class="atc-video-preview" style="margin-top: 10px;"></div>');
                                $(this).after($preview);
                            }
                            $preview.html('<iframe width="300" height="169" src="https://player.vimeo.com/video/' + videoId + '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen style="border: 1px solid #ddd; border-radius: 4px;"></iframe>');
                        } else {
                            $vimeoIdInput.val('');
                            if ($preview.length > 0) {
                                $preview.html('<p style="color: #d63638;">Invalid Vimeo URL. Please use format: https://vimeo.com/VIDEO_ID</p>');
                            }
                        }
                    } else {
                        $vimeoIdInput.val('');
                        if ($preview.length > 0) {
                            $preview.remove();
                        }
                    }
                });
                
                // Video URL preview
                $(document).on('input', '.atc-slide-video-input', function() {
                    var url = $(this).val();
                    var $this = $(this);
                    var preview = $this.siblings('.atc-video-preview');
                    
                    if (url) {
                        if (preview.length === 0) {
                            preview = $('<div class="atc-video-preview" style="margin-top: 10px;"></div>');
                            $this.after(preview);
                        }
                        preview.html('<video controls style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px;"><source src="' + url + '" type="video/mp4"></video>');
                    } else if (preview.length > 0) {
                        preview.remove();
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }
    
    private static function render_slide_editor($index, $slide = []) {
        $slide = wp_parse_args($slide, [
            'media_type' => 'image',
            'image' => '',
            'video_source' => 'upload',
            'video_url' => '',
            'youtube_id' => '',
            'vimeo_id' => '',
            'video_poster' => '',
            'title' => '',
            'description' => '',
            'button_text' => '',
            'link' => '',
            'link_target' => '_self',
        ]);
        
        $media_type = $slide['media_type'];
        $video_source = $slide['video_source'];
        
        // Determine slide number for display
        $slide_number = 'New';
        if (is_numeric($index)) {
            $slide_number = (int)$index + 1;
        } elseif ($index !== '__INDEX__') {
            $slide_number = 'New';
        }
        
        ob_start();
        ?>
        <div class="atc-slide-editor" style="border: 2px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f9f9f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Slide <?php echo esc_html($slide_number); ?></h3>
                <button type="button" class="button button-link-delete atc-remove-slide">Remove Slide</button>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label>Media Type *</label></th>
                    <td>
                        <select name="slides[<?php echo esc_attr($index); ?>][media_type]" class="atc-media-type-selector" style="min-width: 200px;">
                            <option value="image" <?php selected($media_type, 'image'); ?>>Image</option>
                            <option value="video" <?php selected($media_type, 'video'); ?>>Video</option>
                        </select>
                        <p class="description">Choose whether this slide displays an image or video.</p>
                    </td>
                </tr>
                
                <!-- Image Fields -->
                <tr class="atc-image-fields" style="<?php echo $media_type === 'video' ? 'display: none;' : ''; ?>">
                    <th><label>Slide Image *</label></th>
                    <td>
                        <input type="url" name="slides[<?php echo esc_attr($index); ?>][image]" value="<?php echo esc_url($slide['image']); ?>" class="regular-text atc-slide-image-input" <?php echo $media_type === 'image' ? 'required' : ''; ?> />
                        <button type="button" class="button atc-upload-slide-image">Upload from Gallery</button>
                        <p class="description">Main image for this slide. Recommended: 1920x1080px (16:9) for desktop. Images will be automatically optimized for mobile (3:4 aspect ratio).</p>
                        <?php if (!empty($slide['image'])): ?>
                            <div class="atc-image-preview" style="margin-top: 10px;">
                                <img src="<?php echo esc_url($slide['image']); ?>" alt="Preview" style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <!-- Video Fields -->
                <tr class="atc-video-fields" style="<?php echo $media_type === 'image' ? 'display: none;' : ''; ?>">
                    <th><label>Video Source *</label></th>
                    <td>
                        <select name="slides[<?php echo esc_attr($index); ?>][video_source]" class="atc-video-source-selector" style="min-width: 200px;">
                            <option value="upload" <?php selected($video_source, 'upload'); ?>>Upload from Gallery</option>
                            <option value="youtube" <?php selected($video_source, 'youtube'); ?>>YouTube URL</option>
                            <option value="vimeo" <?php selected($video_source, 'vimeo'); ?>>Vimeo URL</option>
                        </select>
                        <p class="description">Choose how to add your video.</p>
                    </td>
                </tr>
                
                <!-- Upload Video -->
                <tr class="atc-video-upload-fields" style="<?php echo ($media_type !== 'video' || $video_source !== 'upload') ? 'display: none;' : ''; ?>">
                    <th><label>Video File *</label></th>
                    <td>
                        <input type="url" name="slides[<?php echo esc_attr($index); ?>][video_url]" value="<?php echo esc_url($slide['video_url']); ?>" class="regular-text atc-slide-video-input" placeholder="https://example.com/video.mp4" />
                        <button type="button" class="button atc-upload-slide-video">Upload Video</button>
                        <p class="description">Upload MP4 video file. Recommended: 1920x1080px, max 50MB for best performance. Mobile-first optimized.</p>
                        <?php if (!empty($slide['video_url'])): ?>
                            <div class="atc-video-preview" style="margin-top: 10px;">
                                <video controls style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px;">
                                    <source src="<?php echo esc_url($slide['video_url']); ?>" type="video/mp4">
                                </video>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <!-- YouTube Video -->
                <tr class="atc-video-youtube-fields" style="<?php echo ($media_type !== 'video' || $video_source !== 'youtube') ? 'display: none;' : ''; ?>">
                    <th><label>YouTube URL *</label></th>
                    <td>
                        <input type="url" name="slides[<?php echo esc_attr($index); ?>][youtube_url]" value="<?php echo esc_url($slide['youtube_url'] ?? ''); ?>" class="regular-text atc-youtube-url-input" placeholder="https://www.youtube.com/watch?v=VIDEO_ID" />
                        <input type="hidden" name="slides[<?php echo esc_attr($index); ?>][youtube_id]" value="<?php echo esc_attr($slide['youtube_id']); ?>" class="atc-youtube-id-input" />
                        <p class="description">Paste YouTube video URL (e.g., https://www.youtube.com/watch?v=VIDEO_ID). Video will auto-play, loop, and be muted.</p>
                        <?php if (!empty($slide['youtube_id'])): ?>
                            <div class="atc-video-preview" style="margin-top: 10px;">
                                <iframe width="300" height="169" src="https://www.youtube.com/embed/<?php echo esc_attr($slide['youtube_id']); ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="border: 1px solid #ddd; border-radius: 4px;"></iframe>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <!-- Vimeo Video -->
                <tr class="atc-video-vimeo-fields" style="<?php echo ($media_type !== 'video' || $video_source !== 'vimeo') ? 'display: none;' : ''; ?>">
                    <th><label>Vimeo URL *</label></th>
                    <td>
                        <input type="url" name="slides[<?php echo esc_attr($index); ?>][vimeo_url]" value="<?php echo esc_url($slide['vimeo_url'] ?? ''); ?>" class="regular-text atc-vimeo-url-input" placeholder="https://vimeo.com/VIDEO_ID" />
                        <input type="hidden" name="slides[<?php echo esc_attr($index); ?>][vimeo_id]" value="<?php echo esc_attr($slide['vimeo_id']); ?>" class="atc-vimeo-id-input" />
                        <p class="description">Paste Vimeo video URL (e.g., https://vimeo.com/VIDEO_ID). Video will auto-play, loop, and be muted.</p>
                        <?php if (!empty($slide['vimeo_id'])): ?>
                            <div class="atc-video-preview" style="margin-top: 10px;">
                                <iframe width="300" height="169" src="https://player.vimeo.com/video/<?php echo esc_attr($slide['vimeo_id']); ?>" frameborder="0" allow="autoplay; fullscreen" allowfullscreen style="border: 1px solid #ddd; border-radius: 4px;"></iframe>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <!-- Video Poster/Thumbnail -->
                <tr class="atc-video-poster-fields" style="<?php echo $media_type !== 'video' ? 'display: none;' : ''; ?>">
                    <th><label>Video Poster/Thumbnail</label></th>
                    <td>
                        <input type="url" name="slides[<?php echo esc_attr($index); ?>][video_poster]" value="<?php echo esc_url($slide['video_poster']); ?>" class="regular-text atc-slide-poster-input" placeholder="https://example.com/poster.jpg" />
                        <button type="button" class="button atc-upload-slide-poster">Upload Poster</button>
                        <p class="description">Thumbnail image shown before video loads (optional). Falls back to slide image if not set.</p>
                        <?php if (!empty($slide['video_poster'])): ?>
                            <div class="atc-poster-preview" style="margin-top: 10px;">
                                <img src="<?php echo esc_url($slide['video_poster']); ?>" alt="Poster Preview" style="max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label>Title</label></th>
                    <td>
                        <input type="text" name="slides[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($slide['title']); ?>" class="regular-text" placeholder="e.g., Special Offer - 50% Off" />
                        <p class="description">Main heading text (optional)</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Description</label></th>
                    <td>
                        <textarea name="slides[<?php echo esc_attr($index); ?>][description]" rows="3" class="large-text" placeholder="e.g., Shop now and get amazing discounts on all products"><?php echo esc_textarea($slide['description']); ?></textarea>
                        <p class="description">Short description or offer text (optional)</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Button Text</label></th>
                    <td>
                        <input type="text" name="slides[<?php echo esc_attr($index); ?>][button_text]" value="<?php echo esc_attr($slide['button_text']); ?>" class="regular-text" placeholder="e.g., Shop Now" />
                        <p class="description">Text for call-to-action button (optional)</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Link URL</label></th>
                    <td>
                        <input type="url" name="slides[<?php echo esc_attr($index); ?>][link]" value="<?php echo esc_url($slide['link']); ?>" class="regular-text" placeholder="https://example.com/product" />
                        <p class="description">URL to open when slide is clicked (optional)</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Link Target</label></th>
                    <td>
                        <select name="slides[<?php echo esc_attr($index); ?>][link_target]">
                            <option value="_self" <?php selected($slide['link_target'], '_self'); ?>>Same Window</option>
                            <option value="_blank" <?php selected($slide['link_target'], '_blank'); ?>>New Window</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function save_slider_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        check_admin_referer('atc_save_hero_slider');
        
        $slider_id = isset($_POST['slider_id']) ? sanitize_key($_POST['slider_id']) : 'default';
        $slider_name = isset($_POST['slider_name']) ? sanitize_text_field($_POST['slider_name']) : 'Default Slider';
        
        $slides = [];
        
        if (isset($_POST['slides']) && is_array($_POST['slides'])) {
            foreach ($_POST['slides'] as $slide) {
                $media_type = isset($slide['media_type']) ? sanitize_text_field($slide['media_type']) : 'image';
                $has_image = !empty($slide['image']);
                $has_video = false;
                
                // Check for video content
                if ($media_type === 'video') {
                    $video_source = isset($slide['video_source']) ? sanitize_text_field($slide['video_source']) : 'upload';
                    
                    if ($video_source === 'upload' && !empty($slide['video_url'])) {
                        $has_video = true;
                    } elseif ($video_source === 'youtube' && !empty($slide['youtube_id'])) {
                        $has_video = true;
                    } elseif ($video_source === 'vimeo' && !empty($slide['vimeo_id'])) {
                        $has_video = true;
                    }
                }
                
                // Only save if has image or valid video
                if ($has_image || $has_video) {
                    $slide_data = [
                        'media_type' => $media_type,
                        'image' => esc_url_raw($slide['image'] ?? ''),
                        'title' => sanitize_text_field($slide['title'] ?? ''),
                        'description' => sanitize_textarea_field($slide['description'] ?? ''),
                        'button_text' => sanitize_text_field($slide['button_text'] ?? ''),
                        'link' => esc_url_raw($slide['link'] ?? ''),
                        'link_target' => in_array($slide['link_target'] ?? '_self', ['_self', '_blank']) ? $slide['link_target'] : '_self',
                    ];
                    
                    // Add video-specific fields
                    if ($media_type === 'video') {
                        $slide_data['video_source'] = $video_source;
                        $slide_data['video_url'] = esc_url_raw($slide['video_url'] ?? '');
                        $slide_data['video_poster'] = esc_url_raw($slide['video_poster'] ?? '');
                        $slide_data['youtube_id'] = sanitize_text_field($slide['youtube_id'] ?? '');
                        $slide_data['vimeo_id'] = sanitize_text_field($slide['vimeo_id'] ?? '');
                    }
                    
                    $slides[] = $slide_data;
                }
            }
        }
        
        // Get all sliders
        $all_sliders = self::get_all_sliders();
        
        // Ensure slider_name is not empty
        if (empty($slider_name) || trim($slider_name) === '') {
            $slider_name = $slider_id === 'default' ? 'Default Slider' : ucfirst(str_replace(['-', '_'], ' ', $slider_id));
        }
        
        // Save/update this slider - preserve existing data if any
        $existing_slider = isset($all_sliders[$slider_id]) ? $all_sliders[$slider_id] : [];
        $all_sliders[$slider_id] = array_merge($existing_slider, [
            'name' => $slider_name,
            'id' => $slider_id,
            'slides' => $slides,
        ]);
        
        $result = update_option(self::OPTION_NAME, $all_sliders);
        
        // Debug: Log if save failed
        if (!$result && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ATC Hero Slider: Failed to save slider. Slider ID: ' . $slider_id . ', Name: ' . $slider_name);
        }
        
        wp_redirect(add_query_arg(['atc_notice' => 'saved', 'slider' => $slider_id], admin_url('admin.php?page=atc-hero-slider')));
        exit;
    }
    
    public static function delete_slider_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        $slider_id = isset($_GET['slider']) ? sanitize_key($_GET['slider']) : '';
        
        if (empty($slider_id) || $slider_id === 'default') {
            wp_redirect(add_query_arg('atc_error', 'invalid_slider', admin_url('admin.php?page=atc-hero-slider')));
            exit;
        }
        
        check_admin_referer('delete_slider_' . $slider_id);
        
        $all_sliders = self::get_all_sliders();
        
        if (isset($all_sliders[$slider_id])) {
            unset($all_sliders[$slider_id]);
            update_option(self::OPTION_NAME, $all_sliders);
        }
        
        wp_redirect(add_query_arg('atc_notice', 'deleted', admin_url('admin.php?page=atc-hero-slider')));
        exit;
    }
}

