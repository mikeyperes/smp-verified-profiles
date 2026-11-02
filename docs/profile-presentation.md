# Profile presentation

Profile display is opt-in through eight shortcodes. No CSS or JavaScript is loaded. Existing mpp CSS classes are retained as public theme styling hooks; there are no site IDs or URLs. The optional Podcast integration supplies profile context and episode relationships. Without it, profile fields still work and episode lists are empty.

- `smp_profile_page_field` replaces `mpp_profile_page_field`.
- `smp_profile_page_heading` replaces `mpp_profile_page_heading`.
- `smp_profile_page_caption` replaces `mpp_profile_page_caption`.
- `smp_profile_page_meta` replaces `mpp_profile_page_meta`.
- `smp_profile_page_social_links` replaces `mpp_profile_page_social_links`.
- `smp_profile_page_archive_label` replaces `mpp_profile_page_archive_label`.
- `smp_profile_links` replaces `mpp_profile_links`.
- `smp_profile_monogram` replaces `mpp_profile_monogram`.
- `smp_profile_biography_short_by_id` replaces `mpp_elementor_profile_biography_short_by_id`.
- `smp_profile_link_by_id` replaces `mpp_elementor_profile_link_by_id`.
- `smp_profile_role` replaces `mpp_elementor_profile_role`.

Shortcodes accept `post_id`. Field accepts `name` (biography, biography_short, job_title, location, personal_current_residence_name). Heading accepts `prefix` (About). Caption retains the Guest label. Monogram accepts `variant` (chip, dossier, rail). Profile links use mpp-profile-links and mpp-profile-link; monograms use mpp-profile-monogram; metadata uses mpp-profile-page-meta; social links use mpp-profile-page-socials. The plugin supplies no styling.
