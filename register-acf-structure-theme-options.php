<?php namespace smp_verified_profiles;

function enable_acf_theme_options() {


    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(array(
        'key'           => 'group_6850930366d8f',
        'title'         => 'Verified Profiles',
        'fields'        => array(
                     // General Settings group
                     array(
                        'key'        => 'field_6850950000000',
                        'label'      => 'General Settings',
                        'name'       => 'general_settings',
                        'type'       => 'group',
                        'layout'     => 'block',
                        'sub_fields' => array(
                            array(
                                'key'   => 'field_6850950100001',
                                'label' => 'Verified Profile CPT Plural Name',
                                'name'  => 'cpt_plural_name',
                                'type'  => 'text',
                            ),
                            array(
                                'key'   => 'field_6850950100002',
                                'label' => 'Verified Profile CPT Singular Name',
                                'name'  => 'cpt_singular_name',
                                'type'  => 'text',
                            ),
                            array(
                                'key'   => 'field_6850950100003',
                                'label' => 'Verified Profile CPT Slug',
                                'name'  => 'cpt_slug',
                                'type'  => 'text',
                            ),
                        ),
                    ),
            // Contributor Network group
            array(
                'key'       => 'field_6850930552d8c',
                'label'     => 'Contributor Network',
                'name'      => 'contributor_network',
                'type'      => 'group',
                'layout'    => 'block',
                'sub_fields'=> array(
                    array(
                        'key'           => 'field_6850932e52d8d',
                        'label'         => 'Program Name',
                        'name'          => 'program_name',
                        'type'          => 'text',
                    ),
                    array(
                        'key'           => 'field_6850935b52d8e',
                        'label'         => 'Email',
                        'name'          => 'email',
                        'type'          => 'text',
                    ),
                    array(
                        'key'           => 'field_6850938a52d8f',
                        'label'         => 'Logo',
                        'name'          => 'logo',
                        'type'          => 'image',
                        'return_format' => 'id',
                        'library'       => 'all',
                        'preview_size'  => 'medium',
                    ),
                    // Loop Items under Contributor Network
                    array(
                        'key'       => 'field_6850940000001',
                        'label'     => 'Loop Items',
                        'name'      => 'loop_items',
                        'type'      => 'group',
                        'layout'    => 'block',
                        'sub_fields'=> array(
                            array(
                                'key'           => 'field_6850940100001',
                                'label'         => 'Single Profile – Press Releases',
                                'name'          => 'display_single_profile_press_releases',
                                'type'          => 'post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940100002',
                                'label'         => 'Single Profile – Articles Written by Profile Entity',
                                'name'          => 'display_single_profile_article_written_by',
                                'type'          => 'post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940100003',
                                'label'         => 'Single Profile – Articles Featured In',
                                'name'          => 'display_single_profile_articles_featured_in',
                                'type'          => 'post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940100004',
                                'label'         => 'Single Post – Entities Mentioned in Article',
                                'name'          => 'display_single_post_mentioned_in_article',
                                'type'          => 'post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                        ),
                    ),
                    // Pages under Contributor Network
                    array(
                        'key'       => 'field_6850940000002',
                        'label'     => 'Pages',
                        'name'      => 'pages',
                        'type'      => 'group',
                        'layout'    => 'block',
                        'sub_fields'=> array(
                            array(
                                'key'           => 'field_6850940200001',
                                'label'         => 'Verified Profile Badges',
                                'name'          => 'verified_profiles_badges',
                                'type'          => 'post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940200002',
                                'label'         => 'Claim a Profile',
                                'name'          => 'verified_profiles_claim',
                                'type'          => 'post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940200003',
                                'label'         => 'Apply for a Profile?',
                                'name'          => 'verified_profiles_apply',
                                'type'          => 'post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940200004',
                                'label'         => 'Welcome?',
                                'name'          => 'verified_profiles_welcome',
                                'type'          => 'post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                        ),
                    ),
                ),
            ),
            // Verified Profile group
            array(
                'key'       => 'field_685093bc52d90',
                'label'     => 'Verified Profile',
                'name'      => 'verified_profile',
                'type'      => 'group',
                'layout'    => 'block',
                'sub_fields'=> array(
                    array(
                        'key'   => 'field_685093bc52d91',
                        'label' => 'Program Name',
                        'name'  => 'program_name',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_685093bc52d92',
                        'label' => 'Email',
                        'name'  => 'email',
                        'type'  => 'text',
                    ),
                    array(
                        'key'           => 'field_685093bc52d93',
                        'label'         => 'Logo',
                        'name'          => 'logo',
                        'type'          => 'image',
                        'return_format' => 'array',
                        'library'       => 'all',
                        'preview_size'  => 'medium',
                    ),
                    // Loop Items under Verified Profile
                    array(
                        'key'       => 'field_6850940300001',
                        'label'     => 'Loop Items',
                        'name'      => 'loop_items',
                        'type'      => 'group',
                        'layout'    => 'block',
                        'sub_fields'=> array(
                            array(
                                'key'       => 'field_6850940400001',
                                'label'     => 'Single Profile – Press Releases',
                                'name'      => 'display_single_profile_press_releases',
                                'type'      => 'post_object',
                                'post_type' => array('elementor_library'),
                                'ui'        => 1,
                            ),
                            array(
                                'key'       => 'field_6850940400002',
                                'label'     => 'Single Profile – Articles Written by Profile Entity',
                                'name'      => 'display_single_profile_article_written_by',
                                'type'      => 'post_object',
                                'post_type' => array('elementor_library'),
                                'ui'        => 1,
                            ),
                            array(
                                'key'       => 'field_6850940400003',
                                'label'     => 'Single Profile – Articles Featured In',
                                'name'      => 'display_single_profile_articles_featured_in',
                                'type'      => 'post_object',
                                'post_type' => array('elementor_library'),
                                'ui'        => 1,
                            ),
                            array(
                                'key'       => 'field_6850940400004',
                                'label'     => 'Single Post – Entities Mentioned in Article',
                                'name'      => 'display_single_post_mentioned_in_article',
                                'type'      => 'post_object',
                                'post_type' => array('elementor_library'),
                                'ui'        => 1,
                            ),
                        ),
                    ),
                    // Pages under Verified Profile
                    array(
                        'key'       => 'field_6850940300002',
                        'label'     => 'Pages',
                        'name'      => 'pages',
                        'type'      => 'group',
                        'layout'    => 'block',
                        'sub_fields'=> array(
                            array(
                                'key'       => 'field_6850940500001',
                                'label'     => 'Verified Profile Badges',
                                'name'      => 'verified_profiles_badges',
                                'type'      => 'post_object',
                                'post_type' => array('page'),
                                'ui'        => 1,
                            ),
                            array(
                                'key'       => 'field_6850940500002',
                                'label'     => 'Claim a Profile',
                                'name'      => 'verified_profiles_claim',
                                'type'      => 'post_object',
                                'post_type' => array('page'),
                                'ui'        => 1,
                            ),
                            array(
                                'key'       => 'field_6850940500003',
                                'label'     => 'Apply for a Profile?',
                                'name'      => 'verified_profiles_apply',
                                'type'      => 'post_object',
                                'post_type' => array('page'),
                                'ui'        => 1,
                            ),
                            array(
                                'key'       => 'field_6850940500004',
                                'label'     => 'Welcome?',
                                'name'      => 'verified_profiles_welcome',
                                'type'      => 'post_object',
                                'post_type' => array('page'),
                                'ui'        => 1,
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'location'      => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'verified-profiles-settings',
                ),
            ),
        ),
        'menu_order'        => 0,
        'position'          => 'normal',
        'style'             => 'default',
        'label_placement'   => 'top',
        'instruction_placement' => 'label',
        'active'            => true,
        'description'       => '',
        'show_in_rest'      => 0,
    ));

    acf_add_options_page(array(
        'page_title' => 'Verified Profiles Settings',
        'menu_slug'  => 'verified-profiles-settings',
        'post_id'    => 'option',
        'redirect'   => false,
    ));
}



// Hook into ACF admin footer to inject view/edit buttons
add_action('acf/input/admin_footer', function() {
    $screen = get_current_screen();
    // Only target our Verified Profiles Settings page
    if ($screen->id !== 'toplevel_page_verified-profiles-settings') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        // Helper to append view/edit buttons to a post-object field
        function appendButtons(field) {
            var postId = field.find('select').val();
            if (!postId) return;
            var viewUrl = '<?php echo esc_url( home_url() ); ?>?p=' + postId;
            var editUrl = '<?php echo esc_url( admin_url('post.php') ); ?>?post=' + postId + '&action=edit';
            var btnContainer = $('<div class="acf-view-edit-buttons" style="margin-top:8px;"></div>');
            btnContainer.append('<a href="' + viewUrl + '" target="_blank" class="button">View</a> ');
            btnContainer.append('<a href="' + editUrl + '" target="_blank" class="button">Edit</a>');
            field.append(btnContainer);
        }

        // Process each post-object inside loop_items and pages groups
        $('.acf-field-group[data-name="loop_items"] .acf-field-post-object,' +
          ' .acf-field-group[data-name="pages"] .acf-field-post-object').each(function() {
            var field = $(this);
            appendButtons(field);
            // Update buttons on select change
            field.on('change', 'select', function() {
                field.find('.acf-view-edit-buttons').remove();
                appendButtons(field);
            });
        });
    });
    </script>
    <?php
});
