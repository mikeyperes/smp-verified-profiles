<?php
namespace smp_verified_profiles;

use Hexa\PluginCore\CorePackageUpdates\CorePackageAjaxController;
use Hexa\PluginCore\CorePackageUpdates\CorePackageConfig;
use Hexa\PluginCore\PluginUpdates\GitHubPluginUpdater;
use Hexa\PluginCore\PluginUpdates\UpdaterAjaxController;
use Hexa\PluginCore\PluginUpdates\UpdaterConfig;
use Hexa\PluginCore\WpAdminAjax\AjaxActionRegistry;
use Hexa\PluginCore\WpAdminAjax\AjaxFailure;
use Hexa\PluginCore\WpAdminAjax\AjaxGuard;
use Hexa\PluginCore\WpAdminAjax\AjaxRequest;
use Hexa\PluginCore\WpAdminTabs\CoreTabConfig;
use Hexa\PluginCore\WpAdminTabs\CoreTabModule;
use Hexa\PluginCore\WpConfigFile\WpConfigFile;

defined( 'ABSPATH' ) || exit;

function smp_vp_hexa_core_available(): bool {
    return class_exists( AjaxActionRegistry::class )
        && class_exists( AjaxRequest::class )
        && class_exists( AjaxGuard::class );
}

function smp_vp_ajax_nonce(): string {
    if ( class_exists( AjaxGuard::class ) ) {
        return AjaxGuard::create_nonce( Config::$ajax_nonce_action );
    }

    return wp_create_nonce( Config::$ajax_nonce_action );
}

function smp_vp_updater_config(): ?UpdaterConfig {
    static $config = null;

    if ( $config instanceof UpdaterConfig ) {
        return $config;
    }

    if ( ! class_exists( UpdaterConfig::class ) ) {
        return null;
    }

    $config = UpdaterConfig::from_plugin_file(
        Config::get_plugin_file_path(),
        Config::$github_repo,
        [
            'plugin_slug'               => Config::$plugin_folder_name,
            'proper_folder_name'        => Config::$plugin_folder_name,
            'runtime_folder_name'       => Config::$plugin_folder_name,
            'plugin_basename'           => Config::get_plugin_basename(),
            'canonical_plugin_basename' => Config::get_plugin_basename(),
            'plugin_starter_file'       => Config::$plugin_starter_file,
            'github_branch'             => Config::$github_branch,
            'requires'                  => '5.0',
            'tested'                    => '7.0',
            'nonce_action'              => Config::$ajax_nonce_action,
            'nonce_param'               => Config::$ajax_nonce_field,
            'ajax_action_prefix'        => Config::$updater_ajax_prefix,
            'progress_key'              => 'smp_vp_core_update_progress',
        ]
    );

    return $config;
}

function smp_vp_core_package_config(): ?CorePackageConfig {
    static $config = null;

    if ( $config instanceof CorePackageConfig ) {
        return $config;
    }

    if ( ! class_exists( CorePackageConfig::class ) ) {
        return null;
    }

    $config = CorePackageConfig::from_core_root(
        __DIR__ . "/lib/hexa-wordpress-plugin-core",
        [
            "github_repo"        => "mikeyperes/hexa-wordpress-plugin-core",
            "github_branch"      => "main",
            "nonce_action"       => Config::$ajax_nonce_action,
            "nonce_param"        => Config::$ajax_nonce_field,
            "ajax_action_prefix" => Config::$core_package_ajax_prefix,
            "cache_key"          => "smp_vp_hexa_plugin_core_package",
        ]
    );

    return $config;
}

function smp_vp_should_boot_hexa_core_updater(): bool {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return true;
    }

    return defined( "WP_CLI" ) && WP_CLI;
}

function smp_vp_boot_hexa_core_admin(): void {
    static $booted = false;

    if ( $booted || ! smp_vp_hexa_core_available() ) {
        return;
    }

    if ( smp_vp_should_boot_hexa_core_updater() ) {
        $updater_config = smp_vp_updater_config();
        if ( $updater_config instanceof UpdaterConfig ) {
            if ( class_exists( GitHubPluginUpdater::class ) ) {
                ( new GitHubPluginUpdater( $updater_config ) )->register();
            }

            if ( class_exists( UpdaterAjaxController::class ) ) {
                ( new UpdaterAjaxController( $updater_config ) )->register();
            }
        }

        $core_config = smp_vp_core_package_config();
        if ( $core_config instanceof CorePackageConfig && class_exists( CorePackageAjaxController::class ) ) {
            ( new CorePackageAjaxController( $core_config ) )->register();
        }
    }

    if ( is_admin() || wp_doing_ajax() ) {
        if ( class_exists( CoreTabModule::class ) && class_exists( CoreTabConfig::class ) ) {
            ( new CoreTabModule(
                new CoreTabConfig(
                    [
                        "tabs_filter"   => "smp_vp_dashboard_tabs",
                        "render_filter" => "smp_vp_render_dashboard_tab",
                        "capability"    => Config::$settings_page_capability,
                        "core_root"     => __DIR__ . "/lib/hexa-wordpress-plugin-core",
                        "readme_path"   => __DIR__ . "/lib/hexa-wordpress-plugin-core/README.md",
                        "library_path"  => __DIR__ . "/HEXA_PLUGIN_CORE_LIBRARY.md",
                    ]
                )
            ) )->register();
        }

        smp_vp_register_ajax_actions();
    }

    if ( is_admin() && isset( $_GET["force-update-check"] ) ) {
        wp_clean_update_cache();
        set_site_transient( "update_plugins", null );
        wp_update_plugins();
        error_log( Config::$plugin_name . ": Forced Hexa Core plugin update check triggered." );
    }

    $booted = true;
}

function smp_vp_register_ajax_actions(): void {
    if ( ! class_exists( AjaxActionRegistry::class ) ) {
        return;
    }

    ( new AjaxActionRegistry(
        [
            'capability'   => Config::$settings_page_capability,
            'nonce_action' => Config::$ajax_nonce_action,
            'nonce_field'  => Config::$ajax_nonce_field,
            'logger'       => static function ( \Throwable $throwable ): void {
                error_log( '[SMP Verified Profiles] AJAX error: ' . $throwable->getMessage() );
            },
        ]
    ) )->register(
        [
            'smp_vp_load_tab' => [
                'callback' => __NAMESPACE__ . '\\smp_vp_ajax_load_tab',
            ],
            'smp_vp_toggle_snippet' => [
                'callback' => __NAMESPACE__ . '\\smp_vp_ajax_toggle_snippet',
            ],
            'smp_vp_test_snippet' => [
                'callback' => __NAMESPACE__ . '\\smp_vp_ajax_test_snippet',
            ],
            'smp_verified_profiles_toggle_snippet' => [
                'callback' => __NAMESPACE__ . '\\smp_vp_ajax_toggle_snippet',
            ],
            'smp_verified_profiles_modify_wp_config_constants' => [
                'callback' => __NAMESPACE__ . '\\smp_vp_ajax_modify_wp_config_constants',
            ],
            'smp_verified_profiles_execute_function' => [
                'callback' => __NAMESPACE__ . '\\smp_vp_ajax_execute_allowed_function',
            ],
            'smp_vp_force_plugin_update_check' => [
                'capability' => 'update_plugins',
                'callback'   => __NAMESPACE__ . '\\smp_vp_ajax_force_plugin_update_check',
            ],
            'get_unclaimed_profiles' => [
                'capability' => 'edit_users',
                'callback'   => __NAMESPACE__ . '\\smp_vp_ajax_get_unclaimed_profiles',
            ],
            'send_email' => [
                'capability' => 'edit_users',
                'callback'   => __NAMESPACE__ . '\\smp_vp_ajax_send_email',
            ],
            'refresh_user' => [
                'capability' => 'edit_users',
                'callback'   => __NAMESPACE__ . '\\smp_vp_ajax_refresh_user',
            ],
        ]
    );
}

function smp_vp_ajax_load_tab( AjaxRequest $request ): array {
    if ( function_exists( __NAMESPACE__ . '\\smp_vp_load_settings_dashboard_files' ) ) {
        smp_vp_load_settings_dashboard_files();
    }

    return smp_vp_tab_fragment( $request->key( 'tab', 'overview', 'post' ) );
}

function smp_vp_ajax_toggle_snippet( AjaxRequest $request ): array {
    $snippet_id = $request->text( 'snippet_id', '', 'post' );
    $enable     = $request->bool( 'enable', false, 'post' );

    if ( '' === $snippet_id ) {
        throw AjaxFailure::bad_request( 'Missing snippet ID.' );
    }

    if ( ! function_exists( __NAMESPACE__ . '\\smp_vp_snippet_registry' ) ) {
        throw AjaxFailure::bad_request( 'Snippet registry is not available.' );
    }

    $registry = smp_vp_snippet_registry();

    if ( ! $registry->has( $snippet_id ) ) {
        throw AjaxFailure::bad_request( 'Invalid snippet ID.' );
    }

    if ( ! $registry->set_enabled( $snippet_id, $enable ) ) {
        throw AjaxFailure::bad_request( 'Snippet could not be updated.' );
    }

    return [
        'snippet_id' => $snippet_id,
        'enabled'    => $enable,
        'message'    => $enable ? 'Snippet enabled.' : 'Snippet disabled.',
        'test'       => $registry->test( $snippet_id ),
    ];
}

function smp_vp_ajax_test_snippet( AjaxRequest $request ): array {
    $snippet_id = $request->text( 'snippet_id', '', 'post' );

    if ( '' === $snippet_id ) {
        throw AjaxFailure::bad_request( 'Missing snippet ID.' );
    }

    if ( ! function_exists( __NAMESPACE__ . '\\smp_vp_snippet_registry' ) ) {
        throw AjaxFailure::bad_request( 'Snippet registry is not available.' );
    }

    $registry = smp_vp_snippet_registry();

    if ( ! $registry->has( $snippet_id ) ) {
        throw AjaxFailure::bad_request( 'Invalid snippet ID.' );
    }

    return $registry->test( $snippet_id );
}

function smp_vp_ajax_modify_wp_config_constants( AjaxRequest $request ): array {
    $raw_constants = $request->raw( 'constants', [], 'post' );

    if ( ! is_array( $raw_constants ) || empty( $raw_constants ) ) {
        throw AjaxFailure::bad_request( 'No constants provided.' );
    }

    $allowed = [
        'WP_AUTO_UPDATE_CORE',
        'WP_MEMORY_LIMIT',
        'WP_DEBUG',
        'WP_DEBUG_LOG',
        'WP_DEBUG_DISPLAY',
        'SCRIPT_DEBUG',
        'DISABLE_WP_CRON',
    ];

    $constants = [];
    foreach ( $raw_constants as $constant => $value ) {
        $constant = is_scalar( $constant ) ? sanitize_key( (string) $constant ) : '';
        $constant = strtoupper( $constant );

        if ( ! in_array( $constant, $allowed, true ) || ! is_scalar( $value ) ) {
            continue;
        }

        $constants[ $constant ] = sanitize_text_field( (string) $value );
    }

    if ( empty( $constants ) ) {
        throw AjaxFailure::bad_request( 'No allowed constants provided.' );
    }

    if ( class_exists( WpConfigFile::class ) ) {
        $result = WpConfigFile::modify_constants( $constants );
    } elseif ( function_exists( __NAMESPACE__ . '\\modify_wp_config_constants' ) ) {
        $result = modify_wp_config_constants( $constants );
    } else {
        throw AjaxFailure::bad_request( 'No wp-config writer is available.' );
    }

    if ( empty( $result['status'] ) ) {
        throw AjaxFailure::bad_request( $result['message'] ?? 'wp-config update failed.' );
    }

    return [
        'message' => $result['message'] ?? 'Configuration updated.',
    ];
}

function smp_vp_ajax_execute_allowed_function( AjaxRequest $request ): array {
    $method = $request->key( 'method', '', 'post' );

    $allowed = [
        'create_unclaimed_profiles_user' => __NAMESPACE__ . '\\create_unclaimed_profiles_user',
        'fix_profile_taxonomies'        => __NAMESPACE__ . '\\fix_profile_taxonomies',
    ];

    if ( '' === $method || empty( $allowed[ $method ] ) || ! is_callable( $allowed[ $method ] ) ) {
        throw AjaxFailure::bad_request( 'Method is not allowed.' );
    }

    $result = call_user_func( $allowed[ $method ] );

    return [
        'method' => $method,
        'result' => $result,
    ];
}

function smp_vp_ajax_force_plugin_update_check( AjaxRequest $request ): array {
    if ( ! function_exists( 'get_plugin_updates' ) ) {
        require_once ABSPATH . 'wp-admin/includes/update.php';
    }

    wp_clean_update_cache();
    wp_update_plugins();
    wp_update_themes();

    $plugin_updates = get_plugin_updates();
    $plugins_list   = [];

    foreach ( $plugin_updates as $plugin_data ) {
        if ( is_object( $plugin_data ) && isset( $plugin_data->Name ) ) {
            $plugins_list[] = (string) $plugin_data->Name;
        }
    }

    return [
        'last_checked'         => current_time( 'mysql' ),
        'plugins_with_updates' => count( $plugin_updates ),
        'plugins_list'         => $plugins_list,
    ];
}

function smp_vp_ajax_get_unclaimed_profiles( AjaxRequest $request ): array {
    $user_id = $request->int( 'user_id', 0, 'post' );
    smp_vp_require_user_edit_access( $user_id );

    $unclaimed_profiles = function_exists( 'get_field' ) ? get_field( 'unclaimed_profiles', 'user_' . $user_id ) : [];
    $profiles_data      = [];

    if ( is_array( $unclaimed_profiles ) ) {
        foreach ( $unclaimed_profiles as $profile ) {
            $profile_id   = isset( $profile['profile'] ) ? absint( $profile['profile'] ) : 0;
            $profile_post = $profile_id ? get_post( $profile_id ) : null;

            if ( $profile_post && 'profile' === $profile_post->post_type ) {
                $profiles_data[] = [
                    'id'   => $profile_post->ID,
                    'name' => get_the_title( $profile_post ),
                ];
            }
        }
    }

    return [ 'profiles' => $profiles_data ];
}

function smp_vp_ajax_send_email( AjaxRequest $request ): array {
    $prefix     = $request->key( 'prefix', '', 'post' );
    $subject    = $request->text( 'subject', '', 'post' );
    $message    = $request->html( 'message', '', 'post' );
    $profile_id = $request->int( 'profile_id', 0, 'post' );
    $user_id    = $request->int( 'user_id', 0, 'post' );

    smp_vp_require_user_edit_access( $user_id );

    if ( ! in_array( $prefix, [ 'welcome_email', 'new_entity_email' ], true ) ) {
        throw AjaxFailure::bad_request( 'Invalid email template.' );
    }

    if ( function_exists( 'update_field' ) ) {
        update_field( $prefix . '_message', $message, 'user_' . $user_id );
        update_field( $prefix . '_subject', $subject, 'user_' . $user_id );
    }

    if ( $profile_id ) {
        $profile_post = get_post( $profile_id );
        if ( $profile_post && 'profile' === $profile_post->post_type ) {
            $profile_name      = get_the_title( $profile_post );
            $profile_permalink = get_permalink( $profile_post );
            $message           = str_replace( '{featured_profile}', '<a href="' . esc_url( $profile_permalink ) . '">' . esc_html( $profile_name ) . '</a>', $message );
            $message           = str_replace( '{featured_profile_name}', esc_html( $profile_name ), $message );
            $message           = str_replace( '{featured_profile_link}', esc_url( $profile_permalink ), $message );
            $subject           = str_replace( '{featured_profile_name}', $profile_name, $subject );
        }
    }

    $email_signature = function_exists( 'get_field' ) ? get_field( 'email_signature', 'options' ) : '';
    $message        .= is_string( $email_signature ) ? wp_kses_post( $email_signature ) : '';
    $emails          = array_filter( array_map( 'sanitize_email', get_notification_emails( $user_id ) ) );

    if ( empty( $emails ) ) {
        throw AjaxFailure::bad_request( 'No notification emails are configured for this user.' );
    }

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
        'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
    ];

    $sent = 0;
    foreach ( $emails as $email ) {
        if ( wp_mail( $email, $subject, $message, $headers ) ) {
            $sent++;
        }
    }

    return [
        'message' => 'Email sent.',
        'sent'    => $sent,
    ];
}

function smp_vp_ajax_refresh_user( AjaxRequest $request ): array {
    $user_id = $request->int( 'user_id', 0, 'post' );
    smp_vp_require_user_edit_access( $user_id );
    update_user_email_settings( $user_id );

    return [
        'message' => 'User email content refreshed without storing a plaintext password.',
        'user_id' => $user_id,
    ];
}

function smp_vp_require_user_edit_access( int $user_id ): void {
    if ( $user_id <= 0 || ! get_user_by( 'id', $user_id ) ) {
        throw AjaxFailure::bad_request( 'Invalid user ID.' );
    }

    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        throw AjaxFailure::forbidden( 'You cannot edit this user.' );
    }
}

function smp_vp_password_reset_url( int $user_id ): string {
    $user = get_user_by( 'id', $user_id );

    if ( ! $user instanceof \WP_User ) {
        return wp_lostpassword_url();
    }

    $key = get_password_reset_key( $user );
    if ( is_wp_error( $key ) ) {
        return wp_lostpassword_url();
    }

    return network_site_url(
        'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user->user_login ),
        'login'
    );
}
