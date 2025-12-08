<?php
/**
 * ATC WhatsApp Templates Manager
 * Separate management system for WhatsApp notification templates
 * 
 * Features:
 * - Create, Edit, Delete WhatsApp templates
 * - Assign templates to booking statuses
 * - Template variables with current features
 * - Setup guide and API configuration help
 */

if (!defined('ABSPATH')) exit;

class ATC_WhatsApp_Templates {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_post_atc_save_whatsapp_template', [__CLASS__, 'save_template']);
        add_action('admin_post_atc_delete_whatsapp_template', [__CLASS__, 'delete_template']);
        add_action('admin_post_atc_test_whatsapp_template', [__CLASS__, 'test_template']);
        add_action('wp_ajax_atc_get_template_variables', [__CLASS__, 'ajax_get_variables']);
    }
    
    public static function admin_menu() {
        add_submenu_page(
            'atc-dashboard',
            __('WhatsApp Templates', 'advanced-travel-crm'),
            __('WhatsApp Templates', 'advanced-travel-crm'),
            'manage_options',
            'atc-whatsapp-templates',
            [__CLASS__, 'templates_page']
        );
    }
    
    public static function templates_page() {
        global $wpdb;
        
        // Handle actions
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'edit' && $template_id) {
            self::edit_template_page($template_id);
            return;
        }
        
        if ($action === 'add') {
            self::edit_template_page(0);
            return;
        }
        
        // Get all templates
        $templates = $wpdb->get_results(
            "SELECT * FROM " . ATC_TABLE_WHATSAPP_TEMPLATES . " ORDER BY event_type, booking_status, priority ASC",
            ARRAY_A
        );
        
        // Group templates by event type
        $grouped_templates = [];
        foreach ($templates as $template) {
            $key = $template['event_type'] . ($template['booking_status'] ? '_' . $template['booking_status'] : '');
            if (!isset($grouped_templates[$key])) {
                $grouped_templates[$key] = [];
            }
            $grouped_templates[$key][] = $template;
        }
        
        ?>
        <div class="wrap atc-whatsapp-templates-wrap">
            <div class="atc-templates-header">
                <div>
                    <h1>💬 WhatsApp Templates</h1>
                    <p class="description">Manage WhatsApp notification templates for bookings, queries, and status changes</p>
                </div>
                <a href="<?php echo admin_url('admin.php?page=atc-whatsapp-templates&action=add'); ?>" class="button button-primary button-large">
                    ➕ Add New Template
                </a>
            </div>
            
            <?php if (isset($_GET['saved'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Template saved successfully!</strong></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Template deleted successfully!</strong></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['tested'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Test WhatsApp message sent!</strong></p>
                </div>
            <?php endif; ?>
            
            <!-- Setup Guide -->
            <div class="atc-setup-guide">
                <h2>📚 Setup Guide</h2>
                <div class="atc-guide-content">
                    <h3>1. Configure WhatsApp Business API</h3>
                    <p>Go to <a href="<?php echo admin_url('admin.php?page=atc-settings'); ?>">Settings → WhatsApp Notifications</a> and configure:</p>
                    <ul>
                        <li><strong>WhatsApp API Type:</strong> Choose WhatsApp Business API, Twilio, or Custom Webhook</li>
                        <li><strong>API Token:</strong> Your WhatsApp Business API access token</li>
                        <li><strong>Phone ID:</strong> Your phone number ID from Meta</li>
                        <li><strong>Admin Phone:</strong> Your phone number for receiving admin notifications</li>
                    </ul>
                    
                    <h3>2. Create Templates</h3>
                    <p>Create templates for different events:</p>
                    <ul>
                        <li><strong>Booking Created:</strong> Sent when customer makes a booking</li>
                        <li><strong>Status Changed:</strong> Sent when booking status changes (pending, confirmed, cancelled, etc.)</li>
                        <li><strong>Query Submitted:</strong> Sent when customer submits a query</li>
                        <li><strong>Payment Received:</strong> Sent when payment is received</li>
                    </ul>
                    
                    <h3>3. Assign to Statuses</h3>
                    <p>For booking status templates, select the specific status (confirmed, pending, cancelled, etc.) to assign the template.</p>
                    
                    <h3>4. Test Templates</h3>
                    <p>Use the "Test" button to send a test message to your admin phone number.</p>
                    
                    <details>
                        <summary><strong>📖 WhatsApp Business API Setup (Click to expand)</strong></summary>
                        <div class="atc-api-guide">
                            <h4>Step 1: Create Meta Business Account</h4>
                            <ol>
                                <li>Go to <a href="https://business.facebook.com" target="_blank">business.facebook.com</a></li>
                                <li>Create or select your Business Account</li>
                                <li>Add WhatsApp as a product</li>
                            </ol>
                            
                            <h4>Step 2: Get API Credentials</h4>
                            <ol>
                                <li>Go to <a href="https://developers.facebook.com/apps" target="_blank">developers.facebook.com/apps</a></li>
                                <li>Create a new app or select existing</li>
                                <li>Add "WhatsApp" product to your app</li>
                                <li>Get your <strong>Access Token</strong> from Graph API Explorer</li>
                                <li>Get your <strong>Phone Number ID</strong> from WhatsApp → API Setup</li>
                            </ol>
                            
                            <h4>Step 3: Configure in Plugin</h4>
                            <ol>
                                <li>Go to <strong>Travel CRM → Settings</strong></li>
                                <li>Enter your Access Token in "WhatsApp API Token"</li>
                                <li>Enter your Phone Number ID in "WhatsApp Phone ID"</li>
                                <li>Select "WhatsApp Business API" as API Type</li>
                                <li>Save settings</li>
                            </ol>
                            
                            <h4>Step 4: Test Configuration</h4>
                            <ol>
                                <li>Go to <strong>Travel CRM → WhatsApp Templates</strong></li>
                                <li>Click "Test" on any template</li>
                                <li>Check your WhatsApp for the test message</li>
                            </ol>
                            
                            <p><strong>📌 Note:</strong> For production use, you need to verify your business and get approved by Meta. Test mode allows limited messages for development.</p>
                        </div>
                    </details>
                </div>
            </div>
            
            <!-- Templates List -->
            <div class="atc-templates-list">
                <h2>📋 Your Templates</h2>
                
                <?php if (empty($templates)): ?>
                    <div class="atc-empty-state">
                        <p>No WhatsApp templates yet. <a href="<?php echo admin_url('admin.php?page=atc-whatsapp-templates&action=add'); ?>">Create your first template</a></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th>Event Type</th>
                                <th>Booking Status</th>
                                <th>Recipient</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($template['name']); ?></strong></td>
                                    <td>
                                        <span class="atc-badge atc-badge-<?php echo esc_attr($template['event_type']); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $template['event_type']))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($template['booking_status']): ?>
                                            <span class="atc-badge atc-badge-status">
                                                <?php echo esc_html(ucfirst($template['booking_status'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="atc-badge atc-badge-default">All Statuses</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="atc-badge atc-badge-<?php echo esc_attr($template['recipient_type']); ?>">
                                            <?php echo esc_html(ucfirst($template['recipient_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($template['active']): ?>
                                            <span class="atc-status-active">✓ Active</span>
                                        <?php else: ?>
                                            <span class="atc-status-inactive">✗ Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($template['priority']); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=atc-whatsapp-templates&action=edit&id=' . $template['id']); ?>" 
                                           class="button button-small">✏️ Edit</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=atc_test_whatsapp_template&template_id=' . $template['id']), 'atc_test_whatsapp_template'); ?>" 
                                           class="button button-small">🧪 Test</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=atc_delete_whatsapp_template&template_id=' . $template['id']), 'atc_delete_whatsapp_template'); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('Are you sure you want to delete this template?');">🗑️ Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Available Variables -->
            <div class="atc-variables-guide">
                <h2>📝 Available Template Variables</h2>
                <p>Use these variables in your templates. They will be automatically replaced with actual data:</p>
                
                <div class="atc-variables-grid">
                    <div class="atc-variable-group">
                        <h3>👤 Customer Information</h3>
                        <div class="atc-variable-list">
                            <code>{customer_name}</code>
                            <code>{customer_email}</code>
                            <code>{customer_phone}</code>
                            <code>{user_id}</code>
                        </div>
                    </div>
                    
                    <div class="atc-variable-group">
                        <h3>📋 Booking Details</h3>
                        <div class="atc-variable-list">
                            <code>{booking_id}</code>
                            <code>{service_name}</code>
                            <code>{destination}</code>
                            <code>{package_name}</code>
                            <code>{travel_date}</code>
                            <code>{return_date}</code>
                            <code>{adults}</code>
                            <code>{children}</code>
                            <code>{price_total}</code>
                            <code>{currency}</code>
                            <code>{payment_status}</code>
                            <code>{status}</code>
                            <code>{old_status}</code>
                            <code>{new_status}</code>
                        </div>
                    </div>
                    
                    <div class="atc-variable-group">
                        <h3>🏢 Company Information</h3>
                        <div class="atc-variable-list">
                            <code>{company_name}</code>
                            <code>{support_email}</code>
                            <code>{support_phone}</code>
                            <code>{customer_portal_url}</code>
                        </div>
                    </div>
                    
                    <div class="atc-variable-group">
                        <h3>📝 Query Information</h3>
                        <div class="atc-variable-list">
                            <code>{query_id}</code>
                            <code>{query_message}</code>
                            <code>{query_subject}</code>
                        </div>
                    </div>
                    
                    <div class="atc-variable-group">
                        <h3>💳 Payment Information</h3>
                        <div class="atc-variable-list">
                            <code>{transaction_id}</code>
                            <code>{payment_method}</code>
                            <code>{payment_date}</code>
                        </div>
                    </div>
                </div>
                
                <div class="atc-formatting-guide">
                    <h3>💡 WhatsApp Formatting Tips</h3>
                    <ul>
                        <li><strong>*Bold text*</strong> - Use asterisks for bold</li>
                        <li><strong>_Italic text_</strong> - Use underscores for italic</li>
                        <li><strong>~Strikethrough~</strong> - Use tildes for strikethrough</li>
                        <li><strong>`Monospace`</strong> - Use backticks for monospace</li>
                        <li>Use emojis to make messages more engaging 🎉 ✅ 📋</li>
                        <li>Keep messages concise and clear</li>
                        <li>Use line breaks (\n) for better readability</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .atc-whatsapp-templates-wrap {
            background: #f8fafc;
            padding: 20px;
        }
        
        .atc-templates-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .atc-templates-header h1 {
            margin: 0 0 10px 0;
            color: #0A1F44;
        }
        
        .atc-setup-guide {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #25D366;
        }
        
        .atc-setup-guide h2 {
            margin-top: 0;
            color: #0A1F44;
        }
        
        .atc-guide-content h3 {
            color: #1e40af;
            margin-top: 20px;
        }
        
        .atc-guide-content ul, .atc-guide-content ol {
            line-height: 1.8;
            margin-left: 20px;
        }
        
        .atc-api-guide {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .atc-api-guide h4 {
            color: #0A1F44;
            margin-top: 15px;
        }
        
        .atc-api-guide ol {
            line-height: 1.8;
        }
        
        .atc-templates-list {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .atc-templates-list h2 {
            margin-top: 0;
            color: #0A1F44;
        }
        
        .atc-empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        
        .atc-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .atc-badge-booking_created { background: #dbeafe; color: #1e40af; }
        .atc-badge-booking_confirmed { background: #d1fae5; color: #065f46; }
        .atc-badge-booking_cancelled { background: #fee2e2; color: #991b1b; }
        .atc-badge-query_submitted { background: #fef3c7; color: #92400e; }
        .atc-badge-payment_received { background: #ddd6fe; color: #5b21b6; }
        .atc-badge-status { background: #e0e7ff; color: #3730a3; }
        .atc-badge-default { background: #f1f5f9; color: #475569; }
        .atc-badge-customer { background: #dbeafe; color: #1e40af; }
        .atc-badge-admin { background: #fef3c7; color: #92400e; }
        
        .atc-status-active { color: #10b981; font-weight: 600; }
        .atc-status-inactive { color: #ef4444; font-weight: 600; }
        
        .atc-variables-guide {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .atc-variables-guide h2 {
            margin-top: 0;
            color: #0A1F44;
        }
        
        .atc-variables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .atc-variable-group {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #25D366;
        }
        
        .atc-variable-group h3 {
            margin: 0 0 10px 0;
            color: #0A1F44;
            font-size: 1rem;
        }
        
        .atc-variable-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .atc-variable-list code {
            background: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #25D366;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .atc-variable-list code:hover {
            background: #25D366;
            color: #fff;
            border-color: #25D366;
        }
        
        .atc-formatting-guide {
            background: #fef3c7;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #f59e0b;
        }
        
        .atc-formatting-guide h3 {
            margin-top: 0;
            color: #92400e;
        }
        
        .atc-formatting-guide ul {
            line-height: 1.8;
        }
        
        @media (max-width: 768px) {
            .atc-templates-header {
                flex-direction: column;
                gap: 15px;
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
        
        $template = null;
        if ($template_id > 0) {
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . ATC_TABLE_WHATSAPP_TEMPLATES . " WHERE id = %d",
                $template_id
            ), ARRAY_A);
            
            if (!$template) {
                echo '<div class="wrap"><div class="notice notice-error"><p>Template not found</p></div></div>';
                return;
            }
        }
        
        $is_new = !$template;
        
        ?>
        <div class="wrap atc-edit-whatsapp-template">
            <h1><?php echo $is_new ? '➕ Add New WhatsApp Template' : '✏️ Edit WhatsApp Template'; ?></h1>
            <a href="<?php echo admin_url('admin.php?page=atc-whatsapp-templates'); ?>" class="button">
                ← Back to Templates
            </a>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="atc-whatsapp-template-form">
                <?php wp_nonce_field('atc_save_whatsapp_template'); ?>
                <input type="hidden" name="action" value="atc_save_whatsapp_template">
                <input type="hidden" name="template_id" value="<?php echo $template_id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="name">Template Name *</label></th>
                        <td>
                            <input type="text" name="name" id="name" 
                                   value="<?php echo $template ? esc_attr($template['name']) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description">A descriptive name for this template (e.g., "Booking Confirmation - Confirmed Status")</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="slug">Template Slug *</label></th>
                        <td>
                            <input type="text" name="slug" id="slug" 
                                   value="<?php echo $template ? esc_attr($template['slug']) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description">Unique identifier (e.g., customer_booking_confirmed). Auto-generated if left empty.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="event_type">Event Type *</label></th>
                        <td>
                            <select name="event_type" id="event_type" required>
                                <option value="">Select Event Type</option>
                                <option value="booking_created" <?php selected($template['event_type'] ?? '', 'booking_created'); ?>>Booking Created</option>
                                <option value="booking_confirmed" <?php selected($template['event_type'] ?? '', 'booking_confirmed'); ?>>Booking Confirmed</option>
                                <option value="booking_pending" <?php selected($template['event_type'] ?? '', 'booking_pending'); ?>>Booking Pending</option>
                                <option value="booking_cancelled" <?php selected($template['event_type'] ?? '', 'booking_cancelled'); ?>>Booking Cancelled</option>
                                <option value="booking_completed" <?php selected($template['event_type'] ?? '', 'booking_completed'); ?>>Booking Completed</option>
                                <option value="booking_refunded" <?php selected($template['event_type'] ?? '', 'booking_refunded'); ?>>Booking Refunded</option>
                                <option value="booking_status_changed" <?php selected($template['event_type'] ?? '', 'booking_status_changed'); ?>>Booking Status Changed</option>
                                <option value="payment_received" <?php selected($template['event_type'] ?? '', 'payment_received'); ?>>Payment Received</option>
                                <option value="query_submitted" <?php selected($template['event_type'] ?? '', 'query_submitted'); ?>>Query Submitted</option>
                            </select>
                            <p class="description">When should this template be used?</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="booking_status">Booking Status</label></th>
                        <td>
                            <select name="booking_status" id="booking_status">
                                <option value="">All Statuses (Default)</option>
                                <option value="pending" <?php selected($template['booking_status'] ?? '', 'pending'); ?>>Pending</option>
                                <option value="confirmed" <?php selected($template['booking_status'] ?? '', 'confirmed'); ?>>Confirmed</option>
                                <option value="cancelled" <?php selected($template['booking_status'] ?? '', 'cancelled'); ?>>Cancelled</option>
                                <option value="completed" <?php selected($template['booking_status'] ?? '', 'completed'); ?>>Completed</option>
                                <option value="refunded" <?php selected($template['booking_status'] ?? '', 'refunded'); ?>>Refunded</option>
                            </select>
                            <p class="description">Assign this template to a specific booking status (optional). Leave empty for all statuses.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="recipient_type">Recipient Type *</label></th>
                        <td>
                            <select name="recipient_type" id="recipient_type" required>
                                <option value="customer" <?php selected($template['recipient_type'] ?? 'customer', 'customer'); ?>>Customer</option>
                                <option value="admin" <?php selected($template['recipient_type'] ?? '', 'admin'); ?>>Admin</option>
                            </select>
                            <p class="description">Who will receive this message?</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="message_body">WhatsApp Message *</label></th>
                        <td>
                            <textarea name="message_body" id="message_body" rows="15" class="large-text code" required><?php echo $template ? esc_textarea($template['message_body']) : ''; ?></textarea>
                            <p class="description">
                                Enter your WhatsApp message. Use variables like {customer_name}, {booking_id}, etc.<br>
                                <strong>Formatting:</strong> *bold*, _italic_, `monospace`, ~strikethrough~
                            </p>
                            <div class="atc-variable-helper">
                                <strong>Quick Insert Variables:</strong>
                                <div class="atc-quick-vars" id="atc-quick-vars"></div>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="priority">Priority</label></th>
                        <td>
                            <input type="number" name="priority" id="priority" 
                                   value="<?php echo $template ? esc_attr($template['priority']) : '0'; ?>" 
                                   class="small-text" min="0" max="100">
                            <p class="description">Higher priority templates are used first when multiple templates match (0 = default)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Status</th>
                        <td>
                            <label>
                                <input type="checkbox" name="active" value="1" 
                                       <?php checked($template['active'] ?? 1, 1); ?>>
                                Active (template will be used)
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        💾 <?php echo $is_new ? 'Create Template' : 'Update Template'; ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=atc-whatsapp-templates'); ?>" class="button button-large">
                        Cancel
                    </a>
                    <?php if (!$is_new): ?>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=atc_test_whatsapp_template&template_id=' . $template_id), 'atc_test_whatsapp_template'); ?>" 
                           class="button button-large">
                            🧪 Test Template
                        </a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <style>
        .atc-edit-whatsapp-template {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }
        
        .atc-variable-helper {
            margin-top: 10px;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 6px;
            border-left: 3px solid #0ea5e9;
        }
        
        .atc-quick-vars {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .atc-quick-vars code {
            background: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .atc-quick-vars code:hover {
            background: #0ea5e9;
            color: #fff;
            border-color: #0ea5e9;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto-generate slug from name
            $('#name').on('blur', function() {
                if (!$('#slug').val() || $('#slug').val() === '<?php echo $template ? esc_js($template['slug']) : ''; ?>') {
                    var slug = $(this).val().toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');
                    $('#slug').val(slug);
                }
            });
            
            // Quick insert variables
            var commonVars = [
                '{customer_name}', '{customer_phone}', '{customer_email}',
                '{booking_id}', '{service_name}', '{destination}', '{package_name}',
                '{travel_date}', '{price_total}', '{currency}', '{status}',
                '{company_name}', '{support_phone}', '{support_email}'
            ];
            
            var quickVarsHtml = '';
            commonVars.forEach(function(v) {
                quickVarsHtml += '<code onclick="insertVariable(\'' + v + '\')">' + v + '</code>';
            });
            $('#atc-quick-vars').html(quickVarsHtml);
        });
        
        function insertVariable(variable) {
            var textarea = document.getElementById('message_body');
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var text = textarea.value;
            textarea.value = text.substring(0, start) + variable + text.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + variable.length;
            textarea.focus();
        }
        </script>
        <?php
    }
    
    public static function save_template() {
        check_admin_referer('atc_save_whatsapp_template');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        // Generate slug if empty
        $slug = sanitize_title($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = sanitize_title($_POST['name'] ?? '');
        }
        
        // Check if slug already exists (for new templates)
        if ($template_id === 0) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM " . ATC_TABLE_WHATSAPP_TEMPLATES . " WHERE slug = %s",
                $slug
            ));
            
            if ($existing) {
                $slug .= '_' . time();
            }
        }
        
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'slug' => $slug,
            'message_body' => sanitize_textarea_field($_POST['message_body']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'booking_status' => !empty($_POST['booking_status']) ? sanitize_text_field($_POST['booking_status']) : null,
            'recipient_type' => sanitize_text_field($_POST['recipient_type']),
            'priority' => intval($_POST['priority'] ?? 0),
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        
        if ($template_id > 0) {
            // Update existing
            $wpdb->update(
                ATC_TABLE_WHATSAPP_TEMPLATES,
                $data,
                ['id' => $template_id]
            );
        } else {
            // Insert new
            $wpdb->insert(ATC_TABLE_WHATSAPP_TEMPLATES, $data);
        }
        
        wp_redirect(add_query_arg('saved', '1', admin_url('admin.php?page=atc-whatsapp-templates')));
        exit;
    }
    
    public static function delete_template() {
        check_admin_referer('atc_delete_whatsapp_template');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $template_id = intval($_GET['template_id'] ?? 0);
        
        if ($template_id > 0) {
            $wpdb->delete(
                ATC_TABLE_WHATSAPP_TEMPLATES,
                ['id' => $template_id]
            );
        }
        
        wp_redirect(add_query_arg('deleted', '1', admin_url('admin.php?page=atc-whatsapp-templates')));
        exit;
    }
    
    public static function test_template() {
        check_admin_referer('atc_test_whatsapp_template');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $template_id = intval($_GET['template_id'] ?? 0);
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_WHATSAPP_TEMPLATES . " WHERE id = %d",
            $template_id
        ), ARRAY_A);
        
        if (!$template) {
            wp_die('Template not found');
        }
        
        // Test data
        $test_data = [
            'customer_name' => 'Test Customer',
            'customer_phone' => get_option('atc_whatsapp_admin_phone', ''),
            'customer_email' => get_option('admin_email'),
            'booking_id' => 'TEST-' . date('Ymd-His'),
            'service_name' => 'Tours',
            'destination' => 'Goa, India',
            'package_name' => 'Test Package',
            'travel_date' => date('d M Y', strtotime('+7 days')),
            'return_date' => date('d M Y', strtotime('+10 days')),
            'adults' => '2',
            'children' => '1',
            'price_total' => '25000',
            'currency' => 'INR',
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'company_name' => get_bloginfo('name'),
            'support_phone' => get_option('atc_whatsapp_admin_phone', ''),
            'support_email' => get_option('atc_admin_email', get_option('admin_email')),
            'customer_portal_url' => home_url('/my-account/'),
        ];
        
        // Replace variables
        $message = self::replace_variables($template['message_body'], $test_data);
        
        // Send test WhatsApp
        if (class_exists('ATC_WhatsApp_Sender')) {
            $phone = get_option('atc_whatsapp_admin_phone', '');
            if (!empty($phone)) {
                $result = ATC_WhatsApp_Sender::send_message($message, $phone);
                if ($result) {
                    wp_redirect(add_query_arg('tested', '1', admin_url('admin.php?page=atc-whatsapp-templates')));
                    exit;
                }
            }
        }
        
        wp_redirect(add_query_arg('test_failed', '1', admin_url('admin.php?page=atc-whatsapp-templates')));
        exit;
    }
    
    /**
     * Replace variables in template
     */
    private static function replace_variables($template, $data) {
        $replacements = [
            '{customer_name}' => $data['customer_name'] ?? '',
            '{customer_email}' => $data['customer_email'] ?? '',
            '{customer_phone}' => $data['customer_phone'] ?? '',
            '{user_id}' => $data['user_id'] ?? '',
            '{booking_id}' => $data['booking_id'] ?? '',
            '{service_name}' => ucfirst($data['service_name'] ?? $data['service'] ?? ''),
            '{destination}' => $data['destination'] ?? '',
            '{package_name}' => $data['package_name'] ?? '',
            '{travel_date}' => $data['travel_date'] ?? '',
            '{return_date}' => $data['return_date'] ?? '',
            '{adults}' => $data['adults'] ?? '1',
            '{children}' => $data['children'] ?? '0',
            '{price_total}' => number_format($data['price_total'] ?? 0, 2),
            '{currency}' => $data['currency'] ?? get_option('atc_currency', 'INR'),
            '{payment_status}' => ucfirst($data['payment_status'] ?? ''),
            '{status}' => ucfirst($data['status'] ?? $data['new_status'] ?? ''),
            '{old_status}' => ucfirst($data['old_status'] ?? ''),
            '{new_status}' => ucfirst($data['new_status'] ?? $data['status'] ?? ''),
            '{company_name}' => get_bloginfo('name'),
            '{support_email}' => get_option('atc_admin_email', get_option('admin_email')),
            '{support_phone}' => get_option('atc_whatsapp_admin_phone', ''),
            '{customer_portal_url}' => home_url('/my-account/'),
            '{query_id}' => $data['query_id'] ?? $data['id'] ?? '',
            '{query_message}' => $data['message'] ?? $data['query'] ?? '',
            '{query_subject}' => $data['subject'] ?? '',
            '{transaction_id}' => $data['transaction_id'] ?? '',
            '{payment_method}' => $data['payment_method'] ?? '',
            '{payment_date}' => $data['payment_date'] ?? '',
        ];
        
        return strtr($template, $replacements);
    }
    
    /**
     * Get template for event and status
     */
    public static function get_template($event_type, $booking_status = null, $recipient_type = 'customer') {
        global $wpdb;
        
        // Build query
        $where = [
            $wpdb->prepare("event_type = %s", $event_type),
            $wpdb->prepare("recipient_type = %s", $recipient_type),
            "active = 1"
        ];
        
        if ($booking_status) {
            $where[] = $wpdb->prepare("(booking_status = %s OR booking_status IS NULL OR booking_status = '')", $booking_status);
        } else {
            $where[] = "(booking_status IS NULL OR booking_status = '')";
        }
        
        $query = "SELECT * FROM " . ATC_TABLE_WHATSAPP_TEMPLATES . " 
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY booking_status DESC, priority DESC, id ASC
                  LIMIT 1";
        
        return $wpdb->get_row($query, ARRAY_A);
    }
    
    public static function ajax_get_variables() {
        check_ajax_referer('atc_nonce', 'nonce');
        
        $variables = [
            'customer' => ['customer_name', 'customer_email', 'customer_phone', 'user_id'],
            'booking' => ['booking_id', 'service_name', 'destination', 'package_name', 'travel_date', 'return_date', 'adults', 'children', 'price_total', 'currency', 'payment_status', 'status', 'old_status', 'new_status'],
            'company' => ['company_name', 'support_email', 'support_phone', 'customer_portal_url'],
            'query' => ['query_id', 'query_message', 'query_subject'],
            'payment' => ['transaction_id', 'payment_method', 'payment_date'],
        ];
        
        wp_send_json_success($variables);
    }
}

