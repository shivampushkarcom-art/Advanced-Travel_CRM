# 🎯 Tour Landing Page & Package Display Guide

## 📋 Overview

This guide explains how to create tour landing pages, organize packages into categories, create package groups, and display packages on your homepage or category pages.

---

## 🏗️ Part 1: Understanding the System

### **Package Organization Methods:**

1. **Categories** - Predefined categories (Honeymoon, Adventure, Family, etc.)
2. **Package Groups** - Custom collections you create (e.g., "Summer Specials", "Weekend Getaways")
3. **Featured Packages** - Mark packages as featured to highlight them
4. **Service Types** - Organize by service (Tours, Hotels, Flights, etc.)

---

## 📦 Part 2: Creating & Managing Packages

### **Step 1: Create Packages**

1. Go to **ATC Dashboard → Custom Packages → Add New**
2. Fill in all package details:
   - **Basic Info**: Name, description, destination, duration
   - **Images & Gallery**: Main image + gallery images
   - **Itinerary**: Day-wise itinerary
   - **Details & Features**: Highlights, inclusions, exclusions
   - **Categories & Tags**: Select category, type, tags, activities
   - **Pricing**: Price, original price, adults, children
3. **Mark as Featured** (optional): Check "Featured" checkbox
4. Click **"Add Package"** or **"Update Package"**

### **Step 2: Assign Category to Package**

1. While creating/editing a package, go to **"Categories & Tags"** tab
2. Select a **Category** from dropdown:
   - Honeymoon
   - Pilgrimage
   - Adventure
   - Family
   - Luxury
   - Budget
   - Beach
   - Hill Station
   - Wildlife
   - Cultural
   - Spiritual
   - Weekend Getaway
   - International
   - Domestic
   - Jungle Safari
3. Select **Package Type**:
   - Domestic
   - International
   - Weekend
   - Extended
4. Add **Tags** (comma-separated): e.g., "beach, adventure, family-friendly"
5. Add **Activities** (comma-separated): e.g., "Scuba Diving, Trekking, Sightseeing"
6. Save package

---

## 🎨 Part 3: Creating Package Groups (Collections)

### **What are Package Groups?**

Package Groups are **custom collections** you create to group related packages together. Examples:
- "Honeymoon Packages"
- "Adventure Tours"
- "Weekend Getaways"
- "Summer Specials"
- "Budget Tours"

### **Step 1: Create a Package Group**

1. Go to **ATC Dashboard → Package Groups → Add New Group**
2. Fill in details:
   - **Group Name**: e.g., "Honeymoon Packages"
   - **Slug**: URL-friendly name (auto-generated from name)
   - **Service**: Select service (Tours, Hotels, etc.) or leave empty for all
   - **Description**: Brief description of the group
   - **Image URL**: Featured image for the group
   - **Display Type**: Grid, List, or Carousel
   - **Sort Order**: Lower numbers appear first
   - **Status**: Active or Inactive
3. Click **"Create Group"**

### **Step 2: Assign Packages to a Group**

1. Go to **ATC Dashboard → Custom Packages**
2. **Edit** a package you want to add to a group
3. In the **"Basic Info"** tab, you'll see a **"Group ID"** field (if implemented)
4. Or use the database `group_id` field to link packages to groups
5. Save package

**Note:** Currently, package-to-group assignment is done via the `group_id` field in the database. A UI for this will be added in future updates.

---

## 🏠 Part 4: Creating Tour Landing Pages

### **Option 1: Search-Based Landing Page (Recommended)**

**Best for:** Main tour booking page with search functionality

1. **Create a new page**: WordPress Admin → Pages → Add New
2. **Title**: "Tours" or "Book Your Tour"
3. **Add shortcode**:
   ```
   [atc_premium_search service="tours" title="Find Your Perfect Tour" subtitle="Search from thousands of packages"]
   ```
4. **Publish** the page
5. **Set as homepage** (optional): Settings → Reading → Homepage

**Features:**
- ✅ Premium search form
- ✅ Auto-shows results on same page
- ✅ Filters and sorting
- ✅ Grid view of packages

### **Option 2: Category-Based Landing Page**

**Best for:** Dedicated pages for specific categories

1. **Create a new page**: WordPress Admin → Pages → Add New
2. **Title**: e.g., "Honeymoon Tours"
3. **Add shortcode**:
   ```
   [atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="12"]
   ```
4. **Publish** the page

**Available Categories:**
- `Honeymoon`
- `Pilgrimage`
- `Adventure`
- `Family`
- `Luxury`
- `Budget`
- `Beach`
- `Hill Station`
- `Wildlife`
- `Cultural`
- `Spiritual`
- `Weekend Getaway`
- `International`
- `Domestic`

### **Option 3: Featured Packages Landing Page**

**Best for:** Showcasing your best packages

1. **Create a new page**: WordPress Admin → Pages → Add New
2. **Title**: "Featured Tours" or "Popular Packages"
3. **Add shortcode**:
   ```
   [atc_featured_packages service="tours" columns="3" per_page="12"]
   ```
4. **Publish** the page

### **Option 4: Package Group Landing Page**

**Best for:** Custom collections you created

1. **Create a new page**: WordPress Admin → Pages → Add New
2. **Title**: e.g., "Honeymoon Packages"
3. **Add shortcode**:
   ```
   [atc_package_group slug="honeymoon-packages" columns="3" per_page="12"]
   ```
   OR
   ```
   [atc_package_group group_id="GRP-20241201-1234" columns="3" per_page="12"]
   ```
4. **Publish** the page

---

## 🏡 Part 5: Displaying Packages on Homepage

### **Method 1: Featured Packages Section**

Add this to your homepage:

```
[atc_featured_packages service="tours" columns="4" per_page="8" title="Featured Tours"]
```

### **Method 2: Multiple Category Sections**

Add multiple sections for different categories:

```
<h2>Honeymoon Tours</h2>
[atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="6"]

<h2>Adventure Tours</h2>
[atc_packages_by_category category="Adventure" service="tours" columns="3" per_page="6"]

<h2>Family Tours</h2>
[atc_packages_by_category category="Family" service="tours" columns="3" per_page="6"]
```

### **Method 3: Package Groups Section**

```
<h2>Popular Collections</h2>
[atc_package_group slug="honeymoon-packages" columns="3" per_page="6"]
[atc_package_group slug="weekend-getaways" columns="3" per_page="6"]
```

### **Method 4: Mixed Approach (Recommended for Homepage)**

```
<!-- Hero Section with Search -->
[atc_premium_search service="tours" title="Discover Amazing Tours"]

<!-- Featured Packages -->
<h2>⭐ Featured Tours</h2>
[atc_featured_packages service="tours" columns="4" per_page="8"]

<!-- Category Sections -->
<h2>💑 Honeymoon Packages</h2>
[atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="6"]

<h2>🏔️ Adventure Tours</h2>
[atc_packages_by_category category="Adventure" service="tours" columns="3" per_page="6"]
```

---

## 📝 Part 6: Available Shortcodes

### **1. `[atc_premium_search]`**
Premium search form with results

**Parameters:**
- `service` (required) - Service type: `tours`, `hotels`, `flights`, etc.
- `title` (optional) - Hero title
- `subtitle` (optional) - Hero subtitle

**Example:**
```
[atc_premium_search service="tours" title="Find Your Perfect Tour"]
```

---

### **2. `[atc_packages_by_category]`** ⭐ NEW
Display packages by category

**Parameters:**
- `category` (required) - Category name (Honeymoon, Adventure, etc.)
- `service` (optional) - Service type (default: all)
- `columns` (optional) - Grid columns (default: 3)
- `per_page` (optional) - Packages per page (default: 12)
- `featured_only` (optional) - Show only featured (default: false)

**Example:**
```
[atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="12"]
```

---

### **3. `[atc_featured_packages]`** ⭐ NEW
Display featured packages

**Parameters:**
- `service` (optional) - Service type (default: all)
- `columns` (optional) - Grid columns (default: 3)
- `per_page` (optional) - Packages per page (default: 12)
- `title` (optional) - Section title

**Example:**
```
[atc_featured_packages service="tours" columns="4" per_page="8" title="Featured Tours"]
```

---

### **4. `[atc_package_group]`**
Display packages from a package group

**Parameters:**
- `group_id` OR `slug` (required) - Group identifier
- `columns` (optional) - Grid columns (default: 3)
- `per_page` (optional) - Packages per page (default: 12)

**Example:**
```
[atc_package_group slug="honeymoon-packages" columns="3" per_page="12"]
```

---

### **5. `[atc_package_details]`**
Display full package details page

**Parameters:**
- `package_id` (optional) - Package ID (uses URL parameter if not provided)

**Example:**
```
[atc_package_details]
```

---

## 🎯 Part 7: Step-by-Step Setup Examples

### **Example 1: Complete Tour Landing Page**

**Page Title:** "Tours"

**Content:**
```
[atc_premium_search service="tours" title="Discover Amazing Tours" subtitle="Search from thousands of verified packages"]

<h2>⭐ Featured Tours</h2>
[atc_featured_packages service="tours" columns="4" per_page="8"]

<h2>💑 Honeymoon Packages</h2>
[atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="6"]

<h2>🏔️ Adventure Tours</h2>
[atc_packages_by_category category="Adventure" service="tours" columns="3" per_page="6"]

<h2>👨‍👩‍👧‍👦 Family Tours</h2>
[atc_packages_by_category category="Family" service="tours" columns="3" per_page="6"]
```

---

### **Example 2: Category-Specific Page**

**Page Title:** "Honeymoon Tours"

**Content:**
```
<h1>Honeymoon Tour Packages</h1>
<p>Discover romantic destinations perfect for your honeymoon.</p>

[atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="12"]
```

---

### **Example 3: Homepage with Tours Section**

**Homepage Content:**
```
<!-- Your existing homepage content -->

<section class="tours-section">
    <h2>Explore Our Tour Packages</h2>
    [atc_featured_packages service="tours" columns="4" per_page="8" title="Popular Tours"]
    
    <div class="tour-categories">
        <h3>Browse by Category</h3>
        <div class="category-grid">
            <div class="category-item">
                <h4>Honeymoon</h4>
                [atc_packages_by_category category="Honeymoon" service="tours" columns="2" per_page="4"]
            </div>
            <div class="category-item">
                <h4>Adventure</h4>
                [atc_packages_by_category category="Adventure" service="tours" columns="2" per_page="4"]
            </div>
        </div>
    </div>
</section>
```

---

## 📋 Part 8: Quick Reference

### **Creating Packages:**
1. ATC Dashboard → Custom Packages → Add New
2. Fill all tabs (Basic, Images, Itinerary, Details, Categories, Pricing)
3. Select Category and mark as Featured (optional)
4. Save

### **Creating Package Groups:**
1. ATC Dashboard → Package Groups → Add New Group
2. Enter name, slug, description
3. Select service (optional)
4. Save

### **Assigning Packages to Groups:**
- Currently done via database `group_id` field
- Future update will add UI for this

### **Creating Landing Pages:**
1. Pages → Add New
2. Add appropriate shortcode(s)
3. Publish

### **Displaying on Homepage:**
- Add shortcodes to homepage content
- Use featured packages and category shortcodes
- Mix and match as needed

---

## ✅ Best Practices

1. **Use Categories** for standard organization (Honeymoon, Adventure, etc.)
2. **Use Package Groups** for custom collections (Seasonal offers, Special promotions)
3. **Mark Popular Packages as Featured** to highlight them
4. **Create Dedicated Pages** for major categories
5. **Use Search Page** as main landing page for bookings
6. **Mix Shortcodes** on homepage for variety

---

## 🎉 You're Ready!

Now you can:
- ✅ Create and organize packages
- ✅ Create package groups
- ✅ Build tour landing pages
- ✅ Display packages on homepage
- ✅ Create category-specific pages

**Need Help?** Check the shortcode examples above or refer to the main plugin documentation.

