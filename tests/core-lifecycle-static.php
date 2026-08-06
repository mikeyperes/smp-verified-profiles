<?php

declare( strict_types=1 );

$root        = dirname( __DIR__ );
$main        = (string) file_get_contents( $root . '/smp-verified-profiles.php' );
$bootstrap   = (string) file_get_contents( $root . '/src/Bootstrap/Plugin.php' );
$integration = (string) file_get_contents( $root . '/hexa-core-integration.php' );
$ajax        = (string) file_get_contents( $root . '/src/Admin/AdminAjaxModule.php' );
$snippets    = (string) file_get_contents( $root . '/settings-dashboard-snippets.php' );
$shortcodes  = (string) file_get_contents( $root . '/settings-dashboard-shortcodes.php' );
$events      = (string) file_get_contents( $root . '/settings-event-handling.php' );
$user_admin  = (string) file_get_contents( $root . '/snippet-wp-admin-user-page-functionality.php' );

$checks = [
    'The host runtime uses the proper SMP Verified Profiles namespace.' => str_contains( $bootstrap, 'namespace SMP\\VerifiedProfiles\\Bootstrap;' ),
    'The package candidate is registered before the lifecycle hook.' => strpos( $main, 'hexa_plugin_core_register_package' ) < strpos( $main, 'smp_vp_boot_plugin' ),
    'The host creates one PluginContext and one CoreBootstrap.' => 1 === substr_count( $bootstrap, 'new PluginContext(' )
        && 1 === substr_count( $bootstrap, 'new CoreBootstrap(' ),
    'Updater, content types, ACF, snippets, and admin AJAX are Core modules.' => str_contains( $bootstrap, 'new GitHubPluginUpdater' )
        && str_contains( $bootstrap, 'VerifiedProfileStructures::content_types()' )
        && str_contains( $bootstrap, 'VerifiedProfileStructures::acf_groups()' )
        && str_contains( $bootstrap, 'new DeferredSnippetModule(' )
        && str_contains( $bootstrap, 'new AdminAjaxModule' ),
    'Legacy register-only modules use the shared Core adapter.' => str_contains( $bootstrap, 'CoreContracts\\RegisterMethodModule' )
        && ! is_file( $root . '/src/Bootstrap/ModuleAdapter.php' ),
    'The procedural Core bridge contains compatibility delegates only.' => ! str_contains( $integration, 'new AjaxActionRegistry' )
        && ! str_contains( $integration, 'new GitHubPluginUpdater' )
        && str_contains( $integration, 'Plugin::instance()->boot();' ),
    'Snippet UI uses the namespaced host catalog and Core renderer.' => str_contains( $snippets, 'SnippetCatalog::registry()' )
        && str_contains( $snippets, 'new SnippetRenderer()' )
        && ! str_contains( $snippets, 'RecursiveDirectoryIterator' ),
    'Shortcode discovery reads the live WordPress registry rather than scanning source.' => str_contains( $shortcodes, 'function smp_vp_runtime_shortcodes()' )
        && ! str_contains( $shortcodes, 'function smp_vp_scan_php_shortcodes()' )
        && ! str_contains( $shortcodes, 'RecursiveDirectoryIterator' ),
    'Legacy shortcode-value AJAX is registered by the Core-backed host module.' => str_contains( $ajax, "'smp_vp_shortcode_profile_values'" )
        && ! str_contains( $shortcodes, "add_action( 'wp_ajax_smp_vp_shortcode_profile_values'" ),
    'Legacy central handlers are compatibility delegates, not duplicate AJAX implementations.' => str_contains( $events, 'AdminAjaxHandlers::dispatch(' )
        && str_contains( $user_admin, 'AdminAjaxHandlers::dispatch(' )
        && ! str_contains( $events . $user_admin, 'check_ajax_referer(' )
        && ! str_contains( $events . $user_admin, 'wp_send_json_' ),
];

foreach ( $checks as $label => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, "FAIL: {$label}\n" );
        exit( 1 );
    }
}

echo "PASS: Verified Profiles uses one namespaced CoreBootstrap lifecycle with thin compatibility facades.\n";
