<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Elementor;

use Hexa\PluginCore\CoreContracts\ModuleInterface;

defined( 'ABSPATH' ) || exit;

/** Registers the Verified Profiles dynamic tags in Elementor (group "Verified Profiles"). */
final class DynamicTags implements ModuleInterface {
    public const GROUP = 'smp-verified-profiles';

    public function register(): void {
        add_action( 'elementor/dynamic_tags/register', [ self::class, 'register_tags' ] );
    }

    /** @param object $manager Elementor's dynamic tags manager. */
    public static function register_tags( $manager ): void {
        if ( ! class_exists( '\\Elementor\\Core\\DynamicTags\\Tag' ) ) {
            return;
        }
        $manager->register_group( self::GROUP, [ 'title' => 'Verified Profiles' ] );
        $manager->register( new ProfileFieldTag() );
        $manager->register( new LinkedProfileTag() );
    }
}
