# 🎨 ATC Customization System Guide

**Status:** ✅ Complete  
**Date:** 2024-12-19

---

## ✅ What's Been Built

### **1. Drag-and-Drop Page Builder**
- ✅ Visual page builder interface
- ✅ Section reordering (drag-and-drop)
- ✅ Add/remove sections
- ✅ Section preview
- ✅ Save/restore layouts

### **2. Shortcode Manager**
- ✅ Visual shortcode picker
- ✅ Shortcode parameter editor
- ✅ Shortcode preview
- ✅ Copy shortcode functionality
- ✅ Insert into editor

### **3. Elementor Integration**
- ✅ Custom Elementor widgets
- ✅ ATC Travel category
- ✅ All homepage sections as widgets
- ✅ Service landing page widgets
- ✅ Full Elementor compatibility

### **4. Astra Theme Integration**
- ✅ Color scheme compatibility
- ✅ Header/Footer styling
- ✅ Button compatibility
- ✅ Container compatibility

### **5. Inline Text Editor**
- ✅ Click-to-edit text
- ✅ Visual text editor
- ✅ Save text changes
- ✅ Admin-only functionality

---

## 🚀 How to Use

### **Page Builder**

1. **Access Page Builder:**
   - Go to **WordPress Admin → Travel CRM → Page Builder**
   - Or edit any page and click "Open Page Builder" in the meta box

2. **Add Sections:**
   - Click "Add" button next to any section in the sidebar
   - Section will appear in the canvas

3. **Reorder Sections:**
   - Drag sections using the ☰ handle
   - Drop to reorder

4. **Remove Sections:**
   - Click "Remove" button on any section

5. **Save Layout:**
   - Click "Save Layout" button
   - Layout is saved to the page

### **Shortcode Manager**

1. **Access Shortcode Manager:**
   - Go to **WordPress Admin → Travel CRM → Shortcode Manager**
   - Or click "ATC Shortcodes" button in the editor

2. **Insert Shortcode:**
   - Click on any shortcode
   - Fill in parameters
   - Click "Insert Shortcode"
   - Shortcode is inserted into editor

3. **Preview Shortcode:**
   - Select a shortcode
   - Fill in parameters
   - Click "Preview" to see output

4. **Copy Shortcode:**
   - Click "Copy" button on any shortcode card
   - Shortcode is copied to clipboard

### **Elementor Integration**

1. **Access Elementor:**
   - Edit any page with Elementor
   - Look for "ATC Travel" category in widgets panel

2. **Available Widgets:**
   - **ATC Hero Section** - Homepage hero
   - **ATC Services Grid** - Services display
   - **ATC Featured Packages** - Featured packages
   - **ATC Testimonials** - Customer reviews
   - **ATC Statistics** - Stats counter
   - **ATC Call to Action** - CTA section
   - **ATC Service Landing** - Service landing pages

3. **Use Widgets:**
   - Drag widget to page
   - Configure settings in panel
   - Preview and publish

### **Astra Theme Compatibility**

1. **Automatic Integration:**
   - Works automatically if Astra theme is active
   - Colors are automatically overridden
   - Header/Footer styled to match ATC theme

2. **Custom Styling:**
   - ATC colors override Astra defaults
   - Buttons use ATC blue color
   - Footer uses ATC dark blue

### **Inline Text Editor**

1. **Enable Editing:**
   - Must be logged in as admin
   - Editable elements have dashed border on hover

2. **Edit Text:**
   - Click on any `.atc-editable` element
   - Edit text in modal
   - Click "Save" to save changes

3. **Make Elements Editable:**
   - Add class `atc-editable` to any element
   - Add data attribute: `data-element-id="unique-id"`

---

## 📋 Available Shortcodes

### **Homepage Shortcodes**

| Shortcode | Description | Parameters |
|-----------|-------------|------------|
| `[atc_homepage_hero]` | Hero section | title, subtitle, show_search |
| `[atc_homepage_services]` | Services grid | title, columns |
| `[atc_homepage_featured_packages]` | Featured packages | service, title, columns, per_page |
| `[atc_homepage_why_choose_us]` | Why choose us | title, columns |
| `[atc_homepage_testimonials]` | Testimonials | title, per_page |
| `[atc_homepage_statistics]` | Statistics | customers, packages, destinations, experience |
| `[atc_homepage_cta]` | Call to action | title, subtitle, show_form |

### **Service Landing Pages**

| Shortcode | Description | Parameters |
|-----------|-------------|------------|
| `[atc_tours_landing]` | Tours landing | show_search, show_packages |
| `[atc_hotels_landing]` | Hotels landing | show_search, show_packages |
| `[atc_flights_landing]` | Flights landing | show_search, show_packages |
| `[atc_trains_landing]` | Trains landing | show_search, show_packages |
| `[atc_cars_landing]` | Cars landing | show_search, show_packages |
| `[atc_forex_landing]` | Forex landing | show_search, show_packages |
| `[atc_visa_landing]` | Visa landing | show_search, show_packages |
| `[atc_celebrity_landing]` | Celebrity landing | show_contact_form |

---

## 🎨 Elementor Widgets

### **ATC Hero Widget**
- Title input
- Subtitle textarea
- Show search toggle

### **ATC Services Widget**
- Title input
- Columns number

### **ATC Featured Packages Widget**
- Service select (tours, hotels, flights, etc.)
- Title input
- Columns number
- Per page number

### **ATC Testimonials Widget**
- Title input
- Per page number

### **ATC Statistics Widget**
- Customers text
- Packages text
- Destinations text
- Experience text

### **ATC CTA Widget**
- Title input
- Subtitle textarea
- Show form toggle

### **ATC Service Landing Widget**
- Service select
- Show search toggle
- Show packages toggle

---

## 🔧 Technical Details

### **Files Created**

**PHP Classes:**
- `includes/customization/class-atc-page-builder.php`
- `includes/customization/class-atc-shortcode-manager.php`
- `includes/customization/class-atc-text-editor.php`
- `includes/customization/class-atc-elementor-integration.php`
- `includes/customization/class-atc-astra-integration.php`
- `includes/customization/elementor/class-atc-base-widget.php`
- `includes/customization/elementor/class-atc-hero-widget.php`
- `includes/customization/elementor/class-atc-services-widget.php`
- `includes/customization/elementor/class-atc-featured-packages-widget.php`
- `includes/customization/elementor/class-atc-testimonials-widget.php`
- `includes/customization/elementor/class-atc-statistics-widget.php`
- `includes/customization/elementor/class-atc-cta-widget.php`
- `includes/customization/elementor/class-atc-service-landing-widget.php`

**CSS Files:**
- `assets/css/atc-page-builder.css`
- `assets/css/atc-shortcode-manager.css`
- `assets/css/atc-text-editor.css`
- `assets/css/atc-astra-compatibility.css`

**JavaScript Files:**
- `assets/js/atc-page-builder.js`
- `assets/js/atc-shortcode-manager.js`
- `assets/js/atc-text-editor.js`

---

## 📝 Usage Examples

### **Using Page Builder**

1. Create a new page
2. Go to Page Builder
3. Add sections: Hero → Services → Featured Packages → CTA
4. Reorder as needed
5. Save layout
6. Publish page

### **Using Shortcode Manager**

1. Edit page/post
2. Click "ATC Shortcodes" button
3. Select "Homepage Hero"
4. Fill in: Title = "Welcome", Subtitle = "Your travel partner"
5. Click "Insert Shortcode"
6. Shortcode is added to editor

### **Using Elementor**

1. Edit page with Elementor
2. Drag "ATC Hero Section" widget
3. Configure in panel:
   - Title: "Your Premium Travel Partner"
   - Subtitle: "Experience luxury travel"
   - Show Search: Yes
4. Publish

### **Making Text Editable**

Add to any element:
```html
<h1 class="atc-editable" data-element-id="hero-title">Your Title</h1>
```

Admin users can click to edit inline.

---

## ✅ Compatibility

### **Elementor**
- ✅ Fully compatible
- ✅ Custom widgets available
- ✅ All ATC components as widgets
- ✅ Works with Elementor Pro

### **Astra Theme**
- ✅ Fully compatible
- ✅ Automatic color integration
- ✅ Header/Footer styling
- ✅ Button compatibility
- ✅ Container compatibility

### **Other Themes**
- ✅ Works with any WordPress theme
- ✅ Custom styles override theme defaults
- ✅ No conflicts expected

---

## 🎯 Features

### **Page Builder**
- ✅ Visual interface
- ✅ Drag-and-drop reordering
- ✅ Section management
- ✅ Live preview
- ✅ Save/restore

### **Shortcode Manager**
- ✅ Visual picker
- ✅ Parameter editor
- ✅ Preview functionality
- ✅ Copy to clipboard
- ✅ Insert into editor

### **Elementor Integration**
- ✅ 7 custom widgets
- ✅ Full customization
- ✅ Live preview
- ✅ Responsive

### **Astra Integration**
- ✅ Color compatibility
- ✅ Style integration
- ✅ No conflicts
- ✅ Seamless experience

### **Text Editor**
- ✅ Click to edit
- ✅ Visual editor
- ✅ Save changes
- ✅ Admin only

---

## 🚀 Next Steps

1. **Use Page Builder:**
   - Create your homepage layout
   - Add and reorder sections
   - Save and publish

2. **Use Shortcode Manager:**
   - Insert shortcodes easily
   - Preview before inserting
   - Copy for reuse

3. **Use Elementor:**
   - Build pages visually
   - Use ATC widgets
   - Customize everything

4. **Customize Text:**
   - Make elements editable
   - Click to edit inline
   - Save changes

---

**Status:** ✅ Ready to Use  
**Last Updated:** 2024-12-19

