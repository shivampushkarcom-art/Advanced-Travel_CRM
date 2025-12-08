# 📱 WhatsApp Notification System Guide

## Overview

Your Travel CRM now uses **WhatsApp as the PRIMARY notification system** and Email as a secondary fallback. All customer notifications are automatically sent via WhatsApp Business API when bookings are created or status changes occur.

---

## ✅ Automatic WhatsApp Notifications

### When Bookings Are Created:
- ✅ **Customer receives WhatsApp** with booking confirmation
- ✅ **Admin receives WhatsApp** notification about new booking
- ✅ Email sent only if WhatsApp fails

### When Booking Status Changes:
- ✅ **Customer receives WhatsApp** when status changes to:
  - `confirmed` - Booking confirmed
  - `pending` - Booking pending
  - `cancelled` - Booking cancelled
  - `completed` - Trip completed
  - `refunded` - Refund processed
- ✅ **Admin receives WhatsApp** notification about status change
- ✅ Email sent only if WhatsApp fails

### Other Automatic Notifications:
- ✅ New Query submitted → Admin WhatsApp
- ✅ Payment received → Customer & Admin WhatsApp
- ✅ Booking cancelled → Customer & Admin WhatsApp

---

## 🔧 WhatsApp API Configuration

### Step 1: Configure WhatsApp API in Settings

Go to **Travel CRM → Settings → WhatsApp Notifications**

1. **WhatsApp Admin Phone**: Your admin phone number (e.g., +919876543210)
2. **WhatsApp API Type**: Choose one:
   - **WhatsApp Business API** (Meta) - Recommended
   - **Twilio WhatsApp API**
   - **Custom Webhook**

3. **For WhatsApp Business API (Meta)**:
   - **WhatsApp API Token**: Your access token from Meta
   - **WhatsApp Phone ID**: Your phone number ID from Meta

4. **For Twilio**:
   - **Twilio Account SID**: Your Twilio account SID
   - **Twilio WhatsApp From**: Your Twilio WhatsApp number

5. **For Custom Webhook**:
   - **Webhook URL**: Your custom webhook endpoint

### Step 2: Enable WhatsApp OTP

Go to **Travel CRM → Settings → WhatsApp OTP Verification**

- ✅ Enable WhatsApp OTP for registration and password reset

---

## 📝 WhatsApp Message Templates

### Customizing Templates

WhatsApp messages use templates with variables that are automatically replaced:

#### Available Variables:
- `{customer_name}` - Customer's name
- `{customer_phone}` - Customer's phone number
- `{customer_email}` - Customer's email
- `{booking_id}` - Booking ID
- `{service_name}` - Service name (Tours, Hotels, etc.)
- `{destination}` - Destination
- `{travel_date}` - Travel date
- `{return_date}` - Return date
- `{price_total}` - Total price
- `{currency}` - Currency (INR, USD, etc.)
- `{adults}` - Number of adults
- `{children}` - Number of children
- `{company_name}` - Your company name
- `{support_phone}` - Support phone number
- `{support_email}` - Support email
- `{status}` - Booking status
- `{old_status}` - Previous booking status
- `{new_status}` - New booking status

### Default Templates

The system includes default templates for:

1. **Booking Confirmation** (`customer_booking_confirmation`)
2. **Booking Confirmed** (`customer_booking_confirmed`)
3. **Booking Pending** (`customer_booking_pending`)
4. **Booking Cancelled** (`customer_booking_cancelled`)
5. **Booking Completed** (`customer_booking_completed`)
6. **Booking Refunded** (`customer_booking_refunded`)
7. **Status Changed** (`customer_booking_status_changed`)
8. **Payment Receipt** (`customer_payment_receipt`)
9. **Query Confirmation** (`customer_query_confirmation`)

### Customizing Templates via Database

Templates are stored in the `wp_atc_email_templates` table. You can customize them by:

1. Adding a `whatsapp_body` column to the table (if not exists)
2. Updating the template with your custom message

**Example SQL:**
```sql
UPDATE wp_atc_email_templates 
SET whatsapp_body = '🎉 *Booking Confirmed!*\n\nDear {customer_name},\n\nYour booking #{booking_id} is confirmed!\n\nAmount: {currency} {price_total}\n\n{company_name}'
WHERE slug = 'customer_booking_confirmation';
```

---

## 🎯 How It Works

### Primary → Secondary Flow:

1. **System tries WhatsApp first** (PRIMARY)
   - Sends via WhatsApp Business API
   - Logs success/failure

2. **If WhatsApp fails, sends Email** (SECONDARY)
   - Only if WhatsApp sending failed
   - Ensures customer always gets notified

### Notification Priority:

```
WhatsApp (PRIMARY)
    ↓ (if fails)
Email (SECONDARY)
```

---

## 📊 Notification History

View all notifications in:
**Travel CRM → Notifications**

You can see:
- ✅ Channel (WhatsApp/Email)
- ✅ Status (Sent/Failed)
- ✅ Recipient
- ✅ Timestamp
- ✅ Resend option

---

## 🔍 Troubleshooting

### WhatsApp Not Sending?

1. **Check API Configuration**:
   - Verify API token is correct
   - Verify phone ID is correct
   - Check API type matches your setup

2. **Check Phone Numbers**:
   - Customer phone must be in international format (+919876543210)
   - Admin phone must be configured in settings

3. **Check Logs**:
   - Go to **Travel CRM → Notifications**
   - Look for failed notifications
   - Check error messages

4. **Test WhatsApp API**:
   - Use the test notification feature
   - Verify API credentials are working

### Email Still Sending?

- Email is sent as **fallback only** when WhatsApp fails
- This ensures customers always receive notifications
- Check notification history to see which channel was used

---

## 🚀 Best Practices

1. **Always configure WhatsApp API** before going live
2. **Test notifications** after configuration
3. **Monitor notification history** regularly
4. **Customize templates** to match your brand voice
5. **Keep phone numbers updated** in customer profiles

---

## 📞 Support

If you need help:
- Check notification history for error messages
- Verify API credentials are correct
- Test with a simple notification first
- Contact your WhatsApp API provider for API issues

---

## ✅ Summary

- ✅ WhatsApp is PRIMARY notification system
- ✅ Email is SECONDARY (fallback only)
- ✅ Automatic notifications for bookings & status changes
- ✅ Customizable templates with variables
- ✅ Full notification history & logging
- ✅ No manual "Send Email" button (removed)

Your system is now fully automated with WhatsApp as the primary communication channel! 🎉

