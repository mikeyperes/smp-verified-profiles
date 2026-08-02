<?php

$root   = dirname( __DIR__ );
$source = (string) file_get_contents( $root . '/verified-profile-display-templates.php' );

$assertions = [
    'historical single-post Loop Item option is resolved' => str_contains( $source, 'options_verified_profile_loop_items_display_single_post_mentioned_in_article' ),
    'only Elementor Loop Item templates are accepted'     => str_contains( $source, '"loop-item" === (string) get_post_meta($template_id, "_elementor_template_type", true)' ),
    'each profile becomes the Elementor dynamic context'  => str_contains( $source, 'setup_postdata($profile);' ),
    'saved Loop Item is rendered through Elementor'       => str_contains( $source, 'get_builder_content_for_display($template_id, true)' ),
    'desktop collection retains six columns'              => str_contains( $source, 'grid-template-columns:repeat(6,minmax(0,1fr))' ),
    'mobile collection retains two columns'               => str_contains( $source, 'grid-template-columns:repeat(2,minmax(0,1fr))' ),
    'built-in renderer remains available as fallback'     => str_contains( $source, '$render_args = array_replace($settings' ),
];

foreach ( $assertions as $label => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, "FAIL: {$label}\n" );
        exit( 1 );
    }
}

echo 'Single-post Elementor Loop Item compatibility: ' . count( $assertions ) . " assertions passed.\n";
