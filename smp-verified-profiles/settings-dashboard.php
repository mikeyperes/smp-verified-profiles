<?php namespace smp_verified_profiles;
 


// Abstract function to add a settings menu and page
function add_wp_admin_settings_page() {
    add_options_page(
        Config::$settings_page_name,       // Page title
        Config::$settings_page_name,       // Menu title
        Config::$settings_page_capability,        // Capability
        Config::$settings_page_slug,        // Menu slug
        __NAMESPACE__ . '\\display_wp_admin_settings_page'    // Callback function
    );
}

// Add settings menu and page
add_action('admin_menu', __NAMESPACE__ . '\\add_wp_admin_settings_page');


function display_wp_admin_settings_page() {
    
    if (ob_get_level() == 0) ob_start();
    

   
    ?>
   

<?php  

/*?>
        <!-- Section 1: ACF Field Groups Status -->
        <section id="acf-field-groups-status">
            <h2>ACF Field Groups Status</h2>
            <ul>
                <li><?php smp_vp_dashboard_display_check_status(is_acf_field_group_imported('group_656ea6b4d7088'), 'Profile - Admin fields are imported.', 'Profile - Admin fields are not imported.'); ?></li>
                <li><?php smp_vp_dashboard_display_check_status(is_acf_field_group_imported('group_656ea59dc5ad8'), 'Profile - Organization - Public fields are imported.', 'Profile - Organization - Public fields are not imported.'); ?></li>
                <li><?php smp_vp_dashboard_display_check_status(is_acf_field_group_imported('group_656eb036374de'), 'Profile - Person - Public fields are imported.', 'Profile - Person - Public fields are not imported.'); ?></li>
                <li><?php smp_vp_dashboard_display_check_status(is_acf_field_group_imported('group_66b7bdf713e77'), 'Post - Verified Profile - Admin fields are imported.', 'Post - Verified Profile - Admin fields are not imported.'); ?></li>
                <li><?php smp_vp_dashboard_display_check_status(is_acf_field_group_imported('group_65a8b25062d91'), 'User - Profile Manager fields are imported.', 'User - Profile Manager fields are not imported.'); ?></li>
                <li><?php smp_vp_dashboard_display_check_status(is_acf_field_group_imported('group_658602c9eaa49'), 'User - Verified Profile Manager - Admin fields are imported.', 'User - Verified Profile Manager - Admin fields are not imported.'); ?></li>
                <li style="opacity:.5"><?php smp_vp_dashboard_display_check_status(is_acf_field_group_imported('group_verified_profiles_settings'), 'Verified Profiles Settings fields are imported.', 'Verified Profiles Settings fields are not imported.'); ?></li>
            </ul>
        </section>
<?php */ ?>

      
<?php /*
            <!-- Subsection: Other Checks -->
            <div class="other-checks">
                <h3>Other Checks</h3>
                <ul>
                    <li><?php display_check_status(does_post_type_exist('profile'), '"Profile" Custom Post Type is active.', '"Profile" Custom Post Type is not active. Please register it to use the Hello World Plugin.'); ?></li>
                    <li style="margin-left: 20px;">
                        <?php display_check_status(does_taxonomy_exist('category'), 'Categories are enabled for "profile" CPT.', 'Categories are not enabled for "profile" CPT.'); ?>
                        <ul style="margin-left: 20px;">
                            <li><?php display_check_status(does_term_exist('person', 'category'), 'Category "Person" exists.', 'Category "Person" does not exist.'); ?></li>
                            <li><?php display_check_status(does_term_exist('organization', 'category'), 'Category "Organization" exists.', 'Category "Organization" does not exist.'); ?></li>
                        </ul>
                    </li>
                    <li><?php display_check_status(does_taxonomy_exist('post_tag'), 'Tags are enabled for "profile" CPT.', 'Tags are not enabled for "profile" CPT.'); ?></li>
                    <li><?php display_check_status(does_user_exist('unclaimed-profile'), 'The "unclaimed-profile" user exists.', 'The "unclaimed-profile" user does not exist. Please create this user to use the Hello World Plugin.'); ?></li>
                </ul>
            </div>
        </section>

        <!-- Section 3: Action Buttons -->
        <section id="action-buttons">
            <button id="create-categories-button" class="button button-primary">Create Verified Profile Categories (person and company)</button>
        </section>

        <!-- Section 4: ACF Field Management -->
        <section id="acf-field-management">
            <h2>ACF Field Management</h2>
            <?php display_settings_create_pages_and_listing_grids(); ?>
        </section>

        <!-- Section 5: Additional Settings -->
        <section id="additional-settings">
            <h2>Settings</h2>
            <?php display_settings_acf_post_and_pages_form(); ?>
        </section>
    </div>

    <!-- jQuery for handling button click -->
    <script type="text/javascript">
        jQuery(document).ready(function($) {
                        // Handle the "Create Verified Profile Categories" button click
            $('#create-categories-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);

                // Disable the button to prevent multiple submissions
                $button.prop('disabled', true).text('Creating...');

                // AJAX request to create categories
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        'action': 'create_verified_profile_categories'
                    },
                    success: function(response) {
                        // Reload the page after the request is successful
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        // Handle errors here
                        console.error('Error:', error);
                        alert('An error occurred while creating categories. Please try again.');
                        $button.prop('disabled', false).text('Create Verified Profile Categories (person and company)');
                    }
                });
            });
        });
    </script>
    <?php */

    ?>

    <style>
  /* Updated Minimalist Panel Styles with Depth */
.panel {
    margin-bottom: 20px;
    border: 1px solid #e0e0e0; /* Slightly lighter border for subtlety */
    border-radius: 6px; /* Slightly more rounded corners */
    background-color: #f9f9f9; /* Lighter background for a clean look */
    padding: 20px; /* Increased padding for better spacing */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); /* Subtle shadow for depth */
}

.panel-title {
    padding: 15px;
    border-bottom: 1px solid #ddd; /* Lighter border to separate the title */
    font-size: 18px; /* Slightly larger font for emphasis */
    font-weight: 600; /* Bold for better readability */
    color: #333; /* Darker text color for contrast */
    margin-bottom: 10px;
    border-radius: 4px 4px 0 0; /* Round the top corners */
}

.panel-content {
    padding: 15px;
    border-radius: 0 0 4px 4px; /* Round the bottom corners */
}

.button {
    padding: 10px 16px; /* Increased padding for a more prominent button */
    font-size: 14px;
    border-radius: 4px; /* Slightly more rounded for modern look */
    text-decoration: none;
    color: #fff;
    background-color: #0073aa; /* WordPress standard blue color */
    border: none;
    cursor: pointer;
    display: inline-block;
    margin-right: 10px;
    transition: background-color 0.3s ease; /* Smooth hover transition */
}

.button:hover {
    background-color: #005c89; /* Darker shade on hover */
}

/* WP-Config Constants Expand/Collapse */
.wp-config-expandable {
    margin-top: 0px;
    padding-top: 15px;
}


/* Styles for the toggle links */
.wp-config-toggle {
    cursor: pointer;
    color: #3b82f6;
    font-size: 16px;
    margin-bottom: 10px;
    display: inline-block;
    background-color: #e0f2fe;
    padding: 5px 10px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}
.wp-config-toggle:hover {
    background-color: #bfdbfe;
}
.wp-config-content {
    display: none;
    margin-top: 15px;
    padding: 15px;
    background-color: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
}
.wp-config-content ul {
    list-style: none;
    padding-left: 0;
}

.wp-config-content ul li {
    margin-bottom: 8px;
    padding: 8px;
    background-color: #f3f4f6;
    border-radius: 4px;
}

pre {
    background-color: #f3f4f6;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 13px;
    color: #1f2937;
}

/* Button styles */
.button {
    padding: 10px 15px;
    font-size: 14px;
    border-radius: 5px;
    text-decoration: none;
    color: #ffffff;
    background-color: #3b82f6;
    border: none;
    cursor: pointer;
    display: inline-block;
    margin-right: 10px;
    transition: background-color 0.3s ease;
}

.button:hover { 
    background-color: #2563eb;
}
</style>

<div class="wrap" id="<?php echo __NAMESPACE__;


//Config::$settings_page_html_id;?>">
<h1><?php echo Config::$settings_page_display_title;?></h1>

<?php display_settings_acf_post_and_pages_form();

display_settings_create_pages_and_listing_grids();

display_acf_field_status();
render_reprocess_profile_schema_page();
 
?>

<?php //display_settings_overview();?>

<?php display_verified_profile_settings();?>
<?php display_settings_system_checks();?>
<?php display_settings_check_plugins();?>
<?php //display_settings_theme_checks();?>
<?php display_settings_snippets();?>
<?php //hws_ct_display_settings_wp_config();?>
<?php //hws_ct_display_settings_php_ini();?>
<?php //if(get_option('enable_custom_rss_functionality', false)) display_settings_rss_dashboard();?>
<?php //if(get_option('enable_comments_management', false)) display_settings_comments_dashboard();?>
<?php display_plugin_info();?>
</div>
<?php
  // Get the buffer contents and clean (erase) the output buffer
  if (ob_get_level() != 0) echo ob_get_clean();
}
?>