<?php

namespace smp_verified_profiles;

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="primary" class="site-main smp-vp-profile-page-shell">
    <?php
    while ( have_posts() ) :
        the_post();

        $template = '';
        if ( function_exists( 'smp_verified_profiles\\smp_vp_profile_page_preview_template' ) ) {
            $template = \smp_verified_profiles\smp_vp_profile_page_preview_template();
        }

        if ( '' === $template && function_exists( 'smp_verified_profiles\\smp_vp_profile_page_settings' ) ) {
            $settings = \smp_verified_profiles\smp_vp_profile_page_settings();
            $template = (string) ( $settings['selected_template'] ?? '' );
        }

        if ( function_exists( 'smp_verified_profiles\\smp_vp_render_profile_page_template' ) ) {
            echo \smp_verified_profiles\smp_vp_render_profile_page_template( get_the_ID(), $template );
        } else {
            the_content();
        }
    endwhile;
    ?>
</main>

<?php
get_footer();
