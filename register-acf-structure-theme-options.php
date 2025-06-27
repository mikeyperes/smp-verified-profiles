<?php
namespace smp_verified_profiles;

function enable_acf_theme_options() {

    if ( ! function_exists('acf_add_local_field_group') ) {
        return;
    }

    acf_add_local_field_group([
        'key'    => 'group_6850930366d8f',
        'title'  => 'Verified Profiles',
        'fields' => [
            // General Settings group
            [
                'key'   => 'field_6850950000000',
                'label' => 'General Settings<br><span style="color:#999;font-size:12px;">name: general_settings<br>[verified_profile field="general_settings"]</span>',
                'name'  => 'general_settings',
                'type'  => 'group',
                'layout'=> 'block',
                'sub_fields' => [
                    [
                        'key'   => 'field_6850950100001',
                        'label' => 'Verified Profile CPT Plural Name<br><span style="color:#999;font-size:12px;">name: cpt_plural_name<br>[verified_profile field="cpt_plural_name"]</span>',
                        'name'  => 'cpt_plural_name',
                        'type'  => 'text',
                    ],
                    [
                        'key'   => 'field_6850950100002',
                        'label' => 'Verified Profile CPT Singular Name<br><span style="color:#999;font-size:12px;">name: cpt_singular_name<br>[verified_profile field="cpt_singular_name"]</span>',
                        'name'  => 'cpt_singular_name',
                        'type'  => 'text',
                    ],
                    [
                        'key'   => 'field_6850950100003',
                        'label' => 'Verified Profile CPT Slug<br><span style="color:#999;font-size:12px;">name: cpt_slug<br>[verified_profile field="cpt_slug"]</span>',
                        'name'  => 'cpt_slug',
                        'type'  => 'text',
                    ],
                ],
            ],

            // Contributor Network group
            [
                'key'   => 'field_6850930552d8c',
                'label' => 'Contributor Network<br><span style="color:#999;font-size:12px;">name: contributor_network<br>[verified_profile field="contributor_network"]</span>',
                'name'  => 'contributor_network',
                'type'  => 'group',
                'layout'=> 'block',
                'sub_fields' => [
                    [
                        'key'   => 'field_6850932e52d8d',
                        'label' => 'Program Name<br><span style="color:#999;font-size:12px;">name: program_name<br>[verified_profile field="program_name"]</span>',
                        'name'  => 'program_name',
                        'type'  => 'text',
                    ],
                    [
                        'key'   => 'field_6850935b52d8e',
                        'label' => 'Email<br><span style="color:#999;font-size:12px;">name: email<br>[verified_profile field="email"]</span>',
                        'name'  => 'email',
                        'type'  => 'text',
                    ],
                    [
                        'key'   => 'field_6850938a52d8f',
                        'label' => 'Logo<br><span style="color:#999;font-size:12px;">name: logo<br>[verified_profile field="logo"]</span>',
                        'name'  => 'logo',
                        'type'  => 'image',
                        'return_format' => 'id',
                        'library'       => 'all',
                        'preview_size'  => 'medium',
                    ],

                    // Loop Items under Contributor Network
                    [
                        'key'   => 'field_6850940000001',
                        'label' => 'Loop Items<br><span style="color:#999;font-size:12px;">name: loop_items<br>[verified_profile field="loop_items"]</span>',
                        'name'  => 'loop_items',
                        'type'  => 'group',
                        'layout'=> 'block',
                        'sub_fields' => [
                            [
                                'key'   => 'field_6850940100001',
                                'label' => 'Single Profile – Press Releases<br><span style="color:#999;font-size:12px;">name: display_single_profile_press_releases<br>[verified_profile field="loop_items.display_single_profile_press_releases"]</span>',
                                'name'  => 'display_single_profile_press_releases',
                                'type'  => 'post_object',
                                'post_type' => ['elementor_library'],
                                'ui'    => 1,
                            ],
                            [
                                'key'   => 'field_6850940100002',
                                'label' => 'Single Profile – Articles Written by Profile Entity<br><span style="color:#999;font-size:12px;">name: display_single_profile_article_written_by<br>[verified_profile field="loop_items.display_single_profile_article_written_by"]</span>',
                                'name'  => 'display_single_profile_article_written_by',
                                'type'  => 'post_object',
                                'post_type' => ['elementor_library'],
                                'ui'    => 1,
                            ],
                            [
                                'key'   => 'field_6850940100003',
                                'label' => 'Single Profile – Articles Featured In<br><span style="color:#999;font-size:12px;">name: display_single_profile_articles_featured_in<br>[verified_profile field="loop_items.display_single_profile_articles_featured_in"]</span>',
                                'name'  => 'display_single_profile_articles_featured_in',
                                'type'  => 'post_object',
                                'post_type' => ['elementor_library'],
                                'ui'    => 1,
                            ],
                            [
                                'key'   => 'field_6850940100004',
                                'label' => 'Single Post – Entities Mentioned in Article<br><span style="color:#999;font-size:12px;">name: display_single_post_mentioned_in_article<br>[verified_profile field="loop_items.display_single_post_mentioned_in_article"]</span>',
                                'name'  => 'display_single_post_mentioned_in_article',
                                'type'  => 'post_object',
                                'post_type' => ['elementor_library'],
                                'ui'    => 1,
                            ],
                        ],
                    ],

                    // Pages under Contributor Network
                    [
                        'key'   => 'field_6850940000002',
                        'label' => 'Pages<br><span style="color:#999;font-size:12px;">name: pages<br>[verified_profile field="pages"]</span>',
                        'name'  => 'pages',
                        'type'  => 'group',
                        'layout'=> 'block',
                        'sub_fields' => [
                            [
                                'key'   => 'field_6850940200001',
                                'label' => 'Verified Profile Badges<br><span style="color:#999;font-size:12px;">name: verified_profiles_badges<br>[verified_profile field="pages.verified_profiles_badges"]</span>',
                                'name'  => 'verified_profiles_badges',
                                'type'  => 'post_object',
                                'post_type' => ['page'],
                                'ui'    => 1,
                            ],
                            [
                                'key'   => 'field_6850940200002',
                                'label' => 'Claim a Profile<br><span style="color:#999;font-size:12px;">name: verified_profiles_claim<br>[verified_profile field="pages.verified_profiles_claim"]</span>',
                                'name'  => 'verified_profiles_claim',
                                'type'  => 'post_object',
                                'post_type' => ['page'],
                                'ui'    => 1,
                            ],
                            [
                                'key'   => 'field_6850940200003',
                                'label' => 'Apply for a Profile?<br><span style="color:#999;font-size:12px;">name: verified_profiles_apply<br>[verified_profile field="pages.verified_profiles_apply"]</span>',
                                'name'  => 'verified_profiles_apply',
                                'type'  => 'post_object',
                                'post_type' => ['page'],
                                'ui'    => 1,
                            ],
                            [
                                'key'   => 'field_6850940200004',
                                'label' => 'Welcome?<br><span style="color:#999;font-size:12px;">name: verified_profiles_welcome<br>[verified_profile field="pages.verified_profiles_welcome"]</span>',
                                'name'  => 'verified_profiles_welcome',
                                'type'  => 'post_object',
                                'post_type' => ['page'],
                                'ui'    => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],

        'location' => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'verified-profiles-settings',
                ],
            ],
        ],

        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,
        'description'           => '',
        'show_in_rest'          => 0,
    ]);


    // Additional Shortcodes message field
    acf_add_local_field_group([
        'key'    => 'group_additional_shortcodes',
        'title'  => 'Additional Shortcodes',
        'fields' => [
            [
                'key'     => 'field_additional_shortcodes',
                'label'   => 'Additional Shortcodes',
                'name'    => 'additional_shortcodes',
                'type'    => 'message',
                'message' => "[display_profile_muckrack_verified]\n[muckrack_verified]\n[acf_author_field]\n[verified_icon_author]\n[verified_single]\n[verified_author]\n[verified_icon_single]",
            ],
            [
                'key'     => 'field_additional_shortcodes_details',
                'label'   => 'Details',
                'name'    => 'additional_shortcodes_details',
                'type'    => 'message',
                'message' => "enable_snippet_verified_profile_shortcodes 
            –
           [display_single_profile_education]
           [display_single_profile_organizations_founded]
           [display_single_profile_press_releases]
           [display_single_profile_article_written_by]
           [display_single_profile_articles_featured_in]
           [display_single_profile_text_based_social_profiles]
           [display_homepage_profiles]
           [display_single_post_mentioned_in_article]
           [display_theme_footer_text_social_links]
           [display_single_profile_validate_schema_button]
           [display_profiles_featured_in_single_post]
           [display_profile_council_banner]
           [display_profile_quick_contact]
           [display_profile_current_residence]
           [display_profile_location_born]
           [display_profile_notable_mentions]",
            ],
        ],
        'location' => [
            [[ 'param'=>'options_page','operator'=>'==','value'=>'verified-profiles-settings' ]],
        ],
        'menu_order' => 1,
        'position'   => 'acf_after_title',
        'style'      => 'default',
        'label_placement'=>'top',
        'instruction_placement'=>'label',
        'active'     => true,
    ]);


    acf_add_options_page([
        'page_title' => 'Verified Profiles Settings',
        'menu_slug'  => 'verified-profiles-settings',
        'post_id'    => 'option',
        'redirect'   => false,
    ]);
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