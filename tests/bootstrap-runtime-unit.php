<?php

declare( strict_types=1 );

$root = dirname( __DIR__ );

define( 'ABSPATH', '/tmp/smp-vp-wordpress/' );
define( 'WP_PLUGIN_DIR', dirname( $root ) );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );

$GLOBALS['smp_vp_test_hooks'] = [];
$GLOBALS['smp_vp_test_actions_ran'] = [];

function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
    $GLOBALS['smp_vp_test_hooks'][ $hook ][ $priority ][] = [ $callback, $accepted_args ];
}

function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
    add_action( $hook, $callback, $priority, $accepted_args );
}

function do_action( string $hook, mixed ...$args ): void {
    $GLOBALS['smp_vp_test_actions_ran'][ $hook ] = ( $GLOBALS['smp_vp_test_actions_ran'][ $hook ] ?? 0 ) + 1;
    $priorities = $GLOBALS['smp_vp_test_hooks'][ $hook ] ?? [];
    ksort( $priorities, SORT_NUMERIC );
    foreach ( $priorities as $callbacks ) {
        foreach ( $callbacks as [ $callback, $accepted_args ] ) {
            call_user_func_array( $callback, array_slice( $args, 0, $accepted_args ) );
        }
    }
}

function did_action( string $hook ): int {
    return (int) ( $GLOBALS['smp_vp_test_actions_ran'][ $hook ] ?? 0 );
}

function is_admin(): bool { return true; }
function wp_doing_ajax(): bool { return false; }
function wp_doing_cron(): bool { return false; }
function is_plugin_active( string $plugin_file ): bool { return true; }
function plugin_basename( string $file ): string { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function plugin_dir_url( string $file ): string { return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/'; }
function admin_url( string $path = '' ): string { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function get_plugin_data( string $file, bool $markup = true, bool $translate = true ): array {
    unset( $file, $markup, $translate );
    return [
        'Name'        => 'SMP Verified Profiles',
        'Version'     => '8.0.0',
        'Author'      => 'Michael Peres',
        'PluginURI'   => 'https://github.com/mikeyperes/smp-verified-profiles',
        'Description' => 'Verified Profiles',
    ];
}
function sanitize_key( mixed $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ) ?: ''; }
function sanitize_text_field( mixed $value ): string { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( mixed $value ): mixed { return $value; }

require $root . '/smp-verified-profiles.php';
do_action( 'plugins_loaded' );

$runtime = \SMP\VerifiedProfiles\Bootstrap\Plugin::instance();
$checks  = [
    'Core bootstrap completed.' => $runtime->bootstrap() instanceof \Hexa\PluginCore\CoreBootstrap\CoreBootstrap,
    'Plugin context was created.' => $runtime->context() instanceof \Hexa\PluginCore\CoreRuntime\PluginContext,
    'Legacy content-type class resolves to the namespaced host.' => class_exists( '\\smp_verified_profiles\\ContentTypes\\VerifiedProfileStructures', false ),
    'Content-type registration was scheduled.' => ! empty( $GLOBALS['smp_vp_test_hooks']['init'] ),
    'Core and host admin AJAX actions were registered.' => ! empty( $GLOBALS['smp_vp_test_hooks']['wp_ajax_smp_vp_core_updater_force_update_check'] )
        && ! empty( $GLOBALS['smp_vp_test_hooks']['wp_ajax_smp_vp_load_tab'] ),
];

foreach ( $checks as $label => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, "FAIL: {$label}\n" );
        exit( 1 );
    }
}

echo "PASS: Verified Profiles boots one real Core lifecycle with host compatibility aliases.\n";
