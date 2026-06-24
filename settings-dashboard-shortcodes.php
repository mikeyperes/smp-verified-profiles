<?php
/**
 * SMP Verified Profiles - Shortcodes dashboard tab.
 *
 * @package smp_verified_profiles
 * @since 6.4
 */

namespace smp_verified_profiles;

use Hexa\PluginCore\ShortcodeRegistry\ShortcodeDefinition;
use Hexa\PluginCore\ShortcodeRegistry\ShortcodeDisplayRenderer;
use Hexa\PluginCore\ShortcodeRegistry\ShortcodeRegistry;
use Hexa\PluginCore\WpAdminComponents\CoreUi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display the Shortcodes tab.
 */
function display_settings_shortcodes() {
    $shortcodes = smp_vp_discover_shortcodes();
    $groups     = smp_vp_group_shortcode_catalog( $shortcodes );
    $profiles   = smp_vp_get_verified_profile_posts();
    $summary    = smp_vp_shortcode_catalog_summary( $shortcodes );

    if ( ! class_exists( ShortcodeDisplayRenderer::class ) || ! class_exists( ShortcodeRegistry::class ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Hexa Core ShortcodeRegistry is not available.', 'smp-verified-profiles' ) . '</p></div>';
        return;
    }

    $registry = smp_vp_shortcode_registry( $shortcodes );

    CoreUi::render_assets();
    $renderer = new ShortcodeDisplayRenderer();
    ?>
    <div class="hpc-ui smp-vp-shortcodes">
        <?php smp_vp_render_shortcode_dashboard_styles(); ?>

        <section class="hpc-card smp-vp-shortcodes-head">
            <div>
                <h2><?php esc_html_e( 'Verified Profiles Shortcodes', 'smp-verified-profiles' ); ?></h2>
                <p><?php esc_html_e( 'Registry-backed catalog built from direct shortcode registrations, provider arrays, legacy snippets, and entity shortcode files. Runtime status reflects the current WordPress request.', 'smp-verified-profiles' ); ?></p>
            </div>
            <div class="smp-vp-shortcodes-pills">
                <?php echo CoreUi::pill( (string) $summary['total'] . ' discovered', 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo CoreUi::pill( (string) count( $registry->all() ) . ' in registry', 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo CoreUi::pill( (string) $summary['registered'] . ' registered', 'success' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo CoreUi::pill( (string) $summary['missing'] . ' missing', $summary['missing'] > 0 ? 'danger' : 'success' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </section>

        <?php foreach ( smp_vp_shortcode_groups() as $group_id => $group ) : ?>
            <?php
            $items = $groups[ $group_id ] ?? [];
            if ( empty( $items ) ) {
                continue;
            }
            ?>
            <details class="hpc-section smp-vp-shortcode-group" open>
                <summary>
                    <span><?php echo esc_html( $group['label'] ); ?></span>
                    <?php echo CoreUi::pill( (string) count( $items ) . ' shortcodes', 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </summary>
                <div class="hpc-section-body">
                    <?php if ( '' !== $group['description'] ) : ?>
                        <p class="smp-vp-shortcode-group-description"><?php echo esc_html( $group['description'] ); ?></p>
                    <?php endif; ?>
                    <?php
                    echo $renderer->render(
                        smp_vp_shortcode_render_items( $items ),
                        [
                            'title'       => '',
                            'description' => '',
                        ]
                    ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
            </details>
        <?php endforeach; ?>

        <details class="hpc-section smp-vp-shortcode-profile-values" open>
            <summary>
                <span><?php esc_html_e( 'Verified Profile Field Values', 'smp-verified-profiles' ); ?></span>
                <?php echo CoreUi::pill( (string) count( $profiles ) . ' profiles', 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </summary>
            <div class="hpc-section-body">
                <p><?php esc_html_e( 'Select a verified profile to render profile field examples and shortcode values against that profile context.', 'smp-verified-profiles' ); ?></p>

                <?php if ( empty( $profiles ) ) : ?>
                    <div class="notice notice-warning inline"><p><?php esc_html_e( 'No published verified profiles were found for the configured profile post type.', 'smp-verified-profiles' ); ?></p></div>
                <?php else : ?>
                    <div class="smp-vp-shortcode-profile-controls">
                        <label for="smp-vp-shortcode-profile"><strong><?php esc_html_e( 'Verified profile', 'smp-verified-profiles' ); ?></strong></label>
                        <select id="smp-vp-shortcode-profile">
                            <?php foreach ( $profiles as $profile ) : ?>
                                <option value="<?php echo esc_attr( $profile->ID ); ?>">
                                    <?php echo esc_html( $profile->post_title . ' (#' . $profile->ID . ')' ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="hpc-button secondary" id="smp-vp-refresh-shortcode-values"><?php esc_html_e( 'Refresh Values', 'smp-verified-profiles' ); ?></button>
                    </div>

                    <div id="smp-vp-shortcode-values-status" class="smp-vp-shortcode-values-status" aria-live="polite"></div>
                    <div id="smp-vp-shortcode-values"></div>
                <?php endif; ?>
            </div>
        </details>
    </div>

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
                    nonce: window.smpVP ? smpVP.nonce : ''
                },
                success: function(response) {
                    if (!response || !response.success) {
                        var message = response && response.data && response.data.message ? response.data.message : response && response.data ? response.data : 'Failed to load shortcode values.';
                        $('#smp-vp-shortcode-values-status').text(message);
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
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], Config::$ajax_nonce_action ) ) {
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
        smp_vp_merge_shortcode_catalog_item( $items, $tag, $data );
    }

    $providers = [
        __NAMESPACE__ . '\\get_verified_profile_shortcodes' => 'Verified profile provider',
        __NAMESPACE__ . '\\get_muckrack_shortcodes'         => 'MuckRack provider',
    ];

    foreach ( $providers as $provider => $provider_label ) {
        if ( ! is_callable( $provider ) ) {
            continue;
        }

        $provided = call_user_func( $provider );
        if ( ! is_array( $provided ) ) {
            continue;
        }

        foreach ( $provided as $tag => $callback ) {
            smp_vp_merge_shortcode_catalog_item(
                $items,
                (string) $tag,
                [
                    'callback' => is_string( $callback ) ? $callback : '',
                    'provider' => $provider_label,
                    'sources'  => [ 'provider:' . basename( str_replace( '\\', '/', $provider ) ) ],
                ]
            );
        }
    }

    foreach ( array_keys( smp_vp_shortcode_metadata_map() ) as $tag ) {
        smp_vp_merge_shortcode_catalog_item( $items, (string) $tag, [ 'sources' => [ 'metadata' ] ] );
    }

    foreach ( array_keys( $items ) as $tag ) {
        $meta   = smp_vp_shortcode_metadata( $tag );
        $source = array_values( array_unique( $items[ $tag ]['sources'] ?? [] ) );

        $items[ $tag ] = array_merge(
            [
                'tag'             => $tag,
                'callback'        => '',
                'provider'        => '',
                'sources'         => [],
                'label'           => smp_vp_shortcode_label( $tag ),
                'description'     => '',
                'example'         => '[' . $tag . ']',
                'examples'        => [],
                'parameters'      => [],
                'group'           => smp_vp_infer_shortcode_group( $tag, $source ),
                'status'          => 'missing',
                'status_label'    => 'Missing callback',
                'registered'      => false,
                'callback_exists' => false,
            ],
            $items[ $tag ],
            $meta
        );

        $items[ $tag ]['sources']         = $source;
        $items[ $tag ]['source']          = implode( ', ', $source );
        $items[ $tag ]['registered']      = shortcode_exists( $tag );
        $items[ $tag ]['callback_exists'] = ! empty( $items[ $tag ]['callback'] ) && is_callable( $items[ $tag ]['callback'] );

        if ( $items[ $tag ]['registered'] ) {
            $items[ $tag ]['status']       = 'registered';
            $items[ $tag ]['status_label'] = 'Registered';
        } elseif ( $items[ $tag ]['callback_exists'] ) {
            $items[ $tag ]['status']       = 'callable';
            $items[ $tag ]['status_label'] = 'Callable, not registered';
        } else {
            $items[ $tag ]['status']       = 'missing';
            $items[ $tag ]['status_label'] = 'Missing callback';
        }

        if ( empty( $items[ $tag ]['examples'] ) ) {
            $items[ $tag ]['examples'] = [
                [
                    'label'       => 'Primary',
                    'shortcode'   => (string) $items[ $tag ]['example'],
                    'description' => '',
                    'parameters'  => $items[ $tag ]['parameters'],
                ],
            ];
        }
    }

    ksort( $items, SORT_NATURAL | SORT_FLAG_CASE );

    return $cache = array_values( $items );
}

/**
 * Build a Hexa Core registry for all discovered Verified Profiles shortcodes.
 *
 * @param array<int,array<string,mixed>>|null $shortcodes Discovered shortcode catalog.
 */
function smp_vp_shortcode_registry( ?array $shortcodes = null ): ShortcodeRegistry {
    $registry   = new ShortcodeRegistry();
    $shortcodes = $shortcodes ?? smp_vp_discover_shortcodes();

    foreach ( $shortcodes as $shortcode ) {
        $tag = isset( $shortcode['tag'] ) ? (string) $shortcode['tag'] : '';
        if ( '' === $tag ) {
            continue;
        }

        $registry->add(
            new ShortcodeDefinition(
                $tag,
                isset( $shortcode['label'] ) ? (string) $shortcode['label'] : smp_vp_shortcode_label( $tag ),
                isset( $shortcode['example'] ) ? (string) $shortcode['example'] : '[' . $tag . ']',
                isset( $shortcode['description'] ) ? (string) $shortcode['description'] : '',
                isset( $shortcode['status_label'] ) ? (string) $shortcode['status_label'] : ''
            )
        );
    }

    return $registry;
}

/**
 * Merge one discovered shortcode source into the catalog without losing earlier source details.
 *
 * @param array<string,array<string,mixed>> $items Catalog by tag.
 * @param string                           $tag Shortcode tag.
 * @param array<string,mixed>              $data Source data.
 */
function smp_vp_merge_shortcode_catalog_item( array &$items, string $tag, array $data ): void {
    if ( '' === $tag ) {
        return;
    }

    if ( ! isset( $items[ $tag ] ) ) {
        $items[ $tag ] = [
            'tag'      => $tag,
            'sources'  => [],
            'callback' => '',
            'provider' => '',
        ];
    }

    if ( ! empty( $data['callback'] ) && is_string( $data['callback'] ) ) {
        $items[ $tag ]['callback'] = $data['callback'];
    }

    if ( ! empty( $data['provider'] ) && is_string( $data['provider'] ) ) {
        $items[ $tag ]['provider'] = $data['provider'];
    }

    foreach ( (array) ( $data['sources'] ?? [] ) as $source ) {
        if ( is_scalar( $source ) && '' !== (string) $source ) {
            $items[ $tag ]['sources'][] = (string) $source;
        }
    }
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
        if ( str_contains( $path, DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'hexa-wordpress-plugin-core' . DIRECTORY_SEPARATOR ) ) {
            continue;
        }

        $code = file_get_contents( $path );
        if ( $code === false ) {
            continue;
        }

        if ( ! preg_match_all( '/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^)]+)\)/i', $code, $matches, PREG_SET_ORDER ) ) {
            continue;
        }

        $relative = ltrim( str_replace( $root, '', $path ), DIRECTORY_SEPARATOR );
        foreach ( $matches as $match ) {
            $tag = $match[1];
            if ( ! isset( $items[ $tag ] ) ) {
                $items[ $tag ] = [ 'tag' => $tag, 'sources' => [], 'callback' => '' ];
            }
            $items[ $tag ]['sources'][] = $relative;

            $callback = smp_vp_parse_shortcode_callback_expression( $match[2] );
            if ( $callback !== '' ) {
                $items[ $tag ]['callback'] = $callback;
            }
        }
    }

    return $items;
}

/**
 * Parse common shortcode callback expressions from source.
 *
 * @param string $expression Callback expression.
 * @return string
 */
function smp_vp_parse_shortcode_callback_expression( $expression ) {
    if ( preg_match( '/__NAMESPACE__\s*\.\s*[\'"]\\\\*([a-zA-Z0-9_]+)[\'"]/', $expression, $matches ) ) {
        return __NAMESPACE__ . '\\' . $matches[1];
    }

    if ( preg_match( '/[\'"]([a-zA-Z0-9_\\\\]+)[\'"]/', $expression, $matches ) ) {
        return $matches[1];
    }

    return '';
}

/**
 * Known shortcode tags from legacy docs and provider maps.
 *
 * @return array<string,bool>
 */
function smp_vp_shortcode_metadata_map(): array {
    return array_fill_keys(
        [
            'acf_author_field',
            'contributor_network',
            'display_homepage_profiles',
            'display_post_mentions',
            'display_profile_contributing_articles',
            'display_profile_council_banner',
            'display_profile_current_residence',
            'display_profile_education',
            'display_profile_internal_features',
            'display_profile_location_born',
            'display_profile_muckrack_verified',
            'display_profile_notable_mentions',
            'display_profile_organizations_founded',
            'display_profile_press_releases',
            'display_profile_quick_contact',
            'display_profile_quick_online_profiles',
            'display_profile_validate_schema_button',
            'display_profiles_featured_in_single_post',
            'display_single_post_mentioned_in_article',
            'display_single_profile_article_written_by',
            'display_single_profile_articles_featured_in',
            'display_single_profile_education',
            'display_single_profile_organizations_founded',
            'display_single_profile_press_releases',
            'display_single_profile_text_based_social_profiles',
            'display_single_profile_validate_schema_button',
            'display_theme_footer_text_social_links',
            'display_website_footer_external_profiles',
            'featured_in_posts',
            'get_profile_field',
            'muckrack_verified',
            'post_mentioned_vocabulary',
            'profiles_in_articles',
            'verified_author',
            'verified_icon_author',
            'verified_icon_single',
            'verified_profile',
            'verified_single',
            'vocabulary_mentioned_posts',
            'wiki_mentioned_posts',
            'woocommerce_account_dashboard',
        ],
        true
    );
}

function smp_vp_shortcode_label( string $tag ): string {
    $tag = preg_replace( '/^(display_|shortcode_)/', '', $tag );
    $tag = str_replace( [ 'acf', 'wp', '_' ], [ 'ACF', 'WP', ' ' ], (string) $tag );

    return ucwords( trim( preg_replace( '/\s+/', ' ', $tag ) ) );
}

/**
 * @param array<int,string> $sources
 */
function smp_vp_infer_shortcode_group( string $tag, array $sources = [] ): string {
    $source_text = implode( ' ', $sources );

    if ( str_contains( $source_text, 'snippet-woocommerce' ) || str_starts_with( $tag, 'woocommerce_' ) ) {
        return 'woocommerce';
    }

    if ( in_array( $tag, [ 'acf_author_field', 'muckrack_verified', 'verified_author', 'verified_icon_author', 'verified_icon_single', 'verified_single', 'display_profile_muckrack_verified' ], true ) ) {
        return 'author_muckrack';
    }

    if ( in_array( $tag, [ 'contributor_network', 'verified_profile', 'display_theme_footer_text_social_links', 'display_website_footer_external_profiles' ], true ) ) {
        return 'global_options';
    }

    if ( in_array( $tag, [ 'post_mentioned_vocabulary', 'profiles_in_articles', 'vocabulary_mentioned_posts', 'wiki_mentioned_posts', 'display_profiles_featured_in_single_post', 'display_single_post_mentioned_in_article' ], true ) ) {
        return 'article_entities';
    }

    if ( in_array( $tag, [ 'display_post_mentions', 'display_profile_internal_features' ], true ) ) {
        return 'legacy';
    }

    if ( 'get_profile_field' === $tag || str_starts_with( $tag, 'display_profile_' ) || str_starts_with( $tag, 'display_single_profile_' ) || 'display_homepage_profiles' === $tag || 'featured_in_posts' === $tag ) {
        return 'profile_display';
    }

    return 'legacy';
}

/**
 * @return array<string,array{label:string,description:string}>
 */
function smp_vp_shortcode_groups(): array {
    return [
        'profile_display' => [
            'label'       => 'Profile Display',
            'description' => 'Shortcodes that render fields, profile sections, profile loops, and single-profile template fragments.',
        ],
        'article_entities' => [
            'label'       => 'Article And Entity Relationships',
            'description' => 'Shortcodes that render profiles/entities mentioned by posts, article relationships, and entity reverse lookups.',
        ],
        'author_muckrack' => [
            'label'       => 'Author And MuckRack Verification',
            'description' => 'Author-context and MuckRack verification shortcodes. Most resolve from the current post author or author archive context.',
        ],
        'global_options' => [
            'label'       => 'Global Options',
            'description' => 'Shortcodes for Verified Profile and Contributor Network option groups, page IDs, template IDs, and site-wide links.',
        ],
        'woocommerce' => [
            'label'       => 'WooCommerce',
            'description' => 'Commerce-related shortcodes registered by the optional WooCommerce snippet.',
        ],
        'legacy' => [
            'label'       => 'Legacy Or Missing',
            'description' => 'Legacy tags discovered in older snippet files or metadata. Missing callbacks are shown explicitly instead of hidden.',
        ],
    ];
}

/**
 * @param array<int,array<string,mixed>> $shortcodes
 * @return array<string,array<int,array<string,mixed>>>
 */
function smp_vp_group_shortcode_catalog( array $shortcodes ): array {
    $groups = [];

    foreach ( $shortcodes as $shortcode ) {
        $group = isset( $shortcode['group'] ) ? (string) $shortcode['group'] : 'legacy';
        $groups[ $group ][] = $shortcode;
    }

    foreach ( $groups as &$items ) {
        usort(
            $items,
            static fn( array $a, array $b ): int => strnatcasecmp( (string) ( $a['label'] ?? $a['tag'] ?? '' ), (string) ( $b['label'] ?? $b['tag'] ?? '' ) )
        );
    }

    return $groups;
}

/**
 * @param array<int,array<string,mixed>> $shortcodes
 * @return array{total:int,registered:int,missing:int,callable:int}
 */
function smp_vp_shortcode_catalog_summary( array $shortcodes ): array {
    $registered = 0;
    $callable   = 0;
    $missing    = 0;

    foreach ( $shortcodes as $shortcode ) {
        if ( ! empty( $shortcode['registered'] ) ) {
            $registered++;
        } elseif ( ! empty( $shortcode['callback_exists'] ) ) {
            $callable++;
        } else {
            $missing++;
        }
    }

    return [
        'total'      => count( $shortcodes ),
        'registered' => $registered,
        'callable'   => $callable,
        'missing'    => $missing,
    ];
}

/**
 * Convert catalog rows to Hexa Core ShortcodeDisplayRenderer items.
 *
 * @param array<int,array<string,mixed>> $items
 * @return array<int,array<string,mixed>>
 */
function smp_vp_shortcode_render_items( array $items ): array {
    $render_items = [];

    foreach ( $items as $item ) {
        $tag      = (string) ( $item['tag'] ?? '' );
        $example  = (string) ( $item['example'] ?? '[' . $tag . ']' );
        $status   = (string) ( $item['status_label'] ?? '' );
        $callback = (string) ( $item['callback'] ?? '' );

        $render_items[] = [
            'tag'         => $tag,
            'label'       => (string) ( $item['label'] ?? smp_vp_shortcode_label( $tag ) ),
            'shortcode'   => $example,
            'description' => (string) ( $item['description'] ?? '' ),
            'provider'    => (string) ( $item['provider'] ?? '' ),
            'source'      => (string) ( $item['source'] ?? '' ),
            'test_method' => '' !== $callback ? $status . ' / ' . $callback : $status,
            'examples'    => isset( $item['examples'] ) && is_array( $item['examples'] ) ? $item['examples'] : [ $example ],
            'parameters'  => isset( $item['parameters'] ) && is_array( $item['parameters'] ) ? $item['parameters'] : [],
            'evaluate'    => false,
            'output_html' => smp_vp_shortcode_status_html( $item ),
        ];
    }

    return $render_items;
}

/**
 * @param array<string,mixed> $item
 */
function smp_vp_shortcode_status_html( array $item ): string {
    $status = (string) ( $item['status'] ?? 'missing' );
    $tone   = match ( $status ) {
        'registered' => 'success',
        'callable'   => 'warning',
        default      => 'danger',
    };

    $html = '<div class="smp-vp-shortcode-status">';
    $html .= CoreUi::pill( (string) ( $item['status_label'] ?? 'Missing callback' ), $tone );

    if ( ! empty( $item['callback_exists'] ) && empty( $item['registered'] ) ) {
        $html .= '<p>Callback exists, but WordPress has not registered the shortcode in this request.</p>';
    } elseif ( empty( $item['callback_exists'] ) && empty( $item['registered'] ) ) {
        $html .= '<p>Discovered from source or metadata, but no callable callback is loaded.</p>';
    } else {
        $html .= '<p>Registered in the current WordPress runtime.</p>';
    }

    $html .= '</div>';

    return $html;
}

function smp_vp_render_shortcode_dashboard_styles(): void {
    static $rendered = false;

    if ( $rendered ) {
        return;
    }

    $rendered = true;
    ?>
    <style>
        .smp-vp-shortcodes{display:grid;gap:14px}
        .smp-vp-shortcodes-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between}
        .smp-vp-shortcodes-head h2{font-size:20px;margin:0 0 7px}
        .smp-vp-shortcodes-head p{color:#4b5563;line-height:1.55;margin:0;max-width:780px}
        .smp-vp-shortcodes-pills{align-items:center;display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
        .smp-vp-shortcode-group-description{color:#4b5563;line-height:1.55;margin:0 0 14px}
        .smp-vp-shortcode-group .hpc-shortcode-display-head{display:none}
        .smp-vp-shortcode-group .hpc-shortcode-list{gap:10px}
        .smp-vp-shortcode-group .hpc-shortcode-row{grid-template-columns:minmax(260px,1.1fr) minmax(260px,.9fr)}
        .smp-vp-shortcode-status{display:grid;gap:8px}
        .smp-vp-shortcode-status p{color:#4b5563;margin:0}
        .smp-vp-shortcode-profile-controls{align-items:center;display:flex;flex-wrap:wrap;gap:10px;margin:12px 0}
        .smp-vp-shortcode-profile-controls select{min-width:320px}
        .smp-vp-shortcode-values-status{color:#64748b;margin:12px 0}
        #smp-vp-shortcode-values code{white-space:nowrap}
        #smp-vp-shortcode-values .smp-shortcode-value{max-width:560px;overflow-wrap:anywhere}
        @media(max-width:900px){.smp-vp-shortcodes-head{display:grid}.smp-vp-shortcodes-pills{justify-content:flex-start}.smp-vp-shortcode-profile-controls select{min-width:0;width:100%}}
    </style>
    <?php
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
    $tag           = smp_vp_extract_shortcode_tag( $shortcode );
    $temp_added    = false;

    try {
        if ( $tag && ! shortcode_exists( $tag ) ) {
            $callback = smp_vp_get_discovered_shortcode_callback( $tag );
            if ( $callback && is_callable( $callback ) ) {
                add_shortcode( $tag, $callback );
                $temp_added = true;
            }
        }

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

    if ( $temp_added ) {
        remove_shortcode( $tag );
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
 * Extract the shortcode tag from a shortcode string.
 *
 * @param string $shortcode Shortcode string.
 * @return string
 */
function smp_vp_extract_shortcode_tag( $shortcode ) {
    if ( preg_match( '/^\s*\[([a-zA-Z0-9_-]+)/', $shortcode, $matches ) ) {
        return $matches[1];
    }

    return '';
}

/**
 * Get a discovered shortcode callback.
 *
 * @param string $tag Shortcode tag.
 * @return string
 */
function smp_vp_get_discovered_shortcode_callback( $tag ) {
    foreach ( smp_vp_discover_shortcodes() as $shortcode ) {
        if ( $shortcode['tag'] === $tag ) {
            return isset( $shortcode['callback'] ) ? (string) $shortcode['callback'] : '';
        }
    }

    return '';
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
