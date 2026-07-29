<?php

$root   = dirname( __DIR__ );
$source = (string) file_get_contents( $root . '/shortcodes.php' );

$assertions = [
    'relationship lookup uses the exact ACF repeater metadata pattern' => str_contains( $source, "'^profiles_[0-9]+_profile$'" ),
    'relationship lookup requires an exact profile ID value'           => str_contains( $source, 'AND meta_value = %s' ),
    'post query is constrained to proven relationship IDs'              => str_contains( $source, "'post__in'               => \$candidate_ids" ),
    'sticky posts cannot enter an empty relationship result'             => str_contains( $source, "'ignore_sticky_posts'    => true" ),
    'legacy global posts_where mutation was removed'                     => ! str_contains( $source, 'function customize_posts_where' ),
];

foreach ( $assertions as $label => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, "FAIL: {$label}\n" );
        exit( 1 );
    }
}

echo 'Verified Profile related coverage query: ' . count( $assertions ) . " assertions passed.\n";
