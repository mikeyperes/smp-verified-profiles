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
            // Output the custom favicon HTML tag
            echo '<link rel="icon" type="image/png" href="https://herforward.com/wp-content/uploads/2024/01/faviconn-e1704592443814.png" />';
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\custom_favicon_for_verified_pages function is already declared", true);