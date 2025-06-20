<?php namespace smp_verified_profiles;




/**
 * Fetch and sanitize Verified Profile settings from ACF options
 *
 * @return array{singular:string,plural:string,slug:string}
 */
function get_verified_profile_settings(): array {

    global $my_verified_profile_settings_cache;
    if ( isset( $my_verified_profile_settings_cache ) ) {
        return $my_verified_profile_settings_cache;
    }

    // If we’re in an ACF field lookup, just return an empty array
    if ( is_admin() && did_action('acf/fields_loaded') ) {
        return [];
    }

    // default fallbacks
    $defaults = [
        'singular' => 'Verified Profile',
        'plural'   => 'Verified Profiles',
        'slug'     => 'profile',
    ];

    // if ACF isn't active, return defaults
    if ( ! function_exists('get_field') ) {
        return $defaults;
    }

    // pull the general_settings group from ACF options
    $settings       = get_field('general_settings', 'option') ?: [];
    $raw_plural     = $settings['cpt_plural_name']   ?? '';
    $raw_singular   = $settings['cpt_singular_name'] ?? '';
    $raw_slug       = $settings['cpt_slug']          ?? '';

    $sanitized = [
        'singular' => sanitize_text_field( $raw_singular ?: $defaults['singular'] ),
        'plural'   => sanitize_text_field( $raw_plural   ?: $defaults['plural']   ),
        'slug'     => sanitize_title(    $raw_slug      ?: $defaults['slug']     ),
    ];

    $my_verified_profile_settings_cache = $sanitized;
    return $sanitized;
}



// Define the write_log function only if it isn't already defined
if (!function_exists(__NAMESPACE__ . '\\write_log')) {
    function write_log($log, $full_debug = false) {
        if (WP_DEBUG && WP_DEBUG_LOG && $full_debug) {
            // Get the backtrace
            $backtrace = debug_backtrace();
            
            // Extract the last function that called this one
            $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'N/A';
            
            // Extract the file and line number where the caller is located
            $caller_file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : 'N/A';
            $caller_line = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 'N/A';
            
            // Prepare the log message
            $log_message = is_array($log) || is_object($log) ? print_r($log, true) : $log;
            $log_message .= "\n\n[Called by: $caller]\n[In file: $caller_file at line $caller_line]\n\n---\n";
            
            // Write to the log
            error_log($log_message);
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\write_log function is already declared", true);



if (!function_exists(__NAMESPACE__ . '\\hws_ct_highlight_based_on_criteria')) {
    function hws_ct_highlight_based_on_criteria($setting, $fail_criteria = null) {
    
        // Initialize the value
        $raw_value = isset($setting['raw_value']) ? $setting['raw_value'] : null;
        // Log if 'value' is not set or null
        if ($raw_value === null) {
            write_log($setting['function'].": a raw_value has not set a value yet", true);
        }
        $status = true;
        
        
            if(isset($setting['status']))
            $status = $setting['status'];
            // Highlight the value based on the status
            if ($status === false || $status === 0 || $status === 'false' || $status === '0') {
                return "<span style='color: red;'>{$raw_value}</span>";
            }
        
            return $raw_value;
        }} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\hws_ct_highlight_based_on_criteria function is already declared", true);
    

        

// Check if the function exists before defining 'is_profile_manager'
if (!function_exists(__NAMESPACE__ . '\\is_profile_manager')) {
    /**
     * Check if the current user is a Profile Manager.
     *
     * @param bool $strict If true, only checks if the user has the 'verified_profile_manager' role.
     * @return bool Returns true if the current user is a Profile Manager, false otherwise.
     */
    function is_profile_manager($strict = false) {

        // Check if the user is an admin and not in strict mode, or if they have the 'verified_profile_manager' capability
        if ((is_admin() && !$strict) || current_user_can('verified_profile_manager')) {
            return true;
        }
        
        return false;
    }
} else {
    // Log a warning if the function already exists
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\is_profile_manager function is already declared", true);
}



// Check if the function exists before defining 'is_contributor'
if (!function_exists(__NAMESPACE__ . '\\is_contributor')) {
    /**
     * Check if the current user is a Contributor or has specific user fields like 'is_contributor' or 'is_council_member'.
     *
     * @param bool $strict If true, only checks if the user has the 'contributor' role or the custom fields.
     * @return bool Returns true if the current user is a Contributor or Council Member, false otherwise.
     */
    function is_contributor($strict = false) {
 

        // Get the current user
        $user = wp_get_current_user();

        // Get the ACF custom fields 'is_contributor' and 'is_council_member' for the current user
        $is_contributor = get_field('is_contributor', 'user_' . $user->ID);
        $is_council_member = get_field('is_council_member', 'user_' . $user->ID);

        // Check if the user is an admin (when not in strict mode), has the 'contributor' role, or has custom fields set
        if ((is_admin() && !$strict) || current_user_can('contributor') || $is_contributor || $is_council_member) {
            return true;
        }
        
        return false;
    }
} else {
    // Log a warning if the function already exists
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\is_contributor function is already declared", true);
}




if (!function_exists(__NAMESPACE__ . '\\check_plugin_status')) {
    function check_plugin_status($plugin_slug) {
        $is_installed = file_exists(WP_PLUGIN_DIR . '/' . $plugin_slug);
        $is_active = $is_installed && is_plugin_active($plugin_slug);

        // Initialize auto-update as not enabled since it's meaningless if not installed
        $is_auto_update_enabled = false;

        if ($is_installed) { 
            // Check global auto-update setting first
            $global_auto_update_enabled = apply_filters('auto_update_plugin', false, (object) array('plugin' => $plugin_slug));
 
            // If globally enabled, set auto-update to true
            if ($global_auto_update_enabled) {
                $is_auto_update_enabled = true;
            } else {
                // Get the current list of plugins with auto-updates enabled
                $auto_update_plugins = get_option('auto_update_plugins', []);

                // Check if this specific plugin is in the list
                $is_auto_update_enabled = in_array($plugin_slug, $auto_update_plugins);

                // If not in the auto-update plugins list, apply the global filter
                if (!$is_auto_update_enabled) {
                    $update_plugins = get_site_transient('update_plugins');

                    // Check the transient data for this specific plugin
                    if (isset($update_plugins->no_update[$plugin_slug])) {
                        $plugin_data = $update_plugins->no_update[$plugin_slug];
                    } elseif (isset($update_plugins->response[$plugin_slug])) {
                        $plugin_data = $update_plugins->response[$plugin_slug];
                    }

                    // Apply the auto_update_plugin filter with both arguments
                    if (isset($plugin_data)) {
                        $is_auto_update_enabled = apply_filters('auto_update_plugin', false, $plugin_data);
                    }
                }
            }
        }

        // Log the final auto-update status for debugging
        write_log("Plugin Slug: $plugin_slug - Installed: " . ($is_installed ? 'Yes' : 'No') . " - Auto-Update Enabled: " . ($is_auto_update_enabled ? 'Yes' : 'No'),false);

        return [$is_installed, $is_active, $is_auto_update_enabled];
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\check_plugin_status function is already declared", true);


if (!function_exists(__NAMESPACE__ . '\\is_plugin_auto_update_enabled')) {
    function is_plugin_auto_update_enabled($plugin_id) {
        // Check if site-wide auto-updates are enabled
        if (has_filter('auto_update_plugin', '__return_true') !== false) {
            return true;
        }
 
        // Get the list of plugins with auto-updates enabled
        $auto_update_plugins = get_site_option('auto_update_plugins', []);

        // Check if the specific plugin has auto-updates enabled
        return in_array($plugin_id, $auto_update_plugins);
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\is_plugin_auto_update_enabled function is already declared", true);


// Get the field label for an ACF field
if (!function_exists('get_field_label')) {
    function get_field_label($field_name) {
        $field_object = get_field_object($field_name);
        return $field_object['label'];
    }}
    


    if (!function_exists(__NAMESPACE__ . '\\check_user_exists_by_slug')) {
        function check_user_exists_by_slug($slug) {
            if (empty($slug)) {
                write_log('Slug is empty', false);
                return false;
            }
            
            $user = get_user_by('slug', $slug);
            $exists = ($user) ? true : false;
            
            write_log('Checking if user exists by slug: ' . $slug . ' - ' . ($exists ? 'Exists' : 'Does not exist'), false);
            
            return $exists;
        }
    } else 
        write_log("⚠️ Warning: " . __NAMESPACE__ . "\\check_user_exists_by_slug function is already declared", true);

        
        if (!function_exists(__NAMESPACE__ . '\\create_unclaimed_profiles_user')) {
            function create_unclaimed_profiles_user() {
                // Get the dynamic base URL from WordPress
                $base_url = get_site_url();
        
                $params = array(
                    'slug' => 'unclaimed',
                    'first_name' => 'Unclaimed Profiles',
                    'last_name' => 'test',
                    'email' => 'unclaimed@' . parse_url($base_url, PHP_URL_HOST), // Use domain part from base URL
                    'website' => $base_url,
                    'bio' => 'Unclaimed profile',
                    'permission' => 'admin'
                );
        
                $result = create_wordpress_user($params);
                if (is_wp_error($result)) {
                    wp_send_json_error($result->get_error_message());
                } else 
                    wp_send_json_success(['user_id' => $result]);
            }
        } else 
            write_log("⚠️ Warning: " . __NAMESPACE__ . "\\create_unclaimed_profiles_user function is already declared", true);
        



            if (!function_exists(__NAMESPACE__ . '\\create_wordpress_user')) {
                function create_wordpress_user($params) {
                    // Validate required fields
                    $required_fields = ['slug', 'first_name', 'last_name', 'email'];
                    foreach ($required_fields as $field) {
                        if (empty($params[$field])) {
                            write_log("Missing required field: " . $field, false);
                            return new \WP_Error('missing_field', 'The ' . $field . ' is required.');
                        }
                    }
            
                    // Check if user exists by slug
                    if (check_user_exists_by_slug($params['slug'])) {
                        write_log("User with slug " . $params['slug'] . " already exists", false);
                        return new \WP_Error('user_exists', 'A user with this slug already exists.');
                    }
            
                    // Prepare user data
                    $user_data = array(
                        'user_login' => $params['slug'],  // This is used for login
                        'user_email' => $params['email'],
                        'first_name' => $params['first_name'],
                        'last_name' => $params['last_name'],
                        'user_url' => isset($params['website']) ? $params['website'] : '',
                        'role' => isset($params['permission']) && $params['permission'] === 'admin' ? 'administrator' : 'subscriber',
                        'description' => isset($params['bio']) ? $params['bio'] : '',
                        'display_name' => $params['first_name'] . ' ' . $params['last_name'],  // Display name
                        'nickname' => $params['first_name'],  // Nickname (Optional)
                        'user_nicename' => $params['slug'],  // This is the slug used in URLs
                    );
            
                    // Create user
                    $user_id = wp_insert_user($user_data);
            
                    // If user creation failed, return error and log it
                    if (is_wp_error($user_id)) {
                        write_log("Error creating user: " . $user_id->get_error_message(), false);
                        return $user_id;
                    }
            
                    // Bind ACF fields (socials, profiles, etc.)
                    if (function_exists('update_field')) {
                        // Socials
                        $socials = array(
                            'facebook' => isset($params['facebook']) ? $params['facebook'] : '',
                            'instagram' => isset($params['instagram']) ? $params['instagram'] : '',
                            'x' => isset($params['x']) ? $params['x'] : '',
                            'linkedin' => isset($params['linkedin']) ? $params['linkedin'] : '',
                        );
                        update_field('socials', $socials, 'user_' . $user_id);
                        write_log('Updated social fields for user: ' . $params['slug'], false);
            
                        // Profiles
                        $profiles = array(
                            'crunchbase' => isset($params['crunchbase']) ? $params['crunchbase'] : '',
                            'muckrack' => isset($params['muckrack']) ? $params['muckrack'] : '',
                        );
                        update_field('profiles', $profiles, 'user_' . $user_id);
                        write_log('Updated profile fields for user: ' . $params['slug'], false);
                    } else 
                        write_log("ACF update_field function does not exist", false);
            
                    write_log('User created successfully: ' . $params['slug'], false);
                    return $user_id;
                }
            } else 
                write_log("⚠️ Warning: " . __NAMESPACE__ . "\\create_wordpress_user function is already declared", true);
            


        


                if (!function_exists(__NAMESPACE__ . '\\check_user_unclaimed_exists')) {
                    function check_user_unclaimed_exists($slug = 'unclaimed') {
                        write_log("Checking if unclaimed user exists", false);
                
                        $settings = get_verified_profile_settings();
$profile_slug = $settings['slug'];


                        // Call existing function to check if user exists by slug
                        $user_exists = check_user_exists_by_slug($slug);
                
                        // If the user exists, fetch and display the user info
                        if ($user_exists) {
                            $user = get_user_by('slug', $slug);
                
                            // Get basic info
                            $user_info = [
                                'name' => $user->display_name,
                                'email' => $user->user_email,
                                'featured_image' => get_avatar_url($user->ID), // Assuming Gravatar or another avatar system
                                'date_created' => $user->user_registered,
                                'number_of_posts' => count_user_posts($user->ID, $profile_slug), // Count of posts of type 'profile'
                                'view_link' => get_author_posts_url($user->ID)
                            ];
                
                     // Build the info to be returned
$info_display = "
    <p>Name: {$user_info['name']}</p>
    <p>Email: {$user_info['email']}</p>
    <p>Featured Image: <img src='{$user_info['featured_image']}' alt='Featured Image' style='width:50px; height:auto;'></p>
    <p>Date Created: {$user_info['date_created']}</p>
    <p>Number of Profile Posts: {$user_info['number_of_posts']}</p>
    <p><a href='{$user_info['view_link']}' target='_blank'>View Profile</a></p>
    <p><a href='" . admin_url('user-edit.php?user_id=' . $user->ID) . "' target='_blank'>Edit User</a></p>
    <p><a href='{$user_info['view_link']}' target='_blank'>View User</a></p>
    <p><a href='" . admin_url('edit.php?post_type=profile&author=' . $user->ID) . "' target='_blank'>View Posts</a></p>
";
                
                            write_log("Unclaimed user exists: {$user_info['name']}", false);
                            
                            // Return the status, user info and button (for other UI purposes if needed)
                            return [
                                'function' => 'check_user_unclaimed_exists',
                                'status' => true,  // User exists
                                'raw_value' => $info_display,  // Info for the user
                                'variables' => [
                                    'user_exists' => true,
                                    'user_info' => $user_info
                                ]
                            ];
                        } else {
                            write_log("Unclaimed user does not exist, offering creation option", false);
                
                            // Button to create the user if they don't exist
                            $create_button = "Unclaimed User Profile User not found.";
                            $create_button.="
                                <button class='button execute-function block' data-method='create_unclaimed_profiles_user' data-loader='true'>
                                    Create Unclaimed User
                                </button>
                            ";
                
                            // Return status and button for creating the user
                            return [
                                'function' => 'check_user_unclaimed_exists',
                                'status' => false,  // User does not exist
                                'raw_value' => $create_button,  // Display button for user creation
                                'variables' => [
                                    'user_exists' => false
                                ]
                            ];
                        }
                    }
                } else 
                    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\check_user_unclaimed_exists function is already declared", true);
                    
                    
                    
                    if (!function_exists(__NAMESPACE__ . '\\check_profile_taxonomies')) {
                        function check_profile_taxonomies() {
              
                            $settings = get_verified_profile_settings();
$slug = $settings['slug'];

write_log( "Checking {$slug} taxonomies", false );

// Check if categories are assigned to this CPT
$categories_assigned = ( get_object_taxonomies( $slug ) && taxonomy_exists( 'category' ) ) ? true : false;

// Check if tags are assigned to this CPT
$tags_assigned = ( get_object_taxonomies( $slug ) && taxonomy_exists( 'post_tag' ) ) ? true : false;


                            // Check if the category 'person' exists
                            $person_category_exists = term_exists('person', 'category') ? true : false;
                            
                            // Check if the category 'organization' exists
                            $organization_category_exists = term_exists('organization', 'category') ? true : false;
                    
                         // Get the term IDs for person and organization categories
$person_category = get_term_by('slug', 'person', 'category');
$organization_category = get_term_by('slug', 'organization', 'category');

// Count the number of profiles assigned to the person category
$person_profiles_count = ($person_category) ? (int) get_posts([
    'post_type' => $slug,
    'category' => $person_category->term_id,
    'fields' => 'ids',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]) : 0;

// Count the number of profiles assigned to the organization category
$organization_profiles_count = ($organization_category) ? (int) get_posts([
    'post_type' => $slug,
    'category' => $organization_category->term_id,
    'fields' => 'ids',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]) : 0;

// Build the info display
$info_display = "
    <p>Categories assigned to 'profile': " . ($categories_assigned ? 'Yes' : 'No') . "</p>
    <p>Tags assigned to 'profile': " . ($tags_assigned ? 'Yes' : 'No') . "</p>
    <p>Category 'person' exists: " . ($person_category_exists ? 'Yes' : 'No') . "</p>
    <p>Number of Profiles in 'person' category: 
        <a href='" . admin_url("edit.php?post_type=profile&category_name=person") . "' target='_blank'>{$person_profiles_count}</a>
    </p>
    <p>Category 'organization' exists: " . ($organization_category_exists ? 'Yes' : 'No') . "</p>
    <p>Number of Profiles in 'organization' category: 
        <a href='" . admin_url("edit.php?post_type=profile&category_name=organization") . "' target='_blank'>{$organization_profiles_count}</a>
    </p>
";
                    
                            // If anything is missing, show a button to fix it
                            if (!$categories_assigned || !$tags_assigned || !$person_category_exists || !$organization_category_exists) {
                                $info_display .= "
                                    <button class='button execute-function block' data-method='fix_profile_taxonomies' data-loader='true'>
                                        Fix Missing Taxonomies
                                    </button>
                                ";
                            }
                    
                            write_log("Profile taxonomy check completed", false);
                    
                            // Return status and info for AJAX response
                            return [
                                'function' => 'check_profile_taxonomies',
                                'status' => ($categories_assigned && $tags_assigned && $person_category_exists && $organization_category_exists),
                                'raw_value' => $info_display,
                                'variables' => [
                                    'categories_assigned' => $categories_assigned,
                                    'tags_assigned' => $tags_assigned,
                                    'person_category_exists' => $person_category_exists,
                                    'organization_category_exists' => $organization_category_exists
                                ]
                            ];
                        }
                    } else 
                        write_log("⚠️ Warning: " . __NAMESPACE__ . "\\check_profile_taxonomies function is already declared", true);



                        if ( ! function_exists( __NAMESPACE__ . '\\fix_profile_taxonomies' ) ) {
                            function fix_profile_taxonomies() {
                                write_log( "Fixing profile taxonomies", false );
                        
                                $settings = get_verified_profile_settings();
                                $slug     = $settings['slug'];
                        
                                // Assign categories to CPT post type
                                if ( taxonomy_exists( 'category' ) ) {
                                    register_taxonomy_for_object_type( 'category', $slug );
                                    write_log( "Categories assigned to '{$slug}' post type", false );
                                }
                        
                                // Assign tags to CPT post type
                                if ( taxonomy_exists( 'post_tag' ) ) {
                                    register_taxonomy_for_object_type( 'post_tag', $slug );
                                    write_log( "Tags assigned to '{$slug}' post type", false );
                                }
                        
                                // Create 'person' category if missing
                                if ( ! term_exists( 'person', 'category' ) ) {
                                    wp_insert_term( 'person', 'category' );
                                    write_log( "Category 'person' created", false );
                                }
                        
                                // Create 'organization' category if missing
                                if ( ! term_exists( 'organization', 'category' ) ) {
                                    wp_insert_term( 'organization', 'category' );
                                    write_log( "Category 'organization' created", false );
                                }
                        
                                wp_send_json_success( [ 'message' => 'Profile taxonomies fixed' ] );
                            }
                        } else {
                            write_log( "⚠️ Warning: " . __NAMESPACE__ . "\\fix_profile_taxonomies function is already declared", true );
                        }

         //SCHEMA UPDATE

if (!function_exists(__NAMESPACE__ . '\\update_schema_markup')) {
    /**
     * Updates the schema markup for profiles in batches, outputs the status of updates, and shows relevant post details.
     * 
     * Checks for 'update_schema' query parameter, batch size, and updates schema markup for profile posts.
     * Outputs information about total profiles, published profiles, drafts, and processed profiles.
     */
    function update_schema_markup() {
        // Ensure the update is triggered by an authorized user with 'manage_options' capability
        if ( isset( $_GET['update_schema'] ) && current_user_can( 'manage_options' ) ) {
            // Retrieve CPT settings via helper
            $settings = get_verified_profile_settings();
            $slug     = $settings['slug'];
    
            // Determine batch size, defaulting to 20 if not specified
            $batch = isset( $_GET['batch'] ) ? intval( $_GET['batch'] ) : 20;
    
            // Base query to fetch profile IDs for total counts
            $base_query_args = [
                'post_type'      => $slug,
                'posts_per_page' => -1,   // Get all posts
                'fields'         => 'ids' // Fetch only the IDs to speed up the query
            ];

            // Query for total profiles
            $total_profiles_query = new \WP_Query($base_query_args);
            $total_profiles = $total_profiles_query->found_posts;

            // Query for published profiles
            $published_profiles_query = new \WP_Query(array_merge($base_query_args, ['post_status' => 'publish']));
            $total_published = $published_profiles_query->found_posts;

            // Query for draft profiles
            $draft_profiles_query = new \WP_Query(array_merge($base_query_args, ['post_status' => 'draft']));
            $total_drafts = $draft_profiles_query->found_posts;

            // Query for processing updates with limited batch
            $update_query_args = array_merge($base_query_args, [
                'posts_per_page' => $batch,
                'no_found_rows' => true,  // Disable pagination for faster processing
                'post_status' => 'publish'
            ]);
            $update_query = new \WP_Query($update_query_args);
            $total_processed = 0;

            // Process each post, generating schema markup
            if ($update_query->have_posts()) {
                foreach ($update_query->posts as $post_id) {
                    generate_schema_markup($post_id); // Assuming this function exists and updates schema markup
                    echo "Updated schema markup for post ID: {$post_id} - <a href='" . get_permalink($post_id) . "' target='_blank'>" . get_the_title($post_id) . "</a><br>";
                    $total_processed++;
                }
            } else {
                echo "No more posts to update.<br>";
            }

            // Output the counts for all profiles, published, drafts, and the number processed
            echo "Total profiles: $total_profiles<br>";
            echo "Total published profiles: $total_published<br>";
            echo "Total draft profiles: $total_drafts<br>";
            echo "Total processed this batch: $total_processed<br>";

            die(); // Stop further processing
        }
    }

    // Hook the update function to run during the 'admin_init' action
    add_action('admin_init', __NAMESPACE__ . '\\update_schema_markup');

} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\update_schema_markup function is already declared", true);



/**
 * Display ACF field structures for given group keys.
 *
 * This function retrieves the ACF fields from specified ACF field groups and returns
 * a formatted string with each field's name, label, and type, including child fields for repeater and group types.
 *
 * @param string|array $group_keys A single ACF group key or an array of group keys.
 * @return string A neatly formatted string listing all ACF fields with their basic information.
 */
/*
function display_acf_structures($group_keys) {
    // Ensure $group_keys is an array even if a single key is passed.
    $group_keys = (array) $group_keys;

    // Initialize an empty string to hold the result.
    $acf_structure_output = '<br />';

    // Fetch all the local ACF field groups registered in the system.
    $field_groups = acf_get_local_field_groups();

    // Iterate over the group keys to process multiple groups if needed.
    foreach ($group_keys as $group_key) {
        // Iterate through all available ACF field groups.
        foreach ($field_groups as $group) {
            // Check if the current group's key matches the provided group key.
            if ($group['key'] === $group_key) {
                // Add the group title to the output for clarity.
                $acf_structure_output .= "<br><b>Group: " . $group['title'] . " (" . $group['key'] . ")</b><br />";
             //   $acf_structure_output .= str_repeat("-", 2) ;

                // Retrieve all fields for this group.
                $fields = acf_get_fields($group['key']);

                // Loop through each field and append name, label, and type.
                foreach ($fields as $field) {
                    $acf_structure_output .= "> " . $field['name'] . " - " . 
                     $field['type'] . "<br />";
//$field['label'] . " - " .
                    // Handle repeater and group types to include child fields
                    if (in_array($field['type'], ['repeater', 'group'])) {
                        if (!empty($field['sub_fields'])) {
                            foreach ($field['sub_fields'] as $sub_field) {
                                $acf_structure_output .= "&nbsp;&nbsp;&nbsp;- " . $sub_field['name'] . " / " . $sub_field['type'] . "<br />";
                            }
                        }
                    }
                    //$acf_structure_output .= str_repeat("-", 2) . "<br />";
                }
                // Break the loop after processing the desired group.
                break;
            }
        }
    }

    // Return the neatly formatted string of ACF field structures.
    return $acf_structure_output ? $acf_structure_output : "No matching ACF groups found.";
}
*/








/**
 * Generate a nicely styled HTML output for one or more ACF field groups,
 * listing each field (with nested sub-fields if it’s a “group”) and then
 * the display conditions, all wrapped in semantic tags and inline CSS.
 *
 * @param string|array $group_keys A single ACF group key (e.g., 'group_65a8b18d98147')
 *                                 or an array of keys.
 * @return string HTML string with improved styling:
 *                • <div> wrapper per group
 *                • <h2> for group title/key
 *                • <ul> for fields, <li> for each field
 *                • Nested <ul> for sub-fields
 *                • <h3> for “Display Conditions”
 *                • <ul> for condition sets, nested <ul> for individual rules
 */
function display_acf_structure( $group_keys ) {
    // If ACF isn’t active, bail early.
    if ( ! function_exists( 'acf_get_field_groups' ) ) {
        return '';
    }

    // Normalize into an array
    $keys   = is_array( $group_keys ) ? $group_keys : [ $group_keys ];
    $output = '';

    foreach ( $keys as $group_key ) {
        // 1) Fetch the group object (title, location, etc.)
        $groups = acf_get_field_groups( [ 'key' => $group_key ] );
        if ( empty( $groups ) ) {
            continue;
        }
        $group = reset( $groups );

        // 2) Open group container
        $output .= '<div style="'
                 . 'border:1px solid #ccc;'
                 . 'border-radius:4px;'
                 . 'padding:16px;'
                 . 'margin-bottom:24px;'
                 . 'font-family:Arial, sans-serif;'
                 . 'background-color:#f9f9f9;'
                 . '">';

        // 3) Group title and key
        $output .= '<h2 style="'
                 . 'margin:0 0 12px;'
                 . 'font-size:1.25em;'
                 . 'color:#333;'
                 . '">'
                 . esc_html( $group['title'] )
                 . ' <code style="'
                 . 'background:#eaeaea;'
                 . 'padding:2px 4px;'
                 . 'border-radius:3px;'
                 . 'font-size:0.9em;'
                 . 'color:#555;'
                 . '">'
                 . esc_html( $group_key )
                 . '</code>'
                 . '</h2>';

        // 4) List all fields
        $fields = acf_get_fields( $group_key );
        if ( ! empty( $fields ) ) {
            $output .= '<ul style="'
                     . 'list-style-type:disc;'
                     . 'margin:0 0 16px 20px;'
                     . 'padding:0;'
                     . '">';
            foreach ( $fields as $field ) {
                // Top-level field: “name – Label (type)”
                $output .= '<li style="margin-bottom:6px;">'
                         . '<span style="font-weight:bold; color:#222;">'
                         . esc_html( $field['name'] )
                         . '</span> – '
                         . '<span style="color:#555;">'
                         . esc_html( $field['label'] )
                         . '</span> '
                         . '<em style="color:#999;">('
                         . esc_html( $field['type'] )
                         . ')</em>';

                // 4a) If this field is a “group,” show its sub_fields indented
                if ( isset( $field['type'], $field['sub_fields'] ) 
                     && $field['type'] === 'group' 
                     && ! empty( $field['sub_fields'] ) ) {
                    $output .= '<ul style="'
                             . 'list-style-type:circle;'
                             . 'margin:8px 0 0 20px;'
                             . 'padding:0;'
                             . '">';
                    foreach ( $field['sub_fields'] as $sub_field ) {
                        $output .= '<li style="margin-bottom:4px;">'
                                 . '<span style="font-weight:bold; color:#222;">'
                                 . esc_html( $sub_field['name'] )
                                 . '</span> – '
                                 . '<span style="color:#555;">'
                                 . esc_html( $sub_field['label'] )
                                 . '</span> '
                                 . '<em style="color:#999;">('
                                 . esc_html( $sub_field['type'] )
                                 . ')</em>'
                                 . '</li>';
                    }
                    $output .= '</ul>';
                }

                $output .= '</li>';
            }
            $output .= '</ul>';
        }

        // 5) Display Conditions heading
        if ( isset( $group['location'] ) && is_array( $group['location'] ) ) {
            $output .= '<h3 style="'
                     . 'margin:0 0 8px;'
                     . 'font-size:1.1em;'
                     . 'color:#333;'
                     . '">'
                     . 'Display Conditions'
                     . '</h3>';

            $output .= '<ul style="'
                     . 'list-style-type:disc;'
                     . 'margin:0 0 0 20px;'
                     . 'padding:0;'
                     . '">';
            foreach ( $group['location'] as $set_index => $rules ) {
                $set_num = $set_index + 1;
                $output .= '<li style="margin-bottom:6px;">'
                         . '<strong style="color:#444;">Condition Set '
                         . esc_html( $set_num )
                         . '</strong>'
                         . '<ul style="'
                         . 'list-style-type:circle;'
                         . 'margin:4px 0 0 20px;'
                         . 'padding:0;'
                         . '">';
                foreach ( $rules as $rule ) {
                    $param    = $rule['param']    ?? '';
                    $operator = $rule['operator'] ?? '';
                    $value    = $rule['value']    ?? '';
                    $output  .= '<li style="margin-bottom:4px; color:#555;">'
                              . esc_html( "{$param} {$operator} {$value}" )
                              . '</li>';
                }
                $output .= '</ul></li>';
            }
            $output .= '</ul>';
        }

        // 6) Close group container
        $output .= '</div>';
    }

    return $output;
}



/**
 * Generate a styled HTML output for a registered custom post type (CPT),
 * listing its main arguments (labels, supports, taxonomies, and flags)
 * in a clear, nested format with inline CSS.
 *
 * @param string $cpt_slug The slug of the registered CPT (e.g., 'organization').
 * @return string HTML string with improved styling:
 *                • <div> wrapper per CPT
 *                • <h2> for CPT name and slug
 *                • <h3> and <ul> for Labels, Supports, Taxonomies, and Flags
 */

 
function display_cpt_structure( string $cpt_slug ): string {
    // Get the post type object; if not registered, return empty string
    $pt_obj = get_post_type_object( $cpt_slug );
    if ( ! $pt_obj ) {
        return '';
    }

    $output = '';

    // Open CPT container
    $output .= '<div style="'
             . 'border:1px solid #666;'
             . 'border-radius:4px;'
             . 'padding:16px;'
             . 'margin-bottom:24px;'
             . 'font-family:Arial, sans-serif;'
             . 'background-color:#f5f5f5;'
             . '">';

    // CPT title and slug
    $output .= '<h2 style="'
             . 'margin:0 0 12px;'
             . 'font-size:1.25em;'
             . 'color:#222;'
             . '">'
             . esc_html( $pt_obj->labels->name )
             . ' <code style="'
             . 'background:#e0e0e0;'
             . 'padding:2px 4px;'
             . 'border-radius:3px;'
             . 'font-size:0.9em;'
             . 'color:#444;'
             . '">'
             . esc_html( $cpt_slug )
             . '</code>'
             . '</h2>';

    // 1) Labels Section
    $output .= '<h3 style="'
             . 'margin:12px 0 6px;'
             . 'font-size:1.1em;'
             . 'color:#333;'
             . '">'
             . 'Labels'
             . '</h3>';
    $output .= '<ul style="'
             . 'list-style-type:disc;'
             . 'margin:0 0 16px 20px;'
             . 'padding:0;'
             . '">';
    $label_props = [
        'singular_name', 'add_new_item', 'edit_item', 'view_item',
        'all_items', 'menu_name', 'archives', 'attributes',
        'insert_into_item', 'uploaded_to_this_item', 'filter_items_list',
        'search_items', 'not_found', 'not_found_in_trash',
        'items_list', 'items_list_navigation'
    ];
    foreach ( $label_props as $prop ) {
        if ( isset( $pt_obj->labels->$prop ) && $pt_obj->labels->$prop !== '' ) {
            $output .= '<li style="margin-bottom:6px;">'
                     . '<span style="font-weight:bold; color:#222;">'
                     . esc_html( $prop )
                     . '</span>: '
                     . '<span style="color:#555;">'
                     . esc_html( $pt_obj->labels->$prop )
                     . '</span>'
                     . '</li>';
        }
    }
    $output .= '</ul>';

    // 2) Supports Section
    if ( ! empty( $pt_obj->supports ) ) {
        $output .= '<h3 style="'
                 . 'margin:12px 0 6px;'
                 . 'font-size:1.1em;'
                 . 'color:#333;'
                 . '">'
                 . 'Supported Features'
                 . '</h3>';
        $output .= '<ul style="'
                 . 'list-style-type:disc;'
                 . 'margin:0 0 16px 20px;'
                 . 'padding:0;'
                 . '">';
        foreach ( $pt_obj->supports as $support ) {
            $output .= '<li style="margin-bottom:6px; color:#555;">'
                     . esc_html( $support )
                     . '</li>';
        }
        $output .= '</ul>';
    }

    // 3) Taxonomies Section
    if ( ! empty( $pt_obj->taxonomies ) ) {
        $output .= '<h3 style="'
                 . 'margin:12px 0 6px;'
                 . 'font-size:1.1em;'
                 . 'color:#333;'
                 . '">'
                 . 'Attached Taxonomies'
                 . '</h3>';
        $output .= '<ul style="'
                 . 'list-style-type:disc;'
                 . 'margin:0 0 16px 20px;'
                 . 'padding:0;'
                 . '">';
        foreach ( $pt_obj->taxonomies as $tax ) {
            $output .= '<li style="margin-bottom:6px; color:#555;">'
                     . esc_html( $tax )
                     . '</li>';
        }
        $output .= '</ul>';
    }

    // 4) Flags & Arguments Section
    $output .= '<h3 style="'
             . 'margin:12px 0 6px;'
             . 'font-size:1.1em;'
             . 'color:#333;'
             . '">'
             . 'Settings & Flags'
             . '</h3>';
    $output .= '<ul style="'
             . 'list-style-type:disc;'
             . 'margin:0 0 0 20px;'
             . 'padding:0;'
             . '">';
    // Public
    $output .= '<li style="margin-bottom:6px; color:#555;">'
             . '<strong>public</strong>: '
             . ( $pt_obj->public ? 'true' : 'false' )
             . '</li>';
    // Show in REST
    $show_in_rest = isset( $pt_obj->show_in_rest ) ? $pt_obj->show_in_rest : false;
    $output     .= '<li style="margin-bottom:6px; color:#555;">'
                 . '<strong>show_in_rest</strong>: '
                 . ( $show_in_rest ? 'true' : 'false' )
                 . '</li>';
    // Menu Icon
    $menu_icon = isset( $pt_obj->menu_icon ) && $pt_obj->menu_icon !== '' 
                 ? esc_html( $pt_obj->menu_icon ) 
                 : '—';
    $output  .= '<li style="margin-bottom:6px; color:#555;">'
              . '<strong>menu_icon</strong>: '
              . $menu_icon
              . '</li>';
    // Delete with user (if registered via args)
    $del_with_user = isset( $pt_obj->delete_with_user ) && $pt_obj->delete_with_user 
                     ? 'true' 
                     : 'false';
    $output     .= '<li style="margin-bottom:6px; color:#555;">'
                 . '<strong>delete_with_user</strong>: '
                 . $del_with_user
                 . '</li>';
    $output .= '</ul>';

    // Close CPT container
    $output .= '</div>';

    return $output;
}








/**
 * Generic function to get the list of shortcodes formatted properly.
 *
 * @param callable $get_shortcode_list_function A callback function that returns an associative array of shortcodes.
 * @return string The formatted list of shortcodes.
 */
function get_formatted_shortcode_list($get_shortcode_list_function) {
    // Ensure the provided argument is a callable function.
    if (!is_callable($get_shortcode_list_function)) {
        return "Error: Provided argument is not a valid function.";
    }

    // Get the array of shortcodes from the provided function.
    $shortcodes = array_keys(call_user_func($get_shortcode_list_function));
    
    // Format the shortcodes.
    $shortcode_list = array_map(function($shortcode) {
        return "[$shortcode]";
    }, $shortcodes);

    // Combine and return the formatted shortcodes.
    return "<br />" . implode("<br />", $shortcode_list);
}










?>