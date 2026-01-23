<?php namespace smp_verified_profiles;

function enable_acf_theme_options() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    // Main ACF group on the "Verified Profiles Settings" options page
    acf_add_local_field_group(array(
        'key'                   => 'group_6850930366d8f',
        'title'                 => 'Verified Profiles',
        'fields'                => array(
            // General Settings group
            array(
                'key'           => 'field_6850950000000',
                'label'         => 'General Settings',
                'name'          => 'general_settings',
                'type'          => 'group',
                'instructions'  => 'options_page:verified-profiles-settings > general_settings:group',
                'layout'        => 'block',
                'sub_fields'    => array(
                    array(
                        'key'           => 'field_6850950100001',
                        'label'         => 'Verified Profile CPT Plural Name',
                        'name'          => 'cpt_plural_name',
                        'type'          => 'text',
                        'instructions'  => 'options_page:verified-profiles-settings > general_settings:group > cpt_plural_name:text',
                    ),
                    array(
                        'key'           => 'field_6850950100002',
                        'label'         => 'Verified Profile CPT Singular Name',
                        'name'          => 'cpt_singular_name',
                        'type'          => 'text',
                        'instructions'  => 'options_page:verified-profiles-settings > general_settings:group > cpt_singular_name:text',
                    ),
                    array(
                        'key'           => 'field_6850950100003',
                        'label'         => 'Verified Profile CPT Slug',
                        'name'          => 'cpt_slug',
                        'type'          => 'text',
                        'instructions'  => 'options_page:verified-profiles-settings > general_settings:group > cpt_slug:text',
                    ),
                ),
            ),
            // Contributor Network group
            array(
                'key'           => 'field_6850930552d8c',
                'label'         => 'Contributor Network',
                'name'          => 'contributor_network',
                'type'          => 'group',
                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group',
                'layout'        => 'block',
                'sub_fields'    => array(
                    array(
                        'key'           => 'field_6850932e52d8d',
                        'label'         => 'Program Name',
                        'name'          => 'program_name',
                        'type'          => 'text',
                        'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > program_name:text',
                    ),
                    array(
                        'key'           => 'field_6850935b52d8e',
                        'label'         => 'Email',
                        'name'          => 'email',
                        'type'          => 'text',
                        'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > email:text',
                    ),
                    array(
                        'key'           => 'field_6850938a52d8f',
                        'label'         => 'Logo',
                        'name'          => 'logo',
                        'type'          => 'image',
                        'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > logo:image',
                        'return_format' => 'id',
                        'library'       => 'all',
                        'preview_size'  => 'medium',
                    ),
                    // Loop Items sub-group
                    array(
                        'key'           => 'field_6850940000001',
                        'label'         => 'Loop Items',
                        'name'          => 'loop_items',
                        'type'          => 'group',
                        'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > loop_items:group',
                        'layout'        => 'block',
                        'sub_fields'    => array(
                            array(
                                'key'           => 'field_6850940100001',
                                'label'         => 'Single Profile – Press Releases',
                                'name'          => 'display_single_profile_press_releases',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > loop_items:group > display_single_profile_press_releases:post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940100002',
                                'label'         => 'Single Profile – Articles Written by Profile Entity',
                                'name'          => 'display_single_profile_article_written_by',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > loop_items:group > display_single_profile_article_written_by:post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940100003',
                                'label'         => 'Single Profile – Articles Featured In',
                                'name'          => 'display_single_profile_articles_featured_in',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > loop_items:group > display_single_profile_articles_featured_in:post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940100004',
                                'label'         => 'Single Post – Entities Mentioned in Article',
                                'name'          => 'display_single_post_mentioned_in_article',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > loop_items:group > display_single_post_mentioned_in_article:post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                        ),
                    ),
                    // Pages sub-group
                    array(
                        'key'           => 'field_6850940000002',
                        'label'         => 'Pages',
                        'name'          => 'pages',
                        'type'          => 'group',
                        'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > pages:group',
                        'layout'        => 'block',
                        'sub_fields'    => array(
                            array(
                                'key'           => 'field_6850940200001',
                                'label'         => 'Verified Profile Badges',
                                'name'          => 'verified_profiles_badges',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > pages:group > verified_profiles_badges:post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940200002',
                                'label'         => 'Claim a Profile',
                                'name'          => 'verified_profiles_claim',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > pages:group > verified_profiles_claim:post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940200003',
                                'label'         => 'Apply for a Profile?',
                                'name'          => 'verified_profiles_apply',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > pages:group > verified_profiles_apply:post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940200004',
                                'label'         => 'Welcome?',
                                'name'          => 'verified_profiles_welcome',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > contributor_network:group > pages:group > verified_profiles_welcome:post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                        ),
                    ),
                ),
            ),
            // Verified Profile group
            array(
                'key'           => 'field_685093bc52d90',
                'label'         => 'Verified Profile',
                'name'          => 'verified_profile',
                'type'          => 'group',
                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group',
                'layout'        => 'block',
                'sub_fields'    => array(
                    array(
                        'key'           => 'field_685093bc52d91',
                        'label'         => 'Program Name',
                        'name'          => 'program_name',
                        'type'          => 'text',
                        'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > program_name:text',
                    ),
                    array(
                        'key'           => 'field_685093bc52d92',
                        'label'         => 'Email',
                        'name'          => 'email',
                        'type'          => 'text',
                        'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > email:text',
                    ),
                    array(
                        'key'           => 'field_685093bc52d93',
                        'label'         => 'Logo',
                        'name'          => 'logo',
                        'type'          => 'image',
                        'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > logo:image',
                        'return_format' => 'array',
                        'library'       => 'all',
                        'preview_size'  => 'medium',
                    ),
                    // Loop Items sub-group
                    array(
                        'key'           => 'field_6850940300001',
                        'label'         => 'Loop Items',
                        'name'          => 'loop_items',
                        'type'          => 'group',
                        'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > loop_items:group',
                        'layout'        => 'block',
                        'sub_fields'    => array(
                            array(
                                'key'           => 'field_6850940400001',
                                'label'         => 'Single Profile – Press Releases',
                                'name'          => 'display_single_profile_press_releases',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > loop_items:group > display_single_profile_press_releases:post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940400002',
                                'label'         => 'Single Profile – Articles Written by Profile Entity',
                                'name'          => 'display_single_profile_article_written_by',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > loop_items:group > display_single_profile_article_written_by:post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940400003',
                                'label'         => 'Single Profile – Articles Featured In',
                                'name'          => 'display_single_profile_articles_featured_in',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > loop_items:group > display_single_profile_articles_featured_in:post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940400004',
                                'label'         => 'Single Post – Entities Mentioned in Article',
                                'name'          => 'display_single_post_mentioned_in_article',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > loop_items:group > display_single_post_mentioned_in_article:post_object',
                                'post_type'     => array('elementor_library'),
                                'ui'            => 1,
                            ),
                        ),
                    ),
                    // Pages sub-group
                    array(
                        'key'           => 'field_6850940300002',
                        'label'         => 'Pages',
                        'name'          => 'pages',
                        'type'          => 'group',
                        'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > pages:group',
                        'layout'        => 'block',
                        'sub_fields'    => array(
                            array(
                                'key'           => 'field_6850940500001',
                                'label'         => 'Verified Profile Badges',
                                'name'          => 'verified_profiles_badges',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > pages:group > verified_profiles_badges:post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940500002',
                                'label'         => 'Claim a Profile',
                                'name'          => 'verified_profiles_claim',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > pages:group > verified_profiles_claim:post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940500003',
                                'label'         => 'Apply for a Profile?',
                                'name'          => 'verified_profiles_apply',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > pages:group > verified_profiles_apply:post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                            array(
                                'key'           => 'field_6850940500004',
                                'label'         => 'Welcome?',
                                'name'          => 'verified_profiles_welcome',
                                'type'          => 'post_object',
                                'instructions'  => 'options_page:verified-profiles-settings > verified_profile:group > pages:group > verified_profiles_welcome:post_object',
                                'post_type'     => array('page'),
                                'ui'            => 1,
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'location'              => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'verified-profiles-settings',
                ),
            ),
        ),
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,
        'description'           => '',
        'show_in_rest'          => 0,
    ));

    // Additional Shortcodes group on same options page
    acf_add_local_field_group(array(
        'key'                   => 'group_additional_shortcodes',
        'title'                 => 'Additional Shortcodes',
        'fields'                => array(
            array(
                'key'           => 'field_additional_shortcodes',
                'label'         => 'Additional Shortcodes',
                'name'          => 'additional_shortcodes',
                'type'          => 'message',
                'instructions'  => 'options_page:verified-profiles-settings > additional_shortcodes:message',
                'message'       => "[display_profile_muckrack_verified]\n[muckrack_verified]\n[acf_author_field]\n[verified_icon_author]\n[verified_single]\n[verified_author]\n[verified_icon_single]",
            ),
            array(
                'key'           => 'field_additional_shortcodes_details',
                'label'         => 'Details',
                'name'          => 'additional_shortcodes_details',
                'type'          => 'message',
                'instructions'  => 'options_page:verified-profiles-settings > additional_shortcodes_details:message',
                'message'       => "enable_snippet_verified_profile_shortcodes –\n[display_single_profile_education]\n[display_single_profile_organizations_founded]\n[display_single_profile_press_releases]\n[display_single_profile_article_written_by]\n[display_single_profile_articles_featured_in]\n[display_single_profile_text_based_social_profiles]\n[display_homepage_profiles]\n[display_single_post_mentioned_in_article]\n[display_theme_footer_text_social_links]\n[display_single_profile_validate_schema_button]\n[display_profiles_featured_in_single_post]\n[display_profile_council_banner]\n[display_profile_quick_contact]\n[display_profile_current_residence]\n[display_profile_location_born]\n[display_profile_notable_mentions]",
            ),
            array(
                'key'           => 'field_acf_structures',
                'label'         => 'Plugin ACF Structures',
                'name'          => 'acf_structures',
                'type'          => 'message',
                'instructions'  => 'options_page:verified-profiles-settings > acf_structures:message',
                'message'       => display_acf_structure(array(
                    'group_66b7bdf713e77',
                    'group_656ea6b4d7088',
                    'group_656eb036374de',
                    'group_65a8b25062d91',
                    'group_658602c9eaa49',
                )),
            ),
        ),
        'location'              => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'verified-profiles-settings',
                ),
            ),
        ),
        'menu_order'            => 1,
        'position'              => 'acf_after_title',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,
    ));

    // Register the options page
    acf_add_options_page(array(
        'page_title'    => 'Verified Profiles Settings',
        'menu_slug'     => 'verified-profiles-settings',
        'post_id'       => 'option',
        'redirect'      => false,
    ));
}

// Inject view/edit buttons on admin footer
add_action('acf/input/admin_footer', function() {
    $screen = get_current_screen();
    if ($screen->id !== 'toplevel_page_verified-profiles-settings') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        function appendButtons(field) {
            var postId = field.find('select').val();
            if (!postId) return;
            var viewUrl = '<?php echo esc_url(home_url()); ?>?p=' + postId;
            var editUrl = '<?php echo esc_url(admin_url('post.php')); ?>?post=' + postId + '&action=edit';
            var btnContainer = $('<div class="acf-view-edit-buttons" style="margin-top:8px;"></div>');
            btnContainer.append('<a href="' + viewUrl + '" target="_blank" class="button">View</a> ');
            btnContainer.append('<a href="' + editUrl + '" target="_blank" class="button">Edit</a>');
            field.append(btnContainer);
        }
        $('.acf-field-group[data-name="loop_items"] .acf-field-post-object,' +
          '.acf-field-group[data-name="pages"] .acf-field-post-object').each(function() {
            var field = $(this);
            appendButtons(field);
            field.on('change', 'select', function() {
                field.find('.acf-view-edit-buttons').remove();
                appendButtons(field);
            });
        });
    });
    </script>
    <?php
});
