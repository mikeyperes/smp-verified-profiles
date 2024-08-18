<?php
if (function_exists('acf_add_local_field_group')) {
acf_add_local_field_group(array(
    'key' => 'group_verified_profiles_settings',
    'title' => 'Verified Profiles Settings',
    'fields' => array(
        array(
            'key' => 'field_page_verified_profiles_badges',
            'label' => 'Page: Verified Profiles - Badges',
            'name' => 'page_verified_profiles_badges',
            'type' => 'post_object',
            'post_type' => array('page'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_page_verified_profiles_claim',
            'label' => 'Page: Verified Profiles - Claim',
            'name' => 'page_verified_profiles_claim',
            'type' => 'post_object',
            'post_type' => array('page'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_page_verified_profiles_apply',
            'label' => 'Page: Verified Profiles - Apply',
            'name' => 'page_verified_profiles_apply',
            'type' => 'post_object',
            'post_type' => array('page'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_page_verified_profiles_welcome',
            'label' => 'Page: Verified Profiles - Welcome',
            'name' => 'page_verified_profiles_welcome',
            'type' => 'post_object',
            'post_type' => array('page'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_single_profile',
            'label' => 'single.php - Verified Profile (profile)',
            'name' => 'single_profile',
            'type' => 'post_object',
            'post_type' => array('jet-engine'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_page_profile_archive',
            'label' => 'page.php - Verified Profile Archive (profile)',
            'name' => 'page_profile_archive',
            'type' => 'post_object',
            'post_type' => array('jet-engine'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_home_profile_archive',
            'label' => 'home.php - Verified Profile Archive (profile)',
            'name' => 'home_profile_archive',
            'type' => 'post_object',
            'post_type' => array('jet-engine'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_single_profile_words_by',
            'label' => 'single-profile.php - Words By (post)',
            'name' => 'single_profile_words_by',
            'type' => 'post_object',
            'post_type' => array('jet-engine'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_single_profile_mentioned',
            'label' => 'single-profile.php - Mentions (post)',
            'name' => 'single_profile_mentioned',
            'type' => 'post_object',
            'post_type' => array('jet-engine'),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1, // Allow empty selection
        ),
        array(
            'key' => 'field_program_name',
            'label' => 'Program Name',
            'name' => 'program_name',
            'type' => 'text',
            'ui' => 1,
        ),
    ),
    'location' => array(
        array(
            array(
                'param' => 'options_page',
                'operator' => '==',
                'value' => 'verified-profiles',
            ),
        ),
    ),
));}?>