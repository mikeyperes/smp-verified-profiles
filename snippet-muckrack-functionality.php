<?php namespace smp_verified_profiles;

function get_muckrack_shortcodes() {
    return [
        'display_profile_muckrack_verified' => __NAMESPACE__ . '\\display_profile_muckrack_verified',
        'muckrack_verified' => __NAMESPACE__ . '\\muckrack_verified',
        'acf_author_field' => __NAMESPACE__ . '\\acf_author_field_shortcode',
        'verified_icon_author' => __NAMESPACE__ . '\\verified_icon_author',
        'verified_single' => __NAMESPACE__ . '\\muckrack_single',
        'verified_author' => __NAMESPACE__ . '\\muckrack_author',
        'verified_icon_single' => __NAMESPACE__ . '\\verified_icon_single',
        // Make sure you don't duplicate the shortcode as seen in the original version
    ];
}


function enable_snippet_muckrack_functionality(){
   // Retrieve the shortcodes and their callbacks
   $shortcodes = get_muckrack_shortcodes();
    
   // Loop through each shortcode and register it
   foreach ($shortcodes as $shortcode => $callback) {
       add_shortcode($shortcode, $callback);
   }
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
if ( ! function_exists( __NAMESPACE__ . '\\muckrack_verified' ) ) {
    function muckrack_verified( $atts ) {

        if ( ! check_plugin_acf() ) {
            return;
        }

        global $post;
        $author_id = $post->post_author;

        // Define default attributes for type, color, size, class and id.
        $atts = shortcode_atts( array(
            'type'  => 'icon',
            'color' => '',         // Custom color for the SVG
            'size'  => '20px',     // Size of the icon (width and height)
            'class' => '',         // Entire CSS selector provided via class attribute
            'id'    => '',         // Optional ID attribute
        ), $atts, 'muckrack_verified' );

        // Retrieve user meta fields.
        $muckrack_verified  = get_field( 'profiles_muckrack_verified', 'user_' . $author_id );
        $muckrack_url       = get_field( 'profiles_muckrack', 'user_' . $author_id );
        $author_description = get_field( 'what_best_describe_you', 'user_' . $author_id );

        if ( ! $muckrack_verified ) {
            return '';
        }

        // If "text" type is chosen and a MuckRack URL is available, output the text variant.
        if ( 'text' === $atts['type'] && ! empty( $muckrack_url ) ) {
            return $author_description . ' verified by <span style="color: #2d5277; font-weight: bold;">MuckRack\'s</span> editorial team <a href="' . esc_url( $muckrack_url ) . '" target="_blank"> (learn more) <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>';
        }

        // Determine the fill color: use the provided color or fallback to primary CSS variable.
        $fillColor = ! empty( $atts['color'] ) ? $atts['color'] : 'var(--e-global-color-primary)';
        $size      = esc_attr( $atts['size'] );

        // For inline SVG output, include a <title> element for the native tooltip.
        $inline_svg = '<svg style="vertical-align:middle;width:' . $size . 'px;fill:' . esc_attr( $fillColor ) . ';" aria-hidden="true" class="e-font-icon-svg e-fas-check-circle" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                            <title>This entity has been verified by MuckRack\'s editorial team.</title>
                            <path d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z"></path>
                        </svg>';

        // Build the target CSS selector using either the provided id or full class attribute.
        $targetSelector = '';
        if ( ! empty( $atts['id'] ) ) {
            $id = trim( $atts['id'] );
            $targetSelector = ( strpos( $id, '#' ) === 0 ) ? $id : '#' . $id;
        } elseif ( ! empty( $atts['class'] ) ) {
            $rawSelector = trim( $atts['class'] );
            // Convert each token into a valid CSS selector.
            $tokens = preg_split( '/\s+/', $rawSelector );
            $selectorParts = array();
            foreach ( $tokens as $token ) {
                $token = trim( $token );
                if ( $token !== '' ) {
                    if ( strpos( $token, '.' ) !== 0 && strpos( $token, '#' ) !== 0 ) {
                        $token = '.' . $token;
                    }
                    $selectorParts[] = $token;
                }
            }
            // Join tokens with a space.
            $targetSelector = implode( ' ', $selectorParts );
        }

        // If a target selector is provided, inject the check mark via CSS and attach tooltip behavior.
        if ( ! empty( $targetSelector ) ) {
            // Build the SVG without a <title> (since the tooltip will be handled via JS).
            $svg_no_title = '<svg style="vertical-align:middle;width:' . $size . 'px;fill:' . esc_attr( $fillColor ) . ';" aria-hidden="true" class="e-font-icon-svg e-fas-check-circle" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                                <path d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z"></path>
                             </svg>';
            $svg_clean   = preg_replace( '/\s+/', ' ', $svg_no_title );
            $encoded_svg = base64_encode( $svg_clean );

            // Build the CSS rule to inject the SVG as a background image on the ::after pseudo-element.
            $cssRule = $targetSelector . '::after { 
                        content: "";
                        display: inline-block;
                        width: ' . $size . ';
                        height: ' . $size . ';
                        background-image: url("data:image/svg+xml;base64,' . $encoded_svg . '");
                        background-size: contain;
                        background-repeat: no-repeat;
                        vertical-align: middle;
                        margin-left: 5px;
                        cursor: pointer;
                    }';
            // Define the tooltip text.
            $tooltipText = "This entity has been verified by MuckRack's editorial team.";

            // Output a script block that injects the style and attaches tooltip behavior via jQuery.
            $output = '<script>
            jQuery(document).ready(function($) {
                // Append the CSS rule.
                var styleEl = $("<style type=\'text/css\'></style>");
                styleEl.text(' . json_encode( $cssRule ) . ');
                $("head").append(styleEl);
    
                // Create a tooltip element with enhanced styling.
                var tooltip = $("<div>", {
                    text: ' . json_encode( $tooltipText ) . ',
                    css: {
                        position: "absolute",
                        background: "#333",
                        color: "#fff",
                        padding: "6px 12px",
                        fontSize: "13px",
                        borderRadius: "4px",
                        display: "none",
                        zIndex: 1000,
                        boxShadow: "0 4px 8px rgba(0,0,0,0.3)"
                    }
                });
                $("body").append(tooltip);
    
                // Attach hover events to show/hide the tooltip.
                $("' . $targetSelector . '").hover(
                    function(e) {
                        var offset = $(this).offset();
                        tooltip.css({
                            top: offset.top - tooltip.outerHeight() - 5,
                            left: offset.left
                        }).fadeIn(200);
                    },
                    function() {
                        tooltip.fadeOut(200);
                    }
                );
            });
            </script>';
    
            return $output;
        } else {
            // No selector provided; return the inline SVG with native tooltip.
            return $inline_svg;
        }
    }
} else {
    write_log( "⚠️ Warning: " . __NAMESPACE__ . "\\muckrack_verified function is already declared", true );
}

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