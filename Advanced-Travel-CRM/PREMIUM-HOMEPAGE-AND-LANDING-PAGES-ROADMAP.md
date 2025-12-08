# 🎨 Premium Homepage & Service Landing Pages - Complete Roadmap

**Project:** Premium Website Redesign with Dark Sky Blue & Gold Theme  
**Date:** 2024-12-19  
**Status:** Planning & Implementation Roadmap  
**Reference Site:** www.vincoholidays.com

---

## 📋 Executive Summary

This roadmap outlines the complete strategy for building a **premium, customizable homepage and service landing pages** with:
- **Primary Color:** Dark Sky Blue (#1E3A5F /rgb(27, 76, 159))
- **Secondary Color:** Gold (#D4AF37 / #F4D03F)
- **Full Customization System:** Drag-and-drop, shortcode management, text editing, custom packages
- **Premium Design:** Modern, elegant, professional aesthetic

---

## 🎨 Color Palette & Design System

### **Primary Color Scheme**

| Color | Hex Code | Usage | RGB |
|-------|----------|-------|-----|
| **Dark Sky Blue (Primary)** | `#1E3A5F` | Headers, backgrounds, primary buttons | rgb(30, 58, 95) |
| **Navy Blue (Secondary)** | `#0A1F44` | Dark sections, text on gold | rgb(10, 31, 68) |
| **Gold (Accent)** | `#D4AF37` | Buttons, highlights, CTAs | rgb(212, 175, 55) |
| **Light Gold (Accent 2)** | `#F4D03F` | Hover states, secondary highlights | rgb(244, 208, 63) |
| **White** | `#FFFFFF` | Backgrounds, text on dark | rgb(255, 255, 255) |
| **Light Gray** | `#F8FAFC` | Section backgrounds | rgb(248, 250, 252) |
| **Text Gray** | `#64748B` | Body text, secondary text | rgb(100, 116, 139) |

### **Color Usage Guidelines**

1. **Dark Sky Blue (#1E3A5F)**
   - Main navigation bar
   - Hero section backgrounds
   - Section headers
   - Primary buttons (with gold text)
   - Footer backgrounds

2. **Gold (#D4AF37)**
   - Call-to-action buttons
   - Hover effects
   - Icons and highlights
   - Border accents
   - Price displays

3. **Gradient Combinations**
   - Hero sections: `linear-gradient(135deg, #1E3A5F 0%, #0A1F44 100%)`
   - Buttons: `linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%)`
   - Section backgrounds: `linear-gradient(135deg, #F8FAFC 0%, #FFFFFF 100%)`

---

## 🏗️ Architecture Overview

### **Current Status**

| Component | Status | Notes |
|-----------|--------|-------|
| **Celebrity Landing Page** | ✅ Complete | Gold & Navy theme implemented |
| **Homepage** | ❌ Not Built | Needs premium design |
| **Tours Landing** | ⚠️ Partial | Has search, needs landing page |
| **Hotels Landing** | ❌ Missing | Needs complete landing page |
| **Flights Landing** | ❌ Missing | Needs complete landing page |
| **Trains Landing** | ❌ Missing | Needs complete landing page |
| **Cars Landing** | ❌ Missing | Needs complete landing page |
| **Forex Landing** | ❌ Missing | Needs complete landing page |
| **Visa Landing** | ❌ Missing | Needs complete landing page |
| **Safari Landing** | ❌ Missing | Needs complete landing page |
| **Customization System** | ❌ Missing | Drag-and-drop builder needed |

### **Services Requiring Landing Pages**

1. ✅ **Celebrity Management** - Complete (reference template)
2. ⏳ **Tours** - Needs premium landing page
3. ⏳ **Hotels** - Needs premium landing page
4. ⏳ **Flights** - Needs premium landing page
5. ⏳ **Trains** - Needs premium landing page
6. ⏳ **Cars** - Needs premium landing page
7. ⏳ **Forex** - Needs premium landing page
8. ⏳ **Visa** - Needs premium landing page
9. ⏳ **Safari** - Needs premium landing page

---

## 🎯 Phase 1: Premium Homepage Design

### **1.1 Homepage Structure**

```
┌─────────────────────────────────────────────────┐
│  NAVIGATION BAR (Dark Sky Blue)                 │
│  - Logo                                          │
│  - Menu Items                                    │
│  - CTA Button (Gold)                            │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  HERO SECTION (Gradient: Dark Blue to Gold)     │
│  - Main Headline                                 │
│  - Subheadline                                   │
│  - Multi-Service Search Widget                    │
│  - Background Image/Video (Optional)             │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  SERVICES GRID (White Background)                │
│  - 9 Service Cards (3x3 or 4x3 grid)             │
│  - Icons/Images                                  │
│  - Service Names                                 │
│  - Hover Effects (Gold border)                  │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  FEATURED PACKAGES (Light Gray Background)       │
│  - Featured Tours                                │
│  - Featured Hotels                               │
│  - Featured Flights                              │
│  - Carousel/Slider                               │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  WHY CHOOSE US (White Background)                │
│  - 4-6 Feature Cards                             │
│  - Icons                                         │
│  - Descriptions                                  │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  TESTIMONIALS (Dark Sky Blue Background)         │
│  - Customer Reviews                              │
│  - Star Ratings                                 │
│  - Customer Photos                               │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  STATISTICS (Gold Background)                    │
│  - Happy Customers                               │
│  - Packages Sold                                 │
│  - Destinations                                  │
│  - Years of Experience                           │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  CALL-TO-ACTION (Gradient Background)            │
│  - Contact Form                                  │
│  - Phone Number                                  │
│  - Email                                         │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  FOOTER (Dark Sky Blue Background)               │
│  - Company Info                                  │
│  - Quick Links                                   │
│  - Social Media                                  │
│  - Copyright                                     │
└─────────────────────────────────────────────────┘
```

### **1.2 Homepage Shortcode Structure**

```html
<!-- Hero Section -->
[atc_homepage_hero 
    title="Your Premium Travel Partner"
    subtitle="Experience Luxury Travel with Expert Service"
    background_image=""
    show_search="true"
]

<!-- Services Grid -->
[atc_service_grid 
    columns="3" 
    layout="grid"
    show_descriptions="true"
]

<!-- Featured Packages Section -->
[atc_homepage_featured_packages
    service="tours"
    columns="4"
    per_page="8"
    title="⭐ Featured Tours"
]

[atc_homepage_featured_packages
    service="hotels"
    columns="4"
    per_page="8"
    title="🏨 Featured Hotels"
]

<!-- Why Choose Us -->
[atc_homepage_why_choose_us
    title="Why Choose Us"
    columns="4"
]

<!-- Testimonials -->
[atc_homepage_testimonials
    per_page="6"
    show_ratings="true"
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

### **1.3 Homepage Customization Features**

**Admin Panel Customization Options:**
- ✅ Edit all text content (headlines, descriptions, CTAs)
- ✅ Upload/change images (hero, backgrounds, service icons)
- ✅ Reorder sections (drag-and-drop)
- ✅ Add/remove sections
- ✅ Customize colors (override defaults)
- ✅ Add custom shortcodes anywhere
- ✅ Edit package displays
- ✅ Manage featured content

---

## 🎯 Phase 2: Service Landing Pages

### **2.1 Landing Page Template Structure**

Each service landing page will follow this structure (based on Celebrity Management template):

```
┌─────────────────────────────────────────────────┐
│  HERO SECTION                                    │
│  - Service Name                                  │
│  - Service Description                           │
│  - CTA Buttons                                   │
│  - Background (Service-specific)                 │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  SERVICE SEARCH (if applicable)                  │
│  - Service-specific search form                  │
│  - Quick filters                                 │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  FEATURED PACKAGES                               │
│  - Top packages for this service                 │
│  - Grid/List view                               │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  SERVICE FEATURES                                │
│  - What this service offers                      │
│  - Benefits                                      │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  HOW IT WORKS                                    │
│  - Step-by-step process                          │
│  - Visual guide                                  │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  TESTIMONIALS (Service-specific)                 │
│  - Reviews for this service                      │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│  CONTACT/QUERY FORM                              │
│  - Service-specific query form                   │
│  - Contact information                          │
└─────────────────────────────────────────────────┘
```

### **2.2 Landing Page Implementation Plan**

| Service | Priority | Estimated Time | Status |
|---------|----------|---------------|--------|
| **Tours** | High | 4-6 hours | ⏳ Pending |
| **Hotels** | High | 4-6 hours | ⏳ Pending |
| **Flights** | High | 4-6 hours | ⏳ Pending |
| **Trains** | Medium | 3-4 hours | ⏳ Pending |
| **Cars** | Medium | 3-4 hours | ⏳ Pending |
| **Forex** | Medium | 3-4 hours | ⏳ Pending |
| **Visa** | Medium | 3-4 hours | ⏳ Pending |
| **Safari** | Low | 3-4 hours | ⏳ Pending |

**Total Estimated Time:** 28-36 hours

### **2.3 Landing Page Shortcodes**

Each service will have its own landing page shortcode:

```html
<!-- Tours Landing Page -->
[atc_tours_landing 
    show_search="true"
    show_packages="true"
    show_testimonials="true"
]

<!-- Hotels Landing Page -->
[atc_hotels_landing 
    show_search="true"
    show_packages="true"
    show_testimonials="true"
]

<!-- Flights Landing Page -->
[atc_flights_landing 
    show_search="true"
    show_packages="true"
    show_testimonials="true"
]

<!-- And so on for other services... -->
```

---

## 🎯 Phase 3: Customization System

### **3.1 Drag-and-Drop Page Builder**

**Features:**
- ✅ Visual page builder interface
- ✅ Drag-and-drop section reordering
- ✅ Add/remove sections
- ✅ Edit section content inline
- ✅ Preview mode
- ✅ Save/restore layouts

**Technology Options:**
1. **Custom WordPress Meta Boxes** (Lightweight, integrated)
2. **Integration with Elementor/Beaver Builder** (User-friendly, but dependency)
3. **Custom React/Vue Builder** (Full control, more complex)

**Recommended:** Custom WordPress Meta Boxes with JavaScript drag-and-drop

### **3.2 Shortcode Management System**

**Features:**
- ✅ Visual shortcode picker
- ✅ Insert shortcodes anywhere on page
- ✅ Shortcode parameter editor
- ✅ Preview shortcode output
- ✅ Shortcode library/templates

**Admin Interface:**
```
┌─────────────────────────────────────────┐
│  Shortcode Manager                      │
├─────────────────────────────────────────┤
│  [Search Shortcodes]                     │
│                                          │
│  📦 Search & Booking                    │
│    • [atc_premium_search]               │
│    • [atc_premium_results]              │
│    • [atc_search_widget]                │
│                                          │
│  📋 Packages                             │
│    • [atc_featured_packages]            │
│    • [atc_packages_by_category]         │
│    • [atc_package_group]                │
│                                          │
│  🏠 Homepage Sections                   │
│    • [atc_homepage_hero]                │
│    • [atc_homepage_services]            │
│    • [atc_homepage_testimonials]        │
│                                          │
│  [Insert Selected Shortcode]             │
└─────────────────────────────────────────┘
```

### **3.3 Text Editing System**

**Features:**
- ✅ Inline text editing (click to edit)
- ✅ Rich text editor (WYSIWYG)
- ✅ Font customization
- ✅ Color customization
- ✅ Save changes instantly

**Implementation:**
- Use WordPress TinyMCE or Gutenberg blocks
- Custom meta fields for each editable text area
- AJAX save functionality

### **3.4 Custom Packages Management**

**Features:**
- ✅ Create custom package displays
- ✅ Add custom package sections
- ✅ Customize package cards
- ✅ Add custom fields to packages
- ✅ Package grouping and collections

---

## 📐 Design Specifications

### **Typography**

**Fonts:**
- **Headings:** 'Poppins', 'Montserrat', or 'Playfair Display' (Google Fonts)
- **Body:** 'Inter', 'Open Sans', or 'Roboto' (Google Fonts)
- **Fallback:** Arial, sans-serif

**Font Sizes:**
- H1: 48px (Hero titles)
- H2: 36px (Section titles)
- H3: 24px (Subsection titles)
- H4: 20px (Card titles)
- Body: 16px
- Small: 14px

**Font Weights:**
- Headings: 700 (Bold)
- Subheadings: 600 (Semi-bold)
- Body: 400 (Regular)
- Light text: 300 (Light)

### **Spacing System**

- **Section Padding:** 80px (desktop), 50px (mobile)
- **Container Max Width:** 1200px
- **Grid Gap:** 30px (desktop), 20px (mobile)
- **Card Padding:** 40px (desktop), 30px (mobile)

### **Border Radius**

- **Buttons:** 50px (pill shape)
- **Cards:** 20px
- **Images:** 12px
- **Input Fields:** 8px

### **Shadows**

- **Cards:** `0 4px 20px rgba(10, 31, 68, 0.08)`
- **Hover Cards:** `0 12px 40px rgba(212, 175, 55, 0.2)`
- **Buttons:** `0 8px 20px rgba(212, 175, 55, 0.4)`

---

## 🚀 Implementation Roadmap

### **Week 1: Foundation & Homepage**

**Day 1-2: Color System & Base Styles**
- [ ] Create global CSS variables for color scheme
- [ ] Update all existing CSS files with new colors
- [ ] Create base typography styles
- [ ] Test color consistency across all components

**Day 3-4: Homepage Hero Section**
- [ ] Create `[atc_homepage_hero]` shortcode
- [ ] Design hero section with gradient background
- [ ] Integrate multi-service search widget
- [ ] Add customization options (admin panel)

**Day 5-7: Homepage Sections**
- [ ] Services grid section
- [ ] Featured packages section
- [ ] Why choose us section
- [ ] Testimonials section
- [ ] Statistics section
- [ ] CTA section
- [ ] Footer section

### **Week 2: Service Landing Pages**

**Day 8-9: Tours Landing Page**
- [ ] Create `[atc_tours_landing]` shortcode
- [ ] Design tours-specific hero section
- [ ] Add tours search integration
- [ ] Featured tours packages section
- [ ] Tours-specific features section

**Day 10-11: Hotels Landing Page**
- [ ] Create `[atc_hotels_landing]` shortcode
- [ ] Design hotels-specific layout
- [ ] Add hotels search integration
- [ ] Featured hotels section

**Day 12-13: Flights Landing Page**
- [ ] Create `[atc_flights_landing]` shortcode
- [ ] Design flights-specific layout
- [ ] Add flights search integration

**Day 14: Remaining Services**
- [ ] Trains landing page
- [ ] Cars landing page
- [ ] Forex landing page
- [ ] Visa landing page
- [ ] Safari landing page

### **Week 3: Customization System**

**Day 15-17: Drag-and-Drop Builder**
- [ ] Create admin interface for page builder
- [ ] Implement drag-and-drop functionality
- [ ] Section reordering system
- [ ] Add/remove sections functionality

**Day 18-19: Shortcode Manager**
- [ ] Create shortcode picker interface
- [ ] Shortcode parameter editor
- [ ] Shortcode preview system
- [ ] Shortcode library/templates

**Day 20-21: Text Editing & Custom Packages**
- [ ] Inline text editing system
- [ ] Rich text editor integration
- [ ] Custom package management interface
- [ ] Package customization options

### **Week 4: Testing & Refinement**

**Day 22-24: Testing**
- [ ] Cross-browser testing
- [ ] Mobile responsiveness testing
- [ ] Performance optimization
- [ ] SEO optimization

**Day 25-28: Final Polish**
- [ ] Design refinements
- [ ] Animation enhancements
- [ ] User experience improvements
- [ ] Documentation

---

## 📁 File Structure

### **New Files to Create**

```
Advanced-Travel-CRM/
├── includes/
│   ├── homepage/
│   │   ├── class-atc-homepage-hero.php
│   │   ├── class-atc-homepage-services.php
│   │   ├── class-atc-homepage-featured.php
│   │   ├── class-atc-homepage-testimonials.php
│   │   ├── class-atc-homepage-statistics.php
│   │   └── class-atc-homepage-cta.php
│   ├── landing-pages/
│   │   ├── class-atc-tours-landing.php
│   │   ├── class-atc-hotels-landing.php
│   │   ├── class-atc-flights-landing.php
│   │   ├── class-atc-trains-landing.php
│   │   ├── class-atc-cars-landing.php
│   │   ├── class-atc-forex-landing.php
│   │   ├── class-atc-visa-landing.php
│   │   └── class-atc-safari-landing.php
│   ├── customization/
│   │   ├── class-atc-page-builder.php
│   │   ├── class-atc-shortcode-manager.php
│   │   ├── class-atc-text-editor.php
│   │   └── class-atc-custom-packages.php
│   └── admin/
│       ├── class-atc-homepage-customizer.php
│       └── class-atc-landing-page-customizer.php
├── assets/
│   ├── css/
│   │   ├── atc-homepage.css
│   │   ├── atc-tours-landing.css
│   │   ├── atc-hotels-landing.css
│   │   ├── atc-flights-landing.css
│   │   ├── atc-trains-landing.css
│   │   ├── atc-cars-landing.css
│   │   ├── atc-forex-landing.css
│   │   ├── atc-visa-landing.css
│   │   ├── atc-safari-landing.css
│   │   └── atc-customization.css
│   └── js/
│       ├── atc-page-builder.js
│       ├── atc-shortcode-manager.js
│       ├── atc-text-editor.js
│       └── atc-homepage.js
└── templates/
    ├── homepage/
    │   ├── hero-section.php
    │   ├── services-grid.php
    │   ├── featured-packages.php
    │   ├── why-choose-us.php
    │   ├── testimonials.php
    │   ├── statistics.php
    │   └── cta-section.php
    └── landing-pages/
        ├── tours-landing.php
        ├── hotels-landing.php
        ├── flights-landing.php
        └── [other services].php
```

---

## 🎨 Design Mockups & Specifications

### **Homepage Hero Section**

**Dimensions:**
- Height: 600px (desktop), 500px (tablet), 400px (mobile)
- Full width with gradient overlay

**Content:**
- Title: 48px, Bold, White
- Subtitle: 20px, Regular, White (90% opacity)
- Search Widget: Centered, max-width 800px
- CTA Buttons: Gold background, Dark text

**Background:**
- Gradient: `linear-gradient(135deg, #1E3A5F 0%, #0A1F44 100%)`
- Optional: Background image with dark overlay (60% opacity)

### **Service Cards**

**Dimensions:**
- Card Size: 300px × 250px (desktop)
- Border Radius: 20px
- Padding: 40px 30px

**Hover Effects:**
- Transform: `translateY(-8px)`
- Border: 2px solid Gold (#D4AF37)
- Shadow: `0 12px 40px rgba(212, 175, 55, 0.2)`

### **Package Cards**

**Dimensions:**
- Card Size: 280px × 400px (desktop)
- Image Height: 200px
- Border Radius: 16px

**Content:**
- Image (top)
- Title: 20px, Bold, Dark Sky Blue
- Description: 14px, Gray
- Price: 24px, Bold, Gold
- CTA Button: Gold background

---

## 🔧 Technical Implementation Details

### **Color System Implementation**

**CSS Variables (Global):**
```css
:root {
    --atc-primary: #1E3A5F;        /* Dark Sky Blue */
    --atc-primary-dark: #0A1F44;   /* Navy Blue */
    --atc-secondary: #D4AF37;      /* Gold */
    --atc-secondary-light: #F4D03F; /* Light Gold */
    --atc-white: #FFFFFF;
    --atc-light-gray: #F8FAFC;
    --atc-text-gray: #64748B;
    --atc-text-dark: #1E293B;
}
```

**Usage in Components:**
```css
.atc-button-primary {
    background: var(--atc-secondary);
    color: var(--atc-primary-dark);
    border: 2px solid var(--atc-secondary);
}

.atc-section-header {
    color: var(--atc-primary);
    background: linear-gradient(135deg, var(--atc-primary) 0%, var(--atc-secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
```

### **Responsive Breakpoints**

```css
/* Mobile */
@media (max-width: 768px) { }

/* Tablet */
@media (min-width: 769px) and (max-width: 1024px) { }

/* Desktop */
@media (min-width: 1025px) { }

/* Large Desktop */
@media (min-width: 1440px) { }
```

### **Animation Specifications**

**Transitions:**
- Default: `0.3s ease`
- Hover: `0.3s ease`
- Button clicks: `0.2s ease`

**Keyframe Animations:**
- Fade in: `fadeIn 0.5s ease-in`
- Slide up: `slideUp 0.5s ease-out`
- Scale: `scale 0.3s ease`

---

## 📊 Success Metrics

### **Design Quality**
- ✅ Consistent color usage across all pages
- ✅ Premium, professional appearance
- ✅ Modern, elegant design
- ✅ Smooth animations and transitions

### **Functionality**
- ✅ All shortcodes working correctly
- ✅ Customization system fully functional
- ✅ Drag-and-drop working smoothly
- ✅ Text editing working properly

### **Performance**
- ✅ Page load time < 3 seconds
- ✅ Mobile-friendly (responsive)
- ✅ Cross-browser compatible
- ✅ SEO optimized

### **User Experience**
- ✅ Easy to navigate
- ✅ Clear call-to-actions
- ✅ Intuitive customization interface
- ✅ Professional appearance

---

## 🎯 Next Steps

### **Immediate Actions (This Week)**

1. **Review & Approve Roadmap**
   - Review this document
   - Provide feedback on design direction
   - Approve color scheme

2. **Gather Assets**
   - Collect high-quality images for hero sections
   - Prepare service icons/images
   - Gather testimonials and reviews

3. **Set Up Development Environment**
   - Ensure WordPress is up to date
   - Backup current website
   - Set up staging environment

### **Phase 1: Homepage (Week 1)**
- Start with color system implementation
- Build homepage hero section
- Create homepage sections one by one
- Test and refine

### **Phase 2: Landing Pages (Week 2)**
- Start with Tours landing page (highest priority)
- Then Hotels and Flights
- Complete remaining services
- Test all landing pages

### **Phase 3: Customization (Week 3)**
- Build drag-and-drop system
- Create shortcode manager
- Implement text editing
- Add custom package features

### **Phase 4: Testing & Launch (Week 4)**
- Comprehensive testing
- Performance optimization
- Final design refinements
- Launch preparation

---

## 📝 Notes & Considerations

### **WordPress Compatibility**
- Ensure compatibility with current WordPress version
- Test with popular themes
- Avoid conflicts with other plugins

### **Browser Support**
- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

### **Accessibility**
- WCAG 2.1 AA compliance
- Keyboard navigation support
- Screen reader compatibility
- Color contrast ratios

### **SEO Considerations**
- Semantic HTML structure
- Proper heading hierarchy
- Alt text for images
- Meta descriptions
- Schema markup

---

## 📞 Support & Questions

If you have any questions or need clarification on any part of this roadmap, please don't hesitate to ask. This is a comprehensive plan that can be adjusted based on your priorities and feedback.

**Estimated Total Development Time:** 4-5 weeks (160-200 hours)

**Priority Order:**
1. Homepage (Week 1)
2. Tours, Hotels, Flights Landing Pages (Week 2)
3. Customization System (Week 3)
4. Remaining Services & Testing (Week 4)

---

**Last Updated:** 2024-12-19  
**Status:** Ready for Review & Approval

