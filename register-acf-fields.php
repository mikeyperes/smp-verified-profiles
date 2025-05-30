<?php namespace smp_verified_profiles;
/*
register_acf_fields_general()
register_acf_post_type_profile()
*/

//DEELTE THIS FILE

function register_acf_post_type_profile()
{return;
    add_action('init', __NAMESPACE__ . '\\register_verified_profile_cpt');
    function register_verified_profile_cpt() {
        // pull ACF options (fallback to defaults)
        $singular = function_exists('get_field')
            ? get_field('verified_profile_name_singular','option') ?: 'Verified Profile'
            : 'Verified Profile';
        $plural = function_exists('get_field')
            ? get_field('verified_profile_name_plural','option') ?: 'Verified Profiles'
            : 'Verified Profiles';
        $slug = function_exists('get_field')
            ? get_field('verified_profile_slug','option') ?: 'profile'
            : 'profile';
    
        $labels = array(
            'name'                  => $plural,
            'singular_name'         => $singular,
            'menu_name'             => $plural,
            'all_items'             => "All $plural",
            'edit_item'             => "Edit $singular",
            'view_item'             => "View $singular",
            'view_items'            => "View $plural",
            'add_new_item'          => "Add New $singular",
            'add_new'               => "Add New $singular",
            'new_item'              => "New $singular",
            'parent_item_colon'     => "Parent $singular:",
            'search_items'          => "Search $plural",
            'not_found'             => "No $plural found",
            'not_found_in_trash'    => "No $plural found in Trash",
            'archives'              => "$singular Archives",
            'attributes'            => "$singular Attributes",
            'insert_into_item'      => "Insert into $singular",
            'uploaded_to_this_item' => "Uploaded to this $singular",
            'filter_items_list'     => "Filter $plural list",
            'filter_by_date'        => "Filter $plural by date",
            'items_list_navigation' => "$plural list navigation",
            'items_list'            => "$plural list",
            'item_published'        => "$singular published.",
            'item_published_privately'=> "$singular published privately.",
            'item_reverted_to_draft'=> "$singular reverted to draft.",
            'item_scheduled'        => "$singular scheduled.",
            'item_updated'          => "$singular updated.",
            'item_link'             => "$singular Link",
            'item_link_description' => "A link to a $singular.",
        );
    
        register_post_type($slug, array(
            'labels'             => $labels,
            'public'             => true,
            'show_in_rest'       => true,
            'rewrite'            => array('slug'=>$slug),
            'supports'           => array('title','author','trackbacks','editor','excerpt','revisions','page-attributes','thumbnail','custom-fields','post-formats'),
            'taxonomies'         => array('category','post_tag'),
            'delete_with_user'   => false,
        ));
    }
    
	register_post_type( 'Xprofile', array(
	'labels' => array(
		'name' => 'Verified Profiles',
		'singular_name' => 'Profile',
		'menu_name' => 'Verified Profiles',
		'all_items' => 'All Verified Profiles',
		'edit_item' => 'Edit Profile',
		'view_item' => 'View Profile',
		'view_items' => 'View Verified Profiles',
		'add_new_item' => 'Add New Profile',
		'add_new' => 'Add New Profile',
		'new_item' => 'New Profile',
		'parent_item_colon' => 'Parent Profile:',
		'search_items' => 'Search Verified Profiles',
		'not_found' => 'No verified profiles found',
		'not_found_in_trash' => 'No verified profiles found in Trash',
		'archives' => 'Profile Archives',
		'attributes' => 'Profile Attributes',
		'insert_into_item' => 'Insert into profile',
		'uploaded_to_this_item' => 'Uploaded to this profile',
		'filter_items_list' => 'Filter verified profiles list',
		'filter_by_date' => 'Filter verified profiles by date',
		'items_list_navigation' => 'Verified Profiles list navigation',
		'items_list' => 'Verified Profiles list',
		'item_published' => 'Profile published.',
		'item_published_privately' => 'Profile published privately.',
		'item_reverted_to_draft' => 'Profile reverted to draft.',
		'item_scheduled' => 'Profile scheduled.',
		'item_updated' => 'Profile updated.',
		'item_link' => 'Profile Link',
		'item_link_description' => 'A link to a profile.',
	),
	'public' => true,
	'show_in_rest' => true,
	'supports' => array(
		0 => 'title',
		1 => 'author',
		2 => 'trackbacks',
		3 => 'editor',
		4 => 'excerpt',
		5 => 'revisions',
		6 => 'page-attributes',
		7 => 'thumbnail',
		8 => 'custom-fields',
		9 => 'post-formats',
	),
	'taxonomies' => array(
		0 => 'category',
		1 => 'post_tag',
	),
	'delete_with_user' => false,
) );
}
















function register_acf_fields_general() {
    $settings = get_verified_profile_settings();
    $singular = $settings['singular'];
    $plural   = $settings['plural'];
    $slug     = $settings['slug'];
    $domain   = parse_url(home_url(), PHP_URL_HOST);

    // Define each group in an array for iteration
    $groups = [
        // 1) Post - Verified Profile - Admin
        [
            'key'       => 'group_post_' . $slug . '_admin',
            'title'     => "Post - {$singular} - Admin",
            'fields'    => [
                [
                    'key'           => 'field_' . $slug . '_profiles',
                    'label'         => $plural,
                    'name'          => 'profiles',
                    'type'          => 'repeater',
                    'layout'        => 'row',
                    'button_label'  => 'Add Row',
                    'rows_per_page' => 20,
                    'sub_fields'    => [
                        [
                            'key'           => 'field_' . $slug . '_profile',
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
                    'key'           => 'field_' . $slug . '_pending_profiles',
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
            'location'  => [
                [['param'=>'post_type','operator'=>'==','value'=>'post']],
                [['param'=>'current_user_role','operator'=>'==','value'=>'administrator']],
            ],
        ],
        // 2) Profile - Admin
        [
            'key'       => 'group_' . $slug . '_admin',
            'title'     => "{$singular} - Admin",
            'fields'    => [
                ['key'=>'field_contributor_profile','label'=>'Contributor Profile','name'=>'contributor_profile','type'=>'user','return_format'=>'id'],
                ['key'=>'field_schema_markup','label'=>'Schema Markup','name'=>'schema_markup','type'=>'textarea'],
                ['key'=>'field_post_id','label'=>'Post ID','name'=>'post_id','type'=>'text'],
                ['key'=>'field_google_knowledge_graph_id','label'=>'Google Knowledge Graph ID','name'=>'google_knowledge_graph_id','type'=>'text'],
                ['key'=>'field_is_council_member','label'=>'Is Council Member','name'=>'is_council_member','type'=>'true_false'],
                ['key'=>'field_is_contributor','label'=>'Is Contributor','name'=>'is_contributor','type'=>'true_false'],
                ['key'=>'field_external_id','label'=>'External ID','name'=>'external_id','type'=>'text'],
            ],
            'location'  => [[['param'=>'post_type','operator'=>'==','value'=>$slug],['param'=>'current_user_role','operator'=>'==','value'=>'administrator']]],
        ],
        // 3) Profile - Person - Public
        [
            'key'       => 'group_' . $slug . '_person_public',
            'title'     => "{$singular} - Person - Public",
            'fields'    => [
                [
                    'key'           => 'field_organizations_founded',
                    'label'         => 'Organizations Founded',
                    'name'          => 'organizations_founded',
                    'type'          => 'repeater',
                    'instructions'  => sprintf('Select a %s. Contact <a href="mailto:verified@%1$s">verified@%1$s</a> if you don\'t see your organization.', $domain),
                    'layout'        => 'table',
                    'button_label'  => 'Add Row',
                    'rows_per_page' => 20,
                    'sub_fields'    => [
                        ['key'=>'field_organization','label'=>'Organization','name'=>'organization','type'=>'post_object','post_type'=>[$slug],'post_status'=>['publish'],'return_format'=>'object','ui'=>1],
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
                ['key'=>'field_personal_info','label'=>'Personal Information','name'=>'personal','type'=>'group','layout'=>'block','sub_fields'=>[
                    ['key'=>'field_location_born','label'=>'Location Born','name'=>'location_born','type'=>'group','layout'=>'block','sub_fields'=>[
                        ['key'=>'field_location_name_born','label'=>'Name','name'=>'name','type'=>'text'],
                        ['key'=>'field_location_wikipedia_born','label'=>'Wikipedia URL','name'=>'wikipedia_url','type'=>'text'],
                    ]],
                    ['key'=>'field_current_residence','label'=>'Current Residence','name'=>'current_residence','type'=>'group','layout'=>'block','sub_fields'=>[
                        ['key'=>'field_residence_name','label'=>'Name','name'=>'name','type'=>'text'],
                        ['key'=>'field_residence_wikipedia','label'=>'Wikipedia URL','name'=>'wikipedia_url','type'=>'text'],
                    ]],
                    ['key'=>'field_marital_status','label'=>'Marital Status','name'=>'marital_status','type'=>'select','choices'=>['single'=>'Single','married'=>'Married','divorced'=>'Divorced','other'=>'Other']],
                    ['key'=>'field_children','label'=>'Children','name'=>'children','type'=>'text','instructions'=>'Leave empty to hide from profile.'],
                    ['key'=>'field_education','label'=>'Education','name'=>'education','type'=>'repeater','layout'=>'table','button_label'=>'Add Row','rows_per_page'=>20,'sub_fields'=>[
                        ['key'=>'field_school','label'=>'School','name'=>'school','type'=>'text','instructions'=>'School name (ex: University of Miami)'],
                        ['key'=>'field_degree','label'=>'Degree','name'=>'degree','type'=>'text','instructions'=>'Degree type (ex: BS)'],
                        ['key'=>'field_education_wikipedia','label'=>'Wikipedia URL','name'=>'wikipedia_url','type'=>'text','instructions'=>'Wikipedia url of the school (ex: https://en.wikipedia.org/wiki/University_of_Miami)'],
                    ]],
                    ['key'=>'field_gender','label'=>'Gender','name'=>'gender','type'=>'select','choices'=>['female'=>'Female','male'=>'Male','other'=>'Other','na'=>'Do Not Show Publicly']],
                ]],
            ],
            'location'  => [[['param'=>'post_type','operator'=>'==','value'=>$slug],['param'=>'post_category','operator'=>'==','value'=>'category:person']]],
        ],
        // 4) User - Profile Manager
        [
            'key'       => 'group_user_profile_manager',
            'title'     => 'User - Profile Manager',
            'fields'    => [
                ['key'=>'field_notification_emails','label'=>'Notification Emails','name'=>'notification_emails','type'=>'repeater','layout'=>'table','button_label'=>'Add Row','rows_per_page'=>20,'sub_fields'=>[
                    ['key'=>'field_notification_email','label'=>'Email','name'=>'email','type'=>'text'],
                ]],
            ],
            'location'  => [[['param'=>'current_user_role','operator'=>'==','value'=>'verified_profile_manager'],['param'=>'user_form','operator'=>'==','value'=>'all']],[['param'=>'current_user_role','operator'=>'==','value'=>'administrator'],['param'=>'user_form','operator'=>'==','value'=>'all']]],
        ],
        // 5) User - Verified Profile Manager - Admin
        [
            'key'       => 'group_user_verified_profile_manager_admin',
            'title'     => 'User - Verified Profile Manager - Admin',
            'fields'    => [
                ['key'=>'field_unclaimed_profiles','label'=>'Unclaimed '.$plural,'name'=>'unclaimed_profiles','type'=>'repeater','layout'=>'table','button_label'=>'Add Row','rows_per_page'=>20,'sub_fields'=>[
                    ['key'=>'field_unclaimed_profile','label'=>$singular,'name'=>'profile','type'=>'post_object','post_type'=>[$slug],'return_format'=>'id','ui'=>1],
                ]],
                ['key'=>'field_new_entity_email','label'=>'New Entity Email','name'=>'new_entity_email','type'=>'group','instructions'=>'{first_name}<br />{list_unclaimed_profiles}<br />{featured_profile}<br />{featured_profile_name}<br />{featured_profile_link}<br />{credentials_dashboard}<br />','sub_fields'=>[
                    ['key'=>'field_new_entity_subject','label'=>'Subject','name'=>'subject','type'=>'text'],
                    ['key'=>'field_new_entity_message','label'=>'Message','name'=>'message','type'=>'wysiwyg','tabs'=>'all','toolbar'=>'full','media_upload'=>1],
                ]],
                ['key'=>'field_entity_summary_email','label'=>'Entity Summary Email','name'=>'entity_summary_email','type'=>'group','sub_fields'=>[
                    ['key'=>'field_entity_summary_subject','label'=>'Subject','name'=>'subject','type'=>'text'],
                    ['key'=>'field_entity_summary_message','label'=>'Message','name'=>'message','type'=>'wysiwyg','tabs'=>'all','toolbar'=>'full','media_upload'=>1],
                ]],
                ['key'=>'field_welcome_email','label'=>'Welcome Email','name'=>'welcome_email','type'=>'group','sub_fields'=>[
                    ['key'=>'field_welcome_subject','label'=>'Subject','name'=>'subject','type'=>'text'],
                    ['key'=>'field_welcome_message','label'=>'Message','name'=>'message','type'=>'wysiwyg','tabs'=>'all','toolbar'=>'full','media_upload'=>1],
                ]],
                ['key'=>'field_password','label'=>'Password','name'=>'password','type'=>'text'],
                ['key'=>'field_price_verified_profile','label'=>'Price Verified Profile','name'=>'price_verified_profile','type'=>'text','default_value'=>'19.99'],
                ['key'=>'field_price_leadership_council','label'=>'Price Leadership Council','name'=>'price_leadership_council','type'=>'text'],
                ['key'=>'field_is_council_member2','label'=>'Is Council Member','name'=>'is_council_member','type'=>'true_false'],
                ['key'=>'field_is_contributor2','label'=>'Is Contributor','name'=>'is_contributor','type'=>'true_false'],
            ],
            'location'  => [[['param'=>'current_user_role','operator'=>'==','value'=>'administrator'],['param'=>'user_role','operator'=>'==','value'=>'all']]],
        ],
    ];

    // Register each group
    foreach ($groups as $grp) {
        acf_add_local_field_group($grp);
    }
}
