# SMP Verified Profiles

Verified-profile registration, claiming, management, display, spawning, shortcodes, and schema integration for Scale My Publication websites.

## Identity

- Repository: `mikeyperes/smp-verified-profiles`
- Plugin slug: `smp-verified-profiles`
- Namespace: `smp_verified_profiles`
- Version: `6.5.51`
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
- Display cards, page/listing templates, shortcodes, and snippets.
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
| Hexa WP Core bundle | 0.19.78 |

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
