<?php namespace smp_verified_profiles;


function enable_snippet_wp_admin_user_page_functionality()
{
add_filter('acf/load_field/name=profile', __NAMESPACE__.'\make_unclaimed_profiles_read_only');
add_action('admin_footer-user-edit.php', __NAMESPACE__.'\add_custom_email_buttons');
add_action('admin_footer-profile.php', __NAMESPACE__.'\add_custom_email_buttons');
add_action('admin_footer', __NAMESPACE__.'\enqueue_custom_admin_scripts');
add_action('personal_options_update', __NAMESPACE__.'\update_user_profile_content');
add_action('edit_user_profile_update', __NAMESPACE__.'\update_user_profile_content');
add_action('user_register', __NAMESPACE__.'\update_user_profile_content', 10, 1);
add_action('profile_update', __NAMESPACE__.'\update_user_email_settings', 10, 1);
}


/**
 * Function to get user ID, checks if user_id is provided in the URL, else returns the current user's ID.
 */
if (!function_exists(__NAMESPACE__ . '\\get_user_id')) {
    function get_user_id() {
        return isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\get_user_id function is already declared", true);

/**
 * Makes the 'profile' subfield in the 'unclaimed_profiles' ACF repeater in Users read-only.
 * 
 * @param array $field The ACF field data.
 * @return array The modified ACF field data.
 */
if (!function_exists(__NAMESPACE__ . '\\make_unclaimed_profiles_read_only')) {
    function make_unclaimed_profiles_read_only($field) {
        if ($field['_name'] == 'profile' && $field['parent'] == 'unclaimed_profiles') {
            $field['disabled'] = 1; // Disable the field
        }
        return $field;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\make_unclaimed_profiles_read_only function is already declared", true);


/**
 * Retrieves and formats unclaimed profiles associated with the user as a bullet list with clickable links.
 * 
 * @param int $user_id The user ID.
 * @return string HTML formatted string of the bullet list of unclaimed profiles.
 */
if (!function_exists(__NAMESPACE__ . '\\list_unclaimed_profiles')) {
    function list_unclaimed_profiles($user_id) {
        $unclaimed_profiles = get_field('unclaimed_profiles', 'user_' . $user_id);

        if (empty($unclaimed_profiles)) {
            return 'No unclaimed profiles found.';
        }

        $output = '<ul>';
        foreach ($unclaimed_profiles as $profile) {
            $profile_id = $profile['profile'];
            $profile_post = get_post($profile_id);

            if ($profile_post) {
                $profile_url = get_permalink($profile_id);
                $output .= '<li><a href="' . esc_url($profile_url) . '" target="_blank">' . esc_html(get_the_title($profile_id)) . '</a></li>';
            }
        }
        $output .= '</ul>';
        return $output;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\list_unclaimed_profiles function is already declared", true);


/**
 * Lists all profiles owned by a specific user.
 * 
 * @param int $user_id The user ID.
 * @return string HTML formatted list of user-owned profiles.
 */
if (!function_exists(__NAMESPACE__ . '\\list_user_owned_profiles')) {
    function list_user_owned_profiles($user_id) {
        $args = array(
            'post_type' => 'profile',
            'author' => $user_id,
            'posts_per_page' => -1
        );

        $user_profiles = get_posts($args);

        if (empty($user_profiles)) {
            return 'No profiles found for this user.';
        }

        $output = '<ul>';
        foreach ($user_profiles as $profile) {
            $profile_link = get_permalink($profile->ID);
            $output .= '<li><a href="' . esc_url($profile_link) . '" target="_blank">' . esc_html($profile->post_title) . '</a></li>';
        }
        $output .= '</ul>';

        return $output;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\list_user_owned_profiles function is already declared", true);

/**
 * Fetches notification emails from the ACF repeater for a given user.
 * 
 * @param int $user_id The user ID.
 * @return array List of notification emails.
 */
if (!function_exists(__NAMESPACE__ . '\\get_notification_emails')) {
    function get_notification_emails($user_id) {
        $emails = [];
        if (have_rows('notification_emails', 'user_' . $user_id)) {
            while (have_rows('notification_emails', 'user_' . $user_id)) {
                the_row();
                $email = get_sub_field('email');
                if (!in_array($email, $emails)) {
                    $emails[] = $email;
                }
            }
        }
        return $emails;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\get_notification_emails function is already declared", true);


/**
 * Adds custom buttons for sending emails from the user-edit screen.
 */
if (!function_exists(__NAMESPACE__ . '\\add_custom_email_buttons')) {
    function add_custom_email_buttons() {
        ?><script type="text/javascript">
        jQuery(document).ready(function($) {
            function appendButtonIfNotExist(containerSelector, buttonId, buttonText) {
                var container = $(containerSelector);
                if ($('#' + buttonId).length === 0) {
                    container.after($('<button/>', {
                        type: 'button',
                        id: buttonId,
                        text: buttonText,
                        class: 'button button-primary',
                        css: { 'margin-top': '10px', 'display': 'block' }
                    }));
                }
            }

            var welcomeEmailEditor = $('[data-key="field_658602dc4ea03"] .acf-field-wysiwyg').last();
            var newEntityEmailEditor = $('[data-key="field_6586031ac6afc"] .acf-field-wysiwyg').last();

            appendButtonIfNotExist(welcomeEmailEditor, 'send-welcome-email-btn', 'Send Welcome Email');
            appendButtonIfNotExist(newEntityEmailEditor, 'send-new-entity-email-btn', 'Send New Entity Email');
        });
        </script><?php
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\add_custom_email_buttons function is already declared", true);


 
 
/**
 * Enqueues custom admin scripts related to user and profile management.
 */
if (!function_exists(__NAMESPACE__ . '\\enqueue_custom_admin_scripts')) {
    function enqueue_custom_admin_scripts() {
        global $pagenow;
        if ($pagenow != 'user-edit.php' && $pagenow != 'profile.php') {
            return;
        }
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var smpVpNonce = <?php echo wp_json_encode( function_exists( __NAMESPACE__ . '\\smp_vp_ajax_nonce' ) ? smp_vp_ajax_nonce() : wp_create_nonce( Config::$ajax_nonce_action ) ); ?>;

                // Function to extract email content from TinyMCE editor using ACF field key
                function getEmailContentFromACF(fieldKey) {
                    var iframeId = $('.acf-field-' + fieldKey + ' .wp-editor-wrap iframe').attr('id');
                    if (iframeId && tinyMCE.get(iframeId)) {
                        return tinyMCE.get(iframeId).getContent();
                    } else {
                        return $('.acf-field-' + fieldKey + ' textarea').val(); // Fallback if TinyMCE is not active
                    }
                }

                // Click event for 'Send New Entity Email' button
                $(document).on('click', '#send-new-entity-email-btn', function(e) {
                    e.preventDefault();
                    var emailSubject = $('#acf-field_6586031ac6afc-field_65860330c6afd').val();
                    var emailContent = getEmailContentFromACF('6586033cc6afe');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'send_email',
                            nonce: smpVpNonce,
                            prefix: 'new_entity_email',
                            subject: emailSubject,
                            message: emailContent,
                            profile_id: $('#select_unclaimed_profiles').val(),
                            user_id: <?php echo get_user_id(); ?>
                        },
                        success: function(response) {
                            if (response && response.success) {
                                alert((response.data && response.data.message) || 'Email sent successfully.');
                            } else {
                                alert((response && response.data && response.data.message) || 'Email not sent.');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("AJAX error: ", textStatus, errorThrown);
                            var errMsg = "Error: " + textStatus + ". " + errorThrown;
                            var responseText = jqXHR.responseText ? " Server response: " + jqXHR.responseText : '';
                            alert(errMsg + responseText);
                        }
                    });
                });

                // Fetch unclaimed profiles and populate the dropdown
                function fetchUnclaimedProfiles() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'get_unclaimed_profiles',
                            nonce: smpVpNonce,
                            user_id: <?php echo get_user_id(); ?>,
                        },
                        success: function(response) {
                            var $dropdown = $('#select_unclaimed_profiles');
                            $dropdown.empty();
                            if (!response || !response.success || !response.data || !response.data.profiles) {
                                return;
                            }
                            $.each(response.data.profiles, function(i, profile) {
                                $dropdown.append($('<option></option>').val(profile.id).html(profile.name));
                            });
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("AJAX error: ", textStatus, errorThrown);
                            var errMsg = "Error: " + textStatus + ". " + errorThrown;
                            var responseText = jqXHR.responseText ? " Server response: " + jqXHR.responseText : '';
                            alert("Email not sent!" + errMsg + responseText);
                        }
                    });
                }

                fetchUnclaimedProfiles(); // Call the function on page load
            });
        </script>
        <?php
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\enqueue_custom_admin_scripts function is already declared", true);


/**
 * Retrieves unclaimed profiles for the given user via AJAX.
 */
if (!function_exists(__NAMESPACE__ . '\\get_unclaimed_profiles')) {
    function get_unclaimed_profiles() {
        check_ajax_referer( Config::$ajax_nonce_action, Config::$ajax_nonce_field );

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error( [ 'message' => 'Invalid User ID' ] );
        }

        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }

        $unclaimed_profiles = get_field('unclaimed_profiles', 'user_' . $user_id);
        $profiles_data = [];

        if ($unclaimed_profiles) {
            foreach ($unclaimed_profiles as $profile) {
                $profile_post = get_post($profile['profile']);
                $profiles_data[] = array(
                    'id' => $profile_post->ID,
                    'name' => $profile_post->post_title
                );
            }
        }

        wp_send_json_success( [ 'profiles' => $profiles_data ] );
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\get_unclaimed_profiles function is already declared", true);


/**
 * Handles sending emails via AJAX.
 */
if (!function_exists(__NAMESPACE__ . '\\handle_send_email')) {
    function handle_send_email() {
        check_ajax_referer( Config::$ajax_nonce_action, Config::$ajax_nonce_field );

        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }

        $prefix = isset($_POST['prefix']) ? sanitize_key($_POST['prefix']) : '';
        if ( ! in_array( $prefix, [ 'welcome_email', 'new_entity_email' ], true ) ) {
            wp_send_json_error( [ 'message' => 'Invalid email template.' ], 400 );
        }

        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $message = isset($_POST['message']) ? wp_kses_post($_POST['message']) : '';
        $profile_id = isset($_POST['profile_id']) ? absint($_POST['profile_id']) : 0;

        update_field($prefix . "_message", $message, "user_" . $user_id);
        update_field($prefix . "_subject", $subject, "user_" . $user_id);
        $message = get_field($prefix . "_message", "user_" . $user_id);

        if ($profile_id) {
            $profile_post = get_post($profile_id);
            if ($profile_post) {
                $profile_name = get_the_title($profile_post);
                $profile_permalink = get_permalink($profile_post);

                $message = str_replace('{featured_profile}', '<a href="' . esc_url($profile_permalink) . '">' . esc_html($profile_name) . '</a>', $message);
                $message = str_replace('{featured_profile_name}', $profile_name, $message);
                $message = str_replace('{featured_profile_link}', $profile_permalink, $message);

                $subject = str_replace('{featured_profile_name}', $profile_name, $subject);
            }
        }

        $emails = get_notification_emails($user_id); // Function to get notification emails
        $email_signature = get_field("email_signature", "options");
        $message .= $email_signature;

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
            'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>'
        );

        $sent = 0;
        foreach ($emails as $email) {
            if ( wp_mail($email, $subject, $message, $headers) ) {
                $sent++;
            }
        }

        wp_send_json_success( [ 'message' => 'Email sent.', 'sent' => $sent ] );
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\handle_send_email function is already declared", true);


/**
 * Handles the user refresh functionality via AJAX.
 */
if (!function_exists(__NAMESPACE__ . '\\handle_refresh_user')) {
    function handle_refresh_user() {
        check_ajax_referer( Config::$ajax_nonce_action, Config::$ajax_nonce_field );

        $user_id = intval($_POST['user_id']);
        if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }

        update_user_email_settings($user_id);

        wp_send_json_success(['message' => 'User email content refreshed for user ID ' . $user_id]);
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\handle_refresh_user function is already declared", true);


/**
 * Updates user profile content when a profile is updated or saved.
 * 
 * @param int $user_id The user ID.
 */
if (!function_exists(__NAMESPACE__ . '\\update_user_profile_content')) {
    function update_user_profile_content($user_id) {
        add_action('acf/save_post', function() use ($user_id) {
            update_user_email_settings($user_id);
        }, 20);
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\update_user_profile_content function is already declared", true);


/**
 * Updates user email settings by replacing placeholders with dynamic content.
 * 
 * @param int $user_id The user ID.
 */
if (!function_exists(__NAMESPACE__ . '\\update_user_email_settings')) {
    function update_user_email_settings($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $user_data = get_userdata($user_id);
        $user_username = $user_data->user_login;
        $user_email = $user_data->user_email;
        $user_full_name = $user_data->display_name;
        $user_first_name = $user_data->first_name;
        $reset_url = function_exists( __NAMESPACE__ . '\\smp_vp_password_reset_url' )
            ? smp_vp_password_reset_url( (int) $user_id )
            : wp_lostpassword_url();

  // build the login URL dynamically
$dashboard_url = esc_url( admin_url() ); // e.g. https://herforward.com/wp-admin/

// make sure you also have the email variable
$user_email = isset( $user_email ) ? $user_email : '';

$credentials_dashboard_content  = '<b>Login URL:</b> ' . $dashboard_url . '<br />';
$credentials_dashboard_content .= '<b>Username:</b> ' . esc_html( $user_username ) . '<br />';
$credentials_dashboard_content .= '<b>Email:</b> '    . esc_html( $user_email )    . '<br />';
$credentials_dashboard_content .= '<b>Password setup:</b> <a href="' . esc_url( $reset_url ) . '">Set or reset password</a>';


        // Define groups of settings and their corresponding fields.
        $groups = array(
            'welcome_email' => array('subject', 'message'),
            'new_entity_email' => array('subject', 'message')
        );

        // Iterate through each group and field.
        foreach ($groups as $group_key => $fields) {
            foreach ($fields as $field) {
                // Construct keys for theme options and user-specific fields.
                $theme_field_key = $group_key . '_' . $field;
                $user_field_key = $group_key . '_' . $field;

                // Fetch value from theme options.
                $value = get_field($theme_field_key, 'options');

                // Replace placeholders in the value with user data.
                $value = str_replace('{full_name}', $user_full_name, $value);
                $value = str_replace('{first_name}', $user_first_name, $value);
                $value = str_replace('{credentials_dashboard}', $credentials_dashboard_content, $value);

                // If the 'message' field is being processed, replace {list_unclaimed_profiles}
                if ($field === 'message') {
                    $list_unclaimed_profiles = list_unclaimed_profiles($user_id);
                    $value = str_replace('{list_unclaimed_profiles}', $list_unclaimed_profiles, $value);
                }

                // Update user-specific field with the processed value.
                update_field($user_field_key, $value, 'user_' . $user_id);
            }
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\update_user_email_settings function is already declared", true);


/**
 * JavaScript handler for refreshing user details on the user-edit page.
 */
if (!function_exists(__NAMESPACE__ . '\\add_user_edit_js')) {
    function add_user_edit_js() {
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $ajax_nonce = function_exists( __NAMESPACE__ . '\\smp_vp_ajax_nonce' ) ? smp_vp_ajax_nonce() : wp_create_nonce( Config::$ajax_nonce_action );
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var buttonHtml = '<button id="refresh_user" class="button button-secondary">Refresh Email Content</button>';
                $('.user-pass1-wrap').after(buttonHtml);

                // Handler for the Refresh User button click
                $(document).on('click', '#refresh_user', function(e) {
                    e.preventDefault();

                    $.ajax({
                        url: ajaxurl,
                        type: 'post',
                        dataType: 'json',
                        data: {
                            action: 'refresh_user',
                            user_id: <?php echo get_user_id(); ?>,
                            nonce: '<?php echo esc_js( $ajax_nonce ); ?>'
                        },
                        success: function(response) {
                            if (response && response.success) {
                                alert((response.data && response.data.message) || 'User email content refreshed.');
                            } else {
                                alert((response && response.data && response.data.message) || 'Error refreshing user email content.');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.log("AJAX Error:", textStatus, errorThrown);
                        }
                    });
                });
            });
        </script>
        <?php
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\add_user_edit_js function is already declared", true);
