# 🏠 Homepage & Landing Pages Setup Guide

**Status:** ✅ Implementation Complete  
**Date:** 2024-12-19

---

## ✅ What's Been Built

### **1. Color Scheme (Gold Background + Blue Accents)**
- ✅ Gold backgrounds (#D4AF37, #F4D03F)
- ✅ Blue buttons and accents (#1E3A5F, #0A1F44)
- ✅ Consistent across all pages

### **2. Homepage Components**
- ✅ `[atc_homepage_hero]` - Hero section with search
- ✅ `[atc_homepage_services]` - Services grid
- ✅ `[atc_homepage_featured_packages]` - Featured packages
- ✅ `[atc_homepage_why_choose_us]` - Why choose us section
- ✅ `[atc_homepage_testimonials]` - Testimonials
- ✅ `[atc_homepage_statistics]` - Statistics counter
- ✅ `[atc_homepage_cta]` - Call-to-action section

### **3. Service Landing Pages**
- ✅ `[atc_tours_landing]` - Tours landing page
- ✅ `[atc_hotels_landing]` - Hotels landing page
- ✅ `[atc_flights_landing]` - Flights landing page
- ✅ `[atc_trains_landing]` - Trains landing page
- ✅ `[atc_cars_landing]` - Car rentals landing page
- ✅ `[atc_forex_landing]` - Forex services landing page
- ✅ `[atc_visa_landing]` - Visa services landing page
- ❌ Safari - Not included (not in use)

### **4. Celebrity Landing Page**
- ✅ Updated with new color scheme
- ✅ Gold background + blue accents

---

## 🚀 How to Use

### **Step 1: Create Homepage**

1. Go to **WordPress Admin → Pages → Add New**
2. Title: `Home` or your homepage title
3. Add these shortcodes in order:

```html
<!-- Hero Section -->
[atc_homepage_hero 
    title="Your Premium Travel Partner"
    subtitle="Experience Luxury Travel with Expert Service"
    show_search="true"
]

<!-- Services Grid -->
[atc_homepage_services 
    title="Our Services"
    subtitle="Comprehensive travel solutions for all your needs"
    columns="3"
]

<!-- Featured Tours -->
[atc_homepage_featured_packages
    service="tours"
    title="⭐ Featured Tours"
    columns="4"
    per_page="8"
]

<!-- Featured Hotels -->
[atc_homepage_featured_packages
    service="hotels"
    title="🏨 Featured Hotels"
    columns="4"
    per_page="8"
]

<!-- Why Choose Us -->
[atc_homepage_why_choose_us
    title="Why Choose Us"
    columns="4"
]

<!-- Testimonials -->
[atc_homepage_testimonials
    title="What Our Customers Say"
    per_page="6"
]

<!-- Statistics -->
[atc_homepage_statistics
    customers="10000+"
    packages="500+"
    destinations="50+"
    experience="10+"
]

<!-- CTA Section -->
[atc_homepage_cta
    title="Ready to Plan Your Trip?"
    subtitle="Contact us today for personalized travel solutions"
    show_form="true"
]
```

4. **Set as Homepage:** Settings → Reading → Homepage displays → Static page → Select your homepage
5. Publish

### **Step 2: Create Service Landing Pages**

For each service, create a page:

#### **Tours Landing Page**
1. Create page: "Tours" or "Tour Packages"
2. Add shortcode: `[atc_tours_landing show_search="true" show_packages="true"]`
3. Set permalink: `/tours/` or `/tour-packages/`

#### **Hotels Landing Page**
1. Create page: "Hotels"
2. Add shortcode: `[atc_hotels_landing show_search="true" show_packages="true"]`
3. Set permalink: `/hotels/`

#### **Flights Landing Page**
1. Create page: "Flights"
2. Add shortcode: `[atc_flights_landing show_search="true" show_packages="true"]`
3. Set permalink: `/flights/`

#### **Trains Landing Page**
1. Create page: "Trains"
2. Add shortcode: `[atc_trains_landing show_search="true" show_packages="true"]`
3. Set permalink: `/trains/`

#### **Cars Landing Page**
1. Create page: "Car Rentals"
2. Add shortcode: `[atc_cars_landing show_search="true" show_packages="true"]`
3. Set permalink: `/car-rentals/`

#### **Forex Landing Page**
1. Create page: "Forex Services"
2. Add shortcode: `[atc_forex_landing show_search="true" show_packages="true"]`
3. Set permalink: `/forex/`

#### **Visa Landing Page**
1. Create page: "Visa Services"
2. Add shortcode: `[atc_visa_landing show_search="true" show_packages="true"]`
3. Set permalink: `/visa/`

### **Step 3: Celebrity Landing Page**

The celebrity landing page is already set up. Just ensure:
1. Page exists with slug: `celebrity-management`
2. Contains shortcode: `[atc_celebrity_landing]`
3. Gold background and blue accent styles are automatically applied

---

## 🎨 Customization

### **Color Customization**

All colors are defined in CSS variables. To customize:

1. Edit `assets/css/atc-global-premium.css`
2. Modify the `:root` variables:

```css
:root {
    --atc-primary: #1E3A5F;        /* Dark Sky Blue */
    --atc-secondary: #D4AF37;       /* Gold */
    --atc-secondary-light: #F4D03F; /* Light Gold */
}
```

### **Text Customization**

All text can be customized via shortcode parameters:

```html
[atc_homepage_hero 
    title="Your Custom Title"
    subtitle="Your Custom Subtitle"
]
```

## 📋 Shortcode Reference

### **Homepage Shortcodes**

| Shortcode | Parameters | Description |
|-----------|------------|-------------|
| `[atc_homepage_hero]` | title, subtitle, show_search, background_image | Hero section |
| `[atc_homepage_services]` | title, subtitle, columns | Services grid |
| `[atc_homepage_featured_packages]` | service, title, columns, per_page | Featured packages |
| `[atc_homepage_why_choose_us]` | title, columns | Why choose us |
| `[atc_homepage_testimonials]` | title, per_page | Testimonials |
| `[atc_homepage_statistics]` | customers, packages, destinations, experience | Statistics |
| `[atc_homepage_cta]` | title, subtitle, show_form | Call-to-action |

### **Landing Page Shortcodes**

| Shortcode | Parameters | Description |
|-----------|------------|-------------|
| `[atc_tours_landing]` | show_search, show_packages, show_testimonials | Tours landing |
| `[atc_hotels_landing]` | show_search, show_packages, show_testimonials | Hotels landing |
| `[atc_flights_landing]` | show_search, show_packages, show_testimonials | Flights landing |
| `[atc_trains_landing]` | show_search, show_packages, show_testimonials | Trains landing |
| `[atc_cars_landing]` | show_search, show_packages, show_testimonials | Cars landing |
| `[atc_forex_landing]` | show_search, show_packages, show_testimonials | Forex landing |
| `[atc_visa_landing]` | show_search, show_packages, show_testimonials | Visa landing |
| `[atc_celebrity_landing]` | show_contact_form | Celebrity landing |

---

## 🎯 Features

### **✅ Gold Background Theme**
- Gold gradient backgrounds
- Blue buttons and accents
- Consistent color scheme
- Premium appearance

### **✅ Responsive Design**
- Mobile-friendly
- Tablet optimized
- Desktop enhanced
- All breakpoints covered

### **✅ Premium Design**
- Modern layouts
- Smooth animations
- Professional typography
- Elegant styling

---

## 🔧 Technical Details

### **Files Created**

**CSS Files:**
- `assets/css/atc-global-premium.css` - Global styles
- `assets/css/atc-homepage.css` - Homepage styles
- `assets/css/atc-landing-pages.css` - Landing page styles
- `assets/css/atc-celebrity-landing.css` - Updated celebrity styles

**PHP Classes:**
- `includes/homepage/class-atc-homepage-hero.php`
- `includes/homepage/class-atc-homepage-sections.php`
- `includes/landing-pages/class-atc-base-landing.php`
- `includes/landing-pages/class-atc-service-landings.php`

### **Dependencies**

All classes are automatically loaded by the main plugin file. No manual includes needed.

---

## 📝 Notes

1. **Safari Service:** Not included as it's not in use
2. **Color Scheme:** Gold backgrounds with blue accents throughout
3. **Responsive:** All components are mobile-friendly
4. **Performance:** Optimized CSS and JavaScript

---

## 🚀 Next Steps

1. Create your homepage using the shortcodes above
2. Create landing pages for each service
3. Customize colors and text as needed
4. Test on different devices
5. Add your own content and images

---

**Status:** ✅ Ready to Use  
**Last Updated:** 2024-12-19

