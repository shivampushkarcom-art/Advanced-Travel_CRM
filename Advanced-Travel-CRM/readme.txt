=== Advanced Travel CRM ===
Contributors: Shivam Pushkar
Tags: travel, booking, crm, whatsapp, notifications
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.4.0
License: GPLv2 or later

Complete travel booking and CRM solution with multi-service support, WhatsApp notifications, and advanced booking management.

== Description ==

Advanced Travel CRM is a comprehensive WordPress plugin designed for travel agencies and tour operators. It provides a complete booking system with integrated notifications and customer relationship management.

**Features:**

* 🏨 8 Service Types: Hotels, Flights, Tours, Trains, Safari, Car Rentals, Forex, Visa
* 🎨 Enterprise Package System with MakeMyTrip-style UI
* 🔧 Multi-Service Ecosystem with Service-Specific Themes
* 📝 Enhanced Package Query System with Customization
* 💬 WhatsApp Notifications (Semi-auto and Full-auto via Business API)
* 📧 Email Notifications
* 🔗 Webhook Integration
* 📊 Admin Dashboard with Booking Management
* 👤 Customer Dashboard with Booking History
* 🔍 Search Lead Tracking
* 💰 Dynamic Price Calculation
* 🎨 Beautiful Modern UI
* 🔒 Secure with Proper Nonce Verification
* 📱 Fully Responsive Design

**Service Configurations:**

Each service is fully configurable via JSON files:
- Custom search fields
- Custom booking fields
- Flexible pricing models
- Dynamic form generation

**Notification Channels:**

1. **WhatsApp** - Send booking alerts via WhatsApp (semi-auto link or full-auto via Business API)
2. **Email** - Automated email notifications to admin and customers
3. **Webhook** - Send booking data to external systems

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/advanced-travel-crm`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to ATC Settings to configure notifications
4. Use shortcodes to display booking forms on your pages

== Shortcodes ==

**Display Booking Interface:**
```
<div class="atc-tabs-container">
    <div class="atc-tabs">
        <div class="atc-tab active" data-target="search-tab">Search</div>
        <div class="atc-tab" data-target="booking-tab">Book Now</div>
    </div>
    <div class="atc-tab-content active" id="search-tab">
        <form class="atc-search-form" data-service="hotels">
            <div class="atc-form-fields"></div>
            <button class="atc-btn" type="submit">Search</button>
        </form>
    </div>
    <div class="atc-tab-content" id="booking-tab">
        <form class="atc-booking-form" data-service="hotels">
            <div class="atc-form-fields"></div>
            <div class="atc-total">Total: 0</div>
            <button class="atc-btn" type="submit">Book Now</button>
        </form>
    </div>
</div>
```

**User Dashboard:**
`[atc_account_dashboard]`

**Login Form:**
`[atc_login_form]`

**Registration Form:**
`[atc_register_form]`

== Configuration ==

**WhatsApp Setup:**

1. Go to ATC Settings
2. Enter your WhatsApp phone number (with country code, e.g., +919876543210)
3. For automated messages, configure WhatsApp Business API credentials

**Webhook Setup:**

1. Go to ATC Settings
2. Enter your webhook URL
3. Booking data will be sent as JSON POST request

== Service Configuration ==

Services are configured via JSON files in `/services/` directory:

* hotels.json
* flights.json
* tours.json
* trains.json
* safari.json
* cars.json
* forex.json

Each configuration includes:
- Search fields
- Booking fields
- Pricing rules

== Frequently Asked Questions ==

= How do I add a new service? =

1. Create a new JSON file in the `services/` directory
2. Define search_fields, booking_fields, and pricing
3. Add the service to the Service Manager

= Can I customize the form fields? =

Yes! Edit the corresponding JSON file in the `services/` directory.

= How do WhatsApp notifications work? =

Two modes:
- Semi-auto: Generates a WhatsApp link that admin clicks to send
- Full-auto: Uses WhatsApp Business API to send automatically

= Is it compatible with other plugins? =

Yes! The plugin uses WordPress standards and should work with most themes and plugins.

== Changelog ==

= 1.0.0 =
* Initial release
* 7 service types
* WhatsApp, Email, and Webhook notifications
* Admin booking management
* User dashboard
* Dynamic form generation
* Live price calculation
* Search lead tracking

== Upgrade Notice ==

= 1.0.0 =
First stable release.

== Support ==

For support, please contact: shivam@example.com

== Credits ==

Developed by: Shivam Pushkar
Version: 1.0.0
License: GPL v2 or later