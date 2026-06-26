<?php

namespace smp_verified_profiles;

defined("ABSPATH") || exit;

// Legacy compatibility loader. The consolidated shortcode registry and display
// card renderer now live in shortcodes.php and verified-profile-display-templates.php.
include_once __DIR__ . "/verified-profile-display-templates.php";
include_once __DIR__ . "/shortcodes.php";

if (!function_exists(__NAMESPACE__ . chr(92) . "enable_snippet_verified_profile_shortcodes_legacy_bootstrap")) {
    function enable_snippet_verified_profile_shortcodes_legacy_bootstrap(): void {
        if (function_exists(__NAMESPACE__ . chr(92) . "enable_snippet_verified_profile_shortcodes")) {
            enable_snippet_verified_profile_shortcodes();
        }
    }
}

