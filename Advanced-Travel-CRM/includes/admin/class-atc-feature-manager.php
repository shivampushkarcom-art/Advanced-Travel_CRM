<?php
/**
 * ATC Feature Manager
 * SaaS-style toggle switches for every major feature
 * Enables/disables features without code changes
 */

if (!defined('ABSPATH')) exit;

class ATC_Feature_Manager {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_post_atc_save_features', [__CLASS__, 'save_features']);
    }
    
    /**
     * Get feature status
     */
    public static function is_enabled($feature) {
        return (bool) get_option('atc_feature_' . $feature, true);
    }
    
    /**
     * Get all features with status
     */
    public static function get_all_features() {
        return [
            'core' => [
                'label' => '🎯 CORE FEATURES',
                'features' => [
                    'booking_system' => [
                        'name' => 'Multi-Service Booking System',
                        'description' => 'Enable booking functionality for all services',
                        'default' => true,
                    ],
                    'lead_capture' => [
                        'name' => 'Lead Capture System',
                        'description' => 'Automatically capture leads from search forms and popups',
                        'default' => true,
                    ],
                    'lead_popup' => [
                        'name' => 'Lead Generation Popup',
                        'description' => 'Auto-popup lead capture form (appears based on time/scroll/exit intent)',
                        'default' => true,
                    ],
                    'customer_portal' => [
                        'name' => 'Customer Account Portal',
                        'description' => 'Enable customer dashboard, booking history, profile',
                        'default' => true,
                    ],
                    'admin_notifications' => [
                        'name' => 'Admin Notification System',
                        'description' => 'Multi-admin email/WhatsApp notifications',
                        'default' => true,
                    ],
                ]
            ],
            'authentication' => [
                'label' => '🔐 AUTHENTICATION',
                'features' => [
                    'user_registration' => [
                        'name' => 'User Registration',
                        'description' => 'Allow customers to create accounts',
                        'default' => true,
                    ],
                    'email_verification' => [
                        'name' => 'WhatsApp OTP Verification',
                        'description' => 'Require WhatsApp OTP verification after registration and for password reset',
                        'default' => true,
                    ],
                    'require_login_booking' => [
                        'name' => 'Require Login for Booking',
                        'description' => 'Force customers to login before booking',
                        'default' => true,
                    ],
                    'customer_login_system' => [
                        'name' => 'Customer Login System',
                        'description' => 'Enable customer registration, login, and account management',
                        'default' => true,
                    ],
                    'social_login' => [
                        'name' => 'Social Login (Google/Facebook)',
                        'description' => 'Enable social login buttons (requires setup)',
                        'default' => false,
                    ],
                    'two_factor_auth' => [
                        'name' => 'Two-Factor Authentication (2FA)',
                        'description' => 'Extra security layer for login',
                        'default' => false,
                    ],
                ]
            ],
            'payments' => [
                'label' => '💳 PAYMENTS',
                'features' => [
                    'payment_gateway' => [
                        'name' => 'Online Payment Gateway',
                        'description' => 'Enable Razorpay/Stripe payment collection',
                        'default' => false,
                    ],
                    'payment_required' => [
                        'name' => 'Require Payment Before Booking',
                        'description' => 'Force payment before confirming booking',
                        'default' => false,
                    ],
                    'manual_payment' => [
                        'name' => 'Allow Manual Payment (Pay Later)',
                        'description' => 'Let customers book without immediate payment',
                        'default' => true,
                    ],
                    'razorpay_gateway' => [
                        'name' => 'Razorpay Gateway',
                        'description' => 'Indian payment gateway',
                        'default' => false,
                    ],
                    'stripe_gateway' => [
                        'name' => 'Stripe Gateway',
                        'description' => 'International payment gateway',
                        'default' => false,
                    ],
                ]
            ],
            'notifications' => [
                'label' => '📧 NOTIFICATIONS',
                'features' => [
                    'email_notifications' => [
                        'name' => 'Email Notifications',
                        'description' => 'Send emails to admins and customers',
                        'default' => true,
                    ],
                    'whatsapp_notifications' => [
                        'name' => 'WhatsApp Notifications',
                        'description' => 'Send WhatsApp messages (semi-auto/full-auto)',
                        'default' => true,
                    ],
                    'sms_notifications' => [
                        'name' => 'SMS Notifications',
                        'description' => 'Send SMS alerts (requires API)',
                        'default' => false,
                    ],
                    'webhook_integration' => [
                        'name' => 'Webhook Integration',
                        'description' => 'Send data to external systems',
                        'default' => false,
                    ],
                    'multi_admin_recipients' => [
                        'name' => 'Multi-Admin Recipients',
                        'description' => 'Configure multiple admin notification recipients',
                        'default' => true,
                    ],
                ]
            ],
            'search_booking' => [
                'label' => '🔍 SEARCH & BOOKING',
                'features' => [
                    'show_search_results' => [
                        'name' => 'Show Search Results',
                        'description' => 'Display packages/results (vs silent lead capture)',
                        'default' => false,
                    ],
                    'booking_modal' => [
                        'name' => 'Dynamic Booking Modal',
                        'description' => 'Open booking form in popup window',
                        'default' => true,
                    ],
                    'price_calculator' => [
                        'name' => 'Real-time Price Calculator',
                        'description' => 'Calculate prices as user fills form',
                        'default' => true,
                    ],
                    'service_availability' => [
                        'name' => 'Service Availability Check',
                        'description' => 'Check dates/capacity before booking',
                        'default' => false,
                    ],
                ]
            ],
            'cancellation' => [
                'label' => '❌ CANCELLATION SYSTEM',
                'features' => [
                    'booking_cancellation' => [
                        'name' => 'Allow Booking Cancellation',
                        'description' => 'Let customers cancel their bookings',
                        'default' => true,
                    ],
                    'cancellation_window' => [
                        'name' => 'Cancellation Time Window',
                        'description' => 'Set deadline for cancellations (24/48/72 hours)',
                        'default' => true,
                    ],
                    'cancellation_approval' => [
                        'name' => 'Require Admin Approval',
                        'description' => 'Admin must approve cancellation requests',
                        'default' => false,
                    ],
                    'process_refunds' => [
                        'name' => 'Process Refunds',
                        'description' => 'Allow refunds for cancelled bookings',
                        'default' => false,
                    ],
                ]
            ],
            'analytics' => [
                'label' => '📊 ANALYTICS & REPORTS',
                'features' => [
                    'dashboard_analytics' => [
                        'name' => 'Dashboard Analytics',
                        'description' => 'Show stats and charts in admin dashboard',
                        'default' => true,
                    ],
                    'lead_scoring' => [
                        'name' => 'Lead Scoring System',
                        'description' => 'Auto-score leads (Hot/Warm/Cold)',
                        'default' => true,
                    ],
                    'customer_segmentation' => [
                        'name' => 'Customer Segmentation',
                        'description' => 'Categorize customers (New/Regular/VIP)',
                        'default' => true,
                    ],
                    'revenue_reports' => [
                        'name' => 'Revenue Reports',
                        'description' => 'Generate financial reports',
                        'default' => true,
                    ],
                ]
            ],
            'automation' => [
                'label' => '🤖 AUTOMATION',
                'features' => [
                    'payment_reminders' => [
                        'name' => 'Payment Reminders',
                        'description' => 'Auto-remind for pending payments',
                        'default' => true,
                    ],
                    'booking_reminders' => [
                        'name' => 'Booking Reminders',
                        'description' => 'Remind customers 1 day before trip',
                        'default' => true,
                    ],
                    'review_requests' => [
                        'name' => 'Review Requests',
                        'description' => 'Auto-request reviews after trip',
                        'default' => false,
                    ],
                    'daily_summary' => [
                        'name' => 'Daily Admin Summary',
                        'description' => 'Send daily stats email to admins',
                        'default' => false,
                    ],
                ]
            ],
        ];
    }
    
    /**
     * Admin menu
     */
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Feature Manager', 'advanced-travel-crm'),
            __('Features', 'advanced-travel-crm'),
            'manage_options',
            'atc-feature-manager',
            [__CLASS__, 'features_page']
        );
    }
    
    /**
     * Features page
     */
    public static function features_page() {
        $features = self::get_all_features();
        
        ?>
        <div class="wrap atc-features-wrap">
            <h1>🎛️ Feature Manager</h1>
            <p class="description">Enable or disable features to customize your CRM. Changes take effect immediately.</p>
            
            <?php if (isset($_GET['saved'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Features saved successfully!</strong></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('atc_save_features'); ?>
                <input type="hidden" name="action" value="atc_save_features">
                
                <?php foreach ($features as $group_key => $group): ?>
                <div class="atc-feature-group">
                    <h2><?php echo esc_html($group['label']); ?></h2>
                    
                    <table class="form-table atc-features-table">
                        <?php foreach ($group['features'] as $feature_key => $feature): 
                            $enabled = self::is_enabled($feature_key);
                        ?>
                        <tr>
                            <th scope="row">
                                <strong><?php echo esc_html($feature['name']); ?></strong>
                                <p class="description"><?php echo esc_html($feature['description']); ?></p>
                            </th>
                            <td>
                                <label class="atc-toggle-switch">
                                    <input type="checkbox" name="features[<?php echo esc_attr($feature_key); ?>]" 
                                           value="1" <?php checked($enabled, true); ?>>
                                    <span class="atc-toggle-slider"></span>
                                </label>
                                <span class="atc-toggle-status">
                                    <?php echo $enabled ? '<span style="color:#10b981;">✓ Enabled</span>' : '<span style="color:#64748b;">○ Disabled</span>'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endforeach; ?>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        💾 Save All Features
                    </button>
                </p>
            </form>
        </div>
        
        <style>
        .atc-features-wrap {
            background: #f8fafc;
            padding: 20px;
        }
        
        .atc-features-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .atc-features-actions {
            display: flex;
            gap: 10px;
        }
        
        .atc-feature-group {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .atc-feature-group h2 {
            margin-top: 0;
            color: #0A1F44;
            padding-bottom: 15px;
            border-bottom: 3px solid #D4AF37;
        }
        
        .atc-features-table th {
            width: 70%;
        }
        
        .atc-features-table td {
            width: 30%;
            text-align: right;
        }
        
        /* Toggle Switch */
        .atc-toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin-right: 10px;
        }
        
        .atc-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .atc-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 30px;
        }
        
        .atc-toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .atc-toggle-slider {
            background-color: #10b981;
        }
        
        input:checked + .atc-toggle-slider:before {
            transform: translateX(30px);
        }
        
        .atc-toggle-status {
            display: inline-block;
            min-width: 100px;
            text-align: left;
            font-weight: 600;
        }
        </style>
        <?php
    }
    
    /**
     * Save features
     */
    public static function save_features() {
        check_admin_referer('atc_save_features');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $features = $_POST['features'] ?? [];
        $all_features = self::get_all_features();
        
        // Update all features
        foreach ($all_features as $group) {
            foreach ($group['features'] as $feature_key => $feature) {
                $enabled = isset($features[$feature_key]) ? 1 : 0;
                update_option('atc_feature_' . $feature_key, $enabled);
            }
        }
        
        ATC_Logger::log('info', 'Features updated by admin');
        
        wp_redirect(add_query_arg('saved', '1', admin_url('admin.php?page=atc-feature-manager')));
        exit;
    }
}
