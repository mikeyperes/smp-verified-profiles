<?php namespace smp_verified_profiles;
/**
 * Restrict Category Selection to 'Person' or 'Organization' for 'Profile' CPT
 * Converts category selection to radio buttons, allowing only one selection.
 */
function enable_profile_category_meta_box_adjustment() {
add_action('add_meta_boxes', 'smp_verified_profiles\adjust_profile_category_meta_box', 30, 2);
}
/**
 * Adjust the category meta box for the 'profile' custom post type (CPT)
 * Replaces the category checkbox list with a radio button selection for 'Person' or 'Organization'.
 *
 * @param string $post_type The post type of the current post.
 * @param WP_Post $post The current post object.
 */
function adjust_profile_category_meta_box($post_type, $post) {
    // Restrict this to administrator users and 'profile' post type
    if (!current_user_can('administrator') || $post_type !== 'profile') {
        return;
    }

    // Remove the default category meta box and add a custom one with radio buttons
    remove_meta_box('categorydiv', $post_type, 'side');
    add_meta_box('profile-category-radio', 'Category', 'smp_verified_profiles\render_profile_category_radio_meta_box', $post_type, 'side', 'default', ['taxonomy' => 'category']);
}

/**
 * Render the custom category meta box with radio buttons for 'Person' and 'Organization'.
 *
 * @param WP_Post $post The current post object.
 * @param array $box The meta box arguments, including the taxonomy name.
 */
function render_profile_category_radio_meta_box($post, $box) {
    // Set up default arguments and extract them
    $defaults = ['taxonomy' => 'category'];
    $args = wp_parse_args($box['args'], $defaults);
    $taxonomy = get_taxonomy($args['taxonomy']);

    // Get term IDs for 'person' and 'organization' categories
    $person_term = get_term_by('slug', 'person', $taxonomy->name);
    $organization_term = get_term_by('slug', 'organization', $taxonomy->name);

    // Ensure the terms exist
    if (!$person_term || !$organization_term) {
        echo '<p><strong>Person or Organization categories are missing.</strong></p>';
        return;
    }

    // Fetch currently selected category terms for the post
    $selected_terms = wp_get_object_terms($post->ID, $taxonomy->name, ['fields' => 'ids']);
    $person_id = $person_term->term_id;
    $organization_id = $organization_term->term_id;

    ?>
    <div id="taxonomy-<?php echo $taxonomy->name; ?>" class="categorydiv">
        <ul id="<?php echo $taxonomy->name; ?>-tabs" class="category-tabs">
            <li class="tabs"><a href="#<?php echo $taxonomy->name; ?>-all"><?php echo $taxonomy->labels->all_items; ?></a></li>
        </ul>

        <div id="<?php echo $taxonomy->name; ?>-all" class="tabs-panel">
            <input type="hidden" name="tax_input[<?php echo $taxonomy->name; ?>][]" value="0" />
            
            <?php
            // Render radio buttons for 'Person' and 'Organization'
            $person_checked = in_array($person_id, $selected_terms) || empty($selected_terms) ? 'checked="checked"' : '';
            $organization_checked = in_array($organization_id, $selected_terms) ? 'checked="checked"' : '';

            // Radio button for 'Person' category
            echo '<label><input type="radio" name="tax_input[' . $taxonomy->name . '][]" value="' . $person_id . '" ' . $person_checked . '> Person</label><br />';

            // Radio button for 'Organization' category
            echo '<label><input type="radio" name="tax_input[' . $taxonomy->name . '][]" value="' . $organization_id . '" ' . $organization_checked . '> Organization</label><br />';
            ?>
        </div>
    </div>
    <?php
}