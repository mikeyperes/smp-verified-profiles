<?php namespace smp_verified_profiles;

// 1) Add the buttons to the admin “edit.php?post_type=wiki” screen
add_action('restrict_manage_posts', function($post_type, $which) {
    $settings = get_verified_profile_settings();

    if ($post_type !== $settings['slug'] || $which !== 'top') {
        return;
    }

    // — Featured ACF filter button
    $is_featured = isset($_GET['featured_filter']) && $_GET['featured_filter'] === '1';
    $base_feat   = remove_query_arg('featured_filter');
    $url_feat    = $is_featured
        ? $base_feat
        : add_query_arg('featured_filter', '1', $base_feat);

    printf(
        '<a href="%1$s" class="button%2$s" style="margin-left:8px;">%3$s</a>',
        esc_url($url_feat),
        $is_featured ? '' : ' button-primary',
        $is_featured ? 'Show All Profiles' : 'Filter by Featured'
    );

    // — Has Featured Image filter button
    $is_thumb   = isset($_GET['thumbnail_filter']) && $_GET['thumbnail_filter'] === '1';
    $base_thumb = remove_query_arg('thumbnail_filter');
    $url_thumb  = $is_thumb
        ? $base_thumb
        : add_query_arg('thumbnail_filter', '1', $base_thumb);

    printf(
        '<a href="%1$s" class="button%2$s" style="margin-left:8px;">%3$s</a>',
        esc_url($url_thumb),
        $is_thumb ? '' : ' button-primary',
        $is_thumb ? 'Show All Profiles' : 'Filter by Has Image'
    );
}, 10, 2);

// 2) Modify the query when those buttons/links are active
add_action('pre_get_posts', function(\WP_Query $query) {
    // 1) Only run in wp-admin (skip front-end & AJAX).
    if (!is_admin() || wp_doing_ajax()) {
        return;
    }

    // 2) Only target the main query.
    if (!$query->is_main_query()) {
        return;
    }

    // 3) Force the pagenow check: must be edit.php
    global $pagenow;
    if ($pagenow !== 'edit.php') {
        return;
    }

    // 4) Only target your CPT slug.
    try {
        $settings = get_verified_profile_settings();
    } catch (\Exception $e) {
        return;
    }
    if (empty($settings['slug']) || $query->get('post_type') !== $settings['slug']) {
        return;
    }

    // 5) Skip ACF’s own internal queries.
    if (isset($query->query_vars['acf_field_name'])) {
        return;
    }

    // Build up meta_query only if needed
    $meta_query = $query->get('meta_query') ?: [];

    // — Filter by ACF “featured” flag
    if (isset($_GET['featured_filter']) && $_GET['featured_filter'] === '1') {
        $meta_query[] = [
            'key'     => 'featured',
            'value'   => '1',
            'compare' => '=',
        ];
    }

    // — Filter by having a featured image
    if (isset($_GET['thumbnail_filter']) && $_GET['thumbnail_filter'] === '1') {
        $meta_query[] = [
            'key'     => '_thumbnail_id',
            'compare' => 'EXISTS',
        ];
    }

    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }
}, 10, 1);
