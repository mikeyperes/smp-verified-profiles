<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\ProfileTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Profile types and the article integration.
 *
 * The built-in `profile` post type is always the default profile type. Site
 * owners add more profile types (for example Podcasters), each its own post
 * type with the standard Verified Profile fields and an optional preset field
 * group. The integration (profile associations, spawning, "featured in" and
 * the in-article profile section) runs on the post types chosen here.
 */
final class ProfileTypes {
    public const DEFAULT_TYPE       = 'profile';
    public const TYPES_OPTION       = 'smp_vp_profile_types';
    public const INTEGRATION_OPTION = 'smp_vp_integration_post_types';

    /** The integration's post types before anyone chooses: the ones it always used. */
    public const DEFAULT_INTEGRATION = [ 'post', 'press-release' ];

    private const RESERVED_KEYS = [
        'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset',
        'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles',
        'wp_navigation', 'wp_font_family', 'wp_font_face', 'profile', 'entity', 'action', 'author', 'order',
        'theme', 'elementor_library', 'product', 'press-release',
    ];

    /**
     * Saved custom profile types, keyed by post-type key.
     *
     * @return array<string,array{key:string,singular:string,plural:string,rewrite_slug:string,preset:string,standard_fields:bool}>
     */
    public static function custom(): array {
        $saved = get_option( self::TYPES_OPTION, [] );
        $types = [];
        foreach ( is_array( $saved ) ? $saved : [] as $type ) {
            $type = is_array( $type ) ? self::normalize( $type ) : null;
            if ( null !== $type ) {
                $types[ $type['key'] ] = $type;
            }
        }

        return $types;
    }

    /** @return list<string> Every configured profile type key, registered or not. */
    public static function keys(): array {
        return array_values( array_unique( array_merge( [ self::DEFAULT_TYPE ], array_keys( self::custom() ) ) ) );
    }

    /** @return list<string> Profile types that carry the standard Verified Profile fields and page. */
    public static function standard_field_keys(): array {
        $keys = [ self::DEFAULT_TYPE ];
        foreach ( self::custom() as $key => $type ) {
            if ( $type['standard_fields'] ) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /** @return list<string> Profile types registered on this request. */
    public static function post_types(): array {
        $keys = self::keys();
        if ( did_action( 'init' ) ) {
            $keys = array_values( array_filter( $keys, 'post_type_exists' ) );
        }

        return (array) apply_filters( 'smp_vp_profile_post_types', $keys );
    }

    public static function is_profile_type( string $post_type ): bool {
        return '' !== $post_type && in_array( $post_type, self::keys(), true );
    }

    /** @return list<string> Post types the article integration runs on. */
    public static function integration_post_types(): array {
        $saved = get_option( self::INTEGRATION_OPTION, null );
        $types = is_array( $saved ) ? array_map( 'sanitize_key', $saved ) : self::DEFAULT_INTEGRATION;
        // Not narrowed to registered post types: field locations are built during
        // init, before plugins that register late (press releases) have run.
        $types = array_values( array_diff( array_filter( array_unique( $types ) ), self::keys() ) );

        return (array) apply_filters( 'smp_vp_integration_post_types', $types );
    }

    public static function is_integration_type( string $post_type ): bool {
        return '' !== $post_type && in_array( $post_type, self::integration_post_types(), true );
    }

    /**
     * Post types a site owner may run the integration on.
     *
     * @return array<string,string> key => label
     */
    public static function integration_choices(): array {
        $choices = [];
        foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $key => $object ) {
            if ( in_array( $key, [ 'attachment', 'elementor_library', 'e-floating-buttons', 'e-landing-page' ], true ) || self::is_profile_type( (string) $key ) ) {
                continue;
            }
            $choices[ $key ] = (string) ( $object->labels->name ?? $key );
        }

        return $choices;
    }

    /**
     * Field-group presets a new profile type can start with.
     *
     * @return array<string,array{label:string,description:string}>
     */
    public static function presets(): array {
        return [
            'blank'     => [ 'label' => 'Standard fields only', 'description' => 'The standard Verified Profile fields and nothing more.' ],
            'podcaster' => [ 'label' => 'Podcaster', 'description' => 'Adds show name, role, show links (website, RSS, Apple Podcasts, Spotify, YouTube), topics, episode count and booking email.' ],
        ];
    }

    /**
     * Content-type definitions for Core's registry, one per custom type.
     *
     * @return list<array<string,mixed>>
     */
    public static function content_type_definitions( array $profile_args ): array {
        $definitions = [];
        foreach ( self::custom() as $type ) {
            $field_groups = [];
            if ( 'podcaster' === $type['preset'] ) {
                $field_groups[] = [
                    'id'          => 'podcaster-fields',
                    'label'       => 'Podcaster Fields',
                    'description' => 'Show, role and listening links for this ' . $type['singular'] . '.',
                    'group_key'   => self::group_key( $type['key'], 'podcaster' ),
                    'definition'  => self::podcaster_group( $type ),
                    'fields'      => [ 'Show name', 'Role', 'Show links', 'Topics', 'Episode count', 'Booking email' ],
                ];
            }

            $definitions[] = [
                'id'              => $type['key'],
                'owner'           => 'SMP Verified Profiles',
                'description'     => $type['plural'] . ': a Verified Profile type added on the Profile Types tab.',
                'enabled_default' => true,
                'post_type'       => [
                    'key'          => $type['key'],
                    'singular'     => $type['singular'],
                    'plural'       => $type['plural'],
                    'rewrite_slug' => $type['rewrite_slug'],
                    'args'         => $profile_args,
                ],
                'field_groups'    => $field_groups,
            ];
        }

        return $definitions;
    }

    /**
     * Add a custom profile type.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed> The saved type.
     */
    public static function add( array $input ): array {
        $singular = sanitize_text_field( (string) ( $input['singular'] ?? '' ) );
        if ( '' === $singular ) {
            throw new \InvalidArgumentException( 'Enter a name for the profile type.' );
        }

        $key = sanitize_key( str_replace( '-', '_', (string) ( $input['key'] ?? '' ) ) );
        if ( '' === $key ) {
            $key = sanitize_key( str_replace( [ '-', ' ' ], '_', strtolower( remove_accents( $singular ) ) ) );
        }
        if ( '' === $key || strlen( $key ) > 20 ) {
            throw new \InvalidArgumentException( 'The internal name must be 1 to 20 letters, numbers or underscores.' );
        }
        if ( in_array( $key, self::RESERVED_KEYS, true ) || isset( self::custom()[ $key ] ) || post_type_exists( $key ) ) {
            throw new \InvalidArgumentException( 'A post type named "' . $key . '" already exists. Choose another internal name.' );
        }

        $type = self::normalize(
            [
                'key'             => $key,
                'singular'        => $singular,
                'plural'          => (string) ( $input['plural'] ?? '' ),
                'rewrite_slug'    => (string) ( $input['rewrite_slug'] ?? '' ),
                'preset'          => (string) ( $input['preset'] ?? 'blank' ),
                'standard_fields' => ! empty( $input['standard_fields'] ),
            ]
        );
        if ( null === $type ) {
            throw new \InvalidArgumentException( 'The profile type could not be saved.' );
        }

        foreach ( get_post_types( [], 'objects' ) as $object ) {
            $slug = is_array( $object->rewrite ?? null ) ? (string) ( $object->rewrite['slug'] ?? '' ) : '';
            if ( '' !== $slug && $slug === $type['rewrite_slug'] ) {
                throw new \InvalidArgumentException( 'The web address /' . $type['rewrite_slug'] . '/ is already used by ' . $object->labels->name . '.' );
            }
        }

        $types         = self::custom();
        $types[ $key ] = $type;
        update_option( self::TYPES_OPTION, array_values( $types ), true );

        return $type;
    }

    /** Remove a custom profile type. Its posts stay in the database untouched. */
    public static function remove( string $key ): bool {
        $types = self::custom();
        if ( ! isset( $types[ $key ] ) ) {
            return false;
        }
        unset( $types[ $key ] );
        update_option( self::TYPES_OPTION, array_values( $types ), true );

        return true;
    }

    /** @param list<string> $post_types */
    public static function save_integration( array $post_types ): array {
        $allowed = array_keys( self::integration_choices() );
        $clean   = array_values( array_intersect( array_unique( array_map( 'sanitize_key', $post_types ) ), $allowed ) );
        update_option( self::INTEGRATION_OPTION, $clean, true );

        return $clean;
    }

    /** @param array<string,mixed> $type */
    private static function normalize( array $type ): ?array {
        $key = sanitize_key( (string) ( $type['key'] ?? '' ) );
        if ( '' === $key || strlen( $key ) > 20 || self::DEFAULT_TYPE === $key ) {
            return null;
        }
        $singular = sanitize_text_field( (string) ( $type['singular'] ?? '' ) ) ?: ucwords( str_replace( '_', ' ', $key ) );
        $plural   = sanitize_text_field( (string) ( $type['plural'] ?? '' ) ) ?: $singular . 's';
        $slug     = sanitize_title( (string) ( $type['rewrite_slug'] ?? '' ) ) ?: sanitize_title( $plural );
        $preset   = sanitize_key( (string) ( $type['preset'] ?? 'blank' ) );

        return [
            'key'             => $key,
            'singular'        => $singular,
            'plural'          => $plural,
            'rewrite_slug'    => $slug,
            'preset'          => isset( self::presets()[ $preset ] ) ? $preset : 'blank',
            'standard_fields' => ! array_key_exists( 'standard_fields', $type ) || (bool) $type['standard_fields'],
        ];
    }

    private static function group_key( string $type_key, string $preset ): string {
        return 'group_smp_vp_' . $type_key . '_' . $preset;
    }

    /** @param array<string,mixed> $type @return array<string,mixed> */
    private static function podcaster_group( array $type ): array {
        $prefix = 'field_smp_vp_' . $type['key'] . '_';
        $field  = static fn( string $name, string $label, string $kind, array $extra = [] ): array => array_merge(
            [
                'key'          => $prefix . $name,
                'label'        => $label,
                'name'         => $name,
                'type'         => $kind,
                'instructions' => 'Shortcode: [verified_profile field="' . $name . '"].',
                'required'     => 0,
            ],
            $extra
        );

        return [
            'key'        => self::group_key( $type['key'], 'podcaster' ),
            'title'      => $type['singular'] . ' - Podcaster',
            'fields'     => [
                $field( 'podcast_name', 'Show name', 'text' ),
                $field(
                    'podcast_role',
                    'Role',
                    'select',
                    [
                        'choices'       => [ 'host' => 'Host', 'co_host' => 'Co-host', 'guest' => 'Guest', 'producer' => 'Producer' ],
                        'default_value' => 'host',
                        'return_format' => 'value',
                    ]
                ),
                $field( 'podcast_description', 'Show description', 'textarea', [ 'rows' => 4 ] ),
                $field( 'podcast_website', 'Show website', 'url' ),
                $field( 'podcast_rss', 'RSS feed', 'url' ),
                $field( 'podcast_apple', 'Apple Podcasts', 'url' ),
                $field( 'podcast_spotify', 'Spotify', 'url' ),
                $field( 'podcast_youtube', 'YouTube', 'url' ),
                $field( 'podcast_topics', 'Topics', 'text', [ 'instructions' => 'Comma separated. Shortcode: [verified_profile field="podcast_topics"].' ] ),
                $field( 'podcast_episode_count', 'Episode count', 'number', [ 'min' => 0 ] ),
                $field( 'podcast_booking_email', 'Booking email', 'email' ),
            ],
            'location'   => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => '@post_type' ] ] ],
            'menu_order' => 1,
            'position'   => 'normal',
            'active'     => true,
        ];
    }
}
