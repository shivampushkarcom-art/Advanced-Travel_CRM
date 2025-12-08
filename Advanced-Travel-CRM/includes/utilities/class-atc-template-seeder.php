<?php
/**
 * Template Seeder - Admin AJAX Action
 * Location: includes/utilities/class-atc-template-seeder.php
 * 
 * Seeds booking status templates into the database.
 * Run by visiting: /wp-admin/admin.php?page=atc-dashboard&seed_templates=1
 * Fix admin recipients: /wp-admin/admin.php?page=atc-dashboard&fix_admin_recipients=1
 */

if (!defined('ABSPATH')) exit;

class ATC_Template_Seeder {
    
    public static function init() {
        add_action('admin_init', [__CLASS__, 'maybe_seed_templates']);
    }
    
    /**
     * Check if we should seed templates (via URL param or first-time)
     */
    public static function maybe_seed_templates() {
        // Manual trigger: Seed templates
        if (isset($_GET['seed_templates']) && current_user_can('manage_options')) {
            self::seed_all_templates();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Templates seeded successfully!</strong> WhatsApp and Email templates have been updated.</p></div>';
            });
        }
        
        // Manual trigger: Fix admin recipients
        if (isset($_GET['fix_admin_recipients']) && current_user_can('manage_options')) {
            self::fix_admin_recipients();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Admin recipients fixed!</strong> All events are now enabled for all admin recipients.</p></div>';
            });
        }
        
        // Auto-seed once on first run
        $seeded = get_option('atc_templates_seeded_v2', false);
        if (!$seeded && current_user_can('manage_options')) {
            self::seed_all_templates();
            update_option('atc_templates_seeded_v2', time());
        }
    }
    
    /**
     * Seed all templates
     */
    public static function seed_all_templates() {
        self::seed_whatsapp_templates();
        self::seed_email_templates();
        self::fix_admin_recipients(); // Also fix admin recipients
    }
    
    /**
     * Fix admin recipients - enable all events
     */
    public static function fix_admin_recipients() {
        global $wpdb;
        
        $table_name = defined('ATC_TABLE_ADMIN_RECIPIENTS') 
            ? ATC_TABLE_ADMIN_RECIPIENTS 
            : $wpdb->prefix . 'atc_admin_recipients';
        
        // All available event types
        $all_events = json_encode([
            'new_booking',
            'new_query', 
            'hot_lead',
            'warm_lead',
            'cold_lead',
            'booking_confirmed',
            'booking_cancelled',
            'booking_completed',
            'payment_received'
        ]);
        
        // Update all admin recipients to have all events enabled
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET notify_events = %s, email_enabled = 1, whatsapp_enabled = 1 WHERE active = 1",
            $all_events
        ));
        
        if (class_exists('ATC_Logger')) {
            ATC_Logger::log('info', 'Admin recipients fixed - all events enabled');
        }
    }
    
    /**
     * Seed WhatsApp Templates
     */
    private static function seed_whatsapp_templates() {
        global $wpdb;
        
        $table_name = defined('ATC_TABLE_WHATSAPP_TEMPLATES') 
            ? ATC_TABLE_WHATSAPP_TEMPLATES 
            : $wpdb->prefix . 'atc_whatsapp_templates';
        
        $templates = [
            [
                'slug' => 'customer_booking_confirmed',
                'name' => 'Booking Confirmed (Customer)',
                'event_type' => 'booking_confirmed',
                'recipient_type' => 'customer',
                'booking_status' => 'confirmed',
                'message_body' => "*Booking Confirmed!* 🎉\n\nHi {customer_name},\n\nYour trip to *{destination}* is confirmed!\n\n*Booking ID:* {booking_id}\n*Package:* {package_name}\n*Travel Date:* {travel_date}\n\nOur team will share the detailed itinerary shortly.\n\nRegards,\n*{company_name}*"
            ],
            [
                'slug' => 'customer_booking_cancelled',
                'name' => 'Booking Cancelled (Customer)',
                'event_type' => 'booking_cancelled',
                'recipient_type' => 'customer',
                'booking_status' => 'cancelled',
                'message_body' => "*Booking Cancelled* ❌\n\nHi {customer_name},\n\nYour booking #{booking_id} for *{destination}* has been cancelled.\n\nIf a refund is applicable, it will be processed within 5-7 business days.\n\nRegards,\n*{company_name}*"
            ],
            [
                'slug' => 'customer_booking_completed',
                'name' => 'Booking Completed (Customer)',
                'event_type' => 'booking_completed',
                'recipient_type' => 'customer',
                'booking_status' => 'completed',
                'message_body' => "*Welcome Back!* 🏡\n\nHi {customer_name},\n\nWe hope you had a wonderful trip to *{destination}*! ✈️\n\nPlease share your feedback with us.\n\nThank you for choosing *{company_name}*!"
            ],
            [
                'slug' => 'customer_booking_received',
                'name' => 'Booking Received (Customer)',
                'event_type' => 'booking_initiated',
                'recipient_type' => 'customer',
                'booking_status' => 'pending',
                'message_body' => "*Booking Received!* ⏳\n\nHi {customer_name},\n\nWe have received your booking request for *{destination}*.\n\n*Booking ID:* {booking_id}\n*Package:* {package_name}\n\nOur team is reviewing and will confirm shortly.\n\nThank you for choosing *{company_name}*!"
            ],
            [
                'slug' => 'admin_new_booking_enriched',
                'name' => 'Admin - New Booking (Enriched)',
                'event_type' => 'new_booking',
                'recipient_type' => 'admin',
                'booking_status' => null,
                'message_body' => "*🎫 New Booking*\n\n*ID:* #{booking_id}\n\n*Client:*\n👤 {customer_name}\n📞 {customer_phone}\n📧 {customer_email}\n\n*Trip:*\n📦 {package_name}\n📍 {destination}\n📅 {travel_date}\n👥 {adults} Ad, {children} Ch\n\n💰 {currency} {price_total}"
            ],
            [
                'slug' => 'admin_new_query_enriched',
                'name' => 'Admin - New Query (Enriched)',
                'event_type' => 'new_query',
                'recipient_type' => 'admin',
                'booking_status' => null,
                'message_body' => "*🔔 New Query*\n\n*Lead:*\n👤 {customer_name}\n📞 {customer_phone}\n📧 {customer_email}\n\n*Trip:*\n📦 {package_name}\n📍 {destination}\n📅 {travel_date}\n👥 {adults} Ad, {children} Ch\n💰 Budget: {budget_min} - {budget_max}\n\n*Message:*\n_{message}_"
            ],
            [
                'slug' => 'admin_new_lead_enriched',
                'name' => 'Admin - New Lead (Enriched)',
                'event_type' => 'hot_lead',
                'recipient_type' => 'admin',
                'booking_status' => null,
                'message_body' => "*🔥 New Lead*\n\n👤 {name}\n📞 {phone}\n📧 {email}\n\n📦 Package: {package_name}\n📍 Dest: {destination}\n🏨 Hotel: {hotel_type}\n📅 Date: {travel_date}\n💰 Budget: {budget_min} - {budget_max}\n\n_{message}_"
            ]
        ];
        
        foreach ($templates as $tpl) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE slug = %s", 
                $tpl['slug']
            ));
            
            $data = [
                'name' => $tpl['name'],
                'slug' => $tpl['slug'],
                'event_type' => $tpl['event_type'],
                'recipient_type' => $tpl['recipient_type'],
                'booking_status' => $tpl['booking_status'],
                'message_body' => $tpl['message_body'],
                'active' => 1,
                'priority' => 10
            ];
            
            if ($exists) {
                $wpdb->update($table_name, $data, ['id' => $exists]);
            } else {
                $wpdb->insert($table_name, $data);
            }
        }
    }
    
    /**
     * Seed Email Templates
     */
    private static function seed_email_templates() {
        // Booking Confirmed
        update_option('atc_email_template_booking_confirmed', [
            'subject' => '✅ Booking Confirmed! - #{booking_id}',
            'body' => "Dear {customer_name},\n\nWe are delighted to confirm your booking for **{destination}**!\n\n**Booking Details:**\n- **Booking ID:** #{booking_id}\n- **Package:** {package_name}\n- **Travel Date:** {travel_date}\n- **Travelers:** {adults} Adults, {children} Children\n\n**Total Amount:** {currency} {price_total}\n\nWe will send your detailed itinerary shortly.\n\nBest Regards,\n**{company_name}**"
        ]);
        
        // Booking Cancelled
        update_option('atc_email_template_booking_cancelled', [
            'subject' => '❌ Booking Cancelled - #{booking_id}',
            'body' => "Dear {customer_name},\n\nYour booking (#{booking_id}) for **{destination}** has been cancelled.\n\nIf eligible for a refund, it will be processed within 5-7 business days.\n\nBest Regards,\n**{company_name}**"
        ]);
        
        // Booking Received (Initiated)
        update_option('atc_email_template_booking_initiated', [
            'subject' => '⏳ Booking Received - #{booking_id}',
            'body' => "Dear {customer_name},\n\nThank you for choosing **{company_name}**!\n\nWe have received your booking request for **{destination}**.\n\n**Booking ID:** #{booking_id}\n**Package:** {package_name}\n\nOur team is reviewing your request and will confirm shortly.\n\nBest Regards,\n**{company_name}**"
        ]);
        
        // Admin - New Booking
        update_option('atc_email_template_admin_new_booking', [
            'subject' => '🎫 New Booking #{booking_id} - {customer_name}',
            'body' => "Hello Admin,\n\nA new booking has been received!\n\n**Booking Details**\n- Booking ID: #{booking_id}\n- Package: {package_name}\n- Service: {service_name}\n- Amount: {currency} {price_total}\n\n**Customer Info**\n- Name: {customer_name}\n- Phone: {customer_phone}\n- Email: {customer_email}\n\n**Trip Details**\n- Destination: {destination}\n- Travel Date: {travel_date}\n- Travelers: {adults} Adults, {children} Children\n\n**Special Requests**\n{special_requests}\n\nPlease proceed with confirmation.\n\nAdvanced Travel CRM"
        ]);
        
        // Admin - New Query
        update_option('atc_email_template_admin_new_query', [
            'subject' => '🔔 New Package Query - {customer_name}',
            'body' => "Hello Admin,\n\nA new package query has been received.\n\n**Customer Details**\n- Name: {customer_name}\n- Email: {customer_email}\n- Phone: {customer_phone}\n\n**Trip Requirements**\n- Package: {package_name}\n- Destination: {destination}\n- Travel Date: {travel_date}\n- Budget Range: {budget_min} - {budget_max}\n- Travelers: {adults} Adults, {children} Children\n\n**Message**\n{message}\n\nPlease contact the customer.\n\nAdvanced Travel CRM"
        ]);
    }
}
