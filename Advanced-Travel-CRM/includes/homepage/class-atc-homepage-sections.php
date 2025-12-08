<?php
/**
 * ATC Homepage Sections
 * Services, Featured Packages, Why Choose Us, Testimonials, Statistics, CTA
 */

if (!defined('ABSPATH')) exit;

class ATC_Homepage_Sections {
    
    public static function init() {
        add_shortcode('atc_homepage_services', [__CLASS__, 'services_section']);
        add_shortcode('atc_homepage_featured_packages', [__CLASS__, 'featured_packages_section']);
        add_shortcode('atc_homepage_why_choose_us', [__CLASS__, 'why_choose_us_section']);
        add_shortcode('atc_homepage_testimonials', [__CLASS__, 'testimonials_section']);
        add_shortcode('atc_homepage_statistics', [__CLASS__, 'statistics_section']);
        add_shortcode('atc_homepage_cta', [__CLASS__, 'cta_section']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function enqueue_assets() {
        // Check if constants exist
        $assets_url = defined('ATC_ASSETS_URL') ? ATC_ASSETS_URL : plugin_dir_url(__FILE__) . '../../assets/';
        $version = defined('ATC_VERSION') ? ATC_VERSION : '2.4.0';
        
        wp_enqueue_style('atc-homepage', $assets_url . 'css/atc-homepage.css', [], $version);
    }
    
    /**
     * Services Section
     */
    public static function services_section($atts) {
        $atts = shortcode_atts([
            'title' => 'Our Services',
            'subtitle' => 'Comprehensive travel solutions for all your needs',
            'columns' => '3',
        ], $atts);
        
        ob_start();
        ?>
        <section class="atc-homepage-services">
            <div class="atc-section-container">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p><?php echo esc_html($atts['subtitle']); ?></p>
                <?php 
                // Check if shortcode exists before calling
                if (shortcode_exists('atc_service_grid')) {
                    echo do_shortcode('[atc_service_grid columns="' . esc_attr($atts['columns']) . '"]');
                } else {
                    echo '<p>Services not available.</p>';
                }
                ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Featured Packages Section
     */
    public static function featured_packages_section($atts) {
        $atts = shortcode_atts([
            'service' => 'tours',
            'title' => 'Featured Packages',
            'columns' => '4',
            'per_page' => '8',
        ], $atts);
        
        ob_start();
        ?>
        <section class="atc-homepage-featured">
            <div class="atc-section-container">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <div class="atc-featured-packages-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 50px;">
                    <?php
                    $packages = self::get_featured_packages($atts['service'], intval($atts['per_page']));
                    foreach ($packages as $package) {
                        self::render_package_card($package);
                    }
                    ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Why Choose Us Section
     */
    public static function why_choose_us_section($atts) {
        $atts = shortcode_atts([
            'title' => 'Why Choose Us',
            'columns' => '4',
        ], $atts);
        
        $features = [
            ['icon' => '✈️', 'title' => 'Expert Service', 'description' => 'Years of experience in travel planning'],
            ['icon' => '🌍', 'title' => 'Global Network', 'description' => 'Connections worldwide for best deals'],
            ['icon' => '💎', 'title' => 'Premium Quality', 'description' => 'Only the best travel experiences'],
            ['icon' => '📞', 'title' => '24/7 Support', 'description' => 'Always here when you need us'],
        ];
        
        ob_start();
        ?>
        <section class="atc-homepage-why">
            <div class="atc-section-container">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <div class="atc-features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 50px;">
                    <?php 
                    if (is_array($features) && !empty($features)) {
                        foreach ($features as $feature): 
                            if (!is_array($feature) || !isset($feature['title'])) {
                                continue;
                            }
                    ?>
                        <div class="atc-feature-card">
                        <div class="atc-feature-icon"><?php echo esc_html($feature['icon'] ?? '⭐'); ?></div>
                        <h3><?php echo esc_html($feature['title']); ?></h3>
                        <p><?php echo esc_html($feature['description'] ?? ''); ?></p>
                    </div>
                    <?php 
                        endforeach;
                    }
                    ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Testimonials Section
     */
    public static function testimonials_section($atts) {
        $atts = shortcode_atts([
            'title' => 'What Our Customers Say',
            'per_page' => '6',
        ], $atts);
        
        // Get testimonials from database or use defaults
        $testimonials = self::get_testimonials(intval($atts['per_page']));
        
        ob_start();
        ?>
        <section class="atc-homepage-testimonials">
            <div class="atc-section-container">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <div class="atc-testimonials-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 50px;">
                    <?php 
                    if (is_array($testimonials) && !empty($testimonials)) {
                        foreach ($testimonials as $testimonial): 
                            if (!is_array($testimonial) || !isset($testimonial['text'])) {
                                continue;
                            }
                            $rating = isset($testimonial['rating']) ? intval($testimonial['rating']) : 5;
                            if ($rating < 1) $rating = 1;
                            if ($rating > 5) $rating = 5;
                    ?>
                    <div class="atc-testimonial-card">
                        <div class="atc-testimonial-rating"><?php echo str_repeat('⭐', $rating); ?></div>
                        <p class="atc-testimonial-text">"<?php echo esc_html($testimonial['text']); ?>"</p>
                        <p class="atc-testimonial-author">— <?php echo esc_html($testimonial['author'] ?? 'Customer'); ?></p>
                    </div>
                    <?php 
                        endforeach;
                    }
                    ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Statistics Section
     */
    public static function statistics_section($atts) {
        $atts = shortcode_atts([
            'customers' => '10000+',
            'packages' => '500+',
            'destinations' => '50+',
            'experience' => '10+',
        ], $atts);
        
        $stats = [
            ['number' => $atts['customers'], 'label' => 'Happy Customers'],
            ['number' => $atts['packages'], 'label' => 'Packages'],
            ['number' => $atts['destinations'], 'label' => 'Destinations'],
            ['number' => $atts['experience'], 'label' => 'Years Experience'],
        ];
        
        ob_start();
        ?>
        <section class="atc-homepage-statistics">
            <div class="atc-section-container">
                <div class="atc-statistics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-top: 50px;">
                    <?php foreach ($stats as $stat): ?>
                    <div class="atc-stat-card">
                        <div class="atc-stat-number"><?php echo esc_html($stat['number']); ?></div>
                        <div class="atc-stat-label"><?php echo esc_html($stat['label']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * CTA Section
     */
    public static function cta_section($atts) {
        $atts = shortcode_atts([
            'title' => 'Ready to Plan Your Trip?',
            'subtitle' => 'Contact us today for personalized travel solutions',
            'show_form' => 'true',
        ], $atts);
        
        ob_start();
        ?>
        <section class="atc-homepage-cta" id="contact">
            <div class="atc-section-container">
                <div class="atc-cta-content">
                    <h2><?php echo esc_html($atts['title']); ?></h2>
                    <p><?php echo esc_html($atts['subtitle']); ?></p>
                    <?php if ($atts['show_form'] === 'true'): ?>
                        <?php 
                        // Check if shortcode exists before calling
                        if (shortcode_exists('atc_query_form')) {
                            echo do_shortcode('[atc_query_form]');
                        } else {
                            echo '<a href="#contact" class="atc-btn-primary">Get In Touch</a>';
                        }
                        ?>
                    <?php else: ?>
                        <a href="#contact" class="atc-btn-primary">Get In Touch</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get Featured Packages
     */
    private static function get_featured_packages($service, $limit = 8) {
        global $wpdb;
        
        // Check if table constant exists
        if (!defined('ATC_TABLE_CUSTOM_PACKAGES')) {
            return self::get_sample_packages($service, $limit);
        }
        
        $table = ATC_TABLE_CUSTOM_PACKAGES;
        
        // Check if table exists - use safe method with error handling
        try {
            $table_check = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if (!$table_check) {
                return self::get_sample_packages($service, $limit);
            }
            
            // Try to get packages with featured column first
            $packages = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM `{$table}` WHERE service = %s AND featured = 1 ORDER BY created_at DESC LIMIT %d",
                $service,
                $limit
            ));
            
            // If no featured packages found, try without featured filter
            if (empty($packages)) {
                $packages = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM `{$table}` WHERE service = %s ORDER BY created_at DESC LIMIT %d",
                    $service,
                    $limit
                ));
            }
        } catch (Exception $e) {
            // Return sample packages on error
            return self::get_sample_packages($service, $limit);
        } catch (Error $e) {
            // Return sample packages on error
            return self::get_sample_packages($service, $limit);
        }
        
        if (empty($packages)) {
            // Return sample packages if none found
            return self::get_sample_packages($service, $limit);
        }
        
        return $packages;
    }
    
    /**
     * Get Sample Packages (fallback)
     */
    private static function get_sample_packages($service, $limit) {
        $samples = [];
        for ($i = 1; $i <= $limit; $i++) {
            $samples[] = (object)[
                'id' => $i,
                'title' => ucfirst($service) . ' Package ' . $i,
                'description' => 'Amazing ' . $service . ' experience',
                'price' => (1000 + $i * 100),
                'image' => '',
            ];
        }
        return $samples;
    }
    
    /**
     * Render Package Card
     */
    private static function render_package_card($package) {
        // Validate package object
        if (!is_object($package) && !is_array($package)) {
            return;
        }
        
        // Convert array to object if needed
        if (is_array($package)) {
            $package = (object) $package;
        }
        
        // Ensure required properties exist
        $package->id = isset($package->id) ? $package->id : 0;
        $package->title = isset($package->title) ? $package->title : 'Package';
        $package->description = isset($package->description) ? $package->description : '';
        $package->price = isset($package->price) ? $package->price : 0;
        $package->image = isset($package->image) ? $package->image : '';
        
        ?>
        <div class="atc-package-card">
            <?php if (!empty($package->image)): ?>
                <img src="<?php echo esc_url($package->image); ?>" alt="<?php echo esc_attr($package->title); ?>" class="atc-package-image">
            <?php endif; ?>
            <div class="atc-package-content">
                <h3><?php echo esc_html($package->title); ?></h3>
                <p><?php echo esc_html($package->description); ?></p>
                <div class="atc-package-price">₹<?php echo number_format(floatval($package->price)); ?></div>
                <div class="atc-package-button">
                    <?php
                    $permalink = get_permalink();
                    if ($package->id > 0) {
                        $permalink = add_query_arg('package_id', intval($package->id), $permalink);
                    }
                    ?>
                    <a href="<?php echo esc_url($permalink); ?>" class="atc-btn-primary">View Details</a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get Testimonials
     */
    private static function get_testimonials($limit = 6) {
        // Get from database or return defaults
        return [
            ['rating' => 5, 'text' => 'Amazing service! They made our honeymoon unforgettable.', 'author' => 'Raj & Priya'],
            ['rating' => 5, 'text' => 'Best travel agency we\'ve worked with. Highly recommended!', 'author' => 'Amit Sharma'],
            ['rating' => 5, 'text' => 'Professional, reliable, and always available. Great experience!', 'author' => 'Sneha Patel'],
            ['rating' => 5, 'text' => 'They planned our entire Europe trip perfectly. Thank you!', 'author' => 'Vikram Singh'],
            ['rating' => 5, 'text' => 'Excellent customer service and great package deals.', 'author' => 'Anjali Mehta'],
            ['rating' => 5, 'text' => 'Made our family vacation stress-free and enjoyable.', 'author' => 'Rohit & Family'],
        ];
    }
}

ATC_Homepage_Sections::init();

