<?php
/**
 * ATC Service Grid Shortcode
 * Premium service category grid with icons
 * Usage: [atc_service_grid] or [atc_service_grid title="Our Services" description="Choose your service"]
 */

if (!defined('ABSPATH')) exit;

class ATC_Service_Grid {
    
    public static function init() {
        add_shortcode('atc_service_grid', [__CLASS__, 'service_grid_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function enqueue_assets() {
        wp_enqueue_style('atc-service-grid', ATC_ASSETS_URL . 'css/atc-service-grid.css', [], ATC_VERSION);
    }
    
    /**
     * Service grid shortcode
     */
    public static function service_grid_shortcode($atts) {
        $atts = shortcode_atts([
            'title' => 'Our Services',
            'description' => 'Explore our wide range of travel services',
            'show_description' => 'false',
            'columns' => 'auto', // auto, 2, 3, 4, 5, 6, 7
            'class' => '', // Additional CSS classes
        ], $atts);
        
        // Get all enabled services
        $services = [];
        if (class_exists('ATC_Services')) {
            $services = ATC_Services::get_services();
        }
        
        if (empty($services)) {
            return '<p class="atc-service-grid-empty">No services available.</p>';
        }
        
        // Service icons mapping
        $service_icons = [
            'tours' => '🏖️',
            'hotels' => '🏨',
            'flights' => '✈️',
            'trains' => '🚂',
            'cars' => '🚗',
            'forex' => '💱',
            'visa' => '🛂',
            'celebrity' => '⭐',
        ];
        
        // Service descriptions
        $service_descriptions = [
            'tours' => 'Explore amazing destinations',
            'hotels' => 'Book your perfect stay',
            'flights' => 'Fly to your dream destination',
            'trains' => 'Comfortable train journeys',
            'cars' => 'Rent a car for your trip',
            'forex' => 'Currency exchange services',
            'visa' => 'Visa assistance & processing',
            'celebrity' => 'Professional celebrity management services',
        ];
        
        // Get service icon and label customizations
        $service_icons_custom = [];
        $service_labels_custom = [];
        if (class_exists('ATC_Service_Manager')) {
            $service_icons_custom = ATC_Service_Manager::get_service_icons();
            $service_labels_custom = ATC_Service_Manager::get_service_labels();
        }
        
        // Get service landing pages
        $service_pages = [];
        foreach ($services as $key => $service) {
            $page_slug = $service['page_slug'] ?? $key;
            
            // Try to find page by slug
            $page = get_page_by_path($page_slug);
            if (!$page) {
                // Try alternative slugs (backward compatibility with singular forms)
                $alternatives = [
                    'tours' => ['tour', 'tour-packages', 'tour-package'],
                    'hotels' => ['hotel', 'hotel-booking'],
                    'flights' => ['flight', 'flight-booking'],
                    'trains' => ['train', 'train-booking'],
                    'cars' => ['car', 'car-rental', 'car-rentals'],
                    'forex' => ['forex-services', 'currency-exchange'],
                    'visa' => ['visa-services', 'visa-assistance'],
                    'celebrity-management' => ['celebrity', 'celebrity-management', 'celebrity-services'],
                ];
                
                if (isset($alternatives[$page_slug])) {
                    foreach ($alternatives[$page_slug] as $alt_slug) {
                        $page = get_page_by_path($alt_slug);
                        if ($page) break;
                    }
                }
            }
            
            $service_pages[$key] = $page ? get_permalink($page->ID) : home_url('/' . $page_slug . '/');
        }
        
        ob_start();
        ?>
        <div class="atc-service-grid <?php echo esc_attr($atts['class']); ?>" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <?php if (!empty($atts['title']) || !empty($atts['description'])): ?>
                <div class="atc-service-grid-header">
                    <?php if (!empty($atts['title'])): ?>
                        <h2><?php echo esc_html($atts['title']); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($atts['description'])): ?>
                        <p><?php echo esc_html($atts['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="atc-service-grid-wrapper">
                <?php foreach ($services as $key => $service): 
                    // Check for custom icon from service icons option
                    $custom_icon = isset($service_icons_custom[$key]) ? $service_icons_custom[$key] : null;
                    $icon = $custom_icon && !empty($custom_icon['icon']) ? $custom_icon['icon'] : ($service['icon'] ?? ($service_icons[$key] ?? '📦'));
                    $icon_image = $custom_icon && !empty($custom_icon['icon_image']) ? $custom_icon['icon_image'] : (isset($service['icon_image']) ? $service['icon_image'] : '');
                    // Use custom label if available, otherwise use service label
                    $label = isset($service_labels_custom[$key]) ? $service_labels_custom[$key] : ($service['label'] ?? ucfirst($key));
                    $description = $service_descriptions[$key] ?? '';
                    $url = $service_pages[$key] ?? home_url('/' . $key . '/');
                    
                    // Check if custom icon image is provided
                    $has_custom_icon = !empty($icon_image);
                    
                    // Convert icon name to emoji if needed (only if no custom image)
                    if (!$has_custom_icon) {
                        if (strlen($icon) <= 4 && !preg_match('/[a-zA-Z]/', $icon)) {
                            // Already an emoji
                        } else {
                            // Icon name, convert to emoji
                            $icon_map = [
                                'palmtree' => '🏖️',
                                'building' => '🏨',
                                'airplane' => '✈️',
                                'train' => '🚂',
                                'car' => '🚗',
                                'currency' => '💱',
                                'passport' => '🛂',
                                'star' => '⭐',
                            ];
                            $icon = $icon_map[$icon] ?? '📦';
                        }
                    }
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="atc-service-card" data-service="<?php echo esc_attr($key); ?>">
                        <div class="atc-service-icon">
                            <?php if ($has_custom_icon): ?>
                                <img src="<?php echo esc_url($icon_image); ?>" alt="<?php echo esc_attr($label); ?>" class="atc-service-icon-image" />
                            <?php else: ?>
                                <?php echo esc_html($icon); ?>
                            <?php endif; ?>
                        </div>
                        <h3 class="atc-service-name"><?php echo esc_html($label); ?></h3>
                        <?php if ($atts['show_description'] === 'true' && !empty($description)): ?>
                            <p class="atc-service-description"><?php echo esc_html($description); ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

