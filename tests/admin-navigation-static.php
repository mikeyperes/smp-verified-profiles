<?php

$root       = dirname( __DIR__ );
$dashboard  = (string) file_get_contents( $root . '/settings-dashboard.php' );
$components = (string) file_get_contents( $root . '/settings-dashboard-components.php' );
$overview   = (string) file_get_contents( $root . '/settings-dashboard-overview.php' );
$core       = trim( (string) file_get_contents( $root . '/lib/hexa-wordpress-plugin-core/VERSION' ) );

$checks = [
    'Uses Hexa Core TabDefinition.' => str_contains( $dashboard, 'new TabDefinition(' ),
    'Uses Hexa Core TabRegistry.' => str_contains( $dashboard, 'new TabRegistry()' ),
    'Uses Hexa Core HostTabsRenderer.' => str_contains( $dashboard, 'new HostTabsRenderer()' ),
    'Uses the grouped sidebar layout.' => str_contains( $dashboard, "'layout'          => 'sidebar'" ),
    'Supplies grouped navigation.' => str_contains( $dashboard, "'groups'          => smp_vp_dashboard_tab_groups( \$tabs )" ),
    'Supplies sidebar identity.' => str_contains( $dashboard, "'sidebar_identity' => smp_vp_sidebar_identity()" ),
    'Keeps the legacy Display Cards URL as an alias.' => str_contains( $dashboard, "'display-cards' => 'features'" ),
    'Does not render Display Cards as a duplicate switch route.' => ! str_contains( $dashboard, "case 'display-cards':" ),
    'Groups profile display and generation routes together.' => str_contains( $dashboard, "[ 'label' => 'Profiles', 'tabs' => [ 'features', 'profile-pages', 'pages', 'spawning-api' ] ]" ),
    'Groups maintenance routes under System.' => str_contains( $dashboard, "[ 'label' => 'System', 'tabs' => [ 'system-checks', 'plugins' ] ]" ),
    'Groups technical routes under Developer.' => str_contains( $dashboard, "[ 'label' => 'Developer', 'tabs' => [ 'snippets', 'shortcodes', 'hexa-core' ] ]" ),
    'Removes retired host tab CSS.' => ! str_contains( $components, '.smp-tab-btn' ),
    'Removes dead legacy tab links.' => ! str_contains( $overview, "jQuery('.smp-tab-btn" ),
    'Vendors the current Hexa WP Core package.' => '0.19.65' === $core,
];

$failed = false;
foreach ( $checks as $label => $passed ) {
    echo ( $passed ? 'PASS' : 'FAIL' ) . ': ' . $label . PHP_EOL;
    $failed = $failed || ! $passed;
}

exit( $failed ? 1 : 0 );
