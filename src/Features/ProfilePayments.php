<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Features;

use Hexa\PluginCore\CoreContracts\ModuleInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Verified Profiles payments through HWS Base Tools' Stripe connection: a
 * paid claim approves itself, and a profile manager's paid profile adds one
 * profile credit to their account. Replaces the old WooCommerce checkout.
 */
final class ProfilePayments implements ModuleInterface {
    public const CREDITS_META = 'smp_vp_profile_credits';

    public function register(): void {
        add_action( 'hws_base_tools_stripe_event', [ self::class, 'handle_event' ] );
        add_action( 'admin_post_smp_vp_buy_profile', [ self::class, 'handle_buy' ] );
        add_action( 'smp_verified_profiles_dashboard_links', [ self::class, 'dashboard_link' ] );
    }

    public static function dashboard_link(): void {
        $s = FeatureSettings::all();
        if ( ! FeatureSettings::on( 'profile_managers' ) || ! FeatureSettings::payments_ready() || '' === (string) $s['profile_price_id'] ) {
            return;
        }
        $credits = (int) get_user_meta( get_current_user_id(), self::CREDITS_META, true );
        echo '<p><a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=smp_vp_buy_profile' ), 'smp_vp_buy_profile' ) ) . '">Buy a verified profile</a>'
            . ( $credits > 0 ? ' <span>Paid profiles not used yet: ' . esc_html( (string) $credits ) . '</span>' : '' ) . '</p>';
    }

    public static function handle_buy(): void {
        check_admin_referer( 'smp_vp_buy_profile' );
        $url = self::buy_profile_url( get_current_user_id() );
        if ( '' === $url ) {
            wp_die( 'Payments are not available right now.' );
        }
        wp_redirect( $url );
        exit;
    }

    /** A Stripe Checkout address for one more profile for the signed-in manager, or an error message. */
    public static function buy_profile_url( int $user_id ): string {
        $settings = FeatureSettings::all();
        if ( ! FeatureSettings::on( 'profile_managers' ) || ! FeatureSettings::payments_ready() || '' === (string) $settings['profile_price_id'] ) {
            return '';
        }
        $user    = get_user_by( 'id', $user_id );
        $session = \HWS\BaseTools\Payments\Stripe::checkout( [
            'price'       => (string) $settings['profile_price_id'],
            'mode'        => (string) $settings['profile_price_mode'],
            'email'       => $user ? (string) $user->user_email : '',
            'success_url' => admin_url( '?smp_vp_paid=1' ),
            'cancel_url'  => admin_url(),
            'metadata'    => [ 'smp_vp_action' => 'profile', 'user_id' => (string) $user_id ],
        ] );

        return is_wp_error( $session ) ? '' : (string) ( $session['url'] ?? '' );
    }

    /** @param array<string,mixed> $event */
    public static function handle_event( array $event ): void {
        if ( 'checkout.session.completed' !== ( $event['type'] ?? '' ) ) {
            return;
        }
        $meta = (array) ( $event['data']['object']['metadata'] ?? [] );
        if ( 'claim' === ( $meta['smp_vp_action'] ?? '' ) && FeatureSettings::on( 'claim_profile' ) ) {
            ClaimProfile::approve( (int) ( $meta['profile_id'] ?? 0 ), (string) ( $meta['claim_id'] ?? '' ) );
        }
        if ( 'profile' === ( $meta['smp_vp_action'] ?? '' ) && FeatureSettings::on( 'profile_managers' ) ) {
            $user_id = (int) ( $meta['user_id'] ?? 0 );
            if ( $user_id > 0 ) {
                update_user_meta( $user_id, self::CREDITS_META, (int) get_user_meta( $user_id, self::CREDITS_META, true ) + 1 );
                wp_mail( (string) get_option( 'admin_email' ), 'Paid verified profile', 'User #' . $user_id . ' paid for a verified profile.' );
            }
        }
    }
}
