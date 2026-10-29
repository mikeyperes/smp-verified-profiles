<?php

namespace smp_verified_profiles;

use SMP\VerifiedProfiles\ProfileTypes\ProfileTypes;

defined( 'ABSPATH' ) || exit;

add_filter( 'smp_vp_dashboard_tabs', __NAMESPACE__ . '\\smp_vp_profile_types_dashboard_tab' );
add_filter( 'smp_vp_render_dashboard_tab', __NAMESPACE__ . '\\smp_vp_render_profile_types_dashboard_tab', 10, 2 );

function smp_vp_profile_types_dashboard_tab( array $tabs ): array {
    $tabs['profile-types'] = 'Profile Types';
    return $tabs;
}

function smp_vp_render_profile_types_dashboard_tab( $rendered, string $tab_id ) {
    if ( 'profile-types' !== $tab_id ) {
        return $rendered;
    }

    smp_vp_render_profile_types_tab();
    return true;
}

function smp_vp_render_profile_types_tab(): void {
    if ( ! current_user_can( Config::$settings_page_capability ) ) {
        echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>';
        return;
    }

    if ( class_exists( '\\Hexa\\PluginCore\\WpAdminComponents\\CoreUi' ) ) {
        \Hexa\PluginCore\WpAdminComponents\CoreUi::render_assets();
    }

    $custom      = ProfileTypes::custom();
    $presets     = ProfileTypes::presets();
    $integration = ProfileTypes::integration_post_types();
    $default     = get_post_type_object( ProfileTypes::DEFAULT_TYPE );
    ?>
    <style>
        .smp-vp-types-card{border:1px solid #dcdcde;background:#fff;border-radius:10px;padding:18px;margin:14px 0;max-width:760px}
        .smp-vp-types-card h2{margin-top:0}
        .smp-vp-types-field{margin:0 0 14px}
        .smp-vp-types-field label.smp-vp-types-label{display:block;font-weight:700;margin-bottom:5px;color:#1d2327}
        .smp-vp-types-field input[type=text],.smp-vp-types-field select{width:100%;min-height:38px;border:1px solid #c3c4c7;border-radius:6px;padding:6px 9px}
        .smp-vp-types-list{list-style:none;margin:0;padding:0}
        .smp-vp-types-list li{display:flex;justify-content:space-between;align-items:center;gap:12px;border-top:1px solid #f0f0f1;padding:10px 0}
        .smp-vp-types-list li:first-child{border-top:0}
        .smp-vp-types-meta{color:#646970;font-size:12px}
        .smp-vp-types-checks label{display:block;margin:4px 0}
        .smp-vp-types-log{margin-top:10px;color:#1d2327}
        .smp-vp-types-log.is-error{color:#b32d2e}
    </style>
    <div class="hpc-ui" id="smp-vp-profile-types">
        <div class="smp-vp-types-card">
            <h2>Profile types</h2>
            <p>Each profile type is its own post type with its own admin menu, web address and fields. The default Verified Profile type always stays as it is.</p>
            <ul class="smp-vp-types-list">
                <li>
                    <div>
                        <strong><?php echo esc_html( $default ? $default->labels->name : 'Verified Profiles' ); ?></strong> (default)
                        <div class="smp-vp-types-meta">profile &middot; /<?php echo esc_html( get_verified_profile_settings()['rewrite_slug'] ); ?>/ &middot; standard fields</div>
                    </div>
                </li>
                <?php foreach ( $custom as $type ) : ?>
                    <li>
                        <div>
                            <strong><?php echo esc_html( $type['plural'] ); ?></strong>
                            <div class="smp-vp-types-meta">
                                <?php echo esc_html( $type['key'] ); ?> &middot; /<?php echo esc_html( $type['rewrite_slug'] ); ?>/
                                &middot; <?php echo esc_html( $type['standard_fields'] ? 'standard fields' : 'no standard fields' ); ?>
                                <?php if ( 'blank' !== $type['preset'] ) : ?>&middot; <?php echo esc_html( $presets[ $type['preset'] ]['label'] ?? $type['preset'] ); ?> fields<?php endif; ?>
                                &middot; <?php echo esc_html( (string) ( wp_count_posts( $type['key'] )->publish ?? 0 ) ); ?> published
                            </div>
                        </div>
                        <div>
                            <a class="hpc-button secondary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $type['key'] ) ); ?>">Open</a>
                            <button type="button" class="hpc-button secondary smp-vp-types-remove" data-key="<?php echo esc_attr( $type['key'] ); ?>" data-name="<?php echo esc_attr( $type['plural'] ); ?>">Remove</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="description">Labels and the web address of every type can be changed later on the Custom Post Types tab. Removing a type keeps its profiles in the database; adding the same internal name again brings them back.</p>
        </div>

        <div class="smp-vp-types-card">
            <h2>Add a profile type</h2>
            <div class="smp-vp-types-field">
                <label class="smp-vp-types-label" for="smp-vp-types-singular">Name (one)</label>
                <input type="text" id="smp-vp-types-singular" placeholder="Podcaster">
            </div>
            <div class="smp-vp-types-field">
                <label class="smp-vp-types-label" for="smp-vp-types-plural">Name (many)</label>
                <input type="text" id="smp-vp-types-plural" placeholder="Podcasters">
            </div>
            <div class="smp-vp-types-field">
                <label class="smp-vp-types-label" for="smp-vp-types-slug">Web address</label>
                <input type="text" id="smp-vp-types-slug" placeholder="podcasters">
                <p class="description">Profiles open at <?php echo esc_html( home_url( '/' ) ); ?><em>web-address</em>/<em>name</em>/. Leave empty to use the plural name.</p>
            </div>
            <div class="smp-vp-types-field">
                <label class="smp-vp-types-label" for="smp-vp-types-key">Internal name</label>
                <input type="text" id="smp-vp-types-key" placeholder="podcaster" maxlength="20">
                <p class="description">Up to 20 letters, numbers or underscores. It cannot change later. Leave empty to build it from the name.</p>
            </div>
            <div class="smp-vp-types-field">
                <label class="smp-vp-types-label" for="smp-vp-types-preset">Extra fields</label>
                <select id="smp-vp-types-preset">
                    <?php foreach ( $presets as $id => $preset ) : ?>
                        <option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $preset['label'] . ': ' . $preset['description'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="smp-vp-types-field">
                <label><input type="checkbox" id="smp-vp-types-standard" checked> Include the standard Verified Profile fields, profile page and schema</label>
            </div>
            <?php echo smp_vp_profile_types_button( 'smp-vp-types-add', 'Add profile type', 'Adding...', 'Added', 'Not added' ); ?>
            <div class="smp-vp-types-log" id="smp-vp-types-add-log" role="status"></div>
        </div>

        <div class="smp-vp-types-card">
            <h2>Where the article integration runs</h2>
            <p>The profiles box in the editor, profile detection and creation, the "in this article" section and each profile's "featured in" list work on the post types checked here.</p>
            <div class="smp-vp-types-field smp-vp-types-checks">
                <?php foreach ( ProfileTypes::integration_choices() as $key => $label ) : ?>
                    <label><input type="checkbox" class="smp-vp-types-integration" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $integration, true ) ); ?>> <?php echo esc_html( $label ); ?> <span class="smp-vp-types-meta">(<?php echo esc_html( $key ); ?>)</span></label>
                <?php endforeach; ?>
            </div>
            <?php echo smp_vp_profile_types_button( 'smp-vp-types-integration-save', 'Save post types', 'Saving...', 'Saved', 'Save failed' ); ?>
            <div class="smp-vp-types-log" id="smp-vp-types-integration-log" role="status"></div>
        </div>
    </div>
    <script>
    jQuery(function($){
        var root = $('#smp-vp-profile-types');
        var nonce = '<?php echo esc_js( smp_vp_ajax_nonce() ); ?>';
        var dyn = window.HexaWpCoreDynamicButton || {start:function(b){$(b).prop('disabled',true)},success:function(b){$(b).prop('disabled',false)},error:function(b){$(b).prop('disabled',false)}};
        function send(action, data, button, log, reload){
            dyn.start(button);
            $(log).removeClass('is-error').text('');
            var payload = $.extend({ action: action, <?php echo wp_json_encode( Config::$ajax_nonce_field ); ?>: nonce }, data);
            return $.post(window.ajaxurl, payload).done(function(resp){
                if (resp && resp.success) {
                    dyn.success(button);
                    $(log).text((resp.data && resp.data.message) || 'Saved.');
                    if (reload) { setTimeout(function(){ window.location.reload(); }, 600); }
                } else {
                    dyn.error(button);
                    $(log).addClass('is-error').text((resp && resp.data && resp.data.message) || 'Something went wrong.');
                }
            }).fail(function(xhr){
                dyn.error(button);
                var msg = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
                $(log).addClass('is-error').text(msg || 'The request failed.');
            });
        }
        root.on('click', '#smp-vp-types-add', function(){
            send('smp_vp_profile_types_add', {
                singular: $('#smp-vp-types-singular').val(),
                plural: $('#smp-vp-types-plural').val(),
                rewrite_slug: $('#smp-vp-types-slug').val(),
                key: $('#smp-vp-types-key').val(),
                preset: $('#smp-vp-types-preset').val(),
                standard_fields: $('#smp-vp-types-standard').is(':checked') ? 1 : 0
            }, this, '#smp-vp-types-add-log', true);
        });
        root.on('click', '.smp-vp-types-remove', function(){
            var name = $(this).data('name');
            if (!window.confirm('Remove ' + name + '? Its profiles stay in the database but are hidden until the type is added again.')) { return; }
            send('smp_vp_profile_types_remove', { key: $(this).data('key') }, this, '#smp-vp-types-add-log', true);
        });
        root.on('click', '#smp-vp-types-integration-save', function(){
            var types = [];
            root.find('.smp-vp-types-integration:checked').each(function(){ types.push($(this).val()); });
            send('smp_vp_profile_types_save_integration', { post_types: types }, this, '#smp-vp-types-integration-log', false);
        });
    });
    </script>
    <?php
}

function smp_vp_profile_types_button( string $id, string $label, string $working, string $success, string $error ): string {
    if ( class_exists( '\\Hexa\\PluginCore\\WpAdminComponents\\DynamicButton' ) ) {
        return \Hexa\PluginCore\WpAdminComponents\DynamicButton::render(
            [ 'id' => $id, 'label' => $label, 'working_label' => $working, 'success_label' => $success, 'error_label' => $error, 'class' => 'hpc-button' ]
        );
    }

    return '<button type="button" id="' . esc_attr( $id ) . '" class="hpc-button">' . esc_html( $label ) . '</button>';
}
