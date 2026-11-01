<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Features;

use SMP\VerifiedProfiles\UserRoles\VerifiedProfileManagerRole;

defined( 'ABSPATH' ) || exit;

/**
 * The plugin's optional features, each off by default: profile managers
 * (their dashboard and paid profiles), claiming a profile
 * from the site, and Stripe payments through HWS Base Tools.
 */
final class FeatureSettings {
    public const OPTION = 'smp_vp_features';

    /** @return array<string,mixed> */
    public static function defaults(): array {
        return [
            'profile_managers'     => false,
            'claim_profile'        => false,
            'payments'             => false,
            'profile_price_id'     => '',
            'profile_price_mode'   => 'payment',
            'claim_price_id'       => '',
            'claim_price_mode'     => 'payment',
        ];
    }

    /** @return array<string,mixed> */
    public static function all(): array {
        $stored = get_option( self::OPTION, null );
        if ( ! is_array( $stored ) ) {
            // First read: a site that already has profile managers keeps their dashboard on.
            $stored = [ 'profile_managers' => self::site_has_managers() ];
        }

        return self::sanitize( array_merge( self::defaults(), $stored ) );
    }

    public static function on( string $feature ): bool {
        return ! empty( self::all()[ $feature ] );
    }

    /** Payments run only when switched on here and Stripe is ready in HWS Base Tools. */
    public static function payments_ready(): bool {
        return self::on( 'payments' ) && self::stripe_available() && \HWS\BaseTools\Payments\Stripe::ready();
    }

    public static function stripe_available(): bool {
        return class_exists( '\\HWS\\BaseTools\\Payments\\Stripe' );
    }

    /** @param array<string,mixed> $changes @return array<string,mixed> */
    public static function update( array $changes ): array {
        $settings = self::sanitize( array_merge( self::all(), $changes ) );
        update_option( self::OPTION, $settings, false );

        return $settings;
    }

    /** @param array<string,mixed> $raw @return array<string,mixed> */
    private static function sanitize( array $raw ): array {
        $out = [];
        foreach ( self::defaults() as $key => $default ) {
            $value = $raw[ $key ] ?? $default;
            if ( is_bool( $default ) ) {
                $out[ $key ] = (bool) $value;
            } elseif ( str_ends_with( $key, '_mode' ) ) {
                $out[ $key ] = 'subscription' === $value ? 'subscription' : 'payment';
            } else {
                $out[ $key ] = preg_match( '/^price_[A-Za-z0-9]+$/', (string) $value ) ? (string) $value : '';
            }
        }

        return $out;
    }

    private static function site_has_managers(): bool {
        if ( ! function_exists( 'get_users' ) ) {
            return false;
        }

        return [] !== get_users( [ 'role' => VerifiedProfileManagerRole::ROLE, 'number' => 1, 'fields' => 'ID' ] );
    }
}
