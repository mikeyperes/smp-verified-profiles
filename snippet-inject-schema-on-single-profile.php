<?php

namespace smp_verified_profiles;

use Hexa\PluginCore\SchemaTools\SchemaInjector;

defined( 'ABSPATH' ) || exit;

function enable_snippet_inject_schema_on_single_profile(): void {
    static $registered = false;
    if ( $registered ) {
        return;
    }

    if ( class_exists( SchemaInjector::class ) ) {
        ( new SchemaInjector(
            __NAMESPACE__ . '\\smp_vp_current_profile_schema',
            [
                'hook'          => 'wp_head',
                'priority'      => 1,
                'script_id'     => 'smp-vp-profile-schema',
                'should_output' => static fn(): bool => is_singular( 'profile' ),
            ]
        ) )->register();
    }

    $registered = true;
}
add_action( 'init', __NAMESPACE__ . '\\enable_snippet_inject_schema_on_single_profile' );

/** @return array<string,mixed> */
function smp_vp_current_profile_schema(): array {
    $post = get_queried_object();
    if ( ! $post instanceof \WP_Post || 'profile' !== $post->post_type ) {
        return [];
    }

    $schema_json = function_exists( 'get_field' ) ? get_field( 'schema_markup', $post->ID, false ) : '';
    if ( is_array( $schema_json ) ) {
        return $schema_json;
    }

    if ( ! is_string( $schema_json ) || '' === trim( $schema_json ) ) {
        return [];
    }

    $schema = json_decode( $schema_json, true );
    return is_array( $schema ) ? $schema : [];
}
