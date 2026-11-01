<?php

namespace smp_verified_profiles;

defined( 'ABSPATH' ) || exit;

/**
 * Required plugin definitions consumed by Hexa WP Core PluginChecks.
 *
 * @return array<int,array<string,mixed>>
 */
function smp_vp_plugin_check_definitions(): array {
    $upload_url = function_exists( 'admin_url' ) ? admin_url( 'plugin-install.php?tab=upload' ) : '';

    return [
        [
            'id'          => 'hws-base-tools',
            'name'        => 'HWS Base Tools',
            'plugin_file' => 'hws-base-tools/initialization.php',
            'slug'        => 'hws-base-tools',
            'source'      => 'github',
            'github_repo' => 'mikeyperes/hws-base-tools',
            'checks'      => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
            'notes'       => 'Shared Hexa tooling used by the publication stack.',
        ],
        [
            'id'          => 'elementor',
            'name'        => 'Elementor',
            'plugin_file' => 'elementor/elementor.php',
            'slug'        => 'elementor',
            'source'      => 'wordpress_org',
            'checks'      => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
        ],
        [
            'id'          => 'classic-editor',
            'name'        => 'Classic Editor',
            'plugin_file' => 'classic-editor/classic-editor.php',
            'slug'        => 'classic-editor',
            'source'      => 'wordpress_org',
            'checks'      => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
        ],
        [
            'id'          => 'wp-mail-smtp',
            'name'        => 'WP Mail SMTP',
            'plugin_file' => 'wp-mail-smtp/wp_mail_smtp.php',
            'slug'        => 'wp-mail-smtp',
            'source'      => 'wordpress_org',
            'checks'      => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
        ],
        [
            'id'          => 'simple-local-avatars',
            'name'        => 'Simple Local Avatars',
            'plugin_file' => 'simple-local-avatars/simple-local-avatars.php',
            'slug'        => 'simple-local-avatars',
            'source'      => 'wordpress_org',
            'checks'      => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
        ],
    ];
}

function display_settings_check_plugins(): void {
    if ( ! class_exists( '\\Hexa\\PluginCore\\PluginChecks\\PluginChecksRenderer' ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Hexa WP Core Plugin Checks are not available.', 'smp-verified-profiles' ) . '</p></div>';
        return;
    }

    echo ( new \Hexa\PluginCore\PluginChecks\PluginChecksRenderer() )->render(
        smp_vp_plugin_check_definitions(),
        [
            'title'         => 'Verified Profiles Plugin Checks',
            'eyebrow'       => 'Verified Profiles',
            'description'   => 'Check the plugins this integration expects, install supported missing plugins, activate inactive plugins, and refresh update status without leaving the page.',
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => smp_vp_ajax_nonce(),
            'nonce_field'   => Config::$ajax_nonce_field,
            'action_prefix' => 'smp_vp_plugin_checks',
        ]
    );
}
