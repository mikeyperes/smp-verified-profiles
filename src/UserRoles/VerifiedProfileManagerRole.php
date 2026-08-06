<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\UserRoles;

use Hexa\PluginCore\CoreContracts\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class VerifiedProfileManagerRole implements ModuleInterface {
    public const ROLE = 'verified_profile_manager';
    public const ADMIN_FEATURE_OPTION = 'enable_snippet_adjust_wp_admin_for_profile_managers';

    private const CAPABILITIES = [
        'read',
        'edit_posts',
        'edit_others_posts',
        'edit_published_posts',
        'create_posts',
        'delete_posts',
        'delete_others_posts',
        'read_private_posts',
        'upload_files',
        'edit_files',
        'read_profile',
        'edit_profile',
        'edit_profiles',
        'edit_others_profiles',
        'edit_published_profiles',
        'publish_profiles',
        'read_private_profiles',
        'delete_profile',
        'delete_others_profiles',
        'delete_private_profiles',
        'delete_published_profiles',
    ];

    private static bool $registered = false;

    public static function boot(): void {
        if ( self::$registered ) {
            return;
        }

        add_action( 'init', [ self::class, 'ensure_when_profile_enabled' ], 1 );
        self::$registered = true;
    }

    public function register(): void {
        self::boot();
    }

    public static function ensure_when_profile_enabled(): void {
        if ( ! (bool) get_option( 'register_profile_custom_post_type', false ) ) {
            return;
        }

        if ( '__smp_vp_missing__' === get_option( self::ADMIN_FEATURE_OPTION, '__smp_vp_missing__' ) ) {
            update_option( self::ADMIN_FEATURE_OPTION, true, false );
        }

        self::ensure();
    }

    public static function ensure(): void {
        $role = get_role( self::ROLE );
        if ( ! $role instanceof \WP_Role ) {
            $role = add_role( self::ROLE, 'Verified Profile Manager', [ 'read' => true ] );
        }
        if ( ! $role instanceof \WP_Role ) {
            return;
        }

        foreach ( self::CAPABILITIES as $capability ) {
            if ( ! $role->has_cap( $capability ) ) {
                $role->add_cap( $capability, true );
            }
        }
    }
}

if ( ! class_exists( '\\smp_verified_profiles\\UserRoles\\VerifiedProfileManagerRole', false ) ) {
    class_alias( VerifiedProfileManagerRole::class, '\\smp_verified_profiles\\UserRoles\\VerifiedProfileManagerRole' );
}
