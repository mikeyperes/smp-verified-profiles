<?php
/**
 * SMP Verified Profiles - Main Settings Dashboard
 * 
 * Tabbed dashboard with the following structure:
 * - Overview tab with quick stats, status cards, and quick links
 * - System Checks tab
 * - Plugin Checks tab  
 * - Snippets tab with toggle switches
 * 
 * Plugin Info is displayed at the bottom of the Overview tab.
 * 
 * @package smp_verified_profiles
 * @since 6.4
 */

namespace smp_verified_profiles;

use Hexa\PluginCore\CorePackageUpdates\CorePackageStatus;
use Hexa\PluginCore\PluginUpdates\PluginUpdateStatus;
use Hexa\PluginCore\WpAdminTabs\HostTabsRenderer;
use Hexa\PluginCore\WpAdminTabs\TabDefinition;
use Hexa\PluginCore\WpAdminTabs\TabRegistry;

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include_once __DIR__ . '/verified-profile-display-templates.php';
include_once __DIR__ . '/verified-profile-page-templates.php';

/**
 * Register settings page in WordPress admin menu
 * Adds the plugin settings page under Settings menu
 */
function add_wp_admin_settings_page() {
    add_options_page(
        Config::$settings_page_name,           // Page title
        Config::$settings_page_name,           // Menu title
        Config::$settings_page_capability,     // Required capability
        Config::$settings_page_slug,           // Menu slug
        __NAMESPACE__ . '\\display_wp_admin_settings_page'  // Callback function
    );
}

/**
 * Dashboard tab definitions.
 *
 * @return array<string,string>
 */
function smp_vp_dashboard_tabs() {
    $tabs = apply_filters( 'smp_vp_dashboard_tabs', [
        'overview'      => 'Dashboard',
        'system-checks' => 'System Checks',
        'plugins'       => 'Plugin Checks',
        'snippets'      => 'Snippets',
        'shortcodes'    => 'Shortcodes',
        'emails'        => 'Email Settings',
    ] );

    // Keep the old route working without rendering the same feature twice.
    unset( $tabs['display-cards'] );

    return $tabs;
}

/**
 * Legacy dashboard routes that now resolve to a canonical tab.
 *
 * @return array<string,string>
 */
function smp_vp_dashboard_tab_aliases(): array {
    return [
        'display-cards' => 'features',
    ];
}

function smp_vp_settings_tab_url( string $tab_id ): string {
    return add_query_arg(
        [
            'page' => Config::$settings_page_slug,
            'tab'  => sanitize_key( $tab_id ),
        ],
        admin_url( 'options-general.php' )
    );
}

/**
 * Resolve a requested tab to one registered canonical route.
 *
 * @param array<string,mixed> $tabs
 */
function smp_vp_resolve_dashboard_tab( $tab_id, array $tabs ): string {
    $tab_id = sanitize_key( (string) $tab_id );
    $tab_id = smp_vp_dashboard_tab_aliases()[ $tab_id ] ?? $tab_id;

    if ( '' !== $tab_id && array_key_exists( $tab_id, $tabs ) ) {
        return $tab_id;
    }

    return array_key_exists( 'overview', $tabs ) ? 'overview' : (string) array_key_first( $tabs );
}

/**
 * Group the canonical tabs for Hexa WP Core's sidebar navigation.
 *
 * @param array<string,mixed> $tabs
 * @return array<int,array{label:string,tabs:array<int,string>}>
 */
function smp_vp_dashboard_tab_groups( array $tabs ): array {
    $groups = [
        [ 'label' => 'Overview', 'tabs' => [ 'overview' ] ],
        [ 'label' => 'Profiles', 'tabs' => [ 'features', 'profile-pages', 'pages', 'spawning-api' ] ],
        [ 'label' => 'Configuration', 'tabs' => [ 'emails' ] ],
        [ 'label' => 'System', 'tabs' => [ 'system-checks', 'plugins' ] ],
        [ 'label' => 'Developer', 'tabs' => [ 'snippets', 'shortcodes', 'hexa-core' ] ],
    ];

    foreach ( $groups as &$group ) {
        $group['tabs'] = array_values(
            array_filter(
                $group['tabs'],
                static fn ( string $id ): bool => array_key_exists( $id, $tabs )
            )
        );
    }
    unset( $group );

    $groups = array_values(
        array_filter(
            $groups,
            static fn ( array $group ): bool => [] !== $group['tabs']
        )
    );

    return apply_filters( 'smp_vp_dashboard_tab_groups', $groups, $tabs );
}

/**
 * Register every dashboard route through Hexa WP Core.
 */
function smp_vp_dashboard_tab_registry(): TabRegistry {
    $registry = new TabRegistry();

    foreach ( smp_vp_dashboard_tabs() as $id => $label ) {
        $id = sanitize_key( (string) $id );
        if ( '' === $id ) {
            continue;
        }

        $registry->add(
            new TabDefinition(
                $id,
                smp_vp_tab_label( $label ),
                static function () use ( $id ): void {
                    smp_vp_render_dashboard_tab( $id );
                },
                Config::$settings_page_capability
            )
        );
    }

    return $registry;
}

function smp_vp_render_registered_dashboard_tab( TabRegistry $registry, string $tab_id ): void {
    $definition = $registry->get( $tab_id ) ?? $registry->get( 'overview' );
    if ( ! $definition instanceof TabDefinition ) {
        return;
    }

    if (
        null !== $definition->capability
        && '' !== $definition->capability
        && ! current_user_can( $definition->capability )
    ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'You do not have permission to view this section.', 'smp-verified-profiles' ) . '</p></div>';
        return;
    }

    if ( is_callable( $definition->renderer ) ) {
        call_user_func( $definition->renderer );
    }
}

/**
 * Render a single dashboard tab.
 */
function smp_vp_render_dashboard_tab( $tab_id ) {
    $tab_id   = sanitize_key( (string) $tab_id );
    $tab_id   = smp_vp_dashboard_tab_aliases()[ $tab_id ] ?? $tab_id;
    $rendered = apply_filters( 'smp_vp_render_dashboard_tab', false, $tab_id );

    if ( $rendered ) {
        return;
    }

    switch ( $tab_id ) {
        case 'overview':
            if ( function_exists( __NAMESPACE__ . '\\display_settings_overview' ) ) {
                display_settings_overview();
            }
            if ( function_exists( __NAMESPACE__ . '\\display_plugin_info' ) ) {
                display_plugin_info();
            }
            break;

        case 'system-checks':
            if ( function_exists( __NAMESPACE__ . '\\display_settings_system_checks' ) ) {
                display_settings_system_checks();
            }
            break;

        case 'plugins':
            if ( function_exists( __NAMESPACE__ . '\\display_settings_check_plugins' ) ) {
                display_settings_check_plugins();
            }
            break;

        case 'snippets':
            if ( function_exists( __NAMESPACE__ . '\\display_settings_snippets' ) ) {
                display_settings_snippets();
            }
            break;

        case 'shortcodes':
            if ( function_exists( __NAMESPACE__ . '\\display_settings_shortcodes' ) ) {
                display_settings_shortcodes();
            }
            break;

        case 'emails':
            display_settings_emails();
            break;

        default:
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'Unknown dashboard tab.', 'smp_verified_profiles' ) . '</p></div>';
            break;
    }
}

/**
 * Build the AJAX payload expected by Hexa's host tab renderer.
 *
 * @return array<string,string>
 */
function smp_vp_tab_fragment( $tab_id ) {
    $registry = smp_vp_dashboard_tab_registry();
    $tabs     = $registry->all();
    $tab_id   = smp_vp_resolve_dashboard_tab( $tab_id, $tabs );
    $tab      = $registry->get( $tab_id );

    ob_start();
    smp_vp_render_registered_dashboard_tab( $registry, $tab_id );
    $html = (string) ob_get_clean();

    return [
        'tab'   => $tab_id,
        'label' => $tab instanceof TabDefinition ? $tab->label : $tab_id,
        'html'  => $html,
    ];
}

function smp_vp_tab_label( $tab ) {
    if ( is_array( $tab ) && isset( $tab['label'] ) ) {
        return (string) $tab['label'];
    }

    if ( is_object( $tab ) && isset( $tab->label ) ) {
        return (string) $tab->label;
    }

    return (string) $tab;
}

/**
 * Plugin and shared-Core details shown by Hexa WP Core above the sidebar.
 *
 * @return array<string,string>
 */
function smp_vp_sidebar_identity(): array {
    $identity = [
        'plugin_name'     => Config::$plugin_name,
        'current_version' => Config::$plugin_version,
        'github_version'  => 'Unknown',
        'github_url'      => 'https://github.com/' . Config::$github_repo,
        'core_name'       => 'Hexa WP Core',
        'core_version'    => defined( 'HEXA_PLUGIN_CORE_SELECTED_VERSION' ) ? (string) HEXA_PLUGIN_CORE_SELECTED_VERSION : 'Unknown',
        'core_github_url' => 'https://github.com/mikeyperes/hexa-wordpress-plugin-core',
    ];

    try {
        $plugin_config = function_exists( __NAMESPACE__ . '\\smp_vp_updater_config' ) ? smp_vp_updater_config() : null;
        if ( $plugin_config && class_exists( PluginUpdateStatus::class ) ) {
            $status = ( new PluginUpdateStatus( $plugin_config ) )->get();
            $identity['plugin_name']     = (string) ( $status['plugin_name'] ?? $identity['plugin_name'] );
            $identity['current_version'] = (string) ( $status['current_version'] ?? $identity['current_version'] );
            $identity['github_version']  = (string) ( $status['latest_version'] ?? $identity['github_version'] );
            $identity['github_url']      = (string) ( $status['github_url'] ?? $identity['github_url'] );
        }

        $core_config = function_exists( __NAMESPACE__ . '\\smp_vp_core_package_config' ) ? smp_vp_core_package_config() : null;
        if ( $core_config && class_exists( CorePackageStatus::class ) ) {
            $status = ( new CorePackageStatus( $core_config ) )->get();
            $identity['core_version']    = (string) ( $status['current_version'] ?? $identity['core_version'] );
            $identity['core_github_url'] = (string) ( $status['github_url'] ?? $identity['core_github_url'] );
        }
    } catch ( \Throwable $throwable ) {
        // Navigation must remain available when a remote version check fails.
    }

    return $identity;
}

/**
 * Display the main settings page with tabs
 */
function display_wp_admin_settings_page() {
    // Start output buffering to prevent header issues
    if ( ob_get_level() == 0 ) {
        ob_start();
    }
    if ( ! current_user_can( Config::$settings_page_capability ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'smp_verified_profiles' ) );
    }

    $registry  = smp_vp_dashboard_tab_registry();
    $tabs      = $registry->all();
    $requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
    $active    = smp_vp_resolve_dashboard_tab( $requested, $tabs );
    
    // Output dashboard styles from components file
    if ( function_exists( __NAMESPACE__ . '\\output_dashboard_styles' ) ) {
        output_dashboard_styles();
    }
    
    ?>
    <div class="wrap" id="smp-dashboard">
        <h1><?php echo esc_html( Config::$settings_page_display_title ); ?></h1>
        
        <script>
        window.smpVP = window.smpVP || {};
        smpVP.nonce = '<?php echo esc_js( function_exists( __NAMESPACE__ . '\\smp_vp_ajax_nonce' ) ? smp_vp_ajax_nonce() : wp_create_nonce( Config::$ajax_nonce_action ) ); ?>';
        smpVP.nonceField = '<?php echo esc_js( Config::$ajax_nonce_field ); ?>';
        smpVP.toggleSnippet = function(snippetId) {
            var $ = jQuery;
            var isChecked = $('#toggle-' + snippetId).prop('checked');
            var $item = $('[data-snippet-id="' + snippetId + '"]');

            $item.css('opacity', '0.5');

            $.ajax({
                url: window.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'smp_vp_toggle_snippet',
                    snippet_id: snippetId,
                    enable: isChecked ? 1 : 0,
                    nonce: smpVP.nonce
                },
                success: function(response) {
                    $item.css('opacity', '1');

                    if (response && response.success) {
                        $item.css('border-left', isChecked ? '3px solid #00a32a' : '3px solid #d63638');
                        setTimeout(function() {
                            $item.css('border-left', '');
                        }, 1500);
                    } else {
                        alert('Error: ' + ((response && response.data && response.data.message) || (response && response.data) || 'Failed to save setting'));
                        $('#toggle-' + snippetId).prop('checked', !isChecked);
                    }
                },
                error: function() {
                    $item.css('opacity', '1');
                    alert('AJAX error occurred. Please try again.');
                    $('#toggle-' + snippetId).prop('checked', !isChecked);
                }
            });
        };
        </script>

        <?php
        if ( class_exists( HostTabsRenderer::class ) ) {
            $renderer = new HostTabsRenderer();
            $renderer->render(
                [
                    'tabs'            => $tabs,
                    'active'          => $active,
                    'page_url'        => menu_page_url( Config::$settings_page_slug, false ),
                    'ajax_action'     => 'smp_vp_load_tab',
                    'nonce'           => function_exists( __NAMESPACE__ . '\\smp_vp_ajax_nonce' ) ? smp_vp_ajax_nonce() : wp_create_nonce( Config::$ajax_nonce_action ),
                    'nonce_field'     => Config::$ajax_nonce_field,
                    'root_id'         => 'smp-vp-host-tabs',
                    'panel_id'        => 'smp-vp-host-tab-panel',
                    'label'           => Config::$settings_page_display_title,
                    'layout'          => 'sidebar',
                    'groups'          => smp_vp_dashboard_tab_groups( $tabs ),
                    'sidebar_identity' => smp_vp_sidebar_identity(),
                    'sidebar_collapsible' => true,
                    'sidebar_collapsed'   => false,
                    'sidebar_persist'     => true,
                    'render_callback' => static function ( string $tab_id ) use ( $registry ): void {
                        smp_vp_render_registered_dashboard_tab( $registry, $tab_id );
                    },
                ]
            );
        } else {
            smp_vp_render_registered_dashboard_tab( $registry, $active );
        }
        ?>
    </div>
    
    <?php
    // End output buffering
    if ( ob_get_level() != 0 ) {
        echo ob_get_clean();
    }
}

function display_settings_emails() {
    echo '<div class="smp-card"><h2>' . esc_html__( 'Email Options', 'smp_verified_profiles' ) . '</h2>';

    if ( ! function_exists( 'acf_form' ) ) {
        echo '<p>' . esc_html__( 'ACF Pro is required to edit email options.', 'smp_verified_profiles' ) . '</p></div>';
        return;
    }

    acf_form(
        [
            'post_id'         => 'options',
            'field_groups'    => [ 'group_658739a0ab536' ],
            'form'            => true,
            'submit_value'    => __( 'Save Email Options', 'smp_verified_profiles' ),
            'updated_message' => __( 'Email options saved.', 'smp_verified_profiles' ),
        ]
    );

    echo '</div>';
}
