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
            'legacy_checkout_product_id' => 0,
            'legacy_profile_icon_url'     => '',
            'support_email'               => '',
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

    /** Build a legacy WooCommerce action URL from this site's saved product. */
    public static function legacy_checkout_url( array $query ): string {
        // Bug: the legacy dashboard pointed every site at one site's product ID.
        // Fix: read the local mapping before producing a WooCommerce checkout link.
        // Why this way: both dashboards keep their existing order arguments and use the same setting.
        $id = (int) self::all()['legacy_checkout_product_id'];
        if ( $id < 1 ) {
            return '#';
        }
        $checkout = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
        return add_query_arg( [ 'add-to-cart' => $id ] + $query, $checkout );
    }

    /** Read the support email shown in the profile manager's help message. */
    public static function support_email(): string {
        // Bug: building contact@ from a URL could include the site's www prefix.
        // Fix: use the saved support address or the WordPress administrator address.
        // Why this way: both are real site settings and work regardless of the public hostname.
        $email = (string) self::all()['support_email'];
        return '' !== $email ? $email : sanitize_email( (string) get_option( 'admin_email', '' ) );
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
            } elseif ( 'legacy_checkout_product_id' === $key ) {
                $out[ $key ] = absint( $value );
            } elseif ( 'legacy_profile_icon_url' === $key ) {
                $out[ $key ] = esc_url_raw( (string) $value, [ 'http', 'https' ] );
            } elseif ( 'support_email' === $key ) {
                $out[ $key ] = sanitize_email( (string) $value );
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
