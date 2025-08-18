<?php namespace smp_verified_profiles;

function snippet_post_functionality(){
add_action('admin_footer', __NAMESPACE__.'\custom_post_admin_footer_scripts');
add_action('save_post',  __NAMESPACE__.'\process_profiles_on_save', 10, 3);
add_action('admin_footer-post.php',  __NAMESPACE__.'\add_edit_profile_links_inside_label');
}
/**
 * Add custom JavaScript functionality to 'post' post type in the admin footer
 * Adds 'Process profiles' button and CMD + Y shortcut functionality to handle profiles.
 */

function custom_post_admin_footer_scripts() {
    global $pagenow, $typenow;

    // Check if we're on a 'post' post type edit page
    if ($typenow != 'post' || ($pagenow != 'post-new.php' && $pagenow != 'post.php')) {
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Add 'Process profiles' button next to ACF field
            $('.acf-field[data-name="pending_profiles"]').after('<button id="process-profiles" class="button button-primary">Process profiles</button>');

            // Handle click event for 'Process profiles' button
            $('#process-profiles').click(function(e) {
                e.preventDefault();

                // Get post status and update it if not published
                var postStatus = $('#post_status').val();
                if (postStatus !== 'publish') {
                    $('#post_status').val('draft');
                }

                $('#save-post').click(); // Trigger save/update post
            });

            // Function to handle CMD + Y keyboard shortcut
            function handleCmdY(editor) {
                editor.on('keydown', function(e) {
                    // CMD + Y (Ctrl + Y on Windows)
                    if (e.keyCode === 89 && (e.ctrlKey || e.metaKey)) {
                        e.preventDefault();

                        // Get selected text and trim it
                        var selectedText = editor.selection.getContent({format: 'text'}).trim();
                        var nameExists = false;

                        // Check if the selected text is already in the 'pending_profiles' ACF field
                        $('.acf-field[data-name="pending_profiles"] .acf-row:not(.acf-clone)').each(function() {
                            var currentName = $(this).find('.acf-field[data-name="name"] input').val().toUpperCase();
                            if (currentName === selectedText.toUpperCase()) {
                                nameExists = true;
                                return false;
                            }
                        });

                        // If the name doesn't exist, add it to 'pending_profiles'
                        if (selectedText && !nameExists) {
                            $('.acf-field[data-name="pending_profiles"] .acf-button[data-event="add-row"]').click();
                            setTimeout(function() {
                                var $lastRow = $('.acf-field[data-name="pending_profiles"] .acf-row:not(.acf-clone)').last();
                                $lastRow.find('.acf-field[data-name="name"] input').val(selectedText).change();
                            }, 100);
                            alert('Added ' + selectedText);
                        }
                    }
                });
            }

            // Attach CMD + Y handler to TinyMCE editor
            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.on('AddEditor', function(e) { handleCmdY(e.editor); });
                tinyMCE.editors.forEach(handleCmdY);
            }
        });
    </script>
    <?php
}


/**
 * Process profiles when saving a 'post' post type
 * Moves pending profiles to the 'profiles' ACF field and clears 'pending_profiles'.
 *
 * @param int $post_id The ID of the post being saved.
 * @param WP_Post $post The post object.
 * @param bool $update Whether this is an existing post being updated.
 */
function process_profiles_on_save($post_id, $post, $update) {
    // Skip autosave and if not a 'post' post type or ACF plugin is inactive
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || $post->post_type !== 'post' || !check_plugin_acf()) {
        return;
    }

    // Get pending profiles
    $pending_profiles = get_field('pending_profiles', $post_id);

    if (!empty($pending_profiles)) {
        $existing_profile_names = [];
        $existing_profiles = get_field('profiles', $post_id);

        // Collect names of existing profiles
        if (!empty($existing_profiles)) {
            foreach ($existing_profiles as $existing_profile) {
                if (isset($existing_profile['profile']) && $existing_profile['profile'] instanceof WP_Post) {
                    $existing_profile_names[] = get_the_title($existing_profile['profile']->ID);
                }
            }
        }

        // Assign 'unclaimed' user as post author
        $user = get_user_by('slug', 'unclaimed');
        $user_id = $user->ID;

        // Process pending profiles
        foreach ($pending_profiles as $profile_data) {
            $name = sanitize_text_field($profile_data['name']);

            // Check if profile is new
            if (!in_array($name, $existing_profile_names)) {
                $new_post_id = wp_insert_post([
                    'post_title'  => $name,
                    'post_type'   => 'profile',
                    'post_status' => 'publish',
                    'post_author' => $user_id, // Set the author of the post
                ]);

                // If profile was successfully created, update its fields
                if ($new_post_id) {
                    update_field('field_key_for_profile_type', sanitize_text_field($profile_data['type']), $new_post_id);
                    update_field('field_key_for_url', esc_url_raw($profile_data['url']), $new_post_id);

                    // Add new profile to the 'profiles' ACF repeater
                    add_row('profiles', ['profile' => $new_post_id], $post_id);
                }
            }
        }

        // Clear pending profiles
        update_field('pending_profiles', [], $post_id);
    }
}

/**
 * Add 'Edit Profile' links to profiles in the repeater field
 * Displays a link to edit the selected profiles in the WordPress admin.
 */
function add_edit_profile_links_inside_label() {
    global $post_type;

    // Only apply to 'post' post type
    if ('post' !== $post_type) {
        return;
    }

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add 'Edit Profile' link to each profile in the repeater field
            $('.acf-field[data-name="profiles"] .acf-row').each(function() {
                var profileSelect = $(this).find('.acf-field[data-name="profile"] select');
                var profileID = profileSelect.val(); // Get selected profile ID

                if (profileID) {
                    // Create the 'Edit Profile' link
                    var editLink = $('<a>', {
                        text: 'Edit Profile',
                        href: '/wp-admin/post.php?post=' + profileID + '&action=edit',
                        target: '_blank',
                        class: 'edit-profile-link',
                        style: 'display: block; margin-top: 5px;'
                    });

                    // Append the link inside the ACF label
                    profileSelect.closest('.acf-field').find('.acf-label').append(editLink);
                }
            });
        });
    </script>
    <?php
}











/**
 * Register "Find Profiles" box specifically on Post edit screens (Classic + Block Editor).
 * (Builds on your code exactly — keeps your get_verified_profile_settings() without leading backslash.)
 */
add_action('add_meta_boxes_post', __NAMESPACE__ . '\\add_find_profiles_metabox');
function add_find_profiles_metabox($post) {
    $labels = get_verified_profile_settings();
    $title  = isset($labels['plural']) ? (string) $labels['plural'] : 'Profiles';

    add_meta_box(
        'smpvp_find_profiles',
        sprintf(__('Find %s', 'smpvp'), $title),
        __NAMESPACE__ . '\\render_find_profiles_metabox',
        'post',
        'normal',
        'high'
    );
}

/**
 * Render meta box + JS.
 * - Robust anchor extraction (Gutenberg iframe + Classic + serialized blocks)
 * - Emphasized logs (✅ ❌ ⚠️ 🔎 ⏭️ ➕ ▶️)
 * - Blocks autosave/refresh while running
 * - Forces ACF Post Object select2 value (ACF API, then select/hidden fallback) and verifies
 */
function render_find_profiles_metabox(\WP_Post $post) {
    $nonce  = wp_create_nonce('smpvp_nonce');
    $labels = get_verified_profile_settings();
    $singJS = esc_js($labels['singular'] ?? 'Profile');
    ?>
    <div id="smpvp-box">
        <p>
            <button type="button" id="smpvp-scan" class="button button-primary" style="width:100%;">
                <?php echo esc_html(sprintf('Scan & Add %s', $labels['plural'] ?? 'Profiles')); ?>
            </button>
        </p>
        <p>
            <textarea id="smpvp-log" rows="14" style="width:100%; font-family: Menlo,Consolas,monospace;" readonly></textarea>
        </p>
        <input type="hidden" id="smpvp-nonce" value="<?php echo esc_attr($nonce); ?>">
    </div>

    <script type="text/javascript">
        jQuery(function($){
            var processing = false;
            var prevBeforeUnload = null;

            // ===== Logging (emphasized) ======================================
            function log(line){
                var $log = $('#smpvp-log');
                var now = new Date();
                var ts = now.toLocaleTimeString();
                $log.val($log.val() + '[' + ts + '] ' + line + "\n");
                $log.scrollTop($log[0].scrollHeight);
            }

            // ===== Utilities ==================================================
            function uniqueCaseInsensitive(arr){
                var seen = Object.create(null), out = [];
                arr.forEach(function(s){
                    var t = (s || '').trim();
                    if (!t) return;
                    var k = t.toLowerCase();
                    if (!seen[k]) { seen[k]=1; out.push(t); }
                });
                return out;
            }

            // Filter out URL-looking anchors; prefer "names" (contain a space, not a URL)
            function filterNameLikeAnchors(names){
                var out = [];
                names.forEach(function(t){
                    if (/^https?:\/\//i.test(t) || /www\./i.test(t) || /\S+\.\S+/.test(t)) {
                        log('⏭️  SKIP URL anchor: "' + t + '"');
                        return;
                    }
                    if (t.indexOf(' ') === -1) { // must have a space to look like a name
                        log('⏭️  SKIP non-name anchor: "' + t + '"');
                        return;
                    }
                    out.push(t);
                });
                return out;
            }

            // 1) Live DOM extraction (handles GB iframe + Classic)
            function extractAnchorTextsFromEditorDOM(){
                var texts = [];

                // Top document candidates
                document.querySelectorAll(
                    '.block-editor-writing-flow a, .editor-styles-wrapper a, .edit-post-visual-editor a, .block-library-rich-text__editable a'
                ).forEach(function(a){
                    var t = (a.textContent || '').trim();
                    if (t) texts.push(t);
                });

                // Gutenberg often renders in an iframe — scan all same-origin iframes
                var frames = document.querySelectorAll(
                    '.edit-post-visual-editor iframe, .block-editor iframe, iframe[title*="Editor"], iframe[class*="editor"], iframe'
                );
                frames.forEach(function(iframe){
                    try {
                        var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
                        if (!doc) return;
                        doc.querySelectorAll('a').forEach(function(a){
                            var t = (a.textContent || '').trim();
                            if (t) texts.push(t);
                        });
                    } catch(e) {/* cross-origin */}
                });

                // TinyMCE (Classic) visual
                if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                    try {
                        var anchors = tinyMCE.activeEditor.dom.select('a');
                        if (anchors && anchors.length) {
                            anchors.forEach(function(a){
                                var t = (a.textContent || '').trim();
                                if (t) texts.push(t);
                            });
                        }
                    } catch(e){}
                }

                return uniqueCaseInsensitive(texts);
            }

            // 2) Serialized blocks (always up-to-date)
            function getSerializedBlocksHTML(){
                try {
                    if (window.wp && wp.data && wp.data.select) {
                        var blocks = wp.data.select('core/block-editor') && wp.data.select('core/block-editor').getBlocks();
                        if (blocks && blocks.length && window.wp.blocks && wp.blocks.serialize) {
                            return wp.blocks.serialize(blocks);
                        }
                        var c = wp.data.select('core/editor').getEditedPostContent();
                        if (typeof c === 'string') return c;
                    }
                } catch(e){}
                return '';
            }

            function extractAnchorTextsFromHTML(html){
                if (!html) return [];
                var container = document.createElement('div');
                container.innerHTML = html;
                var names = [];
                container.querySelectorAll('a').forEach(function(a){
                    var t = (a.textContent || '').trim();
                    if (t) names.push(t);
                });
                return uniqueCaseInsensitive(names);
            }

            function repeaterPresent(){
                return $('.acf-field[data-name="profiles"]').length > 0;
            }

            function getCurrentProfileIds(){
                var ids = [];
                $('.acf-field[data-name="profiles"] .acf-row:not(.acf-clone)').each(function(){
                    var $sub = $(this).find('.acf-field[data-name="profile"]');
                    var v = $sub.find('input[type="hidden"]').val()
                         || $sub.find('select').val()
                         || '';
                    if (v) {
                        var num = parseInt(v, 10);
                        if (!isNaN(num)) ids.push(num);
                    }
                });
                return ids;
            }

            // Force-select value for ACF Post Object (handles select2 AJAX fields)
            function setPostObjectValue($sub, id, text){
                var done = false;

                // 1) Try ACF API first
                try {
                    if (typeof acf !== 'undefined' && typeof acf.getField === 'function') {
                        var field = acf.getField($sub);
                        if (field && typeof field.val === 'function') {
                            // ACF accepts either ID or {id,text}
                            field.val(id);
                            done = true;
                        }
                    }
                } catch(e){}

                var $select = $sub.find('select');
                var $hidden = $sub.find('input[type="hidden"]');

                // 2) Ensure the option exists in the select so select2 can display it
                if ($select.length) {
                    if (!$select.find('option[value="'+id+'"]').length) {
                        $select.append($('<option>', {value: String(id), text: text || ('#'+id)}));
                    }
                    $select.val(String(id)).trigger('change');
                    done = true;
                }

                // 3) Set hidden input too (ACF mirrors to this)
                if ($hidden.length) {
                    $hidden.val(String(id)).trigger('change');
                    done = true;
                }

                // Verify
                var verifyVal = ($select.length ? $select.val() : null) || ($hidden.length ? $hidden.val() : null);
                return done && String(verifyVal) === String(id);
            }

            function addProfileToRepeater(profileId, profileTitle){
                var $rep = $('.acf-field[data-name="profiles"]');
                if (!$rep.length) {
                    log('⚠️  WARNING: ACF repeater "profiles" not found — cannot add.');
                    return false;
                }

                // No duplicates
                var existing = getCurrentProfileIds();
                if (existing.indexOf(profileId) !== -1) {
                    log('⚠️  ALREADY PRESENT: ' + profileTitle + ' (#' + profileId + '). ⏭️  Skipping.');
                    return false;
                }

                // Add row
                $rep.find('.acf-button[data-event="add-row"]').trigger('click');

                // Wait for row/subfield to exist and be ready
                var attempts = 0;
                (function waitAndSet(){
                    attempts++;
                    var $lastRow = $rep.find('.acf-row:not(.acf-clone)').last();
                    var $sub = $lastRow.find('.acf-field[data-name="profile"]');

                    if (!$sub.length || !$sub.find('select, input[type="hidden"]').length) {
                        if (attempts < 30) return setTimeout(waitAndSet, 100);
                        log('❌ FAILED: Subfield "profile" not ready — giving up.');
                        return;
                    }

                    var ok = setPostObjectValue($sub, profileId, profileTitle);
                    if (ok) {
                        log('➕ ADDED: ' + profileTitle + ' (ID ' + profileId + ') to ACF repeater.');
                        // Show what the inputs hold now
                        var _sel = $sub.find('select').val() || '';
                        var _hid = $sub.find('input[type="hidden"]').val() || '';
                        log('▶️  VERIFY: select="' + _sel + '" hidden="' + _hid + '"');
                    } else {
                        log('❌ FAILED: Could not set Post Object value for ' + profileTitle + ' (ID ' + profileId + ').');
                    }
                })();

                return true;
            }

            // ===== AJAX lookup ===============================================
            function lookupProfileByTitle(name, done){
                if (!name || !name.trim()) {
                    done(null, { id:0, title:'', debug:{ note:'empty name'} });
                    return;
                }
                $.post(ajaxurl, {
                    action: 'smpvp_lookup_profile',
                    name: name,
                    nonce: $('#smpvp-nonce').val()
                })
                .done(function(resp){
                    if (resp && resp.success) {
                        if (resp.data && resp.data.debug) {
                            var d = resp.data.debug;
                            log('🧪 DEBUG searched_name: ' + d.searched_name);
                            log('🧪 DEBUG final_slug: '   + d.final_slug);
                            log('🧪 DEBUG found_id: '     + d.found_id);
                            log('🧪 DEBUG SQL: '         + d.sql);
                        }
                        done(null, resp.data || {id:0,title:''});
                    } else {
                        done(new Error('AJAX failure'));
                    }
                })
                .fail(function(){
                    done(new Error('AJAX request failed'));
                });
            }

            function processNamesSequentially(names, idx){
                if (idx >= names.length) {
                    log('✅ DONE: Scan complete. Remember to Update the post to save ACF changes.');
                    unblockSaves();
                    processing = false;
                    return;
                }
                var name = names[idx];
                log('🔎 CHECKING: "' + name + '"');

                lookupProfileByTitle(name, function(err, data){
                    if (err) {
                        log('❌ ERROR: Lookup failed for "' + name + '".');
                        return setTimeout(function(){ processNamesSequentially(names, idx + 1); }, 150);
                    }

                    if (data && data.id) {
                        log('✅ MATCH: "' + name + '" → <?php echo $singJS; ?> "' + data.title + '" (#' + data.id + ').');
                        addProfileToRepeater(parseInt(data.id,10), data.title);
                    } else {
                        log('❌ NOT DETECTED: No match for "' + name + '".');
                    }
                    setTimeout(function(){ processNamesSequentially(names, idx + 1); }, 220);
                });
            }

            // ===== Save/refresh protection ===================================
            function blockSaves(){
                try { if (wp && wp.data && wp.data.dispatch) wp.data.dispatch('core/editor').lockPostSaving('smpvp'); } catch(e){}
                $('#publish, #save-post').prop('disabled', true);
                $('#post, form#editor').on('submit.smpvp', function(e){
                    if (processing) { e.preventDefault(); e.stopImmediatePropagation(); log('⚠️  Blocked submit while scanning.'); return false; }
                });
                prevBeforeUnload = window.onbeforeunload || null;
                window.onbeforeunload = function(){ return 'Scanning profiles — please wait.'; };
            }
            function unblockSaves(){
                try { if (wp && wp.data && wp.data.dispatch) wp.data.dispatch('core/editor').unlockPostSaving('smpvp'); } catch(e){}
                $('#publish, #save-post').prop('disabled', false);
                $('#post, form#editor').off('submit.smpvp');
                window.onbeforeunload = prevBeforeUnload;
                prevBeforeUnload = null;
            }

            // ===== UI binding ================================================
            $('#smpvp-scan').on('click', function(e){
                e.preventDefault(); e.stopPropagation();
                if (processing) return;
                processing = true;
                $('#smpvp-log').val('');

                log('▶️  START: Scanning content for linked names…');
                blockSaves();

                // 1) Live DOM first
                var domNames = extractAnchorTextsFromEditorDOM();
                log('▶️  DOM anchors found: ' + domNames.length);

                // 2) Fallback: serialized blocks
                var serialized = getSerializedBlocksHTML();
                log('▶️  Serialized content length: ' + (serialized ? serialized.length : 0));
                var parsed = extractAnchorTextsFromHTML(serialized);
                log('▶️  Parsed anchors from serialized content: ' + parsed.length);

                // Merge + de-dupe
                var names = uniqueCaseInsensitive([].concat(domNames, parsed));

                // Filter to name-like anchors; skip URLs etc.
                var filtered = filterNameLikeAnchors(names);
                log('▶️  Using name-like anchors: ' + filtered.length + ' of ' + names.length);

                if (!filtered.length) {
                    log('❌ No suitable <a> tags detected (nothing to process).');
                    unblockSaves();
                    processing = false;
                    return;
                }

                // List what we’ll actually process
                filtered.forEach(function(n){ log('• "' + n + '"'); });

                if (!repeaterPresent()) {
                    log('⚠️  WARNING: ACF repeater "profiles" not present. Additions may fail.');
                }

                processNamesSequentially(filtered, 0);
            });
        });
    </script>
    <?php
}

/**
 * AJAX: case-insensitive exact title match for published posts of the dynamic CPT slug.
 * Returns debug payload for inline logging.
 */
add_action('wp_ajax_smpvp_lookup_profile', __NAMESPACE__ . '\\ajax_lookup_profile');
function ajax_lookup_profile() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions.'], 200);
    }

    check_ajax_referer('smpvp_nonce', 'nonce');

    $name = isset($_POST['name']) ? wp_unslash($_POST['name']) : '';
    $name = trim((string)$name);

    if ($name === '') {
        wp_send_json_success([
            'id'    => 0,
            'title' => '',
            'debug' => ['note' => 'Empty name passed, skipping search.']
        ]);
    }

    // Dynamic CPT settings (your function)
    $profile_settings = get_verified_profile_settings();

    // Slug -> DB post_type key
    $raw_slug     = (string) ($profile_settings['slug'] ?? '');
    $profile_slug = sanitize_key( str_replace('-', '_', $raw_slug) );

    global $wpdb;

    $sql = $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = %s
           AND post_status = 'publish'
           AND LOWER(post_title) = LOWER(%s)
         LIMIT 1",
        $profile_slug,
        $name
    );

    $post_id = (int) $wpdb->get_var($sql);

    $debug_info = [
        'searched_name' => $name,
        'raw_settings'  => $profile_settings,
        'final_slug'    => $profile_slug,
        'sql'           => $sql,
        'found_id'      => $post_id,
    ];

    if ($post_id > 0) {
        wp_send_json_success([
            'id'    => $post_id,
            'title' => get_the_title($post_id),
            'debug' => $debug_info
        ]);
    } else {
        wp_send_json_success([
            'id'    => 0,
            'title' => '',
            'debug' => $debug_info
        ]);
    }
}
