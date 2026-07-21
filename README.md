# SMP Verified Profiles

A comprehensive WordPress plugin for managing verified profiles on Scale My Publication systems. This plugin provides complete profile verification, management, and display functionality with ACF (Advanced Custom Fields) integration.

![Version](https://img.shields.io/badge/version-6.5.44-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)

---

## 6.5.44 Updates

- Updated the vendored Hexa WP Core package to 0.19.65 after the final upstream collapsible-state guard release.

## 6.5.43 Updates

- Updated the vendored Hexa WP Core package to 0.19.64 after the upstream release landed.

## 6.5.42 Updates

- Rebuilt the settings navigation with Hexa WP Core `TabDefinition`, `TabRegistry`, and the shared grouped sidebar renderer.
- Consolidated the duplicate Display Cards route into Profile Cards while preserving `tab=display-cards` as a compatibility alias.
- Updated all dashboard quick links to canonical tab URLs and removed retired host-level tab styles.
- Updated the vendored Hexa WP Core package to 0.19.63.

## 6.5.28 Updates

- Added current attached profiles and pending profile panels inside the consolidated post editor Verified Profiles card.
- Added AJAX profile-state refresh with live and backend links for each attached profile.
- Hid the old ACF-only Post - Verified Profile - Admin box in the editor UI when the consolidated card is enabled.

## 6.5.27 Updates

- Consolidated the post editor Verified Profiles workflow so the legacy Find Profiles box no longer duplicates the new spawner when the spawner is enabled.
- Added fast existing-profile detection, manual name entry, per-entity attach-existing versus spawn-new selection, and strict/default detection controls.
- Updated approval to attach existing local verified profiles by default and only spawn new entities when explicitly selected.

## 6.5.26 Updates

- Improved HexaWP Core Dynamic Button loading state with a larger visible custom spinner.
- Added detailed Verified Profiles spawner error reporting for AJAX, WordPress, and upstream API failures.

## 6.5.25 Updates

- Reworked the post editor verified-profile spawning metabox into a step-based flow with a top-level Scan and auto approve action.
- Added HexaWP Core Dynamic Button support for spinner, success, and error states.
- Added JavaScript-rendered spawned profile cards with frontend/backend links and persisted Hexa Core field-report subcards.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Plugin Architecture](#plugin-architecture)
- [ACF Field Groups](#acf-field-groups)
- [Snippets System](#snippets-system)
- [Shortcodes](#shortcodes)
- [GitHub Auto-Updates](#github-auto-updates)
- [Settings Dashboard](#settings-dashboard)
- [Developer Reference](#developer-reference)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)

---

## Overview

**SMP Verified Profiles** is a self-contained WordPress plugin designed for managing verified professional profiles. It integrates deeply with Advanced Custom Fields Pro to provide a robust profile management system with custom post types, user roles, and extensive customization options.

### Key Highlights

- ✅ **Self-contained** - No external plugin dependencies (except ACF Pro)
- ✅ **Hexa Core GitHub Auto-Updates** - Shared Hexa Core updater checks and installs this repository
- ✅ **Modular Snippet System** - Enable/disable features individually
- ✅ **Performance Optimized** - Elementor-aware loading, admin-only features properly scoped
- ✅ **Centralized Configuration** - All settings managed through a single Config class

---

## Features

### Profile Management
- Custom "Profile" post type with comprehensive fields
- Verified profile status tracking
- Profile claiming functionality
- Profile manager user role
- Featured profiles system

### User Management
- Custom user fields for profile managers
- WP Admin customizations for non-admin users
- Password reset controls
- User page enhancements

### Content Features
- Schema.org markup injection for profiles
- Custom favicon for verified pages
- MuckRack integration
- WooCommerce integration (optional)
- Stripe payment integration (optional)

### Admin Features
- Comprehensive settings dashboard
- System health checks
- Plugin dependency verification
- ACF field group management
- Event handling system

---

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 5.0 or higher |
| PHP | 7.4 or higher |
| ACF Pro | Latest version |

### Required Plugins

- **Advanced Custom Fields Pro** - Required for all field group functionality

---

## Installation

### Method 1: Direct Upload

1. Download the latest release from [GitHub Releases](https://github.com/mikeyperes/smp-verified-profiles/releases)
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Upload the `smp-verified-profiles.zip` file
4. Click **Install Now** and then **Activate**

### Method 2: Manual Installation

1. Clone or download this repository
2. Rename the folder to `smp-verified-profiles` (important!)
3. Upload to `/wp-content/plugins/`
4. Activate through the WordPress Plugins menu

### Method 3: Git Clone

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/mikeyperes/smp-verified-profiles.git
```

> ⚠️ **Important**: The plugin folder must be named exactly `smp-verified-profiles` for auto-updates to work correctly.

---

## Configuration

### Initial Setup

1. Navigate to **Settings → Verified Profiles - Settings**
2. Enable the desired snippets (feature modules)
3. Configure ACF field groups as needed
4. Set up profile pages and listing grids

### Config Class Reference

All plugin settings are centralized in the `Config` class located in `initialization.php`:

```php
namespace smp_verified_profiles;

class Config {
    // Plugin Identity
    public static $plugin_name = "Scale My Publication - Verified Profiles";
    public static $plugin_starter_file = "initialization.php";
    public static $plugin_folder_name = "smp-verified-profiles";
    public static $plugin_short_id = "smp_vp";
    
    // Settings Page
    public static $settings_page_slug = "smp-verified-profiles";
    public static $settings_page_name = "Verified Profiles - Settings";
    
    // GitHub Repository
    public static $github_repo = "mikeyperes/smp-verified-profiles";
    public static $github_branch = "main";
}
```

---

## Plugin Architecture

```
smp-verified-profiles/
├── initialization.php          # Main plugin file, Config class, bootstrap
├── GitHub_Updater.php          # Legacy updater class; runtime updates use Hexa Core
├── generic-functions.php       # Core helper functions
│
├── ACF Field Registrations
│   ├── register-acf-fields.php
│   ├── register-acf-structures.php
│   ├── register-acf-user-profile.php
│   ├── register-acf-verified-profile.php
│   └── register-acf-structure-theme-options.php
│
├── Settings Dashboard
│   ├── settings-dashboard.php
│   ├── settings-dashboard-plugin-info.php
│   ├── settings-dashboard-plugin-checks.php
│   ├── settings-dashboard-snippets.php
│   ├── settings-dashboard-system-checks.php
│   └── settings-dashboard-define-pages-and-listing-grids.php
│
├── Snippets (Feature Modules)
│   ├── snippet-adjust-profiles-category-meta-box.php
│   ├── snippet-adjust-wp-admin-for-non-admins.php
│   ├── snippet-adjust-wp-admin-for-profile-managers.php
│   ├── snippet-claim-profile-functionality.php
│   ├── snippet-disable-password-reset.php
│   ├── snippet-enable-verified-profile-shortcodes.php
│   ├── snippet-faviconn-for-verified-pages.php
│   ├── snippet-inject-schema-on-single-profile.php
│   ├── snippet-muckrack-functionality.php
│   ├── snippet-post-functionality.php
│   ├── snippet-profile-functionality.php
│   ├── snippet-profile-post-wp-admin-functionality.php
│   ├── snippet-woocommerce-base.php
│   ├── snippet-woocommerce-stripe-integration.php
│   ├── snippet-wp-admin-add-featured-image-to-events.php
│   ├── snippet-wp-admin-filter-featured-profiles.php
│   └── snippet-wp-admin-user-page-functionality.php
│
├── Dashboards
│   ├── dashboard-verified-profile.php
│   ├── verified-profile-dashboard.php
│   └── profile-manager-dashboard.php
│
└── shortcodes.php              # All shortcode definitions
```

### Loading Order

1. **Security Check** - `defined('ABSPATH')` guard
2. **Config Class** - Static configuration loaded
3. **Helper Functions** - `generic-functions.php`
4. **GitHub Updater** - `admin_init` hook (admin only)
5. **ACF Fields** - `acf/init` hook
6. **Snippets** - `init` hook (priority 12)
   - Admin snippets: Only when `is_admin()` and not in Elementor context
   - Frontend snippets: Always loaded

---

## ACF Field Groups

The plugin registers several ACF field groups:

| Group ID | Name | Location |
|----------|------|----------|
| `group_66b7bdf713e77` | Post - Verified Profile - Admin | Profile post type |
| `group_656ea6b4d7088` | Profile - Admin | Profile administration |
| `group_656eb036374de` | Profile - Person - Public | Public profile fields |
| `group_65a8b25062d91` | User - Profile Manager | User profile management |
| `group_658602c9eaa49` | User - Verified Profile Manager - Admin | Admin user fields |
| `group_67e39e4171b16` | Verified Profile Custom Fields | Extended profile data |

### Enabling Field Groups

Field groups are enabled through snippets in the settings dashboard:

```php
// Example: Enable theme options
enable_acf_theme_options();

// Example: Register profile post type and fields
register_profile_custom_post_type();
register_profile_general_acf_fields();
```

---

## Snippets System

Snippets are modular feature toggles that can be enabled/disabled from the settings dashboard.

### Snippet Categories

#### ACF Snippets
| Snippet ID | Description |
|------------|-------------|
| `enable_acf_theme_options` | Enable ACF theme options page |
| `register_profile_custom_post_type` | Register Profile CPT |
| `register_profile_general_acf_fields` | Register profile ACF fields |
| `register_verified_profile_custom_fields` | Extended verified profile fields |
| `register_user_custom_fields` | User profile fields |

#### Admin Snippets
| Snippet ID | Description |
|------------|-------------|
| `add_wp_admin_add_featured_image_to_events` | Add featured images to events |
| `snippet_post_functionality` | Enhanced post features |
| `enable_snippet_wp_admin_user_page_functionality` | User page enhancements |
| `enable_snippet_adjust_profiles_category_meta_box` | Profile category meta box |
| `enable_snippet_profile_post_wp_admin_functionality` | Profile post admin features |
| `enable_snippet_disable_password_reset` | Disable password reset |

#### Frontend Snippets
| Snippet ID | Description |
|------------|-------------|
| `enable_snippet_inject_schema_on_single_profile` | Schema.org markup |
| `enable_snippet_faviconn_for_verified_pages` | Custom favicon |
| `enable_snippet_claim_profile_functionality` | Profile claiming |
| `enable_snippet_muckrack_functionality` | MuckRack integration |
| `enable_snippet_verified_profile_shortcodes` | Profile shortcodes |

---

## Shortcodes

### Profile Shortcodes

```php
// Display verified profile information
[verified_profile_name]
[verified_profile_bio]
[verified_profile_image]
[verified_profile_social_links]
```

### MuckRack Shortcodes

```php
// MuckRack integration shortcodes
[muckrack_profile]
[muckrack_articles]
```

> **Note**: Shortcodes are enabled through their respective snippets in the settings dashboard.

---

## GitHub Auto-Updates

The plugin uses Hexa WordPress Plugin Core for GitHub update checks, direct installs, and vendored core package checks.

### How It Works

1. WordPress checks for plugin updates periodically
2. Hexa Core fetches the `Version:` header from GitHub's raw file
3. If a newer version exists, it appears in WordPress Updates
4. Hexa Core downloads directly from GitHub and normalizes the folder slug correctly

### Manual Update Check

1. Go to **Settings → Verified Profiles - Settings**
2. Scroll to **Plugin Info** panel
3. Click **Force Update Check** for the plugin or **Force Core Check** for the vendored Hexa Core package

### Direct Update

1. Click **Update Now from GitHub** button
2. The plugin downloads, extracts, and installs automatically
3. Page reloads with new version active

### Version Rollback

1. Click **Load Versions** to fetch GitHub tags
2. Select a previous version from dropdown
3. Click **Download Selected Version**
4. Manually install the downloaded zip if needed

---

## Settings Dashboard

Access via **Settings → Verified Profiles - Settings**

### Dashboard Panels

| Panel | Description |
|-------|-------------|
| **System Checks** | WordPress environment verification |
| **Plugin Checks** | Required plugin status |
| **Snippets** | Enable/disable feature modules |
| **Plugin Info** | Version info, updates, downloads |
| **ACF Structures** | Field group status and configuration |

### Plugin Info Panel Features

- **Current Version** vs **Latest Version** display
- **Force Update Check** - Clear caches, check GitHub
- **Update Now from GitHub** - One-click direct update
- **Download Plugin ZIP** - Get properly-named zip file
- **Version History** - Download previous versions for rollback

---

## Developer Reference

### Namespace

All functions and classes use the `smp_verified_profiles` namespace:

```php
namespace smp_verified_profiles;

// Access config
$plugin_name = Config::$plugin_name;
$basename = Config::get_plugin_basename();

// Use helper functions
write_log('Debug message', true);
activate_snippets('admin');
```

### Key Functions

```php
// Check if in Elementor context (for performance)
is_elementor_context(): bool

// Activate snippets by type
activate_snippets(string $type): void  // 'acf', 'admin', 'non_admin'

// Get snippets array
get_snippets(string $type): array

// Debug logging (respects WP_DEBUG)
write_log(mixed $log, bool $full_debug = false, bool $display_stack = false): void
```

### Hooks & Filters

```php
// Plugin loads ACF fields
add_action('acf/init', function() {
    // ACF field registrations
}, 10);

// Main initialization
add_action('init', function() {
    // Snippet activation
}, 12);

// Admin-only initialization
add_action('admin_init', function() {
    // GitHub updater, admin features
}, 10);
```

### AJAX Actions

| Action | Capability | Description |
|--------|------------|-------------|
| `smp_vp_force_update_check` | `update_plugins` | Force version check |
| `smp_vp_direct_update_plugin` | `update_plugins` | Install from GitHub |
| `smp_vp_download_plugin_zip` | `manage_options` | Download current version |
| `smp_vp_load_github_versions` | `update_plugins` | Fetch version tags |
| `smp_vp_download_specific_version` | `manage_options` | Download specific version |

---

## Troubleshooting

### Common Issues

#### Plugin won't activate
- Ensure ACF Pro is installed and activated
- Check PHP version is 7.4 or higher
- Verify folder name is exactly `smp-verified-profiles`

#### Updates not showing
1. Go to Settings → Verified Profiles - Settings
2. Click **Force Update Check**
3. Check **Dashboard → Updates**

#### GitHub API rate limiting
- Updates are cached for 30 minutes
- If you see errors, wait and try again
- For frequent development, consider using a GitHub access token

#### Snippets not working
1. Verify the snippet is enabled in settings
2. Check if required ACF field groups exist
3. Review `wp-content/debug.log` for errors

### Debug Mode

Enable WordPress debugging to see detailed logs:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logs appear in `/wp-content/debug.log`

---

## Changelog

### Version 6.5.16 (Current)
- Rebuilt the Shortcodes dashboard tab on Hexa WP Core ShortcodeRegistry with grouped discovery, runtime registration status, source/callback reporting, and retained profile value previews.
- Renamed the post edit metabox to Verified Profiles and changed the scan button text to Find Profiles.

### Version 6.5.11
- Synced Hexa WordPress Plugin Core to 0.18.0 and moved the Snippets tab to the generic core snippet registry with description, testing, related snippets, related shortcodes, and README components.

### Version 6.5.6
- Fixed the Profiles Dashboard admin callback registration and namespaced WordPress class lookup that could trigger a critical error.

### Version 6.5.5
- Swapped runtime update checks to Hexa WordPress Plugin Core, added the vendored Hexa Core package updater panel, and synced the bundled core to 0.17.2.

### Version 6.5.2
- Added the active Personal Education repeater to the verified profile ACF group for schema-ready school, degree, date, URL, Wikipedia, SameAs, and description fields.

### Version 6.5.1

- **Removed** legacy Staff Writer and MuckRack user ACF field declarations so hws-base-tools is the sole owner of those fields.

### Version 6.4

- **Added** Shortcodes dashboard tab with source scanning, descriptions, registration status, and live examples
- **Added** Verified profile selector that renders field/shortcode/value rows for the selected profile

### Version 6.3
- **Removed** hws-base-tools dependency - plugin is now fully self-contained
- **Added** Centralized Config class for all plugin settings
- **Added** Self-contained GitHub_Updater class with public API
- **Added** Complete plugin info dashboard with AJAX functionality
- **Added** Version history download and rollback capability
- **Fixed** "Undefined array key timeout" error in GitHub updater
- **Fixed** "Cannot access private property" error in plugin info display
- **Improved** Performance with Elementor context detection
- **Improved** Admin-only feature loading optimization

### Version 6.2
- Previous stable release
- Required hws-base-tools plugin

---

## Support

For issues and feature requests, please use the [GitHub Issues](https://github.com/mikeyperes/smp-verified-profiles/issues) page.

---

## License

This plugin is proprietary software developed for Scale My Publication systems.

**Author:** Michael Peres  
**Website:** [michaelperes.com](https://michaelperes.com)  
**Repository:** [github.com/mikeyperes/smp-verified-profiles](https://github.com/mikeyperes/smp-verified-profiles)
