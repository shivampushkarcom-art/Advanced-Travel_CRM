# Service Slug Standardization Guide

**Date:** 2024-12-19  
**Status:** ✅ Completed

---

## 📋 Overview

All service slugs have been standardized to **plural forms** across the entire plugin for consistency, except for `visa` and `forex` which remain as-is.

---

## ✅ Standardized Service Slugs

| Service Key | Page Slug | Status |
|-------------|-----------|--------|
| `tours` | `tours` | ✅ Plural |
| `hotels` | `hotels` | ✅ Plural |
| `flights` | `flights` | ✅ Plural |
| `trains` | `trains` | ✅ Plural |
| `cars` | `cars` | ✅ Plural |
| `forex` | `forex` | ✅ As-is (no plural) |
| `visa` | `visa` | ✅ As-is (no plural) |

---

## 🔄 Changes Made

### **1. Core Service Files**

#### `includes/class-atc-services.php`
- ✅ Updated `page_slug` from `'tour'` → `'tours'`
- ✅ Updated `page_slug` from `'hotel'` → `'hotels'`
- ✅ Updated `page_slug` from `'flight'` → `'flights'`
- ✅ Updated `page_slug` from `'train'` → `'trains'`
- ✅ Updated `page_slug` from `'car'` → `'cars'`
- ✅ Kept `'forex'` and `'visa'` as-is

#### `includes/admin/class-atc-service-manager.php`
- ✅ Updated all `page_slug` values to plural forms
- ✅ Maintains consistency with core services

#### `includes/core/class-atc-service-grid.php`
- ✅ Updated alternative slugs mapping for backward compatibility
- ✅ Now checks plural first, then falls back to singular for existing pages

---

## 🔍 Service Detection Logic

The plugin now uses **plural slugs** for service detection:

1. **URL Detection:** Checks for plural slugs in URLs (`/tours/`, `/hotels/`, etc.)
2. **Page Slug Matching:** Matches WordPress page slugs using plural forms
3. **Backward Compatibility:** Still checks singular forms as alternatives for existing pages

### Example:
```php
// Service detection checks for:
- Primary: 'tours', 'hotels', 'flights', 'trains', 'cars'
- Fallback: 'tour', 'hotel', 'flight', 'train', 'car' (for existing pages)
```

---

## 📝 Usage in Shortcodes

All shortcodes now use plural service keys:

```php
// ✅ Correct (Plural)
[atc_premium_search service="tours"]
[atc_premium_search service="hotels"]
[atc_premium_search service="flights"]
[atc_premium_search service="trains"]
[atc_premium_search service="cars"]
[atc_premium_search service="forex"]
[atc_premium_search service="visa"]

// ❌ Incorrect (Singular - will still work but not recommended)
[atc_premium_search service="tour"]
[atc_premium_search service="hotel"]
```

---

## 🔗 URL Structure

Your service landing pages should use plural slugs:

- ✅ `/tours/` - Tour Packages
- ✅ `/hotels/` - Hotels
- ✅ `/flights/` - Flights
- ✅ `/trains/` - Trains
- ✅ `/cars/` - Car Rentals
- ✅ `/forex/` - Forex Services
- ✅ `/visa/` - Visa Services

---

## 🔄 Backward Compatibility

The plugin maintains backward compatibility:

1. **Service Grid:** Checks both plural and singular page slugs
2. **Service Detection:** Falls back to singular if plural not found
3. **Existing Pages:** Works with both old (singular) and new (plural) page slugs

---

## 📊 Files Updated

1. ✅ `includes/class-atc-services.php`
2. ✅ `includes/admin/class-atc-service-manager.php`
3. ✅ `includes/core/class-atc-service-grid.php`

---

## ⚠️ Important Notes

1. **Service Keys:** Always use plural (`tours`, `hotels`, etc.) - these are the service identifiers
2. **Page Slugs:** Now use plural (`tours`, `hotels`, etc.) for new pages
3. **Visa & Forex:** Keep as singular (`visa`, `forex`) - no plural form
4. **Existing Pages:** If you have pages with singular slugs, they'll still work via fallback

---

## 🎯 Best Practices

1. **Create New Pages:** Use plural slugs (`tours`, `hotels`, etc.)
2. **Update Existing Pages:** Consider changing page slugs to plural for consistency
3. **Shortcodes:** Always use plural service keys
4. **URLs:** Use plural slugs in permalinks

---

## ✅ Verification Checklist

- [x] All core service files updated
- [x] Service manager updated
- [x] Service grid updated with backward compatibility
- [x] Service detection logic updated
- [x] Alternative slugs mapping updated
- [x] Documentation updated

---

**Status:** ✅ **All service slugs standardized to plural forms (except visa/forex)**

**Last Updated:** 2024-12-19

