# 🚀 Quick Website Building Guide
## Advanced Travel CRM - Build Your Travel Website in 30 Minutes

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Step 1: Installation & Setup](#step-1-installation--setup)
3. [Step 2: Create Essential Pages](#step-2-create-essential-pages)
4. [Step 3: Add Packages](#step-3-add-packages)
5. [Step 4: Create Homepage](#step-4-create-homepage)
6. [Step 5: Create Service Pages](#step-5-create-service-pages)
7. [Step 6: Setup Menus](#step-6-setup-menus)
8. [Step 7: Configure Settings](#step-7-configure-settings)
9. [Quick Reference: Shortcodes](#quick-reference-shortcodes)
10. [Troubleshooting](#troubleshooting)

---

## ✅ Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Advanced Travel CRM plugin installed and activated
- A theme installed (any modern WordPress theme works)

---

## 🔧 Step 1: Installation & Setup

### 1.1 Install Plugin
1. Upload plugin to `/wp-content/plugins/`
2. Activate plugin from WordPress Admin → Plugins
3. Go to **Travel CRM → Dashboard** to verify installation

### 1.2 Run Database Migration
1. Go to **Travel CRM → Dashboard**
2. Click **"Run Migrations"** if prompted
3. Wait for migration to complete

### 1.3 Verify Services
1. Go to **Travel CRM → Services**
2. Verify all services are active:
   - ✅ Tours
   - ✅ Hotels
   - ✅ Flights
   - ✅ Trains
   - ✅ Cars
   - ✅ Forex
   - ✅ Visa

---

## 📄 Step 2: Create Essential Pages

### 2.1 Package Details Page (Required)

**Purpose:** Single page that displays package details for all packages

1. Go to **WordPress Admin → Pages → Add New**
2. **Title:** `Package Details`
3. **Slug:** `package-details` (important!)
4. **Content:**
   ```
   [atc_package_details]
   ```
5. **Publish**

✅ **This ONE page works for ALL packages automatically!**

**How it works:**
- Package #1: `/package-details/?package_id=1` → Shows Package #1
- Package #2: `/package-details/?package_id=2` → Shows Package #2
- Works for all packages from all services!

---

### 2.2 Customer Account Pages (Recommended)

#### My Account Page
1. **Title:** `My Account`
2. **Slug:** `my-account`
3. **Content:**
   ```
   [atc_account_dashboard]
   ```
4. **Publish**

#### Login Page
1. **Title:** `Login`
2. **Slug:** `login`
3. **Content:**
   ```
   [atc_login_form]
   ```
4. **Publish**

#### Register Page
1. **Title:** `Register`
2. **Slug:** `register`
3. **Content:**
   ```
   [atc_register_form]
   ```
4. **Publish**

#### Verify Email Page
1. **Title:** `Verify Email`
2. **Slug:** `verify-email`
3. **Content:**
   ```
   [atc_verify_email_form]
   ```
4. **Publish**

#### Forgot Password Page
1. **Title:** `Forgot Password`
2. **Slug:** `forgot-password`
3. **Content:**
   ```
   [atc_forgot_password_form]
   ```
4. **Publish**

---

## 📦 Step 3: Add Packages

### 3.1 Create Your First Package

1. Go to **Travel CRM → Custom Packages → Add New**
2. Fill in package details:
   - **Package Name:** e.g., "Goa Beach Tour"
   - **Service:** Select service (Tours, Hotels, etc.)
   - **Destination:** e.g., "Goa, India"
   - **Price:** e.g., "15000"
   - **Original Price:** e.g., "18000" (for discount display)
   - **Duration Days:** e.g., "3"
   - **Category:** e.g., "Beach", "Honeymoon", "Family"
   - **Image:** Upload main image (recommended: 1920x1080px)
   - **Description:** Full package description
   - **Status:** Active
   - **Featured:** Check if you want to feature this package
3. Click **"Add Package"**

### 3.2 Package ID System

- **Package ID:** Automatically generated as numeric ID (1, 2, 3, etc.)
- **Visible in Admin:** Package list shows both ID and Package ID
- **Migration:** Old packages automatically get numeric Package IDs

### 3.3 Package Categories

**Tours Categories:**
- Honeymoon, Adventure, Family, Luxury, Budget, Beach, Hill Station, Wildlife, Cultural, Spiritual, Weekend Getaway, International, Domestic, Jungle Safari

**Hotels Categories:**
- Budget, Luxury, Business, Resort, Boutique

**Other Services:**
- Create custom categories as needed

---

## 🏠 Step 4: Create Homepage

### 4.1 Homepage Structure

1. Go to **WordPress Admin → Pages → Add New**
2. **Title:** `Home` or your company name
3. **Content:** Add the following sections:

#### Section 1: Hero Slider
```
[atc_hero_slider id="homepage"]
```

**Setup Hero Slider:**
1. Go to **Travel CRM → Hero Slider**
2. Create/select "homepage" slider
3. Add slides with images, titles, descriptions, and links
4. Configure: Autoplay, interval, height (optional)

#### Section 2: Services Grid
```
[atc_service_grid title="Our Services" description="Explore our wide range of travel services"]
```

#### Section 3: Featured Packages
```
[atc_featured_packages service="tours" columns="3" per_page="6"]
```

#### Section 4: Why Choose Us (Optional)
Add your custom content about your company

#### Section 5: Call-to-Action
```
[atc_query_form service="tours" title="Have Questions?" subtitle="Contact us for more information"]
```

### 4.2 Set as Homepage

1. Go to **Settings → Reading**
2. **Homepage displays:** Select "A static page"
3. **Homepage:** Select your Home page
4. **Posts page:** Select a blog page (or create one)
5. **Save Changes**

---

## 🎯 Step 5: Create Service Pages

### 5.1 Create Service Landing Pages

For each service (Tours, Hotels, Flights, etc.):

#### Tours Page
1. **Title:** `Tours`
2. **Slug:** `tours`
3. **Content:**
   ```
   [atc_premium_search service="tours" title="Find Your Perfect Tour" subtitle="Search from thousands of packages"]
   ```

#### Hotels Page
1. **Title:** `Hotels`
2. **Slug:** `hotels`
3. **Content:**
   ```
   [atc_premium_search service="hotels" title="Find Your Perfect Stay" subtitle="Best hotels at best prices"]
   ```

#### Flights Page
1. **Title:** `Flights`
2. **Slug:** `flights`
3. **Content:**
   ```
   [atc_premium_search service="flights" title="Book Your Flight" subtitle="Compare prices and book instantly"]
   ```

#### Forex Page
1. **Title:** `Forex`
2. **Slug:** `forex`
3. **Content:**
   ```
   [atc_premium_search service="forex" title="Currency Exchange" subtitle="Get the best exchange rates"]
   ```

#### Visa Page
1. **Title:** `Visa`
2. **Slug:** `visa`
3. **Content:**
   ```
   [atc_premium_search service="visa" title="Visa Services" subtitle="Apply for your visa online"]
   ```

#### Trains Page
1. **Title:** `Trains`
2. **Slug:** `trains`
3. **Content:**
   ```
   [atc_premium_search service="trains" title="Book Train Tickets" subtitle="Book your train journey"]
   ```

#### Cars Page
1. **Title:** `Car Rentals`
2. **Slug:** `cars`
3. **Content:**
   ```
   [atc_premium_search service="cars" title="Rent a Car" subtitle="Choose from our fleet of vehicles"]
   ```

### 5.2 Service Page Features

Each service page includes:
- ✅ Premium search form
- ✅ Automatic results display
- ✅ Filters and sorting
- ✅ Grid/List view toggle
- ✅ Package cards with "View Details" and "Book Now" buttons
- ✅ Responsive design

---

## 🎨 Step 6: Setup Menus

### 6.1 Create Main Navigation Menu

1. Go to **Appearance → Menus**
2. Create new menu: "Main Menu"
3. Add pages:
   - Home
   - Tours
   - Hotels
   - Flights
   - Trains
   - Cars
   - Forex
   - Visa
   - My Account (if logged in)
   - Login (if not logged in)
4. Set menu location: **Primary Menu**
5. **Save Menu**

### 6.2 Footer Menu (Optional)

1. Create new menu: "Footer Menu"
2. Add pages:
   - About Us
   - Contact
   - Privacy Policy
   - Terms & Conditions
3. Set menu location: **Footer Menu**
4. **Save Menu**

---

## ⚙️ Step 7: Configure Settings

### 7.1 Plugin Settings

1. Go to **Travel CRM → Settings**
2. Configure:
   - **Company Name:** Your company name
   - **Company Email:** Your email address
   - **Currency Symbol:** ₹, $, €, etc.
   - **Currency Code:** INR, USD, EUR, etc.
   - **Phone Number:** Your phone number
   - **Address:** Your business address

### 7.2 Payment Settings

1. Go to **Travel CRM → Settings → Payments**
2. Configure payment methods:
   - Stripe
   - PayPal
   - Razorpay
   - Cash on Delivery
   - Bank Transfer

### 7.3 Email Settings

1. Go to **Travel CRM → Settings → Email**
2. Configure email templates:
   - Booking confirmation
   - Payment confirmation
   - Query notifications
   - Password reset

### 7.4 Notification Settings

1. Go to **Travel CRM → Settings → Notifications**
2. Enable notifications:
   - ✅ Email notifications
   - ✅ WhatsApp notifications (if configured)
   - ✅ SMS notifications (if configured)

---

## 📚 Quick Reference: Shortcodes

### Search & Booking

| Shortcode | Purpose | Example |
|-----------|---------|---------|
| `[atc_premium_search]` | Premium search page | `[atc_premium_search service="tours"]` |
| `[atc_package_details]` | Package details page | `[atc_package_details]` |
| `[atc_featured_packages]` | Featured packages | `[atc_featured_packages service="tours"]` |
| `[atc_packages_by_category]` | Packages by category | `[atc_packages_by_category category="Honeymoon" service="tours"]` |

### Hero Slider

| Shortcode | Purpose | Example |
|-----------|---------|---------|
| `[atc_hero_slider]` | Hero slider | `[atc_hero_slider id="homepage"]` |

### Services

| Shortcode | Purpose | Example |
|-----------|---------|---------|
| `[atc_service_grid]` | Services grid | `[atc_service_grid title="Our Services"]` |

### Customer Account

| Shortcode | Purpose | Example |
|-----------|---------|---------|
| `[atc_account_dashboard]` | Customer dashboard | `[atc_account_dashboard]` |
| `[atc_login_form]` | Login form | `[atc_login_form]` |
| `[atc_register_form]` | Registration form | `[atc_register_form]` |
| `[atc_verify_email_form]` | Email verification | `[atc_verify_email_form]` |
| `[atc_forgot_password_form]` | Password reset | `[atc_forgot_password_form]` |

### Query Forms

| Shortcode | Purpose | Example |
|-----------|---------|---------|
| `[atc_query_form]` | Query form | `[atc_query_form service="tours"]` |

---

## 🎯 Complete Website Structure

### Recommended Page Structure:

```
Homepage
├── Hero Slider
├── Services Grid
├── Featured Packages
├── Why Choose Us
└── Call-to-Action

Service Pages (Tours, Hotels, Flights, etc.)
├── Premium Search
└── Results (automatic)

Package Details Page
└── Package Details (works for all packages)

Customer Account Pages
├── My Account
├── Login
├── Register
├── Verify Email
└── Forgot Password

Other Pages
├── About Us
├── Contact
├── Privacy Policy
└── Terms & Conditions
```

---

## 🔍 Step-by-Step Checklist

### ✅ Phase 1: Setup (5 minutes)
- [ ] Install and activate plugin
- [ ] Run database migrations
- [ ] Verify services are active
- [ ] Configure basic settings

### ✅ Phase 2: Essential Pages (10 minutes)
- [ ] Create Package Details page
- [ ] Create My Account page
- [ ] Create Login page
- [ ] Create Register page
- [ ] Create Verify Email page
- [ ] Create Forgot Password page

### ✅ Phase 3: Content (10 minutes)
- [ ] Add at least 5 packages
- [ ] Add package images
- [ ] Set featured packages
- [ ] Create hero slider slides

### ✅ Phase 4: Service Pages (5 minutes)
- [ ] Create Tours page
- [ ] Create Hotels page
- [ ] Create Flights page
- [ ] Create other service pages

### ✅ Phase 5: Homepage (5 minutes)
- [ ] Create Homepage
- [ ] Add hero slider
- [ ] Add services grid
- [ ] Add featured packages
- [ ] Set as homepage

### ✅ Phase 6: Menus & Settings (5 minutes)
- [ ] Create main navigation menu
- [ ] Configure plugin settings
- [ ] Configure payment settings
- [ ] Configure email settings

---

## 🚀 Quick Start Templates

### Homepage Template

```
[atc_hero_slider id="homepage"]

[atc_service_grid title="Our Services" description="Explore our wide range of travel services"]

[atc_featured_packages service="tours" columns="3" per_page="6" title="Featured Tours"]

[atc_featured_packages service="hotels" columns="3" per_page="6" title="Featured Hotels"]

[atc_query_form service="tours" title="Have Questions?" subtitle="Contact us for more information"]
```

### Service Page Template

```
[atc_premium_search service="tours" title="Find Your Perfect Tour" subtitle="Search from thousands of packages"]
```

### Package Details Page Template

```
[atc_package_details]
```

---

## 🎨 Customization Tips

### 1. Branding
- Update company name in **Travel CRM → Settings**
- Upload logo in **Travel CRM → Settings → Branding**
- Customize colors in theme settings

### 2. Hero Slider
- Create multiple sliders for different pages
- Use high-quality images (1920x1080px recommended)
- Add compelling titles and descriptions
- Link slides to relevant pages

### 3. Packages
- Use consistent image sizes (1920x1080px for desktop)
- Write compelling descriptions
- Add multiple images to gallery
- Set appropriate categories
- Mark popular packages as "Featured"

### 4. Service Pages
- Customize titles and subtitles for each service
- Use service-specific colors and branding
- Add service-specific content above search form

---

## 🔧 Troubleshooting

### Issue: Packages not showing
**Solution:**
1. Check package status is "Active"
2. Verify package has an image
3. Check service is active in **Travel CRM → Services**
4. Clear cache if using caching plugin

### Issue: Package details page not working
**Solution:**
1. Verify page slug is `package-details`
2. Check shortcode is `[atc_package_details]`
3. Verify package ID is numeric (1, 2, 3, etc.)
4. Run migration if package IDs are not numeric

### Issue: Search not working
**Solution:**
1. Verify shortcode is correct: `[atc_premium_search service="tours"]`
2. Check service name is correct (tours, hotels, flights, etc.)
3. Verify packages exist for that service
4. Check browser console for JavaScript errors

### Issue: Images not loading
**Solution:**
1. Verify image URLs are correct
2. Check image file permissions
3. Verify images are uploaded to media library
4. Clear browser cache

### Issue: Booking not working
**Solution:**
1. Verify payment settings are configured
2. Check booking form is properly configured
3. Verify customer account pages are created
4. Check email settings for notifications

---

## 📞 Support & Resources

### Documentation
- **Complete Website Building Guide:** `COMPLETE-WEBSITE-BUILDING-GUIDE.md`
- **Shortcode Guide:** `COMPLETE-SHORTCODE-GUIDE.md`
- **Multiple Packages Guide:** `MULTIPLE-PACKAGES-GUIDE.md`

### Admin Pages
- **Dashboard:** Travel CRM → Dashboard
- **Settings:** Travel CRM → Settings
- **Packages:** Travel CRM → Custom Packages
- **Services:** Travel CRM → Services
- **Hero Slider:** Travel CRM → Hero Slider

---

## ✅ Success Checklist

After completing this guide, you should have:

- [x] Plugin installed and configured
- [x] Essential pages created (Package Details, Account, Login, Register)
- [x] At least 5 packages added
- [x] Homepage created with hero slider, services grid, and featured packages
- [x] Service pages created for all services
- [x] Navigation menu setup
- [x] Plugin settings configured
- [x] Payment settings configured
- [x] Email settings configured

---

## 🎉 You're Done!

Your travel website is now ready! Users can:
- ✅ Search for packages
- ✅ View package details
- ✅ Book packages
- ✅ Create accounts
- ✅ Manage bookings
- ✅ Contact you via query forms

**Next Steps:**
1. Add more packages
2. Customize branding and colors
3. Configure payment gateways
4. Set up email notifications
5. Test booking flow
6. Launch your website!

---

## 📝 Notes

- **Package IDs:** All packages use numeric IDs (1, 2, 3, etc.) automatically
- **Package Details:** One page works for all packages using URL parameter `?package_id=123`
- **Search:** Each service has its own search page with service-specific branding
- **Responsive:** All shortcodes are fully responsive and mobile-friendly
- **SEO-Friendly:** All pages are SEO-optimized with proper meta tags

---

**Last Updated:** 2024-12-19  
**Plugin Version:** 2.3.0  
**Guide Version:** 1.0

---

**Happy Building!** 🚀

