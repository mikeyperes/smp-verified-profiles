<?php namespace smp_verified_profiles;

function enable_snippet_claim_profile_functionality(){
    // Ensure the action is only added if the function is declared
    add_action('wp_ajax_get_claim_profile_data', __NAMESPACE__ . '\\get_claim_profile_data');
    add_action('wp_ajax_nopriv_get_claim_profile_data', __NAMESPACE__ . '\\get_claim_profile_data');
}

if (!function_exists(__NAMESPACE__ . '\\get_claim_profile_data')) {
    function get_claim_profile_data() {
        if (isset($_POST['profile_id']) && is_numeric($_POST['profile_id'])) {
            $profile_id = intval($_POST['profile_id']);
            $profile_post = get_post($profile_id);

            $verified_profile_settings = get_verified_profile_settings();

            if ($profile_post && $profile_post->post_type === $verified_profile_settings["slug"]) {
                $response = array(
                    'title' => get_the_title($profile_post),
                    'featured_image' => get_the_post_thumbnail_url($profile_post, 'full'),
                    'id' => $profile_post->ID,
                    'slug' => $profile_post->post_name,
                    'link' => get_permalink($profile_post)
                );
                wp_send_json_success($response);
            } else {
                wp_send_json_error('Profile not found');
            }
        } else {
            wp_send_json_error('Invalid profile ID');
        }

        wp_die();
    }

} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\get_claim_profile_data function is already declared", true);