# Visitor Tracking & Auto-Notification System - Complete Guide

## Overview
This is a comprehensive JustDial-style visitor tracking system that automatically captures visitor details and sends notifications to admin via WhatsApp and Email.

## Features Implemented

### 1. **Automatic Visitor Tracking**
- ✅ Captures IP address, location (country, city, state)
- ✅ Detects device type (mobile, tablet, desktop)
- ✅ Identifies browser and OS
- ✅ Tracks screen resolution
- ✅ Records referrer and landing page
- ✅ Tracks session duration and page views

### 2. **Activity Tracking**
The system automatically tracks:
- ✅ **Page Views** - Every page visit
- ✅ **Searches** - All search queries
- ✅ **Bookings** - When bookings are created
- ✅ **Queries** - When package queries are submitted
- ✅ **Package Views** - When packages are viewed
- ✅ **Form Submissions** - Any form submissions

### 3. **Auto-Notifications**
- ✅ **WhatsApp Notifications** - Sends formatted messages to admin WhatsApp
- ✅ **Email Notifications** - Sends HTML emails to admin email
- ✅ **Real-time** - Notifications sent immediately when activities occur
- ✅ **Configurable** - Can be enabled/disabled in settings

### 4. **WhatsApp API Integration**
Supports multiple WhatsApp APIs:
- ✅ **WhatsApp Business API (Meta)** - Official WhatsApp Business API
- ✅ **Twilio WhatsApp API** - Twilio integration
- ✅ **Custom Webhook** - For custom integrations

### 5. **Admin Dashboard**
- ✅ **Visitor Analytics** - View all visitor activities
- ✅ **Statistics** - Unique visitors, total activities, bookings, queries
- ✅ **Filters** - Filter by activity type, service, date range
- ✅ **Detailed View** - See location, device, and activity details

## Setup Instructions

### Step 1: Database Setup
The visitor tracking table is automatically created when you activate/update the plugin. No manual setup needed.

### Step 2: Configure WhatsApp API

1. Go to **Travel CRM → Settings**
2. Scroll to **WhatsApp Notifications** section
3. Fill in:
   - **WhatsApp Admin Phone**: Your phone number with country code (e.g., +919876543210)
   - **WhatsApp API Type**: Select your API provider
   - **WhatsApp API Token**: Your API access token
   - **WhatsApp Phone ID**: Phone number ID (for WhatsApp Business API)
   - **Twilio Account SID**: If using Twilio
   - **WhatsApp Webhook URL**: If using custom webhook

### Step 3: Configure Email Settings

1. Go to **Travel CRM → Settings**
2. Scroll to **Email Settings** section
3. Fill in:
   - **Admin Notification Email(s)**: Email to receive notifications
   - **SMTP Settings**: Configure if using custom SMTP

### Step 4: Enable Visitor Notifications

1. Go to **Travel CRM → Settings**
2. Scroll to **Visitor Tracking & Notifications** section
3. Check **"Enable Visitor Notifications"**
4. Click **Save Settings**

## How It Works

### Automatic Tracking
1. **Page Load**: Every page visit is automatically tracked
2. **Activity Detection**: System detects searches, bookings, queries, package views
3. **Data Collection**: Captures visitor details (IP, location, device, browser)
4. **Database Storage**: All data stored in `wp_atc_visitor_tracking` table
5. **Notification Trigger**: Sends WhatsApp and Email notifications

### Notification Format

**WhatsApp Message Example:**
```
🔔 *New Visitor Activity*

*Activity:* Booking
*Time:* 15 Jan 2025, 02:30 PM

*Visitor Details:*
📍 Location: Mumbai, Maharashtra, India
💻 Device: Mobile (Chrome on Android)
🌐 IP: 103.45.67.89

*Booking Details:*
📋 Booking ID: BK-20250115-1234
👤 Customer: John Doe
📧 Email: john@example.com
📱 Phone: +919876543210
🎯 Service: Tours
📍 Destination: Goa
💰 Amount: ₹25,000.00

*Page:* https://yoursite.com/package/123
```

**Email Notification:**
- HTML formatted email
- Same information as WhatsApp
- Professional layout with styling

## Admin Dashboard

### Access Visitor Tracking
1. Go to **Travel CRM → Visitors**
2. View statistics and recent activities
3. Filter by:
   - Activity Type (Page View, Search, Booking, Query, Package View)
   - Service (Tours, Forex, Visa, Hotels, Flights, Trains, Cars)
   - Date Range

### Statistics Displayed
- **Unique Visitors**: Number of unique visitors
- **Total Activities**: All tracked activities
- **Bookings**: Visitors who made bookings
- **Queries**: Visitors who submitted queries

## Database Structure

### Table: `wp_atc_visitor_tracking`

**Key Fields:**
- `session_id` - Unique session identifier
- `visitor_id` - Persistent visitor identifier (cookie-based)
- `ip_address` - Visitor IP address
- `device_type` - mobile, tablet, desktop
- `browser` - Chrome, Firefox, Safari, etc.
- `os` - Windows, macOS, Android, iOS, Linux
- `country`, `city`, `state` - Location data
- `activity_type` - Type of activity
- `activity_data` - JSON data with activity details
- `service` - Service involved (tours, forex, etc.)
- `package_id` - Package ID if applicable
- `booking_id` - Booking ID if applicable
- `query_id` - Query ID if applicable
- `search_query` - Search query text
- `page_views` - Number of page views
- `session_duration` - Session duration in seconds

## API Integration Details

### WhatsApp Business API (Meta)
- **Endpoint**: `https://graph.facebook.com/v18.0/{phone_id}/messages`
- **Authentication**: Bearer token
- **Required**: API Token, Phone ID

### Twilio WhatsApp API
- **Endpoint**: `https://api.twilio.com/2010-04-01/Accounts/{account_sid}/Messages.json`
- **Authentication**: Basic Auth (Account SID:Auth Token)
- **Required**: Account SID, Auth Token, From Number

### Custom Webhook
- **Method**: POST
- **Content-Type**: application/json
- **Payload**: `{phone, message, timestamp}`

## Customization

### Disable Notifications for Specific Activities
Edit `includes/tracking/class-atc-visitor-tracker.php` and modify the `send_visitor_notification()` method to filter activities.

### Custom Notification Format
Edit `format_notification_message()` method in `class-atc-visitor-tracker.php` to customize message format.

### Add Custom Activity Tracking
```php
// In your code, trigger the tracking:
do_action('atc_custom_activity', $activity_data);

// Then add handler in visitor tracker:
add_action('atc_custom_activity', [$this, 'track_custom_activity'], 10, 1);
```

## Privacy & Compliance

- IP addresses are stored for analytics
- Location data is approximate (city/state level)
- Visitor IDs are cookie-based (can be cleared by user)
- All data stored in WordPress database
- No third-party tracking services used (except IP geolocation API)

## Troubleshooting

### Notifications Not Sending

1. **Check Settings**:
   - Verify WhatsApp phone number is correct
   - Verify API credentials are correct
   - Check if notifications are enabled

2. **Check Logs**:
   - Enable WordPress debug mode
   - Check error logs for WhatsApp/Email errors

3. **Test WhatsApp API**:
   - Verify API token is valid
   - Check phone number format (+country code)
   - Test API connection manually

4. **Test Email**:
   - Verify SMTP settings if using custom SMTP
   - Check spam folder
   - Test with wp_mail() directly

### Tracking Not Working

1. **Check Database**:
   - Verify `wp_atc_visitor_tracking` table exists
   - Check if data is being inserted

2. **Check Hooks**:
   - Verify hooks are firing (add debug logs)
   - Check if visitor tracker is initialized

3. **Check Permissions**:
   - Ensure database write permissions
   - Check PHP error logs

## Performance Considerations

- Tracking runs asynchronously (doesn't block page load)
- IP geolocation API calls are cached
- Database queries are optimized with indexes
- Notifications are sent in background
- Screen resolution captured via JavaScript cookie

## Future Enhancements

Potential additions:
- Visitor journey mapping
- Heat maps
- Conversion tracking
- A/B testing integration
- Real-time visitor dashboard
- Export visitor data
- Visitor segmentation
- Lead scoring based on behavior

