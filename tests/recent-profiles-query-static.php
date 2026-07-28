<?php

declare( strict_types=1 );

$source = (string) file_get_contents( dirname( __DIR__ ) . '/verified-profile-display-templates.php' );

$start = strpos( $source, 'function smp_vp_display_homepage_query_args' );
$end   = false === $start ? false : strpos( $source, 'function smp_vp_display_homepage_ids', $start );
$query = false === $start || false === $end ? '' : substr( $source, $start, $end - $start );

$checks = [
    'Homepage profiles use the configured Profile post type.' => str_contains( $query, 'smp_vp_display_profile_post_type()' ),
    'Homepage profiles include published posts without requiring an attached article.' => str_contains( $query, '"post_status" => "publish"' )
        && ! str_contains( $query, 'post__in' )
        && ! str_contains( $query, 'article' )
        && ! str_contains( $query, 'profiles_' ),
    'Most recent means publish date descending with a deterministic ID fallback.' => str_contains( $query, '"orderby" => ["date" => "DESC", "ID" => "DESC"]' ),
    'Thumbnail filtering remains optional.' => str_contains( $query, 'if ($require_thumbnail)' )
        && str_contains( $query, '"_thumbnail_id"' ),
    'Profile saves invalidate the homepage and archive surfaces.' => str_contains( $source, 'smp_vp_display_invalidate_profile_surfaces' )
        && str_contains( $source, '"litespeed_purge_url"' )
        && str_contains( $source, '"_elementor_element_cache"' ),
];

$failed = false;
foreach ( $checks as $label => $passed ) {
    echo ( $passed ? 'PASS' : 'FAIL' ) . ': ' . $label . PHP_EOL;
    $failed = $failed || ! $passed;
}

exit( $failed ? 1 : 0 );
