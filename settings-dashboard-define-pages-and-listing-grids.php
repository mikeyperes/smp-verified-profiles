<?php namespace smp_verified_profiles;
/*
// Centralized function to return ACF fields with an option for either keys or titles
function smp_vp_get_acf_fields($with_titles = false) {
    $acf_fields = [

   
        'display_single_profile_education' => 'display_single_profile_education',
        'display_single_profile_organizations_founded' => 'display_single_profile_organizations_founded',
        'display_single_profile_press_releases' => 'display_single_profile_press_releases',
        'display_single_profile_article_written_by' => 'display_single_profile_article_written_by',
        'display_single_profile_articles_featured_in' => 'display_single_profile_articles_featured_in',
        'display_single_profile_text_based_social_profiles' => 'display_single_profile_text_based_social_profiles',
        'display_single_post_mentioned_in_article' => 'display_single_post_mentioned_in_article',
        'display_homepage_profiles' => 'display_homepage_profiles',
        'display_theme_footer_text_social_links' => 'display_theme_footer_text_social_links',
        'display_homepage_profiles' => 'display_homepage_profiles',
        'display_single_profile_validate_schema_button' => 'display_single_profile_validate_schema_button',
        
        'page_verified_profiles_badges' => 'Verified Profiles - Badges',
        'page_verified_profiles_claim' => 'Verified Profiles - Claim',
        'page_verified_profiles_apply' => 'Verified Profiles - Apply',
        'page_verified_profiles_welcome' => 'Verified Profiles - Welcome',
        'single_profile' => 'Profile - mentions on single.php',
        'page_profile_archive' => 'Profile - archive.php',
        'home_profile_archive' => 'Profile - archive on home.php',
    
        
    ];

    // If titles are not required, return only the keys
    return $with_titles ? $acf_fields : array_keys($acf_fields);
}*/





/**
 * Append “(ID: ###)” to every ACF post_object Select2 result.
 *
 * @param string  $title   The post title as ACF currently prints it.
 * @param WP_Post $post    The post object for this result row.
 * @param array   $field   The full ACF field settings array.
 * @param mixed   $post_id The post_id being edited (not the result’s post ID).
 * @return string
 */
add_filter('acf/fields/post_object/result', __NAMESPACE__ . '\\append_id_to_post_object_title', 10, 4);
function append_id_to_post_object_title( $title, $post, $field, $post_id ) {
    // e.g. “My Page Title (ID: 123)”
    return sprintf(
        '%s (ID: %d)',
        $title,
        $post->ID
    );
}



/**
 * Retrieve ACF fields for Verified Profile Pages and Listings.
 *
 * This function dynamically fetches all Advanced Custom Fields (ACF) associated with 
 * verified profile pages and listing groups from the 'Verified Profiles Settings' ACF group.
 * It can either return the field names or return both field names and their corresponding labels.
 *
 * @param bool $include_labels Optional. If true, returns an associative array with field names as keys and labels as values.
 *                             If false (default), returns an array of field names only.
 * @return array The ACF field names or an associative array of field names and labels.
 */
function get_acf_fields_to_define_pages_and_listing_grids($with_titles = false) {
    // Fetch all local field groups
    $field_groups = acf_get_local_field_groups();

    // Initialize the array to hold ACF fields
    $acf_fields = [];

    // Loop through each field group
    foreach ($field_groups as $group) {
        if ($group['key'] === 'group_verified_profiles_settings') {
            // Fetch all fields for this group
            $fields = acf_get_fields($group['key']);

            // Loop through the fields and store the key and label
            foreach ($fields as $field) {
                if ($with_titles) {
                    $acf_fields[$field['name']] = $field['label'];
                } else {
                    $acf_fields[] = $field['name'];
                }
            }
        }
    }

    // Return the list of fields, with or without titles
    return $acf_fields;
}






















function display_settings_acf_post_and_pages_form() { ?>
    <form method="post" action="options.php">
        <?php
        // Render ACF fields using only the keys
        acf_form(array(
            'post_id' => 'options',
            'fields' => get_acf_fields_to_define_pages_and_listing_grids(), // Get only keys
            'submit_value' => 'Save Settings',
        ));
        ?>
    </form>
    <script>
        jQuery(document).ready(function($) {
            acf.add_action('ready', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'GET',
                    data: {
                        action: 'get_settings_pages_and_post_reports'
                    },
                    success: function(response) {
                        if (response.success) {
                            $.each(response.data, function(field, message) {
                                var statusMessage = message ? 'Post ID: ' + message.post_id + ' - <a href="' + message.view_link + '">View</a> - <a href="' + message.edit_link + '">Edit</a>' : 'No post assigned';
                                var labelContainer = $('div[data-key="' + field + '"] .acf-label');
                                if (labelContainer.length) {
                                    labelContainer.append('<div class="acf-status" style="font-size: 0.9em; color: #666; margin-top: 5px;">' + statusMessage + '</div>');
                                }
                            });
                        }
                    }
                });
            });
        });
    </script>
<?php }





function display_settings_create_pages_and_listing_grids() { ?>
    <button id="create-posts-button" class="button button-primary">Create Pages and Listing Grids</button>
    <div id="acf-loader" style="display:none;">Loading...</div>
    <div id="create-posts-result"></div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#create-posts-button').on('click', function() {
                var resultDiv = $('#create-posts-result');
                var loaderDiv = $('#acf-loader');
                resultDiv.html('');
                loaderDiv.show();

                var data = { 'action': 'create_posts_and_listing_grids' };

                fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(data => {
                    loaderDiv.hide();
                    resultDiv.html(data.report);
                    if (data.new_posts_created) {
                        resultDiv.append('<p style="color:red;font-weight"bold">Refreshing page in <span id="timer">10</span> seconds...</p>');
                        var seconds = 10;
                        var interval = setInterval(function() {
                            $('#timer').text(--seconds);
                            if (seconds <= 0) {
                                clearInterval(interval);
                                location.reload();
                            }
                        }, 1000);
                    }
                })
                .catch(error => {
                    loaderDiv.hide();
                    resultDiv.html('Error: ' + error);
                });
            });
        });
    </script>
<?php }




















// Display ACF field status
function display_acf_field_status() {
    $acf_fields = get_acf_fields_to_define_pages_and_listing_grids(true); // Get keys with titles

    foreach ($acf_fields as $acf_field_key => $acf_field_label) {
        $field_object = acf_get_field($acf_field_key);
        if ($field_object) {
            $field_value = get_field($acf_field_key, 'option');
            $field_id = $field_object['ID'];

            if ($field_value) {
                echo '<p><strong>' . $acf_field_label . '</strong> (ID: ' . $field_id . '): <a target=_blank href="' . get_permalink($field_value) . '">View Post ID ' . $field_value . '</a></p>';
            } else {
                echo '<p><strong>' . $acf_field_label . '</strong> (ID: ' . $field_id . '): No post assigned</p>';
            }
        }
    }
}















// Create posts and listing grids
function create_posts_and_listing_grids() {
    $acf_fields = get_acf_fields_to_define_pages_and_listing_grids(true); // Get keys with titles
    $report = '';
    $new_posts_created = false;

    foreach ($acf_fields as $field_key => $field_title) {
        // Get field object to determine post type
        $field_object = get_field_object($field_key);
        $post_type = $field_object['post_type'][0];
        $post_id = get_field($field_key, 'option');

        if ($post_id) {
            $report .= '<p>ACF field <strong>' . $field_title . '</strong> already has a post assigned (ID: ' . $post_id . ')</p>';
        } else {
            // Prepare post data
            $post_data = array(
                'post_title' => wp_strip_all_tags($field_title . ' - auto generated'),
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type' => $post_type
            );

            // Insert the post into the database
            $new_post_id = wp_insert_post($post_data);
            if (!is_wp_error($new_post_id)) {
                $new_posts_created = true;
                $report .= '<p>Post created successfully: ' . $field_title . ' (ID: ' . $new_post_id . ')</p>';
                update_field($field_key, $new_post_id, 'option');
            } else {
                $report .= '<p>Failed to insert post: ' . $field_title . '</p>';
            }
        }
    }

    echo json_encode(['report' => $report, 'new_posts_created' => $new_posts_created]);
    wp_die();
}