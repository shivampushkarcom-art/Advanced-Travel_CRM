<?php
/**
 * ATC Lead Generation Popup System
 * Smart auto-popup lead capture with AI-based timing
 * 
 * Features:
 * - Auto-appears based on time spent, scroll depth, exit intent
 * - Theme-aware design matching website colors
 * - Fully customizable from admin
 * - Integrated with lead management
 * - Admin notifications
 */

if (!defined('ABSPATH')) exit;

class ATC_Lead_Popup {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Don't call init here - it's called statically
    }
    
    public static function init() {
        $instance = self::get_instance();
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets']);
        add_action('wp_footer', [$instance, 'render_popup']);
        add_action('wp_ajax_atc_submit_popup_lead', [$instance, 'ajax_submit_lead']);
        add_action('wp_ajax_nopriv_atc_submit_popup_lead', [$instance, 'ajax_submit_lead']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    /**
     * Check if popup is enabled
     */
    public static function is_enabled() {
        return get_option('atc_popup_enabled', 1) && ATC_Feature_Manager::is_enabled('lead_capture');
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (!self::is_enabled() || is_admin()) {
            return;
        }
        
        // Don't show on certain pages
        $excluded_pages = get_option('atc_popup_excluded_pages', '');
        if (!empty($excluded_pages)) {
            $excluded = array_map('trim', explode(',', $excluded_pages));
            $current_page = get_queried_object_id();
            if (in_array($current_page, $excluded)) {
                return;
            }
        }
        
        wp_enqueue_style(
            'atc-lead-popup',
            ATC_ASSETS_URL . 'css/atc-lead-popup.css',
            [],
            ATC_VERSION
        );
        
        wp_enqueue_script(
            'atc-lead-popup',
            ATC_ASSETS_URL . 'js/atc-lead-popup.js',
            ['jquery'],
            ATC_VERSION,
            true
        );
        
        // Localize script with settings
        wp_localize_script('atc-lead-popup', 'atcPopup', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('atc_popup_nonce'),
            'settings' => [
                'trigger_time' => get_option('atc_popup_trigger_time', 30), // seconds
                'trigger_scroll' => get_option('atc_popup_trigger_scroll', 50), // percentage
                'exit_intent' => get_option('atc_popup_exit_intent', 1),
                'show_once_per_session' => get_option('atc_popup_show_once', 1),
                'delay_after_page_load' => get_option('atc_popup_delay', 5), // seconds
                'mobile_enabled' => get_option('atc_popup_mobile_enabled', 1),
            ],
            'messages' => [
                'success' => __('Thank you! We\'ll contact you soon.', 'advanced-travel-crm'),
                'error' => __('Please fill all required fields.', 'advanced-travel-crm'),
                'submitting' => __('Submitting...', 'advanced-travel-crm'),
            ]
        ]);
    }
    
    /**
     * Render popup HTML
     */
    public function render_popup() {
        if (!self::is_enabled() || is_admin()) {
            return;
        }
        
        // Get popup settings
        $title = get_option('atc_popup_title', '🎉 Get Exclusive Travel Deals!');
        $subtitle = get_option('atc_popup_subtitle', 'Enter your details and get the best travel packages delivered to your inbox.');
        $button_text = get_option('atc_popup_button_text', 'Get Started');
        $privacy_text = get_option('atc_popup_privacy_text', 'We respect your privacy. Unsubscribe at any time.');
        $offer_text = get_option('atc_popup_offer_text', '');
        $service = get_option('atc_popup_default_service', 'tours');
        
        // Get theme colors
        $primary_color = get_option('atc_popup_primary_color', '#667eea');
        $secondary_color = get_option('atc_popup_secondary_color', '#764ba2');
        $text_color = get_option('atc_popup_text_color', '#0A1F44');
        
        ?>
        <div id="atc-lead-popup" class="atc-lead-popup" style="display: none;">
            <div class="atc-popup-overlay"></div>
            <div class="atc-popup-container">
                <button class="atc-popup-close" aria-label="Close">×</button>
                
                <?php if (!empty($offer_text)): ?>
                    <div class="atc-popup-badge">
                        <?php echo esc_html($offer_text); ?>
                    </div>
                <?php endif; ?>
                
                <div class="atc-popup-content">
                    <div class="atc-popup-header">
                        <h2 class="atc-popup-title"><?php echo esc_html($title); ?></h2>
                        <?php if (!empty($subtitle)): ?>
                            <p class="atc-popup-subtitle"><?php echo esc_html($subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <form id="atc-popup-form" class="atc-popup-form">
                        <?php
                        // Context Intelligence: If on a package page, capture it
                        if (is_singular('atc_package') || is_singular('package')) {
                            global $post;
                            echo '<input type="hidden" name="package_id" value="' . esc_attr($post->ID) . '">';
                            echo '<input type="hidden" name="package_name" value="' . esc_attr($post->post_title) . '">';
                        }
                        ?>
                        <div class="atc-form-group">
                            <input type="text" 
                                   name="name" 
                                   id="atc-popup-name" 
                                   placeholder="<?php esc_attr_e('Your Name', 'advanced-travel-crm'); ?>" 
                                   class="atc-form-input"
                                   required>
                        </div>
                        
                        <div class="atc-form-group">
                            <input type="tel" 
                                   name="phone" 
                                   id="atc-popup-phone" 
                                   placeholder="<?php esc_attr_e('Mobile Number *', 'advanced-travel-crm'); ?>" 
                                   class="atc-form-input"
                                   required>
                        </div>
                        
                        <div class="atc-form-group">
                            <input type="email" 
                                   name="email" 
                                   id="atc-popup-email" 
                                   placeholder="<?php esc_attr_e('Email Address', 'advanced-travel-crm'); ?>" 
                                   class="atc-form-input">
                        </div>
                        
                        <div class="atc-form-group">
                            <select name="service" id="atc-popup-service" class="atc-form-select atc-popup-service-select">
                                <option value=""><?php esc_html_e('Select Service', 'advanced-travel-crm'); ?></option>
                                <?php
                                if (class_exists('ATC_Services')) {
                                    $services = ATC_Services::get_services();
                                    foreach ($services as $key => $config) {
                                        if ($key !== 'safari') {
                                            echo '<option value="' . esc_attr($key) . '" ' . selected($service, $key, false) . '>' . esc_html($config['label'] ?? ucfirst($key)) . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="atc-form-group">
                            <input type="text" 
                                   name="destination" 
                                   id="atc-popup-destination" 
                                   placeholder="<?php esc_attr_e('Where do you want to go?', 'advanced-travel-crm'); ?>" 
                                   class="atc-form-input">
                        </div>
                        
                        <!-- Travel Date -->
                        <div class="atc-form-group">
                            <input type="date" 
                                   name="travel_date" 
                                   id="atc-popup-travel-date" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="atc-form-input"
                                   placeholder="<?php esc_attr_e('Travel Date', 'advanced-travel-crm'); ?>">
                        </div>
                        
                        <!-- Trip Type -->
                        <div class="atc-form-group">
                            <select name="trip_type" id="atc-popup-trip-type" class="atc-form-select">
                                <option value=""><?php esc_html_e('Trip Type (Optional)', 'advanced-travel-crm'); ?></option>
                                <option value="solo"><?php esc_html_e('Solo', 'advanced-travel-crm'); ?></option>
                                <option value="couple"><?php esc_html_e('Couple', 'advanced-travel-crm'); ?></option>
                                <option value="family"><?php esc_html_e('Family', 'advanced-travel-crm'); ?></option>
                                <option value="friends"><?php esc_html_e('Friends', 'advanced-travel-crm'); ?></option>
                                <option value="business"><?php esc_html_e('Business', 'advanced-travel-crm'); ?></option>
                                <option value="group"><?php esc_html_e('Group', 'advanced-travel-crm'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Number of Travelers -->
                        <div class="atc-form-group">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;"><?php esc_html_e('Adults', 'advanced-travel-crm'); ?></label>
                                    <input type="number" 
                                           name="adults" 
                                           id="atc-popup-adults" 
                                           min="1" 
                                           value="2"
                                           class="atc-form-input atc-popup-travelers-count">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;"><?php esc_html_e('Children', 'advanced-travel-crm'); ?></label>
                                    <input type="number" 
                                           name="children" 
                                           id="atc-popup-children" 
                                           min="0" 
                                           value="0"
                                           class="atc-form-input atc-popup-children-count">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Child Ages (shown only if children > 0) -->
                        <div class="atc-form-group atc-popup-child-ages-group" id="atc-popup-child-ages" style="display: none;">
                            <label style="font-size: 13px; color: #666; display: block; margin-bottom: 8px;"><?php esc_html_e('Children Ages', 'advanced-travel-crm'); ?></label>
                            <div id="atc-popup-child-ages-container" style="display: flex; flex-direction: column; gap: 8px;">
                                <!-- Child age inputs will be dynamically added here -->
                            </div>
                        </div>
                        
                        <!-- Budget -->
                        <div class="atc-form-group">
                            <select name="budget" id="atc-popup-budget" class="atc-form-select">
                                <option value=""><?php esc_html_e('Budget Range (Optional)', 'advanced-travel-crm'); ?></option>
                                <option value="under-50000"><?php esc_html_e('Under ₹50,000', 'advanced-travel-crm'); ?></option>
                                <option value="50000-100000"><?php esc_html_e('₹50,000 - ₹1,00,000', 'advanced-travel-crm'); ?></option>
                                <option value="100000-200000"><?php esc_html_e('₹1,00,000 - ₹2,00,000', 'advanced-travel-crm'); ?></option>
                                <option value="200000-500000"><?php esc_html_e('₹2,00,000 - ₹5,00,000', 'advanced-travel-crm'); ?></option>
                                <option value="500000-1000000"><?php esc_html_e('₹5,00,000 - ₹10,00,000', 'advanced-travel-crm'); ?></option>
                                <option value="over-1000000"><?php esc_html_e('Over ₹10,00,000', 'advanced-travel-crm'); ?></option>
                                <option value="custom"><?php esc_html_e('Custom Budget', 'advanced-travel-crm'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Custom Budget (shown only if "Custom Budget" selected) -->
                        <div class="atc-form-group atc-popup-custom-budget-group" id="atc-popup-custom-budget" style="display: none;">
                            <input type="text" 
                                   name="custom_budget" 
                                   id="atc-popup-custom-budget-input" 
                                   placeholder="<?php esc_attr_e('e.g., ₹75,000', 'advanced-travel-crm'); ?>" 
                                   class="atc-form-input">
                        </div>
                        
                        <!-- Hotel Type (only for services that need hotels) -->
                        <div class="atc-form-group atc-popup-hotel-type-group" id="atc-popup-hotel-type" style="display: none;">
                            <select name="hotel_type" id="atc-popup-hotel-type-select" class="atc-form-select">
                                <option value=""><?php esc_html_e('Preferred Hotel Type (Optional)', 'advanced-travel-crm'); ?></option>
                                <option value="3-star"><?php esc_html_e('3 Star', 'advanced-travel-crm'); ?></option>
                                <option value="4-star"><?php esc_html_e('4 Star', 'advanced-travel-crm'); ?></option>
                                <option value="5-star"><?php esc_html_e('5 Star', 'advanced-travel-crm'); ?></option>
                                <option value="luxury"><?php esc_html_e('Luxury', 'advanced-travel-crm'); ?></option>
                                <option value="budget"><?php esc_html_e('Budget', 'advanced-travel-crm'); ?></option>
                                <option value="resort"><?php esc_html_e('Resort', 'advanced-travel-crm'); ?></option>
                                <option value="no-preference"><?php esc_html_e('No Preference', 'advanced-travel-crm'); ?></option>
                            </select>
                        </div>
                        
                        <div class="atc-form-group">
                            <textarea name="message" 
                                      id="atc-popup-message" 
                                      rows="3" 
                                      placeholder="<?php esc_attr_e('Any specific requirements? (Optional)', 'advanced-travel-crm'); ?>" 
                                      class="atc-form-textarea"></textarea>
                        </div>
                        
                        <?php if (!empty($privacy_text)): ?>
                            <div class="atc-privacy-text">
                                <small><?php echo esc_html($privacy_text); ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="atc-popup-submit">
                            <span class="atc-submit-text"><?php echo esc_html($button_text); ?></span>
                            <span class="atc-submit-loader" style="display: none;">⏳</span>
                        </button>
                    </form>
                    
                    <div class="atc-popup-success" style="display: none;">
                        <div class="atc-success-icon">✅</div>
                        <h3><?php esc_html_e('Thank You!', 'advanced-travel-crm'); ?></h3>
                        <p><?php esc_html_e('We\'ll contact you soon with the best travel deals!', 'advanced-travel-crm'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        :root {
            --atc-popup-primary: <?php echo esc_attr($primary_color); ?>;
            --atc-popup-secondary: <?php echo esc_attr($secondary_color); ?>;
            --atc-popup-text: <?php echo esc_attr($text_color); ?>;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX: Submit popup lead
     */
    public function ajax_submit_lead() {
        try {
            check_ajax_referer('atc_popup_nonce', 'nonce');
            
            $name = sanitize_text_field($_POST['name'] ?? '');
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $email = sanitize_email($_POST['email'] ?? '');
            $service = sanitize_text_field($_POST['service'] ?? '');
            $destination = sanitize_text_field($_POST['destination'] ?? '');
            
            // Validate required fields
            if (empty($name) || empty($phone)) {
                throw new Exception(__('Name and phone number are required.', 'advanced-travel-crm'));
            }
            
            // Validate phone number
            $phone = preg_replace('/[^0-9+]/', '', $phone);
            if (strlen($phone) < 10) {
                throw new Exception(__('Please enter a valid phone number.', 'advanced-travel-crm'));
            }


            // Capture Additional Fields
            $metadata = [
                'travel_date' => sanitize_text_field($_POST['travel_date'] ?? ''),
                'trip_type' => sanitize_text_field($_POST['trip_type'] ?? ''),
                'adults' => absint($_POST['adults'] ?? 2),
                'children' => absint($_POST['children'] ?? 0),
                'child_ages' => sanitize_text_field($_POST['child_ages'] ?? ''),
                'budget' => sanitize_text_field($_POST['budget'] ?? ''),
                'custom_budget' => sanitize_text_field($_POST['custom_budget'] ?? ''),
                'hotel_type' => sanitize_text_field($_POST['hotel_type'] ?? ''),
                'message' => sanitize_textarea_field($_POST['message'] ?? ''),
                'package_name' => sanitize_text_field($_POST['package_name'] ?? ''),
                'package_id' => intval($_POST['package_id'] ?? 0),
            ];

            // Parse Budget (e.g. "50000-100000" -> min: 50000, max: 100000)
            $budget_min = 0;
            $budget_max = 0;
            $budget_str = $metadata['custom_budget'] ?: $metadata['budget'];
            
            if (!empty($budget_str)) {
                // Remove currency symbols and commas
                $clean_budget = preg_replace('/[^0-9\-]/', '', $budget_str);
                
                if (strpos($clean_budget, '-') !== false) {
                    $parts = explode('-', $clean_budget);
                    $budget_min = intval($parts[0]);
                    $budget_max = intval($parts[1]);
                } else {
                    // Start from X or Up to X or just X
                    $val = intval($clean_budget);
                    if (strpos(strtolower($budget_str), 'under') !== false) {
                        $budget_max = $val;
                    } elseif (strpos(strtolower($budget_str), 'over') !== false || strpos(strtolower($budget_str), 'above') !== false) {
                        $budget_min = $val;
                    } else {
                        // Treat as approx value, maybe +/- range or just min? Let's say min.
                        $budget_min = $val;
                    }
                }
            }
            
            global $wpdb;
            $lead_id = 0;
            
            // Check for duplicate lead (same phone within 24 hours)
            $existing_lead = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_LEADS . " 
                WHERE phone = %s 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY id DESC LIMIT 1",
                $phone
            ), ARRAY_A);
            
            if ($existing_lead) {
                // Update existing lead
                $update_data = [
                    'name' => $name,
                    'email' => $email ?: $existing_lead['email'],
                    'service' => $service ?: $existing_lead['service'],
                    'destination' => $destination ?: $existing_lead['destination'],
                    'budget_min' => $budget_min ?: $existing_lead['budget_min'],
                    'budget_max' => $budget_max ?: $existing_lead['budget_max'],
                    'source' => 'popup',
                    'updated_at' => current_time('mysql')
                ];
                
                // Merge metadata
                $columns = $wpdb->get_col("SHOW COLUMNS FROM " . ATC_TABLE_LEADS);
                if (in_array('metadata', $columns)) {
                    $existing_metadata = !empty($existing_lead['metadata']) ? json_decode($existing_lead['metadata'], true) : [];
                    $final_metadata = array_merge($existing_metadata, $metadata);
                    $update_data['metadata'] = wp_json_encode($final_metadata);
                }
                
                $wpdb->update(ATC_TABLE_LEADS, $update_data, ['id' => $existing_lead['id']]);
                $lead_id = $existing_lead['id'];
                
            } else {
                // Create new lead
                $insert_data = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'service' => $service,
                    'destination' => $destination,
                    'budget_min' => $budget_min,
                    'budget_max' => $budget_max,
                    'source' => 'popup',
                    'status' => 'new',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ];

                // Check for metadata column
                $columns = $wpdb->get_col("SHOW COLUMNS FROM " . ATC_TABLE_LEADS);
                if (in_array('metadata', $columns)) {
                    $insert_data['metadata'] = wp_json_encode($metadata);
                }

                $wpdb->insert(ATC_TABLE_LEADS, $insert_data);
                $lead_id = $wpdb->insert_id;
            }
            
            if (!$lead_id) {
                throw new Exception(__('Failed to save lead. Please try again.', 'advanced-travel-crm'));
            }
            
            // Calculate lead score (safe call)
            if (class_exists('ATC_Lead_Scoring')) {
                ATC_Lead_Scoring::update_lead_score($lead_id);
            }
            
            // Get Full Lead Data for Notifications
            $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . ATC_TABLE_LEADS . " WHERE id = %d", $lead_id), ARRAY_A);
            
            // Merge metadata into lead array for easy access in templates
            if (!empty($lead['metadata'])) {
                $decoded = json_decode($lead['metadata'], true);
                if (is_array($decoded)) {
                    $lead = array_merge($lead, $decoded);
                }
            }

            // Notification Logic
            if (get_option('atc_popup_notify_admin', 1)) {
                $this->notify_admin($lead);
            }
            
            // Note: atc_lead_created hook is NOT triggered for popup leads
            // because the popup now handles its own comprehensive notifications directly.
            // This avoids duplicate emails.
            
            // Visitor tracking is also disabled for popup leads to avoid duplicate emails
            // from the visitor tracker's form_submission notification.
            
            wp_send_json_success([
                'message' => __('Thank you! We\'ll contact you soon.', 'advanced-travel-crm'),
                'lead_id' => $lead_id
            ]);

        } catch (Exception $e) {
            // Log the actual error for admin debugging
            if (class_exists('ATC_Logger')) {
                ATC_Logger::log('error', 'Popup Lead Error: ' . $e->getMessage());
            } else {
                error_log('ATC Popup Error: ' . $e->getMessage());
            }
            
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Notify admin about popup lead
     */
    /**
     * Notify admin about popup lead
     */
    private function notify_admin($lead) {
        // 1. Send Enhanced WhatsApp Notification
        if (class_exists('ATC_WhatsApp_Sender')) {
            $admin_phone = get_option('atc_whatsapp_admin_phone', '');
            
            if (!empty($admin_phone)) {
                $message = "🎉 *NEW POPUP LEAD*\n\n";
                $message .= "👤 *Name:* " . ($lead['name'] ?? 'N/A') . "\n";
                $message .= "📱 *Phone:* " . ($lead['phone'] ?? 'N/A') . "\n";
                $message .= "📧 *Email:* " . ($lead['email'] ?? 'N/A') . "\n";
                
                // Trip Details
                $message .= "\n*✈️ Trip Details:*\n";
                $message .= "• Dest: " . ($lead['destination'] ?? 'N/A') . "\n";
                $message .= "• Date: " . ($lead['travel_date'] ?? 'N/A') . "\n";
                
                if (!empty($lead['adults']) || !empty($lead['children'])) {
                    $message .= "• Pax: " . ($lead['adults']??0) . " Ad, " . ($lead['children']??0) . " Ch\n";
                }
                
                if (!empty($lead['budget']) || !empty($lead['custom_budget'])) {
                    $budget_display = !empty($lead['custom_budget']) ? $lead['custom_budget'] : $lead['budget'];
                    $message .= "• Budget: " . $budget_display . "\n";
                }
                if (!empty($lead['trip_type'])) {
                    $message .= "• Type: " . ucfirst($lead['trip_type']) . "\n";
                }
                
                if (!empty($lead['hotel_type'])) {
                    $message .= "• Hotel: " . ucfirst($lead['hotel_type']) . "\n";
                }
                
                if (!empty($lead['child_ages'])) {
                    $message .= "• Child Ages: " . $lead['child_ages'] . "\n";
                }

                // Package Context
                if (!empty($lead['package_name'])) {
                    $message .= "• Package: " . $lead['package_name'] . "\n";
                }
                
                // Requirements
                if (!empty($lead['message'])) {
                    $message .= "\n*💭 Requirements:*\n" . trim(strip_tags($lead['message'])) . "\n";
                }
                
                // Score
                $message .= "\n⭐ Score: " . ($lead['score'] ?? 0) . "/100 (" . ucfirst($lead['temperature'] ?? 'cold') . ")\n";
                
                ATC_WhatsApp_Sender::send_message($message, $admin_phone);
            }
        }
        
        // 2. Send Email Notification DIRECTLY (bypass score check)
        // ATC_Notification_Manager::on_lead_created checks score < 50 and returns early.
        // For popup leads, we want to ALWAYS send email notification with ALL data.
        if (class_exists('ATC_Email_Sender')) {
            $admin_email = get_option('atc_admin_email', get_option('admin_email'));
            
            if (!empty($admin_email) && is_email($admin_email)) {
                // Build comprehensive lead data for email
                $email_data = array_merge([
                    'name' => $lead['name'] ?? '',
                    'email' => $lead['email'] ?? '',
                    'phone' => $lead['phone'] ?? '',
                    'destination' => $lead['destination'] ?? '',
                    'service' => $lead['service'] ?? '',
                    'budget_min' => $lead['budget_min'] ?? 0,
                    'budget_max' => $lead['budget_max'] ?? 0,
                    'score' => $lead['score'] ?? 0,
                    'temperature' => $lead['temperature'] ?? 'cold',
                    // Metadata fields
                    'travel_date' => $lead['travel_date'] ?? '',
                    'trip_type' => $lead['trip_type'] ?? '',
                    'adults' => $lead['adults'] ?? 2,
                    'children' => $lead['children'] ?? 0,
                    'child_ages' => $lead['child_ages'] ?? '',
                    'budget' => $lead['budget'] ?? '',
                    'custom_budget' => $lead['custom_budget'] ?? '',
                    'hotel_type' => $lead['hotel_type'] ?? '',
                    'message' => $lead['message'] ?? '',
                    'special_requirements' => $lead['message'] ?? '',
                    'package_name' => $lead['package_name'] ?? '',
                    'package_id' => $lead['package_id'] ?? 0,
                ], $lead);
                
                // Use a comprehensive template
                $template = [
                    'subject' => '🔥 New Lead From Website - ' . ($lead['name'] ?? 'Unknown'),
                    'body' => "Hello Admin,\n\nA new lead has been captured from the website popup!\n\n**Contact Details**\n- Name: {name}\n- Phone: {phone}\n- Email: {email}\n\n**Trip Requirements**\n- Destination: {destination}\n- Travel Date: {travel_date}\n- Trip Type: {trip_type}\n- Travelers: {adults} Adults, {children} Children\n- Child Ages: {child_ages}\n- Budget: {budget_min} - {budget_max}\n- Hotel Type: {hotel_type}\n- Package: {package_name}\n\n**Message/Requirements**\n{message}\n\n**Lead Quality**\n- Score: {score}/100\n- Temperature: {temperature}\n\nPlease contact this lead as soon as possible!\n\n{company_name}"
                ];
                
                ATC_Email_Sender::send($admin_email, $template, $email_data);
            }
        }
    }
    
    /**
     * Admin menu
     */
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Lead Popup Settings', 'advanced-travel-crm'),
            __('Lead Popup', 'advanced-travel-crm'),
            'manage_options',
            'atc-lead-popup',
            [__CLASS__, 'settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('atc_popup_settings', 'atc_popup_enabled');
        register_setting('atc_popup_settings', 'atc_popup_trigger_time');
        register_setting('atc_popup_settings', 'atc_popup_trigger_scroll');
        register_setting('atc_popup_settings', 'atc_popup_exit_intent');
        register_setting('atc_popup_settings', 'atc_popup_show_once');
        register_setting('atc_popup_settings', 'atc_popup_delay');
        register_setting('atc_popup_settings', 'atc_popup_mobile_enabled');
        register_setting('atc_popup_settings', 'atc_popup_title');
        register_setting('atc_popup_settings', 'atc_popup_subtitle');
        register_setting('atc_popup_settings', 'atc_popup_button_text');
        register_setting('atc_popup_settings', 'atc_popup_offer_text');
        register_setting('atc_popup_settings', 'atc_popup_privacy_text');
        register_setting('atc_popup_settings', 'atc_popup_default_service');
        register_setting('atc_popup_settings', 'atc_popup_primary_color');
        register_setting('atc_popup_settings', 'atc_popup_secondary_color');
        register_setting('atc_popup_settings', 'atc_popup_text_color');
        register_setting('atc_popup_settings', 'atc_popup_notify_admin');
        register_setting('atc_popup_settings', 'atc_popup_excluded_pages');
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('atc_popup_settings');
            self::register_settings();
            do_action('admin_init');
            settings_fields('atc_popup_settings');
            do_settings_sections('atc_popup_settings');
            
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'atc_popup_') === 0) {
                    update_option($key, sanitize_text_field($value));
                }
            }
            
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        ?>
        <div class="wrap atc-popup-settings">
            <h1>🎯 Lead Generation Popup Settings</h1>
            <p class="description">Configure the auto-popup lead capture system. The popup will automatically appear to visitors based on your settings.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('atc_popup_settings'); ?>
                
                <div class="atc-settings-section">
                    <h2>⚙️ General Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th>Enable Popup</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="atc_popup_enabled" value="1" 
                                           <?php checked(get_option('atc_popup_enabled', 1), 1); ?>>
                                    Enable lead generation popup
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Notify Admin</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="atc_popup_notify_admin" value="1" 
                                           <?php checked(get_option('atc_popup_notify_admin', 1), 1); ?>>
                                    Send WhatsApp/Email notification when popup lead is submitted
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Mobile Enabled</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="atc_popup_mobile_enabled" value="1" 
                                           <?php checked(get_option('atc_popup_mobile_enabled', 1), 1); ?>>
                                    Show popup on mobile devices
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="atc-settings-section">
                    <h2>⏱️ Trigger Settings (AI-Optimized)</h2>
                    <p class="description">The popup will appear when ANY of these conditions are met. Research shows optimal timing improves conversion by 2-3x.</p>
                    <table class="form-table">
                        <tr>
                            <th>Time on Page (seconds)</th>
                            <td>
                                <input type="number" name="atc_popup_trigger_time" 
                                       value="<?php echo esc_attr(get_option('atc_popup_trigger_time', 30)); ?>" 
                                       min="5" max="300" class="small-text">
                                <p class="description">
                                    <strong>Recommended: 30-60 seconds</strong><br>
                                    Shows popup after visitor spends this time on page. 
                                    <strong>30 seconds</strong> is optimal for most travel websites (visitors are engaged but not yet ready to leave).
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>Scroll Depth (%)</th>
                            <td>
                                <input type="number" name="atc_popup_trigger_scroll" 
                                       value="<?php echo esc_attr(get_option('atc_popup_trigger_scroll', 50)); ?>" 
                                       min="0" max="100" class="small-text">
                                <p class="description">
                                    <strong>Recommended: 50-70%</strong><br>
                                    Shows popup when visitor scrolls this percentage down the page. 
                                    <strong>50%</strong> indicates genuine interest.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>Exit Intent</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="atc_popup_exit_intent" value="1" 
                                           <?php checked(get_option('atc_popup_exit_intent', 1), 1); ?>>
                                    Show popup when visitor tries to leave (mouse moves to close tab)
                                </label>
                                <p class="description">
                                    <strong>Highly Effective:</strong> Captures 10-20% more leads. 
                                    Shows popup when visitor's mouse moves toward browser close button.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>Delay After Page Load (seconds)</th>
                            <td>
                                <input type="number" name="atc_popup_delay" 
                                       value="<?php echo esc_attr(get_option('atc_popup_delay', 5)); ?>" 
                                       min="0" max="60" class="small-text">
                                <p class="description">
                                    <strong>Recommended: 5-10 seconds</strong><br>
                                    Wait this long after page loads before checking trigger conditions. 
                                    Prevents popup from showing too early.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>Show Once Per Session</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="atc_popup_show_once" value="1" 
                                           <?php checked(get_option('atc_popup_show_once', 1), 1); ?>>
                                    Only show popup once per visitor session
                                </label>
                                <p class="description">Prevents popup from appearing multiple times to the same visitor.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="atc-settings-section">
                    <h2>✏️ Content & Design</h2>
                    <table class="form-table">
                        <tr>
                            <th>Popup Title</th>
                            <td>
                                <input type="text" name="atc_popup_title" 
                                       value="<?php echo esc_attr(get_option('atc_popup_title', '🎉 Get Exclusive Travel Deals!')); ?>" 
                                       class="large-text">
                                <p class="description">Main headline that appears in popup</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Popup Subtitle</th>
                            <td>
                                <textarea name="atc_popup_subtitle" rows="2" class="large-text"><?php echo esc_textarea(get_option('atc_popup_subtitle', 'Enter your details and get the best travel packages delivered to your inbox.')); ?></textarea>
                                <p class="description">Supporting text below the title</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Offer Badge Text</th>
                            <td>
                                <input type="text" name="atc_popup_offer_text" 
                                       value="<?php echo esc_attr(get_option('atc_popup_offer_text', '')); ?>" 
                                       class="large-text" 
                                       placeholder="e.g., Limited Time: 20% Off">
                                <p class="description">Optional: Special offer text shown as a badge (leave empty to hide)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Submit Button Text</th>
                            <td>
                                <input type="text" name="atc_popup_button_text" 
                                       value="<?php echo esc_attr(get_option('atc_popup_button_text', 'Get Started')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th>Privacy Text</th>
                            <td>
                                <input type="text" name="atc_popup_privacy_text" 
                                       value="<?php echo esc_attr(get_option('atc_popup_privacy_text', 'We respect your privacy. Unsubscribe at any time.')); ?>" 
                                       class="large-text">
                                <p class="description">Privacy/disclaimer text shown below form</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Default Service</th>
                            <td>
                                <select name="atc_popup_default_service" class="regular-text">
                                    <option value="tours" <?php selected(get_option('atc_popup_default_service', 'tours'), 'tours'); ?>>Tours</option>
                                    <option value="forex" <?php selected(get_option('atc_popup_default_service', 'tours'), 'forex'); ?>>Forex</option>
                                    <option value="visa" <?php selected(get_option('atc_popup_default_service', 'tours'), 'visa'); ?>>Visa</option>
                                    <option value="hotels" <?php selected(get_option('atc_popup_default_service', 'tours'), 'hotels'); ?>>Hotels</option>
                                    <option value="flights" <?php selected(get_option('atc_popup_default_service', 'tours'), 'flights'); ?>>Flights</option>
                                    <option value="trains" <?php selected(get_option('atc_popup_default_service', 'tours'), 'trains'); ?>>Trains</option>
                                    <option value="cars" <?php selected(get_option('atc_popup_default_service', 'tours'), 'cars'); ?>>Cars</option>
                                    <option value="celebrity" <?php selected(get_option('atc_popup_default_service', 'tours'), 'celebrity'); ?>>Celebrity Management</option>
                                </select>
                                <p class="description">Default service pre-selected in popup form</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="atc-settings-section">
                    <h2>🎨 Theme Colors</h2>
                    <p class="description">Match your website's color scheme for a seamless experience</p>
                    <table class="form-table">
                        <tr>
                            <th>Primary Color</th>
                            <td>
                                <input type="color" name="atc_popup_primary_color" 
                                       value="<?php echo esc_attr(get_option('atc_popup_primary_color', '#667eea')); ?>" 
                                       class="atc-color-picker">
                                <input type="text" 
                                       value="<?php echo esc_attr(get_option('atc_popup_primary_color', '#667eea')); ?>" 
                                       class="atc-color-text regular-text" 
                                       readonly>
                                <p class="description">Main color for buttons and highlights</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Secondary Color</th>
                            <td>
                                <input type="color" name="atc_popup_secondary_color" 
                                       value="<?php echo esc_attr(get_option('atc_popup_secondary_color', '#764ba2')); ?>" 
                                       class="atc-color-picker">
                                <input type="text" 
                                       value="<?php echo esc_attr(get_option('atc_popup_secondary_color', '#764ba2')); ?>" 
                                       class="atc-color-text regular-text" 
                                       readonly>
                                <p class="description">Gradient/secondary color for backgrounds</p>
                </div>
                
                <div class="atc-settings-section">
                    <h2>🚫 Exclusions</h2>
                    <table class="form-table">
                        <tr>
                            <th>Excluded Pages</th>
                            <td>
                                <input type="text" name="atc_popup_excluded_pages" 
                                       value="<?php echo esc_attr(get_option('atc_popup_excluded_pages', '')); ?>" 
                                       class="large-text" 
                                       placeholder="123,456,789">
                                <p class="description">Comma-separated page IDs where popup should NOT appear (e.g., checkout, thank you pages)</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" name="submit" class="button button-primary button-large">
                        💾 Save Settings
                    </button>
                </p>
            </form>
            
            <div class="atc-popup-preview">
                <h2>👁️ Preview</h2>
                <p class="description">This is how your popup will look (colors and content will match your settings)</p>
                <div class="atc-preview-container">
                    <div class="atc-popup-preview-box">
                        <div class="atc-popup-header">
                            <h3><?php echo esc_html(get_option('atc_popup_title', '🎉 Get Exclusive Travel Deals!')); ?></h3>
                            <p><?php echo esc_html(get_option('atc_popup_subtitle', 'Enter your details and get the best travel packages delivered to your inbox.')); ?></p>
                        </div>
                        <div class="atc-popup-form-preview">
                            <div class="atc-form-input-preview"></div>
                            <div class="atc-form-input-preview"></div>
                            <div class="atc-form-input-preview"></div>
                            <div class="atc-popup-submit-preview"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
            .atc-popup-settings {
                background: #f8fafc;
                padding: 20px;
            }
            .atc-settings-section {
                background: #fff;
                padding: 25px;
                margin-bottom: 20px;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .atc-settings-section h2 {
                margin-top: 0;
                color: #0A1F44;
                padding-bottom: 15px;
                border-bottom: 3px solid #D4AF37;
            }
            .atc-color-picker {
                width: 60px;
                height: 35px;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
                cursor: pointer;
                margin-right: 10px;
            }
            .atc-color-text {
                width: 100px;
            }
            .atc-popup-preview {
                background: #fff;
                padding: 25px;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                margin-top: 20px;
            }
            .atc-preview-container {
                background: #f8fafc;
                padding: 40px;
                border-radius: 8px;
                display: flex;
                justify-content: center;
            }
            .atc-popup-preview-box {
                background: #fff;
                border-radius: 12px;
                padding: 30px;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border: 2px solid var(--atc-popup-primary, #667eea);
            }
            .atc-form-input-preview {
                height: 45px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 15px;
            }
            .atc-popup-submit-preview {
                height: 50px;
                background: linear-gradient(135deg, var(--atc-popup-primary, #667eea), var(--atc-popup-secondary, #764ba2));
                border-radius: 8px;
                margin-top: 10px;
            }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                $('.atc-color-picker').on('change', function() {
                    $(this).next('.atc-color-text').val($(this).val());
                });
            });
            </script>
        </div>
        <?php
    }
}

