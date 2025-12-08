<?php
/**
 * ATC Floating Contact Popup Button
 * Replaces WhatsApp button with contact information popup
 * Shows phone, email, Facebook, WhatsApp, and link to contact page
 */

if (!defined('ABSPATH')) exit;

class ATC_Contact_Popup_Button {
    
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [__CLASS__, 'render_button']);
    }
    
    public static function enqueue_assets() {
        if (is_admin()) {
            return;
        }
        
        if (!defined('ATC_ASSETS_URL') || !defined('ATC_VERSION')) {
            return;
        }
        
        wp_enqueue_style('atc-contact-popup-button', ATC_ASSETS_URL . 'css/atc-contact-popup-button.css', [], ATC_VERSION);
        wp_enqueue_script('atc-contact-popup-button', ATC_ASSETS_URL . 'js/atc-contact-popup-button.js', ['jquery'], ATC_VERSION, true);
        
        // Get contact information
        $phone = '+91 6262627979';
        $email = 'connect@vincoholidays.com';
        $whatsapp = '6262627979'; // Without country code for WhatsApp link
        $facebook = 'https://www.facebook.com/vincoholidaysjbp/';
        $contact_page = 'https://vincoholidays.com/contact-us/';
        
        wp_localize_script('atc-contact-popup-button', 'atcContactPopup', [
            'phone' => $phone,
            'email' => $email,
            'whatsapp' => $whatsapp,
            'facebook' => $facebook,
            'contactPage' => $contact_page,
            'delay' => 3000, // 3 seconds delay
        ]);
    }
    
    public static function render_button() {
        if (is_admin()) {
            return;
        }
        
        // Get contact information
        $phone = '+91 6262627979';
        $email = 'connect@vincoholidays.com';
        $whatsapp = '6262627979';
        $facebook = 'https://www.facebook.com/vincoholidaysjbp/';
        $contact_page = 'https://vincoholidays.com/contact-us/';
        
        ?>
        <!-- Floating Contact Popup Button -->
        <div id="atc-contact-popup-button" class="atc-contact-popup-button" style="display: none;">
            <button type="button" 
                    class="atc-contact-popup-trigger" 
                    aria-label="<?php esc_attr_e('Contact Us', 'advanced-travel-crm'); ?>">
                <span class="atc-contact-popup-icon">📞</span>
                <span class="atc-contact-popup-text"><?php echo esc_html(__('Contact Us', 'advanced-travel-crm')); ?></span>
            </button>
            <button type="button" class="atc-contact-popup-close" aria-label="<?php esc_attr_e('Close', 'advanced-travel-crm'); ?>">
                <span>×</span>
            </button>
        </div>
        
        <!-- Contact Popup Modal -->
        <div id="atc-contact-popup-modal" class="atc-contact-popup-modal" style="display: none;">
            <div class="atc-contact-popup-overlay"></div>
            <div class="atc-contact-popup-content">
                <button class="atc-contact-popup-modal-close" aria-label="Close">&times;</button>
                
                <div class="atc-contact-popup-header">
                    <h2><?php echo esc_html(__('Contact Us', 'advanced-travel-crm')); ?></h2>
                    <p><?php echo esc_html(__('Get in touch with us. We\'re here to help!', 'advanced-travel-crm')); ?></p>
                </div>
                
                <div class="atc-contact-popup-actions">
                    <!-- Phone -->
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" 
                       class="atc-contact-action-btn atc-contact-phone-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span><?php echo esc_html($phone); ?></span>
                    </a>
                    
                    <!-- Email -->
                    <a href="mailto:<?php echo esc_attr($email); ?>" 
                       class="atc-contact-action-btn atc-contact-email-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span><?php echo esc_html($email); ?></span>
                    </a>
                    
                    <!-- WhatsApp -->
                    <a href="https://wa.me/91<?php echo esc_attr($whatsapp); ?>?text=<?php echo urlencode('Hello! I would like to know more about your travel packages.'); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="atc-contact-action-btn atc-contact-whatsapp-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" fill="currentColor"/>
                        </svg>
                        <span>WhatsApp</span>
                    </a>
                    
                    <!-- Facebook -->
                    <a href="<?php echo esc_url($facebook); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="atc-contact-action-btn atc-contact-facebook-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 2H15C13.6739 2 12.4021 2.52678 11.4645 3.46447C10.5268 4.40215 10 5.67392 10 7V10H7V14H10V22H14V14H17L18 10H14V7C14 6.73478 14.1054 6.48043 14.2929 6.29289C14.4804 6.10536 14.7348 6 15 6H18V2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Facebook</span>
                    </a>
                    
                    <!-- Contact Page Link -->
                    <a href="<?php echo esc_url($contact_page); ?>" 
                       class="atc-contact-action-btn atc-contact-page-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 21V5C19 3.89543 18.1046 3 17 3H7C5.89543 3 5 3.89543 5 5V21M19 21L21 21M19 21H17M5 21L3 21M5 21H7M9 7H15M9 11H15M9 15H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span><?php echo esc_html(__('View Contact Page', 'advanced-travel-crm')); ?></span>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}

ATC_Contact_Popup_Button::init();

