<?php
// Include the plugin.php file if it hasn't been included yet
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Function to check if a plugin is installed, active, and auto update is enabled
function check_plugin_status($plugin) {
    $is_installed = file_exists(WP_PLUGIN_DIR . '/' . $plugin);
    $is_active = is_plugin_active($plugin);
    $auto_updates = get_option('auto_update_plugins', []);
    $is_auto_update_enabled = in_array($plugin, $auto_updates);
    return [$is_installed, $is_active, $is_auto_update_enabled];
}

// Function to check if a user exists by login
function does_user_exist($login) {
    return get_user_by('login', $login) !== false;
}

// Function to check if a custom post type exists
function does_post_type_exist($post_type) {
    return post_type_exists($post_type);
}

// Function to check if the Hello Elementor theme is active
function is_hello_elementor_theme_active() {
    return wp_get_theme()->get('Name') === 'Hello Elementor';
}

// Function to check if auto updates are enabled for the Hello Elementor theme
function is_hello_elementor_theme_auto_update_enabled() {
    $theme_updates = get_option('auto_update_themes', []);
    return in_array('hello-elementor', $theme_updates);
}

// Display check status
function display_check_status($condition, $message) {
    $color = $condition ? 'green' : 'red';
    $icon = $condition ? '&#x2705;' : '&#x274C;';
    echo "<span style='color: $color;'>$icon $message</span>";
}

// Plugins list
function get_plugins_list() {
    return [
        'advanced-custom-fields/acf.php' => 'ACF',
        'elementor/elementor.php' => 'Elementor',
        'elementor-pro/elementor-pro.php' => 'Elementor Pro',
        'jet-engine/jet-engine.php' => 'JetEngine',
        'seo-by-rank-math/rank-math.php' => 'Rank Math SEO',
        'seo-by-rank-math-pro/rank-math-pro.php' => 'Rank Math Pro',
        'woo-checkout-field-editor-pro/woocommerce-checkout-field-editor-pro.php' => 'Checkout Field Editor for WooCommerce',
        'woocommerce/woocommerce.php' => 'WooCommerce',
        'woocommerce-subscriptions/woocommerce-subscriptions.php' => 'WooCommerce Subscriptions',
        'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php' => 'Payment Plugins for Stripe WooCommerce',
    ];
}

// Perform plugin and theme prechecks
function perform_verified_profiles_plugin_prechecks() {return;
    $plugins = get_plugins_list();
    $messages = [];

    foreach ($plugins as $plugin => $name) {
        list($is_installed, $is_active, $is_auto_update_enabled) = check_plugin_status($plugin);
        if (!$is_installed) {
            $messages[] = "<p><strong>{$name} not installed:</strong> The {$name} plugin is not installed.</p>";
        } elseif (!$is_active) {
            $messages[] = "<p><strong>{$name} not enabled:</strong> The {$name} plugin is not active. Please activate it to use the Hello World Plugin.</p>";
        } elseif (!$is_auto_update_enabled) {
            $messages[] = "<p><strong>{$name} auto updates not enabled:</strong> Please enable automatic updates for the {$name} plugin.</p>";
        }
    }

    if (!does_post_type_exist('profile')) {
        $messages[] = '<p><strong>"Profile" Custom Post Type not enabled:</strong> The "profile" custom post type is not active. Please register it to use the Hello World Plugin.</p>';
    }

    if (!does_user_exist('unclaimed-profile')) {
        $messages[] = '<p><strong>"Unclaimed-profile" user not found:</strong> The "unclaimed-profile" user does not exist. Please create this user to use the Hello World Plugin.</p>';
    }

    if (!is_hello_elementor_theme_active()) {
        $messages[] = '<p><strong>Hello Elementor theme not active:</strong> The Hello Elementor theme is not active. Please activate it to use the Hello World Plugin.</p>';
    } elseif (!is_hello_elementor_theme_auto_update_enabled()) {
        $messages[] = '<p><strong>Hello Elementor theme auto updates not enabled:</strong> Please enable automatic updates for the Hello Elementor theme.</p>';
    }

    if (!empty($messages)) {
        add_action('admin_notices', function() use ($messages) {
            echo '<div class="notice notice-error is-dismissible">';
            foreach ($messages as $message) {
                echo $message;
            }
            echo '</div>';
        });
    }
}

// Function to check if a taxonomy exists
function does_taxonomy_exist($taxonomy) {
    return taxonomy_exists($taxonomy);
}

// Function to check if a term exists within a taxonomy
function does_term_exist($term, $taxonomy) {
    return term_exists($term, $taxonomy) !== 0 && term_exists($term, $taxonomy) !== null;
}


// Function to display the check status
function verified_profiles_dashboard_display_check_status($is_active, $active_message, $inactive_message) {
    if ($is_active) {
        echo '<span style="color: green;">&#x2705; ' . $active_message . '</span>';
    } else {
        echo '<span style="color: red;">&#x274C; ' . $inactive_message . '</span>';
    }
}


// MOVE THIS TO A BETTER LOCATION? 


// Function to create the "person" and "company" categories
function create_verified_profile_categories() {
    if (!does_term_exist('person', 'category')) {
        wp_insert_term('Person', 'category', ['slug' => 'person']);
    }
    if (!does_term_exist('organization', 'category')) {
        wp_insert_term('Organization', 'category', ['slug' => 'orgnization']);
    }
}

// Hook the create_verified_profile_categories function to a custom AJAX action
add_action('wp_ajax_create_verified_profile_categories', 'create_verified_profile_categories');

//Disable this notification for now 
//add_action('admin_init', 'perform_verified_profiles_plugin_prechecks');
?>