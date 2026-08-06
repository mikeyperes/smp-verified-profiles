<?php

namespace SMP\VerifiedProfiles\EntitySources;

use Hexa\PluginCore\EntitySources\CanonicalEntityResolver;

defined( 'ABSPATH' ) || exit;

final class CanonicalProfileSource {
    /** @return array<string,mixed>|null */
    public static function entity(): ?array {
        if ( ! class_exists( CanonicalEntityResolver::class ) ) {
            return null;
        }

        return CanonicalEntityResolver::resolve();
    }

    public static function profile_id(): int {
        $entity = self::entity();
        if ( ! is_array( $entity ) ) {
            return 0;
        }

        if ( 'post' === ( $entity['kind'] ?? '' ) && 'profile' === ( $entity['post_type'] ?? '' ) ) {
            return (int) ( $entity['id'] ?? 0 );
        }

        $user_id = 'user' === ( $entity['kind'] ?? '' )
            ? (int) ( $entity['id'] ?? 0 )
            : (int) ( $entity['attached_user_id'] ?? 0 );
        if ( $user_id <= 0 ) {
            return 0;
        }

        $ids = get_posts(
            [
                'post_type'              => 'profile',
                'post_status'            => [ 'publish', 'draft', 'pending', 'private' ],
                'author'                 => $user_id,
                'posts_per_page'         => 1,
                'orderby'                => 'modified',
                'order'                  => 'DESC',
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]
        );

        return isset( $ids[0] ) ? (int) $ids[0] : 0;
    }
}

if ( ! class_exists( '\\smp_verified_profiles\\EntitySources\\CanonicalProfileSource', false ) ) {
    class_alias( CanonicalProfileSource::class, '\\smp_verified_profiles\\EntitySources\\CanonicalProfileSource' );
}
