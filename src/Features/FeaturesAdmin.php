<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Features;

use Hexa\PluginCore\CoreContracts\ModuleInterface;
use Hexa\PluginCore\WpAdminAjax\AjaxActionRegistry;
use Hexa\PluginCore\WpAdminAjax\AjaxRequest;

defined( 'ABSPATH' ) || exit;

/** Saves the Features tab and approves or rejects claims. */
final class FeaturesAdmin implements ModuleInterface {
    public const NONCE = 'smp_vp_features';

    public function register(): void {
        AjaxActionRegistry::create( [ 'action_prefix' => 'smp_vp', 'nonce_action' => self::NONCE, 'nonce_field' => 'nonce' ] )
            ->register( [
                'features_save' => [ 'callback' => [ self::class, 'handle_save' ] ],
                'claim_decide'  => [ 'callback' => [ self::class, 'handle_decide' ] ],
            ] );
    }

    /** @return array<string,mixed> */
    public static function handle_save( AjaxRequest $request ): array {
        $settings = FeatureSettings::update( [
            'profile_managers'   => $request->bool( 'profile_managers', false, 'post' ),
            'claim_profile'      => $request->bool( 'claim_profile', false, 'post' ),
            'payments'           => $request->bool( 'payments', false, 'post' ),
            'profile_price_id'   => $request->text( 'profile_price_id', '', 'post' ),
            'profile_price_mode' => $request->key( 'profile_price_mode', 'payment', 'post' ),
            'claim_price_id'     => $request->text( 'claim_price_id', '', 'post' ),
            'claim_price_mode'   => $request->key( 'claim_price_mode', 'payment', 'post' ),
        ] );

        return [ 'saved' => true, 'message' => 'Features saved.', 'settings' => $settings ];
    }

    /** @return array<string,mixed> */
    public static function handle_decide( AjaxRequest $request ): array {
        $profile = $request->int( 'profile_id', 0, 'post' );
        $claim   = $request->text( 'claim_id', '', 'post' );
        $done    = 'approve' === $request->key( 'decision', '', 'post' ) ? ClaimProfile::approve( $profile, $claim ) : ClaimProfile::reject( $profile, $claim );

        return [ 'saved' => $done, 'message' => $done ? 'Done.' : 'That claim is no longer pending.' ];
    }
}
