<?php namespace smp_verified_profiles;

function enable_snippet_profile_post_wp_admin_functionality()
{
// Hook into the save post action
add_action('save_post', __NAMESPACE__.'\save_acf_post_id');
add_action('wp_head', __NAMESPACE__.'\inject_schema_on_single_profile');
add_action('save_post', __NAMESPACE__.'\generate_schema_markup');
add_action('admin_footer', __NAMESPACE__.'\enforce_featured_image_with_jquery');
add_action('admin_footer', __NAMESPACE__.'\custom_quick_edit_javascript');
add_action('init', __NAMESPACE__.'\remove_content_editor_from_profile_cpt');
add_action('post_submitbox_misc_actions', __NAMESPACE__.'\wpadmin_profile_display_associated_profiles', 10, 1);
add_action('init',  __NAMESPACE__.'\disable_content_editor_for_profile');
}




/**
 * Disable the content editor for 'profile' custom post type
 * Removes the WordPress content editor from the 'profile' post type.
 */
function disable_content_editor_for_profile() {
    $verified_profile_settings = get_verified_profile_settings();
    remove_post_type_support($verified_profile_settings["slug"], 'editor');
}



//dummy function, delete 

function check_plugin_acf()
{return true;}

/**
 * Save ACF post ID into the 'post_id' field when the post is saved.
 * 
 * @param int $post_id The ID of the post being saved.
 */
if (!function_exists(__NAMESPACE__ . '\\save_acf_post_id')) {
    function save_acf_post_id($post_id) {
        if (!check_plugin_acf()) {
            return;
        }
        update_field('post_id', $post_id, $post_id);
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\save_acf_post_id function is already declared", true);
}

/**
 * Inject schema markup into the head section of a 'profile' post type single view.
 */
if (!function_exists(__NAMESPACE__ . '\\inject_schema_on_single_profile')) {
    function inject_schema_on_single_profile() {
        if (!check_plugin_acf()) 
            return;

        $verified_profile_settings = get_verified_profile_settings();
        //|| is_singular($verified_profile_settings["entity"]) 
        if (is_singular($verified_profile_settings["slug"])) { 
            global $post;
            $schema_json = get_field('schema_markup', $post->ID);
            if ($schema_json) {
                echo "<script type='application/ld+json'>" . $schema_json . "</script>";
            }
        }
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\inject_schema_on_single_profile function is already declared", true);
}

/**
 * Generate and save schema markup for a 'profile' or 'organization' based on post categories.
 * 
 * @param int $post_id The ID of the post being saved.
 */

 
 if (!function_exists(__NAMESPACE__ . '\\generate_schema_markup')) {
    function generate_schema_markup($post_id = -1) {
        if (!check_plugin_acf()) return;
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;

        // only run on 'profile' or 'entity' post types
        $post_type = get_post_type($post_id);


 $verified_profile_settings = get_verified_profile_settings();



        if (! in_array($post_type, [$verified_profile_settings["slug"], 'entity'], true)) {
            return;
        }

        $post_categories = get_the_category($post_id);
        $category_slugs   = array_map(function($category) {
            return $category->slug;
        }, $post_categories);

        $schema = [];
      















if ( in_array( "person", $category_slugs, true ) ) {
    // Fetch ACF fields for Person
    $birth_place     = get_field( 'personal_birth_place',   $post_id );
    $home_location   = get_field( 'personal_home_location', $post_id );
    $alumni_of       = get_field( 'personal_alumni_of',     $post_id );
    $bio             = get_field( 'personal_bio',            $post_id );
    $gender          = get_field( 'personal_gender',         $post_id );
    $social_website  = get_field( 'social_website',          $post_id ); // <-- new ACF field

    // Collect and filter social profile URLs
    $social_profiles = array_filter( [
        get_field( 'social_profiles_facebook',     $post_id ),
        get_field( 'social_profiles_twitter',      $post_id ),
        get_field( 'social_profiles_instagram',    $post_id ),
        get_field( 'social_profiles_linkedin',     $post_id ),
        get_field( 'social_profiles_tiktok',       $post_id ),
        get_field( 'social_profiles_wikipedia',    $post_id ),
        get_field( 'social_profiles_imdb',         $post_id ),
        get_field( 'social_profiles_muckrack_url', $post_id ),
        get_field( 'social_profiles_soundcloud',   $post_id ),
        get_field( 'social_profiles_amazon_author',$post_id ),
        get_field( 'social_profiles_audible',      $post_id ),
        get_field( 'social_profiles_github',       $post_id ),
        get_field( 'social_profiles_crunchbase',   $post_id ),
        get_field( 'social_profiles_f6s',          $post_id ),
        get_field( 'social_profiles_the_org',      $post_id ),
        get_field( 'social_profiles_threads',      $post_id ),
        get_field( 'social_profiles_linktree',     $post_id ),
        get_field( 'social_profiles_pinterest',    $post_id ),
        get_field( 'social_profiles_quora',        $post_id ),
        get_field( 'social_profiles_reddit',       $post_id ),
        get_field( 'social_profiles_youtube',      $post_id ),
        get_field( 'social_profiles_angel_list',   $post_id ),
    ], function( $url ) {
        return ! empty( $url );
    } );

    // Prepare sameAs array
    $same_as_urls = array_values( $social_profiles );

    // --- Person object with cleaned name & guarded image ---
    $person = [
        "@type" => "Person",
        "@id"   => get_permalink( $post_id ) . "#person",
    ];

    // If social_website exists and is a valid URL, use it as 'url'; otherwise fallback to permalink
    if ( $social_website && filter_var( $social_website, FILTER_VALIDATE_URL ) ) {
        $person['url'] = esc_url_raw( $social_website );
    } else {
        $person['url'] = get_permalink( $post_id );
    }

    // Clean up the title: decode entities + convert NBSP to normal space
    $title = html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' );
    $title = preg_replace( '/\x{00A0}+/u', ' ', $title );
    $person['name'] = sanitize_text_field( $title );

    // Only include image if it’s a valid URL
    $thumb_url = get_the_post_thumbnail_url( $post_id );
    if ( $thumb_url && filter_var( $thumb_url, FILTER_VALIDATE_URL ) ) {
        $person['image'] = esc_url_raw( $thumb_url );
    }

    if ( $birth_place !== null && $birth_place !== '' ) {
        $person['birthPlace'] = [
            "@type" => "Place",
            "name"  => sanitize_text_field( $birth_place ),
        ];
    }

    if ( $home_location !== null && $home_location !== '' ) {
        $person['homeLocation'] = [
            "@type" => "Place",
            "name"  => sanitize_text_field( $home_location ),
        ];
    }

    if ( $bio !== null && $bio !== '' ) {
        $person['description'] = wp_kses_post( $bio );
    }

    if ( $gender !== null && $gender !== '' ) {
        $person['gender'] = sanitize_text_field( $gender );
    }

    if ( $alumni_of !== null && $alumni_of !== '' ) {
        $person['alumniOf'] = [
            "@type" => "Organization",
            "name"  => is_array( $alumni_of ) ? sanitize_text_field( $alumni_of['name'] ) : sanitize_text_field( $alumni_of ),
        ];
    }

    if ( ! empty( $same_as_urls ) ) {
        $person['sameAs'] = array_map( 'esc_url_raw', $same_as_urls );
    }

    // Build the ProfilePage wrapper
    $schema = [
        "@context"     => "https://schema.org",
        "@type"        => "ProfilePage",
        "dateCreated"  => get_post_time( 'c', true, $post_id ),
        "dateModified" => get_post_modified_time( 'c', true, $post_id ),
        "mainEntity"   => $person,
    ];
}

















       


} elseif ( in_array( 'organization', $category_slugs, true ) ) {
    // Fetch ACF fields (adjust keys below if your ACF field names differ)
    $org_name            = get_field( 'organization_name', $post_id );
    $social_website      = get_field( 'social_website',    $post_id );  // <-- Website URL
    $legal_name          = get_field( 'legal_name',         $post_id );
    $naics               = get_field( 'naics',              $post_id );
    $email               = get_field( 'email',              $post_id );
    $acf_description     = get_field( 'description',        $post_id );
    $alternate_name      = get_field( 'alternate_name',     $post_id );
    $logo                = get_field( 'logo',               $post_id );
    $award               = get_field( 'award',              $post_id );
    $brand               = get_field( 'brand',              $post_id );
    $founder_id          = get_field( 'founder_id',         $post_id );
    $founder_url         = get_field( 'founder_url',        $post_id );
    $founder_name        = get_field( 'founder_name',       $post_id );
    $founding_date       = get_field( 'founding_date',      $post_id );
    $number_of_employees = get_field( 'number_of_employees',$post_id );
    $seeks               = get_field( 'seeks',              $post_id );

    // Collect and filter social profile URLs for sameAs
    $social_profiles = array_filter([
        get_field( 'social_profiles_facebook',     $post_id ),
        get_field( 'social_profiles_twitter',      $post_id ),
        get_field( 'social_profiles_instagram',    $post_id ),
        get_field( 'social_profiles_linkedin',     $post_id ),
        get_field( 'social_profiles_tiktok',       $post_id ),
        get_field( 'social_profiles_wikipedia',    $post_id ),
        get_field( 'social_profiles_imdb',         $post_id ),
        get_field( 'social_profiles_muckrack_url', $post_id ),
        get_field( 'social_profiles_soundcloud',   $post_id ),
        get_field( 'social_profiles_amazon_author',$post_id ),
        get_field( 'social_profiles_audible',      $post_id ),
        get_field( 'social_profiles_github',       $post_id ),
        get_field( 'social_profiles_crunchbase',   $post_id ),
        get_field( 'social_profiles_f6s',          $post_id ),
        get_field( 'social_profiles_the_org',      $post_id ),
        get_field( 'social_profiles_threads',      $post_id ),
        get_field( 'social_profiles_linktree',     $post_id ),
        get_field( 'social_profiles_pinterest',    $post_id ),
        get_field( 'social_profiles_quora',        $post_id ),
        get_field( 'social_profiles_reddit',       $post_id ),
        get_field( 'social_profiles_youtube',      $post_id ),
        get_field( 'social_profiles_angel_list',   $post_id ),
    ], function ( $url ) {
        return ! empty( $url );
    } );

    // ContactPoint fields
    $contact_type        = get_field( 'contact_type',     $post_id );
    $contact_email       = get_field( 'contact_email',    $post_id );
    $contact_tel         = get_field( 'contact_telephone',$post_id );

    // Address fields
    $street_address      = get_field( 'street_address',   $post_id );
    $address_locality    = get_field( 'address_locality', $post_id );
    $address_region      = get_field( 'address_region',   $post_id );
    $postal_code         = get_field( 'postal_code',      $post_id );
    $address_country     = get_field( 'address_country',   $post_id );

    // Featured image (if exists)
    $thumb_url = get_the_post_thumbnail_url( $post_id );
    if ( $thumb_url && filter_var( $thumb_url, FILTER_VALIDATE_URL ) ) {
        $feature_image = esc_url_raw( $thumb_url );
    } else {
        $feature_image = null;
    }

    // Fallback for org_name: use post title if ACF is empty
    if ( empty( $org_name ) ) {
        $org_name = get_the_title( $post_id );
    }
    // Fallback for description: use excerpt or content if ACF description is empty
    if ( empty( $acf_description ) ) {
        $acf_description = get_the_excerpt( $post_id );
        if ( empty( $acf_description ) ) {
            $acf_description = wp_strip_all_tags( get_the_content( null, false, $post_id ) );
        }
    }

    // Build Organization schema object
    $org = [
        "@context" => "https://schema.org",
        "@type"    => "Organization",
        "name"     => sanitize_text_field( $org_name ),
    ];

    // **Include website URL** if provided
    if ( $social_website && filter_var( $social_website, FILTER_VALIDATE_URL ) ) {
        $org['url'] = esc_url_raw( $social_website );
    }

    if ( $legal_name )          { $org['legalName']         = sanitize_text_field( $legal_name ); }
    if ( $naics )               { $org['naics']             = sanitize_text_field( $naics ); }
    if ( $email )               { $org['email']             = sanitize_email( $email ); }
    if ( $acf_description )     { $org['description']       = wp_kses_post( $acf_description ); }
    if ( $alternate_name )      { $org['alternateName']     = sanitize_text_field( $alternate_name ); }
    if ( $logo && filter_var( $logo, FILTER_VALIDATE_URL ) ) {
        $org['logo']           = esc_url_raw( $logo );
    }
    if ( $award )               { $org['award']             = sanitize_text_field( $award ); }
    if ( $brand )               { $org['brand']             = sanitize_text_field( $brand ); }
    if ( $founding_date )       { $org['foundingDate']      = sanitize_text_field( $founding_date ); }
    if ( $number_of_employees ) { $org['numberOfEmployees'] = intval( $number_of_employees ); }
    if ( $seeks )               {
        $org['seeks']          = is_array( $seeks )
                                    ? array_map( 'sanitize_text_field', $seeks )
                                    : sanitize_text_field( $seeks );
    }
    if ( $feature_image )       { $org['image']             = $feature_image; }

    // sameAs (social profile URLs)
    if ( ! empty( $social_profiles ) ) {
        $same_as_urls = array_map( 'esc_url_raw', $social_profiles );
        $org['sameAs'] = array_values( $same_as_urls );
    }

    // ContactPoint
    if ( $contact_type || $contact_email || $contact_tel ) {
        $cp = [ "@type" => "ContactPoint" ];
        if ( $contact_type )   { $cp['contactType'] = sanitize_text_field( $contact_type ); }
        if ( $contact_email )  { $cp['email']       = sanitize_email( $contact_email ); }
        if ( $contact_tel )    { $cp['telephone']   = sanitize_text_field( $contact_tel ); }
        $org['contactPoint'] = $cp;
    }

    // Founder
    if ( $founder_id || $founder_url || $founder_name ) {
        $f = [ "@type" => "Person" ];
        if ( $founder_id )    { $f['@id']  = esc_url_raw( $founder_id ); }
        if ( $founder_url )   { $f['url']  = esc_url_raw( $founder_url ); }
        if ( $founder_name )  { $f['name'] = sanitize_text_field( $founder_name ); }
        $org['founder'] = $f;
    }

    // PostalAddress
    if ( $street_address || $address_locality || $address_region || $postal_code || $address_country ) {
        $addr = [ "@type" => "PostalAddress" ];
        if ( $street_address )   { $addr['streetAddress']   = sanitize_text_field( $street_address ); }
        if ( $address_locality ) { $addr['addressLocality']  = sanitize_text_field( $address_locality ); }
        if ( $address_region )   { $addr['addressRegion']    = sanitize_text_field( $address_region ); }
        if ( $postal_code )      { $addr['postalCode']       = sanitize_text_field( $postal_code ); }
        if ( $address_country )  { $addr['addressCountry']   = sanitize_text_field( $address_country ); }
        $org['address'] = $addr;
    }

    $schema = $org;
}

















        elseif (get_post_type($post_id) === 'entity') {
            $schema = [
                "@context"         => "https://schema.org",
                "@type"            => "DefinedTerm",
                "@id"              => get_the_permalink($post_id),
                "url"              => get_the_permalink($post_id),
                "name"             => get_the_title($post_id),
                "description"      => get_field('term_description', $post_id),   // adjust your ACF key
                "termCode"         => get_field('term_code', $post_id),          // adjust your ACF key
                "inDefinedTermSet" => get_field('term_set', $post_id),           // adjust your ACF key
            ];
        }


        // Remove any null or empty values from the schema
        $schema = array_filter($schema, function($value) {
            return ($value !== null && $value !== []);
        });
        $schema_json = wp_json_encode(
            $schema,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        update_field( 'schema_markup', $schema_json, $post_id );                   
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\generate_schema_markup function is already declared", true);
}

/**
 * Enforces setting a featured image before publishing a profile post.
 */
if (!function_exists(__NAMESPACE__ . '\\enforce_featured_image_with_jquery')) {
    function enforce_featured_image_with_jquery() {
        global $typenow;
        if (false && $typenow == 'profile') {  // Replace 'profile' with your actual custom post type
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#publish').click(function(e) {
                        if ($('#set-post-thumbnail img').length == 0) {
                            e.preventDefault();
                           alert('You must set a featured image (company logo or headshot) before publishing this profile.');
                            return false;
                        }
                    });
                });
            </script><?php
        }
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\enforce_featured_image_with_jquery function is already declared", true);
}

/**
 * Custom quick edit functionality for removing fields from the quick edit screen for the 'profile' post type.
 */
if (!function_exists(__NAMESPACE__ . '\\custom_quick_edit_javascript')) {
    function custom_quick_edit_javascript() {
        global $current_screen;

        // Check if on the edit screen of the 'profile' post type and if the user has the 'verified_profile_manager' role
        if ($current_screen->id == 'edit-profile' && is_profile_manager(true)) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Remove the author field from quick edit
                    $('#the-list').on('click', '.editinline', function() {
                        setTimeout(function() {
                            var editRow = $('#edit-' + $('.inline-edit-row').attr('id').substring(5));
                            if (editRow.find('.inline-edit-author').length > 0) {
                                editRow.find('.inline-edit-author').remove();
                            }
                        }, 50);
                    });

                    // Remove the category field from quick edit
                    $('#the-list').on('click', '.editinline', function() {
                        setTimeout(function() {
                            var editRow = $('#edit-' + $('.inline-edit-row').attr('id').substring(5));
                            if (editRow.find('.inline-edit-categories').length > 0) {
                                editRow.find('.inline-edit-categories').remove();
                            }

                            // Hide the slug edit section
                            $('.inline-edit-row').find('label:has(.title:contains("Slug"))').remove();
                            // Hide the tags edit section
                            $('.inline-edit-row').find('.inline-edit-tags').remove();
                        }, 50);
                    });

                    // Remove the slug edit section
                    $('#edit-slug-box').remove();
                });
            </script><?php
        }
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\custom_quick_edit_javascript function is already declared", true);
}

/**
 * Removes content editor from the 'profile' custom post type.
 */
if (!function_exists(__NAMESPACE__ . '\\remove_content_editor_from_profile_cpt')) {
    function remove_content_editor_from_profile_cpt() {
        // Remove 'editor' support from 'profile' CPT
        remove_post_type_support('profile', 'editor');
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\remove_content_editor_from_profile_cpt function is already declared", true);
}

/**
 * Displays associated profiles in the Publish meta box when editing a 'profile' post.
 *
 * @param WP_Post $post The current post object.
 */
if (!function_exists(__NAMESPACE__ . '\\wpadmin_profile_display_associated_profiles')) {
    function wpadmin_profile_display_associated_profiles($post) {
        if (!current_user_can('administrator')) return;
        global $wpdb;

        // Ensure this function runs only for the custom post type 'profile'
        if ('profile' !== $post->post_type) {
            return;
        }

        $profile_id = $post->ID;
        $meta_key_like = 'unclaimed_profiles_%_profile';

        // Custom SQL query
        $sql = "
            SELECT DISTINCT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE %s 
            AND meta_value = %d
        ";

        // Prepare and execute the query
        $prepared_sql = $wpdb->prepare($sql, $meta_key_like, $profile_id);
        $user_ids = $wpdb->get_col($prepared_sql);

        // Start building the HTML output
        $html = '<div id="major-publishing-actions" style="overflow:hidden;">';
        $html .= '<div id="publishing-action" style="text-align:left !important;float:left !important;">';
        $html .= '<div><b>Associated with:</b><br />';

        // Check if users found
        if (empty($user_ids)) {
            $html .= "No association";
        } else {
            foreach ($user_ids as $user_id) {
                $user = get_userdata($user_id);
                $html .= esc_html($user->display_name) . ' | <a target=_blank href="' . esc_url(get_edit_user_link($user_id)) . '">edit</a><br />';
            }
        }

        $html .= '</div>';
        $html .= '<div><br /><b>Claimed By:</b><br />';

        // Get the author data
        $author_id = $post->post_author;
        $author = get_userdata($author_id);

        // Check if author exists
        if ($author) {
            $author_name = esc_html($author->display_name);
            $edit_link = esc_url(get_edit_user_link($author_id));
            $html .= "{$author_name} | <a href='{$edit_link}' target='_blank'>edit</a>";
        } else {
            $html .= "No author";
        }

        $html .= '</div>';
        $html .= '</div></div>';

        // Output the HTML
        echo $html;
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\wpadmin_profile_display_associated_profiles function is already declared", true);
}