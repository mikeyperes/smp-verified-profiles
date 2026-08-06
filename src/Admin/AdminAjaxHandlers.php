<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Admin;

use Hexa\PluginCore\WpAdminAjax\AjaxActionRegistry;
use Hexa\PluginCore\WpAdminAjax\AjaxFailure;
use Hexa\PluginCore\WpAdminAjax\AjaxRequest;
use Hexa\PluginCore\WpConfigFile\WpConfigFile;
use smp_verified_profiles\Config;

defined( 'ABSPATH' ) || exit;

final class AdminAjaxHandlers {
    public static function dispatch( callable $callback, string $capability = '' ): void {
        ( new AjaxActionRegistry(
            [
                'capability'   => '' !== $capability ? $capability : Config::$settings_page_capability,
                'nonce_action' => Config::$ajax_nonce_action,
                'nonce_field'  => Config::$ajax_nonce_field,
            ]
        ) )->dispatch(
            [
                'capability'   => '' !== $capability ? $capability : Config::$settings_page_capability,
                'nonce_action' => Config::$ajax_nonce_action,
                'nonce_field'  => Config::$ajax_nonce_field,
                'verify_nonce' => true,
                'callback'     => $callback,
            ]
        );
    }

    public function load_tab( AjaxRequest $request ): array {
        \smp_verified_profiles\smp_vp_load_settings_dashboard_files();

        return \smp_verified_profiles\smp_vp_tab_fragment( $request->key( 'tab', 'overview', 'post' ) );
    }

    public function modify_wp_config_constants( AjaxRequest $request ): array {
        $raw_constants = $request->raw( 'constants', [], 'post' );
        if ( ! is_array( $raw_constants ) || empty( $raw_constants ) ) {
            throw AjaxFailure::bad_request( 'No constants provided.' );
        }

        $allowed   = [ 'WP_AUTO_UPDATE_CORE', 'WP_MEMORY_LIMIT', 'WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY', 'SCRIPT_DEBUG', 'DISABLE_WP_CRON' ];
        $constants = [];
        foreach ( $raw_constants as $constant => $value ) {
            $constant = is_scalar( $constant ) ? strtoupper( sanitize_key( (string) $constant ) ) : '';
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
        } elseif ( function_exists( '\\smp_verified_profiles\\modify_wp_config_constants' ) ) {
            $result = \smp_verified_profiles\modify_wp_config_constants( $constants );
        } else {
            throw AjaxFailure::bad_request( 'No wp-config writer is available.' );
        }

        if ( empty( $result['status'] ) ) {
            throw AjaxFailure::bad_request( $result['message'] ?? 'wp-config update failed.' );
        }

        return [ 'message' => $result['message'] ?? 'Configuration updated.' ];
    }

    public function shortcode_profile_values( AjaxRequest $request ): array {
        \smp_verified_profiles\smp_vp_load_settings_dashboard_files();

        $profile_id = $request->int( 'profile_id', 0, 'post' );
        $profile    = $profile_id ? get_post( $profile_id ) : null;
        $settings   = \smp_verified_profiles\get_verified_profile_settings();

        if ( ! $profile || $profile->post_type !== $settings['slug'] ) {
            throw AjaxFailure::bad_request( 'Invalid verified profile selected.' );
        }

        $rows = \smp_verified_profiles\smp_vp_build_profile_shortcode_rows( $profile_id );

        ob_start();
        ?>
        <table class="smp-table">
            <thead><tr><th>Field / Content</th><th>Shortcode</th><th>Value From Shortcode</th></tr></thead>
            <tbody>
                <?php foreach ( $rows as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row['label'] ); ?></td>
                        <td><code><?php echo esc_html( $row['shortcode'] ); ?></code></td>
                        <td class="smp-shortcode-value"><?php echo esc_html( $row['value'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php

        return [
            'summary' => sprintf( 'Loaded %d rows for %s.', count( $rows ), get_the_title( $profile_id ) ),
            'html'    => (string) ob_get_clean(),
        ];
    }

    public function execute_allowed_function( AjaxRequest $request ): array {
        $method  = $request->key( 'method', '', 'post' );
        $allowed = [
            'create_unclaimed_profiles_user' => '\\smp_verified_profiles\\create_unclaimed_profiles_user',
            'fix_profile_taxonomies'          => '\\smp_verified_profiles\\fix_profile_taxonomies',
        ];

        if ( '' === $method || empty( $allowed[ $method ] ) || ! is_callable( $allowed[ $method ] ) ) {
            throw AjaxFailure::bad_request( 'Method is not allowed.' );
        }

        return [ 'method' => $method, 'result' => call_user_func( $allowed[ $method ] ) ];
    }

    public function get_unclaimed_profiles( AjaxRequest $request ): array {
        $user_id = $request->int( 'user_id', 0, 'post' );
        self::require_user_edit_access( $user_id );

        $unclaimed_profiles = function_exists( 'get_field' ) ? get_field( 'unclaimed_profiles', 'user_' . $user_id ) : [];
        $profiles_data      = [];

        if ( is_array( $unclaimed_profiles ) ) {
            foreach ( $unclaimed_profiles as $profile ) {
                $profile_id   = isset( $profile['profile'] ) ? absint( $profile['profile'] ) : 0;
                $profile_post = $profile_id ? get_post( $profile_id ) : null;
                if ( $profile_post && 'profile' === $profile_post->post_type ) {
                    $profiles_data[] = [ 'id' => $profile_post->ID, 'name' => get_the_title( $profile_post ) ];
                }
            }
        }

        return [ 'profiles' => $profiles_data ];
    }

    public function send_email( AjaxRequest $request ): array {
        $prefix     = $request->key( 'prefix', '', 'post' );
        $subject    = $request->text( 'subject', '', 'post' );
        $message    = $request->html( 'message', '', 'post' );
        $profile_id = $request->int( 'profile_id', 0, 'post' );
        $user_id    = $request->int( 'user_id', 0, 'post' );

        self::require_user_edit_access( $user_id );
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
        $emails          = array_filter( array_map( 'sanitize_email', \smp_verified_profiles\get_notification_emails( $user_id ) ) );
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

        return [ 'message' => 'Email sent.', 'sent' => $sent ];
    }

    public function refresh_user( AjaxRequest $request ): array {
        $user_id = $request->int( 'user_id', 0, 'post' );
        self::require_user_edit_access( $user_id );
        \smp_verified_profiles\update_user_email_settings( $user_id );

        return [
            'message' => 'User email content refreshed without storing a plaintext password.',
            'user_id' => $user_id,
        ];
    }

    public static function require_user_edit_access( int $user_id ): void {
        if ( $user_id <= 0 || ! get_user_by( 'id', $user_id ) ) {
            throw AjaxFailure::bad_request( 'Invalid user ID.' );
        }
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            throw new AjaxFailure( 'You cannot edit this user.', 403, 'forbidden' );
        }
    }

    public static function password_reset_url( int $user_id ): string {
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
}
