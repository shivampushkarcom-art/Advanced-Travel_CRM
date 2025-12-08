<?php
/**
 * ATC Email Templates Manager
 * Manage all email and WhatsApp notification templates
 */

if (!defined('ABSPATH')) exit;

class ATC_Email_Templates {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_post_atc_save_template', [__CLASS__, 'save_template']);
        add_action('admin_post_atc_test_template', [__CLASS__, 'test_template']);
    }
    
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('Email Templates', 'advanced-travel-crm'),
            __('Email Templates', 'advanced-travel-crm'),
            'manage_options',
            'atc-email-templates',
            [__CLASS__, 'templates_page']
        );
    }
    
    public static function templates_page() {
        global $wpdb;
        
        // Handle edit
        $editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        
        if ($editing) {
            self::edit_template_page($editing);
            return;
        }
        
        // Get all templates
        $templates = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_EMAIL_TEMPLATES . " ORDER BY id ASC",
            ARRAY_A
        );
        
        ?>
        <div class="wrap atc-templates-wrap">
            <h1>📧 Email Templates</h1>
            <p class="description">Manage email notification templates for admins and customers</p>
            <p><a href="<?php echo admin_url('admin.php?page=atc-whatsapp-templates'); ?>" class="button">💬 Manage WhatsApp Templates</a></p>
            
            <?php if (isset($_GET['saved'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Template saved successfully!</strong></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['tested'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Test email sent successfully!</strong></p>
                </div>
            <?php endif; ?>
            
            <div class="atc-templates-grid">
                <?php foreach ($templates as $template): 
                    $type_badge = $template['recipient_type'] === 'admin' ? 'Admin' : 'Customer';
                    $type_color = $template['recipient_type'] === 'admin' ? '#0ea5e9' : '#10b981';
                    $active_badge = $template['active'] ? '✓ Active' : '✗ Inactive';
                    $active_color = $template['active'] ? '#10b981' : '#ef4444';
                ?>
                <div class="atc-template-card">
                    <div class="atc-template-header">
                        <h3><?php echo esc_html($template['name']); ?></h3>
                        <div class="atc-template-badges">
                            <span class="atc-badge" style="background: <?php echo $type_color; ?>20; color: <?php echo $type_color; ?>;">
                                <?php echo $type_badge; ?>
                            </span>
                            <span class="atc-badge" style="background: <?php echo $active_color; ?>20; color: <?php echo $active_color; ?>;">
                                <?php echo $active_badge; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="atc-template-content">
                        <p class="atc-template-slug"><code><?php echo esc_html($template['slug']); ?></code></p>
                        
                        <div class="atc-template-preview">
                            <strong>Subject:</strong><br>
                            <span><?php echo esc_html(wp_trim_words($template['subject'], 10)); ?></span>
                        </div>
                        
                        <div class="atc-template-channels">
                            <?php if (!empty($template['body'])): ?>
                                <span class="atc-channel-badge">📧 Email</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="atc-template-actions">
                        <a href="<?php echo admin_url('admin.php?page=atc-email-templates&edit=' . $template['id']); ?>" 
                           class="button button-primary">
                            ✏️ Edit
                        </a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=atc_test_template&template_id=' . $template['id']), 'atc_test_template'); ?>" 
                           class="button">
                            🧪 Test Send
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="atc-templates-info">
                <h2>ℹ️ How Templates Work</h2>
                <ol>
                    <li><strong>Edit Template:</strong> Customize the message content</li>
                    <li><strong>Use Variables:</strong> Add placeholders like <code>{customer_name}</code>, <code>{booking_id}</code></li>
                    <li><strong>Test Send:</strong> Send test notification to yourself</li>
                    <li><strong>Activate:</strong> Enable/disable template as needed</li>
                </ol>
                
                <h3>📋 Available Variables</h3>
                <div class="atc-variables-grid">
                    <div class="atc-variable-group">
                        <h4>Booking Variables:</h4>
                        <code>{booking_id}</code>
                        <code>{service_name}</code>
                        <code>{price_total}</code>
                        <code>{currency}</code>
                        <code>{travel_date}</code>
                        <code>{return_date}</code>
                        <code>{payment_status}</code>
                    </div>
                    
                    <div class="atc-variable-group">
                        <h4>Customer Variables:</h4>
                        <code>{customer_name}</code>
                        <code>{customer_email}</code>
                        <code>{customer_phone}</code>
                    </div>
                    
                    <div class="atc-variable-group">
                        <h4>Lead Variables:</h4>
                        <code>{lead_name}</code>
                        <code>{destination}</code>
                        <code>{score}</code>
                        <code>{temperature}</code>
                        <code>{budget_min}</code>
                        <code>{budget_max}</code>
                    </div>
                    
                    <div class="atc-variable-group">
                        <h4>System Variables:</h4>
                        <code>{company_name}</code>
                        <code>{support_email}</code>
                        <code>{support_phone}</code>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .atc-templates-wrap {
            background: #f8fafc;
            padding: 20px;
        }
        
        .atc-templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .atc-template-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #0ea5e9;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .atc-template-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .atc-template-header h3 {
            margin: 0;
            color: #0A1F44;
            font-size: 1.2rem;
        }
        
        .atc-template-badges {
            display: flex;
            gap: 8px;
        }
        
        .atc-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .atc-template-slug {
            font-size: 0.9rem;
            color: #64748b;
            margin: 0;
        }
        
        .atc-template-preview {
            background: #f8fafc;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        .atc-template-preview strong {
            color: #0A1F44;
        }
        
        .atc-template-channels {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .atc-channel-badge {
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        
        .atc-template-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .atc-templates-info {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        
        .atc-templates-info h2 {
            color: #0A1F44;
            margin-top: 0;
        }
        
        .atc-templates-info h3 {
            color: #1e40af;
            margin-top: 25px;
        }
        
        .atc-templates-info ol {
            line-height: 1.8;
        }
        
        .atc-variables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .atc-variable-group {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #D4AF37;
        }
        
        .atc-variable-group h4 {
            margin: 0 0 10px 0;
            color: #0A1F44;
        }
        
        .atc-variable-group code {
            display: block;
            background: #fff;
            padding: 6px 10px;
            margin: 5px 0;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #0ea5e9;
        }
        
        @media (max-width: 768px) {
            .atc-templates-grid {
                grid-template-columns: 1fr;
            }
            .atc-variables-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    public static function edit_template_page($template_id) {
        global $wpdb;
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_EMAIL_TEMPLATES . " WHERE id = %d",
            $template_id
        ), ARRAY_A);
        
        if (!$template) {
            echo '<div class="wrap"><h1>Template not found</h1></div>';
            return;
        }
        
        ?>
        <div class="wrap atc-edit-template-wrap">
            <h1>✏️ Edit Template: <?php echo esc_html($template['name']); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=atc-email-templates'); ?>" class="button">
                ← Back to Templates
            </a>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('atc_save_template'); ?>
                <input type="hidden" name="action" value="atc_save_template">
                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                
                <table class="form-table">
                    <tr>
                        <th>Template Name</th>
                        <td>
                            <input type="text" name="name" value="<?php echo esc_attr($template['name']); ?>" 
                                   class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Status</th>
                        <td>
                            <label>
                                <input type="checkbox" name="active" value="1" <?php checked($template['active'], 1); ?>>
                                Active (template will be used)
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Email Subject</th>
                        <td>
                            <input type="text" name="subject" value="<?php echo esc_attr($template['subject']); ?>" 
                                   class="large-text">
                            <p class="description">Use variables like {booking_id}, {customer_name}</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Email Body</th>
                        <td>
                            <textarea name="body" rows="12" class="large-text code"><?php echo esc_textarea($template['body']); ?></textarea>
                            <p class="description">Email message content (plain text or HTML)</p>
                        </td>
                    </tr>
                    
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        💾 Save Template
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=atc-email-templates'); ?>" class="button button-large">
                        Cancel
                    </a>
                </p>
            </form>
            
            <div class="atc-template-preview-box">
                <h2>📋 Available Variables for This Template</h2>
                <p>Copy and paste these into your template:</p>
                <div class="atc-variables-list">
                    <?php
                    $vars = explode(',', $template['variables']);
                    foreach ($vars as $var):
                        $var = trim($var);
                        if ($var):
                    ?>
                        <code class="atc-variable-tag">{<?php echo esc_html($var); ?>}</code>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>
        </div>
        
        <style>
        .atc-edit-template-wrap {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        
        .atc-template-preview-box {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 20px;
            margin-top: 30px;
            border-radius: 4px;
        }
        
        .atc-template-preview-box h2 {
            margin-top: 0;
            color: #0A1F44;
        }
        
        .atc-variables-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .atc-variable-tag {
            background: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #0ea5e9;
            cursor: pointer;
            border: 2px solid #e0f2fe;
        }
        
        .atc-variable-tag:hover {
            background: #0ea5e9;
            color: #fff;
            border-color: #0ea5e9;
        }
        </style>
        <?php
    }
    
    public static function save_template() {
        check_admin_referer('atc_save_template');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $template_id = intval($_POST['template_id']);
        
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'subject' => sanitize_text_field($_POST['subject']),
            'body' => wp_kses_post($_POST['body']),
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        
        $wpdb->update(
            ATC_TABLE_EMAIL_TEMPLATES,
            $data,
            ['id' => $template_id]
        );
        
        wp_redirect(add_query_arg('saved', '1', admin_url('admin.php?page=atc-email-templates')));
        exit;
    }
    
    public static function test_template() {
        check_admin_referer('atc_test_template');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $template_id = intval($_GET['template_id']);
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_EMAIL_TEMPLATES . " WHERE id = %d",
            $template_id
        ), ARRAY_A);
        
        if (!$template) {
            wp_die('Template not found');
        }
        
        // Test data
        $test_data = [
            'booking_id' => 'TEST-' . date('Ymd-His'),
            'customer_name' => 'Test Customer',
            'customer_email' => get_option('admin_email'),
            'customer_phone' => '+919876543210',
            'service_name' => 'Test Service',
            'price_total' => '25000',
            'currency' => 'INR',
            'company_name' => get_bloginfo('name'),
            'support_email' => get_option('admin_email'),
        ];
        
        // Send test email
        if (class_exists('ATC_Email_Sender')) {
            ATC_Email_Sender::send(get_option('admin_email'), $template, $test_data);
        }
        
        wp_redirect(add_query_arg('tested', '1', admin_url('admin.php?page=atc-email-templates')));
        exit;
    }
}

// Initialized via main plugin file