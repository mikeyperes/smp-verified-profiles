<?php namespace smp_verified_profiles;



function register_profile_custom_post_type(){ 


      // pull in singular, plural, slug (will be 'wiki' if that's what you saved)
      $opts     = get_verified_profile_settings();
      $singular = sanitize_text_field( $opts['singular'] );
      $plural   = sanitize_text_field( $opts['plural'] );
      $slug     = sanitize_title(      $opts['slug'] );
  

// sanitize and apply defaults
$singular = ! empty( $opts['singular'] ) ? sanitize_text_field( $opts['singular'] ) : 'Verified Profile';
$plural   = ! empty( $opts['plural']   ) ? sanitize_text_field( $opts['plural']   ) : 'Verified Profiles';
$slug     = ! empty( $opts['slug']     ) ? sanitize_title( $opts['slug']     ) : 'profile';

$labels = [
    'name'                     => $plural ,
    'singular_name'            => $singular,
    'menu_name'                => $plural ,
    'all_items'                => 'All ' . $plural,
    'edit_item'                => 'Edit ' . $singular,
    'view_item'                => 'View ' . $singular,
    'view_items'               => 'View ' . $plural,
    'add_new_item'             => 'Add New ' . $singular,
    'add_new'                  => 'Add New ' . $singular,
    'new_item'                 => 'New ' . $singular,
    'parent_item_colon'        => 'Parent ' . $singular . ':',
    'search_items'             => 'Search ' . $plural,
    'not_found'                => 'No ' . strtolower( $plural ) . ' found',
    'not_found_in_trash'       => 'No ' . strtolower( $plural ) . ' found in Trash',
    'archives'                 => $singular . ' Archives',
    'attributes'               => $singular . ' Attributes',
    'insert_into_item'         => 'Insert into ' . strtolower( $singular ),
    'uploaded_to_this_item'    => 'Uploaded to this ' . strtolower( $singular ),
    'filter_items_list'        => 'Filter ' . strtolower( $plural ) . ' list',
    'filter_by_date'           => 'Filter ' . $plural . ' by date',
    'items_list_navigation'    => $plural . ' list navigation',
    'items_list'               => $plural . ' list',
    'item_published'           => $singular . ' published.',
    'item_published_privately' => $singular . ' published privately.',
    'item_reverted_to_draft'   => $singular . ' reverted to draft.',
    'item_scheduled'           => $singular . ' scheduled.',
    'item_updated'             => $singular . ' updated.',
    'item_link'                => $singular . ' Link',
    'item_link_description'    => 'A link to a ' . strtolower( $singular ) . '.',
];

register_post_type( $slug, [
    'labels'             => $labels,
    'public'             => true,
    'show_in_rest'       => true,
    'supports'           => [
        'title',
        'author',
        'trackbacks',
        'editor',
        'excerpt',
        'revisions',
        'page-attributes',
        'thumbnail',
        'custom-fields',
        'post-formats',
    ],
    'taxonomies'         => [ 'category', 'post_tag' ],
    'delete_with_user'   => false,
] );


}

































function register_profile_general_acf_fields() {
    $settings = get_verified_profile_settings();
    $singular = $settings['singular'];
    $plural   = $settings['plural'];
    $slug     = $settings['slug'];

        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }
    
        acf_add_local_field_group( array(
        'key' => 'group_66b7bdf713e77',
        'title' => 'Post - Verified Profile - Admin',
        'fields' => array(
            array(
                'key' => 'field_656c17469ad33',
                'label' => 'Profiles',
                'name' => 'profiles',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'row',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Add Row',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_656c17629ad34',
                        'label' => 'Profile',
                        'name' => 'profile',
                        'aria-label' => '',
                        'type' => 'post_object',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'profile',
                        ),
                        'post_status' => '',
                        'taxonomy' => '',
                        'return_format' => 'id',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'bidirectional' => 0,
                        'ui' => 1,
                        'bidirectional_target' => array(
                        ),
                        'parent_repeater' => 'field_656c17469ad33',
                    ),
                ),
            ),
            array(
                'key' => 'field_65852128a382d',
                'label' => 'Pending Profiles',
                'name' => 'pending_profiles',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'table',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Add Row',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_658521e7a382e',
                        'label' => 'Name',
                        'name' => 'name',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_65852128a382d',
                    ),
                    array(
                        'key' => 'field_658521f3a382f',
                        'label' => 'Profile Type',
                        'name' => 'type',
                        'aria-label' => '',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => array(
                            'person' => 'Person',
                            'organization' => 'Organization',
                        ),
                        'default_value' => false,
                        'return_format' => 'value',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'ui' => 0,
                        'ajax' => 0,
                        'placeholder' => '',
                        'parent_repeater' => 'field_65852128a382d',
                    ),
                    array(
                        'key' => 'field_6585224da3830',
                        'label' => 'URL',
                        'name' => 'url',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_65852128a382d',
                    ),
                ),
            ),
        ),
  'location'          => array(
            // Single rule-group: BOTH must match
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'post',
                ),
                array(
                    'param'    => 'current_user_role',
                    'operator' => '==',
                    'value'    => 'administrator',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ) );
 
    
    
}



function register_verified_profile_pages_custom_fields() {
    return;
    if ( ! function_exists('acf_add_local_field_group') ) {
        return;
    }

    // Fetch settings
    $settings = get_verified_profile_settings();
    $slug      = $settings['slug'];
    $singular  = $settings['singular'];
    $domain    = parse_url( home_url(), PHP_URL_HOST );

    // ---- CONFIG: define all loop-item & page fields here ----
    $fields_config = [
        // Elementor Loop Items
        [
            'id'         => 'display_single_profile_press_releases',
            'label'      => 'Elementor Loop-Item: Single Profile - Press Releases',
            'post_types' => [ 'elementor_library' ],
        ],
        [
            'id'         => 'display_single_profile_article_written_by',
            'label'      => 'Elementor Loop-Item: Single Profile - Articles Written by Profile Entity',
            'post_types' => [ 'elementor_library' ],
        ],
        [
            'id'         => 'display_single_profile_articles_featured_in',
            'label'      => 'Elementor Loop-Item: Single Profile - Articles Profile Entity was Featured In',
            'post_types' => [ 'elementor_library' ],
        ],
        [
            'id'         => 'display_single_post_mentioned_in_article',
            'label'      => 'Elementor Loop-Item: Single Post - Entities Mentioned in Article',
            'post_types' => [ 'elementor_library' ],
        ],

        // Pages
        [
            'id'         => 'page_verified_profiles_badges',
            'label'      => 'Page: Verified Profiles - Verified Profile Badges',
            'post_types' => [ 'page' ],
        ],
        [
            'id'         => 'page_verified_profiles_claim',
            'label'      => 'Page: Verified Profiles - Claim a Profile',
            'post_types' => [ 'page' ],
        ],
        [
            'id'         => 'page_verified_profiles_apply',
            'label'      => 'Page: Verified Profiles - Apply for a Profile?',
            'post_types' => [ 'page' ],
        ],
        [
            'id'         => 'page_verified_profiles_welcome',
            'label'      => 'Page: Verified Profiles - Welcome?',
            'post_types' => [ 'page' ],
        ],
    ];
    // ----------------------------------------

    // Build actual ACF field definitions from config
    $acf_fields = [];
    foreach ( $fields_config as $cfg ) {
        $acf_fields[] = [
            'key'           => $cfg['id'],
            'name'          => $cfg['id'],
            'label'         => $cfg['label'],
            'type'          => 'post_object',
            'post_type'     => $cfg['post_types'],
            'return_format' => 'id',
            'ui'            => 1,
            'allow_null'    => 1,
        ];
    }

    // ---- ADDITIONAL SETTINGS FIELDS ----
    // CPT slug & names
    $acf_fields[] = [
        'key'           => 'verified_profile_cpt_slug',
        'name'          => 'verified_profile_cpt_slug',
        'label'         => 'Verified Profile CPT Slug',
        'type'          => 'text',
        'default_value' => 'profile',
    ];
    $acf_fields[] = [
        'key'           => 'verified_profile_cpt_plural_name',
        'name'          => 'verified_profile_cpt_plural_name',
        'label'         => 'Verified Profile CPT Plural Name',
        'type'          => 'text',
        'default_value' => 'Profiles',
    ];
    $acf_fields[] = [
        'key'           => 'verified_profile_cpt_singular_name',
        'name'          => 'verified_profile_cpt_singular_name',
        'label'         => 'Verified Profile CPT Singular Name',
        'type'          => 'text',
        'default_value' => 'Profile',
    ];

    // Program info
    $acf_fields[] = [
        'key'  => 'verified_profile_program_name',
        'name' => 'verified_profile_program_name',
        'label'=> 'Verified Profile Program Name',
        'type' => 'text',
    ];
    $acf_fields[] = [
        'key'  => 'verified_profile_program_email',
        'name' => 'verified_profile_program_email',
        'label'=> 'Verified Profile Program Email',
        'type' => 'text',
    ];
    $acf_fields[] = [
        'key'           => 'verified_profile_program_logo',
        'name'          => 'verified_profile_program_logo',
        'label'         => 'Verified Profile Program Logo',
        'type'          => 'file',
        'return_format' => 'url',
        'library'       => 'all',
    ];

    // Contributor network info
    $acf_fields[] = [
        'key'  => 'contributor_network_program_name',
        'name' => 'contributor_network_program_name',
        'label'=> 'Contributor Network Program Name',
        'type' => 'text',
    ];
    $acf_fields[] = [
        'key'  => 'contributor_network_program_email',
        'name' => 'contributor_network_program_email',
        'label'=> 'Contributor Network Program Email',
        'type' => 'text',
    ];
    $acf_fields[] = [
        'key'           => 'contributor_network_program_logo',
        'name'          => 'contributor_network_program_logo',
        'label'         => 'Contributor Network Program Logo',
        'type'          => 'file',
        'return_format' => 'url',
        'library'       => 'all',
    ];
    // ----------------------------------------

    // Register the field group on the Verified Profiles options page
    acf_add_local_field_group([
        'key'      => 'group_verified_profiles_settings',
        'title'    => 'Verified Profiles Settings',
        'fields'   => $acf_fields,
        'location' => [[[
            'param'    => 'options_page',
            'operator' => '==',
            'value'    => 'verified-profiles',
        ]]],
    ]);
}
