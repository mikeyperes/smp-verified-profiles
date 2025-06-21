<?php namespace smp_verified_profiles;
use Elementor\Plugin;





add_shortcode( 'contributor_network', __NAMESPACE__ . '\\contributor_network_shortcode' );
add_shortcode( 'verified_profile', __NAMESPACE__ . '\\verified_profile_shortcode' );





// Function to get the list of shortcodes for both registration and listing
function get_verified_profile_shortcodes() {
    return [
        'display_single_profile_education' => __NAMESPACE__ . '\\display_single_profile_education',
        'display_single_profile_organizations_founded' => __NAMESPACE__ . '\\display_single_profile_organizations_founded',
        'display_single_profile_press_releases' => __NAMESPACE__ . '\\display_single_profile_press_releases',
        'display_single_profile_article_written_by' => __NAMESPACE__ . '\\display_single_profile_article_written_by',
        'display_single_profile_articles_featured_in' => __NAMESPACE__ . '\\display_single_profile_articles_featured_in',
        'display_single_profile_text_based_social_profiles' => __NAMESPACE__ . '\\display_single_profile_text_based_social_profiles',
        'display_homepage_profiles' => __NAMESPACE__ . '\\display_homepage_profiles',
        //OLD style name deletemikey
        'display_single_post_mentioned_in_article' => __NAMESPACE__ . '\\display_single_post_mentioned_in_article',
        'display_theme_footer_text_social_links' => __NAMESPACE__ . '\\display_theme_footer_text_social_links',
        'display_single_profile_validate_schema_button' => __NAMESPACE__ . '\\display_single_profile_validate_schema_button',
        

        'display_profiles_featured_in_single_post' => __NAMESPACE__ . '\\display_profiles_featured_in_single_post',
        'display_profile_council_banner' => __NAMESPACE__ . '\\display_profile_council_banner',
        'display_profile_quick_contact' => __NAMESPACE__ . '\\display_profile_quick_contact',
        'display_profile_current_residence' => __NAMESPACE__ . '\\display_profile_current_residence',
        'display_profile_location_born' => __NAMESPACE__ . '\\display_profile_location_born',
        'display_profile_notable_mentions' => __NAMESPACE__ . '\\get_profile_notable_mentions',
    ];
}

// Function to register the shortcodes using the list
function enable_snippet_verified_profile_shortcodes() {
    $shortcodes = get_verified_profile_shortcodes();
    
    foreach ($shortcodes as $shortcode => $callback) {
        add_shortcode($shortcode, $callback);
    }
}














// Ensure function existence before declaring
if ( ! function_exists( __NAMESPACE__ . '\\display_profiles_featured_in_single_post' ) ) {
    /**
     * Displays featured posts for a specific profile ID using an Elementor Loop Item template.
     * Leaves the original ACF fetch lines commented out for reference.
     *
     * @return string Rendered HTML or CSS to hide if no posts found.
     */
    function display_profiles_featured_in_single_post() {
        global $post;

        // Commented out ACF fetch for profile and listing IDs
        // $profile_id = 14481;
        // $listing_id = 15006;
        // $listing_id = get_field( 'display_single_profile_articles_featured_in', 'option' );

        // Add custom filter for modifying the ACF repeater query
        add_filter( 'posts_where', __NAMESPACE__ . '\\modify_posts_where_for_acf_repeater' );

        // Query posts related to the profile
        $query = new \WP_Query( [
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'profiles_$_profile',
                    'value'   => $profile_id,
                    'compare' => 'LIKE',
                ],
            ],
        ] );

        // Remove filter after query
        remove_filter( 'posts_where', __NAMESPACE__ . '\\modify_posts_where_for_acf_repeater' );

        if ( ! $query->have_posts() ) {
            wp_reset_postdata();
            return '<style>.profiles_featured_in_single_post{display:none !important;}</style>';
        }

        // Render each post via Elementor Loop Item template ID 43689
        $content = '<div class="profiles_featured_in_single_post">';
        while ( $query->have_posts() ) {
            $query->the_post();
            $content .= \Elementor\Plugin::instance()
                ->frontend
                ->get_builder_content_for_display( 43689 );
        }
        wp_reset_postdata();

        $content .= '</div>';
        return $content;
    }
} else {
    write_log(
        "⚠️ Warning: " . __NAMESPACE__ . "\\display_profiles_featured_in_single_post already declared",
        true
    );
}















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
if (!function_exists(__NAMESPACE__ . '\\display_single_profile_text_based_social_profiles')) {
    /**
     * Displays online profiles like Crunchbase, F6S, etc., for the current user.
     *
     * @return string HTML of online profiles or CSS to hide element if none exist.
     */
    function display_single_profile_text_based_social_profiles() {
        $output = '<span class="shortcode_display_single_profile_text_based_social_profiles">';
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
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_single_profile_text_based_social_profiles function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_profile_quick_contact')) {
    /**
     * Displays quick contact methods (email, WhatsApp, etc.) for the profile.
     *
     * @return string HTML of contact methods or CSS to hide element if none exist.
     */
    function display_profile_quick_contact() {return;
        $output = '<span class="shortcode_display_profile_quick_contact">';
        $contact_added = false;

        if (get_field('contact_information_email_preferred')) {
            $email = get_field('contact_information_email_email');
            if (!empty($email)) {
                $output .= "<a href='mailto:".esc_html($email) . '>'.esc_html($email).'</a> / ';
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
  // Only remove the exact trailing delimiter once
if ( substr( $output, -3 ) === ' / ' ) {
    $output = substr( $output, 0, -3 );
}

        if (!$contact_added) 
            return '<style> .shortcode_display_profile_quick_contact{display:none !important}</style>';

        $output .= '</span>';
        return $output;
    }
} else 
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_quick_contact function is already declared", true);


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_single_profile_education')) {
    /**
     * Displays the education details for a profile using ACF repeater field.
     *
     * @return string HTML of the education section or CSS to hide element if none exist.
     */
    function display_single_profile_education() {
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
if (!function_exists(__NAMESPACE__ . '\\display_single_profile_organizations_founded')) {
    /**
     * Displays organizations founded by the profile using a repeater field.
     *
     * @return string HTML of the organizations or CSS to hide element if none exist.
     */
    function display_single_profile_organizations_founded() {
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
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_single_profile_organizations_founded function is already declared", true);










    
// Ensure function existence before declaring
if ( ! function_exists( __NAMESPACE__ . '\\display_single_profile_article_written_by' ) ) {
    /**
     * Displays contributing articles for a profile using an Elementor Loop Item template.
     *
     * @return string HTML (or CSS to hide the container if no articles are found).
     */
    function display_single_profile_article_written_by() {
        // If no contributor or they have zero posts, completely hide this section
        $no_results = "<style>.single_profile_article_written_by{display:none !important;}</style>";

        global $post;
        $contributor_id = get_field( 'contributor_profile', $post->ID );
        if ( empty( $contributor_id ) || count_user_posts( $contributor_id ) === 0 ) {
            return $no_results;
        }

                        // pull the template ID from your ACF “display_single_profile_press_releases” option
//$template_id = get_field( 'display_single_profile_article_written_by', 'option' );

// fetch the entire Verified Profile options group once
$vp = get_field( 'verified_profile', 'option' );

// now pull out whichever loop‐item you need, e.g.:
$template_id = 0;

// for “Articles Written by Profile Entity”
if ( ! empty( $vp['loop_items']['display_single_profile_article_written_by'] ) ) {
    $template_id = (int) $vp['loop_items']['display_single_profile_article_written_by'];
}



if (!$template_id ) return;


        // Query the latest 5 posts by that contributor
        $args  = [
            'post_type'      => 'post',
            'posts_per_page' => 5,
            'author'         => $contributor_id,
        ];
        $query = new \WP_Query( $args );

        if ( ! $query->have_posts() ) {
            wp_reset_postdata();
            return $no_results;
        }

        // Render each post through your Elementor Loop Item (ID 12345)
        $content  = '<div class="single_profile_article_written_by">';
        while ( $query->have_posts() ) {
            $query->the_post();
            // This will apply the Loop Item template to the current global $post
            $content .= \Elementor\Plugin::instance()
                ->frontend
                ->get_builder_content_for_display( $template_id);
        }
        wp_reset_postdata();

        // Finally, append a “View More” link back to the author archive
        $author_url = get_author_posts_url( $contributor_id );
        $content   .= sprintf(
            '<a style="color:#333;text-decoration:underline;font-size:13px;" target="_blank" href="%1$s">
                View More <i aria-hidden="true" class="fas fa-angle-double-right"></i>
             </a>',
            esc_url( $author_url )
        );

        $content .= '</div>';
        return $content;
    }
} else {
    write_log(
        "⚠️ Warning: " . __NAMESPACE__ . "\\display_single_profile_article_written_by function is already declared",
        true
    );
}







// Ensure function existence before declaring
if ( ! function_exists( __NAMESPACE__ . '\\display_single_profile_press_releases' ) ) {
    /**
     * Displays press releases related to the profile using an Elementor Loop Item template.
     * Leaves original ACF fetching and listing ID lines commented out for reference.
     *
     * @return string HTML or CSS to hide if no releases are found.
     */
    function display_single_profile_press_releases() {
        global $post;
        $profile_id = $post->ID;
        $post_ids   = [];

                // pull the template ID from your ACF “display_single_profile_press_releases” option
//$template_id = get_field( 'display_single_profile_press_releases', 'option' );

// fetch the entire Verified Profile options group once
$vp = get_field( 'verified_profile', 'option' );

// pull the template ID from your ACF “display_single_profile_press_releases” option
$template_id = 0;
if ( ! empty( $vp['loop_items']['display_single_profile_press_releases'] ) ) {
    $template_id = (int) $vp['loop_items']['display_single_profile_press_releases'];
}


if (!$template_id ) return;


        // Commented out JetEngine listing ID fetch
        // $listing_id = 15315;
        // $listing_id = get_field('display_single_profile_press_releases', 'option');

        // If no ACF rows and no Hexa PR username, hide this section
        if ( ! have_rows( 'additional_hexa_pr_wire_releases', $profile_id )
            && get_field( 'hexa_pr_wire_username', $profile_id ) === '' ) {
            return '<style>.single_profile_press_releases{display:none !important;}</style>';
        }

        $content = '<div class="single_profile_press_releases">';
        $hexa_pr_wire_username = get_field( 'hexa_pr_wire_username', $profile_id );

        // Query press releases by author_slug
        $author_slug_query = new \WP_Query( [
            'post_type'      => 'press-release',
            'posts_per_page' => 6,
            'meta_query'     => [
                [
                    'key'     => 'author_slug',
                    'value'   => $hexa_pr_wire_username,
                    'compare' => '=',
                ],
            ],
        ] );
        $post_ids = array_merge( $post_ids, wp_list_pluck( $author_slug_query->posts, 'ID' ) );

        // Include additional PRs from ACF repeater
        if ( have_rows( 'additional_hexa_pr_wire_releases', $profile_id ) ) {
            while ( have_rows( 'additional_hexa_pr_wire_releases', $profile_id ) ) {
                the_row();
                $press_release_id = get_sub_field( 'press_release' );
                if ( $press_release_id && ! in_array( $press_release_id, $post_ids, true ) ) {
                    $post_ids[] = $press_release_id;
                }
            }
        }

        wp_reset_postdata();

        if ( empty( $post_ids ) ) {
            return '<style>.single_profile_press_releases{display:none !important;}</style>';
        }


    // Render each found press-release via Elementor Loop Item template ID 43785
foreach ( $post_ids as $pr_id ) {
    $pr_post = get_post( $pr_id );
    if ( ! $pr_post ) {
        continue;
    }

    $backup_post = $post;          // 1) back up the main global
    $post        = $pr_post;       // 2) overwrite global with this press-release
    setup_postdata( $post );       // 3) let WP internals point at it


// only render if it’s set

    $content .= \Elementor\Plugin::instance()
        ->frontend
        ->get_builder_content_for_display( $template_id, $post->ID );



    $post = $backup_post;          // 4) restore original global

}

wp_reset_postdata();

        wp_reset_postdata();

        $content .= '</div>';
        return $content;
    }
} else {
    write_log(
        "⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_press_releases already declared",
        true
    );
}


// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_single_profile_validate_schema_button')) {
/**
* Displays a button to validate the schema of the current profile page.
*
* @return string HTML of the schema validation button.
*/
function display_single_profile_validate_schema_button() {
return '<a target=_blank href="https://validator.schema.org/#url=' . get_the_permalink() . '">Validate schema of ' . get_the_title() . '<i aria-hidden="true" class="fas fa-external-link-square-alt"></i></a>';
}
} else 
write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_single_profile_validate_schema_button function is already declared", true);


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
if ( ! function_exists( __NAMESPACE__ . '\\display_profile_current_residence' ) ) {
    /**
     * Displays the current residence of the profile.
     *
     * @return string HTML of current residence or CSS to hide element if none exist.
     */
    function display_profile_current_residence() {
        $current_residence = get_field( 'personal_current_residence_current_residence' );
        $current_residence_wikipedia_url = get_field( 'personal_current_residence_current_residence_wikipedia_url' );

        if ( empty( $current_residence) ) {
            return '<style>.display_profile_current_residence{display:none !important;}</style>';
        }

        $output  = '<span class="shortcode_display_profile_current_residence">';
        if ( ! empty( $current_residence_wikipedia_url ) ) {
            $output .= '<a href="' . esc_url( $current_residence_wikipedia_url ) . '" target="_blank">'
                     . esc_html( $current_residence )
                     . '</a>';
        } else {
            $output .= esc_html( $current_location );
        }
        $output .= '</span>';

        return $output;
    }
} else {
    write_log(
        "⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_current_residence function is already declared",
        true
    );
}











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
if ( ! function_exists( __NAMESPACE__ . '\\display_single_profile_articles_featured_in' ) ) {

    /**
     * Displays internal features related to the profile using an Elementor Loop Item template.
     * Leaves the original ACF listing ID fetch commented out.
     *
     * @return string HTML or CSS to hide if no features are found.
     */
    function display_single_profile_articles_featured_in() {
        global $post;
        $main_post = $post;
    
        $post_ids = find_posts_with_profile( $post->ID );
        if ( empty( $post_ids ) ) {
            return '<style>.profile_internal_features{display:none !important;}</style>';
        }
    
        


        $out = '<div class="profile-internal-features">';
        foreach ( $post_ids as $id ) {
            $post = get_post( $id );          // overwrite global
            setup_postdata( $post );          // tell WP about it
    
            $out .= '<!-- rendering ID: ' . esc_html( $post->ID ) . ' -->';
            $out .= \Elementor\Plugin::instance()
                ->frontend
                ->get_builder_content_for_display( 43689, $post->ID );
        }
        wp_reset_postdata();
    
        $post = $main_post;                   // restore global
        return $out . '</div>';
    }
    
} else {
    write_log(
        "⚠️ Warning: " . __NAMESPACE__ . "\\display_single_profile_articles_featured_in already declared",
        true
    );
}







    

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

        // Fetch the listing ID dynamically from the ACF field 'home_profile_archive'
        $listing_id = get_field('home_profile_archive', 'option');

        if (!$listing_id) {
            return 'No listing ID found in the ACF field.';
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
        
        // Settings for JetEngine Listing Grid
        $settings = [
            'listing_id' => $listing_id,
    'lisitng_id'  => $listing_id,
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
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_homepage_profiles function is already declared", true);







if ( ! function_exists( __NAMESPACE__ . '\\display_single_post_mentioned_in_article' ) ) {
    /**
     * Shortcode: [display_single_post_mentioned_in_article must_have_thumbnail="true|false"]
     *
     * Displays profiles mentioned in a post in a 6-column grid (2 cols on mobile),
     * pulling an Elementor loop-item template with its CSS inline.
     *
     * @param array $atts {
     *   @type bool $must_have_thumbnail If true, only include profiles that have a featured image.
     * }
     * @return string HTML or inline style to hide container.
     */
    function display_single_post_mentioned_in_article( $atts = [] ) {
       
        $content = "";

        // 1) Parse shortcode attributes
        $atts = shortcode_atts( [
            'must_have_thumbnail' => false,
        ], $atts, 'display_single_post_mentioned_in_article' );
        $must_thumb = filter_var( $atts['must_have_thumbnail'], FILTER_VALIDATE_BOOLEAN );

        // 2) Only run on single post pages
        if ( ! is_single() ) {
            return 'This shortcode is only for single post pages.';
        }

        global $post;

        // 3) Fetch the Verified Profile options group
        $vp = get_field( 'verified_profile', 'option' );
        if ( ! is_array( $vp ) || empty( $vp['loop_items'] ) ) {
            $content.="it's empty";
            return $content;
            return '';
        }

        // 4) Extract and normalize the Elementor template ID
        $raw = $vp['loop_items']['display_single_post_mentioned_in_article'] ?? null;
        if ( is_object( $raw ) && isset( $raw->ID ) ) {
            $template_id = $raw->ID;
        } elseif ( is_array( $raw ) && isset( $raw['ID'] ) ) {
            $template_id = (int) $raw['ID'];
        } else {
            $template_id = (int) $raw;
        }
        if ( ! $template_id ) {
            return '';
        }


        // 5) Gather profile IDs from the 'profiles' repeater
        $profile_ids = [];

      //  return "1111".get_field("profiles", $post->ID );
        if ( have_rows( 'profiles', $post->ID ) ) {
         //   return "im here!!!rows yes";
            while ( have_rows( 'profiles', $post->ID ) ) {
                the_row();
                $pid = get_sub_field( 'profile' );
                if ( is_object( $pid ) && isset( $pid->ID ) ) {
                    $pid = $pid->ID;
                } else {
                    $pid = (int) $pid;
                }
                if ( ! $pid ) {
                    continue;
                }
                if ( $must_thumb && ! has_post_thumbnail( $pid ) ) {
                    continue;
                }
                $profile_ids[] = $pid;
            }
        }
        if ( empty( $profile_ids ) ) {
            return '<style>.display_single_post_mentioned_in_article{display:none!important}</style>';
        }

        // 6) Render the Elementor template for each profile
        $original_post = $post;
        $frontend      = \Elementor\Plugin::instance()->frontend;
        ob_start();
        ?>
        <style>
        .display_single_post_mentioned_in_article{width:100%;display:block}
        .display_single_post_mentioned_in_article .shortcode {
          width:100%;
          display:grid;
          grid-template-columns:repeat(6,1fr);
          gap:1rem;
        }
        @media (max-width:600px){
          .display_single_post_mentioned_in_article .shortcode {
            grid-template-columns:repeat(2,1fr)!important;
          }
        }
        </style>
        <div class="display_single_post_mentioned_in_article">
          <div class="shortcode">
        <?php
        foreach ( $profile_ids as $pid ) {
            $profile_post = get_post( $pid );
            if ( ! $profile_post ) {
                continue;
            }
            $GLOBALS['post'] = $profile_post;
            setup_postdata( $profile_post );
            echo $frontend->get_builder_content_for_display( $template_id, true );
        }
        ?>
          </div>
        </div>
        <?php
        wp_reset_postdata();
        $GLOBALS['post'] = $original_post;

        return ob_get_clean();
    }
}






/* DELETE 
// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_single_post_mentioned_in_article')) {

    function display_single_post_mentioned_in_article() {
        $no_results = "<style>.display_single_post_mentioned_in_article{display:none !important}</style>";

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

        $content = '<div class="shortcode_display_single_post_mentioned_in_article">';
        $listing_id = 15768;
        $listing_id = get_field('display_single_post_mentioned_in_article', 'option');

        // Settings for JetEngine Listing Grid
        $settings = [
            'listing_id' => $listing_id,
            'lisitng_id'  => $listing_id,
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
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_single_post_mentioned_in_article function is already declared", true);
*/

// Ensure function existence before declaring
if (!function_exists(__NAMESPACE__ . '\\display_theme_footer_text_social_links')) {
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
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_theme_footer_text_social_links function is already declared", true);



function find_posts_with_profile($profile_id) {
    // Add a filter to modify the WHERE clause of the SQL query
    add_filter('posts_where', __NAMESPACE__.'\customize_posts_where');

    // Custom function to modify the WHERE clause
    function customize_posts_where( $where ) {
        $where = str_replace("meta_key = 'profiles_$", "meta_key LIKE 'profiles_%", $where);
        return $where;
    }

    // Set up a custom query with your specific parameters
    $args = array(
        'post_type' => 'post', // or your specific custom post type
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'profiles_$_profile', // Adjust based on your ACF field name
                'value' => $profile_id, // The profile ID you're searching for
                'compare' => 'LIKE'
            )
        )
    );

    // Execute the custom query
    $the_query = new \WP_Query($args);

    // Array to store the IDs of posts that match the criteria
    $matching_post_ids = array();

    // Check if there are posts that match the criteria
    if ($the_query->have_posts()) {
        while ($the_query->have_posts()) {
            $the_query->the_post();
            $matching_post_ids[] = get_the_ID(); // Store the post ID
        }
    }

    // Clean up after the custom query
    wp_reset_postdata();

    // Remove the filter to avoid affecting other queries
    remove_filter('posts_where', __NAMESPACE__.'\customize_posts_where');
 
    // Return the array of matching post IDs
    return $matching_post_ids;
}









/**
 * Shortcode: [contributor_network field="program_name|email|logo|loop_items.<subfield>|pages.<subfield>" size="thumbnail|medium|medium_large|large"]
 *
 * Usage examples:
 *   // top-level fields
 *   [contributor_network field="program_name"]
 *   [contributor_network field="email"]
 *   [contributor_network field="logo" size="large"]
 *
 *   // nested loop_items
 *   [contributor_network field="loop_items.display_single_profile_press_releases"]
 *   [contributor_network field="loop_items.display_single_profile_article_written_by"]
 *
 *   // nested pages (no "page_" prefix)
 *   [contributor_network field="pages.verified_profiles_badges"]
 *   [contributor_network field="pages.verified_profiles_claim"]
 *   [contributor_network field="pages.verified_profiles_apply"]
 *   [contributor_network field="pages.verified_profiles_welcome"]
 */
function contributor_network_shortcode( $atts ) {

    $atts  = shortcode_atts([
        'field' => '',
        'size'  => 'thumbnail',
    ], $atts, 'contributor_network');

    $field = sanitize_text_field( $atts['field'] );
    $size  = sanitize_key(       $atts['size'] );
    if ( ! $field ) {
        return '';
    }

    $group = get_field( 'contributor_network', 'option' );
    if ( ! is_array( $group ) ) {
        return '';
    }

    $parts = explode( '.', $field, 2 );

    // top-level
    if ( count( $parts ) === 1 ) {
        $key = $parts[0];
        if ( empty( $group[ $key ] ) ) {
            return '';
        }
        if ( $key === 'logo' ) {
            $url = wp_get_attachment_image_url( $group['logo'], $size );
            return $url ?: '';
        }
        return esc_html( $group[ $key ] );
    }

    // nested
    list( $parent, $child ) = $parts;

    // new key?
    if ( isset( $group[ $parent ][ $child ] ) && $group[ $parent ][ $child ] !== '' ) {
        $val = $group[ $parent ][ $child ];
    }
    // fallback old page_ prefix for pages
    elseif ( $parent === 'pages' && isset( $group[ $parent ]['page_' . $child] ) && $group[ $parent ]['page_' . $child ] !== '' ) {
        $val = $group[ $parent ]['page_' . $child];
    }
    else {
        return '';
    }

    // pages → return page ID
    if ( $parent === 'pages' ) {
        $post_id = is_object( $val ) && isset( $val->ID ) ? $val->ID : (int) $val;
        return (string) $post_id;
    }

    // loop_items → return ID
    if ( $parent === 'loop_items' ) {
        $post_id = is_object( $val ) && isset( $val->ID ) ? $val->ID : (int) $val;
        return (string) $post_id;
    }

    return esc_html( $val );
}


/**
 * Shortcode: [verified_profile field="program_name|email|logo|loop_items.<subfield>|pages.<subfield>" size="thumbnail|medium|medium_large|large"]
 *
 * Usage examples:
 *   // top-level fields
 *   [verified_profile field="program_name"]
 *   [verified_profile field="email"]
 *   [verified_profile field="logo" size="medium_large"]
 *
 *   // nested loop_items
 *   [verified_profile field="loop_items.display_single_profile_press_releases"]
 *   [verified_profile field="loop_items.display_single_profile_articles_featured_in"]
 *
 *   // nested pages (no "page_" prefix)
 *   [verified_profile field="pages.verified_profiles_badges"]
 *   [verified_profile field="pages.verified_profiles_apply"]
 *
 *   // example returning permalink
 *   [verified_profile field="pages.verified_profiles_apply"]
 */
function verified_profile_shortcode( $atts ) {
   // write_log( 'verified_profile_shortcode raw $atts: ' . var_export( $atts, true ), true );

    $atts  = shortcode_atts([
        'field' => '',
        'size'  => 'thumbnail',
    ], $atts, 'verified_profile');
    //write_log( 'verified_profile_shortcode parsed $atts: ' . var_export( $atts, true ), true );

    $field = sanitize_text_field( $atts['field'] );
    $size  = sanitize_key(       $atts['size'] );
    //write_log( "verified_profile_shortcode sanitized field={$field}, size={$size}", true );
    if ( ! $field ) {
    //   write_log( 'verified_profile_shortcode: no field, returning empty', true );
        return '';
    }

    $group = get_field( 'verified_profile', 'option' );
   // write_log( 'verified_profile group loaded: ' . var_export( $group, true ), true );
    if ( ! is_array( $group ) ) {
      //  write_log( 'verified_profile: group not array, returning empty', true );
        return '';
    }

    $parts = explode( '.', $field, 2 );
   // write_log( 'verified_profile parts: ' . var_export( $parts, true ), true );

    // top-level
    if ( count( $parts ) === 1 ) {
        $key = $parts[0];
      //  write_log( "verified_profile handling top-level key={$key}", true );
        if ( empty( $group[ $key ] ) ) {
         //   write_log( "verified_profile key {$key} empty, returning empty", true );
            return '';
        }
        if ( $key === 'logo' ) {
            $url = wp_get_attachment_image_url( $group['logo'], $size );
           // write_log( "verified_profile logo URL={$url}", true );
            return $url ?: '';
        }
      //  write_log( "verified_profile returning {$group[$key]}", true );
        return esc_html( $group[ $key ] );
    }

    // nested
    list( $parent, $child ) = $parts;
   // write_log( "verified_profile nested parent={$parent}, child={$child}", true );

    // new key?
    if ( isset( $group[ $parent ][ $child ] ) && $group[ $parent ][ $child ] !== '' ) {
        $val = $group[ $parent ][ $child ];
       // write_log( "verified_profile found {$parent}.{$child} => " . var_export( $val, true ), true );
    }
    // fallback old page_ prefix
    elseif ( $parent === 'pages' && isset( $group[ $parent ]['page_' . $child] ) && $group[ $parent ]['page_' . $child ] !== '' ) {
        $val = $group[ $parent ]['page_' . $child];
       // write_log( "verified_profile fallback pages.page_{$child} => " . var_export( $val, true ), true );
    }
    else {
       // write_log( "verified_profile nested {$parent}.{$child} empty, returning empty", true );
        return '';
    }

    // pages → return permalink
    if ( $parent === 'pages' ) {
        $post_id = is_object( $val ) && isset( $val->ID ) ? $val->ID : (int) $val;
       // write_log( "verified_profile returning permalink for page ID={$post_id}", true );
        return get_permalink( $post_id );
    }

    // loop_items → return ID
    if ( $parent === 'loop_items' ) {
        $post_id = is_object( $val ) && isset( $val->ID ) ? $val->ID : (int) $val;
      //  write_log( "verified_profile returning loop_items ID={$post_id}", true );
        return (string) $post_id;
    }

    write_log( "verified_profile returning fallback " . esc_html( $val ), true );
    return esc_html( $val );
}






