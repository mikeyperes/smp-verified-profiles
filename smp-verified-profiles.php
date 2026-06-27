<?php
/**
 * Plugin Name: Verified Profiles - Scale My Publication (Michael Peres)
 * Description: Verified Profile integration for Scale My Publication systems.
 * Author: Michael Peres
 * Plugin URI: https://github.com/mikeyperes/smp-verified-profiles
 * Version: 6.5.33
 * Text Domain: smp-verified-profiles
 * Domain Path: /languages
 * Author URI: https://michaelperes.com
 * GitHub Plugin URI: https://github.com/mikeyperes/smp-verified-profiles
 * GitHub Branch: main
 */

namespace smp_verified_profiles;

// ============================================================================
// SECURITY: Prevent direct file access
// ============================================================================
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

// ============================================================================
// CONFIGURATION CLASS - All plugin-specific settings in one place
// Never hardcode context-specific strings elsewhere - always use Config::
// ============================================================================
class Config {
    // -------------------------------------------------------------------------
    // Plugin Identity (used throughout the plugin - never hardcode these)
    // -------------------------------------------------------------------------
    
    /** @var string Plugin display name */
    public static $plugin_name = "Scale My Publication - Verified Profiles";
    
    /** @var string Main plugin file name */
    public static $plugin_starter_file = "smp-verified-profiles.php";
    
    /** @var string Plugin folder name (must match actual folder) */
    public static $plugin_folder_name = "smp-verified-profiles";
    
    /** @var string Short identifier for options/transients */
    public static $plugin_short_id = "smp_vp";

    /** @var string Current plugin version */
    public static $plugin_version = "6.5.33";

    /** @var string Shared nonce action for Hexa core admin AJAX */
    public static $ajax_nonce_action = "smp_vp_admin";

    /** @var string Shared nonce field for Hexa core admin AJAX */
    public static $ajax_nonce_field = "nonce";

    /** @var string Prefix for Hexa core plugin updater AJAX actions */
    public static $updater_ajax_prefix = "smp_vp_core_updater";

    /** @var string Prefix for vendored Hexa WordPress Plugin Core package updater AJAX actions */
    public static $core_package_ajax_prefix = "smp_vp_hexa_core_package";
    
    // -------------------------------------------------------------------------
    // Settings Page Configuration
    // -------------------------------------------------------------------------
    
    /** @var string Settings page HTML ID */
    public static $settings_page_html_id = "smp_verified_profiles";
    
    /** @var string Settings page menu title */
    public static $settings_page_name = "Verified Profiles - Settings";
    
    /** @var string Required capability to access settings */
    public static $settings_page_capability = "manage_options";
    
    /** @var string Settings page URL slug */
    public static $settings_page_slug = "smp-verified-profiles";
    
    /** @var string Settings page display title */
    public static $settings_page_display_title = "Verified Profiles - Settings";
    
    // -------------------------------------------------------------------------
    // GitHub Repository Configuration
    // -------------------------------------------------------------------------
    
    /** @var string GitHub repository path (username/repo) */
    public static $github_repo = "mikeyperes/smp-verified-profiles";
    
    /** @var string GitHub branch to track for updates */
    public static $github_branch = "main";
    
    /**
     * Get the full plugin basename (folder/file.php)
     * 
     * @return string Plugin basename for WordPress functions
     */
    public static function get_plugin_basename() {
        return self::$plugin_folder_name . '/' . self::$plugin_starter_file;
    }
    
    /**
     * Get the full path to the main plugin file
     * 
     * @return string Full file path
     */
    public static function get_plugin_file_path() {
        return WP_PLUGIN_DIR . '/' . self::get_plugin_basename();
    }
    
    /**
     * Build configuration array for the GitHub Updater
     * Pulls metadata from plugin headers dynamically
     * 
     * @return array Complete configuration for WP_GitHub_Updater
     */
    public static function get_github_config() {
        // Ensure we can read plugin headers
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get the main plugin file path
        $plugin_file = self::get_plugin_file_path();
        
        // Pull header info from the plugin file
        $plugin_data = get_plugin_data( $plugin_file );
        
        // Build and return the updater config using Config values (no hardcoding!)
        return [
            // Plugin's WP slug (folder/file path under wp-content/plugins)
            'slug'               => self::get_plugin_basename(),
            
            // Folder name on disk
            'proper_folder_name' => self::$plugin_folder_name,
            
            // GitHub endpoints & download URL
            'api_url'            => 'https://api.github.com/repos/' . self::$github_repo,
            'raw_url'            => 'https://raw.githubusercontent.com/' . self::$github_repo . '/' . self::$github_branch,
            'github_url'         => 'https://github.com/' . self::$github_repo,
            'zip_url'            => 'https://github.com/' . self::$github_repo . '/archive/' . self::$github_branch . '.zip',
            
            // HTTP settings
            'sslverify'          => true,
            'access_token'       => '',
            'timeout'            => 10,
            
            // WP compatibility
            'requires'           => '5.0',
            'tested'             => '6.4',
            'readme'             => 'README.md',
            
            // Which file to read "Version:" from
            'plugin_starter_file' => self::$plugin_starter_file,
            
            // Metadata pulled straight from the plugin header
            'plugin_name'        => $plugin_data['Name'],
            'version'            => $plugin_data['Version'],
            'author'             => $plugin_data['Author'],
            'homepage'           => $plugin_data['PluginURI'],
            'description'        => $plugin_data['Description'],
        ];
    }
}

function add_plugin_settings_action_link( array $links ): array {
    $settings_url = admin_url( 'options-general.php?page=' . Config::$settings_page_slug );
    $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'smp-verified-profiles' ) . '</a>';

    $links[] = $settings_link;

    return $links;
}

add_filter( 'plugin_action_links_' . Config::get_plugin_basename(), __NAMESPACE__ . '\\add_plugin_settings_action_link' );

function smp_vp_request_value( string $key ): string {
    if ( isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ) {
        return sanitize_key( wp_unslash( (string) $_POST[ $key ] ) );
    }

    if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) {
        return sanitize_key( wp_unslash( (string) $_GET[ $key ] ) );
    }

    return '';
}

function smp_vp_is_settings_dashboard_request(): bool {
    if ( is_admin() && Config::$settings_page_slug === smp_vp_request_value( 'page' ) ) {
        return true;
    }

    if ( wp_doing_ajax() ) {
        $action = smp_vp_request_value( 'action' );
        return in_array(
            $action,
            [
                'smp_vp_load_tab',
                'smp_vp_toggle_snippet',
                'smp_verified_profiles_toggle_snippet',
                'smp_verified_profiles_modify_wp_config_constants',
                'smp_verified_profiles_execute_function',
                'smp_vp_force_plugin_update_check',
                'smp_vp_display_save_settings',
                'smp_vp_display_import_elementor',
                'smp_vp_display_create_loop_item',
                'smp_vp_display_save_loop_item',
                'smp_vp_display_delete_loop_item',
            ],
            true
        );
    }

    return false;
}

function smp_vp_is_relevant_admin_request(): bool {
    if ( smp_vp_is_settings_dashboard_request() ) {
        return true;
    }

    if ( ! is_admin() ) {
        return false;
    }

    global $pagenow;
    if ( isset( $pagenow ) && in_array( $pagenow, [ 'post.php', 'post-new.php', 'edit.php', 'users.php', 'user-edit.php', 'profile.php' ], true ) ) {
        return true;
    }

    if ( 'profiles-dashboard' === smp_vp_request_value( 'page' ) ) {
        return true;
    }

    if ( wp_doing_ajax() ) {
        return in_array(
            smp_vp_request_value( 'action' ),
            [
                'get_unclaimed_profiles',
                'send_email',
                'refresh_user',
                'smp_vp_spawn_save_settings',
                'smp_vp_spawn_test_api',
                'smp_vp_spawn_propose',
                'smp_vp_spawn_detect_existing',
                'smp_vp_spawn_profile_state',
                'smp_vp_spawn_approve',
                'smp_vp_display_save_settings',
                'smp_vp_display_import_elementor',
                'smp_vp_display_create_loop_item',
                'smp_vp_display_save_loop_item',
                'smp_vp_display_delete_loop_item',
            ],
            true
        );
    }

    return false;
}

function smp_vp_load_settings_dashboard_files(): void {
    static $loaded = false;

    if ( $loaded ) {
        return;
    }

    include_once __DIR__ . '/settings-dashboard-define-pages-and-listing-grids.php';
    include_once __DIR__ . '/settings-dashboard-system-checks.php';
    include_once __DIR__ . '/settings-dashboard-plugin-info.php';
    include_once __DIR__ . '/settings-dashboard-plugin-checks.php';
    include_once __DIR__ . '/settings-event-handling.php';
    include_once __DIR__ . '/setting-dashboard-process-schema-objects.php';
    include_once __DIR__ . '/settings-dashboard-components.php';
    include_once __DIR__ . '/settings-dashboard-overview.php';
    include_once __DIR__ . '/settings-dashboard-snippets.php';
    include_once __DIR__ . '/settings-dashboard-shortcodes.php';
    include_once __DIR__ . '/settings-dashboard.php';
    include_once __DIR__ . '/verified-profile-display-templates.php';
	            include_once __DIR__ . '/verified-profile-spawner.php';

    $loaded = true;
}

function smp_vp_render_settings_page(): void {
    smp_vp_load_settings_dashboard_files();

    if ( function_exists( __NAMESPACE__ . '\\display_wp_admin_settings_page' ) ) {
        display_wp_admin_settings_page();
    }
}

function smp_vp_register_settings_menu(): void {
    add_options_page(
        Config::$settings_page_name,
        Config::$settings_page_name,
        Config::$settings_page_capability,
        Config::$settings_page_slug,
        __NAMESPACE__ . '\\smp_vp_render_settings_page'
    );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\smp_vp_register_settings_menu' );

// ============================================================================
// HEXA WORDPRESS PLUGIN CORE AUTOLOADER
// ============================================================================
function register_hexa_plugin_core_autoloader(): void {
    static $registered = false;

    if ( $registered ) {
        return;
    }

    $base_dir = __DIR__ . '/lib/hexa-wordpress-plugin-core/src/';
    $prefix   = 'Hexa\\PluginCore\\';

    spl_autoload_register(
        static function( string $class_name ) use ( $base_dir, $prefix ): void {
            if ( strncmp( $class_name, $prefix, strlen( $prefix ) ) !== 0 ) {
                return;
            }

            $relative_class = substr( $class_name, strlen( $prefix ) );
            $file           = $base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

            if ( is_readable( $file ) ) {
                require_once $file;
            }
        },
        true,
        true
    );

    $registered = true;
}

register_hexa_plugin_core_autoloader();

// ============================================================================
// DEBUG LOGGING FUNCTION
// ============================================================================
if ( ! function_exists( __NAMESPACE__ . '\\write_log' ) ) {
    /**
     * Write debug log entries when WP_DEBUG and WP_DEBUG_LOG are enabled
     * 
     * @param mixed  $log           Data to log (string, array, or object)
     * @param bool   $full_debug    Whether to enable full debug output
     * @param bool   $display_stack Whether to include stack trace
     */
    function write_log( $log, $full_debug = false, $display_stack = false ) {
        // Only log if debugging is enabled
        if ( ! WP_DEBUG || ! WP_DEBUG_LOG || ! $full_debug ) {
            return;
        }
        
        // Prepare the log message
        $msg = is_array( $log ) || is_object( $log )
             ? print_r( $log, true )
             : $log;
        $msg .= "\n\n";
        
        // Add stack trace if requested
        if ( $display_stack ) {
            $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
            
            // Skip frame 0 (this function) and frame 1 (caller)
            foreach ( array_slice( $backtrace, 2 ) as $index => $frame ) {
                $class = isset( $frame['class'] ) ? $frame['class'] . $frame['type'] : '';
                $func  = $frame['function'] ?? 'N/A';
                $file  = $frame['file'] ?? 'N/A';
                $line  = $frame['line'] ?? 'N/A';
                
                $msg .= sprintf(
                    "Stack #%d → %s%s() in %s on line %s\n",
                    $index + 1,
                    $class,
                    $func,
                    $file,
                    $line
                );
            }
            $msg .= "\n---";
        }
        
        error_log( $msg );
    }
}

// ============================================================================
// ELEMENTOR CONTEXT DETECTION (Performance optimization)
// Skip heavy admin features during Elementor editor operations
// ============================================================================
if ( ! function_exists( __NAMESPACE__ . '\\is_elementor_context' ) ) {
    /**
     * Detect if current request is from Elementor editor/preview/AJAX
     * Used to skip unnecessary processing during page building
     * 
     * @return bool True if in Elementor context
     */
    function is_elementor_context(): bool {
        // Check for Elementor admin-ajax requests
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
            if ( in_array( $action, [ 'elementor_ajax', 'elementor' ], true ) ) {
                return true;
            }
        }
        
        // Check for Elementor REST routes
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
            if ( strpos( $uri, '/wp-json/elementor/' ) !== false ) {
                return true;
            }
        }
        
        // Check for Elementor GET parameters (editor/preview)
        if ( ! empty( $_GET ) ) {
            if (
                isset( $_GET['elementor-preview'] ) ||
                isset( $_GET['elementor_library'] ) ||
                ( isset( $_GET['action'] ) && in_array( $_GET['action'], [ 'elementor', 'elementor_ajax' ], true ) ) ||
                ( isset( $_GET['elementor_safe_mode'] ) && $_GET['elementor_safe_mode'] )
            ) {
                return true;
            }
        }
        
        return false;
    }
}

// ============================================================================
// REQUIRED PLUGIN DEPENDENCY CHECK
// ============================================================================

/**
 * Check that required plugin groups are satisfied
 * Each group is OR logic (any one plugin satisfies), groups are AND logic
 * 
 * @param array $groups Array of plugin-file arrays
 * @return array [bool $ok, string $error_message]
 */
function check_required_plugins( array $groups ): array {
    // Load WP plugin helpers if needed
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $missing = [];
    
    foreach ( $groups as $group ) {
        $group_ok = false;
        foreach ( $group as $plugin_file ) {
            if ( is_plugin_active( $plugin_file ) ) {
                $group_ok = true;
                break;
            }
        }
        if ( ! $group_ok ) {
            $missing[] = $group;
        }
    }
    
    if ( empty( $missing ) ) {
        return [ true, '' ];
    }
    
    // Build human-readable list of missing groups
    $labels = array_map( function( $group ) {
        return implode( ' or ', $group );
    }, $missing );
    
    $error = 'requires ' . implode( ', ', $labels ) . '.';
    
    return [ false, $error ];
}

// Define required plugins (ACF Pro is required)
$required_plugins = [
    [
        'advanced-custom-fields-pro/acf.php',
        'advanced-custom-fields-pro-temp/acf.php'
    ]
];

// Check required plugins
list( $plugins_ok, $plugin_error ) = check_required_plugins( $required_plugins );
if ( ! $plugins_ok ) {
    // Show admin notice if requirements not met
    add_action( 'admin_notices', function() use ( $plugin_error ) {
        echo '<div class="notice notice-error"><p><strong>'
           . esc_html( Config::$plugin_name )
           . '</strong> ' . esc_html( $plugin_error ) . '</p></div>';
    } );
    return; // Stop plugin execution
}

// ============================================================================
// LOAD CORE FILES
// ============================================================================

// Generic helper functions
include_once __DIR__ . '/generic-functions.php';

// Hexa Plugin Core integration: shared AJAX guard, updater, and host tabs.
include_once __DIR__ . '/hexa-core-integration.php';

if ( function_exists( __NAMESPACE__ . '\\smp_vp_boot_hexa_core_admin' ) ) {
    add_action( 'plugins_loaded', __NAMESPACE__ . '\\smp_vp_boot_hexa_core_admin', 20 );
    add_action( 'admin_init', __NAMESPACE__ . '\\smp_vp_boot_hexa_core_admin', 5 );
}

// ============================================================================
// ADMIN INIT: Output buffering fix for settings page
// ============================================================================
if ( is_admin() ) {
    add_action( 'admin_init', function() {
        // Remove shutdown output buffer flush on our settings page
        // Prevents "headers already sent" issues
        if ( isset( $_GET['page'] ) && $_GET['page'] === Config::$settings_page_slug ) {
            remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
        }

        if ( Config::$settings_page_slug === smp_vp_request_value( 'page' ) && 'emails' === smp_vp_request_value( 'tab' ) && function_exists( 'acf_form_head' ) ) {
            acf_form_head();
        }
    } );
}

// ============================================================================
// HEXA CORE GITHUB UPDATER INITIALIZATION
// Runtime update checks are registered through Hexa Plugin Core in hexa-core-integration.php.
// ============================================================================
// ============================================================================
// INCLUDE SNIPPET ACTIVATION SYSTEM
// ============================================================================
include_once __DIR__ . '/activate-snippets.php';

// ============================================================================
// ACF INITIALIZATION HOOK
// Load ACF-dependent structures after ACF is fully initialized
// ============================================================================
add_action( 'acf/init', function() {
    // ACF field group registrations
    include_once __DIR__ . '/register-acf-structure-theme-options.php';
    include_once __DIR__ . '/register-acf-structures.php';
    include_once __DIR__ . '/register-acf-user-profile.php';
    include_once __DIR__ . '/register-acf-verified-profile.php';
    
    // Activate ACF-related snippets
    activate_snippets( 'acf' );
}, 10 );

// ============================================================================
// MAIN INIT HOOK - Load features conditionally
// ============================================================================
add_action( 'init', function() {
    
    // -------------------------------------------------------------------------
    // ADMIN-ONLY FEATURES (Performance: skip on frontend)
    // -------------------------------------------------------------------------
	if ( is_admin() ) {
	    // Skip heavy processing during Elementor operations
	    if ( ! is_elementor_context() ) {
	        // Profile-manager menu registration is lightweight and guarded internally.
	        include_once __DIR__ . '/profile-manager-dashboard.php';

	        if ( smp_vp_is_relevant_admin_request() ) {
	            include_once __DIR__ . '/snippet-adjust-profiles-category-meta-box.php';
	            include_once __DIR__ . '/snippet-adjust-wp-admin-for-profile-managers.php';
	            include_once __DIR__ . '/snippet-wp-admin-user-page-functionality.php';
	            include_once __DIR__ . '/snippet-post-functionality.php';
	            include_once __DIR__ . '/verified-profile-display-templates.php';
    include_once __DIR__ . '/verified-profile-spawner.php';
	            include_once __DIR__ . '/snippet-profile-post-wp-admin-functionality.php';
	            include_once __DIR__ . '/snippet-wp-admin-user-page-optional-functionality.php';
	            include_once __DIR__ . '/snippet-disable-password-reset.php';
	            include_once __DIR__ . '/snippet-wp-admin-add-featured-image-to-events.php';
	            include_once __DIR__ . '/snippet-wp-admin-filter-featured-profiles.php';

	            if ( smp_vp_is_settings_dashboard_request() ) {
	                smp_vp_load_settings_dashboard_files();
	            }

	            activate_snippets( 'admin' );
	        }
	    }
	}
    
    // -------------------------------------------------------------------------
    // FRONTEND + ADMIN FEATURES (Always loaded)
    // -------------------------------------------------------------------------
    include_once __DIR__ . '/snippet-claim-profile-functionality.php';
    include_once __DIR__ . '/snippet-faviconn-for-verified-pages.php';
    include_once __DIR__ . '/snippet-inject-schema-on-single-profile.php';
    include_once __DIR__ . '/snippet-muckrack-functionality.php';
    include_once __DIR__ . '/verified-profile-display-templates.php';
    include_once __DIR__ . '/shortcodes.php';
    include_once __DIR__ . '/snippet-shortcodes-entities.php';
    
    // Activate non-admin snippets
    activate_snippets( 'non_admin' );
    
}, 12 );

// ============================================================================
// SNIPPET DEFINITIONS
// Returns arrays of available snippets for different contexts
// ============================================================================

/**
 * Get array of available snippets by type
 * 
 * @param string $type Type of snippets: 'acf', 'admin', or 'non_admin'
 * @return array Array of snippet definitions
 */
function get_snippets( $type = '' ) {
    
    // -------------------------------------------------------------------------
    // ACF SNIPPETS - Field group registrations
    // -------------------------------------------------------------------------
    $snippets_acf = [
        [
            'id'                => 'enable_acf_theme_options',
            'name'              => 'enable_acf_theme_options',
            'description'       => '',
            'info'              => display_acf_structure( [ 'group_6850930366d8f' ] ),
            'function'          => 'enable_acf_theme_options',
            'scope_admin_only'  => false
        ],
        [
            'id'          => 'register_profile_custom_post_type',
            'name'        => 'register_profile_custom_post_type',
            'description' => '',
            'info'        => '',
            'function'    => 'register_profile_custom_post_type'
        ],
        [
            'id'          => 'register_profile_general_acf_fields',
            'name'        => 'register_profile_acf_fields',
            'description' => display_acf_structure( [
                'group_66b7bdf713e77',  // Post - Verified Profile - Admin
                'group_656ea6b4d7088',  // Profile - Admin
                'group_656eb036374de',  // Profile - Person - Public
                'group_65a8b25062d91',  // User - Profile Manager
                'group_658602c9eaa49',  // User - Verified Profile Manager - Admin
            ] ),
            'info'     => '',
            'function' => 'register_profile_general_acf_fields'
        ],
        [
            'id'          => 'register_verified_profile_custom_fields',
            'name'        => 'register_verified_profile_custom_fields',
            'description' => display_acf_structure( [ 'group_67e39e4171b16' ] ),
            'info'        => '',
            'function'    => 'register_verified_profile_custom_fields'
        ],
        [
            'id'          => 'register_user_custom_fields',
            'name'        => 'register_user_custom_fields',
            'description' => display_acf_structure( 'group_verified_profiles_settings' ),
            'info'        => '',
            'function'    => 'register_user_custom_fields'
        ],
        [
            'id'          => 'register_verified_profile_pages_custom_fields',
            'name'        => 'register_verified_profile_pages_custom_fields',
            'description' => display_acf_structure( 'group_verified_profiles_settings' ),
            'info'        => '',
            'function'    => 'register_verified_profile_pages_custom_fields'
        ]
    ];
    
    // -------------------------------------------------------------------------
    // NON-ADMIN SNIPPETS - Frontend functionality
    // -------------------------------------------------------------------------
    $snippet_non_admin = [
        [
            'id'          => 'enable_snippet_inject_schema_on_single_profile',
            'name'        => 'enable_snippet_inject_schema_on_single_profile',
            'description' => '',
            'info'        => '',
            'function'    => 'enable_snippet_inject_schema_on_single_profile'
        ],
        [
            'id'          => 'enable_snippet_faviconn_for_verified_pages',
            'name'        => 'enable_snippet_faviconn_for_verified_pages',
            'description' => '',
            'info'        => '',
            'function'    => 'enable_snippet_faviconn_for_verified_pages'
        ],
        [
            'id'          => 'enable_snippet_claim_profile_functionality',
            'name'        => 'enable_snippet_claim_profile_functionality',
            'description' => '',
            'info'        => '',
            'function'    => 'enable_snippet_claim_profile_functionality'
        ],
        [
            'id'          => 'enable_snippet_muckrack_functionality',
            'name'        => 'enable_snippet_muckrack_functionality',
            'description' => get_formatted_shortcode_list( __NAMESPACE__ . '\\get_muckrack_shortcodes' ),
            'info'        => '',
            'function'    => 'enable_snippet_muckrack_functionality'
        ],
        [
            'id'          => 'enable_snippet_verified_profile_shortcodes',
            'name'        => 'enable_snippet_verified_profile_shortcodes',
            'description' => get_formatted_shortcode_list( __NAMESPACE__ . '\\get_verified_profile_shortcodes' ),
            'info'        => '',
            'function'    => 'enable_snippet_verified_profile_shortcodes'
        ]
    ];
    
    // -------------------------------------------------------------------------
    // ADMIN SNIPPETS - Backend functionality
    // -------------------------------------------------------------------------
    $snippets_admin = [
        [
            'id'               => 'add_wp_admin_add_featured_image_to_events',
            'name'             => 'add_wp_admin_add_featured_image_to_events',
            'description'      => '',
            'info'             => '',
            'function'         => 'add_wp_admin_add_featured_image_to_events',
            'scope_admin_only' => true
        ],
        [
            'id'          => 'snippet_post_functionality',
            'name'        => 'snippet_post_functionality',
            'description' => '',
            'info'        => '',
            'function'    => 'snippet_post_functionality'
        ],
        [
            'id'          => 'enable_snippet_wp_admin_user_page_functionality',
            'name'        => 'enable_snippet_wp_admin_user_page_functionality',
            'description' => '',
            'info'        => '',
            'function'    => 'enable_snippet_wp_admin_user_page_functionality'
        ],
        [
            'id'          => 'enable_snippet_adjust_profiles_category_meta_box',
            'name'        => 'enable_snippet_adjust_profiles_category_meta_box',
            'description' => '',
            'info'        => '',
            'function'    => 'enable_snippet_adjust_profiles_category_meta_box'
        ],
        [
            'id'          => 'enable_snippet_profile_post_wp_admin_functionality',
            'name'        => 'enable_snippet_profile_post_wp_admin_functionality',
            'description' => '',
            'info'        => '',
            'function'    => 'enable_snippet_profile_post_wp_admin_functionality'
        ],
        [
            'id'          => 'enable_snippet_wp_admin_user_page_optional_functionality',
            'name'        => 'enable_snippet_wp_admin_user_page_optional_functionality',
            'description' => '',
            'info'        => '',
            'function'    => 'enable_snippet_wp_admin_user_page_optional_functionality'
        ],
        [
            'id'          => 'enable_snippet_disable_password_reset',
            'name'        => 'enable_snippet_disable_password_reset',
            'description' => '',
            'info'        => '',
            'function'    => 'enable_snippet_disable_password_reset'
        ],
    ];
    
    // -------------------------------------------------------------------------
    // RETURN BASED ON TYPE
    // -------------------------------------------------------------------------
    if ( $type === 'non_admin' ) {
        return $snippet_non_admin;
    }
    
    if ( $type === 'admin' ) {
        return $snippets_admin;
    }
    
    return $snippets_acf;
}
