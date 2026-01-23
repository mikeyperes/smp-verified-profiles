<?php
/**
 * SMP Verified Profiles - Snippets Dashboard Tab
 * 
 * Displays all available snippets with:
 * - Toggle switches for enabling/disabling
 * - Function/option ID code displayed prominently at the front
 * - Grouped by category (ACF, Admin, Frontend)
 * - Visual feedback on toggle state changes
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
 * Display the Snippets tab content
 * Shows all snippets organized by category with toggle switches
 */
function display_settings_snippets() {
    // Get all snippets from different categories
    $snippets_acf       = get_snippets( 'acf' );
    $snippets_admin     = get_snippets( 'admin' );
    $snippets_non_admin = get_snippets( 'non_admin' );
    
    // Define categories with labels and icons
    $categories = [
        'acf' => [
            'label'    => '📋 ACF Field Groups',
            'desc'     => 'Register Advanced Custom Fields field groups and post types',
            'snippets' => $snippets_acf,
            'class'    => 'acf',
        ],
        'admin' => [
            'label'    => '🔧 Admin Features',
            'desc'     => 'WordPress admin customizations and backend functionality',
            'snippets' => $snippets_admin,
            'class'    => 'admin',
        ],
        'frontend' => [
            'label'    => '🌐 Frontend Features',
            'desc'     => 'Public-facing features, shortcodes, and display options',
            'snippets' => $snippets_non_admin,
            'class'    => 'frontend',
        ],
    ];
    
    // Count enabled snippets
    $all_snippets = array_merge( $snippets_acf, $snippets_admin, $snippets_non_admin );
    $enabled_count = 0;
    foreach ( $all_snippets as $snippet ) {
        if ( get_option( $snippet['id'], false ) ) {
            $enabled_count++;
        }
    }
    ?>
    
    <!-- Snippets Header Panel -->
    <div class="smp-panel">
        <div class="smp-panel-header">Snippets Configuration</div>
        <div class="smp-panel-body">
            <p>Enable or disable plugin features using the toggle switches below. Changes take effect immediately.</p>
            
            <div class="smp-info-box" style="margin-top: 15px;">
                <strong>📊 Status:</strong> 
                <span style="font-size: 16px; font-weight: bold;"><?php echo esc_html( $enabled_count ); ?></span> 
                of <span style="font-size: 16px; font-weight: bold;"><?php echo esc_html( count( $all_snippets ) ); ?></span> 
                snippets currently enabled
            </div>
        </div>
    </div>
    
    <!-- Snippets by Category -->
    <?php foreach ( $categories as $cat_id => $category ) : ?>
        <?php if ( ! empty( $category['snippets'] ) ) : ?>
            <div class="smp-panel">
                <div class="smp-panel-header"><?php echo esc_html( $category['label'] ); ?></div>
                <div class="smp-panel-body">
                    <p style="color: #666; margin-bottom: 20px;"><?php echo esc_html( $category['desc'] ); ?></p>
                    
                    <?php foreach ( $category['snippets'] as $snippet ) : ?>
                        <?php
                        // Get current enabled state
                        $is_enabled = get_option( $snippet['id'], false );
                        
                        // Check for deprecated flag
                        $is_deprecated = isset( $snippet['deprecated'] ) && $snippet['deprecated'];
                        $deprecated_class = $is_deprecated ? ' deprecated' : '';
                        $deprecated_badge = $is_deprecated 
                            ? '<span class="smp-snippet-badge deprecated">Deprecated</span>' 
                            : '';
                        
                        // Handle info field (can be string or callable)
                        $info_text = '';
                        if ( isset( $snippet['info'] ) ) {
                            if ( is_callable( $snippet['info'] ) ) {
                                $info_text = call_user_func( $snippet['info'] );
                            } elseif ( is_string( $snippet['info'] ) ) {
                                $info_text = $snippet['info'];
                            }
                        }
                        
                        // Render toggle switch
                        $toggle_html = render_toggle_switch(
                            'toggle-' . $snippet['id'],
                            '',
                            $is_enabled,
                            'smpVP.toggleSnippet(\'' . esc_js( $snippet['id'] ) . '\')'
                        );
                        ?>
                        
                        <div class="smp-snippet-item<?php echo $deprecated_class; ?>" data-snippet-id="<?php echo esc_attr( $snippet['id'] ); ?>">
                            <!-- Toggle Switch -->
                            <div class="smp-snippet-toggle">
                                <?php echo $toggle_html; ?>
                            </div>
                            
                            <!-- Snippet Content -->
                            <div class="smp-snippet-content">
                                <!-- Header: Code ID first, then name -->
                                <div class="smp-snippet-header">
                                    <code class="smp-snippet-id"><?php echo esc_html( $snippet['id'] ); ?></code>
                                    <span class="smp-snippet-name"><?php echo esc_html( $snippet['name'] ); ?></span>
                                    <span class="smp-snippet-category <?php echo esc_attr( $category['class'] ); ?>"><?php echo esc_html( $cat_id ); ?></span>
                                    <?php echo $deprecated_badge; ?>
                                </div>
                                
                                <!-- Description -->
                                <?php if ( ! empty( $snippet['description'] ) ) : ?>
                                    <div class="smp-snippet-description"><?php echo wp_kses_post( $snippet['description'] ); ?></div>
                                <?php endif; ?>
                                
                                <!-- Additional Info -->
                                <?php if ( $info_text ) : ?>
                                    <div class="smp-snippet-details" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px; font-size: 12px;">
                                        <?php echo wp_kses_post( $info_text ); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Scope indicator for admin-only snippets -->
                                <?php if ( isset( $snippet['scope_admin_only'] ) && $snippet['scope_admin_only'] ) : ?>
                                    <div style="margin-top: 8px;">
                                        <span style="font-size: 11px; color: #666; background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">
                                            🔒 Admin Only
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <!-- Help Panel -->
    <div class="smp-panel">
        <div class="smp-panel-header">💡 Tips</div>
        <div class="smp-panel-body">
            <ul style="margin: 0; padding-left: 20px;">
                <li><strong>ACF snippets</strong> register field groups and should be enabled first if you need custom fields.</li>
                <li><strong>Admin snippets</strong> only run in the WordPress admin area for better performance.</li>
                <li><strong>Frontend snippets</strong> run on public pages and may include shortcodes.</li>
                <li>Changes take effect immediately - no need to save.</li>
                <li>If you encounter issues, try disabling recently enabled snippets.</li>
            </ul>
        </div>
    </div>
    
    <?php
}
