<?php namespace smp_verified_profiles;

function enable_snippet_muckrack_functionality(){
// Shortcodes
add_shortcode('display_profile_muckrack_verified', __NAMESPACE__.'\display_profile_muckrack_verified');
add_shortcode('muckrack_verified', __NAMESPACE__.'\muckrack_verified');
add_shortcode('acf_author_field', __NAMESPACE__.'\acf_author_field_shortcode');
add_shortcode('verified_icon_author', __NAMESPACE__.'\verified_icon_author');
add_shortcode('verified_single', __NAMESPACE__.'\muckrack_single');
add_shortcode('verified_author', __NAMESPACE__.'\muckrack_author');
add_shortcode('verified_icon_single', __NAMESPACE__.'\verified_icon_single');
add_shortcode('acf_author_field', __NAMESPACE__.'\acf_author_field_shortcode');
}

// Shortcode for Muckrack verification on single profile page
if (!function_exists(__NAMESPACE__ . '\\muckrack_single')) {
    function muckrack_single() {
        if (!check_plugin_acf()) return;

        global $post;
        $author_id = $post->post_author;
        $muckrack_image_url = "/wp-content/uploads/2022/07/Muck-Rack-.png"; // Placeholder for image
        $verified_field = get_field('is_verified', 'user_' . $author_id);

        if ($verified_field == 'true') {
            return '<span class="muckrack-text">Journalist verified by <span style="color:#2D5277;font-weight:800">MuckRack\'s</span> editorial team </span>';
        }
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\muckrack_single function is already declared", true);
}

// Shortcode for Muckrack verification by author nicename
if (!function_exists(__NAMESPACE__ . '\\muckrack_author')) {
    function muckrack_author($atts) {
        if (!check_plugin_acf()) return;

        global $wpdb;
        $pageURL = $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $nicename = str_replace(array("herforward.com/author/", "/"), "", $pageURL);

        // Get author ID based on nicename
        $author_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM fUaKOcQVM_users WHERE user_nicename = %s", $nicename));
        $verified_field = get_field('is_verified', 'user_' . $author_id);

        if ($verified_field == 'true') {
            return '<span class="muckrack-text">Journalist verified by <span style="color:#2D5277;font-weight:800">MuckRack\'s</span> editorial team </span>';
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\muckrack_author function is already declared", true);


// Shortcode for displaying verified icon on a single profile page
if (!function_exists(__NAMESPACE__ . '\\verified_icon_single')) {
    function verified_icon_single() {
        if (!check_plugin_acf()) return;

        $post_id = get_the_ID();
        $author_id = get_post_field('post_author', $post_id);
        $verified_field = get_field('is_verified', 'user_' . $author_id);
        $verified_image_url = "/wp-content/uploads/2022/07/checkmark.svg"; // Placeholder for image

        if ($verified_field == 'true') {
            return '<img src="' . $verified_image_url . '" class="verified_box_single">';
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\verified_icon_single function is already declared", true);


// Shortcode for displaying verified icon for an author
if (!function_exists(__NAMESPACE__ . '\\verified_icon_author')) {
    function verified_icon_author($atts) {
        if (!check_plugin_acf()) return;

        global $wpdb;
        $pageURL = $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $nicename = str_replace(array("herforward.com/author/", "/"), "", $pageURL);

        $author_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM fUaKOcQVM_users WHERE user_nicename = %s", $nicename));
        $verified_field = get_field('is_verified', 'user_' . $author_id);
        $verified_image_url = "/wp-content/uploads/2022/07/checkmark.svg"; // Placeholder for image

        if ($verified_field == 'true') {
            return '<img src="' . $verified_image_url . '" class="verified_icon_author">';
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\verified_icon_author function is already declared", true);


// Shortcode to fetch and display a custom author field using ACF
if (!function_exists(__NAMESPACE__ . '\\acf_author_field_shortcode')) {
    function acf_author_field_shortcode($atts) {
        if (!check_plugin_acf()) return;

        $atts = shortcode_atts(array('field' => null), $atts);

        if ($atts['field'] === null) {
            return '';
        }

        global $post;
        $author_id = $post->post_author;
        $field_value = get_field($atts['field'], 'user_' . $author_id);

        return $field_value ? $field_value : '';
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\acf_author_field_shortcode function is already declared", true);


// Shortcode for MuckRack verified status
if (!function_exists(__NAMESPACE__ . '\\muckrack_verified')) {
    function muckrack_verified($atts) {
        if (!check_plugin_acf()) return;

        global $post;
        $author_id = $post->post_author;

        $atts = shortcode_atts(array('type' => 'icon'), $atts, 'muckrack_verified');

        $muckrack_verified = get_field('muckrack_verified', 'user_' . $author_id);
        $muckrack_url = get_field('muckrack_url', 'user_' . $author_id);
        $author_description = get_field('what_best_describe_you', 'user_' . $author_id);

        if (!$muckrack_verified) {
            return '';
        }

        if ($atts['type'] === 'text' && !empty($muckrack_url)) {
            return $author_description . ' verified by <span style="color: #2d5277; font-weight: bold;">MuckRack\'s</span> editorial team <a href="' . esc_url($muckrack_url) . '" target="_blank"> (learn more) <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>';
        }

        return '<i aria-hidden="true" class="fas fa-check-circle" style="color:var(--e-global-color-primary );"></i>';
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\muckrack_verified function is already declared", true);

// Shortcode for displaying MuckRack verified profiles
if (!function_exists(__NAMESPACE__ . '\\display_profile_muckrack_verified')) {
    function display_profile_muckrack_verified($atts) {
        global $post;

        if (get_field("social_profiles_muckrack_verified", $post->ID) && get_field("social_profiles_muckrack_url", $post->ID) != "") {
            $muckrack_url = get_field("social_profiles_muckrack_url", $post->ID);
            $profile_name = get_the_title($post->ID);

            return '<div class="display_profile_muckrack_verified shortcode_display_profile_muckrack_verified">' . $profile_name . ' is verified by <span style="color: #2d5277; font-weight: bold;">MuckRack\'s</span> editorial team <a href="' . esc_url($muckrack_url) . '" target="_blank"> (learn more) <i class="fas fa-external-link-alt" aria-hidden="true"></i></a></div>';
        } else {
            return "<style>.display_profile_muckrack_verified{display:none !important}</style>";
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\display_profile_muckrack_verified function is already declared", true);
?>