<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Admin;

use Hexa\PluginCore\CoreContracts\ModuleInterface;
use Hexa\PluginCore\WpAdminAjax\AjaxActionRegistry;
use smp_verified_profiles\Config;

defined( 'ABSPATH' ) || exit;

final class AdminAjaxModule implements ModuleInterface {
    public function __construct( private AdminAjaxHandlers $handlers ) {
    }

    public function register(): void {
        $actions = [
            'smp_vp_load_tab' => [ 'callback' => [ $this->handlers, 'load_tab' ] ],
            'smp_vp_shortcode_profile_values' => [ 'callback' => [ $this->handlers, 'shortcode_profile_values' ] ],
            'smp_verified_profiles_modify_wp_config_constants' => [ 'callback' => [ $this->handlers, 'modify_wp_config_constants' ] ],
            'smp_verified_profiles_execute_function' => [ 'callback' => [ $this->handlers, 'execute_allowed_function' ] ],
            'get_unclaimed_profiles' => [
                'capability' => 'edit_users',
                'callback'   => [ $this->handlers, 'get_unclaimed_profiles' ],
            ],
            'send_email' => [
                'capability' => 'edit_users',
                'callback'   => [ $this->handlers, 'send_email' ],
            ],
            'refresh_user' => [
                'capability' => 'edit_users',
                'callback'   => [ $this->handlers, 'refresh_user' ],
            ],
        ];

        ( new AjaxActionRegistry(
            [
                'capability'   => Config::$settings_page_capability,
                'nonce_action' => Config::$ajax_nonce_action,
                'nonce_field'  => Config::$ajax_nonce_field,
                'logger'       => static function ( \Throwable $throwable ): void {
                    error_log( '[SMP Verified Profiles] AJAX error: ' . $throwable->getMessage() );
                },
            ]
        ) )->register( $actions );
    }
}
