<?php

namespace smp_verified_profiles;

defined("ABSPATH") || exit;

const SMP_VP_DISPLAY_OPTION = "smp_vp_display_card_settings";
const SMP_VP_DISPLAY_NONCE = "smp_vp_display_nonce";

add_filter("smp_vp_dashboard_tabs", __NAMESPACE__ . "\\smp_vp_display_dashboard_tab");
add_filter("smp_vp_render_dashboard_tab", __NAMESPACE__ . "\\smp_vp_render_display_dashboard_tab", 10, 2);
add_action("wp_ajax_smp_vp_display_save_settings", __NAMESPACE__ . "\\smp_vp_ajax_display_save_settings");
add_action("wp_ajax_smp_vp_display_import_elementor", __NAMESPACE__ . "\\smp_vp_ajax_display_import_elementor");
add_filter("the_content", __NAMESPACE__ . "\\smp_vp_display_append_to_content", 28);
add_shortcode("display_homepage_profiles", __NAMESPACE__ . "\\display_homepage_profiles");
add_shortcode("display_single_post_mentioned_in_article", __NAMESPACE__ . "\\display_single_post_mentioned_in_article");
add_shortcode("display_profiles_featured_in_single_post", __NAMESPACE__ . "\\display_profiles_featured_in_single_post");

function smp_vp_display_dashboard_tab(array $tabs): array {
    $tabs["features"] = "Features";
    return $tabs;
}

function smp_vp_render_display_dashboard_tab($rendered, string $tab_id) {
    if ("features" !== $tab_id) {
        return $rendered;
    }

    smp_vp_display_render_settings();
    return true;
}

function smp_vp_display_templates(): array {
    return [
        "boxed-row" => [
            "label" => "Boxed Row",
            "class" => "vp-a",
            "description" => "A compact bordered card with portrait, verified mark, title, role, and link.",
        ],
        "centered-stack" => [
            "label" => "Centered Stack",
            "class" => "vp-b",
            "description" => "Centered portrait treatment for editorial profile sections.",
        ],
        "directory-list" => [
            "label" => "Directory List",
            "class" => "vp-c",
            "description" => "Horizontal directory row with a right-side profile action.",
        ],
        "minimal-quiet" => [
            "label" => "Minimal Quiet",
            "class" => "vp-d",
            "description" => "Low-noise row with small portrait and restrained spacing.",
        ],
        "accent-rule" => [
            "label" => "Accent Rule Cards",
            "class" => "vp-e",
            "description" => "Soft background card with an accent rule for featured placements.",
        ],
    ];
}

function smp_vp_display_defaults(): array {
    return [
        "enabled" => true,
        "append_to_content" => true,
        "homepage_template" => "boxed-row",
        "post_template" => "directory-list",
        "profile_limit" => 7,
        "require_thumbnail" => false,
        "archive_url" => home_url("/profiles/"),
        "primary_color" => "#b3272d",
        "ink_color" => "#151515",
        "muted_color" => "#747474",
        "line_color" => "#e6e1de",
        "soft_color" => "#faf7f5",
        "name_font_size" => 18,
        "role_font_size" => 10,
    ];
}

function smp_vp_display_settings(): array {
    $stored = get_option(SMP_VP_DISPLAY_OPTION, []);
    return array_replace(smp_vp_display_defaults(), is_array($stored) ? $stored : []);
}

function smp_vp_display_sanitize(array $input): array {
    $settings = smp_vp_display_settings();
    $templates = array_keys(smp_vp_display_templates());

    $settings["enabled"] = ! empty($input["enabled"]);
    $settings["append_to_content"] = ! empty($input["append_to_content"]);
    $settings["require_thumbnail"] = ! empty($input["require_thumbnail"]);
    $settings["homepage_template"] = in_array((string) ($input["homepage_template"] ?? ""), $templates, true) ? (string) $input["homepage_template"] : $settings["homepage_template"];
    $settings["post_template"] = in_array((string) ($input["post_template"] ?? ""), $templates, true) ? (string) $input["post_template"] : $settings["post_template"];
    $settings["profile_limit"] = max(1, min(30, absint($input["profile_limit"] ?? $settings["profile_limit"])));
    $settings["archive_url"] = esc_url_raw((string) ($input["archive_url"] ?? $settings["archive_url"]));

    foreach (["primary_color", "ink_color", "muted_color", "line_color", "soft_color"] as $key) {
        $color = sanitize_hex_color((string) ($input[$key] ?? $settings[$key]));
        if ($color) {
            $settings[$key] = $color;
        }
    }

    return $settings;
}

function smp_vp_display_css(): string {
    return ".smp-vp-display{--vp-red:#b3272d;--vp-ink:#151515;--vp-muted:#747474;--vp-line:#e6e1de;--vp-soft:#faf7f5;max-width:1080px;margin:0 auto;padding:34px 0}.smp-vp-display .vp-head{display:flex;align-items:center;justify-content:space-between;padding:18px 0;border-bottom:1px solid var(--vp-line)}.smp-vp-display .lbl,.smp-vp-display .all,.smp-vp-display .vp-role,.smp-vp-display .vp-cta{font-size:var(--vp-role-size,11px);letter-spacing:.14em;text-transform:uppercase}.smp-vp-display .lbl,.smp-vp-display .vp-role,.smp-vp-display .all{color:var(--vp-muted)}.smp-vp-display a{text-decoration:none}.smp-vp-display svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2}.smp-vp-display .vp-av{position:relative;flex-shrink:0}.smp-vp-display .vp-av img{border-radius:50%;object-fit:cover;filter:grayscale(100%);display:block;background:#f3f3f3}.smp-vp-display .vp-card:hover img{filter:grayscale(0)}.smp-vp-display .vp-badge{position:absolute;right:-3px;bottom:-3px;width:20px;height:20px;background:#fff;border-radius:50%;color:var(--vp-red);display:flex;align-items:center;justify-content:center}.smp-vp-display .vp-name{font-family:Playfair Display,Georgia,serif;font-weight:700;font-size:var(--vp-name-size,18px);color:var(--vp-ink);line-height:1.2}.smp-vp-display .vp-card:hover .vp-name,.smp-vp-display .vp-cta{color:var(--vp-red)}.smp-vp-display .vp-a,.smp-vp-display .vp-b,.smp-vp-display .vp-d,.smp-vp-display .vp-e{display:grid;gap:20px;padding:32px 0}.smp-vp-display .vp-a{grid-template-columns:repeat(3,1fr)}.smp-vp-display .vp-b{grid-template-columns:repeat(3,1fr)}.smp-vp-display .vp-d{grid-template-columns:repeat(4,1fr);gap:6px 28px}.smp-vp-display .vp-e{grid-template-columns:repeat(2,1fr)}.smp-vp-display .vp-a .vp-card{display:flex;align-items:center;gap:18px;border:1px solid var(--vp-line);background:#fff;padding:20px}.smp-vp-display .vp-b .vp-card{display:flex;flex-direction:column;align-items:center;text-align:center;border:1px solid var(--vp-line);background:#fff;padding:30px 20px}.smp-vp-display .vp-c{padding:14px 0 32px}.smp-vp-display .vp-c .vp-card{display:flex;align-items:center;gap:18px;padding:18px 4px;border-bottom:1px solid var(--vp-line)}.smp-vp-display .vp-c .vp-meta{flex:1}.smp-vp-display .vp-d .vp-card{display:flex;align-items:center;gap:14px;padding:18px 2px;border-top:1px solid var(--vp-line)}.smp-vp-display .vp-e .vp-card{display:flex;align-items:center;gap:20px;background:var(--vp-soft);border-left:3px solid var(--vp-red);padding:22px 24px}.smp-vp-display .vp-a img{width:66px;height:66px}.smp-vp-display .vp-b img{width:84px;height:84px}.smp-vp-display .vp-c img{width:58px;height:58px}.smp-vp-display .vp-d img{width:52px;height:52px}.smp-vp-display .vp-e img{width:88px;height:88px}@media(max-width:900px){.smp-vp-display .vp-a,.smp-vp-display .vp-b,.smp-vp-display .vp-d,.smp-vp-display .vp-e{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.smp-vp-display .vp-a,.smp-vp-display .vp-b,.smp-vp-display .vp-d,.smp-vp-display .vp-e{grid-template-columns:1fr}}";
}

function smp_vp_display_render_settings(): void {
    if (! current_user_can("manage_options")) {
        ?><div class="notice notice-error"><p>Insufficient permissions.</p></div><?php
        return;
    }

    $settings = smp_vp_display_settings();
    $templates = smp_vp_display_templates();
    $labels = array_map(static function ($template) {
        return $template["label"];
    }, $templates);
    $nonce = wp_create_nonce(SMP_VP_DISPLAY_NONCE);
    $preview = smp_vp_display_preview_profiles();
    ?>
    <style>
        <?php echo smp_vp_display_css(); ?>
        .smp-vp-display-admin{max-width:1260px;color:#1d2327}.smp-vp-display-admin *{box-sizing:border-box}.smp-vp-display-admin .smp-vp-panel{background:#fff;border:1px solid #dcdcde;border-radius:10px;margin:16px 0;overflow:hidden}.smp-vp-display-admin .smp-vp-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid #eceff3}.smp-vp-display-admin .smp-vp-panel-head h2{margin:0 0 4px;font-size:20px;line-height:1.2}.smp-vp-display-admin .smp-vp-panel-head p{margin:0;color:#646970}.smp-vp-display-admin .smp-vp-settings-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;padding:18px 20px}.smp-vp-display-admin label{font-weight:700;display:block;margin-bottom:6px}.smp-vp-display-admin input[type=text],.smp-vp-display-admin input[type=number]{width:100%;min-height:38px}.smp-vp-display-admin input[type=color]{width:100%;height:38px;padding:2px}.smp-vp-display-admin .smp-vp-checks{display:flex;gap:16px;flex-wrap:wrap;padding:0 20px 18px}.smp-vp-display-admin .smp-vp-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:16px 20px;background:#f6f7f7;border-top:1px solid #eceff3}.smp-vp-display-admin .smp-vp-log{min-height:38px;min-width:280px;border:1px solid #dcdcde;background:#fff;border-radius:6px;padding:9px 12px;font-family:Menlo,Consolas,monospace;color:#3c434a}.smp-vp-display-admin .smp-vp-current{display:flex;gap:10px;flex-wrap:wrap}.smp-vp-display-admin .smp-vp-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;background:#f0f6fc;color:#0a4b78;font-weight:700;padding:7px 11px}.smp-vp-template-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;padding:20px}.smp-vp-template-card{border:1px solid #dcdcde;border-radius:10px;background:#fff;overflow:hidden;transition:border-color .16s ease,box-shadow .16s ease}.smp-vp-template-card.is-homepage,.smp-vp-template-card.is-post{border-color:#b3272d;box-shadow:0 0 0 1px rgba(179,39,45,.18)}.smp-vp-template-card-head{display:flex;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #eef0f3}.smp-vp-template-card h3{margin:0;font-size:16px}.smp-vp-template-card p{margin:4px 0 0;color:#646970}.smp-vp-template-badges{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}.smp-vp-template-badge{display:none;border-radius:999px;background:#f7e5e7;color:#8a1b20;font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;padding:5px 8px;white-space:nowrap}.smp-vp-template-card.is-homepage .smp-vp-template-badge-home,.smp-vp-template-card.is-post .smp-vp-template-badge-post{display:inline-flex}.smp-vp-preview-frame{padding:18px;background:#fbfaf9;min-height:190px;border-bottom:1px solid #eef0f3}.smp-vp-preview-frame .smp-vp-display{max-width:none;margin:0;padding:0}.smp-vp-preview-frame .smp-vp-display .vp-head{display:none}.smp-vp-preview-frame .smp-vp-display .vp-a,.smp-vp-preview-frame .smp-vp-display .vp-b,.smp-vp-preview-frame .smp-vp-display .vp-d,.smp-vp-preview-frame .smp-vp-display .vp-e{grid-template-columns:minmax(0,1fr);padding:12px 0}.smp-vp-preview-frame .smp-vp-display .vp-c{padding:12px 0}.smp-vp-template-actions{display:flex;gap:10px;flex-wrap:wrap;padding:14px 18px}.smp-vp-template-action.is-active{background:#b3272d;border-color:#b3272d;color:#fff}.smp-vp-display-admin .screen-reader-selects{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}@media(max-width:980px){.smp-vp-template-grid,.smp-vp-display-admin .smp-vp-settings-grid{grid-template-columns:1fr}}
    </style>
    <div class="smp-vp-display-admin" id="smp-vp-display-settings" data-nonce="<?php echo esc_attr($nonce); ?>">
        <div class="smp-vp-panel">
            <div class="smp-vp-panel-head">
                <div>
                    <h2>Verified Profiles</h2>
                    <p>Select the frontend homepage design and the post.php mentioned-entities design directly from the template previews.</p>
                </div>
                <div class="smp-vp-current">
                    <span class="smp-vp-pill">Homepage: <strong id="smp-vp-current-homepage"><?php echo esc_html($labels[$settings["homepage_template"]] ?? $settings["homepage_template"]); ?></strong></span>
                    <span class="smp-vp-pill">Post entities: <strong id="smp-vp-current-post"><?php echo esc_html($labels[$settings["post_template"]] ?? $settings["post_template"]); ?></strong></span>
                </div>
            </div>
            <div class="screen-reader-selects">
                <input id="smp-vp-homepage-template" type="hidden" value="<?php echo esc_attr($settings["homepage_template"]); ?>">
                <input id="smp-vp-post-template" type="hidden" value="<?php echo esc_attr($settings["post_template"]); ?>">
            </div>
            <div class="smp-vp-settings-grid">
                <div><label for="smp-vp-profile-limit">Homepage limit</label><input id="smp-vp-profile-limit" type="number" min="1" max="30" value="<?php echo esc_attr($settings["profile_limit"]); ?>"></div>
                <div><label for="smp-vp-archive-url">Archive URL</label><input id="smp-vp-archive-url" type="text" value="<?php echo esc_attr($settings["archive_url"]); ?>"></div>
                <div><label for="smp-vp-name-font-size">Name font size</label><input id="smp-vp-name-font-size" type="number" min="12" max="32" value="<?php echo esc_attr($settings["name_font_size"] ?? 18); ?>"></div>
                <div><label for="smp-vp-role-font-size">Role font size</label><input id="smp-vp-role-font-size" type="number" min="8" max="18" value="<?php echo esc_attr($settings["role_font_size"] ?? 10); ?>"></div>
                <?php foreach (["primary_color" => "Primary", "ink_color" => "Text", "muted_color" => "Muted", "line_color" => "Line", "soft_color" => "Soft background"] as $key => $label) : ?>
                    <div><label for="smp-vp-<?php echo esc_attr(str_replace("_", "-", $key)); ?>"><?php echo esc_html($label); ?></label><input id="smp-vp-<?php echo esc_attr(str_replace("_", "-", $key)); ?>" type="color" value="<?php echo esc_attr($settings[$key]); ?>"></div>
                <?php endforeach; ?>
            </div>
            <div class="smp-vp-checks">
                <label><input id="smp-vp-display-enabled" type="checkbox" <?php checked($settings["enabled"]); ?>> Enable cards</label>
                <label><input id="smp-vp-append-content" type="checkbox" <?php checked($settings["append_to_content"]); ?>> Append to posts and press releases</label>
                <label><input id="smp-vp-require-thumb" type="checkbox" <?php checked($settings["require_thumbnail"]); ?>> Require thumbnails</label>
            </div>
            <div class="smp-vp-actions">
                <button type="button" class="button button-primary" id="smp-vp-display-save">Save Feature Settings</button>
                <button type="button" class="button" id="smp-vp-import-elementor">Import Elementor colors</button>
                <div class="smp-vp-log" id="smp-vp-display-log">Ready.</div>
            </div>
        </div>
        <div class="smp-vp-panel">
            <div class="smp-vp-panel-head">
                <div>
                    <h2>Template Library</h2>
                    <p>Each treatment shows one loop item. Use the buttons on the card to assign it.</p>
                </div>
            </div>
            <div class="smp-vp-template-grid">
                <?php foreach ($templates as $key => $template) : ?>
                    <div class="smp-vp-template-card" data-template-key="<?php echo esc_attr($key); ?>">
                        <div class="smp-vp-template-card-head">
                            <div>
                                <h3><?php echo esc_html($template["label"]); ?></h3>
                                <p><?php echo esc_html($template["description"]); ?></p>
                            </div>
                            <div class="smp-vp-template-badges">
                                <span class="smp-vp-template-badge smp-vp-template-badge-home">Homepage</span>
                                <span class="smp-vp-template-badge smp-vp-template-badge-post">Post</span>
                            </div>
                        </div>
                        <div class="smp-vp-preview-frame">
                            <?php echo smp_vp_display_render_collection($preview, ["template" => $key, "show_head" => false]); ?>
                        </div>
                        <div class="smp-vp-template-actions">
                            <button type="button" class="button smp-vp-template-action" data-target="homepage" data-template="<?php echo esc_attr($key); ?>">Use for homepage</button>
                            <button type="button" class="button smp-vp-template-action" data-target="post" data-template="<?php echo esc_attr($key); ?>">Use for post entities</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
        jQuery(function($){
            const labels = <?php echo wp_json_encode($labels); ?>;
            const $root = $("#smp-vp-display-settings");
            const $log = $("#smp-vp-display-log");
            function collect(){
                return {
                    enabled: $("#smp-vp-display-enabled").is(":checked") ? 1 : 0,
                    append_to_content: $("#smp-vp-append-content").is(":checked") ? 1 : 0,
                    require_thumbnail: $("#smp-vp-require-thumb").is(":checked") ? 1 : 0,
                    homepage_template: $("#smp-vp-homepage-template").val(),
                    post_template: $("#smp-vp-post-template").val(),
                    profile_limit: $("#smp-vp-profile-limit").val(),
                    archive_url: $("#smp-vp-archive-url").val(),
                    primary_color: $("#smp-vp-primary-color").val(),
                    ink_color: $("#smp-vp-ink-color").val(),
                    muted_color: $("#smp-vp-muted-color").val(),
                    line_color: $("#smp-vp-line-color").val(),
                    soft_color: $("#smp-vp-soft-color").val(),
                    name_font_size: $("#smp-vp-name-font-size").val(),
                    role_font_size: $("#smp-vp-role-font-size").val()
                };
            }
            function log(message){ $log.text(message || "Done."); }
            function syncState(){
                const home = $("#smp-vp-homepage-template").val();
                const post = $("#smp-vp-post-template").val();
                $("#smp-vp-current-homepage").text(labels[home] || home);
                $("#smp-vp-current-post").text(labels[post] || post);
                $(".smp-vp-template-card").each(function(){
                    const key = $(this).data("template-key");
                    $(this).toggleClass("is-homepage", key === home).toggleClass("is-post", key === post);
                });
                $(".smp-vp-template-action").each(function(){
                    const target = $(this).data("target");
                    const key = $(this).data("template");
                    $(this).toggleClass("is-active", (target === "homepage" && key === home) || (target === "post" && key === post));
                });
            }
            function save(button, doneMessage){
                const $button = button ? $(button) : $("#smp-vp-display-save");
                const original = $button.text();
                $button.prop("disabled", true).text("Saving...");
                log("Saving display settings...");
                return $.post(ajaxurl, { action: "smp_vp_display_save_settings", nonce: $root.data("nonce"), settings: collect() })
                    .done(function(response){
                        if (response && response.success) { log(doneMessage || response.data.message || "Feature settings saved."); }
                        else { log((response && response.data && response.data.message) || "Save failed."); }
                    })
                    .fail(function(){ log("Save request failed."); })
                    .always(function(){ $button.prop("disabled", false).text(original); syncState(); });
            }
            $(".smp-vp-template-action").on("click", function(){
                const target = $(this).data("target");
                const key = $(this).data("template");
                if (target === "homepage") { $("#smp-vp-homepage-template").val(key); }
                if (target === "post") { $("#smp-vp-post-template").val(key); }
                syncState();
                save(this, "Template selection saved.");
            });
            $("#smp-vp-display-save").on("click", function(){ save(this); });
            $("#smp-vp-import-elementor").on("click", function(){
                const $button = $(this);
                const original = $button.text();
                $button.prop("disabled", true).text("Importing...");
                log("Importing Elementor colors...");
                $.post(ajaxurl, { action: "smp_vp_display_import_elementor", nonce: $root.data("nonce") })
                    .done(function(response){ log(response && response.success ? response.data.message : ((response.data && response.data.message) || "Import failed.")); })
                    .fail(function(){ log("Import request failed."); })
                    .always(function(){ $button.prop("disabled", false).text(original); });
            });
            syncState();
        });
    </script>
    <?php
}

function smp_vp_ajax_display_save_settings(): void {
    if (! current_user_can("manage_options")) {
        wp_send_json_error(["message" => "Insufficient permissions."], 403);
    }

    check_ajax_referer(SMP_VP_DISPLAY_NONCE, "nonce");
    $input = isset($_POST["settings"]) && is_array($_POST["settings"]) ? wp_unslash($_POST["settings"]) : [];
    $settings = smp_vp_display_sanitize($input);
    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
    wp_send_json_success(["message" => "Feature settings saved.", "settings" => $settings]);
}

function smp_vp_ajax_display_import_elementor(): void {
    if (! current_user_can("manage_options")) {
        wp_send_json_error(["message" => "Insufficient permissions."], 403);
    }

    check_ajax_referer(SMP_VP_DISPLAY_NONCE, "nonce");
    $colors = smp_vp_display_elementor_colors();
    if (empty($colors)) {
        wp_send_json_error(["message" => "No Elementor colors found."], 404);
    }

    $settings = array_replace(smp_vp_display_settings(), $colors);
    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
    wp_send_json_success(["message" => "Elementor colors imported.", "settings" => $settings]);
}

function smp_vp_display_elementor_colors(): array {
    $kit = absint(get_option("elementor_active_kit"));
    if (! $kit) {
        return [];
    }

    $raw = get_post_meta($kit, "_elementor_page_settings", true);
    if (! is_array($raw)) {
        return [];
    }

    $flat = [];
    foreach (["system_colors", "custom_colors"] as $group) {
        foreach ((array) ($raw[$group] ?? []) as $color) {
            if (! empty($color["_id"]) && ! empty($color["color"]) && sanitize_hex_color($color["color"])) {
                $flat[sanitize_key($color["_id"])] = sanitize_hex_color($color["color"]);
            }
        }
    }

    return array_filter([
        "primary_color" => $flat["primary"] ?? $flat["accent"] ?? null,
        "ink_color" => $flat["text"] ?? null,
        "muted_color" => $flat["secondary"] ?? null,
    ]);
}

function smp_vp_display_preview_profiles(): array {
    return [[
        "name" => "Mash Viral",
        "role" => "Publication",
        "url" => "#",
        "image" => "https://picsum.photos/seed/smp-vp-template-preview/180/180?grayscale",
    ]];
}

function smp_vp_display_profile_data($profile): array {
    if (is_array($profile)) {
        return $profile;
    }

    $id = is_object($profile) && isset($profile->ID) ? (int) $profile->ID : (int) $profile;
    if (! $id) {
        return [];
    }

    $image = get_the_post_thumbnail_url($id, "medium");

    return [
        "name" => get_the_title($id),
        "role" => smp_vp_display_profile_role($id),
        "url" => get_permalink($id) ?: "#",
        "image" => $image ?: "https://picsum.photos/seed/" . rawurlencode(sanitize_title(get_the_title($id))) . "/180/180?grayscale",
    ];
}

function smp_vp_display_profile_role(int $id): string {
    foreach (["title", "job_title", "role", "occupation", "profession"] as $field) {
        $value = function_exists("get_field") ? get_field($field, $id) : get_post_meta($id, $field, true);
        if (is_scalar($value) && trim((string) $value) !== "") {
            return trim(wp_strip_all_tags((string) $value));
        }
    }

    return "Verified Profile";
}

function smp_vp_display_arrow_svg(): string {
    ob_start();
    ?><svg viewBox="0 0 24 24" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg><?php
    return (string) ob_get_clean();
}

function smp_vp_display_badge_svg(): string {
    ob_start();
    ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?php
    return (string) ob_get_clean();
}

function smp_vp_display_render_card($profile, string $template): string {
    $data = smp_vp_display_profile_data($profile);
    if (empty($data["name"])) {
        return "";
    }

    ob_start();
    ?>
    <a href="<?php echo esc_url($data["url"]); ?>" class="vp-card">
        <div class="vp-av">
            <img src="<?php echo esc_url($data["image"]); ?>" alt="<?php echo esc_attr($data["name"]); ?>">
            <span class="vp-badge"><?php echo smp_vp_display_badge_svg(); ?></span>
        </div>
        <?php if ("directory-list" === $template) : ?>
            <div class="vp-meta">
                <div class="vp-name"><?php echo esc_html($data["name"]); ?></div>
                <span class="vp-role"><?php echo esc_html($data["role"]); ?></span>
            </div>
            <div class="vp-cta">View Profile <?php echo smp_vp_display_arrow_svg(); ?></div>
        <?php else : ?>
            <div>
                <div class="vp-name"><?php echo esc_html($data["name"]); ?></div>
                <span class="vp-role"><?php echo esc_html($data["role"]); ?></span>
                <?php if ("minimal-quiet" !== $template) : ?>
                    <div class="vp-cta">View Profile <?php echo smp_vp_display_arrow_svg(); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </a>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_display_render_collection(array $profiles, array $args = []): string {
    $settings = array_replace(smp_vp_display_settings(), $args);
    $template = $settings["template"] ?? $settings["homepage_template"];
    $templates = smp_vp_display_templates();
    if (empty($templates[$template])) {
        $template = "boxed-row";
    }

    $vars = sprintf(
        "--vp-red:%s;--vp-ink:%s;--vp-muted:%s;--vp-line:%s;--vp-soft:%s;--vp-name-size:%dpx;--vp-role-size:%dpx;",
        esc_attr($settings["primary_color"]),
        esc_attr($settings["ink_color"]),
        esc_attr($settings["muted_color"]),
        esc_attr($settings["line_color"]),
        esc_attr($settings["soft_color"]),
        absint($settings["name_font_size"] ?? 18),
        absint($settings["role_font_size"] ?? 10)
    );

    ob_start();
    ?>
    <style><?php echo smp_vp_display_css(); ?></style>
    <section class="smp-vp-display" style="<?php echo esc_attr($vars); ?>">
        <?php if (($settings["show_head"] ?? true)) : ?>
            <div class="vp-head">
                <span class="lbl"><?php echo esc_html($settings["title"] ?? "Verified Profiles"); ?></span>
                <a class="all" href="<?php echo esc_url($settings["archive_url"]); ?>">View Profiles <?php echo smp_vp_display_arrow_svg(); ?></a>
            </div>
        <?php endif; ?>
        <div class="vp <?php echo esc_attr($templates[$template]["class"]); ?>">
            <?php foreach ($profiles as $profile) { echo smp_vp_display_render_card($profile, $template); } ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_display_homepage_ids(int $limit, bool $require_thumbnail): array {
    $args = [
        "post_type" => "profile",
        "post_status" => "publish",
        "posts_per_page" => $limit,
        "orderby" => "modified",
        "order" => "DESC",
        "fields" => "ids",
    ];

    if ($require_thumbnail) {
        $args["meta_query"] = [["key" => "_thumbnail_id", "compare" => "EXISTS"]];
    }

    return array_map("intval", get_posts($args));
}

function smp_vp_display_post_ids(int $post_id, bool $require_thumbnail = false): array {
    $ids = [];

    if (function_exists("get_field")) {
        foreach ((array) get_field("profiles", $post_id) as $row) {
            $value = is_array($row) ? ($row["profile"] ?? reset($row)) : $row;
            if (is_object($value) && isset($value->ID)) {
                $value = $value->ID;
            }
            if (absint($value)) {
                $ids[] = absint($value);
            }
        }
    }

    $ids = array_values(array_unique($ids));
    if (! $require_thumbnail) {
        return $ids;
    }

    return array_values(array_filter($ids, static function ($id) {
        return has_post_thumbnail($id);
    }));
}

function smp_vp_display_append_to_content(string $content): string {
    $settings = smp_vp_display_settings();
    if (empty($settings["enabled"]) || empty($settings["append_to_content"]) || is_admin() || ! is_singular(["post", "press-release"]) || ! in_the_loop() || ! is_main_query()) {
        return $content;
    }

    $ids = smp_vp_display_post_ids((int) get_the_ID());
    return $ids ? $content . smp_vp_display_render_collection($ids, ["template" => $settings["post_template"], "title" => "Verified Profiles"]) : $content;
}

if (! function_exists(__NAMESPACE__ . "\\display_homepage_profiles")) {
    function display_homepage_profiles($atts = []): string {
        $settings = smp_vp_display_settings();
        $atts = shortcode_atts(["limit" => $settings["profile_limit"], "template" => $settings["homepage_template"]], (array) $atts, "display_homepage_profiles");
        $ids = smp_vp_display_homepage_ids(absint($atts["limit"]), ! empty($settings["require_thumbnail"]));
        return $ids ? smp_vp_display_render_collection($ids, ["template" => $atts["template"], "title" => "Verified Profiles"]) : "<style>.display_home_profiles{display:none!important}</style>";
    }
}

if (! function_exists(__NAMESPACE__ . "\\display_single_post_mentioned_in_article")) {
    function display_single_post_mentioned_in_article($atts = []): string {
        $settings = smp_vp_display_settings();
        $atts = shortcode_atts(["must_have_thumbnail" => false, "template" => $settings["post_template"]], (array) $atts, "display_single_post_mentioned_in_article");
        $ids = smp_vp_display_post_ids((int) get_the_ID(), filter_var($atts["must_have_thumbnail"], FILTER_VALIDATE_BOOLEAN));
        return $ids ? smp_vp_display_render_collection($ids, ["template" => $atts["template"], "title" => "Verified Profiles"]) : "<style>.display_single_post_mentioned_in_article{display:none!important}</style>";
    }
}

if (! function_exists(__NAMESPACE__ . "\\display_profiles_featured_in_single_post")) {
    function display_profiles_featured_in_single_post(): string {
        return display_single_post_mentioned_in_article([]);
    }
}
