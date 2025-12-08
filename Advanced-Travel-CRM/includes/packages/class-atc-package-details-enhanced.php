<?php
/**
 * ATC Package Details - Enhanced Enterprise Version
 * MakeMyTrip-Style Package Details Pages
 * Service-Specific Themes
 */

if (!defined('ABSPATH')) exit;

class ATC_Package_Details_Enhanced {
    
    public static function init() {
        // Override default package details shortcode with enhanced version
        remove_shortcode('atc_package_details');
        add_shortcode('atc_package_details', [__CLASS__, 'package_details_shortcode']);
        add_shortcode('atc_package_details_tours', [__CLASS__, 'tours_package_details_shortcode']);
        add_shortcode('atc_package_details_forex', [__CLASS__, 'forex_package_details_shortcode']);
        add_shortcode('atc_package_details_visa', [__CLASS__, 'visa_package_details_shortcode']);
        add_shortcode('atc_package_details_hotels', [__CLASS__, 'hotels_package_details_shortcode']);
        add_shortcode('atc_package_details_flights', [__CLASS__, 'flights_package_details_shortcode']);
        add_shortcode('atc_package_details_trains', [__CLASS__, 'trains_package_details_shortcode']);
        add_shortcode('atc_package_details_cars', [__CLASS__, 'cars_package_details_shortcode']);
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets'], 20); // Higher priority to override default
    }
    
    public static function enqueue_assets() {
        // Use service ecosystem to load service-specific assets
        // Service ecosystem will handle service detection and asset loading
        if (class_exists('ATC_Service_Ecosystem')) {
            // Service ecosystem handles asset loading at priority 15
            // We load our enhanced JS here
        }
        
        // Always load tours CSS for now (service ecosystem should handle this, but fallback)
        if (is_page() || is_singular()) {
            $service = ATC_Services::detect_service_context();
            $css_file = 'atc-package-details-' . $service . '.css';
            $css_path = ATC_PLUGIN_DIR . 'assets/css/' . $css_file;
            
            // Load service-specific CSS if exists, otherwise load tours CSS
            if (file_exists($css_path)) {
                wp_enqueue_style('atc-package-details-' . $service, ATC_ASSETS_URL . 'css/' . $css_file, [], ATC_VERSION);
            } else {
                // Fallback to tours CSS
                wp_enqueue_style('atc-package-details-tours', ATC_ASSETS_URL . 'css/atc-package-details-tours.css', [], ATC_VERSION);
            }
        }
        
        // Enqueue premium booking assets (required for Book Now button)
        if (class_exists('ATC_Premium_Booking')) {
            ATC_Premium_Booking::enqueue_assets();
        }
        
        wp_enqueue_script('atc-gallery-lightbox', ATC_ASSETS_URL . 'js/atc-gallery-lightbox.js', ['jquery'], ATC_VERSION, true);
        wp_enqueue_script('atc-package-details-enhanced', ATC_ASSETS_URL . 'js/atc-package-details-enhanced.js', ['jquery', 'atc-gallery-lightbox'], ATC_VERSION, true);
        
        wp_localize_script('atc-package-details-enhanced', 'atcPackageDetails', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('atc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'currency' => get_option('atc_currency_symbol', '₹'),
        ]);
    }
    
    public static function register_routes() {
        // Support both numeric IDs and custom package codes
        register_rest_route('atc/v1', '/package/(?P<id>[a-zA-Z0-9\-]+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_package'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    public static function get_package($request) {
        $package_id_raw = sanitize_text_field($request['id']);
        
        // Package ID should be numeric (1, 2, 3, etc.)
        if (!is_numeric($package_id_raw)) {
            return new WP_Error('invalid_package_id', __('Package ID must be numeric (1, 2, 3, etc.)', 'advanced-travel-crm'), ['status' => 400]);
        }
        
        $package_id = intval($package_id_raw);
        
        global $wpdb;
        
        // Query by numeric id (package_id should match id after migration, but check both for compatibility)
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d OR package_id = %s LIMIT 1",
            $package_id,
            (string)$package_id
        ), ARRAY_A);
        
        // If not found in custom packages, try services table
        if (!$package) {
            $package = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_SERVICES . " WHERE id = %d",
                $package_id
            ), ARRAY_A);
        }
        
        if (!$package) {
            return new WP_Error('package_not_found', __('Package not found', 'advanced-travel-crm'), ['status' => 404]);
        }
        
        // Format package data
        $package = self::format_package_data($package);
        
        // Increment view count only if status is active (for frontend views)
        // Use numeric id from package array
        $numeric_id = intval($package['id']);
        if (isset($package['status']) && $package['status'] === 'active' && $numeric_id) {
            $wpdb->query($wpdb->prepare(
                "UPDATE " . ATC_TABLE_CUSTOM_PACKAGES . " SET view_count = COALESCE(view_count, 0) + 1 WHERE id = %d",
                $numeric_id
            ));
            
            // Track package view for visitor analytics
            if (!is_admin() && class_exists('ATC_Visitor_Tracker')) {
                do_action('atc_package_viewed', $numeric_id);
            }
        }
        
        return rest_ensure_response([
            'success' => true,
            'package' => $package,
        ]);
    }
    
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
            $package['highlights'] = is_string($package['highlights']) ? explode(',', $package['highlights']) : $package['highlights'];
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
        
        // Parse metadata for FAQ and map location
        $faq_items = [];
        $map_address = '';
        $map_latitude = '';
        $map_longitude = '';
        $map_embed_url = '';
        $show_map = false;
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
            if (is_array($metadata)) {
                $faq_items = $metadata['faq_items'] ?? [];
                $map_address = $metadata['map_address'] ?? '';
                $map_latitude = $metadata['map_latitude'] ?? '';
                $map_longitude = $metadata['map_longitude'] ?? '';
                $map_embed_url = $metadata['map_embed_url'] ?? '';
                $show_map = isset($metadata['show_map']) ? (bool)$metadata['show_map'] : false;
            }
        }
        $package['faq_items'] = $faq_items;
        $package['map_address'] = $map_address;
        $package['map_latitude'] = $map_latitude;
        $package['map_longitude'] = $map_longitude;
        $package['map_embed_url'] = $map_embed_url;
        $package['show_map'] = $show_map;
        
        // Calculate discount
        if (!empty($package['original_price']) && $package['original_price'] > $package['price']) {
            $package['discount'] = round((($package['original_price'] - $package['price']) / $package['original_price']) * 100);
        }
        
        return $package;
    }
    
    /**
     * Main package details shortcode (auto-detects service)
     */
    public static function package_details_shortcode($atts) {
        $atts = shortcode_atts([
            'package_id' => '',
            'service' => '',
        ], $atts);
        
        // Package ID should be numeric (1, 2, 3, etc.)
        $package_id_raw = !empty($atts['package_id']) ? $atts['package_id'] : (isset($_GET['package_id']) ? $_GET['package_id'] : '');
        $package_id_raw = sanitize_text_field($package_id_raw);
        
        if (empty($package_id_raw)) {
            return '<p class="atc-error">Package ID is required.</p>';
        }
        
        // Convert to integer (numeric package_id)
        $package_id = is_numeric($package_id_raw) ? intval($package_id_raw) : 0;
        
        if (!$package_id) {
            return '<p class="atc-error">Invalid Package ID. Package ID must be numeric (1, 2, 3, etc.).</p>';
        }
        
        $service = !empty($atts['service']) ? sanitize_text_field($atts['service']) : '';
        
        // Get package to determine service - query by numeric id
        if (empty($service)) {
            global $wpdb;
            
            // Query by numeric ID (package_id should match id after migration)
            $package = $wpdb->get_row($wpdb->prepare(
                "SELECT service_key, id, package_id FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d OR package_id = %s LIMIT 1",
                $package_id,
                (string)$package_id
            ), ARRAY_A);
            
            if ($package) {
                $service = $package['service_key'];
                // Use the numeric id for internal operations
                $package_id = intval($package['id']);
            } else {
                return '<p class="atc-error">Package not found. Package ID: ' . esc_html($package_id) . '</p>';
            }
        } else {
            // Service provided in URL - verify package exists and service matches
            global $wpdb;
            $package = $wpdb->get_row($wpdb->prepare(
                "SELECT id, service_key FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d OR package_id = %s LIMIT 1",
                $package_id,
                (string)$package_id
            ), ARRAY_A);
            
            if ($package) {
                $package_id = intval($package['id']);
                // Always use package's service_key if available (overrides URL parameter)
                if (!empty($package['service_key'])) {
                    $service = $package['service_key'];
                } elseif (empty($service)) {
                    // If package doesn't have service_key and none provided, default to tours
                    $service = 'tours';
                }
            } else {
                return '<p class="atc-error">Package not found. Package ID: ' . esc_html($package_id) . '</p>';
            }
        }
        
        // Ensure service is not empty before routing
        if (empty($service)) {
            $service = 'tours'; // Fallback default
        }
        
        // Route to service-specific template
        if ($service === 'tours') {
            return self::tours_package_details_shortcode(['package_id' => $package_id]);
        } elseif ($service === 'forex') {
            return self::forex_package_details_shortcode(['package_id' => $package_id]);
        } elseif ($service === 'visa') {
            return self::visa_package_details_shortcode(['package_id' => $package_id]);
        } elseif ($service === 'hotels') {
            return self::hotels_package_details_shortcode(['package_id' => $package_id]);
        } elseif ($service === 'flights') {
            return self::flights_package_details_shortcode(['package_id' => $package_id]);
        } elseif ($service === 'trains') {
            return self::trains_package_details_shortcode(['package_id' => $package_id]);
        } elseif ($service === 'cars') {
            return self::cars_package_details_shortcode(['package_id' => $package_id]);
        }
        
        // Default template
        return self::default_package_details_shortcode(['package_id' => $package_id]);
    }
    
    /**
     * Helper function to get package by numeric ID
     */
    private static function get_package_by_id($package_id) {
        if (empty($package_id) || !is_numeric($package_id)) {
            return null;
        }
        
        $package_id = intval($package_id);
        
        global $wpdb;
        
        // Query by numeric id (package_id should match id after migration, but check both for compatibility)
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d OR package_id = %s LIMIT 1",
            $package_id,
            (string)$package_id
        ), ARRAY_A);
        
        return $package;
    }
    
    /**
     * Tours Package Details - MakeMyTrip Style
     */
    public static function tours_package_details_shortcode($atts) {
        $atts = shortcode_atts([
            'package_id' => '',
        ], $atts);
        
        // Package ID should be numeric (1, 2, 3, etc.)
        $package_id_raw = !empty($atts['package_id']) ? $atts['package_id'] : (isset($_GET['package_id']) ? $_GET['package_id'] : '');
        
        if (empty($package_id_raw)) {
            return '<p class="atc-error">Package ID is required.</p>';
        }
        
        // Convert to integer (numeric package_id)
        if (!is_numeric($package_id_raw)) {
            return '<p class="atc-error">Invalid Package ID. Package ID must be numeric (1, 2, 3, etc.).</p>';
        }
        
        $package_id = intval($package_id_raw);
        
        // Get package using helper function
        $package = self::get_package_by_id($package_id);
        
        if (!$package) {
            return '<p class="atc-error">Package not found.</p>';
        }
        
        // Use numeric id
        $package_id = intval($package['id']);
        
        if ($package) {
            $package = self::format_package_data($package);
            // Only render if status is active (for frontend) or allow all statuses for admin
            if ($package['status'] === 'active' || current_user_can('manage_options')) {
                ob_start();
                echo self::render_tours_package_details($package);
                return ob_get_clean();
            } else {
                return '<p class="atc-error">Package is not available.</p>';
            }
        }
        
        // Fallback to JS loading if package not found in database
        ob_start();
        ?>
        <div class="atc-package-details-tours">
            <div class="atc-package-details-tours-wrapper" data-package-id="<?php echo esc_attr($package_id); ?>">
                <div class="atc-tours-loading">
                    <div class="atc-tours-spinner"></div>
                    <p>Loading package details...</p>
                </div>
                
                <div class="atc-tours-content" style="display: none;">
                    <!-- Content will be loaded by JS -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Tours Package Details (MakeMyTrip Style)
     */
    public static function render_tours_package_details($package) {
        $currency = get_option('atc_currency_symbol', '₹');
        $gallery_images = !empty($package['gallery_images']) ? $package['gallery_images'] : [];
        if (empty($gallery_images) && !empty($package['images'])) {
            $gallery_images = $package['images'];
        }
        
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $share_text = urlencode($package['name'] . ' - ' . $package['short_description']);
        $share_url = urlencode($current_url);
        
        ob_start();
        ?>
        <!-- Breadcrumb Navigation -->
        <div class="atc-tours-breadcrumb">
            <div class="atc-tours-breadcrumb-inner">
                <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                <span class="atc-breadcrumb-separator">›</span>
                <a href="<?php echo esc_url(home_url('/tours')); ?>">Tours</a>
                <span class="atc-breadcrumb-separator">›</span>
                <span class="atc-breadcrumb-current"><?php echo esc_html($package['name']); ?></span>
            </div>
        </div>
        
        <!-- Hero Section -->
        <div class="atc-tours-hero">
            <?php if (!empty($package['image_url'])): ?>
                <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>" class="atc-tours-hero-image">
            <?php endif; ?>
            <div class="atc-tours-hero-overlay"></div>
            <div class="atc-tours-hero-content">
                <div class="atc-tours-hero-top">
                    <div class="atc-tours-hero-left">
                        <h1 class="atc-tours-hero-title"><?php echo esc_html($package['name']); ?></h1>
                        <?php if (!empty($package['short_description'])): ?>
                            <p class="atc-tours-hero-subtitle"><?php echo esc_html($package['short_description']); ?></p>
                        <?php endif; ?>
                        <div class="atc-tours-hero-meta">
                            <?php if (!empty($package['destination'])): ?>
                                <div class="atc-tours-hero-meta-item">
                                    <span class="atc-meta-icon">📍</span>
                                    <span><?php echo esc_html($package['destination']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($package['duration_days'])): ?>
                                <div class="atc-tours-hero-meta-item">
                                    <span class="atc-meta-icon">📅</span>
                                    <span><?php echo esc_html($package['duration_days']); ?> Days</span>
                                    <?php if (!empty($package['duration_nights'])): ?>
                                        <span><?php echo esc_html($package['duration_nights']); ?> Nights</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($package['rating'])): ?>
                                <div class="atc-tours-hero-meta-item">
                                    <span class="atc-meta-icon">⭐</span>
                                    <span><?php echo esc_html($package['rating']); ?>/5</span>
                                    <?php if (!empty($package['reviews_count'])): ?>
                                        <span>(<?php echo esc_html($package['reviews_count']); ?> reviews)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="atc-tours-hero-share">
                        <div class="atc-share-label">Share</div>
                        <div class="atc-share-buttons">
                            <a href="https://wa.me/?text=<?php echo esc_attr($share_text . ' ' . $share_url); ?>" target="_blank" class="atc-share-btn atc-share-whatsapp" title="Share on WhatsApp">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr($share_url); ?>" target="_blank" class="atc-share-btn atc-share-facebook" title="Share on Facebook">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=<?php echo esc_attr($share_text); ?>&url=<?php echo esc_attr($share_url); ?>" target="_blank" class="atc-share-btn atc-share-twitter" title="Share on Twitter">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Image Gallery -->
        <?php if (!empty($gallery_images) && is_array($gallery_images) && count($gallery_images) > 0): ?>
        <div class="atc-tours-gallery" data-gallery-images='<?php echo esc_attr(json_encode($gallery_images)); ?>'>
            <div class="atc-tours-gallery-main">
                <div class="atc-tours-gallery-primary">
                    <img src="<?php echo esc_url($gallery_images[0]); ?>" alt="Gallery Image 1" id="atc-gallery-main" data-index="0" class="atc-gallery-image">
                    <div class="atc-tours-gallery-view-all" data-action="open-lightbox">
                        <span>View All Photos</span>
                        <span class="atc-gallery-count">(<?php echo count($gallery_images); ?>)</span>
                    </div>
                </div>
                <div class="atc-tours-gallery-secondary">
                    <?php if (isset($gallery_images[1])): ?>
                        <div class="atc-tours-gallery-secondary-item">
                            <img src="<?php echo esc_url($gallery_images[1]); ?>" alt="Gallery Image 2" data-index="1" class="atc-gallery-image" data-action="change-main">
                        </div>
                    <?php endif; ?>
                    <?php if (isset($gallery_images[2])): ?>
                        <div class="atc-tours-gallery-secondary-item">
                            <img src="<?php echo esc_url($gallery_images[2]); ?>" alt="Gallery Image 3" data-index="2" class="atc-gallery-image" data-action="change-main">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (count($gallery_images) > 3): ?>
            <div class="atc-tours-gallery-thumbnails">
                <?php foreach (array_slice($gallery_images, 3, 6) as $index => $image): ?>
                    <div class="atc-tours-gallery-thumb">
                        <img src="<?php echo esc_url($image); ?>" alt="Thumbnail <?php echo $index + 4; ?>" data-index="<?php echo $index + 3; ?>" class="atc-gallery-image" data-action="change-main">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="atc-tours-content-wrapper">
            <!-- Left Column - Main Content -->
            <div class="atc-tours-main-content">
                
                <!-- Package Activities (New Top-Level Section) -->
                <?php if (!empty($package['activities']) && is_array($package['activities'])): ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">🏄 Package Activities</h2>
                    <div class="atc-tours-activities-grid" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px;">
                        <?php foreach ($package['activities'] as $activity): ?>
                            <div class="atc-tours-activity-card" style="background: white; border: 1px solid #e0e0e0; padding: 10px 20px; border-radius: 50px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: transform 0.2s;">
                                <span class="atc-tours-activity-icon" style="font-size: 18px;">🎯</span>
                                <span class="atc-tours-activity-text" style="font-weight: 600; color: #444;"><?php echo esc_html(trim($activity)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Description -->
                <?php if (!empty($package['description'])): ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">📖 About This Package</h2>
                    <div class="atc-tours-description">
                        <?php echo wp_kses_post(nl2br($package['description'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Day-wise Itinerary (Enhanced) -->
                <?php if (!empty($package['day_wise_itinerary']) && is_array($package['day_wise_itinerary'])): ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">🗓️ Day-wise Itinerary</h2>
                    <div class="atc-tours-itinerary">
                        <?php foreach ($package['day_wise_itinerary'] as $day => $day_data): ?>
                            <?php
                            $day_title = is_array($day_data) ? ($day_data['title'] ?? "Day " . ($day + 1)) : "Day " . ($day + 1);
                            $day_content = is_array($day_data) ? ($day_data['content'] ?? '') : $day_data;
                            $day_activities = is_array($day_data) ? ($day_data['activities'] ?? []) : [];
                            $day_num = $day + 1;
                            ?>
                            <div class="atc-tours-itinerary-day" data-day="<?php echo $day_num; ?>" style="margin-bottom: 25px;">
                                <div class="atc-itinerary-day-header" style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                                    <h3 class="atc-tours-itinerary-day-title" style="margin: 0; font-size: 18px; color: #2c3e50;">
                                        <span style="display: inline-block; background: #ff6b35; color: white; padding: 2px 10px; border-radius: 4px; font-size: 14px; margin-right: 10px;">Day <?php echo $day_num; ?></span>
                                        <?php echo esc_html($day_title); ?>
                                    </h3>
                                </div>
                                <div class="atc-tours-itinerary-day-content" style="padding-left: 15px; border-left: 2px solid #ff6b35; margin-left: 15px;">
                                    <?php echo wp_kses_post(nl2br($day_content)); ?>
                                    
                                    <?php if (!empty($day_activities) && is_array($day_activities)): ?>
                                        <div class="atc-tours-itinerary-day-activities" style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee;">
                                            <strong style="display: block; margin-bottom: 8px; font-size: 13px; color: #888; text-transform: uppercase;">Included Activities for Day <?php echo $day_num; ?>:</strong>
                                            <?php foreach ($day_activities as $activity): ?>
                                                <span class="atc-tours-activity-tag" style="background: #e3f2fd; color: #1976d2; border: 1px solid #bbdefb; font-weight: 500; padding: 5px 12px; border-radius: 15px; display: inline-block; margin-right: 5px; margin-bottom: 5px; font-size: 13px;">
                                                    🔹 <?php echo esc_html($activity); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Highlights (Moved Below Itinerary) -->
                <?php if (!empty($package['highlights']) && is_array($package['highlights'])): ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">✨ Package Highlights</h2>
                    <div class="atc-tours-highlights" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <?php foreach ($package['highlights'] as $highlight): ?>
                            <div class="atc-tours-highlight-item" style="background: #f0f7ff; border: 1px solid #cce5ff; padding: 15px; border-radius: 8px; display: flex; align-items: start; gap: 10px;">
                                <span class="atc-tours-highlight-icon" style="color: #0073aa; font-size: 18px; line-height: 1;">✔</span>
                                <span class="atc-tours-highlight-text" style="font-weight: 500; color: #333; line-height: 1.4;"><?php echo esc_html(trim($highlight)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Inclusions & Exclusions -->
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">📋 What's Included</h2>
                    <div class="atc-tours-inclusions-exclusions">
                        <div class="atc-tours-inclusions">
                            <h3>✅ Inclusions</h3>
                            <?php if (!empty($package['inclusions']) && is_array($package['inclusions'])): ?>
                                <ul>
                                    <?php foreach ($package['inclusions'] as $inclusion): ?>
                                        <li><?php echo esc_html(trim($inclusion)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Contact us for details</p>
                            <?php endif; ?>
                        </div>
                        <div class="atc-tours-exclusions">
                            <h3>❌ Exclusions</h3>
                            <?php if (!empty($package['exclusions']) && is_array($package['exclusions'])): ?>
                                <ul>
                                    <?php foreach ($package['exclusions'] as $exclusion): ?>
                                        <li><?php echo esc_html(trim($exclusion)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Contact us for details</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tags & Categories -->
                <?php if (!empty($package['category']) || !empty($package['package_type']) || !empty($package['tags'])): ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">🏷️ Categories & Tags</h2>
                    <div class="atc-tours-tags">
                        <?php if (!empty($package['category'])): ?>
                            <span class="atc-tours-category-badge"><?php echo esc_html($package['category']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($package['package_type'])): ?>
                            <span class="atc-tours-tag"><?php echo esc_html($package['package_type']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($package['tags']) && is_array($package['tags'])): ?>
                            <?php foreach ($package['tags'] as $tag): ?>
                                <span class="atc-tours-tag"><?php echo esc_html(trim($tag)); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Reviews & Ratings Section -->
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">⭐ Reviews & Ratings</h2>
                    <div class="atc-tours-reviews">
                        <?php if (!empty($package['rating'])): ?>
                            <div class="atc-reviews-summary">
                                <div class="atc-reviews-rating">
                                    <span class="atc-rating-number"><?php echo esc_html($package['rating']); ?></span>
                                    <div class="atc-rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="atc-star <?php echo $i <= $package['rating'] ? 'atc-star-filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="atc-reviews-count"><?php echo esc_html($package['reviews_count'] ?? 0); ?> reviews</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="atc-no-reviews">No reviews yet. Be the first to review this package!</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Map Section -->
                <?php 
                // Only show map if enabled
                if (!empty($package['show_map'])) {
                    $google_maps_api_key = get_option('atc_google_maps_api_key', '');
                    $map_src = '';
                    
                    // Priority: Embed URL > Coordinates > Address > Destination
                    if (!empty($package['map_embed_url'])) {
                        // Use embed URL directly
                        $map_src = esc_url($package['map_embed_url']);
                    } elseif (!empty($google_maps_api_key)) {
                        // Build map URL with API key
                        $map_query = '';
                        if (!empty($package['map_latitude']) && !empty($package['map_longitude'])) {
                            $map_query = $package['map_latitude'] . ',' . $package['map_longitude'];
                        } elseif (!empty($package['map_address'])) {
                            $map_query = $package['map_address'];
                        } elseif (!empty($package['destination'])) {
                            $map_query = $package['destination'];
                        }
                        
                        if (!empty($map_query)) {
                            $map_src = 'https://www.google.com/maps/embed/v1/place?key=' . esc_attr($google_maps_api_key) . '&q=' . urlencode($map_query);
                        }
                    }
                    
                    if (!empty($map_src)):
                ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">📍 Location Map</h2>
                    <div class="atc-tours-map">
                        <div class="atc-map-placeholder">
                            <iframe 
                                width="100%" 
                                height="400" 
                                frameborder="0" 
                                style="border:0" 
                                src="<?php echo $map_src; ?>" 
                                allowfullscreen
                                loading="lazy">
                            </iframe>
                        </div>
                    </div>
                </div>
                <?php 
                    elseif (empty($google_maps_api_key)):
                ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">📍 Location Map</h2>
                    <div class="atc-tours-map">
                        <div class="atc-map-placeholder" style="padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px;">
                            <p style="color: #666;">⚠️ Google Maps API key not configured.</p>
                            <p style="color: #666; font-size: 14px;">Please add your Google Maps API key in <a href="<?php echo admin_url('admin.php?page=atc-settings'); ?>">Settings</a> to display the map.</p>
                        </div>
                    </div>
                </div>
                <?php 
                    endif;
                }
                ?>
                
                <!-- FAQ Section -->
                <?php if (!empty($package['faq_items']) && is_array($package['faq_items']) && count($package['faq_items']) > 0): ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">❓ Frequently Asked Questions</h2>
                    <div class="atc-tours-faq">
                        <?php foreach ($package['faq_items'] as $faq_item): ?>
                            <?php
                            $faq_question = is_array($faq_item) ? ($faq_item['question'] ?? '') : '';
                            $faq_answer = is_array($faq_item) ? ($faq_item['answer'] ?? '') : '';
                            if (!empty($faq_question) && !empty($faq_answer)):
                            ?>
                            <div class="atc-faq-item">
                                <div class="atc-faq-question">
                                    <span><?php echo esc_html($faq_question); ?></span>
                                    <button class="atc-faq-toggle">+</button>
                                </div>
                                <div class="atc-faq-answer">
                                    <p><?php echo wp_kses_post(nl2br($faq_answer)); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- Default FAQ if none provided -->
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">❓ Frequently Asked Questions</h2>
                    <div class="atc-tours-faq">
                        <div class="atc-faq-item">
                            <div class="atc-faq-question">
                                <span>What is included in the package?</span>
                                <button class="atc-faq-toggle">+</button>
                            </div>
                            <div class="atc-faq-answer">
                                <p>All inclusions are listed in the "What's Included" section above. This typically includes accommodation, meals, transportation, and guided tours as specified.</p>
                            </div>
                        </div>
                        <div class="atc-faq-item">
                            <div class="atc-faq-question">
                                <span>What is the cancellation policy?</span>
                                <button class="atc-faq-toggle">+</button>
                            </div>
                            <div class="atc-faq-answer">
                                <p>Please refer to the cancellation policy section below for detailed information about refunds and cancellation terms.</p>
                            </div>
                        </div>
                        <div class="atc-faq-item">
                            <div class="atc-faq-question">
                                <span>Can I customize this package?</span>
                                <button class="atc-faq-toggle">+</button>
                            </div>
                            <div class="atc-faq-answer">
                                <p>Yes! Click on "Ask for More Details" to request a customized version of this package tailored to your preferences.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Terms & Conditions -->
                <?php if (!empty($package['terms_conditions'])): ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">📜 Terms & Conditions</h2>
                    <div class="atc-tours-terms">
                        <?php echo wp_kses_post(nl2br($package['terms_conditions'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Cancellation Policy -->
                <?php if (!empty($package['cancellation_policy'])): ?>
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">🚫 Cancellation Policy</h2>
                    <div class="atc-tours-cancellation">
                        <?php echo wp_kses_post(nl2br($package['cancellation_policy'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Right Column - Pricing Card (Sticky) -->
            <div class="atc-tours-pricing-card">
                <div class="atc-tours-price-header">
                    <div class="atc-tours-price-label">Starting from</div>
                    <div class="atc-tours-price-main">
                        <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                            <span class="atc-tours-price-original"><?php echo esc_html($currency . number_format($package['original_price'])); ?></span>
                        <?php endif; ?>
                        <span class="atc-tours-price-current"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                    </div>
                    <div class="atc-tours-price-per">per person</div>
                    <?php if (!empty($package['discount'])): ?>
                        <span class="atc-tours-discount-badge"><?php echo esc_html($package['discount']); ?>% OFF</span>
                    <?php endif; ?>
                </div>
                
                <div class="atc-tours-trust-badges">
                    <div class="atc-trust-badge">
                        <span class="atc-badge-icon">🔒</span>
                        <span>Secure Payment</span>
                    </div>
                    <div class="atc-trust-badge">
                        <span class="atc-badge-icon">✓</span>
                        <span>Verified Package</span>
                    </div>
                </div>
                
                <button class="atc-tours-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                    <span>Book Now</span>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                        <path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                
                <!-- Ask for More Details Button (wrapped, opens modal) -->
                <?php echo do_shortcode('[atc_query_form service="' . esc_attr($package['service_key'] ?? 'tours') . '" button_text="Ask for More Details"]'); ?>
            </div>
        </div>
        
        <!-- Similar Packages Section -->
        <div class="atc-tours-similar-packages">
            <div class="atc-tours-content-wrapper">
                <div class="atc-tours-section">
                    <h2 class="atc-tours-section-title">🔗 Similar Packages</h2>
                    <div class="atc-similar-packages-grid" data-package-id="<?php echo esc_attr($package['id']); ?>" data-service="<?php echo esc_attr($package['service_key'] ?? 'tours'); ?>">
                        <div class="atc-similar-loading">Loading similar packages...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile Sticky Booking Bar -->
        <div class="atc-tours-mobile-booking-bar">
            <div class="atc-mobile-booking-price">
                <span class="atc-mobile-price-label">Starting from</span>
                <span class="atc-mobile-price-value"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
            </div>
            <button class="atc-mobile-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                Book Now
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Forex Package Details - Financial Service Style
     */
    public static function forex_package_details_shortcode($atts) {
        $atts = shortcode_atts([
            'package_id' => '',
        ], $atts);
        
        // Package ID should be numeric (1, 2, 3, etc.)
        $package_id_raw = !empty($atts['package_id']) ? $atts['package_id'] : (isset($_GET['package_id']) ? $_GET['package_id'] : '');
        
        if (empty($package_id_raw)) {
            return '<p class="atc-error">Package ID is required.</p>';
        }
        
        // Convert to integer (numeric package_id)
        if (!is_numeric($package_id_raw)) {
            return '<p class="atc-error">Invalid Package ID. Package ID must be numeric (1, 2, 3, etc.).</p>';
        }
        
        $package_id = intval($package_id_raw);
        
        // Get package using helper function
        $package = self::get_package_by_id($package_id);
        
        if (!$package) {
            return '<p class="atc-error">Package not found.</p>';
        }
        
        // Use numeric id
        $package_id = intval($package['id']);
        
        if ($package) {
            $package = self::format_package_data($package);
            // Only render if status is active (for frontend) or allow all statuses for admin
            if ($package['status'] === 'active' || current_user_can('manage_options')) {
                ob_start();
                echo self::render_forex_package_details($package);
                return ob_get_clean();
            } else {
                return '<p class="atc-error">Package is not available.</p>';
            }
        }
        
        // Fallback to JS loading if package not found in database
        ob_start();
        ?>
        <div class="atc-package-details-forex-wrapper" data-package-id="<?php echo esc_attr($package_id); ?>">
            <div class="atc-loading-state">
                <div class="atc-spinner"></div>
                <p>Loading forex package details...</p>
            </div>
            <div class="atc-package-details-forex-content" style="display: none;">
                <!-- Package details will be loaded here by JS -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Forex Package Details (Financial Service Style)
     */
    public static function render_forex_package_details($package) {
        $currency = get_option('atc_currency_symbol', '₹');
        $gallery_images = !empty($package['gallery_images']) ? $package['gallery_images'] : [];
        if (empty($gallery_images) && !empty($package['images'])) {
            $gallery_images = $package['images'];
        }
        
        // Get forex-specific metadata
        $metadata = [];
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
        }
        
        $currency_pair = $metadata['currency_pair'] ?? ($package['destination'] ?? 'INR to USD');
        $exchange_rate = $metadata['exchange_rate'] ?? '';
        $buy_rate = $metadata['buy_rate'] ?? '';
        $sell_rate = $metadata['sell_rate'] ?? '';
        $minimum_amount = $metadata['minimum_amount'] ?? 0;
        $maximum_amount = $metadata['maximum_amount'] ?? 0;
        $conversion_fee_percent = $metadata['conversion_fee_percent'] ?? 0;
        $conversion_fee_fixed = $metadata['conversion_fee_fixed'] ?? 0;
        
        // Parse delivery_options (can be string or array)
        $delivery_options = $metadata['delivery_options'] ?? [];
        if (is_string($delivery_options)) {
            $delivery_options = array_filter(array_map('trim', explode("\n", $delivery_options)));
        }
        if (!is_array($delivery_options)) {
            $delivery_options = [];
        }
        
        // Parse required_documents (can be string or array)
        $required_documents = $metadata['required_documents'] ?? [];
        if (is_string($required_documents)) {
            $required_documents = array_filter(array_map('trim', explode("\n", $required_documents)));
        }
        if (!is_array($required_documents)) {
            $required_documents = [];
        }
        
        // Get service theme colors
        $theme = ATC_Service_Ecosystem::get_service_theme('forex');
        $colors = $theme['color_scheme'] ?? [
            'primary' => '#1e3a8a',
            'secondary' => '#3b82f6',
            'accent' => '#10b981',
        ];
        
        ob_start();
        ?>
        <div class="atc-package-details-forex" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>; --atc-accent: <?php echo esc_attr($colors['accent']); ?>;">
            <!-- Breadcrumb Navigation -->
            <div class="atc-forex-breadcrumb">
                <div class="atc-forex-breadcrumb-inner">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                    <span class="atc-breadcrumb-separator">›</span>
                    <a href="<?php echo esc_url(home_url('/forex')); ?>">Forex</a>
                    <span class="atc-breadcrumb-separator">›</span>
                    <span class="atc-breadcrumb-current"><?php echo esc_html($package['name']); ?></span>
                </div>
            </div>
            
            <!-- Hero Section -->
            <div class="atc-forex-hero">
                <?php if (!empty($package['image_url'])): ?>
                    <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>" class="atc-forex-hero-image">
                <?php endif; ?>
                <div class="atc-forex-hero-overlay"></div>
                <div class="atc-forex-hero-content">
                    <h1 class="atc-forex-hero-title"><?php echo esc_html($package['name']); ?></h1>
                    <?php if (!empty($package['short_description'])): ?>
                        <p class="atc-forex-hero-subtitle"><?php echo esc_html($package['short_description']); ?></p>
                    <?php endif; ?>
                    <div class="atc-forex-hero-meta">
                        <?php if (!empty($currency_pair)): ?>
                            <div class="atc-forex-hero-meta-item">
                                <span class="atc-meta-icon">💱</span>
                                <span><?php echo esc_html($currency_pair); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($exchange_rate)): ?>
                            <div class="atc-forex-hero-meta-item">
                                <span class="atc-meta-icon">📊</span>
                                <span>Rate: <?php echo esc_html($exchange_rate); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($package['rating'])): ?>
                            <div class="atc-forex-hero-meta-item">
                                <span class="atc-meta-icon">⭐</span>
                                <span><?php echo esc_html($package['rating']); ?>/5</span>
                                <?php if (!empty($package['reviews_count'])): ?>
                                    <span>(<?php echo esc_html($package['reviews_count']); ?> reviews)</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="atc-forex-content-wrapper">
                <!-- Left Column - Main Content -->
                <div class="atc-forex-main-content">
                    
                    <!-- Currency Exchange Rates -->
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">💱 Exchange Rates</h2>
                        <div class="atc-forex-rates-grid">
                            <?php if (!empty($buy_rate)): ?>
                                <div class="atc-forex-rate-card">
                                    <div class="atc-rate-label">Buy Rate</div>
                                    <div class="atc-rate-value"><?php echo esc_html($buy_rate); ?></div>
                                    <div class="atc-rate-description">Rate for buying foreign currency</div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($sell_rate)): ?>
                                <div class="atc-forex-rate-card">
                                    <div class="atc-rate-label">Sell Rate</div>
                                    <div class="atc-rate-value"><?php echo esc_html($sell_rate); ?></div>
                                    <div class="atc-rate-description">Rate for selling foreign currency</div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($exchange_rate)): ?>
                                <div class="atc-forex-rate-card">
                                    <div class="atc-rate-label">Current Rate</div>
                                    <div class="atc-rate-value"><?php echo esc_html($exchange_rate); ?></div>
                                    <div class="atc-rate-description">Market exchange rate</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Package Description -->
                    <?php if (!empty($package['description'])): ?>
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">📖 About This Service</h2>
                        <div class="atc-forex-description">
                            <?php echo wp_kses_post(nl2br($package['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Transaction Limits -->
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">💰 Transaction Limits</h2>
                        <div class="atc-forex-limits">
                            <?php if (!empty($minimum_amount)): ?>
                                <div class="atc-limit-item">
                                    <span class="atc-limit-label">Minimum Amount:</span>
                                    <span class="atc-limit-value"><?php echo esc_html($currency . number_format($minimum_amount)); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($maximum_amount)): ?>
                                <div class="atc-limit-item">
                                    <span class="atc-limit-label">Maximum Amount:</span>
                                    <span class="atc-limit-value"><?php echo esc_html($currency . number_format($maximum_amount)); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($conversion_fee_percent)): ?>
                                <div class="atc-limit-item">
                                    <span class="atc-limit-label">Service Fee:</span>
                                    <span class="atc-limit-value"><?php echo esc_html($conversion_fee_percent); ?>%</span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($conversion_fee_fixed)): ?>
                                <div class="atc-limit-item">
                                    <span class="atc-limit-label">Fixed Fee:</span>
                                    <span class="atc-limit-value"><?php echo esc_html($currency . number_format($conversion_fee_fixed)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Delivery Options -->
                    <?php if (!empty($delivery_options) && is_array($delivery_options)): ?>
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">🚚 Delivery Options</h2>
                        <div class="atc-forex-delivery-options">
                            <?php foreach ($delivery_options as $option): ?>
                                <div class="atc-delivery-option-item">
                                    <span class="atc-delivery-icon">✓</span>
                                    <span><?php echo esc_html($option); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Required Documents -->
                    <?php if (!empty($required_documents) && is_array($required_documents)): ?>
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">📄 Required Documents</h2>
                        <div class="atc-forex-documents">
                            <?php foreach ($required_documents as $doc): ?>
                                <div class="atc-document-item">
                                    <span class="atc-document-icon">📋</span>
                                    <span><?php echo esc_html($doc); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Highlights -->
                    <?php if (!empty($package['highlights']) && is_array($package['highlights'])): ?>
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">✨ Service Highlights</h2>
                        <div class="atc-forex-highlights">
                            <?php foreach ($package['highlights'] as $highlight): ?>
                                <div class="atc-forex-highlight-item">
                                    <span class="atc-forex-highlight-icon">✓</span>
                                    <span class="atc-forex-highlight-text"><?php echo esc_html(trim($highlight)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Terms & Conditions -->
                    <?php if (!empty($package['terms_conditions'])): ?>
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">📜 Terms & Conditions</h2>
                        <div class="atc-forex-terms">
                            <?php echo wp_kses_post(nl2br($package['terms_conditions'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Cancellation Policy -->
                    <?php if (!empty($package['cancellation_policy'])): ?>
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">🚫 Cancellation Policy</h2>
                        <div class="atc-forex-cancellation">
                            <?php echo wp_kses_post(nl2br($package['cancellation_policy'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Right Column - Pricing & Booking Card (Sticky) -->
                <div class="atc-forex-pricing-card">
                    <div class="atc-forex-price-header">
                        <div class="atc-forex-price-label">Starting from</div>
                        <div class="atc-forex-price-main">
                            <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                                <span class="atc-forex-price-original"><?php echo esc_html($currency . number_format($package['original_price'])); ?></span>
                            <?php endif; ?>
                            <span class="atc-forex-price-current"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                        </div>
                        <?php if (!empty($package['discount'])): ?>
                            <span class="atc-forex-discount-badge"><?php echo esc_html($package['discount']); ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="atc-forex-trust-badges">
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">🔒</span>
                            <span>Secure Transaction</span>
                        </div>
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">✓</span>
                            <span>Verified Service</span>
                        </div>
                    </div>
                    
                    <button class="atc-forex-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                        <span>Book Now</span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <!-- Ask for More Details Button -->
                    <?php echo do_shortcode('[atc_query_form service="' . esc_attr($package['service_key'] ?? 'forex') . '" button_text="Ask for More Details"]'); ?>
                </div>
            </div>
            
            <!-- Similar Packages Section -->
            <div class="atc-forex-similar-packages">
                <div class="atc-forex-content-wrapper">
                    <div class="atc-forex-section">
                        <h2 class="atc-forex-section-title">🔗 Similar Forex Services</h2>
                        <div class="atc-similar-packages-grid" data-package-id="<?php echo esc_attr($package['id']); ?>" data-service="<?php echo esc_attr($package['service_key'] ?? 'forex'); ?>">
                            <div class="atc-similar-loading">Loading similar services...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Sticky Booking Bar -->
            <div class="atc-forex-mobile-booking-bar">
                <div class="atc-mobile-booking-price">
                    <span class="atc-mobile-price-label">From</span>
                    <span class="atc-mobile-price-value"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                </div>
                <button class="atc-mobile-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                    Book Now
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Hotels Package Details - Booking.com Style
     */
    public static function hotels_package_details_shortcode($atts) {
        $atts = shortcode_atts([
            'package_id' => '',
        ], $atts);
        
        $package_id = !empty($atts['package_id']) ? intval($atts['package_id']) : (isset($_GET['package_id']) ? intval($_GET['package_id']) : 0);
        
        if (!$package_id) {
            return '<p class="atc-error">Package ID is required.</p>';
        }
        
        // Try to load package data immediately for server-side rendering
        global $wpdb;
        // Query by numeric id (package_id should match id after migration)
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d OR package_id = %s",
            $package_id,
            (string)$package_id
        ), ARRAY_A);
        
        if ($package) {
            $package = self::format_package_data($package);
            // Only render if status is active (for frontend) or allow all statuses for admin
            if ($package['status'] === 'active' || current_user_can('manage_options')) {
                ob_start();
                echo self::render_hotels_package_details($package);
                return ob_get_clean();
            } else {
                return '<p class="atc-error">Package is not available.</p>';
            }
        }
        
        // Fallback to JS loading if package not found in database
        ob_start();
        ?>
        <div class="atc-package-details-hotels-wrapper" data-package-id="<?php echo esc_attr($package_id); ?>">
            <div class="atc-loading-state">
                <div class="atc-spinner"></div>
                <p>Loading hotel details...</p>
            </div>
            <div class="atc-package-details-hotels-content" style="display: none;">
                <!-- Package details will be loaded here by JS -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Hotels Package Details (Booking.com Style)
     */
    public static function render_hotels_package_details($package) {
        $currency = get_option('atc_currency_symbol', '₹');
        $gallery_images = !empty($package['gallery_images']) ? $package['gallery_images'] : [];
        if (empty($gallery_images) && !empty($package['images'])) {
            $gallery_images = $package['images'];
        }
        
        // Get hotel-specific metadata
        $metadata = [];
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
        }
        
        $hotel_name = $metadata['hotel_name'] ?? ($package['name'] ?? '');
        $location = $metadata['location'] ?? ($package['destination'] ?? '');
        $star_rating = $metadata['star_rating'] ?? '';
        $room_types = $metadata['room_types'] ?? [];
        $amenities = $metadata['amenities'] ?? [];
        $check_in_time = $metadata['check_in_time'] ?? '14:00';
        $check_out_time = $metadata['check_out_time'] ?? '11:00';
        $nearby_attractions = $metadata['nearby_attractions'] ?? '';
        $distance_from_airport = $metadata['distance_from_airport'] ?? '';
        $distance_from_railway = $metadata['distance_from_railway'] ?? '';
        
        // Parse room_types and amenities (can be string or array)
        if (is_string($room_types)) {
            $room_types = array_filter(array_map('trim', explode("\n", $room_types)));
        }
        if (!is_array($room_types)) {
            $room_types = [];
        }
        
        if (is_string($amenities)) {
            $amenities = array_filter(array_map('trim', explode("\n", $amenities)));
        }
        if (!is_array($amenities)) {
            $amenities = [];
        }
        
        // Get service theme colors
        $theme = ATC_Service_Ecosystem::get_service_theme('hotels');
        $colors = $theme['color_scheme'] ?? [
            'primary' => '#003580',
            'secondary' => '#006ce4',
            'accent' => '#ffb700',
        ];
        
        ob_start();
        ?>
        <div class="atc-package-details-hotels" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>; --atc-accent: <?php echo esc_attr($colors['accent']); ?>;">
            <!-- Breadcrumb Navigation -->
            <div class="atc-hotels-breadcrumb">
                <div class="atc-hotels-breadcrumb-inner">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                    <span class="atc-breadcrumb-separator">›</span>
                    <a href="<?php echo esc_url(home_url('/hotels')); ?>">Hotels</a>
                    <span class="atc-breadcrumb-separator">›</span>
                    <span class="atc-breadcrumb-current"><?php echo esc_html($package['name']); ?></span>
                </div>
            </div>
            
            <!-- Hero Section with Image Gallery -->
            <div class="atc-hotels-hero">
                <?php if (!empty($gallery_images) && is_array($gallery_images) && count($gallery_images) > 0): ?>
                    <div class="atc-hotels-gallery">
                        <div class="atc-hotels-main-image">
                            <img src="<?php echo esc_url($gallery_images[0]); ?>" alt="<?php echo esc_attr($package['name']); ?>" id="atc-hotels-main-img">
                        </div>
                        <?php if (count($gallery_images) > 1): ?>
                            <div class="atc-hotels-thumbnails">
                                <?php foreach (array_slice($gallery_images, 0, 4) as $index => $img): ?>
                                    <div class="atc-hotels-thumb <?php echo $index === 0 ? 'active' : ''; ?>" data-image="<?php echo esc_url($img); ?>">
                                        <img src="<?php echo esc_url($img); ?>" alt="Gallery Image <?php echo $index + 1; ?>">
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($gallery_images) > 4): ?>
                                    <div class="atc-hotels-thumb-more">
                                        <span>+<?php echo count($gallery_images) - 4; ?> more</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif (!empty($package['image_url'])): ?>
                    <div class="atc-hotels-gallery">
                        <div class="atc-hotels-main-image">
                            <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>">
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Hotel Header Info -->
                <div class="atc-hotels-header">
                    <div class="atc-hotels-header-content">
                        <h1 class="atc-hotels-title"><?php echo esc_html($hotel_name ?: $package['name']); ?></h1>
                        <div class="atc-hotels-location">
                            <span class="atc-location-icon">📍</span>
                            <span><?php echo esc_html($location); ?></span>
                        </div>
                        <div class="atc-hotels-rating-meta">
                            <?php if (!empty($star_rating)): ?>
                                <div class="atc-star-rating">
                                    <?php
                                    $stars = intval(str_replace(['Star', ' '], '', $star_rating));
                                    for ($i = 0; $i < 5; $i++):
                                        if ($i < $stars):
                                    ?>
                                        <span class="atc-star filled">★</span>
                                    <?php else: ?>
                                        <span class="atc-star">★</span>
                                    <?php endif; endfor; ?>
                                    <span class="atc-rating-text"><?php echo esc_html($star_rating); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($package['rating'])): ?>
                                <div class="atc-guest-rating">
                                    <span class="atc-rating-score"><?php echo esc_html($package['rating']); ?></span>
                                    <span class="atc-rating-label">Excellent</span>
                                    <?php if (!empty($package['reviews_count'])): ?>
                                        <span class="atc-reviews-count">(<?php echo esc_html($package['reviews_count']); ?> reviews)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="atc-hotels-content-wrapper">
                <!-- Left Column - Main Content -->
                <div class="atc-hotels-main-content">
                    
                    <!-- Room Types Section -->
                    <?php if (!empty($room_types) && is_array($room_types)): ?>
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">🛏️ Available Room Types</h2>
                        <div class="atc-hotels-rooms-grid">
                            <?php foreach ($room_types as $room_type): ?>
                                <div class="atc-hotels-room-card">
                                    <div class="atc-room-icon">🛏️</div>
                                    <div class="atc-room-name"><?php echo esc_html(trim($room_type)); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Amenities Section -->
                    <?php if (!empty($amenities) && is_array($amenities)): ?>
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">✨ Hotel Amenities</h2>
                        <div class="atc-hotels-amenities-grid">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="atc-hotels-amenity-item">
                                    <span class="atc-amenity-icon">✓</span>
                                    <span class="atc-amenity-text"><?php echo esc_html(trim($amenity)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Hotel Description -->
                    <?php if (!empty($package['description'])): ?>
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">📖 About This Hotel</h2>
                        <div class="atc-hotels-description">
                            <?php echo wp_kses_post(nl2br($package['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Highlights -->
                    <?php if (!empty($package['highlights']) && is_array($package['highlights'])): ?>
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">⭐ Hotel Highlights</h2>
                        <div class="atc-hotels-highlights">
                            <?php foreach ($package['highlights'] as $highlight): ?>
                                <div class="atc-hotels-highlight-item">
                                    <span class="atc-highlight-icon">✓</span>
                                    <span class="atc-highlight-text"><?php echo esc_html(trim($highlight)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Location & Nearby -->
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">📍 Location & Nearby</h2>
                        <div class="atc-hotels-location-info">
                            <?php if (!empty($distance_from_airport)): ?>
                                <div class="atc-location-item">
                                    <span class="atc-location-label">✈️ Airport:</span>
                                    <span class="atc-location-value"><?php echo esc_html($distance_from_airport); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($distance_from_railway)): ?>
                                <div class="atc-location-item">
                                    <span class="atc-location-label">🚂 Railway Station:</span>
                                    <span class="atc-location-value"><?php echo esc_html($distance_from_railway); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($nearby_attractions)): ?>
                                <div class="atc-location-item">
                                    <span class="atc-location-label">🎯 Nearby Attractions:</span>
                                    <span class="atc-location-value"><?php echo esc_html($nearby_attractions); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Check-in/Check-out Info -->
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">🕐 Check-in & Check-out</h2>
                        <div class="atc-hotels-timing">
                            <div class="atc-timing-item">
                                <span class="atc-timing-label">Check-in:</span>
                                <span class="atc-timing-value"><?php echo esc_html($check_in_time); ?></span>
                            </div>
                            <div class="atc-timing-item">
                                <span class="atc-timing-label">Check-out:</span>
                                <span class="atc-timing-value"><?php echo esc_html($check_out_time); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inclusions & Exclusions -->
                    <?php if (!empty($package['inclusions']) || !empty($package['exclusions'])): ?>
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">📋 What's Included</h2>
                        <div class="atc-hotels-inclusions-exclusions">
                            <?php if (!empty($package['inclusions'])): ?>
                                <div class="atc-inclusions">
                                    <h3>✅ Included</h3>
                                    <ul>
                                        <?php
                                        $inclusions = is_array($package['inclusions']) ? $package['inclusions'] : explode(',', $package['inclusions']);
                                        foreach ($inclusions as $inclusion):
                                        ?>
                                            <li><?php echo esc_html(trim($inclusion)); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($package['exclusions'])): ?>
                                <div class="atc-exclusions">
                                    <h3>❌ Not Included</h3>
                                    <ul>
                                        <?php
                                        $exclusions = is_array($package['exclusions']) ? $package['exclusions'] : explode(',', $package['exclusions']);
                                        foreach ($exclusions as $exclusion):
                                        ?>
                                            <li><?php echo esc_html(trim($exclusion)); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Terms & Conditions -->
                    <?php if (!empty($package['terms_conditions'])): ?>
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">📜 Terms & Conditions</h2>
                        <div class="atc-hotels-terms">
                            <?php echo wp_kses_post(nl2br($package['terms_conditions'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Cancellation Policy -->
                    <?php if (!empty($package['cancellation_policy'])): ?>
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">🚫 Cancellation Policy</h2>
                        <div class="atc-hotels-cancellation">
                            <?php echo wp_kses_post(nl2br($package['cancellation_policy'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Right Column - Booking Card (Sticky) -->
                <div class="atc-hotels-booking-card">
                    <div class="atc-hotels-price-header">
                        <div class="atc-hotels-price-label">Starting from</div>
                        <div class="atc-hotels-price-main">
                            <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                                <span class="atc-hotels-price-original"><?php echo esc_html($currency . number_format($package['original_price'])); ?></span>
                            <?php endif; ?>
                            <span class="atc-hotels-price-current"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                            <span class="atc-hotels-price-unit">/ night</span>
                        </div>
                        <?php if (!empty($package['discount'])): ?>
                            <span class="atc-hotels-discount-badge"><?php echo esc_html($package['discount']); ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="atc-hotels-trust-badges">
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">🔒</span>
                            <span>Secure Booking</span>
                        </div>
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">✓</span>
                            <span>Best Price Guarantee</span>
                        </div>
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">⭐</span>
                            <span>Verified Hotel</span>
                        </div>
                    </div>
                    
                    <button class="atc-hotels-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                        <span>Book Now</span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <!-- Ask for More Details Button -->
                    <?php echo do_shortcode('[atc_query_form service="' . esc_attr($package['service_key'] ?? 'hotels') . '" button_text="Ask for More Details"]'); ?>
                </div>
            </div>
            
            <!-- Similar Hotels Section -->
            <div class="atc-hotels-similar">
                <div class="atc-hotels-content-wrapper">
                    <div class="atc-hotels-section">
                        <h2 class="atc-hotels-section-title">🏨 Similar Hotels</h2>
                        <div class="atc-similar-hotels-grid" data-package-id="<?php echo esc_attr($package['id']); ?>" data-service="<?php echo esc_attr($package['service_key'] ?? 'hotels'); ?>">
                            <div class="atc-similar-loading">Loading similar hotels...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Sticky Booking Bar -->
            <div class="atc-hotels-mobile-booking-bar">
                <div class="atc-mobile-booking-price">
                    <span class="atc-mobile-price-label">From</span>
                    <span class="atc-mobile-price-value"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                    <span class="atc-mobile-price-unit">/ night</span>
                </div>
                <button class="atc-mobile-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                    Book Now
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Trains Package Details - IRCTC Style
     */
    public static function trains_package_details_shortcode($atts) {
        $atts = shortcode_atts([
            'package_id' => '',
        ], $atts);
        
        $package_id = !empty($atts['package_id']) ? intval($atts['package_id']) : (isset($_GET['package_id']) ? intval($_GET['package_id']) : 0);
        
        if (!$package_id) {
            return '<p class="atc-error">Package ID is required.</p>';
        }
        
        // Try to load package data immediately for server-side rendering
        global $wpdb;
        // Query by numeric id (package_id should match id after migration)
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d OR package_id = %s",
            $package_id,
            (string)$package_id
        ), ARRAY_A);
        
        if ($package) {
            $package = self::format_package_data($package);
            // Only render if status is active (for frontend) or allow all statuses for admin
            if ($package['status'] === 'active' || current_user_can('manage_options')) {
                ob_start();
                echo self::render_trains_package_details($package);
                return ob_get_clean();
            } else {
                return '<p class="atc-error">Package is not available.</p>';
            }
        }
        
        // Fallback to JS loading if package not found in database
        ob_start();
        ?>
        <div class="atc-package-details-trains-wrapper" data-package-id="<?php echo esc_attr($package_id); ?>">
            <div class="atc-loading-state">
                <div class="atc-spinner"></div>
                <p>Loading train details...</p>
            </div>
            <div class="atc-package-details-trains-content" style="display: none;">
                <!-- Package details will be loaded here by JS -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Trains Package Details (IRCTC Style)
     */
    public static function render_trains_package_details($package) {
        $currency = get_option('atc_currency_symbol', '₹');
        $gallery_images = !empty($package['gallery_images']) ? $package['gallery_images'] : [];
        if (empty($gallery_images) && !empty($package['images'])) {
            $gallery_images = $package['images'];
        }
        
        // Get train-specific metadata
        $metadata = [];
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
        }
        
        $train_number = $metadata['train_number'] ?? '';
        $train_name = $metadata['train_name'] ?? ($package['name'] ?? '');
        $from_station = $metadata['from_station'] ?? '';
        $to_station = $metadata['to_station'] ?? '';
        $departure_time = $metadata['departure_time'] ?? '';
        $arrival_time = $metadata['arrival_time'] ?? '';
        $journey_duration = $metadata['journey_duration'] ?? '';
        $available_classes = $metadata['available_classes'] ?? [];
        $facilities = $metadata['facilities'] ?? [];
        
        // Parse available_classes and facilities (can be string or array)
        if (is_string($available_classes)) {
            $available_classes = array_filter(array_map('trim', explode("\n", $available_classes)));
        }
        if (!is_array($available_classes)) {
            $available_classes = [];
        }
        
        if (is_string($facilities)) {
            $facilities = array_filter(array_map('trim', explode("\n", $facilities)));
        }
        if (!is_array($facilities)) {
            $facilities = [];
        }
        
        // Get service theme colors
        $theme = ATC_Service_Ecosystem::get_service_theme('trains');
        $colors = $theme['color_scheme'] ?? [
            'primary' => '#d32f2f',
            'secondary' => '#f44336',
            'accent' => '#ff9800',
        ];
        
        ob_start();
        ?>
        <div class="atc-package-details-trains" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>; --atc-accent: <?php echo esc_attr($colors['accent']); ?>;">
            <!-- Breadcrumb Navigation -->
            <div class="atc-trains-breadcrumb">
                <div class="atc-trains-breadcrumb-inner">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                    <span class="atc-breadcrumb-separator">›</span>
                    <a href="<?php echo esc_url(home_url('/trains')); ?>">Trains</a>
                    <span class="atc-breadcrumb-separator">›</span>
                    <span class="atc-breadcrumb-current"><?php echo esc_html($package['name']); ?></span>
                </div>
            </div>
            
            <!-- Hero Section -->
            <div class="atc-trains-hero">
                <?php if (!empty($gallery_images) && is_array($gallery_images) && count($gallery_images) > 0): ?>
                    <div class="atc-trains-hero-image">
                        <img src="<?php echo esc_url($gallery_images[0]); ?>" alt="<?php echo esc_attr($package['name']); ?>">
                    </div>
                <?php elseif (!empty($package['image_url'])): ?>
                    <div class="atc-trains-hero-image">
                        <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>">
                    </div>
                <?php endif; ?>
                
                <!-- Train Route Card -->
                <div class="atc-trains-route-card">
                    <div class="atc-route-header">
                        <div class="atc-train-number"><?php echo esc_html($train_number ?: 'Train'); ?></div>
                        <div class="atc-train-name"><?php echo esc_html($train_name); ?></div>
                    </div>
                    
                    <div class="atc-route-details">
                        <div class="atc-route-station atc-departure">
                            <div class="atc-station-name"><?php echo esc_html($from_station ?: 'From Station'); ?></div>
                            <div class="atc-station-time"><?php echo esc_html($departure_time ?: '--:--'); ?></div>
                        </div>
                        
                        <div class="atc-route-connector">
                            <div class="atc-route-line"></div>
                            <div class="atc-route-duration"><?php echo esc_html($journey_duration ?: 'Duration'); ?></div>
                        </div>
                        
                        <div class="atc-route-station atc-arrival">
                            <div class="atc-station-name"><?php echo esc_html($to_station ?: 'To Station'); ?></div>
                            <div class="atc-station-time"><?php echo esc_html($arrival_time ?: '--:--'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="atc-trains-content-wrapper">
                <!-- Left Column - Main Content -->
                <div class="atc-trains-main-content">
                    
                    <!-- Available Classes Section -->
                    <?php if (!empty($available_classes) && is_array($available_classes)): ?>
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">🚂 Available Classes</h2>
                        <div class="atc-trains-classes-grid">
                            <?php foreach ($available_classes as $class): ?>
                                <div class="atc-trains-class-card">
                                    <div class="atc-class-icon">🚂</div>
                                    <div class="atc-class-name"><?php echo esc_html(trim($class)); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Train Facilities Section -->
                    <?php if (!empty($facilities) && is_array($facilities)): ?>
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">✨ Train Facilities</h2>
                        <div class="atc-trains-facilities-grid">
                            <?php foreach ($facilities as $facility): ?>
                                <div class="atc-trains-facility-item">
                                    <span class="atc-facility-icon">✓</span>
                                    <span class="atc-facility-text"><?php echo esc_html(trim($facility)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Train Description -->
                    <?php if (!empty($package['description'])): ?>
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">📖 About This Train</h2>
                        <div class="atc-trains-description">
                            <?php echo wp_kses_post(nl2br($package['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Highlights -->
                    <?php if (!empty($package['highlights']) && is_array($package['highlights'])): ?>
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">⭐ Train Highlights</h2>
                        <div class="atc-trains-highlights">
                            <?php foreach ($package['highlights'] as $highlight): ?>
                                <div class="atc-trains-highlight-item">
                                    <span class="atc-highlight-icon">✓</span>
                                    <span class="atc-highlight-text"><?php echo esc_html(trim($highlight)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Cancellation Policy -->
                    <?php if (!empty($package['cancellation_policy'])): ?>
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">🚫 Cancellation Policy</h2>
                        <div class="atc-trains-cancellation">
                            <?php echo wp_kses_post(nl2br($package['cancellation_policy'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Rescheduling Policy -->
                    <?php if (!empty($metadata['rescheduling_policy'])): ?>
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">🔄 Rescheduling Policy</h2>
                        <div class="atc-trains-rescheduling">
                            <?php echo wp_kses_post(nl2br($metadata['rescheduling_policy'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Refund Policy -->
                    <?php if (!empty($metadata['refund_policy'])): ?>
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">💰 Refund Policy</h2>
                        <div class="atc-trains-refund">
                            <?php echo wp_kses_post(nl2br($metadata['refund_policy'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Terms & Conditions -->
                    <?php if (!empty($package['terms_conditions'])): ?>
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">📜 Terms & Conditions</h2>
                        <div class="atc-trains-terms">
                            <?php echo wp_kses_post(nl2br($package['terms_conditions'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Right Column - Booking Card (Sticky) -->
                <div class="atc-trains-booking-card">
                    <div class="atc-trains-price-header">
                        <div class="atc-trains-price-label">Starting from</div>
                        <div class="atc-trains-price-main">
                            <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                                <span class="atc-trains-price-original"><?php echo esc_html($currency . number_format($package['original_price'])); ?></span>
                            <?php endif; ?>
                            <span class="atc-trains-price-current"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                            <span class="atc-trains-price-unit">/ person</span>
                        </div>
                        <?php if (!empty($package['discount'])): ?>
                            <span class="atc-trains-discount-badge"><?php echo esc_html($package['discount']); ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="atc-trains-trust-badges">
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">🔒</span>
                            <span>Secure Booking</span>
                        </div>
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">✓</span>
                            <span>IRCTC Verified</span>
                        </div>
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">🚂</span>
                            <span>Official Partner</span>
                        </div>
                    </div>
                    
                    <button class="atc-trains-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                        <span>Book Now</span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <!-- Ask for More Details Button -->
                    <?php echo do_shortcode('[atc_query_form service="' . esc_attr($package['service_key'] ?? 'trains') . '" button_text="Ask for More Details"]'); ?>
                </div>
            </div>
            
            <!-- Similar Trains Section -->
            <div class="atc-trains-similar">
                <div class="atc-trains-content-wrapper">
                    <div class="atc-trains-section">
                        <h2 class="atc-trains-section-title">🚂 Similar Trains</h2>
                        <div class="atc-similar-trains-grid" data-package-id="<?php echo esc_attr($package['id']); ?>" data-service="<?php echo esc_attr($package['service_key'] ?? 'trains'); ?>">
                            <div class="atc-similar-loading">Loading similar trains...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Sticky Booking Bar -->
            <div class="atc-trains-mobile-booking-bar">
                <div class="atc-mobile-booking-price">
                    <span class="atc-mobile-price-label">From</span>
                    <span class="atc-mobile-price-value"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                    <span class="atc-mobile-price-unit">/ person</span>
                </div>
                <button class="atc-mobile-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                    Book Now
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Cars Package Details - Premium Rental Style
     */
    public static function cars_package_details_shortcode($atts) {
        $atts = shortcode_atts([
            'package_id' => '',
        ], $atts);
        
        $package_id = !empty($atts['package_id']) ? intval($atts['package_id']) : (isset($_GET['package_id']) ? intval($_GET['package_id']) : 0);
        
        if (!$package_id) {
            return '<p class="atc-error">Package ID is required.</p>';
        }
        
        // Try to load package data immediately for server-side rendering
        global $wpdb;
        // Query by numeric id (package_id should match id after migration)
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " WHERE id = %d OR package_id = %s",
            $package_id,
            (string)$package_id
        ), ARRAY_A);
        
        if ($package) {
            $package = self::format_package_data($package);
            // Only render if status is active (for frontend) or allow all statuses for admin
            if ($package['status'] === 'active' || current_user_can('manage_options')) {
                ob_start();
                echo self::render_cars_package_details($package);
                return ob_get_clean();
            } else {
                return '<p class="atc-error">Package is not available.</p>';
            }
        }
        
        // Fallback to JS loading if package not found in database
        ob_start();
        ?>
        <div class="atc-package-details-cars-wrapper" data-package-id="<?php echo esc_attr($package_id); ?>">
            <div class="atc-loading-state">
                <div class="atc-spinner"></div>
                <p>Loading car details...</p>
            </div>
            <div class="atc-package-details-cars-content" style="display: none;">
                <!-- Package details will be loaded here by JS -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Cars Package Details (Premium Rental Style)
     */
    public static function render_cars_package_details($package) {
        $currency = get_option('atc_currency_symbol', '₹');
        $gallery_images = !empty($package['gallery_images']) ? $package['gallery_images'] : [];
        if (empty($gallery_images) && !empty($package['images'])) {
            $gallery_images = $package['images'];
        }
        
        // Get car-specific metadata
        $metadata = [];
        if (!empty($package['metadata'])) {
            $metadata = is_string($package['metadata']) ? json_decode($package['metadata'], true) : $package['metadata'];
        }
        
        $car_model = $metadata['car_model'] ?? ($package['name'] ?? '');
        $car_type = $metadata['car_type'] ?? '';
        $seating_capacity = $metadata['seating_capacity'] ?? '';
        $fuel_type = $metadata['fuel_type'] ?? '';
        $transmission = $metadata['transmission'] ?? '';
        $features = $metadata['features'] ?? [];
        $rental_type = $metadata['rental_type'] ?? '';
        $pricing_model = $metadata['pricing_model'] ?? '';
        $driver_included = $metadata['driver_included'] ?? '';
        $driver_charges = $metadata['driver_charges'] ?? '';
        $fuel_policy = $metadata['fuel_policy'] ?? '';
        $toll_charges = $metadata['toll_charges'] ?? '';
        $insurance_coverage = $metadata['insurance_coverage'] ?? '';
        
        // Parse features (can be string or array)
        if (is_string($features)) {
            $features = array_filter(array_map('trim', explode("\n", $features)));
        }
        if (!is_array($features)) {
            $features = [];
        }
        
        // Get service theme colors
        $theme = ATC_Service_Ecosystem::get_service_theme('cars');
        $colors = $theme['color_scheme'] ?? [
            'primary' => '#1a1a1a',
            'secondary' => '#333333',
            'accent' => '#ff6b35',
        ];
        
        ob_start();
        ?>
        <div class="atc-package-details-cars" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>; --atc-accent: <?php echo esc_attr($colors['accent']); ?>;">
            <!-- Breadcrumb Navigation -->
            <div class="atc-cars-breadcrumb">
                <div class="atc-cars-breadcrumb-inner">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                    <span class="atc-breadcrumb-separator">›</span>
                    <a href="<?php echo esc_url(home_url('/cars')); ?>">Car Rentals</a>
                    <span class="atc-breadcrumb-separator">›</span>
                    <span class="atc-breadcrumb-current"><?php echo esc_html($package['name']); ?></span>
                </div>
            </div>
            
            <!-- Hero Section with Image Gallery -->
            <div class="atc-cars-hero">
                <?php if (!empty($gallery_images) && is_array($gallery_images) && count($gallery_images) > 0): ?>
                    <div class="atc-cars-gallery">
                        <div class="atc-cars-main-image">
                            <img src="<?php echo esc_url($gallery_images[0]); ?>" alt="<?php echo esc_attr($package['name']); ?>" id="atc-cars-main-img">
                        </div>
                        <?php if (count($gallery_images) > 1): ?>
                            <div class="atc-cars-thumbnails">
                                <?php foreach (array_slice($gallery_images, 0, 5) as $index => $img): ?>
                                    <div class="atc-cars-thumb <?php echo $index === 0 ? 'active' : ''; ?>" data-image="<?php echo esc_url($img); ?>">
                                        <img src="<?php echo esc_url($img); ?>" alt="Gallery Image <?php echo $index + 1; ?>">
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($gallery_images) > 5): ?>
                                    <div class="atc-cars-thumb-more">
                                        <span>+<?php echo count($gallery_images) - 5; ?> more</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif (!empty($package['image_url'])): ?>
                    <div class="atc-cars-gallery">
                        <div class="atc-cars-main-image">
                            <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>">
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Car Info Header -->
                <div class="atc-cars-header">
                    <div class="atc-cars-header-content">
                        <h1 class="atc-cars-title"><?php echo esc_html($car_model ?: $package['name']); ?></h1>
                        <div class="atc-cars-meta">
                            <?php if (!empty($car_type)): ?>
                                <span class="atc-car-badge atc-car-type"><?php echo esc_html($car_type); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($rental_type)): ?>
                                <span class="atc-car-badge atc-rental-type"><?php echo esc_html($rental_type); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($seating_capacity)): ?>
                                <span class="atc-car-badge atc-seating">👥 <?php echo esc_html($seating_capacity); ?> Seats</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="atc-cars-content-wrapper">
                <!-- Left Column - Main Content -->
                <div class="atc-cars-main-content">
                    
                    <!-- Car Specifications Section -->
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">🚗 Car Specifications</h2>
                        <div class="atc-cars-specs-grid">
                            <?php if (!empty($car_type)): ?>
                                <div class="atc-car-spec-item">
                                    <div class="atc-spec-icon">🚗</div>
                                    <div class="atc-spec-content">
                                        <div class="atc-spec-label">Car Type</div>
                                        <div class="atc-spec-value"><?php echo esc_html($car_type); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($seating_capacity)): ?>
                                <div class="atc-car-spec-item">
                                    <div class="atc-spec-icon">👥</div>
                                    <div class="atc-spec-content">
                                        <div class="atc-spec-label">Seating Capacity</div>
                                        <div class="atc-spec-value"><?php echo esc_html($seating_capacity); ?> Seats</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($fuel_type)): ?>
                                <div class="atc-car-spec-item">
                                    <div class="atc-spec-icon">⛽</div>
                                    <div class="atc-spec-content">
                                        <div class="atc-spec-label">Fuel Type</div>
                                        <div class="atc-spec-value"><?php echo esc_html($fuel_type); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($transmission)): ?>
                                <div class="atc-car-spec-item">
                                    <div class="atc-spec-icon">⚙️</div>
                                    <div class="atc-spec-content">
                                        <div class="atc-spec-label">Transmission</div>
                                        <div class="atc-spec-value"><?php echo esc_html($transmission); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Car Features Section -->
                    <?php if (!empty($features) && is_array($features)): ?>
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">✨ Car Features</h2>
                        <div class="atc-cars-features-grid">
                            <?php foreach ($features as $feature): ?>
                                <div class="atc-cars-feature-item">
                                    <span class="atc-feature-icon">✓</span>
                                    <span class="atc-feature-text"><?php echo esc_html(trim($feature)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Rental Information Section -->
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">📋 Rental Information</h2>
                        <div class="atc-cars-rental-info">
                            <?php if (!empty($rental_type)): ?>
                                <div class="atc-rental-info-item">
                                    <span class="atc-info-label">Rental Type:</span>
                                    <span class="atc-info-value"><?php echo esc_html($rental_type); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($pricing_model)): ?>
                                <div class="atc-rental-info-item">
                                    <span class="atc-info-label">Pricing Model:</span>
                                    <span class="atc-info-value"><?php echo esc_html($pricing_model); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($driver_included)): ?>
                                <div class="atc-rental-info-item">
                                    <span class="atc-info-label">Driver:</span>
                                    <span class="atc-info-value"><?php echo esc_html($driver_included); ?><?php if (!empty($driver_charges) && $driver_included !== 'Yes'): ?> (<?php echo esc_html($driver_charges); ?>)<?php endif; ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($fuel_policy)): ?>
                                <div class="atc-rental-info-item">
                                    <span class="atc-info-label">Fuel Policy:</span>
                                    <span class="atc-info-value"><?php echo esc_html($fuel_policy); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($toll_charges)): ?>
                                <div class="atc-rental-info-item">
                                    <span class="atc-info-label">Toll Charges:</span>
                                    <span class="atc-info-value"><?php echo esc_html($toll_charges); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Car Description -->
                    <?php if (!empty($package['description'])): ?>
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">📖 About This Car</h2>
                        <div class="atc-cars-description">
                            <?php echo wp_kses_post(nl2br($package['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Highlights -->
                    <?php if (!empty($package['highlights']) && is_array($package['highlights'])): ?>
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">⭐ Car Highlights</h2>
                        <div class="atc-cars-highlights">
                            <?php foreach ($package['highlights'] as $highlight): ?>
                                <div class="atc-cars-highlight-item">
                                    <span class="atc-highlight-icon">✓</span>
                                    <span class="atc-highlight-text"><?php echo esc_html(trim($highlight)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Insurance Coverage -->
                    <?php if (!empty($insurance_coverage)): ?>
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">🛡️ Insurance Coverage</h2>
                        <div class="atc-cars-insurance">
                            <?php echo wp_kses_post(nl2br($insurance_coverage)); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Cancellation Policy -->
                    <?php if (!empty($package['cancellation_policy'])): ?>
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">🚫 Cancellation Policy</h2>
                        <div class="atc-cars-cancellation">
                            <?php echo wp_kses_post(nl2br($package['cancellation_policy'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Terms & Conditions -->
                    <?php if (!empty($package['terms_conditions'])): ?>
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">📜 Terms & Conditions</h2>
                        <div class="atc-cars-terms">
                            <?php echo wp_kses_post(nl2br($package['terms_conditions'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Right Column - Booking Card (Sticky) -->
                <div class="atc-cars-booking-card">
                    <div class="atc-cars-price-header">
                        <div class="atc-cars-price-label">Starting from</div>
                        <div class="atc-cars-price-main">
                            <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                                <span class="atc-cars-price-original"><?php echo esc_html($currency . number_format($package['original_price'])); ?></span>
                            <?php endif; ?>
                            <span class="atc-cars-price-current"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                            <span class="atc-cars-price-unit">/ day</span>
                        </div>
                        <?php if (!empty($package['discount'])): ?>
                            <span class="atc-cars-discount-badge"><?php echo esc_html($package['discount']); ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="atc-cars-trust-badges">
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">🔒</span>
                            <span>Secure Booking</span>
                        </div>
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">✓</span>
                            <span>Verified Fleet</span>
                        </div>
                        <div class="atc-trust-badge">
                            <span class="atc-badge-icon">🚗</span>
                            <span>Best Price</span>
                        </div>
                    </div>
                    
                    <button class="atc-cars-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                        <span>Book Now</span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <!-- Ask for More Details Button -->
                    <?php echo do_shortcode('[atc_query_form service="' . esc_attr($package['service_key'] ?? 'cars') . '" button_text="Ask for More Details"]'); ?>
                </div>
            </div>
            
            <!-- Similar Cars Section -->
            <div class="atc-cars-similar">
                <div class="atc-cars-content-wrapper">
                    <div class="atc-cars-section">
                        <h2 class="atc-cars-section-title">🚗 Similar Cars</h2>
                        <div class="atc-similar-cars-grid" data-package-id="<?php echo esc_attr($package['id']); ?>" data-service="<?php echo esc_attr($package['service_key'] ?? 'cars'); ?>">
                            <div class="atc-similar-loading">Loading similar cars...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Sticky Booking Bar -->
            <div class="atc-cars-mobile-booking-bar">
                <div class="atc-mobile-booking-price">
                    <span class="atc-mobile-price-label">From</span>
                    <span class="atc-mobile-price-value"><?php echo esc_html($currency . number_format($package['price'])); ?></span>
                    <span class="atc-mobile-price-unit">/ day</span>
                </div>
                <button class="atc-mobile-booking-btn" data-package-id="<?php echo esc_attr($package['id']); ?>" data-action="book-now">
                    Book Now
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Default package details (fallback)
     */
    public static function default_package_details_shortcode($atts) {
        // Use existing implementation
        if (class_exists('ATC_Package_Details')) {
            return ATC_Package_Details::package_details_shortcode($atts);
        }
        
        return '<p class="atc-error">Package details system not available.</p>';
    }
}

