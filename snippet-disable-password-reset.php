<?php namespace smp_verified_profiles;

function enable_snippet_disable_password_reset(){
    // Hook into the filter to modify the password reset email behavior
    add_filter('send_password_change_email', __NAMESPACE__ . '\\disable_password_reset_email_for_logged_in_users', 10, 1000);
}

if (!function_exists(__NAMESPACE__ . '\\disable_password_reset_email_for_logged_in_users')) {
    /**
     * Disables the password reset email for logged-in users who are resetting their own password.
     *
     * @param bool $allow Whether to allow the password reset email to be sent.
     * @param int $user_id The ID of the user whose password is being reset.
     * @return bool Modified value of $allow, disabling the email if conditions are met.
     */
    function disable_password_reset_email_for_logged_in_users( $allow, $user_id ) {
        // Check if the user is logged in
        if ( is_user_logged_in() ) {
            // Get current user data
            $current_user = wp_get_current_user();

            // Disable email if the current user is resetting their own password
            if ( $current_user->ID == $user_id ) {
                $allow = false;
            }
        }

        // Disable the password reset email in the admin area
        if ( is_admin() ) {
            $allow = false;
        }

        return $allow;
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\disable_password_reset_email_for_logged_in_users function is already declared", true);