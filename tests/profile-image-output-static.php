<?php

declare( strict_types=1 );

$shortcodes = (string) file_get_contents( dirname( __DIR__ ) . '/shortcodes.php' );
$templates  = (string) file_get_contents( dirname( __DIR__ ) . '/verified-profile-page-templates.php' );

$checks = [
    'Verified Profile image HTML uses WordPress responsive image markup.' => str_contains( $shortcodes, 'wp_get_attachment_image( $attachment_id, $size, false, $attributes )' ),
    'External or filtered featured-image URLs retain a safe HTML fallback.' => str_contains( $shortcodes, "'<img%s src=\"%s\" alt=\"%s\" loading=\"lazy\">'" ),
    'The built-in profile page requests the medium portrait derivative.' => str_contains( $templates, "'size'    => 'medium'" ),
    'The profile-page image helper uses medium when shortcode rendering is unavailable.' => str_contains( $templates, "function smp_vp_profile_page_image_url( int \$post_id, string \$size = 'medium' )" ),
];

$failed = false;
foreach ( $checks as $label => $passed ) {
    echo ( $passed ? 'PASS' : 'FAIL' ) . ': ' . $label . PHP_EOL;
    $failed = $failed || ! $passed;
}

exit( $failed ? 1 : 0 );
