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
        add_action('init', static function(): void { if (get_option('smp_vp_podcast_rewrite_pending')) { flush_rewrite_rules(false); if (class_exists('RankMath\\Sitemap\\Cache')) { \RankMath\Sitemap\Cache::invalidate_storage(); } delete_option('smp_vp_podcast_rewrite_pending'); } }, 99);
        // Resolve migrated URLs before SEO plugins apply historical redirects on wp.
        add_action('wp', [self::class, 'redirect'], 1);
    }
    public static function render($rendered, string $tab) {
        if ($tab !== 'podcast') { return $rendered; }
        \Hexa\PluginCore\WpAdminComponents\CoreUi::render_assets();
        $on = ProfileTypes::guest_type() !== '';
        $count = $on ? (int)(wp_count_posts(ProfileTypes::GUEST_TYPE)->publish ?? 0) : 0;
        $archive = $on ? get_post_type_archive_link(ProfileTypes::GUEST_TYPE) : '';
        ?>
        <div id="smp-vp-podcast-settings" class="hpc-ui" data-nonce="<?php echo esc_attr(wp_create_nonce('smp_verified_profiles_podcast')); ?>" style="max-width:760px">
            <h2 style="margin:4px 0 4px;font-size:22px">Podcast</h2>
            <p style="margin:0 0 18px;color:#646970">Podcast guests get their own profile pages, separate from verified profiles, with their own list and sitemap. A guest does not need an episode.</p>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:16px">
                <?php echo \Hexa\PluginCore\WpAdminComponents\CoreUi::toggle('smp_vp_podcast_enabled', $on, 'Separate podcast guests', ['id' => 'smp-vp-podcast-toggle']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <p style="margin:10px 0 0;color:#50575e;font-size:13px" data-role="state"><?php echo esc_html($on ? sprintf('On. %d published guests at /guest/<name>/.', $count) : 'Off. Guests are stored as ordinary verified profiles.'); ?></p>
                <p style="margin:6px 0 0;color:#787c82;font-size:12px">Turning it off only hides the guest pages; no guest is deleted. The guest sitemap is on the Sitemaps tab.</p>
                <div class="hpc-actions" style="margin-top:12px" data-role="links"<?php echo $on ? '' : ' hidden'; ?>>
                    <a class="hpc-button secondary" href="<?php echo esc_url(admin_url('edit.php?post_type=' . ProfileTypes::GUEST_TYPE)); ?>">Manage guests</a>
                    <?php if ($archive) : ?><a class="hpc-button secondary hpc-external" href="<?php echo esc_url($archive); ?>" target="_blank" rel="noopener">View guest list</a><?php endif; ?>
                    <a class="hpc-button secondary hpc-external" href="<?php echo esc_url(home_url('/' . ProfileTypes::GUEST_TYPE . '-sitemap.xml')); ?>" target="_blank" rel="noopener">Guest sitemap</a>
                </div>
                <p style="margin:10px 0 0;font-size:12px" data-role="notice" role="status" aria-live="polite"></p>
            </div>
        </div>
        <script>
        (function(){
            var root = document.getElementById('smp-vp-podcast-settings'); if (!root || root.dataset.ready) return; root.dataset.ready = '1';
            var box = root.querySelector('#smp-vp-podcast-toggle'), note = root.querySelector('[data-role=notice]');
            box.addEventListener('change', function(){
                box.disabled = true; note.style.color = '#50575e'; note.textContent = 'Saving…';
                var body = new URLSearchParams({action:'smp_verified_profiles_save_podcast', nonce: root.dataset.nonce, enabled: box.checked ? '1' : '0'});
                fetch(ajaxurl, {method:'POST', credentials:'same-origin', body: body}).then(function(r){ return r.json(); }).then(function(j){
                    if (!j || !j.success) throw new Error((j && j.data && j.data.message) || 'Not saved.');
                    note.style.color = '#00701a'; note.textContent = 'Saved. Reloading…'; setTimeout(function(){ location.reload(); }, 600);
                }).catch(function(e){ box.checked = !box.checked; box.disabled = false; note.style.color = '#b32d2e'; note.textContent = e.message; });
            });
        })();
        </script>
        <?php
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
