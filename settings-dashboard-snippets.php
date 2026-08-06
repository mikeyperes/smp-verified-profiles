<?php

declare( strict_types=1 );

namespace smp_verified_profiles;

use Hexa\PluginCore\SnippetRegistry\SnippetRegistry;
use Hexa\PluginCore\SnippetRegistry\SnippetRenderer;
use SMP\VerifiedProfiles\Snippets\SnippetCatalog;

defined( 'ABSPATH' ) || exit;

function display_settings_snippets(): void {
    if ( ! class_exists( SnippetRenderer::class ) || ! class_exists( SnippetRegistry::class ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Hexa Core SnippetRegistry is not available.', 'smp-verified-profiles' ) . '</p></div>';
        return;
    }

    echo ( new SnippetRenderer() )->render(
        SnippetCatalog::registry(),
        [
            'title'         => 'Snippets Configuration',
            'description'   => 'Enable, document, and test Verified Profiles feature snippets through the shared Hexa WP Core snippet registry.',
            'toggle_action' => 'smp_vp_toggle_snippet',
            'test_action'   => 'smp_vp_test_snippet',
            'nonce'         => smp_vp_ajax_nonce(),
            'nonce_field'   => Config::$ajax_nonce_field,
            'root_id'       => 'smp-vp-snippet-registry',
            'categories'    => SnippetCatalog::categories(),
        ]
    );
}

/** Legacy public helpers delegate to the namespaced host catalog. */
function smp_vp_snippet_registry(): SnippetRegistry {
    return SnippetCatalog::registry();
}

function smp_vp_snippet_categories(): array {
    return SnippetCatalog::categories();
}

function smp_vp_snippet_definition( array $snippet, string $type ): array {
    return SnippetCatalog::definition( $snippet, $type );
}

function smp_vp_snippet_label( string $value ): string {
    return SnippetCatalog::label( $value );
}
