<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Snippets;

use Hexa\PluginCore\SnippetRegistry\SnippetRegistry;

defined( 'ABSPATH' ) || exit;

final class SnippetCatalog {
    private static ?SnippetRegistry $registry = null;

    public static function registry(): SnippetRegistry {
        if ( self::$registry instanceof SnippetRegistry ) {
            return self::$registry;
        }

        self::$registry = new SnippetRegistry();
        foreach ( [ 'acf', 'admin', 'non_admin' ] as $type ) {
            foreach ( \smp_verified_profiles\get_snippets( $type ) as $snippet ) {
                self::$registry->add( self::definition( $snippet, $type ) );
            }
        }

        return self::$registry;
    }

    /** @return array<string,array<string,string>> */
    public static function categories(): array {
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

    /** @return array<string,mixed> */
    public static function definition( array $snippet, string $type ): array {
        $id          = isset( $snippet['id'] ) ? (string) $snippet['id'] : '';
        $function    = isset( $snippet['function'] ) ? (string) $snippet['function'] : '';
        $fq_function = '' !== $function && str_contains( $function, '\\' )
            ? $function
            : '\\smp_verified_profiles\\' . $function;
        $description = self::description( $snippet, $type );
        $shortcodes  = self::shortcodes( $snippet );

        return array_merge(
            $snippet,
            [
                'id'               => $id,
                'name'             => self::label( isset( $snippet['name'] ) ? (string) $snippet['name'] : $id ),
                'description'      => $description,
                'category'         => $type,
                'option_key'       => $id,
                'function'         => $fq_function,
                'scope_admin_only' => 'admin' === $type || ! empty( $snippet['scope_admin_only'] ),
                'snippets'         => self::components( $id, $fq_function ),
                'shortcodes'       => $shortcodes,
                'testing'          => self::testing_rules( $fq_function, $shortcodes ),
                'readme'           => self::readme( $id, $type, $function, $description, $shortcodes ),
            ]
        );
    }

    public static function label( string $value ): string {
        $value = preg_replace( '/^(enable_|register_|add_)?(snippet_)?/', '', $value );
        $value = str_replace( [ '_acf_', '_wp_', '_' ], [ ' ACF ', ' WP ', ' ' ], (string) $value );

        return ucwords( trim( (string) preg_replace( '/\s+/', ' ', $value ) ) );
    }

    private static function description( array $snippet, string $type ): string {
        $description = isset( $snippet['description'] ) ? trim( (string) $snippet['description'] ) : '';
        if ( '' !== $description ) {
            return $description;
        }

        $name = self::label( isset( $snippet['name'] ) ? (string) $snippet['name'] : (string) ( $snippet['id'] ?? 'snippet' ) );

        return match ( $type ) {
            'acf'       => $name . ' registers field structures used by Verified Profiles.',
            'admin'     => $name . ' enables an admin-side workflow or WordPress dashboard adjustment.',
            'non_admin' => $name . ' enables frontend behavior, display output, schema support, or shortcode registration.',
            default     => $name . ' enables a Verified Profiles feature snippet.',
        };
    }

    /** @return array<int,array<string,string>> */
    private static function components( string $id, string $fq_function ): array {
        $items = [
            [
                'label'       => 'Option key',
                'value'       => $id,
                'description' => 'WordPress option used to enable or disable this snippet.',
            ],
        ];

        if ( '' !== $fq_function ) {
            $items[] = [
                'label'       => 'Activation function',
                'value'       => $fq_function,
                'description' => 'Function called when this snippet is enabled.',
            ];
        }

        return $items;
    }

    /** @return array<int,array<string,string>> */
    private static function shortcodes( array $snippet ): array {
        $function  = isset( $snippet['function'] ) ? (string) $snippet['function'] : '';
        $providers = [];

        if ( 'enable_snippet_muckrack_functionality' === $function ) {
            $providers[] = '\\smp_verified_profiles\\get_muckrack_shortcodes';
        }
        if ( 'enable_snippet_verified_profile_shortcodes' === $function ) {
            $providers[] = '\\smp_verified_profiles\\get_verified_profile_shortcodes';
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
                $tag     = (string) $tag;
                $items[] = [
                    'id'          => sanitize_key( $tag ),
                    'tag'         => $tag,
                    'label'       => self::label( $tag ),
                    'value'       => '[' . $tag . ']',
                    'description' => 'Registered by this snippet provider.',
                ];
            }
        }

        return $items;
    }

    /** @return array<int,array<string,mixed>> */
    private static function testing_rules( string $fq_function, array $shortcodes ): array {
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
                'description' => 'Confirms the configured activation function is loaded.',
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

    private static function readme( string $id, string $type, string $function, string $description, array $shortcodes ): string {
        $lines = [ self::label( $id ), '', wp_strip_all_tags( $description ), '', 'Category: ' . $type, 'Option: ' . $id ];

        if ( '' !== $function ) {
            $lines[] = 'Activation function: \\smp_verified_profiles\\' . $function;
        }
        if ( ! empty( $shortcodes ) ) {
            $lines[] = '';
            $lines[] = 'Related shortcodes:';
            foreach ( $shortcodes as $shortcode ) {
                $lines[] = '- ' . (string) ( $shortcode['value'] ?? '[' . (string) $shortcode['tag'] . ']' );
            }
        }

        $lines[] = '';
        $lines[] = 'Enable this domain feature when its Verified Profiles behavior is required.';

        return implode( "\n", $lines );
    }
}
