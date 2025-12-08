# 📘 ATC Premium Search & Booking - Shortcode Usage Guide

## 🎯 Overview

The premium search and booking system uses **3 main shortcodes** that work together:

1. **`[atc_premium_search]`** - Search form (starting point)
2. **`[atc_premium_results]`** - Results display (shown after search)
3. **`[atc_package_details]`** - Package details page (shown when viewing a package)

---

## 📋 Shortcode Flow

```
User Journey:
Search Page → Results Page → Package Details → Booking Wizard
     ↓              ↓              ↓              ↓
[atc_premium_search] → [atc_premium_results] → [atc_package_details] → Booking Modal (automatic)
```

---

## 1️⃣ Premium Search Page

### Shortcode:
```
[atc_premium_search service="tours" title="Find Your Perfect Tour" subtitle="Search from thousands of packages"]
```

### Where to Add:
- **Create a new page** (e.g., "Search Tours" or "Book Now")
- **Add ONLY this shortcode** on the page
- This is your **landing/search page**

### Example Page Setup:
1. Go to **WordPress Admin → Pages → Add New**
2. Title: "Search Tours" or "Book Your Trip"
3. Add shortcode: `[atc_premium_search service="tours"]`
4. Publish the page

### Parameters:
- `service` - Service type: `tours`, `hotels`, `flights`, `trains`, `safari`, `cars`, `forex`
- `title` - Hero title (optional, default: "Find Your Perfect Travel Package")
- `subtitle` - Hero subtitle (optional, default: "Search from thousands of verified packages")

### How It Works:
- User enters search criteria (destination, dates, guests, budget)
- On submit, it **automatically shows results** on the same page
- Results appear **below the search form** automatically

---

## 2️⃣ Premium Results (Automatic - No Need to Add!)

### Important: 
**You DON'T need to manually add this shortcode!**

The `[atc_premium_search]` shortcode **automatically includes** the results section. When a user searches, results appear automatically on the same page.

### If You Want a Separate Results Page:

If you want a **dedicated results page** (optional), you can create one:

1. Create a new page: "Search Results"
2. Add shortcode: `[atc_premium_results service="tours"]`
3. Users will be redirected here after search (if you configure it)

### Shortcode (Optional):
```
[atc_premium_results service="tours"]
```

### Parameters:
- `service` - Service type
- `view` - Default view: `grid` or `list` (optional, default: `grid`)
- `columns` - Grid columns (optional, default: `3`)
- `per_page` - Results per page (optional, default: `12`)

---

## 3️⃣ Package Details Page

### Shortcode:
```
[atc_package_details package_id="123"]
```

### Where to Add:
- **Create a new page** (e.g., "Package Details" or "View Package")
- **Add this shortcode** on the page
- This page shows **full package information**

### Example Page Setup:
1. Go to **WordPress Admin → Pages → Add New**
2. Title: "Package Details"
3. Add shortcode: `[atc_package_details]`
4. Publish the page

### How It Works:
- Package ID is passed via **URL parameter**: `?package_id=123`
- If no `package_id` in URL, you can specify it in shortcode
- When user clicks "View Details" from results, they're redirected here
- **"Book Now" button** opens the premium booking wizard automatically

### Parameters:
- `package_id` - Package ID (optional, will use URL parameter if not provided)

### URL Format:
```
https://yoursite.com/package-details/?package_id=123
```

---

## 🏗️ Recommended Page Structure

### Option 1: Single Page (Recommended for Simple Setup)

**Page: "Search & Book"**
```
[atc_premium_search service="tours"]
```
- Search form at top
- Results appear below automatically when user searches
- Clicking "View Details" opens package details in modal or redirects

### Option 2: Two-Page Setup (Recommended for Better UX)

**Page 1: "Search Tours"**
```
[atc_premium_search service="tours"]
```
- Landing page with search form
- Results appear below after search

**Page 2: "Package Details"**
```
[atc_package_details]
```
- Shows full package information
- Gets package_id from URL: `?package_id=123`
- "Book Now" button opens booking wizard

### Option 3: Three-Page Setup (Advanced)

**Page 1: "Search"**
```
[atc_premium_search service="tours"]
```

**Page 2: "Results"**
```
[atc_premium_results service="tours"]
```

**Page 3: "Package Details"**
```
[atc_package_details]
```

---

## 🔄 How They Work Together

### Flow Example:

1. **User visits Search Page**
   - Sees search form
   - Enters: Destination = "Goa", Dates, Guests = 2

2. **User clicks "Search Packages"**
   - Results appear **automatically** on the same page below the form
   - Shows matching packages in grid/list view

3. **User clicks "View Details" on a package**
   - Redirects to Package Details page with `?package_id=123`
   - Shows full package information

4. **User clicks "Book Now"**
   - **Premium Booking Wizard opens** (modal popup)
   - 3-step booking process:
     - Step 1: Review package
     - Step 2: Enter traveler info
     - Step 3: Payment

5. **Booking Complete**
   - Booking confirmation shown
   - Email sent to customer

---

## 📝 Step-by-Step Setup Guide

### Step 1: Create Search Page

1. Go to **WordPress Admin → Pages → Add New**
2. Title: **"Search Tours"** (or your service name)
3. In the content editor, add:
   ```
   [atc_premium_search service="tours" title="Find Your Perfect Tour" subtitle="Search from thousands of packages"]
   ```
4. Click **Publish**
5. Note the page URL (e.g., `/search-tours/`)

### Step 2: Create Package Details Page

1. Go to **WordPress Admin → Pages → Add New**
2. Title: **"Package Details"**
3. In the content editor, add:
   ```
   [atc_package_details]
   ```
4. Click **Publish**
5. Note the page URL (e.g., `/package-details/`)

### Step 3: Configure Permalinks (Important!)

1. Go to **WordPress Admin → Settings → Permalinks**
2. Make sure **"Post name"** is selected
3. Click **Save Changes**

### Step 4: Test the Flow

1. Visit your Search page
2. Enter search criteria and click "Search Packages"
3. Results should appear below
4. Click "View Details" on any package
5. Should redirect to Package Details page
6. Click "Book Now"
7. Booking wizard should open

---

## 🎨 Customization Examples

### Example 1: Tours Search Page
```
[atc_premium_search service="tours" title="Discover Amazing Tours" subtitle="Book your dream vacation today"]
```

### Example 2: Hotels Search Page
```
[atc_premium_search service="hotels" title="Find Your Perfect Stay" subtitle="Best hotels at best prices"]
```

### Example 3: Flights Search Page
```
[atc_premium_search service="flights" title="Book Your Flight" subtitle="Compare prices and book instantly"]
```

### Example 4: Package Details with Specific Package
```
[atc_package_details package_id="123"]
```

---

## ⚙️ Advanced Configuration

### Multiple Service Pages

You can create separate search pages for each service:

- `/search-tours/` → `[atc_premium_search service="tours"]`
- `/search-hotels/` → `[atc_premium_search service="hotels"]`
- `/search-flights/` → `[atc_premium_search service="flights"]`

All can use the same Package Details page!

### Custom Results Page (Optional)

If you want a separate results page:

1. Create page: "Search Results"
2. Add: `[atc_premium_results service="tours"]`
3. Configure JavaScript to redirect to this page after search

---

## 🐛 Troubleshooting

### Problem: Results don't appear after search
**Solution:** Make sure `atc-premium-search.js` is loaded. Check browser console for errors.

### Problem: Package details page shows "Package not found"
**Solution:** 
- Check if package_id is in URL: `?package_id=123`
- Verify package exists in database
- Check package status is "active"

### Problem: Booking wizard doesn't open
**Solution:**
- Make sure `atc-premium-booking.js` is loaded
- Check browser console for JavaScript errors
- Verify jQuery is loaded

### Problem: Search form doesn't submit
**Solution:**
- Check if REST API is working: `/wp-json/atc/v1/search`
- Verify nonce is correct
- Check browser console for errors

---

## 📱 Mobile Responsive

All shortcodes are **fully responsive** and work on:
- Desktop
- Tablet
- Mobile

No additional configuration needed!

---

## 🎯 Quick Reference

| Shortcode | Where to Use | Purpose |
|-----------|--------------|---------|
| `[atc_premium_search]` | Search/Landing Page | Search form |
| `[atc_premium_results]` | Results Page (optional) | Results display |
| `[atc_package_details]` | Package Details Page | Full package info |

---

## 💡 Pro Tips

1. **Use descriptive page titles** - Helps with SEO
2. **Add navigation menu** - Link to Search page from main menu
3. **Test on mobile** - All components are responsive
4. **Use same Package Details page** - For all services
5. **Keep it simple** - Start with Option 1 (single page setup)

---

## 📞 Need Help?

If you encounter any issues:
1. Check browser console (F12) for errors
2. Verify all files are loaded correctly
3. Check WordPress REST API is enabled
4. Verify database tables exist

---

**Happy Booking! 🎉**

