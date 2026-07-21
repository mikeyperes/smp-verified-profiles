<?php

declare(strict_types=1);

$root   = dirname( __DIR__ );
$source = (string) file_get_contents( $root . '/verified-profile-display-templates.php' );

$checks = [
    'Profile Cards uses the shared Core collapsible renderer.' => str_contains( $source, 'use Hexa\\PluginCore\\WpAdminComponents\\CoreUi;' )
        && substr_count( $source, 'CoreUi::collapsible([' ) >= 2,
    'Both top-level feature cards have stable query keys.' => str_contains( $source, '"query_key" => "verified-profiles"' )
        && str_contains( $source, '"query_key" => "template-library"' ),
    'Both top-level feature cards default collapsed.' => substr_count( $source, '"open" => false' ) >= 2,
    'The retired custom top-level panel markup is no longer rendered.' => ! str_contains( $source, '<div class="smp-vp-panel">' ),
    'Existing settings and live template selectors remain intact.' => str_contains( $source, 'id="smp-vp-display-save"' )
        && str_contains( $source, 'id="smp-vp-current-homepage"' )
        && str_contains( $source, 'id="smp-vp-current-post"' )
        && str_contains( $source, 'smp-vp-template-action' ),
];

foreach ( $checks as $message => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, 'FAIL: ' . $message . "\n" );
        exit( 1 );
    }
}

echo "PASS: Verified Profiles feature cards use query-backed Hexa Core collapsibles.\n";
