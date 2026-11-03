<?php
namespace SMP\VerifiedProfiles\Elementor;
use WP_Term;
use Hexa\PluginCore\CoreContracts\ModuleInterface;
use Hexa\PluginCore\ShortcodeRegistry\ShortcodeRegistry;
use Hexa\PluginCore\ShortcodeRegistry\ShortcodeDefinition;
defined('ABSPATH') || exit;
/** Opt-in profile presentation. Existing CSS hooks are retained for theme compatibility. */
final class ProfilePresentation implements ModuleInterface {
    /** Read-only computed fields for conditions, reusing the profile presentation owners. */
    public static function computed_profile_field($value, $post_id, $key, $single) {
        if ($value !== null || !in_array($key, ['_smp_profile_has_image', '_smp_profile_has_no_image', '_smp_profile_has_socials', '_smp_profile_has_episodes'], true)) return $value;
        if (!\SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::is_profile_type((string)get_post_type($post_id))) return $value;
        $present = match ($key) {
            '_smp_profile_has_image' => self::profile_has_usable_image($post_id),
            '_smp_profile_has_no_image' => !self::profile_has_usable_image($post_id) && self::profile_initials($post_id) !== '',
            '_smp_profile_has_socials' => self::profile_page_social_links_shortcode(['post_id' => $post_id]) !== '',
            '_smp_profile_has_episodes' => self::episode_ids($post_id) !== [],
        };
        $result = $present ? '1' : '';
        return $single ? $result : [$result];
    }
    public static function callbacks(): array { return [
        'smp_profile_page_field' => [self::class, 'profile_page_field_shortcode'],
        'smp_profile_page_heading' => [self::class, 'profile_page_heading_shortcode'],
        'smp_profile_page_caption' => [self::class, 'profile_page_caption_shortcode'],
        'smp_profile_page_meta' => [self::class, 'profile_page_meta_shortcode'],
        'smp_profile_page_social_links' => [self::class, 'profile_page_social_links_shortcode'],
        'smp_profile_page_archive_label' => [self::class, 'profile_page_archive_label_shortcode'],
        'smp_profile_links' => [self::class, 'profile_links_shortcode'],
        'smp_profile_monogram' => [self::class, 'profile_monogram_shortcode'],
    ]; }
    public function register(): void {
        require_once __DIR__ . '/ProfileCallbacks.php';
        add_action('init', [self::class, 'register_shortcodes']);
        add_filter('get_post_metadata', [self::class, 'computed_profile_field'], 10, 4);
        add_filter('jet-engine/listings/allowed-callbacks', [self::class, 'allowed_callbacks']);
    }
    public static function register_shortcodes(): void {
        $registry = new ShortcodeRegistry();
        foreach (self::callbacks() as $tag => $callback) {
            $registry->add(new ShortcodeDefinition($tag, $tag, '[' . $tag . ']', 'Profile presentation with no plugin styling.', '', ''));
            add_shortcode($tag, $callback);
        }
        do_action('smp_verified_profiles_shortcode_registry', $registry);
    }
    public static function allowed_callbacks(array $callbacks): array {
        foreach (['smp_profile_biography_short_by_id' => 'Related profile: short biography', 'smp_profile_link_by_id' => 'Related profile: linked name', 'smp_profile_role' => 'Profile role unless duplicated by the name'] as $name => $label) $callbacks[$name] = $label;
        return $callbacks;
    }
    public static function context_id(int $candidate = 0): int {
        if (class_exists(\SMP\Podcast\Frontend\EpisodeData::class)) return \SMP\Podcast\Frontend\EpisodeData::profile_context_id($candidate);
        foreach ([$candidate, get_the_ID(), get_queried_object_id()] as $id) if ($id && \SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::is_profile_type((string)get_post_type($id))) return (int)$id;
        return 0;
    }
    public static function episode_ids(int $id): array {
        return class_exists(\SMP\Podcast\Frontend\EpisodeData::class) ? \SMP\Podcast\Frontend\EpisodeData::profile_episode_ids($id) : [];
    }
public static function profile_page_field_shortcode( array $atts = [] ): string {
    $atts = shortcode_atts( [ 'name' => '', 'post_id' => 0 ], $atts, 'smp_profile_page_field' );
    $profile_id = self::context_id( absint( $atts['post_id'] ) );
    $name = sanitize_key( (string) $atts['name'] );
    $allowed = [
        'biography', 'biography_short', 'job_title', 'location',
        'personal_current_residence_name',
    ];
    if ( $profile_id < 1 || ! in_array( $name, $allowed, true ) ) {
        return '';
    }
    $value = trim( wp_strip_all_tags( (string) get_post_meta( $profile_id, $name, true ) ) );
    return '' !== $value ? esc_html( $value ) : '';
}

public static function profile_page_heading_shortcode( array $atts = [] ): string {
    $atts = shortcode_atts( [ 'prefix' => 'About', 'post_id' => 0 ], $atts, 'smp_profile_page_heading' );
    $profile_id = self::context_id( absint( $atts['post_id'] ) );
    $title = $profile_id > 0 ? trim( wp_strip_all_tags( (string) get_the_title( $profile_id ) ) ) : '';
    if ( '' === $title ) {
        return '';
    }
    $prefix = trim( wp_strip_all_tags( (string) $atts['prefix'] ) );
    return esc_html( trim( $prefix . ' ' . $title ) );
}

public static function profile_page_caption_shortcode( array $atts = [] ): string {
    $atts = shortcode_atts( [ 'post_id' => 0 ], $atts, 'smp_profile_page_caption' );
    $profile_id = self::context_id( absint( $atts['post_id'] ) );
    $title = $profile_id > 0 ? trim( wp_strip_all_tags( (string) get_the_title( $profile_id ) ) ) : '';
    return '' !== $title ? esc_html( 'Guest · ' . $title ) : '';
}

public static function profile_page_meta_shortcode( array $atts = [] ): string {
    $atts = shortcode_atts( [ 'post_id' => 0 ], $atts, 'smp_profile_page_meta' );
    $profile_id = self::context_id( absint( $atts['post_id'] ) );
    if ( $profile_id < 1 ) {
        return '';
    }

    $items = [];
    if (get_post_type($profile_id) === \SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::DEFAULT_TYPE && '' !== trim( (string) get_post_meta( $profile_id, 'schema_markup', true ) ) ) {
        $items[] = '<li class="mpp-profile-meta__verified"><span aria-hidden="true">✓</span> Verified profile</li>';
    }
    $episode_ids = self::episode_ids( $profile_id );
    $count = count( $episode_ids );
    if ( $count > 0 ) {
        $items[] = '<li><i aria-hidden="true"></i>' . esc_html( sprintf( _n( '%d appearance', '%d appearances', $count, 'smp-verified-profiles' ), $count ) ) . '</li>';
    }

    $categories = [];
    foreach ( $episode_ids as $episode_id ) {
        foreach ( wp_get_post_categories( $episode_id ) as $term_id ) {
            $term_id = absint( $term_id );
            if ( $term_id > 0 && $term_id !== absint( get_option( 'default_category' ) ) ) {
                $categories[ $term_id ] = ( $categories[ $term_id ] ?? 0 ) + 1;
            }
        }
    }
    if ( $categories ) {
        arsort( $categories, SORT_NUMERIC );
        $term = get_term( (int) array_key_first( $categories ), 'category' );
        if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
            $url = get_term_link( $term );
            if ( ! is_wp_error( $url ) ) {
                $items[] = '<li><i aria-hidden="true"></i><a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a></li>';
            }
        }
    }

    $location = trim( wp_strip_all_tags( (string) get_post_meta( $profile_id, 'location', true ) ) );
    if ( '' === $location ) {
        $location = trim( wp_strip_all_tags( (string) get_post_meta( $profile_id, 'personal_current_residence_name', true ) ) );
    }
    if ( '' !== $location ) {
        $items[] = '<li><i aria-hidden="true"></i>' . esc_html( $location ) . '</li>';
    }

    return $items ? '<ul class="mpp-profile-page-meta">' . implode( '', $items ) . '</ul>' : '';
}

public static function profile_page_social_links_shortcode( array $atts = [] ): string {
    $atts = shortcode_atts( [ 'post_id' => 0 ], $atts, 'smp_profile_page_social_links' );
    $profile_id = self::context_id( absint( $atts['post_id'] ) );
    if ( $profile_id < 1 ) {
        return '';
    }
    $fields = [
        'url_instagram' => 'Instagram', 'url_linkedin' => 'LinkedIn', 'url_website' => 'Website',
        'url_youtube' => 'YouTube', 'url_x' => 'X', 'url_facebook' => 'Facebook',
        'url_wikipedia' => 'Wikipedia', 'url_imdb' => 'IMDb', 'url_tiktok' => 'TikTok',
        'url_amazon' => 'Amazon', 'url_audible' => 'Audible', 'url_github' => 'GitHub',
        'url_crunchbase' => 'Crunchbase', 'url_muckrack' => 'Muck Rack', 'url_soundcloud' => 'SoundCloud',
    ];
    $links = [];
    foreach ( $fields as $meta_key => $label ) {
        $url = esc_url_raw( trim( (string) get_post_meta( $profile_id, $meta_key, true ) ) );
        if ( '' === $url ) {
            continue;
        }
        $links[] = sprintf(
            '<a class="mpp-profile-social mpp-profile-social--%1$s" href="%2$s" target="_blank" rel="noopener noreferrer"><span>%3$s</span><b aria-hidden="true">↗</b><span class="screen-reader-text"> (opens in a new tab)</span></a>',
            esc_attr( str_replace( 'url_', '', $meta_key ) ),
            esc_url( $url ),
            esc_html( $label )
        );
    }
    return $links ? '<div class="mpp-profile-page-socials">' . implode( '', $links ) . '</div>' : '';
}

public static function profile_page_archive_label_shortcode( array $atts = [] ): string {
    unset( $atts );
    $type = \SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::guest_type() ?: \SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::default_type();
    $counts = wp_count_posts( $type );
    $count = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
    return esc_html( sprintf( _n( 'Browse all %d guest profile', 'Browse all %d guest profiles', $count, 'smp-verified-profiles' ), $count ) );
}

public static function profile_links_shortcode(array $atts = []): string {
    $atts = shortcode_atts(['post_id' => 0], $atts, 'smp_profile_links');
    $profile_id = absint($atts['post_id']);
    if (!$profile_id) {
        $profile_id = get_the_ID();
    }
    if (!$profile_id || !\SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::is_profile_type((string)get_post_type($profile_id))) {
        return '';
    }

    $profile_url = get_permalink($profile_id);
    $links = [];
    if ($profile_url) {
        $links[] = '<a class="mpp-profile-link mpp-profile-link--internal" href="' . esc_url($profile_url) . '">' . esc_html((string)(get_post_type_object(get_post_type($profile_id))->labels->singular_name ?? 'Profile')) . ' <span aria-hidden="true">→</span></a>';
    }

    foreach (['url_instagram' => 'Instagram', 'url_linkedin' => 'LinkedIn', 'url_website' => 'Website'] as $meta_key => $label) {
        $url = esc_url_raw((string) get_post_meta($profile_id, $meta_key, true));
        if ($url === '') {
            continue;
        }
        $links[] = '<a class="mpp-profile-link mpp-profile-link--external" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . ' <span aria-hidden="true">↗</span><span class="screen-reader-text"> (opens in a new tab)</span></a>';
    }

    return $links === [] ? '' : '<div class="mpp-profile-links">' . implode('', $links) . '</div>';
}

public static function profile_monogram_shortcode(array $atts = []): string {
    $atts = shortcode_atts(['post_id' => 0, 'variant' => 'chip'], $atts, 'smp_profile_monogram');
    $profile_id = self::context_id(absint($atts['post_id']));
    if (!$profile_id || self::profile_has_usable_image($profile_id)) {
        return '';
    }
    $initials = self::profile_initials($profile_id);
    if ($initials === '') {
        return '';
    }
    $variant = in_array($atts['variant'], ['chip', 'dossier', 'rail'], true) ? $atts['variant'] : 'chip';
    return '<span class="mpp-profile-monogram mpp-profile-monogram--' . esc_attr($variant) . '" aria-hidden="true">'
        . esc_html($initials)
        . '</span>';
}

public static function profile_initials(int $profile_id): string {
    $title = trim(wp_strip_all_tags((string) get_the_title($profile_id)));
    if ($title === '') {
        return '';
    }
    $words = preg_split('/[\s\-]+/u', $title, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if ($words === []) {
        return '';
    }
    $initials = mb_substr((string) $words[0], 0, 1);
    if (count($words) > 1) {
        $initials .= mb_substr((string) $words[array_key_last($words)], 0, 1);
    }
    return mb_strtoupper($initials);
}

public static function profile_has_usable_image(int $profile_id): bool {
    $attachment_id = absint(get_post_thumbnail_id($profile_id));
    if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
        return false;
    }
    $file = get_attached_file($attachment_id);
    return is_string($file) && $file !== '' && is_file($file) && is_readable($file);
}

public static function profile_id($value): int {
    if (is_array($value)) {
        $value = reset($value);
    }
    if (is_object($value) && isset($value->ID)) {
        $value = $value->ID;
    }
    return absint($value);
}

public static function profile_biography_short_by_id($value): string {
    $profile_id = self::profile_id($value);
    if (!$profile_id || !\SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::is_profile_type((string)get_post_type($profile_id))) {
        return '';
    }
    $biography = trim(wp_strip_all_tags((string) get_post_meta($profile_id, 'biography_short', true)));
    if ($biography === '') {
        $biography = trim(wp_strip_all_tags((string) get_post_meta($profile_id, 'biography', true)));
    }
    return $biography;
}

public static function profile_link_by_id($value): string {
    $profile_id = self::profile_id($value);
    if (!$profile_id || !\SMP\VerifiedProfiles\ProfileTypes\ProfileTypes::is_profile_type((string)get_post_type($profile_id))) {
        return '';
    }
    $title = get_the_title($profile_id);
    $url = get_permalink($profile_id);
    if (!$title || !$url) {
        return '';
    }
    return sprintf(
        '<a href="%1$s"><span class="mpp-guest-k">Guest</span><span class="mpp-guest-n">%2$s</span><span class="mpp-guest-arrow" aria-hidden="true">&#8594;</span><span class="screen-reader-text"> - view the %3$s</span></a>',
        esc_url($url),
        esc_html($title),
        esc_html(strtolower((string)(get_post_type_object(get_post_type($profile_id))->labels->singular_name ?? 'profile')))
    );
}

public static function profile_role($value): string {
    $role = trim(wp_strip_all_tags((string) $value));
    $name = trim((string) get_the_title());
    return $role !== '' && strcasecmp($role, $name) !== 0 ? $role : '';
}
}
