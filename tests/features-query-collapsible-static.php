<?php

declare(strict_types=1);

$root   = dirname( __DIR__ );
$source = (string) file_get_contents( $root . '/verified-profile-display-templates.php' );
$pages  = (string) file_get_contents( $root . '/verified-profile-page-templates.php' );

$checks = [
    'Profile Cards uses the shared Core collapsible renderer.' => str_contains( $source, 'use Hexa\\PluginCore\\WpAdminComponents\\CoreUi;' )
        && substr_count( $source, 'CoreUi::collapsible([' ) >= 2,
    'Both top-level feature cards have stable query keys.' => str_contains( $source, '"query_key" => "verified-profiles"' )
        && str_contains( $pages, "'query_key'   => 'profile-pages'" ),
    'Profile card and profile-page controls use Core collapsibles.' => str_contains( $source, 'CoreUi::collapsible([' )
        && str_contains( $pages, 'echo CoreUi::collapsible(' ),
    'The retired custom top-level panel markup is no longer rendered.' => ! str_contains( $source, '<div class="smp-vp-panel">' ),
    'Existing settings and live template selectors remain intact.' => str_contains( $source, 'id="smp-vp-display-save"' )
        && str_contains( $source, 'id="smp-vp-current-homepage"' )
        && str_contains( $source, 'id="smp-vp-current-post"' )
        && str_contains( $source, 'smp_vp_display_template_selector([' ),
    'Profile-page settings render below Profile Cards on Features.' => str_contains( $source, 'smp_vp_profile_page_render_settings();' ),
    'The standalone Template Library UI is retired.' => ! str_contains( $pages, 'Template Library' ),
];

foreach ( $checks as $message => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, 'FAIL: ' . $message . "\n" );
        exit( 1 );
    }
}

echo "PASS: Verified Profiles feature cards use query-backed Hexa Core collapsibles.\n";
