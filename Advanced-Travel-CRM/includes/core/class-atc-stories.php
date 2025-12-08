<?php
/**
 * ATC Stories Feature
 * Instagram/Facebook Stories-like feature for showcasing special packages
 * Supports images, videos, package details, and customizable settings
 */

if (!defined('ABSPATH')) exit;

class ATC_Stories {
    
    const OPTION_NAME = 'atc_stories';
    const STORY_DURATION_DEFAULT = 5000; // 5 seconds in milliseconds
    
    public static function init() {
        add_shortcode('atc_stories', [__CLASS__, 'stories_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 20); // Priority 20 to ensure parent menu exists
        add_action('admin_post_atc_save_story', [__CLASS__, 'save_story_handler']);
        add_action('admin_post_atc_delete_story', [__CLASS__, 'delete_story_handler']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
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
                __('Stories', 'advanced-travel-crm'),
                __('Stories', 'advanced-travel-crm'),
                'manage_options',
                'atc-stories',
                [__CLASS__, 'admin_page']
            );
        }
    }
    
    public static function enqueue_assets() {
        if (!defined('ATC_ASSETS_URL') || !defined('ATC_VERSION')) {
            return;
        }
        wp_enqueue_style('atc-stories', ATC_ASSETS_URL . 'css/atc-stories.css', [], ATC_VERSION);
        wp_enqueue_script('atc-stories', ATC_ASSETS_URL . 'js/atc-stories.js', ['jquery'], ATC_VERSION, true);
        
        // Localize script with settings
        wp_localize_script('atc-stories', 'atcStories', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('atc_stories_nonce'),
        ]);
    }
    
    public static function admin_assets($hook) {
        // Robust detection of the Stories admin screen
        $is_stories_screen = (strpos($hook, 'atc-stories') !== false);
        if (!$is_stories_screen) {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if ($screen && isset($screen->id)) {
                $is_stories_screen = (strpos($screen->id, 'atc-stories') !== false);
            }
        }
        if (!$is_stories_screen) {
            return;
        }
        
        // Ensure WordPress media uploader and jQuery are available
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        
        if (!defined('ATC_ASSETS_URL') || !defined('ATC_VERSION')) {
            return;
        }
        wp_enqueue_style('atc-stories-admin', ATC_ASSETS_URL . 'css/atc-stories-admin.css', [], ATC_VERSION);
    }
    
    public static function get_all_stories() {
        $stories = get_option(self::OPTION_NAME, []);
        if (!is_array($stories)) {
            $stories = [];
        }
        
        // Filter out expired stories
        $current_time = current_time('timestamp');
        $stories = array_filter($stories, function($story) use ($current_time) {
            if (empty($story['expiry_date'])) {
                return true; // No expiry date means story is permanent
            }
            $expiry = strtotime($story['expiry_date']);
            return $expiry > $current_time;
        });
        
        // Sort by order/priority
        usort($stories, function($a, $b) {
            $order_a = isset($a['order']) ? intval($a['order']) : 0;
            $order_b = isset($b['order']) ? intval($b['order']) : 0;
            return $order_a - $order_b;
        });
        
        return $stories;
    }
    
    public static function get_story($story_id) {
        $stories = self::get_all_stories();
        foreach ($stories as $story) {
            if (isset($story['id']) && $story['id'] === $story_id) {
                return $story;
            }
        }
        return null;
    }
    
    public static function stories_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => '10',
            'category' => '',
            'show_expired' => 'false',
        ], $atts);
        
        $stories = self::get_all_stories();
        
        // Filter by category if specified
        if (!empty($atts['category'])) {
            $stories = array_filter($stories, function($story) use ($atts) {
                return isset($story['category']) && $story['category'] === $atts['category'];
            });
        }
        
        // Reindex after filtering to keep JSON as a proper array
        $stories = array_values($stories);
        
        // Limit stories
        $limit = absint($atts['limit']);
        if ($limit > 0) {
            $stories = array_slice($stories, 0, $limit);
        }
        
        if (empty($stories)) {
            return '';
        }
        
        $stories_json = wp_json_encode($stories);
        $container_id = 'atc-stories-' . uniqid();
        $script_id = $container_id . '-data';
        
        ob_start();
        ?>
        <div class="atc-stories-container" id="<?php echo esc_attr($container_id); ?>" data-stories='<?php echo esc_attr($stories_json); ?>'>
            <script type="application/json" id="<?php echo esc_attr($script_id); ?>" class="atc-stories-data" data-container="<?php echo esc_attr($container_id); ?>"><?php echo $stories_json; ?></script>
            <div class="atc-stories-list">
                <?php foreach ($stories as $index => $story): 
                    $media_type = isset($story['media_type']) ? $story['media_type'] : 'image';
                    $thumbnail = !empty($story['thumbnail']) ? $story['thumbnail'] : (!empty($story['media_url']) ? $story['media_url'] : '');
                ?>
                    <div class="atc-story-item" data-story-id="<?php echo esc_attr($story['id'] ?? $index); ?>" data-story-index="<?php echo esc_attr($index); ?>">
                        <div class="atc-story-avatar">
                            <?php if ($thumbnail): ?>
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($story['title'] ?? 'Story'); ?>" />
                            <?php else: ?>
                                <div class="atc-story-avatar-placeholder">
                                    <span><?php echo esc_html(substr($story['title'] ?? 'S', 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="atc-story-title"><?php echo esc_html($story['title'] ?? 'Story'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        $stories = self::get_all_stories();
        $notice_code = isset($_GET['atc_notice']) ? sanitize_text_field($_GET['atc_notice']) : '';
        $error_code = isset($_GET['atc_error']) ? sanitize_text_field($_GET['atc_error']) : '';
        $edit_story_id = isset($_GET['edit']) ? sanitize_key($_GET['edit']) : '';
        $editing_story = null;
        
        if ($edit_story_id) {
            foreach ($stories as $story) {
                if (isset($story['id']) && $story['id'] === $edit_story_id) {
                    $editing_story = $story;
                    break;
                }
            }
        }
        
        ?>
        <div class="wrap atc-stories-admin">
            <h1>📱 Stories Manager</h1>
            <p class="description">Create Instagram/Facebook Stories-like content to showcase special packages. Stories can include images, videos, package details, and custom expiry dates.</p>
            
            <?php if ($notice_code === 'saved'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Story saved successfully!', 'advanced-travel-crm'); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <?php if ($notice_code === 'deleted'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Story deleted successfully!', 'advanced-travel-crm'); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <?php if ($error_code === 'invalid_story'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php esc_html_e('Invalid story ID.', 'advanced-travel-crm'); ?></strong></p>
                </div>
            <?php elseif ($error_code === 'missing_fields'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php esc_html_e('Please provide a title and media URL for the story.', 'advanced-travel-crm'); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <?php if ($editing_story): ?>
                <div class="atc-edit-story-form" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
                    <h2>Edit Story</h2>
                    <?php self::render_story_form($editing_story); ?>
                </div>
            <?php else: ?>
                <div class="atc-new-story-form" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
                    <h2>Create New Story</h2>
                    <?php self::render_story_form(); ?>
                </div>
            <?php endif; ?>
            
            <div class="atc-stories-list-admin" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <h2>All Stories (<?php echo count($stories); ?>)</h2>
                
                <?php if (empty($stories)): ?>
                    <p>No stories created yet. Create your first story above!</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Thumbnail</th>
                                <th>Title</th>
                                <th>Media Type</th>
                                <th>Package Price</th>
                                <th>Expiry Date</th>
                                <th>Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stories as $story): 
                                $expiry_date = !empty($story['expiry_date']) ? date('Y-m-d H:i', strtotime($story['expiry_date'])) : 'Never';
                                $is_expired = !empty($story['expiry_date']) && strtotime($story['expiry_date']) < current_time('timestamp');
                            ?>
                                <tr class="<?php echo $is_expired ? 'atc-expired-story' : ''; ?>">
                                    <td>
                                        <?php if (!empty($story['thumbnail'])): ?>
                                            <img src="<?php echo esc_url($story['thumbnail']); ?>" alt="Thumbnail" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%;" />
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <span><?php echo esc_html(substr($story['title'] ?? 'S', 0, 1)); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo esc_html($story['title'] ?? 'Untitled'); ?></strong></td>
                                    <td><?php echo esc_html(ucfirst($story['media_type'] ?? 'image')); ?></td>
                                    <td><?php echo !empty($story['package_price']) ? esc_html($story['package_price']) : '—'; ?></td>
                                    <td><?php echo esc_html($expiry_date); ?></td>
                                    <td><?php echo esc_html($story['order'] ?? 0); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=atc-stories&edit=' . ($story['id'] ?? ''))); ?>" class="button button-small">Edit</a>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=atc_delete_story&story_id=' . ($story['id'] ?? '')), 'delete_story_' . ($story['id'] ?? ''))); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('Are you sure you want to delete this story?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="atc-shortcode-info" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-top: 20px;">
                <h3>📋 Shortcode Usage</h3>
                <p>Use this shortcode to display stories anywhere on your website:</p>
                <code>[atc_stories]</code>
                
                <h4>Available Options:</h4>
                <ul>
                    <li><code>limit="10"</code> - Maximum number of stories to display (default: 10)</li>
                    <li><code>category="special"</code> - Filter stories by category (optional)</li>
                    <li><code>show_expired="false"</code> - Show expired stories (default: false)</li>
                </ul>
                
                <p><strong>Examples:</strong></p>
                <ul>
                    <li>All stories: <code>[atc_stories]</code></li>
                    <li>Limited: <code>[atc_stories limit="5"]</code></li>
                    <li>By category: <code>[atc_stories category="special-offers"]</code></li>
                </ul>
            </div>
        </div>
        
        <script type="text/javascript">
        (function($) {
            'use strict';
            
            $(document).ready(function() {
                // Media type selector
                $(document).on('change', '.atc-media-type-selector', function() {
                    var $form = $(this).closest('form');
                    var mediaType = $(this).val();
                    
                    if (mediaType === 'video') {
                        $form.find('.atc-video-fields').show();
                        $form.find('.atc-image-fields').hide();
                        $form.find('.atc-media-image-input').prop('required', false);
                        $form.find('.atc-media-video-input').prop('required', true);
                    } else {
                        $form.find('.atc-video-fields').hide();
                        $form.find('.atc-image-fields').show();
                        $form.find('.atc-media-video-input').prop('required', false);
                        $form.find('.atc-media-image-input').prop('required', true);
                    }
                });
                
                // Image upload
                $(document).on('click', '.atc-upload-story-media', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var target = button.data('target') || 'image';
                    var input = target === 'video'
                        ? button.siblings('input[name="media_url_video"]')
                        : button.siblings('input[name="media_url_image"]');
                    var preview = button.siblings('.atc-media-preview');
                    
                    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                        alert('WordPress media library is not available.');
                        return;
                    }
                    
                    var frame = wp.media({
                        title: 'Select Story Media',
                        button: { text: 'Use this' },
                        multiple: false
                    });
                    
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        input.val(attachment.url);
                        if (preview.length === 0) {
                            preview = $('<div class="atc-media-preview" style="margin-top: 10px;"></div>');
                            input.after(preview);
                        }
                        if (attachment.type === 'video') {
                            preview.html('<video controls style="max-width: 300px; height: auto;"><source src="' + attachment.url + '" type="video/mp4"></video>');
                        } else {
                            preview.html('<img src="' + attachment.url + '" alt="Preview" style="max-width: 300px; height: auto;" />');
                        }
                    });
                    
                    frame.open();
                });
                
                // Thumbnail upload
                $(document).on('click', '.atc-upload-story-thumbnail', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var input = button.siblings('input[name="thumbnail"]');
                    var preview = button.siblings('.atc-thumbnail-preview');
                    
                    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                        alert('WordPress media library is not available.');
                        return;
                    }
                    
                    var frame = wp.media({
                        title: 'Select Thumbnail',
                        button: { text: 'Use this image' },
                        multiple: false,
                        library: { type: 'image' }
                    });
                    
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        input.val(attachment.url);
                        if (preview.length === 0) {
                            preview = $('<div class="atc-thumbnail-preview" style="margin-top: 10px;"></div>');
                            input.after(preview);
                        }
                        preview.html('<img src="' + attachment.url + '" alt="Preview" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;" />');
                    });
                    
                    frame.open();
                });
            });
        })(jQuery);
        </script>
        <?php
    }
    
    private static function render_story_form($story = null) {
        $story = wp_parse_args($story, [
            'id' => '',
            'title' => '',
            'media_type' => 'image',
            'media_url' => '',
            'thumbnail' => '',
            'package_price' => '',
            'package_link' => '',
            'package_description' => '',
            'story_duration' => self::STORY_DURATION_DEFAULT,
            'expiry_date' => '',
            'category' => '',
            'order' => 0,
        ]);
        
        $is_edit = !empty($story['id']);
        $media_url_image = $story['media_type'] === 'video' ? '' : $story['media_url'];
        $media_url_video = $story['media_type'] === 'video' ? $story['media_url'] : '';
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('atc_save_story'); ?>
            <input type="hidden" name="action" value="atc_save_story">
            <?php if ($is_edit): ?>
                <input type="hidden" name="story_id" value="<?php echo esc_attr($story['id']); ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="story_title">Story Title *</label></th>
                    <td>
                        <input type="text" id="story_title" name="title" value="<?php echo esc_attr($story['title']); ?>" class="regular-text" required />
                        <p class="description">Title displayed on the story avatar (e.g., "Special Offer")</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="media_type">Media Type *</label></th>
                    <td>
                        <select id="media_type" name="media_type" class="atc-media-type-selector">
                            <option value="image" <?php selected($story['media_type'], 'image'); ?>>Image</option>
                            <option value="video" <?php selected($story['media_type'], 'video'); ?>>Video</option>
                        </select>
                        <p class="description">Choose image or video for your story</p>
                    </td>
                </tr>
                
                <tr class="atc-image-fields" style="<?php echo $story['media_type'] === 'video' ? 'display: none;' : ''; ?>">
                    <th><label for="media_url">Media URL *</label></th>
                    <td>
                        <input type="url" id="media_url" name="media_url_image" value="<?php echo esc_url($media_url_image); ?>" class="regular-text atc-media-image-input" <?php echo $story['media_type'] === 'video' ? '' : 'required'; ?> />
                        <button type="button" class="button atc-upload-story-media" data-target="image">Upload from Gallery</button>
                        <p class="description">Image URL for the story (recommended: 1080x1920px for mobile-first design)</p>
                        <?php if (!empty($media_url_image)): ?>
                            <div class="atc-media-preview" style="margin-top: 10px;">
                                <img src="<?php echo esc_url($media_url_image); ?>" alt="Preview" style="max-width: 300px; height: auto;" />
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr class="atc-video-fields" style="<?php echo $story['media_type'] === 'image' ? 'display: none;' : ''; ?>">
                    <th><label for="media_url_video">Video URL *</label></th>
                    <td>
                        <input type="url" id="media_url_video" name="media_url_video" value="<?php echo esc_url($media_url_video); ?>" class="regular-text atc-media-video-input" <?php echo $story['media_type'] === 'video' ? 'required' : ''; ?> />
                        <button type="button" class="button atc-upload-story-media" data-target="video">Upload from Gallery</button>
                        <p class="description">Video URL (MP4 format recommended, max 50MB for best performance)</p>
                        <?php if (!empty($media_url_video)): ?>
                            <div class="atc-media-preview" style="margin-top: 10px;">
                                <video controls style="max-width: 300px; height: auto;">
                                    <source src="<?php echo esc_url($media_url_video); ?>" type="video/mp4">
                                </video>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="thumbnail">Thumbnail</label></th>
                    <td>
                        <input type="url" id="thumbnail" name="thumbnail" value="<?php echo esc_url($story['thumbnail']); ?>" class="regular-text" />
                        <button type="button" class="button atc-upload-story-thumbnail">Upload Thumbnail</button>
                        <p class="description">Thumbnail image for story avatar (optional, defaults to media if not set)</p>
                        <?php if (!empty($story['thumbnail'])): ?>
                            <div class="atc-thumbnail-preview" style="margin-top: 10px;">
                                <img src="<?php echo esc_url($story['thumbnail']); ?>" alt="Thumbnail" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;" />
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="package_price">Package Price</label></th>
                    <td>
                        <input type="text" id="package_price" name="package_price" value="<?php echo esc_attr($story['package_price']); ?>" class="regular-text" placeholder="e.g., $999 or ₹49,999" />
                        <p class="description">Price to display on the story (optional)</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="package_link">Package Link</label></th>
                    <td>
                        <input type="url" id="package_link" name="package_link" value="<?php echo esc_url($story['package_link']); ?>" class="regular-text" placeholder="https://example.com/package" />
                        <p class="description">Link to product/package page (optional, appears as "View Details" button)</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="package_description">Package Description</label></th>
                    <td>
                        <textarea id="package_description" name="package_description" rows="3" class="large-text"><?php echo esc_textarea($story['package_description']); ?></textarea>
                        <p class="description">Short description shown on the story (optional)</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="story_duration">Story Duration (ms)</label></th>
                    <td>
                        <input type="number" id="story_duration" name="story_duration" value="<?php echo esc_attr($story['story_duration']); ?>" class="small-text" min="1000" step="500" />
                        <p class="description">How long each story displays in milliseconds (default: 5000ms = 5 seconds). Minimum: 1000ms</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="expiry_date">Expiry Date</label></th>
                    <td>
                        <input type="datetime-local" id="expiry_date" name="expiry_date" value="<?php echo !empty($story['expiry_date']) ? esc_attr(date('Y-m-d\TH:i', strtotime($story['expiry_date']))) : ''; ?>" class="regular-text" />
                        <p class="description">When the story should expire (leave empty for permanent stories)</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="category">Category</label></th>
                    <td>
                        <input type="text" id="category" name="category" value="<?php echo esc_attr($story['category']); ?>" class="regular-text" placeholder="e.g., special-offers" />
                        <p class="description">Category for filtering stories (optional)</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="order">Display Order</label></th>
                    <td>
                        <input type="number" id="order" name="order" value="<?php echo esc_attr($story['order']); ?>" class="small-text" min="0" />
                        <p class="description">Order in which stories appear (lower numbers appear first)</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary button-large"><?php echo $is_edit ? 'Update Story' : 'Create Story'; ?></button>
                <?php if ($is_edit): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=atc-stories')); ?>" class="button">Cancel</a>
                <?php endif; ?>
            </p>
        </form>
        <?php
    }
    
    public static function save_story_handler() {
        // Allow editors/authors to create stories too (not just admins)
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_redirect(add_query_arg('atc_error', 'no_permission', admin_url('admin.php?page=atc-stories')));
            exit;
        }
        
        check_admin_referer('atc_save_story');
        
        $story_id = isset($_POST['story_id']) ? sanitize_key($_POST['story_id']) : uniqid('story_');
        $stories = get_option(self::OPTION_NAME, []);
        
        // Find existing story or create new
        $story_index = -1;
        foreach ($stories as $index => $story) {
            if (isset($story['id']) && $story['id'] === $story_id) {
                $story_index = $index;
                break;
            }
        }
        
        $media_type = in_array($_POST['media_type'] ?? 'image', ['image', 'video']) ? $_POST['media_type'] : 'image';
        $media_url_image = esc_url_raw($_POST['media_url_image'] ?? '');
        $media_url_video = esc_url_raw($_POST['media_url_video'] ?? '');
        $story_data = [
            'id' => $story_id,
            'title' => sanitize_text_field($_POST['title'] ?? 'Untitled'),
            'media_type' => $media_type,
            'media_url' => $media_type === 'video' ? $media_url_video : $media_url_image,
            'thumbnail' => esc_url_raw($_POST['thumbnail'] ?? ''),
            'package_price' => sanitize_text_field($_POST['package_price'] ?? ''),
            'package_link' => esc_url_raw($_POST['package_link'] ?? ''),
            'package_description' => sanitize_textarea_field($_POST['package_description'] ?? ''),
            'story_duration' => absint($_POST['story_duration'] ?? self::STORY_DURATION_DEFAULT),
            'expiry_date' => !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : '',
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'order' => absint($_POST['order'] ?? 0),
            'created_at' => isset($stories[$story_index]['created_at']) ? $stories[$story_index]['created_at'] : current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        
        // Ensure minimum duration
        if ($story_data['story_duration'] < 1000) {
            $story_data['story_duration'] = 1000;
        }
        
        if (empty($story_data['media_url'])) {
            wp_redirect(add_query_arg('atc_error', 'missing_fields', admin_url('admin.php?page=atc-stories')));
            exit;
        }

        if ($story_index >= 0) {
            $stories[$story_index] = $story_data;
        } else {
            $stories[] = $story_data;
        }
        
        update_option(self::OPTION_NAME, $stories);
        
        wp_redirect(add_query_arg(['atc_notice' => 'saved'], admin_url('admin.php?page=atc-stories')));
        exit;
    }
    
    public static function delete_story_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        $story_id = isset($_GET['story_id']) ? sanitize_key($_GET['story_id']) : '';
        
        if (empty($story_id)) {
            wp_redirect(add_query_arg('atc_error', 'invalid_story', admin_url('admin.php?page=atc-stories')));
            exit;
        }
        
        check_admin_referer('delete_story_' . $story_id);
        
        $stories = get_option(self::OPTION_NAME, []);
        $stories = array_filter($stories, function($story) use ($story_id) {
            return !isset($story['id']) || $story['id'] !== $story_id;
        });
        
        update_option(self::OPTION_NAME, array_values($stories));
        
        wp_redirect(add_query_arg('atc_notice', 'deleted', admin_url('admin.php?page=atc-stories')));
        exit;
    }
}

ATC_Stories::init();

