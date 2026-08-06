<?php

$root    = dirname( __DIR__ );
$plugin  = file_get_contents( $root . '/smp-verified-profiles.php' );
$roles   = file_get_contents( $root . '/src/UserRoles/VerifiedProfileManagerRole.php' );
$legacy  = file_get_contents( $root . '/snippet-adjust-wp-admin-for-profile-managers.php' );
$runtime = file_get_contents( $root . '/src/Bootstrap/Plugin.php' );

$checks = [
    'Plugin autoloads the namespaced role manager' => str_contains( $roles, 'namespace SMP\\VerifiedProfiles\\UserRoles;' ),
    'Plugin boots the role manager through CoreBootstrap' => str_contains( $runtime, 'add_module( new VerifiedProfileManagerRole() )' ),
    'Profile Manager snippet is restored to the registry' => str_contains( $plugin, "'id'               => 'enable_snippet_adjust_wp_admin_for_profile_managers'" ),
    'Role initialization is gated by the Profile CPT option' => str_contains( $roles, "get_option( 'register_profile_custom_post_type', false )" ),
    'Only a missing admin-feature option is initialized' => str_contains( $roles, "'__smp_vp_missing__' === get_option" ),
    'Existing role capabilities are not rewritten repeatedly' => str_contains( $roles, "if ( ! \$role->has_cap( \$capability ) )" ),
    'Legacy role callbacks delegate to one role manager' => substr_count( $legacy, 'UserRoles\\VerifiedProfileManagerRole::ensure();' ) === 2,
];

foreach ( $checks as $label => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, "FAIL: {$label}\n" );
        exit( 1 );
    }
    echo "PASS: {$label}\n";
}
