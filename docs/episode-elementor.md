# Episode relationship Loop Grid queries

Only explicitly configured shortcodes and Elementor query IDs produce output. No styles or scripts are added. Existing site CSS classes remain unchanged for migration compatibility.

- `smp_verified_profiles_episode_hosts`: ordered `hosts` relationship.
- `smp_verified_profiles_episode_guests`: ordered `profiles` repeater.
- `smp_verified_profiles_profile_episodes`: published reverse-linked episodes, newest first, filtered by podcast content kind.

Empty relationships use post__in=[0]. Outer queries retain found rows. Re-entry guards use try/finally, preserving nested query arguments. Context supports frontend loops and Elementor previews. No dependency on any site helper.
