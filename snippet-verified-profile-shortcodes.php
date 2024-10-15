<?php namespace smp_verified_profiles;

// Shortcode and action declarations at the top
add_shortcode('featured_in_posts', __NAMESPACE__ . '\\display_users_featured_posts');
add_shortcode('display_profile_council_banner', __NAMESPACE__ . '\\display_profile_council_banner');
add_shortcode('display_profile_quick_online_profiles', __NAMESPACE__ . '\\display_profile_quick_online_profiles');
add_shortcode('display_profile_quick_contact', __NAMESPACE__ . '\\display_profile_quick_contact');
add_shortcode('display_profile_education', __NAMESPACE__ . '\\display_profile_education');
add_shortcode('display_profile_organizations_founded', __NAMESPACE__ . '\\display_profile_organizations_founded');
add_shortcode('display_profile_contributing_articles', __NAMESPACE__ . '\\display_profile_contributing_articles');
add_shortcode('display_profile_current_residence', __NAMESPACE__ . '\\display_profile_current_residence');
add_shortcode('display_profile_press_releases', __NAMESPACE__ . '\\display_profile_press_releases');
add_shortcode('display_profile_validate_schema_button', __NAMESPACE__ . '\\display_profile_validate_schema_button');
add_shortcode('display_profile_location_born', __NAMESPACE__ . '\\display_profile_location_born');
add_shortcode('display_profile_notable_mentions', __NAMESPACE__ . '\\get_profile_notable_mentions');
add_shortcode('display_profile_internal_features', __NAMESPACE__ . '\\display_profile_internal_features');
add_shortcode('display_homepage_profiles', __NAMESPACE__ . '\\display_homepage_profiles');
add_shortcode('display_post_mentions', __NAMESPACE__ . '\\display_post_mentions');
add_shortcode('display_website_footer_external_profiles', __NAMESPACE__ . '\\display_website_footer_external_profiles');

// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_users_featured_posts')) {
    /**
     * Displays featured posts for a specific profile ID using JetEngine's Listing Grid.
     * Ensures the required class is available and manages ACF repeater queries.
     *
     * @return string Rendered HTML content of the featured posts or an error message.
     */
    function display_users_featured_posts() {
        // Ensure JetEngine class exists
        if (!class_exists('Jet_Engine_Render_Listing_Grid')) 
            return 'JetEngine is not active or the required class is not available.';

        global $post;
        $profile_id = 14481;
        $listing_id = 15006;
        $post_ids = [];

        // Add custom filter for modifying the ACF repeater query
        add_filter('posts_where', __NAMESPACE__ . '\\modify_posts_where_for_acf_repeater');

        // Query posts related to the profile
        $query = new \WP_Query([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'profiles_$_profile',
                    'value' => $profile_id,
                    'compare' => 'LIKE'
                ]
            ]
        ]);

        // Collect post IDs
        $post_ids = wp_list_pluck($query->posts, 'ID');

        // Remove filter after query
        remove_filter('posts_where', __NAMESPACE__ . '\\modify_posts_where_for_acf_repeater');

        // Configure JetEngine settings
        $settings = [
            'listing_id' => $listing_id,
            'columns' => 1,
            'columns_tablet' => 1,
            'columns_mobile' => 1,
            'post_status' => ['publish'],
            'posts_num' => 5,
            'posts_query' => [
                [
                    'type' => 'posts_params',
                    'posts_in' => $post_ids,
                    'post_type' => 'profile',
                ]
            ],
            'custom_query' => false,
            'custom_query_id' => null
        ];

        // Render the listing grid
        $listing_grid = new \Jet_Engine_Render_Listing_Grid($settings);
        ob_start();
        $listing_grid->render();
        $content = ob_get_clean();
        wp_reset_postdata();

        return $content;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_users_featured_posts function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\modify_posts_where_for_acf_repeater')) {
    /**
     * Modifies the SQL query for ACF repeater fields to include wildcard searches.
     *
     * @param string $where The existing WHERE clause.
     * @return string Modified WHERE clause.
     */
    function modify_posts_where_for_acf_repeater($where) {
        return str_replace("meta_key = 'profiles_$", "meta_key LIKE 'profiles_%", $where);
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\modify_posts_where_for_acf_repeater function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_council_banner')) {
    /**
     * Displays a council banner if the user is a council member.
     *
     * @return string Council banner HTML or an empty string.
     */
    function display_profile_council_banner() {
        $user_id = get_field('contributor_profile');

        // Ensure the user ID exists and retrieve council member status
        if ($user_id) {
            $is_council_member = get_field('council_member', 'user_' . $user_id);

            // Return the council member banner if applicable
            if ($is_council_member === true) 
                return '<style>.display_profile_council_banner i:before{content: "\f058";font-family: "Font Awesome 5 Free";font-weight: 900;color: red;font-size: 19px;margin-right: 10px;display: block;}</style><span class="display_profile_council_banner"><i class="fas"></i><span>Her Forward Leadership Council Member</span></span>';
        }

        return ''; // Return empty if not a council member
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_council_banner function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_quick_online_profiles')) {
    /**
     * Displays online profiles like Crunchbase, F6S, etc., for the current user.
     *
     * @return string HTML of online profiles or CSS to hide element if none exist.
     */
    function display_profile_quick_online_profiles() {
        $output = '<span class="shortcode_display_profile_quick_online_profiles">';
        $profile_added = false;

        $crunchbase = get_field('social_profiles_crunchbase');
        if (!empty($crunchbase)) {
            $output .= "<a target=_blank href='".esc_url($crunchbase)."'>Crunchbase</a> / ";
            $profile_added = true;
        }

        $f6s = get_field('social_profiles_f6s');
        if (!empty($f6s)) {
            $output .= "<a target=_blank href='".esc_url($f6s)."'>F6S</a> / ";
            $profile_added = true;
        }

        $the_org = get_field('social_profiles_the_org');
        if (!empty($the_org)) {
            $output .= "<a target=_blank href='".esc_url($the_org)."'>The Org</a> / ";
            $profile_added = true;
        }

        $imdb = get_field('social_profiles_imdb');
        if (!empty($imdb)) {
            $output .= "<a target=_blank href='".esc_url($imdb)."'>IMDb</a> / ";
            $profile_added = true;
        }

        $angel_list = get_field('social_profiles_angel_list');
        if (!empty($angel_list)) {
            $output .= "<a target=_blank href='".esc_url($angel_list)."'>AngelList</a> / ";
            $profile_added = true;
        }

        $muckrack = get_field('social_profiles_muckrack_url');
        if (!empty($muckrack)) {
            $output .= "<a target=_blank href='".esc_url($muckrack)."'>Muck Rack</a> / ";
            $profile_added = true;
        }

        $output = rtrim($output, ' / ');

        if (!$profile_added) 
            return '<style>.profile_quick_online_profiles{display:none !important}</style>';

        $output .= '</span>';
        return $output;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_quick_online_profiles function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_quick_contact')) {
    /**
     * Displays quick contact methods (email, WhatsApp, etc.) for the profile.
     *
     * @return string HTML of contact methods or CSS to hide element if none exist.
     */
    function display_profile_quick_contact() {
        $output = '<span class="shortcode_display_profile_quick_contact">';
        $contact_added = false;

        if (get_field('contact_information_email_preferred')) {
            $email = get_field('contact_information_email_email');
            if (!empty($email)) {
                $output .= "<a href='mailto:".esc_html($email) . '</a> / ';
                $contact_added = true;
            }
        }

        if (get_field('contact_information_whatsapp_preferred')) {
            $whatsapp_link = get_field('contact_information_whatsapp_link');
            if (!empty($whatsapp_link)) {
                $output .= '<a href="' . esc_url($whatsapp_link) . '">WhatsApp</a> / ';
                $contact_added = true;
            }
        }

        if (get_field('contact_information_telegram_preferred')) {
            $telegram_link = get_field('contact_information_telegram_link');
            if (!empty($telegram_link)) {
                $output .= '<a href="' . esc_url($telegram_link) . '">Telegram</a> / ';
                $contact_added = true;
            }
        }

        if (get_field('contact_information_signal_preferred')) {
            $signal_link = get_field('contact_information_signal_link');
            if (!empty($signal_link)) {
                $output .= '<a href="' . esc_url($signal_link) . '">Signal</a> / ';
                $contact_added = true;
            }
        }

        if (get_field('contact_information_calendly_preferred')) {
            $calendly_link = get_field('contact_information_calendly_link');
            if (!empty($calendly_link)) {
                $output .= '<a href="' . esc_url($calendly_link) . '">Book an Appointment</a> / ';
                $contact_added = true;
            }
        }

        // Remove the last ' / ' from the output
        $output = rtrim($output, ' / ');

        if (!$contact_added) 
            return '<style> .profile_quick_contact{display:none !important}</style>';

        $output .= '</span>';
        return $output;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_quick_contact function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_education')) {
    /**
     * Displays the education details for a profile using ACF repeater field.
     *
     * @return string HTML of the education section or CSS to hide element if none exist.
     */
    function display_profile_education() {
        $has_school_content = false;

        // Check if the repeater field has rows of data
        if (have_rows('personal_education')) {
            // Loop through each row to check for 'school' content
            while (have_rows('personal_education')) {
                the_row();
                $school = get_sub_field('school');
                if (!empty($school)) {
                    $has_school_content = true;
                    break;
                }
            }

            // If no 'school' field has content, hide the element
            if (!$has_school_content) 
                return '<style>.profile_education{display:none !important}</style>';

            // Reset the loop for output generation
            reset_rows();

            $output = '<span class="shortcode_display_profile_education">';

            // Loop through each row of data in the repeater for output
            while (have_rows('personal_education')) {
                the_row();
                $school = get_sub_field('school');
                if (empty($school)) continue;
                $degree = get_sub_field('degree');
                $wikipedia_url = get_sub_field('wikipedia_url');

                // Concatenate school and degree
                $education_text = esc_html($school) . ' - ' . esc_html($degree);

                // Check if the Wikipedia URL field is not empty
                if (!empty($wikipedia_url)) {
                    $output .= '<a href="' . esc_url($wikipedia_url) . '" target="_blank">' . $education_text . '</a><br />';
                } else {
                    $output .= $education_text . '<br />';
                }
            }

            $output .= '</span>';
            return $output;
        }

        return '<style> .profile_education{display:none !important}</style>';
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_education function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_organizations_founded')) {
    /**
     * Displays organizations founded by the profile using a repeater field.
     *
     * @return string HTML of the organizations or CSS to hide element if none exist.
     */
    function display_profile_organizations_founded() {
        $output = '<div class="shortcode_display_profile_organizations">';

        // Check if the 'organizations_founded' repeater field has rows of data
        if (have_rows('organizations_founded')) {
            $total_rows = count(get_field('organizations_founded'));
            $current_row = 0;

            while (have_rows('organizations_founded')) {
                the_row();
                $current_row++;
                $organization_id = get_sub_field('organization');

                if (!empty($organization_id)) {
                    $organization_name = get_the_title($organization_id);
                    $organization_link = get_field("url", $organization_id);
                    $organization_founded = get_field("founded", $organization_id);
                    $organization_headquarters_location = get_field("headquarters_location", $organization_id);
                    $organization_headquarters_wikipedia_url = get_field("headquarters_wikipedia_url", $organization_id);
                    $founder = get_the_title();
                    $social_profiles_crunchbase = get_field("social_profiles_crunchbase");

                    $organization_logo = get_the_post_thumbnail_url($organization_id, 'thumbnail');

                    // Format the output
                    $output .= '<div class="organization-entry">';
                    $output .= '<div class="left-column" style="float:left">';
                    if ($organization_logo) {
                        $output .= '<img class="featured_image" src="' . esc_url($organization_logo) . '" alt="' . esc_attr($organization_name) . '">';
                    }
                    $output .= '</div>'; // Close left column

                    $output .= '<div class="right-column" style="float:left">';
                    $output .= '<div style="line-height:initial"><a class="name" href="' . esc_url($organization_link) . '" target="_blank">' . esc_html($organization_name) . '</a></div>';
                    $output .= '<div style="line-height:initial">';
                    // Headquarters and Founding Date
                    if ($organization_headquarters_location) {
                        $hq_link_start = $organization_headquarters_wikipedia_url ? '<a href="' . esc_url($organization_headquarters_wikipedia_url) . '" target="_blank">' : '';
                        $hq_link_end = $organization_headquarters_wikipedia_url ? '</a>' : '';
                        $output .= '<p class="headquarters">' . $hq_link_start . esc_html($organization_headquarters_location) . $hq_link_end . '</p>';
                    }

                    if ($organization_founded) {
                        $output .= '<p class="founded">';
                        if ($organization_headquarters_location) $output .= ' (';
                        $output .= esc_html($organization_founded) . ')</p>';
                    }

                    $output .= '</div>'; // Close right column
                    $output .= '<div style="line-height:initial"><p class="founder">Founder: ' . esc_html($founder) . '</p></div>';
                    $output .= '</div>'; // Close right column
                    $output .= '</div>'; // Close organization entry

                    $organization_biography = get_field('biography', $organization_id);

                    if (!empty($organization_biography)) {
                        $output .= '<div style="clear:both"></div>
                                    <div class="biography">' . wp_kses_post($organization_biography) . '</div>';
                    }

                    if ($current_row < $total_rows) {
                        $output .= '<hr />';
                    }
                }
            }
        } else {
            return '<style>.profile_organizations_founded{display:none !important}</style>';
        }

        if ($social_profiles_crunchbase != "") {
            $output .= '<a class="view_more" href="' . esc_url($social_profiles_crunchbase) . '" target=_blank>Learn more on CrunchBase <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>';
        }

        $output .= '</div>';
        return $output;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_organizations_founded function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_contributing_articles')) {
    /**
     * Displays contributing articles for a profile using JetEngine Listing Grid.
     *
     * @return string HTML or a message if no articles are found.
     */
    function display_profile_contributing_articles() {
        $no_results = "<style>.profile_contributing_articles{display:none !important;}</style>";

        global $post;
        $contributor_id = get_field('contributor_profile', $post->ID);

        if (empty($contributor_id) || count_user_posts($contributor_id) == 0) 
            return $no_results;

        $listing_id = 15006;
        $content = '<div class="display_contributor_articles">';

        $args = [
            'post_type' => 'post',
            'posts_per_page' => 5,
            'author' => $contributor_id
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $post_ids = wp_list_pluck($query->posts, 'ID'); // Get array of post IDs

// Settings for JetEngine Listing Grid
$settings = [
    'listing_id' => $listing_id,
    'columns' => 1,
    'columns_tablet' => 1,
    'columns_mobile' => 1,
    'post_status' => ['publish'],
    'posts_num' => 5,
    'posts_query' => [
        [
            'type' => 'posts_params',
            'posts_in' => $post_ids,
            'post_type' => 'post'
        ]
    ],
    'custom_query' => false,
    'custom_query_id' => null
];

// Create and render the listing grid
$listing_grid = new \Jet_Engine_Render_Listing_Grid($settings);
ob_start();
$listing_grid->render();
$content .= ob_get_clean();
} else {
return $no_results;
}

wp_reset_postdata();

$contributor_profile = get_field("contributor_profile");

// Check if the contributor_profile field is not empty and contains a valid user ID
if (!empty($contributor_profile) && is_numeric($contributor_profile)) {
// Get the URL of the author's page
$author_url = get_author_posts_url($contributor_profile);

// Build the anchor tag with the author URL
$content .= '<a style="color:#333;text-decoration:underline;font-size:13px;" target=_blank href="' . esc_url($author_url) . '">View More <i aria-hidden="true" class="fas fa-angle-double-right"></i></a>';
}

$content .= '</div>';
return $content;
}
} else 
write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_contributing_articles function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_current_residence')) {
/**
* Displays the current residence of the profile.
*
* @return string HTML of the residence or CSS to hide element if none exist.
*/
function display_profile_current_residence() {
$current_residence = get_field('personal_current_residence_current_residence');
$current_residence_wikipedia_url = get_field('personal_current_residence_current_residence_wikipedia_url');

if (empty($current_residence)) 
return '<style> .profile_current_residence{display:none !important}</style>';

$output = '<span class="shortcode_display_profile_current_residence">';

if (!empty($current_residence_wikipedia_url)) {
$output .= '<a href="' . esc_url($current_residence_wikipedia_url) . '" target="_blank">' . esc_html($current_residence) . '</a>';
} else {
$output .= esc_html($current_residence);
}

$output .= '</span>';
return $output;
}
} else 
write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_current_residence function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_press_releases')) {
/**
* Displays press releases related to the profile using JetEngine's Listing Grid.
*
* @return string HTML or CSS to hide if no releases are found.
*/
function display_profile_press_releases() {
global $post;
$profile_id = $post->ID;
$post_ids = [];
$listing_id = 15315;

if (have_rows('additional_hexa_pr_wire_releases', $profile_id) == false && get_field('hexa_pr_wire_username', $profile_id) == "") {
return '<style>.profile_official_announcements{display:none !important}</style>';
}

$content = "<div class='display_profile_press_releases'>";
$hexa_pr_wire_username = get_field('hexa_pr_wire_username', $profile_id);

// Query press releases by author_slug
$author_slug_query = new \WP_Query([
'post_type' => 'press-release',
'posts_per_page' => 6,
'meta_query' => [
    [
        'key' => 'author_slug',
        'value' => $hexa_pr_wire_username,
        'compare' => '='
    ]
]
]);

// Add post IDs from author_slug query
$post_ids = array_merge($post_ids, wp_list_pluck($author_slug_query->posts, 'ID'));

// Get press releases from additional_hexa_pr_wire_releases repeater
if (have_rows('additional_hexa_pr_wire_releases', $profile_id)) {
while (have_rows('additional_hexa_pr_wire_releases', $profile_id)) {
    the_row();
    $press_release_id = get_sub_field('press_release');
    if (!in_array($press_release_id, $post_ids)) {
        $post_ids[] = $press_release_id;
    }
}
}

// JetEngine listing grid settings
$settings = [
'listing_id' => $listing_id,
'columns' => 2,
'columns_tablet' => 1,
'columns_mobile' => 1,
'post_status' => ['publish'],
'posts_num' => 10,
'posts_query' => [
    [
        'type' => 'posts_params',
        'posts_in' => $post_ids,
        'post_type' => 'press-release'
    ]
],
'custom_query' => false,
'custom_query_id' => null
];

// Render the listing grid
$listing_grid = new \Jet_Engine_Render_Listing_Grid($settings);
ob_start();
$listing_grid->render();
$content .= ob_get_clean();
wp_reset_postdata();
$content .= "</div>";

return $content;
}
} else 
write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_press_releases function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_validate_schema_button')) {
/**
* Displays a button to validate the schema of the current profile page.
*
* @return string HTML of the schema validation button.
*/
function display_profile_validate_schema_button() {
return '<a target=_blank href="https://validator.schema.org/#url=' . get_the_permalink() . '">Validate schema of ' . get_the_title() . '<i aria-hidden="true" class="fas fa-external-link-square-alt"></i></a>';
}
} else 
write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_validate_schema_button function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_location_born')) {
/**
* Displays the place of birth of the profile.
*
* @return string HTML of the place of birth or CSS to hide element if none exist.
*/
function display_profile_location_born() {
$location_born = get_field('personal_location_born_location_born');
$location_born_wikipedia_url = get_field('personal_location_born_location_born_wikipedia_url');

if (empty($location_born)) {
return '<style> .display_profile_location_born{display:none !important}</style>';
}

$output = '<span class="shortcode_display_profile_location_born">';

if (!empty($location_born_wikipedia_url)) {
$output .= '<a href="' . esc_url($location_born_wikipedia_url) . '" target="_blank">' . esc_html($location_born) . '</a>';
} else {
$output .= esc_html($location_born);
}

$output .= '</span>';
return $output;
}
} else 
write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_location_born function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\get_profile_notable_mentions')) {
/**
* Displays notable recognitions for the profile using a repeater field.
*
* @return string HTML of notable recognitions or CSS to hide element if none exist.
*/
function get_profile_notable_mentions() {
if (!have_rows('notable_recognitions')) {
return "<style>.profile_notable_recognitions{display:none !important}</style>";
}

$output = '<div class="container_notable_recognitions">';
$total_rows = count(get_field('notable_recognitions'));
$current_row = 0;

while (have_rows('notable_recognitions')) {
the_row();
$current_row++;
$title = get_sub_field('title');
$link = get_sub_field('link');
$source = get_sub_field('source');

if (empty($title) && empty($source)) continue;

$output .= '<div><a href="' . esc_url($link) . '" target="_blank"><span class="source">' . esc_html($source) . '</span> - <span class="title">' . esc_html($title) . '</span><i aria-hidden="true" class="fas fa-external-link-square-alt"></i></a></div>';

if ($current_row < $total_rows) {
    $output .= '<hr>';
}
}

$output .= '</div>';
        return $output;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\get_profile_notable_mentions function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_internal_features')) {
    /**
     * Displays internal features related to the profile using JetEngine's Listing Grid.
     *
     * @return string HTML or a message if no features are found.
     */
    function display_profile_internal_features() {
        global $post;
        $profile_id = $post->ID;
        $post_ids = find_posts_with_profile($profile_id);
        $no_results = "<style>.profile_internal_features{display:none !important;}</style>";

        if (empty($post_ids)) {
            return $no_results;
        }

        $listing_id = 15006;
        $content = '<div class="profile-internal-features">';

        // Settings for JetEngine Listing Grid
        $settings = [
            'listing_id' => $listing_id,
            'columns' => 1,
            'columns_tablet' => 1,
            'columns_mobile' => 1,
            'post_status' => ['publish'],
            'posts_num' => count($post_ids),
            'posts_query' => [
                [
                    'type' => 'posts_params',
                    'posts_in' => $post_ids,
                    'post_type' => 'post'
                ]
            ],
            'custom_query' => false,
            'custom_query_id' => null
        ];

        // Create and render the listing grid
        $listing_grid = new \Jet_Engine_Render_Listing_Grid($settings);
        ob_start();
        $listing_grid->render();
        $content .= ob_get_clean();
        wp_reset_postdata();
        $content .= '</div>';
        return $content;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_internal_features function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_homepage_profiles')) {
    /**
     * Displays the most recent profiles for the homepage using JetEngine's Listing Grid.
     *
     * @return string HTML of recent profiles or a message if none are found.
     */
    function display_homepage_profiles() {
        $no_results = "nothing";

        if (!class_exists('Jet_Engine_Render_Listing_Grid')) {
            return 'JetEngine is not active or the required class is not available.';
        }

        $args = [
            'post_type' => 'profile',
            'posts_per_page' => 30,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC'
        ];
        $recent_profiles = get_posts($args);

        if (!empty($recent_profiles)) {
            $profile_ids = [];
            foreach ($recent_profiles as $profile) {
                if ($profile->ID && has_post_thumbnail($profile->ID)) {
                    $profile_ids[] = $profile->ID;
                    if (count($profile_ids) >= 7) {
                        break;
                    }
                }
            }
        } else {
            return $no_results;
        }

        $content = '<div class="display_home_profiles">';
        $listing_id = 17978;

        // Settings for JetEngine Listing Grid
        $settings = [
            'listing_id' => $listing_id,
            'columns' => 7,
            'lazy_load' => false,
            'columns_tablet' => 4,
            'columns_mobile' => 2,
            'post_status' => ['publish'],
            'posts_num' => count($profile_ids),
            'posts_query' => [
                [
                    'type' => 'posts_params',
                    'posts_in' => $profile_ids,
                    'post_type' => 'profile'
                ]
            ],
            'custom_query' => false,
            'custom_query_id' => null
        ];

        // Create and render the listing grid
        $listing_grid = new \Jet_Engine_Render_Listing_Grid($settings);
        ob_start();
        $listing_grid->render();
        $content .= ob_get_clean();
        $content .= '</div>';
        return $content;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_homepage_profiles function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_post_mentions')) {
    /**
     * Displays profiles mentioned in a post using JetEngine's Listing Grid.
     *
     * @return string HTML or a message if no profiles are found.
     */
    function display_post_mentions() {
        $no_results = "<style>.display_post_mentions{display:none !important}</style>";

        if (!class_exists('Jet_Engine_Render_Listing_Grid')) {
            return 'JetEngine is not active or the required class is not available.';
        }

        if (!is_single()) {
            return 'This shortcode is only for single post pages.';
        }

        global $post;
        $profile_ids = [];

        // Iterate over repeater field and get profile IDs
        if (have_rows('profiles', $post->ID)) {
            while (have_rows('profiles', $post->ID)) {
                the_row();
                $profile_id = get_sub_field('profile');
                if ($profile_id && has_post_thumbnail($profile_id)) {
                    $profile_ids[] = $profile_id;
                }
            }
        }

        if (empty($profile_ids)) {
            return $no_results;
        }

        $content = '<div class="display_post_mentions">';
        $listing_id = 15768;

        // Settings for JetEngine Listing Grid
        $settings = [
            'listing_id' => $listing_id,
            'columns' => 7,
            'lazy_load' => false,
            'columns_tablet' => 5,
            'columns_mobile' => 3,
            'post_status' => ['publish'],
            'posts_num' => count($profile_ids),
            'posts_query' => [
                [
                    'type' => 'posts_params',
                    'posts_in' => $profile_ids,
                    'post_type' => 'profile'
                ]
            ],
            'custom_query' => false,
            'custom_query_id' => null
        ];

        // Create and render the listing grid
        $listing_grid = new \Jet_Engine_Render_Listing_Grid($settings);
        ob_start();
        $listing_grid->render();
        $content .= ob_get_clean();
        $content .= '</div>';
        return $content;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_post_mentions function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_website_footer_external_profiles')) {
    /**
     * Displays external profiles like Crunchbase, Google News, etc. in the website footer.
     *
     * @return string HTML of the external profiles or empty string if none exist.
     */
    function display_website_footer_external_profiles() {
        $fields = [
            'muckrack_url' => 'MuckRack',
            'crunchbase_url' => 'CrunchBase',
            'google_news_url' => 'Google News',
            'imdb_url' => 'iMDb',
            'f6s_url' => 'F6S',
            'the_org_url' => 'The Org'
        ];

        $links = [];
        foreach ($fields as $field => $name) {
            $url = get_field($field, 'option');
            if (!empty($url)) {
                $url = rtrim($url, '/');
                $links[] = "<a href=\"{$url}\" target=\"_blank\" style=\"color:#333; font-size:13px;\">{$name}</a>";
            }
        }

        // Create string with ' / ' after each link and remove the last ' / '
        $output = '<div>' . implode(' / ', $links);
        $output = rtrim($output, ' / '); // Remove the last ' / '
        $output .= '</div>';

        return $output;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_website_footer_external_profiles function is already declared", true);?>


