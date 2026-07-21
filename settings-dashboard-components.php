<?php
/**
 * SMP Verified Profiles - Reusable Dashboard Components
 * 
 * Contains abstract UI components used throughout the plugin:
 * - Toggle switches
 * - Panel containers
 * - Status cards
 * - Styled tables
 * - Quick links
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
 * Render a toggle switch
 * 
 * @param string $id         Unique identifier for the toggle
 * @param string $label      Optional label text to display next to toggle
 * @param bool   $checked    Whether the toggle should be checked/on
 * @param string $onclick    JavaScript onclick handler function call
 * @param string $extra_class Additional CSS class(es) to add
 * @return string HTML markup for the toggle switch
 */
function render_toggle_switch( $id, $label = '', $checked = false, $onclick = '', $extra_class = '' ) {
    // Build checked attribute if enabled
    $checked_attr = $checked ? 'checked' : '';
    
    // Build onclick attribute if provided
    $onclick_attr = $onclick ? ' onclick="' . esc_attr( $onclick ) . '"' : '';
    
    // Build the toggle HTML
    $html = '<label class="smp-toggle-switch ' . esc_attr( $extra_class ) . '">';
    $html .= '<input type="checkbox" id="' . esc_attr( $id ) . '" ' . $checked_attr . $onclick_attr . '>';
    $html .= '<span class="smp-toggle-slider"></span>';
    
    // Add optional label
    if ( $label ) {
        $html .= '<span class="smp-toggle-label">' . esc_html( $label ) . '</span>';
    }
    
    $html .= '</label>';
    
    return $html;
}

/**
 * Output all dashboard styles
 * Called once at the top of the main dashboard page
 */
function output_dashboard_styles() {
    ?>
    <style>
        /* ============================================
           SMP Dashboard Global Styles
           ============================================ */
        #smp-dashboard { max-width: 1400px; }
        #smp-dashboard * { box-sizing: border-box; }
        
        /* ============================================
           Toggle Switch
           ============================================ */
        .smp-toggle-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .smp-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .smp-toggle-slider {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            background-color: #ccc;
            border-radius: 24px;
            transition: background-color 0.3s ease;
        }
        .smp-toggle-slider::before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            background-color: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .smp-toggle-switch input:checked + .smp-toggle-slider {
            background-color: #00a32a;
        }
        .smp-toggle-switch input:checked + .smp-toggle-slider::before {
            transform: translateX(20px);
        }
        .smp-toggle-switch input:focus + .smp-toggle-slider {
            box-shadow: 0 0 0 2px rgba(0, 163, 42, 0.2);
        }
        .smp-toggle-label {
            margin-left: 10px;
            font-size: 13px;
            color: #1d2327;
        }
        
        /* ============================================
           Panels
           ============================================ */
        .smp-panel {
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: #fff;
        }
        .smp-panel-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f9f9f9;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px 6px 0 0;
        }
        .smp-panel-body { 
            padding: 20px; 
        }
        
        /* ============================================
           Status Cards Grid
           ============================================ */
        .smp-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .smp-status-card {
            background: #f6f7f7;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            border-left: 4px solid #c3c4c7;
        }
        .smp-status-card.good { border-left-color: #00a32a; }
        .smp-status-card.bad { border-left-color: #d63638; }
        .smp-status-card.warn { border-left-color: #dba617; }
        .smp-status-card .value { 
            font-size: 20px; 
            font-weight: 600; 
            color: #1d2327; 
        }
        .smp-status-card .label { 
            font-size: 11px; 
            color: #646970; 
            text-transform: uppercase; 
            margin-top: 4px; 
        }
        
        /* ============================================
           Snippet Items
           ============================================ */
        .smp-snippet-item {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            margin-bottom: 10px;
            background: #fff;
            border: 1px solid #dcdcdc;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .smp-snippet-item:hover {
            border-color: #2271b1;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .smp-snippet-item.deprecated {
            opacity: 0.6;
            background: #fcf0f1;
        }
        .smp-snippet-toggle {
            flex-shrink: 0;
            margin-right: 15px;
            margin-top: 2px;
        }
        .smp-snippet-content { 
            flex: 1; 
            min-width: 0; 
        }
        .smp-snippet-header {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 4px;
        }
        .smp-snippet-id {
            background: #e7e8ea;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            color: #1e1e1e;
        }
        .smp-snippet-name {
            font-weight: 600;
            font-size: 14px;
            color: #1d2327;
        }
        .smp-snippet-description {
            font-size: 13px;
            color: #646970;
            margin-top: 4px;
        }
        .smp-snippet-category {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .smp-snippet-category.acf { background: #e0e7ff; color: #3b5998; }
        .smp-snippet-category.admin { background: #fef3c7; color: #92400e; }
        .smp-snippet-category.frontend { background: #d1fae5; color: #065f46; }
        
        .smp-snippet-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .smp-snippet-badge.deprecated {
            background: #d63638;
            color: #fff;
        }
        
        /* ============================================
           Tables
           ============================================ */
        .smp-table {
            width: 100%;
            border-collapse: collapse;
        }
        .smp-table th,
        .smp-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .smp-table th { 
            background: #f9f9f9; 
            font-weight: 600; 
        }
        .smp-table tr:hover { 
            background: #f9f9f9; 
        }
        
        /* ============================================
           Status Indicators
           ============================================ */
        .status-ok { color: #00a32a; }
        .status-bad { color: #d63638; }
        .status-warn { color: #dba617; }
        
        /* ============================================
           Quick Links
           ============================================ */
        .smp-quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        .smp-quick-links a,
        .smp-quick-links button {
            display: inline-block;
            padding: 8px 15px;
            background: #f0f0f1;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            text-decoration: none;
            color: #2271b1;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .smp-quick-links a:hover,
        .smp-quick-links button:hover {
            background: #2271b1;
            color: #fff;
            border-color: #2271b1;
        }
        
        /* ============================================
           Buttons
           ============================================ */
        .smp-btn {
            display: inline-block;
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .smp-btn-primary {
            background: #2271b1;
            color: #fff;
        }
        .smp-btn-primary:hover { 
            background: #135e96; 
            color: #fff; 
        }
        .smp-btn-secondary {
            background: #f0f0f1;
            color: #2271b1;
            border: 1px solid #c3c4c7;
        }
        .smp-btn-secondary:hover { 
            background: #e0e0e0; 
        }
        .smp-btn-success {
            background: #00a32a;
            color: #fff;
        }
        .smp-btn-success:hover { 
            background: #008a20; 
        }
        .smp-btn-danger {
            background: #d63638;
            color: #fff;
        }
        .smp-btn-danger:hover { 
            background: #b32d2e; 
        }
        
        /* ============================================
           Info Boxes
           ============================================ */
        .smp-info-box {
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-left: 4px solid #2271b1;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        .smp-info-box.warning {
            background: #fcf9e8;
            border-left-color: #dba617;
        }
        .smp-info-box.success {
            background: #edfaef;
            border-left-color: #00a32a;
        }
        .smp-info-box.error {
            background: #fcf0f1;
            border-left-color: #d63638;
        }
        
        /* ============================================
           Overview Stats Row
           ============================================ */
        .smp-stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .smp-stat-box {
            flex: 1;
            min-width: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .smp-stat-box.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .smp-stat-box.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .smp-stat-box.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .smp-stat-box .stat-value {
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
        }
        .smp-stat-box .stat-label {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 8px;
        }
    </style>
    <?php
}

/**
 * Render a panel container
 * 
 * @param string $title   Panel header title
 * @param string $content Panel body content (HTML)
 * @param string $id      Optional element ID
 */
function render_panel( $title, $content, $id = '' ) {
    $id_attr = $id ? ' id="' . esc_attr( $id ) . '"' : '';
    ?>
    <div class="smp-panel"<?php echo $id_attr; ?>>
        <div class="smp-panel-header"><?php echo esc_html( $title ); ?></div>
        <div class="smp-panel-body"><?php echo $content; ?></div>
    </div>
    <?php
}

/**
 * Render a status card
 * 
 * @param string $value  Display value (e.g., number, checkmark)
 * @param string $label  Card label text
 * @param string $status Status class: 'good', 'bad', 'warn', or empty
 */
function render_status_card( $value, $label, $status = '' ) {
    $class = $status ? ' ' . esc_attr( $status ) : '';
    ?>
    <div class="smp-status-card<?php echo $class; ?>">
        <div class="value"><?php echo esc_html( $value ); ?></div>
        <div class="label"><?php echo esc_html( $label ); ?></div>
    </div>
    <?php
}

/**
 * Render a stat box for overview
 * 
 * @param string $value Value to display
 * @param string $label Label text
 * @param string $color Color class: 'green', 'orange', 'blue', or empty for default purple
 */
function render_stat_box( $value, $label, $color = '' ) {
    $class = $color ? ' ' . esc_attr( $color ) : '';
    ?>
    <div class="smp-stat-box<?php echo $class; ?>">
        <div class="stat-value"><?php echo esc_html( $value ); ?></div>
        <div class="stat-label"><?php echo esc_html( $label ); ?></div>
    </div>
    <?php
}
