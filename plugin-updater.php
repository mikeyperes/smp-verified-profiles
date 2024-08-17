<? function smp_verified_profiles_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_slug = 'smp-verified-profiles/smp-verified-profiles.php';
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug);
    $current_version = $plugin_data['Version'];

    $repo_uri = 'https://api.github.com/repos/yourusername/smp-verified-profiles/releases/latest';
    $response = wp_remote_get($repo_uri);

    if (is_wp_error($response)) {
        return $transient;
    }

    $response_data = json_decode(wp_remote_retrieve_body($response));

    if (isset($response_data->tag_name) && version_compare($current_version, $response_data->tag_name, '<')) {
        $plugin_info = array(
            'slug' => dirname($plugin_slug),
            'new_version' => $response_data->tag_name,
            'url' => $plugin_data['PluginURI'],
            'package' => $response_data->zipball_url,
        );
        $transient->response[$plugin_slug] = (object) $plugin_info;
    }

    return $transient;
}

add_filter('site_transient_update_plugins', 'smp_verified_profiles_check_for_updates');