<?php namespace smp_verified_profiles;


function enable_snippet_woocommerce_base(){
add_action('init', __NAMESPACE__.'\custom_add_to_cart_redirect');
add_action('woocommerce_order_status_processing', __NAMESPACE__.'\custom_actions_after_successful_order', 10, 1);
add_action('woocommerce_order_status_completed', __NAMESPACE__.'\custom_actions_after_successful_order', 10, 1);
add_shortcode('woocommerce_account_dashboard', __NAMESPACE__.'\woocommerce_display_account_dashboard');
add_action('woocommerce_before_calculate_totals', __NAMESPACE__.'\woocommerce_adjust_price_for_products', 10, 1);

    // Add filter to save custom data in the cart
    add_filter('woocommerce_add_cart_item_data', __NAMESPACE__ . '\\custom_save_data_in_cart_object', 10, 3);
    add_action('template_redirect', __NAMESPACE__.'\custom_cart_checkout_flow');

// Add actions for logged-in and logged-out users
add_action('wp_ajax_checkout_get_profile_data', __NAMESPACE__ . '\\checkout_get_profile_data');
add_action('wp_ajax_nopriv_checkout_get_profile_data', __NAMESPACE__ . '\\checkout_get_profile_data');
}

if (!function_exists(__NAMESPACE__ . '\\woocommerce_display_account_dashboard')) {
 function woocommerce_display_account_dashboard() {
        if (!function_exists('wc_get_template')) return; // Prevent errors if WooCommerce is not active
        return wc_get_template(
            'myaccount/my-account.php',
            array(
                'current_user' => get_user_by('id', get_current_user_id())
            )
        );
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\woocommerce_display_account_dashboard function is already declared", true);


if (!function_exists(__NAMESPACE__ . '\\custom_cart_checkout_flow')) {
   
    function custom_cart_checkout_flow() {
        if (function_exists('is_cart') && is_cart() && !WC()->cart->is_empty()) {
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\custom_cart_checkout_flow function is already declared", true);

if (!function_exists(__NAMESPACE__ . '\\custom_add_to_cart_redirect')) {
  
    function custom_add_to_cart_redirect() {
        if (isset($_GET['add-to-cart'])) {
            $product_id = intval($_GET['add-to-cart']);
            WC()->cart->empty_cart();

            if ($product_id > 0 && !WC()->cart->find_product_in_cart(WC()->cart->generate_cart_id($product_id))) {
                WC()->cart->add_to_cart($product_id);
            }

            $query_args = $_GET;
            unset($query_args['add-to-cart']); // Remove 'add-to-cart' to avoid repetition
            wp_safe_redirect(add_query_arg($query_args, wc_get_checkout_url()));
            exit;
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\custom_add_to_cart_redirect function is already declared", true);

if (!function_exists(__NAMESPACE__ . '\\custom_actions_after_successful_order')) {
  function custom_actions_after_successful_order($order_id) {
        if (!function_exists('wc_get_order')) return; // Prevent errors if WooCommerce is not active

        $order = wc_get_order($order_id);
        $product_ids_to_check = [14140, 14141];
        $contains_product = false;

        foreach ($order->get_items() as $item) {
            if (in_array($item->get_product_id(), $product_ids_to_check)) {
                $contains_product = true;
                break;
            }
        }

        if ($contains_product) {
            $user_id = $order->get_user_id() ? $order->get_user_id() : $order->get_customer_id();
            $user = new \WP_User($user_id);

            if (in_array('administrator', (array) $user->roles)) return;
            else if (in_array('customer', (array) $user->roles)) $user->set_role('verified_profile_manager');
        }

        if (!$contains_product) return;

        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $new_user_created = false;
        $user_password = '';

        if (!$user_id) {
            $customer_email = $order->get_billing_email();
            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

            if (!email_exists($customer_email)) {
                $user_password = wp_generate_password();
                $user_id = wp_create_user($customer_email, $user_password, $customer_email);

                if (is_wp_error($user_id)) return;

                $new_user_created = true;
                $user = new \WP_User($user_id);
                $user->set_role('verified_profile_manager');

                wp_mail($customer_email, 'Welcome to Verified Profile Manager', "Hello $customer_name,\n\nYour account has been created. Email: $customer_email\nPassword: $user_password", array('Content-Type: text/plain; charset=UTF-8'));
            }
        }

        if (!$user_id || is_wp_error($user_id)) return;

        $order_profile_name = $order->get_meta('order_profile_name');
        $unclaimed_profile_id = $_POST['unclaimed_profile_id'] ?? null;

        if ($unclaimed_profile_id && $unclaimed_profile_id != -1) {
            wp_update_post(array('ID' => $unclaimed_profile_id, 'post_author' => $user_id));

            if (function_exists('have_rows') && function_exists('delete_row')) {
                while (have_rows('unclaimed_profiles', 'user_' . $user_id)) {
                    the_row();
                    if (get_sub_field('profile') == $unclaimed_profile_id) {
                        delete_row('unclaimed_profiles', get_row_index(), 'user_' . $user_id);
                        break;
                    }
                }
            }
        } else {
            $order_profile_type = $order->get_meta('order_profile_type');
            $profile_type_slug = strtolower($order_profile_type);
            $default_category_id = get_category_by_slug($profile_type_slug)->term_id;
            $post_title = $order_profile_name ? $order_profile_name . " - New Profile" : "New Profile: " . $customer_name;

            $post_id = wp_insert_post(array(
                'post_title' => $post_title,
                'post_content' => '',
                'post_status' => 'draft',
                'post_type' => 'profile',
                'post_author' => $user_id,
                'post_category' => array($default_category_id)
            ));

            if (!is_wp_error($post_id)) {
                $entity_type = $item->get_product_id() == 14140 ? 'Person' : 'Organization';
                update_field('entity_type', $entity_type, $post_id);
            }
        }

        update_post_meta($order_id, '_custom_actions_performed', true);
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\custom_actions_after_successful_order function is already declared", true);

if (!function_exists(__NAMESPACE__ . '\\woocommerce_adjust_price_for_products')) {
    function woocommerce_adjust_price_for_products($cart) {
        if (!check_plugin_acf()) return;
        $product_id_verified_profile = 14140;
        $product_id_leadership_council = 14142;
        $user = wp_get_current_user();

        if (!$user->exists()) return;

        $price_verified_profile = get_field('price_verified_profile', 'user_' . $user->ID);
        $price_leadership_council = get_field('price_leadership_council', 'user_' . $user->ID);

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $product_id_verified_profile || $cart_item['product_id'] == $product_id_leadership_council) {
                $price = $cart_item['product_id'] == $product_id_verified_profile ? $price_verified_profile : $price_leadership_council;

                if (!empty($price) && is_numeric($price)) {
                    $cart_item['data']->set_price($price);
                }
            }
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\woocommerce_adjust_price_for_products function is already declared", true);


// CHECKOUT CODE, MERGED SNIPPPET

// Ensure the function is not declared already
if (!function_exists(__NAMESPACE__ . '\\checkout_get_profile_data')) {
    // Get profile data via AJAX for the checkout form
    function checkout_get_profile_data() {
        // Retrieve profile ID from the GET request
        $profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;
        $profile_post = get_post($profile_id);

        // Check if the profile post exists and is of the correct post type
        if (!$profile_post || $profile_post->post_type != 'profile') {
            wp_send_json_error('Profile not found');
        }

        // Retrieve the category of the profile
        $profile_category = wp_get_post_terms($profile_post->ID, 'category', array("fields" => "names"));
        $profile_category = !empty($profile_category) ? $profile_category[0] : '';

        // Prepare profile data array
        $profile_data = array(
            'id' => $profile_post->ID,
            'url' => get_permalink($profile_post),
            'name' => get_the_title($profile_post),
            'slug' => $profile_post->post_name,
            'type' => $profile_category
        );

        // Send success response with profile data
        wp_send_json_success($profile_data);
    }

 } else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\checkout_get_profile_data function is already declared", true);

// Ensure the function is not declared already
if (!function_exists(__NAMESPACE__ . '\\custom_save_data_in_cart_object')) {
    // Save custom data in the WooCommerce cart object
    function custom_save_data_in_cart_object( $cart_item_data, $product_id, $variation_id ) {
        // Check if 'unclaimed_profile_id' is present in the URL and add it to the cart item data
        if (isset($_GET['unclaimed_profile_id'])) {
            $cart_item_data['unclaimed_profile_id'] = $_GET['unclaimed_profile_id'];
        }
        return $cart_item_data;
    }

} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\custom_save_data_in_cart_object function is already declared", true);