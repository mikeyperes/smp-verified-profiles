<?php namespace smp_verified_profiles;

function enable_snippet_adjust_wp_admin_for_profile_managers()
{
add_action('admin_init', __NAMESPACE__.'\disable_elementor_for_non_admins');
add_filter('login_redirect', __NAMESPACE__.'\redirect_profile_manager_after_login', 10, 3);
add_action('init', __NAMESPACE__.'\create_verified_profile_manager_role');
add_action('admin_head', __NAMESPACE__.'\hide_add_new_buttons_in_sidebar');
add_action('do_meta_boxes', __NAMESPACE__.'\remove_meta_boxes', 100);
add_action('admin_menu', __NAMESPACE__.'\hide_pages_for_profile_manager', 100000);
add_action('admin_head', __NAMESPACE__.'\hide_elementor_edit_button_for_non_admins');
add_action('admin_head', __NAMESPACE__.'\modify_profile_post_link');
add_action('admin_menu', __NAMESPACE__.'\change_profile_menu_label');
add_action('init', __NAMESPACE__.'\add_profile_capabilities_to_verified_profile_manager');
add_action('admin_init', __NAMESPACE__.'\restrict_add_new_post_access_for_verified_profile_manager');
}


// Uncomment this line if you want to enable redirection
// add_action('admin_init', 'redirect_verified_profile_manager');
// Uncomment if needed
// add_filter('register_post_type_args', 'modify_profile_post_type_labels', 10, 2);
// Uncomment if needed
// add_action('pre_get_posts', 'restrict_verified_profile_manager_to_own_posts');


/**
 * Disable Elementor features for non-admin users.
 */
function disable_elementor_for_non_admins() {
    // Only execute for non-administrator users
    if (!current_user_can('administrator')) {
        // Remove Elementor action from the admin bar
        remove_action('admin_bar_menu', array(\Elementor\Plugin::$instance->admin, 'add_edit_in_dashboard'), 200);
        // Remove Elementor switch mode action from the post edit screen
        remove_action('edit_form_after_title', ['\Elementor\Plugin::$instance->documents_manager', 'add_switch_mode_to_edit_form']);
    }
}


/**
 * Redirect Profile Manager to 'Profile' post type after login.
 *
 * @param string $redirect_to The default redirection URL.
 * @param string $request The requested redirection URL.
 * @param WP_User $user The current user object.
 * @return string The modified redirection URL.
 */
function redirect_profile_manager_after_login($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        // Check if the user is a Profile Manager
        if (is_profile_manager(true)) {
            return admin_url('edit.php?post_type=profile'); // Redirect to 'Profile' post type
        }
    }
    return $redirect_to; // Return default redirection if not a Profile Manager
}

/**
 * Redirect Profile Manager to 'Profile' post type dashboard when accessing the admin dashboard.
 */
function redirect_verified_profile_manager() {
    global $pagenow;

    // Only redirect if the user is a Profile Manager and is accessing the dashboard
    if (is_profile_manager(true) && !wp_doing_ajax() && $pagenow === 'index.php') {
        wp_redirect(admin_url('edit.php?post_type=profile'));
        exit;
    }
}

/**
 * Modify post type labels for 'profile' CPT based on the user's role.
 *
 * @param array  $args The post type arguments.
 * @param string $post_type The current post type.
 * @return array The modified post type arguments.
 */
function modify_profile_post_type_labels($args, $post_type) {
    if ('profile' === $post_type && is_profile_manager(true)) {
        // Change post type labels for Profile Managers
        $args['labels']['name'] = 'Claimed Profiles';
        $args['labels']['singular_name'] = 'Claimed Profile';
    }
    return $args;
}

/**
 * Create 'Verified Profile Manager' role with custom capabilities.
 */
function create_verified_profile_manager_role() {
    if (!get_role('verified_profile_manager')) {
        add_role('verified_profile_manager', 'Verified Profile Manager', array(
            'read' => true, // Access admin dashboard
            'edit_posts' => true, // Allow editing posts
            'create_posts' => true, // Allow creating new posts
            'upload_files' => true, // Allow uploading files
            'edit_files' => true, // Allow editing media library files

            // Capabilities for 'profile' CPT
            'edit_profile' => true,
            'read_private_profiles' => true,
        ));
    }

    if (!get_role('contributor')) {
        add_role('contributor', 'Contributor', array(
            'read' => true,
            'edit_posts' => true,
            'create_posts' => true,
            'edit_published_posts' => true,
            'upload_files' => true,
            'edit_files' => true,
        ));
    } else {
        // Modify Contributor role if already exists
        $role = get_role('contributor');
        $role->add_cap('edit_posts');
        $role->add_cap('create_posts');
        $role->add_cap('upload_files');
        $role->add_cap('edit_files');
        $role->add_cap('edit_published_posts');
        $role->remove_cap('publish_posts');
        $role->remove_cap('edit_others_posts');
    }
}

/**
 * Remove meta boxes for Profile Managers and Contributors.
 */
function remove_meta_boxes() {
    if (is_profile_manager(true)) {
        remove_meta_box('categorydiv', 'profile', 'side');
        remove_meta_box('tagsdiv-post_tag', 'profile', 'side');
        remove_meta_box('authordiv', 'profile', 'normal');
    }
}

/**
 * Hide admin menu pages for Profile Managers and Contributors.
 */
function hide_pages_for_profile_manager() {
    if (is_profile_manager(true)) {
        remove_menu_page('index.php'); // Hide Dashboard
        remove_menu_page('tools.php'); // Hide Tools
        remove_menu_page('edit.php?post_type=team-member'); // Hide 'Team' post type
        // Add more menu items to hide as needed
    }

    if (is_contributor(true)) {
        remove_menu_page('index.php');
        remove_menu_page('tools.php');
    }
}

/**
 * Hide Elementor edit button for non-admin users.
 */
function hide_elementor_edit_button_for_non_admins() {
    if (!current_user_can('administrator')) {
        echo '<style>
            #elementor-switch-mode,
            #footer-left {
                display: none !important;
            }
        </style>';
    }
}

/**
 * Hide 'Add New' buttons for Profile Managers.
 */
function hide_add_new_buttons_in_sidebar() {
    if (!is_profile_manager(true)) return;

    $style = '<style type="text/css">';
    if (!is_contributor(true)) {
        $style .= '.post-type-post .page-title-action,';
    }
    $style .= '
        .post-type-profile .page-title-action,
        #edit-slug-box,
        #menu-posts li a[href="post-new.php"],
        .subsubsub .count,
        #menu-posts-profile li a[href="post-new.php?post_type=profile"] {
            display: none !important;
        }
    </style>';
    echo $style;
}

/**
 * Modify the 'Add New' button for 'profile' post type to redirect to profile dashboard.
 */
function modify_profile_post_link() {
    if (is_profile_manager(true)) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Change 'Add New' button text and link to Profile Dashboard
                $('a.page-title-action[href*="post_type=profile"]').text('View Profile Dashboard').attr('target', '_blank');

                // Redirect to custom profile dashboard
                $(document).on('click', 'a.page-title-action[href*="post_type=profile"]', function(e) {
                    e.preventDefault();
                    window.open(window.location.origin + '/wp-admin/admin.php?page=profiles-dashboard', '_blank');
                });
            });
        </script>
        <?php
    }
}

/**
 * Change menu labels for Profile Managers.
 */
function change_profile_menu_label() {
    if (is_profile_manager(true)) {
        global $menu, $submenu;
        $menu[70][0] = 'Manager Profile'; // Change 'Profile' to 'Manager Profile'
        if (isset($submenu['profile.php'])) {
            foreach ($submenu['profile.php'] as $index => $menu_item) {
                if ($menu_item[2] === 'profile.php') {
                    $submenu['profile.php'][$index][0] = 'Manager Profile';
                }
            }
        }
    }
}

/**
 * Restrict 'Verified Profile Managers' and 'Contributors' to their own posts in the admin.
 *
 * @param WP_Query $query The current query object.
 */
function restrict_verified_profile_manager_to_own_posts($query) {
    if (is_admin() && (is_profile_manager(true) || is_contributor(true))) {
        $user_id = get_current_user_id();
        if ($query->is_main_query() && $query->get('post_type') === 'profile') {
            $query->set('author', $user_id);
        } elseif ($query->is_main_query() && $query->get('post_type') === 'post') {
            $query->set('author', $user_id);
        }
    }
}

/**
 * Add custom capabilities to 'Verified Profile Manager' role for managing profiles.
 */
function add_profile_capabilities_to_verified_profile_manager() {
    $role = get_role('verified_profile_manager');
    if ($role) {
        // Grant capabilities for the 'profile' custom post type (CPT)
        $role->add_cap('edit_profile');
        $role->add_cap('edit_profiles');
        $role->add_cap('edit_published_profiles');
        $role->add_cap('publish_profiles');
        $role->add_cap('read_private_profiles');
        $role->add_cap('delete_profile');
        $role->add_cap('delete_others_profiles');
        $role->add_cap('delete_private_profiles');
        $role->add_cap('delete_published_profiles');

        // Grant general WordPress capabilities for posts and uploads
        $role->add_cap('edit_posts');
        $role->add_cap('edit_others_posts');
        $role->add_cap('delete_posts');
        $role->add_cap('delete_others_posts');
        $role->add_cap('read_private_posts');
        $role->add_cap('edit_published_posts');
        $role->add_cap('upload_files');
    }

    // Modify contributor capabilities as needed
    if (is_contributor(true)) {
        $contributor_role = get_role('contributor');
        if ($contributor_role) {
            $contributor_role->add_cap('upload_files');
        }
    }
}

/**
 * Restrict the 'Add New Post' page for 'Verified Profile Managers'.
 */
function restrict_add_new_post_access_for_verified_profile_manager() {
    global $pagenow;

    // Check if the user is a Verified Profile Manager and is accessing 'post-new.php'
    if (!is_contributor(true) && is_profile_manager(true) && 'post-new.php' === $pagenow) {
        if (current_user_can('verified_profile_manager')) {
            wp_redirect(admin_url('edit.php')); // Redirect to 'All Posts' page
            exit;
        }
    }
}
