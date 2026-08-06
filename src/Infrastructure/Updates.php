<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Infrastructure;

use Hexa\PluginCore\CorePackageUpdates\CorePackageConfig;
use Hexa\PluginCore\PluginUpdates\UpdaterConfig;
use smp_verified_profiles\Config;

defined( 'ABSPATH' ) || exit;

final class Updates {
    private static ?UpdaterConfig $plugin_config = null;
    private static ?CorePackageConfig $core_config = null;

    public static function plugin_config(): UpdaterConfig {
        if ( self::$plugin_config instanceof UpdaterConfig ) {
            return self::$plugin_config;
        }

        $plugin_file = dirname( __DIR__, 2 ) . '/smp-verified-profiles.php';
        $basename    = function_exists( 'plugin_basename' )
            ? plugin_basename( $plugin_file )
            : Config::get_plugin_basename();

        self::$plugin_config = UpdaterConfig::from_plugin_file(
            $plugin_file,
            Config::$github_repo,
            [
                'plugin_slug'               => Config::$plugin_folder_name,
                'proper_folder_name'        => Config::$plugin_folder_name,
                'runtime_folder_name'       => dirname( $basename ),
                'plugin_basename'           => $basename,
                'canonical_plugin_basename' => Config::get_plugin_basename(),
                'plugin_starter_file'       => Config::$plugin_starter_file,
                'github_branch'             => Config::$github_branch,
                'requires'                  => '5.0',
                'tested'                    => '7.0',
                'requires_php'              => '8.0',
                'nonce_action'              => Config::$ajax_nonce_action,
                'nonce_param'               => Config::$ajax_nonce_field,
                'ajax_action_prefix'        => Config::$updater_ajax_prefix,
                'progress_key'              => 'smp_vp_core_update_progress',
            ]
        );

        return self::$plugin_config;
    }

    public static function core_config(): CorePackageConfig {
        if ( self::$core_config instanceof CorePackageConfig ) {
            return self::$core_config;
        }

        self::$core_config = CorePackageConfig::from_core_root(
            dirname( __DIR__, 2 ) . '/lib/hexa-wordpress-plugin-core',
            [
                'github_repo'        => 'mikeyperes/hexa-wordpress-plugin-core',
                'github_branch'      => 'main',
                'nonce_action'       => Config::$ajax_nonce_action,
                'nonce_param'        => Config::$ajax_nonce_field,
                'ajax_action_prefix' => Config::$core_package_ajax_prefix,
                'cache_key'          => 'smp_vp_hexa_plugin_core_package',
            ]
        );

        return self::$core_config;
    }
}
