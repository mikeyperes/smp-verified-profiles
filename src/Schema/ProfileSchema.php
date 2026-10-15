<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Schema;

use Hexa\PluginCore\DataNormalization\ValueNormalizer;
use Hexa\PluginCore\SchemaTools\SchemaIds;
use Hexa\PluginCore\SchemaTools\SchemaNodes;
use Hexa\PluginCore\SchemaTools\SchemaPage;
use Hexa\PluginCore\SchemaTools\SchemaSources;

defined( 'ABSPATH' ) || exit;

/**
 * Verified profile pages: a ProfilePage about the person or organization
 * on the profile, built when the page is viewed with HexaWP Core's schema
 * library and added to Core's shared schema block. Nothing is stored in
 * the post.
 */
final class ProfileSchema {
    public const SOURCE = 'smp-verified-profiles';
    public const PRIORITY = 10;

    /** Profile fields that hold a public profile URL for sameAs. */
    private const PROFILE_URL_FIELDS = [
        'url_facebook', 'url_twitter', 'url_x', 'url_instagram', 'url_linkedin', 'url_tiktok', 'url_wikipedia',
        'url_imdb', 'url_muckrack_url', 'url_soundcloud', 'url_amazon_author', 'url_audible', 'url_github',
        'url_crunchbase', 'url_f6s', 'url_the_org', 'url_threads', 'url_linktree', 'url_pinterest', 'url_quora',
        'url_reddit', 'url_youtube', 'url_angel_list',
    ];

    private const ORGANIZATION_ONLY_FIELDS = [
        'organization_name', 'legal_name', 'company_details', 'founding_date', 'number_of_employees', 'founder_profile', 'naics',
    ];

    public static function register(): void {
        SchemaPage::register( self::SOURCE, [ self::class, 'current_nodes' ], self::PRIORITY );
    }

    /** @return list<array<string,mixed>> */
    public static function current_nodes(): array {
        if ( ! is_singular( [ 'profile', 'entity' ] ) ) {
            return [];
        }
        $post = get_queried_object();
        return $post instanceof \WP_Post ? self::nodes( $post ) : [];
    }

    /** @return list<array<string,mixed>> */
    public static function nodes( \WP_Post $post ): array {
        if ( 'publish' !== $post->post_status && ! is_preview() ) {
            return [];
        }
        if ( 'entity' === $post->post_type ) {
            return array_filter( [ self::defined_term( $post ) ] );
        }

        $url = (string) get_permalink( $post );
        $type = self::profile_type( (int) $post->ID );
        $entity = 'organization' === $type ? self::organization( $post ) : self::person( $post );
        $image = SchemaSources::post_image( (int) $post->ID, SchemaIds::primary_image( $url ) );
        $breadcrumb = SchemaSources::post_breadcrumb( (int) $post->ID, SchemaIds::breadcrumb( $url ) );

        $page = SchemaSources::post_page(
            $post,
            'ProfilePage',
            [
                'dateCreated'        => get_post_time( 'c', true, $post ),
                'mainEntity'         => SchemaNodes::ref( (string) $entity['@id'] ),
                'about'              => SchemaNodes::ref( (string) $entity['@id'] ),
                'primaryImageOfPage' => $image ? SchemaNodes::ref( (string) $image['@id'] ) : null,
                'breadcrumb'         => $breadcrumb ? SchemaNodes::ref( (string) $breadcrumb['@id'] ) : null,
                'description'        => $entity['description'] ?? null,
            ]
        );

        return array_values( array_filter( [ $page, $entity, $image, $breadcrumb ] ) );
    }

    /**
     * Person or organization. An explicit profile type or category wins;
     * untyped profiles are a person unless organization-only fields hold data.
     */
    public static function profile_type( int $post_id ): string {
        $type = sanitize_key( (string) self::field( 'profile_type', $post_id ) );
        $type = 'company' === $type ? 'organization' : $type;
        if ( in_array( $type, [ 'person', 'organization' ], true ) ) {
            return $type;
        }
        $categories = wp_list_pluck( get_the_category( $post_id ), 'slug' );
        foreach ( [ 'person', 'organization' ] as $category ) {
            if ( in_array( $category, $categories, true ) ) {
                return $category;
            }
        }
        foreach ( self::ORGANIZATION_ONLY_FIELDS as $field ) {
            if ( ValueNormalizer::present( self::field( $field, $post_id ) ) ) {
                return 'organization';
            }
        }
        return 'person';
    }

    private static function person( \WP_Post $post ): array {
        $post_id = (int) $post->ID;
        $url = (string) get_permalink( $post );
        $bio = self::field( 'biography_short', $post_id ) ?: self::field( 'biography', $post_id );
        $alumni = self::field( 'personal_alumni_of', $post_id );
        $website = SchemaNodes::same_as( self::field( 'url_website', $post_id ) )[0] ?? '';

        return SchemaNodes::person(
            [
                '@id'          => SchemaIds::for_url( $url, 'person' ),
                'name'         => self::clean_title( $post_id ),
                'url'          => '' !== $website ? $website : $url,
                'mainEntityOfPage' => SchemaNodes::ref( SchemaIds::webpage( $url ) ),
                'image'        => SchemaSources::post_image( $post_id ),
                'description'  => ValueNormalizer::text( $bio ),
                'gender'       => ValueNormalizer::text( self::field( 'personal_gender', $post_id ) ),
                'birthPlace'   => SchemaNodes::place( [ 'name' => self::field( 'personal_birth_place', $post_id ) ] ),
                'homeLocation' => SchemaNodes::place( [ 'name' => self::field( 'personal_home_location', $post_id ) ] ),
                'alumniOf'     => SchemaNodes::thing( is_array( $alumni ) ? (string) ( $alumni['name'] ?? '' ) : (string) $alumni, '', 'Organization' ),
                'sameAs'       => self::same_as( $post_id ),
            ]
        );
    }

    private static function organization( \WP_Post $post ): array {
        $post_id = (int) $post->ID;
        $url = (string) get_permalink( $post );
        $details = self::field( 'company_details', $post_id );
        $details = is_array( $details ) ? $details : [];
        $detail = static fn( string $name ) => self::field( $name, $post_id ) ?: ( $details[ $name ] ?? '' );
        $website = (string) self::field( 'url_website', $post_id );
        if ( function_exists( 'smp_verified_profiles\\smp_vp_profile_page_resolved_url' ) ) {
            $website = \smp_verified_profiles\smp_vp_profile_page_resolved_url( $post_id, 'website', $website );
        }
        $description = self::field( 'description', $post_id ) ?: SchemaSources::post_description( $post );
        $founder = absint( $detail( 'founder_profile' ) instanceof \WP_Post ? $detail( 'founder_profile' )->ID : $detail( 'founder_profile' ) );
        $seeks = self::field( 'seeks', $post_id );

        return SchemaNodes::organization(
            [
                '@id'               => SchemaIds::for_url( $url, 'organization' ),
                'name'              => ValueNormalizer::text( $detail( 'organization_name' ) ) ?: self::clean_title( $post_id ),
                'legalName'         => ValueNormalizer::text( $detail( 'legal_name' ) ),
                'alternateName'     => ValueNormalizer::text( self::field( 'alternate_name', $post_id ) ),
                'url'               => SchemaNodes::same_as( $website )[0] ?? $url,
                'mainEntityOfPage'  => SchemaNodes::ref( SchemaIds::webpage( $url ) ),
                'description'       => ValueNormalizer::text( $description ),
                'logo'              => SchemaSources::logo( self::field( 'logo', $post_id ) ) ?: null,
                'image'             => SchemaSources::post_image( $post_id ),
                'email'             => self::field( 'email', $post_id ),
                'naics'             => ValueNormalizer::text( self::field( 'naics', $post_id ) ),
                'award'             => ValueNormalizer::text( self::field( 'award', $post_id ) ),
                'brand'             => SchemaNodes::thing( (string) self::field( 'brand', $post_id ), '', 'Brand' ),
                'foundingDate'      => ValueNormalizer::date( $detail( 'founding_date' ) ),
                'numberOfEmployees' => ValueNormalizer::number( $detail( 'number_of_employees' ) ),
                'seeks'             => is_array( $seeks ) ? array_map( [ ValueNormalizer::class, 'text' ], $seeks ) : ValueNormalizer::text( $seeks ),
                'founder'           => $founder > 0
                    ? SchemaNodes::person( [ '@id' => SchemaIds::for_url( (string) get_permalink( $founder ), 'person' ), 'name' => self::clean_title( $founder ), 'url' => get_permalink( $founder ) ] )
                    : SchemaNodes::person( [ 'name' => $detail( 'founder_name' ), 'url' => $detail( 'founder_url' ) ] ),
                'contactPoint'      => SchemaNodes::contact_point( [ 'contact_type' => self::field( 'contact_type', $post_id ), 'email' => self::field( 'contact_email', $post_id ), 'telephone' => self::field( 'contact_telephone', $post_id ) ] ),
                'address'           => SchemaNodes::postal_address(
                    [
                        'street_address'   => self::field( 'street_address', $post_id ),
                        'address_locality' => self::field( 'address_locality', $post_id ),
                        'address_region'   => self::field( 'address_region', $post_id ),
                        'postal_code'      => self::field( 'postal_code', $post_id ),
                        'address_country'  => self::field( 'address_country', $post_id ),
                    ]
                ),
                'sameAs'            => self::same_as( $post_id ),
            ]
        );
    }

    private static function defined_term( \WP_Post $post ): array {
        $url = (string) get_permalink( $post );
        return SchemaNodes::node(
            'DefinedTerm',
            [
                'url'              => $url,
                'name'             => self::clean_title( (int) $post->ID ),
                'description'      => ValueNormalizer::text( self::field( 'term_description', (int) $post->ID ) ),
                'termCode'         => ValueNormalizer::text( self::field( 'term_code', (int) $post->ID ) ),
                'inDefinedTermSet' => ValueNormalizer::text( self::field( 'term_set', (int) $post->ID ) ),
            ],
            $url
        );
    }

    /** @return list<string> */
    private static function same_as( int $post_id ): array {
        $values = [];
        foreach ( self::PROFILE_URL_FIELDS as $field ) {
            $values[] = self::field( $field, $post_id );
        }
        return SchemaNodes::same_as( ...$values );
    }

    private static function clean_title( int $post_id ): string {
        $title = html_entity_decode( (string) get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' );
        return ValueNormalizer::text( (string) preg_replace( '/\x{00A0}+/u', ' ', $title ) );
    }

    /** @return mixed */
    private static function field( string $name, int $post_id ) {
        return \Hexa\PluginCore\Fields\Field::available() ? \Hexa\PluginCore\Fields\Field::get( $name, $post_id ) : get_post_meta( $post_id, $name, true );
    }
}
