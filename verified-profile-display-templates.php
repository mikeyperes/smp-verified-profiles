<?php

namespace smp_verified_profiles;

defined("ABSPATH") || exit;

const SMP_VP_DISPLAY_OPTION = "smp_vp_display_card_settings";
const SMP_VP_DISPLAY_NONCE = "smp_vp_display_nonce";
const SMP_VP_PAGES_NONCE = "smp_vp_pages_nonce";

add_filter("smp_vp_dashboard_tabs", __NAMESPACE__ . "\\smp_vp_display_dashboard_tab");
add_filter("smp_vp_dashboard_tabs", __NAMESPACE__ . "\\smp_vp_pages_dashboard_tab");
add_filter("smp_vp_render_dashboard_tab", __NAMESPACE__ . "\\smp_vp_render_display_dashboard_tab", 10, 2);
add_filter("smp_vp_render_dashboard_tab", __NAMESPACE__ . "\\smp_vp_render_pages_dashboard_tab", 10, 2);
add_action("wp_ajax_smp_vp_display_save_settings", __NAMESPACE__ . "\\smp_vp_ajax_display_save_settings");
add_action("wp_ajax_smp_vp_display_import_elementor", __NAMESPACE__ . "\\smp_vp_ajax_display_import_elementor");
add_action("wp_ajax_smp_vp_display_create_loop_item", __NAMESPACE__ . "\\smp_vp_ajax_display_create_loop_item");
add_action("wp_ajax_smp_vp_display_save_loop_item", __NAMESPACE__ . "\\smp_vp_ajax_display_save_loop_item");
add_action("wp_ajax_smp_vp_display_delete_loop_item", __NAMESPACE__ . "\\smp_vp_ajax_display_delete_loop_item");
add_action("admin_init", __NAMESPACE__ . "\\smp_vp_register_pages_ajax");
add_filter("the_content", __NAMESPACE__ . "\\smp_vp_display_append_to_content", 28);
add_shortcode("verified_profiles_loop", __NAMESPACE__ . "\\smp_vp_verified_profiles_loop_shortcode");
add_shortcode("smp_verified_profiles_loop", __NAMESPACE__ . "\\smp_vp_verified_profiles_loop_shortcode");
add_shortcode("display_homepage_profiles", __NAMESPACE__ . "\\display_homepage_profiles");
add_shortcode("display_single_post_mentioned_in_article", __NAMESPACE__ . "\\display_single_post_mentioned_in_article");
add_shortcode("display_profiles_featured_in_single_post", __NAMESPACE__ . "\\display_profiles_featured_in_single_post");

function smp_vp_display_dashboard_tab(array $tabs): array {
    $tabs["features"] = "Features";
    return $tabs;
}

function smp_vp_pages_dashboard_tab(array $tabs): array {
    $tabs["pages"] = "Pages";
    return $tabs;
}

function smp_vp_render_display_dashboard_tab($rendered, string $tab_id) {
    if (! in_array($tab_id, ["features", "display-cards"], true)) {
        return $rendered;
    }

    smp_vp_display_render_settings();
    return true;
}

function smp_vp_render_pages_dashboard_tab($rendered, string $tab_id) {
    if ($tab_id !== "pages") {
        return $rendered;
    }

    smp_vp_render_pages_dashboard();
    return true;
}

function smp_vp_pages_actions(): array {
    return [
        "assign_page" => "smp_vp_pages_assign_page",
        "create_page" => "smp_vp_pages_create_page",
        "delete_page" => "smp_vp_pages_delete_page",
        "save_template" => "smp_vp_pages_save_template",
        "apply_template" => "smp_vp_pages_apply_template",
        "page_details" => "smp_vp_pages_page_details",
        "update_page_slug" => "smp_vp_pages_update_page_slug",
    ];
}

function smp_vp_pages_definitions(): array {
    return [
        "profiles_archive" => [
            "title" => "Profiles",
            "slug" => "profiles",
            "template" => true,
            "children" => [],
        ],
    ];
}

function smp_vp_pages_default_templates(): array {
    return [
        "profiles_archive" => "<!-- wp:shortcode -->\n[verified_profiles_loop id=\"homepage\"]\n<!-- /wp:shortcode -->",
    ];
}

function smp_vp_pages_manager() {
    if (! class_exists("\\Hexa\\PluginCore\\SiteStructure\\PageStructureManager")) {
        return null;
    }

    return new \Hexa\PluginCore\SiteStructure\PageStructureManager([
        "pages" => smp_vp_pages_definitions(),
        "default_templates" => smp_vp_pages_default_templates(),
        "managed_meta_key" => "_smp_vp_managed_page",
        "managed_key_meta_key" => "_smp_vp_page_key",
        "created_page_status" => "publish",
        "select_post_statuses" => ["publish", "draft", "private"],
        "assignment_statuses" => ["publish", "draft", "private"],
        "reuse_existing_pages" => true,
        "assignment_getter" => __NAMESPACE__ . "\\smp_vp_pages_get_assignment",
        "assignment_saver" => __NAMESPACE__ . "\\smp_vp_pages_save_assignment",
        "assignment_deleter" => __NAMESPACE__ . "\\smp_vp_pages_delete_assignment",
        "template_getter" => __NAMESPACE__ . "\\smp_vp_pages_get_template",
        "template_saver" => __NAMESPACE__ . "\\smp_vp_pages_save_template",
        "page_detail_renderer" => __NAMESPACE__ . "\\smp_vp_pages_detail_html",
    ]);
}

function smp_vp_register_pages_ajax(): void {
    static $registered = false;
    if ($registered || ! current_user_can("manage_options")) {
        return;
    }

    $manager = smp_vp_pages_manager();
    if (! $manager || ! class_exists("\\Hexa\\PluginCore\\SiteStructure\\SiteStructureAjaxController")) {
        return;
    }

    (new \Hexa\PluginCore\SiteStructure\SiteStructureAjaxController($manager, [
        "capability" => "manage_options",
        "nonce_action" => SMP_VP_PAGES_NONCE,
        "actions" => smp_vp_pages_actions(),
    ]))->register();

    $registered = true;
}

function smp_vp_render_pages_dashboard(): void {
    if (! current_user_can("manage_options")) {
        ?><div class="notice notice-error"><p>Insufficient permissions.</p></div><?php
        return;
    }

    $manager = smp_vp_pages_manager();
    if (! $manager || ! class_exists("\\Hexa\\PluginCore\\SiteStructure\\SiteStructureRenderer")) {
        ?><div class="notice notice-error"><p>Hexa WP Core Site Structure is unavailable.</p></div><?php
        return;
    }

    ?>
    <div class="smp-vp-pages-admin">
        <style>
            .smp-vp-pages-admin{max-width:1260px}.smp-vp-pages-admin .smp-vp-pages-intro{background:#fff;border:1px solid #dcdcde;border-radius:10px;margin:16px 0;padding:18px 20px}.smp-vp-pages-admin .smp-vp-pages-intro h2{margin:0 0 6px}.smp-vp-pages-admin .smp-vp-pages-intro p{color:#646970;margin:0}.smp-vp-pages-admin .hpc-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;margin:16px 0;padding:18px 20px}.smp-vp-pages-admin .hpc-table{border-collapse:collapse;width:100%}.smp-vp-pages-admin .hpc-table th,.smp-vp-pages-admin .hpc-table td{border-top:1px solid #e5e7eb;padding:12px;text-align:left;vertical-align:middle}.smp-vp-pages-admin .hpc-table thead th{border-top:0;color:#1d2327;font-weight:800}.smp-vp-pages-admin .hpc-card-header{align-items:center;display:flex;gap:8px;margin-bottom:14px}.smp-vp-pages-admin .hpc-card-header h3{font-size:18px;margin:0}
        </style>
        <div class="smp-vp-pages-intro">
            <h2>Verified Profiles Pages</h2>
            <p>Assign or create the canonical Profiles archive page. The Features tab reads its archive URL from this assignment.</p>
        </div>
        <?php
        echo (new \Hexa\PluginCore\SiteStructure\SiteStructureRenderer($manager, [
            "instance_id" => "smp-vp-pages-structure",
            "nonce" => wp_create_nonce(SMP_VP_PAGES_NONCE),
            "card_class" => "hpc-card",
            "table_class" => "hpc-table",
            "show_pages" => true,
            "show_menus" => false,
            "show_page_details" => true,
            "enable_templates" => true,
            "enable_template_editors" => true,
            "template_editor_rows" => 5,
            "actions" => smp_vp_pages_actions(),
            "labels" => [
                "pages_title" => "Verified Profiles Pages",
                "pages_heading" => "Required Pages",
                "pages_description" => "Create or assign the pages used by the Verified Profiles plugin. Do not type these URLs manually in Features.",
            ],
        ]))->render();
        ?>
    </div>
    <?php
}

function smp_vp_pages_get_assignment(string $page_key): int {
    $settings = smp_vp_display_settings();
    return absint($settings["pages"][$page_key] ?? 0);
}

function smp_vp_pages_save_assignment(string $page_key, int $page_id): void {
    $settings = smp_vp_display_settings();
    $settings["pages"] = is_array($settings["pages"] ?? null) ? $settings["pages"] : [];
    if ($page_id > 0) {
        $settings["pages"][$page_key] = $page_id;
    } else {
        unset($settings["pages"][$page_key]);
    }

    if ($page_key === "profiles_archive") {
        $settings["archive_url"] = $page_id > 0 ? (get_permalink($page_id) ?: home_url("/profiles/")) : home_url("/profiles/");
    }

    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
}

function smp_vp_pages_delete_assignment(string $page_key): void {
    smp_vp_pages_save_assignment($page_key, 0);
}

function smp_vp_pages_get_template(string $page_key): string {
    $settings = smp_vp_display_settings();
    $templates = is_array($settings["page_templates"] ?? null) ? $settings["page_templates"] : [];
    return (string) ($templates[$page_key] ?? (smp_vp_pages_default_templates()[$page_key] ?? ""));
}

function smp_vp_pages_save_template(string $page_key, string $template): void {
    $settings = smp_vp_display_settings();
    $settings["page_templates"] = is_array($settings["page_templates"] ?? null) ? $settings["page_templates"] : [];
    $settings["page_templates"][$page_key] = wp_kses_post($template);
    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
}

function smp_vp_pages_detail_html(int $page_id): string {
    if ($page_id <= 0) {
        return "";
    }

    $url = get_permalink($page_id);
    if (! $url) {
        return "";
    }

    return '<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;"><strong>Live URL:</strong><a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($url) . '</a><code>[verified_profiles_loop id="homepage"]</code></div>';
}

function smp_vp_display_archive_url(array $settings = []): string {
    if (! $settings) {
        $stored = get_option(SMP_VP_DISPLAY_OPTION, []);
        $settings = is_array($stored) ? $stored : [];
    }

    $page_id = absint($settings["pages"]["profiles_archive"] ?? 0);
    if ($page_id > 0) {
        $url = get_permalink($page_id);
        if ($url) {
            return $url;
        }
    }

    return esc_url_raw((string) ($settings["archive_url"] ?? home_url("/profiles/")));
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

function smp_vp_display_contexts(): array {
    return [
        "homepage" => "Homepage",
        "author" => "Author page",
        "single" => "Single page",
    ];
}

function smp_vp_display_bool($value): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value === 1;
    }

    return in_array(strtolower((string) $value), ["1", "true", "yes", "on"], true);
}

function smp_vp_display_number($value, int $fallback, int $min, int $max): int {
    $number = absint($value);
    if (! $number) {
        $number = $fallback;
    }

    return max($min, min($max, $number));
}

function smp_vp_display_default_items_per_row(string $template): int {
    $defaults = [
        "boxed-row" => 3,
        "centered-stack" => 3,
        "directory-list" => 1,
        "minimal-quiet" => 4,
        "accent-rule" => 2,
    ];

    return $defaults[$template] ?? 3;
}

function smp_vp_display_loop_rows_from_limit(int $limit, int $items_per_row): int {
    $items_per_row = max(1, $items_per_row);
    return max(1, min(8, (int) ceil(max(1, $limit) / $items_per_row)));
}

function smp_vp_display_loop_limit(int $rows, int $items_per_row): int {
    return max(1, min(48, max(1, $rows) * max(1, $items_per_row)));
}

function smp_vp_display_loop_item_defaults(array $settings, string $id = ""): array {
    $template = $settings["homepage_template"] ?? "boxed-row";
    $items_per_row = smp_vp_display_default_items_per_row($template);
    $source_limit = smp_vp_display_number($settings["profile_limit"] ?? 7, 7, 1, 48);
    $rows = smp_vp_display_loop_rows_from_limit($source_limit, $items_per_row);

    return [
        "id" => $id,
        "label" => $id ? ucwords(str_replace(["-", "_"], " ", $id)) : "Verified Profiles Loop",
        "context" => "homepage",
        "template" => $template,
        "rows" => $rows,
        "items_per_row" => $items_per_row,
        "limit" => smp_vp_display_loop_limit($rows, $items_per_row),
        "require_thumbnail" => true,
        "primary_color" => $settings["primary_color"] ?? "#b3272d",
        "secondary_color" => $settings["secondary_color"] ?? ($settings["ink_color"] ?? "#151515"),
        "name_font_size" => smp_vp_display_number($settings["name_font_size"] ?? 18, 18, 12, 42),
        "role_font_size" => smp_vp_display_number($settings["role_font_size"] ?? 10, 10, 8, 24),
        "author_id" => 0,
    ];
}

function smp_vp_display_default_loop_items(array $settings): array {
    $homepage = array_replace(smp_vp_display_loop_item_defaults($settings, "homepage"), [
        "label" => "Homepage",
        "context" => "homepage",
        "template" => $settings["homepage_template"] ?? "boxed-row",
    ]);
    $homepage["items_per_row"] = smp_vp_display_default_items_per_row($homepage["template"]);
    $homepage["rows"] = smp_vp_display_loop_rows_from_limit((int) $homepage["limit"], (int) $homepage["items_per_row"]);
    $homepage["limit"] = smp_vp_display_loop_limit((int) $homepage["rows"], (int) $homepage["items_per_row"]);

    $single = array_replace(smp_vp_display_loop_item_defaults($settings, "single-post"), [
        "label" => "Single Post",
        "context" => "single",
        "template" => $settings["post_template"] ?? "directory-list",
    ]);
    $single["items_per_row"] = smp_vp_display_default_items_per_row($single["template"]);
    $single["rows"] = smp_vp_display_loop_rows_from_limit((int) $single["limit"], (int) $single["items_per_row"]);
    $single["limit"] = smp_vp_display_loop_limit((int) $single["rows"], (int) $single["items_per_row"]);

    return [
        "homepage" => $homepage,
        "single-post" => $single,
    ];
}

function smp_vp_display_unique_loop_id(string $base, array $items): string {
    $base = sanitize_key(sanitize_title($base));
    if ($base === "") {
        $base = "loop-item";
    }

    $candidate = $base;
    $suffix = 2;
    while (isset($items[$candidate])) {
        $candidate = $base . "-" . $suffix;
        $suffix++;
    }

    return $candidate;
}

function smp_vp_display_sanitize_loop_item(array $input, array $settings, string $fallback_id = ""): array {
    $templates = array_keys(smp_vp_display_templates());
    $contexts = array_keys(smp_vp_display_contexts());
    $id = sanitize_key((string) ($input["id"] ?? $fallback_id));
    if ($id === "") {
        $id = smp_vp_display_unique_loop_id((string) ($input["label"] ?? "loop-item"), []);
    }

    $defaults = smp_vp_display_loop_item_defaults($settings, $id);
    $context = sanitize_key((string) ($input["context"] ?? $defaults["context"]));
    if (! in_array($context, $contexts, true)) {
        $context = $defaults["context"];
    }

    $fallback_template = $context === "single" ? ($settings["post_template"] ?? $defaults["template"]) : ($settings["homepage_template"] ?? $defaults["template"]);
    $template = sanitize_key((string) ($input["template"] ?? $fallback_template));
    if (! in_array($template, $templates, true)) {
        $template = in_array($fallback_template, $templates, true) ? $fallback_template : "boxed-row";
    }

    $label = sanitize_text_field((string) ($input["label"] ?? $defaults["label"]));
    if ($label === "") {
        $label = $defaults["label"];
    }

    $primary = sanitize_hex_color((string) ($input["primary_color"] ?? $defaults["primary_color"]));
    $secondary = sanitize_hex_color((string) ($input["secondary_color"] ?? $defaults["secondary_color"]));
    $fallback_items_per_row = smp_vp_display_default_items_per_row($template);
    $items_per_row = smp_vp_display_number($input["items_per_row"] ?? $input["per_row"] ?? $fallback_items_per_row, $fallback_items_per_row, 1, 6);
    $legacy_limit = smp_vp_display_number($input["limit"] ?? $defaults["limit"], (int) ($defaults["limit"] ?? 7), 1, 48);
    $rows = smp_vp_display_number($input["rows"] ?? smp_vp_display_loop_rows_from_limit($legacy_limit, $items_per_row), smp_vp_display_loop_rows_from_limit($legacy_limit, $items_per_row), 1, 8);
    $limit = smp_vp_display_loop_limit($rows, $items_per_row);

    return [
        "id" => $id,
        "label" => $label,
        "context" => $context,
        "template" => $template,
        "rows" => $rows,
        "items_per_row" => $items_per_row,
        "limit" => $limit,
        "require_thumbnail" => smp_vp_display_bool($input["require_thumbnail"] ?? $defaults["require_thumbnail"]),
        "primary_color" => $primary ?: $defaults["primary_color"],
        "secondary_color" => $secondary ?: $defaults["secondary_color"],
        "name_font_size" => smp_vp_display_number($input["name_font_size"] ?? $defaults["name_font_size"], (int) $defaults["name_font_size"], 12, 42),
        "role_font_size" => smp_vp_display_number($input["role_font_size"] ?? $defaults["role_font_size"], (int) $defaults["role_font_size"], 8, 24),
        "author_id" => absint($input["author_id"] ?? $defaults["author_id"]),
    ];
}

function smp_vp_display_sanitize_loop_items($items, array $settings, bool $include_defaults = true): array {
    $normalized = [];
    if (is_array($items)) {
        foreach ($items as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $fallback_id = is_string($key) ? $key : (string) ($item["id"] ?? "");
            $sanitized = smp_vp_display_sanitize_loop_item($item, $settings, $fallback_id);
            $normalized[$sanitized["id"]] = $sanitized;
        }
    }

    if ($include_defaults) {
        foreach (smp_vp_display_default_loop_items($settings) as $id => $item) {
            if (empty($normalized[$id])) {
                $normalized[$id] = $item;
            }
        }
    }

    return $normalized;
}

function smp_vp_display_defaults(): array {
    return [
        "enabled" => true,
        "append_to_content" => true,
        "single_injection" => "after_content",
        "homepage_template" => "boxed-row",
        "post_template" => "directory-list",
        "profile_limit" => 7,
        "require_thumbnail" => true,
        "archive_url" => home_url("/profiles/"),
        "primary_color" => "#b3272d",
        "secondary_color" => "#151515",
        "ink_color" => "#151515",
        "muted_color" => "#747474",
        "line_color" => "#e6e1de",
        "soft_color" => "#faf7f5",
        "name_font_size" => 18,
        "role_font_size" => 10,
        "loop_items" => [],
        "pages" => [],
        "page_templates" => [],
    ];
}

function smp_vp_display_settings(): array {
    $stored = get_option(SMP_VP_DISPLAY_OPTION, []);
    $settings = array_replace(smp_vp_display_defaults(), is_array($stored) ? $stored : []);
    if (empty($settings["single_injection"])) {
        $settings["single_injection"] = ! empty($settings["append_to_content"]) ? "after_content" : "shortcode";
    }
    $settings["append_to_content"] = $settings["single_injection"] === "after_content";
    $settings["pages"] = is_array($settings["pages"] ?? null) ? $settings["pages"] : [];
    $settings["page_templates"] = is_array($settings["page_templates"] ?? null) ? $settings["page_templates"] : [];
    $settings["archive_url"] = smp_vp_display_archive_url($settings);
    $settings["loop_items"] = smp_vp_display_sanitize_loop_items($settings["loop_items"] ?? [], $settings, true);
    return $settings;
}

function smp_vp_display_sanitize(array $input): array {
    $settings = smp_vp_display_settings();
    $templates = array_keys(smp_vp_display_templates());

    $settings["enabled"] = ! empty($input["enabled"]);
    $single_injection = sanitize_key((string) ($input["single_injection"] ?? (! empty($input["append_to_content"]) ? "after_content" : "shortcode")));
    if (! in_array($single_injection, ["after_content", "shortcode"], true)) {
        $single_injection = "after_content";
    }
    $settings["single_injection"] = $single_injection;
    $settings["append_to_content"] = $single_injection === "after_content";
    $settings["require_thumbnail"] = ! empty($input["require_thumbnail"]);
    $settings["homepage_template"] = in_array((string) ($input["homepage_template"] ?? ""), $templates, true) ? (string) $input["homepage_template"] : $settings["homepage_template"];
    $settings["post_template"] = in_array((string) ($input["post_template"] ?? ""), $templates, true) ? (string) $input["post_template"] : $settings["post_template"];
    $settings["profile_limit"] = smp_vp_display_number($input["profile_limit"] ?? $settings["profile_limit"], (int) $settings["profile_limit"], 1, 30);
    $settings["archive_url"] = smp_vp_display_archive_url($settings);
    $settings["name_font_size"] = smp_vp_display_number($input["name_font_size"] ?? $settings["name_font_size"], (int) $settings["name_font_size"], 12, 42);
    $settings["role_font_size"] = smp_vp_display_number($input["role_font_size"] ?? $settings["role_font_size"], (int) $settings["role_font_size"], 8, 24);

    foreach (["primary_color", "secondary_color", "ink_color", "muted_color", "line_color", "soft_color"] as $key) {
        $color = sanitize_hex_color((string) ($input[$key] ?? $settings[$key]));
        if ($color) {
            $settings[$key] = $color;
        }
    }

    $settings["loop_items"] = smp_vp_display_sanitize_loop_items($input["loop_items"] ?? $settings["loop_items"], $settings, true);

    return $settings;
}


function smp_vp_display_color_control(string $key, string $label, string $value, array $args = []): string {
    $default = sanitize_hex_color((string) ($args["default"] ?? $value)) ?: "#000000";
    $value = sanitize_hex_color($value) ?: $default;
    $id = (string) ($args["id"] ?? "smp-vp-" . str_replace("_", "-", $key));
    $description = (string) ($args["description"] ?? "");
    $control_class = trim("smp-vp-color-control " . (string) ($args["control_class"] ?? ""));
    $hex_input_class = trim("smp-vp-color-hex " . (string) ($args["hex_input_class"] ?? ""));
    $picker_class = trim("smp-vp-color-picker " . (string) ($args["picker_class"] ?? ""));

    if (class_exists("\Hexa\PluginCore\WpAdminComponents\ColorControl")) {
        return \Hexa\PluginCore\WpAdminComponents\ColorControl::render([
            "key" => $key,
            "label" => $label,
            "description" => $description,
            "value" => $value,
            "default" => $default,
            "id" => $id,
            "control_class" => $control_class,
            "hex_input_class" => $hex_input_class,
            "picker_class" => $picker_class,
        ]);
    }

    ob_start();
    ?>
    <div class="<?php echo esc_attr($control_class); ?> smp-vp-color-control-fallback" data-key="<?php echo esc_attr($key); ?>">
        <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
        <?php if ($description !== "") : ?><p><?php echo esc_html($description); ?></p><?php endif; ?>
        <input id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($hex_input_class); ?>" data-key="<?php echo esc_attr($key); ?>" type="text" value="<?php echo esc_attr($value); ?>" pattern="#?[0-9a-fA-F]{6}">
        <span class="smp-vp-color-fallback-swatch" style="background:<?php echo esc_attr($value); ?>"></span>
    </div>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_display_detailed_color_picker(array $args): string {
    $primary = (array) ($args["primary"] ?? []);
    $secondary = (array) ($args["secondary"] ?? []);
    if (class_exists("\Hexa\PluginCore\WpAdminComponents\DetailedColorPicker")) {
        return \Hexa\PluginCore\WpAdminComponents\DetailedColorPicker::render([
            "id" => (string) ($args["id"] ?? "smp-vp-detailed-color-picker"),
            "title" => (string) ($args["title"] ?? "Detailed Color Picker"),
            "description" => (string) ($args["description"] ?? ""),
            "show_primary" => array_key_exists("show_primary", $args) ? (bool) $args["show_primary"] : true,
            "show_secondary" => array_key_exists("show_secondary", $args) ? (bool) $args["show_secondary"] : true,
            "show_elementor_import" => array_key_exists("show_elementor_import", $args) ? (bool) $args["show_elementor_import"] : true,
            "show_fonts" => array_key_exists("show_fonts", $args) ? (bool) $args["show_fonts"] : false,
            "fonts" => (array) ($args["fonts"] ?? []),
            "primary" => $primary,
            "secondary" => $secondary,
        ]);
    }

    return '<div class="smp-vp-detailed-color-fallback">'
        . smp_vp_display_color_control((string) ($primary["key"] ?? "primary_color"), (string) ($primary["label"] ?? "Primary color"), (string) ($primary["value"] ?? "#b3272d"), $primary)
        . smp_vp_display_color_control((string) ($secondary["key"] ?? "secondary_color"), (string) ($secondary["label"] ?? "Secondary color"), (string) ($secondary["value"] ?? "#151515"), $secondary)
        . '</div>';
}

function smp_vp_display_color_palette(array $settings): string {
    $colors = [];
    foreach (["primary_color" => "Primary color", "secondary_color" => "Secondary color", "ink_color" => "Text", "muted_color" => "Muted", "line_color" => "Line", "soft_color" => "Soft background"] as $key => $label) {
        $colors[] = [
            "key" => $key,
            "label" => $label,
            "value" => (string) ($settings[$key] ?? "#000000"),
            "id" => "smp-vp-" . str_replace("_", "-", $key),
            "control_class" => "smp-vp-global-color-control",
            "hex_input_class" => "smp-vp-global-color smp-vp-global-" . str_replace("_", "-", $key),
            "picker_class" => "smp-vp-global-color-picker",
        ];
    }

    if (class_exists("\Hexa\PluginCore\WpAdminComponents\ColorPalette")) {
        return \Hexa\PluginCore\WpAdminComponents\ColorPalette::render([
            "id" => "smp-vp-card-palette",
            "title" => "Colors",
            "description" => "Card palette for the verified-profiles display. Primary and secondary are seeded from Hexa WP Core. Edit any value and Save.",
            "colors" => $colors,
            "elementor_detector" => [
                "id" => "smp-vp-elementor-palette",
                "title" => "Elementor palette",
                "button_label" => "Load Elementor colors",
                "description" => "Reference only. This never changes your saved colors. Load your Elementor site colors, then use Copy hex to paste any value into a field above.",
                "empty_label" => "Click \"Load Elementor colors\" to show your Elementor palette.",
            ],
        ]);
    }

    ob_start();
    ?>
    <section class="smp-vp-section">
        <h3 class="smp-vp-section-title">Colors</h3>
        <p class="smp-vp-section-note">Card palette for the verified-profiles display. Primary and secondary are seeded from Hexa WP Core. Edit any value and Save.</p>
        <div class="smp-vp-color-list">
            <?php foreach ($colors as $color) : ?>
                <div class="smp-vp-color-field">
                    <?php echo smp_vp_display_color_control((string) $color["key"], (string) $color["label"], (string) $color["value"], $color); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_display_css(): string {
    return ".smp-vp-display{--vp-red:#b3272d;--vp-secondary:#151515;--vp-ink:#151515;--vp-muted:#747474;--vp-line:#e6e1de;--vp-soft:#faf7f5;--vp-items-per-row:3;container-type:inline-size;max-width:none;margin:0;padding:0}.smp-vp-display .vp-role,.smp-vp-display .vp-cta{font-size:var(--vp-role-size,11px);letter-spacing:.14em;text-transform:uppercase}.smp-vp-display .vp-role{color:var(--vp-muted)}.smp-vp-display a{text-decoration:none}.smp-vp-display svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2}.smp-vp-display .vp-av{position:relative;flex-shrink:0}.smp-vp-display .vp-av img{border-radius:50%;object-fit:cover;filter:grayscale(100%);display:block;background:#f3f3f3}.smp-vp-display .vp-card:hover img{filter:grayscale(0)}.smp-vp-display .vp-badge{position:absolute;right:-3px;bottom:-3px;width:20px;height:20px;background:#fff;border-radius:50%;color:var(--vp-red);display:flex;align-items:center;justify-content:center}.smp-vp-display .vp-name{font-family:Playfair Display,Georgia,serif;font-weight:700;font-size:var(--vp-name-size,18px);color:var(--vp-ink);line-height:1.2}.smp-vp-display .vp-card:hover .vp-name,.smp-vp-display .vp-cta{color:var(--vp-red)}.smp-vp-display .vp-a,.smp-vp-display .vp-b,.smp-vp-display .vp-d,.smp-vp-display .vp-e{display:grid;grid-template-columns:repeat(var(--vp-items-per-row,3),minmax(0,1fr));gap:20px;padding:0}.smp-vp-display .vp-c{display:grid;grid-template-columns:repeat(var(--vp-items-per-row,1),minmax(0,1fr));gap:0 24px;padding:0}.smp-vp-display .vp-d{gap:6px 28px}.smp-vp-display .vp-a .vp-card{display:flex;align-items:center;gap:18px;border:1px solid var(--vp-line);background:#fff;padding:20px}.smp-vp-display .vp-b .vp-card{display:flex;flex-direction:column;align-items:center;text-align:center;border:1px solid var(--vp-line);background:#fff;padding:30px 20px}.smp-vp-display .vp-c .vp-card{display:flex;align-items:flex-start;gap:18px;padding:18px 4px;border-bottom:1px solid var(--vp-line)}.smp-vp-display .vp-c .vp-meta{flex:1}.smp-vp-display .vp-d .vp-card{display:flex;align-items:flex-start;gap:14px;padding:18px 2px;border-top:1px solid var(--vp-line)}.smp-vp-display .vp-e .vp-card{display:flex;align-items:center;gap:20px;background:var(--vp-soft);border-left:3px solid var(--vp-red);padding:22px 24px}.smp-vp-display .vp-a img{width:66px;height:66px}.smp-vp-display .vp-b img{width:84px;height:84px}.smp-vp-display .vp-c img{width:58px;height:58px}.smp-vp-display .vp-d img{width:52px;height:52px}.smp-vp-display .vp-e img{width:88px;height:88px}@container (max-width:1100px){.smp-vp-display .vp-a,.smp-vp-display .vp-b,.smp-vp-display .vp-c,.smp-vp-display .vp-d,.smp-vp-display .vp-e{grid-template-columns:repeat(2,minmax(0,1fr))}}@container (max-width:620px){.smp-vp-display .vp-a,.smp-vp-display .vp-b,.smp-vp-display .vp-c,.smp-vp-display .vp-d,.smp-vp-display .vp-e{grid-template-columns:1fr}}@media(max-width:900px){.smp-vp-display .vp-a,.smp-vp-display .vp-b,.smp-vp-display .vp-c,.smp-vp-display .vp-d,.smp-vp-display .vp-e{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.smp-vp-display .vp-a,.smp-vp-display .vp-b,.smp-vp-display .vp-c,.smp-vp-display .vp-d,.smp-vp-display .vp-e{grid-template-columns:1fr}}";
}

function smp_vp_display_empty_section_assets(): string {
    return '<style>.smp-vp-hide-if-empty:has([data-smp-vp-empty-loop]){display:none!important}</style><script>(function(){function run(){document.querySelectorAll(".smp-vp-hide-if-empty").forEach(function(section){if(section.querySelector("[data-smp-vp-empty-loop]")){section.style.display="none";}});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",run);}else{run();}})();</script>';
}

function smp_vp_display_empty_loop_marker(string $id): string {
    return smp_vp_display_empty_section_assets() . '<span class="smp-vp-empty-loop-marker verified-profiles-loop-' . esc_attr($id) . '" data-smp-vp-empty-loop="' . esc_attr($id) . '" hidden></span>';
}

function smp_vp_display_log_failure(string $context, \Throwable $error): void {
    if (function_exists('error_log')) {
        error_log('[smp-verified-profiles] ' . $context . ' failed: ' . $error->getMessage());
    }
}

function smp_vp_display_render_settings(): void {
    if (! current_user_can("manage_options")) {
        ?><div class="notice notice-error"><p>Insufficient permissions.</p></div><?php
        return;
    }

    $settings = smp_vp_display_settings();
    $templates = smp_vp_display_templates();
    $contexts = smp_vp_display_contexts();
    $labels = array_map(static function ($template) {
        return $template["label"];
    }, $templates);
    $nonce = wp_create_nonce(SMP_VP_DISPLAY_NONCE);
    $preview = smp_vp_display_preview_profiles();
    ?>
    <style>
        <?php echo smp_vp_display_css(); ?>
        .smp-vp-display-admin{max-width:1260px;color:#1d2327}.smp-vp-display-admin *{box-sizing:border-box}.smp-vp-display-admin .smp-vp-panel{background:#fff;border:1px solid #dcdcde;border-radius:10px;margin:16px 0;overflow:hidden}.smp-vp-display-admin .smp-vp-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid #eceff3}.smp-vp-display-admin .smp-vp-panel-head h2{margin:0 0 4px;font-size:20px;line-height:1.2}.smp-vp-display-admin .smp-vp-panel-head p{margin:0;color:#646970}.smp-vp-display-admin .smp-vp-settings-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;padding:18px 20px}.smp-vp-display-admin label{font-weight:700;display:block;margin-bottom:6px}.smp-vp-display-admin select,.smp-vp-display-admin input[type=text]:not(.hpc-color-hex-input),.smp-vp-display-admin input[type=number]{width:100%;min-height:38px}.smp-vp-display-admin input[type=color]:not(.hpc-color-picker){width:100%;height:38px;padding:2px}.smp-vp-color-field,.smp-vp-loop-color-slot{min-width:0}.smp-vp-display-admin .hpc-color-control{min-width:0}.smp-vp-display-admin .hpc-color-control h3{font-size:12px;margin:0}.smp-vp-display-admin .hpc-color-control p{font-size:12px}.smp-vp-display-admin .hpc-color-row{gap:8px}.smp-vp-display-admin .hpc-color-picker-shell,.smp-vp-display-admin .hpc-color-hex-shell,.smp-vp-display-admin .hpc-color-value{display:grid;margin-bottom:0}.smp-vp-display-admin .hpc-button{min-height:36px}.smp-vp-color-fallback-swatch{border:1px solid #cbd5e1;border-radius:8px;display:inline-block;height:34px;margin-left:8px;vertical-align:middle;width:34px}.smp-vp-display-admin .smp-vp-checks{display:flex;gap:16px;flex-wrap:wrap;padding:0 20px 18px}.smp-vp-display-admin .smp-vp-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:16px 20px;background:#f6f7f7;border-top:1px solid #eceff3}.smp-vp-display-admin .smp-vp-log{min-height:38px;min-width:280px;border:1px solid #dcdcde;background:#fff;border-radius:6px;padding:9px 12px;font-family:Menlo,Consolas,monospace;color:#3c434a}.smp-vp-display-admin .smp-vp-current{display:flex;gap:10px;flex-wrap:wrap}.smp-vp-display-admin .smp-vp-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;background:#f0f6fc;color:#0a4b78;font-weight:700;padding:7px 11px}.smp-vp-template-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;padding:20px}.smp-vp-template-card{border:1px solid #dcdcde;border-radius:10px;background:#fff;overflow:hidden;transition:border-color .16s ease,box-shadow .16s ease}.smp-vp-template-card.is-homepage,.smp-vp-template-card.is-post{border-color:#b3272d;box-shadow:0 0 0 1px rgba(179,39,45,.18)}.smp-vp-template-card-head{display:flex;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #eef0f3}.smp-vp-template-card h3{margin:0;font-size:16px}.smp-vp-template-card p{margin:4px 0 0;color:#646970}.smp-vp-template-badges{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}.smp-vp-template-badge{display:none;border-radius:999px;background:#f7e5e7;color:#8a1b20;font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;padding:5px 8px;white-space:nowrap}.smp-vp-template-card.is-homepage .smp-vp-template-badge-home,.smp-vp-template-card.is-post .smp-vp-template-badge-post{display:inline-flex}.smp-vp-preview-frame{padding:18px;background:#fbfaf9;min-height:190px;border-bottom:1px solid #eef0f3}.smp-vp-preview-frame .smp-vp-display{max-width:none;margin:0;padding:0}.smp-vp-preview-frame .smp-vp-display .vp-a,.smp-vp-preview-frame .smp-vp-display .vp-b,.smp-vp-preview-frame .smp-vp-display .vp-d,.smp-vp-preview-frame .smp-vp-display .vp-e{grid-template-columns:minmax(0,1fr);padding:12px 0}.smp-vp-preview-frame .smp-vp-display .vp-c{padding:12px 0}.smp-vp-template-actions{display:flex;gap:10px;flex-wrap:wrap;padding:14px 18px}.smp-vp-template-action.is-active{background:#b3272d;border-color:#b3272d;color:#fff}.smp-vp-display-admin .screen-reader-selects{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}@media(max-width:980px){.smp-vp-template-grid,.smp-vp-display-admin .smp-vp-settings-grid{grid-template-columns:1fr}}
        .smp-vp-display-admin .smp-vp-sections{display:grid;gap:20px;padding:20px}
        .smp-vp-display-admin .smp-vp-section{border:1px solid #e6e9ee;border-radius:10px;background:#fff;padding:16px 18px}
        .smp-vp-display-admin .smp-vp-section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
        .smp-vp-display-admin .smp-vp-section-title{font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#1d2327;margin:0 0 14px}
        .smp-vp-display-admin .smp-vp-section-head .smp-vp-section-title{margin:0}
        .smp-vp-display-admin .smp-vp-section-note{color:#646970;font-size:12.5px;margin:0 0 14px}
        .smp-vp-display-admin .smp-vp-fields{display:grid;gap:14px;max-width:540px}
        .smp-vp-display-admin .hpc-color-head h3{font-size:12px;margin:0;color:#1d2327}
        .smp-vp-display-admin .smp-vp-settings-grid .wide{grid-column:span 2}.smp-vp-display-admin .hpc-detailed-color-picker{height:100%}@media(max-width:980px){.smp-vp-display-admin .smp-vp-settings-grid .wide{grid-column:auto}}
        .smp-vp-loop-section{border-top:1px solid #eceff3;padding:20px}.smp-vp-loop-section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.smp-vp-loop-section-head h3{margin:0 0 4px;font-size:17px}.smp-vp-loop-section-head p{margin:0;color:#646970}.smp-vp-feature-instructions{background:#f8fbff;border:1px solid #cfe0ff;border-left:4px solid #3157d5;border-radius:8px;margin:0 0 16px;padding:13px 15px}.smp-vp-feature-instructions h3{font-size:15px;margin:0 0 6px}.smp-vp-feature-instructions p{color:#3f4d63;margin:0 0 10px}.smp-vp-feature-instructions code{background:#eef0f3;border-radius:5px;display:block;white-space:pre-wrap;padding:10px}.smp-vp-loop-toolbar{display:grid;grid-template-columns:minmax(180px,1fr) 180px 220px auto;gap:10px;align-items:end;background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px;padding:14px;margin-bottom:16px}.smp-vp-loop-toolbar select,.smp-vp-loop-card select{width:100%;min-height:38px}.smp-vp-loop-list{display:grid;gap:14px}.smp-vp-loop-card{border:1px solid #dcdcde;border-radius:8px;background:#fff;overflow:hidden}.smp-vp-loop-card-head{display:flex;justify-content:space-between;gap:14px;padding:14px 16px;border-bottom:1px solid #eef0f3}.smp-vp-loop-card-head h4{margin:0;font-size:15px}.smp-vp-loop-card-head p{margin:4px 0 0;color:#646970}.smp-vp-loop-chip{display:inline-flex;border-radius:999px;background:#f0f6fc;color:#0a4b78;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.05em;padding:5px 8px;white-space:nowrap}.smp-vp-loop-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:16px}.smp-vp-loop-grid .wide{grid-column:span 2}.smp-vp-loop-output-count{min-height:38px;border:1px solid #dcdcde;background:#f6f7f7;border-radius:4px;padding:8px 10px;color:#3c434a}.smp-vp-loop-output-count strong{font-size:15px;color:#1d2327}.smp-vp-loop-preview{grid-column:1/-1;border:1px solid #dcdcde;background:#fbfaf9;border-radius:8px;padding:14px;margin-top:2px;overflow:auto}.smp-vp-loop-preview .smp-vp-display{max-width:none;margin:0;padding:0}.smp-vp-loop-preview .smp-vp-display .vp-a,.smp-vp-loop-preview .smp-vp-display .vp-b,.smp-vp-loop-preview .smp-vp-display .vp-c,.smp-vp-loop-preview .smp-vp-display .vp-d,.smp-vp-loop-preview .smp-vp-display .vp-e{grid-template-columns:repeat(var(--vp-items-per-row,3),minmax(0,1fr));padding:10px 0}.smp-vp-loop-preview .smp-vp-display .vp-card{min-height:0}.smp-vp-loop-preview-title{font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#646970;margin-bottom:10px}.smp-vp-loop-card-response{margin:0 16px 14px;padding:11px 13px;border-radius:7px;font-weight:800;border:1px solid transparent}.smp-vp-loop-card-response.is-success{background:#ecfdf3;border-color:#9ad6ad;color:#116329}.smp-vp-loop-card-response.is-error{background:#fff1f0;border-color:#f0aaaa;color:#9f1d1d}.smp-vp-loop-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:14px 16px;background:#f6f7f7;border-top:1px solid #eef0f3}.smp-vp-loop-actions .smp-vp-loop-shortcode{max-width:360px;font-family:Menlo,Consolas,monospace;background:#fff}.smp-vp-loop-copy-status{color:#646970}@media(max-width:980px){.smp-vp-loop-toolbar,.smp-vp-loop-grid{grid-template-columns:1fr}.smp-vp-loop-grid .wide{grid-column:auto}}
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
            <div class="smp-vp-sections">
                <section class="smp-vp-section">
                    <h3 class="smp-vp-section-title">General</h3>
                    <div class="smp-vp-fields">
                        <div><label for="smp-vp-profile-limit">Homepage limit</label><input id="smp-vp-profile-limit" type="number" min="1" max="30" value="<?php echo esc_attr($settings["profile_limit"]); ?>"></div>
                        <div>
                            <label for="smp-vp-single-injection">Single post placement</label>
                            <select id="smp-vp-single-injection">
                                <option value="shortcode" <?php selected($settings["single_injection"], "shortcode"); ?>>Shortcode only - do not auto inject</option>
                                <option value="after_content" <?php selected($settings["single_injection"], "after_content"); ?>>Auto append after article content</option>
                            </select>
                        </div>
                        <div>
                            <label for="smp-vp-archive-url">Archive URL</label>
                            <input id="smp-vp-archive-url" type="text" readonly value="<?php echo esc_attr($settings["archive_url"]); ?>">
                            <p class="smp-vp-section-note">Managed in the Pages tab.</p>
                        </div>
                    </div>
                </section>
                <section class="smp-vp-section">
                    <h3 class="smp-vp-section-title">Typography</h3>
                    <div class="smp-vp-fields">
                        <div><label for="smp-vp-name-font-size">Name font size</label><input id="smp-vp-name-font-size" type="number" min="12" max="32" value="<?php echo esc_attr($settings["name_font_size"] ?? 18); ?>"></div>
                        <div><label for="smp-vp-role-font-size">Role font size</label><input id="smp-vp-role-font-size" type="number" min="8" max="24" value="<?php echo esc_attr($settings["role_font_size"] ?? 10); ?>"></div>
                    </div>
                </section>
                <?php echo smp_vp_display_color_palette($settings); ?>
            </div>
            <div class="smp-vp-checks">
                <label><input id="smp-vp-display-enabled" type="checkbox" <?php checked($settings["enabled"]); ?>> Enable cards</label>
                <label><input id="smp-vp-require-thumb" type="checkbox" <?php checked($settings["require_thumbnail"]); ?>> Require thumbnails</label>
            </div>
            <div class="smp-vp-actions">
                <button type="button" class="button button-primary" id="smp-vp-display-save">Save Feature Settings</button>
                <div class="smp-vp-log" id="smp-vp-display-log">Ready.</div>
            </div>
            <div class="smp-vp-loop-section">
                <div class="smp-vp-loop-section-head">
                    <div>
                        <h3>Loop Items</h3>
                        <p>Create reusable Verified Profiles loops, assign a template, and place the generated shortcode anywhere on the site.</p>
                    </div>
                </div>
                <div class="smp-vp-feature-instructions">
                    <h3>Single-post empty section wrapper</h3>
                    <p>When a template has a header outside the shortcode, wrap the whole header and shortcode in this class so the entire section hides when the post has no attached verified profiles.</p>
                    <code>&lt;section class="smp-vp-hide-if-empty"&gt;
    &lt;h2&gt;This Article&lt;/h2&gt;
    [verified_profiles_loop id="single-post"]
&lt;/section&gt;</code>
                </div>
                <div class="smp-vp-loop-toolbar">
                    <div><label for="smp-vp-new-loop-label">Loop name</label><input id="smp-vp-new-loop-label" type="text" placeholder="Featured founders"></div>
                    <div><label for="smp-vp-new-loop-context">Context</label><select id="smp-vp-new-loop-context">
                        <?php foreach ($contexts as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div><label for="smp-vp-new-loop-template">Starting design</label><select id="smp-vp-new-loop-template">
                        <?php foreach ($templates as $key => $template) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($template["label"]); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <button type="button" class="button button-primary" id="smp-vp-loop-create">Create Loop Item</button>
                </div>
                <div id="smp-vp-loop-items">
                    <?php echo smp_vp_display_render_loop_items_admin($settings); ?>
                </div>
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
                            <?php echo smp_vp_display_render_collection($preview, ["template" => $key]); ?>
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
                    single_injection: $("#smp-vp-single-injection").val(),
                    append_to_content: $("#smp-vp-single-injection").val() === "after_content" ? 1 : 0,
                    require_thumbnail: $("#smp-vp-require-thumb").is(":checked") ? 1 : 0,
                    homepage_template: $("#smp-vp-homepage-template").val(),
                    post_template: $("#smp-vp-post-template").val(),
                    profile_limit: $("#smp-vp-profile-limit").val(),
                    archive_url: $("#smp-vp-archive-url").val(),
                    primary_color: globalColor("primary_color"),
                    secondary_color: globalColor("secondary_color"),
                    ink_color: globalColor("ink_color"),
                    muted_color: globalColor("muted_color"),
                    line_color: globalColor("line_color"),
                    soft_color: globalColor("soft_color"),
                    name_font_size: $("#smp-vp-name-font-size").val(),
                    role_font_size: $("#smp-vp-role-font-size").val()
                };
            }
            function log(message){ $log.text(message || "Done."); }
            function normalizeHex(value){
                let hex = String(value || "").trim().toLowerCase();
                if (hex && hex.charAt(0) !== "#") { hex = "#" + hex; }
                if (/^#[0-9a-f]{3}$/.test(hex)) { hex = "#" + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3]; }
                return /^#[0-9a-f]{6}$/.test(hex) ? hex : "";
            }
            function globalColor(key){ return $root.find(".smp-vp-global-color[data-key=\"" + key + "\"]").first().val() || ""; }
            function setColorValue($input, value){
                const hex = normalizeHex(value);
                if (!$input.length || !hex) { return; }
                const $control = $input.closest("[data-hpc-color-control],.smp-vp-color-control-fallback");
                $input.val(hex);
                $control.find("[data-hpc-color-picker]").val(hex);
                $control.find("[data-hpc-color-swatch],.smp-vp-color-fallback-swatch").css("background", hex);
                $control.find("[data-hpc-color-hex]").text(hex);
                $control.find("[data-hpc-copy]").attr("data-hpc-copy", hex);
                $input.trigger("input").trigger("change");
            }
            function applyGlobalColors(colors){
                if (!colors) { return; }
                $.each(colors, function(key, color){ setColorValue($root.find(".smp-vp-global-color[data-key=\"" + key + "\"]").first(), color); });
            }
            function applyLoopElementorColors($card, colors){
                if (!colors) { return; }
                setColorValue($card.find(".smp-vp-loop-primary-color").first(), colors.primary_color);
                setColorValue($card.find(".smp-vp-loop-secondary-color").first(), colors.secondary_color || colors.ink_color || colors.muted_color);
                syncLoopPreview($card);
            }
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
            function save(button, doneMessage, syncTarget){
                const $button = button ? $(button) : $("#smp-vp-display-save");
                const original = $button.text();
                $button.prop("disabled", true).text("Saving...");
                log("Saving display settings...");
                return $.post(ajaxurl, { action: "smp_vp_display_save_settings", nonce: $root.data("nonce"), settings: collect(), sync_loop_target: syncTarget || "" })
                    .done(function(response){
                        if (response && response.success) { if (response.data && response.data.html) { $("#smp-vp-loop-items").html(response.data.html); } log(doneMessage || response.data.message || "Feature settings saved."); }
                        else { log((response && response.data && response.data.message) || "Save failed."); }
                    })
                    .fail(function(){ log("Save request failed."); })
                    .always(function(){ $button.prop("disabled", false).text(original); syncState(); });
            }
            function cardMessage(id, type, message){
                const $card = $(".smp-vp-loop-card[data-loop-id=\"" + id + "\"]");
                if (!$card.length) { return; }
                const icon = type === "success" ? "✅" : "❌";
                $card.find(".smp-vp-loop-card-response")
                    .removeAttr("hidden")
                    .removeClass("is-success is-error")
                    .addClass(type === "success" ? "is-success" : "is-error")
                    .text(icon + " " + message);
            }
            function replaceLoops(response, cardStatus){
                if (response && response.success && response.data && response.data.html) {
                    $("#smp-vp-loop-items").html(response.data.html);
                    syncLoopPreviews();
                }
                if (cardStatus && cardStatus.id) {
                    cardMessage(cardStatus.id, cardStatus.type || "success", cardStatus.message || "Loop item saved.");
                }
                log(response && response.data && response.data.message ? response.data.message : "Loop items updated.");
            }
            function collectLoop($card){
                return {
                    id: $card.data("loop-id"),
                    label: $card.find(".smp-vp-loop-label").val(),
                    context: $card.find(".smp-vp-loop-context").val(),
                    template: $card.find(".smp-vp-loop-template").val(),
                    rows: $card.find(".smp-vp-loop-rows").val(),
                    items_per_row: $card.find(".smp-vp-loop-items-per-row").val(),
                    require_thumbnail: $card.find(".smp-vp-loop-require-thumb").is(":checked") ? 1 : 0,
                    primary_color: $card.find(".smp-vp-loop-primary-color").val(),
                    secondary_color: $card.find(".smp-vp-loop-secondary-color").val(),
                    name_font_size: $card.find(".smp-vp-loop-name-font-size").val(),
                    role_font_size: $card.find(".smp-vp-loop-role-font-size").val()
                };
            }
            function syncLoopPreview($card){
                if (!$card || !$card.length) { return; }
                const template = $card.find(".smp-vp-loop-template").val();
                const label = labels[template] || template;
                const primary = $card.find(".smp-vp-loop-primary-color").val() || "#b3272d";
                const secondary = $card.find(".smp-vp-loop-secondary-color").val() || "#151515";
                const nameSize = parseInt($card.find(".smp-vp-loop-name-font-size").val(), 10) || 18;
                const roleSize = parseInt($card.find(".smp-vp-loop-role-font-size").val(), 10) || 10;
                const rows = Math.max(1, parseInt($card.find(".smp-vp-loop-rows").val(), 10) || 1);
                const itemsPerRow = Math.max(1, parseInt($card.find(".smp-vp-loop-items-per-row").val(), 10) || 1);
                const outputCount = Math.min(48, rows * itemsPerRow);
                $card.find(".smp-vp-loop-output-count strong").text(outputCount);
                $card.find(".smp-vp-loop-template-chip").text("Design: " + label);
                $card.find(".smp-vp-loop-preview-template").attr("hidden", true);
                $card.find(".smp-vp-loop-preview-template[data-template-key=\"" + template + "\"]").removeAttr("hidden");
                $card.find(".smp-vp-loop-preview .smp-vp-display").each(function(){
                    this.style.setProperty("--vp-red", primary);
                    this.style.setProperty("--vp-secondary", secondary);
                    this.style.setProperty("--vp-ink", secondary);
                    this.style.setProperty("--vp-name-size", nameSize + "px");
                    this.style.setProperty("--vp-role-size", roleSize + "px");
                    this.style.setProperty("--vp-items-per-row", itemsPerRow);
                    const $vp = $(this).find(".vp");
                    const $first = $vp.children(".vp-card").first();
                    if ($first.length) {
                        while ($vp.children(".vp-card").length < outputCount) { $first.clone().appendTo($vp); }
                        $vp.children(".vp-card").slice(outputCount).remove();
                    }
                });
            }
            function syncLoopPreviews(){
                $root.find(".smp-vp-loop-card").each(function(){
                    syncLoopPreview($(this));
                });
            }
            function saveLoop(button){
                const $button = $(button);
                const $card = $button.closest(".smp-vp-loop-card");
                const loopId = $card.data("loop-id");
                const original = $button.text();
                $button.prop("disabled", true).text("Saving...");
                log("Saving loop item...");
                $card.find(".smp-vp-loop-card-response").attr("hidden", true).removeClass("is-success is-error").text("");
                $.post(ajaxurl, { action: "smp_vp_display_save_loop_item", nonce: $root.data("nonce"), loop_item: collectLoop($card) })
                    .done(function(response){
                        if (response && response.success) {
                            replaceLoops(response, { id: loopId, type: "success", message: response.data.message || "Loop item saved." });
                        } else {
                            const message = (response && response.data && response.data.message) || "Loop item save failed.";
                            cardMessage(loopId, "error", message);
                            log(message);
                        }
                    })
                    .fail(function(){
                        cardMessage(loopId, "error", "Loop save request failed.");
                        log("Loop save request failed.");
                    })
                    .always(function(){ $button.prop("disabled", false).text(original); });
            }
            $(".smp-vp-template-action").on("click", function(){
                const target = $(this).data("target");
                const key = $(this).data("template");
                if (target === "homepage") { $("#smp-vp-homepage-template").val(key); }
                if (target === "post") { $("#smp-vp-post-template").val(key); }
                syncState();
                save(this, "Template selection saved.", target);
            });
            $("#smp-vp-display-save").on("click", function(){ save(this); });
            $root.on("hexa:elementorPaletteLoaded", "[data-hpc-elementor-palette]", function(event){
                const count = event.originalEvent && event.originalEvent.detail && event.originalEvent.detail.palette ? event.originalEvent.detail.palette.length : 0;
                log(count ? "Elementor palette loaded. Your saved colors were not changed." : "No Elementor colors found.");
            });
            $("#smp-vp-loop-create").on("click", function(){
                const $button = $(this);
                const original = $button.text();
                $button.prop("disabled", true).text("Creating...");
                log("Creating loop item...");
                $.post(ajaxurl, { action: "smp_vp_display_create_loop_item", nonce: $root.data("nonce"), label: $("#smp-vp-new-loop-label").val(), context: $("#smp-vp-new-loop-context").val(), template: $("#smp-vp-new-loop-template").val() })
                    .done(function(response){ replaceLoops(response); if (response && response.success) { $("#smp-vp-new-loop-label").val(""); } })
                    .fail(function(){ log("Loop create request failed."); })
                    .always(function(){ $button.prop("disabled", false).text(original); });
            });
            $root.on("click", ".smp-vp-loop-save", function(){ saveLoop(this); });
            $root.on("click", ".smp-vp-loop-import-elementor", function(){
                const $button = $(this);
                const $card = $button.closest(".smp-vp-loop-card");
                const original = $button.text();
                $button.prop("disabled", true).text("Importing...");
                log("Importing Elementor colors into loop item...");
                $.post(ajaxurl, { action: "smp_vp_display_import_elementor", nonce: $root.data("nonce"), preview_only: 1 })
                    .done(function(response){
                        if (response && response.success) {
                            applyLoopElementorColors($card, response.data.colors || {});
                            $button.prop("disabled", false).text(original);
                            saveLoop($button[0]);
                            return;
                        }
                        cardMessage($card.data("loop-id"), "error", (response && response.data && response.data.message) || "Elementor import failed.");
                        log((response && response.data && response.data.message) || "Elementor import failed.");
                    })
                    .fail(function(){ cardMessage($card.data("loop-id"), "error", "Elementor import request failed."); log("Elementor import request failed."); })
                    .always(function(){ if ($button.text() === "Importing...") { $button.prop("disabled", false).text(original); } });
            });
            $root.on("input change", ".smp-vp-loop-label,.smp-vp-loop-context,.smp-vp-loop-template,.smp-vp-loop-rows,.smp-vp-loop-items-per-row,.smp-vp-loop-primary-color,.smp-vp-loop-secondary-color,.smp-vp-loop-primary-picker,.smp-vp-loop-secondary-picker,.smp-vp-loop-name-font-size,.smp-vp-loop-role-font-size,.smp-vp-loop-require-thumb", function(){
                syncLoopPreview($(this).closest(".smp-vp-loop-card"));
            });
            $root.on("click", ".smp-vp-loop-delete", function(){
                const $button = $(this);
                const original = $button.text();
                const id = $button.closest(".smp-vp-loop-card").data("loop-id");
                $button.prop("disabled", true).text("Deleting...");
                log("Deleting loop item...");
                $.post(ajaxurl, { action: "smp_vp_display_delete_loop_item", nonce: $root.data("nonce"), id: id })
                    .done(replaceLoops)
                    .fail(function(){ log("Loop delete request failed."); })
                    .always(function(){ $button.prop("disabled", false).text(original); });
            });
            $root.on("click", ".smp-vp-loop-copy", function(){
                const $field = $(this).closest(".smp-vp-loop-actions").find(".smp-vp-loop-shortcode");
                $field.trigger("select");
                if (navigator.clipboard) { navigator.clipboard.writeText($field.val()); }
                $(this).siblings(".smp-vp-loop-copy-status").text("Copied");
            });
            syncState();
            syncLoopPreviews();
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
    $sync_target = sanitize_key(wp_unslash((string) ($_POST["sync_loop_target"] ?? "")));
    if ($sync_target === "homepage" && ! empty($settings["loop_items"]["homepage"])) {
        $settings["loop_items"]["homepage"]["template"] = $settings["homepage_template"];
    }
    if ($sync_target === "post" && ! empty($settings["loop_items"]["single-post"])) {
        $settings["loop_items"]["single-post"]["template"] = $settings["post_template"];
    }
    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
    wp_send_json_success(["message" => "Feature settings saved.", "settings" => $settings, "html" => smp_vp_display_render_loop_items_admin($settings)]);
}

function smp_vp_display_render_loop_items_admin(array $settings): string {
    $templates = smp_vp_display_templates();
    $contexts = smp_vp_display_contexts();
    $items = $settings["loop_items"] ?? [];
    ob_start();
    ?>
    <div class="smp-vp-loop-list">
        <?php foreach ($items as $item) :
            $id = (string) $item["id"];
            $shortcode = "[verified_profiles_loop id=\"" . $id . "\"]";
            $is_default = in_array($id, ["homepage", "single-post"], true);
            $template_label = $templates[$item["template"]]["label"] ?? $item["template"];
            ?>
            <div class="smp-vp-loop-card" data-loop-id="<?php echo esc_attr($id); ?>">
                <div class="smp-vp-loop-card-head">
                    <div>
                        <h4><?php echo esc_html($item["label"]); ?></h4>
                        <p><?php echo esc_html($shortcode); ?></p>
                    </div>
                    <div>
                        <span class="smp-vp-loop-chip"><?php echo esc_html($contexts[$item["context"]] ?? $item["context"]); ?></span>
                        <span class="smp-vp-loop-chip smp-vp-loop-template-chip">Design: <?php echo esc_html($template_label); ?></span>
                    </div>
                </div>
                <div class="smp-vp-loop-grid">
                    <div class="wide"><label>Loop label</label><input type="text" class="smp-vp-loop-label" value="<?php echo esc_attr($item["label"]); ?>"></div>
                    <div><label>Context</label><select class="smp-vp-loop-context">
                        <?php foreach ($contexts as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($item["context"], $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div><label>Design</label><select class="smp-vp-loop-template">
                        <?php foreach ($templates as $key => $template) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($item["template"], $key); ?>><?php echo esc_html($template["label"]); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div><label>Rows</label><input type="number" min="1" max="8" class="smp-vp-loop-rows" value="<?php echo esc_attr($item["rows"]); ?>"></div>
                    <div><label>Items per row</label><input type="number" min="1" max="6" class="smp-vp-loop-items-per-row" value="<?php echo esc_attr($item["items_per_row"]); ?>"></div>
                    <div><label>Output count</label><div class="smp-vp-loop-output-count"><strong><?php echo esc_html($item["limit"]); ?></strong> profiles</div></div>
                    <div class="wide smp-vp-loop-color-slot">
                        <?php echo smp_vp_display_detailed_color_picker([
                            "id" => "smp-vp-loop-detailed-colors-" . $id,
                            "title" => "Detailed Color Picker",
                            "description" => "This loop item keeps its own primary and secondary colors.",
                            "show_elementor_import" => true,
                            "show_fonts" => false,
                            "primary" => [
                                "key" => "loop_primary_color_" . $id,
                                "label" => "Primary color",
                                "value" => (string) $item["primary_color"],
                                "id" => "smp-vp-loop-primary-" . $id,
                                "control_class" => "smp-vp-loop-color-control",
                                "hex_input_class" => "smp-vp-loop-primary-color",
                                "picker_class" => "smp-vp-loop-primary-picker",
                            ],
                            "secondary" => [
                                "key" => "loop_secondary_color_" . $id,
                                "label" => "Secondary color",
                                "value" => (string) $item["secondary_color"],
                                "id" => "smp-vp-loop-secondary-" . $id,
                                "control_class" => "smp-vp-loop-color-control",
                                "hex_input_class" => "smp-vp-loop-secondary-color",
                                "picker_class" => "smp-vp-loop-secondary-picker",
                            ],
                        ]); ?>
                    </div>
                    <div><label>Name font size</label><input type="number" min="12" max="42" class="smp-vp-loop-name-font-size" value="<?php echo esc_attr($item["name_font_size"]); ?>"></div>
                    <div><label>Role font size</label><input type="number" min="8" max="24" class="smp-vp-loop-role-font-size" value="<?php echo esc_attr($item["role_font_size"]); ?>"></div>
                    <div><label><input type="checkbox" class="smp-vp-loop-require-thumb" <?php checked($item["require_thumbnail"]); ?>> Require thumbnails</label></div>
                    <?php echo smp_vp_display_render_loop_preview_admin($item, $templates); ?>
                </div>
                <div class="smp-vp-loop-card-response" hidden></div>
                <div class="smp-vp-loop-actions">
                    <input type="text" class="smp-vp-loop-shortcode" readonly value="<?php echo esc_attr($shortcode); ?>">
                    <button type="button" class="button smp-vp-loop-copy">Copy shortcode</button>
                    <button type="button" class="button button-primary smp-vp-loop-save">Save Loop Item</button>
                    <?php if (! $is_default) : ?><button type="button" class="button smp-vp-loop-delete">Delete</button><?php endif; ?>
                    <span class="smp-vp-loop-copy-status"></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}


function smp_vp_display_render_loop_preview_admin(array $item, array $templates): string {
    $profiles = smp_vp_display_preview_profiles((int) ($item["limit"] ?? 1));
    $base_args = [
        "primary_color" => $item["primary_color"] ?? "#b3272d",
        "secondary_color" => $item["secondary_color"] ?? "#151515",
        "ink_color" => $item["secondary_color"] ?? "#151515",
        "items_per_row" => $item["items_per_row"] ?? 3,
        "name_font_size" => $item["name_font_size"] ?? 18,
        "role_font_size" => $item["role_font_size"] ?? 10,
        "archive_url" => "#",
    ];

    ob_start();
    ?>
    <div class="smp-vp-loop-preview" data-active-template="<?php echo esc_attr($item["template"]); ?>">
        <div class="smp-vp-loop-preview-title">Live card preview</div>
        <?php foreach ($templates as $key => $template) : ?>
            <div class="smp-vp-loop-preview-template" data-template-key="<?php echo esc_attr($key); ?>" <?php echo $key === $item["template"] ? "" : "hidden"; ?>>
                <?php echo smp_vp_display_render_collection($profiles, array_replace($base_args, ["template" => $key])); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_ajax_display_create_loop_item(): void {
    if (! current_user_can("manage_options")) {
        wp_send_json_error(["message" => "Insufficient permissions."], 403);
    }

    check_ajax_referer(SMP_VP_DISPLAY_NONCE, "nonce");
    $settings = smp_vp_display_settings();
    $label = sanitize_text_field(wp_unslash((string) ($_POST["label"] ?? "")));
    $context = sanitize_key(wp_unslash((string) ($_POST["context"] ?? "homepage")));
    if (! isset(smp_vp_display_contexts()[$context])) {
        $context = "homepage";
    }

    if ($label === "") {
        $label = smp_vp_display_contexts()[$context] . " Loop";
    }

    $id = smp_vp_display_unique_loop_id($label, $settings["loop_items"] ?? []);
    $requested_template = sanitize_key(wp_unslash((string) ($_POST["template"] ?? "")));
    $templates = array_keys(smp_vp_display_templates());
    $template = in_array($requested_template, $templates, true) ? $requested_template : ($context === "single" ? ($settings["post_template"] ?? "directory-list") : ($settings["homepage_template"] ?? "boxed-row"));
    $item = smp_vp_display_sanitize_loop_item([
        "id" => $id,
        "label" => $label,
        "context" => $context,
        "template" => $template,
    ], $settings, $id);

    $settings["loop_items"][$item["id"]] = $item;
    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
    wp_send_json_success(["message" => "Loop item created.", "html" => smp_vp_display_render_loop_items_admin($settings), "settings" => $settings]);
}

function smp_vp_ajax_display_save_loop_item(): void {
    if (! current_user_can("manage_options")) {
        wp_send_json_error(["message" => "Insufficient permissions."], 403);
    }

    check_ajax_referer(SMP_VP_DISPLAY_NONCE, "nonce");
    $input = isset($_POST["loop_item"]) && is_array($_POST["loop_item"]) ? wp_unslash($_POST["loop_item"]) : [];
    $settings = smp_vp_display_settings();
    $item = smp_vp_display_sanitize_loop_item($input, $settings, (string) ($input["id"] ?? ""));
    if ($item["id"] === "") {
        wp_send_json_error(["message" => "Missing loop item id."], 422);
    }

    $settings["loop_items"][$item["id"]] = $item;
    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
    wp_send_json_success(["message" => "Loop item saved.", "html" => smp_vp_display_render_loop_items_admin($settings), "settings" => $settings]);
}

function smp_vp_ajax_display_delete_loop_item(): void {
    if (! current_user_can("manage_options")) {
        wp_send_json_error(["message" => "Insufficient permissions."], 403);
    }

    check_ajax_referer(SMP_VP_DISPLAY_NONCE, "nonce");
    $id = sanitize_key(wp_unslash((string) ($_POST["id"] ?? "")));
    $settings = smp_vp_display_settings();
    if (in_array($id, ["homepage", "single-post"], true)) {
        wp_send_json_error(["message" => "Default loop items cannot be deleted."], 422);
    }

    unset($settings["loop_items"][$id]);
    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
    wp_send_json_success(["message" => "Loop item deleted.", "html" => smp_vp_display_render_loop_items_admin($settings), "settings" => $settings]);
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

    if (! empty($_POST["preview_only"])) {
        wp_send_json_success(["message" => "Elementor colors loaded.", "colors" => $colors, "palette" => smp_vp_display_elementor_palette()]);
    }

    $settings = array_replace(smp_vp_display_settings(), $colors);
    update_option(SMP_VP_DISPLAY_OPTION, $settings, false);
    wp_send_json_success(["message" => "Elementor primary/secondary colors imported.", "colors" => $colors, "settings" => $settings]);
}

function smp_vp_display_elementor_colors(): array {
    if (class_exists("\Hexa\PluginCore\BrandColors\BrandColorProvider")) {
        $colors = \Hexa\PluginCore\BrandColors\BrandColorProvider::elementor_colors();
        return array_filter([
            "primary_color" => $colors["primary_color"] ?? null,
            "secondary_color" => $colors["secondary_color"] ?? null,
            "ink_color" => $colors["text_color"] ?? ($colors["secondary_color"] ?? null),
            "muted_color" => $colors["secondary_color"] ?? null,
        ]);
    }

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

    $primary = $flat["primary"] ?? $flat["accent"] ?? null;
    $secondary = $flat["secondary"] ?? $flat["text"] ?? null;

    return array_filter([
        "primary_color" => $primary,
        "secondary_color" => $secondary,
        "ink_color" => $flat["text"] ?? $secondary,
        "muted_color" => $secondary,
    ]);
}

function smp_vp_display_elementor_palette(): array {
    if (class_exists("\Hexa\PluginCore\BrandColors\BrandColorProvider")) {
        return \Hexa\PluginCore\BrandColors\BrandColorProvider::elementor_palette();
    }

    $kit = absint(get_option("elementor_active_kit"));
    if (! $kit) {
        return [];
    }
    $raw = get_post_meta($kit, "_elementor_page_settings", true);
    if (! is_array($raw)) {
        return [];
    }
    $palette = [];
    foreach (["system_colors" => "System", "custom_colors" => "Custom"] as $group => $group_label) {
        foreach ((array) ($raw[$group] ?? []) as $color) {
            $hex = sanitize_hex_color((string) ($color["color"] ?? ""));
            if (! $hex) {
                continue;
            }
            $palette[] = [
                "id" => sanitize_key((string) ($color["_id"] ?? $hex)),
                "label" => (string) ($color["title"] ?? ($color["_id"] ?? "Color")),
                "group" => $group_label,
                "hex" => $hex,
            ];
        }
    }
    return $palette;
}

function smp_vp_display_preview_profiles(int $count = 1): array {
    $count = max(1, min(48, $count));
    $names = ["Mash Viral", "Verified Founder", "Editorial Source", "Industry Voice", "Featured Expert", "Profile Member"];
    $roles = ["Publication", "Founder", "Executive", "Creator", "Expert", "Verified Profile"];
    $profiles = [];

    for ($i = 0; $i < $count; $i++) {
        $profiles[] = [
            "name" => $names[$i % count($names)],
            "role" => $roles[$i % count($roles)],
            "url" => "#",
            "image" => "https://picsum.photos/seed/smp-vp-template-preview-" . $i . "/180/180?grayscale",
        ];
    }

    return $profiles;
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

    $profile_count = count($profiles);
    $configured_items_per_row = smp_vp_display_number($settings["items_per_row"] ?? smp_vp_display_default_items_per_row((string) $template), smp_vp_display_default_items_per_row((string) $template), 1, 6);
    $effective_items_per_row = $profile_count > 0 ? min($configured_items_per_row, $profile_count) : $configured_items_per_row;

    $vars = sprintf(
        "--vp-red:%s;--vp-secondary:%s;--vp-ink:%s;--vp-muted:%s;--vp-line:%s;--vp-soft:%s;--vp-items-per-row:%d;--vp-name-size:%dpx;--vp-role-size:%dpx;",
        esc_attr($settings["primary_color"]),
        esc_attr($settings["secondary_color"] ?? ($settings["ink_color"] ?? "#151515")),
        esc_attr($settings["ink_color"]),
        esc_attr($settings["muted_color"]),
        esc_attr($settings["line_color"]),
        esc_attr($settings["soft_color"]),
        $effective_items_per_row,
        absint($settings["name_font_size"] ?? 18),
        absint($settings["role_font_size"] ?? 10)
    );

    ob_start();
    ?>
    <style><?php echo smp_vp_display_css(); ?></style>
    <section class="smp-vp-display" style="<?php echo esc_attr($vars); ?>">
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

    $count = absint(get_post_meta($post_id, "profiles", true));
    for ($i = 0; $i < $count; $i++) {
        $value = get_post_meta($post_id, "profiles_" . $i . "_profile", true);
        if (is_object($value) && isset($value->ID)) {
            $value = $value->ID;
        }
        if (is_array($value)) {
            $value = reset($value);
        }
        if (absint($value)) {
            $ids[] = absint($value);
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

function smp_vp_display_author_ids(int $author_id, int $limit, bool $require_thumbnail = false): array {
    if (! $author_id && is_author()) {
        $author_id = absint(get_queried_object_id());
    }

    if (! $author_id && is_singular()) {
        $author_id = absint(get_post_field("post_author", get_the_ID()));
    }

    if (! $author_id) {
        return [];
    }

    $post_ids = get_posts([
        "post_type" => ["post", "press-release"],
        "post_status" => "publish",
        "author" => $author_id,
        "posts_per_page" => 80,
        "orderby" => "date",
        "order" => "DESC",
        "fields" => "ids",
    ]);

    $ids = [];
    foreach ($post_ids as $post_id) {
        foreach (smp_vp_display_post_ids((int) $post_id, $require_thumbnail) as $profile_id) {
            $ids[] = $profile_id;
            if (count(array_unique($ids)) >= $limit) {
                break 2;
            }
        }
    }

    return array_slice(array_values(array_unique($ids)), 0, $limit);
}

function smp_vp_display_loop_profile_ids(array $item, array $atts = []): array {
    $items_per_row = smp_vp_display_number($atts["items_per_row"] ?? $atts["per_row"] ?? $item["items_per_row"] ?? 3, (int) ($item["items_per_row"] ?? 3), 1, 6);
    $rows = smp_vp_display_number($atts["rows"] ?? $item["rows"] ?? 1, (int) ($item["rows"] ?? 1), 1, 8);
    $has_layout_override = isset($atts["rows"]) || isset($atts["items_per_row"]) || isset($atts["per_row"]);
    $limit = (! $has_layout_override && isset($atts["limit"]) && $atts["limit"] !== "") ? smp_vp_display_number($atts["limit"], (int) $item["limit"], 1, 48) : smp_vp_display_loop_limit($rows, $items_per_row);
    $require_thumbnail = smp_vp_display_bool($atts["require_thumbnail"] ?? $atts["must_have_thumbnail"] ?? $item["require_thumbnail"]);
    $context = sanitize_key((string) ($atts["context"] ?? $item["context"]));
    if (! isset(smp_vp_display_contexts()[$context])) {
        $context = $item["context"];
    }

    if ($context === "single") {
        $post_id = absint($atts["post_id"] ?? 0);
        if (! $post_id) {
            $post_id = absint(get_the_ID());
        }
        if (! $post_id) {
            return [];
        }

        $ids = smp_vp_display_post_ids($post_id, false);
        if ($require_thumbnail) {
            $thumbnail_ids = array_values(array_filter($ids, static function ($id) {
                return has_post_thumbnail($id);
            }));

            if ($thumbnail_ids) {
                $ids = $thumbnail_ids;
            }
        }

        return array_slice($ids, 0, $limit);
    }

    if ($context === "author") {
        $author_id = absint($atts["author_id"] ?? $item["author_id"] ?? 0);
        return smp_vp_display_author_ids($author_id, $limit, $require_thumbnail);
    }

    return smp_vp_display_homepage_ids($limit, $require_thumbnail);
}

function smp_vp_display_render_loop_item(string $id, array $atts = []): string {
    $settings = smp_vp_display_settings();
    $id = sanitize_key($id ?: ($atts["id"] ?? "homepage"));
    $items = $settings["loop_items"] ?? [];
    if (empty($items[$id])) {
        return smp_vp_display_empty_loop_marker($id);
    }

    $item = $items[$id];
    $template = sanitize_key((string) ($atts["template"] ?? $item["template"]));
    if (! isset(smp_vp_display_templates()[$template])) {
        $template = $item["template"];
    }

    $ids = smp_vp_display_loop_profile_ids($item, $atts);
    if (! $ids) {
        return smp_vp_display_empty_loop_marker($id);
    }

    $render_args = array_replace($settings, [
        "template" => $template,
        "primary_color" => $item["primary_color"] ?: $settings["primary_color"],
        "secondary_color" => $item["secondary_color"] ?: ($settings["secondary_color"] ?? $settings["ink_color"]),
        "ink_color" => $item["secondary_color"] ?: ($settings["ink_color"] ?? "#151515"),
        "name_font_size" => $item["name_font_size"] ?: $settings["name_font_size"],
        "rows" => $item["rows"] ?? 1,
        "items_per_row" => $item["items_per_row"] ?? 3,
        "role_font_size" => $item["role_font_size"] ?: $settings["role_font_size"],
    ]);

    return "<div class=\"verified-profiles-loop verified-profiles-loop-" . esc_attr($id) . "\">" . smp_vp_display_render_collection($ids, $render_args) . "</div>";
}

function smp_vp_display_content_has_loop(string $content): bool {
    foreach (["verified_profiles_loop", "smp_verified_profiles_loop", "display_single_post_mentioned_in_article", "display_profiles_featured_in_single_post"] as $shortcode) {
        if (has_shortcode($content, $shortcode)) {
            return true;
        }
    }

    foreach (["verified-profiles-loop-single-post", "verified-profiles-loop", "data-smp-vp-empty-loop=\"single-post\""] as $needle) {
        if (false !== strpos($content, $needle)) {
            return true;
        }
    }

    return false;
}

function smp_vp_display_post_declares_loop(int $post_id): bool {
    if ($post_id <= 0) {
        return false;
    }

    $post = get_post($post_id);
    if ($post && smp_vp_display_content_has_loop((string) $post->post_content)) {
        return true;
    }

    $needles = [
        "verified_profiles_loop",
        "smp_verified_profiles_loop",
        "display_single_post_mentioned_in_article",
        "display_profiles_featured_in_single_post",
    ];

    foreach (["_elementor_data", "_elementor_page_settings"] as $meta_key) {
        $value = get_post_meta($post_id, $meta_key, true);
        if (is_array($value)) {
            $value = wp_json_encode($value);
        }

        $value = is_string($value) ? $value : "";
        if ($value === "") {
            continue;
        }

        foreach ($needles as $needle) {
            if (false !== strpos($value, $needle)) {
                return true;
            }
        }
    }

    return false;
}

function smp_vp_verified_profiles_loop_shortcode($atts = []): string {
    try {
        $atts = shortcode_atts([
            "id" => "homepage",
            "context" => "",
            "limit" => "",
            "rows" => "",
            "items_per_row" => "",
            "per_row" => "",
            "template" => "",
            "post_id" => 0,
            "author_id" => 0,
            "require_thumbnail" => "",
            "must_have_thumbnail" => "",
        ], (array) $atts, "verified_profiles_loop");

        $id = sanitize_key((string) ($atts["id"] ?: "homepage"));
        return smp_vp_display_render_loop_item($id, array_filter($atts, static function ($value) {
            return $value !== "" && $value !== null;
        }));
    } catch (\Throwable $error) {
        smp_vp_display_log_failure('verified_profiles_loop shortcode', $error);
        return '';
    }
}

function smp_vp_display_append_to_content(string $content): string {
    $settings = smp_vp_display_settings();
    if (empty($settings["enabled"]) || ($settings["single_injection"] ?? "after_content") !== "after_content" || is_admin() || ! is_singular(["post", "press-release"]) || ! in_the_loop() || ! is_main_query()) {
        return $content;
    }

    $post_id = get_the_ID();
    if (smp_vp_display_content_has_loop($content) || smp_vp_display_post_declares_loop((int) $post_id)) {
        return $content;
    }

    try {
        $rendered = smp_vp_display_render_loop_item("single-post", ["post_id" => $post_id]);
        return $rendered ? $content . $rendered : $content;
    } catch (\Throwable $error) {
        smp_vp_display_log_failure('single-post content injection', $error);
        return $content;
    }
}

if (! function_exists(__NAMESPACE__ . "\\display_homepage_profiles")) {
    function display_homepage_profiles($atts = []): string {
        $settings = smp_vp_display_settings();
        $atts = shortcode_atts(["limit" => $settings["profile_limit"], "template" => $settings["homepage_template"]], (array) $atts, "display_homepage_profiles");
        return smp_vp_verified_profiles_loop_shortcode(array_merge(["id" => "homepage"], (array) $atts));
    }
}

if (! function_exists(__NAMESPACE__ . "\\display_single_post_mentioned_in_article")) {
    function display_single_post_mentioned_in_article($atts = []): string {
        $settings = smp_vp_display_settings();
        $atts = shortcode_atts(["must_have_thumbnail" => false, "template" => $settings["post_template"], "post_id" => get_the_ID()], (array) $atts, "display_single_post_mentioned_in_article");
        return smp_vp_verified_profiles_loop_shortcode(array_merge(["id" => "single-post"], (array) $atts));
    }
}

if (! function_exists(__NAMESPACE__ . "\\display_profiles_featured_in_single_post")) {
    function display_profiles_featured_in_single_post(): string {
        return display_single_post_mentioned_in_article([]);
    }
}
