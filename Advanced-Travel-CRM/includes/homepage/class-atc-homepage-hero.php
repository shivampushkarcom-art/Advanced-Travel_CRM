<?php
/**
 * ATC Homepage Hero Section
 * Premium hero with gold background and blue accents
 */

if (!defined('ABSPATH')) exit;

class ATC_Homepage_Hero {
    
    public static function init() {
        add_shortcode('atc_homepage_hero', [__CLASS__, 'homepage_hero_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function enqueue_assets() {
        // Check if constants exist
        $assets_url = defined('ATC_ASSETS_URL') ? ATC_ASSETS_URL : plugin_dir_url(__FILE__) . '../../assets/';
        $version = defined('ATC_VERSION') ? ATC_VERSION : '2.4.0';
        
        wp_enqueue_style('atc-homepage', $assets_url . 'css/atc-homepage.css', [], $version);
    }
    
    /**
     * Homepage Hero Shortcode
     * Usage: [atc_homepage_hero title="Your Title" subtitle="Your Subtitle" show_search="true"]
     */
    public static function homepage_hero_shortcode($atts) {
        $atts = shortcode_atts([
            'title' => 'Your Premium Travel Partner',
            'subtitle' => 'Experience Luxury Travel with Expert Service',
            'background_image' => '',
            'show_search' => 'true',
            'primary_button_text' => 'Explore Services',
            'primary_button_link' => '#services',
            'secondary_button_text' => 'Contact Us',
            'secondary_button_link' => '#contact',
        ], $atts);
        
        ob_start();
        ?>
        <section class="atc-homepage-hero">
            <div class="atc-hero-content">
                <h1><?php echo esc_html($atts['title']); ?></h1>
                <p><?php echo esc_html($atts['subtitle']); ?></p>
                
                <div class="atc-hero-cta" style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-bottom: 40px;">
                    <a href="<?php echo esc_url($atts['primary_button_link']); ?>" class="atc-btn-primary">
                        <?php echo esc_html($atts['primary_button_text']); ?>
                    </a>
                    <a href="<?php echo esc_url($atts['secondary_button_link']); ?>" class="atc-btn-secondary">
                        <?php echo esc_html($atts['secondary_button_text']); ?>
                    </a>
                </div>
                
                <?php if ($atts['show_search'] === 'true'): ?>
                    <?php 
                    // Check if shortcode exists before calling
                    if (shortcode_exists('atc_service_grid')) {
                        echo do_shortcode('[atc_service_grid columns="3" layout="grid"]');
                    } else {
                        echo '<p>Search functionality not available.</p>';
                    }
                    ?>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

ATC_Homepage_Hero::init();

