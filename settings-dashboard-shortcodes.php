<?php
/**
 * SMP Verified Profiles - Shortcodes dashboard tab.
 *
 * @package smp_verified_profiles
 * @since 6.4
 */

namespace smp_verified_profiles;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display the Shortcodes tab.
 */
function display_settings_shortcodes() {
    $shortcodes = smp_vp_discover_shortcodes();
    $profiles   = smp_vp_get_verified_profile_posts();
    $profile_id = ! empty( $profiles ) ? (int) $profiles[0]->ID : 0;
    ?>
    <div class="smp-panel">
        <div class="smp-panel-header">Shortcodes</div>
        <div class="smp-panel-body">
            <p>Shortcodes discovered from this plugin's PHP files and shortcode provider functions. Registered status reflects the current WordPress runtime.</p>

            <table class="smp-table smp-shortcodes-table">
                <thead>
                    <tr>
                        <th>Shortcode</th>
                        <th>Description</th>
                        <th>Live Example</th>
                        <th>Status</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $shortcodes as $shortcode ) : ?>
                    <?php
                    $tag     = $shortcode['tag'];
                    $example = $shortcode['example'];
                    $preview = smp_vp_render_shortcode_preview( $example, $profile_id );
                    ?>
                    <tr>
                        <td><code>[<?php echo esc_html( $tag ); ?>]</code></td>
                        <td><?php echo esc_html( $shortcode['description'] ); ?></td>
                        <td>
                            <code><?php echo esc_html( $example ); ?></code>
                            <div class="smp-shortcode-preview"><?php echo esc_html( $preview['summary'] ); ?></div>
                        </td>
                        <td>
                            <?php if ( shortcode_exists( $tag ) ) : ?>
                                <span class="status-ok">Registered</span>
                            <?php else : ?>
                                <span class="status-warn">Discovered only</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( implode( ', ', $shortcode['sources'] ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="smp-panel">
        <div class="smp-panel-header">Verified Profile Shortcode Values</div>
        <div class="smp-panel-body">
            <p>Select a verified profile to render profile fields and shortcode values against that profile context.</p>

            <?php if ( empty( $profiles ) ) : ?>
                <div class="smp-info-box warning">No published verified profiles were found for the configured profile post type.</div>
            <?php else : ?>
                <label for="smp-vp-shortcode-profile"><strong>Verified profile</strong></label>
                <select id="smp-vp-shortcode-profile" style="min-width:320px;">
                    <?php foreach ( $profiles as $profile ) : ?>
                        <option value="<?php echo esc_attr( $profile->ID ); ?>">
                            <?php echo esc_html( $profile->post_title . ' (#' . $profile->ID . ')' ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-secondary" id="smp-vp-refresh-shortcode-values">Refresh Values</button>

                <div id="smp-vp-shortcode-values-status" class="smp-shortcode-values-status"></div>
                <div id="smp-vp-shortcode-values"></div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .smp-shortcodes-table code,
        #smp-vp-shortcode-values code {
            white-space: nowrap;
        }
        .smp-shortcode-preview {
            margin-top: 6px;
            color: #50575e;
            font-size: 12px;
            max-width: 420px;
            overflow-wrap: anywhere;
        }
        .smp-shortcode-values-status {
            margin: 12px 0;
            color: #646970;
        }
        .smp-shortcode-value {
            max-width: 520px;
            overflow-wrap: anywhere;
        }
    </style>

    <script>
    jQuery(function($) {
        function loadProfileShortcodeValues() {
            var profileId = $('#smp-vp-shortcode-profile').val();
            if (!profileId) {
                return;
            }

            $('#smp-vp-shortcode-values-status').text('Loading shortcode values...');
            $('#smp-vp-shortcode-values').html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'smp_vp_shortcode_profile_values',
                    profile_id: profileId,
                    nonce: smpVP.nonce
                },
                success: function(response) {
                    if (!response || !response.success) {
                        $('#smp-vp-shortcode-values-status').text(response && response.data ? response.data : 'Failed to load shortcode values.');
                        return;
                    }

                    $('#smp-vp-shortcode-values-status').text(response.data.summary);
                    $('#smp-vp-shortcode-values').html(response.data.html);
                },
                error: function() {
                    $('#smp-vp-shortcode-values-status').text('AJAX error while loading shortcode values.');
                }
            });
        }

        $('#smp-vp-shortcode-profile').on('change', loadProfileShortcodeValues);
        $('#smp-vp-refresh-shortcode-values').on('click', loadProfileShortcodeValues);
        if ($('#smp-vp-shortcode-profile').length) {
            loadProfileShortcodeValues();
        }
    });
    </script>
    <?php
}

/**
 * AJAX handler for selected profile shortcode values.
 */
function ajax_shortcode_profile_values() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'smp_vp_ajax_nonce' ) ) {
        wp_send_json_error( 'Invalid security token' );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    $profile_id = isset( $_POST['profile_id'] ) ? absint( $_POST['profile_id'] ) : 0;
    $profile    = $profile_id ? get_post( $profile_id ) : null;
    $settings   = get_verified_profile_settings();

    if ( ! $profile || $profile->post_type !== $settings['slug'] ) {
        wp_send_json_error( 'Invalid verified profile selected.' );
    }

    $rows = smp_vp_build_profile_shortcode_rows( $profile_id );

    ob_start();
    ?>
    <table class="smp-table">
        <thead>
            <tr>
                <th>Field / Content</th>
                <th>Shortcode</th>
                <th>Value From Shortcode</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $rows as $row ) : ?>
                <tr>
                    <td><?php echo esc_html( $row['label'] ); ?></td>
                    <td><code><?php echo esc_html( $row['shortcode'] ); ?></code></td>
                    <td class="smp-shortcode-value"><?php echo esc_html( $row['value'] ); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    $html = ob_get_clean();

    wp_send_json_success( [
        'summary' => sprintf( 'Loaded %d rows for %s.', count( $rows ), get_the_title( $profile_id ) ),
        'html'    => $html,
    ] );
}
add_action( 'wp_ajax_smp_vp_shortcode_profile_values', __NAMESPACE__ . '\\ajax_shortcode_profile_values' );

/**
 * Discover plugin shortcodes from source and runtime providers.
 *
 * @return array<int,array<string,mixed>>
 */
function smp_vp_discover_shortcodes() {
    static $cache = null;
    if ( $cache !== null ) {
        return $cache;
    }

    $items = [];

    foreach ( smp_vp_scan_php_shortcodes() as $tag => $data ) {
        $items[ $tag ] = $data;
    }

    $providers = [
        __NAMESPACE__ . '\\get_verified_profile_shortcodes',
        __NAMESPACE__ . '\\get_muckrack_shortcodes',
    ];

    foreach ( $providers as $provider ) {
        if ( ! is_callable( $provider ) ) {
            continue;
        }

        $provided = call_user_func( $provider );
        if ( ! is_array( $provided ) ) {
            continue;
        }

        foreach ( array_keys( $provided ) as $tag ) {
            if ( ! isset( $items[ $tag ] ) ) {
                $items[ $tag ] = [ 'tag' => $tag, 'sources' => [] ];
            }
            $items[ $tag ]['sources'][] = 'provider';
        }
    }

    foreach ( array_keys( $items ) as $tag ) {
        $meta = smp_vp_shortcode_metadata( $tag );
        $items[ $tag ]['description'] = $meta['description'];
        $items[ $tag ]['example']     = $meta['example'];
        $items[ $tag ]['sources']     = array_values( array_unique( $items[ $tag ]['sources'] ) );
    }

    ksort( $items, SORT_NATURAL | SORT_FLAG_CASE );

    return $cache = array_values( $items );
}

/**
 * Scan plugin PHP files for direct add_shortcode calls.
 *
 * @return array<string,array<string,mixed>>
 */
function smp_vp_scan_php_shortcodes() {
    $items = [];
    $root  = __DIR__;
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS )
    );

    foreach ( $files as $file ) {
        if ( ! $file instanceof \SplFileInfo || $file->getExtension() !== 'php' ) {
            continue;
        }

        $path = $file->getPathname();
        $code = file_get_contents( $path );
        if ( $code === false ) {
            continue;
        }

        if ( ! preg_match_all( '/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,/i', $code, $matches ) ) {
            continue;
        }

        $relative = ltrim( str_replace( $root, '', $path ), DIRECTORY_SEPARATOR );
        foreach ( $matches[1] as $tag ) {
            if ( ! isset( $items[ $tag ] ) ) {
                $items[ $tag ] = [ 'tag' => $tag, 'sources' => [] ];
            }
            $items[ $tag ]['sources'][] = $relative;
        }
    }

    return $items;
}

/**
 * Metadata and examples for known plugin shortcodes.
 *
 * @param string $tag Shortcode tag.
 * @return array{description:string,example:string}
 */
function smp_vp_shortcode_metadata( $tag ) {
    $map = [
        'acf_author_field' => [
            'description' => 'Outputs an ACF user field for the current post author.',
            'example'     => '[acf_author_field field="profiles_muckrack"]',
        ],
        'contributor_network' => [
            'description' => 'Outputs a field from the Contributor Network options group.',
            'example'     => '[contributor_network field="program_name"]',
        ],
        'display_homepage_profiles' => [
            'description' => 'Renders recent verified profiles through the configured homepage listing template.',
            'example'     => '[display_homepage_profiles]',
        ],
        'display_post_mentions' => [
            'description' => 'Legacy shortcode for displaying profile mentions on post content.',
            'example'     => '[display_post_mentions]',
        ],
        'display_profile_contributing_articles' => [
            'description' => 'Legacy shortcode for articles contributed by the selected profile.',
            'example'     => '[display_profile_contributing_articles]',
        ],
        'display_profile_council_banner' => [
            'description' => 'Shows the leadership council banner for a profile whose linked user is a council member.',
            'example'     => '[display_profile_council_banner]',
        ],
        'display_profile_current_residence' => [
            'description' => 'Displays the current residence field for the selected profile.',
            'example'     => '[display_profile_current_residence]',
        ],
        'display_profile_education' => [
            'description' => 'Legacy shortcode for education rows on the selected profile.',
            'example'     => '[display_profile_education]',
        ],
        'display_profile_internal_features' => [
            'description' => 'Legacy shortcode for internal article features connected to the selected profile.',
            'example'     => '[display_profile_internal_features]',
        ],
        'display_profile_location_born' => [
            'description' => 'Displays the birthplace field for the selected profile.',
            'example'     => '[display_profile_location_born]',
        ],
        'display_profile_muckrack_verified' => [
            'description' => 'Displays MuckRack verification text for the selected profile when enabled.',
            'example'     => '[display_profile_muckrack_verified]',
        ],
        'display_profile_notable_mentions' => [
            'description' => 'Displays notable recognitions from the selected profile.',
            'example'     => '[display_profile_notable_mentions]',
        ],
        'display_profile_organizations_founded' => [
            'description' => 'Legacy shortcode for organizations founded by the selected profile.',
            'example'     => '[display_profile_organizations_founded]',
        ],
        'display_profile_press_releases' => [
            'description' => 'Legacy shortcode for press releases connected to the selected profile.',
            'example'     => '[display_profile_press_releases]',
        ],
        'display_profile_quick_contact' => [
            'description' => 'Displays preferred contact methods for the selected profile.',
            'example'     => '[display_profile_quick_contact]',
        ],
        'display_profile_quick_online_profiles' => [
            'description' => 'Displays online profile links such as Crunchbase, F6S, IMDb, and MuckRack.',
            'example'     => '[display_profile_quick_online_profiles]',
        ],
        'display_profiles_featured_in_single_post' => [
            'description' => 'Renders profiles featured in the current post.',
            'example'     => '[display_profiles_featured_in_single_post]',
        ],
        'display_profiles_in_articles' => [
            'description' => 'Renders verified profiles that are referenced by article profile repeaters.',
            'example'     => '[profiles_in_articles]',
        ],
        'display_single_post_mentioned_in_article' => [
            'description' => 'Renders profiles mentioned in a single post, with an optional thumbnail requirement.',
            'example'     => '[display_single_post_mentioned_in_article must_have_thumbnail="true"]',
        ],
        'display_single_profile_article_written_by' => [
            'description' => 'Renders articles written by the profile contributor through the configured loop template.',
            'example'     => '[display_single_profile_article_written_by]',
        ],
        'display_single_profile_articles_featured_in' => [
            'description' => 'Renders articles where the selected profile is featured.',
            'example'     => '[display_single_profile_articles_featured_in columns_web="4" columns_mobile="2"]',
        ],
        'display_single_profile_education' => [
            'description' => 'Displays education rows for the selected profile.',
            'example'     => '[display_single_profile_education]',
        ],
        'display_single_profile_organizations_founded' => [
            'description' => 'Displays organizations founded by the selected profile.',
            'example'     => '[display_single_profile_organizations_founded]',
        ],
        'display_single_profile_press_releases' => [
            'description' => 'Renders press releases connected to the selected profile.',
            'example'     => '[display_single_profile_press_releases]',
        ],
        'display_single_profile_text_based_social_profiles' => [
            'description' => 'Displays text links for the selected profile social/profile URLs.',
            'example'     => '[display_single_profile_text_based_social_profiles]',
        ],
        'display_single_profile_validate_schema_button' => [
            'description' => 'Outputs a Schema.org validator link for the selected profile permalink.',
            'example'     => '[display_single_profile_validate_schema_button]',
        ],
        'display_profile_validate_schema_button' => [
            'description' => 'Legacy schema validator link for the selected profile permalink.',
            'example'     => '[display_profile_validate_schema_button]',
        ],
        'display_theme_footer_text_social_links' => [
            'description' => 'Displays footer links from global verified profile options.',
            'example'     => '[display_theme_footer_text_social_links]',
        ],
        'display_website_footer_external_profiles' => [
            'description' => 'Legacy footer shortcode for external verified profile links.',
            'example'     => '[display_website_footer_external_profiles]',
        ],
        'featured_in_posts' => [
            'description' => 'Legacy featured-in posts shortcode.',
            'example'     => '[featured_in_posts]',
        ],
        'get_profile_field' => [
            'description' => 'Outputs a selected ACF field from the current verified profile.',
            'example'     => '[get_profile_field field="title"]',
        ],
        'muckrack_verified' => [
            'description' => 'Outputs MuckRack verification icon or text for the current author context.',
            'example'     => '[muckrack_verified type="text"]',
        ],
        'verified_author' => [
            'description' => 'Legacy MuckRack verified text for author archive context.',
            'example'     => '[verified_author]',
        ],
        'verified_icon_author' => [
            'description' => 'Legacy MuckRack verified icon for author archive context.',
            'example'     => '[verified_icon_author]',
        ],
        'verified_icon_single' => [
            'description' => 'Legacy MuckRack verified icon for the current single post context.',
            'example'     => '[verified_icon_single]',
        ],
        'post_mentioned_vocabulary' => [
            'description' => 'Renders mentioned entities for the current post.',
            'example'     => '[post_mentioned_vocabulary]',
        ],
        'profiles_in_articles' => [
            'description' => 'Renders profiles referenced across articles.',
            'example'     => '[profiles_in_articles]',
        ],
        'verified_profile' => [
            'description' => 'Outputs a field from the Verified Profile options group.',
            'example'     => '[verified_profile field="program_name"]',
        ],
        'verified_single' => [
            'description' => 'Legacy MuckRack verified text for the current single post context.',
            'example'     => '[verified_single]',
        ],
        'vocabulary_mentioned_posts' => [
            'description' => 'Renders posts that mention the current vocabulary/entity post.',
            'example'     => '[vocabulary_mentioned_posts]',
        ],
        'wiki_mentioned_posts' => [
            'description' => 'Renders posts that mention the current wiki/profile entity.',
            'example'     => '[wiki_mentioned_posts]',
        ],
    ];

    if ( isset( $map[ $tag ] ) ) {
        return $map[ $tag ];
    }

    return [
        'description' => ucwords( str_replace( '_', ' ', $tag ) ) . ' shortcode discovered in this plugin.',
        'example'     => '[' . $tag . ']',
    ];
}

/**
 * Get published verified profile posts.
 *
 * @return array<int,\WP_Post>
 */
function smp_vp_get_verified_profile_posts() {
    $settings = get_verified_profile_settings();

    return get_posts( [
        'post_type'      => $settings['slug'],
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );
}

/**
 * Build rows for the selected profile value table.
 *
 * @param int $profile_id Profile post ID.
 * @return array<int,array{label:string,shortcode:string,value:string}>
 */
function smp_vp_build_profile_shortcode_rows( $profile_id ) {
    $rows = [];

    foreach ( smp_vp_profile_field_examples() as $field => $label ) {
        $shortcode = '[get_profile_field field="' . $field . '"]';
        $preview   = smp_vp_render_shortcode_preview( $shortcode, $profile_id );
        $rows[]    = [
            'label'     => $label,
            'shortcode' => $shortcode,
            'value'     => $preview['summary'],
        ];
    }

    foreach ( smp_vp_discover_shortcodes() as $shortcode ) {
        if ( $shortcode['tag'] === 'get_profile_field' ) {
            continue;
        }

        $preview = smp_vp_render_shortcode_preview( $shortcode['example'], $profile_id );
        $rows[]  = [
            'label'     => $shortcode['description'],
            'shortcode' => $shortcode['example'],
            'value'     => $preview['summary'],
        ];
    }

    return $rows;
}

/**
 * Field examples for get_profile_field.
 *
 * @return array<string,string>
 */
function smp_vp_profile_field_examples() {
    $fields = [
        'title'             => 'Title',
        'featured'          => 'Featured',
        'url'               => 'Profile URL',
        'url_wikipedia'     => 'Wikipedia URL',
        'url_facebook'      => 'Facebook URL',
        'url_instagram'     => 'Instagram URL',
        'url_linkedin'      => 'LinkedIn URL',
        'url_website'       => 'Website URL',
        'url_soundcloud'    => 'SoundCloud URL',
        'url_imdb'          => 'IMDb URL',
        'url_tiktok'        => 'TikTok URL',
        'url_youtube'       => 'YouTube URL',
        'url_amazon'        => 'Amazon URL',
        'url_x'             => 'X URL',
        'url_audible'       => 'Audible URL',
        'url_github'        => 'GitHub URL',
        'url_f6s'           => 'F6S URL',
        'url_crunchbase'    => 'Crunchbase URL',
        'url_muckrack'      => 'MuckRack URL',
        'url_angellist'     => 'AngelList URL',
        'biography'         => 'Biography',
        'biography_short'   => 'Short Biography',
        'schema_markup'     => 'Schema Markup',
    ];

    foreach ( smp_vp_scan_acf_profile_field_names() as $field ) {
        if ( ! isset( $fields[ $field ] ) ) {
            $fields[ $field ] = ucwords( str_replace( '_', ' ', $field ) );
        }
    }

    return $fields;
}

/**
 * Scan likely profile ACF files for field names.
 *
 * @return array<int,string>
 */
function smp_vp_scan_acf_profile_field_names() {
    $field_names = [];
    $files       = [
        __DIR__ . '/register-acf-verified-profile.php',
        __DIR__ . '/register-acf-fields.php',
        __DIR__ . '/register-acf-structures.php',
    ];

    foreach ( $files as $file ) {
        $code = file_exists( $file ) ? file_get_contents( $file ) : '';
        if ( ! $code ) {
            continue;
        }

        if ( preg_match_all( '/[\'"]name[\'"]\s*=>\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $code, $matches ) ) {
            foreach ( $matches[1] as $name ) {
                if ( strlen( $name ) > 1 && ! in_array( $name, [ 'name', 'type', 'profile' ], true ) ) {
                    $field_names[] = $name;
                }
            }
        }
    }

    return array_values( array_unique( $field_names ) );
}

/**
 * Render a shortcode in an optional profile context and summarize its output.
 *
 * @param string $shortcode Shortcode string.
 * @param int    $profile_id Profile post ID.
 * @return array{raw:string,summary:string}
 */
function smp_vp_render_shortcode_preview( $shortcode, $profile_id = 0 ) {
    global $post;

    $previous_post = $post;
    $raw           = '';
    $buffer_level  = ob_get_level();

    try {
        if ( $profile_id ) {
            $profile_post = get_post( $profile_id );
            if ( $profile_post ) {
                $post = $profile_post;
                $GLOBALS['post'] = $profile_post;
                setup_postdata( $profile_post );
            }
        }

        ob_start();
        $raw = do_shortcode( $shortcode );
        $raw = ob_get_clean() . $raw;
    } catch ( \Throwable $e ) {
        while ( ob_get_level() > $buffer_level ) {
            ob_end_clean();
        }
        $raw = 'Error rendering shortcode: ' . $e->getMessage();
    }

    wp_reset_postdata();
    $post = $previous_post;
    $GLOBALS['post'] = $previous_post;

    return [
        'raw'     => $raw,
        'summary' => smp_vp_summarize_shortcode_output( $raw, $shortcode ),
    ];
}

/**
 * Summarize rendered shortcode output for table display.
 *
 * @param string $raw Raw output.
 * @param string $shortcode Shortcode used.
 * @return string
 */
function smp_vp_summarize_shortcode_output( $raw, $shortcode ) {
    $trimmed = trim( (string) $raw );

    if ( $trimmed === '' ) {
        return 'No output';
    }

    if ( $trimmed === $shortcode ) {
        return 'Not registered in the current runtime';
    }

    $without_style = preg_replace( '/<style\b[^>]*>.*?<\/style>/is', '', $trimmed );
    $text          = trim( html_entity_decode( wp_strip_all_tags( $without_style ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
    $text          = preg_replace( '/\s+/', ' ', $text );

    if ( $text === '' ) {
        return 'Rendered HTML/CSS only (' . strlen( $trimmed ) . ' bytes)';
    }

    if ( strlen( $text ) > 300 ) {
        return substr( $text, 0, 297 ) . '...';
    }

    return $text;
}
