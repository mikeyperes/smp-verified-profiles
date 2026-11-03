<?php
namespace SMP\VerifiedProfiles\Sitemaps;

use Hexa\PluginCore\CoreContracts\ModuleInterface;
use Hexa\PluginCore\Sitemaps\SitemapProviders;
use Hexa\PluginCore\Sitemaps\SitemapsPanel;
use SMP\VerifiedProfiles\ProfileTypes\ProfileTypes;

/**
 * The sitemaps this plugin owns: one per profile type, including podcast
 * guests. HWS Base Tools shows them read-only and links to the Sitemaps tab.
 */
final class SitemapTypes implements ModuleInterface {
    public const SLUG = 'smp-verified-profiles';

    public static function definitions(): array {
        $defs = [];
        foreach ( array_unique( array_merge( ProfileTypes::keys(), [ ProfileTypes::GUEST_TYPE ] ) ) as $type ) {
            $defs[] = [ 'id' => 'post_type_' . $type, 'recommended' => true, 'why' => 'Profile pages are the pages this plugin exists to get found.' ];
        }
        return $defs;
    }

    public function register(): void {
        if ( ! class_exists( SitemapProviders::class ) ) {
            return;
        }
        $url = admin_url( 'options-general.php?page=' . \SMP\VerifiedProfiles\Features\ClaimProfile::admin_page() . '&tab=sitemaps' );
        SitemapProviders::register( self::SLUG, 'SMP Verified Profiles', $url, self::definitions() );
        add_filter( 'smp_vp_dashboard_tabs', static function ( array $tabs ): array { $tabs['sitemaps'] = 'Sitemaps'; return $tabs; } );
        add_filter( 'smp_vp_render_dashboard_tab', static function ( $rendered, string $tab ) {
            if ( 'sitemaps' !== $tab ) {
                return $rendered;
            }
            SitemapsPanel::render( self::SLUG );
            return true;
        }, 10, 2 );
    }
}
