<?php
/**
 * ATC Settings Page
 * Handles all plugin configuration
 */

if (!defined('ABSPATH')) exit;

class ATC_Settings {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function menu(){
        add_submenu_page(
            'atc-dashboard',  // ✅ FIX #2: Correct parent menu
            'ATC Settings',
            'Settings',
            'manage_options',
            'atc-settings',
            [__CLASS__,'settings_page']
        );
    }

    public static function register_settings(){
        // WhatsApp Settings
        register_setting('atc_settings_group','atc_whatsapp_admin_phone');
        register_setting('atc_settings_group','atc_whatsapp_api_token');
        register_setting('atc_settings_group','atc_whatsapp_phone_id');
        register_setting('atc_settings_group','atc_whatsapp_api_type');
        register_setting('atc_settings_group','atc_whatsapp_webhook_url');
        register_setting('atc_settings_group','atc_twilio_account_sid');
        register_setting('atc_settings_group','atc_twilio_whatsapp_from');
        
        // Webhook
        register_setting('atc_settings_group','atc_webhook_url');
        
        // Email Settings
        register_setting('atc_settings_group','atc_email_from_name');
        register_setting('atc_settings_group','atc_email_from_address');
        register_setting('atc_settings_group','atc_email_reply_to');
        register_setting('atc_settings_group','atc_admin_email');
        
        // SMTP Settings
        register_setting('atc_settings_group','atc_smtp_enabled');
        register_setting('atc_settings_group','atc_smtp_host');
        register_setting('atc_settings_group','atc_smtp_port');
        register_setting('atc_settings_group','atc_smtp_user');
        register_setting('atc_settings_group','atc_smtp_pass');
        register_setting('atc_settings_group','atc_smtp_secure');
        
        // Email Template
        register_setting('atc_settings_group','atc_email_template');
        
        // Google Maps API Key
        register_setting('atc_settings_group','atc_google_maps_api_key');
        
        // Visitor Tracking Settings
        register_setting('atc_settings_group','atc_enable_visitor_tracking');
        register_setting('atc_settings_group','atc_track_logged_in_only');
        register_setting('atc_settings_group','atc_visitor_notifications_enabled');
        
        // WhatsApp OTP Settings
        register_setting('atc_settings_group','atc_whatsapp_otp_enabled');
    }

    public static function settings_page(){
        ?>
        <div class="wrap atc-settings-wrap">
            <h1>🚀 Advanced Travel CRM - Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('atc_settings_group'); ?>
                
                <h2>📱 WhatsApp Notifications</h2>
                <table class="form-table">
                    <tr>
                        <th>WhatsApp Admin Phone</th>
                        <td>
                            <input type="text" name="atc_whatsapp_admin_phone" 
                                   value="<?php echo esc_attr(get_option('atc_whatsapp_admin_phone','')); ?>" 
                                   class="regular-text" placeholder="+919876543210">
                            <p class="description">Phone number with country code (e.g., +919876543210)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>WhatsApp API Token</th>
                        <td>
                            <input type="text" name="atc_whatsapp_api_token" 
                                   value="<?php echo esc_attr(get_option('atc_whatsapp_api_token','')); ?>" 
                                   class="regular-text">
                            <p class="description">For automated WhatsApp messages via Business API</p>
                        </td>
                    </tr>
                    <tr>
                        <th>WhatsApp Phone ID</th>
                        <td>
                            <input type="text" name="atc_whatsapp_phone_id" 
                                   value="<?php echo esc_attr(get_option('atc_whatsapp_phone_id','')); ?>" 
                                   class="regular-text">
                            <p class="description">Phone number ID from WhatsApp Business API</p>
                        </td>
                    </tr>
                    <tr>
                        <th>WhatsApp API Type</th>
                        <td>
                            <select name="atc_whatsapp_api_type" class="regular-text">
                                <option value="whatsapp_business" <?php selected(get_option('atc_whatsapp_api_type','whatsapp_business'), 'whatsapp_business'); ?>>WhatsApp Business API (Meta)</option>
                                <option value="twilio" <?php selected(get_option('atc_whatsapp_api_type'), 'twilio'); ?>>Twilio WhatsApp API</option>
                                <option value="webhook" <?php selected(get_option('atc_whatsapp_api_type'), 'webhook'); ?>>Custom Webhook</option>
                            </select>
                            <p class="description">Select the WhatsApp API service you're using</p>
                        </td>
                    </tr>
                    <tr>
                        <th>WhatsApp Webhook URL</th>
                        <td>
                            <input type="url" name="atc_whatsapp_webhook_url" 
                                   value="<?php echo esc_attr(get_option('atc_whatsapp_webhook_url','')); ?>" 
                                   class="regular-text">
                            <p class="description">Custom webhook URL for WhatsApp integration (if using webhook type)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Twilio Account SID</th>
                        <td>
                            <input type="text" name="atc_twilio_account_sid" 
                                   value="<?php echo esc_attr(get_option('atc_twilio_account_sid','')); ?>" 
                                   class="regular-text">
                            <p class="description">Twilio Account SID (if using Twilio)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Twilio WhatsApp From Number</th>
                        <td>
                            <input type="text" name="atc_twilio_whatsapp_from" 
                                   value="<?php echo esc_attr(get_option('atc_twilio_whatsapp_from','whatsapp:+14155238886')); ?>" 
                                   class="regular-text">
                            <p class="description">Twilio WhatsApp sender number (if using Twilio)</p>
                        </td>
                    </tr>
                </table>

                <h2>🔗 Webhook Integration</h2>
                <table class="form-table">
                    <tr>
                        <th>Webhook URL</th>
                        <td>
                            <input type="url" name="atc_webhook_url" 
                                   value="<?php echo esc_attr(get_option('atc_webhook_url','')); ?>" 
                                   class="regular-text" placeholder="https://example.com/webhook">
                            <p class="description">Booking data will be sent to this URL as JSON</p>
                        </td>
                    </tr>
                </table>

                <h2>📧 Email Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>Admin Notification Email(s)</th>
                        <td>
                            <input type="text" name="atc_admin_email" 
                                   value="<?php echo esc_attr(get_option('atc_admin_email', get_option('admin_email'))); ?>" 
                                   class="regular-text" placeholder="admin@example.com">
                            <p class="description">Email to receive booking notifications</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Email From Name</th>
                        <td>
                            <input type="text" name="atc_email_from_name" 
                                   value="<?php echo esc_attr(get_option('atc_email_from_name', get_bloginfo('name'))); ?>" 
                                   class="regular-text">
                            <p class="description">Name that appears in "From" field (e.g., Your Company Name)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Email From Address</th>
                        <td>
                            <input type="email" name="atc_email_from_address" 
                                   value="<?php echo esc_attr(get_option('atc_email_from_address', get_option('admin_email'))); ?>" 
                                   class="regular-text" placeholder="noreply@yourdomain.com">
                            <p class="description">
                                <strong>Important:</strong> Use an email address from your domain (e.g., noreply@yourdomain.com) to prevent spam.<br>
                                If using Gmail SMTP, use your Gmail address. For better deliverability, use a domain email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>Reply-To Email</th>
                        <td>
                            <input type="email" name="atc_email_reply_to" 
                                   value="<?php echo esc_attr(get_option('atc_email_reply_to', get_option('admin_email'))); ?>" 
                                   class="regular-text" placeholder="support@yourdomain.com">
                            <p class="description">Email address where replies should be sent (defaults to admin email)</p>
                        </td>
                    </tr>
                </table>

                <h3>SMTP Configuration (Optional)</h3>
                <table class="form-table">
                    <tr>
                        <th>Enable SMTP</th>
                        <td>
                            <label>
                                <input type="checkbox" name="atc_smtp_enabled" value="1" 
                                       <?php checked(1, get_option('atc_smtp_enabled',0)); ?>>
                                Enable SMTP for outgoing emails
                            </label>
                            <p class="description">Use custom SMTP instead of WordPress default mail</p>
                        </td>
                    </tr>
                    <tr>
                        <th>SMTP Host</th>
                        <td>
                            <input type="text" name="atc_smtp_host" 
                                   value="<?php echo esc_attr(get_option('atc_smtp_host','')); ?>" 
                                   class="regular-text" placeholder="smtp.gmail.com">
                        </td>
                    </tr>
                    <tr>
                        <th>SMTP Port</th>
                        <td>
                            <input type="text" name="atc_smtp_port" 
                                   value="<?php echo esc_attr(get_option('atc_smtp_port','587')); ?>" 
                                   class="regular-text" placeholder="587">
                            <p class="description">Usually 587 for TLS or 465 for SSL</p>
                        </td>
                    </tr>
                    <tr>
                        <th>SMTP Username</th>
                        <td>
                            <input type="text" name="atc_smtp_user" 
                                   value="<?php echo esc_attr(get_option('atc_smtp_user','')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th>SMTP Password</th>
                        <td>
                            <input type="password" name="atc_smtp_pass" 
                                   value="<?php echo esc_attr(get_option('atc_smtp_pass','')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th>Encryption</th>
                        <td>
                            <select name="atc_smtp_secure">
                                <option value="" <?php selected(get_option('atc_smtp_secure',''),''); ?>>None</option>
                                <option value="ssl" <?php selected(get_option('atc_smtp_secure',''),'ssl'); ?>>SSL</option>
                                <option value="tls" <?php selected(get_option('atc_smtp_secure',''),'tls'); ?>>TLS</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h3>📝 Email Template</h3>
                <table class="form-table">
                    <tr>
                        <th>Booking Notification Template</th>
                        <td>
                            <textarea name="atc_email_template" rows="10" class="large-text code"><?php 
                                echo esc_textarea(get_option('atc_email_template', 
                                    "New booking received:\n\nBooking ID: {booking_id}\nService: {service}\nCustomer: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\nPrice: {price_total} {currency}\n\nView in admin for details."
                                )); 
                            ?></textarea>
                            <p class="description">
                                <strong>Available placeholders:</strong><br>
                                {booking_id}, {service}, {customer_name}, {customer_email}, {customer_phone}, 
                                {price_total}, {currency}, {travel_date}, {return_date}
                            </p>
                        </td>
                    </tr>
                </table>

                <h2>👁️ Visitor Tracking & Notifications</h2>
                <table class="form-table">
                    <tr>
                        <th>Enable Visitor Tracking</th>
                        <td>
                            <label>
                                <input type="checkbox" name="atc_enable_visitor_tracking" value="1" 
                                       <?php checked(get_option('atc_enable_visitor_tracking', 1), 1); ?>>
                                Enable visitor tracking system
                            </label>
                            <p class="description">Turn off to completely disable all visitor tracking and analytics.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Track Logged-in Users Only</th>
                        <td>
                            <label>
                                <input type="checkbox" name="atc_track_logged_in_only" value="1" 
                                       <?php checked(get_option('atc_track_logged_in_only', 0), 1); ?>>
                                Only track users who are logged in
                            </label>
                            <p class="description">If enabled, guest visitors will not be tracked.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Visitor Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="atc_visitor_notifications_enabled" value="1" 
                                       <?php checked(get_option('atc_visitor_notifications_enabled', 1), 1); ?>>
                                Send notifications for important activities
                            </label>
                            <p class="description">Receive alerts for high-value actions like Bookings and Queries (Page views/Searches are detailed in dashboard only).</p>
                        </td>
                    </tr>
                </table>
                
                <h2>🔐 WhatsApp OTP Verification</h2>
                <table class="form-table">
                    <tr>
                        <th>Enable WhatsApp OTP</th>
                        <td>
                            <label>
                                <input type="checkbox" name="atc_whatsapp_otp_enabled" value="1" 
                                       <?php checked(get_option('atc_whatsapp_otp_enabled', 1), 1); ?>>
                                Enable WhatsApp OTP verification for registration and password reset
                            </label>
                            <p class="description">When enabled, users will receive OTP codes via WhatsApp instead of email for account verification and password reset. Make sure your WhatsApp API is configured above.</p>
                        </td>
                    </tr>
                </table>
                
                <h2>🗺️ Google Maps Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>Google Maps API Key</th>
                        <td>
                            <input type="text" name="atc_google_maps_api_key" 
                                   value="<?php echo esc_attr(get_option('atc_google_maps_api_key','')); ?>" 
                                   class="regular-text" placeholder="AIzaSy...">
                            <p class="description">
                                <strong>Required for map display on package details pages.</strong><br>
                                Get your API key from <a href="https://console.cloud.google.com/google/maps-apis" target="_blank">Google Cloud Console</a><br>
                                Enable "Maps Embed API" for your project.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings', 'primary', 'submit', true); ?>
            </form>
            
            <hr>
            
            <div class="atc-settings-info">
                <h3>ℹ️ Quick Setup Guide</h3>
                <ol>
                    <li><strong>Email:</strong> Add your admin email above to receive notifications</li>
                    <li><strong>WhatsApp (Optional):</strong> Add phone number for WhatsApp alerts</li>
                    <li><strong>SMTP (Optional):</strong> Enable if you want to use Gmail/custom SMTP</li>
                    <li><strong>Multi-Admin:</strong> Go to <a href="<?php echo admin_url('admin.php?page=atc-admin-recipients'); ?>">Admin Recipients</a> to add more admins</li>
                </ol>
                
                <h3>🔗 Next Steps</h3>
                <ul>
                    <li><a href="<?php echo admin_url('admin.php?page=atc-service-manager'); ?>">Configure Services</a> - Enable/disable travel services</li>
                    <li><a href="<?php echo admin_url('admin.php?page=atc-payment-settings'); ?>">Setup Payments</a> - Add Razorpay/Stripe credentials</li>
                    <li><a href="<?php echo admin_url('admin.php?page=atc-email-templates'); ?>">Customize Templates</a> - Edit email/WhatsApp messages</li>
                </ul>
            </div>
        </div>
        
        <style>
        .atc-settings-wrap {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .atc-settings-info {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 20px;
            margin-top: 30px;
            border-radius: 4px;
        }
        .atc-settings-info h3 {
            margin-top: 0;
            color: #0A1F44;
        }
        .atc-settings-info ul, .atc-settings-info ol {
            margin: 10px 0;
        }
        .atc-settings-info li {
            margin: 8px 0;
        }
        </style>
        <?php
    }
}
