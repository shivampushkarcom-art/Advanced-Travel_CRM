# 📋 Query Form Shortcode Guide

## 🎯 **Shortcode Used in Package Details Page**

### **Shortcode:** `[atc_query_form]`

**Location:** `includes/packages/class-atc-package-details-enhanced.php` (line 619)

**Current Usage:**
```php
<?php echo do_shortcode('[atc_query_form package_id="' . esc_attr($package['id']) . '" show_button="false"]'); ?>
```

**Parameters:**
- `package_id` - The package ID (required for package-specific query form)
- `show_button` - Set to `"false"` to hide the trigger button (form is shown inline)

**What It Does:**
- Displays the package-specific query form
- When `package_id` is provided, it automatically uses `package_query_shortcode()` internally
- Shows the query form modal when "Ask for More Details" button is clicked
- Loads query fields from service config + package metadata

---

## 🎯 **Admin Menu - Query Customization**

### **Location:** Custom Packages Admin Menu

**Path:** `ATC Dashboard → Custom Packages → Edit Package → Query Customization Tab`

**File:** `includes/packages/class-atc-custom-packages.php` (lines 676-750)

**What It Does:**
- **NOT a shortcode** - It's an admin interface for customizing query forms per package
- Allows admins to customize query form fields for each individual package
- Settings are saved in package `metadata` field (JSON)

**Features:**
1. **Pre-filled Values** - Pre-fill query form fields with package-specific values
   - Example: Pre-fill destination, travel dates, etc.
   - Key should match field ID from service config

2. **Custom Message** - Add custom message above the query form
   - Optional message to display above the query form for this package

3. **Query Form Title** - Customize the query form modal title
   - Default: "Ask for More Details"
   - Can be customized per package

4. **Query Form Subtitle** - Customize the query form modal subtitle
   - Optional subtitle for the query form

**How It Works:**
1. Admin goes to `Custom Packages → Edit Package`
2. Clicks on "Query Customization" tab
3. Configures:
   - Pre-filled fields (key-value pairs)
   - Custom message
   - Form title
   - Form subtitle
4. Settings are saved in package `metadata` field
5. When customer clicks "Ask for More Details", the query form uses these customizations

---

## 📝 **Available Shortcodes**

### **1. `[atc_query_form]`** ✅ (Currently Used)

**Purpose:** General service-specific query form OR package-specific query form

**Parameters:**
- `service` - Service key (e.g., "tours", "visa", "hotels")
- `package_id` - Package ID (if provided, becomes package-specific)
- `title` - Custom title
- `subtitle` - Custom subtitle
- `button_text` - Button text
- `button_style` - Button style ("premium")
- `show_button` - Show/hide trigger button ("true" or "false")
- `modal_id` - Custom modal ID

**Usage Examples:**
```php
// Service-specific query form
[atc_query_form service="tours"]

// Package-specific query form (automatically uses package_query_shortcode)
[atc_query_form package_id="123" show_button="false"]

// With custom title
[atc_query_form package_id="123" title="Custom Title" subtitle="Custom Subtitle"]
```

**When `package_id` is provided:**
- Automatically calls `package_query_shortcode()` internally
- Loads package-specific customizations from metadata
- Uses service-specific query fields from service config
- Merges package-specific fields with service fields

---

### **2. `[atc_package_query]`** (Legacy/Alternative)

**Purpose:** Package-specific query form (same as `[atc_query_form]` with `package_id`)

**Parameters:**
- `package_id` - Package ID (required)
- `title` - Custom title
- `subtitle` - Custom subtitle
- `button_text` - Button text
- `button_style` - Button style
- `show_button` - Show/hide trigger button

**Usage:**
```php
[atc_package_query package_id="123" show_button="false"]
```

**Note:** This is essentially the same as `[atc_query_form package_id="123"]` - both use the same `package_query_shortcode()` function.

---

## 🔄 **How They Work Together**

### **Flow:**

1. **Package Details Page:**
   - Uses `[atc_query_form package_id="123" show_button="false"]`
   - Shortcode detects `package_id` parameter
   - Calls `package_query_shortcode()` internally
   - Loads package metadata for customizations
   - Renders query form modal

2. **Admin Customization:**
   - Admin edits package in `Custom Packages → Edit Package`
   - Goes to "Query Customization" tab
   - Configures pre-filled values, custom message, title, subtitle
   - Settings saved in package `metadata` field

3. **Customer Experience:**
   - Customer clicks "Ask for More Details" button
   - Query form modal opens
   - Form loads with:
     - Service-specific query fields (from service config JSON)
     - Package-specific customizations (from package metadata)
     - Pre-filled values (if configured)
     - Custom title/subtitle (if configured)
     - Custom message (if configured)

---

## 📂 **File Locations**

### **Shortcode Registration:**
- **File:** `includes/packages/class-atc-package-query-enhanced.php`
- **Lines:** 13-14
```php
add_shortcode('atc_package_query', [__CLASS__, 'package_query_shortcode']);
add_shortcode('atc_query_form', [__CLASS__, 'query_form_shortcode']);
```

### **Package Details Usage:**
- **File:** `includes/packages/class-atc-package-details-enhanced.php`
- **Line:** 619
```php
<?php echo do_shortcode('[atc_query_form package_id="' . esc_attr($package['id']) . '" show_button="false"]'); ?>
```

### **Admin Customization Interface:**
- **File:** `includes/packages/class-atc-custom-packages.php`
- **Lines:** 676-750 (Query Customization Tab)

### **Query Management Admin Menu:**
- **File:** `includes/packages/class-atc-package-query-enhanced.php`
- **Lines:** 24-33 (Admin Menu)
- **Path:** `ATC Dashboard → Package Queries`
- **Purpose:** View and manage submitted queries (not customization)

---

## ✅ **Summary**

| Location | Shortcode/Interface | Purpose |
|----------|---------------------|---------|
| **Package Details Page** | `[atc_query_form]` | Display package-specific query form |
| **Admin - Custom Packages** | Query Customization Tab (No shortcode) | Customize query form per package |
| **Admin - Package Queries** | Admin Menu (No shortcode) | View/manage submitted queries |

---

## 🎯 **Current Implementation**

✅ **Package Details Page:** Uses `[atc_query_form package_id="..." show_button="false"]`

✅ **Admin Customization:** Available in `Custom Packages → Edit Package → Query Customization Tab`

✅ **Query Management:** Available in `ATC Dashboard → Package Queries`

---

**All query form functionality is properly integrated!** 🎉

