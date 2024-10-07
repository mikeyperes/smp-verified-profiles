<?php

/**
 * Verified Profiles Settings Page
 * --------------------------------
 * This script adds a settings page to the WordPress admin menu under "Settings."
 * It includes various checks for themes, plugins, ACF field groups, and more.
 */

add_action('admin_menu', 'smp_vp_add_verified_profiles_menu');

/**
 * Add Verified Profiles Menu
 * --------------------------
 * Registers the "Verified Profiles" settings page in the WordPress admin menu.
 */
function smp_vp_add_verified_profiles_menu() {
    add_settings_menu(
        'Verified Profiles',       // Page title
        'Verified Profiles',       // Menu title
        'manage_options',          // Capability
        'verified-profiles',       // Menu slug
        'smp_vp_display_settings_page'   // Callback function
    );
}

/**
 * Display Settings Page Content
 * -----------------------------
 * Renders the content for the Verified Profiles settings page.
 */
function smp_vp_display_settings_page() {
    ?>
    <style>
        /* Minimalist styling */
        .verified-profiles-settings {
            margin-top:20px;
            font-family: Arial, sans-serif;
            max-width: 100%; /* Full-width, no centering */
            padding: 20px;
            background: #fff;
            border-left: 4px solid #007cba;
        }

        .verified-profiles-settings h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .verified-profiles-settings h2 {
            font-size: 20px;
            color: #007cba;
            margin-bottom: 15px;
            border-bottom: 1px solid #e1e1e1;
            padding-bottom: 5px;
        }

        .verified-profiles-settings h3 {
            font-size: 16px;
            color: #555;
            margin-bottom: 10px;
        }

        .verified-profiles-settings ul {
            list-style: none;
            padding-left: 0;
            margin-bottom: 20px;
        }

        .verified-profiles-settings ul li {
            padding: 8px;
            margin-bottom: 6px;
            background: #f9f9f9;
            border-left: 4px solid #ddd;
            transition: border-color 0.3s ease;
        }

        .verified-profiles-settings ul li:hover {
            border-left: 4px solid #007cba;
        }

        .verified-profiles-settings button.button-primary {
            background-color: #007cba;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .verified-profiles-settings button.button-primary:hover {
            background-color: #005a9c;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .verified-profiles-settings {
                padding: 15px;
            }
        }
    </style>

    <div class="verified-profiles-settings">
        <!-- Page Title -->
        <h1>Verified Profiles</h1>

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

        <!-- Section 2: Pre-checks -->
        <section id="pre-checks">
            <h2>Pre-checks</h2>

            <!-- Subsection: Theme Checks -->
            <div class="theme-checks">
                <h3>Theme</h3>
                <ul>
                    <li><?php display_check_status(is_theme_active('Hello Elementor'), 'Hello Elementor theme is active.', 'Hello Elementor theme is not active. Please activate it to use the Hello World Plugin.'); ?></li>
                    <li style="margin-left: 20px;"><?php display_check_status(is_theme_auto_update_enabled('hello-elementor'), 'Hello Elementor theme auto updates are enabled.', 'Hello Elementor theme auto updates are not enabled. Please enable them.'); ?></li>
                </ul>
            </div>

            <!-- Subsection: Plugins Checks -->
            <div class="plugins-checks">
                <h3>Plugins</h3>
                <?php 
                $plugins = smp_vp_get_plugins_list();
                foreach ($plugins as $plugin => $name): 
                    list($is_installed, $is_active, $is_auto_update_enabled) = check_plugin_status($plugin);
                ?>
                <ul>
                    <li><?php display_check_status($is_installed, "{$name} Plugin exists.", "{$name} Plugin does not exist. Please install it to use the Hello World Plugin."); ?></li>
                    <li style="margin-left: 20px;"><?php display_check_status($is_active, "{$name} Plugin is active.", "{$name} Plugin is not active. Please activate it to use the Hello World Plugin."); ?></li>
                    <li style="margin-left: 20px;"><?php display_check_status($is_auto_update_enabled, "{$name} Plugin auto updates are enabled.", "{$name} Plugin auto updates are not enabled. Please enable them."); ?></li>
                </ul>
                <?php endforeach; ?>
            </div>

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
    <?php
}