<?php namespace smp_verified_profiles;

// Move all shortcode initializations to the top
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

add_shortcode('display_profile_validate_schema_button', __NAMESPACE__ . '\\display_profile_validate_schema_button');
add_shortcode('display_profile_press_releases', __NAMESPACE__ . '\\display_profile_press_releases');
add_shortcode('display_profile_contributing_articles', __NAMESPACE__ . '\\display_profile_contributing_articles');
add_shortcode('display_profile_current_residence', __NAMESPACE__ . '\\display_profile_current_residence');
 add_shortcode('display_profile_location_born', __NAMESPACE__ . '\\display_profile_location_born');


 
// Functions
if (!function_exists(__NAMESPACE__ . '\\display_users_featured_posts')) {
    function display_users_featured_posts() {
        if (!class_exists('Jet_Engine_Render_Listing_Grid')) {
            return 'JetEngine is not active or the required class is not available.';
        }

        global $post;
        $post_ids = [];
        $listing_id = 15006;
        $profile_id = 14481;

        add_filter('posts_where', __NAMESPACE__ . '\\modify_posts_where_for_acf_repeater');
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'profiles_$_profile',
                    'value' => $profile_id,
                    'compare' => 'LIKE'
                )
            )
        );
        $query = new \WP_Query($args);
        $post_ids = wp_list_pluck($query->posts, 'ID');
        remove_filter('posts_where', __NAMESPACE__ . '\\modify_posts_where_for_acf_repeater');

        $settings = array(
            'listing_id' => $listing_id,
            'columns' => 1,
            'columns_tablet' => 1,
            'columns_mobile' => 1,
            'post_status' => array('publish'),
            'posts_num' => 5,
            'posts_query' => array(
                array(
                    'type' => 'posts_params',
                    'posts_in' => $post_ids,
                    'post_type' => 'profile'
                ),
            ),
            'custom_query' => false,
            'custom_query_id' => null
        );

        $listing_grid = new \Jet_Engine_Render_Listing_Grid($settings);
        ob_start();
        $listing_grid->render();
        $content = ob_get_clean();
        wp_reset_postdata();
        return $content;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_users_featured_posts function is already declared", true);

if (!function_exists(__NAMESPACE__ . '\\modify_posts_where_for_acf_repeater')) {
    function modify_posts_where_for_acf_repeater($where) {
        $where = str_replace("meta_key = 'profiles_$", "meta_key LIKE 'profiles_%", $where);
        return $where;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\modify_posts_where_for_acf_repeater function is already declared", true);

if (!function_exists(__NAMESPACE__ . '\\display_profile_council_banner')) {
    function display_profile_council_banner() {
        $user_id = get_field('contributor_profile');
        if ($user_id) {
            $is_council_member = get_field('council_member', 'user_' . $user_id);
            if ($is_council_member === true) {
                return '<style>
                .display_profile_council_banner span{margin-top:15px;display:inline-block;font-weight:700}
                .display_profile_council_banner i:before{
                    content: "\f058";
                    font-family: "Font Awesome 5 Free" !important;
                    font-weight: 900;
                    color: red;
                    font-size: 19px;
                    margin-right: 10px;
                    display: block;}
                </style>
                <span class="display_profile_council_banner"><i class="fas"></i><span>Her Forward Leadership Council Member</span></span>';
            }
        }
        return '';
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_council_banner function is already declared", true);

if (!function_exists(__NAMESPACE__ . '\\display_profile_quick_online_profiles')) {
    function display_profile_quick_online_profiles() {
        $output = '<span class="shortcode_display_profile_quick_online_profiles">';
        $profile_added = false;

        $crunchbase = get_field('social_profiles_crunchbase');
        if (!empty($crunchbase)) {
            $output .= "<a target=_blank href='" . esc_url($crunchbase) . "'>Crunchbase</a> / ";
            $profile_added = true;
        }

        $f6s = get_field('social_profiles_f6s');
        if (!empty($f6s)) {
            $output .= "<a target=_blank href='" . esc_url($f6s) . "'>F6S</a> / ";
            $profile_added = true;
        }

        $the_org = get_field('social_profiles_the_org');
        if (!empty($the_org)) {
            $output .= "<a target=_blank href='" . esc_url($the_org) . "'>The Org</a> / ";
            $profile_added = true;
        }

        $imdb = get_field('social_profiles_imdb');
        if (!empty($imdb)) {
            $output .= "<a target=_blank href='" . esc_url($imdb) . "'>IMDb</a> / ";
            $profile_added = true;
        }

        $angel_list = get_field('social_profiles_angel_list');
        if (!empty($angel_list)) {
            $output .= "<a target=_blank href='" . esc_url($angel_list) . "'>AngelList</a> / ";
            $profile_added = true;
        }

        $muckrack = get_field('social_profiles_muckrack_url');
        if (!empty($muckrack)) {
            $output .= "<a target=_blank href='" . esc_url($muckrack) . "'>Muck Rack</a> / ";
            $profile_added = true;
        }

        $output = rtrim($output, ' / ');

        if (!$profile_added) {
            return '<style>.profile_quick_online_profiles{display:none !important}</style>';
        }

        $output .= '</span>';
        return $output;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_quick_online_profiles function is already declared", true);

// Continue with the rest of the functions in a similar way...
if (!function_exists(__NAMESPACE__ . '\\display_profile_quick_contact')) {
    function display_profile_quick_contact() {
        $output = '<span class="shortcode_display_profile_quick_contact">';
        $contact_added = false;

        if (get_field('contact_information_email_preferred')) {
            $email = get_field('contact_information_email_email');
            if (!empty($email)) {
                $output .= "<a href='mailto:" . esc_html($email) . "'>" . esc_html($email) . '</a> / ';
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
    
            // Check if any contact method was added, if not return CSS to hide
            if (!$contact_added) {
                return '<style> .profile_quick_contact{display:none !important}</style>';
            }
    
            // Append the closing span tag to the output
            $output .= '</span>';
    
            // Return the final output
            return $output;
        }
    } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_quick_contact function is already declared", true);
    
    if (!function_exists(__NAMESPACE__ . '\\display_profile_education')) {
        function display_profile_education() {
            // Flag to check if any 'school' field has content
            $has_school_content = false;
    
            // Check if the repeater field has rows of data
            if (have_rows('personal_education')) {
                // Loop through each row of data in the repeater to check for 'school' content
                while (have_rows('personal_education')) {
                    the_row();
                    $school = get_sub_field('school');
                    if (!empty($school)) {
                        $has_school_content = true;
                        break;
                    }
                }
    
                // If no 'school' field has content, return the CSS style to hide the element
                if (!$has_school_content) {
                    return '<style> .profile_education{display:none !important}</style>';
                }
    
                // Reset the loop for output generation
                reset_rows();
    
                // Start building the output string with an opening span tag
                $output = '<span class="shortcode_display_profile_education">';
    
                // Loop through each row of data in the repeater for output
                while (have_rows('personal_education')) {
                    the_row();
    
                    // Retrieve the 'school', 'degree', and 'wikipedia_url' values from the current row
                    $school = get_sub_field('school');
                    if (empty($school)) continue;
                    $degree = get_sub_field('degree');
                    $wikipedia_url = get_sub_field('wikipedia_url');
    
                    // Concatenate school and degree
                    $education_text = esc_html($school) . ' - ' . esc_html($degree);
    
                    // Check if the Wikipedia URL field is not empty
                    if (!empty($wikipedia_url)) {
                        // If the URL is provided, format the output as a hyperlink
                        $output .= '<a href="' . esc_url($wikipedia_url) . '" target="_blank">' . $education_text . '</a><br />';
                    } else {
                        // If no URL is provided, output the education text as plain text
                        $output .= $education_text . '<br />';
                    }
                }
            } else {
                // If no data is found, return the CSS style to hide the element
                return '<style> .profile_education{display:none !important}</style>';
            }
    
            // Append the closing span tag to the output
            $output .= '</span>';
    
            // Return the final output
            return $output;
        }
    } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_education function is already declared", true);
    
    if (!function_exists(__NAMESPACE__ . '\\display_profile_organizations_founded')) {
        function display_profile_organizations_founded() {
            $output = '<div class="shortcode_display_profile_organizations">';
    
            if (have_rows('organizations_founded')) {
                $total_rows = count(get_field('organizations_founded'));
                $current_row = 0;
    
                while (have_rows('organizations_founded')) {
                    the_row();
                    $current_row++;
    
                    $organization_id = get_sub_field('organization');
                    if (!empty($organization_id)) {
                        $organization_name = get_the_title($organization_id);
                        $organization_link = get_field('url', $organization_id);
                        $organization_founded = get_field('founded', $organization_id);
                        $organization_headquarters_location = get_field('headquarters_location', $organization_id);
                        $organization_headquarters_wikipedia_url = get_field('headquarters_wikipedia_url', $organization_id);
                        $organization_logo = get_the_post_thumbnail_url($organization_id, 'thumbnail');
    
                        $output .= '<div class="organization-entry">';
                        $output .= '<div class="left-column" style="float:left">';
                        if ($organization_logo) {
                            $output .= '<img class="featured_image" src="' . esc_url($organization_logo) . '" alt="' . esc_attr($organization_name) . '">';
                        }
                        $output .= '</div>';
    
                        $output .= '<div class="right-column" style="float:left">';
                        $output .= '<div style="line-height:initial"><a class="name" href="' . esc_url($organization_link) . '" target="_blank">' . esc_html($organization_name) . '</a></div>';
                        $output .= '<div style="line-height:initial">';
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
                        $output .= '</div>';
                        $output .= '</div>';
                        $output .= '</div>';
    
                        if ($current_row < $total_rows) {
                            $output .= '<hr />';
                        }
                    }
                }
            } else {
                return '<style>.profile_organizations_founded{display:none !important}</style>';
            }
    
            $output .= '</div>';
            return $output;
        }
    } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_organizations_founded function is already declared", true);
    
    if (!function_exists(__NAMESPACE__ . '\\display_profile_contributing_articles')) {
        function display_profile_contributing_articles() {
            $no_results = "<style>.profile_contributing_articles{display:none !important;}</style>";
    
            global $post;
            $contributor_id = get_field('contributor_profile', $post->ID);
    
            if (empty($contributor_id) || count_user_posts($contributor_id) == 0) {
                return $no_results;
            }
    
            $listing_id = 15006; 
            $content = '<div class="display_contributor_articles">';
    
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => 5,
                'author' => $contributor_id 
            );
    
            $query = new WP_Query($args);
    
            if ($query->have_posts()) {
                $post_ids = wp_list_pluck($query->posts, 'ID'); 
    
                $settings = array(
                    'listing_id' => $listing_id,
                    'columns' => 1,
                    'columns_tablet' => 1,
                    'columns_mobile' => 1,
                    'post_status' => array('publish'),
                    'posts_num' => count($post_ids),
                    'posts_query' => array(
                        array(
                            'type' => 'posts_params',
                            'posts_in' => $post_ids,
                            'post_type' => 'post'
                        )
                    ),
                    'custom_query' => false,
                    'custom_query_id' => null
                );
    
                $listing_grid = new Jet_Engine_Render_Listing_Grid($settings);
                ob_start();
                $listing_grid->render();
                $content .= ob_get_clean();
            } else {
                return $no_results;
            }
    
            wp_reset_postdata();
    
            $contributor_profile = get_field("contributor_profile");
    
            if (!empty($contributor_profile) && is_numeric($contributor_profile)) {
                $author_url = get_author_posts_url($contributor_profile);
                $content .= '<a style="color:#333;text-decoration:underline;font-size:13px;" target=_blank href="' . esc_url($author_url) . '">View More <i aria-hidden="true" class="fas fa-angle-double-right"></i></a>';
            }
    
            $content .= '</div>';
            return $content;
        }
    } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_contributing_articles function is already declared", true);
    
    if (!function_exists(__NAMESPACE__ . '\\display_profile_current_residence')) {
        function display_profile_current_residence() {
            $current_residence = get_field('personal_current_residence_current_residence');
            $current_residence_wikipedia_url = get_field('personal_current_residence_current_residence_wikipedia_url');
    
            if (empty($current_residence)) {
                return '<style> .profile_current_residence{display:none !important}</style>';
            }
    
            $output = '<span class="shortcode_display_profile_current_residence">';
            if (!empty($current_residence_wikipedia_url)) {
                $output .= '<a href="' . esc_url($current_residence_wikipedia_url) . '" target="_blank">' . esc_html($current_residence) . '</a>';
            } else {
                $output .= esc_html($current_residence);
            }
            $output .= '</span>';
    
            return $output;
        }
    } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_current_residence function is already declared", true);
    
    if (!function_exists(__NAMESPACE__ . '\\display_profile_press_releases')) {
        function display_profile_press_releases() {
            global $post;
            $post_ids = array();
            $listing_id = 15315;
            $profile_id = $post->ID;
    
            if (have_rows('additional_hexa_pr_wire_releases', $profile_id) == false && get_field('hexa_pr_wire_username', $profile_id) == "") {
                return '<style>.profile_official_announcements{display:none !important}</style>';
            }
    
            $content = "<div class='display_profile_press_releases'>";
            $hexa_pr_wire_username = get_field('hexa_pr_wire_username', $profile_id);
    
            $author_slug_query = new WP_Query(array(
                'post_type' => 'press-release',
                'posts_per_page' => 6,
                'meta_query' => array(
                    array(
                        'key' => 'author_slug',
                        'value' => $hexa_pr_wire_username,
                        'compare' => '='
                    )
                )
            ));
    
            $post_ids = array_merge($post_ids, wp_list_pluck($author_slug_query->posts, 'ID'));
    
            if (have_rows('additional_hexa_pr_wire_releases', $profile_id)) {
                while (have_rows('additional_hexa_pr_wire_releases', $profile_id)) {
                    the_row();
                    $press_release_id = get_sub_field('press_release');
                    if (!in_array($press_release_id, $post_ids)) {
                        $post_ids[] = $press_release_id;
                    }
                }
            }
    
            $settings = array(
                'listing_id'  => $listing_id, 
                'columns' => 2,
                'columns_tablet' => 1,
                'columns_mobile' => 1,
                'post_status' => array('publish'),
                'posts_num' => 10,
                'posts_query' => array(
                    array(
                        'type' => 'posts_params',
                        'posts_in' => $post_ids,
                        'post_type' => 'press-release'
                    ),
                ),
                'custom_query' => false,
                'custom_query_id' => null
            );
    
            $listing_grid = new Jet_Engine_Render_Listing_Grid($settings);
            ob_start();
            $listing_grid->render();
            $content .= ob_get_clean();
            $content .= "</div>";
    
            wp_reset_postdata();
            return $content;
        }
    } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_press_releases function is already declared", true);
    
    if (!function_exists(__NAMESPACE__ . '\\display_profile_validate_schema_button')) {
        function display_profile_validate_schema_button() {
            return '<a target=_blank href="https://validator.schema.org/#url=' . get_the_permalink() . '">Validate schema of ' . get_the_title() . '<i aria-hidden="true" class="fas fa-external-link-square-alt"></i></a>';
        }
    } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_validate_schema_button function is already declared", true);
    
    if (!function_exists(__NAMESPACE__ . '\\display_profile_location_born')) {
        function display_profile_location_born() {
            $location_born = get_field('personal_location_born_location_born');
            if (empty($location_born)) {
                return '<style> .display_profile_location_born{display:none !important}</style>';
            }
            $location_born_wikipedia_url = get_field('personal_location_born_location_born_wikipedia_url');
            $output = '<span class="shortcode_display_profile_location_born">';
            if (!empty($location_born_wikipedia_url)) {
                $output .= '<a href="' . esc_url($location_born_wikipedia_url) . '" target="_blank">' . esc_html($location_born) . '</a>';
            } else {
                $output .= esc_html($location_born);
            }
            $output .= '</span>';
            return $output;
        }
    } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_location_born function is already declared", true);
    
