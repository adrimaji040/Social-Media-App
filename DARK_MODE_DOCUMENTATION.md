# Dark Mode Implementation Documentation

## Social Media App - Algonquin College Project

**Date:** August 7, 2025  
**Branch:** feature/ui-ux-improvements-v2  
**Bootstrap Version:** 5.3.3

---

## Overview

The dark mode implementation in this Social Media App is a comprehensive system that combines **Bootstrap 5.3.3's native dark theme support** with **custom CSS enhancements** and **JavaScript functionality** to provide a seamless, persistent, and visually consistent theme switching experience.

---

## 1. Architecture Foundation

### Bootstrap 5.3.3 Theme System

The project leverages Bootstrap's built-in dark theme system via the `data-bs-theme` attribute:

```html
<html lang="en" data-bs-theme="light"></html>
```

This attribute controls Bootstrap's automatic dark/light theme switching for all Bootstrap components including:

- Cards, forms, buttons, modals
- Navigation bars, dropdowns
- Tables, alerts, badges
- Background and text colors

---

## 2. Theme Persistence & Initialization

### Pre-render Theme Loading

**Location:** `Common/Header.php` (Lines 22-27)

```javascript
// Theme management - Load saved theme before page renders
(function () {
  const savedTheme = localStorage.getItem("bs-theme") || "light";
  document.documentElement.setAttribute("data-bs-theme", savedTheme);
})();
```

**Purpose:**

- Prevents flash of incorrect theme on page load (FOUC - Flash of Unstyled Content)
- Loads user preference before any visual rendering occurs
- Provides graceful fallback to light theme

**Storage Strategy:**

- Uses `localStorage.setItem('bs-theme', theme)` for persistence
- Survives browser sessions and page refreshes
- Works across all pages in the application

---

## 3. User Interface Components

### Theme Toggle Button

**Location:** `Common/Header.php` (Lines 87-91)

```html
<button
  class="btn btn-outline-light btn-sm"
  id="theme-toggle"
  title="Toggle Dark/Light Theme"
>
  <i class="fas fa-moon" id="theme-icon"></i>
</button>
```

**Features:**

- **Position:** Integrated into main navigation bar
- **Icons:** FontAwesome moon (light mode) / sun (dark mode) icons
- **Accessibility:** Includes descriptive title attributes
- **Styling:** Bootstrap outline button with responsive sizing

---

## 4. JavaScript Theme Management

### Core Theme Switching Logic

**Location:** `Common/Footer.php` (Lines 64-81)

```javascript
themeToggle.addEventListener("click", function () {
  const currentTheme = document.documentElement.getAttribute("data-bs-theme");
  const newTheme = currentTheme === "dark" ? "light" : "dark";

  // Update theme
  document.documentElement.setAttribute("data-bs-theme", newTheme);
  localStorage.setItem("bs-theme", newTheme);

  // Update navbar classes
  updateNavbarTheme(newTheme);

  // Update icon
  updateThemeIcon();
});
```

### Dynamic Icon Management

**Location:** `Common/Footer.php` (Lines 52-62)

```javascript
function updateThemeIcon() {
  const currentTheme = document.documentElement.getAttribute("data-bs-theme");
  if (currentTheme === "dark") {
    themeIcon.className = "fas fa-sun";
    themeToggle.title = "Switch to Light Mode";
  } else {
    themeIcon.className = "fas fa-moon";
    themeToggle.title = "Switch to Dark Mode";
  }
}
```

### Navbar Theme Updates

**Location:** `Common/Footer.php` (Lines 83-95)

```javascript
function updateNavbarTheme(theme) {
  const navbar = document.getElementById("main-navbar");
  if (navbar) {
    if (theme === "dark") {
      navbar.classList.add("navbar-dark");
      navbar.classList.remove("navbar-light");
    } else {
      navbar.classList.add("navbar-light");
      navbar.classList.remove("navbar-dark");
    }
  }
}
```

---

## 5. CSS Theme-Specific Styling

### Bootstrap Responsive Classes

The project extensively uses Bootstrap's theme-responsive utility classes:

- **`bg-body`** - Background that adapts to current theme
- **`bg-body-tertiary`** - Secondary background with theme adaptation
- **`text-body`** - Text color that automatically adjusts
- **`border`** - Borders that adapt to theme colors

**Example Usage:**

```html
<!-- Automatically adapts from light to dark background -->
<form class="p-4 border bg-body rounded shadow">
  <h1 class="text-center display-6 animated-border">Login</h1>
  <!-- Form content -->
</form>
```

### Custom Dark Theme Overrides

**Location:** `Common/css/styles.css`

#### Body and Foundation

```css
[data-bs-theme="dark"] body {
  background-color: #1a1a1a;
  color: #e9ecef;
}
```

#### Card Components

```css
[data-bs-theme="dark"] .card {
  background-color: #2d3748;
  border-color: #4a5568;
  color: #e9ecef;
}

[data-bs-theme="dark"] .card h2.card-title strong {
  color: #61dafb;
}
```

#### Navigation Bar

```css
[data-bs-theme="dark"] #main-navbar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-bottom: 1px solid #4a5568;
}

[data-bs-theme="dark"] #main-navbar .nav-link {
  color: #ffffff !important;
  transition: color 0.3s ease;
}

[data-bs-theme="dark"] #main-navbar .nav-link:hover {
  color: #61dafb !important;
  text-shadow: 0 0 5px rgba(97, 218, 251, 0.3);
}
```

#### Theme Toggle Button

```css
[data-bs-theme="dark"] #main-navbar .btn-outline-light {
  border-color: #61dafb;
  color: #61dafb;
}

[data-bs-theme="dark"] #main-navbar .btn-outline-light:hover {
  background-color: #61dafb;
  color: #1a1a1a;
  box-shadow: 0 0 10px rgba(97, 218, 251, 0.3);
}
```

---

## 6. Animated Elements Adaptation

### Light Mode Animation (Gradient Text)

```css
.animated-border {
  padding: 5px;
  text-align: center;
  background: linear-gradient(
    90deg,
    #ff6b6b,
    #4ecdc4,
    #45b7d1,
    #f9ca24,
    #ff6b6b
  );
  background-size: 400% 400%;
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: borderAnimation 5s linear infinite alternate, fadeInOut 5s
      ease-in-out infinite;
}
```

### Dark Mode Animation (Subtle Glow)

```css
[data-bs-theme="dark"] .animated-border {
  background: none;
  background-clip: unset;
  -webkit-background-clip: unset;
  -webkit-text-fill-color: unset;
  color: #fff;
  text-shadow: 0 0 2px #ff9ff3, 0 0 4px #54a0ff;
  animation: glowAnimation 5s ease-in-out infinite alternate;
}

@keyframes glowAnimation {
  0% {
    text-shadow: 0 0 2px #ff9ff3, 0 0 4px #54a0ff;
  }
  50% {
    text-shadow: 0 0 3px #54a0ff, 0 0 5px #5f27cd;
  }
  100% {
    text-shadow: 0 0 2px #00d2d3, 0 0 4px #ff9ff3;
  }
}
```

**Design Decisions:**

- **Light Mode:** Uses gradient background with text clipping for vibrant, colorful effect
- **Dark Mode:** Uses subtle text-shadow glow to prevent eye strain
- **Reduced Intensity:** Glow effects use small blur radii (2px-5px) for comfort

---

## 7. Performance Optimizations

### CSS Cache Management

**Location:** `Common/Header.php` (Line 21)

```php
<link rel="stylesheet" href="<?= $isInPages ? '../' : '' ?>Common/css/styles.css?v=<?= time() ?>">
```

**Purpose:**

- Forces browser to reload CSS when changes are made
- Prevents styling issues due to browser caching
- Ensures users always get the latest theme improvements

### Path Resolution

```php
$isInPages = strpos($currentScript, '/pages/') !== false;
```

**Function:**

- Automatically detects whether current page is in `/pages/` subdirectory
- Adjusts CSS path accordingly (`../Common/css/styles.css` vs `Common/css/styles.css`)
- Ensures proper CSS loading across all application pages

---

## 8. Component Integration Examples

### Forms (Login, NewUser)

**Before (Theme Issues):**

```html
<form class="p-4 border bg bg-light rounded shadow"></form>
```

**After (Theme Responsive):**

```html
<form class="p-4 border bg-body rounded shadow"></form>
```

**Result:** Forms now automatically adapt their background color to the current theme.

### Content Cards (MyAlbums, EditProfile, etc.)

```html
<div class="shadow py-2 px-3 mb-5 bg-body-tertiary rounded">
  <h1 class="mb-2 animated-border display-6">Page Title</h1>
  <!-- Content -->
</div>
```

**Features:**

- `bg-body-tertiary` provides secondary background that adapts to theme
- `animated-border` class provides theme-specific text animations
- `shadow` provides depth that works in both themes

---

## 9. File Structure & Organization

```
Common/
├── Header.php          # Theme initialization, toggle button UI
├── Footer.php          # Theme switching JavaScript logic
└── css/
    └── styles.css      # Custom dark theme CSS overrides

pages/
├── Login.php           # Theme-responsive form backgrounds
├── NewUser.php         # Theme-responsive form backgrounds
├── MyAlbums.php        # Theme-responsive content cards
├── EditProfile.php     # Theme-responsive content cards
└── [other pages...]    # All use theme-responsive classes
```

---

## 10. Implementation Workflow

### Page Load Sequence

1. **Pre-render Script Execution:**

   - Reads `localStorage.getItem('bs-theme')`
   - Sets `data-bs-theme` attribute immediately
   - Prevents theme flash

2. **CSS Application:**

   - Bootstrap applies base theme styles
   - Custom `[data-bs-theme="dark"]` rules override specific elements
   - Animations adapt based on current theme

3. **JavaScript Initialization:**
   - Updates theme toggle icon to match current theme
   - Applies navbar theme classes
   - Sets up event listeners

### User Theme Switch

1. **User Clicks Toggle Button:**

   - Event listener triggers theme switch function
   - Determines opposite theme (`light` ↔ `dark`)

2. **Theme Application:**

   - Updates `data-bs-theme` attribute on `<html>` element
   - Saves preference to `localStorage`
   - Updates navbar classes dynamically
   - Changes toggle button icon

3. **Visual Updates:**
   - Bootstrap automatically re-applies theme styles
   - Custom CSS rules activate/deactivate
   - Smooth transitions occur where defined

---

## 11. Browser Compatibility

### Supported Features

- **LocalStorage:** All modern browsers (IE8+)
- **CSS Custom Properties:** Modern browsers (IE not supported)
- **CSS Background-clip:** Webkit browsers, modern browsers
- **Text-shadow:** All modern browsers

### Fallback Strategies

- Default to light theme if localStorage unavailable
- Graceful degradation for unsupported CSS features
- Progressive enhancement approach

---

## 12. Future Enhancement Opportunities

### Potential Improvements

1. **System Theme Detection:**

   ```javascript
   const prefersDark = window.matchMedia(
     "(prefers-color-scheme: dark)"
   ).matches;
   ```

2. **Transition Animations:**

   ```css
   html {
     transition: background-color 0.3s ease, color 0.3s ease;
   }
   ```

3. **Advanced Color Schemes:**

   - Multiple theme options (dark, light, auto, high-contrast)
   - Custom color picker for personalization

4. **Performance Optimizations:**
   - CSS custom properties for theme variables
   - Reduced CSS specificity
   - Lazy loading of theme-specific assets

---

## 13. Troubleshooting Guide

### Common Issues

**Theme Not Persisting:**

- Check localStorage is enabled in browser
- Verify `data-bs-theme` attribute is being set
- Ensure JavaScript is not being blocked

**Animations Not Working:**

- Clear browser cache (CSS cache-busting should handle this)
- Check for JavaScript errors in console
- Verify CSS file is loading correctly

**Form Backgrounds Not Adapting:**

- Ensure `bg-body` or `bg-body-tertiary` classes are used instead of `bg-light`
- Check Bootstrap version compatibility

---

## 14. Testing Checklist

- [ ] Theme toggle button changes icon correctly
- [ ] Theme preference persists across page refreshes
- [ ] All forms adapt background to theme
- [ ] Animated text is visible in both themes
- [ ] Navigation bar styling updates properly
- [ ] No console errors during theme switching
- [ ] Accessibility features work (screen reader compatibility)
- [ ] Performance is acceptable (no significant lag during switching)

---

## Conclusion

This dark mode implementation provides a robust, user-friendly, and maintainable theme switching system that enhances the user experience while maintaining visual consistency across the entire Social Media Application. The combination of Bootstrap's native theme support with custom enhancements ensures both rapid development and fine-grained control over the visual experience.

The system is designed to be extensible, performant, and accessible, making it suitable for production use in educational and professional environments.
