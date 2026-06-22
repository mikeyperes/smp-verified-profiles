<?php
/**
 * SMP Verified Profiles - Snippets Dashboard Tab
 * 
 * Displays all available snippets with:
 * - Toggle switches for enabling/disabling
 * - Function/option ID code displayed prominently at the front
 * - Grouped by category (ACF, Admin, Frontend)
 * - Visual feedback on toggle state changes
 * 
 * @package smp_verified_profiles
 * @since 6.3
 */

namespace smp_verified_profiles;

use Hexa\PluginCore\SnippetRegistry\SnippetRegistry;
use Hexa\PluginCore\SnippetRegistry\SnippetRenderer;

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display the Snippets tab content
 * Shows all snippets organized by category with toggle switches
 */
function display_settings_snippets() {
    if ( ! class_exists( SnippetRenderer::class ) || ! class_exists( SnippetRegistry::class ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Hexa Core SnippetRegistry is not available.', 'smp-verified-profiles' ) . '</p></div>';
        return;
    }

    echo ( new SnippetRenderer() )->render(
        smp_vp_snippet_registry(),
        [
            'title'         => 'Snippets Configuration',
            'description'   => 'Enable, document, and test Verified Profiles feature snippets through the shared Hexa WP Core snippet registry.',
            'toggle_action' => 'smp_vp_toggle_snippet',
            'test_action'   => 'smp_vp_test_snippet',
            'nonce'         => function_exists( __NAMESPACE__ . '\\smp_vp_ajax_nonce' ) ? smp_vp_ajax_nonce() : wp_create_nonce( Config::$ajax_nonce_action ),
            'nonce_field'   => Config::$ajax_nonce_field,
            'root_id'       => 'smp-vp-snippet-registry',
            'categories'    => smp_vp_snippet_categories(),
        ]
    );
}

function smp_vp_snippet_registry(): SnippetRegistry {
    static $registry = null;

    if ( $registry instanceof SnippetRegistry ) {
        return $registry;
    }

    $registry = new SnippetRegistry();
    foreach ( [ 'acf', 'admin', 'non_admin' ] as $type ) {
        foreach ( get_snippets( $type ) as $snippet ) {
            $registry->add( smp_vp_snippet_definition( $snippet, $type ) );
        }
    }

    return $registry;
}

function smp_vp_snippet_categories(): array {
    return [
        'acf' => [
            'label'       => 'ACF Field Groups',
            'description' => 'Register Advanced Custom Fields groups, profile structures, and supporting post types.',
        ],
        'admin' => [
            'label'       => 'Admin Features',
            'description' => 'WordPress admin customizations and backend workflow helpers.',
        ],
        'non_admin' => [
            'label'       => 'Frontend Features',
            'description' => 'Public-facing display features, schema integrations, and shortcode providers.',
        ],
    ];
}

function smp_vp_snippet_definition( array $snippet, string $type ): array {
    $id          = isset( $snippet['id'] ) ? (string) $snippet['id'] : '';
    $function    = isset( $snippet['function'] ) ? (string) $snippet['function'] : '';
    $fq_function = '' !== $function && str_contains( $function, '\\' ) ? $function : __NAMESPACE__ . '\\' . $function;
    $description = smp_vp_snippet_description( $snippet, $type );
    $shortcodes  = smp_vp_snippet_shortcodes( $snippet );

    return array_merge(
        $snippet,
        [
            'id'               => $id,
            'name'             => smp_vp_snippet_label( isset( $snippet['name'] ) ? (string) $snippet['name'] : $id ),
            'description'      => $description,
            'category'         => $type,
            'option_key'       => $id,
            'function'         => $fq_function,
            'scope_admin_only' => $type === 'admin' || ! empty( $snippet['scope_admin_only'] ),
            'snippets'         => smp_vp_snippet_components( $id, $function, $fq_function ),
            'shortcodes'       => $shortcodes,
            'testing'          => smp_vp_snippet_testing_rules( $id, $fq_function, $shortcodes ),
            'readme'           => smp_vp_snippet_readme( $id, $type, $function, $description, $shortcodes ),
        ]
    );
}

function smp_vp_snippet_label( string $value ): string {
    $value = preg_replace( '/^(enable_|register_|add_)?(snippet_)?/', '', $value );
    $value = str_replace( [ '_acf_', '_wp_', '_' ], [ ' ACF ', ' WP ', ' ' ], (string) $value );

    return ucwords( trim( preg_replace( '/\s+/', ' ', $value ) ) );
}

function smp_vp_snippet_description( array $snippet, string $type ): string {
    $description = isset( $snippet['description'] ) ? trim( (string) $snippet['description'] ) : '';
    if ( '' !== $description ) {
        return $description;
    }

    $name = smp_vp_snippet_label( isset( $snippet['name'] ) ? (string) $snippet['name'] : (string) ( $snippet['id'] ?? 'snippet' ) );

    return match ( $type ) {
        'acf'       => $name . ' registers field structures used by Verified Profiles.',
        'admin'     => $name . ' enables an admin-side workflow or WordPress dashboard adjustment.',
        'non_admin' => $name . ' enables frontend behavior, display output, schema support, or shortcode registration.',
        default     => $name . ' enables a Verified Profiles feature snippet.',
    };
}

function smp_vp_snippet_components( string $id, string $function, string $fq_function ): array {
    $items = [
        [
            'label'       => 'Option key',
            'value'       => $id,
            'description' => 'WordPress option used to enable or disable this snippet.',
        ],
    ];

    if ( '' !== $function ) {
        $items[] = [
            'label'       => 'Activation function',
            'value'       => $fq_function,
            'description' => 'Function called by activate_snippets() when this snippet is enabled.',
        ];
    }

    $source = smp_vp_locate_function_file( $function );
    if ( '' !== $source ) {
        $items[] = [
            'label'       => 'Source file',
            'value'       => $source,
            'description' => 'Plugin file that declares the activation function.',
        ];
    }

    return $items;
}

function smp_vp_snippet_shortcodes( array $snippet ): array {
    $function = isset( $snippet['function'] ) ? (string) $snippet['function'] : '';
    $providers = [];

    if ( 'enable_snippet_muckrack_functionality' === $function ) {
        $providers[] = __NAMESPACE__ . '\\get_muckrack_shortcodes';
    }

    if ( 'enable_snippet_verified_profile_shortcodes' === $function ) {
        $providers[] = __NAMESPACE__ . '\\get_verified_profile_shortcodes';
    }

    $items = [];
    foreach ( $providers as $provider ) {
        if ( ! is_callable( $provider ) ) {
            continue;
        }

        $provided = call_user_func( $provider );
        if ( ! is_array( $provided ) ) {
            continue;
        }

        foreach ( array_keys( $provided ) as $tag ) {
            $meta = function_exists( __NAMESPACE__ . '\\smp_vp_shortcode_metadata' ) ? smp_vp_shortcode_metadata( (string) $tag ) : [];
            $items[] = [
                'id'          => sanitize_key( (string) $tag ),
                'tag'         => (string) $tag,
                'label'       => isset( $meta['description'] ) ? (string) $meta['description'] : smp_vp_snippet_label( (string) $tag ),
                'value'       => isset( $meta['example'] ) ? (string) $meta['example'] : '[' . (string) $tag . ']',
                'description' => 'Registered by this snippet provider.',
            ];
        }
    }

    return $items;
}

function smp_vp_snippet_testing_rules( string $id, string $fq_function, array $shortcodes ): array {
    $rules = [
        [
            'id'          => 'option_enabled',
            'label'       => 'Snippet option is enabled',
            'type'        => 'option_enabled',
            'required'    => true,
            'description' => 'Confirms the controlling WordPress option is active.',
        ],
    ];

    if ( '' !== $fq_function ) {
        $rules[] = [
            'id'          => 'activation_function_exists',
            'label'       => 'Activation function exists',
            'type'        => 'function_exists',
            'function'    => $fq_function,
            'required'    => true,
            'description' => 'Confirms the function configured for this snippet is loaded.',
        ];
    }

    foreach ( $shortcodes as $shortcode ) {
        if ( empty( $shortcode['tag'] ) ) {
            continue;
        }

        $rules[] = [
            'id'          => 'shortcode_' . sanitize_key( (string) $shortcode['tag'] ),
            'label'       => 'Shortcode [' . (string) $shortcode['tag'] . '] exists',
            'type'        => 'shortcode_exists',
            'tag'         => (string) $shortcode['tag'],
            'required'    => false,
            'description' => 'Confirms WordPress has the shortcode registered in the current runtime.',
        ];
    }

    return $rules;
}

function smp_vp_snippet_readme( string $id, string $type, string $function, string $description, array $shortcodes ): string {
    $lines = [
        smp_vp_snippet_label( $id ),
        '',
        wp_strip_all_tags( $description ),
        '',
        'Category: ' . $type,
        'Option: ' . $id,
    ];

    if ( '' !== $function ) {
        $lines[] = 'Activation function: ' . __NAMESPACE__ . '\\' . $function;
    }

    if ( ! empty( $shortcodes ) ) {
        $lines[] = '';
        $lines[] = 'Related shortcodes:';
        foreach ( $shortcodes as $shortcode ) {
            $lines[] = '- ' . (string) ( $shortcode['value'] ?? '[' . (string) $shortcode['tag'] . ']' );
        }
    }

    $lines[] = '';
    $lines[] = 'Use this snippet when the related Verified Profiles feature should be active. Disable it to remove that feature without uninstalling the plugin.';

    return implode( "\n", $lines );
}

function smp_vp_locate_function_file( string $function ): string {
    if ( '' === $function ) {
        return '';
    }

    static $files = null;
    if ( null === $files ) {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( __DIR__, \FilesystemIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $file ) {
            if ( $file instanceof \SplFileInfo && 'php' === $file->getExtension() ) {
                $files[] = $file->getPathname();
            }
        }
    }

    foreach ( $files as $file ) {
        $contents = file_get_contents( $file );
        if ( false === $contents ) {
            continue;
        }

        if ( preg_match( '/function\s+' . preg_quote( $function, '/' ) . '\s*\(/', $contents ) ) {
            return ltrim( str_replace( __DIR__, '', $file ), DIRECTORY_SEPARATOR );
        }
    }

    return '';
}
