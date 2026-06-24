<?php
/**
 * GitHub Plugin Updater for smp-verified-profiles
 * 
 * Enables automatic updates for plugins hosted on GitHub.
 * Self-contained - no dependency on hws-base-tools.
 * 
 * @package smp_verified_profiles
 * @version 2.0.0
 */

namespace smp_verified_profiles;

// ============================================================================
// SECURITY: Prevent direct file access
// ============================================================================
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================================
// PREVENT DUPLICATE CLASS LOADING
// ============================================================================
if ( class_exists( __NAMESPACE__ . '\\WP_GitHub_Updater' ) ) {
    return;
}

/**
 * GitHub Updater Class
 * 
 * Handles all update checking and installation logic for GitHub-hosted plugins.
 * Uses WordPress transients for caching to minimize API calls.
 */
class WP_GitHub_Updater {

    /**
     * Updater version
     */
    const VERSION = '2.0.0';

    /**
     * Configuration values (accessible via get_config())
     * 
     * @var array
     */
    private $config;

    /**
     * Cached GitHub API response
     * 
     * @var object|null
     */
    private $github_data = null;

    /**
     * Cached new version number
     * 
     * @var string|null
     */
    private $new_version = null;

    /**
     * Constructor - Initialize the updater with configuration
     *
     * @param array $config Full configuration array with all required values
     */
    public function __construct( array $config ) {
        // Store configuration
        $this->config = $config;

        // Set defaults for optional values
        $this->config = wp_parse_args( $this->config, [
            'sslverify'    => true,
            'access_token' => '',
            'timeout'      => 10,
            'requires'     => '5.0',
            'tested'       => '6.4',
            'readme'       => 'README.md',
        ] );

        // Validate configuration before proceeding
        if ( ! $this->validate_config() ) {
            return;
        }

        // Prepare runtime values (e.g., add access token to URLs)
        $this->prepare_config();

        // Hook into WordPress update system
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 10, 3 );
        add_filter( 'upgrader_post_install', [ $this, 'post_install' ], 10, 3 );
        
        // HTTP request filters for timeout and SSL
        add_filter( 'http_request_timeout', [ $this, 'http_timeout' ] );
        add_filter( 'http_request_args', [ $this, 'http_args' ], 10, 2 );
    }

    /**
     * Validate that all required configuration values are present
     *
     * @return bool True if valid, false otherwise
     */
    private function validate_config() {
        // Required configuration keys
        $required = [
            'slug',
            'proper_folder_name',
            'api_url',
            'raw_url',
            'zip_url',
            'plugin_name',
            'version',
        ];

        $missing = [];
        foreach ( $required as $key ) {
            if ( empty( $this->config[ $key ] ) ) {
                $missing[] = $key;
            }
        }

        // Log missing keys if debugging is enabled
        if ( ! empty( $missing ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'SMP GitHub Updater: Missing config keys: ' . implode( ', ', $missing ) );
            }
            return false;
        }

        return true;
    }

    /**
     * Prepare runtime configuration values
     * Handles access token injection for private repos
     */
    private function prepare_config() {
        // Add access token to zip URL if provided (for private repos)
        if ( ! empty( $this->config['access_token'] ) ) {
            $this->config['zip_url'] = add_query_arg( 
                'access_token', 
                $this->config['access_token'], 
                $this->config['zip_url'] 
            );
        }
    }

    /**
     * Set HTTP timeout for GitHub requests
     *
     * @param int $timeout Current timeout value
     * @return int Modified timeout value
     */
    public function http_timeout( $timeout ) {
        return isset( $this->config['timeout'] ) ? (int) $this->config['timeout'] : 10;
    }

    /**
     * Modify HTTP request args for GitHub requests
     * Applies SSL verification and authorization headers
     *
     * @param array  $args HTTP request arguments
     * @param string $url  Request URL
     * @return array Modified arguments
     */
    public function http_args( $args, $url ) {
        // Only modify requests to GitHub
        if ( strpos( $url, 'github.com' ) !== false || strpos( $url, 'githubusercontent.com' ) !== false ) {
            $args['sslverify'] = $this->config['sslverify'];
            
            // Add authorization header for private repos
            if ( ! empty( $this->config['access_token'] ) ) {
                $args['headers']['Authorization'] = 'token ' . $this->config['access_token'];
            }
        }
        return $args;
    }

    /**
     * Fetch the latest version from GitHub
     * Reads the Version: header from the main plugin file
     *
     * @return string|false Version string or false on failure
     */
    private function get_remote_version() {
        // Check cache first (30 minute cache)
        $transient_key = 'smp_gu_version_' . md5( $this->config['slug'] );
        $cached = get_site_transient( $transient_key );
        
        // Return cached version unless force update is requested
        if ( $cached !== false && ! ( defined( 'WP_GITHUB_FORCE_UPDATE' ) && WP_GITHUB_FORCE_UPDATE ) ) {
            return $cached;
        }

        // Fetch the main plugin file from GitHub
        $starter_file = isset( $this->config['plugin_starter_file'] ) 
            ? $this->config['plugin_starter_file'] 
            : 'smp-verified-profiles.php';
            
        $url = trailingslashit( $this->config['raw_url'] ) . $starter_file;
        
        $response = wp_remote_get( $url, [
            'sslverify' => $this->config['sslverify'],
            'timeout'   => $this->config['timeout'],
        ] );

        // Handle errors gracefully
        if ( is_wp_error( $response ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'SMP GitHub Updater: Failed to fetch version - ' . $response->get_error_message() );
            }
            return false;
        }

        // Check HTTP response code
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'SMP GitHub Updater: HTTP ' . $response_code . ' when fetching version' );
            }
            return false;
        }

        // Extract version from plugin header
        $body = wp_remote_retrieve_body( $response );
        if ( preg_match( '/^[\s\*]*Version:\s*(.+)$/mi', $body, $matches ) ) {
            $version = trim( $matches[1] );
            
            // Cache for 30 minutes (faster update detection)
            set_site_transient( $transient_key, $version, 30 * MINUTE_IN_SECONDS );
            
            return $version;
        }

        return false;
    }

    /**
     * Fetch GitHub repository data (description, updated_at, etc.)
     *
     * @return object|false Repository data or false on failure
     */
    private function get_github_data() {
        // Return cached data if available
        if ( $this->github_data !== null ) {
            return $this->github_data;
        }

        // Check transient cache
        $transient_key = 'smp_gu_repo_' . md5( $this->config['slug'] );
        $cached = get_site_transient( $transient_key );

        if ( $cached !== false && ! ( defined( 'WP_GITHUB_FORCE_UPDATE' ) && WP_GITHUB_FORCE_UPDATE ) ) {
            $this->github_data = $cached;
            return $this->github_data;
        }

        // Fetch from GitHub API
        $response = wp_remote_get( $this->config['api_url'], [
            'sslverify' => $this->config['sslverify'],
            'timeout'   => $this->config['timeout'],
            'headers'   => ! empty( $this->config['access_token'] ) 
                ? [ 'Authorization' => 'token ' . $this->config['access_token'] ] 
                : [],
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        // Check for valid response
        if ( empty( $data ) || isset( $data->message ) ) {
            return false;
        }

        // Cache for 30 minutes
        set_site_transient( $transient_key, $data, 30 * MINUTE_IN_SECONDS );
        $this->github_data = $data;

        return $this->github_data;
    }

    /**
     * Check for updates and inject into WordPress transient
     * This is the main hook that tells WordPress about available updates
     *
     * @param object $transient Update transient data
     * @return object Modified transient
     */
    public function check_for_update( $transient ) {
        // Only proceed if WordPress has checked for updates
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        // Get remote version from GitHub
        $remote_version = $this->get_remote_version();
        
        if ( $remote_version === false ) {
            return $transient;
        }

        // Compare versions - add to update list if newer
        if ( version_compare( $remote_version, $this->config['version'], '>' ) ) {
            $plugin_info = (object) [
                'slug'        => $this->config['proper_folder_name'],
                'plugin'      => $this->config['slug'],
                'new_version' => $remote_version,
                'url'         => $this->config['github_url'],
                'package'     => $this->config['zip_url'],
                'icons'       => [],
                'banners'     => [],
                'tested'      => $this->config['tested'],
                'requires'    => $this->config['requires'],
            ];

            $transient->response[ $this->config['slug'] ] = $plugin_info;
        }

        return $transient;
    }

    /**
     * Provide plugin information for the "View version details" popup
     * Shown when user clicks "View version x.x details" link
     *
     * @param false|object $result Plugin info result
     * @param string       $action API action being performed
     * @param object       $args   Request arguments
     * @return object|false Plugin info or false
     */
    public function plugin_info( $result, $action, $args ) {
        // Only respond to plugin_information requests
        if ( $action !== 'plugin_information' ) {
            return $result;
        }

        // Check if this request is for our plugin (by folder name)
        if ( ! isset( $args->slug ) || $args->slug !== $this->config['proper_folder_name'] ) {
            return $result;
        }

        // Get latest version and GitHub data
        $remote_version = $this->get_remote_version();
        $github_data = $this->get_github_data();

        // Build plugin information object
        return (object) [
            'name'              => $this->config['plugin_name'],
            'slug'              => $this->config['proper_folder_name'],
            'version'           => $remote_version ?: $this->config['version'],
            'author'            => $this->config['author'],
            'author_profile'    => $this->config['homepage'],
            'homepage'          => $this->config['homepage'],
            'requires'          => $this->config['requires'],
            'tested'            => $this->config['tested'],
            'downloaded'        => 0,
            'last_updated'      => $github_data ? date( 'Y-m-d', strtotime( $github_data->updated_at ) ) : '',
            'sections'          => [
                'description' => isset( $this->config['description'] ) ? $this->config['description'] : '',
                'changelog'   => $github_data && isset( $github_data->description ) 
                    ? $github_data->description 
                    : 'See GitHub repository for changelog.',
            ],
            'download_link'     => $this->config['zip_url'],
        ];
    }

    /**
     * Handle post-installation folder renaming
     * GitHub archives include branch name in folder (e.g., repo-main),
     * so we rename it to match the expected plugin folder name
     *
     * @param bool  $response   Installation response
     * @param array $hook_extra Extra hook arguments
     * @param array $result     Installation result
     * @return array Modified result
     */
    public function post_install( $response, $hook_extra, $result ) {
        global $wp_filesystem;

        // Only process our plugin
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->config['slug'] ) {
            return $result;
        }

        // Get the correct destination
        $proper_destination = WP_PLUGIN_DIR . '/' . $this->config['proper_folder_name'];

        // Move to correct location
        $wp_filesystem->move( $result['destination'], $proper_destination );
        $result['destination'] = $proper_destination;

        // Reactivate plugin
        $activate = activate_plugin( $this->config['slug'] );
        
        if ( is_wp_error( $activate ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'SMP GitHub Updater: Failed to reactivate plugin after update' );
            }
        }

        return $result;
    }

    /**
     * Clear update caches (useful for debugging or forcing fresh check)
     */
    public function clear_cache() {
        delete_site_transient( 'smp_gu_version_' . md5( $this->config['slug'] ) );
        delete_site_transient( 'smp_gu_repo_' . md5( $this->config['slug'] ) );
        delete_site_transient( 'update_plugins' );
    }

    /**
     * Public getter for the latest remote version
     * 
     * @return string|false Version string or false on failure
     */
    public function get_new_version() {
        return $this->get_remote_version();
    }

    /**
     * Public getter for config values
     * Replaces direct access to private $config property
     * 
     * @param string|null $key Config key to retrieve (null returns all)
     * @return mixed Config value, full config array, or null if key not found
     */
    public function get_config( $key = null ) {
        if ( $key === null ) {
            return $this->config;
        }
        return isset( $this->config[ $key ] ) ? $this->config[ $key ] : null;
    }
}
