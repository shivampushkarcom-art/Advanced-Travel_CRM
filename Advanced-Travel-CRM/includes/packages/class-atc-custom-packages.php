<?php
/**
 * ATC Custom Packages Management
 * Admin interface for creating and managing custom packages
 */

if (!defined('ABSPATH')) exit;

class ATC_Custom_Packages {
    
    private static $initialized = false;
    
    public static function init() {
        // Prevent double initialization
        if (self::$initialized) {
            return;
        }
        
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 20); // ✅ FIXED: Register after parent menu
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('admin_post_atc_save_package', [__CLASS__, 'save_package_handler']);
        add_action('admin_post_atc_delete_package', [__CLASS__, 'delete_package_handler']);
        add_action('admin_post_atc_fix_packages_table', [__CLASS__, 'fix_packages_table_handler']);
        
        // Register shortcodes for displaying packages
        add_shortcode('atc_packages_by_category', [__CLASS__, 'packages_by_category_shortcode']);
        add_shortcode('atc_featured_packages', [__CLASS__, 'featured_packages_shortcode']);
        
        // Run migration on init (admin only)
        if (is_admin() && class_exists('ATC_Installer') && method_exists('ATC_Installer', 'migrate_custom_packages_table')) {
            ATC_Installer::migrate_custom_packages_table();
        }
        
        self::$initialized = true;
    }
    
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Custom Packages', 'advanced-travel-crm'),
            __('Custom Packages', 'advanced-travel-crm'),
            'manage_options',
            'atc-custom-packages',
            [__CLASS__, 'packages_page']
        );
    }
    
    public static function register_routes() {
        register_rest_route('atc/v1', '/packages', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_packages'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('atc/v1', '/packages', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_package'],
            'permission_callback' => [__CLASS__, 'check_permission'],
        ]);
    }
    
    public static function check_permission() {
        return current_user_can('manage_options');
    }
    
    public static function get_packages($request) {
        global $wpdb;
        
        $service = sanitize_text_field($request['service'] ?? '');
        $status = sanitize_text_field($request['status'] ?? 'active');
        
        $where = ["status = %s"];
        $params = [$status];
        
        if (!empty($service)) {
            $where[] = "service_key = %s";
            $params[] = $service;
        }
        
        $sql = "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY featured DESC, created_at DESC";
        
        $packages = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        return rest_ensure_response([
            'success' => true,
            'packages' => $packages,
            'count' => count($packages),
        ]);
    }
    
    public static function create_package($request) {
        $params = $request->get_json_params();
        
        $package_data = [
            'service_key' => sanitize_text_field($params['service_key'] ?? ''),
            'name' => sanitize_text_field($params['name'] ?? ''),
            'description' => wp_kses_post($params['description'] ?? ''),
            'destination' => sanitize_text_field($params['destination'] ?? ''),
            'price' => floatval($params['price'] ?? 0),
            'original_price' => floatval($params['original_price'] ?? 0),
            'duration_days' => intval($params['duration_days'] ?? 1),
            'adults' => intval($params['adults'] ?? 1),
            'children' => intval($params['children'] ?? 0),
            'featured' => isset($params['featured']) ? 1 : 0,
            'status' => sanitize_text_field($params['status'] ?? 'active'),
            'image_url' => esc_url_raw($params['image_url'] ?? ''),
            'images' => !empty($params['images']) ? json_encode($params['images']) : null,
            'features' => !empty($params['features']) ? implode(',', array_map('sanitize_text_field', $params['features'])) : null,
            'inclusions' => !empty($params['inclusions']) ? implode(',', array_map('sanitize_text_field', $params['inclusions'])) : null,
            'exclusions' => !empty($params['exclusions']) ? implode(',', array_map('sanitize_text_field', $params['exclusions'])) : null,
            'itinerary' => !empty($params['itinerary']) ? json_encode($params['itinerary']) : null,
            'terms_conditions' => wp_kses_post($params['terms_conditions'] ?? ''),
            'cancellation_policy' => wp_kses_post($params['cancellation_policy'] ?? ''),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ];
        
        global $wpdb;
        $result = $wpdb->insert(ATC_TABLE_CUSTOM_PACKAGES, $package_data);
        
        if ($result) {
            $insert_id = $wpdb->insert_id;
            // Set package_id to numeric id (update after insert)
            $wpdb->update(
                ATC_TABLE_CUSTOM_PACKAGES,
                ['package_id' => (string)$insert_id],
                ['id' => $insert_id],
                ['%s'],
                ['%d']
            );
            
            return rest_ensure_response([
                'success' => true,
                'package_id' => (string)$insert_id,
                'id' => $insert_id,
                'message' => __('Package created successfully', 'advanced-travel-crm'),
            ]);
        }
        
        return new WP_Error('package_creation_failed', __('Failed to create package', 'advanced-travel-crm'), ['status' => 500]);
    }
    
    public static function packages_page() {
        // Show admin notices
        if (isset($_GET['atc_notice'])) {
            $notice = sanitize_text_field($_GET['atc_notice']);
            if ($notice === 'created') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Package created successfully!', 'advanced-travel-crm') . '</p></div>';
            } elseif ($notice === 'updated') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Package updated successfully!', 'advanced-travel-crm') . '</p></div>';
            } elseif ($notice === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Package deleted successfully!', 'advanced-travel-crm') . '</p></div>';
            }
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            self::edit_package_page();
            return;
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'add') {
            self::add_package_page();
            return;
        }
        
        self::list_packages_page();
    }
    
    public static function list_packages_page() {
        global $wpdb;
        
        // Check and fix table structure if needed
        if (isset($_GET['fix_table']) && $_GET['fix_table'] === '1') {
            if (class_exists('ATC_Installer') && method_exists('ATC_Installer', 'migrate_custom_packages_table')) {
                ATC_Installer::migrate_custom_packages_table();
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Table structure updated successfully!', 'advanced-travel-crm') . '</p></div>';
            }
        }
        
        // Check and migrate package_ids if needed
        if (isset($_GET['migrate_package_ids']) && $_GET['migrate_package_ids'] === '1') {
            if (class_exists('ATC_Installer') && method_exists('ATC_Installer', 'migrate_package_ids')) {
                $updated_count = ATC_Installer::migrate_package_ids();
                if ($updated_count > 0) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Package ID migration completed! Updated %d packages to use numeric package IDs (1, 2, 3, etc.).', 'advanced-travel-crm'), $updated_count) . '</p></div>';
                } else {
                    echo '<div class="notice notice-info is-dismissible"><p>' . __('All packages already have numeric package IDs. No migration needed.', 'advanced-travel-crm') . '</p></div>';
                }
            }
        }
        
        // Check for packages that need migration to numeric format
        $packages_needing_migration = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . ATC_TABLE_CUSTOM_PACKAGES . " 
            WHERE package_id != CAST(id AS CHAR) OR package_id IS NULL OR package_id = ''"
        );
        
        $needs_migration_count = intval($packages_needing_migration);
        
        if ($needs_migration_count > 0) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Package ID Migration Needed', 'advanced-travel-crm') . '</strong></p>';
            echo '<p>' . sprintf(__('Some packages need to be updated with numeric package IDs (1, 2, 3, etc.). %d package(s) need migration.', 'advanced-travel-crm'), $needs_migration_count) . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=atc-custom-packages&migrate_package_ids=1') . '" class="button button-primary">' . __('Migrate Package IDs Now', 'advanced-travel-crm') . '</a></p>';
            echo '</div>';
        }
        
        // Check for table structure issues
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . ATC_TABLE_CUSTOM_PACKAGES . "'") == ATC_TABLE_CUSTOM_PACKAGES;
        $has_short_description = false;
        if ($table_exists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM " . ATC_TABLE_CUSTOM_PACKAGES);
            foreach ($columns as $column) {
                if ($column->Field === 'short_description') {
                    $has_short_description = true;
                    break;
                }
            }
        }
        
        if ($table_exists && !$has_short_description) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . __('Database Table Issue Detected', 'advanced-travel-crm') . '</strong></p>';
            echo '<p>' . __('The packages table is missing required columns. Please fix it now.', 'advanced-travel-crm') . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=atc-custom-packages&fix_table=1') . '" class="button button-primary">' . __('Fix Table Structure', 'advanced-travel-crm') . '</a></p>';
            echo '</div>';
        }
        
        // Service filter
        $service_filter = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
        $where = [];
        $params = [];
        
        if (!empty($service_filter)) {
            $where[] = "service_key = %s";
            $params[] = $service_filter;
        }
        
        $where_clause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';
        
        if (!empty($params)) {
            $packages = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " {$where_clause} ORDER BY featured DESC, created_at DESC",
                $params
            ), ARRAY_A);
        } else {
            $packages = $wpdb->get_results(
                "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " {$where_clause} ORDER BY featured DESC, created_at DESC",
                ARRAY_A
            );
        }
        
        // Get available services for filter
        if (class_exists('ATC_Services')) {
            $services = ATC_Services::get_services();
        } else {
            $services = ['tours' => ['label' => 'Tours'], 'forex' => ['label' => 'Forex'], 'visa' => ['label' => 'Visa']];
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Custom Packages', 'advanced-travel-crm'); ?>
                <a href="<?php echo admin_url('admin.php?page=atc-custom-packages&action=add'); ?>" class="page-title-action">
                    <?php _e('Add New Package', 'advanced-travel-crm'); ?>
                </a>
            </h1>
            
            <!-- Service Filter -->
            <div class="atc-filters" style="margin: 20px 0;">
                <form method="get" style="display: inline-block;">
                    <input type="hidden" name="page" value="atc-custom-packages">
                    <select name="service" style="margin-right: 10px;">
                        <option value=""><?php _e('All Services', 'advanced-travel-crm'); ?></option>
                        <?php foreach ($services as $service_key => $service): ?>
                            <?php $label = is_array($service) ? $service['label'] : $service; ?>
                            <option value="<?php echo esc_attr($service_key); ?>" <?php selected($service_filter, $service_key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button"><?php _e('Filter', 'advanced-travel-crm'); ?></button>
                    <?php if (!empty($service_filter)): ?>
                        <a href="<?php echo admin_url('admin.php?page=atc-custom-packages'); ?>" class="button">
                            <?php _e('Reset', 'advanced-travel-crm'); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php _e('ID', 'advanced-travel-crm'); ?></th>
                        <th style="width: 100px;"><?php _e('Package ID', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Name', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Service', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Destination', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Price', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Status', 'advanced-travel-crm'); ?></th>
                        <th><?php _e('Actions', 'advanced-travel-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($packages)): ?>
                        <tr>
                            <td colspan="8"><?php _e('No packages found. Create your first package!', 'advanced-travel-crm'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($packages as $package): ?>
                            <?php 
                            // Ensure package_id is numeric - use id if package_id is not numeric or empty
                            $display_package_id = (!empty($package['package_id']) && is_numeric($package['package_id'])) 
                                ? intval($package['package_id']) 
                                : intval($package['id']);
                            ?>
                            <tr>
                                <td><strong>#<?php echo esc_html($package['id']); ?></strong></td>
                                <td><strong style="color: #0073aa; font-size: 14px; font-weight: 600;"><?php echo esc_html($display_package_id); ?></strong></td>
                                <td><strong><?php echo esc_html($package['name']); ?></strong></td>
                                <td><?php echo esc_html($package['service_key']); ?></td>
                                <td><?php echo esc_html($package['destination'] ?? '-'); ?></td>
                                <td><?php echo get_option('atc_currency_symbol', '₹'); ?><?php echo number_format($package['price']); ?></td>
                                <td>
                                    <span class="atc-status-badge atc-status-<?php echo esc_attr($package['status']); ?>">
                                        <?php echo esc_html(ucfirst($package['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=atc-custom-packages&action=edit&id=' . $package['id']); ?>">
                                        <?php _e('Edit', 'advanced-travel-crm'); ?>
                                    </a> |
                                    <a href="<?php echo admin_url('admin-post.php?action=atc_delete_package&id=' . $package['id'] . '&_wpnonce=' . wp_create_nonce('delete_package_' . $package['id'])); ?>" 
                                       onclick="return confirm('Are you sure?');">
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
    
    public static function add_package_page() {
        self::package_form();
    }
    
    public static function edit_package_page() {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            wp_die(__('Invalid package ID', 'advanced-travel-crm'));
        }
        
        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if (!$package) {
            wp_die(__('Package not found', 'advanced-travel-crm'));
        }
        
        self::package_form($package);
    }
    
    public static function package_form($package = null) {
        $is_edit = !empty($package);
        
        // Get current service
        $current_service = !empty($package['service_key']) ? $package['service_key'] : 'tours';
        
        // Load service-specific config
        $service_config = [];
        if (class_exists('ATC_Config_Loader')) {
            $service_config = ATC_Config_Loader::load_service_config($current_service);
        }
        
        // Get service-specific package fields
        $service_package_fields = !empty($service_config['package_fields']) ? $service_config['package_fields'] : [];
        
        // Parse existing metadata for service-specific fields
        $service_metadata = [];
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
            if (is_array($metadata)) {
                // Extract service-specific fields from metadata
                foreach ($service_package_fields as $field) {
                    $field_id = $field['id'] ?? '';
                    if (!empty($field_id) && isset($metadata[$field_id])) {
                        $service_metadata[$field_id] = $metadata[$field_id];
                    }
                }
            }
        }
        
        // Parse existing data
        $gallery_images = [];
        $day_wise_itinerary = [];
        $highlights = [];
        $tags = [];
        $activities = [];
        $metadata = [];
        $query_prefilled = [];
        $query_custom_message = '';
        $query_form_title = '';
        $query_form_subtitle = '';
        $faq_items = [];
        $map_latitude = '';
        $map_longitude = '';
        $map_address = '';
        $map_embed_url = '';
        $show_map = false;
        
        if ($is_edit) {
            if (!empty($package['gallery_images'])) {
                $gallery_images = is_string($package['gallery_images']) ? json_decode($package['gallery_images'], true) : $package['gallery_images'];
                if (!is_array($gallery_images)) $gallery_images = [];
            }
            if (!empty($package['day_wise_itinerary'])) {
                $day_wise_itinerary = is_string($package['day_wise_itinerary']) ? json_decode($package['day_wise_itinerary'], true) : $package['day_wise_itinerary'];
                if (!is_array($day_wise_itinerary)) $day_wise_itinerary = [];
            }
            if (!empty($package['highlights'])) {
                $highlights = is_string($package['highlights']) ? array_filter(explode(',', $package['highlights'])) : (is_array($package['highlights']) ? $package['highlights'] : []);
            }
            if (!empty($package['tags'])) {
                $tags = is_string($package['tags']) ? array_filter(explode(',', $package['tags'])) : (is_array($package['tags']) ? $package['tags'] : []);
            }
            if (!empty($package['activities'])) {
                $activities = is_string($package['activities']) ? array_filter(explode(',', $package['activities'])) : (is_array($package['activities']) ? $package['activities'] : []);
            }
            
            // Parse metadata for query customization, FAQ, and map location
            if (!empty($package['metadata'])) {
                $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
                if (is_array($metadata)) {
                    $query_prefilled = $metadata['query_prefilled'] ?? [];
                    $query_custom_message = $metadata['query_custom_message'] ?? '';
                    $query_form_title = $metadata['query_form_title'] ?? '';
                    $query_form_subtitle = $metadata['query_form_subtitle'] ?? '';
                    $faq_items = $metadata['faq_items'] ?? [];
                    $map_latitude = $metadata['map_latitude'] ?? '';
                    $map_longitude = $metadata['map_longitude'] ?? '';
                    $map_address = $metadata['map_address'] ?? '';
                    $map_embed_url = $metadata['map_embed_url'] ?? '';
                    $show_map = isset($metadata['show_map']) ? (bool)$metadata['show_map'] : false;
                }
            }
        }
        
        
        // Fetch Package Groups
        $available_groups = [];
        $selected_groups = [];
        if (class_exists('ATC_Package_Groups')) {
            // Mock request for all groups
            $groups_response = ATC_Package_Groups::get_groups(['status' => 'active']);
            if (!is_wp_error($groups_response)) {
                $groups_data = $groups_response->get_data();
                $available_groups = $groups_data['groups'] ?? [];
            }
            
            if ($is_edit) {
                $current_groups = ATC_Package_Groups::get_package_groups($package['id']);
                $selected_groups = wp_list_pluck($current_groups, 'id');
            }
        }
        
        // Tour package categories
        $tour_categories = [
            'Honeymoon' => 'Honeymoon',
            'Pilgrimage' => 'Pilgrimage',
            'Adventure' => 'Adventure',
            'Family' => 'Family',
            'Luxury' => 'Luxury',
            'Budget' => 'Budget',
            'Beach' => 'Beach',
            'Hill Station' => 'Hill Station',
            'Wildlife' => 'Wildlife',
            'Cultural' => 'Cultural',
            'Spiritual' => 'Spiritual',
            'Weekend Getaway' => 'Weekend Getaway',
            'International' => 'International',
            'Domestic' => 'Domestic',
            'Jungle Safari' => 'Jungle Safari',
        ];
        
        // Package types
        $package_types = [
            'Domestic' => 'Domestic',
            'International' => 'International',
            'Weekend' => 'Weekend',
            'Extended' => 'Extended',
        ];
        
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        ?>
        <script type="text/javascript">
        function atcValidatePackageForm(form) {
            // Check required fields
            const serviceKey = form.querySelector('[name="service_key"]');
            const packageName = form.querySelector('[name="name"]');
            
            if (!serviceKey || !serviceKey.value) {
                alert('<?php echo esc_js(__('Please select a service', 'advanced-travel-crm')); ?>');
                if (serviceKey) {
                    serviceKey.focus();
                    // Scroll to service field
                    serviceKey.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            
            if (!packageName || !packageName.value.trim()) {
                alert('<?php echo esc_js(__('Package name is required', 'advanced-travel-crm')); ?>');
                if (packageName) {
                    packageName.focus();
                    // Scroll to name field
                    packageName.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.textContent = '<?php echo esc_js(__('Saving...', 'advanced-travel-crm')); ?>';
                submitBtn.style.opacity = '0.6';
            }
            
            return true;
        }
        
        jQuery(document).ready(function($) {
            // Handle form submission with validation
            $('#atc-package-form').on('submit', function(e) {
                const form = this;
                console.log('Form submit event triggered');
                
                // Before validation, ensure only visible service-specific fields are required
                const selectedService = $('#service_key').val() || 'tours';
                
                // Remove required from all hidden service-specific fields
                $('.atc-tab-content.atc-tab-service-specific').each(function() {
                    const $content = $(this);
                    const contentService = $content.data('service');
                    
                    if (contentService !== selectedService || !$content.is(':visible')) {
                        // Hide and remove required from fields in this content
                        $content.find('input[required], select[required], textarea[required]').each(function() {
                            $(this).prop('required', false);
                        });
                    }
                });
                
                // Run validation
                if (!atcValidatePackageForm(form)) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Validation failed, preventing submission');
                    return false;
                }
                
                console.log('Validation passed, form will submit');
                return true;
            });
            
            // Handle submit button click - ensure validation runs
            $('#atc-package-form button[type="submit"]').on('click', function(e) {
                console.log('Submit button clicked');
                const form = $(this).closest('form')[0];
                
                // Log form details for debugging
                console.log('Form action:', $(form).attr('action'));
                console.log('Form method:', $(form).attr('method'));
                console.log('Nonce field exists:', $(form).find('[name="_wpnonce"]').length > 0);
                console.log('Service value:', $(form).find('[name="service_key"]').val());
                console.log('Name value:', $(form).find('[name="name"]').val());
                
                // Don't prevent default - let the form submit event handle it
                // The submit event will run validation
            });
        });
        </script>
        <div class="wrap">
            <h1><?php echo $is_edit ? __('Edit Package', 'advanced-travel-crm') : __('Add New Package', 'advanced-travel-crm'); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" id="atc-package-form">
                <?php wp_nonce_field('atc_save_package'); ?>
                <input type="hidden" name="action" value="atc_save_package">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="package_id" value="<?php echo esc_attr($package['id']); ?>">
                <?php endif; ?>
                
                <div class="atc-package-form-tabs">
                    <button type="button" class="atc-tab-btn active" data-tab="basic">Basic Info</button>
                    <button type="button" class="atc-tab-btn" data-tab="images">Images & Gallery</button>
                    <button type="button" class="atc-tab-btn atc-tab-service-specific" data-tab="service-fields-forex" data-service="forex" style="display: none;">Forex Details</button>
                    <button type="button" class="atc-tab-btn atc-tab-service-specific" data-tab="service-fields-visa" data-service="visa" style="display: none;">Visa Details</button>
                    <button type="button" class="atc-tab-btn atc-tab-service-specific" data-tab="service-fields-hotels" data-service="hotels" style="display: none;">Hotel Details</button>
                    <button type="button" class="atc-tab-btn atc-tab-service-specific" data-tab="service-fields-flights" data-service="flights" style="display: none;">Flight Details</button>
                    <button type="button" class="atc-tab-btn atc-tab-service-specific" data-tab="service-fields-trains" data-service="trains" style="display: none;">Train Details</button>
                    <button type="button" class="atc-tab-btn atc-tab-service-specific" data-tab="service-fields-cars" data-service="cars" style="display: none;">Car Details</button>
                    <button type="button" class="atc-tab-btn atc-tab-service-specific" data-tab="itinerary" data-service="tours">Itinerary</button>
                    <button type="button" class="atc-tab-btn" data-tab="details">Details & Features</button>
                    <button type="button" class="atc-tab-btn" data-tab="faq">FAQ</button>
                    <button type="button" class="atc-tab-btn" data-tab="map">Map Location</button>
                    <button type="button" class="atc-tab-btn atc-tab-service-specific" data-tab="categories" data-service="tours">Categories & Tags</button>
                    <button type="button" class="atc-tab-btn" data-tab="pricing">Pricing</button>
                    <button type="button" class="atc-tab-btn" data-tab="query">Query Customization</button>
                </div>
                
                <!-- Basic Info Tab -->
                <div class="atc-tab-content active" id="tab-basic">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Package Name', 'advanced-travel-crm'); ?> *</label></th>
                            <td>
                                <input type="text" name="name" value="<?php echo esc_attr($package['name'] ?? ''); ?>" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Service', 'advanced-travel-crm'); ?> *</label></th>
                            <td>
                                <select name="service_key" id="service_key" required>
                                    <option value=""><?php _e('Select Service', 'advanced-travel-crm'); ?></option>
                                    <?php
                                    // Get services from ATC_Services class
                                    if (class_exists('ATC_Services')) {
                                        $services = ATC_Services::get_services();
                                    } else {
                                        $services = ['tours' => ['label' => 'Tours'], 'hotels' => ['label' => 'Hotels'], 'flights' => ['label' => 'Flights'], 'trains' => ['label' => 'Trains'], 'cars' => ['label' => 'Cars'], 'forex' => ['label' => 'Forex'], 'visa' => ['label' => 'Visa']];
                                    }
                                    foreach ($services as $key => $service):
                                        $label = is_array($service) ? $service['label'] : $service;
                                    ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($package['service_key'] ?? '', $key); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Short Description', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <textarea name="short_description" rows="3" class="large-text"><?php echo esc_textarea($package['short_description'] ?? ''); ?></textarea>
                                <p class="description">Brief description shown in hero section (max 200 characters)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Full Description', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <?php
                                wp_editor(
                                    $package['description'] ?? '',
                                    'description',
                                    ['textarea_name' => 'description', 'textarea_rows' => 15, 'media_buttons' => true]
                                );
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Destination', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="text" name="destination" value="<?php echo esc_attr($package['destination'] ?? ''); ?>" class="regular-text" placeholder="e.g., Goa, Kerala, Switzerland">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Duration (Days)', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="number" name="duration_days" value="<?php echo esc_attr($package['duration_days'] ?? 1); ?>" min="1" class="small-text">
                                <label style="margin-left: 10px;">
                                    <?php _e('Nights:', 'advanced-travel-crm'); ?>
                                    <input type="number" name="duration_nights" value="<?php echo esc_attr($package['duration_nights'] ?? 0); ?>" min="0" class="small-text">
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Status', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <select name="status">
                                    <option value="active" <?php selected($package['status'] ?? 'active', 'active'); ?>><?php _e('Active', 'advanced-travel-crm'); ?></option>
                                    <option value="inactive" <?php selected($package['status'] ?? '', 'inactive'); ?>><?php _e('Inactive', 'advanced-travel-crm'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Featured', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="featured" value="1" <?php checked($package['featured'] ?? 0, 1); ?>>
                                    <?php _e('Mark as featured package', 'advanced-travel-crm'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Images & Gallery Tab -->
                <div class="atc-tab-content" id="tab-images">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Main Image URL', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="url" name="image_url" id="image_url" value="<?php echo esc_url($package['image_url'] ?? ''); ?>" class="regular-text">
                                <button type="button" class="button" id="upload_image_btn">Upload Image</button>
                                <div id="image_preview" style="margin-top: 10px;">
                                    <?php if (!empty($package['image_url'])): ?>
                                        <img src="<?php echo esc_url($package['image_url']); ?>" style="max-width: 300px; height: auto; border: 1px solid #ddd; padding: 5px;">
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Gallery Images', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <div id="gallery_images_container">
                                    <?php if (!empty($gallery_images)): ?>
                                        <?php foreach ($gallery_images as $index => $img): ?>
                                            <div class="gallery-image-item" style="display: inline-block; margin: 5px; position: relative;">
                                                <input type="url" name="gallery_images[]" value="<?php echo esc_url($img); ?>" class="regular-text" style="width: 300px;">
                                                <button type="button" class="button remove-gallery-image">Remove</button>
                                                <img src="<?php echo esc_url($img); ?>" style="max-width: 100px; height: auto; display: block; margin-top: 5px;">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button" id="add_gallery_image">Add Gallery Image</button>
                                <p class="description">Add multiple images for the gallery. Recommended: 6-12 images</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Service-Specific Fields Tab (Forex) -->
                <div class="atc-tab-content atc-tab-service-specific" id="tab-service-fields-forex" data-service="forex" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th colspan="2">
                                <h3><?php _e('Forex Service Details', 'advanced-travel-crm'); ?></h3>
                                <p class="description">Configure forex-specific fields for this package.</p>
                            </th>
                        </tr>
                        <?php
                        // Render forex-specific fields from config
                        if ($current_service === 'forex') {
                            foreach ($service_package_fields as $field):
                                $field_id = $field['id'] ?? '';
                                $field_label = $field['label'] ?? '';
                                $field_type = $field['type'] ?? 'text';
                                $field_placeholder = $field['placeholder'] ?? '';
                                $field_required = !empty($field['required']);
                                $field_value = $service_metadata[$field_id] ?? '';
                                
                                // Handle array values (delivery_options, required_documents)
                                if (is_array($field_value)) {
                                    $field_value = implode("\n", $field_value);
                                }
                                
                                if (empty($field_id)) continue;
                        ?>
                        <tr>
                            <th><label><?php echo esc_html($field_label); ?><?php if ($field_required): ?> *<?php endif; ?></label></th>
                            <td>
                                <?php if ($field_type === 'textarea'): ?>
                                    <textarea name="service_metadata[<?php echo esc_attr($field_id); ?>]" rows="5" class="large-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>><?php echo esc_textarea($field_value); ?></textarea>
                                <?php elseif ($field_type === 'number'): ?>
                                    <input type="number" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" step="<?php echo esc_attr($field['step'] ?? '1'); ?>" min="<?php echo esc_attr($field['min'] ?? ''); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($field_type); ?>" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php endif; ?>
                                <?php if (!empty($field['description'])): ?>
                                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        }
                        ?>
                    </table>
                </div>
                
                <!-- Service-Specific Fields Tab (Visa) -->
                <div class="atc-tab-content atc-tab-service-specific" id="tab-service-fields-visa" data-service="visa" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th colspan="2">
                                <h3><?php _e('Visa Service Details', 'advanced-travel-crm'); ?></h3>
                                <p class="description">Configure visa-specific fields for this package.</p>
                            </th>
                        </tr>
                        <?php
                        // Load visa config for fields
                        $visa_config = [];
                        if (class_exists('ATC_Config_Loader')) {
                            $visa_config = ATC_Config_Loader::load_service_config('visa');
                        }
                        $visa_package_fields = !empty($visa_config['package_fields']) ? $visa_config['package_fields'] : [];
                        
                        // Parse visa metadata
                        $visa_metadata = [];
                        if (!empty($package['metadata'])) {
                            $all_metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
                            if (is_array($all_metadata)) {
                                foreach ($visa_package_fields as $field) {
                                    $field_id = $field['id'] ?? '';
                                    if (!empty($field_id) && isset($all_metadata[$field_id])) {
                                        $visa_metadata[$field_id] = $all_metadata[$field_id];
                                    }
                                }
                            }
                        }
                        
                        // Render visa-specific fields from config
                        foreach ($visa_package_fields as $field):
                            $field_id = $field['id'] ?? '';
                            $field_label = $field['label'] ?? '';
                            $field_type = $field['type'] ?? 'text';
                            $field_placeholder = $field['placeholder'] ?? '';
                            $field_required = !empty($field['required']);
                            $field_value = $visa_metadata[$field_id] ?? '';
                            
                            // Handle array values (document_checklist)
                            if (is_array($field_value)) {
                                $field_value = implode("\n", $field_value);
                            }
                            
                            // Handle select fields
                            $field_options = $field['options'] ?? [];
                            
                            if (empty($field_id)) continue;
                        ?>
                        <tr>
                            <th><label><?php echo esc_html($field_label); ?><?php if ($field_required): ?> *<?php endif; ?></label></th>
                            <td>
                                <?php if ($field_type === 'textarea'): ?>
                                    <textarea name="service_metadata[<?php echo esc_attr($field_id); ?>]" rows="5" class="large-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>><?php echo esc_textarea($field_value); ?></textarea>
                                <?php elseif ($field_type === 'select' && !empty($field_options)): ?>
                                    <select name="service_metadata[<?php echo esc_attr($field_id); ?>]" class="regular-text" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                        <option value=""><?php _e('Select', 'advanced-travel-crm'); ?></option>
                                        <?php foreach ($field_options as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>" <?php selected($field_value, $option); ?>>
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field_type === 'number'): ?>
                                    <input type="number" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" step="<?php echo esc_attr($field['step'] ?? '1'); ?>" min="<?php echo esc_attr($field['min'] ?? ''); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($field_type); ?>" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php endif; ?>
                                <?php if (!empty($field['description'])): ?>
                                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <!-- Service-Specific Fields Tab (Hotels) -->
                <div class="atc-tab-content atc-tab-service-specific" id="tab-service-fields-hotels" data-service="hotels" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th colspan="2">
                                <h3><?php _e('Hotel Service Details', 'advanced-travel-crm'); ?></h3>
                                <p class="description">Configure hotel-specific fields for this package.</p>
                            </th>
                        </tr>
                        <?php
                        // Load hotels config for fields
                        $hotels_config = [];
                        if (class_exists('ATC_Config_Loader')) {
                            $hotels_config = ATC_Config_Loader::load_service_config('hotels');
                        }
                        $hotels_package_fields = !empty($hotels_config['package_fields']) ? $hotels_config['package_fields'] : [];
                        
                        // Parse hotels metadata
                        $hotels_metadata = [];
                        if (!empty($package['metadata'])) {
                            $all_metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
                            if (is_array($all_metadata)) {
                                foreach ($hotels_package_fields as $field) {
                                    $field_id = $field['id'] ?? '';
                                    if (!empty($field_id) && isset($all_metadata[$field_id])) {
                                        $hotels_metadata[$field_id] = $all_metadata[$field_id];
                                    }
                                }
                            }
                        }
                        
                        // Render hotels-specific fields from config
                        foreach ($hotels_package_fields as $field):
                            $field_id = $field['id'] ?? '';
                            $field_label = $field['label'] ?? '';
                            $field_type = $field['type'] ?? 'text';
                            $field_placeholder = $field['placeholder'] ?? '';
                            $field_required = !empty($field['required']);
                            $field_value = $hotels_metadata[$field_id] ?? '';
                            
                            // Handle array values (room_types, amenities)
                            if (is_array($field_value)) {
                                $field_value = implode("\n", $field_value);
                            }
                            
                            // Handle select fields
                            $field_options = $field['options'] ?? [];
                            
                            if (empty($field_id)) continue;
                        ?>
                        <tr>
                            <th><label><?php echo esc_html($field_label); ?><?php if ($field_required): ?> *<?php endif; ?></label></th>
                            <td>
                                <?php if ($field_type === 'textarea'): ?>
                                    <textarea name="service_metadata[<?php echo esc_attr($field_id); ?>]" rows="5" class="large-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>><?php echo esc_textarea($field_value); ?></textarea>
                                <?php elseif ($field_type === 'select' && !empty($field_options)): ?>
                                    <select name="service_metadata[<?php echo esc_attr($field_id); ?>]" class="regular-text" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                        <option value=""><?php _e('Select', 'advanced-travel-crm'); ?></option>
                                        <?php foreach ($field_options as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>" <?php selected($field_value, $option); ?>>
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field_type === 'number'): ?>
                                    <input type="number" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" step="<?php echo esc_attr($field['step'] ?? '1'); ?>" min="<?php echo esc_attr($field['min'] ?? ''); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($field_type); ?>" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php endif; ?>
                                <?php if (!empty($field['description'])): ?>
                                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <!-- Service-Specific Fields Tab (Flights) -->
                <div class="atc-tab-content atc-tab-service-specific" id="tab-service-fields-flights" data-service="flights" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th colspan="2">
                                <h3><?php _e('Flight Service Details', 'advanced-travel-crm'); ?></h3>
                                <p class="description">Configure flight-specific fields for this package.</p>
                            </th>
                        </tr>
                        <?php
                        // Load flights config for fields
                        $flights_config = [];
                        if (class_exists('ATC_Config_Loader')) {
                            $flights_config = ATC_Config_Loader::load_service_config('flights');
                        }
                        $flights_package_fields = !empty($flights_config['package_fields']) ? $flights_config['package_fields'] : [];
                        
                        // Parse flights metadata
                        $flights_metadata = [];
                        if (!empty($package['metadata'])) {
                            $all_metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
                            if (is_array($all_metadata)) {
                                foreach ($flights_package_fields as $field) {
                                    $field_id = $field['id'] ?? '';
                                    if (!empty($field_id) && isset($all_metadata[$field_id])) {
                                        $flights_metadata[$field_id] = $all_metadata[$field_id];
                                    }
                                }
                            }
                        }
                        
                        // Render flights-specific fields from config
                        foreach ($flights_package_fields as $field):
                            $field_id = $field['id'] ?? '';
                            $field_label = $field['label'] ?? '';
                            $field_type = $field['type'] ?? 'text';
                            $field_placeholder = $field['placeholder'] ?? '';
                            $field_required = !empty($field['required']);
                            $field_value = $flights_metadata[$field_id] ?? '';
                            
                            // Handle array values (if any)
                            if (is_array($field_value)) {
                                $field_value = implode("\n", $field_value);
                            }
                            
                            // Handle select fields
                            $field_options = $field['options'] ?? [];
                            
                            if (empty($field_id)) continue;
                        ?>
                        <tr>
                            <th><label><?php echo esc_html($field_label); ?><?php if ($field_required): ?> *<?php endif; ?></label></th>
                            <td>
                                <?php if ($field_type === 'textarea'): ?>
                                    <textarea name="service_metadata[<?php echo esc_attr($field_id); ?>]" rows="5" class="large-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>><?php echo esc_textarea($field_value); ?></textarea>
                                <?php elseif ($field_type === 'select' && !empty($field_options)): ?>
                                    <select name="service_metadata[<?php echo esc_attr($field_id); ?>]" class="regular-text" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                        <option value=""><?php _e('Select', 'advanced-travel-crm'); ?></option>
                                        <?php foreach ($field_options as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>" <?php selected($field_value, $option); ?>>
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field_type === 'number'): ?>
                                    <input type="number" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" step="<?php echo esc_attr($field['step'] ?? '1'); ?>" min="<?php echo esc_attr($field['min'] ?? ''); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($field_type); ?>" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php endif; ?>
                                <?php if (!empty($field['description'])): ?>
                                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <!-- Train Details Tab (Trains Only) -->
                <div class="atc-tab-content atc-tab-service-specific" id="tab-service-fields-trains" data-service="trains" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th colspan="2">
                                <h3><?php _e('Train Service Details', 'advanced-travel-crm'); ?></h3>
                                <p class="description">Configure train-specific fields for this package.</p>
                            </th>
                        </tr>
                        <?php
                        // Load trains config
                        $trains_config = ATC_Config_Loader::load_service_config('trains');
                        $trains_package_fields = !empty($trains_config['package_fields']) ? $trains_config['package_fields'] : [];
                        
                        // Parse trains metadata
                        $trains_metadata = [];
                        if (!empty($package['metadata'])) {
                            $all_metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
                            if (is_array($all_metadata)) {
                                foreach ($trains_package_fields as $field) {
                                    $field_id = $field['id'] ?? '';
                                    if (!empty($field_id) && isset($all_metadata[$field_id])) {
                                        $trains_metadata[$field_id] = $all_metadata[$field_id];
                                    }
                                }
                            }
                        }
                        
                        // Render trains-specific fields from config
                        foreach ($trains_package_fields as $field):
                            $field_id = $field['id'] ?? '';
                            $field_label = $field['label'] ?? '';
                            $field_type = $field['type'] ?? 'text';
                            $field_placeholder = $field['placeholder'] ?? '';
                            $field_required = !empty($field['required']);
                            $field_value = $trains_metadata[$field_id] ?? '';
                            
                            // Handle array values (if any)
                            if (is_array($field_value)) {
                                $field_value = implode("\n", $field_value);
                            }
                            
                            // Handle select fields
                            $field_options = $field['options'] ?? [];
                            
                            if (empty($field_id)) continue;
                        ?>
                        <tr>
                            <th><label><?php echo esc_html($field_label); ?><?php if ($field_required): ?> *<?php endif; ?></label></th>
                            <td>
                                <?php if ($field_type === 'textarea'): ?>
                                    <textarea name="service_metadata[<?php echo esc_attr($field_id); ?>]" rows="5" class="large-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>><?php echo esc_textarea($field_value); ?></textarea>
                                <?php elseif ($field_type === 'select' && !empty($field_options)): ?>
                                    <select name="service_metadata[<?php echo esc_attr($field_id); ?>]" class="regular-text" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                        <option value=""><?php _e('Select', 'advanced-travel-crm'); ?></option>
                                        <?php foreach ($field_options as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>" <?php selected($field_value, $option); ?>>
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field_type === 'number'): ?>
                                    <input type="number" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" step="<?php echo esc_attr($field['step'] ?? '1'); ?>" min="<?php echo esc_attr($field['min'] ?? ''); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($field_type); ?>" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php endif; ?>
                                <?php if (!empty($field['description'])): ?>
                                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <!-- Car Details Tab (Cars Only) -->
                <div class="atc-tab-content atc-tab-service-specific" id="tab-service-fields-cars" data-service="cars" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th colspan="2">
                                <h3><?php _e('Car Rental Service Details', 'advanced-travel-crm'); ?></h3>
                                <p class="description">Configure car rental-specific fields for this package.</p>
                            </th>
                        </tr>
                        <?php
                        // Load cars config
                        $cars_config = ATC_Config_Loader::load_service_config('cars');
                        $cars_package_fields = !empty($cars_config['package_fields']) ? $cars_config['package_fields'] : [];
                        
                        // Parse cars metadata
                        $cars_metadata = [];
                        if (!empty($package['metadata'])) {
                            $all_metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
                            if (is_array($all_metadata)) {
                                foreach ($cars_package_fields as $field) {
                                    $field_id = $field['id'] ?? '';
                                    if (!empty($field_id) && isset($all_metadata[$field_id])) {
                                        $cars_metadata[$field_id] = $all_metadata[$field_id];
                                    }
                                }
                            }
                        }
                        
                        // Render cars-specific fields from config
                        foreach ($cars_package_fields as $field):
                            $field_id = $field['id'] ?? '';
                            $field_label = $field['label'] ?? '';
                            $field_type = $field['type'] ?? 'text';
                            $field_placeholder = $field['placeholder'] ?? '';
                            $field_required = !empty($field['required']);
                            $field_value = $cars_metadata[$field_id] ?? '';
                            
                            // Handle array values (if any)
                            if (is_array($field_value)) {
                                $field_value = implode("\n", $field_value);
                            }
                            
                            // Handle select fields
                            $field_options = $field['options'] ?? [];
                            
                            if (empty($field_id)) continue;
                        ?>
                        <tr>
                            <th><label><?php echo esc_html($field_label); ?><?php if ($field_required): ?> *<?php endif; ?></label></th>
                            <td>
                                <?php if ($field_type === 'textarea'): ?>
                                    <textarea name="service_metadata[<?php echo esc_attr($field_id); ?>]" rows="5" class="large-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>><?php echo esc_textarea($field_value); ?></textarea>
                                <?php elseif ($field_type === 'select' && !empty($field_options)): ?>
                                    <select name="service_metadata[<?php echo esc_attr($field_id); ?>]" class="regular-text" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                        <option value=""><?php _e('Select', 'advanced-travel-crm'); ?></option>
                                        <?php foreach ($field_options as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>" <?php selected($field_value, $option); ?>>
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field_type === 'number'): ?>
                                    <input type="number" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" step="<?php echo esc_attr($field['step'] ?? '1'); ?>" min="<?php echo esc_attr($field['min'] ?? ''); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($field_type); ?>" name="service_metadata[<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field_value); ?>" class="regular-text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php if ($field_required): ?>data-required="true"<?php endif; ?>>
                                <?php endif; ?>
                                <?php if (!empty($field['description'])): ?>
                                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <!-- Itinerary Tab (Tours Only) -->
                <div class="atc-tab-content atc-tab-service-specific" id="tab-itinerary" data-service="tours">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Day-wise Itinerary', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <div id="itinerary_days_container">
                                    <?php if (!empty($day_wise_itinerary) && is_array($day_wise_itinerary)): ?>
                                        <?php foreach ($day_wise_itinerary as $day_index => $day_data): ?>
                                            <?php
                                            $day_title = is_array($day_data) ? ($day_data['title'] ?? 'Day ' . ($day_index + 1)) : 'Day ' . ($day_index + 1);
                                            $day_content = is_array($day_data) ? ($day_data['content'] ?? '') : $day_data;
                                            $day_activities = is_array($day_data) ? ($day_data['activities'] ?? []) : [];
                                            ?>
                                            <div class="itinerary-day-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                                                <h4>Day <?php echo $day_index + 1; ?></h4>
                                                <label>Day Title:</label>
                                                <input type="text" name="itinerary[<?php echo $day_index; ?>][title]" value="<?php echo esc_attr($day_title); ?>" class="regular-text" placeholder="e.g., Day 1: Arrival in Goa">
                                                <label style="display: block; margin-top: 10px;">Day Content:</label>
                                                <textarea name="itinerary[<?php echo $day_index; ?>][content]" rows="5" class="large-text"><?php echo esc_textarea($day_content); ?></textarea>
                                                <label style="display: block; margin-top: 10px;">Activities (comma-separated):</label>
                                                <input type="text" name="itinerary[<?php echo $day_index; ?>][activities]" value="<?php echo esc_attr(implode(', ', $day_activities)); ?>" class="large-text" placeholder="e.g., Beach visit, Water sports, Sunset cruise">
                                                <button type="button" class="button remove-itinerary-day" style="margin-top: 10px;">Remove Day</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button" id="add_itinerary_day">Add Day</button>
                                <p class="description">Add detailed day-wise itinerary for the package</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Details & Features Tab -->
                <div class="atc-tab-content" id="tab-details">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Package Highlights', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <div id="highlights_container">
                                    <?php if (!empty($highlights)): ?>
                                        <?php foreach ($highlights as $highlight): ?>
                                            <div class="highlight-item" style="margin-bottom: 5px;">
                                                <input type="text" name="highlights[]" value="<?php echo esc_attr(trim($highlight)); ?>" class="regular-text" placeholder="e.g., Sunset Cruise">
                                                <button type="button" class="button remove-highlight">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button" id="add_highlight">Add Highlight</button>
                                <p class="description">Key highlights of the package (shown prominently)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Package Activities', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <div id="activities_container">
                                    <?php if (!empty($activities)): ?>
                                        <?php foreach ($activities as $activity): ?>
                                            <div class="activity-item" style="margin-bottom: 5px;">
                                                <input type="text" name="activities[]" value="<?php echo esc_attr(trim($activity)); ?>" class="regular-text" placeholder="e.g., Scuba Diving">
                                                <button type="button" class="button remove-activity">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button" id="add_activity">Add Activity</button>
                                <p class="description">Activities included in the package</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Inclusions', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <textarea name="inclusions" rows="8" class="large-text" placeholder="Enter one inclusion per line"><?php 
                                    if (!empty($package['inclusions'])) {
                                        $incs = is_string($package['inclusions']) ? explode(',', $package['inclusions']) : $package['inclusions'];
                                        echo esc_textarea(implode("\n", array_map('trim', $incs)));
                                    }
                                ?></textarea>
                                <p class="description">Enter one inclusion per line</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Exclusions', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <textarea name="exclusions" rows="8" class="large-text" placeholder="Enter one exclusion per line"><?php 
                                    if (!empty($package['exclusions'])) {
                                        $excs = is_string($package['exclusions']) ? explode(',', $package['exclusions']) : $package['exclusions'];
                                        echo esc_textarea(implode("\n", array_map('trim', $excs)));
                                    }
                                ?></textarea>
                                <p class="description">Enter one exclusion per line</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Terms & Conditions', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <?php
                                wp_editor(
                                    $package['terms_conditions'] ?? '',
                                    'terms_conditions',
                                    ['textarea_name' => 'terms_conditions', 'textarea_rows' => 8]
                                );
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Cancellation Policy', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <?php
                                wp_editor(
                                    $package['cancellation_policy'] ?? '',
                                    'cancellation_policy',
                                    ['textarea_name' => 'cancellation_policy', 'textarea_rows' => 8]
                                );
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- FAQ Tab -->
                <div class="atc-tab-content" id="tab-faq">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Frequently Asked Questions', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <p class="description">Add custom FAQ items for this package. These will be displayed on the package details page.</p>
                                <div id="faq_items_container">
                                    <?php if (!empty($faq_items) && is_array($faq_items)): ?>
                                        <?php foreach ($faq_items as $faq_index => $faq_item): ?>
                                            <?php
                                            $faq_question = is_array($faq_item) ? ($faq_item['question'] ?? '') : '';
                                            $faq_answer = is_array($faq_item) ? ($faq_item['answer'] ?? '') : '';
                                            ?>
                                            <div class="faq-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                                                <h4>FAQ Item <?php echo $faq_index + 1; ?></h4>
                                                <label style="display: block; margin-bottom: 5px;">
                                                    <strong>Question:</strong>
                                                    <input type="text" name="faq_question[]" value="<?php echo esc_attr($faq_question); ?>" class="regular-text" placeholder="e.g., What is included in the package?">
                                                </label>
                                                <label style="display: block; margin-top: 10px;">
                                                    <strong>Answer:</strong>
                                                    <textarea name="faq_answer[]" rows="4" class="large-text" placeholder="Enter the answer..."><?php echo esc_textarea($faq_answer); ?></textarea>
                                                </label>
                                                <button type="button" class="button remove-faq-item" style="margin-top: 10px;">Remove FAQ</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button" id="add_faq_item">Add FAQ Item</button>
                                <p class="description">If no FAQ items are added, default FAQ will be displayed on the package details page.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Map Location Tab -->
                <div class="atc-tab-content" id="tab-map">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Map Location', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <p class="description">Add map location details for this package. You can use coordinates, address, or embed URL.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Enable Map', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="show_map" value="1" <?php checked($show_map, true); ?>>
                                    <?php _e('Show map on package details page', 'advanced-travel-crm'); ?>
                                </label>
                                <p class="description">Check this box to display the map on the package details page.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Map Embed URL', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="url" name="map_embed_url" value="<?php echo esc_url($map_embed_url); ?>" class="large-text" placeholder="https://www.google.com/maps/embed?pb=...">
                                <p class="description">Optional: Paste a Google Maps embed URL here. If provided, this will be used instead of address/coordinates.</p>
                                <p class="description"><strong>How to get embed URL:</strong> Go to Google Maps → Search location → Share → Embed a map → Copy HTML → Extract src URL</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Map Address', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="text" name="map_address" value="<?php echo esc_attr($map_address); ?>" class="large-text" placeholder="e.g., Goa, India or Full address">
                                <p class="description">Enter the destination address. This will be used for the map if embed URL is not provided.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Latitude', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="text" name="map_latitude" value="<?php echo esc_attr($map_latitude); ?>" class="regular-text" placeholder="e.g., 15.2993">
                                <p class="description">Optional: Enter latitude coordinate for precise map location.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Longitude', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="text" name="map_longitude" value="<?php echo esc_attr($map_longitude); ?>" class="regular-text" placeholder="e.g., 74.1240">
                                <p class="description">Optional: Enter longitude coordinate for precise map location.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Categories & Tags Tab (Tours Only) -->
                <div class="atc-tab-content atc-tab-service-specific" id="tab-categories" data-service="tours">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Categories', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <?php
                                // Get current categories (comma-separated)
                                $current_categories = [];
                                if (!empty($package['category'])) {
                                    $current_categories = array_map('trim', explode(',', $package['category']));
                                }
                                ?>
                                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: #f9f9f9;">
                                    <?php foreach ($tour_categories as $key => $label): ?>
                                        <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                                            <input type="checkbox" 
                                                   name="categories[]" 
                                                   value="<?php echo esc_attr($key); ?>" 
                                                   <?php checked(in_array($key, $current_categories)); ?>
                                                   style="margin-right: 8px;">
                                            <?php echo esc_html($label); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description">Select one or more categories for this package (e.g., Honeymoon, Adventure, Family)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Package Groups', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <?php
                                $groups = [];
                                if (class_exists('ATC_Package_Groups')) {
                                    /* get_groups takes array of params. REST API callback wrapper handles request object,
                                       but the underlying logic in get_groups method seems to expect $request array/object.
                                       Wait, looking at ATC_Package_Groups::get_groups, the static method expects $request.
                                       It's better to use a direct DB query here or a new helper method in that class.
                                       Actually, the raw SQL used before was fine, but let's see if we can use the class.
                                       The previous code had:
                                       $groups = $wpdb->get_results("SELECT * FROM " . ATC_TABLE_PACKAGE_GROUPS . " WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
                                       That is fine. Let's just make sure the field name matches what we save: 'package_groups[]'.
                                    */
                                    global $wpdb;
                                    $groups = $wpdb->get_results("SELECT * FROM " . ATC_TABLE_PACKAGE_GROUPS . " WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
                                    
                                    // Get current groups for this package
                                    $current_group_ids = [];
                                    if ($is_edit && !empty($package['id'])) {
                                        $current_groups = ATC_Package_Groups::get_package_groups($package['id']);
                                        $current_group_ids = wp_list_pluck($current_groups, 'id');
                                    }
                                }
                                ?>
                                <?php if (empty($groups)): ?>
                                    <p class="description"><?php _e('No package groups found.', 'advanced-travel-crm'); ?> <a href="<?php echo admin_url('admin.php?page=atc-package-groups'); ?>" target="_blank"><?php _e('Create one', 'advanced-travel-crm'); ?></a></p>
                                <?php else: ?>
                                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                                        <?php foreach ($groups as $group): ?>
                                            <label style="display: block; margin-bottom: 5px;">
                                                <input type="checkbox" name="package_groups[]" value="<?php echo esc_attr($group['id']); ?>" <?php checked(in_array($group['id'], $current_group_ids)); ?>>
                                                <?php echo esc_html($group['name']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description"><?php _e('Select groups this package belongs to.', 'advanced-travel-crm'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                        <?php // Cleaned up duplicate block ?>
                        <tr>
                            <th><label><?php _e('Package Type', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <select name="package_type">
                                    <option value=""><?php _e('Select Type', 'advanced-travel-crm'); ?></option>
                                    <?php foreach ($package_types as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($package['package_type'] ?? '', $key); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Tags', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="text" name="tags" value="<?php echo esc_attr(implode(', ', $tags)); ?>" class="large-text" placeholder="e.g., beach, adventure, family-friendly">
                                <p class="description">Comma-separated tags for better searchability</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Activities', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="text" name="activities" value="<?php echo esc_attr(implode(', ', $activities)); ?>" class="large-text" placeholder="e.g., Scuba Diving, Trekking, Sightseeing">
                                <p class="description">Comma-separated list of activities included</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Rating', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="number" name="rating" value="<?php echo esc_attr($package['rating'] ?? 0); ?>" min="0" max="5" step="0.1" class="small-text">
                                <label style="margin-left: 10px;">
                                    <?php _e('Reviews Count:', 'advanced-travel-crm'); ?>
                                    <input type="number" name="reviews_count" value="<?php echo esc_attr($package['reviews_count'] ?? 0); ?>" min="0" class="small-text">
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Pricing Tab -->
                <div class="atc-tab-content" id="tab-pricing">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Price', 'advanced-travel-crm'); ?> *</label></th>
                            <td>
                                <input type="number" name="price" value="<?php echo esc_attr($package['price'] ?? 0); ?>" step="0.01" min="0" required class="regular-text">
                                <p class="description">Current selling price</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Original Price', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="number" name="original_price" value="<?php echo esc_attr($package['original_price'] ?? 0); ?>" step="0.01" min="0" class="regular-text">
                                <p class="description">Original price (for showing discount)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Adults', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="number" name="adults" value="<?php echo esc_attr($package['adults'] ?? 1); ?>" min="1" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Children', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="number" name="children" value="<?php echo esc_attr($package['children'] ?? 0); ?>" min="0" class="small-text">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Query Customization Tab -->
                <div class="atc-tab-content" id="tab-query">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Query Form Customization', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <p class="description">
                                    <?php _e('Customize the query form fields for this package. Customers will see these fields when asking for more details.', 'advanced-travel-crm'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Pre-filled Values', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <p class="description">
                                    <?php _e('Pre-fill certain query fields with package-specific values (e.g., destination, travel dates).', 'advanced-travel-crm'); ?>
                                </p>
                                <div id="atc-query-prefilled-fields">
                                    <?php 
                                    // Load existing prefilled values
                                    if (!empty($query_prefilled) && is_array($query_prefilled)) {
                                        foreach ($query_prefilled as $key => $value): 
                                    ?>
                                        <div class="atc-query-prefilled-item" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                                            <label style="display: block; margin-bottom: 5px;">
                                                Field Key: <input type="text" name="query_prefilled_key[]" value="<?php echo esc_attr($key); ?>" placeholder="e.g., destination" class="regular-text" style="width: 200px;">
                                            </label>
                                            <label style="display: block;">
                                                Value: <input type="text" name="query_prefilled_value[]" value="<?php echo esc_attr($value); ?>" placeholder="Value" class="regular-text" style="width: 300px;">
                                            </label>
                                            <button type="button" class="button remove-query-prefilled" style="margin-top: 5px;">Remove</button>
                                        </div>
                                    <?php 
                                        endforeach;
                                    } else {
                                        // Default fields
                                    ?>
                                        <div class="atc-query-prefilled-item" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                                            <label style="display: block; margin-bottom: 5px;">
                                                Field Key: <input type="text" name="query_prefilled_key[]" value="destination" placeholder="e.g., destination" class="regular-text" style="width: 200px;">
                                            </label>
                                            <label style="display: block;">
                                                Value: <input type="text" name="query_prefilled_value[]" value="<?php echo esc_attr($package['destination'] ?? ''); ?>" placeholder="Value" class="regular-text" style="width: 300px;">
                                            </label>
                                            <button type="button" class="button remove-query-prefilled" style="margin-top: 5px;">Remove</button>
                                        </div>
                                    <?php } ?>
                                </div>
                                <button type="button" class="button" id="atc-add-query-prefilled"><?php _e('Add Pre-filled Field', 'advanced-travel-crm'); ?></button>
                                <p class="description"><?php _e('Pre-fill query form fields with package-specific values. Key should match the field ID from service config.', 'advanced-travel-crm'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Custom Query Message', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <textarea name="query_custom_message" rows="3" class="large-text" placeholder="<?php _e('Custom message to show above the query form (optional)', 'advanced-travel-crm'); ?>"><?php echo esc_textarea($query_custom_message); ?></textarea>
                                <p class="description"><?php _e('Optional custom message to display above the query form for this package.', 'advanced-travel-crm'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Query Form Title', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <input type="text" name="query_form_title" value="<?php echo esc_attr(!empty($query_form_title) ? $query_form_title : __('Ask for More Details', 'advanced-travel-crm')); ?>" class="regular-text">
                                <p class="description"><?php _e('Custom title for the query form (default: "Ask for More Details")', 'advanced-travel-crm'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Query Form Subtitle', 'advanced-travel-crm'); ?></label></th>
                            <td>
                                <textarea name="query_form_subtitle" rows="2" class="large-text" placeholder="<?php _e('Custom subtitle for the query form (optional)', 'advanced-travel-crm'); ?>"><?php echo esc_textarea($query_form_subtitle); ?></textarea>
                                <p class="description"><?php _e('Optional subtitle to display below the title.', 'advanced-travel-crm'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php echo $is_edit ? __('Update Package', 'advanced-travel-crm') : __('Create Package', 'advanced-travel-crm'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=atc-custom-packages'); ?>" class="button">
                        <?php _e('Cancel', 'advanced-travel-crm'); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <style>
        .atc-package-form-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .atc-tab-btn {
            padding: 12px 20px;
            background: #f5f5f5;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .atc-tab-btn:hover {
            background: #e5e5e5;
        }
        .atc-tab-btn.active {
            background: white;
            border-bottom-color: #2271b1;
            color: #2271b1;
        }
        .atc-tab-content {
            display: none;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .atc-tab-content.active {
            display: block;
        }
        .gallery-image-item, .highlight-item, .itinerary-day-item {
            background: #f9f9f9;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Service-aware tab visibility
            function updateServiceSpecificTabs() {
                const selectedService = $('#service_key').val() || 'tours';
                
                // Show/hide service-specific tabs and content
                $('.atc-tab-service-specific').each(function() {
                    const $tab = $(this);
                    const tabService = $tab.data('service');
                    
                    if (tabService === selectedService) {
                        $tab.show();
                    } else {
                        $tab.hide();
                        // If this tab is active, switch to basic tab
                        if ($tab.hasClass('active')) {
                            $tab.removeClass('active');
                            $('#tab-basic').addClass('active');
                            $('.atc-tab-btn[data-tab="basic"]').addClass('active');
                        }
                    }
                });
                
                // Show/hide service-specific tab content and handle required attributes
                $('.atc-tab-content.atc-tab-service-specific').each(function() {
                    const $content = $(this);
                    const contentService = $content.data('service');
                    
                    if (contentService === selectedService) {
                        $content.show();
                        // Add required attributes to visible fields
                        $content.find('input[data-required="true"], select[data-required="true"], textarea[data-required="true"]').each(function() {
                            $(this).prop('required', true).removeAttr('data-required');
                        });
                    } else {
                        $content.hide();
                        // Remove required attributes from hidden fields
                        $content.find('input[required], select[required], textarea[required]').each(function() {
                            $(this).attr('data-required', 'true').prop('required', false);
                        });
                    }
                });
            }
            
            // Update tabs when service changes
            $('#service_key').on('change', function() {
                updateServiceSpecificTabs();
            });
            
            // Initial update
            updateServiceSpecificTabs();
            
            // Tab switching (only show visible tabs)
            $('.atc-tab-btn').on('click', function() {
                const $btn = $(this);
                // Only switch if tab is visible
                if ($btn.is(':visible')) {
                    const tab = $btn.data('tab');
                    $('.atc-tab-btn').removeClass('active');
                    $('.atc-tab-content').removeClass('active');
                    $btn.addClass('active');
                    const $content = $('#tab-' + tab);
                    $content.addClass('active').show();
                }
            });
            
            // Image upload
            $('#upload_image_btn').on('click', function(e) {
                e.preventDefault();
                const frame = wp.media({
                    title: 'Select Main Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('#image_url').val(attachment.url);
                    $('#image_preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto; border: 1px solid #ddd; padding: 5px;">');
                });
                frame.open();
            });
            
            // Add gallery image
            let galleryIndex = <?php echo count($gallery_images); ?>;
            $('#add_gallery_image').on('click', function() {
                const frame = wp.media({
                    title: 'Select Gallery Image',
                    button: { text: 'Add to gallery' },
                    multiple: false
                });
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    const html = '<div class="gallery-image-item" style="display: inline-block; margin: 5px; position: relative;">' +
                        '<input type="url" name="gallery_images[]" value="' + attachment.url + '" class="regular-text" style="width: 300px;">' +
                        '<button type="button" class="button remove-gallery-image">Remove</button>' +
                        '<img src="' + attachment.url + '" style="max-width: 100px; height: auto; display: block; margin-top: 5px;">' +
                        '</div>';
                    $('#gallery_images_container').append(html);
                });
                frame.open();
            });
            
            // Remove gallery image
            $(document).on('click', '.remove-gallery-image', function() {
                $(this).closest('.gallery-image-item').remove();
            });
            
            // Add highlight
            $('#add_highlight').on('click', function() {
                const html = '<div class="highlight-item" style="margin-bottom: 5px;">' +
                    '<input type="text" name="highlights[]" value="" class="regular-text">' +
                    '<button type="button" class="button remove-highlight">Remove</button>' +
                    '</div>';
                $('#highlights_container').append(html);
            });
            
            // Remove highlight
            $(document).on('click', '.remove-highlight', function() {
                $(this).closest('.highlight-item').remove();
            });

            // Add activity
            $('#add_activity').on('click', function() {
                const html = '<div class="activity-item" style="margin-bottom: 5px;">' +
                    '<input type="text" name="activities[]" value="" class="regular-text" placeholder="e.g., Scuba Diving">' +
                    '<button type="button" class="button remove-activity">Remove</button>' +
                    '</div>';
                $('#activities_container').append(html);
            });
            
            // Remove activity
            $(document).on('click', '.remove-activity', function() {
                $(this).closest('.activity-item').remove();
            });
            
            // Add itinerary day
            let itineraryIndex = <?php echo count($day_wise_itinerary); ?>;
            $('#add_itinerary_day').on('click', function() {
                const dayNum = itineraryIndex + 1;
                const html = '<div class="itinerary-day-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">' +
                    '<h4>Day ' + dayNum + '</h4>' +
                    '<label>Day Title:</label>' +
                    '<input type="text" name="itinerary[' + itineraryIndex + '][title]" value="Day ' + dayNum + '" class="regular-text" placeholder="e.g., Day ' + dayNum + ': Arrival">' +
                    '<label style="display: block; margin-top: 10px;">Day Content:</label>' +
                    '<textarea name="itinerary[' + itineraryIndex + '][content]" rows="5" class="large-text"></textarea>' +
                    '<label style="display: block; margin-top: 10px;">Activities (comma-separated):</label>' +
                    '<input type="text" name="itinerary[' + itineraryIndex + '][activities]" value="" class="large-text" placeholder="e.g., Beach visit, Water sports">' +
                    '<button type="button" class="button remove-itinerary-day" style="margin-top: 10px;">Remove Day</button>' +
                    '</div>';
                $('#itinerary_days_container').append(html);
                itineraryIndex++;
            });
            
            // Remove itinerary day
            $(document).on('click', '.remove-itinerary-day', function() {
                $(this).closest('.itinerary-day-item').remove();
            });
            
            // Add query prefilled field
            $('#atc-add-query-prefilled').on('click', function() {
                const html = '<div class="atc-query-prefilled-item" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">' +
                    '<label style="display: block; margin-bottom: 5px;">' +
                    'Field Key: <input type="text" name="query_prefilled_key[]" value="" placeholder="e.g., destination" class="regular-text" style="width: 200px;">' +
                    '</label>' +
                    '<label style="display: block;">' +
                    'Value: <input type="text" name="query_prefilled_value[]" value="" placeholder="Value" class="regular-text" style="width: 300px;">' +
                    '</label>' +
                    '<button type="button" class="button remove-query-prefilled" style="margin-top: 5px;">Remove</button>' +
                    '</div>';
                $('#atc-query-prefilled-fields').append(html);
            });
            
            // Remove query prefilled field
            $(document).on('click', '.remove-query-prefilled', function() {
                $(this).closest('.atc-query-prefilled-item').remove();
            });
            
            // Add FAQ item
            let faqIndex = <?php echo count($faq_items); ?>;
            $('#add_faq_item').on('click', function() {
                const faqNum = faqIndex + 1;
                const html = '<div class="faq-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">' +
                    '<h4>FAQ Item ' + faqNum + '</h4>' +
                    '<label style="display: block; margin-bottom: 5px;">' +
                    '<strong>Question:</strong>' +
                    '<input type="text" name="faq_question[]" value="" class="regular-text" placeholder="e.g., What is included in the package?">' +
                    '</label>' +
                    '<label style="display: block; margin-top: 10px;">' +
                    '<strong>Answer:</strong>' +
                    '<textarea name="faq_answer[]" rows="4" class="large-text" placeholder="Enter the answer..."></textarea>' +
                    '</label>' +
                    '<button type="button" class="button remove-faq-item" style="margin-top: 10px;">Remove FAQ</button>' +
                    '</div>';
                $('#faq_items_container').append(html);
                faqIndex++;
            });
            
            // Remove FAQ item
            $(document).on('click', '.remove-faq-item', function() {
                $(this).closest('.faq-item').remove();
            });
        });
        </script>
        <?php
    }
    
    public static function save_package_handler() {
        // Log that handler was called for debugging
        if (function_exists('error_log')) {
            error_log('ATC Package Save Handler Called');
            error_log('POST data keys: ' . implode(', ', array_keys($_POST)));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'atc_save_package')) {
            wp_die(__('Security check failed. Please try again.', 'advanced-travel-crm'));
        }
        
        check_admin_referer('atc_save_package');
        
        $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        $is_edit = $package_id > 0;
        
        // Process gallery images
        $gallery_images = [];
        if (!empty($_POST['gallery_images']) && is_array($_POST['gallery_images'])) {
            foreach ($_POST['gallery_images'] as $img) {
                $img = esc_url_raw(trim($img));
                if (!empty($img) && filter_var($img, FILTER_VALIDATE_URL)) {
                    $gallery_images[] = $img;
                }
            }
        }
        
        // Process day-wise itinerary
        $day_wise_itinerary = [];
        if (!empty($_POST['itinerary']) && is_array($_POST['itinerary'])) {
            foreach ($_POST['itinerary'] as $day_index => $day_data) {
                if (is_array($day_data) && (!empty($day_data['content']) || !empty($day_data['title']))) {
                    $day_item = [
                        'title' => sanitize_text_field($day_data['title'] ?? 'Day ' . ($day_index + 1)),
                        'content' => wp_kses_post($day_data['content'] ?? ''),
                        'activities' => !empty($day_data['activities']) ? array_filter(array_map('trim', explode(',', sanitize_text_field($day_data['activities'])))) : [],
                    ];
                    $day_wise_itinerary[] = $day_item;
                }
            }
        }
        
        // Process highlights
        $highlights = [];
        if (!empty($_POST['highlights']) && is_array($_POST['highlights'])) {
            foreach ($_POST['highlights'] as $highlight) {
                $highlight = trim(sanitize_text_field($highlight));
                if (!empty($highlight)) {
                    $highlights[] = $highlight;
                }
            }
        }
        
        // Process inclusions/exclusions (convert from newline to comma-separated)
        $inclusions_raw = !empty($_POST['inclusions']) ? sanitize_textarea_field($_POST['inclusions']) : '';
        $inclusions = !empty($inclusions_raw) ? array_filter(array_map('trim', explode("\n", $inclusions_raw))) : [];
        
        $exclusions_raw = !empty($_POST['exclusions']) ? sanitize_textarea_field($_POST['exclusions']) : '';
        $exclusions = !empty($exclusions_raw) ? array_filter(array_map('trim', explode("\n", $exclusions_raw))) : [];
        
        // Process tags and activities
        $tags_raw = !empty($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
        $tags = !empty($tags_raw) ? array_filter(array_map('trim', explode(',', $tags_raw))) : [];
        
        // Process activities
        $activities = [];
        if (!empty($_POST['activities']) && is_array($_POST['activities'])) {
            foreach ($_POST['activities'] as $activity) {
                $activity = trim(sanitize_text_field($activity));
                if (!empty($activity)) {
                    $activities[] = $activity;
                }
            }
        }
        
        $package_data = [
            'service_key' => sanitize_text_field($_POST['service_key'] ?? ''),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'short_description' => sanitize_textarea_field($_POST['short_description'] ?? ''),
            'description' => wp_kses_post($_POST['description'] ?? ''),
            'destination' => sanitize_text_field($_POST['destination'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'original_price' => floatval($_POST['original_price'] ?? 0),
            'duration_days' => intval($_POST['duration_days'] ?? 1),
            'duration_nights' => intval($_POST['duration_nights'] ?? 0),
            'adults' => intval($_POST['adults'] ?? 1),
            'children' => intval($_POST['children'] ?? 0),
            'image_url' => esc_url_raw($_POST['image_url'] ?? ''),
            'gallery_images' => !empty($gallery_images) ? json_encode($gallery_images, JSON_UNESCAPED_SLASHES) : null,
            'highlights' => !empty($highlights) ? implode(',', $highlights) : null,
            'inclusions' => !empty($inclusions) ? implode(',', $inclusions) : null,
            'exclusions' => !empty($exclusions) ? implode(',', $exclusions) : null,
            'day_wise_itinerary' => !empty($day_wise_itinerary) ? json_encode($day_wise_itinerary, JSON_UNESCAPED_SLASHES) : null,
            'terms_conditions' => wp_kses_post($_POST['terms_conditions'] ?? ''),
            'cancellation_policy' => wp_kses_post($_POST['cancellation_policy'] ?? ''),
            'refund_policy' => wp_kses_post($_POST['refund_policy'] ?? ''),
            'category' => !empty($_POST['categories']) && is_array($_POST['categories']) 
                ? implode(',', array_map('sanitize_text_field', $_POST['categories'])) 
                : sanitize_text_field($_POST['category'] ?? ''),
            'package_type' => sanitize_text_field($_POST['package_type'] ?? ''),
            'tags' => !empty($tags) ? implode(',', $tags) : null,
            'activities' => !empty($activities) ? implode(',', $activities) : null,
            'rating' => floatval($_POST['rating'] ?? 0),
            'reviews_count' => intval($_POST['reviews_count'] ?? 0),
            'featured' => isset($_POST['featured']) ? 1 : 0,
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'updated_at' => current_time('mysql'),
        ];
        
        // Handle query customization metadata
        $query_prefilled = [];
        if (!empty($_POST['query_prefilled_key']) && !empty($_POST['query_prefilled_value']) && is_array($_POST['query_prefilled_key']) && is_array($_POST['query_prefilled_value'])) {
            $keys = $_POST['query_prefilled_key'];
            $values = $_POST['query_prefilled_value'];
            foreach ($keys as $index => $key) {
                $key = sanitize_text_field(trim($key));
                $value = sanitize_text_field(trim($values[$index] ?? ''));
                if (!empty($key) && !empty($value)) {
                    $query_prefilled[$key] = $value;
                }
            }
        }
        
        // Process FAQ items
        $faq_items = [];
        if (!empty($_POST['faq_question']) && !empty($_POST['faq_answer']) && is_array($_POST['faq_question']) && is_array($_POST['faq_answer'])) {
            $questions = $_POST['faq_question'];
            $answers = $_POST['faq_answer'];
            foreach ($questions as $index => $question) {
                $question = sanitize_text_field(trim($question));
                $answer = sanitize_textarea_field(trim($answers[$index] ?? ''));
                if (!empty($question) && !empty($answer)) {
                    $faq_items[] = [
                        'question' => $question,
                        'answer' => $answer,
                    ];
                }
            }
        }
        
        // Process map location
        $map_address = sanitize_text_field($_POST['map_address'] ?? '');
        $map_latitude = sanitize_text_field($_POST['map_latitude'] ?? '');
        $map_longitude = sanitize_text_field($_POST['map_longitude'] ?? '');
        $map_embed_url = esc_url_raw($_POST['map_embed_url'] ?? '');
        $show_map = isset($_POST['show_map']) && $_POST['show_map'] === '1';
        
        // Process service-specific metadata
        $service_metadata = [];
        if (!empty($_POST['service_metadata']) && is_array($_POST['service_metadata'])) {
            foreach ($_POST['service_metadata'] as $key => $value) {
                $key = sanitize_text_field($key);
                if ($key === 'delivery_options' || $key === 'required_documents' || $key === 'document_checklist' || $key === 'room_types' || $key === 'amenities' || $key === 'baggage_allowance' || $key === 'available_classes' || $key === 'facilities' || $key === 'features') {
                    // These are textarea fields - convert newlines to array
                    $value = sanitize_textarea_field($value);
                    $service_metadata[$key] = array_filter(array_map('trim', explode("\n", $value)));
                } elseif (is_numeric($value)) {
                    $service_metadata[$key] = floatval($value);
                } else {
                    $service_metadata[$key] = sanitize_text_field($value);
                }
            }
        }
        
        // Build metadata for query customization, FAQ, map location, and service-specific fields
        $metadata = [];
        if (!empty($query_prefilled)) {
            $metadata['query_prefilled'] = $query_prefilled;
        }
        if (!empty($_POST['query_custom_message'])) {
            $metadata['query_custom_message'] = sanitize_textarea_field($_POST['query_custom_message']);
        }
        if (!empty($_POST['query_form_title'])) {
            $metadata['query_form_title'] = sanitize_text_field($_POST['query_form_title']);
        }
        if (!empty($_POST['query_form_subtitle'])) {
            $metadata['query_form_subtitle'] = sanitize_textarea_field($_POST['query_form_subtitle']);
        }
        if (!empty($faq_items)) {
            $metadata['faq_items'] = $faq_items;
        }
        // Always save map settings (even if empty) to allow disabling
        $metadata['show_map'] = $show_map;
        if (!empty($map_embed_url)) {
            $metadata['map_embed_url'] = $map_embed_url;
        }
        if (!empty($map_address)) {
            $metadata['map_address'] = $map_address;
        }
        if (!empty($map_latitude)) {
            $metadata['map_latitude'] = $map_latitude;
        }
        if (!empty($map_longitude)) {
            $metadata['map_longitude'] = $map_longitude;
        }
        
        // Merge service-specific metadata
        if (!empty($service_metadata)) {
            $metadata = array_merge($metadata, $service_metadata);
        }
        
        // Store metadata as JSON
        if (!empty($metadata)) {
            $package_data['metadata'] = json_encode($metadata, JSON_UNESCAPED_SLASHES);
        }
        
        global $wpdb;
        
        // Ensure required fields are set
        if (empty($package_data['service_key'])) {
            wp_die(__('Service is required', 'advanced-travel-crm'));
        }
        if (empty($package_data['name'])) {
            wp_die(__('Package name is required', 'advanced-travel-crm'));
        }
        
        // Set defaults for optional fields (ensure they're always set)
        if (empty($package_data['status'])) {
            $package_data['status'] = 'active';
        }
        if (!isset($package_data['price']) || $package_data['price'] === '') {
            $package_data['price'] = 0;
        }
        if (empty($package_data['duration_days'])) {
            $package_data['duration_days'] = 1;
        }
        if (empty($package_data['adults'])) {
            $package_data['adults'] = 1;
        }
        if (!isset($package_data['children'])) {
            $package_data['children'] = 0;
        }
        if (!isset($package_data['featured'])) {
            $package_data['featured'] = 0;
        }
        if (empty($package_data['duration_nights'])) {
            $package_data['duration_nights'] = 0;
        }
        
        // Before insert/update, ensure table structure is correct
        if (class_exists('ATC_Installer') && method_exists('ATC_Installer', 'migrate_custom_packages_table')) {
            ATC_Installer::migrate_custom_packages_table();
        }
        
        // Remove any columns from package_data that don't exist in the table
        $table_columns = [];
        $columns = $wpdb->get_results("SHOW COLUMNS FROM " . ATC_TABLE_CUSTOM_PACKAGES);
        foreach ($columns as $column) {
            $table_columns[$column->Field] = true;
        }
        
        // Filter package_data to only include existing columns
        $filtered_package_data = [];
        foreach ($package_data as $key => $value) {
            if (isset($table_columns[$key])) {
                $filtered_package_data[$key] = $value;
            } else {
                // Column doesn't exist, log it but don't include it
                if (function_exists('error_log')) {
                    error_log("Warning: Column '$key' does not exist in table, skipping...");
                }
            }
        }
        $package_data = $filtered_package_data;
        
        if ($is_edit) {
            // For update, let WordPress auto-detect formats (more reliable)
            $result = $wpdb->update(
                ATC_TABLE_CUSTOM_PACKAGES,
                $package_data,
                ['id' => $package_id],
                null, // Auto-detect formats
                ['%d']
            );
            
            // Check for actual errors (0 rows affected is OK if nothing changed)
            if ($result === false && !empty($wpdb->last_error)) {
                $error_msg = $wpdb->last_error;
                if (function_exists('error_log')) {
                    error_log('Package update failed: ' . $error_msg);
                    error_log('Package ID: ' . $package_id);
                    error_log('Package data keys: ' . implode(', ', array_keys($package_data)));
                    error_log('SQL: ' . $wpdb->last_query);
                }
                wp_die(
                    __('Failed to update package. Error: ' . esc_html($error_msg), 'advanced-travel-crm'),
                    __('Package Update Error', 'advanced-travel-crm'),
                    ['back_link' => true, 'response' => 200]
                );
            }
            $message = 'updated';
        } else {
            // New package - don't set package_id yet, it will be set after insert
            // Remove package_id from data if it exists
            if (isset($package_data['package_id'])) {
                unset($package_data['package_id']);
            }
            $package_data['created_by'] = get_current_user_id();
            $package_data['created_at'] = current_time('mysql');
            
            // Let WordPress auto-detect formats for insert
            $result = $wpdb->insert(ATC_TABLE_CUSTOM_PACKAGES, $package_data, null);
            
            if ($result === false) {
                $error_msg = !empty($wpdb->last_error) ? $wpdb->last_error : 'Unknown database error';
                if (function_exists('error_log')) {
                    error_log('Package creation failed: ' . $error_msg);
                    error_log('Package data keys: ' . implode(', ', array_keys($package_data)));
                    error_log('SQL: ' . $wpdb->last_query);
                }
                wp_die(
                    __('Failed to create package. Error: ' . esc_html($error_msg), 'advanced-travel-crm'),
                    __('Package Creation Error', 'advanced-travel-crm'),
                    ['back_link' => true, 'response' => 200]
                );
            }
            
            $package_id = $wpdb->insert_id;
            // Set package_id to numeric id (update after insert)
            $wpdb->update(
                ATC_TABLE_CUSTOM_PACKAGES,
                ['package_id' => (string)$package_id],
                ['id' => $package_id],
                ['%s'],
                ['%d']
            );
            
            $message = 'created';
        }
        
        // Save Package Groups
        if (class_exists('ATC_Package_Groups')) {
            $package_groups = isset($_POST['package_groups']) ? array_map('intval', $_POST['package_groups']) : [];
            ATC_Package_Groups::assign_package_to_groups($package_id, $package_groups);
        }
        
        // Clear any caches
        // Clear any caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        wp_redirect(add_query_arg('atc_notice', $message, admin_url('admin.php?page=atc-custom-packages')));
        exit;
    }
    
    public static function delete_package_handler() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'advanced-travel-crm'));
        }
        
        $id = intval($_GET['id'] ?? 0);
        check_admin_referer('delete_package_' . $id);
        
        global $wpdb;
        $wpdb->delete(ATC_TABLE_CUSTOM_PACKAGES, ['id' => $id]);
        
        wp_redirect(add_query_arg('atc_notice', 'deleted', admin_url('admin.php?page=atc-custom-packages')));
        exit;
    }
    
    /**
     * Shortcode: Display packages by category
     * Usage: [atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="12"]
     */
    public static function packages_by_category_shortcode($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'service' => '',
            'columns' => 3,
            'per_page' => 12,
            'featured_only' => 'false',
            'title' => '',
        ], $atts);
        
        if (empty($atts['category'])) {
            return '<p class="atc-error">Category is required. Usage: [atc_packages_by_category category="Honeymoon"]</p>';
        }
        
        global $wpdb;
        
        // Check for View All
        $view_all_cat = isset($_GET['atc_category_view_all']) ? sanitize_text_field($_GET['atc_category_view_all']) : '';
        $is_view_all = ($view_all_cat === $atts['category']);
        $limit = intval($atts['per_page']);
        
        if ($is_view_all) {
            $limit = 1000;
        }
        
        // Get Total Count for View All Button
        $where = ["status = 'active'", "category = %s"];
        $params = [$atts['category']];
        if (!empty($atts['service'])) {
            $where[] = "service_key = %s";
            $params[] = $atts['service'];
        }
        if ($atts['featured_only'] === 'true') {
            $where[] = "featured = 1";
        }
        $count_sql = "SELECT COUNT(*) FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE " . implode(' AND ', $where);
        $total_count = $wpdb->get_var($wpdb->prepare($count_sql, $params));
        
        // Main Query
        $sql = "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY featured DESC, created_at DESC 
                LIMIT %d";
        
        $params[] = $limit;
        
        $packages = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        if (empty($packages)) {
            return '<p class="atc-info">No packages found in this category.</p>';
        }
        
        // Format packages
        foreach ($packages as &$package) {
            $package = self::format_package_data($package);
        }
        
        ob_start();
        ?>
        <div class="atc-packages-by-category" data-category="<?php echo esc_attr($atts['category']); ?>" data-service="<?php echo esc_attr($atts['service']); ?>">
            <?php if (!empty($atts['title'])): ?>
                <h2 class="atc-packages-section-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="atc-packages-grid atc-grid-cols-<?php echo esc_attr($atts['columns']); ?>">
                <?php foreach ($packages as $package): ?>
                    <div class="atc-package-card" data-package-id="<?php echo esc_attr($package['id']); ?>">
                        <?php if (!empty($package['image_url'])): ?>
                            <div class="atc-package-image">
                                <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>">
                                <?php if (!empty($package['featured'])): ?>
                                    <span class="atc-badge atc-badge-featured">⭐ Featured</span>
                                <?php endif; ?>
                                <?php if (!empty($package['discount'])): ?>
                                    <span class="atc-badge atc-badge-discount"><?php echo esc_html($package['discount']); ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="atc-package-content">
                            <h3 class="atc-package-title"><?php echo esc_html($package['name']); ?></h3>
                            
                            <?php if (!empty($package['short_description'])): ?>
                                <p class="atc-package-description"><?php echo esc_html(wp_trim_words($package['short_description'], 20)); ?></p>
                            <?php endif; ?>
                            
                            <div class="atc-package-meta">
                                <?php if (!empty($package['destination'])): ?>
                                    <span class="atc-meta-item">📍 <?php echo esc_html($package['destination']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($package['duration_days'])): ?>
                                    <span class="atc-meta-item">⏱️ <?php echo esc_html($package['duration_days']); ?> Days</span>
                                <?php endif; ?>
                                <?php if (!empty($package['rating'])): ?>
                                    <span class="atc-meta-item">⭐ <?php echo esc_html($package['rating']); ?>/5</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="atc-package-price">
                                <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                                    <span class="atc-price-original"><?php echo esc_html(get_option('atc_currency_symbol', '₹') . number_format($package['original_price'])); ?></span>
                                <?php endif; ?>
                                <span class="atc-price-current"><?php echo esc_html(get_option('atc_currency_symbol', '₹') . number_format($package['price'])); ?></span>
                            </div>
                            
                            <div class="atc-package-actions">
                                <?php 
                                // Use numeric package_id (prefer numeric package_id if available, otherwise use id)
                                $package_identifier = (!empty($package['package_id']) && is_numeric($package['package_id'])) 
                                    ? intval($package['package_id']) 
                                    : intval($package['id']);
                                ?>
                                <a href="<?php echo esc_url(home_url('/package-details/?package_id=' . $package_identifier)); ?>" class="atc-btn atc-btn-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!$is_view_all && $total_count > $limit): ?>
                <div class="atc-view-all-container">
                    <a href="<?php echo esc_url(add_query_arg(['atc_category_view_all' => $atts['category']], get_permalink())); ?>" 
                       class="atc-view-all-btn">
                        <?php _e('View All Packages', 'advanced-travel-crm'); ?> (<?php echo $total_count; ?>)
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .atc-packages-by-category {
            margin: 30px 0;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .atc-packages-section-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #1a1a1a;
            position: relative;
            display: inline-block;
        }
        .atc-packages-section-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #0073aa, #00a0d2);
            border-radius: 2px;
            margin-top: 8px;
        }
        .atc-packages-grid {
            display: grid;
            gap: 25px;
            margin: 25px 0;
        }
        .atc-grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .atc-grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
        .atc-grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
        
        .atc-package-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            border: 1px solid #eee;
            transition: transform 0.3s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .atc-package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: #ddd;
        }
        .atc-package-image {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
        }
        .atc-package-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .atc-package-card:hover .atc-package-image img {
            transform: scale(1.05);
        }
        .atc-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            z-index: 2;
        }
        .atc-badge-featured {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .atc-badge-discount {
            background: #ff6b35;
            right: auto;
            left: 10px;
        }
        .atc-package-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .atc-package-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #2c3e50;
            line-height: 1.4;
        }
        .atc-package-description {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
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
        .atc-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .atc-package-price {
            margin-top: auto;
            margin-bottom: 20px;
            display: flex;
            align-items: baseline;
            gap: 10px;
        }
        .atc-price-original {
            text-decoration: line-through;
            color: #aeb5bc;
            font-size: 14px;
        }
        .atc-price-current {
            font-size: 22px;
            font-weight: 800;
            color: #2c3e50;
        }
        .atc-price-per {
            font-size: 13px;
            color: #6c757d;
        }
        .atc-package-actions {
            margin-top: 0;
        }
        .atc-btn {
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
            box-sizing: border-box;
        }
        .atc-btn-primary:hover {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
            transform: none;
            box-shadow: none;
        }
        
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
        
        @media (max-width: 991px) {
            .atc-grid-cols-4 { grid-template-columns: repeat(2, 1fr); }
            .atc-grid-cols-3 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .atc-packages-by-category { padding: 15px; }
            .atc-grid-cols-2, .atc-grid-cols-3, .atc-grid-cols-4 {
                grid-template-columns: 1fr;
            }
            .atc-package-image { height: 180px; }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Display featured packages
     * Usage: [atc_featured_packages service="tours" columns="3" per_page="12" title="Featured Tours"]
     */
    public static function featured_packages_shortcode($atts) {
        $atts = shortcode_atts([
            'service' => '',
            'columns' => 3,
            'per_page' => 12,
            'title' => '',
        ], $atts);
        
        global $wpdb;
        
        $where = ["status = 'active'", "featured = 1"];
        $params = [];
        
        if (!empty($atts['service'])) {
            $where[] = "service_key = %s";
            $params[] = $atts['service'];
        }
        
        $sql = "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY featured DESC, created_at DESC 
                LIMIT %d";
        
        $params[] = intval($atts['per_page']);
        
        if (!empty($params)) {
            $packages = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        } else {
            $packages = $wpdb->get_results($wpdb->prepare($sql, intval($atts['per_page'])), ARRAY_A);
        }
        
        if (empty($packages)) {
            return '<p class="atc-info">No featured packages found.</p>';
        }
        
        // Format packages
        foreach ($packages as &$package) {
            $package = self::format_package_data($package);
        }
        
        ob_start();
        ?>
        <div class="atc-featured-packages" data-service="<?php echo esc_attr($atts['service']); ?>">
            <?php if (!empty($atts['title'])): ?>
                <h2 class="atc-packages-section-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="atc-packages-grid atc-grid-cols-<?php echo esc_attr($atts['columns']); ?>">
                <?php 
                $seen_ids = [];
                $seen_names = [];
                ?>
                <?php foreach ($packages as $package): ?>
                    <?php 
                    // Deduplication logic
                    if (in_array($package['id'], $seen_ids) || in_array($package['name'], $seen_names)) {
                        continue;
                    }
                    $seen_ids[] = $package['id'];
                    $seen_names[] = $package['name'];
                    ?>
                    <div class="atc-package-card" data-package-id="<?php echo esc_attr($package['id']); ?>">
                        <?php if (!empty($package['image_url'])): ?>
                            <div class="atc-package-image">
                                <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>">
                                <span class="atc-badge atc-badge-featured">⭐ Featured</span>
                                <?php if (!empty($package['discount'])): ?>
                                    <span class="atc-badge atc-badge-discount"><?php echo esc_html($package['discount']); ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="atc-package-content">
                            <h3 class="atc-package-title"><?php echo esc_html($package['name']); ?></h3>
                            
                            <?php if (!empty($package['short_description'])): ?>
                                <p class="atc-package-description"><?php echo esc_html(wp_trim_words($package['short_description'], 20)); ?></p>
                            <?php endif; ?>
                            
                            <div class="atc-package-meta">
                                <?php if (!empty($package['destination'])): ?>
                                    <span class="atc-meta-item">📍 <?php echo esc_html($package['destination']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($package['duration_days'])): ?>
                                    <span class="atc-meta-item">📅 <?php echo esc_html($package['duration_days']); ?> Days</span>
                                <?php endif; ?>
                                <?php if (!empty($package['rating'])): ?>
                                    <span class="atc-meta-item">⭐ <?php echo esc_html($package['rating']); ?>/5</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="atc-package-price">
                                <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                                    <span class="atc-price-original"><?php echo esc_html(get_option('atc_currency_symbol', '₹') . number_format($package['original_price'])); ?></span>
                                <?php endif; ?>
                                <span class="atc-price-current"><?php echo esc_html(get_option('atc_currency_symbol', '₹') . number_format($package['price'])); ?></span>
                                <span class="atc-price-per">per person</span>
                            </div>
                            
                            <div class="atc-package-actions">
                                <?php 
                                // Use numeric package_id (prefer numeric package_id if available, otherwise use id)
                                $package_identifier = (!empty($package['package_id']) && is_numeric($package['package_id'])) 
                                    ? intval($package['package_id']) 
                                    : intval($package['id']);
                                ?>
                                <a href="<?php echo esc_url(home_url('/package-details/?package_id=' . $package_identifier)); ?>" class="atc-btn atc-btn-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Format package data for display (used by shortcodes)
     */
    public static function format_package_data($package) {
        // Parse JSON fields
        if (!empty($package['images'])) {
            $package['images'] = is_string($package['images']) ? json_decode($package['images'], true) : $package['images'];
        }
        
        if (!empty($package['gallery_images'])) {
            $package['gallery_images'] = is_string($package['gallery_images']) ? json_decode($package['gallery_images'], true) : $package['gallery_images'];
        }
        
        if (!empty($package['features'])) {
            $package['features'] = is_string($package['features']) ? explode(',', $package['features']) : $package['features'];
        }
        
        if (!empty($package['highlights'])) {
            if (is_string($package['highlights'])) {
                $package['highlights'] = array_filter(array_map('trim', explode(',', $package['highlights'])));
            } elseif (is_array($package['highlights'])) {
                $package['highlights'] = array_filter(array_map('trim', $package['highlights']));
            }
        }
        
        if (!empty($package['inclusions'])) {
            $package['inclusions'] = is_string($package['inclusions']) ? explode(',', $package['inclusions']) : $package['inclusions'];
        }
        
        if (!empty($package['exclusions'])) {
            $package['exclusions'] = is_string($package['exclusions']) ? explode(',', $package['exclusions']) : $package['exclusions'];
        }
        
        if (!empty($package['itinerary'])) {
            $package['itinerary'] = is_string($package['itinerary']) ? json_decode($package['itinerary'], true) : $package['itinerary'];
        }
        
        if (!empty($package['day_wise_itinerary'])) {
            $package['day_wise_itinerary'] = is_string($package['day_wise_itinerary']) ? json_decode($package['day_wise_itinerary'], true) : $package['day_wise_itinerary'];
        }
        
        if (!empty($package['tags'])) {
            $package['tags'] = is_string($package['tags']) ? explode(',', $package['tags']) : $package['tags'];
        }
        
        if (!empty($package['activities'])) {
            $package['activities'] = is_string($package['activities']) ? explode(',', $package['activities']) : $package['activities'];
        }
        
        // Calculate discount
        if (!empty($package['original_price']) && $package['original_price'] > $package['price']) {
            $package['discount'] = round((($package['original_price'] - $package['price']) / $package['original_price']) * 100);
        }
        
        return $package;
    }
}

