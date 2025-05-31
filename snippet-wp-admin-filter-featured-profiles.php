<?php namespace smp_verified_profiles;

// 1) Add the button to the admin “edit.php?post_type=wiki” screen
add_action('restrict_manage_posts', function($post_type, $which) {

    $verified_profile_settings = get_verified_profile_settings();

    if ($post_type !== $verified_profile_settings["slug"] || $which !== 'top') return;

    // detect current filter state
    $is_filtered = isset($_GET['featured_filter']) && $_GET['featured_filter'] === '1';

    // URL for toggling
    $base_url = remove_query_arg('featured_filter');
    $url      = $is_filtered
        ? $base_url
        : add_query_arg('featured_filter', '1', $base_url);

    // render as WP button
    printf(
        '<a href="%1$s" class="button%2$s" style="margin-left:8px;">%3$s</a>',
        esc_url($url),
        $is_filtered ? '' : ' button-primary',
        $is_filtered ? 'Show All Profiles' : 'Filter by Featured'
    );
}, 10, 2);

// 2) Modify the query when that button/link is active
add_action( 'pre_get_posts', function( \WP_Query $query ) {
    // 1) Only run in wp-admin (skip front-end & AJAX).
    if ( ! is_admin() || wp_doing_ajax() ) {
        return;
    }

    // 2) Only target the main query.
    if ( ! $query->is_main_query() ) {
        return;
    }

    // 3) Force the pagenow check: must be edit.php
    global $pagenow;
    if ( $pagenow !== 'edit.php' ) {
        return;
    }

    // 4) Only target your CPT slug.
    //    If get_verified_profile_settings() calls get_field() unguarded, 
    //    wrap it in a try/catch or guard inside get_verified_profile_settings().
    $settings = get_verified_profile_settings();
    if ( empty( $settings['slug'] ) || $query->get('post_type') !== $settings['slug'] ) {
        return;
    }

    // 5) Skip ACF’s own internal queries.
    if ( isset( $query->query_vars['acf_field_name'] ) ) {
        return;
    }

    // Now that all checks are passed, only filter by ‘featured’ if the GET param is set:
    if ( isset( $_GET['featured_filter'] ) && $_GET['featured_filter'] === '1' ) {
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'key'     => 'featured',
            'value'   => '1',
            'compare' => '=',
        ];
        $query->set( 'meta_query', $meta_query );
    }
}, 10, 1 );
