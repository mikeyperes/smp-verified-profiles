<?php

namespace smp_verified_profiles\ContentTypes;

use Hexa\PluginCore\ContentTypes\ContentTypeRegistry;
use Hexa\PluginCore\FieldStructures\AcfFieldGroupRegistry;

defined( 'ABSPATH' ) || exit;

final class VerifiedProfileStructures {
    private static ?ContentTypeRegistry $content_types = null;
    private static ?AcfFieldGroupRegistry $acf_groups = null;

    /** @var array<string,array<string,array<string,mixed>>> */
    private static array $legacy_groups = [];

    private static bool $collecting = false;

    /** @var array<string,array<string,mixed>> */
    private static array $collection = [];

    public static function boot(): void {
        require_once dirname( __DIR__, 2 ) . '/register-acf-structure-theme-options.php';
        require_once dirname( __DIR__, 2 ) . '/register-acf-structures.php';
        require_once dirname( __DIR__, 2 ) . '/register-acf-user-profile.php';
        require_once dirname( __DIR__, 2 ) . '/register-acf-verified-profile.php';

        add_action( 'init', [ self::class, 'migrate_legacy_content_type_settings' ], 3 );
        add_action( 'init', [ self::class, 'migrate_legacy_acf_settings' ], 3 );
        self::content_types()->register();
        self::acf_groups()->register();
    }

    public static function content_types(): ContentTypeRegistry {
        if ( self::$content_types instanceof ContentTypeRegistry ) {
            return self::$content_types;
        }

        self::$content_types = new ContentTypeRegistry(
            [
                'option_name'   => 'smp_vp_content_type_settings',
                'capability'    => 'manage_options',
                'ajax_action'   => 'smp_vp_save_content_type',
                'nonce_action'  => 'smp_vp_content_types',
                'nonce_field'   => 'nonce',
                'hook_priority' => 4,
            ]
        );

        self::$content_types->add(
            [
                'id'                    => 'profile',
                'owner'                 => 'SMP Verified Profiles',
                'description'           => 'Public Person and Organization profiles, profile claims, associated articles, and profile schema data.',
                'enabled_default'       => false,
                'legacy_enabled_option' => 'register_profile_custom_post_type',
                'post_type'             => [
                    'key'          => 'profile',
                    'singular'     => 'Verified Profile',
                    'plural'       => 'Verified Profiles',
                    'rewrite_slug' => 'profile',
                    'args'         => [
                        'public'             => true,
                        'publicly_queryable' => true,
                        'show_ui'            => true,
                        'show_in_menu'       => true,
                        'show_in_nav_menus'  => true,
                        'show_in_admin_bar'  => true,
                        'show_in_rest'       => true,
                        'capability_type'    => 'post',
                        'hierarchical'       => false,
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
                        'taxonomies'       => [ 'category', 'post_tag' ],
                        'has_archive'      => true,
                        'rewrite'          => [ 'with_front' => false ],
                        'query_var'        => true,
                        'delete_with_user' => false,
                    ],
                ],
            ]
        );

        return self::$content_types;
    }

    public static function acf_groups(): AcfFieldGroupRegistry {
        if ( self::$acf_groups instanceof AcfFieldGroupRegistry ) {
            return self::$acf_groups;
        }

        self::$acf_groups = new AcfFieldGroupRegistry(
            [
                'option_name'   => 'smp_vp_acf_structure_settings',
                'capability'    => 'manage_options',
                'ajax_action'   => 'smp_vp_save_acf_structure',
                'nonce_action'  => 'smp_vp_acf_structures',
                'nonce_field'   => 'nonce',
                'hook_priority' => 4,
                'after_save'    => [ self::class, 'sync_acf_legacy_option' ],
            ]
        );

        foreach ( self::acf_definitions() as $definition ) {
            self::$acf_groups->add( $definition );
        }

        return self::$acf_groups;
    }

    /** @return array<int,array<string,mixed>> */
    private static function acf_definitions(): array {
        return [
            self::acf_definition(
                'profile-fields',
                'Verified Profile Fields',
                'group_67e39e4171b16',
                'register_verified_profile_custom_fields',
                'register_verified_profile_custom_fields',
                'Profile editors',
                [ 'Profile type', 'Person details', 'Organization details', 'Biography', 'Social URLs', 'Media', 'Related profiles', 'Generated schema' ]
            ),
            self::acf_definition(
                'post-profile-associations',
                'Post Profile Associations',
                'group_66b7bdf713e77',
                'register_profile_general_acf_fields',
                'register_profile_general_acf_fields',
                'Post editors for administrators',
                [ 'Profiles mentioned in the article', 'Pending profile candidates' ]
            ),
            self::acf_definition(
                'profile-manager-user',
                'Profile Manager User Fields',
                'group_65a8b25062d91',
                'register_user_custom_fields',
                'register_user_custom_fields',
                'WordPress user profiles for profile managers and administrators',
                [ 'Notification emails', 'Profile-manager account settings' ]
            ),
            self::acf_definition(
                'verified-profile-manager-admin',
                'Verified Profile Manager Admin Fields',
                'group_658602c9eaa49',
                'register_user_custom_fields',
                'register_user_custom_fields',
                'WordPress user profiles for administrators',
                [ 'Unclaimed profiles', 'Profile ownership and manager administration' ]
            ),
            self::acf_definition(
                'email-settings',
                'Verified Profile Email Settings',
                'group_658739a0ab536',
                'register_user_custom_fields',
                'register_user_custom_fields',
                'Verified Profiles Email Settings tab',
                [ 'Welcome email', 'Profile workflow emails', 'Notification templates' ]
            ),
            self::acf_definition(
                'program-settings',
                'Verified Profile Program Settings',
                'group_6850930366d8f',
                'enable_acf_theme_options',
                'enable_acf_theme_options',
                'Verified Profiles Profile Settings tab',
                [ 'Contributor Network identity', 'Verified Profile identity', 'Elementor loop assignments', 'Required page assignments' ],
                [ self::class, 'program_settings_group' ]
            ),
            self::acf_definition(
                'settings-reference',
                'Verified Profile Settings Reference',
                'group_additional_shortcodes',
                'enable_acf_theme_options',
                'enable_acf_theme_options',
                'Verified Profiles Profile Settings tab',
                [ 'Additional shortcode reference', 'Registered ACF structure reference' ]
            ),
        ];
    }

    /**
     * @param array<int,string> $fields
     * @param callable|null     $definition_callback
     * @return array<string,mixed>
     */
    private static function acf_definition(
        string $id,
        string $label,
        string $group_key,
        string $legacy_option,
        string $legacy_function,
        string $location,
        array $fields,
        ?callable $definition_callback = null
    ): array {
        return [
            'id'              => $id,
            'label'           => $label,
            'description'     => 'Registered through the shared Hexa WP Core while retaining the plugin\'s existing field keys and stored values.',
            'group_key'       => $group_key,
            'enabled_default' => false,
            'legacy_option'   => '',
            'legacy_snippet'  => $legacy_option,
            'definition'      => $definition_callback ?? static fn(): array => self::legacy_group( $legacy_function, $group_key ),
            'location'        => $location,
            'fields'          => $fields,
            'dependencies'    => [ 'Advanced Custom Fields Pro' ],
        ];
    }

    /** @param array<string,mixed> $group */
    public static function capture_acf_group( array $group ): bool {
        if ( self::$collecting ) {
            $key = sanitize_key( (string) ( $group['key'] ?? '' ) );
            if ( '' !== $key ) {
                self::$collection[ $key ] = $group;
            }
        }

        // Once this adapter is loaded, Core is the only registration path.
        return true;
    }

    /** @return array<string,mixed> */
    public static function program_settings_group(): array {
        $group = self::legacy_group( 'enable_acf_theme_options', 'group_6850930366d8f' );
        if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
            return $group;
        }

        // CPT labels and the public URL slug now have one source of truth in the
        // Core Custom Post Types panel. Existing ACF values remain untouched and
        // are migrated once into the new settings store.
        $group['fields'] = array_values(
            array_filter(
                $group['fields'],
                static fn( mixed $field ): bool => ! is_array( $field ) || 'field_6850950000000' !== (string) ( $field['key'] ?? '' )
            )
        );

        return $group;
    }

    /** @return array<string,mixed> */
    private static function legacy_group( string $function, string $group_key ): array {
        if ( ! isset( self::$legacy_groups[ $function ] ) ) {
            self::$collecting = true;
            self::$collection = [];
            try {
                $callback = '\\smp_verified_profiles\\' . $function;
                if ( is_callable( $callback ) ) {
                    call_user_func( $callback );
                }
                self::$legacy_groups[ $function ] = self::$collection;
            } finally {
                self::$collecting = false;
                self::$collection = [];
            }
        }

        return self::$legacy_groups[ $function ][ sanitize_key( $group_key ) ] ?? [];
    }

    public static function migrate_legacy_content_type_settings(): void {
        $all = get_option( 'smp_vp_content_type_settings', [] );
        if ( is_array( $all ) && isset( $all['profile'] ) ) {
            return;
        }

        $legacy = function_exists( '\\smp_verified_profiles\\get_verified_profile_legacy_settings' )
            ? \smp_verified_profiles\get_verified_profile_legacy_settings()
            : [ 'singular' => 'Verified Profile', 'plural' => 'Verified Profiles', 'rewrite_slug' => 'profile' ];

        self::content_types()->store()->save(
            self::content_types()->definition( 'profile' ) ?? [],
            [
                'enabled'              => (bool) get_option( 'register_profile_custom_post_type', false ),
                'singular'             => (string) ( $legacy['singular'] ?? 'Verified Profile' ),
                'plural'               => (string) ( $legacy['plural'] ?? 'Verified Profiles' ),
                'rewrite_slug'         => (string) ( $legacy['rewrite_slug'] ?? 'profile' ),
                'enabled_field_groups' => [],
            ]
        );
    }

    public static function migrate_legacy_acf_settings(): void {
        $saved = get_option( 'smp_vp_acf_structure_settings', [] );
        $saved = is_array( $saved ) ? $saved : [];
        $changed = false;

        foreach ( self::acf_definitions() as $definition ) {
            $id = (string) $definition['id'];
            if ( array_key_exists( $id, $saved ) ) {
                continue;
            }
            $legacy = (string) ( $definition['legacy_snippet'] ?? '' );
            $saved[ $id ] = '' !== $legacy && (bool) get_option( $legacy, false ) ? 1 : 0;
            $changed = true;
        }

        if ( $changed ) {
            update_option( 'smp_vp_acf_structure_settings', $saved, false );
        }
    }

    public static function sync_legacy_snippet( string $snippet_id, bool $enabled ): void {
        $saved = get_option( 'smp_vp_acf_structure_settings', [] );
        $saved = is_array( $saved ) ? $saved : [];
        $matched = false;

        foreach ( self::acf_definitions() as $definition ) {
            if ( $snippet_id !== (string) ( $definition['legacy_snippet'] ?? '' ) ) {
                continue;
            }
            $saved[ (string) $definition['id'] ] = $enabled ? 1 : 0;
            $matched = true;
        }

        if ( $matched ) {
            update_option( 'smp_vp_acf_structure_settings', $saved, false );
            update_option( $snippet_id, $enabled ? 1 : 0, false );
        }
    }

    /** @param array<string,mixed> $definition */
    public static function sync_acf_legacy_option( array $definition, bool $enabled, AcfFieldGroupRegistry $registry ): void {
        unset( $enabled );
        $legacy = (string) ( $definition['legacy_snippet'] ?? '' );
        if ( '' === $legacy ) {
            return;
        }

        $any_enabled = false;
        foreach ( $registry->resolved_definitions() as $candidate ) {
            if ( $legacy === (string) ( $candidate['legacy_snippet'] ?? '' ) && ! empty( $candidate['enabled'] ) ) {
                $any_enabled = true;
                break;
            }
        }
        update_option( $legacy, $any_enabled ? 1 : 0, false );
    }
}
