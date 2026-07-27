<?php

namespace smp_verified_profiles;

use Hexa\PluginCore\ContentTypes\ContentTypeRenderer;
use Hexa\PluginCore\FieldStructures\AcfFieldGroupRenderer;
use Hexa\PluginCore\FieldStructures\AcfSettingsPanel;
use smp_verified_profiles\ContentTypes\VerifiedProfileStructures;

defined( 'ABSPATH' ) || exit;

function smp_vp_render_content_types_tab(): void {
    if ( ! class_exists( ContentTypeRenderer::class ) || ! class_exists( AcfFieldGroupRenderer::class ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'The Hexa WP Core content-type components are unavailable.', 'smp-verified-profiles' ) . '</p></div>';
        return;
    }

    echo ( new ContentTypeRenderer() )->render(
        VerifiedProfileStructures::content_types(),
        [
            'title'          => 'Custom Post Types',
            'description'    => 'The Profile post-type key is fixed to protect existing content. Its public URL slug and all WordPress labels remain editable.',
            'persist_prefix' => 'smp-vp-content-types',
        ]
    );

    echo ( new AcfFieldGroupRenderer() )->render(
        VerifiedProfileStructures::acf_groups(),
        [
            'title'          => 'ACF Structures',
            'description'    => 'Enable or disable each Verified Profiles field structure and expand it for its exact location, dependencies, and field coverage.',
            'persist_prefix' => 'smp-vp-acf-structures',
        ]
    );
}
function smp_vp_render_profile_settings_tab(): void {
    if ( ! class_exists( AcfSettingsPanel::class ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'The Hexa WP Core settings component is unavailable.', 'smp-verified-profiles' ) . '</p></div>';
        return;
    }

    $panel = new AcfSettingsPanel(
        [
            'page_slug'      => Config::$settings_page_slug,
            'tab'            => 'profile-settings',
            'post_id'        => 'option',
            'field_groups'   => [ 'group_6850930366d8f', 'group_additional_shortcodes' ],
            'title'          => 'Verified Profile Program Settings',
            'description'    => 'Manage contributor-network identity, profile-program identity, Elementor loop assignments, and required page assignments. CPT labels and the public URL slug are managed in Custom Post Types.',
            'submit_value'   => 'Save Profile Settings',
            'updated_message'=> 'Verified Profile settings saved.',
            'persist_key'    => 'smp-vp-profile-settings',
            'open'           => true,
        ]
    );

    echo $panel->render();
}
