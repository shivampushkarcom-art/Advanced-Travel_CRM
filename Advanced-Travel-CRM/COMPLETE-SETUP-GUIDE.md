# 🎯 Complete Setup Guide - Search to Booking Flow

## ✅ What You Have Now

You already have:
- ✅ **Search Page** - Working! Users can search for packages
- ✅ **Search Results** - Working! Results appear automatically after search

## 📋 What Happens Next (User Flow)

### Current Flow:
1. **User searches** → Results appear on same page ✅
2. **User clicks "View Details"** → **NEEDS TO BE SET UP** ⚠️
3. **User clicks "Book Now"** → **NEEDS TO BE SET UP** ⚠️

---

## 🏗️ What Pages You Need to Create

### **Option 1: Simple Setup (Recommended) - 2 Pages**

#### **Page 1: Search Page** ✅ (You already have this!)
- **Page Title:** "Search Tours" (or your service name)
- **Shortcode:** `[atc_premium_search service="tours"]`
- **What it does:** Shows search form, results appear below automatically

#### **Page 2: Package Details Page** ⚠️ (You need to create this!)
- **Page Title:** "Package Details" (or "View Package")
- **Shortcode:** `[atc_package_details]`
- **What it does:** Shows full package information when user clicks "View Details"
- **URL Format:** `https://yoursite.com/package-details/?package_id=123`

**Note:** Booking wizard opens automatically as a modal - **NO SEPARATE PAGE NEEDED!** ✅

---

## 📝 Step-by-Step Setup Instructions

### Step 1: Create Package Details Page

1. Go to **WordPress Admin → Pages → Add New**
2. **Page Title:** "Package Details" (or "View Package")
3. **Page Slug/URL:** `package-details` (important for links to work)
4. In the content editor, add:
   ```
   [atc_package_details]
   ```
5. Click **Publish**
6. **Note the page URL** (e.g., `https://yoursite.com/package-details/`)

### Step 2: Configure "View Details" Button

The "View Details" button in search results needs to know where to redirect. 

**Option A: Automatic (Recommended)**
- The system will automatically redirect to `/package-details/?package_id=123`
- Make sure your Package Details page slug is `package-details`

**Option B: Custom URL**
- If your page has a different slug, you may need to update the JavaScript
- Contact support if you need help with this

### Step 3: Test the Flow

1. **Visit your Search Page**
   - Enter search criteria
   - Click "Search Packages"
   - Results should appear

2. **Click "View Details" on any package**
   - Should redirect to Package Details page
   - Should show full package information

3. **Click "Book Now" on Package Details page**
   - Booking wizard should open as a modal popup
   - 3-step booking process:
     - Step 1: Review package
     - Step 2: Enter traveler info
     - Step 3: Payment

---

## 🎯 Complete User Journey

```
1. User visits Search Page
   ↓
2. User enters search criteria (destination, dates, guests)
   ↓
3. User clicks "Search Packages"
   ↓
4. Results appear automatically on same page ✅
   ↓
5. User clicks "View Details" on a package
   ↓
6. Redirects to Package Details page ⚠️ (You need to create this!)
   ↓
7. User sees full package information
   ↓
8. User clicks "Book Now"
   ↓
9. Booking wizard opens as modal popup ✅ (Automatic - no page needed!)
   ↓
10. User completes booking (3 steps)
   ↓
11. Booking confirmation shown ✅
```

---

## 📄 Page Structure Summary

### **Page 1: Search Page** ✅ (Already Created)
- **URL:** `/search-tours/` (or your custom URL)
- **Shortcode:** `[atc_premium_search service="tours"]`
- **Purpose:** Search form + Results display

### **Page 2: Package Details Page** ⚠️ (Need to Create)
- **URL:** `/package-details/` (recommended slug)
- **Shortcode:** `[atc_package_details]`
- **Purpose:** Show full package information
- **Gets package ID from URL:** `?package_id=123`

### **Booking Wizard** ✅ (Automatic - No Page Needed!)
- Opens as modal popup
- No shortcode needed
- Works automatically when user clicks "Book Now"

---

## 🔧 Quick Setup Checklist

- [ ] **Search Page** - ✅ Already created and working
- [ ] **Package Details Page** - ⚠️ Need to create:
  - [ ] Create new page: "Package Details"
  - [ ] Add shortcode: `[atc_package_details]`
  - [ ] Set page slug to: `package-details`
  - [ ] Publish page
- [ ] **Test "View Details" button** - Should redirect to Package Details page
- [ ] **Test "Book Now" button** - Should open booking wizard modal

---

## 💡 Important Notes

### 1. **Package Details Page Slug**
- **Recommended:** `package-details`
- The "View Details" button automatically redirects to `/package-details/?package_id=123`
- If you use a different slug, you may need to update the JavaScript

### 2. **Booking Wizard**
- **NO PAGE NEEDED!** ✅
- Opens automatically as a modal popup
- Works on both Search Results and Package Details pages
- No shortcode needed

### 3. **Package ID**
- Package ID is passed via URL parameter: `?package_id=123`
- The Package Details shortcode automatically reads this from the URL
- You can also specify it in shortcode: `[atc_package_details package_id="123"]`

---

## 🎨 Customization Options

### Package Details Page
- You can customize the page title, description, etc.
- The shortcode handles all the package display logic
- Just add `[atc_package_details]` and it works!

### Booking Wizard
- Opens automatically as a modal
- Styled to match your theme
- No configuration needed

---

## ❓ Common Questions

### Q: Do I need a separate page for booking?
**A:** No! The booking wizard opens as a modal popup automatically. No page or shortcode needed.

### Q: What if my Package Details page has a different URL?
**A:** The system automatically uses `/package-details/` as the default. If you need a different URL, you may need to update the JavaScript (contact support).

### Q: Can I customize the Package Details page?
**A:** Yes! You can add content above/below the shortcode, customize the page title, etc. The shortcode handles the package display.

### Q: What happens after booking?
**A:** After booking is complete:
- Booking confirmation is shown
- Email is sent to customer
- Booking is saved in database
- Admin can view booking in WordPress admin

---

## 🚀 Next Steps

1. **Create Package Details Page** (5 minutes)
   - Go to Pages → Add New
   - Title: "Package Details"
   - Slug: `package-details`
   - Add shortcode: `[atc_package_details]`
   - Publish

2. **Test the Complete Flow** (5 minutes)
   - Visit Search Page
   - Search for packages
   - Click "View Details" on a package
   - Verify it redirects to Package Details page
   - Click "Book Now"
   - Verify booking wizard opens

3. **You're Done!** ✅

---

## 📞 Need Help?

If you need help with:
- Setting up the Package Details page
- Customizing the booking flow
- Changing URLs or redirects
- Any other questions

Just ask! I'm here to help. 🎉

