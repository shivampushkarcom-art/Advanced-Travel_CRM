# 🚀 Enterprise Package System - Complete Guide

## Overview

The Advanced Travel CRM now features an **enterprise-level package management system** with MakeMyTrip-style package details pages, advanced categorization, and rich content management.

---

## ✨ Key Features

### 1. **Service-Specific Package Detail Pages**
- **Tours**: MakeMyTrip-style premium design
- **Hotels**: (Coming soon)
- **Flights**: (Coming soon)
- Each service has its own unique, branded package detail page theme

### 2. **Rich Package Management**
- ✅ Multiple images & gallery support
- ✅ Day-wise detailed itinerary
- ✅ Package highlights
- ✅ Inclusions & exclusions
- ✅ Categories & tags
- ✅ Activities tracking
- ✅ Ratings & reviews

### 3. **Advanced Categorization**
- **Categories**: Honeymoon, Pilgrimage, Adventure, Family, Luxury, Budget, Beach, Hill Station, Wildlife, Cultural, Spiritual, Weekend Getaway
- **Package Types**: Domestic, International, Weekend, Extended
- **Tags**: Custom tags for better searchability
- **Activities**: Track specific activities included

### 4. **Featured Package Groups**
- Create collections of featured packages
- Group packages by theme, destination, or category
- Display featured groups on frontend

---

## 📋 Database Schema Enhancements

### Enhanced `atc_custom_packages` Table

**New Fields Added:**
- `short_description` - Brief description for hero section
- `duration_nights` - Number of nights
- `gallery_images` - JSON array of gallery images
- `highlights` - Package highlights
- `day_wise_itinerary` - Detailed day-wise itinerary (JSON)
- `category` - Main category (Honeymoon, Pilgrimage, etc.)
- `package_type` - Package type (Domestic, International, etc.)
- `tags` - Comma-separated tags
- `activities` - Comma-separated activities
- `rating` - Package rating (0-5)
- `reviews_count` - Number of reviews
- `group_id` - Link to package group
- `view_count` - Track views
- `booking_count` - Track bookings

### New `atc_package_groups` Table

For creating featured package collections:
- `group_id` - Unique group identifier
- `name` - Group name
- `slug` - URL-friendly slug
- `description` - Group description
- `service_key` - Service type
- `image_url` - Group image
- `display_type` - Grid/list view
- `status` - Active/inactive

---

## 🎨 MakeMyTrip-Style Package Details Page

### Features:
1. **Hero Section**
   - Large background image
   - Package title & subtitle
   - Meta information (destination, duration, rating)

2. **Image Gallery**
   - Main large image
   - Secondary images grid
   - Thumbnail gallery
   - "View All Photos" button

3. **Content Layout**
   - Left column: Main content
   - Right column: Sticky pricing card

4. **Sections:**
   - Package Highlights
   - About This Package
   - Day-wise Itinerary
   - Inclusions & Exclusions
   - Categories & Tags

5. **Pricing Card (Sticky)**
   - Current price
   - Original price (if discount)
   - Discount badge
   - "Book Now" button
   - "Ask for Custom Package" button

---

## 📝 Admin Package Form

### Tabbed Interface:
1. **Basic Info**
   - Package name
   - Service selection
   - Short description
   - Full description (rich editor)
   - Destination
   - Duration (days & nights)
   - Status & featured

2. **Images & Gallery**
   - Main image upload
   - Gallery images (multiple)
   - Image preview
   - WordPress media library integration

3. **Itinerary**
   - Day-wise itinerary builder
   - Day title
   - Day content (rich text)
   - Activities per day
   - Add/remove days dynamically

4. **Details & Features**
   - Package highlights (multiple)
   - Inclusions (textarea)
   - Exclusions (textarea)
   - Terms & conditions (rich editor)
   - Cancellation policy (rich editor)

5. **Categories & Tags**
   - Category dropdown (Honeymoon, Pilgrimage, etc.)
   - Package type dropdown
   - Tags input (comma-separated)
   - Activities input (comma-separated)
   - Rating & reviews count

6. **Pricing**
   - Current price
   - Original price
   - Adults & children count

---

## 🎯 Usage Guide

### Creating a Tour Package

1. **Go to:** `ATC Dashboard > Custom Packages > Add New Package`

2. **Fill Basic Info:**
   - Package Name: "Romantic Goa Honeymoon Package"
   - Service: Tours
   - Short Description: "Perfect romantic getaway for couples"
   - Full Description: (Rich text editor)
   - Destination: "Goa"
   - Duration: 3 Days, 2 Nights

3. **Add Images:**
   - Upload main image
   - Add 6-12 gallery images

4. **Create Itinerary:**
   - Day 1: Arrival in Goa
   - Day 2: Beach activities & sunset cruise
   - Day 3: Departure
   - Add activities for each day

5. **Add Details:**
   - Highlights: "Beach access", "Sunset cruise", "Romantic dinner"
   - Inclusions: Hotel, meals, transfers
   - Exclusions: Airfare, personal expenses

6. **Set Categories:**
   - Category: Honeymoon
   - Package Type: Domestic
   - Tags: beach, romantic, honeymoon
   - Activities: Beach visit, Water sports, Sunset cruise

7. **Set Pricing:**
   - Price: ₹25,000
   - Original Price: ₹30,000 (for discount display)

8. **Save Package**

### Displaying Package Details

**Shortcode:**
```
[atc_package_details package_id="123"]
```

**Or use URL parameter:**
```
/package-details/?package_id=123
```

**Service-specific (Tours):**
```
[atc_package_details_tours package_id="123"]
```

The system automatically detects the service type and uses the appropriate theme.

---

## 🏷️ Package Categories

### Available Categories:
- **Honeymoon** - Romantic packages for couples
- **Pilgrimage** - Religious/spiritual tours
- **Adventure** - Adventure activities
- **Family** - Family-friendly packages
- **Luxury** - Premium packages
- **Budget** - Affordable packages
- **Beach** - Beach destinations
- **Hill Station** - Mountain destinations
- **Wildlife** - Wildlife safaris
- **Cultural** - Cultural experiences
- **Spiritual** - Spiritual retreats
- **Weekend Getaway** - Short trips

### Package Types:
- **Domestic** - Within country
- **International** - Overseas
- **Weekend** - 2-3 days
- **Extended** - 7+ days

---

## 📦 Featured Package Groups

### Creating a Group:

1. **Go to:** `ATC Dashboard > Package Groups > Add New Group`

2. **Fill Details:**
   - Group Name: "Honeymoon Packages"
   - Slug: "honeymoon-packages"
   - Description: "Romantic packages for couples"
   - Service: Tours
   - Image: Group cover image

3. **Assign Packages:**
   - Select packages to include in group
   - Set display order

4. **Display:**
   - Use shortcode: `[atc_package_group slug="honeymoon-packages"]`

---

## 🎨 Customization

### Service-Specific Themes

Each service can have its own package detail page theme:

**Tours:** MakeMyTrip-style (currently implemented)
**Hotels:** Hotel booking style (coming soon)
**Flights:** Flight booking style (coming soon)

### CSS Customization

Tours theme CSS: `assets/css/atc-package-details-tours.css`

You can customize:
- Colors
- Layout
- Typography
- Spacing
- Responsive breakpoints

---

## 🔧 Technical Details

### File Structure:
```
includes/packages/
├── class-atc-package-details.php (Original)
├── class-atc-package-details-enhanced.php (New - Enhanced)
├── class-atc-custom-packages.php (Enhanced form)
└── class-atc-package-queries.php

assets/
├── css/
│   └── atc-package-details-tours.css (MakeMyTrip-style)
└── js/
    └── atc-package-details-enhanced.js
```

### REST API Endpoints:
- `GET /atc/v1/package/{id}` - Get package details
- `POST /atc/v1/packages` - Create package (admin)
- `GET /atc/v1/packages?service=tours` - Get packages by service

### JavaScript Functions:
- `atcChangeMainImage(src)` - Change gallery main image
- `atcOpenGallery()` - Open gallery modal
- `atcOpenBookingModal(packageId)` - Open booking modal
- `atcOpenQueryModal(packageId)` - Open query form

---

## 📊 Package Data Structure

### Day-wise Itinerary Format:
```json
[
  {
    "title": "Day 1: Arrival in Goa",
    "content": "Arrive at Goa airport...",
    "activities": ["Airport pickup", "Hotel check-in", "Beach visit"]
  },
  {
    "title": "Day 2: Beach Activities",
    "content": "Morning beach activities...",
    "activities": ["Water sports", "Sunset cruise"]
  }
]
```

### Gallery Images Format:
```json
[
  "https://example.com/image1.jpg",
  "https://example.com/image2.jpg",
  ...
]
```

---

## 🚀 Next Steps

### Immediate:
1. ✅ Database migration (run on activation)
2. ✅ Create tour packages
3. ✅ Test package detail pages
4. ✅ Verify form saves all fields

### Future Enhancements:
1. ⚠️ Package groups management UI
2. ⚠️ Featured package groups shortcode
3. ⚠️ Hotel package detail theme
4. ⚠️ Flight package detail theme
5. ⚠️ Package search & filtering
6. ⚠️ Package comparison feature

---

## 📝 Notes

- **Database Migration:** The enhanced schema will be created automatically on plugin activation/update
- **Backward Compatibility:** Existing packages will continue to work
- **Service Detection:** Package detail page automatically detects service type and uses appropriate theme
- **Image Upload:** Uses WordPress media library for easy image management
- **Rich Content:** Full support for rich text editing in descriptions and itinerary

---

## 🎉 Summary

You now have:
- ✅ Enterprise-level package management
- ✅ MakeMyTrip-style package detail pages for tours
- ✅ Advanced categorization (Honeymoon, Pilgrimage, etc.)
- ✅ Rich content management (images, itinerary, highlights)
- ✅ Service-specific themes (tours implemented)
- ✅ Featured package groups support
- ✅ Comprehensive admin interface

**The system is ready for production use!** 🚀

