<?php namespace smp_verified_profiles;

function enable_snippet_profile_post_wp_admin_functionality()
{
// Hook into the save post action
add_action('save_post', __NAMESPACE__.'\save_acf_post_id');

add_action('admin_footer', __NAMESPACE__.'\enforce_featured_image_with_jquery');
add_action('admin_footer', __NAMESPACE__.'\custom_quick_edit_javascript');
add_action('init', __NAMESPACE__.'\remove_content_editor_from_profile_cpt');
add_action('post_submitbox_misc_actions', __NAMESPACE__.'\wpadmin_profile_display_associated_profiles', 10, 1);
add_action('init',  __NAMESPACE__.'\disable_content_editor_for_profile');




}

/**
 * Disable the content editor for 'profile' custom post type
 * Removes the WordPress content editor from the 'profile' post type.
 */
function disable_content_editor_for_profile() {
    $verified_profile_settings = get_verified_profile_settings();
    remove_post_type_support($verified_profile_settings["slug"], 'editor');
}



//dummy function, delete 

function check_plugin_acf()
{return true;}

/**
 * Save ACF post ID into the 'post_id' field when the post is saved.
 * 
 * @param int $post_id The ID of the post being saved.
 */
if (!function_exists(__NAMESPACE__ . '\\save_acf_post_id')) {
    function save_acf_post_id($post_id) {
        if (!check_plugin_acf()) {
            return;
        }
        \Hexa\PluginCore\Fields\Field::update('post_id', $post_id, $post_id);
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\save_acf_post_id function is already declared", true);
}



/**
 * Enforces setting a featured image before publishing a profile post.
 */
if (!function_exists(__NAMESPACE__ . '\\enforce_featured_image_with_jquery')) {
    function enforce_featured_image_with_jquery() {
        global $typenow;
        if (false && $typenow == 'profile') {  // Replace 'profile' with your actual custom post type
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#publish').click(function(e) {
                        if ($('#set-post-thumbnail img').length == 0) {
                            e.preventDefault();
                           alert('You must set a featured image (company logo or headshot) before publishing this profile.');
                            return false;
                        }
                    });
                });
            </script><?php
        }
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\enforce_featured_image_with_jquery function is already declared", true);
}

/**
 * Custom quick edit functionality for removing fields from the quick edit screen for the 'profile' post type.
 */
if (!function_exists(__NAMESPACE__ . '\\custom_quick_edit_javascript')) {
    function custom_quick_edit_javascript() {
        global $current_screen;

        // Check if on the edit screen of the 'profile' post type and if the user has the 'verified_profile_manager' role
        if ($current_screen->id == 'edit-profile' && is_profile_manager(true)) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Remove the author field from quick edit
                    $('#the-list').on('click', '.editinline', function() {
                        setTimeout(function() {
                            var editRow = $('#edit-' + $('.inline-edit-row').attr('id').substring(5));
                            if (editRow.find('.inline-edit-author').length > 0) {
                                editRow.find('.inline-edit-author').remove();
                            }
                        }, 50);
                    });

                    // Remove the category field from quick edit
                    $('#the-list').on('click', '.editinline', function() {
                        setTimeout(function() {
                            var editRow = $('#edit-' + $('.inline-edit-row').attr('id').substring(5));
                            if (editRow.find('.inline-edit-categories').length > 0) {
                                editRow.find('.inline-edit-categories').remove();
                            }

                            // Hide the slug edit section
                            $('.inline-edit-row').find('label:has(.title:contains("Slug"))').remove();
                            // Hide the tags edit section
                            $('.inline-edit-row').find('.inline-edit-tags').remove();
                        }, 50);
                    });

                    // Remove the slug edit section
                    $('#edit-slug-box').remove();
                });
            </script><?php
        }
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\custom_quick_edit_javascript function is already declared", true);
}

/**
 * Removes content editor from the 'profile' custom post type.
 */
if (!function_exists(__NAMESPACE__ . '\\remove_content_editor_from_profile_cpt')) {
    function remove_content_editor_from_profile_cpt() {
        // Remove 'editor' support from 'profile' CPT
        remove_post_type_support('profile', 'editor');
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\remove_content_editor_from_profile_cpt function is already declared", true);
}

/**
 * Displays associated profiles in the Publish meta box when editing a 'profile' post.
 *
 * @param WP_Post $post The current post object.
 */
if (!function_exists(__NAMESPACE__ . '\\wpadmin_profile_display_associated_profiles')) {
    function wpadmin_profile_display_associated_profiles($post) {
        if (!current_user_can('administrator')) return;
        global $wpdb;

        // Ensure this function runs only for the custom post type 'profile'
        if ('profile' !== $post->post_type) {
            return;
        }

        $profile_id = $post->ID;
        $meta_key_like = 'unclaimed_profiles_%_profile';

        // Custom SQL query
        $sql = "
            SELECT DISTINCT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE %s 
            AND meta_value = %d
        ";

        // Prepare and execute the query
        $prepared_sql = $wpdb->prepare($sql, $meta_key_like, $profile_id);
        $user_ids = $wpdb->get_col($prepared_sql);

        // Start building the HTML output
        $html = '<div id="major-publishing-actions" style="overflow:hidden;">';
        $html .= '<div id="publishing-action" style="text-align:left !important;float:left !important;">';
        $html .= '<div><b>Associated with:</b><br />';

        // Check if users found
        if (empty($user_ids)) {
            $html .= "No association";
        } else {
            foreach ($user_ids as $user_id) {
                $user = get_userdata($user_id);
                $html .= esc_html($user->display_name) . ' | <a target=_blank href="' . esc_url(get_edit_user_link($user_id)) . '">edit</a><br />';
            }
        }

        $html .= '</div>';
        $html .= '<div><br /><b>Claimed By:</b><br />';

        // Get the author data
        $author_id = $post->post_author;
        $author = get_userdata($author_id);

        // Check if author exists
        if ($author) {
            $author_name = esc_html($author->display_name);
            $edit_link = esc_url(get_edit_user_link($author_id));
            $html .= "{$author_name} | <a href='{$edit_link}' target='_blank'>edit</a>";
        } else {
            $html .= "No author";
        }

        $html .= '</div>';
        $html .= '</div></div>';

        // Output the HTML
        echo $html;
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\wpadmin_profile_display_associated_profiles function is already declared", true);
}
