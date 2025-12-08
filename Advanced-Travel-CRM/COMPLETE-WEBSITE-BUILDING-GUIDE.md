# 🚀 Complete Website Building Guide
## Multi-Service Travel CRM - Step-by-Step Implementation

---

## 📋 Table of Contents

1. [Overview & Architecture](#overview--architecture)
2. [Homepage Setup](#homepage-setup)
3. [Service Landing Pages](#service-landing-pages)
4. [Package Management](#package-management)
5. [Shortcodes Reference](#shortcodes-reference)
6. [Branding & Theming](#branding--theming)
7. [Step-by-Step Implementation](#step-by-step-implementation)

---

## 🏗️ Overview & Architecture

### **Your Multi-Service Ecosystem:**

Your website supports multiple travel services, each with its own:
- ✅ Dedicated landing page
- ✅ Service-specific branding & colors
- ✅ Customized search system
- ✅ Premium package details page
- ✅ Booking system
- ✅ Query system

### **Available Services:**
1. **Tours** - Travel packages, itineraries, destinations
2. **Forex** - Currency exchange services
3. **Visa** - Visa application services
4. **Hotels** - Hotel bookings
5. **Flights** - Flight bookings
6. **Trains** - Train bookings
7. **Cars** - Car rental services

---

## 🏠 Homepage Setup

### **Step 1: Create Homepage**

1. Go to **WordPress Admin → Pages → Add New**
2. Title: `Home` or `Welcome to [Your Company Name]`
3. Set as **Homepage**: Settings → Reading → Homepage displays → Static page → Select your homepage

### **Step 2: Homepage Structure**

Your homepage should include:

#### **Section 1: Hero Section with Branding**
```
[Your Company Logo]
[Tagline: "Your Premium Travel Partner"]
[Search Bar for All Services]
```

#### **Section 2: Services Grid**
Display all available services with icons and links

#### **Section 3: Featured Packages**
Show special packages from different services

#### **Section 4: Why Choose Us / Features**
Branding and value propositions

#### **Section 5: Testimonials / Reviews**
Social proof

#### **Section 6: Call-to-Action**
Contact form or booking button

---

## 📄 Service Landing Pages

### **Step 1: Create Landing Page for Each Service**

For each service (Tours, Forex, Visa, Hotels, Flights, Trains, Cars):

1. Go to **WordPress Admin → Pages → Add New**
2. Title: `[Service Name]` (e.g., "Tours", "Hotels", "Forex")
3. Slug: `[service-name]` (e.g., `/tours`, `/hotels`, `/forex`)

### **Step 2: Landing Page Structure**

Each service landing page should include:

#### **Section 1: Service Hero Section**
- Service-specific branding
- Service icon and colors
- Search widget for that service
- Brief description

#### **Section 2: Service Categories**
- Display packages by category
- Category filters
- Featured packages

#### **Section 3: Special Packages**
- Highlighted packages
- Package groups/collections

#### **Section 4: Service Features**
- Why choose this service
- Service-specific benefits

#### **Section 5: Call-to-Action**
- Query form
- Contact information

---

## 📦 Package Management

### **Step 1: Create Packages**

1. Go to **ATC Dashboard → Custom Packages → Add New**
2. Fill in package details:
   - **Basic Info**: Name, description, destination, duration
   - **Service**: Select service (Tours, Hotels, etc.)
   - **Category**: Select category (for Tours: Honeymoon, Adventure, etc.)
   - **Images**: Main image + gallery images
   - **Pricing**: Price, original price, adults, children
   - **Itinerary**: Day-wise itinerary (for Tours)
   - **Details**: Highlights, inclusions, exclusions
   - **Status**: Active/Inactive
   - **Featured**: Mark as featured

3. Click **"Add Package"**

### **Step 2: Organize Packages**

#### **By Category:**
- Tours: Honeymoon, Adventure, Family, Luxury, Budget, Beach, Hill Station, Wildlife, Cultural, Spiritual, Weekend Getaway, International, Domestic, Jungle Safari
- Hotels: Budget, Luxury, Business, Resort, Boutique
- Flights: Domestic, International, Business, Economy
- Trains: Express, Superfast, Luxury
- Cars: Out of Station, In Station, Luxury, Economy

#### **By Featured Status:**
- Mark special packages as "Featured"
- These will appear in homepage and service landing pages

#### **By Package Groups:**
- Create custom collections (e.g., "Summer Specials", "Weekend Getaways")
- Group related packages together

---

## 🎨 Shortcodes Reference

### **1. Premium Search Widget**

**Usage:**
```
[atc_premium_search service="tours"]
[atc_premium_search service="hotels"]
[atc_premium_search service="forex"]
[atc_premium_search service="visa"]
[atc_premium_search service="flights"]
[atc_premium_search service="trains"]
[atc_premium_search service="cars"]
```

**Parameters:**
- `service` - Service key (tours, hotels, forex, visa, flights, trains, cars)
- `title` - Custom title (optional)
- `show_filters` - Show/hide filters (default: true)

**Where to Use:**
- Homepage (for all services or specific service)
- Service landing pages (service-specific search)

---

### **2. Packages by Category**

**Usage:**
```
[atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="12"]
[atc_packages_by_category category="Luxury" service="hotels" columns="4" per_page="8"]
```

**Parameters:**
- `category` - Category name (required)
- `service` - Service key (required)
- `columns` - Number of columns (default: 3)
- `per_page` - Packages per page (default: 12)
- `title` - Section title (optional)

**Where to Use:**
- Service landing pages (show packages by category)
- Category pages
- Homepage (featured categories)

---

### **3. Featured Packages**

**Usage:**
```
[atc_featured_packages service="tours" columns="4" per_page="8"]
[atc_featured_packages service="hotels" columns="3" per_page="6"]
```

**Parameters:**
- `service` - Service key (optional, leave empty for all services)
- `columns` - Number of columns (default: 3)
- `per_page` - Packages per page (default: 12)
- `title` - Section title (optional)

**Where to Use:**
- Homepage (show featured packages from all services)
- Service landing pages (show featured packages for that service)

---

### **4. Package Details**

**Usage:**
```
[atc_package_details package_id="123"]
[atc_package_details_tours package_id="123"]
[atc_package_details_hotels package_id="456"]
```

**Parameters:**
- `package_id` - Package ID (required)

**Where to Use:**
- Dedicated package detail pages
- Automatically used when clicking "View Details" from search results

---

### **5. Query Form**

**Usage:**
```
[atc_query_form service="tours" button_text="Ask for More Details"]
[atc_query_form service="hotels" button_text="Inquire Now"]
```

**Parameters:**
- `service` - Service key (required)
- `button_text` - Button text (optional, default: "Submit Query")

**Where to Use:**
- Package detail pages
- Service landing pages
- Contact pages

---

### **6. Package Groups**

**Usage:**
```
[atc_package_group group_slug="summer-specials" service="tours"]
```

**Parameters:**
- `group_slug` - Package group slug (required)
- `service` - Service key (optional)

**Where to Use:**
- Service landing pages
- Special collection pages

---

## 🎨 Branding & Theming

### **Service-Specific Colors:**

Each service has its own color scheme:

- **Tours**: Blue gradient (like login/query form)
- **Forex**: Gold/Yellow gradient
- **Visa**: Green gradient
- **Hotels**: Purple gradient
- **Flights**: Sky blue gradient
- **Trains**: Red gradient (IRCTC style)
- **Cars**: Dark gradient

### **Customizing Colors:**

Colors are defined in service JSON files:
- `services/tours.json`
- `services/forex.json`
- `services/visa.json`
- etc.

Edit the `theme.color` field in each JSON file to change service colors.

---

## 📝 Step-by-Step Implementation

### **Phase 1: Homepage Setup**

#### **Step 1.1: Create Homepage**

1. **WordPress Admin → Pages → Add New**
2. Title: `Home`
3. Slug: `home` or leave empty for homepage
4. **Settings → Reading → Homepage displays → Static page → Select "Home"**

#### **Step 1.2: Add Hero Section**

```html
<div class="atc-homepage-hero">
    <h1>Welcome to [Your Company Name]</h1>
    <p>Your Premium Travel Partner</p>
    
    <!-- Universal Search or Service-Specific Search -->
    [atc_premium_search service="tours"]
</div>
```

#### **Step 1.3: Add Services Grid**

```html
<div class="atc-services-grid">
    <h2>Our Services</h2>
    
    <div class="atc-service-card">
        <h3>🏖️ Tours</h3>
        <p>Explore amazing destinations</p>
        <a href="/tours" class="button">View Tours</a>
    </div>
    
    <div class="atc-service-card">
        <h3>🏨 Hotels</h3>
        <p>Book your perfect stay</p>
        <a href="/hotels" class="button">View Hotels</a>
    </div>
    
    <!-- Repeat for all services -->
</div>
```

#### **Step 1.4: Add Featured Packages**

```html
<div class="atc-featured-packages">
    <h2>Featured Packages</h2>
    
    <!-- Featured Tours -->
    [atc_featured_packages service="tours" columns="4" per_page="4" title="Featured Tours"]
    
    <!-- Featured Hotels -->
    [atc_featured_packages service="hotels" columns="4" per_page="4" title="Featured Hotels"]
    
    <!-- Featured Flights -->
    [atc_featured_packages service="flights" columns="4" per_page="4" title="Featured Flights"]
</div>
```

#### **Step 1.5: Add Call-to-Action**

```html
<div class="atc-cta-section">
    <h2>Need Help? Contact Us</h2>
    [atc_query_form service="tours" button_text="Get in Touch"]
</div>
```

---

### **Phase 2: Service Landing Pages**

#### **Step 2.1: Create Tours Landing Page**

1. **WordPress Admin → Pages → Add New**
2. Title: `Tours`
3. Slug: `tours`
4. Add content:

```html
<!-- Tours Hero Section -->
<div class="atc-service-hero atc-tours-hero">
    <h1>🏖️ Tours & Travel Packages</h1>
    <p>Discover amazing destinations with our curated tour packages</p>
    
    <!-- Tours Search -->
    [atc_premium_search service="tours"]
</div>

<!-- Featured Tours -->
<div class="atc-service-featured">
    <h2>Featured Tours</h2>
    [atc_featured_packages service="tours" columns="3" per_page="6"]
</div>

<!-- Tours by Category -->
<div class="atc-service-categories">
    <h2>Explore by Category</h2>
    
    <!-- Honeymoon Packages -->
    [atc_packages_by_category category="Honeymoon" service="tours" columns="3" per_page="6" title="Honeymoon Packages"]
    
    <!-- Adventure Packages -->
    [atc_packages_by_category category="Adventure" service="tours" columns="3" per_page="6" title="Adventure Tours"]
    
    <!-- International Packages -->
    [atc_packages_by_category category="International" service="tours" columns="3" per_page="6" title="International Tours"]
    
    <!-- Domestic Packages -->
    [atc_packages_by_category category="Domestic" service="tours" columns="3" per_page="6" title="Domestic Tours"]
    
    <!-- Jungle Safari -->
    [atc_packages_by_category category="Jungle Safari" service="tours" columns="3" per_page="6" title="Jungle Safari Packages"]
</div>

<!-- Query Form -->
<div class="atc-service-query">
    <h2>Have Questions?</h2>
    [atc_query_form service="tours" button_text="Ask for More Details"]
</div>
```

#### **Step 2.2: Create Hotels Landing Page**

1. **WordPress Admin → Pages → Add New**
2. Title: `Hotels`
3. Slug: `hotels`
4. Add content:

```html
<!-- Hotels Hero Section -->
<div class="atc-service-hero atc-hotels-hero">
    <h1>🏨 Hotel Bookings</h1>
    <p>Find your perfect stay anywhere in the world</p>
    
    <!-- Hotels Search -->
    [atc_premium_search service="hotels"]
</div>

<!-- Featured Hotels -->
<div class="atc-service-featured">
    <h2>Featured Hotels</h2>
    [atc_featured_packages service="hotels" columns="4" per_page="8"]
</div>

<!-- Hotels by Category -->
<div class="atc-service-categories">
    <h2>Hotels by Type</h2>
    
    <!-- Luxury Hotels -->
    [atc_packages_by_category category="Luxury" service="hotels" columns="4" per_page="8" title="Luxury Hotels"]
    
    <!-- Budget Hotels -->
    [atc_packages_by_category category="Budget" service="hotels" columns="4" per_page="8" title="Budget Hotels"]
</div>

<!-- Query Form -->
<div class="atc-service-query">
    <h2>Need Help Booking?</h2>
    [atc_query_form service="hotels" button_text="Inquire Now"]
</div>
```

#### **Step 2.3: Create Other Service Landing Pages**

Repeat the same process for:
- **Forex** (`/forex`)
- **Visa** (`/visa`)
- **Flights** (`/flights`)
- **Trains** (`/trains`)
- **Cars** (`/cars`)

Use the same structure but with service-specific:
- Search widget: `[atc_premium_search service="[service]"]`
- Featured packages: `[atc_featured_packages service="[service]"]`
- Query form: `[atc_query_form service="[service]"]`

---

### **Phase 3: Package Detail Pages**

#### **Step 3.1: Create Package Detail Template**

1. **WordPress Admin → Pages → Add New**
2. Title: `Package Details` (or use a template)
3. Slug: `package-details`
4. Add shortcode:

```html
[atc_package_details package_id=""]
```

**Note:** The package ID will be passed via URL parameter automatically when users click "View Details" from search results.

#### **Step 3.2: Configure Permalink**

The package details page will automatically show the correct package based on URL:
- `/package-details/?package_id=123`
- Or use custom permalink structure

---

### **Phase 4: Navigation Menu**

#### **Step 4.1: Create Main Menu**

1. **WordPress Admin → Appearance → Menus**
2. Create new menu: `Main Menu`
3. Add pages:
   - Home
   - Tours
   - Hotels
   - Flights
   - Trains
   - Cars
   - Forex
   - Visa
   - Contact
4. Assign to **Primary Menu** location

#### **Step 4.2: Add Service Dropdowns**

Create dropdown menus for each service:
- Tours → Categories (Honeymoon, Adventure, etc.)
- Hotels → Types (Luxury, Budget, etc.)

---

### **Phase 5: Package Creation & Organization**

#### **Step 5.1: Create Packages**

1. **ATC Dashboard → Custom Packages → Add New**
2. For each service, create packages:
   - **Tours**: Create packages for each category
   - **Hotels**: Create hotel listings
   - **Flights**: Create flight packages
   - etc.

#### **Step 5.2: Mark Featured Packages**

- Select "Featured" checkbox for special packages
- These will appear in homepage and service landing pages

#### **Step 5.3: Organize by Categories**

- Assign appropriate categories to packages
- Use categories to display packages on landing pages

---

### **Phase 6: Styling & Branding**

#### **Step 6.1: Add Custom CSS**

1. **WordPress Admin → Appearance → Customize → Additional CSS**
2. Add service-specific styling:

```css
/* Tours Service */
.atc-tours-hero {
    background: linear-gradient(135deg, #0A1F44 0%, #1E3A5F 100%);
    color: white;
    padding: 60px 20px;
    text-align: center;
}

/* Hotels Service */
.atc-hotels-hero {
    background: linear-gradient(135deg, #6B46C1 0%, #9333EA 100%);
    color: white;
    padding: 60px 20px;
    text-align: center;
}

/* Services Grid */
.atc-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 40px 20px;
}

.atc-service-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s;
}

.atc-service-card:hover {
    transform: translateY(-5px);
}
```

#### **Step 6.2: Customize Service Colors**

Edit service JSON files to change colors:
- `services/tours.json` → `theme.color`
- `services/hotels.json` → `theme.color`
- etc.

---

## 🎯 Complete Homepage Template

```html
<!-- Hero Section -->
<div class="atc-homepage-hero">
    <div class="atc-hero-content">
        <h1>Welcome to [Your Company Name]</h1>
        <p class="atc-tagline">Your Premium Travel Partner</p>
        
        <!-- Universal Search -->
        <div class="atc-hero-search">
            [atc_premium_search service="tours"]
        </div>
    </div>
</div>

<!-- Services Grid -->
<div class="atc-services-section">
    <div class="atc-container">
        <h2 class="atc-section-title">Our Services</h2>
        <div class="atc-services-grid">
            <!-- Service Cards -->
            <div class="atc-service-card">
                <div class="atc-service-icon">🏖️</div>
                <h3>Tours</h3>
                <p>Explore amazing destinations</p>
                <a href="/tours" class="button button-primary">View Tours</a>
            </div>
            
            <div class="atc-service-card">
                <div class="atc-service-icon">🏨</div>
                <h3>Hotels</h3>
                <p>Book your perfect stay</p>
                <a href="/hotels" class="button button-primary">View Hotels</a>
            </div>
            
            <div class="atc-service-card">
                <div class="atc-service-icon">✈️</div>
                <h3>Flights</h3>
                <p>Book flights worldwide</p>
                <a href="/flights" class="button button-primary">View Flights</a>
            </div>
            
            <div class="atc-service-card">
                <div class="atc-service-icon">🚂</div>
                <h3>Trains</h3>
                <p>Train bookings made easy</p>
                <a href="/trains" class="button button-primary">View Trains</a>
            </div>
            
            <div class="atc-service-card">
                <div class="atc-service-icon">🚗</div>
                <h3>Car Rentals</h3>
                <p>Rent a car for your journey</p>
                <a href="/cars" class="button button-primary">View Cars</a>
            </div>
            
            <div class="atc-service-card">
                <div class="atc-service-icon">💱</div>
                <h3>Forex</h3>
                <p>Currency exchange services</p>
                <a href="/forex" class="button button-primary">View Forex</a>
            </div>
            
            <div class="atc-service-card">
                <div class="atc-service-icon">📋</div>
                <h3>Visa</h3>
                <p>Visa application services</p>
                <a href="/visa" class="button button-primary">View Visa</a>
            </div>
        </div>
    </div>
</div>

<!-- Featured Packages Section -->
<div class="atc-featured-section">
    <div class="atc-container">
        <h2 class="atc-section-title">Featured Packages</h2>
        
        <!-- Featured Tours -->
        <div class="atc-featured-service">
            <h3>🏖️ Featured Tours</h3>
            [atc_featured_packages service="tours" columns="4" per_page="4"]
        </div>
        
        <!-- Featured Hotels -->
        <div class="atc-featured-service">
            <h3>🏨 Featured Hotels</h3>
            [atc_featured_packages service="hotels" columns="4" per_page="4"]
        </div>
        
        <!-- Featured Flights -->
        <div class="atc-featured-service">
            <h3>✈️ Featured Flights</h3>
            [atc_featured_packages service="flights" columns="4" per_page="4"]
        </div>
    </div>
</div>

<!-- Why Choose Us -->
<div class="atc-features-section">
    <div class="atc-container">
        <h2 class="atc-section-title">Why Choose Us?</h2>
        <div class="atc-features-grid">
            <div class="atc-feature-item">
                <div class="atc-feature-icon">✅</div>
                <h4>Best Prices</h4>
                <p>Competitive pricing for all services</p>
            </div>
            <div class="atc-feature-item">
                <div class="atc-feature-icon">🛡️</div>
                <h4>Secure Booking</h4>
                <p>Safe and secure payment processing</p>
            </div>
            <div class="atc-feature-item">
                <div class="atc-feature-icon">📞</div>
                <h4>24/7 Support</h4>
                <p>Round-the-clock customer support</p>
            </div>
            <div class="atc-feature-item">
                <div class="atc-feature-icon">⭐</div>
                <h4>Premium Service</h4>
                <p>Top-notch service quality</p>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="atc-cta-section">
    <div class="atc-container">
        <h2>Need Help? Contact Us</h2>
        <p>Our travel experts are here to help you plan your perfect trip</p>
        [atc_query_form service="tours" button_text="Get in Touch"]
    </div>
</div>
```

---

## 🎨 Service Landing Page Template

```html
<!-- Service Hero -->
<div class="atc-service-hero atc-[service]-hero">
    <div class="atc-hero-content">
        <h1>[Service Icon] [Service Name]</h1>
        <p>[Service Description]</p>
        
        <!-- Service-Specific Search -->
        [atc_premium_search service="[service]"]
    </div>
</div>

<!-- Featured Packages -->
<div class="atc-service-featured">
    <div class="atc-container">
        <h2>Featured [Service Name]</h2>
        [atc_featured_packages service="[service]" columns="3" per_page="6"]
    </div>
</div>

<!-- Packages by Category -->
<div class="atc-service-categories">
    <div class="atc-container">
        <h2>Explore by Category</h2>
        
        <!-- Category 1 -->
        [atc_packages_by_category category="[Category 1]" service="[service]" columns="3" per_page="6" title="[Category 1] Packages"]
        
        <!-- Category 2 -->
        [atc_packages_by_category category="[Category 2]" service="[service]" columns="3" per_page="6" title="[Category 2] Packages"]
        
        <!-- Add more categories as needed -->
    </div>
</div>

<!-- Service Features -->
<div class="atc-service-features">
    <div class="atc-container">
        <h2>Why Choose Our [Service Name]?</h2>
        <div class="atc-features-list">
            <!-- Add service-specific features -->
        </div>
    </div>
</div>

<!-- Query Form -->
<div class="atc-service-query">
    <div class="atc-container">
        <h2>Have Questions?</h2>
        [atc_query_form service="[service]" button_text="Ask for More Details"]
    </div>
</div>
```

---

## 📱 Responsive Design Tips

1. **Mobile-First**: All shortcodes are mobile-responsive
2. **Grid Layouts**: Use CSS Grid for service cards
3. **Flexible Columns**: Shortcodes automatically adjust columns based on screen size
4. **Touch-Friendly**: Buttons and links are optimized for mobile

---

## ✅ Checklist

### **Homepage:**
- [ ] Hero section with branding
- [ ] Services grid
- [ ] Featured packages from multiple services
- [ ] Why choose us section
- [ ] Call-to-action with query form

### **Service Landing Pages:**
- [ ] Service hero with search
- [ ] Featured packages for that service
- [ ] Packages by category
- [ ] Service features
- [ ] Query form

### **Package Management:**
- [ ] Create packages for each service
- [ ] Assign categories
- [ ] Mark featured packages
- [ ] Add images and descriptions
- [ ] Set pricing

### **Navigation:**
- [ ] Main menu with all services
- [ ] Service dropdowns with categories
- [ ] Footer links

### **Branding:**
- [ ] Customize service colors
- [ ] Add company logo
- [ ] Custom CSS styling
- [ ] Service-specific themes

---

## 🎯 Quick Start Commands

### **Create Homepage:**
1. Pages → Add New → Title: "Home"
2. Paste homepage template
3. Settings → Reading → Set as homepage

### **Create Service Landing Page:**
1. Pages → Add New → Title: "[Service Name]"
2. Paste service landing page template
3. Replace `[service]` with actual service key

### **Add Packages:**
1. ATC Dashboard → Custom Packages → Add New
2. Fill in details
3. Select service and category
4. Mark as featured if needed

---

## 📞 Support

For questions or issues:
1. Check shortcode documentation
2. Review service JSON configurations
3. Check WordPress admin for package management
4. Review notification settings

---

## 🚀 Next Steps

1. **Create Homepage** using the template above
2. **Create Service Landing Pages** for each service
3. **Add Packages** for each service
4. **Customize Branding** with your colors and logo
5. **Test Everything** - search, booking, queries
6. **Go Live!** 🎉

---

**Happy Building! 🎉**

