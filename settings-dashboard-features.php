<?php

namespace smp_verified_profiles;

use Hexa\PluginCore\WpAdminComponents\CoreUi;
use SMP\VerifiedProfiles\Features\ClaimProfile;
use SMP\VerifiedProfiles\Features\FeatureSettings;
use SMP\VerifiedProfiles\Features\FeaturesAdmin;

defined( 'ABSPATH' ) || exit;

add_filter( 'smp_vp_dashboard_tabs', __NAMESPACE__ . '\\smp_vp_features_dashboard_tab' );
add_filter( 'smp_vp_render_dashboard_tab', __NAMESPACE__ . '\\smp_vp_render_features_dashboard_tab', 10, 2 );

function smp_vp_features_dashboard_tab( array $tabs ): array {
    $tabs['features'] = 'Features';
    return $tabs;
}

function smp_vp_render_features_dashboard_tab( $rendered, string $tab_id ) {
    if ( 'features' !== $tab_id ) {
        return $rendered;
    }
    smp_vp_render_features_tab();
    return true;
}

function smp_vp_render_features_tab(): void {
    if ( ! current_user_can( Config::$settings_page_capability ) ) {
        echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>';
        return;
    }
    CoreUi::render_assets();
    $s       = FeatureSettings::all();
    $stripe  = FeatureSettings::stripe_available();
    $ready   = $stripe && \HWS\BaseTools\Payments\Stripe::ready();
    $payments_url = admin_url( 'options-general.php?page=hws-core-tools&tab=payments' );
    $price = static function ( string $prefix, array $s ): string {
        return '<p><label>Stripe price ID<br><input type="text" class="regular-text" name="' . $prefix . '_price_id" value="' . esc_attr( (string) $s[ $prefix . '_price_id' ] ) . '" placeholder="price_..."></label></p>'
            . '<p><label>Charged <select name="' . $prefix . '_price_mode"><option value="payment"' . selected( $s[ $prefix . '_price_mode' ], 'payment', false ) . '>once</option><option value="subscription"' . selected( $s[ $prefix . '_price_mode' ], 'subscription', false ) . '>as a subscription</option></select></label></p>';
    };
    ?>
    <div class="hpc-ui smp-vp-features" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( FeaturesAdmin::NONCE ) ); ?>">
        <style>.smp-vp-features{max-width:860px}.smp-vp-features .hpc-card{margin:0 0 18px}.smp-vp-claims td,.smp-vp-claims th{padding:6px 8px;text-align:left;vertical-align:top}</style>
        <?php
        echo CoreUi::card( [
            'title'     => 'Profile managers',
            'body_html' => '<p>People who manage one or more profiles get their own dashboard (Profiles Dashboard), and with payments on a "Buy a verified profile" button. Off by default.</p>'
                . CoreUi::toggle( 'profile_managers', (bool) $s['profile_managers'], 'Use profile managers on this site', [ 'id' => 'smp-vp-f-managers' ] )
                . '<h4>Price of one verified profile</h4>' . $price( 'profile', $s ),
        ] );
        echo CoreUi::card( [
            'title'     => 'Claim your profile',
            'body_html' => '<p>Put <code>[' . ClaimProfile::SHORTCODE . ']</code> on the profile page (in its Elementor template). On an unclaimed profile it shows a "Claim this profile" button and a short form; on every other profile it shows nothing. With payments on and a claim price set, the claimer pays through Stripe and the claim approves itself; otherwise you approve it below. Off by default.</p>'
                . CoreUi::toggle( 'claim_profile', (bool) $s['claim_profile'], 'Let visitors claim unclaimed profiles', [ 'id' => 'smp-vp-f-claim' ] )
                . '<h4>Price of a claim (empty means free, approved by hand)</h4>' . $price( 'claim', $s ),
        ] );
        echo CoreUi::card( [
            'title'     => 'Payments',
            'body_html' => '<p>Payments use the one Stripe connection in HWS Base Tools, shared with every HWS plugin. ' . ( ! $stripe ? '<strong>HWS Base Tools 13.46 or newer is needed.</strong>' : ( $ready ? 'Stripe is connected in ' . esc_html( \HWS\BaseTools\Payments\Stripe::mode() ) . ' mode.' : '<strong>Stripe is off or has no key yet.</strong>' ) . ' <a href="' . esc_url( $payments_url ) . '">Open Payments in HWS Base Tools</a>' ) . '</p>'
                . CoreUi::toggle( 'payments', (bool) $s['payments'], 'Take payments for profiles and claims', [ 'id' => 'smp-vp-f-payments', 'disabled' => ! $stripe ] ),
        ] );
        echo CoreUi::card( [ 'title' => 'Save', 'body_html' => '<button type="button" class="hpc-button smp-vp-f-save">Save features</button> <span class="smp-vp-f-status" aria-live="polite"></span>' ] );

        $rows = '';
        foreach ( ClaimProfile::pending() as $item ) {
            $c     = $item['claim'];
            $rows .= '<tr><td><a href="' . esc_url( (string) get_permalink( $item['profile_id'] ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $item['profile_id'] ) ) . '</a></td><td>' . esc_html( $c['name'] ) . '<br>' . esc_html( $c['email'] ) . '</td><td>' . esc_html( (string) $c['note'] ) . '</td><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $c['at'] ) ) . '</td>'
                . '<td><button type="button" class="hpc-button" data-claim="approve" data-profile="' . esc_attr( (string) $item['profile_id'] ) . '" data-id="' . esc_attr( $c['id'] ) . '">Approve</button> <button type="button" class="hpc-button secondary" data-claim="reject" data-profile="' . esc_attr( (string) $item['profile_id'] ) . '" data-id="' . esc_attr( $c['id'] ) . '">Reject</button></td></tr>';
        }
        echo CoreUi::card( [
            'title'     => 'Claims waiting for review',
            'body_html' => '' === $rows ? '<p>None.</p>' : '<table class="smp-vp-claims"><tr><th>Profile</th><th>Claimer</th><th>Note</th><th>Sent</th><th></th></tr>' . $rows . '</table><p>Approving makes the claimer the profile\'s author and a profile manager; an account is created and emailed when they have none.</p>',
        ] );
        ?>
        <script>
        (function () {
            var root = document.querySelector('.smp-vp-features');
            if (!root) return;
            function post(data, done) {
                data.append('nonce', root.getAttribute('data-nonce'));
                fetch(root.getAttribute('data-ajax-url'), { method: 'POST', credentials: 'same-origin', body: data })
                    .then(function (r) { return r.json(); }).then(done)
                    .catch(function () { done({ data: { message: 'The request failed.' } }); });
            }
            root.querySelector('.smp-vp-f-save').addEventListener('click', function () {
                var data = new FormData(), status = root.querySelector('.smp-vp-f-status');
                data.append('action', 'smp_vp_features_save');
                root.querySelectorAll('input[name],select[name]').forEach(function (f) {
                    if (f.type === 'checkbox') { if (f.checked) data.append(f.name, '1'); return; }
                    data.append(f.name, f.value);
                });
                status.textContent = 'Saving...';
                post(data, function (j) { status.textContent = j && j.data ? j.data.message : 'Not saved.'; });
            });
            root.querySelectorAll('[data-claim]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var data = new FormData();
                    data.append('action', 'smp_vp_claim_decide');
                    data.append('decision', b.getAttribute('data-claim'));
                    data.append('profile_id', b.getAttribute('data-profile'));
                    data.append('claim_id', b.getAttribute('data-id'));
                    post(data, function (j) { var row = b.closest('tr'); row.lastChild.textContent = j && j.data ? j.data.message : 'Failed.'; });
                });
            });
        })();
        </script>
    </div>
    <?php
}
