<?php
/**
 * ATC Premium Contact Us Shortcode
 * Premium contact information display with company details
 */

if (!defined('ABSPATH')) exit;

class ATC_Contact_Us {
    
    public static function init() {
        add_shortcode('atc_contact_us', [__CLASS__, 'contact_us_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    /**
     * Enqueue assets
     */
    public static function enqueue_assets() {
        wp_enqueue_style('atc-contact-us', ATC_ASSETS_URL . 'css/atc-contact-us.css', [], ATC_VERSION);
    }
    
    /**
     * Contact Us Shortcode
     * Usage: [atc_contact_us]
     */
    public static function contact_us_shortcode($atts = []) {
        $atts = shortcode_atts([
            'title' => 'Contact Us',
            'show_map' => 'true',
            'layout' => 'grid', // grid or list
        ], $atts);
        
        // Company information
        $company_name = 'Vinco Holidays';
        $phone = '+91 6262627979';
        $email = 'connect@vincoholidays.com';
        $address = 'Shiv Marketing, Anand Nagar, Jabalpur, 482004, Madhya Pradesh';
        
        ob_start();
        ?>
        <div class="atc-contact-us-wrapper">
            <div class="atc-contact-us-container">
                <!-- Header Section -->
                <div class="atc-contact-header">
                    <h2 class="atc-contact-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="atc-contact-subtitle">Get in touch with us. We're here to help!</p>
                </div>
                
                <!-- Contact Information Grid -->
                <div class="atc-contact-grid">
                    <!-- Company Card -->
                    <div class="atc-contact-card atc-contact-company">
                        <div class="atc-contact-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 21V5C19 3.89543 18.1046 3 17 3H7C5.89543 3 5 3.89543 5 5V21M19 21L21 21M19 21H17M5 21L3 21M5 21H7M9 7H15M9 11H15M9 15H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="atc-contact-card-title">Company</h3>
                        <p class="atc-contact-card-value"><?php echo esc_html($company_name); ?></p>
                        <p class="atc-contact-card-desc">Your travel partner</p>
                    </div>
                    
                    <!-- Address Card -->
                    <div class="atc-contact-card atc-contact-address">
                        <div class="atc-contact-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="atc-contact-card-title">Address</h3>
                        <p class="atc-contact-card-value"><?php echo esc_html($address); ?></p>
                        <p class="atc-contact-card-desc">Visit our office</p>
                    </div>
                    
                    <!-- Email Card -->
                    <div class="atc-contact-card atc-contact-email">
                        <div class="atc-contact-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="atc-contact-card-title">Email</h3>
                        <p class="atc-contact-card-value">
                            <a href="mailto:<?php echo esc_attr($email); ?>" class="atc-contact-link">
                                <?php echo esc_html($email); ?>
                            </a>
                        </p>
                        <p class="atc-contact-card-desc">Send us an email</p>
                    </div>
                    
                    <!-- Phone Card -->
                    <div class="atc-contact-card atc-contact-phone">
                        <div class="atc-contact-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="atc-contact-card-title">Phone</h3>
                        <p class="atc-contact-card-value">
                            <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>" class="atc-contact-link">
                                <?php echo esc_html($phone); ?>
                            </a>
                        </p>
                        <p class="atc-contact-card-desc">Call us anytime</p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="atc-contact-actions">
                    <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>" class="atc-contact-action-btn atc-call-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Call Now
                    </a>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="atc-contact-action-btn atc-email-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Send Email
                    </a>
                    <a href="https://wa.me/916262627979" target="_blank" rel="noopener noreferrer" class="atc-contact-action-btn atc-whatsapp-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 11.5C21.0034 16.7503 16.7503 21.0034 11.5 21C9.5 21 7.7 20.3 6.3 19.1L3 20L3.9 16.7C2.7 15.3 2 13.5 2 11.5C2 6.25 6.25 2 11.5 2C16.75 2 21 6.25 21 11.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17 11.5C17 14.26 14.76 16.5 12 16.5C11.1 16.5 10.25 16.25 9.5 15.75L7 16.5L7.75 14C7.25 13.25 7 12.4 7 11.5C7 8.74 9.24 6.5 12 6.5C14.76 6.5 17 8.74 17 11.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        WhatsApp
                    </a>
                    <a href="https://www.facebook.com/vincoholidaysjbp/" target="_blank" rel="noopener noreferrer" class="atc-contact-action-btn atc-facebook-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 2H15C13.6739 2 12.4021 2.52678 11.4645 3.46447C10.5268 4.40215 10 5.67392 10 7V10H7V14H10V22H14V14H17L18 10H14V7C14 6.73478 14.1054 6.48043 14.2929 6.29289C14.4804 6.10536 14.7348 6 15 6H18V2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Facebook
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

