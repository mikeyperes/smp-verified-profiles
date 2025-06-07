<?php namespace smp_verified_profiles;

/**
 * 1) Add the buttons to the admin “edit.php?post_type=wiki” screen
 */
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


/**
 * 2) Modify the query when those buttons/links are active
 */
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


/**
 * 3) Register the “Featured” column in the list table
 */
add_action('admin_init', function() {
    $settings = get_verified_profile_settings();
    $slug     = $settings['slug'];

    // Add the column header
    add_filter("manage_{$slug}_posts_columns", function($columns) {
        $columns['featured'] = __('Featured', 'smp_verified_profiles');
        return $columns;
    });

    // Render the column contents
    add_action("manage_{$slug}_posts_custom_column", function($column, $post_id) {
        if ($column === 'featured') {
            echo get_post_meta($post_id, 'featured', true) === '1' ? 'Yes' : 'No';
        }
    }, 10, 2);
});


/**
 * 4) Add the ACF “featured” checkbox to Quick Edit
 */
add_action('quick_edit_custom_box', function($column_name, $post_type) {
    $settings = get_verified_profile_settings();
    if ($post_type !== $settings['slug'] || $column_name !== 'featured') {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <span class="title">Featured</span>
                <span class="input-text-wrap">
                    <input type="checkbox" name="featured" value="1">
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}, 10, 2);


/**
 * 5) Add the ACF “featured” checkbox to Bulk Edit
 */
add_action('bulk_edit_custom_box', function($column_name, $post_type) {
    $settings = get_verified_profile_settings();
    if ($post_type !== $settings['slug'] || $column_name !== 'featured') {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-left">
        <div class="inline-edit-col">
            <label>
                <input type="checkbox" name="featured_bulk" value="1">
                <span class="title"><?php esc_html_e('Featured', 'smp_verified_profiles'); ?></span>
            </label>
        </div>
    </fieldset>
    <?php
}, 10, 2);


/**
 * 6) Save Quick & Bulk Edit “featured” values
 */
add_action('save_post', function($post_id, $post, $update) {
    $settings = get_verified_profile_settings();
    if ($post->post_type !== $settings['slug']) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Bulk edit handling
    if (isset($_REQUEST['bulk_edit'])) {
        $new = isset($_REQUEST['featured_bulk']) ? '1' : '0';
        update_post_meta($post_id, 'featured', $new);
        return;
    }

    // Quick edit handling
    $new = isset($_POST['featured']) ? '1' : '0';
    update_post_meta($post_id, 'featured', $new);
}, 10, 3);


/**
 * 7) Inline JS to populate Quick Edit with the existing value
 */
add_action('admin_footer-edit.php', function() {
    $screen   = get_current_screen();
    $settings = get_verified_profile_settings();
    if (!$screen || $screen->post_type !== $settings['slug']) {
        return;
    }
    ?>
    <script type="text/javascript">
    (function($){
        var originalEdit = inlineEditPost.edit;
        inlineEditPost.edit = function(post_id){
            originalEdit.apply(this, arguments);
            var id = typeof(post_id) === 'object'
                ? parseInt(this.getId(post_id))
                : post_id;
            if (id > 0) {
                var $row       = $('#post-' + id),
                    featured   = $row.find('.column-featured').text().trim() === 'Yes';
                $('#edit-' + id).find('input[name="featured"]').prop('checked', featured);
            }
        };
    })(jQuery);
    </script>
    <?php
});
