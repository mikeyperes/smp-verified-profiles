<?php namespace smp_verified_profiles;


/**
 * 2) Render the page with the button + report container + inline jQuery
 */
function render_reprocess_profile_schema_page() { ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Reprocess All Profile Schema Objects', 'smp_verified_profiles' ); ?></h1>
        <button id="smp-vp-reprocess-schema" class="button button-primary">
            <?php esc_html_e( 'Reprocess all profile schema objects', 'smp_verified_profiles' ); ?>
        </button>
        <div id="smp-vp-reprocess-report"
             style="margin-top:20px; padding:15px; background:#fff; border:1px solid #ccc; max-height:400px; overflow:auto;">
        </div>
    </div>

    <script>
jQuery(document).ready(function($){
    var offset    = 0,
        batchSize = 20,
        total     = 0;

    $('#smp-vp-reprocess-schema').on('click', function(){
        $(this).prop('disabled', true);
        $('#smp-vp-reprocess-report')
            .empty()
            .append('<p>Starting…</p>');
        processBatch();
    });

    function processBatch(){
        $.post( ajaxurl, {
            action:     'smp_vp_reprocess_schema',
            offset:     offset,
            batch_size: batchSize
        })
        .done(function(res){
            if ( ! res.success ) {
                $('#smp-vp-reprocess-report')
                    .append('<p style="color:red;">Error: ' + res.data.message + '</p>');
                return;
            }

            total = res.data.total;

            $.each(res.data.items, function(i, item){
                // escape HTML in the schema for safe display
                var escapedSchema = $('<div>').text(item.schema).html();

                $('#smp-vp-reprocess-report').append(
                    '<div style="margin-bottom:20px;">' +
                      '<p>' +
                        '<strong>Post ID ' + item.post_id + '</strong> – ' +
                        '<a href="' + item.admin_link + '" target="_blank">Edit</a> | ' +
                        '<a href="' + item.view_link + '" target="_blank">View</a>' +
                      '</p>' +
                      '<pre style="background:#f9f9f9;padding:10px;border:1px solid #ddd;white-space:pre-wrap;">' +
                        escapedSchema +
                      '</pre>' +
                    '</div>'
                );
            });

            offset += batchSize;
            $('#smp-vp-reprocess-report').append(
              '<p>Processed ' + Math.min(offset, total) + ' of ' + total + '</p>'
            );

            if ( offset < total ) {
                processBatch();
            } else {
                $('#smp-vp-reprocess-report')
                  .append('<p><strong>✅ Completed processing ' + total + ' profiles.</strong></p>');
            }
        })
        .fail(function(){
            $('#smp-vp-reprocess-report')
                .append('<p style="color:red;">AJAX request failed.</p>');
        });
    }
});
</script>

<?php }

/**
 * 3) Handle the AJAX in small batches
 */
add_action( 'wp_ajax_smp_vp_reprocess_schema', __NAMESPACE__ . '\\ajax_reprocess_schema' );
function ajax_reprocess_schema() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Permission denied.' ] );
    }

    // get dynamic post type from settings
    $settings  = get_verified_profile_settings();
    $post_type = isset( $settings['slug'] ) ? sanitize_key( $settings['slug'] ) : 'profile';

    $offset     = isset( $_POST['offset'] )     ? intval( $_POST['offset'] )     : 0;
    $batch_size = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 20;

    // total published posts of this type
    $counts = wp_count_posts( $post_type );
    $total  = isset( $counts->publish ) ? $counts->publish : 0;

    // fetch this batch of IDs
    $q = new \WP_Query( [
        'post_type'      => $post_type,
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'fields'         => 'ids',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    ] );

    $items = [];
    foreach ( $q->posts as $post_id ) {
        // 1) generate & save schema
        generate_schema_markup( $post_id );

        // 2) now retrieve what was saved
        $schema = get_field( 'schema_markup', $post_id );

        // 3) build your response item
        $items[] = [
            'post_id'    => $post_id,
            'schema'     => $schema,
            'admin_link' => get_edit_post_link( $post_id ),
            'view_link'  => get_permalink( $post_id ),
        ];
    }

    wp_send_json_success( [
        'total'    => $total,
        'batch'    => count( $items ),
        'offset'   => $offset,
        'items'    => $items,
        'postType' => $post_type,
    ] );
}

