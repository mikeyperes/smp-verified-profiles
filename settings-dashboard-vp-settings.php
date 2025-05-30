<?php namespace smp_verified_profiles;

if ( ! defined('ABSPATH') ) exit;

// on activation, set defaults
function activate_verified_profile_settings() {
    $defaults = [
        'singular' => 'Verified Profile',
        'plural'   => 'Verified Profiles',
        'slug'     => 'profile',
    ];
    if ( false === get_option('smp_verified_profile_settings') ) {
        add_option('smp_verified_profile_settings', $defaults);
    }
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\activate_verified_profile_settings');

// add admin menu page
function register_verified_profile_settings_page() {
    add_menu_page(
        'Verified Profile Settings',
        'Verified Profiles',
        'manage_options',
        'verified-profile-settings',
        __NAMESPACE__ . '\\display_verified_profile_settings',
        'dashicons-admin-users',
        80
    );
}
add_action('admin_menu', __NAMESPACE__ . '\\register_verified_profile_settings_page');

// handle form submission
function save_verified_profile_settings() {
    if ( empty($_POST['submit_verified_profile_settings']) ) return;
    if ( ! current_user_can('manage_options') ) return;
    check_admin_referer('nonce_verified_profile_settings','nonce_field');
    $in = $_POST['smp_verified_profile_settings'];
    $opts = [
        'singular' => sanitize_text_field($in['singular'] ?? ''),
        'plural'   => sanitize_text_field($in['plural']   ?? ''),
        'slug'     => sanitize_title($in['slug'] ?? ''),
    ];
    update_option('smp_verified_profile_settings', $opts);
    add_settings_error('verified_profile_messages','verified_profile_saved','Settings saved.','updated');
}
add_action('admin_init', __NAMESPACE__ . '\\save_verified_profile_settings');

// output the settings panel
function display_verified_profile_settings() {
    $opts = get_option('smp_verified_profile_settings', []);
    $singular = esc_attr($opts['singular'] ?? 'Verified Profile');
    $plural   = esc_attr($opts['plural']   ?? 'Verified Profiles');
    $slug     = esc_attr($opts['slug']     ?? 'profile');
    ?>
    <div class="panel">
      <h2 class="panel-title">Verified Profile Settings</h2>
      <small><a href="<?php echo admin_url('edit.php?post_type=' . $slug); ?>" target="_blank">view CPT entries</a></small>
      <h3>Custom Post Type for Verified Profile Settings</h3>
      <?php settings_errors('verified_profile_messages'); ?>
      <form method="post" action="">
        <?php wp_nonce_field('nonce_verified_profile_settings','nonce_field'); ?>
        <table class="form-table">
          <tr>
            <th><label for="field_verified_profile_singular">Verified Profile Name (Singular)</label></th>
            <td><input name="smp_verified_profile_settings[singular]" type="text" id="field_verified_profile_singular" value="<?php echo $singular; ?>" class="regular-text"></td>
          </tr>
          <tr>
            <th><label for="field_verified_profile_plural">Verified Profile Name (Plural)</label></th>
            <td><input name="smp_verified_profile_settings[plural]" type="text" id="field_verified_profile_plural" value="<?php echo $plural; ?>" class="regular-text"></td>
          </tr>
          <tr>
            <th><label for="field_verified_profile_slug">Verified Profile Slug</label></th>
            <td><input name="smp_verified_profile_settings[slug]" type="text" id="field_verified_profile_slug" value="<?php echo $slug; ?>" class="regular-text"></td>
          </tr>
        </table>
        <?php submit_button('Save Changes','primary','submit_verified_profile_settings'); ?>
      </form>

      <?php
      // Render ACF fields on this options page
      if ( function_exists('acf_add_local_field_group') ) {
          // ACF will inject fields below
      }
      ?>
    </div>
    <?php
}

// Register additional ACF fields on the same settings page
add_action('acf/init', __NAMESPACE__ . '\\register_acf_additional_fields');
function register_acf_additional_fields() {
    if ( ! function_exists('acf_add_local_field_group') ) return;

    acf_add_local_field_group([
        'key'      => 'group_verified_profile_additional',
        'title'    => 'Additional Verified Profile Settings',
        'fields'   => [
            ['key'=>'field_verified_profile_program_name','label'=>'Verified Profile Program Name','name'=>'verified_profile_program_name','type'=>'text'],
            ['key'=>'field_contributor_network_program_name','label'=>'Contributor Network Program Name','name'=>'contributor_network_program_name','type'=>'text'],
            ['key'=>'field_profile_fallback_image','label'=>'Profile Fallback Image','name'=>'profile_fallback_image','type'=>'image','return_format'=>'id','preview_size'=>'thumbnail'],
            ['key'=>'field_verified_profile_program_logo','label'=>'Verified Profile Program Logo','name'=>'verified_profile_program_logo','type'=>'image','return_format'=>'id','preview_size'=>'thumbnail'],
            ['key'=>'field_contributor_network_profile_logo','label'=>'Contributor Network Profile Logo','name'=>'contributor_network_profile_logo','type'=>'image','return_format'=>'id','preview_size'=>'thumbnail'],
        ],
        'location' => [[['param'=>'options_page','operator'=>'==','value'=>'verified-profile-settings']]],
    ]);
}
