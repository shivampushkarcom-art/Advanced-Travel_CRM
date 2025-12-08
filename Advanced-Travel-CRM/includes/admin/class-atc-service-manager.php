<?php
/**
 * ATC Service Manager
 * Enable/disable travel services from admin
 */

if (!defined('ABSPATH')) exit;

class ATC_Service_Manager {

    const OPTION_SERVICE_STATUS = 'atc_services_status';
    const OPTION_CUSTOM_SERVICES = 'atc_services_custom';
    const OPTION_SERVICE_ICONS = 'atc_services_icons'; // Store icon customizations for all services
    const OPTION_SERVICE_LABELS = 'atc_services_labels'; // Store custom labels for all services

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_atc_toggle_service', [__CLASS__, 'toggle_service_handler']);
        add_action('admin_post_atc_add_service', [__CLASS__, 'add_service_handler']);
        add_action('admin_post_atc_edit_service', [__CLASS__, 'edit_service_handler']);
        add_action('admin_post_atc_delete_service', [__CLASS__, 'delete_service_handler']);
    }

    private static function get_default_services() {
        return [
            'tours'   => ['label' => 'Tour Packages', 'page_slug' => 'tours',   'icon' => '🏖️'],
            'hotels'  => ['label' => 'Hotels',        'page_slug' => 'hotels',  'icon' => '🏨'],
            'flights' => ['label' => 'Flights',       'page_slug' => 'flights', 'icon' => '✈️'],
            'trains'  => ['label' => 'Trains',        'page_slug' => 'trains',  'icon' => '🚂'],
            'cars'    => ['label' => 'Car Rentals',   'page_slug' => 'cars',    'icon' => '🚗'],
            'forex'   => ['label' => 'Forex',         'page_slug' => 'forex',  'icon' => '💱'],
            'visa'    => ['label' => 'Visa Services', 'page_slug' => 'visa',   'icon' => '🛂'],
            'celebrity' => ['label' => 'Celebrity Management', 'page_slug' => 'celebrity-management', 'icon' => '⭐'],
        ];
    }

    public static function get_custom_services() {
        $custom = get_option(self::OPTION_CUSTOM_SERVICES, []);

        if (!is_array($custom)) {
            $custom = [];
        }

        return $custom;
    }

    public static function get_service_icons() {
        $icons = get_option(self::OPTION_SERVICE_ICONS, []);

        if (!is_array($icons)) {
            $icons = [];
        }

        return $icons;
    }

    public static function save_service_icons($icons) {
        update_option(self::OPTION_SERVICE_ICONS, $icons);
    }

    public static function get_service_labels() {
        $labels = get_option(self::OPTION_SERVICE_LABELS, []);

        if (!is_array($labels)) {
            $labels = [];
        }

        return $labels;
    }

    public static function save_service_labels($labels) {
        update_option(self::OPTION_SERVICE_LABELS, $labels);
    }

    private static function save_custom_services($services) {
        update_option(self::OPTION_CUSTOM_SERVICES, $services);
    }

    public static function get_registered_services() {
        return array_merge(self::get_default_services(), self::get_custom_services());
    }

    private static function sanitize_service_key($key) {
        $key = strtolower(sanitize_title($key));
        return preg_replace('/[^a-z0-9_\-]/', '', $key);
    }

    public static function service_exists($service_key) {
        $services = self::get_registered_services();
        return isset($services[$service_key]);
    }

    public static function menu() {
        add_submenu_page(
            'atc-dashboard',  // ✅ FIX #2: Correct parent
            __('Service Manager', 'advanced-travel-crm'),
            __('Services', 'advanced-travel-crm'),
            'manage_options',
            'atc-service-manager',
            [__CLASS__, 'services_page']
        );
    }

    public static function get_services_status() {
        $statuses = get_option(self::OPTION_SERVICE_STATUS, []);

        if (!is_array($statuses)) {
            $statuses = [];
        }

        $original = $statuses;

        foreach (self::get_registered_services() as $key => $service) {
            if (!isset($statuses[$key])) {
                $statuses[$key] = 1;
            }
        }

        if ($statuses !== $original) {
            update_option(self::OPTION_SERVICE_STATUS, $statuses);
        }

        return $statuses;
    }

    public static function set_service_status($service_key, $status) {
        $statuses = self::get_services_status();
        $statuses[$service_key] = $status ? 1 : 0;
        update_option(self::OPTION_SERVICE_STATUS, $statuses);
    }

    public static function services_page() {
        $services         = self::get_registered_services();
        $custom_services  = self::get_custom_services();
        $statuses         = self::get_services_status();

        $notice_code = isset($_GET['atc_notice']) ? sanitize_text_field($_GET['atc_notice']) : '';
        $error_code  = isset($_GET['atc_error']) ? sanitize_text_field($_GET['atc_error']) : '';

        $notices = [
            'toggled' => __('Service status updated successfully.', 'advanced-travel-crm'),
            'added'   => __('New service added successfully.', 'advanced-travel-crm'),
            'updated' => __('Service updated successfully.', 'advanced-travel-crm'),
            'deleted' => __('Custom service removed successfully.', 'advanced-travel-crm'),
        ];

        $errors = [
            'invalid_service'  => __('The selected service does not exist.', 'advanced-travel-crm'),
            'duplicate_service'=> __('A service with that key already exists. Please choose a different key.', 'advanced-travel-crm'),
            'missing_label'    => __('Service name is required.', 'advanced-travel-crm'),
            'missing_key'      => __('Service key is required.', 'advanced-travel-crm'),
            'delete_default'   => __('Default services cannot be removed.', 'advanced-travel-crm'),
        ];

        ?>
        <div class="wrap atc-services-wrap">
            <h1>🎯 Service Manager</h1>
            <p class="description"><?php esc_html_e("Control the services available on your multiservice travel portal.", 'advanced-travel-crm'); ?></p>

            <?php if (isset($_GET['atc_service_toggled'])) : $notice_code = 'toggled'; endif; ?>

            <?php if ($notice_code && isset($notices[$notice_code])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php echo esc_html($notices[$notice_code]); ?></strong></p>
                </div>
            <?php endif; ?>

            <?php if ($error_code && isset($errors[$error_code])) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php echo esc_html($errors[$error_code]); ?></strong></p>
                </div>
            <?php endif; ?>

            <div class="atc-services-grid">
                <?php foreach ($services as $key => $service) :
                    $active       = isset($statuses[$key]) ? (int) $statuses[$key] : 1;
                    $status_text  = $active ? __('Active', 'advanced-travel-crm') : __('Inactive', 'advanced-travel-crm');
                    $status_class = $active ? 'atc-service-active' : 'atc-service-inactive';
                    $button_text  = $active ? __('Disable', 'advanced-travel-crm') : __('Enable', 'advanced-travel-crm');
                    $button_class = $active ? 'button-secondary' : 'button-primary';
                    $is_custom    = isset($custom_services[$key]);
                    $service_icons = self::get_service_icons();
                    $service_labels = self::get_service_labels();
                    $icon         = isset($service_icons[$key]['icon']) ? $service_icons[$key]['icon'] : (isset($service['icon']) ? $service['icon'] : '📦');
                    $icon_image   = isset($service_icons[$key]['icon_image']) ? $service_icons[$key]['icon_image'] : (isset($service['icon_image']) ? $service['icon_image'] : '');
                    $display_label = isset($service_labels[$key]) ? $service_labels[$key] : (isset($service['label']) ? $service['label'] : ucfirst($key));
                    $page_slug    = isset($service['page_slug']) ? $service['page_slug'] : $key;
                    $config_file  = ATC_SERVICES_DIR . $key . '.json';
                    $config_exists = file_exists($config_file);
                ?>
                <div class="atc-service-card <?php echo esc_attr($status_class); ?><?php echo $is_custom ? ' atc-service-custom' : ''; ?>">
                    <div class="atc-service-icon">
                        <?php if (!empty($icon_image)): ?>
                            <img src="<?php echo esc_url($icon_image); ?>" alt="<?php echo esc_attr($service['label']); ?>" class="atc-service-icon-image" />
                        <?php else: ?>
                            <?php echo esc_html($icon); ?>
                        <?php endif; ?>
                    </div>
                    <div class="atc-service-content">
                        <div class="atc-service-header">
                            <h3><?php echo esc_html($service['label']); ?></h3>
                            <?php if ($is_custom) : ?>
                                <span class="atc-service-badge atc-badge-custom">Custom</span>
                            <?php endif; ?>
                        </div>
                        <p class="atc-service-slug">
                            <strong><?php esc_html_e('Service Key:', 'advanced-travel-crm'); ?></strong>
                            <code><?php echo esc_html($key); ?></code>
                        </p>
                        <p class="atc-service-slug">
                            <strong><?php esc_html_e('Landing Slug:', 'advanced-travel-crm'); ?></strong>
                            <code><?php echo esc_html($page_slug); ?></code>
                        </p>
                        <div class="atc-service-status">
                            <span class="atc-status-badge atc-status-<?php echo $active ? 'active' : 'inactive'; ?>">
                                <?php echo $active ? '✓' : '✗'; ?> <?php echo esc_html($status_text); ?>
                            </span>
                        </div>
                    </div>
                    <div class="atc-service-actions">
                        <a href="<?php echo esc_url(
                            wp_nonce_url(
                                admin_url('admin-post.php?action=atc_toggle_service&service=' . $key),
                                'atc_toggle_service_' . $key
                            )
                        ); ?>" class="button <?php echo esc_attr($button_class); ?>">
                            <?php echo esc_html($button_text); ?>
                        </a>
                        
                        <button type="button" class="button atc-edit-service-btn" data-service-key="<?php echo esc_attr($key); ?>" data-service-label="<?php echo esc_attr($display_label); ?>" data-service-slug="<?php echo esc_attr($page_slug); ?>" data-service-icon="<?php echo esc_attr($icon); ?>" data-service-icon-image="<?php echo esc_attr($icon_image); ?>" data-is-custom="<?php echo $is_custom ? '1' : '0'; ?>">
                            <?php esc_html_e('Edit', 'advanced-travel-crm'); ?>
                        </button>

                        <div class="atc-service-meta">
                            <span class="atc-config-status <?php echo $config_exists ? '' : 'atc-config-missing'; ?>">
                                <?php echo $config_exists ? '✅ ' . esc_html__('Config', 'advanced-travel-crm') : '⚠️ ' . esc_html__('Config Missing', 'advanced-travel-crm'); ?>
                            </span>

                            <?php if ($is_custom) : ?>
                                <a href="<?php echo esc_url(
                                    wp_nonce_url(
                                        admin_url('admin-post.php?action=atc_delete_service&service=' . $key),
                                        'atc_delete_service_' . $key
                                    )
                                ); ?>" class="button-link-delete" onclick="return confirm('<?php echo esc_js(__('Delete this custom service? This cannot be undone.', 'advanced-travel-crm')); ?>');">
                                    <?php esc_html_e('Delete', 'advanced-travel-crm'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Edit Service Modal -->
            <div id="atc-edit-service-modal" class="atc-modal" style="display: none;">
                <div class="atc-modal-content">
                    <div class="atc-modal-header">
                        <h2>✏️ <?php esc_html_e('Edit Service', 'advanced-travel-crm'); ?></h2>
                        <button type="button" class="atc-modal-close">&times;</button>
                    </div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="atc-service-form" id="atc-edit-service-form">
                        <?php wp_nonce_field('atc_edit_service'); ?>
                        <input type="hidden" name="action" value="atc_edit_service">
                        <input type="hidden" name="service_key_original" id="atc_edit_service_key_original">
                        <input type="hidden" name="is_custom" id="atc_edit_is_custom">
                        
                        <div class="atc-form-row" id="atc_edit_label_row">
                            <label for="atc_edit_service_label"><?php esc_html_e('Service Name (Display Name)', 'advanced-travel-crm'); ?> *</label>
                            <input type="text" id="atc_edit_service_label" name="service_label" required />
                            <p class="description" id="atc_edit_label_desc"><?php esc_html_e('This name will be displayed on the frontend service grid.', 'advanced-travel-crm'); ?></p>
                        </div>

                        <div class="atc-form-row" id="atc_edit_slug_row">
                            <label for="atc_edit_service_slug"><?php esc_html_e('Landing Page Slug', 'advanced-travel-crm'); ?></label>
                            <input type="text" id="atc_edit_service_slug" name="service_slug" />
                            <p class="description"><?php esc_html_e('Optional. Used to auto-detect service context on matching pages.', 'advanced-travel-crm'); ?></p>
                        </div>

                        <div class="atc-form-row">
                            <label for="atc_edit_service_icon"><?php esc_html_e('Icon / Emoji', 'advanced-travel-crm'); ?></label>
                            <input type="text" id="atc_edit_service_icon" name="service_icon" maxlength="4" />
                            <p class="description"><?php esc_html_e('Emoji icon (max 4 characters). Leave empty if using custom icon image.', 'advanced-travel-crm'); ?></p>
                        </div>

                        <div class="atc-form-row">
                            <label for="atc_edit_service_icon_image"><?php esc_html_e('Custom Icon Image (URL)', 'advanced-travel-crm'); ?></label>
                            <div class="atc-icon-image-upload">
                                <input type="url" id="atc_edit_service_icon_image" name="service_icon_image" class="regular-text" />
                                <button type="button" class="button atc-upload-icon-btn" data-target="atc_edit_service_icon_image">
                                    <?php esc_html_e('Upload Image', 'advanced-travel-crm'); ?>
                                </button>
                            </div>
                            <p class="description"><?php esc_html_e('Upload or enter URL for custom icon image. Supported formats: PNG, SVG, JPG, JPEG, GIF, WEBP. Recommended size: 64x64px or larger (square).', 'advanced-travel-crm'); ?></p>
                            <div id="atc_edit_icon_image_preview" class="atc-icon-preview" style="margin-top: 10px; display: none;">
                                <img src="" alt="Icon Preview" style="max-width: 64px; max-height: 64px; border: 1px solid #ddd; border-radius: 4px;" />
                            </div>
                        </div>

                        <div class="atc-form-actions">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Update Service', 'advanced-travel-crm'); ?></button>
                            <button type="button" class="button atc-modal-close"><?php esc_html_e('Cancel', 'advanced-travel-crm'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="atc-service-add">
                <h2>➕ <?php esc_html_e('Add New Service', 'advanced-travel-crm'); ?></h2>
                <p><?php esc_html_e('Extend your multiservice catalog by defining additional offerings (e.g., visas, cruises, corporate travel).', 'advanced-travel-crm'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="atc-service-form">
                    <?php wp_nonce_field('atc_add_service'); ?>
                    <input type="hidden" name="action" value="atc_add_service">

                    <div class="atc-form-row">
                        <label for="atc_service_label"><?php esc_html_e('Service Name', 'advanced-travel-crm'); ?> *</label>
                        <input type="text" id="atc_service_label" name="service_label" required placeholder="e.g., Visa Assistance">
                    </div>

                    <div class="atc-form-row">
                        <label for="atc_service_key"><?php esc_html_e('Service Key', 'advanced-travel-crm'); ?> *</label>
                        <input type="text" id="atc_service_key" name="service_key" required placeholder="e.g., visas" pattern="[a-z0-9_-]+" title="Lowercase letters, numbers, hyphen or underscore">
                        <p class="description"><?php esc_html_e('Used in shortcodes and configuration files. Lowercase letters, numbers, hyphen or underscore only.', 'advanced-travel-crm'); ?></p>
                    </div>

                    <div class="atc-form-row">
                        <label for="atc_service_slug"><?php esc_html_e('Landing Page Slug', 'advanced-travel-crm'); ?></label>
                        <input type="text" id="atc_service_slug" name="service_slug" placeholder="e.g., visa" />
                        <p class="description"><?php esc_html_e('Optional. Used to auto-detect service context on matching pages. Defaults to the service key.', 'advanced-travel-crm'); ?></p>
                    </div>

                    <div class="atc-form-row">
                        <label for="atc_service_icon"><?php esc_html_e('Icon / Emoji', 'advanced-travel-crm'); ?></label>
                        <input type="text" id="atc_service_icon" name="service_icon" maxlength="4" placeholder="e.g., 🛂" />
                        <p class="description"><?php esc_html_e('Emoji icon (max 4 characters). Leave empty if using custom icon image.', 'advanced-travel-crm'); ?></p>
                    </div>

                    <div class="atc-form-row">
                        <label for="atc_service_icon_image"><?php esc_html_e('Custom Icon Image (URL)', 'advanced-travel-crm'); ?></label>
                        <div class="atc-icon-image-upload">
                            <input type="url" id="atc_service_icon_image" name="service_icon_image" placeholder="https://example.com/icon.png" class="regular-text" />
                            <button type="button" class="button atc-upload-icon-btn" data-target="atc_service_icon_image">
                                <?php esc_html_e('Upload Image', 'advanced-travel-crm'); ?>
                            </button>
                        </div>
                        <p class="description"><?php esc_html_e('Upload or enter URL for custom icon image. Recommended size: 64x64px or larger (square).', 'advanced-travel-crm'); ?></p>
                        <div id="atc_icon_image_preview" class="atc-icon-preview" style="margin-top: 10px; display: none;">
                            <img src="" alt="Icon Preview" style="max-width: 64px; max-height: 64px; border: 1px solid #ddd; border-radius: 4px;" />
                        </div>
                    </div>

                    <div class="atc-form-actions">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Add Service', 'advanced-travel-crm'); ?></button>
                    </div>
                </form>
            </div>

            <div class="atc-services-info">
                <h2>ℹ️ <?php esc_html_e('How Services Work', 'advanced-travel-crm'); ?></h2>
                <ol>
                    <li><strong><?php esc_html_e('Enable/Disable:', 'advanced-travel-crm'); ?></strong> <?php esc_html_e("Control which services appear on your website.", 'advanced-travel-crm'); ?></li>
                    <li><strong><?php esc_html_e('Configuration:', 'advanced-travel-crm'); ?></strong> <?php esc_html_e('Each service reads an optional JSON config from the /services/ folder.', 'advanced-travel-crm'); ?></li>
                    <li><strong><?php esc_html_e('Search & Booking:', 'advanced-travel-crm'); ?></strong> <?php esc_html_e('Shortcodes and widgets adapt automatically to enabled services.', 'advanced-travel-crm'); ?></li>
                    <li><strong><?php esc_html_e('Notifications:', 'advanced-travel-crm'); ?></strong> <?php esc_html_e('Leads and bookings instantly notify assigned admins via email/WhatsApp.', 'advanced-travel-crm'); ?></li>
                </ol>

                <h3>📋 <?php esc_html_e('Service Configuration Files', 'advanced-travel-crm'); ?></h3>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Service', 'advanced-travel-crm'); ?></th>
                            <th><?php esc_html_e('Config File', 'advanced-travel-crm'); ?></th>
                            <th><?php esc_html_e('Status', 'advanced-travel-crm'); ?></th>
                            <th><?php esc_html_e('Path', 'advanced-travel-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $key => $service) :
                            $config_file = ATC_SERVICES_DIR . $key . '.json';
                            $exists      = file_exists($config_file);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($service['label']); ?></strong></td>
                            <td><code><?php echo esc_html($key); ?>.json</code></td>
                            <td>
                                <?php if ($exists) : ?>
                                    <span style="color: #10b981;">✅ <?php esc_html_e('Found', 'advanced-travel-crm'); ?></span>
                                <?php else : ?>
                                    <span style="color: #ef4444;">❌ <?php esc_html_e('Missing', 'advanced-travel-crm'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo esc_html(str_replace(ABSPATH, '/', $config_file)); ?></code></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3>📝 <?php esc_html_e('Shortcode Examples', 'advanced-travel-crm'); ?></h3>
                <pre><code>[atc_search service="tours" title="Search Tours"]</code></pre>
                <pre><code>[atc_booking_form service="hotels" title="Book Hotel"]</code></pre>
                <pre><code>[atc_booking_button service="flights" text="Book Flight"]</code></pre>
                <pre><code>[atc_service_grid title="Our Services"]</code></pre>
                <pre><code>[atc_premium_search service="tours"]</code></pre>
            </div>
        </div>

        <?php
        // Enqueue enhanced styles
        wp_enqueue_style('atc-service-manager-enhanced', ATC_ASSETS_URL . 'css/atc-service-manager-enhanced.css', [], ATC_VERSION);
        
        // Enqueue media uploader
        wp_enqueue_media();
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Icon image upload
            $('.atc-upload-icon-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var targetInput = $('#' + button.data('target'));
                var preview = $('#atc_icon_image_preview');
                
                var frame = wp.media({
                    title: '<?php echo esc_js(__('Select Icon Image', 'advanced-travel-crm')); ?>',
                    button: {
                        text: '<?php echo esc_js(__('Use this image', 'advanced-travel-crm')); ?>'
                    },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    targetInput.val(attachment.url);
                    preview.find('img').attr('src', attachment.url);
                    preview.show();
                });
                
                frame.open();
            });
            
            // Preview on URL input
            $('#atc_service_icon_image').on('input', function() {
                var url = $(this).val();
                if (url) {
                    $('#atc_icon_image_preview img').attr('src', url);
                    $('#atc_icon_image_preview').show();
                } else {
                    $('#atc_icon_image_preview').hide();
                }
            });
            
            // Edit service modal
            $('.atc-edit-service-btn').on('click', function() {
                var $btn = $(this);
                var isCustom = $btn.data('is-custom') == '1';
                
                $('#atc_edit_service_key_original').val($btn.data('service-key'));
                $('#atc_edit_is_custom').val(isCustom ? '1' : '0');
                $('#atc_edit_service_label').val($btn.data('service-label'));
                $('#atc_edit_service_slug').val($btn.data('service-slug'));
                $('#atc_edit_service_icon').val($btn.data('service-icon'));
                $('#atc_edit_service_icon_image').val($btn.data('service-icon-image'));
                
                // Show/hide fields based on whether it's a custom service
                if (isCustom) {
                    $('#atc_edit_label_row').show();
                    $('#atc_edit_slug_row').show();
                    $('#atc_edit_label_desc').text('<?php echo esc_js(__('This name will be displayed on the frontend service grid.', 'advanced-travel-crm')); ?>');
                } else {
                    // For default services, show label field but hide slug (slug can't be changed for defaults)
                    $('#atc_edit_label_row').show();
                    $('#atc_edit_slug_row').hide();
                    $('#atc_edit_label_desc').text('<?php echo esc_js(__('Customize the display name shown on the frontend service grid. The service key cannot be changed.', 'advanced-travel-crm')); ?>');
                }
                
                if ($btn.data('service-icon-image')) {
                    $('#atc_edit_icon_image_preview img').attr('src', $btn.data('service-icon-image'));
                    $('#atc_edit_icon_image_preview').show();
                } else {
                    $('#atc_edit_icon_image_preview').hide();
                }
                
                $('#atc-edit-service-modal').fadeIn();
            });
            
            // Close modal
            $('.atc-modal-close').on('click', function() {
                $('#atc-edit-service-modal').fadeOut();
            });
            
            // Edit form icon upload
            $('.atc-upload-icon-btn[data-target="atc_edit_service_icon_image"]').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var targetInput = $('#' + button.data('target'));
                var preview = $('#atc_edit_icon_image_preview');
                
                var frame = wp.media({
                    title: '<?php echo esc_js(__('Select Icon Image', 'advanced-travel-crm')); ?>',
                    button: {
                        text: '<?php echo esc_js(__('Use this image', 'advanced-travel-crm')); ?>'
                    },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    targetInput.val(attachment.url);
                    preview.find('img').attr('src', attachment.url);
                    preview.show();
                });
                
                frame.open();
            });
            
            // Edit form preview on URL input
            $('#atc_edit_service_icon_image').on('input', function() {
                var url = $(this).val();
                if (url) {
                    $('#atc_edit_icon_image_preview img').attr('src', url);
                    $('#atc_edit_icon_image_preview').show();
                } else {
                    $('#atc_edit_icon_image_preview').hide();
                }
            });
        });
        </script>
        <style>
        .atc-icon-image-upload {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .atc-icon-image-upload input {
            flex: 1;
        }
        .atc-icon-preview img {
            display: block;
        }
        .atc-service-icon-image {
            width: 64px;
            height: 64px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        </style>
        <?php
    }

    public static function add_service_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }

        check_admin_referer('atc_add_service');

        $label_raw = isset($_POST['service_label']) ? wp_unslash($_POST['service_label']) : '';
        $key_raw   = isset($_POST['service_key']) ? wp_unslash($_POST['service_key']) : '';
        $slug_raw  = isset($_POST['service_slug']) ? wp_unslash($_POST['service_slug']) : '';
        $icon_raw  = isset($_POST['service_icon']) ? wp_unslash($_POST['service_icon']) : '';
        $icon_image_raw = isset($_POST['service_icon_image']) ? wp_unslash($_POST['service_icon_image']) : '';

        $label = sanitize_text_field($label_raw);
        $service_key = self::sanitize_service_key($key_raw);
        $slug  = sanitize_title($slug_raw);
        $icon  = sanitize_text_field($icon_raw);
        $icon_image = esc_url_raw($icon_image_raw);

        if (empty($label)) {
            self::redirect_with_error('missing_label');
        }

        if (empty($service_key)) {
            self::redirect_with_error('missing_key');
        }

        if (self::service_exists($service_key)) {
            self::redirect_with_error('duplicate_service');
        }

        if (empty($slug)) {
            $slug = $service_key;
        }

        if (empty($icon) && empty($icon_image)) {
            $icon = '📦';
        }

        $custom = self::get_custom_services();
        $custom[$service_key] = [
            'label'      => $label,
            'page_slug'  => $slug,
            'icon'       => $icon,
            'icon_image' => $icon_image,
        ];

        self::save_custom_services($custom);
        self::set_service_status($service_key, 1);

        wp_redirect(add_query_arg('atc_notice', 'added', admin_url('admin.php?page=atc-service-manager')));
        exit;
    }

    public static function edit_service_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }

        check_admin_referer('atc_edit_service');

        $service_key_original = isset($_POST['service_key_original']) ? self::sanitize_service_key(wp_unslash($_POST['service_key_original'])) : '';
        $is_custom = isset($_POST['is_custom']) ? (int) $_POST['is_custom'] : 0;
        $label_raw = isset($_POST['service_label']) ? wp_unslash($_POST['service_label']) : '';
        $slug_raw  = isset($_POST['service_slug']) ? wp_unslash($_POST['service_slug']) : '';
        $icon_raw  = isset($_POST['service_icon']) ? wp_unslash($_POST['service_icon']) : '';
        $icon_image_raw = isset($_POST['service_icon_image']) ? wp_unslash($_POST['service_icon_image']) : '';

        $label = sanitize_text_field($label_raw);
        $slug  = sanitize_title($slug_raw);
        $icon  = sanitize_text_field($icon_raw);
        $icon_image = esc_url_raw($icon_image_raw);

        if (empty($service_key_original)) {
            self::redirect_with_error('invalid_service');
        }

        if (!self::service_exists($service_key_original)) {
            self::redirect_with_error('invalid_service');
        }

        // Validate image URL format if provided
        if (!empty($icon_image)) {
            $allowed_extensions = ['png', 'svg', 'jpg', 'jpeg', 'gif', 'webp'];
            $url_parts = parse_url($icon_image);
            $path = isset($url_parts['path']) ? strtolower($url_parts['path']) : '';
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            
            if (!empty($extension) && !in_array($extension, $allowed_extensions)) {
                // Still allow it, but log a warning - user might be using a CDN or API
            }
        }

        // Get service icons and labels
        $service_icons = self::get_service_icons();
        $service_labels = self::get_service_labels();
        
        // Save icon/icon_image for all services
        $service_icons[$service_key_original] = [
            'icon'       => $icon,
            'icon_image' => $icon_image,
        ];
        self::save_service_icons($service_icons);

        // Save custom label for all services (if provided)
        if (!empty($label)) {
            $service_labels[$service_key_original] = $label;
            self::save_service_labels($service_labels);
        }

        // If it's a custom service, also update label and slug in custom services
        if ($is_custom) {
            if (empty($label)) {
                self::redirect_with_error('missing_label');
            }

            $custom = self::get_custom_services();
            
            if (!isset($custom[$service_key_original])) {
                self::redirect_with_error('invalid_service');
            }

            if (empty($slug)) {
                $slug = $service_key_original;
            }

            // Update the custom service
            $custom[$service_key_original] = [
                'label'      => $label,
                'page_slug'  => $slug,
                'icon'       => $icon,
                'icon_image' => $icon_image,
            ];

            self::save_custom_services($custom);
        }

        wp_redirect(add_query_arg('atc_notice', 'updated', admin_url('admin.php?page=atc-service-manager')));
        exit;
    }

    public static function delete_service_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }

        $service = isset($_GET['service']) ? self::sanitize_service_key(wp_unslash($_GET['service'])) : '';

        if (empty($service)) {
            self::redirect_with_error('invalid_service');
        }

        check_admin_referer('atc_delete_service_' . $service);

        $custom = self::get_custom_services();

        if (!isset($custom[$service])) {
            self::redirect_with_error('delete_default');
        }

        unset($custom[$service]);
        self::save_custom_services($custom);

        $statuses = self::get_services_status();
        if (isset($statuses[$service])) {
            unset($statuses[$service]);
            update_option(self::OPTION_SERVICE_STATUS, $statuses);
        }

        wp_redirect(add_query_arg('atc_notice', 'deleted', admin_url('admin.php?page=atc-service-manager')));
        exit;
    }

    private static function redirect_with_error($code) {
        wp_redirect(add_query_arg('atc_error', $code, admin_url('admin.php?page=atc-service-manager')));
        exit;
    }

    public static function toggle_service_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }

        $service = isset($_GET['service']) ? self::sanitize_service_key(wp_unslash($_GET['service'])) : '';

        if (empty($service) || !self::service_exists($service)) {
            self::redirect_with_error('invalid_service');
        }

        check_admin_referer('atc_toggle_service_' . $service);

        $statuses = self::get_services_status();
        $current  = isset($statuses[$service]) ? (int) $statuses[$service] : 1;
        $statuses[$service] = $current ? 0 : 1;
        update_option(self::OPTION_SERVICE_STATUS, $statuses);

        wp_redirect(add_query_arg('atc_notice', 'toggled', admin_url('admin.php?page=atc-service-manager')));
        exit;
    }
}
