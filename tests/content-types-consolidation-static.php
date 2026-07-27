<?php

declare( strict_types=1 );

$root = dirname( __DIR__ );

/** @var array<string,string> $files */
$files = [
    'main'       => (string) file_get_contents( $root . '/smp-verified-profiles.php' ),
    'structures' => (string) file_get_contents( $root . '/src/ContentTypes/VerifiedProfileStructures.php' ),
    'dashboard'  => (string) file_get_contents( $root . '/settings-dashboard.php' ),
    'panel'      => (string) file_get_contents( $root . '/settings-dashboard-content-types.php' ),
    'settings'   => (string) file_get_contents( $root . '/generic-functions.php' ),
    'schema'     => (string) file_get_contents( $root . '/snippet-profile-post-wp-admin-functionality.php' ),
    'injector'   => (string) file_get_contents( $root . '/snippet-inject-schema-on-single-profile.php' ),
    'source'     => (string) file_get_contents( $root . '/src/EntitySources/CanonicalProfileSource.php' ),
    'theme_acf'  => (string) file_get_contents( $root . '/register-acf-structure-theme-options.php' ),
];

$checks = [
    'Boots the Core content/ACF adapter after Core package resolution.' => str_contains( $files['main'], "add_action( 'plugins_loaded', [ \\smp_verified_profiles\\ContentTypes\\VerifiedProfileStructures::class, 'boot' ], 1 )" )
        && ! str_contains( $files['main'], '\\smp_verified_profiles\\ContentTypes\\VerifiedProfileStructures::boot();' ),
    'Keeps the Profile post-type key immutable.' => str_contains( $files['structures'], "'key'          => 'profile'" ),
    'Exposes an editable public rewrite slug.' => str_contains( $files['structures'], "'rewrite_slug' => 'profile'" ),
    'Preserves legacy snippet state during migration.' => str_contains( $files['structures'], 'migrate_legacy_acf_settings' )
        && str_contains( $files['structures'], 'sync_legacy_snippet' ),
    'Registers all active ACF groups through Core.' => str_contains( $files['structures'], 'AcfFieldGroupRegistry' )
        && str_contains( $files['theme_acf'], 'smp_vp_register_local_acf_group' )
        && ! str_contains( $files['theme_acf'], 'acf_add_options_page(' ),
    'Adds the Core Custom Post Types dashboard tab.' => str_contains( $files['dashboard'], "'content-types' => 'Custom Post Types'" )
        && str_contains( $files['panel'], 'ContentTypeRenderer' )
        && str_contains( $files['panel'], 'AcfFieldGroupRenderer' ),
    'Moves legacy Profile settings into the plugin dashboard.' => str_contains( $files['dashboard'], "'profile-settings' => 'Profile Settings'" )
        && str_contains( $files['panel'], 'AcfSettingsPanel' )
        && str_contains( $files['main'], "'verified-profiles-settings' === smp_vp_request_value( 'page' )" ),
    'Separates the immutable key from the public URL slug.' => str_contains( $files['settings'], "'slug'         => 'profile'" )
        && str_contains( $files['settings'], "'rewrite_slug'" ),
    'Routes schema normalization and JSON through Core.' => str_contains( $files['schema'], 'SchemaDocumentRenderer' )
        && str_contains( $files['schema'], 'build_verified_profile_schema' ),
    'Routes frontend schema injection through Core.' => str_contains( $files['injector'], 'SchemaInjector' )
        && ! str_contains( $files['injector'], 'echo \'<script type="application/ld+json"\'' ),
    'Consumes the optional HWS canonical entity without requiring one.' => str_contains( $files['source'], 'CanonicalEntityResolver::resolve()' )
        && str_contains( $files['source'], 'return null;' ),
];

$failed = false;
foreach ( $checks as $label => $passed ) {
    echo ( $passed ? 'PASS' : 'FAIL' ) . ': ' . $label . PHP_EOL;
    $failed = $failed || ! $passed;
}

exit( $failed ? 1 : 0 );
