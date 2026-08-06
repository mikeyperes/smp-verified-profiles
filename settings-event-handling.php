<?php namespace smp_verified_profiles;

// Hook to load custom JavaScript in wp-admin head
add_action('admin_head', __NAMESPACE__ . '\\activate_listeners');


function activate_listeners()
{
    if ( function_exists( __NAMESPACE__ . '\\smp_vp_is_settings_dashboard_request' ) && ! smp_vp_is_settings_dashboard_request() ) {
        return;
    }
    ?>
    <script>
    window.smpVP = window.smpVP || {};
    smpVP.nonce = smpVP.nonce || '<?php echo esc_js( function_exists( __NAMESPACE__ . '\\smp_vp_ajax_nonce' ) ? smp_vp_ajax_nonce() : wp_create_nonce( Config::$ajax_nonce_action ) ); ?>';
    jQuery(document).ready(function($) {
    $('#<?php echo Config::$settings_page_html_id;?> .modify-wp-config').on('click', function(e) {
        e.preventDefault();

        const constant = $(this).data('constant');
        const value = $(this).data('value');
        const target = $(this).data('target');

        $.post(ajaxurl, {
            action: '<?php echo __NAMESPACE__; ?>_modify_wp_config_constants',
            nonce: smpVP.nonce,
            constants: {
                [constant]: value
            }
        }, function(response) {
            if (response.success) {
                alert(response.data.message || 'Configuration updated successfully.');
                location.reload();
            } else {
                alert(response.data.message || 'Failed to update configuration.');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX Request Failed:', jqXHR, textStatus, errorThrown);
            alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
        });
    });
});
</script>
  <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Event handler for enabling auto-updates for all plugins
            $('#<?php echo Config::$settings_page_html_id;?> #enable-plugin-auto-updates').on('click', function(e) {
                e.preventDefault();

                $.post(ajaxurl, {
                    action: '<?php echo __NAMESPACE__; ?>_enable_plugin_auto_updates',
                    nonce: smpVP.nonce

                }, function(response) {
                    if (response.success) {
                        alert('Auto updates for all plugins have been enabled.');
                        location.reload();
                    } else {
                        alert('Failed to enable auto updates for plugins: ' + response.data.message);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                });
            });
        });
    </script>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Event handler for enabling WP Core auto-updates
            $('#<?php echo Config::$settings_page_html_id;?>r #enable-auto-updates').on('click', function(e) {
                e.preventDefault();

                $.post(ajaxurl, {
                    action: '<?php echo __NAMESPACE__; ?>_modify_wp_config_constants',
                    nonce: smpVP.nonce,

                    constants: {
                        'WP_AUTO_UPDATE_CORE': 'true'
                    }
                }, function(response) {
                    if (response.success) {
                        alert('Auto updates have been enabled.');
                        location.reload();
                    } else {
                        alert('Failed to enable auto updates: ' + response.data.message);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                });
            });
        });
    </script>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#<?php echo Config::$settings_page_html_id;?> .fix-ram-issue').on('click', function(e) {
                e.preventDefault();

                $.post(ajaxurl, {

                    action: '<?php echo __NAMESPACE__; ?>_modify_wp_config_constants',
                    nonce: smpVP.nonce,
                      constants: {
                        'WP_MEMORY_LIMIT': '4000M' // Adding the constant to update
                    }
                }, function(response) {
                    console.log('Raw AJAX Response:', response); // Log the entire response
                    console.log('Data Object:', response.data);   // Log the data object to see what's inside

                    var message = response.data ? response.data.message : 'No message received';

                    if (response.success) {
                        alert(message);
                        location.reload();
                    } else {
                        alert(message);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                    console.error('AJAX Request Failed:', jqXHR, textStatus, errorThrown); // Debugging: Log the failure details
                });
            });
        });
    </script> 



<script>
$ = jQuery;
$(document).ready(function($) {
    // Handle click event and AJAX request all in one function
    $('#<?php echo Config::$settings_page_html_id;?> .execute-function').on('click', function() {

        var methodName = $(this).data('method');  // Get the method name
        var state = $(this).data('state');  // Get the state
        var setting = $(this).data('setting');  // Get the setting name
        var variable = $(this).data('variable');  // Get the setting name
        var slug = $(this).data('slug');  // Get the setting name
        var post_type = $(this).data('post_type');  // Get the setting name
        var name = $(this).data('name');  // Get the setting name

     



        // Ensure methodName and setting are available
        if (methodName) {
         //   console.log('State passed:', state);  // Log the state for debugging
        //    console.log('Setting passed:', setting);  // Log the setting for debugging

            // Make the AJAX call to execute the function
            var dataToSend = {
                action: '<?php echo __NAMESPACE__; ?>_execute_function',  // The action to hook into on the server-side
                nonce: smpVP.nonce,
                method: methodName,          // Pass the method name
                setting: setting,            // Pass the setting name
                state: state,                // Pass the state
                variable: variable,                 // Pass the variable
                slug:slug,
                post_type:post_type,
                name:name
            };

            jQuery.ajax({
                url: ajaxurl,  // WordPress provides this for AJAX calls in the admin area
                type: 'post',
                data: dataToSend,
                success: function(response) {
                    if (response.success) {
                        alert(methodName+' executed successfully: ' + response.data);
                    } else {
                        alert('Error for '+methodName+': ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                    alert('An AJAX error occurred: ' + textStatus + ' - ' + errorThrown);
                }
            });
        } else alert('No method or setting provided.');

    });
});





  // 1) Define your namespace string once
  var ns = 'smp_verified_profiles';

  // 2) Ensure the global namespace object exists
  window[ns] = window[ns] || {};

  // 3) Define toggleSnippet under that namespace
  window[ns].toggleSnippet = function(snippetId) {
    var isChecked = jQuery('#' + snippetId).prop('checked');

    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: ns + '_toggle_snippet',
        snippet_id: snippetId,
        enable: isChecked,
        nonce: smpVP.nonce
      },
      success: function(response) {
        if (response.success) {
          alert(response.data);
        } else {
          alert('Error: ' + response.data);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
        alert('An AJAX error occurred: ' + textStatus + ' - ' + errorThrown);
      }
    });
  };

  // 4) On document ready, bind clicks to call your namespaced function
  jQuery(document).ready(function() {
    jQuery('#<?php echo __NAMESPACE__; ?> .modify-snippet-via-button').on('click', function(e) {
      e.preventDefault();
      e.stopImmediatePropagation();

      var snippetId = jQuery(this).data('snippet-id');
      var action    = jQuery(this).data('action'); // "enable" or "disable"
      if (!snippetId) {
        return;
      }

      // Toggle hidden checkbox state if needed
      var shouldEnable = (action === 'enable');
      jQuery('#' + snippetId).prop('checked', shouldEnable);

      // Call the function under our namespace
      window[ns].toggleSnippet(snippetId);
    });
  });



</script>


<?php }


if (!function_exists('smp_verified_profiles\toggle_snippet')) {
    function toggle_snippet(): void {
        \SMP\VerifiedProfiles\Admin\AdminAjaxHandlers::dispatch(
            [ \SMP\VerifiedProfiles\Bootstrap\Plugin::instance()->snippet_controller(), 'toggle' ]
        );
    }
} else write_log("Warning: " . __NAMESPACE__ . "/smp_verified_profiles/toggle_snippet function is already declared", true);



   


function modify_wp_config_constants_handler(): void {
    \SMP\VerifiedProfiles\Admin\AdminAjaxHandlers::dispatch(
        [ \SMP\VerifiedProfiles\Bootstrap\Plugin::instance()->ajax_handlers(), 'modify_wp_config_constants' ]
    );
}


function handle_execute_function_ajax(): void {
    \SMP\VerifiedProfiles\Admin\AdminAjaxHandlers::dispatch(
        [ \SMP\VerifiedProfiles\Bootstrap\Plugin::instance()->ajax_handlers(), 'execute_allowed_function' ]
    );
}
?>
