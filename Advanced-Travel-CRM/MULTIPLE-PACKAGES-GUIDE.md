# 📦 Multiple Packages Setup Guide

## 🎯 Quick Answer

**Use the SAME shortcode for ALL packages!** ✅

```
[atc_package_details]
```

The shortcode **automatically** gets the package ID from the URL, so you don't need to specify it for each package.

---

## 📋 How It Works

### **Option 1: Same Shortcode for All (Recommended)** ✅

**One Page, One Shortcode:**
```
Page: "Package Details"
Shortcode: [atc_package_details]
```

**How it works:**
- User clicks "View Details" on Package #123
- Redirects to: `/package-details/?package_id=123`
- Shortcode automatically reads `package_id=123` from URL
- Shows Package #123 details

- User clicks "View Details" on Package #456
- Redirects to: `/package-details/?package_id=456`
- Same shortcode automatically reads `package_id=456` from URL
- Shows Package #456 details

**✅ ONE PAGE, ONE SHORTCODE FOR ALL PACKAGES!**

---

### **Option 2: Shortcode with Package ID (Alternative)**

**If you want a static page for a specific package:**

```
Page: "Goa Tour Package"
Shortcode: [atc_package_details package_id="123"]
```

**When to use:**
- You want a dedicated page for a specific popular package
- You want a direct link without URL parameters
- Example: `/goa-tour-package/` (no `?package_id=123` needed)

---

## 🏗️ Recommended Setup

### **Single Package Details Page (Recommended)**

**Create ONE page:**
1. **Page Title:** "Package Details"
2. **Page Slug:** `package-details`
3. **Shortcode:** `[atc_package_details]`
4. **Publish**

**This ONE page works for ALL packages!**

**How it works:**
- Package #1: `/package-details/?package_id=1` → Shows Package #1
- Package #2: `/package-details/?package_id=2` → Shows Package #2
- Package #3: `/package-details/?package_id=3` → Shows Package #3
- ... and so on for all packages!

---

## 📝 Step-by-Step Setup

### **Step 1: Create ONE Package Details Page**

1. Go to **WordPress Admin → Pages → Add New**
2. **Page Title:** "Package Details"
3. **Page Slug:** `package-details` (important!)
4. **Content:** Add shortcode:
   [atc_package_details]
   
5. **Publish**

**That's it! This ONE page works for ALL packages!** ✅

---

## 🔄 How Package ID is Determined

The shortcode checks in this order:

1. **Shortcode parameter** (if specified):
   ```
   [atc_package_details package_id="123"]
   ```
   → Uses Package ID: 123

2. **URL parameter** (if not in shortcode):
   ```
   /package-details/?package_id=123
   ```
   → Uses Package ID: 123

3. **Error** (if neither found):
   → Shows: "Package ID is required."

---

## 💡 Examples

### **Example 1: Same Shortcode for All (Recommended)**

**Page: "Package Details"**
```
[atc_package_details]
```

**URLs:**
- `/package-details/?package_id=1` → Shows Package #1
- `/package-details/?package_id=2` → Shows Package #2
- `/package-details/?package_id=3` → Shows Package #3

**✅ ONE SHORTCODE, ALL PACKAGES!**

---

### **Example 2: Specific Package Pages (Optional)**

**Page 1: "Goa Tour Package"**
```
[atc_package_details package_id="1"]
```
**URL:** `/goa-tour-package/` (no parameters needed)

**Page 2: "Kerala Tour Package"**
```
[atc_package_details package_id="2"]
```
**URL:** `/kerala-tour-package/` (no parameters needed)

**Page 3: "Package Details" (for all others)**
```
[atc_package_details]
```
**URL:** `/package-details/?package_id=3` (uses URL parameter)

---

## 🎯 Best Practice

### **Recommended: One Page for All Packages** ✅

**Why?**
- ✅ Simple setup (one page, one shortcode)
- ✅ Easy to maintain
- ✅ Works automatically for all packages
- ✅ No need to create pages for each package

**Setup:**
1. Create ONE page: "Package Details"
2. Add shortcode: `[atc_package_details]`
3. Done! Works for all packages automatically

---

### **Alternative: Specific Pages for Popular Packages** (Optional)

**When to use:**
- You have very popular packages
- You want SEO-friendly URLs (no parameters)
- You want dedicated landing pages

**Setup:**
1. Create specific page for popular package
2. Add shortcode with package_id: `[atc_package_details package_id="123"]`
3. Keep general page for other packages: `[atc_package_details]`

---

## ❓ Common Questions

### **Q: Do I need to create a page for each package?**
**A:** No! Create ONE page with `[atc_package_details]` and it works for ALL packages automatically.

### **Q: How does it know which package to show?**
**A:** The package ID comes from the URL parameter `?package_id=123`. The "View Details" button automatically adds this to the URL.

### **Q: Can I use the same shortcode for all packages?**
**A:** Yes! That's the recommended way. Just use `[atc_package_details]` and it automatically gets the package ID from the URL.

### **Q: What if I want a specific page for a popular package?**
**A:** You can create a separate page and use `[atc_package_details package_id="123"]` in the shortcode. This gives you a direct URL without parameters.

### **Q: Can I mix both approaches?**
**A:** Yes! You can have:
- One general page: `[atc_package_details]` (for most packages)
- Specific pages: `[atc_package_details package_id="123"]` (for popular packages)

---

## 🚀 Quick Setup Summary

### **For All Packages (Recommended):**

1. **Create ONE page:**
   - Title: "Package Details"
   - Slug: `package-details`
   - Shortcode: `[atc_package_details]`
   - Publish

2. **Done!** ✅
   - Works for ALL packages automatically
   - Package ID comes from URL: `?package_id=123`
   - "View Details" button redirects correctly

---

## 📊 Comparison

| Approach | Pages Needed | Shortcode | Best For |
|----------|-------------|-----------|----------|
| **Same Shortcode** | 1 page | `[atc_package_details]` | Most cases ✅ |
| **With Package ID** | 1+ pages | `[atc_package_details package_id="123"]` | Popular packages |
| **Mixed** | 2+ pages | Both | Flexibility |

---

## ✅ Recommended Solution

**Create ONE page:**
- **Title:** "Package Details"
- **Slug:** `package-details`
- **Shortcode:** `[atc_package_details]`

**This works for ALL packages automatically!** 🎉

---

## 🎯 Final Answer

**Use the SAME shortcode `[atc_package_details]` for ALL packages!**

You only need to create ONE page with this shortcode, and it will automatically show the correct package based on the URL parameter.

No need to specify package_id in the shortcode unless you want a dedicated page for a specific package.

