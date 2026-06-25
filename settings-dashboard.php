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

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include_once __DIR__ . '/verified-profile-display-templates.php';

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
    return apply_filters( 'smp_vp_dashboard_tabs', [
        'overview'      => 'Overview',
        'system-checks' => 'System Checks',
        'plugins'       => 'Plugin Checks',
        'snippets'      => 'Snippets',
        'shortcodes'    => 'Shortcodes',
        'emails'        => 'Emails',
        'display-cards' => 'Display Cards',
    ] );
}

/**
 * Render a single dashboard tab.
 */
function smp_vp_render_dashboard_tab( $tab_id ) {
    $tab_id   = sanitize_key( (string) $tab_id );
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

        case 'display-cards':
            if ( function_exists( __NAMESPACE__ . '\smp_vp_display_render_settings' ) ) {
                smp_vp_display_render_settings();
            }
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
    $tabs   = smp_vp_dashboard_tabs();
    $tab_id = sanitize_key( (string) $tab_id );

    if ( '' === $tab_id || ! array_key_exists( $tab_id, $tabs ) ) {
        $keys   = array_keys( $tabs );
        $tab_id = isset( $keys[0] ) ? (string) $keys[0] : 'overview';
    }

    ob_start();
    smp_vp_render_dashboard_tab( $tab_id );
    $html = (string) ob_get_clean();

    return [
        'tab'   => $tab_id,
        'label' => smp_vp_tab_label( $tabs[ $tab_id ] ?? $tab_id ),
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

    $tabs   = smp_vp_dashboard_tabs();
    $active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
    
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
        if ( class_exists( '\\Hexa\\PluginCore\\WpAdminTabs\\HostTabsRenderer' ) ) {
            $renderer = new \Hexa\PluginCore\WpAdminTabs\HostTabsRenderer();
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
                    'render_callback' => __NAMESPACE__ . '\\smp_vp_render_dashboard_tab',
                ]
            );
        } else {
            smp_vp_render_dashboard_tab( $active );
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
