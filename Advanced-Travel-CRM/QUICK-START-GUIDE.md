# 🚀 Quick Start Guide - ATC Premium Search & Booking

## 📍 Simple Setup (Recommended)

### Step 1: Create Search Page

1. **Go to:** WordPress Admin → Pages → Add New
2. **Title:** "Search Tours" (or your service name)
3. **Add this shortcode:**
   ```
   [atc_premium_search service="tours"]
   ```
4. **Click Publish**
5. **Done!** ✅

**What happens:**
- User sees search form
- User searches → Results appear **automatically** below
- User clicks "View Details" → Opens package details
- User clicks "Book Now" → Booking wizard opens

---

### Step 2: Create Package Details Page (Optional but Recommended)

1. **Go to:** WordPress Admin → Pages → Add New
2. **Title:** "Package Details"
3. **Add this shortcode:**
   ```
   [atc_package_details]
   ```
4. **Click Publish**
5. **Done!** ✅

**What happens:**
- When user clicks "View Details" from results
- They're redirected to this page with package info
- "Book Now" button opens booking wizard

---

## 🎯 Visual Flow

```
┌─────────────────────────────────────┐
│   SEARCH PAGE                       │
│   [atc_premium_search]             │
│                                     │
│   ┌───────────────────────────┐   │
│   │  Search Form               │   │
│   │  - Destination             │   │
│   │  - Dates                   │   │
│   │  - Guests                  │   │
│   │  - Budget                  │   │
│   └───────────────────────────┘   │
│                                     │
│   [User clicks "Search"]            │
│           ↓                         │
│   ┌───────────────────────────┐   │
│   │  RESULTS (Auto-shown)     │   │
│   │  - Package Cards          │   │
│   │  - Filters Sidebar        │   │
│   │  - Sort Options           │   │
│   └───────────────────────────┘   │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   PACKAGE DETAILS PAGE               │
│   [atc_package_details]              │
│                                     │
│   ┌───────────────────────────┐   │
│   │  Full Package Info        │   │
│   │  - Images                 │   │
│   │  - Description            │   │
│   │  - Features               │   │
│   │  - Price                  │   │
│   │  - [Book Now] Button      │   │
│   └───────────────────────────┘   │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   BOOKING WIZARD (Modal Popup)       │
│   (Automatic - No shortcode needed) │
│                                     │
│   Step 1: Package Review            │
│   Step 2: Traveler Info             │
│   Step 3: Payment                   │
└─────────────────────────────────────┘
```

---

## 📝 Example Pages Setup

### Page 1: "Search Tours"
**URL:** `/search-tours/`
**Content:**
```
[atc_premium_search service="tours" title="Find Your Perfect Tour" subtitle="Search from thousands of packages"]
```

### Page 2: "Package Details"
**URL:** `/package-details/`
**Content:**
```
[atc_package_details]
```

---

## ⚙️ Service Types

Change the `service` parameter for different services:

- `service="tours"` - Tours
- `service="hotels"` - Hotels
- `service="flights"` - Flights
- `service="trains"` - Trains
- `service="safari"` - Safari
- `service="cars"` - Car Rentals
- `service="forex"` - Forex

---

## 🎨 Customization Examples

### Example 1: Tours
```
[atc_premium_search service="tours" title="Discover Amazing Tours" subtitle="Book your dream vacation"]
```

### Example 2: Hotels
```
[atc_premium_search service="hotels" title="Find Your Perfect Stay" subtitle="Best hotels at best prices"]
```

### Example 3: Flights
```
[atc_premium_search service="flights" title="Book Your Flight" subtitle="Compare prices and book instantly"]
```

---

## ❓ FAQ

### Q: Do I need to add results shortcode separately?
**A:** No! Results appear automatically when user searches. The `[atc_premium_search]` shortcode includes everything.

### Q: Where does the booking wizard come from?
**A:** It's automatic! When user clicks "Book Now", the wizard opens as a modal popup. No shortcode needed.

### Q: Can I use the same Package Details page for all services?
**A:** Yes! The page automatically gets the package_id from the URL.

### Q: How do I link to the search page?
**A:** Add it to your WordPress menu:
- Go to Appearance → Menus
- Add your "Search Tours" page to the menu
- Save menu

---

## 🎯 That's It!

You only need **2 pages**:
1. **Search Page** - `[atc_premium_search service="tours"]`
2. **Package Details Page** - `[atc_package_details]`

Everything else works automatically! 🎉

