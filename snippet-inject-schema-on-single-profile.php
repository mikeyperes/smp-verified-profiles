<?php namespace smp_verified_profiles;
function enable_snippet_inject_schema_on_single_profile()
{

add_action('wp_head', __NAMESPACE__.'\inject_schema_on_single_profile');
}
/**
 * Inject schema markup into the head section of a 'profile' post type single view.
 */
if (!function_exists(__NAMESPACE__ . '\\inject_schema_on_single_profile')) {
    function inject_schema_on_single_profile() {
  
        $verified_profile_settings = get_verified_profile_settings();
        //|| is_singular($verified_profile_settings["entity"]) 
        if (is_singular($verified_profile_settings["slug"])) { 
            global $post;
            $schema_json = get_field('schema_markup', $post->ID);
            if ($schema_json) {
                echo "<script type='application/ld+json'>" . $schema_json . "</script>";
            }
        }
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\inject_schema_on_single_profile function is already declared", true);
}
