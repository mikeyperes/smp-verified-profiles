<?php namespace smp_verified_profiles;

function enable_snippet_woocommerce_stripe_integration()
{
    add_filter('wc_stripe_payment_intent_args', 'modify_stripe_payment_intent_args', 10, 2);
}

if (!function_exists(__NAMESPACE__ . '\\my_hooked_function_callback')) {
    function my_hooked_function_callback($order) {
        // Log or perform actions on the $order object if needed
        // write_log($order);
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\my_hooked_function_callback function is already declared", true);

if (!function_exists(__NAMESPACE__ . '\\modify_stripe_payment_intent_args')) {
    function modify_stripe_payment_intent_args($args, $order) {
        if (!method_exists($order, 'get_meta')) return $args;

        // Extract the profile name from the order meta
        $profile_name = $order->get_meta('order_profile_name');

        // Extract the full name, email, and order ID
        $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $email = $order->get_billing_email();
        $order_id = $order->get_id();

        // Initialize profile type
        $profile_type = "";

        // Check each item in the order to determine the profile type
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == 14140) {
                $profile_type = "person";
                break;
            } elseif ($item->get_product_id() == 14141) {
                $profile_type = "organization";
                break;
            }
        }

        // Set the description for Stripe payment intent
        $args['description'] = "HF, Verified Profile: " . $profile_name . "[" . $profile_type . "] (" . $full_name . "<" . $email . ">)";

        return $args;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\modify_stripe_payment_intent_args function is already declared", true);