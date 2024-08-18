<?php
/*
Plugin Name: Verified Profiles - Scale My Publication (Michael Peres)
Description: Verified Profiles Functionality
Author: Michael Peres
Plugin URI: https://github.com/mikeyperes/smp-verified-profiles
Description: Verified Profile integration for Scale My Publication systems.
Version: 1.0.5
Author URI: https://michaelperes.com
GitHub Plugin URI: https://github.com/mikeyperes/smp-verified-profiles
GitHub Branch: main
*/ 

// Ensure this file is being included by a parent file
defined('ABSPATH') or die('No script kiddies please!');

//Generic functions import
include_once("generic-functions.php");

//Precheck WordPress is set up correctly
include_once("wordpress-pre-check.php");

//Import ACF Fields for wp-admin settings page
include_once("register-acf-fields-settings-page.php");

//Import ACF Fields
include_once("register-acf-fields.php");

//Precheck WordPress is set up correctly
include_once("initiate-user-roles.php");

//Build Dashboard
include_once("settings-dashboard.php");

//Verified Profiles Manager
include_once("verified-profile-dashboard.php");

// Functionality to process empty Pages and Jet Engine Listing Grids
include_once("create-pages-and-listing-grids.php");

// Run updater check
//include_once("plugin-updater.php");

    // Include the WP_GitHub_Updater class file
if (file_exists(plugin_dir_path(__FILE__) . 'GitHub_Updater.php')) {
    require_once(plugin_dir_path(__FILE__) . 'GitHub_Updater.php');
} else {
    error_log('WP_GitHub_Updater.php file is missing.');
}

// Initialize the updater
if (is_admin()) { // Ensure this runs only in the admin area

    $config = array(
        'slug' => plugin_basename(__FILE__), // Plugin slug
        'proper_folder_name' => dirname(plugin_basename(__FILE__)), // Proper folder name
        'sslverify' => true, // SSL verification for the download
      //  'access_token' => 'YOUR_GITHUB_ACCESS_TOKEN', // GitHub access token (if required for private repositories)
        'api_url' => 'https://api.github.com/repos/mikeyperes/smp-verified-profiles', // GitHub API URL
        'raw_url' => 'https://raw.githubusercontent.com/mikeyperes/smp-verified-profiles/main', // Raw GitHub URL
        'github_url' => 'https://github.com/mikeyperes/smp-verified-profiles', // GitHub repository URL
        'zip_url' => 'https://github.com/mikeyperes/smp-verified-profiles/archive/main.zip', // Zip URL for the latest version
        'requires' => '5.0', // Minimum required WordPress version
        'tested' => '6.0', // Tested up to WordPress version
        'readme' => 'README.md', // Readme file for version checking
    );

    $updater = new WP_GitHub_Updater($config);
}