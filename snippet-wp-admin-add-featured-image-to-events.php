<?php namespace smp_verified_profiles;

function add_wp_admin_add_featured_image_to_events(){
// Add our custom column only for the 'profile' post type.
add_filter( 'manage_edit-profile_columns', __NAMESPACE__ . '\\add_profile_featured_image_column' );
add_action( 'manage_profile_posts_custom_column', __NAMESPACE__ . '\\display_profile_featured_image_column', 10, 2 );
add_filter( 'pre_get_posts', __NAMESPACE__ . '\\jpn_structure_filter_profiles_by_featured' );

// Add "Filter by Featured Profiles" link to the profile listing page.
add_filter( 'views_edit-profile', __NAMESPACE__ . '\\add_featured_profile_filter_link' );

} 


// Append the Featured Image column to the Profile post type list view
function add_profile_featured_image_column( $columns ) {
    $columns['featured_image'] = __( 'Featured Image', 'your-textdomain' );
    return $columns;
}

// Display the featured image in the new column
function display_profile_featured_image_column( $column, $post_id ) {
    if ( 'featured_image' === $column ) {
        if ( has_post_thumbnail( $post_id ) ) {
            // Output a small 50x50 pixel thumbnail
            echo get_the_post_thumbnail( $post_id, array( 50, 50 ) );
        } else {
            echo 'none';
        }
    }
}





// Filter the admin query for the 'profile' post type based on a URL parameter
function jpn_structure_filter_profiles_by_featured( $query ) {

    // 1) Only run in the admin area and skip AJAX calls
    if ( ! is_admin() || wp_doing_ajax() ) {
        return;
    }
    // 2) Don’t run on ACF’s internal field lookups (they set acf_field_name)
    if ( $query->get('acf_field_name') ) {
        return;
    }

    // 3) Only target the main WP_Query (so we don’t accidentally fire on secondary queries)
    if ( ! $query->is_main_query() ) {
        return;
    }

    // 4) Only run on the profile edit‐list screen (edit.php?post_type=profile)
    global $pagenow;
    if ( $pagenow !== 'edit.php' ) {
        return;
    }
   $verified_profile_settings =  get_verified_profile_settings();
    // 5) Ensure it’s specifically the “profile” CPT being listed
    if ( $query->get('post_type') !== $verified_profile_settings["slug"] ) {
        return;
    }


        // Check for the custom URL parameter (e.g. featured_filter=1)
        if ( isset( $_GET['featured_filter'] ) && '1' === $_GET['featured_filter'] ) {
            // Build the meta query to filter where the ACF field 'featured' equals 1
            $meta_query = array(
                array(
                    'key'     => 'featured',
                    'value'   => '1',
                    'compare' => '='
                )
            );
            
            // Set the meta query for the current admin query
            $query->set( 'meta_query', $meta_query );
        }
    }





function add_featured_profile_filter_link( $views ) {
    // Check if the filter is active.
    $current = ( isset( $_GET['featured_filter'] ) && '1' === $_GET['featured_filter'] ) ? ' class="current"' : '';
    // Create the URL for filtering by featured profiles.
    $url = admin_url( 'edit.php?post_type=profile&featured_filter=1' );
    // Add the new view link.
    $views['featured'] = '<a href="' . esc_url( $url ) . '"' . $current . '>Filter by Featured Profiles</a>';
    return $views;
}

?>