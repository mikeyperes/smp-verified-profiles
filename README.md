# SMP Verified Profiles

> Feature base for HWS Skills. Read before building on this plugin; use or
> extend these features instead of rebuilding them.

**Purpose:** verified profiles (the `profile` post type) with claiming, management, display and schema. **Admin:** WP Admin → Settings → Verified Profiles. **Depends on:** HexaWP Core (bundled); Elementor and WooCommerce for the templates and checkout pieces that use them.

Verified-profile registration, claiming, management, display, spawning, shortcodes, and schema integration for Scale My Publication websites.

## Features

### Profiles
- **Does:** the `profile` post type with its fields, claiming and unclaimed-profile workflows, profile managers, spawning and the profile manager dashboard.
- **Code:** `smp-verified-profiles.php`, `register-acf-*.php`, `profile-manager-dashboard.php`, `verified-profile-spawner.php`.

### Profile types
- **Does:** adds more kinds of profile next to the default Verified Profile, for example Podcasters. Each type is its own post type with its own admin menu, web address and archive, the standard Verified Profile fields, page and schema (optional), and an optional preset field group (Podcaster: show name, role, show links, topics, episode count, booking email). Removing a type keeps its posts. Labels and web addresses of every type are edited on the Custom Post Types tab.
- **Use:** WP Admin → Settings → Verified Profiles → Profile Types. Preset fields read with `[verified_profile field="podcast_name"]`.
- **Extend:** filter `smp_vp_profile_post_types` (every profile post type); helpers `smp_verified_profiles\smp_vp_profile_post_types()` and `smp_vp_is_profile_post_type()`.
- **Code:** `src/ProfileTypes/ProfileTypes.php`, `settings-dashboard-profile-types.php`.

### Article integration post types
- **Does:** chooses the post types the article integration runs on (posts, press releases, any public post type): the profiles box and profile detection in the editor, profile creation, the "in this article" section, and each profile's "featured in" and "articles written" lists. Defaults to posts and press releases, as before.
- **Use:** Profile Types tab → "Where the article integration runs".
- **Extend:** filter `smp_vp_integration_post_types`; helper `smp_verified_profiles\smp_vp_integration_post_types()`.
- **Code:** `src/ProfileTypes/ProfileTypes.php`, `register-acf-structures.php`, `snippet-post-functionality.php`, `verified-profile-spawner.php`.

### Profile shortcodes
- **Use:** `[display_single_profile_education]`, `[display_single_profile_organizations_founded]`, `[display_single_profile_press_releases]`, `[display_single_profile_article_written_by]`, `[display_single_profile_articles_featured_in]`, `[display_single_profile_text_based_social_profiles]`, `[display_homepage_profiles]`, `[display_single_post_mentioned_in_article]`, `[display_theme_footer_text_social_links]`, `[display_single_profile_validate_schema_button]`, `[display_profiles_featured_in_single_post]`, `[display_profile_quick_contact]`, `[display_profile_current_residence]`, `[display_profile_location_born]`, `[display_profile_notable_mentions]`, `[get_profile_field]`, `[profiles_in_articles]`, `[verified_profile_page]`, `[verified_profile field="..."]`, `[post_mentioned_vocabulary]`, `[vocabulary_mentioned_posts]`, `[wiki_mentioned_posts]`. Each is listed with an example on the Shortcodes tab.
- **Code:** `shortcodes.php`, `settings-dashboard-shortcodes.php`.

### Features (each off by default)
- **Does:** the Features tab (Settings → Verified Profiles → Features) switches on, one by one: profile managers (their Profiles Dashboard, and with payments a "Buy a verified profile" button that adds a paid profile credit), claiming a profile from the site, and payments. A site that already had profile managers keeps their dashboard on.
- **Code:** `src/Features/FeatureSettings.php`, `settings-dashboard-features.php`, `src/Features/FeaturesAdmin.php`.

### Claim this profile
- **Use:** `[smp_vp_claim_profile]` (optional `profile="<ID>"`, `label="..."`) on the profile page. On an unclaimed profile (author: the Unclaimed Profiles user) it shows a "Claim this profile" button and a short form (name, email, note); elsewhere nothing. Free claims wait for an admin's Approve or Reject on the Features tab; with payments on and a claim price set, the claimer pays through Stripe and the claim approves itself. Approving makes the claimer the profile's author and a Verified Profile Manager, creating and emailing their account when needed.
- **Code:** `src/Features/ClaimProfile.php` (claims stored on the profile in `_smp_vp_claims`).

### Elementor dynamic tags
- **Use:** in Elementor, group "Verified Profiles": **Profile field (plain text)** shows one field of the current post (a profile in a profile loop) with HTML removed, optionally hidden when it only repeats the name (a role equal to the person's name); **Linked profile** shows "<label> <name> →" linking to the profile whose ID a field holds (for example an episode's `profiles_0_profile`, label "Guest"), with markup `<a><span class="mpp-guest-k">…</span><span class="mpp-guest-n">…</span><span class="mpp-guest-arrow">→</span></a>`. They replace JetEngine dynamic fields.
- **Code:** `src/Elementor/`.

### Payments (Stripe through HWS Base Tools)
- **Does:** profile and claim payments run through HWS Base Tools' one Stripe connection (Payments tab there), once or as a subscription, using a Stripe price ID per item. A paid claim is approved by Stripe's `checkout.session.completed` event; a paid profile adds one to the buyer's `smp_vp_profile_credits`. WooCommerce is no longer used.
- **Code:** `src/Features/ProfilePayments.php`.

### Profile dashboard links
- **Does:** the profile manager dashboard's Additional Links (apply for a new profile).
- **Extend:** action `smp_verified_profiles_dashboard_links` prints more links there; SMP Contributor Network adds the council apply link.
- **Code:** `profile-manager-dashboard.php`.

### Schema
- **Does:** ProfilePage schema on each profile page (see Schema below).
- **Code:** `src/Schema/ProfileSchema.php`.

### Leadership Council (moved)
- The council settings, the "Price Leadership Council" and "Is Council Member" user fields, `[contributor_network]`, `[display_profile_council_banner]`, the council checkout price and apply link moved to SMP Contributor Network (`mikeyperes/smp-contributor-network`) in 8.3.0. Install it on sites that use them. The contributor check in `generic-functions.php` still reads `is_council_member`.

## Identity

- Repository: `mikeyperes/smp-verified-profiles`
- Plugin slug: `smp-verified-profiles`
- Namespace: `smp_verified_profiles`
- Version: `8.4.1`
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

Each profile page gets a ProfilePage about the profile's Person or Organization (plus image and breadcrumb), built when the page is viewed in `src/Schema/ProfileSchema.php` with Hexa WP Core's schema library and added to Core's shared `hexa-schema` block (`SchemaPage` source `smp-verified-profiles`, priority 10). The type comes from the profile type field, the category, or organization-only fields. Nothing is stored in the post. Rank Math's schema is controlled only by HWS Base Tools.

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
| Hexa WP Core bundle | 3.46.2 |

ACF Pro is not required: the profile field structures run on Hexa WP Core custom fields, which use ACF when it is active and store the same data natively when it is not. Elementor/JetEngine integrations require those plugins only when their templates are used.

## Installation

Install the repository as `wp-content/plugins/smp-verified-profiles`, activate `smp-verified-profiles.php`, and open **Settings > Verified Profiles**. Existing profile posts, managers, page assignments, and ACF values are reused in place.

## Development

Run all static regression contracts with:

```bash
for file in tests/*.php; do php "$file" || exit 1; done
```

Live release verification must also exercise claiming, spawning, role restrictions, templates, shortcodes, and schema output through their visible WordPress workflows.

## Changelog

### 8.6.0
- Elementor dynamic tags "Profile field (plain text)" and "Linked profile" (group Verified Profiles), so profile cards no longer need JetEngine.

### 8.5.0
- Profile types (for example podcast profiles) are back: each its own post type with its own section, beside verified profiles.
- New Features tab, every feature off by default: profile managers (dashboard and paid profiles), claim this profile (`[smp_vp_claim_profile]`, free with admin approval or paid through Stripe) and payments through HWS Base Tools' Stripe connection.
- Removed the unused WooCommerce price and Stripe snippets; the plugin check no longer asks for JetEngine, WooCommerce or WP User Avatars and asks for Simple Local Avatars.
- The dashboard counts profile managers by their real role and shows a count per profile type.
- `error_log` is no longer tracked. Bundles Hexa WP Core 3.47.0.

### 8.4.1
- `[verified_profile]` and the other profile field shortcodes read the current profile on every profile type, not only the default one.
- The article profiles field shows on every chosen post type, including ones registered late in loading (press releases).

### 8.4.0
- Profile types: add more kinds of profile (for example Podcasters) on the new Profile Types tab. Each is its own post type with the standard Verified Profile fields, page and schema, plus an optional Podcaster field group. Profile lookups, the profile field on articles, "in this article", schema and spawning matches cover every profile type.
- The article integration runs on the post types chosen on the Profile Types tab (default posts and press releases). The profiles field on articles now also shows on press releases; the Profile Generation tab's own post-type list is replaced by this one setting.
- Bundles Hexa WP Core 3.46.2.

### 8.3.0
- Leadership Council pieces moved to SMP Contributor Network: program settings and its second copy, the two council user fields, `[contributor_network]`, `[display_profile_council_banner]`, the council product price and the council apply link. New action `smp_verified_profiles_dashboard_links` for links other plugins add to the profile dashboard.
- Bundles Hexa WP Core 3.34.1.

### 8.2.0

- Profile pages get a ProfilePage about the profile's Person or Organization, built when the page is viewed with Hexa WP Core 3.22.0's schema library and printed in Core's shared schema block, linked to the site's organization and website.
- Removed the schema copy saved into each profile on save, its Schema Markup fields, the batch reprocess code and the unused reprocess screen.

### 8.1.2

- Legacy layouts that supply their own "In This Article" heading widget tagged `display_single_post_mentioned_in_article` keep that heading, and the plugin's section title is hidden so the heading is not shown twice.

### 8.1.1

- When a post has no verified profiles, the empty "In This Article" state now also hides companion Elementor widgets tagged `display_single_post_mentioned_in_article` (for example a separate heading widget), matching the pre-8.0 behavior on legacy layouts.

### 8.1.0

- Removed the ACF Pro requirement: the plugin no longer stops loading without ACF, every field group, field read/write, row loop, options page, form and ACF hook runs on Hexa WP Core 3.4.6 `Fields` (ACF when active, native otherwise), and field-group registration waits for `acf/init` or `init` through `FieldGroups::ready()`.
- Removed ACF Pro from the plugin checks and system status, which now report the active field mode.
- Synchronized the bundled Hexa WordPress Plugin Core to 3.4.6.

### 8.0.2

- Let the single-post "No plugin design" mode keep saved Elementor Loop Item markup while omitting the plugin's section and grid CSS, so publication-owned Elementor styling can control the complete presentation.
- Preserved the historical responsive compatibility grid for placements that continue to use a plugin design.

### 8.0.1

- Synchronized the bundled Hexa WordPress Plugin Core to 3.0.1, including the standalone schema relationship fix.

### 8.0.0

- Coordinated major release for the consolidated Verified Profiles structure, template, lifecycle, schema, and admin infrastructure, synchronized with Hexa WordPress Plugin Core 3.0.0.

### 7.1.4

- Updated the bundled Hexa WP Core to 2.1.4, preserving the one-column template-library fix while consolidating lifecycle, registries, updater, and admin AJAX dispatch under the shared Core bootstrap.

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
