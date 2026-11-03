<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Pages;

defined( 'ABSPATH' ) || exit;

/** Canonical catalog. Layouts and sections are not ordinary WordPress pages. */
final class PageTypes {
    public static function definitions(): array {
        $profile = get_post_type_object( 'profile' );
        $slug = trim( (string) ( $profile->rewrite['slug'] ?? 'profile' ), '/' );
        $group = \Hexa\PluginCore\Fields\Field::get( 'verified_profile', 'option' );
        $group = is_array( $group ) ? $group : [];
        $pages = [
            'profiles_archive' => [ 'title' => $profile->labels->name ?? 'Verified Profiles', 'slug' => $slug, 'kind' => 'layout', 'content_source' => 'Verified Profiles directory with search and filters', 'title_source' => 'Verified Profiles content-type settings', 'slug_source' => 'Verified Profiles content-type settings' ],
            'single_profile' => [ 'title' => 'Single profile', 'slug' => '<profile>', 'parent' => 'profiles_archive', 'kind' => 'profile', 'content_source' => 'Verified profile post and display settings' ],
            'homepage_profiles' => [ 'title' => 'Homepage profiles section', 'slug' => '', 'kind' => 'section', 'content_source' => '[display_homepage_profiles]' ],
        ];
        foreach ( [ 'badges' => 'Badges', 'claim' => 'Claim', 'apply' => 'Apply', 'welcome' => 'Welcome' ] as $key => $title ) {
            $field = 'verified_profiles_' . $key;
            $value = $group['pages'][$field] ?? 0;
            $id = is_object( $value ) ? (int) $value->ID : ( is_array( $value ) ? (int) ( $value['ID'] ?? 0 ) : (int) $value );
            $url = $id ? (string) get_permalink( $id ) : '';
            $pages[$key] = [ 'title' => $title, 'slug' => $url ? trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) : '', 'url' => $url, 'field' => 'page_' . $field, 'slug_source' => 'verified_profile.pages.' . $field, 'content_source' => 'verified_profile.pages.' . $field ];
        }
        if ( \SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::guest_type() !== '' ) {
            $voices_slug = trim( (string) get_option( 'smp_vp_voices_slug', 'voices' ), '/' );
            $voices_id = (int) get_option( 'smp_vp_page_voices', get_option( 'smp_podcast_page_voices', 0 ) );
            $voices = $voices_id ? get_post( $voices_id ) : get_page_by_path( $voices_slug );
            if ( $voices ) {
                $voices_slug = trim( (string) wp_parse_url( get_permalink( $voices ), PHP_URL_PATH ), '/' );
            }
            $guest = get_post_type_object( \SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::GUEST_TYPE );
            $guest_slug = trim( (string) ( $guest->rewrite['slug'] ?? 'guest' ), '/' );
            $pages['voices'] = [ 'title' => $voices ? $voices->post_title : 'Voices', 'slug' => $voices_slug, 'slugs' => [ $guest_slug ], 'kind' => 'layout', 'conditions' => [ 'smp_vp_content_type_settings.podcast-guest.enabled' ], 'content_source' => 'Podcast guest directory with search and filters', 'slug_source' => 'smp_vp_page_voices, legacy smp_podcast_page_voices, or smp_vp_voices_slug', 'aliases' => [ 'guests' ], 'url' => $voices ? get_permalink( $voices ) : get_post_type_archive_link( \SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::GUEST_TYPE ) ];
            $pages['guest_profile'] = [ 'title' => 'Single guest', 'slug' => '/' . $guest_slug . '/<name>/', 'parent' => 'voices', 'kind' => 'profile', 'conditions' => [ 'smp_vp_content_type_settings.podcast-guest.enabled' ], 'content_source' => 'Verified Profiles podcast guest record and related episodes' ];
        }
        foreach ( $pages as &$page ) {
            $page += [ 'slugs' => [], 'parent' => '', 'design' => 'custom', 'enabled_by_default' => false, 'conditions' => [], 'kind' => 'page', 'content_source' => $page['field'] ?? '' ];
        }
        unset( $page );
        return $pages;
    }

    public static function pages(): array {
        return array_filter( self::definitions(), static fn( array $page ): bool => 'page' === $page['kind'] );
    }
}
