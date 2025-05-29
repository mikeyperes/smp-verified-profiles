<?php namespace smp_verified_profiles;

function snippet_post_functionality(){
add_action('admin_footer', __NAMESPACE__.'\custom_post_admin_footer_scripts');
add_action('save_post',  __NAMESPACE__.'\process_profiles_on_save', 10, 3);
add_action('admin_footer-post.php',  __NAMESPACE__.'\add_edit_profile_links_inside_label');
}
/**
 * Add custom JavaScript functionality to 'post' post type in the admin footer
 * Adds 'Process profiles' button and CMD + Y shortcut functionality to handle profiles.
 */

function custom_post_admin_footer_scripts() {
    global $pagenow, $typenow;

    // Check if we're on a 'post' post type edit page
    if ($typenow != 'post' || ($pagenow != 'post-new.php' && $pagenow != 'post.php')) {
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Add 'Process profiles' button next to ACF field
            $('.acf-field[data-name="pending_profiles"]').after('<button id="process-profiles" class="button button-primary">Process profiles</button>');

            // Handle click event for 'Process profiles' button
            $('#process-profiles').click(function(e) {
                e.preventDefault();

                // Get post status and update it if not published
                var postStatus = $('#post_status').val();
                if (postStatus !== 'publish') {
                    $('#post_status').val('draft');
                }

                $('#save-post').click(); // Trigger save/update post
            });

            // Function to handle CMD + Y keyboard shortcut
            function handleCmdY(editor) {
                editor.on('keydown', function(e) {
                    // CMD + Y (Ctrl + Y on Windows)
                    if (e.keyCode === 89 && (e.ctrlKey || e.metaKey)) {
                        e.preventDefault();

                        // Get selected text and trim it
                        var selectedText = editor.selection.getContent({format: 'text'}).trim();
                        var nameExists = false;

                        // Check if the selected text is already in the 'pending_profiles' ACF field
                        $('.acf-field[data-name="pending_profiles"] .acf-row:not(.acf-clone)').each(function() {
                            var currentName = $(this).find('.acf-field[data-name="name"] input').val().toUpperCase();
                            if (currentName === selectedText.toUpperCase()) {
                                nameExists = true;
                                return false;
                            }
                        });

                        // If the name doesn't exist, add it to 'pending_profiles'
                        if (selectedText && !nameExists) {
                            $('.acf-field[data-name="pending_profiles"] .acf-button[data-event="add-row"]').click();
                            setTimeout(function() {
                                var $lastRow = $('.acf-field[data-name="pending_profiles"] .acf-row:not(.acf-clone)').last();
                                $lastRow.find('.acf-field[data-name="name"] input').val(selectedText).change();
                            }, 100);
                            alert('Added ' + selectedText);
                        }
                    }
                });
            }

            // Attach CMD + Y handler to TinyMCE editor
            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.on('AddEditor', function(e) { handleCmdY(e.editor); });
                tinyMCE.editors.forEach(handleCmdY);
            }
        });
    </script>
    <?php
}


/**
 * Process profiles when saving a 'post' post type
 * Moves pending profiles to the 'profiles' ACF field and clears 'pending_profiles'.
 *
 * @param int $post_id The ID of the post being saved.
 * @param WP_Post $post The post object.
 * @param bool $update Whether this is an existing post being updated.
 */
function process_profiles_on_save($post_id, $post, $update) {
    // Skip autosave and if not a 'post' post type or ACF plugin is inactive
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || $post->post_type !== 'post' || !check_plugin_acf()) {
        return;
    }

    // Get pending profiles
    $pending_profiles = get_field('pending_profiles', $post_id);

    if (!empty($pending_profiles)) {
        $existing_profile_names = [];
        $existing_profiles = get_field('profiles', $post_id);

        // Collect names of existing profiles
        if (!empty($existing_profiles)) {
            foreach ($existing_profiles as $existing_profile) {
                if (isset($existing_profile['profile']) && $existing_profile['profile'] instanceof WP_Post) {
                    $existing_profile_names[] = get_the_title($existing_profile['profile']->ID);
                }
            }
        }

        // Assign 'unclaimed' user as post author
        $user = get_user_by('slug', 'unclaimed');
        $user_id = $user->ID;

        // Process pending profiles
        foreach ($pending_profiles as $profile_data) {
            $name = sanitize_text_field($profile_data['name']);

            // Check if profile is new
            if (!in_array($name, $existing_profile_names)) {
                $new_post_id = wp_insert_post([
                    'post_title'  => $name,
                    'post_type'   => 'profile',
                    'post_status' => 'publish',
                    'post_author' => $user_id, // Set the author of the post
                ]);

                // If profile was successfully created, update its fields
                if ($new_post_id) {
                    update_field('field_key_for_profile_type', sanitize_text_field($profile_data['type']), $new_post_id);
                    update_field('field_key_for_url', esc_url_raw($profile_data['url']), $new_post_id);

                    // Add new profile to the 'profiles' ACF repeater
                    add_row('profiles', ['profile' => $new_post_id], $post_id);
                }
            }
        }

        // Clear pending profiles
        update_field('pending_profiles', [], $post_id);
    }
}

/**
 * Add 'Edit Profile' links to profiles in the repeater field
 * Displays a link to edit the selected profiles in the WordPress admin.
 */
function add_edit_profile_links_inside_label() {
    global $post_type;

    // Only apply to 'post' post type
    if ('post' !== $post_type) {
        return;
    }

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add 'Edit Profile' link to each profile in the repeater field
            $('.acf-field[data-name="profiles"] .acf-row').each(function() {
                var profileSelect = $(this).find('.acf-field[data-name="profile"] select');
                var profileID = profileSelect.val(); // Get selected profile ID

                if (profileID) {
                    // Create the 'Edit Profile' link
                    var editLink = $('<a>', {
                        text: 'Edit Profile',
                        href: '/wp-admin/post.php?post=' + profileID + '&action=edit',
                        target: '_blank',
                        class: 'edit-profile-link',
                        style: 'display: block; margin-top: 5px;'
                    });

                    // Append the link inside the ACF label
                    profileSelect.closest('.acf-field').find('.acf-label').append(editLink);
                }
            });
        });
    </script>
    <?php
}
