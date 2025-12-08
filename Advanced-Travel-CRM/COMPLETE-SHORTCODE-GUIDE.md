# 📘 Complete Shortcode Guide - Advanced Travel CRM

## 🎯 Overview

This guide covers **all available shortcodes** in the Advanced Travel CRM plugin, organized by category with detailed usage instructions, parameters, and examples.

---

## 📋 Table of Contents

1. [Search & Booking Shortcodes](#1-search--booking-shortcodes)
2. [Customer Account Shortcodes](#2-customer-account-shortcodes)
3. [Package Management Shortcodes](#3-package-management-shortcodes)
4. [Advanced Features Shortcodes](#4-advanced-features-shortcodes)
5. [Quick Setup Guide](#5-quick-setup-guide)
6. [Best Practices](#6-best-practices)

---

## 1. Search & Booking Shortcodes

### 1.1 `[atc_premium_search]` ⭐ **RECOMMENDED**

**Purpose:** Premium search page with MakeMyTrip-like interface

**Parameters:**
- `service` (required) - Service type: `tours`, `hotels`, `flights`, `trains`, `safari`, `cars`, `forex`
- `title` (optional) - Hero title (default: "Find Your Perfect Travel Package")
- `subtitle` (optional) - Hero subtitle (default: "Search from thousands of verified packages")

**Usage:**
```php
[atc_premium_search service="tours" title="Find Your Perfect Tour" subtitle="Search from thousands of packages"]
```

**Example:**
```php
// Tours
[atc_premium_search service="tours" title="Discover Amazing Tours"]

// Hotels
[atc_premium_search service="hotels" title="Find Your Perfect Stay"]

// Flights
[atc_premium_search service="flights" title="Book Your Flight"]
```

**Features:**
- ✅ Premium UI with hero section
- ✅ Auto-shows results on same page
- ✅ Filters and sorting
- ✅ Grid/List view toggle
- ✅ Responsive design

**Where to Use:**
- Create a dedicated "Search" or "Book Now" page
- Add this shortcode as the only content on the page
- Results appear automatically below search form

---

### 1.2 `[atc_premium_results]`

**Purpose:** Display search results (usually automatic, but can be used separately)

**Parameters:**
- `service` (optional) - Service type
- `view` (optional) - Default view: `grid` or `list` (default: `grid`)
- `columns` (optional) - Grid columns (default: `3`)
- `per_page` (optional) - Results per page (default: `12`)

**Usage:**
```php
[atc_premium_results service="tours" view="grid" columns="3" per_page="12"]
```

**Note:** Usually **automatic** - included by `[atc_premium_search]`. Only use separately if you need a dedicated results page.

---

### 1.3 `[atc_search]` (Legacy)

**Purpose:** Basic search form (backward compatible)

**Parameters:**
- `service` (optional) - Service type (auto-detected if not provided)
- `title` (optional) - Form title

**Usage:**
```php
[atc_search service="tours" title="Search Tours"]
```

**Note:** Use `[atc_premium_search]` instead for better UI.

---

### 1.4 `[atc_search_widget]`

**Purpose:** Modern search widget with premium styling

**Parameters:**
- `service` (optional) - Service type
- `title` (optional) - Widget title
- `show_results` (optional) - Show results inline: `true` or `false` (default: `false`)

**Usage:**
```php
[atc_search_widget service="tours" title="Search Tours" show_results="true"]
```

---

### 1.5 `[atc_search_results]`

**Purpose:** Search results container

**Parameters:**
- `service` (optional) - Service type

**Usage:**
```php
[atc_search_results service="tours"]
```

---

### 1.6 `[atc_search_results_container]`

**Purpose:** Simple results container (no parameters)

**Usage:**
```php
[atc_search_results_container]
```

---

### 1.7 `[atc_booking_form]`

**Purpose:** Booking form for a specific service

**Parameters:**
- `service` (optional) - Service type (auto-detected if not provided)
- `title` (optional) - Form title
- `package_id` (optional) - Pre-select a package
- `require_login` (optional) - Require login: `true` or `false` (default: `false`)

**Usage:**
```php
[atc_booking_form service="tours" title="Book Your Tour" package_id="123" require_login="true"]
```

**Note:** Usually opened automatically from package details. Use directly if you need a standalone booking form.

---

### 1.8 `[atc_booking_button]`

**Purpose:** Button to trigger booking modal

**Parameters:**
- `service` (optional) - Service type (default: `tours`)
- `package_id` (optional) - Package ID to book
- `text` (optional) - Button text (default: "Book Now")
- `class` (optional) - CSS classes (default: `atc-btn atc-btn-gold`)

**Usage:**
```php
[atc_booking_button service="tours" package_id="123" text="Book This Package" class="atc-btn atc-btn-primary"]
```

**Example:**
```php
// Simple booking button
[atc_booking_button service="tours" text="Book Now"]

// With package ID
[atc_booking_button service="tours" package_id="123" text="Book This Package"]
```

---

## 2. Customer Account Shortcodes

### 2.1 `[atc_account_dashboard]` ⭐ **RECOMMENDED**

**Purpose:** Complete customer account dashboard

**Parameters:** None

**Usage:**
```php
[atc_account_dashboard]
```

**Features:**
- ✅ Dashboard overview with stats
- ✅ Booking history
- ✅ Profile management
- ✅ Password change
- ✅ Tabbed interface

**Where to Use:**
- Create a "My Account" or "Dashboard" page
- Add this shortcode as the only content
- Requires user to be logged in

**Page Setup:**
1. Create new page: "My Account"
2. Add shortcode: `[atc_account_dashboard]`
3. Set page as account page in settings (optional)

---

### 2.2 `[atc_register_form]`

**Purpose:** Customer registration form with OTP verification

**Parameters:** None

**Usage:**
```php
[atc_register_form]
```

**Features:**
- ✅ Premium UI design
- ✅ Name, Phone, Email, Password fields
- ✅ Email verification via OTP
- ✅ Phone number validation
- ✅ Password strength indicator

**Where to Use:**
- Create a "Register" or "Sign Up" page
- Add this shortcode
- Users will be redirected to email verification after registration

**Page Setup:**
1. Create new page: "Register"
2. Add shortcode: `[atc_register_form]`
3. Link from login page or header menu

---

### 2.3 `[atc_login_form]`

**Purpose:** Customer login form

**Parameters:** None

**Usage:**
```php
[atc_login_form]
```

**Features:**
- ✅ Premium UI design
- ✅ Email or Phone number login (single field)
- ✅ Password field
- ✅ "Remember me" checkbox
- ✅ "Forgot password" link
- ✅ "Don't have an account?" link

**Where to Use:**
- Create a "Login" page
- Add this shortcode
- Users can login with email OR phone number

**Page Setup:**
1. Create new page: "Login"
2. Add shortcode: `[atc_login_form]`
3. Link from header menu or redirect unauthorized users here

---

### 2.4 `[atc_verify_email_form]`

**Purpose:** OTP email verification form

**Parameters:** None

**Usage:**
```php
[atc_verify_email_form]
```

**Features:**
- ✅ Premium UI design
- ✅ 6-digit OTP input
- ✅ Auto-submit when 6 digits entered
- ✅ Resend OTP button
- ✅ Auto-focus on input

**Where to Use:**
- Create a "Verify Email" page
- Add this shortcode
- Users redirected here after registration
- Auto-login after successful verification

**Page Setup:**
1. Create new page: "Verify Email" (slug: `verify-email`)
2. Add shortcode: `[atc_verify_email_form]`
3. Users will be redirected here automatically after registration

---

### 2.5 `[atc_forgot_password_form]` ⭐ **NEW**

**Purpose:** Forgot password form with OTP-based password reset

**Parameters:** None

**Usage:**
```php
[atc_forgot_password_form]
```

**Features:**
- ✅ Premium UI design
- ✅ Email input field
- ✅ OTP verification (automatic after email submission)
- ✅ Password reset form (automatic after OTP verification)
- ✅ Resend OTP functionality
- ✅ Complete password reset flow
- ✅ Auto-submit OTP when 6 digits entered
- ✅ Security: Doesn't reveal if email exists

**Where to Use:**
- Create a "Forgot Password" page
- Add this shortcode
- Users can access from login page "Forgot password?" link

**Page Setup:**
1. Create new page: "Forgot Password" (slug: `forgot-password`)
2. Add shortcode: `[atc_forgot_password_form]`
3. Link from login page: "Forgot password?" link

**Flow:**
1. User enters email → OTP sent
2. User enters OTP → OTP verified
3. User enters new password → Password reset
4. Redirect to login page with success message

**Security Features:**
- ✅ OTP expires in 10 minutes
- ✅ Max 5 OTP attempts
- ✅ Rate limiting (3 OTPs per hour)
- ✅ OTP verification required before password reset
- ✅ Doesn't reveal if email exists (security)

---

### 2.6 `[atc_profile_form]`

**Purpose:** Profile editing form

**Parameters:** None

**Usage:**
```php
[atc_profile_form]
```

**Features:**
- ✅ Edit name and phone number
- ✅ Email display (read-only)
- ✅ Premium UI design
- ✅ Success/error messages

**Note:** Usually included in `[atc_account_dashboard]` under "Profile" tab. Use separately if needed.

---

### 2.7 `[atc_password_form]`

**Purpose:** Password change form

**Parameters:** None

**Usage:**
```php
[atc_password_form]
```

**Features:**
- ✅ Current password field
- ✅ New password field
- ✅ Confirm password field
- ✅ Password strength indicator
- ✅ Premium UI design

**Note:** Usually included in `[atc_account_dashboard]` under "Password" tab. Use separately if needed.

---

### 2.8 `[atc_logout_button]`

**Purpose:** Logout button

**Parameters:**
- `text` (optional) - Button text (default: "Logout")
- `class` (optional) - CSS classes (default: `atc-btn atc-btn-text`)
- `redirect` (optional) - Redirect URL after logout (default: home URL)

**Usage:**
```php
[atc_logout_button text="Logout" redirect="/"]
```

**Example:**
```php
// Simple logout button
[atc_logout_button]

// Custom text and redirect
[atc_logout_button text="Sign Out" redirect="/login/"]
```

---

### 2.9 `[atc_account_menu]`

**Purpose:** Account navigation menu

**Parameters:** None

**Usage:**
```php
[atc_account_menu]
```

**Features:**
- ✅ Menu with dashboard, profile, bookings links
- ✅ Logout button
- ✅ Responsive design

**Where to Use:**
- Add to sidebar or header
- Shows account navigation for logged-in users

---

### 2.10 `[atc_dashboard_advanced]`

**Purpose:** Advanced dashboard with enhanced features

**Parameters:** None

**Usage:**
```php
[atc_dashboard_advanced]
```

**Features:**
- ✅ Enhanced UI with stats cards
- ✅ Booking history with filters
- ✅ Customer tier display
- ✅ Quick actions

**Note:** Similar to `[atc_account_dashboard]` but with more advanced features.

---

### 2.11 `[atc_booking_history]`

**Purpose:** Booking history display

**Parameters:** None

**Usage:**
```php
[atc_booking_history]
```

**Note:** Usually included in dashboard. Use separately if needed.

---

### 2.12 `[atc_booking_details]`

**Purpose:** Booking details display

**Parameters:** None

**Usage:**
```php
[atc_booking_details]
```

**Note:** Usually opened in modal from booking history. Use separately if needed.

---

## 3. Package Management Shortcodes

### 3.1 `[atc_package_details]` ⭐ **RECOMMENDED**

**Purpose:** Display full package details

**Parameters:**
- `package_id` (optional) - Package ID (will use URL parameter if not provided)

**Usage:**
```php
[atc_package_details package_id="123"]
```

**Features:**
- ✅ Full package information
- ✅ Image gallery
- ✅ Pricing display
- ✅ Features, inclusions, exclusions
- ✅ Itinerary
- ✅ Terms & conditions
- ✅ "Book Now" button
- ✅ "Ask for Custom Package" button

**Where to Use:**
- Create a "Package Details" page
- Add this shortcode
- Package ID can come from URL: `?package_id=123`
- Or specify in shortcode: `[atc_package_details package_id="123"]`

**Page Setup:**
1. Create new page: "Package Details"
2. Add shortcode: `[atc_package_details]`
3. Users redirected here when clicking "View Details" from search results

**URL Format:**
```
https://yoursite.com/package-details/?package_id=123
```

---

### 3.2 `[atc_query_form]`

**Purpose:** Custom package request form

**Parameters:**
- `service` (optional) - Service type
- `package_id` (optional) - Package ID (if requesting modification)
- `title` (optional) - Form title (default: "Request Custom Package")
- `subtitle` (optional) - Form subtitle
- `button_text` (optional) - Button text (default: "Request Custom Package")
- `button_style` (optional) - Button style: `premium`, `outline`, `minimal` (default: `premium`)
- `show_button` (optional) - Show button or form: `true` or `false` (default: `true`)

**Usage:**
```php
// Show as button (opens modal)
[atc_query_form service="tours" button_text="Request Custom Package" button_style="premium"]

// Show form directly
[atc_query_form service="tours" show_button="false" title="Request Custom Package"]
```

**Features:**
- ✅ Premium modal form
- ✅ Personal information fields
- ✅ Travel details
- ✅ Budget range
- ✅ Preferences
- ✅ File upload (optional)

**Where to Use:**
- Add to package details page
- Add to search results
- Add to any page where users might want custom packages

---

## 4. Advanced Features Shortcodes

### 4.1 `[atc_custom_request]`

**Purpose:** Custom package request form (alternative to query form)

**Parameters:** None

**Usage:**
```php
[atc_custom_request]
```

**Features:**
- ✅ Destination input
- ✅ Travel dates
- ✅ Travelers (adults/children)
- ✅ Budget range
- ✅ Preferences textarea
- ✅ Contact details

**Note:** Similar to `[atc_query_form]` but simpler interface.

---

## 5. Quick Setup Guide

### Recommended Page Structure

#### **Option 1: Minimal Setup (Recommended)**

**Page 1: Search & Book**
- **Page Title:** "Search Tours" or "Book Now"
- **URL:** `/search-tours/`
- **Content:**
```php
[atc_premium_search service="tours" title="Find Your Perfect Tour"]
```

**Page 2: Package Details**
- **Page Title:** "Package Details"
- **URL:** `/package-details/`
- **Content:**
```php
[atc_package_details]
```

**Page 3: My Account**
- **Page Title:** "My Account"
- **URL:** `/my-account/`
- **Content:**
```php
[atc_account_dashboard]
```

**Page 4: Login**
- **Page Title:** "Login"
- **URL:** `/login/`
- **Content:**
```php
[atc_login_form]
```

**Page 5: Register**
- **Page Title:** "Register"
- **URL:** `/register/`
- **Content:**
```php
[atc_register_form]
```

**Page 6: Verify Email**
- **Page Title:** "Verify Email"
- **URL:** `/verify-email/`
- **Content:**
```php
[atc_verify_email_form]
```

**Page 7: Forgot Password**
- **Page Title:** "Forgot Password"
- **URL:** `/forgot-password/`
- **Content:**
```php
[atc_forgot_password_form]
```

---

#### **Option 2: Complete Setup**

Add these additional pages:

**Page 8: Booking History**
- **Page Title:** "My Bookings"
- **URL:** `/my-bookings/`
- **Content:**
```php
[atc_booking_history]
```

---

### Service Types

Use different `service` parameters for different services:

- `service="tours"` - Tours
- `service="hotels"` - Hotels
- `service="flights"` - Flights
- `service="trains"` - Trains
- `service="safari"` - Safari
- `service="cars"` - Car Rentals
- `service="forex"` - Forex

---

## 6. Best Practices

### ✅ Do's

1. **Use Premium Shortcodes**
   - Use `[atc_premium_search]` instead of `[atc_search]`
   - Use `[atc_account_dashboard]` for complete account management

2. **Create Dedicated Pages**
   - One shortcode per page (for main features)
   - Clean, focused pages work better

3. **Set Proper Page Slugs**
   - Use friendly URLs: `/search-tours/`, `/my-account/`, `/package-details/`
   - Avoid special characters in URLs

4. **Test Each Shortcode**
   - Test on a staging site first
   - Verify all functionality works
   - Check mobile responsiveness

5. **Use Consistent Service Parameters**
   - Use the same `service` parameter across related shortcodes
   - Example: If search page uses `service="tours"`, use same for booking forms

### ❌ Don'ts

1. **Don't Mix Legacy and Premium**
   - Don't use `[atc_search]` and `[atc_premium_search]` on same page
   - Stick to one style

2. **Don't Nest Shortcodes Unnecessarily**
   - Most shortcodes work standalone
   - Only nest when specifically documented

3. **Don't Override Required Parameters**
   - Always provide `service` parameter for search/booking shortcodes
   - Don't skip required fields

4. **Don't Use on Admin Pages**
   - Shortcodes are for frontend only
   - Won't work in WordPress admin

---

## 7. Common Use Cases

### Use Case 1: Simple Tour Booking Site

**Pages Needed:**
1. Homepage with search: `[atc_premium_search service="tours"]`
2. Package details: `[atc_package_details]`
3. My account: `[atc_account_dashboard]`
4. Login: `[atc_login_form]`
5. Register: `[atc_register_form]`

---

### Use Case 2: Multi-Service Travel Agency

**Pages Needed:**
1. Tours search: `[atc_premium_search service="tours"]`
2. Hotels search: `[atc_premium_search service="hotels"]`
3. Flights search: `[atc_premium_search service="flights"]`
4. Package details: `[atc_package_details]` (works for all services)
5. My account: `[atc_account_dashboard]`
6. Login/Register pages

---

### Use Case 3: Custom Package Focused Site

**Pages Needed:**
1. Search page: `[atc_premium_search service="tours"]`
2. Package details with query form: 
   ```php
   [atc_package_details]
   [atc_query_form service="tours" button_text="Request Custom Package"]
   ```
3. My account: `[atc_account_dashboard]`

---

## 8. Troubleshooting

### Issue: Shortcode not displaying

**Solution:**
- Check if shortcode is spelled correctly
- Verify plugin is activated
- Clear cache (browser and WordPress)
- Check for JavaScript errors in console

---

### Issue: Results not showing

**Solution:**
- Verify `service` parameter is correct
- Check if service is enabled in admin
- Verify REST API is working
- Check browser console for errors

---

### Issue: Login/Register not working

**Solution:**
- Verify pages are created with correct slugs
- Check if email verification is enabled
- Verify SMTP settings for OTP emails
- Check error logs

---

### Issue: Booking form not submitting

**Solution:**
- Verify user is logged in (if required)
- Check if service is active
- Verify REST API endpoints
- Check browser console for errors

---

## 9. Shortcode Reference Table

| Shortcode | Category | Required | Premium UI | Auto Features |
|-----------|----------|----------|------------|---------------|
| `[atc_premium_search]` | Search | ✅ | ✅ | ✅ |
| `[atc_premium_results]` | Search | ❌ | ✅ | ✅ |
| `[atc_search]` | Search | ❌ | ❌ | ❌ |
| `[atc_search_widget]` | Search | ❌ | ✅ | ❌ |
| `[atc_booking_form]` | Booking | ❌ | ❌ | ❌ |
| `[atc_booking_button]` | Booking | ❌ | ✅ | ✅ |
| `[atc_package_details]` | Package | ❌ | ✅ | ✅ |
| `[atc_query_form]` | Package | ❌ | ✅ | ✅ |
| `[atc_account_dashboard]` | Account | ❌ | ✅ | ✅ |
| `[atc_register_form]` | Account | ❌ | ✅ | ✅ |
| `[atc_login_form]` | Account | ❌ | ✅ | ✅ |
| `[atc_verify_email_form]` | Account | ❌ | ✅ | ✅ |
| `[atc_forgot_password_form]` | Account | ❌ | ✅ | ✅ |
| `[atc_profile_form]` | Account | ❌ | ✅ | ❌ |
| `[atc_password_form]` | Account | ❌ | ✅ | ❌ |
| `[atc_logout_button]` | Account | ❌ | ✅ | ❌ |
| `[atc_account_menu]` | Account | ❌ | ✅ | ❌ |
| `[atc_custom_request]` | Advanced | ❌ | ✅ | ❌ |

---

## 10. Support & Documentation

### Additional Resources

- **Plugin Documentation:** Check plugin readme
- **Admin Settings:** WordPress Admin → Travel CRM → Settings
- **Feature Manager:** WordPress Admin → Travel CRM → Features
- **Service Manager:** WordPress Admin → Travel CRM → Services

### Getting Help

1. Check this guide first
2. Review plugin documentation
3. Check WordPress error logs
4. Contact plugin support

---

## ✅ Summary

### Essential Shortcodes (Must Have)

1. `[atc_premium_search]` - Search functionality
2. `[atc_package_details]` - Package details
3. `[atc_account_dashboard]` - Customer account
4. `[atc_login_form]` - Login
5. `[atc_register_form]` - Registration
6. `[atc_verify_email_form]` - Email verification
7. `[atc_forgot_password_form]` - Password reset (forgot password)

### Recommended Pages

1. Search page (homepage or dedicated)
2. Package details page
3. My account page
4. Login page
5. Register page
6. Verify email page
7. Forgot password page

---

**Last Updated:** Current
**Plugin Version:** 2.3.0
**Guide Version:** 1.0

---

## 🎉 You're All Set!

With these shortcodes, you can build a complete travel booking website. Start with the essential shortcodes and add more as needed.

**Happy Building!** 🚀

