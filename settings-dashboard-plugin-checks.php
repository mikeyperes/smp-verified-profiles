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
            'id'             => 'woocommerce-checkout-field-editor-pro',
            'name'           => 'WooCommerce Checkout Field Editor Pro',
            'plugin_file'    => 'woo-checkout-field-editor-pro/checkout-form-designer.php',
            'slug'           => 'woo-checkout-field-editor-pro',
            'source'         => 'pro',
            'download_url'   => $upload_url,
            'download_label' => 'Upload pro plugin',
            'checks'         => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
        ],
        [
            'id'             => 'jet-engine',
            'name'           => 'JetEngine',
            'plugin_file'    => 'jet-engine/jet-engine.php',
            'slug'           => 'jet-engine',
            'source'         => 'pro',
            'download_url'   => 'https://crocoblock.com/plugins/jetengine/',
            'download_label' => 'Download from Crocoblock',
            'checks'         => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
        ],
        [
            'id'          => 'woocommerce',
            'name'        => 'WooCommerce',
            'plugin_file' => 'woocommerce/woocommerce.php',
            'slug'        => 'woocommerce',
            'source'      => 'wordpress_org',
            'checks'      => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
        ],
        [
            'id'             => 'woocommerce-subscriptions',
            'name'           => 'WooCommerce Subscriptions',
            'plugin_file'    => 'woocommerce-subscriptions/woocommerce-subscriptions.php',
            'slug'           => 'woocommerce-subscriptions',
            'source'         => 'pro',
            'download_url'   => 'https://woocommerce.com/products/woocommerce-subscriptions/',
            'download_label' => 'Download from WooCommerce',
            'checks'         => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
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
            'id'          => 'wp-user-avatars',
            'name'        => 'WP User Avatars',
            'plugin_file' => 'wp-user-avatars/wp-user-avatars.php',
            'slug'        => 'wp-user-avatars',
            'source'      => 'wordpress_org',
            'checks'      => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
        ],
        [
            'id'             => 'advanced-custom-fields-pro',
            'name'           => 'Advanced Custom Fields Pro',
            'plugin_file'    => 'advanced-custom-fields-pro/acf.php',
            'slug'           => 'advanced-custom-fields-pro',
            'source'         => 'pro',
            'download_url'   => 'https://www.advancedcustomfields.com/pro/',
            'download_label' => 'Download ACF Pro',
            'checks'         => [ 'installed' => true, 'active' => true, 'up_to_date' => true ],
            'notes'          => 'Required for Verified Profiles field groups and profile metadata.',
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
