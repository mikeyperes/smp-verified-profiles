<?php
/**
 * SMP Verified Profiles - Overview Dashboard Tab
 * 
 * Displays:
 * - Quick stats (profiles, users, snippets)
 * - System status cards
 * - Quick links to common actions
 * - ACF field group status
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
 * Get profile statistics
 * 
 * @return array Profile counts and stats
 */
function get_profile_stats() {
    $stats = [
        'total'     => 0,
        'published' => 0,
        'draft'     => 0,
        'verified'  => 0,
    ];
    
    // Check if profile post type exists
    if ( ! post_type_exists( 'profile' ) ) {
        return $stats;
    }
    
    // Get post counts
    $counts = wp_count_posts( 'profile' );
    $stats['published'] = isset( $counts->publish ) ? (int) $counts->publish : 0;
    $stats['draft']     = isset( $counts->draft ) ? (int) $counts->draft : 0;
    $stats['total']     = $stats['published'] + $stats['draft'];
    
    // Count verified profiles (if ACF field exists)
    if ( function_exists( 'get_field' ) ) {
        $verified_query = new \WP_Query([
            'post_type'      => 'profile',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => 'is_verified',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
        ]);
        $stats['verified'] = $verified_query->found_posts;
    }
    
    return $stats;
}

/**
 * Get user statistics
 * 
 * @return array User counts
 */
function get_user_stats() {
    $stats = [
        'total'            => 0,
        'profile_managers' => 0,
        'admins'           => 0,
    ];
    
    // Total users
    $stats['total'] = count_users()['total_users'];
    
    // Profile managers (custom role or capability)
    $profile_managers = get_users([
        'role'   => 'profile_manager',
        'fields' => 'ID',
    ]);
    $stats['profile_managers'] = count( $profile_managers );
    
    // Admins
    $admins = get_users([
        'role'   => 'administrator',
        'fields' => 'ID',
    ]);
    $stats['admins'] = count( $admins );
    
    return $stats;
}

/**
 * Get snippet statistics
 * 
 * @return array Snippet counts
 */
function get_snippet_stats() {
    $snippets_acf       = get_snippets( 'acf' );
    $snippets_admin     = get_snippets( 'admin' );
    $snippets_non_admin = get_snippets( 'non_admin' );
    $all_snippets       = array_merge( $snippets_acf, $snippets_admin, $snippets_non_admin );
    
    $enabled = 0;
    foreach ( $all_snippets as $snippet ) {
        if ( get_option( $snippet['id'], false ) ) {
            $enabled++;
        }
    }
    
    return [
        'total'   => count( $all_snippets ),
        'enabled' => $enabled,
    ];
}

/**
 * Check system requirements
 * 
 * @return array System check results
 */
function get_system_checks() {
    $checks = [];
    
    // ACF Pro check
    $acf_active = class_exists( 'ACF' ) || function_exists( 'acf_add_local_field_group' );
    $checks['acf'] = [
        'label'  => 'ACF Pro',
        'status' => $acf_active ? 'good' : 'bad',
        'value'  => $acf_active ? '✓' : '✗',
    ];
    
    // Profile CPT check
    $profile_cpt = post_type_exists( 'profile' );
    $checks['profile_cpt'] = [
        'label'  => 'Profile CPT',
        'status' => $profile_cpt ? 'good' : 'warn',
        'value'  => $profile_cpt ? '✓' : '⚠',
    ];
    
    // PHP Version
    $php_ok = version_compare( PHP_VERSION, '7.4', '>=' );
    $checks['php'] = [
        'label'  => 'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
        'status' => $php_ok ? 'good' : 'bad',
        'value'  => $php_ok ? '✓' : '✗',
    ];
    
    // WordPress Version
    global $wp_version;
    $wp_ok = version_compare( $wp_version, '5.0', '>=' );
    $checks['wp'] = [
        'label'  => 'WP ' . $wp_version,
        'status' => $wp_ok ? 'good' : 'bad',
        'value'  => $wp_ok ? '✓' : '✗',
    ];
    
    // Debug Mode
    $debug_on = defined( 'WP_DEBUG' ) && WP_DEBUG;
    $checks['debug'] = [
        'label'  => 'Debug Mode',
        'status' => $debug_on ? 'warn' : 'good',
        'value'  => $debug_on ? 'ON' : 'OFF',
    ];
    
    return $checks;
}

/**
 * Display the Overview tab content
 */
function display_settings_overview() {
    // Get all stats
    $profile_stats = get_profile_stats();
    $user_stats    = get_user_stats();
    $snippet_stats = get_snippet_stats();
    $system_checks = get_system_checks();
    
    // Plugin data
    $plugin_data = smp_get_plugin_data();
    ?>
    
    <!-- Welcome Header -->
    <div class="smp-info-box success" style="margin-top: 0;">
        <strong>👋 Welcome to <?php echo esc_html( Config::$plugin_name ); ?></strong>
        <p style="margin: 5px 0 0;">Manage verified profiles, configure snippets, and monitor system health from this dashboard.</p>
    </div>
    
    <!-- Quick Stats Row -->
    <div class="smp-stats-row">
        <?php
        render_stat_box( $profile_stats['published'], 'Published Profiles', 'green' );
        render_stat_box( $profile_stats['verified'], 'Verified Profiles', 'blue' );
        render_stat_box( $snippet_stats['enabled'] . '/' . $snippet_stats['total'], 'Active Snippets', 'orange' );
        render_stat_box( $user_stats['profile_managers'], 'Profile Managers' );
        ?>
    </div>
    
    <!-- System Status Cards -->
    <div class="smp-panel">
        <div class="smp-panel-header">🔍 System Status</div>
        <div class="smp-panel-body">
            <div class="smp-status-grid">
                <?php foreach ( $system_checks as $check ) : ?>
                    <?php render_status_card( $check['value'], $check['label'], $check['status'] ); ?>
                <?php endforeach; ?>
            </div>
            
            <?php if ( ! $system_checks['acf']['status'] === 'good' ) : ?>
                <div class="smp-info-box error">
                    <strong>⚠️ ACF Pro Required</strong>
                    <p>This plugin requires Advanced Custom Fields Pro to function properly.</p>
                </div>
            <?php endif; ?>
            
            <?php if ( $system_checks['profile_cpt']['status'] !== 'good' ) : ?>
                <div class="smp-info-box warning">
                    <strong>⚠️ Profile Post Type Not Registered</strong>
                    <p>Enable the "register_profile_custom_post_type" snippet in the Snippets tab to register the Profile post type.</p>
                    <a href="<?php echo esc_url( smp_vp_settings_tab_url( 'snippets' ) ); ?>" class="smp-btn smp-btn-primary" style="margin-top: 10px;">Go to Snippets</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="smp-panel">
        <div class="smp-panel-header">🔗 Quick Links</div>
        <div class="smp-panel-body">
            <div class="smp-quick-links">
                <?php if ( post_type_exists( 'profile' ) ) : ?>
                    <a href="<?php echo admin_url( 'edit.php?post_type=profile' ); ?>">📋 All Profiles</a>
                    <a href="<?php echo admin_url( 'post-new.php?post_type=profile' ); ?>">➕ Add New Profile</a>
                <?php endif; ?>
                <a href="<?php echo admin_url( 'users.php' ); ?>">👥 Manage Users</a>
                <a href="<?php echo esc_url( smp_vp_settings_tab_url( 'snippets' ) ); ?>">✂️ Configure Snippets</a>
                <a href="<?php echo esc_url( smp_vp_settings_tab_url( 'system-checks' ) ); ?>">🔍 System Checks</a>
                <a href="https://github.com/<?php echo esc_attr( Config::$github_repo ); ?>" target="_blank">📚 Documentation</a>
            </div>
        </div>
    </div>
    
    <!-- Profile Statistics -->
    <?php if ( post_type_exists( 'profile' ) ) : ?>
    <div class="smp-panel">
        <div class="smp-panel-header">📊 Profile Statistics</div>
        <div class="smp-panel-body">
            <table class="smp-table">
                <tr>
                    <th>Metric</th>
                    <th>Count</th>
                </tr>
                <tr>
                    <td>Total Profiles</td>
                    <td><strong><?php echo esc_html( $profile_stats['total'] ); ?></strong></td>
                </tr>
                <tr>
                    <td>Published</td>
                    <td><span class="status-ok"><?php echo esc_html( $profile_stats['published'] ); ?></span></td>
                </tr>
                <tr>
                    <td>Drafts</td>
                    <td><span class="status-warn"><?php echo esc_html( $profile_stats['draft'] ); ?></span></td>
                </tr>
                <tr>
                    <td>Verified</td>
                    <td><span class="status-ok"><?php echo esc_html( $profile_stats['verified'] ); ?></span></td>
                </tr>
            </table>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url( 'edit.php?post_type=profile' ); ?>" class="smp-btn smp-btn-secondary">View All Profiles</a>
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Enabled Snippets Summary -->
    <div class="smp-panel">
        <div class="smp-panel-header">✂️ Active Snippets Summary</div>
        <div class="smp-panel-body">
            <?php
            $snippets_acf       = get_snippets( 'acf' );
            $snippets_admin     = get_snippets( 'admin' );
            $snippets_non_admin = get_snippets( 'non_admin' );
            $all_snippets       = array_merge( $snippets_acf, $snippets_admin, $snippets_non_admin );
            
            $enabled_snippets = [];
            foreach ( $all_snippets as $snippet ) {
                if ( get_option( $snippet['id'], false ) ) {
                    $enabled_snippets[] = $snippet;
                }
            }
            
            if ( empty( $enabled_snippets ) ) :
            ?>
                <div class="smp-info-box warning">
                    <strong>No snippets enabled</strong>
                    <p>Enable snippets in the Snippets tab to add functionality to your site.</p>
                </div>
            <?php else : ?>
                <p style="margin-bottom: 15px;"><strong><?php echo count( $enabled_snippets ); ?></strong> snippet(s) currently active:</p>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ( $enabled_snippets as $snippet ) : ?>
                        <li><code><?php echo esc_html( $snippet['id'] ); ?></code> — <?php echo esc_html( $snippet['name'] ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo esc_url( smp_vp_settings_tab_url( 'snippets' ) ); ?>" class="smp-btn smp-btn-secondary">Manage Snippets</a>
            </p>
        </div>
    </div>
    
    <?php
}
