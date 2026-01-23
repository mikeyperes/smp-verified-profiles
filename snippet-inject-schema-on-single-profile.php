<?php namespace smp_verified_profiles;

function enable_snippet_inject_schema_on_single_profile() {
  add_action( 'wp_head', __NAMESPACE__ . '\inject_schema_on_single_profile', 1 );
}
add_action( 'init', __NAMESPACE__ . '\enable_snippet_inject_schema_on_single_profile' );

/**
 * Inject schema markup into the head section of a 'profile' post type single view.
 */
if ( ! function_exists( __NAMESPACE__ . '\\inject_schema_on_single_profile' ) ) {
  function inject_schema_on_single_profile() {
    $settings = get_verified_profile_settings();
    if ( is_singular( $settings['slug'] ) ) {
      global $post;
      // 2) false = return raw value without ACF formatting
      $schema_json = get_field( 'schema_markup', $post->ID, false );
      if ( $schema_json ) {
        echo '<script type="application/ld+json">' . $schema_json . '</script>';
      }
    }
  }
} else {
  write_log( "⚠️ Warning: " . __NAMESPACE__ . "\\inject_schema_on_single_profile already declared", true );
}
