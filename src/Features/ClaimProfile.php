<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Features;

use Hexa\PluginCore\CoreContracts\ModuleInterface;
use SMP\VerifiedProfiles\ProfileTypes\ProfileTypes;
use SMP\VerifiedProfiles\UserRoles\VerifiedProfileManagerRole;

defined( 'ABSPATH' ) || exit;

/**
 * "Claim this profile": the [smp_vp_claim_profile] shortcode on a profile
 * page. A visitor sends their name and email; with payments on they pay
 * through Stripe and the claim is approved by the payment, otherwise an admin
 * approves it on the Features tab. Approving makes the claimer the profile's
 * author (a profile manager). Off by default.
 */
final class ClaimProfile implements ModuleInterface {
    public const SHORTCODE = 'smp_vp_claim_profile';
    public const META      = '_smp_vp_claims';
    public const NONCE     = 'smp_vp_claim';

    public function register(): void {
        add_shortcode( self::SHORTCODE, [ self::class, 'shortcode' ] );
        add_action( 'wp_ajax_smp_vp_claim_submit', [ self::class, 'handle_submit' ] );
        add_action( 'wp_ajax_nopriv_smp_vp_claim_submit', [ self::class, 'handle_submit' ] );
    }

    public static function unclaimed( int $profile_id ): bool {
        $post = get_post( $profile_id );
        if ( ! $post || ! ProfileTypes::is_profile_type( (string) $post->post_type ) ) {
            return false;
        }
        $holder = get_user_by( 'slug', 'unclaimed' );

        return $holder && (int) $post->post_author === (int) $holder->ID;
    }

    /** @param array<string,string>|string $atts */
    public static function shortcode( $atts = [] ): string {
        $atts       = shortcode_atts( [ 'profile' => '', 'label' => 'Claim this profile' ], is_array( $atts ) ? $atts : [], self::SHORTCODE );
        $profile_id = '' !== $atts['profile'] ? (int) $atts['profile'] : (int) get_the_ID();
        if ( ! FeatureSettings::on( 'claim_profile' ) || ! self::unclaimed( $profile_id ) ) {
            return '';
        }
        $paid = FeatureSettings::payments_ready() && '' !== (string) FeatureSettings::all()['claim_price_id'];
        $id   = 'smp-vp-claim-' . $profile_id;
        ob_start();
        ?>
        <div class="smp-vp-claim" id="<?php echo esc_attr( $id ); ?>">
            <details>
                <summary class="smp-vp-claim__open"><?php echo esc_html( $atts['label'] ); ?></summary>
                <form class="smp-vp-claim__form" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
                    <input type="hidden" name="action" value="smp_vp_claim_submit">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( self::NONCE ) ); ?>">
                    <input type="hidden" name="profile_id" value="<?php echo esc_attr( (string) $profile_id ); ?>">
                    <p><label>Your full name<br><input type="text" name="name" required autocomplete="name"></label></p>
                    <p><label>Your email<br><input type="email" name="email" required autocomplete="email"></label></p>
                    <p><label>How you are connected to this profile<br><textarea name="note" rows="3"></textarea></label></p>
                    <p class="smp-vp-claim__trap" aria-hidden="true" style="position:absolute;left:-9999px"><input type="text" name="website" tabindex="-1" autocomplete="off"></p>
                    <p><button type="submit"><?php echo esc_html( $paid ? 'Continue to payment' : 'Send claim' ); ?></button></p>
                    <p class="smp-vp-claim__status" aria-live="polite"></p>
                </form>
            </details>
            <script>
            (function () {
                var form = document.querySelector('#<?php echo esc_js( $id ); ?> form');
                if (!form) return;
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var status = form.querySelector('.smp-vp-claim__status');
                    status.textContent = 'Sending...';
                    fetch(form.getAttribute('data-ajax'), { method: 'POST', credentials: 'same-origin', body: new FormData(form) })
                        .then(function (r) { return r.json(); })
                        .then(function (j) {
                            var d = j && j.data ? j.data : {};
                            if (j && j.success && d.redirect) { window.location.href = d.redirect; return; }
                            status.textContent = d.message || 'Not sent.';
                            if (j && j.success) form.querySelector('button').disabled = true;
                        })
                        .catch(function () { status.textContent = 'Not sent: the request failed.'; });
                });
            })();
            </script>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    public static function handle_submit(): void {
        if ( ! FeatureSettings::on( 'claim_profile' ) || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Claiming is not available. Refresh the page and try again.' ] );
        }
        $profile_id = absint( $_POST['profile_id'] ?? 0 );
        $name       = sanitize_text_field( wp_unslash( (string) ( $_POST['name'] ?? '' ) ) );
        $email      = strtolower( sanitize_email( wp_unslash( (string) ( $_POST['email'] ?? '' ) ) ) );
        $note       = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );
        if ( '' !== (string) ( $_POST['website'] ?? '' ) ) {
            wp_send_json_success( [ 'message' => 'Thank you. We will be in touch.' ] );
        }
        if ( ! self::unclaimed( $profile_id ) || '' === $name || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => '' === $name || ! is_email( $email ) ? 'Enter your name and a valid email.' : 'This profile cannot be claimed.' ] );
        }
        $claim = [ 'id' => wp_generate_password( 12, false ), 'name' => $name, 'email' => $email, 'note' => $note, 'status' => 'pending', 'at' => time() ];
        self::save_claim( $profile_id, $claim );

        $settings = FeatureSettings::all();
        if ( FeatureSettings::payments_ready() && '' !== (string) $settings['claim_price_id'] ) {
            $session = \HWS\BaseTools\Payments\Stripe::checkout( [
                'price'       => (string) $settings['claim_price_id'],
                'mode'        => (string) $settings['claim_price_mode'],
                'email'       => $email,
                'success_url' => add_query_arg( 'claim', 'paid', (string) get_permalink( $profile_id ) ),
                'cancel_url'  => (string) get_permalink( $profile_id ),
                'metadata'    => [ 'smp_vp_action' => 'claim', 'profile_id' => (string) $profile_id, 'claim_id' => $claim['id'] ],
            ] );
            if ( is_wp_error( $session ) || empty( $session['url'] ) ) {
                wp_send_json_error( [ 'message' => 'Payment could not start. Your claim was saved and we will contact you.' ] );
            }
            wp_send_json_success( [ 'redirect' => (string) $session['url'] ] );
        }

        wp_mail( (string) get_option( 'admin_email' ), 'New profile claim: ' . get_the_title( $profile_id ), $name . ' <' . $email . '> asked to claim "' . get_the_title( $profile_id ) . "\".\n\n" . $note . "\n\nReview it: " . admin_url( 'options-general.php?page=' . self::admin_page() . '&tab=features' ) );
        wp_send_json_success( [ 'message' => 'Thank you. Your claim was sent for review.' ] );
    }

    /** Makes the claimer the profile's author; creates their account when they have none. */
    public static function approve( int $profile_id, string $claim_id ): bool {
        $claims = self::claims( $profile_id );
        foreach ( $claims as $i => $claim ) {
            if ( $claim['id'] !== $claim_id || 'pending' !== $claim['status'] ) {
                continue;
            }
            $user = get_user_by( 'email', $claim['email'] );
            if ( ! $user ) {
                $user_id = wp_insert_user( [ 'user_login' => sanitize_user( $claim['email'], true ), 'user_email' => $claim['email'], 'display_name' => $claim['name'], 'first_name' => $claim['name'], 'user_pass' => wp_generate_password( 24 ), 'role' => VerifiedProfileManagerRole::ROLE ] );
                if ( is_wp_error( $user_id ) ) {
                    return false;
                }
                wp_new_user_notification( (int) $user_id, null, 'user' );
                $user = get_user_by( 'id', (int) $user_id );
            } elseif ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
                $user->add_role( VerifiedProfileManagerRole::ROLE );
            }
            wp_update_post( [ 'ID' => $profile_id, 'post_author' => (int) $user->ID ] );
            $claims[ $i ]['status'] = 'approved';
            $claims[ $i ]['done']   = time();
            update_post_meta( $profile_id, self::META, $claims );

            return true;
        }

        return false;
    }

    public static function reject( int $profile_id, string $claim_id ): bool {
        $claims = self::claims( $profile_id );
        foreach ( $claims as $i => $claim ) {
            if ( $claim['id'] === $claim_id && 'pending' === $claim['status'] ) {
                $claims[ $i ]['status'] = 'rejected';
                $claims[ $i ]['done']   = time();
                update_post_meta( $profile_id, self::META, $claims );

                return true;
            }
        }

        return false;
    }

    /** @return list<array<string,mixed>> */
    public static function claims( int $profile_id ): array {
        $claims = get_post_meta( $profile_id, self::META, true );

        return is_array( $claims ) ? array_values( $claims ) : [];
    }

    /** @return list<array{profile_id:int,claim:array<string,mixed>}> Every pending claim, newest first. */
    public static function pending(): array {
        $out = [];
        foreach ( get_posts( [ 'post_type' => ProfileTypes::keys(), 'post_status' => 'any', 'numberposts' => 200, 'meta_key' => self::META, 'fields' => 'ids' ] ) as $id ) {
            foreach ( self::claims( (int) $id ) as $claim ) {
                if ( 'pending' === $claim['status'] ) {
                    $out[] = [ 'profile_id' => (int) $id, 'claim' => $claim ];
                }
            }
        }
        usort( $out, static fn( $a, $b ) => (int) $b['claim']['at'] <=> (int) $a['claim']['at'] );

        return $out;
    }

    public static function admin_page(): string {
        return class_exists( '\\smp_verified_profiles\\Config' ) && ! empty( \smp_verified_profiles\Config::$settings_page_slug ) ? (string) \smp_verified_profiles\Config::$settings_page_slug : 'smp-verified-profiles';
    }

    /** @param array<string,mixed> $claim */
    private static function save_claim( int $profile_id, array $claim ): void {
        $claims   = self::claims( $profile_id );
        $claims[] = $claim;
        update_post_meta( $profile_id, self::META, array_slice( $claims, -20 ) );
    }
}
