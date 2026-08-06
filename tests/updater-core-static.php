<?php

declare( strict_types=1 );

$root        = dirname( __DIR__ );
$main        = (string) file_get_contents( $root . '/smp-verified-profiles.php' );
$integration = (string) file_get_contents( $root . '/hexa-core-integration.php' );
$runtime     = (string) file_get_contents( $root . '/src/Bootstrap/Plugin.php' );
$updates     = (string) file_get_contents( $root . '/src/Infrastructure/Updates.php' );
$plugin_info = (string) file_get_contents( $root . '/settings-dashboard-plugin-info.php' );

$checks = [
    'Uses the Hexa WP Core plugin updater inside CoreBootstrap.' => str_contains( $runtime, 'add_module( new GitHubPluginUpdater( Updates::plugin_config() ) )' ),
    'Uses the Hexa WP Core updater configuration.' => str_contains( $updates, 'UpdaterConfig::from_plugin_file' ),
    'Keeps updater compatibility functions as delegation facades.' => str_contains( $integration, 'return class_exists( UpdaterConfig::class ) ? Updates::plugin_config() : null;' )
        && ! str_contains( $integration, 'wp_update_themes();' ),
    'Contains no standalone updater implementation.' => ! is_file( $root . '/GitHub_Updater.php' )
        && ! str_contains( $main, 'get_github_config' )
        && ! str_contains( $main, 'WP_GitHub_Updater' )
        && ! str_contains( $plugin_info, 'ajax_download_specific_version' )
        && ! str_contains( $plugin_info, 'ajax_direct_update_plugin' )
        && ! str_contains( $plugin_info, 'ajax_download_plugin_zip' )
        && str_contains( $plugin_info, 'new UpdaterPanelRenderer' ),
];

foreach ( $checks as $label => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, "FAIL: {$label}\n" );
        exit( 1 );
    }
}

echo "PASS: Verified Profiles delegates plugin updates entirely to Hexa WP Core.\n";
