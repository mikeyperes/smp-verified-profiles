<?php namespace smp_verified_profiles;

add_action('init',  __NAMESPACE__.'\disable_content_editor_for_profile');

/**
 * Disable the content editor for 'profile' custom post type
 * Removes the WordPress content editor from the 'profile' post type.
 */
function disable_content_editor_for_profile() {
    remove_post_type_support('profile', 'editor');
}

?>