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
add_action('pre_get_posts', function($query) {
    global $pagenow;
    $verified_profile_settings = get_verified_profile_settings();

    if (
        is_admin()
        && $pagenow === 'edit.php'
        && $query->get('post_type') === $verified_profile_settings["slug"]
        && isset($_GET['featured_filter'])
        && $_GET['featured_filter'] === '1'
    ) {
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'key'     => 'featured',      // your ACF field name
            'value'   => '1',             // true_false stores "1" for checked
            'compare' => '=',
        ];
        $query->set('meta_query', $meta_query);
    }
});
