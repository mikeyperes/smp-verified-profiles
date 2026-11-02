<?php
// Run with wp eval-file tests/episode-queries.php on an activated installation.
foreach (['episode_hosts','episode_guests','profile_episodes'] as $suffix) {
    if (!has_action('elementor/query/smp_verified_profiles_' . $suffix)) {
        throw new RuntimeException('Missing relationship query: ' . $suffix);
    }
}
echo "Episode relationship queries registered.\n";
