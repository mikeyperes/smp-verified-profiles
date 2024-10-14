<?php namespace smp_verified_profiles;

function enable_snippet_wp_admin_adjust_table_for_unclaimed_profiles()
{
add_filter('acf/fields/post_object/query/key=field_659a5dbd40f5b', __NAMESPACE__ . '\\filter_profiles_by_unclaimed_author', 10, 3);
    // Hook the function into the pre_get_users action
add_action( 'pre_get_users', __NAMESPACE__ . '\\sort_users_by_date_registered' );
add_filter('manage_users_columns', __NAMESPACE__ . '\\add_unclaimed_profiles_column');
add_action('manage_users_custom_column', __NAMESPACE__ . '\\show_unclaimed_profiles', 10, 3);
add_filter('manage_users_columns', __NAMESPACE__ . '\\remove_posts_column_from_users');
add_filter('manage_users_columns', __NAMESPACE__ . '\\add_claimed_profiles_column');
add_action('manage_users_custom_column', __NAMESPACE__ . '\\show_claimed_profiles', 10, 3);
add_action('manage_users_custom_column', __NAMESPACE__ . '\\show_profile_post_count', 10, 3);
add_filter('manage_users_columns', __NAMESPACE__ . '\\add_profile_posts_column');
}


// Ensure the function is not declared already
if (!function_exists(__NAMESPACE__ . '\\filter_profiles_by_unclaimed_author')) {
    /**
     * Filters the ACF Post Object query to show only 'profile' posts by the 'unclaimed' author.
     *
     * @param array $args The existing query arguments.
     * @param array $field The ACF field array.
     * @param int $post_id The ID of the current post.
     * @return array Modified query arguments to filter by 'unclaimed' author.
     */
    function filter_profiles_by_unclaimed_author( $args, $field, $post_id ) {
        // Get the user data for the 'unclaimed' username
        $unclaimed_user = get_user_by('slug', 'unclaimed');

        // Check if the 'unclaimed' user exists
        if ($unclaimed_user) {
            // Modify the query to include only 'profile' posts by the 'unclaimed' author
            $args['author'] = $unclaimed_user->ID;
        }

        // Return the modified query arguments
        return $args;
    }

    // Add filter for the ACF Post Object field to modify the query
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\filter_profiles_by_unclaimed_author function is already declared", true);

/// MERGED FROM ANOTHER SNIPPET

// Ensure the function is not declared already
if (!function_exists(__NAMESPACE__ . '\\sort_users_by_date_registered')) {
    /**
     * Sort users by registration date in descending order in the admin users list.
     *
     * @param WP_User_Query $user_query The current user query object.
     */
    function sort_users_by_date_registered( $user_query ) {
        // Check if we're in the admin area and on the users.php page
        if ( is_admin() && 'users.php' === $GLOBALS['pagenow'] ) {
            // Modify query to sort users by the registration date in descending order
            $user_query->query_vars['orderby'] = 'user_registered';
            $user_query->query_vars['order'] = 'DESC';
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\sort_users_by_date_registered function is already declared", true);

//// FROM ANOTHER SNIPPET 

// Add a new column for Unclaimed Profiles to the Users page
if (!function_exists(__NAMESPACE__ . '\\add_unclaimed_profiles_column')) {
    /**
     * Adds a new column to the Users page to display unclaimed profiles.
     *
     * @param array $columns The existing columns.
     * @return array Modified columns with 'Unclaimed Profiles'.
     */
    function add_unclaimed_profiles_column($columns) {
        $columns['unclaimed_profiles'] = 'Unclaimed <br/>Profiles';
        return $columns;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\add_unclaimed_profiles_column function is already declared", true);

// Populate the Unclaimed Profiles column with data
if (!function_exists(__NAMESPACE__ . '\\show_unclaimed_profiles')) {
    /**
     * Populates the 'Unclaimed Profiles' column for each user with clickable links to profiles.
     *
     * @param string $value The current value of the column.
     * @param string $column_name The column name.
     * @param int $user_id The user ID.
     * @return string Populated column value with profile links.
     */
    function show_unclaimed_profiles($value, $column_name, $user_id) {
        if ('unclaimed_profiles' === $column_name) {
            $profiles_output = '';
            if (have_rows('unclaimed_profiles', 'user_' . $user_id)) {
                while (have_rows('unclaimed_profiles', 'user_' . $user_id)) {
                    the_row();
                    $profile_id = get_sub_field('profile');
                    if ($profile_id) {
                        $profile_post = get_post($profile_id);
                        if ($profile_post) {
                            $profiles_output .= '<a href="' . get_permalink($profile_id) . '" target="_blank">' . get_the_title($profile_id) . '</a><br>';
                        }
                    }
                }
            }
            return $profiles_output;
        }
        return $value;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\show_unclaimed_profiles function is already declared", true);

// Remove the default 'Posts' column
if (!function_exists(__NAMESPACE__ . '\\remove_posts_column_from_users')) {
    /**
     * Removes the default 'Posts' column from the Users page.
     *
     * @param array $column_headers The existing column headers.
     * @return array Modified column headers without 'Posts'.
     */
    function remove_posts_column_from_users($column_headers) {
        unset($column_headers['posts']);
        return $column_headers;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\remove_posts_column_from_users function is already declared", true);

// Add a new column for Profile CPT post count
if (!function_exists(__NAMESPACE__ . '\\add_profile_posts_column')) {
    /**
     * Adds a new column to display the number of Profile posts authored by a user.
     *
     * @param array $columns The existing columns.
     * @return array Modified columns with 'Profile Posts'.
     */
    function add_profile_posts_column($columns) {
        $columns['profile_posts'] = 'Posts';
        return $columns;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\add_profile_posts_column function is already declared", true);

// Populate the Profile Posts column
if (!function_exists(__NAMESPACE__ . '\\show_profile_post_count')) {
    /**
     * Populates the 'Profile Posts' column with the number of profile posts authored by a user.
     *
     * @param string $value The current value of the column.
     * @param string $column_name The column name.
     * @param int $user_id The user ID.
     * @return string The number of profile posts authored by the user.
     */
    function show_profile_post_count($value, $column_name, $user_id) {
        if ('profile_posts' === $column_name) {
            $query = new \WP_Query([
                'post_type' => 'profile',
                'author' => $user_id,
                'posts_per_page' => -1
            ]);
            return $query->found_posts;
        }
        return $value;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\show_profile_post_count function is already declared", true);

// Add a new column for Claimed Profiles
if (!function_exists(__NAMESPACE__ . '\\add_claimed_profiles_column')) {
    /**
     * Adds a new column to display claimed profiles for each user.
     *
     * @param array $columns The existing columns.
     * @return array Modified columns with 'Claimed Profiles'.
     */
    function add_claimed_profiles_column($columns) {
        $columns['claimed_profiles'] = 'Claimed <br />Profiles';
        return $columns;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\add_claimed_profiles_column function is already declared", true);

// Populate the Claimed Profiles column with clickable links
if (!function_exists(__NAMESPACE__ . '\\show_claimed_profiles')) {
    /**
     * Populates the 'Claimed Profiles' column with clickable links to the claimed profiles authored by the user.
     *
     * @param string $value The current value of the column.
     * @param string $column_name The column name.
     * @param int $user_id The user ID.
     * @return string The populated column value with profile links.
     */
    function show_claimed_profiles($value, $column_name, $user_id) {
        if ('claimed_profiles' === $column_name) {
            $posts = get_posts([
                'post_type' => 'profile',
                'author' => $user_id,
                'posts_per_page' => -1
            ]);

            $output = '';
            foreach ($posts as $post) {
                $permalink = get_permalink($post->ID);
                $output .= '<a href="' . esc_url($permalink) . '" target="_blank">' . esc_html($post->post_title) . '</a><br>';
            }
            return $output;
        }
        return $value;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\show_claimed_profiles function is already declared", true);