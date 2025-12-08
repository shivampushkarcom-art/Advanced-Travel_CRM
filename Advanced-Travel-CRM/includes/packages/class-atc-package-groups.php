<?php
/**
 * ATC Package Groups Management
 * Admin interface for creating and managing featured package groups/collections
 */

if (!defined('ABSPATH')) exit;

class ATC_Package_Groups {
    
    private static $initialized = false;
    
    public static function init() {
        // Prevent double initialization
        if (self::$initialized) {
            return;
        }
        
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 21); // After custom packages
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('admin_post_atc_save_package_group', [__CLASS__, 'save_group_handler']);
        add_action('admin_post_atc_delete_package_group', [__CLASS__, 'delete_group_handler']);
        add_shortcode('atc_package_group', [__CLASS__, 'package_group_shortcode']);
        self::$initialized = true;
    }
    
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Package Groups', 'advanced-travel-crm'),
            __('Package Groups', 'advanced-travel-crm'),
            'manage_options',
            'atc-package-groups',
            [__CLASS__, 'groups_page']
        );
    }
    
    public static function register_routes() {
        register_rest_route('atc/v1', '/package-groups', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_groups'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    public static function get_groups($request) {
        global $wpdb;
        
        $service = sanitize_text_field($request['service'] ?? '');
        $status = sanitize_text_field($request['status'] ?? 'active');
        
        $where = ["status = %s"];
        $params = [$status];
        
        if (!empty($service)) {
            $where[] = "service_key = %s";
            $params[] = $service;
        }
        
        $sql = "SELECT * FROM " . ATC_TABLE_PACKAGE_GROUPS . " 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY sort_order ASC, created_at DESC";
        
        $groups = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        return rest_ensure_response([
            'success' => true,
            'groups' => $groups,
            'count' => count($groups),
        ]);
    }
    
    public static function groups_page() {
        // Show success/error notices
        if (isset($_GET['atc_notice'])) {
            $notice = sanitize_text_field($_GET['atc_notice']);
            if ($notice === 'created') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Package group created successfully!', 'advanced-travel-crm') . '</p></div>';
            } elseif ($notice === 'updated') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Package group updated successfully!', 'advanced-travel-crm') . '</p></div>';
            } elseif ($notice === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Package group deleted successfully!', 'advanced-travel-crm') . '</p></div>';
            }
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            self::edit_group_page();
            return;
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'add') {
            self::add_group_page();
            return;
        }
        
        self::list_groups_page();
    }
    
    public static function list_groups_page() {
        global $wpdb;
        
        $groups = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_PACKAGE_GROUPS . " ORDER BY sort_order ASC, created_at DESC",
            ARRAY_A
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('Package Groups', 'advanced-travel-crm'); ?>
                <a href="<?php echo admin_url('admin.php?page=atc-package-groups&action=add'); ?>" class="page-title-action">
                    <?php _e('Add New Group', 'advanced-travel-crm'); ?>
                </a>
            </h1>
            
            <p class="description"><?php _e('Create featured package groups/collections (e.g., "Honeymoon Packages", "Adventure Tours", "Weekend Getaways")', 'advanced-travel-crm'); ?></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Group ID', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Name', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Slug', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Service', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Display Type', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Actions', 'advanced-travel-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groups)): ?>
                        <tr>
                            <td colspan="8"><?php _e('No package groups found. Create your first group!', 'advanced-travel-crm'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><?php echo esc_html($group['id']); ?></td>
                                <td><?php echo esc_html($group['group_id'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo esc_html($group['name'] ?? 'Untitled'); ?></strong></td>
                                <td><?php echo esc_html($group['slug'] ?? '-'); ?></td>
                                <td><?php echo esc_html($group['service_key'] ?? '-'); ?></td>
                                <td><?php echo esc_html($group['display_type'] ?? 'grid'); ?></td>
                                <td>
                                    <span class="atc-status-badge atc-status-<?php echo esc_attr($group['status'] ?? 'active'); ?>">
                                        <?php echo esc_html(ucfirst($group['status'] ?? 'active')); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=atc-package-groups&action=edit&id=' . $group['id']); ?>" class="button button-small">
                                        <?php _e('Edit', 'advanced-travel-crm'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin-post.php?action=atc_delete_package_group&id=' . $group['id'] . '&_wpnonce=' . wp_create_nonce('delete_group_' . $group['id'])); ?>" 
                                       class="button button-small delete"
                                       onclick="return confirm('<?php _e('Are you sure you want to delete this group?', 'advanced-travel-crm'); ?>');">
                                        <?php _e('Delete', 'advanced-travel-crm'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public static function add_group_page() {
        self::group_form();
    }
    
    public static function edit_group_page() {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            wp_die(__('Invalid group ID', 'advanced-travel-crm'));
        }
        
        global $wpdb;
        $group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_PACKAGE_GROUPS . " WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if (!$group) {
            wp_die(__('Group not found', 'advanced-travel-crm'));
        }
        
        self::group_form($group);
    }
    
    public static function group_form($group = null) {
        $is_edit = !empty($group);
        $group = $group ?: [];
        
        wp_enqueue_media();
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? __('Edit Package Group', 'advanced-travel-crm') : __('Add New Package Group', 'advanced-travel-crm'); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="atc-group-form">
                <?php wp_nonce_field('atc_save_package_group'); ?>
                <input type="hidden" name="action" value="atc_save_package_group">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="group_id" value="<?php echo esc_attr($group['id']); ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Group Name', 'advanced-travel-crm'); ?> *</label></th>
                        <td>
                            <input type="text" name="name" value="<?php echo esc_attr($group['name'] ?? ''); ?>" class="regular-text" required>
                            <p class="description"><?php _e('Name of the package group (e.g., "Honeymoon Packages", "Adventure Tours")', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Slug', 'advanced-travel-crm'); ?> *</label></th>
                        <td>
                            <input type="text" name="slug" value="<?php echo esc_attr($group['slug'] ?? ''); ?>" class="regular-text" required>
                            <p class="description"><?php _e('URL-friendly slug (e.g., "honeymoon-packages")', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Service', 'advanced-travel-crm'); ?></label></th>
                        <td>
                            <select name="service_key" id="service_key">
                                <option value=""><?php _e('All Services', 'advanced-travel-crm'); ?></option>
                                <?php
                                if (class_exists('ATC_Services')) {
                                    $services = ATC_Services::get_services();
                                } else {
                                    $services = ['tours' => ['label' => 'Tours'], 'hotels' => ['label' => 'Hotels'], 'flights' => ['label' => 'Flights'], 'trains' => ['label' => 'Trains'], 'cars' => ['label' => 'Cars'], 'forex' => ['label' => 'Forex'], 'visa' => ['label' => 'Visa']];
                                }
                                foreach ($services as $key => $service):
                                    $label = is_array($service) ? $service['label'] : $service;
                                ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($group['service_key'] ?? '', $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Service this group belongs to (leave empty for all services)', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Description', 'advanced-travel-crm'); ?></label></th>
                        <td>
                            <textarea name="description" rows="5" class="large-text"><?php echo esc_textarea($group['description'] ?? ''); ?></textarea>
                            <p class="description"><?php _e('Brief description of this package group', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Image URL', 'advanced-travel-crm'); ?></label></th>
                        <td>
                            <input type="url" name="image_url" id="image_url" value="<?php echo esc_url($group['image_url'] ?? ''); ?>" class="regular-text">
                            <button type="button" class="button" id="upload_image_btn"><?php _e('Upload Image', 'advanced-travel-crm'); ?></button>
                            <p class="description"><?php _e('Featured image for this group', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Display Type', 'advanced-travel-crm'); ?></label></th>
                        <td>
                            <select name="display_type">
                                <option value="grid" <?php selected($group['display_type'] ?? 'grid', 'grid'); ?>><?php _e('Grid', 'advanced-travel-crm'); ?></option>
                                <option value="list" <?php selected($group['display_type'] ?? 'grid', 'list'); ?>><?php _e('List', 'advanced-travel-crm'); ?></option>
                                <option value="carousel" <?php selected($group['display_type'] ?? 'grid', 'carousel'); ?>><?php _e('Carousel', 'advanced-travel-crm'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Sort Order', 'advanced-travel-crm'); ?></label></th>
                        <td>
                            <input type="number" name="sort_order" value="<?php echo esc_attr($group['sort_order'] ?? 0); ?>" class="small-text" min="0">
                            <p class="description"><?php _e('Lower numbers appear first', 'advanced-travel-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Status', 'advanced-travel-crm'); ?></label></th>
                        <td>
                            <select name="status">
                                <option value="active" <?php selected($group['status'] ?? 'active', 'active'); ?>><?php _e('Active', 'advanced-travel-crm'); ?></option>
                                <option value="inactive" <?php selected($group['status'] ?? 'active', 'inactive'); ?>><?php _e('Inactive', 'advanced-travel-crm'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php echo $is_edit ? __('Update Group', 'advanced-travel-crm') : __('Create Group', 'advanced-travel-crm'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=atc-package-groups'); ?>" class="button"><?php _e('Cancel', 'advanced-travel-crm'); ?></a>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#upload_image_btn').on('click', function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: '<?php _e('Choose Group Image', 'advanced-travel-crm'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'advanced-travel-crm'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#image_url').val(attachment.url);
                });
                
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }
    
    public static function save_group_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        check_admin_referer('atc_save_package_group');
        
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        $is_edit = $group_id > 0;
        
        $group_data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'slug' => sanitize_title($_POST['slug'] ?? ''),
            'service_key' => sanitize_text_field($_POST['service_key'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'image_url' => esc_url_raw($_POST['image_url'] ?? ''),
            'display_type' => sanitize_text_field($_POST['display_type'] ?? 'grid'),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
        ];
        
        // Ensure required fields
        if (empty($group_data['name'])) {
            wp_die(__('Group name is required', 'advanced-travel-crm'));
        }
        if (empty($group_data['slug'])) {
            $group_data['slug'] = sanitize_title($group_data['name']);
        }
        
        global $wpdb;
        
        if ($is_edit) {
            $result = $wpdb->update(
                ATC_TABLE_PACKAGE_GROUPS,
                $group_data,
                ['id' => $group_id],
                null,
                ['%d']
            );
            
            if ($result === false && !empty($wpdb->last_error)) {
                wp_die(__('Failed to update group. Error: ' . esc_html($wpdb->last_error), 'advanced-travel-crm'));
            }
            $message = 'updated';
        } else {
            $group_data['group_id'] = 'GRP-' . date('Ymd') . '-' . wp_rand(1000, 9999);
            $group_data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(ATC_TABLE_PACKAGE_GROUPS, $group_data);
            
            if ($result === false) {
                $error_msg = !empty($wpdb->last_error) ? $wpdb->last_error : 'Unknown database error';
                wp_die(__('Failed to create group. Error: ' . esc_html($error_msg), 'advanced-travel-crm'));
            }
            $message = 'created';
        }
        
        wp_redirect(add_query_arg('atc_notice', $message, admin_url('admin.php?page=atc-package-groups')));
        exit;
    }
    
    public static function delete_group_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        $id = intval($_GET['id'] ?? 0);
        check_admin_referer('delete_group_' . $id);
        
        global $wpdb;
        $wpdb->delete(ATC_TABLE_PACKAGE_GROUPS, ['id' => $id], ['%d']);
        
        wp_redirect(add_query_arg('atc_notice', 'deleted', admin_url('admin.php?page=atc-package-groups')));
        exit;
    }
    
    public static function package_group_shortcode($atts) {
        $atts = shortcode_atts([
            'group_id' => '',
            'slug' => '',
            'columns' => 3,
            'limit' => 6,  // Default limit for initial display
            'show_view_all' => 'true',
        ], $atts);
        
        global $wpdb;
        
        // Get group
        if (!empty($atts['group_id'])) {
            $group = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_PACKAGE_GROUPS . " WHERE group_id = %s AND status = 'active'",
                $atts['group_id']
            ), ARRAY_A);
        } elseif (!empty($atts['slug'])) {
            $group = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_PACKAGE_GROUPS . " WHERE slug = %s AND status = 'active'",
                $atts['slug']
            ), ARRAY_A);
        } else {
            return '<p class="atc-error">Group ID or slug is required.</p>';
        }
        
        if (!$group) {
            return '<p class="atc-error">Package group not found.</p>';
        }
        
        // Handle "View All" state via URL parameters
        $view_all_id = isset($_GET['atc_group_view_all']) ? sanitize_text_field($_GET['atc_group_view_all']) : '';
        $is_view_all = ($view_all_id == $group['id'] || $view_all_id == $group['slug']);
        
        $limit = intval($atts['limit']);
        if ($is_view_all) {
            $limit = 1000; // High limit to show all
        }
        
        // Get packages in this group using the relationship table
        $packages = $wpdb->get_results($wpdb->prepare("
            SELECT p.* 
            FROM " . ATC_TABLE_CUSTOM_PACKAGES . " p
            INNER JOIN " . ATC_TABLE_PACKAGE_GROUP_RELATIONS . " r ON p.id = r.package_id
            WHERE r.group_id = %d AND p.status = 'active'
            ORDER BY r.sort_order ASC, p.created_at DESC
            LIMIT %d",
            $group['id'],
            $limit
        ), ARRAY_A);
        
        // Get total count for view all button
        $total_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM " . ATC_TABLE_CUSTOM_PACKAGES . " p
            INNER JOIN " . ATC_TABLE_PACKAGE_GROUP_RELATIONS . " r ON p.id = r.package_id
            WHERE r.group_id = %d AND p.status = 'active'",
            $group['id']
        ));
        
        ob_start();
        ?>
        <div class="atc-package-group" data-group-id="<?php echo esc_attr($group['id']); ?>">
            <div class="atc-package-group-header">
                <h2><?php echo esc_html($group['name']); ?></h2>
                <?php if (!empty($group['description'])): ?>
                    <p><?php echo esc_html($group['description']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="atc-package-group-content atc-display-<?php echo esc_attr($group['display_type']); ?>">
                <div class="atc-packages-grid atc-packages-grid-<?php echo esc_attr($atts['columns']); ?>-cols">
                    <?php if (empty($packages)): ?>
                        <p><?php _e('No packages found in this group.', 'advanced-travel-crm'); ?></p>
                    <?php else: ?>
                        <?php foreach ($packages as $package): ?>
                            <div class="atc-package-item">
                                <?php if (!empty($package['image_url'])): ?>
                                    <div class="atc-package-image">
                                        <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="atc-package-content">
                                    <h3 class="atc-package-title">
                                        <a href="<?php echo esc_url(get_permalink() . '?package_id=' . $package['package_id']); ?>">
                                            <?php echo esc_html($package['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if (!empty($package['short_description'])): ?>
                                        <p class="atc-package-description"><?php echo esc_html($package['short_description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="atc-package-meta">
                                        <?php if (!empty($package['duration_days'])): ?>
                                            <span class="atc-package-duration"><?php echo esc_html($package['duration_days']); ?> Days</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($package['destination'])): ?>
                                            <span class="atc-package-destination"><?php echo esc_html($package['destination']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="atc-package-price">
                                        <?php if ($package['original_price'] > $package['price']): ?>
                                            <span class="atc-package-original-price"><?php echo get_option('atc_currency_symbol', '₹'); ?><?php echo number_format($package['original_price']); ?></span>
                                        <?php endif; ?>
                                        <span class="atc-package-current-price"><?php echo get_option('atc_currency_symbol', '₹'); ?><?php echo number_format($package['price']); ?></span>
                                    </div>
                                    
                                    <div class="atc-package-actions">
                                        <a href="<?php echo esc_url(get_permalink() . '?package_id=' . $package['package_id']); ?>" 
                                           class="atc-package-details-btn button">
                                            <?php _e('View Details', 'advanced-travel-crm'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (!$is_view_all && $atts['show_view_all'] === 'true' && $total_count > $limit): ?>
                    <div class="atc-view-all-container">
                        <a href="<?php echo esc_url(add_query_arg(['atc_group_view_all' => $group['id']], get_permalink())); ?>" 
                           class="atc-view-all-btn button button-primary">
                            <?php _e('View All Packages', 'advanced-travel-crm'); ?> (<?php echo $total_count; ?>)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .atc-package-group {
            margin: 30px 0;
            padding: 30px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .atc-package-group-header h2 {
            margin-top: 0;
            color: #1a1a1a;
            font-size: 28px;
            font-weight: 700;
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }
        
        .atc-package-group-header h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #0073aa, #00a0d2);
            border-radius: 2px;
            margin-top: 8px;
        }
        
        .atc-package-group-header p {
            color: #666;
            font-size: 16px;
            margin-bottom: 25px;
        }
        
        .atc-packages-grid {
            display: grid;
            gap: 25px;
            margin: 25px 0;
        }
        
        /* Responsive Grid System */
        .atc-packages-grid-2-cols { grid-template-columns: repeat(2, 1fr); }
        .atc-packages-grid-3-cols { grid-template-columns: repeat(3, 1fr); }
        .atc-packages-grid-4-cols { grid-template-columns: repeat(4, 1fr); }
        
        .atc-package-item {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eee;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            transition: transform 0.3s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .atc-package-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: #ddd;
        }
        
        .atc-package-image {
            position: relative;
            height: 220px;
            overflow: hidden;
        }
        
        .atc-package-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .atc-package-item:hover .atc-package-image img {
            transform: scale(1.05);
        }
        
        .atc-package-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .atc-package-title {
            margin: 0 0 10px 0;
            font-size: 18px;
            line-height: 1.4;
        }
        
        .atc-package-title a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 700;
            transition: color 0.2s;
        }
        
        .atc-package-title a:hover {
            color: #0073aa;
        }
        
        .atc-package-description {
            margin: 0 0 15px 0;
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .atc-package-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .atc-package-duration::before { content: '⏱️ '; }
        .atc-package-destination::before { content: '📍 '; }
        
        .atc-package-price {
            margin-top: auto;
            margin-bottom: 20px;
            display: flex;
            align-items: baseline;
            gap: 10px;
        }
        
        .atc-package-current-price {
            font-size: 22px;
            font-weight: 800;
            color: #2c3e50;
        }
        
        .atc-package-original-price {
            text-decoration: line-through;
            color: #aeb5bc;
            font-size: 14px;
        }
        
        .atc-package-actions {
            margin-top: 0;
        }
        
        .atc-package-details-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e9ecef;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .atc-package-details-btn:hover {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        
        /* View All Button */
        .atc-view-all-container {
            text-align: center;
            margin-top: 40px;
        }
        
        .atc-view-all-btn {
            background: transparent;
            color: #0073aa;
            padding: 12px 30px;
            border: 2px solid #0073aa;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .atc-view-all-btn:hover {
            background: #0073aa;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,115,170,0.3);
        }
        
        /* Mobile Optimization */
        @media (max-width: 991px) {
            .atc-packages-grid-4-cols { grid-template-columns: repeat(2, 1fr); }
            .atc-packages-grid-3-cols { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 600px) {
            .atc-package-group { padding: 20px 15px; }
            .atc-packages-grid-2-cols,
            .atc-packages-grid-3-cols,
            .atc-packages-grid-4-cols {
                grid-template-columns: 1fr;
            }
            .atc-package-image { height: 180px; }
            .atc-package-current-price { font-size: 20px; }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get packages by group ID for AJAX requests
     */
    public static function get_packages_by_group($group_id, $limit = 10, $offset = 0) {
        global $wpdb;
        
        $packages = $wpdb->get_results($wpdb->prepare("
            SELECT p.* 
            FROM " . ATC_TABLE_CUSTOM_PACKAGES . " p
            INNER JOIN " . ATC_TABLE_PACKAGE_GROUP_RELATIONS . " r ON p.id = r.package_id
            WHERE r.group_id = %d AND p.status = 'active'
            ORDER BY r.sort_order ASC, p.created_at DESC
            LIMIT %d OFFSET %d",
            $group_id,
            $limit,
            $offset
        ), ARRAY_A);
        
        return $packages;
    }
    
    /**
     * Get all packages in a group with pagination support
     */
    public static function get_all_packages_in_group($group_id, $limit = -1) {
        global $wpdb;
        
        $query = "
            SELECT p.* 
            FROM " . ATC_TABLE_CUSTOM_PACKAGES . " p
            INNER JOIN " . ATC_TABLE_PACKAGE_GROUP_RELATIONS . " r ON p.id = r.package_id
            WHERE r.group_id = %d AND p.status = 'active'
            ORDER BY r.sort_order ASC, p.created_at DESC";
            
        if ($limit > 0) {
            $query .= " LIMIT " . intval($limit);
        }
        
        $packages = $wpdb->get_results($wpdb->prepare($query, $group_id), ARRAY_A);
        
        return $packages;
    }
    
    /**
     * Get total package count in a group
     */
    public static function get_group_package_count($group_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM " . ATC_TABLE_CUSTOM_PACKAGES . " p
            INNER JOIN " . ATC_TABLE_PACKAGE_GROUP_RELATIONS . " r ON p.id = r.package_id
            WHERE r.group_id = %d AND p.status = 'active'",
            $group_id
        ));
        
        return intval($count);
    }
    
    /**
     * Assign a package to multiple groups
     */
    public static function assign_package_to_groups($package_id, $group_ids) {
        global $wpdb;
        
        // First, remove all existing relationships for this package
        $wpdb->delete(ATC_TABLE_PACKAGE_GROUP_RELATIONS, ['package_id' => $package_id]);
        
        // Then add new relationships
        foreach ($group_ids as $group_id) {
            $wpdb->insert(ATC_TABLE_PACKAGE_GROUP_RELATIONS, [
                'package_id' => $package_id,
                'group_id' => $group_id,
                'sort_order' => 0,
                'created_at' => current_time('mysql')
            ]);
        }
        
        return true;
    }
    
    /**
     * Get groups that a package belongs to
     */
    public static function get_package_groups($package_id) {
        global $wpdb;
        
        $groups = $wpdb->get_results($wpdb->prepare("
            SELECT g.* 
            FROM " . ATC_TABLE_PACKAGE_GROUPS . " g
            INNER JOIN " . ATC_TABLE_PACKAGE_GROUP_RELATIONS . " r ON g.id = r.group_id
            WHERE r.package_id = %d AND g.status = 'active'
            ORDER BY r.sort_order ASC",
            $package_id
        ), ARRAY_A);
        
        return $groups;
    }
}

