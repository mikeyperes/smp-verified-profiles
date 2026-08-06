<?php

declare( strict_types=1 );

namespace smp_verified_profiles;

use Hexa\PluginCore\CorePackageUpdates\CorePackageConfig;
use Hexa\PluginCore\PluginUpdates\UpdaterAjaxController;
use Hexa\PluginCore\PluginUpdates\UpdaterConfig;
use Hexa\PluginCore\WpAdminAjax\AjaxGuard;
use Hexa\PluginCore\WpAdminAjax\AjaxRequest;
use SMP\VerifiedProfiles\Admin\AdminAjaxHandlers;
use SMP\VerifiedProfiles\Bootstrap\Plugin;
use SMP\VerifiedProfiles\Infrastructure\Updates;

defined( 'ABSPATH' ) || exit;

/** Compatibility facade for legacy integrations that probe Core availability. */
function smp_vp_hexa_core_available(): bool {
    return class_exists( \Hexa\PluginCore\CoreBootstrap\CoreBootstrap::class )
        && class_exists( AjaxRequest::class )
        && class_exists( AjaxGuard::class );
}

function smp_vp_ajax_nonce(): string {
    return AjaxGuard::create_nonce( Config::$ajax_nonce_action );
}

function smp_vp_updater_config(): ?UpdaterConfig {
    return class_exists( UpdaterConfig::class ) ? Updates::plugin_config() : null;
}

function smp_vp_core_package_config(): ?CorePackageConfig {
    return class_exists( CorePackageConfig::class ) ? Updates::core_config() : null;
}

function smp_vp_should_boot_hexa_core_updater(): bool {
    return is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI );
}

function smp_vp_boot_hexa_core_admin(): void {
    Plugin::instance()->boot();
}

function smp_vp_register_ajax_actions(): void {
    Plugin::instance()->boot();
}

function smp_vp_ajax_load_tab( AjaxRequest $request ): array {
    return Plugin::instance()->ajax_handlers()->load_tab( $request );
}

function smp_vp_ajax_toggle_snippet( AjaxRequest $request ): array {
    return Plugin::instance()->snippet_controller()->toggle( $request );
}

function smp_vp_ajax_test_snippet( AjaxRequest $request ): array {
    return Plugin::instance()->snippet_controller()->test( $request );
}

function smp_vp_ajax_modify_wp_config_constants( AjaxRequest $request ): array {
    return Plugin::instance()->ajax_handlers()->modify_wp_config_constants( $request );
}

function smp_vp_ajax_execute_allowed_function( AjaxRequest $request ): array {
    return Plugin::instance()->ajax_handlers()->execute_allowed_function( $request );
}

function smp_vp_ajax_force_plugin_update_check( AjaxRequest $request ): void {
    unset( $request );
    ( new UpdaterAjaxController( Updates::plugin_config() ) )->force_update_check();
}

function smp_vp_ajax_get_unclaimed_profiles( AjaxRequest $request ): array {
    return Plugin::instance()->ajax_handlers()->get_unclaimed_profiles( $request );
}

function smp_vp_ajax_send_email( AjaxRequest $request ): array {
    return Plugin::instance()->ajax_handlers()->send_email( $request );
}

function smp_vp_ajax_refresh_user( AjaxRequest $request ): array {
    return Plugin::instance()->ajax_handlers()->refresh_user( $request );
}

function smp_vp_require_user_edit_access( int $user_id ): void {
    AdminAjaxHandlers::require_user_edit_access( $user_id );
}

function smp_vp_password_reset_url( int $user_id ): string {
    return AdminAjaxHandlers::password_reset_url( $user_id );
}
