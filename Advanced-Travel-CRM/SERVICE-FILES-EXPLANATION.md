# Service-Related Files Explanation

## Overview
This document explains the different service-related files in the Advanced Travel CRM plugin and their purposes.

---

## 📁 Service Files Structure

### 1. **`includes/class-atc-services.php`** ✅ Core Service Registry
**Purpose:** Main service registry and detection class

**Key Functions:**
- `register_default_services()` - Registers default 7 services (tours, hotels, flights, trains, cars, forex, visa)
- `get_services()` - Returns all enabled services (respects Service Manager status)
- `detect_service_context()` - Auto-detects current service from URL or page content
- `get_service_config()` - Loads service-specific JSON configuration

**Used By:**
- All shortcodes for service detection
- Service grid shortcode
- Search widgets
- Package details pages

**Status:** ✅ Core file - DO NOT DELETE

---

### 2. **`includes/admin/class-atc-service-manager.php`** ✅ Admin Service Management
**Purpose:** Admin interface for managing services (enable/disable, add custom services)

**Key Functions:**
- `services_page()` - Admin UI for service management
- `get_registered_services()` - Gets all services (default + custom)
- `get_services_status()` - Gets enabled/disabled status for each service
- `toggle_service_handler()` - Enable/disable services
- `add_service_handler()` - Add custom services
- `delete_service_handler()` - Remove custom services

**Features:**
- Enable/disable services
- Add custom services
- View service config file status
- Service icons and labels management

**Status:** ✅ Admin file - DO NOT DELETE

---

### 3. **`includes/core/class-atc-service-grid.php`** ✅ Service Grid Shortcode
**Purpose:** Premium service grid shortcode `[atc_service_grid]`

**Key Functions:**
- `service_grid_shortcode()` - Renders the service grid with icons
- Auto-detects all enabled services
- Auto-links to service landing pages
- Responsive horizontal scrolling on mobile

**Features:**
- Gold & navy blue theme
- Horizontal scrollable on mobile/tablet
- Auto-detects service icons and labels
- Links to service landing pages

**Status:** ✅ Frontend file - DO NOT DELETE

---

### 4. **`includes/core/class-atc-service-ecosystem.php`** ✅ Service Ecosystem Framework
**Purpose:** Service-specific theming and configuration system

**Key Functions:**
- `get_service_theme()` - Gets service-specific theme (colors, styles)
- `get_service_config()` - Loads service JSON configs
- Service-specific UI customization
- MakeMyTrip-style package details per service

**Features:**
- Service-specific color schemes
- Service-specific package detail templates
- Service-specific search forms
- Service-specific booking flows

**Status:** ✅ Core file - DO NOT DELETE

---

## 🔄 How They Work Together

```
┌─────────────────────────────────────────────────────────┐
│  ATC_Services (class-atc-services.php)                 │
│  - Core registry                                        │
│  - Service detection                                    │
│  - Default services list                                │
└─────────────────┬───────────────────────────────────────┘
                  │
        ┌─────────┴─────────┐
        │                   │
┌───────▼────────┐  ┌───────▼──────────────┐
│ Service Manager│  │ Service Ecosystem    │
│ (Admin UI)     │  │ (Theming & Config)   │
│                │  │                      │
│ - Enable/Disable│  │ - Service themes    │
│ - Add Custom    │  │ - JSON configs       │
│ - View Status   │  │ - UI customization  │
└────────────────┘  └──────────────────────┘
        │                   │
        └─────────┬─────────┘
                  │
        ┌─────────▼─────────┐
        │ Service Grid       │
        │ (Frontend)         │
        │                    │
        │ - Display services │
        │ - Icons & links    │
        │ - Responsive       │
        └────────────────────┘
```

---

## 📊 File Dependencies

### `class-atc-services.php` (Core)
- **Depends on:** `ATC_Service_Manager` (optional)
- **Used by:** All other service files

### `class-atc-service-manager.php` (Admin)
- **Depends on:** `ATC_Services` (optional fallback)
- **Uses:** Service config files in `/services/` folder

### `class-atc-service-grid.php` (Frontend)
- **Depends on:** `ATC_Services` (required)
- **Uses:** Service icons, labels, landing pages

### `class-atc-service-ecosystem.php` (Theming)
- **Depends on:** `ATC_Services` (required)
- **Uses:** Service JSON configs in `/services/` folder

---

## ✅ Summary

| File | Type | Purpose | Status |
|------|------|---------|--------|
| `class-atc-services.php` | Core | Service registry & detection | ✅ Essential |
| `class-atc-service-manager.php` | Admin | Service management UI | ✅ Essential |
| `class-atc-service-grid.php` | Frontend | Service grid shortcode | ✅ Optional (for grid) |
| `class-atc-service-ecosystem.php` | Core | Service theming & config | ✅ Essential |

**All files are needed for full functionality!**

---

**Last Updated:** 2024-12-19

