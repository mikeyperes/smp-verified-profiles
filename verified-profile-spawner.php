<?php

namespace smp_verified_profiles;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const SMP_VP_SPAWN_OPTION = 'smp_vp_spawn_settings';
const SMP_VP_SPAWN_NONCE  = 'smp_vp_spawn_nonce';

add_filter( 'smp_vp_dashboard_tabs', __NAMESPACE__ . '\\smp_vp_spawn_dashboard_tab' );
add_filter( 'smp_vp_render_dashboard_tab', __NAMESPACE__ . '\\smp_vp_render_spawn_dashboard_tab', 10, 2 );
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\smp_vp_register_spawn_metabox' );
add_action( 'wp_ajax_smp_vp_spawn_save_settings', __NAMESPACE__ . '\\smp_vp_ajax_spawn_save_settings' );
add_action( 'wp_ajax_smp_vp_spawn_test_api', __NAMESPACE__ . '\\smp_vp_ajax_spawn_test_api' );
add_action( 'wp_ajax_smp_vp_spawn_propose', __NAMESPACE__ . '\\smp_vp_ajax_spawn_propose' );
add_action( 'wp_ajax_smp_vp_spawn_detect_existing', __NAMESPACE__ . '\\smp_vp_ajax_spawn_detect_existing' );
add_action( 'wp_ajax_smp_vp_spawn_profile_state', __NAMESPACE__ . '\\smp_vp_ajax_spawn_profile_state' );
add_action( 'wp_ajax_smp_vp_spawn_approve', __NAMESPACE__ . '\\smp_vp_ajax_spawn_approve' );
add_action( 'wp_ajax_smp_vp_spawn_attach_existing', __NAMESPACE__ . '\\smp_vp_ajax_spawn_attach_existing' );

function smp_vp_spawn_dashboard_tab( array $tabs ): array {
    $tabs['spawning-api'] = 'Spawning API';
    return $tabs;
}

function smp_vp_render_spawn_dashboard_tab( $rendered, string $tab_id ) {
    if ( 'spawning-api' !== $tab_id ) {
        return $rendered;
    }

    smp_vp_spawn_render_settings();
    return true;
}

function smp_vp_spawn_defaults(): array {
    return [
        'enabled'        => true,
        'api_base_url'   => 'https://scalemypublication.com/vp-api/v1',
        'api_key'        => '',
        'default_mode'             => 'filled',
        'default_status'           => 'publish',
        'default_strictness'       => 'strict',
        'default_detection_method' => 'ai',
        'post_types'               => [ 'post', 'press-release' ],
        'last_test'      => [],
    ];
}

function smp_vp_spawn_settings(): array {
    $settings = get_option( SMP_VP_SPAWN_OPTION, [] );
    if ( ! is_array( $settings ) ) {
        $settings = [];
    }
    $settings = array_replace_recursive( smp_vp_spawn_defaults(), $settings );
    if ( 'existing' === (string) ( $settings['default_detection_method'] ?? '' ) ) {
        $settings['default_detection_method'] = 'direct';
    }
    $settings['post_types'] = array_values( array_filter( array_map( 'sanitize_key', (array) $settings['post_types'] ) ) );
    return $settings;
}

function smp_vp_spawn_save_settings( array $input ): array {
    $settings = smp_vp_spawn_settings();
    $post_types = isset( $input['post_types'] ) ? (array) $input['post_types'] : $settings['post_types'];
    $settings['enabled'] = ! empty( $input['enabled'] );
    $settings['api_base_url'] = esc_url_raw( rtrim( (string) ( $input['api_base_url'] ?? $settings['api_base_url'] ), '/' ) );
    $settings['api_key'] = sanitize_text_field( (string) ( $input['api_key'] ?? $settings['api_key'] ) );
    $settings['default_mode'] = 'empty' === (string) ( $input['default_mode'] ?? $settings['default_mode'] ) ? 'empty' : 'filled';
    $strictness = sanitize_key( (string) ( $input['default_strictness'] ?? $settings['default_strictness'] ) );
    $settings['default_strictness'] = in_array( $strictness, [ 'default', 'strict' ], true ) ? $strictness : 'default';
    $method = sanitize_key( (string) ( $input['default_detection_method'] ?? $settings['default_detection_method'] ) );
    if ( 'existing' === $method ) {
        $method = 'direct';
    }
    $settings['default_detection_method'] = in_array( $method, [ 'direct', 'ai' ], true ) ? $method : 'ai';
    $status = sanitize_key( (string) ( $input['default_status'] ?? $settings['default_status'] ) );
    $settings['default_status'] = in_array( $status, [ 'publish', 'draft', 'private', 'pending' ], true ) ? $status : 'publish';
    $settings['post_types'] = array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) );

    update_option( SMP_VP_SPAWN_OPTION, $settings, false );
    return $settings;
}

function smp_vp_dynamic_button( array $args ): string {
    if ( class_exists( '\\Hexa\\PluginCore\\WpAdminComponents\\DynamicButton' ) ) {
        return \Hexa\PluginCore\WpAdminComponents\DynamicButton::render( $args );
    }

    $id = isset( $args['id'] ) ? sanitize_html_class( (string) $args['id'] ) : '';
    $class = trim( (string) ( $args['class'] ?? 'button' ) );
    $label = (string) ( $args['label'] ?? 'Run' );
    $attrs = '';
    foreach ( (array) ( $args['attrs'] ?? [] ) as $name => $value ) {
        $attrs .= ' ' . esc_attr( (string) $name ) . '="' . esc_attr( (string) $value ) . '"';
    }

    return '<button type="button"' . ( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . ' class="' . esc_attr( $class ) . '" data-hpc-dynamic-button' . $attrs . '>' . esc_html( $label ) . '</button>';
}

function smp_vp_spawn_smart_profile_search(): void {
    if ( class_exists( '\\Hexa\\PluginCore\\SmartSearch\\SmartSearchRenderer' ) ) {
        ( new \Hexa\PluginCore\SmartSearch\SmartSearchRenderer() )->render(
            [
                'id'          => 'smp-vp-spawn-manual-search',
                'label'       => 'Find or add profile',
                'placeholder' => 'Type a person or company name',
                'source'      => 'posts',
                'post_type'   => smp_vp_spawn_profile_post_type(),
                'min_chars'   => 2,
                'limit'       => 10,
            ]
        );
        return;
    }

    echo '<label class="smp-vp-spawn-field"><span class="smp-vp-spawn-field-label">Find or add profile</span><input type="text" id="smp-vp-spawn-manual-name" placeholder="Type a person or company name"></label>';
}

function smp_vp_spawn_render_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>';
        return;
    }

    if ( class_exists( '\\Hexa\\PluginCore\\WpAdminComponents\\CoreUi' ) ) {
        \Hexa\PluginCore\WpAdminComponents\CoreUi::render_assets();
    }

    $settings = smp_vp_spawn_settings();
    $nonce = wp_create_nonce( SMP_VP_SPAWN_NONCE );
    $types = get_post_types( [ 'show_ui' => true ], 'objects' );
    ?>
    <style>
        .smp-vp-spawn-card{border:1px solid #dcdcde;background:#fff;border-radius:10px;padding:18px;margin:14px 0;max-width:1120px}
        .smp-vp-spawn-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .smp-vp-spawn-field label{display:block;font-weight:700;margin-bottom:5px;color:#1d2327}
        .smp-vp-spawn-field input[type=text],.smp-vp-spawn-field select{width:100%;max-width:100%;min-height:38px;border:1px solid #c3c4c7;border-radius:6px;padding:6px 9px}
        .smp-vp-spawn-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:15px}
        .smp-vp-spawn-log{margin-top:12px;border:1px solid #dcdcde;border-radius:8px;background:#f6f7f7;padding:10px;min-height:54px;font-family:Menlo,Consolas,monospace;white-space:pre-wrap}
        .smp-vp-spawn-checks{display:flex;gap:14px;flex-wrap:wrap}
        @media(max-width:900px){.smp-vp-spawn-grid{grid-template-columns:1fr}}
    </style>
    <div class="smp-vp-spawn-card hpc-ui" id="smp-vp-spawn-settings">
        <h2>WordPress Verified Profile Spawning</h2>
        <p>Connect this site to the Scale My Publication verified profiles API through the proxy endpoint. The editor box on posts and press releases uses these settings.</p>
        <div class="smp-vp-spawn-grid">
            <div class="smp-vp-spawn-field">
                <label>Proxy API base URL</label>
                <input type="text" id="smp-vp-spawn-api-base" value="<?php echo esc_attr( $settings['api_base_url'] ); ?>">
            </div>
            <div class="smp-vp-spawn-field">
                <label>Site API key</label>
                <input type="text" id="smp-vp-spawn-api-key" value="<?php echo esc_attr( $settings['api_key'] ); ?>">
            </div>
            <div class="smp-vp-spawn-field">
                <label>Default scan mode</label>
                <select id="smp-vp-spawn-default-mode">
                    <option value="filled" <?php selected( $settings['default_mode'], 'filled' ); ?>>Detect and prepare populated profiles</option>
                    <option value="empty" <?php selected( $settings['default_mode'], 'empty' ); ?>>Create empty profile structures</option>
                </select>
            </div>
            <div class="smp-vp-spawn-field">
                <label>Default detection</label>
                <select id="smp-vp-spawn-default-detection-method">
                    <option value="direct" <?php selected( $settings['default_detection_method'], 'direct' ); ?>>Direct article scan</option>
                    <option value="ai" <?php selected( $settings['default_detection_method'], 'ai' ); ?>>Detect with AI</option>
                </select>
            </div>
            <div class="smp-vp-spawn-field">
                <label>Default strictness</label>
                <select id="smp-vp-spawn-default-strictness">
                    <option value="default" <?php selected( $settings['default_strictness'], 'default' ); ?>>Default</option>
                    <option value="strict" <?php selected( $settings['default_strictness'], 'strict' ); ?>>Strict: primary article subjects only</option>
                </select>
            </div>
            <div class="smp-vp-spawn-field">
                <label>Approved profile status</label>
                <select id="smp-vp-spawn-default-status">
                    <?php foreach ( [ 'publish', 'draft', 'private', 'pending' ] as $status ) : ?>
                        <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $settings['default_status'], $status ); ?>><?php echo esc_html( $status ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <p><label><input type="checkbox" id="smp-vp-spawn-enabled" <?php checked( ! empty( $settings['enabled'] ) ); ?>> Enable editor spawning interface</label></p>
        <div class="smp-vp-spawn-field">
            <label>Post types</label>
            <div class="smp-vp-spawn-checks">
                <?php foreach ( $types as $type => $object ) : ?>
                    <?php if ( ! in_array( $type, [ 'post', 'press-release' ], true ) ) { continue; } ?>
                    <label><input type="checkbox" class="smp-vp-spawn-post-type" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $settings['post_types'], true ) ); ?>> <?php echo esc_html( $object->labels->singular_name ?? $type ); ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="smp-vp-spawn-actions">
            <?php
            echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-save', 'label' => 'Save settings', 'working_label' => 'Saving...', 'success_label' => 'Saved', 'error_label' => 'Save failed', 'class' => 'hpc-button' ] );
            echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-test', 'label' => 'Test API connection', 'working_label' => 'Testing...', 'success_label' => 'Connected', 'error_label' => 'Test failed', 'class' => 'hpc-button secondary' ] );
            ?>
        </div>
        <div class="smp-vp-spawn-log" id="smp-vp-spawn-settings-log"><?php echo esc_html( ! empty( $settings['last_test']['message'] ) ? $settings['last_test']['message'] : 'Ready.' ); ?></div>
    </div>
    <script>
    jQuery(function($){
        var nonce = '<?php echo esc_js( $nonce ); ?>';
        var dyn = window.HexaWpCoreDynamicButton || {start:function(b,t){$(b).prop('disabled',true).text(t||'Working...')},success:function(b,t){$(b).prop('disabled',false).text(t||'Done')},error:function(b,t){$(b).prop('disabled',false).text(t||'Failed')},reset:function(b){$(b).prop('disabled',false)}};
        function collect(){
            var postTypes = [];
            $('.smp-vp-spawn-post-type:checked').each(function(){ postTypes.push($(this).val()); });
            return {
                enabled: $('#smp-vp-spawn-enabled').is(':checked') ? 1 : 0,
                api_base_url: $('#smp-vp-spawn-api-base').val(),
                api_key: $('#smp-vp-spawn-api-key').val(),
                default_mode: $('#smp-vp-spawn-default-mode').val(),
                default_detection_method: $('#smp-vp-spawn-default-detection-method').val(),
                default_strictness: $('#smp-vp-spawn-default-strictness').val(),
                default_status: $('#smp-vp-spawn-default-status').val(),
                post_types: postTypes
            };
        }
        function log(message){ $('#smp-vp-spawn-settings-log').text(message || 'Done.'); }
        $('#smp-vp-spawn-save').on('click', function(){
            var btn = this; dyn.start(btn);
            $.post(ajaxurl, {action:'smp_vp_spawn_save_settings', nonce:nonce, settings:collect()})
                .done(function(resp){ var ok=!!(resp&&resp.success); log(ok ? (resp.data.message || 'Settings saved.') : ((resp.data && resp.data.message) || 'Save failed.')); ok ? dyn.success(btn) : dyn.error(btn); })
                .fail(function(){ log('Settings save request failed.'); dyn.error(btn); });
        });
        $('#smp-vp-spawn-test').on('click', function(){
            var btn = this; dyn.start(btn);
            $.post(ajaxurl, {action:'smp_vp_spawn_test_api', nonce:nonce, settings:collect()})
                .done(function(resp){ var ok=!!(resp&&resp.success); log(ok ? (resp.data.message || 'API test passed.') : ((resp.data && resp.data.message) || 'API test failed.')); ok ? dyn.success(btn) : dyn.error(btn); })
                .fail(function(){ log('API test request failed.'); dyn.error(btn); });
        });
    });
    </script>
    <?php
}

function smp_vp_register_spawn_metabox(): void {
    $settings = smp_vp_spawn_settings();
    if ( empty( $settings['enabled'] ) ) {
        return;
    }

    foreach ( (array) $settings['post_types'] as $post_type ) {
        if ( post_type_exists( $post_type ) ) {
            add_meta_box(
                'smp_vp_spawn_profiles',
                'Verified Profiles',
                __NAMESPACE__ . '\\smp_vp_render_spawn_metabox',
                $post_type,
                'normal',
                'high'
            );
        }
    }
}

function smp_vp_render_spawn_metabox( \WP_Post $post ): void {
    $settings = smp_vp_spawn_settings();
    $nonce = wp_create_nonce( SMP_VP_SPAWN_NONCE );
    if ( class_exists( '\\Hexa\\PluginCore\\WpAdminComponents\\CoreUi' ) ) {
        \Hexa\PluginCore\WpAdminComponents\CoreUi::render_assets();
    }
    if ( class_exists( '\\Hexa\\PluginCore\\WpAdminComponents\\DynamicButton' ) ) {
        \Hexa\PluginCore\WpAdminComponents\DynamicButton::render_assets();
    }
    ?>
    <style>
        #smp-vp-spawn-box{--svp-bg:#f6f8fb;--svp-card:#fff;--svp-line:#d9dee8;--svp-line-strong:#b7c0ce;--svp-muted:#5f6876;--svp-text:#182133;--svp-blue:#4059d9;--svp-green:#16803c;--svp-red:#b42336;border:1px solid var(--svp-line-strong);border-radius:12px;background:var(--svp-card);box-shadow:0 1px 2px rgba(24,33,51,.04);overflow:hidden}
        #smp-vp-spawn-box .smp-vp-spawn-head{padding:20px 22px;border-bottom:1px solid var(--svp-line);background:linear-gradient(180deg,#fbfcfe 0%,#f4f6f9 100%)}
        #smp-vp-spawn-box .smp-vp-spawn-head strong{display:block;color:var(--svp-text);font-size:20px;line-height:1.2;margin-bottom:7px}
        #smp-vp-spawn-box .smp-vp-spawn-body{display:grid;gap:18px;padding:22px;background:#fff}
        #smp-vp-spawn-box .smp-vp-spawn-step{border:1px solid var(--svp-line);border-radius:12px;margin:0;padding:20px 22px;background:var(--svp-card);box-shadow:0 1px 0 rgba(24,33,51,.03)}
        #smp-vp-spawn-box .smp-vp-spawn-step h4,#smp-vp-spawn-box .smp-vp-spawn-log-title{color:var(--svp-text);font-size:13px;font-weight:800;letter-spacing:.08em;margin:0 0 10px;text-transform:uppercase}
        #smp-vp-spawn-box .smp-vp-spawn-step p{margin-top:0}
        #smp-vp-spawn-box .smp-vp-spawn-actions{display:flex;gap:12px;flex-wrap:wrap;margin:16px 0 0}
        #smp-vp-spawn-box .smp-vp-spawn-auto{padding:0;margin:0}
        #smp-vp-spawn-box .smp-vp-spawn-auto .hpc-button{min-height:46px;padding-left:22px;padding-right:22px}
        #smp-vp-spawn-box .hpc-button{border-radius:8px;font-weight:800;min-height:42px}
        #smp-vp-spawn-box .smp-vp-spawn-field{display:grid;gap:7px}
        #smp-vp-spawn-box .smp-vp-spawn-field-label{color:var(--svp-text);font-size:13px;font-weight:800}
        #smp-vp-spawn-box input[type="text"],#smp-vp-spawn-box input[type="search"],#smp-vp-spawn-box select{border:1px solid #9aa4b2;border-radius:8px;box-shadow:inset 0 0 0 1px rgba(24,33,51,.02);box-sizing:border-box;color:var(--svp-text);font-size:15px;min-height:44px;padding:8px 12px;width:100%}
        #smp-vp-spawn-box input[type="text"]:focus,#smp-vp-spawn-box input[type="search"]:focus,#smp-vp-spawn-box select:focus{border-color:var(--svp-blue);box-shadow:0 0 0 3px rgba(64,89,217,.14);outline:0}
        #smp-vp-spawn-box select{appearance:auto;background:#fff;font-weight:700;padding-right:38px}
        #smp-vp-spawn-box .smp-vp-spawn-check{align-items:center;background:#f9fbff;border:1px solid var(--svp-line);border-radius:10px;color:var(--svp-text);display:inline-flex;font-weight:800;gap:10px;min-height:44px;padding:0 14px}
        #smp-vp-spawn-box .smp-vp-spawn-check input{margin:0}
        #smp-vp-spawn-box .smp-vp-state-grid{display:grid;gap:12px;grid-template-columns:repeat(2,minmax(0,1fr))}
        #smp-vp-spawn-box .smp-vp-state-list{display:grid;gap:8px;margin-top:10px}
        #smp-vp-spawn-box .smp-vp-state-row{align-items:center;border:1px solid #e1e6ef;border-radius:10px;display:grid;gap:10px;grid-template-columns:minmax(0,1fr) auto;padding:12px;background:#fff}
        #smp-vp-spawn-box .smp-vp-state-row.is-duplicate{background:#fff7e6;border-color:#e0a800}
        #smp-vp-spawn-box .smp-vp-state-title{font-weight:800}
        #smp-vp-spawn-box .smp-vp-state-actions{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
        #smp-vp-spawn-box .smp-vp-state-actions a{font-size:12px}
        #smp-vp-spawn-box .smp-vp-spawn-inline{align-items:end;display:grid;gap:12px;grid-template-columns:minmax(220px,1fr) auto}
        #smp-vp-spawn-box .smp-vp-spawn-inline input,#smp-vp-spawn-box .smp-vp-spawn-inline select{min-height:44px;width:100%}
        #smp-vp-spawn-box .smp-vp-manual-search-wrap{min-width:0}
        #smp-vp-spawn-box .hpc-smart-search{position:relative}
        #smp-vp-spawn-box .hpc-smart-search .hpc-field{display:grid;gap:7px;margin:0}
        #smp-vp-spawn-box .hpc-smart-search .hpc-field span{color:var(--svp-text);font-size:13px;font-weight:800}
        #smp-vp-spawn-box .hpc-smart-search-status{color:var(--svp-muted);font-size:12px;margin-top:7px}
        #smp-vp-spawn-box .hpc-smart-search-results{background:#fff;border:1px solid var(--svp-line-strong);border-radius:10px;box-shadow:0 14px 32px rgba(24,33,51,.14);display:grid;gap:4px;left:0;margin-top:8px;max-height:280px;overflow:auto;padding:6px;position:absolute;right:0;z-index:30}
        #smp-vp-spawn-box .hpc-smart-search-results[hidden]{display:none}
        #smp-vp-spawn-box .hpc-smart-search-result{background:#fff;border:0;border-radius:8px;cursor:pointer;display:grid;gap:2px;padding:10px 12px;text-align:left;width:100%}
        #smp-vp-spawn-box .hpc-smart-search-result.active,#smp-vp-spawn-box .hpc-smart-search-result:hover{background:#eef4ff}
        #smp-vp-spawn-box .hpc-smart-search-result strong{color:var(--svp-text);font-size:13px}
        #smp-vp-spawn-box .hpc-smart-search-result span,#smp-vp-spawn-box .hpc-smart-search-result em{color:var(--svp-muted);font-size:12px;font-style:normal}
        #smp-vp-spawn-box .hpc-smart-search-selected{align-items:center;display:flex;gap:8px;margin-top:8px}
        #smp-vp-spawn-box .hpc-smart-search-selected[hidden]{display:none}
        #smp-vp-spawn-box .smp-vp-manual-intent{background:#f8fafc;border:1px solid #e3e8f0;border-radius:8px;color:var(--svp-muted);font-size:12px;margin-top:8px;padding:9px 10px}
        #smp-vp-spawn-box .smp-vp-manual-intent.is-selected{background:#eaf8ef;border-color:#c7ecd4;color:#176c36;font-weight:700}
        #smp-vp-spawn-box .smp-vp-manual-intent.is-new{background:#fff7e6;border-color:#f5ddac;color:#8a5700;font-weight:700}
        #smp-vp-spawn-box .smp-vp-spawn-toggles{align-items:end;display:grid;gap:14px;grid-template-columns:minmax(180px,max-content) minmax(220px,320px);margin:16px 0}
        #smp-vp-spawn-box .smp-vp-spawn-log-panel{border:1px solid #202b3f;border-radius:12px;background:#111827;box-shadow:0 1px 2px rgba(24,33,51,.08);overflow:hidden}
        #smp-vp-spawn-box .smp-vp-spawn-log-head{align-items:center;background:#172033;border-bottom:1px solid #263349;display:flex;gap:12px;justify-content:space-between;padding:12px 14px}
        #smp-vp-spawn-box .smp-vp-spawn-log-title{color:#e5edf8;margin:0}
        #smp-vp-spawn-box .smp-vp-spawn-log-status{color:#95a3b8;font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
        #smp-vp-spawn-box .smp-vp-spawn-log{width:100%;min-height:168px;border:0;border-radius:0;color:#e5edf8;font-family:Menlo,Consolas,monospace;font-size:13px;line-height:1.55;padding:14px;white-space:pre-wrap;background:#0b1020;box-sizing:border-box;overflow:auto}
        #smp-vp-spawn-box .smp-vp-error-panel{background:#fff5f5;border:1px solid #d63638;border-left:5px solid #b42336;border-radius:10px;color:#1d2327;display:none;margin:0;padding:14px}
        #smp-vp-spawn-box.has-error .smp-vp-error-panel{display:block}
        #smp-vp-spawn-box .smp-vp-error-title{color:#8a1f2d;font-size:14px;font-weight:800;margin-bottom:4px}
        #smp-vp-spawn-box .smp-vp-error-message{font-weight:700;margin-bottom:8px}
        #smp-vp-spawn-box .smp-vp-error-grid{display:grid;grid-template-columns:150px minmax(0,1fr);gap:4px 10px;margin:8px 0}
        #smp-vp-spawn-box .smp-vp-error-grid dt{font-weight:800;color:#646970}
        #smp-vp-spawn-box .smp-vp-error-grid dd{margin:0;word-break:break-word}
        #smp-vp-spawn-box .smp-vp-error-panel pre{background:#fff;border:1px solid #f0c5c7;border-radius:6px;max-height:240px;overflow:auto;padding:8px;white-space:pre-wrap}
        #smp-vp-spawn-box .smp-vp-spawn-step-two,#smp-vp-spawn-box .smp-vp-spawn-results{display:none}
        #smp-vp-spawn-box.has-entities .smp-vp-spawn-step-two,#smp-vp-spawn-box.has-results .smp-vp-spawn-results{display:block}
        #smp-vp-spawn-box .smp-vp-entity{display:grid;grid-template-columns:auto minmax(0,1fr) minmax(190px,240px) auto;gap:12px;align-items:start;border:1px solid var(--svp-line);border-radius:10px;padding:12px;margin:10px 0;background:#fff}
        #smp-vp-spawn-box .smp-vp-entity-title{font-weight:700}
        #smp-vp-spawn-box .smp-vp-entity-meta{color:var(--svp-muted);font-size:12px;margin-top:2px}
        #smp-vp-spawn-box .smp-vp-entity-desc{font-size:12px;color:#3c434a;margin-top:5px}
        #smp-vp-spawn-box .smp-vp-pill{background:#eef4ff;border-radius:999px;color:#24486f;display:inline-block;font-size:11px;font-weight:800;letter-spacing:.04em;margin:5px 6px 0 0;padding:4px 8px;text-transform:uppercase}
        #smp-vp-spawn-box .smp-vp-pill.success{background:#eaf8ef;color:#176c36}
        #smp-vp-spawn-box .smp-vp-pill.warn{background:#fff7e6;color:#8a5700}
        #smp-vp-spawn-box .smp-vp-entity-action label{display:block;font-size:11px;font-weight:800;letter-spacing:.04em;margin-bottom:4px;text-transform:uppercase}
        #smp-vp-spawn-box .smp-vp-spawned-profile{border:1px solid var(--svp-line);border-radius:8px;margin:10px 0;overflow:hidden;background:#fff}
        #smp-vp-spawn-box .smp-vp-spawned-profile-head{align-items:center;background:#f8fafc;border-bottom:1px solid var(--svp-line);display:flex;gap:10px;justify-content:space-between;padding:12px}
        #smp-vp-spawn-box .smp-vp-spawned-profile-title{font-weight:800}
        #smp-vp-spawn-box .smp-vp-spawned-profile-actions{display:flex;gap:8px;flex-wrap:wrap}
        #smp-vp-spawn-box .smp-vp-spawn-report .hpc-section-body{padding:12px}
        #smp-vp-spawn-box .smp-vp-field-report{border-collapse:collapse;width:100%}
        #smp-vp-spawn-box .smp-vp-field-report th,#smp-vp-spawn-box .smp-vp-field-report td{border-bottom:1px solid #edf1f6;padding:7px;text-align:left;vertical-align:top}
        #smp-vp-spawn-box .smp-vp-field-report th{font-size:11px;letter-spacing:.04em;text-transform:uppercase}
        #smp-vp-spawn-box .smp-vp-field-report code{white-space:normal;word-break:break-word}
        #smp-vp-spawn-box .smp-vp-empty{color:var(--svp-muted);font-style:italic}
        @media(max-width:900px){#smp-vp-spawn-box .smp-vp-entity{grid-template-columns:auto minmax(0,1fr)}#smp-vp-spawn-box .smp-vp-entity-action,#smp-vp-spawn-box .smp-vp-entity button{grid-column:2}#smp-vp-spawn-box .smp-vp-spawn-inline,#smp-vp-spawn-box .smp-vp-state-grid,#smp-vp-spawn-box .smp-vp-spawn-toggles{grid-template-columns:1fr}}
    </style>
    <div id="smp-vp-spawn-box" class="hpc-ui" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-post-type="<?php echo esc_attr( $post->post_type ); ?>" data-status="<?php echo esc_attr( $settings['default_status'] ); ?>" data-strictness="<?php echo esc_attr( $settings['default_strictness'] ); ?>" data-detection-method="<?php echo esc_attr( $settings['default_detection_method'] ); ?>">
        <div class="smp-vp-spawn-head">
            <strong>Verified Profiles</strong>
            <div class="smp-vp-entity-meta">Current ACF profiles load automatically. Use Scan to detect article entities, then attach or spawn only what is needed.</div>
        </div>
        <div class="smp-vp-spawn-body">
            <div class="smp-vp-spawn-actions smp-vp-spawn-auto">
                <?php echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-auto-approve', 'label' => 'Scan and auto approve', 'working_label' => 'Scanning...', 'success_label' => 'Auto approved', 'error_label' => 'Auto approve failed', 'class' => 'hpc-button', 'attrs' => [ 'data-smp-vp-action' => 'auto' ] ] ); ?>
            </div>

            <div class="smp-vp-spawn-step smp-vp-spawn-step-one">
                <h4>Step One: Scan Article</h4>
                <p class="smp-vp-entity-meta">Choose the scan method, then run Scan. Direct scan uses article data locally; AI scan sends article text to the configured API.</p>
                <div class="smp-vp-spawn-toggles">
                    <label class="smp-vp-spawn-check"><input type="checkbox" id="smp-vp-spawn-strict" <?php checked( $settings['default_strictness'], 'strict' ); ?>> Strict mode</label>
                    <label class="smp-vp-spawn-field"><span class="smp-vp-spawn-field-label">Scan method</span>
                        <select id="smp-vp-spawn-method">
                            <option value="direct" <?php selected( $settings['default_detection_method'], 'direct' ); ?>>Direct scan</option>
                            <option value="ai" <?php selected( $settings['default_detection_method'], 'ai' ); ?>>Detect with AI</option>
                        </select>
                    </label>
                </div>
                <div class="smp-vp-spawn-actions">
                    <?php
                    echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-scan', 'label' => 'Scan', 'working_label' => 'Scanning...', 'success_label' => 'Scanned', 'error_label' => 'Scan failed', 'class' => 'hpc-button secondary', 'attrs' => [ 'data-smp-vp-action' => 'scan' ] ] );
                    ?>
                </div>
            </div>

            <div class="smp-vp-spawn-step smp-vp-spawn-step-two">
                <h4>Step Two: Review Proposed Profiles</h4>
                <p class="smp-vp-entity-meta">Review matches. Existing profiles attach by default; choose Spawn new entity only when you intentionally need a new profile.</p>
                <div id="smp-vp-spawn-entities"></div>
                <div class="smp-vp-spawn-actions">
                    <?php
                    echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-create-empty', 'label' => 'Create empty structures', 'working_label' => 'Creating...', 'success_label' => 'Created', 'error_label' => 'Create failed', 'class' => 'hpc-button secondary', 'attrs' => [ 'data-smp-vp-action' => 'empty' ], 'disabled' => true ] );
                    echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-approve', 'label' => 'Approve selected', 'working_label' => 'Approving...', 'success_label' => 'Approved', 'error_label' => 'Approve failed', 'class' => 'hpc-button', 'attrs' => [ 'data-smp-vp-action' => 'approve' ], 'disabled' => true ] );
                    echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-select-all', 'label' => 'Select all', 'working_label' => 'Selecting...', 'success_label' => 'Selected', 'error_label' => 'Select failed', 'class' => 'hpc-button secondary', 'attrs' => [ 'data-smp-vp-action' => 'select-all' ], 'disabled' => true ] );
                    echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-clear', 'label' => 'Clear', 'working_label' => 'Clearing...', 'success_label' => 'Cleared', 'error_label' => 'Clear failed', 'class' => 'hpc-button secondary', 'attrs' => [ 'data-smp-vp-action' => 'clear' ], 'disabled' => true ] );
                    ?>
                </div>
            </div>

            <div class="smp-vp-spawn-step smp-vp-spawn-results">
                <h4>Verified Profiles Attached or Spawned</h4>
                <div id="smp-vp-spawn-created"></div>
            </div>

            <div class="smp-vp-error-panel" id="smp-vp-spawn-error" role="alert" aria-live="assertive"></div>
            <div class="smp-vp-spawn-log-panel">
                <div class="smp-vp-spawn-log-head">
                    <h4 class="smp-vp-spawn-log-title">Activity Log</h4>
                    <span class="smp-vp-spawn-log-status">Live status</span>
                </div>
                <div class="smp-vp-spawn-log" id="smp-vp-spawn-log">Ready.</div>
            </div>

            <div class="smp-vp-spawn-step smp-vp-spawn-manual">
                <h4>Manual Add</h4>
                <p class="smp-vp-entity-meta">Search existing verified profiles first. Selecting a match attaches it to this article immediately; leave no selection and use Spawn new profile for a new profile from the typed name.</p>
                <div class="smp-vp-spawn-inline">
                    <div class="smp-vp-manual-search-wrap">
                        <?php smp_vp_spawn_smart_profile_search(); ?>
                        <div id="smp-vp-spawn-manual-intent" class="smp-vp-manual-intent">Type to search existing verified profiles.</div>
                    </div>
                    <?php echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-manual-add', 'label' => 'Spawn new profile', 'working_label' => 'Adding...', 'success_label' => 'Added', 'error_label' => 'Add failed', 'class' => 'hpc-button secondary', 'attrs' => [ 'data-smp-vp-action' => 'manual' ] ] ); ?>
                </div>
            </div>

            <div class="smp-vp-spawn-step smp-vp-spawn-state">
                <h4>Post Profiles</h4>
                <div class="smp-vp-state-grid">
                    <div>
                        <div class="smp-vp-entity-meta">Current profiles attached to this post.</div>
                        <div id="smp-vp-current-profiles" class="smp-vp-state-list"><p class="smp-vp-empty">Loading current profiles...</p></div>
                    </div>
                    <div>
                        <div class="smp-vp-entity-meta">Pending profile names from the existing ACF structure.</div>
                        <div id="smp-vp-pending-profiles" class="smp-vp-state-list"><p class="smp-vp-empty">Loading pending profiles...</p></div>
                    </div>
                </div>
                <div class="smp-vp-spawn-actions">
                    <?php echo smp_vp_dynamic_button( [ 'id' => 'smp-vp-spawn-refresh-state', 'label' => 'Refresh profiles', 'working_label' => 'Refreshing...', 'success_label' => 'Refreshed', 'error_label' => 'Refresh failed', 'class' => 'hpc-button secondary', 'attrs' => [ 'data-smp-vp-action' => 'refresh-state' ] ] ); ?>
                </div>
            </div>
        </div>
        <input type="hidden" id="smp-vp-spawn-nonce" value="<?php echo esc_attr( $nonce ); ?>">
    </div>
    <script>
    jQuery(function($){
        var box = $('#smp-vp-spawn-box');
        var dyn = window.HexaWpCoreDynamicButton || {start:function(b,t){$(b).prop('disabled',true).text(t||'Working...')},success:function(b,t){$(b).prop('disabled',false).text(t||'Done')},error:function(b,t){$(b).prop('disabled',false).text(t||'Failed')},reset:function(b){$(b).prop('disabled',false)},enable:function(b){$(b).prop('disabled',false)},disable:function(b){$(b).prop('disabled',true)}};
        var state = {entities:[], busy:false, profileState:{current_profiles:[], pending_profiles:[]}, manualSelected:null};
        hideLegacyAcfBox();
        function hideLegacyAcfBox(){
            $('.postbox').each(function(){
                var title = ($(this).find('.hndle, .postbox-header h2, h2').first().text() || '').trim();
                if(title === 'Post - Verified Profile - Admin'){
                    $(this).hide().attr('data-smp-vp-hidden-legacy-box', '1');
                }
            });
        }
        function now(){return new Date().toLocaleTimeString([], {hour:'numeric', minute:'2-digit', second:'2-digit'});}
        function log(line){
            var el = $('#smp-vp-spawn-log');
            var current = el.text() === 'Ready.' ? '' : el.text() + "\n";
            el.text(current + '[' + now() + '] ' + line);
        }
        function manualSearchRoot(){ return $('#smp-vp-spawn-manual-search'); }
        function manualSearchInput(){
            var root = manualSearchRoot();
            var input = root.length ? root.find('.hpc-smart-search-input') : $();
            return input.length ? input : $('#smp-vp-spawn-manual-name');
        }
        function manualSearchValue(){ return (manualSearchInput().val() || '').trim(); }
        function setManualSearchValue(value){
            manualSearchInput().val(value || '');
            manualSearchRoot().find('.hpc-smart-search-value').val('');
        }
        function setManualButtonLabel(label){
            var btn = document.getElementById('smp-vp-spawn-manual-add');
            if(!btn) return;
            btn.dataset.defaultLabel = label;
            var node = btn.querySelector('.hpc-dynamic-button-label');
            if(node){ node.textContent = label; } else { btn.textContent = label; }
        }
        function setManualIntent(type, text){
            var intent = $('#smp-vp-spawn-manual-intent');
            intent.removeClass('is-selected is-new').addClass(type ? 'is-' + type : '').text(text);
            setManualButtonLabel(type === 'selected' ? 'Attach selected profile' : 'Spawn new profile');
        }
        function clearManualSelection(message){
            state.manualSelected = null;
            manualSearchRoot().find('.hpc-smart-search-selected').prop('hidden', true).empty();
            manualSearchRoot().find('.hpc-smart-search-value').val('');
            setManualIntent('', message || 'Type to search existing verified profiles.');
        }
        function entityFromSelectedProfile(item){
            return {
                name: item.name || item.label || ('Profile #' + item.id),
                entity_type: 'entity',
                confidence: 1,
                description: 'Selected from existing WordPress verified profiles.',
                source: 'manual',
                resolution: 'attach_existing',
                existing_profile_id: parseInt(item.id || item.value || 0, 10),
                existing_profile_title: item.name || item.label || '',
                existing_backend_url: item.edit_url || item.url || '',
                existing_frontend_url: item.view_url || '',
                match_type: 'manual_wp_search'
            };
        }
        function attachManualSelectedProfile(item, btn){
            var profileId = parseInt((item && (item.id || item.value)) || 0, 10);
            if(!profileId){
                if(btn){ dyn.error(btn, 'No profile'); }
                setManualIntent('', 'Select an existing verified profile result, or type a new name to spawn.');
                return $.Deferred().reject().promise();
            }
            clearError();
            if(btn){ dyn.start(btn, 'Attaching...'); }
            log('Attaching existing verified profile "' + (item.name || item.label || ('#' + profileId)) + '" to this post...');
            return $.post(ajaxurl, {
                action: 'smp_vp_spawn_attach_existing',
                nonce: $('#smp-vp-spawn-nonce').val(),
                post_id: box.data('post-id'),
                profile_id: profileId
            }).done(function(resp){
                if(!resp || !resp.success){
                    showError(resp, 'Manual profile attach failed.', 'Manual attach');
                    if(btn){ dyn.error(btn); }
                    return;
                }
                var data = resp.data || {};
                log(data.message || 'Existing verified profile attached.');
                if(data.created_profiles){ renderCreated(data.created_profiles); }
                if(data.state){ renderProfileState(data.state); } else { loadProfileState(); }
                setManualSearchValue('');
                clearManualSelection('Type to search existing verified profiles.');
                if(btn){ dyn.success(btn, data.already_attached ? 'Already attached' : 'Attached'); }
            }).fail(function(resp){
                showError(resp, 'Manual profile attach request failed.', 'Manual attach');
                if(btn){ dyn.error(btn); }
            });
        }
        function entityFromManualName(name){
            return {
                name: name,
                entity_type: 'entity',
                confidence: 0.65,
                description: 'Manually entered name with no selected local profile match.',
                source: 'manual',
                resolution: 'spawn_new'
            };
        }
        function strictness(){ return $('#smp-vp-spawn-strict').is(':checked') ? 'strict' : 'default'; }
        function setBusy(busy, activeButton){
            state.busy = !!busy;
            $('[data-smp-vp-action]').each(function(){
                if (this === activeButton) return;
                if (busy) dyn.disable(this); else dyn.enable(this);
            });
            refreshButtons();
        }
        function refreshButtons(){
            var has = state.entities.length > 0 && !state.busy;
            ['#smp-vp-spawn-create-empty','#smp-vp-spawn-approve','#smp-vp-spawn-select-all','#smp-vp-spawn-clear'].forEach(function(sel){
                var b = document.querySelector(sel);
                if(!b) return;
                if(has) dyn.enable(b); else dyn.disable(b);
            });
        }
        function editedContent(){
            try {
                if (window.wp && wp.data && wp.data.select) {
                    var content = wp.data.select('core/editor').getEditedPostContent();
                    if (typeof content === 'string') return content;
                }
            } catch(e){}
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                return tinyMCE.activeEditor.getContent();
            }
            return $('#content').val() || '';
        }
        function editedTitle(){
            try {
                if (window.wp && wp.data && wp.data.select) {
                    var title = wp.data.select('core/editor').getEditedPostAttribute('title');
                    if (typeof title === 'string') return title;
                }
            } catch(e){}
            return $('#title').val() || '';
        }
        function extractLinks(html){
            var node = document.createElement('div');
            node.innerHTML = html || '';
            return Array.prototype.slice.call(node.querySelectorAll('a')).map(function(a){
                return {text:(a.textContent || '').trim(), url:a.getAttribute('href') || ''};
            }).filter(function(row){ return row.text || row.url; });
        }
        function payload(mode, extra){
            var content = editedContent();
            return Object.assign({
                nonce: $('#smp-vp-spawn-nonce').val(),
                post_id: box.data('post-id'),
                post_type: box.data('post-type'),
                mode: mode || 'filled',
                strictness: strictness(),
                status: box.data('status') || 'publish',
                title: editedTitle(),
                url: $('#sample-permalink a').attr('href') || '',
                content: content,
                links: extractLinks(content)
            }, extra || {});
        }
        function mergeEntities(items){
            var seen = {};
            state.entities.forEach(function(entity){ seen[(entity.name || '').toLowerCase()] = true; });
            (items || []).forEach(function(entity){
                var key = (entity.name || '').toLowerCase();
                if(!key || seen[key]) return;
                seen[key] = true;
                state.entities.push(entity);
            });
            renderEntities();
        }
        function renderProfileState(data){
            state.profileState = data || {current_profiles:[], pending_profiles:[]};
            var current = $('#smp-vp-current-profiles').empty();
            var pending = $('#smp-vp-pending-profiles').empty();
            if(!(state.profileState.current_profiles || []).length){
                current.append('<p class="smp-vp-empty">No profiles attached yet.</p>');
            }
            (state.profileState.current_profiles || []).forEach(function(profile){
                var row = $('<div class="smp-vp-state-row">').toggleClass('is-duplicate', !!profile.duplicate);
                var body = $('<div>').append($('<div class="smp-vp-state-title">').text(profile.title || ('Profile #' + profile.profile_id)));
                body.append($('<div class="smp-vp-entity-meta">').text('ID ' + profile.profile_id + ' - ' + (profile.post_status || 'unknown') + (profile.duplicate ? ' - duplicate row' : '')));
                var actions = $('<div class="smp-vp-state-actions">');
                if(profile.frontend_url){ actions.append($('<a class="hpc-button secondary" target="_blank" rel="noopener">View live</a>').attr('href', profile.frontend_url)); }
                if(profile.backend_url){ actions.append($('<a class="hpc-button secondary" target="_blank" rel="noopener">Edit backend</a>').attr('href', profile.backend_url)); }
                row.append(body).append(actions);
                current.append(row);
            });
            if(!(state.profileState.pending_profiles || []).length){
                pending.append('<p class="smp-vp-empty">No pending profile names.</p>');
            }
            (state.profileState.pending_profiles || []).forEach(function(item){
                var row = $('<div class="smp-vp-state-row">');
                var body = $('<div>').append($('<div class="smp-vp-state-title">').text(item.name || 'Untitled pending profile'));
                body.append($('<div class="smp-vp-entity-meta">').text([item.type, item.url].filter(Boolean).join(' - ') || 'Pending'));
                var actions = $('<div class="smp-vp-state-actions">');
                actions.append($('<button type="button" class="hpc-button secondary">Add to review</button>').on('click', function(){
                    setManualSearchValue(item.name || '');
                    clearManualSelection('Pending name loaded. Select an existing match or spawn it as new.');
                }));
                row.append(body).append(actions);
                pending.append(row);
            });
        }
        function loadProfileState(btn){
            if(btn){ dyn.start(btn); }
            return $.post(ajaxurl, {action:'smp_vp_spawn_profile_state', nonce:$('#smp-vp-spawn-nonce').val(), post_id:box.data('post-id')})
                .done(function(resp){
                    if(resp && resp.success){ renderProfileState(resp.data || {}); if(btn){ dyn.success(btn); } }
                    else { if(btn){ dyn.error(btn); } }
                })
                .fail(function(){ if(btn){ dyn.error(btn); } });
        }
        function renderEntities(){
            var wrap = $('#smp-vp-spawn-entities').empty();
            state.entities.forEach(function(entity, index){
                var existingId = parseInt(entity.existing_profile_id || 0, 10);
                var row = $('<div class="smp-vp-entity">');
                row.append($('<input type="checkbox" class="smp-vp-spawn-entity-check">').attr('data-index', index).prop('checked', true));
                var body = $('<div>');
                body.append($('<div class="smp-vp-entity-title">').text(entity.name || 'Untitled entity'));
                body.append($('<div class="smp-vp-entity-meta">').text((entity.entity_type || entity.type || 'entity') + ' - confidence ' + Math.round(Number(entity.confidence || 0) * 100) + '% - ' + (entity.source || 'scan')));
                if(existingId){
                    body.append($('<span class="smp-vp-pill success">').text('Existing profile #' + existingId));
                    if(entity.existing_backend_url){ body.append($('<a class="smp-vp-pill" target="_blank" rel="noopener">Edit existing</a>').attr('href', entity.existing_backend_url)); }
                } else {
                    body.append($('<span class="smp-vp-pill warn">').text('No local match'));
                }
                if(entity.description){ body.append($('<div class="smp-vp-entity-desc">').text(entity.description)); }
                row.append(body);
                var action = $('<div class="smp-vp-entity-action">').append($('<label>').text('Action'));
                var select = $('<select class="smp-vp-entity-resolution">').attr('data-index', index);
                if(existingId){
                    select.append($('<option value="attach_existing">').text('Attach existing profile'));
                    select.append($('<option value="spawn_new">').text('No, spawn a new entity'));
                    select.val(entity.resolution === 'spawn_new' ? 'spawn_new' : 'attach_existing');
                } else {
                    select.append($('<option value="spawn_new">').text('Spawn new entity'));
                    select.append($('<option value="empty">').text('Create empty structure'));
                    select.val(entity.resolution === 'empty' ? 'empty' : 'spawn_new');
                }
                action.append(select);
                row.append(action);
                row.append($('<button type="button" class="button-link-delete">Deny</button>').on('click', function(){
                    state.entities.splice(index, 1);
                    renderEntities();
                    log('Removed entity from this approval batch.');
                }));
                wrap.append(row);
            });
            box.toggleClass('has-entities', state.entities.length > 0);
            refreshButtons();
        }
        function selectedEntities(){
            var selected = [];
            $('.smp-vp-spawn-entity-check:checked').each(function(){
                var idx = parseInt($(this).attr('data-index'), 10);
                if(state.entities[idx]){
                    var entity = $.extend({}, state.entities[idx]);
                    entity.resolution = $('.smp-vp-entity-resolution[data-index="'+idx+'"]').val() || entity.resolution || 'spawn_new';
                    selected.push(entity);
                }
            });
            return selected;
        }
        function readableJson(value){ try { return JSON.stringify(value, null, 2); } catch(e) { return String(value || ''); } }
        function normalizeError(resp, fallback, context){
            var detail = {title:(context || 'Request') + ' failed',message:fallback || 'Request failed.',status:'',statusText:'',endpoint:ajaxurl || 'admin-ajax.php',code:'',responsePreview:'',raw:null};
            if(resp && resp.data && resp.data.message){ detail.message = resp.data.message; detail.raw = resp.data; }
            if(resp && resp.responseJSON){
                detail.raw = resp.responseJSON;
                if(resp.responseJSON.data && resp.responseJSON.data.message){ detail.message = resp.responseJSON.data.message; }
                else if(resp.responseJSON.message){ detail.message = resp.responseJSON.message; }
                if(resp.responseJSON.data && resp.responseJSON.data.result){ detail.result = resp.responseJSON.data.result; }
            }
            if(resp && typeof resp.status !== 'undefined'){ detail.status = resp.status || '0'; detail.statusText = resp.statusText || ''; }
            if(resp && resp.responseText){
                detail.responsePreview = String(resp.responseText).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 1200);
                if(detail.message === fallback && detail.responsePreview){ detail.message = detail.responsePreview; }
            }
            if(detail.result){
                detail.code = detail.result.status || detail.result.code || detail.result.http_code || '';
                detail.endpoint = detail.result.endpoint || detail.result.url || detail.endpoint;
                if(detail.result.body_preview){ detail.responsePreview = detail.result.body_preview; }
            }
            return detail;
        }
        function clearError(){ box.removeClass('has-error'); $('#smp-vp-spawn-error').empty(); }
        function showError(resp, fallback, context){
            var detail = normalizeError(resp, fallback, context);
            var panel = $('#smp-vp-spawn-error').empty();
            panel.append($('<div class="smp-vp-error-title">').text(detail.title));
            panel.append($('<div class="smp-vp-error-message">').text(detail.message));
            var grid = $('<dl class="smp-vp-error-grid">');
            [['HTTP status',[detail.status, detail.statusText].filter(Boolean).join(' ') || 'WordPress JSON error'],['Endpoint',detail.endpoint],['API/status code',detail.code || 'Not provided']].forEach(function(row){
                grid.append($('<dt>').text(row[0])); grid.append($('<dd>').text(row[1]));
            });
            panel.append(grid);
            if(detail.responsePreview){ panel.append($('<pre>').text(detail.responsePreview)); }
            if(detail.raw){ panel.append($('<details open>').append($('<summary>').text('Raw response')).append($('<pre>').text(readableJson(detail.raw)))); }
            box.addClass('has-error');
            log(detail.title + ': ' + detail.message);
            return detail;
        }
        function detectExisting(btn, extra){
            clearError(); dyn.start(btn); setBusy(true, btn);
            log('Scanning article directly...');
            return $.post(ajaxurl, Object.assign({action:'smp_vp_spawn_detect_existing'}, payload('filled', extra || {})))
                .done(function(resp){
                    if(!resp || !resp.success){ showError(resp, 'Direct scan failed.', 'Direct scan'); dyn.error(btn); return; }
                    state.entities = resp.data.entities || [];
                    log(resp.data.message || ('Scanned article and found ' + state.entities.length + ' verified profile candidate(s).'));
                    renderEntities();
                    dyn.success(btn);
                })
                .fail(function(resp){ showError(resp, 'Direct scan failed.', 'Direct scan'); dyn.error(btn); })
                .always(function(){ setBusy(false); });
        }
        function propose(btn){
            clearError(); dyn.start(btn); setBusy(true, btn);
            log('Detecting verified profiles with AI...');
            return $.post(ajaxurl, Object.assign({action:'smp_vp_spawn_propose'}, payload('filled')))
                .done(function(resp){
                    if(!resp || !resp.success){ showError(resp, 'AI detection failed.', 'AI detection'); dyn.error(btn); return; }
                    state.entities = resp.data.entities || [];
                    log(resp.data.message || ('Detected ' + state.entities.length + ' proposed verified profile entit' + (state.entities.length === 1 ? 'y.' : 'ies.')));
                    renderEntities();
                    dyn.success(btn);
                })
                .fail(function(resp){ showError(resp, 'AI detection failed.', 'AI detection'); dyn.error(btn); })
                .always(function(){ setBusy(false); });
        }
        function approve(btn, mode, entities){
            var selected = entities || selectedEntities();
            if(!selected.length){ log('No entities selected.'); dyn.error(btn, 'Nothing selected'); return $.Deferred().reject().promise(); }
            clearError(); dyn.start(btn); setBusy(true, btn);
            log((mode === 'empty' ? 'Creating empty structures' : 'Approving selected entities') + '...');
            return $.post(ajaxurl, Object.assign({action:'smp_vp_spawn_approve', entities:selected}, payload(mode || 'filled')))
                .done(function(resp){
                    if(!resp || !resp.success){ showError(resp, 'Approval request failed.', 'Approval request'); dyn.error(btn); return; }
                    log(resp.data.message || 'Verified profiles attached or created.');
                    renderCreated(resp.data.created_profiles || resp.data.created || []);
                    loadProfileState();
                    dyn.success(btn);
                })
                .fail(function(resp){ showError(resp, 'Approval request failed.', 'Approval request'); dyn.error(btn); })
                .always(function(){ setBusy(false); });
        }
        function autoApprove(btn){
            var method = $('#smp-vp-spawn-method').val() || box.data('detection-method') || 'ai';
            var detector = method === 'ai' ? propose : detectExisting;
            detector(btn).done(function(resp){
                if(resp && resp.success && (resp.data.entities || []).length){
                    approve(btn, 'filled', resp.data.entities || []);
                }
            });
        }
        function scan(btn){
            var method = $('#smp-vp-spawn-method').val() || box.data('detection-method') || 'ai';
            return method === 'ai' ? propose(btn) : detectExisting(btn);
        }
        function esc(value){return $('<div>').text(value == null ? '' : String(value)).html();}
        function fieldRows(fields){
            if(!fields || !fields.length){return '<p class="smp-vp-empty">No custom fields were reported for this profile.</p>'}
            return '<table class="smp-vp-field-report"><thead><tr><th>Field</th><th>Value</th><th>Source</th></tr></thead><tbody>' + fields.map(function(field){
                return '<tr><td><strong>'+esc(field.label || field.name || 'Field')+'</strong><br><code>'+esc(field.name || '')+'</code></td><td>'+esc(field.value_preview || field.value || '')+'</td><td>'+esc(field.source || 'ACF')+'</td></tr>';
            }).join('') + '</tbody></table>';
        }
        function initPersistentDetails(root){
            $(root).find('details[data-smp-vp-collapse-key]').each(function(){
                var key = this.getAttribute('data-smp-vp-collapse-key');
                var saved = window.localStorage ? localStorage.getItem(key) : null;
                if(saved === null){ this.open = true; } else { this.open = saved === 'open'; }
                $(this).off('toggle.smpVpPersist').on('toggle.smpVpPersist', function(){ try { localStorage.setItem(key, this.open ? 'open' : 'closed'); } catch(e) {} });
            });
        }
        function renderCreated(created){
            var wrap = $('#smp-vp-spawn-created').empty();
            if(!created.length){ wrap.append('<p class="smp-vp-empty">No attached or created profiles were returned.</p>'); }
            created.forEach(function(profile){
                var id = profile.wp_post_id || profile.post_id || '';
                var title = profile.name || profile.post_title || ('Profile #' + id);
                var card = $('<article class="smp-vp-spawned-profile">');
                var actions = $('<div class="smp-vp-spawned-profile-actions">');
                if(profile.frontend_url || profile.permalink){ actions.append($('<a class="hpc-button secondary" target="_blank" rel="noopener">View front end</a>').attr('href', profile.frontend_url || profile.permalink)); }
                if(profile.backend_url || profile.edit_url){ actions.append($('<a class="hpc-button secondary" target="_blank" rel="noopener">Edit back end</a>').attr('href', profile.backend_url || profile.edit_url)); }
                card.append($('<div class="smp-vp-spawned-profile-head">').append($('<div class="smp-vp-spawned-profile-title">').text(title + (profile.action === 'attached_existing' ? ' - attached existing' : ''))).append(actions));
                var key = 'smp-vp-spawn-field-report-' + (id || title.replace(/\W+/g, '-').toLowerCase());
                var report = '<details class="hpc-section smp-vp-spawn-report" data-smp-vp-collapse-key="'+esc(key)+'" open><summary><span>Custom fields report</span><span class="hpc-pill success">Expanded by default</span></summary><div class="hpc-section-body">'+fieldRows(profile.fields_report || profile.custom_fields_report || [])+'</div></details>';
                card.append(report);
                wrap.append(card);
            });
            box.addClass('has-results');
            initPersistentDetails(wrap);
        }
        $('#smp-vp-spawn-refresh-state').on('click', function(){ loadProfileState(this); });
        $('#smp-vp-spawn-scan').on('click', function(){ scan(this); });
        $('#smp-vp-spawn-auto-approve').on('click', function(){ autoApprove(this); });
        $('#smp-vp-spawn-create-empty').on('click', function(){ approve(this, 'empty'); });
        $('#smp-vp-spawn-approve').on('click', function(){ approve(this, 'filled'); });
        $('#smp-vp-spawn-manual-add').on('click', function(){
            var btn = this, name = manualSearchValue();
            if(!name){ dyn.error(btn, 'Name required'); return; }
            dyn.start(btn);
            if(state.manualSelected && parseInt(state.manualSelected.id || state.manualSelected.value || 0, 10) > 0){
                attachManualSelectedProfile(state.manualSelected, btn);
                return;
            }
            mergeEntities([entityFromManualName(name)]);
            log('Added "' + name + '" to the review list as a new verified profile.');
            setManualSearchValue('');
            clearManualSelection();
            dyn.success(btn, 'Added');
        });
        document.addEventListener('hexa-search-selected', function(event){
            if(!event.detail || event.detail.component_id !== 'smp-vp-spawn-manual-search') return;
            state.manualSelected = event.detail.item || null;
            if(state.manualSelected){
                setManualIntent('selected', 'Attaching existing verified profile #' + (state.manualSelected.id || state.manualSelected.value || '') + ' ' + (state.manualSelected.name || state.manualSelected.label || '') + '...');
                attachManualSelectedProfile(state.manualSelected, document.getElementById('smp-vp-spawn-manual-add'));
            }
        });
        manualSearchInput().on('input', function(){
            var value = manualSearchValue();
            if(state.manualSelected && value !== (state.manualSelected.name || state.manualSelected.label || '')){
                clearManualSelection(value ? 'No existing profile selected. Click Spawn new profile to create a new verified profile from this name.' : 'Type to search existing verified profiles.');
            } else if(!state.manualSelected) {
                setManualIntent(value ? 'new' : '', value ? 'No existing profile selected. Click Spawn new profile to create a new verified profile from this name.' : 'Type to search existing verified profiles.');
            }
        });
        $('#smp-vp-spawn-select-all').on('click', function(){ var btn=this; dyn.start(btn); $('.smp-vp-spawn-entity-check').prop('checked', true); log('Selected all proposed entities.'); dyn.success(btn); });
        $('#smp-vp-spawn-clear').on('click', function(){ var btn=this; dyn.start(btn); $('.smp-vp-spawn-entity-check').prop('checked', false); log('Cleared selected entities.'); dyn.success(btn); });
        loadProfileState();
        refreshButtons();
    });
    </script>
    <?php
}

function smp_vp_spawn_profile_post_type(): string {
    $profile_settings = function_exists( __NAMESPACE__ . '\\get_verified_profile_settings' ) ? get_verified_profile_settings() : [];
    $raw_slug = (string) ( $profile_settings['slug'] ?? 'profile' );
    $post_type = sanitize_key( str_replace( '-', '_', $raw_slug ) );
    return post_type_exists( $post_type ) ? $post_type : 'profile';
}

function smp_vp_spawn_clean_entity_name( $name ): string {
    $name = trim( wp_strip_all_tags( html_entity_decode( (string) $name, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
    $name = preg_replace( '/\\s+/', ' ', $name );
    return is_string( $name ) ? $name : '';
}

function smp_vp_spawn_is_name_candidate( string $name ): bool {
    if ( '' === $name || strlen( $name ) > 120 ) {
        return false;
    }
    if ( preg_match( '/^(https?:\\/\\/|www\\.|mailto:)/i', $name ) || preg_match( '/\\S+@\\S+\\.\\S+/', $name ) ) {
        return false;
    }
    if ( preg_match( '/^(read more|click here|learn more|source|website|homepage|view profile)$/i', $name ) ) {
        return false;
    }
    return true;
}

function smp_vp_spawn_extract_candidate_names( array $payload ): array {
    $names = [];
    foreach ( (array) ( $payload['manual_names'] ?? [] ) as $manual ) {
        $manual = smp_vp_spawn_clean_entity_name( $manual );
        if ( smp_vp_spawn_is_name_candidate( $manual ) ) {
            $names[] = [ 'name' => $manual, 'source' => 'manual' ];
        }
    }
    foreach ( (array) ( $payload['links'] ?? [] ) as $link ) {
        $text = is_array( $link ) ? smp_vp_spawn_clean_entity_name( $link['text'] ?? '' ) : '';
        if ( smp_vp_spawn_is_name_candidate( $text ) ) {
            $names[] = [ 'name' => $text, 'source' => 'linked-name' ];
        }
    }
    $content = (string) ( $payload['content'] ?? '' );
    if ( '' !== $content && preg_match_all( '/<a\\b[^>]*>(.*?)<\\/a>/is', $content, $matches ) ) {
        foreach ( $matches[1] as $html ) {
            $text = smp_vp_spawn_clean_entity_name( $html );
            if ( smp_vp_spawn_is_name_candidate( $text ) ) {
                $names[] = [ 'name' => $text, 'source' => 'content-link' ];
            }
        }
    }

    $seen = [];
    $out = [];
    foreach ( $names as $row ) {
        $key = strtolower( $row['name'] );
        if ( isset( $seen[ $key ] ) ) {
            continue;
        }
        $seen[ $key ] = true;
        $out[] = $row;
    }
    return $out;
}

function smp_vp_spawn_find_existing_profile( string $name ): ?array {
    $name = smp_vp_spawn_clean_entity_name( $name );
    if ( '' === $name ) {
        return null;
    }
    global $wpdb;
    $post_type = smp_vp_spawn_profile_post_type();
    $slug = sanitize_title( $name );
    $sql = $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = %s
           AND post_status IN ('publish','draft','pending','private')
           AND (LOWER(post_title) = LOWER(%s) OR post_name = %s)
         ORDER BY CASE WHEN post_name = %s THEN 0 ELSE 1 END, post_date ASC, ID ASC
         LIMIT 1",
        $post_type,
        $name,
        $slug,
        $slug
    );
    $id = (int) $wpdb->get_var( $sql );
    if ( $id <= 0 ) {
        return null;
    }
    return [
        'id' => $id,
        'title' => get_the_title( $id ),
        'post_status' => get_post_status( $id ),
        'backend_url' => get_edit_post_link( $id, 'raw' ),
        'frontend_url' => get_permalink( $id ),
        'match_type' => 'title_or_slug',
    ];
}

function smp_vp_spawn_entity_from_name( string $name, string $source = 'scan', array $extra = [] ): array {
    $existing = smp_vp_spawn_find_existing_profile( $name );
    $entity = array_merge(
        [
            'name' => $name,
            'entity_type' => 'entity',
            'confidence' => $existing ? 1 : 0.65,
            'description' => $existing ? 'A matching local verified profile already exists.' : 'Manual or linked-name candidate with no local profile match.',
            'source' => $source,
            'resolution' => $existing ? 'attach_existing' : 'spawn_new',
        ],
        $extra
    );
    if ( $existing ) {
        $entity['existing_profile_id'] = $existing['id'];
        $entity['existing_profile_title'] = $existing['title'];
        $entity['existing_backend_url'] = $existing['backend_url'];
        $entity['existing_frontend_url'] = $existing['frontend_url'];
        $entity['match_type'] = $existing['match_type'];
    }
    return $entity;
}

function smp_vp_spawn_filter_entities_by_strictness( array $entities, array $payload ): array {
    if ( 'strict' !== (string) ( $payload['strictness'] ?? 'default' ) ) {
        return $entities;
    }
    $filtered = [];
    foreach ( $entities as $entity ) {
        if ( ! is_array( $entity ) ) {
            continue;
        }
        $confidence = (float) ( $entity['confidence'] ?? 0 );
        $type = strtolower( (string) ( $entity['entity_type'] ?? $entity['type'] ?? '' ) );
        if ( $confidence >= 0.9 && in_array( $type, [ 'person', 'company', 'organization', 'entity' ], true ) ) {
            $filtered[] = $entity;
        }
    }
    return $filtered;
}

function smp_vp_spawn_annotate_entities( array $entities, array $payload ): array {
    $entities = smp_vp_spawn_filter_entities_by_strictness( $entities, $payload );
    $out = [];
    $seen = [];
    foreach ( $entities as $entity ) {
        if ( ! is_array( $entity ) ) {
            continue;
        }
        $name = smp_vp_spawn_clean_entity_name( $entity['name'] ?? '' );
        if ( '' === $name ) {
            continue;
        }
        $key = strtolower( $name );
        if ( isset( $seen[ $key ] ) ) {
            continue;
        }
        $seen[ $key ] = true;
        $existing = smp_vp_spawn_find_existing_profile( $name );
        $entity['name'] = $name;
        $entity['source'] = $entity['source'] ?? 'ai';
        $entity['resolution'] = $existing ? 'attach_existing' : ( $entity['resolution'] ?? 'spawn_new' );
        if ( $existing ) {
            $entity['existing_profile_id'] = $existing['id'];
            $entity['existing_profile_title'] = $existing['title'];
            $entity['existing_backend_url'] = $existing['backend_url'];
            $entity['existing_frontend_url'] = $existing['frontend_url'];
            $entity['match_type'] = $existing['match_type'];
        }
        $out[] = $entity;
    }
    return $out;
}

function smp_vp_ajax_spawn_save_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }
    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );
    $settings = smp_vp_spawn_save_settings( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : [] );
    wp_send_json_success( [ 'message' => 'Spawning API settings saved.', 'settings' => $settings ] );
}

function smp_vp_ajax_spawn_profile_state(): void {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }
    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );
    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    wp_send_json_success( smp_vp_spawn_post_profile_state( $post_id ) );
}

function smp_vp_ajax_spawn_attach_existing(): void {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }

    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $profile_id = isset( $_POST['profile_id'] ) ? absint( $_POST['profile_id'] ) : 0;

    if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( [ 'message' => 'Invalid or unauthorized post.' ], 403 );
    }

    if ( $profile_id <= 0 || ! current_user_can( 'edit_post', $profile_id ) ) {
        wp_send_json_error( [ 'message' => 'Invalid or unauthorized verified profile.' ], 403 );
    }

    $profile_post = get_post( $profile_id );
    if ( ! $profile_post || smp_vp_spawn_profile_post_type() !== $profile_post->post_type ) {
        wp_send_json_error( [ 'message' => 'Selected item is not a verified profile.' ], 200 );
    }

    $already_attached = smp_vp_spawn_post_has_profile( $post_id, $profile_id );
    $profile = smp_vp_spawn_existing_profile_result( $profile_id, [ 'entity_type' => 'manual' ] );
    $attached = smp_vp_spawn_attach_profiles_to_post( $post_id, [ $profile ] );

    if ( ! $already_attached && ! in_array( $profile_id, $attached, true ) ) {
        wp_send_json_error(
            [
                'message' => 'Verified profile could not be attached to the post ACF field.',
                'state' => smp_vp_spawn_post_profile_state( $post_id ),
            ],
            200
        );
    }

    wp_send_json_success(
        [
            'message' => $already_attached
                ? get_the_title( $profile_id ) . ' was already attached to this post.'
                : get_the_title( $profile_id ) . ' attached to this post.',
            'attached_profile_ids' => $attached,
            'created_profiles' => [ $profile ],
            'profile' => $profile,
            'state' => smp_vp_spawn_post_profile_state( $post_id ),
            'already_attached' => $already_attached,
        ]
    );
}

function smp_vp_spawn_post_profile_state( int $post_id ): array {
    $current = [];
    $pending = [];
    $seen_titles = [];
    $seen_master_ids = [];

    if ( $post_id > 0 && function_exists( 'get_field' ) ) {
        $rows = get_field( 'profiles', $post_id, false );
        foreach ( (array) $rows as $index => $row ) {
            $profile_id = 0;
            if ( is_array( $row ) ) {
                $profile_id = (int) ( $row['profile'] ?? $row['field_656c17629ad34'] ?? 0 );
            } elseif ( is_numeric( $row ) ) {
                $profile_id = (int) $row;
            }
            if ( $profile_id <= 0 ) {
                continue;
            }
            $title = get_the_title( $profile_id );
            $title_key = strtolower( $title );
            $master_id = (string) get_post_meta( $profile_id, 'smp_master_verified_profile_id', true );
            $duplicate = isset( $seen_titles[ $title_key ] ) || ( '' !== $master_id && isset( $seen_master_ids[ $master_id ] ) );
            $seen_titles[ $title_key ] = true;
            if ( '' !== $master_id ) {
                $seen_master_ids[ $master_id ] = true;
            }
            $current[] = [
                'row' => (int) $index + 1,
                'profile_id' => $profile_id,
                'title' => $title,
                'post_status' => get_post_status( $profile_id ),
                'backend_url' => admin_url( 'post.php?post=' . $profile_id . '&action=edit' ),
                'frontend_url' => get_permalink( $profile_id ),
                'master_verified_profile_id' => $master_id,
                'duplicate' => $duplicate,
            ];
        }

        $pending_rows = get_field( 'pending_profiles', $post_id, false );
        foreach ( (array) $pending_rows as $index => $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $pending[] = [
                'row' => (int) $index + 1,
                'name' => sanitize_text_field( (string) ( $row['name'] ?? $row['field_656c17b79ad35'] ?? '' ) ),
                'type' => sanitize_text_field( (string) ( $row['type'] ?? $row['profile_type'] ?? '' ) ),
                'url' => esc_url_raw( (string) ( $row['url'] ?? '' ) ),
            ];
        }
    }

    return [
        'current_profiles' => $current,
        'pending_profiles' => $pending,
        'current_count' => count( $current ),
        'pending_count' => count( $pending ),
    ];
}

function smp_vp_ajax_spawn_test_api(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }
    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );
    $settings = smp_vp_spawn_save_settings( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : [] );
    $result = smp_vp_spawn_api_request( 'GET', '/health', [], $settings );
    $settings['last_test'] = [
        'success' => ! empty( $result['success'] ),
        'message' => (string) ( $result['message'] ?? 'API test completed.' ),
        'checked_at' => current_time( 'mysql' ),
    ];
    update_option( SMP_VP_SPAWN_OPTION, $settings, false );

    if ( empty( $result['success'] ) ) {
        wp_send_json_error( [ 'message' => (string) ( $result['message'] ?? 'API test failed.' ), 'result' => $result ], 200 );
    }

    wp_send_json_success( [ 'message' => (string) ( $result['message'] ?? 'API test passed.' ), 'result' => $result ] );
}

function smp_vp_ajax_spawn_detect_existing(): void {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }
    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );
    $payload = smp_vp_spawn_request_payload();
    $entities = [];
    foreach ( smp_vp_spawn_extract_candidate_names( $payload ) as $candidate ) {
        $entity = smp_vp_spawn_entity_from_name( $candidate['name'], $candidate['source'] );
        if ( ! empty( $entity['existing_profile_id'] ) || 'manual' === $candidate['source'] ) {
            $entities[] = $entity;
        }
    }
    $entities = smp_vp_spawn_filter_entities_by_strictness( $entities, $payload );
    wp_send_json_success( [
        'success' => true,
        'message' => 'Direct scan found ' . count( $entities ) . ' verified profile candidate' . ( 1 === count( $entities ) ? '.' : 's.' ),
        'entities' => $entities,
        'strictness' => $payload['strictness'],
    ] );
}

function smp_vp_ajax_spawn_propose(): void {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }
    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );
    $payload = smp_vp_spawn_request_payload();
    $result = smp_vp_spawn_api_request( 'POST', '/entities/propose', $payload );
    if ( empty( $result['success'] ) ) {
        wp_send_json_error( [ 'message' => (string) ( $result['message'] ?? 'Entity proposal failed.' ), 'result' => $result ], 200 );
    }

    $result['entities'] = smp_vp_spawn_annotate_entities( (array) ( $result['entities'] ?? [] ), $payload );
    $result['message'] = 'Detected ' . count( $result['entities'] ) . ' proposed verified profile entit' . ( 1 === count( $result['entities'] ) ? 'y.' : 'ies.' );
    wp_send_json_success( $result );
}

function smp_vp_ajax_spawn_approve(): void {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }
    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );
    $payload = smp_vp_spawn_request_payload();
    $incoming = isset( $_POST['entities'] ) && is_array( $_POST['entities'] ) ? wp_unslash( $_POST['entities'] ) : [];
    $spawn_entities = [];
    $attached_existing = [];

    foreach ( (array) $incoming as $entity ) {
        if ( ! is_array( $entity ) ) {
            continue;
        }
        $resolution = sanitize_key( (string) ( $entity['resolution'] ?? '' ) );
        $existing_id = (int) ( $entity['existing_profile_id'] ?? 0 );
        if ( $existing_id > 0 && 'spawn_new' !== $resolution && 'empty' !== $resolution ) {
            $attached_existing[] = smp_vp_spawn_existing_profile_result( $existing_id, $entity );
            continue;
        }
        $spawn_entities[] = $entity;
    }

    $created = [];
    $result = [ 'success' => true, 'created' => [], 'message' => '' ];
    if ( $spawn_entities ) {
        $api_payload = $payload;
        $api_payload['entities'] = $spawn_entities;
        $result = smp_vp_spawn_api_request( 'POST', '/entities/approve', $api_payload );
        if ( empty( $result['success'] ) && empty( $result['partial_success'] ) && empty( $attached_existing ) ) {
            wp_send_json_error( [ 'message' => (string) ( $result['message'] ?? 'Entity approval failed.' ), 'result' => $result ], 200 );
        }
        $created = smp_vp_spawn_enrich_created_profiles( (array) ( $result['created'] ?? [] ) );
    }

    $created = array_merge( $attached_existing, $created );
    $attached = smp_vp_spawn_attach_profiles_to_post( (int) $payload['post_id'], $created );
    $result['created'] = $created;
    $result['created_profiles'] = $created;
    $result['attached_profile_ids'] = $attached;
    $result['message'] = count( $attached_existing ) . ' existing profile' . ( 1 === count( $attached_existing ) ? '' : 's' ) . ' selected, ' . count( $spawn_entities ) . ' profile' . ( 1 === count( $spawn_entities ) ? '' : 's' ) . ' sent for creation.';
    wp_send_json_success( $result );
}

function smp_vp_spawn_request_payload(): array {
    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    $post = $post_id > 0 ? get_post( $post_id ) : null;
    $content = wp_kses_post( wp_unslash( (string) ( $_POST['content'] ?? '' ) ) );
    if ( '' === trim( wp_strip_all_tags( $content ) ) && $post ) {
        $content = (string) $post->post_content;
    }
    $title = sanitize_text_field( wp_unslash( (string) ( $_POST['title'] ?? '' ) ) );
    if ( '' === trim( $title ) && $post ) {
        $title = (string) $post->post_title;
    }

    return [
        'site_url' => home_url(),
        'mode' => 'empty' === (string) ( $_POST['mode'] ?? '' ) ? 'empty' : 'filled',
        'strictness' => 'strict' === sanitize_key( (string) ( $_POST['strictness'] ?? smp_vp_spawn_settings()['default_strictness'] ) ) ? 'strict' : 'default',
        'manual_names' => isset( $_POST['manual_names'] ) && is_array( $_POST['manual_names'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['manual_names'] ) ) : [],
        'status' => sanitize_key( (string) ( $_POST['status'] ?? smp_vp_spawn_settings()['default_status'] ) ),
        'post_id' => $post_id,
        'post_type' => sanitize_key( (string) ( $_POST['post_type'] ?? ( $post ? $post->post_type : '' ) ) ),
        'title' => $title,
        'url' => esc_url_raw( (string) ( $_POST['url'] ?? ( $post ? get_permalink( $post ) : '' ) ) ),
        'content' => $content,
        'links' => isset( $_POST['links'] ) && is_array( $_POST['links'] ) ? wp_unslash( $_POST['links'] ) : [],
    ];
}

function smp_vp_spawn_api_request( string $method, string $path, array $payload = [], ?array $settings = null ): array {
    $settings = $settings ?: smp_vp_spawn_settings();
    $base = rtrim( (string) ( $settings['api_base_url'] ?? '' ), '/' );
    $key = trim( (string) ( $settings['api_key'] ?? '' ) );
    if ( '' === $base || '' === $key ) {
        return [
            'success' => false,
            'message' => 'Spawning API base URL and API key are required.',
            'endpoint' => $base,
            'payload' => smp_vp_spawn_payload_debug( $payload ),
        ];
    }

    $url = $base . '/' . ltrim( $path, '/' );
    $args = [
        'timeout' => 120,
        'headers' => [
            'Accept' => 'application/json',
            'X-SMP-VP-Key' => $key,
        ],
    ];
    if ( 'GET' !== strtoupper( $method ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body'] = wp_json_encode( array_merge( [ 'site_url' => home_url() ], $payload ) );
    } else {
        $url = add_query_arg( [ 'site_url' => home_url() ], $url );
    }

    $response = wp_remote_request( $url, array_merge( $args, [ 'method' => strtoupper( $method ) ] ) );
    if ( is_wp_error( $response ) ) {
        return [
            'success' => false,
            'message' => $response->get_error_message(),
            'code' => $response->get_error_code(),
            'endpoint' => $url,
            'payload' => smp_vp_spawn_payload_debug( $payload ),
        ];
    }

    $body = wp_remote_retrieve_body( $response );
    $status_code = (int) wp_remote_retrieve_response_code( $response );
    $decoded = json_decode( (string) $body, true );
    if ( ! is_array( $decoded ) ) {
        return [
            'success' => false,
            'message' => 'Invalid API JSON response.',
            'status' => $status_code,
            'endpoint' => $url,
            'body_preview' => mb_substr( wp_strip_all_tags( (string) $body ), 0, 1200 ),
            'payload' => smp_vp_spawn_payload_debug( $payload ),
        ];
    }

    $decoded['endpoint'] = $decoded['endpoint'] ?? $url;
    $decoded['http_code'] = $decoded['http_code'] ?? $status_code;
    if ( empty( $decoded['success'] ) ) {
        $decoded['payload'] = $decoded['payload'] ?? smp_vp_spawn_payload_debug( $payload );
        if ( empty( $decoded['message'] ) ) {
            $decoded['message'] = 'Verified profiles API returned an unsuccessful response.';
        }
    }

    return $decoded;
}

function smp_vp_spawn_payload_debug( array $payload ): array {
    $content = (string) ( $payload['content'] ?? '' );
    return [
        'post_id' => (int) ( $payload['post_id'] ?? 0 ),
        'post_type' => sanitize_key( (string) ( $payload['post_type'] ?? '' ) ),
        'mode' => sanitize_key( (string) ( $payload['mode'] ?? '' ) ),
        'title' => sanitize_text_field( (string) ( $payload['title'] ?? '' ) ),
        'content_chars' => strlen( $content ),
        'content_plain_chars' => strlen( wp_strip_all_tags( $content ) ),
        'links_count' => isset( $payload['links'] ) && is_array( $payload['links'] ) ? count( $payload['links'] ) : 0,
    ];
}

function smp_vp_spawn_enrich_created_profiles( array $created ): array {
    $out = [];
    foreach ( $created as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }
        $profile_id = smp_vp_spawn_created_profile_id( $row );
        if ( $profile_id > 0 ) {
            $row['wp_post_id'] = $profile_id;
            $row['post_id'] = $profile_id;
            $row['backend_url'] = admin_url( 'post.php?post=' . $profile_id . '&action=edit' );
            $row['edit_url'] = $row['backend_url'];
            $row['frontend_url'] = get_permalink( $profile_id );
            $row['permalink'] = $row['frontend_url'];
            $row['post_title'] = get_the_title( $profile_id );
            if ( empty( $row['name'] ) ) {
                $row['name'] = $row['post_title'];
            }
        }
        $row['fields_report'] = smp_vp_spawn_profile_field_report( $profile_id, $row );
        $row['custom_fields_report'] = $row['fields_report'];
        $out[] = $row;
    }
    return $out;
}

function smp_vp_spawn_existing_profile_result( int $profile_id, array $entity = [] ): array {
    return [
        'name' => get_the_title( $profile_id ),
        'post_title' => get_the_title( $profile_id ),
        'entity_type' => sanitize_text_field( (string) ( $entity['entity_type'] ?? 'entity' ) ),
        'wp_post_id' => $profile_id,
        'post_id' => $profile_id,
        'backend_url' => admin_url( 'post.php?post=' . $profile_id . '&action=edit' ),
        'edit_url' => admin_url( 'post.php?post=' . $profile_id . '&action=edit' ),
        'frontend_url' => get_permalink( $profile_id ),
        'permalink' => get_permalink( $profile_id ),
        'action' => 'attached_existing',
        'fields_report' => smp_vp_spawn_profile_field_report( $profile_id, [] ),
    ];
}

function smp_vp_spawn_created_profile_id( array $row ): int {
    foreach ( [ 'wp_post_id', 'post_id', 'profile_id', 'id', 'local_post_id' ] as $key ) {
        if ( ! empty( $row[ $key ] ) && is_numeric( $row[ $key ] ) ) {
            return (int) $row[ $key ];
        }
    }
    return 0;
}

function smp_vp_spawn_profile_field_report( int $profile_id, array $api_row = [] ): array {
    $rows = [];
    $acf = [];
    if ( $profile_id > 0 && function_exists( 'get_fields' ) ) {
        $acf = get_fields( $profile_id );
        if ( ! is_array( $acf ) ) {
            $acf = [];
        }
    }
    smp_vp_spawn_flatten_fields( $acf, '', $rows, 'ACF' );

    if ( $profile_id > 0 ) {
        $meta_fields = [];
        foreach ( get_post_meta( $profile_id ) as $meta_key => $meta_values ) {
            if ( '' === $meta_key || '_' === $meta_key[0] ) {
                continue;
            }
            $values = array_map( 'maybe_unserialize', (array) $meta_values );
            $meta_fields[ $meta_key ] = 1 === count( $values ) ? reset( $values ) : $values;
        }
        smp_vp_spawn_flatten_fields( $meta_fields, '', $rows, 'WordPress custom field' );
    }

    foreach ( [ 'fields', 'custom_fields', 'acf_fields', 'generated_fields' ] as $key ) {
        if ( isset( $api_row[ $key ] ) && is_array( $api_row[ $key ] ) ) {
            smp_vp_spawn_flatten_fields( $api_row[ $key ], '', $rows, 'API' );
        }
    }

    if ( empty( $rows ) ) {
        $api_profile = [];
        if ( isset( $api_row['profile'] ) && is_array( $api_row['profile'] ) ) {
            $api_profile = $api_row['profile'];
        } elseif ( isset( $api_row['scan']['profile'] ) && is_array( $api_row['scan']['profile'] ) ) {
            $api_profile = $api_row['scan']['profile'];
        }

        if ( $api_profile ) {
            $summary = [];
            foreach ( [ 'name', 'title', 'primary_url', 'primary_photo_url', 'biography', 'biography_short', 'profile_type', 'master_verified_profile_id' ] as $key ) {
                if ( isset( $api_profile[ $key ] ) && '' !== smp_vp_spawn_preview_value( $api_profile[ $key ] ) ) {
                    $summary[ $key ] = $api_profile[ $key ];
                }
            }
            smp_vp_spawn_flatten_fields( $summary, '', $rows, 'API profile summary' );
        }
    }

    $seen = [];
    $deduped = [];
    foreach ( $rows as $row ) {
        $sig = $row['name'] . '|' . $row['value_preview'] . '|' . $row['source'];
        if ( isset( $seen[ $sig ] ) ) {
            continue;
        }
        $seen[ $sig ] = true;
        $deduped[] = $row;
    }
    return $deduped;
}

function smp_vp_spawn_flatten_fields( $value, string $prefix, array &$rows, string $source ): void {
    if ( ! is_array( $value ) ) {
        if ( '' !== $prefix && '' !== smp_vp_spawn_preview_value( $value ) ) {
            $rows[] = [
                'name' => $prefix,
                'label' => ucwords( str_replace( [ '_', '.' ], ' ', $prefix ) ),
                'value_preview' => smp_vp_spawn_preview_value( $value ),
                'source' => $source,
            ];
        }
        return;
    }

    foreach ( $value as $key => $item ) {
        $name = '' === $prefix ? (string) $key : $prefix . '.' . (string) $key;
        if ( is_array( $item ) && count( $item ) <= 30 ) {
            smp_vp_spawn_flatten_fields( $item, $name, $rows, $source );
        } else {
            $preview = smp_vp_spawn_preview_value( $item );
            if ( '' !== $preview ) {
                $rows[] = [
                    'name' => $name,
                    'label' => ucwords( str_replace( [ '_', '.' ], ' ', $name ) ),
                    'value_preview' => $preview,
                    'source' => $source,
                ];
            }
        }
    }
}

function smp_vp_spawn_preview_value( $value ): string {
    if ( is_scalar( $value ) || null === $value ) {
        return mb_substr( trim( wp_strip_all_tags( (string) $value ) ), 0, 260 );
    }
    if ( is_object( $value ) && isset( $value->ID ) ) {
        return '#' . (int) $value->ID . ' ' . get_the_title( (int) $value->ID );
    }
    if ( is_array( $value ) ) {
        if ( isset( $value['url'] ) && is_scalar( $value['url'] ) ) {
            return mb_substr( (string) $value['url'], 0, 260 );
        }
        return mb_substr( wp_json_encode( $value ), 0, 260 );
    }
    return '';
}

function smp_vp_spawn_post_has_profile( int $post_id, int $profile_id ): bool {
    if ( $post_id <= 0 || $profile_id <= 0 || ! function_exists( 'get_field' ) ) {
        return false;
    }

    $rows = get_field( 'profiles', $post_id, false );
    foreach ( (array) $rows as $row ) {
        $existing_id = 0;
        if ( is_array( $row ) ) {
            $existing_id = (int) ( $row['profile'] ?? $row['field_656c17629ad34'] ?? 0 );
        } elseif ( is_numeric( $row ) ) {
            $existing_id = (int) $row;
        }

        if ( $existing_id === $profile_id ) {
            return true;
        }
    }

    return false;
}

function smp_vp_spawn_attach_profiles_to_post( int $post_id, array $created ): array {
    if ( $post_id <= 0 || ! function_exists( 'add_row' ) ) {
        return [];
    }

    $existing = [];
    $existing_titles = [];
    $existing_master_ids = [];
    $rows = function_exists( 'get_field' ) ? get_field( 'profiles', $post_id, false ) : [];
    foreach ( (array) $rows as $row ) {
        $existing_id = 0;
        if ( is_array( $row ) ) {
            $existing_id = (int) ( $row['profile'] ?? $row['field_656c17629ad34'] ?? 0 );
        } elseif ( is_numeric( $row ) ) {
            $existing_id = (int) $row;
        }
        if ( $existing_id > 0 ) {
            $existing[] = $existing_id;
            $existing_titles[] = strtolower( get_the_title( $existing_id ) );
            $master_id = (string) get_post_meta( $existing_id, 'smp_master_verified_profile_id', true );
            if ( '' !== $master_id ) {
                $existing_master_ids[] = $master_id;
            }
        }
    }

    $attached = [];
    foreach ( $created as $row ) {
        $profile_id = (int) ( is_array( $row ) ? ( $row['wp_post_id'] ?? 0 ) : 0 );
        if ( $profile_id <= 0 || in_array( $profile_id, $existing, true ) ) {
            continue;
        }
        $title_key = strtolower( get_the_title( $profile_id ) );
        $master_id = (string) get_post_meta( $profile_id, 'smp_master_verified_profile_id', true );
        if ( in_array( $title_key, $existing_titles, true ) || ( '' !== $master_id && in_array( $master_id, $existing_master_ids, true ) ) ) {
            continue;
        }
        if ( add_row( 'profiles', [ 'profile' => $profile_id ], $post_id ) ) {
            $attached[] = $profile_id;
            $existing[] = $profile_id;
            $existing_titles[] = $title_key;
            if ( '' !== $master_id ) {
                $existing_master_ids[] = $master_id;
            }
        }
    }

    return $attached;
}
