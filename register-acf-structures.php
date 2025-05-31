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

    $groups = [
        // 1) Post - Verified Profile - Admin
        [
            'key'      => 'group_post_' . $slug . '_admin',
            'title'    => "Post - {$singular} - Admin",
            'fields'   => [
                [
                    'key'           => 'field_profile_profiles',
                    'label'         => $plural,
                    'name'          => 'profiles',
                    'type'          => 'repeater',
                    'layout'        => 'row',
                    'button_label'  => 'Add Row',
                    'rows_per_page' => 20,
                    'sub_fields'    => [
                        [
                            'key'           => 'field_profile_profile',
                            'label'         => $singular,
                            'name'          => 'profile',
                            'type'          => 'post_object',
                            'post_type'     => [$slug],
                            'return_format' => 'id',
                            'ui'            => 1,
                        ],
                    ],
                ],
                [
                    'key'           => 'field_profile_pending_profiles',
                    'label'         => 'Pending ' . $plural,
                    'name'          => 'pending_profiles',
                    'type'          => 'repeater',
                    'layout'        => 'table',
                    'button_label'  => 'Add Row',
                    'rows_per_page' => 20,
                    'sub_fields'    => [
                        ['key'=>'field_pending_name','label'=>'Name','name'=>'name','type'=>'text'],
                        ['key'=>'field_pending_type','label'=>'Profile Type','name'=>'type','type'=>'select','choices'=>['person'=>'Person','organization'=>'Organization']],
                        ['key'=>'field_pending_url','label'=>'URL','name'=>'url','type'=>'text'],
                    ],
                ],
            ],
            'location' => [
                [['param'=>'post_type','operator'=>'==','value'=>'post']],
                [['param'=>'current_user_role','operator'=>'==','value'=>'administrator']],
            ],
        ],

        // 2) Profile - Admin
        [
            'key'      => 'group_' . $slug . '_admin',
            'title'    => "{$singular} - Admin",
            'fields'   => [
                ['key'=>'field_contributor_profile','label'=>'Contributor Profile','name'=>'contributor_profile','type'=>'user','return_format'=>'id'],
                ['key'=>'field_schema_markup','label'=>'Schema Markup','name'=>'schema_markup','type'=>'textarea'],
                ['key'=>'field_post_id','label'=>'Post ID','name'=>'post_id','type'=>'text'],
                ['key'=>'field_google_knowledge_graph_id','label'=>'Google Knowledge Graph ID','name'=>'google_knowledge_graph_id','type'=>'text'],
                ['key'=>'field_is_council_member','label'=>'Is Council Member','name'=>'is_council_member','type'=>'true_false'],
                ['key'=>'field_is_contributor','label'=>'Is Contributor','name'=>'is_contributor','type'=>'true_false'],
                ['key'=>'field_external_id','label'=>'External ID','name'=>'external_id','type'=>'text'],
            ],
            'location' => [[['param'=>'post_type','operator'=>'==','value'=>$slug],['param'=>'current_user_role','operator'=>'==','value'=>'administrator']]],
        ],

        // 3) Profile - Person - Public
        [
            'key'      => 'group_' . $slug . '_person_public',
            'title'    => "{$singular} - Person - Public",
            'fields'   => [
                [
                    'key'          => 'field_organizations_founded',
                    'label'        => 'Organizations Founded',
                    'name'         => 'organizations_founded',
                    'type'         => 'repeater',
                    'instructions' => sprintf('Select a %s. Contact <a href="mailto:verified@%1$s">verified@%1$s</a> if you don\'t see your organization.', parse_url(home_url(), PHP_URL_HOST)),
                    'layout'       => 'table',
                    'button_label' => 'Add Row',
                    'rows_per_page'=> 20,
                    'sub_fields'   => [
                        [
                            'key'           => 'field_organization',
                            'label'         => 'Organization',
                            'name'          => 'organization',
                            'type'          => 'post_object',
                            'post_type'     => [$slug],
                            'post_status'   => ['publish'],
                            'return_format' => 'object',
                            'ui'            => 1,
                        ],
                    ],
                ],
                [
                    'key'           => 'field_books',
                    'label'         => 'Books',
                    'name'          => 'books',
                    'type'          => 'repeater',
                    'layout'        => 'table',
                    'button_label'  => 'Add Row',
                    'rows_per_page' => 20,
                    'sub_fields'    => [
                        ['key'=>'field_book_title','label'=>'Title','name'=>'title','type'=>'text'],
                        ['key'=>'field_book_cover','label'=>'Cover','name'=>'cover','type'=>'image','return_format'=>'array','preview_size'=>'medium'],
                    ],
                ],

                // Personal Information group
                [
                    'key'          => 'field_personal_info',
                    'label'        => 'Personal Information',
                    'name'         => 'personal',
                    'type'         => 'group',
                    'layout'       => 'block',
                    'sub_fields'   => [
                        // Location Born
                        [
                            'key'          => 'field_location_born',
                            'label'        => 'Location Born',
                            'name'         => 'location_born',
                            'type'         => 'group',
                            'layout'       => 'block',
                            'sub_fields'   => [
                                ['key'=>'field_location_name_born','label'=>'Name','name'=>'name','type'=>'text'],
                                ['key'=>'field_location_wikipedia_born','label'=>'Wikipedia URL','name'=>'wikipedia_url','type'=>'text'],
                            ],
                        ],

                        // Current Residence
                        [
                            'key'          => 'field_current_residence',
                            'label'        => 'Current Residence',
                            'name'         => 'current_residence',
                            'type'         => 'group',
                            'layout'       => 'block',
                            'sub_fields'   => [
                                ['key'=>'field_residence_name','label'=>'Name','name'=>'name','type'=>'text'],
                                ['key'=>'field_residence_wikipedia','label'=>'Wikipedia URL','name'=>'wikipedia_url','type'=>'text'],
                            ],
                        ],

                        ['key'=>'field_marital_status','label'=>'Marital Status','name'=>'marital_status','type'=>'select','choices'=>['single'=>'Single','married'=>'Married','divorced'=>'Divorced','other'=>'Other']],
                        ['key'=>'field_children','label'=>'Children','name'=>'children','type'=>'text','instructions'=>'Leave empty to hide from profile.'],

                        // Education repeater
                        [
                            'key'          => 'field_education',
                            'label'        => 'Education',
                            'name'         => 'education',
                            'type'         => 'repeater',
                            'layout'       => 'table',
                            'button_label' => 'Add Row',
                            'rows_per_page'=> 20,
                            'sub_fields'   => [
                                ['key'=>'field_school','label'=>'School','name'=>'school','type'=>'text','instructions'=>'School name (ex: University of Miami)'],
                                ['key'=>'field_degree','label'=>'Degree','name'=>'degree','type'=>'text','instructions'=>'Degree type (ex: BS)'],
                                ['key'=>'field_education_wikipedia','label'=>'Wikipedia URL','name'=>'wikipedia_url','type'=>'text','instructions'=>'Wikipedia url of the school'],
                            ],
                        ],

                        // **Updated Gender field – allow_null => 1, no default_value**
                        [
                            'key'           => 'field_gender',
                            'label'         => 'Gender',
                            'name'          => 'gender',
                            'type'          => 'select',
                            'choices'       => [
                                'female' => 'Female',
                                'male'   => 'Male',
                                'other'  => 'Other',
                                'na'     => 'Do Not Show Publicly',
                            ],
                            'allow_null'    => 1,          // <-- This lets you save with no selection
                            // 'default_value' => '',     // <-- Make sure there's no default here
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    ['param'=>'post_type','operator'=>'==','value'=>$slug],
                    ['param'=>'post_category','operator'=>'==','value'=>'category:person']
                ]
            ],
        ],
    ];

    foreach ( $groups as $grp ) {
        acf_add_local_field_group( $grp );
    }
}



function register_verified_profile_pages_custom_fields() {
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
