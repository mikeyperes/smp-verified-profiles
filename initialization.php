<?php
/*
Plugin Name: Verified Profiles - Scale My Publication (Michael Peres)
Description: Verified Profiles Functionality
Version: 1.0
Author: Michael Peres
*/

//Precheck WordPress is set up correctly
include_once("wordpress-pre-check.php");

// Ensure this file is being included by a parent file
defined('ABSPATH') or die('No script kiddies please!');

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