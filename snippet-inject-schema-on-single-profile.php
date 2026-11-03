<?php

namespace smp_verified_profiles;

defined( 'ABSPATH' ) || exit;

/**
 * Adds verified profile pages to HexaWP Core's shared schema block.
 * The nodes are built when the page is viewed; see src/Schema/ProfileSchema.php.
 */
function enable_snippet_inject_schema_on_single_profile(): void {
    static $registered = false;
    if ( $registered ) {
        return;
    }

    \SMP\VerifiedProfiles\Schema\ProfileSchema::register();
    $registered = true;
}
// This file is loaded by the plugin's init callback. Register immediately
// when init is already running, rather than scheduling an elapsed priority.
if ( did_action( 'init' ) ) {
    enable_snippet_inject_schema_on_single_profile();
} else {
    add_action( 'init', __NAMESPACE__ . '\\enable_snippet_inject_schema_on_single_profile' );
}
