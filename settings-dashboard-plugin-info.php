<?php

declare( strict_types=1 );

namespace smp_verified_profiles;

use Hexa\PluginCore\CorePackageUpdates\CorePackageConfig;
use Hexa\PluginCore\CorePackageUpdates\CorePackagePanelRenderer;
use Hexa\PluginCore\PluginUpdates\UpdaterConfig;
use Hexa\PluginCore\PluginUpdates\UpdaterPanelRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Read local plugin metadata for the fallback panel.
 *
 * @return array<string,mixed>
 */
function smp_get_plugin_data(): array {
    $plugin_file = __DIR__ . '/' . Config::$plugin_starter_file;

    if ( ! is_readable( $plugin_file ) || ! is_file( $plugin_file ) ) {
        return [
            'Name'      => 'Not Available',
            'Version'   => 'Not Available',
            'PluginURI' => 'Not Available',
            'Author'    => 'Not Available',
            'AuthorURI' => 'Not Available',
        ];
    }

    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugin_data = get_plugin_data( $plugin_file );
    foreach ( $plugin_data as $key => $value ) {
        if ( empty( $value ) ) {
            $plugin_data[ $key ] = 'Not Available';
        }
    }

    return $plugin_data;
}

/**
 * Render the canonical Core updater panels, with a read-only fallback.
 */
function display_plugin_info(): void {
    $rendered = false;

    if ( class_exists( UpdaterPanelRenderer::class )
        && function_exists( __NAMESPACE__ . '\\smp_vp_updater_config' )
    ) {
        $config = smp_vp_updater_config();
        if ( $config instanceof UpdaterConfig ) {
            ( new UpdaterPanelRenderer( $config ) )->render();
            $rendered = true;
        }
    }

    if ( class_exists( CorePackagePanelRenderer::class )
        && function_exists( __NAMESPACE__ . '\\smp_vp_core_package_config' )
    ) {
        $core_config = smp_vp_core_package_config();
        if ( $core_config instanceof CorePackageConfig ) {
            ( new CorePackagePanelRenderer( $core_config ) )->render();
            $rendered = true;
        }
    }

    if ( $rendered ) {
        return;
    }

    $plugin_data = smp_get_plugin_data();
    ?>
    <div class="panel">
        <h2 class="panel-title"><?php echo esc_html( Config::$plugin_name ); ?> - Plugin Info</h2>
        <div class="panel-content">
            <p><strong>Plugin Name:</strong> <?php echo esc_html( $plugin_data['Name'] ); ?></p>
            <p><strong>Plugin Slug:</strong> <?php echo esc_html( Config::$plugin_folder_name ); ?></p>
            <p><strong>Current Version:</strong> <?php echo esc_html( $plugin_data['Version'] ); ?></p>
            <p><strong>GitHub URL:</strong> <a href="https://github.com/<?php echo esc_attr( Config::$github_repo ); ?>" target="_blank" rel="noopener">https://github.com/<?php echo esc_html( Config::$github_repo ); ?></a></p>
            <p><?php esc_html_e( 'Hexa updater controls are unavailable because Hexa WordPress Plugin Core did not load.', 'smp-verified-profiles' ); ?></p>
        </div>
    </div>
    <?php
}
