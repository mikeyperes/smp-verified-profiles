<?php
namespace SMP\VerifiedProfiles\Elementor;
use WP_Query;
use WP_Post;
use Throwable;
use Hexa\PluginCore\CoreContracts\ModuleInterface;
final class EpisodeQueries implements ModuleInterface {
public function register(): void {
 add_action('elementor/query/smp_verified_profiles_episode_hosts', static function (WP_Query $query): void { self::apply_profile_query($query, 'hosts'); });
 add_action('elementor/query/smp_verified_profiles_episode_guests', static function (WP_Query $query): void { self::apply_profile_query($query, 'profiles'); });
 add_action('elementor/query/smp_verified_profiles_profile_episodes', [self::class, 'profile_apply_episode_query']);
 }
public static function context_id(int $candidate = 0): int {
    if ($candidate > 0 && get_post_type($candidate) === 'post') {
        return $candidate;
    }

    // Prefer the active loop object when it is itself an episode. This keeps
    // related-episode Loop Items bound to their own audio/video instead of the
    // outer singular post. Profile Loop Items safely fall through because
    // their global object is not a `post`.
    $global_id = get_the_ID();
    if ($global_id && get_post_type($global_id) === 'post') {
        return (int) $global_id;
    }

    $queried_id = get_queried_object_id();
    if ($queried_id && get_post_type($queried_id) === 'post') {
        return (int) $queried_id;
    }

    // Elementor Theme Builder uses the selected preview ID in the editor.
    if (class_exists('Elementor\\Plugin')) {
        $document = \Elementor\Plugin::$instance->documents->get_current();
        if ($document && method_exists($document, 'get_settings')) {
            $preview_id = absint($document->get_settings('preview_id'));
            if ($preview_id && get_post_type($preview_id) === 'post') {
                return $preview_id;
            }
        }
    }

    return 0;
}

public static function relationship_ids(string $relationship, int $episode_id = 0): array {
    $episode_id = self::context_id($episode_id);
    if (!$episode_id || !in_array($relationship, ['hosts', 'profiles'], true)) {
        return [];
    }

    $ids = [];
    if (function_exists('get_field')) {
        $value = get_field($relationship, $episode_id);
        foreach (is_array($value) ? $value : [] as $row) {
            if ($relationship === 'profiles' && is_array($row) && array_key_exists('profile', $row)) {
                $row = $row['profile'];
            }
            if (is_object($row) && isset($row->ID)) {
                $row = $row->ID;
            }
            $profile_id = absint($row);
            if ($profile_id && get_post_type($profile_id) === 'profile') {
                $ids[] = $profile_id;
            }
        }
    }

    if ($ids === [] && $relationship === 'hosts') {
        foreach ((array) maybe_unserialize(get_post_meta($episode_id, 'hosts', true)) as $profile_id) {
            $profile_id = absint($profile_id);
            if ($profile_id && get_post_type($profile_id) === 'profile') {
                $ids[] = $profile_id;
            }
        }
    }

    if ($ids === [] && $relationship === 'profiles') {
        $count = absint(get_post_meta($episode_id, 'profiles', true));
        for ($index = 0; $index < $count; $index++) {
            $profile_id = absint(get_post_meta($episode_id, "profiles_{$index}_profile", true));
            if ($profile_id && get_post_type($profile_id) === 'profile') {
                $ids[] = $profile_id;
            }
        }
    }

    return array_values(array_unique($ids));
}

public static function apply_profile_query(WP_Query $query, string $relationship): void {
    static $resolving = false;
    if ($resolving) {
        return;
    }
    $resolving = true;
    try {
        $episode_id = self::context_id();
        $ids = self::relationship_ids($relationship, $episode_id);
        $query->set('post_type', 'profile');
        $query->set('post_status', 'publish');
        $query->set('post__in', $ids !== [] ? $ids : [0]);
        $query->set('orderby', 'post__in');
        $query->set('order', 'ASC');
        $query->set('posts_per_page', max(1, count($ids)));
        $query->set('ignore_sticky_posts', true);
        // Elementor's Loop Grid tests `found_posts`, not `post_count`, before it
        // renders the Loop Item. The outer display query must therefore calculate
        // found rows even though its relationship ID set is intentionally small.
        $query->set('no_found_rows', false);
    } finally {
        $resolving = false;
    }
}

public static function profile_context_id( int $candidate = 0 ): int {
    $candidates = [ $candidate ];
    if ( function_exists( 'get_queried_object_id' ) ) {
        $candidates[] = (int) get_queried_object_id();
    }
    $candidates[] = (int) get_the_ID();

    try {
        if ( class_exists( 'Elementor\\Plugin' )
            && isset( \Elementor\Plugin::$instance->documents )
            && method_exists( \Elementor\Plugin::$instance->documents, 'get_current' ) ) {
            $document = \Elementor\Plugin::$instance->documents->get_current();
            if ( is_object( $document ) && method_exists( $document, 'get_settings' ) ) {
                $candidates[] = (int) $document->get_settings( 'preview_id' );
            }
        }
    } catch ( Throwable ) {
        // Preview resolution is optional; continue through the safe candidates.
    }

    $post = $GLOBALS['post'] ?? null;
    if ( $post instanceof WP_Post ) {
        $candidates[] = (int) $post->ID;
    }

    foreach ( array_values( array_unique( array_filter( array_map( 'absint', $candidates ) ) ) ) as $post_id ) {
        if ( 'profile' === get_post_type( $post_id ) ) {
            return $post_id;
        }
    }
    return 0;
}

public static function profile_episode_ids( int $profile_id = 0 ): array {
    static $cache = [];
    $profile_id = self::profile_context_id( $profile_id );
    if ( $profile_id < 1 ) {
        return [];
    }
    if ( array_key_exists( $profile_id, $cache ) ) {
        return $cache[ $profile_id ];
    }

    global $wpdb;
    if ( ! is_object( $wpdb ) || ! isset( $wpdb->posts, $wpdb->postmeta ) ) {
        $cache[ $profile_id ] = [];
        return [];
    }

    $meta_key = $wpdb->esc_like( 'profiles_' ) . '%' . $wpdb->esc_like( '_profile' );
    $sql = $wpdb->prepare(
        "SELECT DISTINCT p.ID
           FROM {$wpdb->posts} AS p
           INNER JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
          WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND pm.meta_key LIKE %s
            AND CAST(pm.meta_value AS UNSIGNED) = %d
          ORDER BY p.post_date DESC, p.ID DESC",
        $meta_key,
        $profile_id
    );
    $ids = is_string( $sql ) ? $wpdb->get_col( $sql ) : [];
    $ids = is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : [];
    if ( class_exists( 'SMP\\Podcast\\Settings\\PodcastSettings' ) ) {
        $ids = array_values(
            array_filter(
                $ids,
                static fn( int $post_id ): bool => \SMP\Podcast\Settings\PodcastSettings::is_podcast_content( $post_id )
            )
        );
    }
    $cache[ $profile_id ] = $ids;
    return $ids;
}

public static function profile_apply_episode_query( WP_Query $query ): void {
    static $resolving = false;
    if ($resolving) { return; }
    $resolving = true;
    try {
    $ids = self::profile_episode_ids();
    $query->set( 'post_type', 'post' );
    $query->set( 'post_status', 'publish' );
    $query->set( 'post__in', $ids ?: [ 0 ] );
    $query->set( 'orderby', 'post__in' );
    $query->set( 'order', 'ASC' );
    $query->set( 'posts_per_page', max( 1, count( $ids ) ) );
    $query->set( 'ignore_sticky_posts', true );
    $query->set( 'no_found_rows', false );
    } finally { $resolving = false; }
}
}
