<?php
namespace SMP\VerifiedProfiles\ProfileTypes;
use Hexa\PluginCore\CoreContracts\ModuleInterface;
use SMP\VerifiedProfiles\ContentTypes\VerifiedProfileStructures;
/** Storage and integration contract. Presentation intentionally left to the screen owner. */
final class PodcastSection implements ModuleInterface {
    public function register(): void {
        add_filter('smp_vp_dashboard_tabs', static function(array $tabs): array { $tabs['podcast'] = 'Podcast'; return $tabs; });
        add_filter('smp_vp_render_dashboard_tab', [self::class, 'render'], 10, 2);
        add_action('wp_ajax_smp_verified_profiles_save_podcast', [self::class, 'save']);
        add_filter('acf/load_field/name=profiles', [self::class, 'guest_field']);
        add_action('init', static function(): void { if (get_option('smp_vp_podcast_rewrite_pending')) { flush_rewrite_rules(false); delete_option('smp_vp_podcast_rewrite_pending'); } }, 99);
        add_action('template_redirect', [self::class, 'redirect'], 1);
    }
    public static function render($rendered, string $tab) {
        if ($tab !== 'podcast') { return $rendered; }
        echo '<div id="smp-vp-podcast-settings"></div>';
        return true;
    }
    public static function set_enabled(bool $enabled): void {
        $registry = VerifiedProfileStructures::content_types();
        $definition = $registry->definition(ProfileTypes::GUEST_TYPE);
        $current = $registry->store()->resolve($definition);
        $groups = array_column(array_filter($current['field_groups'], static fn(array $g): bool => !empty($g['enabled'])), 'id');
        // The managed guest type always uses its existing Podcaster preset.
        if ($enabled) { $groups[] = 'podcaster-fields'; }
        $registry->store()->save($definition, [
            'enabled' => $enabled, 'singular' => $current['post_type']['singular'],
            'plural' => $current['post_type']['plural'], 'rewrite_slug' => $current['post_type']['rewrite_slug'],
            'enabled_field_groups' => array_unique($groups),
        ]);
        update_option('smp_vp_podcast_rewrite_pending', 1, false);
    }
    public static function save(): void {
        if (!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Insufficient permissions.'],403); }
        check_ajax_referer('smp_verified_profiles_podcast', 'nonce');
        $value = isset($_POST['enabled']) ? wp_unslash($_POST['enabled']) : null;
        if (!in_array($value, ['0','1',0,1], true)) { wp_send_json_error(['message'=>'enabled must be 0 or 1.'],400); }
        self::set_enabled((bool)$value);
        wp_send_json_success(['enabled'=>ProfileTypes::guest_type() !== '', 'post_type'=>ProfileTypes::GUEST_TYPE]);
    }
    public static function guest_field(array $field): array {
        $id = absint($_POST['post_ID'] ?? $_GET['post'] ?? get_the_ID());
        if (class_exists('SMP\\Podcast\\Settings\\PodcastSettings') && \SMP\Podcast\Settings\PodcastSettings::is_podcast_content($id)) {
            foreach ($field['sub_fields'] ?? [] as $i=>$sub) {
                if (($sub['name'] ?? '') === 'profile') { $field['sub_fields'][$i]['post_type'] = ProfileTypes::guest_type() !== '' ? [ProfileTypes::guest_type()] : ProfileTypes::post_types(); }
            }
            $field['label'] = 'Guests';
        }
        return $field;
    }
    public static function redirect(): void {
        if (ProfileTypes::guest_type() === '') { return; }
        $path = trim((string)wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        $prefix = trim((string)get_option('smp_vp_guest_legacy_slug', 'profile'), '/');
        if (!preg_match('~^'.preg_quote($prefix,'~').'/([^/]+)$~', $path, $m)) { return; }
        $post = get_page_by_path(rawurldecode($m[1]), OBJECT, ProfileTypes::GUEST_TYPE);
        if ($post && $post->post_status === 'publish' && get_post_meta($post->ID, '_smp_vp_guest_migrated', true)) {
            wp_safe_redirect(get_permalink($post),301,'SMP Verified Profiles'); exit;
        }
    }
}
