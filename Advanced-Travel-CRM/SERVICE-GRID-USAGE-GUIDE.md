# 🎨 Service Grid Usage Guide

**Date:** 2024-12-19  
**Status:** ✅ Ready to Use

---

## 📋 Overview

A premium, modern service grid with gold and navy blue theme that displays all your travel services with icons. Fully responsive with horizontal scrolling on mobile/tablet.

---

## 🚀 Two Ways to Use

### **Option 1: Shortcode (Recommended)**
Use the plugin shortcode - easiest and fully integrated.

### **Option 2: CSS Class (Elementor)**
Add CSS class to your existing Elementor service grid.

---

## 📝 Shortcode Usage

### **Basic Usage:**
```
[atc_service_grid]
```

### **With Custom Title & Description:**
```
[atc_service_grid title="Our Services" description="Choose from our wide range of travel services"]
```

### **With Descriptions Shown:**
```
[atc_service_grid show_description="true"]
```

### **All Options:**
```
[atc_service_grid 
    title="Our Services" 
    description="Explore amazing destinations" 
    show_description="true" 
    columns="auto" 
    class="custom-class"]
```

### **Shortcode Parameters:**
- `title` - Grid title (default: "Our Services")
- `description` - Subtitle/description (default: "Explore our wide range of travel services")
- `show_description` - Show service descriptions (default: "false")
- `columns` - Number of columns on desktop: auto, 2, 3, 4, 5, 6, 7 (default: "auto")
- `class` - Additional CSS classes

---

## 🎨 CSS Class Usage (Elementor)

If you're using Elementor and want to style your existing service grid:

1. **Add CSS Class:**
   - In Elementor, select your service grid container
   - Go to Advanced → CSS Classes
   - Add: `atc-service-grid`

2. **Structure Your HTML:**
   ```html
   <div class="atc-service-grid">
       <div class="atc-service-grid-header">
           <h2>Our Services</h2>
           <p>Explore our wide range of travel services</p>
       </div>
       <div class="atc-service-grid-wrapper">
           <a href="/tours/" class="atc-service-card">
               <div class="atc-service-icon">🏖️</div>
               <h3 class="atc-service-name">Tours</h3>
           </a>
           <!-- Repeat for other services -->
       </div>
   </div>
   ```

3. **Required Classes:**
   - `.atc-service-grid` - Main container
   - `.atc-service-grid-wrapper` - Scrollable wrapper
   - `.atc-service-card` - Individual service card
   - `.atc-service-icon` - Service icon
   - `.atc-service-icon` - Service name

---

## 🎯 Features

### **✅ Automatic Features:**
- ✅ **Auto-detects all enabled services** from your plugin
- ✅ **Auto-links to service landing pages** (finds pages by slug)
- ✅ **Service icons** (emoji or icon names)
- ✅ **Responsive design** (desktop grid, mobile scrollable)
- ✅ **Gold & Navy Blue theme** matching your website
- ✅ **Smooth animations** and hover effects
- ✅ **Accessibility** (keyboard navigation, focus states)

### **✅ Responsive Behavior:**
- **Desktop (1025px+):** Full grid layout (auto-fit columns)
- **Tablet (769px-1024px):** Horizontal scrollable with larger cards
- **Mobile (≤768px):** Compact horizontal scrollable
- **Small Mobile (≤480px):** Even more compact

### **✅ Visual Effects:**
- Gold gradient border on hover
- Icon scale and rotation on hover
- Smooth card lift animation
- Gold accent line animation
- Shadow effects
- Staggered fade-in animations

---

## 🎨 Design Details

### **Color Scheme:**
- **Navy Blue:** `#0A1F44` (primary text, borders)
- **Gold:** `#D4AF37` (accents, hover effects)
- **Gradient:** Navy Blue → Gold → Navy Blue

### **Card Design:**
- White background with subtle shadow
- Rounded corners (20px)
- Gold border on hover
- Icon with drop shadow
- Service name in bold navy blue
- Optional description text

### **Scrollable Design:**
- Smooth horizontal scrolling
- Custom scrollbar (gold & navy)
- Scroll hint gradient on mobile
- Touch-friendly on mobile/tablet

---

## 📱 Mobile Optimization

### **Key Features:**
- ✅ **Horizontal scrolling** - Cards scroll left/right
- ✅ **Scroll hint** - Gradient shows more content available
- ✅ **Touch-friendly** - Large touch targets
- ✅ **Compact cards** - Optimized for small screens
- ✅ **Smooth scrolling** - Native momentum scrolling

### **Card Sizes:**
- **Desktop:** Auto-fit (flexible width)
- **Tablet:** 220px wide
- **Mobile:** 180px wide
- **Small Mobile:** 160px wide

---

## 🔧 Customization

### **Change Colors:**
Edit `assets/css/atc-service-grid.css`:
```css
/* Change gold color */
#D4AF37 → Your color

/* Change navy blue */
#0A1F44 → Your color
```

### **Change Card Size:**
```css
.atc-service-card {
    width: 250px; /* Desktop */
    min-width: 250px;
}
```

### **Change Icons:**
Icons are automatically pulled from service configs. To change:
1. Go to **Travel CRM → Service Manager**
2. Edit service icon/emoji
3. Grid will update automatically

---

## 📊 Service Detection

The grid automatically:
1. ✅ Gets all enabled services from `ATC_Services::get_services()`
2. ✅ Respects service status (disabled services won't show)
3. ✅ Finds service landing pages by slug
4. ✅ Uses service icons from configs
5. ✅ Uses service labels from configs

---

## 🎯 Example Implementations

### **Homepage:**
```
[atc_service_grid title="Our Services" description="Choose your perfect travel experience"]
```

### **Services Page:**
```
[atc_service_grid title="All Services" show_description="true"
```

---

## ✅ Testing Checklist

- [ ] All 7 services display correctly
- [ ] Icons show properly
- [ ] Links work to service pages
- [ ] Desktop shows grid layout
- [ ] Tablet scrolls horizontally
- [ ] Mobile scrolls horizontally
- [ ] Hover effects work
- [ ] Gold & navy blue theme matches
- [ ] Scroll hint visible on mobile
- [ ] Touch scrolling smooth

---

## 🎉 Result

Your service grid is now:
- ✅ **Modern and premium** - Beautiful gold & navy blue design
- ✅ **Fully responsive** - Perfect on all devices
- ✅ **Horizontally scrollable** - Mobile/tablet optimized
- ✅ **Eye-catching** - Smooth animations and effects
- ✅ **Auto-integrated** - Works with your service system
- ✅ **Easy to use** - Simple shortcode or CSS class

---

**Shortcode:** `[atc_service_grid]`  
**CSS Class:** `.atc-service-grid`  
**Status:** ✅ Ready to Use

