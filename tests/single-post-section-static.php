<?php

$root       = dirname(__DIR__);
$display    = (string) file_get_contents($root . '/verified-profile-display-templates.php');
$shortcodes = (string) file_get_contents($root . '/shortcodes.php');

$assertions = [
    'single-post section renderer exists' => str_contains($display, 'function smp_vp_display_single_post_section('),
    'empty loop is detected before section markup' => str_contains($display, "strpos(\$loop_html, 'data-smp-vp-empty-loop=')"),
    'empty Elementor shortcode host is hidden' => str_contains($display, '.elementor-widget-shortcode.display_single_post_mentioned_in_article:has([data-smp-vp-empty-loop])'),
    'plugin owns the single-post section wrapper' => str_contains($display, 'class="smp-vp-single-post-section"'),
    'plugin owns the section heading' => str_contains($display, "esc_html__('In This Article', 'smp-verified-profiles')"),
    'plugin section uses an H2 heading' => str_contains($display, '<h2 id="'),
    'automatic content injection uses the full section' => str_contains($display, 'smp_vp_display_single_post_section(' . "\n" . '            smp_vp_display_render_loop_item'),
    'canonical shortcode uses the full section' => str_contains($display, 'smp_vp_display_single_post_section(' . "\n" . '            smp_vp_verified_profiles_loop_shortcode'),
    'fallback shortcode uses the full section' => str_contains($shortcodes, '? smp_vp_display_single_post_section($loop_html)'),
];

foreach ($assertions as $label => $passed) {
    if (! $passed) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

echo 'Single-post verified-profile section: ' . count($assertions) . " assertions passed.\n";
