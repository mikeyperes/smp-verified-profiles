<?php namespace smp_verified_profiles;

// Hook the function into the admin menu action
add_action('admin_menu', __NAMESPACE__.'\add_custom_admin_pages');
add_action('admin_head', __NAMESPACE__.'\add_styles_admin_profiles_dashboard');
// Hook the function into the admin menu action
add_action('admin_menu', __NAMESPACE__.'\add_custom_admin_pages');
 
// Add custom admin pages
function add_custom_admin_pages() {
    // Perform pre-checks 
   // if (!perform_verified_profiles_plugin_prechecks()) return;

    // Check if the current user is a profile manager
    //if (!is_profile_manager()) return;
    
    // Add a Profiles Dashboard menu item
    add_menu_page('Profiles Dashboard', 'Profiles Dashboard', 'read', 'profiles-dashboard', 'display_admin_profiles_dashboard');
    
    // Global $submenu for potential submenu usage (not used in this snippet)
    global $submenu;
    
    // Getting the current user ID
    $user_id = get_current_user_id();
    
    // Fetch unclaimed profiles for the user
    $unclaimed_profiles = get_field('unclaimed_profiles', 'user_' . $user_id);
    // Uncomment the following line to debug the unclaimed profiles
    // var_dump($unclaimed_profiles);
}



// Add custom admin styles for tables
function add_styles_admin_profiles_dashboard() {
    echo '<style>
       .display_admin_profiles_dashboard .custom-admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .display_admin_profiles_dashboard .custom-admin-table,
        .display_admin_profiles_dashboard .custom-admin-table th,
        .display_admin_profiles_dashboard .custom-admin-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .display_admin_profiles_dashboard .custom-admin-table th {
            font-size: 1.1em;
        }
        .display_admin_profiles_dashboard .custom-admin-table td {
            font-size: 1em;
        }

        .display_admin_profiles_dashboard .header {
            margin-top: 30px;
            margin-bottom: 5px;
        }

        .display_admin_profiles_dashboard .container_display_admin_profiles_dashboard_footer a {
            display: block;
            margin-bottom: 10px;
        }
    </style>';
}

// Display unclaimed profiles in a table
function display_admin_unclaimed_profiles() {
    $user_id = get_current_user_id();
    $unclaimed_profiles = get_field('unclaimed_profiles', 'user_' . $user_id);

    echo '<section class="container_display_admin_unclaimed_profiles"><h2 class="header">Unclaimed Profiles</h2><table class="custom-admin-table">';
    echo '<tr><th>Profile ID</th><th>Profile Name</th><th>Profile URL</th><th>Profile Type</th><th>Claim This Profile</th></tr>';

    if (!empty($unclaimed_profiles)) {
        foreach ($unclaimed_profiles as $profile) {
            // Get the profile ID directly from the profile array
            $profile_id = $profile['profile'];

            // Check if profile ID exists
            if ($profile_id) {
                // Get the post object using the profile ID
                $profile_post = get_post($profile_id);

           // Check if the post object is valid
if ( $profile_post ) {
    $profile_title = get_the_title( $profile_id );
    $profile_url   = get_permalink( $profile_id );
    $profile_type  = implode( ', ', wp_get_post_terms( $profile_id, 'category', [ 'fields' => 'names' ] ) );
    
    // Dynamically build the claim profile URL using home_url()
    $claim_url = home_url( '/checkout/?add-to-cart=14140&unclaimed_profile_id=' . $profile_id );
    
    echo "<tr>";
    echo "<td>{$profile_id}</td>";
    echo "<td>{$profile_title}</td>";
    echo "<td><a href='" . esc_url( $profile_url ) . "' target=\"_blank\">View Profile</a></td>";
    echo "<td>{$profile_type}</td>";
    echo "<td><a href='" . esc_url( $claim_url ) . "' target=\"_blank\">Claim This Profile</a></td>";
    echo "</tr>";
}

            }
        }
    } else {
        echo '<tr><td colspan="5">No unclaimed profiles found.</td></tr>';
    }

    echo '</table></section>';
}

// Display claimed profiles in a table
function display_admin_claimed_profiles() {
    $user_id = get_current_user_id();

    // Query for profiles where the current user is the author
    $claimed_profiles = new WP_Query([
        'post_type' => 'profile',
        'author'    => $user_id,
    ]);

    echo '<section class="container_display_admin_claimed_profiles"><h2 class="header">Claimed Profiles</h2><table class="custom-admin-table">';
    echo '<tr><th>Profile ID</th><th>Profile Name</th><th>Profile URL</th><th>Profile Type</th><th>Actions</th></tr>';

    if ($claimed_profiles->have_posts()) {
        while ($claimed_profiles->have_posts()) {
            $claimed_profiles->the_post();
            $profile_id = get_the_ID();
            $profile_title = get_the_title();
            $profile_url = get_permalink();
            $profile_type = implode(', ', wp_get_post_terms($profile_id, 'category', ['fields' => 'names']));
            $edit_link = get_edit_post_link($profile_id);

            echo "<tr>";
            echo "<td>{$profile_id}</td>";
            echo "<td>{$profile_title}</td>";
            echo "<td><a href='{$profile_url}' target='_blank'>{$profile_url}</a></td>";
            echo "<td>{$profile_type}</td>";
            echo "<td><a href='{$profile_url}' target='_blank'>View</a> | <a href='{$edit_link}' target='_blank'>Edit</a></td>";
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="5">No claimed profiles found.</td></tr>';
    }

    echo '</table></section>';
    wp_reset_postdata(); // Reset the global post object
}

function display_admin_profiles_dashboard() {
    // Perform pre-checks
    if (!perform_verified_profiles_plugin_prechecks()) return;

    $user = wp_get_current_user();
    $price_verified_profile = get_field('price_verified_profile', 'user_' . $user->ID);
    $price_leadership_council = get_field('price_leadership_council', 'user_' . $user->ID);

    //MIKEREVIEW 

$verified_profile_link = empty($price_verified_profile)
    ? '#'
    : home_url('/checkout/?add-to-cart=14140&order_type=new');

$leadership_council_link = empty($price_leadership_council)
    ? '#'
    : home_url('/checkout/?add-to-cart=14142&order_type=new');


    echo '<section class="display_admin_profiles_dashboard">';
    display_admin_unclaimed_profiles();
    display_admin_claimed_profiles();
    //MIKEREVIEW
echo '<h2 class="header">Orders Dashboard</h2>
    <a target="_blank" href="' . esc_url( home_url( '/my-account/' ) ) . '">View Account Dashboard</a>';
    echo '<h2 class="header">Additional Links</h2>
    <div class="container_display_admin_profiles_dashboard_footer">
        <a href="' . esc_url( $verified_profile_link ) . '" class="apply-link" target="_blank">Apply for a new Her Forward Verified Profile</a>
        <a href="' . esc_url( home_url( '/her-forward-leadership-council/application-her-forward-leadership-council/' ) ) . '" target="_blank">Apply for The Her Forward Leadership Network</a>
    </div></section>';


      // Get the site's domain dynamically
$domain = parse_url( home_url(), PHP_URL_HOST );
    ?>
<script type="text/javascript">
jQuery(document).ready(function($){
    $("a").on("click", function(e){
        if ($(this).attr("href") === "#") {
            e.preventDefault();
            alert("Your account is not yet enabled to apply for this feature. Please reach out to contact@<?php echo $domain; ?> for further help.");
        }
    });
});
</script>
    <?php
}