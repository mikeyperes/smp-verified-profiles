# SMP Verified Profiles

Verified-profile registration, claiming, management, display, spawning, shortcodes, and schema integration for Scale My Publication websites.

## Identity

- Repository: `mikeyperes/smp-verified-profiles`
- Plugin slug: `smp-verified-profiles`
- Namespace: `smp_verified_profiles`
- Version: `7.1.4`
- PHP requirement: `8.0+`

## Ownership

Verified Profiles owns:

- The immutable `profile` custom post type.
- Verified Profile, profile-manager, program-settings, and public profile ACF structures.
- Profile claiming, application, assignment, spawning, and profile-manager roles/capabilities.
- Profile display cards, page templates, listing templates, MuckRack output, and profile shortcodes.
- Verified Profile schema object construction and profile-page injection rules.

HWS Base Tools owns website classification and the optional primary entity. Verified Profiles can provide or consume a canonical Verified Profile source, but no HWS primary entity is required for normal profile operations.

## Custom Post Types and Fields

The **Custom Post Types** tab uses Hexa WP Core for:

- Profile enable/disable state.
- Editable public rewrite slug and WordPress labels.
- ACF field-group toggles and detailed field breakdowns.
- The moved Verified Profile Program Settings panel.

The post-type key remains `profile` so existing posts, URLs, relationships, and templates are not orphaned.

## Existing Functional Surface

The consolidation preserves the plugin's established behavior:

- Profile claiming and unclaimed-profile workflows.
- Profile Manager role creation and restricted admin behavior.
- Profile spawning proposals, API tests, existing-profile detection, approval, and attachment.
- Verified Profile application, welcome, badges, archive, and single-profile page assignments.
- Display cards, Elementor/listing templates, MuckRack displays, and shortcode providers.
- Profile reprocessing, schema controls, entity shortcodes, and public JSON-LD output.
- Email/program settings and existing stored ACF values.

## Canonical Entity

Hexa WP Core resolves an optional HWS canonical entity and any WordPress user bound to the selected Profile post. The settings UI shows the source record, attached author, and available fields without changing the profile's existing field storage.

Legacy source values remain available as read-only migration fallbacks.

## Schema

Verified Profiles constructs profile-specific schema nodes. `Hexa\PluginCore\SchemaTools` owns reusable graph normalization, deduplication, safe JSON-LD encoding, and output injection. Existing profile IDs, relationships, field mappings, and Rank Math coexistence rules remain plugin-owned and must retain output parity.

## Dashboard

The settings dashboard uses Hexa WP Core grouped tabs and collapsible components. Major areas include:

- Overview, Profile Settings, Custom Post Types, and ACF structures.
- Profile cards, full profile-page templates, page/listing templates, shortcodes, and snippets.
- Profile generation/spawning and processing tools.
- Plugins, updates, Hexa WP Core, and developer diagnostics.

Legacy standalone Profile settings routes are redirected into the plugin dashboard rather than rendered as separate WordPress admin pages.

## Architecture

`smp-verified-profiles.php` is the plugin entry. Focused adapters under `src/` integrate Core-managed content types, ACF groups, canonical entities, FAQ/schema utilities, tabs, AJAX guards, and update panels.

Plugin updates and vendored Core updates are delegated entirely to Hexa WP Core. The retired standalone `GitHub_Updater.php` implementation has been removed.

## Requirements

| Requirement | Minimum |
| --- | --- |
| WordPress | 5.0 |
| PHP | 8.0 |
| Hexa WP Core bundle | 2.1.2 |

ACF Pro is required for the profile field structures. Elementor/JetEngine integrations require those plugins only when their templates are used.

## Installation

Install the repository as `wp-content/plugins/smp-verified-profiles`, activate `smp-verified-profiles.php`, and open **Settings > Verified Profiles**. Existing profile posts, managers, page assignments, and ACF values are reused in place.

## Development

Run all static regression contracts with:

```bash
for file in tests/*.php; do php "$file" || exit 1; done
```

Live release verification must also exercise claiming, spawning, role restrictions, templates, shortcodes, and schema output through their visible WordPress workflows.

## Changelog

### 7.1.4

- Updated the bundled Hexa WP Core to 2.1.2 so an explicitly one-column template library remains one column at tablet widths.

### 7.1.3

- Changed the single-post verified-profile collection to three columns at tablet widths while preserving six desktop and two mobile columns.

### 7.1.2

- Consolidated the live single-post "In This Article" wrapper and empty-state handling with the full-row profile-template selector release.
- Preserved the shortcode fallback and Elementor-host hiding behavior while shipping the complete Hexa WP Core 2.1.1 bundle.

### 7.1.1

- Moved the no-design state above each template library as a Hexa WP Core toggle that disables the design choices while active.
- Changed every profile-card selector to one full-width template per row and rendered three representative profile cards across each preview.
- Updated the bundled Hexa WP Core to 2.1.1.

### 7.1.0

- Rebuilt every Profile Card and Verified Profile page template selection surface with the reusable Hexa WP Core three-column visual selector.
- Added a third full-page design and a true custom-design mode that emits no automatic/default plugin markup or styling while preserving explicitly named template shortcodes.
- Moved full profile-page settings into Features, retained the old URL as an alias, and replaced the standalone template library with Core toggles, detailed colors, Elementor import, and typography controls.
- Updated the bundled Hexa WP Core to 2.1.0.

### 7.0.6

- Restored compatibility with the saved Elementor Loop Item selected for profiles mentioned in single articles, including the historical responsive collection grid and per-profile dynamic context.

### 7.0.5

- Restricted profile-related coverage to posts with an exact ACF repeater relationship and prevented sticky posts from being injected into empty profile queries.

### 7.0.4

- Let WordPress own responsive image loading attributes so optimization plugins cannot duplicate `loading="lazy"` in profile markup.

### 7.0.3

- Rendered local Verified Profile images through WordPress responsive image markup with intrinsic dimensions and `srcset`/`sizes`.
- Changed built-in profile portraits from the 1024px large derivative to the 300px medium derivative used by the 260px profile layout.

### 7.0.2

- Made homepage profile shortcodes select the newest published profiles by publication date without requiring an attached article.
- Added deterministic ordering and targeted homepage/profile-archive cache invalidation when profiles change.
- Added a regression contract for recent-profile query behavior.

### 7.0.1

- Exposed Verified Profiles billing and account administration as one explicit ACF structure with its complete field breakdown.
- Prevented disabled billing fields and their attached user-profile behavior from loading through legacy database or snippet paths.
- Updated the bundled Hexa WP Core to 1.1.2.

### 7.0.0

- Established the stable major baseline for profile registration, claiming, management, display, spawning, shortcodes, and schema.
- Updated shared CPT, ACF, entity, schema, updater, AJAX, rendering, and admin UI infrastructure to Hexa WP Core 1.0.0.
- Preserved existing Profile keys, records, roles, page assignments, program settings, schema identities, and optional HWS entity behavior.

### 6.5.54

- Registered the retired standalone settings slug as a hidden compatibility route so bookmarked URLs redirect to the canonical Profile Settings tab without restoring a legacy menu item.

### 6.5.53

- Restored schema output for legacy profiles that have no Person/Organization category or explicit profile type.
- Rebuilds an empty stored schema at render time without mutating profile records.

### 6.5.52

- Restored the existing Profile Manager admin snippet to the feature registry.
- Ensured the manager role and capabilities whenever the Profile CPT is enabled, while preserving explicitly disabled sites.

### 6.5.51

- Deferred the Core-managed Profile CPT and ACF adapter until `plugins_loaded`, after the shared Core package and autoloader are resolved.

### 6.5.50

- Registered the Profile CPT and active ACF structures through Hexa WP Core with editable labels and public slug.
- Moved legacy Profile program settings into the plugin dashboard.
- Consumed the optional HWS canonical entity while preserving standalone operation.
- Routed schema normalization/injection and plugin updates through Hexa WP Core.
- Removed the unused standalone GitHub updater implementation.
- Updated the bundled Hexa WordPress Plugin Core to 0.19.78.
- Reconciled README, plugin-header, PHP, and Core-version documentation.

## Support

Report issues at <https://github.com/mikeyperes/smp-verified-profiles/issues>.

## License

Proprietary Scale My Publication software unless a source file states otherwise.
