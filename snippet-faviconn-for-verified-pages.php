<?php namespace smp_verified_profiles;

function enable_snippet_faviconn_for_verified_pages(){
    // Add the custom function to wp_head action hook
    add_action('wp_head', __NAMESPACE__ . '\\custom_favicon_for_verified_pages');
}

if (!function_exists(__NAMESPACE__ . '\\custom_favicon_for_verified_pages')) {
    function custom_favicon_for_verified_pages() {
        // Check if the current page is one of the specified pages
        if (is_page('claim-your-profile') || 
            is_page('her-forward-verified-profiles') || 
            is_page('her-forward-verified-profile-badges')) {
            // Bug: the snippet printed another site's icon on a profile page.
            // Fix: use the site's saved profile icon.
            // Why this way: this override is independent of the ordinary WordPress site icon.
            $url = (string) \SMP\VerifiedProfiles\Features\FeatureSettings::all()['legacy_profile_icon_url'];
            if ( '' !== $url ) {
                echo '<link rel="icon" type="image/png" href="' . esc_url( $url ) . '" />';
            }
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\custom_favicon_for_verified_pages function is already declared", true);