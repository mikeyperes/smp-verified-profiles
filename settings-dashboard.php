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
 * @since 6.3
 */

namespace smp_verified_profiles;

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
add_action( 'admin_menu', __NAMESPACE__ . '\\add_wp_admin_settings_page' );

/**
 * Display the main settings page with tabs
 */
function display_wp_admin_settings_page() {
    // Start output buffering to prevent header issues
    if ( ob_get_level() == 0 ) {
        ob_start();
    }
    
    // Define available tabs
    $tabs = [
        'overview'      => '📊 Overview',
        'system-checks' => '🔍 System Checks',
        'plugins'       => '🔌 Plugin Checks',
        'snippets'      => '✂️ Snippets',
    ];
    
    // Output dashboard styles from components file
    if ( function_exists( __NAMESPACE__ . '\\output_dashboard_styles' ) ) {
        output_dashboard_styles();
    }
    
    ?>
    <div class="wrap" id="smp-dashboard">
        <h1><?php echo esc_html( Config::$settings_page_display_title ); ?></h1>
        
        <!-- Tab Navigation -->
        <div class="smp-tabs-nav">
            <?php
            $first = true;
            foreach ( $tabs as $tab_id => $tab_label ) :
                $active = $first ? ' active' : '';
            ?>
                <button type="button" class="smp-tab-btn<?php echo $active; ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>">
                    <?php echo esc_html( $tab_label ); ?>
                </button>
            <?php
                $first = false;
            endforeach;
            ?>
        </div>
        
        <!-- Tab Contents -->
        <?php
        $first = true;
        foreach ( $tabs as $tab_id => $tab_label ) :
            $active = $first ? ' active' : '';
        ?>
            <div id="tab-<?php echo esc_attr( $tab_id ); ?>" class="smp-tab-content<?php echo $active; ?>">
                <?php
                switch ( $tab_id ) {
                    case 'overview':
                        // Display overview content
                        if ( function_exists( __NAMESPACE__ . '\\display_settings_overview' ) ) {
                            display_settings_overview();
                        }
                        // Plugin info at bottom of overview
                        if ( function_exists( __NAMESPACE__ . '\\display_plugin_info' ) ) {
                            display_plugin_info();
                        }
                        break;
                        
                    case 'system-checks':
                        // Display system checks
                        if ( function_exists( __NAMESPACE__ . '\\display_settings_system_checks' ) ) {
                            display_settings_system_checks();
                        }
                        break;
                        
                    case 'plugins':
                        // Display plugin dependency checks
                        if ( function_exists( __NAMESPACE__ . '\\display_settings_check_plugins' ) ) {
                            display_settings_check_plugins();
                        }
                        break;
                        
                    case 'snippets':
                        // Display snippets with toggles
                        if ( function_exists( __NAMESPACE__ . '\\display_settings_snippets' ) ) {
                            display_settings_snippets();
                        }
                        break;
                }
                ?>
            </div>
        <?php
            $first = false;
        endforeach;
        ?>
    </div>
    
    <!-- Tab switching JavaScript (no page refresh) -->
    <script>
    // Create namespace for this plugin's JS
    var smpVP = smpVP || {};
    
    // Store nonce for AJAX calls
    smpVP.nonce = '<?php echo wp_create_nonce( 'smp_vp_ajax_nonce' ); ?>';
    
    jQuery(document).ready(function($) {
        // Tab switching functionality
        $('.smp-tab-btn').on('click', function() {
            var tabId = $(this).data('tab');
            
            // Update active states
            $('.smp-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Show selected tab content
            $('.smp-tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
            
            // Store active tab in sessionStorage (persists during session)
            sessionStorage.setItem('smpActiveTab', tabId);
        });
        
        // Restore last active tab from sessionStorage
        var savedTab = sessionStorage.getItem('smpActiveTab');
        if (savedTab && $('.smp-tab-btn[data-tab="' + savedTab + '"]').length) {
            $('.smp-tab-btn[data-tab="' + savedTab + '"]').click();
        }
    });
    
    /**
     * Toggle snippet enabled/disabled state via AJAX
     * Called when user clicks a snippet toggle switch
     * 
     * @param {string} snippetId The option name/ID of the snippet
     */
    smpVP.toggleSnippet = function(snippetId) {
        var $ = jQuery;
        var isChecked = $('#toggle-' + snippetId).prop('checked');
        var $item = $('[data-snippet-id="' + snippetId + '"]');
        
        // Visual feedback - dim the item while saving
        $item.css('opacity', '0.5');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'smp_vp_toggle_snippet',
                snippet_id: snippetId,
                enable: isChecked ? 1 : 0,
                nonce: smpVP.nonce
            },
            success: function(response) {
                $item.css('opacity', '1');
                
                if (response.success) {
                    // Show success feedback with green border flash
                    $item.css('border-left', isChecked ? '3px solid #00a32a' : '3px solid #d63638');
                    setTimeout(function() {
                        $item.css('border-left', '');
                    }, 1500);
                } else {
                    // Show error and revert toggle
                    alert('Error: ' + (response.data || 'Failed to save setting'));
                    $('#toggle-' + snippetId).prop('checked', !isChecked);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $item.css('opacity', '1');
                console.error('AJAX Error:', textStatus, errorThrown);
                alert('AJAX error occurred. Please try again.');
                // Revert toggle on error
                $('#toggle-' + snippetId).prop('checked', !isChecked);
            }
        });
    };
    </script>
    
    <?php
    // End output buffering
    if ( ob_get_level() != 0 ) {
        echo ob_get_clean();
    }
}

/**
 * AJAX handler for toggling snippet settings
 * Saves the snippet enabled/disabled state to WordPress options
 */
function ajax_toggle_snippet() {
    // Verify nonce for security
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'smp_vp_ajax_nonce' ) ) {
        wp_send_json_error( 'Invalid security token' );
        return;
    }
    
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
        return;
    }
    
    // Get and sanitize parameters
    $snippet_id = isset( $_POST['snippet_id'] ) ? sanitize_text_field( $_POST['snippet_id'] ) : '';
    $enable     = isset( $_POST['enable'] ) ? (bool) $_POST['enable'] : false;
    
    if ( empty( $snippet_id ) ) {
        wp_send_json_error( 'Missing snippet ID' );
        return;
    }
    
    // Update the option
    $result = update_option( $snippet_id, $enable );
    
    if ( $result || get_option( $snippet_id ) === $enable ) {
        wp_send_json_success([
            'snippet_id' => $snippet_id,
            'enabled'    => $enable,
            'message'    => $enable ? 'Snippet enabled' : 'Snippet disabled',
        ]);
    } else {
        wp_send_json_error( 'Failed to update setting' );
    }
}
add_action( 'wp_ajax_smp_vp_toggle_snippet', __NAMESPACE__ . '\\ajax_toggle_snippet' );
