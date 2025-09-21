<?php namespace hpr_distributor;

function display_settings_overview() {
    // Get the base URL of the WordPress installation dynamically
    $base_url = site_url();

    ?>
    <!-- Settings Overview Panel --> 
    <div class="panel" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0; background-color: #f9f9f9;">
        <h2 class="panel-title" style="font-size: 24px; font-weight: bold; margin-bottom: 15px;">Settings Overview</h2>

        <!-- RSS URL Display -->
        <p style="font-size: 16px;">
            RSS URL: <a href="https://hexaprwire.com/feed/internal-rss" target="_blank" style="color: #0073aa; text-decoration: none;">https://hexaprwire.com/feed/internal-rss</a>
        </p>

        <!-- RSS Checklist -->
        <h3 style="font-size: 18px; font-weight: bold; margin-top: 20px;">RSS Checklist</h3>
        <ul style="list-style-type: disc; margin-left: 20px; font-size: 16px;">
            <li>Post type: <strong>press-release</strong></li>
            <li>FIFU enabled</li>
            <li>Category: <strong>press-release</strong></li>
            <li>User: <strong>hexa-pr-wire</strong></li>
            <li>Update every hour (check for changes)</li>
        </ul>

        <!-- Cron and Auto Delete Links Section -->
        <h3 style="font-size: 18px; font-weight: bold; margin-top: 20px;">Cron and Auto Delete Links</h3>
        <ul style="list-style-type: disc; margin-left: 20px; font-size: 16px;">
            <li>View Purge List from Hexa PR Wire<br /><a href="https://hexaprwire.com/wp-admin/admin-ajax.php?action=purge_release_list" target="_blank">https://hexaprwire.com/wp-admin/admin-ajax.php?action=purge_release_list</a></li>
            <li>View Crons<br /><a href="<?php echo esc_url($base_url . '/wp-admin/admin-ajax.php?action=view_crons'); ?>" target="_blank"><?php echo esc_url($base_url . '/wp-admin/admin-ajax.php?action=view_crons'); ?></a></li>
            <li>Process Press Release Purge List<br /><a href="<?php echo esc_url($base_url . '/wp-admin/admin-ajax.php?action=hexaprwire_process_deletes'); ?>" target="_blank"><?php echo esc_url($base_url . '/wp-admin/admin-ajax.php?action=hexaprwire_process_deletes'); ?></a></li>
        </ul>
    </div>
    <?php
}