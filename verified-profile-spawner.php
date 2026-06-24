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
add_action( 'wp_ajax_smp_vp_spawn_approve', __NAMESPACE__ . '\\smp_vp_ajax_spawn_approve' );

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
        'default_mode'   => 'filled',
        'default_status' => 'publish',
        'post_types'     => [ 'post', 'press-release' ],
        'last_test'      => [],
    ];
}

function smp_vp_spawn_settings(): array {
    $settings = get_option( SMP_VP_SPAWN_OPTION, [] );
    if ( ! is_array( $settings ) ) {
        $settings = [];
    }
    $settings = array_replace_recursive( smp_vp_spawn_defaults(), $settings );
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
    $status = sanitize_key( (string) ( $input['default_status'] ?? $settings['default_status'] ) );
    $settings['default_status'] = in_array( $status, [ 'publish', 'draft', 'private', 'pending' ], true ) ? $status : 'publish';
    $settings['post_types'] = array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) );

    update_option( SMP_VP_SPAWN_OPTION, $settings, false );
    return $settings;
}

function smp_vp_spawn_render_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>';
        return;
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
    <div class="smp-vp-spawn-card" id="smp-vp-spawn-settings">
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
                    <option value="filled" <?php selected( $settings['default_mode'], 'filled' ); ?>>Detect and fill basics</option>
                    <option value="empty" <?php selected( $settings['default_mode'], 'empty' ); ?>>Create empty profile structures</option>
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
            <button type="button" class="button button-primary" id="smp-vp-spawn-save">Save settings</button>
            <button type="button" class="button" id="smp-vp-spawn-test">Test API connection</button>
        </div>
        <div class="smp-vp-spawn-log" id="smp-vp-spawn-settings-log"><?php echo esc_html( ! empty( $settings['last_test']['message'] ) ? $settings['last_test']['message'] : 'Ready.' ); ?></div>
    </div>
    <script>
    jQuery(function($){
        var nonce = '<?php echo esc_js( $nonce ); ?>';
        function collect(){
            var postTypes = [];
            $('.smp-vp-spawn-post-type:checked').each(function(){ postTypes.push($(this).val()); });
            return {
                enabled: $('#smp-vp-spawn-enabled').is(':checked') ? 1 : 0,
                api_base_url: $('#smp-vp-spawn-api-base').val(),
                api_key: $('#smp-vp-spawn-api-key').val(),
                default_mode: $('#smp-vp-spawn-default-mode').val(),
                default_status: $('#smp-vp-spawn-default-status').val(),
                post_types: postTypes
            };
        }
        function log(message){ $('#smp-vp-spawn-settings-log').text(message || 'Done.'); }
        $('#smp-vp-spawn-save').on('click', function(){
            var $btn = $(this).prop('disabled', true).text('Saving...');
            $.post(ajaxurl, {action:'smp_vp_spawn_save_settings', nonce:nonce, settings:collect()})
                .done(function(resp){ log(resp && resp.success ? (resp.data.message || 'Settings saved.') : ((resp.data && resp.data.message) || 'Save failed.')); })
                .fail(function(){ log('Settings save request failed.'); })
                .always(function(){ $btn.prop('disabled', false).text('Save settings'); });
        });
        $('#smp-vp-spawn-test').on('click', function(){
            var $btn = $(this).prop('disabled', true).text('Testing...');
            $.post(ajaxurl, {action:'smp_vp_spawn_test_api', nonce:nonce, settings:collect()})
                .done(function(resp){ log(resp && resp.success ? (resp.data.message || 'API test passed.') : ((resp.data && resp.data.message) || 'API test failed.')); })
                .fail(function(){ log('API test request failed.'); })
                .always(function(){ $btn.prop('disabled', false).text('Test API connection'); });
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
                'Spawn Verified Profiles',
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
    ?>
    <style>
        #smp-vp-spawn-box{border:1px solid #dcdcde;border-radius:10px;background:#fff;overflow:hidden}
        #smp-vp-spawn-box .smp-vp-spawn-head{padding:12px 14px;border-bottom:1px solid #dcdcde;background:#f6f7f7}
        #smp-vp-spawn-box .smp-vp-spawn-body{padding:14px}
        #smp-vp-spawn-box .smp-vp-spawn-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
        #smp-vp-spawn-box .smp-vp-spawn-log{width:100%;min-height:130px;border:1px solid #c3c4c7;border-radius:8px;font-family:Menlo,Consolas,monospace;padding:9px;white-space:pre-wrap;background:#fbfbfc;box-sizing:border-box}
        #smp-vp-spawn-box .smp-vp-entity{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:10px;align-items:start;border:1px solid #dcdcde;border-radius:8px;padding:10px;margin:8px 0;background:#fff}
        #smp-vp-spawn-box .smp-vp-entity-title{font-weight:700}
        #smp-vp-spawn-box .smp-vp-entity-meta{color:#646970;font-size:12px;margin-top:2px}
        #smp-vp-spawn-box .smp-vp-entity-desc{font-size:12px;color:#3c434a;margin-top:5px}
    </style>
    <div id="smp-vp-spawn-box" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-post-type="<?php echo esc_attr( $post->post_type ); ?>" data-mode="<?php echo esc_attr( $settings['default_mode'] ); ?>" data-status="<?php echo esc_attr( $settings['default_status'] ); ?>">
        <div class="smp-vp-spawn-head">
            <strong>Verified profile spawning</strong>
            <div class="smp-vp-entity-meta">Scan this article, approve people or companies, create profile CPT records, and attach them to this post.</div>
        </div>
        <div class="smp-vp-spawn-body">
            <div class="smp-vp-spawn-actions">
                <button type="button" class="button button-primary" id="smp-vp-spawn-filled">Scan and fill basics</button>
                <button type="button" class="button" id="smp-vp-spawn-empty">Scan and create empty structures</button>
                <button type="button" class="button" id="smp-vp-spawn-approve" disabled>Approve selected</button>
                <button type="button" class="button" id="smp-vp-spawn-select-all" disabled>Select all</button>
                <button type="button" class="button" id="smp-vp-spawn-clear" disabled>Clear</button>
            </div>
            <div id="smp-vp-spawn-entities"></div>
            <div class="smp-vp-spawn-log" id="smp-vp-spawn-log">Ready.</div>
        </div>
        <input type="hidden" id="smp-vp-spawn-nonce" value="<?php echo esc_attr( $nonce ); ?>">
    </div>
    <script>
    jQuery(function($){
        var state = {entities:[], mode:$('#smp-vp-spawn-box').data('mode') || 'filled', busy:false};
        function log(line){
            var now = new Date().toLocaleTimeString();
            var $log = $('#smp-vp-spawn-log');
            $log.text(($log.text() === 'Ready.' ? '' : $log.text() + "\n") + '[' + now + '] ' + line);
        }
        function setBusy(busy, label){
            state.busy = busy;
            $('#smp-vp-spawn-filled,#smp-vp-spawn-empty').prop('disabled', busy);
            $('#smp-vp-spawn-approve').prop('disabled', busy || !state.entities.length);
            $('#smp-vp-spawn-select-all,#smp-vp-spawn-clear').prop('disabled', busy || !state.entities.length);
            if(label){ log(label); }
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
        function articlePayload(mode){
            var content = editedContent();
            return {
                nonce: $('#smp-vp-spawn-nonce').val(),
                post_id: $('#smp-vp-spawn-box').data('post-id'),
                post_type: $('#smp-vp-spawn-box').data('post-type'),
                mode: mode || state.mode || 'filled',
                status: $('#smp-vp-spawn-box').data('status') || 'publish',
                title: editedTitle(),
                url: $('#sample-permalink a').attr('href') || '',
                content: content,
                links: extractLinks(content)
            };
        }
        function renderEntities(){
            var $wrap = $('#smp-vp-spawn-entities').empty();
            state.entities.forEach(function(entity, index){
                var desc = entity.description ? '<div class="smp-vp-entity-desc"></div>' : '';
                var $row = $('<div class="smp-vp-entity">')
                    .append($('<input type="checkbox" class="smp-vp-spawn-entity-check">').attr('data-index', index).prop('checked', true))
                    .append($('<div>')
                        .append($('<div class="smp-vp-entity-title">').text(entity.name || 'Untitled entity'))
                        .append($('<div class="smp-vp-entity-meta">').text((entity.entity_type || 'entity') + ' · confidence ' + Math.round((entity.confidence || 0) * 100) + '%'))
                        .append(desc ? $('<div class="smp-vp-entity-desc">').text(entity.description) : '')
                    )
                    .append($('<button type="button" class="button-link-delete">Deny</button>').on('click', function(){ state.entities.splice(index, 1); renderEntities(); }));
                $wrap.append($row);
            });
            $('#smp-vp-spawn-approve,#smp-vp-spawn-select-all,#smp-vp-spawn-clear').prop('disabled', !state.entities.length);
        }
        function propose(mode){
            state.mode = mode;
            setBusy(true, 'Scanning article through verified profiles API...');
            $.post(ajaxurl, Object.assign({action:'smp_vp_spawn_propose'}, articlePayload(mode)))
                .done(function(resp){
                    if(!resp || !resp.success){ log((resp && resp.data && resp.data.message) || 'Proposal request failed.'); return; }
                    state.entities = resp.data.entities || [];
                    log(resp.data.message || ('Detected ' + state.entities.length + ' entities.'));
                    renderEntities();
                })
                .fail(function(){ log('Proposal request failed.'); })
                .always(function(){ setBusy(false); });
        }
        function selectedEntities(){
            var selected = [];
            $('.smp-vp-spawn-entity-check:checked').each(function(){
                var idx = parseInt($(this).attr('data-index'), 10);
                if(state.entities[idx]) selected.push(state.entities[idx]);
            });
            return selected;
        }
        function approve(){
            var selected = selectedEntities();
            if(!selected.length){ log('No entities selected.'); return; }
            setBusy(true, 'Approving selected entities and creating verified profiles...');
            $.post(ajaxurl, Object.assign({action:'smp_vp_spawn_approve', entities:selected}, articlePayload(state.mode)))
                .done(function(resp){
                    if(!resp || !resp.success){ log((resp && resp.data && resp.data.message) || 'Approval failed.'); return; }
                    log(resp.data.message || 'Profiles created and attached.');
                    (resp.data.created || []).forEach(function(row){
                        log('Created #' + row.wp_post_id + ' ' + row.name + (row.permalink ? ' ' + row.permalink : ''));
                    });
                })
                .fail(function(){ log('Approval request failed.'); })
                .always(function(){ setBusy(false); });
        }
        $('#smp-vp-spawn-filled').on('click', function(){ propose('filled'); });
        $('#smp-vp-spawn-empty').on('click', function(){ propose('empty'); });
        $('#smp-vp-spawn-approve').on('click', approve);
        $('#smp-vp-spawn-select-all').on('click', function(){ $('.smp-vp-spawn-entity-check').prop('checked', true); });
        $('#smp-vp-spawn-clear').on('click', function(){ $('.smp-vp-spawn-entity-check').prop('checked', false); });
    });
    </script>
    <?php
}

function smp_vp_ajax_spawn_save_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }
    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );
    $settings = smp_vp_spawn_save_settings( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : [] );
    wp_send_json_success( [ 'message' => 'Spawning API settings saved.', 'settings' => $settings ] );
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

    wp_send_json_success( $result );
}

function smp_vp_ajax_spawn_approve(): void {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }
    check_ajax_referer( SMP_VP_SPAWN_NONCE, 'nonce' );
    $payload = smp_vp_spawn_request_payload();
    $payload['entities'] = isset( $_POST['entities'] ) && is_array( $_POST['entities'] ) ? wp_unslash( $_POST['entities'] ) : [];
    $result = smp_vp_spawn_api_request( 'POST', '/entities/approve', $payload );
    if ( empty( $result['success'] ) && empty( $result['partial_success'] ) ) {
        wp_send_json_error( [ 'message' => (string) ( $result['message'] ?? 'Entity approval failed.' ), 'result' => $result ], 200 );
    }

    $created = smp_vp_spawn_attach_profiles_to_post( (int) $payload['post_id'], (array) ( $result['created'] ?? [] ) );
    $result['attached_profile_ids'] = $created;
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
        return [ 'success' => false, 'message' => 'Spawning API base URL and API key are required.' ];
    }

    $url = $base . '/' . ltrim( $path, '/' );
    $args = [
        'timeout' => 90,
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
        return [ 'success' => false, 'message' => $response->get_error_message() ];
    }

    $body = wp_remote_retrieve_body( $response );
    $decoded = json_decode( (string) $body, true );
    if ( ! is_array( $decoded ) ) {
        return [ 'success' => false, 'message' => 'Invalid API JSON response.', 'status' => wp_remote_retrieve_response_code( $response ), 'body' => mb_substr( (string) $body, 0, 1000 ) ];
    }

    return $decoded;
}

function smp_vp_spawn_attach_profiles_to_post( int $post_id, array $created ): array {
    if ( $post_id <= 0 || ! function_exists( 'add_row' ) ) {
        return [];
    }

    $existing = [];
    $rows = function_exists( 'get_field' ) ? get_field( 'profiles', $post_id, false ) : [];
    foreach ( (array) $rows as $row ) {
        if ( is_array( $row ) && ! empty( $row['profile'] ) ) {
            $existing[] = (int) $row['profile'];
        } elseif ( is_numeric( $row ) ) {
            $existing[] = (int) $row;
        }
    }

    $attached = [];
    foreach ( $created as $row ) {
        $profile_id = (int) ( is_array( $row ) ? ( $row['wp_post_id'] ?? 0 ) : 0 );
        if ( $profile_id <= 0 || in_array( $profile_id, $existing, true ) ) {
            continue;
        }
        if ( add_row( 'profiles', [ 'profile' => $profile_id ], $post_id ) ) {
            $attached[] = $profile_id;
            $existing[] = $profile_id;
        }
    }

    return $attached;
}
