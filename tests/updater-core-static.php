<?php

declare( strict_types=1 );

$root        = dirname( __DIR__ );
$main        = (string) file_get_contents( $root . '/smp-verified-profiles.php' );
$integration = (string) file_get_contents( $root . '/hexa-core-integration.php' );

$checks = [
    'Uses the Hexa WP Core plugin updater.' => str_contains( $integration, 'new GitHubPluginUpdater( $updater_config )' ),
    'Uses the Hexa WP Core updater configuration.' => str_contains( $integration, 'UpdaterConfig::from_plugin_file' ),
    'Contains no standalone updater implementation.' => ! is_file( $root . '/GitHub_Updater.php' )
        && ! str_contains( $main, 'get_github_config' )
        && ! str_contains( $main, 'WP_GitHub_Updater' ),
];

foreach ( $checks as $label => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, "FAIL: {$label}\n" );
        exit( 1 );
    }
}

echo "PASS: Verified Profiles delegates plugin updates entirely to Hexa WP Core.\n";
