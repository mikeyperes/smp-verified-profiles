<?php
/*
Plugin Name: Verified Profiles - Scale My Publication (Michael Peres)
Description: Verified Profiles Functionality
Author: Michael Peres
Plugin URI: https://github.com/mikeyperes/smp-verified-profiles
Description: Verified Profile integration for Scale My Publication systems.
Version: 4.5
Text Domain: smp-verified-profiles
Domain Path: /languages
Author URI: https://michaelperes.com
GitHub Plugin URI: https://github.com/mikeyperes/smp-verified-profiles
GitHub Branch: main
*/ 
namespace smp_verified_profiles;
 
// Ensure this file is being included by a parent file
defined('ABSPATH') or die('No script kiddies please!');

// Generic functions import 
include_once("generic-functions.php");
 
// Define constants
class Config {
    public static $plugin_name = "Scale My Publication - Verified Profiles";
    public static $plugin_starter_file = "initialization.php";


    
    public static $settings_page_html_id = "smp_verified_profiles";
    public static $settings_page_name = "Verified Profiles - Settings";
    public static $settings_page_capability = "manage_options";
    public static $settings_page_slug = "smp-verified-profiles";
    public static $settings_page_display_title = "Verified Profiles - Settings";
    public static $plugin_short_id = "smp_vp";
    

    // Add this method to return the GitHub config dynamically
    public static function get_github_config() {
        return array(
            'slug' => plugin_basename(__FILE__), // Plugin slug
            'proper_folder_name' => 'smp-verified-profiles', // Proper folder name
            'api_url' => 'https://api.github.com/repos/mikeyperes/smp-verified-profiles', // GitHub API URL
            'raw_url' => 'https://raw.github.com/mikeyperes/smp-verified-profiles/main', // Raw GitHub URL
            'github_url' => 'https://github.com/mikeyperes/smp-verified-profiles', // GitHub repository URL
            'zip_url' => 'https://github.com/mikeyperes/smp-verified-profiles/archive/main.zip', // Zip URL for the latest version
            'sslverify' => true, // SSL verification for the download
            'requires' => '5.0', // Minimum required WordPress version
            'tested' => '1.1', // Tested up to WordPress version
            'readme' => 'README.md', // Readme file for version checking
            'access_token' => '', // Access token if required
        );
    }
}


// don’t load anything on the front-end
//if ( ! is_admin() )return;


// Always loaded on every admin page:
if ( is_admin() ) {
    // Only remove the shutdown hook on our settings page:
    if ( isset( $_GET['page'] ) && $_GET['page'] === Config::$settings_page_slug ) {
        remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
    }
}



hws_import_tool('GitHub_Updater.php', 'WP_GitHub_Updater');
// Automatically imports the class into your current namespace
//hws_alias_namespace_functions('hws_base_tools', 'smp_core_podcast_functionality');




    /**
 * Initialize GitHub Updater only after plugins have loaded and i18n is ready.
 */
add_action( 'admin_init', function() {
    $updater = new WP_GitHub_Updater( Config::get_github_config() );

    // if you still want your “force‐update‐check” debug hook:
    if ( isset( $_GET['force-update-check'] ) ) {
        wp_clean_update_cache();
        set_site_transient( 'update_plugins', null );
        wp_update_plugins();
        error_log( 'WP_GitHub_Updater: Forced plugin update check triggered.' );
    }
} );



// Array of plugins to check
$plugins_to_check = [
    'advanced-custom-fields-pro/acf.php',
    'advanced-custom-fields-pro-temp/acf.php'
];

// Initialize flags for active status
$acf_active = false;

// Check if any of the plugins is active
foreach ($plugins_to_check as $plugin) {
    list($installed, $active) = check_plugin_status($plugin);
    if ($active) {
        $acf_active = true;
        break; // Stop checking once we find an active one
    }
}

// If none of the ACF plugins are active, display a warning and prevent the plugin from running
if (!$acf_active) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>'.\smp_verified_profiles\Config::$plugin_name.'</strong> The Advanced Custom Fields (ACF) or Advanced Custom Fields Pro (ACF Pro) plugin is required and must be active to use this plugin. Please activate ACF or ACF Pro.</p></div>';
    });
    return; // Stop further execution of the plugin
}



function get_settings_snippets()
{


  //  $_verified_profile_settings    = get_verified_profile_settings();

    $settings_snippets = [

             
        [
            'id' => 'add_wp_admin_add_featured_image_to_events',
            'name' => 'add_wp_admin_add_featured_image_to_events',
            'description' => '',
            'info' => '',

            'function' => 'add_wp_admin_add_featured_image_to_events',
       
        ],

        [
            'id' => 'enable_acf_theme_options',
            'name' => 'enable_acf_theme_options',
            'description' => '',
            'info' => display_acf_structure(["group_6850930366d8f"]),

            'function' => 'enable_acf_theme_options',
       
        ],


        

        
     
        [
            'id' => 'register_profile_custom_post_type',
            'name' => 'register_profile_custom_post_type',
            'description' => '',
            'info' => '',
           // 'info' => display_cpt_structure($_verified_profile_settings['slug']),
            'function' => 'register_profile_custom_post_type'
        ],

        [
            'id' => 'register_profile_general_acf_fields',
            'name' => 'register_profile_acf_fields',
            'description' => display_acf_structure(
                [
                    'group_66b7bdf713e77',  // Post - Verified Profile - Admin
                    'group_656ea6b4d7088',  // Profile - Admin
                    'group_656eb036374de',  // Profile - Person - Public
                    'group_65a8b25062d91',  // User - Profile Manager
                    'group_658602c9eaa49',  // User - Verified Profile Manager - Admin
                ]
        ),
            'info' => '',
            'function' => 'register_profile_general_acf_fields'
        ],

        [
            'id' => 'register_verified_profile_custom_fields',
            'name' => 'register_verified_profile_custom_fields',
            'description' => display_acf_structure(["group_67e39e4171b16"]),
            'info' => '',
            'function' => 'register_verified_profile_custom_fields'
         ],  
          






             [
            'id' => 'register_user_custom_fields',
            'name' => 'register_user_custom_fields',
            'description' => display_acf_structure("group_verified_profiles_settings"),
            'info' => '',
            'function' => 'register_user_custom_fields'
        ],  
        
        
        
        [
            'id' => 'register_verified_profile_pages_custom_fields',
            'name' => 'register_verified_profile_pages_custom_fields',
            'description' => display_acf_structure("group_verified_profiles_settings"),
            'info' => '',
            'function' => 'register_verified_profile_pages_custom_fields'
        ],  

        [
            'id' => 'enable_snippet_adjust_wp_admin_for_profile_managers',
            'name' => 'enable_snippet_adjust_wp_admin_for_profile_managers',
            'description' => '',
            'info' => '',
            'function' => 'enable_snippet_adjust_wp_admin_for_profile_managers'
        ],  

        [
            'id' => 'enable_snippet_wp_admin_user_page_functionality',
            'name' => 'enable_snippet_wp_admin_user_page_functionality',
            'description' => '',
            'info' => '',
            'function' => 'enable_snippet_wp_admin_user_page_functionality'
        ],  
            [
                'id' => 'enable_snippet_adjust_profiles_category_meta_box',
                'name' => 'enable_snippet_adjust_profiles_category_meta_box',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_adjust_profiles_category_meta_box'
            ],
            [
                'id' => 'enable_snippet_adjust_wp_admin_for_profile_managers',
                'name' => 'enable_snippet_adjust_wp_admin_for_profile_managers',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_adjust_wp_admin_for_profile_managers'
            ],
            [
                'id' => 'enable_snippet_wp_admin_user_page_functionality',
                'name' => 'enable_snippet_wp_admin_user_page_functionality',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_wp_admin_user_page_functionality'
            ],
            [
                'id' => 'snippet_post_functionality',
                'name' => 'snippet_post_functionality',
                'description' => '',
                'info' => '',
                'function' => 'snippet_post_functionality'
            ],
            [
                'id' => 'enable_snippet_faviconn_for_verified_pages',
                'name' => 'enable_snippet_faviconn_for_verified_pages',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_faviconn_for_verified_pages'
            ],
            [
                'id' => 'enable_snippet_wp_admin_adjust_table_for_unclaimed_profiles',
                'name' => 'enable_snippet_wp_admin_adjust_table_for_unclaimed_profiles',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_wp_admin_adjust_table_for_unclaimed_profiles'
            ],
            [
                'id' => 'enable_snippet_woocommerce_base',
                'name' => 'enable_snippet_woocommerce_base',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_woocommerce_base'
            ],
            [
                'id' => 'enable_snippet_woocommerce_stripe_integration',
                'name' => 'enable_snippet_woocommerce_stripe_integration',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_woocommerce_stripe_integration'
            ],
            [
                'id' => 'enable_snippet_claim_profile_functionality',
                'name' => 'enable_snippet_claim_profile_functionality',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_claim_profile_functionality'
            ],
            [
                'id' => 'enable_snippet_profile_post_wp_admin_functionality',
                'name' => 'enable_snippet_profile_post_wp_admin_functionality',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_profile_post_wp_admin_functionality'
            ],
            [
                'id' => 'enable_snippet_wp_admin_user_page_optional_functionality',
                'name' => 'enable_snippet_wp_admin_user_page_optional_functionality',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_wp_admin_user_page_optional_functionality'
            ],
            [
                'id' => 'enable_snippet_muckrack_functionality',
                'name' => 'enable_snippet_muckrack_functionality',
                'description' => get_formatted_shortcode_list(__NAMESPACE__."\get_muckrack_shortcodes"),
                'info' => '',
                'function' => 'enable_snippet_muckrack_functionality'
            ],
            [
                'id' => 'enable_snippet_disable_password_reset',
                'name' => 'enable_snippet_disable_password_reset',
                'description' => '',
                'info' => '',
                'function' => 'enable_snippet_disable_password_reset'
            ],
            [
                'id' => 'enable_snippet_verified_profile_shortcodes',
                'name' => 'enable_snippet_verified_profile_shortcodes',
                'description' => get_formatted_shortcode_list(__NAMESPACE__."\get_verified_profile_shortcodes"),
                'info' => '',
                'function' => 'enable_snippet_verified_profile_shortcodes'
            ]
        
       

    ];
/*
    // Ensure closure results are handled
    foreach ($settings_snippets as &$snippet) {
        if (is_callable($snippet['info'])) {
            $snippet['info'] = $snippet['info'](); // Execute closure and replace it with the returned value
        }
    }
*/
    return $settings_snippets;
}


// Hook to acf/init to ensure ACF is initialized before running any ACF-related code
add_action('acf/init', function() {


  //  if (is_admin()) {
    include_once("register-acf-structure-theme-options.php");
    include_once("register-acf-structures.php");

    include_once("register-acf-user-profile.php");
    include_once("register-acf-verified-profile.php");

    //register_verified_profile_custom_fields();
 //   }
    
    


 if (is_admin()) {
include_once("snippet-adjust-profiles-category-meta-box.php");
    include_once("snippet-adjust-wp-admin-for-profile-managers.php");
    include_once("snippet-wp-admin-user-page-functionality.php");
    include_once("snippet-post-functionality.php");
    include_once("snippet-faviconn-for-verified-pages.php");
    include_once("snippet-wp-admin-adjust-table-for-unclaimed-profiles.php");
   include_once("snippet-woocommerce-base.php");
    include_once("snippet-woocommerce-stripe-integration.php");
    include_once("snippet-claim-profile-functionality.php");
    include_once("snippet-profile-post-wp-admin-functionality.php");
    include_once("snippet-wp-admin-user-page-optional-functionality.php");
    include_once("snippet-muckrack-functionality.php");
    include_once("snippet-disable-password-reset.php");

    include_once("snippet-wp-admin-add-featured-image-to-events.php");
    include_once("snippet-wp-admin-filter-featured-profiles.php");
 }
    





    // don’t load anything on the front-end
if (is_admin()) {

    include_once("profile-manager-dashboard.php");
    include_once("settings-dashboard-define-pages-and-listing-grids.php");
    
   
   
    include_once("settings-dashboard-system-checks.php");
    include_once("settings-dashboard-plugin-info.php");
    include_once("settings-dashboard-plugin-checks.php");
    include_once("settings-event-handling.php");
    include_once("setting-dashboard-process-schema-objects.php");


    include_once("settings-dashboard.php");

    
}






include_once("settings-dashboard-snippets.php");

    include_once("shortcodes.php");
include_once("snippet-shortcodes-entities.php");



include_once("activate-snippets.php");

/*
    // Register ACF Fields
    include_once("register-acf-press-release.php");
    include_once("register-acf-user.php");
    // Import Snippets 
    
    include_once("snippet-add-press-release-post-to-author.php");
    include_once("snippet-auto-delete.php");
    // Build Dashboards
    include_once("settings-dashboard-overview.php");
   




    
   include_once("settings-action-create-hexa-pr-wire-user.php");
   

   */
}, 11 );
?>