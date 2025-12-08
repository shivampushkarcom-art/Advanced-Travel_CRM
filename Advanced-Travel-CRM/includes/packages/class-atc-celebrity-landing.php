<?php
/**
 * ATC Celebrity Management Landing Page
 * Branding-focused landing page for celebrity management service
 */

if (!defined('ABSPATH')) exit;

class ATC_Celebrity_Landing {
    
    public static function init() {
        add_shortcode('atc_celebrity_landing', [__CLASS__, 'celebrity_landing_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function get_custom_content() {
        if (class_exists('ATC_Celebrity_Customizer')) {
            return ATC_Celebrity_Customizer::get_content();
        }
        // Fallback to default content structure
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
                    ['icon' => '🎭', 'title' => 'Event Management', 'description' => 'Complete celebrity management for corporate events, weddings, product launches, and more.', 'image' => ''],
                    ['icon' => '📸', 'title' => 'Brand Endorsements', 'description' => 'Connect your brand with the right celebrity for authentic endorsements and partnerships.', 'image' => ''],
                    ['icon' => '🎤', 'title' => 'Concert & Shows', 'description' => 'Professional management for concerts, shows, and entertainment events.', 'image' => ''],
                    ['icon' => '🤝', 'title' => 'Partnerships', 'description' => 'Strategic celebrity partnerships for long-term brand associations.', 'image' => ''],
                ],
            ],
            'why_choose_us' => [
                'title' => 'Why Choose Us',
                'features' => [
                    ['icon' => '✨', 'title' => 'Experienced Team', 'description' => 'Years of experience in celebrity management and event coordination.', 'image' => ''],
                    ['icon' => '🌟', 'title' => 'Wide Network', 'description' => 'Extensive network of celebrities across various industries and genres.', 'image' => ''],
                    ['icon' => '💼', 'title' => 'Professional Service', 'description' => 'End-to-end management from planning to execution.', 'image' => ''],
                    ['icon' => '🎯', 'title' => 'Custom Solutions', 'description' => 'Tailored solutions to meet your specific requirements and budget.', 'image' => ''],
                ],
            ],
            'contact' => [
                'title' => 'Get In Touch',
                'subtitle' => 'Ready to work with us? Contact us today to discuss your celebrity management needs.',
            ],
        ];
    }
    
    public static function enqueue_assets() {
        wp_enqueue_style('atc-global-premium', ATC_ASSETS_URL . 'css/atc-global-premium.css', [], ATC_VERSION);
        wp_enqueue_style('atc-celebrity-landing', ATC_ASSETS_URL . 'css/atc-celebrity-landing.css', ['atc-global-premium'], ATC_VERSION);
        // Cursor script removed per user request
    }
    
    /**
     * Celebrity Management Landing Page Shortcode
     * Usage: [atc_celebrity_landing]
     */
    public static function celebrity_landing_shortcode($atts) {
        $atts = shortcode_atts([
            'show_contact_form' => 'true',
        ], $atts);
        
        // Get colors with fallback
        $colors = [
            'primary' => '#1E3A5F',  // Blue for buttons/accents
            'secondary' => '#D4AF37', // Gold for backgrounds
            'accent' => '#F4D03F',    // Light gold
        ];
        
        // Try to get from config if classes exist
        if (class_exists('ATC_Config_Loader') && class_exists('ATC_Service_Ecosystem')) {
            try {
                $config = ATC_Config_Loader::load_service_config('celebrity');
                $theme = ATC_Service_Ecosystem::get_service_theme('celebrity');
                if (isset($theme['color_scheme'])) {
                    $colors = $theme['color_scheme'];
                }
            } catch (Exception $e) {
                // Use defaults if error
            }
        }
        
        // Get customized content
        $content = self::get_custom_content();
        
        ob_start();
        ?>
        <div class="atc-celebrity-landing" style="--atc-primary: <?php echo esc_attr($colors['primary']); ?>; --atc-secondary: <?php echo esc_attr($colors['secondary']); ?>; --atc-accent: <?php echo esc_attr($colors['accent']); ?>;">
            
            <!-- Hero Section -->
            <section class="atc-celebrity-hero" <?php if (!empty($content['hero']['background_image'])): ?>style="background-image: url('<?php echo esc_url($content['hero']['background_image']); ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
                <div class="atc-hero-content">
                    <h1 class="atc-hero-title"><?php echo esc_html($content['hero']['title']); ?></h1>
                    <p class="atc-hero-subtitle"><?php echo esc_html($content['hero']['subtitle']); ?></p>
                    <div class="atc-hero-cta">
                        <a href="#contact" class="atc-btn-primary"><?php echo esc_html($content['hero']['primary_button_text']); ?></a>
                        <a href="#services" class="atc-btn-secondary"><?php echo esc_html($content['hero']['secondary_button_text']); ?></a>
                    </div>
                </div>
            </section>
            
            <!-- Services Section -->
            <section id="services" class="atc-celebrity-services">
                <div class="atc-section-container">
                    <h2 class="atc-section-title"><?php echo esc_html($content['services']['title']); ?></h2>
                    <div class="atc-services-grid">
                        <?php foreach ($content['services']['items'] as $item): ?>
                            <div class="atc-service-item">
                                <?php if (!empty($item['image'])): ?>
                                    <div class="atc-service-image">
                                        <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
                                    </div>
                                <?php else: ?>
                                    <div class="atc-service-icon"><?php echo esc_html($item['icon']); ?></div>
                                <?php endif; ?>
                                <h3><?php echo esc_html($item['title']); ?></h3>
                                <p><?php echo esc_html($item['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            
            <!-- Why Choose Us Section -->
            <section class="atc-celebrity-why">
                <div class="atc-section-container">
                    <h2 class="atc-section-title"><?php echo esc_html($content['why_choose_us']['title']); ?></h2>
                    <div class="atc-features-grid">
                        <?php foreach ($content['why_choose_us']['features'] as $feature): ?>
                            <div class="atc-feature-item">
                                <?php if (!empty($feature['image'])): ?>
                                    <div class="atc-feature-image">
                                        <img src="<?php echo esc_url($feature['image']); ?>" alt="<?php echo esc_attr($feature['title']); ?>" />
                                    </div>
                                <?php endif; ?>
                                <h3><?php echo esc_html($feature['icon'] . ' ' . $feature['title']); ?></h3>
                                <p><?php echo esc_html($feature['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            
            <!-- Contact Section -->
            <section id="contact" class="atc-celebrity-contact">
                <div class="atc-section-container">
                    <h2 class="atc-section-title"><?php echo esc_html($content['contact']['title']); ?></h2>
                    <p class="atc-section-subtitle"><?php echo esc_html($content['contact']['subtitle']); ?></p>
                    
                    <?php if ($atts['show_contact_form'] === 'true'): ?>
                        <div class="atc-contact-form-wrapper">
                            <?php echo do_shortcode('[atc_query_form service="celebrity" button_text="Send Inquiry"]'); ?>
                        </div>
                    <?php else: ?>
                        <div class="atc-contact-info">
                            <?php echo do_shortcode('[atc_contact_us]'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
        </div>
        <?php
        return ob_get_clean();
    }
}

