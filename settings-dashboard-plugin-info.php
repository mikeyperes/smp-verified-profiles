<?php
/**
 * Plugin Info Dashboard Panel
 * 
 * Displays plugin information, version status, and update controls.
 * Includes GitHub version download, direct updates, and rollback capabilities.
 * 
 * @package smp_verified_profiles
 * @version 2.0.0
 */

namespace smp_verified_profiles;

// ============================================================================
// SECURITY: Prevent direct file access
// ============================================================================
defined( 'ABSPATH' ) || exit;

// ============================================================================
// AJAX HANDLERS
// ============================================================================

/**
 * AJAX: Load available versions (tags) from GitHub
 */
function ajax_load_github_versions() {
    if ( ! current_user_can( 'update_plugins' ) ) {
        wp_send_json_error( 'Unauthorized' );
        return;
    }
    
    $github_repo = Config::$github_repo;
    $tags_url = 'https://api.github.com/repos/' . $github_repo . '/tags';
    
    $response = wp_remote_get( $tags_url, [
        'timeout' => 15,
        'headers' => [
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
        ],
    ] );
    
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'Failed to fetch versions: ' . $response->get_error_message() );
        return;
    }
    
    $body = wp_remote_retrieve_body( $response );
    $tags = json_decode( $body, true );
    
    if ( ! is_array( $tags ) ) {
        wp_send_json_error( 'Invalid response from GitHub' );
        return;
    }
    
    $versions = [];
    foreach ( $tags as $tag ) {
        if ( isset( $tag['name'] ) ) {
            $versions[] = [
                'name'    => $tag['name'],
                'zip_url' => 'https://github.com/' . $github_repo . '/archive/refs/tags/' . $tag['name'] . '.zip',
            ];
        }
    }
    
    array_unshift( $versions, [
        'name'    => 'main (latest)',
        'zip_url' => 'https://github.com/' . $github_repo . '/archive/refs/heads/main.zip',
    ] );
    
    wp_send_json_success( [
        'versions' => $versions,
        'count'    => count( $versions ),
    ] );
}

/**
 * AJAX: Download a specific version from GitHub
 */
function ajax_download_specific_version() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
        return;
    }
    
    $version = isset( $_POST['version'] ) ? sanitize_text_field( $_POST['version'] ) : '';
    if ( empty( $version ) ) {
        wp_send_json_error( 'No version specified' );
        return;
    }
    
    $github_repo         = Config::$github_repo;
    $correct_folder_name = Config::$plugin_folder_name;
    
    if ( $version === 'main (latest)' ) {
        $download_url = 'https://github.com/' . $github_repo . '/archive/refs/heads/main.zip';
        $version_slug = 'main';
    } else {
        $download_url = 'https://github.com/' . $github_repo . '/archive/refs/tags/' . $version . '.zip';
        $version_slug = $version;
    }
    
    $upload_dir = wp_upload_dir();
    $temp_dir   = $upload_dir['basedir'] . '/smp-temp-' . time();
    $temp_zip   = $temp_dir . '/github-download.zip';
    $final_zip  = $upload_dir['basedir'] . '/' . $correct_folder_name . '-' . $version_slug . '.zip';
    
    if ( ! wp_mkdir_p( $temp_dir ) ) {
        wp_send_json_error( 'Could not create temp directory' );
        return;
    }
    
    $response = wp_remote_get( $download_url, [
        'timeout'  => 60,
        'stream'   => true,
        'filename' => $temp_zip,
    ] );
    
    if ( is_wp_error( $response ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Failed to download from GitHub: ' . $response->get_error_message() );
        return;
    }
    
    if ( ! file_exists( $temp_zip ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Download failed - file not created' );
        return;
    }
    
    $extract_dir = $temp_dir . '/extracted';
    wp_mkdir_p( $extract_dir );
    
    $zip = new \ZipArchive();
    if ( $zip->open( $temp_zip ) !== true ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Failed to open downloaded zip file' );
        return;
    }
    
    $zip->extractTo( $extract_dir );
    $zip->close();
    
    $extracted_folders = glob( $extract_dir . '/*', GLOB_ONLYDIR );
    if ( empty( $extracted_folders ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'No folder found in extracted zip' );
        return;
    }
    
    $wrong_folder   = $extracted_folders[0];
    $correct_folder = $extract_dir . '/' . $correct_folder_name;
    
    if ( basename( $wrong_folder ) !== $correct_folder_name ) {
        rename( $wrong_folder, $correct_folder );
    } else {
        $correct_folder = $wrong_folder;
    }
    
    $new_zip = new \ZipArchive();
    if ( $new_zip->open( $final_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Failed to create new zip file' );
        return;
    }
    
    smp_add_folder_to_zip( $new_zip, $correct_folder, $correct_folder_name );
    $new_zip->close();
    
    smp_delete_directory( $temp_dir );
    
    $final_url = $upload_dir['baseurl'] . '/' . $correct_folder_name . '-' . $version_slug . '.zip';
    
    wp_send_json_success( [
        'message'  => 'Version ' . $version . ' ready for download',
        'url'      => $final_url,
        'filename' => $correct_folder_name . '-' . $version_slug . '.zip',
    ] );
}

/**
 * AJAX: Force WordPress to check for plugin updates
 */
function ajax_force_update_check() {
    if ( ! current_user_can( 'update_plugins' ) ) {
        wp_send_json_error( 'Unauthorized' );
        return;
    }
    
    $plugin_basename = Config::get_plugin_basename();
    $github_repo     = Config::$github_repo;
    $github_branch   = Config::$github_branch;
    
    delete_site_transient( 'smp_gu_version_' . md5( $plugin_basename ) );
    delete_site_transient( 'smp_gu_repo_' . md5( $plugin_basename ) );
    delete_site_transient( 'smp_github_ver_' . md5( $github_repo . $github_branch ) );
    delete_site_transient( 'update_plugins' );
    delete_option( '_site_transient_update_plugins' );
    
    wp_clean_update_cache();
    wp_update_plugins();
    
    $new_version = smp_get_github_version_fresh( $github_repo, $github_branch );
    
    wp_send_json_success( [
        'message'     => 'Update check complete',
        'new_version' => $new_version ?: 'Unknown',
    ] );
}

/**
 * Get GitHub version WITHOUT using cache
 */
function smp_get_github_version_fresh( $repo, $branch = 'main' ) {
    $url = 'https://raw.githubusercontent.com/' . $repo . '/' . $branch . '/' . Config::$plugin_starter_file;
    
    $response = wp_remote_get( $url, [
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => [ 'Cache-Control' => 'no-cache' ],
    ] );
    
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return false;
    }
    
    $body = wp_remote_retrieve_body( $response );
    if ( preg_match( '/^[\s\*]*Version:\s*(.+)$/mi', $body, $matches ) ) {
        $version = trim( $matches[1] );
        set_site_transient( 'smp_github_ver_' . md5( $repo . $branch ), $version, 30 * MINUTE_IN_SECONDS );
        return $version;
    }
    
    return false;
}

/**
 * AJAX: Direct update plugin from GitHub
 */
function ajax_direct_update_plugin() {
    if ( ! current_user_can( 'update_plugins' ) ) {
        wp_send_json_error( 'Unauthorized - you need update_plugins capability' );
        return;
    }
    
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    
    WP_Filesystem();
    global $wp_filesystem;
    
    $github_repo         = Config::$github_repo;
    $github_branch       = Config::$github_branch;
    $correct_folder_name = Config::$plugin_folder_name;
    $plugin_file         = Config::get_plugin_basename();
    
    $download_url = 'https://github.com/' . $github_repo . '/archive/refs/heads/' . $github_branch . '.zip';
    
    $temp_dir = get_temp_dir() . 'smp-update-' . time();
    $temp_zip = $temp_dir . '/github-download.zip';
    
    if ( ! wp_mkdir_p( $temp_dir ) ) {
        wp_send_json_error( 'Could not create temp directory' );
        return;
    }
    
    $response = wp_remote_get( $download_url, [
        'timeout'  => 120,
        'stream'   => true,
        'filename' => $temp_zip,
    ] );
    
    if ( is_wp_error( $response ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Failed to download from GitHub: ' . $response->get_error_message() );
        return;
    }
    
    if ( ! file_exists( $temp_zip ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Download failed - file not created' );
        return;
    }
    
    $extract_dir = $temp_dir . '/extracted';
    wp_mkdir_p( $extract_dir );
    
    $unzip_result = unzip_file( $temp_zip, $extract_dir );
    if ( is_wp_error( $unzip_result ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Failed to extract zip: ' . $unzip_result->get_error_message() );
        return;
    }
    
    $extracted_folders = glob( $extract_dir . '/*', GLOB_ONLYDIR );
    if ( empty( $extracted_folders ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'No folder found in extracted zip' );
        return;
    }
    
    $source_folder = $extracted_folders[0];
    $plugin_dir    = WP_PLUGIN_DIR . '/' . $correct_folder_name;
    
    $was_active = is_plugin_active( $plugin_file );
    if ( $was_active ) {
        deactivate_plugins( $plugin_file, true );
    }
    
    if ( is_dir( $plugin_dir ) ) {
        $wp_filesystem->delete( $plugin_dir, true );
    }
    
    $move_result = $wp_filesystem->move( $source_folder, $plugin_dir );
    if ( ! $move_result ) {
        $copy_result = copy_dir( $source_folder, $plugin_dir );
        if ( is_wp_error( $copy_result ) ) {
            smp_delete_directory( $temp_dir );
            wp_send_json_error( 'Failed to install plugin: ' . $copy_result->get_error_message() );
            return;
        }
    }
    
    smp_delete_directory( $temp_dir );
    
    if ( $was_active ) {
        $activate_result = activate_plugin( $plugin_file );
        if ( is_wp_error( $activate_result ) ) {
            wp_send_json_success( [
                'message' => 'Plugin updated but failed to reactivate: ' . $activate_result->get_error_message(),
                'reload'  => true,
            ] );
            return;
        }
    }
    
    delete_site_transient( 'update_plugins' );
    delete_site_transient( 'smp_github_ver_' . md5( $github_repo . $github_branch ) );
    
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $new_plugin_data = get_plugin_data( $plugin_dir . '/' . Config::$plugin_starter_file );
    
    wp_send_json_success( [
        'message'     => 'Plugin updated successfully to v' . $new_plugin_data['Version'],
        'new_version' => $new_plugin_data['Version'],
        'reload'      => true,
    ] );
}

/**
 * AJAX: Download current plugin as zip
 */
function ajax_download_plugin_zip() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
        return;
    }
    
    $github_repo         = Config::$github_repo;
    $github_branch       = Config::$github_branch;
    $correct_folder_name = Config::$plugin_folder_name;
    
    $download_url = 'https://github.com/' . $github_repo . '/archive/refs/heads/' . $github_branch . '.zip';
    
    $upload_dir = wp_upload_dir();
    $temp_dir   = $upload_dir['basedir'] . '/smp-temp-' . time();
    $temp_zip   = $temp_dir . '/github-download.zip';
    $final_zip  = $upload_dir['basedir'] . '/' . $correct_folder_name . '.zip';
    
    if ( ! wp_mkdir_p( $temp_dir ) ) {
        wp_send_json_error( 'Could not create temp directory' );
        return;
    }
    
    $response = wp_remote_get( $download_url, [
        'timeout'  => 60,
        'stream'   => true,
        'filename' => $temp_zip,
    ] );
    
    if ( is_wp_error( $response ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Failed to download from GitHub: ' . $response->get_error_message() );
        return;
    }
    
    if ( ! file_exists( $temp_zip ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Download failed - file not created' );
        return;
    }
    
    $extract_dir = $temp_dir . '/extracted';
    wp_mkdir_p( $extract_dir );
    
    $zip = new \ZipArchive();
    if ( $zip->open( $temp_zip ) !== true ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Failed to open downloaded zip file' );
        return;
    }
    
    $zip->extractTo( $extract_dir );
    $zip->close();
    
    $extracted_folders = glob( $extract_dir . '/*', GLOB_ONLYDIR );
    if ( empty( $extracted_folders ) ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'No folder found in extracted zip' );
        return;
    }
    
    $wrong_folder   = $extracted_folders[0];
    $correct_folder = $extract_dir . '/' . $correct_folder_name;
    
    if ( basename( $wrong_folder ) !== $correct_folder_name ) {
        rename( $wrong_folder, $correct_folder );
    } else {
        $correct_folder = $wrong_folder;
    }
    
    $new_zip = new \ZipArchive();
    if ( $new_zip->open( $final_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true ) {
        smp_delete_directory( $temp_dir );
        wp_send_json_error( 'Failed to create new zip file' );
        return;
    }
    
    smp_add_folder_to_zip( $new_zip, $correct_folder, $correct_folder_name );
    $new_zip->close();
    
    smp_delete_directory( $temp_dir );
    
    $final_url = $upload_dir['baseurl'] . '/' . $correct_folder_name . '.zip';
    
    wp_send_json_success( [
        'message' => 'Plugin zip created successfully',
        'url'     => $final_url,
    ] );
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Recursively add folder contents to zip archive
 */
function smp_add_folder_to_zip( $zip, $folder, $base_path ) {
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator( $folder ),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ( $files as $file ) {
        if ( ! $file->isDir() ) {
            $file_path     = $file->getRealPath();
            $relative_path = $base_path . '/' . substr( $file_path, strlen( $folder ) + 1 );
            $zip->addFile( $file_path, $relative_path );
        }
    }
}

/**
 * Recursively delete directory and all contents
 */
function smp_delete_directory( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return;
    }
    
    $files = array_diff( scandir( $dir ), [ '.', '..' ] );
    foreach ( $files as $file ) {
        $path = $dir . '/' . $file;
        is_dir( $path ) ? smp_delete_directory( $path ) : unlink( $path );
    }
    
    rmdir( $dir );
}

/**
 * Get the latest version from GitHub repository (with caching)
 */
function smp_get_github_version( $repo, $branch = 'main' ) {
    $transient_key = 'smp_github_ver_' . md5( $repo . $branch );
    $cached = get_site_transient( $transient_key );
    
    if ( $cached !== false ) {
        return $cached;
    }
    
    $url = 'https://raw.githubusercontent.com/' . $repo . '/' . $branch . '/' . Config::$plugin_starter_file;
    
    $response = wp_remote_get( $url, [
        'timeout'   => 10,
        'sslverify' => true,
    ] );
    
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return false;
    }
    
    $body = wp_remote_retrieve_body( $response );
    if ( preg_match( '/^[\s\*]*Version:\s*(.+)$/mi', $body, $matches ) ) {
        $version = trim( $matches[1] );
        set_site_transient( $transient_key, $version, 30 * MINUTE_IN_SECONDS );
        return $version;
    }
    
    return false;
}

/**
 * Get plugin data from main plugin file
 */
function smp_get_plugin_data() {
    $plugin_file = __DIR__ . '/' . Config::$plugin_starter_file;
    
    if ( ! file_exists( $plugin_file ) || ! is_file( $plugin_file ) || ! is_readable( $plugin_file ) ) {
        return [
            'Name'      => 'Not Available',
            'Version'   => 'Not Available',
            'PluginURI' => 'Not Available',
            'Author'    => 'Not Available',
            'AuthorURI' => 'Not Available',
        ];
    }

    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugin_data = get_plugin_data( $plugin_file );

    foreach ( $plugin_data as $key => $value ) {
        if ( empty( $value ) ) {
            $plugin_data[ $key ] = 'Not Available';
        }
    }

    return $plugin_data;
}

// ============================================================================
// DISPLAY PLUGIN INFO PANEL
// ============================================================================

/**
 * Display the plugin info panel on the settings page
 */
function display_plugin_info() {
    if ( class_exists( '\\Hexa\\PluginCore\\PluginUpdates\\UpdaterPanelRenderer' )
        && function_exists( __NAMESPACE__ . '\\smp_vp_updater_config' ) ) {
        $config = smp_vp_updater_config();

        if ( $config instanceof \Hexa\PluginCore\PluginUpdates\UpdaterConfig ) {
            ( new \Hexa\PluginCore\PluginUpdates\UpdaterPanelRenderer( $config ) )->render();
            return;
        }
    }

    $plugin_data = smp_get_plugin_data();
    ?>
    <div class="panel">
        <h2 class="panel-title"><?php echo esc_html( Config::$plugin_name ); ?> - Plugin Info</h2>
        <div class="panel-content">
            <p><strong>Plugin Name:</strong> <?php echo esc_html( $plugin_data['Name'] ); ?></p>
            <p><strong>Plugin Slug:</strong> <?php echo esc_html( Config::$plugin_folder_name ); ?></p>
            <p><strong>Current Version:</strong> <?php echo esc_html( $plugin_data['Version'] ); ?></p>
            <p><strong>GitHub URL:</strong> <a href="https://github.com/<?php echo esc_attr( Config::$github_repo ); ?>" target="_blank" rel="noopener">https://github.com/<?php echo esc_html( Config::$github_repo ); ?></a></p>
            <p><?php esc_html_e( 'Hexa updater controls are unavailable because the Hexa WordPress Plugin Core did not load.', 'smp_verified_profiles' ); ?></p>
        </div>
    </div>
    <?php
}
