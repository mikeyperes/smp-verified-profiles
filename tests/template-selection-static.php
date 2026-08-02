<?php

declare(strict_types=1);

$root    = dirname( __DIR__ );
$cards   = (string) file_get_contents( $root . '/verified-profile-display-templates.php' );
$pages   = (string) file_get_contents( $root . '/verified-profile-page-templates.php' );
$control = (string) file_get_contents( $root . '/lib/hexa-wordpress-plugin-core/src/WpAdminComponents/TemplateSelectionControl.php' );

$checks = [
    'Core owns the three-column visual template selector.' => str_contains( $control, 'final class TemplateSelectionControl' )
        && str_contains( $control, 'grid-template-columns:repeat(var(--hpc-template-columns,3),minmax(0,1fr))' ),
    'Profile cards use the Core selector for every selection surface.' => str_contains( $cards, 'use Hexa\PluginCore\WpAdminComponents\TemplateSelectionControl;' )
        && substr_count( $cards, 'smp_vp_display_template_selector([' ) >= 4,
    'All six profile-card templates remain available.' => 6 === substr_count( $cards, '"class" => "vp-' ),
    'Profile pages provide three complete templates.' => str_contains( $pages, "'editorial-masthead'" )
        && str_contains( $pages, "'modern-ledger'" )
        && str_contains( $pages, "'sidebar-dossier'" ),
    'Profile pages use the same Core visual selector.' => str_contains( $pages, 'TemplateSelectionControl::render(' )
        && str_contains( $pages, "'columns'        => 3" )
        && str_contains( $pages, "'preview_width'  => 1120" ),
    'Both display systems offer a custom-design mode.' => str_contains( $cards, 'SMP_VP_CUSTOM_TEMPLATE' )
        && str_contains( $pages, 'SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE' )
        && substr_count( $pages, "I'm going to design it myself" ) >= 2,
    'Card custom mode exits before plugin styles or markup.' => preg_match( '/function smp_vp_display_render_collection.*?smp_vp_display_is_custom_template.*?return "";.*?<style>/s', $cards ) === 1,
    'Page custom mode exits before plugin styles or markup.' => preg_match( '/function smp_vp_render_profile_page_template_data.*?SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE.*?return \'\';.*?<style>/s', $pages ) === 1,
    'Custom page mode disables automatic takeover.' => str_contains( $pages, "SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE !== \$settings['selected_template']" )
        && str_contains( $pages, "\$settings['enabled']     = false" ),
    'Explicit named profile-page shortcodes remain supported.' => str_contains( $pages, "[verified_profile_page template=\"' . \$key . '\"]" )
        && str_contains( $pages, "elseif ( ! isset( smp_vp_profile_page_templates()[ \$template ] ) )" ),
    'Profile-page colors and typography use Core controls.' => str_contains( $pages, 'DetailedColorPicker::render(' )
        && str_contains( $pages, 'TypographyControl::render(' )
        && str_contains( $pages, 'CoreUi::toggle(' ),
    'Admin previews use the exact frontend page renderer.' => str_contains( $pages, 'smp_vp_render_profile_page_template_data( $data, $key )' ),
];

foreach ( $checks as $message => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
        exit( 1 );
    }
}

echo "PASS: Verified Profiles uses reusable three-column selectors and a true no-output custom mode.\n";
