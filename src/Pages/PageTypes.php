<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Pages;

defined( 'ABSPATH' ) || exit;

/** Canonical catalog. Layouts and sections are not ordinary WordPress pages. */
final class PageTypes {
    public static function definitions(): array {
        $pages = [
            'badges' => [ 'title' => 'Badges', 'slug' => 'badges', 'field' => 'page_verified_profiles_badges' ],
            'claim' => [ 'title' => 'Claim', 'slug' => 'claim', 'field' => 'page_verified_profiles_claim' ],
            'apply' => [ 'title' => 'Apply', 'slug' => 'apply', 'field' => 'page_verified_profiles_apply' ],
            'welcome' => [ 'title' => 'Welcome', 'slug' => 'welcome', 'field' => 'page_verified_profiles_welcome' ],
            'single_profile' => [ 'title' => 'Single profile layout', 'slug' => '<profile>', 'kind' => 'layout', 'content_source' => 'Verified profile post and display settings' ],
            'profiles_archive' => [ 'title' => 'Profiles archive layout', 'slug' => 'profiles', 'template' => true, 'design' => 'text', 'content_source' => '[verified_profiles_loop id="homepage"]' ],
            'homepage_profiles' => [ 'title' => 'Homepage profiles section', 'slug' => '', 'kind' => 'section', 'content_source' => '[display_homepage_profiles]' ],
        ];
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
