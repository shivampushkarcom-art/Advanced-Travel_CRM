<?php
/**
 * ATC Base Landing Page Class
 * Template for all service landing pages
 */

if (!defined('ABSPATH')) exit;

class ATC_Base_Landing {
    
    /**
     * Render Landing Page
     */
    public static function render($service_key, $service_name, $atts = []) {
        // Validate inputs
        if (empty($service_key) || empty($service_name)) {
            return '<p>Invalid service parameters.</p>';
        }
        
        $atts = shortcode_atts([
            'show_search' => 'true',
            'show_packages' => 'true',
            'show_testimonials' => 'true',
        ], $atts);
        
        // Get config with error handling
        $config = [];
        try {
            $config = self::get_service_config($service_key);
        } catch (Exception $e) {
            // Use defaults
        } catch (Error $e) {
            // Use defaults
        }
        
        $colors = [];
        if (isset($config['theme']['color_scheme']) && is_array($config['theme']['color_scheme'])) {
            $colors = $config['theme']['color_scheme'];
        } else {
            $colors = [
                'primary' => '#1E3A5F',
                'secondary' => '#D4AF37',
            ];
        }
        
        ob_start();
        ?>
        <div class="atc-landing-page" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>;">
            
            <!-- Hero Section -->
            <section class="atc-landing-hero">
                <div class="atc-hero-content">
                    <h1 class="atc-hero-title"><?php echo esc_html($service_name); ?></h1>
                    <p class="atc-hero-subtitle"><?php echo esc_html($config['description'] ?? 'Premium ' . $service_name . ' services'); ?></p>
                    <div class="atc-hero-cta">
                        <a href="#search" class="atc-btn-primary">Search Now</a>
                        <a href="#packages" class="atc-btn-secondary">View Packages</a>
                    </div>
                </div>
            </section>
            
            <?php if ($atts['show_search'] === 'true'): ?>
            <!-- Search Section -->
            <section id="search" class="atc-landing-search">
                <div class="atc-section-container">
                    <?php 
                    // Check if shortcode exists before calling
                    if (shortcode_exists('atc_premium_search')) {
                        echo do_shortcode('[atc_premium_search service="' . esc_attr($service_key) . '"]');
                    } else {
                        echo '<p>Search functionality not available.</p>';
                    }
                    ?>
                </div>
            </section>
            <?php endif; ?>
            
            <?php if ($atts['show_packages'] === 'true'): ?>
            <!-- Featured Packages -->
            <section id="packages" class="atc-landing-packages">
                <div class="atc-section-container">
                    <h2 class="atc-section-title">Featured <?php echo esc_html($service_name); ?> Packages</h2>
                    <?php 
                    // Check if shortcode exists before calling
                    if (shortcode_exists('atc_featured_packages')) {
                        echo do_shortcode('[atc_featured_packages service="' . esc_attr($service_key) . '" columns="3" per_page="6"]');
                    } else {
                        echo '<p>Packages not available.</p>';
                    }
                    ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Service Features -->
            <section class="atc-landing-features">
                <div class="atc-section-container">
                    <h2 class="atc-section-title">Why Choose Our <?php echo esc_html($service_name); ?> Services</h2>
                    <div class="atc-features-grid">
                        <?php
                        $features = self::get_service_features($service_key);
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
            
            <?php if ($atts['show_testimonials'] === 'true'): ?>
            <!-- Testimonials -->
            <section class="atc-landing-testimonials">
                <div class="atc-section-container">
                    <h2 class="atc-section-title">Customer Reviews</h2>
                    <div class="atc-testimonials-grid">
                        <?php
                        $testimonials = self::get_service_testimonials($service_key);
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
            <?php endif; ?>
            
            <!-- Contact Section -->
            <section class="atc-landing-contact">
                <div class="atc-section-container">
                    <h2 class="atc-section-title">Get In Touch</h2>
                    <p class="atc-section-subtitle">Have questions about our <?php echo esc_html(strtolower($service_name)); ?> services? Contact us today!</p>
                    <?php 
                    // Check if shortcode exists before calling
                    if (shortcode_exists('atc_query_form')) {
                        echo do_shortcode('[atc_query_form service="' . esc_attr($service_key) . '" button_text="Send Inquiry"]');
                    } else {
                        echo '<p>Contact form not available. Please contact us directly.</p>';
                    }
                    ?>
                </div>
            </section>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get Service Config
     */
    private static function get_service_config($service_key) {
        // Try to use Config Loader if available
        if (class_exists('ATC_Config_Loader')) {
            try {
                return ATC_Config_Loader::load_service_config($service_key);
            } catch (Exception $e) {
                // Fall back to file reading
            } catch (Error $e) {
                // Fall back to file reading
            }
        }
        
        // Fallback to file reading
        $services_dir = defined('ATC_SERVICES_DIR') ? ATC_SERVICES_DIR : plugin_dir_path(__FILE__) . '../../../services/';
        $config_file = $services_dir . $service_key . '.json';
        
        if (file_exists($config_file) && is_readable($config_file)) {
            $content = @file_get_contents($config_file);
            if ($content !== false) {
                $config = @json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($config)) {
                    return $config;
                }
            }
        }
        return [];
    }
    
    /**
     * Get Service Features
     */
    private static function get_service_features($service_key) {
        $features_map = [
            'tours' => [
                ['icon' => '🗺️', 'title' => 'Expert Guides', 'description' => 'Professional tour guides for the best experience'],
                ['icon' => '🏨', 'title' => 'Quality Stays', 'description' => 'Carefully selected accommodations'],
                ['icon' => '🚌', 'title' => 'Comfortable Transport', 'description' => 'Safe and comfortable travel'],
                ['icon' => '📅', 'title' => 'Flexible Dates', 'description' => 'Choose dates that work for you'],
            ],
            'hotels' => [
                ['icon' => '⭐', 'title' => 'Verified Hotels', 'description' => 'All hotels verified for quality'],
                ['icon' => '💰', 'title' => 'Best Prices', 'description' => 'Competitive rates guaranteed'],
                ['icon' => '🔄', 'title' => 'Easy Cancellation', 'description' => 'Flexible cancellation policies'],
                ['icon' => '📞', 'title' => '24/7 Support', 'description' => 'Round-the-clock assistance'],
            ],
            'flights' => [
                ['icon' => '✈️', 'title' => 'Best Deals', 'description' => 'Lowest prices on flights'],
                ['icon' => '🔄', 'title' => 'Multiple Airlines', 'description' => 'Compare all major airlines'],
                ['icon' => '💺', 'title' => 'Seat Selection', 'description' => 'Choose your preferred seats'],
                ['icon' => '🎫', 'title' => 'Instant Booking', 'description' => 'Book and confirm instantly'],
            ],
            'trains' => [
                ['icon' => '🚂', 'title' => 'All Classes', 'description' => 'AC, Sleeper, and more'],
                ['icon' => '📅', 'title' => 'Advance Booking', 'description' => 'Book up to 120 days in advance'],
                ['icon' => '💳', 'title' => 'Easy Payment', 'description' => 'Multiple payment options'],
                ['icon' => '📱', 'title' => 'E-Ticket', 'description' => 'Instant e-ticket delivery'],
            ],
            'cars' => [
                ['icon' => '🚗', 'title' => 'Wide Selection', 'description' => 'Economy to luxury vehicles'],
                ['icon' => '🛡️', 'title' => 'Fully Insured', 'description' => 'Comprehensive insurance coverage'],
                ['icon' => '📍', 'title' => 'Multiple Locations', 'description' => 'Pickup and drop anywhere'],
                ['icon' => '⛽', 'title' => 'Fuel Included', 'description' => 'Full tank included'],
            ],
            'forex' => [
                ['icon' => '💱', 'title' => 'Best Rates', 'description' => 'Competitive exchange rates'],
                ['icon' => '🌍', 'title' => 'All Currencies', 'description' => 'Wide range of currencies'],
                ['icon' => '🔒', 'title' => 'Secure', 'description' => 'Safe and secure transactions'],
                ['icon' => '⚡', 'title' => 'Quick Service', 'description' => 'Fast currency exchange'],
            ],
            'visa' => [
                ['icon' => '📋', 'title' => 'Expert Guidance', 'description' => 'Professional visa assistance'],
                ['icon' => '✅', 'title' => 'High Success Rate', 'description' => 'Proven track record'],
                ['icon' => '📄', 'title' => 'Document Help', 'description' => 'Complete document support'],
                ['icon' => '⏱️', 'title' => 'Fast Processing', 'description' => 'Quick visa processing'],
            ],
        ];
        
        return $features_map[$service_key] ?? [
            ['icon' => '⭐', 'title' => 'Quality Service', 'description' => 'Premium service guaranteed'],
            ['icon' => '💎', 'title' => 'Expert Team', 'description' => 'Experienced professionals'],
            ['icon' => '🌍', 'title' => 'Global Reach', 'description' => 'Worldwide coverage'],
            ['icon' => '📞', 'title' => '24/7 Support', 'description' => 'Always available'],
        ];
    }
    
    /**
     * Get Service Testimonials
     */
    private static function get_service_testimonials($service_key) {
        return [
            ['rating' => 5, 'text' => 'Excellent ' . $service_key . ' service! Highly recommended.', 'author' => 'Happy Customer'],
            ['rating' => 5, 'text' => 'Great experience with their ' . $service_key . ' services.', 'author' => 'Satisfied Client'],
            ['rating' => 5, 'text' => 'Professional and reliable service for ' . $service_key . '.', 'author' => 'Valued Customer'],
        ];
    }
}

