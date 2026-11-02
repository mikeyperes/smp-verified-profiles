<?php namespace smp_verified_profiles;

add_action('init',  __NAMESPACE__.'\disable_content_editor_for_profile');

/**
 * Disable the content editor for 'profile' custom post type
 * Removes the WordPress content editor from the 'profile' post type.
 */
function disable_content_editor_for_profile() {
    foreach (\SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::post_types() as $type) { remove_post_type_support($type, 'editor'); }
}

?>