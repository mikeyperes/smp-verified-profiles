<?php namespace smp_verified_profiles; 


function enable_snippet_wp_admin_user_page_optional_functionality(){
// Hook the function into the 'admin_footer' action
add_action('admin_footer', __NAMESPACE__.'\user_page_shortcut_functionality', 1);
}

/**
 * Provides additional functionality on the user edit page, such as auto-populating fields and updating emails.
 * This function applies only for administrators.
 */
if (!function_exists(__NAMESPACE__ . '\\user_page_shortcut_functionality')) {
    function user_page_shortcut_functionality() {
        if (!current_user_can('administrator')) return;

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Uncheck the "Send User Notification" checkbox by default
                $('#send_user_notification').prop('checked', false);

                // Attach event handlers to update fields when they lose focus
                $('#email, #user_login, #pass1, #url').on('blur', updateFields);

                // Function to update various fields based on the entered email
                function updateFields() {
                    // Standardize the email to lowercase
                    var emailValue = $('#email').val().toLowerCase();
                    $('#email').val(emailValue);

                    // Extract the username and domain from the email
                    var emailParts = emailValue.split('@');
                    var specialDomains = ['gmail.com', 'hotmail.com', 'outlook.com'];
                    var domain = emailParts.length === 2 ? emailParts[1] : '';
                    var isSpecialDomain = specialDomains.includes(domain);

                    // Set the username and website based on the email domain
                    var username = isSpecialDomain ? emailParts[0] : (domain.split('.')[0]);
                    var website = isSpecialDomain ? '' : 'https://' + domain;

                    // Update the username and website fields if they are empty
                    if ($('#user_login').val() === '') {
                        $('#user_login').val(username);
                    }
                    if ($('#url').val() === '') {
                        $('#url').val(website);
                    }

                    // Update the password in a custom ACF field if it's set
                    var password = $('#pass1').val();
                    if (password) {
                        $('input[name="acf[field_659f267203e55]"]').val(password);
                    }

                    // Update notification emails based on the entered email
                    updateNotificationEmails(emailValue);
                }

                // Function to split first and last names
                function splitName() {
                    // Retrieve and trim the first and last name values
                    var firstName = $('#first_name').val().trim();
                    var lastName = $('#last_name').val().trim();

                    // Split the first name if it contains a space
                    if (firstName.includes(' ')) {
                        var nameParts = firstName.split(' ');
                        if (nameParts.length > 1) {
                            $('#first_name').val(capitalizeFirstLetter(nameParts[0]));
                            $('#last_name').val(capitalizeFirstLetter(nameParts[nameParts.length - 1]));
                        }
                    }
                }

                // Function to capitalize the first letter of a string
                function capitalizeFirstLetter(string) {
                    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
                }

                // Function to validate an email format
                function validateEmail(email) {
                    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                }

                // Function to update notification emails
                function updateNotificationEmails(email) {
                    // Return if the email is not valid
                    if (!validateEmail(email)) return;

                    // Check if the email already exists in the notification list
                    var emailExists = false;
                    $('.acf-field[data-name="notification_emails"] .acf-row:not(.acf-clone)').each(function() {
                        var currentEmail = $(this).find('.acf-field[data-name="email"] input').val();
                        if (currentEmail === email) {
                            emailExists = true;
                            return false; // Break the loop
                        }
                    });

                    // Add the email if it doesn't already exist
                    if (!emailExists) {
                        $('.acf-field[data-name="notification_emails"] .acf-button').click();
                        var newRow = $('.acf-field[data-name="notification_emails"] .acf-row:not(.acf-clone)').last();
                        newRow.find('.acf-field[data-name="email"] input').val(email);
                    }
                }
            });
        </script><?php
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\user_page_shortcut_functionality function is already declared", true);